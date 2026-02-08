<?php

namespace app\common\model\xiluxc\order;

use app\common\library\Auth;
use app\common\model\User;
use app\common\model\xiluxc\activity\Coupon;
use app\common\model\xiluxc\activity\UserCoupon;
use app\common\model\xiluxc\brand\BranchPackage;
use app\common\model\xiluxc\brand\Package;
use app\common\model\xiluxc\brand\PackageService;
use app\common\model\xiluxc\brand\Shop;
use app\common\model\xiluxc\brand\ShopBranchService;
use app\common\model\xiluxc\brand\ShopService;
use app\common\model\xiluxc\current\Config;
use app\common\model\xiluxc\user\UserAccount;
use app\common\model\xiluxc\user\UserPackage;
use app\common\model\xiluxc\user\UserPackageService;
use app\common\model\xiluxc\user\UserShopAccount;
use app\common\model\xiluxc\user\UserShopVip;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\Hook;
use think\Model;
use traits\model\SoftDelete;
use function fast\array_get;

class Order extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'xiluxc_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'paid_time_text',
        'appoint_date_text',
        'pay_type_text',
        'state',
        'state_text'
    ];

    const ORDER_STATUS = [
        '-1'=>'已取消',
        '0'=>'待核销',
        '1'=>'待评价',
        '2'=>'已完成',
    ];
    
    public function getTypeList()
    {
        return ['service' => __('Type service'), 'package' => __('Type package')];
    }

    public function getStatusList()
    {
        return ['unpaid' => __('Status unpaid'), 'paid' => __('Status paid')];
    }

    public function getPayTypeList(){
        return ['1' => __('微信支付'), '2' => __('余额支付'), '3' => __('套餐抵扣')];
    }

    public function getRefundStatusList()
    {
        return ['0' => __('Refund_status 0'), '1' => __('Refund_status 1'), '-1' => __('Refund_status -1')];
    }

    public function getPlatformList()
    {
        return ['H5' => __('Platform H5'), 'WechatOfficialAccount' => __('Platform WechatOfficialAccount'), 'WechatMiniProgram' => __('Platform WechatMiniProgram'), 'App' => __('Platform App'), 'PC' => __('Platform PC')];
    }

    public function getPayTypeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['pay_type']) ? $data['pay_type'] : '');
        $list = $this->getPayTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPaidTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['paid_time']) ? $data['paid_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getAppointDateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['appoint_date']) ? $data['appoint_date'] : '');
        return is_numeric($value) ? date("Y-m-d", $value) : $value;
    }

    //状态对应文本消息
    public function getStateTextAttr($value, $data)
    {
        $state = $this->getAttr('state');
        return $state !== '' ? self::ORDER_STATUS[$state] :'';
    }

    /**
     * 获取对订单状态
     */
    public function getStateAttr($value, $data)
    {
        if(!isset($data['status']) || !in_array($data['status'],['cancel','paid'])){
            return '';
        }else if($data['status'] === 'cancel'){
            return '-1';
        } else if($data['refund_status'] == '-1'){
            $state = '5';//退款失败
        } else if($data['refund_status'] == '2'){
            $state = '4';//退款成功
        } else if($data['refund_status'] == '1'){
            $state = '3';//退款中
        }else{
            if($data['comment_status'] == '1'){
                $state = '2'; //已完成
            } else if ($data['verify_status'] == '1') {
                //取消
                $state = '1'; //待评价
            }else{
                $state = '0'; //待使用
            }
        }
        return $state;
    }

    public function shop(){
        return $this->belongsTo(Shop::class,'shop_id','id',[],'left')->setEagerlyType(0);
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','id',[],'left')->setEagerlyType(0);
    }

    public function orderItem(){
        return $this->hasOne(OrderItem::class,'order_id','id',[],'inner')->setEagerlyType(0);
    }

    /**
     * 下单前订单数据
     * @param $params
     * @param $userId
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function preOrder($params,$userId){
        $type = array_get($params,'type');
        $coupon_id = array_get($params,'coupon_id');
        $shopId = array_get($params,'shop_id');
        if(!$shopId){
            throw new Exception("门店参数错误");
        }
        if($type == 'service'){
            $shopServiceModel = new ShopService;
            $shopServiceId = array_get($params,'service_id');
            $shopBranchService = new ShopBranchService();
            $shopBranchService = $shopBranchService->normal()->where("shop_id",$shopId)->where("shop_service_id",$shopServiceId)->find();
            if(!$shopBranchService){
                throw new Exception("服务不存在或已下架");
            }
            $servicePriceId = array_get($params,'service_price_id');
            $packageId = array_get($params,'package_id');
            $shopService = $shopServiceId ? $shopServiceModel->with(['service','service_price'])->where($shopServiceModel->getTable().'.id',$shopServiceId)->find() :null;
            if(!$shopService || $shopService->status == 'hidden'){
                throw new Exception(__("服务不存在或已下架"));
            }
            if(!$shopService['service_price']){
                throw new Exception("服务未设置规格");
            }
            if($servicePriceId){
                $servicePrice = array_column(collection($shopService['service_price'])->toArray(),null,'id');
                $servicePriceChoosed = $servicePrice[$servicePriceId];
            }else{
                $servicePriceChoosed = $shopService['service_price'][0];
            }
            $shopService['service_price_choose'] = $servicePriceChoosed;
            $isShopVip = UserShopVip::isShopVip($shopBranchService['shop_id'],$userId);
            $totalPrice = $isShopVip ? $shopService['service_price_choose']['vip_price'] : $shopService['service_price_choose']['salesprice'];


            //如果不是套餐的，查询是否有可用的套餐
            $userPackage = null;
            if(!$packageId){
                $userPackageModel = new UserPackage();
                $currentName = $userPackageModel->getTable();
                $userPackage = $userPackageModel->with(['packageService'])->where('user_id',$userId)->whereExists(function ($query) use($currentName,$servicePriceChoosed){
                    $packageService = (new UserPackageService())->getQuery()->getTable();
                    $query->table($packageService)->where($currentName . '.id=' . $packageService . '.user_package_id')->where("service_price_id",$servicePriceChoosed['id'])->where("stock",'>',0);
                    return $query;
                })->where('status','ing')->order("id",'asc')->find();
            }

            //推荐套餐
            $shopPackages = [];
            $packageChoosed = null;
            if(!$userPackage){
                $package = new Package();
                $currentName = $package->getTable();
                $shopPackages = $package->with(['package_service2'=>function($q){
                    $q->with(['service','service_price']);
                }])->whereExists(function ($query) use($currentName,$servicePriceChoosed){
                    $packageService = (new PackageService())->getQuery()->getTable();
                    $query->table($packageService)->where($currentName . '.id=' . $packageService . '.package_id')->where("service_price_id",$servicePriceChoosed['id']);
                    return $query;
                })->whereExists(function ($query) use($currentName,$shopBranchService){
                    $shopBranchPackage = (new BranchPackage())->getQuery()->getTable();
                    $query->table($shopBranchPackage)->where($currentName . '.id=' . $shopBranchPackage . '.shop_package_id')->where("shop_id",$shopBranchService['shop_id'])->where("status",'normal');
                })->select();
                foreach ($shopPackages as $shopPackage){
                   $shopPackage['checked'] = $packageId == $shopPackage['id']?1:0;
                    $packageChoosed = $packageId == $shopPackage['id'] ? $shopPackage : null;
                }
                if($packageChoosed){
                    $totalPrice = $isShopVip ? $packageChoosed['vip_price'] : $packageChoosed['salesprice'];
                    list($couponList,$useCoupon,$couponPrice,$payPrice) = self::getOrderCoupon(2,$packageChoosed,$userId,$totalPrice,$coupon_id);
                }else{
                    list($couponList,$useCoupon,$couponPrice,$payPrice) = self::getOrderCoupon(1,$shopService,$userId,$totalPrice,$coupon_id);
                }
            }else{
                list($couponList,$useCoupon,$couponPrice,$payPrice) = self::getOrderCoupon(1,$shopService,$userId,$totalPrice,$coupon_id);
            }
            $shopPrice = $payPrice;
            //积分抵扣金额
            $userAccount = UserAccount::addAccount($userId);
            $shopinfo = Config::getMyConfig('shopinfo');
            $scoreRate = !empty($shopinfo['score_rate']) ? $shopinfo['score_rate'] : 0;
            $points = $scoreRate ? bcmul(bcdiv($scoreRate,100,2),$payPrice) : 0;
            if($userAccount['points'] <= $points){
                $points = $userAccount['points'];
            }
            $points_fee = $points>0 ? bcdiv($points,100,2) : 0;
            if($use_points_status = array_get($params,'use_points_status')){
                $payPrice = bcsub($payPrice,$points_fee,2);
            }
            $userShopAccount = UserShopAccount::addAccount($userId,$shopBranchService['shop_id']);
            return [
                'coupon'        =>  $useCoupon,
                'data'          =>  $shopService,
                'shop_branch_service'=>  $shopBranchService,
                'total_price'   =>  $totalPrice,
                'shop_price'     =>  $shopPrice, //扣除优惠券的
                'pay_price'     =>  $payPrice,
                'points_fee'     =>  $points_fee,
                'points'        =>  $points,
                'coupon_price'  =>  $couponPrice,
                'coupon_list'   =>  $couponList,
                'is_shop_vip'   =>  $isShopVip,
                'shop_packages' =>  $shopPackages,
                'package_choosed'=> $packageChoosed,//单品里面购买推荐的套餐
                'user_shop_account'=>   $userShopAccount, // 个人在当前门店的余额,
                'user_package'  =>  $userPackage,
                'user_account'  =>  $userAccount
            ];
        }
        else if($type == 'package'){
            $packageId = array_get($params,'package_id');
            $shopBranchPackage = new BranchPackage();
            $shopBranchPackage = $shopBranchPackage->normal()->where("shop_id",$shopId)->where("shop_package_id",$packageId)->find();
            if(!$shopBranchPackage){
                throw new Exception("套餐不存在或已下架");
            }
            $package = $packageId ? Package::with(['package_service2'=>function($q){
                $q->with(['service','service_price','shop_service'=>function($q){
                    $q->withField(['id','image']);
                }]);
            }])->where('id',$packageId)->find():null;
            if(!$package || $package->status == 'hidden'){
                throw new Exception(__("套餐不存在或已下架"));
            }
            $isShopVip = UserShopVip::isShopVip($shopBranchPackage['shop_id'],$userId);
            $totalPrice = $isShopVip ? $package['vip_price'] : $package['salesprice'];
            list($couponList,$useCoupon,$couponPrice,$payPrice) = self::getOrderCoupon(2,$package,$userId,$totalPrice,$coupon_id);

            $shopPrice = $payPrice;
            //积分抵扣金额
            $userAccount = UserAccount::addAccount($userId);
            $shopinfo = Config::getMyConfig('shopinfo');
            $scoreRate = !empty($shopinfo['score_rate']) ? $shopinfo['score_rate'] : 0;
            $points = $scoreRate ? bcmul(bcdiv($scoreRate,100,2),$payPrice) : 0;
            if($userAccount['points'] <= $points){
                $points = $userAccount['points'];
            }
            $points_fee = $points>0 ? bcdiv($points,100,2) : 0;
            if($use_points_status = array_get($params,'use_points_status')){
                $payPrice = bcsub($payPrice,$points_fee,2);
            }

            $userShopAccount = UserShopAccount::addAccount($userId,$shopBranchPackage['shop_id']);
            return [
                'coupon'        =>  $useCoupon,
                'data'          =>  $package,
                'total_price'   =>  $totalPrice,
                'shop_price'    =>  $shopPrice,
                'pay_price'     =>  $payPrice,
                'points_fee'     =>  $points_fee,
                'points'        =>  $points,
                'coupon_price'  =>  $couponPrice,
                'coupon_list'   =>  $couponList,
                'is_shop_vip'   =>  $isShopVip,
                'user_shop_account'=>   $userShopAccount, // 个人在当前门店的余额
                'user_account'  =>  $userAccount,
                'shop_branch_package'=>  $shopBranchPackage
            ];
        }

    }

    /**
     * 获取优惠券
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private static function getOrderCoupon($type,$data,$userId,$totalPrice,$couponId){
        //优惠券使用
        $couponPrice = 0;
        $couponList = Coupon::getUserCouponsByType($type,$data['id'],$userId,$totalPrice);
        $useCoupon = null;
        if($couponList){
            if(!$couponId){
                $couponPrice = $couponList[0]['money'];
                $couponList[0]['checked'] = 1;
                $useCoupon = $couponList[0];
            }else{
                foreach ($couponList as $k=>$coupon){
                    if($coupon['id'] == $couponId){
                        $couponPrice = $couponList[$k]['money'];
                        $couponList[$k]['checked'] = 1;
                        $useCoupon = $coupon;
                        break;
                    }
                }
            }
        }
        if($totalPrice<=$couponPrice){
            throw new Exception(__("订单价格不能小于优惠券价格"));
        }
        $payPrice = bcsub($totalPrice,$couponPrice,2);
        return [$couponList,$useCoupon,$couponPrice,$payPrice];
    }


    /**
     * 下单
     */
    public static function createOrder($params,$userId){
        //如果套餐存在，修正为套餐下单
        $paramsInfo = $params;
        if(!empty($paramsInfo['package_id'])){
            $paramsInfo['type'] = 'package';
        }
        $use_points_status = array_get($params,'use_points_status',0);
        $sure = self::preOrder($paramsInfo,$userId);
        $appoint_date = array_get($params,'appoint_date');
        //判断
        $type = array_get($params,'type','service');
        $packageId = array_get($params,'package_id');
        $ext = [];
        if($type == 'service' && $packageId){
            $ext = array_intersect_key($params, array_flip((array)  ['appoint_date', 'service_id', 'service_price_id', 'shop_id', 'type'
            ]));
        }
        Db::startTrans();
        try {
            #1.创建订单
            $order = self::create([
                'type'          =>  $paramsInfo['type'],
                'order_no'      =>  'S'.date('YmdHis').mt_rand(0,9999),
                'user_id'       =>  $userId,
                'order_amount'  =>  $sure['total_price'],
                'appoint_date'  =>  $appoint_date ? strtotime($appoint_date) : null,
                'pay_fee'       =>  $sure['pay_price'],
                'shop_fee'      =>  $sure['shop_price'],
                'points'        =>  $use_points_status?$sure['points']:0,
                'points_fee'    =>  $use_points_status?$sure['points_fee']:0,
                'status'        =>  'unpaid',
                'coupon_id'     =>  $sure['coupon']['id'] ?? 0,
                'coupon_discount_fee'  =>  $sure['coupon_price'],
                'platform'      => array_get($params,'platform','wxmini'),
                'remark'       =>  $params['remark'] ?? '',
                'order_ip'      =>  request()->ip(),
                'shop_id'       =>  $params['shop_id'],
                'brand_id'      =>  $sure['data']['brand_id'] ?? 0,
                'ext'           =>   $ext ? json_encode(['service'=>$ext]) : "",
                'user_package_id'=>  $sure['data']['user_package']['id'] ?? 0
            ]);

            #2.添加服务/套餐
            if($order['type'] == 'service'){
                $item = [
                    'order_id'      =>   $order->id,
                    'data_id'       =>   $sure['data']['service_price_choose']['shop_service_id'],
                    'title'         =>   $sure['data']['service']['name'],
                    'service_price_id'=> $sure['data']['service_price_choose']['id'],
                    'branch_service_id'=> $sure['shop_branch_service']['id'],
                    'sku_text'      =>   $sure['data']['service_price_choose']['title'],
                    'image'         =>   $sure['data']['image_text'],
                    'salesprice'    =>   $sure['data']['service_price_choose']['salesprice'],
                    'vip_price'     =>   $sure['data']['service_price_choose']['vip_price'],
                    'ext'           =>   json_encode(is_object($sure['data']['service_price_choose']) ? $sure['data']['service_price_choose']->toArray() : $sure['data']['service_price_choose']),
                    'discount_fee'  =>   $sure['coupon_price']
                ];
            }else{
                $item = [
                    'order_id'      =>   $order->id,
                    'data_id'       =>   $sure['data']['id'],
                    'branch_service_id'=> $sure['shop_branch_package']['id'],
                    'title'         =>   $sure['data']['name'],
                    'image'         =>   $sure['data']['image_text'],
                    'salesprice'    =>   $sure['data']['salesprice'],
                    'vip_price'     =>   $sure['data']['vip_price'],
                    'ext'           =>   json_encode(collection($sure['data']['package_service2'])->toArray()),
                    'discount_fee'  =>   $sure['coupon_price'],
                ];
            }
            $orderItem = OrderItem::create($item);
            #3.如果使用了优惠券，则添加优惠券记录
            if($sure['coupon']){
                $orderCoupon = OrderCoupon::create([
                    'order_id'     =>   $order->id,
                    'coupon_id'    =>   $sure['coupon']['id'],
                    'coupon_name'  =>   $sure['coupon']['name'],
                    'coupon_money' =>   $sure['coupon']['money'],
                    'at_least'     =>   $sure['coupon']['at_least'],
                ]);
            }
        }catch (PDOException|Exception $e){
            Db::rollback();
            throw $e;
        }
        Db::commit();
        return self::get($order->id);
    }

    /**
     * 套餐直接核销订单
     */
    public static function createOrderByPackage($userPackage,$userPackageService,$verifier){
        $all_amount = 0; //所有子服务总价格
        foreach ($userPackage->package_service as $item){
            $all_amount += $item['salesprice']*$item['total_count'];
        }
        //当前服务所占比例
        $percent = bcdiv($userPackageService['salesprice'] * $userPackageService['total_count'],$all_amount,2);
        //当前服务总金额
        $service_amount = bcmul($percent,$userPackage->order_amount,2);
        $order_amount = bcdiv($service_amount,$userPackageService->total_count,2);
        try {
            #1.创建订单
            $order = self::create([
                'type'          =>  'service',
                'order_no'      =>  'S'.date('YmdHis').mt_rand(0,9999),
                'user_id'       =>  $userPackage['user_id'],
                'order_amount'  =>  $order_amount,
                'appoint_date'  =>  strtotime(Config::getTodayTime()),
                'shop_fee'      =>  $order_amount,
                'pay_fee'       =>  $order_amount,
                'status'        =>  'unpaid',
                'coupon_id'     =>  0,
                'coupon_discount_fee'  =>  0,
                'platform'      => 'wxmini',
                'remark'       =>  $params['remark'] ?? '',
                'order_ip'      =>  request()->ip(),
                'shop_id'       =>  $verifier['shop_id'],
                'brand_id'      =>  $userPackage['brand_id'],
                'user_package_id'=>  $userPackage['id'],
            ]);
            #2.添加服务
            $item = [
                'order_id'      =>   $order->id,
                'data_id'       =>   $userPackageService['shop_service_id'],
                'title'         =>   $userPackageService['service_name'],
                'service_price_id'=> $userPackageService['service_price_id'],
                'sku_text'      =>   $userPackageService['service_price_name'],
                'image'         =>   $userPackageService['service_image'],
                'salesprice'    =>   $userPackageService['salesprice'],
                'vip_price'     =>   $userPackageService['vip_price'],
                'ext'           =>   json_encode($userPackageService->toArray()),
                'discount_fee'  =>   0,
            ];
            $orderItem = OrderItem::create($item);
        }catch (PDOException|Exception $e){
            throw $e;
        }
        return self::get($order->id);
    }


    public function scopeService($query){
        return $query->where("type",'service');
    }

    /**
     * 订单列表条件
     */
    public static function orderWhere($state){
        $whereList = [
            'all' =>['status'=>['in',['cancel','paid']]],//全部
            'unuse' =>['status'=>'paid','verify_status'=>'0','refund_status'=>['in',['0','-1']]],//待使用
            'uncomment' =>['status'=>'paid','verify_status'=>'1','comment_status'=>'0','refund_status'=>['in',['0','-1']]],//待评价
            'finished' =>['status'=>'paid','verify_status'=>'1','comment_status'=>'1','refund_status'=>['in',['0','-1']]],//已完成
        ];
        $orderList = ['all'=>'createtime desc','unuse'=>'paid_time desc','uncomment'=>'verifytime desc','finished'=>'commenttime desc'];
        return [$whereList[$state],$orderList[$state]];

    }

    /**
     * 数量
     * @param $state
     * @param $userId
     * @return mixed
     */
    public static function getCount($state,$userId){
        list($where,$order) = self::orderWhere($state);
        return  (new self)->service()
            ->where($where)
            ->where('user_id',$userId)
            ->order($order)
            ->count();
    }

    /**
     * 服务订单列表
     * @param array $param
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public static function orderLists($params,$userId){
        $state = array_get($params,'state','all');
        list($where,$order) = self::orderWhere($state);
        $orderModel = new self();
        $pagesize = array_get($params, 'pagesize', 10);
        $list = $orderModel->service()
            ->with(['orderItem'])
            ->where($where)
            ->where($orderModel->getTable().'.user_id',$userId)
            ->order($order)
            ->paginate($pagesize)
            ->each(function ($row){
                $row->relationQuery(['shop'=>function($q){
                    $q->field(['id','name','image']);
                }]);
                $row['shop'] && $row->shop->append(['image_text']);
            });
        return $list;
    }

    /**
     * @param $orderId
     * @return array|bool|false|\PDOStatement|string|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function detail($params){
        $auth = Auth::instance();
        $orderId = array_get($params,'order_id');
        $lat = array_get($params,'lat');
        $lng = array_get($params,'lng');
        if($lng && $lat){
            $field = "*,(ST_DISTANCE(POINT(".$lng.", ".$lat."), POINT(lng, lat)) * 111195) AS distance";
        }else{
            $field = "*";
        }
        $row = self::where('id',$orderId)->where('user_id',$auth->id)->find();
        if($row){
            $row->relationQuery(['order_item']);
            $shop = Shop::field($field)->where("id",$row->shop_id)->find();
            $shop->distance = isset($shop->distance) ? ($shop->distance>=1000 ? bcdiv($shop->distance,1000,1).'km' : bcadd($shop->distance,0,1).'m') : '';
            $row['shop'] = $shop;
            $qrcode = OrderQrcode::where("order_id",$row->id)->find();
            if(!$qrcode){
                $platform = array_get($params,'platform');
                #创建核销二维码
                list($token,$code,$qrcode) = xiluxc_qrcode_token($row->id,$platform);
                $qrcode = OrderQrcode::create([
                    'order_id'  =>  $row->id,
                    'qrcode'    =>  $qrcode,
                    'code'      =>  $code,
                    'verifier_status'=> 0,
                    'token'     =>  $token
                ]);
            }
            $row['qrcode'] = $qrcode;
        }else{
            exception("未找到订单");
        }

        return $row;
    }

    /**
     * @param $orderId
     * @return array|bool|false|\PDOStatement|string|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function verifyDetail($params){
        $auth = Auth::instance();
        $orderId = array_get($params,'order_id');
        $lat = array_get($params,'lat');
        $lng = array_get($params,'lng');
        if($lng && $lat){
            $field = "*,(ST_DISTANCE(POINT(".$lng.", ".$lat."), POINT(lng, lat)) * 111195) AS distance";
        }else{
            $field = "*";
        }
        $qrcode =  OrderQrcode::where("verifier_id",$auth->id)->where('order_id',$orderId)->find();
        if(!$qrcode){
            exception("未找到已核销的订单");
        }
        $row = self::where('id',$orderId)->find();
        if($row){
            $row->relationQuery(['order_item']);
            $shop = Shop::field($field)->where("id",$row->shop_id)->find();
            $shop->distance = isset($shop->distance) ? ($shop->distance>=1000 ? bcdiv($shop->distance,1000,1).'km' : bcadd($shop->distance,0,1).'m') : '';
            $row['shop'] = $shop;
        }else{
            exception("未找到订单");
        }

        return $row;
    }


    /**
     * 支付成功
     */
    public static function payNotify($order_no,$trade_no){
        $order = self::where('order_trade_no',$order_no)->find();
        if(!$order){
            throw new Exception("订单不存在");
        }
        if($order->status == 'paid'){
            throw new Exception("不要重复支付");
        }
        $order->trade_no = $trade_no;
        $order->status = 'paid';
        $order->paid_time = time();
        $order->save();
        #优惠券变成已使用
        $orderCoupon = OrderCoupon::where("order_id",$order->id)->find();
        if($orderCoupon){
            $result = UserCoupon::where('user_id', $order['user_id'])->where('coupon_id',$orderCoupon['coupon_id'])->update(['use_status'=>1,'use_time'=>time()]);
        }

        #如果是购买的套餐，则添加套餐信息
        if($order['type'] === 'package'){
            UserPackage::addPackage($order);
        }else{
            #服务销量
            ShopService::where("id",$order->order_item['data_id'])->setInc('sales',1);
            Shop::where("id",$order['shop_id'])->setInc('sales',1);
        }
        #如果有抵扣积分，直接扣除
        if($order->points>0){
            $type = $order['type'] === 'package'?'package_deduction':'service_deduction';
            $params = [
                'type'  =>  $type,
                'order' =>  $order
            ];
            Hook::listen('xiluxc_reduce_score',$params);
        }
        #如果是付款的，则给积分
        if(in_array($order['pay_type'],[1,2])){
            $type = $order['type'] === 'package'?'package_order':'service_order';
            $params = [
                'type'  =>  $type,
                'order' =>  $order
            ];
            Hook::listen("xiluxc_add_score",$params);
        }
        if($order['type'] == 'package'){
            Hook::listen("xiluxc_package_buy_message",$order);
        }else{
            Hook::listen("xiluxc_service_buy_message",$order);
        }
        //添加门店会员
        $data = ['order'=>$order];
        Hook::listen('xiluxc_shop_user',$data);
        //创建服务并支抵扣服务
        $ext = $order['ext'] ? json_decode($order['ext'],true) : [];
        if($order['type'] == 'package' && !empty($ext['service'])){
            self::addPackageService($order,$ext['service']);
        }
        return true;
    }

    /**
     * 创建服务订单并抵扣
     * @param $order
     */
    private static function addPackageService($order,$service){
        $orderinfo = self::createOrder($service,$order->user_id);
        $userPackage = UserPackage::get(['user_id'=>$order->user_id,'order_id'=>$order->id]);
        if(!$userPackage){
            return false;
        }
        $userPackageService = UserPackageService::where('user_package_id',$userPackage->id)->where('service_price_id',$orderinfo->order_item->service_price_id)->find();
        if(!$userPackageService){
            return false;
        }
        //支付
        $orderinfo->pay_type = 3;
        $orderinfo->user_package_id = $userPackage->id; //使用套餐
        $orderinfo->order_trade_no = 'SP' . date('YmdHis') . mt_rand(10, 99999);
        $orderinfo->allowField(true)->save();
        if($userPackageService->stock - 1 <= 0){
            $userPackageService->status = 'finished';
        }
        $userPackageService->stock = Db::raw("stock-1");
        $userPackageService->use_count = Db::raw("use_count+1");
        $userPackageService->save();
        $total_count = $count = 0;
        foreach ($userPackage->package_service as $v){
            $total_count++;
            if($v['status'] == 'finished'){
                $count++;
            }
        }
        if($total_count == $count){
            $userPackage->allowField(['status'=>'finished']);
        }
        self::payNotify($orderinfo->order_trade_no,'package');
        return true;
    }

}

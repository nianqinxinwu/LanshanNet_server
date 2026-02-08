<?php


namespace app\api\controller\xiluxc;


use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\xiluxc\brand\BranchPackage;
use app\common\model\xiluxc\brand\Package;
use app\common\model\xiluxc\brand\Shop;
use app\common\model\xiluxc\brand\ShopBranchService;
use app\common\model\xiluxc\brand\ShopBrand;
use app\common\model\xiluxc\brand\ShopVerifier;
use app\common\model\xiluxc\order\OrderQrcode;
use app\common\model\xiluxc\user\UserPackageService;
use app\common\model\xiluxc\user\UserPackage AS UserPackageModel;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\Hook;
use function fast\array_get;

class UserPackage extends XiluxcApi
{

    /**
     * 我的套餐
     */
    public function lists(){
        $params = $this->request->param('');
        $model  = new UserPackageModel();
        $pagesize = array_get($params,'pagesize');
        $where[$model->getTable().'.user_id'] = $this->auth->id;
        $lists = $model->with(['shop'=>function($q){
            $q->withField(['id','name','image','address','lat','lng']);
        }, 'brand'=>function($q){
                $q->withField(['id','brand_name','logo']);
            }
        ])
            ->where($where)
            ->order('id','desc')
            ->paginate($pagesize)
            ->each(function ($row){
                if(in_array($row->status,['apply_refund','refund'])){
                    $row->append(['status_text']);
                }
                $row->unuse_num = UserPackageService::where("user_package_id",$row->id)->sum('stock');
                $row->use_num = UserPackageService::where("user_package_id",$row->id)->sum('use_count');
                $row->total_num = UserPackageService::where("user_package_id",$row->id)->sum('total_count');
                $row->shop_num = BranchPackage::where("shop_package_id",$row->package_id)->where("status",'normal')->count();
                $row->shop  && $row->shop->append(['image_text']);
            });
        $this->success('查询成功',$lists);
    }

    /**
     * 可使用门店
     */
    public function package_shops(){
        $params = $this->request->param('');
        $packageId = array_get($params,'package_id');
        $shopPackage = $packageId?Package::get($packageId) : null;
        if(!$shopPackage){
            $this->error("未找到套餐");
        }
        $query = new Shop();
        $lat = array_get($params,'lat');
        $lng = array_get($params,'lng');
        if($lng && $lat){
            $query->field("id,name,image,point,lat,lng,address,sales,(ST_DISTANCE(POINT(".$lng.", ".$lat."), POINT(lng, lat)) * 111195) AS distance")->order("distance",'asc');
        }else{
            $query->field("id,name,image,point,lat,lng,address,sales")->order("id",'desc');
        }
        if($shopPackage->brand_id){
            $shopBrand = ShopBrand::get($shopPackage->brand_id);
            if(!$shopBrand){
                $this->error("品牌未找到");
            }
            $query->where("brand_id",$shopBrand->user_id);
        }else{
            $query->where("id",$shopPackage->shop_id);
        }
        $currentName = $query->getTable();
        $query->whereExists(function ($query) use($currentName,$shopPackage){
            $branchPackage = (new BranchPackage())->getQuery()->getTable();
            $query->table($branchPackage)->where($currentName . '.id=' . $branchPackage . '.shop_id')->where("shop_package_id",$shopPackage['id'])->where("status",'normal');
            return $query;
        });
        $lists = $query->paginate(request()->param('pagesize',10))
            ->each(function ($row){
                $row->append(['image_text']);
                $row->distance = isset($row->distance) ? ($row->distance>=1000 ? bcdiv($row->distance,1000,1).'km' : bcadd($row->distance,0,1).'m') : '';
            });
        $this->success('',$lists);
    }

    /**
     * 我的套餐详情
     */
    public function mypackage_detail(){
        $params = $this->request->param();
        $model  = new UserPackageModel;
        $packageId = array_get($params,'package_id');
        $platform = array_get($params,'platform','wxmini');
        $lat = array_get($params,'lat');
        $lng = array_get($params,'lng');
        if($lng && $lat){
            $field = "*,(ST_DISTANCE(POINT(".$lng.", ".$lat."), POINT(lng, lat)) * 111195) AS distance";
        }else{
            $field = "*";
        }
        $row = $model->get(['id'=>$packageId,'user_id'=>$this->auth->id]);
        if(!$row){
            $this->error("套餐不存在");
        }
        $row->relationQuery(['shop','package_service','ordering','brand']);
        $shop = Shop::field($field)->where("id",$row->shop_id)->find();
        $shop->distance = isset($shop->distance) ? ($shop->distance>=1000 ? bcdiv($shop->distance,1000,1).'km' : bcadd($shop->distance,0,1).'m') : '';
        $row['shop'] = $shop;
        if(!$row->qrcode){
            #创建核销二维码
            list($token,$code,$qrcode) = xiluxc_qrcode_token($row->id,$platform,"pages/to_offset2/to_offset2");
            $row->allowField(['qrcode','code','token'])->save(['qrcode'=>$qrcode,'code'=>  $code,'token'=>  $token]);
        }
        #核销记录
        $model = new OrderQrcode();
        $verify_ists = $model->where($model->getTable().".user_package_id",$row->id)->order("id",'desc')->select();
        foreach ($verify_ists as $verify_ist){
            $verify_ist->relationQuery(['ordering.order_item']);
        }
        unset($verify_ist);
        $row->verify_ist = $verify_ists;
        $this->success('查询成功',$row);
    }

    /**
     * 核销订单确认信息
     */
    public function package_confirm(){
        $token = $this->request->post('token');
        $row = UserPackageModel::where('token',$token)->find();
        if(!$row){
            $this->error("套餐未找到");
        }
        if($row->status !== "ing"){
            $this->error("用户套餐状态不可使用");
        }
        //品牌门店判断核销权限
        if($row['brand_id']){
            $userId = ShopBrand::where("id",$row['brand_id'])->value('user_id');
            $shopIds = $userId ? Shop::where('brand_id',$userId)->column("id") : [];
            if(!$shopIds){
                $this->error("品牌门店错误");
            }
            //判断我是否可以核销
            $verifier = ShopVerifier::isVerifier($this->auth->mobile,$shopIds);
            if(!$verifier){
                $this->error("非品牌分店核销，不可核销");
            }
            //分店是否上架了套餐
            if(!BranchPackage::normal()->where("shop_id",$verifier['shop_id'])->where("shop_package_id",$row['package_id'])->count()){
                $this->error("分店未上架套餐，不可核销");
            }
        }else {
            //判断我是否可以核销
            $verifier = ShopVerifier::isVerifier($this->auth->mobile,[$row['shop_id']]);
            if(!$verifier){
                $this->error("不是门店核销员，不可核销");
            }
        }

        $row->relationQuery(['shop','package_service']);
        #核销记录
        $model = new OrderQrcode();
        $verify_ists = $model->where($model->getTable().".user_package_id",$row->id)->order("id",'desc')->select();
        foreach ($verify_ists as $verify_ist){
            $verify_ist->relationQuery(['ordering.order_item']);
            $verify_ist['user'] = \app\common\model\User::where("id",$verify_ist['verifier_id'])->field('id,nickname,avatar')->find();
        }
        unset($verify_ist);
        $row->verify_ist = $verify_ists;
        $this->success('', $row);
    }

    /**
     * @title 扫码核销
     * @description 扫码核销
     */
    public function verifier_package(){
        $token = $this->request->post('token');
        $id = $this->request->post('user_package_service_id');
        $row = UserPackageModel::where('token',$token)->find();
        if(!$row){
            $this->error("套餐未找到");
        }
        if($row->status !== "ing"){
            $this->error("用户套餐状态不可使用");
        }
        $userPackageService = UserPackageService::get(['user_package_id'=>$row->id,'id'=>$id]);
        if(!$userPackageService){
            $this->error("套餐服务未找到");
        }
        if($userPackageService->status == 'finished'){
            $this->error("套餐服务已用完");
        }
        //品牌门店判断核销权限
        if($row['brand_id']){
            $userId = ShopBrand::where("id",$row['brand_id'])->value('user_id');
            $shopIds = $userId ? Shop::where('brand_id',$userId)->column("id") : [];
            if(!$shopIds){
                $this->error("品牌门店错误");
            }
            //判断我是否可以核销
            $verifier = ShopVerifier::isVerifier($this->auth->mobile,$shopIds);
            if(!$verifier){
                $this->error("非品牌分店核销，不可核销");
            }
            //分店是否上架了套餐
            if(!BranchPackage::normal()->where("shop_id",$verifier['shop_id'])->where("shop_package_id",$row['package_id'])->count()){
                $this->error("分店未上架套餐，不可核销");
            }
            //分店未上架服务，
            if(!ShopBranchService::normal()->where("shop_id",$verifier['shop_id'])->where("shop_service_id",$userPackageService['shop_service_id'])->count()){
                $this->error("分店未上架服务，不可核销");
            }
        }else {
            //判断我是否可以核销
            $verifier = ShopVerifier::isVerifier($this->auth->mobile,[$row['shop_id']]);
            if(!$verifier){
                $this->error("不是门店核销员，不可核销");
            }
        }
        $verifierId = $this->auth->id;
        Db::startTrans();
        try {
            #1.添加个核销订单
           $order = \app\common\model\xiluxc\order\Order::createOrderByPackage($row,$userPackageService,$verifier);
            #2.支付成功
            $order->pay_type = '3';//套餐支付
            $order->order_trade_no = 'V' . date('YmdHis') . mt_rand(10, 9999);
            $order->allowField(['pay_type','order_trade_no'])->save();
            \app\common\model\xiluxc\order\Order::payNotify($order->order_trade_no,'usepackage');
            #3.添加个核销码，且已核销
            $qrcode = OrderQrcode::create([
                'order_id'  =>  $order->id,
                'qrcode'    =>  $row->qrcode,
                'code'      =>  $row->code,
                'verifier_status'=> 1,
                'verifier_id'=> $verifierId,
                'verifytime'=> time(),
                'user_package_id'=> $row->id,
                'token'     =>  $token
            ]);
            #4.订单状态已核销
            $order->verify_status = '1';
            $order->verifytime = time();
            $order->allowField(['verify_status','verifytime'])->save();
            #5扣除核销次数，增加已使用次数
            if($userPackageService->stock - 1 <= 0){
                $userPackageService->status = 'finished';
            }
            $userPackageService->stock = Db::raw("stock-1");
            $userPackageService->use_count = Db::raw("use_count+1");
            $userPackageService->save();
            #6.判断套餐是否已无可用次数，有，则变成已完成
            $total_count = $count = 0;
            foreach ($row->package_service as $v){
                $total_count++;
                if($v['status'] == 'finished'){
                    $count++;
                }
            }
            if($total_count == $count){
                $row->allowField(['status'=>'finished']);
            }
            #给佣金
            $params = [
                'type'  =>  'service_order',
                'user_package' =>  $row,
                'order' =>  $order
            ];
            Hook::listen("xiluxc_service_calculate",$params);
            #核销成功消息
            $ret = [
                'order' =>  $order,
                'user_package' =>  $row,
                'qrcode'=>  $qrcode
            ];
            Hook::listen("xiluxc_package_verifier_message",$ret);
        }catch (Exception|PDOException $e){
            Db::rollback();
            $this->error($e->getMessage());
        }
        Db::commit();
        $this->success('核销成功');
    }

}
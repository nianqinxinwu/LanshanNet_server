<?php

namespace app\common\model\xiluxc\user;


use app\common\model\xiluxc\brand\Shop;
use app\common\model\xiluxc\brand\ShopBrand;
use app\common\model\xiluxc\current\Config;
use app\common\model\xiluxc\order\Order;
use think\Exception;
use think\Model;

class UserPackage extends Model{

    protected $name = 'xiluxc_user_package';

    protected $append = [

    ];

    protected $autoWriteTimestamp = "int";

    protected $createTime = "createtime";
    protected $updateTime = "updatetime";
    protected $deleteTime = false;


    public function getStatusList(){
        return ['ing' => __('进行中'), 'finish' => __('已完成'), 'apply_refund' => __('退款中'),'refund'=>__('已退款')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function ordering(){
        return $this->belongsTo(Order::class,'order_id','id',[],'left')->setEagerlyType(0);
    }

    public function shop(){
        return $this->belongsTo(Shop::class,'shop_id','id',[],'left')->setEagerlyType(0);
    }

    public function brand(){
        return $this->belongsTo(ShopBrand::class,'brand_id','id',[],'left');
    }

    public function packageService(){
        return $this->hasMany(UserPackageService::class,'user_package_id');
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','id',[],'left')->setEagerlyType(0);
    }

    /**
     * 更新或添加门店会员
     * @param $order
     * @return array|bool|false|\PDOStatement|string|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function addPackage($order){
        #判断是否已经存在
        if(self::where("user_id",$order->user_id)->where("order_id",$order->id)->count()){
            return;
        }
        $pcakageServices = json_decode($order->order_item->ext,true);
        #1.添加套餐关联数据
        $user_package = [
            'user_id'   =>  $order->user_id,
            'shop_id'   =>  $order->shop_id,
            'brand_id'   =>  $order->brand_id,
            'package_id'=>  $order->order_item->data_id,
            'package_name'=>  $order->order_item->title,
            'package_image'=>  $order->order_item->image,
            'order_id'  =>  $order->id,
            'order_amount'   =>  bcsub($order->order_amount,$order->coupon_discount_fee,2),
            'pay_fee'   =>  $order->pay_fee,
        ];
        $result = self::create($user_package);
        #2.套餐关联服务
        $data = [];
        foreach ($pcakageServices as $pcakageService){
            $data[] = [
                'user_package_id'   =>  $result->id,
                'package_id'        =>  $pcakageService['package_id'],
                'package_service_id'=>  $pcakageService['id'],
                'shop_service_id'   =>  $pcakageService['shop_service_id'],
                'service_price_id'  =>  $pcakageService['service_price']['id'],
                'service_image'     =>  $pcakageService['shop_service']['image_text'],
                'service_name'      =>  $pcakageService['service']['name'],
                'service_price_name'=>  $pcakageService['service_price']['title'],
                'salesprice'        =>  $pcakageService['service_price']['salesprice'],
                'vip_price'         =>  $pcakageService['service_price']['vip_price'],
                'total_count'       =>  $pcakageService['use_count'],
                'stock'             =>  $pcakageService['use_count'],
            ];
        }
        (new UserPackageService)->saveAll($data);
        return true;
    }

    /**
     * 判断是否门店会员
     * @param $shopId
     * @param $userId
     */
    public static function isShopVip($shopId,$userId){
        return self::where("shop_id",$shopId)->where('user_id',$userId)->where("expire_in",'>=',Config::getExpiretime())->count();
    }


}
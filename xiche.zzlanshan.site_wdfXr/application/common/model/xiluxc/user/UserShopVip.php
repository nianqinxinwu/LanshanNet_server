<?php

namespace app\common\model\xiluxc\user;


use app\common\model\xiluxc\brand\Shop;
use app\common\model\xiluxc\brand\ShopBrand;
use app\common\model\xiluxc\current\Config;
use think\Exception;
use think\Model;

class UserShopVip extends Model{

    protected $name = 'xiluxc_user_shop_vip';

    protected $append = [

    ];

    protected $autoWriteTimestamp = "int";

    protected $createTime = "createtime";
    protected $updateTime = "updatetime";
    protected $deleteTime = false;

    public function getExpireInTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['expire_in']) ? $data['expire_in'] : '');
        return datetime($value,'Y.m.d');
    }

    public function shop(){
        return $this->belongsTo(Shop::class,'shop_id','id',[],'left')->setEagerlyType(0);
    }
    public function brand(){
        return $this->belongsTo(ShopBrand::class,'brand_id','id',[],'left');
    }

    /**
     * 更新或添加门店会员
     * @param $vipOrder
     * @return array|bool|false|\PDOStatement|string|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function addVip($vipOrder){
        if(!$vipOrder){
            throw new  Exception("参数错误");
        }
        #更新会员卡时间
        if($vipOrder->brand_id){
            $userShopVip = UserShopVip::where('user_id',$vipOrder->user_id)->where("brand_id",$vipOrder->brand_id)->find();
        }else{
            $userShopVip = UserShopVip::where('user_id',$vipOrder->user_id)->where("shop_id",$vipOrder->shop_id)->find();
        }

        if(!$userShopVip || ($userShopVip['expire_in'] < Config::getTodayTime())){
            $expire_in = strtotime(date('Y-m-d',strtotime($vipOrder->vip_days.' day')));
        }else{
            $expire_in = strtotime("+".$vipOrder->vip_days." day ".date('Y-m-d',$userShopVip['expire_in']));
        }
        $user_vip_ret = [
            'user_id'   =>  $vipOrder->user_id,
            'shop_id'   =>  $vipOrder->shop_id,
            'brand_id'   =>  $vipOrder->brand_id,
            'shop_vip_id'=> $vipOrder->vip_id,
            'expire_in' =>  $expire_in
        ];
        if(!$userShopVip){
            $arr = explode(' ',microtime());
            $num = $arr[0]*10000000000 + $arr[1] - $arr[0]*1000000;
            $num = str_pad($num,14,mt_rand(0,9));
            $num = str_pad($num,15,mt_rand(0,9));
            $user_vip_ret['vip_no'] = $num;
            UserShopVip::create($user_vip_ret);
        }else{
            $userShopVip->save($user_vip_ret);
        }
        return true;
    }

    /**
     * 判断是否门店会员
     * @param $shopId
     * @param $userId
     */
    public static function isShopVip($shopId,$userId){
        $shop = Shop::get($shopId);
        $where = Shop::getBrandShopWhere($shop);
        return self::where($where)->where('user_id',$userId)->where("expire_in",'>=',Config::getExpiretime())->count();
    }

    /**
     * 门店详情有效期
     */
    public static function shopDetailVip($userId,$shop){
        if(!$shop instanceof Shop){
            $shop = Shop::get($shop);
        }
        $where = Shop::getBrandShopWhere($shop);
        $query = self::where("user_id",$userId);
        $query->where($where);
        $shopVip = $query->find();
        if(!$shopVip){
            $status = 0; //未办理
        }
        else if($shopVip->expire_in >= strtotime(date("Y-m-d"))){
            $status = 1; //使用中
        }
        else {
            $status = 2; //已过期
        }
        return ['status'=>$status,'expire_in_text'=>$shopVip?$shopVip->expire_in_text:''];
    }


}
<?php

namespace app\common\model\xiluxc\user;


use app\common\model\xiluxc\brand\Shop;
use app\common\model\xiluxc\brand\ShopBrand;
use think\Exception;
use think\Model;

class UserShopAccount extends Model{

    protected $name = 'xiluxc_user_shop_account';

    protected $append = [

    ];

    public function shop(){
        return $this->belongsTo(Shop::class,'shop_id','id',[],'inner')->setEagerlyType(0);
    }

    public function brand(){
        return $this->belongsTo(ShopBrand::class,'brand_id','id',[],'left');
    }

    /**
     * 用户门店账号
     * @param $user_id
     * @param int $shopId
     * @return array|bool|false|\PDOStatement|string|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function addAccount($user_id,$shopId,$isCreate=true){
        if(!$user_id || !$shopId){
            throw new  Exception("参数错误");
        }
        $shop = Shop::get($shopId);
        if($shop['type'] == 1){
            $brand = null;
            $where['shop_id'] = $shop->id;
        }else{
            $brand = ShopBrand::get(['user_id'=>$shop->brand_id]);
            $where['brand_id'] = $brand->id;
        }
        $account = static::where('user_id',$user_id)->where($where)->find();
        if($isCreate && !$account){
            $account = static::create([
                'user_id'  =>   $user_id,
                'shop_id'  =>   $shop->id,
                'brand_id' =>   $brand ? $brand->id : 0
            ]);
            $account = self::where('id',$account->id)->find();

        }
        return $account;
    }


}
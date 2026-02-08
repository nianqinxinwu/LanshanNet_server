<?php

namespace app\common\model\xiluxc\brand;

use think\Model;

class ShopAccount extends Model{

    protected $name = 'xiluxc_shop_account';

    protected $append = [

    ];

    /**
     * 门店账户
     * @param $shop
     * @return array|bool|false|\PDOStatement|string|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function addAccount($shop,$params=[]){
        if(!$shop instanceof Shop){
            $shop = Shop::get($shop);
        }
        if(!$shop){
            return false;
        }
        $account = static::where('shop_id',$shop->id)->find();
        if(!$account){
            $account = static::create([
                'shop_id'           =>  $shop->id,
                'vip_rate'          =>  $params['vip_rate']??0,
                'rate'              =>  $params['rate']??0,
            ]);
            $account = self::where('id',$account->id)->find();
        }
        return $account;
    }


}
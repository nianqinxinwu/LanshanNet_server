<?php

namespace app\common\model\xiluxc\brand;

use think\Model;

class ShopDevice extends Model{

    protected $name = 'xiluxc_shop_device';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;


    // public static function getDevice($shop_id){
    //     return self::normal()
    //         ->field("id,shop_id,name,status,createtime,updatetime")
    //         ->where('shop_id',$shop_id)
    //         ->select();
    // }


}
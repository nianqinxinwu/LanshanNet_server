<?php

namespace app\common\model\xiluxc\order;

use think\Model;


class OrderCoupon extends Model
{

    

    

    // 表名
    protected $name = 'xiluxc_order_coupon';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

}

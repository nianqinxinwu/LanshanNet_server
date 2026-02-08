<?php

namespace app\common\model\xiluxc;

use think\Model;


class Divide extends Model
{

    const TYPE_VIP = 1;
    const TYPE_SERVICE = 2;
    const TYPE_PACKAGE = 3;

    const TYPE_ARRAY = [
        'vip'      =>   self::TYPE_VIP,
        'service'  =>   self::TYPE_SERVICE,
        'package'  =>   self::TYPE_PACKAGE,
    ];


    // 表名
    protected $name = 'xiluxc_divide';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

}

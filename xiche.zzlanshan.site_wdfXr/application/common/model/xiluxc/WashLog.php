<?php

namespace app\common\model\xiluxc;

use think\Model;

/**
 * 会员余额日志模型
 */
class WashLog extends Model
{

    // 表名
    protected $name = 'xiluxc_shop_washlog';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    // 追加属性
    protected $append = [
    ];
}

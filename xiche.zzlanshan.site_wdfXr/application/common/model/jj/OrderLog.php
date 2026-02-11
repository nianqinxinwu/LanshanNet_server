<?php

namespace app\common\model\jj;

use think\Model;

/**
 * 订单状态日志模型
 */
class OrderLog extends Model
{
    protected $name = 'jj_order_log';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = false;

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id', [], 'left')->setEagerlyType(0);
    }
}

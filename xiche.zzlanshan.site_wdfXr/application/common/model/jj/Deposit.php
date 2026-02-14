<?php

namespace app\common\model\jj;

use think\Model;

/**
 * 保证金记录模型
 */
class Deposit extends Model
{
    protected $name = 'jj_deposit';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $append = [
        'pay_status_text',
    ];

    const PAY_STATUS_PENDING      = 0;
    const PAY_STATUS_PAID         = 1;
    const PAY_STATUS_REFUNDED     = 2;
    const PAY_STATUS_COMPENSATED  = 3;

    const PAY_STATUS_MAP = [
        0 => '待支付',
        1 => '已支付',
        2 => '已退回',
        3 => '已赔付',
    ];

    public function getPayStatusTextAttr($value, $data)
    {
        $status = isset($data['pay_status']) ? $data['pay_status'] : 0;
        return self::PAY_STATUS_MAP[$status] ?? '未知';
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id', [], 'left')->setEagerlyType(0);
    }
}

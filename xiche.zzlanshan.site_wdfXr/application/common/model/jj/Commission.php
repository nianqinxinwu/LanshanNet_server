<?php

namespace app\common\model\jj;

use think\Model;

/**
 * 佣金记录模型
 */
class Commission extends Model
{
    protected $name = 'jj_commission';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $append = [
        'status_text',
    ];

    const STATUS_PENDING = 0;
    const STATUS_SETTLED = 1;

    const STATUS_MAP = [
        0 => '待结算',
        1 => '已结算',
    ];

    public function getStatusTextAttr($value, $data)
    {
        $status = isset($data['status']) ? $data['status'] : 0;
        return self::STATUS_MAP[$status] ?? '未知';
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id', [], 'left')->setEagerlyType(0);
    }

    public function agent()
    {
        return $this->belongsTo('app\common\model\User', 'agent_id', 'id', [], 'left')->setEagerlyType(0);
    }
}

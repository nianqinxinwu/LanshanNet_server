<?php

namespace app\common\model\jj;

use think\Model;

/**
 * 平台红包记录模型
 */
class RedPacket extends Model
{
    protected $name = 'jj_red_packet';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $append = [
        'status_text',
    ];

    const STATUS_PENDING  = 0;
    const STATUS_RECEIVED = 1;

    const STATUS_MAP = [
        0 => '待领取',
        1 => '已领取',
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
}

<?php

namespace app\common\model\jj;

use think\Model;

/**
 * 合同记录模型
 */
class Contract extends Model
{
    protected $name = 'jj_contract';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $append = [
        'status_text',
    ];

    const STATUS_PENDING  = 0;
    const STATUS_UPLOADED = 1;
    const STATUS_EXPIRED  = 2;

    const STATUS_MAP = [
        0 => '待上传',
        1 => '已上传',
        2 => '已过期',
    ];

    public function getStatusTextAttr($value, $data)
    {
        $status = isset($data['status']) ? $data['status'] : 0;
        return self::STATUS_MAP[$status] ?? '未知';
    }

    public function getFileUrlAttr($value)
    {
        return $value ? cdnurl($value, true) : '';
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id', [], 'left')->setEagerlyType(0);
    }
}

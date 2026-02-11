<?php

namespace app\common\model\jj;

use think\Model;

/**
 * 物流跟踪模型
 */
class Logistics extends Model
{
    protected $name = 'jj_logistics';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $append = [
        'status_text',
    ];

    const TYPE_PLATFORM = 1;
    const TYPE_SELF     = 2;

    // 平台物流状态
    const STATUS_WAIT_SHIP = 0;
    const STATUS_SHIPPED   = 1;
    const STATUS_TRANSIT   = 2;
    const STATUS_SIGNED    = 3;

    const STATUS_MAP = [
        0 => '待发货',
        1 => '已发货',
        2 => '运输中',
        3 => '已签收',
    ];

    const SELF_STATUS_MAP = [
        0 => '待提货',
        1 => '已提货',
    ];

    public function getStatusTextAttr($value, $data)
    {
        $status = isset($data['status']) ? $data['status'] : 0;
        $type = isset($data['logistics_type']) ? $data['logistics_type'] : self::TYPE_PLATFORM;
        if ($type == self::TYPE_SELF) {
            return self::SELF_STATUS_MAP[$status] ?? '未知';
        }
        return self::STATUS_MAP[$status] ?? '未知';
    }

    public function getTimelineJsonAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setTimelineJsonAttr($value)
    {
        return is_array($value) ? json_encode($value) : $value;
    }

    public function getChecklistFilesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setChecklistFilesAttr($value)
    {
        return is_array($value) ? json_encode($value) : $value;
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id', [], 'left')->setEagerlyType(0);
    }
}

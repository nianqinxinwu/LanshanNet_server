<?php

namespace app\common\model\jj;

use think\Model;

/**
 * 竞标工厂报价模型
 */
class BidQuote extends Model
{
    protected $name = 'jj_bid_quote';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $append = [
        'status_text',
    ];

    const STATUS_PENDING = 0;
    const STATUS_QUOTED  = 1;
    const STATUS_EXPIRED = 2;

    const STATUS_MAP = [
        0 => '待报价',
        1 => '已报价',
        2 => '已过期',
    ];

    public function getStatusTextAttr($value, $data)
    {
        $status = isset($data['status']) ? $data['status'] : 0;
        return self::STATUS_MAP[$status] ?? '未知';
    }

    public function bid()
    {
        return $this->belongsTo(Bid::class, 'bid_id', 'id', [], 'left')->setEagerlyType(0);
    }

    public function factory()
    {
        return $this->belongsTo(Factory::class, 'factory_id', 'id', [], 'left')->setEagerlyType(0);
    }
}

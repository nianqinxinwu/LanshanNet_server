<?php

namespace app\common\model\jj;

use think\Model;

/**
 * 竞标记录模型
 */
class Bid extends Model
{
    protected $name = 'jj_bid';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $append = [
        'status_text',
    ];

    const STATUS_BIDDING   = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_EXPIRED   = 3;

    const STATUS_MAP = [
        1 => '竞标中',
        2 => '已完成',
        3 => '已过期',
    ];

    /**
     * 生成竞标编号
     */
    public static function generateBidSn()
    {
        return 'BID' . date('YmdHis') . mt_rand(1000, 9999);
    }

    public function getStatusTextAttr($value, $data)
    {
        $status = isset($data['status']) ? $data['status'] : 1;
        return self::STATUS_MAP[$status] ?? '未知';
    }

    public function agent()
    {
        return $this->belongsTo('app\common\model\User', 'agent_id', 'id', [], 'left')->setEagerlyType(0);
    }

    public function quotes()
    {
        return $this->hasMany(BidQuote::class, 'bid_id', 'id');
    }
}

<?php

namespace app\common\model\jj;

use think\Model;
use traits\model\SoftDelete;

/**
 * 居间人订单模型
 */
class Order extends Model
{
    use SoftDelete;

    protected $name = 'jj_order';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    protected $append = [
        'status_text',
    ];

    // 订单状态机
    const STATUS_PENDING    = 0; // 待确认
    const STATUS_DEPOSIT    = 1; // 待缴保证金
    const STATUS_DEPOSITED  = 2; // 已缴保证金
    const STATUS_CONTRACT   = 3; // 待上传合同
    const STATUS_EXECUTING  = 4; // 履约执行中
    const STATUS_SETTLING   = 5; // 待结算
    const STATUS_SETTLED    = 6; // 已结算
    const STATUS_CANCELLED  = 7; // 已取消
    const STATUS_OVERDUE    = 8; // 已逾期

    const STATUS_MAP = [
        0 => '待确认',
        1 => '待缴保证金',
        2 => '已缴保证金',
        3 => '待上传合同',
        4 => '履约执行中',
        5 => '待结算',
        6 => '已结算',
        7 => '已取消',
        8 => '已逾期',
    ];

    public function getStatusTextAttr($value, $data)
    {
        $status = isset($data['status']) ? $data['status'] : 0;
        return self::STATUS_MAP[$status] ?? '未知';
    }

    public function getCoverImageAttr($value)
    {
        return $value ? cdnurl($value, true) : '';
    }

    /**
     * 生成订单编号
     */
    public static function generateOrderSn()
    {
        return 'JJ' . date('YmdHis') . mt_rand(1000, 9999);
    }

    // 关联关系
    public function agent()
    {
        return $this->belongsTo('app\common\model\User', 'agent_id', 'id', [], 'left')->setEagerlyType(0);
    }

    public function factory()
    {
        return $this->belongsTo(Factory::class, 'factory_id', 'id', [], 'left')->setEagerlyType(0);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id', [], 'left')->setEagerlyType(0);
    }

    public function deposit()
    {
        return $this->hasOne(Deposit::class, 'order_id', 'id');
    }

    public function contract()
    {
        return $this->hasOne(Contract::class, 'order_id', 'id');
    }

    public function logistics()
    {
        return $this->hasOne(Logistics::class, 'order_id', 'id');
    }

    public function orderLogs()
    {
        return $this->hasMany(OrderLog::class, 'order_id', 'id');
    }

    public function bid()
    {
        return $this->belongsTo(Bid::class, 'bid_id', 'id', [], 'left')->setEagerlyType(0);
    }
}

<?php

namespace app\common\model\jj;

use think\Model;

/**
 * 保证金批次支付模型
 */
class DepositBatch extends Model
{
    protected $name = 'jj_deposit_batch';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    const PAY_STATUS_PENDING = 0;
    const PAY_STATUS_PAID    = 1;

    /**
     * 生成批次号
     */
    public static function generateBatchNo()
    {
        return 'DB' . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * 获取关联订单ID数组
     */
    public function getOrderIdsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 设置关联订单ID数组
     */
    public function setOrderIdsAttr($value)
    {
        return is_array($value) ? json_encode($value) : $value;
    }
}

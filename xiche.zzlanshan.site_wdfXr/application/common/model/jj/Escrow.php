<?php

namespace app\common\model\jj;

use think\Model;

/**
 * 佣金托管记录模型（账户B）
 */
class Escrow extends Model
{
    protected $name = 'jj_escrow';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    const STATUS_HOLDING  = 0;  // 托管中
    const STATUS_SETTLED  = 1;  // 已结算
    const STATUS_REFUNDED = 2;  // 已退回

    const STATUS_MAP = [
        0 => '托管中',
        1 => '已结算',
        2 => '已退回',
    ];

    // 拆分比例配置
    const PLATFORM_FEE_RATE = 0;       // C: 不再从佣金扣平台费（改由service_fee收取）
    const TAX_RATE          = 0;       // D: 暂不扣个税（居间人有企业/个人两种身份，税务另行处理）
    const LOGISTICS_RATE    = 0;       // E: 物流返佣 暂定0

    protected $append = [
        'status_text',
    ];

    public function getStatusTextAttr($value, $data)
    {
        $status = isset($data['status']) ? $data['status'] : 0;
        return self::STATUS_MAP[$status] ?? '未知';
    }

    /**
     * 计算佣金拆分
     * @param float $totalAmount 托管总金额
     * @return array
     */
    public static function calculateSplit($totalAmount)
    {
        $platformFee      = bcmul($totalAmount, self::PLATFORM_FEE_RATE, 2);
        $taxAmount        = bcmul($totalAmount, self::TAX_RATE, 2);
        $logisticsRebate  = bcmul($totalAmount, self::LOGISTICS_RATE, 2);

        // 居间人结算 = 总金额 - 平台服务费 - 税务 - 物流返佣
        $deductions = bcadd(bcadd($platformFee, $taxAmount, 2), $logisticsRebate, 2);
        $agentSettlement = bcsub($totalAmount, $deductions, 2);

        return [
            'platform_fee'     => $platformFee,
            'tax_amount'       => $taxAmount,
            'logistics_rebate' => $logisticsRebate,
            'agent_settlement' => $agentSettlement,
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id', [], 'left')->setEagerlyType(0);
    }
}

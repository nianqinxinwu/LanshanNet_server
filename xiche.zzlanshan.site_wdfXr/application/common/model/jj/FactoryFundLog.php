<?php

namespace app\common\model\jj;

use think\Model;

/**
 * 工厂资金流水模型
 */
class FactoryFundLog extends Model
{
    protected $name = 'jj_factory_fund_log';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = false;

    const TYPE_RECHARGE        = 1;  // 充值
    const TYPE_FREEZE          = 2;  // 佣金冻结
    const TYPE_SETTLE          = 3;  // 佣金结算扣除
    const TYPE_UNFREEZE        = 4;  // 佣金解冻退回
    const TYPE_WITHDRAW        = 5;  // 提现
    const TYPE_WITHDRAW_REFUND = 6;  // 提现退回

    const TYPE_MAP = [
        1 => '充值',
        2 => '佣金冻结',
        3 => '佣金结算',
        4 => '佣金解冻',
        5 => '提现',
        6 => '提现退回',
    ];

    protected $append = [
        'type_text',
    ];

    public function getTypeTextAttr($value, $data)
    {
        $type = isset($data['type']) ? $data['type'] : 0;
        return self::TYPE_MAP[$type] ?? '未知';
    }
}

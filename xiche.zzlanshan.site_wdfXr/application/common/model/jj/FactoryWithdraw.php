<?php

namespace app\common\model\jj;

use think\Db;
use think\Model;

/**
 * 工厂提现记录模型
 */
class FactoryWithdraw extends Model
{
    protected $name = 'jj_factory_withdraw';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    const STATE_PENDING    = 1; // 审核中
    const STATE_PROCESSING = 2; // 处理中
    const STATE_DONE       = 3; // 已处理
    const STATE_REFUSED    = 4; // 已拒绝

    const STATE_MAP = [
        1 => '审核中',
        2 => '处理中',
        3 => '已处理',
        4 => '已拒绝',
    ];

    protected $append = [
        'state_text',
        'checktime_text',
        'createtime_text',
    ];

    public function getStateTextAttr($value, $data)
    {
        $state = isset($data['state']) ? $data['state'] : 0;
        return self::STATE_MAP[$state] ?? '未知';
    }

    public function getChecktimeTextAttr($value, $data)
    {
        $value = $value ?: (isset($data['checktime']) ? $data['checktime'] : '');
        return is_numeric($value) && $value ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getCreatetimeTextAttr($value, $data)
    {
        $value = $value ?: (isset($data['createtime']) ? $data['createtime'] : '');
        return is_numeric($value) && $value ? date("Y-m-d H:i:s", $value) : $value;
    }

    /**
     * 生成提现单号
     */
    public static function generateOrderNo()
    {
        return 'FW' . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * 申请提现
     * @param array $factory     工厂信息
     * @param FactoryAccount $account 工厂资金账户
     * @param FactoryBankAccount $bankAccount 银行账户
     * @param float $amount      提现金额
     * @return FactoryWithdraw
     * @throws \Exception
     */
    public static function applyWithdraw($factory, $account, $bankAccount, $amount)
    {
        Db::startTrans();
        try {
            // 行锁检查余额
            $account = FactoryAccount::lock(true)->find($account->id);
            if (bccomp($account->money, $amount, 2) < 0) {
                throw new \Exception('可用余额不足');
            }

            // 扣减可用余额
            $before = $account->money;
            $after = bcsub($account->money, $amount, 2);
            $account->save(['money' => $after]);

            // 手续费率暂设0
            $rate = 0;
            $rateMoney = 0;
            $realMoney = bcsub($amount, $rateMoney, 2);

            // 创建提现记录（快照银行信息）
            $withdraw = self::create([
                'order_no'        => self::generateOrderNo(),
                'factory_id'      => $factory['id'],
                'user_id'         => $account->user_id,
                'bank_account_id' => $bankAccount->id,
                'account_name'    => $bankAccount->account_name,
                'bank_name'       => $bankAccount->bank_name,
                'bank_branch'     => $bankAccount->bank_branch,
                'bank_no'         => $bankAccount->bank_no,
                'money'           => $amount,
                'real_money'      => $realMoney,
                'rate'            => $rate,
                'rate_money'      => $rateMoney,
                'state'           => self::STATE_PENDING,
            ]);

            // 写入资金流水
            FactoryFundLog::create([
                'factory_id'   => $factory['id'],
                'user_id'      => $account->user_id,
                'type'         => FactoryFundLog::TYPE_WITHDRAW,
                'amount'       => $amount,
                'before_money' => $before,
                'after_money'  => $after,
                'order_id'     => $withdraw->id,
                'memo'         => '提现申请¥' . $amount,
            ]);

            Db::commit();
            return $withdraw;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 拒绝提现后退回金额（调用方需自行管理事务）
     * @param int $withdrawId
     * @return bool
     * @throws \Exception
     */
    public static function refundOnRefuse($withdrawId)
    {
        Db::startTrans();
        try {
            $withdraw = self::lock(true)->find($withdrawId);
            if (!$withdraw || $withdraw->state != self::STATE_REFUSED) {
                throw new \Exception('提现记录状态异常');
            }

            // 幂等检查：是否已退款过
            $existingRefund = FactoryFundLog::where('order_id', $withdrawId)
                ->where('type', FactoryFundLog::TYPE_WITHDRAW_REFUND)
                ->find();
            if ($existingRefund) {
                Db::commit();
                return true; // 已退款，幂等返回
            }

            $account = FactoryAccount::lock(true)->where('factory_id', $withdraw->factory_id)->find();
            if (!$account) {
                throw new \Exception('工厂账户不存在');
            }

            $before = $account->money;
            $after = bcadd($account->money, $withdraw->money, 2);
            $account->save(['money' => $after]);

            FactoryFundLog::create([
                'factory_id'   => $withdraw->factory_id,
                'user_id'      => $withdraw->user_id,
                'type'         => FactoryFundLog::TYPE_WITHDRAW_REFUND,
                'amount'       => $withdraw->money,
                'before_money' => $before,
                'after_money'  => $after,
                'order_id'     => $withdraw->id,
                'memo'         => '提现退回¥' . $withdraw->money,
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }
}

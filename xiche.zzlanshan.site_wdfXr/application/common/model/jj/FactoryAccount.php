<?php

namespace app\common\model\jj;

use think\Db;
use think\Model;

/**
 * 工厂资金账户模型
 */
class FactoryAccount extends Model
{
    protected $name = 'jj_factory_account';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 获取或创建工厂账户
     */
    public static function getOrCreate($factoryId, $userId)
    {
        $account = self::where('factory_id', $factoryId)->find();
        if (!$account) {
            $account = self::create([
                'factory_id' => $factoryId,
                'user_id'    => $userId,
                'money'      => 0,
                'frozen_money'    => 0,
                'total_recharge'  => 0,
                'total_settled'   => 0,
                'total_withdraw'  => 0,
            ]);
        }
        return $account;
    }

    /**
     * 充值到账
     * @param float  $amount    充值金额
     * @param int    $orderId   关联订单ID（充值订单）
     * @param string $memo      备注
     * @return bool
     */
    public function recharge($amount, $orderId = 0, $memo = '充值到账')
    {
        Db::startTrans();
        try {
            $account = self::lock(true)->find($this->id);
            if (!$account || $amount <= 0) {
                Db::rollback();
                return false;
            }
            $before = $account->money;
            $after = bcadd($account->money, $amount, 2);
            $totalRecharge = bcadd($account->total_recharge, $amount, 2);

            $account->save([
                'money'          => $after,
                'total_recharge' => $totalRecharge,
            ]);

            FactoryFundLog::create([
                'factory_id'   => $account->factory_id,
                'user_id'      => $account->user_id,
                'type'         => FactoryFundLog::TYPE_RECHARGE,
                'amount'       => $amount,
                'before_money' => $before,
                'after_money'  => $after,
                'order_id'     => $orderId,
                'memo'         => $memo,
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    /**
     * 佣金冻结（从可用余额扣除，转入冻结金额）
     * @param float  $amount   冻结金额
     * @param int    $orderId  关联订单ID
     * @param string $memo     备注
     * @return bool
     */
    public function freeze($amount, $orderId = 0, $memo = '佣金冻结')
    {
        Db::startTrans();
        try {
            $account = self::lock(true)->find($this->id);
            if (!$account || $amount <= 0) {
                Db::rollback();
                return false;
            }

            if (bccomp($account->money, $amount, 2) < 0) {
                Db::rollback();
                return false;
            }

            $before = $account->money;
            $after = bcsub($account->money, $amount, 2);
            $frozenAfter = bcadd($account->frozen_money, $amount, 2);

            $account->save([
                'money'        => $after,
                'frozen_money' => $frozenAfter,
            ]);

            FactoryFundLog::create([
                'factory_id'   => $account->factory_id,
                'user_id'      => $account->user_id,
                'type'         => FactoryFundLog::TYPE_FREEZE,
                'amount'       => $amount,
                'before_money' => $before,
                'after_money'  => $after,
                'order_id'     => $orderId,
                'memo'         => $memo,
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    /**
     * 佣金结算扣除（从冻结金额中扣除）
     * @param float  $amount   结算金额
     * @param int    $orderId  关联订单ID
     * @param string $memo     备注
     * @return bool
     */
    public function settle($amount, $orderId = 0, $memo = '佣金结算扣除')
    {
        Db::startTrans();
        try {
            $account = self::lock(true)->find($this->id);
            if (!$account || $amount <= 0) {
                Db::rollback();
                return false;
            }

            if (bccomp($account->frozen_money, $amount, 2) < 0) {
                Db::rollback();
                return false;
            }

            $frozenAfter = bcsub($account->frozen_money, $amount, 2);
            $totalSettled = bcadd($account->total_settled, $amount, 2);

            $account->save([
                'frozen_money'  => $frozenAfter,
                'total_settled' => $totalSettled,
            ]);

            FactoryFundLog::create([
                'factory_id'   => $account->factory_id,
                'user_id'      => $account->user_id,
                'type'         => FactoryFundLog::TYPE_SETTLE,
                'amount'       => $amount,
                'before_money' => $account->money,
                'after_money'  => $account->money,
                'order_id'     => $orderId,
                'memo'         => $memo,
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    /**
     * 佣金解冻退回（从冻结金额退回到可用余额）
     * @param float  $amount   解冻金额
     * @param int    $orderId  关联订单ID
     * @param string $memo     备注
     * @return bool
     */
    public function unfreeze($amount, $orderId = 0, $memo = '佣金解冻退回')
    {
        Db::startTrans();
        try {
            $account = self::lock(true)->find($this->id);
            if (!$account || $amount <= 0) {
                Db::rollback();
                return false;
            }

            if (bccomp($account->frozen_money, $amount, 2) < 0) {
                Db::rollback();
                return false;
            }

            $before = $account->money;
            $after = bcadd($account->money, $amount, 2);
            $frozenAfter = bcsub($account->frozen_money, $amount, 2);

            $account->save([
                'money'        => $after,
                'frozen_money' => $frozenAfter,
            ]);

            FactoryFundLog::create([
                'factory_id'   => $account->factory_id,
                'user_id'      => $account->user_id,
                'type'         => FactoryFundLog::TYPE_UNFREEZE,
                'amount'       => $amount,
                'before_money' => $before,
                'after_money'  => $after,
                'order_id'     => $orderId,
                'memo'         => $memo,
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }
}

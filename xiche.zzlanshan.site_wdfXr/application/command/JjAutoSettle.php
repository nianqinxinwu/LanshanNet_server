<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Log;
use app\common\model\jj\Order;
use app\common\model\jj\Contract;
use app\common\model\jj\Deposit;
use app\common\model\jj\Escrow;
use app\common\model\jj\FactoryAccount;
use app\common\model\jj\Factory;
use app\common\model\jj\OrderLog;
use app\common\model\User;

/**
 * 居间人订单自动违约结算 + 工厂超时封禁
 *
 * 用法：php think jj:auto_settle
 * 建议 crontab 每分钟执行：* * * * * php /path/to/think jj:auto_settle >> /tmp/jj_auto_settle.log 2>&1
 */
class JjAutoSettle extends Command
{
    protected function configure()
    {
        $this->setName('jj:auto_settle')
             ->setDescription('居间人订单自动违约结算 + 工厂超时封禁');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('[' . date('Y-m-d H:i:s') . '] 开始扫描...');

        $now = time();
        $settledCount = 0;
        $bannedCount = 0;

        // 1. 扫描合同上传超时 (status=3, contract_upload_deadline 已过期)
        $contractExpired = Order::where('status', Order::STATUS_CONTRACT)
            ->where('contract_upload_deadline', '>', 0)
            ->where('contract_upload_deadline', '<', $now)
            ->select();

        foreach ($contractExpired as $order) {
            try {
                $this->doFailSettle($order);
                $settledCount++;
                $output->writeln('  违约结算: 订单#' . $order['id'] . ' (' . $order['order_sn'] . ') 合同超时');
            } catch (\Exception $e) {
                $output->writeln('  失败: 订单#' . $order['id'] . ' ' . $e->getMessage());
                Log::error('jj:auto_settle contract expired: order#' . $order['id'] . ' ' . $e->getMessage());
            }
        }

        // 2. 扫描催款超时 (status=4, payment_urge_deadline 已过期)
        $paymentExpired = Order::where('status', Order::STATUS_EXECUTING)
            ->where('payment_urge_deadline', '>', 0)
            ->where('payment_urge_deadline', '<', $now)
            ->select();

        foreach ($paymentExpired as $order) {
            try {
                $this->doFailSettle($order);
                $settledCount++;
                $output->writeln('  违约结算: 订单#' . $order['id'] . ' (' . $order['order_sn'] . ') 催款超时');
            } catch (\Exception $e) {
                $output->writeln('  失败: 订单#' . $order['id'] . ' ' . $e->getMessage());
                Log::error('jj:auto_settle payment expired: order#' . $order['id'] . ' ' . $e->getMessage());
            }
        }

        // 3. 扫描工厂超时未锁定佣金 (status=2, 保证金已缴纳超过2小时)
        $lockExpired = Db::name('jj_order')
            ->alias('o')
            ->join('fa_jj_deposit d', 'd.order_id = o.id')
            ->where('o.status', Order::STATUS_DEPOSITED)
            ->where('d.pay_status', Deposit::PAY_STATUS_PAID)
            ->where('d.updatetime', '<', $now - 2 * 3600)
            ->where('o.deletetime', null)
            ->field('o.id, o.order_sn, o.factory_id, o.agent_id, o.status')
            ->select();

        foreach ($lockExpired as $orderRow) {
            try {
                $this->banFactoryAndCancelOrder($orderRow, $output);
                $bannedCount++;
            } catch (\Exception $e) {
                $output->writeln('  封禁失败: 订单#' . $orderRow['id'] . ' ' . $e->getMessage());
                Log::error('jj:auto_settle ban factory: order#' . $orderRow['id'] . ' ' . $e->getMessage());
            }
        }

        $output->writeln('[' . date('Y-m-d H:i:s') . '] 完成: 违约结算' . $settledCount . '笔, 封禁工厂' . $bannedCount . '个');
    }

    /**
     * 执行违约结算（复用 FcOrder::fail_settle 逻辑）
     */
    protected function doFailSettle($order)
    {
        $orderId = $order['id'];
        $factoryId = $order['factory_id'];

        Db::startTrans();
        try {
            // 加锁重新校验
            $order = Order::lock(true)->find($orderId);
            if (!in_array($order['status'], [Order::STATUS_CONTRACT, Order::STATUS_EXECUTING])) {
                Db::commit();
                return; // 已被其他进程处理
            }

            // 验证超时条件
            $allowFailSettle = false;
            if ($order['status'] == Order::STATUS_CONTRACT) {
                $contract = Contract::where('order_id', $orderId)->find();
                if ($contract && $contract['upload_deadline'] > 0 && time() > $contract['upload_deadline']) {
                    $allowFailSettle = true;
                }
            } elseif ($order['status'] == Order::STATUS_EXECUTING) {
                if ($order['payment_urge_deadline'] > 0 && time() > $order['payment_urge_deadline']) {
                    $allowFailSettle = true;
                }
            }
            if (!$allowFailSettle) {
                Db::commit();
                return;
            }

            // 获取保证金记录
            $deposit = Deposit::where('order_id', $orderId)
                ->where('pay_status', Deposit::PAY_STATUS_PAID)
                ->find();
            if (!$deposit) {
                throw new \Exception('未找到已缴纳的保证金记录');
            }

            // 获取托管记录
            $escrow = Escrow::where('order_id', $orderId)
                ->where('factory_id', $factoryId)
                ->where('status', Escrow::STATUS_HOLDING)
                ->lock(true)
                ->find();
            if (!$escrow) {
                throw new \Exception('未找到托管记录');
            }

            $depositAmount = floatval($deposit['amount']);
            $commissionAmount = floatval($escrow['total_amount']);
            $serviceFee = floatval($escrow['service_fee'] ?: 0);

            // 分账：平台收保证金50%，工厂退回佣金+剩余服务费，保证金全额赔付工厂
            $platformCut = bcmul($depositAmount, '0.50', 2);
            $factoryRefund = bcadd($commissionAmount, bcsub($serviceFee, $platformCut, 2), 2);

            // 1. 保证金标记为赔付
            $deposit->save(['pay_status' => Deposit::PAY_STATUS_COMPENSATED]);

            // 2. 工厂冻结金额处理
            $account = FactoryAccount::where('factory_id', $factoryId)->find();
            if ($account) {
                if (floatval($factoryRefund) > 0) {
                    $account->unfreeze($factoryRefund, $orderId, '订单' . $order['order_sn'] . '违约退回佣金+剩余服务费');
                }
                if (floatval($platformCut) > 0) {
                    $account->settle($platformCut, $orderId, '订单' . $order['order_sn'] . '违约-平台收取服务费');
                }
            }

            // 3. 保证金赔付工厂
            if ($account && floatval($depositAmount) > 0) {
                $account->recharge($depositAmount, $orderId, '订单' . $order['order_sn'] . '违约-保证金赔付');
            }

            // 4. 更新 Escrow
            $escrow->save([
                'platform_fee'     => $platformCut,
                'tax_amount'       => 0,
                'agent_settlement' => 0,
                'status'           => Escrow::STATUS_REFUNDED,
                'settle_time'      => time(),
            ]);

            // 5. 订单状态改为已逾期
            $originalStatus = $order['status'];
            $order->save(['status' => Order::STATUS_OVERDUE]);

            OrderLog::create([
                'order_id'      => $orderId,
                'from_status'   => $originalStatus,
                'to_status'     => Order::STATUS_OVERDUE,
                'description'   => '[系统自动]违约结算：保证金' . $depositAmount . '元赔付工厂，平台收取' . $platformCut . '元，退还工厂' . $factoryRefund . '元',
                'operator_type' => 'system',
                'operator_id'   => 0,
            ]);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 封禁超时未锁定佣金的工厂，并取消订单、退还保证金
     */
    protected function banFactoryAndCancelOrder($orderRow, $output)
    {
        $orderId = $orderRow['id'];
        $factoryId = $orderRow['factory_id'];

        Db::startTrans();
        try {
            // 加锁重新校验订单状态
            $order = Order::lock(true)->find($orderId);
            if (!$order || $order['status'] != Order::STATUS_DEPOSITED) {
                Db::commit();
                return; // 已被其他进程处理
            }

            // 再次确认保证金已超时
            $deposit = Deposit::where('order_id', $orderId)
                ->where('pay_status', Deposit::PAY_STATUS_PAID)
                ->find();
            if (!$deposit || (time() - $deposit['updatetime']) < 2 * 3600) {
                Db::commit();
                return;
            }

            // 1. 封禁工厂
            $factory = Factory::lock(true)->find($factoryId);
            if ($factory && $factory['status'] != Factory::STATUS_FROZEN) {
                $factory->save([
                    'status' => Factory::STATUS_FROZEN,
                ]);
                $output->writeln('  封禁工厂#' . $factoryId);
            }

            // 2. 取消订单
            $order->save(['status' => Order::STATUS_CANCELLED]);

            // 3. 退还保证金给居间人
            $depositAmount = floatval($deposit['amount']);
            $deposit->save(['pay_status' => Deposit::PAY_STATUS_REFUNDED]);
            if ($depositAmount > 0) {
                User::money($depositAmount, $order['agent_id'], '订单' . $order['order_sn'] . '工厂超时未锁定佣金，保证金退还');
            }

            OrderLog::create([
                'order_id'      => $orderId,
                'from_status'   => Order::STATUS_DEPOSITED,
                'to_status'     => Order::STATUS_CANCELLED,
                'description'   => '[系统自动]工厂超时未锁定佣金（>2小时），订单取消并退还保证金' . $depositAmount . '元，工厂已封禁',
                'operator_type' => 'system',
                'operator_id'   => 0,
            ]);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }
}

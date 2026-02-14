<?php

namespace app\api\controller\xiluxc;

use addons\xiluxc\library\Wechat;
use addons\xiluxc\library\wechat\Payment;
use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\Factory;
use app\common\model\jj\FactoryAccount;
use app\common\model\jj\FactoryBankAccount;
use app\common\model\jj\FactoryFundLog;
use app\common\model\jj\FactoryRechargeOrder;
use app\common\model\jj\FactoryWithdraw;
use app\common\model\jj\Escrow;
use app\common\model\xiluxc\current\Config;
use app\common\model\xiluxc\user\Third;
use think\Db;
use think\Exception;

/**
 * 工厂端 - 钱包管理
 */
class FcWallet extends XiluxcApi
{
    protected $noNeedLogin = ['notify'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取当前用户关联的工厂（必须已认证）
     */
    protected function getFactory()
    {
        $factory = Factory::where('user_id', $this->auth->id)->find();
        if (!$factory) {
            $this->error('请先完成企业认证');
        }
        if ($factory['status'] != Factory::STATUS_NORMAL) {
            $this->error('企业认证未通过');
        }
        return $factory;
    }

    /**
     * 格式化金额
     */
    protected function formatMoney($val)
    {
        return number_format(floatval($val ?: 0), 2, '.', ',');
    }

    /**
     * 获取钱包余额
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function balance()
    {
        $factory = $this->getFactory();
        $account = FactoryAccount::getOrCreate($factory['id'], $this->auth->id);

        $this->success('查询成功', [
            'money'           => $this->formatMoney($account['money']),
            'frozen_money'    => $this->formatMoney($account['frozen_money']),
            'total_recharge'  => $this->formatMoney($account['total_recharge']),
            'total_settled'   => $this->formatMoney($account['total_settled']),
            'total_withdraw'  => $this->formatMoney(isset($account['total_withdraw']) ? $account['total_withdraw'] : 0),
        ]);
    }

    /**
     * 资金流水列表
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="type", type="int", required=false, description="类型筛选:1=充值,2=佣金冻结,3=佣金结算,4=解冻退回,5=提现,6=提现退回")
     * @ApiParams (name="page", type="int", required=false, description="页码")
     * @ApiParams (name="pagesize", type="int", required=false, description="每页数量")
     */
    public function fund_log()
    {
        $factory = $this->getFactory();

        $type     = $this->request->get('type', '');
        $page     = $this->request->get('page/d', 1);
        $pagesize = $this->request->get('pagesize/d', 15);

        $query = FactoryFundLog::where('factory_id', $factory['id']);
        if ($type !== '') {
            $query->where('type', intval($type));
        }

        $list = $query->order('id', 'desc')
            ->paginate($pagesize, false, ['page' => $page]);

        $listData = $list->toArray();
        foreach ($listData['data'] as &$item) {
            $item['amount'] = $this->formatMoney($item['amount']);
            $item['before_money'] = $this->formatMoney($item['before_money']);
            $item['after_money'] = $this->formatMoney($item['after_money']);
        }
        unset($item);

        $this->success('查询成功', ['list' => $listData]);
    }

    /**
     * 创建充值订单
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="amount", type="float", required=true, description="充值金额")
     * @ApiParams (name="platform", type="string", required=false, description="支付平台:wxmini/wxoffical")
     * @ApiParams (name="openid", type="string", required=false, description="公众号openid")
     */
    public function create_recharge()
    {
        $factory = $this->getFactory();
        $amount   = $this->request->post('amount/f', 0);
        $platform = $this->request->post('platform', 'wxmini');

        if ($amount < 1) {
            $this->error('充值金额最低1元');
        }

        // 确保账户存在
        FactoryAccount::getOrCreate($factory['id'], $this->auth->id);

        // 创建充值订单
        $orderNo = FactoryRechargeOrder::generateOrderNo();
        $order = FactoryRechargeOrder::create([
            'order_no'   => $orderNo,
            'factory_id' => $factory['id'],
            'user_id'    => $this->auth->id,
            'amount'     => $amount,
            'pay_type'   => 'wechat',
            'pay_status' => FactoryRechargeOrder::PAY_STATUS_PENDING,
        ]);

        // 获取微信支付配置
        $config = Config::getMyConfig('wxpayment');
        if (!$config || !$config['mch_id'] || !$config['mch_key']) {
            $this->error('请正确配置微信商户信息');
        }

        // 获取openid
        if ($platform == 'wxmini') {
            $openid = Third::where('user_id', $this->auth->id)->where('platform', $platform)->value('openid');
        } elseif ($platform == 'wxoffical') {
            $openid = $this->request->post('openid');
        } else {
            $this->error('支付平台错误');
        }

        // Debug模式：openid为空时模拟充值成功
        $isDebug = config('app_debug');
        if (!$openid && $isDebug) {
            Db::startTrans();
            try {
                $order->save([
                    'pay_status'     => FactoryRechargeOrder::PAY_STATUS_PAID,
                    'pay_time'       => time(),
                    'transaction_id' => 'DEBUG_' . $orderNo,
                ]);

                $account = FactoryAccount::getOrCreate($factory['id'], $this->auth->id);
                $account->recharge($amount, $order['id'], '钱包充值(测试)¥' . $amount);

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error('充值处理失败：' . $e->getMessage());
            }

            $this->success('充值成功', ['mock_paid' => true]);
        }

        if (!$openid) {
            $this->error('支付参数错误，无法获取openid');
        }

        try {
            $wechat = new Wechat($platform);
            $orderData = [
                'body'       => '工厂钱包充值',
                'order_no'   => $orderNo,
                'pay_price'  => $amount,
                'notify_url' => request()->domain() . '/api/xiluxc.fc_wallet/notify',
                'openid'     => $openid,
            ];
            $result = $wechat->union_order($orderData);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success('创建充值订单成功', $result);
    }

    /**
     * 充值支付回调
     *
     * @ApiMethod (POST)
     */
    public function notify()
    {
        // 回调来自微信服务器，默认使用 wxmini 支付配置
        $platform = 'wxmini';
        $payment = new Payment($platform);
        $response = $payment->getPayment()->handlePaidNotify(function ($message, $fail) {
            // 验证支付结果
            if (!isset($message['result_code']) || $message['result_code'] !== 'SUCCESS') {
                return $fail('支付失败');
            }

            $orderNo       = $message['out_trade_no'] ?? '';
            $transactionId = $message['transaction_id'] ?? '';
            $totalFee      = intval($message['total_fee'] ?? 0);

            // 验证订单存在并校验金额
            $order = FactoryRechargeOrder::where('order_no', $orderNo)->find();
            if (!$order) {
                return $fail('订单不存在');
            }
            if ($order->pay_status == FactoryRechargeOrder::PAY_STATUS_PAID) {
                return true; // 已处理，幂等返回
            }
            $expectedFee = intval(bcmul($order->amount, '100', 0));
            if ($totalFee !== $expectedFee) {
                return $fail('支付金额不一致');
            }

            // 由 payNotify 独立管理事务，不再外层包裹
            $result = FactoryRechargeOrder::payNotify($orderNo, $transactionId);
            if (!$result) {
                return $fail('入账处理失败');
            }
            return true;
        });
        $response->send();
        return;
    }

    // ==================== 对公账户管理 ====================

    /**
     * 对公账户列表
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function bank_account_list()
    {
        $factory = $this->getFactory();

        $list = FactoryBankAccount::where('factory_id', $factory['id'])
            ->order('is_default', 'desc')
            ->order('id', 'desc')
            ->select();

        $this->success('查询成功', ['list' => $list]);
    }

    /**
     * 添加对公账户
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="account_name", type="string", required=true, description="开户名称")
     * @ApiParams (name="bank_name", type="string", required=true, description="开户银行")
     * @ApiParams (name="bank_branch", type="string", required=true, description="开户支行")
     * @ApiParams (name="bank_no", type="string", required=true, description="对公账号")
     */
    public function bank_account_add()
    {
        $factory = $this->getFactory();

        $count = FactoryBankAccount::where('factory_id', $factory['id'])->count();
        if ($count >= 5) {
            $this->error('最多添加5个对公账户');
        }

        $accountName = $this->request->post('account_name', '');
        $bankName    = $this->request->post('bank_name', '');
        $bankBranch  = $this->request->post('bank_branch', '');
        $bankNo      = $this->request->post('bank_no', '');

        if (!$accountName || !$bankName || !$bankNo) {
            $this->error('请填写完整的银行信息');
        }

        $bankAccount = FactoryBankAccount::create([
            'factory_id'   => $factory['id'],
            'user_id'      => $this->auth->id,
            'account_name' => $accountName,
            'bank_name'    => $bankName,
            'bank_branch'  => $bankBranch,
            'bank_no'      => $bankNo,
            'is_default'   => $count == 0 ? 1 : 0,
        ]);

        $this->success('添加成功', ['id' => $bankAccount->id]);
    }

    /**
     * 编辑对公账户
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="id", type="int", required=true, description="账户ID")
     * @ApiParams (name="account_name", type="string", required=true, description="开户名称")
     * @ApiParams (name="bank_name", type="string", required=true, description="开户银行")
     * @ApiParams (name="bank_branch", type="string", required=true, description="开户支行")
     * @ApiParams (name="bank_no", type="string", required=true, description="对公账号")
     */
    public function bank_account_edit()
    {
        $factory = $this->getFactory();

        $id = $this->request->post('id/d', 0);
        $bankAccount = FactoryBankAccount::where('id', $id)
            ->where('factory_id', $factory['id'])
            ->find();

        if (!$bankAccount) {
            $this->error('账户不存在');
        }

        $accountName = $this->request->post('account_name', '');
        $bankName    = $this->request->post('bank_name', '');
        $bankBranch  = $this->request->post('bank_branch', '');
        $bankNo      = $this->request->post('bank_no', '');

        if (!$accountName || !$bankName || !$bankNo) {
            $this->error('请填写完整的银行信息');
        }

        $bankAccount->save([
            'account_name' => $accountName,
            'bank_name'    => $bankName,
            'bank_branch'  => $bankBranch,
            'bank_no'      => $bankNo,
        ]);

        $this->success('修改成功');
    }

    /**
     * 删除对公账户
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="id", type="int", required=true, description="账户ID")
     */
    public function bank_account_delete()
    {
        $factory = $this->getFactory();

        $id = $this->request->post('id/d', 0);
        $bankAccount = FactoryBankAccount::where('id', $id)
            ->where('factory_id', $factory['id'])
            ->find();

        if (!$bankAccount) {
            $this->error('账户不存在');
        }

        // 检查是否有进行中的提现
        $pendingCount = FactoryWithdraw::where('bank_account_id', $id)
            ->whereIn('state', [FactoryWithdraw::STATE_PENDING, FactoryWithdraw::STATE_PROCESSING])
            ->count();

        if ($pendingCount > 0) {
            $this->error('该账户有进行中的提现，无法删除');
        }

        $bankAccount->delete();

        // 如果删除的是默认账户，自动将第一个设为默认
        if ($bankAccount->is_default) {
            $first = FactoryBankAccount::where('factory_id', $factory['id'])
                ->order('id', 'asc')
                ->find();
            if ($first) {
                $first->save(['is_default' => 1]);
            }
        }

        $this->success('删除成功');
    }

    /**
     * 设置默认对公账户
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="id", type="int", required=true, description="账户ID")
     */
    public function bank_account_set_default()
    {
        $factory = $this->getFactory();

        $id = $this->request->post('id/d', 0);
        $bankAccount = FactoryBankAccount::where('id', $id)
            ->where('factory_id', $factory['id'])
            ->find();

        if (!$bankAccount) {
            $this->error('账户不存在');
        }

        FactoryBankAccount::setDefault($factory['id'], $id);

        $this->success('设置成功');
    }

    // ==================== 提现 ====================

    /**
     * 申请提现
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="amount", type="float", required=true, description="提现金额")
     * @ApiParams (name="bank_account_id", type="int", required=true, description="银行账户ID")
     */
    public function create_withdraw()
    {
        $factory = $this->getFactory();

        $amount        = $this->request->post('amount/f', 0);
        $bankAccountId = $this->request->post('bank_account_id/d', 0);

        if ($amount <= 0) {
            $this->error('提现金额必须大于0');
        }

        $bankAccount = FactoryBankAccount::where('id', $bankAccountId)
            ->where('factory_id', $factory['id'])
            ->find();

        if (!$bankAccount) {
            $this->error('银行账户不存在');
        }

        $account = FactoryAccount::getOrCreate($factory['id'], $this->auth->id);

        if (bccomp($account->money, $amount, 2) < 0) {
            $this->error('可用余额不足');
        }

        try {
            $withdraw = FactoryWithdraw::applyWithdraw($factory, $account, $bankAccount, $amount);
            $this->success('提现申请提交成功', ['order_no' => $withdraw->order_no]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 提现记录列表
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="state", type="int", required=false, description="状态:1=审核中,2=处理中,3=已处理,4=已拒绝")
     * @ApiParams (name="page", type="int", required=false, description="页码")
     * @ApiParams (name="pagesize", type="int", required=false, description="每页数量")
     */
    public function withdraw_log()
    {
        $factory = $this->getFactory();

        $state    = $this->request->get('state', '');
        $page     = $this->request->get('page/d', 1);
        $pagesize = $this->request->get('pagesize/d', 15);

        $query = FactoryWithdraw::where('factory_id', $factory['id']);
        if ($state !== '') {
            $query->where('state', intval($state));
        }

        $list = $query->order('id', 'desc')
            ->paginate($pagesize, false, ['page' => $page]);

        $listData = $list->toArray();
        foreach ($listData['data'] as &$item) {
            $item['money']      = $this->formatMoney($item['money']);
            $item['real_money'] = $this->formatMoney($item['real_money']);
            $item['rate_money'] = $this->formatMoney($item['rate_money']);
        }
        unset($item);

        $this->success('查询成功', ['list' => $listData]);
    }

    /**
     * 托管记录列表
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="status", type="int", required=false, description="状态:0=托管中,1=已结算,2=已退回")
     * @ApiParams (name="page", type="int", required=false, description="页码")
     * @ApiParams (name="pagesize", type="int", required=false, description="每页数量")
     */
    public function escrow_list()
    {
        $factory = $this->getFactory();

        $status   = $this->request->get('status', '');
        $page     = $this->request->get('page/d', 1);
        $pagesize = $this->request->get('pagesize/d', 15);

        $query = Escrow::where('factory_id', $factory['id']);
        if ($status !== '') {
            $query->where('status', intval($status));
        }

        $list = $query->order('id', 'desc')
            ->paginate($pagesize, false, ['page' => $page]);

        $listData = $list->toArray();
        foreach ($listData['data'] as &$item) {
            $item['total_amount']     = $this->formatMoney($item['total_amount']);
            $item['platform_fee']     = $this->formatMoney($item['platform_fee']);
            $item['tax_amount']       = $this->formatMoney($item['tax_amount']);
            $item['logistics_rebate'] = $this->formatMoney($item['logistics_rebate']);
            $item['agent_settlement'] = $this->formatMoney($item['agent_settlement']);
        }
        unset($item);

        $this->success('查询成功', ['list' => $listData]);
    }
}

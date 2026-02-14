<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\Factory;
use app\common\model\jj\Order;
use app\common\model\jj\Deposit;
use app\common\model\jj\Contract;
use app\common\model\jj\Logistics;
use app\common\model\jj\OrderLog;
use app\common\model\jj\Commission;
use app\common\model\jj\FactoryAccount;
use app\common\model\jj\Escrow;
use app\common\model\jj\PaymentProof;
use app\common\model\User;
use think\Db;

/**
 * 工厂端 - 订单管理
 */
class FcOrder extends XiluxcApi
{
    protected $noNeedLogin = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取当前用户关联的工厂
     */
    protected function getFactory()
    {
        $factory = Factory::where('user_id', $this->auth->id)->find();
        if (!$factory) {
            $this->error('请先完成企业认证');
        }
        return $factory;
    }

    /**
     * 清除工厂相关的所有缓存
     */
    protected function clearFactoryCache($fid)
    {
        cache('fc_overview_' . $fid, null);
        cache('fc_finance_' . $fid, null);
        cache('fc_todo_' . $fid, null);
        cache('fc_prodstats_' . $fid, null);
    }

    /**
     * 格式化金额：千分位 + 2位小数，空值返回 "0.00"
     */
    protected function formatMoney($val)
    {
        return number_format(floatval($val ?: 0), 2, '.', ',');
    }

    /**
     * 订单列表
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="status", type="string", required=false, description="状态筛选")
     * @ApiParams (name="keyword", type="string", required=false, description="关键词搜索")
     * @ApiParams (name="page", type="int", required=false, description="页码")
     * @ApiParams (name="pagesize", type="int", required=false, description="每页数量")
     */
    public function index()
    {
        $factory = $this->getFactory();
        $status = $this->request->get('status', '');
        $keyword = $this->request->get('keyword', '');
        $page = $this->request->get('page/d', 1);
        $pagesize = $this->request->get('pagesize/d', 10);

        $query = Order::where('factory_id', $factory['id']);

        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($keyword) {
            // 搜索订单号、商品名、居间人昵称
            $agentIds = Db::name('user')->where('nickname', 'like', '%' . $keyword . '%')->column('id');
            $query->where(function ($q) use ($keyword, $agentIds) {
                $q->where('order_sn', 'like', '%' . $keyword . '%')
                  ->whereOr('product_name', 'like', '%' . $keyword . '%');
                if ($agentIds) {
                    $q->whereOr('agent_id', 'in', $agentIds);
                }
            });
        }

        $list = $query->order('id', 'desc')
            ->paginate($pagesize, false, ['page' => $page]);

        // 格式化金额字段 + 关联居间人信息
        $listData = $list->toArray();
        $agentIds = array_unique(array_column($listData['data'], 'agent_id'));
        $agents = [];
        if ($agentIds) {
            $agents = Db::name('user')->where('id', 'in', $agentIds)->column('nickname,mobile,avatar', 'id');
        }
        foreach ($listData['data'] as &$item) {
            $item['unit_price'] = $this->formatMoney($item['unit_price'] ?? 0);
            $item['total_amount'] = $this->formatMoney($item['total_amount'] ?? 0);
            $item['commission_amount'] = $this->formatMoney($item['commission_amount'] ?? 0);
            $item['deposit_amount'] = $this->formatMoney($item['deposit_amount'] ?? 0);
            $item['factory_bonus'] = $this->formatMoney($item['factory_bonus'] ?? 0);
            $item['logistics_rebate'] = $this->formatMoney($item['logistics_rebate'] ?? 0);
            $aid = $item['agent_id'] ?? 0;
            $item['agent_name'] = isset($agents[$aid]) ? ($agents[$aid]['nickname'] ?? '') : '';
            $item['agent_mobile'] = isset($agents[$aid]) ? ($agents[$aid]['mobile'] ?? '') : '';
            // 合同是否已上传（状态3时前端据此判断是否显示"审核合同"按钮）
            $item['contract_uploaded'] = false;
            if ($item['status'] == Order::STATUS_CONTRACT) {
                $contract = Contract::where('order_id', $item['id'])->find();
                $item['contract_uploaded'] = $contract && $contract['status'] == Contract::STATUS_UPLOADED;
            }
            // 付款证明是否待审核（状态4时前端据此判断是否显示"审核付款证明"按钮）
            $item['proof_pending'] = false;
            if ($item['status'] == Order::STATUS_EXECUTING) {
                $proof = PaymentProof::where('order_id', $item['id'])->find();
                $item['proof_pending'] = $proof && $proof['status'] == PaymentProof::STATUS_PENDING;
            }
        }
        unset($item);

        // 各状态计数（合并为1条SQL）
        $statusCounts = Db::name('jj_order')
            ->where('factory_id', $factory['id'])
            ->whereIn('status', [
                Order::STATUS_PENDING, Order::STATUS_DEPOSIT, Order::STATUS_DEPOSITED, Order::STATUS_CONTRACT,
                Order::STATUS_EXECUTING, Order::STATUS_SETTLING, Order::STATUS_SETTLED
            ])
            ->field('status, COUNT(*) as cnt')
            ->group('status')
            ->select();
        $countMap = [];
        foreach ($statusCounts as $row) {
            $countMap[$row['status']] = intval($row['cnt']);
        }
        $stats = [
            'pending'     => $countMap[Order::STATUS_PENDING] ?? 0,
            'pay_commission' => $countMap[Order::STATUS_DEPOSIT] ?? 0,
            'deposit'     => $countMap[Order::STATUS_DEPOSITED] ?? 0,
            'contract'    => $countMap[Order::STATUS_CONTRACT] ?? 0,
            'executing'   => $countMap[Order::STATUS_EXECUTING] ?? 0,
            'settling'    => $countMap[Order::STATUS_SETTLING] ?? 0,
            'settled'     => $countMap[Order::STATUS_SETTLED] ?? 0,
        ];

        $this->success('查询成功', [
            'list'  => $listData,
            'stats' => $stats,
        ]);
    }

    /**
     * 订单详情
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="order_id", type="int", required=true, description="订单ID")
     */
    public function detail()
    {
        $factory = $this->getFactory();
        $orderId = $this->request->get('order_id/d', 0);

        $order = Order::where('id', $orderId)->where('factory_id', $factory['id'])->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        $deposit = Deposit::where('order_id', $orderId)->find();
        $contract = Contract::where('order_id', $orderId)->find();
        $logistics = Logistics::where('order_id', $orderId)->find();
        $paymentProof = PaymentProof::where('order_id', $orderId)->find();

        // 佣金锁定倒计时（保证金已缴纳后2小时内必须锁定）
        $lockDeadline = 0;
        if ($order['status'] == Order::STATUS_DEPOSITED && $deposit && $deposit['pay_status'] == Deposit::PAY_STATUS_PAID) {
            $lockDeadline = $deposit['updatetime'] + 2 * 3600;
        }

        // 查询居间人信息
        $agent = Db::name('user')->where('id', $order['agent_id'])->find();

        $this->success('查询成功', [
            'order_id'          => $order['id'],
            'order_sn'          => $order['order_sn'],
            'product_name'      => $order['product_name'],
            'cover_image'       => $order['cover_image'],
            'agent_name'        => $agent ? ($agent['nickname'] ?? '') : '',
            'agent_mobile'      => $agent ? ($agent['mobile'] ?? '') : '',
            'agent_avatar'      => $agent ? ($agent['avatar'] ?? '') : '',
            'unit_price'        => $this->formatMoney($order['unit_price']),
            'quantity'          => $order['quantity'],
            'total_amount'      => $this->formatMoney($order['total_amount']),
            'commission_rate'   => $order['commission_rate'],
            'commission_amount' => $this->formatMoney($order['commission_amount']),
            'service_fee'       => $this->formatMoney($order['service_fee'] ?: 0),
            'service_fee_rate'  => $order['service_fee_rate'] ?: 1,
            'deposit_rate'      => $order['deposit_rate'],
            'deposit_amount'    => $this->formatMoney($order['deposit_amount']),
            'status'            => $order['status'],
            'status_text'       => $order['status_text'],
            'create_time'       => date('Y-m-d H:i', $order['createtime']),
            'lock_deadline'     => $lockDeadline,
            'contract_upload_hours' => $order['contract_upload_hours'] ?: 0,
            'execution_hours'   => $order['execution_hours'] ?: 0,
            'payment_urge_deadline' => $order['payment_urge_deadline'] ?: 0,
            'is_debug'              => config('app_debug') ? true : false,
            'deposit_info'      => $deposit ? [
                'amount'      => $this->formatMoney($deposit['amount']),
                'pay_status'  => $deposit['pay_status'],
                'status_text' => $deposit['pay_status_text'],
            ] : null,
            'contract_info'     => $contract ? [
                'file_url'         => $contract['file_url'] ? cdnurl($contract['file_url'], true) : '',
                'file_name'        => $contract['file_name'],
                'status'           => $contract['status'],
                'status_text'      => $contract['status_text'],
                'upload_deadline'  => $contract['upload_deadline'] ?: 0,
            ] : null,
            'logistics_info'    => $logistics ? [
                'logistics_type' => $logistics['logistics_type'],
                'company_name'   => $logistics['company_name'],
                'tracking_no'    => $logistics['tracking_no'],
                'status'         => $logistics['status'],
                'status_text'    => $logistics['status_text'],
            ] : null,
            'payment_proof'     => $paymentProof ? [
                'file_urls'     => $paymentProof['file_urls'],
                'remark'        => $paymentProof['remark'] ?: '',
                'logistics_method' => $paymentProof['logistics_method'] ?: '',
                'status'        => $paymentProof['status'],
                'status_text'   => $paymentProof['status_text'],
                'reject_reason' => $paymentProof['reject_reason'] ?: '',
            ] : null,
        ]);
    }

    /**
     * 确认接单
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="order_id", type="int", required=true, description="订单ID")
     */
    public function confirm()
    {
        $factory = $this->getFactory();
        $orderId = $this->request->post('order_id/d', 0);

        $order = Order::where('id', $orderId)->where('factory_id', $factory['id'])->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        if ($order['status'] != Order::STATUS_PENDING) {
            $this->error('当前订单状态不允许确认');
        }

        Db::startTrans();
        try {
            $order->save(['status' => Order::STATUS_DEPOSIT]);

            OrderLog::create([
                'order_id'      => $orderId,
                'from_status'   => Order::STATUS_PENDING,
                'to_status'     => Order::STATUS_DEPOSIT,
                'description'   => '工厂确认接单',
                'operator_type' => 'factory',
                'operator_id'   => $this->auth->id,
            ]);

            Db::commit();
            $this->clearFactoryCache($factory['id']);
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('操作失败，请重试');
        }

        $this->success('确认成功，等待居间人缴纳保证金');
    }

    /**
     * 锁定佣金
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="order_id", type="int", required=true, description="订单ID")
     * @ApiParams (name="contract_upload_hours", type="int", required=true, description="合同上传期限:24=24小时,48=48小时")
     * @ApiParams (name="contract_upload_minutes", type="int", required=false, description="[DEBUG]合同上传期限(分钟),仅调试模式有效")
     */
    public function lock_commission()
    {
        $factory = $this->getFactory();
        $orderId = $this->request->post('order_id/d', 0);
        $contractUploadHours = $this->request->post('contract_upload_hours/d', 0);
        $debugMinutes = $this->request->post('contract_upload_minutes/d', 0);
        $isDebug = config('app_debug');

        // debug模式下支持自定义分钟数
        if ($isDebug && $debugMinutes > 0) {
            $contractDeadlineSeconds = $debugMinutes * 60;
            $contractUploadHours = $debugMinutes; // 记录到订单字段（此时含义为分钟）
        } else {
            // 正式模式：校验合同上传期限
            if (!in_array($contractUploadHours, [24, 48])) {
                $this->error('请选择合同上传期限（24小时或48小时）');
            }
            $contractDeadlineSeconds = $contractUploadHours * 3600;
        }

        $order = Order::where('id', $orderId)->where('factory_id', $factory['id'])->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        if (!in_array($order['status'], [Order::STATUS_DEPOSIT, Order::STATUS_DEPOSITED])) {
            $this->error('当前订单状态不允许缴纳佣金');
        }

        // 状态2时检查保证金是否已缴纳
        if ($order['status'] == Order::STATUS_DEPOSITED) {
            $deposit = Deposit::where('order_id', $orderId)->where('pay_status', Deposit::PAY_STATUS_PAID)->find();
            if (!$deposit) {
                $this->error('居间人尚未缴纳保证金');
            }

            // 检查2小时锁定期限
            $deadline = $deposit['updatetime'] + 2 * 3600;
            if (time() > $deadline) {
                $this->error('锁定佣金已超时（超过2小时），该订单将由系统自动处理');
            }
        }

        // 计算佣金金额 + 服务费
        $commissionAmount = floatval($order['commission_amount'] ?: 0);
        $serviceFee = floatval($order['service_fee'] ?: 0);
        $totalFreeze = bcadd($commissionAmount, $serviceFee, 2);
        if ($commissionAmount <= 0) {
            $this->error('佣金金额异常');
        }

        // 获取工厂账户并检查余额
        $account = FactoryAccount::getOrCreate($factory['id'], $this->auth->id);
        if (bccomp($account['money'], $totalFreeze, 2) < 0) {
            $this->error('钱包余额不足，请先充值。当前余额：' . $account['money'] . '元，需冻结：' . $totalFreeze . '元');
        }

        Db::startTrans();
        try {
            // 事务内加锁重新校验（防并发重复冻结）
            $order = Order::lock(true)->where('id', $orderId)->find();
            if (!in_array($order['status'], [Order::STATUS_DEPOSIT, Order::STATUS_DEPOSITED])) {
                Db::rollback();
                $this->error('订单状态已变更，请刷新重试');
            }

            // 检查是否已存在托管记录（防重复冻结）
            $existingEscrow = Escrow::where('order_id', $orderId)
                ->where('factory_id', $factory['id'])
                ->where('status', Escrow::STATUS_HOLDING)
                ->lock(true)
                ->find();
            if ($existingEscrow) {
                Db::rollback();
                $this->error('佣金已锁定，请勿重复操作');
            }

            // 从钱包冻结佣金+服务费
            $freezeResult = $account->freeze($totalFreeze, $orderId, '订单' . $order['order_sn'] . '佣金+服务费冻结');
            if (!$freezeResult) {
                Db::rollback();
                $this->error('佣金冻结失败，请检查钱包余额');
            }

            // 创建托管记录（账户B）含 service_fee
            Escrow::create([
                'order_id'       => $orderId,
                'factory_id'     => $factory['id'],
                'agent_user_id'  => $order['agent_id'],
                'total_amount'   => $commissionAmount,
                'service_fee'    => $serviceFee,
                'status'         => Escrow::STATUS_HOLDING,
            ]);

            // 状态变更为待上传合同，写入工厂选择的合同上传期限
            $contractDeadline = time() + $contractDeadlineSeconds;
            $order->save([
                'status'              => Order::STATUS_CONTRACT,
                'commission_locked'   => 1,
                'commission_lock_time' => time(),
                'contract_upload_hours' => $contractUploadHours,
                'contract_upload_deadline' => $contractDeadline,
            ]);

            // 创建合同记录，按选择的时间设置截止
            $existingContract = Contract::where('order_id', $orderId)->find();
            if (!$existingContract) {
                Contract::create([
                    'order_id'         => $orderId,
                    'file_url'         => '',
                    'file_name'        => '',
                    'upload_deadline'  => $contractDeadline,
                    'status'           => Contract::STATUS_PENDING,
                ]);
            }

            OrderLog::create([
                'order_id'      => $orderId,
                'from_status'   => $order->getData('status'),
                'to_status'     => Order::STATUS_CONTRACT,
                'description'   => '工厂缴纳佣金' . $commissionAmount . '元+服务费' . $serviceFee . '元，合同上传期限' . $contractUploadHours . '小时',
                'operator_type' => 'factory',
                'operator_id'   => $this->auth->id,
            ]);

            Db::commit();
            $this->clearFactoryCache($factory['id']);
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('操作失败，请重试');
        }

        $this->success('佣金已锁定，等待居间人上传合同');
    }

    /**
     * 审核合同
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="order_id", type="int", required=true, description="订单ID")
     * @ApiParams (name="action", type="string", required=true, description="操作:approve=通过 reject=驳回")
     * @ApiParams (name="payment_urge_days", type="int", required=false, description="付款期限(天):7=7天,14=14天，审核通过时必填")
     * @ApiParams (name="payment_urge_minutes", type="int", required=false, description="[DEBUG]付款期限(分钟),仅调试模式有效")
     * @ApiParams (name="reject_reason", type="string", required=false, description="驳回原因")
     */
    public function review_contract()
    {
        $factory = $this->getFactory();
        $orderId = $this->request->post('order_id/d', 0);
        $action = $this->request->post('action');
        $paymentUrgeDays = $this->request->post('payment_urge_days/d', 0);
        $debugMinutes = $this->request->post('payment_urge_minutes/d', 0);
        $rejectReason = $this->request->post('reject_reason', '');

        if (!in_array($action, ['approve', 'reject'])) {
            $this->error('操作参数错误');
        }

        $order = Order::where('id', $orderId)->where('factory_id', $factory['id'])->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        $contract = Contract::where('order_id', $orderId)->find();
        if (!$contract || $contract['status'] != Contract::STATUS_UPLOADED) {
            $this->error('合同尚未上传或状态异常');
        }

        // 审核通过时的参数预校验（在事务外，确保错误信息不被 catch 吞掉）
        $isDebug = config('app_debug');
        $paymentUrgeHours = 0;
        $urgeDeadline = 0;
        $deadlineDesc = '';
        if ($action === 'approve') {
            if ($isDebug && $debugMinutes > 0) {
                $paymentUrgeHours = $debugMinutes;
                $urgeDeadline = time() + $debugMinutes * 60;
                $deadlineDesc = $debugMinutes . '分钟(测试)';
            } else {
                if (!in_array($paymentUrgeDays, [7, 14])) {
                    $this->error('请选择付款期限（7天或14天）');
                }
                $paymentUrgeHours = $paymentUrgeDays * 24;
                $urgeDeadline = time() + $paymentUrgeHours * 3600;
                $deadlineDesc = $paymentUrgeDays . '天';
            }
        }

        Db::startTrans();
        try {
            if ($action === 'approve') {
                // 合同通过，进入履约执行期
                $order->save([
                    'status' => Order::STATUS_EXECUTING,
                    'payment_urge_hours' => $paymentUrgeHours,
                    'execution_hours'    => $paymentUrgeHours,
                    'payment_urge_deadline' => $urgeDeadline,
                ]);
                $contract->save([
                    'status' => Contract::STATUS_APPROVED,
                    'review_time' => time(),
                ]);

                OrderLog::create([
                    'order_id'      => $orderId,
                    'from_status'   => $order['status'],
                    'to_status'     => Order::STATUS_EXECUTING,
                    'description'   => '工厂审核合同通过，进入履约期（付款期限' . $deadlineDesc . '）',
                    'operator_type' => 'factory',
                    'operator_id'   => $this->auth->id,
                ]);
            } else {
                if (!$rejectReason) {
                    Db::rollback();
                    $this->error('请填写驳回原因');
                }
                // 合同驳回，标记为已驳回状态
                $contract->save([
                    'status'        => Contract::STATUS_REJECTED,
                    'reject_reason' => $rejectReason,
                    'review_time'   => time(),
                ]);

                $order->save(['status' => Order::STATUS_CONTRACT]);

                OrderLog::create([
                    'order_id'      => $orderId,
                    'from_status'   => $order['status'],
                    'to_status'     => Order::STATUS_CONTRACT,
                    'description'   => '工厂驳回合同：' . $rejectReason,
                    'operator_type' => 'factory',
                    'operator_id'   => $this->auth->id,
                ]);
            }

            Db::commit();
            $this->clearFactoryCache($factory['id']);
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('操作失败，请重试');
        }

        $this->success($action === 'approve' ? '合同审核通过' : '合同已驳回');
    }

    /**
     * 确认发货
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="order_id", type="int", required=true, description="订单ID")
     * @ApiParams (name="company_name", type="string", required=true, description="物流公司名称")
     * @ApiParams (name="tracking_no", type="string", required=true, description="运单号")
     */
    public function confirm_shipment()
    {
        $factory = $this->getFactory();
        $orderId = $this->request->post('order_id/d', 0);
        $logisticsMethod = $this->request->post('logistics_method', 'factory_ship');
        $companyName = $this->request->post('company_name');
        $trackingNo = $this->request->post('tracking_no');

        if (!in_array($logisticsMethod, ['pickup', 'factory_ship'])) {
            $this->error('请选择物流方式');
        }

        if ($logisticsMethod === 'factory_ship' && (!$companyName || !$trackingNo)) {
            $this->error('请填写物流公司和运单号');
        }

        $order = Order::where('id', $orderId)->where('factory_id', $factory['id'])->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        if ($order['status'] != Order::STATUS_EXECUTING) {
            $this->error('当前订单状态不允许发货');
        }

        Db::startTrans();
        try {
            // 事务内加锁重新校验订单状态（防并发双重结算）
            $order = Order::lock(true)->where('id', $orderId)->find();
            if ($order['status'] != Order::STATUS_EXECUTING) {
                Db::rollback();
                $this->error('订单状态已变更，请刷新重试');
            }

            // 检查付款凭证
            $proof = PaymentProof::where('order_id', $orderId)->find();
            if (!$proof || !in_array($proof['status'], [PaymentProof::STATUS_PENDING, PaymentProof::STATUS_APPROVED])) {
                Db::rollback();
                $this->error('买家尚未上传付款凭证');
            }

            // 查找托管记录（加锁防并发）
            $escrow = Escrow::where('order_id', $orderId)
                ->where('factory_id', $factory['id'])
                ->where('status', Escrow::STATUS_HOLDING)
                ->lock(true)
                ->find();

            // 1. 审核通过付款凭证
            if ($proof['status'] == PaymentProof::STATUS_PENDING) {
                $proof->save([
                    'status'      => PaymentProof::STATUS_APPROVED,
                    'review_time' => time(),
                ]);
            }

            // 2. 创建或更新物流记录
            $logistics = Logistics::where('order_id', $orderId)->find();

            if ($logisticsMethod === 'pickup') {
                $logisticsData = [
                    'order_id'       => $orderId,
                    'logistics_type' => Logistics::TYPE_SELF,
                    'company_name'   => '自提',
                    'tracking_no'    => '',
                    'status'         => Logistics::STATUS_SHIPPED,
                    'ship_time'      => time(),
                    'timeline_json'  => json_encode([
                        ['time' => date('Y-m-d H:i'), 'desc' => '买家自提取货', 'status' => 'shipped'],
                    ], JSON_UNESCAPED_UNICODE),
                ];
                $logDesc = '买家自提取货';
            } else {
                $logisticsData = [
                    'order_id'       => $orderId,
                    'logistics_type' => Logistics::TYPE_PLATFORM,
                    'company_name'   => $companyName,
                    'tracking_no'    => $trackingNo,
                    'status'         => Logistics::STATUS_SHIPPED,
                    'ship_time'      => time(),
                    'timeline_json'  => json_encode([
                        ['time' => date('Y-m-d H:i'), 'desc' => '工厂已发货', 'status' => 'shipped'],
                    ], JSON_UNESCAPED_UNICODE),
                ];
                $logDesc = '工厂确认发货，物流：' . $companyName . ' ' . $trackingNo;
            }

            if ($logistics) {
                $logistics->save($logisticsData);
            } else {
                Logistics::create($logisticsData);
            }

            OrderLog::create([
                'order_id'      => $orderId,
                'from_status'   => Order::STATUS_EXECUTING,
                'to_status'     => Order::STATUS_EXECUTING,
                'description'   => $logDesc,
                'operator_type' => 'factory',
                'operator_id'   => $this->auth->id,
            ]);

            // 3. 自动结算
            $commissionAmount = floatval($order['commission_amount']);
            $factoryBonus = floatval($order['factory_bonus'] ?: 0);
            $logisticsRebateOrder = floatval($order['logistics_rebate'] ?: 0);

            if ($escrow) {
                $escrowCommission = floatval($escrow['total_amount']);
                $serviceFee = floatval($escrow['service_fee'] ?: 0);
                $totalFrozen = bcadd($escrowCommission, $serviceFee, 2);

                // 佣金拆分：仅扣个税
                $split = Escrow::calculateSplit($escrowCommission);

                // 从冻结金额中结算
                $account = FactoryAccount::where('factory_id', $factory['id'])->lock(true)->find();
                if (!$account) {
                    throw new \Exception('工厂账户不存在');
                }
                $settleResult = $account->settle($totalFrozen, $orderId, '订单' . $order['order_sn'] . '佣金+服务费结算');
                if (!$settleResult) {
                    throw new \Exception('佣金结算扣款失败');
                }

                // 居间人到手
                if (floatval($split['agent_settlement']) > 0) {
                    User::money($split['agent_settlement'], $order['agent_id'], '订单' . $order['order_sn'] . '佣金结算');
                }

                // 更新托管记录
                $escrow->save([
                    'platform_fee'     => $serviceFee,
                    'tax_amount'       => $split['tax_amount'],
                    'logistics_rebate' => $split['logistics_rebate'],
                    'agent_settlement' => $split['agent_settlement'],
                    'status'           => Escrow::STATUS_SETTLED,
                    'settle_time'      => time(),
                ]);

                // 创建佣金结算记录
                Commission::create([
                    'agent_id'          => $order['agent_id'],
                    'order_id'          => $orderId,
                    'order_no'          => $order['order_sn'],
                    'product_name'      => $order['product_name'],
                    'base_commission'   => $commissionAmount,
                    'factory_bonus'     => $factoryBonus,
                    'logistics_rebate'  => $logisticsRebateOrder,
                    'total_amount'      => $commissionAmount + $factoryBonus + $logisticsRebateOrder,
                    'platform_fee'      => $serviceFee,
                    'tax_amount'        => $split['tax_amount'],
                    'status'            => Commission::STATUS_SETTLED,
                    'settled_time'      => time(),
                ]);
            } else {
                // 兼容旧订单
                Commission::create([
                    'agent_id'          => $order['agent_id'],
                    'order_id'          => $orderId,
                    'order_no'          => $order['order_sn'],
                    'product_name'      => $order['product_name'],
                    'base_commission'   => $commissionAmount,
                    'factory_bonus'     => $factoryBonus,
                    'logistics_rebate'  => $logisticsRebateOrder,
                    'total_amount'      => $commissionAmount + $factoryBonus + $logisticsRebateOrder,
                    'status'            => Commission::STATUS_PENDING,
                ]);
            }

            // 退还保证金（退到居间人平台余额）
            $deposit = Deposit::where('order_id', $orderId)
                ->where('pay_status', Deposit::PAY_STATUS_PAID)
                ->find();
            if ($deposit) {
                $deposit->save(['pay_status' => Deposit::PAY_STATUS_REFUNDED]);
                $depositRefund = floatval($deposit['amount']);
                if ($depositRefund > 0) {
                    User::money($depositRefund, $order['agent_id'], '订单' . $order['order_sn'] . '保证金退还');
                }
            }

            // 4. 订单状态直接变为已结算
            $order->save(['status' => Order::STATUS_SETTLED]);

            OrderLog::create([
                'order_id'      => $orderId,
                'from_status'   => Order::STATUS_EXECUTING,
                'to_status'     => Order::STATUS_SETTLED,
                'description'   => '工厂确认发货，系统自动结算完成',
                'operator_type' => 'factory',
                'operator_id'   => $this->auth->id,
            ]);

            Db::commit();
            $this->clearFactoryCache($factory['id']);
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('操作失败，请重试');
        }

        $this->success($logisticsMethod === 'pickup' ? '自提确认成功，已自动结算' : '发货成功，已自动结算');
    }

    /**
     * 同意放款（触发结算）
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="order_id", type="int", required=true, description="订单ID")
     */
    public function release_payment()
    {
        $factory = $this->getFactory();
        $orderId = $this->request->post('order_id/d', 0);

        $order = Order::where('id', $orderId)->where('factory_id', $factory['id'])->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        if ($order['status'] != Order::STATUS_SETTLING) {
            $this->error('当前订单状态不允许放款');
        }

        Db::startTrans();
        try {
            // 事务内加锁重新校验订单状态（防并发双重结算）
            $order = Order::lock(true)->where('id', $orderId)->find();
            if ($order['status'] != Order::STATUS_SETTLING) {
                Db::rollback();
                $this->error('订单状态已变更，请刷新重试');
            }

            // 检查付款证明已通过审核
            $paymentProof = PaymentProof::where('order_id', $orderId)->find();
            if (!$paymentProof || $paymentProof['status'] != PaymentProof::STATUS_APPROVED) {
                Db::rollback();
                $this->error('买家付款证明尚未审核通过，不能放款');
            }

            $originalStatus = $order['status'];
            $order->save(['status' => Order::STATUS_SETTLED]);

            // 查找托管记录并计算拆分（加锁防并发）
            $escrow = Escrow::where('order_id', $orderId)
                ->where('factory_id', $factory['id'])
                ->where('status', Escrow::STATUS_HOLDING)
                ->lock(true)
                ->find();

            $commissionAmount = floatval($order['commission_amount']);
            $factoryBonus = floatval($order['factory_bonus'] ?: 0);
            $logisticsRebateOrder = floatval($order['logistics_rebate'] ?: 0);

            if ($escrow) {
                // 从 Escrow 读取
                $escrowCommission = floatval($escrow['total_amount']);
                $serviceFee = floatval($escrow['service_fee'] ?: 0);
                $totalFrozen = bcadd($escrowCommission, $serviceFee, 2);

                // 佣金拆分：仅扣个税，不扣平台费
                $split = Escrow::calculateSplit($escrowCommission);

                // 从冻结金额中结算全部（佣金+服务费）
                $account = FactoryAccount::where('factory_id', $factory['id'])->lock(true)->find();
                if (!$account) {
                    throw new \Exception('工厂账户不存在');
                }
                $settleResult = $account->settle($totalFrozen, $orderId, '订单' . $order['order_sn'] . '佣金+服务费结算');
                if (!$settleResult) {
                    throw new \Exception('佣金结算扣款失败');
                }

                // 居间人到手 = commission × 80%（扣20%个税，不扣平台费）
                if (floatval($split['agent_settlement']) > 0) {
                    User::money($split['agent_settlement'], $order['agent_id'], '订单' . $order['order_sn'] . '佣金结算');
                }

                // 更新托管记录：平台收入=服务费
                $escrow->save([
                    'platform_fee'     => $serviceFee,
                    'tax_amount'       => $split['tax_amount'],
                    'logistics_rebate' => $split['logistics_rebate'],
                    'agent_settlement' => $split['agent_settlement'],
                    'status'           => Escrow::STATUS_SETTLED,
                    'settle_time'      => time(),
                ]);

                // 创建佣金结算记录（含拆分字段）
                Commission::create([
                    'agent_id'          => $order['agent_id'],
                    'order_id'          => $orderId,
                    'order_no'          => $order['order_sn'],
                    'product_name'      => $order['product_name'],
                    'base_commission'   => $commissionAmount,
                    'factory_bonus'     => $factoryBonus,
                    'logistics_rebate'  => $logisticsRebateOrder,
                    'total_amount'      => $commissionAmount + $factoryBonus + $logisticsRebateOrder,
                    'platform_fee'      => $serviceFee,
                    'tax_amount'        => $split['tax_amount'],
                    'status'            => Commission::STATUS_SETTLED,
                    'settled_time'      => time(),
                ]);
            } else {
                // 兼容旧订单（无托管记录）
                Commission::create([
                    'agent_id'          => $order['agent_id'],
                    'order_id'          => $orderId,
                    'order_no'          => $order['order_sn'],
                    'product_name'      => $order['product_name'],
                    'base_commission'   => $commissionAmount,
                    'factory_bonus'     => $factoryBonus,
                    'logistics_rebate'  => $logisticsRebateOrder,
                    'total_amount'      => $commissionAmount + $factoryBonus + $logisticsRebateOrder,
                    'status'            => Commission::STATUS_PENDING,
                ]);
            }

            // 退还保证金（退到居间人平台余额）
            $deposit = Deposit::where('order_id', $orderId)
                ->where('pay_status', Deposit::PAY_STATUS_PAID)
                ->find();
            if ($deposit) {
                $deposit->save(['pay_status' => Deposit::PAY_STATUS_REFUNDED]);
                $depositRefund = floatval($deposit['amount']);
                if ($depositRefund > 0) {
                    User::money($depositRefund, $order['agent_id'], '订单' . $order['order_sn'] . '保证金退还');
                }
            }

            OrderLog::create([
                'order_id'      => $orderId,
                'from_status'   => $originalStatus,
                'to_status'     => Order::STATUS_SETTLED,
                'description'   => '工厂同意放款，订单已结算',
                'operator_type' => 'factory',
                'operator_id'   => $this->auth->id,
            ]);

            Db::commit();
            $this->clearFactoryCache($factory['id']);
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('操作失败，请重试');
        }

        $this->success('放款成功，订单已结算');
    }

    /**
     * 审核买家付款证明
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="order_id", type="int", required=true, description="订单ID")
     * @ApiParams (name="action", type="string", required=true, description="操作:approve=通过 reject=驳回")
     * @ApiParams (name="reject_reason", type="string", required=false, description="驳回原因")
     */
    public function review_payment()
    {
        $factory = $this->getFactory();
        $orderId = $this->request->post('order_id/d', 0);
        $action = $this->request->post('action');
        $rejectReason = $this->request->post('reject_reason', '');

        if (!in_array($action, ['approve', 'reject'])) {
            $this->error('操作参数错误');
        }

        $order = Order::where('id', $orderId)->where('factory_id', $factory['id'])->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        if ($order['status'] != Order::STATUS_EXECUTING) {
            $this->error('当前订单状态不允许审核付款证明');
        }

        $proof = PaymentProof::where('order_id', $orderId)->find();
        if (!$proof || $proof['status'] != PaymentProof::STATUS_PENDING) {
            $this->error('没有待审核的付款证明');
        }

        Db::startTrans();
        try {
            if ($action === 'approve') {
                $proof->save([
                    'status'      => PaymentProof::STATUS_APPROVED,
                    'review_time' => time(),
                ]);

                $order->save(['status' => Order::STATUS_SETTLING]);

                OrderLog::create([
                    'order_id'      => $orderId,
                    'from_status'   => Order::STATUS_EXECUTING,
                    'to_status'     => Order::STATUS_SETTLING,
                    'description'   => '工厂确认买家已付款，进入待结算',
                    'operator_type' => 'factory',
                    'operator_id'   => $this->auth->id,
                ]);
            } else {
                if (!$rejectReason) {
                    Db::rollback();
                    $this->error('请填写驳回原因');
                }

                $proof->save([
                    'status'        => PaymentProof::STATUS_REJECTED,
                    'reject_reason' => $rejectReason,
                    'review_time'   => time(),
                ]);

                OrderLog::create([
                    'order_id'      => $orderId,
                    'from_status'   => Order::STATUS_EXECUTING,
                    'to_status'     => Order::STATUS_EXECUTING,
                    'description'   => '工厂驳回付款证明：' . $rejectReason,
                    'operator_type' => 'factory',
                    'operator_id'   => $this->auth->id,
                ]);
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('操作失败，请重试');
        }

        $this->success($action === 'approve' ? '审核通过，订单进入待结算' : '已驳回，等待居间人重新上传');
    }

    /**
     * 违约结算（合同超时/催款超时）
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="order_id", type="int", required=true, description="订单ID")
     */
    public function fail_settle()
    {
        $factory = $this->getFactory();
        $orderId = $this->request->post('order_id/d', 0);

        $order = Order::where('id', $orderId)->where('factory_id', $factory['id'])->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        if (!in_array($order['status'], [Order::STATUS_CONTRACT, Order::STATUS_EXECUTING])) {
            $this->error('当前订单状态不满足违约结算条件');
        }

        Db::startTrans();
        try {
            // 事务内加锁重新校验（防并发双重违约结算）
            $order = Order::lock(true)->where('id', $orderId)->find();

            // 验证触发条件：合同阶段超时 或 催款阶段超时
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
                Db::rollback();
                $this->error('当前订单状态不满足违约结算条件');
            }

            // 获取保证金记录
            $deposit = Deposit::where('order_id', $orderId)
                ->where('pay_status', Deposit::PAY_STATUS_PAID)
                ->find();
            if (!$deposit) {
                Db::rollback();
                $this->error('未找到已缴纳的保证金记录');
            }

            // 获取托管记录（加锁防并发）
            $escrow = Escrow::where('order_id', $orderId)
                ->where('factory_id', $factory['id'])
                ->where('status', Escrow::STATUS_HOLDING)
                ->lock(true)
                ->find();
            if (!$escrow) {
                Db::rollback();
                $this->error('未找到托管记录');
            }

            $depositAmount = floatval($deposit['amount']);
            $commissionAmount = floatval($escrow['total_amount']);
            $serviceFee = floatval($escrow['service_fee'] ?: 0);
            $totalFrozen = bcadd($commissionAmount, $serviceFee, 2);

            // 分账逻辑：
            // platformCut = depositAmount × 50% （平台从服务费中收取）
            // factoryRefund = commission + (serviceFee - platformCut) （退还工厂）
            // factoryCompensation = depositAmount （保证金赔付工厂）
            $platformCut = bcmul($depositAmount, '0.50', 2);
            $factoryRefund = bcadd($commissionAmount, bcsub($serviceFee, $platformCut, 2), 2);
            // 1. 保证金标记为赔付状态
            $deposit->save([
                'pay_status' => Deposit::PAY_STATUS_COMPENSATED,
            ]);

            // 2. 工厂冻结金额处理
            $account = FactoryAccount::where('factory_id', $factory['id'])->find();
            if ($account) {
                // 解冻退回工厂的部分（佣金 + 剩余服务费）
                if (floatval($factoryRefund) > 0) {
                    $account->unfreeze($factoryRefund, $orderId, '订单' . $order['order_sn'] . '违约退回佣金+剩余服务费');
                }
                // 平台收取部分（从冻结中结算）
                if (floatval($platformCut) > 0) {
                    $account->settle($platformCut, $orderId, '订单' . $order['order_sn'] . '违约-平台收取服务费');
                }
            }

            // 3. 保证金赔付工厂（转入工厂可用余额）
            if ($account && floatval($depositAmount) > 0) {
                $account->recharge($depositAmount, $orderId, '订单' . $order['order_sn'] . '违约-保证金赔付');
            }

            // 4. 更新 Escrow 状态
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
                'description'   => '违约结算：保证金' . $depositAmount . '元赔付工厂，平台收取' . $platformCut . '元，退还工厂' . $factoryRefund . '元',
                'operator_type' => 'factory',
                'operator_id'   => $this->auth->id,
            ]);

            Db::commit();
            $this->clearFactoryCache($factory['id']);
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('操作失败，请重试');
        }

        $this->success('违约结算完成', [
            'deposit_compensated' => $depositAmount,
            'platform_cut'        => $platformCut,
            'factory_refund'      => $factoryRefund,
        ]);
    }

    /**
     * 待办事项统计
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function todo_count()
    {
        $factory = $this->getFactory();
        $fid = $factory['id'];

        // 缓存命中检查（30秒TTL）
        $cacheKey = 'fc_todo_' . $fid;
        $cached = cache($cacheKey);
        if ($cached) {
            $this->success('查询成功', $cached);
        }

        // 聚合查询：5条SQL合并为2条
        // 1. 按状态分组计数（pending_confirm, pending_lock, pending_release）
        $statusCounts = Db::name('jj_order')
            ->where('factory_id', $fid)
            ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_DEPOSITED, Order::STATUS_EXECUTING, Order::STATUS_SETTLING])
            ->field('status, COUNT(*) as cnt')
            ->group('status')
            ->select();
        $cntMap = [];
        foreach ($statusCounts as $row) {
            $cntMap[$row['status']] = intval($row['cnt']);
        }

        // 2. 履约执行中的订单：合同待审核 + 付款证明待审核（用LEFT JOIN一次查出）
        $execDetail = Db::name('jj_order')
            ->alias('o')
            ->join('fa_jj_contract c', 'c.order_id = o.id', 'LEFT')
            ->join('fa_jj_payment_proof pp', 'pp.order_id = o.id', 'LEFT')
            ->where('o.factory_id', $fid)
            ->where('o.status', Order::STATUS_EXECUTING)
            ->field([
                "SUM(IF(c.status = " . Contract::STATUS_UPLOADED . ", 1, 0)) as pending_review",
                "SUM(IF(pp.status = " . PaymentProof::STATUS_PENDING . ", 1, 0)) as pending_payment_review",
            ])->find();

        $data = [
            'pending_confirm'        => $cntMap[Order::STATUS_PENDING] ?? 0,
            'pending_lock'           => $cntMap[Order::STATUS_DEPOSITED] ?? 0,
            'pending_review'         => intval($execDetail['pending_review'] ?? 0),
            'pending_payment_review' => intval($execDetail['pending_payment_review'] ?? 0),
            'pending_release'        => $cntMap[Order::STATUS_SETTLING] ?? 0,
        ];
        cache($cacheKey, $data, 30);
        $this->success('查询成功', $data);
    }
}

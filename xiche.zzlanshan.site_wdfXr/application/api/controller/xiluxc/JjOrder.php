<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\Order;
use app\common\model\jj\Product;
use app\common\model\jj\Cart;
use app\common\model\jj\Deposit;
use app\common\model\jj\DepositBatch;
use app\common\model\jj\Contract;
use app\common\model\jj\Logistics;
use app\common\model\jj\OrderLog;
use app\common\model\jj\Factory;
use app\common\model\jj\PaymentProof;
use think\Db;
use think\Validate;

/**
 * 居间人端 - 订单模块
 */
class JjOrder extends XiluxcApi
{
    protected $noNeedLogin = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 订单列表
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="status", type="string", required=false, description="订单状态筛选")
     * @ApiParams (name="keyword", type="string", required=false, description="关键词搜索(订单号/商品名/买家企业)")
     * @ApiParams (name="page", type="int", required=false, description="页码")
     * @ApiParams (name="pagesize", type="int", required=false, description="每页数量")
     */
    public function index()
    {
        $userId = $this->auth->id;
        $status = $this->request->get('status', '');
        $keyword = $this->request->get('keyword', '');
        $page = $this->request->get('page/d', 1);
        $pagesize = $this->request->get('pagesize/d', 10);

        $query = Order::where('agent_id', $userId);
        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('order_sn', 'like', '%' . $keyword . '%')
                  ->whereOr('product_name', 'like', '%' . $keyword . '%')
                  ->whereOr('buyer_company', 'like', '%' . $keyword . '%');
            });
        }

        $list = $query->order('id', 'desc')
            ->paginate($pagesize, false, ['page' => $page]);

        // 手动加载工厂数据，避免 JOIN 导致 status/id 等列名歧义
        $result = $list->toArray();
        $factoryIds = array_unique(array_filter(array_column($result['data'], 'factory_id')));
        $factories = [];
        if (!empty($factoryIds)) {
            $factoryRows = Factory::where('id', 'in', $factoryIds)->select();
            foreach ($factoryRows as $f) {
                $factories[$f['id']] = $f->toArray();
            }
        }
        foreach ($result['data'] as &$row) {
            $row['factory'] = isset($factories[$row['factory_id']]) ? $factories[$row['factory_id']] : null;
        }
        unset($row);

        $this->success('查询成功', $result);
    }

    /**
     * 提交接单
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function submit()
    {
        $userId = $this->auth->id;
        $productId = $this->request->post('productId/d', 0);
        $quantity = $this->request->post('quantity/d', 1);
        $cartId = $this->request->post('cart_id/d', 0);
        // 买家信息字段（暂时注释，后期需要时取消注释）
        // $companyName = $this->request->post('companyName');
        // $address = $this->request->post('address');
        // $contactName = $this->request->post('contactName');
        // $contactPhone = $this->request->post('contactPhone');
        // $creditCode = $this->request->post('creditCode');
        // $taxNumber = $this->request->post('taxNumber', '');

        if (!$productId) {
            $this->error('缺少产品ID');
        }
        if ($quantity < 1) {
            $quantity = 1;
        }
        // 买家信息验证（暂时注释，后期需要时取消注释）
        // if (!$companyName || !$address || !$contactName || !$contactPhone || !$creditCode) {
        //     $this->error('请填写完整买家信息');
        // }
        // if (!Validate::regex($contactPhone, "^1\d{10}$")) {
        //     $this->error('请输入正确的手机号');
        // }

        $product = Product::where('id', $productId)->where('status', Product::STATUS_ON)->find();
        if (!$product) {
            $this->error('产品不存在或已下架');
        }
        if ($product['stock'] < $quantity) {
            $this->error('库存不足，当前库存：' . $product['stock']);
        }

        $totalAmount = bcmul($product['price'], $quantity, 2);
        $commissionRate = 10; // 固定佣金比例：合同总价的10%
        $commissionAmount = bcmul($totalAmount, bcdiv($commissionRate, 100, 4), 2);
        $depositRate = 1; // 固定保证金比例：佣金总额的1%
        $depositAmount = bcmul($commissionAmount, bcdiv($depositRate, 100, 4), 2);
        $serviceFeeRate = 1; // 1%
        $serviceFee = bcmul($totalAmount, bcdiv($serviceFeeRate, 100, 4), 2);

        Db::startTrans();
        try {
            // 原子扣减库存，防止并发超卖
            $affected = Db::name('jj_product')
                ->where('id', $productId)
                ->where('stock', '>=', $quantity)
                ->setDec('stock', $quantity);
            if (!$affected) {
                Db::rollback();
                $this->error('库存不足，请减少数量后重试');
            }

            $order = Order::create([
                'order_sn'          => Order::generateOrderSn(),
                'agent_id'          => $userId,
                'product_id'        => $productId,
                'factory_id'        => $product['factory_id'],
                'product_name'      => $product['name'],
                'cover_image'       => $product->getData('cover_image'),
                'unit_price'        => $product['price'],
                'total_amount'      => $totalAmount,
                'quantity'          => $quantity,
                'commission_rate'   => $commissionRate,
                'commission_amount' => $commissionAmount,
                'service_fee'       => $serviceFee,
                'service_fee_rate'  => $serviceFeeRate,
                'deposit_rate'      => $depositRate,
                'deposit_amount'    => $depositAmount,
                'buyer_company'     => '', // 买家信息暂时留空
                'buyer_address'     => '',
                'buyer_contact'     => '',
                'buyer_phone'       => '',
                'buyer_credit_code' => '',
                'buyer_tax_number'  => '',
                'contract_upload_hours' => 0,
                'execution_hours'   => 0,
                'payment_urge_hours' => 0,
                'status'            => Order::STATUS_DEPOSIT,
            ]);

            // 创建保证金记录
            Deposit::create([
                'order_id'    => $order['id'],
                'user_id'     => $userId,
                'amount'      => $depositAmount,
                'pay_status'  => Deposit::PAY_STATUS_PENDING,
            ]);

            // 记录订单日志
            OrderLog::create([
                'order_id'      => $order['id'],
                'from_status'   => 0,
                'to_status'     => Order::STATUS_DEPOSIT,
                'description'   => '居间人提交接单(数量:' . $quantity . ')',
                'operator_type' => 'agent',
                'operator_id'   => $userId,
            ]);

            // 如果来自清单，删除对应清单记录
            if ($cartId) {
                Cart::where('id', $cartId)->where('user_id', $userId)->delete();
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('提交失败，请重试');
        }

        $this->success('提交成功', [
            'order_id'              => $order['id'],
            'order_sn'              => $order['order_sn'],
            'commission_amount'     => $commissionAmount,
            'deposit_rate'          => $depositRate,
            'contract_upload_hours' => (int)$order['contract_upload_hours'],
            'execution_hours'       => 72,
        ]);
    }

    /**
     * 保证金信息
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="order_id", type="int", required=true, description="订单ID")
     */
    public function deposit_info()
    {
        $userId = $this->auth->id;
        $orderId = $this->request->get('order_id/d', 0);

        $order = Order::where('id', $orderId)->where('agent_id', $userId)->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        $deposit = Deposit::where('order_id', $orderId)->find();
        $factory = Factory::where('id', $order['factory_id'])->find();

        $this->success('查询成功', [
            'order_id'          => $order['id'],
            'productName'       => $order['product_name'],
            'coverImage'        => $order['cover_image'],
            'factoryName'       => $factory ? ($factory['company_name'] ?: '') : '',
            'unitPrice'         => $this->formatMoney($order['unit_price']),
            'quantity'          => $order['quantity'],
            'totalAmount'       => $this->formatMoney($order['total_amount']),
            'commission'        => $order['commission_rate'],
            'commissionAmount'  => $this->formatMoney($order['commission_amount']),
            'depositRate'       => $order['deposit_rate'],
            'depositAmount'     => $deposit ? $deposit['amount'] : '0.00',
            'contractUploadHours' => (int)$order['contract_upload_hours'],
            'executionHours'    => $order['execution_hours'] ?: 72,
        ]);
    }

    /**
     * 缴纳保证金
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="order_id", type="int", required=true, description="订单ID")
     * @ApiParams (name="pay_type", type="string", required=true, description="支付方式:1=微信 2=支付宝")
     */
    public function pay_deposit()
    {
        $userId = $this->auth->id;
        $orderId = $this->request->post('order_id/d', 0);
        $payType = $this->request->post('pay_type/d', 1);

        $order = Order::where('id', $orderId)->where('agent_id', $userId)->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        if ($order['status'] != Order::STATUS_DEPOSIT) {
            $this->error('当前订单状态不允许缴纳保证金');
        }

        $deposit = Deposit::where('order_id', $orderId)->find();
        if (!$deposit) {
            $this->error('保证金记录不存在');
        }

        // TODO: 对接实际支付（微信/支付宝），此处模拟支付成功
        $isDebug = config('app_debug');

        Db::startTrans();
        try {
            $deposit->save([
                'pay_status' => Deposit::PAY_STATUS_PAID,
                'pay_type'   => $payType,
            ]);

            $order->save([
                'status' => Order::STATUS_DEPOSITED,
            ]);

            OrderLog::create([
                'order_id'      => $orderId,
                'from_status'   => Order::STATUS_DEPOSIT,
                'to_status'     => Order::STATUS_DEPOSITED,
                'description'   => '保证金缴纳成功，等待工厂支付佣金+服务费',
                'operator_type' => 'agent',
                'operator_id'   => $userId,
            ]);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('支付处理失败');
        }

        $this->success('保证金缴纳成功', [
            'order_id'  => $orderId,
            'mock_paid' => $isDebug ? true : false,
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
        $userId = $this->auth->id;
        $orderId = $this->request->get('order_id/d', 0);

        $order = Order::where('id', $orderId)->where('agent_id', $userId)->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        $deposit = Deposit::where('order_id', $orderId)->find();
        $contract = Contract::where('order_id', $orderId)->find();
        $logistics = Logistics::where('order_id', $orderId)->find();
        $factory = Factory::where('id', $order['factory_id'])->find();
        $paymentProof = PaymentProof::where('order_id', $orderId)->find();

        $this->success('查询成功', [
            'order_id'          => $order['id'],
            'orderNo'           => $order['order_sn'],
            'productName'       => $order['product_name'],
            'coverImage'        => $order['cover_image'],
            'factoryName'       => $factory ? $factory['company_name'] : '',
            'unitPrice'         => $this->formatMoney($order['unit_price']),
            'quantity'          => $order['quantity'],
            'totalAmount'       => $this->formatMoney($order['total_amount']),
            'createTime'        => date('Y-m-d H:i', $order['createtime']),
            'state'             => $order['status'],
            'status_text'       => $order['status_text'],
            'commission'        => $order['commission_rate'],
            'commissionAmount'  => $order['commission_amount'],
            'serviceFee'        => $order['service_fee'] ?: 0,
            'serviceFeeRate'    => $order['service_fee_rate'] ?: 1,
            'depositRate'       => $order['deposit_rate'],
            'factoryBonus'      => $order['factory_bonus'] ?: 0,
            'logisticsRebate'   => $order['logistics_rebate'] ?: 0,
            'contractUploadHours' => (int)$order['contract_upload_hours'],
            'executionHours'    => $order['execution_hours'] ?: 72,
            'paymentUrgeDeadline' => $order['payment_urge_deadline'] ?: 0,
            'contractDeadline'  => $order['contract_upload_deadline'] ?: 0,
            'contractRejectReason' => $contract ? ($contract['reject_reason'] ?: '') : '',
            'contractStatus'    => $contract ? $contract['status'] : 0,
            'deposit'           => $deposit,
            'contract'          => $contract,
            'logistics'         => $logistics,
            'payment_proof'     => $paymentProof ? [
                'file_urls'     => $paymentProof['file_urls'],
                'remark'        => $paymentProof['remark'] ?: '',
                'logistics_method' => $paymentProof['logistics_method'] ?: '',
                'status'        => $paymentProof['status'],
                'status_text'   => $paymentProof['status_text'],
                'reject_reason' => $paymentProof['reject_reason'] ?: '',
            ] : null,
            'bonus_rules'       => json_decode($order['bonus_rules'] ?: '[]', true) ?: [],
            'rebate_info'       => [
                'company_name' => $logistics ? ($logistics['company_name'] ?: '') : '',
                'rate'         => $order['logistics_rebate_rate'] ?: 0,
            ],
        ]);
    }

    /**
     * 合同状态
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="order_id", type="int", required=true, description="订单ID")
     */
    public function contract_status()
    {
        $userId = $this->auth->id;
        $orderId = $this->request->get('order_id/d', 0);

        $order = Order::where('id', $orderId)->where('agent_id', $userId)->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        $contract = Contract::where('order_id', $orderId)->find();

        $stage = 'upload';
        if ($order['status'] >= Order::STATUS_EXECUTING && $order['status'] <= Order::STATUS_SETTLED) {
            $stage = 'execution';
        } elseif ($order['status'] == Order::STATUS_OVERDUE) {
            $stage = 'expired';
        }

        $deadlineTimestamp = 0;
        if ($stage == 'upload' && $order['contract_upload_deadline']) {
            $deadlineTimestamp = $order['contract_upload_deadline'];
        } elseif ($stage == 'execution' && $order['payment_urge_deadline']) {
            $deadlineTimestamp = $order['payment_urge_deadline'];
        }

        $this->success('查询成功', [
            'stage'                 => $stage,
            'deadline_timestamp'    => $deadlineTimestamp,
            'contract_upload_hours' => (int)$order['contract_upload_hours'],
            'execution_hours'       => $order['execution_hours'] ?: 72,
            'contract_url'          => ($contract && $contract['file_url']) ? cdnurl($contract['file_url'], true) : '',
            'contract_name'         => $contract ? $contract['file_name'] : '',
            'contract_status'       => $contract ? $contract['status'] : 0,
            'reject_reason'         => $contract ? ($contract['reject_reason'] ?: '') : '',
        ]);
    }

    /**
     * 提交合同
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="order_id", type="int", required=true, description="订单ID")
     * @ApiParams (name="contract_url", type="string", required=true, description="合同文件URL")
     * @ApiParams (name="contract_name", type="string", required=false, description="合同文件名")
     */
    public function submit_contract()
    {
        $userId = $this->auth->id;
        $orderId = $this->request->post('order_id/d', 0);
        $contractUrl = $this->request->post('contract_url');
        $contractName = $this->request->post('contract_name', '买卖合同.pdf');

        if (!$orderId || !$contractUrl) {
            $this->error('参数不完整');
        }

        $order = Order::where('id', $orderId)->where('agent_id', $userId)->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        if ($order['status'] != Order::STATUS_CONTRACT) {
            $this->error('当前订单状态不允许上传合同');
        }

        Db::startTrans();
        try {
            // 更新合同记录
            $contract = Contract::where('order_id', $orderId)->find();
            if ($contract) {
                // 允许待上传或已驳回状态重新上传
                if (!in_array($contract['status'], [Contract::STATUS_PENDING, Contract::STATUS_REJECTED])) {
                    Db::rollback();
                    $this->error('当前合同状态不允许上传');
                }
                $contract->save([
                    'file_url'  => $contractUrl,
                    'file_name' => $contractName,
                    'status'    => Contract::STATUS_UPLOADED,
                ]);
            } else {
                Contract::create([
                    'order_id'  => $orderId,
                    'file_url'  => $contractUrl,
                    'file_name' => $contractName,
                    'status'    => Contract::STATUS_UPLOADED,
                ]);
            }

            // 订单保持在合同阶段，等待工厂审核

            OrderLog::create([
                'order_id'      => $orderId,
                'from_status'   => Order::STATUS_CONTRACT,
                'to_status'     => Order::STATUS_CONTRACT,
                'description'   => '合同上传成功，等待工厂审核',
                'operator_type' => 'agent',
                'operator_id'   => $userId,
            ]);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('提交失败，请重试');
        }

        $this->success('合同提交成功');
    }

    /**
     * 上传买家付款证明
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="order_id", type="int", required=true, description="订单ID")
     * @ApiParams (name="file_urls", type="string", required=true, description="付款证明图片URL(JSON数组)")
     * @ApiParams (name="remark", type="string", required=false, description="备注说明")
     */
    public function upload_payment_proof()
    {
        $userId = $this->auth->id;
        $orderId = $this->request->post('order_id/d', 0);
        $fileUrls = $this->request->post('file_urls', '', null);
        $remark = $this->request->post('remark', '');
        $logisticsMethod = $this->request->post('logistics_method', '');

        if (!$orderId || !$fileUrls) {
            $this->error('参数不完整');
        }

        if (!in_array($logisticsMethod, ['pickup', 'factory_ship'])) {
            $this->error('请选择物流方式');
        }

        // 解析 file_urls
        $urlArr = is_array($fileUrls) ? $fileUrls : (json_decode($fileUrls, true) ?: []);
        if (empty($urlArr)) {
            $this->error('请上传至少一个付款证明文件');
        }

        $order = Order::where('id', $orderId)->where('agent_id', $userId)->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        if ($order['status'] != Order::STATUS_EXECUTING) {
            $this->error('当前订单状态不允许上传付款证明');
        }

        // 检查催款截止时间
        if ($order['payment_urge_deadline'] > 0 && time() > $order['payment_urge_deadline']) {
            $this->error('付款期限已过，无法上传');
        }

        Db::startTrans();
        try {
            $proof = PaymentProof::where('order_id', $orderId)->find();
            if ($proof) {
                // 已有记录：允许待审核或已驳回状态重新上传
                if (!in_array($proof['status'], [PaymentProof::STATUS_PENDING, PaymentProof::STATUS_REJECTED])) {
                    Db::rollback();
                    $this->error('付款证明已审核通过，不能重复上传');
                }
                $proof->save([
                    'file_urls' => $urlArr,
                    'remark'    => $remark,
                    'logistics_method' => $logisticsMethod,
                    'status'    => PaymentProof::STATUS_PENDING,
                    'reject_reason' => '',
                ]);
            } else {
                PaymentProof::create([
                    'order_id'  => $orderId,
                    'user_id'   => $userId,
                    'file_urls' => json_encode($urlArr, JSON_UNESCAPED_UNICODE),
                    'remark'    => $remark,
                    'logistics_method' => $logisticsMethod,
                    'status'    => PaymentProof::STATUS_PENDING,
                ]);
            }

            OrderLog::create([
                'order_id'      => $orderId,
                'from_status'   => Order::STATUS_EXECUTING,
                'to_status'     => Order::STATUS_EXECUTING,
                'description'   => '居间人上传买家付款证明，等待工厂审核',
                'operator_type' => 'agent',
                'operator_id'   => $userId,
            ]);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('提交失败，请重试');
        }

        $this->success('付款证明提交成功，等待工厂审核');
    }

    /**
     * 物流信息
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="order_id", type="int", required=true, description="订单ID")
     */
    public function logistics()
    {
        $userId = $this->auth->id;
        $orderId = $this->request->get('order_id/d', 0);

        $order = Order::where('id', $orderId)->where('agent_id', $userId)->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        $logistics = Logistics::where('order_id', $orderId)->find();

        $data = [
            'logistics_type'   => $logistics ? ($logistics['logistics_type'] == 1 ? 'platform' : 'self') : 'platform',
            'logistics_info'   => null,
            'timeline'         => [],
            'self_pickup_info' => null,
            'checklist_files'  => [],
        ];

        if ($logistics) {
            $data['logistics_info'] = [
                'companyName'  => $logistics['company_name'] ?: '',
                'trackingNo'   => $logistics['tracking_no'] ?: '',
                'status'       => $this->getLogisticsStatusKey($logistics['status']),
                'rebateAmount' => $logistics['rebate_amount'] ?: 0,
            ];
            $timeline = $logistics['timeline_json'];
            $data['timeline'] = is_array($timeline) ? $timeline : (json_decode($timeline, true) ?: []);
            $data['self_pickup_info'] = [
                'status'        => $logistics['status'] >= 3 ? 'picked' : 'pending',
                'pickupTime'    => $logistics['pickup_time'] ? date('Y-m-d H:i', $logistics['pickup_time']) : '',
                'pickupNoteUrl' => $logistics['pickup_note_url'] ?: '',
            ];
            $checklistFiles = $logistics['checklist_files'];
            $data['checklist_files'] = is_array($checklistFiles) ? $checklistFiles : (json_decode($checklistFiles, true) ?: []);
        }

        $this->success('查询成功', $data);
    }

    /**
     * 催促工厂
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="order_id", type="int", required=true, description="订单ID")
     */
    public function urge_factory()
    {
        $userId = $this->auth->id;
        $orderId = $this->request->post('order_id/d', 0);

        $order = Order::where('id', $orderId)->where('agent_id', $userId)->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        // TODO: 发送催促通知给工厂（短信/推送）
        OrderLog::create([
            'order_id'      => $orderId,
            'from_status'   => $order['status'],
            'to_status'     => $order['status'],
            'description'   => '居间人催促工厂发货',
            'operator_type' => 'agent',
            'operator_id'   => $userId,
        ]);

        $this->success('催促通知已发送');
    }

    /**
     * 上传收发货清单
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="order_id", type="int", required=true, description="订单ID")
     * @ApiParams (name="file_url", type="string", required=true, description="文件URL")
     */
    public function upload_checklist()
    {
        $userId = $this->auth->id;
        $orderId = $this->request->post('order_id/d', 0);
        $fileUrl = $this->request->post('file_url');

        if (!$orderId || !$fileUrl) {
            $this->error('参数不完整');
        }

        $order = Order::where('id', $orderId)->where('agent_id', $userId)->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        $logistics = Logistics::where('order_id', $orderId)->find();
        if ($logistics) {
            $files = $logistics['checklist_files'];
            $files = is_array($files) ? $files : (json_decode($files, true) ?: []);
            $files[] = [
                'type' => 'image',
                'url'  => $fileUrl,
                'name' => '收发货清单',
                'time' => date('Y-m-d H:i'),
                'ext'  => 'JPG',
            ];
            $logistics->save(['checklist_files' => json_encode($files, JSON_UNESCAPED_UNICODE)]);
        }

        $this->success('上传成功', ['url' => $fileUrl]);
    }

    /**
     * 获取物流状态字符串
     */
    private function getLogisticsStatusKey($status)
    {
        $map = [
            0 => 'pending',
            1 => 'shipped',
            2 => 'transit',
            3 => 'signed',
        ];
        return $map[$status] ?? 'pending';
    }

    /**
     * 批量提交接单（多商品统一结算）
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function batch_submit()
    {
        $userId = $this->auth->id;
        // 获取items参数：兼容数组和JSON字符串两种传参
        $postData = $this->request->post();
        $itemsRaw = isset($postData['items']) ? $postData['items'] : null;
        if (is_array($itemsRaw)) {
            $items = $itemsRaw;
        } elseif (is_string($itemsRaw) && !empty($itemsRaw)) {
            $items = json_decode($itemsRaw, true);
        } else {
            $items = null;
        }
        if (!$items || !is_array($items) || count($items) < 1) {
            $this->error('请选择至少一个商品');
        }
        if (count($items) > 20) {
            $this->error('单次最多结算20个商品');
        }

        // 预加载所有产品并校验
        $productIds = array_column($items, 'productId');
        $products = Product::where('id', 'in', $productIds)
            ->where('status', Product::STATUS_ON)
            ->column('*', 'id');

        foreach ($items as $item) {
            $pid = $item['productId'] ?? 0;
            $qty = intval($item['quantity'] ?? 1);
            if (!$pid || $qty < 1) {
                $this->error('商品参数错误');
            }
            if (!isset($products[$pid])) {
                $this->error('商品(ID:' . $pid . ')不存在或已下架');
            }
            if ($products[$pid]['stock'] < $qty) {
                $this->error('商品"' . $products[$pid]['name'] . '"库存不足');
            }
        }

        // 计算每个商品的金额
        $orderDataList = [];
        $totalDeposit = '0.00';
        $commissionRate = 10; // 固定佣金比例：合同总价的10%

        foreach ($items as $item) {
            $pid = $item['productId'];
            $qty = intval($item['quantity']);
            $cartId = intval($item['cartId'] ?? 0);
            $product = $products[$pid];

            $totalAmount = bcmul($product['price'], $qty, 2);
            $commissionAmount = bcmul($totalAmount, bcdiv($commissionRate, 100, 4), 2);
            $depositRate = 1; // 固定保证金比例：佣金总额的1%
            $depositAmount = bcmul($commissionAmount, bcdiv($depositRate, 100, 4), 2);
            $serviceFeeRate = 1; // 1%
            $serviceFee = bcmul($totalAmount, bcdiv($serviceFeeRate, 100, 4), 2);
            $totalDeposit = bcadd($totalDeposit, $depositAmount, 2);

            $orderDataList[] = [
                'product'               => $product,
                'quantity'              => $qty,
                'cart_id'               => $cartId,
                'total_amount'          => $totalAmount,
                'commission_amount'     => $commissionAmount,
                'service_fee'           => $serviceFee,
                'service_fee_rate'      => $serviceFeeRate,
                'deposit_rate'          => $depositRate,
                'deposit_amount'        => $depositAmount,
            ];
        }

        Db::startTrans();
        try {
            $orderIds = [];
            $depositIds = [];
            $orderDetails = [];

            // 创建批次记录
            $batch = DepositBatch::create([
                'batch_no'     => DepositBatch::generateBatchNo(),
                'user_id'      => $userId,
                'total_amount' => $totalDeposit,
                'order_ids'    => [], // 先创建，后更新
                'pay_status'   => DepositBatch::PAY_STATUS_PENDING,
            ]);

            foreach ($orderDataList as $data) {
                $product = $data['product'];

                // 原子扣减库存，防止并发超卖
                $affected = Db::name('jj_product')
                    ->where('id', $product['id'])
                    ->where('stock', '>=', $data['quantity'])
                    ->setDec('stock', $data['quantity']);
                if (!$affected) {
                    throw new \Exception('商品"' . $product['name'] . '"库存不足');
                }

                $order = Order::create([
                    'order_sn'          => Order::generateOrderSn(),
                    'agent_id'          => $userId,
                    'product_id'        => $product['id'],
                    'factory_id'        => $product['factory_id'],
                    'product_name'      => $product['name'],
                    'cover_image'       => $product['cover_image'] ?? '',
                    'unit_price'        => $product['price'],
                    'total_amount'      => $data['total_amount'],
                    'quantity'          => $data['quantity'],
                    'commission_rate'   => $commissionRate,
                    'commission_amount' => $data['commission_amount'],
                    'service_fee'       => $data['service_fee'],
                    'service_fee_rate'  => $data['service_fee_rate'],
                    'deposit_rate'      => $data['deposit_rate'],
                    'deposit_amount'    => $data['deposit_amount'],
                    'buyer_company'     => '',
                    'buyer_address'     => '',
                    'buyer_contact'     => '',
                    'buyer_phone'       => '',
                    'buyer_credit_code' => '',
                    'buyer_tax_number'  => '',
                    'contract_upload_hours' => 0,
                    'execution_hours'   => 0,
                    'payment_urge_hours' => 0,
                    'status'            => Order::STATUS_DEPOSIT,
                ]);

                $orderIds[] = $order['id'];

                // 创建保证金记录（关联批次）
                $deposit = Deposit::create([
                    'order_id'   => $order['id'],
                    'batch_id'   => $batch['id'],
                    'user_id'    => $userId,
                    'amount'     => $data['deposit_amount'],
                    'pay_status' => Deposit::PAY_STATUS_PENDING,
                ]);

                $depositIds[] = $deposit['id'];

                // 订单日志
                OrderLog::create([
                    'order_id'      => $order['id'],
                    'from_status'   => 0,
                    'to_status'     => Order::STATUS_DEPOSIT,
                    'description'   => '居间人批量提交接单(数量:' . $data['quantity'] . ')',
                    'operator_type' => 'agent',
                    'operator_id'   => $userId,
                ]);

                // 清理清单记录
                if ($data['cart_id']) {
                    Cart::where('id', $data['cart_id'])->where('user_id', $userId)->delete();
                }

                $orderDetails[] = [
                    'order_id'          => $order['id'],
                    'order_sn'          => $order['order_sn'],
                    'product_name'      => $product['name'],
                    'cover_image'       => $product['cover_image'] ? cdnurl($product['cover_image'], true) : '',
                    'quantity'          => $data['quantity'],
                    'unit_price'        => $product['price'],
                    'total_amount'      => $data['total_amount'],
                    'commission_amount' => $data['commission_amount'],
                    'deposit_rate'      => $data['deposit_rate'],
                    'deposit_amount'    => $data['deposit_amount'],
                ];
            }

            // 回填批次的 order_ids
            $batch->save(['order_ids' => json_encode($orderIds)]);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage() ?: '提交失败，请重试');
        }

        $this->success('提交成功', [
            'batch_id'      => $batch['id'],
            'batch_no'      => $batch['batch_no'],
            'total_deposit'  => $totalDeposit,
            'order_count'    => count($orderIds),
            'order_ids'      => $orderIds,
            'orders'         => $orderDetails,
        ]);
    }

    /**
     * 批次保证金信息
     *
     * @ApiMethod (GET)
     * @ApiParams (name="batch_id", type="int", required=true, description="批次ID")
     */
    public function batch_deposit_info()
    {
        $userId = $this->auth->id;
        $batchId = $this->request->get('batch_id/d', 0);

        $batch = DepositBatch::where('id', $batchId)->where('user_id', $userId)->find();
        if (!$batch) {
            $this->error('批次不存在');
        }

        $deposits = Deposit::where('batch_id', $batchId)->select();
        $items = [];
        foreach ($deposits as $dep) {
            $order = Order::where('id', $dep['order_id'])->find();
            if ($order) {
                $items[] = [
                    'order_id'          => $order['id'],
                    'product_name'      => $order['product_name'],
                    'cover_image'       => $order['cover_image'],
                    'quantity'          => $order['quantity'],
                    'unit_price'        => $order['unit_price'],
                    'total_amount'      => $order['total_amount'],
                    'commission_rate'   => $order['commission_rate'],
                    'commission_amount' => $order['commission_amount'],
                    'deposit_rate'      => $order['deposit_rate'],
                    'deposit_amount'    => $dep['amount'],
                ];
            }
        }

        $this->success('查询成功', [
            'batch_id'      => $batch['id'],
            'batch_no'      => $batch['batch_no'],
            'total_amount'  => $batch['total_amount'],
            'pay_status'    => $batch['pay_status'],
            'items'         => $items,
        ]);
    }

    /**
     * 批量缴纳保证金（统一支付）
     *
     * @ApiMethod (POST)
     * @ApiParams (name="batch_id", type="int", required=true, description="批次ID")
     * @ApiParams (name="pay_type", type="int", required=true, description="支付方式:1=微信 2=支付宝")
     */
    public function pay_batch_deposit()
    {
        $userId = $this->auth->id;
        $batchId = $this->request->post('batch_id/d', 0);
        $payType = $this->request->post('pay_type/d', 1);

        $batch = DepositBatch::where('id', $batchId)->where('user_id', $userId)->find();
        if (!$batch) {
            $this->error('批次不存在');
        }
        if ($batch['pay_status'] != DepositBatch::PAY_STATUS_PENDING) {
            $this->error('该批次已支付');
        }

        $orderIds = $batch->getAttr('order_ids');
        if (!$orderIds || !is_array($orderIds)) {
            $orderIds = json_decode($batch->getData('order_ids'), true) ?: [];
        }
        if (empty($orderIds)) {
            $this->error('批次无关联订单');
        }

        // 校验所有订单状态
        $orders = Order::where('id', 'in', $orderIds)->where('agent_id', $userId)->select();
        if (count($orders) != count($orderIds)) {
            $this->error('部分订单不存在');
        }
        foreach ($orders as $order) {
            if ($order['status'] != Order::STATUS_DEPOSIT) {
                $this->error('订单' . $order['order_sn'] . '状态异常，不允许缴纳保证金');
            }
        }

        // TODO: 对接实际支付，此处模拟支付成功
        $isDebug = config('app_debug');

        Db::startTrans();
        try {
            // 更新批次状态
            $batch->save([
                'pay_status' => DepositBatch::PAY_STATUS_PAID,
                'pay_type'   => $payType,
            ]);

            // 更新每个子保证金记录
            Deposit::where('batch_id', $batchId)->update([
                'pay_status' => Deposit::PAY_STATUS_PAID,
                'pay_type'   => $payType,
                'updatetime' => time(),
            ]);

            // 更新每个订单状态（等待工厂支付佣金+服务费）
            foreach ($orders as $order) {
                $order->save(['status' => Order::STATUS_DEPOSITED]);

                OrderLog::create([
                    'order_id'      => $order['id'],
                    'from_status'   => Order::STATUS_DEPOSIT,
                    'to_status'     => Order::STATUS_DEPOSITED,
                    'description'   => '批量保证金缴纳成功，等待工厂支付(批次:' . $batch['batch_no'] . ')',
                    'operator_type' => 'agent',
                    'operator_id'   => $userId,
                ]);
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('支付处理失败');
        }

        $this->success('保证金缴纳成功', [
            'batch_id'   => $batchId,
            'order_count' => count($orderIds),
            'mock_paid'  => $isDebug ? true : false,
        ]);
    }
}

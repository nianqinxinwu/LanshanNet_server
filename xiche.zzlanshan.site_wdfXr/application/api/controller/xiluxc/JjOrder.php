<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\Order;
use app\common\model\jj\Product;
use app\common\model\jj\Deposit;
use app\common\model\jj\Contract;
use app\common\model\jj\Logistics;
use app\common\model\jj\OrderLog;
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

        $this->success('查询成功', $list);
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
        $companyName = $this->request->post('companyName');
        $address = $this->request->post('address');
        $contactName = $this->request->post('contactName');
        $contactPhone = $this->request->post('contactPhone');
        $creditCode = $this->request->post('creditCode');
        $taxNumber = $this->request->post('taxNumber', '');

        if (!$productId) {
            $this->error('缺少产品ID');
        }
        if (!$companyName || !$address || !$contactName || !$contactPhone || !$creditCode) {
            $this->error('请填写完整买家信息');
        }
        if (!Validate::regex($contactPhone, "^1\d{10}$")) {
            $this->error('请输入正确的手机号');
        }

        $product = Product::where('id', $productId)->where('status', Product::STATUS_ON)->find();
        if (!$product) {
            $this->error('产品不存在或已下架');
        }

        $commissionAmount = bcmul($product['price'], bcdiv($product['commission_rate'], 100, 4), 2);
        $depositRate = $product['deposit_rate'] ?: 10;
        $depositAmount = bcmul($commissionAmount, bcdiv($depositRate, 100, 4), 2);

        Db::startTrans();
        try {
            $order = Order::create([
                'order_sn'          => Order::generateOrderSn(),
                'agent_id'          => $userId,
                'product_id'        => $productId,
                'factory_id'        => $product['factory_id'],
                'product_name'      => $product['name'],
                'cover_image'       => $product->getData('cover_image'),
                'unit_price'        => $product['price'],
                'total_amount'      => $product['price'],
                'quantity'          => 1,
                'commission_rate'   => $product['commission_rate'],
                'commission_amount' => $commissionAmount,
                'deposit_rate'      => $depositRate,
                'deposit_amount'    => $depositAmount,
                'buyer_company'     => $companyName,
                'buyer_address'     => $address,
                'buyer_contact'     => $contactName,
                'buyer_phone'       => $contactPhone,
                'buyer_credit_code' => $creditCode,
                'buyer_tax_number'  => $taxNumber,
                'contract_upload_hours' => 24,
                'execution_hours'   => 72,
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
                'description'   => '居间人提交接单',
                'operator_type' => 'agent',
                'operator_id'   => $userId,
            ]);

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
            'contract_upload_hours' => 24,
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

        $this->success('查询成功', [
            'order_id'          => $order['id'],
            'productName'       => $order['product_name'],
            'coverImage'        => $order['cover_image'],
            'companyName'       => $order['buyer_company'],
            'commission'        => $order['commission_rate'],
            'commissionAmount'  => $order['commission_amount'],
            'depositRate'       => $order['deposit_rate'],
            'depositAmount'     => $deposit ? $deposit['amount'] : '0.00',
            'contractUploadHours' => $order['contract_upload_hours'] ?: 24,
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
                'status' => Order::STATUS_CONTRACT,
            ]);

            // 创建合同记录（待上传状态）
            $contractDeadline = time() + ($order['contract_upload_hours'] ?: 24) * 3600;
            Contract::create([
                'order_id'        => $orderId,
                'file_url'        => '',
                'file_name'       => '',
                'upload_deadline'  => $contractDeadline,
                'status'          => Contract::STATUS_PENDING,
            ]);

            OrderLog::create([
                'order_id'      => $orderId,
                'from_status'   => Order::STATUS_DEPOSIT,
                'to_status'     => Order::STATUS_CONTRACT,
                'description'   => '保证金缴纳成功',
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

        $this->success('查询成功', [
            'order_id'          => $order['id'],
            'orderNo'           => $order['order_sn'],
            'productName'       => $order['product_name'],
            'coverImage'        => $order['cover_image'],
            'companyName'       => $order['buyer_company'],
            'createTime'        => date('Y-m-d H:i', $order['createtime']),
            'state'             => $order['status'],
            'status_text'       => $order['status_text'],
            'commission'        => $order['commission_rate'],
            'commissionAmount'  => $order['commission_amount'],
            'depositRate'       => $order['deposit_rate'],
            'factoryBonus'      => $order['factory_bonus'] ?: 0,
            'logisticsRebate'   => $order['logistics_rebate'] ?: 0,
            'contractUploadHours' => $order['contract_upload_hours'] ?: 24,
            'executionHours'    => $order['execution_hours'] ?: 72,
            'deposit'           => $deposit,
            'contract'          => $contract,
            'logistics'         => $logistics,
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
        if ($contract) {
            if ($stage == 'upload' && $contract['upload_deadline']) {
                $deadlineTimestamp = $contract['upload_deadline'];
            }
        }

        $this->success('查询成功', [
            'stage'                 => $stage,
            'deadline_timestamp'    => $deadlineTimestamp,
            'contract_upload_hours' => $order['contract_upload_hours'] ?: 24,
            'execution_hours'       => $order['execution_hours'] ?: 72,
            'contract_url'          => $contract ? $contract['file_url'] : '',
            'contract_name'         => $contract ? $contract['file_name'] : '',
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

            $order->save([
                'status' => Order::STATUS_EXECUTING,
            ]);

            OrderLog::create([
                'order_id'      => $orderId,
                'from_status'   => Order::STATUS_CONTRACT,
                'to_status'     => Order::STATUS_EXECUTING,
                'description'   => '合同上传成功，进入履约期',
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
}

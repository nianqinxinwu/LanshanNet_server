<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\Bid;
use app\common\model\jj\BidQuote;
use app\common\model\jj\Factory;
use app\common\model\jj\Order;
use app\common\model\jj\Deposit;
use app\common\model\jj\OrderLog;
use think\Db;
use think\Validate;

/**
 * 居间人端 - 竞价模块
 */
class JjBid extends XiluxcApi
{
    protected $noNeedLogin = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 工厂列表（竞价选择）
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="province", type="string", required=false, description="省份筛选")
     * @ApiParams (name="industry", type="string", required=false, description="行业筛选")
     */
    public function factory_list()
    {
        $province = $this->request->get('province', '');
        $industry = $this->request->get('industry', '');

        $query = Factory::normal();

        if ($province) {
            $query->where('province', $province);
        }
        if ($industry) {
            $query->where('industry', $industry);
        }

        $list = $query->field('id, company_name as name, province, industry, fulfill_rate as fulfillRate, product_count as productCount')
            ->order('fulfill_rate', 'desc')
            ->select();

        $this->success('查询成功', ['list' => $list]);
    }

    /**
     * 发布竞标
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function publish()
    {
        $userId = $this->auth->id;
        $factoryIds = $this->request->post('factory_ids/a', []);
        $categoryName = $this->request->post('category_name');
        $quantity = $this->request->post('quantity/f', 0);
        $unit = $this->request->post('unit');
        $expectDelivery = $this->request->post('expect_delivery');
        $targetCommission = $this->request->post('target_commission/f', 0);
        $companyName = $this->request->post('company_name');
        $address = $this->request->post('address');
        $contactName = $this->request->post('contact_name');
        $contactPhone = $this->request->post('contact_phone');
        $creditCode = $this->request->post('credit_code');
        $taxNumber = $this->request->post('tax_number', '');

        if (empty($factoryIds)) {
            $this->error('请至少选择1家工厂');
        }
        if (count($factoryIds) > 5) {
            $this->error('最多选择5家工厂');
        }
        if (!$categoryName || !$quantity || !$unit || !$expectDelivery) {
            $this->error('请填写完整需求信息');
        }
        if (!$companyName || !$address || !$contactName || !$contactPhone || !$creditCode) {
            $this->error('请填写完整买家信息');
        }
        if (!Validate::regex($contactPhone, "^1\d{10}$")) {
            $this->error('请输入正确的手机号');
        }

        Db::startTrans();
        try {
            $bid = Bid::create([
                'bid_sn'            => Bid::generateBidSn(),
                'agent_id'          => $userId,
                'category_name'     => $categoryName,
                'quantity'          => $quantity,
                'unit'              => $unit,
                'expect_delivery'   => $expectDelivery,
                'target_commission' => $targetCommission,
                'buyer_company'     => $companyName,
                'buyer_address'     => $address,
                'buyer_contact'     => $contactName,
                'buyer_phone'       => $contactPhone,
                'buyer_credit_code' => $creditCode,
                'buyer_tax_number'  => $taxNumber,
                'factory_count'     => count($factoryIds),
                'expire_time'       => time() + 72 * 3600,
                'status'            => Bid::STATUS_BIDDING,
            ]);

            // 创建各工厂的报价记录（初始状态）
            foreach ($factoryIds as $factoryId) {
                BidQuote::create([
                    'bid_id'     => $bid['id'],
                    'factory_id' => $factoryId,
                    'status'     => 0, // 待报价
                ]);
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('发布失败，请重试');
        }

        $this->success('竞标发起成功', [
            'bid_id' => $bid['id'],
            'bid_sn' => $bid['bid_sn'],
        ]);
    }

    /**
     * 竞标列表
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="status", type="string", required=false, description="状态筛选:all|bidding|completed|expired")
     */
    public function list()
    {
        $userId = $this->auth->id;
        $status = $this->request->get('status', 'all');

        $query = Bid::where('agent_id', $userId);
        if ($status == 'bidding') {
            $query->where('status', Bid::STATUS_BIDDING);
        } elseif ($status == 'completed') {
            $query->where('status', Bid::STATUS_COMPLETED);
        } elseif ($status == 'expired') {
            $query->where('status', Bid::STATUS_EXPIRED);
        }

        $list = $query->with(['quotes' => function ($q) {
            $q->where('status', 1); // 已报价
        }])
            ->order('id', 'desc')
            ->select();

        // 统计信息
        $stats = [
            'bidding'   => Bid::where('agent_id', $userId)->where('status', Bid::STATUS_BIDDING)->count(),
            'completed' => Bid::where('agent_id', $userId)->where('status', Bid::STATUS_COMPLETED)->count(),
            'expired'   => Bid::where('agent_id', $userId)->where('status', Bid::STATUS_EXPIRED)->count(),
        ];

        $this->success('查询成功', [
            'list'  => $list,
            'stats' => $stats,
        ]);
    }

    /**
     * 竞标详情
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="bid_id", type="int", required=true, description="竞标ID")
     */
    public function detail()
    {
        $userId = $this->auth->id;
        $bidId = $this->request->get('bid_id/d', 0);

        $bid = Bid::where('id', $bidId)->where('agent_id', $userId)->find();
        if (!$bid) {
            $this->error('竞标不存在');
        }

        $quotes = BidQuote::where('bid_id', $bidId)
            ->with(['factory' => function ($q) {
                $q->withField(['id', 'company_name', 'fulfill_rate']);
            }])
            ->select();

        $quotedCount = 0;
        foreach ($quotes as $q) {
            if ($q['status'] == 1) {
                $quotedCount++;
            }
        }

        $remainTime = max(0, $bid['expire_time'] - time());

        $this->success('查询成功', [
            'id'               => $bid['id'],
            'bidSn'            => $bid['bid_sn'],
            'categoryName'     => $bid['category_name'],
            'quantity'          => $bid['quantity'],
            'unit'              => $bid['unit'],
            'expectDelivery'    => $bid['expect_delivery'],
            'targetCommission'  => $bid['target_commission'],
            'factoryCount'      => $bid['factory_count'],
            'quotedCount'       => $quotedCount,
            'status'            => $bid['status'],
            'status_text'       => $bid['status_text'],
            'remainTime'        => $remainTime,
            'createTime'        => date('Y-m-d H:i', $bid['createtime']),
            'buyerInfo'         => [
                'companyName'  => $bid['buyer_company'],
                'address'      => $bid['buyer_address'],
                'contactName'  => $bid['buyer_contact'],
                'contactPhone' => $bid['buyer_phone'],
            ],
            'quotes' => $quotes,
        ]);
    }

    /**
     * 选择工厂（从报价中选定）
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="bid_id", type="int", required=true, description="竞标ID")
     * @ApiParams (name="factory_id", type="int", required=true, description="工厂ID")
     */
    public function select_factory()
    {
        $userId = $this->auth->id;
        $bidId = $this->request->post('bid_id/d', 0);
        $factoryId = $this->request->post('factory_id/d', 0);

        $bid = Bid::where('id', $bidId)->where('agent_id', $userId)->find();
        if (!$bid) {
            $this->error('竞标不存在');
        }
        if ($bid['status'] != Bid::STATUS_BIDDING) {
            $this->error('当前竞标已结束');
        }

        $quote = BidQuote::where('bid_id', $bidId)
            ->where('factory_id', $factoryId)
            ->where('status', 1)
            ->find();
        if (!$quote) {
            $this->error('该工厂未报价');
        }

        Db::startTrans();
        try {
            // 标记竞标完成
            $bid->save(['status' => Bid::STATUS_COMPLETED]);

            // 标记该报价为选中
            $quote->save(['selected' => 1]);

            // 创建订单
            $commissionAmount = $quote['commission_amount'] ?: 0;
            $depositRate = 10;

            $order = Order::create([
                'order_sn'          => Order::generateOrderSn(),
                'agent_id'          => $userId,
                'factory_id'        => $factoryId,
                'bid_id'            => $bidId,
                'product_name'      => $bid['category_name'],
                'unit_price'        => $quote['contract_price'] ?: 0,
                'total_amount'      => bcmul($quote['contract_price'] ?: 0, $bid['quantity'], 2),
                'quantity'          => $bid['quantity'],
                'commission_rate'   => 0,
                'commission_amount' => $commissionAmount,
                'deposit_rate'      => $depositRate,
                'deposit_amount'    => bcmul($commissionAmount, bcdiv($depositRate, 100, 4), 2),
                'buyer_company'     => $bid['buyer_company'],
                'buyer_address'     => $bid['buyer_address'],
                'buyer_contact'     => $bid['buyer_contact'],
                'buyer_phone'       => $bid['buyer_phone'],
                'buyer_credit_code' => $bid['buyer_credit_code'],
                'buyer_tax_number'  => $bid['buyer_tax_number'],
                'contract_upload_hours' => 24,
                'execution_hours'   => 72,
                'status'            => Order::STATUS_DEPOSIT,
            ]);

            Deposit::create([
                'order_id'    => $order['id'],
                'user_id'     => $userId,
                'amount'      => bcmul($commissionAmount, bcdiv($depositRate, 100, 4), 2),
                'pay_status'  => Deposit::PAY_STATUS_PENDING,
            ]);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('操作失败，请重试');
        }

        $this->success('选择成功', [
            'order_id' => $order['id'],
        ]);
    }
}

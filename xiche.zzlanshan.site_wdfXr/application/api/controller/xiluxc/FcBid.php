<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\Factory;
use app\common\model\jj\Bid;
use app\common\model\jj\BidQuote;
use think\Db;

/**
 * 工厂端 - 竞标报价
 */
class FcBid extends XiluxcApi
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
     * 格式化金额：千分位 + 2位小数，空值返回 "0.00"
     */
    protected function formatMoney($val)
    {
        return number_format(floatval($val ?: 0), 2, '.', ',');
    }

    /**
     * 竞标邀请列表（工厂收到的竞标邀请）
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="status", type="string", required=false, description="筛选:all|pending|quoted|expired")
     * @ApiParams (name="page", type="int", required=false, description="页码")
     * @ApiParams (name="pagesize", type="int", required=false, description="每页数量")
     */
    public function invitation_list()
    {
        $factory = $this->getFactory();
        $status = $this->request->get('status', 'all');
        $page = $this->request->get('page/d', 1);
        $pagesize = $this->request->get('pagesize/d', 10);

        $query = BidQuote::where('factory_id', $factory['id'])
            ->with(['bid' => function ($q) {
                $q->withField(['id', 'bid_sn', 'agent_id', 'category_name', 'quantity', 'unit',
                    'expect_delivery', 'target_commission', 'factory_count', 'expire_time', 'status', 'createtime']);
            }]);

        if ($status === 'pending') {
            $query->where('status', BidQuote::STATUS_PENDING);
        } elseif ($status === 'quoted') {
            $query->where('status', BidQuote::STATUS_QUOTED);
        } elseif ($status === 'expired') {
            $query->where('status', BidQuote::STATUS_EXPIRED);
        }

        $list = $query->order('id', 'desc')
            ->paginate($pagesize, false, ['page' => $page]);

        // 转数组并格式化金额
        $listData = $list->toArray();
        foreach ($listData['data'] as &$item) {
            $item['remain_time'] = 0;
            if (isset($item['bid']) && $item['bid']) {
                $item['remain_time'] = max(0, ($item['bid']['expire_time'] ?? 0) - time());
                $item['bid']['target_commission'] = $this->formatMoney($item['bid']['target_commission'] ?? 0);
            }
            $item['contract_price'] = $this->formatMoney($item['contract_price'] ?? 0);
            $item['commission_amount'] = $this->formatMoney($item['commission_amount'] ?? 0);
        }
        unset($item);

        // 统计
        $stats = [
            'pending' => BidQuote::where('factory_id', $factory['id'])->where('status', BidQuote::STATUS_PENDING)->count(),
            'quoted'  => BidQuote::where('factory_id', $factory['id'])->where('status', BidQuote::STATUS_QUOTED)->count(),
        ];

        $this->success('查询成功', [
            'list'  => $listData,
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
        $factory = $this->getFactory();
        $bidId = $this->request->get('bid_id/d', 0);

        // 确认工厂在该竞标的邀请列表中
        $myQuote = BidQuote::where('bid_id', $bidId)->where('factory_id', $factory['id'])->find();
        if (!$myQuote) {
            $this->error('未找到该竞标邀请');
        }

        $bid = Bid::where('id', $bidId)->find();
        if (!$bid) {
            $this->error('竞标不存在');
        }

        $remainTime = max(0, $bid['expire_time'] - time());

        $this->success('查询成功', [
            'bid_id'            => $bid['id'],
            'bid_sn'            => $bid['bid_sn'],
            'category_name'     => $bid['category_name'],
            'quantity'          => $bid['quantity'],
            'unit'              => $bid['unit'],
            'expect_delivery'   => $bid['expect_delivery'],
            'target_commission' => $this->formatMoney($bid['target_commission']),
            'factory_count'     => $bid['factory_count'],
            'remain_time'       => $remainTime,
            'bid_status'        => $bid['status'],
            'bid_status_text'   => $bid['status_text'],
            'create_time'       => date('Y-m-d H:i', $bid['createtime']),
            'buyer_info'        => [
                'company_name'  => $bid['buyer_company'],
                'address'       => $bid['buyer_address'],
                'contact_name'  => $bid['buyer_contact'],
                'contact_phone' => $bid['buyer_phone'],
            ],
            'my_quote'          => [
                'id'                => $myQuote['id'],
                'status'            => $myQuote['status'],
                'status_text'       => $myQuote['status_text'],
                'contract_price'    => $this->formatMoney($myQuote['contract_price']),
                'delivery_date'     => $myQuote['delivery_date'] ?: '',
                'commission_amount' => $this->formatMoney($myQuote['commission_amount']),
                'remark'            => $myQuote['remark'] ?: '',
                'selected'          => $myQuote['selected'] ?: 0,
                'quote_time'        => $myQuote['updatetime'] ? date('Y-m-d H:i', $myQuote['updatetime']) : '',
            ],
        ]);
    }

    /**
     * 提交报价
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="bid_id", type="int", required=true, description="竞标ID")
     * @ApiParams (name="contract_price", type="float", required=true, description="合同价格")
     * @ApiParams (name="delivery_date", type="string", required=true, description="交货日期(Y-m-d)")
     * @ApiParams (name="remark", type="string", required=false, description="备注")
     */
    public function submit_quote()
    {
        $factory = $this->getFactory();
        $bidId = $this->request->post('bid_id/d', 0);
        $contractPrice = $this->request->post('contract_price/f', 0);
        $deliveryDate = $this->request->post('delivery_date');
        $remark = $this->request->post('remark', '');

        if ($contractPrice <= 0) {
            $this->error('合同价格必须大于0');
        }
        if (!$deliveryDate) {
            $this->error('请填写交货日期');
        }

        $myQuote = BidQuote::where('bid_id', $bidId)->where('factory_id', $factory['id'])->find();
        if (!$myQuote) {
            $this->error('未找到该竞标邀请');
        }
        if ($myQuote['status'] != BidQuote::STATUS_PENDING) {
            $this->error('已提交过报价');
        }

        $bid = Bid::where('id', $bidId)->find();
        if (!$bid || $bid['status'] != Bid::STATUS_BIDDING) {
            $this->error('竞标已结束或不存在');
        }
        if (time() > $bid['expire_time']) {
            $this->error('竞标已过期');
        }

        // 自动计算佣金总额
        $commissionAmount = bcmul($contractPrice, $bid['quantity'], 2);

        $myQuote->save([
            'contract_price'    => $contractPrice,
            'delivery_date'     => $deliveryDate,
            'commission_amount' => $commissionAmount,
            'remark'            => $remark,
            'status'            => BidQuote::STATUS_QUOTED,
        ]);

        $this->success('报价提交成功', [
            'commission_amount' => $this->formatMoney($commissionAmount),
        ]);
    }

    /**
     * 修改报价（72小时内可修改一次）
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="bid_id", type="int", required=true, description="竞标ID")
     * @ApiParams (name="contract_price", type="float", required=true, description="合同价格")
     * @ApiParams (name="delivery_date", type="string", required=true, description="交货日期(Y-m-d)")
     * @ApiParams (name="remark", type="string", required=false, description="备注")
     */
    public function update_quote()
    {
        $factory = $this->getFactory();
        $bidId = $this->request->post('bid_id/d', 0);
        $contractPrice = $this->request->post('contract_price/f', 0);
        $deliveryDate = $this->request->post('delivery_date');
        $remark = $this->request->post('remark', '');

        if ($contractPrice <= 0) {
            $this->error('合同价格必须大于0');
        }

        $myQuote = BidQuote::where('bid_id', $bidId)->where('factory_id', $factory['id'])->find();
        if (!$myQuote) {
            $this->error('未找到该竞标邀请');
        }
        if ($myQuote['status'] != BidQuote::STATUS_QUOTED) {
            $this->error('尚未提交报价，无法修改');
        }

        $bid = Bid::where('id', $bidId)->find();
        if (!$bid || $bid['status'] != Bid::STATUS_BIDDING) {
            $this->error('竞标已结束');
        }

        // 72小时内只能修改一次
        if ($myQuote['modify_count'] >= 1) {
            $this->error('报价仅可修改一次');
        }
        $quoteTime = $myQuote['updatetime'] ?: $myQuote['createtime'];
        if (time() - $quoteTime > 72 * 3600) {
            $this->error('已超过72小时修改期限');
        }

        $commissionAmount = bcmul($contractPrice, $bid['quantity'], 2);

        $myQuote->save([
            'contract_price'    => $contractPrice,
            'delivery_date'     => $deliveryDate ?: $myQuote['delivery_date'],
            'commission_amount' => $commissionAmount,
            'remark'            => $remark,
            'modify_count'      => ($myQuote['modify_count'] ?: 0) + 1,
        ]);

        $this->success('报价修改成功');
    }
}

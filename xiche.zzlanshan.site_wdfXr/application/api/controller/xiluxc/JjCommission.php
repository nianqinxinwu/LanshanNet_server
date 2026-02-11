<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\Commission;

/**
 * 居间人端 - 佣金管理
 */
class JjCommission extends XiluxcApi
{
    protected $noNeedLogin = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 佣金汇总
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function summary()
    {
        $userId = $this->auth->id;

        $pending = Commission::where('agent_id', $userId)
            ->where('status', Commission::STATUS_PENDING)
            ->sum('total_amount');

        $settled = Commission::where('agent_id', $userId)
            ->where('status', Commission::STATUS_SETTLED)
            ->sum('total_amount');

        $this->success('查询成功', [
            'pending' => $pending ?: '0.00',
            'settled' => $settled ?: '0.00',
            'total'   => bcadd($pending ?: 0, $settled ?: 0, 2),
        ]);
    }

    /**
     * 佣金列表
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="status", type="string", required=false, description="状态筛选:pending|settled")
     * @ApiParams (name="page", type="int", required=false, description="页码")
     * @ApiParams (name="pagesize", type="int", required=false, description="每页数量")
     */
    public function list()
    {
        $userId = $this->auth->id;
        $status = $this->request->get('status', '');
        $page = $this->request->get('page/d', 1);
        $pagesize = $this->request->get('pagesize/d', 10);

        $query = Commission::where('agent_id', $userId);

        if ($status == 'pending') {
            $query->where('status', Commission::STATUS_PENDING);
        } elseif ($status == 'settled') {
            $query->where('status', Commission::STATUS_SETTLED);
        }

        $list = $query->order('id', 'desc')
            ->paginate($pagesize, false, ['page' => $page])
            ->each(function ($item) {
                $item->append(['status_text']);
                $item->orderNo = $item['order_no'];
                $item->productName = $item['product_name'];
                $item->companyName = $item['company_name'];
                $item->rate = 0; // 佣金比例需要从订单获取
                $item->amount = $item['total_amount'];
                $item->baseCommission = $item['base_commission'];
                $item->factoryBonus = $item['factory_bonus'];
                $item->logisticsRebate = $item['logistics_rebate'];
                $item->pkBonus = $item['pk_bonus'];
                $item->redPacket = $item['red_packet'];
                $item->settledTime = $item['settled_time'] ? date('Y-m-d H:i', $item['settled_time']) : '';
                $item->showDetail = false;
            });

        $this->success('查询成功', ['list' => $list]);
    }
}

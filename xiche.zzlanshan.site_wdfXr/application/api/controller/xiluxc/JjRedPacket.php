<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\RedPacket;
use app\common\model\jj\Deposit;
use think\Db;

/**
 * 居间人端 - 红包管理
 */
class JjRedPacket extends XiluxcApi
{
    protected $noNeedLogin = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 红包汇总
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function summary()
    {
        $userId = $this->auth->id;

        $totalAmount = RedPacket::where('agent_id', $userId)->sum('amount');
        $totalOrders = RedPacket::where('agent_id', $userId)->count();

        // 本月红包
        $monthStart = strtotime(date('Y-m-01'));
        $monthAmount = RedPacket::where('agent_id', $userId)
            ->where('createtime', '>=', $monthStart)
            ->sum('amount');

        $perOrderAmount = $totalOrders > 0 ? bcdiv($totalAmount, $totalOrders, 2) : '0.00';

        $this->success('查询成功', [
            'totalAmount'    => $totalAmount ?: '0.00',
            'totalOrders'    => $totalOrders,
            'perOrderAmount' => $perOrderAmount,
            'monthAmount'    => $monthAmount ?: '0.00',
        ]);
    }

    /**
     * 红包列表
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="status", type="string", required=false, description="状态筛选:unclaimed|claimed")
     * @ApiParams (name="page", type="int", required=false, description="页码")
     * @ApiParams (name="pagesize", type="int", required=false, description="每页数量")
     */
    public function list()
    {
        $userId = $this->auth->id;
        $status = $this->request->get('status', '');
        $page = $this->request->get('page/d', 1);
        $pagesize = $this->request->get('pagesize/d', 10);

        $query = RedPacket::where('agent_id', $userId);

        if ($status == 'unclaimed') {
            $query->where('status', 0);
        } elseif ($status == 'claimed') {
            $query->where('status', 1);
        }

        $list = $query->order('id', 'desc')
            ->paginate($pagesize, false, ['page' => $page]);

        $this->success('查询成功', ['list' => $list]);
    }

    /**
     * 红包抵扣保证金
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="deposit_id", type="int", required=true, description="保证金记录ID")
     * @ApiParams (name="red_packet_ids", type="array", required=true, description="使用的红包ID列表")
     */
    public function redeem()
    {
        $userId = $this->auth->id;
        $depositId = $this->request->post('deposit_id/d', 0);
        $packetIds = $this->request->post('red_packet_ids/a', []);

        if (!$depositId || empty($packetIds)) {
            $this->error('参数不完整');
        }

        $deposit = Deposit::where('id', $depositId)
            ->where('user_id', $userId)
            ->where('pay_status', Deposit::PAY_STATUS_PENDING)
            ->find();
        if (!$deposit) {
            $this->error('保证金记录不存在或已支付');
        }

        $packets = RedPacket::where('agent_id', $userId)
            ->where('id', 'in', $packetIds)
            ->where('status', RedPacket::STATUS_RECEIVED)
            ->where('used', 0)
            ->select();

        if (count($packets) === 0) {
            $this->error('无可用红包');
        }

        $deductedAmount = '0.00';
        Db::startTrans();
        try {
            foreach ($packets as $packet) {
                $deductedAmount = bcadd($deductedAmount, $packet['amount'], 2);
                $packet->used = 1;
                $packet->used_deposit_id = $depositId;
                $packet->updatetime = time();
                $packet->save();
            }
            if (bccomp($deductedAmount, $deposit['amount'], 2) > 0) {
                $deductedAmount = $deposit['amount'];
            }
            $remainingAmount = bcsub($deposit['amount'], $deductedAmount, 2);
            $deposit->red_packet_deduct = $deductedAmount;
            $deposit->save();

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('操作失败：' . $e->getMessage());
        }

        $this->success('红包抵扣成功', [
            'deducted_amount'  => $deductedAmount,
            'remaining_amount' => $remainingAmount,
        ]);
    }
}

<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\AgentProfile;
use app\common\model\jj\Commission;
use app\common\model\jj\Order;
use app\common\model\jj\PkPool;
use app\common\model\jj\PkRank;

/**
 * 居间人端 - 首页与个人中心
 */
class JjAgent extends XiluxcApi
{
    protected $noNeedLogin = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 首页仪表盘数据
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function dashboard()
    {
        $userId = $this->auth->id;

        // 居间人扩展信息
        $profile = AgentProfile::where('user_id', $userId)->find();
        if (!$profile) {
            $this->error('请先完成居间人认证');
        }

        // 收入统计
        $income = [
            'pending_revenue' => $profile['pending_revenue'],
            'settled_revenue' => $profile['settled_revenue'],
            'total_revenue'   => $profile['total_revenue'],
        ];

        // 雷达图六维数据
        $hexagonData = $profile['hexagon_data'] ?: [
            'dealAbility'    => 0,
            'creditScore'    => 0,
            'activeLevel'    => 0,
            'fulfillRate'    => 0,
            'teamScale'      => 0,
            'growthRate'     => 0,
        ];

        // 待办事项统计
        $todo = [
            'pending_deposit'  => Order::where('agent_id', $userId)->where('status', Order::STATUS_DEPOSIT)->count(),
            'pending_contract' => Order::where('agent_id', $userId)->where('status', Order::STATUS_CONTRACT)->count(),
            'executing'        => Order::where('agent_id', $userId)->where('status', Order::STATUS_EXECUTING)->count(),
            'pending_settle'   => Order::where('agent_id', $userId)->where('status', Order::STATUS_SETTLING)->count(),
        ];

        // 当前 PK 奖池信息
        $currentPool = PkPool::where('status', PkPool::STATUS_ACTIVE)
            ->order('id', 'desc')
            ->find();

        $pkInfo = null;
        if ($currentPool) {
            $myRank = PkRank::where('pool_id', $currentPool['id'])
                ->where('agent_id', $userId)
                ->find();
            $pkInfo = [
                'total_amount' => $currentPool['total_amount'],
                'start_date'   => $currentPool['start_date'],
                'end_date'     => $currentPool['end_date'],
                'my_rank'      => $myRank ? $myRank['rank'] : 0,
                'my_revenue'   => $myRank ? $myRank['revenue_amount'] : '0.00',
                'my_prize'     => $myRank ? $myRank['prize_amount'] : '0.00',
            ];
        }

        $data = [
            'profile'     => [
                'real_name'    => $profile['real_name'],
                'credit_score' => $profile['credit_score'],
                'agent_level'  => $profile['agent_level'],
                'invite_code'  => $profile['invite_code'],
                'avatar'       => cdnurl($this->auth->getUser()['avatar'], true),
            ],
            'income'      => $income,
            'hexagonData' => $hexagonData,
            'todo'        => $todo,
            'pkPool'      => $pkInfo,
        ];

        $this->success('查询成功', $data);
    }

    /**
     * 获取居间人扩展信息
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function profile()
    {
        $userId = $this->auth->id;
        $user = $this->auth->getUser();

        $profile = AgentProfile::where('user_id', $userId)->find();

        $data = [
            'nickname'       => $user['nickname'],
            'avatar'         => cdnurl($user['avatar'], true),
            'mobile'         => substr_replace($user['mobile'], '****', 3, 4),
            'roleType'       => $user['roleType'],
            'agentType'      => $user['agentType'],
            'isIntermediary' => $user['isIntermediary'],
            'agent_profile'  => $profile,
        ];

        $this->success('查询成功', $data);
    }
}

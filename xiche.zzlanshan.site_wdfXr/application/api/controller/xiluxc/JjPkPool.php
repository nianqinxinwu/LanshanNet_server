<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\PkPool;
use app\common\model\jj\PkRank;

/**
 * 居间人端 - PK奖池
 */
class JjPkPool extends XiluxcApi
{
    protected $noNeedLogin = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * PK奖池信息
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function info()
    {
        $userId = $this->auth->id;

        // 当前进行中的奖池
        $currentPool = PkPool::where('status', PkPool::STATUS_ACTIVE)
            ->order('id', 'desc')
            ->find();

        $poolInfo = null;
        $myRank = null;
        $rankList = [];

        if ($currentPool) {
            $poolInfo = [
                'id'           => $currentPool['id'],
                'total_amount' => $currentPool['total_amount'],
                'start_date'   => $currentPool['start_date'],
                'end_date'     => $currentPool['end_date'],
            ];

            // 我的排名
            $myRankRecord = PkRank::where('pool_id', $currentPool['id'])
                ->where('agent_id', $userId)
                ->find();
            if ($myRankRecord) {
                $myRank = [
                    'rank'           => $myRankRecord['rank'],
                    'revenue_amount' => $myRankRecord['revenue_amount'],
                    'prize_amount'   => $myRankRecord['prize_amount'],
                ];
            }

            // Top10 排名 (使用Db查询避免模型关联的PHP8.1兼容性问题)
            $rankList = \think\Db::name('jj_pk_rank')
                ->alias('r')
                ->join('fa_user u', 'r.agent_id = u.id', 'left')
                ->where('r.pool_id', $currentPool['id'])
                ->where('r.rank', '<=', 10)
                ->order('r.rank', 'asc')
                ->field('r.*, u.nickname, u.avatar')
                ->select();

            // 处理不存在的用户
            foreach ($rankList as &$rank) {
                if (empty($rank['nickname'])) {
                    $rank['nickname'] = '用户' . $rank['agent_id'];
                    $rank['avatar'] = '';
                }
            }
            unset($rank);
        }

        // 历史奖池
        $historyPools = PkPool::where('status', PkPool::STATUS_SETTLED)
            ->order('id', 'desc')
            ->limit(5)
            ->select();

        $this->success('查询成功', [
            'pool_info'     => $poolInfo,
            'my_rank'       => $myRank,
            'rank_list'     => $rankList,
            'history_pools' => $historyPools,
        ]);
    }
}

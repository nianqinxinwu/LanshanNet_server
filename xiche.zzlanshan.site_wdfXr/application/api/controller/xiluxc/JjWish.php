<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use think\Db;

/**
 * 居间人端 - 心愿目标
 */
class JjWish extends XiluxcApi
{
    protected $noNeedLogin = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取当前心愿目标
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function current()
    {
        $userId = $this->auth->id;

        // 从 agent_profile 的扩展字段中获取心愿目标
        $profile = \app\common\model\jj\AgentProfile::where('user_id', $userId)->find();
        if (!$profile) {
            $this->error('请先完成居间人认证');
        }

        // 当前目标（存储在独立表或profile扩展字段中，此处简化为profile字段）
        $currentGoal = Db::name('jj_wish_goal')
            ->where('agent_id', $userId)
            ->where('status', 0) // 进行中
            ->order('id', 'desc')
            ->find();

        if ($currentGoal) {
            // 计算当前进度
            $currentAmount = $profile['total_revenue'] ?: 0;
            $this->success('查询成功', [
                'id'         => $currentGoal['id'],
                'type'       => $currentGoal['type'],
                'target'     => $currentGoal['target_amount'],
                'current'    => $currentAmount,
                'rewardDesc' => $currentGoal['reward_desc'],
                'claimed'    => $currentGoal['claimed'],
            ]);
        } else {
            $this->success('查询成功', null);
        }
    }

    /**
     * 设置心愿目标
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="type", type="string", required=true, description="目标类型:income|order")
     * @ApiParams (name="target", type="float", required=true, description="目标金额/数量")
     */
    public function set_goal()
    {
        $userId = $this->auth->id;
        $type = $this->request->post('type', 'income');
        $target = $this->request->post('target/f', 0);

        if ($target <= 0) {
            $this->error('请输入有效的目标值');
        }

        // 将现有进行中的目标标记为已完成
        Db::name('jj_wish_goal')
            ->where('agent_id', $userId)
            ->where('status', 0)
            ->update(['status' => 2]); // 2=已替换

        $rewardDesc = $type == 'income'
            ? '完成收入目标 ¥' . number_format($target, 2) . ' 可获得平台奖励'
            : '完成 ' . intval($target) . ' 单订单可获得平台奖励';

        $id = Db::name('jj_wish_goal')->insertGetId([
            'agent_id'      => $userId,
            'type'          => $type,
            'target_amount' => $target,
            'reward_desc'   => $rewardDesc,
            'status'        => 0,
            'claimed'       => 0,
            'createtime'    => time(),
            'updatetime'    => time(),
        ]);

        $this->success('目标设置成功', ['id' => $id]);
    }

    /**
     * 领取奖励
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="goal_id", type="int", required=true, description="目标ID")
     */
    public function claim_reward()
    {
        $userId = $this->auth->id;
        $goalId = $this->request->post('goal_id/d', 0);

        $goal = Db::name('jj_wish_goal')
            ->where('id', $goalId)
            ->where('agent_id', $userId)
            ->find();

        if (!$goal) {
            $this->error('目标不存在');
        }
        if ($goal['claimed']) {
            $this->error('奖励已领取');
        }

        // 检查是否达标
        $profile = \app\common\model\jj\AgentProfile::where('user_id', $userId)->find();
        $currentAmount = $profile ? ($profile['total_revenue'] ?: 0) : 0;

        if ($currentAmount < $goal['target_amount']) {
            $this->error('目标尚未达成');
        }

        Db::name('jj_wish_goal')
            ->where('id', $goalId)
            ->update([
                'claimed'    => 1,
                'status'     => 1, // 已完成
                'updatetime' => time(),
            ]);

        $this->success('奖励领取成功');
    }

    /**
     * 历史目标列表
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="page", type="int", required=false, description="页码")
     * @ApiParams (name="pagesize", type="int", required=false, description="每页数量")
     */
    public function history()
    {
        $userId = $this->auth->id;
        $page = $this->request->get('page/d', 1);
        $pagesize = $this->request->get('pagesize/d', 10);

        $list = Db::name('jj_wish_goal')
            ->where('agent_id', $userId)
            ->where('status', '<>', 0)
            ->order('id', 'desc')
            ->paginate($pagesize, false, ['page' => $page]);

        $data = $list->toArray();
        foreach ($data['data'] as &$item) {
            $item['achieved'] = ($item['status'] == 1);
            $item['period'] = date('Y年n月', $item['createtime']);
        }

        $this->success('查询成功', $data);
    }
}

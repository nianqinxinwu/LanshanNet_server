<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\Invite;
use app\common\model\jj\AgentProfile;

/**
 * 居间人端 - 分销邀请
 */
class JjDistribution extends XiluxcApi
{
    protected $noNeedLogin = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 分销概览
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function index()
    {
        $userId = $this->auth->id;

        $profile = AgentProfile::where('user_id', $userId)->find();

        // 邀请统计
        $inviteCount = Invite::where('agent_id', $userId)->count();
        $teamRevenue = $profile ? $profile['total_revenue'] : '0.00';

        // 团队成员列表
        $teamList = Invite::where('agent_id', $userId)
            ->with(['inviteUser' => function ($q) {
                $q->withField(['id', 'nickname', 'avatar', 'mobile']);
            }])
            ->order('id', 'desc')
            ->limit(20)
            ->select();

        $this->success('查询成功', [
            'invite_code'  => $profile ? $profile['invite_code'] : '',
            'invite_count' => $inviteCount,
            'team_revenue' => $teamRevenue ?: '0.00',
            'team_list'    => $teamList,
        ]);
    }

    /**
     * 分销海报数据
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function poster()
    {
        $userId = $this->auth->id;

        $profile = AgentProfile::where('user_id', $userId)->find();
        if (!$profile || !$profile['invite_code']) {
            $this->error('请先完成居间人认证');
        }

        $user = $this->auth->getUser();
        $inviteCode = $profile['invite_code'];
        $inviteUrl = $this->request->domain() . '/pages/login/login?invite_code=' . $inviteCode;

        $this->success('查询成功', [
            'invite_code' => $inviteCode,
            'invite_url'  => $inviteUrl,
            'nickname'    => $user['nickname'],
            'avatar'      => cdnurl($user['avatar'], true),
        ]);
    }
}

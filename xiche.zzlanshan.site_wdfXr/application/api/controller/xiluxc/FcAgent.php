<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\Factory;
use app\common\model\jj\AgentProfile;

/**
 * 工厂端 - 居间人筛选
 */
class FcAgent extends XiluxcApi
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
     * 居间人列表（工厂筛选居间人）
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="min_score", type="int", required=false, description="最低身价评分")
     * @ApiParams (name="keyword", type="string", required=false, description="搜索关键词")
     * @ApiParams (name="sort", type="string", required=false, description="排序:score|deals|rate")
     * @ApiParams (name="page", type="int", required=false, description="页码")
     * @ApiParams (name="pagesize", type="int", required=false, description="每页数量")
     */
    public function list()
    {
        $this->getFactory(); // 校验工厂身份

        $minScore = $this->request->get('min_score/d', 0);
        $keyword = $this->request->get('keyword', '');
        $sort = $this->request->get('sort', 'score');
        $page = $this->request->get('page/d', 1);
        $pagesize = $this->request->get('pagesize/d', 10);

        $tableName = (new AgentProfile)->getTable();
        $query = AgentProfile::where($tableName . '.status', AgentProfile::STATUS_NORMAL)
            ->with(['user' => function ($q) {
                $q->withField(['id', 'nickname', 'avatar']);
            }]);

        if ($minScore > 0) {
            $query->where($tableName . '.credit_score', '>=', $minScore);
        }
        if ($keyword) {
            $query->where($tableName . '.real_name', 'like', '%' . $keyword . '%');
        }

        // 排序
        switch ($sort) {
            case 'deals':
                $query->order($tableName . '.total_deals', 'desc');
                break;
            case 'rate':
                $query->order($tableName . '.fulfill_rate', 'desc');
                break;
            default:
                $query->order($tableName . '.credit_score', 'desc');
        }

        $list = $query->field([
            $tableName . '.id', $tableName . '.user_id', 'real_name', 'agent_type', 'agent_level',
            'credit_score', 'total_deals', 'total_revenue',
            'fulfill_rate', 'hexagon_data', $tableName . '.createtime'
        ])->paginate($pagesize, false, ['page' => $page]);

        // 格式化字段
        $listData = $list->toArray();
        foreach ($listData['data'] as &$item) {
            $item['total_revenue'] = $this->formatMoney($item['total_revenue'] ?? 0);
            // 补全头像URL
            if (isset($item['user']['avatar']) && $item['user']['avatar']) {
                $item['user']['avatar'] = cdnurl($item['user']['avatar'], true);
            }
        }
        unset($item);

        $this->success('查询成功', $listData);
    }

    /**
     * 居间人详情
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="agent_id", type="int", required=true, description="居间人profile ID")
     */
    public function detail()
    {
        $this->getFactory();

        $agentId = $this->request->get('agent_id/d', 0);

        $tableName = (new AgentProfile)->getTable();
        $profile = AgentProfile::where($tableName . '.id', $agentId)
            ->with(['user' => function ($q) {
                $q->withField(['id', 'nickname', 'avatar']);
            }])
            ->find();

        if (!$profile) {
            $this->error('居间人不存在');
        }

        $this->success('查询成功', [
            'id'              => $profile['id'],
            'user_id'         => $profile['user_id'],
            'nickname'        => $profile['user'] ? $profile['user']['nickname'] : '',
            'avatar'          => $profile['user'] ? cdnurl($profile['user']['avatar'], true) : '',
            'real_name'       => $profile['real_name'],
            'agent_type'      => $profile['agent_type'],
            'agent_level'     => $profile['agent_level'],
            'credit_score'    => $profile['credit_score'],
            'total_deals'     => $profile['total_deals'],
            'total_revenue'   => $this->formatMoney($profile['total_revenue']),
            'fulfill_rate'    => $profile['fulfill_rate'],
            'hexagon_data'    => $profile['hexagon_data'],
            'join_time'       => date('Y-m-d', $profile['createtime']),
        ]);
    }
}

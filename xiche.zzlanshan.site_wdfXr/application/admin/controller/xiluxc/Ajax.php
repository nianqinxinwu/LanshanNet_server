<?php

namespace app\admin\controller\xiluxc;

use app\common\controller\Backend;
use app\common\model\xiluxc\current\Area;

use think\Db;


/**
 * Ajax异步请求接口
 * @internal
 */
class Ajax extends Backend
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];
    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();

        //设置过滤方法
        $this->request->filter(['trim', 'strip_tags', 'htmlspecialchars']);
    }

    /**
     * 读取省市区数据,联动列表
     */
    public function area()
    {
        $params = $this->request->get("row/a");
        if (!empty($params)) {
            $province = isset($params['province']) ? $params['province'] : null;
            $city = isset($params['city']) ? $params['city'] : null;
        } else {
            $province = $this->request->get('province');
            $city = $this->request->get('city');
        }
        $where = ['pid' => 0, 'level' => 1];
        $provincelist = null;
        if ($province !== null) {
            $where['pid'] = $province;
            $where['level'] = 2;
            if ($city !== null) {
                $where['pid'] = $city;
                $where['level'] = 3;
            }
        }
        $provincelist = Area::where($where)->field('id as value,name')->select();
        $this->success('', '', $provincelist);
    }


    /**
     * 读取省市区数据,联动列表
     */
    public function areas()
    {
        $pid = $this->request->param('pid', 0);
        $list = Area::where('pid', $pid)->field('id as value,name')->select();
        $this->success('', null, $list);
    }

}

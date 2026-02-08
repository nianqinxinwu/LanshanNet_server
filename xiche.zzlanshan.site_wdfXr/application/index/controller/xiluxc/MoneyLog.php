<?php

namespace app\index\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcFront;

/**
 * 会员余额变动管理
 *
 * @icon fa fa-circle-o
 */
class MoneyLog extends XiluxcFront
{
    protected $relationSearch = true;
    protected $searchFields = "shop.name";
    /**
     * MoneyLog模型对象
     * @var \app\common\model\xiluxc\MoneyLog
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\xiluxc\MoneyLog;
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("statusList", $this->model->getStatusList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * @return string|\think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        if($this->brand){
            $where2['shop.brand_id'] = $this->brand->user_id;
        }else{
            $where2['shop.id'] = $this->shop->id;
        }
        $list = $this->model
            ->with(['shop'=>function($query){
                $query->withField(["id","name"]);
            }])
            ->where($where)
            ->where($where2)
            ->where($this->model->getTable().".type",\app\common\model\xiluxc\MoneyLog::TYPE_SHOP)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }


}

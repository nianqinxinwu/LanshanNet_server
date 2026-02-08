<?php

namespace app\index\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcFront;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\response\Json;

/**
 * 订单管理
 *
 * @icon fa fa-circle-o
 */
class Order extends XiluxcFront
{
    protected $relationSearch = true;
    /**
     * Order模型对象
     * @var \app\common\model\xiluxc\order\Order
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\xiluxc\order\Order;
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("refundStatusList", $this->model->getRefundStatusList());
        $this->view->assign("platformList", $this->model->getPlatformList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
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
        $where2 = [];
        if($this->brand){
            $where2['shop.brand_id'] = $this->brand->user_id;
        }else{
            $where2['shop.id'] = $this->shop->id;
        }
        $list = $this->model
            ->with(['user'=>function($q){
                $q->withField(['id','nickname']);
            },'shop'=>function($q){
                $q->withField(['id','name']);
            }])
            ->where($where)
            ->where($where2)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    public function edit($ids = null)
    {
        if($this->brand){
            $row = $this->model->get(['id'=>$ids,'brand_id'=>$this->brand->id]);
        }else{
            $row = $this->model->get(['id'=>$ids,'shop_id'=>$this->shop->id]);
        }
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $row->relationQuery(['order_item']);
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

}

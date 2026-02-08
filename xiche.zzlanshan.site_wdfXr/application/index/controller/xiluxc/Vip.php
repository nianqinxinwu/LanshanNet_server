<?php

namespace app\index\controller\xiluxc;


use app\common\controller\xiluxc\XiluxcFront;
use app\common\model\xiluxc\brand\ShopBrand;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 门店会员卡
 *
 * @icon fa fa-circle-o
 */
class Vip extends XiluxcFront
{
    protected $searchFields = "shop.name";

    protected $relationSearch = true;
    /**
     * Shopvip模型对象
     * @var \app\common\model\xiluxc\brand\Shopvip
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\xiluxc\brand\Shopvip;
        $this->view->assign("statusList", $this->model->getStatusList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $where2 = [];
        if($this->brand){
            $where2[$this->model->getTable().'.brand_id'] = $this->brand->id;
        }else if($this->shop['type'] != 1){
            $brand = ShopBrand::get(['user_id'=>$this->shop->brand_id]);
            $where2[$this->model->getTable().'.brand_id'] = $brand->id;
        }else{
            $where2['shop.id'] = $this->shop->id;
        }
        $list = $this->model
            ->with(['shop'=>function($query){
                $query->withField(["id","name"]);
            }])
            ->where($where)
            ->where($where2)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }


    /**
     * 添加
     *
     * @return string
     * @throws \think\Exception
     */
    public function add()
    {
        if(!$this->brand && $this->shop['type'] != 1){
            $this->error("分店没有权限");
        }
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        if ($this->shop && $this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->shop->id;
        }else if($this->brand){
            $params['brand_id'] = $this->brand->id;
        }
        $result = false;
        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }
        $this->success();
    }

    /**
     * 编辑
     *
     * @param $ids
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function edit($ids = null)
    {
        if(!$this->brand && $this->shop['type'] != 1){
            $this->error("分店没有权限");
        }
        if($this->brand){
            $row = $this->model->get(['id'=>$ids,'brand_id'=>$this->brand->id]);
        }else{
            $row = $this->model->get(['id'=>$ids,'shop_id'=>$this->shop->id]);
        }
        if (!$row) {
            $this->error(__('No Results were found'));
        }
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

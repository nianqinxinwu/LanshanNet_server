<?php

namespace app\index\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcFront;
use app\common\model\xiluxc\brand\BranchPackage;
use app\common\model\xiluxc\brand\PackageService;
use app\common\model\xiluxc\brand\ShopServicePrice;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use function fast\array_get;

/**
 * 门店套餐
 *
 * @icon fa fa-circle-o
 */
class Package extends XiluxcFront
{
    /**
     * Package模型对象
     * @var \app\common\model\xiluxc\brand\Package
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\xiluxc\brand\Package;
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
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();

        $this->model
            ->with(['shop'=>function($query){
                $query->withField(["id","name"]);
            }]);
        if($this->brand){
            $this->model->where($this->model->getTable().'.brand_id',$this->brand->id);
        }else{
            $this->model->where('shop.id', $this->shop->id);
        }
        $list = $this->model->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }


    public function add()
    {
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
        $services = $this->request->post("services/a");
        if(!$services){
            $this->error("请至少设置一个服务");
        }
        $result = false;
        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            //包含服务
            PackageService::setService($this->model,$services);
            //添加可用门店
            BranchPackage::setData($this->model,array_get($params,'shop_ids'));
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
        if (false === $this->request->isPost()) {
            $services = PackageService::field("shop_service_id as id,service_id,service_price_id,use_count")
                ->with(['service'=>function($q){
                    $q->withField(['id','name']);
                }])
                ->where("package_id",$row->id)
                ->select();
            foreach ($services as $service){
                $service['service_price'] = ShopServicePrice::field(["id",'title','shop_service_id'])->where("shop_service_id",$service['id'])->select();
            }
            $shopIds = BranchPackage::where("shop_package_id",$row->id)->column('shop_id');
            $this->assignconfig("services",$services);
            $this->view->assign("shopIds",$shopIds?implode(',',$shopIds): '');
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $services = $this->request->post('services/a');
        if(!$services){
            $this->error("请至少设置一个服务");
        }
        $result = false;
        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            //包含服务
            PackageService::setService($row,$services);
            //添加可用门店
            BranchPackage::setData($row,array_get($params,'shop_ids'));
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

    public function selects(){
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        $ids = $this->request->param('ids',null);
        $shopId = $this->request->param('shop_id',0);
        if (false === $this->request->isAjax()) {
            if($ids){
                $selectdata = $this->model
                    ->whereIN('id',$ids)->select();
                $this->assignconfig('selectdata',collection($selectdata)->toArray());
            }
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }

        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $list = $this->model
            ->where($where)
            ->where('shop_id',$shopId)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }


}

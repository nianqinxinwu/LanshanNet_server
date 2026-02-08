<?php

namespace app\admin\controller\xiluxc\brand;

use app\common\controller\Backend;
use app\common\model\xiluxc\brand\BranchPackage;
use app\common\model\xiluxc\brand\PackageService;
use app\common\model\xiluxc\brand\ShopServicePrice;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 门店套餐
 *
 * @icon fa fa-circle-o
 */
class Package extends Backend
{
    protected $noNeedRight = ["selectpage",'selects'];
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
        $this->relationSearch = true;
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
        $list = $this->model
            ->with([
                'brand'=>function($query){
                    $query->withField(["id","brand_name"]);
                },
                'shop'=>function($query){
                $query->withField(["id","name","brand_id"]);
            }])
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    public function add()
    {
        return;
    }


    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $services = PackageService::field("shop_service_id as id,service_id,service_price_id,use_count")
                ->with(['service'=>function($q){
                $q->withField(['id','name']);
            }
            ])
                ->where("package_id",$row->id)
                ->select();
            foreach ($services as $service){
                $service['service_price'] = ShopServicePrice::field(["id",'title','shop_service_id'])->where("shop_service_id",$service['id'])->select();
            }
            $this->assignconfig("services",$services);
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $services = $this->request->post('services/a');
        if(!$services){
            $this->error("请至少设置一个服务");
        }
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            //包含服务
            PackageService::setService($row,$services);

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
        $currentName = $this->model->getTable();
        $list = $this->model
            ->where($where)
            ->whereExists(function ($query) use($currentName,$shopId){
                $shopBranchPackage = (new BranchPackage())->getQuery()->getTable();
                $query->table($shopBranchPackage)->where($currentName.'.id=' . $shopBranchPackage . '.shop_package_id')->where("shop_id",$shopId);
            })
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }


}

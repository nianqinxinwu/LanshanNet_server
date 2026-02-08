<?php

namespace app\index\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcFront;
use app\common\model\xiluxc\brand\PackageService;
use app\common\model\xiluxc\brand\ShopServicePrice;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Model;
use function fast\array_get;

/**
 * 门店服务
 *
 * @icon fa fa-circle-o
 */
class BranchPackage extends XiluxcFront
{
    protected $relationSearch = true;
    /**
     * ShopService模型对象
     * @var \app\common\model\xiluxc\brand\BranchPackage
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->request->filter('trim,strip_tags');
        $this->model = new \app\common\model\xiluxc\brand\BranchPackage;
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
        $shopId = $this->shop->id;
        $list = $this->model
            ->with(["shopPackage"])
            ->where($where)
            ->where($this->model->getTable().'.shop_id',$shopId)
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
        $package = $this->model->get($ids);
        $this->model = new \app\common\model\xiluxc\brand\Package();
        $row = $this->model->get($package->shop_package_id);
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
            $shopIds = \app\common\model\xiluxc\brand\BranchPackage::where("shop_package_id",$row->id)->column('shop_id');
            $this->assignconfig("services",$services);
            $this->view->assign("shopIds",$shopIds?implode(',',$shopIds): '');
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $this->error("连锁店不可编辑");
    }

    /**
     * 优惠券
     * @return string|\think\response\Json
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function selects(){
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        $ids = $this->request->param('ids',null);
        $shopId = $this->shop->id;
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
            ->with(["shopPackage"])
            ->where($where)
            ->where($this->model->getTable().'.shop_id',$shopId)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

}

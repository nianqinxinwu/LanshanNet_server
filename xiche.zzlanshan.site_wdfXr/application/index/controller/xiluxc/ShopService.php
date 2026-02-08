<?php

namespace app\index\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcFront;
use app\common\model\xiluxc\brand\ShopBranchService;
use app\common\model\xiluxc\brand\ShopBrand;
use app\common\model\xiluxc\brand\ShopServicePrice;
use app\common\model\xiluxc\service\Service;
use fast\Tree;
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
class ShopService extends XiluxcFront
{
    protected $relationSearch = true;
    /**
     * ShopService模型对象
     * @var \app\common\model\xiluxc\brand\ShopService
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->request->filter('trim,strip_tags');
        $this->model = new \app\common\model\xiluxc\brand\ShopService;
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
            },'service'=>function($query){
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
        $skus = array_get($params,'skus');
        $skus = $skus ? json_decode($skus,true):null;
        if(!$skus){
            $this->error("请至少设置一个规格");
        }
        $params['salesprice'] = min(array_column($skus,'salesprice'));
        $params['vip_price'] = min(array_column($skus,'vip_price'));
        $result = false;
        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            //规格
            ShopServicePrice::setPrices($this->model,$skus);
            //添加可用门店
            ShopBranchService::setData($this->model,array_get($params,'shop_ids'));
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
            $prices = ShopServicePrice::where("shop_id",$row->shop_id)->where("service_id",$row->service_id)->where("shop_service_id",$row->id)->field("id,title,salesprice,vip_price,status")->select();
            $shopIds = ShopBranchService::where("shop_service_id",$row->id)->column('shop_id');
            $this->view->assign("prices",$prices);
            $this->view->assign("shopIds",$shopIds?implode(',',$shopIds): '');
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $skus = array_get($params,'skus');
        $skus = $skus ? json_decode($skus,true):null;
        if(!$skus){
            $this->error("请至少设置一个规格");
        }
        $params['salesprice'] = min(array_column($skus,'salesprice'));
        $params['vip_price'] = min(array_column($skus,'vip_price'));
        $result = false;
        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            //规格
            ShopServicePrice::setPrices($row,$skus);
            //添加可用门店
            ShopBranchService::setData($row,array_get($params,'shop_ids'));
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


    /**
     * Selectpage的实现方法
     *
     * 当前方法只是一个比较通用的搜索匹配,请按需重载此方法来编写自己的搜索逻辑,$where按自己的需求写即可
     * 这里示例了所有的参数，所以比较复杂，实现上自己实现只需简单的几行即可
     *
     */
    public function selectpage_service()
    {
        $this->model = new Service();
        //设置过滤方法
        $this->request->filter(['trim', 'strip_tags', 'htmlspecialchars']);

        //搜索关键词,客户端输入以空格分开,这里接收为数组
        $word = (array)$this->request->request("q_word/a");
        //当前页
        $page = $this->request->request("pageNumber");
        //分页大小
        $pagesize = $this->request->request("pageSize");
        //搜索条件
        $andor = $this->request->request("andOr", "and", "strtoupper");
        //排序方式
        $orderby = (array)$this->request->request("orderBy/a");
        //显示的字段
        $field = $this->request->request("showField");
        //主键
        $primarykey = $this->request->request("keyField");
        //主键值
        $primaryvalue = $this->request->request("keyValue");
        //搜索字段
        $searchfield = (array)$this->request->request("searchField/a");
        //自定义搜索条件
        $custom = (array)$this->request->request("custom/a");
        //是否返回树形结构
        $istree = $this->request->request("isTree", 0);
        $ishtml = $this->request->request("isHtml", 0);
        if ($istree) {
            $word = [];
            $pagesize = 999999;
        }
        $order = [];
        foreach ($orderby as $k => $v) {
            $order[$v[0]] = $v[1];
        }
        $field = $field ? $field : 'name';

        //如果有primaryvalue,说明当前是初始化传值
        if ($primaryvalue !== null) {
            $where = [$primarykey => ['in', $primaryvalue]];
            $pagesize = 999999;
        } else {
            $where = function ($query) use ($word, $andor, $field, $searchfield, $custom) {
                $logic = $andor == 'AND' ? '&' : '|';
                $searchfield = is_array($searchfield) ? implode($logic, $searchfield) : $searchfield;
                $searchfield = str_replace(',', $logic, $searchfield);
                $word = array_filter(array_unique($word));
                if (count($word) == 1) {
                    $query->where($searchfield, "like", "%" . reset($word) . "%");
                } else {
                    $query->where(function ($query) use ($word, $searchfield) {
                        foreach ($word as $index => $item) {
                            $query->whereOr(function ($query) use ($item, $searchfield) {
                                $query->where($searchfield, "like", "%{$item}%");
                            });
                        }
                    });
                }
                if ($custom && is_array($custom)) {
                    foreach ($custom as $k => $v) {
                        if (is_array($v) && 2 == count($v)) {
                            $query->where($k, trim($v[0]), $v[1]);
                        } else {
                            if($k == 'shop_id'){
                                $serviceIds = $v ? \app\common\model\xiluxc\brand\ShopService::where("shop_id",$v)->column('service_id') : [];
                                $query->whereNotIn("id",$serviceIds);
                            }else{
                                $query->where($k, '=', $v);
                            }

                        }
                    }
                }
            };
        }
        $list = [];

        $where2 = [];

        $total = $this->model->where($where)->where($where2)->count();
        if ($total > 0) {
            $fields = is_array($this->selectpageFields) ? $this->selectpageFields : ($this->selectpageFields && $this->selectpageFields != '*' ? explode(',', $this->selectpageFields) : []);

            //如果有primaryvalue,说明当前是初始化传值,按照选择顺序排序
            if ($primaryvalue !== null && preg_match("/^[a-z0-9_\-]+$/i", $primarykey)) {
                $primaryvalue = array_unique(is_array($primaryvalue) ? $primaryvalue : explode(',', $primaryvalue));
                //修复自定义data-primary-key为字符串内容时，给排序字段添加上引号
                $primaryvalue = array_map(function ($value) {
                    return '\'' . $value . '\'';
                }, $primaryvalue);

                $primaryvalue = implode(',', $primaryvalue);

                $this->model->orderRaw("FIELD(`{$primarykey}`, {$primaryvalue})");
            } else {
                $this->model->order($order);
            }

            $datalist = $this->model->where($where)
                ->where($where2)
                ->page($page, $pagesize)
                ->select();

            foreach ($datalist as $index => $item) {
                unset($item['password'], $item['salt']);
                if ($this->selectpageFields == '*') {
                    $result = [
                        $primarykey => $item[$primarykey] ?? '',
                        $field      => $item[$field] ?? '',
                    ];
                } else {
                    $result = array_intersect_key(($item instanceof Model ? $item->toArray() : (array)$item), array_flip($fields));
                }
                $result['pid'] = isset($item['pid']) ? $item['pid'] : (isset($item['parent_id']) ? $item['parent_id'] : 0);
                $list[] = $result;
            }
            if ($istree && !$primaryvalue) {
                $tree = Tree::instance();
                $tree->init(collection($list)->toArray(), 'pid');
                $list = $tree->getTreeList($tree->getTreeArray(0), $field);
                if (!$ishtml) {
                    foreach ($list as &$item) {
                        $item = str_replace('&nbsp;', ' ', $item);
                    }
                    unset($item);
                }
            }
        }
        //这里一定要返回有list这个字段,total是可选的,如果total<=list的数量,则会隐藏分页按钮
        return json(['list' => $list, 'total' => $total]);
    }


    public function selects(){
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            $ids = $this->request->param('ids',null);
            if($ids){
                $selectdata = $this->model
                    ->with(['shop'=>function($query){
                        $query->withField(["id","name"]);
                    },'service'=>function($query){
                        $query->withField(["id","name"]);
                    }])
                    ->whereIN($this->model->getTable().'.id',$ids)->select();
                $this->assignconfig('selectdata',collection($selectdata)->toArray());
            }
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
            },'service'=>function($query){
                $query->withField(["id","name"]);
            },'service_price'=>function($query){
                $query->field(["id","title","shop_service_id"]);
            }
                ]);
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


}

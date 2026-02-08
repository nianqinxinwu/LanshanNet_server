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
class BranchService extends XiluxcFront
{
    protected $relationSearch = true;
    /**
     * ShopService模型对象
     * @var \app\common\model\xiluxc\brand\ShopBranchService
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->request->filter('trim,strip_tags');
        $this->model = new \app\common\model\xiluxc\brand\ShopBranchService;
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
            ->with(["shopService"])
            ->where($where)
            ->where($this->model->getTable().'.shop_id',$shopId)
            ->order($sort, $order)
            ->paginate($limit)
            ->each(function ($query){
                $query->shop_service->relationQuery(['service']);
            });
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }


    public function add()
    {
        return;
    }

    public function edit($ids = null)
    {
        $brandService = $this->model->get($ids);
        $this->model = new \app\common\model\xiluxc\brand\ShopService();
        $row = $this->model->get($brandService->shop_service_id);
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
        $this->error("连锁店不可编辑");
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
        $list = $this->model
            ->with(['service'=>function($query){
                $query->withField(["id","name"]);
            },'shopService'
                ])
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    /**
     * 批量更新
     *
     * @param $ids
     * @return void
     */
    public function multi($ids = null)
    {
        if (false === $this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $ids = $ids ?: $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }

        if (false === $this->request->has('params')) {
            $this->error(__('No rows were updated'));
        }
        parse_str($this->request->post('params'), $values);
        $values = array_intersect_key($values, array_flip(is_array($this->multiFields) ? $this->multiFields : explode(',', $this->multiFields)));
        if (empty($values)) {
            $this->error(__('You have no permission'));
        }
        if ($this->shop && $this->dataLimit && $this->dataLimitFieldAutoFill) {
            $this->model->where($this->dataLimitField,$this->shop->id);
        }
        $count = 0;
        Db::startTrans();
        try {
            $list = $this->model->where($this->model->getPk(), 'in', $ids)->select();
            foreach ($list as $item) {
                $count += $item->allowField(true)->isUpdate(true)->save($values);
            }
            Db::commit();
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        }
        $this->error(__('No rows were updated'));
    }

}

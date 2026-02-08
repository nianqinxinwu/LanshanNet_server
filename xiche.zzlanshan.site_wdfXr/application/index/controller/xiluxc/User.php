<?php

namespace app\index\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcFront;
use app\common\library\Auth;
use app\common\model\xiluxc\brand\ShopBrand;
use app\common\model\xiluxc\brand\ShopUser;
use app\common\model\xiluxc\order\Aftersale;
use app\common\model\xiluxc\user\UserPackage;
use app\common\model\xiluxc\user\UserShopVip;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Loader;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends XiluxcFront
{

    protected $relationSearch = true;
    protected $searchFields = 'id,username,nickname';

    /**
     * @var \app\common\model\xiluxc\user\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\xiluxc\user\User();
    }

    /**
     * 查看
     */
    public function index()
    {
        $this->dataLimit = false;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $where2 = [];
            if($this->brand){
                $where2['brand_id'] = $this->brand->id;
            }else if($this->shop['type'] != 1){
                $brand = ShopBrand::get(['user_id'=>$this->shop->brand_id]);
                $where2['brand_id'] = $brand->id;
            }else{
                $where2['shop_id'] = $this->shop->id;
            }
            $sql = ShopUser::where($where2)->field("user_id shop_user_id,createtime shop_createtime,updatetime shop_updatetime")->buildSql();
            $list = $this->model
                ->join([$sql=>'shop_user'],'shop_user.shop_user_id=user.id')
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as $k => $v) {
                $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
                $v->hidden(['password', 'salt']);

                //$v->username = preg_match("/^1[3-9]{1}\d{9}$/",$v->username) ? substr_replace($v->username,'****',3,4) : $v->username;
                //$v->mobile = preg_match("/^1[3-9]{1}\d{9}$/",$v->mobile) ? substr_replace($v->mobile,'****',3,4) : $v->mobile;
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        return;
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            $this->token();
        }
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $this->view->assign('groupList', build_select('row[group_id]', \app\admin\model\UserGroup::column('id,name'), $row['group_id'], ['class' => 'form-control selectpicker']));
        return parent::edit($ids);
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        Auth::instance()->delete($row['id']);
        $this->success();
    }


    /**
     * 生成查询所需要的条件,排序方式
     * @param mixed   $searchfields   快速查询的字段
     * @param boolean $relationSearch 是否关联查询
     * @return array
     */
    protected function buildparams($searchfields = null, $relationSearch = null)
    {
        $searchfields = is_null($searchfields) ? $this->searchFields : $searchfields;
        $relationSearch = is_null($relationSearch) ? $this->relationSearch : $relationSearch;
        $search = $this->request->get("search", '');
        $filter = $this->request->get("filter", '');
        $op = $this->request->get("op", '', 'trim');
        $sort = $this->request->get("sort", !empty($this->model) && $this->model->getPk() ? $this->model->getPk() : 'id');
        $order = $this->request->get("order", "DESC");
        $offset = $this->request->get("offset/d", 0);
        $limit = $this->request->get("limit/d", 999999);
        //新增自动计算页码
        $page = $limit ? intval($offset / $limit) + 1 : 1;
        if ($this->request->has("page")) {
            $page = $this->request->get("page/d", 1);
        }
        $this->request->get([config('paginate.var_page') => $page]);
        $filter = (array)json_decode($filter, true);
        $op = (array)json_decode($op, true);
        $filter = $filter ? $filter : [];
        $where = [];
        $alias = [];
        $bind = [];
        $name = '';
        $aliasName = '';
        if (!empty($this->model) && $relationSearch) {
            $name = $this->model->getTable();
            $alias[$name] = Loader::parseName(basename(str_replace('\\', '/', get_class($this->model))));
            $aliasName = $alias[$name] . '.';
        }
        $sortArr = explode(',', $sort);
        foreach ($sortArr as $index => & $item) {
            $item = stripos($item, ".") === false ? $aliasName . trim($item) : $item;
        }
        unset($item);
        $sort = implode(',', $sortArr);
        $adminIds = $this->auth->id;
        if ($this->dataLimit) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        if ($search) {
            $searcharr = is_array($searchfields) ? $searchfields : explode(',', $searchfields);
            foreach ($searcharr as $k => &$v) {
                $v = stripos($v, ".") === false ? $aliasName . $v : $v;
            }
            unset($v);
            $where[] = [implode("|", $searcharr), "LIKE", "%{$search}%"];
        }
        $index = 0;
        foreach ($filter as $k => $v) {
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $k)) {
                continue;
            }
            $sym = $op[$k] ?? '=';
            if (stripos($k, ".") === false) {
                $k = $aliasName . $k;
            }
            $v = !is_array($v) ? trim($v) : $v;
            $sym = strtoupper($op[$k] ?? $sym);
            //null和空字符串特殊处理
            if (!is_array($v)) {
                if (in_array(strtoupper($v), ['NULL', 'NOT NULL'])) {
                    $sym = strtoupper($v);
                }
                if (in_array($v, ['""', "''"])) {
                    $v = '';
                    $sym = '=';
                }
            }
            if($k == 'account.first_username'){
                $userIds = \app\admin\model\User::whereLike('nickname',"%$v%")->column('id')?:[-1];
                $where[] = ['first_user_id','IN',$userIds];
                continue;
            }
            if($k == 'account.second_username'){
                $userIds2 = \app\admin\model\User::whereLike('nickname',"%$v%")->column('id')?:[-1];
                $where[] = ['second_user_id','IN',$userIds2];
                continue;
            }

            switch ($sym) {
                case '=':
                case '<>':
                    $where[] = [$k, $sym, (string)$v];
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                case 'LIKE %...%':
                case 'NOT LIKE %...%':
                    $where[] = [$k, trim(str_replace('%...%', '', $sym)), "%{$v}%"];
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $where[] = [$k, $sym, intval($v)];
                    break;
                case 'FINDIN':
                case 'FINDINSET':
                case 'FIND_IN_SET':
                    $v = is_array($v) ? $v : explode(',', str_replace(' ', ',', $v));
                    $findArr = array_values($v);
                    foreach ($findArr as $idx => $item) {
                        $bindName = "item_" . $index . "_" . $idx;
                        $bind[$bindName] = $item;
                        $where[] = "FIND_IN_SET(:{$bindName}, `" . str_replace('.', '`.`', $k) . "`)";
                    }
                    break;
                case 'IN':
                case 'IN(...)':
                case 'NOT IN':
                case 'NOT IN(...)':
                    $where[] = [$k, str_replace('(...)', '', $sym), is_array($v) ? $v : explode(',', $v)];
                    break;
                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr, function ($v) {
                            return $v != '' && $v !== false && $v !== null;
                        })) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'BETWEEN' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'BETWEEN' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, $sym, $arr];
                    break;
                case 'RANGE':
                case 'NOT RANGE':
                    $v = str_replace(' - ', ',', $v);
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr)) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'RANGE' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'RANGE' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $tableArr = explode('.', $k);
                    if (count($tableArr) > 1 && $tableArr[0] != $name && !in_array($tableArr[0], $alias)
                        && !empty($this->model) && $this->relationSearch) {
                        //修复关联模型下时间无法搜索的BUG
                        $relation = Loader::parseName($tableArr[0], 1, false);
                        $alias[$this->model->$relation()->getTable()] = $tableArr[0];
                    }
                    $where[] = [$k, str_replace('RANGE', 'BETWEEN', $sym) . ' TIME', $arr];
                    break;
                case 'NULL':
                case 'IS NULL':
                case 'NOT NULL':
                case 'IS NOT NULL':
                    $where[] = [$k, strtolower(str_replace('IS ', '', $sym))];
                    break;
                default:
                    break;
            }
            $index++;
        }
        if (!empty($this->model)) {
            $this->model->alias($alias);
        }
        $model = $this->model;
        $where = function ($query) use ($where, $alias, $bind, &$model) {
            if (!empty($model)) {
                $model->alias($alias);
                $model->bind($bind);
            }
            foreach ($where as $k => $v) {
                if (is_array($v)) {
                    call_user_func_array([$query, 'where'], $v);
                } else {
                    $query->where($v);
                }
            }
        };
        return [$where, $sort, $order, $offset, $limit, $page, $alias, $bind];
    }


    /**
     * 我的会员卡
     * @return string|\think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function uservip(){
        $this->dataLimit = false;
        $this->model = new UserShopVip();
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $where2 = [];
            if($this->brand){
                $where2[$this->model->getTable().'.brand_id'] = $this->brand->id;
            }else if($this->shop['type'] != 1){
                $brand = ShopBrand::get(['user_id'=>$this->shop->brand_id]);
                $where2[$this->model->getTable().'.brand_id'] = $brand->id;
            }else{
                $where2[$this->model->getTable().'.shop_id'] = $this->shop->id;
            }
            $list = $this->model
                ->with(['brand','shop'])
                ->where($where)
                ->where($where2)
                ->order($sort, $order)
                ->paginate($limit);
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 我的套餐
     * @return string|\think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function package(){
        $this->dataLimit = false;

        $this->model = new UserPackage();
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $where2 = [];
            if($this->brand){
                $where2['user_package.brand_id'] = $this->brand->id;
            }else if($this->shop['type'] != 1){
                $brand = ShopBrand::get(['user_id'=>$this->shop->brand_id]);
                $where2['user_package.brand_id'] = $brand->id;
            }else{
                $where2['user_package.shop_id'] = $this->shop->id;
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->with(['brand','shop','packageService'])
                ->where($where)
                ->where($where2)
                ->order($sort, $order)
                ->paginate($limit)
                ->each(function ($row){
                    $row->aftersale = Aftersale::where("order_id",$row->order_id)->where('aftersale_type','package')->order('id','desc')->find();
                });
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 退款
     * @param null $ids
     */
    public function aftersale($ids=null){
        if(!$ids){
            $this->error("参数不对");
        }
        $this->model = new UserPackage();
         $where2 = [];
        if($this->brand){
            $where2['user_package.brand_id'] = $this->brand->id;
        }else if($this->shop['type'] != 1){
            $brand = ShopBrand::get(['user_id'=>$this->shop->brand_id]);
            $where2['user_package.brand_id'] = $brand->id;
        }else{
            $where2['user_package.shop_id'] = $this->shop->id;
        }
        $row = $this->model
            ->with(['brand','shop','packageService','ordering'])
            ->where($this->model->getTable().".id",$ids)
            ->where($where2)
            ->find();
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $money = 0;
        foreach ($row->package_service as $item){
            $money = bcadd($money,bcmul($item->salesprice,$item->use_count,2),2);
        }
        $refund_money = bcsub($row->pay_fee,$money,2);
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);

            $this->view->assign("refund_money",$refund_money>0?$refund_money:0);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        if($refund_money < $params['refund_money']){
            $this->error("退款金额超过可退款金额");
        }
        $aftersale = new Aftersale();
        if($aftersale->where('order_id',$row->order_id)->whereIn("status",["0",'1'])->count()>0){
            $this->error("已存在退款申请记录，不可再次申请");
        }

        $infoParams = array_merge([
                'aftersale_type'    =>  'package',
                'order_no'          =>  'A'.date('YmdHis').mt_rand(0,9999),
                'user_id'           =>  $row->user_id,
                'user_package_id'   =>  $row->id,
                'order_id'          =>  $row->order_id,
                'refund_money'      =>  $refund_money,
                'shop_id'           =>  $row->shop_id,
                'brand_id'          =>  $row->brand_id,
        ],$params);
        $result = false;
        Db::startTrans();
        try {
            $result = $aftersale->allowField(true)->save($infoParams);
            $row->allowField(true)->save(['status'=>'apply_refund']);//申请退款
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

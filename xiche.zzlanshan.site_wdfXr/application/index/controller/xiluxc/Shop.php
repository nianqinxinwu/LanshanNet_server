<?php

namespace app\index\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcFront;
use app\common\model\User;
use app\common\model\xiluxc\brand\BrandUser as BrandUserModel;
use app\common\model\xiluxc\brand\ShopAccount;
use fast\Tree;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Model;
use think\response\Json;

/**
 * 门店管理
 *
 * @icon fa fa-circle-o
 */
class Shop extends XiluxcFront
{
    protected $relationSearch = true;

    protected $searchFields = "name,user.mobile";

    /**
     * Shop模型对象
     * @var \app\common\model\xiluxc\brand\Shop
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        if(!$this->brand && !in_array($this->request->action(),['edit'])){
            $this->error("没有权限");
        }
        $this->model = new \app\common\model\xiluxc\brand\Shop;
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("auditStatusList", $this->model->getAuditStatusList());
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

        $where2['brand_id'] = $this->brand->user_id;

        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $list = $this->model
            ->with(['user'=>function($q){
                $q->withField(['id','nickname','mobile']);
            },'account'=>function($q){
                $q->withField(['id','rate','vip_rate','total_money','money','withdraw_money']);
            }
                ])
            ->where($where)
            ->where($where2)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    /**
     * 添加
     */
    public function add()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $users = $this->request->post('user/a');
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }
        $user = User::where('mobile',$users['mobile'])->find();
        if($user){
            $brandUser = BrandUserModel::where("user_id",$user->id)->find();
            if($brandUser){
                $this->error("手机号已存在门店/品牌账号");
            }
        }
        $params['province_id'] = $this->request->post('province',0);
        $params['city_id'] = $this->request->post('city',0);
        $params['district_id'] = $this->request->post('district',0);
        $salt = \fast\Random::alnum();
        $users['password'] = \app\common\library\Auth::instance()->getEncryptPassword($users['password'], $salt);
        $users['salt'] = $salt;
        $result = false;
        Db::startTrans();
        try {
            if($user){
                //账号存在-手机号不可修改
               $user->allowField(['nickname','password','salt'])->save($users);
            }else{
                //账号不存在-创建新账号
                $user = new User();
                $users['status'] = 'normal';
                $user->allowField(true)->save($users);
            }
            //创建身份
            $groupType = $params['type'] == '1' ? 1 : 3;
            BrandUserModel::saveInfo($groupType,$user);
            if($groupType == 1){
                $params['brand_id'] = 0;
            }
            $params['user_id'] = $user->id;
            //创建门店
            $result = $this->model->allowField(true)->save($params);
            //创建门店钱包
            ShopAccount::addAccount($this->model,$params);
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
        $ids = !$ids ? (!$this->brand?$this->shop->id:null): $ids;
        if($this->brand){
            $row = $this->model->get(['id'=>$ids,'brand_id'=>$this->brand->user_id]);
        }else{
            $row = $this->model->get(['id'=>$ids,'user_id'=>$this->auth->id]);
        }

        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('user', $row->user);
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $users = $this->request->post('user/a');
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        if($row->user->mobile !== $users['mobile']){
            $user = User::where('mobile',$users['mobile'])->find();
            if($user){
                $xiluxcUser = BrandUserModel::where("user_id",$user->id)->find();
                if($xiluxcUser){
                    $this->error("手机号已被占用");
                }
            }
        }
        $params['province_id'] = $this->request->post('province',0);
        $params['city_id'] = $this->request->post('city',0);
        $params['district_id'] = $this->request->post('district',0);
        if($users['password']){
            $salt = \fast\Random::alnum();
            $users['password'] = \app\common\library\Auth::instance()->getEncryptPassword($users['password'], $salt);
            $users['salt'] = $salt;
        }
        $result = false;
        Db::startTrans();
        try {
            $row->user->allowField(['nickname','password','salt'])->save($users);
            //账号存在
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


    /**
     * 删除
     *
     * @param $ids
     * @return void
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function del($ids = null)
    {
        if (false === $this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ?: $this->request->post("ids");
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        $pk = $this->model->getPk();
        if ($this->shop && $this->dataLimit && $this->dataLimitFieldAutoFill) {
            $this->model->where($this->dataLimitField,$this->shop->id);
        }else if($this->brand){
            $brandId = $this->brand->user_id;
            $this->model->where('brand_id',$brandId);
        }
        $list = $this->model->where($pk, 'in', $ids)->select();
        $count = 0;
        Db::startTrans();
        try {
            foreach ($list as $item) {
                $count += $item->delete();
            }
            Db::commit();
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        }
        $this->error(__('No rows were deleted'));
    }

    /**
     * Selectpage的实现方法
     *
     * 当前方法只是一个比较通用的搜索匹配,请按需重载此方法来编写自己的搜索逻辑,$where按自己的需求写即可
     * 这里示例了所有的参数，所以比较复杂，实现上自己实现只需简单的几行即可
     *
     */
    public function selectpage()
    {
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
                            $query->where($k, '=', $v);
                        }
                    }
                }
            };
        }
        $list = [];

        $where2 = [];
        if($this->brand){
            $where2['brand_id'] = $this->brand->user_id;
        }
        $total = $this->model->passed()->where($where)->where($where2)->count();
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

            $datalist = $this->model->passed()->where($where)
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

}

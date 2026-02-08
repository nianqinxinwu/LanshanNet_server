<?php

namespace app\admin\controller\xiluxc\brand;

use app\common\model\User;
use app\common\controller\Backend;
use app\common\model\xiluxc\brand\ShopBrand;
use app\common\model\xiluxc\brand\BrandUser AS BrandUserModel;
use fast\Tree;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Model;
use think\response\Json;
use function fast\array_get;

/**
 * 会员管理
 *
 * @icon fa fa-circle-o
 */
class BrandUser extends Backend
{
    protected $noNeedRight = ['selectpage','brand_selectpage'];
    protected $relationSearch = true;
    protected $searchFields = "brand.brand_name";
    /**
     * BrandUser模型对象
     * @var \app\common\model\xiluxc\brand\BrandUser
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\xiluxc\brand\BrandUser;
        $this->view->assign("groupTypeList", $this->model->getGroupTypeList());
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
        $list = $this->model
            ->brandUser()
            ->with(['user'=>function($q){
                $q->withField(['id','mobile']);
            },'brand'=>function($q){
                $q->withField(['id','user_id','brand_name','logo']);
            }])
            ->where($where)
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
        $users = $this->request->post('user/a');
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }

        $user = User::where('mobile',$users['mobile'])->find();
        if($user){
            $xiluxcUser = BrandUserModel::where("user_id",$user->id)->find();
            if($xiluxcUser){
                $this->error("手机号已存在门店账号，无需创建");
            }
        }
        $salt = \fast\Random::alnum();
        $users['password'] = \app\common\library\Auth::instance()->getEncryptPassword($users['password'], $salt);
        $users['salt'] = $salt;
        $result = false;
        Db::startTrans();
        try {
            if($user){
                $result = $user->allowField(['password','salt'])->save($users);
            }else{
                //账号不存在-创建新账号
                $user = new User();
                $users['status'] = 'normal';
                $result = $user->allowField(true)->save($users);
            }
            //创建身份
            if($user){
                BrandUserModel::saveInfo(2,$user);
            }
            //品牌信息保存
            if($user){
                ShopBrand::saveInfo($user,$params);
            }
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
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('user', $row->user);
            $this->view->assign('row', $row);
            $this->view->assign('brand', $row->brand);
            return $this->view->fetch();
        }
        $users = $this->request->post('user/a');
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        if(!$row->user){
            $this->error("账号不存在");
        }
        if($row->user->mobile !== $users['mobile']){
            $user = User::where('mobile',$users['mobile'])->find();
            if($user){
                $xiluxcUser = BrandUserModel::where("user_id",$user->id)->find();
                if($xiluxcUser){
                    $this->error("手机号已存在门店账号，无需创建");
                }
            }
        }
        if($users['password']){
            $salt = \fast\Random::alnum();
            $users['password'] = \app\common\library\Auth::instance()->getEncryptPassword($users['password'], $salt);
            $users['salt'] = $salt;
        }
        $result = false;
        Db::startTrans();
        try {
            $result = $row->user->allowField(['password','salt'])->save($users);
            //品牌信息保存-更新
            if($row->user){
                ShopBrand::saveInfo($row->user,$params);
            }
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
            $where = ['brand_user.'.$primarykey => ['in', $primaryvalue]];
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
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        $list = [];
        $total = $this->model->brandUser()->with(['brand'])->where($where)->count();
        if ($total > 0) {
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }

            $fields = is_array($this->selectpageFields) ? $this->selectpageFields : ($this->selectpageFields && $this->selectpageFields != '*' ? explode(',', $this->selectpageFields) : []);

            //如果有primaryvalue,说明当前是初始化传值,按照选择顺序排序
            if ($primaryvalue !== null && preg_match("/^[a-z0-9_\-]+$/i", $primarykey)) {
                $primaryvalue = array_unique(is_array($primaryvalue) ? $primaryvalue : explode(',', $primaryvalue));
                //修复自定义data-primary-key为字符串内容时，给排序字段添加上引号
                $primaryvalue = array_map(function ($value) {
                    return '\'' . $value . '\'';
                }, $primaryvalue);

                $primaryvalue = implode(',', $primaryvalue);

                $this->model->orderRaw("FIELD(`brand_user`.`{$primarykey}`, {$primaryvalue})");
            } else {
                $this->model->order($order);
            }

            $datalist = $this->model->brandUser()
                ->with(['brand'])->where($where)
                ->page($page, $pagesize)
                ->select();

            foreach ($datalist as $index => $item) {
                unset($item['password'], $item['salt']);
                if ($this->selectpageFields == '*') {
                    $result = [
                        $primarykey => $item[$primarykey] ?? '',
                        $field      => $item['brand'][$field] ?? '',
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


    public function brand_selectpage()
    {
        $this->model = new ShopBrand();
        return parent::selectpage();
    }

    public function multi($ids=null)
    {
        return;
    }

}

<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use app\common\library\Auth;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{

    protected $relationSearch = true;
    protected $searchFields = 'id,username,nickname';

    /**
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\User;
    }

    /**
     * 查看
     */
    // public function index()
    // {
    //     //设置过滤方法
    //     $this->request->filter(['strip_tags', 'trim']);
    //     if ($this->request->isAjax()) {
    //         //如果发送的来源是Selectpage，则转发到Selectpage
    //         if ($this->request->request('keyField')) {
    //             return $this->selectpage();
    //         }
    //         list($where, $sort, $order, $offset, $limit) = $this->buildparams();
    //         $list = $this->model
    //             ->with('group')
    //             ->where($where)
    //             ->order($sort, $order)
    //             ->paginate($limit);
    //         foreach ($list as $k => $v) {
    //             $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
    //             $v->hidden(['password', 'salt']);
    //         }
    //         $result = array("total" => $list->total(), "rows" => $list->items());

    //         return json($result);
    //     }
    //     return $this->view->fetch();
    // }
public function index()
{
    //设置过滤方法
    $this->request->filter(['strip_tags', 'trim']);
    if ($this->request->isAjax()) {
        //如果发送的来源是Selectpage，则转发到Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        list($where, $sort, $order, $offset, $limit) = $this->buildparams();
        $list = $this->model
            ->with('group')
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        
        foreach ($list as $k => $v) {
            $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
            $v->hidden(['password', 'salt']);
            
            // 处理 username 字段
            if (isset($v->username) && !empty($v->username)) {
                $v->username = $this->desensitizePhone($v->username);
            }
            
            // 处理 mobile 字段
            if (isset($v->mobile) && !empty($v->mobile)) {
                $v->mobile = $this->desensitizePhone($v->mobile);
            }
        }
        
        $result = array("total" => $list->total(), "rows" => $list->items());

        return json($result);
    }
    return $this->view->fetch();
}

/**
 * 手机号脱敏处理
 * 
 * @param string $phone 手机号
 * @return string 脱敏后的手机号或原值
 */
private function desensitizePhone($phone)
{
    // 判断是否是11位的手机号（1开头，第二位是3-9）
    if (preg_match('/^1[3-9]\d{9}$/', $phone)) {
        // 将手机号中间4位替换为*
        return substr($phone, 0, 3) . '****' . substr($phone, 7);
    }
    return $phone;
}
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $this->token();
        }
        return parent::add();
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

}

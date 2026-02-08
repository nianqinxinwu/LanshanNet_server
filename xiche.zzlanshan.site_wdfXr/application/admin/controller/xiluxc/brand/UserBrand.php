<?php

namespace app\admin\controller\xiluxc\brand;

use app\admin\model\User;
use app\common\controller\Backend;
use app\common\model\xiluxc\brand\BrandUser;
use app\common\model\xiluxc\brand\ShopBrand;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 品牌申请
 *
 * @icon fa fa-circle-o
 */
class UserBrand extends Backend
{
    protected $searchFields = 'brand_name';
    /**
     * UserBrand模型对象
     * @var \app\common\model\xiluxc\brand\UserBrand
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\xiluxc\brand\UserBrand;
        $this->view->assign("statusList", $this->model->getStatusList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


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
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
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
            if($params['status'] == 'passed'){
                #审核成功后添加品牌账号
                $user = User::where("mobile",$row->account_mobile)->find();
                if($user){
                    $xiluxcUser = BrandUser::where("user_id",$user['id'])->find();
                    if($xiluxcUser){
                        throw new Exception("当前账号已被占用");
                    }
                }
                if(!$user){
                    $salt = \fast\Random::alnum();
                    $users['password'] = \app\common\library\Auth::instance()->getEncryptPassword(888888, $salt);
                    $users['salt'] = $salt;
                    $users['status'] = 'normal';
                    $users['nickname'] = $row['brand_name'];
                    $users['mobile'] = $row['account_mobile'];
                    $users['avatar'] = $row['logo'];
                    $user = User::create($users);
                }
                //添加品牌
                BrandUser::saveInfo(2,$user);
                //品牌信息保存
                ShopBrand::saveInfo($user,[
                    'brand_name'    =>  $row['brand_name'],
                    'logo'    =>  $row['logo'],
                    'description'    =>  $row['description'] ?? '',
                    'concat_name'    =>  $row['concat_name'],
                    'contact_mobile'    =>  $row['mobile'],
                ]);
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

}

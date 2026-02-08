<?php

namespace app\admin\controller\xiluxc\brand;

use app\common\controller\Backend;
use app\common\model\User;
use app\common\model\xiluxc\brand\BrandUser as BrandUserModel;
use app\common\model\xiluxc\brand\ShopAccount;
use app\common\model\xiluxc\brand\ShopBrand;
use app\common\model\xiluxc\brand\ShopTag;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\response\Json;
use function fast\array_get;

/**
 * 门店管理
 *
 * @icon fa fa-circle-o
 */
class Shop extends Backend
{
    protected $relationSearch = true;

    protected $searchFields = "name,user.mobile";

    protected $noNeedRight = ['selectpage'];
    /**
     * Shop模型对象
     * @var \app\common\model\xiluxc\brand\Shop
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
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
            $this->assignconfig('brands',ShopBrand::column("user_id,brand_name"));
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $list = $this->model
            ->with(['user'=>function($q){
                $q->withField(['id','nickname','mobile']);
            },'account'=>function($q){
                $q->withField(['id','rate','total_money','money','withdraw_money']);
            }
                ])
            ->where($where)
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
        $params = $this->preExcludeFields($params);

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

            //标签
            if($tagIds = array_get($params,'tag_ids')){
                ShopTag::setData($this->model,$tagIds);
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
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $tagIds = ShopTag::where("shop_id",$row->id)->column('tag_id');
            $this->view->assign("tag_ids",$tagIds ? implode(',',$tagIds) : '');
            $this->view->assign('user', $row->user);
            $this->view->assign('row', $row);
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
            $row->user->allowField(['nickname','mobile','password','salt'])->save($users);
            //账号存在
            $result = $row->allowField(true)->save($params);
            //创建门店钱包
            $row->account->save([
                'vip_rate'          =>  $params['vip_rate']??0,
                'rate'              =>  $params['rate']??0,
            ]);
            //标签
            if($tagIds = array_get($params,'tag_ids')){
                ShopTag::setData($row,$tagIds);
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

    /**
     * @param $ids
     * @return string
     * @throws Exception
     */
    public function audit($ids) {
        $info = $this->model->get(['id'=>$ids]);

        if (false === $this->request->isPost()) {
            $user = User::where('id',$info->user_id)->find();
            $info->append(['images_text']);
            $this->view->assign('user',$user);
            $this->view->assign('row',$info);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if(in_array($params['audit_status'],['passed','failed'])){
            $params['checktime'] = time();
        }
        Db::startTrans();
        try {
            $info->allowField(['audit_status','refuse_reason'])->save($params);
            //创建门店钱包
            if(in_array($params['audit_status'],['passed'])){
                ShopAccount::where('shop_id',$info->id)->update(['rate'=>$params['rate'],'vip_rate'=>$params['vip_rate']]);
            }
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success();
    }


    public function selectpage()
    {
        return parent::selectpage(); // TODO: Change the autogenerated stub
    }

}

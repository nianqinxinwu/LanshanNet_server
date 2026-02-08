<?php

namespace app\admin\controller\xiluxc\order;

use addons\xiluxc\library\wechat\Payment;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Hook;
use think\response\Json;

/**
 * 售后退款
 *
 * @icon fa fa-circle-o
 */
class Aftersale extends Backend
{
    protected $relationSearch = true;
    /**
     * Aftersale模型对象
     * @var \app\common\model\xiluxc\order\Aftersale
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\xiluxc\order\Aftersale;
        $this->view->assign("aftersaleTypeList", $this->model->getAftersaleTypeList());
        $this->view->assign("statusList", $this->model->getStatusList());
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
            ->with(['user'=>function($q){
                $q->withField(['id','nickname']);
            },'shop'=>function($q){
                $q->withField(['id','name']);
            },'user_package'=>function($q){
                $q->withField(['id','package_name']);
            }])
            ->where('aftersale_type','package')
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    public function edit($ids = null)
    {
        $aftersale = $this->model->get($ids);
        if (!$aftersale) {
            $this->error(__('No Results were found'));
        }
        $row = $aftersale->user_package;
        if(!$row){
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }

        if (false === $this->request->isPost()) {
            $this->view->assign('aftersale', $aftersale);
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        if($aftersale->status != '0'){
            $this->error("售后状态不可更改");
        }
        $params = $this->preExcludeFields($params);
        if($params['status'] == '1'){
            $params['refuse_reason'] = '';
            $params['agreetime'] = time();
        }else if($params['status'] == '-1'){
            $params['refusetime'] = time();
        }
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $aftersale->validateFailException()->validate($validate);
            }
            $result = $aftersale->allowField(true)->save($params);
            if($aftersale->status == '1'){

                if($aftersale->ordering['pay_type'] == '1'){
                    //微信支付
                    $payment = new Payment($aftersale->ordering->platform);
                    $res= $payment->refund($aftersale->id,$aftersale->refund_money,'用户申请退款');
                    if(!$res['status']){
                        throw new Exception($res['msg']);
                    }
                }else if($row['pay_type'] == '2'){
                    //余额支付
                    Hook::listen('xiluxc_refund_success',$aftersale);
                }
                $row->allowField(true)->save(['status'=>'refund']);//退款成功
            }else if($aftersale->status == '-1'){
                $row->allowField(true)->save(['status'=>'ing']);//退款失败，返回进行中
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

    public function add(){
        return;
    }

    public function multi($ids=null){
        return;
    }

}

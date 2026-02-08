<?php

namespace app\index\controller\xiluxc;


use app\common\controller\xiluxc\XiluxcFront;
use app\common\model\xiluxc\brand\ShopAccount;
use app\common\model\xiluxc\Divide;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Hook;
use think\response\Json;
use function fast\array_get;

/**
 * 门店提现
 *
 * @icon fa fa-circle-o
 */
class ShopWithdraw extends XiluxcFront
{
    protected $dataLimit = true;

    protected $dataLimitFieldAutoFill = true;
    /**
     * 数据限制字段
     */
    protected $dataLimitField = 'user_id';
    /**
     * CompanyWithdraw模型对象
     * @var \app\common\model\xiluxc\finance\ShopWithdraw
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\xiluxc\finance\ShopWithdraw;
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("stateList", $this->model->getStateList());
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

        if($this->brand){
            $where2['shop.brand_id'] = $this->brand->user_id;
        }else{
            $where2['shop.id'] = $this->shop->id;
        }

        $list = $this->model
            ->with(['shop'=>function($query){
                $query->withField(["id","name"]);
            }])
            ->where($where)
            ->where($where2)
            ->order($sort, $order)
            ->paginate($limit);

        if($this->brand){
            $result = array("total" => $list->total(), "rows" => $list->items(), "extend" => ['shop_money' => 0, 'freeze_money' => 0]);
        }else{
            $account = ShopAccount::addAccount($this->shop->id);
            $freeze_money = Divide::where('shop_id',$this->shop->id)->where('status',1)->sum('shop_money');
            $result = array("total" => $list->total(), "rows" => $list->items(), "extend" => ['shop_money' => $account->money, 'freeze_money' => $freeze_money]);
        }


        return json($result);
    }


    /**
     * 添加
     *
     * @return string
     * @throws \think\Exception
     */
    public function add()
    {
        if($this->brand){
            $this->error("品牌账号没有权限提现");
        }
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        $shopAccount = ShopAccount::addAccount($this->shop->id);
        $money = array_get($params,'money');
        if(!$money || $shopAccount->money<$money){
            $this->error("金额不足");
        }
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        if ($this->shop && $this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->shop->id;
        }
        $params['order_no'] = "W".date("YmdHis").mt_rand(0,99999);
        $params['shop_id'] = $this->shop->id; //提现门店
        $result = false;
        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            Hook::listen("xiluxc_shop_withdraw",$this->model);
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

}

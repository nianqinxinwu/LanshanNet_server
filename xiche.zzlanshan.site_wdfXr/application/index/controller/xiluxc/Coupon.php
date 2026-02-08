<?php

namespace app\index\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcFront;
use app\common\model\xiluxc\activity\CouponItems;
use app\common\model\xiluxc\activity\UserCoupon;
use app\common\model\xiluxc\brand\Package;
use app\common\model\xiluxc\brand\ShopService;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use function fast\array_get;

/**
 * 优惠券管理
 *
 * @icon fa fa-circle-o
 */
class Coupon extends XiluxcFront
{
    protected $searchFields = "name";

    protected $relationSearch = true;
    /**
     * Coupon模型对象
     * @var \app\common\model\xiluxc\activity\Coupon
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\xiluxc\activity\Coupon;
        $this->view->assign("freightTypeList", $this->model->getFreightTypeList());
        $this->view->assign("rangeTypeList", $this->model->getRangeTypeList());
        $this->view->assign("rangeStatusList", $this->model->getRangeStatusList());
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
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $where2 = [];
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
        $result = ['total' => $list->total(), 'rows' => $list->items()];
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
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        if ($this->shop && $this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->shop->id;
        }
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            $result = $this->model->allowField(true)->save($params);
            if($item_ids = array_get($params,'items_ids')){
                CouponItems::setCouponIds($this->model,$item_ids);
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

    public function edit($ids = null){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if (false === $this->request->isPost()) {
            $row->daterange = $row->use_start_time_text.' - '.$row->use_end_time_text;
            //使用范围,
            $target_ids = $row->range_status == 0?CouponItems::where('coupon_id',$row->id)->column('target_id'):[0];
            if($row->range_type == 1){
                $lists = ShopService::with(['service'])->whereIn('shop_service.id',$target_ids)->select();
            }else if($row->range_type == 2){
                $lists = Package::whereIn('id',$target_ids)->select();
            }else{
                $lists = [];
            }
            $this->view->assign('lists', $lists);
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            $result = $row->allowField(true)->save($params);
            $items_ids = array_get($params,'items_ids');
            CouponItems::setCouponIds($row,$items_ids);

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
     * 发放日志
     */
    public function coupon_log(){
        $this->model = new UserCoupon();
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->with(['user'=>function($q){
                    $q->withField(['id','nickname','mobile','avatar']);
                }])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
//            foreach ($list as $k => $v) {
//                $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
//                $v->hidden(['password', 'salt']);
//            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

}

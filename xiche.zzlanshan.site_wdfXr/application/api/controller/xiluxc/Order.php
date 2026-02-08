<?php


namespace app\api\controller\xiluxc;


use addons\xiluxc\library\wechat\Payment;
use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\xiluxc\brand\ShopVerifier;
use app\common\model\xiluxc\order\Aftersale;
use app\common\model\xiluxc\order\Order AS OrderModel;
use app\common\model\xiluxc\order\OrderLog;
use app\common\model\xiluxc\order\OrderQrcode;
use app\common\model\xiluxc\user\UserPackageService;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\Hook;
use function fast\array_get;

class Order extends XiluxcApi
{
    /**
     * 下单信息查询
     */
    public function pre_order(){
        $params = $this->request->post('');
        try {
            $row = OrderModel::preOrder($params,$this->auth->id);
        }catch (Exception $e){
            $this->error($e->getMessage());
        }
        $this->success('',$row);
    }

    /**
     * 下单
     */
    public function create_order(){
        $params = $this->request->post('');
        $user = $this->auth->getUser();
        if($user->status != 'normal') {
            throw new Exception('账户已被禁用');
        }
        try {
            $result = OrderModel::createOrder($params,$user->id);
            #订单日志
            OrderLog::operate_add($result['type']=='service'?OrderLog::TYPE_SERVICE : OrderLog::TYPE_PACKAGE,$result, null, $user, 'user', [
                'title'     => '买家下单',
                'description' => '买家下单成功，等待买家支付',
                'images'    =>  ''
            ]);
        }catch (Exception $e){
            $this->error($e->getMessage(),'',$e->getCode());
        }
        $this->success('',$result);
    }

    /**
     * 订单列表
     */
    public function lists(){
        $params = $this->request->param('');
        $list = OrderModel::orderLists($params, $this->auth->id);
        $this->success('',$list);
    }

    /**
     * 订单详情
     */
    public function detail(){
        $params = $this->request->param('');
        $params['platform'] = $this->platform;
        try {
            $row = OrderModel::detail($params);
        }catch (Exception|PDOException $e){
            $this->error($e->getMessage());
        }

        $this->success('',$row);
    }


    /**
     * 核销订单确认信息
     */
    public function order_confirm(){
        $token = $this->request->post('token');
        $qrcode = OrderQrcode::where('token',$token)->find();
        if(!$qrcode){
            $this->error("核销码未找到");
        }

        if($qrcode->verifier_status == 1){
            $this->error("券码已核销，请勿重复核销");
        }
        $order = OrderModel::get($qrcode->order_id);
        if(!$order){
            $this->error("订单不存在");
        }
        if($order->state != 0){
            $this->error("订单状态不可核销");
        }
        //判断我是否可以核销
        $verifier = ShopVerifier::isVerifier($this->auth->mobile);
        if(!$verifier || $verifier['shop_id'] != $order['shop_id']){
            $this->error("不是门店核销员，不可核销");
        }
        $order->relationQuery(['order_item','shop']);
        $this->success('',['order'=>$order,'qrcode'=>$qrcode]);
    }

    /**
     * @title 扫码核销
     * @description 扫码核销
     */
    public function verifier_order(){
        $token = $this->request->post('token');
        $qrcode = OrderQrcode::where('token',$token)->find();
        if(!$qrcode){
            $this->error("核销码未找到");
        }
        if($qrcode->verifier_status == 1){
            $this->error("券码已核销，请勿重复核销");
        }
        $order = OrderModel::get($qrcode->order_id);
        if(!$order){
            $this->error("订单不存在");
        }
        if($order->state != 0){
            $this->error("订单状态不可核销");
        }
        //判断我是否可以核销
        $verifier = ShopVerifier::isVerifier($this->auth->mobile);
        if(!$verifier || $verifier['shop_id'] != $order['shop_id']){
            $this->error("不是门店核销员，不可核销");
        }
        Db::startTrans();
        try {
            $qrcode->verifier_status = 1;
            $qrcode->verifier_id = $this->auth->id;
            $qrcode->verifytime = time();
            $qrcode->save();
            #判断是否全部核销完毕
            $order->verify_status = '1';
            $order->verifytime = time();
            $order->allowField(['verify_status','verifytime'])->save();
            #给佣金
            $params = [
                'type'  =>  'service_order',
                'order' =>  $order
            ];
            Hook::listen("xiluxc_service_calculate",$params);
            #核销成功消息
            $ret = [
                'order' =>  $order,
                'qrcode'=>  $qrcode
            ];
            Hook::listen("xiluxc_service_verifier_message",$ret);
        }catch (Exception|PDOException $e){
            Db::rollback();
            $this->error($e->getMessage());
        }
        Db::commit();
        $this->success('核销成功');
    }

    /**
     * 核销订单列表
     */
    public function verifier_list(){
        $params = $this->request->param('');
        $pagesize = array_get($params, 'pagesize', 10);
        $userId = $this->auth->id;
        $list = OrderModel::alias("order")
            ->field("order.*,order_qrcode.verifier_id")
            ->join("xiluxc_order_qrcode order_qrcode",'order_qrcode.order_id=order.id')
            ->where('order_qrcode.verifier_status','1')
            ->where('order_qrcode.verifier_id',$userId)
            ->group("order_qrcode.order_id")
            ->order('order_qrcode.verifytime','desc')
            ->paginate($pagesize)
            ->each(function ($row) use($userId){
                $row->relationQuery(['order_item']);
                $row->user = \app\common\model\User::where("id",$row->verifier_id)->field("id,nickname,avatar")->find();
                $row->verifytime_text = datetime($row->verifytime,"Y-m-d H:i");
            });
        $this->success('',$list);
    }


    /**
     * 核销订单详情
     */
    public function verify_detail(){
        $params = $this->request->param('');
        try {
            $row = OrderModel::verifyDetail($params);
        }catch (Exception|PDOException $e){
            $this->error($e->getMessage());
        }

        $this->success('',$row);
    }


    /**
     * 退款
     * @param null $ids
     */
    public function aftersale(){
        $id = $this->request->post('id');
        $order = new OrderModel();

        $row = $order->where("id",$id)
            ->where("user_id",$this->auth->id)
            ->find();
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if($row->state != '0'){
            $this->error("状态不可取消");
        }
        $aftersale = new Aftersale();
        if($aftersale->where("order_id",$row->id)->where("aftersale_type",'service')->count()>0){
            $this->error("不要重复取消");
        }

        $infoParams = [
            'aftersale_type'    =>  'service',
            'order_no'          =>  'A'.date('YmdHis').mt_rand(0,9999),
            'user_id'           =>  $row->user_id,
            'user_package_id'   =>  0,
            'order_id'          =>  $row->id,
            'refund_money'      =>  $row->pay_fee,
            'shop_id'           =>  $row->shop_id,
            'brand_id'          =>  $row->brand_id,
            'status'            =>  '1',
            'agreetime'         =>  time(),
        ];
        Db::startTrans();
        try {
            #1.创建售后订单
            $result = $aftersale->allowField(true)->save($infoParams);
            #2.根据支付方式，原路退还
            if($row['pay_type'] == '1'){
                $payment = new Payment($row->platform);
                $res= $payment->refund($aftersale->id,$aftersale->refund_money,'用户申请退款');
                if(!$res['status']){
                    throw new Exception($res['msg']);
                }
            }else if($row['pay_type'] == '2'){
                Hook::listen('xiluxc_refund_success',$aftersale);
            }else{
                //退套餐次数
                $userPackage = \app\common\model\xiluxc\user\UserPackage::where("id",$row->user_package_id)->find();
                if(!$userPackage){
                    throw new Exception("套餐不存在");
                }
                if($userPackage->status != 'ing'){
                    throw new Exception("套餐状态异常");
                }
                $userPackageService = UserPackageService::where('user_package_id',$userPackage->id)->where("service_price_id",$row->order_item->service_price_id)->find();
                if(!$userPackageService){
                    throw new Exception("未找到套餐服务");
                }
                $userPackageService->allowField(true)->save(['stock'=>Db::raw("stock+1"),'use_count'=>Db::raw("use_count-1")]);
            }
            $ext = $row->ext ? json_decode($row->ext,true): [];
            $ext['canceltime'] = time();
            $row->allowField(true)->save(['status'=>'cancel','ext'=>json_encode($ext)]);//申请退款
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        Db::commit();
        $row->relationQuery(['order_item','shop'=>function($q){
            $q->field(['id','name','image']);
        }]);
        $row['shop'] && $row->shop->append(['image_text']);
        $this->success('取消成功',$row);

    }

}
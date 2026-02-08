<?php

namespace app\api\controller\xiluxc;

use addons\xiluxc\library\Wechat;
use addons\xiluxc\library\wechat\Payment;
use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\xiluxc\order\RechargeOrder;
use app\common\model\xiluxc\order\VipOrder;
use app\common\model\xiluxc\order\Order;
use app\common\model\xiluxc\user\Third;
use app\common\model\xiluxc\current\Config;
use app\common\model\xiluxc\user\UserPackageService;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\Hook;
use function fast\array_get;

class Pay extends XiluxcApi
{
    protected $noNeedRight = ['*'];
    protected $noNeedLogin = ['notify'];

    /**
     * 支付
     */
    public function pay()
    {
        $params = $this->request->post('');
        $platform = array_get($params, 'platform', 'wxmini');
        $order_id = array_get($params, 'order_id');
        $pay_type = array_get($params, 'pay_type');
        if (!$order_id || !$pay_type) {
            $this->error('参数错误');
        }
        $config = Config::getMyConfig('wxpayment');
        if (!$config || !$config['mch_id'] || !$config['mch_key']) {
            $this->error("请正确配置微信商户信息");
        }
        $type = array_get($params, 'type');
        switch ($type) {
            case 'vip':
                $orderinfo = VipOrder::get($order_id);
                if ($platform !== $orderinfo['platform']) {
                    $this->error("下单端与支付端不一致");
                }
                if ($orderinfo && $orderinfo->pay_status == 'paid') {
                    $this->error("不要重复支付");
                }
                $orderinfo->pay_type = $pay_type;
                $orderinfo->order_trade_no = 'V' . date('YmdHis') . mt_rand(10, 9999);
                $orderinfo->save();
                $order_no = $orderinfo['order_trade_no'];
                $pay_price = $orderinfo['pay_fee'];
                $notify_url = request()->domain().'/api/xiluxc.pay/notify/table/vip';
                break;
            case 'recharge':
                $orderinfo = RechargeOrder::get($order_id);
                if ($platform !== $orderinfo['platform']) {
                    $this->error("下单端与支付端不一致");
                }
                if ($orderinfo && $orderinfo->pay_status == 'paid') {
                    $this->error("不要重复支付");
                }
                $orderinfo->pay_type = $pay_type;
                $orderinfo->order_trade_no = 'SC' . date('YmdHis') . mt_rand(10, 99999);
                $orderinfo->save();
                $order_no = $orderinfo['order_trade_no'];
                $pay_price = $orderinfo['pay_fee'];
                $notify_url = request()->domain().'/api/xiluxc.pay/notify/table/recharge';
                break;
            case 'order':
                $orderinfo = Order::get($order_id);
                if ($platform !== $orderinfo['platform']) {
                    $this->error("下单端与支付端不一致");
                }
                if ($orderinfo && $orderinfo->status == 'paid') {
                    $this->error("不要重复支付");
                }
                $orderinfo->pay_type = $pay_type;
                if($pay_type == 3){
                    $user_package_id = array_get($params,'user_package_id');
                    $userPackage = $user_package_id ? \app\common\model\xiluxc\user\UserPackage::get(['user_id'=>$this->auth->id,'id'=>$user_package_id]) : null;
                    if(!$userPackage){
                        $this->error("未找到可用套餐");
                    }
                    $userPackageService = UserPackageService::where('user_package_id',$userPackage->id)->where('service_price_id',$orderinfo->order_item->service_price_id)->find();
                    if(!$userPackageService){
                        $this->error("未找到可抵扣套餐");
                    }
                    $orderinfo->user_package_id = $userPackage->id;
                }

                $orderinfo->order_trade_no = 'SP' . date('YmdHis') . mt_rand(10, 99999);
                $orderinfo->save();
                $order_no = $orderinfo['order_trade_no'];
                $pay_price = $orderinfo['pay_fee'];
                $notify_url = request()->domain().'/api/xiluxc.pay/notify/table/order';
                break;
            default:
                $this->success("当前模块不支持支付");
        }
        if ($pay_type == 1) {
            #微信支付,查看是什么平台订单
            if ($platform == 'wxmini') {
                $openid = Third::where('user_id', $this->auth->id)->where('platform', $platform)->value('openid');
            }else if($platform == 'wxoffical'){
                $openid = array_get($params,'openid');
            }else{
                $this->error("支付平台错误");
            }
            if(!$openid){
                $this->error("支付参数错误");
            }
            
         
            try {
                $wechat = new Wechat($platform);
                $orderData = [
                    'body'      =>  '购买商品',
                    'order_no'  =>  $order_no,
                    'pay_price' =>  $pay_price,
                    //'pay_price' =>  0.01,
                    'notify_url' =>  $notify_url,
                    'openid'    =>  $openid,
                ];
              
                $result3 = $wechat->union_order($orderData);
                
                //   var_dump('11111111113333='.$result3);exit;
            }catch (Exception $e){
                $this->error($e->getMessage());
            }
            $this->success('创建微信订单成功', $result3);

        }
        else if($pay_type == 2){
            //余额支付
            Db::startTrans();
            try {
                Hook::listen("xiluxc_money_pay",$orderinfo);
                Order::payNotify($order_no,'balance');
            }catch (Exception|PDOException $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
            Db::commit();
            $this->success("支付成功",$orderinfo);
        }
        else if($pay_type == 3){
            //套餐支付
            //判断此数是否足够
            if($userPackageService->stock - 1 < 0){
                $this->error("次数不足以抵扣");
            }
            Db::startTrans();
            try {
                if($userPackageService->stock - 1 <= 0){
                    $userPackageService->status = 'finished';
                }
                $userPackageService->stock = Db::raw("stock-1");
                $userPackageService->use_count = Db::raw("use_count+1");
                $userPackageService->save();
                $total_count = $count = 0;
                foreach ($userPackage->package_service as $v){
                    $total_count++;
                    if($v['status'] == 'finished'){
                        $count++;
                    }
                }
                if($total_count == $count){
                    $userPackage->allowField(['status'=>'finished']);
                }
                Order::payNotify($order_no,'package');
            }catch (Exception|PDOException $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
            Db::commit();
            $this->success("支付成功",$orderinfo);
        }
        else {
            $this->success("暂不支持当前支付类型");
        }
    }

    /**
     * 支付回调
     */
    /**
     * 支付回调
     */
    public function notify()
    {
        $payment = new Payment($this->platform);
        $table = input('table','order');
        $response = $payment->getPayment()->handlePaidNotify(function ($message, $fail) use($table){
            // 你的逻辑
            $order_no = $message['out_trade_no'];
            $trade_no = $message['transaction_id'];
            Db::startTrans();
            switch($table){
                case 'recharge':
                    try {
                        RechargeOrder::payNotify($order_no,$trade_no);
                    }catch (Exception $e){
                        Db::rollback();;
                        return $e->getMessage();
                    }
                    break;
                case 'vip':
                    try {
                        VipOrder::payNotify($order_no,$trade_no);
                    }catch (Exception $e){
                        Db::rollback();;
                        return $e->getMessage();
                    }
                    break;
                case 'order':
                    try {
                        Order::payNotify($order_no,$trade_no);
                    }catch (Exception $e){
                        Db::rollback();;
                        return $e->getMessage();
                    }
                    break;
            }
            Db::commit();
            return true;

        });
        $response->send();
        return;
    }

}
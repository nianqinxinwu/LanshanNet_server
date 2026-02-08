<?php

namespace app\common\model\xiluxc\order;

use app\common\model\User;
use app\common\model\xiluxc\brand\Shop;
use app\common\model\xiluxc\user\UserShopVip;
use think\Exception;
use think\Hook;
use think\Model;


class VipOrder extends Model
{

    

    

    // 表名
    protected $name = 'xiluxc_vip_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        //'paytime_text'
    ];
    

    
    public function getPayTypeList()
    {
        return ['1' => __('Pay_type 1'), '2' => __('Pay_type 2'), '3' => __('Pay_type 3')];
    }

    public function getPayStatusList()
    {
        return ['unpaid' => __('Pay_status unpaid'), 'paid' => __('Pay_status paid')];
    }


    public function getPaytimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['paytime']) ? $data['paytime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','id',[],'left')->setEagerlyType(0);
    }

    public function shop(){
        return $this->belongsTo(Shop::class,'shop_id','id',[],'left')->setEagerlyType(0);
    }

    /**
     * 支付成功
     */
    public static function payNotify($order_no,$trade_no){
        $order = self::where('order_trade_no',$order_no)->find();
        if(!$order){
            throw new Exception("订单不存在");
        }
        if($order->pay_status == 'paid'){
            throw new Exception("不要重复支付");
        }
        $order->trade_no = $trade_no;
        $order->pay_status = 'paid';
        $order->paytime = time();
        $order->save();
        #支付成功，添加会员卡
        UserShopVip::addVip($order);
        #新增积分
        $params = [
            'type'  =>  'vip_order',
            'order' =>  $order
        ];
        Hook::listen("xiluxc_add_score",$params);
        #订单佣金
        Hook::listen("xiluxc_vip_calculate",$params);

        //添加门店会员
        $data = ['order'=>$order];
        Hook::listen('xiluxc_shop_user',$data);
        return true;
    }


}

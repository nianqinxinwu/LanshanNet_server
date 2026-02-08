<?php

namespace app\common\model\xiluxc\activity;

use app\common\model\xiluxc\brand\Shop;
use app\common\model\xiluxc\current\Config;
use app\common\model\xiluxc\user\User;
use think\Model;


class UserCoupon extends Model
{

    

    

    // 表名
    protected $name = 'xiluxc_user_coupon';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    /**
     * 获取状态
     * @param $value
     * @param $data
     * @return false|string
     */
    public function getStateAttr($value, $data)
    {
        if($data['use_status'] == 1){
            $value = 2; //已使用
        }else if($this->coupon['use_end_time']<Config::getTodayTime()){
            $value = 3; //已过期
        }else{
            $value = 1;
        }
        return $value;
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','id',[],'left')->setEagerlyType(0);
    }

    public function coupon(){
        return $this->belongsTo(Coupon::class,'coupon_id','id',[],'inner')->setEagerlyType(0);
    }

    public function shop(){
        return $this->belongsTo(Shop::class,'shop_id','id',[],'inner')->setEagerlyType(0);
    }

    //获取查询条件
    public static function buildWhere($tab){
        $where = [
            '1'=>['coupon.status'=>'normal','use_status'=>0,'coupon.use_start_time'=>['elt',Config::getTodayTime()
            ],'coupon.use_end_time'=>['egt',Config::getTodayTime()]],
            '2'=>['use_status'=>1],
            '3'=>['use_status'=>0,'coupon.use_end_time'=>['lt',Config::getTodayTime()]],
        ];
        if(isset($where[$tab])){
            return $where[$tab];
        }
        return [];
    }

    /**
     * 优惠券数量
     */
    public static function getCount($where){
        return self::with(['coupon'])->where($where)->where('use_start_time','<=',Config::getTodayTime())->where('use_end_time','>=',Config::getTodayTime())->count(1);
    }

    /**
     * 优惠券是否领取
     * @param $userId
     * @param $couponId
     */
    public static function isReceive($userId,$couponId){
        return self::where('user_id',$userId)->whereIn('coupon_id',$couponId)->count(1);
    }

}

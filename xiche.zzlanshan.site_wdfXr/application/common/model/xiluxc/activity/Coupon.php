<?php

namespace app\common\model\xiluxc\activity;

use app\common\model\xiluxc\brand\Shop;
use app\common\model\xiluxc\current\Config;
use think\Model;


class Coupon extends Model
{

    // 表名
    protected $name = 'xiluxc_coupon';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'use_start_time_text',
        'use_end_time_text',
//        'freight_type_text',
//        'range_status_text',
//        'status_text'
    ];


    protected static function init()
    {
       self::beforeWrite(function ($row){
           $change = $row->getChangedData();
           if(isset($change['daterange'])){
               $daterange = explode(' - ',$change['daterange']);
                $row->use_start_time = $daterange[0];
                $row->use_end_time = $daterange[1];
           }

       });
       self::afterUpdate(function ($row){
           $change = $row->getChangedData();
           if(isset($change['range_type']) || (isset($change['range_status']) && $change['range_status'] == 1)){
               //修改了使用类型或使用范围是通用，先删除所有优惠券关联
               CouponItems::where('coupon_id',$row->id)->delete();
           }
       });
    }


    public function getFreightTypeList()
    {
        return ['1' => __('Freight_type 1')];
//        return ['1' => __('Freight_type 1'), '2' => __('Freight_type 2')];
    }

    public function getRangeTypeList()
    {
        return ['1' => __('Range_type 1'), '2' => __('Range_type 2')];
    }

    public function getRangeStatusList()
    {
        return ['0' => __('Range_status 0'), '1' => __('Range_status 1')];
    }

    public function getStatusList()
    {
        return ['hidden' => __('Status hidden'), 'normal' => __('Status normal')];
    }


    public function getUseStartTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['use_start_time']) ? $data['use_start_time'] : '');
        return is_numeric($value) ? date("Y-m-d", $value) : $value;
    }


    public function getUseEndTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['use_end_time']) ? $data['use_end_time'] : '');
        return is_numeric($value) ? date("Y-m-d", $value) : $value;
    }


    public function getFreightTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['freight_type']) ? $data['freight_type'] : '');
        $list = $this->getFreightTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getRangeTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['range_type']) ? $data['range_type'] : '');
        $list = $this->getRangeTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getRangeStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['range_status']) ? $data['range_status'] : '');
        $list = $this->getRangeStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setUseStartTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setUseEndTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    public function shop(){
        return $this->belongsTo(Shop::class,'shop_id','id',[],'left')->setEagerlyType(0);
    }

    public function couponItems(){
        return $this->hasMany(CouponItems::class,'coupon_id','id');
    }

    public function scopeNormal($query){
        return $query->where("status","normal");
    }

    /**
     * 获取门店优惠券
     * @param $shopId
     * @param int $targetId
     * @param null $userId
     * @return bool|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getCoupons($shopId,$userId=NULL){
        $time = Config::getTodayTime();
        $list =  self::alias('coupon')
            ->field("coupon.id,coupon.name,coupon.max_count,coupon.at_least,coupon.money,coupon.range_status,coupon.use_start_time,coupon.use_end_time")
            ->join("XiluxcCouponItems coupon_items",'coupon_items.coupon_id=coupon.id','LEFT')
            ->where("freight_type",1)
            ->where('shop_id',$shopId)
            ->where('status','normal')
            ->where('use_start_time','<=', $time)
            ->where('use_end_time','>=', $time)
//            ->where(function ($q) use($targetId){
//                $q->where("range_status",1)->whereOr("range_status=0 AND target_id=".$targetId);
//            })
            ->group("coupon.id")
            ->select();

        foreach ($list as $row){
            $row->setAttr("is_receive_count",$userId?UserCoupon::isReceive($userId,$row->id):0);
        }
        return $list;
    }


    /**
     * 获取下单优惠券
     * @param $rangeType
     * @param int $targetId
     * @param null $userId
     * @return array|bool|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getUserCouponsByType($rangeType,$targetId=0,$userId=NULL,$payPrice=0){
        $time = Config::getTodayTime();
        $list =  self::alias('coupon')
            ->field("coupon.id,coupon.name,coupon.max_count,coupon.at_least,coupon.money,coupon.range_status,coupon.use_end_time,coupon.use_start_time")
            ->join("XiluxcCouponItems coupon_items",'coupon_items.coupon_id=coupon.id','LEFT')
            ->join("XiluxcUserCoupon user_coupon",'user_coupon.coupon_id=coupon.id','LEFT')
            ->where("freight_type",1)
            ->where("at_least",'<=',$payPrice)
            ->where("money",'<',$payPrice)
            ->where("range_type",$rangeType)
            ->where('status','normal')
            ->where('use_start_time','<=', $time)
            ->where('use_end_time','>=', $time)
            ->where(function ($q) use($targetId){
                $q->where("range_status",1)->whereOr("range_status=0 AND target_id=".$targetId);
            })
            ->where('user_coupon.user_id',$userId)
            ->where("user_coupon.use_status",0)
            ->order("coupon.money",'desc')
            ->select();
        return $list;
    }

}

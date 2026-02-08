<?php


namespace app\common\model\xiluxc\user;


use app\common\model\xiluxc\car\CarBrand;
use app\common\model\xiluxc\car\CarModels;
use app\common\model\xiluxc\car\CarSeries;
use think\Model;

class UserCar extends Model
{
    protected $name = 'xiluxc_user_car';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    protected $append = [

    ];


    protected function setRegisterTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    public function brand(){
        return $this->belongsTo(CarBrand::class,'brand_id','id',[],'inner')->setEagerlyType(0);
    }

    public function series(){
        return $this->belongsTo(CarSeries::class,'series_id','id',[],'inner')->setEagerlyType(0);
    }

    public function models(){
        return $this->belongsTo(CarModels::class,'models_id','id',[],'inner')->setEagerlyType(0);
    }
}
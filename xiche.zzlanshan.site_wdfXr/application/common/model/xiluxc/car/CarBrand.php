<?php

namespace app\common\model\xiluxc\car;

use think\Model;
use traits\model\SoftDelete;

class CarBrand extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'xiluxc_car_brand';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'image_text'
    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            if (!$row['weigh']) {
                $pk = $row->getPk();
                $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
            }
        });
    }

    
    public function getStatusList()
    {
        return ['hidden' => __('Status hidden'), 'normal' => __('Status normal')];
    }

    public function getImageTextAttr($value,$data){
        $value = isset($data['image']) && $data['image'] ? $data['image'] : '';
        return $value?cdnurl($value,true):'';
    }

    public function scopeNormal($query){
        $query->where("status",'normal');
    }

    public function brands(){
        return $this->hasMany(self::class,'first_letter','first_letter')->where("status",'normal');
    }

    public function series(){
        return $this->hasMany(CarSeries::class,'brand_id','id')->where("status",'normal');
    }



}

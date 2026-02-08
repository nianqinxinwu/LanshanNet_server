<?php

namespace app\common\model\xiluxc\brand;

use app\common\model\xiluxc\service\Service;
use think\Model;


class Package extends Model
{

    

    

    // 表名
    protected $name = 'xiluxc_shop_package';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        "image_text"
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
        return $query->where("status","normal");
    }

    public function brand(){
        return $this->belongsTo(ShopBrand::class,'brand_id','id',[],'left')->setEagerlyType(0);
    }

    public function shop(){
        return $this->belongsTo(Shop::class,'shop_id','id',[],'left')->setEagerlyType(0);
    }

    public function packageService(){
        return $this->belongsToMany(Service::class,PackageService::class,'service_id','package_id');
    }

    public function packageService2(){
        return $this->hasMany(PackageService::class,'package_id','id');
    }


}

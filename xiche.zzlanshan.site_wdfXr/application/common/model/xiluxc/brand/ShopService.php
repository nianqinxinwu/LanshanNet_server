<?php

namespace app\common\model\xiluxc\brand;

use app\common\model\xiluxc\service\Service;
use think\model\Pivot;


class ShopService extends Pivot
{

    

    

    // 表名
    protected $name = 'xiluxc_shop_service';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'image_text'
    ];

    
    public function getStatusList()
    {
        return ['hidden' => __('Status hidden'), 'normal' => __('Status normal')];
    }

    public function getImageTextAttr($value,$data){
        $value = isset($data['image']) && $data['image'] ? $data['image'] : '';
        return $value?cdnurl($value,true):'';
    }


    public function scopeNormal($query)
    {
        return $query->where("status",'normal');
    }

    public function brand(){
        return $this->belongsTo(ShopBrand::class,'brand_id','id',[],'left')->setEagerlyType(0);
    }

    public function shop(){
        return $this->belongsTo(Shop::class,'shop_id','id',[],'left')->setEagerlyType(0);
    }

    public function service(){
        return $this->belongsTo(Service::class,'service_id','id',[],'left')->setEagerlyType(0);
    }

    public function servicePrice(){
        return $this->hasMany(ShopServicePrice::class,'shop_service_id','id')->order("salesprice","asc");
    }

    /**
     * 关联属性
     * @param $shop
     * @param $tagIds
     * @return array|bool|false|\think\Collection|\think\model\Collection
     * @throws \think\Exception
     */
    public static function setData($shop,$serviceIds){
        if($serviceIds) {
            $insertServiceIds = [];
            $serviceIds = is_array($serviceIds) ? $serviceIds : explode(',', $serviceIds);
            self::whereNotIn('service_id', $serviceIds)->where('shop_id', $shop->id)->delete();
            foreach ($serviceIds as &$serviceId) {
                if(self::where('shop_id',$shop->id)->where('service_id',$serviceId)->count()<1)
                    $insertServiceIds[] = ['shop_id'=>$shop->id, 'service_id'=>$serviceId];
            }
            if($insertServiceIds) {
                return (new self())->saveAll($insertServiceIds);
            }
        }else {
            self::where('shop_id', $shop->id)->delete();
        }
    }

}

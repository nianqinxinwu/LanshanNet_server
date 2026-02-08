<?php

namespace app\common\model\xiluxc\brand;

use app\common\model\xiluxc\service\Service;
use think\Model;
use think\model\Pivot;


class PackageService extends Pivot
{

    

    

    // 表名
    protected $name = 'xiluxc_shop_package_service';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    public function shopService(){
        return $this->belongsTo(ShopService::class,'shop_service_id','id',[],'inner')->setEagerlyType(0);
    }

    public function service(){
        return $this->belongsTo(Service::class,'service_id','id',[],'inner')->setEagerlyType(0);
    }

    public function servicePrice(){
        return $this->belongsTo(ShopServicePrice::class,'service_price_id','id',[],'inner')->setEagerlyType(0);
    }

    /**
     * 关联服务
     * @param $shopPackage
     * @param $services
     * @return array|bool|false|\think\Collection|\think\model\Collection
     * @throws \think\Exception
     */
    public static function setService($shopPackage,$services){
        if($services) {
            $insertServices = [];
            $services = is_array($services) ? $services : json_decode($services,true);
            $ids = array_column($services,"service_id");
            self::whereNotIn("service_id",$ids)->where('package_id', $shopPackage->id)->delete();
            foreach ($services as $service) {
                if($id = self::where('package_id',$shopPackage->id)->where('service_id',$service['service_id'])->value("id")){
                    $service['id'] = $id;
                }
                $service['package_id'] = $shopPackage->id;
                $insertServices[] = $service;
            }
            if($insertServices) {
                return (new self())->saveAll($insertServices);
            }
        }else {
            self::where('package_id', $shopPackage->id)->delete();
        }
    }

}

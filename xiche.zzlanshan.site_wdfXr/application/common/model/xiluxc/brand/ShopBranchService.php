<?php

namespace app\common\model\xiluxc\brand;

use app\common\model\xiluxc\service\Service;
use think\Model;


class ShopBranchService extends Model
{

    

    

    // 表名
    protected $name = 'xiluxc_shop_branch_service';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    
    public function getStatusList()
    {
        return ['hidden' => __('Status hidden'), 'normal' => __('Status normal')];
    }

    public function scopeNormal($query)
    {
        return $query->where("status",'normal');
    }

    public function shopService(){
        return $this->belongsTo(ShopService::class,'shop_service_id','id',[],'inner')->setEagerlyType(0);
    }

    public function service(){
        return $this->belongsTo(Service::class,'service_id','id',[],'inner')->setEagerlyType(0);
    }

    /**
     * 分店/单店
     * @param $shopService
     * @param $shopIds
     * @return array|bool|false|\think\Collection|\think\model\Collection
     * @throws \think\Exception
     */
    public static function setData($shopService,$shopIds){
        if($shopIds) {
            $insertIds = [];
            $shopIds = is_array($shopIds) ? $shopIds : explode(',', $shopIds);
            self::whereNotIn('shop_id', $shopIds)->where('shop_service_id', $shopService->id)->delete();
            foreach ($shopIds as &$shopId) {
                if(self::where('shop_service_id',$shopService->id)->where('shop_id',$shopId)->count()<1)
                    $insertIds[] = ['shop_service_id'=>$shopService->id, 'shop_id'=>$shopId,'service_id'=>$shopService->service_id];
            }
            if($insertIds) {
                return (new self())->saveAll($insertIds);
            }
        }else {
            self::where('shop_service_id', $shopService->id)->delete();
        }
    }

}

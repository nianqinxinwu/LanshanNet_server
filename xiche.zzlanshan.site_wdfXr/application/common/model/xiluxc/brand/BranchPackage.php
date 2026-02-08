<?php

namespace app\common\model\xiluxc\brand;

use think\Model;


class BranchPackage extends Model
{

    

    

    // 表名
    protected $name = 'xiluxc_shop_branch_package';
    
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

    public function shopPackage(){
        return $this->belongsTo(Package::class,'shop_package_id','id',[],'inner')->setEagerlyType(0);
    }

    /**
     * 分店/单店
     * @param $shopPackage
     * @param $shopIds
     * @return array|bool|false|\think\Collection|\think\model\Collection
     * @throws \think\Exception
     */
    public static function setData($shopPackage,$shopIds){
        if($shopIds) {
            $insertIds = [];
            $shopIds = is_array($shopIds) ? $shopIds : explode(',', $shopIds);
            self::whereNotIn('shop_id', $shopIds)->where('shop_package_id', $shopPackage->id)->delete();
            foreach ($shopIds as &$shopId) {
                if(self::where('shop_package_id',$shopPackage->id)->where('shop_id',$shopId)->count()<1)
                    $insertIds[] = ['shop_package_id'=>$shopPackage->id, 'shop_id'=>$shopId];
            }
            if($insertIds) {
                return (new self())->saveAll($insertIds);
            }
        }else {
            self::where('shop_service_id', $shopPackage->id)->delete();
        }
    }

}

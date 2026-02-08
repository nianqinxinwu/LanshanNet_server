<?php

namespace app\common\model\xiluxc\brand;

use app\common\model\xiluxc\service\Service;
use think\Model;


class ShopServicePrice extends Model
{

    

    

    // 表名
    protected $name = 'xiluxc_shop_service_price';
    
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

    /**
     * 关联规格价格
     * @param $shopService
     * @param $skus
     * @return array|bool|false|\think\Collection|\think\model\Collection
     * @throws \think\Exception
     */
    public static function setPrices($shopService,$skus){
        if($skus) {
            $insertSkus = [];
            $skus = is_array($skus) ? $skus : json_decode($skus,true);
            $ids = array_column($skus,"id");
            self::whereNotIn("id",$ids)->where('shop_service_id', $shopService->id)->delete();
            foreach ($skus as $sku) {
                if(!$sku['id']){
                    unset($sku['id']);
                }
                $sku['shop_id'] = $shopService->shop_id ?? 0;
                $sku['brand_id'] = $shopService->brand_id ?? 0;
                $sku['service_id'] = $shopService->service_id;
                $sku['shop_service_id'] = $shopService->id;
                $insertSkus[] = $sku;
            }
            if($insertSkus) {
                return (new self())->saveAll($insertSkus);
            }
        }else {
            self::where('shop_service_id', $shopService->id)->delete();
        }
    }

}

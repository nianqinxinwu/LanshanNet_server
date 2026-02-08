<?php


namespace app\common\model\xiluxc\brand;


use think\Model;

class ShopProperty extends Model
{
    // 表名
    protected $name = 'xiluxc_shop_property';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;



    /**
     * 关联属性
     * @param $shop
     * @param $tagIds
     * @return array|bool|false|\think\Collection|\think\model\Collection
     * @throws \think\Exception
     */
    public static function setData($shop,$propertyIds){
        if($propertyIds) {
            $insertPropertyIds = [];
            $propertyIds = is_array($propertyIds) ? $propertyIds : explode(',', $propertyIds);
            self::whereNotIn('property_id', $propertyIds)->where('shop_id', $shop->id)->delete();
            foreach ($propertyIds as &$propertyId) {
                if(self::where('shop_id',$shop->id)->where('property_id',$propertyId)->count()<1)
                    $insertPropertyIds[] = ['shop_id'=>$shop->id, 'property_id'=>$propertyId];
            }
            if($insertPropertyIds) {
                return (new self())->saveAll($insertPropertyIds);
            }
        }else {
            self::where('shop_id', $shop->id)->delete();
        }
    }


}
<?php


namespace app\common\model\xiluxc\brand;

use think\model\Pivot;

class ShopTag extends Pivot
{
    // 表名
    protected $name = 'xiluxc_shop_tag';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;



    /**
     * 关联标签
     * @param $shop
     * @param $tagIds
     * @return array|bool|false|\think\Collection|\think\model\Collection
     * @throws \think\Exception
     */
    public static function setData($shop,$tagIds){
        if($tagIds) {
            $insertTagIds = [];
            $tagIds = is_array($tagIds) ? $tagIds : explode(',', $tagIds);
            self::whereNotIn('tag_id', $tagIds)->where('shop_id', $shop->id)->delete();
            foreach ($tagIds as &$tagId) {
                if(self::where('shop_id',$shop->id)->where('tag_id',$tagId)->count()<1)
                    $insertTagIds[] = ['shop_id'=>$shop->id, 'tag_id'=>$tagId];
            }
            if($insertTagIds) {
                return (new self())->saveAll($insertTagIds);
            }
        }else {
            self::where('shop_id', $shop->id)->delete();
        }
    }



}
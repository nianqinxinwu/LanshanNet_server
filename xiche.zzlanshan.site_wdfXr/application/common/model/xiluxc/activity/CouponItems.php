<?php

namespace app\common\model\xiluxc\activity;

use think\Model;


class CouponItems extends Model
{

    

    

    // 表名
    protected $name = 'xiluxc_coupon_items';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];



    /**
     * 关联活动
     * @param $coupon
     * @param $itemIds
     * @return array|bool|false|\think\Collection|\think\model\Collection
     * @throws \think\Exception
     */
    public static function setCouponIds($coupon,$itemIds){
        if($itemIds) {
            $insertTagIds = [];
            $itemIds = is_array($itemIds) ? $itemIds : explode(',', $itemIds);
            self::whereNotIn('target_id', $itemIds)->where('coupon_id', $coupon->id)->delete();
            foreach ($itemIds as &$itemId) {
                if(self::where('coupon_id',$coupon->id)->where('target_id',$itemId)->count()<1)
                    $insertTagIds[] = ['coupon_id'=>$coupon->id, 'target_id'=>$itemId];
            }
            if($insertTagIds) {
                return (new self())->saveAll($insertTagIds);
            }
        }else {
            self::where('coupon_id', $coupon->id)->delete();
        }
    }

}

<?php

namespace app\common\model\xiluxc\brand;

use think\Model;


class ShopVerifier extends Model
{

    

    

    // 表名
    protected $name = 'xiluxc_shop_verifier';
    
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


    public function shop(){
        return $this->belongsTo(Shop::class,'shop_id','id',[],'inner')->setEagerlyType(0);
    }

    /**
     * 我是否是核销员
     * @param $mobile
     * @return bool
     * @throws \think\Exception
     */
    public static function isVerifier($mobile,$shopIds=[]){
        $query = new self;
        if($shopIds){
            $query->whereIn('shop_id',$shopIds);
        }
        return $query->where('mobile',$mobile)->where('status','normal')->find();
    }


}

<?php

namespace app\common\model\xiluxc\order;

use app\common\model\xiluxc\brand\Shop;
use app\common\model\xiluxc\user\User;
use app\common\model\xiluxc\user\UserPackage;
use think\Model;
use traits\model\SoftDelete;

class Aftersale extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'xiluxc_aftersale';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [

    ];
    

    
    public function getAftersaleTypeList()
    {
        return ['service' => __('Aftersale_type service'), 'package' => __('Aftersale_type package')];
    }

    public function getStatusList()
    {
        return ['-1' => __('Status -1'), '0' => __('Status 0'), '1' => __('Status 1')];
    }

    public function ordering(){
        return $this->belongsTo(Order::class,'order_id','id',[],'left')->setEagerlyType(0);
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','id',[],'left')->setEagerlyType(0);
    }

    public function shop(){
        return $this->belongsTo(Shop::class,'shop_id','id',[],'left')->setEagerlyType(0);
    }

    public function userPackage(){
        return $this->belongsTo(UserPackage::class,'user_package_id','id',[],'left')->setEagerlyType(0);
    }

}

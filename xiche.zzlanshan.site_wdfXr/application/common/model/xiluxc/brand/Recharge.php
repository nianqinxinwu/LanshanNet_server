<?php

namespace app\common\model\xiluxc\brand;

use think\Model;


class Recharge extends Model
{

    

    

    // 表名
    protected $name = 'xiluxc_recharge';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            if (!$row['weigh']) {
                $pk = $row->getPk();
                $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
            }
        });
    }

    public function scopeNormal($query){
        return $query->where("status","normal");
    }

    
    public function getStatusList()
    {
        return ['hidden' => __('Status hidden'), 'normal' => __('Status normal')];
    }

    public function brand(){
        return $this->belongsTo(ShopBrand::class,'brand_id','id',[],'left')->setEagerlyType(0);
    }

    public function shop(){
        return $this->belongsTo(Shop::class,'shop_id','id',[],'left')->setEagerlyType(0);
    }

    /**
     * @param $shop
     * @return mixed
     */
    public static function getRecharge($shop){
        $where = Shop::getBrandShopWhere($shop);
        return self::normal()
            ->field("id,money,extra_money")
            ->where($where)
            ->order("weigh",'desc')
            ->select();
    }



}

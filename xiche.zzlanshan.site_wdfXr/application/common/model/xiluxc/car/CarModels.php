<?php

namespace app\common\model\xiluxc\car;

use think\Model;
use traits\model\SoftDelete;

class CarModels extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'xiluxc_car_models';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

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

    
    public function getStatusList()
    {
        return ['hidden' => __('Status hidden'), 'normal' => __('Status normal')];
    }


    public function scopeNormal($query){
        $query->where("status",'normal');
    }




}

<?php

namespace app\common\model\xiluxc\car;

use think\Model;
use traits\model\SoftDelete;

class CarSeries extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'xiluxc_car_series';
    
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



    public function getLevelidList()
    {
        return [
            '1' => __('Levelid 1'), '2' => __('Levelid 2'), '3' => __('Levelid 3'),
            '4' => __('Levelid 4'), '5' => __('Levelid 5'), '6' => __('Levelid 6'),
            '7' => __('Levelid 7'), '8' => __('Levelid 8'), '9' => __('Levelid 9'),
            '10' => __('Levelid 10'), '11' => __('Levelid 11'), '12' => __('Levelid 12'),
            '13' => __('Levelid 13'), '14' => __('Levelid 14'), '15' => __('Levelid 15'),
            '16' => __('Levelid 16'), '17' => __('Levelid 17'), '18' => __('Levelid 18'),
            '19' => __('Levelid 19'), '20' => __('Levelid 20'), '21' => __('Levelid 21'), '22' => __('Levelid 22')
        ];
    }

}

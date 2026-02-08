<?php

namespace app\common\model\xiluxc\activity;

use think\Model;


class Navigation extends Model
{

    

    

    // 表名
    protected $name = 'xiluxc_navigation';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'icon_image_text'
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

    
    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2'), '3' => __('Type 3')];
    }

    public function getJumpTypeList()
    {
        return ['1' => __('Jump_type 1'), '2' => __('Jump_type 2')];
    }

    public function getStatusList()
    {
        return ['hidden' => __('Status hidden'), 'normal' => __('Status normal')];
    }

    public function getIconImageTextAttr($value,$data){
        $value = isset($data['icon_image']) && $data['icon_image'] ? $data['icon_image'] : '';
        return $value?cdnurl($value,true):'';
    }





}

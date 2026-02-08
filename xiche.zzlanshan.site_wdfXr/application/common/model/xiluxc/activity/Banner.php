<?php

namespace app\common\model\xiluxc\activity;

use think\Model;
use traits\model\SoftDelete;

class Banner extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'xiluxc_banner';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'thumb_image_text'
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

    public function getThumbImageTextAttr($value,$data){
        $value = isset($data['thumb_image']) && $data['thumb_image'] ? $data['thumb_image'] : '';
        return $value?cdnurl($value,true):'';
    }


    /**
     * @return \string[][]
     */
    public function banner_group(){
        return [
            'shop' => array (
                'name' => '门店',
                'size' => '700*500',
            ),
            'index2' => array (
                'name' => '首页A2',
                'size' => '350*500',
            ),

        ];

    }



}

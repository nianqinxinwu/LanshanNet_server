<?php

namespace app\common\model\jj;

use think\Model;

/**
 * 商品任务池模型
 */
class Product extends Model
{
    protected $name = 'jj_product';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $append = [
        'status_text',
    ];

    const STATUS_OFF = 0;
    const STATUS_ON = 1;

    const STATUS_MAP = [
        0 => '下架',
        1 => '上架',
    ];

    public function getStatusTextAttr($value, $data)
    {
        $status = isset($data['status']) ? $data['status'] : 0;
        return self::STATUS_MAP[$status] ?? '未知';
    }

    public function getCoverImageAttr($value)
    {
        return $value ? cdnurl($value, true) : '';
    }

    public function factory()
    {
        return $this->belongsTo(Factory::class, 'factory_id', 'id', [], 'left')->setEagerlyType(0);
    }

    public function scopeOn($query)
    {
        return $query->where('status', self::STATUS_ON);
    }
}

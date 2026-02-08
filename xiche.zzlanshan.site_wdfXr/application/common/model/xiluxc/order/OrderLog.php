<?php

namespace app\common\model\xiluxc\order;

use app\common\model\User;
use think\Model;

class OrderLog extends Model
{
    // 表名
    protected $name = 'xiluxc_order_log';

    const TYPE_SERVICE = 1;
    const TYPE_PACKAGE = 2;
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    /**
     * @param int $type
     * @param null $order
     * @param null $aftersale
     * @param null $oper
     * @param string $operate_type
     * @param array $data
     * @return OrderLog
     */
    public static function operate_add($type, $order = null, $aftersale = null, $operate = null, $operate_type = 'user', $data = [])
    {
        $operate_id = empty($operate) ? 0 : (is_array($operate) ? $operate['id'] : $operate->id);
        $images = $data['images'] && is_array($data['images']) ? implode(',', $data['images']) : $data['images'];

        $self = new self();
        $self->order_type = $type;
        $self->order_id = $order['id'] ?? ($aftersale['order_id'] ?? 0);
        $self->aftersale_id = is_null($aftersale) ? 0 : $aftersale['id'];
        $self->operate_type = $operate_type;
        $self->operate_id = $operate_id;
        $self->title = $data['title'] ?? '';
        $self->description = $data['description'] ?? '';
        $self->images = $images;
        $self->save();

        // 售后单变动行为
        return $self;
    }

}

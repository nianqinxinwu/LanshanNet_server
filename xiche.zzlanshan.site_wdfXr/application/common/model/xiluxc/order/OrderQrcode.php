<?php

namespace app\common\model\xiluxc\order;

use think\Model;

class OrderQrcode extends Model
{
    // 表名
    protected $name = 'xiluxc_order_qrcode';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'verifytime_text'
    ];

    public function getVerifytimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['verifytime']) ? $data['verifytime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i", $value) : $value;
    }

    public function ordering(){
        return $this->belongsTo(Order::class,'order_id','id',[],'left')->setEagerlyType(0);
    }

}

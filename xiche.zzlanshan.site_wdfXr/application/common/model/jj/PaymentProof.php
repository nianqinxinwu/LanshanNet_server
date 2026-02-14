<?php

namespace app\common\model\jj;

use think\Model;

/**
 * 买家付款证明模型
 */
class PaymentProof extends Model
{
    protected $name = 'jj_payment_proof';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $append = [
        'status_text',
    ];

    const STATUS_PENDING  = 0;  // 待审核
    const STATUS_APPROVED = 1;  // 已通过
    const STATUS_REJECTED = 2;  // 已驳回

    const STATUS_MAP = [
        0 => '待审核',
        1 => '已通过',
        2 => '已驳回',
    ];

    public function getStatusTextAttr($value, $data)
    {
        $status = isset($data['status']) ? $data['status'] : 0;
        return self::STATUS_MAP[$status] ?? '未知';
    }

    /**
     * file_urls 存储为 JSON 数组，读取时解码并转为完整 CDN URL
     */
    public function getFileUrlsAttr($value)
    {
        $arr = is_array($value) ? $value : (json_decode($value ?: '[]', true) ?: []);
        return array_map(function ($v) {
            return $v ? cdnurl($v, true) : '';
        }, $arr);
    }

    /**
     * file_urls 写入时自动 JSON 编码
     */
    public function setFileUrlsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id', [], 'left')->setEagerlyType(0);
    }
}

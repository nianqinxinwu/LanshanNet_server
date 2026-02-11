<?php

namespace app\common\model\jj;

use think\Model;

/**
 * 居间人扩展表模型
 */
class AgentProfile extends Model
{
    protected $name = 'jj_agent_profile';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $append = [
        'status_text',
    ];

    const STATUS_PENDING = 0;
    const STATUS_NORMAL = 1;
    const STATUS_FROZEN = 2;

    const STATUS_MAP = [
        0 => '待审核',
        1 => '正常',
        2 => '冻结',
    ];

    public function getStatusTextAttr($value, $data)
    {
        $status = isset($data['status']) ? $data['status'] : 0;
        return self::STATUS_MAP[$status] ?? '未知';
    }

    public function getHexagonDataAttr($value)
    {
        return $value ? json_decode($value, true) : null;
    }

    public function setHexagonDataAttr($value)
    {
        return is_array($value) ? json_encode($value) : $value;
    }

    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id', 'id', [], 'left')->setEagerlyType(0);
    }

    public function scopeNormal($query)
    {
        return $query->where('status', self::STATUS_NORMAL);
    }
}

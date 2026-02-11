<?php

namespace app\common\model\jj;

use think\Model;

/**
 * PK奖池模型
 */
class PkPool extends Model
{
    protected $name = 'jj_pk_pool';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $append = [
        'status_text',
    ];

    const STATUS_ACTIVE  = 0;
    const STATUS_SETTLED = 1;

    const STATUS_MAP = [
        0 => '进行中',
        1 => '已结算',
    ];

    public function getStatusTextAttr($value, $data)
    {
        $status = isset($data['status']) ? $data['status'] : 0;
        return self::STATUS_MAP[$status] ?? '未知';
    }

    public function ranks()
    {
        return $this->hasMany(PkRank::class, 'pool_id', 'id');
    }
}

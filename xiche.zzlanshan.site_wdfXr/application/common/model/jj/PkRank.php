<?php

namespace app\common\model\jj;

use think\Model;

/**
 * PK排名记录模型
 */
class PkRank extends Model
{
    protected $name = 'jj_pk_rank';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = false;

    public function pool()
    {
        return $this->belongsTo(PkPool::class, 'pool_id', 'id', [], 'left')->setEagerlyType(0);
    }

    public function agent()
    {
        return $this->belongsTo('app\common\model\User', 'agent_id', 'id', [], 'left')->setEagerlyType(0);
    }
}

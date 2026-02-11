<?php

namespace app\common\model\jj;

use think\Model;

/**
 * 分销推广关系模型
 */
class Invite extends Model
{
    protected $name = 'jj_invite';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public function agent()
    {
        return $this->belongsTo('app\common\model\User', 'agent_id', 'id', [], 'left')->setEagerlyType(0);
    }

    public function inviteUser()
    {
        return $this->belongsTo('app\common\model\User', 'invite_user_id', 'id', [], 'left')->setEagerlyType(0);
    }
}

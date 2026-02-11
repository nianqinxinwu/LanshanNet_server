<?php

namespace app\common\model\jj;

use think\Model;

/**
 * 居间人-买家信息模型
 */
class BuyerInfo extends Model
{
    protected $name = 'jj_buyer_info';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
}

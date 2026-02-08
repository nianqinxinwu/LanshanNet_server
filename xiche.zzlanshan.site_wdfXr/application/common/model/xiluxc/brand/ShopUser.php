<?php

namespace app\common\model\xiluxc\brand;

use think\Model;

class ShopUser extends Model{

    protected $name = 'xiluxc_shop_user';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;


}
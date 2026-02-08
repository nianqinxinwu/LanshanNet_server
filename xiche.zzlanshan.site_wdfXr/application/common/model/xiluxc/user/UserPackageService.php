<?php

namespace app\common\model\xiluxc\user;

use think\Model;

class UserPackageService extends Model{

    protected $name = 'xiluxc_user_package_service';

    protected $append = [

    ];

    protected $autoWriteTimestamp = "int";

    protected $createTime = "createtime";
    protected $updateTime = "updatetime";
    protected $deleteTime = false;

}
<?php

namespace app\common\validate\xiluxc\brand;

use think\Validate;

class UserBrand extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'brand_name|品牌名称'   => ['require'],
        'logo|品牌LOGO'        => ['require'],
        'concat_name|联系人'    => ['require'],
        'mobile|手机号'         => ['require'],
        'account_mobile|品牌账号'=> ['require'],
    ];
    /**
     * 提示消息
     */
    protected $message = [
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add' => [],

        'edit' => [],
    ];
    
}

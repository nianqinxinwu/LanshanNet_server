<?php

namespace app\common\validate\xiluxc;

use think\Validate;

class UserCar extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'car_no|车牌号'         => ['require'],
        'brand_id|品牌车系'     => ['require', 'gt:0'],
        'series_id|品牌车系'    => ['require', 'gt:0']
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
        'add' => ['car_no', 'brand_id', 'series_id'],

        'edit' => ['car_no', 'brand_id', 'series_id'],
    ];

}
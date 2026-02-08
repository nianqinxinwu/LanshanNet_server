<?php

namespace app\common\model\xiluxc;

use think\Model;

/**
 * 会员余额日志模型
 */
class ScoreLog extends Model
{

    // 表名
    protected $name = 'xiluxc_score_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = '';
    // 追加属性
    protected $append = [
    ];


    /**
     * 积分说明
     * @return string[]
     */
    public function getTypeMemoList(){
        return [
            'vip_order'     =>  '门店会员下单',
            'package_order' =>  '门店套餐下单',
            'service_order' =>  '门店服务下单',
            'service_deduction' =>  '门店服务抵扣',
            'package_deduction' =>  '门店套餐抵扣',
        ];
    }

}

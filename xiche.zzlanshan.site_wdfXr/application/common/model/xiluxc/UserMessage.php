<?php

namespace app\common\model\xiluxc;

use think\Model;
use function fast\array_get;

class UserMessage extends Model
{

    //消息类型
    const TYPE_SERVICE_ORDER = 1; //服务支付成功
    const TYPE_PACKAGE_ORDER = 2; //套餐支付成功
    const TYPE_VIP_ORDER = 3; //会员支付成功
    const TYPE_RECHARGE = 4; //门店充值支付成功
    const TYPE_SERVICE_APPOINTMENT_SUCCESS = 5; //单个服务预约成功
    const TYPE_SERVICE_VERIFIER_SUCCESS = 6; //单个服务核销成功
    const TYPE_PACKAGE_VERIFIER_SUCCESS = 7; //套餐核销成功


    protected $name = 'xiluxc_user_message';

    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'createtime_text'
    ];

    public function getCreatetimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['createtime']) ? $data['createtime'] : '');
        return is_numeric($value) ? date("Y.m.d H:i", $value) : $value;
    }

    public function setExtraAttr($value, $data) {
        return is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function getExtraAttr($value, $data) {
        $value = $value ? : array_get($data, 'extra');
        return is_string($value) ? json_decode($value,true) : $value;
    }


}

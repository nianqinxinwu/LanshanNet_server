<?php

namespace app\common\model\xiluxc;

use app\common\model\User;
use app\common\model\xiluxc\brand\Shop;
use think\Model;

/**
 * 会员余额日志模型
 */
class MoneyLog extends Model
{

    // 表名
    protected $name = 'xiluxc_money_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = '';
    // 追加属性
    protected $append = [
    ];

    const TYPE_USER_BALANCE = 1; //用户余额
    const TYPE_COMMISSION = 2; // 用户佣金
    const TYPE_SHOP = 3; //门店金额

    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2')];
    }

    public function getStatusList()
    {
        return ['-1' => __('Status -1'), '0' => __('Status 0'), '1' => __('Status 1')];
    }

    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','id',[],'left')->setEagerlyType(0);
    }

    public function shop(){
        return $this->belongsTo(Shop::class,'shop_id','id',[],'left')->setEagerlyType(0);
    }

}

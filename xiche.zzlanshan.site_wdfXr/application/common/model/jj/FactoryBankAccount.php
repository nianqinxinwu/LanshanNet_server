<?php

namespace app\common\model\jj;

use think\Model;
use traits\model\SoftDelete;

/**
 * 工厂对公银行账户模型
 */
class FactoryBankAccount extends Model
{
    use SoftDelete;

    protected $name = 'jj_factory_bank_account';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    /**
     * 设置默认账户（同工厂下其他账户取消默认）
     * @param int $factoryId
     * @param int $accountId
     * @return bool
     */
    public static function setDefault($factoryId, $accountId)
    {
        // 取消当前默认
        self::where('factory_id', $factoryId)
            ->where('is_default', 1)
            ->update(['is_default' => 0]);

        // 设置新默认
        return self::where('id', $accountId)
            ->where('factory_id', $factoryId)
            ->update(['is_default' => 1]) !== false;
    }

    /**
     * 获取默认账户
     * @param int $factoryId
     * @return FactoryBankAccount|null
     */
    public static function getDefault($factoryId)
    {
        return self::where('factory_id', $factoryId)
            ->where('is_default', 1)
            ->find();
    }
}

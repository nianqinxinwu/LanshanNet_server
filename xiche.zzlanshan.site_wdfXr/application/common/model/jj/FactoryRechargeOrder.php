<?php

namespace app\common\model\jj;

use think\Db;
use think\Model;

/**
 * 工厂充值订单模型
 */
class FactoryRechargeOrder extends Model
{
    protected $name = 'jj_factory_recharge_order';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    const PAY_STATUS_PENDING = 0;
    const PAY_STATUS_PAID    = 1;

    /**
     * 生成充值订单号
     * @return string
     */
    public static function generateOrderNo()
    {
        return 'FR' . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * 支付回调处理
     * @param string $orderNo       订单号
     * @param string $transactionId 第三方交易号
     * @return bool
     */
    public static function payNotify($orderNo, $transactionId)
    {
        Db::startTrans();
        try {
            $order = self::where('order_no', $orderNo)->lock(true)->find();
            if (!$order) {
                Db::rollback();
                return false;
            }
            if ($order->pay_status == self::PAY_STATUS_PAID) {
                Db::commit();
                return true; // 已处理过，幂等返回
            }

            $order->save([
                'pay_status'     => self::PAY_STATUS_PAID,
                'pay_time'       => time(),
                'transaction_id' => $transactionId,
            ]);

            // 充值到工厂账户（确保账户存在）
            $account = FactoryAccount::getOrCreate($order->factory_id, $order->user_id);
            $rechargeResult = $account->recharge($order->amount, $order->id, '微信充值到账');
            if (!$rechargeResult) {
                throw new \Exception('充值入账失败');
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }
}

<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\Factory;
use app\common\model\jj\Order;
use app\common\model\jj\Product;
use app\common\model\jj\BidQuote;
use app\common\model\jj\Commission;
use app\common\model\jj\Deposit;
use app\common\model\jj\FactoryAccount;
use think\Db;

/**
 * 工厂端 - 数据看板
 */
class FcDashboard extends XiluxcApi
{
    protected $noNeedLogin = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取当前用户关联的工厂
     */
    protected function getFactory()
    {
        $factory = Factory::where('user_id', $this->auth->id)->find();
        if (!$factory) {
            $this->error('请先完成企业认证');
        }
        return $factory;
    }

    /**
     * 格式化金额：千分位 + 2位小数，空值返回 "0.00"
     */
    protected function formatMoney($val)
    {
        return number_format(floatval($val ?: 0), 2, '.', ',');
    }

    /**
     * 总览数据
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function overview()
    {
        $factory = $this->getFactory();
        $fid = $factory['id'];

        // 缓存命中检查（60秒TTL）
        $cacheKey = 'fc_overview_' . $fid;
        $cached = cache($cacheKey);
        if ($cached) {
            $this->success('查询成功', $cached);
        }

        // 本月起止时间
        $monthStart = strtotime(date('Y-m-01'));
        $monthEnd = strtotime(date('Y-m-01', strtotime('+1 month')));

        // 聚合查询：月交易量 + 月交易额 + 总订单数（3条SQL合并为1条）
        $agg = Db::name('jj_order')
            ->where('factory_id', $fid)
            ->where('status', '>=', Order::STATUS_DEPOSIT)
            ->field([
                'COUNT(*) as total_orders',
                "SUM(IF(createtime >= {$monthStart} AND createtime < {$monthEnd}, 1, 0)) as month_orders",
                "SUM(IF(createtime >= {$monthStart} AND createtime < {$monthEnd}, total_amount, 0)) as month_amount",
            ])->find();

        $totalOrders = intval($agg['total_orders']);
        $monthOrders = intval($agg['month_orders']);
        $monthAmount = floatval($agg['month_amount']);

        // 产品浏览 → 接单转化率（简化：总订单数/产品数）
        $productCount = $factory['product_count'] ?: 1;
        $conversionRate = $productCount > 0 ? round($totalOrders / $productCount * 100, 1) : 0;

        // 平均响应时间（从接单到锁定佣金，取最近30条）
        $avgResult = Db::name('jj_order')
            ->where('factory_id', $fid)
            ->where('commission_locked', 1)
            ->where('commission_lock_time', '>', 0)
            ->order('id', 'desc')
            ->limit(30)
            ->field('AVG(commission_lock_time - createtime) as avg_time')
            ->find();
        $avgResponseTime = $avgResult ? floatval($avgResult['avg_time']) : 0;
        $avgResponseHours = $avgResponseTime ? round($avgResponseTime / 3600, 1) : 0;

        // 待办统计（3条SQL合并为1条GROUP BY）
        $todoAgg = Db::name('jj_order')
            ->where('factory_id', $fid)
            ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_DEPOSITED, Order::STATUS_EXECUTING])
            ->field('status, COUNT(*) as cnt')
            ->group('status')
            ->select();
        $todoMap = [];
        foreach ($todoAgg as $row) {
            $todoMap[$row['status']] = intval($row['cnt']);
        }

        $data = [
            'month_orders'       => $monthOrders,
            'month_amount'       => $this->formatMoney($monthAmount),
            'total_orders'       => $totalOrders,
            'conversion_rate'    => $conversionRate,
            'avg_response_hours' => $avgResponseHours,
            'product_count'      => intval($factory['product_count']),
            'fulfill_rate'       => floatval($factory['fulfill_rate']),
            'pending_confirm'    => $todoMap[Order::STATUS_PENDING] ?? 0,
            'pending_lock'       => $todoMap[Order::STATUS_DEPOSITED] ?? 0,
            'pending_ship'       => $todoMap[Order::STATUS_EXECUTING] ?? 0,
        ];
        cache($cacheKey, $data, 60);
        $this->success('查询成功', $data);
    }

    /**
     * 产品统计
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function product_stats()
    {
        $factory = $this->getFactory();
        $fid = $factory['id'];

        // 缓存命中检查（120秒TTL）
        $cacheKey = 'fc_prodstats_' . $fid;
        $cached = cache($cacheKey);
        if ($cached) {
            $this->success('查询成功', $cached);
        }

        // TOP10 畅销产品
        $top10 = Order::where('factory_id', $fid)
            ->where('status', '>=', Order::STATUS_DEPOSIT)
            ->field('product_name, product_id, count(*) as order_count, sum(total_amount) as total_amount')
            ->group('product_id')
            ->order('order_count', 'desc')
            ->limit(10)
            ->select();

        // 格式化金额
        $top10Data = [];
        foreach ($top10 as $item) {
            $row = is_array($item) ? $item : $item->toArray();
            $row['total_amount'] = $this->formatMoney($row['total_amount'] ?? 0);
            $top10Data[] = $row;
        }

        // 产品上下架统计（2条SQL合并为1条GROUP BY）
        $shelfCounts = Db::name('jj_product')
            ->where('factory_id', $fid)
            ->whereIn('status', [Product::STATUS_ON, Product::STATUS_OFF])
            ->field('status, COUNT(*) as cnt')
            ->group('status')
            ->select();
        $shelfMap = [];
        foreach ($shelfCounts as $row) {
            $shelfMap[$row['status']] = intval($row['cnt']);
        }
        $onCount = $shelfMap[Product::STATUS_ON] ?? 0;
        $offCount = $shelfMap[Product::STATUS_OFF] ?? 0;

        // 竞标响应率（2条SQL合并为1条条件聚合）
        $bidAgg = Db::name('jj_bid_quote')
            ->where('factory_id', $fid)
            ->field([
                'COUNT(*) as total_invites',
                "SUM(IF(status = " . BidQuote::STATUS_QUOTED . ", 1, 0)) as quoted_count",
            ])->find();
        $totalBidInvites = intval($bidAgg['total_invites']);
        $quotedCount = intval($bidAgg['quoted_count']);
        $bidResponseRate = $totalBidInvites > 0 ? round($quotedCount / $totalBidInvites * 100, 1) : 0;

        $data = [
            'top10_products'   => $top10Data,
            'on_shelf_count'   => $onCount,
            'off_shelf_count'  => $offCount,
            'bid_response_rate' => $bidResponseRate,
            'total_bid_invites' => $totalBidInvites,
            'quoted_count'      => $quotedCount,
        ];
        cache($cacheKey, $data, 120);
        $this->success('查询成功', $data);
    }

    /**
     * 财务统计
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function finance_stats()
    {
        $factory = $this->getFactory();
        $fid = $factory['id'];

        // 缓存命中检查（60秒TTL）
        $cacheKey = 'fc_finance_' . $fid;
        $cached = cache($cacheKey);
        if ($cached) {
            $this->success('查询成功', $cached);
        }

        // 已锁定佣金总额（commission_locked=1 的订单）
        $lockedCommission = Order::where('factory_id', $fid)
            ->where('commission_locked', 1)
            ->sum('commission_amount');

        // 已结算佣金总额
        $settledCommission = Order::where('factory_id', $fid)
            ->where('status', Order::STATUS_SETTLED)
            ->sum('commission_amount');

        // 违约保证金收入（逾期订单的保证金）
        $defaultDeposit = Db::name('jj_deposit')
            ->alias('d')
            ->join('fa_jj_order o', 'd.order_id = o.id')
            ->where('o.factory_id', $fid)
            ->where('o.status', Order::STATUS_OVERDUE)
            ->where('d.pay_status', Deposit::PAY_STATUS_PAID)
            ->sum('d.amount');

        // 本月已结算
        $monthStart = strtotime(date('Y-m-01'));
        $monthSettled = Order::where('factory_id', $fid)
            ->where('status', Order::STATUS_SETTLED)
            ->where('updatetime', '>=', $monthStart)
            ->sum('commission_amount');

        // 工厂钱包余额
        $account = FactoryAccount::getOrCreate($fid, $this->auth->id);

        $data = [
            'locked_commission'  => $this->formatMoney($lockedCommission),
            'settled_commission' => $this->formatMoney($settledCommission),
            'default_deposit'    => $this->formatMoney($defaultDeposit),
            'month_settled'      => $this->formatMoney($monthSettled),
            'wallet_money'       => $this->formatMoney($account['money']),
            'wallet_frozen'      => $this->formatMoney($account['frozen_money']),
        ];
        cache($cacheKey, $data, 60);
        $this->success('查询成功', $data);
    }
}

<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\xiluxc\brand\Recharge AS RechargeModel;
use app\common\model\xiluxc\order\RechargeOrder;
use app\common\model\xiluxc\user\UserShopAccount;
use function fast\array_get;

class Recharge extends XiluxcApi
{
    protected $noNeedLogin = [];
    /**
     * @ApiTitile (充值金额列表)
     * @ApiSummary(充值金额列表)
     * @ApiMethod (GET)
     * @ApiRoute (/api/xiluxc.recharge/index)
     * @ApiHeaders (name=token, type=string, require=true, description="Token")
     * @ApiParams (name=_id, type=int, require=true, description="id")
     */
    public function index() {
        $shopId = $this->request->param('shop_id/d');
        $shop = $shopId ? \app\common\model\xiluxc\brand\Shop::get($shopId) : null;
        if(!$shop){
            $this->error("门店不存在");
        }
        $where = \app\common\model\xiluxc\brand\Shop::getBrandShopWhere($shop);
        $lists = RechargeModel::normal()->field("id,money,extra_money")->where($where)->order("weigh",'desc')->select();
        $this->success('查询成功', $lists);
    }


    /**
     * @ApiTitile (下单)
     * @ApiSummary(充值下单)
     * @ApiMethod (GET)
     * @ApiRoute (/api/recharge/lists)
     * @ApiHeaders (name=token, type=string, require=true, description="Token")
     * @ApiParams (name=_id, type=int, require=true, description="id")
     */
    public function create_order() {
        $params = $this->request->post('');
        $rechargeId = array_get($params,'recharge_id');
        $shopId = array_get($params,'shop_id');
        $recharge = $rechargeId ? RechargeModel::get($rechargeId) : null;
        if(!$recharge || $recharge->money<=0) {
            $this->error('充值卡不存在');
        }
        $user_id = $this->auth->id;
        $userShopAccount = UserShopAccount::addAccount($user_id,$shopId);
        $params = array_merge($params, [
            'order_no'          =>  'RE'.date("YmdHis").mt_rand(100,9999),
            'user_id'           =>  $user_id,
            'brand_id'          =>  $userShopAccount->brand_id,
            'pay_fee'           =>  $recharge->money,
            'recharge_id'       =>  $recharge->id,
            'recharge_money'    =>  $recharge->money,
            'recharge_extra_money'=>$recharge->extra_money,
            'recharge_total_money'=>bcadd($recharge->money,$recharge->extra_money),
            'ip'                => request()->ip(),
            'admin_id'          => '1234'
        ]);
        $order = new RechargeOrder;
        $order->allowField(true)->save($params);
        // $order->save($params);
        $this->success('下单成功', $order);
    }

}
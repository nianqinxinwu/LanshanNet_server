<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\xiluxc\brand\ShopBrand;
use app\common\model\xiluxc\brand\Shopvip;
use app\common\model\xiluxc\brand\Shopvip AS VipModel;
use app\common\model\xiluxc\current\Config;
use app\common\model\xiluxc\order\VipOrder;
use app\common\model\xiluxc\user\UserShopVip;
use app\common\model\xiluxc\brand\Shop;
use function fast\array_get;

class Vip extends XiluxcApi
{
    protected $noNeedLogin = ["detail"];
    /**
     * @ApiTitile (会员卡详情)
     * @ApiSummary(会员卡详情)
     * @ApiMethod (GET)
     * @ApiRoute (/api/xiluxc.vip/index)
     * @ApiHeaders (name=token, type=string, require=true, description="Token")
     * @ApiParams (name=_id, type=int, require=true, description="id")
     */
    public function detail() {
        $vipId = $this->request->param('vip_id/d');
        $vip = $vipId ? VipModel::normal()->field("*")->where("id",$vipId)->find() : null;
        if(!$vip){
            $this->error("会员卡不存在");
        }
        $userShopVip = UserShopVip::where("shop_id",$vip->shop_id)->where('user_id',$this->auth->id)->find();
        if($userShopVip && $userShopVip["expire_in"] >= Config::getExpiretime()){
            $vip['my_state'] = 1;//正常
        }else if($userShopVip){
            $vip['my_state'] = 2;//已过期
        }else{
            $vip['my_state'] = 0;//未购买
        }
        $vip['user_shop_vip'] = $userShopVip;
        $this->success('查询成功', $vip);
    }


    /**
     * @ApiTitile (下单)
     * @ApiSummary(会员卡下单)
     * @ApiMethod (GET)
     * @ApiRoute (/api/recharge/lists)
     * @ApiHeaders (name=token, type=string, require=true, description="Token")
     * @ApiParams (name=_id, type=int, require=true, description="id")
     */
    public function create_order() {
        $params = $this->request->post('');
        $vipId = array_get($params,'vip_id');
        $shopId = array_get($params,'shop_id');
        $platform = array_get($params,'platform','wxmini');
        $shop = $shopId ? Shop::get(['id'=>$shopId,'status'=>'normal','audit_status'=>Shop::AUDIT_STATUS_PASSED]) : null;
        if(!$shop){
            $this->error("门店不存在或已下架");
        }
        $vip = $vipId ? VipModel::normal()->where('id',$vipId)->find() : null;
        if(!$vip) {
            $this->error('会员卡不存在');
        }
        $user_id = $this->auth->id;
        $params = [
            'user_id'           =>  $user_id,
            'platform'          =>  $platform,
            'order_no'          =>  'V'.date("YmdHis").mt_rand(100,9999),
            'pay_fee'           =>  $vip->salesprice,
            'pay_status'        =>  'unpaid',
            'vip_id'            =>  $vip->id,
            'vip_salesprice'    =>  $vip->salesprice,
            'vip_name'          =>  $vip->name,
            'vip_days'          =>  $vip->days,
            'vip_privilege'     =>  $vip->privilege,
            'ip'                =>  request()->ip(),
            'shop_id'           =>  $shopId,
            'brand_id'          =>  $vip->brand_id,
        ];
        $order = new VipOrder();
        $order->allowField(true)->save($params);
        $this->success('下单成功', $order);
    }

    /**
     * 我的会员卡
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function myvip(){
        $userShopVip = new UserShopVip();
        $shopVip = $userShopVip->with(['shop'=>function($q){
            $q->withField(["id","name",'image']);
        },'brand'=>function($q){
            $q->withField(["id","brand_name",'logo']);
        }])
            ->where($userShopVip->getTable().".user_id",$this->auth->id)
            ->where("expire_in",'>=',strtotime(date("Y-m-d")))
            ->order($userShopVip->getTable().".id",'desc')
            ->select();
        foreach ($shopVip as $item) {
            $item->append(['expire_in_text']);
            $item->shop->append(['image_text']);
        }
        $this->success('',$shopVip);
    }


    /**
     * 可使用门店
     */
    public function vip_shops(){
        $params = $this->request->param('');
        $shopVipId = array_get($params,'shop_vip_id');
        $shopVip = $shopVipId?Shopvip::get($shopVipId) : null;
        if(!$shopVip){
            $this->error("会员卡错误");
        }
        $query = new Shop();
        $lat = array_get($params,'lat');
        $lng = array_get($params,'lng');
        if($lng && $lat){
            $query->field("id,name,image,point,lat,lng,address,sales,(ST_DISTANCE(POINT(".$lng.", ".$lat."), POINT(lng, lat)) * 111195) AS distance")->order("distance",'asc');
        }else{
            $query->field("id,name,image,point,lat,lng,address,sales")->order("id",'desc');
        }
        if($shopVip->brand_id){
            $shopBrand = ShopBrand::get($shopVip->brand_id);
            if(!$shopBrand){
                $this->error("品牌未找到");
            }
            $query->where("brand_id",$shopBrand->user_id);
        }else{
            $query->where("id",$shopVip->shop_id);
        }

        $lists = $query->paginate(request()->param('pagesize',10))
        ->each(function ($row){
            $row->append(['image_text']);
            $row->distance = isset($row->distance) ? ($row->distance>=1000 ? bcdiv($row->distance,1000,1).'km' : bcadd($row->distance,0,1).'m') : '';
        });
        $this->success('',$lists);
    }


}
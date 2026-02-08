<?php


namespace app\api\controller\xiluxc;


use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\xiluxc\current\Config;
use app\common\model\xiluxc\activity\Coupon AS CouponModel;
use app\common\model\xiluxc\activity\UserCoupon;
use app\common\model\xiluxc\brand\Shop;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use function fast\array_get;

class Coupon extends XiluxcApi
{
    protected $noNeedLogin = [];

    /**
     * 我的优惠券
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function mycoupons(){
        $params = $this->request->param('');
        $tab = array_get($params,'tab');
        $pagesize = array_get($params,'pagesize',10);
        $where = UserCoupon::buildWhere($tab);
        $shops = Shop::alias("shop")
            ->join("XiluxcUserCoupon user_coupon","shop.id=user_coupon.shop_id")
            ->join("XiluxcCoupon coupon","coupon.id=user_coupon.coupon_id")
            ->field(['shop.id', 'shop.name', 'shop.image','shop.lat', 'shop.lng'])
            ->where('user_coupon.user_id',$this->auth->id)
            ->where($where)
            ->order('user_coupon.id','desc')
            ->group('user_coupon.shop_id')
            ->paginate($pagesize);
        foreach ($shops as $shop){
            $shop->append(['image_text']);
            $lists = UserCoupon::field("id,coupon_id,use_status")
                ->with(['coupon'=>function($q){
                    $q->withField(['id','name','at_least','money','use_end_time']);
                }])
                ->where('user_coupon.user_id',$this->auth->id)
                ->where($where)
                ->where("user_coupon.shop_id",$shop->id)
                ->order('user_coupon.id','desc')
                ->select();
            foreach ($lists as $list){
                $list->coupon->append(['use_end_time_text']);
                $list->append(['state']);
            }
            unset($list);
            $shop['coupon'] = $lists;
        }

        $this->success('',$shops);
    }


    /**
     * 优惠券领取
     * @throws \think\exception\DbException
     */
    public function receive(){
        $couponId = $this->request->post('coupon_id');
        $time = Config::getTodayTime();
        $couponModel = new CouponModel;
        Db::startTrans();
        try {
            $row = $couponId ? $couponModel->where('id',$couponId)
                ->normal()
                ->where('use_start_time','elt',$time)
                ->where('use_end_time','egt',$time)
                ->lock(true)
                ->find() : null;
            if(!$row){
                throw new Exception("优惠券不存在或已下架");
            }
            if($row->max_count<=$row->receive_count){
                throw new Exception("优惠券已领完");
            }
            $userId = $this->auth->id;
            if(UserCoupon::isReceive($userId,$row->id)){
                throw new Exception("不要重复领取");
            }
            $ret = UserCoupon::create([
                'user_id'   =>  $userId,
                'coupon_id' =>  $row->id,
                'shop_id'   =>  $row->shop_id,
            ]);
            $ret2 = $row->save(['receive_count'=>Db::raw("receive_count+1")]);
            if($ret2 === false){
                throw new Exception("领取失败");
            }
        }catch (Exception|PDOException|Exception $e){
            Db::rollback();
            $this->error($e->getMessage());
        }
        Db::commit();
        $this->success('领取成功',$row);
    }

}
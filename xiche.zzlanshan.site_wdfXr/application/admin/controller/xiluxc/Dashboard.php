<?php
namespace app\admin\controller\xiluxc;


use app\common\controller\Backend;
use app\common\model\xiluxc\brand\Shop;
use app\common\model\xiluxc\order\Order;
use app\common\model\xiluxc\order\VipOrder;
use app\common\model\xiluxc\user\User;
use app\common\model\xiluxc\user\UserShopVip;
use app\common\model\xiluxc\order\RechargeOrder;
use fast\Date;
use think\Db;

class Dashboard extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
        //今日数据----新增会员数、新增服务订单，新增订单总金额数、新增充值订单，新增套餐订单
        //财务数据：总收入、已提现、可提现、审核中、冻结中、已退款
//        $basicWhere = [];
//        if($this->brand){
//            $basicWhere['brand_id'] = $this->brand->id;
//        }else{
//            $basicWhere['shop_id'] = $this->shop->id;
//        }
        [$shopCount,$registerCount,$userCount,$orderServiceCount,$orderSum,$rechargeCount,$orderPackageCount] = $this->statics('d');

        //本周
        [$shopCount2,$registerCount2,$userCount2,$orderServiceCount2,$orderSum2,$rechargeCount2,$orderPackageCount2] = $this->statics('w');

        //本月
        [$shopCount3,$registerCount3,$userCount3,$orderServiceCount3,$orderSum3,$rechargeCount3,$orderPackageCount3] = $this->statics('m');

//        $column = [];
//        $starttime = Date::unixtime('day', -6);
//        $endtime = Date::unixtime('day', 0, 'end');
//        $joinlist = Db("user")->where('jointime', 'between time', [$starttime, $endtime])
//            ->field('jointime, status, COUNT(*) AS nums, DATE_FORMAT(FROM_UNIXTIME(jointime), "%Y-%m-%d") AS join_date')
//            ->group('join_date')
//            ->select();
//        for ($time = $starttime; $time <= $endtime;) {
//            $column[] = date("Y-m-d", $time);
//            $time += 86400;
//        }
//        $userlist = array_fill_keys($column, 0);
//        foreach ($joinlist as $k => $v) {
//            $userlist[$v['join_date']] = $v['nums'];
//        }

        $dbTableList = Db::query("SHOW TABLE STATUS");

        $this->view->assign([
            //今日
            'shop_count'                    =>  $shopCount,
            'user_today_count'              =>  $userCount,
            'order_service_count'           =>  $orderServiceCount,
            'order_sum'                     =>  $orderSum,
            'recharge_count'                =>  $rechargeCount,
            'order_package_count'           =>  $orderPackageCount,
            //本周
            'shop_count2'                    =>  $shopCount2,
            'user_today_count2'              =>  $userCount2,
            'order_service_count2'           =>  $orderServiceCount2,
            'order_sum2'                     =>  $orderSum2,
            'recharge_count2'                =>  $rechargeCount2,
            'order_package_count2'           =>  $orderPackageCount2,
            //本月
            'shop_count3'                    =>  $shopCount3,
            'user_today_count3'              =>  $userCount3,
            'order_service_count3'           =>  $orderServiceCount3,
            'order_sum3'                     =>  $orderSum3,
            'recharge_count3'                =>  $rechargeCount3,
            'order_package_count3'           =>  $orderPackageCount3,

//            'todayusersignup'   => User::whereTime('jointime', 'today')->count(),
//            'todayuserlogin'    => User::whereTime('logintime', 'today')->count(),
//            'sevendau'          => User::whereTime('jointime|logintime|prevtime', '-7 days')->count(),
//            'thirtydau'         => User::whereTime('jointime|logintime|prevtime', '-30 days')->count(),
//            'threednu'          => User::whereTime('jointime', '-3 days')->count(),
//            'sevendnu'          => User::whereTime('jointime', '-7 days')->count(),
            'dbtablenums'       => count($dbTableList),
            'dbsize'            => array_sum(array_map(function ($item) {
                return $item['Data_length'] + $item['Index_length'];
            }, $dbTableList)),
        ]);

        //$this->assignconfig('column', array_keys($userlist));
        //$this->assignconfig('userdata', array_values($userlist));

        return $this->view->fetch();
    }

    protected function statics($type='d'){
        //今日数据----新增门店数量,新增会员数、新增服务订单，新增订单总金额数、新增充值订单，新增套餐订单
        //财务数据：总收入、已提现、可提现、审核中、冻结中、已退款
        $basicWhere = [];
        $shopCount = Shop::whereTime("createtime",$type)->count();
        $registerCount = User::whereTime("createtime",$type)->count();
        $userCount = UserShopVip::where($basicWhere)->whereTime("createtime",$type)->count();
        $orderServiceCount = Order::where($basicWhere)->where('status','paid')->where('type','service')->whereTime('createtime',$type)->count();
        //订单总金额
        $orderSum1 = Order::where($basicWhere)->where('status','paid')->whereIn('pay_type',[1,2])->whereTime("createtime",$type)->sum('pay_fee'); //服务与套餐
        $orderSum2 = VipOrder::where($basicWhere)->where('pay_status','paid')->whereTime("createtime",$type)->sum('pay_fee');
        $orderSum = bcadd($orderSum1,$orderSum2,2);

        $rechargeCount = RechargeOrder::where($basicWhere)->where('pay_status','paid')->whereTime("createtime",$type)->sum('pay_fee');
        $orderPackageCount = Order::where($basicWhere)->where('status','paid')->where('type','package')->whereTime('createtime',$type)->count();

        return [$shopCount,$registerCount,$userCount,$orderServiceCount,$orderSum,$rechargeCount,$orderPackageCount];
    }

}
<?php
namespace app\api\controller\xiluxc;
use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\xiluxc\activity\Coupon;
use app\common\model\xiluxc\brand\BranchPackage;
use app\common\model\xiluxc\brand\Package;
use app\common\model\xiluxc\brand\Recharge;
use app\common\model\xiluxc\brand\Shop AS ShopModel;
use app\common\model\xiluxc\brand\ShopBranchService;
use app\common\model\xiluxc\brand\ShopDevice;
use app\common\model\xiluxc\brand\ShopService;
use app\common\model\xiluxc\brand\Shopvip;
use app\common\model\xiluxc\user\UserShopVip;

use app\common\model\xiluxc\user\UserShopAccount;
use app\common\model\xiluxc\activity\UserCoupon;
// use app\common\model\xiluxc\CameraLog;
use app\common\model\xiluxc\WashLog;
use app\common\model\xiluxc\brand\ShopDeviceBox;


use function fast\array_get;

/**
 * Class 门店
 * @package app\api\controller
 */
class Shop extends XiluxcApi
{
    protected $noNeedLogin = ['*'];

    private static $indexInstance = null;
    
    private function getIndexController()
    {
        if (self::$indexInstance === null) {
            // 创建并缓存实例
            self::$indexInstance = new \app\api\controller\Index();
        }
        return self::$indexInstance;
    }

    /**
     * 门店列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(){
        $params = $this->request->param('');
        $params['city_id'] = $this->cityid;
        
        
        $result1 = ShopModel::searchList($params);
        $result = $result1[0];
        
        $ShopDevice = new ShopDevice();
        $device = $ShopDevice->where("shop_id",9)->select();
 
        $current_time = time();
        foreach ($device as $item) {
            if ($item['status'] == '0') {
                // status为0时，显示空闲中
                $str = "空闲中";
            } else{
                // status为1时，计算已使用时间（分钟）
                $time_diff = $current_time - $item['updatetime']; // 计算时间差（秒）
                $minutes = floor($time_diff / 60); // 转换为分钟
                // 确保分钟数不为负数
                if ($minutes < 0) {
                    $minutes = 0;
                }
                
                $str = "已洗{$minutes}分";
            } 
            if($item['doorindex'] ==1){
                $result['number1'] = $str;
            }
            if($item['doorindex'] ==2){
                $result['number2'] = $str;
            }
        }
        
        
        $this->success('',$result1);
    }

    /**
     * 服务列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function service_lists(){
        $params = $this->request->param('');
        //$params['city_id'] = $this->cityid;
        $model = new ShopBranchService();
        $where = [];
        if($q = array_get($params,'q')){
            $where['service.name'] = ["LIKE","%$q%"];
        }
        $sql = $model->where($model->getTable().".status",'normal')
            ->where("shop_service.status",'normal')
            ->join("xiluxc_service service",'service.id='.$model->getTable().'.service_id')
            ->join("xiluxc_shop_service shop_service",'shop_service.service_id='.$model->getTable().'.service_id')
            ->field($model->getTable().".shop_id")
            ->group($model->getTable().'.shop_id')
            ->whereLike('service.name',"%$q%")
            ->buildSql();
        $shop = new \app\common\model\xiluxc\brand\Shop();
        $shop->normal()->passed();
        $lat = array_get($params,'lat');
        $lng = array_get($params,'lng');
        if($lng && $lat){
            $shop->field("id,name,type,image,point,lat,lng,address,sales,(ST_DISTANCE(POINT(".$lng.", ".$lat."), POINT(lng, lat)) * 111195) AS distance");
        }else{
            $shop->field("id,name,type,image,point,lat,lng,address,sales");
        }
        if($districtId = array_get($params,'district_id')){
            $shop->where("district_id",$districtId);
        }
        $sort  = array_get($params,'sort','weigh');
        $order  = array_get($params,'order','desc');
        $lists = $shop->join([$sql=>'service'],'service.shop_id='.$shop->getTable().'.id','inner')
            ->order($sort,$order)
            ->order("point","desc")
            ->paginate(request()->param('pagesize',10))
            ->each(function ($row){
                $row->append(['image_text']);
                $row->shop_services = $row->shopServices();
                $row->distance = isset($row->distance) ? ($row->distance>=1000 ? bcdiv($row->distance,1000,1).'km' : bcadd($row->distance,0,1).'m') : '';
            });
        $this->success('',$lists);
    }


    /**
     * 门店详情
     */
    public function detail(){
        $params = $this->request->param('');
        $lat = array_get($params,'lat');
        $lng = array_get($params,'lng');
        if($lng && $lat){
            $field = "*,(ST_DISTANCE(POINT(".$lng.", ".$lat."), POINT(lng, lat)) * 111195) AS distance";
        }else{
            $field = "*";
        }
        $shopId = array_get($params,'shop_id');
        $shop = new ShopModel();
        $shop = $shopId?$shop->field($field)->normal()->passed()->where('id',$shopId)->find():null;
        if(!$shop){
            $this->error("门店不存在或已下架");
        }
        //服务与套餐
        $shop->append(['images_text','shop_tag']);
        $shop->shop_services = $shop->shopServices();
        $shop->shop_package = $shop->shopPackage;
        $shop->distance = isset($shop->distance) ? ($shop->distance>=1000 ? bcdiv($shop->distance,1000,1).'km' : bcadd($shop->distance,0,1).'m') : '';
        //优惠券
        $userId = $this->auth->id;
        // $shop->setAttr("coupons",Coupon::getCoupons($shop->id,$userId));
        
        
        $coupon = new UserCoupon();
        $couponInfo = $coupon->where("shop_id",$shopId)->where("user_id",$userId)->where("use_status",'0')->select();
        // var_dump($couponInfo);
        $coupons = [];
        if(!empty($couponInfo)){
            foreach ($couponInfo as $item) {
               $coupons[] = [
                    'id'   => $item['id'],
                    'name' => $item['coupon_id'],
                    'platform' => $item['platform'],
                    'time' =>$item['createtime'],
                ];
            }
        }
        $shop->setAttr("coupons",$coupons);
        
        
        //充值金额
        $shop->setAttr("recharge",Recharge::getRecharge($shop));
        //会员卡
        $shop->setAttr("vip",Shopvip::getVip($shop));
        
        $ShopDevice = new ShopDevice();
        $device = $ShopDevice->where("shop_id",$shopId)->select();
        $shop->shop_id = $shopId;
        
        $current_time = time();
        $result = [];
        foreach ($device as $item) {
            if ($item['status'] == '0') {
                // status为0时，显示空闲中
                $str = "空闲中";
            } else{
                // status为1时，计算已使用时间（分钟）
                $time_diff = $current_time - $item['updatetime']; // 计算时间差（秒）
                $minutes = floor($time_diff / 60); // 转换为分钟
                // 确保分钟数不为负数
                if ($minutes < 0) {
                    $minutes = 0;
                }
                
                $str = "已使用{$minutes}分钟";
            } 
           $result[] = [
                'id'   => $item['id'],
                'name' => $item['name'],
                'uid'  => $item['cur_userid'],
                'status_str' => $str,
                'status' =>$item['status'],
                'fee'   => $item['firstfee'],
            ];
        }
        
        $userShopAccount = new UserShopAccount;
        $shopInfo = $userShopAccount->where("shop_id",$shopId)->where("user_id",$userId)->find();
        if(!empty($shopInfo)){
            $shop->balance = $shopInfo['money'];
        }else{
            $shop->balance = 0;
        }
        

        
        $shop->setAttr("device",$result);
        //是否会员
        $shop->setAttr("is_vip",$this->auth->isLogin()?UserShopVip::shopDetailVip($userId,$shop):['status'=>0]);
        $this->success('',$shop);

    }

    //管理员控制开关门的指令
    public function doorControlAdmin(){
        
        $params     = $this->request->param('');
        $operateId  = array_get($params,'operateId'); //1开  2关 
        $doorindex  = array_get($params,'doorindex');
        
        $userId     = $this->auth->id;
        if($userId == 5 || $userId == 8){
             $this->sendDoorControl($doorindex,$operateId,1);
        }
        
        $result = [];
        $this->success('控制成功',$result);
    }
        
    //门控制处理
    private function sendDoorControl($doorindex,$action,$flag){
        $b = ($action == 1 && $doorindex ==1) ? 910050:100000;
        $c = ($action == 2 && $doorindex ==1) ? 910050:100000;

        
        $f = ($action == 1 && $doorindex ==2) ? 910050:100000;
        $g = ($action == 2 && $doorindex ==2) ? 910050:100000;

        $commandType = [
            "A01" => 100000,
            "A02" => $b,
            "A03" => $c,
            "A04" => 100000,
            "A05" => 100000,
            "A06" => $f,
            "A07" => $g,
            "A08" => 100000,
            "res" => "666888"
        ]; 

         
        $indexController = $this->getIndexController();
        $indexController->sendCommandTx($commandType);
    }
    public function allPowerControl(){
        $params     = $this->request->param('');
        $shopId     = array_get($params,'shopId');
        $operateId  = array_get($params,'operateId'); //1通  2断

        $userId     = $this->auth->id;
        if($userId == 5 || $userId == 8){
             
            $indexController = $this->getIndexController();
            if($operateId ==1){
                 $indexController->openElectric(1);
                 $indexController->openElectric(2);
             }else{
                 $indexController->closeElectric(1);
                 $indexController->closeElectric(2);
             }
        }
        
        $result = [];
        $this->success('控制成功',$result);
    }

    public function electricControlAdmin(){
        $params     = $this->request->param('');
        $shopId     = array_get($params,'shopId');
        $operateId  = array_get($params,'operateId'); //1通  2断
        $doorindex  = array_get($params,'doorindex');
        
        $userId     = $this->auth->id;
        if($userId == 5 || $userId == 8){
             $this->sendElectricControl($doorindex,$operateId);
        }
        
        $result = [];
        $this->success('控制成功',$result);
    }
    //电控制处理
    private function sendElectricControl($doorIndex,$action){
        $indexController = $this->getIndexController();
     
        if($action ==1){
             $indexController->openElectric($doorIndex);
         }else{
             $indexController->closeElectric($doorIndex);
         }
    }

    //管理员箱子控制
    public function boxControlAdmin(){
        $params     = $this->request->param('');
        $shopId     = array_get($params,'shopId');
        $operateId  = array_get($params,'operateId'); //1通  2断
        $doorindex  = array_get($params,'doorindex');
        $boxid      = array_get($params,'boxid');
        
        $userId     = $this->auth->id;
        if($userId == 5 || $userId == 8){
             $this->sendBoxControl($doorindex,$boxid,$operateId);
        }
        
        $result = [];
        $this->success('控制成功',$result);
    }
    
    private function sendBoxControl($doorIndex,$boxid,$operateId){
        $boxinfo = new ShopDeviceBox();
        $boxData = $boxinfo->where('doorindex', $doorIndex)
                          ->where('boxid', $boxid)
                          ->find();
        if ($boxData) {
            $indexController = $this->getIndexController();
            if($operateId == 1){ //开箱子
                $indexController->openOneBox($doorIndex,$boxData['boxid']);
            }elseif($operateId == 2){ //重置
                $boxData->status = 0;
                $boxData->save();
            }
            
        }
    }
    
    public function openUsedStorageBox(){
        $params     = $this->request->param('');
        $shopId     = array_get($params,'shopId');
        $doorindex  = array_get($params,'doorindex');
        
        $boxinfo = new ShopDeviceBox();
        $boxList = $boxinfo->where('doorindex', $doorIndex)->where('status', 1)->select();
        
        if (!$boxList) return false;
        
        $userId     = $this->auth->id;
        if($userId == 5 || $userId == 8){
    
            $indexController = $this->getIndexController();
            foreach ($boxList as $box) {
                $indexController->openOneBox($doorIndex, $box['boxid']);
            }
            
            $boxinfo->where('doorindex', $doorIndex)->where('status', 1)->update(['status' => 0]);
        }
            
        $result = [];
        $this->success('控制成功',$result);
    }
    
    public function allBoxControlAdmin(){
        $params     = $this->request->param('');
        $shopId     = array_get($params,'shopId');
        $operateId  = array_get($params,'operateId'); //1开所有  2重置所有
        $doorindex  = array_get($params,'doorindex');

        $userId     = $this->auth->id;
        if($userId == 5 || $userId == 8){
             $this->sendAllBoxControl($doorindex,$operateId);
        }
        
        
        $result = [];
        $this->success('控制成功',$result);
    }
    
    private function sendAllBoxControl($doorIndex, $operateId)
    {
        $boxinfo = new ShopDeviceBox();
        $boxList = $boxinfo->where('doorindex', $doorIndex)->select();
        
        if (!$boxList) return false;
        
        if ($operateId == 1) { // 开箱子
            $indexController = $this->getIndexController();
            foreach ($boxList as $box) {
                $indexController->openOneBox($doorIndex, $box['boxid']);
            }
        } elseif ($operateId == 2) { // 重置
            $boxinfo->where('doorindex', $doorIndex)->update(['status' => 0]);
        }
        
        return true;
    }

    //控制开关门的指令，要基于当前用户还在使用中
    public function doorControl(){
        
        $params     = $this->request->param('');
        $operateId  = array_get($params,'operateId'); //1开  3关  2停 
        $shopId     = array_get($params,'shopId');
        
        
        
        $userId     = $this->auth->id;
        $ShopDevice = new ShopDevice();
        $device     = $ShopDevice->where("shop_id",$shopId)->where("cur_userid",$userId)->find();
        if(empty($device)){
            $this->error("设备不存在");
        }
        
        if($device['status'] == '1'){
            $this->sendDoorControl($device['doorindex'],$operateId,0);
        }
        
        $result = [];
        $this->success('控制成功',$result);
    }
    
    //核销券
    public function cunponCheck(){
        $params = $this->request->param('');
        $shopid = array_get($params,'shopid'); 
        $couponId   = array_get($params,'couponId');  
        
        $userId = $this->auth->id;
        $coupon = new Coupon();
        
        
        $couponInfo = $coupon->where("shop_id",$shopId)->where("user_id",$userId)->where("coupon_id",$couponId)->find();
        if(empty($couponInfo)){
            $this->error("核销码不存在");
        }
        

        $couponInfo->status = 1;  // 假设状态改为已使用
        
        $t = time();
        $couponInfo->updatetime = $t;  // 使用时间
        $couponInfo->use_time   = $t;  // 使用时间
        $couponInfo->save();
        
        $result = [];
        $this->success('核销成功',$result);
    }
    
    public function checkBalanceRightNow() {
        $ShopDevice = new ShopDevice();
    
        try {
            // 获取所有洗车状态的设备
            $devices = $ShopDevice->where("status", 1)->select();
            
            if (empty($devices)) {
                return 'success,no car '; // 没有正在洗车的设备，也返回success
            }
            
            foreach ($devices as $device) {
                $shopId = $device['shop_id'];
                $doorIndex = $device['doorindex'];
                $userId = $device['cur_userid'];
                
                // 获取用户在当前洗车店的余额信息
                $userShopAccount = new UserShopAccount();
                $shopInfo = $userShopAccount->where("shop_id", $shopId)
                                            ->where("user_id", $userId)
                                            ->find();
                
                if (!$shopInfo) {
                    // 用户账户不存在，跳过当前设备
                    continue;
                }
                
                $currentTime = time();
                $startTime = $device['updatetime'];
                
                // 确保时间有效
                if (!$startTime || $startTime <= 0) {
                    continue;
                }
                
                $washTime = $currentTime - $startTime;
                
                // 如果洗车时间小于等于0，跳过
                if ($washTime <= 0) {
                    continue;
                }
                
                $fee = 0;
                $shouldDeductBalance = false;
                
                
                $minutes = floor($washTime / 60);
                $seconds = $washTime % 60;
                $timeStr = $minutes . '分' . $seconds . '秒';
                
                $indexController = $this->getIndexController();
                $indexController->sendCommandLed($doorIndex,2,$timeStr);
                
                // 根据计费类型进行不同的扣费计算
                switch ($device['cur_feetype']) {
                    case 0: // 余额洗车
                        $firsttime = isset($device['firsttime']) ? $device['firsttime'] : 720;
                        $firstfee = isset($device['firstfee']) ? $device['firstfee'] : 8;
                        $secondfee = isset($device['secondfee']) ? $device['secondfee'] : 0.5;
                        
                        if ($washTime <= $firsttime) {
                            $fee = $firstfee;
                        } else {
                            $overTime = $washTime - $firsttime;
                            $secondUnits = ceil($overTime / 60);
                            $secondFee = $secondUnits * $secondfee;
                            $fee = $firstfee + $secondFee;
                        }
                        $shouldDeductBalance = true;
                        break;
                        
                    case 1: // 抖音抵扣券
                        $coupon_douyin = isset($device['coupon_douyin']) ? $device['coupon_douyin'] : 900;
                        $secondfee = isset($device['secondfee']) ? $device['secondfee'] : 0.5;
                        
                        if ($washTime <= $coupon_douyin) {
                            $fee = 0;
                        } else {
                            $overTime = $washTime - $coupon_douyin;
                            $secondUnits = ceil($overTime / 60);
                            $fee = $secondUnits * $secondfee;
                            $shouldDeductBalance = true;
                        }
                        break;
                        
                    case 2: // 美团抵扣券
                        $coupon_meituan = isset($device['coupon_meituan']) ? $device['coupon_meituan'] : 900;
                        $secondfee = isset($device['secondfee']) ? $device['secondfee'] : 0.5;
                        
                        if ($washTime <= $coupon_meituan) {
                            $fee = 0;
                        } else {
                            $overTime = $washTime - $coupon_meituan;
                            $secondUnits = ceil($overTime / 60);
                            $fee = $secondUnits * $secondfee;
                            $shouldDeductBalance = true;
                        }
                        break;
                        
                    default:
                        // 未知计费类型，跳过
                        continue 2;
                }
                
                // 如果需要扣余额，检查余额是否足够
                if ($shouldDeductBalance && $fee > 0) {
                    $userBalance = isset($shopInfo['money']) ? $shopInfo['money'] : 0;
                    $oldFee = isset($device['fee']) ? $device['fee'] : 0;
                    $realfee = $fee - $oldFee;
                    
                    if ($realfee > 0) { // 只有需要扣费时才检查余额
                        if ($userBalance < $realfee || $userBalance < 1) {
                            // 余额不足，停水停电
                            $this->closeElectric($doorIndex);
                            continue;
                        }
                        
                        // 执行余额扣款
                        $userShopAccount->where("shop_id", $shopId)
                                       ->where("user_id", $userId)
                                       ->update(['money' => $userBalance - $realfee]);
                    }
                }
                
                // 更新设备的fee字段
                $ShopDevice->where("shop_id", $shopId)
                          ->where("doorindex", $doorIndex)
                          ->update(['fee' => $fee]);
            }
            
            return 'success1';
            
        } catch (\Exception $e) {
            // 记录错误日志但不抛出异常
            // Log::error('扣费处理异常: ' . $e->getMessage());
            return 'fail'; // 即使出错也返回success，保证接口不报错
        }
}

    
    // 开始开门
    public function detailOpen(){
        $params = $this->request->param('');
        $doorIndex = array_get($params,'doorIndex'); //开门编号
        $payType   = array_get($params,'payType');   //支付类型 0余额， 1抵扣券
        $shopId    = array_get($params,'shopId');   //对应的shopid
        $couponId  = array_get($params,'couponId'); //使用核销券的情况下需要传， 即payType为1时 
        $cartype    = array_get($params,'carType');

        $shop = new ShopModel();
        $shop = $shopId?$shop->normal()->passed()->where('id',$shopId)->find():null;
        if(!$shop){
            $this->error("门店不存在或已下架");
        }  
        
        $ShopDevice = new ShopDevice();
        $device = $ShopDevice->where("shop_id",$shopId)->where("doorindex",$doorIndex)->find();
        // var_dump($device);
        if(empty($device)){
            $this->error("设备不存在1");
        }
        
        if($device['status'] != '0'){
            $this->error("设备占用中");
        }
        $userId = $this->auth->id;
        $coupon = new UserCoupon();
        
        if($payType == '1'){ //验券
        
            $couponInfo = $coupon->where("shop_id",$shopId)->where("user_id",$userId)->where("coupon_id",$couponId)->find();
            if(empty($couponInfo)){
                $this->error("核销码不存在");
            }
            

            $couponInfo->status = 1;  // 假设状态改为已使用
            
            $t = time();
            $couponInfo->updatetime = $t;  // 使用时间
            $couponInfo->use_time   = $t;  // 使用时间
            $couponInfo->save();
            
            $keeptime = $couponInfo['keeptime']*60; //洗车时间 
            
            $device ->status = 1;
            $device ->cur_userid = $userId;
            $device ->cur_feetype = '1';
            $device ->cartype = $cartype;
            $device ->updatetime  = $t;
            $device ->finishtime  = $t+$keeptime;
            $device ->fee         = 0;
            $device ->save();
  
            
        }else{ //判断用户余额
            
            $userShopAccount = new UserShopAccount;
            $shopInfo = $userShopAccount->where("shop_id",$shopId)->where("user_id",$userId)->find();
 
   
            if(empty($shopInfo)){
                $this->error("用户信息异常1");
            }
            if($shopInfo['money'] < $device['firstfee']){
                $this->error("余额不足");
            }
            
            $t = time();
            $device ->status      = 1;
            $device ->cur_userid  = $userId;
            $device ->cur_feetype = '0';
            $device ->cartype = $cartype;
            $device ->updatetime  = $t;
            $device ->fee         = 0;
            $device ->save();
  
        }
        
        //开箱子
        $this->openBox($doorIndex);

                
        //开门通电
        $this->openDoorElectric($doorIndex);
        
        
        $indexController = $this->getIndexController();
        $indexController->sendCommandLed($doorIndex,2,"0分10秒");
        
        
        
        $result = [];

        $this->success('',$result);
    }
    
    //开门通电
    private function openDoorElectric($doorIndex){
  
        $ShopDevice  = new ShopDevice();
        $device1     = $ShopDevice->where("shop_id",9)->where("doorindex",1)->find();
        $deviceOther = $ShopDevice->where("shop_id",9)->where("doorindex",2)->find();
        $a = 100000;
        $e = 100000;
        
        if($doorIndex ==1){
           $a =  110000;
           $e = $deviceOther['status'] == 1? 110000 : 100000;
        }elseif($doorIndex ==2){
            $a = $device1['status'] == 1? 110000 : 100000;
            $e = 110000;
        }
        
        $b = $doorIndex ==1 ? 910050:100000;
        $f = $doorIndex ==2 ? 910050:100000;

        $commandType = [
            "A01" => $a,
            "A02" => $b,
            "A03" => 100000,
            "A04" => 100000,
            "A05" => $e,
            "A06" => $f,
            "A07" => 100000,
            "A08" => 100000,
            "res" => "666888"
        ]; 
        $indexController = $this->getIndexController();
        $indexController->openDoorCommand($doorIndex);
        
    }
    
    //洗车结束，不需要关门，断电即可
    public function closeElectricStatus($doorIndex){

        $indexController = $this->getIndexController();
        $indexController->closeElectric($doorIndex);
        
        // $indexController->sendCommandLed($doorIndex,1,"");
    }
    
    private function openBox($doorIndex){
        $boxinfo = new ShopDeviceBox();
    
        // 按id从小到大排序，查询第一条 doorindex 等于 $doorIndex 的数据
        $boxData = $boxinfo->where('doorindex', $doorIndex)->where('status', 0)
                        //   ->order('id', 'asc')
                           ->find();
        
        // 如果有数据，返回数据
        if ($boxData) {
            $indexController = $this->getIndexController();
            $indexController->openOneBox($doorIndex,$boxData['boxid']);
            
            $boxData->status = 1;
            $boxData->save();
        }
    }

    
    public function endWashing(){
        $params    = $this->request->param('');
        $doorIndex = array_get($params,'doorIndex'); //开门编号
        $shopId    = array_get($params,'shopId');   //对应的shopid
            
        $userId = $this->auth->id;
            $shopId = 9;
        $shop = new ShopModel();
        $shop = $shopId?$shop->normal()->passed()->where('id',$shopId)->find():null;
        if(!$shop){
            $this->error("门店不存在或已下架");
        }  
        
        $ShopDevice = new ShopDevice();
        $device = $ShopDevice->where("shop_id",$shopId)->where("cur_userid",$userId)->find();
        
        $doorIndex = $device['doorindex'];
        // var_dump($device);
        if(empty($device)){
            $this->error("设备不存在");
        }
        
        if($device['status'] != '1'){
            $this->error("设备异常");
        }
        $userId = $this->auth->id;
        if($userId != $device ->cur_userid){
            $this->error("操作异常");
        }
        
        $t = time();

        $record = [];
        $record['shop_id'] = $shopId;
        $record['user_id'] = $device['cur_userid'];
        $record['fee'] = $device->fee;
        $record['cur_feetype'] = $device->cur_feetype;
        // 计算洗车时长（秒）cur_feetype
        $duration_seconds = $t - $device->updatetime;
        
        // 将秒数转换为 xx分xx秒 格式
        if ($duration_seconds < 60) {
            $record['washtime'] = $duration_seconds . '秒';
        } else {
            $minutes = floor($duration_seconds / 60);
            $seconds = $duration_seconds % 60;
            $record['washtime'] = $minutes . '分' . $seconds . '秒';
        }
        
        // 将时间戳转换为洗车时间：x年x月x日 时分秒
        $begin_time = $device->updatetime;
        $record['begintime'] = date('Y-m-d H:i:s', $begin_time);
        $record['createtime'] = time();
        
        $WashLog = new WashLog();
        $WashLog->save($record);
        
            
        $device ->status = 0;
        $device ->cur_userid = 0;
        $device ->cur_feetype = '1';
        $device ->updatetime  = $t;
        $device ->fee         = 0;
        $device ->save();

        $this->closeElectricStatus($doorIndex);
        
        $result = [];

        $this->success('',$result);

    }

    /**
     * 服务详情
     */
    public function service_detail(){
        $params = $this->request->param('');
        $shopServiceId = array_get($params,'id');
        $shopId = array_get($params,'shop_id');
        $shopBranchService = new ShopBranchService();
        $shopBranchService = $shopBranchService->normal()->where("shop_id",$shopId)->where("shop_service_id",$shopServiceId)->find();
        if(!$shopBranchService){
            $this->error("服务不存在或已下架");
        }
        $shopService = new ShopService();
        $shopService = $shopServiceId?$shopService->with(['service_price'])->normal()->where('id',$shopServiceId)->find():null;
        if(!$shopService || !$shopService->service){
            $this->error("服务不存在或已下架");
        }
        $shopService->setAttr("is_vip",$this->auth->isLogin()?UserShopVip::shopDetailVip($this->auth->id,$shopId):['status'=>0]);
        $this->success('',$shopService);
    }

    /**
     * 套餐详情
     */
    public function package_detail(){
        $params = $this->request->param('');
        $packageId = array_get($params,'id');
        $shopId = array_get($params,'shop_id');
        $shopBranchPackage = new BranchPackage();
        $shopBranchPackage = $shopBranchPackage->normal()->where("shop_id",$shopId)->where("shop_package_id",$packageId)->find();
        if(!$shopBranchPackage){
            $this->error("套餐不存在或已下架");
        }
        $package = new Package();
        $package = $packageId?$package->normal()->where('id',$packageId)->find():null;
        if(!$package){
            $this->error("套餐不存在或已下架");
        }
        $package->relationQuery(['package_service2'=>function($q){
            $q->with(['service','service_price']);
        }]);
        $package->setAttr("is_vip",$this->auth->isLogin()?UserShopVip::shopDetailVip($this->auth->id,$shopId):['status'=>0]);
        $this->success('',$package);
    }

}
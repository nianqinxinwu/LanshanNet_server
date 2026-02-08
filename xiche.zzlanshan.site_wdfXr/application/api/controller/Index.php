<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Mqtt;
use think\Db;
use think\Model;

use app\common\library\Douyin;

use app\common\model\xiluxc\CameraLog;
use app\common\model\xiluxc\WashLog;
use app\common\model\xiluxc\brand\ShopDevice;

use app\common\model\xiluxc\brand\ShopDeviceBox;


class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    // public function update
    
    


    private $url = 'https://open.qianmingyun.com/webapi/apiService/v1/s1/';
    private $appKey = "4MZE3IVlGJ0VxUepZAsrhp0IzhKitf2r";
    private $secretKey = "L8d1f4qg0IuruYDzDzGvREFifmTw931I";


public function CheckDYWebhook()
{
    // 获取POST数据
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // 记录日志
    $logContent = "[" . date('Y-m-d H:i:s') . "] " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents(__DIR__ . '/douyin.txt', $logContent, FILE_APPEND | LOCK_EX);
    
    // 获取challenge参数 - 正确的路径
    // 根据你的日志：challenge在content字段里
    $challenge = $data['content']['challenge'] ?? '';
    
    // 抖音要求返回JSON对象，包含challenge字段
    if ($challenge) {
        return json(['challenge' => $challenge]);
    }
    
    // 其他回调返回成功对象
    return json(['success' => true]);
}


    public function verifyDouyin()
    {
        // 1. 接收小程序端参数
        // $code = input('post.code', '');
        // $poiId = input('post.poi_id', ''); // 如果多门店，小程序需传递选择的门店ID
        // $operator = input('post.operator', '系统'); // 操作员
        
        $code = '108269363292002';
        $poiId = '7187651466377578553';
        $operator = 'admin';
        
        if (empty($code) || empty($poiId)) {
            return json(['err_code' => 400, 'err_msg' => '参数缺失']);
        }
        
        try {
            // 2. 实例化服务，调用核销
            var_dump('111111111111111110000');
            $douyinService = new Douyin();
            var_dump('1111111111111111112222');
            $result = $douyinService->verifyCoupon($code, $poiId);
            
            var_dump($result);
            var_dump('111111111111111111');
            // 3. 解析抖音接口返回
            $data = $result['data'] ?? [];
            $error = $result['error'] ?? [];
            
            if ($error['code'] != 0) {
                // 核销失败（可能是已核销、无效券码等）
                return json([
                    'err_code' => $error['code'],
                    'err_msg'  => $error['message'] ?? '核销失败',
                    'detail'   => $data // 可能包含具体状态
                ]);
            }
            
            // 4. 核销成功！记录到本地数据库（建议）
            $this->saveVerifyLog([
                'code'          => $code,
                'poi_id'        => $poiId,
                'order_id'      => $data['order_id'] ?? '',
                'verify_time'   => time(),
                'operator'      => $operator,
                'status'        => 1,
            ]);
            
            // 5. 返回成功信息给小程序
            return json([
                'err_code' => 0,
                'err_msg'  => 'success',
                'data'     => [
                    'verify_time'   => date('Y-m-d H:i:s'),
                    'order_id'      => $data['order_id'] ?? '',
                    'goods_name'    => $data['goods_info']['name'] ?? '未知商品',
                ]
            ]);
            
        } catch (\Exception $e) {
            // Log::error('核销过程异常：' . $e->getMessage());
            return json(['err_code' => 500, 'err_msg' => '系统异常，请稍后重试']);
        }
    }
    
    /**
     * 将核销记录存入数据库（示例）
     */
    private function saveVerifyLog($logData)
    {
        // 这里替换为你的实际模型操作，例如：
        // Db::name('douyin_verify_log')->insert($logData);
        // 或者使用模型
        // $log = new DouyinVerifyLogModel();
        // $log->save($logData);
        
        // 记录日志用于演示
        Log::info('核销成功记录：' . json_encode($logData));
    }


    public function chkDevice()  //检查设备是否在线
    {
        $id     = '291514269'; //686343136  https://open.qianmingyun.com/webapi/apiService/v1/s1/chkDevice
        $action = 'chkDevice';
        $arr    = $this->getParam($id, []);
        $a      = $this->post($action, $arr);
        
        // var_dump($a);
    }
    
    public function set_totalBoxNum()  //设置箱门总数checkBoxDevice
    {
        $id     = '686343136'; //291514269   686343136 https://open.qianmingyun.com/webapi/apiService/v1/s1/set_totalBoxNum
        $action = 'set_totalBoxNum';
        $arr    = $this->getParam($id, ["boxCount" => 24]);
        $a      = $this->post($action, $arr);
        
        var_dump($a);
    }
    
    public function get_boxInfo()  //查询箱门状态
    {
        $id     = '291514269'; //291514269   686343136 https://open.qianmingyun.com/webapi/apiService/v1/s1/get_boxInfo
        $action = 'get_boxInfo';
        $arr    = $this->getParam($id, ["boxNo" => 10]);
        $a      = $this->post($action, $arr);
        
        var_dump($a);
    }
    
    
    public function openOneBox($doorindex,$boxid)  //打开指定箱门
    {
        $id     = $doorindex == 1? '291514269':'686343136'; 
        $action = 'openOneBox';
        $arr    = $this->getParam($id, ["boxNo" => $boxid]);
        $a      = $this->post($action, $arr);
  
    }
    
    
    public function clearAllBox()  //开全部箱门
    {
        $id     = '291514269'; //291514269   686343136   https://open.qianmingyun.com/webapi/apiService/v1/s1/clearAllBox直接开门并清除数据
        $action = 'openOneBox';
        $arr    = $this->getParam($id, ["boxNo" => 10]);
        $a      = $this->post($action, $arr);
        
        var_dump($a);
    }
    
   
    

    private function getParam($id, $addParam)
    {
        $arr = [
            "cabinetId" => $id,
            "timeStamp" => time() . "453",
        ];
        $arr = $arr + $addParam;
        $sign = $this->sign($arr);
        $arr['sign'] = $sign;
        $arr['appKey'] = $this->appKey;
        return $arr;
    }

    private function sign($parameter)
    {
        $parameter['appKey'] = $this->appKey;
        ksort($parameter);
//        $str = http_build_query($parameter) . $this->secretKey;
        foreach ($parameter as $key => $val) {
            $aPOST[] = $key . "=" . ($val);
        }
        $strPOST = join("&", $aPOST) . $this->secretKey;
        return md5($strPOST);

    }

    private function post($action, $param)
    {
        $oCurl = curl_init();
//        $param['isOpen'] = 0;
        foreach ($param as $key => &$val) {
            $val = ($val);
        }
        $url = $this->url . $action;
        $strPOST = json_encode($param, 1);
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($strPOST)));
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return json_decode($sContent, 1);
        } else {
            return false;
        }
    }
    
    public function receiveCommandsgdsdgsdgdgdggmjfghyyert65754677fghfg()
    {
        // 获取POST数据（EMQX传过来的）
        $input = file_get_contents('php://input');

        $data_utf_8 = $this->yang_gbk2utf8($input);

        $data = json_decode($data_utf_8, true);
        

        // $logContent = "[" . date('Y-m-d H:i:s') . "] " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";

        // file_put_contents(__DIR__ . '/mqtt_log.txt',$logContent, FILE_APPEND | LOCK_EX); 
        
        
        
        // if(!empty($data['topic']) && strpos($data['topic'], 'sub7') !== false  &&  !empty($data['payload']) ){

        
        //     $info = json_decode($data['payload'], true);
        //     if($info['HumanSenseState'] == 0){
        //         $doorindex = 1;
        //         if($data['clientid'] == 'leidanumber2'){
        //             $doorindex = 2;
        //         }
                
                
        //         $device = $ShopDevice->where("shop_id", 9)->where("doorindex", $doorindex)->find();
                
        //         if($device['status'] ==1){
        //         $CameraLog = new CameraLog();
   
        //         $logData = [
        //             'createtime' => time(),
        //         ];

        //         $shopId = 9; 
                
        //         $logData['shop_id']     = $shopId;
        //         $logData['name']        = $data['clientid'];
        //         $logData['img']         = 'leida';
        //         $logData['carColor']    = $info['HumanSenseState'];
        //         $logData['license']     = $info['MotionSenseState'];
        //         $logData['cardType']    = $info['ActiveState'];
        //         $logData['platecolor']  = 1;
        //         $logData['cartype']     = 1;
                
        //         $result = $CameraLog->save($logData);
   
        //         $ttt = time()-$device['updatetime'];
                
        //         //HumanSenseState = 1 是有人， HumanSenseState = 0是没人
        //         if($ttt > 180){ 
   
        //             $record = [];
        //             $record['shop_id']     = $shopId;
        //             $record['user_id']     = $device['cur_userid'];
        //             $record['fee']         = $device->fee;
        //             $record['cur_feetype'] = $device->cur_feetype;
        //             // 计算洗车时长（秒）cur_feetype
        //             $duration_seconds = time() - $device->updatetime;
                    
        //             // 将秒数转换为 xx分xx秒 格式
        //             if ($duration_seconds < 60) {
        //                 $record['washtime'] = $duration_seconds . '秒';
        //             } else {
        //                 $minutes = floor($duration_seconds / 60);
        //                 $seconds = $duration_seconds % 60;
        //                 $record['washtime'] = $minutes . '分' . $seconds . '秒';
        //             }
                    
        //             // 将时间戳转换为洗车时间：x年x月x日 时分秒
        //             $begin_time = $device->updatetime;
        //             $record['begintime']  = date('Y-m-d H:i:s', $begin_time);
        //             $record['createtime'] = time();
                    
        //             $WashLog = new WashLog();
        //             $WashLog->save($record);    
                    

        //             $device ->status = 0;
        //             $device ->cur_userid = 0;
        //             $device ->cur_feetype = '0';
        //             $device ->updatetime  = time();
        //             $device ->fee         = 0;
        //             $device ->save();

        //             $result = $this->closeElectric(1);

        //             $this->sendCommandLed(1,1,"0分10秒");
        //         }
                
        //         }
        //     }
        
        // }

    }
    

    
    
    public function testOpenDoor(){
        $commandType = [
            "A01" => 100000,
            "A02" => 910050,
            "A03" => 100000,
            "A04" => 100000,
            "A05" => 100000,
            "A06" => 100000,
            "A07" => 100000,
            "A08" => 100000,
            "res" => "666888"
        ]; 
        $this->sendCommandTx($commandType);
    }
    
    public function openDoorCommand($doorIndex){
        
        $b = $doorIndex ==1 ? 910050:100000;
        $f = $doorIndex ==2 ? 910050:100000;

        $commandType = [
            "A01" => 100000,
            "A02" => $b,
            "A03" => 100000,
            "A04" => 100000,
            "A05" => 100000,
            "A06" => $f,
            "A07" => 100000,
            "A08" => 100000,
            "res" => "666888"
        ]; 
        

        $commandData = [
            'type' => $commandType, 
            'params' => [],
            'timestamp' => time(),
            'from' => 'fastadmin'
        ];
        
        $topic = "a1P2scMba3a/device/sub";
        $mqtt = new Mqtt();
        $messageId = $mqtt->sendCommand($topic, $commandData, 1); 

        $this->openElectric($doorIndex);

    }
    
    
    public function sendCommandTx($str){
        $params = $this->request->post('params', []);
        $commandData = [
            'type' => $str, 
            'params' => $params,
            'timestamp' => time(),
            'from' => 'fastadmin'
        ];
        $topic = "a1P2scMba3a/device/sub";
        $mqtt = new Mqtt();
        $messageId = $mqtt->sendCommand($topic, $commandData, 1); 
        if ($messageId !== null) { // 注意：QoS=1时，有效ID可能是整数0+
            $this->success('指令已发送', '', ['message_id' => $messageId]);
        } else {
            $this->error('指令发送失败（无messageId）');
        }
    }
    
    public function sendCommandTx1($str){
        $params = $this->request->post('params', []);
        $commandData = [
            'type' => $str, 
            'params' => $params,
            'timestamp' => time(),
            'from' => 'fastadmin'
        ];
        // $topic = "/uKTyeN/hCkBAMPiscBK/c64f33af70351/subscribe";
        $topic = "/a1P2scMba3a/device/sub1";
        $mqtt = new Mqtt();
        $messageId = $mqtt->sendCommand($topic, $commandData, 1); 
        if ($messageId !== null) { // 注意：QoS=1时，有效ID可能是整数0+
            $this->success('指令已发送', '', ['message_id' => $messageId]);
        } else {
            $this->error('指令发送失败（无messageId）');
        }
    }
    
    public function sendCommandTx2($str){
        $params = $this->request->post('params', []);
        $commandData = [
            'type' => $str, 
            'params' => $params,
            'timestamp' => time(),
            'from' => 'fastadmin'
        ];
        // $topic = "/uKTyeN/hCkBAMPiscBK/c64f33af70351/subscribe";
        $topic = "/a1P2scMba3a/device/sub2";
        $mqtt = new Mqtt();
        $messageId = $mqtt->sendCommand($topic, $commandData, 1); 
        if ($messageId !== null) { // 注意：QoS=1时，有效ID可能是整数0+
            $this->success('指令已发送', '', ['message_id' => $messageId]);
        } else {
            $this->error('指令发送失败（无messageId）');
        }
    }
    
        //显示屏控制
    public function sendCommandLed($door,$str1,$str2){
        $data = ''; 
        if($str1 == 1 ){ //空闲中
        
            $data = '[{"index": 1, "color": 2, "font": 1,"fsize": 1, "Data": [32,32,191,213,207,208,32,32]},
                        {"index": 2, "color": 2, "font": 1,"fsize": 1, "Data": [201,168,194,235,207,180,179,181]}]';
        }elseif($str1 == 2){ //使用中  正在洗车编码： 213, 202, 186, 179, 204, 212, 183, 212

            $data = '[{"index": 1, "color": 1, "font": 1,"fsize": 1, "Data": [213,253,212,218,207,180,179,181]},
                        {"index": 2, "color": 1, "font": 1,"fsize": 1, "Data": [';
            $gb2312Str = mb_convert_encoding($str2, 'GB2312', 'UTF-8');
            $length = strlen($gb2312Str);
            for ($i = 0; $i < $length; $i++) {
                if($i == $length-1 ){
                    $data = $data.ord($gb2312Str[$i]);
                }else{
                    $data = $data.ord($gb2312Str[$i]).',';
                }
            }
            $data = $data.']}]';
        }
        // echo $data;exit;

        $topic = "/a1P2scMba3a/device/sub3";
        if($door == 2){
           $topic = "/a1P2scMba3a/device/sub4"; 
        }
        
        $mqtt = new Mqtt();
        $messageId = $mqtt->sendCommandLed($topic, $data, 0); 
        // if ($messageId !== null) { // 注意：QoS=1时，有效ID可能是整数0+
        //     $this->success('指令已发送', '', ['message_id' => $messageId]);
        // } else {
        //     $this->error('指令发送失败（无messageId）');
        // }
    }
    
    
    public function testCommandLed(){
        $door = 1;
        $str1 =1;
        $str2 = '';
        $data = ''; 
        if($str1 == 1 ){ //空闲中
        
            $data = '[{"index": 1, "color": 2, "font": 1,"fsize": 1, "Data": [32,32,191,213,207,208,32,32]},
                        {"index": 2, "color": 2, "font": 1,"fsize": 1, "Data": [201,168,194,235,207,180,179,181]}]';
        }elseif($str1 == 2){ //使用中  正在洗车编码： 213, 202, 186, 179, 204, 212, 183, 212

            $data = '[{"index": 1, "color": 1, "font": 1,"fsize": 1, "Data": [213,253,212,218,207,180,179,181]},
                        {"index": 2, "color": 1, "font": 1,"fsize": 1, "Data": [';
            $gb2312Str = mb_convert_encoding($str2, 'GB2312', 'UTF-8');
            $length = strlen($gb2312Str);
            for ($i = 0; $i < $length; $i++) {
                if($i == $length-1 ){
                    $data = $data.ord($gb2312Str[$i]);
                }else{
                    $data = $data.ord($gb2312Str[$i]).',';
                }
            }
            $data = $data.']}]';
        }
        // echo $data;exit;

        $topic = "/a1P2scMba3a/device/sub3";
        if($door == 2){
           $topic = "/a1P2scMba3a/device/sub4"; 
        }
        
        $mqtt = new Mqtt();
        $messageId = $mqtt->sendCommandLed($topic, $data, 0); 
        if ($messageId !== null) { // 注意：QoS=1时，有效ID可能是整数0+
            $this->success('指令已发送', '', ['message_id' => $messageId]);
        } else {
            $this->error('指令发送失败（无messageId）');
        }
    }
    
    
    
    //1号显示屏控制
    public function sendCommandTx3(){
        $str1 = 1;
        $str2 = '15分15秒';
        
        $str  = '正在洗车';
        

        $data = ''; 
        if($str1 == 1 ){ //空闲中
        
            $data = '[{"index": 1, "color": 2, "font": 1,"fsize": 1, "Data": [32,32,191,213,207,208,32,32]},
                        {"index": 2, "color": 2, "font": 1,"fsize": 1, "Data": [201,168,194,235,207,180,179,181]}]';
        }elseif($str1 == 2){ //使用中  正在洗车编码： 213, 202, 186, 179, 204, 212, 183, 212

            $data = '[{"index": 1, "color": 1, "font": 1,"fsize": 1, "Data": [213,253,212,218,207,180,179,181]},
                        {"index": 2, "color": 1, "font": 1,"fsize": 1, "Data": [';
            $gb2312Str = mb_convert_encoding($str2, 'GB2312', 'UTF-8');
            $length = strlen($gb2312Str);
            for ($i = 0; $i < $length; $i++) {
                if($i == $length-1 ){
                    $data = $data.ord($gb2312Str[$i]);
                }else{
                    $data = $data.ord($gb2312Str[$i]).',';
                }
            }
            $data = $data.']}]';
        }
        // echo $data;exit;

        $topic = "/a1P2scMba3a/device/sub3";
        
        $mqtt = new Mqtt();
        $messageId = $mqtt->sendCommandLed($topic, $data, 0); 
        if ($messageId !== null) { // 注意：QoS=1时，有效ID可能是整数0+
            $this->success('指令已发送', '', ['message_id' => $messageId]);
        } else {
            $this->error('指令发送失败（无messageId）');
        }
    }
    
    
    //2号显示屏控制  文字转换成GB2312编码的10进制数字
    public function sendCommandTx4(){
        $str1 = 1;
        $str2 = '35分35秒';
        $str  = '正在洗车';
        

        $data = ''; 
        if($str1 == 1 ){ //空闲中
        
            $data = '[{"index": 1, "color": 2, "font": 1,"fsize": 1, "Data": [32,32,191,213,207,208,32,32]},
                        {"index": 2, "color": 2, "font": 1,"fsize": 1, "Data": [201,168,194,235,207,180,179,181]}]';
        }elseif($str1 == 2){ //使用中  正在洗车编码： 213, 202, 186, 179, 204, 212, 183, 212
        // Data: [213, 202, 186, 179, 204, 212, 183, 212]

            $data = '[{"index": 1, "color": 1, "font": 1,"fsize": 1, "Data": [213,253,212,218,207,180,179,181]},
                        {"index": 2, "color": 1, "font": 1,"fsize": 1, "Data": [';
            $gb2312Str = mb_convert_encoding($str2, 'GB2312', 'UTF-8');
            $length = strlen($gb2312Str);
            for ($i = 0; $i < $length; $i++) {
                if($i == $length-1 ){
                    $data = $data.ord($gb2312Str[$i]);
                }else{
                    $data = $data.ord($gb2312Str[$i]).',';
                }
            }
            $data = $data.']}]';
        }
        // echo $data;exit;

        $topic = "/a1P2scMba3a/device/sub4";
        
        $mqtt = new Mqtt();
        $messageId = $mqtt->sendCommandLed($topic, $data, 0); 

        if ($messageId !== null) { // 注意：QoS=1时，有效ID可能是整数0+
            $this->success('指令已发送', '', ['message_id' => $messageId]);
        } else {
            $this->error('指令发送失败（无messageId）');
        }
    }
    
    
    public  function closeElectric($doorIndex){
        $topic = 'a1P2scMba3a/device/sub6';
        if($doorIndex == 2){
           $topic = 'a1P2scMba3a/device/sub5';
        }
        
        $commandType = [
            "A01" => 100000,
            "A02" => 100000,
            "A03" => 100000,
            "A04" => 100000,
            "res" => "666888"
        ]; 
        $this->sendCommandNew($commandType,$topic);
        
        
        
        $this->sendCommandLed($doorIndex,1,"空闲中");
    }
    
    public  function openElectric($doorIndex){
        $topic = 'a1P2scMba3a/device/sub6';
        if($doorIndex == 2){
           $topic = 'a1P2scMba3a/device/sub5';
        }
        
        $commandType = [
            "A01" => 110000,
            "A02" => 100000,
            "A03" => 100000,
            "A04" => 100000,
            "res" => "666888"
        ]; 
        $this->sendCommandNew($commandType,$topic);
        
        
        // $this->sendCommandLed($doorIndex,2,"0分10秒");
    }
    
     public function sendCommandNew($str,$topic){
        $params = $this->request->post('params', []);
        $commandData = [
            'type' => $str, 
            'params' => $params,
            'timestamp' => time(),
            'from' => 'fastadmin'
        ];
        $mqtt = new Mqtt();
        $messageId = $mqtt->sendCommand($topic, $commandData, 1); 
        // if ($messageId !== null) { // 注意：QoS=1时，有效ID可能是整数0+
        //     $this->success('指令已发送', '', ['message_id' => $messageId]);
        // } else {
        //     $this->error('指令发送失败（无messageId）');
        // }
    }
 
     public function newjidianqidian1(){
        $commandType = [
            "A01" => 910100,
            "A02" => 100000,
            "A03" => 100000,
            "A04" => 100000,
            "res" => "666888"
        ]; 
        
        $commandData = [
            'type' => $commandType, 
            'params' => [],
            'timestamp' => time(),
            'from' => 'fastadmin'
        ];
        // $topic = "a1P2scMba3a/device/sub5";  //2号 
        
        $topic = "a1P2scMba3a/device/sub6";  //1号 
                  
        // {"A01":100000,"A02":110000,"A03":100000,"A04":100000,"res":"15555"}
        $mqtt = new Mqtt();
        $messageId = $mqtt->sendCommand($topic, $commandData, 1); 
        if ($messageId !== null) { // 注意：QoS=1时，有效ID可能是整数0+
            $this->success('指令已发送', '', ['message_id' => $messageId]);
        } else {
            $this->error('指令发送失败（无messageId）');
        }
    }
    
     //控制电路
     public function controlElectric($doorindex,$flag){
         
        $a  =   $flag == 1?110000:100000;
        $b  =   $flag == 1?100000:110000;
         
        $commandType = [
            "A01" => $a,
            "A02" => $b,
            "A03" => 100000,
            "A04" => 100000,
            "res" => "666888"
        ]; 
        
        $commandData = [
            'type' => $commandType, 
            'params' => [],
            'timestamp' => time(),
            'from' => 'fastadmin'
        ];
        $topic = "a1P2scMba3a/device/sub6";
        if($doorindex ==1){
            $topic = "a1P2scMba3a/device/sub5";
        }
        
        $mqtt = new Mqtt();
        $messageId = $mqtt->sendCommand($topic, $commandData, 1); 
        if ($messageId !== null) { // 注意：QoS=1时，有效ID可能是整数0+
            $this->success('指令已发送', '', ['message_id' => $messageId]);
        } else {
            $this->error('指令发送失败（无messageId）');
        }
    }
 

    //获取当前继电器状态 
    public function sendCommandzhuangtai234dfdfgf7gbvttt34ff6dgd()
    {
       $commandType = [
            "readall" => 0,
            "res" => "456654"
        ]; 
        $this->sendCommandTx($commandType);
    }
    
    public function sendCameraComand()
    {

        $data       = file_get_contents('php://input'); 
        $data_utf_8 = $this->yang_gbk2utf8($data);
        $testjosn = json_decode($data_utf_8, true);
        
        // 记录日志到文本文件
        $logContent = "[" . date('Y-m-d H:i:s') . "] " . json_encode($testjosn, JSON_UNESCAPED_UNICODE) . "\n";
        
        $ShopDevice = new ShopDevice();
        $device = $ShopDevice->where("shop_id", 9)->where("doorindex", 1)->find();
            
        if(!empty($testjosn['AlarmInfoPlate'])) {

            $CameraLog = new CameraLog();
            $alarmInfo = $testjosn['AlarmInfoPlate'];
          if(!empty($alarmInfo['result']) && !empty($alarmInfo['result']['PlateResult'])   && $device['status'] == '1' && $device['cartype'] == 1) {
              
                $plateResult = $alarmInfo['result']['PlateResult'];

    
                 $shopId = 9; 
                //  $CameraLog->save([
                //         'shop_id' => 9,
                //         'name'     => '工位1',
                //         'img'      => isset($plateResult['imageFile']) ? $plateResult['imageFile'] : '',
                //         'carColor' => isset($plateResult['carColor']) ? $plateResult['carColor'] : 0,
                //         'license'  => isset($plateResult['license']) ? $plateResult['license'] : '',
                //         'cardType' => isset($plateResult['type']) ? $plateResult['type'] : 0,
                //         'platecolor' => isset($plateResult['platecolor']) ? $plateResult['platecolor'] : '',
                //         'cartype' => isset($plateResult['CarType']) ? $plateResult['CarType'] : 0,
                //         'createtime' => time(),
                //     ]);
            // file_put_contents(__DIR__ . '/camera_log.txt',$logContent, FILE_APPEND | LOCK_EX);
                $ttt = time()-$device['updatetime'];

                //如果扫不到车，说明已经离场，立即结算，并且重置状态就可以
                if($plateResult['carColor'] == 0 && $plateResult['license'] == "" && $plateResult['type'] == 0 
                && $plateResult['platecolor'] == "" && $plateResult['CarType'] == 0 && $ttt > 240){ 
                    $record = [];
                    $record['shop_id']     = $shopId;
                    $record['user_id']     = $device['cur_userid'];
                    $record['fee']         = $device->fee;
                    $record['cur_feetype'] = $device->cur_feetype;
                    // 计算洗车时长（秒）cur_feetype
                    $duration_seconds = time() - $device->updatetime;
                    
                    //将秒数转换为 xx分xx秒 格式
                    if ($duration_seconds < 60) {
                        $record['washtime'] = $duration_seconds . '秒';
                    } else {
                        $minutes = floor($duration_seconds / 60);
                        $seconds = $duration_seconds % 60;
                        $record['washtime'] = $minutes . '分' . $seconds . '秒';
                    }
                    
                    //将时间戳转换为洗车时间：x年x月x日 时分秒
                    $begin_time = $device->updatetime;
                    $record['begintime']  = date('Y-m-d H:i:s', $begin_time);
                    $record['createtime'] = time();
                    
                    $WashLog = new WashLog();
                    $WashLog->save($record);    

                    $device ->status = 0;
                    $device ->cur_userid = 0;
                    $device ->cur_feetype = '0';
                    $device ->updatetime  = time();
                    $device ->fee         = 0;
                    $device ->save();

                    $result = $this->closeElectric(1);

                    // $this->sendCommandLed(1,1,"空闲中");
                }
                
            }
        }
        if(!empty($testjosn['KeepAlive'])){
            if($device && $device['status'] == '1'){ //说明还是洗车中，把数据存入数据库
                        // 返回响应给摄像头
                $arr = [
                    'Response' => [
                        'trigger_data' => [ // 模拟触发
                            'action' => 'on' // on 发出模拟触发信号
                        ],
                    ]
                ];
                
                $jsonobj = json_encode($arr, JSON_UNESCAPED_UNICODE);
                $jsonobj = stripslashes($jsonobj);
                $jsonobj1 = mb_convert_encoding($jsonobj, 'GBK', 'UTF-8');
                
                return $jsonobj1;
            }

        } 
    
    }
    
    //摄像头2对应接口
    public function sendCameraComand1()
    {
        $data = file_get_contents('php://input'); 
        $data_utf_8 = $this->yang_gbk2utf8($data);
        $testjosn = json_decode($data_utf_8, true);
        // $logContent = "[" . date('Y-m-d H:i:s') . "] " . json_encode($testjosn, JSON_UNESCAPED_UNICODE) . "\n";
        // file_put_contents(__DIR__ . '/camera_log1.txt',$logContent, FILE_APPEND | LOCK_EX);
        $ShopDevice = new ShopDevice();
        $device = $ShopDevice->where("shop_id", 9)->where("doorindex", 2)->find();
            
        if(!empty($testjosn['AlarmInfoPlate'])) {

            $CameraLog = new CameraLog();
            $alarmInfo = $testjosn['AlarmInfoPlate'];
            if(!empty($alarmInfo['result']) && !empty($alarmInfo['result']['PlateResult'])  && $device['status'] == '1' && $device['cartype'] == '1') {
                
                $plateResult = $alarmInfo['result']['PlateResult'];
            

                $shopId = 9; 
    
                //  $CameraLog->save([
                //         'shop_id' => 9,
                //         'name'     => '工位2',
                //         'img'      => isset($plateResult['imageFile']) ? $plateResult['imageFile'] : '',
                //         'carColor' => isset($plateResult['carColor']) ? $plateResult['carColor'] : 0,
                //         'license'  => isset($plateResult['license']) ? $plateResult['license'] : '',
                //         'cardType' => isset($plateResult['type']) ? $plateResult['type'] : 0,
                //         'platecolor' => isset($plateResult['platecolor']) ? $plateResult['platecolor'] : '',
                //         'cartype' => isset($plateResult['CarType']) ? $plateResult['CarType'] : 0,
                //         'createtime' => time(),
                //     ]);
                
                $ttt = time()-$device['updatetime'];
                
                //如果扫不到车，说明已经离场，立即结算，并且重置状态就可以
                if($plateResult['carColor'] == 0 && $plateResult['license'] == "" && $plateResult['type'] == 0 
                && $plateResult['platecolor'] == "" && $plateResult['CarType'] == 0 && $ttt > 240){ 
   
                    $record = [];
                    $record['shop_id']     = $shopId;
                    $record['user_id']     = $device['cur_userid'];
                    $record['fee']         = $device->fee;
                    $record['cur_feetype'] = $device->cur_feetype;
                    // 计算洗车时长（秒）cur_feetype
                    $duration_seconds = time() - $device->updatetime;
                    
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
                    $record['begintime']  = date('Y-m-d H:i:s', $begin_time);
                    $record['createtime'] = time();
                    
                    $WashLog = new WashLog();
                    $WashLog->save($record);    
                    
                    $device ->status        = 0;
                    $device ->cur_userid    = 0;
                    $device ->cur_feetype   = '0';
                    $device ->updatetime    = time();
                    $device ->fee           = 0;
                    $device ->save();
                    
                    //断水电
                    $result = $this->closeElectric(2);
                    // $this->sendCommandLed(2,1,"0分10秒");
                }
            }
        }
        if(!empty($testjosn['KeepAlive'])){
            if($device && $device['status'] == '1'){ //说明还是洗车中，把数据存入数据库
                        // 返回响应给摄像头
                $arr = [
                    'Response' => [
                        'trigger_data' => [ // 模拟触发
                            'action' => 'on' // on 发出模拟触发信号
                        ],
                    ]
                ];
                $jsonobj = json_encode($arr, JSON_UNESCAPED_UNICODE);
                $jsonobj = stripslashes($jsonobj);
                $jsonobj1 = mb_convert_encoding($jsonobj, 'GBK', 'UTF-8');
                
                return $jsonobj1;
            }
        } 
    }

    public function index()
    {
        $this->success('请求成功');
    }
    
    
    
    private function yang_gbk2utf8($str){ 
	    $charset = mb_detect_encoding($str,array('UTF-8','GBK','GB2312')); 
	    $charset = strtolower($charset); 
	    if('cp936' == $charset){ 
	        $charset='GBK'; 
	    } 
	    if('utf-8' != $charset){ 
	        $str = iconv($charset,'UTF-8//IGNORE',$str); 
	    } 
	    return $str; 
	}
	

}

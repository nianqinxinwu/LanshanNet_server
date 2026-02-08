<?php
namespace app\common\library;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use think\Log;

class Mqtt
{
    // 从配置文件读取MQTT服务器信息
    private $config;

    public function __construct()
    {
        $this->config = config('mqtt.server');
    }

    /**
     * 发送指令并等待响应（同步方式）
     * @param string $topic 主题
     * @param array $data 指令内容
     * @param int $qos QoS等级
     * @param int $timeout 等待响应超时时间（秒）
     * @return array 返回完整结果
     */
     // 在 Mqtt 类中添加
/**
 * 发送指令并设置响应回调
 */
public function sendWithCallback(string $topic, array $data, callable $callback, int $qos = 1, int $timeout = 10)
{
    try {
        $clientId = 'fastadmin_callback_' . uniqid();
        $client = new MqttClient(
            $this->config['host'],
            $this->config['port'],
            $clientId
        );
            $username1 = $this->config['username'];
            $password1 = $this->config['password'];
            if($topic == '/a1P2scMba3a/device/sub4'){
                $username1 = $this->config['username'].'3';
                $password1 = $this->config['password'].'3';
            }elseif($topic == '/a1P2scMba3a/device/sub3'){
                $username1 = $this->config['username'].'2';
                $password1 = $this->config['password'].'2';
            }elseif($topic == '/a1P2scMba3a/device/sub5'){
                $username1 = $this->config['username'].'4';
                $password1 = $this->config['password'].'4';
            }elseif($topic == '/a1P2scMba3a/device/sub6'){
                $username1 = $this->config['username'].'5';
                $password1 = $this->config['password'].'5';
            }
            

        $connectionSettings = (new ConnectionSettings)
            ->setUsername($username1)
            ->setPassword($password1)
            ->setKeepAliveInterval($this->config['keepalive'])
            ->setConnectTimeout($this->config['timeout']);

        $client->connect($connectionSettings, true);

        // 生成业务ID和响应主题
        $bizMessageId = uniqid('biz_', true);
        $responseTopic = "device/response/{$bizMessageId}";
        
        // 告诉设备响应的主题
        $data['biz_message_id'] = $bizMessageId;
        $data['response_topic'] = $responseTopic;
        
        $message = json_encode($data, JSON_UNESCAPED_UNICODE);
        
        // 订阅响应主题
        $client->subscribe($responseTopic, function ($topic, $msg) use ($client, $callback, $bizMessageId) {
            $response = json_decode($msg, true);
            if ($response && isset($response['biz_message_id']) && $response['biz_message_id'] === $bizMessageId) {
                call_user_func($callback, $response);
                $client->unsubscribe($topic);
                $client->disconnect();
            }
        }, 1);

        // 发送消息
        // var_dump('1111111111');
        // var_dump($message);exit();
        
        $client->publish($topic, $message, $qos);
        
        // Log::record("已发送指令并等待响应 - 业务ID: {$bizMessageId}", 'info');
        
        // 等待响应
        $startTime = time();
        while ((time() - $startTime) < $timeout) {
            $client->loop(true, 1);
            usleep(100000); // 100ms
        }
        
        $client->disconnect();
        return null;

    } catch (\Exception $e) {
        Log::record("发送失败: " . $e->getMessage(), 'error');
        return null;
    }
}
     
    public function sendAndWait(string $topic, array $data, int $qos = 1, int $timeout = 10)
    {
        try {
            // 生成唯一ID用于匹配响应
            $bizMessageId = uniqid('biz_', true);
            
            // 准备消息
            $data['biz_message_id'] = $bizMessageId;
            $message = json_encode($data, JSON_UNESCAPED_UNICODE);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("JSON编码失败: " . json_last_error_msg());
            }

            // 创建客户端并连接
            $clientId = 'fastadmin_sender_' . uniqid();
            $client = new MqttClient(
                $this->config['host'],
                $this->config['port'],
                $clientId
            );
            
            $username1 = $this->config['username'];
            $password1 = $this->config['password'];
            if($topic == '/a1P2scMba3a/device/sub4'){
                $username1 = $this->config['username'].'3';
                $password1 = $this->config['password'].'3';
            }elseif($topic == '/a1P2scMba3a/device/sub3'){
                $username1 = $this->config['username'].'2';
                $password1 = $this->config['password'].'2';
            }elseif($topic == '/a1P2scMba3a/device/sub5'){
                $username1 = $this->config['username'].'4';
                $password1 = $this->config['password'].'4';
            }elseif($topic == '/a1P2scMba3a/device/sub6'){
                $username1 = $this->config['username'].'5';
                $password1 = $this->config['password'].'5';
            }
            

            $connectionSettings = (new ConnectionSettings)
                ->setUsername($username1)
                ->setPassword($password1)
                ->setKeepAliveInterval($this->config['keepalive'])
                ->setConnectTimeout($this->config['timeout']);

            $client->connect($connectionSettings, true);

            // 订阅响应主题（设备需要响应到此主题）
            $responseTopic = "response/{$bizMessageId}";
            $responseData = null;
            
            $client->subscribe($responseTopic, function ($topic, $msg) use (&$responseData) {
                $responseData = json_decode($msg, true);
            }, 1);

            // 发布消息
            $mqttMessageId = $client->publish($topic, $message, $qos);
            Log::info("发送成功 - 主题: {$topic}, 业务ID: {$bizMessageId}");

            // 等待响应
            $startTime = time();
            while ($responseData === null && (time() - $startTime) < $timeout) {
                $client->loop(true, 1); // 每次处理1秒
                usleep(100000); // 100ms
            }

            $client->disconnect();

            // 返回完整结果
            $result = [
                'success' => true,
                'biz_message_id' => $bizMessageId,
                'mqtt_message_id' => $mqttMessageId,
                'topic' => $topic,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            if ($responseData !== null) {
                $result['response'] = $responseData;
                $result['message'] = '收到设备响应';
            } else {
                $result['message'] = '设备响应超时';
            }
            
            return $result;

        } catch (\Exception $e) {
            Log::error("发送失败: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'topic' => $topic,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * 发送指令（原方法，保持兼容）
     */
    public function sendCommand(string $topic, array $data, int $qos = 1)
    {
        try {
            $clientId = 'fastadmin_sender_' . uniqid() . '_' . getmypid();
            $client = new MqttClient(
                $this->config['host'],
                $this->config['port'],
                $clientId
            );

            $username1 = $this->config['username'];
            $password1 = $this->config['password'];
            
            // if($topic == '/a1P2scMba3a/device/sub4'){
            //     $username1 = $this->config['username'].'3';
            //     $password1 = $this->config['password'].'3';
            // }elseif($topic == '/a1P2scMba3a/device/sub3'){
            //     $username1 = $this->config['username'].'2';
            //     $password1 = $this->config['password'].'2';
            // }elseif($topic == '/a1P2scMba3a/device/sub5'){
            //     $username1 = $this->config['username'].'4';
            //     $password1 = $this->config['password'].'4';
            // }elseif($topic == '/a1P2scMba3a/device/sub6'){
            //     $username1 = $this->config['username'].'5';
            //     $password1 = $this->config['password'].'5';
            // }

            $connectionSettings = (new ConnectionSettings)
                ->setUsername($username1)
                ->setPassword($password1)
                ->setKeepAliveInterval($this->config['keepalive'])
                ->setConnectTimeout($this->config['timeout']);

            $client->connect($connectionSettings, true);

            $bizMessageId = uniqid('biz_', true); 
            $data['biz_message_id'] = $bizMessageId;

            $message = json_encode($data, JSON_UNESCAPED_UNICODE);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("JSON编码失败: " . json_last_error_msg());
            }

            $mqttMessageId = $client->publish($topic, $message, $qos);
            $client->disconnect();

            $finalMessageId = $mqttMessageId !== null ? $mqttMessageId : $bizMessageId;
            Log::info("发送成功 - 主题: {$topic}, 消息ID: {$finalMessageId}");
            
            return [
                'success' => true,
                'message_id' => $username1,
                'biz_message_id' => $bizMessageId,
                'topic' => $topic,
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            Log::error("发送失败: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'topic' => $topic
            ];
        }
    }


    public function sendCommandLed(string $topic, string $str, int $qos = 1)
    {
        try {
            $clientId = 'fastadmin_sender_' . uniqid() . '_' . getmypid();
            $client = new MqttClient(
                $this->config['host'],
                $this->config['port'],
                $clientId
            );

            $username1 = $this->config['username'];
            $password1 = $this->config['password'];
            if($topic == '/a1P2scMba3a/device/sub4'){
                $username1 = $this->config['username'].'3';
                $password1 = $this->config['password'].'3';
            }elseif($topic == '/a1P2scMba3a/device/sub3'){
                $username1 = $this->config['username'].'2';
                $password1 = $this->config['password'].'2';
            }elseif($topic == '/a1P2scMba3a/device/sub5'){
                $username1 = $this->config['username'].'4';
                $password1 = $this->config['password'].'4';
            }elseif($topic == '/a1P2scMba3a/device/sub6'){
                $username1 = $this->config['username'].'5';
                $password1 = $this->config['password'].'5';
            }

            $connectionSettings = (new ConnectionSettings)
                ->setUsername($username1)
                ->setPassword($password1)
                ->setKeepAliveInterval($this->config['keepalive'])
                ->setConnectTimeout($this->config['timeout']);

            $client->connect($connectionSettings, true);

            $message = $str;
          
            $mqttMessageId = $client->publish($topic, $message, $qos);
            $client->disconnect();

            $finalMessageId = $mqttMessageId !== null ? $mqttMessageId : $bizMessageId;
            Log::info("发送成功 - 主题: {$topic}, 消息ID: {$finalMessageId}");
            
            return [
                'success' => true,
                // 'message_id' => $message,
                // 'biz_message_id' => $bizMessageId,
                'topic' => $topic,
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            Log::error("发送失败: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'topic' => $topic
            ];
        }
    }
    /**
     * 发送设备控制指令（封装常用主题格式）
     */
    public function sendToDevice($deviceId, $command, $qos = 1)
    {
        $topic = "device/{$deviceId}/command";
        return $this->sendCommand($topic, $command, $qos);
    }
}
<?php
namespace app\common\service;

use think\Log;
use app\admin\model\Device;
use app\admin\model\DeviceCommandLog;

class MqttMessageService
{
    /**
     * 统一消息入口
     * @param string $topic 主题
     * @param array $data 解析后的消息数据
     * @return bool
     */
    public function handle(string $topic, array $data)
    {
        // 根据主题前缀分发处理
        if (strpos($topic, 'device/') === 0) {
            return $this->handleDeviceMessage($topic, $data);
        }
        
        Log::info("未处理的MQTT主题: {$topic}，数据: " . json_encode($data));
        return true;
    }

    /**
     * 处理设备相关消息
     */
    private function handleDeviceMessage(string $topic, array $data)
    {
        $parts = explode('/', $topic);
        $deviceSn = $parts[1] ?? '';
        $messageType = $parts[2] ?? '';

        if (empty($deviceSn)) {
            Log::error("无效的设备主题格式: {$topic}");
            return false;
        }

        // 验证设备是否存在
        $device = Device::where('sn', $deviceSn)->find();
        if (!$device) {
            Log::error("设备SN不存在: {$deviceSn}，主题: {$topic}");
            return false;
        }

        // 根据消息类型分发
        switch ($messageType) {
            case 'response':
                return $this->handleResponse($device, $data);
            case 'status':
                return $this->handleStatus($device, $data);
            case 'event':
                return $this->handleEvent($device, $data);
            default:
                Log::info("未处理的设备消息类型: {$messageType}，设备: {$deviceSn}");
                return true;
        }
    }

    /**
     * 处理设备指令响应
     */
    private function handleResponse(Device $device, array $data)
    {
        if (empty($data['command'])) {
            Log::error("设备响应缺少指令标识: " . json_encode($data));
            return false;
        }

        // 更新指令日志状态
        $log = DeviceCommandLog::where([
            'device_id' => $device['id'],
            'command' => $data['command'],
            'status' => 1 // 仅更新"已发送"状态的指令
        ])
        ->order('create_time', 'desc')
        ->find();

        if ($log) {
            $log->save([
                'response' => json_encode($data),
                'response_time' => time(),
                'status' => $data['success'] ? 2 : 3 // 2=成功，3=失败
            ]);
            Log::info("设备[{$device['sn']}]指令响应已更新: {$data['command']}");
        } else {
            Log::warning("未找到匹配的指令日志: 设备{$device['sn']}，指令{$data['command']}");
        }
        return true;
    }

    /**
     * 处理设备状态上报
     */
    private function handleStatus(Device $device, array $data)
    {
        $updateData = [
            'online' => $data['online'] ?? 0,
            'last_active_time' => time()
        ];
        // 补充可选字段
        if (isset($data['signal'])) $updateData['signal'] = $data['signal'];
        if (isset($data['battery'])) $updateData['battery'] = $data['battery'];
        
        $device->save($updateData);
        Log::info("设备[{$device['sn']}]状态已更新: " . json_encode($updateData));
        return true;
    }

    /**
     * 处理设备事件通知（如报警、故障）
     */
    private function handleEvent(Device $device, array $data)
    {
        // 示例：记录设备事件日志
        \app\admin\model\DeviceEventLog::create([
            'device_id' => $device['id'],
            'event_type' => $data['type'] ?? 'unknown',
            'event_content' => json_encode($data),
            'create_time' => time()
        ]);
        Log::info("设备[{$device['sn']}]事件记录: {$data['type']}");
        return true;
    }
}

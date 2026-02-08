<?php
namespace app\command;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\MqttClientException;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Log;
use app\common\service\MqttMessageService;

class MqttListen extends Command
{
    // 命令配置
    protected function configure()
    {
        $this->setName('mqtt:listen')
             ->setDescription('FastAdmin MQTT消息监听服务')
             ->addOption('daemon', 'd', null, '是否以守护进程模式运行（仅Linux有效）');
    }

    // 执行入口
    protected function execute(Input $input, Output $output)
    {
        $config = config('mqtt');
        $this->printInfo($output, "MQTT监听服务启动中... 配置: " . json_encode($config['server']));

        // 守护进程模式（Linux）
        if ($input->getOption('daemon') && PHP_OS !== 'WINNT') {
            $this->daemonize();
        }

        // 消息处理器实例
        $messageService = new MqttMessageService();

        // 主循环：持续连接和监听
        while (true) {
            try {
                // 创建客户端（每次重连生成新ClientID避免冲突）
                $clientId = 'fastadmin_' . uniqid() . '_' . getmypid();
                $client = new MqttClient(
                    $config['server']['host'],
                    $config['server']['port'],
                    $clientId
                );

                // 配置连接参数
                $connectionSettings = (new ConnectionSettings)
                    ->setUsername($config['server']['username'])
                    ->setPassword($config['server']['password'])
                    ->setKeepAliveInterval($config['server']['keepalive'])
                    ->setConnectTimeout($config['server']['timeout']);

                // 连接服务器
                $client->connect($connectionSettings, true);
                $this->printInfo($output, "已连接到MQTT服务器: {$config['server']['host']}:{$config['server']['port']}");

                // 订阅主题
                foreach ($config['topics'] as $topic => $qos) {
                    $client->subscribe($topic, function ($topic, $message) use ($output, $messageService) {
                        $this->onMessageReceived($topic, $message, $output, $messageService);
                    }, $qos); // 注意：第三个参数传入QoS等级
                }

                // 进入阻塞监听（直到连接断开）
                $client->loop(true);

                // 正常断开时清理
                $client->disconnect();
                $this->printInfo($output, "已主动断开MQTT连接");

            } catch (MqttClientException $e) {
                $error = "MQTT客户端错误: {$e->getMessage()}";
            } catch (\Exception $e) {
                $error = "系统错误: {$e->getMessage()}";
            }

            // 错误处理与重试
            if (isset($error)) {
                $this->printError($output, $error);
                Log::error($error);
                unset($error); // 重置错误状态
            }

            // 重试间隔
            $this->printInfo($output, "将在 {$config['listen']['retry_interval']} 秒后重试连接...");
            sleep($config['listen']['retry_interval']);
        }
    }

    /**
     * 消息接收处理
     */
    private function onMessageReceived(string $topic, string $message, Output $output, MqttMessageService $service)
    {
        $time = date('Y-m-d H:i:s');
        // $this->printInfo($output, "[$time] 收到消息 - 主题: {$topic}, 内容: {$message}");


        // $data = json_decode($input, true);
        $logContent = "[" . date('Y-m-d H:i:s') . "] " . '主题:'.$topic .'内容：'.$message . "\n";

        file_put_contents(__DIR__ . '/mqtt_logs.txt',$logContent, FILE_APPEND | LOCK_EX);


        // 解析JSON消息
        $data = json_decode($message, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = "消息JSON解析失败: {$message}，错误: " . json_last_error_msg();
            $this->printError($output, $error);
            Log::error($error);
            return;
        }

        // 交给服务类处理业务
        $service->handle($topic, $data);
    }

    /**
     * 转为守护进程（Linux）
     */
    private function daemonize()
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            throw new \RuntimeException('创建子进程失败');
        } elseif ($pid > 0) {
            exit(0); // 父进程退出
        }

        // 子进程成为会话组长
        posix_setsid();

        // 再次fork避免进程拥有控制终端
        $pid = pcntl_fork();
        if ($pid == -1) {
            throw new \RuntimeException('第二次创建子进程失败');
        } elseif ($pid > 0) {
            exit(0);
        }

        // 重定向标准输入输出
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);
    }

    /**
     * 控制台信息输出
     */
    private function printInfo(Output $output, string $msg)
    {
        $output->writeln("[" . date('Y-m-d H:i:s') . "] " . $msg);
    }

    /**
     * 控制台错误输出
     */
    private function printError(Output $output, string $msg)
    {
        $output->error("[" . date('Y-m-d H:i:s') . "] " . $msg);
    }
}

<?php
namespace app\common\service;

use think\facade\Cache;
use think\facade\Log;

class DouyinService
{
    private $clientKey;
    private $clientSecret;
    private $baseUrl;

    public function __construct()
    {
        $config = config('douyin');
        $this->clientKey = $config['client_key'];
        $this->clientSecret = $config['client_secret'];
        $this->baseUrl = $config['base_url'];
    }

    /**
     * 获取Access Token（务必缓存，每日有次数限制）
     */
    public function getAccessToken()
    {
        $cacheKey = 'douyin_access_token_' . $this->clientKey;
        $token = Cache::get($cacheKey);
        if ($token) {
            return $token;
        }

        $url = $this->baseUrl . '/oauth/access_token/';
        $params = [
            'client_key'    => $this->clientKey,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'client_credential',
        ];

        $result = $this->httpRequest($url, $params, 'POST');
        $result = json_decode($result, true);

        if (empty($result['data']['access_token'])) {
            Log::error('抖音AccessToken获取失败：' . json_encode($result));
            throw new \Exception('获取抖音Token失败');
        }

        $token = $result['data']['access_token'];
        $expiresIn = $result['data']['expires_in'] ?? 7200; // 默认2小时
        // 提前过期，比如设置缓存为7000秒
        Cache::set($cacheKey, $token, $expiresIn - 200);

        return $token;
    }

    /**
     * 核销抖音券码
     * @param string $code 用户出示的券码（加密字符串）
     * @param string $poiId 门店ID，从配置或参数传入
     * @return array
     */
    public function verifyCoupon($code, $poiId)
    {
        $token = $this->getAccessToken();
        $url = $this->baseUrl . '/goodlife/v1/fulfilment/certificate/verify/';
        
        $headers = [
            'Content-Type: application/json',
            'access-token: ' . $token,
        ];
        
        $data = [
            'encrypted_code' => $code, // 注意：这里是用户出示的加密code
            'poi_id'         => $poiId,
        ];
        
        $result = $this->httpRequest($url, json_encode($data), 'POST', $headers);
        $result = json_decode($result, true);
        
        // 记录日志，便于排查
        Log::info('抖音核销请求：' . json_encode($data) . '，响应：' . json_encode($result));
        
        return $result;
    }
    
    /**
     * 查询券码详情（可选，用于核销前校验）
     */
    public function getCouponDetail($code)
    {
        $token = $this->getAccessToken();
        $url = $this->baseUrl . '/goodlife/v1/fulfilment/certificate/get/';
        
        $headers = [
            'Content-Type: application/json',
            'access-token: ' . $token,
        ];
        
        $data = ['encrypted_code' => $code];
        $result = $this->httpRequest($url, json_encode($data), 'POST', $headers);
        return json_decode($result, true);
    }

    /**
     * 通用HTTP请求方法
     */
    private function httpRequest($url, $data = null, $method = 'GET', $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            Log::error('抖音接口请求CURL错误：' . curl_error($ch));
            curl_close($ch);
            throw new \Exception('网络请求失败');
        }
        curl_close($ch);
        
        if ($httpCode != 200) {
            Log::error('抖音接口HTTP状态码异常：' . $httpCode . '，响应：' . $response);
        }
        
        return $response;
    }
}
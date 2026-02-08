<?php
namespace app\common\library;

class Douyin
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

    private function getToken()
    {
        // 在当前目录缓存
        $cacheFile = __DIR__ . '/douyin_token.cache';
        var_dump('1111111113333333333333347777777');
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data && time() - $data['time'] < 6600) {
                return $data['token'];
            }
        }
        
        // 获取新token
        $url = $this->baseUrl . '/oauth/access_token/';
        // $postData = "client_key={$this->clientKey}&client_secret={$this->clientSecret}&grant_type=client_credential";
       
        $postData = json_encode([
            'client_key' => $this->clientKey,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credential',
                             
        ]);
       
        var_dump('111888888888888====='.$postData);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        var_dump('111111100000000000033333333345');
        $result = json_decode($response, true);
        var_dump($result);
        if (empty($result['data']['access_token'])) {
            var_dump('0000000000000001');
            throw new \Exception('获取token失败');
        }
        
        $token = $result['data']['access_token'];
        var_dump('000000000000000');
        var_dump($token);
        file_put_contents($cacheFile, json_encode([
            'token' => $token,
            'time' => time()
        ]));
        
        return $token;
    }

    public function verifyCoupon($code, $poiId)
    {
        $token = $this->getToken();
        
        $url = $this->baseUrl . '/goodlife/v1/fulfilment/certificate/verify/';
        
        $data = json_encode([
            'encrypted_code' => $code,
            'poi_id' => $poiId,
        ]);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'access-token: ' . $token,
            ],
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }

    
    /**
     * 查询券码详情
     */
    public function getCouponDetail($code)
    {
        $url = $this->baseUrl . '/goodlife/v1/fulfilment/certificate/get/';
        
        $data = [
            'encrypted_code' => $code,
            'client_key' => $this->clientKey,
            'client_secret' => $this->clientSecret,
        ];
        
        $result = $this->httpRequest($url, json_encode($data), 'POST');
        return json_decode($result, true);
    }
    
    /**
     * 简单HTTP请求
     */
    private function httpRequest($url, $data = null, $method = 'POST', $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $defaultHeaders = [
            'Content-Type: application/json',
        ];
        
        $finalHeaders = array_merge($defaultHeaders, $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $finalHeaders);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('请求失败: ' . $error);
        }
        
        curl_close($ch);
        return $response;
    }

}
<?php


namespace app\common\controller\xiluxc;


use app\common\controller\Api;

class XiluxcApi extends Api {

    protected $noNeedRight = ['*'];
    protected $cityid = null;
    protected $platform = 'wxmini';

    const DEFAULT_CITY_ID = 104;

    /**
     * 初始化操作
     * @access protected
     */
    protected function _initialize()
    {
        parent::_initialize();
        $this->cityid = $this->request->header('cityid') === null?self::DEFAULT_CITY_ID : $this->request->header('cityid');
        $this->platform = $this->request->header('platform') === null?'wxmini' : $this->request->header('platform');
    }

    /**
     * 将完整URL转为相对路径存储（去掉域名部分）
     */
    protected function normalizeUrl($url)
    {
        if (!$url) return $url;
        if (preg_match('#^https?://#i', $url)) {
            $parsed = parse_url($url);
            return isset($parsed['path']) ? $parsed['path'] : $url;
        }
        return $url;
    }

    /**
     * 将JSON数组中的URL都转为相对路径
     */
    protected function normalizeUrlArray($jsonStr)
    {
        if (!$jsonStr) return $jsonStr;
        $arr = json_decode($jsonStr, true);
        if (!is_array($arr)) return $jsonStr;
        $arr = array_map([$this, 'normalizeUrl'], $arr);
        return json_encode($arr);
    }

    /**
     * 格式化金额，保留两位小数
     */
    protected function formatMoney($amount)
    {
        return number_format(floatval($amount), 2, '.', '');
    }

}
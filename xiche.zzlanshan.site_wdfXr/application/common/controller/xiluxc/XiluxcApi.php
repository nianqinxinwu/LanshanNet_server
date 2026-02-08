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



}
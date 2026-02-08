<?php

namespace app\admin\controller\xiluxc\car;

use app\common\controller\Backend;
use app\common\model\xiluxc\current\Config;
use fast\Http;

/**
 * 汽车品牌
 *
 * @icon fa fa-circle-o
 */
class CarBrand extends Backend
{
    protected $noNeedRight = ['third_brands'];
    /**
     * CarBrand模型对象
     * @var \app\common\model\xiluxc\car\CarBrand
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\xiluxc\car\CarBrand;
        $this->view->assign("statusList", $this->model->getStatusList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 车辆品牌同步
     */
    public function third_brands(){
        // 基本参数配置
        $shopinfo = Config::getMyConfig('shopinfo');
        if(empty($shopinfo['juhe_key'])){
            $this->error("聚合key未填写");
        }
        $apiUrl = "http://apis.juhe.cn/cxdq/brand"; // 接口请求URL
        $headers = ["Content-Type: application/x-www-form-urlencoded"]; // 接口请求header
        $apiKey = $shopinfo['juhe_key']; // 在个人中心->我的数据,接口名称上方查看
        // 接口请求入参配置
        $requestParams = [
            'key' => $apiKey,
            'first_letter' => '',
        ];
        //$requestParamsStr = http_build_query($requestParams);
       $json = Http::get($apiUrl,$requestParams,$headers);
       $result = json_decode($json,true);
       $datas = [];
       $exists  = $this->model->column('name,id');
       foreach ($result['result']  as  $vo){
           $item = [
               'name'           =>  $vo['brand_name'],
               'image'          =>  $vo['brand_logo'],
               'first_letter'   =>  $vo['first_letter'],
               'brand_id'       =>  $vo['id'],
               'weigh'          =>  0,
           ];
           if(isset($exists[$vo['brand_name']])){
               $item['id'] = $exists[$vo['brand_name']];
           }
           $datas[] = $item;
       }
       $this->model->saveAll($datas);
       $this->success('');
    }

}

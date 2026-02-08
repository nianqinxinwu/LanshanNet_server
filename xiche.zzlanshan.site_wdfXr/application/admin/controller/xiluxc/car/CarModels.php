<?php

namespace app\admin\controller\xiluxc\car;

use app\common\controller\Backend;
use app\common\model\xiluxc\current\Config;
use fast\Http;

/**
 * 汽车型号
 *
 * @icon fa fa-circle-o
 */
class CarModels extends Backend
{
    protected $noNeedRight = ['third_models'];
    /**
     * CarModels模型对象
     * @var \app\common\model\xiluxc\car\CarModels
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\xiluxc\car\CarModels;
        $this->view->assign("statusList", $this->model->getStatusList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */





    /**
     * 车辆型号同步
     */
    public function third_models(){
        $seriesId = $this->request->param('series_id');
        $series = $seriesId?\app\common\model\xiluxc\car\CarSeries::where("id",$seriesId)->find() : null;
        if(!$series || !$series->series_id){
            $this->error("车系ID必传");
        }
        $shopinfo = Config::getMyConfig('shopinfo');
        if(empty($shopinfo['juhe_key'])){
            $this->error("聚合key未填写");
        }
        // 基本参数配置
        $apiUrl = "http://apis.juhe.cn/cxdq/models"; // 接口请求URL
        $headers = ["Content-Type: application/x-www-form-urlencoded"]; // 接口请求header
        $apiKey = $shopinfo['juhe_key']; // 在个人中心->我的数据,接口名称上方查看
        // 接口请求入参配置
        $requestParams = [
            'key' => $apiKey,
            'series_id'=> $series->series_id,
        ];
        $json = Http::get($apiUrl,$requestParams,$headers);
        $result = json_decode($json,true);
        if($result['error_code'] !== 0){
            $this->error('同步失败');
        }
        if(!$result['result']){
            $this->error('车系没有型号');
        }
        $datas = [];
        $exists  = $this->model->where('series_id',$series->id)->column('name,id');
        foreach ($result['result']  as  $vo){
            $item = [
                'series_id'     =>  $series->id,
                'name'          =>  $vo['name'],
                'year'          =>  $vo['year'],
                'peizhi'        =>  $vo['peizhi'],
                'models_id'     =>  $vo['id'],
                'weigh'         =>  0,
            ];
            if(isset($exists[$vo['name']])){
                $item['id'] = $exists[$vo['name']];
            }
            $datas[] = $item;
        }
        $this->model->saveAll($datas);
        $this->success('');
    }


}

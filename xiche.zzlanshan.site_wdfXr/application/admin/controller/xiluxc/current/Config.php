<?php

namespace app\admin\controller\xiluxc\current;

use app\common\controller\Backend;
use app\common\model\xiluxc\current\Config AS ConfigModel;
use think\Exception;

/**
 * 系统配置
 *
 * @icon fa fa-circle-o
 */
class Config extends Backend
{

    /**
     * Config模型对象
     * @var \app\common\model\xiluxc\current\Config
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new ConfigModel;
        $this->view->assign('distributionModuleList',['1'=>'全局比例','2'=>'单品设置']);

    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index()
    {
        return $this->view->fetch();
    }

    public function config($name,$group,$title){
        if ($this->request->isPost()) {
            $data = $this->request->post("row/a");
            $data = $data ? json_encode($data) : json_encode([]);
            if ($data) {
                try {
                    $config = $this->model->get(['name' => $name]);
                    if(!$config) {
                        $this->model->allowField(true)->save([
                            'name' => $name,
                            'title' => $title,
                            'group' => $group,
                            'type' => 'array',
                            'value' => $data,
                        ]);
                    }else {
                        $config->value = $data;
                        $config->save();
                    }
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                }

//                try {
//                    ConfigModel::refreshFile();
//                } catch (Exception $e) {
//                    $this->error($e->getMessage());
//                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $config = $this->model->where(['name' => $name])->value('value');
        $config = json_decode($config, true);
        $this->view->assign('row',$config);
        $this->view->assign('name',$name);
        return $this->view->fetch();
    }

    public function add(){
        return;
    }

    public function edit($ids=NULL){
        return;
    }

    public function del($ids=NULL){
        return;
    }
    public function multi($ids = null)
    {
        return;
    }

}

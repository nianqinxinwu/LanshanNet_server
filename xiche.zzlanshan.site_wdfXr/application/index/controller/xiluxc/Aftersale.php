<?php

namespace app\index\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcFront;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Hook;
use think\response\Json;

/**
 * 售后退款
 *
 * @icon fa fa-circle-o
 */
class Aftersale extends XiluxcFront
{
    protected $relationSearch = true;
    /**
     * Aftersale模型对象
     * @var \app\common\model\xiluxc\order\Aftersale
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\xiluxc\order\Aftersale;
        $this->view->assign("aftersaleTypeList", $this->model->getAftersaleTypeList());
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        $where2 = [];
        if($this->brand){
            $where2['shop.brand_id'] = $this->brand->user_id;
        }else{
            $where2['shop.id'] = $this->shop->id;
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $list = $this->model
            ->with(['user'=>function($q){
                $q->withField(['id','nickname']);
            },'shop'=>function($q){
                $q->withField(['id','name']);
            },'user_package'=>function($q){
                $q->withField(['id','package_name']);
            }])
            ->where('aftersale_type','package')
            ->where($where)
            ->where($where2)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    public function edit($ids = null)
    {
        $aftersale = $this->model->get($ids);
        if (!$aftersale) {
            $this->error(__('No Results were found'));
        }
        $row = $aftersale->user_package;
        if(!$row){
            $this->error(__('No Results were found'));
        }

        if (false === $this->request->isPost()) {
            $this->view->assign('aftersale', $aftersale);
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
    }

    public function add(){
        return;
    }

    public function multi($ids=null){
        return;
    }

}

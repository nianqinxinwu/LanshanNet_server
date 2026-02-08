<?php


namespace app\api\controller\xiluxc;


use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\xiluxc\user\UserCar AS UserCarModel;
use function fast\array_get;

class UserCar extends XiluxcApi
{

    public function mycar(){
        $userCar = new UserCarModel();
        $car = $userCar->with(['brand'=>function($q){
            $q->withField(["id","name",'image']);
        },
            'series'=>function($q){
                $q->withField(["id","name"]);
            }
        ])
            ->where($userCar->getTable().".user_id",$this->auth->id)
            ->where("is_default",1)
            ->find();
        $car && $car->brand && $car->brand->append(['image_text']);
        $this->success('',$car);
    }

    /**
     * 列表
     */
    public function lists(){
        $lists = UserCarModel::field("id,car_no,brand_id,series_id,models_id,register_time,car_vin,engine_number,car_belongs_to,use_nature,is_default")
            ->with(['brand','series'])
            ->where('user_id',$this->auth->id)
            ->order('user_car.updatetime','desc')
            ->select();
        foreach ($lists as $list){
            $list->relationQuery(['models']);
        }
        $this->success('',$lists);
    }

    /**
     * 新增-编辑
     */
    public function set_car(){
        $user_id = $this->auth->id;
        $params = $this->request->post('');

        $validator = new \app\common\validate\xiluxc\UserCar();
        if(!$validator->scene('add')->check($params)) {
            $this->error($validator->getError());
        }
        $model = (new UserCarModel());
        if($id = array_get($params,'id')){
            $row = $model->get($id);
            if($row->user_id != $user_id){
                $this->error('没有权限操作数据');
            }
            $result = $row->allowField(true)->save($params);
            $data = $row;
        }else{
            $params['user_id'] = $user_id;
            $result = $model->allowField(true)->save($params);
            $data = $model;
        }
        $tip = isset($params['id'])?'保存':'添加';
        if(!$result){
            $this->error($tip.'失败');
        }
        $this->success($tip.'成功',$data);


    }

    /**
     * 删除
     */
    public function del(){
        $user_id = $this->auth->id;
        $params = $this->request->post('');
        $model = (new UserCarModel());
        if($user_car_id = array_get($params,'user_car_id')){
            $row = $model->get($user_car_id);
            if(!$row){
                $this->error('爱车不存在');
            }
            if($row->user_id != $user_id) {
                $this->error('没有权限操作数据');
            }
            $result = $row->delete();
        }else{
            $this->error('参数错误');
        }
        $this->success('删除成功');
    }

    /**
     * 删除
     */
    public function set_default(){
        $user_id = $this->auth->id;
        $params = $this->request->post('');
        $model = (new UserCarModel());
        if($user_car_id = array_get($params,'user_car_id')){
            $row = $model->get($user_car_id);
            if(!$row){
                $this->error('爱车不存在');
            }
            if($row->user_id != $user_id) {
                $this->error('没有权限操作数据');
            }
            if($row->is_default == 1){
                $this->error('当前车辆无须切换');
            }
            $model->where("user_id",$user_id)->where("is_default",1)->update(['is_default'=>0]);
            $result = $row->save(['is_default'=>1]);
        }else{
            $this->error('参数错误');
        }
        $this->success('切换成功');
    }

}
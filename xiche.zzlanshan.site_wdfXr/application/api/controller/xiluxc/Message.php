<?php
namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;

use app\common\model\xiluxc\message\Notice;
use app\common\model\xiluxc\message\UserNotice;
use app\common\model\xiluxc\UserMessage;
use think\Db;
use think\db\Expression;
use think\Exception;
use think\exception\PDOException;
use function fast\array_get;

class Message extends XiluxcApi {

    /**
     * 未读消息数
     */
    public function unread(){
        $userId = $this->auth->id;
        //个人未读消息
        $count = UserMessage::where('user_id', $userId)->where('read',0)->count(1);
        //公告未读
        $notice =  new Notice;
        $currentName = $notice->getTable();
        $noticeCount = Notice::whereNotExists(function ($query) use($currentName,$userId){
            $userNotice = (new UserNotice())->getQuery()->getTable();
            $query->table($userNotice)->where($currentName . '.id=' . $userNotice . '.notice_id')->where("user_id",$userId);
            return $query;
        })->where('status','normal')->count();
        $this->success('',$count+$noticeCount);
    }

    /**
     * @ApiTitle (消息概况)
     * @ApiSummary (消息总的统计情况)
     * @ApiMethod (POST)
     * @ApiRoute (/api/xiluxc.message/summary)
     * @ApiHeaders (name=token, type=string, require=true, description="Token")
     */
    public function summary() {
        $userId = $this->auth->id;
        #平台公告
        $notice =  new Notice;
        $currentName = $notice->getTable();
        $noticeCount = Notice::whereNotExists(function ($query) use($currentName,$userId){
            $userNotice = (new UserNotice())->getQuery()->getTable();
            $query->table($userNotice)->where($currentName . '.id=' . $userNotice . '.notice_id')->where("user_id",$userId);
            return $query;
        })->where('status','normal')->count();
        $lastNotice = Notice::where('status','normal')->order("id","desc")->find();
        #服务消息
        $serviceCount = UserMessage::whereIn("type",[
            UserMessage::TYPE_SERVICE_ORDER,
            UserMessage::TYPE_SERVICE_APPOINTMENT_SUCCESS
        ])->where('user_id',$userId)->where('read',0)->count();
        $lastService = UserMessage::whereIn("type",[
            UserMessage::TYPE_SERVICE_ORDER,
            UserMessage::TYPE_SERVICE_APPOINTMENT_SUCCESS
        ])->where('user_id',$userId)->order('id',"desc")->find();
        #订单消息
        $orderCount = UserMessage::whereIn("type",[
            UserMessage::TYPE_PACKAGE_ORDER,
            UserMessage::TYPE_VIP_ORDER,
            UserMessage::TYPE_RECHARGE,
            UserMessage::TYPE_SERVICE_VERIFIER_SUCCESS,
            UserMessage::TYPE_PACKAGE_VERIFIER_SUCCESS,
        ])->where('user_id',$userId)->where('read',0)->count();
        $lastOrder = UserMessage::whereIn("type",[
            UserMessage::TYPE_PACKAGE_ORDER,
            UserMessage::TYPE_VIP_ORDER,
            UserMessage::TYPE_RECHARGE,
            UserMessage::TYPE_SERVICE_VERIFIER_SUCCESS,
            UserMessage::TYPE_PACKAGE_VERIFIER_SUCCESS,
        ])->where('user_id',$userId)->order('id','desc')->find();
        $data = [
            'notice'=>['total'=>$noticeCount,'message'=>$lastNotice],
            'service'=>['total'=>$serviceCount,'message'=>$lastService],
            'order'=>['total'=>$orderCount,'message'=>$lastOrder],
        ];
        $this->success('查询成功',$data);
    }

    /**
     * @ApiTitle (平台公告)
     * @ApiSummary (平台公告)
     * @ApiMethod (POST)
     * @ApiRoute (/api/xiluxc.message/notice_list)
     * @ApiHeaders (name=token, type=string, require=true, description="Token")
     */
    public function notice_list() {
        $userId = $this->auth->id;
        $list = Notice::where('status', 'normal')
            ->order('id','desc')
            ->paginate(10)
            ->each(function ($row) use($userId){
                $row->read = UserNotice::where("user_id",$userId)->where('notice_id',$row->id)->count()?1:0;
            });
        $this->success('查询成功', $list);
    }

    public function notice_detail(){
        $id = $this->request->param('id');
        $row = $id ? Notice::where('status', 'normal')
            ->where('id',$id)
            ->find()  : null;
        $userId = $this->auth->id;
        //浏览
        try {
            UserNotice::viewNotice($userId,$id);
        }catch (Exception $e){

        }
        $this->success('查询成功', $row);
    }

    /**
     * @ApiTitle (个人消息)
     * @ApiSummary (个人消息)
     * @ApiMethod (POST)
     * @ApiRoute (/api/xiluxc.message/personal_list)
     * @ApiHeaders (name=token, type=string, require=true, description="Token")
     */
    public function personal_list() {
        $params = $this->request->param('');
        $type = array_get($params,'type');
        if($type == 'service'){
            $where['type'] = ["in",[
                UserMessage::TYPE_SERVICE_ORDER,
                UserMessage::TYPE_SERVICE_APPOINTMENT_SUCCESS
            ]];
        }else{
            $where['type'] = ["in",[
                UserMessage::TYPE_PACKAGE_ORDER,
                UserMessage::TYPE_VIP_ORDER,
                UserMessage::TYPE_RECHARGE,
                UserMessage::TYPE_SERVICE_VERIFIER_SUCCESS,
                UserMessage::TYPE_PACKAGE_VERIFIER_SUCCESS,
            ]];
        }
        $list = UserMessage::where('user_id',$this->auth->id)
            ->where($where)
            ->order('id','desc')
            ->paginate(20);

        $this->success('查询成功', $list);
    }

    /**
     * 点击变成已读
     */
    public function set_read(){
        $id = $this->request->post('message_id');
        $message = $id?UserMessage::get(['id'=>$id,'user_id'=>$this->auth->id]):null;
        if(!$message){
            $this->error("消息未找到");
        }
        if($message->read == 0){
            try {
                $message->read = 1;
                $message->read_time = time();
                $message->save();
            }catch (Exception|PDOException $e){
                $this->error($e->getMessage());
            }
        }
        $this->success('已读成功');
    }


}
<?php

namespace app\common\model\xiluxc\message;

use think\Db;
use think\Exception;
use think\Log;
use think\Model;


class UserNotice extends Model
{

    

    

    // 表名
    protected $name = 'xiluxc_user_notice';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
    ];

    /**
     * 公告查看记录
     * @param int $userId
     * @param int $notice_id
     * @return
     */
    public static function viewNotice($userId, $notice_id) {
        if(!$userId || !$notice_id) {
            throw new Exception(__('invalid arguments'));
        }
        Db::startTrans();
        try {
            $record = static::where('user_id',$userId)->where('notice_id', $notice_id)->find();
            if(!$record) {
                $data = ['user_id'=>$userId,'notice_id'=>$notice_id,'num'=>1];
                $record = new static();
                $record->save($data);

                //更新阅读人数
                $count = self::where('notice_id',$record->notice_id)->count(1);
                Notice::where("id",$notice_id)->update(['view_num'=>$count]);
            }else {
                $data['num'] = Db::raw('num+1');
                $record->save($data);
            }
            Db::commit();
            return true;
        }catch (Exception $e) {
            Db::rollback();
            Log::error($e->getMessage()."\n".$e->getTraceAsString());
            throw $e;
        }
    }



}

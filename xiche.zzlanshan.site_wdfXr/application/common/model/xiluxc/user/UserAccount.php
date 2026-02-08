<?php

namespace app\common\model\xiluxc\user;


use app\common\model\User;
use think\Model;

class UserAccount extends Model{

    protected $name = 'xiluxc_user_account';

    protected $append = [

    ];

    /**
     * 用户账号
     * @param $user_id
     * @param int $puserId
     * @return array|bool|false|\PDOStatement|string|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function addAccount($user_id,$puserId=0){
        $account = static::field("config",true)->where('user_id',$user_id)->find();
        if(!$account){
            $secondUserId = $puserId ? self::where('user_id',$puserId)->value('first_user_id') :0;
            if(in_array($user_id,[$puserId,$secondUserId])){
                //如果等于我，则不绑定关系
                $secondUserId = 0;
                $puserId = 0;
            }
            $account = static::create([
                'user_id'           =>  $user_id,
                'first_user_id'     =>  $puserId,
                'second_user_id'    =>  $secondUserId,
                'bindtime'          =>  $puserId ? time() : null
            ]);
            $account = self::where('id',$account->id)->find();
        }
        return $account;
    }


    public function user()
    {
        return $this->belongsTo(User::class,'user_id','id',[],'inner')->setEagerlyType(0);
    }


}
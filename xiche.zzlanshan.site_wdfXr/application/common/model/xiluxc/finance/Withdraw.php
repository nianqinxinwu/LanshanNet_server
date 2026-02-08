<?php

namespace app\common\model\xiluxc\finance;

use app\common\model\User;
use app\common\model\xiluxc\current\Config;
use app\common\model\xiluxc\user\UserAccount;
use think\db\Expression;
use think\Exception;
use think\exception\PDOException;
use think\Hook;
use think\Model;
use function fast\array_get;


class Withdraw extends Model
{

    // 表名
    protected $name = 'xiluxc_withdraw';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'state_text',
        'createtime_text',
        'checktime_text',
    ];



    public function getStateList()
    {
        return ['1' => __('State 1'), '2' => __('State 2'), '3' => __('State 3'), '4' => __('State 4')];
    }


    public function getStateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['state']) ? $data['state'] : '');
        $list = $this->getStateList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getCreatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['createtime']) ? $data['createtime'] : '');
        return is_numeric($value) ? date("Y.m.d H:i", $value) : $value;
    }

    public function getChecktimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['checktime']) ? $data['checktime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','id',[],'left')->setEagerlyType(0);
    }


    /**
     * 获取提现次数
     * @param $where
     */
    public static function withdrawCount($where){
        return static::where($where)->count();
    }

    /**
     * @param array $params
     */
    public static function withdraw($params,$user){
        $config = Config::getMyConfig("apply");
        if(!$config || $config['apply_status'] != '1'){
            throw new Exception("提现未开启或未配置");
        }
        $money = array_get($params,'money');
        if(!$money || $money<0){
            throw new Exception("提现金额错误");
        }
        if($config['apply_small_money'] > $money){
            throw new Exception("低于最低提现金额");
        }
        if($config['apply_large_money'] < $money){
            throw new Exception("高于最高提现金额");
        }
        $withdraw_count = static::withdrawCount(['createtime'=>['between',[strtotime(date('Y-m-d')), strtotime(date('Y-m-d 23:59:59'))]]]);
        if($config['apply_daily_num'] <=$withdraw_count){
            throw new Exception("达到每天提现次数上限");
        }
        #判断用户余额是否充足
        $account = UserAccount::get(['user_id'=>$user->id]);
        if(!$account || $account->money<$money){
            throw new Exception("余额不足");
        }
        #添加提现记录
        //$rate = $config['apply_rate']/1000 ?? 0; //手续费率
        $rate = 0; //不需要手续费
        $rate_money = bcmul($rate,$money,2);
        $real_money = bcsub($money, $rate_money,2);
        $withdraw = [
            'order_no'  =>  'W'.date('YmdHis').mt_rand(100,9999),
            'user_id'   =>  $user->id,
            'money'     =>  $money,
            'real_money'=>  $real_money,
            'rate'      =>  $rate,
            'rate_money'=>  $rate_money,
            'state'     =>  '1'
        ];
        static::startTrans();
        try {
            $result = self::create($withdraw);
            Hook::listen("xiluxc_withdraw",$result);
        }catch (Exception|PDOException $e){
            static::rollback();
            throw $e;
        }
        static::commit();
        return $result;
    }


}

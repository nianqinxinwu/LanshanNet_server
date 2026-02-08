<?php

namespace app\common\model\xiluxc\brand;

use app\common\model\User;
use think\Model;


class BrandUser extends Model
{

    // 表名
    protected $name = 'xiluxc_user';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    const GROUP_SHOP = 1; //普通店
    const GROUP_BRAND_ACCOUNT = 2; //品牌账号
    const GROUP_BRAND_SON = 3; //品牌子账号

    
    public function getGroupTypeList()
    {
        return ['1' => __('Group_type 1'), '2' => __('Group_type 2'), '3' => __('Group_type 3')];
    }

    /**
     * 品牌账号
     */
    public function scopeBrandUser($query){
        $query->where("group_type",self::GROUP_BRAND_ACCOUNT);
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','id',[],'left')->setEagerlyType(0);
    }

    public function brand(){
        return $this->belongsTo(ShopBrand::class,'user_id','user_id',[],'inner')->setEagerlyType(0);
    }


    /**
     * 创建角色信息
     * @param $groupType
     * @param $user
     */
    public static function saveInfo($groupType,$user){
        if(!$user){
            return false;
        }
        $row = static::where('user_id',$user->id)->find();
        if(!$row){
            $row = static::create([
                'group_type'    => $groupType,
                'user_id'       => $user->id,
                'createtime'    => time(),
                'updatetime'    => time()
            ]);
            $row = self::where('id',$row->id)->find();
        }
        return $row;
    }

}

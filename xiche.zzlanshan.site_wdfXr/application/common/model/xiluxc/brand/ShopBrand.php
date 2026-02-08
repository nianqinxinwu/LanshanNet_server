<?php


namespace app\common\model\xiluxc\brand;


use think\Model;

class ShopBrand extends Model
{
    // 表名
    protected $name = 'xiluxc_shop_brand';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    protected $append = [

    ];

    public function getLogoAttr($value,$data){
        return !empty($data['logo']) ? cdnurl($data['logo'],true) : '';
    }

    /**
     * 创建角色信息
     * @param $groupType
     * @param $user
     */
    public static function saveInfo($user,$params){
        if(!$user){
            return false;
        }
        $row = static::where('user_id',$user->id)->find();
        $data = array_merge([
            'user_id'       =>  $user->id,
        ],$params);
        if(!$row){
            $row = static::create($data);
            $row = self::where('id',$row->id)->find();
        }else{
            $row->save($data);
        }
        return $row;
    }

}
<?php

namespace app\common\model\xiluxc\current;

use think\Model;
use traits\model\SoftDelete;

class Area extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'xiluxc_area';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [

    ];
    

    
    public function getLevelList()
    {
        return ['1' => __('Level 1'), '2' => __('Level 2'), '3' => __('Level 3'), '4' => __('Level 4')];
    }

    public function getIsReList()
    {
        return ['1' => __('Is_re 1'), '2' => __('Is_re 2')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden')];
    }

    public function scopeNormal($query){
        return $query->where("status",'normal');
    }

    public function children(){
        return $this->hasMany(self::class,'first','first');
    }
    public function childlist(){
        return $this->hasMany(self::class,'pid','id');
    }

    /**
     * 根据name获取id
     * @param $name
     * @param int $level
     */
    public static function getIdByName($name,$level=1,$pid=0){
        $id = self::where('name','like',$name.'%')->where('level',$level)->value('id');
        if(!$id){
            $area = self::create([
                'name'      =>  $name,
                'shortname' =>  $name,
                'status'    => '1',
                'pid'       =>  $pid,
                'level'     => $level
            ]);
            $id = $area->id;
        }
        return $id;
    }

    /**
     * 根据经纬度获取城市
     * @param $lng
     * @param $lat
     * @return array|bool|false|\PDOStatement|string|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getCityFromLngLat($lng, $lat){
        $arr = xiluxcGetAddr($lat, $lng);
        if (isset($arr['city'])) {
            $city = (new self)->where('level', 2)->normal()->where('name', 'like', $arr['city'] . '%')->field('id,name')->find() ?: [];
            $city['pois'] = $arr['pois'] ?? [];
        } else {
            $city = [];
        }
        return $city;
    }


}

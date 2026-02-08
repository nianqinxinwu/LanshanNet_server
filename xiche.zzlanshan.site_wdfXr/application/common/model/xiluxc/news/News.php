<?php

namespace app\common\model\xiluxc\news;

use think\Model;
use traits\model\SoftDelete;
use function fast\array_get;

class News extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'xiluxc_news';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [

    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            if (!$row['weigh']) {
                $pk = $row->getPk();
                $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
            }
        });
    }

    
    public function getStatusList()
    {
        return ['hidden' => __('Status hidden'), 'normal' => __('Status normal')];
    }


    public function scopeNormal($query){
        return $query->where("status","normal");
    }

    public function getImageTextAttr($value,$data){
        $value = isset($data['image']) && $data['image'] ? $data['image'] : '';
        return $value?cdnurl($value,true):'';
    }

    public function getCreatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['createtime']) ? $data['createtime'] : '');
        return is_numeric($value) ? date("Y.m.d", $value) : $value;
    }

    /**
     * 列表
     * @return mixed
     */
    public static function lists(){
        $params = request()->param("");
        $news = new self;
        if($categoryId = array_get($params,'category_id')){
            $news->where("category_id",$categoryId);
        }
        $lists = $news->field("id,name,createtime,view_num,image")
            ->normal()
            ->order("weigh","desc")
            ->paginate($params['pagesize'] ?? 10)
            ->each(function ($row){
                $row->append(['createtime_text','image_text']);
            });
        return $lists;
    }


}

<?php

namespace app\common\model\xiluxc\message;

use app\common\model\xiluxc\user\User;
use think\Model;
use traits\model\SoftDelete;

class Advice extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'xiluxc_advice';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'reply_status_text'
    ];
    

    
    public function getReplyStatusList()
    {
        return ['0' => __('Reply_status 0'), '1' => __('Reply_status 1')];
    }


    public function getReplyStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['reply_status']) ? $data['reply_status'] : '');
        $list = $this->getReplyStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }
    public function getCreatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['createtime']) ? $data['createtime'] : '');
        return datetime($value,'Y-m-d H:i');
    }

    public function getReplytimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['replytime']) ? $data['replytime'] : '');
        return datetime($data['createtime'],'Y-m-d H:i');
    }

    public function getImagesTextAttr($value,$data){
        $value = !empty($data['images']) ? $data['images']:'';
        $images = [];
        if($value && is_string($value)){
            $value = explode(',',$value);
            foreach ($value as $image){
                $images[] = cdnurl($image,true);
            }
        }
        return $images;
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','id',[],'left')->setEagerlyType(0);
    }


}

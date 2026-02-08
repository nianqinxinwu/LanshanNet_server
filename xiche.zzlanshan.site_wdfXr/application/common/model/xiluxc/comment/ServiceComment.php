<?php

namespace app\common\model\xiluxc\comment;

use app\common\model\User;
use app\common\model\xiluxc\brand\Shop;
use app\common\model\xiluxc\order\Order;
use app\common\model\xiluxc\order\OrderItem;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\Model;
use traits\model\SoftDelete;
use function fast\array_get;

class ServiceComment extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'xiluxc_service_comment';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [

    ];
    

    
    public function getStatusList()
    {
        return ['hidden' => __('Status hidden'), 'normal' => __('Status normal')];
    }

    public function getCreatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['createtime']) ? $data['createtime'] : '');
        return $value ? xiluxc_time_tran($value) : '';
    }

    public function getImagesTextAttr($value,$data){
        $images = [];
        $value = isset($data['images'])?$data['images']:'';
        if($value && is_string($value)){
            $images_array = explode(',',$value);
            foreach ($images_array as $image){
                $images[] = cdnurl($image,true);
            }
        }
        return $images;
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','id',[],'inner')->setEagerlyType(0);
    }

    public function shop(){
        return $this->belongsTo(Shop::class,'shop_id','id',[],'inner')->setEagerlyType(0);
    }
    public function ordering(){
        return $this->belongsTo(Order::class,'order_id','id',[],'inner')->setEagerlyType(0);
    }

    public function orderItem(){
        return $this->belongsTo(OrderItem::class,'order_item_id','id',[],'left')->setEagerlyType(0);
    }

    /**
     * @param $params
     * @return bool|false|\PDOStatement|string|\think\Collection
     * @throws Exception
     * @throws PDOException
     * @throws \think\exception\DbException
     */
    public static function addComment($params) {
        $orderId = array_get($params,'order_id');
        if(!$orderId){
            throw new Exception("参数错误");
        }
        $order = Order::get($orderId);
        if(!$order){
            throw new Exception("不允许评论");
        }
        if($order->comment_status == 1){
            throw new Exception("不要重复评论");
        }
        Db::startTrans();
        try {
            $images = array_get($params,'images');
            $params['images'] = $images? (is_array($images) ? implode(',',$images) : $images) :'';
            $params['content'] = array_get($params,'content', '');
            $params['status'] = 'normal';
            $params['avg_star'] = bcdiv($params['service_star']+$params['comprehensive_star'],2,1);
            $params['shop_service_id'] = $order['order_item']['data_id'];
            $params['service_price_id'] = $order['order_item']['service_price_id'];
            $params['order_item_id'] = $order['order_item']['id'];
            $params['shop_id'] = $order['shop_id'];
            $comment = new self();
            $ret = $comment->allowField(true)->save($params);
            if ($ret === false) {
                throw new Exception($comment->getError());
            }
            $order->comment_status = 1;
            $order->commenttime = time();
            $order->allowField(['comment_status','commenttime'])->save();
            #计算评分
            $comment = self::field("SUM(service_star) total_service_star,SUM(comprehensive_star) total_comprehensive_star, COUNT(*) total_count")->where('shop_id', $params['shop_id'])->group("shop_id")->select();
            $avg1 = bcdiv($comment[0]['total_comprehensive_star'],$comment[0]['total_count'],1);
            $avg2 = bcdiv($comment[0]['total_service_star'],$comment[0]['total_count'],1);
            $avg = bcdiv(bcadd($avg1,$avg2,1),2,1);
            Shop::where('id',$order['shop_id'])->update(['point'=>$avg]);
        }catch (Exception|PDOException $e) {
            Db::rollback();
            throw $e;
        }
        Db::commit();
        return $comment;
    }

}

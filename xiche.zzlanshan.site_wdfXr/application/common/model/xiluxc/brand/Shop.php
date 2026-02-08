<?php

namespace app\common\model\xiluxc\brand;

use app\common\library\Auth;
use app\common\model\User;
use app\common\model\xiluxc\current\Tag;
use app\common\model\xiluxc\service\Service;
use app\common\model\xiluxc\user\UserShopVip;
use think\Model;
use function fast\array_get;


class Shop extends Model
{

    

    

    // 表名
    protected $name = 'xiluxc_shop';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'audittime_text'
    ];

    const AUDIT_STATUS_CHECKED = "checked";
    const AUDIT_STATUS_PASSED = "passed";
    const AUDIT_STATUS_FAILED = "failed";

    
    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2')];
    }

    public function getStatusList()
    {
        return ['hidden' => __('Status hidden'), 'normal' => __('Status normal')];
    }

    public function getAuditStatusList()
    {
        return ['checked' => __('Audit_status checked'), 'passed' => __('Audit_status passed'), 'failed' => __('Audit_status failed')];
    }

    public function scopeNormal($query){
        return $query->where("status","normal");
    }

    public function scopePassed($query){
        $query->where("audit_status",self::AUDIT_STATUS_PASSED);
    }

    public function getAudittimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['audittime']) ? $data['audittime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getImageTextAttr($value,$data){
        $value = isset($data['image']) && $data['image'] ? $data['image'] : '';
        return $value?cdnurl($value,true):'';
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

    public function account(){
        return $this->hasOne(ShopAccount::class,'shop_id','id',[],'left')->setEagerlyType(0);
    }

    public function shopTag(){
        return $this->belongsToMany(Tag::class,ShopTag::class,'tag_id','shop_id');
    }

    /**
     * 门店服务
     * @param $value
     * @param $data
     * @return bool|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getServicesAttr($value,$data){
        return ShopService::with(['service'=>function($row){
            $row->withField(['id','name']);
        }])
            ->where("shop_id",$data['id'])
            ->select();
    }

    /**
     * 门店服务
     * @return \think\model\relation\BelongsToMany
     */
    public function shopServices(){
        $service = new ShopBranchService();
        $currentName = $service->getTable();
        return $service->with(["shopService","service"=>function($query){
            $query->withField(['id','name']);
        }])
            ->where($currentName.".status",'normal')
            ->where("shopService.status",'normal')
            ->where($currentName.".shop_id",$this->id)
            ->select();
        //return $this->belongsToMany(Service::class,ShopService::class,'service_id','shop_id')->wherePivot("status","normal");
    }

    /**
     * 门店套餐
     */
    public function getShopPackageAttr($value,$data){
        $package = new Package();
        $currentName = $package->getTable();
        return $package->whereExists(function ($query) use($currentName,$data){
            $shopBranchPackage = (new BranchPackage())->getQuery()->getTable();
            $query->table($shopBranchPackage)->where($currentName . '.id=' . $shopBranchPackage . '.shop_package_id')->where("shop_id",$data['id'])->where("status",'normal');
        })->normal()->field("id,name,shop_id,image,salesprice,vip_price,original_price")->order("weigh",'desc')->select();
    }
    /**
     * 门店/管理员条件
     */
    public static function getBrandShopWhere($shop){
        if($shop['type'] == 1){
            $where['shop_id'] = $shop->id;
        }else{
            $brand = ShopBrand::get(['user_id'=>$shop->brand_id]);
            $where['brand_id'] = $brand->id;
        }
       return $where;
    }

    /**
     * 搜索列表
     */
    public static function searchList($params){
        $auth = Auth::instance();
        $shop = new self;
        $shop->normal()->passed();
        if($q = array_get($params,'q')){
            $shop->whereLike("name","%$q%");
        }
        $lat = array_get($params,'lat');
        $lng = array_get($params,'lng');
        if($lng && $lat){
            $field = "id,name,brand_id,type,image,point,lat,lng,address,sales,(ST_DISTANCE(POINT(".$lng.", ".$lat."), POINT(lng, lat)) * 111195) AS distance";
        }else{
            $field = "id,name,brand_id,type,image,point,lat,lng,address,sales";
        }
        if($districtId = array_get($params,'district_id')){
            $shop->where("district_id",$districtId);
        }
        if($isBrand = array_get($params,'is_brand')){
            $shop->where("type",'2');
        }
        if($distance = array_get($params,'distance')){
            $shop->whereRaw("(ST_DISTANCE(POINT(".$lng.", ".$lat."), POINT(lng, lat)) * 111195)<=".$distance*1000);
        }
        $sort  = $lng && $lat ? array_get($params,'sort','weigh') : 'sales';
        $order  = array_get($params,'order','desc');
        $serviceId = array_get($params,'service_id');
        // 当前表名
        $currentName = $shop->getTable();
        if($serviceId){
            $shop->whereExists(function ($query) use($currentName,$serviceId){
                $shopService = (new ShopBranchService())->getQuery()->getTable();
                if($serviceId){
                    $query->table($shopService)->where($currentName . '.id=' . $shopService . '.shop_id')->where("service_id",$serviceId);
                    return $query;
                }
            });
        }
        $lists = $shop->field($field)
            ->order($sort,$order)
            ->order("point","desc")
            ->paginate(request()->param('pagesize',10))
            ->each(function ($row) use($auth){
                //是否vip
                $row->is_vip = $auth->isLogin()?UserShopVip::shopDetailVip($auth->id,$row):['status'=>0];
                $row->append(['image_text']);
                $row->shop_services = $row->shopServices();
                $row->distance = isset($row->distance) ? ($row->distance>=1000 ? bcdiv($row->distance,1000,1).'km' : bcadd($row->distance,0,1).'m') : '';
            });
        return $lists;
    }

}

<?php


namespace app\api\controller\xiluxc;


use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\xiluxc\activity\Banner;
use app\common\model\xiluxc\activity\Navigation;
use app\common\model\xiluxc\car\CarBrand;
use app\common\model\xiluxc\car\CarModels;
use app\common\model\xiluxc\car\CarSeries;
use app\common\model\xiluxc\current\Area;
use app\common\model\xiluxc\current\Config;
use app\common\model\xiluxc\current\Property;
use app\common\model\xiluxc\current\Singlepage;
use app\common\model\xiluxc\news\NewsCategory;
use app\common\model\xiluxc\service\Service;
use OSS\OssClient;
use function fast\array_get;

class Common extends XiluxcApi
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = '*';

    /**
     * 全局配置信息
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function init_config(){

        $shopinfo = Config::getMyConfig('shopinfo');
        $apply = Config::getMyConfig('apply');
        $config['logo'] = isset($shopinfo['logo'])?cdnurl($shopinfo['logo'],true) :'';
        $config['refund_reasons'] = isset($shopinfo['refund_reason'])?json_decode($shopinfo['refund_reason'],true) :[];
        $config['apply_rule'] = isset($apply['apply_rule'])?$apply['apply_rule'] : '';
        $config['user_agreement'] = isset($shopinfo['user_agreement'])?$shopinfo['user_agreement'] : '';
        $config['privacy_agreement'] = isset($shopinfo['privacy_agreement'])?$shopinfo['privacy_agreement'] : '';
        $config['brand_agreement'] = isset($shopinfo['brand_agreement'])?$shopinfo['brand_agreement'] : '';
        $config['shop_agreement'] = isset($shopinfo['shop_agreement'])?$shopinfo['shop_agreement'] : '';
        $this->success('',['config'=>$config]);
    }

    /**
     * 图片上传
     */
    public function params()
    {
        $name = $this->request->post('name');
        $md5 = $this->request->post('md5');
        $auth = new \addons\alioss\library\Auth();
        $params = $auth->params($name, $md5);
        config('default_return_type','json');//返回json而不是html
        $this->success('', $params);
    }

    /**
     * 单页文章
     */
    public function singlepage(){
        $id = $this->request->get('id');
        $row = $id ? Singlepage::where("id",$id)->find() : null;
        $this->success('',$row);
    }

    /**
     * 省市区树状图
     */
    public function area_tree(){
        $areasModel = new Area;
        $lists = $areasModel->field("id,name")
            ->with(['childlist'=>function($query){
                $query->field(['id','name','pid'])
                    ->normal()
                    ->with(['childlist'=>function($query){
                        $query->normal()->field(['id','name','pid']);
                }]);
            }])
            ->where('level',1)
            ->normal()
            //->order('first','asc')
            ->select();
        $this->success('',$lists);
    }

    /**
     * 根据经纬度获取城市
     */
    public function get_city_by_lat(){
        $params = $this->request->param('');
        $lat = array_get($params,'lat');
        $lng = array_get($params,'lng');
        $result = Area::getCityFromLngLat($lng,$lat);
        
        // var_dump($result);
        if(!$result){
            $this->error('城市未找到或未开放');
        }
        $this->success('',$result);
    }

    /**
     * 根据首字母获取城市
     */
    public function cities(){
        $areaModel = new Area();
        $list = $areaModel->field("first")
            ->with(['children'=>function($query){
                $query->where('level',2)->where('status','normal')->field(['id','first','name','shortname']);
            }])
            ->normal()
            ->where('level',2)
            ->order('first','asc')
            ->group("first")
            ->select();
        $hot_cities = Area::where('level',2)->where('is_re',2)->normal()->field(['id','first','name','shortname'])->select();
        $this->success('',['hot_cities'=>$hot_cities,'cities'=>$list]);
    }

    /**
     * 全市区县
     */
    public function districts(){
        $cityId = $this->request->param('city_id');
        $areaModel = new Area();
        $list = $areaModel->field("id,name")->normal()->where("pid",$cityId)->select();
        $list = collection($list)->toArray();
        $this->success('',array_merge([['id'=>0,'name'=>'全部']],$list));
    }

    //首页金刚区
    public function navication(){
        $list = Navigation::field("id,name,mini_appid,icon_image,type,jump_type,url")->where('status','normal')->order('weigh','desc')->select();
        $this->success('',$list);
    }

    /**
     * 广告图
     */
    public function banner(){
        $group = $this->request->param('group');
        $list = Banner::field("id,thumb_image")->where("group",$group)->where('status','normal')->order('weigh','desc')->select();
        $this->success('',$list);
    }

    /**
     * 平台服务
     */
    public function services(){
        $services = new Service();
        $list = $services->field("id,name,image")->normal()->order('weigh','asc')->select();
        $this->success('',$list);
    }

    /**
     * 店铺属性
     */
    public function shop_property(){
        $lists = Property::normal()
            ->select();
        $this->success('',$lists);
    }

    /**
     * 新闻分类
     */
    public function news_category(){
        $category = new NewsCategory();
        $list = $category->field("id,name")->normal()->order('weigh','asc')->select();
        $this->success('',$list);
    }

    /**
     * 车辆品牌
     */
    public function car_brand(){

        $brands = (new CarBrand)->field("first_letter")
            ->normal()
            ->with(['brands'=>function($query){
                $query->field(['id','name','first_letter','image']);
            }])
            ->order("first_letter",'asc')
            ->group("first_letter")
            ->select();
        $letters = [];
        foreach ($brands as $brand){
            $letters[] = $brand['first_letter'];
        }
        $this->success('',['brands'=>$brands,'letters'=>$letters]);
    }

    /**
     * 车辆车系
     */
    public function car_series(){
        $brandId = $this->request->param('brand_id');
        $series = $brandId ? CarSeries::normal()
            ->where("brand_id",$brandId)
            ->select() : [];
        foreach ($series as $serie){
            $serie->is_models = CarModels::where("series_id",$serie->id)->count() ? true: false;
        }
        $this->success('',['brand'=>CarBrand::get($brandId),'series'=>$series]);
    }

    /**
     * 车辆型号
     */
    public function car_models(){
        $seriesId = $this->request->param('series_id');
        $models = $seriesId ? CarModels::normal()
            ->where("series_id",$seriesId)
            ->select() : [];
        $series = CarSeries::get($seriesId);
        $brand = $series ? CarBrand::get($series->brand_id) : null;
        $this->success('',['brand'=>$brand,'series'=>$series,'models'=>$models]);
    }

}
<?php

namespace app\common\model\xiluxc\current;

use app\common\model\xiluxc\brand\Package;
use app\common\model\xiluxc\brand\Shopvip;
use app\common\model\xiluxc\service\Service;
use think\Model;

/**
 * 配置模型
 */
class Config extends Model
{

    // 表名,不含前缀
    protected $name = 'xiluxc_config';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;
    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    // 追加属性
    protected $append = [

    ];

    protected static function init()
    {
        self::beforeUpdate(function ($row){
            if($row['name'] == 'distribution'){
                $changed = $row->getChangedData();
                if(isset($changed['value'])){
                    $value = json_decode($changed['value'],true);
                    if($value['distribution_type'] == 2){
                        //单品设置，修改全局的
                        Service::where("status",'normal')->update([
                            'distribution_one_rate'=>$value['service_distribution_one_rate'],
                            'distribution_two_rate'=>$value['service_distribution_two_rate'],
                        ]);
                        Package::where("status",'normal')->update([
                            'distribution_one_rate'=>$value['package_distribution_one_rate'],
                            'distribution_two_rate'=>$value['package_distribution_two_rate'],
                        ]);
                        Shopvip::where("status",'normal')->update([
                            'distribution_one_rate'=>$value['vip_distribution_one_rate'],
                            'distribution_two_rate'=>$value['vip_distribution_two_rate'],
                        ]);
                    }
                }

            }
        });
    }

    /**
     * 日期时间戳
     * @param null $date
     * @return false|int
     */
    public static function getTodayTime($date=NULL){
        return $date ? strtotime($date) : strtotime(date("Y-m-d"));
    }

    /**
     * 订单过期时间
     * @return int;
     */
    public static function getExpiretime(){
        $shopinfo = Config::getMyConfig('shopinfo');
        $auto_close_minutes = !empty($shopinfo['auto_close_minutes']) ? $shopinfo['auto_close_minutes'] : 15;
        return time()+bcmul($auto_close_minutes,60);
    }

    /**
     * 读取配置类型
     * @return array
     */
    public static function getTypeList()
    {
        $typeList = [
            'string'        => __('String'),
            'password'      => __('Password'),
            'text'          => __('Text'),
            'editor'        => __('Editor'),
            'number'        => __('Number'),
            'date'          => __('Date'),
            'time'          => __('Time'),
            'datetime'      => __('Datetime'),
            'datetimerange' => __('Datetimerange'),
            'select'        => __('Select'),
            'selects'       => __('Selects'),
            'image'         => __('Image'),
            'images'        => __('Images'),
            'file'          => __('File'),
            'files'         => __('Files'),
            'switch'        => __('Switch'),
            'checkbox'      => __('Checkbox'),
            'radio'         => __('Radio'),
            'city'          => __('City'),
            'selectpage'    => __('Selectpage'),
            'selectpages'   => __('Selectpages'),
            'array'         => __('Array'),
            'custom'        => __('Custom'),
        ];
        return $typeList;
    }

    public static function getRegexList()
    {
        $regexList = [
            'required' => '必选',
            'digits'   => '数字',
            'letters'  => '字母',
            'date'     => '日期',
            'time'     => '时间',
            'email'    => '邮箱',
            'url'      => '网址',
            'qq'       => 'QQ号',
            'IDcard'   => '身份证',
            'tel'      => '座机电话',
            'mobile'   => '手机号',
            'zipcode'  => '邮编',
            'chinese'  => '中文',
            'username' => '用户名',
            'password' => '密码'
        ];
        return $regexList;
    }

    public function getExtendHtmlAttr($value, $data)
    {
        $result = preg_replace_callback("/\{([a-zA-Z]+)\}/", function ($matches) use ($data) {
            if (isset($data[$matches[1]])) {
                return $data[$matches[1]];
            }
        }, $data['extend']);
        return $result;
    }

    /**
     * 读取分类分组列表
     * @return array
     */
    public static function getGroupList()
    {
        $groupList = self::group('group')->column('group');
        foreach ($groupList as $k => &$v) {
            $v = __($v);
        }
        return $groupList;
    }

    public static function getArrayData($data)
    {
        if (!isset($data['value'])) {
            $result = [];
            foreach ($data as $index => $datum) {
                $result['field'][$index] = $datum['key'];
                $result['value'][$index] = $datum['value'];
            }
            $data = $result;
        }
        $fieldarr = $valuearr = [];
        $field = isset($data['field']) ? $data['field'] : (isset($data['key']) ? $data['key'] : []);
        $value = isset($data['value']) ? $data['value'] : [];
        foreach ($field as $m => $n) {
            if ($n != '') {
                $fieldarr[] = $field[$m];
                $valuearr[] = $value[$m];
            }
        }
        return $fieldarr ? array_combine($fieldarr, $valuearr) : [];
    }

    /**
     * 将字符串解析成键值数组
     * @param string $text
     * @return array
     */
    public static function decode($text, $split = "\r\n")
    {
        $content = explode($split, $text);
        $arr = [];
        foreach ($content as $k => $v) {
            if (stripos($v, "|") !== false) {
                $item = explode('|', $v);
                $arr[$item[0]] = $item[1];
            }
        }
        return $arr;
    }

    /**
     * 将键值数组转换为字符串
     * @param array $array
     * @return string
     */
    public static function encode($array, $split = "\r\n")
    {
        $content = '';
        if ($array && is_array($array)) {
            $arr = [];
            foreach ($array as $k => $v) {
                $arr[] = "{$k}|{$v}";
            }
            $content = implode($split, $arr);
        }
        return $content;
    }

    /**
     * 本地上传配置信息
     * @return array
     */
    public static function upload()
    {
        $uploadcfg = config('upload');

        $uploadurl = request()->module() ? $uploadcfg['uploadurl'] : ($uploadcfg['uploadurl'] === 'ajax/upload' ? 'index/' . $uploadcfg['uploadurl'] : $uploadcfg['uploadurl']);

        if (!preg_match("/^((?:[a-z]+:)?\/\/)(.*)/i", $uploadurl) && substr($uploadurl, 0, 1) !== '/') {
            $uploadurl = url($uploadurl, '', false);
        }
        $uploadcfg['fullmode'] = isset($uploadcfg['fullmode']) && $uploadcfg['fullmode'] ? true : false;
        $uploadcfg['thumbstyle'] = $uploadcfg['thumbstyle'] ?? '';

        $upload = [
            'cdnurl'     => $uploadcfg['cdnurl'],
            'uploadurl'  => $uploadurl,
            'bucket'     => 'local',
            'maxsize'    => $uploadcfg['maxsize'],
            'mimetype'   => $uploadcfg['mimetype'],
            'chunking'   => $uploadcfg['chunking'],
            'chunksize'  => $uploadcfg['chunksize'],
            'savekey'    => $uploadcfg['savekey'],
            'multipart'  => [],
            'multiple'   => $uploadcfg['multiple'],
            'fullmode'   => $uploadcfg['fullmode'],
            'thumbstyle' => $uploadcfg['thumbstyle'],
            'storage'    => 'local'
        ];
        return $upload;
    }


    /**
     * 刷新配置文件
     */
    public static function refreshFile()
    {
        //如果没有配置权限无法进行修改
        if (!\app\admin\library\Auth::instance()->check('xiluxc/config/config')) {
            return false;
        }
        $config = [];
        $configList = self::all();
        foreach ($configList as $k => $v) {
            $value = $v->toArray();
            if (in_array($value['type'], ['selects', 'checkbox', 'images', 'files'])) {
                $value['value'] = explode(',', $value['value']);
            }
            if ($value['type'] == 'array') {
                $value['value'] = (array)json_decode($value['value'], true);
            }
            $config[$value['name']] = $value['value'];
        }
        file_put_contents(
            CONF_PATH . 'extra' . DS . 'xiluxc.php',
            '<?php' . "\n\nreturn " . var_export_short($config) . ";\n"
        );
        return true;
    }


    /**
     * 获取对应的name配置
     * @param null $name
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public static function getMyConfig($name=NULL){
        $config = [];
        $configList = self::whereNotIn('name',['minimessage'])->select();
        foreach ($configList as $k => $v) {
            $value = $v->toArray();
            if ($value['type'] == 'array') {
                $value['value'] = (array)json_decode($value['value'], true);
            }
            $config[$value['name']] = $value['value'];
        }
        if($name){
            return isset($config[$name]) ? $config[$name] : [];
        }
        return $config;
    }

}

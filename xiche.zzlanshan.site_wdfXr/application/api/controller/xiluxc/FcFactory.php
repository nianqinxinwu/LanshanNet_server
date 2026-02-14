<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\Factory;
use think\Validate;

/**
 * 工厂端 - 企业认证
 */
class FcFactory extends XiluxcApi
{
    protected $noNeedLogin = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取当前用户关联的工厂记录
     */
    protected function getFactory($mustExist = true)
    {
        $factory = Factory::where('user_id', $this->auth->id)->find();
        if ($mustExist && !$factory) {
            $this->error('尚未提交认证，请先提交企业资质');
        }
        return $factory;
    }

    /**
     * 提交企业认证
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="company_name", type="string", required=true, description="企业名称")
     * @ApiParams (name="province", type="string", required=false, description="所在省份")
     * @ApiParams (name="industry", type="string", required=false, description="行业")
     * @ApiParams (name="contact_name", type="string", required=true, description="联系人")
     * @ApiParams (name="contact_phone", type="string", required=true, description="联系电话")
     * @ApiParams (name="business_license", type="string", required=true, description="营业执照图片URL")
     * @ApiParams (name="inspection_reports", type="string", required=false, description="检测报告URL(JSON数组)")
     * @ApiParams (name="trade_certs", type="string", required=false, description="贸易证书URL(JSON数组)")
     * @ApiParams (name="factory_photos", type="string", required=false, description="工厂照片URL(JSON数组)")
     */
    public function submit_cert()
    {
        $userId = $this->auth->id;
        $companyName = $this->request->post('company_name');
        $province = $this->request->post('province', '');
        $industry = $this->request->post('industry', '');
        $contactName = $this->request->post('contact_name');
        $contactPhone = $this->request->post('contact_phone');
        $businessLicense = $this->request->post('business_license');
        $inspectionReports = $this->request->post('inspection_reports', '[]');
        $tradeCerts = $this->request->post('trade_certs', '[]');
        $factoryPhotos = $this->request->post('factory_photos', '[]');

        if (!$companyName || !$contactName || !$contactPhone || !$businessLicense) {
            $this->error('请填写完整的企业信息');
        }
        if (!Validate::regex($contactPhone, "^1\d{10}$")) {
            $this->error('请输入正确的手机号');
        }

        $existing = Factory::where('user_id', $userId)->find();

        $data = [
            'user_id'             => $userId,
            'company_name'        => $companyName,
            'province'            => $province,
            'industry'            => $industry,
            'contact_name'        => $contactName,
            'contact_phone'       => $contactPhone,
            'business_license'    => $this->normalizeUrl($businessLicense),
            'inspection_reports'  => $this->normalizeUrlArray($inspectionReports),
            'trade_certs'         => $this->normalizeUrlArray($tradeCerts),
            'factory_photos'      => $this->normalizeUrlArray($factoryPhotos),
            'status'              => Factory::STATUS_NORMAL,
        ];

        if ($existing) {
            if ($existing['status'] == Factory::STATUS_NORMAL) {
                // 已认证，同步 isFactory 标识后返回
                \app\common\model\User::where('id', $userId)->update([
                    'isFactory' => 1,
                    'businessLicense' => $this->normalizeUrl($existing['business_license']),
                ]);
                $this->success('企业已认证通过', [
                    'factory_id'  => $existing['id'],
                    'status'      => $existing['status'],
                    'status_text' => $existing['status_text'],
                ]);
            }
            $existing->save($data);
            $factory = $existing;
        } else {
            $data['fulfill_rate'] = 100;
            $data['product_count'] = 0;
            $factory = Factory::create($data);
        }

        // 认证通过，同步用户表 isFactory 标识 + 营业执照
        \app\common\model\User::where('id', $userId)->update([
            'isFactory' => 1,
            'businessLicense' => $data['business_license'],
        ]);

        $this->success('认证提交成功', [
            'factory_id' => $factory['id'],
            'status'     => $factory['status'],
            'status_text' => $factory['status_text'],
        ]);
    }

    /**
     * 认证状态查询
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function cert_status()
    {
        $factory = Factory::where('user_id', $this->auth->id)->find();

        if (!$factory) {
            $this->success('查询成功', [
                'certified'   => false,
                'factory_id'  => 0,
                'status'      => -1,
                'status_text' => '未提交',
            ]);
            return;
        }

        $this->success('查询成功', [
            'certified'          => $factory['status'] == Factory::STATUS_NORMAL,
            'factory_id'         => $factory['id'],
            'status'             => $factory['status'],
            'status_text'        => $factory['status_text'],
            'company_name'       => $factory['company_name'],
            'province'           => $factory['province'],
            'industry'           => $factory['industry'],
            'contact_name'       => $factory['contact_name'],
            'contact_phone'      => $factory['contact_phone'],
            'business_license'   => $factory['business_license'] ? cdnurl($factory['business_license'], true) : '',
            'inspection_reports' => array_map(function ($v) { return $v ? cdnurl($v, true) : ''; }, json_decode($factory['inspection_reports'] ?: '[]', true) ?: []),
            'trade_certs'        => array_map(function ($v) { return $v ? cdnurl($v, true) : ''; }, json_decode($factory['trade_certs'] ?: '[]', true) ?: []),
            'factory_photos'     => array_map(function ($v) { return $v ? cdnurl($v, true) : ''; }, json_decode($factory['factory_photos'] ?: '[]', true) ?: []),
            'fulfill_rate'       => $factory['fulfill_rate'],
        ]);
    }

    /**
     * 更新工厂基本信息（已认证后）
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function update_info()
    {
        $factory = $this->getFactory();
        if ($factory['status'] != Factory::STATUS_NORMAL) {
            $this->error('企业未认证通过，无法修改信息');
        }

        $allowFields = ['contact_name', 'contact_phone', 'province', 'industry', 'business_license', 'inspection_reports', 'trade_certs', 'factory_photos'];
        $data = [];
        foreach ($allowFields as $field) {
            $val = $this->request->post($field);
            if ($val !== null) {
                $data[$field] = $val;
            }
        }

        if (empty($data)) {
            $this->error('无可更新的字段');
        }

        // 对图片URL字段做相对路径归一化
        if (isset($data['business_license'])) {
            $data['business_license'] = $this->normalizeUrl($data['business_license']);
        }
        if (isset($data['inspection_reports'])) {
            $data['inspection_reports'] = $this->normalizeUrlArray($data['inspection_reports']);
        }
        if (isset($data['trade_certs'])) {
            $data['trade_certs'] = $this->normalizeUrlArray($data['trade_certs']);
        }
        if (isset($data['factory_photos'])) {
            $data['factory_photos'] = $this->normalizeUrlArray($data['factory_photos']);
        }

        $factory->save($data);

        // 同步营业执照到用户表
        if (isset($data['business_license'])) {
            \app\common\model\User::where('id', $this->auth->id)->update([
                'businessLicense' => $data['business_license'],
            ]);
        }

        $this->success('更新成功');
    }
}

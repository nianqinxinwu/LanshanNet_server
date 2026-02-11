<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\BuyerInfo;

/**
 * 居间人端 - 买家信息管理
 */
class JjBuyerInfo extends XiluxcApi
{
    protected $noNeedLogin = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 买家信息列表
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     */
    public function index()
    {
        $userId = $this->auth->id;

        $list = BuyerInfo::where('user_id', $userId)
            ->order('is_default', 'desc')
            ->order('id', 'desc')
            ->select();

        $this->success('查询成功', $list);
    }

    /**
     * 新增买家信息
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="company_name", type="string", required=true, description="企业名称")
     * @ApiParams (name="address", type="string", required=true, description="收货地址")
     * @ApiParams (name="contact_name", type="string", required=true, description="联系人")
     * @ApiParams (name="contact_phone", type="string", required=true, description="联系电话")
     * @ApiParams (name="credit_code", type="string", required=false, description="统一社会信用代码")
     * @ApiParams (name="tax_number", type="string", required=false, description="税务登记证号")
     */
    public function add()
    {
        $userId = $this->auth->id;

        $companyName = $this->request->post('company_name', '');
        $address = $this->request->post('address', '');
        $contactName = $this->request->post('contact_name', '');
        $contactPhone = $this->request->post('contact_phone', '');
        $creditCode = $this->request->post('credit_code', '');
        $taxNumber = $this->request->post('tax_number', '');

        if (!$companyName || !$address || !$contactName || !$contactPhone) {
            $this->error('请填写完整的买家信息');
        }

        // 检查是否为第一条记录
        $count = BuyerInfo::where('user_id', $userId)->count();
        $isDefault = ($count === 0) ? 1 : 0;

        $buyer = new BuyerInfo();
        $buyer->user_id = $userId;
        $buyer->company_name = $companyName;
        $buyer->address = $address;
        $buyer->contact_name = $contactName;
        $buyer->contact_phone = $contactPhone;
        $buyer->credit_code = $creditCode;
        $buyer->tax_number = $taxNumber;
        $buyer->is_default = $isDefault;
        $buyer->save();

        $this->success('保存成功', $buyer);
    }

    /**
     * 删除买家信息
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="id", type="int", required=true, description="买家信息ID")
     */
    public function delete()
    {
        $userId = $this->auth->id;
        $id = $this->request->post('id/d', 0);

        if (!$id) {
            $this->error('缺少参数');
        }

        $buyer = BuyerInfo::where('id', $id)
            ->where('user_id', $userId)
            ->find();

        if (!$buyer) {
            $this->error('记录不存在');
        }

        $buyer->delete();

        $this->success('删除成功');
    }
}

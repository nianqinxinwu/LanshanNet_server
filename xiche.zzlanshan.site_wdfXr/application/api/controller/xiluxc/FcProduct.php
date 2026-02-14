<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\Factory;
use app\common\model\jj\Product;
use think\Validate;

/**
 * 工厂端 - 产品管理
 */
class FcProduct extends XiluxcApi
{
    protected $noNeedLogin = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取当前用户关联的工厂（必须已认证）
     */
    protected function getFactory()
    {
        $factory = Factory::where('user_id', $this->auth->id)->find();
        if (!$factory) {
            $this->error('请先完成企业认证');
        }
        if ($factory['status'] != Factory::STATUS_NORMAL) {
            $this->error('企业认证未通过，无法管理产品');
        }
        return $factory;
    }

    /**
     * 格式化金额：千分位 + 2位小数，空值返回 "0.00"
     */
    protected function formatMoney($val)
    {
        return number_format(floatval($val ?: 0), 2, '.', ',');
    }

    /**
     * 产品列表
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="status", type="int", required=false, description="状态筛选:0=下架 1=上架")
     * @ApiParams (name="keyword", type="string", required=false, description="关键词搜索")
     * @ApiParams (name="page", type="int", required=false, description="页码")
     * @ApiParams (name="pagesize", type="int", required=false, description="每页数量")
     */
    public function index()
    {
        $factory = $this->getFactory();

        $status = $this->request->get('status', '');
        $keyword = $this->request->get('keyword', '');
        $page = $this->request->get('page/d', 1);
        $pagesize = $this->request->get('pagesize/d', 10);

        $query = Product::where('factory_id', $factory['id']);

        if ($status !== '') {
            $query->where('status', intval($status));
        }
        if ($keyword) {
            $query->where('name', 'like', '%' . $keyword . '%');
        }

        $list = $query->order('id', 'desc')
            ->paginate($pagesize, false, ['page' => $page]);

        // 格式化金额字段
        $listData = $list->toArray();
        foreach ($listData['data'] as &$item) {
            $item['price'] = $this->formatMoney($item['price'] ?? 0);
            $item['estimated_daily_capacity'] = $this->formatMoney($item['estimated_daily_capacity'] ?? 0);
        }
        unset($item);

        // 统计
        $stats = [
            'total'   => Product::where('factory_id', $factory['id'])->count(),
            'on'      => Product::where('factory_id', $factory['id'])->where('status', Product::STATUS_ON)->count(),
            'off'     => Product::where('factory_id', $factory['id'])->where('status', Product::STATUS_OFF)->count(),
        ];

        $this->success('查询成功', [
            'list'  => $listData,
            'stats' => $stats,
        ]);
    }

    /**
     * 新增产品
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="name", type="string", required=true, description="产品名称")
     * @ApiParams (name="category_id", type="int", required=false, description="品类ID")
     * @ApiParams (name="category_name", type="string", required=false, description="品类名称")
     * @ApiParams (name="cover_image", type="string", required=true, description="封面图URL(必填)")
     * @ApiParams (name="price", type="float", required=true, description="单价")
     * @ApiParams (name="unit", type="string", required=true, description="单位")
     * @ApiParams (name="stock", type="int", required=true, description="库存数量")
     * @ApiParams (name="deposit_rate", type="float", required=false, description="保证金比例(%)")
     * @ApiParams (name="craft_standard", type="string", required=false, description="工艺标准(富文本)")
     * @ApiParams (name="inspection_report_url", type="string", required=true, description="检测报告URL(必填,仅PDF)")
     * @ApiParams (name="estimated_daily_capacity", type="float", required=false, description="日产能佣金(元)")
     */
    public function add()
    {
        $factory = $this->getFactory();

        $name = $this->request->post('name');
        $price = $this->request->post('price/f', 0);
        $unit = $this->request->post('unit');
        $stock = $this->request->post('stock/d', 0);
        // $commissionRate = $this->request->post('commission_rate/f', 0); // 佣金比例已改为固定10%，工厂端不再配置
        $coverImage = $this->request->post('cover_image', '');
        $inspectionReportUrl = $this->request->post('inspection_report_url', '');

        if (!$name || !$unit) {
            $this->error('产品名称和单位为必填项');
        }
        if ($price <= 0) {
            $this->error('单价必须大于0');
        }
        // 佣金比例已改为固定10%，工厂端不再校验
        // if ($commissionRate < 0 || $commissionRate > 100) {
        //     $this->error('佣金比例应在0-100之间');
        // }
        if (!$coverImage) {
            $this->error('请上传产品图片');
        }
        if (!$inspectionReportUrl) {
            $this->error('请上传产品检测报告');
        }
        if (!preg_match('/\.pdf$/i', parse_url($inspectionReportUrl, PHP_URL_PATH) ?: $inspectionReportUrl)) {
            $this->error('检测报告仅支持PDF格式');
        }

        $product = Product::create([
            'factory_id'               => $factory['id'],
            'name'                     => $name,
            'category_id'              => $this->request->post('category_id/d', 0),
            'category_name'            => $this->request->post('category_name', ''),
            'cover_image'              => $coverImage,
            'price'                    => $price,
            'unit'                     => $unit,
            'stock'                    => $stock,
            'commission_rate'          => 10, // 固定佣金比例10%，工厂端不再配置
            'deposit_rate'             => 1, // 固定保证金比例：佣金总额的1%
            'craft_standard'           => $this->request->post('craft_standard', ''),
            'inspection_report_url'    => $inspectionReportUrl,
            'estimated_daily_capacity' => $this->request->post('estimated_daily_capacity/f', 0),
            'status'                   => Product::STATUS_ON, // 新增默认上架
            'onshelf_time'             => time(),
        ]);

        // 更新工厂产品数
        Factory::where('id', $factory['id'])->setInc('product_count');

        $this->success('添加成功', ['product_id' => $product['id']]);
    }

    /**
     * 编辑产品
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="id", type="int", required=true, description="产品ID")
     */
    public function edit()
    {
        $factory = $this->getFactory();
        $id = $this->request->post('id/d', 0);

        $product = Product::where('id', $id)->where('factory_id', $factory['id'])->find();
        if (!$product) {
            $this->error('产品不存在');
        }

        $allowFields = [
            'name', 'category_id', 'category_name', 'cover_image',
            'price', 'unit', 'stock',
            'craft_standard', 'inspection_report_url', 'estimated_daily_capacity'
            // 'commission_rate' 已移除，佣金比例固定10%
            // 'deposit_rate' 已移除，保证金比例固定为佣金总额的1%
        ];

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

        // 价格校验
        if (isset($data['price']) && floatval($data['price']) <= 0) {
            $this->error('单价必须大于0');
        }
        // 佣金比例已改为固定10%，工厂端不再校验
        // if (isset($data['commission_rate'])) {
        //     $rate = floatval($data['commission_rate']);
        //     if ($rate < 0 || $rate > 100) {
        //         $this->error('佣金比例应在0-100之间');
        //     }
        // }
        if (isset($data['inspection_report_url']) && $data['inspection_report_url']) {
            if (!preg_match('/\.pdf$/i', parse_url($data['inspection_report_url'], PHP_URL_PATH) ?: $data['inspection_report_url'])) {
                $this->error('检测报告仅支持PDF格式');
            }
        }

        $product->save($data);
        $this->success('更新成功');
    }

    /**
     * 删除产品
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="id", type="int", required=true, description="产品ID")
     */
    public function del()
    {
        $factory = $this->getFactory();
        $id = $this->request->post('id/d', 0);

        $product = Product::where('id', $id)->where('factory_id', $factory['id'])->find();
        if (!$product) {
            $this->error('产品不存在');
        }
        if ($product['status'] == Product::STATUS_ON) {
            $this->error('上架中的产品不能删除，请先下架');
        }

        $product->delete();
        Factory::where('id', $factory['id'])->setDec('product_count');

        $this->success('删除成功');
    }

    /**
     * 上架产品
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="id", type="int", required=true, description="产品ID")
     */
    public function on_shelf()
    {
        $factory = $this->getFactory();
        $id = $this->request->post('id/d', 0);

        $product = Product::where('id', $id)->where('factory_id', $factory['id'])->find();
        if (!$product) {
            $this->error('产品不存在');
        }
        if ($product['status'] == Product::STATUS_ON) {
            $this->error('产品已上架');
        }
        if (!$product->getData('cover_image')) {
            $this->error('请先上传产品图片后再上架');
        }
        if (!$product->getData('inspection_report_url')) {
            $this->error('请先上传产品检测报告(PDF)后再上架');
        }

        $product->save(['status' => Product::STATUS_ON, 'onshelf_time' => time()]);
        $this->success('上架成功');
    }

    /**
     * 下架产品
     *
     * @ApiMethod (POST)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="id", type="int", required=true, description="产品ID")
     */
    public function off_shelf()
    {
        $factory = $this->getFactory();
        $id = $this->request->post('id/d', 0);

        $product = Product::where('id', $id)->where('factory_id', $factory['id'])->find();
        if (!$product) {
            $this->error('产品不存在');
        }
        if ($product['status'] == Product::STATUS_OFF) {
            $this->error('产品已下架');
        }

        $product->save(['status' => Product::STATUS_OFF]);
        $this->success('下架成功');
    }

    /**
     * 产品详情
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="id", type="int", required=true, description="产品ID")
     */
    public function detail()
    {
        $factory = $this->getFactory();
        $id = $this->request->get('id/d', 0);

        $product = Product::where('id', $id)->where('factory_id', $factory['id'])->find();
        if (!$product) {
            $this->error('产品不存在');
        }

        $productData = $product->toArray();
        // 计算预估产能佣金（使用固定佣金比例10%）
        $price = floatval($product->getData('price') ?? 0);
        $stock = intval($product['stock'] ?? 0);
        $commissionRate = 10; // 固定佣金比例10%
        $productData['estimated_commission'] = $this->formatMoney($price * $stock * $commissionRate / 100);
        $productData['price'] = $this->formatMoney($price);
        $productData['estimated_daily_capacity'] = $this->formatMoney($productData['estimated_daily_capacity']);

        // 检测报告：返回完整URL + 文件名（供下载用）
        $rawReportUrl = $product->getData('inspection_report_url');
        $productData['inspection_report_url'] = $rawReportUrl ? cdnurl($rawReportUrl, true) : '';
        $productData['inspection_report_name'] = $rawReportUrl ? basename($rawReportUrl) : '';

        $this->success('查询成功', $productData);
    }

    /**
     * 下载产品检测报告
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="id", type="int", required=true, description="产品ID")
     */
    public function download_report()
    {
        $factory = $this->getFactory();
        $id = $this->request->get('id/d', 0);

        $product = Product::where('id', $id)->where('factory_id', $factory['id'])->find();
        if (!$product) {
            $this->error('产品不存在');
        }

        $rawUrl = $product->getData('inspection_report_url');
        if (!$rawUrl) {
            $this->error('该产品暂无检测报告');
        }

        $this->success('获取成功', [
            'download_url'  => cdnurl($rawUrl, true),
            'file_name'     => basename($rawUrl),
        ]);
    }
}

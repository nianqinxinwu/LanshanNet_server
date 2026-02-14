<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\Product;
use app\common\model\jj\Factory;

/**
 * 居间人端 - 产品池
 */
class JjProduct extends XiluxcApi
{
    protected $noNeedLogin = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 产品列表
     *
     * @ApiMethod (GET)
     * @ApiHeaders (name=token, type=string, required=true, description="Token")
     * @ApiParams (name="page", type="int", required=false, description="页码")
     * @ApiParams (name="pagesize", type="int", required=false, description="每页数量")
     * @ApiParams (name="category_id", type="int", required=false, description="品类ID")
     * @ApiParams (name="keyword", type="string", required=false, description="关键词搜索")
     */
    public function index()
    {
        $page = $this->request->get('page/d', 1);
        $pagesize = $this->request->get('pagesize/d', 10);
        $categoryId = $this->request->get('category_id/d', 0);
        $keyword = $this->request->get('keyword', '');

        $model = new Product();
        $query = $model->where('fa_jj_product.status', Product::STATUS_ON)
            ->with(['factory' => function ($q) {
                $q->withField(['id', 'company_name', 'fulfill_rate']);
            }]);

        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }
        if ($keyword) {
            $query->where('name', 'like', '%' . $keyword . '%');
        }

        $list = $query->order('fa_jj_product.id', 'desc')
            ->paginate($pagesize, false, ['page' => $page]);

        $this->success('查询成功', $list);
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
        $id = $this->request->get('id/d', 0);
        if (!$id) {
            $this->error('缺少产品ID');
        }

        $product = Product::with(['factory' => function ($q) {
            $q->withField(['id', 'company_name', 'fulfill_rate', 'province', 'industry', 'inspection_reports']);
        }])->where('fa_jj_product.id', $id)->find();

        if (!$product) {
            $this->error('产品不存在');
        }

        $productData = $product->toArray();
        // 服务端计算预估产能佣金（使用固定佣金比例10%）
        $price = floatval($product->getData('price') ?? 0);
        $stock = intval($product['stock'] ?? 0);
        $commissionRate = 10; // 固定佣金比例10%
        $productData['estimated_commission'] = round($price * $stock * $commissionRate / 100, 2);

        // 工厂级检测报告（JSON数组 → 完整URL数组）
        $factoryReports = [];
        if (isset($productData['factory']['inspection_reports'])) {
            $raw = $productData['factory']['inspection_reports'];
            $arr = is_array($raw) ? $raw : (json_decode($raw ?: '[]', true) ?: []);
            $factoryReports = array_map(function ($v) { return $v ? cdnurl($v, true) : ''; }, $arr);
        }
        $productData['factory_inspection_reports'] = $factoryReports;

        // 产品级检测报告（单文件，已通过模型getter转为完整URL）
        // inspection_report_url 字段已自动转换
        $rawReportUrl = $product->getData('inspection_report_url');
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
        $id = $this->request->get('id/d', 0);
        if (!$id) {
            $this->error('缺少产品ID');
        }

        $product = Product::where('id', $id)
            ->where('status', Product::STATUS_ON)
            ->find();
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

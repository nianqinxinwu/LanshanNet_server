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
            $q->withField(['id', 'company_name', 'fulfill_rate', 'province', 'industry']);
        }])->where('fa_jj_product.id', $id)->find();

        if (!$product) {
            $this->error('产品不存在');
        }

        $productData = $product->toArray();
        // 服务端计算预估产能佣金: price * stock * commission_rate / 100
        $price = floatval($product['price'] ?? 0);
        $stock = intval($product['stock'] ?? 0);
        $commissionRate = floatval($product['commission_rate'] ?? 0);
        $productData['estimated_commission'] = round($price * $stock * $commissionRate / 100, 2);

        $this->success('查询成功', $productData);
    }
}

<?php

namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\jj\Cart;
use app\common\model\jj\Product;

/**
 * 居间人端 - 商品清单(购物车)
 */
class JjCart extends XiluxcApi
{
    protected $noNeedLogin = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 加入清单
     *
     * @ApiMethod (POST)
     * @ApiParams (name="product_id", type="int", required=true, description="产品ID")
     * @ApiParams (name="quantity", type="int", required=false, description="数量，默认1")
     */
    public function add()
    {
        $userId = $this->auth->id;
        $productId = $this->request->post('product_id/d', 0);
        $quantity = $this->request->post('quantity/d', 1);

        if (!$productId) {
            $this->error('缺少产品ID');
        }
        if ($quantity < 1) {
            $quantity = 1;
        }

        $product = Product::where('id', $productId)->where('status', Product::STATUS_ON)->find();
        if (!$product) {
            $this->error('产品不存在或已下架');
        }
        if ($product['stock'] < $quantity) {
            $this->error('库存不足');
        }

        // 查找是否已有记录
        $cart = Cart::where('user_id', $userId)->where('product_id', $productId)->find();
        if ($cart) {
            $newQty = $cart['quantity'] + $quantity;
            if ($newQty > $product['stock']) {
                $newQty = $product['stock'];
            }
            $cart->save(['quantity' => $newQty]);
        } else {
            $cart = Cart::create([
                'user_id'    => $userId,
                'product_id' => $productId,
                'quantity'   => $quantity,
            ]);
        }

        $totalCount = Cart::where('user_id', $userId)->count();

        $this->success('已加入清单', [
            'cart_id'     => $cart['id'],
            'total_count' => $totalCount,
        ]);
    }

    /**
     * 清单列表
     *
     * @ApiMethod (GET)
     */
    public function lists()
    {
        $userId = $this->auth->id;

        $list = Cart::with(['product' => function ($q) {
            $q->withField(['id', 'name', 'cover_image', 'price', 'unit', 'commission_rate', 'deposit_rate', 'stock', 'status', 'factory_id']);
        }])->where('fa_jj_cart.user_id', $userId)
            ->order('fa_jj_cart.id', 'desc')
            ->select();

        $items = [];
        $summaryContractAmount = '0.00';
        $summaryCommission = '0.00';
        $summaryDeposit = '0.00';
        $totalCount = 0;

        foreach ($list as $row) {
            $product = $row['product'];
            $available = $product && $product['status'] == Product::STATUS_ON;
            $price = $product ? floatval($product['price']) : 0;
            $commissionRate = 10; // 固定佣金比例10%，不再读取产品配置
            $depositRate = 1; // 固定保证金比例：佣金总额的1%
            $qty = intval($row['quantity']);

            $contractAmount = bcmul($price, $qty, 2);
            $commission = bcmul($contractAmount, bcdiv($commissionRate, 100, 4), 2);
            $deposit = bcmul($commission, bcdiv($depositRate, 100, 4), 2);

            // 获取工厂名称
            $factoryName = '';
            if ($product && $product['factory_id']) {
                $factory = \app\common\model\jj\Factory::where('id', $product['factory_id'])->field('company_name')->find();
                $factoryName = $factory ? $factory['company_name'] : '';
            }

            $items[] = [
                'cart_id'         => $row['id'],
                'product_id'      => $row['product_id'],
                'quantity'        => $qty,
                'name'            => $product ? $product['name'] : '已删除商品',
                'cover_image'     => $product ? $product['cover_image'] : '',
                'price'           => $price,
                'unit'            => $product ? $product['unit'] : '',
                'commission_rate' => $commissionRate,
                'deposit_rate'    => $depositRate,
                'stock'           => $product ? intval($product['stock']) : 0,
                'factory_name'    => $factoryName,
                'available'       => $available,
                'contract_amount' => $contractAmount,
                'commission'      => $commission,
                'deposit'         => $deposit,
            ];

            if ($available) {
                $summaryContractAmount = bcadd($summaryContractAmount, $contractAmount, 2);
                $summaryCommission = bcadd($summaryCommission, $commission, 2);
                $summaryDeposit = bcadd($summaryDeposit, $deposit, 2);
                $totalCount += $qty;
            }
        }

        $this->success('查询成功', [
            'list'    => $items,
            'summary' => [
                'total_count'     => $totalCount,
                'contract_amount' => $summaryContractAmount,
                'commission'      => $summaryCommission,
                'deposit'         => $summaryDeposit,
            ],
        ]);
    }

    /**
     * 修改数量
     *
     * @ApiMethod (POST)
     * @ApiParams (name="cart_id", type="int", required=true, description="清单记录ID")
     * @ApiParams (name="quantity", type="int", required=true, description="新数量")
     */
    public function update()
    {
        $userId = $this->auth->id;
        $cartId = $this->request->post('cart_id/d', 0);
        $quantity = $this->request->post('quantity/d', 1);

        $cart = Cart::where('id', $cartId)->where('user_id', $userId)->find();
        if (!$cart) {
            $this->error('记录不存在');
        }

        if ($quantity < 1) {
            $this->error('数量不能小于1');
        }

        $product = Product::where('id', $cart['product_id'])->find();
        if ($product && $quantity > $product['stock']) {
            $this->error('超出库存数量，当前库存：' . $product['stock']);
        }

        $cart->save(['quantity' => $quantity]);

        $this->success('修改成功', ['cart_id' => $cartId, 'quantity' => $quantity]);
    }

    /**
     * 删除单条
     *
     * @ApiMethod (POST)
     * @ApiParams (name="cart_id", type="int", required=true, description="清单记录ID")
     */
    public function remove()
    {
        $userId = $this->auth->id;
        $cartId = $this->request->post('cart_id/d', 0);

        $cart = Cart::where('id', $cartId)->where('user_id', $userId)->find();
        if (!$cart) {
            $this->error('记录不存在');
        }

        $cart->delete();

        $this->success('删除成功');
    }

    /**
     * 清空清单
     *
     * @ApiMethod (POST)
     */
    public function clear()
    {
        $userId = $this->auth->id;
        Cart::where('user_id', $userId)->delete();
        $this->success('已清空');
    }

    /**
     * 清单数量
     *
     * @ApiMethod (GET)
     */
    public function count()
    {
        $userId = $this->auth->id;
        $count = Cart::where('user_id', $userId)->count();
        $this->success('查询成功', ['count' => $count]);
    }
}

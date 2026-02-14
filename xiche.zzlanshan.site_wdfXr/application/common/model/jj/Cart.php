<?php

namespace app\common\model\jj;

use think\Model;

/**
 * 居间人商品清单(购物车)模型
 */
class Cart extends Model
{
    protected $name = 'jj_cart';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id', [], 'left')->setEagerlyType(0);
    }
}

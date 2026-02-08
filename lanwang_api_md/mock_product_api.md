# 商品池模块 接口文档（Mock）

> 更新日期：2026-02-08
>
> 状态：**待后端实现**，前端商品池列表（`jj-products-content` 组件）与商品详情页（`jj_product_detail`）当前使用硬编码 Mock 数据，后端就绪后替换为以下接口。

---

## 1. 商品分类列表

**接口地址：** `/api/xiluxc.product/category_list`

**请求方式：** GET

**需要登录：** 是（请求头携带 Token）

**说明：** 获取商品池的品类列表，用于分类 Tab 筛选。

### 请求头

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| Token | string | 是 | 登录凭证 |

### 请求参数

无

### 返回参数

**成功响应：**

```json
{
    "code": 1,
    "msg": "查询成功",
    "time": "1707300000",
    "data": {
        "list": [
            { "id": 1, "name": "建材" },
            { "id": 2, "name": "化工" },
            { "id": 3, "name": "机械" },
            { "id": 4, "name": "电子" },
            { "id": 5, "name": "纺织" }
        ]
    }
}
```

**list 项字段说明：**

| 字段 | 类型 | 说明 |
|------|------|------|
| id | int | 分类ID |
| name | string | 分类名称 |

> 前端在列表头部固定追加 `{ id: 0, name: '全部' }` 选项，不由接口返回。

---

## 2. 商品列表（商品池）

**接口地址：** `/api/xiluxc.product/list`

**请求方式：** GET

**需要登录：** 是（请求头携带 Token）

**说明：** 分页获取已上架商品列表，支持关键词搜索、分类筛选、排序。对应前端 `jj-products-content` 组件。

### 请求头

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| Token | string | 是 | 登录凭证 |

### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | int | 否 | 页码，默认 1 |
| page_size | int | 否 | 每页条数，默认 10 |
| keyword | string | 否 | 搜索关键词，匹配商品名称或工厂名称 |
| category_id | int | 否 | 分类ID，0 或不传表示全部 |
| sort | string | 否 | 排序方式：`default`=综合排序（默认），`commission_desc`=佣金最高，`price_asc`=价格最低 |

### 返回参数

**成功响应：**

```json
{
    "code": 1,
    "msg": "查询成功",
    "time": "1707300000",
    "data": {
        "total": 12,
        "per_page": 10,
        "current_page": 1,
        "last_page": 2,
        "list": [
            {
                "id": 1,
                "name": "高强度螺纹钢 HRB400",
                "categoryId": 1,
                "categoryName": "建材",
                "coverImage": "https://cdn.example.com/product/1.jpg",
                "price": 4280.00,
                "unit": "吨",
                "commission": 3.5,
                "stock": 500,
                "factoryName": "鑫达钢铁有限公司",
                "factoryRate": 96,
                "status": 1
            }
        ]
    }
}
```

**data 字段说明：**

| 字段 | 类型 | 说明 |
|------|------|------|
| total | int | 总记录数 |
| per_page | int | 每页条数 |
| current_page | int | 当前页码 |
| last_page | int | 最后一页页码 |
| list | array | 商品列表 |

**list 项字段说明：**

| 字段 | 类型 | 说明 |
|------|------|------|
| id | int | 商品ID |
| name | string | 商品名称 |
| categoryId | int | 分类ID |
| categoryName | string | 分类名称 |
| coverImage | string | 商品封面图URL |
| price | float | 单价（元） |
| unit | string | 计量单位（吨/台/个/套/米） |
| commission | float | 佣金比例（%） |
| stock | int | 库存数量 |
| factoryName | string | 工厂企业名称 |
| factoryRate | int | 工厂历史履约率（0-100） |
| status | int | 商品状态：1=已上架，2=可排产 |

**失败响应：**

```json
{
    "code": 0,
    "msg": "错误信息",
    "time": "1707300000",
    "data": null
}
```

### 排序规则说明

| sort 值 | 排序逻辑 |
|---------|---------|
| `default` | 后端综合排序（建议按上架时间倒序 + 工厂履约率权重） |
| `commission_desc` | 佣金比例降序 |
| `price_asc` | 单价升序 |

---

## 3. 商品详情

**接口地址：** `/api/xiluxc.product/detail`

**请求方式：** GET

**需要登录：** 是（请求头携带 Token）

**说明：** 获取单个商品完整信息，包括商品参数、工厂信息、产能佣金估算。对应前端 `jj_product_detail` 页面。

### 请求头

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| Token | string | 是 | 登录凭证 |

### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | int | 是 | 商品ID |

### 返回参数

**成功响应：**

```json
{
    "code": 1,
    "msg": "查询成功",
    "time": "1707300000",
    "data": {
        "id": 1,
        "name": "高强度螺纹钢 HRB400",
        "categoryId": 1,
        "categoryName": "建材",
        "coverImage": "https://cdn.example.com/product/1.jpg",
        "images": [
            "https://cdn.example.com/product/1_1.jpg",
            "https://cdn.example.com/product/1_2.jpg"
        ],
        "price": 4280.00,
        "unit": "吨",
        "commission": 3.5,
        "stock": 500,
        "craftStandard": "GB/T 1499.2-2018",
        "description": "",
        "estimatedDailyCommission": 74900.00,
        "reportUrl": "https://cdn.example.com/report/1.pdf",
        "factoryId": 101,
        "factoryName": "鑫达钢铁有限公司",
        "factoryRate": 96,
        "factoryOrderCount": 328,
        "status": 1
    }
}
```

**data 字段说明：**

| 字段 | 类型 | 说明 |
|------|------|------|
| id | int | 商品ID |
| name | string | 商品名称 |
| categoryId | int | 分类ID |
| categoryName | string | 分类名称 |
| coverImage | string | 商品封面图URL |
| images | array\<string\> | 商品图片列表（轮播图） |
| price | float | 单价（元） |
| unit | string | 计量单位 |
| commission | float | 佣金比例（%） |
| stock | int | 库存数量 |
| craftStandard | string | 工艺标准 |
| description | string | 商品描述（富文本，可为空） |
| estimatedDailyCommission | float | 厂家预估当日产能佣金（元），由厂家自行填写 |
| reportUrl | string | 检测报告文件URL（PDF），为空表示暂无报告 |
| factoryId | int | 工厂用户ID |
| factoryName | string | 工厂企业名称 |
| factoryRate | int | 工厂历史履约率（0-100） |
| factoryOrderCount | int | 工厂历史成交单数 |
| status | int | 商品状态：1=已上架，2=可排产 |

**失败响应：**

```json
{
    "code": 0,
    "msg": "错误信息",
    "time": "1707300000",
    "data": null
}
```

| msg | 说明 |
|-----|------|
| 商品不存在 | id 无效 |
| 商品已下架 | status != 1 |

### 产能佣金展示规则

前端展示区域标题为"厂家预估产能佣金"，金额取 `estimatedDailyCommission` 直接显示。需附带黄色警示条：

> 此为厂家自行估算值，平台未核实。接单前请务必与厂家确认最终锁定佣金！

如 `estimatedDailyCommission` 为 0 或空，则前端使用本地公式估算：`price × stock × commission / 100`，并标注"预估"。

---

## 4. 查看检测报告

**接口地址：** `/api/xiluxc.product/report`

**请求方式：** GET

**需要登录：** 是（请求头携带 Token）

**说明：** 获取商品关联的检测报告文件URL。前端使用 `uni.openDocument()` 或跳转 webview 预览 PDF。

### 请求头

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| Token | string | 是 | 登录凭证 |

### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| product_id | int | 是 | 商品ID |

### 返回参数

**成功响应：**

```json
{
    "code": 1,
    "msg": "查询成功",
    "time": "1707300000",
    "data": {
        "report_url": "https://cdn.example.com/report/1.pdf",
        "report_name": "HRB400检测报告.pdf",
        "upload_time": "2026-01-15 10:30:00"
    }
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| report_url | string | 检测报告文件URL（PDF） |
| report_name | string | 文件名 |
| upload_time | string | 上传时间 |

**失败响应：**

```json
{
    "code": 0,
    "msg": "暂无检测报告",
    "time": "1707300000",
    "data": null
}
```

---

## 5. 联系厂家（获取 IM 会话信息）

**接口地址：** `/api/xiluxc.product/contact_factory`

**请求方式：** GET

**需要登录：** 是（请求头携带 Token）

**说明：** 居间人点击"联系厂家"按钮后调用，获取与工厂的 IM 会话入口信息。

### 请求头

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| Token | string | 是 | 登录凭证 |

### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| product_id | int | 是 | 商品ID（用于定位工厂） |

### 返回参数

**成功响应：**

```json
{
    "code": 1,
    "msg": "查询成功",
    "time": "1707300000",
    "data": {
        "factory_user_id": 101,
        "factory_name": "鑫达钢铁有限公司",
        "conversation_id": "conv_jj_1_fc_101",
        "product_id": 1,
        "product_name": "高强度螺纹钢 HRB400"
    }
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| factory_user_id | int | 工厂用户ID |
| factory_name | string | 工厂企业名称 |
| conversation_id | string | IM 会话ID，前端据此跳转聊天页 |
| product_id | int | 商品ID（用于会话上下文） |
| product_name | string | 商品名称 |

**失败响应：**

```json
{
    "code": 0,
    "msg": "工厂暂不可联系",
    "time": "1707300000",
    "data": null
}
```

---

## 附录：商品状态枚举

| status | 含义 | 说明 |
|--------|------|------|
| 1 | 已上架 | 有现货，可正常接单 |
| 2 | 可排产 | 无现货但可排期生产，居间人接单前需弹窗确认 |
| 3 | 已下架 | 暂停销售，不在商品池显示 |

## 附录：前端调用入口

| 接口 | 调用位置 | 调用时机 |
|------|----------|----------|
| `product/category_list` | `jj-products-content` 组件 | 组件 mounted |
| `product/list` | `jj-products-content` 组件 | mounted / 切换分类 / 搜索 / 切换排序 / 上拉加载更多 |
| `product/detail` | `jj_product_detail` 页面 | 页面 onLoad |
| `product/report` | `jj_product_detail` 页面 | 点击"查看"检测报告 |
| `product/contact_factory` | `jj_product_detail` 页面 | 点击"联系厂家"按钮 |

## 附录：当前 Mock 数据结构

前端硬编码的 Mock 数据使用以下字段（与接口返回保持一致，便于无缝替换）：

```javascript
{
    id: 1,
    name: '高强度螺纹钢 HRB400',
    categoryId: 1,
    categoryName: '建材',
    coverImage: '/static/images/icon_upload_logo.png',
    price: 4280.00,
    unit: '吨',
    commission: 3.5,
    stock: 500,
    factoryName: '鑫达钢铁有限公司',
    factoryRate: 96,
    craftStandard: 'GB/T 1499.2-2018',
    status: 1
}
```

Mock 数据分布在 3 个文件中（后端就绪后需同步删除）：

| 文件 | 变量 | 用途 |
|------|------|------|
| `components/jj-products-content/jj-products-content.vue` | `MOCK_PRODUCTS` | 商品列表（12条） |
| `pages/jj/jj_product_detail/jj_product_detail.vue` | `MOCK_DETAIL_MAP` | 商品详情（12条） |
| `pages/jj/jj_buyer_form/jj_buyer_form.vue` | `MOCK_PRODUCT_MAP` | 接单页商品摘要（12条） |

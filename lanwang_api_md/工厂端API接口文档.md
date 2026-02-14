# 工厂端 API 接口文档

> 供前端对接使用，所有接口已通过测试验证。

## 通用说明

### Base URL

```
http://192.168.1.149:8000/api/
```

### 请求头

所有接口（除特殊标注外）均需在 Header 中携带 `token`：

```
token: <用户登录后获取的token>
```

### 响应格式

所有接口统一返回 JSON：

```json
{
  "code": 1,         // 1=成功, 0=失败
  "msg": "操作信息",
  "time": "1770873096",
  "data": { ... }    // 具体数据
}
```

### 订单状态码映射

| status | 含义 | status_text |
|--------|------|-------------|
| 0 | 待确认 | 待确认 |
| 1 | 待缴保证金 | 待缴保证金 |
| 2 | 已缴保证金/待锁定佣金 | 已缴保证金 |
| 3 | 待上传合同 | 待上传合同 |
| 4 | 履约执行中 | 履约执行中 |
| 5 | 待结算 | 待结算 |
| 6 | 已结算 | 已结算 |
| 7 | 已取消 | 已取消 |
| 8 | 已逾期 | 已逾期 |

### 前端调用方式

```javascript
// GET 请求
this.$core.get('xiluxc.fc_product/index', { page: 1, pagesize: 10 })

// POST 请求
this.$core.post('xiluxc.fc_product/add', { name: '产品A', price: 100, unit: '件', stock: 500, commission_rate: 5 })
```

---

## 一、企业认证模块（FcFactory）

### 1.1 提交企业认证

- **接口**: `xiluxc.fc_factory/submit_cert`
- **方法**: POST
- **说明**: 首次提交或认证被驳回后重新提交

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| company_name | string | 是 | 企业名称 |
| province | string | 否 | 所在省份 |
| industry | string | 否 | 行业 |
| contact_name | string | 是 | 联系人 |
| contact_phone | string | 是 | 联系电话（11位手机号） |
| business_license | string | 是 | 营业执照图片URL |
| inspection_reports | string | 否 | 检测报告URL，JSON数组字符串，如 `["url1","url2"]` |
| trade_certs | string | 否 | 贸易证书URL，JSON数组字符串 |
| factory_photos | string | 否 | 工厂照片URL，JSON数组字符串 |

**成功响应**:

```json
{
  "code": 1,
  "msg": "提交成功，等待平台审核",
  "data": {
    "factory_id": 6,
    "status": 0,
    "status_text": "待审核"
  }
}
```

**失败场景**:
- `请填写完整的企业信息` — 必填字段缺失
- `请输入正确的手机号` — 手机号格式错误
- `企业已认证通过，无需重复提交` — 已认证状态

---

### 1.2 认证状态查询

- **接口**: `xiluxc.fc_factory/cert_status`
- **方法**: GET
- **说明**: 查询当前用户的企业认证状态，前端可据此决定跳转到认证页还是主页

**请求参数**: 无

**成功响应（未提交认证）**:

```json
{
  "code": 1,
  "msg": "查询成功",
  "data": {
    "certified": false,
    "factory_id": 0,
    "status": -1,
    "status_text": "未提交"
  }
}
```

**成功响应（已提交）**:

```json
{
  "code": 1,
  "msg": "查询成功",
  "data": {
    "certified": true,
    "factory_id": 6,
    "status": 1,
    "status_text": "正常",
    "company_name": "测试工厂有限公司",
    "province": "广东",
    "industry": "纺织",
    "contact_name": "张三",
    "contact_phone": "13800138000",
    "business_license": "http://xxx/uploads/test_license.jpg",
    "inspection_reports": [],
    "trade_certs": [],
    "factory_photos": [],
    "fulfill_rate": "100.00"
  }
}
```

**status 值说明**:
- `-1` — 未提交
- `0` — 待审核
- `1` — 正常（已通过）
- `2` — 冻结

---

### 1.3 更新工厂基本信息

- **接口**: `xiluxc.fc_factory/update_info`
- **方法**: POST
- **说明**: 认证通过后，可修改部分信息（联系人、联系电话、省份、行业、工厂照片）

**请求参数**（传哪个更新哪个）:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| contact_name | string | 否 | 联系人 |
| contact_phone | string | 否 | 联系电话 |
| province | string | 否 | 所在省份 |
| industry | string | 否 | 行业 |
| factory_photos | string | 否 | 工厂照片URL（JSON数组字符串） |

**成功响应**:

```json
{ "code": 1, "msg": "更新成功", "data": null }
```

**失败场景**:
- `企业未认证通过，无法修改信息` — 尚未认证
- `无可更新的字段` — 没有传任何参数

---

## 二、产品管理模块（FcProduct）

> 前置条件：企业必须认证通过（status=1），否则所有接口返回 `企业认证未通过，无法管理产品`

### 2.1 产品列表

- **接口**: `xiluxc.fc_product/index`
- **方法**: GET

**请求参数**:

| 参数 | 类型 | 必填 | 默认 | 说明 |
|------|------|------|------|------|
| status | int | 否 | 全部 | 状态筛选：0=下架，1=上架 |
| keyword | string | 否 | - | 按产品名称搜索 |
| page | int | 否 | 1 | 页码 |
| pagesize | int | 否 | 10 | 每页数量 |

**成功响应**:

```json
{
  "code": 1,
  "msg": "查询成功",
  "data": {
    "list": {
      "total": 1,
      "per_page": 10,
      "current_page": 1,
      "last_page": 1,
      "data": [
        {
          "id": 13,
          "factory_id": 6,
          "category_id": 0,
          "category_name": "",
          "name": "测试产品A",
          "cover_image": "",
          "price": "100.00",
          "unit": "件",
          "commission_rate": "5.00",
          "deposit_rate": "10.00",
          "stock": 500,
          "craft_standard": "",
          "inspection_report_url": "",
          "estimated_daily_capacity": "0.00",
          "sort": 0,
          "status": 1,
          "createtime": 1770872732,
          "updatetime": 1770872816,
          "status_text": "上架"
        }
      ]
    },
    "stats": {
      "total": 1,
      "on": 1,
      "off": 0
    }
  }
}
```

---

### 2.2 新增产品

- **接口**: `xiluxc.fc_product/add`
- **方法**: POST
- **说明**: 新增产品默认为下架状态

**请求参数**:

| 参数 | 类型 | 必填 | 默认 | 说明 |
|------|------|------|------|------|
| name | string | 是 | - | 产品名称 |
| price | float | 是 | - | 单价（必须>0） |
| unit | string | 是 | - | 单位（件/箱/吨等） |
| stock | int | 是 | 0 | 库存数量 |
| commission_rate | float | 是 | 0 | 佣金比例（0-100） |
| category_id | int | 否 | 0 | 品类ID |
| category_name | string | 否 | - | 品类名称 |
| cover_image | string | 否 | - | 封面图URL |
| deposit_rate | float | 否 | 10 | 保证金比例（%） |
| craft_standard | string | 否 | - | 工艺标准（富文本HTML） |
| inspection_report_url | string | 否 | - | 检测报告URL |
| estimated_daily_capacity | float | 否 | 0 | 日产能佣金（元） |

**成功响应**:

```json
{
  "code": 1,
  "msg": "添加成功",
  "data": { "product_id": 13 }
}
```

---

### 2.3 编辑产品

- **接口**: `xiluxc.fc_product/edit`
- **方法**: POST
- **说明**: 传哪个字段更新哪个

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 产品ID |
| name | string | 否 | 产品名称 |
| price | float | 否 | 单价（必须>0） |
| unit | string | 否 | 单位 |
| stock | int | 否 | 库存数量 |
| commission_rate | float | 否 | 佣金比例（0-100） |
| category_id | int | 否 | 品类ID |
| category_name | string | 否 | 品类名称 |
| cover_image | string | 否 | 封面图URL |
| deposit_rate | float | 否 | 保证金比例（%） |
| craft_standard | string | 否 | 工艺标准 |
| inspection_report_url | string | 否 | 检测报告URL |
| estimated_daily_capacity | float | 否 | 日产能佣金 |

**成功响应**:

```json
{ "code": 1, "msg": "更新成功", "data": null }
```

---

### 2.4 删除产品

- **接口**: `xiluxc.fc_product/del`
- **方法**: POST
- **说明**: 仅下架状态可删除

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 产品ID |

**成功响应**:

```json
{ "code": 1, "msg": "删除成功", "data": null }
```

**失败场景**:
- `上架中的产品不能删除，请先下架`

---

### 2.5 上架产品

- **接口**: `xiluxc.fc_product/on_shelf`
- **方法**: POST

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 产品ID |

**成功响应**:

```json
{ "code": 1, "msg": "上架成功", "data": null }
```

---

### 2.6 下架产品

- **接口**: `xiluxc.fc_product/off_shelf`
- **方法**: POST

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 产品ID |

**成功响应**:

```json
{ "code": 1, "msg": "下架成功", "data": null }
```

---

### 2.7 产品详情

- **接口**: `xiluxc.fc_product/detail`
- **方法**: GET

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 产品ID |

**成功响应**:

```json
{
  "code": 1,
  "msg": "查询成功",
  "data": {
    "id": 13,
    "factory_id": 6,
    "category_id": 0,
    "category_name": "",
    "name": "测试产品A",
    "cover_image": "",
    "price": "100.00",
    "unit": "件",
    "commission_rate": "5.00",
    "deposit_rate": "10.00",
    "stock": 500,
    "craft_standard": "",
    "inspection_report_url": "",
    "estimated_daily_capacity": "0.00",
    "sort": 0,
    "status": 0,
    "createtime": 1770872732,
    "updatetime": 1770872732,
    "status_text": "下架",
    "estimated_commission": 2500
  }
}
```

> `estimated_commission` = price * stock * commission_rate / 100（服务端计算）

---

## 三、订单管理模块（FcOrder）

### 3.1 订单列表

- **接口**: `xiluxc.fc_order/index`
- **方法**: GET

**请求参数**:

| 参数 | 类型 | 必填 | 默认 | 说明 |
|------|------|------|------|------|
| status | int | 否 | 全部 | 按状态筛选（0-8） |
| keyword | string | 否 | - | 搜索订单号/产品名/买家公司 |
| page | int | 否 | 1 | 页码 |
| pagesize | int | 否 | 10 | 每页数量 |

**成功响应**:

```json
{
  "code": 1,
  "msg": "查询成功",
  "data": {
    "list": {
      "total": 1,
      "per_page": 10,
      "current_page": 1,
      "last_page": 1,
      "data": [
        {
          "id": 10,
          "order_sn": "JJ202602100001",
          "agent_id": 1,
          "factory_id": 6,
          "product_id": 13,
          "bid_id": 0,
          "product_name": "测试产品A",
          "cover_image": "",
          "quantity": 100,
          "unit_price": "100.00",
          "total_amount": "10000.00",
          "commission_rate": "5.00",
          "commission_amount": "500.00",
          "deposit_rate": "10.00",
          "deposit_amount": "1000.00",
          "factory_bonus": "0.00",
          "logistics_rebate": "0.00",
          "commission_locked": 0,
          "commission_lock_time": null,
          "buyer_company": "测试公司",
          "buyer_address": "测试地址",
          "buyer_contact": "张三",
          "buyer_phone": "13800138000",
          "contract_upload_hours": 24,
          "execution_hours": 72,
          "status": 0,
          "createtime": 1770873061,
          "updatetime": 1770873061,
          "status_text": "待确认"
        }
      ]
    },
    "stats": {
      "pending": 1,
      "deposit": 0,
      "contract": 0,
      "executing": 0,
      "settling": 0,
      "settled": 0
    }
  }
}
```

> `stats` 用于顶部 Tab 角标数字

---

### 3.2 订单详情

- **接口**: `xiluxc.fc_order/detail`
- **方法**: GET

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| order_id | int | 是 | 订单ID |

**成功响应**:

```json
{
  "code": 1,
  "msg": "查询成功",
  "data": {
    "order_id": 10,
    "order_sn": "JJ202602100001",
    "product_name": "测试产品A",
    "cover_image": "",
    "buyer_company": "测试公司",
    "buyer_contact": "张三",
    "buyer_phone": "13800138000",
    "buyer_address": "测试地址",
    "unit_price": "100.00",
    "quantity": 100,
    "total_amount": "10000.00",
    "commission_rate": "5.00",
    "commission_amount": "500.00",
    "deposit_rate": "10.00",
    "deposit_amount": "1000.00",
    "status": 0,
    "status_text": "待确认",
    "create_time": "2026-02-12 13:11",
    "lock_deadline": 0,
    "contract_upload_hours": 24,
    "execution_hours": 72,
    "deposit_info": null,
    "contract_info": null,
    "logistics_info": null
  }
}
```

**字段说明**:

| 字段 | 说明 |
|------|------|
| `lock_deadline` | 佣金锁定截止时间戳。仅 status=2 时有值，前端需做倒计时（超时需警告） |
| `contract_upload_hours` | 合同上传期限（小时） |
| `execution_hours` | 履约执行期限（小时） |
| `deposit_info` | 保证金信息。pay_status: 0=待支付, 1=已支付, 2=已退还 |
| `contract_info` | 合同信息。status: 0=待上传, 1=已上传, 3=已审核通过 |
| `logistics_info` | 物流信息。status: 0=待发货, 1=已发货, 2=运输中, 3=已签收 |

---

### 3.3 确认接单

- **接口**: `xiluxc.fc_order/confirm`
- **方法**: POST
- **说明**: 将订单从 `待确认(0)` → `待缴保证金(1)`

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| order_id | int | 是 | 订单ID |

**成功响应**:

```json
{ "code": 1, "msg": "确认成功，等待居间人缴纳保证金", "data": null }
```

**失败场景**:
- `当前订单状态不允许确认` — 订单非待确认状态

---

### 3.4 锁定佣金

- **接口**: `xiluxc.fc_order/lock_commission`
- **方法**: POST
- **说明**: 将订单从 `已缴保证金(2)` → `待上传合同(3)`。保证金缴纳后 **2小时内** 必须锁定佣金

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| order_id | int | 是 | 订单ID |

**成功响应**:

```json
{ "code": 1, "msg": "佣金已锁定，等待居间人上传合同", "data": null }
```

**失败场景**:
- `当前订单状态不允许锁定佣金` — 订单非 status=2
- `居间人尚未缴纳保证金` — 保证金记录未找到或未支付

---

### 3.5 审核合同

- **接口**: `xiluxc.fc_order/review_contract`
- **方法**: POST
- **说明**: 居间人上传合同后，工厂审核。通过则进入履约期(4)，驳回则退回待上传合同(3)

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| order_id | int | 是 | 订单ID |
| action | string | 是 | `approve`=通过, `reject`=驳回 |
| reject_reason | string | 驳回时必填 | 驳回原因 |

**成功响应（通过）**:

```json
{ "code": 1, "msg": "合同审核通过", "data": null }
```

**成功响应（驳回）**:

```json
{ "code": 1, "msg": "合同已驳回", "data": null }
```

**失败场景**:
- `合同尚未上传或状态异常` — 合同未上传
- `请填写驳回原因` — 驳回时未填原因

---

### 3.6 确认发货

- **接口**: `xiluxc.fc_order/confirm_shipment`
- **方法**: POST
- **说明**: 仅在 `履约执行中(4)` 状态下可发货

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| order_id | int | 是 | 订单ID |
| company_name | string | 是 | 物流公司名称 |
| tracking_no | string | 是 | 运单号 |

**成功响应**:

```json
{ "code": 1, "msg": "发货成功", "data": null }
```

**失败场景**:
- `请填写物流公司和运单号` — 参数缺失
- `当前订单状态不允许发货` — 订单非 status=4

---

### 3.7 同意放款（触发结算）

- **接口**: `xiluxc.fc_order/release_payment`
- **方法**: POST
- **说明**: 货物签收后，工厂确认放款。订单变为 `已结算(6)`，自动创建佣金结算记录并退还保证金

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| order_id | int | 是 | 订单ID |

**成功响应**:

```json
{ "code": 1, "msg": "放款成功，订单已结算", "data": null }
```

**失败场景**:
- `当前订单状态不允许放款` — 订单非 status=4 或 5
- `货物尚未签收，不能放款` — 物流未签收

---

### 3.8 待办事项统计

- **接口**: `xiluxc.fc_order/todo_count`
- **方法**: GET
- **说明**: 用于首页/导航栏红点数字提示

**请求参数**: 无

**成功响应**:

```json
{
  "code": 1,
  "msg": "查询成功",
  "data": {
    "pending_confirm": 0,
    "pending_lock": 0,
    "pending_review": 0,
    "pending_ship": 0,
    "pending_release": 0
  }
}
```

| 字段 | 说明 |
|------|------|
| pending_confirm | 待确认接单 |
| pending_lock | 待锁定佣金 |
| pending_review | 待审核合同 |
| pending_ship | 待发货 |
| pending_release | 待放款 |

---

## 四、竞标报价模块（FcBid）

### 4.1 竞标邀请列表

- **接口**: `xiluxc.fc_bid/invitation_list`
- **方法**: GET
- **说明**: 获取居间人发送给本工厂的竞标邀请

**请求参数**:

| 参数 | 类型 | 必填 | 默认 | 说明 |
|------|------|------|------|------|
| status | string | 否 | all | 筛选：`all` / `pending` / `quoted` / `expired` |
| page | int | 否 | 1 | 页码 |
| pagesize | int | 否 | 10 | 每页数量 |

**成功响应**:

```json
{
  "code": 1,
  "msg": "查询成功",
  "data": {
    "list": {
      "total": 0,
      "per_page": 10,
      "current_page": 1,
      "last_page": 0,
      "data": [
        {
          "id": 1,
          "bid_id": 1,
          "factory_id": 6,
          "status": 0,
          "status_text": "待报价",
          "contract_price": null,
          "delivery_date": null,
          "commission_amount": null,
          "remark": "",
          "modify_count": 0,
          "bid": {
            "id": 1,
            "bid_sn": "BD20260210001",
            "agent_id": 1,
            "category_name": "纺织品",
            "quantity": 1000,
            "unit": "件",
            "expect_delivery": "2026-03-01",
            "target_commission": "5000.00",
            "factory_count": 3,
            "expire_time": 1771000000,
            "status": 0,
            "createtime": 1770900000
          },
          "remain_time": 86400
        }
      ]
    },
    "stats": {
      "pending": 0,
      "quoted": 0
    }
  }
}
```

> `remain_time` = 竞标剩余秒数（前端格式化为"剩余xx时xx分"）

---

### 4.2 竞标详情

- **接口**: `xiluxc.fc_bid/detail`
- **方法**: GET

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| bid_id | int | 是 | 竞标ID |

**成功响应**:

```json
{
  "code": 1,
  "msg": "查询成功",
  "data": {
    "bid_id": 1,
    "bid_sn": "BD20260210001",
    "category_name": "纺织品",
    "quantity": 1000,
    "unit": "件",
    "expect_delivery": "2026-03-01",
    "target_commission": "5000.00",
    "factory_count": 3,
    "remain_time": 86400,
    "bid_status": 0,
    "bid_status_text": "竞标中",
    "create_time": "2026-02-10 10:00",
    "buyer_info": {
      "company_name": "买方公司",
      "address": "深圳市南山区",
      "contact_name": "李四",
      "contact_phone": "13900139000"
    },
    "my_quote": {
      "id": 1,
      "status": 0,
      "status_text": "待报价",
      "contract_price": "",
      "delivery_date": "",
      "commission_amount": "",
      "remark": "",
      "selected": 0,
      "quote_time": ""
    }
  }
}
```

**`my_quote.status` 说明**:
- `0` — 待报价（pending）
- `1` — 已报价（quoted）
- `2` — 已过期（expired）

**`my_quote.selected`**:
- `0` — 未中标
- `1` — 已中标

---

### 4.3 提交报价

- **接口**: `xiluxc.fc_bid/submit_quote`
- **方法**: POST
- **说明**: 首次提交报价。佣金总额 = contract_price * quantity（服务端自动计算）

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| bid_id | int | 是 | 竞标ID |
| contract_price | float | 是 | 合同价格（单价，必须>0） |
| delivery_date | string | 是 | 交货日期，格式 `YYYY-MM-DD` |
| remark | string | 否 | 备注 |

**成功响应**:

```json
{
  "code": 1,
  "msg": "报价提交成功",
  "data": {
    "commission_amount": "100000.00"
  }
}
```

**失败场景**:
- `合同价格必须大于0`
- `请填写交货日期`
- `已提交过报价` — 重复提交
- `竞标已结束或不存在`
- `竞标已过期`

---

### 4.4 修改报价

- **接口**: `xiluxc.fc_bid/update_quote`
- **方法**: POST
- **说明**: 72小时内仅可修改1次

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| bid_id | int | 是 | 竞标ID |
| contract_price | float | 是 | 新的合同价格（必须>0） |
| delivery_date | string | 否 | 新的交货日期，不传则保持原值 |
| remark | string | 否 | 备注 |

**成功响应**:

```json
{ "code": 1, "msg": "报价修改成功", "data": null }
```

**失败场景**:
- `尚未提交报价，无法修改`
- `报价仅可修改一次`
- `已超过72小时修改期限`
- `竞标已结束`

---

## 五、居间人筛选模块（FcAgent）

### 5.1 居间人列表

- **接口**: `xiluxc.fc_agent/list`
- **方法**: GET
- **说明**: 浏览平台上已认证的居间人

**请求参数**:

| 参数 | 类型 | 必填 | 默认 | 说明 |
|------|------|------|------|------|
| min_score | int | 否 | 0 | 最低信用评分筛选 |
| keyword | string | 否 | - | 按姓名搜索 |
| sort | string | 否 | score | 排序：`score`=信用分, `deals`=成交量, `rate`=履约率 |
| page | int | 否 | 1 | 页码 |
| pagesize | int | 否 | 10 | 每页数量 |

**成功响应**:

```json
{
  "code": 1,
  "msg": "查询成功",
  "data": {
    "total": 1,
    "per_page": 10,
    "current_page": 1,
    "last_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 10,
        "real_name": "张三",
        "agent_type": "个人",
        "agent_level": 2,
        "credit_score": 85,
        "total_deals": 12,
        "total_revenue": "2107.50",
        "fulfill_rate": "98.50",
        "hexagon_data": {
          "teamScale": 45,
          "growthRate": 68,
          "activeLevel": 72,
          "creditScore": 85,
          "dealAbility": 78,
          "fulfillRate": 90
        },
        "createtime": 1770650853,
        "user": {
          "id": 10,
          "nickname": "张三",
          "avatar": "http://xxx/uploads/avatar.jpg"
        },
        "status_text": "正常"
      }
    ]
  }
}
```

---

### 5.2 居间人详情

- **接口**: `xiluxc.fc_agent/detail`
- **方法**: GET

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| agent_id | int | 是 | 居间人 profile ID |

**成功响应**:

```json
{
  "code": 1,
  "msg": "查询成功",
  "data": {
    "id": 1,
    "user_id": 10,
    "nickname": "张三",
    "avatar": "http://xxx/uploads/avatar.jpg",
    "real_name": "张三",
    "agent_type": "个人",
    "agent_level": 2,
    "credit_score": 85,
    "total_deals": 12,
    "total_revenue": "2107.50",
    "fulfill_rate": "98.50",
    "hexagon_data": {
      "teamScale": 45,
      "growthRate": 68,
      "activeLevel": 72,
      "creditScore": 85,
      "dealAbility": 78,
      "fulfillRate": 90
    },
    "join_time": "2026-02-09"
  }
}
```

**`hexagon_data` 六维数据说明**（用于雷达图）:

| 字段 | 含义 | 范围 |
|------|------|------|
| teamScale | 团队规模 | 0-100 |
| growthRate | 成长速率 | 0-100 |
| activeLevel | 活跃度 | 0-100 |
| creditScore | 信用评分 | 0-100 |
| dealAbility | 成交能力 | 0-100 |
| fulfillRate | 履约率 | 0-100 |

---

## 六、数据看板模块（FcDashboard）

### 6.1 总览数据

- **接口**: `xiluxc.fc_dashboard/overview`
- **方法**: GET
- **说明**: 首页数据看板核心数据

**请求参数**: 无

**成功响应**:

```json
{
  "code": 1,
  "msg": "查询成功",
  "data": {
    "month_orders": 5,
    "month_amount": 50000.00,
    "total_orders": 28,
    "conversion_rate": 14.0,
    "avg_response_hours": 1.2,
    "product_count": 10,
    "fulfill_rate": 98.5,
    "pending_confirm": 2,
    "pending_lock": 1,
    "pending_ship": 0
  }
}
```

| 字段 | 说明 |
|------|------|
| month_orders | 本月交易量（订单数） |
| month_amount | 本月交易额（元） |
| total_orders | 总订单数 |
| conversion_rate | 接单转化率（%） |
| avg_response_hours | 平均响应时间（小时） |
| product_count | 产品总数 |
| fulfill_rate | 履约率（%） |
| pending_confirm | 待确认接单数 |
| pending_lock | 待锁定佣金数 |
| pending_ship | 待发货数 |

---

### 6.2 产品统计

- **接口**: `xiluxc.fc_dashboard/product_stats`
- **方法**: GET

**请求参数**: 无

**成功响应**:

```json
{
  "code": 1,
  "msg": "查询成功",
  "data": {
    "top10_products": [
      {
        "product_name": "产品A",
        "product_id": 13,
        "order_count": 8,
        "total_amount": "80000.00"
      }
    ],
    "on_shelf_count": 6,
    "off_shelf_count": 4,
    "bid_response_rate": 75.0,
    "total_bid_invites": 20,
    "quoted_count": 15
  }
}
```

| 字段 | 说明 |
|------|------|
| top10_products | TOP10畅销产品（按订单数降序） |
| on_shelf_count | 上架产品数 |
| off_shelf_count | 下架产品数 |
| bid_response_rate | 竞标响应率（%） |
| total_bid_invites | 收到的竞标邀请总数 |
| quoted_count | 已报价的竞标数 |

---

### 6.3 财务统计

- **接口**: `xiluxc.fc_dashboard/finance_stats`
- **方法**: GET

**请求参数**: 无

**成功响应**:

```json
{
  "code": 1,
  "msg": "查询成功",
  "data": {
    "locked_commission": 15000.00,
    "settled_commission": 8500.00,
    "default_deposit": 2000.00,
    "month_settled": 3200.00
  }
}
```

| 字段 | 说明 |
|------|------|
| locked_commission | 已锁定佣金总额（托管中） |
| settled_commission | 已结算佣金总额 |
| default_deposit | 违约保证金收入（逾期订单扣款） |
| month_settled | 本月已结算金额 |

---

## 接口总览速查表

| # | 模块 | 接口路径 | 方法 | 说明 |
|---|------|---------|------|------|
| 1 | 认证 | `xiluxc.fc_factory/submit_cert` | POST | 提交企业认证 |
| 2 | 认证 | `xiluxc.fc_factory/cert_status` | GET | 查询认证状态 |
| 3 | 认证 | `xiluxc.fc_factory/update_info` | POST | 更新工厂信息 |
| 4 | 产品 | `xiluxc.fc_product/index` | GET | 产品列表 |
| 5 | 产品 | `xiluxc.fc_product/add` | POST | 新增产品 |
| 6 | 产品 | `xiluxc.fc_product/edit` | POST | 编辑产品 |
| 7 | 产品 | `xiluxc.fc_product/del` | POST | 删除产品 |
| 8 | 产品 | `xiluxc.fc_product/on_shelf` | POST | 上架产品 |
| 9 | 产品 | `xiluxc.fc_product/off_shelf` | POST | 下架产品 |
| 10 | 产品 | `xiluxc.fc_product/detail` | GET | 产品详情 |
| 11 | 订单 | `xiluxc.fc_order/index` | GET | 订单列表 |
| 12 | 订单 | `xiluxc.fc_order/detail` | GET | 订单详情 |
| 13 | 订单 | `xiluxc.fc_order/confirm` | POST | 确认接单 |
| 14 | 订单 | `xiluxc.fc_order/lock_commission` | POST | 锁定佣金 |
| 15 | 订单 | `xiluxc.fc_order/review_contract` | POST | 审核合同 |
| 16 | 订单 | `xiluxc.fc_order/confirm_shipment` | POST | 确认发货 |
| 17 | 订单 | `xiluxc.fc_order/release_payment` | POST | 同意放款 |
| 18 | 订单 | `xiluxc.fc_order/todo_count` | GET | 待办统计 |
| 19 | 竞标 | `xiluxc.fc_bid/invitation_list` | GET | 竞标邀请列表 |
| 20 | 竞标 | `xiluxc.fc_bid/detail` | GET | 竞标详情 |
| 21 | 竞标 | `xiluxc.fc_bid/submit_quote` | POST | 提交报价 |
| 22 | 竞标 | `xiluxc.fc_bid/update_quote` | POST | 修改报价 |
| 23 | 居间人 | `xiluxc.fc_agent/list` | GET | 居间人列表 |
| 24 | 居间人 | `xiluxc.fc_agent/detail` | GET | 居间人详情 |
| 25 | 看板 | `xiluxc.fc_dashboard/overview` | GET | 总览数据 |
| 26 | 看板 | `xiluxc.fc_dashboard/product_stats` | GET | 产品统计 |
| 27 | 看板 | `xiluxc.fc_dashboard/finance_stats` | GET | 财务统计 |

---

## 订单生命周期流程

```
居间人下单 → [待确认(0)]
                ↓ 工厂调 confirm
           [待缴保证金(1)]
                ↓ 居间人缴纳保证金
           [已缴保证金(2)]
                ↓ 工厂调 lock_commission（2小时内）
           [待上传合同(3)]
                ↓ 居间人上传合同
                ↓ 工厂调 review_contract
                   ├── approve → [履约执行中(4)]
                   └── reject  → 退回 [待上传合同(3)]
                ↓
           [履约执行中(4)]
                ↓ 工厂调 confirm_shipment
                ↓ 买家签收
                ↓ 工厂调 release_payment
           [已结算(6)]
                ↓ 自动创建佣金记录 + 退还保证金
```

# 居间人接单 · 保证金 · 合同履约 接口文档（Mock）

> 更新日期：2026-02-08
>
> 状态：**待后端实现**，前端已按本文档完成对接，当前使用 Mock 占位。

---

## 1. 提交接单

**接口地址：** `/api/xiluxc.jj_order/submit`

**请求方式：** POST

**需要登录：** 是（请求头携带 Token）

**说明：** 居间人填写买家信息后提交接单，后端创建订单并返回订单ID及厂家预设的保证金比例、倒计时时长等参数。

### 请求头

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| Token | string | 是 | 登录凭证 |

### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| productId | int | 是 | 商品ID |
| companyName | string | 是 | 买家企业名称 |
| address | string | 是 | 收货地址 |
| contactName | string | 是 | 联系人姓名 |
| contactPhone | string | 是 | 联系电话（11位手机号） |
| creditCode | string | 是 | 统一社会信用代码（18位） |
| taxNumber | string | 否 | 税务登记证号 |

### 返回参数

**成功响应：**

```json
{
    "code": 1,
    "msg": "接单提交成功",
    "time": "1707300000",
    "data": {
        "order_id": 10001,
        "order_sn": "JJ202602080001",
        "commission_amount": 14980.00,
        "deposit_rate": 10,
        "contract_upload_hours": 24,
        "execution_hours": 72
    }
}
```

**data 字段说明：**

| 字段 | 类型 | 说明 |
|------|------|------|
| order_id | int | 订单ID |
| order_sn | string | 订单编号 |
| commission_amount | float | 锁定佣金金额（元），由厂家在后台设置 |
| deposit_rate | int | 保证金比例（%），由厂家设置，如 10 表示佣金的 10% |
| contract_upload_hours | int | 合同上传倒计时（小时），由厂家设置 |
| execution_hours | int | 履约执行倒计时（小时），由厂家设置 |

**失败响应：**

```json
{
    "code": 0,
    "msg": "错误信息",
    "time": "1707300000",
    "data": null
}
```

**错误信息说明：**

| msg | 说明 |
|-----|------|
| Please login first | 未登录或 Token 无效 |
| 请输入企业名称 | companyName 为空 |
| 请输入收货地址 | address 为空 |
| 请输入联系人姓名 | contactName 为空 |
| 请输入正确的手机号 | contactPhone 格式不正确 |
| 请输入统一社会信用代码 | creditCode 为空或格式不正确 |
| 商品不存在或已下架 | productId 无效 |
| 该商品暂不可接单 | 商品状态异常 |

---

## 2. 查询保证金详情

**接口地址：** `/api/xiluxc.jj_order/deposit_info`

**请求方式：** GET

**需要登录：** 是（请求头携带 Token）

**说明：** 进入保证金支付页时调用，获取订单的保证金金额、商品信息等。

### 请求头

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| Token | string | 是 | 登录凭证 |

### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| order_id | int | 是 | 订单ID |

### 返回参数

**成功响应：**

```json
{
    "code": 1,
    "msg": "查询成功",
    "time": "1707300000",
    "data": {
        "order_id": 10001,
        "order_sn": "JJ202602080001",
        "productName": "高强度螺纹钢 HRB400",
        "coverImage": "https://cdn.example.com/product/1.jpg",
        "companyName": "杭州某某贸易有限公司",
        "commission": 3.5,
        "commissionAmount": 14980.00,
        "depositRate": 10,
        "depositAmount": 1498.00,
        "depositStatus": 0,
        "contractUploadHours": 24,
        "executionHours": 72
    }
}
```

**data 字段说明：**

| 字段 | 类型 | 说明 |
|------|------|------|
| order_id | int | 订单ID |
| order_sn | string | 订单编号 |
| productName | string | 商品名称 |
| coverImage | string | 商品封面图URL |
| companyName | string | 买家企业名称 |
| commission | float | 佣金比例（%） |
| commissionAmount | float | 锁定佣金金额（元） |
| depositRate | int | 保证金比例（%） |
| depositAmount | float | 应缴保证金金额（元） = commissionAmount × depositRate / 100 |
| depositStatus | int | 保证金状态：0=未缴纳，1=已冻结，2=已退还，3=已划转工厂 |
| contractUploadHours | int | 合同上传倒计时时长（小时） |
| executionHours | int | 履约执行倒计时时长（小时） |

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
| 订单不存在 | order_id 无效 |
| 无权查看该订单 | 订单不属于当前登录用户 |

---

## 3. 缴纳保证金（发起支付）

**接口地址：** `/api/xiluxc.jj_order/pay_deposit`

**请求方式：** POST

**需要登录：** 是（请求头携带 Token）

**说明：** 居间人确认《履约保证协议》后发起保证金支付。后端创建支付订单，返回微信/支付宝支付参数。支付成功后保证金冻结至托管账户A。

### 请求头

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| Token | string | 是 | 登录凭证 |

### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| order_id | int | 是 | 订单ID |
| pay_type | string | 是 | 支付方式：`wxpay`=微信支付，`alipay`=支付宝支付 |

### 返回参数

**成功响应（微信支付）：**

```json
{
    "code": 1,
    "msg": "下单成功",
    "time": "1707300000",
    "data": {
        "appId": "wxxxxxxxxxxx",
        "timeStamp": "1707300000",
        "nonceStr": "xxxxxxxxxxxxxxxx",
        "package": "prepay_id=wx20260208xxxxxx",
        "signType": "MD5",
        "paySign": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
    }
}
```

**微信支付 data 字段说明：**

| 字段 | 类型 | 说明 |
|------|------|------|
| appId | string | 微信 AppID |
| timeStamp | string | 时间戳 |
| nonceStr | string | 随机字符串 |
| package | string | 预支付交易会话标识，格式 `prepay_id=xxx` |
| signType | string | 签名方式，固定 `MD5` |
| paySign | string | 支付签名 |

> 前端收到后调用 `uni.requestPayment()`（小程序）或 JSSDK `chooseWXPay()`（H5）唤起支付。

**成功响应（支付宝 — 预留）：**

```json
{
    "code": 1,
    "msg": "下单成功",
    "time": "1707300000",
    "data": {
        "order_string": "alipay_sdk=xxxx&app_id=xxxx&sign=xxxx..."
    }
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| order_string | string | 支付宝订单信息字符串，前端传入支付宝 SDK |

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
| 订单不存在 | order_id 无效 |
| 保证金已缴纳 | 重复支付 |
| 订单状态异常 | 订单不在"待缴纳保证金"状态 |
| 支付下单失败 | 第三方支付平台返回错误 |

### 支付回调（后端内部）

支付成功后后端需完成：
1. 更新订单状态为"保证金已冻结"
2. 保证金金额记入托管账户A
3. 记录合同上传倒计时开始时间（`deposit_paid_at`）
4. 推送通知给工厂："居间人已缴纳保证金，请锁定佣金"

---

## 4. 查询合同与履约状态

**接口地址：** `/api/xiluxc.jj_order/contract_status`

**请求方式：** GET

**需要登录：** 是（请求头携带 Token）

**说明：** 进入合同上传/履约执行页时调用，返回当前阶段、倒计时截止时间戳、已上传合同信息等。

### 请求头

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| Token | string | 是 | 登录凭证 |

### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| order_id | int | 是 | 订单ID |

### 返回参数

**成功响应（合同上传阶段）：**

```json
{
    "code": 1,
    "msg": "查询成功",
    "time": "1707300000",
    "data": {
        "stage": "upload",
        "deadline_timestamp": 1707386400,
        "contract_upload_hours": 24,
        "execution_hours": 72,
        "contract_url": "",
        "contract_name": "",
        "productName": "高强度螺纹钢 HRB400",
        "coverImage": "https://cdn.example.com/product/1.jpg",
        "companyName": "杭州某某贸易有限公司"
    }
}
```

**成功响应（履约执行阶段）：**

```json
{
    "code": 1,
    "msg": "查询成功",
    "time": "1707300000",
    "data": {
        "stage": "execution",
        "deadline_timestamp": 1707645600,
        "contract_upload_hours": 24,
        "execution_hours": 72,
        "contract_url": "https://cdn.example.com/contract/10001.pdf",
        "contract_name": "买卖合同_杭州某某.pdf",
        "productName": "高强度螺纹钢 HRB400",
        "coverImage": "https://cdn.example.com/product/1.jpg",
        "companyName": "杭州某某贸易有限公司"
    }
}
```

**成功响应（已过期/已违约）：**

```json
{
    "code": 1,
    "msg": "查询成功",
    "time": "1707300000",
    "data": {
        "stage": "expired",
        "deadline_timestamp": 0,
        "contract_url": "",
        "contract_name": ""
    }
}
```

**data 字段说明：**

| 字段 | 类型 | 说明 |
|------|------|------|
| stage | string | 当前阶段：`upload`=合同上传期，`execution`=履约执行期，`expired`=已逾期违约，`settled`=已结算 |
| deadline_timestamp | int | 当前阶段截止时间（Unix 时间戳，秒）。前端用 `deadline - now` 计算剩余秒数做倒计时 |
| contract_upload_hours | int | 合同上传倒计时总时长（小时），由厂家设置 |
| execution_hours | int | 履约执行倒计时总时长（小时），由厂家设置 |
| contract_url | string | 已上传合同文件URL，未上传时为空 |
| contract_name | string | 已上传合同文件名 |
| productName | string | 商品名称 |
| coverImage | string | 商品封面图URL |
| companyName | string | 买家企业名称 |

**stage 状态流转：**

```
upload（合同上传期）
  ├─ 上传成功 → execution（履约执行期）
  └─ 倒计时归零 → expired（已违约，保证金划转工厂）

execution（履约执行期）
  ├─ 工厂点击【同意放款】→ settled（已结算）
  └─ 倒计时归零 → settled（自动结算）
```

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
| 订单不存在 | order_id 无效 |
| 保证金未缴纳 | 尚未完成保证金支付 |

---

## 5. 提交合同

**接口地址：** `/api/xiluxc.jj_order/submit_contract`

**请求方式：** POST

**需要登录：** 是（请求头携带 Token）

**说明：** 居间人上传正式买卖合同（PDF）。上传成功后进入履约执行期，开始执行倒计时。

### 请求头

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| Token | string | 是 | 登录凭证 |

### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| order_id | int | 是 | 订单ID |
| contract_url | string | 是 | 合同文件URL（通过 `/common/upload` 上传后获得） |
| contract_name | string | 是 | 合同文件名 |

### 文件要求

| 项目 | 要求 |
|------|------|
| 格式 | 仅 PDF |
| 大小 | ≤ 10MB |
| 内容 | 真实买卖合同，需包含最终锁定佣金金额 |

### 返回参数

**成功响应：**

```json
{
    "code": 1,
    "msg": "合同提交成功",
    "time": "1707300000",
    "data": {
        "execution_hours": 72,
        "deadline_timestamp": 1707645600
    }
}
```

**data 字段说明：**

| 字段 | 类型 | 说明 |
|------|------|------|
| execution_hours | int | 履约执行倒计时时长（小时） |
| deadline_timestamp | int | 履约执行截止时间戳（秒），前端据此开始新一轮倒计时 |

### 后端逻辑

1. 校验订单状态是否为"合同上传期"
2. 校验合同上传是否在倒计时内
3. 保存合同URL至订单记录
4. 订单状态变更为"履约执行中"
5. 计算履约执行截止时间 = `now + execution_hours * 3600`
6. 推送通知给工厂："居间人已上传合同，请审核"

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
| 订单不存在 | order_id 无效 |
| 合同上传已逾期 | 超过合同上传倒计时，保证金已划转 |
| 合同已提交 | 重复提交 |
| 请上传合同文件 | contract_url 为空 |

---

## 6. 催促工厂

**接口地址：** `/api/xiluxc.jj_order/urge_factory`

**请求方式：** POST

**需要登录：** 是（请求头携带 Token）

**说明：** 履约执行期内，居间人向工厂发送催促通知（站内信）。

### 请求头

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| Token | string | 是 | 登录凭证 |

### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| order_id | int | 是 | 订单ID |

### 返回参数

**成功响应：**

```json
{
    "code": 1,
    "msg": "催促通知已发送",
    "time": "1707300000",
    "data": null
}
```

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
| 订单不存在 | order_id 无效 |
| 订单不在履约期 | 当前阶段不是 execution |
| 操作过于频繁 | 短时间内重复催促（建议限制每小时1次） |

---

## 附录：订单状态枚举

| 状态值 | 状态名 | 说明 |
|--------|--------|------|
| 0 | 待确认 | 居间人已提交接单，等待工厂确认 |
| 1 | 待缴纳保证金 | 工厂已确认，等待居间人缴纳保证金 |
| 2 | 保证金已冻结 | 保证金已缴纳并冻结至托管账户A |
| 3 | 合同上传中 | 等待居间人上传正式买卖合同（倒计时中） |
| 4 | 履约执行中 | 合同已上传，进入履约执行倒计时 |
| 5 | 待放款 | 履约完成，等待工厂点击同意放款 |
| 6 | 已结算 | 佣金已结算，保证金已退还 |
| 7 | 已违约 | 逾期未上传合同，保证金已划转工厂 |
| 8 | 已取消 | 订单已取消 |

## 附录：前端调用入口

| 接口 | 调用页面 | 调用时机 |
|------|----------|----------|
| `jj_order/submit` | `pages/jj/jj_buyer_form` | 点击"提交接单"按钮 |
| `jj_order/deposit_info` | `pages/jj/jj_deposit` | 页面 onLoad |
| `jj_order/pay_deposit` | `pages/jj/jj_deposit` | 点击"缴纳保证金"按钮 |
| `jj_order/contract_status` | `pages/jj/jj_contract` | 页面 onLoad |
| `jj_order/submit_contract` | `pages/jj/jj_contract` | 点击"提交合同"按钮 |
| `jj_order/urge_factory` | `pages/jj/jj_contract` | 点击"催促工厂"按钮 |

## 附录：倒计时逻辑说明

两阶段倒计时均由厂家在后台设置时长，前端通过 `deadline_timestamp` 做精准倒计时：

```
剩余秒数 = deadline_timestamp - Math.floor(Date.now() / 1000)
```

**合同上传倒计时：**
- 起点：保证金支付成功时刻
- 时长：`contract_upload_hours` 小时（厂家设置）
- 到期动作：后端自动将保证金划转给工厂，订单状态变为"已违约"

**履约执行倒计时：**
- 起点：合同提交成功时刻
- 时长：`execution_hours` 小时（厂家设置）
- 到期动作：后端自动结算佣金，退还保证金

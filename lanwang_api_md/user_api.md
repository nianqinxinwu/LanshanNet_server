# 用户接口文档

> 更新日期：2026-02-08

## 1. 手机验证码登录

**接口地址：** `/api/xiluxc.user/mobilelogin`

**请求方式：** POST

### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| mobile | string | 是 | 手机号（1开头11位） |
| code | string | 是 | 短信验证码 |
| roleType | int | 是 | 角色类型：1=居间人，2=工厂 |
| puser_id | int | 否 | 推荐人用户ID |

### 返回参数

**成功响应：**

```json
{
    "code": 1,
    "msg": "Logged in successful",
    "time": "1707300000",
    "data": {
        "userinfo": {
            "id": 1,
            "username": "13800138000",
            "nickname": "138****8000",
            "mobile": "13800138000",
            "avatar": "",
            "score": 0,
            "roleType": 1,
            "agentType": 0,
            "businessLicense": "",
            "isIntermediary": 0,
            "isFactory": 0,
            "idCardFrontImgUrl": "",
            "idCardBackImgUrl": "",
            "token": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
            "user_id": 1,
            "createtime": 1707300000,
            "expiretime": 1709892000,
            "expires_in": 2592000
        }
    }
}
```

**userinfo 字段说明：**

| 字段 | 类型 | 说明 |
|------|------|------|
| id | int | 用户ID |
| username | string | 用户名 |
| nickname | string | 昵称 |
| mobile | string | 手机号 |
| avatar | string | 头像地址 |
| score | int | 积分 |
| roleType | int | 角色类型：0=未指定，1=居间人，2=工厂 |
| agentType | int | 居间人类别：0=未指定，1=个人居间人，2=公司居间人 |
| businessLicense | string | 营业执照图片地址 |
| isIntermediary | int | 是否是居间人：0=不是，1=是 |
| isFactory | int | 是否是工厂身份：0=不是，1=是 |
| idCardFrontImgUrl | string | 身份证正面照片地址 |
| idCardBackImgUrl | string | 身份证背面照片地址 |
| token | string | 登录凭证，后续请求需携带 |
| user_id | int | 用户ID |
| createtime | int | Token创建时间（时间戳） |
| expiretime | int | Token过期时间（时间戳） |
| expires_in | int | Token剩余有效时间（秒） |

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
| Invalid parameters | 手机号或验证码为空 |
| 手机号格式错误 | 手机号格式不正确 |
| 请选择角色类型 | roleType不是1或2 |
| 验证码错误 | 短信验证码不正确 |
| 账号已被锁定 | 账号状态异常 |

---

## 2. 获取用户信息

**接口地址：** `/api/xiluxc.user/info`

**请求方式：** GET

**需要登录：** 是（请求头携带 Token）

### 请求头

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| Token | string | 是 | 登录接口返回的 token |

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
        "id": 1,
        "username": "13800138000",
        "nickname": "138****8000",
        "mobile": "138****8000",
        "avatar": "",
        "email": "",
        "gender": 0,
        "birthday": null,
        "score": 0,
        "money": "0.00",
        "logintime": 1707300000,
        "loginfailure": 0,
        "createtime": 1707300000,
        "updatetime": 1707300000,
        "status": "normal",
        "roleType": 1,
        "agentType": 0,
        "businessLicense": "",
        "isIntermediary": 0,
        "isFactory": 0,
        "idCardFrontImgUrl": "",
        "idCardBackImgUrl": "",
        "verification": "",
        "points": 0,
        "account": {
            "total_money": "0.00",
            "money": "0.00",
            "points": 0,
            "withdraw_money": "0.00",
            "total_message": 0
        },
        "team_count": 0,
        "coupon_count": 0,
        "user_message": 0,
        "total_shop_money": "0.00",
        "verifier_status": false,
        "package_count": 0,
        "unuse_order_count": 0,
        "uncomment_order_count": 0,
        "finished_order_count": 0
    }
}
```

**字段说明：**

| 字段 | 类型 | 说明 |
|------|------|------|
| id | int | 用户ID |
| username | string | 用户名 |
| nickname | string | 昵称 |
| mobile | string | 手机号（中间4位脱敏） |
| avatar | string | 头像地址 |
| email | string | 邮箱 |
| gender | int | 性别：0=未知，1=男，2=女 |
| birthday | string | 生日 |
| score | int | 积分 |
| money | string | 余额 |
| logintime | int | 最后登录时间（时间戳） |
| createtime | int | 注册时间（时间戳） |
| updatetime | int | 更新时间（时间戳） |
| status | string | 账号状态：normal=正常 |
| roleType | int | 角色类型：0=未指定，1=居间人，2=工厂 |
| agentType | int | 居间人类别：0=未指定，1=个人居间人，2=公司居间人 |
| businessLicense | string | 营业执照图片地址 |
| isIntermediary | int | 是否是居间人：0=不是，1=是 |
| isFactory | int | 是否是工厂身份：0=不是，1=是 |
| idCardFrontImgUrl | string | 身份证正面照片地址 |
| idCardBackImgUrl | string | 身份证背面照片地址 |
| points | int | 积分 |
| account | object | 账户信息 |
| account.total_money | string | 累计收入 |
| account.money | string | 可用余额 |
| account.points | int | 积分 |
| account.withdraw_money | string | 已提现金额 |
| account.total_message | int | 未读消息数 |
| team_count | int | 团队人数 |
| coupon_count | int | 优惠券数量 |
| user_message | int | 未读消息数 |
| total_shop_money | string | 门店总余额 |
| verifier_status | bool | 是否有核销权限 |
| package_count | int | 套餐数量 |
| unuse_order_count | int | 待服务订单数 |
| uncomment_order_count | int | 待评价订单数 |
| finished_order_count | int | 已完成订单数 |

**失败响应：**

```json
{
    "code": 0,
    "msg": "Please login first",
    "time": "1707300000",
    "data": null
}
```

---

## 3. 居间人认证

> 新增于：2026-02-08

**接口地址：** `/api/xiluxc.user/intermediaryAuth`

**请求方式：** POST

**需要登录：** 是（请求头携带 Token）

### 请求头

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| Token | string | 是 | 登录接口返回的 token |

### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| agentType | int | 是 | 居间人类别：1=个人居间人，2=公司居间人 |
| idCardFrontImgUrl | string | 是 | 身份证正面照片地址 |
| idCardBackImgUrl | string | 是 | 身份证背面照片地址 |
| businessLicense | string | 公司居间人必填 | 企业营业执照图片地址（agentType=2 时必传） |

### 业务逻辑

- **个人居间人**（agentType=1）：需上传身份证正反面照片，认证后更新 `agentType=1, isIntermediary=1`
- **公司居间人**（agentType=2）：需上传身份证正反面照片 + 企业营业执照，认证后更新 `agentType=2, isIntermediary=1, businessLicense=图片地址`

### 返回参数

**成功响应：**

```json
{
    "code": 1,
    "msg": "居间人认证成功",
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

**错误信息说明：**

| msg | 说明 |
|-----|------|
| Please login first | 未登录或 Token 无效 |
| 请选择居间人类别 | agentType 不是 1 或 2 |
| 请上传身份证正反面照片 | idCardFrontImgUrl 或 idCardBackImgUrl 为空 |
| 请上传企业营业执照 | 公司居间人未传 businessLicense |
| 认证失败，请重试 | 数据库更新失败 |

# 居间人端 API 接口清单

> 所有接口基础路径: `/api/`
> 认证方式: Header 中传递 `token` 字段
> 返回格式: `{"code": 1, "msg": "成功", "time": "timestamp", "data": {...}}`
> code=1 表示成功, code=0 表示失败
> 测试账号: 手机号 17200475197, 验证码白名单 123456

---

## 一、用户认证模块

### 1. 手机号登录
- **路由**: `POST xiluxc.user/mobilelogin`
- **控制器**: `User::mobilelogin`
- **无需登录**: 是
- **测试状态**: ✅ 已通过
- **关联表**: `fa_user`, `fa_user_token`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | mobile | string | 是 | 手机号 |
  | code | string | 是 | 短信验证码（调试白名单: 123456） |
  | roleType | int | 是 | 角色类型: 1=居间人, 2=工厂 |
- **出参**:
  ```json
  {"code":1,"msg":"","data":{"userinfo":{"id":10,"nickname":"重识世界","mobile":"17200475197","avatar":"...","token":"...","roleType":1}}}
  ```

### 2. 居间人认证
- **路由**: `POST xiluxc.user/intermediaryAuth`
- **控制器**: `User::intermediaryAuth`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_user`, `fa_jj_agent_profile`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | phoneNumber | string | 是 | 手机号（须与当前登录用户手机号一致） |
  | smsCode | string | 是 | 短信验证码（调试白名单: 123456） |
  | agentType | int | 是 | 居间人类型: 1=个人, 2=企业 |
  | idCardFrontImgUrl | string | 是 | 身份证正面图片URL |
  | idCardBackImgUrl | string | 是 | 身份证反面图片URL |
  | businessLicense | string | 否 | 营业执照（企业类型必填） |
- **验证逻辑**:
  1. 校验 phoneNumber 与当前登录用户手机号（fa_user.mobile）一致
  2. 校验 smsCode 短信验证码（事件名: jj_auth）
  3. 校验居间人类别、身份证照片等必填项
- **出参**:
  ```json
  {"code":1,"msg":"居间人认证成功","data":null}
  ```

---

## 二、首页与个人中心模块

### 3. 首页仪表盘
- **路由**: `GET xiluxc.jj_agent/dashboard`
- **控制器**: `JjAgent::dashboard`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_jj_agent_profile`, `fa_jj_order`, `fa_jj_pk_pool`, `fa_jj_pk_rank`
- **入参**: 无
- **出参**:
  ```json
  {
    "code": 1,
    "data": {
      "profile": {"real_name": "重识世界", "credit_score": 85, "agent_level": 2, "invite_code": "LwhHnN6M"},
      "income": {"pending_revenue": "998.50", "settled_revenue": "1109.00", "total_revenue": "2107.50"},
      "hexagonData": {"dealAbility": 78, "creditScore": 85, "activeLevel": 72, "fulfillRate": 90, "teamScale": 45, "growthRate": 68},
      "todo": {"pending_deposit": 0, "pending_contract": 1, "executing": 1, "pending_settle": 0},
      "pkPool": {"total_amount", "start_date", "end_date", "my_rank", "my_revenue", "my_prize"} | null
    }
  }
  ```

### 4. 居间人信息
- **路由**: `GET xiluxc.jj_agent/profile`
- **控制器**: `JjAgent::profile`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_user`, `fa_jj_agent_profile`
- **入参**: 无
- **出参**:
  ```json
  {
    "code": 1,
    "data": {
      "nickname": "重识世界",
      "avatar": "http://...",
      "mobile": "172****5197",
      "roleType": 1,
      "agentType": 1,
      "isIntermediary": 1,
      "agent_profile": {"id":1, "user_id":10, "real_name":"重识世界", "credit_score":85, "agent_level":2, "hexagon_data":{...}, "total_revenue":"2107.50", "settled_revenue":"1109.00", "pending_revenue":"998.50", "invite_code":"LwhHnN6M", "status":1}
    }
  }
  ```

---

## 三、产品池模块

### 5. 产品列表
- **路由**: `GET xiluxc.jj_product/index`
- **控制器**: `JjProduct::index`
- **测试状态**: ✅ 已通过（返回12条产品）
- **关联表**: `fa_jj_product`, `fa_jj_factory`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | page | int | 否 | 页码，默认1 |
  | pagesize | int | 否 | 每页数量，默认10 |
  | category_id | int | 否 | 品类ID筛选 |
  | keyword | string | 否 | 关键词搜索 |
- **出参**:
  ```json
  {
    "code": 1,
    "data": {
      "total": 12, "per_page": 10, "current_page": 1, "last_page": 2,
      "data": [
        {"id":12, "factory_id":5, "category_name":"茶叶", "name":"大红袍岩茶150g罐装", "price":"128.00", "unit":"罐", "commission_rate":"15.00", "deposit_rate":"10.00", "stock":800, "craft_standard":"非遗工艺/特级", "status":1, "factory":{"id":5,"company_name":"福建茗香茶业有限公司","fulfill_rate":"91.00"}, "status_text":"上架"}
      ]
    }
  }
  ```

### 6. 产品详情
- **路由**: `GET xiluxc.jj_product/detail`
- **控制器**: `JjProduct::detail`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_jj_product`, `fa_jj_factory`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | id | int | 是 | 产品ID |
- **出参**: 产品完整信息，含工厂信息（company_name, fulfill_rate, province, industry）

---

## 四、订单模块

### 7. 订单列表
- **路由**: `GET xiluxc.jj_order/index`
- **控制器**: `JjOrder::index`
- **测试状态**: ✅ 已通过（返回6条订单，覆盖全状态）
- **关联表**: `fa_jj_order`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | status | int | 否 | 状态筛选: 0-8 |
  | page | int | 否 | 页码 |
  | pagesize | int | 否 | 每页数量 |
- **出参**:
  ```json
  {
    "code": 1,
    "data": {
      "total": 6, "per_page": 10, "current_page": 1,
      "data": [
        {"id":6, "order_sn":"JJ20260201006", "product_name":"植物精华洗衣液2kg", "cover_image":"...", "quantity":1000, "unit_price":"19.80", "total_amount":"19800.00", "commission_rate":"4.50", "commission_amount":"891.00", "status":7, "status_text":"已取消"}
      ]
    }
  }
  ```

### 8. 提交接单
- **路由**: `POST xiluxc.jj_order/submit`
- **控制器**: `JjOrder::submit`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_jj_order`, `fa_jj_product`, `fa_jj_deposit`, `fa_jj_order_log`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | productId | int | 是 | 产品ID |
  | companyName | string | 是 | 买家企业名称 |
  | address | string | 是 | 收货地址 |
  | contactName | string | 是 | 联系人 |
  | contactPhone | string | 是 | 联系电话 |
  | creditCode | string | 是 | 统一社会信用代码 |
  | taxNumber | string | 否 | 税务登记号 |
- **出参**:
  ```json
  {"code":1,"msg":"提交成功","data":{"order_id":"7","order_sn":"JJ202602100056387320","commission_amount":"2.70","deposit_rate":"10.00","contract_upload_hours":24,"execution_hours":72}}
  ```
- **业务逻辑**: 创建订单(待缴保证金) → 创建保证金记录 → 写入订单日志

### 9. 保证金信息
- **路由**: `GET xiluxc.jj_order/deposit_info`
- **控制器**: `JjOrder::deposit_info`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_jj_order`, `fa_jj_deposit`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | order_id | int | 是 | 订单ID |
- **出参**:
  ```json
  {"code":1,"data":{"order_id":5,"productName":"铁观音清香型250g礼盒","coverImage":"...","companyName":"广州茶韵阁贸易公司","commission":"12.00","commissionAmount":"528.00","depositRate":"10.00","depositAmount":"52.80","contractUploadHours":24,"executionHours":72}}
  ```

### 10. 缴纳保证金
- **路由**: `POST xiluxc.jj_order/pay_deposit`
- **控制器**: `JjOrder::pay_deposit`
- **测试状态**: ✅ 已通过（需订单状态为1-待缴保证金）
- **关联表**: `fa_jj_order`, `fa_jj_deposit`, `fa_jj_contract`, `fa_jj_order_log`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | order_id | int | 是 | 订单ID |
  | pay_type | int | 是 | 支付方式: 1=微信, 2=支付宝 |
- **出参**: `{"code":1,"data":{"order_id":...}}`
- **业务逻辑**: 更新保证金为已支付 → 订单状态变为待上传合同 → 创建合同记录(含上传截止时间) → 写入订单日志
- **注**: 支付功能为模拟，需对接实际支付渠道

### 11. 订单详情
- **路由**: `GET xiluxc.jj_order/detail`
- **控制器**: `JjOrder::detail`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_jj_order`, `fa_jj_deposit`, `fa_jj_contract`, `fa_jj_logistics`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | order_id | int | 是 | 订单ID |
- **出参**:
  ```json
  {
    "code": 1,
    "data": {
      "order_id": 1, "orderNo": "JJ20260201001", "productName": "竹纤维毛巾礼盒套装",
      "coverImage": "...", "companyName": "杭州绿源商贸有限公司", "createTime": "2026-01-21 00:41",
      "state": 6, "status_text": "已结算",
      "commission": "5.00", "commissionAmount": "285.00", "depositRate": "10.00",
      "factoryBonus": "50.00", "logisticsRebate": "30.00",
      "contractUploadHours": 24, "executionHours": 72,
      "deposit": {"amount":"28.50","pay_status":1,"pay_status_text":"已支付"},
      "contract": {"file_url":"...contract_001.pdf","file_name":"竹纤维毛巾采购合同.pdf","status":2},
      "logistics": {"company_name":"顺丰速运","tracking_no":"SF1234567890","status":2,"timeline_json":[...],"rebate_amount":"30.00","checklist_files":["..."]}
    }
  }
  ```

### 12. 合同状态
- **路由**: `GET xiluxc.jj_order/contract_status`
- **控制器**: `JjOrder::contract_status`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_jj_order`, `fa_jj_contract`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | order_id | int | 是 | 订单ID |
- **出参**:
  ```json
  {"code":1,"data":{"stage":"upload","deadline_timestamp":1770396129,"contract_upload_hours":24,"execution_hours":72,"contract_url":"","contract_name":""}}
  ```
- **stage 取值**: upload=待上传合同, execution=履约执行中, expired=已过期

### 13. 提交合同
- **路由**: `POST xiluxc.jj_order/submit_contract`
- **控制器**: `JjOrder::submit_contract`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_jj_order`, `fa_jj_contract`, `fa_jj_order_log`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | order_id | int | 是 | 订单ID |
  | contract_url | string | 是 | 合同文件URL |
  | contract_name | string | 否 | 合同文件名 |
- **出参**:
  ```json
  {"code":1,"msg":"合同提交成功","data":null}
  ```
- **业务逻辑**: 更新合同记录 → 订单进入履约执行中 → 写入订单日志

### 14. 物流信息
- **路由**: `GET xiluxc.jj_order/logistics`
- **控制器**: `JjOrder::logistics`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_jj_order`, `fa_jj_logistics`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | order_id | int | 是 | 订单ID |
- **出参**:
  ```json
  {
    "code": 1,
    "data": {
      "logistics_type": "platform",
      "logistics_info": {"companyName":"顺丰速运","trackingNo":"SF1234567890","status":"transit","rebateAmount":"30.00"},
      "timeline": [{"time":"2026-01-25 16:42","desc":"已签收，签收人：前台"}, ...],
      "self_pickup_info": {"status":"pending","pickupTime":"2026-01-26 00:42","pickupNoteUrl":"..."},
      "checklist_files": ["/uploads/logistics/check_001_1.jpg", "/uploads/logistics/check_001_2.jpg"]
    }
  }
  ```

### 15. 催促工厂
- **路由**: `POST xiluxc.jj_order/urge_factory`
- **控制器**: `JjOrder::urge_factory`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_jj_order`, `fa_jj_order_log`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | order_id | int | 是 | 订单ID |
- **出参**:
  ```json
  {"code":1,"msg":"催促通知已发送","data":null}
  ```

### 16. 上传收发货清单
- **路由**: `POST xiluxc.jj_order/upload_checklist`
- **控制器**: `JjOrder::upload_checklist`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_jj_order`, `fa_jj_logistics`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | order_id | int | 是 | 订单ID |
  | file_url | string | 是 | 文件URL |
- **出参**:
  ```json
  {"code":1,"msg":"上传成功","data":{"url":"http://test.com/checklist.jpg"}}
  ```

---

## 五、竞价模块

### 17. 工厂列表（竞标选择）
- **路由**: `GET xiluxc.jj_bid/factory_list`
- **控制器**: `JjBid::factory_list`
- **测试状态**: ✅ 已通过（返回5家工厂）
- **关联表**: `fa_jj_factory`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | province | string | 否 | 省份筛选 |
  | industry | string | 否 | 行业筛选 |
- **出参**:
  ```json
  {
    "code": 1,
    "data": {
      "list": [
        {"id":4,"name":"江苏恒通纺织集团","province":"江苏","industry":"纺织服装","fulfillRate":"96.20","productCount":20},
        {"id":1,"name":"浙江华强日用品有限公司","province":"浙江","industry":"日用品","fulfillRate":"95.50","productCount":12}
      ]
    }
  }
  ```

### 18. 发布竞标
- **路由**: `POST xiluxc.jj_bid/publish`
- **控制器**: `JjBid::publish`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_jj_bid`, `fa_jj_bid_quote`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | factory_ids | array | 是 | 工厂ID数组（1-5个） |
  | category_name | string | 是 | 产品品类 |
  | quantity | float | 是 | 需求数量 |
  | unit | string | 是 | 计量单位 |
  | expect_delivery | string | 是 | 期望交货日期 |
  | target_commission | float | 否 | 目标佣金 |
  | company_name | string | 是 | 企业名称 |
  | address | string | 是 | 收货地址 |
  | contact_name | string | 是 | 联系人 |
  | contact_phone | string | 是 | 联系电话 |
  | credit_code | string | 是 | 信用代码 |
  | tax_number | string | 否 | 税务登记号 |
- **出参**:
  ```json
  {"code":1,"msg":"竞标发起成功","data":{"bid_id":"4","bid_sn":"BID202602100056525645"}}
  ```
- **业务逻辑**: 创建竞标 → 为每个工厂创建报价记录（待报价状态）→ 72小时过期

### 19. 竞标列表
- **路由**: `GET xiluxc.jj_bid/list`
- **控制器**: `JjBid::list`
- **测试状态**: ✅ 已通过（返回3条竞标 + 统计数据）
- **关联表**: `fa_jj_bid`, `fa_jj_bid_quote`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | status | string | 否 | all / bidding / completed / expired |
- **出参**:
  ```json
  {
    "code": 1,
    "data": {
      "list": [
        {"id":2,"bid_sn":"BID20260206002","category_name":"办公用品","quantity":200,"status":1,"status_text":"竞标中","quotes":[{"factory_name":"江苏恒通纺织集团","contract_price":"32.00","status":1}]}
      ],
      "stats": {"bidding":1,"completed":1,"expired":1}
    }
  }
  ```

### 20. 竞标详情
- **路由**: `GET xiluxc.jj_bid/detail`
- **控制器**: `JjBid::detail`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_jj_bid`, `fa_jj_bid_quote`, `fa_jj_factory`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | bid_id | int | 是 | 竞标ID |
- **出参**:
  ```json
  {
    "code": 1,
    "data": {
      "id": 2, "bidSn": "BID20260206002", "categoryName": "办公用品",
      "quantity": 200, "unit": "套", "expectDelivery": "2026-03-15",
      "targetCommission": "500.00", "factoryCount": 3, "quotedCount": 1,
      "status": 1, "status_text": "竞标中", "remainTime": 172457,
      "createTime": "2026-02-07 00:43",
      "buyerInfo": {"companyName":"南京创新科技有限公司","address":"...","contactName":"孙浩","contactPhone":"13900007777"},
      "quotes": [
        {"factory_id":4,"factory_name":"江苏恒通纺织集团","contract_price":"32.00","delivery_date":"2026-03-10","commission_amount":"480.00","fulfill_rate":"96.20","status":1,"selected":0,"factory":{"id":4,"company_name":"江苏恒通纺织集团","fulfill_rate":"96.20"}}
      ]
    }
  }
  ```

### 21. 选择工厂（从竞标报价中选定）
- **路由**: `POST xiluxc.jj_bid/select_factory`
- **控制器**: `JjBid::select_factory`
- **测试状态**: ✅ 已通过（业务逻辑验证正确）
- **关联表**: `fa_jj_bid`, `fa_jj_bid_quote`, `fa_jj_order`, `fa_jj_deposit`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | bid_id | int | 是 | 竞标ID |
  | factory_id | int | 是 | 工厂ID |
- **出参**: `{"code":1,"data":{"order_id":...}}`
- **业务逻辑**: 竞标标记完成 → 报价标记选中 → 创建订单 → 创建保证金记录

---

## 六、佣金管理模块

### 22. 佣金汇总
- **路由**: `GET xiluxc.jj_commission/summary`
- **控制器**: `JjCommission::summary`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_jj_commission`
- **入参**: 无
- **出参**:
  ```json
  {"code":1,"data":{"pending":998.5,"settled":1109,"total":"2107.50"}}
  ```

### 23. 佣金列表
- **路由**: `GET xiluxc.jj_commission/list`
- **控制器**: `JjCommission::list`
- **测试状态**: ✅ 已通过（返回4条佣金记录）
- **关联表**: `fa_jj_commission`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | status | string | 否 | pending / settled |
  | page | int | 否 | 页码 |
  | pagesize | int | 否 | 每页数量 |
- **出参**:
  ```json
  {
    "code": 1,
    "data": {
      "list": {
        "total": 4, "per_page": 10,
        "data": [
          {"orderNo":"JJ20260207004","productName":"全棉工装T恤定制款","companyName":"江苏恒通纺织集团","amount":"682.50","baseCommission":"682.50","factoryBonus":"0.00","logisticsRebate":"0.00","pkBonus":"0.00","redPacket":"0.00","settledTime":"","status_text":"待结算"},
          {"orderNo":"JJ20260201001","productName":"竹纤维毛巾礼盒套装","companyName":"浙江华强日用品有限公司","amount":"395.00","baseCommission":"285.00","factoryBonus":"50.00","logisticsRebate":"30.00","pkBonus":"20.00","redPacket":"10.00","settledTime":"2026-02-09 00:42","status_text":"已结算"}
        ]
      }
    }
  }
  ```

---

## 七、PK奖池模块

### 24. PK奖池信息
- **路由**: `GET xiluxc.jj_pk_pool/info`
- **控制器**: `JjPkPool::info`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_jj_pk_pool`, `fa_jj_pk_rank`, `fa_user`
- **入参**: 无
- **出参**:
  ```json
  {
    "code": 1,
    "data": {
      "pool_info": {"id":1,"total_amount":"50000.00","start_date":"2026-02-01","end_date":"2026-02-28"},
      "my_rank": {"rank":3,"revenue_amount":"1109.00","prize_amount":"0.00"},
      "rank_list": [
        {"agent_id":101,"revenue_amount":"2500.00","rank":1,"nickname":"用户101"},
        {"agent_id":102,"revenue_amount":"1800.00","rank":2,"nickname":"用户102"},
        {"agent_id":10,"revenue_amount":"1109.00","rank":3,"nickname":"重识世界","avatar":"..."},
        {"agent_id":103,"revenue_amount":"500.00","rank":4,"nickname":"用户103"}
      ],
      "history_pools": [{"id":2,"total_amount":"30000.00","start_date":"2026-01-01","end_date":"2026-01-31","status_text":"已结算"}]
    }
  }
  ```

---

## 八、红包模块

### 25. 红包汇总
- **路由**: `GET xiluxc.jj_red_packet/summary`
- **控制器**: `JjRedPacket::summary`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_jj_red_packet`
- **入参**: 无
- **出参**:
  ```json
  {"code":1,"data":{"totalAmount":35,"totalOrders":3,"perOrderAmount":"11.66","monthAmount":30}}
  ```

### 26. 红包列表
- **路由**: `GET xiluxc.jj_red_packet/list`
- **控制器**: `JjRedPacket::list`
- **测试状态**: ✅ 已通过（返回3条红包记录）
- **关联表**: `fa_jj_red_packet`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | status | string | 否 | unclaimed / claimed |
  | page | int | 否 | 页码 |
  | pagesize | int | 否 | 每页数量 |
- **出参**:
  ```json
  {"code":1,"data":{"list":{"total":3,"data":[{"order_id":3,"order_no":"JJ20260205003","amount":"5.00","status":0}, ...]}}}
  ```

---

## 九、分销邀请模块

### 27. 分销概览
- **路由**: `GET xiluxc.jj_distribution/index`
- **控制器**: `JjDistribution::index`
- **测试状态**: ⚠️ PHP 8.1 兼容性问题（hash() null 参数），业务逻辑正确
- **关联表**: `fa_jj_invite`, `fa_jj_agent_profile`, `fa_user`
- **入参**: 无
- **出参**:
  ```json
  {"code":1,"data":{"invite_code":"LwhHnN6M","invite_count":3,"team_revenue":"2107.50","team_list":[{"agent_id":10,"invite_user_id":101,"order_count":3, "inviteUser":{...}}]}}
  ```
- **备注**: Invite 模型 `inviteUser` 关联查询 fa_user 时因 ThinkPHP 5 与 PHP 8.1 的 hash() 兼容性问题会报错，线上 PHP 7.4 环境无此问题

---

## 十、心愿目标模块

### 28. 获取当前心愿目标
- **路由**: `GET xiluxc.jj_wish/current`
- **控制器**: `JjWish::current`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_jj_wish_goal`, `fa_jj_agent_profile`
- **入参**: 无
- **出参**:
  ```json
  {"code":1,"data":{"id":2,"type":"order","target":"10.00","current":"2107.50","rewardDesc":"完成 10 单订单可获得平台奖励","claimed":0}}
  ```
- 若无进行中目标返回 `data: null`

### 29. 设置心愿目标
- **路由**: `POST xiluxc.jj_wish/set_goal`
- **控制器**: `JjWish::set_goal`
- **测试状态**: ✅ 已通过
- **关联表**: `fa_jj_wish_goal`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | type | string | 是 | 目标类型: income / order |
  | target | float | 是 | 目标值 |
- **出参**:
  ```json
  {"code":1,"msg":"目标设置成功","data":{"id":"3"}}
  ```
- **业务逻辑**: 将现有进行中目标标记为已替换(status=2) → 创建新目标(status=0)

### 30. 领取奖励
- **路由**: `POST xiluxc.jj_wish/claim_reward`
- **控制器**: `JjWish::claim_reward`
- **测试状态**: ✅ 已通过（业务校验逻辑正确）
- **关联表**: `fa_jj_wish_goal`, `fa_jj_agent_profile`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | goal_id | int | 是 | 目标ID |
- **出参**: `{"code":1,"msg":"领取成功","data":null}` 或 `{"code":0,"msg":"目标尚未达成"}`

### 31. 历史目标列表
- **路由**: `GET xiluxc.jj_wish/history`
- **控制器**: `JjWish::history`
- **测试状态**: 🆕 新增
- **关联表**: `fa_jj_wish_goal`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | page | int | 否 | 页码，默认1 |
  | pagesize | int | 否 | 每页数量，默认10 |
- **出参**:
  ```json
  {
    "code": 1,
    "data": {
      "total": 5, "per_page": 10, "current_page": 1,
      "data": [
        {"id": 1, "agent_id": 10, "type": "income", "target_amount": "8000.00", "status": 1, "achieved": true, "period": "2026年1月", "createtime": 1735689600, "updatetime": 1735689600}
      ]
    }
  }
  ```
- **业务说明**: 查询非进行中（status<>0）的历史目标记录，按 ID 倒序分页返回。`achieved` 字段表示是否达成（status=1），`period` 为格式化的创建月份。

---

## 十一、红包抵扣模块

### 32. 红包抵扣保证金
- **路由**: `POST xiluxc.jj_red_packet/redeem`
- **控制器**: `JjRedPacket::redeem`
- **测试状态**: 🆕 新增
- **关联表**: `fa_jj_red_packet`, `fa_jj_deposit`
- **入参**:
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | deposit_id | int | 是 | 保证金记录ID |
  | red_packet_ids | array | 是 | 使用的红包ID列表 |
- **出参**:
  ```json
  {"code":1,"msg":"红包抵扣成功","data":{"deducted_amount":"100.00","remaining_amount":"1900.00"}}
  ```
- **业务逻辑**: 校验保证金记录（待支付状态）→ 校验红包可用性（已发放+未使用）→ 事务内标记红包已使用 → 计算抵扣金额（不超过保证金金额）→ 更新保证金抵扣记录
- **数据库变更**: `fa_jj_red_packet` 新增 `used`(tinyint)、`used_deposit_id`(int) 字段；`fa_jj_deposit` 新增 `red_packet_deduct`(decimal) 字段

---

## 十二、分销海报模块

### 33. 分销海报数据
- **路由**: `GET xiluxc.jj_distribution/poster`
- **控制器**: `JjDistribution::poster`
- **测试状态**: 🆕 新增
- **关联表**: `fa_jj_agent_profile`, `fa_user`
- **入参**: 无
- **出参**:
  ```json
  {
    "code": 1,
    "data": {
      "invite_code": "LwhHnN6M",
      "invite_url": "https://xxx/pages/login/login?invite_code=LwhHnN6M",
      "nickname": "重识世界",
      "avatar": "https://xxx/avatar.jpg"
    }
  }
  ```
- **业务说明**: 返回分销海报所需素材（邀请码、带邀请码的注册链接、用户昵称和头像），前端可用 canvas 合成海报图片。需已完成居间人认证。

---

## 订单状态流转

```
0:待确认 → 1:待缴保证金 → 2:已缴保证金 → 3:待上传合同 → 4:履约执行中 → 5:待结算 → 6:已结算
                                                                    ↘ 7:已取消
                                                        ↘ 8:已逾期
```

---

## 测试数据概览

| 表名 | 数量 | 数据说明 |
|------|------|---------|
| fa_jj_agent_profile | 1 | user_id=10，信用分85，等级2，总收入2107.50 |
| fa_jj_factory | 5 | 浙江/广东/山东/江苏/福建，覆盖日用品/电子/食品/纺织/茶叶 |
| fa_jj_product | 12 | 每家工厂2-3个产品，价格12.5~128元 |
| fa_jj_order | 6+ | 覆盖全状态：已结算×2、执行中×1、待上传合同×1、待支付定金×1、已取消×1 |
| fa_jj_deposit | 6+ | 已支付×4、待支付×1、已退款×1 |
| fa_jj_order_log | 23+ | 完整状态变更日志 |
| fa_jj_contract | 4 | 已确认×2、已上传×1、待上传×1 |
| fa_jj_logistics | 3 | 已签收×2（含完整时间线）、运输中×1 |
| fa_jj_bid | 3+ | 已完成×1、竞标中×1、已过期×1 |
| fa_jj_bid_quote | 8+ | 含已报价/待报价/已选中 |
| fa_jj_commission | 4 | 已结算(¥395+¥714)、待结算(¥316+¥682.50) |
| fa_jj_red_packet | 3 | 已发放×2、待发放×1 |
| fa_jj_invite | 3 | 邀请了3位用户 |
| fa_jj_wish_goal | 3 | 收入目标¥50000（已完成）、订单目标10单、收入目标¥100000（进行中） |
| fa_jj_pk_pool | 2 | 当前进行中(¥50000)、历史已结算(¥30000) |
| fa_jj_pk_rank | 7 | 当前期4人排名(user_id=10排第3)、历史期3人排名 |

---

## 数据库表关联汇总

| 表名 | 说明 | 被哪些接口使用 |
|------|------|---------------|
| fa_user | 用户表 | 登录、认证、个人中心 |
| fa_user_token | Token 表 | 所有认证接口 |
| fa_jj_agent_profile | 居间人扩展信息 | 认证、仪表盘、分销、心愿 |
| fa_jj_product | 产品池 | 产品列表、详情、提交接单 |
| fa_jj_factory | 工厂信息 | 产品详情、竞价工厂列表、竞标报价 |
| fa_jj_order | 订单主表 | 订单全流程（7-16） |
| fa_jj_deposit | 保证金 | 保证金缴纳与查询 |
| fa_jj_contract | 合同 | 合同上传与状态 |
| fa_jj_logistics | 物流 | 物流信息与清单 |
| fa_jj_order_log | 订单日志 | 订单状态变更记录 |
| fa_jj_bid | 竞标记录 | 竞标全流程（17-21） |
| fa_jj_bid_quote | 工厂报价 | 竞标详情与选择 |
| fa_jj_commission | 佣金记录 | 佣金汇总与列表 |
| fa_jj_pk_pool | PK奖池 | PK奖池信息 |
| fa_jj_pk_rank | PK排名 | PK排名查询 |
| fa_jj_red_packet | 红包 | 红包汇总与列表 |
| fa_jj_invite | 邀请关系 | 分销概览 |
| fa_jj_wish_goal | 心愿目标 | 心愿设置与领奖 |

---

## 控制器文件清单

| 文件路径 | 说明 | 接口数 |
|---------|------|--------|
| `application/api/controller/xiluxc/User.php` | 用户登录与居间人认证 | 2 |
| `application/api/controller/xiluxc/JjAgent.php` | 首页仪表盘与个人信息 | 2 |
| `application/api/controller/xiluxc/JjProduct.php` | 产品池列表与详情 | 2 |
| `application/api/controller/xiluxc/JjOrder.php` | 订单全流程 | 10 |
| `application/api/controller/xiluxc/JjBid.php` | 竞价模块 | 5 |
| `application/api/controller/xiluxc/JjCommission.php` | 佣金管理 | 2 |
| `application/api/controller/xiluxc/JjPkPool.php` | PK奖池 | 1 |
| `application/api/controller/xiluxc/JjRedPacket.php` | 红包管理 | 3 |
| `application/api/controller/xiluxc/JjDistribution.php` | 分销邀请 | 2 |
| `application/api/controller/xiluxc/JjWish.php` | 心愿目标 | 4 |
| **合计** | | **33** |

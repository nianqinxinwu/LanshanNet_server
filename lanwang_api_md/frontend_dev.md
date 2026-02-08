# 居间人端前端开发文档

## 项目概述

基于 **uni-app (Vue 2)** 的居间人电商平台前端，支持 H5（PC + Mobile）、微信小程序、Android/iOS 多端。使用 HBuilderX 作为构建工具，无 npm 构建流程。

- **API 基地址**：`http://192.168.1.149:8000/api`（开发环境）
- **UI 框架**：uView UI（`uni_modules/uview-ui`）
- **主题色**：`#FE4B01`

---

## 角色体系

| 角色 | roleType | 认证字段 | 页面前缀 | 目录 |
|------|----------|----------|----------|------|
| 居间人 | 1 | `isIntermediary` | `jj_` | `pages/jj/` |
| 工厂 | 2 | `isFactory` | `fc_` | `pages/fc/`（待开发） |

---

## 登录与跳转流程

```
登录页 (pages/login/login.vue)
  │
  ├─ roleType=1 (居间人)
  │   ├─ isIntermediary=1 → /pages/jj/jj_main/jj_main  ✅ 已开发
  │   └─ isIntermediary=0 → /pages/jj/jj_auth/jj_auth   ✅ 已开发
  │
  ├─ roleType=2 (工厂)
  │   ├─ isFactory=1 → /pages/index/index                ⬜ 待开发工厂首页
  │   └─ isFactory=0 → 提示"请完成企业认证"               ⬜ 待开发工厂认证页
  │
  └─ 其他 → navigateBack
```

---

## 居间人模块开发清单

### 页面文件

| 文件路径 | 功能 | 状态 |
|----------|------|------|
| `pages/jj/jj_auth/jj_auth.vue` | 居间人认证（个人/公司） | ✅ 已完成 |
| `pages/jj/jj_main/jj_main.vue` | 容器主页（左菜单+右内容 / 底部TabBar） | ✅ 已完成 |
| `pages/jj/jj_home/jj_home.vue` | 首页独立页面（已被 jj_main 替代，保留备用） | ✅ 已完成（未注册路由） |
| `pages/jj/jj_product_detail/jj_product_detail.vue` | 商品详情页 | ✅ 已完成（Mock数据） |
| `pages/jj/jj_buyer_form/jj_buyer_form.vue` | 买家信息填写页（接单） | ✅ 已完成（Mock数据） |
| `pages/jj/jj_distribution/jj_distribution.vue` | 分销推广页 | ✅ 已完成（Mock数据） |
| `pages/jj/jj_settings/jj_settings.vue` | 账号设置页 | ✅ 已完成（Mock数据） |
| `pages/jj/jj_products/jj_products.vue` | 商品池独立页面（已被 jj_main 替代） | ⬜ 占位（未注册路由） |
| `pages/jj/jj_orders/jj_orders.vue` | 订单独立页面（已被 jj_main 替代） | ⬜ 占位（未注册路由） |
| `pages/jj/jj_profile/jj_profile.vue` | 我的独立页面（已被 jj_main 替代） | ⬜ 占位（未注册路由） |

### 组件文件

| 文件路径 | 功能 | 状态 |
|----------|------|------|
| `components/jj-home-content/jj-home-content.vue` | 首页仪表盘内容组件 | ✅ 已完成 |
| `components/jj-products-content/jj-products-content.vue` | 商品池列表内容组件 | ✅ 已完成（Mock数据） |
| `components/jj-profile-content/jj-profile-content.vue` | 个人中心内容组件 | ✅ 已完成（Mock数据） |
| `components/jj-tabbar/jj-tabbar.vue` | 移动端底部 4Tab 导航 | ✅ 已完成 |
| `components/pc-sidebar/pc-sidebar.vue` | PC端左侧菜单导航 | ✅ 已完成 |

### 基础设施文件

| 文件路径 | 功能 | 状态 |
|----------|------|------|
| `xilu/responsive.js` | 响应式适配 Mixin（isPC/isMobile） | ✅ 已完成 |
| `main.js` | 全局注册 responsive mixin | ✅ 已修改 |
| `CLAUDE.md` | AI 开发规则（含响应式适配规范） | ✅ 已更新 |

---

## 居间人端 4 大模块功能划分

| 模块 ID | 页面路径 | 承载职能 | 当前状态 |
|---------|---------|---------|----------|
| jj_home | `/pages/index/index` | **工作台/首页**：集成六边形身价、佣金看板、激励通知 | ✅ 已完成（占位数据） |
| jj_products | `/pages/market/list` | **接单大厅**：任务/产品列表、筛选、智能推荐 | ✅ 已完成（Mock数据） |
| jj_orders | `/pages/order/list` | **订单/履约**：订单列表、合同状态、保证金管理 | ⬜ 占位待开发 |
| jj_profile | `/pages/user/profile` | **个人中心**：收入看板、分销入口、设置 | ✅ 已完成（Mock数据） |

> **说明**：以上页面路径为业务规划路径。当前代码中 4 个模块统一由容器页 `pages/jj/jj_main/jj_main.vue` 承载，通过 `v-if` 切换内容组件，不使用独立页面路由。后续各模块功能开发时，在 `jj_main` 内新增对应内容组件即可。

### 各模块功能拆解

#### jj_home — 工作台/首页

| 子功能 | 说明 | 状态 |
|--------|------|------|
| 用户信息卡片 | 头像 + 昵称 + 信誉评分 | ✅ 已完成（占位数据） |
| 六边形身价雷达图 | Canvas 绘制，6 维度能力值 | ✅ 已完成（占位数据） |
| 佣金看板 | 待结算 / 本月已结算 / 心愿进度 | ✅ 已完成（占位数据） |
| 快捷入口 | 浏览商品 / 发起竞标 / 我的订单 / 新手指南 | ✅ 已完成（部分跳转为占位） |
| 待办事项 | 保证金 / 完善资料 / 新手指南 | ✅ 已完成（占位数据） |
| 激励通知 | 业绩播报、排名提醒等 | ⬜ 待开发 |
| 数据接口对接 | 替换全部占位数据为真实接口 | ⬜ 待对接 |

#### jj_products — 接单大厅

| 子功能 | 说明 | 状态 |
|--------|------|------|
| 任务/产品列表 | 分页加载、下拉刷新 | ✅ 已完成（Mock数据） |
| 筛选功能 | 按类别、价格区间、佣金比例等筛选 | ✅ 已完成（Mock数据） |
| 智能推荐 | 根据居间人能力匹配推荐任务 | ⬜ 待开发 |
| 任务详情 | 查看任务详情、佣金规则 | ✅ 已完成（Mock数据） |
| 接单/竞标 | 发起接单或竞标操作 | ✅ 已完成（Mock数据，买家信息表单） |

#### jj_orders — 订单/履约

| 子功能 | 说明 | 状态 |
|--------|------|------|
| 订单列表 | Tab 切换（待处理/进行中/已完成） | ⬜ 待开发 |
| 订单详情 | 订单信息、交易双方、时间线 | ⬜ 待开发 |
| 合同状态 | 合同上传、签署、查看 | ⬜ 待开发 |
| 保证金管理 | 缴纳、退还、冻结记录 | ⬜ 待开发 |
| 物流跟踪 | 物流节点展示 | ⬜ 待开发 |
| 结算记录 | 佣金结算明细 | ⬜ 待开发 |

#### jj_profile — 个人中心

| 子功能 | 说明 | 状态 |
|--------|------|------|
| 个人信息 | 头像、昵称、手机号管理 | ✅ 已完成（Mock数据，设置页展示） |
| 实名认证状态 | 查看/重新认证 | ✅ 已完成（设置页展示认证状态） |
| 收入看板 | 待结算/已结算/累计收入、心愿目标进度 | ✅ 已完成（Mock数据） |
| 待结算订单 | 订单ID、商品、佣金、倒计时 | ✅ 已完成（Mock数据） |
| 钱包/余额 | 余额、提现、收支明细 | ⬜ 待开发 |
| 分销入口 | 邀请下级、团队管理 | ✅ 已完成（Mock数据） |
| 消息中心 | 系统通知、订单消息 | ⬜ 待开发 |
| 设置 | 账号安全、切换身份、关于、退出 | ✅ 已完成（Mock数据） |

---

## 已完成功能详情

### 1. 居间人认证页 `jj_auth` ✅

**接口**：`POST xiluxc.user/intermediaryAuth`

**表单字段**：

| 字段 | 说明 | 类型 |
|------|------|------|
| `agentType` | 1=个人居间人，2=公司居间人 | 必填 |
| `mobile` | 手机号（只读，从登录传入） | 必填 |
| `code` | 短信验证码（6位） | 必填 |
| `idCardFrontImgUrl` | 身份证正面照片 URL | 必填 |
| `idCardBackImgUrl` | 身份证反面照片 URL | 必填 |
| `businessLicense` | 营业执照照片 URL | 公司必填 |

**功能点**：
- 个人/公司类型单选切换（choose_sc/choose_uc 图标）
- 手机号只读展示 + SMS 验证码（60s 倒计时，接口 `sms/send`，event=`jj_auth`）
- 图片上传（H5 用 `uploadFileH5`，微信用 `uploadFile`，条件编译）
- 协议勾选（《平台协议》《履约保证金规则》）
- 表单校验（`xilu/validate.js`）
- 认证成功 → 跳转 `jj_main`

### 2. 容器主页 `jj_main` ✅

**架构**：单页面承载 4 个 Tab 模块，通过 `v-if` 切换内容组件。

**PC 端布局**：
```
┌─────────────┬──────────────────────────┐
│  pc-sidebar  │     内容区域              │
│  (220px固定)  │  max-width: 1200px      │
│              │                          │
│  · 首页      │   v-if 切换:             │
│  · 商品池    │   home → jj-home-content │
│  · 订单      │   products → 占位        │
│  · 我的      │   orders → 占位          │
│              │   profile → 占位         │
│  退出登录    │                          │
└─────────────┴──────────────────────────┘
```

**移动端布局**：
```
┌──────────────────────────┐
│                          │
│     内容区域              │
│     v-if 切换组件         │
│                          │
├──────────────────────────┤
│  首页 │ 商品池 │ 订单 │ 我的 │  ← jj-tabbar
└──────────────────────────┘
```

**导航方式**：sidebar/tabbar 设置 `navigate=false`，通过 `@change` 事件切换 `activeTab`，无页面跳转。

### 3. 首页仪表盘 `jj-home-content` ✅

**模块组成**（全部使用占位数据）：

| 模块 | 内容 | 数据来源 |
|------|------|----------|
| 用户信息 | 头像 + 昵称 + 信誉评分 85 分 | 占位 |
| 能力雷达图 | Canvas 六边形，6 维度（履约率/成交额/客户评价/响应速度/沟通能力/专业领域） | 占位 [0.8, 0.6, 0.9, 0.7, 0.85, 0.75] |
| 收入卡片 | 待结算 ¥12,580 / 本月已结算 ¥8,320 / 心愿进度 68% | 占位 |
| 快捷入口 | 浏览商品 / 发起竞标 / 我的订单 / 新手指南 | 浏览商品和我的订单可切换Tab，其余提示"开发中" |
| 待办事项 | 缴纳保证金 / 完善资料 / 查看新手指南 | 占位 |

**雷达图参数**：Canvas 360×320px，圆心 (180,160)，半径 110px，标签字号 13px。

### 4. 响应式适配 ✅

**断点**：768px（`isPC` >= 768px，`isMobile` < 768px）

**全局 Mixin**（`xilu/responsive.js`）：
- `isPC` / `isMobile` 计算属性
- H5 端监听 `window.resize` 实时更新
- `handleTabBar()` 方法：PC 隐藏原生 tabBar

**PC 端规则**：
- 左侧固定菜单 220px + 右侧内容区 margin-left: 220px
- 内容区 max-width: 1200px 居中
- rpx 冻结，改用 px
- 可点击元素添加 `cursor: pointer` + `hover` 效果

**移动端规则**：
- 全宽纵向排列
- 使用 rpx 单位
- 原生 tabBar 或自定义 jj-tabbar

---

## 待开发功能

### 居间人端（优先级从高到低）

| 模块 | 说明 | 优先级 |
|------|------|--------|
| 商品池页面 | 商品列表浏览、筛选、搜索 | P0 |
| 订单页面 | 订单列表（待处理/进行中/已完成）、订单详情 | P0 |
| 我的页面 | 个人信息、钱包、设置 | P0 |
| 首页数据对接 | 用户信息、雷达图、收入卡片、待办事项接口对接 | P1 |
| 发起竞标 | 竞标流程页面 | P1 |
| 保证金缴纳 | 保证金支付流程 | P1 |
| 合同管理 | 合同上传/查看 | P2 |
| 物流跟踪 | 物流信息展示 | P2 |
| 新手指南 | 操作引导页 | P3 |

### 工厂端

| 模块 | 说明 | 优先级 |
|------|------|--------|
| 工厂认证页 | `pages/fc/fc_auth` | P0 |
| 工厂首页 | `pages/fc/fc_main` | P0 |
| 商品发布 | 工厂发布商品到商品池 | P0 |
| 订单管理 | 接单、发货、结算 | P1 |

### 平台公共

| 模块 | 说明 | 优先级 |
|------|------|--------|
| `init_config` 接口 | App.vue 启动配置（当前已注释） | P0 |
| 消息通知 | 系统消息、订单通知 | P2 |

---

## pages.json 路由注册（居间人相关）

```json
{
  "path": "pages/jj/jj_auth/jj_auth",
  "style": { "navigationBarTitleText": "居间人认证" }
},
{
  "path": "pages/jj/jj_main/jj_main",
  "style": { "navigationStyle": "custom" }
}
```

> 注：jj_home、jj_products、jj_orders、jj_profile 的文件保留在 pages/jj/ 目录下，但已从 pages.json 路由中移除，功能由 jj_main 容器页统一承载。

---

## API 接口清单

### 已对接

| 接口 | 方法 | 说明 | 对接文件 |
|------|------|------|----------|
| `xiluxc.user/mobilelogin` | POST | 手机号+验证码登录 | login.vue |
| `sms/send` | POST | 发送短信验证码 | login.vue, jj_auth.vue |
| `xiluxc.user/intermediaryAuth` | POST | 居间人认证提交 | jj_auth.vue |

### 待开发

| 接口 | 说明 | 关联页面 |
|------|------|----------|
| 用户信息接口 | 获取昵称、头像、信誉评分 | jj-home-content |
| 雷达图数据接口 | 6 维度能力值 | jj-home-content |
| 收入统计接口 | 待结算/已结算/心愿进度 | jj-home-content |
| 待办事项接口 | 待处理任务列表 | jj-home-content |
| 商品列表接口 | 商品池数据 | jj_products（待开发） |
| 订单列表接口 | 订单数据 | jj_orders（待开发） |

---

## 技术约定

- **HTTP 请求**：统一使用 `this.$core.post()` / `this.$core.get()`，自动附加 token/cityid/platform
- **表单校验**：`xilu/validate.js` 的 `validate.check(formData, rule)` 模式
- **图片上传**：`uni.chooseImage` + 条件编译（H5: `uploadFileH5`，微信: `uploadFile`）
- **缓存**：`this.$core.getCache(key)` / `this.$core.setCache(key, value, ttl)`
- **全局状态**：`getApp().globalData`（非 Vuex）
- **条件编译**：`// #ifdef H5`、`// #ifdef MP-WEIXIN`、`// #endif`
- **单位**：移动端 rpx，PC 端 px，断点 768px

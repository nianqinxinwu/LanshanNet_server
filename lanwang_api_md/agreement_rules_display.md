# 协议与规则展示逻辑说明

## 一、整体机制

项目中所有协议、规则、公告等富文本内容，均由**后台配置管理**，前端通过 API 加载 HTML 内容后使用 `<u-parse>` 组件渲染。不在前端硬编码协议文案。

---

## 二、核心渲染组件

| 组件 | 来源 | 用途 | 说明 |
|------|------|------|------|
| `<u-parse>` | `uni_modules/uview-ui/components/u-parse/u-parse.vue` | 完整富文本渲染 | 基于 mp-html v2.0.4，支持图片预览、链接跳转、懒加载 |
| `<rich-text>` | uni-app 原生组件 | 简短片段预览 | 仅用于列表中的摘要预览（如公告列表） |

---

## 三、协议/规则展示模式

### 模式A：通用富文本页 `rich_mp.vue`（主要方式）

**页面路径：** `pages/rich_mp/rich_mp.vue`

**流程：**
```
用户点击协议链接
  → 从 getApp().globalData.config[configKey] 取到内容ID
  → uni.navigateTo 跳转到 rich_mp 页面，携带 id 参数
  → rich_mp 页面调用 API 加载内容
  → <u-parse> 渲染 HTML 富文本
```

**rich_mp.vue 核心代码：**
```vue
<template>
  <view>
    <view class="container">
      <view class="ptb30 plr40">
        <view class="col1 fs40 fwb">{{ article.name }}</view>
        <view class="mt30 col1 fs30">
          <u-parse :content="article.content"></u-parse>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  data() {
    return {
      id: 0,
      article: { name: '', content: '' }
    }
  },
  onLoad(options) {
    this.id = options.id || 0;
    this.fetchDetail();
  },
  methods: {
    fetchDetail() {
      this.$core.get({
        url: 'xiluxc.common/singlepage',
        data: { id: this.id },
        loading: false,
        success: ret => {
          this.article = ret.data;
          uni.setNavigationBarTitle({ title: ret.data.name });
        },
        fail: err => {
          console.log(err);
        }
      });
    }
  }
}
</script>
```

**API 接口：**
- 接口地址：`xiluxc.common/singlepage`
- 请求方式：GET
- 请求参数：`{ id: Number }` — 内容ID，从全局配置中获取
- 返回数据：`{ name: String, content: String }` — name 为标题，content 为 HTML 富文本

**调用方统一写法：**
```js
openAgreement(type) {
  let id = getApp().globalData.config[type] || 0;
  uni.navigateTo({ url: '/pages/rich_mp/rich_mp?id=' + id });
}
```

---

### 模式B：EventChannel 父子传参

**使用场景：** `vip_agreement.vue`（VIP会员购买协议）

父页面已加载完整数据，通过 eventChannel 直接传给子页面，避免重复请求。

**父页面（vip_member.vue / my_vip.vue）：**
```js
onVipAgreement() {
  let vip = this.vip;
  uni.navigateTo({
    url: '/pages/vip_agreement/vip_agreement',
    success(res) {
      res.eventChannel.emit('vipAgreement', { data: vip });
    }
  });
}
```

**子页面（vip_agreement.vue）：**
```js
onLoad(options) {
  let page = this;
  this.getOpenerEventChannel().on('vipAgreement', function(data) {
    page.vip = data.data;
  });
}
```

```vue
<u-parse :content="vip.content"></u-parse>
```

---

## 四、全局配置 Key 与协议对应表

以下配置存储在 `getApp().globalData.config` 中，值为后台内容ID：

| 配置 Key | 协议名称 | 调用页面 | 调用入口 |
|----------|----------|----------|----------|
| `user_agreement` | 《用户协议》 | `pages/login/login.vue` | 登录页底部勾选区 |
| `privacy_agreement` | 《隐私协议》 | `pages/login/login.vue` | 登录页底部勾选区 |
| `platform_agreement` | 《平台协议》 | `pages/jj/jj_auth/jj_auth.vue` | 居间人认证页协议勾选 |
| `deposit_rule` | 《履约保证金规则》 | `pages/jj/jj_auth/jj_auth.vue` | 居间人认证页协议勾选 |
| `deposit_rule` | 《履约保证协议》 | `pages/jj/jj_deposit/jj_deposit.vue` | 保证金支付页协议勾选 |
| `order_agreement` | 《接单服务协议》 | `pages/jj/jj_buyer_form/jj_buyer_form.vue` | 买家信息填写页协议勾选 |

---

## 五、其他富文本展示页面

除协议规则外，以下页面也使用相同的 `<u-parse>` 模式展示后台配置的富文本内容：

| 页面 | API 接口 | 用途 |
|------|----------|------|
| `pages/platform_bulletin_info/platform_bulletin_info.vue` | `xiluxc.message/notice_detail` | 平台公告详情 |
| `pages/car_knowledge_info/car_knowledge_info.vue` | `xiluxc.knowledge/detail` | 养车知识详情 |
| `pages/service_detail/service_detail.vue` | `xiluxc.shop/service_detail` | 服务项目详情 |
| `pages/package_detail/package_detail.vue` | `xiluxc.shop/package_detail` | 套餐详情 |
| `pages/stores_info/stores_info.vue` | `xiluxc.shop/shop_info` | 门店介绍 |
| `pages/vip_member/vip_member.vue` | `xiluxc.vip/detail` | VIP会员权益说明 |

---

## 六、列表页中的富文本预览

公告列表等场景使用原生 `<rich-text>` 组件做简短预览：

```vue
<!-- platform_bulletin.vue 公告列表中的摘要 -->
<rich-text :nodes="item.content"></rich-text>
```

```vue
<!-- pay_order.vue 套餐注意事项 -->
<rich-text :nodes="order.package_choosed.notice"></rich-text>
```

---

## 七、居间人端规则展示汇总

居间人端各页面中涉及的规则展示（非跳转 rich_mp 的部分），采用前端硬编码列表 + 后台数据结合的方式：

| 页面 | 规则内容 | 展示方式 |
|------|----------|----------|
| `jj_red_packet.vue` | 红包发放规则（4条） | 前端 `ruleList` 数组硬编码，圆点列表展示 |
| `jj_pk_pool.vue` | PK奖池分配规则（5条） | 前端 `rules` 数组硬编码，圆点列表展示 |
| `jj_wish.vue` | 心愿奖励规则 | 前端 `rewardPreview` 根据金额区间展示 |
| `jj_distribution.vue` | 分销推广规则（3条） | 前端 `ruleList` 数组硬编码 |
| `jj_deposit.vue` | 保证金说明提示 | 页面内黄色警告条，文案硬编码 |
| `jj_contract.vue` | 合同上传要求说明 | 页面内提示文案，硬编码 |
| `jj_logistics.vue` | 自提要求（身份证+订单号） | 前端 `pickupRequirements` 数组硬编码 |

这些页面内的简要规则说明为前端硬编码，属于 UI 引导文案，不需要从后台动态加载。正式协议文本仍统一走 `rich_mp` + `xiluxc.common/singlepage` 模式。

---

## 八、后台接口对接要点

1. **后台需配置的内容页：** 在管理后台的"单页面管理"中创建各协议内容，得到对应 ID
2. **全局配置对应：** 在管理后台的"系统配置"中将 `user_agreement`、`platform_agreement` 等 key 设置为对应内容 ID
3. **内容格式：** HTML 富文本，支持图片、表格、链接等标准 HTML 标签
4. **前端不做内容缓存：** 每次打开协议页都会重新请求 API，确保内容实时更新

# 数据库表结构文档

数据库: andone | 字符集: utf8mb4 | 表前缀: fa_

---

## 表概览

| # | 表名 | 注释 | 约行数 | 引擎 |
|---|------|------|--------|------|
| 1 | fa_admin | 管理员表 | 0 | InnoDB |
| 2 | fa_admin_log | 管理员日志表 | 210 | InnoDB |
| 3 | fa_area | 地区表 | 72692 | InnoDB |
| 4 | fa_attachment | 附件表 | 19 | InnoDB |
| 5 | fa_auth_group | 分组表 | 5 | InnoDB |
| 6 | fa_auth_group_access | 权限分组表 | 0 | InnoDB |
| 7 | fa_auth_rule | 节点表 | 310 | InnoDB |
| 8 | fa_category | 分类表 | 13 | InnoDB |
| 9 | fa_config | 系统配置 | 18 | InnoDB |
| 10 | fa_ems | 邮箱验证码表 | 0 | InnoDB |
| 11 | fa_sms | 短信验证码表 | 0 | InnoDB |
| 12 | fa_test | 测试表 | 0 | InnoDB |
| 13 | fa_user | 会员表 | 8 | InnoDB |
| 14 | fa_user_group | 会员组表 | 0 | InnoDB |
| 15 | fa_user_money_log | 会员余额变动表 | 0 | InnoDB |
| 16 | fa_user_rule | 会员规则表 | 12 | InnoDB |
| 17 | fa_user_score_log | 会员积分变动表 | 0 | InnoDB |
| 18 | fa_user_token | 会员Token表 | 62 | InnoDB |
| 19 | fa_version | 版本表 | 0 | InnoDB |
| 20 | fa_xiluxc_advice | 投诉建议 | 0 | InnoDB |
| 21 | fa_xiluxc_aftersale | 售后退款 | 0 | InnoDB |
| 22 | fa_xiluxc_area | 地区表 | 42814 | InnoDB |
| 23 | fa_xiluxc_areaCopy | 地区表 | 72862 | InnoDB |
| 24 | fa_xiluxc_banner | 图片banner | 0 | InnoDB |
| 25 | fa_xiluxc_car_brand | 汽车品牌 | 566 | InnoDB |
| 26 | fa_xiluxc_car_models | 汽车型号 | 3880 | InnoDB |
| 27 | fa_xiluxc_car_series | 汽车型号 | 1128 | InnoDB |
| 28 | fa_xiluxc_config | 基础配置 | 7 | InnoDB |
| 29 | fa_xiluxc_coupon | 优惠券管理 | 0 | InnoDB |
| 30 | fa_xiluxc_coupon_items | 优惠券使用范围 | 0 | InnoDB |
| 31 | fa_xiluxc_divide | 分销基础信息 | 0 | InnoDB |
| 32 | fa_xiluxc_money_log | 会员余额变动表 | 0 | InnoDB |
| 33 | fa_xiluxc_navigation | 金刚区 | 10 | InnoDB |
| 34 | fa_xiluxc_news | 知识库管理 | 8 | InnoDB |
| 35 | fa_xiluxc_news_category | 知识库分类 | 3 | InnoDB |
| 36 | fa_xiluxc_notice | 平台公告 | 0 | InnoDB |
| 37 | fa_xiluxc_order | 订单管理 | 4 | InnoDB |
| 38 | fa_xiluxc_order_coupon | 订单优惠券 | 2 | InnoDB |
| 39 | fa_xiluxc_order_item | 订单商品 | 4 | InnoDB |
| 40 | fa_xiluxc_order_log | 订单日志 | 4 | InnoDB |
| 41 | fa_xiluxc_order_qrcode | 服务订单核销码 | 0 | InnoDB |
| 42 | fa_xiluxc_property | 属性管理 | 3 | InnoDB |
| 43 | fa_xiluxc_recharge | 充值金额 | 3 | InnoDB |
| 44 | fa_xiluxc_recharge_order | 充值订单 | 29 | InnoDB |
| 45 | fa_xiluxc_score_log | 会员积分变动表 | 0 | InnoDB |
| 46 | fa_xiluxc_service | 服务管理 | 10 | InnoDB |
| 47 | fa_xiluxc_service_comment | 服务评价 | 0 | InnoDB |
| 48 | fa_xiluxc_shop | 门店管理 | 0 | InnoDB |
| 49 | fa_xiluxc_shop_account | 门店账户表 | 3 | InnoDB |
| 50 | fa_xiluxc_shop_branch_package | 分店套餐 | 0 | InnoDB |
| 51 | fa_xiluxc_shop_branch_service | 分店服务 | 0 | InnoDB |
| 52 | fa_xiluxc_shop_brand | 品牌信息 | 2 | InnoDB |
| 53 | fa_xiluxc_shop_cameralog | 门店管理 | 126 | InnoDB |
| 54 | fa_xiluxc_shop_device | 门店管理 | 2 | InnoDB |
| 55 | fa_xiluxc_shop_deviceBox | 门店管理 | 48 | InnoDB |
| 56 | fa_xiluxc_shop_device_box | 门店管理 | 48 | InnoDB |
| 57 | fa_xiluxc_shop_package | 门店套餐 | 4 | InnoDB |
| 58 | fa_xiluxc_shop_package_service | 门店套餐服务 | 7 | InnoDB |
| 59 | fa_xiluxc_shop_property | 门店属性 | 0 | InnoDB |
| 60 | fa_xiluxc_shop_service | 门店服务 | 6 | InnoDB |
| 61 | fa_xiluxc_shop_service_price | 门店服务价格 | 8 | InnoDB |
| 62 | fa_xiluxc_shop_tag | 门店标签 | 0 | InnoDB |
| 63 | fa_xiluxc_shop_user | 门店会员表 | 0 | InnoDB |
| 64 | fa_xiluxc_shop_verifier | 门店核销员 | 0 | InnoDB |
| 65 | fa_xiluxc_shop_vip | 门店会员卡 | 3 | InnoDB |
| 66 | fa_xiluxc_shop_washlog | 门店管理 | 93 | InnoDB |
| 67 | fa_xiluxc_shop_withdraw | 门店提现 | 0 | InnoDB |
| 68 | fa_xiluxc_singlepage | 单页管理 | 5 | InnoDB |
| 69 | fa_xiluxc_tag | 标签管理 | 3 | InnoDB |
| 70 | fa_xiluxc_third | 第三方登录表 | 2 | InnoDB |
| 71 | fa_xiluxc_user | 会员身份表 | 4 | InnoDB |
| 72 | fa_xiluxc_user_account | 用户账户表 | 4 | InnoDB |
| 73 | fa_xiluxc_user_brand | 品牌申请 | 0 | InnoDB |
| 74 | fa_xiluxc_user_car | 用户车辆 | 0 | InnoDB |
| 75 | fa_xiluxc_user_coupon | 用户优惠券 | 3 | InnoDB |
| 76 | fa_xiluxc_user_message | 消息 | 0 | InnoDB |
| 77 | fa_xiluxc_user_notice | 会员查看公告 | 0 | InnoDB |
| 78 | fa_xiluxc_user_package | 会员套餐 | 0 | InnoDB |
| 79 | fa_xiluxc_user_package_service | - | 0 | InnoDB |
| 80 | fa_xiluxc_user_shop_account | 用户门店账户表 | 2 | InnoDB |
| 81 | fa_xiluxc_user_shop_vip | 用户门店会员表 | 0 | InnoDB |
| 82 | fa_xiluxc_vip_order | 会员订单 | 0 | InnoDB |
| 83 | fa_xiluxc_withdraw | 提现金额 | 0 | InnoDB |

---

## 表详细结构

### fa_admin (管理员表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | ID |
| username | varchar(20) | Y | UNI |  | - | 用户名 |
| nickname | varchar(50) | Y | - |  | - | 昵称 |
| password | varchar(32) | Y | - |  | - | 密码 |
| salt | varchar(30) | Y | - |  | - | 密码盐 |
| avatar | varchar(255) | Y | - |  | - | 头像 |
| email | varchar(100) | Y | - |  | - | 电子邮箱 |
| mobile | varchar(11) | Y | - |  | - | 手机号码 |
| loginfailure | tinyint(1) unsigned | N | - | 0 | - | 失败次数 |
| logintime | bigint(16) | Y | - | NULL | - | 登录时间 |
| loginip | varchar(50) | Y | - | NULL | - | 登录IP |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |
| token | varchar(59) | Y | - |  | - | Session标识 |
| status | varchar(30) | N | - | normal | - | 状态 |

### fa_admin_log (管理员日志表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | ID |
| admin_id | int(10) unsigned | N | - | 0 | - | 管理员ID |
| username | varchar(30) | Y | MUL |  | - | 管理员名字 |
| url | varchar(1500) | Y | - |  | - | 操作页面 |
| title | varchar(100) | Y | - |  | - | 日志标题 |
| content | longtext | N | - | NULL | - | 内容 |
| ip | varchar(50) | Y | - |  | - | IP |
| useragent | varchar(255) | Y | - |  | - | User-Agent |
| createtime | bigint(16) | Y | - | NULL | - | 操作时间 |

### fa_area (地区表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) | N | PRI | NULL | auto_increment | ID |
| pid | int(10) | Y | MUL | NULL | - | 父id |
| shortname | varchar(100) | Y | - | NULL | - | 简称 |
| name | varchar(100) | Y | - | NULL | - | 名称 |
| mergename | varchar(255) | Y | - | NULL | - | 全称 |
| level | tinyint(4) | Y | - | NULL | - | 层级:1=省,2=市,3=区/县 |
| pinyin | varchar(100) | Y | - | NULL | - | 拼音 |
| code | varchar(100) | Y | - | NULL | - | 长途区号 |
| zip | varchar(100) | Y | - | NULL | - | 邮编 |
| first | varchar(50) | Y | - | NULL | - | 首字母 |
| lng | varchar(100) | Y | - | NULL | - | 经度 |
| lat | varchar(100) | Y | - | NULL | - | 纬度 |
| is_re | int(10) | Y | - | 0 | - | - |
| status | varchar(200) | Y | - | NULL | - | - |

### fa_attachment (附件表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(20) unsigned | N | PRI | NULL | auto_increment | ID |
| category | varchar(50) | Y | - |  | - | 类别 |
| admin_id | int(10) unsigned | N | - | 0 | - | 管理员ID |
| user_id | int(10) unsigned | N | - | 0 | - | 会员ID |
| url | varchar(255) | Y | - |  | - | 物理路径 |
| imagewidth | int(10) unsigned | Y | - | 0 | - | 宽度 |
| imageheight | int(10) unsigned | Y | - | 0 | - | 高度 |
| imagetype | varchar(30) | Y | - |  | - | 图片类型 |
| imageframes | int(10) unsigned | N | - | 0 | - | 图片帧数 |
| filename | varchar(100) | Y | - |  | - | 文件名称 |
| filesize | int(10) unsigned | N | - | 0 | - | 文件大小 |
| mimetype | varchar(100) | Y | - |  | - | mime类型 |
| extparam | varchar(255) | Y | - |  | - | 透传数据 |
| createtime | bigint(16) | Y | - | NULL | - | 创建日期 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |
| uploadtime | bigint(16) | Y | - | NULL | - | 上传时间 |
| storage | varchar(100) | N | - | local | - | 存储位置 |
| sha1 | varchar(40) | Y | - |  | - | 文件 sha1编码 |

### fa_auth_group (分组表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| pid | int(10) unsigned | N | - | 0 | - | 父组别 |
| name | varchar(100) | Y | - |  | - | 组名 |
| rules | text | N | - | NULL | - | 规则ID |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |
| status | varchar(30) | Y | - |  | - | 状态 |

### fa_auth_group_access (权限分组表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| uid | int(10) unsigned | N | PRI | NULL | - | 会员ID |
| group_id | int(10) unsigned | N | PRI | NULL | - | 级别ID |

### fa_auth_rule (节点表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| type | enum('menu','file') | N | - | file | - | menu为菜单,file为权限节点 |
| pid | int(10) unsigned | N | MUL | 0 | - | 父ID |
| name | varchar(100) | Y | UNI |  | - | 规则名称 |
| title | varchar(50) | Y | - |  | - | 规则名称 |
| icon | varchar(50) | Y | - |  | - | 图标 |
| url | varchar(255) | Y | - |  | - | 规则URL |
| condition | varchar(255) | Y | - |  | - | 条件 |
| remark | varchar(255) | Y | - |  | - | 备注 |
| ismenu | tinyint(1) unsigned | N | - | 0 | - | 是否为菜单 |
| menutype | enum('addtabs','blank','dialog','ajax') | Y | - | NULL | - | 菜单类型 |
| extend | varchar(255) | Y | - |  | - | 扩展属性 |
| py | varchar(30) | Y | - |  | - | 拼音首字母 |
| pinyin | varchar(100) | Y | - |  | - | 拼音 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |
| weigh | int(10) | N | MUL | 0 | - | 权重 |
| status | varchar(30) | Y | - |  | - | 状态 |

### fa_category (分类表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| pid | int(10) unsigned | N | MUL | 0 | - | 父ID |
| type | varchar(30) | Y | - |  | - | 栏目类型 |
| name | varchar(30) | Y | - |  | - | - |
| nickname | varchar(50) | Y | - |  | - | - |
| flag | set('hot','index','recommend') | Y | - |  | - | - |
| image | varchar(100) | Y | - |  | - | 图片 |
| keywords | varchar(255) | Y | - |  | - | 关键字 |
| description | varchar(255) | Y | - |  | - | 描述 |
| diyname | varchar(30) | Y | - |  | - | 自定义名称 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |
| weigh | int(10) | N | MUL | 0 | - | 权重 |
| status | varchar(30) | Y | - |  | - | 状态 |

### fa_config (系统配置)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| name | varchar(30) | Y | UNI |  | - | 变量名 |
| group | varchar(30) | Y | - |  | - | 分组 |
| title | varchar(100) | Y | - |  | - | 变量标题 |
| tip | varchar(100) | Y | - |  | - | 变量描述 |
| type | varchar(30) | Y | - |  | - | 类型:string,text,int,bool,array,datetime,date,file |
| visible | varchar(255) | Y | - |  | - | 可见条件 |
| value | text | Y | - | NULL | - | 变量值 |
| content | text | Y | - | NULL | - | 变量字典数据 |
| rule | varchar(100) | Y | - |  | - | 验证规则 |
| extend | varchar(255) | Y | - |  | - | 扩展属性 |
| setting | varchar(255) | Y | - |  | - | 配置 |

### fa_ems (邮箱验证码表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | ID |
| event | varchar(30) | Y | - |  | - | 事件 |
| email | varchar(100) | Y | - |  | - | 邮箱 |
| code | varchar(10) | Y | - |  | - | 验证码 |
| times | int(10) unsigned | N | - | 0 | - | 验证次数 |
| ip | varchar(30) | Y | - |  | - | IP |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |

### fa_sms (短信验证码表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | ID |
| event | varchar(30) | Y | - |  | - | 事件 |
| mobile | varchar(20) | Y | - |  | - | 手机号 |
| code | varchar(10) | Y | - |  | - | 验证码 |
| times | int(10) unsigned | N | - | 0 | - | 验证次数 |
| ip | varchar(30) | Y | - |  | - | IP |
| createtime | bigint(16) unsigned | Y | - | 0 | - | 创建时间 |

### fa_test (测试表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | ID |
| user_id | int(10) | Y | - | 0 | - | 会员ID |
| admin_id | int(10) | Y | - | 0 | - | 管理员ID |
| category_id | int(10) unsigned | Y | - | 0 | - | 分类ID(单选) |
| category_ids | varchar(100) | Y | - | NULL | - | 分类ID(多选) |
| tags | varchar(255) | Y | - |  | - | 标签 |
| week | enum('monday','tuesday','wednesday') | Y | - | NULL | - | 星期(单选):monday=星期一,tuesday=星期二,wednesday=星期三 |
| flag | set('hot','index','recommend') | Y | - |  | - | 标志(多选):hot=热门,index=首页,recommend=推荐 |
| genderdata | enum('male','female') | Y | - | male | - | 性别(单选):male=男,female=女 |
| hobbydata | set('music','reading','swimming') | Y | - | NULL | - | 爱好(多选):music=音乐,reading=读书,swimming=游泳 |
| title | varchar(100) | Y | - |  | - | 标题 |
| content | text | Y | - | NULL | - | 内容 |
| image | varchar(100) | Y | - |  | - | 图片 |
| images | varchar(1500) | Y | - |  | - | 图片组 |
| attachfile | varchar(100) | Y | - |  | - | 附件 |
| keywords | varchar(255) | Y | - |  | - | 关键字 |
| description | varchar(255) | Y | - |  | - | 描述 |
| city | varchar(100) | Y | - |  | - | 省市 |
| array | varchar(255) | Y | - |  | - | 数组:value=值 |
| json | varchar(255) | Y | - |  | - | 配置:key=名称,value=值 |
| multiplejson | varchar(1500) | Y | - |  | - | 二维数组:title=标题,intro=介绍,author=作者,age=年龄 |
| price | decimal(10,2) unsigned | Y | - | 0.00 | - | 价格 |
| views | int(10) unsigned | Y | - | 0 | - | 点击 |
| workrange | varchar(100) | Y | - |  | - | 时间区间 |
| startdate | date | Y | - | NULL | - | 开始日期 |
| activitytime | datetime | Y | - | NULL | - | 活动时间(datetime) |
| year | year(4) | Y | - | NULL | - | 年 |
| times | time | Y | - | NULL | - | 时间 |
| refreshtime | bigint(16) | Y | - | NULL | - | 刷新时间 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |
| deletetime | bigint(16) | Y | - | NULL | - | 删除时间 |
| weigh | int(10) | Y | - | 0 | - | 权重 |
| switch | tinyint(1) | Y | - | 0 | - | 开关 |
| status | enum('normal','hidden') | Y | - | normal | - | 状态 |
| state | enum('0','1','2') | Y | - | 1 | - | 状态值:0=禁用,1=正常,2=推荐 |

### fa_user (会员表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | ID |
| group_id | int(10) unsigned | N | - | 0 | - | 组别ID |
| username | varchar(32) | Y | MUL |  | - | 用户名 |
| nickname | varchar(50) | Y | - |  | - | 昵称 |
| password | varchar(32) | Y | - |  | - | 密码 |
| salt | varchar(30) | Y | - |  | - | 密码盐 |
| email | varchar(100) | Y | MUL |  | - | 电子邮箱 |
| mobile | varchar(11) | Y | MUL |  | - | 手机号 |
| avatar | varchar(255) | Y | - |  | - | 头像 |
| level | tinyint(1) unsigned | N | - | 0 | - | 等级 |
| gender | tinyint(1) unsigned | N | - | 0 | - | 性别 |
| birthday | date | Y | - | NULL | - | 生日 |
| bio | varchar(100) | Y | - |  | - | 格言 |
| money | decimal(10,2) | N | - | 0.00 | - | 余额 |
| score | int(10) | N | - | 0 | - | 积分 |
| successions | int(10) unsigned | N | - | 1 | - | 连续登录天数 |
| maxsuccessions | int(10) unsigned | N | - | 1 | - | 最大连续登录天数 |
| prevtime | bigint(16) | Y | - | NULL | - | 上次登录时间 |
| logintime | bigint(16) | Y | - | NULL | - | 登录时间 |
| loginip | varchar(50) | Y | - |  | - | 登录IP |
| loginfailure | tinyint(1) unsigned | N | - | 0 | - | 失败次数 |
| loginfailuretime | bigint(16) | Y | - | NULL | - | 最后登录失败时间 |
| joinip | varchar(50) | Y | - |  | - | 加入IP |
| jointime | bigint(16) | Y | - | NULL | - | 加入时间 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |
| token | varchar(50) | Y | - |  | - | Token |
| status | varchar(30) | Y | - |  | - | 状态 |
| verification | varchar(255) | Y | - |  | - | 验证 |
| roleType | int(11) | N | - | 1 | - | 角色类型1代表居间人，2代表工厂端 |
| agentType | int(11) | N | - | 1 | - | 居间人类型1：个人居间人，2：公司居间人 |
| businessLicense | varchar(255) | Y | - | NULL | - | 营业执照图片地址 |
| isIntermediary | int(11) | N | - | 0 | - | 是否是居间人0：不是居间人,1：是居间人 |
| isFactory | int(11) | N | - | 0 | - | 是否是工厂身份，0：不是工厂身份；1：是工厂身份 |
| idCardFrontImgUrl | varchar(255) | Y | - | NULL | - | 身份证正面照片 |
| idCardBackImgUrl | varchar(255) | Y | - | NULL | - | 身份证背面照片 |

### fa_user_group (会员组表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| name | varchar(50) | Y | - |  | - | 组名 |
| rules | text | Y | - | NULL | - | 权限节点 |
| createtime | bigint(16) | Y | - | NULL | - | 添加时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |
| status | enum('normal','hidden') | Y | - | NULL | - | 状态 |

### fa_user_money_log (会员余额变动表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | - | 0 | - | 会员ID |
| money | decimal(10,2) | N | - | 0.00 | - | 变更余额 |
| before | decimal(10,2) | N | - | 0.00 | - | 变更前余额 |
| after | decimal(10,2) | N | - | 0.00 | - | 变更后余额 |
| memo | varchar(255) | Y | - |  | - | 备注 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |

### fa_user_rule (会员规则表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| pid | int(10) | Y | - | NULL | - | 父ID |
| name | varchar(50) | Y | - | NULL | - | 名称 |
| title | varchar(50) | Y | - |  | - | 标题 |
| remark | varchar(100) | Y | - | NULL | - | 备注 |
| ismenu | tinyint(1) | Y | - | NULL | - | 是否菜单 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |
| weigh | int(10) | Y | - | 0 | - | 权重 |
| status | enum('normal','hidden') | Y | - | NULL | - | 状态 |

### fa_user_score_log (会员积分变动表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | - | 0 | - | 会员ID |
| score | int(10) | N | - | 0 | - | 变更积分 |
| before | int(10) | N | - | 0 | - | 变更前积分 |
| after | int(10) | N | - | 0 | - | 变更后积分 |
| memo | varchar(255) | Y | - |  | - | 备注 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |

### fa_user_token (会员Token表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| token | varchar(50) | N | PRI | NULL | - | Token |
| user_id | int(10) unsigned | N | - | 0 | - | 会员ID |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| expiretime | bigint(16) | Y | - | NULL | - | 过期时间 |

### fa_version (版本表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(11) | N | PRI | NULL | auto_increment | ID |
| oldversion | varchar(30) | Y | - |  | - | 旧版本号 |
| newversion | varchar(30) | Y | - |  | - | 新版本号 |
| packagesize | varchar(30) | Y | - |  | - | 包大小 |
| content | varchar(500) | Y | - |  | - | 升级内容 |
| downloadurl | varchar(255) | Y | - |  | - | 下载地址 |
| enforce | tinyint(1) unsigned | N | - | 0 | - | 强制更新 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |
| weigh | int(10) | N | - | 0 | - | 权重 |
| status | varchar(30) | Y | - |  | - | 状态 |

### fa_xiluxc_advice (投诉建议)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(11) | N | MUL | 0 | - | 用户 |
| content | varchar(500) | N | - |  | - | 建议 |
| images | varchar(2000) | N | - |  | - | 图片 |
| reply_status | tinyint(1) unsigned | N | - | 0 | - | 回复状态:0=待处理,1=已处理 |
| reply_content | varchar(255) | N | - |  | - | 回复内容 |
| replytime | bigint(16) | Y | - | NULL | - | 回复时间 |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 处理时间 |
| deletetime | bigint(16) | Y | - | NULL | - | 删除时间 |

### fa_xiluxc_aftersale (售后退款)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | ID |
| aftersale_type | enum('service','package') | Y | - | service | - | 售后类型:service=服务,package=套餐 |
| order_no | char(32) | Y | - | NULL | - | 售后订单号 |
| user_id | int(10) unsigned | N | - | 0 | - | 用户ID，服务时是申请人 |
| user_package_id | int(10) unsigned | N | MUL | 0 | - | 套餐ID |
| order_id | int(10) unsigned | N | MUL | 0 | - | 套餐关联订单 |
| refund_money | decimal(10,2) unsigned | N | - | 0.00 | - | 退款金额 |
| reason | varchar(255) | N | - |  | - | 理由 |
| status | enum('-1','0','1') | N | - | 0 | - | 退款状态:-1=拒绝,0=申请退款,1=同意 |
| refuse_reason | varchar(255) | N | - |  | - | 拒绝原因 |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 门店ID |
| brand_id | int(10) unsigned | N | - | 0 | - | 品牌ID |
| agreetime | bigint(16) | Y | - | NULL | - | 同意申请时间 |
| refusetime | bigint(16) | Y | - | NULL | - | 拒绝时间 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |
| deletetime | bigint(16) | Y | - | NULL | - | 删除时间 |

### fa_xiluxc_area (地区表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) | N | PRI | NULL | auto_increment | ID |
| pid | int(10) | Y | MUL | NULL | - | 父id |
| shortname | varchar(100) | Y | - | NULL | - | 简称 |
| name | varchar(100) | Y | - | NULL | - | 名称 |
| mergename | varchar(255) | Y | - | NULL | - | 全称 |
| level | tinyint(4) | Y | - | NULL | - | 层级:1=省,2=市,3=区/县 |
| pinyin | varchar(100) | Y | - | NULL | - | 拼音 |
| code | varchar(100) | Y | - | NULL | - | 长途区号 |
| zip | varchar(100) | Y | - | NULL | - | 邮编 |
| first | varchar(50) | Y | - | NULL | - | 首字母 |
| lng | varchar(100) | Y | - | NULL | - | 经度 |
| lat | varchar(100) | Y | - | NULL | - | 纬度 |
| is_re | int(10) | Y | - | 0 | - | - |
| deletetime | varchar(100) | Y | - | NULL | - | - |
| status | varchar(200) | Y | - | NULL | - | - |

### fa_xiluxc_areaCopy (地区表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) | N | PRI | NULL | auto_increment | ID |
| pid | int(10) | Y | MUL | NULL | - | 父id |
| shortname | varchar(100) | Y | - | NULL | - | 简称 |
| name | varchar(100) | Y | - | NULL | - | 名称 |
| mergename | varchar(255) | Y | - | NULL | - | 全称 |
| level | tinyint(4) | Y | - | NULL | - | 层级:1=省,2=市,3=区/县 |
| pinyin | varchar(100) | Y | - | NULL | - | 拼音 |
| code | varchar(100) | Y | - | NULL | - | 长途区号 |
| zip | varchar(100) | Y | - | NULL | - | 邮编 |
| first | varchar(50) | Y | - | NULL | - | 首字母 |
| lng | varchar(100) | Y | - | NULL | - | 经度 |
| lat | varchar(100) | Y | - | NULL | - | 纬度 |
| is_re | int(10) | Y | - | 0 | - | - |
| deletetime | varchar(100) | Y | - | NULL | - | - |
| status | varchar(200) | Y | - | NULL | - | - |

### fa_xiluxc_banner (图片banner)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| minapp_url | varchar(255) | N | - |  | - | 小程序链接 |
| thumb_image | varchar(255) | N | - |  | - | 图片 |
| group | varchar(50) | N | - |  | - | 分组 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=显示 |
| weigh | int(10) unsigned | N | - | 0 | - | 排序 |
| memo | varchar(255) | N | - |  | - | 备注 |
| show_count | int(10) unsigned | N | - | 0 | - | - |
| createtime | bigint(16) | Y | - | NULL | - | - |
| updatetime | bigint(16) | Y | - | NULL | - | - |
| deletetime | bigint(16) | Y | - | NULL | - | - |

### fa_xiluxc_car_brand (汽车品牌)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| name | varchar(255) | N | - |  | - | 品牌名 |
| image | varchar(255) | N | - |  | - | 品牌LOGO |
| first_letter | varchar(2) | N | - |  | - | 首字母 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=显示 |
| weigh | int(10) unsigned | N | - | 0 | - | 排序 |
| brand_id | int(10) unsigned | N | - | 0 | - | 三方ID |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |
| deletetime | bigint(16) | Y | - | NULL | - | 删除时间 |

### fa_xiluxc_car_models (汽车型号)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| series_id | int(10) unsigned | N | MUL | 0 | - | 车系ID |
| name | varchar(255) | N | - |  | - | 车系名称 |
| year | varchar(10) | N | - |  | - | 年份 |
| peizhi | varchar(50) | N | - |  | - | 配置 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=显示 |
| weigh | int(10) unsigned | N | - | 0 | - | 排序 |
| models_id | int(10) unsigned | N | - | 0 | - | 型号ID |
| createtime | bigint(16) | Y | - | NULL | - | - |
| updatetime | bigint(16) | Y | - | NULL | - | - |
| deletetime | bigint(16) | Y | - | NULL | - | - |

### fa_xiluxc_car_series (汽车型号)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| name | varchar(255) | N | - |  | - | 车系名 |
| brand_id | int(10) unsigned | N | MUL | 0 | - | 品牌 |
| levelid | smallint(6) unsigned | N | - | 1 | - | 车系类型:1=微型车,2=小型车,3=紧凑型车,4=中型车,5=中大型车,6=大型车,7=跑车,8=MPV,11=微面,12=微卡,13=轻客,14=低端皮卡,15=高端皮卡,16=小型SUV,17=紧凑型SUV,18=中型SUV,19=中大型SUV,20=大型SUV,21=紧凑型MPV |
| levelname | varchar(50) | N | - |  | - | 车系类型名称 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=显示 |
| weigh | int(10) unsigned | N | - | 0 | - | 排序 |
| series_id | int(10) unsigned | N | - | 0 | - | 系列ID |
| createtime | bigint(16) | Y | - | NULL | - | - |
| updatetime | bigint(16) | Y | - | NULL | - | - |
| deletetime | bigint(16) | Y | - | NULL | - | - |

### fa_xiluxc_config (基础配置)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| name | varchar(30) | Y | UNI |  | - | 变量名 |
| group | varchar(30) | Y | - |  | - | 分组 |
| title | varchar(100) | Y | - |  | - | 变量标题 |
| tip | varchar(100) | Y | - |  | - | 变量描述 |
| type | varchar(30) | Y | - |  | - | 类型:string,text,int,bool,array,datetime,date,file |
| visible | varchar(255) | Y | - |  | - | 可见条件 |
| value | text | Y | - | NULL | - | 变量值 |
| content | text | Y | - | NULL | - | 变量字典数据 |
| rule | varchar(100) | Y | - |  | - | 验证规则 |
| extend | varchar(255) | Y | - |  | - | 扩展属性 |
| setting | varchar(255) | Y | - |  | - | 配置 |

### fa_xiluxc_coupon (优惠券管理)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 门店 |
| name | varchar(255) | N | - |  | - | 名称 |
| use_start_time | bigint(16) | Y | - | NULL | - | 使用开始时间 |
| use_end_time | bigint(16) | Y | - | NULL | - | 使用结束时间 |
| max_count | int(10) unsigned | N | - | 0 | - | 发行数量 |
| at_least | decimal(10,0) | N | - | 0 | - | 使用门槛 |
| type | enum('1','2') | Y | - | 1 | - | 类型:1=满减,2=折扣 |
| discount | tinyint(4) unsigned | N | - | 0 | - | 折扣（折扣类型必填） |
| money | decimal(10,0) | N | - | 0 | - | 减免金额 |
| freight_type | tinyint(2) unsigned | N | - | 1 | - | 发放形式:1=手动领取,2=注册赠送 |
| range_type | tinyint(2) unsigned | N | - | 1 | - | 使用类型:1=服务,2=套餐 |
| range_status | tinyint(2) unsigned | N | - | 0 | - | 使用范围:0=部分,1=全部 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=下架,normal=上架 |
| receive_count | int(10) unsigned | N | - | 0 | - | 领取数量 |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_coupon_items (优惠券使用范围)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| coupon_id | int(10) unsigned | N | MUL | 0 | - | 优惠券id |
| target_id | int(10) unsigned | N | - | 0 | - | 参数 |
| createtime | bigint(16) | Y | - | NULL | - | 时间 |

### fa_xiluxc_divide (分销基础信息)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| type | varchar(20) | N | - |  | - | 类型:vip_order=会员卡,service_order=服务3=套餐 |
| user_id | int(10) unsigned | N | - | 0 | - | 下单用户ID |
| order_id | int(10) unsigned | N | - | 0 | - | 订单ID |
| order_money | decimal(10,2) unsigned | N | - | 0.00 | - | 订单金额 |
| ready_money | decimal(10,2) unsigned | N | - | 0.00 | - | 已分金额 |
| shop_money | decimal(10,2) unsigned | N | - | 0.00 | - | 分销金额（门店获得金额） |
| platform_rate | tinyint(4) unsigned | N | - | 0 | - | 平台费率 |
| platform_money | decimal(10,2) unsigned | N | - | 0.00 | - | 平台收益 |
| first_user_id | int(10) unsigned | N | MUL | 0 | - | 一级用户 |
| first_rate | tinyint(4) unsigned | N | - | 0 | - | 一级比例 |
| first_money | decimal(10,2) unsigned | N | - | 0.00 | - | 一级佣金 |
| second_user_id | int(10) unsigned | N | MUL | 0 | - | 二级佣金 |
| second_rate | tinyint(4) unsigned | N | - | 0 | - | 二级比例 |
| second_money | decimal(10,2) unsigned | N | - | 0.00 | - | 二级佣金 |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 门店ID |
| status | enum('0','1','2','3') | N | - | 1 | - | 状态:0=失效,1=冻结中,2=部分退款,3=正常 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| unfreezetime | bigint(16) | Y | - | NULL | - | 解冻时间 |
| canceltime | bigint(16) | Y | - | NULL | - | 取消时间 |

### fa_xiluxc_money_log (会员余额变动表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| type | tinyint(1) unsigned | N | - | 1 | - | 类型:1=余额,2=佣金,3=门店 |
| event | varchar(20) | N | - |  | - | 具体类型 |
| shop_id | int(10) unsigned | N | - | 0 | - | 门店 |
| user_id | int(10) unsigned | N | MUL | 0 | - | 会员ID |
| divide_id | int(10) unsigned | N | - | 0 | - | 冻结金额ID |
| order_id | int(10) unsigned | N | - | 0 | - | 订单ID |
| withdraw_id | int(10) unsigned | N | - | 0 | - | 提现ID |
| money | decimal(10,2) | N | - | 0.00 | - | 变更余额 |
| before | decimal(10,2) | N | - | 0.00 | - | 变更前余额 |
| after | decimal(10,2) | N | - | 0.00 | - | 变更后余额 |
| memo | varchar(255) | Y | - |  | - | 备注 |
| status | tinyint(2) | N | - | 1 | - | 状态:-1=作废,0=冻结,1=正常 |
| extra | varchar(600) | Y | - | NULL | - | - |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |

### fa_xiluxc_navigation (金刚区)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| name | varchar(50) | N | - |  | - | 名称 |
| icon_image | varchar(255) | N | - |  | - | 图标 |
| type | tinyint(2) unsigned | N | - | 1 | - | 类型:1=内链,2=外链,3=其他小程序 |
| mini_appid | varchar(50) | N | - |  | - | 第三方APPID |
| jump_type | tinyint(4) | N | - | 1 | - | 跳转:1=navigate,2=switchtab |
| url | varchar(255) | N | - |  | - | 链接 |
| status | enum('hidden','normal') | N | - | hidden | - | 状态:hidden=隐藏,normal=正常 |
| weigh | int(10) unsigned | N | - | 0 | - | 排序 |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | - |

### fa_xiluxc_news (知识库管理)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) | N | PRI | NULL | auto_increment | ID |
| category_id | int(10) unsigned | N | - | 0 | - | 分类 |
| name | varchar(255) | N | - |  | - | 标题 |
| image | varchar(100) | N | - |  | - | 封面图 |
| description | varchar(255) | N | - |  | - | 描述 |
| content | longtext | Y | - | NULL | - | 内容 |
| weigh | int(10) unsigned | Y | - | 0 | - | 权重 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=显示 |
| view_num | int(10) unsigned | N | - | 0 | - | 阅读量 |
| favorite_num | int(10) unsigned | N | - | 0 | - | 点赞量 |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) unsigned | Y | - | NULL | - | 更新时间 |
| deletetime | bigint(16) unsigned | Y | - | NULL | - | 删除时间 |

### fa_xiluxc_news_category (知识库分类)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| name | varchar(30) | N | - |  | - | 名称 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=正常 |
| weigh | int(10) unsigned | N | - | 0 | - | 排序 |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_notice (平台公告)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| title | varchar(255) | N | - |  | - | 标题 |
| content | text | N | - | NULL | - | 内容 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=正常 |
| view_num | int(10) unsigned | N | - | 0 | - | 查看人数 |
| weigh | int(10) unsigned | N | - | 0 | - | 排序 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_order (订单管理)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) | N | PRI | NULL | auto_increment | - |
| type | enum('service','package') | N | - | service | - | 订单类型:service=服务订单,package=套餐订单 |
| order_no | varchar(60) | N | UNI |  | - | 订单号 |
| order_trade_no | varchar(40) | N | - |  | - | 商户交易单号 |
| user_id | int(10) unsigned | N | MUL | 0 | - | 用户 |
| pay_type | tinyint(4) unsigned | N | - | 1 | - | 支付类型:1=微信,2=余额,3=套餐 |
| order_amount | decimal(10,2) unsigned | N | - | 0.00 | - | 订单总金额 |
| shop_fee | decimal(10,2) unsigned | N | - | 0.00 | - | 商家金额（扣除优惠券） |
| points | int(10) unsigned | N | - | 0 | - | 积分 |
| points_fee | decimal(10,2) unsigned | N | - | 0.00 | - | 积分抵扣金额 |
| pay_fee | decimal(10,2) unsigned | N | - | 0.00 | - | 支付总金额 |
| status | enum('closed','cancel','unpaid','paid') | N | MUL | unpaid | - | 订单状态:closed=交易关闭,cancel=已取消,unpaid=未支付,paid=已支付,completed=已完成,pending=待定 |
| appoint_date | bigint(16) | Y | - | NULL | - | 预约日期 |
| paid_time | bigint(16) | Y | - | NULL | - | 支付成功时间 |
| refund_status | tinyint(4) | N | - | 0 | - | 申请退款状态:0=未申请,1=用户申请退款,-1=拒绝申请,2=退款成功 |
| coupon_discount_fee | decimal(10,1) | N | - | 0.0 | - | 优惠券抵扣金额 |
| coupon_id | int(11) | N | - | 0 | - | 优惠券 |
| ext | varchar(2048) | Y | - | NULL | - | 附加信息 |
| remark | varchar(255) | Y | - | NULL | - | 用户备注 |
| platform | enum('h5','app','wxmini','wxoffical','pc') | Y | - | NULL | - | 平台:h5=H5,wxofficial=微信公众号,wxmini=微信小程序,app=App,pc=PC |
| shop_id | int(10) unsigned | Y | - | 0 | - | 门店ID |
| brand_id | int(10) unsigned | N | - | 0 | - | 品牌 |
| user_package_id | int(10) unsigned | N | - | 0 | - | 使用的套餐ID |
| comment_status | tinyint(2) unsigned | N | - | 0 | - | 评价状态:0=带评价,1=已评价 |
| commenttime | bigint(16) | Y | - | NULL | - | 评价时间 |
| verify_status | tinyint(2) unsigned | N | - | 0 | - | 核销状态:0=待核销,1=已核销 |
| verifytime | bigint(16) | Y | - | NULL | - | 核销时间 |
| trade_no | varchar(30) | Y | - | NULL | - | 交易单号 |
| order_ip | varchar(30) | N | - |  | - | 下单IP |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |
| deletetime | bigint(16) | Y | - | NULL | - | 删除时间 |

### fa_xiluxc_order_coupon (订单优惠券)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| order_id | int(10) unsigned | N | MUL | 0 | - | 订单ID |
| coupon_id | int(10) unsigned | N | - | 0 | - | 优惠券ID |
| coupon_name | varchar(50) | N | - | 0.00 | - | 优惠券名称 |
| coupon_money | decimal(10,0) unsigned | N | - | 0 | - | 优惠券金额 |
| at_least | decimal(10,0) unsigned | N | - | 0 | - | 门槛 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |

### fa_xiluxc_order_item (订单商品)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| order_id | int(10) unsigned | N | MUL | 0 | - | 订单 |
| user_id | int(10) unsigned | N | MUL | 0 | - | 用户 |
| data_id | int(10) unsigned | N | MUL | 0 | - | 商品 |
| title | varchar(255) | Y | - | NULL | - | 商品名称 |
| service_price_id | int(10) unsigned | N | - | 0 | - | 服务规格ID |
| branch_service_id | int(10) | N | - | 0 | - | 分店服务ID |
| sku_text | varchar(50) | N | - |  | - | 规格名 |
| image | varchar(255) | Y | - | NULL | - | 商品图片 |
| salesprice | decimal(10,2) unsigned | Y | - | 0.00 | - | 商品价格 |
| vip_price | decimal(10,2) unsigned | Y | - | 0.00 | - | 会员价格 |
| discount_fee | decimal(10,2) unsigned | N | - | 0.00 | - | 优惠费用 |
| ext | text | N | - | NULL | - | 扩展数据 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_order_log (订单日志)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | ID |
| order_type | tinyint(2) unsigned | N | - | 0 | - | 订单类型:1=服务,2=套餐 |
| order_id | int(10) unsigned | N | - | 0 | - | 订单id |
| aftersale_id | int(10) unsigned | N | - | 0 | - | 售后订单ID |
| title | varchar(255) | N | - |  | - | 售后标题 |
| operate_type | enum('user','admin') | Y | - | user | - | 操作人类型 |
| operate_id | int(10) unsigned | Y | - | 0 | - | 操作人id |
| description | varchar(500) | N | - |  | - | 描述 |
| images | varchar(1500) | N | - |  | - | 图片 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_order_qrcode (服务订单核销码)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| order_id | int(10) unsigned | N | MUL | 0 | - | 订单ID |
| qrcode | varchar(255) | N | - |  | - | 核销码 |
| verifier_status | tinyint(2) unsigned | N | - | 0 | - | 核销状态:0=未核销,1=已核销 |
| verifytime | bigint(16) | Y | - | NULL | - | 核销时间 |
| verifier_id | int(10) unsigned | N | - | 0 | - | 核销人 |
| user_package_id | int(10) unsigned | N | - | 0 | - | 套餐ID |
| code | varchar(30) | N | - |  | - | 券码 |
| token | varchar(50) | N | - |  | - | 核销token |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |

### fa_xiluxc_property (属性管理)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| name | varchar(30) | N | - |  | - | 名称 |
| image | varchar(255) | N | - |  | - | 图标 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=正常 |
| weigh | int(10) unsigned | N | - | 0 | - | 排序 |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_recharge (充值金额)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| brand_id | int(10) unsigned | N | - | 0 | - | 品牌ID |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 门店 |
| money | int(10) unsigned | N | - | 0 | - | 充值金额 |
| extra_money | int(10) unsigned | N | - | 0 | - | 额外赠送 |
| weigh | int(10) unsigned | N | - | 0 | - | 权重 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=正常 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_recharge_order (充值订单)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | MUL | 0 | - | 下单用户 |
| platform | varchar(10) | N | - | 1 | - | 下单平台:1=wxmini,2=app |
| recharge_id | int(10) unsigned | N | - | 0 | - | 金额id |
| order_no | varchar(50) | N | - |  | - | 订单号 |
| order_trade_no | varchar(32) | N | - |  | - | 交易单号 |
| pay_type | tinyint(2) unsigned | N | - | 1 | - | 支付类型:1=微信,2=支付宝,3=后台人工 |
| pay_fee | decimal(10,0) unsigned | N | - | 0 | - | 支付金额 |
| pay_status | enum('unpaid','paid') | N | - | unpaid | - | 支付状态:unpaid=待支付,paid=已支付 |
| paytime | bigint(16) | Y | - | NULL | - | 支付时间 |
| trade_no | varchar(32) | N | - |  | - | 三方单号 |
| recharge_money | decimal(10,0) unsigned | N | - | 0 | - | 充值金额 |
| recharge_extra_money | decimal(10,0) unsigned | N | - | 0 | - | 充值赠送金额 |
| recharge_total_money | decimal(10,0) unsigned | N | - | 0 | - | 到账金额 |
| real_price | decimal(10,2) unsigned | N | - | 0.00 | - | 实际支付金额 |
| ip | varchar(20) | N | - |  | - | 下单id |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |
| admin_id | int(10) | N | - | NULL | - | 管理员 |
| shop_id | int(10) unsigned | N | - | 0 | - | 门店 |
| brand_id | int(10) unsigned | N | - | 0 | - | 品牌ID |

### fa_xiluxc_score_log (会员积分变动表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| event | varchar(20) | N | - |  | - | 具体类型 |
| shop_id | int(10) unsigned | N | - | 0 | - | 门店 |
| user_id | int(10) unsigned | N | MUL | 0 | - | 会员ID |
| divide_id | int(10) unsigned | N | - | 0 | - | 冻结金额ID |
| order_id | int(10) unsigned | N | - | 0 | - | 订单ID |
| score | decimal(10,0) | N | - | 0 | - | 变更积分 |
| before | decimal(10,0) | N | - | 0 | - | 变更前积分 |
| after | decimal(10,0) | N | - | 0 | - | 变更后积分 |
| memo | varchar(255) | Y | - |  | - | 备注 |
| status | tinyint(2) | N | - | 1 | - | 状态:-1=作废,0=冻结,1=正常 |
| extra | varchar(600) | Y | - | NULL | - | - |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |

### fa_xiluxc_service (服务管理)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| name | varchar(30) | N | - |  | - | 名称 |
| image | varchar(255) | N | - |  | - | 图标 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=正常 |
| weigh | int(10) unsigned | N | - | 0 | - | 排序 |
| distribution_one_rate | smallint(3) unsigned | N | - | 0 | - | 一级返佣比例 |
| distribution_two_rate | smallint(3) unsigned | N | - | 0 | - | 二级返佣比例 |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_service_comment (服务评价)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | MUL | 0 | - | 用户 |
| order_id | int(10) unsigned | N | - | 0 | - | 订单 |
| order_item_id | int(10) unsigned | N | MUL | 0 | - | 订单服务ID |
| service_id | int(10) unsigned | N | - | 0 | - | 服务 |
| shop_service_id | int(10) unsigned | N | - | 0 | - | 门店服务 |
| service_price_id | int(10) unsigned | N | - | 0 | - | 服务规格 |
| shop_id | int(10) unsigned | N | - | 0 | - | 门店 |
| images | varchar(2000) | N | - |  | - | 图片 |
| content | varchar(255) | N | - |  | - | 评价内容 |
| status | enum('normal','hidden') | N | - | normal | - | 状态:hidden=隐藏,normal=上架 |
| avg_star | float(2,1) unsigned | N | - | 0.0 | - | 平均分 |
| service_star | tinyint(2) unsigned | N | - | 0 | - | 服务评价 |
| comprehensive_star | tinyint(2) | Y | - | NULL | - | 综合评价 |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 发布时间 |
| updatetime | bigint(16) unsigned | Y | - | NULL | - | 更新时间 |
| deletetime | bigint(16) unsigned | Y | - | NULL | - | 删除时间 |

### fa_xiluxc_shop (门店管理)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | MUL | 0 | - | 账号ID |
| name | varchar(255) | N | - |  | - | 名称 |
| connector | varchar(255) | N | - |  | - | 联系人名称 |
| type | enum('1','2') | N | - | 1 | - | 类型:1=普通店,2=连锁店 |
| concat_mobile | varchar(20) | N | - |  | - | 联系电话 |
| province_id | int(10) unsigned | N | - | 0 | - | 省id |
| city_id | int(10) unsigned | N | MUL | 0 | - | 市id |
| district_id | int(10) unsigned | N | - | 0 | - | 区/县id |
| address | varchar(50) | N | - |  | - | 详细地址 |
| lng | decimal(10,6) unsigned | N | - | 0.000000 | - | 经度 |
| lat | decimal(10,6) unsigned | N | - | 0.000000 | - | 纬度 |
| legal_person | varchar(50) | N | - |  | - | 法人 |
| legal_idcard | varchar(20) | N | - |  | - | 法人身份证 |
| description | varchar(500) | N | - |  | - | 简单介绍 |
| license_image | varchar(255) | N | - |  | - | 营业执照 |
| images | varchar(2000) | N | - |  | - | 场馆图片 |
| image | varchar(255) | N | - |  | - | 主图 |
| starttime | varchar(10) | Y | - | NULL | - | 营业时间 |
| endtime | varchar(10) | Y | - | NULL | - | 营业结束时间 |
| point | decimal(2,1) unsigned | N | - | 5.0 | - | 评分 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=正常 |
| sales | int(10) unsigned | N | - | 0 | - | 销量 |
| audit_status | enum('checked','passed','failed') | N | - | checked | - | 审核状态:checked=审核中,passed=通过,failed=已拒绝 |
| refuse_reason | varchar(255) | Y | - |  | - | 拒绝理由 |
| audittime | bigint(16) | Y | - | NULL | - | 审核时间 |
| brand_id | int(10) unsigned | N | - | 0 | - | 归属的品牌账号ID |
| weigh | int(10) unsigned | N | - | 0 | - | 排序 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_shop_account (门店账户表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 用户id |
| vip_rate | smallint(3) unsigned | N | - | 0 | - | 会员费率 |
| rate | smallint(3) unsigned | N | - | 0 | - | 服务费率 |
| total_money | decimal(10,2) unsigned | N | - | 0.00 | - | 累计佣金 |
| money | decimal(10,2) unsigned | N | - | 0.00 | - | 余额 |
| withdraw_money | decimal(10,2) unsigned | N | - | 0.00 | - | 累计提现金额 |
| platform_message | int(10) unsigned | N | - | 0 | - | 活动消息 |
| system_message | int(10) unsigned | N | - | 0 | - | 系统消息 |
| user_message | int(10) unsigned | Y | - | 0 | - | 个人消息 |

### fa_xiluxc_shop_branch_package (分店套餐)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| shop_id | int(10) unsigned | N | - | 0 | - | 门店ID |
| shop_package_id | int(10) unsigned | N | MUL | 0 | - | 门店（品牌）套餐ID |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=正常 |
| sales | int(10) unsigned | N | - | 0 | - | 销量 |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_shop_branch_service (分店服务)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| shop_id | int(10) unsigned | N | - | 0 | - | 门店ID |
| service_id | int(10) unsigned | N | - | 0 | - | 服务ID |
| shop_service_id | int(10) unsigned | N | MUL | 0 | - | 门店（品牌）服务ID |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=正常 |
| sales | int(10) unsigned | N | - | 0 | - | 销量 |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_shop_brand (品牌信息)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | - | 0 | - | 账号ID |
| brand_name | varchar(50) | N | - |  | - | 品牌名 |
| logo | varchar(255) | N | - |  | - | 品牌LOGO |
| description | varchar(500) | N | - |  | - | 品牌介绍 |
| concat_name | varchar(30) | N | - |  | - | 联系人 |
| contact_mobile | varchar(20) | N | - |  | - | 联系电话 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=显示 |
| weigh | int(10) unsigned | N | - | 0 | - | 排序 |
| createtime | bigint(16) | Y | - | NULL | - | - |
| updatetime | bigint(16) | Y | - | NULL | - | - |
| deletetime | bigint(16) | Y | - | NULL | - | - |

### fa_xiluxc_shop_cameralog (门店管理)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 洗车店id |
| name | varchar(255) | Y | - | NULL | - | 相机工位名称 |
| img | text | Y | - | NULL | - | 当前图片base64 |
| carColor | varchar(20) | Y | - | NULL | - | 车辆颜色 |
| license | varchar(50) | Y | - | NULL | - | 车牌号码 |
| cardType | varchar(50) | Y | - | NULL | - | 车牌类型0 未知车牌
1 蓝牌
2 黑牌
3 单排黄牌
4 双排黄牌（ 大车尾牌， 农用车）
5 警车车牌
6 武警车牌
7 个性化车牌
8 单排军车
9 双排军车
10 使馆牌
11 香港牌
12 拖拉机
13 澳门牌
14 厂内牌
15 民航牌
16 领事领馆车
17 新能源车牌-小型车
18 新能源车牌-大型车 |
| platecolor | varchar(50) | Y | - | NULL | - | 车牌颜色 |
| cartype | int(10) | Y | - | NULL | - | 车型：0 未知 1 小轿车
2 SUV
3 面包车
4 中巴车
5 大巴车
6 小货车
7 大货车 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_shop_device (门店管理)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 洗车店id |
| name | varchar(255) | N | - |  | - | 名称 |
| status | varchar(10) | N | - | 0 | - | 0:空闲，1：使用中 |
| doorindex | int(11) | N | - | NULL | - | 当前门编号 |
| cur_userid | int(11) | N | - | NULL | - | 当前洗车的用户id |
| cur_feetype | varchar(10) | N | - | 0 | - | 当前消费类型0：余额，1：券 |
| cartype | int(11) | N | - | NULL | - | 车类型，1：小轿车，2：两轮三轮车 |
| fee | decimal(10,2) | N | - | NULL | - | 已扣费用 |
| firstfee | decimal(10,2) | N | - | NULL | - | 第一阶段费用：前600秒8块钱 |
| firsttime | int(10) | N | - | NULL | - | 第一阶段的时间：单位秒 |
| secondfee | decimal(10,2) | N | - | NULL | - | 第二阶段费用: 元/60s |
| coupon_douyin | int(10) | N | - | NULL | - | 抖音优惠券抵扣时间;秒 |
| coupon_meituan | int(10) | N | - | NULL | - | 美团优惠券抵扣时间：秒 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |
| finishtime | bigint(16) | N | - | NULL | - | 核销团购的到期时间 |

### fa_xiluxc_shop_deviceBox (门店管理)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| boxid | int(11) | N | - | NULL | - | 箱子编号 |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 洗车店id |
| status | varchar(10) | N | - | 0 | - | 0:未使用，1：已使用 |
| doorindex | int(11) | N | - | NULL | - | 当前门编号 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |

### fa_xiluxc_shop_device_box (门店管理)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| boxid | int(11) | N | - | NULL | - | 箱子编号 |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 洗车店id |
| status | varchar(10) | N | - | 0 | - | 0:未使用，1：已使用 |
| doorindex | int(11) | N | - | NULL | - | 当前门编号 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| update_time | bigint(16) | Y | - | NULL | - | - |

### fa_xiluxc_shop_package (门店套餐)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 门店ID |
| brand_id | int(10) unsigned | N | - | 0 | - | 品牌ID |
| name | varchar(50) | N | - |  | - | 套餐名称 |
| sub_title | varchar(255) | N | - |  | - | 副标题 |
| image | varchar(255) | N | - |  | - | 图片 |
| salesprice | decimal(10,2) unsigned | N | - | 0.00 | - | 价格 |
| original_price | decimal(10,2) unsigned | N | - | 0.00 | - | 原价 |
| vip_price | decimal(10,2) unsigned | N | - | 0.00 | - | 会员价 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=正常 |
| weigh | int(10) unsigned | N | - | 0 | - | 排序 |
| sales | int(10) unsigned | N | - | 0 | - | 销量 |
| content | mediumtext | N | - | NULL | - | 详情 |
| notice | text | N | - | NULL | - | 套餐说明 |
| distribution_one_rate | smallint(3) unsigned | N | - | 0 | - | 一级返佣比例 |
| distribution_two_rate | smallint(3) unsigned | N | - | 0 | - | 二级返佣比例 |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_shop_package_service (门店套餐服务)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| package_id | int(10) unsigned | N | MUL | 0 | - | 套餐ID |
| shop_service_id | int(10) unsigned | N | - | 0 | - | 门店服务ID |
| service_id | int(10) unsigned | N | - | 0 | - | 服务ID |
| service_price_id | int(10) unsigned | N | - | 0 | - | 服务价格ID |
| use_count | smallint(6) unsigned | N | - | 0 | - | 包含次数 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_shop_property (门店属性)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 门店ID |
| property_id | int(10) unsigned | N | - | 0 | - | 属性ID |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |

### fa_xiluxc_shop_service (门店服务)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 门店ID |
| brand_id | int(10) unsigned | N | - | 0 | - | 品牌ID |
| service_id | int(10) unsigned | N | - | 0 | - | 服务ID |
| sub_title | varchar(255) | N | - |  | - | 简单描述 |
| image | varchar(255) | N | - |  | - | 图片 |
| salesprice | decimal(10,2) unsigned | N | - | 0.00 | - | 价格 |
| vip_price | decimal(10,2) unsigned | N | - | 0.00 | - | 会员价 |
| content | text | N | - | NULL | - | 详情 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=正常 |
| weigh | int(10) unsigned | N | - | 0 | - | 排序 |
| sales | int(10) unsigned | N | - | 0 | - | 销量 |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_shop_service_price (门店服务价格)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 门店ID |
| brand_id | int(10) unsigned | N | - | 0 | - | 品牌ID |
| service_id | int(10) unsigned | N | MUL | 0 | - | 服务ID |
| shop_service_id | int(10) unsigned | N | MUL | 0 | - | 门店服务ID |
| title | varchar(50) | N | - |  | - | 项目名 |
| salesprice | decimal(10,2) unsigned | N | - | 0.00 | - | 价格 |
| vip_price | decimal(10,2) unsigned | N | - | 0.00 | - | 会员价 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=正常 |
| sales | int(10) unsigned | N | - | 0 | - | 销量 |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_shop_tag (门店标签)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 门店ID |
| tag_id | int(10) unsigned | N | - | 0 | - | 标签ID |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |

### fa_xiluxc_shop_user (门店会员表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | - | 0 | - | 用户 |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 门店ID |
| brand_id | int(10) unsigned | N | - | 0 | - | 品牌ID |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_shop_verifier (门店核销员)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | ID |
| username | varchar(32) | Y | MUL |  | - | 用户名 |
| mobile | varchar(11) | Y | UNI |  | - | 手机号 |
| password | varchar(32) | Y | - |  | - | 密码 |
| salt | varchar(30) | Y | - |  | - | 密码盐 |
| status | enum('hidden','normal') | N | - | hidden | - | 状态:hidden=隐藏,normal=正常 |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 所属门店 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_shop_vip (门店会员卡)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 门店ID |
| brand_id | int(10) unsigned | N | - | 0 | - | 品牌ID |
| name | varchar(50) | N | - |  | - | 名称 |
| image | varchar(255) | N | - |  | - | 图片 |
| salesprice | decimal(10,2) unsigned | N | - | 0.00 | - | 价格 |
| original_price | decimal(10,2) unsigned | N | - | 0.00 | - | 原价 |
| days | smallint(6) unsigned | N | - | 0 | - | 天数 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=正常 |
| weigh | int(10) unsigned | N | - | 0 | - | 排序 |
| sales | int(10) unsigned | N | - | 0 | - | 销量 |
| privilege | text | N | - | NULL | - | 权益 |
| vip_agreement | mediumtext | N | - | NULL | - | 会员权益 |
| distribution_one_rate | smallint(3) unsigned | N | - | 0 | - | 一级返佣比例 |
| distribution_two_rate | smallint(3) unsigned | N | - | 0 | - | 二级返佣比例 |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_shop_washlog (门店管理)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| shop_id | int(10) unsigned | N | MUL | 0 | - | 洗车店id |
| user_id | int(11) | N | - | NULL | - | 用户id |
| washtime | varchar(255) | N | - | NULL | - | 洗车时间 |
| cur_feetype | varchar(10) | N | - | 0 | - | 当前消费类型0：余额，1：券 |
| fee | decimal(10,2) | N | - | NULL | - | 已扣费用 |
| coupon_douyin | varchar(100) | Y | - | NULL | - | 抖音优惠券 |
| coupon_meituan | varchar(100) | Y | - | NULL | - | 美团优惠券 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| begintime | varchar(100) | Y | - | NULL | - | 开始洗车时间 |

### fa_xiluxc_shop_withdraw (门店提现)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| type | tinyint(2) unsigned | N | - | 1 | - | 类型:1=支付宝,2=银行卡 |
| user_id | int(10) unsigned | N | - | 0 | - | 提现用户 |
| shop_id | int(10) unsigned | N | - | 0 | - | 提现门店 |
| account | varchar(50) | N | - |  | - | 支付宝 |
| username | varchar(50) | N | - |  | - | 姓名 |
| bank_no | varchar(30) | N | - |  | - | 卡号 |
| bank | varchar(50) | N | - |  | - | 开户行 |
| bank_branch | varchar(80) | N | - |  | - | 开户支行 |
| money | decimal(10,2) | N | - | 0.00 | - | 提现金额 |
| real_money | decimal(10,2) | N | - | 0.00 | - | 实际到账 |
| rate | double(10,2) | N | - | 0.00 | - | 手续费率 |
| rate_money | decimal(10,2) | N | - | 0.00 | - | 手续费 |
| state | tinyint(4) | N | - | 1 | - | 状态:1=审核中,2=处理中,3=已处理,4=已拒绝 |
| reason | varchar(255) | N | - |  | - | 拒绝理由 |
| certificate | varchar(255) | N | - |  | - | 打款凭证 |
| order_no | varchar(50) | N | - |  | - | 提现订单号 |
| checktime | bigint(16) | Y | - | NULL | - | 审核时间 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_singlepage (单页管理)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) | N | PRI | NULL | auto_increment | ID |
| name | varchar(50) | N | - |  | - | 名称 |
| content | text | Y | - | NULL | - | 内容 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=正常 |
| view_num | int(10) unsigned | N | - | 0 | - | 阅读量 |
| weigh | int(10) unsigned | Y | - | 0 | - | 权重 |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) unsigned | Y | - | NULL | - | 更新时间 |
| deletetime | bigint(16) unsigned | Y | - | NULL | - | 删除时间 |

### fa_xiluxc_tag (标签管理)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| type | enum('news','shop') | N | - | news | - | 类型:news=知识库,shop=门店 |
| name | varchar(30) | N | - |  | - | 名称 |
| image | varchar(255) | N | - |  | - | 图标 |
| status | enum('hidden','normal') | N | - | normal | - | 状态:hidden=隐藏,normal=正常 |
| weigh | int(10) unsigned | N | - | 0 | - | 排序 |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_third (第三方登录表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | ID |
| user_id | int(10) unsigned | N | MUL | 0 | - | 会员ID |
| platform | varchar(30) | N | - |  | - | 第三方应用 |
| openid | varchar(100) | N | - |  | - | 第三方唯一ID |
| openname | varchar(100) | N | - |  | - | 第三方会员昵称 |
| avatar | varchar(255) | N | - |  | - | 头像 |
| access_token | varchar(255) | N | - |  | - | AccessToken |
| refresh_token | varchar(255) | N | - |  | - | RefreshToken |
| unionid | varchar(50) | N | - |  | - | UnionId |
| expires_in | int(10) unsigned | N | - | 0 | - | 有效期 |
| auth_userinfo | enum('0','1') | Y | - | 0 | - | 授权用户信息 |
| createtime | bigint(16) unsigned | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) unsigned | Y | - | NULL | - | 更新时间 |
| logintime | bigint(16) unsigned | Y | - | NULL | - | 登录时间 |

### fa_xiluxc_user (会员身份表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | ID |
| user_id | int(10) unsigned | N | - | 0 | - | 用户id |
| group_type | enum('1','2','3') | N | - | 1 | - | 身份:1=单店,2=总店,3=分店 |
| parent_id | int(10) unsigned | N | - | 0 | - | 归属账号 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_user_account (用户账户表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | MUL | 0 | - | 用户id |
| first_user_id | int(10) unsigned | N | - | 0 | - | 一级 |
| second_user_id | int(10) unsigned | N | - | 0 | - | 二级 |
| bindtime | bigint(16) | Y | - | NULL | - | 绑定时间 |
| total_points | int(10) unsigned | N | - | 0 | - | 总积分 |
| points | int(10) unsigned | N | - | 0 | - | 积分 |
| total_money | decimal(10,2) unsigned | N | - | 0.00 | - | 累计佣金 |
| money | decimal(10,2) unsigned | N | - | 0.00 | - | 余额 |
| withdraw_money | decimal(10,2) unsigned | N | - | 0.00 | - | 累计提现金额 |
| platform_message | int(10) unsigned | N | - | 0 | - | 活动消息 |
| system_message | int(10) unsigned | N | - | 0 | - | 系统消息 |
| user_message | int(10) unsigned | Y | - | 0 | - | 个人消息 |

### fa_xiluxc_user_brand (品牌申请)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | - | 0 | - | 申请人账号 |
| brand_name | varchar(50) | N | - |  | - | 品牌名 |
| logo | varchar(255) | N | - |  | - | 品牌LOGO |
| concat_name | varchar(30) | N | - |  | - | 联系人 |
| mobile | char(11) | N | - |  | - | 手机号 |
| account_mobile | varchar(20) | N | - |  | - | 品牌账号 |
| status | enum('checked','passed','failed') | N | - | checked | - | 状态:checked=待审核,passed=通过,failed=驳回 |
| refuse_reason | varchar(255) | N | - |  | - | 拒绝原因 |
| weigh | int(10) unsigned | N | - | 0 | - | 排序 |
| createtime | bigint(16) | Y | - | NULL | - | - |
| updatetime | bigint(16) | Y | - | NULL | - | - |
| deletetime | bigint(16) | Y | - | NULL | - | - |

### fa_xiluxc_user_car (用户车辆)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | MUL | 0 | - | 用户 |
| car_no | varchar(20) | N | - |  | - | 车牌 |
| brand_id | int(10) unsigned | N | - | 0 | - | 品牌 |
| series_id | int(10) unsigned | N | - | 0 | - | 车系 |
| models_id | int(10) unsigned | N | - | 0 | - | 具体车型 |
| register_time | bigint(16) | Y | - | NULL | - | 注册日期 |
| car_vin | varchar(20) | N | - |  | - | 车架号 |
| engine_number | varchar(20) | N | - |  | - | 发动机号 |
| car_belongs_to | tinyint(2) unsigned | N | - | 0 | - | 归属:1=个人,2=公司 |
| use_nature | tinyint(2) unsigned | N | - | 0 | - | 使用性质:1=非营运,2=营运 |
| is_default | tinyint(2) unsigned | N | - | 0 | - | 默认:0=否,1=是 |
| createtime | bigint(16) | Y | - | NULL | - | 添加时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_user_coupon (用户优惠券)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | MUL | 0 | - | 用户 |
| coupon_id | varchar(100) | N | MUL | 0 | - | 优惠券 |
| platform | int(11) | N | - | 1 | - | 平台：1抖音 ，2美团 |
| use_order_id | int(10) | N | - | 0 | - | 使用订单 |
| shop_id | int(10) | Y | - | NULL | - | - |
| use_status | tinyint(2) unsigned | N | - | 0 | - | 使用状态:0=未使用,1=已使用 |
| keeptime | int(11) | N | - | NULL | - | 洗车时长(分钟) |
| use_time | bigint(16) | Y | MUL | NULL | - | 使用时间 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_user_message (消息)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | MUL | 0 | - | 用户id |
| title | varchar(30) | N | - |  | - | 名称 |
| content | varchar(255) | N | - |  | - | 内容 |
| type | tinyint(3) unsigned | N | - | 0 | - | 类型: 多样，见php class |
| read | tinyint(1) unsigned | N | - | 0 | - | 读取状态:0=未读,1=已读 |
| read_time | bigint(16) | Y | - | NULL | - | 读取时间 |
| extra | varchar(500) | N | - |  | - | - |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |

### fa_xiluxc_user_notice (会员查看公告)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | MUL | 0 | - | 用户 |
| notice_id | int(10) unsigned | N | - | 0 | - | 公告 |
| num | smallint(6) unsigned | N | - | 0 | - | 查看次数 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_user_package (会员套餐)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | MUL | 0 | - | 用户 |
| shop_id | int(10) unsigned | N | - | 0 | - | 门店 |
| brand_id | int(10) unsigned | N | - | 0 | - | 品牌 |
| package_id | int(10) | N | - | NULL | - | 套餐ID |
| package_name | varchar(50) | N | - |  | - | 套餐名 |
| package_image | varchar(255) | N | - |  | - | 套餐图片 |
| order_id | int(10) unsigned | N | UNI | 0 | - | 下单ID |
| order_amount | decimal(10,2) unsigned | N | - | 0.00 | - | 订单金额 |
| pay_fee | decimal(10,2) unsigned | N | - | 0.00 | - | 购买价格 |
| status | enum('ing','finished','apply_refund','refund') | N | - | ing | - | 套餐状态:ing=进行中,finished=已完成,apply_refund=退款申请中,refund=已退款 |
| qrcode | varchar(255) | N | - |  | - | 核销二维码 |
| code | varchar(30) | N | - |  | - | 核销code |
| token | varchar(255) | N | - |  | - | 核销token |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_user_package_service

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_package_id | varchar(50) | N | MUL |  | - | 用户套餐ID |
| package_id | int(10) unsigned | N | - | 0 | - | 关联套餐ID |
| package_service_id | int(10) unsigned | N | - | 0 | - | 关联套餐服务ID |
| service_id | int(10) unsigned | N | - | 0 | - | 服务ID |
| shop_service_id | int(10) unsigned | N | - | 0 | - | 门店服务ID |
| service_price_id | int(10) unsigned | N | - | 0 | - | 服务价格ID |
| service_image | varchar(255) | N | - |  | - | 服务图标 |
| service_name | varchar(50) | N | - | NULL | - | 服务名称 |
| service_price_name | varchar(50) | N | - |  | - | 服务规格 |
| salesprice | decimal(10,2) unsigned | N | - | 0.00 | - | 原价格 |
| vip_price | decimal(10,2) unsigned | N | - | 0.00 | - | 会员价格 |
| total_count | smallint(6) unsigned | N | - | 0 | - | 总次数 |
| stock | smallint(6) | N | - | NULL | - | 次数 |
| use_count | smallint(6) unsigned | N | - | 0 | - | 已使用次数 |
| status | enum('ing','finished') | N | - | ing | - | 状态:ing=使用中,finished=已完成 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_user_shop_account (用户门店账户表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | MUL | 0 | - | 用户ID |
| shop_id | int(10) unsigned | N | - | 0 | - | 用户id |
| brand_id | int(10) unsigned | N | - | 0 | - | 品牌ID |
| total_money | decimal(10,2) unsigned | N | - | 0.00 | - | 累计充值 |
| money | decimal(10,2) unsigned | N | - | 0.00 | - | 余额 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_user_shop_vip (用户门店会员表)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | MUL | 0 | - | 用户ID |
| vip_no | varchar(20) | N | - |  | - | 会员卡号 |
| shop_id | int(10) unsigned | N | - | 0 | - | 用户id |
| brand_id | int(10) unsigned | N | - | 0 | - | 品牌ID |
| shop_vip_id | int(10) unsigned | N | - | 0 | - | 门店会员卡 |
| expire_in | bigint(16) unsigned | N | - | 0 | - | 过期时间 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |

### fa_xiluxc_vip_order (会员订单)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | MUL | 0 | - | 下单用户 |
| platform | varchar(10) | N | - | 1 | - | 下单平台:1=wxmini,2=app |
| order_no | varchar(50) | N | - |  | - | 订单号 |
| order_trade_no | varchar(32) | N | - |  | - | 交易单号 |
| pay_type | tinyint(2) unsigned | N | - | 1 | - | 支付类型:1=微信,2=支付宝,3=后台人工 |
| pay_fee | decimal(10,0) unsigned | N | - | 0 | - | 支付金额 |
| pay_status | enum('unpaid','paid') | N | - | unpaid | - | 支付状态:unpaid=待支付,paid=已支付 |
| paytime | bigint(16) | Y | - | NULL | - | 支付时间 |
| trade_no | varchar(32) | N | - |  | - | 三方单号 |
| vip_id | int(10) unsigned | N | - | 0 | - | 会员卡 |
| vip_salesprice | decimal(10,2) unsigned | N | - | 0.00 | - | 售价 |
| vip_name | varchar(50) | N | - |  | - | 会员卡名 |
| vip_days | smallint(6) unsigned | N | - | 0 | - | 有效期 |
| vip_privilege | text | N | - | NULL | - | 会员卡权益 |
| ip | varchar(20) | N | - |  | - | 下单id |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |
| admin_id | int(10) | N | - | NULL | - | 管理员 |
| shop_id | int(10) unsigned | N | - | 0 | - | 门店 |
| brand_id | int(10) unsigned | N | - | 0 | - | 品牌ID |

### fa_xiluxc_withdraw (提现金额)

| 字段 | 类型 | 可空 | 键 | 默认值 | 额外 | 注释 |
|------|------|------|----|--------|------|------|
| id | int(10) unsigned | N | PRI | NULL | auto_increment | - |
| user_id | int(10) unsigned | N | MUL | 0 | - | 提现用户 |
| money | decimal(10,2) | N | - | 0.00 | - | 提现金额 |
| real_money | decimal(10,2) | N | - | 0.00 | - | 实际到账 |
| rate | double(10,2) | N | - | 0.00 | - | 手续费率 |
| rate_money | decimal(10,2) | N | - | 0.00 | - | 手续费 |
| state | tinyint(4) | N | - | 1 | - | 状态:1=审核中,2=处理中,3=已处理,4=已拒绝 |
| reason | varchar(255) | N | - |  | - | 拒绝理由 |
| certificate | varchar(255) | N | - |  | - | 打款凭证 |
| order_no | varchar(50) | N | - |  | - | 提现订单号 |
| checktime | bigint(16) | Y | - | NULL | - | 审核时间 |
| createtime | bigint(16) | Y | - | NULL | - | 创建时间 |
| updatetime | bigint(16) | Y | - | NULL | - | 更新时间 |


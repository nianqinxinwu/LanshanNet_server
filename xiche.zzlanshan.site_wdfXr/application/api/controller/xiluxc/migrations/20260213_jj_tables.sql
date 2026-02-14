-- 居间人模块 - 全部数据表建表 SQL
-- 日期: 2026-02-13
-- 表前缀: fa_

-- ----------------------------
-- 1. 居间人扩展表
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_agent_profile` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '关联用户ID',
  `real_name` varchar(50) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `id_card` varchar(30) NOT NULL DEFAULT '' COMMENT '身份证号',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `company_name` varchar(200) NOT NULL DEFAULT '' COMMENT '所属公司',
  `level` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '等级',
  `total_orders` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '累计成交订单数',
  `total_commission` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '累计佣金',
  `hexagon_data` text COMMENT '六维雷达数据(JSON)',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态:0=待审核,1=正常,2=冻结',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='居间人扩展信息';

-- ----------------------------
-- 2. 工厂信息表
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_factory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '关联用户ID',
  `company_name` varchar(200) NOT NULL DEFAULT '' COMMENT '企业名称',
  `credit_code` varchar(30) NOT NULL DEFAULT '' COMMENT '统一社会信用代码',
  `contact_name` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人',
  `contact_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `address` varchar(500) NOT NULL DEFAULT '' COMMENT '地址',
  `logo` varchar(255) NOT NULL DEFAULT '' COMMENT 'LOGO',
  `description` text COMMENT '简介',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态:0=待审核,1=正常,2=冻结',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工厂信息';

-- ----------------------------
-- 3. 商品任务池
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_product` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `factory_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '工厂ID',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '商品名称',
  `cover_image` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图',
  `images` text COMMENT '详情图(JSON数组)',
  `price` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '单价',
  `unit` varchar(20) NOT NULL DEFAULT '' COMMENT '单位(吨/台等)',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '佣金比例(%)',
  `deposit_rate` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT '保证金比例(%)',
  `stock` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '库存',
  `sales` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '销量',
  `category_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '分类ID',
  `category_name` varchar(50) NOT NULL DEFAULT '' COMMENT '分类名称',
  `craft_standard` varchar(500) NOT NULL DEFAULT '' COMMENT '工艺/规格说明',
  `factory_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '工厂履约率(%)',
  `description` text COMMENT '详细描述',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态:0=下架,1=上架',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_factory_id` (`factory_id`),
  KEY `idx_status` (`status`),
  KEY `idx_category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品任务池';

-- ----------------------------
-- 4. 居间人订单
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(30) NOT NULL DEFAULT '' COMMENT '订单编号',
  `agent_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '居间人用户ID',
  `product_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '商品ID',
  `factory_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '工厂ID',
  `bid_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '竞标ID(竞标生成的订单)',
  `product_name` varchar(200) NOT NULL DEFAULT '' COMMENT '商品名称(冗余)',
  `cover_image` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图(冗余)',
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '单价',
  `quantity` int(10) unsigned NOT NULL DEFAULT 1 COMMENT '数量',
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '合同总金额',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '佣金比例(%)',
  `commission_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '佣金金额',
  `deposit_rate` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT '保证金比例(%)',
  `deposit_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '保证金金额',
  `buyer_company` varchar(200) NOT NULL DEFAULT '' COMMENT '买家企业名称',
  `buyer_address` varchar(500) NOT NULL DEFAULT '' COMMENT '买家地址',
  `buyer_contact` varchar(50) NOT NULL DEFAULT '' COMMENT '买家联系人',
  `buyer_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '买家电话',
  `buyer_credit_code` varchar(30) NOT NULL DEFAULT '' COMMENT '买家信用代码',
  `buyer_tax_number` varchar(50) NOT NULL DEFAULT '' COMMENT '买家税号',
  `contract_upload_hours` int(10) unsigned NOT NULL DEFAULT 24 COMMENT '合同上传期限(小时)',
  `execution_hours` int(10) unsigned NOT NULL DEFAULT 72 COMMENT '履约期限(小时)',
  `factory_bonus` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '工厂返利',
  `logistics_rebate` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '物流返佣金额',
  `logistics_rebate_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '物流返佣比例(%)',
  `bonus_rules` text COMMENT '奖励规则(JSON)',
  `status` tinyint(2) NOT NULL DEFAULT 0 COMMENT '状态:0=待确认,1=待缴保证金,2=已缴保证金,3=待上传合同,4=履约执行中,5=待结算,6=已结算,7=已取消,8=已逾期',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  `deletetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_order_sn` (`order_sn`),
  KEY `idx_agent_id` (`agent_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_factory_id` (`factory_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='居间人订单';

-- ----------------------------
-- 5. 订单状态日志
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_order_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '订单ID',
  `from_status` tinyint(2) NOT NULL DEFAULT 0 COMMENT '变更前状态',
  `to_status` tinyint(2) NOT NULL DEFAULT 0 COMMENT '变更后状态',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '描述',
  `operator_type` varchar(20) NOT NULL DEFAULT '' COMMENT '操作人类型:agent/admin/system',
  `operator_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '操作人ID',
  `createtime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单状态日志';

-- ----------------------------
-- 6. 保证金记录
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_deposit` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '订单ID',
  `batch_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '批次ID(批量支付)',
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '保证金金额',
  `red_packet_deduct` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '红包抵扣金额',
  `pay_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '支付状态:0=待支付,1=已支付,2=已退回',
  `pay_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '支付方式:1=微信,2=支付宝',
  `transaction_id` varchar(60) NOT NULL DEFAULT '' COMMENT '第三方交易号',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='保证金记录';

-- ----------------------------
-- 7. 保证金批次支付
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_deposit_batch` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `batch_no` varchar(30) NOT NULL DEFAULT '' COMMENT '批次号',
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '批次总金额',
  `order_ids` text COMMENT '关联订单ID(JSON数组)',
  `pay_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '支付状态:0=待支付,1=已支付',
  `pay_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '支付方式:1=微信,2=支付宝',
  `transaction_id` varchar(60) NOT NULL DEFAULT '' COMMENT '第三方交易号',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_batch_no` (`batch_no`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='保证金批次支付';

-- ----------------------------
-- 8. 合同记录
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_contract` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '订单ID',
  `file_url` varchar(500) NOT NULL DEFAULT '' COMMENT '合同文件URL',
  `file_name` varchar(200) NOT NULL DEFAULT '' COMMENT '合同文件名',
  `upload_deadline` bigint(16) NOT NULL DEFAULT 0 COMMENT '上传截止时间戳',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态:0=待上传,1=已上传,2=已过期',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='合同记录';

-- ----------------------------
-- 9. 物流跟踪
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_logistics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '订单ID',
  `logistics_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '物流类型:1=平台物流,2=自提',
  `company_name` varchar(100) NOT NULL DEFAULT '' COMMENT '物流公司名称',
  `tracking_no` varchar(50) NOT NULL DEFAULT '' COMMENT '物流单号',
  `status` tinyint(2) NOT NULL DEFAULT 0 COMMENT '物流状态:0=待发货,1=已发货,2=运输中,3=已签收',
  `rebate_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '返佣金额',
  `timeline_json` text COMMENT '物流时间线(JSON)',
  `pickup_time` bigint(16) NOT NULL DEFAULT 0 COMMENT '提货时间',
  `pickup_note_url` varchar(500) NOT NULL DEFAULT '' COMMENT '提货单URL',
  `checklist_files` text COMMENT '收发货清单(JSON)',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='物流跟踪';

-- ----------------------------
-- 10. 佣金记录
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_commission` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '订单ID',
  `agent_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '居间人用户ID',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '佣金总额',
  `rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '佣金比例',
  `base_commission` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '基础佣金',
  `factory_bonus` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '工厂返利',
  `logistics_rebate` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '物流返佣',
  `pk_bonus` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'PK奖金',
  `red_packet` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '红包',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态:0=待结算,1=已结算',
  `settled_time` bigint(16) DEFAULT NULL COMMENT '结算时间',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_agent_id` (`agent_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='佣金记录';

-- ----------------------------
-- 11. 竞标记录
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_bid` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bid_sn` varchar(30) NOT NULL DEFAULT '' COMMENT '竞标编号',
  `agent_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '居间人用户ID',
  `category_name` varchar(50) NOT NULL DEFAULT '' COMMENT '商品分类',
  `quantity` int(10) unsigned NOT NULL DEFAULT 1 COMMENT '需求数量',
  `unit` varchar(20) NOT NULL DEFAULT '' COMMENT '单位',
  `expect_delivery` varchar(100) NOT NULL DEFAULT '' COMMENT '期望交付日期',
  `target_commission` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '目标佣金比例(%)',
  `factory_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '邀请工厂数',
  `quoted_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '已报价工厂数',
  `remain_time` bigint(16) NOT NULL DEFAULT 0 COMMENT '截止时间戳',
  `buyer_company` varchar(200) NOT NULL DEFAULT '' COMMENT '买家企业',
  `buyer_address` varchar(500) NOT NULL DEFAULT '' COMMENT '买家地址',
  `buyer_contact` varchar(50) NOT NULL DEFAULT '' COMMENT '买家联系人',
  `buyer_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '买家电话',
  `remark` text COMMENT '备注',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态:1=竞标中,2=已完成,3=已过期',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_bid_sn` (`bid_sn`),
  KEY `idx_agent_id` (`agent_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='竞标记录';

-- ----------------------------
-- 12. 竞标工厂报价
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_bid_quote` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bid_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '竞标ID',
  `factory_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '工厂ID',
  `contract_price` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '合同价格',
  `delivery_date` varchar(50) NOT NULL DEFAULT '' COMMENT '交付日期',
  `commission_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '佣金金额',
  `fulfill_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '工厂履约率(%)',
  `selected` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否选中',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态:0=待报价,1=已报价,2=已过期',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bid_id` (`bid_id`),
  KEY `idx_factory_id` (`factory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='竞标工厂报价';

-- ----------------------------
-- 13. 平台红包
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_red_packet` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '关联订单ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '红包金额',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态:0=待领取,1=已领取',
  `used` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已使用',
  `used_deposit_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '抵扣的保证金ID',
  `redeem_code` varchar(50) NOT NULL DEFAULT '' COMMENT '兑换码',
  `redeemed_at` bigint(16) DEFAULT NULL COMMENT '兑换时间',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='平台红包';

-- ----------------------------
-- 14. PK奖池
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_pk_pool` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `period` varchar(20) NOT NULL DEFAULT '' COMMENT '期数(如2026-W07)',
  `pool_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '奖池金额',
  `start_time` bigint(16) NOT NULL DEFAULT 0 COMMENT '开始时间',
  `end_time` bigint(16) NOT NULL DEFAULT 0 COMMENT '结束时间',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态:0=进行中,1=已结算',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_period` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='PK奖池';

-- ----------------------------
-- 15. PK排名记录
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_pk_rank` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pool_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '奖池ID',
  `agent_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '居间人用户ID',
  `score` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '得分(成交额)',
  `rank` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '排名',
  `bonus` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '奖金',
  `createtime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pool_id` (`pool_id`),
  KEY `idx_agent_id` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='PK排名';

-- ----------------------------
-- 16. 分销推广关系
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_invite` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '居间人(推荐人)ID',
  `invite_user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '被推荐人ID',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_agent_id` (`agent_id`),
  KEY `idx_invite_user_id` (`invite_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分销推广关系';

-- ----------------------------
-- 17. 买家信息 (已在 20260210_buyer_info.php 中创建, 此处做兼容)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_buyer_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '关联用户ID(居间人)',
  `company_name` varchar(200) NOT NULL DEFAULT '' COMMENT '企业名称',
  `address` varchar(500) NOT NULL DEFAULT '' COMMENT '收货地址',
  `contact_name` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人',
  `contact_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `credit_code` varchar(30) NOT NULL DEFAULT '' COMMENT '统一社会信用代码',
  `tax_number` varchar(50) NOT NULL DEFAULT '' COMMENT '税务登记证号',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='居间人-买家信息';

-- ----------------------------
-- 18. 工厂资金账户
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_factory_account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `factory_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '工厂ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `money` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '可用余额',
  `frozen_money` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '冻结金额',
  `total_recharge` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '累计充值',
  `total_settled` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '累计结算',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_factory_id` (`factory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工厂资金账户';

-- ----------------------------
-- 19. 工厂资金流水
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_factory_fund_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `factory_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '工厂ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `type` tinyint(2) NOT NULL DEFAULT 0 COMMENT '类型:1=充值,2=佣金冻结,3=佣金结算,4=佣金解冻',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '金额',
  `before_money` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '变动前余额',
  `after_money` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '变动后余额',
  `order_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '关联订单ID',
  `memo` varchar(200) NOT NULL DEFAULT '' COMMENT '备注',
  `createtime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_factory_id` (`factory_id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工厂资金流水';

-- ----------------------------
-- 20. 佣金托管记录
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_escrow` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '订单ID',
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '托管总金额',
  `platform_fee` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '平台服务费',
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '税务代缴',
  `logistics_rebate` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '物流返佣',
  `agent_settlement` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '居间人实际结算',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态:0=托管中,1=已结算,2=已退回',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='佣金托管记录';

-- ----------------------------
-- 21. 工厂充值订单
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_factory_recharge_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `factory_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '工厂ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `order_no` varchar(30) NOT NULL DEFAULT '' COMMENT '充值订单号',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '充值金额',
  `pay_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '支付状态:0=待支付,1=已支付',
  `pay_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '支付方式:1=微信,2=支付宝',
  `pay_time` bigint(16) DEFAULT NULL COMMENT '支付时间',
  `transaction_id` varchar(60) NOT NULL DEFAULT '' COMMENT '第三方交易号',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_order_no` (`order_no`),
  KEY `idx_factory_id` (`factory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工厂充值订单';

-- ----------------------------
-- 22. 居间人商品清单(购物车)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_jj_cart` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `product_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '商品ID',
  `quantity` int(10) unsigned NOT NULL DEFAULT 1 COMMENT '数量',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='居间人商品清单';

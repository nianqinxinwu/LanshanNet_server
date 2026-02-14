-- 工厂对公银行账户
CREATE TABLE IF NOT EXISTS `fa_jj_factory_bank_account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `factory_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '工厂ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `account_name` varchar(200) NOT NULL DEFAULT '' COMMENT '开户名称',
  `bank_name` varchar(200) NOT NULL DEFAULT '' COMMENT '开户银行',
  `bank_branch` varchar(200) NOT NULL DEFAULT '' COMMENT '开户支行',
  `bank_no` varchar(50) NOT NULL DEFAULT '' COMMENT '对公账号',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认:0=否,1=是',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  `deletetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_factory_id` (`factory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工厂对公银行账户';

-- 工厂提现记录
CREATE TABLE IF NOT EXISTS `fa_jj_factory_withdraw` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(30) NOT NULL DEFAULT '' COMMENT '提现单号',
  `factory_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '工厂ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `bank_account_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '银行账户ID',
  `account_name` varchar(200) NOT NULL DEFAULT '' COMMENT '开户名称(快照)',
  `bank_name` varchar(200) NOT NULL DEFAULT '' COMMENT '开户银行(快照)',
  `bank_branch` varchar(200) NOT NULL DEFAULT '' COMMENT '开户支行(快照)',
  `bank_no` varchar(50) NOT NULL DEFAULT '' COMMENT '对公账号(快照)',
  `money` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '提现金额',
  `real_money` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '实到金额',
  `rate` decimal(5,4) NOT NULL DEFAULT 0.0000 COMMENT '手续费率',
  `rate_money` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '手续费',
  `state` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=审核中,2=处理中,3=已处理,4=已拒绝',
  `reason` varchar(500) NOT NULL DEFAULT '' COMMENT '拒绝原因',
  `certificate` varchar(500) NOT NULL DEFAULT '' COMMENT '转账凭证',
  `checktime` bigint(16) DEFAULT NULL COMMENT '审核时间',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_order_no` (`order_no`),
  KEY `idx_factory_id` (`factory_id`),
  KEY `idx_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工厂提现记录';

-- fa_jj_factory_account 新增累计提现字段
ALTER TABLE `fa_jj_factory_account`
  ADD COLUMN `total_withdraw` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '累计提现' AFTER `total_settled`;

-- 工厂技术服务费 + 结算分账逻辑调整
-- 日期: 2026-02-14

-- fa_jj_order 新增技术服务费字段
ALTER TABLE `fa_jj_order`
  ADD COLUMN `service_fee` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '技术服务费' AFTER `commission_amount`,
  ADD COLUMN `service_fee_rate` decimal(5,2) NOT NULL DEFAULT 1.00 COMMENT '技术服务费率(%)' AFTER `service_fee`;

-- fa_jj_escrow 新增技术服务费字段
ALTER TABLE `fa_jj_escrow`
  ADD COLUMN `service_fee` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '技术服务费' AFTER `total_amount`;

-- fa_jj_order 新增佣金锁定相关字段（lock_commission 使用）
ALTER TABLE `fa_jj_order`
  ADD COLUMN `commission_locked` tinyint(1) NOT NULL DEFAULT 0 COMMENT '佣金是否已锁定' AFTER `status`,
  ADD COLUMN `commission_lock_time` bigint(16) DEFAULT NULL COMMENT '佣金锁定时间' AFTER `commission_locked`;

-- fa_jj_escrow 新增 factory_id 和 agent_user_id 字段
ALTER TABLE `fa_jj_escrow`
  ADD COLUMN `factory_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '工厂ID' AFTER `order_id`,
  ADD COLUMN `agent_user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '居间人用户ID' AFTER `factory_id`,
  ADD COLUMN `settle_time` bigint(16) DEFAULT NULL COMMENT '结算时间' AFTER `status`;

-- fa_jj_order 新增催款截止时间字段
ALTER TABLE `fa_jj_order`
  ADD COLUMN `payment_urge_deadline` bigint(16) NOT NULL DEFAULT 0 COMMENT '催款截止时间' AFTER `payment_urge_hours`;

-- 买家付款证明表
CREATE TABLE IF NOT EXISTS `fa_jj_payment_proof` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '订单ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '上传用户ID(居间人)',
  `file_urls` text COMMENT '付款证明图片(JSON数组)',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注说明',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=待审核,1=已通过,2=已驳回',
  `reject_reason` varchar(500) NOT NULL DEFAULT '' COMMENT '驳回原因',
  `review_time` bigint(16) DEFAULT NULL COMMENT '审核时间',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='买家付款证明';

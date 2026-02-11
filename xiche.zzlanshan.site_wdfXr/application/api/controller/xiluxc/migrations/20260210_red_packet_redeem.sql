-- 红包抵扣保证金功能 - 数据库变更
-- 日期: 2026-02-10

-- fa_jj_red_packet 新增字段：标记红包使用状态
ALTER TABLE `fa_jj_red_packet` ADD COLUMN `used` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已使用' AFTER `status`;
ALTER TABLE `fa_jj_red_packet` ADD COLUMN `used_deposit_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '抵扣的保证金ID' AFTER `used`;

-- fa_jj_deposit 新增字段：记录红包抵扣金额
ALTER TABLE `fa_jj_deposit` ADD COLUMN `red_packet_deduct` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '红包抵扣金额' AFTER `amount`;

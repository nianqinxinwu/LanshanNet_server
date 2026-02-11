<?php
$pdo = new PDO('mysql:host=122.51.106.231;port=3306;dbname=andone;charset=utf8mb4', 'andone', 'Dp6MT3XnJiA7nP2p');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "CREATE TABLE IF NOT EXISTS `fa_jj_buyer_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '关联用户ID（居间人）',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='居间人-买家信息管理'";

$pdo->exec($sql);
echo "Table fa_jj_buyer_info created successfully\n";

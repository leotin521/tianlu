-- ----------------------------
-- Table structure for lzh_business_detail
-- ----------------------------
DROP TABLE IF EXISTS `lzh_business_detail`;
CREATE TABLE `lzh_business_detail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `business_name` varchar(100) NOT NULL DEFAULT '' COMMENT '企业名称',
  `legal_person` varchar(25) NOT NULL DEFAULT '' COMMENT '企业法人',
  `registered_capital` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '注册资金',
  `city` varchar(255) NOT NULL DEFAULT '' COMMENT '公司所在城市',
  `uid` int(10) unsigned NOT NULL COMMENT '用户uid',
  `bianhao` varchar(30) NOT NULL COMMENT '企业编号/注册号',
  `bid_money` int(9) unsigned NOT NULL DEFAULT '0' COMMENT '融资金额',
  `bid_duration` int(9) unsigned NOT NULL DEFAULT '0' COMMENT '融资期限',
  `use_type` varchar(600) NOT NULL DEFAULT '' COMMENT '贷款用途',
  `repay_source` varchar(600) NOT NULL DEFAULT '' COMMENT '还款来源',
  `add_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deal_info` varchar(300) NOT NULL DEFAULT '' COMMENT '审核处理说明',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='企业基本信息表';
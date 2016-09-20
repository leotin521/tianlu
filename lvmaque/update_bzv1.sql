-- 2015/10/27  将灵活宝项目信息表里的term字段类型更改为smallint
-- ALTER TABLE `new_bzv1`.`lzh_bao`     CHANGE `term` `term` SMALLINT(5) UNSIGNED DEFAULT '7' NOT NULL COMMENT '封存天数';
--
-- 2015--8-27 提现手续费增加每笔提现最小金额配置
--
delete from lzh_global where id=126;
INSERT INTO `lzh_global` VALUES ('126', 'input', '5-10000|0-0|50-1000000|2', '提现手续费', '以10-50|0-0|3-30|2的形式填入，数字从左到右依次表示超出回款资金总额的每笔收取总额的千分之10作为手续费,最大手续费上限50元;提现在回款总金额内的每笔收费千分之0元，手续费上限0元;单日单笔提现上限3万,单日提现资金上限30万;当回款资金池提现手续费设置为0-0时，每笔提现最少收取2元手续费(fee_tqtx)', '10', 'fee_tqtx', '1', '1');


--
-- 2015--8-27 在member_info表里增加省市
--
ALTER TABLE `lzh_member_info`     ADD COLUMN `province` INT(10) UNSIGNED DEFAULT '0' NOT NULL COMMENT '省' AFTER `em_relation`,     ADD COLUMN `city` INT(10) UNSIGNED DEFAULT '0' NOT NULL COMMENT '市' AFTER `province`;

--
-- 2015-8-29  添加还款提醒配置
--
INSERT INTO `lzh_global` VALUES ('155', 'input', '1|2|3|7|15', '还款提醒设置', '距最后还款日1天，2天，3天，7天和15天时各提醒一次；参数设置为0表示不开启还款提醒', '0', 'expire', '1', '1');
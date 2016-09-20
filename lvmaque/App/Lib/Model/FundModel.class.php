<?php
// 定投宝模型
class FundModel extends ACommonModel {
	protected $tableName    = 'borrow_info'; 
	protected $_validate	=	array(
	array('bianhao','require',"定投宝编号名称不能为空"),
	array('repayment_type','require',"还款方式不能为空")
	);
}
?>
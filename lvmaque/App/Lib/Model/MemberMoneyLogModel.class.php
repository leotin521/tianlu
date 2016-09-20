<?php
// 用户资金日记模型
class MemberMoneyLogModel extends ACommonModel {
	protected $tableName = 'member_moneylog';

    // 获取所有moneylog类型
    public static function get_moneyLog_type()
    {
        return  C('MONEY_LOG');
    }

    // 获得moneyLogType分组  充值 提现 投资 收益 回收本金
    public static function get_moneyLog_type_group()
    {
        $ret = array(
            1 => array(3, 27), //充值
            2 => array(12,29), //提现
            3 => array(6,37,53), //投资
            4 => array(13,20,32,34,40,41,43,45,51,52), //收益
            5 => array(9,10,54), //回收本金
        );
        return $ret;

    }



}
?>
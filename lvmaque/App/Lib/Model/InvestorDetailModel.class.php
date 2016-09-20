<?php

// 还款详情表模型
class InvestorDetailModel extends ACommonModel {

    //status 0代表还未确认通过； 1代表正常还完； 2代表提前还款； 3代表迟还； 4代表网站代还本金； 5代表逾期还款；6代表逾期未还； 7代表复审通过，还款中
    const INVEST_DETAIL_STATUS_WAIT_REVIEW = 0; // 还未确认通过
    const INVEST_DETAIL_STATUS_NORMAL_FINISH = 1; // 正常还完
    const INVEST_DETAIL_STATUS_VIEW_PASS = 2; // 提前还款
    const INVEST_DETAIL_STATUS_LATE = 3; // 迟还
    const INVEST_DETAIL_STATUS_PLAT_REPAY = 4;  // 网站代还本金
    const INVEST_DETAIL_STATUS_OVERDUE_REPAY = 5; // 逾期还款
    const INVEST_DETAIL_STATUS_OVERDUE = 6; // 逾期未还
    const INVEST_DETAIL_STATUS_REPAYING = 7;  // (复审通过)还款中
    const INVEST_DETAIL_STATUS_TRANSFER = 14; // 14:转让中

	protected $tableName = 'investor_detail';

    /**
     * 统计企业直投回款利息interest、回款总额capital、利息手续费fee
     * @param $where string
     */
    public static function get_transfer_investor_detail_account($where, $fields = null)
    {
        $pre = C('DB_PREFIX');
        $where = "bi.borrow_type=6 and ".$where;
        // 如果查询字段不存在，默认查找已回款信息
        if( !isset($fields) ) $fields = "sum(d.receive_capital) as capital, sum(d.receive_interest) as interest, sum(d.interest_fee) as fee";
        $ret = M("investor_detail d")
            ->field($fields)
            ->join("{$pre}borrow_investor bi ON bi.id=d.invest_id")
            ->where($where)->find();
        return $ret;
    }

    /**
     * 获得标的投资的利息总和
     * @param int $borrow_id
     * @param int $status  标的状态
     */
    public static function get_sum_investor_interest($borrow_id, $status = false)
    {
        $ret = false;
        try {
            $where = array("borrow_id"=> $borrow_id);
            if( !empty($status) ) $where['status'] = $status;
            $ret = M("investor_detail")->where($where)->sum('interest');
        } catch( Exception $e) {
            throw $e;
        }
        return $ret;
    }

    /**
     * 获得需要网站代还的状态
     */
    public static function get_need_web_repay_status()
    {
        $ret = array(
            self::INVEST_DETAIL_STATUS_OVERDUE,
            self::INVEST_DETAIL_STATUS_REPAYING
        );
        return $ret;
    }

    /**
     * 获得需要自己还款的状态
     * @return array
     */
    public static function get_need_self_repay_status()
    {
        $ret = array(
            self::INVEST_DETAIL_STATUS_PLAT_REPAY,
            self::INVEST_DETAIL_STATUS_OVERDUE,
            self::INVEST_DETAIL_STATUS_REPAYING
        );
        return $ret;
    }
    
    /**
     * 检测用户是否存在逾期
     * @param $type 默认1 散标限制; 其它标种可扩展
     */
    public static function check_is_overdue($uid, $type=1){
        if (empty($uid)) return true;
        $where = array();
        if ($type==1) {
            $where['borrow_uid'] = $uid;
            $where['status'] = array('in','4,6,7');
            $where['deadline'] = array('lt',time());
            $count = M("investor_detail")->where($where)->count('id');
            if ($count>0){
                return false;
            }
            else return true;
        }
    }

    /**
     * 查询已还款本息
     * @param $borrow_id
     * @param null $sort_order
     */
    public static function get_has_receive($borrow_id, $sort_order = null, $fields = null)
    {
        $ret = false;
        $group = null;
        if( $borrow_id > 0 ) {
            $where = array(
                'borrow_id' => $borrow_id,
                'status' => array('neq', 14)
            );
            if( isset($sort_order) ) {
                $where['sort_order'] = $sort_order;
            }else{
                $group = 'sort_order';
            }
            if( !isset($sort_order) ) {
                $fields = "sum(receive_capital+receive_interest+interest_fee) as receive, sort_order, borrow_id";
            }
            $ret = self::get_investor_detail(null, $fields, $where, null, $group);
        }
        return $ret;
    }

    /**
     * @param null $config_id
     * @param null $where
     * @param null $fields
     * @param null $order
     * @param null $group
     */
    public static function get_investor_detail($borrow_id=null, $fields=null, $where=null, $order=null, $group=null)
    {
        $ret = false;
        if( !empty($borrow_id) && $where==null ) {
            $where['borrow_id'] = intval($borrow_id);
        }
        if( !empty($where) ) {
            $ret = M('investor_detail')->field($fields)->where($where)->order($order)->group($group)->select();
        }
        return $ret;
    }

    /**
     * @param $config_id
     * @param $data
     * @param null $where
     */
    public static function update_investor_detail($borrow_id = null, $data, $where = null)
    {
        $ret = false;
        if( !empty($borrow_id) ) {
            $ret = M('investor_detail')->where(array('borrow_id'=>$borrow_id))->save($data);
        }elseif(!empty($where)) {
            $ret = M('investor_detail')->where($where)->save($data);
        }
        return $ret;
    }

}
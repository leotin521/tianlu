<?php
// +----------------------------------------------------------------------
// | ThinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2008 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

// 管理用户模型
class BorrowInvestorModel extends ACommonModel {
    // 原transfer_borrow_investor 状态  1:还款中     2:正常完成， 对应下面 4 和 5
    // 状态：1代表等待复审；2代表标未满，返回； 3代表标审核未通过，返回； 4代表审核通过，还款中； 5代表正常完成；6代表网站代还完成； 7代表逾期还款；
    const BID_INVEST_STATUS_WAIT_REVIEW = 1; //代表等待复审
    const BID_INVEST_STATUS_UNFINISHED = 2;
    const BID_INVEST_STATUS_REVIEW_FAIL = 3;
    const BID_INVEST_STATUS_REPAYMENT = 4; //代表(审核通过)还款中,即投计息没有复审
    const BID_INVEST_STATUS_SUCCESS = 5; //代表正常完成
    const BID_INVEST_STATUS_PLATFORM_REPAY = 6; //  p2p当中，会员逾期网站会代还完成，已逾期的状态对“前台”用户来说没有什么意义，因为网站代还了
    const BID_INVEST_STATUS_OVERDUE = 7;  // 已逾期
    const BID_INVEST_STATUS_TRANSFER = 14; // 14:转让中

    const BID_INVEST_SOURCE_WEB = 0; // pc
    const BID_INVEST_SOURCE_WEIXIN = 1; //微信
    const BID_INVEST_SOURCE_APP = 2; // APP
	protected $tableName = 'borrow_investor';

    /**
     * 投资详情分页显示
     * @param bool $where
     * @param string $fields
     * @param int $page
     * @param bool $getCount
     * @param bool $count
     * @param bool $orderBy
     * @return bool
     * @throws Exception
     */
    public static function getBorrowInvestByPage($where = false, $fields = '*', $page = 1, $limit = false, $getCount = false, $orderBy = false)
    {
        $ret = false;
        try {
            $pre = C('DB_PREFIX');
            //分页处理
            import("ORG.Util.Page");
            $count = M('borrow_investor bi')->join("{$pre}members m ON m.id=bi.investor_uid")->where($where)->count('bi.id');
            if( $count > 0 ) {
                $p = new Page($count, C('ADMIN_PAGE_SIZE'));
                if( !empty($limit) ) $p->listRows = $limit;
                $nowPage = ($page-1) * ($p->listRows);

                $Lsql = "$nowPage,{$p->listRows}";
                //分页处理
                $list = M('borrow_investor bi')->field($fields)
                    ->join("{$pre}members m ON m.id=bi.investor_uid")
                    ->join("{$pre}borrow_info b ON b.id=bi.borrow_id")
                    ->where($where)->limit($Lsql)->order("bi.id DESC")->select();
                $show = $p->show();
                $list = TborrowModel::_listFilter($list);
                $ret['invest_items'] = $list;
                $ret['page'] = $show;
            }
        } catch( Exception $e ) {
            throw $e;
        }
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
            $ret = M("borrow_investor")->where($where)->sum('investor_interest');
        } catch( Exception $e) {
            throw $e;
        }
        return $ret;
    }
    
    /**
     * 判断当前用户是否可以投新手标（是否第一次投标）
     */
    public static function get_is_novice($uid)
    {
        if (empty($uid)) return false;
        $result = false;
        $count = M("borrow_investor")->where("investor_uid=".$uid)->count('id');
        if ($count>0){
            $result = false;
        }else{
            $result = true; 
        }
        return $result;
    }

    /**
     * 获得投资来源
     * @param null $type
     * @return array|bool
     */
    public static function get_invest_source($type = null)
    {
        $result = false;
        $invest_type = array(
            self::BID_INVEST_SOURCE_WEB => '网站',
            self::BID_INVEST_SOURCE_WEIXIN => '微信',
            self::BID_INVEST_SOURCE_APP => 'APP'
        );
        if(isset($type)) {
            if( isset($invest_type[$type]) ) {
                $result = $invest_type[$type];
            }
        } else {
            $result = $invest_type;
        }
        return $result;
    }

    /**
     * 即投计息借款管理费
     * @param $invest_money  用户投资金额
     * @param $borrow_money  借款总金额
     * @param $borrow_fee  总的借款管理费
     */
    public static function get_current_borrow_fee($invest_money, $borrow_money,  $borrow_fee)
    {
        if( $invest_money > 0 && $borrow_money > 0 && $borrow_fee > 0 ) {
            return bcmul($borrow_fee, bcdiv($invest_money, $borrow_money, 2), 2);
        }
    }



}
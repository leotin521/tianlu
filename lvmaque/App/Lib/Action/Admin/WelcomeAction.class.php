<?php
// 本类由系统自动生成，仅供测试用途
class WelcomeAction extends ACommonAction {

	var $justlogin = true;
	
    public function index(){
        $version = FS("Webconfig/version");
        $str = '';
        if ($version['single']==1) {
            $str .= 'p2p+';
        }
        if ($version['business']==1) {
            $str .= 'p2c+';
        }
        if ($version['fund']==1) {
            $str .= '定投宝+';
        }
        if ($version['agility']==1) {
            $str .= '灵活宝+';
        }
        if ($version['manualcapital']==1 || $version['autocapital'] == 1) {
            $str .= '配资+';
        }
        if ($version['crowdfunding']==1) {
            $str .= '众筹+';
        }
        if ($version['mobile']==1) {
            $str .= '移动端+';
        }
        if ($version['wechat']==1) {
            $str .= '微信端+';
        }
        $str = rtrim($str,'+');
        $this->assign("str",$str);
        $this->assign("version",$version);
        #短信接口状态
        $datag = get_global_setting();
        $is_manual = $datag['is_manual'];
        $this->assign("is_manual",$is_manual);
        #实名认证状态
        $id5_config = FS("Webconfig/id5");
        $this->assign("id5_enable",$id5_config['enabled']);
        #待审核工作区 start.
		$row['borrow_1'] = M('borrow_info')->where('borrow_status=0 AND borrow_type<6')->count('id');//散标等待初审
		$row['borrow_2'] = M('borrow_info')->where('borrow_status=4 AND borrow_type<6')->count('id');//散标等待复审
		$row['borrow_3'] = M('borrow_info')->where('borrow_status=-1 AND borrow_type<6')->count('id');//散标预发布
		$row['borrow_bus'] = M('borrow_info')->where('borrow_status=4 AND borrow_type=6 and rate_type=2')->count('id');//企业直投等待复审
		$row['borrow_pre'] = M('borrow_info')->where('borrow_status=-1 AND borrow_type=6')->count('id');//企业直投预发布
		$row['borrow_fund'] = M('borrow_info')->where('borrow_status=-1 AND borrow_type=7')->count('id');//定投宝预发布
		$row['limit_a'] = M('member_apply')->where('apply_status=0')->count('id');//额度
		$row['data_up'] = M('member_data_info')->where('status=0')->count('id');//上传资料
		$row['real_a'] = M('members_status')->where('id_status=3')->count('uid');//实名认证
		$row['mobile_a'] = M('members_status')->where('phone_status=3')->count('uid');//手机认证
		$row['withdraw'] = M('member_withdraw')->where('withdraw_status=0')->count('id');//待审核提现
		$row['loan'] = M('borrow_apply')->where('status=1')->count('id');//借款会员待审核
		
		$this->assign("row",$row);
		#待审核工作区  end.
		$sql_chart_1 = "select count(x.t) as e  from (select count(*) as t from lzh_borrow_info group by borrow_uid) as x";
		$chart1_borrow = M()->query($sql_chart_1);
		$memberCount = M("members")->count("*");
		$sql_chart_3 = "select count(x.t) as e  from (select count(*) as t from lzh_borrow_investor group by investor_uid) as x";
		$chart1_invest = M()->query($sql_chart_3);
		$chart_1_total = intval($memberCount) + intval( $chart1_invest[0]['e']) + intval($chart1_borrow[0]['e']);
		$chart_1 = array(
						"register" => intval($memberCount),
						"invest" => intval($chart1_invest[0]['e']),
						"borrow" => intval($chart1_borrow[0]['e']),
						"register_rate" => getfloatvalue(intval($memberCount) / $chart_1_total * 100, 2),
						"invest_rate" => getfloatvalue(intval($chart1_invest[0]['e']) / $chart_1_total * 100, 2)
		);
		$this->assign("chart_one", $chart_1);
		
		$start = strtotime(date("Y-m-01", time())." 00:00:00");
		$end = strtotime(date("Y-m-t", time())." 23:59:59");
		$mapChart2 = array();
		$mapChart2['full_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$mapChart2['borrow_status'] = array("in", "6,7,8,9");
		$mapTChart2 = array();
		$mapTChart2['add_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$month = array();
		$moneyMonth = M("borrow_info")->where($mapChart2)->sum("borrow_money");
		$moneyMonth_t = M("transfer_borrow_info")->where($mapTChart2)->sum("borrow_money");
		$mapRChart2 = array();
		$mapRChart2['repayment_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$moneyMonth_r = M("investor_detail")->where($mapRChart2)->sum("receive_capital");
		$month['money_repayment'] = getFloatvalue($moneyMonth_r / 10000, 2);
		$month['money_normal'] = getFloatvalue($moneyMonth / 10000, 2);
		$month['money_transfer'] = getFloatvalue($moneyMonth_t / 10000, 2);
		$month['month'] = date("Y-m", $end);
		
		
		
		$start = strtotime("-1 months", strtotime(date("Y-m-01", time())." 00:00:00"));
		$end = strtotime(date("Y-m-t", strtotime("-1 months", time()))." 23:59:59");
		$mapChart2 = array();
		$mapChart2['full_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$mapChart2['borrow_status'] = array("in", "6,7,8,9");
		$mapTChart2 = array();
		$mapTChart2['add_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$month1 = array();
		$moneyMonth1 = M("borrow_info")->where($mapChart2 )->sum("borrow_money");
		$moneyMonth1_t = M("transfer_borrow_info")->where($mapTChart2)->sum("borrow_money");
		$mapRChart2 = array();
		$mapRChart2['repayment_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$moneyMonth1_r = M("investor_detail")->where($mapRChart2)->sum("receive_capital");
		$month1['money_repayment'] = getFloatvalue($moneyMonth1_r / 10000, 2);
		$month1['money_normal'] = getFloatvalue($moneyMonth1 / 10000, 2);
		$month1['money_transfer'] = getFloatvalue($moneyMonth1_t / 10000, 2);
		$month1['month'] = date("Y-m", $end);
		$start = strtotime("-2 months",strtotime( date( "Y-m-01", time())." 00:00:00"));
		$end = strtotime(date("Y-m-t", strtotime( "-2 months", time()))." 23:59:59");
		$mapChart2 = array();
		$mapChart2['full_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$mapChart2['borrow_status'] = array("in","6,7,8,9");
		$mapTChart2 = array();
		$mapTChart2['add_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$month2 = array();
		$moneyMonth2 = M("borrow_info")->where($mapChart2)->sum("borrow_money");
		$moneyMonth2_t =M("transfer_borrow_info")->where($mapTChart2)->sum("borrow_money");
		$mapRChart2 = array();
		$mapRChart2['repayment_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$moneyMonth2_r = M("investor_detail")->where($mapRChart2)->sum("receive_capital");
		$month2['money_repayment'] = getfloatvalue( $moneyMonth2_r / 10000, 2);
		$month2['money_normal'] = getfloatvalue( $moneyMonth2 / 10000, 2);
		$month2['money_transfer'] = getfloatvalue( $moneyMonth2_t / 10000, 2);
		$month2['month'] = date("Y-m", $end );
		$start = strtotime("-3 months", strtotime(date("Y-m-01", time())." 00:00:00"));
		$end = strtotime( date("Y-m-t", strtotime("-3 months", time()))." 23:59:59");
		$mapChart2 = array();
		$mapChart2['full_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$mapChart2['borrow_status'] = array("in","6,7,8,9");
		$mapTChart2 = array( );
		$mapTChart2['add_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$month3 = array();
		$moneyMonth3 = M("borrow_info")->where($mapChart2)->sum("borrow_money");
		$moneyMonth3_t = M("transfer_borrow_info")->where( $mapTChart2 )->sum("borrow_money");
		$mapRChart2 = array();
		$mapRChart2['repayment_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$moneyMonth3_r = M("investor_detail")->where($mapRChart2)->sum("receive_capital");
		$month3['money_repayment'] = getFloatvalue($moneyMonth3_r / 10000, 2);
		$month3['money_normal'] = getFloatvalue($moneyMonth3 / 10000, 2);
		$month3['money_transfer'] = getFloatvalue($moneyMonth3_t / 10000, 2);
		$month3['month'] = date( "Y-m", $end );
		$start = strtotime( "-4 months", strtotime( date( "Y-m-01", time( ) )." 00:00:00" ) );
		$end = strtotime( date( "Y-m-t", strtotime( "-4 months", time( ) ) )." 23:59:59" );
		$mapChart2 = array( );
		$mapChart2['full_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$mapChart2['borrow_status'] = array( "in", "6,7,8,9" );
		$mapTChart2 = array( );
		$mapTChart2['add_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$month4 = array( );
		$mapRChart2 = array( );
		$mapRChart2['repayment_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$moneyMonth4_r = M( "investor_detail" )->where( $mapRChart2 )->sum( "receive_capital" );
		$month4['money_repayment'] = getfloatvalue( $moneyMonth4_r / 10000, 2 );
		$moneyMonth4 = M( "borrow_info" )->where( $mapChart2 )->sum( "borrow_money" );
		$moneyMonth4_t = M( "transfer_borrow_info" )->where( $mapTChart2 )->sum( "borrow_money" );
		$month4['money_normal'] = getfloatvalue( $moneyMonth4 / 10000, 2 );
		$month4['money_transfer'] = getfloatvalue( $moneyMonth4_t / 10000, 2 );
		$month4['month'] = date( "Y-m", $end );
		
		$start = strtotime("-5 months", strtotime(date("Y-m-01", time())." 00:00:00"));
		$end = strtotime(date("Y-m-t", strtotime("-5 months", time()))." 23:59:59");
		$mapChart2 = array();
		$mapChart2['full_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$mapChart2['borrow_status'] = array("in", "6,7,8,9");
		$mapTChart2 = array();
		$mapTChart2['add_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$month5 = array();
		$moneyMonth5 = M("borrow_info")->where($mapChart2 )->sum("borrow_money");
		$moneyMonth5_t = M("transfer_borrow_info")->where($mapTChart2)->sum("borrow_money");
		$mapRChart2 = array();
		$mapRChart2['repayment_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$moneyMonth5_r = M("investor_detail")->where($mapRChart2)->sum("receive_capital");
		$month5['money_repayment'] = getFloatvalue($moneyMonth5_r / 10000, 2);
		$month5['money_normal'] = getFloatvalue($moneyMonth5 / 10000, 2);
		$month5['money_transfer'] = getFloatvalue($moneyMonth5_t / 10000, 2);
		$month5['month'] = date("Y-m", $end);
		
		$start = strtotime("-6 months", strtotime(date("Y-m-01", time())." 00:00:00"));
		$end = strtotime(date("Y-m-t", strtotime("-6 months", time()))." 23:59:59");
		$mapChart2 = array();
		$mapChart2['full_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$mapChart2['borrow_status'] = array("in", "6,7,8,9");
		$mapTChart2 = array();
		$mapTChart2['add_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$month6 = array();
		$moneyMonth6 = M("borrow_info")->where($mapChart2)->sum("borrow_money");
		$moneyMonth6_t = M("transfer_borrow_info")->where($mapTChart2)->sum("borrow_money");
		$mapRChart2 = array();
		$mapRChart2['repayment_time'] = array(
						"between",
						"{$start},{$end}"
		);
		$moneyMonth6_r = M("investor_detail")->where($mapRChart2)->sum("receive_capital");
		$month6['money_repayment'] = getFloatvalue($moneyMonth6_r / 10000, 2);
		$month6['money_normal'] = getFloatvalue($moneyMonth6 / 10000, 2);
		$month6['money_transfer'] = getFloatvalue($moneyMonth6_t / 10000, 2);
		$month6['month'] = date("Y-m", $end);
		
		$this->assign("month6", $month6);
		$this->assign("month5", $month5);
		$this->assign("month4", $month4);
		$this->assign("month3", $month3);
		$this->assign("month2", $month2);
		$this->assign("month1", $month1);
		$this->assign("month", $month);
		
		//dump($month2);exit;
		////////////////////////////////////////////////////////////
	
		$this->getServiceInfo();
        $this->getAdminInfo();
		$this->display();
    }
	
	private function getServiceInfo()
    {
        $service['service_name'] = php_uname('s');//服务器系统名称
        $service['service'] = $_SERVER['SERVER_SOFTWARE'];   //服务器版本
        $service['zend'] = 'Zend '.Zend_Version();    //zend版本号
        $service['ip'] = GetHostByName($_SERVER['SERVER_NAME']); //服务器ip
        $service['mysql'] = mysql_get_server_info();
        $service['filesize'] = ini_get("upload_max_filesize");
        
        $this->assign('service', $service);
    }
	
    private function getAdminInfo()
    {
        $id = $_SESSION['admin_id'];
        $userinfo = M('ausers a')
                    ->field('a.user_name, c.groupname')
                    ->join(C('DB_PREFIX').'acl as c on a.u_group_id = c.group_id')
                    ->where(" a.id={$id}")
                    ->find();                      
        $userinfo['last_log_time'] = $_SESSION['admin_last_log_time'];
        $userinfo['last_log_ip'] = $_SESSION['admin_last_log_ip'];
        $this->assign('user',$userinfo);
    }
	
}
<?php
// 管理员管理
class BrokermoneyAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function index()
    {
		$tmap = "";//`mxl:team20141217`
		$tmaps['v.status'] = array("in", "4,5,6,7");/* mxl 20150617 */
        import("ORG.Util.Page");
		if($_REQUEST['user_name']){
			$map['user_name'] = urldecode($_REQUEST['user_name']);
			$user_name = $map['user_name'];
		}
		if($_REQUEST['start_time'] && $_REQUEST['end_time']){
		    $timespan = strtotime(urldecode($_REQUEST['start_time']))." AND ".strtotime(urldecode($_REQUEST['end_time']));//`mxl:team20141217`
			$tmaps['v.add_time'] = array("between", implode(",", explode(" AND ", $timespan)));//`mxl:team20141217`
			$tmap = "add_time between {$timespan} AND ";//`mxl:team20141217`
			$search['start_time'] = urldecode($_REQUEST['start_time']);	
			$search['end_time'] = urldecode($_REQUEST['end_time']);
		}elseif(!empty($_REQUEST['start_time'])){
			$xtime = strtotime(urldecode($_REQUEST['start_time']));
			$tmaps['v.add_time'] = array("gt",$xtime);//`mxl:team20141217`
			$tmap = "add_time > {$xtime} AND ";//`mxl:team20141217`
			$search['start_time'] = urldecode($_REQUEST['start_time']);//`mxl:team20141217`
			//$search['start_time'] = $xtime;//`mxl:team20141217hide`
		}elseif(!empty($_REQUEST['end_time'])){
			$xtime = strtotime(urldecode($_REQUEST['end_time']));
			$tmaps['v.add_time'] = array("lt",$xtime);//`mxl:team20141217`
			$tmap = "add_time < {$xtime} AND ";//`mxl:team20141217`
			$search['end_time'] = urldecode($_REQUEST['end_time']);//`mxl:team20141217`
			//$search['end_time'] = $xtime;//`mxl:team20141217hide`
		}
		//$map['u_group_id'] = 26;//`mxl:team20150117`hide
		//`mxl:team20150116`
		$aid = session('admin_id');
		if (chkGroup($aid, $ainfo) === false){ $this->error("请重新登录"); }
		$this->assign("ainfo", $ainfo);
		$map['u_group_id'] = $ainfo['r_member'];
		if ($ainfo['me'] === "member"){ $map['id'] = $ainfo['id']; }
		if ($ainfo['me'] === "leader"){ $map['parent'] = $ainfo['id']; }
		//`mxl:team20150116`
		$AdminU = M('ausers');
		$page_size = ($page_szie==0)?C('ADMIN_PAGE_SIZE'):$page_szie;
		
		
		$count  = $AdminU->where($map)->count(); // 查询满足要求的总记录数   
		$Page = new Page($count,$page_size); // 实例化分页类传入总记录数和每页显示的记录数   
		$show = $Page->show(); // 分页显示输出
		   
		$fields = "id,user_name,u_group_id,real_name,is_ban,area_name,is_kf,qq,phone,user_word";
		$order = "id DESC";
		
		$list = $AdminU->field(true)->where($map)->order($order)->limit($Page->firstRow.','.$Page->listRows)->select();

		$AdminUserList = $list;
		
		$datag = get_global_setting();
		//`mxl:team20141218`
		//计算总额
		foreach ($tmaps as $k => $v){ $map[$k] = $v; }
		$result1 = M("ausers a")->field("sum(v.investor_capital * i.borrow_duration / if (i.repayment_type = 1, '365', '12')) as total")
								->where($map)->join("{$this->pre}members m ON a.id = m.recommend_id")
								->join("{$this->pre}borrow_investor v ON m.id = v.investor_uid")
								->join("{$this->pre}borrow_info i ON v.borrow_id = i.id")->select();
		$result2 = M("ausers a")->field("sum(v.investor_capital * i.borrow_duration / if (i.repayment_type = 1, '365', '12')) as total")
								->where($map)->join("{$this->pre}members m ON a.id = m.recommend_id")
								->join("{$this->pre}transfer_borrow_investor v ON m.id = v.investor_uid")
								->join("{$this->pre}transfer_borrow_info i ON v.borrow_id = i.id")->select();
		$result = ($result1[0]['total'] + $result2[0]['total']) * $datag['broker_push_money'];
		//`mxl:team20141218`
		$total = 0;
		foreach($AdminUserList as $key => $v){
			$idlist = array();
			$mid = M('members')->field('id')->where("recommend_id={$v['id']}")->select();
			foreach($mid as $km =>$vm){
			    array_push($idlist,$vm['id']);
			}
			//`mxl:weighted`
			$sidlist = implode(",", $idlist);
			$map1 = "v.investor_uid in ( {$sidlist} ) AND v.status IN (4,5,6,7)";/* mxl 20150617 */
			if (strlen($tmap) > 0){ $map1 = "v.".$tmap.$map1; }//`mxl:team20141217`
			$field1 = "sum(v.investor_capital * i.borrow_duration / if (i.repayment_type = 1, 365, 12)) as total";
			$sql = "SELECT {$field1} FROM `{$this->pre}borrow_investor` v JOIN `{$this->pre}borrow_info` i ON v.borrow_id = i.id WHERE ( {$map1} )";
			$total1 = M()->query($sql);
			$map2 = "tv.investor_uid in ( {$sidlist} ) AND tv.status IN (1,2)";/* mxl 20150617 */
			if (strlen($tmap) > 0){ $map2 = "tv.".$tmap.$map2; }//`mxl:team20141217`
			$map2 = "1 = 0";
			$field2 = "sum(tv.investor_capital * ti.borrow_duration / if (ti.repayment_type = 1, 365, 12)) as total";
			$sql = "SELECT {$field2} FROM `{$this->pre}transfer_borrow_investor` tv JOIN `{$this->pre}transfer_borrow_info` ti ON tv.borrow_id = ti.id WHERE ( {$map2} )";
			$total2 = M()->query($sql);
			$AdminUserList[$key]['money'] = ($total1[0]['total'] + $total2[0]['total']) * $datag['broker_push_money'];
			//`mxl:weighted`
			$total = $total + $AdminUserList[$key]['money'];
		}

		$this->assign('position', '经纪人提成统计');
		$this->assign('pagebar', $show);
		$this->assign('total', $result);//`mxl:team20141218`
		$this->assign('admin_list', $AdminUserList);
		$this->assign("search",$search);
		$this->assign("user_name",$user_name);
		$this->assign("query", http_build_query($search));
        $this->display();
    }
	//经纪人提成统计导出
	public function export(){
		$tmap = "";//`mxl:team20141217`
		import("ORG.Io.Excel");
		import("ORG.Util.Page");
		if($_REQUEST['user_name']){
		    $map['user_name'] = urldecode($_REQUEST['user_name']);
		}
		if($_REQUEST['start_time'] && $_REQUEST['end_time']){
		    $timespan = strtotime(urldecode($_REQUEST['start_time']))." AND ".strtotime(urldecode($_REQUEST['end_time']));//`mxl:team20141218`
			//$tmap['add_time'] = array("between",$timespan);//`mxl:team20141218hide`
			$tmap = "add_time between {$timespan} AND ";//`mxl:team20141218`
			$search['start_time'] = urldecode($_REQUEST['start_time']);	
			$search['end_time'] = urldecode($_REQUEST['end_time']);
		}elseif(!empty($_REQUEST['start_time'])){
			$xtime = strtotime(urldecode($_REQUEST['start_time']));
			//$tmap['add_time'] = array("gt",$xtime);//`mxl:team20141218hide`
			$tmap = "add_time > {$xtime} AND ";//`mxl:team20141218`
			$search['start_time'] = urldecode($_REQUEST['start_time']);//`mxl:team20141218`
			//$search['start_time'] = $xtime;//`mxl:team20141218hide`
		}elseif(!empty($_REQUEST['end_time'])){
			$xtime = strtotime(urldecode($_REQUEST['end_time']));
			//$tmap['add_time'] = array("lt",$xtime);//`mxl:team20141218hide`
			$tmap = "add_time < {$xtime} AND ";//`mxl:team20141218`
			$search['end_time'] = urldecode($_REQUEST['end_time']);//`mxl:team20141218`
			//$search['end_time'] = $xtime;//`mxl:team20141218hide`
		}
		//$map['u_group_id'] = 26;//`mxl:team20150117`hide
		//`mxl:team20150116`
		$aid = session('admin_id');
		if (chkGroup($aid, $ainfo) === false){ $this->error("请重新登录"); }
		$this->assign("ainfo", $ainfo);
		$map['u_group_id'] = $ainfo['r_member'];
		if ($ainfo['me'] === "member"){ $map['id'] = $ainfo['id']; }
		if ($ainfo['me'] === "leader"){ $map['parent'] = $ainfo['id']; }
		//`mxl:team20150116`
		$AdminU = M('ausers');
		   
		$fields = "id,user_name,u_group_id,real_name,is_ban,area_name,is_kf,qq,phone,user_word";
		$order = "id DESC";
		
		$list = $AdminU->field(true)->where($map)->order($order)->select();

		$AdminUserList = $list;
		$datag = get_global_setting();
		$total = 0;
		foreach($AdminUserList as $key => $v){
			$idlist = array();
			$mid = M('members')->field('id')->where("recommend_id={$v['id']}")->select();
			foreach($mid as $km =>$vm){
			    array_push($idlist,$vm['id']);
			}
			//`mxl:weighted`
			$sidlist = implode(",", $idlist);
			$map1 = "v.investor_uid in ( {$sidlist} ) AND v.status IN (4,5,6,7)";/* mxl 20150617 */
			if (strlen($tmap) > 0){ $map1 = "v.".$tmap.$map1; }//`mxl:team20141217`
			$field1 = "sum(v.investor_capital * i.borrow_duration / if (i.repayment_type = 1, 365, 12)) as total";
			$sql = "SELECT {$field1} FROM `{$this->pre}borrow_investor` v JOIN `{$this->pre}borrow_info` i ON v.borrow_id = i.id WHERE ( {$map1} )";
			$total1 = M()->query($sql);
			$map2 = "tv.investor_uid in ( {$sidlist} ) AND tv.status IN (1,2)";/* mxl 20150617 */
			if (strlen($tmap) > 0){ $map2 = "tv.".$tmap.$map2; }//`mxl:team20141217`
			$map2 = "1 = 0";
			$field2 = "sum(tv.investor_capital * ti.borrow_duration / if (ti.repayment_type = 1, 365, 12)) as total";
			$sql = "SELECT {$field2} FROM `{$this->pre}transfer_borrow_investor` tv JOIN `{$this->pre}transfer_borrow_info` ti ON tv.borrow_id = ti.id WHERE ( {$map2} )";
			$total2 = M()->query($sql);
			$AdminUserList[$key]['money'] = ($total1[0]['total'] + $total2[0]['total']) * $datag['broker_push_money'];
			$AdminUserList[$key]['capital'] = $total1[0]['total'] + $total2[0]['total'];//资产总额
			//`mxl:weighted`
			$AdminUserList[$key]['num'] = count($idlist);//客户总数
			
		}
		
		$row=array();
		$row[0]=array('序号','经纪人账号','真实姓名','资产总额','客户总数','交易提成');
		$i=1;
		foreach($AdminUserList as $v){
				$row[$i]['i'] = $i;
				$row[$i]['user_name'] = $v['user_name'];
				$row[$i]['real_name'] = $v['real_name'];
				$row[$i]['capital'] = $v['capital'];
				$row[$i]['num'] = $v['num'];
				$row[$i]['total'] = $v['money'];
				$i++;
		}
		
		$xls = new Excel_XML('UTF-8', false, 'datalist');
		$xls->addArray($row);
		$xls->generateXML("jingjirenticheng");
	}

    public function investorlist()
    {
		$map = array();//`mxl:team20141218`
        import("ORG.Util.Page");
		if($_REQUEST['user_name']){
		    $map['user_name'] = urldecode($_REQUEST['user_name']);
		}
		if($_REQUEST['id']){
			$aname = M('ausers')->getFieldById($_REQUEST['id'],"user_name");
			$brokerid = urldecode($_REQUEST['id']);
			$map['recommend_id'] = $brokerid;
		}
		$members = M('members m');
		$page_size = ($page_szie==0)?C('ADMIN_PAGE_SIZE'):$page_szie;
		$count  = $members->where($map)->count();
		$Page = new Page($count,$page_size); // 实例化分页类传入总记录数和每页显示的记录数   
		$show = $Page->show(); // 分页显示输出
		
		$fields = "m.id,m.user_name,mi.real_name,mi.idcard,m.reg_time";
		$order = "id DESC";
		$mlist = $members->join("{$this->pre}member_info mi ON mi.uid=m.id")->field($field)->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();
		$datag = get_global_setting();
		//`mxl:team20141218`
		//计算总额
		$map['v.status'] = array("in", "4,5,6,7");/* mxl 20150617 */
		$total1 = M("members m")->field("sum(v.investor_capital * i.borrow_duration / if (i.repayment_type = 1, '365', '12')) as total")->where($map)
								->join("{$this->pre}borrow_investor v ON m.id = v.investor_uid")
								->join("{$this->pre}borrow_info i ON v.borrow_id = i.id")->select();
		$map['v.status'] = array("in", "1,2");/* mxl 20150617 */
		$total2 = M("members m")->field("sum(v.investor_capital * i.borrow_duration / if (i.repayment_type = 1, '365', '12')) as total")->where($map)
								->join("{$this->pre}transfer_borrow_investor v ON m.id = v.investor_uid")
								->join("{$this->pre}transfer_borrow_info i ON v.borrow_id = i.id")->select();
		$result = ($total1[0]['total'] + $total2[0]['total']) * $datag['broker_push_money'];
		//`mxl:team20141218`
		foreach($mlist as $km =>$vm){
			//`mxl:weighted`
			$map1 = "v.investor_uid = {$vm['id']} AND v.status IN (4,5,6,7)";/* mxl 20150617 */
			$field1 = "sum(v.investor_capital * i.borrow_duration / if (i.repayment_type = 1, 365, 12)) as total";
			$sql = "SELECT {$field1} FROM `{$this->pre}borrow_investor` v JOIN `{$this->pre}borrow_info` i ON v.borrow_id = i.id WHERE ( {$map1} )";
			$total1 = M()->query($sql);
			$map2 = "1 = 0";//标准版没有企业直投
			$field2 = "sum(tv.investor_capital * ti.borrow_duration / if (ti.repayment_type = 1, 365, 12)) as total";
			$sql = "SELECT {$field2} FROM `{$this->pre}transfer_borrow_investor` tv JOIN `{$this->pre}transfer_borrow_info` ti ON tv.borrow_id = ti.id WHERE ( {$map2} )";
			$total2 = M()->query($sql);
			$mlist[$km]['money'] = ($total1[0]['total'] + $total2[0]['total']) * $datag['broker_push_money'];
			//`mxl:weighted`
			$total = $total + $mlist[$km]['money'];
		}
		
		$this->assign('position', '经纪人提成统计');
		$this->assign('pagebar', $show);
		$this->assign('total', $result);//`mxl:team20141218`
		$this->assign('mlist', $mlist);
		$this->assign('aname', $aname);
		$this->assign('brokerid', $brokerid);
        $this->display();
    }
    
	//`mxl:invlist`
	/**
    +----------------------------------------------------------
    * 投资人明细列表
    +----------------------------------------------------------
    */
	public function listinvestor(){
		$param = array();
		$TYPE_TBORROW = 10;
		$investor_uid = 0;
		$search = array("start_time" => "", "end_time" => "", "excel" => "fileout");
		$param = (isset($_POST['uid']) ? $_POST : $_GET);
		$start_time = (isset($param['start_time']) && strlen(text($param['start_time'])) > 0) ? strtotime(text($param['start_time'])." 00:00:00") : 0;
		$end_time = (isset($param['end_time']) && strlen(text($param['end_time'])) > 0) ? strtotime(text($param['end_time'])." 23:59:59") : time();
		$search['start_time'] = text($param['start_time']);
		$search['end_time'] = text($param['end_time']);
		$map1 .= ((strlen($map1) > 0) ? " and " : "")."v.add_time >= {$start_time} and {$end_time} >= v.add_time";//`mxl:weighted`
		$map2 .= ((strlen($map2) > 0) ? " and " : "")."tv.add_time >= {$start_time} and {$end_time} >= tv.add_time";//`mxl:weighted`
		$investor_uid = intval($param['uid']);
		$investor_uname = text($param['uname']);
		$broker_id = intval($param['broker']);
		if ($investor_uid === 0){
			$this->error("未指定用户");
		}
		$search['uid'] = $investor_uid;
		$search['uname'] = $investor_uname;
		//`mxl:weighted`
		$map1 .= ((strlen($map1) > 0) ? " and " : "")."v.investor_uid = {$investor_uid} AND v.status IN (4,5,6,7)";/* mxl 20150617 */
		$map2 .= ((strlen($map2) > 0) ? " and " : "")."tv.investor_uid = {$investor_uid} AND tv.status IN (1,2)";/* mxl 20150617 */
		$map2 = "1 = 0";//不包括企业直投
		//合计start
		$field1 = "sum(v.investor_capital * i.borrow_duration / if (i.repayment_type = 1, 365, 12)) as total";
		$sql = "SELECT {$field1} FROM `{$this->pre}borrow_investor` v JOIN `{$this->pre}borrow_info` i ON v.borrow_id = i.id WHERE ( {$map1} )";
		$total1 = M()->query($sql);
		$field2 = "sum(tv.investor_capital * ti.borrow_duration / if (ti.repayment_type = 1, 365, 12)) as total";
		$sql = "SELECT {$field2} FROM `{$this->pre}transfer_borrow_investor` tv JOIN `{$this->pre}transfer_borrow_info` ti ON tv.borrow_id = ti.id WHERE ( {$map2} )";
		$total2 = M()->query($sql);
		//合计end
		$field1 = "v.investor_capital, v.add_time, v.status, v.is_auto, i.borrow_name, i.id, i.repayment_type, i.borrow_duration, i.borrow_type";/* 托管版增加i.borrow_type mxl 20150617 */
		$field2 = "tv.investor_capital, tv.add_time, (tv.status + $TYPE_TBORROW) as status, tv.is_auto, ti.borrow_name, ti.id, ti.repayment_type, ti.borrow_duration";
		$sql = "SELECT {$field1} FROM `{$this->pre}borrow_investor` v JOIN `{$this->pre}borrow_info` i ON v.borrow_id = i.id WHERE ( {$map1} )";
		//$sql .= " UNION ALL SELECT {$field2} FROM `{$this->pre}transfer_borrow_investor` tv JOIN `{$this->pre}transfer_borrow_info` ti ON tv.borrow_id = ti.id WHERE ( {$map2} )";
		$count1 = M()->query("SELECT count(*) FROM `{$this->pre}borrow_investor` v WHERE ( {$map1} )");
		$count1 = $count1[0]["count(*)"];
		$count2 = M()->query("SELECT count(*) FROM `{$this->pre}transfer_borrow_investor` tv WHERE ( {$map2} )");
		$count2 = $count2[0]["count(*)"];
		$count = $count1 + $count2;
		//`mxl:weighted`
		
		import("ORG.Util.Page");
		$page_size = C('ADMIN_PAGE_SIZE');
		if (isset($_GET['excel']) && text($_GET['excel']) === "fileout"){
			$page_size = $count;	//输出excel列表时不分页
		}
		$Page = new Page($count, $page_size); // 实例化分页类传入总记录数和每页显示的记录数
		$show = $Page->show(); // 分页显示输出
		
		$order = "add_time DESC";
		$limit = $Page->firstRow.",".$Page->listRows;
		$invlist = M()->query($sql." ORDER by ".$order." LIMIT ".$limit);//投资明细
		//获取本页之前的合计金额
		/* $prevmoney = 0;
		$offset = $Page->firstRow + $Page->listRows;
		if ($count > $offset){
			$limit = $offset.",".($count - $offset);
			$field1 = "add_time, investor_capital";
			$field2 = "add_time, investor_capital";
			$sql = "SELECT {$field1} FROM `{$this->pre}borrow_investor` WHERE ( {$map1} ) UNION ALL SELECT {$field2} FROM `{$this->pre}transfer_borrow_investor` WHERE ( {$map2} )";
			$prev = M()->query($sql." ORDER by ".$order." LIMIT ".$limit);
			foreach ($prev as $v){
				$prevmoney += $v['investor_capital'];
			}
		} *///不再计算累计金额//`mxl:weighted`
		//获取本页之前的合计金额
		
		//计算累计金额、套用状态/类型
		$i = count($invlist);
		$Bconfig = require C("APP_ROOT")."Conf/borrow_config.php";
		$this -> assign("repayment_type", $Bconfig['REPAYMENT_TYPE']);//还款类型列表
		$borrow_status = array("4" => "还款中", "5" => "正常完成", "6" => "网站代还", "7" => "逾期还款");/* mxl 20150617 */
		$borrow_type = $Bconfig['BORROW_TYPE'];/* mxl 20150617 */
		$tborrow_status = array("1" => "还款中", "2" => "还款完成", "3" => "流标");
		while ($i-- > 0){
			$ii = $invlist[$i]['status'];
			if ($ii >= $TYPE_TBORROW){
				$invlist[$i]['type'] = "直投";
				$invlist[$i]['status'] = $tborrow_status[$ii % $TYPE_TBORROW];
			}
			else {
				$invlist[$i]['type'] = "普";
				$invlist[$i]['type'] = $borrow_type[$invlist[$i]['borrow_type']];/* 托管版适用 mxl 20150617 */
				$invlist[$i]['status'] = $borrow_status[$ii];
			}
			$invlist[$i]['weighted'] = round($invlist[$i]['investor_capital'] * $invlist[$i]['borrow_duration'] / (($invlist[$i]['repayment_type'] == "1") ? 365 : 12), 2);//加权//`mxl:weighted`
			//$invlist[$i]['total'] = $invlist[$i]['investor_capital'] + (isset($invlist[$i + 1]) ? $invlist[$i + 1]['total'] : $prevmoney);//不再计算累计金额//`mxl:weighted`
		}
		//计算累计金额、套用状态/类型
		
		//输出excel列表start
		if (isset($_GET['excel']) && text($_GET['excel']) === "fileout"){
			import("ORG.Io.Excel");
			$row = array();
			$row[0] = array("序号", "ID", "项目名称", "投资期限", "还款方式", "投标方式", "投标金额", "加权金额", "日期", "类型", "状态");
			$i = 1;
			foreach($invlist as $v){
					$row[$i]['i'] = $i;
					$row[$i]['id'] = $v['id'];
					//`mxl:weighted`
					$row[$i]['borrow_name'] = cnsubstr($v['borrow_name'], 15);
					$row[$i]['borrow_duration'] = $v['borrow_duration'].(($v['repayment_type'] == "1") ? "天" : "个月");
					$row[$i]['repayment_type'] = $Bconfig['REPAYMENT_TYPE'][$v['repayment_type']];
					$row[$i]['is_auto'] = ($v['is_auto'] == "1") ? "自动投" : "手动";
					//`mxl:weighted`
					$row[$i]['investor_capital'] = $v['investor_capital'];
					$row[$i]['total'] = $v['weighted'];//`mxl:weighted`
					$row[$i]['add_time'] = date("Y-m-d", $v['add_time']);
					$row[$i]['type'] = $v['type'];
					$row[$i]['status'] = $v['status'];
					$i++;
			}
			$xls = new Excel_XML("UTF-8", false, "investorlist");
			$xls->addArray($row);
			$xls->generateXML("investorlist");
			return;
		}
		//输出excel列表end
		
		$this->assign('pagebar', $show);
		$this->assign("search", $search);
		$this->assign("invlist", $invlist);
		$this->assign("user_id", $investor_uid);
		$this->assign("user_name", $investor_uname);
		$this->assign("total", round($total1[0]['total'] + $total2[0]['total'], 2));
		$this->assign("query", http_build_query($search));
		$this->assign("broker", $broker_id);
        $this->display();
	}
    //`mxl:invlist`
}
?>
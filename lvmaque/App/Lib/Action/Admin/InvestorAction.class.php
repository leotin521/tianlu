<?php
// 全局设置
class InvestorAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
	//本函数所有$_REQUEST改为$_GET//`mxl:teamreward`
    public function index()
    {
		$map=array();
		if($_GET['uname']){
			$map['m.user_name'] = array("like",urldecode($_GET['uname'])."%");
			$search['uname'] = urldecode($_GET['uname']);	
		}
		if($_GET['realname']){
			$map['mi.real_name'] = urldecode($_GET['realname']);
			$search['realname'] = $map['mi.real_name'];	
		}
		//身份证号
        if($_GET['idcard']){
			
			$map['mi.idcard']  = urldecode($_GET['idcard']);
			$search['idcard'] = $map['mi.idcard'];	
		}
		 
		//手机
		if($_GET['user_phone'] ){
			$map['m.user_phone'] = urldecode($_GET['user_phone']);
			$search['user_phone'] = urldecode($_GET['user_phone']);	
		}
		 
		 //邮箱
		if($_GET['user_email'] ){
			$map['m.user_email'] = urldecode($_GET['user_email']);
			$search['user_email'] = urldecode($_GET['user_email']);	
		}
        if ($_REQUEST['is_transfer']) {
            $map['m.is_transfer'] = intval($_REQUEST['is_transfer']);
            $search['is_transfer'] = intval($_REQUEST['is_transfer']);
        }

		if(session('admin_is_kf')==1){
				//$map['m.customer_id'] = session('admin_id');//客户反映比较多，取消此项限制//`mxl:team20150116`hide
		}else{
			if($_GET['customer_name']){
				$map['m.customer_id'] = $_GET['customer_id'];
				$search['customer_id'] = $map['m.customer_id'];	
				$search['customer_name'] = urldecode($_GET['customer_name']);	
			}
			
			if($_GET['customer_name']){
				$cusname = urldecode($_GET['customer_name']);
				$kfid = M('ausers')->getFieldByUserName($cusname,'id');
				$map['m.customer_id'] = $kfid;
				$search['customer_name'] = $cusname;	
				$search['customer_id'] = $kfid;	
			}
		}
		if(!empty($_GET['bj']) && !empty($_GET['lx']) && !empty($_GET['money'])){
			
			if($_GET['lx']=='allmoney'){
				if($_GET['bj']=='gt'){
					$bj = '>';
				}else if($_GET['bj']=='lt'){
					$bj = '<';
				}else if($_GET['bj']=='eq'){
					$bj = '=';
				}
				$map['_string'] = "(mm.account_money+mm.back_money) ".$bj.$_GET['money'];
			}else{
				$map[$_GET['lx']] = array($_GET['bj'],$_GET['money']);
			}
			$search['bj'] = $_GET['bj'];	
			$search['lx'] = $_GET['lx'];	
			$search['money'] = $_GET['money'];	
		}

		if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
			$timespan = strtotime(urldecode($_GET['start_time'])).",".strtotime(urldecode($_REQUEST['end_time']));
			$map['m.reg_time'] = array("between",$timespan);
			$search['start_time'] = urldecode($_GET['start_time']);	
			$search['end_time'] = urldecode($_GET['end_time']);	
		}elseif(!empty($_GET['start_time'])){
			$xtime = strtotime(urldecode($_GET['start_time']));
			$map['m.reg_time'] = array("gt",$xtime);
			$search['start_time'] = $xtime;	
		}elseif(!empty($_GET['end_time'])){
			$xtime = strtotime(urldecode($_GET['end_time']));
			$map['m.reg_time'] = array("lt",$xtime);
			$search['end_time'] = $xtime;	
		}
		$map['m.recommend_type'] = array('exp',"=1 or (m.recommend_type=0 and m.recommend_id=0)");
		//分页处理
		import("ORG.Util.Page");
		$count = M('members m')->join("{$this->pre}member_money mm ON mm.uid=m.id")->join("{$this->pre}member_info mi ON mi.uid=m.id")->where($map)->count('m.id');
		$p = new Page($count, C('ADMIN_PAGE_SIZE'));
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
		$field= 'm.id,m.user_phone,m.reg_time,m.user_name,m.user_email,m.user_phone,mi.idcard,mi.real_name,m.customer_name,m.user_leve,m.time_limit,mi.real_name,mm.money_freeze,mm.money_collect,(mm.account_money+mm.back_money) account_money,m.user_email,m.recommend_id,m.is_borrow,m.is_transfer,m.recommend_type,m.guanlian_time';
		$list = M('members m')->field($field)->join("{$this->pre}member_money mm ON mm.uid=m.id")->join("{$this->pre}member_info mi ON mi.uid=m.id")->where($map)->limit($Lsql)->order('m.id DESC')->select();
		//dump(M()->GetLastsql());
		$list=$this->_listFilter($list);
		//`mxl:team20150116`
		$list=$this->_listFilter2($list);
		$aid = session('admin_id');
		if (chkGroup($aid, $ainfo) === false){ $this->error("请重新登录"); }
		$this->assign("ainfo", $ainfo);
		//`mxl:team20150116`
        $this->assign("bj", array("gt"=>'大于',"eq"=>'等于',"lt"=>'小于'));
        $this->assign("lx", array("allmoney"=>'可用余额',"mm.money_freeze"=>'冻结金额',"mm.money_collect"=>'待收金额'));
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);
        $this->assign("query", http_build_query($search));

        $this->display();
    }
    
	public function relieve(){
	    $id =$_POST['aid'];
		if(empty($id)){
		    ajaxmsg("无法解除",0);
		}else{
			//`mxl:team20150116`
			$aid = session('admin_id');
			if (chkGroup($aid, $ainfo) === false){ ajaxmsg("请重新登录", 0); }
			if ($ainfo['me'] === "member"){ ajaxmsg("没有权限进行解除关联操作", 0); }
			if ($ainfo['me'] === "leader"){
				$mid = M('members')->where("id = {$id}")->getField("recommend_id");
				$pid = M("ausers")->where("id = {$mid}")->getField("parent");
				if ($pid != $aid){ ajaxmsg("没有权限进行解除关联操作", 0); }
			}
			$data['recommend_type'] = 0;//类型还原为推荐人
			//`mxl:team20150116`
			$data['recommend_id'] = 0;
		    $op = M('members')->where("id={$id}")->save($data);
			if($op){
			     ajaxmsg("解除关联成功");
			}else{
			     ajaxmsg("解除关系失败",0);
			}
		}
		
	}
	public function ajax_reset(){
	    $investorid = $_GET['id'];
		$this->assign('investorid', $investorid);
		$data['content'] = $this->fetch();
		exit(json_encode($data));
	}
	public function reset(){
		$investorid = $_GET['id'];
		import("ORG.Util.Page");
		
		$AdminU = M('ausers');
		$page_size = ($page_szie==0)?C('ADMIN_PAGE_SIZE'):$page_szie;
		$page_size = 10;
		
		//`mxl:team20150116`
		$aid = session('admin_id');
		if (chkGroup($aid, $ainfo) === false){ $this->error("请重新登录"); }
		$map['u_group_id'] = $ainfo['r_member'];
		if ($ainfo['me'] === "leader"){ $map['parent'] = $ainfo['id']; }
		$count = $AdminU->where($map)->count();
		//`mxl:team20150116`
		//$count  = $AdminU->where("u_group_id=26")->count(); // 查询满足要求的总记录数   //`mxl:team20150116`hide
		$Page = new Page($count,$page_size); // 实例化分页类传入总记录数和每页显示的记录数   
		$show = $Page->show(); // 分页显示输出
		   
		$fields = "id,user_name,u_group_id,real_name,is_ban,area_name,is_kf,qq,phone,user_word";
		$order = "id DESC,u_group_id DESC";
		//$map['u_group_id'] = 26;//`mxl:team20150116`hide
		$list = $AdminU->field(true)->where($map)->order($order)->limit($Page->firstRow.','.$Page->listRows)->select();

		$AdminUserList = $list;
		
		$GroupArr = get_group_data();
		foreach($AdminUserList as $key => $v){
			$AdminUserList[$key]['groupname'] = $GroupArr[$v['u_group_id']]['groupname'];
		}

		
		$this->assign('pagebar', $show);
		$this->assign('admin_list', $AdminUserList);
		
		$this->assign('investorid', $investorid);
        $this->display();
	}
    public function resetact(){
	    $brokerid =$_POST['setradio'];
		$investorid =$_POST['investorid'];
		if(empty($brokerid)){
		    echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' /><script language='javascript' type='text/javascript'>alert('重置失败');parent.location='/admin/investor/index';</script>";
		}else{
			//`mxl:team20150117`
			$minfo = M("members")->where("id = {$investorid}")->field("recommend_id, recommend_type")->find();
			if ($minfo['recommend_id'] > 0 && $minfo['recommend_type'] == 0){
				$aid = session('admin_id');
				if (chkGroup($aid, $ainfo) === false){ $this->error("请重新登录"); }
				if ($ainfo['me'] === "leader" || $ainfo['me'] === "member"){
					echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' /><script language='javascript' type='text/javascript'>alert('重置失败');parent.location='/admin/investor/index';</script>";
					return;
				}
			}
			//`mxl:team20150117`
			$data['recommend_id'] = $brokerid;
			$data['recommend_type'] = 1;
			$data['guanlian_time'] = time();
		    $op = M('members')->where("id={$investorid}")->save($data);
			if($op){
			     //$this->success("重置成功");
				 echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' /><script language='javascript' type='text/javascript'>alert('重置成功');parent.location='/admin/investor/index';</script>";
				 
			}else{
			     //$this->error("重置关系失败");
				 echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' /><script language='javascript' type='text/javascript'>alert('重置关系失败');parent.location='/admin/investor/index';</script>";
				 
			}
		}
	}
	public function export(){
		import("ORG.Io.Excel");
		alogs("Capitaldetail",0,1,'执行了会员资金明细列表导出操作！');//管理员操作日志
		$map=array();
		if($_REQUEST['uname']){
			$map['m.user_name'] = array("like",urldecode($_REQUEST['uname'])."%");
			$search['uname'] = urldecode($_REQUEST['uname']);	
		}
		if($_REQUEST['realname']){
			$map['mi.real_name'] = urldecode($_REQUEST['realname']);
			$search['realname'] = $map['mi.real_name'];	
		}
		//身份证号
        if($_REQUEST['idcard']){
			
			$map['mi.idcard']  = urldecode($_REQUEST['idcard']);
			$search['idcard'] = $idcard;	
		}
		 
		//手机
		if($_REQUEST['user_phone'] ){
			$map['m.user_phone'] = urldecode($_REQUEST['user_phone']);
			$search['user_phone'] = urldecode($_REQUEST['user_phone']);	
		}
		 
		 //邮箱
		if($_REQUEST['user_email'] ){
			$map['m.user_email'] = urldecode($_REQUEST['user_email']);
			$search['user_email'] = urldecode($_REQUEST['user_email']);	
		}
		if($_REQUEST['is_vip']=='yes'){
			$map['m.user_leve'] = 1;
			$map['m.time_limit'] = array('gt',time());
			$search['is_vip'] = 'yes';	
		}elseif($_REQUEST['is_vip']=='no'){
			$map['_string'] = 'm.user_leve=0 OR m.time_limit<'.time();
			$search['is_vip'] = 'no';	
		}
		if($_REQUEST['is_transfer']=='yes'){
			$map['m.is_transfer'] = 1;
		}elseif($_REQUEST['is_transfer']=='no'){
			$map['m.is_transfer'] = 0;
		}
		
//		if(session('admin_is_kf')==1){
//				$map['m.customer_id'] = session('admin_id');
//		}else{
			if($_REQUEST['customer_name']){
				$map['m.customer_id'] = intval($_REQUEST['customer_id']);
				$search['customer_id'] = $map['m.customer_id'];	
				$search['customer_name'] = urldecode($_REQUEST['customer_name']);	
			}
			
			if($_REQUEST['customer_name']){
				$cusname = urldecode($_REQUEST['customer_name']);
				$kfid = M('ausers')->getFieldByUserName($cusname,'id');
				$map['m.customer_id'] = $kfid;
				$search['customer_name'] = $cusname;	
				$search['customer_id'] = $kfid;	
			}
//		}
		if(!empty($_REQUEST['bj']) && !empty($_REQUEST['lx']) && !empty($_REQUEST['money'])){
			
			if($_REQUEST['lx']=='allmoney'){
				if($_REQUEST['bj']=='gt'){
					$bj = '>';
				}else if($_REQUEST['bj']=='lt'){
					$bj = '<';
				}else if($_REQUEST['bj']=='eq'){
					$bj = '=';
				}
				$map['_string'] = "(mm.account_money+mm.back_money) ".$bj.$_REQUEST['money'];
			}else{
				$map[$_REQUEST['lx']] = array($_REQUEST['bj'],$_REQUEST['money']);
			}
			$search['bj'] = htmlspecialchars($_REQUEST['bj'], ENT_QUOTES);	
			$search['lx'] = htmlspecialchars($_REQUEST['lx'], ENT_QUOTES);	
			$search['money'] = floatval($_REQUEST['money']);	
		}

		if(!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])){
			$timespan = strtotime(urldecode($_REQUEST['start_time'])).",".strtotime(urldecode($_REQUEST['end_time']));
			$map['m.reg_time'] = array("between",$timespan);
			$search['start_time'] = urldecode($_REQUEST['start_time']);	
			$search['end_time'] = urldecode($_REQUEST['end_time']);	
		}elseif(!empty($_REQUEST['start_time'])){
			$xtime = strtotime(urldecode($_REQUEST['start_time']));
			$map['m.reg_time'] = array("gt",$xtime);
			$search['start_time'] = $xtime;	
		}elseif(!empty($_REQUEST['end_time'])){
			$xtime = strtotime(urldecode($_REQUEST['end_time']));
			$map['m.reg_time'] = array("lt",$xtime);
			$search['end_time'] = $xtime;	
		}
		
		$map['m.recommend_type'] = array('exp',"=1 or (m.recommend_type=0 and m.recommend_id=0)");//`mxl:team20150105debug`
		$field= 'm.id,m.user_phone,m.reg_time,m.user_name,m.user_email,m.user_phone,mi.idcard,mi.real_name,m.customer_name,m.user_leve,m.time_limit,mi.real_name,mm.money_freeze,mm.money_collect,(mm.account_money+mm.back_money) account_money,m.user_email,m.recommend_id,m.is_borrow';
		$list = M('members m')->field($field)->join("{$this->pre}member_money mm ON mm.uid=m.id")->join("{$this->pre}member_info mi ON mi.uid=m.id")->where($map)->limit($Lsql)->order('m.id DESC')->select();
		$list=$this->_listFilter2($list);
		$row=array();
		$row[0]=array('序号','用户ID','用户名','真实姓名','身份证号','手机号码','邮箱地址','推荐人','所属客服','会员类型','可用余额','冻结金额','待收金额','注册时间');
		$i=1;
		foreach($list as $v){
				$row[$i]['i'] = $i;
				$row[$i]['uid'] = $v['id'];
				$row[$i]['card_num'] = $v['user_name'];
				$row[$i]['real_name'] = $v['real_name'];
				$row[$i]['idcard'] = $v['idcard'];
				$row[$i]['user_phone'] = $v['user_phone'];
				$row[$i]['user_email'] = $v['user_email'];
				$row[$i]['recommend_name'] = $v['recommend_name'];
				$row[$i]['customer_name'] = $v['customer_name'];
				$row[$i]['user_type'] = $type[$v['user_type']];
				$row[$i]['card_mianfei1'] = $v['account_money'];
				$row[$i]['card_mianfei2'] = $v['freeze_money'];
				$row[$i]['card_mianfei3'] = $v['collect_money'];
				$row[$i]['card_timelimit'] = date("Y-m-d H:i:s",$v['reg_time']);
				
				$i++;
		}
		
		$xls = new Excel_XML('UTF-8', false, 'datalist');
		$xls->addArray($row);
		$xls->generateXML("datalistmembers");
		
	}
    
	
	
    public function infowait()
    {	
		$Bconfig = require C("APP_ROOT")."Conf/borrow_config.php";
		if($_GET['user_name']) $search['m.user_name'] = text($_GET['user_name']);
		else $search=array();
		$list = getMemberApplyList($search,10);
		$this->assign("aType",$Bconfig['APPLY_TYPE']);
		$this->assign("list",$list['list']);
		$this->assign("pagebar",$list['page']);
        $this->display();
    }
	
   
	
	
    public function _listFilter($list){
		$row=array();
		foreach($list as $key=>$v){
			if($v['recommend_id']<>0 && $v['recommend_type']==1){
				$broker = M('ausers')->where("id={$v['recommend_id']}")->field("id,user_name,parent")->find();
				$v['broker_name'] = $broker['user_name'];
				if ($broker['parent']>0){
				    $team = M('ausers')->where("id={$broker['parent']}")->field("id,user_name")->find();
				    $v['team_name'] = $team['user_name'];
				}
			 }
			 
			$v['user_type'] = MembersModel::get_user_type($v['is_transfer']);
			$row[$key]=$v;
		}
		return $row;
	}
	public function _listFilter2($list){
		$row=array();
		foreach($list as $key=>$v){
			if($v['recommend_id']<>0 && $v['recommend_type']==0){//加条件$v['recommend_type']==0//`mxl:team20150117`
				$v['recommend_name'] = M("members")->getFieldById($v['recommend_id'],"user_name");
			 }else{
				$v['recommend_name'] ="无推荐人";
			 }
			 
			($v['user_leve']==1 && $v['time_limit']>time())?$v['user_type'] = "<span style='color:red'>VIP会员</span>":$v['user_type'] = "普通会员";
			$row[$key]=$v;
		}
		return $row;
	}
	
}
?>
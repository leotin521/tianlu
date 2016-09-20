<?php
// 全局设置
class RefereedetailAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
	//qixiugai
    public function index()
    {
		$this->pre = C('DB_PREFIX');
		$map['bi.status']=array('in','4,5,6,7,14');
		$map['m.recommend_type'] = 0;//`mxl:team20141231debug`
		if(!empty($_REQUEST['runame'])){
			$ruid = M("members")->getFieldByUserName(text($_REQUEST['runame']),'id');
			$map['m.recommend_id'] = $ruid;
		}else{
			$map['m.recommend_id'] =array('neq','0');
		}
		if($_REQUEST['uname']){
			$map['m.user_name'] = array("like",urldecode($_REQUEST['uname'])."%");
			$search['uname'] = urldecode($_REQUEST['uname']);	
		}
		if(!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])){
			$timespan = strtotime(urldecode($_REQUEST['start_time'])).",".strtotime(urldecode($_REQUEST['end_time']));
			$map['bi.add_time'] = array("between",$timespan);
			$search['start_time'] = urldecode($_REQUEST['start_time']);	
			$search['end_time'] = urldecode($_REQUEST['end_time']);	
		}elseif(!empty($_REQUEST['start_time'])){
			$xtime = strtotime(urldecode($_REQUEST['start_time']));
			$map['bi.add_time'] = array("gt",$xtime);
			$search['start_time'] = $xtime;	
		}elseif(!empty($_REQUEST['end_time'])){
			$xtime = strtotime(urldecode($_REQUEST['end_time']));
			$map['bi.add_time'] = array("lt",$xtime);
			$search['end_time'] = $xtime;	
		}
		//if(session('admin_is_kf')==1 && m.customer_id!=0)	$map['m.customer_id'] = session('admin_id');
		//分页处理
		import("ORG.Util.Page");
	
		$count = M('borrow_investor bi')->join("{$this->pre}members m ON m.id = bi.investor_uid")->where($map)->count('DISTINCT bi.investor_uid');
		
		$p = new Page($count, C('ADMIN_PAGE_SIZE'));
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
		
		$field= ' sum(bi.investor_capital) investor_capital,count(bi.id) total,bi.investor_uid,m.recommend_id, m.recommend_type,m.id,m.user_name,m.reg_time,mi.real_name,mt.vip_status,mt.phone_status,mt.email_status';//增加m.recommend_type//`mxl:team20141231debug`
		$list = M('borrow_investor bi')->join("{$this->pre}members m ON m.id = bi.investor_uid")->join("{$this->pre}member_info mi ON mi.uid=m.id")->join("{$this->pre}members_status mt ON mt.uid=m.id")->field($field)->where($map)->group('bi.investor_uid')->limit($Lsql)->select();
		
		$list=$this->_listFilter($list);
		
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);
        $this->assign("query", http_build_query($search));
		
        $this->display();
    }
	
	
	public function _listFilter($list){
		$row=array();
		foreach($list as $key=>$v){
			 if($v['recommend_id']<>0 && intval($v['recommend_type']) === 0){//增加判断 && intval($v['recommend_type']) === 0//`mxl:team20141231debug`
				$v['recommend_name'] = M("members")->getFieldById($v['recommend_id'],"user_name");
			 }else{
				$v['recommend_name'] ="<span style='color:red'>无推荐人</span>";
			 }
			 if($v['vip_status'] == 1){
				$v['vip_status'] = "是";
			 }else{
				$v['vip_status'] = "否";
			 }
			  if($v['phone_status'] == 1){
				$v['phone_status'] = "是";
			 }else{
				$v['phone_status'] = "否";
			 }
			  if($v['real_name'] == ''){
				$v['real_name'] = "未认证";
			 }else{
				$v['real_name'] = $v['real_name'];
			 }
			  if($v['email_status'] == 1){
				$v['email_status'] = "是";
			 }else{
				$v['email_status'] = "否";
			 }
			 $row[$key]=$v;
		 }
		return $row;
	}
	
	public function export(){
		import("ORG.Io.Excel");

		$this->pre = C('DB_PREFIX');
		$map['bi.status']=array('in','4,5,6,7,14');
		$map['m.recommend_type'] = 0;//`mxl:team20141231debug`
		if(!empty($_REQUEST['runame'])){
			$ruid = M("members")->getFieldByUserName(text($_REQUEST['runame']),'id');
			$map['m.recommend_id'] = $ruid;
		}else{
			$map['m.recommend_id'] =array('neq','0');
		}
		if($_REQUEST['uname']){
			$map['m.user_name'] = array("like",urldecode($_REQUEST['uname'])."%");
			$search['uname'] = urldecode($_REQUEST['uname']);	
		}
		if(!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])){
			$timespan = strtotime(urldecode($_REQUEST['start_time'])).",".strtotime(urldecode($_REQUEST['end_time']));
			$map['bi.add_time'] = array("between",$timespan);
			$search['start_time'] = urldecode($_REQUEST['start_time']);	
			$search['end_time'] = urldecode($_REQUEST['end_time']);	
		}elseif(!empty($_REQUEST['start_time'])){
			$xtime = strtotime(urldecode($_REQUEST['start_time']));
			$map['bi.add_time'] = array("gt",$xtime);
			$search['start_time'] = $xtime;	
		}elseif(!empty($_REQUEST['end_time'])){
			$xtime = strtotime(urldecode($_REQUEST['end_time']));
			$map['bi.add_time'] = array("lt",$xtime);
			$search['end_time'] = $xtime;	
		}
		if(session('admin_is_kf')==1 && m.customer_id!=0)	$map['m.customer_id'] = session('admin_id');
		
	$field= ' sum(bi.investor_capital) investor_capital,count(bi.id) total,bi.investor_uid,m.recommend_id,m.id,m.user_name,m.reg_time,mi.real_name,mt.vip_status,mt.phone_status,mt.email_status';
		$list = M('borrow_investor bi')->join("{$this->pre}members m ON m.id = bi.investor_uid")->join("{$this->pre}member_info mi ON mi.uid=m.id")->join("{$this->pre}members_status mt ON mt.uid=m.id")->field($field)->where($map)->group('bi.investor_uid')->limit($Lsql)->select();
		
		
		
		$list=$this->_listFilter($list);
		
		
		$row=array();
		$row[0]=array('序号','推广人','投资人','投资总金额','投资总笔数','投资人真实姓名','投资人注册时间','投资人手机认证','投资人邮箱认证','投资人VIP认证');
		$i=1;
		foreach($list as $v){
				$row[$i]['i'] = $i;
				$row[$i]['recommend_name'] = $v['recommend_name'];
				$row[$i]['user_name'] = $v['user_name'];
				$row[$i]['capital'] = $v['investor_capital'];
				$row[$i]['bishu'] = $v['total'];
				$row[$i]['real_name'] = $v['real_name'];
				$row[$i]['reg_time'] = $v['reg_time'];
				$row[$i]['phone_status'] = $v['phone_status'];
				$row[$i]['email_status'] = $v['email_status'];
				$row[$i]['vip_status'] = $v['vip_status'];
				$i++;
		}
		
		$xls = new Excel_XML('UTF-8', false, 'datalist');
		$xls->addArray($row);
		$xls->generateXML("datalistcard");
	}


	
}
?>
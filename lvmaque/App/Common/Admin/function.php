<?php

//获取借款列表 
function getTMemberList($search=array(),$size=''){
	$pre = C('DB_PREFIX');
	$map['m.is_transfer'] = '1';
	$map = array_merge($map,$search);


	//分页处理
	import("ORG.Util.Page");
	$count = M('members m')->where($map)->count('m.id');
	$p = new Page($count, $size);
	$page = $p->show();
	$Lsql = "{$p->firstRow},{$p->listRows}";
	//分页处理

	$field = "m.id,m.user_name,mf.info";
	$list = M('members m')->field($field)->join("{$pre}member_info mf ON m.id=mf.uid")->where($map)->limit($Lsql)->select();
	foreach($list as $key=>$v){
		$total = M('borrow_info bi')
            ->field("sum(borrow_money) as tb,sum(has_borrow*per_transfer) as total")
            ->join("{$pre}borrow_transfer_detail btd ON btd.borrow_id = bi.id")
            ->where("borrow_uid={$v['id']}")
            ->find();
		$list[$key]['transfer_total'] = $total['tb'];
		$list[$key]['transfer_total_out'] = $total['total'];
	}
	
	$row=array();
	$row['list'] = $list;
	$row['page'] = $page;
	return $row;
}

//获取借款列表  更新时间：2014-10-11
function getMemberInfoList($search=array(),$size=''){
	$pre = C('DB_PREFIX');
	$map = array();
	$map = array_merge($map,$search);


	//分页处理
	import("ORG.Util.Page");
	$count = M('members m')->where($map)->count('m.id');
	$p = new Page($count, $size);
	$page = $p->show();
	$Lsql = "{$p->firstRow},{$p->listRows}";
	//分页处理

	  $field = "m.id,m.id as uid,m.user_name,
	  mbank.uid as mbank_id,mbank.bank_num as mbank_num,
	  mi.uid as mi_id,mi.sex as mi_sex,
	  mci.uid as mci_id,mci.address as mci_address,
	  mhi.uid as mhi_id,mhi.house_dizhi as mhi_dizhi,
	  mdpi.uid as mdpi_id,mdpi.department_name as mdpi_name,
	  mei.uid as mei_id,mei.ensuer1_name as mei_name,
	  mfi.uid as mfi_id,mfi.fin_monthin as mfi_monthin";
	
	$list = M('members m')->field($field)
	->join("{$pre}member_banks mbank ON m.id=mbank.uid")
	->join("{$pre}member_contact_info mci ON m.id=mci.uid")
	->join("{$pre}member_department_info mdpi ON m.id=mdpi.uid")
	->join("{$pre}member_house_info mhi ON m.id=mhi.uid")
	->join("{$pre}member_ensure_info mei ON m.id=mei.uid")
	->join("{$pre}member_info mi ON m.id=mi.uid")
	->join("{$pre}member_financial_info mfi ON m.id=mfi.uid")
	->where($map)->limit($Lsql)->order('m.id DESC')->select();
	

	foreach($list as $key=>$v){
		$is_data = M('member_data_info')->where("uid={$v['uid']}")->count("id");
		$list[$key]['mbank'] = (($v['mbank_id']>0)&&($v['mbank_num']!=''))?"<span style='color:green'>已填写</span>":"<span style='color:black'>未填写</span>";
		$list[$key]['mci'] = (($v['mci_id']>0)&&($v['mci_address']!=''))?"<span style='color:green'>已填写</span>":"<span style='color:black'>未填写</span>";
		$list[$key]['mdi'] = ($is_data>0)?"<span style='color:green'>已填写(<a href='".U('/admin/memberdata/index')."?uid={$v['uid']}'>查看</a>)</span>":"<span style='color:black'>未填写</span>";
		$list[$key]['mhi'] = (($v['mhi_id']>0)&&($v['mhi_dizhi']!=''))?"<span style='color:green'>已填写</span>":"<span style='color:black'>未填写</span>";
		$list[$key]['mdpi'] = (($v['mdpi_id']>0)&&($v['mdpi_name']!=''))?"<span style='color:green'>已填写</span>":"<span style='color:black'>未填写</span>";
		$list[$key]['mei'] = (($v['mei_id']>0)&&($v['mei_name']!=''))?"<span style='color:green'>已填写</span>":"<span style='color:black'>未填写</span>";
		$list[$key]['mfi'] = (($v['mfi_id']>0)&&($v['mfi_monthin']!=''))?"<span style='color:green'>已填写</span>":"<span style='color:black'>未填写</span>";
		$list[$key]['mi'] = (($v['mi_id']>0)&&($v['mi_sex']!=''))?"<span style='color:green'>已填写</span>":"<span style='color:black'>未填写</span>";
	}
	

	$row=array();
	$row['list'] = $list;
	$row['page'] = $page;
	return $row;
}


//获取借款列表
function getMemberApplyList($search=array(),$size=''){
	$pre = C('DB_PREFIX');
	$map['ap.apply_status'] = '0';
	$map = array_merge($map,$search);
	//$map = $search;


	//分页处理
	import("ORG.Util.Page");
	$count = M('member_apply ap')->where($map)->count('ap.id');
	$p = new Page($count, $size);
	$page = $p->show();
	$Lsql = "{$p->firstRow},{$p->listRows}";
	//分页处理

	$field = "ap.id,ap.apply_type,m.id as uid,m.user_name,mbank.uid as mbank_id,mi.uid as mi_id,mci.uid as mci_id,mdpi.department_name as mdpi_id,mfi.uid as mfi_id,ap.add_time";
	$list = M('member_apply ap')
	       ->field($field)
	       ->join("{$pre}members m ON m.id=ap.uid")
	       ->join("{$pre}member_banks mbank ON m.id=mbank.uid")
	       ->join("{$pre}member_contact_info mci ON m.id=mci.uid")
	       ->join("{$pre}member_department_info mdpi ON m.id=mdpi.uid")
	       ->join("{$pre}member_info mi ON m.id=mi.uid")
	       ->join("{$pre}member_financial_info mfi ON m.id=mfi.uid")
	       ->where($map)
	       ->limit($Lsql)
	       ->group("ap.id")
	       ->order('ap.id DESC')
	       ->select();
	foreach($list as $key=>$v){
		$is_data = M('member_data_info')->where("uid={$v['uid']}")->count("id");
		$list[$key]['mbank'] = ($v['mbank_id']>0)?"<span style='color:green'>已填写</span>":"<span style='color:black'>未填写</span>";
		$list[$key]['mci'] = ($v['mci_id']>0)?"<span style='color:green'>已填写</span>":"<span style='color:black'>未填写</span>";
		$list[$key]['mdi'] = ($is_data>0)?"<span style='color:green'>已填写(<a href='".U('/admin/memberdata/index')."?uid={$v['uid']}'>查看</a>)</span>":"<span style='color:black'>未填写</span>";
		$list[$key]['mdpi'] = empty($v['mdpi_id'])?"<span style='color:black'>未填写</span>":"<span style='color:green'>已填写</span>";
		$list[$key]['mfi'] = ($v['mfi_id']>0)?"<span style='color:green'>已填写</span>":"<span style='color:black'>未填写</span>";
		$list[$key]['mi'] = ($v['mi_id']>0)?"<span style='color:green'>已填写</span>":"<span style='color:black'>未填写</span>";
	}
	
	$row=array();
	$row['list'] = $list;
	$row['page'] = $page;
	return $row;
}

/**
 * 获取个人借款列表
 * 20150326 liu.
 */

function getMemberInfoDetail($uid){
	$pre = C('DB_PREFIX');
	$map['m.id'] = $uid;
	$field = "*";
	$list = M('members m')
	       ->field($field)
	       ->join("{$pre}member_contact_info mci ON m.id=mci.uid")
	       ->join("{$pre}member_department_info mdpi ON m.id=mdpi.uid")
	       ->join("{$pre}member_info mi ON m.id=mi.uid")
	       ->join("{$pre}member_financial_info mfi ON m.id=mfi.uid")
	       ->where($map)->find();
	return $list;
}
/**
 * 获取企业借款列表
 */
function getBusinessDetail($uid){
    $pre = C('DB_PREFIX');
    $map['m.id'] = $uid;
    $field = "*";
    $list = M('members m')
            ->field($field)
            ->join("{$pre}business_detail bd ON m.id=bd.uid")
            ->join("{$pre}member_info mi ON m.id=mi.uid")
            ->where($map)->find();
    return $list;
}
//在线客服
function get_qq($type){
    $list = M('qq')->where("type = $type and is_show = 1")->order("qq_order DESC")->select();
	return $list;
}

if(get_magic_quotes_gpc()){
    function stripslashes_deep($value)
    {
        $value = is_array($value)? array_map('stripslashes_deep', $value): stripslashes($value); 
        return $value;
    }
    $_POST = array_map('stripslashes_deep', $_POST);
    $_GET = array_map('stripslashes_deep', $_GET);
    $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
}

//`mxl:team20150116`
function getGroupID($typeName, $typeRight = array(), $force = 0){
	$data['groupname'] = $typeName;
	$data['controller'] = serialize($typeRight);
	$res = M("acl")->where("groupname = '{$typeName}'")->getField("group_id");
	if (empty($res) === true){
		$res = M("acl")->add($data);
		if (empty($res) === false){ $res = M("acl")->where("groupname = '{$typeName}'")->getField("group_id"); }
	}
	else if ($force > 0){
		$rt = M("acl")->where("groupname = '{$typeName}'")->getField("controller");
		$data['group_id'] = $res;
		if ($data['controller'] !== $rt){ M("acl")->save($data); }
	}
	return $res;
}
function chkGroup($aid, &$ainfo, $force = 0){
	$right_leader = getGroupID("团队长", C("RIGHT_TEAM_LEADER"), $force);
	$right_member = getGroupID("经纪人", C("RIGHT_TEAM_MEMBER"), $force);
	$ainfo = M("ausers")->where("id = {$aid}")->field("u_group_id, parent")->find();
	if (empty($ainfo) === true){ return false; }
	$ainfo['id'] = $aid;
	$ainfo['me'] = "other";
	$ainfo['r_leader'] = $right_leader;
	$ainfo['r_member'] = $right_member;
	if ($ainfo['u_group_id'] == $right_member){
		$ainfo['me'] = "member";
	}
	if ($ainfo['u_group_id'] == $right_leader){
		$ainfo['parent'] = $aid;
		$ainfo['me'] = "leader";
	}
	return true;
}
//`mxl:team20150116`
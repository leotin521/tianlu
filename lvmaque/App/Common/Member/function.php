<?php
function getFriendList($map,$size,$xuid=0){
	//if(empty($map['f.uid'])) return;
	$pre = C('DB_PREFIX');
	
	//分页处理
	import("ORG.Util.Page");
	$count = M('member_friend f')->where($map)->count('f.id');
	$p = new Page($count, $size);
	$page = $p->show();
	$Lsql = "{$p->firstRow},{$p->listRows}";
	//分页处理

	$list = M('member_friend f')->field("f.uid,f.friend_id,f.add_time,m.user_name,m.credits,fm.user_name as funame,fm.credits as fcredits")->join("{$pre}members m ON f.uid = m.id")->join("{$pre}members fm ON f.friend_id = fm.id")->where($map)->limit($Lsql)->select();
	foreach($list as $key=>$v){
		if($map['f.apply_status']==0){
			$list[$key]['user_name'] = $v['user_name'];
			$list[$key]['credits'] = $v['credits'];
		}else{
			$list[$key]['user_name'] = $v['funame'];
			$list[$key]['credits'] = $v['fcredits'];
		}
	}
	
	$row=array();
	$row['list'] = $list;
	$row['page'] = $page;
	return $row;
}
//获取商品,包括分页数据
function getMsgList($parm=array()){
	$M = new Model('member_msg');
	$pre = C('DB_PREFIX');
	$field=true;
	$orderby = " id DESC";
	
	
	if($parm['pagesize']){
		//分页处理
		import("ORG.Util.Page");
		$count = $M->where($parm['map'])->count('id');
		$p = new Page($count, $parm['pagesize']);
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
	}else{
		$page="";
		$Lsql="{$parm['limit']}";
	}

	$data = M('member_msg')->field(true)->where($parm['map'])->order($orderby)->limit($Lsql)->select();
		
	$symbol = C('MONEY_SYMBOL');
	$suffix=C("URL_HTML_SUFFIX");
	foreach($data as $key=>$v){}
	
	$row=array();
	$row['list'] = $data;
	$row['page'] = $page;
	$row['count'] = $count;
	return $row;

}

function getWithDrawLog($map,$size,$limit=10){
	if(empty($map['uid'])) return;
	
	if($size){
		//分页处理
		import("ORG.Util.Page");
		$count = M('member_withdraw')->where($map)->count('id');
		$p = new Page($count, $size);
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
	}else{
		$page="";
		$Lsql="{$parm['limit']}";
	}
	$info = get_bconf_setting();
	$integration = $info['BANK_NAME'];
	$status_arr =array('待审核','审核通过,处理中','已提现','审核未通过');
	$list = M('member_withdraw')->where($map)->order('id DESC')->limit($Lsql)->select();
	foreach($list as $key=>$v){
		$bank = M('member_banks')->field("bank_name,bank_num")->where("id={$v['bank_id']}")->find();
		$list[$key]['status'] = $status_arr[$v['withdraw_status']];
		$list[$key]['bank_name'] = $integration[$bank['bank_name']];
		$list[$key]['bank_num'] = $bank['bank_num'];
 	}
	
	$row=array();
	$row['list'] = $list;
	$row['page'] = $page;
	$map['status'] = 1;
	$row['success_money'] = M('member_payonline')->where($map)->sum('money');
	$map['status'] = array('neq','1');
	$row['fail_money'] = M('member_payonline')->where($map)->sum('money');
	return $row;
}

function getChargeLog($map,$size,$limit=10){
	if(empty($map['uid'])) return;
	
	if($size){
		//分页处理
		import("ORG.Util.Page");
		$count = M('member_payonline')->where($map)->count('id');
		$p = new Page($count, $size);
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
	}else{
		$page="";
		$Lsql="{$parm['limit']}";
	}
	
	$status_arr =array('充值未完成','充值成功','签名不符','充值失败');
	$list = M('member_payonline')->where($map)->order('id DESC')->limit($Lsql)->select();
	foreach($list as $key=>$v){
		$list[$key]['status'] = $status_arr[$v['status']];
	}
	
	$row=array();
	$row['list'] = $list;
	$row['page'] = $page;
	$map['status'] = 1;
	$row['success_money'] = M('member_payonline')->where($map)->sum('money');
	$map['status'] = array('neq','1');
	$row['fail_money'] = M('member_payonline')->where($map)->sum('money');
	return $row;
}
//借款逾期但还未还的借款列表(逾期)
function getMBreakRepaymentList($uid=0,$size=10,$Wsql=""){
	if(empty($uid)) return;
	$pre = C('DB_PREFIX');
	
	if($size){
		//分页处理
		import("ORG.Util.Page");
		$count = M()->query("select d.id as count from {$pre}investor_detail d where d.borrow_id in(select tb.id from {$pre}borrow_info tb where tb.borrow_uid={$uid}) AND tb.borrow_status in(6,9) AND d.deadline<".time()." AND d.repayment_time=0 {$Wsql} group by d.sort_order,d.borrow_id");
		$count = count($count);
		$p = new Page($count, $size);
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
	}else{
		$page="";
		$Lsql="{$parm['limit']}";
	}
	
	$field = "b.borrow_name,d.status,d.total,d.borrow_id,d.sort_order,sum(d.capital) as capital,sum(d.interest) as interest,d.deadline";
	$sql = "select {$field} from {$pre}investor_detail d left join {$pre}borrow_info b ON b.id=d.borrow_id where d.borrow_uid ={$uid} AND b.borrow_status in(6,9) AND d.deadline<".time()." AND d.repayment_time=0 {$Wsql} group by d.sort_order,d.borrow_id order by  d.borrow_id,d.sort_order limit {$Lsql}";

	$list = M()->query($sql);
	$status_arr =array('还未还','已还完','已提前还款','逾期还款','网站代还本金');
	$glodata = get_global_setting();
	$expired = explode("|",$glodata['fee_expired']);
	$call_fee = explode("|",$glodata['fee_call']);
	foreach($list as $key=>$v){
		$list[$key]['status'] = $status_arr[$v['status']];
		$list[$key]['breakday'] = getExpiredDays($v['deadline']);
		
		if($list[$key]['breakday']>$expired[0]){
			$list[$key]['expired_money'] = getExpiredMoney($list[$key]['breakday'],$v['capital'],$v['interest']);
		}
		
		if($list[$key]['breakday']>$call_fee[0]){
			$list[$key]['call_fee'] = getExpiredCallFee($list[$key]['breakday'],$v['capital'],$v['interest']);
		}
		
		$list[$key]['allneed'] = $list[$key]['call_fee'] + $list[$key]['expired_money'] + $v['capital'] + $v['interest'];
	}
	$row=array();
	$row['list'] = $list;
	$row['page'] = $page;
	$row['count'] = $count;
	return $row;
}



//集合起每笔借款的每期的还款状态(逾期)
function getMBreakInvestList($map,$size=10){
	$pre = C('DB_PREFIX');
	
	if($size){
		//分页处理
		import("ORG.Util.Page");
		$count = M('investor_detail d')->where($map)->count('d.id');
		$p = new Page($count, $size);
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
	}else{
		$page="";
		$Lsql="{$parm['limit']}";
	}
	
	$field = "m.user_name as borrow_user,b.borrow_interest_rate,d.borrow_id,b.borrow_name,d.status,d.total,d.borrow_id,d.sort_order,d.interest,d.capital,d.deadline,d.sort_order";
	$list =M('investor_detail d')->field($field)->join("{$pre}borrow_info b ON b.id=d.borrow_id")->join("{$pre}members m ON m.id=b.borrow_uid")->where($map)->limit($Lsql)->select();

	$status_arr =array('还未还','已还完','已提前还款','逾期还款','网站代还本金');
	$glodata = get_global_setting();
	$expired = explode("|",$glodata['fee_expired']);
	$call_fee = explode("|",$glodata['fee_call']);
	foreach($list as $key=>$v){
		$list[$key]['status'] = $status_arr[$v['status']];
		$list[$key]['breakday'] = getExpiredDays($v['deadline']);
	}
	$row=array();
	$row['list'] = $list;
	$row['page'] = $page;
	$row['count'] = $count;
	return $row;
}

function getBorrowList($map,$size,$limit=10){
	if(empty($map['borrow_uid'])) return;
	
	$Model = D("BorrowView");
	if($size){
		//分页处理
		import("ORG.Util.Page");
		$count = $Model->where($map)->count('DISTINCT borrow.id');
		$p = new Page($count, $size);
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
	}else{
		$page="";
		$Lsql="{$parm['limit']}";
	}
	
	$Bconfig = require C("APP_ROOT")."Conf/borrow_config.php";
	$status_arr =$Bconfig['BORROW_STATUS_SHOW'];
	//$list = M('borrow_info')->where($map)->order('id DESC')->limit($Lsql)->select();
	/////////////使用了视图查询操作 fans 2013-05-22/////////////////////////////////
    
	$list=$Model->field(true)->where($map)->order('times ASC')->group('id')->limit($Lsql)->select();

	/////////////使用了视图查询操作 fans 2013-05-22/////////////////////////////////
	foreach($list as $key=>$v){
		$list[$key]['status'] = $status_arr[$v['borrow_status']];
		$list[$key]['repayment_type_num'] = $v['repayment_type'];
		$list[$key]['repayment_type'] = BorrowModel::get_borrow_type($v['repayment_type']);
		$list[$key]['progress'] = getFloatValue($v['has_borrow']/$v['borrow_money']*100,2);
		if($map['borrow_status']==6){
			$vx = M('investor_detail')->field('deadline')->where("borrow_id={$v['id']} and status=7")->order("deadline ASC")->find();
			$list[$key]['repayment_time'] = $vx['deadline'];
		}
		if($map['borrow_status']==5 || $map['borrow_status']==1){
			$vd = M('borrow_verify')->field(true)->where("borrow_id={$v['id']}")->find();
			$list[$key]['dealinfo'] = $vd;
		}
	}
	
	$row=array();
	$row['list'] = $list;
	$row['page'] = $page;
	//$map['status'] = 1;
	//$row['success_money'] = M('member_payonline')->where($map)->sum('money');
	//$map['status'] = array('neq','1');
	//$row['fail_money'] = M('member_payonline')->where($map)->sum('money');
	return $row;
}


function getTenderList($map,$size,$limit=10){
	$pre = C('DB_PREFIX');
	$Bconfig = require C("APP_ROOT")."Conf/borrow_config.php";
	//if(empty($map['i.investor_uid'])) return;
	if(empty($map['investor_uid'])) return;
	if($size){
		//分页处理
		import("ORG.Util.Page");
		$count = M('borrow_investor i')->where($map)->count('i.id');
		$p = new Page($count, $size);
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
	}else{
		$page="";
		$Lsql="{$parm['limit']}";
	}
	
	$type_arr =$Bconfig['BORROW_TYPE'];
	/////////////////////////视图查询 fan 20130522//////////////////////////////////////////
	$Model = D("TenderListView");
	$list=$Model->field(true)->where($map)->order('times ASC')->group('id')->limit($Lsql)->select();
	////////////////////////视图查询 fan 20130522//////////////////////////////////////////
	foreach($list as $key=>$v){
		//if($map['i.status']==4){
		if($map['status']==4){
			$list[$key]['total'] = ($v['borrow_type']==3)?"1":$v['borrow_duration'];
			$list[$key]['back'] = $v['has_pay'];
			$vx = M('investor_detail')->field('deadline')->where("borrow_id={$v['borrowid']} and status=7")->order("deadline ASC")->find();
			$list[$key]['repayment_time'] = $vx['deadline'];
		}
        if($v['debt_time']){
            $list[$key]['borrow_interest_rate'] = $v['debt_interest_rate'];
        }
	}

	$row=array();
	$row['list'] = $list;
	$row['page'] = $page;
	$row['total_money'] = M('borrow_investor i')->where($map)->sum('investor_capital');
	$row['total_num'] = $count;
	return $row;
}


function getBackingList($map,$size,$limit=10){
	$pre = C('DB_PREFIX');
	if(empty($map['d.investor_uid'])) return;
	
	if($size){
		//分页处理
		import("ORG.Util.Page");
		$count = M('investor_detail d')->where($map)->count('d.id');
		$p = new Page($count, $size);
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
	}else{
		$page="";
		$Lsql="{$parm['limit']}";
	}
	
	$type_arr =C('BORROW_TYPE');
	$field = true;
	$list = M('investor_detail d')->field($field)->where($map)->order('d.id DESC')->limit($Lsql)->select();
	foreach($list as $key=>$v){
		//$list[$key]['status'] = $status_arr[$v['status']];
	}
	
	$row=array();
	$row['list'] = $list;
	$row['page'] = $page;
	$sx = M('investor_detail d')->field("sum(d.capital + d.interest) as tox")->where("d.status=1 AND d.investor_uid={$map['d.investor_uid']}")->find();
	$sxcount = M('borrow_investor')->where("status=4 AND investor_uid={$map['d.investor_uid']}")->count("id");
	$month = M('investor_detail d')->field("sum(d.capital + d.interest) as tox")->where($map)->find();
	$row['month_total'] = $month['tox'];
	$row['total_money'] = $sx['tox'];
	$row['total_num'] = $sxcount;
	return $row;
}


//在线客服
function get_qq($type){
    $list = M('qq')->where("type = $type and is_show = 1")->order("qq_order DESC")->select();
	return $list;
}

//获取借款列表
function getMemberDetail($uid){
	$pre = C('DB_PREFIX');
	$map['m.id'] = $uid;
	//$field = "*";
	$list = M('members m')->field(true)->join("{$pre}member_banks mbank ON m.id=mbank.uid")->join("{$pre}member_contact_info mci ON m.id=mci.uid")->join("{$pre}member_house_info mhi ON m.id=mhi.uid")->join("{$pre}member_department_info mdpi ON m.id=mdpi.uid")->join("{$pre}member_ensure_info mei ON m.id=mei.uid")->join("{$pre}member_info mi ON m.id=mi.uid")->join("{$pre}member_financial_info mfi ON m.id=mfi.uid")->where($map)->limit($Lsql)->find();
	return $list;
}
/**
 * 投资记录
 * TABLE：borrow_investor, debt
 * @param array() $map
 * @param number $size
 * @param number $limit
 * @return array();
 */
function getTTenderList($map,$size,$limit = 10)
{
	$pre = C("DB_PREFIX");
	if(empty($map['i.investor_uid']))
	{
		return;
	}
	if($size)
	{
		import( "ORG.Util.Page" );
		$count = M("borrow_investor i")->join("{$pre}borrow_info b ON b.id=i.borrow_id")->where($map)->count("i.id");

		$p = new Page($count,$size);
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
	}else{
		$page = "";
		$Lsql = "{$parm['limit']}";
	}
	
	$field = "i.*,i.id as i_id,i.status as bi_status,i.add_time as invest_time,m.user_name as borrow_user,b.borrow_duration,b.borrow_interest_rate,b.add_time as borrow_time,b.borrow_money,b.borrow_status,b.borrow_name,m.credits,b.danbao,d.*,d.status as de_status";

	$list = M("borrow_investor i")
	       ->field($field)
	       ->join("{$pre}borrow_info b ON b.id=i.borrow_id")
	       ->join( "{$pre}members m ON m.id=b.borrow_uid")
	       ->join( "{$pre}debt d ON i.id=d.invest_id")
	       ->where($map)
	       ->order("i.id DESC")->limit($Lsql)->select();
	foreach($list as $key => $v )
	{
	    //担保机构
    	if($v['danbao']!=0 ){
    	    $list[$key]['danbaoid'] = intval($v['danbao']);
    	    $danbao = M('article')->field('id,title')->where("type_id=7 and id={$v['danbao']}")->find();
    	    $list[$key]['danbao']=$danbao['title'];
    	}else{
    	    $list[$key]['danbao']='暂无担保机构';
    	}
		//如果有债权转让，则显示转让后的利息
		if( $v['debt_interest_rate'] > 0 ) {
			$list[$key]['borrow_interest_rate']=$v['debt_interest_rate'];
		}
    	//产品类型
    	$list[$key]['borrow_type'] = BorrowModel::get_borrow_type($v['borrow_type']);
        $list[$key]['btype'] = $v['borrow_type'];
    	//投资方式
    	if ($v['parent_invest_id']==0){
    	    $list[$key]['invest_way'] = "直接投资";
    	}else{
    	    $list[$key]['invest_way'] = "认购债权";
    	}

		if( !isset($map['d.status']) ) {
			if( $v['de_status'] == 4 ) {
				unset($list[$key]);
			}
		}
    	
	}
	$row = array();
	$row['list'] = $list;
	$row['page'] = $page;
	return $row;
}
/**
 * 还款详情
 * @param array $map
 * @param number $size
 * @param number $limit
 * @return void|multitype:NULL unknown Ambigous <string, unknown>
 */
function getTDTenderList($map, $size, $limit = 10)
{
	$pre = C("DB_PREFIX");
	$Bconfig = require(C("APP_ROOT")."Conf/borrow_config.php");
	if(empty($map['d.investor_uid']))
	{
		return;
	}
	if($size)
	{
		import("ORG.Util.Page");
		$count = M("investor_detail d")->where($map)->count("d.id");
		$p = new Page($count, $size);
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
	}else{
		$page = "";
		$Lsql = "{$parm['limit']}";
	}
	$field = "d.*,m.user_name as borrow_user,b.borrow_name,m.credits,i.add_time";
	$list = M("investor_detail d")
	       ->field($field)->where($map)
	       ->join("{$pre}borrow_info b ON b.id=d.borrow_id")
	       ->join( "{$pre}borrow_investor i ON i.id=d.invest_id")
	       ->join("{$pre}members m ON m.id=b.borrow_uid")
	       ->order("d.deadline ASC")
	       ->limit($Lsql)
	       ->select();
	foreach($list as $key => $v )
	{
	    //担保机构
	    if($v['danbao']!=0 ){
	        $list[$key]['danbaoid'] = intval($v['danbao']);
	        $danbao = M('article')->field('id,title')->where("type_id=7 and id={$v['danbao']}")->find();
	        $list[$key]['danbao']=$danbao['title'];
	    }else{
	        $list[$key]['danbao']='暂无担保机构';
	    }
	    //产品类型
	    $list[$key]['borrow_type'] = BorrowModel::get_borrow_type($v['borrow_type']);
	    //投资方式
	    if ($v['parent_invest_id']==0){
	        $list[$key]['invest_way'] = "直接投资";
	    }else{
	        $list[$key]['invest_way'] = "认购债权";
	    }
	     
	}
	$row = array();
	$row['list'] = $list;
	$row['page'] = $page;
	$row['total_money'] = M("investor_detail d")->where($map)->sum("`capital`+`interest`-`interest_fee`");
	//未支付本息
	$map['d.status'] = 7;
	$row['fail_pay'] = M("investor_detail d")->where($map)->sum("`capital`+`interest`-`interest_fee`");
	//已支付本息
	$map['d.status'] = array('not in','7,14'); //不包括债权转让
	$row['have_pay'] = M("investor_detail d")->where($map)->sum("`capital`+`interest`-`interest_fee`");
	$row['total_num'] = $count;
	return $row;
}


//////////////////////////////企业直投 管理模块结束  /////////////////////////////

/**
 * 安全设置
 */
function safeset($uid){
	$pre = C('DB_PREFIX');
	$vo = M("members m")
		->field("m.id,m.pin_pass,m.user_email,m.user_phone,s.id_status,s.phone_status,s.email_status,s.safequestion_status,sa.question1,sa.question2,mi.*")
		->join("{$pre}members_status s ON s.uid=m.id")
		->join("{$pre}member_safequestion sa ON sa.uid=m.id")
		->join("{$pre}member_info mi ON mi.uid=m.id")
		->where("m.id={$uid}")
		->find();
	if(empty($vo['id_status'])) $vo['id_status']=0;
	if(empty($vo['phone_status'])) $vo['phone_status']=0;
	if(empty($vo['email_status'])) $vo['email_status']=0;
	if(!empty($vo['user_phone'])) $vo['user_phone'] = hidecard($vo['user_phone'], 2);
	if(empty($vo['safequestion_status'])) $vo['safequestion_status']=0;
	if(empty($vo['pin_pass'])) {
	    $vo['pin_pass']=0;
	}
	else $vo['pin_pass']=1;
	return $vo;
}
/**
 * 手机&邮箱状态监测 # liu.
 * @property integer $uid
 */
function getMemberstatus($uid){
    if(empty($uid)) return;
    $pre = C('DB_PREFIX');
    $map['m.id'] = $uid;
    $field = "ms.phone_status,ms.email_status,m.user_email,m.user_phone,ms.safequestion_status,mf.question1,mf.answer1,mf.question2,mf.answer2";
    $list = M('members m')->field($field)->join("{$pre}members_status ms ON m.id=ms.uid")->join("{$pre}member_safequestion mf ON m.id=mf.uid")->where($map)->find();
    $row=array();
    if ($list['phone_status']==1){
        $row['phone_status'] = $list['phone_status'];
        $row['user_phone'] = $list['user_phone'];
    }
    if ($list['email_status']==1){
        $row['email_status'] = $list['email_status'];
        $row['user_email'] = $list['user_email'];
    }
    if ($list['safequestion_status']==1){
        $row['question1'] = $list['question1'];
        $row['answer1'] = $list['answer1'];
        $row['question2'] = $list['question2'];
        $row['answer2'] = $list['answer2'];
        $row['safequestion_status'] = $list['safequestion_status'];
    }
    return $row;
}
/**
 * 默认设置站内信/信息/邮件
 * @param NumberFormatter $map
 */
function noTify($uid,$type){
    $array=array('chk1_','chk2_','chk6_','chk8_','chk7_','chk10_','chk11_','chk9_','chk12_','chk14_','chk15_','chk16_','chk18_','chk25_','chk27_');
    $str = '';
    foreach ($array as $val){
        $str.=$val.$type.",";
    }
    $Sys = M('sys_tip');
    $data['uid'] = $uid;
    $data['tipset'] = $str;
    $Sys->add($data);
    return true;
    /* 不要删除
    $info = $Sys->field("tipset")->where("uid = '$uid'")->find();
    if (is_array($info)){
        $strA =  (string) $info['tipset'];
        $updata['tipset'] = $strA.$str;
        $Sys->where("uid = '$uid'")->save($updata);
    }else{
        $data['uid'] = $uid;
        $data['tipset'] = $str;
        $Sys->add($data);
    }
    return true;
    */
}
//获取完整列表数据，专用于折线图 //`mxl 20150309`
function getFullData($arr, $key, &$data){
	$ii = 0;
	$data = array();
	$start = intval($arr[0]['time']);
	if (1 > $start){ return false; }
	$end = intval(date("Y") * 12 + date("n"));//截止到当前月份
	if (intval($arr[count($arr) - 1]['time']) > $end){ $end = intval($arr[count($arr) - 1]['time']); }
	for ($i = $start; $end >= $i; $i++){
		$time = strtotime((floor($i / 12))."/".(intval($i % 12))."/10") * 1000;
		if (intval($arr[$ii]['time']) === intval($i)){
			$data[] = array($time, floatval($arr[$ii][$key]));
			$ii++;
		}
		else{
			$data[] = array($time, 0);
		}
	}
	return true;
}

//检测借款标是否能够撤销 /* mxl 20150415 */
function canErase($v){
	return (intval($v['has_borrow']) !== 0 || (getBorrowType($v['borrow_type']) === "man" && intval($v['borrow_status']) !== BorrowModel::BID_SINGLE_CONFIG_STATUS_WAIT_VIEW)) ? false : true;
}

//检测或获取借款标类型 /* mxl 20150415 */
function getBorrowType($type, $is_arr = 0){
	$borrow_type['man'] = array(
		BorrowModel::BID_CONFIG_TYPE_NORMAL,
		BorrowModel::BID_CONFIG_TYPE_GUARANTEE,
		BorrowModel::BID_CONFIG_TYPE_SECOND,
		BorrowModel::BID_CONFIG_TYPE_NET_ASSETS,
		BorrowModel::BID_CONFIG_TYPE_MORTGAGE
	);
	$borrow_type['biz'] = array(BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID, BorrowModel::BID_CONFIG_TYPE_FINANCIAL);
	foreach ($borrow_type as $k => $v){
		if ($type === $k){ return ($is_arr > 0) ? $borrow_type[$type] : implode(",", $borrow_type[$type]); }
		if (in_array($type, $v) === true && is_numeric($type) === true){ return $k; }
	}
	return false;
}

//检测或获取借款标状态 /* mxl 20150415 */
function getBorrowStatus($status, $is_arr = 0){
	$borrow_status['ing'] = array(BorrowModel::BID_SINGLE_CONFIG_STATUS_WAIT_VIEW, BorrowModel::BID_SINGLE_CONFIG_STATUS_VIEW_PASS, BorrowModel::BID_SINGLE_CONFIG_STATUS_WAIT_REVIEW);
	$borrow_status['pay'] = array(BorrowModel::BID_SINGLE_CONFIG_STATUS_VIEW_PASS, BorrowModel::BID_SINGLE_CONFIG_STATUS_REPAYMENT, BorrowModel::BID_SINGLE_CONFIG_STATUS_PLATFORM_REPAY);
	$borrow_status['late'] = array(BorrowModel::BID_SINGLE_CONFIG_STATUS_VIEW_PASS, BorrowModel::BID_SINGLE_CONFIG_STATUS_REPAYMENT, BorrowModel::BID_SINGLE_CONFIG_STATUS_OVERDUE, BorrowModel::BID_SINGLE_CONFIG_STATUS_PLATFORM_REPAY);
	$borrow_status['fail'] = array(BorrowModel::BID_SINGLE_CONFIG_STATUS_VIEW_FAIL, BorrowModel::BID_SINGLE_CONFIG_STATUS_UNFINISHED, BorrowModel::BID_SINGLE_CONFIG_STATUS_REVIEW_FAIL);
	$borrow_status['done'] = array(BorrowModel::BID_SINGLE_CONFIG_STATUS_SUCCESS, BorrowModel::BID_SINGLE_CONFIG_STATUS_FINISH_REPAY);
	foreach ($borrow_status as $k => $v){
		if ($status === $k){ return ($is_arr > 0) ? $borrow_status[$status] : implode(",", $borrow_status[$status]); }
		if (in_array($status, $v) === true && is_numeric($status) === true){ return $k; }
	}
	return false;
}
?>
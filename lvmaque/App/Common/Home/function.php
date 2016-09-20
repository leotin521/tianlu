<?php
//获取借款列表
function getBorrowList($parm=array()){
	if(empty($parm['map'])) return;
	$map= $parm['map'];
    
	$orderby= $parm['orderby'];
	if($parm['pagesize']){
		//分页处理
		import("ORG.Util.Page");
		$count = M('borrow_info b')->where($map)->count('b.id');
		$p = new Page($count, $parm['pagesize']);
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
	}else{
		$page="";
		$Lsql="{$parm['limit']}";
	}
	$pre = C('DB_PREFIX');
	$suffix=C("URL_HTML_SUFFIX");
	$field = "b.id,b.borrow_name,b.duration_unit,b.borrow_type,b.reward_type,b.borrow_times,b.borrow_status,b.borrow_money,b.borrow_use,b.repayment_type,b.borrow_interest_rate,b.borrow_duration,b.collect_time,b.add_time,b.has_borrow,b.has_vouch,b.reward_type,b.reward_num,b.password,m.user_name,m.id as uid,m.credits,m.customer_name,b.is_tuijian,b.deadline,b.danbao,b.borrow_info,b.risk_control,b.is_xinshou,b.is_taste";
	$list = M('borrow_info b')->field($field)->join("{$pre}members m ON m.id=b.borrow_uid")->where($map)->order($orderby)->limit($Lsql)->select();
	$areaList = getArea();
	foreach($list as $key=>$v){
		$list[$key]['location'] = $areaList[$v['province']].$areaList[$v['city']];
		$list[$key]['biao'] = $v['borrow_times'];
		$list[$key]['need'] = $v['borrow_money'] - $v['has_borrow'];
		$list[$key]['duration_unit'] = BorrowModel::get_unit_format($list[$key]['duration_unit']);
		$list[$key]['leftdays'] = getLeftTime($v['collect_time']);
		$list[$key]['progress'] =  BorrowModel::get_progress_decimal(getFloatValue($v['has_borrow']/$v['borrow_money']*100,2));
		$list[$key]['vouch_progress'] = getFloatValue($v['has_vouch']/$v['borrow_money']*100,2);
		$list[$key]['burl'] = MU("Home/invest","invest",array("id"=>$v['id'],"suffix"=>$suffix));
		//新加
		$list[$key]['lefttime']=$v['collect_time']-time();
				
		if($v['deadline']==0){
			$endTime = strtotime(date("Y-m-d",time()));
			if($v['repayment_type']==1) {
				$list[$key]['repaytime'] = strtotime("+{$v['borrow_duration']} day",$endTime);
			}else {
				$list[$key]['repaytime'] = strtotime("+{$v['borrow_duration']} month",$endTime);
			}
		}else{
			$list[$key]['repaytime'] = $v['deadline'];//还款时间
		}

		$list[$key]['publishtime']=$v['add_time']+60*60*24*3;//预计发标时间=添加时间+1天
		$list[$key]['investornum']= M('borrow_investor')->where("borrow_id={$v['id']} ")->count('id');
		if($v['danbao']!=0 ){
			$danbao = M('article')->field("id,title")->where("type_id =7 and id ={$v['danbao']}")->find();
			$list[$key]['danbao']=$danbao['title'];//担保机构
		}else{
			$list[$key]['danbao']='暂无担保机构';//担保机构
		}
		
	}
	
	$row=array();
	$row['list'] = $list;
	$row['page'] = $page;
	return $row;
}

//获取特定栏目下文章列表
function getArticleList($parm){
	if(empty($parm['type_id'])) return;
	//$map['type_id'] = $parm['type_id'];
   $type_id= intval($parm['type_id']);
   $Allid = M("article_category")->field("id")->where("parent_id = {$type_id}")->select();
   $newlist = array();
   array_push($newlist,$parm['type_id']);
  
   foreach ($Allid as $ka => $v) {
	   array_push($newlist,$v["id"]);
   }
   $map['type_id']= array("in",$newlist);
   
	$Osql="sort_order desc,id DESC";//id DESC,
	$field="id,title,art_set,art_time,art_url,art_img,art_info";
	//查询条件 
	if($parm['pagesize']){
		//分页处理
		import("ORG.Util.Page");
		$count = M('article')->where($map)->count('id');
		$p = new Page($count, $parm['pagesize']);
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
	}else{
		$page="";
		$Lsql="{$parm['limit']}";
	}

	$data = M('article')->field($field)->where($map)->order($Osql)->limit($Lsql)->select();

	$suffix=C("URL_HTML_SUFFIX");
	$typefix = get_type_leve_nid($map['type_id']);
	$typeu = implode("/",$typefix);
	foreach($data as $key=>$v){
		if($v['art_set']==1) $data[$key]['arturl'] = (stripos($v['art_url'],"http://")===false)?"http://".$v['art_url']:$v['art_url'];
		//elseif(count($typefix)==1) $data[$key]['arturl'] = 
		else $data[$key]['arturl'] = MU("Home/{$typeu}","article",array("id"=>$v['id'],"suffix"=>$suffix));
	}
	$row=array();
	$row['list'] = $data;
	$row['page'] = $page;
	
	return $row;
}


function getCommentList($map,$size){
	$Osql="id DESC";
	$field=true;
	//查询条件 
	if($size){
		//分页处理
		import("ORG.Util.Page");
		$count = M('comment')->where($map)->count('id');
		$p = new Page($count, $size);
		$p->parameter .= "type=commentlist&";
		$p->parameter .= "id={$map['tid']}&";
		$page = $p->show_comment();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
	}

	$data = M('comment')->field($field)->where($map)->order($Osql)->limit($Lsql)->select();
	foreach($data as $key=>$v){
	}
	$row=array();
	$row['list'] = $data;
	$row['page'] = $page;
	$row['count'] = $count;
	
	return $row;
}
//排行榜
function getRankList($map,$size)
{
	$field = "investor_uid,sum(investor_capital) as total";
	$list = M("borrow_investor")->field($field)->where($map)->group("investor_uid")->order("total DESC")->limit($size)->select();
	foreach($list as $k=>$v )
	{
		$list[$k]['user_name'] = M("members")->getFieldById($v['investor_uid'],"user_name");
	}
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
//获取企业直投借款列表
function getTBorrowList($parm =array())
{
	if(empty($parm['map'])) return;
	$map = $parm['map'];
	$orderby = $parm['orderby'];
	if($parm['pagesize'])
	{
		import( "ORG.Util.Page" );
		$count = M("borrow_info b")->where($map)->count("b.id");
		$p = new Page($count, $parm['pagesize']);
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
	}else{
		$page = "";
		$Lsql = "{$parm['limit']}";
	}
    $map['borrow_type'] = BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID;
	$pre = C("DB_PREFIX");
	$suffix =C("URL_HTML_SUFFIX");
	$field = "b.id,b.borrow_name,b.borrow_status,b.borrow_money,b.repayment_type,b.has_borrow,b.repayment_money,b.borrow_min,"
        ."b.borrow_interest_rate,b.borrow_duration,b.deadline,b.on_off,m.province,m.city,m.area,m.user_name,m.id as uid,"
        ."m.credits,m.customer_name,b.borrow_type,b.b_img,b.add_time,b.collect_day,b.danbao";
    $list = M("borrow_info b")->field($field)
        ->join("{$pre}members m ON m.id=b.borrow_uid")
    ->where($map)->order($orderby)->limit($Lsql)->select();
	$areaList = getarea();
	foreach($list as $key => $v)
	{
		$list[$key]['location'] = $areaList[$v['province']].$areaList[$v['city']];
		$list[$key]['progress'] = getfloatvalue( $v['has_borrow'] / $v['transfer_total'] * 100, 2);
		$list[$key]['need'] = getfloatvalue(($v['transfer_total'] - $v['has_borrow'])*$v['per_transfer'], 2 );
		$list[$key]['burl'] = MU("Home/invest_transfer", "invest_transfer",array("id" => $v['id'],"suffix" => $suffix));
        $list[$key]['per_transfer'] = $list[$key]['borrow_min'];
        $list[$key]['transfer_total'] = getfloatvalue($list[$key]['borrow_money']/$list[$key]['borrow_min'], 0);

		$temp=floor(("{$v['collect_day']}"*3600*24-time()+"{$v['add_time']}")/3600/24);
		$list[$key]['leftdays'] = "{$temp}".'天以上';
		$list[$key]['now'] = time();
		if($v['danbao']!=0 ){
			$list[$key]['danbaoid'] = intval($v['danbao']);
			$danbao = M('article')->field('id,title')->where("type_id=7 and id={$v['danbao']}")->find();
			$list[$key]['danbao']=$danbao['title'];//担保机构
		}else{
			$list[$key]['danbao']='暂无担保机构';//担保机构
		}	
	}
	$row = array();
	$row['list'] = $list;
	$row['page'] = $page;
	return $row;
}
//在线客服
function get_qq($type){
    $list = M('qq')->where("type = $type and is_show = 1")->order("qq_order DESC")->select();
	return $list;
}

//`mxl 20150223`
function chkInvest($bid, $uid, $money, &$msg, $pin, $pass = null){
	$minfo = getMinfo($uid, "m.pin_pass, m.user_name, mm.account_money, mm.back_money, mm.money_collect");
	if (empty($pin) === true || $minfo['pin_pass'] !== md5($pin)){ $msg = "支付密码不正确，请重新输入"; return 3; }
	$binfo = TborrowModel::get_borrow_info($bid);
	if(empty($binfo['password']) === false){
		if(empty($pass) === true){ $msg = "此标是定向标，必须验证投标密码"; return 0; }
		else if($binfo['password'] <> md5($pass)){ $msg = "投标密码不正确"; return 0; }
	}
	////////////////////////////////////待收金额限制 2013-08-26  fan///////////////////
	if($binfo['money_collect'] > 0 && $minfo['money_collect'] < $binfo['money_collect']){
		$msg = "此标设置有投标待收金额限制，您账户里必须有足够的待收才能投此标";
		return 0;
	}
	////////////////////////////////////待收金额限制 2013-08-26  fan///////////////////
	//投标总数检测
	$capital = M('borrow_investor')->where("borrow_id={$bid} AND investor_uid={$uid}")->sum('investor_capital');
	if(($capital + $money) > $binfo['borrow_max'] && $binfo['borrow_max'] > 0){
		$xtee = $binfo['borrow_max'] - $capital;
		$msg = "您已投标{$capital}元，此投上限为{$binfo['borrow_max']}元，你最多只能再投{$xtee}";
		return 0;
	}
	$need = $binfo['borrow_money'] - $binfo['has_borrow'];
	$caninvest = $need - $binfo['per_transfer'];
	$last_money = ($need * 100 - $money * 100) / 100;
	$amoney = floatval($minfo['account_money'] + $minfo['back_money']);
//	if( $money > $caninvest && $need <> $money){
//		$msg = "尊敬的{$minfo['user_name']}，此标还差{$need}元满标,如果您投标{$money}元，将导致最后一次投标最多只能投{$last_money}元，小于最小投标金额{$binfo['borrow_min']}元，所以您本次可以选择<font color='#FF0000'>满标</font>或者投标金额必须<font color='#FF0000'>小于等于{$caninvest}元</font>";
//		if($caninvest < $binfo['per_transfer']) $msg = "尊敬的{$minfo['user_name']}，此标还差{$need}元满标,如果您投标{$money}元，将导致最后一次投标最多只能投{$last_money}元，小于最小投标金额{$binfo['borrow_min']}元，所以您本次可以选择<font color='#FF0000'>满标</font>即投标金额必须<font color='#FF0000'>等于{$need}元</font>";
//		return 3;
//	}
	if($binfo['per_transfer'] > $money){
		$msg = "尊敬的{$minfo['user_name']}，本标最低投标金额为{$binfo['per_transfer']}元，请重新输入投标金额";
		return 0;
	}
	if($money > $need){
		$msg = "尊敬的{$minfo['user_name']}，此标还差{$need}元满标,您最多只能再投{$need}元";
		return 0;
	}
	if($money > $amoney){
		//$msg = "尊敬的{$minfo['user_name']}，您准备投标{$money}元，但您的账户可用余额为{$amoney}元，您要先去充值吗？";
           $msg = "余额不足，请先充值";
		return 0;
	}else{
	//	$msg = "尊敬的{$minfo['user_name']}，您的账户可用余额为{$amoney}元，您确认投标{$money}元吗？";
		return 1;
	}
}
/**
 * @定投宝投标二次检测
 * @param $m array 用户金额数据
 *  @param $binfo array 标信息
 * @param $tnum int   投资金额或份数
 * @param $binfo array 标信息
 * @param $investType int  投资方式  1按金额  0按份数
 * @param $repayment_type int  还款方式，定投宝
 * @return void
 */
function chkTwoInvest($m,$binfo,$tnum,$investType,$repayment_type){
              //标基本条件判断
                if($binfo['on_off']!=1){
                   return "此标无法投资！";
                }
                if($binfo['online_time'] > time()) {
                     return  "未到上线时间，不能投标！";
                }
                if($binfo['collect_time'] < time()){
                     return "募集期已经结束！";
                }
                if($m['uid'] == $binfo['borrow_uid']){
                    return "不能去投自己的标";
                }
                /*用户状态判断
                $mo = M('members_status')->field("email_status,phone_status")->where("uid={$this->uid}")->find();
                if($mo['email_status']!=1){
                        return  "请先进行邮箱认证",__APP__."/member/verify?id=1#fragment-1";
                }
                if($mo['phone_status']!=1){
                      return  "请先进行手机认证",__APP__."/member/verify?id=1#fragment-2";
                }*/
               
                $amoney = $m['account_money']+$m['back_money'];
                $uname = session("u_user_name");
                if($investType==1){
                    $money = $tnum;
                    $parm="元";
                    $parms="金额";
                }else{
                     $money = $binfo['per_transfer'] * $tnum;
                     $parm="份";
                     $parms="份数";
                }
                if($tnum <$binfo['per_transfer'] ) {
                        return  "本标最少要投{$binfo['per_transfer']}{$parm}，请重新输入认购{$parms}!" ;
                }
                if($amoney < $money){
                        return  "尊敬的{$uname}，您准备认购{$money}元，但您的账户可用余额为{$amoney}元，请先去充值再认购,".__APP__."/member/charge#fragment-1";
                }
                $vm = getMinfo($m['uid'] ,"m.pin_pass,mm.invest_vouch_cuse,mm.money_collect");
                $pin_pass = $vm['pin_pass'];
                $pin = md5($_POST['T_pin']);
                if ($pin != $pin_pass){
                        return "支付密码错误，请重试" ;
                }
                /*
                 //最大份和最大金额限制
                $max_num = $binfo['transfer_total'] - $binfo['transfer_out'];
                 $min_month = $binfo['min_month'];
                if($binfo['borrow_max'] > 0){
                    if($binfo['borrow_max'] < $tnum){
                        return "单人最大购买份数为".$binfo['borrow_max']."份，请重新输入认购份数";
                    }
                }
                if($binfo['transfer_out'] > 0 && $binfo['borrow_max'] > 0){
                    $havebuy = M("borrow_investor")->where("investor_uid={$this->uid} and borrow_id={$borrow_id}")->sum("transfer_num");
                    if($binfo['borrow_max'] < $tnum + $havebuy){
                        return "单人最大购买份数为".$binfo['borrow_max']."份，请重新输入认购份数!";
                    }
                }
                if($max_num < $tnum){
                        return "本标还能认购最大份数为".$max_num."份，请重新输入认购份数" ;
                }*/
                return TRUE;
               
}
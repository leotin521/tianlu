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
    $field = "b.id,b.borrow_name,b.duration_unit,b.borrow_type,b.reward_type,b.borrow_times,b.borrow_status,b.borrow_money,b.borrow_use,b.repayment_type,b.borrow_interest_rate,b.borrow_duration,b.collect_time,b.add_time,b.has_borrow,b.has_vouch,b.reward_type,b.reward_num,b.password,m.user_name,m.id as uid,m.credits,m.customer_name,b.is_tuijian,b.deadline,b.danbao,b.borrow_info,b.risk_control";
    $list = M('borrow_info b')->field($field)->join("{$pre}members m ON m.id=b.borrow_uid")->where($map)->order($orderby)->limit($Lsql)->select();
    //echo M()->getLastSql();
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
    echo M()->getLastSql();
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
 * @点金宝投标二次检测
 * @param $m array 用户金额数据
 *  @param $binfo array 标信息
 * @param $tnum int   投资金额或份数
 * @param $binfo array 标信息
 * @param $investType int  投资方式  1按金额  0按份数
 * @param $repayment_type int  还款方式，点金宝
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
    $pin = md5($_POST['pin_pass']);
    if ($pin != $pin_pass){
        return "支付密码错误" ;
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

	function getTTenderList($map,$size,$limit = 10)
{
	$pre = C("DB_PREFIX");
	$Bconfig = require(C("APP_ROOT")."Conf/borrow_config.php");
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
	
	$type_arr = $Bconfig['BORROW_TYPE'];
	
	$field = "i.*,i.id as i_id,i.status as bi_status,i.add_time as invest_time,m.user_name as borrow_user,b.borrow_duration,b.borrow_interest_rate,b.add_time as borrow_time,b.borrow_money,b.borrow_status,b.borrow_name,m.credits,b.danbao,d.*,d.status as de_status";

	$list = M("borrow_investor i")
	       ->field($field)
	       ->join("{$pre}borrow_info b ON b.id=i.borrow_id")
	       ->join( "{$pre}members m ON m.id=b.borrow_uid")
	       ->join( "{$pre}debt d ON i.id=d.invest_id")
	       ->where($map)
	       ->order("i.id DESC")->limit($Lsql)->select();
    //echo M()->getLastSql();
    //dump($list);
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
    	$list[$key]['borrow_type'] = $type_arr[$v['borrow_type']];
     $list[$key]['btype'] = $v['borrow_type'];
    	//投资方式
    	if ($v['parent_invest_id']==0){
    	    $list[$key]['invest_way'] = "直接投资";
    	}else{
    	    $list[$key]['invest_way'] = "认购债权";
    	}
    	
	}
	/*
	foreach($list as $key => $v )
	{
		if($map['i.status'] == 4 )
		{
			$list[$key]['total'] = $v['borrow_type'] == 3 ? "1" : $v['borrow_duration'];     //借款期限
			$list[$key]['back'] = $v['has_pay'];     //已还款期数
		}
	}
	*/
	$row = array();
	$row['list'] = $list;
	$row['page'] = $page;
	/*
	$row['total_money'] = M("borrow_investor i")->where($map)->sum("investor_capital");    //投资金额总和
	$row['total_num'] = $count;    //投标记录条数
	*/
	return $row;
}

/**
 * 支付密码
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
	if(empty($vo['safequestion_status'])) $vo['safequestion_status']=0;
	if(empty($vo['pin_pass'])) {
	    $vo['pin_pass']=0;
	}
	else $vo['pin_pass']=1;
	return $vo;
}



/**********
 * 获取投标记录
 * @borrow_id   标的id
 * @detb        是否为债权转让
 * **********/
function investRecord($borrow_id,$detb = 0){
    $borrow_id = $_GET['borrow_id'];
    $list = M("borrow_investor as b")
        ->join(C("DB_PREFIX")."members as m on  b.investor_uid = m.id")
        ->join(C("DB_PREFIX")."borrow_info as i on  b.borrow_id = i.id")
        ->field('i.borrow_interest_rate, i.repayment_type, b.investor_capital, b.add_time, b.is_auto, m.user_name, i.borrow_duration,i.duration_unit')
        ->where("b.borrow_id={$borrow_id}")
        ->order('b.id desc, b.investor_capital desc')
        ->select();

    /*
     * 债权转让
     * */
    if($detb == 1){

        $debt['invest_id'] = $borrow_id;
        $where = array(
            'parent_invest_id' => $debt['invest_id']
        );
        $fields = "bi.investor_uid,bi.add_time,investor_capital,m.user_name";
        $list = BorrowInvestorModel::getBorrowInvestByPage($where, $fields, 1, 6);//只取6个
        $string = "";
        $string.="<table width='100%'>";
        $string.="<tr>";
        $string.="<td style='width:10%' align='center'>名次</td>";
        $string.="<td style='width:25%' align='center'>投资人</td>";
        $string.="<td style='width:30%' align='center'>金额</td>";
        $string.="<td style='width:35%' align='center'>时间</td>";
        $string.="</tr>";
        if($list === false){
            $string.="<tr>";
            $string.="<td colspan=4 style='font-size:16px' align='center'>暂时没有投资记录</td>";
            $string.="</tr>";
        }else{
            foreach($list['invest_items'] as $k => $v){
            if(!empty($v['user_name'])){
                    $i=$k+1;
                    $string.="<tr style='margin-top:3px;'>";
                    $string.="<td style='width:10%' align='center'>".$i."</td>";
                    $string.="<td style='width:25%' align='center'>".hidecard($v['user_name'],5)."</td>";
                    $string.="<td style='width:30%' align='center'>".Fmoney($v['investor_capital'])."元</td>";
                    $string.="<td style='width:35%' align='center'>".date('Y-m-d',$v['add_time']) ."</td>";
                    $string.="</tr>";
                }
            }
        }

        $string.="</table>";
        return $string;
    }else{
        $string = "";
        $string.="<table width='100%'>";
        $string.="<tr>";
        $string.="<td style='width:10%' align='center'>名次</td>";
        $string.="<td style='width:25%' align='center'>投资人</td>";
        $string.="<td style='width:30%' align='center'>金额</td>";
        $string.="<td style='width:35%' align='center'>时间</td>";
        $string.="</tr>";

        if(empty($list)){
            $string.="</tr>";
            $string.="<tr>";
            $string.="<td colspan=4 style='font-size:16px' align='center'>暂时没有投资记录</td>";
            $string.="</tr>";
        }else{

            foreach($list as $k => $v){
                if(!empty($v['user_name'])){
                    $i=$k+1;
                    $string.="<tr style='margin-top:3px;'>";
                    $string.="<td style='width:10%' align='center'>".$i."</td>";
                    $string.="<td style='width:25%' align='center'>".hidecard($v['user_name'],5)."</td>";
                    $string.="<td style='width:30%' align='center'>".Fmoney($v['investor_capital'])."元</td>";
                    $string.="<td style='width:35%' align='center'>".date('Y-m-d',$v['add_time']) ."</td>";
                    $string.="</tr>";
                }
            }
        }
        $string.="</table>";
        return $string;
    }
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

/*
 * 微信签名验证 access_token,用于调用微信的高级接口,该接口每日调用次数受限，所以需要放到缓存里面。
 *
 * @param string $appid 通过申请微信支付时的邮件中获取或者通过微信公众平台后台获取。
 * @param string $appsecret 通过微信公众平台后台获取。
 * @oaram string $token 缓存名,用于调取。
*/
function get_wetch_access_token($appID,$appsecret,$token){
    static $access_token;
    $access_token = S($token.'weixin_access_token');
    if($access_token) { //已缓存，直接使用
        //file_put_contents("11111.txt",$access_token);
        return $access_token;
    } else { //没有缓存,访问微信接口获取access_token

        $url_get = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appID . '&secret=' . $appsecret;
        $ch1 = curl_init ();
        $timeout = 5;
        curl_setopt ( $ch1, CURLOPT_URL, $url_get );
        curl_setopt ( $ch1, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch1, CURLOPT_CONNECTTIMEOUT, $timeout );
        curl_setopt ( $ch1, CURLOPT_SSL_VERIFYPEER, FALSE );
        curl_setopt ( $ch1, CURLOPT_SSL_VERIFYHOST, false );
        $accesstxt = curl_exec ( $ch1 );
        curl_close ( $ch1 );
        $access = json_decode ( $accesstxt, true );
// 缓存数据7200秒
        S($token.'weixin_access_token',$access['access_token'],7200);
        //file_put_contents("22222.txt",$access['access_token']);
        return $access['access_token'];
    }
}
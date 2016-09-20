<?php

class WithdrawAction extends MCommonAction {

    public function index(){
		$vo1 = M('members')->field('id,user_name,user_email,user_pass,is_ban')->where("id={$this->uid}")->find();
		if($vo1['is_ban']==1||$vo1['is_ban']==2) $this->error("您的帐户已被冻结，请联系客服处理！",__APP__."/index.html");
		
		$voinfo = M("member_info")->field('idcard,real_name')->find($this->uid); 
		$mobile = M('members')->getFieldById($this->uid,'user_phone');  //手机号
		$vobank = M("member_banks")->field(true)->where("uid = {$this->uid} and bank_num !=''")->order('id desc')->select();
		$vobank_latest = M("member_banks")->field(true)->where("uid = {$this->uid} and bank_num !=''")->order('id desc')->limit(1)->select();
		
		$pre = C('DB_PREFIX');
		$field = "m.user_name,m.user_phone,(mm.account_money+mm.back_money) all_money,mm.account_money,mm.back_money,mm.money_collect,i.real_name";
		$vo = M('members m')->field($field)->join("{$pre}member_info i on i.uid = m.id")->join("{$pre}member_money mm on mm.uid = m.id")->where("m.id={$this->uid}")->find();
		$borrow_info = M("borrow_info")
					->field("sum(borrow_money+borrow_interest+borrow_fee) as borrow, sum(repayment_money+repayment_interest) as also")
					->where("borrow_uid = {$this->uid} and borrow_type=4 and borrow_status in (0,2,4,6,8,9,10)")
					->find();
		//可提现 = 可用余额-待还+待收
		$money = $vo['all_money'] - ($borrow_info['borrow']-$borrow_info['also']) + $vo['money_collect'];
		if($money>=$vo['all_money']){
			$money = $vo['all_money'];//如果可提现金额大于等于可用余额
		}
		$vo['all_money'] = $money;
		
		$id5_config = FS("Webconfig/id5");
		$this->assign("id5_enable",$id5_config['enabled']);   //1：开启     0：未开启
		
		//是否开启手机验证
		$datag = get_global_setting();
		$is_manual = $datag['is_manual'];
		$this->assign("is_manual",$is_manual);

		$this->assign("mobile",$mobile);
		$this->assign("voinfo",$voinfo);
		$this->assign("bank_list",get_bank_type($this->uid));
		$this->assign("vobank",$vobank);
		$this->assign("vobank_latest",$vobank_latest[0]);
		$this->assign("vm",$vo);
  
        $tqfee = explode( "|", $this->glo['fee_tqtx']);
		$fee[0] = explode( "-", $tqfee[0]);
		$fee[1] = explode( "-", $tqfee[1]);
		$fee[2] = explode( "-", $tqfee[2]);
		$fee[3] = $tqfee[3];
		$this->assign( "fee",$fee);
		$this->display();
    }

    public function withdraw(){
		$pre = C('DB_PREFIX');
		$field = "m.user_name,m.user_phone,(mm.account_money+mm.back_money) all_money,mm.account_money,mm.back_money,mm.money_collect,i.real_name,b.bank_num,b.bank_name,b.bank_address";
		$vo = M('members m')->field($field)->join("{$pre}member_info i on i.uid = m.id")->join("{$pre}member_money mm on mm.uid = m.id")->join("{$pre}member_banks b on b.uid = m.id")->where("m.id={$this->uid}")->find();
		if(empty($vo['bank_num'])) $data['html'] = '<script type="text/javascript">alert("您还未绑定银行帐户，请先绑定");window.location.href="'.__APP__.'/member/bank#fragment-1";</script>';
		else{
			$tqfee = explode( "|", $this->glo['fee_tqtx']);
			$fee[0] = explode( "-", $tqfee[0]);
			$fee[1] = explode( "-", $tqfee[1]);
			$fee[2] = explode( "-", $tqfee[2]);
			$this->assign( "fee",$fee);
            $borrow_info = M("borrow_info")
                        ->field("sum(borrow_money+borrow_interest+borrow_fee) as borrow, sum(repayment_money+repayment_interest) as also")
                        ->where("borrow_uid = {$this->uid} and borrow_type=4 and borrow_status in (0,2,4,6,8,9,10)")
                        ->find();
           #   $vo['all_money'] -= $borrow_info['borrow'] + $borrow_info['also'];
            //可提现 = 可用余额-待还+待收
            $money = $vo['all_money'] - ($borrow_info['borrow']-$borrow_info['also']) + $vo['money_collect'];
            if($money>=$vo['all_money']){
            	$money = $vo['all_money'];//如果可提现金额大于等于可用余额
            }
            $vo['all_money'] = $money;
            
            $this->assign("borrow_info", $borrow_info);
			$this->assign( "vo",$vo);
			$this->assign("memberinfo", M('members')->find($this->uid));
			$data['html'] = $this->fetch();
		}
		exit(json_encode($data));
    }
	
	public function validate(){
		$pre = C('DB_PREFIX');
		$withdraw_money = floatval($_POST['amount']);
		$pwd = md5($_POST['pwd']);
		$vo = M('members m')->field('mm.account_money,mm.back_money,mm.money_collect,(mm.account_money+mm.back_money) all_money,m.user_leve,m.time_limit')->join("{$pre}member_money mm on mm.uid = m.id")->where("m.id={$this->uid} AND m.pin_pass='{$pwd}'")->find();
        $borrow_info = M("borrow_info")
                        ->field("sum(borrow_money+borrow_interest+borrow_fee) as borrow, sum(repayment_money+repayment_interest) as also")
                        ->where("borrow_uid = {$this->uid} and borrow_type=4 and borrow_status in (0,2,4,6,8,9,10)")
                        ->find();
		if(!is_array($vo)) ajaxmsg("",0);
     #   $borrow_money = $vo['account_money']+$vo['back_money']-($borrow_info['borrow']+$borrow_info['also']);
     	//可提现 = 可用余额-待还+待收
        $money = $vo['all_money'] - ($borrow_info['borrow']-$borrow_info['also']) + $vo['money_collect'];
        if($money>=$vo['all_money']){
        	$money = $vo['all_money'];//如果可提现金额大于等于可用余额
        }
        $borrow_money = $money;
        
        
        if($borrow_money < $withdraw_money){
            ajaxmsg("存在净值标借款".($borrow_info['borrow']+$borrow_info['also'])."元未还，账户余额提现不足",2);
        }
		if($vo['all_money']<$withdraw_money) ajaxmsg("提现额大于帐户余额",2);
		$start = strtotime(date("Y-m-d",time())." 00:00:00");
		$end = strtotime(date("Y-m-d",time())." 23:59:59");
		$wmap['uid'] = $this->uid;
		$wmap['withdraw_status'] = array("neq",3);
		$wmap['add_time'] = array("between","{$start},{$end}");
		$today_money = M('member_withdraw')->where($wmap)->sum('withdraw_money');	
		$today_time = M('member_withdraw')->where($wmap)->count('id');	
		
		$tqfee = explode("|",$this->glo['fee_tqtx']);
		$fee[0] = explode("-",$tqfee[0]);
		$fee[1] = explode("-",$tqfee[1]);
		$fee[2] = explode("-",$tqfee[2]);
		
		$one_limit = $fee[2][0]*10000;
		if($withdraw_money<100 ||$withdraw_money>$one_limit) ajaxmsg("单笔提现金额限制为100-{$one_limit}元",2);
		$today_limit = $fee[2][1]/$fee[2][0];
		if($today_time>$today_limit){
					$message = "一天最多只能提现{$today_limit}次";
					ajaxmsg($message,2);
		}
		
		if(1==1 || $vo['user_leve']>0 && $vo['time_limit']>time()){
		//////////////////////////////////////////
			$itime = strtotime(date("Y-m", time())."-01 00:00:00").",".strtotime( date( "Y-m-", time()).date("t", time())." 23:59:59");
			$wmapx['uid'] = $this->uid;
			$wmapx['withdraw_status'] = array("neq",3);
			$wmapx['add_time'] = array("between","{$itime}");
			$times_month = M("member_withdraw")->where($wmapx)->count("id");
			
			$tqfee1 = explode("|",$this->glo['fee_tqtx']);
			$fee1[0] = explode("-",$tqfee1[0]);
			$fee1[1] = explode("-",$tqfee1[1]);
			if(($withdraw_money-$vo['back_money'])>=0){
				$maxfee1 = ($withdraw_money-$vo['back_money'])*$fee1[0][0]/1000;
				if($maxfee1>=$fee1[0][1]){
					$maxfee1 = $fee1[0][1];
				}
				
				$maxfee2 = $vo['back_money']*$fee1[1][0]/1000;
				if($maxfee2>=$fee1[1][1]){
					$maxfee2 = $fee1[1][1];
				}
				
				$fee = $maxfee1+$maxfee2;
				$money = $withdraw_money-$vo['back_money'];
			}else{
				$fee = $vo['back_money']*$fee1[1][0]/1000;
			}
			
			if($withdraw_money <= $vo['back_money'])
			{
				$message = "您好，您申请提现{$withdraw_money}元，小于目前的回款总额{$vo['back_money']}元，因此无需手续费，确认要提现吗？";
			}else{
				$message = "您好，您申请提现{$withdraw_money}元，其中有{$vo['back_money']}元在回款之内，无需提现手续费，另有{$money}元需收取提现手续费{$fee}元，确认要提现吗？";
			}
			ajaxmsg( "{$message}", 1 );
			
			if(($today_money+$withdraw_money)>$fee[2][1]*10000){
					$message = "单日提现上限为{$fee[2][1]}万元。您今日已经申请提现金额：{$today_money}元,当前申请金额为:{$withdraw_money}元,已超出单日上限，请您修改申请金额或改日再申请提现";
					ajaxmsg($message,2);
			}
			
		//////////////////////////////////////////////
				
		}else{//普通会员暂未使用
				if(($today_money+$withdraw_money)>300000){
					$message = "您是普通会员，单日提现上限为30万元。您今日已经申请提现金额：$today_money元,当前申请金额为:$withdraw_money元,已超出单日上限，请您修改申请金额或改日再申请提现";
					ajaxmsg($message,2);
				}
				$tqfee = $this->glo['fee_pttx'];
				$fee = getFloatValue($tqfee*$withdraw_money/100,2);
				
				if( ($vo['account_money']-$withdraw_money - $fee)<0 ){
					$message = "您好，您申请提现{$withdraw_money}元，提现手续费{$fee}元将从您的提现金额中扣除，确认要提现吗？";
				}else{
					$message = "您好，您申请提现{$withdraw_money}元，提现手续费{$fee}元将从您的账户余额中扣除，确认要提现吗？";
				}
				ajaxmsg("{$message}",1);
		}
	}
	
	public function actwithdraw(){
		if($_SESSION['code'] != sha1(strtolower($_POST['valicode']))){
			ajaxmsg("验证码错误",0);
		}
		$pre = C('DB_PREFIX');
		$withdraw_money = floatval($_POST['amount']);
		$bank_id = intval($_POST['bank_id']);
		$pwd = md5($_POST['pwd']);
		$vo = M('members m')->field('mm.account_money,mm.back_money,(mm.account_money+mm.back_money) all_money,m.user_leve,m.time_limit')->join("{$pre}member_money mm on mm.uid = m.id")->where("m.id={$this->uid} AND m.pin_pass='{$pwd}'")->find();
		if(!is_array($vo)) ajaxmsg("支付密码错误",0);
		if($vo['all_money']<$withdraw_money) ajaxmsg("提现额大于账户余额",2);
		$start = strtotime(date("Y-m-d",time())." 00:00:00");
		$end = strtotime(date("Y-m-d",time())." 23:59:59");
		$wmap['uid'] = $this->uid;
		$wmap['withdraw_status'] = array("neq",3);
		$wmap['add_time'] = array("between","{$start},{$end}");
		$today_money = M('member_withdraw')->where($wmap)->sum('withdraw_money');	
		$today_time = M('member_withdraw')->where($wmap)->count('id');	
		$tqfee = explode("|",$this->glo['fee_tqtx']);
		$fee[0] = explode("-",$tqfee[0]);
		$fee[1] = explode("-",$tqfee[1]);
		$fee[2] = explode("-",$tqfee[2]);
		$one_limit = $fee[2][0]*10000;
		if($withdraw_money<100 ||$withdraw_money>$one_limit) ajaxmsg("单笔提现金额限制为100-{$one_limit}元",2);
		$today_limit = $fee[2][1]/$fee[2][0];
		if($today_time>=$today_limit){
					$message = "一天最多只能提现{$today_limit}次";
					ajaxmsg($message,2);
		}
		
		if(1==1 || $vo['user_leve']>0 && $vo['time_limit']>time()){
			if(($today_money+$withdraw_money)>$fee[2][1]*10000){
				$message = "单日提现上限为{$fee[2][1]}万元。您今日已经申请提现金额：{$today_money}元,当前申请金额为:{$withdraw_money}元,已超出单日上限，请您修改申请金额或改日再申请提现";
				ajaxmsg($message,2);
			}
			$itime = strtotime(date("Y-m", time())."-01 00:00:00").",".strtotime( date( "Y-m-", time()).date("t", time())." 23:59:59");
			$wmapx['uid'] = $this->uid;
			$wmapx['withdraw_status'] = array("neq",3);
			$wmapx['add_time'] = array("between","{$itime}");
			$times_month = M("member_withdraw")->where($wmapx)->count("id");
			
		
			$tqfee1 = explode("|",$this->glo['fee_tqtx']);
			$fee1[0] = explode("-",$tqfee1[0]);
			$fee1[1] = explode("-",$tqfee1[1]);
			$fee1[3] = $tqfee1[3];
			if(($withdraw_money-$vo['back_money'])>=0){
				$maxfee1 = ($withdraw_money-$vo['back_money'])*$fee1[0][0]/1000;
				if($maxfee1>=$fee1[0][1]){
					$maxfee1 = $fee1[0][1];
				}
				
				$maxfee2 = $vo['back_money']*$fee1[1][0]/1000;
				if($maxfee2>=$fee1[1][1]){
					$maxfee2 = $fee1[1][1];
				}
				
				$fee = $maxfee1+$maxfee2;
				$money = $withdraw_money-$vo['back_money'];
			}else{
				$fee = $withdraw_money*$fee1[1][0]/1000;
				if($fee>=$fee1[1][1]){
					$fee = $fee1[1][1];
				}
			}
			if ($fee>0 && $fee<$fee1[3]){
			    $fee = $fee1[3];
			}
			$withdraw = M('member_money')->field('back_money,account_money')->find($this->uid);
            if ($withdraw_money>$withdraw['back_money']){
                $moneydata['back_money'] = $withdraw['back_money'];
                $moneydata['account_money'] = $withdraw_money-$withdraw['back_money'];
            }else{
                $moneydata['back_money'] = $withdraw_money;
                $moneydata['account_money'] = '0.00';
            }
			$moneydata['withdraw_money'] = $withdraw_money;
			$moneydata['withdraw_fee'] = $fee;
			$moneydata['second_fee'] = $fee;
			$moneydata['withdraw_status'] = 0;
			$moneydata['uid'] =$this->uid;
			$moneydata['add_time'] = time();
			$moneydata['add_ip'] = get_client_ip();
			$moneydata['bank_id'] = $bank_id;
			$newid = M('member_withdraw')->add($moneydata);
			if($newid){
				//memberMoneyLog($this->uid,4,-$withdraw_money,"提现,默认自动扣减手续费".$fee."元",'0','@网站管理员@',-$fee);
				memberMoneyLog($this->uid,4,-$withdraw_money,"提现,默认自动扣减手续费".$fee."元",'0','@网站管理员@');
				MTip('chk6',$this->uid, '', '', null, 1);
				MTip('chk6',$this->uid, '', '', null, 2);
				MTip('chk6',$this->uid, '', '', null, 3);
				//NoticeSet('chk6',$this->uid);
				ajaxmsg("恭喜，提现申请提交成功",1);
			} 
			ajaxmsg("对不起，提现出错，请重试",2);
		}else{//普通会员暂未使用
				if(($today_money+$withdraw_money)>300000){
					$message = "您是普通会员，单日提现上限为30万元。您今日已经申请提现金额：$today_money元,当前申请金额为:$withdraw_money元,已超出单日上限，请您修改申请金额或改日再申请提现";
					ajaxmsg($message,2);
				}
				$tqfee = $this->glo['fee_pttx'];
				$fee = getFloatValue($tqfee*$withdraw_money/100,2);
				
				if( ($vo['account_money']-$withdraw_money - $fee)<0 ){
				
					$withdraw_money = ($withdraw_money - $fee);
					$moneydata['withdraw_money'] = $withdraw_money;
					$moneydata['withdraw_fee'] = $fee;
					$moneydata['withdraw_status'] = 0;
					$moneydata['uid'] =$this->uid;
					$moneydata['add_time'] = time();
					$moneydata['add_ip'] = get_client_ip();
					$moneydata['bank_id'] = $bank_id;
					$newid = M('member_withdraw')->add($moneydata);
					if($newid){
						memberMoneyLog($this->uid,4,-$withdraw_money - $fee,"提现,自动扣减手续费".$fee."元");
						MTip('chk6',$this->uid, '', '', null, 1);
						MTip('chk6',$this->uid, '', '', null, 2);
						MTip('chk6',$this->uid, '', '', null, 3);
						//NoticeSet('chk6',$this->uid);
						ajaxmsg("恭喜，提现申请提交成功",1);
					} 
				}else{
					$moneydata['withdraw_money'] = $withdraw_money;
					$moneydata['withdraw_fee'] = $fee;
					$moneydata['withdraw_status'] = 0;
					$moneydata['uid'] =$this->uid;
					$moneydata['add_time'] = time();
					$moneydata['add_ip'] = get_client_ip();
					$moneydata['bank_id'] = $bank_id;
					$newid = M('member_withdraw')->add($moneydata);
					if($newid){
						memberMoneyLog($this->uid,4,-$withdraw_money,"提现,自动扣减手续费".$fee."元",'0','@网站管理员@',-$fee);
						MTip('chk6',$this->uid, '', '', null, 1);
						MTip('chk6',$this->uid, '', '', null, 2);
						MTip('chk6',$this->uid, '', '', null, 3);
						//NoticeSet('chk6',$this->uid);
						ajaxmsg("恭喜，提现申请提交成功",1);
					} 
				}
				ajaxmsg("对不起，提现出错，请重试",2);
		}
	}
	
	public function backwithdraw(){
		$id = intval($_GET['id']);
		$map['withdraw_status'] = 0;
		$map['uid'] = $this->uid;
		$map['id'] = $id;
		$vo = M('member_withdraw')->where($map)->find();
		if(!is_array($vo)) ajaxmsg('',0);
		///////////////////////////////////////////////
		$field = "(mm.account_money+mm.back_money) all_money,mm.account_money,mm.back_money";
		$m = M('member_money mm')->field($field)->where("mm.uid={$this->uid}")->find();
		////////////////////////////////////////////////////
		$newid = M('member_withdraw')->where($map)->delete();
		if($newid){
			$res = memberMoneyLog($this->uid,5,$vo['withdraw_money'],"撤消提现",'0','@网站管理员@');
		}
		if($res) ajaxmsg();
		else ajaxmsg("",0);
	}

    public function withdrawlog(){
		if($_GET['start_time']&&$_GET['end_time']){
			$_GET['start_time'] = strtotime($_GET['start_time']." 00:00:00");
			$_GET['end_time'] = strtotime($_GET['end_time']." 23:59:59");
			
			if($_GET['start_time']<$_GET['end_time']){
				$map['add_time']=array("between","{$_GET['start_time']},{$_GET['end_time']}");
				$search['start_time'] = $_GET['start_time'];
				$search['end_time'] = $_GET['end_time'];
			}
		}
		$curr_time = "time=1";
		$curr_type = "";
		$time = $_GET['time'];
		$type = $_GET['type'];
		if($time){
			switch($time){
				case "1":
					$map['add_time'] = array("lt",time());
					break;
				case "2":
					$map['add_time'] = array("between",array(strtotime("-1 week"),time()));
					break;
				case "3":
				    $map['add_time'] = array("between",array(strtotime("-1 month"),time()));
					break;
				case "4":
				    $map['add_time'] = array("between",array(strtotime("-3 months"),time()));
					break;
			}
			$curr_time = "time=".$time;
		}
		if(isset($type)){
			$map['withdraw_status'] = $type;
			$curr_type = "&type=".$type;
		}
		
		$map['uid'] = $this->uid;
		$list = getWithDrawLog($map,15);
		$this->assign('curr_time',$curr_time);
		$this->assign('curr_type',$curr_type);
		$this->assign('search',$search);
		$this->assign("list",$list['list']);
		$this->assign("pagebar",$list['page']);

		$this->display();
    }
	public function getarea(){
		$rid = intval($_POST['pid']);
		if(empty($rid)){
			$data['code'] = 1;
			exit(json_encode($data));
		}
		$map['reid'] = $rid;
		$alist = M('area')->field('id,name')->order('sort_order DESC')->where($map)->select();

		if(count($alist)===0){
			$str="<option value=''>--该地区下无下级地区--</option>";
		}else{
			if($rid==1) $str="<option value='0'>请选择省份</option>";
			foreach($alist as $v){
				$str.="<option value='{$v['id']}'>{$v['name']}</option>";
			}
		}
		$data['data'][] = $str;
		$data['code'] = 0;
		$res = json_encode($data);
		echo $res;
	}
    /**
     * 添加银行卡账号
     */
    public function addbank()
    {
        $pre = C('DB_PREFIX');
        $vm = M("members m")->field("s.id_status,s.phone_status,m.pin_pass")->join("{$pre}members_status s ON s.uid=m.id")->where("m.id={$this->uid}")->find();
        if ($vm['id_status']!=1) ajaxmsg("请先完成实名认证",0);
        if ($vm['phone_status']!=1) ajaxmsg("请先完成手机认证",0);
        if (empty($vm['pin_pass'])) ajaxmsg("请先设置支付密码",0);
        if (empty($_POST['vcode'])){
            $this->doadd($_POST);
        }else{
            if( is_verify($this->uid,text($_POST['vcode']),2,10*60) ){
                $this->doadd($_POST);
            }
            else ajaxmsg("验证码错误，请重新输入~",0);
        }
    }
    protected function doadd($data){
        unset($_POST['vcode']);
        $data = textPost($_POST);
        $arr['uid'] = $this->uid;
        $arr['bank_name'] = $data['bank_name'];
        $userCount = M('member_banks')->where($arr)->count("id");
        if ($userCount<>0) ajaxmsg('不能重复添加数据！请刷新后再试~',0);
        $data['uid'] = $this->uid;
        $data['add_ip'] = get_client_ip();
        $data['add_time'] = time();
        $newid = M('member_banks')->add($data);
        if($newid){
            MTip('chk2',$this->uid, '', '', null, 1);
            MTip('chk2',$this->uid, '', '', null, 2);
            MTip('chk2',$this->uid, '', '', null, 3);
            //NoticeSet('chk2',$this->uid);
            $dd['id'] = $newid;
            $dd['account'] = $data['bank_num'];
            $dd['img_src'] = $data['bank_name'];
            $dtt['data'] = $dd;
            $dtt['status'] = 1;
            echo json_encode($dtt);
        }
        else ajaxmsg('操作失败，请重试~',0);
    }
	public function verify(){
	    ob_clean();
		Header("Content-type: image/GIF");
	    import("ORG.Util.Imagecode");
	    $imagecode=new Imagecode(113,30);//(96,30);//参数控制图片宽、高
	    $imagecode->imageout();
	}
}
<?php
class McenterAction extends MMCommonAction {

    function __construct()
    {
        parent::__construct();
        $this->uid = session('u_id');
		D("AgilityBehavior");
        $this->AgilityBehavior = new AgilityBehavior();
		$this->Model = M('debt');
    }
     
	//我的帐户
    public function userinfo() {
        // $mess['session_expired']=$this->$sessionExpired;
		$pre = C('DB_PREFIX');
        $mess = array();
        $mess['uid'] = intval(session("u_id"));
        $vo = M('members')->field('id,user_name,user_email,user_pass,is_ban')->where("id={$mess['uid']}")->find();
        $mess['username'] = $vo['user_name'];
        $minfo = getMinfo($mess['uid'],true);
		//累计收益
		$agility_interest = BaoInvestModel::get_sum_interest($this->uid);
		$income = get_personal_benefit($this->uid);
		$minfo['income'] = $income['total'];
		//灵活宝资金详情
		$agility_money = BaoInvestModel::get_sum_money($this->uid);
		//代收金额
		$now = time();
		$sec_day = 24 * 60 * 60;
		$field = "sum(interest-interest_fee) as interest, sum(if (deadline > {$now}, ((deadline - {$now}))/{$sec_day}, 0)) as sumday";
		$wait = M("investor_detail")->field($field)->where("investor_uid = {$this->uid} AND status in (6,7)")->find();
		//灵活宝待收收益
        $agility_collect = BaoInvestModel::get_collect_money($this->uid);
        $wait['interest'] += $agility_collect['collect_interest'];
        $wait['perday'] = $wait['interest']/($wait['sumday']+$agility_collect['collect_days']);

		$voo = M('members m')->field($field)->join("{$pre}member_info i on i.uid = m.id")->join("{$pre}member_money mm on mm.uid = m.id")->join("{$pre}member_banks b on b.uid = m.id")->where("m.id={$this->uid}")->find();

        
		$ids = M('members_status')->where('uid='.$this->uid)->find();
        if($ids['id_status']=='0' || empty($voo['bank_num'])){
			$mess['is_verify'] = 0;
		}else{
			$mess['is_verify'] = 1;
		}
        $membermoney = M("member_money")->field(true)->where("uid={$mess['uid']}")->find();
        if(is_array($minfo)){    
			$mess['outmoney'] = $minfo['account_money'] + $minfo['back_money'];//可用余额
            $mess['income'] = $minfo['income'] + $agility_interest;//累计收益
            $mess['collect'] = floatval($wait['interest']);//代收
            $mess['total'] = $minfo['account_money'] + $minfo['back_money'] + $minfo['money_freeze'] + $minfo['money_collect'] + $agility_money;//总额
        }else{
			$mess['outmoney'] = 0;
            $mess['total'] = 0;
            $mess['income'] = 0;
            $mess['collect'] = 0;
        }
        AppCommonAction::ajax_encrypt($mess,1);
    }
   
   
	//我的帐户之帐户信息
    public function accountinfo(){
        $jsoncode = file_get_contents("php://input");

        $mstatus=M('members_status')->field('id_status,email_status, phone_status,id_status')->find($this->uid);
        $memberinfo=M('members')->field('pin_pass,user_phone,user_email,is_transfer,user_name')->find($this->uid);
        $memberdetail=M('member_info')->field('real_name,idcard')->where('uid='.$this->uid)->find();

		$arr['is_transfer'] = $memberinfo['is_transfer'];
		$arr['username'] = $memberinfo['user_name'];

        $arr['phone_status'] = $mstatus['phone_status'];
        $arr['phone'] = $memberinfo['user_phone'];

        $arr['email_status'] = $mstatus['email_status'];
        $arr['email'] = $memberinfo['user_email'];

        $arr['real_status'] = $mstatus['id_status'];
        $arr['real'] =mb_substr($memberdetail['real_name'], 0, 1, 'utf-8')."***";   //2015-01-19 xiugai
        $arr['real_id'] = hidecard($memberdetail['idcard'],1);

		$arr['is_manual'] = $this->glo['is_manual'];//是否开启手机验证
        // $arr['session_expired']=$this->sessionExpired;
        $vobank = M("member_banks")->field(true)->where("uid = {$this->uid} and bank_num !=''")->find();
        if($vobank){
           // $arr['card'] = hidecard($vobank['bank_num'],4);
            $arr['card_status'] = 1;
        }else{
            $arr['card_status'] = 0;
           // $arr['card'] = '';
        }
       /* $arr['avatar']=get_avatar($this->uid,'small');
        $arr['credits'] = 'E';
		
        
        //绑卡状态
        $user_data = M('escrow_account')->getFieldByUid($this->uid,'bind_status');
        $arr['bind_status'] = $user_data;
		*/
        AppCommonAction::ajax_encrypt($arr,1);
        //ajaxmsg($arr);
    }

    //我要提现
    public function tixian(){
        //   $list['session_expired']=$this->$sessionExpired;
        $jsoncode = file_get_contents("php://input");
//        alogsm("tixian",0,1,session("u_id").$jsoncode);
        $pre = C('DB_PREFIX');
        $field = "m.user_name,m.user_phone,(mm.account_money+mm.back_money) all_money,mm.account_money,mm.back_money,i.real_name,b.bank_num,b.bank_name,b.bank_address";
        $vo = M('members m')->field($field)->join("{$pre}member_info i on i.uid = m.id")->join("{$pre}member_money mm on mm.uid = m.id")->join("{$pre}member_banks b on b.uid = m.id")->where("m.id={$this->uid}")->find();

        if(empty($vo['bank_num'])){
            $list['is_jumpmsg']="您未绑定银行卡，请先绑定银行卡后进行提现操作！";
            //echo ajaxmsg($list,0);
            AppCommonAction::ajax_encrypt($list,1005);
        }else{
            $list['bank_num'] = substr($vo['bank_num'],-4);
            $list['bank_name'] = $vo['bank_name'];
            //$list['bank_address'] = $vo['bank_address'];
            $list['real_name'] = $vo['real_name'];
            $list['user_phone'] = $vo['user_phone'];
            $list['all_money'] = $vo['all_money'];
            $list['qixian'] = "72小时/24小时（72小时内打款，到帐时间因各个银行不同） ";
            //echo ajaxmsg($list);
            AppCommonAction::ajax_encrypt($list,0);

            //$tqfee = explode( "|", $this->glo['fee_tqtx']);
//            $fee[0] = explode( "-", $tqfee[0]);
//            $fee[1] = explode( "-", $tqfee[1]);
//            $fee[2] = explode( "-", $tqfee[2]);
//            $this->assign( "fee",$fee);
            //$this->assign( "vo",$vo);
            //$this->assign("memberinfo", M('members')->find($this->uid));
            //$data['html'] = $this->fetch();
        }
        //exit(json_encode($data));
    }
	//提现页面
	public function validate_index(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		if(!$this->uid){
			AppCommonAction::ajax_encrypt('请先登录', 0);
		}
        $borrowconfig = get_bconf_setting();
        $ids = M('members_status')->field('id_status,phone_status')->find($this->uid);
        if ($ids['id_status']!=1){
            AppCommonAction::ajax_encrypt('您还未完成身份验证，请先进行实名认证', 0);
        }elseif ($ids['phone_status']!=1){
            AppCommonAction::ajax_encrypt('您还未完成身份验证，请先进行手机认证', 0);
        } 
		$vobank = M("member_banks")->field(true)->where("uid = {$this->uid} and bank_num !=''")->order('id desc')->select();
		$mobile = M('members')->getFieldById($this->uid,'user_phone');  
		$bank_name = $borrowconfig['BANK_NAME'];//银行列表
		//$bank_list = get_bank_type($this->uid);
		$_list = array();
		//$datas = array();
		foreach($vobank as $k=>$value){
			$_list[$k]['id'] = $value['id']; //银行卡id
			$_list[$k]['bank_id'] = $value['bank_name'];//银行id
			$_list[$k]['bank_name'] = $bank_name[$value['bank_name']]; //银行名称
			$_list[$k]['bank_num'] = $value['bank_num']; //银行卡号
			//array_push($datas,$_list);
		}
		
		if(is_array($_list) and !empty($_list)){
            $data['list'] = $_list;
        }else{
            $data['is_jumpmsg'] = '您未绑定银行卡，请先绑定银行卡后进行提现操作！';
			AppCommonAction::ajax_encrypt($data,1005);
        }
		//帐户余额
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
		$tqfee = explode("|",$this->glo['fee_tqtx']);
		$fee[0] = explode("-",$tqfee[0]);
		$fee[1] = explode("-",$tqfee[1]);
		$fee[2] = explode("-",$tqfee[2]);
		$minfee = $tqfee[3];
		$data['cc_hksxfee'] = $fee[0][0]; //超出回款金额费率
		$data['maxfee'] = $fee[0][1];   //超出回款金额手续费最大金额
		$data['hksxfee'] = $fee[1][0];  //回款金额费率
		$data['hk_maxfee'] = $fee[1][1]; //回款金额手续费最大金额
		$data['minfee'] = $minfee;   //手续费最低金额
		$data['all_money'] = $money;//可提现金额
		$data['back_money'] = $vo['back_money'];//免手续费金额

        AppCommonAction::ajax_encrypt($data,1);
		
    }


    //提现前确认
    public function validate(){
        ///  $message['session_expired']=$this->$sessionExpired;
        $jsoncode = file_get_contents("php://input");
//        alogsm("validate",0,1,session("u_id").$jsoncode);
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        if (!is_array($arr)||empty($arr)||empty($arr['amount'])||empty($arr['pwd'])) {
            $message['message']="请求错误！";
            AppCommonAction::ajax_encrypt($message,0);
            //ajaxmsg($message,0);
        }
        if (intval($arr['uid'])!=$this->uid){
            $message['message']="请求错误！";
            AppCommonAction::ajax_encrypt($message,0);
            //ajaxmsg($message,0);
        }
        $pre = C('DB_PREFIX');
        $withdraw_money = floatval($arr['amount']);
        $pwd = md5($arr['pwd']);
        //alogsm("validate",0,1,$arr['pwd']."-".$arr['amount']);
        $vo = M('members m')->field('mm.account_money,mm.back_money,m.user_leve,m.time_limit')->join("{$pre}member_money mm on mm.uid = m.id")->where("m.id={$this->uid} AND m.pin_pass='{$pwd}'")->find();
        //$this->display("Public:_footer");
        if(!is_array($vo)) {
            AppCommonAction::ajax_encrypt("密码错误！",0);
        }
        //alogsm("validate_密码是否正确",0,1,is_array($vo));//
        if(($vo['account_money']+$vo['back_money'])<$withdraw_money) {
            //alogsm("validate",0,1,"提现额大于帐户余额");//
            $message['message']="提现额大于帐户余额";
            AppCommonAction::ajax_encrypt($message,2);
        }
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
        if($withdraw_money<100 ||$withdraw_money>$one_limit) {
            AppCommonAction::ajax_encrypt("单笔提现金额限制为100-{$one_limit}元",2);
        }
        $today_limit = $fee[2][1]/$fee[2][0];
        if($today_time>$today_limit){
            $message['message'] = "一天最多只能提现{$today_limit}次";
            //alogsm("validate",0,1,$message);//
            AppCommonAction::ajax_encrypt($message,2);
        }

        if(1==1 || $vo['user_leve']>0 && $vo['time_limit']>time()){
            //////////////////////////////////////////
            $itime = strtotime(date("Y-m", time())."-01 00:00:00").",".strtotime( date( "Y-m-", time()).date("t", time())." 23:59:59");
            $wmapx['uid'] = $this->uid;
            $wmapx['withdraw_status'] = array("neq",3);
            $wmapx['add_time'] = array("between","{$itime}");
            $times_month = M("member_withdraw")->where($wmapx)->count("id");


            if(($withdraw_money-$vo['back_money'])>=0){
                $maxfee1 = ($withdraw_money-$vo['back_money'])*$fee[0][0]/1000;
                if($maxfee1>=$fee[0][1]){
                    $maxfee1 = $fee[0][1];
                }

                $maxfee2 = $vo['back_money']*$fee[1][0]/1000;
                if($maxfee2>=$fee[1][1]){
                    $maxfee2 = $fee[1][1];
                }

                $fee = $maxfee1+$maxfee2;
                $money = $withdraw_money-$vo['back_money'];
            }else{
                $fee = $vo['back_money']*$fee[1][0]/1000;
            }

            if($withdraw_money <= $vo['back_money'])
            {
                $message['message'] = "您好，您申请提现{$withdraw_money}元，小于目前的回款总额{$vo['back_money']}元，因此无需手续费，确认要提现吗？";
            }else{
                $message['message'] = "您好，您申请提现{$withdraw_money}元，其中有{$vo['back_money']}元在回款之内，无需提现手续费，另有{$money}元需收取提现手续费{$fee}元，确认要提现吗？";
            }
            //alogsm("validate",0,1,$message);//
            AppCommonAction::ajax_encrypt($message,1);

            if(($today_money+$withdraw_money)>$fee[2][1]*10000){
                $message['message'] = "单日提现上限为{$fee[2][1]}万元。您今日已经申请提现金额：{$today_money}元,当前申请金额为:{$withdraw_money}元,已超出单日上限，请您修改申请金额或改日再申请提现";
                //alogsm("validate",0,1,$message);//
                AppCommonAction::ajax_encrypt($message,2);
            }

            //////////////////////////////////////////////

        }else{//普通会员暂未使用
            if(($today_money+$withdraw_money)>300000){
                $message['message'] = "您是普通会员，单日提现上限为30万元。您今日已经申请提现金额：$today_money元,当前申请金额为:$withdraw_money元,已超出单日上限，请您修改申请金额或改日再申请提现";
                //alogsm("validate",0,1,$message);//
                AppCommonAction::ajax_encrypt($message,2);
            }
            $tqfee = $this->glo['fee_pttx'];
            $fee = getFloatValue($tqfee*$withdraw_money/100,2);

            if( ($vo['account_money']-$withdraw_money - $fee)<0 ){
                $message['message'] = "您好，您申请提现{$withdraw_money}元，提现手续费{$fee}元将从您的提现金额中扣除，确认要提现吗？";
            }else{
                $message['message'] = "您好，您申请提现{$withdraw_money}元，提现手续费{$fee}元将从您的帐户余额中扣除，确认要提现吗？";
            }
            //alogsm("validate",0,1,$message);//
            AppCommonAction::ajax_encrypt($message,1);
        }
    }
    //最后提现
    public function actwithdraw(){
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        if (!is_array($arr)||empty($arr)||empty($arr['amount'])||empty($arr['pwd'])) {
            //alogsm("actwithdraw_fail",0,1,"请求错误！");//
            $message['message']="请求错误！";
            AppCommonAction::ajax_encrypt($message,0);
        }
        if (intval($arr['uid'])!=$this->uid){
            //alogsm("actwithdraw_fail",0,1,"用户错误！");//
            $message['message']="用户错误！";
            AppCommonAction::ajax_encrypt($message,0);
        }
		if($_SESSION['code'] != sha1(strtolower($arr['code']))){
			AppCommonAction::ajax_encrypt("验证码错误",0);
		}
        $pre = C('DB_PREFIX');
        $withdraw_money = floatval($arr['amount']);
		$bank_id = intval($arr['bank_id']);
        $pwd = md5($arr['pwd']);
        //alogsm("actwithdraw_pwd",0,1,$arr['pwd']."-".$arr['amount']);//
        $vo = M('members m')->field('mm.account_money,mm.back_money,(mm.account_money+mm.back_money) all_money,m.user_leve,m.time_limit')->join("{$pre}member_money mm on mm.uid = m.id")->where("m.id={$this->uid} AND m.pin_pass='{$pwd}'")->find();
		if(!is_array($vo)) ajaxmsg("支付密码错误",0);
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
				if($fee>=$fee1[1][1]){
					$fee = $fee1[1][1];
				}
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
				/*MTip('chk6',$this->uid, '', '', null, 1);
				MTip('chk6',$this->uid, '', '', null, 2);
				MTip('chk6',$this->uid, '', '', null, 3);*/
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
						/*MTip('chk6',$this->uid, '', '', null, 1);
						MTip('chk6',$this->uid, '', '', null, 2);
						MTip('chk6',$this->uid, '', '', null, 3);*/
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
						/*MTip('chk6',$this->uid, '', '', null, 1);
						MTip('chk6',$this->uid, '', '', null, 2);
						MTip('chk6',$this->uid, '', '', null, 3);*/
						//NoticeSet('chk6',$this->uid);
						ajaxmsg("恭喜，提现申请提交成功",1);
					} 
				}
				ajaxmsg("对不起，提现出错，请重试",2);
		}
    }

    public function backwithdraw(){
        //      $message['session_expired']=$this->$sessionExpired;
        $id = intval($_GET['id']);
        $map['withdraw_status'] = 0;
        $map['uid'] = $this->uid;
        $map['id'] = $id;
        $vo = M('member_withdraw')->where($map)->find();
        if(!is_array($vo)) {
            AppCommonAction::ajax_encrypt("",0);
        }
        ///////////////////////////////////////////////
        $field = "(mm.account_money+mm.back_money) all_money,mm.account_money,mm.back_money";
        $m = M('member_money mm')->field($field)->where("mm.uid={$this->uid}")->find();
        ////////////////////////////////////////////////////
        $newid = M('member_withdraw')->where($map)->delete();
        if($newid){
            $res = memberMoneyLog($this->uid,5,$vo['withdraw_money'],"撤消提现",'0','@网站管理员@');

        }
        if($res){
            AppCommonAction::ajax_encrypt($message,1);
        }else{
            AppCommonAction::ajax_encrypt($message,0);
        }
    }

    public function withdrawlog(){
        //    $data['session_expired']=$this->$sessionExpired;
        if($_GET['start_time']&&$_GET['end_time']){
            $_GET['start_time'] = strtotime($_GET['start_time']." 00:00:00");
            $_GET['end_time'] = strtotime($_GET['end_time']." 23:59:59");

            if($_GET['start_time']<$_GET['end_time']){
                $map['add_time']=array("between","{$_GET['start_time']},{$_GET['end_time']}");
                $search['start_time'] = $_GET['start_time'];
                $search['end_time'] = $_GET['end_time'];
            }
        }

        $map['uid'] = $this->uid;
        $list = getWithDrawLog($map,15);
        $this->assign('search',$search);
        $this->assign("list",$list['list']);
        $this->assign("pagebar",$list['page']);

        $data['html'] = $this->fetch();
        exit(json_encode($data));
    }
    //交易记录
    public function tradinglog(){
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        if ($arr['uid'] != $this->uid){
            AppCommonAction::ajax_encrypt("用户错误！",0);
        }

        $_GET['p'] = intval($arr['page']);
        $page=intval($arr['page']); //tianjia  2015-01-21
        $limit = intval($arr['limit']);
        $map['uid'] = $this->uid;
        $list = getMoneyLog($map,$limit);

        $loglist = $list['list'];
        foreach($loglist as $key=>$v) {
            $_list[$key]['id'] = $v['id'];
            $_list[$key]['add_time'] = date("Y-m-d",$v['add_time']);
            $_list[$key]['affect_money'] = $v['affect_money'];
            //start 2015-01-19
            $_list[$key]['account_money']=$v['account_money']+$v['back_money'];//可用金额：account_money
            $_list[$key]['freeze_money']=$v['freeze_money']; //冻结金额：freeze_money
            $_list[$key]['collect_money']=$v['collect_money'];//代收金额：collect_money
            //end 2015-01-19
            $_list[$key]['info'] = $v['info'];
            $_list[$key]['type'] = $v['type'];
        }

        $count = M('member_moneylog')->where($map)->count('id');
        $totalPage = ceil($count/$limit);
        if($_list){
            $row=array();
            $row['list'] = $_list;
            $row['totalPage'] = $totalPage;
            $row['nowPage'] =  $page;
        }else{
            AppCommonAction::ajax_encrypt("暂无交易纪录",0);
        }
        AppCommonAction::ajax_encrypt($row,1);
    }

    //更多交易记录
    public function tradinglogadd(){
        $jsoncode = file_get_contents("php://input");
        //    $m_list['session_expired']=$this->$sessionExpired;
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        if (intval($arr['uid'])!=$this->uid){
            $m_list['message']="用户错误！";
            AppCommonAction::ajax_encrypt($m_list,0);
        }
        if (!is_array($arr)||empty($arr)||empty($arr['id'])) {
            $m_list['message']="查询错误！";
            AppCommonAction::ajax_encrypt($m_list,0);
        }

        $id = $arr['id'];

        $map['id'] = array('lt',$id);
        $map['uid'] = $this->uid;
        $list = getMoneyLog($map,15);
        if(is_array($list)&&!empty($list)){
            $loglist = $list['list'];
            foreach($loglist as $key=>$v) {
                $_list[$key]['id'] = $v['id'];
                $_list[$key]['add_time'] = date("Y-m-d",$v['add_time']);
                $_list[$key]['affect_money'] = $v['affect_money'];
                $_list[$key]['info'] = $v['info'];
                $_list[$key]['type'] = $v['type'];
            }
            $m_list['list'] = $_list;
        }

        if(is_array($_list)&&!empty($_list)){
            AppCommonAction::ajax_encrypt($m_list,1);
        }else{
            $m_list['message']="暂无交易纪录";
            AppCommonAction::ajax_encrypt($m_list,0);
        }
    }
    //投标记录
    public function tendlog(){
        $jsoncode = file_get_contents("php://input");
        //   $tendlist['session_expired']=$this->$sessionExpired;
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        if (intval($arr['uid'])!=$this->uid){
            $tendlist['message']="用户错误！";
            AppCommonAction::ajax_encrypt($tendlist,0);
        }
        $pre = C('DB_PREFIX');
        //普通标
        $fieldx = "bi.investor_capital,bi.add_time,m.user_name,bo.borrow_name";
        $investinfo = M("borrow_investor bi")->field($fieldx)->join("{$pre}members m ON bi.investor_uid = m.id")->join("{$pre}borrow_info bo ON bo.id =bi.borrow_id")->limit(10)->where("bi.investor_uid={$this->uid}")->order("bi.id DESC")->select();
        foreach($investinfo as $key=>$v){
            $list[$key]['borrow_name'] = $v['borrow_name'];
            $list[$key]['investor_capital'] = $v['investor_capital'];
            $list[$key]['add_time'] = date("Y-m-d",$v['add_time']);

        }
        //企业直投
        $_fieldx = "bi.investor_capital,bi.add_time,m.user_name,bo.borrow_name";
        $_investinfo = M("transfer_borrow_investor bi")->field($_fieldx)->join("{$pre}members m ON bi.investor_uid = m.id")->join("{$pre}transfer_borrow_info bo ON bo.id =bi.borrow_id")->limit(10)->where("bi.investor_uid={$this->uid}")->order("bi.id DESC")->select();
        foreach($_investinfo as $key=>$v){
            $_list[$key]['borrow_name'] = $v['borrow_name'];
            $_list[$key]['investor_capital'] = $v['investor_capital'];
            $_list[$key]['add_time'] = date("Y-m-d",$v['add_time']);

        }

        $tendlist["invest"] = $list;
        $tendlist["tinvest"] = $_list;

        if (!empty($list)||!empty($_list)){
            AppCommonAction::ajax_encrypt($tendlist,1);
        }else{
            $tendlist['message']="暂无记录";
            AppCommonAction::ajax_encrypt($tendlist,0);
        }

    }
    //修改密码
    public function changepwd(){
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        //     $message['session_expired']=$this->$sessionExpired;
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        //alogsm("changepwd",0,1,$jsoncode);
        if (intval($arr['uid'])!=$this->uid){
            $message['message']="用户错误！";
            AppCommonAction::ajax_encrypt($message,0);
        }
        if (!is_array($arr)||empty($arr)||empty($arr['oldpwd'])||empty($arr['newpwd'])){
            $message['message']="数据错误！";
            AppCommonAction::ajax_encrypt($message,0);
        }
        $old = md5($arr['oldpwd']);
        $newpwd = md5($arr['newpwd']);
        $c = M('members')->where("id={$this->uid} AND user_pass = '{$old}'")->count('id');
        if($c==0){
            $message['message']="原密码错误";
            AppCommonAction::ajax_encrypt($message,0);
        }
        $newid = M('members')->where("id={$this->uid}")->setField('user_pass',$newpwd);
        if($newid){
            //MTip('chk1',$this->uid);
            $message['message']="密码修改成功";
            AppCommonAction::ajax_encrypt($message,1);
        }else{
            $message['message']="密码修改失败";
            AppCommonAction::ajax_encrypt($message,0);
        }
    }
    /**
     *显示绑定的银行卡信息
     *
     */
    public function obtain_bound_debit(){

        $bank = C('bank');

        $ids = M('members_status')->where('uid='.$this->uid)->find();
        if($ids['id_status']=='1'){
            $voinfo = M("member_info")->field('idcard,real_name')->find($this->uid);
            $vobank = M("member_banks")->field(true)->where("uid = {$this->uid} and bank_num !=''")->find();
            if(!$vobank){
                $msg['message']='您还未绑定银行卡，请先绑定银行卡';
                AppCommonAction::ajax_encrypt($msg,0);
            }

            $msg['real_name']       =$voinfo['real_name'];
            $msg['bank']            =$bank[$vobank['bank_name']];
            $msg['debit_id']        =hidecard($vobank['bank_num'],3);
            $msg['account_province']= M('cityinfo')->where("id={$vobank['bank_province']}")->getField('cityname');
            $msg['account_city']    = M('cityinfo')->where("id={$vobank['bank_city']}")->getField('cityname');
            $msg['account_branch']  =$vobank['bank_address'];

            $msg['can_modify']      =$this->glo['edit_bank'];
            
            AppCommonAction::ajax_encrypt($msg,1);
        }else{
            $msg['message']='您还未完成身份验证，请先进行实名认证';
            AppCommonAction::ajax_encrypt($msg,0);
        }
    }
    /**
     *绑定的银行卡信息
     *
     */
    public function bind_debitcard(){
        $jsoncode = file_get_contents("php://input");
        //   $msg['session_expired']=$this->$sessionExpired;
        $msg['real_name']=M("member_info")->getFieldByUid($this->uid,'real_name');
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        $bank_info        = M('member_banks')->field("uid, bank_num")->where("uid=".$this->uid)->find();
        !$bank_info['uid'] && $data['uid'] = $this->uid;
        $data['bank_num']      = text($arr['debit_id']);
        $data['bank_name']     = text($arr['bank']);
        $data['bank_address']  = text($arr['account_branch']);
        $data['bank_province'] = text($arr['account_province']);
        $data['bank_city']     = text($arr['account_city']);
        $data['add_ip']        = get_client_ip();
        $data['add_time']      = time();
        if($bank_info['uid']){
            /////////////////////新增银行卡修改锁定开关 开始 20130510 fans///////////////////////////
            if(intval($this->glo['edit_bank'])!= 1 && $bank_info['bank_num']){
                $msg['message']="为了您的帐户资金安全，银行卡已锁定，如需修改，请联系客服";
                AppCommonAction::ajax_encrypt($msg,0);
            }
            /////////////////////新增银行卡修改锁定开关 结束 20130510 fans///////////////////////////
            $old = text($arr['original_debit_id']);
            if($bank_info['bank_num'] && $old <> $bank_info['bank_num']){
                $msg['message']='原银卡号不对';
                AppCommonAction::ajax_encrypt($msg,0);
            }
            $newid = M('member_banks')->where("uid=".$this->uid)->save($data);
        }else{
            $newid = M('member_banks')->add($data);
        }
        if($newid){
            MTip('chk2',$this->uid);
            AppCommonAction::ajax_encrypt($msg,1);
        }else{
            $msg['message']='操作失败，请重试';
            AppCommonAction::ajax_encrypt($msg,0);
        }


    }
    public function  credit_list()
    {
        $jsoncode = file_get_contents("php://input");
        $arr = json_decode($jsoncode, true);
        $arr = AppCommonAction::get_decrypt_json($arr);

        $uid = $arr['uid'];
        if(!$this->uid || $uid != $this->uid){
            AppCommonAction::ajax_encrypt('登录信息有吴，请重新登录！',0);
        }
        $limit = intval($arr['limit'])? intval($arr['limit']): 5;
        $page = intval($arr['page'])? intval($arr['page']) :1;

        $data = $this->getBorrowRecord('1,2,3,4,5', $page, $limit);

        if(empty($data)){
            AppCommonAction::ajax_encrypt("暂时没有记录",0);
        }
        
        AppCommonAction::ajax_encrypt($data,1);
    }

    /**
     * 借款记录（带分页）
     *
     * @param mixed $borrow_type   // 借款类型
     * @param mixed $page         // 当前页数
     * @param mixed $limit       // 每页内容数
     * @return array
     */

    private function getBorrowRecord($borrow_type='1,2,3,4,5', $page = 1, $limit=5)
    {

        $_list = '';
        $_GET['p'] = intval($page);
        $map['borrow_type'] = array('in', $borrow_type);
        $map['borrow_uid'] = $this->uid;

        $Osql="borrow_status asc,id DESC";//id DESC,
        $field="id, borrow_name, borrow_status,  borrow_money, has_borrow, borrow_interest_rate, repayment_money, borrow_type, repayment_type, collect_day";
        import("ORG.Util.Page");
        $count = M('borrow_info')->where($map)->count('id');

        $totalPage = ceil($count/$limit);
        $p = new Page($count, $limit);
        $Lsql = "{$p->firstRow},{$p->listRows}";
        $data = M('borrow_info')
            ->field($field)
            ->where($map)
            ->order($Osql)
            ->limit($Lsql)
            ->select();
        foreach($data as $key=>$v){
            $_list[$key]['id'] = $v['id'];  // ID编号
            $_list[$key]['title'] = $v['borrow_name']; // 借款名称
            $_list[$key]['amount'] = $v['borrow_money']; // 借款金额
            $_list[$key]['repay_amount'] = $v['has_borrow']; // 已还金额
            $_list[$key]['status'] = $v['borrow_status']; // 借款状态
            $_list[$key]['interest_rate'] = $v['borrow_interest_rate']; // 借款利率
            $_list[$key]['repay_kind']  = $v['repayment_type'];// 还款方式
            $_list[$key]['repay_due_unit'] = $v['repayment_type']==1 ? 1: 0;
            $_list[$key]['kind'] = $v['borrow_type'];// 标类型

            $_list[$key]['repay_due_date']  = $v['collect_day'];// 还款时间
        }
        if($_list){
            $row=array();
            $row['list'] = $_list;
            $row['totalPage'] = $totalPage;
            $row['nowPage'] =  $page;
        }else{
            $row = '无记录';
        }
        return $row;
    }
    //提交借款申请
    public function request_credit(){//is_targeting":1,"kind":"1","repay_kind":1,"reward_kind":0,"borrow_info":"哈哈","interest_rate":"5","name":"简介","borrow_duration":1,"moneycollect":"88","amount":"100","is_moneycollect":true,"uid":115,"timestamp":1440747573.87177,"reward_num":"5","borrow_min":50,"targeting_pass":"88","borrow_time":1,"borrow_max":0,"borrow_use":1
		//{"uid":50,"kind":2,"interest_rate":"1","amount":"200","borrow_use":3,"borrow_duration":1,"borrow_min":"100元","borrow_max":"没有限制","borrow_time":3,"repay_kind":3,"is_targeting":0,"targeting_pass":"","reward_kind":0,"reward_num":"","is_moneycollect":0,"moneycollect":"","name":"258369","borrow_info":"369258"}
		
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
		/*$arr['uid'] = 50;
		$arr['kind'] = 2;
		$arr['interest_rate'] = 10;
		$arr['amount'] = '2000';
		$arr['borrow_use'] = 3;
		$arr['borrow_duration'] = 1;
		$arr['borrow_min'] = '100元';
		$arr['borrow_max'] = '没有限制';
		$arr['borrow_time'] = 3;
		$arr['repay_kind'] = 3;
		$arr['is_targeting'] = 0;
		$arr['targeting_pass'] = '';
		$arr['reward_kind'] = 0;
		$arr['reward_num'] = '';
		$arr['is_moneycollect'] = 0;
		$arr['moneycollect'] = '';
		$arr['name'] = '3432';
		$arr['borrow_info'] = 'borrow_info';
		$this->uid = 50;*/
        if (!intval($this->uid)){
            ajaxmsg("用户错误！",0);
        }
        $vo1 = M('members')->field('is_ban')->where("id = {$this->uid}")->find();
        if($vo1['is_ban'] == 1 || $vo1['is_ban'] == 2){ 
            ajaxmsg("您的帐户已被冻结，请联系客服处理！",0);
        }
        $vminfo = M('members')->field("user_leve, time_limit, is_borrow, is_vip")->find($this->uid);
        if($vminfo['is_vip'] == 0){
            $_xoc = M('borrow_info')->where("borrow_uid = {$this->uid} AND borrow_status in(0,2,4)")->count('id');
           /* if($_xoc>0){ 
                AppCommonAction::ajax_encrypt("您有一个借款中的标，请等待审核",0);
            }*/
            /*if(!($vminfo['user_leve'] > 0 && $vminfo['time_limit'] > time())){ 
                AppCommonAction::ajax_encrypt("请先通过VIP审核再发标",0);
            }*/
           /* if($vminfo['is_borrow'] == 0){ 
                ajaxmsg("您目前不允许发布借款，如需帮助，请与客服人员联系！",0);
            }*/
            //$vo = getMemberDetail($this->uid);
            /*if($vo['origin_place'] == 0 && $vo['address'] == 0){ 
                AppCommonAction::ajax_encrypt("请先填写个人详细资料后再发标",0);
            }*/
        }
        $pre = C('DB_PREFIX');
        //相关的判断参数
        $set = get_global_setting();
        $Bconfig = require(C("APP_ROOT")."Conf/borrow_config.php");
        $borrow_type = intval($arr['kind']);
        /*if (isset($Bconfig['BORROW_TYPE'][$borrow_type]) === false){ 
            AppCommonAction::ajax_encrypt("未知的借款类型：{$borrow_type}",0);
        }*/
        $rate_lixt = explode("|", $set['rate_lixi']);
        $borrow_duration = explode("|", $set['borrow_duration']);
        $borrow_duration_day = explode("|", $set['borrow_duration_day']);
        $fee_borrow_manage = explode("|", $set['fee_borrow_manage']);
        $vminfo = M('members m')->join("{$pre}member_info mf ON m.id=mf.uid")->field("m.user_leve,m.time_limit,mf.province_now,mf.city_now,mf.area_now")->where("m.id={$this->uid}")->find();
        //相关的判断参数
        $borrow['borrow_type'] = $borrow_type;
        $borrow['borrow_interest_rate'] = intval($arr['interest_rate']);
		
        if(floatval($borrow['borrow_interest_rate']) > $rate_lixt[1] || floatval($borrow['borrow_interest_rate']) < $rate_lixt[0]){ 
            ajaxmsg("提交的借款利率超出允许范围，请重试",0);
        }
        $borrow['borrow_money'] = floatval($arr['amount']);
        $_minfo = getMinfo($this->uid,true);
        $_capitalinfo = getMemberBorrowScan($this->uid);
        ///////////////////////////////////////////////////////
        $borrowNum = M('borrow_info')->field("borrow_type,count(id) as num,sum(borrow_money) as money,sum(repayment_money) as repayment_money")->where("borrow_uid = {$this->uid} AND borrow_status=6 ")->group("borrow_type")->select();
        $borrowDe = array();
		if(intval($arr['amount']) % intval($arr['borrow_min']) > 0){
			ajaxmsg('必须是起投金额的整数倍!',0);
		}
        foreach ($borrowNum as $k => $v) {
            $borrowDe[$v['borrow_type']] = $v['money'] - $v['repayment_money'];
        }
        ///////////////////////////////////////////////////
        switch($borrow['borrow_type']){
            case 1://普通标
                if($_minfo['credit_limit']<$borrow['borrow_money']){ 
                    ajaxmsg("您的可用信用额度为{$_minfo['credit_limit']}元，小于您准备借款的金额，不能发标",0);
                }
                break;
            case 2://新担保标
            case 3://秒还标
                break;
            case 4://净值标
                $_netMoney = getFloatValue(0.9 * $_minfo['money_collect'] - $borrowDe[4], 2);
                if($_netMoney < $borrow['borrow_money']){ 
                    ajaxmsg("您的净值额度{$_netMoney}元，小于您准备借款的金额，不能发标",0);
                }
                break;
            case 5://抵押标
                break;
        }
		if( $arr['repay_kind'] == 1 ) {
            $borrow['duration_unit'] = BorrowModel::BID_CONFIG_DURATION_UNIT_DAY;
        } else {
            $borrow['duration_unit'] = BorrowModel::BID_CONFIG_DURATION_UNIT_MONTH;
        }
        $borrow['borrow_uid'] = $this->uid;
        $borrow['borrow_name'] = text($arr['name']);
        $borrow['borrow_duration'] = ($borrow['borrow_type'] == 3) ? 1 : intval($arr['borrow_duration']);//秒标固定为一月
        $borrow['borrow_interest_rate'] = doubleval($arr['interest_rate']);
        if(strtolower($arr['repay_kind'])==1) $borrow['repayment_type'] = 1;
		elseif($borrow['borrow_type']==3) $borrow['repayment_type'] = 2;//秒标按月还
		else $borrow['repayment_type'] = intval($arr['repay_kind']);

		// 验证期限是否在有效期内
        if( !GlobalModel::validate_bid_duration($borrow['repayment_type'], $borrow['borrow_duration']) ) {
            ajaxmsg("请检查借款期限是否正确",0);
        }
        
        $borrow['borrow_status'] = 0;
        $borrow['borrow_use'] = 1;//缺省
        $borrow['add_time'] = time();
        $borrow['collect_day'] = $arr['borrow_time'];//缺省
        $borrow['add_ip'] = get_client_ip();
        $borrow['borrow_info'] = text($arr['borrow_info']);//缺省
        $borrow['reward_type'] = intval($arr['reward_num'])>0? 1:0;
        $borrow['reward_num'] = intval($arr['reward_num']);
        $borrow['borrow_min'] = 50;//缺省
        $borrow['borrow_max'] = 0;//缺省
		$borrow['has_borrow'] = 0;//缺省
		$borrow['repayment_money'] = 0;//缺省
		$borrow['rate_type'] = BorrowModel::BID_CONFIG_RATE_TYPE_FULL_BORROW; // 满标计息
        if (intval($arr['is_targeting']) === 1){ $borrow['password'] = md5(text($arr['targeting_pass'])); }
        $borrow['money_collect'] = floatval($arr['moneycollect']);//代收金额限制设置,缺省
		
        //借款费和利息
		//print_r(getBorrowInterest($borrow['repayment_type'],$borrow['borrow_money'],$borrow['borrow_duration'],$borrow['borrow_interest_rate'], true, true));exit;
		$borrow['borrow_interest'] = getBorrowInterest($borrow['repayment_type'],$borrow['borrow_money'],$borrow['borrow_duration'],$borrow['borrow_interest_rate'], true, true);
        if($borrow['repayment_type']=='1' || $borrow['repayment_type']=='5'){
            $borrow['total'] = 1;
        }elseif($borrow['repayment_type']== 4) { // 如果为按天计息，按月付息，到期还本，所还期数未必等于月数
            $borrowScan = EqualEndMonth(array(
                'duration' => $borrow['borrow_duration'],
                'account' => $borrow['borrow_money'],
                'year_apr' => $borrow['borrow_interest_rate']
            ));
            $borrow['total'] = count($borrowScan);
        }
        else{
            $borrow['total'] = $borrow['borrow_duration'];//分几期还款
        }
        // 借款管理费
        $borrow['borrow_fee'] = BorrowModel::get_fee_borrow_manage($borrow['borrow_duration'], $borrow['borrow_money'], $borrow['duration_unit']);
		
		if($borrow['borrow_type']==3){//秒还标
			if($borrow['reward_type']>0){
				$_reward_money = getFloatValue($borrow['borrow_money']*$borrow['reward_num']/100,2);
			}
			$_reward_money =floatval($_reward_money);
			if(($_minfo['account_money']+$_minfo['back_money'])<($borrow['borrow_fee']+$_reward_money)) AppCommonAction::ajax_encrypt("发布此标您最少需保证您的帐户余额大于等于".($borrow['borrow_fee']+$_reward_money)."元，以确保可以支付借款管理费和投标奖励费用",0);
		}
        $newid = M("borrow_info")->add($borrow);
		//print_r(M()->getlastsql());exit;
        if ($newid > 0){
            $suo=array();
            $suo['id'] = $newid;
            $suo['suo'] = 0;
            ajaxmsg("借款发布成功，网站会尽快初审",1);
        }
        ajaxmsg("发布失败，请先检查是否完成了个人详细资料然后重试",0);
    }
    //`mxl:credit`
    /*
     *#31 API .上传实名认证照片,身份证号,姓名
     *参考文档 服务器与客户端协议v20140912.docx
     *14-09-15 元
     */
    public function verify_personalid(){
        //     $msg['session_expired']=$this->$sessionExpired;
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        //$arr = AppCommonAction::get_decrypt_json($arr);
        //判断数据合法
		$glo = get_bconf_setting();
		if(intval($glo['is_card'])==1){  //是否开启身份证图片上传 
			if(empty($arr['personal_id_photo']))  {
				$msg['message']="请先上传身份证正面图片";
				AppCommonAction::ajax_encrypt($msg,0);
			}
			if(empty($arr['personal_id_photo2'])) {
				$msg['message']="请先上传身份证反面图片";
				AppCommonAction::ajax_encrypt($msg,0);
			}
		}
        if(empty($arr['personal_id'])||empty($arr['real_name']))  {
            $msg['message']="请填写真实姓名和身份证号码";
            AppCommonAction::ajax_encrypt($msg,0);
        }


        //判断身份证号是否合法 start 2015-01-19
        if(!preg_match("/^(\d{18,18}|\d{15,15}|\d{17,17}x)$/",$arr['personal_id'])){
            $msg['message']="输入的身份证不合法";
            AppCommonAction::ajax_encrypt($msg,0);
        }

        if (!preg_match('/^[\x{4e00}-\x{9fa5}]+$/u',$arr['real_name'])) {
            $msg['message']="真实姓名不合法";
            AppCommonAction::ajax_encrypt($msg,0);
        }




        //判断身份证号是否合法 end  2015-01-19


        $xuid = M('member_info')->getFieldByIdcard($arr['personal_id'],'uid');
        if($xuid>0 && $xuid!=$this->uid) {
            $msg['message']="此身份证号码已被人使用";
            AppCommonAction::ajax_encrypt($msg,0);
        }
		if(intval($glo['is_card'])==1){ 
			//身份证照片转存
			$personal_id_photo=stripslashes($arr['personal_id_photo']);
			$personal_id_photo=base64_decode($personal_id_photo);
			$personal_id_photo_name = 'UF/Uploads/Idcard/'.date("YmdHis",time()).rand(0,1000)."_{$this->uid}.png";
			$res=file_put_contents($personal_id_photo_name,$personal_id_photo);

			$personal_id_photo2=stripslashes($arr['personal_id_photo2']);
			$personal_id_photo2=base64_decode($personal_id_photo2);
			$personal_id_photo2_name = 'UF/Uploads/Idcard/'.date("YmdHis",time()).rand(0,1000)."s_{$this->uid}_back.png";
			$res2=file_put_contents($personal_id_photo2_name,$personal_id_photo2);
		   /* if($res=='0' || $res2=='0'){ 
				$msg['message']="身份证上传失败，请重试！";
				AppCommonAction::ajax_encrypt($msg,0);
			}*/
			if($res>0 && $res2>0){
				$c = M('member_info')->where("uid = {$this->uid}")->count('uid');
				if($c==1){
					$data['card_img'] = $personal_id_photo_name;
					$data['card_back_img'] = $personal_id_photo2_name;
				}else{
					$data['uid'] = $this->uid;
					$data['card_img'] = $personal_id_photo_name;
					$data['card_back_img'] = $personal_id_photo2_name;
					$newid = M('member_info')->add($data);
				}
			}
		}
        $data['real_name'] =   text($arr['real_name']);
        $data['idcard']    =   text($arr['personal_id']);
        $data['up_time']   =   time();

        $data1['idcard']  = text($arr['personal_id']);
        $data1['up_time'] = time();
        $data1['uid']     = $this->uid;
        $data1['status']  = 0;
        $b = M('name_apply')->where("uid = {$this->uid}")->count('uid');
        if($b==1){
            M('name_apply')->where("uid ={$this->uid}")->save($data1);
        }else{
            M('name_apply')->add($data1);
        }
        $c = M('member_info')->where("uid = {$this->uid}")->count('uid');
        if($c==1){
            $newid = M('member_info')->where("uid = {$this->uid}")->save($data);
        }else{
            $data['uid'] = $this->uid;
            $newid = M('member_info')->add($data);
        }
        if($newid){
            $ms=M('members_status')->where("uid={$this->uid}")->setField('id_status',3);
            if($ms==1){
                $msg['message']="您已提交身份验证！";
                AppCommonAction::ajax_encrypt($msg,1);
            }else{
                $dt['uid'] = $this->uid;
                $dt['id_status'] = 3;
                M('members_status')->add($dt);
                $msg['message']="您已提交身份验证！";
                AppCommonAction::ajax_encrypt($msg,1);

            }
            // ajaxmsg("保存失败，请重试",0);
        }else {
            $msg['message']="保存失败，请重试";
            AppCommonAction::ajax_encrypt($msg,0);
        }
    }

    /*
     *#30 API 提交用于验证的手机号
     *参考文档 服务器与客户端协议v20140912.docx
     *14-09-13 元
     */
    public function verifyphone(){
        $jsoncode = file_get_contents("php://input");
        //   $msg['session_expired']=$this->$sessionExpired;
        $arr = array();

        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);

        $uid = $arr['uid'];
        $phone =  $arr['phone'];
        $verify_code = $arr['verify_code'];
		$paypass = $arr['paypass'];
        if($uid != $this->uid) {
            AppCommonAction::ajax_encrypt('登陆信息有误，请重新登陆',0);
        }
		$mimas = M('members')->where("id={$this->uid}")->find();
		
		if(!empty($paypass)){
			if(empty($mimas['pin_pass'])){
				$msg['message']='您还未设置支付密码!';
				AppCommonAction::ajax_encrypt($msg,0);
			}elseif($mimas['pin_pass'] != md5($paypass)){
				$msg['message']='支付密码不正确!';
				AppCommonAction::ajax_encrypt($msg,0);
			}
		}
        $msg['message'] = "提交失败,请重试！";
        if(!is_array($arr) || empty($arr)) {
            AppCommonAction::ajax_encrypt($msg,0);
        }
        $phonestatus = M('members_status')->getFieldByUid($this->uid,'phone_status');
        /*$msg['message'] = "手机已验证,请到网站进行更改";
        if($phonestatus==1) {
            AppCommonAction::ajax_encrypt($msg,0);
        }*/

        $datag = get_global_setting();
        $is_manual=$datag['is_manual'];
        if($is_manual){ // 手动验证

            $updata['phone_status'] = 3;//待审核

            $updata1['user_phone'] = $phone;
            $a = M('members')->where("id = {$this->uid}")->count('id');
            if($a==1){
                $newid = M("members")->where("id={$this->uid}")->save($updata1);
            }else{
                M('members')->where("id={$this->uid}")->setField('user_phone',$phone);
            }

            $updata2['cell_phone'] = $phone;
            $b = M('member_info')->where("uid = {$this->uid}")->count('uid');
            if($b==1){
                $newid = M("member_info")->where("uid={$this->uid}")->save($updata2);
            }else{
                $updata2['uid'] = $this->uid;
                M('member_info')->add($updata2);
            }
            $c = M('members_status')->where("uid = {$this->uid}")->count('uid');
            if($c==1){
                $newid = M("members_status")->where("uid={$this->uid}")->save($updata);
            }else{
                $updata['uid'] = $this->uid;
                $newid = M('members_status')->add($updata);
            }
            if($newid){
                $msg['message']="提交成功，等待管理员审核";
                AppCommonAction::ajax_encrypt($msg,1);
            }else{
                $msg['message']="验证失败";
                AppCommonAction::ajax_encrypt($msg,0);
            }

        }else{
            //if(md5($phone.$verify_code) == session("temp_phone")){
                session('temp_phone', null);

                $updata['user_phone'] = $phone;
                $newid = M("members")->where("id={$this->uid}")->save($updata);

                $updata2['uid'] = $this->uid;
                $updata2['cell_phone'] = $phone;
                $b = M('member_info')->where("uid = {$this->uid}")->count('uid');
                if($b){
                    $newid = M("member_info")->save($updata2);
                }else{
                    M('member_info')->add($updata2);
                }
                $newid = setMemberStatus($this->uid, 'phone', 1, 10, '手机');
                if($newid){
                    $msg['message']="绑定成功！";
                    AppCommonAction::ajax_encrypt($msg,1);
                }else{
                    $msg['message']="手机修改成功！";
                    AppCommonAction::ajax_encrypt($msg,1);
                }


            /*}else{
                AppCommonAction::ajax_encrypt('验证校验码不对，请重新输入！',0);
            }*/

        }
        AppCommonAction::ajax_encrypt('参数错误，请重新输入！',0);


    }

    /*
     *#29 API 获取手机号验证码
     *参考文档 服务器与客户端协议v20140912.docx
     *14-09-12 元
     */
    public function commitphone(){

        //  $msg['session_expired']=$this->$sessionExpired;
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        $msg['message']="提交失败,请重试！";
        if(!is_array($arr) || empty($arr)){
            AppCommonAction::ajax_encrypt($msg,0);
        }

        $phone = text($arr['phone']);
        $uid= intval($arr['uid']);
        $xuid = M('members')->getFieldByUserPhone($phone,'id');
        $msg['message']="手机号已被使用";
        if($xuid){ 
            AppCommonAction::ajax_encrypt($msg,0);
        }

        $smsTxt = FS("Webconfig/smstxt");
        $smsTxt=de_xie($smsTxt);
        $code = rand_string($this->uid,6,1,2);
        $res = sendsms($phone,str_replace(array("#UserName#","#CODE#"),array(session('u_user_name'),$code),$smsTxt['verify_phone']));
        if($res){
            session("temp_phone",md5($phone.$code));
            $msg['message']="发送验证码成功";
            AppCommonAction::ajax_encrypt($msg,1);
        }else{
            $msg['message']="发送验证码失败";
            AppCommonAction::ajax_encrypt($msg,0);
        }

    }

    /*
    *#32 API 找回支付密码
    *参考文档 服务器与客户端协议v20140912.docx
    *14-09-15 元
    */
    public function recover_pay_passwd(){
        // $msg['session_expired']=$this->$sessionExpired;
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        // $arr['uid']=15;
        // $arr['userinfo']="aa";
        $userinfo=text($arr['userinfo']);
        $c = M('members')->where("id={$this->uid}")->find();
        if(empty($c['pin_pass'])){
            $msg['message']='您还没有设置支付密码';
            AppCommonAction::ajax_encrypt($msg,0);
        }
        if(empty($userinfo)){
            $msg['message']='请填入正确的邮箱或账户实名';
            AppCommonAction::ajax_encrypt($msg,0);
        }
        $email=$c['user_email'];
        $name=M('member_info')->where("uid={$this->uid}")->getField('real_name');

        if($userinfo==$email || $userinfo==$name){
            $r = Notice(10,$this->uid);
            if($r) {
                $msg['message']='发送找回邮件成功';
                AppCommonAction::ajax_encrypt($msg,1);
            }else{
                $msg['message']='发送找回邮件失败';
                AppCommonAction::ajax_encrypt($msg,0);
            }
        }else{
            $msg['message']='请填入正确的邮箱或账户实名';
            AppCommonAction::ajax_encrypt($msg,0);
        }
    }
    /*
     *#32 API 找回密码
     *参考文档 服务器与客户端协议v20140912.docx
     *14-09-15 元
     */
    public function recover_passwd(){
        //   $msg['session_expired']=1;
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        $user_name=text($arr['userinfo']);
        // $arr['uid']=15;
        // $arr['userinfo']="aa";
        // $userinfo=text($arr['userinfo']);
        $c = M('members')->where("user_name='{$user_name}'")->find();
        if(empty($c)){
            $msg['message']='输入信息有误，请重试！';
            AppCommonAction::ajax_encrypt($msg,0);
        }
        if(!empty($c['user_email'])){
            $r = Notice(7,$c['id']);
            if($r) {
                $msg['message']='发送找回密码邮件成功';
                AppCommonAction::ajax_encrypt($msg,1);
            }else{
                $msg['message']='发送找回密码邮件失败';
                AppCommonAction::ajax_encrypt($msg,0);
            }
        }else{
            $msg['message']='您还没有绑定邮箱';
            AppCommonAction::ajax_encrypt($msg,0);
        }
    }
    /*
     *#33 API 修改支付密码
     *参考文档 服务器与客户端协议v20140912.docx
     *14-09-15 元
     */
    public function change_pay_passwd(){
        //    $msg['session_expired']=$this->$sessionExpired;
        $jsoncode = file_get_contents("php://input");

        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        $old =    md5($arr['oldpwd']);
        $newpwd = md5($arr['newpwd']);
        if($old==''||$newpwd==''){
            AppCommonAction::ajax_encrypt("密码不能为空",0);
        }
        $c = M('members')->where("id={$this->uid}")->find();
        if($old==$newpwd){
            $msg['message']="设置失败，请勿让新密码与老密码相同。";
            AppCommonAction::ajax_encrypt($msg,0);
        }
        if(empty($c['pin_pass'])){
            if($c['user_pass'] == $old){
                $newid = M('members')->where("id={$this->uid}")->setField('pin_pass',$newpwd);
                if($newid){
                    $msg['message']="设置支付密码成功";
                    AppCommonAction::ajax_encrypt($msg,1);
                }else{
                    $msg['message']="设置失败，请重试";
                    AppCommonAction::ajax_encrypt($msg,0);
                }
            }else{
                $msg['message']="原支付密码(即登陆密码)错误，请重试";
                AppCommonAction::ajax_encrypt($msg,0);

            }
        }else{
            if($c['pin_pass'] == $old){
                $newid = M('members')->where("id={$this->uid}")->setField('pin_pass',$newpwd);
                if($newid){
                    $msg['message']="修改支付密码成功";
                    AppCommonAction::ajax_encrypt($msg,1);
                }else{
                    $msg['message']="设置失败，请重试";
                    AppCommonAction::ajax_encrypt($msg,0);
                }
            }else{
                $msg['message']="原支付密码错误，请重试";
                AppCommonAction::ajax_encrypt($msg,0);
            }
        }
    }

    public function verifiEmail()
    {
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        if (intval($arr['uid'])!=$this->uid){
            AppCommonAction::ajax_encrypt("用户错误！",0);
        }
        $uid = $arr['uid'];
        $email = $arr['email'];
        $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
        if(!preg_match( $pattern, $email )){
            AppCommonAction::ajax_encrypt('邮箱地址格式不正确！',0);
        }
        $email_res = M('members')->where("user_email='{$email}' and id <> {$uid}")->count('id');
        if($email_res){
            AppCommonAction::ajax_encrypt('此邮箱已存在，请更换！',0);
        }

        $up_id = M('members')->save(array('id'=>$uid, 'user_email'=>$email));

        if(!$up_id){
            $email_res = M('members')->where("user_email='{$email}' and id = {$uid}")->count('id');
            if(!$email_res){
                AppCommonAction::ajax_encrypt('更新失败！',0);
            }
        }
        $status = Notice(8,$uid);
        if($status) {
            AppCommonAction::ajax_encrypt('邮件已发送，请注意查收！',1);
        }
        else {
            AppCommonAction::ajax_encrypt('邮件发送失败,请重试！',0);
        }
    }

    public function bankInfo()
    {

        $bank = $this->gloconf['BANK_NAME'];

        $i=0;
        foreach($bank as $key=>$v){
            $data['bank'][$i]['id'] = $key;
            $data['bank'][$i]['value'] = $v;
            $i++;
        }
        $province = M('area')->field('id, name as value')->where("reid=1")->select();
        foreach($province as $key=>$v){
            $data['province'][$key]['id'] = strval($v['id']);
            $data['province'][$key]['value'] = $v['value'];

        }
        AppCommonAction::ajax_encrypt($data,1);

    }

    public function getCity()
    {
        $jsoncode = file_get_contents("php://input");
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        $pid = intval($arr['id']);

        if(!$pid){
            AppCommonAction::ajax_encrypt('参数错误',0);
        }

        $city = M('area')->field('id, name as value')->where("reid={$pid}")->select();
        foreach($city as $key=>$v){
            $data['city'][$key]['id'] = strval($v['id']);
            $data['city'][$key]['value'] = $v['value'];
        }
        AppCommonAction::ajax_encrypt($data,1);
    }
    //会员头像上传API 2015-05-04
    public function upload_photo(){
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);

        if(empty($arr['uid']) || empty($this->uid)){
            AppCommonAction::ajax_encrypt('请先登录！',0);
        }
        $uid = $arr['uid'];
        if(empty($arr['photo']))  {
            $msg['message']="请上传头像图片！";
            AppCommonAction::ajax_encrypt($msg,0);
        }

        $photo=stripslashes($arr['photo']);
        $photo=base64_decode($photo);
        //ajaxmsg($photo,0);
        $personal_id_photo_name = 'Style/header/customavatars/000/00/00/'.substr($uid, -2).'_avatar_middle.jpg';
        //ajaxmsg($personal_id_photo_name,0);
        $res2=file_put_contents($personal_id_photo_name,$photo);
        if($res2>0){
            AppCommonAction::ajax_encrypt('上传头像成功！',0);
        }else{
            AppCommonAction::ajax_encrypt('上传头像失败！',0);
        }
    }

    /**
     * 优惠券查询
     */
    public function get_coupon() {
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        //$arr = AppCommonAction::get_decrypt_json($arr);
        if(empty($arr['uid']) || empty($this->uid) || $arr['uid'] != $this->uid){
            ajaxmsg('请先登录！',0);
        }
        $time = time();
        $condition = "uid = ".$this->uid;
        $status = isset($arr['status'])? intval($arr['status']):1;
        $order_num = isset($arr['order'])? intval($arr['order']):7;

        /*$status==1 && $condition .= " and status=1 and expired_time > ".$time ;  // 未使用的
        $status==4 && $condition .= " and status=4 " ;  // 已使用
        $status==3 && $condition .= " and status=1 and expired_time < ".$time ;  // 已过期*/


        $order = ' add_time desc,status asc';

        /*$order_num == 3 && $order = " expired_time asc " ;
        $order_num == 4 && $order = " expired_time desc " ;
        $order_num == 5 && $order = " money asc " ;
        $order_num == 6 && $order = " money desc " ;
        $order_num == 7 && $order = " add_time asc " ;
        $order_num == 8 && $order = " add_time desc " ;*/

        import('ORG.Util.Page');
        $page = intval($arr['page'])? intval($arr['page']):1;
        $limit = intval($arr['limit'])? intval($arr['limit']):5;
		$exp_type = C('EXP_TYPE'); //优惠券类型
        $_GET['p'] = $page;
        $count      = M('expand_money')->where($condition)->count();
        $totalPage = ceil($count/$limit);
        $Page       = new Page($count,$limit);
        $expand_list = M('expand_money')
            ->field('money, invest_money, status, expired_time, type, use_time, remark, is_taste')
            ->where($condition)
            ->limit($Page->firstRow.','.$Page->listRows)
            ->order($order)
            ->select();
		$expand_list = ExpandMoneyModel::get_coupon_type_format($expand_list);
		$_list = array();
            foreach($expand_list as $k=>$v){
				$_list[$k]['money'] = $v['money'];   //优惠卷金额
				$_list[$k]['invest_money'] = $v['invest_money'];  // 每多少金额
				$_list[$k]['funds'] = date('Y-m-d',$v['expired_time']);  //过期时间
				$_list[$k]['exp_type'] = $exp_type[$v['type']];  ///来源
				$_list[$k]['coupon_type'] = $v['coupon_type'];  ///卷类型
				if($v['status']==1 and $v['expired_time']>time()){
					$_list[$k]['status'] = 0;  ///未使用的
				}elseif($v['status']==4){
					$_list[$k]['status'] = 1;  ///已使用
				}elseif($v['status']==1 and $v['expired_time']<time()){
					$_list[$k]['status'] = 2;  ///已过期
				}
				//$_list[$k]['type'] = $v['is_taste']==1? '仅用于投资,不可提现,利息可提现,债权转让不可使用':'仅用于投资,债权转让不可使用';//提示信息
                
            }
        
        //$data['coupon_status'] =$status;
		$n_num = M('expand_money')->where("status=1 and expired_time> ".time()." and uid=".$this->uid)->count('id');  
        $data['n_num'] = floatval($n_num);  ////统计未使用优惠券  
        
        $n_money = M('expand_money')->where("status=1 and expired_time> ".time()." and uid=".$this->uid)->sum('money');  
        $data['n_money'] = floatval($n_money);  //统计已过期优惠券金额 
        
        $y_money = M('expand_money')->where("status=4  and uid=".$this->uid)->sum('money');  
        $data['y_money'] = floatval($y_money);  //统计未使用优惠券总额 
        
        $ex_money = M('expand_money')->where("status=1 and expired_time < ".time()." and uid=".$this->uid)->sum('money');  
        $data['ex_money'] = ($ex_money=='')? 0:floatval($ex_money);     //统计已过期优惠券金额
        $data['list'] = $_list;
        $data['totalPage'] = $totalPage;
        $data['nowPage'] =  $page;
        ajaxmsg($data,1);

    }
	/**
    * 优惠券奖励记录
    * @author zhang ji li  2015-03-13
    */
    public function expLog()
    {
		$jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        //$arr = AppCommonAction::get_decrypt_json($arr);
		$page = intval($arr['page'])? intval($arr['page']):1;
        $limit = intval($arr['limit'])? intval($arr['limit']):5;
        if(empty($arr['uid']) || empty($this->uid) || $arr['uid'] != $this->uid){
            ajaxmsg('请先登录！',0);
        }
		$_GET['p'] = $page;
        $condition .= " uid={$this->uid}";
        import("ORG.Util.Page");
        $count = M('expand_money')
                ->where($condition)
                ->count('id');
		$totalPage = ceil($count/$limit);
        $p = new Page($count, $limit);
        //$page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
            
        
         $list = M('expand_money')
                        ->field(true)
                        ->where($condition)
						->limit($Lsql)
                        ->order("add_time desc ")
                        ->select();  
		 $_list = array();
		 $exp_type = C('EXP_TYPE');
            foreach($list as $k=>$v){
				$_list[$k]['add_time'] = date('Y-m-d',$v['add_time']);//时间
				$_list[$k]['exp_type'] = $exp_type[$v['type']];//类型
				$_list[$k]['remark'] = $v['remark'];//获得详情
				$_list[$k]['money'] = $v['money'];  //奖励金额
				if($v['status'] == 1 and $v['expired_time'] > time()){
					$_list[$k]['status'] = 0;  //未使用
				}elseif($v['status'] == 4){
					$_list[$k]['status'] = 1;  //已使用
				}else{
					$_list[$k]['status'] = 2;  //已过期
				}
                
            }
            
           if(is_array($_list)){
			   $n_num = M('expand_money')->where("status=1 and expired_time> ".time()." and uid=".$this->uid)->count('id');  //统计未使用优惠券  
				$data['n_num'] = floatval($n_num);  ////统计未使用优惠券  
				
				$n_money = M('expand_money')->where("status=1 and expired_time> ".time()." and uid=".$this->uid)->sum('money');  //统计未使用优惠券金额  
				$data['n_money'] = floatval($n_money);  //统计未使用优惠券金额 
				
				$y_money = M('expand_money')->where("status=4  and uid=".$this->uid)->sum('money');  //统计已经使用优惠券金额  
				$data['y_money'] = floatval($y_money);  //统计已经使用优惠券金额 
				
				$ex_money = M('expand_money')->where("status=1 and expired_time < ".time()." and uid=".$this->uid)->sum('money');  //统计已过期优惠券金额  
				$data['ex_money'] = $ex_money==''? 0:floatval($ex_money);     //统计已过期优惠券金额
				$data['list'] = $_list;
				$data['totalPage'] = $totalPage;
				$data['nowPage'] =  $page;
			}else{
				$data['message'] = '暂无项目记录';
				ajaxmsg($data,0);
			}

            ajaxmsg($data,1);
         
         
            
        //$this->display();    
    }
    /**
     * 信用额度申请
     */
    public function credit_apply(){
        $jsoncode = file_get_contents("php://input");
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        if(empty($arr['uid']) || empty($this->uid) || $arr['uid'] != $this->uid){
            AppCommonAction::ajax_encrypt('请先登录！',0);
        }
        $xtime = strtotime("-1 month");
        $vo = M('member_apply')->field('apply_status')->where("uid={$this->uid}")->order("id DESC")->find();
        $xcount = M('member_apply')->field('add_time')->where("uid={$this->uid} AND add_time>{$xtime}")->order("id DESC")->find();
        if(is_array($vo) && $vo['apply_status']==0){
            $xs = "是您的申请正在审核，请等待此次审核结束再提交新的申请";
            AppCommonAction::ajax_encrypt($xs,0);
        }elseif(is_array($xcount)){
            $timex = date("Y-m-d",$xcount['add_time']);
            $xs = "一个月内只能进行一次额度申请，您已在{$timex}申请过了，如急需额度，请直接联系客服";
            AppCommonAction::ajax_encrypt($xs,0);
        }else{
            $apply['uid'] = $this->uid;
            $apply['apply_type'] = 1;
            $apply['apply_money'] = floatval($arr['apply_money']);
            $apply['apply_info'] = text($arr['apply_info']);
            $apply['add_time'] = time();
            $apply['apply_status'] = 0;
            $apply['add_ip'] = get_client_ip();
            $nid = M('member_apply')->add($apply);
        }
        if($nid) {
            AppCommonAction::ajax_encrypt('申请已提交，请等待审核',1);
        }else {
            AppCommonAction::ajax_encrypt('申请提交失败，请重试',0);
        }
    }
    /**
     * 申请借款人
     */
    public function borrow_apply(){
        $jsoncode = file_get_contents("php://input");
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        
        if(empty($arr['uid']) || empty($this->uid)){
            AppCommonAction::ajax_encrypt('请先登录！',0);
        }
        $url = "http://" . $_SERVER['HTTP_HOST'] ."/borrow/index.html";
        AppCommonAction::ajax_encrypt($url,1);
    }
    
    /**
     * 邀请链接
     */
    public function invite_link(){
        $jsoncode = file_get_contents("php://input");
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        
        if(empty($arr['uid']) || empty($this->uid)){
            AppCommonAction::ajax_encrypt('请先登录！',0);
        }
        $expconf = FS("Webconfig/expconf");
        
        $type_conf = $expconf[1];
        if($type_conf['num']){
            $money = "注册就送".$type_conf['money']."元！";
        }else{
            $money = '';
        }
        
        $uid = MembersModel::get_user_Encryption($arr['uid']);
        $data['url'] = "http://" . $_SERVER['HTTP_HOST'] . '/i/'. $uid;
		$data['message'] = "100元做投资人，{$money}10-15%年化收益，网上理财赚翻天！从此告别死工资，速速注册吧！";
        
        AppCommonAction::ajax_encrypt($data,1);
    }
	//邀请列表
	public function invite_index(){
        $jsoncode = file_get_contents("php://input");
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        if(empty($this->uid)){
            AppCommonAction::ajax_encrypt('请先登录！',0);
        }
		$page = intval($arr['page'])? intval($arr['page']):1;
		$limit = intval($arr['limit'])? intval($arr['limit']):5;
		$_GET['p'] = $page;
        $User = M('members'); 
        import('ORG.Util.Page');
        $count      = $User->where("recommend_id = ".$this->uid)->count();
        $p = new Page($count, $limit);
		$totalPage = ceil($count/$limit);
		$Lsql = "{$p->firstRow},{$p->listRows}";
        $user_list = $User->field("id, user_name, reg_time")->where("recommend_id = ".$this->uid)->limit($Lsql)->select();
        
        if(count($user_list)){
            foreach($user_list as $key=>$val){
                $exp_money = M('expand_money')->where("source_uid={$val['id']}")->getField('money'); //已经赠送了

                if(empty($exp_money)){
                    $user_list[$key]['be'] = 0; 
                    $user_list[$key]['money'] = $expconf[4]['money'];      
                }else{
                    $user_list[$key]['be'] = 1;
                    $user_list[$key]['money'] = $exp_money;   
                }
                
            }    
        }
		$_list = array();
			foreach($user_list as $k=>$value){
				$_list[$k]['user_name'] = $value['user_name']; //邀请用户
				$_list[$k]['reg_time'] = date('Y-m-d',$value['reg_time']); //用户注册时间
				$_list[$k]['yizhuce'] = '已注册'; //用户状态
				$_list[$k]['money'] = floatval($value['money']).'元'; //邀请奖励
				$_list[$k]['be'] = $value['be'] > 0? '已生效':'未生效'; //是否生效
			}
			
			if(is_array($_list)){
				$data['list'] = $_list;
				$data['totalPage'] = $totalPage;
				$data['nowPage'] =  $page;

			}else{
				$data = '暂无相关数据！';
				AppCommonAction::ajax_encrypt($data,0);
			}
		$expconf = FS("Webconfig/expconf");
        $yq = $expconf[4];
		$data['money'] = $yq['num']*$yq['money'];
        AppCommonAction::ajax_encrypt($data,1);
    }
/** **************************************************************
 * 
 * yeebao @author Administrator
 * 
/****************************************************************/
    /**
     * yeepay  注册
     * @param int uid;
     */
    public function ybregister(){
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);

        if(empty($arr['uid']) || empty($this->uid)){
            AppCommonAction::ajax_encrypt('请先登录！',0);
        }
        //检测
        $this->ckstatus($this->uid);
        //查询
        import("ORG.Loan.Escrow");
        $loan = new Escrow();
        $pre = C('DB_PREFIX');
        $vo = M("members m")
            ->field("m.id,m.is_transfer,m.user_name,m.user_email,m.user_phone,mi.idcard,mi.real_name")
            ->join("{$pre}member_info mi ON mi.uid=m.id")
            ->where("m.id={$this->uid}")
            ->find();
        //身份验证
        if ($vo['is_transfer']==1) {
            AppCommonAction::ajax_encrypt('非个人用户绑定托管请到网页！',0);
        }
        $record = M("escrow_account")->field('*')->where("uid={$this->uid}")->find();
        //首次注册
        if(!$record){
            $user_data = array(
                'uid' => $this->uid,
                'platform' =>date('YmdHis').$this->uid,
                'type' => $vo['is_transfer'],
                'orders'=>build_order_no(),
                'add_time' => time(),
            );
            $res = M('escrow_account')->add($user_data);
            if ($res) {
                $callback= C('WEB_URL').'success.html';
                $notify= C('WEB_URL').U("mobile/Notify/bindNotify");
                //数组
                $data = array();
                //$data['platformNo'] = $loan->platform();//商户编号
                $data['platformUserNo'] = $user_data['platform']; //商户平台会员标识
                $data['requestNo'] = $user_data['orders'];//请求流水号
                $data['nickName'] = $vo['user_name'];//昵称
                $data['realName'] = $vo['real_name'];//会员真实姓名
                $data['idCardType'] = 'G2_IDCARD';//身份证类型
                $data['idCardNo'] = $vo['idcard'];//会员身份证号
                $data['mobile'] = $vo['user_phone'];//接收短信验证码的手机号
                $data['email'] = $vo['user_email'];//邮箱
                $data['callbackUrl'] = $callback;//页面回跳URL
                $data['notifyUrl'] = $notify;//服务器通知URL
                $xml = $loan->array_xml($data);
                AppCommonAction::ajax_encrypt($xml,1);
            }else{
                AppCommonAction::ajax_encrypt('绑定第三方托管失败！',0);
            }
        }else{//失败后重新尝试
            //请求数据
            $data['platformNo']= $loan->platform();  //商户编号
            $data['platformUserNo ']= $record['platform']; //平台会员编号
            $service='ACCOUNT_INFO';
            $dataStr=$loan->array_xml($data);
            $result=$loan->lazyCatDirect($dataStr,$service);
            $res_data=xml_to_array($result);
            if($res_data['response']['code']==1){
                M('escrow_account')->where("uid={$this->uid}")->save(array('bind_status'=>1));
                $c = M('members_status')->where('uid='.$this->uid)->find();
                if ($c) {
                    $ids['id_status'] = 1;
                    $ids['phone_status'] = 1;
                    M('members_status')->where('uid='.$this->uid)->save($ids);
                } else {
                    $ids['id_status'] = 1;
                    $ids['phone_status'] = 1;
                    $ids['uid'] = $record['uid'];
                    M('members_status')->add($ids);
                }
                AppCommonAction::ajax_encrypt('绑定第三方托管账户成功！',1);
            }else{
                //绑定失败
                M('escrow_account')->where("uid={$this->uid} and bind_status=0")->delete();
                AppCommonAction::ajax_encrypt('绑定第三方托管失败！',0);
            }
        }
        /*
        //platformUserNo 商户平台会员标识  date('YmdHis').$uid
        //requestNo 请求流水号  build_order_no(),
        //platformNo  商户编号 $loan->platform()，
        */
    }
    /**
     * yeepay  当前用户认证状态，
     * @param int $uid;
     */
    public function ckstatus($uid){
        $vo = M("members_status")->field("id_status,phone_status,email_status")->where("uid=".$uid)->find();
        //
        if ($vo['id_status']==3) {
            AppCommonAction::ajax_encrypt('实名认证等待审核中！',0);
        }elseif ($vo['id_status']==1 && $vo['id_status']==3) {
            AppCommonAction::ajax_encrypt('请先进行实名认证！',0);
        }
        //
        if ($vo['phone_status']==3) {
            AppCommonAction::ajax_encrypt('手机认证等待审核中！',0);
        }elseif ($vo['phone_status']==1 && $vo['phone_status']==3) {
            AppCommonAction::ajax_encrypt('请先进行手机认证！',0);
        }
    }
    
    /**
     * yeepay  检测是否绑定托管
     * @param int $uid;
     */
    public function is_binding($uid){
        if(empty($uid) || empty($this->uid)){
            AppCommonAction::ajax_encrypt('请先登录！',0);
        }
        $count = M('escrow_account')->where("uid=".$uid)->count('uid');
        if ($count<1) {
            AppCommonAction::ajax_encrypt('请先绑定托管账户！',0);
        }
    }
    
    /**
    * yeepay  充值
    * @param int uid;
    * @param float money;
    */
    public function ybrecharge(){
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        //检测
        $this->is_binding($arr['uid']);
        import("ORG.Loan.Escrow");
        $loan = new Escrow();
        $user_data = M('escrow_account')->field('*')->where('uid=' . $this->uid)->find();
        if (!$user_data) {
            AppCommonAction::ajax_encrypt('请先绑定托管账户！',0);
        }
        $money = floatval($arr['money']);
        if (empty($money)){
            AppCommonAction::ajax_encrypt('请输入充值金额！',0);
        }
        $add = array(//同时添加到 member_payonline 一条临时数据 状态 issuccess = 0
            'uid' => $this->uid,
            'add_time' => time(),
            'money' => $money,
            'loan_no'=>  "CZ".build_order_no()
        );
        $id = M("member_payonline")->add($add); //利用返回id 查找表自动生成的 orderno(平台充值单号)
        if (!$id) {
            AppCommonAction::ajax_encrypt('订单生成出错！',0);
        }
        $callback= C('WEB_URL').'success.html';
        $notify= C('WEB_URL').U("mobile/Notify/chargeNotify");
        //数组
        $data = array();
        $data['platformNo'] = $loan->platform();//商户编号
        $data['requestNo'] = $add['loan_no'];//请求流水号
        $data['platformUserNo'] = text($user_data['platform']);//平台用户编号
        $data['amount'] = floatval($arr['money']);//充值金额
        $data['feeMode'] = 'PLATFORM'; //费率模式，固定值PLATFORM
        $data['callbackUrl'] = $callback;//页面回跳URL
        $data['notifyUrl'] = $notify;//服务器通知URL
        AppCommonAction::ajax_encrypt($data,1);
    }
    
    /**
     *  yeepay  绑卡
     *  @param int uid;
     */
    public function ybtiecard() {
        header("Content-type:text/html;charset=utf-8");
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        //检测
        $this->is_binding($arr['uid']);
        $user_data = M('escrow_account')->field('*')->where('uid=' . $arr['uid'])->find();
        if (!$user_data) {
            AppCommonAction::ajax_encrypt('请先绑定托管账户！',0);
        }
        import("ORG.Loan.Escrow");
        $loan = new Escrow();
        //请求数据
        $data['platformNo']= $loan->platform();
        $data['platformUserNo'] = text($user_data['platform']);
        $service='ACCOUNT_INFO';
        $dataStr=$loan->array_xml($data);
        $result=$loan->lazyCatDirect($dataStr,$service);
        $balance=xml_to_array($result);
        if(!empty($balance) && $balance['response']['cardNo']!=''){
            $borrowconfig = FS("Webconfig/borrowconfig");
            $bbankname = $borrowconfig['BANK_NAME'];
            $bank_status = array_flip(C('CARD_STATUS'));
            $bank_info['bank_status'] = $bank_status[$balance['response']['cardStatus']];
            $bank_info['bank_code'] = $balance['response']['bank'];
            $bank_info['bank_name'] = $bbankname[$balance['response']['bank']];
            $bank_info['uid'] = $arr['uid'];
            $bank_info['bank_num'] =  intval($balance['response']['cardNo']) ;
            $b=M('member_banks')->where('uid='.$arr['uid'])->find();
            if(!$b){
                $res=M('member_banks')->add($bank_info);
                if($res){
                    AppCommonAction::ajax_encrypt('恭喜您！绑卡成功~',1);
                }else{
                    AppCommonAction::ajax_encrypt('您已绑定过银行卡,请联系客服！',0);
                }
            }else{
                AppCommonAction::ajax_encrypt('您已绑定过银行卡,请不要重复绑定！',0);
            }
        }else{
            $callback= C('WEB_URL').'success.html';
            $notify= C('WEB_URL').U("mobile/Notify/addBankNotify");
            //数组
            $data['requestNo'] ='BK'.date("YmdHi");//请求流水号
            $data['callbackUrl'] = $callback;//页面回跳URL
            $data['notifyUrl'] = $notify;//服务器通知URL
            AppCommonAction::ajax_encrypt($data,1);
        }
    }
    
    /**
     *  yeepay  提现
     *  @param int uid;
     *  @param float money;
     *  @param int account_bank_id;
     */
    public function ybwithdraw(){
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        //检测
        $this->is_binding($arr['uid']);
        $pre = C('DB_PREFIX');
        $withdraw_money = floatval($arr['money']);//提现金额
        $bank_id = intval($arr['account_bank_id']);//提现银行卡
        $vo = M('members m')->field('mm.account_money,mm.back_money,(mm.account_money+mm.back_money) all_money,m.user_leve,m.time_limit')->join("{$pre}member_money mm on mm.uid = m.id")->where("m.id={$this->uid}")->find();
        if($vo['all_money']<$withdraw_money) {
            AppCommonAction::ajax_encrypt("提现额大于帐户余额",0);
        }
        
        $fee_mode = explode("|",$this->glo['fee_mode']);      //获取全局费率模式
        $money_tx_max = explode("|",$this->glo['money_tx']);      //获取全局提现金额
        
        
        $money_tx = explode("-",$money_tx_max[0]);        //普通会员提现额度限制
        $money_tx_vip = explode("-",$money_tx_max[1]);   //VIP会员提现额度限制
        
        if($vo['user_leve']>0){
            $fee_mode_type=$fee_mode[0];
            if($withdraw_money>$money_tx_vip[1] && $money_tx_vip[1]!=0){
                AppCommonAction::ajax_encrypt("提现金额不能超过{$money_tx_vip[1]}哦~",0);
            }
            if($withdraw_money<$money_tx_vip[1] && $money_tx_vip[1]!=0){
                AppCommonAction::ajax_encrypt("提现金额需要大于{$money_tx_vip[1]}哦~",0);
            }
        }else{//普通会员
            $fee_mode_type=$fee_mode[1];
            if($withdraw_money>$money_tx[1] && $money_tx[1]!=0){
                AppCommonAction::ajax_encrypt("提现金额不能超过{$money_tx[1]}哦~",0);
            }
            if($withdraw_money<$money_tx[1] && $money_tx[1]!=0){
                AppCommonAction::ajax_encrypt("提现金额需要大于{$money_tx[1]}哦~",0);
            }
        }
        $moneydata['withdraw_money'] = $withdraw_money;
        $moneydata['withdraw_fee'] = 0.00;
        $moneydata['withdraw_status'] = 0;
        $moneydata['uid'] =$this->uid;
        $moneydata['add_time'] = time();
        $moneydata['add_ip'] = get_client_ip();
        $moneydata['bank_id'] = $bank_id;
        $moneydata['fee_mode']=$fee_mode_type;
        $moneydata['orders']='TX'.build_order_no();
        $newid = M('member_withdraw')->add($moneydata);
        if($newid){
            import("ORG.Loan.Escrow");
            $loan = new Escrow();
            $user_data = M('escrow_account')->field('*')->where('uid=' . $this->uid)->find();
            if (!$user_data) {
                AppCommonAction::ajax_encrypt('请先绑定托管账户！',0);
            }
            $callback= C('WEB_URL').'success.html';
            $notify= C('WEB_URL').U("mobile/Notify/withdrawNotify");
            //数组
            $data['platformNo']= $loan->platform();
            $data['requestNo'] =$moneydata['orders'];
            $data['platformUserNo'] = text($user_data['platform']);
            $data['amount'] = $withdraw_money;
            $data['feeMode'] = $fee_mode_type=='1' ? 'PLATFORM' : 'USER';
            $data['callbackUrl'] = $callback;
            $data['notifyUrl'] = $notify;
            AppCommonAction::ajax_encrypt($data,1);
        }
    }
	/**
	**
	**灵活宝相关函数开始*
	**
	**/
	/**灵活宝首页**/
	public function flexible_index(){
		$jsoncode = file_get_contents("php://input");
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
		
		$uid = $arr['uid'];
		if($uid != $this->uid){
			AppCommonAction::ajax_encrypt('请先登录!',0);
		}
            $user_money = M('member_money')->field('account_money, back_money')->where("uid=".$this->uid)->find();
            $user_money['money'] = bcadd($user_money['account_money'], $user_money['back_money'], 2);
            //$this->assign('user_money', $user_money);

            $agility_bao = new AgilityBehavior();
            $bao = $agility_bao->format_list();
            $bao = $bao[0];
            if( !empty($bao) ) {
                $bao['lefttime'] = time() - $bao['online_time'];
            }
            
            $deadline = strtotime("+{$bao['repayment_period']} month", $bao['online_time']);
            $deadline = strtotime(date("Y-m-d 23:59:59", $deadline));
            $day = intval(($deadline-time())/3600/24); // 剩余天数
            $bao['day'] = $day;
            //$this->assign('bao', $bao);
			if($bao['status'] == 1 and $bao['lefttime'] < 0){
				$list['bao_status'] = 1;
			}elseif($bao['status'] == 1 and $bao['lefttime'] > 0){
				if($uid){
					$list['bao_status'] = 2;
				}else{
					$list['bao_status'] = 3;
				}
			}else{
				$list['bao_status'] = 4;
			}

			$list['lefttime'] = $bao['lefttime'];             //剩余时间
			$list['remain_money'] = $bao['remain_money'].'元';     //可投金额
			$list['interest_rate'] = $bao['interest_rate'].'%';   //年化收益
			$list['funds'] = $bao['funds'].'元';                   //计划金额
			$list['term'] = $bao['term'].'天';                     //封存期限
			$list['start_funds'] = $bao['start_funds'];       //起投金额
			$list['bao_id'] = $bao['id'];                     //项目id
			$list['user_money'] = $user_money['money'];       //帐户余额

            $code_str = $this->uid.$bao['id'].$user_money['money'];
            $auth_info = md5($code_str);
            session('agility_auth_info', $auth_info);
			
            $minfo = getMinfo($this->uid, "m.pin_pass");
			if(empty($minfo['pin_pass']) === true){
				$msg['is_jumpmsg'] = '请先设置支付密码!';
				AppCommonAction::ajax_encrypt($msg,1006);
			}
            
            AppCommonAction::ajax_encrypt($list,1);
        }   
		public function flexible_ajax_index(){
			$jsoncode = file_get_contents("php://input");
			$arr = json_decode($jsoncode,true);
			$arr = AppCommonAction::get_decrypt_json($arr);
			$agility_bao = new AgilityBehavior();
			$money = intval($arr['money']);
            $bao = $agility_bao->format_list();
            $bao = $bao[0];
            if( !empty($bao) ) {
                $bao['lefttime'] = time() - $bao['online_time'];
            }
			$user_money = M('member_money')->field('account_money, back_money')->where("uid=".$this->uid)->find();
            $user_money['money'] = bcadd($user_money['account_money'], $user_money['back_money'], 2);

			$deadline = strtotime("+{$bao['repayment_period']} month", $bao['online_time']);
            $deadline = strtotime(date("Y-m-d 23:59:59", $deadline));
            $day = intval(($deadline-time())/3600/24); // 剩余天数
            $bao['day'] = $day;
			//(money * interest_rate/365 * day)/100
			$list['shouyi'] = intval($money * $bao['interest_rate']/365 * $bao['day'])/100;
			$list['user_money'] = $user_money['money'];
			AppCommonAction::ajax_encrypt($list,1);
		}
        
        /**
        * 转入资金
        * 
        */
        public function flexible_save()
        {
			$jsoncode = file_get_contents("php://input");
			$arr = array();
			$arr = json_decode($jsoncode,true);
			$arr = AppCommonAction::get_decrypt_json($arr);
			
           if(!$this->uid){
               AppCommonAction::ajax_encrypt("请先登录后进行操作",0);
           }
           

           $bao_id = intval($arr['bao_id']);
           $invest_money = intval($arr['money']);
           $pay_pass = $arr['pay_pass'];
           
           
           
           if(!$bao_id || !$invest_money || !$pay_pass){
               AppCommonAction::ajax_encrypt("参数有误！",0);    
           }
           
           $pin_pass = M("members")->where("id={$this->uid}")->getField("pin_pass");
           if(md5($pay_pass)!==$pin_pass){
               AppCommonAction::ajax_encrypt("支付密码不正确",0);    
           }
           
           $user_money = M('member_money')->field('account_money, back_money')->where("uid=".$this->uid)->find();
           $user_money['money'] = bcadd($user_money['account_money'], $user_money['back_money'], 2);
           $bao = M("bao")->field(true)->where("id={$bao_id} and status=1")->find();   
           $auth_info = $this->uid.$bao['id'].$user_money['money'];
			
           if($user_money['money'] < $invest_money){
               AppCommonAction::ajax_encrypt("账户余额不足",0);    
           }
           
           if($invest_money%$bao['start_funds']){
               AppCommonAction::ajax_encrypt("投资金额必须为{$bao['start_funds']}的整数倍！",0);
           }
           
           
           $raise_money = bcadd($bao['raise_funds'], $invest_money, 2);
           if($raise_money > $bao['funds']){
               AppCommonAction::ajax_encrypt("投资金额超过计划金额上限",0);    
           }
            // 充值之后未刷新，提示信息有误
           if(md5($auth_info) != session("agility_auth_info")){

               AppCommonAction::ajax_encrypt("验证信息有误",0);    
           }else{ // 执行投资

               $bao_invest_id = $this->investment($this->uid, $bao_id, $invest_money);
               if($bao_invest_id){
                   session('agility_auth_info', null);
                   AppCommonAction::ajax_encrypt("投资成功，投资金额{$invest_money}元");    
               }else{
                   AppCommonAction::ajax_encrypt("很遗憾，投资失败！稍后重试", 0);
               }
           }
           
           
        }
        /**
        * 进行投资操作
        * 
        */
        private function  investment($uid, $bao_id, $money)
        {
        /**
        * 1 更新bao 数据表 raise_funds  已集资 金额
        * 2 bao_invest 项目投资记录汇总，此数据每位投资者一条信息，同一投资者多次投资更新记录，次表数据用于投资账户资金别动资金池  
        */
        
            $bao_info = M("bao")->field(true)->where("id={$bao_id} and status=1 and funds > raise_funds")->find();
            if(!$bao_info){
                AppCommonAction::ajax_encrypt("信息有误", 0);
            }              
            return $this->AgilityBehavior->investMoney($bao_info['batch_no'], $money, $uid); // 投资，成功返回id, 失败返回false
            

        }
		/**
        * 产品首页
        * 
        */
        public function user_index()
        {
			$jsoncode = file_get_contents("php://input");
			$arr = json_decode($jsoncode,true);
			$arr = AppCommonAction::get_decrypt_json($arr);
			//$uid = $arr['uid'];
			if(!$this->uid){
				ajaxmsg("请先登录后进行操作",0);
			}
            $interest = BaoInvestModel::get_sum_interest($this->uid);

            $assets = BaoInvestModel::get_sum_money($this->uid); 
            
            $recently = M('bao_record')->where("uid={$this->uid}")->order('id desc')->getField('money');
            
            $data = $this->getMyItem($this->uid); // 投资中的项目
			
			if(empty($data)){
				$list = array();
				//$list['list'] = $data;
				$list['interest'] = ceil($interest);  // 总收益
				$list['assets'] = ceil($assets);      //资产总额
				$list['recently'] = $recently;  // 最近收益
				ajaxmsg($list, 1);
			}else{
				$list = array();
				$list['list'] = $data;
				$list['interest'] = ceil($interest);  // 总收益
				$list['assets'] = ceil($assets);      //资产总额
				$list['recently'] = $recently;  // 最近收益
			}

            ajaxmsg($list, 1);
        }
        
        
        
        /**
        * 获取投资中，还款中的项目
        * 
        * @param mixed $uid
        */
        private function getMyItem($uid)
        {
            $condition =  " i.uid={$uid} and b.status in (1,2)";  
            $item_list = M("bao as b")
                        ->join(C('DB_PREFIX')."bao_invest as i ON b.batch_no=i.batch_no")
                        ->field("b.batch_no, b.interest_rate, i.deadline, i.interest, i.money")
                        ->where($condition)
                        ->order('i.add_time')
                        ->select();
            foreach($item_list as $key=>$val){
                $out_money = M('bao_log')->where("type=2 and status=1 and uid={$uid}  and batch_no='{$val['batch_no']}'")->sum('money');
                $capital = M('bao_log')->where("type=1 and status=1 and uid={$uid}  and batch_no='{$val['batch_no']}'")->sum('money');  
				
                $item_list[$key]['out_money']   = number_format($out_money,2);        // 已赎本息
                $item_list[$key]['capital'] = floatval($capital);//当前本金
				$item_list[$key]['deadline'] = date('Y-m-d',$val['deadline']);
            }
            if(empty($item_list)){
                return '';
            }else{
                return $item_list;
            }            
                        
            
        }
        //灵活宝回款列表详情
        public function iteminfo()
        {
			$jsoncode = file_get_contents("php://input");
			$arr = array();
			$arr = json_decode($jsoncode,true);
			$arr = AppCommonAction::get_decrypt_json($arr);
            $batch_no = text($arr['batch']);
            $time = time();
            if(empty($batch_no)){
                AppCommonAction::ajax_encrypt('参数错误！', 0);
            }
            $bao_invest = $this->getBaoInvest($batch_no, $this->uid);
            $bao_info = M('bao')->field(true)->where("batch_no='{$batch_no}'")->find(); 
            //$this->assign('bao_info', $bao_info);
            //$this->assign('bao_invest', $bao_invest);
			$data['interest_rate'] = $bao_info['interest_rate'];//年化收益
			$data['batch_no'] = $bao_info['batch_no'];//项目编号
            $data['money'] = number_format($bao_invest['money'],2);//在投金额
			$data['term'] = $bao_info['term']; //封存期限
			$data['start_funds'] = floatval($bao_info['start_funds']);//部分赎回后剩余金额不得小于
			$data['deadline'] = date('Y-m-d',$bao_invest['deadline']);
			
			
            $archive_time = M('bao_log')->where("batch_no='{$batch_no}' and uid={$this->uid}")->order("archive_time desc")->getField('archive_time');
            //$this->assign('archive_time', $archive_time);
			$data['archive_time'] = date('Y-m-d',$archive_time);//可随时一次性或部分赎回本息
            
            $add_time = M('bao_log')->where("batch_no='{$batch_no}' and uid={$this->uid} and type=1 and status=1" )->order("add_time asc")->getField('add_time');
            //$this->assign('add_time', $add_time);
			$data['add_time'] = date('Y-m-d',$add_time);//投资日期
			
			if($archive_time>time()){
				$data['is_shuhui'] = 0;
			}else{
				$data['is_shuhui'] = 1;
			}
			$data['tip'] = $data['add_time'].'后，可随时一次性或部分赎回本息部分赎回后剩余金额不得小于'.$data['start_funds'].'元';
            
            $e_time = M('bao_record')->where("batch_no='{$batch_no}' and uid={$this->uid} and  status=1" )->order("e_time asc")->getField('e_time');

            //$this->assign('e_time', $e_time);
			$data['e_time'] = date('Y-m-d',$e_time);//起息日期
			$data['fenpei_style'] = '收益复投';

			
            
            //统计收益记录
            $record['count'] = M('bao_record')->where("batch_no='{$batch_no}' and uid={$this->uid} and status=1")->count('id');
            $record['money'] = M('bao_record')->where("batch_no='{$batch_no}' and uid={$this->uid} and status=1")->sum('money');
            //已赚收益
            $record['incoming'] = BaoInvestModel::get_sum_interest($this->uid, $batch_no);
            
            //$this->assign('record', $record);
			$data['record_money'] = number_format($record['money'],2);//已赚收益
            

            $archive_money = M('bao_log')->where("batch_no='{$batch_no}' and uid={$this->uid} and type=1 and archive_time >= {$time} and status=1")->sum('money');// 封存本金

            $bao['money'] = bcsub($bao_invest['money'], $archive_money, 2);
            
            //$this->assign('bao', $bao);
            if($bao['money'] > 0){  //赎回状态
				$data['bao_status'] = 1;
			}else{
				$data['bao_status'] = 0;
			}
            AppCommonAction::ajax_encrypt($data,1);
        }
        
        
        /**
        * 赎回资金
        * 
        */
        public function redeemSave()
        {
			$jsoncode = file_get_contents("php://input");
			$arr = array();
			$arr = json_decode($jsoncode, true);
			$arr = AppCommonAction::get_decrypt_json($arr);
            
            $batch = text($arr['batch']);
            $out_money = floatval($arr['fredeemamount']);
			$uid = $arr['uid'];
			if($uid != $this->uid){
				AppCommonAction::ajax_encrypt("请登录后操作!",0);
            }
            $time =  time();
            
            $bao_info = M('bao')->field(true)->where("batch_no='{$batch}'")->find();
            $archive_money = M('bao_log')->where("batch_no='{$batch}' and uid={$uid} and type=1 and archive_time >= {$time}")->sum('money');// 封存本金
            $invest_money = M('bao_invest')->where("batch_no='{$batch}' and uid={$uid}")->getField("money");
            
            if(bcsub($invest_money, $archive_money, 2) < $out_money){
				AppCommonAction::ajax_encrypt("赎回金额大于可赎回金额!",0);
            }
            if(bcsub($invest_money, $out_money, 2) && bcsub($invest_money, $out_money, 2) < $bao_info['start_funds']){
				AppCommonAction::ajax_encrypt("赎回后剩余金额不得小于最低投资金额!",0);
            }
            
            D("AgilityBehavior");
            $AgilityBehavior = new AgilityBehavior();
            $out_money_res = $AgilityBehavior->outMoney($batch, $out_money, $uid); // 赎回资金
            
            if($out_money_res){
				AppCommonAction::ajax_encrypt("赎回提交成功，请查证账户!",1);
            }else{
				AppCommonAction::ajax_encrypt("赎回提交失败，请重试！",0);
            }  

        }
        
        
        /**
        * 获取指定用户，指定项目的投资信息
        * 
        * @param mixed $batch
        * @param mixed $uid
        */
        private function getBaoInvest($batch, $uid)
        {
            $bao_invest = M('bao_invest')->field(true)->where("batch_no='{$batch}' and uid={$uid} ")->find();    
            return $bao_invest;
        }
        
        /**
        * 获取收益记录，带分页
        * 
        * @param mixed $batch
        * @param mixed $uid
        */
        public function getRecord()
        {
			$jsoncode = file_get_contents("php://input");
			$arr = array();
			$arr = json_decode($jsoncode, true);
			$arr = AppCommonAction::get_decrypt_json($arr);

			$page = intval($arr['page'])? intval($arr['page']):1;
			$limit = intval($arr['limit'])? intval($arr['limit']):10;
			$uid = intval($arr['uid']);
            $batch = $arr['batch']; 
			$_GET['p'] = intval($page);
            $Page = D('Page');    
            $condition =  "batch_no='".$batch."' and uid={$uid}";  
            import("ORG.Util.Page");  
			
            $count = M("bao_record")->where($condition)->count('id');
            $totalPage = ceil($count/$limit);
            $p     = new Page($count,$limit);
			$Lsql = "{$p->firstRow},{$p->listRows}";
            
            
            
            
            $list = M('bao_record')
                        ->field(true)
                        ->where($condition)
                        ->order('e_time desc')
                        ->limit($Lsql)
                        ->select();
            $string = '';
            foreach($list as $k=>$v){
				$_list[$k]['e_time'] = $v['e_time'];
				$_list[$k]['money'] = $v['money'];
				$_list[$k]['funds'] = $v['funds'];
				$_list[$k]['yifutou'] = '已复投';
                //$string .= '<tr class="yepageid"><td>'.date("Y-m-d", $v['e_time']).'</td><td>'.$v['money'].'</td><td>'.$v['funds'].'</td><td>已复投</td></tr>';
                
            }
            
           if(is_array($_list)){
				$data['list'] = $_list;
				$data['totalPage'] = $totalPage;
				$data['nowPage'] =  $page;
			}else{
				$data['message'] = '暂无项目记录';
				AppCommonAction::ajax_encrypt($data,0);
			}

            AppCommonAction::ajax_encrypt($data,1);

        }
        
        /**
        * 获取资金记录，带分页
        * 
        * @param mixed $batch
        * @param mixed $uid
        */
        public function getLog()
        {
			$jsoncode = file_get_contents("php://input");
			$arr = array();
			$arr = json_decode($jsoncode, true);
			$arr = AppCommonAction::get_decrypt_json($arr);

			$page = intval($arr['page'])? intval($arr['page']):1;
			$limit = intval($arr['limit'])? intval($arr['limit']):10;
			$uid = intval($arr['uid']);
			
			if($uid != $this->uid){
				AppCommonAction::ajax_encrypt("请先登录!",0);
			}
			$_GET['p'] = intval($page);
            $status = array(0=>'审核中',1=>'成功',2=>'退回');
            $batch = $_GET['batch'];
            $type = intval($_GET['type']);
            
            $condition =  " uid={$uid}";  
            $type && $condition .= " and type=".$type;
            $batch && $condition .= " and batch_no='".$batch."'"; 
            
            import("ORG.Util.Page");       
            $count = M("bao_log")->where($condition)->count('id');
			$totalPage = ceil($count/$limit);
            $p     = new Page($count,$limit);
			$Lsql = "{$p->firstRow},{$p->listRows}";
            
            
            $list = M('bao_log')
                        ->field(true)
                        ->where($condition)
                        ->order('add_time desc, auditors_time desc')
                        ->limit($Lsql)
                        ->select();
            $string = '';
            foreach($list as $k=>$v){
                $v['remark']=='' && $v['remark'] = '无';
				$_list[$k]['add_time'] = date("Y-m-d", $v['add_time']);
				$_list[$k]['batch_no'] = $v['batch_no'];
                if($v['type']==2){
					$_list[$k]['money'] = $v['money'];
					$_list[$k]['Redeem'] = 0;   // 赎回
                }else{
					$_list[$k]['money'] = $v['money'];
					$_list[$k]['Redeem'] = 1;   //投资
                }
                $_list[$k]['status_type'] = $v['status'];
				$_list[$k]['remark'] = $v['remark'];
            }
			if(is_array($_list)){
				$data['list'] = $_list;
				$data['totalPage'] = $totalPage;
				$data['nowPage'] =  $page;
			}else{
				$data['message'] = '暂无项目记录';
				AppCommonAction::ajax_encrypt($data,0);
			}

            AppCommonAction::ajax_encrypt($data,1);
        }
        
         /**
        * 获取已结束项目，带分页
        * 
        * @param mixed $uid
        */
        public function getEndItem()
        {
			$jsoncode = file_get_contents("php://input");
			$arr = array();
			$arr = json_decode($jsoncode, true);
			$arr = AppCommonAction::get_decrypt_json($arr);

			$page = intval($arr['page'])? intval($arr['page']):1;
			$limit = intval($arr['limit'])? intval($arr['limit']):10;
            $uid = intval($this->uid);
			
			if($uid != $this->uid){
				AppCommonAction::ajax_encrypt("请先登录后进行操作",0);
			}
			$_GET['p'] = intval($page);
            $condition =  " i.uid={$uid} and b.status=4 ";  
            import("ORG.Util.Page");       
            $count = M("bao as b")->join(C('DB_PREFIX')."bao_invest as i ON b.batch_no=i.batch_no")->where($condition)->count('b.id');

            $totalPage = ceil($count/$limit);
            $p = new Page($count, $limit);
			$Lsql = "{$p->firstRow},{$p->listRows}";
            
            $list =  M("bao as b")
                        ->join(C('DB_PREFIX')."bao_invest as i ON b.batch_no=i.batch_no")
                        ->field("b.batch_no, i.add_time, b.interest_rate, i.deadline, i.interest")
                        ->where($condition)
                        ->order('i.add_time')
                        ->limit($Lsql)
                        ->select();

            foreach($list as $k=>$v){
                $e_time = M('bao_record')->where("batch_no='{$v['batch_no']}'")->order("e_time asc")->getField('e_time');
                $money = M('bao_log')->where("batch_no='{$v['batch_no']}' and type=1 and status=1")->sum('money');
                
				$_list[$k]['batch_no'] = $v['batch_no'];
				$_list[$k]['interest_rate'] = $v['interest_rate'];
				$_list[$k]['e_time'] = date("Y-m-d", $e_time);
				$_list[$k]['deadline'] = date("Y-m-d", $v['deadline']);
				$_list[$k]['money'] = $money;
				$_list[$k]['interest'] = $v['interest'];
                
            }
            
			if(is_array($_list)){
				$data['list'] = $_list;
				$data['totalPage'] = $totalPage;
				$data['nowPage'] =  $page;
			}else{
				$data = '暂无项目记录';
			}
			
            AppCommonAction::ajax_encrypt($data,1);
        }
        
        /**
        * 获取资金记录，带分页
        * 
        * @param mixed $batch
        * @param mixed $uid
        */
        public function getLog2()
        {
			$jsoncode = file_get_contents("php://input");
			$arr = array();
			$arr = json_decode($jsoncode, true);
			$arr = AppCommonAction::get_decrypt_json($arr);

			$page = intval($arr['page'])? intval($arr['page']):1;
			$limit = intval($arr['limit'])? intval($arr['limit']):10;
            $uid = intval($this->uid);
			//$uid = 50;
			$_GET['p'] = intval($page);

            $status = array(0=>'审核中',1=>'成功',2=>'退回');
            $batch = $arr['batch'];
            $type = intval($arr['type']);
            $condition =  " uid={$uid}";  
            $type && $condition .= " and type=".$type;
            $batch && $condition .= " and batch_no='".$batch."'"; 
            
            import("ORG.Util.Page");       
            $count = M("bao_log")->where($condition)->count('id');
            $totalPage = ceil($count/$limit);
            $p = new Page($count, $limit);
			$Lsql = "{$p->firstRow},{$p->listRows}";
            
            $list = M('bao_log')
                        ->field(true)
                        ->where($condition)
                        ->order('add_time desc, auditors_time desc')
                        ->limit($Lsql)
                        ->select();

            $string = '';
            foreach($list as $k=>$v){

				$v['remark']=='' && $v['remark'] = '无';
				$_list[$k]['add_time'] = date("Y-m-d", $v['add_time']);
                if($v['type']==2){
					$_list[$k]['money'] = $v['money'];
					$_list[$k]['Redeem'] = '赎回';
                }else{
					$_list[$k]['money'] = $v['money'];
					$_list[$k]['Redeem'] = '投资';
                }
                $_list[$k]['status_type'] = $v['status']==1? '成功':'失败';
				$_list[$k]['remark'] = $v['remark'];
                
            }
            if(is_array($_list)){
				$data['list'] = $_list;
				$data['totalPage'] = $totalPage;
				$data['nowPage'] =  $page;
			}else{
				$data['message'] = '暂无项目记录';
				AppCommonAction::ajax_encrypt($data,0);
			}

            AppCommonAction::ajax_encrypt($data,1);
        }
		public function load_xieyi(){
			$jsoncode = file_get_contents("php://input");
			$arr = array();
			$arr = json_decode($jsoncode, true);
			$arr = AppCommonAction::get_decrypt_json($arr);
			if($this->uid){
				AppCommonAction::ajax_encrypt('请先登录!',0);
			}
			$root = C('WEB_URL');
			$bao_id = $arr['batch'];
			AppCommonAction::ajax_encrypt($root.'/member/agreement/flexible?id='.$bao_id,1);
		}
		/**
	**
	**灵活宝相关函数结束*
	**
	**/

	/**
	**
	**投资管理方法开始*
	**
	**/
	//投资管理首页
	public function invest_index(){

		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		//$arr = AppCommonAction::get_decrypt_json($arr);
		//"limit":"10","uid":50,"t":"0","page":"1","k":"0","production":1
		
		$page = intval($arr['page'])? intval($arr['page']):1;
        $limit = intval($arr['limit'])? intval($arr['limit']):5;
		$pre = C("DB_PREFIX");
		$_GET['p'] = $page;
        $uid = intval($this->uid);
		 
		if($uid != $this->uid){
			ajaxmsg('请登陆后查看!',0);
		}

        $designer = FS("Webconfig/designer");
        $version = FS("Webconfig/version");
        #-----------默认条件--------------------------
        $map['i.investor_uid'] = $this->uid;
        $map['b.borrow_status'] = array('in','6,8');   //默认还款中
		
        
        #*-----------搜索条件-----------------
        
		$surl = array('t'=>$arr['t'],'production'=>$arr['production'],'k'=>$arr['k']);
        $urlArr = array('production','t','k');
        foreach($urlArr as $v){
            $newpars = $surl;  //用新变量避免后面的连接受影响
            unset($newpars[$v],$newpars['type_list'],$newpars['order_sort'],$newpars['orderby']);   //去掉公共参数，对掉当前参数
            foreach($newpars as $skey=>$sv){
                if($sv=="0") unset($newpars[$skey]); //去掉"全部"状态的参数,避免地址栏全满
            }
            $newurl = http_build_query($newpars);  //生成此值的链接,生成必须是即时生成
            $searchUrl[$v]['url'] = $newurl;
            $searchUrl[$v]['cur'] = text($arr[$v]);
        }
        if (empty($searchUrl['k']['cur'])){
            $searchUrl['k']['cur'] = 4; //保证进入页面，还款状态为复审中
        }
        //print_r($searchUrl['k']['cur']);
        foreach($urlArr as $v){
            if($arr[$v]){
                switch($v){
                    case 'production':  //产品类型
                        $borrow_type = text($arr[$v]);
                        $map["i.borrow_type"] = $borrow_type;
                        break;
                    case 't':  //保障机构   //parent_invest_id，有值，认购债权。0，直投
                        $t = text($arr[$v]);
                        if ($t=='1'){
                            $map['i.parent_invest_id'] = 0;
                        }elseif ($t=='2'){
                            $map['i.parent_invest_id'] = array('neq',0);
                        }
                        break;
                    case 'k':   //还款状态
                        $k = text($arr[$v]);
                        if ($k=='4') {
                            $map['b.borrow_status'] = array('in','6,8');  //还款中
                        }elseif ($k=='1'){
                            $map['b.borrow_status'] = array('in','7,9,10');   //已完成
                        }elseif ($k=='14'){
                            $map['d.status'] = 4;   //已转让
                        }elseif ($k=='2'){
                            $map['b.borrow_status'] = array('in','0,2,4');   //竞标中
                        }
                        break;
                    default:
                        break;
                }
            }
        }//print_r(111);exit;
        $list = getTTenderList($map, $limit);
		$_list = null;
        foreach($list['list'] as $k => $v){
			$_list[$k]['borrow_id'] = $v['i_id'];  //标id
            $_list[$k]['borrow_name'] = $v['borrow_name'];  //项目名称
            $_list[$k]['danbao'] = $v['danbao'];  //保障机构
            $_list[$k]['borrow_interest_rate'] = $v['borrow_interest_rate'];  //收益率
            $_list[$k]['investor_capital'] = $v['investor_capital'];  //在投金额
            $_list[$k]['invest_time'] = date('Y-m-d', $v['invest_time']);  // 投资时间
            $_list[$k]['deadline'] = date('Y-m-d', $v['deadline']);   //到期时间
        }
		$count = M("borrow_investor i")->join("{$pre}borrow_info b ON b.id=i.borrow_id")->where($map)->count("i.id");
        $totalPage = ceil($count/$limit);    
        if(is_array($_list)){
            $data['list'] = $_list;
            $data['totalPage'] = $totalPage;
            $data['nowPage'] =  $page;
        }else{
            $data = '暂无相关记录!';
			ajaxmsg($data, 0);
        }
        ajaxmsg($data, 1);
        
        
		
    }
    
    //还款详情
    public function tenddetail()
    {
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		
		$uid = $arr['uid'];
		$page = intval($arr['page'])? intval($arr['page']):1;
        $limit = intval($arr['limit'])? intval($arr['limit']):5;
		$pre = C("DB_PREFIX");
		$_GET['p'] = $page;
        //$uid = intval($this->uid);
		if($uid != $this->uid){
			AppCommonAction::ajax_encrypt('请登陆后查看!',0);
		}
		
        $map['d.investor_uid'] = $this->uid;
        #$map['d.status'] = 7;   //未还款
        $map['d.invest_id'] = intval($arr['id']);
        $list = getTDTenderList($map,$limit);
		if (empty($list['have_pay'])){
            $list['have_pay'] = '0.00';
        }
        if (empty($list['fail_pay'])){
            $list['fail_pay'] = '0.00';
        }
		$_list = null;
        foreach($list['list'] as $k => $v){
            $_list[$k]['deadline'] = date('Y-m-d',$v['deadline']); //预计支付时间
            $_list[$k]['yj_money'] = $v['capital'] + $v['interest'] - $v['interest_fee'];  //预计支付金额
            $_list[$k]['sj_type'] = in_array($v['status'], array(6,7,14))? '未支付':'已支付';  //实际支付状态
        }
        $count = M("investor_detail d")->where($map)->count("d.id");
        $totalPage = ceil($count/$limit);    
        if(is_array($_list)){
            $data['list'] = $_list;
            $data['totalPage'] = $totalPage;
            $data['nowPage'] =  $page;
			$data['have_pay'] =  $list['have_pay'];//已支付本息
			$data['fail_pay'] =  $list['fail_pay'];//未支付本息
        }else{
            $data = '暂无投资记录!';
			AppCommonAction::ajax_encrypt($data,0);
        }
        AppCommonAction::ajax_encrypt($data,1);
        
    }
	/**
	**
	**投资管理方法结束*
	**
	**/

	/**
	**
	**债权转让开始*
	**
	**/
	/**
     * 可以流转的普通标
     */
    public function  canTransfer()
    {
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		//$arr = AppCommonAction::get_decrypt_json($arr);
		
		$uid = $arr['uid'];
		$page = intval($arr['page'])? intval($arr['page']):1;
        $limit = intval($arr['limit'])? intval($arr['limit']):5;
		$pre = C("DB_PREFIX");
		$_GET['p'] = $page;
        //$uid = intval($this->uid);
		if($uid != $this->uid){
			ajaxmsg('请登陆后查看!',0);
		}
		import("ORG.Util.Page");
        $count = M('borrow_investor')->where("investor_uid = ".$this->uid."  and status = 4")->count(); // 必须还款中状态
        $p = new Page($count, $limit);
		$totalPage = ceil($count/$limit);
        $Lsql = "{$p->firstRow},{$p->listRows}";

        

        $transfer = M('borrow_investor i')
            ->join("{$pre}borrow_info b ON b.id = i.borrow_id")
            ->join("{$pre}members m ON i.borrow_uid = m.id")
            ->field("i.id,b.borrow_type, i.borrow_id, i.add_time, i.deadline, b.borrow_name, i.invest_fee, b.borrow_interest_rate, m.user_name, i.debt_interest_rate, i.debt_time")
            ->where("i.investor_uid = ".$this->uid."  and i.status = 4")
            ->limit($Lsql)
            ->order('i.id')
            ->select();

        if( !empty($transfer) ) {
            $ids = only_array($transfer, 'id');
            $investor_detail = M('investor_detail')->field("sum(capital) as capital,sum(interest) as interest,invest_id")
                ->where(array('invest_id'=>array('in',implode(',', $ids)), 'status' => 7))->group("invest_id")->select();
            if( !empty($investor_detail) ) {
                foreach($transfer as $k=>$v){
                    foreach( $investor_detail as $val ) {
                        if( $val['invest_id'] == $v['id'] ) {
                            $v['investor_capital'] = $val['capital'];
                            $v['investor_interest'] = $val['interest'];
                            break;
                        }
                    }
                    $arr = $this->countDebt($v['id']);
                    $transfers['data'][$k] = $arr+ $v;
                }
            }

           // $transfers['page'] = $Page->show();
        }
		$_list = array();
		foreach($transfers['data'] as $k=>$value){
			$_list[$k]['id'] = $value['id']; //项目id
			$_list[$k]['borrow_name'] = $value['borrow_name']; //项目名称
			$_list[$k]['interest_rate'] = $value['debt_interest_rate'] > 0? $value['debt_interest_rate']:$value['borrow_interest_rate']; //利率
			$_list[$k]['investor_capital'] = $value['investor_capital'].'/'.($value['investor_interest']-$value['invest_fee']); //待收本金/待收利息
			$_list[$k]['addtime'] = date('Y-m-d',$value['add_time']); //投资时间
			$_list[$k]['deadline'] = date('Y-m-d',$value['deadline']); //投资时间
			if((($value['deadline'] - time()) < 3600*24*5) and ($value['deadline'] - time()) > 0){
				$is_debt = 0;
			}else{
				$is_debt = 1;
			}
			$_list[$k]['is_debt'] = $is_debt; //是否可转让
		}
		
		if(is_array($_list)){
            $data['list'] = $_list;
            $data['totalPage'] = $totalPage;
            $data['nowPage'] =  $page;
        }else{
            $data = '暂无相关数据！';
			ajaxmsg($data,0);
        }
        ajaxmsg($data,1);
    }
	/**
     * 统计债权回购情况
     * @param intval $invest_id  // 投资id
     */
    public function countDebt($invest_id)
    {
        $debt = array();
        $invest_id = intval($invest_id);
        if(!$invest_id){
            return $debt;
        }
        $condition = "invest_id= '".$invest_id."' and status =7";  // 还款中状态且未逾期
        //可转让期数、统计待收本金和利息
        $debt = M("investor_detail")->field("count(id) as re_num, sum(capital) as capital")->where($condition)->find();
        $debt['total'] = M("investor_detail")->where("invest_id=".$invest_id)->getField('total'); //总共多少期
        $benefit = M("investor_detail")->field("sum(interest) as interest ")->where("invest_id= {$invest_id} and  status in (1,2,3,4,5)")->find();
        $debt['uncollect'] = $this->getUncollect($invest_id); // 本期未收利息
        $debt['benefit']  = $benefit['interest']; // 已经回款的利息收益
        return $debt;

    }
	/**
     * 获取本期未收利息，不到期按天计算
     *
     * @param intval $invest_id // 投资债权id
     */
    private function getUncollect($invest_id)
    {
        $time = time();

        $uncollect = 0.00;
        $invest_info = M('borrow_investor')->field("borrow_id, debt_time, add_time")->where("id={$invest_id}")->find();
        $borrow_info = M('borrow_info')->field("borrow_interest_rate, rate_type, full_time")->where("id={$invest_info['borrow_id']}")->find();

        $interest = M("investor_detail")->field("deadline, sort_order , sum(capital) as capital")->where("invest_id= {$invest_id} and status=7")->order(" sort_order asc")->find(); // 待收利息
        if( $borrow_info['rate_type'] == BorrowModel::BID_CONFIG_RATE_TYPE_IMMEDIATE ) {
            $deadline = $invest_info['add_time'];  // 以投资添加时间为准
        } else {
            $deadline = $borrow_info['full_time']; // 即投计息没有满标时间
        }

        // 如果已经还过款，查询上一次应该还款日。如果未还过款，那么利息从满标之后开始计算。//TODO: 企业直投即投计息
        if($interest['sort_order']>1){ // 存在还款的情况
            $sort_order = $interest['sort_order'] - 1;
            $detail_info = M("investor_detail")->field("deadline, sort_order")->where("invest_id= {$invest_id} and sort_order={$sort_order}")->find();

            $deadline = $detail_info['deadline'];

        }
        if($invest_info['debt_time']){
            $deadline = $invest_info['debt_time'];
            if($detail_info['deadline'] > $deadline){
                $deadline =  $detail_info['deadline'];
            }
        }

        $day = ($time - $deadline)/3600/24;

        $day = intval($day);
        $uncollect = bcDiv(bcMul(bcMul($borrow_info['borrow_interest_rate'], $day, 6), $interest['capital'], 6), 100, 6);
        $uncollect  = bcDiv($uncollect, 365, 2);
        return $uncollect;
    }
	/**
     * 转让中的债权
     *
     */
    public function onBonds()
    {
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		//$arr = AppCommonAction::get_decrypt_json($arr);
		
		$uid = $arr['uid'];
		$page = intval($arr['page'])? intval($arr['page']):1;
        $limit = intval($arr['limit'])? intval($arr['limit']):5;
		$pre = C("DB_PREFIX");
		$_GET['p'] = $page;
        //$uid = intval($this->uid);
		if($uid != $this->uid){
			ajaxmsg('请登陆后查看!',0);
		}
		import("ORG.Util.Page");
        $count = M('debt')->where("sell_uid = ".$this->uid."  and status in (2,99)")->count();
		
        $p = new Page($count, $limit);
		$totalPage = ceil($count/$limit);
        $Lsql = "{$p->firstRow},{$p->listRows}";
        $Bonds['data'] = M('debt d')
            ->join("{$pre}borrow_investor i ON d.invest_id = i.id")
            ->join("{$pre}borrow_info b ON i.borrow_id = b.id")
            ->field("d.id,d.invest_id, d.status, i.borrow_id, d.money, d.addtime, d.period, d.total_period, b.borrow_name, b.borrow_interest_rate, b.total, b.has_pay")
            ->where("d.sell_uid = ".$this->uid."  and d.status in (2,99) ")
            ->limit($Lsql)
            ->order('d.id')
            ->select();

        $_list = array();
		foreach($Bonds['data'] as $k=>$value){
			$_list[$k]['id'] = $value['invest_id']; //项目id
			$_list[$k]['borrow_name'] = $value['borrow_name']; //项目名称
			$_list[$k]['interest_rate'] = $value['debt_interest_rate'] > 0? $value['debt_interest_rate']:$value['borrow_interest_rate']; //利率
			$_list[$k]['investor_capital'] = bcsub($value['total'],$value['has_pay']).'期/'.$value['total'].'期'; //未还/总期数
			$_list[$k]['money'] = $value['money']; //转让本金
			$_list[$k]['status'] = $value['status']; //审核状态
			$_list[$k]['addtime'] = date('Y-m-d',$value['addtime']); //转让时间
		}
		
		if(is_array($_list)){
            $data['list'] = $_list;
            $data['totalPage'] = $totalPage;
            $data['nowPage'] =  $page;
        }else{
            $data = '暂无相关数据！';
			ajaxmsg($data,0);
        }
        ajaxmsg($data,1);
    }
	//已转让债券列表
	public function successDebt()
    {
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		//$arr = AppCommonAction::get_decrypt_json($arr);
		/*$arr['uid'] = 50;
		$this->uid = 50;*/
		$uid = $arr['uid'];
		$page = intval($arr['page'])? intval($arr['page']):1;
        $limit = intval($arr['limit'])? intval($arr['limit']):5;
		$pre = C("DB_PREFIX");
		$_GET['p'] = $page;
        //$uid = intval($this->uid);
		if($uid != $this->uid){
			ajaxmsg('请登陆后查看!',0);
		}
		import("ORG.Util.Page");
        $count = M('debt')->where("sell_uid = ".$this->uid."  and status = 4")->count();
        $p = new Page($count, $limit);
		$totalPage = ceil($count/$limit);
        $Lsql = "{$p->firstRow},{$p->listRows}";
        $lists['data'] = M('debt d')
            ->join("{$pre}borrow_investor i ON d.invest_id = i.id")
            ->join("{$pre}borrow_info b ON i.borrow_id = b.id")
            ->field("d.id,d.invest_id, i.borrow_id, d.money,  d.status,d.addtime,d.period, d.total_period, b.borrow_name,b.borrow_type, d.interest_rate, b.total, b.has_pay")
            ->where("d.sell_uid = ".$this->uid."  and d.status =4 ")
            ->limit($Lsql)
            ->order('d.id')
            ->select();
        $_list = array();
		foreach($lists['data'] as $k=>$value){
			$_list[$k]['id'] = $value['id']; //项目id
			$_list[$k]['borrow_name'] = $value['borrow_name']; //项目名称
			$_list[$k]['interest_rate'] = $value['interest_rate']; //收益率
			$_list[$k]['investor_capital'] = $value['period'].'期/'.$value['total_period'].'期'; //购买期数/总期数
			$_list[$k]['money'] = $value['money']; //债权本金
			$_list[$k]['addtime'] = date('Y-m-d',$value['addtime']); //转让时间
		}
		
		if(is_array($_list)){
            $data['list'] = $_list;
            $data['totalPage'] = $totalPage;
            $data['nowPage'] =  $page;
        }else{
            $data = '暂无相关数据！';
			ajaxmsg($data,0);
        }
        ajaxmsg($data,1);
    }

	/**
     * 已购买的债权
     *
     */
    public function buydetb()
    {
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		//$arr = AppCommonAction::get_decrypt_json($arr);
		/*$arr['uid'] = 50;
		$this->uid = 50;*/
		$uid = $arr['uid'];
		$page = intval($arr['page'])? intval($arr['page']):1;
        $limit = intval($arr['limit'])? intval($arr['limit']):5;
		$pre = C("DB_PREFIX");
		$_GET['p'] = $page;
        //$uid = intval($this->uid);
		if($uid != $this->uid){
			ajaxmsg('请登陆后查看!',0);
		}
		import("ORG.Util.Page");
        $where = "investor_uid=".$this->uid." and parent_invest_id > 0 and d.sell_uid !=".$this->uid;
        $count = M('borrow_investor i')
            ->join("{$this->pre}debt d ON d.invest_id = i.parent_invest_id")
            ->where($where)->count();
        $p = new Page($count, $limit);
		$totalPage = ceil($count/$limit);
        $Lsql = "{$p->firstRow},{$p->listRows}";
        $lists['data'] = M('borrow_investor i')
            ->join("{$pre}debt d ON d.invest_id = i.parent_invest_id")
            ->join("{$pre}borrow_info b ON i.borrow_id = b.id")
            ->join("{$pre}members m ON d.sell_uid=m.id")
            ->field("d.id,i.id as invest_id, i.borrow_id, i.investor_capital, i.add_time, i.status, d.serialid, d.discount_gold, d.interest_rate, m.user_name,d.period, d.total_period, b.borrow_name,b.borrow_type, d.interest_rate, b.total, b.has_pay")
            ->where("i.investor_uid=".$this->uid." and d.status in(2,4) and d.sell_uid != ".$this->uid)
            ->limit($Lsql)
            ->order('d.status')
            ->select();
        if( !empty($lists['data']) ) {
            for( $i=0;$count=count($lists['data']),$i<$count; $i++ ) {
                $lists['data'][$i]['buy_money'] = $lists['data'][$i]['investor_capital'] * (1-$lists['data'][$i]['discount_gold']/100);
            }
        }
        $_list = array();
		foreach($lists['data'] as $k=>$value){
			$_list[$k]['id'] = $value['id']; //项目id
			$_list[$k]['invest_id'] = $value['invest_id']; //下载id
			$_list[$k]['borrow_name'] = $value['borrow_name']; //项目名称
			$_list[$k]['interest_rate'] = $value['interest_rate']; //利率
			$_list[$k]['total_periods'] = $value['period'].'期/'.$value['total_period'].'期'; //转让期数/总期数
			$_list[$k]['investor_capital'] = $value['investor_capital']; //债权总值
			$_list[$k]['buy_money'] = $value['buy_money']; //购买价格
			$_list[$k]['addtime'] = date('Y-m-d',$value['add_time']); //购买时间
			$_list[$k]['status_type'] = $value['status']; //状态是否可以下载
		}
		
		if(is_array($_list)){
            $data['list'] = $_list;
            $data['totalPage'] = $totalPage;
            $data['nowPage'] =  $page;
        }else{
            $data = '暂无相关数据！';
			ajaxmsg($data,0);
        }
        ajaxmsg($data,1);
    }
	//已认购债券协议下载
	public function debt_download(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		$uid = $arr['uid'];
		$invest_id = $arr['invest_id'];
		if($uid != $this->uid){
			AppCommonAction::ajax_encrypt('请登陆后查看!',0);
		}
		$root = C('WEB_URL');
		AppCommonAction::ajax_encrypt($root.'/Member/debt/agreement?invest_id='.$invest_id,1);
	}
	//转让债券显示页
	public function sellhtml(){
			$jsoncode = file_get_contents("php://input");
			$arr = array();
			$arr = json_decode($jsoncode, true);
			$arr = AppCommonAction::get_decrypt_json($arr);
            $datag = get_global_setting();
            $invest_id = isset($arr['id'])? intval($arr['id']):0;
            !$invest_id && AppCommonAction::ajax_encrypt(L('parameter_error'),0);
            $info = $this->countDebt($invest_id);
            $price = $info['capital']+$info['interest'];
            $debt_fee_rate = $datag['debt_fee']; // 手续费率

            
			$data['capital'] = $info['capital'];  //转让本金：
			$data['price'] = $price;   //手续费：
			$data['debt_fee_rate'] = $debt_fee_rate;   //手续费率
			$data['uncollect'] = $info['uncollect'];  //本期应收利息
			$data['invest_id'] = $info['invest_id'];  //债权id

            

            //判断支付密码
            $vm = getMinfo($this->uid,'m.pin_pass');
            $pin_pass = $vm['pin_pass'];
            $data['has_pin'] = (empty($pin_pass))?"0":"1";
            AppCommonAction::ajax_encrypt($data,1);
        }
	//转让债券
	public function sell_debt()
        {
			$jsoncode = file_get_contents("php://input");
			$arr = array();
			$arr = json_decode($jsoncode, true);
			$arr = AppCommonAction::get_decrypt_json($arr);
            $discount_gold = floatval($arr['discount_gold']);
            $money = floatval($arr['money']);
            $paypass = $arr['paypass'];
            $invest_id = intval($arr['id']);
            if($discount_gold<0 || $discount_gold > 7.5){
                AppCommonAction::ajax_encrypt('折让率超过0.0%-7.5%的范围',0);       
            }
            $deadline = M('investor_detail')->where("invest_id={$invest_id} and repayment_time=0")->getField('deadline');
            $day =   intval(($deadline - time())/ 3600/ 24);
            if($day < 5){
                AppCommonAction::ajax_encrypt('剩余还款时间不得小于5天',0);       
            }
            if($money && $paypass && $invest_id){
                $result = $this->sell($invest_id, $money, $paypass, $discount_gold);
                if($result ==='TRUE')
                {
                    AppCommonAction::ajax_encrypt('债权转让成功');   
                }else{
                    AppCommonAction::ajax_encrypt($result,0);
                }
            }else{
                AppCommonAction::ajax_encrypt('债权转让失败',0);
            }
        }
		/**
     * 支付密码是否正确
     * @param intval $invest_id  // 投资id
     * @param float $price // 转让价格
     * @param password $paypass // 支付密码
     */
    private function checkSell($invest_id, $price, $paypass)
    {
        $vm = getMinfo($this->uid,'m.pin_pass,mm.account_money,mm.back_money');
        if($paypass != $vm['pin_pass']){
            return '支付密码错误';
            exit;
        }
        //如果有散标，并且正在借款或还款中的净值标，则不让其转让
        $version = FS("Webconfig/version");
        if( $version['single'] == 1 ) {
            $net_where = array(
                'borrow_uid' => $this->uid,
                'borrow_type' => BorrowModel::BID_CONFIG_TYPE_NET_ASSETS,
                'borrow_status' => array('in', '-1,0,2,6,8,9')
            );
            $net_borrow = M('borrow_info')->field('id')->where($net_where)->find();
            if( !empty($net_borrow) ) {
                return '您有借款中的净值标，不能进行债权转让';
            }
        }

        return 'TRUE';
    }
	/**
     * 购买方年化利率
     * (原债权人本债权未收利息+折让金)/(认购债权本金-折让金)/此债权到期剩余天数*365*100%=实际年化收益
     *
     * @param mixed $interest_closed  // 待收利息
     * @param mixed $d_gold   //  折让金
     * @param mixed $principal      //转让本金
     * @param mixed $surplus_day      // 剩余天数
     */
    private function subscriberRates($interest_closed, $d_gold,  $principal, $surplus_day)
    {
        $add1 = bcadd($interest_closed, $d_gold, 5);
        $sub = bcsub($principal, $d_gold, 5);
        $mul = bcmul($add1, 365, 5);
        $div1 = bcdiv($mul, $sub, 10);
        $div2 = bcdiv($div1, $surplus_day, 10);
        $rates = bcmul($div2, 100, 2);
        return $rates;
    }
	/**
     * 获取购买债权利率
     * TODO: 等额本息和按天计息的推导利率的方式不同
     * @param mixed $invest_id
     */
    public function getInterestRate($invest_id)
    {
        $invest_id = intval($invest_id);
        $uncollect = $this->getUncollect($invest_id); // 本息未收利息
        $debt_info = $this->Model->field("money, assigned, discount_gold")->where("invest_id={$invest_id}")->find();
        $money =  bcsub($debt_info['money'], $debt_info['assigned'], 2);   // 本金

        if($debt_info['assigned'] > 0.00){
            $uncollect = bcSub($uncollect , ($uncollect/$debt_info['money']*$debt_info['assigned']), 2);
        }
        $d_gold = bcDiv(bcMul($money , $debt_info['discount_gold'], 5), 100, 2);// z折让金

        $interest = M('investor_detail')->where("invest_id={$invest_id} and repayment_time=0")->sum('interest'); // 待收利息

        if($debt_info['assigned'] > 0.00){
            $interest = bcSub($interest , ($interest/$debt_info['money']*$debt_info['assigned']), 2);
        }
        $invest_deadline = M('borrow_investor')->field('deadline')->where("id={$invest_id}")->find();
        $surplus_day =  intval(($invest_deadline['deadline'] - time())/3600/24); // 剩余天数

        $InterestRate = $this->subscriberRates($interest, $d_gold,  $money, $surplus_day);

        return $InterestRate;

    }
	/**
     * 债权转让资金操作记录日志
     * @param int  $uid  // 用户id
     * @param int  $type // 操作类型
     * @param float $money  //操作资金
     * @param float $debt_money // 债权金额，实际影响转让人金额
     * @param string $info //日志说明
     * @param int  $target_uid // 交易对方uid
     */
    public function moneyLog2($uid, $type, $money, $debt_money, $info, $target_uid)
    {
        if(!$target_uid){
            $user['user_name'] = '@网站平台@';
        }else{
            $user = M("members")->field("user_name")->where("id={$target_uid}")->find();
        }

        $money_log = M("member_moneylog")
            ->field("account_money, back_money, collect_money, freeze_money")
            ->where("uid={$uid}")
            ->order("id desc")
            ->find();

        $money_log['affect_money'] = $money;
        $money_log['uid'] = $uid;
        $money_log['type'] = $type;
        $money_log['info'] = $info;
        $money_log['add_time'] = time();
        $money_log['add_ip'] = get_client_ip();
        $money_log['target_uid'] = $target_uid;
        $money_log['target_uname'] = $user['user_name'];
        //--------------------------------------------

        if($type==47){ //转让债权 在转让时一次性去掉待收
            if($money <= 0){
                $money_log['collect_money'] = bcsub($money_log['collect_money'], $debt_money, 2);//待收资金减少债权金额
                $money_log['affect_money'] = -$debt_money;
                $money_log['info'] = $info.",减少待收资金";
            }else{
                $money_log['account_money'] +=  $money;
                $money_log['info'] = $info."{$debt_money}元份额";
            }


        }elseif($type==49){ // 取消债权  一次性增加待收
            $money_log['collect_money'] = bcadd($money_log['collect_money'], $debt_money, 2) ;
            $money_log['affect_money'] = $debt_money;
            $money_log['info'] = $info.",增加待收资金";
        }

        //--------------------------------------------

        $id = M("member_moneylog")->add($money_log);
        return $id;
    }
	/**
     * 债权转让操作
     *
     * @param int $invest_id   // 债权id
     * @param float $price    // 出售价格
     * @param string $paypass // 支付密码
     * @return mixed        // 成功返回TRUE 失败返回失败状态
     */
    public function sell($invest_id, $price, $paypass, $discount_gold)
    {
        $invest_id = intval($invest_id);
        $price = floatval($price);
        $paypass = md5($paypass);
        $db = new Model();
        $db->startTrans();
		
        $check = $this->checkSell($invest_id, $price, $paypass);
        if($check==='TRUE'){ // 检测通过
            $count_invest = $this->countDebt($invest_id);
            $info['invest_id'] = $invest_id;
            $info['sell_uid'] = $this->uid;
            $info['money'] =  $count_invest['capital'];
            $info['period'] = $count_invest['re_num'];
            $info['total_period'] = $count_invest['total'];
            $info['addtime'] = time();
            $info['discount_gold'] = $discount_gold;
            $info['ip'] = get_client_ip();

            $datag = get_global_setting();
            $debt_audit = $datag['debt_audit']; // 债权转让是否审核
            if($debt_audit){
                $info['status'] = 99; //审核
            }else{
                $info['status'] = 2;
                $info['valid'] = time()+$this->time ;

            }

            //如果存在转让记录 则直接更新
            $record = $this->Model->where("invest_id=".$invest_id)->getField('id');
            if($record){
                $this->Model->where("id=".$record)->delete();
            }

            $this->Model->startTrans();
            $debt = $this->Model->add($info);
            if( !empty($debt) ) {
                $debt_rate = $this->getInterestRate($invest_id);
                $update_res = M("debt")->where("id=".$debt)->save(array('interest_rate'=>$debt_rate));
                if(!$update_res) {
                    $this->Model->rollback();
                    return '债权转让失败';
                }
            }

            $investor = M("borrow_investor")->where("id=".$invest_id)->save(array('status'=>14));
            $detail = M("investor_detail")->where("invest_id=".$invest_id." and status=7")->save(array('status'=>14));

            if(!$debt_audit){
                $detail_info = M('investor_detail')->field(" sum(capital) as capital, sum(interest) as interest")->where("invest_id={$invest_id} and status=14")->find();
                $money = bcadd($detail_info['capital'] , $detail_info['interest'], 2);
                $this->moneyLog2($this->uid, 47, 0, $money, "转让{$invest_id}号债权", 0);

                $money_collect = M('member_money')->where("uid={$this->uid}")->getField('money_collect');
                $money_collect = bcsub($money_collect, $money, 2);
                M('member_money')->where("uid={$this->uid}")->save(array('money_collect'=>$money_collect));
            }
            if($debt && !empty($investor) && !empty($detail) ){
                $this->Model->commit();
                return 'TRUE';
            }else{
                $this->Model->rollback();
                return '债权转让失败';
            }

        }else{
            return $check;
        }
    }
	/**
     * 取消转让
     *
     * @param intval $invest_id   //债权id
     * @param strval $paypass // 支付密码
     */
    public function cancel($invest_id, $paypass)
    {
        $invest_id = intval($invest_id);
        $paypass = md5($paypass);
        $vm = getMinfo($this->uid,'m.pin_pass');
        if( empty($paypass) ) {
            return false;
        }
        if($paypass != $vm['pin_pass']){
            return false;
        }
        if($this->cancelDebt($invest_id, 1)){
            return true;
        }else{
            return false;
        }

    }
	/**
     * 撤销转让,债权没有任何人购买过的情况下才可以撤消
     *
     * @param mixed $invest_id  // 债权id
     * @param mixed $type     状态 1 债权人撤销，2债权还款撤销  3转让超时   4还款操作，用户可能提交还款
     */
    public function cancelDebt($invest_id, $type)
    {
        if(!$this->Model->where("invest_id={$invest_id}")->count('id')){
            return false;
        }
        //查询是否有人购买过
        $assigned = $this->Model->where("invest_id={$invest_id}")->getField('assigned');
        if( $assigned > 0 ) {
            return false;
        }
        $status = $this->Model->where("invest_id={$invest_id}")->getField('status');
        $sell_uid = $this->Model->where("invest_id={$invest_id}")->getField('sell_uid');
        $remark = array(
            '1'=>'债权人撤销',
            '2'=>'债权还款撤销',
            '3'=>'转让超时',
        );
        $update = array(
            'status'=>3,
            'cancel_time'=>time(),
            'remark' =>$remark[$type],
        );

        $condition1 = " id={$invest_id} and status=14";
        $condition2 =  " invest_id={$invest_id} and status=14";
        $this->Model->startTrans();
        $borrow_investor = M("borrow_investor")->where($condition1)->save(array("status"=>4));
        $investor_detail = M("investor_detail")->where($condition2)->save(array("status"=>7));
        $invest_detb = $this->Model->where(" invest_id={$invest_id} and status=2")->save($update);
        if($status==2){
            $detail_info = M('investor_detail')->field(" sum(capital) as capital, sum(interest) as interest")->where("invest_id={$invest_id}")->find();
            $money = $detail_info['capital'] + $detail_info['interest'];
            $this->moneyLog2($sell_uid, 49, 0, $money, "取消{$invest_id}号债权", 0);

            $money_collect = M('member_money')->where("uid={$sell_uid}")->getField('money_collect');
            $money_collect = bcadd($money_collect, $money, 2);
            M('member_money')->where("uid={$sell_uid}")->save(array('money_collect'=>$money_collect));
        }

        if($borrow_investor && $invest_detb && $investor_detail){
            $this->Model->commit();
            return true;
        }else{
            $this->Model->rollback();
            return false;
        }
    }
	/**
        *  撤销债权转让
        * 
        */
        public function cancel_debt()
        {
			$jsoncode = file_get_contents("php://input");
			$arr = array();
			$arr = json_decode($jsoncode, true);
			$arr = AppCommonAction::get_decrypt_json($arr);
            $invest_id = $arr['id'];
            $paypsss = strval($arr['paypass']);
            !$invest_id && AppCommonAction::ajax_encrypt(L('parameter_error'), 0);
        
            if($this->cancel($invest_id, $paypsss)) {
                AppCommonAction::ajax_encrypt(L('撤销成功'), 1);
            }else{  
                AppCommonAction::ajax_encrypt(L('撤销失败'), 0);
            }
            
        }
	/**
	**
	**债权转让结束*
	**
	**/

	/**
	**
	**自动投标开始*
	**
	**/
	//自动投标显示页
	public function autolong(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		if(!$this->uid){
			AppCommonAction::ajax_encrypt('请您登陆！', 0);
		}
		

        $map['uid'] = $this->uid;
        $type = intval($arr['type']);  //  1、散标  6、企业直投  7、 定投宝
        $map['borrow_type'] = $type;
        
        $vo = M('auto_borrow')->where($map)->find();
        $list = array();
        if (is_array($vo)){
            //`mxl:autoday`
            $MAXMOONS = 180;
            $vo['is_auto_day'] = ($vo['duration_to'] >= $MAXMOONS) ? 1 : 0; //1：月标，0天标
            $vo['duration_to'] = $vo['duration_to'] % $MAXMOONS; 
            //`mxl:autoday`
            $list = array($vo);
        }
        //$this->assign('list',$list);
       $data['invest_money'] = (empty($list[0]['invest_money'])===true)? 200:$list[0]['invest_money'];
	   $data['min_invest'] = (empty($list[0]['min_invest'])===true)? 50:$list[0]['min_invest'];
	   if($list[0]['interest_rate'] <> 0){
			$data['interest_rate'] = $list[0]['interest_rate'];
			$data['is_interest_rate'] = 1;
	   }else{
			$data['interest_rate'] = '';
			$data['is_interest_rate'] = 0;
	   }
	   if($list[0]['duration_from'] <> 0){
			$data['duration_from'] = $list[0]['duration_from'];
			$data['duration_to'] = $list[0]['duration_to'];
			$data['is_duration_from'] = 1;
	   }else{
			$data['duration_from'] = '';
			$data['duration_to'] = '';
			$data['is_duration_from'] = 0;
	   }
	   if($list[0]['is_auto_day'] == 1){
			$data['is_auto_day'] = 1;
	   }else{
			$data['is_auto_day'] = 0;
	   }
	   if($list[0]['account_money'] <> 0){
			$data['account_money'] = $list[0]['account_money'];
			$data['is_account_money'] = 1;
	   }else{
			$data['account_money'] = '';
			$data['is_account_money'] = 0;
	   }
	   if($list[0]['end_time'] <>0){
			$data['end_time'] = $list[0]['end_time']==0? '':date('Y-m-d',$list[0]['end_time']);
			$data['is_end_time'] = 1;
	   }else{
			$data['end_time'] = '';
			$data['is_end_time'] = 0;
	   }
	   $data['is_use'] = $list[0]['is_use'];
		AppCommonAction::ajax_encrypt($data, 1);
    }
	//自动投标结束页
	public function savelong(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		if(!$this->uid){
			AppCommonAction::ajax_encrypt('请您登陆！', 0);
		}
        $map['uid'] = $this->uid;
		$x = M('members')->field("time_limit,user_leve")->find($this->uid);
		(intval($arr['invest_money'])==0)?$is_full=1:$is_full=0;
		
		
		$data['uid'] = $this->uid;
		$data['account_money'] = $arr['is_account_money']==1? floatval($arr['account_money']):0;
		$data['borrow_type'] = intval($arr['type']);
		$data['interest_rate'] = $arr['is_interest_rate']==1? floatval($arr['interest_rate']):0;
		$data['duration_from'] = intval($arr['is_duration_from'])==1? intval($arr['duration_from']):0;
		$data['end_time'] = intval($arr['is_end_time'])==1? strtotime($arr['end_time']):0;
		
		$data['duration_to'] = intval($arr['is_duration_from'])==1? intval($arr['duration_to']):0;
		//`mxl:autoday`
		$MAXMOONS = 180;
		if (intval($arr['is_auto_day'])==1){
			//此处隐含限制条件是duration_to最大不能超过75个月
			
				$data['duration_to'] += $MAXMOONS;
			
		}
		//`mxl:autoday`
		$data['is_auto_full'] = $is_full;
		$data['invest_money'] = floatval($arr['invest_money']);
		$data['min_invest'] = floatval($arr['min_invest']);
		$data['add_ip'] = get_client_ip();
		$data['add_time'] = time();
		$data['is_use'] = intval($arr['is_use']);
		
		$c = M('auto_borrow')->field('id')->where("uid={$this->uid} AND borrow_type={$data['borrow_type']}")->find();
		if(is_array($c)){
			$data['id'] = $c['id'];
			$newid = M('auto_borrow')->save($data);
			if($newid) AppCommonAction::ajax_encrypt("修改成功",1);
			else AppCommonAction::ajax_encrypt("修改失败，请重试",0);
		}
		else{
			$data['invest_time'] = time();
			$newid = M('auto_borrow')->add($data);
			if($newid) AppCommonAction::ajax_encrypt("添加成功",1);
			else AppCommonAction::ajax_encrypt("添加失败，请重试",0);
		}
	}

	/**
	**
	**自动投标结束*
	**
	**/

	/**
	**
	**站内信开始*
	**
	**/
	//站内信列表
	public function msg_index(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		$page = intval($arr['page'])? intval($arr['page']):1;
        $limit = intval($arr['limit'])? intval($arr['limit']):5;
		if(!$this->uid){
			AppCommonAction::ajax_encrypt('请您登陆！', 0);
		}
        if (isset($arr['status'])){
            if (text($arr['status'])==0) {  // 未读状态
                $map['status']=0;
                
            }
            if (text($arr['status'])==1) {  //已读状态
                $map['status']=1;
                
            }
        }
        $map['uid'] = $this->uid;
        //分页处理
		$_GET['p'] = intval($page);
        import("ORG.Util.Page");
        $count = M('inner_msg')->where($map)->count('id');
        $totalPage = ceil($count/$limit);
        $p = new Page($count, $limit);
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $list = M('inner_msg')->where($map)->order('id DESC')->limit($Lsql)->select();

        //$read=M("inner_msg")->where("uid={$this->uid} AND status=1")->count('id');
        $_list = array();
		foreach($list as $k=>$value){
			$_list[$k]['id'] = $value['id']; //消息id
			$_list[$k]['msg_name'] = '系统通知'; //系统通知
			$_list[$k]['status'] = $value['status']; //读取状态
			$_list[$k]['send_time'] = date('Y-m-d',$value['send_time']); //发送时间
			$_list[$k]['title'] = $value['title']; //消息简介
			$_list[$k]['msg_content'] = '亲爱的'.$glo['web_name'].'用户，'.$value['msg']; //消息内容
		}
		
		if(is_array($_list)){
            $data['list'] = $_list;
            $data['totalPage'] = $totalPage;
            $data['nowPage'] =  $page;
        }else{
            $data = '暂无相关数据！';
			AppCommonAction::ajax_encrypt($data,0);
        }
        AppCommonAction::ajax_encrypt($data,1);
    }
	// 标记已读、未读
	public function changestatus(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
        $id = intval($arr['id']);
        $vo = M("inner_msg")->field('msg')->where("id={$id} AND uid={$this->uid}")->find();
        if(!is_array($vo)){
            AppCommonAction::ajax_encrypt('访问数据不存在~',0);
        }
        M("inner_msg")->where("id={$id} AND uid={$this->uid}")->setField("status",1);
        AppCommonAction::ajax_encrypt();
    }
	/**
	**
	**站内信结束*
	**
	**/

	/**
	**
	**用户资料信息开始*
	**
	**/
	
	/**
	*个人资料
	*
	*/
	public function people(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		
		/*$arr['borrowing_causes']= "1234567890";
		$arr['uid']= 50;
		$arr['address']= "凤凰城A座417房间";
		$arr['income']=  "2000-5000元";
		$arr['profession']=  "按时地方";
		$arr['origin_place']=  "山东省菏泽";
		$arr['education']=  "大专";
		$arr['is_data']=  "2";
		$arr['marry']=  "未婚";*/

		if(!$this->uid){
			AppCommonAction::ajax_encrypt('请您登陆！', 0);
		}
	    $pre = C('DB_PREFIX');
	    $info = get_basic();
	    #最高学历
	    $education = $info['EDUCATION'];
	    $data['educations'] = $education;
	    #月收入
	    $month_income = $info['MONTH_INCOME'];
	    $data['month_incomes'] = $month_income;
	    
	    
	    $model=M('member_info mi');
	    if(intval($arr['is_data'])==1){
	        $field = "mi.*,m.user_phone,ms.id_status,ms.phone_status";
	        $vo = $model->field($field)->join("{$pre}members m ON mi.uid = m.id")->join("{$pre}members_status ms ON mi.uid = ms.uid")->where("mi.uid = {$this->uid}")->find();
	        if(!is_array($vo)) {
				$model->add(array('uid'=>$this->uid));
				$vo = $model->field($field)->join("{$pre}members m ON mi.uid = m.id")->join("{$pre}members_status ms ON mi.uid = ms.uid")->where("mi.uid = {$this->uid}")->find();
			}
				$data['real_name'] = $vo['id_status']==1? hidecard($vo['real_name'],7):'未认证'; //真实姓名：
				$data['idcard'] = $vo['id_status']==1? hidecard($vo['idcard'],1):'未认证';    //身份证号：
				$data['user_phone'] = $vo['phone_status']==1? hidecard($vo['user_phone'],2):'未认证';  //手机号码：
				$data['birthday'] = $vo['id_status']==1? hidecard($vo['idcard'],8):'未认证';   //出生年月：
				$data['origin_place'] = $vo['origin_place'];   //籍贯地址：
				$data['address'] = $vo['address']; //现居住地：
				$data['marry'] = $vo['marry'];  //婚姻状况：
				$data['education'] = $vo['education'];  //最高学历：
				$data['income'] = $vo['income'];  //月收入：
				$data['profession'] = $vo['profession'];  //职业：
				$data['borrowing_causes'] = $vo['borrowing_causes'];  //借款原因：
				AppCommonAction::ajax_encrypt($data, 1);
			
	    }
	    if (intval($arr['is_data'])==2){
	        $model = M('member_info');
	        $savedata = textPost($arr);
	        unset($savedata['is_data']); 
	        $savedata['uid'] = $this->uid;
	        
	        if (false === $model->create($savedata)) {
	            $json['message'] = "修改失败";
	            AppCommonAction::ajax_encrypt($json, 0);
	        }elseif ($result = $model->save()) {
	            $json['message'] = "修改成功";
	            AppCommonAction::ajax_encrypt($json, 1);
	        } elseif ($model->save() == 0){
	            $json['message'] = "修改成功";
	            AppCommonAction::ajax_encrypt($json, 1);
	        } else {
	            $json['message'] = "修改失败或者资料没有改动";
	            AppCommonAction::ajax_encrypt($json, 0);
	        }
	    }else{
			AppCommonAction::ajax_encrypt('非法请求', 0);
	    }
	}
	/**
     * 联系方式
     */
    public function editcontact(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		if(!$this->uid){
			AppCommonAction::ajax_encrypt('请您登陆！', 0);
		}
        $model=M('member_contact_info');
        if($arr['is_data']==1){
            $vo = $model->find($this->uid);
            if(!is_array($vo)) {
				$model->add(array('uid'=>$this->uid));
				$vo = $model->find($this->uid);
			}
            
				$data['contact1'] = $vo['contact1'];   //第一联系人：
				$data['contact1_tel'] = $vo['contact1_tel']; //联系电话：
				$data['contact1_re'] = $vo['contact1_re'];  //关系：
				$data['contact2'] = $vo['contact2'];  //第二联系人：
				$data['contact2_tel'] = $vo['contact2_tel'];  //联系电话：
				$data['contact2_re'] = $vo['contact2_re'];  //关系：
				AppCommonAction::ajax_encrypt($data, 1);
			
            
        }
		
        $savedata = textPost($arr);
		unset($savedata['is_data']); 
        $savedata['uid'] = $this->uid;
        if (false === $model->create($savedata)) {
            AppCommonAction::ajax_encrypt('添加失败！', 0);
        }elseif ($result = $model->save()) {
            /*
            //增加积分
            $this->mclog(31);
            */
            $json['message'] = "修改成功";
            AppCommonAction::ajax_encrypt($json, 1);
        } elseif ($model->save() == 0){
            $json['message'] = "修改成功";
            AppCommonAction::ajax_encrypt($json, 1);
        } else {
            $json['message'] = "修改失败或者资料没有改动";
            AppCommonAction::ajax_encrypt($json, 0);
        }
    }
	/**
     * 单位资料
     */
    public function editdepartment(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		if(!$this->uid){
			AppCommonAction::ajax_encrypt('请您登陆！', 0);
		}
        $model=M('member_department_info');
        if($arr['is_data']==1){
            $vo = $model->find($this->uid);
            if(!is_array($vo)) {
				$model->add(array('uid'=>$this->uid));
				$vo = $model->find($this->uid);
			}
            
				$data['department_name'] = $vo['department_name'];   //单位名称：
				$data['department_tel'] = $vo['department_tel']; //电话：
				$data['department_address'] = $vo['department_address'];  //地址：
				$data['department_year'] = $vo['department_year'];  //工作年限：
				$data['voucher_name'] = $vo['voucher_name'];  //证明人：
				$data['voucher_tel'] = $vo['voucher_tel'];  //证明人手机：
				AppCommonAction::ajax_encrypt($data, 1);
			
        }
    
        $savedata = textPost($arr);
		unset($savedata['is_data']); 
        $savedata['uid'] = $this->uid;
        if (false === $model->create($savedata)) {
            AppCommonAction::ajax_encrypt('添加失败', 0);
        }elseif ($result = $model->save()) {
            /*
            //增加积分
            $this->mclog(32);
            */
            $json['message'] = "修改成功";
            AppCommonAction::ajax_encrypt($json, 1);
        }elseif ($model->save() == 0){
            $json['message'] = "修改成功";
            AppCommonAction::ajax_encrypt($json, 1);
        } else {
            $json['message'] = "修改失败或者资料没有改动";
            AppCommonAction::ajax_encrypt($json, 0);
        }
    }
	/**
     * 财务状况
     */
    public function editfinancial(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		if(!$this->uid){
			AppCommonAction::ajax_encrypt('请您登陆！', 0);
		}
        $model = M('member_financial_info');
        if(intval($arr['is_data'])==1){
            
            $vo = $model->find($this->uid);
            if(!is_array($vo)) {
				$model->add(array('uid'=>$this->uid));
				$vo = $model->find($this->uid);
			}
            
				$data['fin_monthin'] = $vo['fin_monthin'];   //月均收入：
				$data['fin_incomedes'] = $vo['fin_incomedes']; //收入构成描述：
				$data['fin_monthout'] = $vo['fin_monthout'];  //月均支出：
				$data['fin_outdes'] = $vo['fin_outdes'];  //支出构成描述：
				$data['fin_house'] = $vo['fin_house'];  //住房条件：
				$data['fin_housevalue'] = $vo['fin_housevalue'];  //房产价值：
				$data['fin_car'] = $vo['fin_car'];  //是否购车：
				$data['fin_carvalue'] = $vo['fin_carvalue'];  //车辆价值：
				$data['fin_stockcompany'] = $vo['fin_stockcompany'];  //参股企业名称：
				$data['fin_stockcompanyvalue'] = $vo['fin_stockcompanyvalue'];  //参股企业出资额：
				$data['fin_otheremark'] = $vo['fin_otheremark'];  //其他资产描述：
				AppCommonAction::ajax_encrypt($data, 1);
			
        }
        

        $savedata = textPost($arr);
		unset($savedata['is_data']); 
        $savedata['uid'] = $this->uid;
        if (false === $model->create($savedata)) {
            AppCommonAction::ajax_encrypt('添加失败', 0);
        }elseif ($model->save() || ($model->save() == 0)) {
            /*
             //增加积分
             $this->mclog(33);
             */
			 $user_type = M('borrow_apply')->field('is_transfer')->where(array('uid'=>$this->uid))->find();
            #添加申请表
            if ($user_type['is_transfer'] == 0){
                $info = M('borrow_apply')->where(array('uid'=>$this->uid))->find();
                $data['status'] = 1;
                $data['update_time'] = time();
                if(!is_array($info)){
                    $data['uid'] = $this->uid;
                    $data['user_type'] = MembersModel::MEMBERS_IS_TRANSFER_PERSONAL;    //个人借款者身份
                    $data['add_time'] = time(); //记录第一次添加时间
                    M('borrow_apply')->add($data);
                }else{
                    $data['user_type'] = MembersModel::MEMBERS_IS_TRANSFER_PERSONAL;
                    M('borrow_apply')->where(array('uid'=>$this->uid))->save($data);
                }
            }
            $json['message'] = "修改成功";
            AppCommonAction::ajax_encrypt($json, 1);
        }/*elseif ($model->save() == 0){
            $json['message'] = "资料没有改动";
            $json['status'] = 0;
            exit(json_encode($json));
        } */else {
            $json['message'] = "修改失败";
             AppCommonAction::ajax_encrypt($json, 0);
        }
    }
	/**
     * 企业基本资料显示
     */
    public function business(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		
		if(!$this->uid){
			AppCommonAction::ajax_encrypt('请您登陆！', 0);
		}
        $businessModel = M('business_detail');
        $user_id = session('u_id');
        $where = array('uid'=>$user_id);
        $business = $businessModel->where($where)->find();
        $apply = M('borrow_apply')->field('id')->where($where)->find();
        if(intval($arr['is_data'])==2) {
			unset($arr['is_data']); 
            $db = new Model();
            $db->startTrans();
            if ($businessModel->autoCheckToken($arr)){ // 令牌验证
                $data = array(
                    'uid' => $user_id,
                    'business_name' => $arr['business_name'],
                    'legal_person' => $arr['legal_person'],
                    'registered_capital' => $arr['registered_capital'],
                    'city' => $arr['city'],
                    'bianhao' => $arr['bianhao'], //genRandChars(10)
                    'bid_money' => $arr['bid_money'],
                    'bid_duration' => $arr['bid_duration'],
                    'use_type' => $arr['use_type'],
                    'repay_source' => $arr['repay_source'],
                    'add_time' => date('Y-m-d H:i:s')
                );
                if( empty($business) ) {
                    if($db->table(C('DB_PREFIX').'business_detail')->add($data)) {
                        $borrow_apply = array(
                            'uid' => $user_id,
                            'user_type' => MembersModel::MEMBERS_IS_TRANSFER_BUSINESS,
                            'add_time' => time(),
                            'update_time' => time(),
                            'status' => 1
                        );
                        if( !empty($apply) ) {
                            $up_apply = $db->table(C('DB_PREFIX').'borrow_apply')->where($where)->save($borrow_apply);
                        } else {
                            $up_apply =  $db->table(C('DB_PREFIX').'borrow_apply')->add($borrow_apply);
                        }
                        if( $up_apply ) {
                            // 用户身份证号码
                            if( M('member_info')->where($where)->getfield('uid') ) {
                                $info = array(
                                    'idcard' => $arr['idcard']
                                );
                                if( $db->table(C('DB_PREFIX').'member_info')->where($where)->save($info) === false ){
                                    $db->rollback();
                                    AppCommonAction::ajax_encrypt('操作失败', 0);
                                }
                            }else {
                                $info = array(
                                    'uid' => $user_id,
                                    'idcard' => $arr['idcard']
                                );
                                if(!$db->table(C('DB_PREFIX').'member_info')->add($info) ) {
                                    $db->rollback();
                                    AppCommonAction::ajax_encrypt('操作失败', 0);
                                }
                            }
                            $businessModel->commit();
                            AppCommonAction::ajax_encrypt('您的申请资料已经提交，请等待审核！', 1);
                        } else {
                            $db->rollback();
                            AppCommonAction::ajax_encrypt('操作失败', 0);
                        }

                    } else {
                        $db->rollback();
                        AppCommonAction::ajax_encrypt('操作失败', 0);
                    }
                } else { //修改资料
                    if( $businessModel->where(array('uid'=>$user_id))->save($data) !== false ) {
                        $msg = '操作成功';
						
                        $user_type =M('members')->where(array('id'=>$user_id))->getField('is_transfer');
                        if( $user_type == MembersModel::MEMBERS_IS_TRANSFER_NORMAL ) { // 用户被驳回后可以再次申请，但需修改资料
                            $up_ret = array(
                                'status' => 1,
                                'update_time' => time(),
                                'user_type' => MembersModel::MEMBERS_IS_TRANSFER_BUSINESS
                            );
                            if(  !$db->table(C('DB_PREFIX').'borrow_apply')->where($where)->save($up_ret) ) {
                                $db->rollback();
                                AppCommonAction::ajax_encrypt('操作失败', 0);
                            }
                            $msg = '操作成功，请等待审核';
							AppCommonAction::ajax_encrypt($msg, 1);
                        }
                        // 用户身份证号码
                        if( M('member_info')->where($where)->getfield('uid') ) {
                            $info = array(
                                'idcard' => $arr['idcard']
                            );
                            if( $db->table(C('DB_PREFIX').'member_info')->where($where)->save($info) === false ){
                                $db->rollback();
                                AppCommonAction::ajax_encrypt('操作失败', 0);
                            }
                        } else {
                            $info = array(
                                'uid' => $user_id,
                                'idcard' => $arr['idcard']
                            );
                            if(!$db->table(C('DB_PREFIX').'member_info')->add($info) ) {
                                $db->rollback();
                                AppCommonAction::ajax_encrypt('操作失败', 0);
                            }
                        }
                        $businessModel->commit();
                        AppCommonAction::ajax_encrypt('您的申请资料已经提交，请等待审核！', 1);
                    } else {
                        $db->rollback();
                        AppCommonAction::ajax_encrypt('操作失败', 0);
                    }
                }
            } else {
                AppCommonAction::ajax_encrypt('请不要重复提交', 0);
            }
        }
        if( !empty($business) ) {
            //获取用户身份评点号
            $idcard = M('member_info')->where(array('uid'=>$user_id))->getField('idcard');
            if(!empty($idcard)) $business['idcard'] = $idcard;
            $data['business_name'] = $business['business_name'];  //企业名称：
			$data['bianhao'] = $business['bianhao'];   //注册号：
			$data['legal_person'] = $business['legal_person'];   //法人代表：
			$data['idcard'] = $business['idcard'];  //身份证号：
			$data['registered_capital'] = $business['registered_capital'];   //注册资金：
			$data['city'] = $business['city'];    //所在地：
			$data['bid_money'] = $business['bid_money'];    //借款金额：
			$data['bid_duration'] = $business['bid_duration'];   //周期：
			$data['use_type'] = $business['use_type'];   //借款用途：
			$data['repay_source'] = $business['repay_source'];   //还款来源：
			ajaxmsg($data, 1);
        }
        
    }
	/**
     * 上传资料
     */
    public function editdata(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		
		if(!$this->uid){
			AppCommonAction::ajax_encrypt('请您登陆！', 0);
		}
        $integration = FilterUploadType(FS("Webconfig/integration"));
        //$this->assign('integration',$integration);
    
        $Bconfig = require C("APP_ROOT")."Conf/borrow_config.php";
        $to_upload_type = get_upload_type($this->uid);
        $model=M('member_data_info');
        if(intval($arr['is_data'])==1){
			$page = intval($arr['page'])? intval($arr['page']):1;
			$limit = intval($arr['limit'])? intval($arr['limit']):5;
			$_GET['p'] = $page;
            import("ORG.Util.Page");
            $count = $model->where("uid={$this->uid}")->count('id');
			$p = new Page($count, $limit);
			$totalPage = ceil($count/$limit);
			$Lsql = "{$p->firstRow},{$p->listRows}";
            
            $list = $model->field('id,data_url,data_name,add_time,status,type,ext,size,deal_info,deal_credits')->where("uid={$this->uid}")->order("type DESC")->limit($Lsql)->select();
			$_list = array();
			foreach($list as $k=>$value){
				$_list[$k]['id'] = $value['id']; //资料id
				$_list[$k]['data_name'] = $value['data_name']; //文件名
				$_list[$k]['deal_credits'] = $value['status']==0? 1:'积分+'.$value['deal_credits']; //说明
				$_list[$k]['status'] = $Bconfig['DATA_STATUS'][$value['status']]; //审核状态
			}
			
			if(is_array($_list)){
				$data['list'] = $_list;
				$data['totalPage'] = $totalPage;
				$data['nowPage'] =  $page;
			}else{
				$data = '暂无相关数据！';
				AppCommonAction::ajax_encrypt($data,0);
			}
			AppCommonAction::ajax_encrypt($data,1);
            
        }
        
		$photo=stripslashes($arr['uploadfile']);
        $photo=base64_decode($photo);
		mkdir('UF/Uploads/MemberData/'.$this->uid.'/');
        $photo_url = 'UF/Uploads/MemberData/'.$this->uid.'/'.time().'.png';
        $res = file_put_contents($photo_url,$photo);
		if($res=='0'){AppCommonAction::ajax_encrypt('文件上传失败！', 0);}
        $savedata['data_url'] = $photo_url;
        $savedata['size'] = 65;
        $savedata['ext'] = 'png';
        $savedata['data_name'] = text(urldecode($arr['name']));
        $savedata['type'] = 33;//intval($_GET['data_type']);
        $savedata['uid'] = $this->uid;
        $savedata['add_time'] = time();
        $savedata['status'] = 0;
    
        if (false === $model->create($savedata)) {
            AppCommonAction::ajax_encrypt('添加失败', 0);
        }elseif ($result = $model->add()) {
            $json['message'] = "文件上传成功";
            AppCommonAction::ajax_encrypt($json, 1);
        } else {
            $json['message'] = "文件上传失败";
            AppCommonAction::ajax_encrypt($json, 0);
        }
    }
	/**
     * 删除资料
     */
	public function delfile(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		if(!$this->uid){
			AppCommonAction::ajax_encrypt('请您登陆！', 0);
		}
        $id = intval($arr['id']);
    
        $model=M('member_data_info');
        $vo = $model->field("uid,status")->where("id={$id}")->find();
        if(!is_array($vo)) AppCommonAction::ajax_encrypt("提交数据有误！",0);
        else if($vo['uid']!=$this->uid) AppCommonAction::ajax_encrypt("不是你的资料！",0);
        else if($vo['status']==1) AppCommonAction::ajax_encrypt("审核通过的资料不能删除！",0);
        else{
            $newid = $model->where("id={$id}")->delete();
        }
        if($newid) AppCommonAction::ajax_encrypt();
        else AppCommonAction::ajax_encrypt('删除失败，请重试！',0);
    }
	/**
     * 引导页判断是否可以填写下一步用户信息
     */
	 public function yindaoye(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		if(!$this->uid){
			AppCommonAction::ajax_encrypt('请您登陆！', 0);
		}
        $id = intval($arr['id']);
		$member_info = M('member_info')->where("uid={$this->uid}")->select();//个人资料
		
		if($member_info[0]['origin_place']!=''){
			$data['is_member_info'] = 1;
		}else{
			$data['is_member_info'] = 0;
		}
		$member_contact_info = M('member_contact_info')->where("uid={$this->uid}")->select();//联系方式
		if($member_contact_info[0]['contact1']!=''){
			$data['is_member_contact_info'] = 1;
		}else{
			$data['is_member_contact_info'] = 0;
		}
		$member_department_info = M('member_department_info')->where("uid={$this->uid}")->select();//单位资料
		if($member_department_info[0]['department_name']!=''){
			$data['is_member_department_info'] = 1;
		}else{
			$data['is_member_department_info'] = 0;
		}
		$member_financial_info = M('member_financial_info')->where("uid={$this->uid}")->select();//财务状况
		if($member_financial_info[0]['fin_monthin']!=''){
			$data['is_member_financial_info'] = 1;
		}else{
			$data['is_member_financial_info'] = 0;
		}
		$business_detail = M('business_detail')->where("uid={$this->uid}")->select();//企业资料
		if($business_detail[0]['business_name']!=''){
			$data['is_business_detail'] = 1;
		}else{
			$data['is_business_detail'] = 0;
		}
        
        AppCommonAction::ajax_encrypt($data,1);
    }
	//借款页
	public function borrow_index(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		//print_r(MembersModel::MEMBERS_IS_TRANSFER_BUSINESS);exit;
        $version = FS('Webconfig/version');
		$per = C('DB_PREFIX');
		if($this->uid){
			//$this->assign("mstatus", M('members_status')->field(true)->find($this->uid));
			//$this->assign("mdata", getMemberInfoDone($this->uid));
			$minfo = getMinfo($this->uid,true);
			$data['credit_limit'] = floatval($minfo['credit_limit']);//可使用额度
			$data['credit_cuse'] = floatval($minfo['credit_cuse']);//总额度
			$data['credit_use'] = $minfo['credit_cuse']-$minfo['credit_limit'];//已使用额度
			$data['credit_stuats'] = ($minfo['credit_cuse']>0)? 1:0;//额度状态


			$data['netmoney'] = getNet($this->uid);  //可用净值额度
			$_allnetMoney = getFloatValue(0.9*$minfo['money_collect'],2);
			$data['allnetMoney'] = $_allnetMoney;//总净值额度
			$data['allnetMoney_use'] = $_allnetMoney-$data['netmoney'];  //已使用净值额度
			$data['netMoney_stuats'] = ($data['netmoney']>0)? 1:0;  //净值额度状态

			//$this->assign("capitalinfo", getMemberBorrowScan($this->uid));
            $member = BorrowModel::borrow_validate($this->uid);
            $info = M('borrow_apply')->field('user_type')->where(array('uid'=>$this->uid))->find();
            if (is_array($info)){
                $member['apply_type'] = $info['user_type'];
            }
            else $member['apply_type'] = 0;
			
			$members_status = M('members_status')->field('phone_status,id_status')->where(array('uid'=>$this->uid))->find();
            $data['apply_type'] = $member['apply_type'];  //企业状态
            $data['is_transfer'] = $member['is_transfer'];  //会员借款状态
            $data['phone_status'] = $members_status['phone_status'];//手机验证状态
			$data['id_status'] = $members_status['id_status']; //身份认证状态
            $data['validate_user_type'] = $member['validate_user_type'];   //会员借款类型申请状态
			AppCommonAction::ajax_encrypt($data, 1);
		}else{
			AppCommonAction::ajax_encrypt('请先登录！', 0);
		}
	}
	

	/**
	**
	**用户资料信息结束*
	**
	**/

	/**
	**
	**银行卡接口*开始
	**
	**/
	public function bank_index(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		if(!$this->uid){
			AppCommonAction::ajax_encrypt('请先登录', 0);
		}
        $borrowconfig = FS("Webconfig/borrowconfig");
        $ids = M('members_status')->field('id_status,phone_status')->find($this->uid);
        if ($ids['id_status']!=1){
            AppCommonAction::ajax_encrypt('您还未完成身份验证，请先进行实名认证', 0);
        }elseif ($ids['phone_status']!=1){
            AppCommonAction::ajax_encrypt('您还未完成身份验证，请先进行手机认证', 0);
        }
        $voinfo = M("member_info")->field('idcard,real_name')->find($this->uid); 
		$vobank = M("member_banks")->field(true)->where("uid = {$this->uid} and bank_num !=''")->order('id desc')->select();
		$data['is_manual'] = $this->glo['is_manual'];//是否开启手机验证
		$data['edit_bank'] = $this->glo['edit_bank'];  //修改开关
		$mobile = M('members')->getFieldById($this->uid,'user_phone');  
		$data['mobile'] = $mobile;//手机号
		$data['real_name'] = $voinfo['real_name'];//用户银行卡开户名
		$data['bank_name'] = $borrowconfig['BANK_NAME'];//银行列表
		$bank_list = get_bank_type($this->uid);
		$_list = array();
		//$datas = array();
		foreach($vobank as $k=>$value){
			$_list[$k]['id'] = $value['id']; //银行卡id
			$_list[$k]['bank_id'] = $value['bank_name'];//银行id
			$_list[$k]['bank_name'] = $data['bank_name'][$value['bank_name']]; //银行名称
			$_list[$k]['bank_num'] = hidecard($value['bank_num'],12); //银行卡号
			//array_push($datas,$_list);
		}
		
		if(is_array($_list)){
            $data['list'] = $_list;
            
        }else{
            $data = '暂无相关数据！';
			AppCommonAction::ajax_encrypt($data,0);
        }
        AppCommonAction::ajax_encrypt($data,1);
		
    }
    
    /**
     * 获取验证码
     */
    public function getcode()
    {
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
        $result = GlobalModel::send_msg_limit($this->uid);
        if ($result==false){
            AppCommonAction::ajax_encrypt("", 0);
        }
        $smsTxt = FS("Webconfig/smstxt");
        $smsTxt = de_xie($smsTxt);
        $vo = M('members')->field('user_phone')->find($this->uid);
        $phone = $vo['user_phone'];
        //手机号验证
        $map['id'] = $this->uid;
        $code = rand_string($map['id'],6,1,2);
        $res = sendsms($phone, str_replace(array("#UserName#", "#CODE#"), array(session('u_user_name'), $code), $smsTxt['verify_phone']));
        if ($res) {
            AppCommonAction::ajax_encrypt('发送验证码成功！',1);
        }
        else AppCommonAction::ajax_encrypt("发送验证码失败", 0);
    }
    /**
     * 添加银行卡账号
     */
    public function addbank()
    {
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		if(!$this->uid){
			AppCommonAction::ajax_encrypt('请先登录', 0);
		}
        if (empty($arr['vcode'])){
            $this->doadd($arr);
        }else{
            if( is_verify($this->uid,text($arr['vcode']),2,10*60) ){
                $this->doadd($arr);
            }
            else AppCommonAction::ajax_encrypt("验证码错误，请重新输入~",0);
        }
    }
    /**
     * 
     */
    protected function doadd($arr){
        unset($arr['vcode']);
        $data = textPost($arr);
        $arr['uid'] = $this->uid;
		//$arr['add_time'] = time();
        $data['bank_name'] = $arr['bank_name'];
		$data['bank_num'] = $arr['bank_num'];
		$data['bank_province'] = $arr['bank_province'];
		$data['bank_city'] = $arr['bank_city'];
		$data['bank_address'] = $arr['bank_address'];
        $userCount = M('member_banks')->where($arr)->count("id");
        if ($userCount<>0) AppCommonAction::ajax_encrypt('不能重复添加数据！请刷新后再试~',0);
        $data['uid'] = $this->uid;
        $data['add_ip'] = get_client_ip();
        $data['add_time'] = time();
        $newid = M('member_banks')->add($data);
        if($newid){
            MTip('chk2',$this->uid, '', '', null, 1);
            MTip('chk2',$this->uid, '', '', null, 2);
            MTip('chk2',$this->uid, '', '', null, 3);
            //NoticeSet('chk2',$this->uid);
            AppCommonAction::ajax_encrypt('添加成功！',1);
        }
        else AppCommonAction::ajax_encrypt('操作失败，请重试~',0);
    }
    /**
     * 删除银行卡账号
     */
    public function bank_del(){
        $map['id'] = intval($arr['id']);
        $newid = M('member_banks')->where($map)->delete();
        if ($newid){
            AppCommonAction::ajax_encrypt();
        }
        else AppCommonAction::ajax_encrypt("操作失败，请重试~",0);
    } 
    /**
     * 修改
     */
    public function edit(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		if(!$this->uid){
			AppCommonAction::ajax_encrypt('请先登录', 0);
		}
        $id = intval($arr['id']);
        
        $voinfo = M("member_info")->field('idcard,real_name')->find($this->uid);
        $data['real_name'] = $voinfo['real_name'];//用户银行卡开户名
        $vobank = M("member_banks")->field(true)->where("uid = {$this->uid} and id=$id and bank_num !=''")->find();
        
        
        //是否开启手机验证
        $datag = get_global_setting();
        $is_manual = $datag['is_manual'];
        $data['is_manual'] = $is_manual;//是否开启手机验证
        
        //手机号
        $mobile = M('members')->getFieldById($this->uid,'user_phone');  
        $data['mobile'] = $mobile;//手机号
        
        //银行名称
        $bank_list = get_bank_type($this->uid);
        $info = get_bconf_setting();
        $integration = $info['BANK_NAME'];
	    $bank_list[$vobank['bank_name']] = $integration[$vobank['bank_name']];
        $data['bank_name'] = $integration[$vobank['bank_name']];//银行卡名称
        
		
        $data['province'] = M('area')->where("id={$vobank['bank_province']}")->getField('name');//省份列表
        $data['city'] = M('area')->where("id={$vobank['bank_city']}")->getField('name');    //市级
        
		$data['bank_num'] = $vobank['bank_num'];
		$data['bank_address'] = $vobank['bank_address'];
        $data['edit_bank'] = $this->glo['edit_bank'];  //修改开关
		AppCommonAction::ajax_encrypt($data,1);

        
		
		
		
    }
    /**
     * 修改
     */
    public function doedit()
    {
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
        if (empty($arr['vcode'])){
            $this->doedit_do($arr);
        }else{
            if( is_verify($this->uid,text($arr['vcode']),2,10*60) ){
                $this->doedit_do($arr);
            }
            else AppCommonAction::ajax_encrypt("验证码错误，请重新输入~",0);
        }
    }
    /**
     * 
     */
    protected function doedit_do($arr){
        
        //$data = $arr;
        $map['id'] = intval($arr['id']);
        $map['uid'] = $this->uid;
        
        $data['add_ip'] = get_client_ip();
        $data['add_time'] = time();
		$data['bank_name'] = $arr['bank_name'];
		$data['bank_num'] = $arr['bank_num'];
		$data['bank_province'] = $arr['bank_province'];
		$data['bank_city'] = $arr['bank_city'];
		$data['bank_address'] = $arr['bank_address'];
        $newid = M('member_banks')->where($map)->save($data);
        if($newid){
            MTip('chk2',$this->uid, '', '', null, 1);
            MTip('chk2',$this->uid, '', '', null, 2);
            MTip('chk2',$this->uid, '', '', null, 3);
            //NoticeSet('chk2',$this->uid);
            AppCommonAction::ajax_encrypt();
        }
        else AppCommonAction::ajax_encrypt('操作失败，请重试~',0);
    }
	/**
	**
	**银行卡接口*结束
	**
	**/
	//红包列表
	public function bonus_index(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
        $bonus_config = BonusModel::get_bonus_config();
        // 已发放未领取红包
        $status = isset($arr['status']) ? intval($arr['status']) : 1;
        $uid = $this->uid;
        switch($status) {
            case 1:
                $where = array(
                    'uid' => $uid,
                    'status' => BonusModel::BONUS_STATUS_UNRECEIVE,
                    'validate_et' => array('gt', date('Y-m-d H:i:s', time()))
                );
                break;
            case 4:
                $where = array(
                    'receive_user_id' => $uid,
                );
                break;
            case 3:
                $where = array(
                    'uid' => $uid,
                    'status' => BonusModel::BONUS_STATUS_UNRECEIVE,
                    'validate_et' => array('lt', date('Y-m-d H:i:s', time()))
                );
                break;
            default:
                $where = array(
                    'status' => BonusModel::BONUS_STATUS_UNRECEIVE,
                    'validate_et' => array('gt', date('Y-m-d H:i:s', time()))
                );
        }
        $page = isset($arr['page']) ? intval($arr['page']) : 1;
		$_GET['p'] = $page;
        $bonus_items = BonusModel::get_bonus_byPage($where, '*', null, null, $page);
        if( !empty($bonus_items['data']) ) {
            $bonus_items['data'] = BonusModel::get_url_format($bonus_items['data']);
            $send_uids = only_array($bonus_items['data'], 'uid');
            $map['id'] = array('in', implode(',', $send_uids));
            $user_items = M('members')->field('user_name,id')->where($map)->select();
            if( !empty($user_items) ) {
                for($i=0; $i<count($bonus_items['data']); $i++) {
                    foreach( $user_items as $val ) {
                        if( $bonus_items['data'][$i]['uid'] == $val['id'] ) {
                            $bonus_items['data'][$i]['source_name'] = $val['user_name'];
                            break;
                        }
                    }
                }
            }
        }
		$_list = array();
		foreach($bonus_items['data'] as $k=>$value){
			if(intval($arr['status'])==1){
				$_list[$k]['create_time'] = date('Y-m-d',strtotime($value['create_time'])); //生成时间
				$_list[$k]['validate_et'] = date('Y-m-d',strtotime($value['validate_et'])); //过期时间
				$_list[$k]['share_url'] = $value['share_url']; //生成连接
			}elseif(intval($arr['status'])==4){
				$_list[$k]['take_time'] = date('Y-m-d',strtotime($value['take_time'])); //领取时间
				$_list[$k]['source_name'] = $value['source_name']; //来源
			}else{
				$_list[$k]['validate_et'] = date('Y-m-d',strtotime($value['validate_et'])); //过期时间
				$_list[$k]['weilingqu'] = '未领取'; //来源
			}
			$_list[$k]['bonus_money'] = $value['bonus_money']; //金额
			
		}
		
		if(is_array($_list)){
            $data['list'] = $_list;
            $data['totalPage'] = 1;
            $data['nowPage'] =  $page;
        }else{
            $data = '暂无相关数据！';
			AppCommonAction::ajax_encrypt($data,0);
        }
        AppCommonAction::ajax_encrypt($data,1);
        
    }
	 // 生成红包
    public function send()
    {
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
        $bonus_money = floatval($arr['bonus_money']);//金额
        if( BonusModel::validate_bonus_money($bonus_money) == false ) {
            $ret = '红包金额填写有误';
            AppCommonAction::ajax_encrypt($ret, 0);
        }
        $mm = getMinfo($this->uid);
        if( $mm['user_account'] < $bonus_money ) {
            $ret = '账户余额不足';
            AppCommonAction::ajax_encrypt($ret, 0);
        }else {
            $config_id = getMillisecond();
            //生成配置文件
            $data = array(
                'config_id' => $config_id,
                'uid' => $this->uid,
                'bonus_money' => $bonus_money,
                'source_type' => BonusModel::BONUS_SOURCE_TYPE_USER,
                'bonus_type' => 1,
                'send_way' => 2,
                'take_way' => 1,
                'validate_st' => date('Y-m-d', time()),
                'validate_et' => date('Y-m-d', strtotime("+30 days", time())) //默认有效期一个月
            );
            $db = M();
            $db->startTrans();
            if( BonusModel::create_bonus($data) ) {
                //冻结用户的金钱
                $result = memberMoneyLog($this->uid, 55, -$bonus_money, '生成红包链接成功,冻结金额'.$bonus_money, '', '', 0, $db);
                if( $result == true ) {
                    $db->commit();
					$bonus_items = BonusModel::get_bonus_byPage("uid = {$this->uid}", '*', null, null, 1);
					$bonus_items['data'] = BonusModel::get_url_format($bonus_items['data']);
					//$bao = M('bonus')->where("uid = {$this->uid}")->find();

					$ret['url'] = $bonus_items['data'][0]['share_url'];
                    $ret['message'] = '亲~，您的红包已生成！';
                    AppCommonAction::ajax_encrypt($ret, 1);
                }else{
                    $db->rollback();
                    $ret = '生成失败';
                    AppCommonAction::ajax_encrypt($ret, 0);
                }
            }else {
                $ret = '生成失败';
                AppCommonAction::ajax_encrypt($ret, 0);
            }
        }
    }
	//积分记录和积分兑换列表
	public function integral_index(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
        //memberCreditsLog($this->uid, 1, -5, "调试测试-5分");
        
		$expconf = FS("Webconfig/expconf");
        $yq = $expconf[4];
		$data['money'] = $yq['num']*$yq['money'];
		if(intval($arr['code'])==1){
			// 投资积分记录
			$page = intval($arr['page'])? intval($arr['page']):1;
			$limit = intval($arr['limit'])? intval($arr['limit']):5;
			$_GET['p'] = $page;
			import('ORG.Util.Page');
			$count      = M('member_integrallog')->where("uid=".$this->uid)->count();
			$p = new Page($count, $limit);
			$totalPage = ceil($count/$limit);
			$Lsql = "{$p->firstRow},{$p->listRows}";
			$list = M('member_integrallog')->field(true) ->where("uid=".$this->uid)->limit($Lsql)->order("id desc")->select();
			$_list = array();
			foreach($list as $k=>$value){
				$_list[$k]['add_time'] = date('Y-m-d H:i:s',$value['add_time']); //时间
				$_list[$k]['affect_integral'] = $value['affect_integral'] > 0? '获取':'使用'; //类型
				$_list[$k]['info'] = $value['info']; //详情
				$_list[$k]['integral_log'] = $value['affect_integral'] > 0? '+'.$value['affect_integral']:$value['affect_integral']; //积分
			}
			
			if(is_array($_list)){
				$data['list'] = $_list;
				$data['totalPage'] = $totalPage;
				$data['nowPage'] =  $page;
			}else{
				$data = '暂无相关数据！';
				AppCommonAction::ajax_encrypt($data,0);
			}
			
		}elseif(intval($arr['code'])==2){
        //投资积分规则
        /*$_P_fee = get_global_setting();
        $invest_integral = $_P_fee['invest_integral'];
        $this->assign('invest_integral', $invest_integral);*/
        // 积分兑换
			$reddemconf = FS("Webconfig/reddemconf");
			$_list = array();
			$listya = array();
			foreach($reddemconf as $k=>$value){
				$data['goodid'] = $k; //抵现券id
				$data['money'] = $value['money']; //抵现券金额
				$data['info'] = '投资每满'.$value['invest_money'].'元可以抵'.$value['money'].'元,有效期'.$value['expired_time'].'个月'; //简介
				$data['integral'] = $value['integral']; //需要积分
				array_push($listya,$data);
			}
			
			if(is_array($listya)){
				$data['list'] = $listya;
			}else{
				$data = '暂无相关数据！';
				AppCommonAction::ajax_encrypt($data,0);
			}
        }
		$integral_info = M("members")->field('integral, invest_credits,active_integral')->where("id=".$this->uid)->find();

		$data['integral'] = $integral_info['integral'];//累计获取投资积分
		$data['active_integral'] = $integral_info['active_integral'];//累计获取投资积分
		$data['integral_use'] = $integral_info['integral']<>$integral_info['active_integral']? $integral_info['integral']-$integral_info['active_integral']:0;//累计获取投资积分
        
		AppCommonAction::ajax_encrypt($data,1);
    }
	//积分兑换
	public function ajaxcredit()
    {
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
        if($arr['amount']!='' && $arr['goodid']!=''){ // 提交兑换券
            $msg = array(
                'data'=>'',
                'code'=>0,
                'message'=>'兑换成功',
            );
            
            $reddemconf = FS("Webconfig/reddemconf"); 
            
            $amount = intval($arr['amount']);
            $goodid = intval($arr['goodid']);
            $need_integral = $amount* $reddemconf[$goodid]['integral'];
            
            $integral_info = M("members")->field('active_integral,integral')->where("id=".$this->uid)->find();
            $active_integral = $integral_info['active_integral']; //兑换前可用积分
            $integral = $integral_info['integral'];//总积分
            if(!$amount || !$goodid){
                $msg['code'] = 100;
                $msg['message'] = '参数有误！';  
				AppCommonAction::ajax_encrypt($msg,0);
            }elseif($active_integral < $need_integral){
                $msg['code'] = 101;
                $msg['message'] = '您的积分不足！';
				AppCommonAction::ajax_encrypt($msg,0);
            }
            
            $remark = "积分兑换一张".$reddemconf[$goodid]['money']."元优惠券，投资".$reddemconf[$goodid]['invest_money']."元可用"; 
            $expired_time = strtotime("+{$reddemconf[$goodid]['expired_time']} month");  
            
            M()->startTrans();
                for($i=1; $i<=$amount; $i++){
                    $expand_money['uid'] =  $this->uid;
                    $expand_money['money'] = $reddemconf[$goodid]['money'];
                    $expand_money['remark'] = $remark;
                    $expand_money['expired_time']  =  $expired_time;
                    $expand_money['add_time'] = time(); 
                    $expand_money['orders'] = "DH".build_order_no(); 
                    $expand_money['invest_money'] = $reddemconf[$goodid]['invest_money'];
                    $expand_money['type'] = 98;    
                    $expand_money['source_uid'] = 0;
                    
                    $exp_id = M('expand_money')->add($expand_money);
                    if(!$exp_id) break;
                }    
            
                $active_integral = $active_integral - $need_integral;   //兑换后可用积分
                $m_up_id = M("members")->save(array('id'=>$this->uid, 'active_integral'=>$active_integral));
                
                $data['uid'] = $this->uid;
                $data['type'] = 1;
                $data['affect_integral'] = -$need_integral;  //兑换消耗的积分
                $data['active_integral'] = $active_integral;    //活跃积分
                $data['account_integral'] = $integral;//总积分
                $data['info'] = "兑换优惠券使用".$need_integral."分";
                $data['add_time'] = time();
                $data['add_ip'] = get_client_ip();
                $credits_id = D('member_integrallog')->add($data);

            if($exp_id && $m_up_id && $credits_id){
                M()->commit();   
            }else{
                M()->rollback();
                $msg['code'] = 102;
                $msg['message'] = '兑换失败，请联系客服！';
				AppCommonAction::ajax_encrypt($msg,0);
            }

            AppCommonAction::ajax_encrypt($msg,0);  
                
        }
    }
	/**
	**
	**宝付充值银行卡列表(宝付不再应用这个接口)
	**
	**/
	public function chk_bank_index(){
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		if(!$this->uid){
			AppCommonAction::ajax_encrypt('请先登录', 0);
		}
        $borrowconfig = FS("Webconfig/borrowconfig");
        $ids = M('members_status')->field('id_status,phone_status')->find($this->uid);
        if ($ids['id_status']!=1){
            AppCommonAction::ajax_encrypt('您还未完成身份验证，请先进行实名认证', 0);
        }elseif ($ids['phone_status']!=1){
            AppCommonAction::ajax_encrypt('您还未完成身份验证，请先进行手机认证', 0);
        }
		$vobank = M("member_banks")->field(true)->where("uid = {$this->uid} and bank_num !=''")->order('id desc')->select();
		$bank_name = $borrowconfig['BANK_NAME'];//银行列表
		$_list = array();
		//$datas = array();
		foreach($vobank as $k=>$value){
			$_list[$k]['bank_id'] = $value['bank_name'];//银行id
			$_list[$k]['bank_name'] = $bank_name[$value['bank_name']]; //银行名称
			$_list[$k]['bank_num'] = $value['bank_num']; //银行卡号
			//array_push($datas,$_list);
		}
		
		if(is_array($_list)){
            $data['list'] = $_list;
            
        }else{
            $data = '暂无相关数据！';
			AppCommonAction::ajax_encrypt($data,0);
        }
        AppCommonAction::ajax_encrypt($data,1);
		
    }


	 //借款总表
    public function summaList()
    {
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
        $now = time();
        $map = array();
        $order = "b.id DESC";
        $pre = C('DB_PREFIX');
        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";
        $rptype = array_keys($Bconfig['REPAYMENT_TYPE']);
        $typename = array("1" => "ing", "2" => "pay", "3" => "late", "4" => "fail", "5" => "done");
        $type = intval($arr['type']);
		//$type = 1;
        $typestr = getBorrowStatus($typename[$type]);
		$types = array(
            "1" => array(    //发标中
                "pay" => 0,
                "status" => array("in", "{$typestr}"),
//                "rate_type" => BorrowModel::BID_CONFIG_RATE_TYPE_FULL_BORROW,
                "width" => array(143, 163, 163, 163, 133),
                "name" => array("还款方式", "借款金额", "借款进度", "借款时间", "操作"),
                "code" => array("repayment_type", "borrow_money", "progress", "add_time", "option")
            ),
            "2" => array(    //偿还中
                "pay" => 1,
                "status" => array("exp", "in ({$typestr}) AND d.status in (4,6,7,14) AND d.deadline > {$now}"),
                "width" => array(129, 127, 127, 127, 127, 129),
                "name" => array("还款方式", "借款金额", "已还金额", "年化利率", "还款期限", "即将还款期限"),
                "code" => array("repayment_type", "borrow_money", "receive", "borrow_interest_rate", "borrow_duration", "deadline")
            ),
            "3" => array(    //已逾期
                "pay" => 1,
                "status" => array("exp", "in ({$typestr}) AND d.status in (4,6,7) AND {$now} > d.deadline"),
                "width" => array(85, 85, 85, 85, 85, 85, 85, 85),
                "name" => array("待还本金", "待还利息", "待付罚息", "待付催收费", "待付总金额", "应还日期", "当前/总期", "逾期天数"),
                "code" => array("capital", "interest", "expired_money_now", "call_fee_now", "expired_total", "deadline", "sort_total", "expired_time")
            ),
            "4" => array(    //已失败
                "pay" => 0,
                "status" => array("in", "{$typestr}"),
                "width" => array(143, 163, 163, 163, 133),
                "name" => array("还款方式", "借款金额", "借款时间", "标的状态", "备注"),
                "code" => array("repayment_type", "borrow_money", "borrow_duration", "borrow_status", "remark")
            ),
            "5" => array(    //已还清
                "pay" => 2,
                "status" => array("in", "{$typestr}"),
                "width" => array(143, 163, 163, 163, 133),
                "name" => array("还款方式", "借款金额", "借款期限", "借款时间", "已还本息"),
                "code" => array("repayment_type", "borrow_money", "borrow_duration", "add_time", "receive")
            )
        );
        if (empty($types[$type]['order']) === false) {
            $order = $types[$type]['order'];
        }
        if (empty($types[$type]['status']) === false) {
            $map['b.borrow_status'] = $types[$type]['status'];
        }
        
        //$perpage = 5;//$_POST['perpage'];
        //$curpage = 1;//$_POST['curpage'];
        $map['b.borrow_uid'] = $this->uid;
		$page = intval($arr['page'])? intval($arr['page']):1;
		$limit = intval($arr['limit'])? intval($arr['limit']):5;
        $_GET['p'] = $page;
		import('ORG.Util.Page');
	
        $field = "b.id as bid, b.borrow_name, b.borrow_status, b.add_time, b.borrow_money, b.has_borrow, b.borrow_interest_rate, b.borrow_duration, b.duration_unit, b.borrow_type, b.repayment_type, ";
        $field .= "min(d.deadline) as deadline, max(d.status in (6,7)) as pay_status, d.status as d_status, sum(d.capital) as capital, sum(d.interest) as interest, d.sort_order, d.total, ";
        $field .= "sum(d.receive_capital + d.receive_interest + if(d.repayment_time > 0, d.interest_fee, 0)) as receive, if(v.deal_time_2 > 0, v.deal_info_2, v.deal_info) as remark";
       if (!$type || $type == 1) {
            if (!empty($map['d.deadline'])) {
                $map['b.add_time'] = $map['d.deadline'];
            }
            unset($map['d.deadline']);
            $field = "b.id as bid, b.borrow_name, b.borrow_status, b.add_time, b.borrow_money, b.has_borrow, b.borrow_interest_rate, b.borrow_duration, b.duration_unit, b.borrow_type, b.repayment_type";
            $count = M("borrow_info b")->where($map)->count("distinct b.id");
			$totalPage = ceil($count/$limit);
//            $limit = calPage($count, $curpage, $perpage);
            $re = M("borrow_info b")->field($field)->where($map)->select();
        } else {
            $map['bi.parent_invest_id'] = 0;
            $count = M("borrow_info b")
                ->join("{$pre}investor_detail d ON b.id = d.borrow_id")
                ->join("{$pre}borrow_investor bi ON bi.id = d.invest_id")
                ->where($map)->count("distinct b.id");

			$p = new Page($count, $limit);
			$totalPage = ceil($count/$limit);
			$Lsql = "{$p->firstRow},{$p->listRows}";
            //$limit = calPage($count, $curpage, $perpage);

            $re = M("borrow_info b")->field($field)->where($map)
                ->join("{$pre}investor_detail d ON b.id = d.borrow_id")
                ->join("{$pre}borrow_investor bi ON bi.id = d.invest_id")
                ->join("{$pre}borrow_verify v ON b.id = v.borrow_id")
                ->order($order)->group("bid")->limit($Lsql)->select();
        }
		
        foreach ($re as $k => $v) {
            $re[$k]['option'] = (canErase($v) === false || $v['borrow_type'] == 6 || $v['borrow_type'] == 7) ? "--" : "<span style='color:#3181d8; cursor:pointer;' class='do_erase' _bid='{$v['bid']}'>撤销</span>";
            $re[$k]['deadline'] = date("Y-m-d", $v['deadline']);
            $re[$k]['add_time'] = date("Y-m-d", $v['add_time']);
            $re[$k]['borrow_url'] = getBorrowUrl($v['borrow_type'], $v['bid']);
            $re[$k]['borrow_status'] = $Bconfig['BORROW_STATUS'][$v['borrow_status']];
            $re[$k]['remark'] = (empty($v['remark']) === false) ? $v['remark'] : "--";
            $re[$k]['repayment_type'] = $Bconfig['REPAYMENT_TYPE'][$v['repayment_type']];
            $re[$k]['progress'] = getFloatValue($v['has_borrow'] * 100 / $v['borrow_money'], 0) . "%";
            $re[$k]['borrow_duration'] = $v['borrow_duration'] . BorrowModel::get_unit_format($v['duration_unit']);
            if ($type == 3) {    //逾期
                $re[$k]['expired_time'] = $expired_days = getExpiredDays($v['deadline']);
                $re[$k]['call_fee_now'] = getExpiredCallFee($expired_days, $v['capital'], $v['interest']);
                $re[$k]['expired_money_now'] = getExpiredMoney($expired_days, $v['capital'], $v['interest']);
                $re[$k]['expired_total'] = getFloatValue($v['capital'] + $v['interest'] + $re[$k]['expired_money_now'] + $re[$k]['call_fee_now'], 2);
                $re[$k]['sort_total'] = $v['sort_order'] . "/" . $v['total'];
            }
        }
		if(is_array($re)){
				$data['list'] = $re;
				$data['totalPage'] = $totalPage;
				$data['nowPage'] =  $page;
			}else{
				$data = '暂无相关数据！';
				AppCommonAction::ajax_encrypt($data,0);
			}
		AppCommonAction::ajax_encrypt($data,1);
        
        
    }
	//还款列表
	public function paylist()
    {
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		$pre = C('DB_PREFIX');
		/*$arr['bid'] = 98;
		$this->uid = 6;*/
        $bid = intval($arr['bid']);
		$page = intval($arr['page'])? intval($arr['page']):1;
		$limit = intval($arr['limit'])? intval($arr['limit']):5;
        $_GET['p'] = $page;
		import('ORG.Util.Page');
		$map['d.borrow_id'] = $bid;
        $map['d.borrow_uid'] = $this->uid;
		$count = M("investor_detail d")
            ->join("{$pre}borrow_investor bi on bi.id=d.invest_id")
            ->where($map)
            ->count("distinct d.sort_order");

		$p = new Page($count, $limit);
		$totalPage = ceil($count/$limit);
		$Lsql = "{$p->firstRow},{$p->listRows}";
        $field = "d.deadline, sum(d.capital) as capital, sum(d.interest) as interest, d.status, sum(d.substitute_money) as substitute_money, d.sort_order,b.has_pay";
        $dinfo = M("investor_detail d")->field($field)
            ->join("{$pre}borrow_investor bi on bi.id=d.invest_id")
            ->join("{$pre}borrow_info b on b.id = d.borrow_id")
            ->where($map)->order("d.sort_order asc,d.status ASC")->group("d.sort_order")->limit($Lsql)->select();
		
        //查询已回款金额
        $receive_detail = InvestorDetailModel::get_has_receive($bid);
        if( !empty($receive_detail) ) {
            foreach( $receive_detail as $value ) {
                foreach( $dinfo as $key=>$val ) {
                    if( $value['sort_order'] == $val['sort_order'] ) {
                        $dinfo[$key]['receive'] = $value['receive'];
                        break;
                    }
                }
            }
        }

        // 如果has_pay小于当期，则当期出现"还款"接口还款
        if (!empty($dinfo)) {

            for ($i = 0; $i < count($dinfo); $i++) {
                if ($dinfo[$i]['has_pay'] < $dinfo[$i]['sort_order']) {
                    $dinfo[$i]['need_pay'] = 1; // 需要还款
                }else{
					$dinfo[$i]['need_pay'] = 0;
				}
				$dinfo[$i]['deadline'] = date("Y-m-d", $dinfo[$i]['deadline']);
            }
            // 查看取出来的状态是不是都是14，如果都是14，则重新查询出不带14的数据，正确情况下是不会出现都是14的。如果都是14，说明数据量非常小，也无太多消耗。
            $status_all = array_unique(only_array($dinfo, 'status'));
            if (count($status_all) == 1 && $status_all[0] == 14 && $filter != 14) {
                // 通过$filter再判断，防止出现死循环
                header("Location: " . DOMAIN . $_SERVER['REQUEST_URI'] . 'filter=14');
            }
        }

        $re = calExpired($dinfo);
		if(is_array($re)){
				$binfo = M("borrow_info")->field("id, borrow_name, borrow_type, add_time, borrow_money, has_borrow, borrow_times")->where("id = {$bid}")->find();
				$data['borrow_money'] = $binfo['borrow_money'];
				$data['has_borrow'] = $binfo['has_borrow'];
				$data['borrow_times'] = $binfo['borrow_times'];
				$data['list'] = $re;
				$data['totalPage'] = $totalPage;
				$data['nowPage'] =  $page;
			}else{
				$data = '暂无相关数据！';
				AppCommonAction::ajax_encrypt($data,0);
			}
		AppCommonAction::ajax_encrypt($data,1);
    }
	//还款
    public function dopay()
    {
        $jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);

        $bid = intval($arr['bid']);
        $sort = intval($arr['sort']);
        $re = borrowRepayment($bid, $sort);
        if ($re === true) {
            $json['mssage'] = "还款成功";
			AppCommonAction::ajax_encrypt($json,1);
        } else if ($re === false) {
            $json['mssage'] = "还款失败，请联系平台技术支持";
			AppCommonAction::ajax_encrypt($json,0);
        } else {
            $json['mssage'] = $re;
			AppCommonAction::ajax_encrypt($json,0);
        }
		AppCommonAction::ajax_encrypt($json);
        
    }
	//撤销
    public function doerase()
    {
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);

        $designer = FS("Webconfig/designer");
        
        $bid = intval($arr['bid']);
        $binfo = M("borrow_info")->field("has_borrow, borrow_status, borrow_type")->where("id = {$bid} and borrow_uid=".$this->uid)->find();
        if ($binfo['borrow_type'] == 6 || $binfo['borrow_type'] == 7) {
            $json['mssage'] = $designer[6] . "和" . $designer[7] . "不能进行撤销操作";
            AppCommonAction::ajax_encrypt($json,0);
            exit;
        }
        if (empty($binfo) === false && is_array($binfo) === true) {
            if (canErase($binfo) === true) {
                if (getBorrowType($binfo['borrow_type']) === "man") {
                    M("borrow_info")->where("id = {$bid}")->delete();
                } else {
                    M("borrow_info")->where("id = {$bid}")->delete();
                    M("borrow_detail")->where("borrow_id = {$bid}")->delete();
                }
                $json['mssage'] = "完成{$bid}号借款标撤销操作";
				AppCommonAction::ajax_encrypt($json,1);
            } else {
                $json['mssage'] = "{$bid}号借款标无法撤销，已通过初审（散标）或已有会员投资此项目，请重新检查";
				AppCommonAction::ajax_encrypt($json,0);
            }
        } else {
            $json['mssage'] = "{$bid}号借款标不存在";
			AppCommonAction::ajax_encrypt($json,0);
        }
        
    }

	//申请vip方法
	public function apply(){
 		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode,true);
		/*$arr['des'] = "\U586b\U5199\U7533\U8bf7VIP\U4f1a\U5458\U7684\U8bf4\U660e";
		$arr['kfid'] = 112;
		$arr['uid'] = 23;
		$this->uid = 23;*/

		$uid = intval($arr['uid']);//用户id
		$kfid = intval($arr['kfid']);
		$des = text($arr['des']);
		if($uid != $this->uid){
			ajaxmsg('登陆信息有误',0);
		}

		$vx = M('vip_apply')->where("uid={$this->uid} AND status=0 AND loanno<>''")->count("id");
		if($vx>0) ajaxmsg('您的VIP申请已在处理中，请耐心等待',0);
		
		$mmdata=M('member_money')->where("uid={$this->uid}")->find();
		$datag = get_global_setting();
		$mmpd=$mmdata['account_money']+$mmdata['back_money']-$datag['fee_vip'];
		if($mmpd<0){
			ajaxmsg('您的余额不足,请充值后再申请',0); 
		}
		$orders = build_order_no();
		$savedata['kfid'] = $kfid;
		$savedata['uid'] = $this->uid;
		$savedata['des'] = $des;
		$savedata['add_time'] = time();
		$savedata['status'] = 4;
		$savedata['orders'] = $orders;
        $savedata['vip_fee'] = $datag['fee_vip'];
		
		$newid = M('vip_apply')->add($savedata);
		
		if($newid){
			if($savedata['vip_fee'] > 0 || $datag['fee_vip']>0.00){
				import("ORG.Loan.Escrow");
                $loan = new Escrow();
                $loanconfig = FS("Webconfig/loanconfig"); 
                $pay_qdd = M("escrow_account")->field('*')->where("uid={$this->uid}")->find(); 
                $loanList[] = $loan->loanJsonList($pay_qdd['qdd_marked'], $loanconfig['pfmmm'], $orders, 'VIP_'.$newid , $datag['fee_vip'], '','VIP认证',"VIP认证费用");
                $data['loanJsonList'] = $loanList;
				$data['PlatformMoneymoremore'] =  $loanconfig['pfmmm'];
                //$data['returnURL'] = C('WEB_URL').U("vipReturn");
                $data['notifyURL'] = C('WEB_URL').U("member/notify/vip");
				ajaxmsg($data,1); 
			}else{
				$result = M('vip_apply')->where("id={$newid}")->save(array('status'=>0));
			    ajaxmsg('提交成功，等待审核,',0); 
			}

        }else{
        	ajaxmsg('保存失败，请重试',0); 
        } 
             
	}


	public function vipstatus(){
			$jsoncode = file_get_contents("php://input");
    		 $arr = json_decode($jsoncode,true);
       		 $uid = intval($arr['uid']);
       		
        if($uid != $this->uid){
            ajaxmsg('登陆信息有误',0);
        }
        $status=M('vip_apply')->field("status")->where("uid={$uid}")->find();
          if(!$status){
        	ajaxmsg('你还未申请vip',0);
        }
        if($status['status']==0){
              ajaxmsg('待审核',0);
        }else if($status['status']==1){
             ajaxmsg('通过',1);
        }else if($status['status']==2){
             ajaxmsg('未通过',2);
        }else if($status['status']==3){
             ajaxmsg('处理中',3);
        }


	}

	
/*申请vip页面*/
	public function vip_list(){
		$jsoncode = file_get_contents("php://input");
		 $arr = json_decode($jsoncode,true);
		 $uid = intval($arr['uid']);
	
        if($uid != $this->uid){
            ajaxmsg('登陆信息有误',0);
        }
		$vo = M('members')->field('user_leve,time_limit')->find($this->uid);
		if($vo['user_leve']>0 && $vo['time_limit']>time()){
			$data['vipTime']=$vo['time_limit'];//到期时间
		}
		$vx = M('vip_apply')->where("uid={$this->uid} AND status=3")->count("id");
		if($vx>0)  ajaxmsg("您的VIP申请已在处理中，请耐心等待",0);
		$map['is_kf'] = 1;
		$count = M('ausers')->where($map)->count('id');
		if($count==0) unset($map['area_id']);	
		$count = M('ausers')->where($map)->count('id');
		$list = M('ausers')->where($map)->select();
		$data['list']=$list;//客服列表
		$data['count']=$count;//客服人数
		$datag = get_global_setting();
		$data['fee_vip']=$datag['fee_vip'];
		ajaxmsg($data);
		
	}

	 //借款总表
    public function summaList()
    {
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		
        $now = time();
        $map = array();
        $order = "b.id DESC";
        $pre = C('DB_PREFIX');
        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";
        $rptype = array_keys($Bconfig['REPAYMENT_TYPE']);
        $typename = array("1" => "ing", "2" => "pay", "3" => "late", "4" => "fail", "5" => "done");
        $type = intval($arr['type']);
		//$type = 1;
        $typestr = getBorrowStatus($typename[$type]);
		$types = array(
            "1" => array(    //发标中
                "pay" => 0,
                "status" => array("in", "{$typestr}"),
//                "rate_type" => BorrowModel::BID_CONFIG_RATE_TYPE_FULL_BORROW,
                "width" => array(143, 163, 163, 163, 133),
                "name" => array("还款方式", "借款金额", "借款进度", "借款时间", "操作"),
                "code" => array("repayment_type", "borrow_money", "progress", "add_time", "option")
            ),
            "2" => array(    //偿还中
                "pay" => 1,
                "status" => array("exp", "in ({$typestr}) AND d.status in (4,6,7,14) AND d.deadline > {$now}"),
                "width" => array(129, 127, 127, 127, 127, 129),
                "name" => array("还款方式", "借款金额", "已还金额", "年化利率", "还款期限", "即将还款期限"),
                "code" => array("repayment_type", "borrow_money", "receive", "borrow_interest_rate", "borrow_duration", "deadline")
            ),
            "3" => array(    //已逾期
                "pay" => 1,
                "status" => array("exp", "in ({$typestr}) AND d.status in (4,6,7) AND {$now} > d.deadline"),
                "width" => array(85, 85, 85, 85, 85, 85, 85, 85),
                "name" => array("待还本金", "待还利息", "待付罚息", "待付催收费", "待付总金额", "应还日期", "当前/总期", "逾期天数"),
                "code" => array("capital", "interest", "expired_money_now", "call_fee_now", "expired_total", "deadline", "sort_total", "expired_time")
            ),
            "4" => array(    //已失败
                "pay" => 0,
                "status" => array("in", "{$typestr}"),
                "width" => array(143, 163, 163, 163, 133),
                "name" => array("还款方式", "借款金额", "借款时间", "标的状态", "备注"),
                "code" => array("repayment_type", "borrow_money", "borrow_duration", "borrow_status", "remark")
            ),
            "5" => array(    //已还清
                "pay" => 2,
                "status" => array("in", "{$typestr}"),
                "width" => array(143, 163, 163, 163, 133),
                "name" => array("还款方式", "借款金额", "借款期限", "借款时间", "已还本息"),
                "code" => array("repayment_type", "borrow_money", "borrow_duration", "add_time", "receive")
            )
        );
        if (empty($types[$type]['order']) === false) {
            $order = $types[$type]['order'];
        }
        if (empty($types[$type]['status']) === false) {
            $map['b.borrow_status'] = $types[$type]['status'];
        }
        
        //$perpage = 5;//$_POST['perpage'];
        //$curpage = 1;//$_POST['curpage'];
        $map['b.borrow_uid'] = $this->uid;
		$page = intval($arr['page'])? intval($arr['page']):1;
		$limit = intval($arr['limit'])? intval($arr['limit']):5;
        $_GET['p'] = $page;
		import('ORG.Util.Page');
	
        $field = "b.id as bid, b.borrow_name, b.borrow_status, b.add_time, b.borrow_money, b.has_borrow, b.borrow_interest_rate, b.borrow_duration, b.duration_unit, b.borrow_type, b.repayment_type, ";
        $field .= "min(d.deadline) as deadline, max(d.status in (6,7)) as pay_status, d.status as d_status, sum(d.capital) as capital, sum(d.interest) as interest, d.sort_order, d.total, ";
        $field .= "sum(d.receive_capital + d.receive_interest + if(d.repayment_time > 0, d.interest_fee, 0)) as receive, if(v.deal_time_2 > 0, v.deal_info_2, v.deal_info) as remark";
       if (!$type || $type == 1) {
            if (!empty($map['d.deadline'])) {
                $map['b.add_time'] = $map['d.deadline'];
            }
            unset($map['d.deadline']);
            $field = "b.id as bid, b.borrow_name, b.borrow_status, b.add_time, b.borrow_money, b.has_borrow, b.borrow_interest_rate, b.borrow_duration, b.duration_unit, b.borrow_type, b.repayment_type";
            $count = M("borrow_info b")->where($map)->count("distinct b.id");
			$totalPage = ceil($count/$limit);
//            $limit = calPage($count, $curpage, $perpage);
            $re = M("borrow_info b")->field($field)->where($map)->select();
        } else {
			if (!in_array($type, array(3, 4, 5))) {
                $map['bi.parent_invest_id'] = 0;
            }
            
            $count = M("borrow_info b")
                ->join("{$pre}investor_detail d ON b.id = d.borrow_id")
                ->join("{$pre}borrow_investor bi ON bi.id = d.invest_id")
                ->where($map)->count("distinct b.id");

			$p = new Page($count, $limit);
			$totalPage = ceil($count/$limit);
			$Lsql = "{$p->firstRow},{$p->listRows}";
            //$limit = calPage($count, $curpage, $perpage);

            $re = M("borrow_info b")->field($field)->where($map)
                ->join("{$pre}investor_detail d ON b.id = d.borrow_id")
                ->join("{$pre}borrow_investor bi ON bi.id = d.invest_id")
                ->join("{$pre}borrow_verify v ON b.id = v.borrow_id")
                ->order($order)->group("bid")->limit($Lsql)->select();
			//print_r(M()->getlastsql());exit;
        }
		
        foreach ($re as $k => $v) {
            $re[$k]['option'] = (canErase($v) === false || $v['borrow_type'] == 6 || $v['borrow_type'] == 7) ? "--" : "<span style='color:#3181d8; cursor:pointer;' class='do_erase' _bid='{$v['bid']}'>撤销</span>";
            $re[$k]['deadline'] = date("Y-m-d", $v['deadline']);
            $re[$k]['add_time'] = date("Y-m-d", $v['add_time']);
            $re[$k]['borrow_url'] = getBorrowUrl($v['borrow_type'], $v['bid']);
            $re[$k]['borrow_status'] = $Bconfig['BORROW_STATUS'][$v['borrow_status']];
            $re[$k]['remark'] = (empty($v['remark']) === false) ? $v['remark'] : "--";
            $re[$k]['repayment_type'] = $Bconfig['REPAYMENT_TYPE'][$v['repayment_type']];
            $re[$k]['progress'] = getFloatValue($v['has_borrow'] * 100 / $v['borrow_money'], 0) . "%";
            $re[$k]['borrow_duration'] = $v['borrow_duration'] . BorrowModel::get_unit_format($v['duration_unit']);
            if ($type == 3) {    //逾期
                $re[$k]['expired_time'] = $expired_days = getExpiredDays($v['deadline']);
                $re[$k]['call_fee_now'] = getExpiredCallFee($expired_days, $v['capital'], $v['interest']);
                $re[$k]['expired_money_now'] = getExpiredMoney($expired_days, $v['capital'], $v['interest']);
                $re[$k]['expired_total'] = getFloatValue($v['capital'] + $v['interest'] + $re[$k]['expired_money_now'] + $re[$k]['call_fee_now'], 2);
                $re[$k]['sort_total'] = $v['sort_order'] . "/" . $v['total'];
            }
        }
		if(is_array($re)){
				$data['list'] = $re;
				$data['totalPage'] = $totalPage;
				$data['nowPage'] =  $page;
			}else{
				$data = '暂无相关数据！';
				ajaxmsg($data,0);
			}
		ajaxmsg($data,1);
        
        
    }
	//还款列表
	public function paylist()
    {
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);
		$pre = C('DB_PREFIX');
		
        $bid = intval($arr['bid']);
		$page = intval($arr['page'])? intval($arr['page']):1;
		$limit = intval($arr['limit'])? intval($arr['limit']):5;
        $_GET['p'] = $page;
		import('ORG.Util.Page');
		$map['d.borrow_id'] = $bid;
        $map['d.borrow_uid'] = $this->uid;
		$count = M("investor_detail d")
            ->join("{$pre}borrow_investor bi on bi.id=d.invest_id")
            ->where($map)
            ->count("distinct d.sort_order");

		$p = new Page($count, $limit);
		$totalPage = ceil($count/$limit);
		$Lsql = "{$p->firstRow},{$p->listRows}";
        $field = "d.deadline, d.repayment_time, sum(d.capital) as capital, sum(d.interest) as interest, d.status, sum(d.substitute_money) as substitute_money, d.sort_order,b.has_pay";
        $dinfo = M("investor_detail d")->field($field)
            ->join("{$pre}borrow_investor bi on bi.id=d.invest_id")
            ->join("{$pre}borrow_info b on b.id = d.borrow_id")
            ->where($map)->order("d.sort_order asc,d.status ASC")->group("d.sort_order")->limit($limit)->select();
		
        //查询已回款金额
        $receive_detail = InvestorDetailModel::get_has_receive($bid);
        if( !empty($receive_detail) ) {
            foreach( $receive_detail as $value ) {
                foreach( $dinfo as $key=>$val ) {
                    if( $value['sort_order'] == $val['sort_order'] ) {
                        $dinfo[$key]['receive'] = $value['receive'];
                        break;
                    }
                }
            }
        }

        // 如果has_pay小于当期，则当期出现"还款"接口还款
        if (!empty($dinfo)) {
            for ($i = 0; $i < count($dinfo); $i++) {
                if ($dinfo[$i]['has_pay'] < $dinfo[$i]['sort_order']) {
                    $dinfo[$i]['need_pay'] = 1; // 需要还款
                }else{
                    //当有债权转让的时候，且不需要还款时，在已还清里查询应该为已还款，状态从14改成1
                    if( $dinfo[$i]['status'] == 14 ) {
                        $dinfo[$i]['status'] = 1;
                    }
                }
            }
            // 查看取出来的状态是不是都是14，如果都是14，则重新查询出不带14的数据，正确情况下是不会出现都是14的。如果都是14，说明数据量非常小，也无太多消耗。
            $status_all = array_unique(only_array($dinfo, 'status'));
            if (count($status_all) == 1 && $status_all[0] == 14 && $filter != 14) {
                // 通过$filter再判断，防止出现死循环
//                header("Location: " . DOMAIN . $_SERVER['REQUEST_URI'] . 'filter=14');
            }
        }

        $re = calExpired($dinfo);
		if(is_array($re)){
				$binfo = M("borrow_info")->field("id, borrow_name, borrow_type, add_time, borrow_money, has_borrow, borrow_times")->where("id = {$bid}")->find();
				$data['borrow_money'] = $binfo['borrow_money'];
				$data['has_borrow'] = $binfo['has_borrow'];
				$data['borrow_times'] = $binfo['borrow_times'];
				$data['list'] = $re;
				$data['totalPage'] = $totalPage;
				$data['nowPage'] =  $page;
			}else{
				$data = '暂无相关数据！';
				ajaxmsg($data,0);
			}
		ajaxmsg($data,1);
    }
	//还款
    public function dopay()
    {
        $jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);

        $bid = intval($arr['bid']);
        $sort = intval($arr['sort']);
        $re = borrowRepayment($bid, $sort);
        if ($re === true) {
            $json['mssage'] = "还款成功";
			AppCommonAction::ajax_encrypt($json,1);
        } else if ($re === false) {
            $json['mssage'] = "还款失败，请联系平台技术支持";
			AppCommonAction::ajax_encrypt($json,0);
        } else {
            $json['mssage'] = $re;
			AppCommonAction::ajax_encrypt($json,0);
        }
		AppCommonAction::ajax_encrypt($json);
        
    }
	//撤销
    public function doerase()
    {
		$jsoncode = file_get_contents("php://input");
		$arr = array();
		$arr = json_decode($jsoncode, true);
		$arr = AppCommonAction::get_decrypt_json($arr);

        $designer = FS("Webconfig/designer");
        
        $bid = intval($arr['bid']);
        $binfo = M("borrow_info")->field("has_borrow, borrow_status, borrow_type")->where("id = {$bid} and borrow_uid=".$this->uid)->find();
        if ($binfo['borrow_type'] == 6 || $binfo['borrow_type'] == 7) {
            $json['mssage'] = $designer[6] . "和" . $designer[7] . "不能进行撤销操作";
            AppCommonAction::ajax_encrypt($json,0);
            exit;
        }
        if (empty($binfo) === false && is_array($binfo) === true) {
            if (canErase($binfo) === true) {
                if (getBorrowType($binfo['borrow_type']) === "man") {
                    M("borrow_info")->where("id = {$bid}")->delete();
                } else {
                    M("borrow_info")->where("id = {$bid}")->delete();
                    M("borrow_detail")->where("borrow_id = {$bid}")->delete();
                }
                $json['mssage'] = "完成{$bid}号借款标撤销操作";
				AppCommonAction::ajax_encrypt($json,1);
            } else {
                $json['mssage'] = "{$bid}号借款标无法撤销，已通过初审（散标）或已有会员投资此项目，请重新检查";
				AppCommonAction::ajax_encrypt($json,0);
            }
        } else {
            $json['mssage'] = "{$bid}号借款标不存在";
			AppCommonAction::ajax_encrypt($json,0);
        }
        
    }

			
}

            
			
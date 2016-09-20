<?php
// 本类由系统自动生成，仅供测试用途
class ChargeAction extends HCommonAction {
    public function index(){
        $borrowconfig = FS("Webconfig/borrowconfig");
        //dump($borrowconfig['BANK_NAME']);
        $voinfo = M("member_info")->field('idcard,real_name')->find($this->uid);
        //$vobank = M("member_banks")->field(true)->where("uid = {$this->uid} and bank_num !='' and mobile =='mobile'")->order('id desc')->select();
        $vobank = M("member_banks")->field(true)->where("uid = {$this->uid} and bank_num !='' and mobile = 'mobile'")->find();
        if(!empty($vobank)){
            $data['is_manual'] = $this->glo['is_manual'];//是否开启手机验证
            $data['edit_bank'] = $this->glo['edit_bank'];  //修改开关
            $mobile = M('members')->getFieldById($this->uid,'user_phone');
            $data['mobile'] = $mobile;//手机号
            $data['real_name'] = $voinfo['real_name'];//用户银行卡开户名
            $data['bank_name'] = $borrowconfig['BANK_NAME'];//银行列表
            $vobank['bankname'] = $data['bank_name'][$vobank['bank_name']]; //银行名称
            $vobank['bank_id'] = $vobank['bank_name']; //银行名称
            $vobank['bank_num'] = hidecard($vobank['bank_num'],12); //银行卡号
        }
        $this->assign("banks",$vobank);
        $this->assign("pay",intval($_GET['pay']));

        /*
                //微信支付
                ini_set('date.timezone','Asia/Shanghai');
                require_once "App/Lib/Wxpay/WxPay.Api.php";
                require_once "App/Lib/Wxpay/WxPay.JsApiPay.php";
                //require_once "App/Lib/Wxpay/log.php";  //日志记录


                //打印输出数组信息
                function printf_info($data)
                {
                    foreach($data as $key=>$value){
                        echo "<font color='#00ff55;'>$key</font> : $value <br/>";
                    }
                }

        //①、获取用户openid   授权登陆
                $tools = new JsApiPay();
                $openId = $tools->GetOpenid(); // openid


        //②、统一下单
                $input = new WxPayUnifiedOrder();

                $input->SetBody("微信充值");  //商品描述
                $input->SetAttach("test");
                $input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));
                $input->SetTotal_fee("1");
                $input->SetTime_start(date("YmdHis"));  //时间戳
                $input->SetTime_expire(date("YmdHis", time() + 600));
                $input->SetGoods_tag("test");
                $input->SetNotify_url("http://paysdk.weixin.qq.com/example/notify.php");
                $input->SetTrade_type("JSAPI");
                $input->SetOpenid($openId);
                $order = WxPayApi::unifiedOrder($input);
                echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
                printf_info($order);
                $jsApiParameters = $tools->GetJsApiParameters($order);
        //获取共享收货地址js函数参数
                //$editAddress = $tools->GetEditAddressParameters();
                $this->assign("jsApiParameters",$jsApiParameters);
        //③、在支持成功回调通知中处理成功之后的事宜，见 notify.php
                /**
                 * 注意：
                 * 1、当你的回调地址不可访问的时候，回调通知会失败，可以通过查询订单来确认支付是否成功
                 * 2、jsapi支付时需要填入用户openid，WxPay.JsApiPay.php中有获取openid流程 （文档可以参考微信公众平台“网页授权接口”，
                 * 参考http://mp.weixin.qq.com/wiki/17/c0f37d5704f0b64713d5d2c37b468d75.html）
                 */
        $this->display();
    }

    public function user_check(){
        $vo1 = M('members')->field('id,user_name,user_email,user_pass,is_ban')->where("id={$this->uid}")->find();
        if($vo1['is_ban']==1 || $vo1['is_ban']==2){
            ajaxmsg('您的帐户已被冻结，请联系客服处理',0);
        }

        $payConfig = FS("Webconfig/payconfig");
        if ($payConfig['reapal']['enable'] == 0 ){
            ajaxmsg( "对不起，该支付方式被关闭，暂时不能使用!",0);
        }

        /*$banks = M('member_banks')->field('bank_num')->where("uid={$this->uid}")->find();
        if($banks['bank_num']=='' || $banks < 1){
            ajaxmsg('请先到账户中心绑定银行卡',2);
        }*/
        ajaxmsg();
    }

    public function withdrawals(){
        $userinfo=M("members")->where("id={$this->uid}")->getField("user_name");
        $this->assign("username",$userinfo);
        $money_info = M("member_money")->field("account_money, back_money")->where("uid={$this->uid}")->find();
        $bank_info = M("member_banks")->field(true)->where("uid = {$this->uid} and bank_num !=''")->order('id desc')->select();
        $this->assign("usermoney",$money_info['account_money']+$money_info['back_money']);
        $info = get_bconf_setting();
        $arr = array();
        foreach ($bank_info as $k => $v){
            $v['numberor'] = $v['bank_name'];
            $v['bank_name'] = $info['BANK_NAME'][$v['bank_name']];
            $arr[] = $v;
        }
        $bank_info=$arr;
        $this->assign("bank_info",$bank_info);

        /*免手续费金额*/
        $pre = C('DB_PREFIX');
        $field = "m.user_name,m.user_phone,(mm.account_money+mm.back_money) all_money,mm.account_money,mm.back_money,mm.money_collect,i.real_name";
        $vo = M('members m')->field($field)->join("{$pre}member_info i on i.uid = m.id")->join("{$pre}member_money mm on mm.uid = m.id")->where("m.id={$this->uid}")->find();
        $data['back_money'] = $vo['back_money'];//免手续费金额

        $tqfee = explode("|",$this->glo['fee_tqtx']);
        $fee[0] = explode("-",$tqfee[0]);
        $fee[1] = explode("-",$tqfee[1]);
        $fee[2] = explode("-",$tqfee[2]);
        $minfee = $tqfee[3];
        $datas['cc_hksxfee'] = $fee[0][0]; //超出回款金额费率
        $datas['maxfee'] = $fee[0][1];   //超出回款金额手续费最大金额
        $datas['hksxfee'] = $fee[1][0];  //回款金额费率
        $datas['hk_maxfee'] = $fee[1][1]; //回款金额手续费最大金额
        $datas['minfee'] = $minfee;   //手续费最低金额

        $this->assign("back",$data['back_money']);

        $this->assign("fee",json_encode($datas));
        $this->display();
    }


    //最后提现
    public function actwithdraw(){
//        if($_SESSION['code'] != sha1(strtolower($_POST['valicode']))){
//            ajaxmsg("验证码错误",0);
//        }
        //file_put_contents("111.txt",$_POST);
//        ajaxmsg($_POST);
//        exit;
        $pre = C('DB_PREFIX');
        $withdraw_money = floatval($_POST['amount']);
        $bank_id = intval($_POST['bankname']);
        $pwd = md5($_POST['pin_pass']);
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
    /*public function actwithdraw(){
    	$pre = C('DB_PREFIX');
    	$rct = 0;
    	$withdraw_money = floatval($_POST['amount']);
    	$pwd = md5($_POST['pin_pass']);
    	$bank_id = $_POST['bankname'];
    	$vo = M('members m')
    		->join("{$pre}member_money mm on mm.uid = m.id")
    		->field('mm.account_money,mm.back_money,(mm.account_money+mm.back_money) all_money,m.user_leve,m.time_limit,m.pin_pass')
    		->where("m.id={$this->uid}")->find();

    	if(!is_array($vo)) ajaxmsg("数据有误",0);
    	if($pwd != $vo['pin_pass']){ ajaxmsg("支付密码错误",0); }
    	if($vo['all_money']<$withdraw_money) ajaxmsg("提现金额大于帐户余额",0);
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
    	if($withdraw_money<100 ||$withdraw_money>$one_limit) ajaxmsg("单笔提现金额限制为100-{$one_limit}元",0);
    	$today_limit = $fee[2][1]/$fee[2][0];
    	if($today_time>=$today_limit){
    		$message = "一天最多只能提现{$today_limit}次";
    		ajaxmsg($message,0);
    	}

    	if($vo['user_leve']>0 && $vo['time_limit']>time()){
    		if(($today_money+$withdraw_money)>$fee[2][1]*10000){
    			$message = "单日提现上限为{$fee[2][1]}万元。请您改日再申请提现";
    			ajaxmsg($message,0);
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

    		if(($vo['all_money']-$withdraw_money - $fee)<0 ){

    			//$withdraw_money = ($withdraw_money - $fee);
    			$moneydata['withdraw_money'] = $withdraw_money;
    			$moneydata['bank_id'] =$bank_id;
    			$moneydata['withdraw_fee'] = $fee;
    			$moneydata['second_fee'] = $fee;
    			$moneydata['withdraw_status'] = 0;
    			$moneydata['uid'] =$this->uid;
    			$moneydata['add_time'] = time();
    			$moneydata['add_ip'] = get_client_ip();
    			$newid = M('member_withdraw')->add($moneydata);
    			$rct = $newid;
    			if($newid){
    				memberMoneyLog($this->uid,4,-$withdraw_money,"提现,默认自动扣减手续费".$fee."元",'0','@网站管理员@',0);
    				MTip('chk6',$this->uid);
    				//ajaxmsg("恭喜，提现申请提交成功",1);
    			}
    			if($rct > 0){
    				//file_put_contents('2.txt',$newid);
    				ajaxmsg("恭喜，提现申请提交成功",1);
    			}

    		}else{
    			$moneydata['withdraw_money'] = $withdraw_money;
    			$moneydata['bank_id'] =$bank_id;
    			$moneydata['withdraw_fee'] = $fee;
    			$moneydata['second_fee'] = $fee;
    			$moneydata['withdraw_status'] = 0;
    			$moneydata['uid'] =$this->uid;
    			$moneydata['add_time'] = time();
    			$moneydata['add_ip'] = get_client_ip();
    			$newid = M('member_withdraw')->add($moneydata);
    			$rct = $newid;
    			if($newid){
    				//memberMoneyLog($this->uid,4,-$withdraw_money,"提现,默认自动扣减手续费".$fee."元",'0','@网站管理员@',-$fee);
    				memberMoneyLog($this->uid,4,-$withdraw_money,"提现,默认自动扣减手续费".$fee."元",'0','@网站管理员@',0);
    				//MTip('chk6',$this->uid);
    				//ajaxmsg("恭喜，提现申请提交成功",1);
    			}
    			if($rct > 0){
    				//file_put_contents('2.txt',$newid);
    				ajaxmsg("恭喜，提现申请提交成功",1);
    			}
    		}
    	}else{ //普通会员暂未使用

    		if(($today_money+$withdraw_money)>300000){
    			$message = "您是普通会员，单日提现上限为30万元。前改日再申请提现";
    			ajaxmsg($message,0);
    		}
    		$tqfee = $this->glo['fee_pttx'];
    		$fee = getFloatValue($tqfee*$withdraw_money/100,2);
    		if( ($vo['account_money']-$withdraw_money - $fee)<0 ){
    			file_put_contents('1.txt', $vo['account_money']-$withdraw_money - $fee);
    			$withdraw_money = ($withdraw_money - $fee);
    			$moneydata['withdraw_money'] = $withdraw_money;
    			$moneydata['bank_id'] =$bank_id;
    			$moneydata['withdraw_fee'] = $fee;
    			$moneydata['withdraw_status'] = 0;
    			$moneydata['uid'] =$this->uid;
    			$moneydata['add_time'] = time();
    			$moneydata['add_ip'] = get_client_ip();
    			$newid = M('member_withdraw')->add($moneydata);
    			$rct = $newid;
    			if($newid){
    				memberMoneyLog($this->uid,4,-$withdraw_money - $fee,"提现,自动扣减手续费".$fee."元");
    				MTip('chk6',$this->uid);
    				//ajaxmsg("恭喜，提现申请提交成功",1);
    			}
    			if($rct > 0){
    				//file_put_contents('2.txt',$newid);
    				ajaxmsg("恭喜，提现申请提交成功",1);
    			}
    		}else{
    			$moneydata['withdraw_money'] = $withdraw_money;
    			$moneydata['bank_id'] =$bank_id;
    			$moneydata['withdraw_fee'] = $fee;
    			$moneydata['withdraw_status'] = 0;
    			$moneydata['uid'] =$this->uid;
    			$moneydata['add_time'] = time();
    			$moneydata['add_ip'] = get_client_ip();
    			$newid = M('member_withdraw')->add($moneydata);
    			$rct = $newid;
    			if($newid){
    				memberMoneyLog($this->uid,4,-$withdraw_money,"提现,自动扣减手续费".$fee."元",'0','@网站管理员@',-$fee);
    				MTip('chk6',$this->uid);
    			}
    			if($rct > 0){
    				//file_put_contents('2.txt',$newid);
    				ajaxmsg("恭喜，提现申请提交成功",1);
    			}
    		}
    		ajaxmsg("对不起，提现出错，请重试",0);
    	}
    }*/
}
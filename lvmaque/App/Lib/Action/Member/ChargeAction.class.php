<?php
//解决火狐swfupload的session bug
if (isset($_POST[session_name()]) && empty($_SESSION)) {
    session_destroy();
    session_id($_POST[session_name()]);
    session_start();
}
class ChargeAction extends MCommonAction {

    public function index(){
		$vo1 = M('members')->field('id,user_name,user_email,user_pass,is_ban')->where("id={$this->uid}")->find();
		if($vo1['is_ban']==1||$vo1['is_ban']==2) $this->error("您的账户已被冻结，请联系客服处理！",__APP__."/index.html");
		
		$map['uid'] = $this->uid;
		$account_money = M('member_money')->field('(account_money+back_money) account_money')->where($map)->find();
		$this->assign("account_money",$account_money);
		$this->assign("payConfig",FS("Webconfig/payconfig"));
		
		$id5_config = FS("Webconfig/id5"); // 实名认证接口
		$this->assign("id5_enable",$id5_config['enabled']);   //1：开启     0：未开启
		
		//线下充值奖励
		$offline_reward = $this->glo['offline_reward'];
		$arr = explode("|",$offline_reward);
		$reward = "";
		if($arr[1]==0 && $arr[3]==0 && $arr[5]==0){
		    $reward .= "线下充值不收取任何手续费用。";
		}else{
		    $reward .= "线下充值奖励规则：<br>&nbsp;&nbsp;&nbsp;&nbsp;a.&nbsp;".$arr[0]."元奖励".($arr[1]/10)."%；<br>&nbsp;&nbsp;&nbsp;&nbsp;b.&nbsp;".$arr[2]."元奖励".($arr[3]/10)."%；<br>&nbsp;&nbsp;&nbsp;&nbsp;c.&nbsp;".$arr[4]."元以上奖励".($arr[5]/10)."%；<br>&nbsp;&nbsp;&nbsp;&nbsp;d.&nbsp;奖励金额 = 充值金额  x 百分比。";
		}
		
		$this->assign("reward", $reward);

        $smsconfig = FS("Webconfig/msgconfig"); // 短信接口
        $sms_switch = $smsconfig['sms']['type']; // 1 关闭  0 开启
        $this->assign("sms_switch", $sms_switch);

        $phonestatus = M('members_status')->getFieldByUid($this->uid,'phone_status');
        $this->assign('phonestatus', $phonestatus);

	    $payConfig=FS("Webconfig/payconfig");
        $this->assign("payConfig",  $payConfig);
        $payoff = FS("Webconfig/payoff");
        $this->assign('bank', $payoff['BANK']);
        $this->assign('info',$payoff['BANK_INFO']);
		$this->assign('payoff',$payoff['IS_OPEN']); //线下充值
		if ($payConfig['baofoo']['enable']==1){
		    $feerate = $payConfig['baofoo']['feerate'];
		}else{
		    $feerate = $payConfig['reapal']['feerate'];
		}
		//$feerate=array($payConfig['baofoo']['feerate'],$payConfig['reapal']['feerate']);
		//sort($feerate);
		$this->assign("feerate", $feerate);
		$this->display();
    }
	public function actcharge(){
		header("Content-type:text/html;charset=utf-8");
		if($_SESSION['code'] == sha1($_POST['valicode'])){
			$type = text($_POST['payment_id']);
			$bankcode  = text($_POST['bank_payment_id']);
			$money = floatval($_POST['money']);
			if($money>5000000){
			    $this->error("单笔充值不能超过500万","/Member/Charge");       
			}
			switch($type){
			    case "unspay":
			        $way = "unspay";
			    break;
                case "baofoo":
                    $way = "baofoo";
                break;
                case "reapal":
                    $way = "reapal";
                break;
                case "chinabank":
                    $way = "chinabank";
                break;
                default:
                    $way = '';
                break;
            }
			if(!empty($way)){
			    $url = "/Pay/".$way."?bankCode=".$bankcode."&t_money=".$money;
				Header("Location: $url");
			}
		}else{
		    $this->error("验证码错误","/Member/Charge");
		}
		
	}
    public function index2(){
		$vo1 = M('members')->field('id,user_name,user_email,user_pass,is_ban')->where("id={$this->uid}")->find();
		if($vo1['is_ban']==1||$vo1['is_ban']==2) $this->error("您的账户已被冻结，请联系客服处理！",__APP__."/index.html");
		
		$this->display();
    }
    public function charge(){
		$map['uid'] = $this->uid;
		$account_money = M('member_money')->field('(account_money+back_money) account_money')->where($map)->find();
		$this->assign("account_money",$account_money);
		$this->assign("payConfig",FS("Webconfig/payconfig"));
		
		$config = FS("Webconfig/payoff");
        $this->assign('bank', $config['BANK']);
        $this->assign('info',$config['BANK_INFO']);
		$this->assign('payoff',$config['IS_OPEN']);
		$data['html'] = $this->fetch();
		exit(json_encode($data));
    }

    public function chargeoff(){
		$this->assign("vo",M('article_category')->where("type_name='线下充值'")->find());
		
        $config = FS("Webconfig/payoff");
        $this->assign('bank', $config['BANK']);
        $this->assign('info',$config['BANK_INFO']);
        $data['html'] = $this->fetch();
		exit(json_encode($data));
    }

    public function chargelog(){
		$map['uid'] = $this->uid;
		
		if($_GET['start_time']&&$_GET['end_time']){
			$_GET['start_time'] = strtotime($_GET['start_time']." 00:00:00");
			$_GET['end_time'] = strtotime($_GET['end_time']." 23:59:59");
			
			if($_GET['start_time']<$_GET['end_time']){
				$map['add_time']=array("between","{$_GET['start_time']},{$_GET['end_time']}");
				$search['start_time'] = $_GET['start_time'];
				$search['end_time'] = $_GET['end_time'];
			}
		}
		$list = getChargeLog($map,10);
		$this->assign('search',$search);
		$this->assign("list",$list['list']);
		$this->assign("pagebar",$list['page']);
		$this->assign("success_money",$list['success_money']);
		$this->assign("fail_money",$list['fail_money']);
		
		$data['html'] = $this->fetch();
		exit(json_encode($data));
    }
    public function uploadimg()
    {
        $uid = $this->uid;
         
        if ( $_POST['picpath'] ){ //删除
            $imgpath = substr( $_POST['picpath'], 1 );           
            if ( in_array( $imgpath, $_SESSION['imgfiles'] ) ){                
                $res = unlink( C( "WEB_ROOT" ).$imgpath );                
                if ( $res )        $this->success( "删除成功", "", $_POST['oid'] );                
                else             $this->error( "删除失败", "", $_POST['oid'] );                
            }else{                
                $this->error( "图片不存在", "", $_POST['oid'] );            
            }        
        } else { //上传
            $this->savePathNew = C( "MEMBER_UPLOAD_DIR" )."PayImg/$uid/";            
            $this->saveRule = date( "YmdHis", time() ).rand( 0, 1000 );            
            $info = $this->CUpload(); 

            if ( !isset( $_SESSION['count_file'] ) )    $_SESSION['count_file']=1;            
            else  ++$_SESSION['count_file'];

            $data['img'] = $info[0]['savepath'].$info[0]['savename'];  
            
                      
            $_SESSION['imgfiles'][$_SESSION['count_file']] = $data['img'];            
            echo text("{$_SESSION['count_file']}:".__ROOT__."/".$data['img']);        
        }
    }

}
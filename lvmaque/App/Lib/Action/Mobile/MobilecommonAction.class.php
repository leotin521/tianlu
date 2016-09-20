<?php
class MobilecommonAction extends MMCommonAction {
    var $notneedlogin=true;


    public function login(){
    
        $name = $_POST['name'];
        $password = $_POST['password'];
        //$android['password']=$password;
        //$android['android']=$name;
        //$suoid = M("android")->add($android);
        $content = array();
        $content['name']= $name;
        $content['password']= $password;
      //  $content['session_expired']=$this->$sessionExpired;
        echo json_encode($content);
    }
    

    //登录
    public function actlogin(){
        
        //$msg['session_expired']=0;
    
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        
        //(false!==strpos($arr['sUserName'],"@"))?$data['user_email'] = text($arr['sUserName']):$data['user_name'] = text($arr['sUserName']);
		if(false!==strpos($arr['sUserName'],"@")){
			$data['user_email'] = text($arr['sUserName']);
		}elseif(preg_match("/1[34578]{1}\d{9}$/",$arr['sUserName'])){
			$data['user_phone'] = text($arr['sUserName']);
		}else{
			$data['user_name'] = text($arr['sUserName']);
		}
        $vo = M('members')->field('id,user_name,user_email,user_pass,is_ban')->where($data)->find();
        $msg['message']='您的帐户已被冻结，请联系客服处理！';
        if($vo['is_ban']==1) 
            AppCommonAction::ajax_encrypt($msg,0);
        
        if(is_array($vo)){
                
            if($vo['user_pass'] == md5($arr['sPassword']) ){//本站登陆成功
                
                $this->_memberlogin($vo['id']);
                
                $mess = array();
                $mess['uid'] = intval(session("u_id"));//用户id
                $mess['username'] = $vo['user_name'];//用户名
                //$mess['phone'] = intval(session("u_user_phone"));//用户手机
				//总资产
				$minfo = getMinfo($mess['uid'],true);
                $membermoney = M("member_money")->field(true)->where("uid={$mess['uid']}")->find();
				$agility_money = BaoInvestModel::get_sum_money($this->uid);
				$datag = get_global_setting();
                $is_manual=$datag['is_manual'];
                //$mess['phoneverify_manual'] = $is_manual? 1 : 2;
				//代收收益
				$wait = M("investor_detail")->field($field)->where("investor_uid = {$this->uid} AND status in (6,7)")->find();
				//累计收益
				$agility_interest = BaoInvestModel::get_sum_interest($this->uid);
                if(is_array($minfo)){
                    //$mess['mayuse'] = $minfo['account_money'] + $minfo['back_money'];//可用余额    
                    $mess['cumulative'] = $minfo['income'] + $agility_interest;//累计收益
                    $mess['collect'] = $wait['interest'];//代收收益
                    $mess['total'] = $minfo['account_money'] + $minfo['back_money'] + $minfo['money_freeze'] + $minfo['money_collect'] + $agility_money;//总资产
                }else{
                    $mess['total'] = 0;
                    //$mess['mayuse'] = 0;
                    $mess['cumulative'] = 0;
                    $mess['collect'] = 0;
                }
                //$mess['session_expired']=0;
                AppCommonAction::ajax_encrypt($mess,1);
            }else{//本站登陆不成功
                AppCommonAction::ajax_encrypt("用户名或者密码错误!",0);
            }
        }else {
            AppCommonAction::ajax_encrypt("用户名或者密码错误!",0);
        }
    }


    //登录

	/***
    public function actlogin(){
        
        $msg['session_expired']=0;
    
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);

        //ajaxmsg($jsoncode);
        //ajaxmsg($arr);

        (false!==strpos($arr['sUserName'],"@"))?$data['user_email'] = text($arr['sUserName']):$data['user_name'] = text($arr['sUserName']);
        $vo = M('members')->field('id,user_name,user_email,user_pass,is_ban')->where($data)->find();
        $msg['message']='您的帐户已被冻结，请联系客服处理！';
        if($vo['is_ban']==1) 
            ajaxmsg($msg,0);
        
        if(is_array($vo)){
                
            if($vo['user_pass'] == md5($arr['sPassword']) ){//本站登陆成功
                
                $this->_memberlogin($vo['id']);
                //alogs("login",'','1',session("u_id")."登录成功");
                $arr2 = array();
                $arr2['type'] = 'actlogin';
                $arr2['deal_user'] = $vo['user_name'];
                $arr2['tstatus'] = '1';
                $arr2['deal_time'] = time();
                $arr2['deal_info'] = $vo['user_name']."登录成功_".$jsoncode;
                $newid = M("auser_dologs")->add($arr2);
                
                $mess = array();
                $mess['uid'] = intval(session("u_id"));
                $mess['username'] = $vo['user_name'];
                $mess['phone'] = intval(session("u_user_phone"));
                $mess['head'] = get_avatar($mess['uid']);//头像
                $minfo = getMinfo($mess['uid'],true);
                $mess['credits'] = getLeveIco($minfo['credits'],3);//会员等级
                $membermoney = M("member_money")->field(true)->where("uid={$mess['uid']}")->find();
                $datag = get_global_setting();
                $is_manual=$datag['is_manual'];
                $mess['phoneverify_manual'] = $is_manual? 1 : 2;
                if(is_array($membermoney)){
                    $mess['mayuse'] = $membermoney['account_money']+$membermoney['back_money'];//可用    
                    $mess['freeze'] = $membermoney['money_freeze'];//冻结
                    $mess['collect'] = $membermoney['money_collect'];//代收
                    $mess['total'] = $mess['mayuse']+$mess['freeze']+$mess['collect'];//总额
                }else{
                    $mess['total'] = 0;
                    $mess['mayuse'] = 0;
                    $mess['freeze'] = 0;
                    $mess['collect'] = 0;
                }
                $mess['session_expired']=0;
                ajaxmsg($mess,1);
            }else{//本站登陆不成功
                $msg['message']="用户名或者密码错误！2";
                ajaxmsg($msg,0);
            }
        }else {
                $msg['message']="用户名或者密码错误！3";
                //ajaxmsg(vo['user_pass'],0);
                ajaxmsg($msg,0);
        }
    }
    
	***/
	/*
	 *#29 API 获取手机号验证码
	 *参考文档 服务器与客户端协议v20140912.docx
	 *14-09-12 元
	 */
	 public function commitphone(){

	    $jsoncode = file_get_contents("php://input");
	    $arr = array();
		
		$arr = json_decode($jsoncode,true);
		$arr = AppCommonAction::get_decrypt_json($arr); //私钥解密

		$msg['message']="提交失败,请重试！";
		if(!is_array($arr) || empty($arr)){AppCommonAction::ajax_encrypt($msg,0);}
		 
		$phone = text($arr['phone']);
		$xuid = M('members')->getFieldByuser_phone($phone,'id');
		$msg['message']="手机号已被使用";
		if($xuid){ AppCommonAction::ajax_encrypt($msg,0);}

		if(!empty($arr['username'])){
			$xuids = M('members')->getFieldByuser_name($arr['username'],'id');
			$msg['message']="用户名已被使用";
			if($xuids){ AppCommonAction::ajax_encrypt($msg,0);}
		}


		$smsTxt = FS("Webconfig/smstxt");
		$smsTxt=de_xie($smsTxt);
		$code = rand_string_reg(6, 1, 2);
		$res = sendsms($phone, str_replace(array("#UserName#","#CODE#"), array($arr['username'],$code), $smsTxt['verify_phone']));
		if($res){
			session("temp_phone",md5($phone.$code));
			$msg['message']="发送验证码成功";
			AppCommonAction::ajax_encrypt($msg,1);
		}else{
			$msg['message']="发送验证码失败";
			AppCommonAction::ajax_encrypt($msg,0);
		}

    }
    //注册
    public function regaction(){
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode, true);
        $arr = AppCommonAction::get_decrypt_json($arr); //私钥解密
        if (!is_array($arr)||empty($arr)) {
          AppCommonAction::ajax_encrypt("提交信息错误，请重试!",0);
        }
        if ($arr['name']==""||$arr['password']=="") {
          AppCommonAction::ajax_encrypt("提交信息错误，请重试!",0);
        }
		$tel = $arr['tel'];
		$tel2 = $arr['tel2'];
		if(session("temp_phone")!=md5($tel.$tel2)){
			AppCommonAction::ajax_encrypt('对不起,验证码错误请重新输入',0);
		}
 
       //实现邮箱的合法性  2015-01-19
             
       $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";

	    /*if (!preg_match( $pattern, $arr['email'] ) )
		 {
		       AppCommonAction::ajax_encrypt("您输入的电子邮件地址不合法!",0);
		  }*/
         

        //实现邮箱验证的合法性 2015-01-19
		if(strlen($arr['name']) < 4 or strlen($arr['name']) > 20){
		    AppCommonAction::ajax_encrypt("用户名为4到20个字符!",0);
		}
        $data['user_name'] = text($arr['name']);
        $data['user_pass'] = md5($arr['password']);
        $data['user_phone'] = text($arr['tel']);


        $count = M('members')->where("user_phone = '{$data['user_phone']}' OR user_name='{$data['user_name']}'")->count('id');
        if($count>0) {
            AppCommonAction::ajax_encrypt("注册失败，用户名或者手机已经有人使用!",0);
        }
		
//-------------------------
        $data['register_rec']=text($arr['people']);   //tianjiaren
		if(!empty($data['register_rec']))
			{
				$count_rec=M('members')->where("user_name='{$arr['people']}'")->find();
				 if(empty($count_rec)){
					 AppCommonAction::ajax_encrypt("推荐人不存在!",0);
		          }         
			$data['recommend_id']=$count_rec['id'];
		   }
//----------------------------------


        $data['reg_time'] = time();
        $data['reg_ip'] = get_client_ip();
        $data['lastlog_time'] = time();
        $data['lastlog_ip'] = get_client_ip();
        if(session("tmp_invite_user"))  $data['recommend_id'] = session("tmp_invite_user");
        $newid = M('members')->add($data);
        $updata['cell_phone'] = $data['user_phone'];
        if($newid){ 
            $mess = array();
            $mess['uid'] = $newid; 
            $mess['message']="注册已成功!";
			/*$updatas['account_money'] = 10000000;
			$abc = M("member_money")->where("uid={$newid}")->count('id');
			if($abc == 1){
				M("member_money")->where("uid={$newid}")->save($updatas);
			}else{
				$updatas['uid'] = $newid;
				M('member_money')->add($updatas);
			}*/
			addCoupon($newid, 1, "新用户注册奖励");
			addCoupon($this->uid, 2, "手机认证奖励");
           // noTify($newid,2);
			
			$b = M("member_info")->where("uid={$newid}")->count('id');
			if ($b == 1){
				$newid = M("member_info")->where("uid={$newid}")->save($updata);
			}else{
				$updata['uid'] = $newid;
				M('member_info')->add($updata);
			} 
			setMemberStatus($newid, 'phone', 1, 10, '手机');
            AppCommonAction::ajax_encrypt($mess,1);
            
        }else{
            AppCommonAction::ajax_encrypt("注册失败，请重试!",0);
        }  
    }
    
    public function mactlogout(){
        $this->_memberloginout();
        AppCommonAction::ajax_encrypt("注销成功!",1);   //公钥加密 
    }
    
   
}
<?php
// 本类由系统自动生成，仅供测试用途
class CommonAction extends MCommonAction {
	var $notneedlogin=true;
    public function index(){
		$this->display();
    }
	
    public function login(){
        if (isset($_COOKIE['NameCookie'])){
            $this->assign('user_name', $_COOKIE['NameCookie']);
        }
		$loginconfig = FS("Webconfig/loginconfig");//判断快捷登录是否开启
		$this->assign("loginconfig",$loginconfig);
		$this->display();
    }
	
    public function register(){
		$loginconfig = FS("Webconfig/loginconfig");//判断快捷登录是否开启
		$this->assign("loginconfig",$loginconfig);
		if($_GET['invite']){
			//$uidx = M('members')->getFieldByUserName(text($_GET['invite']),'id');
			//if($uidx>0) session("tmp_invite_user",$uidx);
			session("tmp_invite_user",$_GET['invite']);
            $this->assign('tmp_invite_user', $_GET['invite']);
		}else{
			session("tmp_invite_user",null);
		}
		$this->display();
    }
	
	public function actlogin(){
		setcookie('LoginCookie','',time()-10*60,"/");
		//uc登录
		
		$loginconfig = FS("Webconfig/loginconfig"); 
		$uc_mcfg  = $loginconfig['uc'];
		if($uc_mcfg['enable']==1){
			require_once C('APP_ROOT')."Lib/Uc/config.inc.php";
			require C('APP_ROOT')."Lib/Uc/uc_client/client.php";
		}
		if($_SESSION['code'] != sha1(strtolower($_POST['sVerCode']))){
			ajaxmsg("验证码错误！",0);
		}
		if (false!==strpos($_POST['sUserName'],"@")){
		    $data['user_email'] = text($_POST['sUserName']);
		}elseif (preg_match("/^1[34578]\d{9}$/", $_POST['sUserName'])){
		    $data['user_phone'] = text($_POST['sUserName']);
		}else{
		    $data['user_name'] = text($_POST['sUserName']);
		}
		$vo = M('members')->field('id,user_name,user_email,user_pass,is_ban')->where($data)->find();
		if(empty($vo)) ajaxmsg("账户不存在，请检查后重试！",3);
		$vo['sVerRem'] = text($_POST['sVerRem']);//
		if($vo['is_ban']==1) ajaxmsg("当前账户已冻结，请联系客服处理！",2);
		if($vo['is_ban']==2) ajaxmsg("当前账户已锁定，请联系客服处理！",2);
//                读取网站登录次数设置
		$lnum =M('global')->where("code='login_num'")->getField('text'); 
                                         $login_num= intval($lnum);
                                         if($login_num=='0'){        //登录错误次数不受限制
                                             $this->loginlog($vo,0,0);
                                         }else{
                                                  $time = strtotime(date("Y-m-d",time()));
                                                  $where = ' and add_time >'.$time.' and add_time<'.($time+3600*24);
                                                  $fail_login_num =M('member_login')-> where("is_success=1 and uid={$vo['id']}{$where}")->count();
                                                  $ttime =$login_num-1-$fail_login_num;
                                                  if ($ttime < 0){
                                                      ajaxmsg("当前账户已锁定，请明天登录或联系客服！",2); 
                                                  }
                                                  if($fail_login_num>$login_num) {
                                                      ajaxmsg("账号密码错误，您今天还可以登录{$ttime}次！",0);  
                                                  }elseif($fail_login_num==$login_num) {
                                                      $Mdata['is_ban'] = '2';
                                                      M('members')->where('id='.$vo['id'])->save($Mdata);
                                                      ajaxmsg("当前账户已锁定，请明天登录或联系客服！",2);
                                                  }else{
                                                       $this->loginlog($vo,1,$ttime); 
                                                  }
                                       }           
		
	}
        /**
         * 
         * @param void $vo  登录信息
         * @param int $type  是否开启登录次数限制 0为不限制，1为限制
         */
        protected function loginlog($vo,$type,$ttime){
            $loginconfig = FS("Webconfig/loginconfig"); 
            $uc_mcfg  = $loginconfig['uc'];
            if($uc_mcfg['enable']==1){
                    require_once C('APP_ROOT')."Lib/Uc/config.inc.php";
                    require C('APP_ROOT')."Lib/Uc/uc_client/client.php";
            }
                if(!is_array($vo)){
                      //本站登录不成功，偿试uc登录及注册本站
                      if($uc_mcfg['enable']==1){
                              list($uid, $username, $password, $email) = uc_user_login(text($_POST['sUserName']), text($_POST['sPassword']));
                              if($uid > 0) {
                                      $regdata['txtUser'] = text($_POST['sUserName']);
                                      $regdata['txtPwd'] = text($_POST['sPassword']);
                                      $regdata['txtEmail'] = $email;
                                      $newuid = $this->ucreguser($regdata);

                                      if(is_numeric($newuid)&&$newuid>0){
                                              $logincookie = uc_user_synlogin($uid);//UC同步登录
                                              setcookie('LoginCookie',$logincookie,time()+10*60,"/");
                                              $this->_memberlogin($newuid,0);
                                              ajaxmsg();//登录成功
                                              
                                      }else{
                                              ajaxmsg($newuid,0);
                                      }
                              }  else {
                                   ajaxmsg("账号或者密码错误！",0); 
                              }
                      }else{
                          //本站登录不成功，偿试uc登录及注册本站
                           ajaxmsg("账号或者密码错误！",0); exit;  
                      }
              }else{

                      if($vo['user_pass'] == md5($_POST['sPassword']) ){//本站登录成功，uc登录及注册UC
                          $remember_user_name = intval($_POST['sVerRem']);
                          if ($remember_user_name==1){
                              setcookie('NameCookie',$_POST['sUserName'],time()+3*24*60*60,"/");
                          } else {
                              setcookie('NameCookie',$_POST['sUserName'],time()-3600,"/");
                          }
                              //uc登录及注册UC
                              if($uc_mcfg['enable']==1){
                                      $dataUC = uc_get_user($vo['user_name']);
                                      if($dataUC[0] > 0) {
                                              $logincookie = uc_user_synlogin($dataUC[0]);//UC同步登录
                                              setcookie('LoginCookie',$logincookie,time()+10*60,"/");
                                      }else{
                                              $uid = uc_user_register($vo['user_name'], $_POST['sPassword'], $vo['user_email']);
                                              if($uid>0){
                                                      $logincookie = uc_user_synlogin($dataUC[0]);//UC同步登录
                                                      setcookie('LoginCookie',$logincookie,time()+10*60,"/");
                                              }
                                      }
                              }
                              $this->_memberlogin($vo['id'],0);
                              ajaxmsg();
                      }else{//本站登录不成功
                           $this->_memberlogin($vo['id'],1);
                          if($type=='1'){
                                if($ttime<='0'){
                                    $data['is_ban'] = '2';
                                    M('members')->where('id='.$vo['id'])->save($data);
                                    ajaxmsg("当前账户已冻结，请明天登录或联系客服！",0);    
                                }else{
                                    ajaxmsg("账号密码错误，您今天还可以登录{$ttime}次！",0);    
                                }   
                          }else{
                                 ajaxmsg("账号或者密码错误！",0); 
                          }  
                      }
              }
        }
	public function actlogout(){
		$this->_memberloginout();
		//uc登录
		$loginconfig = FS("Webconfig/loginconfig");
		$uc_mcfg  = $loginconfig['uc'];
		if($uc_mcfg['enable']==1){
			require_once C('APP_ROOT')."Lib/Uc/config.inc.php";
			require C('APP_ROOT')."Lib/Uc/uc_client/client.php";
			$logout = uc_user_synlogout();
		}
		//uc登录
		$this->assign("uclogout",de_xie($logout));
		$this->success("注销成功",__APP__."/");
	}
	
	private function ucreguser($reg){
		$data['user_name'] = text($reg['txtUser']);
		$data['user_pass'] = md5($reg['txtPwd']);
		$data['user_email'] = text($reg['txtEmail']);
		$count = M('members')->where("user_email = '{$data['user_email']}' OR user_name='{$data['user_name']}'")->count('id');
		if($count>0) return "登录失败,UC用户名冲突,用户名或者邮件已经有人使用";
		$data['reg_time'] = time();
		$data['reg_ip'] = get_client_ip();
		$data['last_log_time'] = time();
		$data['last_log_ip'] = get_client_ip();
		$newid = M('members')->add($data);
		
		if($newid){
			session('u_id',$newid);
			session('u_user_name',$data['user_name']);
			return $newid;
		}
		return "登录失败,UC用户名冲突";
	}
	
	public function regtemp(){
		session('name_temp',text($_POST['txtUser']));
		session('pwd_temp',md5($_POST['txtPwd']));
		session('rec_temp',text($_POST['txtRec']));
		ajaxmsg();
	}
	public function regaction(){
	    //判断来源（pc，winxin，app）
	    $agent = new AgentModel();
        $type = $agent->sourceType();
	    $data['register_resource'] = $type;
	    
		$data['user_name'] = session('name_temp');
		$data['user_pass'] = session('pwd_temp');
		if(session('temp_phone')){
		    $data['user_phone'] = session('temp_phone');
		}
		if(session('email_temp')){
			$data['user_email'] = session('email_temp');
		}
		//`mxl:regnodb`
		$ruser = $data['user_name'];
		$Retemp1 = M('members')->field("id")->where("user_name = '{$ruser}'")->find();
		$Retemp2 = M('ausers')->field("id")->where("user_name = '{$ruser}'")->find();
		if ($Retemp1['id'] > 0 || $Retemp2['id'] > 0) {
			ajaxmsg('用户名已经存在',0);
		}
		//`mxl:regnodb`
		//uc注册
		$loginconfig = FS("Webconfig/loginconfig");
		$uc_mcfg  = $loginconfig['uc'];
		if($uc_mcfg['enable']==1 && session('email_temp')){
			require_once C('APP_ROOT')."Lib/Uc/config.inc.php";
			require C('APP_ROOT')."Lib/Uc/uc_client/client.php";
			$uid = uc_user_register($data['user_name'], $_POST['txtPwd'], $data['user_email']);
			if($uid <= 0) {
				if($uid == -1) {
					ajaxmsg('用户名不合法',0);
				} elseif($uid == -2) {
					ajaxmsg('包含要允许注册的词语',0);
				} elseif($uid == -3) {
					ajaxmsg('用户名已经存在',0);
				} elseif($uid == -4) {
					ajaxmsg('Email 格式有误',0);
				} elseif($uid == -5) {
					ajaxmsg('Email 不允许注册',0);
				} elseif($uid == -6) {
					ajaxmsg('该 Email 已经被注册',0);
				} else {
					ajaxmsg('未定义',0);
				}
			}
		}
		//uc注册
		
		$data['reg_time'] = time();
		$data['reg_ip'] = get_client_ip();
		$data['last_log_time'] = time();
        $data['last_log_ip'] = get_client_ip();
		//$global = get_global_setting();
		//$data['reward_money'] = $global['reg_reward'];//新注册用户奖励
		
		if(session("tmp_invite_user")) {
			$data['recommend_id'] = MembersModel::get_Decrypt_uid(session("tmp_invite_user"));
		}else if(session('rec_temp')){
			$Rectemp = session('rec_temp');
		    $Retemp1 = M('members')->field("id")->where("user_name = '{$Rectemp}'")->find();
			$Retemp2 = M('ausers')->field("id")->where("user_name = '{$Rectemp}'")->find();//`mxl:teamreward`
		    if($Retemp1['id']>0){
				$data['recommend_id'] = $Retemp1['id'];//推荐人为投资人
				$data['recommend_type'] = 0;//`mxl:teamreward`
			}
			//`mxl:teamreward`
			if($Retemp2['id']>0){
				$data['recommend_id'] = $Retemp2['id'];//推荐人为经纪人
				$data['recommend_type'] = 1;
			}
			//`mxl:teamreward`
		}
		$newid = M('members')->add($data);
		if($newid){
		    session('name_temp',NULL);
		    session('pwd_temp',NULL);
            addCoupon($newid, 1, "新用户注册奖励");
            noTify($newid,2);
            /* 如果要手机/邮箱验证后对应通知设置选中，去掉注释（存在需求问题，请不要删除）
            if(session('temp_phone')){
                noTify($newid,3);
            }
            if(session('email_temp')){
                noTify($newid,1);
            }
            */
			$updata['cell_phone'] = session("temp_phone");
			$b = M('member_info')->where("uid = {$newid}")->count('uid');
			if ($b == 1){
				M("member_info")->where("uid={$newid}")->save($updata);
			}else{
				$updata['uid'] = $newid;
				$updata['cell_phone'] = session("temp_phone");
				M('member_info')->add($updata);
			} 
			session('u_id',$newid);
			session('u_user_name',$data['user_name']);
			session('new_guide',1);
			return $newid;
		}
		
		
	}
	public function sendphone() {
	 $code = text($_POST['code']);
          if(!$code){
                 ajaxmsg("", 3);
          }else{
                    if($_SESSION['code'] != sha1($code)){
                          ajaxmsg("", 4);
                    }
          }
	    $result = GlobalModel::send_msg_limit($this->uid);
	    if ($result==false){
	        ajaxmsg("",0);
	    }
		$smsTxt = FS("Webconfig/smstxt");
		$smsTxt = de_xie($smsTxt);
		$phone = text($_POST['cellphone']);
		$xuid = M('members') -> getFieldByUserPhone($phone, 'id');
		if ($xuid > 0 && $xuid <> $this -> uid) ajaxmsg("", 2);

		$code = rand_string_reg(6, 1, 2);
		$datag = get_global_setting();
		$is_manual = $datag['is_manual'];
		
		if ($is_manual == 0) { // 如果未开启后台人工手机验证，则由系统向会员自动发送手机验证码到会员手机，
			$res = sendsms($phone, str_replace(array("#UserName#", "#CODE#"), array(session('u_user_name'), $code), $smsTxt['verify_phone']));
			// session("temp_phone", $phone);
			// ajaxmsg();
		} else { // 否则，则由后台管理员来手动审核手机验证
			$res = true;
			$phonestatus = M('members_status') -> getFieldByUid($this -> uid, 'phone_status');
			if ($phonestatus == 1) ajaxmsg("手机已经通过验证", 1);
			$updata['phone_status'] = 3; //待审核
			$updata1['user_phone'] = $phone;
			$a = M('members') -> where("id = {$this->uid}") -> count('id');
			if ($a == 1) $newid = M("members") -> where("id={$this->uid}") -> save($updata1);
			else {
				M('members') -> where("id={$this->uid}") -> setField('user_phone', $phone);
			} 

			$updata2['cell_phone'] = $phone;
			$b = M('member_info') -> where("uid = {$this->uid}") -> count('uid');
			if ($b == 1) $newid = M("member_info") -> where("uid={$this->uid}") -> save($updata2);
			else {
				$updata2['uid'] = $this -> uid;
				$updata2['cell_phone'] = $phone;
				M('member_info') -> add($updata2);
			} 
			$c = M('members_status') -> where("uid = {$this->uid}") -> count('uid');
			if ($c == 1) $newid = M("members_status") -> where("uid={$this->uid}") -> save($updata);
			else {
				$updata['uid'] = $this -> uid;
				$newid = M('members_status') -> add($updata);
			} 
			if ($newid) {
				ajaxmsg();
			} else ajaxmsg("验证失败", 0); 
			// ////////////////////////////////////////////////////////////
		} 
		
		if ($res) {
			session("temp_phone", $phone);
			ajaxmsg();
		} else ajaxmsg("", 0);
	}
	
	public function validatephone() {
		if (session('code_temp')==text($_POST['code'])) {
			$updata['phone_status'] = 1;
			if (!session("temp_phone")) {
				ajaxmsg("验证失败", 0);
				exit();
			}
            $mid = $this->regaction();
			
			$newid = setMemberStatus($mid, 'phone', 1, 10, '手机');
			if ($newid) {
			    addCoupon($mid, 2, "手机认证奖励");
				ajaxmsg("验证成功",1);
			} else{
				ajaxmsg("验证失败", 0);
			}
		} else {
			//$this->regaction();
			ajaxmsg("验证校验码不对，请重新输入！", 2);
		} 
	}  
	
	public function emailverify(){
	    $data['uid'] = intval($_GET['uid']);
	    $uid = $data['uid'];
	    $data['new_email'] = text($_GET['email']);
	    $data['time'] = intval($_GET['time']);
	    $data['sign'] = text($_GET['sign']);
	    if(!empty($data['new_email']) && !empty($data['sign']) && !empty($data['uid']) && !empty($data['time'])){
	        if((time()-$data['time'])>3600*24){
	            $this->error("您的激活链接已过期，请重新申请！");
	        }
	        $check = SignModel::check_sign($data);
	        if($check){
        	    $data1['user_email'] = $data['new_email'];
        		$data1['last_log_time']=time();
	            $newid = M('members')->where("id = $uid")->save($data1);//更改邮箱
	            if($newid){
	                $this->success("邮箱修改成功",__APP__."/member");
	            }else {
	                $this->error("邮箱修改失败");
	            }
	        }else {
	            $this->error("邮箱修改失败");
	        }
	    }else {
	        $code = text($_GET['vcode']);
	        $uk = is_verify(0,$code,1,60*1000);
	        if(false===$uk){
	            $this->error("验证失败");
	        }else{
	            $this->assign("waitSecond",3);
	            setMemberStatus($uk, 'email', 1, 9, '邮箱');
	            $this->success("验证成功",__APP__."/member");
	        }
	    }
	}
	
	public function getpasswordverify(){
		$code = text($_GET['vcode']);
		$uk = is_verify(0,$code,7,60*1000);
		if(false===$uk){
			$this->error("验证失败");
		}else{
			session("tmp_verify",$uk);
			$this->display('getpassword3');
		}
	}
/*	
	public function setnewpass(){
		$d['content'] = $this->fetch();
		echo json_encode($d);
	}
	
	public function dosetnewpass(){
		$per = C('DB_PREFIX');
		$uid = session("temp_get_pass_uid");
		$oldpass = M("members")->getFieldById($uid,'user_pass');
		if($oldpass == md5($_POST['pass'])){
			$newid = true;
		}else{
			$newid = M()->execute("update {$per}members set `user_pass`='".md5($_POST['pass'])."' where id={$uid}");
		}
		
		if($newid){
			session("temp_get_pass_uid",NULL);
			ajaxmsg();
		}else{
			ajaxmsg('',0);
		}
	}
*/	
	
	public function ckuser(){
		$map['user_name'] = text($_POST['UserName']);
		$m_count = M('members')->where($map)->count('id');    //用户表
		$a_count = M('ausers')->where($map)->count('id');    //管理员表
		if ($m_count > 0 || $a_count > 0) {
		    exit(json_encode(array('status'=>0)));
		}else {
		    ajaxmsg();
		}
	}
	
	public function ckemail(){
		$map['user_email'] = text($_POST['Email']);
		$count = M('members')->where($map)->count('id');
        
		if ($count>0) {
			$json['status'] = 0;
			exit(json_encode($json));
        } else {
			$json['status'] = 1;
			exit(json_encode($json));
        }
	}

	public function ckphone(){
    		$map['user_phone'] = text($_POST['phone']);
    		$count = M('members')->where($map)->count('id');
    		if ($count>0) {
                        echo 0;exit;
            } else {
                         echo 1;exit;
            }
    	}

	public function emailvsend(){
		session('email_temp',text($_POST['email']));
		$mid = $this->regaction();
				
		$status=Notice(8,$mid);
		if($status) ajaxmsg('邮件已发送，请注意查收！',1);
		else ajaxmsg('邮件发送失败，请重试！',0);
		
    }
	public function ckcode(){
		if($_SESSION['code'] != sha1(strtolower($_POST['sVerCode']))){
			echo (0);
		 }else{
			echo json_encode(array("code"=>1));
        }
	}
	
	public function verify(){
	    import("ORG.Util.Imagecode");
	    $imagecode=new Imagecode(113,30);//(96,30);//参数控制图片宽、高
	    $imagecode->imageout();
	}
	
	public function regsuccess(){
		$this->assign('userEmail',M('members')->getFieldById($this->uid,'user_email'));
		$d['content'] = $this->fetch();
		echo json_encode($d);
	}

    //找回密码，账户验证
	public function getpassword(){
		$this->display();
	}
	
	public function ckusername(){
	    if($_SESSION['code'] != sha1(strtolower($_POST['sVerCode']))) ajaxmsg('<font style="color:red"> × 验证码输入错误！</font>',0);
	    $user_name = text($_POST['UserName']);
	    $rule  = "/^(13|14|15|17|18)[0-9]{9}$/A";
	    preg_match($rule,$user_name,$result);
	    if (!empty($result)){
	        $map['user_phone'] = $result[0];
	    }else{
	        $map['user_name'] = $user_name;
	    }
	    $arr = M('members')->field('id,user_name')->where($map)->find();
	    if (is_array($arr)){
	        session("tmp_uid",$arr['id']);
	        session("tmp_uname",$arr['user_name']);
	        ajaxmsg();
	    }
	    else ajaxmsg('<font style="color:red"> × 账户不存在！</font>',0);
	}

	public function getpassword2(){
	    if (session('tmp_uid')){
	        $pwd_uid = session('tmp_uid');
	        $list = getMemberstatus($pwd_uid);
	        if (empty($list)){
	            $this->error("该账户未进行认证，请联系客服！",__APP__."/");
	        }
	        //是否开启手机验证
	        $datag = get_global_setting();
	        $is_manual = $datag['is_manual'];
	        $this->assign("is_manual",$is_manual);
	        $this->assign('list',$list);
	        $this->display();
	    }else{
	        $this->error("非法操作",'/member/common/getPassWord/');
	    }
	}
	
	#验证密保答案
	public function ck_question()
	{
	    $result =array();
	    $uid = session('tmp_uid');
	    $answer1 = $_POST['answer1'];
	    $answer2 = $_POST['answer2'];
	    $result = M('member_safequestion')->where('uid='.$uid)->find();
	    if(empty($result)){
	        $this->error("非法操作",'/member/common/getPassWord/');
	    }
	    if($result[answer1] == $answer1 AND $result[answer2] == $answer2) {
	        session("tmp_verify",$uid);
	        ajaxmsg("", 1);
	    }else{
	        ajaxmsg("× 答案错误，请重新输入！", 2);
	    }
	}
	# 找回密码--手机--发送验证码
	public function getpwdsendphone()
	{
	    $result = GlobalModel::send_msg_limit(session('tmp_uid'));
	    if ($result==false){
	        ajaxmsg("", 0);
	    }
	    $smsTxt = FS("Webconfig/smstxt");
	    $smsTxt = de_xie($smsTxt);
	    $phone = text($_POST['cellphone']);
	    //手机号验证
	    $map['id'] = session('tmp_uid');
	    $vo = M('members')->field('user_phone')->where($map)->find();
	    if ($vo['user_phone']!=$phone) ajaxmsg("", 2);
	    $code = rand_string($map['id'],6,1,2);
	    $res = sendsms($phone, str_replace(array("#UserName#", "#CODE#"), array(session('tmp_uname'), $code), $smsTxt['verify_phone']));
	    if ($res) {
	        ajaxmsg();
	    }
		else ajaxmsg("", 0);
	}
	//找回密码--验证身份--手机
	public function cksubform(){
	    $map['id'] = session('tmp_uid');
	    $vo = M('members')->field('user_phone')->where($map)->find();
	    if ($vo['user_phone']!=text($_POST['Mobile'])) ajaxmsg("× 手机号不一致！", 2);
	    
	    if( is_verify($map['id'],text($_POST['Vcode']),2,10*60) ){
	        session("tmp_verify",$map['id']);
	        ajaxmsg();
	    }else{
			ajaxmsg("× 验证码错误，请重新输入！",2);
		}
	}
	
    //找回密码--重置密码--page
	public function getpassword3(){
	    if (session('tmp_verify')){
	        $this->display();
	    }else{
	        $this->error("非法操作",'/member/common/getPassWord/');
	    }
		
	}
	public function changepass(){
	    if (session('tmp_verify')){
	        $map['id'] = session('tmp_verify');
	        $newid = M('members')->where($map)->setField('user_pass',md5($_POST['new_pwd']));
	        if($newid){
	            NoticeSet('chk1',$map['id']);
	            session('tmp_verify',NULL);
	            session("tmp_uid",NULL);
	            session("tmp_uname",NULL);
	            ajaxmsg();
	        }
	        else ajaxmsg('',0);
	    }else{
	        $this->error("非法操作",'/member/common/getPassWord/');
	    }
	}

	public function getpassword4(){
		$this->display();
	}
	//找回密码--邮箱验证--tg.aip
	public function dogetpass(){
		//(false!==strpos($_POST['u'],"@"))?$data['user_email'] = text($_POST['u']):$data['user_name'] = text($_POST['u']);
	    $data['user_email'] = text($_POST['u']);
	    $data['user_name'] = session("tmp_uname");
		$vo = M('members')->field('id')->where($data)->find();
		if(is_array($vo)){
			$res = Notice(7,$vo['id']);
			if($res) ajaxmsg();
			else ajaxmsg('',0);
		}else{
			ajaxmsg('',0);
		}
	}
    public function register2(){
        if (session('name_temp')==NULL){
            $this->error("非法操作",'/member/index/index/');
        }
        else {
            //是否开启手机验证
            $datag = get_global_setting();
            $is_manual = $datag['is_manual'];
            $this->assign("is_manual",$is_manual);
            
            $this->display();
        }
	}
	public function phone(){
		$this->assign("phone",$_GET['phone']);
		$data['content'] = $this->fetch();
		exit(json_encode($data));
		
	}
	
	//跳过手机验证
	public function skipphone(){
		unset($_SESSION['temp_phone']);
		$this->regaction();
		ajaxmsg();
		
	}
	//推荐人检测
	public function ckInviteUser(){
		$map['user_name'] = text($_POST['InviteUserName']);
		$map2['user_name'] = text($_POST['InviteUserName']);
		$map2['u_group_id'] = 26;
		$count = M('members')->where($map)->count('id');
		$count2 = M('ausers')->where($map2)->count('id');
        
		if ($count==1 || $count2==1) {
			$json['status'] = 1;
			exit(json_encode($json));
        } else {
			$json['status'] = 0;
			exit(json_encode($json));
        }
	}
	
	//新手指引状态
	public function cancel_guide(){
	    session('new_guide',null);
	}
}
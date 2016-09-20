<?php

class CommonAction extends MobileAction
{
    var $notneedlogin=true;
    public function index(){
        $this->display();
    }


    public function logins(){

        if (isset($_COOKIE['NameCookie'])){
            $this->assign('user_name', $_COOKIE['NameCookie']);
        }
        $global = get_global_setting();
        $this->assign("is_manual",$global['is_manual']);//判断是否开启手机注册

        $loginconfig = FS("Webconfig/loginconfig");//判断快捷登录是否开启
        $this->assign("loginconfig",$loginconfig);
        $this->display();
    }


    public function register(){
        $loginconfig = FS("Webconfig/loginconfig");//判断快捷登录是否开启
        $this->assign("loginconfig",$loginconfig);
        if($_GET['invite']){
            session("tmp_invite_user",$_GET['invite']);
            $this->assign('tmp_invite_user', $_GET['invite']);
        }else{
			session("tmp_invite_user",null);
		}
//        $datag = get_global_setting();
//        $is_manual=$datag['is_manual'];
        //$this->assign("is_manual",$is_manual);
        $this->display();
    }


    /************登录*******************/
    public function actlogin(){
        $user_name = text($_POST['user_name']);
        $pass = text($_POST['user_pass']);
        $vo = M('members')->field('id,user_name,user_email,user_pass,is_ban')->where("user_name='{$user_name}'")->find();
        if(!$vo){
            ajaxmsg("没有此用户！",0);
        }
        if($vo['is_ban']==1){
            ajaxmsg("您的帐户已被冻结，请联系客服处理！",0);
        }
        if($vo['user_pass'] != md5($pass)){
            ajaxmsg("密码错误，请重新输入！",0);
        }

        session('u_id', $vo['id']);
        session('u_user_name', $vo['user_name']);
        $JumpUrl =U('M/user/');
        ajaxmsg();
    }

    /********************注册**************************/

    public function register2(){
        $phone = text($_POST['user_phone']);
        $ruser = text($_POST['user_name']);
        //file_put_contents("333.txt",trim($ruser));
        session('phones',$phone);
        session('username',$ruser);

        //检测手机号是否已被注册

        //检测用户是否存在
        $Retemp1 = M('members')->field("id")->where("user_name = '{$ruser}'")->find();
        $Retemp2 = M('ausers')->field("id")->where("user_name = '{$ruser}'")->find();
        if ($Retemp1['id'] > 0 || $Retemp2['id'] > 0) {
            ajaxmsg('用户名已经存在',0);
        }

        $xuid = M('members')->getFieldByUserPhone($phone,'id');
        if($xuid){
            ajaxmsg("该手机号已被注册~",0);
        }
        ajaxmsg();
    }

    public function register3(){
        $myphone = session('phones');
        $myname = session('username');
        //$code = session('code');
        //dump($_SESSION['code']);
        $this->assign('tmp_invite_user', session('tmp_invite_user'));
        $this->assign("myphone",$myphone);
        $this->assign("myname",$myname);
        //dump($_SESSION)
        $this->display();
    }

    public function regaction(){
        //验证手机验证码
        $datag = get_global_setting();
        $is_manual=$datag['is_manual'];
        if($is_manual == 0){
            if(text($_POST['phone_code']) == ""){
                ajaxmsg('请输入验证码！',0);
            }
            if (md5(session('code_temp'))==text(md5($_POST['phone_code']))) {
                if(session('send_time')< (time()-36000)){
                    session('temp_phone',null);
                    session('send_time', null);
                    ajaxmsg('手机验证码超时',0);
                }
            }else{
                ajaxmsg('手机验证码错误',0);
            }
            $data['user_phone'] = text($_POST['user_phone']);
        }
        else{
            $data['user_phone'] = text($_POST['user_phone']);
        }


        $data['user_name'] = text($_POST['user_name']);
        $data['user_pass'] = text(md5($_POST['user_pass']));
        $data['recommend_id'] = text($_POST['recommend_id']);
        //file_put_contents("222",text($_POST['recommend_id']));
        $data['reg_time'] = time();
        $data['reg_ip'] = get_client_ip();
        $data['last_log_time'] = time();
        $data['last_log_ip'] = get_client_ip();
        
        if(session("tmp_invite_user")) {
        	$data['recommend_id'] = MembersModel::get_Decrypt_uid(session("tmp_invite_user"));
        	//file_put_contents('1.txt',$data['recommend_id']);exit;
        }else{
        	/*******判断推荐人是否填写*****/
        	$data['recommend_id'] = text($_POST['recommend_id']);
        	$Rectemp=$data['recommend_id'];
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
        	empty($data['recommend_id']) ? $data['recommend_id'] = 0 : $data['recommend_id'] = $Retemp1['id'];
        }

        /*******判断推荐人是否填写*****/
        /*$Rectemp=$data['recommend_id'];
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
        empty($data['recommend_id']) ? $data['recommend_id'] = 0 : $data['recommend_id'] = $Retemp1['id'];*/

        //检测用户是否存在
        $ruser = $data['user_name'];
        $Retemp1 = M('members')->field("id")->where("user_name = '{$ruser}'")->find();
        $Retemp2 = M('ausers')->field("id")->where("user_name = '{$ruser}'")->find();
        if ($Retemp1['id'] > 0 || $Retemp2['id'] > 0) {
            ajaxmsg('用户名已经存在',0);
        }

        $newid = M('members')->add($data);
        if($newid){
            setMemberStatus($newid, 'phone', 1, 10, '手机');
            addCoupon($newid, 1, "新用户注册奖励");
            addCoupon($newid, 2, "手机认证奖励");
            //noTify($newid,2);
            $updata['cell_phone'] = $data['user_phone'];
            $b = M('member_info')->where("uid = {$newid}")->count('uid');
            if ($b == 1){
                M("member_info")->where("uid={$newid}")->save($updata);
            }else{
                $updata['uid'] = $newid;
                $updata['cell_phone'] = $data['user_phone'];
                M('member_info')->add($updata);
            }
            session('u_id',$newid);
            session('u_user_name',$data['user_name']);
            ajaxmsg();
        }


    }

	public function protocal(){
        $this->display();
    }

    public function protocals(){
        $name = '服务协议';
        $t = M("article_category")->field("type_content")->where("type_name='{$name}'")->find();
        ajaxmsg($t['type_content']);
    }

    /***************发送手机验证码*************/
    public function sendphone() {
        if($_SESSION['code'] != sha1($_POST['code'])){
            ajaxmsg("验证码错误!请重新输入",3);
        }
        $smsTxt = FS("Webconfig/smstxt");
        $smsTxt = de_xie($smsTxt);
        $phone = text($_POST['cellphone']);
        $myname = text($_POST['myname']);
        $xuid = M('members') -> getFieldByUserPhone($phone, 'id');
        if ($xuid > 0 && $xuid <> $this -> uid) ajaxmsg("手机号码已经存在", 2);

        $code = rand_string_reg(6, 1, 2);
        $datag = get_global_setting();
        $is_manual = $datag['is_manual'];

        if ($is_manual == 0) { // 如果未开启后台人工手机验证，则由系统向会员自动发送手机验证码到会员手机，
            $res = sendsms($phone, str_replace(array("#UserName#", "#CODE#"), array($myname, $code), $smsTxt['verify_phone']));
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

    /************找回密码时输入用户先进行检测是否存在****************/
    public function ischeckuser(){
        $username = text($_POST['user_name']);
        $user_check = M('members')->field('id,user_name')->where("user_name='{$username}'")->find();
        if(!$user_check){
            ajaxmsg("没有此用户！",0);
        }
        ajaxmsg();
    }

    //修改密码获取验证码............
    public function sendphonepass() {
        $smsTxt = FS("Webconfig/smstxt");
        $smsTxt = de_xie($smsTxt);
        $phone = text($_POST['cellphone']);

        $minfo = M("members m")->join("lzh_members_status ms ON m.id = ms.uid")->where("m.user_phone = '$phone'")->field("m.id, m.user_name, m.user_phone, ms.phone_status, m.last_log_time")->find();

        if (empty($minfo['user_phone']) === true || empty($minfo['phone_status']) === true){
            ajaxmsg("该用户未绑定此手机，不能使用短信方式找回密码！",0);
        }
        $code = rand_string_reg(6, 1, 2);
        $datag = get_global_setting();
        $is_manual = $datag['is_manual'];

        $res = sendsms($phone, str_replace(array("#UserName#", "#CODE#"), array($minfo['user_name'], $code), $smsTxt['verify_phone']));
        session("temp_phone", $phone);
        if ($res) {
            session("temp_phone", $phone);
            ajaxmsg();
        } else ajaxmsg("发送失败，请重试", 0);
    }

    function getpass(){
        $username = $_GET['name'];
        $this->assign("name",$username);
        $this->display();
    }
    /*****************找回密码*******************/
    public function setpassword(){
        $username = text($_GET['name']);
        $setpass = M("members")->field("user_name,user_phone,id")->where("user_name = '{$username}'")->find();
        $this->assign("username",$username);
        $this->assign("setpass",$setpass);
        $this->display();
    }

    /**
     * 用户注册
     *
     */
    public function setuppass(){
        $username = text($_POST['user_name']);
        $userphone = text($_POST['user_phone']);
        $password = $_POST['pass_word'];
        $mobilecode=$_POST["phone_code"];
        if(session('code_temp')!=$mobilecode){
            ajaxmsg("验证码不正确",0);
        }

        $user = M("members")->field("user_name,id,user_pass,user_phone")->where("user_name='{$username}'")->find();
        if($user['user_phone'] !=$userphone){//判断此手机号是否是当前用户的手机号
            ajaxmsg("手机号码错误",0);
        }
        $where['id']=$user['id'];
        $data['user_pass']=md5($password);
        $data['reg_time']=time();
        $data['reg_ip']=get_client_ip();

        $newid = M("members")->where($where)->save($data);
        if($newid){
            ajaxmsg();
        }else{
            ajaxmsg("修改失败",0);
        }
    }

    /*************退出登录****************/

    /*public function actlogout(){
        $this->_memberloginout();
        //uc登陆
        $loginconfig = FS("Webconfig/loginconfig");
        $uc_mcfg  = $loginconfig['uc'];
        if($uc_mcfg['enable']==1){
            require_once C('APP_ROOT')."Lib/Uc/config.inc.php";
            require C('APP_ROOT')."Lib/Uc/uc_client/client.php";
            $logout = uc_user_synlogout();
        }
        //uc登陆
        $this->assign("uclogout",de_xie($logout));
        $this->success("注销成功",__APP__."/m/index/");
    }*/
    
    public function actlogout(){
    	if ($_GET['action'] == 'logout'){
    		session(null);
    		echo '1';
    	}
    }



}

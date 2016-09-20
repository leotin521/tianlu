<?php
// 本类由系统自动生成，仅供测试用途
class OauthAction extends MCommonAction {
	var $notneedlogin=true;
	var $memberTable = "lzh_members";
	public function  _MyInit(){
		if($this->uid > 0){
			redirect("/member/");
			exit;
		}
	}
    public function index(){
		exit();
    }
	
	//QQ登陆
	public function qq(){
		$loginconfig = FS("Webconfig/loginconfig");
		if($loginconfig['qq']['enable']==0) $this->error("此登陆方式已被暂时关闭，请选择其他方式登陆",__APP__."/");

		require C("APP_ROOT")."Lib/Oauth/qq2.0/oauth/qq_login.php";
		qq_login($_SESSION["appid"], $_SESSION["scope"], $_SESSION["callback"]);

	}
	
	public function qqlogin(){
		$loginconfig = FS("Webconfig/loginconfig");
		require C("APP_ROOT")."Lib/Oauth/qq2.0/oauth/qq_callback.php";
		//QQ登录成功后的回调地址,主要保存access token
		qq_callback();
		//获取用户标示id
		get_openid();
		//获取用户信息
		$userInfo = get_user_info();
		$map['openid'] = text($_SESSION['openid']);//唯一ID
		$map['site'] = 'qq';
		$this->appCk($map,$userInfo->nickname, 'qq');
	}
	
	//新浪登陆
	public function sina(){
		$loginconfig = FS("Webconfig/loginconfig");
		define( "WB_AKEY" , $loginconfig['sina']['akey'] );
		define( "WB_SKEY" , $loginconfig['sina']['skey'] );
		define( "WB_CALLBACK_URL" , C('WEB_URL').__APP__.'/member/oauth/sinalogin' );
		require C("APP_ROOT")."Lib/Oauth/sina/saetv2.ex.class.php";
		//构造快捷登录接口
		$o = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
		$code_url = $o->getAuthorizeURL( WB_CALLBACK_URL );
		redirect($code_url);
	}
	
	public function sinalogin(){
		$loginconfig = FS("Webconfig/loginconfig");
		define( "WB_AKEY" , $loginconfig['sina']['akey'] );
		define( "WB_SKEY" , $loginconfig['sina']['skey'] );
		define( "WB_CALLBACK_URL" , C('WEB_URL').__APP__.'/member/oauth/sinalogin' );
		require C("APP_ROOT")."Lib/Oauth/sina/saetv2.ex.class.php";
		$o = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
		
		if (isset($_REQUEST['code'])) {
			$keys = array();
			$keys['code'] = htmlspecialchars($_REQUEST['code'], ENT_QUOTES);
			$keys['redirect_uri'] = WB_CALLBACK_URL;
			try {
				$token = $o->getAccessToken( 'code', $keys ) ;
			} catch (OAuthException $e) {
			
			}
		}
		
		if ($token) {
			$_SESSION['token'] = $token;
		}else{
			exit("出错，请重试");
		}
		
		$map['openid'] = text($token['uid']);//唯一ID
		$map['site'] = 'sina';
		$this->appCk($map,"@sina".$map['openid'], 'sina');//nickname
	}
	
	
	public function appbind(){
		$tempuid = session('temp_bind_appid');
		$oauth_id = session('temp_bind_oauth_id');
		if(!$tempuid){
			$data['content'] = $this->fetch('msg');
			exit(json_encode($data));
		}
		if(!$_POST){
			$data['content'] = $this->fetch('loginbox');
			exit(json_encode($data));
		}
		
		$uname = text($_POST['uname']);
		$email = text($_POST['email']);
		$cn = M()->table($this->memberTable)->where("(user_email= '{$email}' OR user_name='{$uname}') AND id<>{$tempuid}")->count('*');
		if($cn>0){
			$data['status'] = 0;
			$data['message'] = "已有用户使用了此用户名或者邮箱，请重新设置";
			exit(json_encode($data));
		}else{
			$updata['user_name'] = $uname;
			$updata['user_email'] = $email;
			$updata['user_pass'] = md5($_POST['pass']);
	
			//uc注册
			$loginconfig = FS("Webconfig/loginconfig");
			$uc_mcfg  = $loginconfig['uc'];
			if($uc_mcfg['enable']==1){
				require_once C('APP_ROOT')."Lib/Uc/config.inc.php";
				require C('APP_ROOT')."Lib/Uc/uc_client/client.php";
				$uid = uc_user_register($updata['user_name'], $_POST['pass'], $updata['user_email']);
				if($uid <= 0) {
					if($uid == -1) {
						ajaxmsg('用户名不合法',0);
					} elseif($uid == -2) {
						ajaxmsg('包含不允许注册的词语',0);
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

			$newid = M()->table($this->memberTable)->where("id={$tempuid}")->save($updata);
			if($newid>0){
				$per = C('DB_PREFIX');
				M()->query("UPDATE `{$per}oauth` SET is_bind=1 WHERE id = {$oauth_id}");
				$this->applogin($tempuid);
				$data['status'] = 1;
				exit(json_encode($data));
			}else{
				$data['status'] = 0;
				$data['message'] = "绑定失败，请重试";
				exit(json_encode($data));
			}
		}
	}
	
	private function appCk($map,$nickname, $type){
		$vo = M('oauth')->field('id,logintimes,is_bind,bind_uid')->where($map)->find();
		$this->assign('type', $type);
		if($type=='qq'){
			$user_info = (array)get_user_info();
			$user['img'] = $user_info['figureurl_2'];
			$user['type_img'] = 'qq_n.png';
			$this->assign('nickname', $nickname);
		}elseif($type=='sina'){
			$user['img'] = '/Style/H/images/sina.jpg';
			$user['type_img'] = 'sina_n.png';
		}

		$this->assign('user', $user);
		if(is_array($vo)){//以前登陆过
			$data['id'] = $vo['id'];
			$data['logintimes'] = $vo['logintimes'];
			$this->logapp($data);
			if($vo['is_bind']==1){//已经绑定会员帐户
				$this->applogin($vo['bind_uid']);
				redirect('/member/');
			}else{
				$_SESSION['temp_bind_appid']=$vo['bind_uid'];
				$_SESSION['temp_bind_oauth_id'] = $vo['id'];
				$this->display('index');
			}
		}elseif($nickname){
			$map['nickname'] = text($nickname);
			$newid = $this->addapp($map);
			
			if($newid) $this->display('index');
		}
	}
	public function appbindold(){
		$tempuid = session('temp_bind_appid');
		$oauth_id = session('temp_bind_oauth_id');
		if(!$tempuid){
			$data['content'] = $this->fetch('msg');
			exit(json_encode($data));
		}
		if(!$_POST){
			$data['content'] = $this->fetch('loginbox');
			exit(json_encode($data));
		}
		
		$uname = text($_POST['uname']);
		$pass = md5($_POST['pass']);
		$cn = M()->table($this->memberTable)->field('id')->where("user_name='{$uname}' AND user_pass='{$pass}'")->find();
		
		if(!is_array($cn)){
			$data['status'] = 0;
			$data['message'] = "用户名或者密码不对，如果你在本站还没有帐户请选择新用户绑定";
			exit(json_encode($data));
		}else{
				$per = C('DB_PREFIX');
				M()->execute("UPDATE `{$per}oauth` SET is_bind=1, bind_uid={$cn['id']} WHERE id = {$oauth_id}");
				M()->table($this->memberTable)->where("id={$tempuid}")->delete();
				$this->applogin($cn['id']);
				$data['status'] = 1;
				exit(json_encode($data));
		}
	}
	private function logapp($data){
		$save['id'] = $data['id'];
		$save['logintimes'] = ($data['logintimes']+1);
		$save['logintime'] = time();
		M('oauth')->save($save);
	}
	
	private function addapp($data){
		$map=array();
		//会员表要保存的信息
		$remember['user_name'] = "@{$data['site']}_".$data['nickname'].rand(1,999);
		$remember['user_pass'] = md5($data['openid'].$data['site'].time());
		$remember['reg_time'] = time();
		$remember['reg_ip'] = get_client_ip();
		$map['bind_uid'] = M()->table($this->memberTable)->add($remember);
		//会员表要保存的信息
		
		if($map['bind_uid']>0){
			$map['is_bind'] = 0;
			$map['nickname'] = $data['nickname'];
			$map['openid'] = $data['openid'];
			$map['site'] = $data['site'];
			$map['logintimes'] = 1;
			$map['addtime'] = time();
			$map['logintime'] = time();
			$newid = M('oauth')->add($map);
			$_SESSION['temp_bind_appid']=$map['bind_uid'];
			$_SESSION['temp_bind_oauth_id'] = $newid;
		}else{
			$newid = false;
		}
		return $newid;
	}
	
	private function applogin($uid){//模拟登陆
		$this->_memberlogin($uid);
	}
/*	
	private function set_session($user=array()){

		if($user){//登陆成功的相关操作
			//全局使用的常用信息
			foreach($user as $key=>$v){
				session("u_".$key,$v);
			}
			//记录登陆
			$savelogin['uid'] = $user['id'];
			$savelogin['ip'] = get_client_ip();
			$savelogin['add_time'] = time();
			M('member_login')->add($savelogin);
		}
	}
*/
}
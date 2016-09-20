<?php
    /**
    * QQ 新浪微博快捷登录绑定解除功能
    */
    class OauthloginAction extends MCommonAction
    {
        /**
        * 快捷登录列表
        * 
        */
        public function index()
        {
            $loginconfig = FS("Webconfig/loginconfig");//判断快捷登录是否开启
            if ($loginconfig['qq']['enable']==1 || $loginconfig['sina']['enable']==1) {
                $this->assign("loginconfig",$loginconfig);
                $qq_list = M("oauth")->field('*')->where("site='qq' and bind_uid=".$this->uid)->select();
                $sina_list = M("oauth")->field('*')->where("site='sina' and bind_uid=".$this->uid)->select();
                $this->assign('qq_list', $qq_list);
                $this->assign('sina_list', $sina_list);
                $this->display();
            }else{
                $this->error('非法操作！');
            }
            
        }
        /**
        * 解除快捷登录
        * 
        */
        public function del_oauth()
        {
			$id = intval($_GET['id']);
            $result = M("oauth")->where("id=".$id." and bind_uid=".$this->uid)->delete();
            if($result){
                $this->success('成功解除绑定', U('index'));
            }else{
                $this->error('解除失败！');
            }                
        }
        /**
        * 添加绑定快捷登录
        * 
        */
        public function add_oauth()
        {
            $type = $this->_get('type');
			if($type=='qq'){
				$this->QQLogin();
			}elseif($type=="sina"){
				$this->SinaLogin();
			}else{
				$this->error("参数错误");
			}
        }
        
        private function QQLogin()
        {
            $loginconfig = FS("Webconfig/loginconfig");
			if($loginconfig['qq']['enable']==0) $this->error("此登陆方式已被暂时关闭，请选择其他方式登陆");

			require C("APP_ROOT")."Lib/Oauth/qq2.0/oauth/qq_login.php";
	
			qq_login($_SESSION["appid"], $_SESSION["scope"], $_SERVER[SERVER_NAME].'/'.U("qq")); 
        }
		public function qq()
		{
			$loginconfig = FS("Webconfig/loginconfig");
			require C("APP_ROOT")."Lib/Oauth/qq2.0/oauth/qq_callback.php";
			qq_callback();  
			//获取用户标示id
			get_openid();
			//获取用户信息
			$userInfo = (array)get_user_info();
			$map['openid'] = text($_SESSION['openid']);//唯一ID
			$map['site'] = 'qq';

			$field = array(
				'is_bind' => 1,
				'site'  =>  'qq',
				'openid' => $_SESSION['openid'],
				'nickname' => $userInfo['nickname'],
                'avatar'   => $userInfo['figureurl_2'],
				'logintimes' => 1,
				'bind_uid' =>  $this->uid,
				'logintime' => time(),
				'addtime'  => time(),
			);
			$this->OauthSave($map, $field);
			
		}
        private function SinaLogin()
        {
            $loginconfig = FS("Webconfig/loginconfig");
			define( "WB_AKEY" , $loginconfig['sina']['akey'] );
			define( "WB_SKEY" , $loginconfig['sina']['skey'] );
			define( "WB_CALLBACK_URL" , C('WEB_URL').__APP__.'/member/oauthlogin/sina' );
			require C("APP_ROOT")."Lib/Oauth/sina/saetv2.ex.class.php";
			//构造快捷登录接口
			$o = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
			$code_url = $o->getAuthorizeURL( WB_CALLBACK_URL );
			redirect($code_url);
        }

		public function sina()
		{   
			if(isset($_GET['error_uri'])){
				$this->error('绑定已取消', U('oauthlogin/index'));
			}
			require_once C("APP_ROOT")."Lib/Oauth/sina/saetv2.ex.class.php";
			$loginconfig = FS("Webconfig/loginconfig"); 
			define( "WB_AKEY" , $loginconfig['sina']['akey'] );
			define( "WB_SKEY" , $loginconfig['sina']['skey'] );
			define( "WB_CALLBACK_URL" , C('WEB_URL').__APP__.'/member/oauthlogin/sina' );
			$o = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
			$keys['code'] = htmlspecialchars($_REQUEST['code'], ENT_QUOTES);
			$keys['redirect_uri'] = WB_CALLBACK_URL;
			
			$token = $o->getAccessToken( 'code', $keys ); 
			if(empty($token)){
				$this->error('绑定已取消', U('oauthlogin/index'));
			}
			$_SESSION['token'] = $token;
			
			$c = new SaeTClientV2(WB_AKEY, WB_SKEY, $token['access_token']);
			$uid = $token['uid'];
			$user_message = $c->show_user_by_id($uid);

			$map['openid'] = text($uid);//唯一ID
			$map['site'] = 'sina';

			$field = array(
				'is_bind' => 1,
				'site'  =>  'sina',
				'openid' => $uid,
				'nickname' => $user_message['name'],
                'avatar'   => $user_message['avatar_large'],
				'logintimes' => 1,
				'bind_uid' =>  $this->uid,
				'logintime' => time(),
				'addtime'  => time(),
			);
			$this->OauthSave($map, $field);
		}
		private function OauthSave($map, $data)
		{ 
			if(M("oauth")->where($map)->count()){
				$this->error("此账号已经绑定账号，请删除绑定后重新绑定", U("Oauthlogin/index"));
			}
			if(M("oauth")->add($data)){
				$this->success("绑定成功", U("index"));
			}else{
				$this->error("绑定失败，请重试！", U("index"));
			}
		}
    }
?>

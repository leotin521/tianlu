<?php
    class PubAction extends Action
    {
        
         public function Verify()
         {
            import("ORG.Util.Image");
            Image::buildImageVerify();   
         }
        /**
         * 用户登陆
         */
         public function login()
         {   
			 $hetong = M('hetong')->field('name,dizhi,tel')->find();
			$this->assign("web",$hetong);
             if($this->isPost()){
                //[username] => dsfsaf [password] => asdf [verify] => mebr
                 if($_SESSION['verify'] != md5($_POST['verify'])) {
                   $this->error('验证码错误！');
                 }
                 $user_name = $this->_post('username');
                 $pass = $this->_post('password');
                 $vo = M('members')->field('id,user_name,user_email,user_pass,is_ban')->where("user_name='{$user_name}'")->find();
                 if(!$vo){
                    $this->error('没有此用户！'); 
                 }
                 if($vo['is_ban']==1){
                    $this->error('您的帐户已被冻结，请联系客服处理！');
                 }  
                 if($vo['user_pass'] != md5($pass)){
                    $this->error('密码错误，请重新输入！'); 
                 }
                 
                 session('u_id', $vo['id']);
                 session('u_user_name', $vo['user_name']);
                 $JumpUrl = session('JumpUrl')?session('JumpUrl'):U('M/user/index');
                 session('JumpUrl','');
                 $this->success("登陆成功！", $JumpUrl);
                 
             }else{
                 if(session('u_id')){
                    $this->redirect('M/user/index');   
                 }
                 session('JumpUrl', $_SERVER['HTTP_REFERER']);
                 $this->display();    
             }
             
         }
         /**
         * 注销用户
         */
         public function Logout()
         {
            session(null);
            $this->success('安全退出!',U('M/index/index'));   
         } 
         
         /**
         * 用户注册
         * 
         */
         public function regist()
         {
			 $hetong = M('hetong')->field('name,dizhi,tel')->find();
			$this->assign("web",$hetong);
             if(session('u_id')){
                $this->redirect('M/user/index');   
             }
             if($this->isAjax()){
                 $email = $this->_post('email');
                 $username = $this->_post('username');
                 $password = $this->_post('password');
                 $verify = $this->_post('verify');
                 if(!$email || !$username || !$password || !$verify){
                     die("数据不完整");
                 }
                 if($_SESSION['verify'] != md5($verify)) {
                     die('验证码错误！');
                 }
                 if(M("members")->where("user_email='{$email}'")->count('id')){
                     die("邮箱被占用，请更换邮箱地址");
                 }
                 if(M("members")->where("user_name='{$username}'")->count('id')){
                     die("用户名已被占用，请更换");
                 }
                 $data = array(
                        'user_name'=>$username,
                        'user_pass'=>md5($password),
                        'user_email'=>$email,
                        'reg_time'=>time(),
                        'reg_ip' => get_client_ip(),
                 );
                 if($newid = M("members")->add($data)){
                     //Notice(1,$newid,array('email',$data['user_email'])); 
                     session('u_id', $newid);
                     session('u_user_name', $username);
                     echo '1';
                 }else{
                     die('注册失败');
                 }
             }else{
                 
                 $this->display();
             }
         }  
    }
?>

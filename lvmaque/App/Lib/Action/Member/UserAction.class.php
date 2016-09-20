<?php
// 本类由系统自动生成，仅供测试用途
class UserAction extends MCommonAction {

    public function index(){
		$this->display();
    }

    public function header(){
		$data['html'] = $this->fetch();
		exit(json_encode($data));
    }

    public function password(){
		$data['html'] = $this->fetch();
		exit(json_encode($data));
    }

    public function pinpass(){
		$data['html'] = $this->fetch();
		exit(json_encode($data));
    }

    public function changepass(){
		$old = md5($_POST['oldpwd']);
		$newpwd1 = md5($_POST['newpwd1']);
		$c = M('members')->where("id={$this->uid} AND user_pass = '{$old}'")->count('id');
		if($c==0) ajaxmsg('',2);
		$newid = M('members')->where("id={$this->uid}")->setField('user_pass',$newpwd1);
		if($newid){
			NoticeSet('chk1',$this->uid);
			ajaxmsg();
		}
		else ajaxmsg('',0);
    }

    public function changepin(){
        if(!preg_match("/^[a-zA-Z0-9_]{6,20}$/",$_POST['newpwd1'])){
            ajaxmsg("设置失败，密码需要数字字母下划线组成。",0);
        }
        $newpwd1 = md5($_POST['newpwd1']);
        $newid = M('members')->where("id={$this->uid}")->setField('pin_pass',$newpwd1);
        if($newid) {
            ajaxmsg();
        }
        else ajaxmsg("设置失败，请重试~",0);
        /*
		$old = md5($_POST['oldpwd']);
                 //////支付密码服务端验证 元/////
			if(!preg_match("/^[a-zA-Z0-9_]{6,20}$/",$_POST['newpwd1'])){
				ajaxmsg("设置失败，密码需要数字字母下划线组成。",0);
			}
           //////支付密码服务端验证/////   
		$newpwd1 = md5($_POST['newpwd1']);
		$c = M('members')->where("id={$this->uid}")->find();
		if($old==$newpwd1){
			ajaxmsg("设置失败，请勿让新密码与老密码相同。",0);
		}
		if(empty($c['pin_pass'])){
			if($c['user_pass'] == $old){
				$newid = M('members')->where("id={$this->uid}")->setField('pin_pass',$newpwd1);
				if($newid) ajaxmsg();
				else ajaxmsg("设置失败，请重试",0);
			}else{
				ajaxmsg("原支付密码(即登陆密码)错误，请重试",0);
			}
		}else{
			if($c['pin_pass'] == $old){
				$newid = M('members')->where("id={$this->uid}")->setField('pin_pass',$newpwd1);
				if($newid) ajaxmsg();
				else ajaxmsg("设置失败，请重试",0);
			}else{
				ajaxmsg("原支付密码错误，请重试",0);
			}
		}
		*/
    }

    public function msgset(){
		$this->assign("vo",M('sys_tip')->find($this->uid));
		$data['html'] = $this->fetch();
		exit(json_encode($data));
    }
	
	public function savetip(){
		$oldtip = M('sys_tip')->where("uid={$this->uid}")->count('uid');
		$data['tipset'] = text($_POST['Params']);
		$data['uid'] = $this->uid;
		if($oldtip) $newid = M('sys_tip')->save($data);
		else $newid = M('sys_tip')->add($data);
		//$this->display('Public:_footer');
		if($newid) echo 1;
		else echo 0;
	}

}
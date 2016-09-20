<?php
// 全局设置
class CommonAction extends ACommonAction
{
    public function member(){
		//$utype = C('XMEMBER_TYPE');
		$area=get_Area_list();
		$uid=intval($_GET['id']);
		$vo=M('members m')->field("m.is_transfer,m.user_email,m.customer_name,m.user_phone,m.id,m.credits,m.is_ban,m.user_type,m.user_name,m.integral,m.active_integral,mi.*,mm.*,mb.*")->join("{$this->pre}member_info mi ON mi.uid=m.id")->join("{$this->pre}member_money mm ON mm.uid=m.id")->join("{$this->pre}member_banks mb ON mb.uid=m.id")->where("m.id={$uid}")->find();
		$vo['id'] = $uid;
		$vo['is_ban'] = ($vo['is_ban']==0)?"未冻结":"<span style='color:red'>已冻结</span>";
		//$vo['user_type'] = $utype[$vo['user_type']];
		$vo['user_type'] = MembersModel::get_user_type($vo['is_transfer']);
		//银行名称
		$info = get_bconf_setting();
		$bank_list = $info['BANK_NAME'];
		$vo['bank_name'] = $bank_list[$vo['bank_name']];
		
		$this->assign("capitalinfo",getMemberBorrowScan($uid));
		$this->assign("wc",getUserWC($uid));
        $this->assign("credit", getCredit($uid));
        $this->assign("vo",$vo);
		$this->assign("user",$vo['user_name']);

		//*******2013-11-23*************
		$minfo =getMinfo($uid,true);
        $this->assign("minfo",$minfo); 

		$this->assign('benefit', get_personal_benefit($uid)); //收益相关
		$this->assign('out', get_personal_out($uid)); //支出相关
		$this->assign('pcount', get_personal_count($uid));
        $this->display();
    }
	
	public function sms(){
		$utype = C('XMEMBER_TYPE');
        if(in_array(intval($_GET['tab']), array(1,2,3,4)))  $tab = intval($_GET['tab']);
        else    $tab = 1;
		$m_id = intval($_GET['mid']);
		$members  = M('members')->field("user_name")->where("id={$m_id}")->find();
        $this->assign("user_name", $members['user_name']);
		
        $this->assign("tab", $tab);
        $this->assign("admin_id", $this->admin_id);
		$this->assign("mid", $m_id);
        $this->display();
    }

    public function sendsms(){//账户通讯
    	$info = cnsubstr(text($_POST['info']), 500);
    	$title = cnsubstr($info, 20);
    	if ($info == "") 	exit("发送内容不可为空");

        $smsLog['admin_id'] = $_SESSION['admin_id'];
        $smsLog['admin_real_name'] = $_SESSION['admin_user_name'];

        $smsLog['title'] = $title;
        $smsLog['content'] = $info;
        $smsLog['add_time'] = time();

    	if(intval($_POST['sms'])==1){//账户通讯
    		$user_name = text($_POST['user_name']);
    		$type = text($_POST['type']);

    		$user = M('members m')->field(" m.id,m.user_email,m.user_phone,ms.email_status,ms.phone_status ")->join(" lzh_members_status ms ON m.id=ms.uid ")->where(" m.user_name = '".$user_name."' ")->find();
    		if (!$user)		exit("找不到用户$user_name");

            if (stripos( $type,"1") && $user['email_status']==1){//邮件
                $sm = sendemail($user['user_email'],$title,$info);
                if($sm) $smsLog['user_email'] = $user['user_email'];
            }

            if (stripos( $type,"2") && $user['phone_status']==1){//短信
                $ss = sendsms($user['user_phone'],$info);
                if($ss) $smsLog['user_phone'] = $user['user_phone'];
            }

            if (stripos( $type,"4")){//站内信
                $si = true;
                addInnerMsg($user['id'],$title,$info);
                $smsLog['user_name'] = $user_name;
            }

            if($sm || $ss || $si){
                M('smslog')->add($smsLog);
				alogs("Smslog",0,1,'对'.$user_name.'成功执行了会员账户通讯通知操作！');//管理员操作日志
                exit("发送成功");
            }else{
				alogs("Smslog",0,0,'执行会员账户通讯通知操作失败！');//管理员操作日志
                exit("发送失败");
            }
    	}elseif(intval($_POST['sms'])==2){//具体通讯
    		$email = text($_POST['email']);
    		$phone = text($_POST['phone']);

    		if ($phone){
                $ss = sendsms($phone,$info);
                if($ss) $smsLog['user_phone'] = $phone;
            }

    		if ($email){
                $sm = sendemail($email,$title,$info);
                if($sm) $smsLog['user_email'] = $email;
            }

            if($sm || $ss ){
                M('smslog')->add($smsLog);
				alogs("Smslog",0,1,'成功执行了单个会员通讯通知操作！');//管理员操作日志
                exit("发送成功");
            }else{
				alogs("Smslog",0,0,'执行单个会员通讯通知操作失败！');//管理员操作日志
                exit("发送失败");
            }
    	}
    }

    public function sendgala(){//节日通讯
        set_time_limit(0);//设置脚本最大执行时间

        $info = cnsubstr(text($_POST['info']),500);
        $title = cnsubstr($info,12);
        if ($info == "")    exit("发送内容不可为空");

        $smsLog['admin_id'] = $_SESSION['admin_id'];
        $smsLog['admin_real_name'] = $_SESSION['admin_user_name'];

        $smsLog['title'] = $title;
        $smsLog['content'] = $info;
        $smsLog['add_time'] = time();

        $type = text($_POST['type']);
        $user_name = intval($_POST['user_name']);

        if ($user_name==2){//VIP会员
            $map = "m.is_transfer=0";
            $user = "普通会员";
        }elseif ($user_name==3){//非VIP会员
            $map = "m.is_transfer>0";
            $user = "借款会员";
        }else{//所有会员
            $map = ""; 
            $user = "所有会员";
        }

        if(stripos( $type,"1")) $smsLog['user_email'] = $user;
        if(stripos( $type,"2")) $smsLog['user_phone'] = $user;
        if(stripos( $type,"4")) $smsLog['user_name'] = $user;
        M('smslog')->add($smsLog);
       
        $result = M('members m')->field(" m.id,m.user_email,m.user_phone,ms.email_status,ms.phone_status ")->join(" lzh_members_status ms ON m.id=ms.uid ")->where($map)->select();
        
        if (stripos( $type,"4")) {//站内信
            foreach ($result as $k => $v) {
                addInnerMsg($v['id'],$title,$info);
            }
        }

        /*if (stripos( $type,"1")) {//邮件
            $i= 1;
            foreach ($user as $k => $v) {
                if($v['email_status']==1){
                    $to[floor($i/160)] .=$v['user_email'].",";
                    $i++;
                }
            }

            foreach ($to as $key => $val) {
                $val = substr($val, 0, strlen($val)-1 );

                if($key<6)     sendemail2($val,$title,$info);
                else           sendemail($val,$title,$info);
            }
        }*/

        if (stripos( $type,"2")) {//短信
            $i= 1;
            foreach ($result as $k => $v) {
                if($v['phone_status']==1){
                    $phone[floor($i/150)] .=$v['user_phone'].",";
                    $i++;
                }
            }
            //var_dump($phone);

            foreach ($phone as $key2 => $val2) {
                $val2 = substr($val2, 0, strlen($val2)-1 );
                sendsms($val2,$info);
                // var_dump("$val2,$info");
            }
        }
		alogs("Smslog",0,1,'对'.$user.'执行通讯通知操作成功！');//管理员操作日志
        exit("发送成功");
    }

    public function smslog(){
        $data = M('ausers')->field("id,real_name")->select(); //前台通讯发送人为空白原因是因为管理员的账户实名认证为空
        foreach ($data as $k => $v) {
            $admin_data[$v['id']] = $v['real_name'];
        }
        if(!empty($_GET['admin_id']))       $map['admin_id'] = intval($_GET['admin_id']);
       if(!empty($_GET['mid']))  {
			$m_id = intval($_GET['mid']);
			$members  = M('members')->field("user_name")->where("id={$m_id}")->find();
			$map['user_name'] = $members['user_name'];
		}
        if(!empty($_GET['user_email']))     $map['user_email'] = text($_GET['user_email']);
        if(!empty($_GET['user_phone']))     $map['user_phone'] = text($_GET['user_phone']);
	
	
        //分页处理-通讯系统分页修改
		import("ORG.Util.Page");
		$count = M('smslog')->where($map)->count();
		$p = new Page($count, 10);
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		$list = M('smslog')->where($map)->order("id asc")->limit($Lsql)->select();
			
			
		$this->assign("page", $page);
        $this->assign("list", $list);
        $this->assign('admin_data',$admin_data);
        $this->assign('map',$map);
	$data['html'] = $this->fetch('smslog_res');
	exit(json_encode($data)); 
    }
}
?>
<?php
//解决火狐swfupload的session bug
if (isset($_POST[session_name()]) && empty($_SESSION)) {
    session_destroy();
    session_id($_POST[session_name()]);
    session_start();
}
// 本类由系统自动生成，仅供测试用途
class VerifyAction extends MCommonAction {
   /**
    * view
    */
  public function index() {
		$_SESSION['u_selectid'] = $this -> _get('selectid');
		$id5_config = FS("Webconfig/id5");
		$this->assign("id5_enable",$id5_config['enabled']);   //1：开启     0：未开启      
		//是否开启手机验证
		$datag = get_global_setting();
		$is_manual=$datag['is_manual'];
		$this->assign("is_manual",$is_manual);
		//获取证件类型
		$info = get_basic();
		$card_type = $info['CARD_TYPE'];
		$this->assign("card_type",$card_type);
		//紧急联系人关系
		$relation = $info['RELATION'];
		$this->assign("relation",$relation);
		//密保问题
		$question = $info['SAFEQUESTION'];
		$this->assign("question",$question);
		
		$vo = safeset($this->uid);
		$this->assign("vo",$vo);
		$this -> display();
	} 
	/**
	 * 紧急联系人
	 */
	public function emery(){
	    $data = textPost($_POST);
	    $arr = M('members')->field('pin_pass')->where('id = '.$this->uid)->find();
	    if(md5($data['pin_pass'])!=$arr['pin_pass']){
	        ajaxmsg('支付密码不正确',0);
	    }
	    unset($data['pin_pass']);
	    $count = M('member_info')->where('uid = '.$this->uid)->count('uid');
	    if ($count>0){
	        $result = M('member_info')->where("uid = {$this->uid}")->save($data);
	    }else{
	        $data['uid'] = $this->uid;
	        $result = M('member_info')->add($data);;
	    }
	    if ($result){
	        ajaxmsg();
	    }
	    else ajaxmsg('设置失败，请重试~',0);
	}
	/**
	 * 发送邮件
	 */
    public function emailvsend(){
		$data['user_email'] = text($_POST['email']);
		$data['last_log_time']=time();
		$newid = M('members')->where("id = {$this->uid}")->save($data);//更改邮箱，重新激活
		if($newid){
			$status=Notice(8,$this->uid);
			if($status) ajaxmsg('邮件已发送，请注意查收~',1);
			else ajaxmsg('邮件发送失败,请重试~',0);
		}else{
			 ajaxmsg('新邮件修改失败~',2);
		}
    }
    /**
     * 邮箱验证
     */
	public function ckemail(){
		$map['user_email'] = text($_POST['Email']);
		$map['id']  = array('neq',$this->uid);
		$count = M('members')->where($map)->count('id');
        
		if ($count>0) {
			$json['status'] = 0;
			exit(json_encode($json));
        } else {
			$json['status'] = 1;
			exit(json_encode($json));
        }
	}
	/**
	 * 
	 * 实名认证--接口.
	 * 新版本  实名认证  与  上传认证  为两个不同的提交.
	 * 
	 */
	public function saveid() {
	    $id5_config = FS("Webconfig/id5");
		if (empty($_POST['real_name']) || empty($_POST['idcard'])){
			ajaxmsg("请输入真实姓名和身份证号码~", 0);
		}
		//查询条数
		$client = new SoapClient($id5_config['account_api']);
		$auth = new stdClass();
		$auth->UserName = $id5_config['account_name'];
		$auth->Password = $id5_config['account_password'];
		$json = $client->QueryBalance(array('request'=> '','cred'=> json_encode($auth)))->QueryBalanceResult;
		$result = json_decode($json);
		//$result->ExactBalance//多项认证的余额条数
		if ($result->SimpleBalance == 0){
		    $dw_kefu=get_qq(2);
		    ajaxmsg("认证失败，请联系客服{$dw_kefu[0]['qq_num']}", 0);
		}
		
		$hasId = M('member_info')->getFieldByIdcard($_POST['idcard'],'uid');
		if($hasId > 0 && $hasId != $this->uid){
			ajaxmsg("此身份证号码已被人使用~",0);
		}
		$c = FS('Webconfig/id5');
		if($c['enabled']=='1'){ 
			$res = real_name_auth_id5($_POST['real_name'], $_POST['idcard']);
			if($res == '一致'){
				$status = 1;				
			}else{
				ajaxmsg('身份证名称或者号码错误~',0);
			}
		}else{
			ajaxmsg("实名验证授权没有开启,可以试试上传认证~",0);
		}
		
		$name = $_POST['real_name'];
		$idcard = text($_POST['idcard']);
		$time = time();
		
        $model = new Model();
        $model->startTrans();
        
        $data = array(
        		'uid' => $this->uid,
                'card_type' => 1,
        		'up_time' => $time,
        		'status' => $status,
        		'idcard' => $idcard,
                'deal_info' => '系统认证'
        );
        
        $applyRes = M('name_apply') -> where("uid = {$this->uid}") -> save($data);
        if(!$applyRes){
	        $applyRes = M('name_apply') -> add($data);
        }
		unset($data['status'],$data['deal_info']);
		$data['real_name'] = $name;
		#判断性别
		$data['sex'] = hidecard($data['idcard'],11);
		$infoRes = M('member_info') -> where("uid = {$this->uid}") -> save($data);
		if(!$infoRes){
			$newid = M('member_info') -> add($data);
		}
		
		$data2 = array('id_status'=> $status, 'uid'=> $this->uid);
		$statusRes = M('members_status') -> where("uid={$this->uid}") -> save($data2);
		if(!$statusRes){
			$statusRes = M('members_status') -> where("uid={$this->uid}") -> add($data2);
		}
		//if($applyRes && $newid && $statusRes){
		if($statusRes){
			$model->commit();
			setMemberStatus($this->uid, 'id', $status, 2, '实名');    // 自动验证时更新积分
			addCoupon($this->uid, 3, "实名认证奖励");
			ajaxmsg();
		}else{
			$model->rollback();
			ajaxmsg("保存失败，请重试~", 0);
		}
	}
/*
	public function idcheck() {
		// 开启错误提示
		ini_set('display_errors', 'on');
		error_reporting(E_ALL);
		$id5_config = FS("Webconfig/id5");
		if ($id5_config['enabled'] == 0) {
			//echo '实名验证授权没有开启！！！';
			$this -> saveid();
			exit;
		} 
		$data['real_name'] = text($_POST['real_name']);
		$data['idcard'] = text($_POST['idcard']);
		$data['up_time'] = time(); 
		// ///////////////////////
		$data1['idcard'] = text($_POST['idcard']);
		$data1['up_time'] = time();
		$data1['uid'] = $this -> uid;
		$data1['status'] = 0;
        $card = $data1['idcard'];
       
		$xuid = M('member_info')->getFieldByIdcard($data['idcard'],'uid');
		if($xuid>0 && $xuid!=$this->uid) ajaxmsg("此身份证号码已被人使用",0);
		$b = M('name_apply') -> where("uid = {$this->uid}") -> count('uid');
		if ($b == 1) {
			M('name_apply') -> where("uid ={$this->uid}") -> save($data1);
		} else {
			M('name_apply') -> add($data1);
		} 
		// //////////////////////
		if (empty($data['real_name']) || empty($data['idcard'])) ajaxmsg("请填写真实姓名和身份证号码", 0);

		$c = M('member_info') -> where("uid = {$this->uid}") -> count('uid');
		if ($c == 1) {
			$newid = M('member_info') -> where("uid = {$this->uid}") -> save($data);
		} else {
			$data['uid'] = $this -> uid;
			$newid = M('member_info') -> add($data);
		} 

		function get_data($d) {
			preg_match_all('/<ROWS>(.*)<\/ROWS>/isU', $d, $arr);
			$data = array();
			$aa = array();
			$cc = array();
			foreach($arr[1] as $k => $v) {
				preg_match_all('#<result_gmsfhm>(.*)</result_gmsfhm>#isU', $v, $ar[$k]);
				preg_match_all('#<gmsfhm>(.*)</gmsfhm>#isU', $v, $sfhm[$k]);
				preg_match_all('#<result_xm>(.*)</result_xm>#isU', $v, $br[$k]);
				preg_match_all('#<xm>(.*)</xm>#isU', $v, $xm[$k]);
				preg_match_all('#<xp>(.*)</xp>#isU', $v, $cr[$k]);

				$data[] = $ar[$k][1];
				$aa[] = $br[$k][1];
				$cc[] = $cr[$k][1];
				$sfhm[] = $sfhm[1];
				$xm[] = $xm[1];
			} 
			$resa['data'] = $data[0][0];
			$resa['aa'] = $aa[0][0];
			$resa['cc'] = $cc[0][0];
			$resa['xm'] = $xm[0][0][0];
			$resa['sfhm'] = $sfhm[0][0][0];
			return $resa;
		} 
		$res = '';
		try {
			$client = new SoapClient(C("APP_ROOT") . "common/wsdl/NciicServices.wsdl");
			$licenseCode = $id5_config['auth']; //file_get_contents(C("APP_ROOT")."common/wsdl/license.txt");
			$condition = '<?xml version="1.0" encoding="UTF-8" ?>
        <ROWS>
            <INFO>
            <SBM>' . time() . '</SBM>
            </INFO>
            <ROW>
                <GMSFHM>公民身份号码</GMSFHM>
                <XM>姓名</XM>
            </ROW>
            <ROW FSD="100022" YWLX="身份证认证测试-错误" >
            <GMSFHM>' . trim($_REQUEST['idcard']) . '</GMSFHM>
            <XM>' . trim($_REQUEST['real_name']) . '</XM>
            </ROW>
            
        </ROWS>'; //330381198609262623 薛佩佩
			$params = array('inLicense' => $licenseCode,
				'inConditions' => $condition,
				);
			$res = $client -> nciicCheck($params);
		} 
		catch(Exception $e) {
			echo $e -> getMessage();
			exit;
		} 
		$shuju = get_data($res -> out); 
		// ajaxmsg("实名认证成功",1);
		if (@$shuju['data'] == '一致' && @$shuju['aa'] == '一致') {
			$time = time();
			$temp = M('members_status') -> where("uid={$this->uid}") -> find();
			if(is_array($temp)){
				$cid['id_status'] = 1;
			    $status = M('members_status') -> where("uid={$this->uid}") -> save($cid);
			}else{
			    $dt['uid'] = $this -> uid;
				$dt['id_status'] = 1;
				$status = M('members_status') -> add($dt);
			}
			if($status){
			    $data2['status'] = 1;
				$data2['deal_info'] = '会员中心实名认证成功';
				$new = M("name_apply") -> where("uid={$this -> uid}") -> save($data2);
				if($new) ajaxmsg();
			}else{
			    $data2['status'] = 0;
				$data2['deal_info'] = '会员中心实名认证失败';
				$new = M("name_apply") -> where("uid={$this -> uid}") -> save($data2);
				ajaxmsg("认证失败",0);
			}
		}else{   
		    ajaxmsg("实名认证失败",0);
		    $mm = M('members_status') -> where("uid={$this->uid}") -> setField('id_status', 3);
		    if ($mm == 1) {
			    ajaxmsg('待审核', 0);
		    } else {
			    $dt['uid'] = $this -> uid;
			    $dt['id_status'] = 3;
			    M('members_status') -> add($dt);
			    ajaxmsg('等待审核', 0);
		    }
		}
		$data['html'] = $this -> fetch();
		exit(json_encode($data));
	}
*/
	/**
	 * 实名认证--上传
	 */
	public function up_saveid(){
	    //身份证图片上传判断
	    $isimg = session('url1');
	    $isimg2 = session('url2');
	    if(empty($isimg)) ajaxmsg("请先上传身份证正面图片~",0);
	    if(empty($isimg2)) ajaxmsg("请先上传身份证反面图片~",0);
	    //接收传值
	    $data['real_name'] = text($_POST['realname']);
	    $data['idcard'] = text($_POST['idcard']);
	    $data['card_type'] = intval($_POST['card_type']);  ///证件类型
	    $data['card_img'] = htmlspecialchars($isimg, ENT_QUOTES);
	    $data['card_back_img'] = htmlspecialchars($isimg2, ENT_QUOTES);
	    $data['up_time'] = time();
	    #判断性别
	    $data['sex'] = hidecard($data['idcard'],11);
	    #---------------------------------------------------------------
	    $data1['idcard'] = text($_POST['idcard']);
	    $data1['up_time'] = time();
	    $data1['card_type'] = intval($_POST['card_type']); //证件类型
	    $data1['uid'] = $this->uid;
	    $data1['status'] = 3; 
	    //验证不空
	    if(empty($data['real_name'])||empty($data['idcard']))  ajaxmsg("请输入真实姓名和身份证号码~",0);
	    //验证唯一
	    $xuid = M('member_info')->getFieldByIdcard($data['idcard'],'uid');
	    if($xuid>0 && $xuid!=$this->uid) ajaxmsg("此身份证号码已被人使用~",0);
	    //实名认证表
	    $b = M('name_apply')->where("uid = {$this->uid}")->count('uid');
	    if($b==1){
	        M('name_apply')->where("uid ={$this->uid}")->save($data1);
	    }else{
	        M('name_apply')->add($data1);
	    }
	    //监测身份证认证状态
	    $idstatus=M('members_status')->where('uid = '.$this->uid)->find();
	    if($idstatus['id_status'] == 3) {
	        ajaxmsg("您已提交身份验证，请刷新页面~",0);
	    } elseif($idstatus['id_status'] == 1){
	        ajaxmsg("您已完成身份验证，请刷新页面~",0);
	    }else{
	    
	    }
	    //修改身份证--删除之前上传图
	    $img=M('member_info')->field('card_img,card_back_img')->where('uid = '.$this->uid)->find();
	    unlink('./'.$img['card_img']);
	    unlink('./'.$img['card_back_img']);
	    
	    //更改|添加用户信息
	    $c = M('member_info')->where("uid = {$this->uid}")->count('uid');
	    if($c==1){
	        $newid = M('member_info')->where("uid = {$this->uid}")->save($data);
	    }else{
	        $data['uid'] = $this->uid;
	        $newid = M('member_info')->add($data);
	    }
	    //清空session
	    session('url1',NULL);
	    session('url2',NULL);

	    //修改状态为等待审核
	    if($newid){
	        $ms=M('members_status')->where("uid={$this->uid}")->setField('id_status',3);
	        if($ms==1){
	            ajaxmsg();
	        }else{
	            $dt['uid'] = $this->uid;
	            $dt['id_status'] = 3;
	            M('members_status')->add($dt);
	        }
	        ajaxmsg();
	    }
	    else  ajaxmsg("保存失败，请重试~",0);
	}
    /**
     * 安全设置--密保问题（首次密保设置/新密保设置）
     */
	public function questionsave(){
		$data['question1'] = text($_POST['q1']);
		$data['question2'] = text($_POST['q2']);
		$data['answer1'] = text($_POST['a1']);
		$data['answer2'] = text($_POST['a2']);
		$data['add_time'] = time();
		$c = M('member_safequestion')->where("uid = {$this->uid}")->count('uid');
		if($c==1) $newid = M("member_safequestion")->where("uid={$this->uid}")->save($data);
		else{
			$data['uid'] = $this->uid;
			$newid = M('member_safequestion')->add($data);
		}
		if($newid){
			M('members_status')->where("uid = {$this->uid}")->setField('safequestion_status',1);
			$newid = setMemberStatus($this->uid, 'safequestion', 1, 6, '安全问题');
			if($newid){
				addInnerMsg($uid,"您的安全问题已设置","您的安全问题已设置");
			}
			ajaxmsg();
		}
		else  ajaxmsg("",0);
	}
    /**
     * 安全设置--手机首次认证
     */
    public function sendphone(){
        $result = GlobalModel::send_msg_limit($this->uid);
        if ($result==false){
            ajaxmsg("操作繁忙，请稍后再试！",3);
        }
		$smsTxt = FS("Webconfig/smstxt");
		$smsTxt=de_xie($smsTxt);
		$phone = text($_POST['cellphone']);
		$xuid = M('members')->getFieldByUserPhone($phone,'id');
		if($xuid>0 && $xuid<>$this->uid) ajaxmsg("",2);
		
		$code = rand_string($this->uid,6,1,2);
		$datag = get_global_setting();
		$is_manual=$datag['is_manual'];
		if($is_manual==0){//如果未开启后台人工手机验证，则由系统向会员自动发送手机验证码到会员手机，
			$res = sendsms($phone,str_replace(array("#UserName#","#CODE#"),array(session('u_user_name'),$code),$smsTxt['verify_phone']));
		}else{//否则，则由后台管理员来手动审核手机验证
			$res = true;
			$phonestatus = M('members_status')->getFieldByUid($this->uid,'phone_status');
			if($phonestatus==1) ajaxmsg("手机已经通过验证",1);
            if( $phonestatus == 3 ) ajaxmsg("手机验证正在审核中", 3); // 如果正在审核TODO:，但是手机号码不相符，应该再提示手机号码填写错误,细节需慢慢优化
			$updata['phone_status'] = 3;//待审核
			$updata['uid'] = $this->uid;//待审核

			$updata1['user_phone'] = $phone;
			$a = M('members')->where("id = {$this->uid}")->count('id');
			if($a==1) $newid = M("members")->where("id={$this->uid}")->save($updata1);
			else{
				M('members')->where("id={$this->uid}")->setField('user_phone',$phone);
			}
			
			/*$updata2['cell_phone'] = $phone; //TODO: members_status 有cell_phone字段吗
			$b = M('member_info')->where("uid = {$this->uid}")->count('uid');
			if($b==1) $newid = M("member_info")->where("uid={$this->uid}")->save($updata2);
			else{
				$updata2['uid'] = $this->uid;
				M('member_info')->add($updata2);
			}*/
			$c = M('members_status')->where("uid = {$this->uid}")->count('uid');
			if($c==1) $newid = M("members_status")->where("uid={$this->uid}")->save($updata);
			else{
				$updata['uid'] = $this->uid;
				$newid = M('members_status')->add($updata);
			}
			if($newid !== false){
				ajaxmsg();
			}else{
			    ajaxmsg("验证失败",0);
			} 
		}
		if($res){
			session("temp_phone",$phone);
			ajaxmsg();
		}
		else ajaxmsg("",0);
    }

    public function done(){
		$data['html'] = $this->fetch();
		exit(json_encode($data));
    }
    /**
     * 手机认证
     */
    public function validatephone(){
        
        $phone = text($_POST['cellphone']);
        $xuid = M('members')->getFieldByUserPhone($phone,'id');
        if($xuid>0 && $xuid<>$this->uid) ajaxmsg("该手机号已被注册~".$_POST['cellphone'],2);
        
		$phonestatus = M('members_status')->getFieldByUid($this->uid,'phone_status');
		if($phonestatus==1) ajaxmsg("手机已经通过验证",1);
		if( is_verify($this->uid,text($_POST['code']),2,10*60) ){
			$updata['phone_status'] = 1;
			if(!session("temp_phone")) ajaxmsg("验证失败",0);
			
			$updata1['user_phone'] = session("temp_phone");
			$a = M('members')->where("id = {$this->uid}")->count('id');
			if($a==1) $newid = M("members")->where("id={$this->uid}")->save($updata1);
			else{
				M('members')->where("id={$this->uid}")->setField('user_phone',session("temp_phone"));
			}
			
			$updata2['cell_phone'] = session("temp_phone");
			$b = M('member_info')->where("uid = {$this->uid}")->count('uid');
			if($b==1) $newid = M("member_info")->where("uid={$this->uid}")->save($updata2);
			else{
				$updata2['uid'] = $this->uid;
				M('member_info')->add($updata2);
			}
			$c = M('members_status')->where("uid = {$this->uid}")->count('uid');
			if($c==1) $newid = M("members_status")->where("uid={$this->uid}")->save($updata);
			else{
				$updata['uid'] = $this->uid;
				$newid = M('members_status')->add($updata);
			}
			if($newid){
				$newid = setMemberStatus($this->uid, 'phone', 1, 10, '手机');
                addCoupon($this->uid, 2, "手机认证奖励");
				ajaxmsg();
				
			}
			else  ajaxmsg("验证失败",0);
		}else{
			ajaxmsg("手机验证码不对，请重新输入！",2);
		}
    }
    /**
     * 身份证上传
     */
    public function ajaximg1(){
        //$file_infor = var_export($_FILES,true);
        //上传文件类型列表
         $uptypes = array(
            'image/jpg',
            'image/jpeg',
            'image/png',
            'image/pjpeg',
            'image/gif',
            'image/bmp',
            'image/x-png'
        );
        $max_file_size = 2000000 ;     //上传文件大小限制, 单位BYTE
        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            if (!is_uploaded_file($_FILES['file']['tmp_name'])) //是否存在文件
            {
                ajaxmsg('图片不存在~',0);
            }
            $file = $_FILES["file"];
            if($max_file_size < $file["size"]) //检查文件大小
            {
                ajaxmsg('文件太大~',0);
            }
            if(!in_array($file["type"], $uptypes))  //检查文件类型
            {
                ajaxmsg('文件类型不符~'.$file["type"],0);
            }
            $destination_folder = C('MEMBER_UPLOAD_DIR').'Idcard/' ;    //上传文件路径
            if(!file_exists($destination_folder))
            {
                mkdir($destination_folder);
            }
            $filename = $file["tmp_name"];
            $image_size = getimagesize($filename);
            $pinfo = pathinfo($file["name"]);
            $ftype = $pinfo['extension'];
            $destination = $destination_folder.time().".".$ftype;
            if (file_exists($destination))
            {
                ajaxmsg('文件名已经存在了~',0);
            }
            if(!move_uploaded_file ($filename, $destination))
            {
                ajaxmsg('移动文件出错~',0);
            }
            if (session('url1')){
                $url = session('url1');
                unlink('./'.$url);
            }
            session('url1',$destination);
            $pinfo=pathinfo($destination);
            $fname=$pinfo['basename'];
            ajaxmsg('/'.$destination,1);
        }else{
            ajaxmsg('提交方式不对~',0);
        }
    }
    /**
     * 身份张反面上传
     */
    public function ajaximg2(){
        //上传文件类型列表
         $uptypes = array(
            'image/jpg',
            'image/jpeg',
            'image/png',
            'image/pjpeg',
            'image/gif',
            'image/bmp',
            'image/x-png'
        );
        $max_file_size = 2000000 ;     //上传文件大小限制, 单位BYTE
        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            if (!is_uploaded_file($_FILES['files']['tmp_name'])) //是否存在文件
            {
                ajaxmsg('图片不存在~',0);
            }
            $file = $_FILES["files"];
            if($max_file_size < $file["size"]) //检查文件大小
            {
                ajaxmsg('文件太大~',0);
            }
            if(!in_array($file["type"], $uptypes))  //检查文件类型
            {
                ajaxmsg('文件类型不符~'.$file["type"],0);
            }
            $destination_folder = C('MEMBER_UPLOAD_DIR').'Idcard/' ;    //上传文件路径
            if(!file_exists($destination_folder))
            {
                mkdir($destination_folder);
            }
            $filename = $file["tmp_name"];
            $image_size = getimagesize($filename);
            $pinfo = pathinfo($file["name"]);
            $ftype = $pinfo['extension'];
            $destination = $destination_folder.time()."_back.".$ftype;
            if (file_exists($destination))
            {
                ajaxmsg('文件名已经存在了~',0);
            }
            if(!move_uploaded_file ($filename, $destination))
            {
                ajaxmsg('移动文件出错~',0);
            }
            if (session('url2')){
                $url = session('url2');
                unlink('./'.$url);
            }
            session('url2',$destination);
            $pinfo=pathinfo($destination);
            $fname=$pinfo['basename'];
            ajaxmsg('/'.$destination,1);
        }else{
            ajaxmsg('提交方式不对~',0);
        }
    }
	/**
	 * 安全设置--登录密码
	 */
	public function changepass(){
	    $old = md5($_POST['oldpwd']);
	    $newpwd1 = md5($_POST['newpwd1']);
	    $c = M('members')->where("id={$this->uid} AND user_pass = '{$old}'")->count('id');
	    if($c==0) ajaxmsg('',2);
	    $newid = M('members')->where("id={$this->uid}")->setField('user_pass',$newpwd1);
	    if($newid){
	        MTip('chk1',$this->uid, '', '', null, 1);
	        MTip('chk1',$this->uid, '', '', null, 2);
	        MTip('chk1',$this->uid, '', '', null, 3);
	        //NoticeSet('chk1',$this->uid);
	        ajaxmsg();
	    }
	    else ajaxmsg('',0);
	}
	/**
	 * 安全设置--支付密码
	 */
	public function changepin(){
	    #服务端验证支付密码
	    if(!preg_match("/^[a-zA-Z0-9_]{6,20}$/",$_POST['newpwd1'])){
	        ajaxmsg("密码需要数字字母下划线组成~",0);
	    }
	    $newpwd1 = md5($_POST['newpwd1']);
	    if(isset($_POST['oldpwd'])) {

    	    $c = M('members')->where("id={$this->uid}")->find();
    	    $old = md5($_POST['oldpwd']);
    	    if($old==$newpwd1){
    	        ajaxmsg("新密码与老密码不能一样~",0);
    	    }
    	    if(empty($c['pin_pass'])){
    	        if($c['user_pass'] == $old){
    	            $newid = M('members')->where("id={$this->uid}")->setField('pin_pass',$newpwd1);
    	            if($newid) ajaxmsg();
    	            else ajaxmsg("设置失败，请重试~",0);
    	        }else{
    	            ajaxmsg("原支付密码错误，请重试~",0);
    	        }
    	    }else{
    	        if($c['pin_pass'] == $old){
    	            $newid = M('members')->where("id={$this->uid}")->setField('pin_pass',$newpwd1);
    	            if($newid) ajaxmsg();
    	            else ajaxmsg("设置失败，请重试~",0);
    	        }else{
    	            ajaxmsg("原支付密码错误，请重试~",0);
    	        }
    	    }
	    }else{
	        $newid = M('members')->where("id={$this->uid}")->setField('pin_pass',$newpwd1);
	        if($newid) {
	            ajaxmsg();
	        }
	        else ajaxmsg("设置失败，请重试~",0);
	    }
	}	
	/**
	 * 安全设置--找回支付密码--获取验证码
	 */
	public function getpwdsendphone()
	{
	    $result = GlobalModel::send_msg_limit($this->uid);
	    if ($result==false){
	        ajaxmsg("",0);
	    }
	    $smsTxt = FS("Webconfig/smstxt");
	    $smsTxt = de_xie($smsTxt);
	    $vo = M('members')->field('user_phone')->find($this->uid);
	    $phone = $vo['user_phone']; 
	    //手机号验证
	    $map['id'] = $this->uid;
	    $code = rand_string($map['id'],6,1,2);
	    $res = sendsms($phone, str_replace(array("#UserName#", "#CODE#"), array(session('u_user_name'), $code), $smsTxt['verify_phone']));
	    if ($res) {
	        ajaxmsg();
	    }
	    else ajaxmsg("", 0);
	}
	/**
	 * 安全设置--找回支付密码--修改密码
	 */
	public function getchangepass(){
	   if( is_verify($this->uid,text($_POST['Vcode']),2,10*60) ){
	       $newid = M('members')->where("id={$this->uid}")->setField('pin_pass',md5($_POST['newpin']));
	       if($newid) ajaxmsg();
	       else ajaxmsg("设置失败，请重试~",0);
	    }else{
			ajaxmsg("验证码错误，请重新输入~",0);
		}
	}
    /**
     * 安全设置--密码保护--原问题验证
     */
	public function verifyquestion(){
	    $map['answer1'] = text($_POST['old_answer1']);
	    $map['answer2'] = text($_POST['old_answer2']);
	    $map['uid']  = $this->uid;
	    $c = M('member_safequestion')->where($map)->count('uid');
	    if($c==0) ajaxmsg('',0);
	    else{
	        session('temp_safequestion',1);
	        ajaxmsg();
	    }
	}
	/**
	 * 安全设置--密码保护--短信验证
	 */
	public function safeverify()
	{
	    if( is_verify($this->uid,text($_POST['Vcode']),2,10*60) ){
	        ajaxmsg();
	    }else{
	        ajaxmsg("<front style='color:red'>验证码错误，请重新输入~</font>",0);
	    }
	}
	/**
	 * 安全设置--邮箱验证
	 */
	public function sendemail(){
	    $map['id']  = $this->uid;
	    $map['user_email'] = text($_POST['oldemail']);
	    $c = M('members')->where($map)->count('id');
	    if ($c==0){
	        ajaxmsg('原邮箱验证失败，请重试~',0);
	    }
	    $data['new_email'] = text($_POST['email']);
	    $map['user_email'] = $data['new_email'];
	    $map['id']  = array('neq',$this->uid);
	    $count = M('members')->where($map)->count('id');
	    if ($count>0) {
	        ajaxmsg('新邮箱已在本站注册~',0);
	        exit;
	    } 
	    $data['time'] = time();
	    $data['uid'] = $this->uid;
	    $status=Notice(8,$this->uid,$data);
	    if($status){
	        ajaxmsg();
	    } else{
	        ajaxmsg('邮件发送失败,请重试~',0);
	    }
	}
	/**
	 * 安全设置--手机认证--获取短信
	 */
	public function getmobliecode()
	{
	    $result = GlobalModel::send_msg_limit($this->uid);
	    if ($result==false){
	        ajaxmsg("", 0);
	    }
	    $smsTxt = FS("Webconfig/smstxt");
	    $smsTxt = de_xie($smsTxt);
	    $phone = text($_POST['cellphone']);
	    //手机号验证
	    $map['id']  = $this->uid;
	    $vo = M('members')->field('user_phone')->where($map)->find();
	    if ($vo['user_phone']!=$phone) ajaxmsg("", 2);
	    $code = rand_string($map['id'],6,1,2);
	    $res = sendsms($phone, str_replace(array("#UserName#", "#CODE#"), array(session('tmp_uname'), $code), $smsTxt['verify_phone']));
	    if ($res) {
	        ajaxmsg();
	    }
	    else ajaxmsg("", 0);
	}
	/**
	 * 安全设置--手机认证--新号码获取短信
	 */
	public function getmobliecode1()
	{
	    $result = GlobalModel::send_msg_limit($this->uid);
	    if ($result==false){
	        ajaxmsg("", 0);
	    }
	    $smsTxt = FS("Webconfig/smstxt");
	    $smsTxt = de_xie($smsTxt);
	    $phone = text($_POST['cellphone']);
	    $xuid = M('members')->getFieldByUserPhone($phone,'id');
	    if($xuid>0 && $xuid<>$this->uid) ajaxmsg("× 手机号已被注册",2);
	    //手机号验证
	    $map['id']  = $this->uid;
	    $code = rand_string($map['id'],6,1,2);
	    $res = sendsms($phone, str_replace(array("#UserName#", "#CODE#"), array(session('tmp_uname'), $code), $smsTxt['verify_phone']));
	    if ($res) {
	        ajaxmsg();
	    }
	    else ajaxmsg("", 0);
	}
	/**
	 * 安全设置--手机认证--验证原手机号
	 */
	public function cksubnext(){
	    $map['id'] = $this->uid;
	    $vo = M('members')->field('user_phone')->where($map)->find();
	    if ($vo['user_phone']!=text($_POST['Mobile'])) ajaxmsg("手机号不一致~", 2);
	     
	    if( is_verify($map['id'],text($_POST['Vcode']),2,10*60) ){
	        ajaxmsg();
	    }else{
	        ajaxmsg("验证码错误，请重新输入~",2);
	    }
	}
	/**
	 * 安全设置--手机认证--修改新手机号
	 */
	function subphone(){
	    $map['id'] = $this->uid;
	    if( is_verify($map['id'],text($_POST['Vcode']),2,10*60) ){
	        //$xuid = M('members')->getFieldByUserPhone($phone,'id');
	        $newid = M('members')->where($map)->setField('user_phone',text($_POST['Mobile']));
	        if($newid) {
	            ajaxmsg();
	        }
	        else ajaxmsg('新号码不能与旧号码一样喔~',0);
	    }else{
	        ajaxmsg("验证码错误，请重新输入~",2);
	    }
	}
	/**
	 * 安全设置--手机认证--验证密保问题
	 */
	function subans(){
	    $map['answer1'] = text($_POST['Answer']);
	    $count = M('member_safequestion')->where($map)->count('uid');
	    if ($count>0) {
	        $json['status'] = 1;
	        exit(json_encode($json));
	    } else {
	        $json['status'] = 0;
	        exit(json_encode($json));
	    }
	}

	// 实名认证-充值提现
	
	public function saveid_select() {
		if (empty($_POST['real_name']) || empty($_POST['idcard'])){
			ajaxmsg("请填写真实姓名和身份证号码", 0);
		}
		$_POST['idcard'] = htmlspecialchars($_POST['idcard'], ENT_QUOTES);
		$_POST['real_name'] = htmlspecialchars($_POST['real_name'], ENT_QUOTES);
		$hasId = M('member_info')->getFieldByIdcard($_POST['idcard'],'uid');
		if($hasId > 0 && $hasId != $this->uid){
			ajaxmsg("此身份证号码已被人使用",0);
		}
		$realname_type = text($_POST['realname_type']);
		if($realname_type==1){
			$c = FS('Webconfig/id5');
			if($c['enabled']){ // 自动认证
				$res = real_name_auth_id5($_POST['real_name'], $_POST['idcard']);
				if($res == '一致'){
					$status = 1;				
				}else{
					ajaxmsg('身份证名称或者号码错误',0);
				}
			}else{
			    ajaxmsg('自动认证功能未开启',0);
			}
		}else{
		    if(!session('idcardimg') || !session('idcardimg2')){
				ajaxmsg("请先上传身份证图片",0);
			}
			$status = 3;
		}
		
		
		session('idcardimg',NULL);
		session('idcardimg2',NULL);
		
		$name = text($_POST['real_name']);
		$idcard = text($_POST['idcard']);
		$time = time();
		
        $model = new Model();
        $model->startTrans();
        
        $data = array(
        		'uid' => $this->uid,
        		'up_time' => $time,
        		'status' => $status,
        		'idcard' => $idcard
        );
		
        if($c['enabled']){
        	$data['deal_info'] = '系统认证';
        }
        
        $applyRes = M('name_apply') -> where("uid = {$this->uid}") -> save($data);
        if(!$applyRes){
	        $applyRes = M('name_apply') -> add($data);
        }
		unset($data['status'],$data['deal_info']);
		$data['real_name'] = $name;
		#判断性别
		$data['sex'] = hidecard($data['idcard'],11);
		$infoRes = M('member_info') -> where("uid = {$this->uid}") -> save($data);
		if(!$infoRes){
			$newid = M('member_info') -> add($data);
		}
		
		$data = array('id_status'=> $status, 'uid'=> $this->uid);
		$statusRes = M('members_status') -> where("uid={$this->uid}") -> save($data);
		if(!$statusRes){
			$statusRes = M('members_status') -> where("uid={$this->uid}") -> add($data);
		}
		//if($applyRes && $newid && $statusRes){
		if($statusRes){
			$model->commit();
			if($c['enabled']){ // 自动验证时更新积分
				setMemberStatus($this->uid, 'id', $status, 2, '实名');
			}
			ajaxmsg();
		}else{
			$model->rollback();
			ajaxmsg("保存失败，请重试", 0);
		}
	}
	//上传实名认证1
	public function uploadImg()
    {
        $uid = $this->uid;

		$this->savePathNew = C( "MEMBER_UPLOAD_DIR" )."Idcard/";            
		$this->saveRule = date( "YmdHis", time() ).rand( 0, 1000 );            
		$info = $this->CUpload(); 

		$img = $info[0]['savepath'].$info[0]['savename'];  
	  
		if($img){
			$c = M('member_info')->where("uid = {$this->uid}")->count('uid');
			if($c==1){
				$newid = M("member_info")->where("uid={$this->uid}")->setField('card_img',$img);
			}else{
				$data['uid'] = $this->uid;
				$data['card_img'] = $img;
				$newid = M('member_info')->add($data);
			}
			session("idcardimg","1");
			$data['file_src']=$img;
			$dd['data']=$data;
			$dd['code']=0;
			echo json_encode($dd);
		}
		else{
			$dd['code']=2;
			echo json_encode($dd);
		}       
       
    }
	//上传实名认证2
	public function uploadImg2()
    {
        $uid = $this->uid;

		$this->savePathNew = C( "MEMBER_UPLOAD_DIR" )."Idcard/";            
		$this->saveRule = date( "YmdHis", time() ).rand( 0, 1000 );            
		$info = $this->CUpload(); 

		$img = $info[0]['savepath'].$info[0]['savename'];  
	  
		if($img){
			$c = M('member_info')->where("uid = {$this->uid}")->count('uid');
			if($c==1){
				$newid = M("member_info")->where("uid={$this->uid}")->setField('card_back_img',$img);
			}else{
				$data['uid'] = $this->uid;
				$data['card_back_img'] = $img;
				$newid = M('member_info')->add($data);
			}
			session("idcardimg2","1");
			$data['file_src']=$img;
			$dd['data']=$data;
			$dd['code']=0;
			echo json_encode($dd);
		}
		else{
			$dd['code']=2;
			echo json_encode($dd);
		}       
       
    }
	//充值提现前手机认证
	public function sendphone2(){
	    $result = GlobalModel::send_msg_limit($this->uid);
	    if ($result==false){
	        ajaxmsg("当前操作繁忙！",3);
	    }
		$smsTxt = FS("Webconfig/smstxt");
		$smsTxt=de_xie($smsTxt);
		$phone = text($_POST['cellphone']);
		$xuid = M('members')->getFieldByUserPhone($phone,'id');
		if($xuid>0 && $xuid<>$this->uid) ajaxmsg("",2);
		
		$code = rand_string($this->uid,6,1,2);
		$datag = get_global_setting();
		$is_manual=$datag['is_manual'];

		$res = sendsms($phone,str_replace(array("#UserName#","#CODE#"),array(session('u_user_name'),$code),$smsTxt['verify_phone']));

		if($res){
			session("temp_phone",$phone);
			ajaxmsg();
		}else{
			ajaxmsg("",0);
		}
    }
	///////////////////////////////
    public function register3($param) {
        $expconf = FS("Webconfig/expconf");
        $type_conf = $expconf[1];
        if($type_conf['num']){
            $this->assign('money',$type_conf['money']);
        }
        $this->display();
    }
    public function register4($param) {
        $expconf = FS("Webconfig/expconf");
        $type_conf = $expconf[1];
        if($type_conf['num']){
            $this->assign('money',$type_conf['money']);
        }
        $this->assign('email',1);
        $this->display();
    }

}

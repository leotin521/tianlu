<?php
// 本类由系统自动生成，仅供测试用途
class BankAction extends MCommonAction {

    public function index(){
        header("Content-type: text/html; charset=utf-8");
        $borrowconfig = FS("Webconfig/borrowconfig");
        $ids = M('members_status')->field('id_status,phone_status')->find($this->uid);
        if ($ids['id_status']!=1){
            echo '<script type="text/javascript">alert("您还未完成身份验证，请先进行实名认证");window.location.href="'.__APP__.'/member/verify/";</script>';
            exit;
        }elseif ($ids['phone_status']!=1){
            echo '<script type="text/javascript">alert("您还未完成身份验证，请先进行手机认证");window.location.href="'.__APP__.'/member/verify/";</script>';
            exit;
        }
        $voinfo = M("member_info")->field('idcard,real_name')->find($this->uid); 
		$vobank = M("member_banks")->field(true)->where("uid = {$this->uid} and bank_num !=''")->order('id desc')->select();
		$this->assign("is_manual",$this->glo['is_manual']);//是否开启手机验证
		$this->assign('edit_bank', $this->glo['edit_bank']);  //修改开关
		$mobile = M('members')->getFieldById($this->uid,'user_phone');  //手机号
		$this->assign("mobile",$mobile);
		$this->assign("voinfo",$voinfo);
		$this->assign("vobank",$vobank);
		$this->assign("bank_name",$borrowconfig['BANK_NAME']);
		$this->assign("province",$this->city(1));
		$this->assign("bank_list",get_bank_type($this->uid));
		$this->display();
    }
    /**
     * 获取地区
     */
    public function city($reid){
        $vobank = M("area")->field(true)->where("reid = ".$reid." and is_open=0")->order('id asc')->select();
        return $vobank;
    }
    /**
     * 地区联动
     */
    public function sele()
    {
        $arr = $this->city(intval($_POST['pid']));
        exit(json_encode($arr));
    }
    /**
     * 获取验证码
     */
    public function getcode()
    {
        $result = GlobalModel::send_msg_limit($this->uid);
        if ($result==false){
            ajaxmsg("", 0);
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
     * 添加银行卡账号
     */
    public function addbank()
    {
        if (empty($_POST['vcode'])){
            $this->doadd($_POST);
        }else{
            if( is_verify($this->uid,text($_POST['vcode']),2,10*60) ){
                $this->doadd($_POST);
            }
            else ajaxmsg("验证码错误，请重新输入~",0);
        }
    }
    /**
     * 
     */
    protected function doadd($data){
        unset($_POST['vcode']);
        $data = textPost($_POST);
        $arr['uid'] = $this->uid;
        $arr['bank_name'] = $data['bank_name'];
        $userCount = M('member_banks')->where($arr)->count("id");
        if ($userCount<>0) ajaxmsg('不能重复添加数据！请刷新后再试~',0);
        $data['uid'] = $this->uid;
        $data['add_ip'] = get_client_ip();
        $data['add_time'] = time();
        $newid = M('member_banks')->add($data);
        if($newid){
            MTip('chk2',$this->uid, '', '', null, 1);
            MTip('chk2',$this->uid, '', '', null, 2);
            MTip('chk2',$this->uid, '', '', null, 3);
            //NoticeSet('chk2',$this->uid);
            ajaxmsg();
        }
        else ajaxmsg('操作失败，请重试~',0);
    }
    /**
     * 删除银行卡账号
     */
    public function bank_del(){
        $map['id'] = intval($_GET['id']);
        $newid = M('member_banks')->where($map)->delete();
        if ($newid){
            ajaxmsg();
        }
        else ajaxmsg("操作失败，请重试~",0);
    } 
    /**
     * 修改
     */
    public function edit(){
        $id = intval($_GET['bank_id']);
        
        $voinfo = M("member_info")->field('idcard,real_name')->find($this->uid);
        $this->assign("voinfo",$voinfo);
        $vobank = M("member_banks")->field(true)->where("uid = {$this->uid} and id=$id and bank_num !=''")->find();
        $this->assign("vobank",$vobank);
        
        //是否开启手机验证
        $datag = get_global_setting();
        $is_manual = $datag['is_manual'];
        $this->assign("is_manual",$is_manual);
        
        //手机号
        $mobile = M('members')->getFieldById($this->uid,'user_phone');  
        $this->assign("mobile",$mobile);
        
        //银行名称
        $bank_list = get_bank_type($this->uid);
        $info = get_bconf_setting();
        $integration = $info['BANK_NAME'];
	    $bank_list[$vobank['bank_name']] = $integration[$vobank['bank_name']];
        $this->assign("bank_list",$bank_list);
        
        $this->assign("province",$this->city(1));   //省级
        $this->assign("city",$this->city($vobank['bank_province']));    //市级
        $this->assign("id",$id);
        $this->assign('edit_bank', $this->glo['edit_bank']);  //修改开关
        $this->display();
    }
    /**
     * 修改
     */
    public function doedit()
    {
        if (empty($_POST['vcode'])){
            $this->doedit_do($_POST);
        }else{
            if( is_verify($this->uid,text($_POST['vcode']),2,10*60) ){
                $this->doedit_do($_POST);
            }
            else ajaxmsg("验证码错误，请重新输入~",0);
        }
    }
    /**
     * 
     */
    protected function doedit_do(){
        unset($_POST['vcode']);
        $data = textPost($_POST);
        $map['id'] = intval($data['bank_id']);
        $map['uid'] = $this->uid;
        unset($data['bank_id']);
        $data['add_ip'] = get_client_ip();
        $data['add_time'] = time();
        $newid = M('member_banks')->where($map)->save($data);
        if($newid){
            MTip('chk2',$this->uid, '', '', null, 1);
            MTip('chk2',$this->uid, '', '', null, 2);
            MTip('chk2',$this->uid, '', '', null, 3);
            //NoticeSet('chk2',$this->uid);
            ajaxmsg();
        }
        else ajaxmsg('操作失败，请重试~',0);
    }
    
}
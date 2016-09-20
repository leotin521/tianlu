<?php
/**
 * 手机版 用户中心
 */
class UserAction extends MobileAction
{

    public function index()     //金额展示
    {
//        session_destroy();
//        exit;
        $pre = C('DB_PREFIX');//表前缀
        $mess = array();
        $mess['uid'] = intval(session("u_id"));
        //累计收益
        $agility_interest = BaoInvestModel::get_sum_interest($this->uid);
        $income = get_personal_benefit($this->uid);
		$money_collect_total = bcadd($income['interest_collection'], $income['capital_collection'], 2);
		$money_collect['money_collect'] = $money_collect_total;

        $minfo['income'] = $income['total'];
        $mess['income'] = $minfo['income'] + $agility_interest;//累计收益
        $this->assign("income",$mess['income']);

        //代收收益
        $field = "sum(interest-interest_fee) as interest";
        $wait = M("investor_detail")->field($field)->where("investor_uid = {$this->uid} AND status in (6,7)")->find();
        //$wait['interest'] = $wait['interest']?0:$wait['interest'];
        //dump($wait['interest']);
        if($wait['interest']){
            $wait['interest'] = $wait['interest'];
        }else{
            $wait['interest'] = 0;
        }
        $this->assign("interest",$wait['interest']);
        //dump($wait['interest']);
        //用户名
        $userinfo = M('members')->field("user_leve,user_name")->where("id = {$this->uid}")->find();
        $this->assign("useri",$userinfo);

        //灵活宝资金详情
		$agility_money = BaoInvestModel::get_sum_money($this->uid);
	
        //总额
        $minfo = getMinfo($mess['uid'],true);
       // $mess['total'] = $minfo['account_money'] + $minfo['back_money'] + $minfo['money_freeze'] + $minfo['money_collect'] + $agility_money;
		 $mess['total'] = $minfo['account_money'] + $minfo['back_money'] + $minfo['money_freeze']+$money_collect['money_collect']+$agility_money;
		//dump( $mess['total']);
        $this->assign('total',$mess['total']);
		/*用户验证*/
            $meber = M('members_status')->field("phone_status,id_status")->where("uid = {$this->uid}")->find();
        if($meber['phone_status'] != 1){
            $this->assign("phone_status",0);
        }else{
            $this->assign("phone_status",1);
        }
        if($meber['id_status'] != 1){
            $this->assign("id_status",0);
        }else{
            $this->assign("id_status",1);
        }
        /*用户验证*/
        $this->display();
    }

    /**
     * 个人资料
     */
    public function userinfo()
    {
        $pre = C('DB_PREFIX');//表前缀
        $field = "ms.phone_status,ms.id_status,ms.email_status,mi.real_name,mi.head_img,mb.uid as bank_id,mb.bank_num,m.user_phone,m.user_email,m.pin_pass";
        $mstatus = M('members_status ms')
            ->field($field)
            ->join("{$pre}member_info mi ON mi.uid = {$this->uid}")
            ->join("{$pre}member_banks mb ON mb.uid = {$this->uid}")
            ->join("{$pre}members m ON m.id = {$this->uid}")
            ->where("ms.uid = {$this->uid}")
            ->order("bank_id desc")
            ->find();
		//dump($mstatus);exit;
        //用户名
        $userinfo = M('members')->field("user_leve,user_name")->where("id = {$this->uid}")->find();
        $this->assign("useri",$userinfo);

        $this->assign('idc',hidecard($mstatus['real_name'],5));//加密姓名
        $this->assign('banks',hidecard($mstatus['bank_num'],4));//加密银行卡
        $this->assign('mi',$mstatus);

        $member = BorrowModel::borrow_validate($this->uid);
        if($member['is_transfer'] == MembersModel::MEMBERS_IS_TRANSFER_NORMAL){//判断当前用户是否为个人借款者或者是企业借款者
            $this->assign("qiyeandgeren",1);
        }else{
            $apply = M('borrow_apply')->field('user_type')->where("uid = {$this->uid}")->find();
            $this->assign("user_type",$apply['user_type']);
        }
        //exit;
        $code = text($_GET['vcode']);
        $uk = is_verify(0,$code,1,60*1000);
        
        $this->assign('vcode',$code);
        $this->assign('uk',$uk);
        
        $this->display();
    }

    /**
     * 身份证信息
     * */
    public function idcard(){
        $pre = C('DB_PREFIX');//表前缀
        $field = "ms.id_status,mi.real_name,mi.idcard";
        $mstatus = M('member_info mi')
            ->field($field)
            ->join("{$pre}members_status ms ON ms.uid = {$this->uid}")
            ->where("mi.uid = {$this->uid}")
            ->find();
        //echo M()->getLastSql();
        //dump($mstatus);
        $this->assign("info",$mstatus);
        $this->assign('idn',hidecard($mstatus['real_name'],5));//加密姓名
        $this->assign('idc',hidecard($mstatus['idcard'],4));//加密身份证号
        $this->display();
    }
    /**
     * 身份证提交审核展示
     * */
    public function idcard_add(){

        $this->display();
    }

    public function up_idcard(){
        //接收传值
        $data['real_name'] = text($_POST['realname']);

        $data['idcard'] = text($_POST['card_id']);
        $data['up_time'] = time();
        #判断性别
        $data['sex'] = hidecard($data['idcard'],11);
        #---------------------------------------------------------------
        $data1['idcard'] = text($_POST['idcard']);
        $data1['up_time'] = time();
        //$data1['card_type'] = intval($_POST['card_type']); //证件类型
        $data1['uid'] = $this->uid;
        $data1['status'] = 3;
        //验证不空
        if(empty($data['real_name'])||empty($data['idcard']))  ajaxmsg("请输入真实姓名和身份证号码~",0);
        //验证唯一
        $xuid = M('member_info')->getFieldByIdcard($data['idcard'],'uid');
        file_put_contents("1111.txt",M()->getLastSql());
        if($xuid) {
            ajaxmsg("此身份证号码已被人使用~",0);
        }
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



    public function uphone(){
		$datag = get_global_setting();
        $is_manual=$datag['is_manual'];
		$this->assign('is_manual',$is_manual);//加密身份证号
        $this->display();
    }

    public function sendphone(){
        $result = GlobalModel::send_msg_limit($this->uid);
        if ($result==false){
            ajaxmsg("操作繁忙，请稍后再试！",3);
        }
        $smsTxt = FS("Webconfig/smstxt");
        $smsTxt=de_xie($smsTxt);
        $phone = text($_POST['cellphone']);
        //file_put_contents("1111.txt",$phone);
        $xuid = M('members')->getFieldByUserPhone($phone,'id');
        //file_put_contents("5555.txt",M()->getLastSql());
        //file_put_contents("2222.txt",$xuid);
        //file_put_contents("3333.txt",$this->uid);
        if($xuid){
            ajaxmsg("",2);
        }
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


    public function validatephone(){

        $phone = text($_POST['cellphone']);
        $xuid = M('members')->getFieldByUserPhone($phone,'id');
        if($xuid){
            ajaxmsg("该手机号已被注册~".$_POST['cellphone'],2);
        }

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
            ajaxmsg("验证校验码不对，请重新输入！",2);
        }
    }

    public function email(){
        $this->display();
    }

	public function ckemail(){
        $map['user_email'] = text($_POST['Email']);
        $map['id']  = array('eq',$this->uid);
        $count = M('members')->where($map)->count('id');
            //file_put_contents("2222222222.txt",M()->getLastSql());
       if ($count>0) {
          ajaxmsg('邮件已经存在~',0);
       } else {
           ajaxmsg();
       }
    }




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
        
//    public function emailverify(){
//      $uk = text($_POST['uk']);
//      //file_put_contents('1.txt', $uk);
//      $emailsend = setMemberStatus($uk, 'email', 1, 9, '邮箱');
//      //file_put_contents('1.txt', $emailsend);
//      if($emailsend != 0){
//        if($emailsend){
//        	ajaxmsg('验证成功',1);
//        }else{
//        	ajaxmsg('验证失败',0);
//        }
//      }
//    }


    public function edit_bank(){  //修改银行卡详情展示页

        $borrowconfig = FS("Webconfig/borrowconfig");
        $id = intval($_GET['id']);
        $vobank = M("member_banks")->field(true)->where("uid = {$this->uid} and id=$id and bank_num !=''")->find();
        //dump($vobank);

        $this->assign("vobank",$vobank);//银行信息
        $data['bank_name'] = $borrowconfig['BANK_NAME'];//银行列表
        $banks['bank_name'] = $data['bank_name'][$vobank['bank_name']]; //银行名称
        $this->assign("bankname", $banks['bank_name']);//银行名称
        //银行名称
        $bank_list = get_bank_type($this->uid);
        $info = get_bconf_setting();
        $integration = $info['BANK_NAME'];
        $bank_list[$vobank['bank_name']] = $integration[$vobank['bank_name']];
        $this->assign("bank_list",$bank_list);
        $voinfo = M("member_info")->field('idcard,real_name')->find($this->uid);
        $this->assign("voinfo",$voinfo);
        $bankinfo = array();
        $this->assign("id",$id);//银行信息id
        //dump($id);
        //exit;
        $bankinfo['bank_num'] = $vobank['bank_num']; //银行卡号
        $bankinfo['bank_address'] = $vobank['bank_address']; //开户行


        $this->assign("province",$this->city(1));   //省级
        $this->assign("city",$this->city($vobank['bank_province']));    //市级
        $this->assign("id",$id);
        $this->assign("bankinfo",$bankinfo);
        $this->display();
    }

    public function add_banks(){
        header("Content-type: text/html; charset=utf-8");
        $borrowconfig = FS("Webconfig/borrowconfig");
        $ids = M('members_status')->field('id_status,phone_status')->find($this->uid);
        if ($ids['id_status']!=1){
            echo '<script type="text/javascript">alert("您还未完成身份验证，请先进行实名认证");window.location.href="'.__APP__.'/m/user/idcard";</script>';
            exit;
        }elseif ($ids['phone_status']!=1){
            echo '<script type="text/javascript">alert("您还未完成身份验证，请先进行手机认证");window.location.href="'.__APP__.'/m/user/uphone";</script>';
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

    public function bank_index(){
        $borrowconfig = FS("Webconfig/borrowconfig");
        //dump($borrowconfig['BANK_NAME']);
        session('baofoo',$_GET['bank_type']);
 
        $voinfo = M("member_info")->field('idcard,real_name')->find($this->uid);
        $vobank = M("member_banks")->field(true)->where("uid = {$this->uid} and bank_num !=''")->order('id desc')->select();
        $data['is_manual'] = $this->glo['is_manual'];//是否开启手机验证
        $data['edit_bank'] = $this->glo['edit_bank'];  //修改开关
        $mobile = M('members')->getFieldById($this->uid,'user_phone');
        $data['mobile'] = $mobile;//手机号
        $data['real_name'] = $voinfo['real_name'];//用户银行卡开户名
        $data['bank_name'] = $borrowconfig['BANK_NAME'];//银行列表

        $bank_list = get_bank_type($this->uid);
        $_list = array();
        //$datas = array();
        foreach($vobank as $k=>$value){
            $_list[$k]['id'] = $value['id']; //银行卡id
            $_list[$k]['bank_id'] = $value['bank_name'];//银行id
            $_list[$k]['bank_name'] = $data['bank_name'][$value['bank_name']]; //银行名称
            $_list[$k]['bank_num'] = hidecard($value['bank_num'],12); //银行卡号
        }

        if(is_array($_list)){
            $data['list'] = $_list;
            $this->assign("banks",$data['list']);
        }

        $this->display();
    }

    public function edit()
    {
        //file_put_contents("444.txt",$_POST['bank_num']);
        $id = intval($_POST['id']);
        $bankinfo = M('member_banks')->field(true)->where("id = {$id}")->find();
        if($bankinfo){
            $data['bank_address'] = text($_POST['bank_address']);//开户行
            $data['bank_city'] = intval($_POST['bank_city']);//开户城市
            $data['bank_name'] = text($_POST['bank_name']);//银行名
            $data['bank_num'] = text($_POST['bank_num']);//卡号
            $data['bank_province'] = intval($_POST['bank_province']);//开户省份
            $savebankinfo = M('member_banks')->where("id = {$id}")->save($data);
            if($savebankinfo){
                ajaxmsg("修改成功",1);
            }else{
                ajaxmsg("修改失败",0);
            }
        }
    }


    public function addbank()
    {
        $m_status = M('members_status')->field("id_status")->where("uid = {$this->uid}")->find();
        session('baofoo') != "" ? $mobile['mobile'] = "mobile" : $mobile['mobile'] = "";
        if($m_status){
            $data['bank_num'] = text($_POST['bank_num']);
           	$data['mobile'] = $mobile['mobile'];
            $data['bank_province'] = text($_POST['bank_province']);
            $data['bank_city'] = text($_POST['bank_city']);
            $data['bank_address'] = text($_POST['bank_address']);
            $data['bank_name'] = text($_POST['bank_name']);
            $data['add_time'] = time();
            $data['add_ip'] = get_client_ip();
            $data['uid'] = $this->uid;
            $m_bank = M('member_banks')->add($data);

            ajaxmsg("恭喜您，添加成功",1);
        }else{
            ajaxmsg("还未实名认证",0);
        }
    }

    function bankinfo(){
        $pre = C('DB_PREFIX');//表前缀
        $field = 'mb.bank_num,mb.bank_province,mb.bank_city,mb.bank_address,mb.bank_name,a.name';
        $m_status = M('member_banks mb')
            ->field($field)
            ->join("{$pre}area a ON a.reid = mb.bank_province and a.id = mb.bank_city")
            ->where("uid = {$this->uid}")
            ->select();

        // echo M()->getLastSql();exit;
        $this->display();
    }



    public function newpass(){
        $this->display();
    }

    public function newpass_save(){   //修改密码
        $arr['now_password'] = text($_POST['now_password']);
        $arr['new_password'] = text($_POST['new_password']);
        $arr['news_password'] = text($_POST['news_password']);
        $mer = M('members')->field("user_pass")->where("id = {$this->uid}")->find();
        $yupsaa = md5($arr['now_password']);
        
        if($yupsaa != $mer['user_pass']) {
            ajaxmsg("原始密码错误",0);
        }elseif($yupsaa == $mer['user_pass']){
            $data['user_pass'] = md5($arr['new_password']);
            $newpass = M('members')->where("id = {$this->uid}")->save($data);
            $this->_memberloginout();//清除session的同時，並且跳轉到登陸頁。
            ajaxmsg("修改成功",1);
        }

    }

    public function pin_pass(){
        $this->display();
    }

    public function pin_pass_save(){   //修改支付密码
        $arr['now_password'] = text($_POST['now_password']);
        $arr['new_password'] = text($_POST['new_password']);
        $arr['news_password'] = text($_POST['news_password']);
        $mpin = M('members')->field('user_pass,pin_pass')->where("id = {$this->uid}")->find();
        $yupsaa = md5($arr['now_password']);
        if($mpin['pin_pass']){ //設置過
            if($yupsaa != $mpin['pin_pass']){
                ajaxmsg("原始密码错误",0);
            }else{
                $data['pin_pass'] = md5($arr['new_password']);
                $newpass = M('members')->where("id = {$this->uid}")->save($data);
                ajaxmsg("修改成功",1);
            }
        }else{ //未設置過
            if($yupsaa != $mpin['user_pass']){
                ajaxmsg("原始密码错误",0);
            }else{
                $data['pin_pass'] = md5($arr['new_password']);
                $newpass = M('members')->where("id = {$this->uid}")->save($data);
                ajaxmsg("修改成功",1);
            }
        }

    }

    public function jiaoyijilv(){
        $search['uid'] = $this->uid;

        foreach($urlArr as $v){
            if($_GET[$v] && $_GET[$v]<>'null'){
                switch($v){
                    case 'time':
                        $time = text($_GET[$v]);
                        if ($time == '2') {
                            $time = time()-604800;
                        }elseif ($time == '3'){
                            $time = time()-2592000;
                        }elseif ($time == '4'){
                            $time = time()-7776000;
                        }
                        $search["add_time"] = array("gt",$time);
                        break;
                    case 'type':
                        $type = text($_GET[$v]);
                        $types = array("2" => "3,27", "3" => "12,29", "4" => "6,37", "5" => "13,20,32,34,40,41,43,45,51,52", "6" => "9,10", "7"=>"2,4,5,7,8,11,14,15,16,17,18,19,21,22,23,24,25,26,28,30,31,33,35,36,38,39,42,44,46,47,48,49,50,");
                        if(array_key_exists($type, $types)){
                            $search['type']  = array('in',$types[$type]);
                        }
                        break;
                    case 'from_time':
                        $from_time = strtotime($_GET[$v]);
                    case 'to_time':
                        $to_time = strtotime($_GET[$v]);
                        $search["add_time"] = array('between',array($from_time,$to_time));
                        break;
                    default:
                        break;
                }
            }
        }

        $list = getMoneyLog($search,5);
        $this->assign("logs",$list['list']);
		 $this->assign("page",$list['page']);
        $this->display();
    }


	public function jiaoyijilv_page(){
        if (!$this->uid){
           $this->error("用户错误！",0);
        }

        $p=intval($_POST['k'])?intval(trim($_POST['k'])):0;//当前页数

    /*    $count = M('member_moneylog')->where($map)->count('id');
        $num = 2;//每页记录
        $totalPage = ceil($count/$num);//计算总页数
        $limitpage=($p)*$num;//每次查询取记录

        if($p>$totalPage){
            exit;
        }*/
        $resut = M('member_moneylog')->where("uid = {$this->uid} and id <{$p}")->order('id DESC')->limit(5)->select();
        $type_arr = C("MONEY_LOG");
        foreach($resut as $key=>$v){
            $resut[$key]['type'] = $type_arr[$v['type']];
        }
        $list=array();
        $list['list'] = $resut;

        $loglist = $list['list'];
        $string = '';
        foreach($loglist as $key=>$v) {
            $string .= "<a rel='{$v['id']}' href='__URL__/jiaoyijilv_page?id={$v['id']}'>";
                $string .= "<div class='mod-transaction mod-transaction-one' id='test'>";
                    $string .= "<div class='mod-transaction-left'>";
                        $string .= "<div class='mod-transaction-lefticon'>";
                            if($v['affect_money'] < 0){
                                $string .= "<img src='/Style/NewWeChat/images/member/icon-transaction.png' alt='' border='0' />";
                            }else{
                                $string .= "<img src='/Style/NewWeChat/images/member/icon-transaction2.png' alt='' border='0' />";
                            }

                            $string .= " <div class='mod-transaction-link' style='float:right;'>".$v['type']. "<br/><span style='float:right;'>".date('Y-m-d H:i:s',$v['add_time']);

                            $string .= "</span>";
                            $string .= "</div>";
                         $string .= "</div>";
                       $string .= "</div>";

                    if($v['affect_money'] < 0){
                        $string .= "<div class='mod-transaction-right' style='color:#02E738;'>".$v['affect_money']."元";
                        $string .= "</div>";
                    }else{
                        $string .= "<div class='mod-transaction-right' style='color:red;'>+".$v['affect_money']."元";
                        $string .= "</div>";
                    }

                $string .= "</div>";
            $string .= "</a>";
        }
        ajaxmsg($string,1);
        //ajaxmsg($string,1);
    }

    function jiaoyijilvinfo(){
        $id = intval($_GET['id']);
        if(!$id)$this->error("非法参数");
        if(!$this->uid)$this->error("请先登陆!");
        $search['uid'] = array("eq",$this->uid);
        $search['id'] = array("eq",$id);
        $list = getMoneyLog($search,10);
        $this->assign("list",$list['list']);
        $this->display();
    }


    /**
     * 优惠券查询
     */
    public function youhuiquan() {
        $times = time();
        $field = "money,expired_time,money,type,remark";
        $exp = M('expand_money')->field($field)->where("uid = {$this->uid} and status = 1 and expired_time > {$times}")->select();
        //echo M()->getLastSql();
        //exit;
        $this->assign("exp",$exp);
        $this->display();
    }


    public function borrow_list(){
        $field = "borrow_name,borrow_money,has_borrow,borrow_interest_rate,repayment_type,borrow_duration,borrow_status";
        $borrow_info = M('borrow_info')->field($field)->where("borrow_uid = {$this->uid}")->limit('0,10')->order('id desc')->select();
        //echo M()->getLastSql();
        //echo M()->getlastSql();
        //sdump($borrow_info);
        // exit;
        $this->assign("list",$borrow_info);
        //$this->assign("lstatus",C('BORROW_STATUS',$borrow_info['borrow_status']));
        $this->display();
    }

    public function apply(){
        $this->display();
    }

    public function apply_add(){
        $xtime = strtotime("-1 month");
        $vo = M('member_apply')->field('apply_status')->where("uid={$this->uid}")->order("id DESC")->find();
        $xcount = M('member_apply')->field('add_time')->where("uid={$this->uid} AND add_time>{$xtime}")->order("id DESC")->find();
        //if(is_array($vo) && $vo['apply_status']==0){
        if(1 == 2){
            $xs = "是您的申请正在审核，请等待此次审核结束再提交新的申请";
            ajaxmsg($xs,0);
            //}elseif(is_array($xcount)){
        }elseif(1 == 2){
            $timex = date("Y-m-d",$xcount['add_time']);
            $xs = "一个月内只能进行一次额度申请，您已在{$timex}申请过了，如急需额度，请直接联系客服";
            ajaxmsg($xs,0);
        }else{
            $apply['uid'] = $this->uid;
            $apply['apply_type'] = 1;
            $apply['apply_money'] = floatval($_POST['apply_money']);
            $apply['apply_info'] = text($_POST['apply_info']);
            $apply['add_time'] = time();
            $apply['apply_status'] = 0;
            $apply['add_ip'] = get_client_ip();
            $nid = M('member_apply')->add($apply);
            file_put_contents("111.txt",M()->getLastSql());

        }
        if($nid) ajaxmsg('申请已提交，请等待审核',1);
        else ajaxmsg('申请提交失败，请重试',0);
    }
































    /**
     * 资金信息
     */
    public function fund()
    {
        $this->assign('pcount', get_personal_count($this->uid));
        $this->assign('benefit', get_personal_benefit($this->uid));   //收入
        $minfo =getMinfo($this->uid,true);
        $this->assign("minfo",$minfo);
        $this->display();
    }

    /**
     * 我要提现
     */
    public function cash()
    {
        if($this->isAjax()){
            $money = $this->_post('money');
            $paypass = $this->_post('paypass');
            $status = checkCash($this->uid, $money, $paypass);
            if($status == 'TRUE'){
                die('TRUE');
            }else{
                die('<font color=red>'.$status.'</font>');
            }
        }else{
            $pre = C('DB_PREFIX');
            $field = "m.user_name,m.user_phone,(mm.account_money+mm.back_money) all_money,mm.account_money,mm.back_money,i.real_name,b.bank_num,b.bank_name,b.bank_address";
            $vo = M('members m')->field($field)->join("{$pre}member_info i on i.uid = m.id")->join("{$pre}member_money mm on mm.uid = m.id")->join("{$pre}member_banks b on b.uid = m.id")->where("m.id={$this->uid}")->find();
            //print_r($vo);exit;
            if(empty($vo['bank_num']))
                $this->error("请用电脑登录先绑定银行卡后申请提现！");


            $tqfee = explode( "|", $this->glo['fee_tqtx']);
            $fee[0] = explode( "-", $tqfee[0]);
            $fee[1] = explode( "-", $tqfee[1]);
            $fee[2] = explode( "-", $tqfee[2]);
            $this->assign( "fee",$fee);
            $borrow_info = M("borrow_info")
                ->field("sum(borrow_money+borrow_interest+borrow_fee) as borrow, sum(repayment_money+repayment_interest) as also")
                ->where("borrow_uid = {$this->uid} and borrow_type=4 and borrow_status in (0,2,4,6,8,9,10)")
                ->find();
            $vo['all_money'] -= $borrow_info['borrow'] + $borrow_info['also'];
            $this->assign("borrow_info", $borrow_info);
            $this->assign( "vo",$vo);
            $this->assign("memberinfo", M('members')->find($this->uid));
            $this->display();
        }
    }
    /**
     * 投资总表
     */
    public function invest()
    {
        $uid = $this->uid;
        $pre = C('DB_PREFIX');

        $this->assign("dc",M('investor_detail')->where("investor_uid = {$this->uid}")->sum('substitute_money'));
        $this->assign("mx",getMemberBorrowScan($this->uid));
        $this->display();
    }
    public function loan()
    {
        $this->assign("mx",getMemberBorrowScan($this->uid));
        $this->display();
    }
    /**
     * 安全中心
     */
    public function safe()
    {
        $this->assign("memberinfo", M('members')->find($this->uid));
        $this->assign("mstatus", M('members_status')->field(true)->find($this->uid));
        $this->assign("memberdetail", M('member_info')->find($this->uid));
        $paypass = M("members")->field('pin_pass')->where('id='.$this->uid)->find();
        $this->assign('paypass', $paypass['pin_pass']);
        $this->display();
    }
    /**
     * 设置支付密码
     */
    public function setPayPass()
    {
        if($this->isAjax()){
            $password = $this->_post('password');
            $paypass = $this->_post('paypass');
            $paypass2 = $this->_post('paypass2');
            if(!$password || !$paypass || !$paypass2){
                die('数据不完整，请检查后再试');
            }
            $paypass == $password && die('不能和登陆密码相同，请重新输入');
            $paypass != $paypass2 && die('两次支付密码不一致，请重新输入');
            $user = M('members')->field('user_pass, pin_pass')->where('id='.$this->uid)->find();
            !$user  && die('数据有误');
            if($user['user_pass']!=md5($password)){
                die('登陆密码不正确');
            }
            if(M("members")->where('id='.$this->uid)->save(array('pin_pass'=>md5($paypass)))){
                die('TRUE');
            }else{
                echo '设置出错，刷新页面重试';
            }

        }else{
            $this->display();
        }
    }
    /**
     * 修改支付密码
     *
     */
    public function editpaypass()
    {
        if($this->isAjax()){
            $oldpass = $this->_post('oldpass');
            $paypass = $this->_post('paypass');
            $paypass2 = $this->_post('paypass2');
            if(!$oldpass || !$paypass || !$paypass2){
                die('数据不完整，请检查后再试');
            }
            $paypass == $oldpass && die('新密码不能和旧密码相同，请重新输入');
            $paypass != $paypass2 && die('两次支付密码不一致，请重新输入');
            $user = M('members')->field('pin_pass')->where('id='.$this->uid)->find();
            !$user  && die('数据有误');
            if($user['pin_pass']!=md5($oldpass)){
                die('支付密码不正确');
            }
            if(M("members")->where('id='.$this->uid)->save(array('pin_pass'=>md5($paypass)))){
                die('TRUE');
            }else{
                echo '设置出错，刷新页面重试';
            }

        }else{
            $this->display();
        }
    }

    /**
     * 修改登录密码
     *
     */
    public function editpass()
    {
        if($this->isAjax()){
            $oldpass = $this->_post('oldpass');
            $password = $this->_post('password');
            $password2 = $this->_post('password2');
            if(!$oldpass || !$password || !$password2){
                die('数据不完整，请检查后再试');
            }
            $password == $oldpass && die('新密码不能和旧密码相同，请重新输入');
            $password != $password2 && die('两次密码不一致，请重新输入');
            $user = M('members')->field('user_pass')->where('id='.$this->uid)->find();
            !$user  && die('数据有误');
            if($user['user_pass']!=md5($oldpass)){
                die('旧密码不正确');
            }
            if(M("members")->where('id='.$this->uid)->save(array('user_pass'=>md5($password)))){
                die('TRUE');
            }else{
                echo '设置出错，刷新页面重试';
            }

        }else{
            $this->display();
        }
    }

    /**
     * 资金记录
     */
    public function  records()
    {
        $logtype = C('MONEY_LOG');
        $this->assign('log_type',$logtype);

        $map['uid'] = $this->uid;
        $list = getMoneyLog($map,15);
        $this->assign("list",$list['list']);
        $this->assign("pagebar",$list['page']);
        $this->assign("query", http_build_query($search));
        $this->display();
    }

    public function msg()
    {
        if($this->isAjax()){
            $id = $this->_get('id');
            $msg = M('inner_msg')->field('msg')->where('id='.$id.' and uid='.$this->uid)->find();
            if(count($msg)){
                M('inner_msg')->where('id='.$id)->save(array('status'=>1));
                echo $msg['msg'];
            }else{
                echo '<font color=\'red\'>读取错误</font>';
            }

        }else{
            $map['uid'] = $this->uid;
            //分页处理
            import("ORG.Util.Page");
            $count = M('inner_msg')->where($map)->count('id');
            $p = new Page($count, 15);
            $page = $p->show();
            $Lsql = "{$p->firstRow},{$p->listRows}";
            //分页处理
            $list = M('inner_msg')->where($map)->order('status asc,id DESC')->limit($Lsql)->select();

            $this->assign("list",$list);
            $this->assign("pagebar",$page);
            $this->assign("count",$count);
            $this->display();
        }

    }

}
?>

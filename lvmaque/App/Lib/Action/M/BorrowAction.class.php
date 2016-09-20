<?php
//解决火狐swfupload的session bug
if (isset($_POST[session_name()]) && empty($_SESSION)) {
    session_destroy();
    session_id($_POST[session_name()]);
    session_start();
}
// 本类由系统自动生成，仅供测试用途
class BorrowAction extends MobileAction {
    public function index(){
        //借款页
//            $jsoncode = file_get_contents("php://input");
//            $arr = array();
//            $arr = json_decode($jsoncode, true);
//            $arr = AppCommonAction::get_decrypt_json($arr);
            //print_r(MembersModel::MEMBERS_IS_TRANSFER_BUSINESS);exit;
            $version = FS('Webconfig/version');
            $per = C('DB_PREFIX');
            if($this->uid){
                $minfo = getMinfo($this->uid,true);
                $data['credit_limit'] = $minfo['credit_limit'];//可使用额度
                $data['credit_cuse'] = $minfo['credit_cuse'];//总额度
                $data['credit_use'] = $minfo['credit_cuse']-$minfo['credit_limit'];//已使用额度
                $data['credit_stuats'] = ($minfo['credit_cuse']>0)? 1:0;//额度状态


                $data['netmoney'] = getNet($this->uid);  //可用净值额度
                $_allnetMoney = getFloatValue(0.9*$minfo['money_collect'],2);
                $data['allnetMoney'] = $_allnetMoney;//总净值额度
                $data['allnetMoney_use'] = $_allnetMoney-$data['netmoney'];  //已使用净值额度
                $data['netMoney_stuats'] = ($data['netmoney']>0)? 1:0;  //净值额度状态

                //$this->assign("capitalinfo", getMemberBorrowScan($this->uid));
                $member = BorrowModel::borrow_validate($this->uid);
                $info = M('borrow_apply')->field('user_type')->where(array('uid'=>$this->uid))->find();
                if (is_array($info)){
                    $member['apply_type'] = $info['user_type'];
                }
                else $member['apply_type'] = 0;

                $members_status = M('members_status')->field('phone_status,id_status')->where(array('uid'=>$this->uid))->find();
                $data['apply_type'] = $member['apply_type'];  //企业状态
                $data['is_transfer'] = $member['is_transfer'];  //会员借款状态
                $data['phone_status'] = $members_status['phone_status'];//手机验证状态
                $data['id_status'] = $members_status['id_status']; //身份认证状态
                $data['validate_user_type'] = $member['validate_user_type'];   //会员借款类型申请状态
                $this->assign("data",$data);
            }else{
                $this->error('请先登录！','__APP__/m/common/logins');
            }

            $member = BorrowModel::borrow_validate($this->uid);
            if($member['is_transfer'] == MembersModel::MEMBERS_IS_TRANSFER_NORMAL){//判断当前用户是否为个人借款者或者是企业借款者
                $this->assign("qiyeandgeren",1);
            }else{
                $apply = M('borrow_apply')->field('user_type')->where("uid = {$this->uid}")->find();
                $this->assign("user_type",$apply['user_type']);
            }
            $this->display();
        }

        public function yanzhen(){
            $uid = intval($_POST['uid']);
            $m_status = M('members_status')->field('id_status,phone_status')->where("uid = {$uid}")->find();
            if($m_status['id_status'] != 1){
                $msg['message'] = '借款必须得先实名验证~';
                ajaxmsg($msg,0);
            }elseif($m_status['phone_status'] != 1){
                $msg['message'] = '借款必须得先手机验证~';
                ajaxmsg($msg,0);
            }
            ajaxmsg();

        }


	public function postt(){
//        $gtype = $_GET['type'];
//        dump($gtype);
//        exit;
        if(!$this->uid) $this->error("请先登录",__APP__."/m/common/logins");
        $sess_uid = $this->uid;
        $vo = M('members')->field('id,user_name,user_type,validate_user_type,user_email,user_pass,is_ban')->where($sess_uid)->find();
        if($vo['is_ban']==1||$vo['is_ban']==2) ajaxmsg("您的帐户已被冻结，请联系客服处理！",0);
        $vminfo = M('members')->field("is_transfer,user_leve,time_limit,is_borrow")->find($this->uid);
		
		

        if($vminfo['is_transfer'] != MembersModel::MEMBERS_IS_TRANSFER_PERSONAL)
            $this->error("请先申请为个人借款者",__APP__."/m/borrow/index");

        if($vminfo['is_borrow']==0){
            $this->error("您目前不允许发布借款，如需帮助，请与客服人员联系！");
            $this->assign("waitSecond",3);
        }

        $gtype = text($_GET['type']);
        $vkey = md5(time().$gtype);
        switch($gtype){
            case "normal"://普通标
                $borrow_type=1;
                break;
            case "vouch"://新担保标
                $borrow_type=2;
                break;
            case "second"://秒还标
                $this->assign("miao",'yes');
                $borrow_type=3;
                break;
            case "net"://净值标
                $borrow_type=4;
                //如果有债权转让，且为转让中状态，不让其发布净值标
                $debt_where = array(
                    'sell_uid' => $this->uid,
                    'status' => 2
                );
                $debt_ret = M('debt')->field('id')->where($debt_where)->find();
                if( !empty($debt_ret) ) {
                    $this->error('您有转让中的债权，不能发布净值标。');
                }
                break;
            case "mortgage"://抵押标
                $borrow_type=5;
                break;
        }

        $this->assign("borrow_type",$borrow_type);

        $borrow_duration_day = explode("|",$this->glo['borrow_duration_day']);
        $day = range($borrow_duration_day[0],$borrow_duration_day[1]);
        $day_time=array();
        foreach($day as $v){
            $day_time[$v] = $v."天";
        }

        $borrow_duration = explode("|",$this->glo['borrow_duration']);
        $month = range($borrow_duration[0],$borrow_duration[1]);
        $month_time=array();
        foreach($month as $v){
            $month_time[$v] = $v."个月";
        }
        $rate_lixt = explode("|",$this->glo['rate_lixi']);
        $this->assign("borrow_use",$this->gloconf['BORROW_USE']);
        $this->assign("borrow_min",$this->gloconf['BORROW_MIN']);
        $this->assign("borrow_max",$this->gloconf['BORROW_MAX']);
        $this->assign("borrow_time",$this->gloconf['BORROW_TIME']);
        $this->assign("BORROW_TYPE",BorrowModel::get_borrow_type());
        $this->assign("borrow_type",$borrow_type);
        $this->assign("borrow_day_time",$day_time);
        $this->assign("borrow_month_time",$month_time);
        $repayment_items = BorrowModel::get_repay_type();
        $this->assign("repayment_type", $repayment_items);
        $this->assign("vkey",$vkey);
        $this->assign("rate_lixt",$rate_lixt);

        $binfo = get_bconf_setting();
        $this->assign("borrow_use",$binfo['BORROW_USE']);
        $this->assign("borrow_min",$binfo['BORROW_MIN']);
        $this->assign("borrow_max",$binfo['BORROW_MAX']);
        $this->assign("borrow_time",$binfo['BORROW_TIME']);


        $borrow_duration_day = explode("|",$this->glo['borrow_duration_day']);
        $day = range($borrow_duration_day[0],$borrow_duration_day[1]);
        $day_time=array();
        foreach($day as $v){
            $day_time[$v] = $v."天";
        }

        $this->assign("borrow_day_time",$day_time);

        $borrow_duration = explode("|",$this->glo['borrow_duration']);
        $month = range($borrow_duration[0],$borrow_duration[1]);
        $month_time=array();
        foreach($month as $v){
            $month_time[$v] = $v."个月";
        }
        $this->assign("borrow_month_time",$month_time);


        $this->display();
	}

    /**
     *
     * @throws Exception
     */
    public function save(){
         //file_put_contents("bbbb.txt",serialize($_POST));
        if(!$this->uid) ajaxmsg("请先登陆",0);
        $pre = C('DB_PREFIX');
        //相关的判断参数
        $rate_lixt = explode("|",$this->glo['rate_lixi']);
        //相关的判断参数
        if (!M('borrow_info')->autoCheckToken($_POST)){
            ajaxmsg('非法请求',0);
        }
        $borrow['borrow_type'] = intval($_POST['borrow_type']);
        if(floatval($_POST['borrow_interest_rate'])>$rate_lixt[1] || floatval($_POST['borrow_interest_rate'])<$rate_lixt[0]) ajaxmsg("提交的借款利率超出允许范围，请重试",0);
        $borrow['borrow_money'] = intval($_POST['borrow_money']);
        //if(strtolower($_POST['is_day'])=='yes') $_POST['repayment_type'] = 1; // repayment_type,在某种条件下点击按天按钮时获取不到值
        if(intval($_POST['repayment_type']) == 1){
            $_POST['repayment_type'] = 1;
        }
        $_minfo = getMinfo($this->uid,"m.pin_pass,mm.account_money,mm.back_money,mm.credit_cuse,mm.money_collect");
        ///////////////////////////////////////////////////////
        $borrowNum=M('borrow_info')->field("borrow_type,count(id) as num,sum(borrow_money) as money,sum(repayment_money) as repayment_money")->where("borrow_uid = {$this->uid} AND borrow_status=6 ")->group("borrow_type")->select();
        $borrowDe = array();
        foreach ($borrowNum as $k => $v) {
            $borrowDe[$v['borrow_type']] = $v['money'] - $v['repayment_money'];
        }
        ///////////////////////////////////////////////////
        switch($borrow['borrow_type']){
            case 1://普通标
                if($_minfo['credit_cuse']<$borrow['borrow_money']) {
                    //$this->error("您的可用信用额度为".intval($_minfo['credit_cuse'])."元，小于您准备借款的金额，不能发标", __APP__.'/member/borrows/credit');
                    ajaxmsg("您的可用信用额度为".intval($_minfo['credit_cuse'])."元，小于您准备借款的金额，不能发标",0);
                }
                break;
            case 2://新担保标
            case 3://秒还标
                break;
            case 4://净值标
                $_netMoney = getFloatValue(0.9*$_minfo['money_collect']-$borrowDe[4],2);
                if($_netMoney<$borrow['borrow_money']) ajaxmsg("您的净值额度{$_netMoney}元，小于您准备借款的金额，不能发标",0);
                break;
            case 5://抵押标
                //$borrow_type=5;
                break;
        }
        if( $_POST['repayment_type'] == 1 ) {
            $borrow['duration_unit'] = BorrowModel::BID_CONFIG_DURATION_UNIT_DAY;
        } else {
            $borrow['duration_unit'] = BorrowModel::BID_CONFIG_DURATION_UNIT_MONTH;
        }

        $borrow['borrow_uid'] = $this->uid;
        $borrow['borrow_name'] = text($_POST['borrow_name']);

        //file_put_contents("333.txt", $borrow['borrow_name']);
        $borrow['borrow_duration'] = ($borrow['borrow_type']==3)?1:intval($_POST['borrow_duration']);//秒标固定为一月
        $borrow['borrow_interest_rate'] = floatval($_POST['borrow_interest_rate']);
        if(strtolower($_POST['is_day'])=='yes') $borrow['repayment_type'] = 1;
        elseif($borrow['borrow_type']==3) $borrow['repayment_type'] = 2;//秒标按月还
        else $borrow['repayment_type'] = intval($_POST['repayment_type']);

        // 验证期限是否在有效期内
        if( !GlobalModel::validate_bid_duration($borrow['repayment_type'], $borrow['borrow_duration']) ) {
            ajaxmsg('请检查借款期限是否正确',0);
        }

        $borrow['borrow_status'] = 0;
        $borrow['borrow_use'] = intval($_POST['borrow_use']);
        $borrow['add_time'] = time();
        $borrow['collect_day'] = intval($_POST['borrow_time']);
        $borrow['add_ip'] = get_client_ip();
        $borrow['borrow_info'] = text($_POST['borrow_info']);
        $borrow['reward_type'] = intval($_POST['reward_type']);
        $borrow['reward_num'] = floatval($_POST["reward_type_{$borrow['reward_type']}_value"]);
        $borrow['borrow_min'] = intval($_POST['borrow_min']);
        $borrow['borrow_max'] = intval($_POST['borrow_max']);
        $borrow['rate_type'] = BorrowModel::BID_CONFIG_RATE_TYPE_FULL_BORROW; // 满标计息
        if($_POST['is_pass']&&intval($_POST['is_pass'])==1) $borrow['password'] = md5($_POST['password']);
        $borrow['money_collect'] = floatval($_POST['money_collect']);//代收金额限制设置


        //借款费和利息
        $borrow['borrow_interest'] = getBorrowInterest($borrow['repayment_type'],$borrow['borrow_money'],$borrow['borrow_duration'],$borrow['borrow_interest_rate'], true, true);
        if($borrow['repayment_type']=='1' || $borrow['repayment_type']=='5'){
            $borrow['total'] = 1;
        }elseif($borrow['repayment_type']== 4) { // 如果为按天计息，按月付息，到期还本，所还期数未必等于月数
            $borrowScan = EqualEndMonth(array(
                'duration' => $borrow['borrow_duration'],
                'account' => $borrow['borrow_money'],
                'year_apr' => $borrow['borrow_interest_rate']
            ));
            $borrow['total'] = count($borrowScan);
        }
        else{
            $borrow['total'] = $borrow['borrow_duration'];//分几期还款
        }
        // 借款管理费
        $borrow['borrow_fee'] = BorrowModel::get_fee_borrow_manage($borrow['borrow_duration'], $borrow['borrow_money'], $borrow['duration_unit']);

        if($borrow['borrow_type']==3){//秒还标
            if($borrow['reward_type']>0){
                $_reward_money = getFloatValue($borrow['borrow_money']*$borrow['reward_num']/100,2);
            }
            $_reward_money =floatval($_reward_money);
            if(($_minfo['account_money']+$_minfo['back_money'])<($borrow['borrow_fee']+$_reward_money)) ajaxmsg("发布此标您最少需保证您的帐户余额大于等于".($borrow['borrow_fee']+$_reward_money)."元，以确保可以支付借款管理费和投标奖励费用",0);
        }

//        //投标上传图片资料（暂隐）
//        foreach($_POST['swfimglist'] as $key=>$v){
//            if($key>10) break;
//            $row[$key]['img'] = substr($v,1);
//            $row[$key]['info'] = $_POST['picinfo'][$key];
//        }
//        $borrow['updata']=serialize($row);

        $newid = M("borrow_info")->add($borrow);
        if($newid){
            $this->success("借款发布成功，网站会尽快初审",__APP__."/member/borrows/summa/");
        }
        else ajaxmsg("发布失败，请先检查是否完成了个人详细资料然后重试",0);

    }



}

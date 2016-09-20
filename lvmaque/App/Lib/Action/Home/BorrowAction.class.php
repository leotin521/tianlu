<?php
//解决火狐swfupload的session bug
if (isset($_POST[session_name()]) && empty($_SESSION)) {
    session_destroy();
    session_id($_POST[session_name()]);
    session_start();
}
// 本类由系统自动生成，仅供测试用途
class BorrowAction extends HCommonAction {
    public function index(){
        $version = FS('Webconfig/version');
		$per = C('DB_PREFIX');
		if($this->uid){
			$this->assign("mstatus", M('members_status')->field(true)->find($this->uid));
			$this->assign("mdata", getMemberInfoDone($this->uid));
			$minfo = getMinfo($this->uid,true);
			$this->assign("minfo", $minfo);
			$this->assign("netMoney", getNet($this->uid));//可用净值额度
			$_allnetMoney = getFloatValue(0.9*$minfo['money_collect'],2);
			$this->assign("allnetMoney",$_allnetMoney);//总净值额度
			$this->assign("capitalinfo", getMemberBorrowScan($this->uid));
            $member = BorrowModel::borrow_validate($this->uid);
            $info = M('borrow_apply')->field('user_type,status')->where(array('uid'=>$this->uid))->find();
            if (is_array($info)){
                $member['apply_type'] = $info['user_type'];
                $apply_status = $info['status'];
                $this->assign('app_status',$apply_status);
            }
            else $member['apply_type'] = 0;
            $this->assign('apply_type', $member['apply_type']);
            $this->assign('is_transfer', $member['is_transfer']);
            if( $member['is_transfer'] == MembersModel::MEMBERS_IS_TRANSFER_NORMAL ) {
                // 必须进行实名认证和手机认证
                $members_status = M('members_status')->field('phone_status,id_status')->where(array('uid'=>$this->uid))->find();
                if( $members_status['phone_status'] != 1 || $members_status['id_status'] != 1 ) {
                    $data['msg'] = '温馨提示';
                    $data['code'] = 4;
                } else {
                    if( $member['validate_user_type'] == MembersModel::MEMBERS_USER_VALIDATE_USER_TYPE_INIT ) {
                        if( $version['single'] == 1 && $version['business'] == 0 && $version['fund'] == 0 ) {
                            redirect(DOMAIN .'/member/memberinfo/index_index/tid/2');
                        }elseif( $version['single'] == 0 && ($version['business'] == 1 || $version['fund'] == 1) ) {
                            redirect(DOMAIN .'/member/memberinfo/index_index/tid/1');
                        }
                        $data['msg'] = '请选择申请类别';
                        $data['code'] = 1;
                    }else if( $member['validate_user_type'] == MembersModel::MEMBERS_USER_VALIDATE_USER_TYPE_WAIT_VIEW ) {
                        $data['msg'] = '您的申请正在审核';
                        $data['code'] = 2;
                    }else {
                        $data['code'] = 0;
                    }
                }

            }
            if( $member['is_transfer'] == MembersModel::MEMBERS_IS_TRANSFER_BUSINESS ) {
                $data['msg'] = '温馨提示';
                $data['code'] = 3;
            }
            if ($member['is_transfer'] == MembersModel::MEMBERS_IS_TRANSFER_PERSONAL){
                if (InvestorDetailModel::check_is_overdue($this->uid)==false){
                    $data['msg'] = '温馨提示';
                    $data['code'] = 5;
                }
            }
            $this->assign('single', $version['single']);
            $member_type = MembersModel::get_user_type(null, true);
            $this->assign('member_type', $member_type);
            $this->assign('user_type', $data);
		} else {
            MembersModel::unlogin_home();
        }
		$this->display();
    }
	
	public function post(){
		if(!$this->uid) $this->error("请先登录",__APP__."/login");
		$sess_uid = $this->uid;
		$vo = M('members')->field('id,user_name,user_type,validate_user_type,user_email,user_pass,is_ban')->where(array('id'=>$sess_uid))->find();
		if($vo['is_ban']==1||$vo['is_ban']==2) ajaxmsg("您的帐户已被冻结，请联系客服处理！",0);
		$vminfo = M('members')->field("is_transfer,user_leve,time_limit,is_borrow")->find($this->uid);

        if($vminfo['is_transfer'] != MembersModel::MEMBERS_IS_TRANSFER_PERSONAL)
            $this->error("请先申请为个人借款者",__APP__."/borrow/index");

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
		
		$this->display();
	}

    /**
     *
     * @throws Exception
     */
	public function save(){
	    
		if(!$this->uid) $this->error("请先登录",__APP__."/member/common/login");
		$pre = C('DB_PREFIX');
		//相关的判断参数
		$rate_lixt = explode("|",$this->glo['rate_lixi']);
		//相关的判断参数
		if (!M('borrow_info')->autoCheckToken($_POST)){
		    $this->error('非法请求');
		}
		$borrow['borrow_type'] = intval($_POST['borrow_type']);
		if(floatval($_POST['borrow_interest_rate'])>$rate_lixt[1] || floatval($_POST['borrow_interest_rate'])<$rate_lixt[0]) $this->error("提交的借款利率超出允许范围，请重试",0);
		$borrow['borrow_money'] = intval($_POST['borrow_money']);
        if(strtolower($_POST['is_day'])=='yes') $_POST['repayment_type'] = 1; // repayment_type,在某种条件下点击按天按钮时获取不到值

		$_minfo = getMinfo($this->uid,"m.pin_pass,mm.account_money,mm.back_money,mm.credit_limit,mm.money_collect");
		///////////////////////////////////////////////////////
		$borrowNum=M('borrow_info')->field("borrow_type,count(id) as num,sum(borrow_money) as money,sum(repayment_money) as repayment_money")->where("borrow_uid = {$this->uid} AND borrow_status=6 ")->group("borrow_type")->select();
		$borrowDe = array();
		foreach ($borrowNum as $k => $v) {
			$borrowDe[$v['borrow_type']] = $v['money'] - $v['repayment_money'];
		}
		///////////////////////////////////////////////////
		switch($borrow['borrow_type']){
			case 1://普通标
				if(intval($_minfo['credit_limit']) < intval($borrow['borrow_money'])) {
                    $this->error("您的可用信用额度为".intval($_minfo['credit_limit'])."元，小于您准备借款的金额，不能发标", __APP__.'/member/borrows/credit');
                }
                $result = M('borrow_info')->field("borrow_uid, sum(borrow_money) as money")->where("borrow_uid={$this->uid} AND borrow_status=0 AND borrow_type=1")->find();
                if(!empty($result)) {
                    if((intval($result['money'])+intval($borrow['borrow_money'])) > intval($_minfo['credit_limit'])) {
                        $this->error("您的待审核借款金额加上此次借款金额，大于您的信用额度".intval($_minfo['credit_limit'])."元，不能发标");
                    }
                }
			break;
			case 2://新担保标
			case 3://秒还标
			break;
			case 4://净值标
				$_netMoney = getFloatValue(0.9*$_minfo['money_collect']-$borrowDe[4],2);
				if($_netMoney<$borrow['borrow_money']) $this->error("您的净值额度{$_netMoney}元，小于您准备借款的金额，不能发标");
				$result = M('borrow_info')->field("borrow_uid, sum(borrow_money) as money")->where("borrow_uid={$this->uid} AND borrow_status=0 AND borrow_type=4")->find();
				if(!empty($result)) {
				    if((intval($result['money'])+intval($borrow['borrow_money'])) > intval($_netMoney)) {
				        $this->error("您的待审核借款金额加上此次借款金额，大于您的净值额度".intval($_netMoney)."元，不能发标");
				    }
				}
				$result1 = M('borrow_info')->field("borrow_uid")->where("borrow_uid={$this->uid} AND borrow_status=2 AND borrow_type=4")->find();
				if(!empty($result1)){
				    $this->error("您已有一个借款中的净值标，不能再次发标！");
				}
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
		$borrow['borrow_duration'] = ($borrow['borrow_type']==3)?1:intval($_POST['borrow_duration']);//秒标固定为一月
		$borrow['borrow_interest_rate'] = floatval($_POST['borrow_interest_rate']);
		if(strtolower($_POST['is_day'])=='yes') $borrow['repayment_type'] = 1;
		elseif($borrow['borrow_type']==3) $borrow['repayment_type'] = 2;//秒标按月还
		else $borrow['repayment_type'] = intval($_POST['repayment_type']);

        // 验证期限是否在有效期内
        if( !GlobalModel::validate_bid_duration($borrow['repayment_type'], $borrow['borrow_duration']) ) {
            $this->error('请检查借款期限是否正确');
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
		$borrow['money_collect'] = floatval($_POST['moneycollect']);//代收金额限制设置
		//验证最多投标总额是否大于借贷总金额
		if($borrow['borrow_max']>$borrow['borrow_money']){
		    $this->error("最多投标总额不能大于借贷总金额");
		}
		//验证最多投标总额是否大于借贷总金额
		if(($borrow['borrow_max']!=0)&&($borrow['borrow_money']%$borrow['borrow_max']!=0)){
		    $this->error("最多投标总额必须是借贷总金额的整倍数!");
		}
		
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
			if(($_minfo['account_money']+$_minfo['back_money'])<($borrow['borrow_fee']+$_reward_money)) $this->error("发布此标您最少需保证您的帐户余额大于等于".($borrow['borrow_fee']+$_reward_money)."元，以确保可以支付借款管理费和投标奖励费用");
		}
		
		//投标上传图片资料（暂隐）
		foreach($_POST['swfimglist'] as $key=>$v){
			if($key>10) break;
			$row[$key]['img'] = substr($v,1);
			$row[$key]['info'] = $_POST['picinfo'][$key];
		}
		$borrow['updata']=serialize($row);
		
		$newid = M("borrow_info")->add($borrow);

		if($newid) $this->success("借款发布成功，网站会尽快初审",__APP__."/member/borrows/summa/");
		else $this->error("发布失败，请先检查是否完成了个人详细资料然后重试");
		
	}
	
	//swf上传图片
	public function swfupload(){
		if($_POST['picpath']){
			$imgpath = substr($_POST['picpath'],1);
			if(in_array($imgpath,$_SESSION['imgfiles'])){
					 unlink(C("WEB_ROOT").$imgpath);
					 $thumb = get_thumb_pic($imgpath);
				$res = unlink(C("WEB_ROOT").$thumb);
				if($res) $this->success("删除成功","",$_POST['oid']);
				else $this->error("删除失败","",$_POST['oid']);
			}else{
				$this->error("图片不存在","",$_POST['oid']);
			}
		}else{
			$this->savePathNew = C('HOME_UPLOAD_DIR').'Product/';
			$this->thumbMaxWidth = C('PRODUCT_UPLOAD_W');
			$this->thumbMaxHeight = C('PRODUCT_UPLOAD_H');
			$this->saveRule = date("YmdHis",time()).rand(0,1000);
			$info = $this->CUpload();
			$data['product_thumb'] = $info[0]['savepath'].$info[0]['savename'];
			if(!isset($_SESSION['count_file'])) $_SESSION['count_file']=1;
			else $_SESSION['count_file']++;
			$_SESSION['imgfiles'][$_SESSION['count_file']] = $data['product_thumb'];
			echo "{$_SESSION['count_file']}:".__ROOT__."/".$data['product_thumb'];//返回给前台显示缩略图
		}
	}

}
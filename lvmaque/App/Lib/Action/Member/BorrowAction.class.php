<?php
// 本类由系统自动生成，仅供测试用途
class BorrowAction extends MCommonAction {


	public function index(){
		$this->display();
	}
    public function borrow(){
		$ids = M('members')->getFieldById($this->uid,'credit_status');
		if($ids==1){
			$vminfo = M('members')->field("credit_limit,credit_use,credit_status")->find($this->uid);
			$vminfo['credit_canb_money']= $vminfo['credit_limit'] - $vminfo['credit_use'];
			$this->assign("vo",$vminfo);
			$this->assign("re_type",C('REPAYMENT_TYPE'));
			$data['html'] = $this->fetch();
		}
		else  $data['html'] = '<script type="text/javascript">alert("为保障资金安全，请填写借入人资料，通过借入人审核，再发标借款。点击确定后转到转到借入人审核页面。");window.location.href="'.__APP__.'/member/Memberinfo/index";</script>';

		exit(json_encode($data));
    }
	
	public function addborrow(){
		$rate_lixt = explode("|",$this->glo['rate_lixi']);
		$borrow_duration = explode("|",$this->glo['borrow_duration']);
		$fee_borrow_manage = explode("|",$this->glo['fee_borrow_manage']);
		$vminfo = M('members')->field("credit_limit,credit_use,credit_status,user_leve,time_limit")->find($this->uid);
		
		$add_field = array('borrow_interest_rate','borrow_money','borrow_duration','repayment_type','borrow_name','borrow_info');
		foreach($add_field as $v){
			$savedata[$v]= text($_POST[$v]); 
		}

		if($savedata['borrow_interest_rate']>$rate_lixt[1] || $savedata['borrow_interest_rate']<$rate_lixt[0]) ajaxmsg("提交的数据有误，请重试",0);
		if($savedata['borrow_duration']>$borrow_duration[1] || $savedata['borrow_duration']<$borrow_duration[0]) ajaxmsg("提交的数据有误，请重试",0);
		if($savedata['borrow_money']>($vminfo['credit_limit'] - $vminfo['credit_use']) || $savedata['borrow_money']<500) ajaxmsg("提交的数据有误，请重试",0);
		//if(!in_array($savedata['repayment_type'],C('REPAYMENT_TYPE'))) ajaxmsg("提交的数据有误，请重试",0);
		if(empty($savedata['borrow_name'])||empty($savedata['repayment_type'])||empty($savedata['borrow_info'])) ajaxmsg("提交的数据有误，请重试",0);
		
		if($vminfo['credit_status']==0) ajaxmsg("您还未通过借款审核",0);
		$bc = M("borrow_info")->where("borrow_uid={$this->uid} AND borrow_status in(0,2)")->count('id');
		if($bc>0) ajaxmsg("您有正在审核或者正在筹集中的借款，所以暂时不能发布新的借款申请",0);
		
		($vminfo['user_leve']==1 && $vminfo['time_limit']>time())?$fee_rate=($fee_borrow_manage[1]/100):$fee_rate=($fee_borrow_manage[0]/100);
		
		$savedata['borrow_uid'] = $this->uid;
		$savedata['borrow_interest'] = getBorrowInterest($savedata['repayment_type'],$savedata['borrow_money'],$savedata['borrow_duration'],$savedata['borrow_interest_rate']);
		$savedata['borrow_fee'] = getFloatValue($fee_rate*$savedata['borrow_money'],2);
		$savedata['borrow_status'] = 0;
		$savedata['add_time'] = time();
		$savedata['add_ip'] = get_client_ip();
		$savedata['total']=($savedata['repayment_type']==1)?$savedata['borrow_duration']:"1";
		$newid = M('borrow_info')->add($savedata);
		if($newid) ajaxmsg();
		else ajaxmsg("借款申请发布失败，请重试",0);
	}
}
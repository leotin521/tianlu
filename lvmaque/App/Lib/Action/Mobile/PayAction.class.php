<?php
// 本类由系统自动生成，仅供测试用途
class PayAction extends HCommonAction {
	var $paydetail = NULL;
	var $payConfig = NULL;
	var $locked = false;
	var $return_url = "";
	var $notice_url = "";
	var $member_url = "";
	
	
	//////////////////////////////////////////新宝付接口处理方法开始    shao2014-01-26/////////////////////////////
	public function paybaofoback(){
		
		$recode = $_REQUEST['Result'];
		
			if($recode == "1"){
				$signGet = $this->getSign( "baofoo_return", $_REQUEST );
				
				if ( $_REQUEST['Md5Sign'] == $signGet )
				{
					$this->success( "充值完成", __APP__."/member/" );
				}
				else
				{
					$this->error( "签名不付", __APP__."/member/" );
				}
			}else{
				$this->error(auto_charset($_REQUEST['resultDesc']), __APP__."/member/" );
			}
	}
	public function paybaofonotice(){
		
		require_once("baofu/BaofooSdk.php");
		$member_id = $this->payConfig['baofoo']['MemberID'];	//商户号
		$terminal_id = $this->payConfig['baofoo']['TerminalID'];  //终端号
		$data_type = "xml";
		$path = $_SERVER['DOCUMENT_ROOT'];
		$path .=  "/App/Lib/Action/Mobile/baofu/cer/";	//证书路径
		$private_key_password = "100000178_204500";  //私钥密码
		$request_url = "https://tgw.baofoo.com/apipay/sdk";  //SDK尊享版请求地址
		
		$baofoosdk = new BaofooSdk($member_id, $terminal_id, $data_type, $path."merchant_pri.pfx",$path."baofoo_pub.cer",$private_key_password); //初始化加密类。
		$data_content = $baofoosdk->decryptByPublicKey($_REQUEST['data_content']);

		$val = base64_decode($data_content);
		$val = xml_to_array($val);
		
		 M('notify')->add(array('data'=>$json));
		$recode = $val['Result']['resp_code'];
		
				if ($recode == 0000){
					
					$nid = $this->createnid("baofoo", $val['result']['trans_id'] );
					
					$done = $this->payDone(1,$nid,$val['result']['trans_id']);
					echo "OK";
					
				}else{
					$done = $this->payDone(3, $nid);
					echo "Fail";
				}
				if($done===true){
					echo "OK";
				}else{
				 	echo "Fail";
				}
				
	}
	//宝付银联支付回调接口开始
	public function paybaofonotice_yl(){
				if ($_REQUEST['Result'] == 1){
					//$nid = $this->createnid("baofoo", $val['result']['trans_id'] );
					$done = $this->payDone_yl(1,$_REQUEST['TransID']);
					echo "OK";
				}else{
					$done = $this->payDone_yl(3,$_REQUEST['TransID']);
					echo "Fail";
				}
				if($done===true){
					echo "OK";
				}else{
				 	echo "Fail";
				}
	}
	private function payDone_yl($status,$oid){
		$done = false;
		$Moneylog = D('member_payonline');
		if($this->locked) return false;
		$this->locked = true;
		$vo = M('member_payonline')->field('uid,money,fee,status')->where("tran_id='{$oid}'")->find();
		switch($status){
			case 1:
				$updata['status'] = $status;
				//$updata['tran_id'] = text($oid);
				$vo = M('member_payonline')->field('uid,money,fee,status')->where("tran_id='{$oid}'")->find();
				
				if($vo['status']!=0 || !is_array($vo)) return;
				$xid = $Moneylog->where("uid={$vo['uid']} AND tran_id='{$oid}'")->save($updata);
				
				$tmoney = floatval($vo['money'] - $vo['fee']);
				if($xid) $newid = memberMoneyLog($vo['uid'],3,$tmoney,"充值订单号:".$oid,0,'@网站管理员@');//更新成功才充值,避免重复充值 
				$vx = M("members")->field("user_phone,user_name")->find($vo['uid']);
				SMStip("payonline",$vx['user_phone'],array("#USERANEM#","#MONEY#"),array($vx['user_name'],$vo['money']));
			break;
			case 2:
				$updata['status'] = $status;
				//$updata['tran_id'] = text($oid);
				$xid = $Moneylog->where("uid={$vo['uid']} AND tran_id='{$oid}'")->save($updata);
			break;
			case 3:
				$updata['status'] = $status;
				$xid = $Moneylog->where("uid={$vo['uid']} AND tran_id='{$oid}'")->save($updata);
			break;
		}
		
		if($status>0){
			if($xid) $done = true;
		}
		$this->locked = false;
		return $done;
	}
	//宝付银联支付回调接口结束

	
	private function payDone($status,$nid,$oid){
		$done = false;
		$Moneylog = D('member_payonline');
		if($this->locked) return false;
		$this->locked = true;
		$vo = M('member_payonline')->field('uid,money,fee,status')->where("nid='{$nid}'")->find();
		switch($status){
			case 1:
				$updata['status'] = $status;
				$updata['tran_id'] = text($oid);
				$vo = M('member_payonline')->field('uid,money,fee,status')->where("nid='{$nid}'")->find();
				
				if($vo['status']!=0 || !is_array($vo)) return;
				$xid = $Moneylog->where("uid={$vo['uid']} AND nid='{$nid}'")->save($updata);
				
				$tmoney = floatval($vo['money'] - $vo['fee']);
				if($xid) $newid = memberMoneyLog($vo['uid'],3,$tmoney,"充值订单号:".$oid,0,'@网站管理员@');//更新成功才充值,避免重复充值 
				$vx = M("members")->field("user_phone,user_name")->find($vo['uid']);
				SMStip("payonline",$vx['user_phone'],array("#USERANEM#","#MONEY#"),array($vx['user_name'],$vo['money']));
			break;
			case 2:
				$updata['status'] = $status;
				$updata['tran_id'] = text($oid);
				$xid = $Moneylog->where("uid={$vo['uid']} AND nid='{$nid}'")->save($updata);
			break;
			case 3:
				$updata['status'] = $status;
				$xid = $Moneylog->where("uid={$vo['uid']} AND nid='{$nid}'")->save($updata);
			break;
		}
		
		if($status>0){
			if($xid) $done = true;
		}
		$this->locked = false;
		return $done;
	}
	
	private function createnid($type,$static){
			return md5("XXXXX@@#$%".$type.$static);
	}
	
	

}
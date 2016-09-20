<?php
class RbPayAction extends HCommonAction {
	//宝付H5支付接口
	public function _Myinit(){
		$this->payConfig = FS("Webconfig/payconfig");
		$this->reapalReturn_url = "http://".$_SERVER['HTTP_HOST']."/mobile/rbpay/payReapalReturn";//返回融宝前台同步
		$this->reapalNotice_url = "http://".$_SERVER['HTTP_HOST']."/mobile/rbpay/payReapalNotice";//返回融宝后台异步
	}
	public function pay() {
		
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
		$uid = $arr['uid'];
		$amoney = $arr['amoney'];
		require_once 'rongbao/util.php'; 
		require_once 'rongbao/config.php'; 
		
		$m_info = M('member_info')->field('real_name,idcard')->where("uid={$uid}")->find();
		
		$id_card = isset($m_info["idcard"]) ? $m_info["idcard"] : "";  //身份证号
		$jumpmsg['is_jumpmsg'] = '请先进行实名认证';
		if($id_card=='')AppCommonAction::ajax_encrypt($jumpmsg,1004);
		//参数数组
		$paramArr = array(
			 'merchant_id' => $merchant_id,
			 'order_no' =>'reapal'.date('YmdHis').mt_rand(100000,999999),
			 'transtime' =>time(),
			 'currency' =>'156',
			 'total_fee' =>$amoney*100,
			 'title' => '融宝冲值',
			 'body' => $uid.'冲值金额'.$amoney ,
			 'member_id' => $uid,
			 'terminal_type'=>'mobile',
			 'terminal_info' => 'IMEI',
			 'member_ip' => get_client_ip(),
			 'seller_email' => $apiEmail,
			 'notify_url' => $this->reapalNotice_url,
			 'return_url' => $this->reapalReturn_url,
			 'payment_type' => '2',
			 'pay_method' => 'bankPay'
		);

		//生成签名
		$sign = createSign($paramArr,$apiKey);

		$paramArr['sign'] = $sign;
		//生成AESkey
		$generateAESKey = generateAESKey();
		$request = array();
		$request['merchant_id'] = $merchant_id;
		//加密key
		
		$encryptkey = RSAEncryptkey($generateAESKey,$reapalPublicKey);
		$request['encryptkey'] = rawurlencode($encryptkey);
		//加密数据
		$data = AESEncryptRequest($generateAESKey,$paramArr);
		$request['data'] = rawurlencode($data);

		$adddata['money'] = number_format($amoney, 2,".", "" );
		$adddata['add_time'] = time();
		$adddata['add_ip'] = get_client_ip();
		$adddata['status'] = 0;
		$adddata['uid'] = $uid;
		$adddata['fee'] = getfloatvalue( $this->payConfig['reapal']['feerate'] * $amoney/100,2);
		$adddata['nid'] = $paramArr['order_no'];
		$adddata['way'] = "reapal";
		M("member_payonline")->add($adddata);
		AppCommonAction::ajax_encrypt($request,1);
		

    }

	//////////////////////////////////////////融宝支付接口处理方法开始    qin2014-08-25/////////////////////////////
	public function payReapalReturn(){//同步
		require_once 'rongbao/util.php'; 
		require_once 'rongbao/config.php'; 
		$encryptkey=$_POST['encryptkey'];
		$data=$_POST['data'];
		$key = RSADecryptkey($encryptkey,$merchantPrivateKey);
		$dataTemp = AESDecryptResponse($key,$data);  
		$dataArray = json_decode($dataTemp,true);
		$moneyarrsign = $dataArray['sign'];
		unset($dataArray['sign']);
		ksort($dataArray);
		$string = '';
		foreach($dataArray as $k => $v){
			$string.= $k.'='.$v.'&';
		}
		$msg = substr ( $string,0,(strlen ( $string )-1));
		$sign = md5($msg.$apiKey);
		if ($sign == $moneyarrsign) {
			 if($dataArray['status'] == 'TRADE_FINISHED'){
				 $msg = '您已成功充值';
				$this->assign('msg',$msg);
				$this->display(); 
			}else{
				$msg = '充值失败';
				$this->assign('msg',$msg);
				$this->display(); 
				
			}
		}
	}
	//异步
	public function payReapalNotice(){
		require_once 'rongbao/util.php'; 
		require_once 'rongbao/config.php'; 
		$encryptkey=$_POST['encryptkey'];
		$data=$_POST['data'];
		$key = RSADecryptkey($encryptkey,$merchantPrivateKey);
		$dataTemp = AESDecryptResponse($key,$data);  
		$dataArray = json_decode($dataTemp,true);
		$moneyarrsign = $dataArray['sign'];
		unset($dataArray['sign']);
		ksort($dataArray);
		$string = '';
		foreach($dataArray as $k => $v){
			$string.= $k.'='.$v.'&';
		}
		$msg = substr ( $string,0,(strlen ( $string )-1));
		$sign = md5($msg.$apiKey);
		//生成签名
		$nid = $dataArray['order_no'];
		$oid = $dataArray['trade_no'];
		if ($sign == $moneyarrsign) {
		   if($dataArray['status'] == 'TRADE_FINISHED'){
			   $done = $this->payDone( 1, $nid, $oid);
		   }else{
			   //支付失败的处理
					$done = $this->payDone( 2, $nid, $oid);
		   }
		}else{
			$done = $this->payDone(3,$nid);
		}
		if ( $done === true ){
			echo "success";
		}else{
			echo "fail";
		}
	}
	
	//////////////////sina-end/////////////////////
	
	private function payDone($status,$nid,$oid){
		$done = false;
		$Moneylog = D('member_payonline');
		if($this->locked) return false;
		$this->locked = true;
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

}

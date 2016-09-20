<?php
class BfPayAction extends MMCommonAction {
	//宝付H5支付接口
	public function pay() {
		
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
		
        $arr = AppCommonAction::get_decrypt_json($arr);


		//每次更改客户需要修改的参数
		$private_key_password = "100000178_204500";  //私钥密码
		$path = $_SERVER['DOCUMENT_ROOT'];
		$path .=  "/App/Lib/Action/Mobile/baofu/cer/";	//证书路径
		$siyao = $path."merchant_pri.pfx";
		$gongyao = $path."baofoo_pub.cer";
		$request_url = "https://tgw.baofoo.com/apipay/sdk";  //SDK尊享版请求地址
		//每次更改客户需要修改的参数

		require_once("baofu/SdkXML.php");
		require_once("baofu/BaofooSdk.php");
		
		if(!file_exists($siyao))
		{
		die("私钥证书不存在！<br>");
		}
		if(!file_exists($gongyao))
		{
		die("公钥证书不存在！<br>");
		}
	
		$borrow_config = require C("APP_ROOT")."Conf/borrow_config.php";
		//echo $borrow_config['bank_n'][1];exit;
		
		$this->payConfig = FS("Webconfig/payconfig");
		$terminal_id = $this->payConfig['baofoo']['TerminalID'];  //终端号
		$txn_sub_type = "02"; //SDK交易类型为02
		$member_id = $this->payConfig['baofoo']['MemberID'];	//商户号
		$data_type = "xml";
		/*$arr['uid'] = 35;
		$arr['amount'] = 100;*/
		$mess['uid'] = intval($arr['uid']); //50;//
		$mess['money'] = $arr['amount']*100;//1;//
		$mess['bank_id'] = intval($arr['bank_id']);
		$mess['bank_num'] = $arr['bank_num'];
		$vo = M('members')->field('user_phone')->where("id={$mess['uid']}")->find();
		$m_info = M('member_info')->field('real_name,idcard')->where("uid={$mess['uid']}")->find();
		$m_bank = M('member_banks')->field('bank_num,bank_name')->where("uid={$mess['uid']}")->find();

		//print_r(M()->getlastsql());exit;
		$id_card = isset($m_info["idcard"]) ? $m_info["idcard"] : "";  //身份证号
		$jumpmsg['is_jumpmsg'] = '请先进行实名认证';
		if($id_card=='')AppCommonAction::ajax_encrypt($jumpmsg,1004);
		$id_holder = isset($m_info["real_name"]) ? $m_info["real_name"] : "";	//持卡人姓名
		$mobile = isset($vo["user_phone"]) ? $vo["user_phone"] : "";	//持卡人手机号
		$txn_amt = isset($mess['money']) ? $mess['money'] : "";	//交易金额
		$page_url = "http://".$_SERVER['HTTP_HOST']."/mobile/pay/paybaofoback";
		$return_url = "http://".$_SERVER['HTTP_HOST']."/mobile/pay/paybaofonotice";//服务器通知地址

		$pay_code =  $borrow_config['bank_n'][$m_bank['bank_name']];	//银行编码
		//ajaxmsg("asdf"+$pay_code);
		//ajaxmsg("asdf".$m_bank['bank_name']);
		
		$acc_no = isset($m_bank['bank_num']) ? $m_bank['bank_num'] : "";	//银行卡号
		$jumpmsg['is_jumpmsg'] = '请先绑定银行卡';
		if($acc_no=='')AppCommonAction::ajax_encrypt($jumpmsg,1005);
		$trans_id = "TID".strtotime(date('Y-m-d H:i:s',time())).rand(1000,9999);
		//ob_start (); //打开缓冲区
		$arr = array ('txn_sub_type'=>$txn_sub_type,
				  'biz_type'=>"0000",
				  'terminal_id'=>$terminal_id,
				  'member_id'=>$member_id,
				  'pay_code'=>$pay_code,
				  'acc_no'=>$acc_no,
				  'id_card_type'=>"01",
				  'id_card'=>$id_card,
				  'id_holder'=>$id_holder,
				  'mobile'=>$mobile,
				  'trans_id'=>$trans_id,
				  'txn_amt'=>$txn_amt,
				  'trade_date'=>date('YmdHis',time()),
				  'page_url'=>$page_url,
				  'return_url'=>$return_url,
				  );
		$array['uid'] = $mess['uid'];
		$array['add_time'] = time();
		$array['tran_id'] = $trans_id;
		$array['add_ip'] = $_SERVER["REMOTE_ADDR"];
		$array['money'] = $mess['money']/100;
		
		$array['off_bank'] = 0;
		$array['off_way'] = 0;
		$array['deal_user'] = 0;
		$array['deal_uid'] = 0;
		$array['payimg'] = 0;


		$array['fee'] = getfloatvalue( $this->payConfig['baofoo']['feerate'] * $array['money']/100, 2 );
		$array['nid'] = $this->createnid("baofoo", $trans_id);
		$array['way'] = "baofoo";
        M("member_payonline" )->add($array);

		$baofoosdk = new BaofooSdk($member_id, $terminal_id, $data_type, $siyao,$gongyao,$private_key_password); //初始化加密类。		  

		if($data_type == "json")
		{
			$Encrypted_string = str_replace("\\/", "/",json_encode($arr));//转JSON
			//echo $Encrypted_string."<br>";
		}
		else
		{
			$toxml = new SdkXML();
			$Encrypted_string = $toxml->toXml($arr);//转XML
			//echo $Encrypted_string."<br>";
		}



		$Encrypted = $baofoosdk->encryptedByPrivateKey(base64_encode($Encrypted_string));	//先BASE64进行编码再RSA加密
		//print_r($Encrypted);exit;
		//echo $Encrypted."<br>";  //输出密文
		//ob_end_clean();//清空缓冲区内容，并关闭缓冲区；

		$val = $baofoosdk->post($Encrypted,$request_url);	//发送请求到宝付服务器，并输出返回结果。
		$val = json_decode(urldecode($val),true);
		//echo  $val['tradeNo'];
		//return $val['tradeNo'];
		if(intval($val['retCode']) == 0000){
			AppCommonAction::ajax_encrypt($val,1);
		}else
		{
			$val['message'] = $val['retMsg'];
			AppCommonAction::ajax_encrypt($val,0);
		}

    }
	private function createnid($type,$static){
			return md5("XXXXX@@#$%".$type.$static);
	}
	//宝付银联支付接口
	public function pay2() {
		
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
		//ajaxmsg('请到网站端冲值!',0);
        $arr = AppCommonAction::get_decrypt_json($arr);


		require_once("baofu/SdkXML.php");
		require_once("baofu/BaofooSdk.php");
		$path = $_SERVER['DOCUMENT_ROOT'];
		
	
		$borrow_config = require C("APP_ROOT")."Conf/borrow_config.php";
		$this->payConfig = FS("Webconfig/payconfig");
		$request_url = "http://tgw.baofoo.com/payupwap";  //SDK尊享版请求地址
		$terminal_id = $this->payConfig['baofoo']['TerminalID'];  //终端号
		$txn_sub_type = "02"; //SDK交易类型为02
		$member_id = $this->payConfig['baofoo']['MemberID'];	//商户号
		$pkey = $this->payConfig['baofoo']['pkey'];	//商户号
		$data_type = "xml";
		/*$arr['uid'] = 50;
		$arr['amount'] = 100;*/
		$mess['uid'] = intval($arr['uid']); //50;//
		$mess['money'] =  $arr['amount']*100;
		$vo = M('members')->field('user_phone,user_email')->where("id={$mess['uid']}")->find();
		$m_info = M('member_info')->field('real_name,idcard')->where("uid={$mess['uid']}")->find();

		$m_bank = M('member_banks')->field('bank_num,bank_name')->where("uid={$mess['uid']}")->find();

		

		//print_r(M()->getlastsql());exit;
		$id_card = isset($m_info["idcard"]) ? $m_info["idcard"] : "";  //身份证号
		$jumpmsg['is_jumpmsg'] = '请先进行实名认证';
		if($id_card=='')AppCommonAction::ajax_encrypt($jumpmsg,1004);

		$id_holder = isset($m_info["real_name"]) ? $m_info["real_name"] : "";	//持卡人姓名
		
		$user_email = isset($vo["user_email"]) ? $vo["user_email"] : "";	//持卡人手机号
		$txn_amt = isset($mess['money']) ? $mess['money'] : "";	//交易金额
		$page_url = "http://".$_SERVER['HTTP_HOST']."/mobile/pay/paybaofoback";
		$return_url = "http://".$_SERVER['HTTP_HOST']."/mobile/pay/paybaofonotice_yl";//服务器通知地址
		$trans_id = "TID".strtotime(date('Y-m-d H:i:s',time())).rand(1000,9999);
		$arr = array (///'txn_sub_type'=>$txn_sub_type,
				  //'biz_type'=>"0000",
				  'TerminalID'=>$terminal_id,
				  'MemberID'=>$member_id,
				  'PayID'=>'4010001',
				  'NoticeType'=>0,
				  'KeyType'=>1,
				  'UserName'=>$id_holder,
				  'Email'=>$user_email,
				  'AdditionalInfo'=>'充值',
				  'TransID'=>$trans_id,
				  'InterfaceVersion'=>'4.0',
				  'CommodityName'=>'lvmaque',
				  'OrderMoney'=>$txn_amt,
				  'CommodityAmount'=>1,
				  'TradeDate'=>date('YmdHis',time()),
				  'PageUrl'=>$page_url,
				  
				  'ReturnUrl'=>$return_url,
				  );
		$signature = $arr['MemberID'] . '|' . $arr['PayID'] . '|' . $arr['TradeDate'] . '|' . $arr['TransID'] . '|' . $arr['OrderMoney'] . '|' . $arr['PageUrl'] . '|' . $arr['ReturnUrl'] . '|' . $arr['NoticeType'] . '|' . $pkey;
		//ob_start (); //打开缓冲区
		$arr = array (///'txn_sub_type'=>$txn_sub_type,
				  //'biz_type'=>"0000",
				  'TerminalID'=>$terminal_id,
				  'MemberID'=>$member_id,
				  'PayID'=>'4010001',
				  'NoticeType'=>0,
				  'KeyType'=>1,
				  'UserName'=>$id_holder,
				  'Email'=>$user_email,
				  'AdditionalInfo'=>'充值',
				  'TransID'=>$trans_id,
				  'InterfaceVersion'=>'4.0',
				  'CommodityName'=>'lvmaque',
				  'OrderMoney'=>$txn_amt,
				  'CommodityAmount'=>1,
				  'TradeDate'=>date('YmdHis',time()),
				  'PageUrl'=>$page_url,
				  'Signature'=>md5($signature),
				  'ReturnUrl'=>$return_url,
				  );
		$array['uid'] = $mess['uid'];
		$array['add_time'] = time();
		$array['tran_id'] = $trans_id;
		$array['add_ip'] = $_SERVER["REMOTE_ADDR"];
		$array['money'] = $mess['money']/100;
		
		$array['off_bank'] = 0;
		$array['off_way'] = 0;
		$array['deal_user'] = 0;
		$array['deal_uid'] = 0;
		$array['payimg'] = 0;

		
		$array['fee'] = getfloatvalue( $this->payConfig['baofoo']['feerate'] * $array['money']/100, 2 );
		$array['nid'] = $arr['Signature'];
		$array['way'] = "baofoo";
        M("member_payonline" )->add($array);

		$baofoosdk = new BaofooSdk($member_id, $terminal_id, '', '','','abcdefg'); //初始化加密类。		  

		//if($data_type == "json")
		//{
			$Encrypted_string = str_replace("\\/", "/",json_encode($arr));//转JSON
			//echo $Encrypted_string."<br>";
		//}
		//else
		//{
			/*$toxml = new SdkXML();
			$Encrypted_string = $toxml->toXml($arr);//转XML
			//echo $Encrypted_string."<br>";
		}*/



		//$Encrypted = $baofoosdk->encryptedByPrivateKey(base64_encode($Encrypted_string));	//先BASE64进行编码再RSA加密
		//echo $Encrypted."<br>";  //输出密文
		//ob_end_clean();//清空缓冲区内容，并关闭缓冲区；
		/*$context = array(
			'http' => array(
				'method' => 'POST',
				 'header' => 'Content-type: application/x-www-form-urlencoded',
				 'content' => http_build_query($arr)
				)
		);
		# var_dump($context);
		 $streamPostData = stream_context_create($context);
		 $httpResult = file_get_contents($request_url, false, $streamPostData);*/
		$val = $baofoosdk->post_yl($arr,$request_url);//$httpResult;//	//发送请求到宝付服务器，并输出返回结果。

		$b=preg_match_all('/\d+/',$val,$shuzu);
		//print_r($val);exit;
		//$val = json_decode(urldecode($val),true);

		$node['tradeNo'] = $shuzu[0][1];
		//return $val['tradeNo'];
		if($shuzu[0][2] === '00'){
			ajaxmsg($node,1);
		}else
		{
			ajaxmsg($node,0);
		}

    }

}

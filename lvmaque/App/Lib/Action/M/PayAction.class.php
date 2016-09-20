<?php
define("BAOFOO_ENCRYPT_LEN", 32);
// 本类由系统自动生成，仅供测试用途
class PayAction extends HCommonAction {
	var $paydetail = NULL;
	var $payConfig = NULL;
	private $private_key;
	var $locked = false;
    private $appId;   //微信参数
    private $appSecret;  //微信参数

    public function __construct($appId, $appSecret) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }

	public function _Myinit(){
		$this->payConfig = FS("Webconfig/payconfig");
		$this->reapalReturn_url = "http://".$_SERVER['HTTP_HOST']."/M/user/index";//融宝支付同步
		$this->baofooNotice_url = "http://".$_SERVER['HTTP_HOST']."/M/Pay/baofopaynotifyurl";//宝付支付异步
		$this->reapalNotice_url = "http://".$_SERVER['HTTP_HOST']."/M/Pay/reapalpaynotifyurl";//融宝支付异步
	}

	//充值方式通道
	public  function paymoneytype(){
		$paytype = text($_POST['pay_channel']);
		if($paytype == 'reapal'){
			$this->reapal_app();
		}elseif($paytype == 'baofoo'){
			$this->baofo_app();
		}
	}
	
	//宝付支付
	// 私钥加密
	function encryptedByPrivateKey($data_content){	
		$private_key_path =BAOFOOPUBLICKEY;//商户加密证书
		$pkcs12 = file_get_contents($private_key_path);
		$private_key_password = '100000178_204500';
		$private_key = array();
		openssl_pkcs12_read($pkcs12,$private_key,$private_key_password);
		//return "私钥是否可用:", empty($private_key) == true ? '不可用':'可用', "\n";
		$private_key = $private_key["pkey"];
		$data_content = base64_encode(json_encode($data_content));
         $encrypted = "";
         $totalLen = strlen($data_content);
         $encryptPos = 0;
         while ($encryptPos < $totalLen){
             openssl_private_encrypt(substr($data_content, $encryptPos, BAOFOO_ENCRYPT_LEN), $encryptData, $private_key);
             $encrypted .= bin2hex($encryptData);
             $encryptPos += BAOFOO_ENCRYPT_LEN;
             }
             
        return $encrypted;
	}
	
	function hex2bin($str) {
		$sbin = "";
		$len = strlen($str);
		for ($i = 0; $i < $len; $i += 2) {
			$sbin .= pack("H*", substr($str,$i,2));
		}	
		return $sbin;
	}
	
	// 公钥解密
	function decryptByPublicKey($encrypted){
		$public_key = BAOFOOENCRIPTKEY;
		$keyFile = file_get_contents($public_key);
		$public_key = openssl_get_publickey($keyFile);
		$decrypt = "";
		$totalLen = strlen($encrypted);
		$decryptPos = 0;
		while ($decryptPos < $totalLen) {
			openssl_public_decrypt($this->hex2bin(substr($encrypted, $decryptPos, BAOFOO_ENCRYPT_LEN * 8)), $decryptData, $public_key);
			$decrypt .= $decryptData;
			$decryptPos += BAOFOO_ENCRYPT_LEN * 8;
		}
		//openssl_public_decrypt($encrypted, $decryptData, $this->public_key);
		$decrypt=base64_decode($decrypt);
		return $decrypt;
	}
	
	

	public function baofo_app(){
		$pre = C('DB_PREFIX');//表前缀
		if ( $this->payConfig['baofoo']['enable'] == 0 )
		{
			ajaxmsg( "对不起，该支付方式被关闭，暂时不能使用!",0);
		}
		$pre = C('DB_PREFIX');//表前缀
		$Bconfig = require(C("APP_ROOT")."Conf/borrow_config.php");
		$amoney = $_POST['amoney'];
		$this->getPaydetails($this->uid,$amoney);
		$banks = M("member_banks b")->join("{$pre}members m on m.id=b.uid")->join("{$pre}member_info f on f.uid=b.uid")
		->field("b.bank_num,b.bank_name,m.id,m.user_phone,m.user_email,m.user_name,f.real_name,f.idcard")->where("b.uid = {$this->uid}")->find();
		$path =$_SERVER['DOCUMENT_ROOT'].'/Style/NewWeChat/CER/';
		
		$submitdata['member_id'] = $this->payConfig['baofoo']['MemberID'];//'100000178';//商户号
		$submitdata['terminal_id'] = $this->payConfig['baofoo']['TerminalID'];//'100000916';//终 端 号
		$submitdata['txn_sub_type'] = '01';
		$submitdata['biz_type'] = '0000';
		$submitdata['pay_code'] = $Bconfig['bank_n'][$banks['bank_name']];//'ICBC';//银行编码
		$submitdata['acc_no'] = $banks['bank_num'];//卡号
		$submitdata['id_card_type'] = '01';//身份证类型
		$submitdata['id_card'] = $banks['idcard'];//身份证号
		$submitdata['id_holder'] = $banks['real_name'];//持卡人姓名
		$submitdata['mobile'] = text($_POST['payphone']);//$banks['user_phone'];//银行卡预留手机号
		$submitdata['trans_id'] = 'baofoo'.date('YmdHis').mt_rand( 100000,999999);//商户订单号
		$submitdata['txn_amt'] = $amoney * 100;//交易金额
		$submitdata['trade_date'] = date('YmdHis',time());
		$submitdata['commodity_name'] = '充值';//商品名称
		$submitdata['commodity_amount'] = '1';//商品数量
		$submitdata['user_name'] = $banks['user_name'];//用户名
		$submitdata['page_url'] = $this->reapalReturn_url;//页面通知地址
		$submitdata['return_url'] = $this->reapalNotice_url;//服务器通知地址
		$submitdata['data_content'] = $this->encryptedByPrivateKey($submitdata);
		
		$p=fopen('6.txt','a+b');
		fwrite($p,print_r($submitdata,true));
		fclose($p);
		
		$submifrm['version'] = '4.0.0.0';
		$submifrm['input_charset'] = 'UTF-8';
		$submifrm['language'] = '1';
		$submifrm['member_id'] = '100000178';//商户号
		$submifrm['terminal_id'] = '100000916';//终 端 号
		$submifrm['txn_type'] = '03311';//交易金额
		$submifrm['txn_sub_type'] = '01';//订单日期
		$submifrm['data_type'] = json;
		$request['data'] = $submitdata;
		$submifrm['back_url'] = $this->reapalReturn_url;
		$request['frm'] = $submifrm;

		$this->paydetail['fee'] = getfloatvalue( $this->payConfig['baofoo']['feerate'] * $amoney/100,2);
		$this->paydetail['nid'] = $submitdata['trans_id'];
		$this->paydetail['way'] = "baofoo";
		M("member_payonline" )->add($this->paydetail);
		//提交form表单
        $url = 'https://tgw.baofoo.com/apipay/wap';
		$form = $this->create($request, $url);		//正式环境
		$json['str'] = $form;
		exit(json_encode($json));
	}

    /*
     * 微信支付开始
     * */

    public function wetch_app(){
        $pre = C('DB_PREFIX');//表前缀
        if ( $this->payConfig['watch_app']['enable'] == 0 )
        {
            // ajaxmsg( "该支付方式被关闭。",0);
        }

        $agent = strtolower($_SERVER['HTTP_USER_AGENT']); //获取http头部信息
        $is_iphone = (strpos($agent, 'iphone')) ? true : false;
        $is_android = (strpos($agent, 'android')) ? true : false;

        if($is_iphone){  //判断来源是否是iphone
            $exp = explode('micromessenger/',$agent);
            $number = explode(' ',$exp[1]);
            if($number[0] < '5.0'){
                ajaxmsg("sorry ~ 您的微信版本低于5.0,请升级",0,true,1);
            }else{	//微信内置版本检测通过。
                session('account_money',intval($_POST['account_money'])); //将传过来的金额存放在session里
                ajaxmsg("ok",1,true,1);
            }

        }elseif($is_android){  //判断来源是否是android

            unset($exp,$number);
            $exp = explode('micromessenger/',$agent);
            $number = explode('_',$exp[1]);
            if($number[0] < '6.3.7'){
                ajaxmsg("sorry ~ 您的微信版本低于5.0,请升级",0,true,1);
            }else{	//微信内置版本检测通过。
                session('account_money',intval($_POST['account_money'])); //将传过来的金额存放在session里
                ajaxmsg("ok",1,true,1);
            }
        }else{
            ajaxmsg("不支持的类型",1,true,1);
        }
    }


    /*
     * 微信支付结束
     * */



	private function getPaydetails($uid,$amoney){
		$this->paydetail['money'] = number_format($amoney, 2,".", "" );
		$this->paydetail['add_time'] = time();
		$this->paydetail['add_ip'] = get_client_ip();
		$this->paydetail['status'] = 0;
		$this->paydetail['uid'] = $uid;
	}

	
	//融宝充值********/
	public function reapal_app(){
		$pre = C('DB_PREFIX');//表前缀
		if ( $this->payConfig['reapal']['enable'] == 0 )
		{
			ajaxmsg( "该支付方式被关闭",0);
		}
		$pre = C('DB_PREFIX');//表前缀
		$amoney = $_POST['amoney'];
		$this->getPaydetails($this->uid,$amoney);
		$banks = M("member_banks b")->join("{$pre}members m on m.id=b.uid")->join("{$pre}member_info f on f.uid=b.uid")
		->field("b.bank_num,m.id,m.user_phone,m.user_email,f.real_name,f.idcard")->where("b.uid = {$this->uid}")->find();
		$generateAESKey = $this->generateAESKey();
		$submitdata['merchant_id'] = '100000000009085';
		$submitdata['order_no'] = 'reapal'.date('YmdHis').mt_rand( 100000,999999);
		$submitdata['transtime'] = time();
		$submitdata['currency'] = '156';
		$submitdata['total_fee'] = $amoney * 100;
		$submitdata['title'] =  $this->glo['web_name'].'充值';//订单名称;
		$submitdata['body'] = '睿银充值';
		$submitdata['member_id'] = $this->uid;
		$submitdata['terminal_type'] = 'mobile';
		$submitdata['terminal_info'] = 'IMEI';
		$submitdata['member_ip'] = get_client_ip();
		$submitdata['seller_email'] = '492215340@qq.com';//签约融宝支付账号或卖家收款融宝支付帐户;
		$submitdata['notify_url'] = $this->reapalNotice_url;//异步
		$submitdata['return_url'] = $this->reapalReturn_url;//同步
		$submitdata['payment_type'] = '2';
		$submitdata['pay_method'] = 'bankPay';
		//$submitdata['sign'] = $this->createSign($submitdata,$this->payConfig['reapal']['MD5key']);
		$submitdata['sign'] = $this->createSign($submitdata,'48958gg3a25eeabg5fdgb4d95g93d4a4gfeb92c4g02ef276518da56cb9c7a809');
		//unset( $this->paydetail['bank'] );
		$this->paydetail['fee'] = getfloatvalue( $this->payConfig['reapal']['feerate'] * $amoney/100,2);
		$this->paydetail['nid'] = $submitdata['order_no'];
		$this->paydetail['way'] = "reapal";
		M("member_payonline" )->add( $this->paydetail );
		
		//$request = array();
		//$request['merchant_id'] = $submitdata['merchant_id'];
		$request['encryptkey'] = $this->RSAEncryptkey($generateAESKey,REAPALPUBLICKEY);
		$request['data'] = $this->AESEncryptRequest($generateAESKey,$submitdata);
		//$url = 'http://api.reapal.com/mobile/portal'.'?';
        $url = 'http://testapi.reapal.com/mobile/portal'.'?';
		$form = $this->reapalcreate($request, $url);		//正式环境
		$json['str'] = $form;
		exit(json_encode($json));
	}

	//////////////////////////////////////////融宝支付接口处理方法开始    qin2014-08-25/////////////////////////////
	//异步
	public function reapalpaynotifyurl(){
		$apikey = '48958gg3a25eeabg5fdgb4d95g93d4a4gfeb92c4g02ef276518da56cb9c7a809';
		$encryptkey=$_POST['encryptkey'];
		$merchantPrivateKey = REAPALENCRIPTKEY;
		$data=$_POST['data'];
		$key = $this->RSADecryptkey($encryptkey,$merchantPrivateKey);
		$dataTemp = $this->AESDecryptResponse($key,$data);
		$dataArray = json_decode($dataTemp,true);

		$moneyarrsign = $dataArray['sign'];
		unset($dataArray['sign']);
		ksort($dataArray);
		//dump($dataArray);
		$string = '';
		foreach($dataArray as $k => $v){
			$string.= $k.'='.$v.'&';
		}
		$msg = substr ( $string,0,(strlen ( $string )-1));
		$sign = md5($msg.$apikey);

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


	

	/**
	 * 通过RSA，使用融宝公钥，加密本次请求的AESKey
	 *
	 * @return string
	 */
	function RSADecryptkey($encryptKey,$merchantPrivateKey){
		$private_key= file_get_contents($merchantPrivateKey);
		$pi_key =  openssl_pkey_get_private($private_key);//这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
		openssl_private_decrypt(base64_decode($encryptKey),$decrypted,$pi_key);//私钥解密
		return $decrypted;
	}

	function AESDecryptResponse($encryptKey,$data){
		return $this->decrypt($data,$encryptKey);

	}

	function decrypt($sStr, $sKey) {
		$decrypted= mcrypt_decrypt(
				MCRYPT_RIJNDAEL_128,
				$sKey,
				base64_decode($sStr),
				MCRYPT_MODE_ECB
		);

		$dec_s = strlen($decrypted);
		$padding = ord($decrypted[$dec_s-1]);
		$decrypted = substr($decrypted, 0, -$padding);
		return $decrypted;
	}

	//签名函数
	function createSign ($paramArr,$apiKey) {
		global $appSecret;
		$sign = $appSecret;
		ksort($paramArr);
		foreach ($paramArr as $key => $val) {
			if ($key != '' && $val != '') {
				$sign .= $key.'='.$val.'&';
			}
		}

		$sign = substr ( $sign,0,(strlen ( $sign )-1));
		$sign.=$appSecret;
		//echo $sign;
		$sign = md5($sign.$apiKey);
		return $sign;
	}

	/******融宝充值********/
	/**
	 * 生成一个随机的字符串作为AES密钥
	 *
	 * @param number $length
	 * @return string
	 */
	function generateAESKey($length=16){
		$baseString = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$AESKey = '';
		$_len = strlen($baseString);
		for($i=1;$i<=$length;$i++){
			$AESKey .= $baseString[rand(0, $_len-1)];
		}
		return $AESKey;
	}

	function getPublicKey($cert_path) {
		$pkcs12 = file_get_contents ( $cert_path );
		return $pkcs12;
	}

	/**
	 * 通过RSA，使用融宝公钥，加密本次请求的AESKey
	 *
	 * @return string
	 */
	function RSAEncryptkey($encryptKey,$reapalPublicKey){
		$public_key= $this->getPublicKey($reapalPublicKey);
		$pu_key = openssl_pkey_get_public($public_key);//这个函数可用来判断公钥是否是可用的
		openssl_public_encrypt($encryptKey,$encrypted,$pu_key);//公钥加密
		return base64_encode($encrypted);
	}

	/**
	 * 通过AES加密请求数据
	 *
	 * @param array $query
	 * @return string
	 */
	function AESEncryptRequest($encryptKey,array $query){

		return $this->encrypt(json_encode($query),$encryptKey);

	}

	function encrypt($input, $key) {
		$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
		$input = $this->pkcs5_pad($input, $size);
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, $key, $iv);
		$data = mcrypt_generic($td, $input);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		$data = base64_encode($data);
		return $data;
	}

	function pkcs5_pad ($text, $blocksize) {
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);
	}

	private function create($data,$submitUrl){
		header("Content-Type:text/html;charset=UTF-8");
		$inputstr = "";
		
		foreach($data['frm'] as $k=>$vs){
			$inputstr .= '
			<input type="hidden"  id="'.$k.'" name="'.$k.'" value="'.$vs.'"/>
			';
		}

		
		$inputstr .= '
		<input type="hidden"  id="data_content" name="data_content" value="'.$data['data']['data_content'].'"/>
		';
		
		$form = '
		<form action="'.$submitUrl.'" name="form1" id="frm" method="POST">
		';
		$form.=	$inputstr;
		$form.=	'
		</form>
		';
		//file_put_contents('1.txt',$form);
		$html = '
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>请不要关闭页面,支付跳转中.....</title>
		</head>
		<body>
		';
		//dump($form);exit;
		$html.=	$form;
		$html.=	'
		<script type="text/javascript">
		document.getElementById("frm").submit();
		</script>
		';
		$html.= '
		</body>
		</html>
		';
		return $html;
	}
	
	private function reapalcreate($data,$submitUrl){
		header("Content-Type:text/html;charset=UTF-8");
		$inputstr = "";
		foreach($data as $key=>$v){
			$inputstr .= '
			<input type="hidden"  id="'.$key.'" name="'.$key.'" value="'.$v.'"/>
			';
		}
	
		$form = '
		<form action="'.$submitUrl.'" name="sendOrder" id="sendOrder" method="POST">
		';
		$form.=	$inputstr;
		$form.=	'
		</form>
		';
	
		$html = '
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>请不要关闭页面,支付跳转中.....</title>
		</head>
		<body>
		';
		//dump($form);exit;
		$html.=	$form;
		$html.=	'
		<script type="text/javascript">
		document.getElementById("sendOrder").submit();
		</script>
		';
		$html.= '
		</body>
		</html>
		';
		return $html;
	}
}
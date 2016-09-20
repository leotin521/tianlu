<?php
require APP_PATH."Common/Lib.php";
require APP_PATH."Common/DataSource.php";
//require APP_PATH."Common/Refusedcc.php";//防御CC攻击  fan  2013-11-28
function acl_get_key($model = false, $action = false){
	empty($model)?$model=strtolower(MODULE_NAME):$model=strtolower($model);
	empty($action)?$action=strtolower(ACTION_NAME):$action=strtolower($action);
	
	$keys = array($model,'data','eqaction_'.$action);
	require C('APP_ROOT')."Common/acl.inc.php";
	$inc = $acl_inc;
	
	$array = array();
	foreach($inc as $key => $v){
			if(isset($v['low_leve'][$model])){
				$array = $v['low_leve'];
				continue;
			}
	}//找到acl.inc中对当前模块的定义的数组
	
	$num = count($keys);
	$num_last = $num - 1;
	$this_array_0 = &$array;
	$last_key = $keys[$num_last];
	
	for ($i = 0; $i < $num_last; $i++){
		$this_key = $keys[$i];
		$this_var_name = 'this_array_' . $i;
		$next_var_name = 'this_array_' . ($i + 1);        
		if (!array_key_exists($this_key, $$this_var_name)) {            
			break;       
		}        
		$$next_var_name = &${$this_var_name}[$this_key];    
	}    
	/*取得条件下的数组  ${$next_var_name}得到data数组 $last_key即$keys = array($model,'data','eqaction_'.$action);里面的'eqaction_'.$action,所以总的组成就是，在acl.inc数组里找到键为$model的数组里的键为data的数组里的键为'eqaction_'.$action的值;*/
	$actions = ${$next_var_name}[$last_key];//这个值即为当前action的别名,然后用别名与用户的权限比对,如果是带有参数的条件则$actions是数组，数组里有相关的参数限制
	if(is_array($actions)){
		foreach($actions as $key_s => $v_s){
			$ma = true;
			if(isset($v_s['POST'])){
				foreach($v_s['POST'] as $pkey => $pv){
					switch($pv){
						case 'G_EMPTY';//必须为空
							if( isset($_POST[$pkey]) && !empty($_POST[$pkey]) ) $ma = false;
						break;
					
						case 'G_NOTSET';//不能设置
							if( isset($_POST[$pkey]) ) $ma = false;
						break;
					
						case 'G_ISSET';//必须设置
							if( !isset($_POST[$pkey]) ) $ma = false;
						break;
					
						default;//默认
							if( !isset($_POST[$pkey]) || strtolower($_POST[$pkey]) != strtolower($pv) ) $ma = false;
						break;
					}
				}
			}
			
			if(isset($v_s['GET'])){
				foreach($v_s['GET'] as $pkey => $pv){
					switch($pv){
						case 'G_EMPTY';//必须为空
							if( isset($_GET[$pkey]) && !empty($_GET[$pkey]) ) $ma = false;
						break;
					
						case 'G_NOTSET';//不能设置
							if( isset($_GET[$pkey]) ) $ma = false;
						break;
					
						case 'G_ISSET';//必须设置
							if( !isset($_GET[$pkey]) ) $ma = false;
						break;
					
						default;//默认
							if( !isset($_GET[$pkey]) || strtolower($_GET[$pkey]) != strtolower($pv) ) $ma = false;
						break;
					}
					
				}
			}
			if($ma)	return $key_s;
			else $actions="0";
		}//foreach
	}else{
		return $actions;
	}
}
//////////////////////////////////// 第三方支付--移动支付专用 开始 fan 2014-06-07 ////////////////////////////
//* 移动支付使用该方法
//获取客户端ip地址
//注意:如果你想要把ip记录到服务器上,请在写库时先检查一下ip的数据是否安全.
//*
function getIp() {
        if (getenv('HTTP_CLIENT_IP')) {
				$ip = getenv('HTTP_CLIENT_IP'); 
		}
		elseif (getenv('HTTP_X_FORWARDED_FOR')) { //获取客户端用代理服务器访问时的真实ip 地址
				$ip = getenv('HTTP_X_FORWARDED_FOR');
		}
		elseif (getenv('HTTP_X_FORWARDED')) { 
				$ip = getenv('HTTP_X_FORWARDED');
		}
		elseif (getenv('HTTP_FORWARDED_FOR')) {
				$ip = getenv('HTTP_FORWARDED_FOR'); 
		}
		elseif (getenv('HTTP_FORWARDED')) {
				$ip = getenv('HTTP_FORWARDED');
		}
		else if(!empty($_SERVER["REMOTE_ADDR"])){
				$cip = $_SERVER["REMOTE_ADDR"];  
		}else{
				$cip = "unknown";  
		}
		return $ip;
}

	//移动支付MD5方式签名
	  function MD5sign($okey,$odata){
	  		$signdata=hmac("",$odata);			     
	  		return hmac($okey,$signdata);
	  }
	  
	  /*function hmac ($key, $data){
		  $key = iconv('gb2312', 'utf-8', $key);
		  $data = iconv('gb2312', 'utf-8', $data);
		  $b = 64;
		  if (strlen($key) > $b) {
		  		$key = pack("H*",md5($key));
		  }
		  $key = str_pad($key, $b, chr(0x00));
		  $ipad = str_pad('', $b, chr(0x36));
		  $opad = str_pad('', $b, chr(0x5c));
		  $k_ipad = $key ^ $ipad ;
		  $k_opad = $key ^ $opad;
		  return md5($k_opad . pack("H*",md5($k_ipad . $data)));
      }*/ 
	  
	  function HmacMd6($data, $key) {
    // RFC 2104 HMAC implementation for php.
    // Creates an md5 HMAC.
    // Eliminates the need to install mhash to compute a HMAC
    // Hacked by Lance Rushing(NOTE: Hacked means written)

    //需要配置环境支持iconv，否则中文参数不能正常处理
   //$key = iconv("GB2312", "UTF-8", $key);
    ///$data = iconv("GB2312", "UTF-8", $data);

    $b = 64; // byte length for md5
    if (strlen($key) > $b) {
        $key = pack("H*", md5($key));
    }
    $key = str_pad($key, $b, chr(0x00));
    $ipad = str_pad('', $b, chr(0x36));
    $opad = str_pad('', $b, chr(0x5c));
    $k_ipad = $key ^ $ipad;
    $k_opad = $key ^ $opad;

    return md5($k_opad . pack("H*", md5($k_ipad . $data)));
}
//////////////////////////////////// 第三方支付--移动支付专用 结束 fan 2014-06-07 ////////////////////////////

/***************融宝支付start***********************/
	function build_mysign($sort_array,$key,$sign_type = "MD5") 
	{
	    $prestr = create_linkstring($sort_array);     	//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
	    $prestr = $prestr.$key;							//把拼接后的字符串再与安全校验码直接连接起来
	    $mysgin = sign_reapal($prestr,$sign_type);			    //把最终的字符串签名，获得签名结果
	    return $mysgin;
	}	

	/**
	    *把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		*$array 需要拼接的数组
		*return 拼接完成以后的字符串
	*/
	function create_linkstring($array) 
	{
	    $arg  = "";
	    while (list ($key, $val) = each ($array)) 
		{
	        $arg.=$key."=".$val."&";
	    }
	    $arg = substr($arg,0,count($arg)-2);		     //去掉最后一个&字符
	    return $arg;
	}

	/**
	    *除去数组中的空值和签名参数
		*$parameter 签名参数组
		*return 去掉空值与签名参数后的新签名参数组
	 */
	function para_filter($parameter) 
	{
	    $para = array();
	    while (list ($key, $val) = each ($parameter)) 
		{
	        if($key == "sign" || $key == "sign_type" || $val == "")
			{
				continue;
			}
	        else
			{
				$para[$key] = $parameter[$key];
			}
	    }
	    return $para;
	}

	/**对数组排序
		*$array 排序前的数组
		*return 排序后的数组
	 */
	function arg_sort($array) 
	{
	    ksort($array);
	    reset($array);
	    return $array;
	}

	/**签名字符串
		*$prestr 需要签名的字符串
		*return 签名结果
	 */
	function sign_reapal($prestr,$sign_type) 
	{
	    $sign='';
	    if($sign_type == 'MD5') 
		{
	        $sign = md5($prestr);
	    }
		else 
		{
	        die("融宝支付暂不支持".$sign_type."类型的签名方式");
	    }
	    return $sign;
	}

	/******************融宝支付end*********************/
/**
 * 用户身份证认证
 * @param string $name
 * @param stirng $passwd
 * @return multitype:number string |multitype:number Ambigous <string>
 */
function real_name_auth_id5($name, $id){

	if(empty($name) || empty($id)){
		return array('errcode'=> 0, 'errmsg'=> '请输入认证资料');
	}
	try{
		$c = FS('Webconfig/id5');
		
		$client = new SoapClient($c['account_api']);
		$data = new stdClass();
		$data->Name = $name;
		$data->IDNumber = $id;
		
			

		$auth = new stdClass();
		$auth->UserName = $c['account_name'];
		$auth->Password = $c['account_password'];
		$json = $client->SimpleCheckByJson(array('request'=> json_encode($data),
												 'cred'=> json_encode($auth)))->SimpleCheckByJsonResult;
		$result = json_decode($json);
		$errcode = array(
				'c100'=> '调用成功',
				'c-31'=> '余额不足',
				'c-53'=> '账号冻结/过期/权限不足',
				'c-60'=> '参数为空或格式错误',
				'c-71'=> '用户名/密码错误',
				'c-72'=> 'IP受限',
				'c-90'=> '服务器错误',
		);

		$index = 'c' . $result->ResponseCode;
		if($result->ResponseText=="成功"){
			return $result->Identifier->Result;//认证结果
		}else{
			return $result->ResponseText;
		}
	}catch (Exception $e){
		echo '<pre>';
		print_r($e);
	}
}
/**
 * 京东快捷支付 start
 */
/************ConfigUtil**************/
function get_val_by_key($key,$wepay) {
    #	$settings = array();
    #	$settings = load_ini( $wepay );
    return get( "wepay." . $key, $wepay);
}
function get_trade_num($wepay) {
    return get_val_by_key('merchantNum',$wepay) . get_Millisecond();
}
function get_Millisecond() {
    list ( $s1, $s2 ) = explode ( ' ', microtime () );
    return ( float ) sprintf ( '%.0f', (floatval ( $s1 ) + floatval ( $s2 )) * 1000 );
}
function get($var,$result) {
    $var = explode ( '.', $var );

    foreach ( $var as $key ) {
        if (! isset ( $result [$key] )) {
            return false;
        }

        $result = $result [$key];
    }

    return $result;
}
function load_new($file) {
    trigger_error ( 'Not yet implemented', E_USER_ERROR );
}
function load_ini($file) {
    if (file_exists ( $file ) == true) {
        return parse_ini_file( $file, true );
    }
}
/************ConfigUtil**************/

/************RSAUtils**************/
function encryptByPrivateKey($data) {
    $pi_key =  openssl_pkey_get_private(file_get_contents(JDPAY_PATH.'seller_rsa_private_key.pem'));//这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
    $encrypted="";
    openssl_private_encrypt($data,$encrypted,$pi_key,OPENSSL_PKCS1_PADDING);//私钥加密
    $encrypted = base64_encode($encrypted);//加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url传输时要注意base64编码是否是url安全的
    return $encrypted;
}
function decryptByPublicKey($data) {
    $pu_key =  openssl_pkey_get_public(file_get_contents(JDPAY_PATH.'wy_rsa_public_key.pem'));//这个函数可用来判断公钥是否是可用的，可用返回资源id Resource id
    $decrypted = "";
    $data = base64_decode($data);
    openssl_public_decrypt($data,$decrypted,$pu_key);//公钥解密
    return $decrypted;
}
/************RSAUtils**************/

/************SignUtil*************/
function signWithoutToHex($params) {

    $unSignKeyList = array (
        "merchantSign",
        "version",
        "successCallbackUrl",
        "forPayLayerUrl"
    );
    ksort($params);
    $sourceSignString = signString( $params, $unSignKeyList );
    error_log($sourceSignString, 0);
    $sha256SourceSignString = hash( "sha256", $sourceSignString,true);
    error_log($sha256SourceSignString, 0);
    return encryptByPrivateKey($sha256SourceSignString);
}
function sign($params) {
    $unSignKeyList = array (
        "merchantSign",
        "version",
        "successCallbackUrl",
        "forPayLayerUrl"
    );
    ksort($params);
    $sourceSignString = signString( $params, $unSignKeyList );
    error_log($sourceSignString, 0);
    $sha256SourceSignString = hash( "sha256", $sourceSignString);
    error_log($sha256SourceSignString, 0);
    return encryptByPrivateKey($sha256SourceSignString);
}
function signString($params, $unSignKeyList) {

    // 拼原String
    $sb = "";
    // 删除不需要参与签名的属性
    foreach ( $params as $k => $arc ) {
        for($i = 0; $i < count ( $unSignKeyList ); $i ++) {

            if ($k == $unSignKeyList [$i]) {
                unset ( $params [$k] );
            }
        }
    }

    foreach ( $params as $k => $arc ) {

        $sb = $sb . $k . "=" . ($arc == null ? "" : $arc) . "&";
    }
    // 去掉最后一个&
    $sb = substr ( $sb, 0, - 1 );

    return $sb;
}
/************SignUtil*************/

/************WebAsynNotificationCtrl*************/
function xml_to_array($xml) {
    $array = ( array ) (simplexml_load_string ( $xml ));
    foreach ( $array as $key => $item ) {
        $array [$key] = struct_to_array ( ( array ) $item );
    }
    return $array;
}
function struct_to_array($item) {
    if (! is_string ( $item )) {
        $item = ( array ) $item;
        foreach ( $item as $key => $val ) {
            $item [$key] = struct_to_array ( $val );
        }
    }
    return $item;
}
function generateSign($data, $md5Key) {
    $sb = $data ['VERSION'] [0] . $data ['MERCHANT'] [0] . $data ['TERMINAL'] [0] . $data ['DATA'] [0] . $md5Key;

    return md5 ( $sb );
}
/************WebAsynNotificationCtrl*************/

/************DesUtils*************/
function decrypt_jdpay($encrypted,$key) {
    $encrypted = base64_decode ($encrypted);
    $key = base64_decode ($key);
    $key = pad2Length ( $key, 8 );
    $td = mcrypt_module_open ( 'des', '', 'ecb', '' );
    // 使用MCRYPT_DES算法,cbc模式
    $iv = @mcrypt_create_iv ( mcrypt_enc_get_iv_size ( $td ), MCRYPT_RAND );
    $ks = mcrypt_enc_get_key_size ( $td );
    @mcrypt_generic_init ( $td, $key, $iv );
    // 初始处理
    $decrypted = mdecrypt_generic ( $td, $encrypted );
    // 解密
    mcrypt_generic_deinit ( $td );
    // 结束
    mcrypt_module_close ( $td );
    $y = pkcs5_unpad ( $decrypted );
    return $y;
}
function pad2Length($text, $padlen) {
    $len = strlen ( $text ) % $padlen;
    $res = $text;
    $span = $padlen - $len;
    for($i = 0; $i < $span; $i ++) {
        $res .= chr ( $span );
    }
    return $res;
}
function pkcs5_unpad($text) {
    $pad = ord ( $text {strlen ( $text ) - 1} );
    if ($pad > strlen ( $text ))
        return false;
    if (strspn ( $text, chr ( $pad ), strlen ( $text ) - $pad ) != $pad)
        return false;
    return substr ( $text, 0, - 1 * $pad );
}
/************DesUtils*************/	

?>
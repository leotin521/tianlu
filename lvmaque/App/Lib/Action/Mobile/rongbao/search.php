<?php
header("Content-Type:text/html;charset=UTF-8");
require_once 'util.php'; 
require_once 'config.php'; 

//参数数组
$paramArr = array(
     'merchant_id' => '100000000009085',
     'order_no' =>'123456',
     
);
//生成签名
$sign = createSign($paramArr,$apiKey);

$paramArr['sign'] = $sign;
//生成AESkey
$generateAESKey = generateAESKey();
$request = array();
$request['merchant_id'] = $merchant_id;
//加密key
$request['encryptkey'] = RSAEncryptkey($generateAESKey,$reapalPublicKey);
//加密数据
$request['data'] = AESEncryptRequest($generateAESKey,$paramArr);
//访问服务
$url = $apiUrl.'/fast/search';
//echo $url,"\n";

$result = sendHttpRequest($request,$url);
//echo $url.$result;
//print_r($result);

$response = json_decode($result,true);
$encryptkey = RSADecryptkey($response['encryptkey'],$merchantPrivateKey);
echo $encryptkey,"\n";
echo AESDecryptResponse($encryptkey,$response['data']);



?>
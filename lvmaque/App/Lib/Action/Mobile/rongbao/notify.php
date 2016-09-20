<?php
header("Content-Type:text/html;charset=UTF-8");
require_once 'util.php'; 

$merchant_id = '100000000009085';
// 商户私钥
$merchantPrivateKey = 'F:\\cert\\itrus001_pri.pem';
// 商户公钥
$merchantPublicKey = 'F:\\cert\\itrus001.pem';
// 融宝公钥
$reapalPublicKey = 'F:\\cert\\itrus001.pem';

$encryptkey=$_POST['encryptkey'];
$data=$_POST['data'];


$key = RSADecryptkey($encryptkey,$merchantPrivateKey);
echo $key,"\n";
echo AESDecryptResponse($key,$data);

?>
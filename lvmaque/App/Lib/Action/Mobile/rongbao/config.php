<?php
$path = $_SERVER['DOCUMENT_ROOT'];
$path .=  "/App/Lib/Action/Mobile/rongbao/cert/";
$merchant_id = '100000000009085';
// 商户私钥 客户自己生成 
$merchantPrivateKey = $path.'itrus001_pri.pem';
// 商户公钥 客户自己生成 融宝需要cer,服务器需要pem
$merchantPublicKey = $path.'itrus001.pem';
// 融宝公钥  不需要替换 融宝只有一套公私钥
$reapalPublicKey = $path.'itrus001.pem';
// APIKEy
$apiKey = '48958gg3a25eeabg5fdgb4d95g93d4a4gfeb92c4g02ef276518da56cb9c7a809';
// APIUrl
$apiUrl = 'http://testapi.reapal.com';
// 签约邮箱地址
$apiEmail = '4922135340@qq.com';

?>
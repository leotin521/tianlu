<?php

/* 返回验证页面
 * 验证方式：交易返回接口采用Md5摘要验证(pResHashArithmetic=12)
 * 验证过程: 明文信息: MD5原文=base64_decode(pICPayRes)+本地密钥，验证与pICPayResHashValue是否匹配
 *
 * 注意:由于可能多次返回支付结果,故需要商户的系统有能力处理多次返回的情况.
 */



/**************************返回的XML格式**************************************************
 * <?xml version="1.0" encoding="UTF-8"\?>
 * <IPSResRoot>
 *   <ICPay>
 *     <Version>2.0.0</Version>
 *     <StandardPaymentRes>
 *       <pIPSOrderNum><![CDATA[NT2009101000000001]]></pIPSOrderNum>
 *       <pMerchantOrderNum><![CDATA[MER0000001]]></pMerchantOrderNum>
 *       <pLanguage><![CDATA[GB]]></pLanguage>
 *       <pOrderCurrency><![CDATA[CNY]]></pOrderCurrency>
 *       <pOrderAmount><![CDATA[0.02]]></pOrderAmount>
 *       <pDisplayAmount><![CDATA[$1.00]]></pDisplayAmount>
 *       <pIPSTransactionTime><![CDATA[20091010121022]]></pIPSTransactionTime>
 *       <pMerchantTransactionTime><![CDATA[20091009121022]]></pMerchantTransactionTime>
 *       <pResultCode><![CDATA[Y]]></pResultCode>
 *       <pBankMessage><![CDATA[Success]]></pBankMessage>
 *       <pAttach><![CDATA[ABC]]></pAttach>
 *     </StandardPaymentRes>
 *   </ICPay>
 * </IPSResRoot>
 *
 ******************************************************************************************/


//接收返回数据
$pMerchantCode = $_REQUEST['pMerchantCode'];
$pICPayResHashValue = $_REQUEST['pICPayResHashValue'];

//对pICPayRes进行base64解码,得到支付信息XML字符串
$pICPayRes = base64_decode($_REQUEST['pICPayRes']); 

//商户证书：IPS商户后台merchant.ips.com.cn下载证书内字符串。
$pMerchantCert = trim('00518847228994856151214381286034373160268923638865209509623755128452179689329064232083487454640280528679651027955842303507571503');

//md5签名验证
if(md5($pICPayRes . $pMerchantCert) == $pICPayResHashValue)
{
  //解析XML
  $xml = new DOMDocument();
  $xml->loadXML($pICPayRes);

  //支付结果
  $pResultCode = $xml->getElementsByTagName('pResultCode')->item(0)->nodeValue;
  
  //IPS订单号
  $pIPSOrderNum = $xml->getElementsByTagName('pIPSOrderNum')->item(0)->nodeValue;

  //银行返回信息
  $pBankMessage = $xml->getElementsByTagName('pBankMessage')->item(0)->nodeValue;

  //判断交易是否成功
  if($pResultCode == 'Y')
  {
    /**********************************************************************************
    //比较返回的订单号和金额与您数据库中的金额是否相符（可选）

    //订单号
    $pMerchantOrderNum = $xml->getElementsByTagName('pMerchantOrderNum')->item(0)->nodeValue;

    //支付金额
    $pOrderAmount = $xml->getElementsByTagName('pOrderAmount')->item(0)->nodeValue;

    //显示金额
    $pDisplayAmount = $xml->getElementsByTagName('pDisplayAmount')->item(0)->nodeValue;

    if(相等)
    {
      echo '交易成功，请处理您的数据库';
      exit();
    }
    else
    {
      echo "从IPS返回的数据和本地记录的不符合，失败！";
      exit();
    }
    **********************************************************************************/

    echo '交易成功，请处理您的数据库';
    exit();
  }
  else
  {
    echo "交易失败:$pBankMessage";
    exit();
  }
}
else
{
  echo '签名验证失败';
  exit();
}
?>
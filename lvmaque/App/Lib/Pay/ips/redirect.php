<?php
//IPS支付接口版本号(*)：指定使用接口版本号，请固定使用“2.0.0”
$Version = '2.0.0';

//商户号(*)：IPS所提供的商户号。
$pMerchantCode = trim($_POST['pMerchantCode']);

//商户证书(*)：IPS商户后台merchant.ips.com.cn下载证书内字符串。
$pMerchantKey = trim($_POST['pMerchantKey']);

//提交地址(*): 
if($_POST['test'] == "1")
{
	$form_url = "https://pay.ips.net.cn/icpay/standard/payment.aspx";	//测试提交地址
}
else
{
	$form_url = "https://pay.ips.com.cn/icpay/standard/payment.aspx";	//正式提交地址
}

//订单交易日期(*): 客户的购物日期,日期格式：YYYYMMDDHHMISS
$pMerchantTransactionTime = $_POST['pMerchantTransactionTime'];

//商户订单编号(*): 商户提供的购物信息唯一的订单编号,长度30
$pMerchantOrderNum = $_POST['pMerchantOrderNum'];

//界面语言(*)：客户选择的支付界面显示的语言代码。EN---英文（缺省）、GB---GB中文、BIG5---BIG5中文、JP---日文、FR---法文
$pLanguage = $_POST['pLanguage'];

//支付币种(*)：交易的币种代码
$pOrderCurrency = $_POST['pOrderCurrency'];

//订单实际金额(*)：购物实际总额，保留二位小数.注意:请将其它币种金额转为CNY金额，否则支付金额有误
$pOrderAmount = number_format($_POST['pOrderAmount'], 2); //金额*汇率(0.10*6.8282)

//订单显示金额：存放商户希望在IPS 平台上选择银行界面上显示给持卡人的金额。
$pDisplayAmount = $_POST['pDisplayAmount'];

//商品名称
$pProductName = $_POST['pProductName'];

//商品描述
$pProductDescription = $_POST['pProductDescription'];

//商户数据包：如果商户需要在交易结束后获取一些提交时参数中未定义的自定义内容，可以通过这个参数提交
$pAttach = $_POST['pAttach'];

//商户返回URL(*)：客户支付完成后，环迅支付会把支付结果信息发送到此参数提供的地址。
$pSuccessReturnUrl = $_POST['pSuccessReturnUrl'];

//预留参数1
$pFailReturnUrl = '';

//预留参数2
$pErrorReturnUrl = '';

//商户Server to Server返回地址(*)
$pS2SReturnUrl = $_POST['pS2SReturnUrl'];

//交易返回接口加密方式(*)：设定环迅支付将交易结果返回时使用的防篡改策略。12-交易返回采用Md5的摘要认证方式
$pResHashArithmetic = $_POST['pResHashArithmetic'];

//交互方式(*)：0-浏览器模式  1-浏览器+服务器模式
$pResType = $_POST['pResType'];

//反欺诈验证(*)：1-启用(默认)
$pEnableFraudGuard = $_POST['pEnableFraudGuard'];

//将商户提交信息按接口文档说明写成XML格式(*)
$pICPayReq = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><IPSReqRoot><ICPay><Version><![CDATA[$Version]]></Version><StandardPaymentReq><pMerchantOrderNum><![CDATA[$pMerchantOrderNum]]></pMerchantOrderNum><pOrderAmount><![CDATA[$pOrderAmount]]></pOrderAmount><pDisplayAmount><![CDATA[$pDisplayAmount]]></pDisplayAmount><pMerchantTransactionTime><![CDATA[$pMerchantTransactionTime]]></pMerchantTransactionTime><pOrderCurrency><![CDATA[$pOrderCurrency]]></pOrderCurrency><pLanguage><![CDATA[$pLanguage]]></pLanguage><pSuccessReturnUrl><![CDATA[$pSuccessReturnUrl]]></pSuccessReturnUrl><pFailReturnUrl><![CDATA[$pFailReturnUrl]]></pFailReturnUrl><pErrorReturnUrl><![CDATA[$pErrorReturnUrl]]></pErrorReturnUrl><pS2SReturnUrl><![CDATA[$pS2SReturnUrl]]></pS2SReturnUrl><pResType><![CDATA[$pResType]]></pResType><pResHashArithmetic><![CDATA[$pResHashArithmetic]]></pResHashArithmetic><pProductName><![CDATA[$pProductName]]></pProductName><pProductDescription><![CDATA[$pProductDescription]]></pProductDescription><pAttach><![CDATA[$pAttach]]></pAttach><pEnableFraudGuard><![CDATA[$pEnableFraudGuard]]></pEnableFraudGuard></StandardPaymentReq></ICPay></IPSReqRoot>";

//进行base64_encode(*)
$pICPayReqB64 = base64_encode($pICPayReq);

//签名验证串(*)：MD5原文=商户提交信息+商户证书
$pICPayReqHashValue = md5($pICPayReq . $pMerchantKey);

//反欺诈验证信息：
//持卡人信息
$pAccID             =  '';
$pAccEMail          =  '';
$pAccLoginIP        =  '';
$pAccLoginDate      =  '';
$pAccLoginDevice    =  '';
$pAccRegisterDate   =  '';
$pAccRegisterDevice =  '';
$pAccRegisterIP     =  '';

//帐单信息
$pBillFName         =  $_POST['pBillFName'];
$pBillMName         =  $_POST['pBillMName'];
$pBillLName         =  $_POST['pBillLName'];
$pBillStreet        =  $_POST['pBillStreet'];
$pBillCity          =  $_POST['pBillCity'];
$pBillState         =  $_POST['pBillState'];
$pBillCountry       =  strtolower($_POST['pBillCountry']); //请使用国家/地区的小写二字英文代码
$pBillZIP           =  $_POST['pBillZIP'];
$pBillEmail         =  $_POST['pBillEmail'];
$pBillPhone         =  $_POST['pBillPhone'];

//产品信息
$pProductData1      =  $_POST['pProductData1'];
$pProductData2      =  $_POST['pProductData2'];
$pProductData3      =  $_POST['pProductData3'];
$pProductData4      =  $_POST['pProductData4'];
$pProductData5      =  $_POST['pProductData5'];
$pProductData6      =  $_POST['pProductData6'];
$pProductType       =  $_POST['pProductType'];

//货运信息
$pShipFName         =  $_POST['pShipFName'];
$pShipMName         =  $_POST['pShipMName'];
$pShipLName         =  $_POST['pShipLName'];
$pShipStreet        =  $_POST['pShipStreet'];
$pShipCity          =  $_POST['pShipCity'];
$pShipState         =  $_POST['pShipState'];
$pShipCountry       =  strtolower($_POST['pShipCountry']); //请使用国家/地区的小写二字英文代码
$pShipZIP           =  $_POST['pShipZIP'];
$pShipEmail         =  $_POST['pShipEmail'];
$pShipPhone         =  $_POST['pShipPhone'];

//使用接口版本号(*):请固定使用“1.0.0”
$fVersion = '1.0.0';

//指定使用验证规则库的编号,默认为1
$pCheckRuleBaseID = '1';

//反欺诈信息按接口文档中格式写成XML(*)
$pAFSReq = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><IPSReqRoot><AFS><Version><![CDATA[$fVersion]]></Version><cBasicParameters><pCheckRuleBaseID><![CDATA[$pCheckRuleBaseID]]></pCheckRuleBaseID></cBasicParameters><StandardPaymentReq><cBillParameters><pBillFName><![CDATA[$pBillFName]]></pBillFName><pBillMName><![CDATA[$pBillMName]]></pBillMName><pBillLName><![CDATA[$pBillLName]]></pBillLName><pBillStreet><![CDATA[$pBillStreet]]></pBillStreet><pBillCity><![CDATA[$pBillCity]]></pBillCity><pBillState><![CDATA[$pBillState]]></pBillState><pBillCountry><![CDATA[$pBillCountry]]></pBillCountry><pBillZIP><![CDATA[$pBillZIP]]></pBillZIP><pBillEmail><![CDATA[$pBillEmail]]></pBillEmail><pBillPhone><![CDATA[$pBillPhone]]></pBillPhone></cBillParameters><cShipParameters><pShipFName><![CDATA[$pShipFName]]></pShipFName><pShipMName><![CDATA[$pShipMName]]></pShipMName><pShipLName><![CDATA[$pShipLName]]></pShipLName><pShipStreet><![CDATA[$pShipStreet]]></pShipStreet><pShipCity><![CDATA[$pShipCity]]></pShipCity><pShipState><![CDATA[$pShipState]]></pShipState><pShipCountry><![CDATA[$pShipCountry]]></pShipCountry><pShipZIP><![CDATA[$pShipZIP]]></pShipZIP><pShipEmail><![CDATA[$pShipEmail]]></pShipEmail><pShipPhone><![CDATA[$pShipPhone]]></pShipPhone></cShipParameters><cProductParameters><pProductType><![CDATA[$pProductType]]></pProductType><pProductName><![CDATA[$pProductName]]></pProductName><pProductData1><![CDATA[$pProductData1]]></pProductData1><pProductData2><![CDATA[$pProductData2]]></pProductData2><pProductData3><![CDATA[$pProductData3]]></pProductData3><pProductData4><![CDATA[$pProductData4]]></pProductData4><pProductData5><![CDATA[$pProductData5]]></pProductData5><pProductData6><![CDATA[$pProductData6]]></pProductData6></cProductParameters><cAccountParameters><pAccID><![CDATA[$pAccID]]></pAccID><pAccEMail><![CDATA[$pAccEMail]]></pAccEMail><pAccRegisterIP><![CDATA[$pAccRegisterIP]]></pAccRegisterIP><pAccLoginIP><![CDATA[$pAccLoginIP]]></pAccLoginIP><pAccRegisterDate><![CDATA[$pAccRegisterDate]]></pAccRegisterDate><pAccLoginDate><![CDATA[$pAccLoginDate]]></pAccLoginDate><pAccRegisterDevice><![CDATA[$pAccRegisterDevice]]></pAccRegisterDevice><pAccLoginDevice><![CDATA[$pAccLoginDevice]]></pAccLoginDevice></cAccountParameters></StandardPaymentReq></AFS></IPSReqRoot>";

//对反欺诈信息进行base64_encode(*)
$pAFSReqB64 = base64_encode($pAFSReq);

//反欺诈签名验证串(*)：MD5原文=反欺诈信息+商户证书
$pAFSReqHashValue = md5($pAFSReq . $pMerchantKey);
?>
<html>
  <head>
    <title>跳转......</title>
    <meta http-equiv="content-Type" content="text/html; charset=utf-8" />
  </head>
  <body>
    <form action="<?php echo $form_url; ?>" method="post" id="sendOrder">
      <input type="hidden" name="pMerchantCode" value="<?php echo $pMerchantCode; ?>">
      <input type="hidden" name="pICPayReq" value="<?php echo $pICPayReqB64; ?>">
      <input type="hidden" name="pICPayReqHashValue" value="<?php echo $pICPayReqHashValue; ?>">
      <input type="hidden" name="pAFSReq" value="<?php echo $pAFSReqB64; ?>">
      <input type="hidden" name="pAFSReqHashValue" value="<?php echo $pAFSReqHashValue; ?>">
    </form>
    <script language="javascript" type="text/javascript">
      document.getElementById("sendOrder").submit();
    </script>
  </body>
</html>

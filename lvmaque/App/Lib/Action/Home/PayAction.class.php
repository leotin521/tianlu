<?php
// 本类由系统自动生成，仅供测试用途
class PayAction extends HCommonAction {
	var $paydetail = NULL;
	var $payConfig = NULL;
	var $locked = false;
	var $return_url = "";
	var $notice_url = "";
	var $member_url = "";
	
	public function _Myinit(){
		$this->return_url = "http://".$_SERVER['HTTP_HOST']."/Pay/payreturn";
		$this->notice_url = "http://".$_SERVER['HTTP_HOST']."/Pay/paynotice";
		$this->member_url = "http://".$_SERVER['HTTP_HOST']."/member";
		$this->payConfig = FS("Webconfig/payconfig");
		$this->ipsnotice_url = "http://".$_SERVER['HTTP_HOST']."/Pay/payipsnotice";//环迅主动对账
		
		$this->easypaynotice_url = "http://".$_SERVER['HTTP_HOST']."/Pay/payeasypaynotice";//易生支付
		$this->easypayreturn_url = "http://".$_SERVER['HTTP_HOST']."/Pay/payeasypayreturn";//易生支付
		
		$this->baofoback_url = "http://".$_SERVER['HTTP_HOST']."/pay/paybaofoback";//返回宝付前台
		$this->baofonotice_url = "http://".$_SERVER['HTTP_HOST']."/pay/paybaofonotice";//返回宝付后台
		
		$this->sinaback_url = "http://".$_SERVER['HTTP_HOST']."/pay/paysinaback";//返回新浪前台
		$this->sinanotice_url = "http://".$_SERVER['HTTP_HOST']."/pay/paysinanotice";//返回新浪后台

		$this->allinpayback_url = "http://".$_SERVER['HTTP_HOST']."/pay/payAllinpayBack";//返回通联支付前台
		$this->allinpaynotice_url = "http://".$_SERVER['HTTP_HOST']."/pay/payAllinpayNotice";//返回通联支付后台
		$this->unspaynotice_url = "http://".$_SERVER['HTTP_HOST']."/pay/payunspaynotice";//返回银生宝后台
		
				
		$this->reapalReturn_url = "http://".$_SERVER['HTTP_HOST']."/pay/payReapalReturn";//返回融宝前台同步
		$this->reapalNotice_url = "http://".$_SERVER['HTTP_HOST']."/pay/payReapalNotice";//返回融宝后台异步
	}
	
	public function offline(){
		$this->getPaydetail();
		$this->paydetail['money'] = floatval($_POST['money_off']);
        if(floatval($_POST['money_off'])>5000000){
             $this->error("单笔充值不能超过500万");       
          }
		//本地要保存的信息

        $payimg_arr = $_POST['swfimglist'];
        if(count($payimg_arr)){
            $this->paydetail['payimg'] = serialize($payimg_arr);
        }else{
            $this->paydetail['payimg'] = '';
        }

        $config = FS("Webconfig/payoff"); 
        $bank_id = intval($_POST['bank'])-1;
		$this->paydetail['fee'] = 0;
		$this->paydetail['nid'] = 'offline';
		$this->paydetail['way'] = 'off';
		$this->paydetail['tran_id'] = text($_POST['tran_id']);
		$this->paydetail['off_bank'] = $config['BANK'][$bank_id]['bank'].' 开户名：'.$config['BANK'][$bank_id]['payee'];
		$this->paydetail['off_way'] = text($_POST['off_way']);
		$newid = M('member_payonline')->add($this->paydetail);
		if($newid){
			$this->success("线下充值提交成功，请等待管理员审核",__APP__."/member");
		}else{
			$this->error("线下充值提交失败，请重试");
		}
	}
	
	//融宝支付
	public function reapal(){
		if ( $this->payConfig['reapal']['enable'] == 0 )
		{
			exit( "对不起，该支付方式被关闭，暂时不能使用!" );
		}
		$this->getPaydetail();
		
		$submitdata['service'] = 'online_pay';//接口名称，不需要修改pay_cus_no
		$submitdata['payment_type'] = 1;//交易类型
		$submitdata['merchant_ID'] = $this->payConfig['reapal']['MerNo'];// 合作身份者ID，由纯数字组成的字符串
		$submitdata['seller_email'] = $this->payConfig['reapal']['seller_email'];//签约融宝支付账号或卖家收款融宝支付帐户
		$submitdata['return_url'] = $this->reapalReturn_url;//同步
		$submitdata['notify_url'] = $this->reapalNotice_url;//异步
		$submitdata['charset'] = 'utf-8';
		$submitdata['order_no'] = 'reapal'.date('YmdHis').mt_rand( 100000,999999);//网站订单系统中的唯一订单号匹配
		$submitdata['title'] = $this->glo['web_name'].'充值';//订单名称，显示在融宝支付收银台里的“商品名称”里，显示在融宝支付的交易管理的“商品名称”的列表里。
		$submitdata['body'] = '在线充值';//订单描述、订单详细、订单备注，显示在融宝支付收银台里的“商品描述”里
		$submitdata['total_fee'] = number_format( $this->paydetail['money'], 2, ".", "" );//$this->paydetail['money'];//充值金额
		$submitdata['paymethod'] = 'directPay';//支付方式，银行直连
		if($this->paydetail['bank']=='REAPAL'){//更多，非直连
			$submitdata['defaultbank'] = '';
		}else{
			$submitdata['defaultbank'] = $this->paydetail['bank'];//银行代码$this->paydetail['bank']
		}
		$submitdata['sign_type'] = 'MD5';
		$submitdata['sign'] = $this->getSign("reapal_return", $submitdata);//签名数据
		
		unset( $this->paydetail['bank'] );
		$this->paydetail['fee'] = getfloatvalue( $this->payConfig['reapal']['feerate'] * $this->paydetail['money']/100,2);
		$this->paydetail['nid'] = $this->createnid("reapal",$submitdata['order_no']);
		$this->paydetail['way'] = "reapal";
		M("member_payonline" )->add( $this->paydetail );
		if ($_SERVER['HTTPS'] != "on") {
			$url = "http://epay.reapal.com/portal";//正式环境		  
		 }else{
			$url = "https://epay.reapal.com/portal";//正式环境		
		}
		$this->create( $submitdata, $url);		//正式环境
	}
	//网银在线
	public function chinabank(){
	    if($this->payConfig['chinabank']['enable']==0) exit("对不起，该支付方式被关闭，暂时不能使用!");
	    $this->getPaydetail();
	    $vo = M('members m')->field("m.user_name")->where("m.id={$this->uid}")->find();
	    $submitdata['v_mid'] = $this->payConfig['chinabank']['MerCode'];
	    $submitdata['v_oid'] = "chinabank".time().rand(10000,99999);
	    $submitdata['v_amount'] = $this->paydetail['money'];
	    $submitdata['v_moneytype'] = 'CNY';
	    $submitdata['v_url'] = $this->notice_url."?payid=chinabank";
	    if($this->paydetail['bank']){
	        $submitdata['pmode_id'] = $this->paydetail['bank'];//银行直联必须
	    }
	    $submitdata['remark1'] ='';
	    $submitdata['remark2'] ='[url:='.$this->notice_url."?payid=chinabank".']'; //服务器异步通知的接收地址。对应AutoReceive.php示例。必须要有[url:=]格式。
	    $submitdata['v_rcvname'] = urlencode($this->glo['web_name']."帐户充值" );
	    $submitdata['v_ordername'] =$vo['user_name'];
	    $submitdata['v_md5info'] = strtoupper($this->getSign('chinabank',$submitdata));
	
	    //本地要保存的信息
	    unset($this->paydetail['bank']);
	    $this->paydetail['fee'] = getFloatValue($this->payConfig['chinabank']['feerate'] * $this->paydetail['money'] / 100,2);
	    $this->paydetail['nid'] = $this->createnid('chinabank',$submitdata['v_oid']);
	    $this->paydetail['way'] = 'chinabank';
	    M('member_payonline')->add($this->paydetail);
	    $this->create($submitdata,"https://Pay3.chinabank.com.cn/PayGate");
	}
	/**
	 * 京东快捷支付
	 *
	 */
	public function jdpay(){
	    if($this->payConfig['jdpay']['enable']==0) exit("对不起，该支付方式被关闭，暂时不能使用!");
	    $wepay = array(
	        'wepay' => array(
	            'merchantNum' => $this->payConfig['jdpay']['MerCode'],
	            'desKey' => $this->payConfig['jdpay']['desKey'],
	            'md5Key' => $this->payConfig['jdpay']['key'],
	            'serverPayUrl' => 'https://plus.jdpay.com/nPay.htm',
	            'serverQueryUrl' => 'https://m.jdpay.com/wepay/query',
	            'serverRefundUrl' => 'https://m.jdpay.com/wepay/refund',
	            'successCallbackUrl' => $this->return_url."?payid=jdpay",
	            'failCallbackUrl' => 'http://www.baidu.com ',
	            'notifyUrl' => $this->notice_url."?payid=jdpay",
	            'forPayLayerUrl' => 'http://localhost/pcclient-php/forPayLayer.html',
	        )
	    );
	    $money = getFloatValue($_GET['t_money'],2);
	    $submitdata['version'] = '1.1.5';//版本号
	    $submitdata["currency"] = 'CNY';
	    $submitdata['token'] = '';//token值
	    $submitdata['merchantNum'] = $this->payConfig['jdpay']['MerCode'];
	    $submitdata['merchantRemark'] = '快捷支付';//生产环境-测试商户号
	    $submitdata['tradeNum'] = 123;//get_trade_num($wepay);//交易号
	    $submitdata['tradeName'] = '快捷支付';
	    $submitdata['tradeDescription'] = '快捷支付';
	    $submitdata['tradeTime'] = date('Y-m-d H:i:s', time());//交易时间
	    $submitdata['tradeAmount'] = $money*100;//交易金额 分为单位
	    $submitdata['notifyUrl'] = $this->notice_url."?payid=jdpay";//异步
	    $submitdata['successCallbackUrl'] = $this->return_url."?payid=jdpay";//同步
	    $submitdata['ip'] = get_client_ip();
	    $sign = signWithoutToHex($submitdata);
	    $submitdata['merchantSign'] = $sign;
	    //本地要保存的信息
	    $this->paydetail['money'] = $money;
	    $this->paydetail['add_time'] = time();
	    $this->paydetail['add_ip'] = get_client_ip();
	    $this->paydetail['status'] = 0;
	    $this->paydetail['uid'] = $this->uid;
	    $this->paydetail['fee'] = getFloatValue($this->payConfig['jdpay']['feerate'] * $this->paydetail['money'] / 100,2);
	    $this->paydetail['nid'] = $this->createnid('jdpay',$submitdata['tradeNum']);
	    $this->paydetail['way'] = 'jdpay';
	    M('member_payonline')->add($this->paydetail);
	    #	dump($submitdata);exit;
	    $this->create($submitdata,"https://plus.jdpay.com/nPay.htm");
	}
	//银生宝支付
     public function unspay(){
	 	header("Content-type: text/html; charset=gbk");
      if ( $this->payConfig['unspay']['enable'] == 0 )
        {
            exit( "对不起，该支付方式被关闭，暂时不能使用!" );
        }  
        $this->getPaydetail();
		$submitdata['version'] = "3.0.0";	
		$submitdata['merchantId'] = $this->payConfig['unspay']['merchantId'];//"1120070416084124001";		//注册商户在银生的客户编号
		$submitdata['merchantKey'] =$this->payConfig['unspay']['merchantKey'];//"111111";					//注册商户在银生设置的密钥
		$submitdata['merchantUrl'] = $this->unspaynotice_url;//返回地址
		$submitdata['responseMode'] = "3";					//响应方式，1-页面响应，2-后台响应，3-两者都需
		$submitdata['time'] =  date('YmdHis');			//订单创建时间
		$submitdata['orderId'] ="GD".time().mt_rand(1000,999999);		//订单id[商户网站]
		$submitdata['currencyType'] ="CNY";        //货币种类，暂时只支持人民币：CNY
		$submitdata['amount'] = number_format( $this->paydetail['money'], 2, ".", "" );
		$submitdata['remark'] = "";
		if($this->paydetail['bank']!='UNSPAY' || $this->paydetail['bank']!='UNSCARD') {
			$submitdata['assuredPay'] = false;//非担保交易
			$submitdata['bankCode'] = strtolower($this->paydetail['bank']);//银行直联必须
		}else{
			$submitdata['assuredPay'] = "";
		}
		$submitdata['b2b'] = ""; //是否B2B支付
		$submitdata['commodity'] = $this->glo['web_name']."帐户充值";//iconv( "UTF-8", "gb2312//IGNORE" ,$this->glo['web_name']."帐户充值");//产品名称
		$submitdata['orderUrl'] ="";

		$submitdata['mac'] = strtoupper(md5("merchantId={$submitdata['merchantId']}&merchantUrl={$submitdata['merchantUrl']}&responseMode={$submitdata['responseMode']}&orderId={$submitdata['orderId']}&currencyType={$submitdata['currencyType']}&amount={$submitdata['amount']}&assuredPay={$submitdata['assuredPay']}&time={$submitdata['time']}&remark={$submitdata['remark']}&merchantKey={$submitdata['merchantKey']}"));//数字签名
		
		
        //unset( $this->paydetail['bank']);
        $this->paydetail['fee'] = getfloatvalue( $this->payConfig['unspay']['feerate'] * $this->paydetail['money']/100,2);
        $this->paydetail['nid'] = $this->createnid("unspay",$submitdata['orderId']);
        $this->paydetail['way'] = "unspay";
        M("member_payonline" )->add( $this->paydetail);
        $this->create( $submitdata,"https://www.unspay.com/unspay/page/linkbank/payRequest.do" );//银生宝接收地址
    }
	
	//国付宝接口
	 public function guofubaopay(){
		if($this->payConfig['guofubao']['enable']==0) exit("对不起，该支付方式被关闭，暂时不能使用!");
		$this->getPaydetail();
		$submitdata['charset'] = 2;
		$submitdata['language'] = 1;
		$submitdata['version'] = "2.1";
		$submitdata['tranCode'] = '8888';
		$submitdata['feeAmt'] = isset($this->payConfig['guofubao']['feerate'])?getFloatValue($this->payConfig['guofubao']['feerate'],2):0;
		$submitdata['currencyType'] = 156;
		$submitdata['merOrderNum'] = "guofu".time().rand(10000,99999);
		$submitdata['tranDateTime'] = date("YmdHis",time());
		$submitdata['tranIP'] = get_client_ip();
		$submitdata['goodsName'] = $this->glo['web_name']."帐户充值";
		$submitdata['frontMerUrl'] = $this->return_url."?payid=gfb";
		$submitdata['backgroundMerUrl'] = $this->notice_url."?payid=gfb";
		$submitdata['merchantID'] = $this->payConfig['guofubao']['merchantID'];//商户ID
		$submitdata['virCardNoIn'] = $this->payConfig['guofubao']['virCardNoIn'];//国付宝帐户
		$submitdata['tranAmt'] = $this->paydetail['money'];
		if($this->paydetail['bank']!='GUOFUBAO') $submitdata['bankCode'] = $this->paydetail['bank'];//银行直联必须
		$submitdata['userType'] = 1;//银行直联,1个人,2企业
		$submitdata['signType']=1;
		$submitdata['signValue'] = $this->getSign('gfb',$submitdata);
		
		//本地要保存的信息
		unset($this->paydetail['bank']);
		$this->paydetail['fee'] = getFloatValue($this->payConfig['guofubao']['feerate'] * $this->paydetail['money'] / 100,2);
		$this->paydetail['nid'] = $this->createnid('gfb',$submitdata['merOrderNum']);
		$this->paydetail['way'] = 'gfb';
		M('member_payonline')->add($this->paydetail);
		//$this->create($submitdata,"https://gateway.gopay.com.cn/Trans/WebClientAction.do");//新网关环境
		$this->create($submitdata,"https://www.gopay.com.cn/PGServer/Trans/WebClientAction.do?");//旧网关环境
    }
	
	//环迅支付
	public function ips(){
		if ( $this->payConfig['ips']['enable'] == 0 )
		{
			exit( "对不起，该支付方式被关闭，暂时不能使用!" );
		}
		$this->getPaydetail( );
		$submitdata['Mer_code'] = $this->payConfig['ips']['MerCode'];
		$submitdata['Billno'] = date( "YmdHis" ).mt_rand( 100000, 999999 );
		$submitdata['Date'] = date( "Ymd" );
		$submitdata['Amount'] = number_format( $this->paydetail['money'], 2, ".", "" );
		$submitdata['DispAmount'] = $submitdata['Amount'];
		$submitdata['Currency_Type'] = "RMB";
		$submitdata['Gateway_Type'] = "01";
		$submitdata['Lang'] = "GB";
		$submitdata['Merchanturl'] = $this->return_url."?payid=ips";
		$submitdata['FailUrl'] = $this->return_url."?payid=ips";
		$submitdata['ErrorUrl'] = "";
		$submitdata['Attach'] = "";
		$submitdata['OrderEncodeType'] = "5";
		$submitdata['RetEncodeType'] = "17";
		$submitdata['Rettype'] = "1";
		//$submitdata['DoCredit'] = "1";//环迅支付网银直连必须
		//if($this->paydetail['bank']) $submitdata['Bankco'] = $this->paydetail['bank'];
		//$submitdata['ServerUrl'] = $this->notice_url."?payid=ips";
		$submitdata['ServerUrl'] = $this->ipsnotice_url;//环迅主动对账 提交地址不能带参数
		$submitdata['SignMD5'] = $this->getSign( "ips", $submitdata );
		unset( $this->paydetail['bank'] );
		$this->paydetail['fee'] = getfloatvalue( $this->payConfig['ips']['feerate'] * $this->paydetail['money'] / 100, 2 );
		$this->paydetail['nid'] = $this->createnid( "ips", $submitdata['Billno'] );
		$this->paydetail['way'] = "ips";
		M( "member_payonline" )->add( $this->paydetail );
		$this->create( $submitdata, "https://pay.ips.com.cn/ipayment.aspx" );		//正式环境
		//$this->create( $submitdata, "http://pay.ips.net.cn/ipayment.aspx" );		//测试环境
	}
	
	//原宝付接口
	/*public function baofoo(){
		if($this->payConfig['baofoo']['enable'] == 0)
		{
			exit( "对不起，该支付方式被关闭，暂时不能使用!" );
		}
		$this->getPaydetail( );
		$submitdata['MerchantID'] = $this->payConfig['baofoo']['MerCode'];
		$submitdata['PayID'] = 1000;
		$submitdata['TradeDate'] = date("Ymdhis");
		$submitdata['TransID'] = date("YmdHis").mt_rand( 1000, 9999 );
		$submitdata['OrderMoney'] = number_format( $this->paydetail['money'], 2, ".", "" ) * 100;
		$submitdata['ProductName'] = urlencode($this->glo['web_name']."帐户充值" );
		$submitdata['Amount'] = "1";
		$submitdata['ProductLogo'] = "";
		$submitdata['Username'] = "";
		$submitdata['Email'] = "";
		$submitdata['Mobile'] = "";
		$submitdata['AdditionalInfo'] = "";
		$submitdata['Merchant_url'] = $this->return_url."?payid=baofoo";
		$submitdata['Return_url'] = $this->notice_url."?payid=baofoo";
		$submitdata['NoticeType'] = "1";
		$submitdata['Md5Sign'] = $this->getSign( "baofoo", $submitdata );
		unset( $this->paydetail['bank'] );
		$this->paydetail['fee'] = getfloatvalue( $this->payConfig['baofoo']['feerate'] * $this->paydetail['money']/100, 2 );
		$this->paydetail['nid'] = $this->createnid("baofoo", $submitdata['TransID']);
		$this->paydetail['way'] = "baofoo";
		M("member_payonline")->add( $this->paydetail );
		//$this->create( $submitdata, "http://paygate.baofoo.com/PayReceive/payindex.aspx" );//正式环境
		//$this->create( $submitdata, "http://paytest.baofoo.com/PayReceive/payindex.aspx" );//测试环境
		$this->create( $submitdata, "http://paygate.baofoo.com/PayReceive/bankpay.aspx" );//借贷分离地址
	}*/
	
	//升级后宝付接口
	public function baofoo(){
		if($this->payConfig['baofoo']['enable'] == 0)
		{
			exit( "对不起，该支付方式被关闭，暂时不能使用!" );
		}
		$this->getPaydetail( );
        $submitdata['MemberID'] = $this->payConfig['baofoo']['MemberID'];//商户号
        $submitdata['TerminalID'] = $this->payConfig['baofoo']['TerminalID'];//'18161';//终端号
        $submitdata['InterfaceVersion'] = '4.0';//接口版本号
		$submitdata['KeyType'] = 1;//接口版本号
		if($this->paydetail['bank']!='BAOFOO') {
			$submitdata['PayID'] = $this->paydetail['bank'];
		}else{
			$submitdata['PayID'] ='';
		}
		
		$submitdata['TradeDate'] = date("Ymdhis");//交易时间
		$submitdata['TransID'] = date("YmdHis").mt_rand( 1000, 9999 );//流水号
		$submitdata['OrderMoney'] = number_format( $this->paydetail['money'], 2, ".", "" ) * 100;
		$submitdata['ProductName'] = $this->glo['web_name']."帐户充值";//'toubiao';//产品名称
		$submitdata['Amount'] = "1";
		$submitdata['Username'] = "";
		$submitdata['AdditionalInfo'] = "";
		$submitdata['PageUrl'] = $this->baofoback_url;
		$submitdata['ReturnUrl'] = $this->baofonotice_url;
		$submitdata['NoticeType'] = "1";
		$submitdata['Signature'] = $this->getSign("baofoo", $submitdata);

		//unset( $this->paydetail['bank']);
		$this->paydetail['fee'] = getfloatvalue( $this->payConfig['baofoo']['feerate'] * $this->paydetail['money']/100, 2 );
		$this->paydetail['nid'] = $this->createnid("baofoo", $submitdata['TransID']);
		$this->paydetail['way'] = "baofoo";
		M("member_payonline")->add( $this->paydetail );

		if ($_SERVER['HTTPS'] != "on") {
			$url = "http://gw.baofoo.com/payindex";//正式环境			"http://vgw.baofoo.com/payindex";//测试环境     
		 }else{
			$url = "https://gw.baofoo.com/payindex";//正式环境			"https://vgw.baofoo.com/payindex";//测试环境		
		}
		$this->create( $submitdata, $url);//正式
	}
	
	//丰付支付
       public function sumapay(){
		
      if ( $this->payConfig['sumapay']['enable'] == 0 )
        {
            exit( "对不起，该支付方式被关闭，暂时不能使用!" );
        }  
        $this->getPaydetail();
        $submitdata['requestId'] =date("YmdHis").mt_rand( 100000,999999);//请求流水编号
        $submitdata['tradeProcess'] =$this->payConfig['sumapay']['merAcct'];//外部系统标识
        $submitdata['totalBizType'] =$this->payConfig['sumapay']['bizType'];//业务类型
        $submitdata['totalPrice'] =$this->paydetail['money'];//交易总价格
        $submitdata['backurl'] =$this->return_url."?payid=sumapay";//成功跳转的url地址
        $submitdata['returnurl'] =$this->member_url;//不执行支付返回的URL地址
        $submitdata['noticeurl'] =$this->notice_url."?payid=sumapay";//支付成功后后台通知地址
		
        //银行代码
		if($this->paydetail['bank']!='SUMAPAY') {
			$submitdata['bankcode'] = strtolower($this->paydetail['bank']);//直连必须
		}else{
			$submitdata['bankcode'] ='';//银行代码
		}
		
		
        $submitdata['description'] ='';//辅助信息
        $submitdata['rnaName'] ='';//姓名
        $submitdata['rnaIdNumber'] ='';//身份证号
        $submitdata['rnaMobilePhone'] ='';//手机号
        $submitdata['userIdIdentity'] ='mem'.mt_rand(1000,999999);//商户用户唯一标识，支付系统把该唯一标识与用户输入的支付信息进行保存
		
        $submitdata['payType'] ='';//支付类型（0或空：网银支付，1：快捷支付）
        $submitdata['bankCardType'] ='';//网银支付借贷分离标记（默认不区分，1：借记，2：贷记卡）
        $submitdata['goodsDesc'] ='';//商品描述
        $submitdata['allowRePay'] ='';//是否可以重新支付
        $submitdata['rePayTimeOut'] ='';//重新支付有效期（默认为30天）
        $submitdata['productId'] ='product_'.mt_rand(1000,999999);//产品编码
        $submitdata['productName'] =iconv( "UTF-8", "gb2312//IGNORE" ,$this->glo['web_name']."帐户充值");//$this->glo['web_name']."帐户充值";//产品名称

        $submitdata['fund'] =$this->paydetail['money'];//产品定价
		
        $submitdata['merAcct'] =$this->payConfig['sumapay']['merAcct'];//供应商编码
        $submitdata['bizType'] =$this->payConfig['sumapay']['bizType'];//产品业务类型
		
        $submitdata['productNumber'] =1;//产品订购数量
		
        $submitdata['mersignature'] =$this->getSign("sumapay", $submitdata);//数字签名
		
        //unset( $this->paydetail['bank'] );
        $this->paydetail['fee'] = getfloatvalue( $this->payConfig['sumapay']['feerate'] * $this->paydetail['money']/100,2);
        $this->paydetail['nid'] = $this->createnid("sumapay",$submitdata['requestId']);
        $this->paydetail['way'] = "sumapay";
        M("member_payonline" )->add( $this->paydetail );
		if($submitdata['bankcode']!='') {
			$this->create( $submitdata,"https://www.sumapay.com/sumapay/pay_bankPayForNoLoginUser");//丰付网银直连链接
		}else{
			$this->create( $submitdata,"https://www.sumapay.com/sumapay/unitivepay_bankPayForNoLoginUser" );//丰付网银非直连链接
		}
        
    }
	
	//盛付通接口
	public function shengpay(){
		if($this->payConfig['shengpay']['enable'] == 0)
		{
			exit( "对不起，该支付方式被关闭，暂时不能使用!" );
		}
		$this->getPaydetail();
		$submitdata['Name'] = "B2CPayment";
		$submitdata['Version'] = "V4.1.1.1.1";
		$submitdata['Charset'] = "UTF-8";
		$submitdata['MsgSender'] = $this->payConfig['shengpay']['MerCode'];
		$submitdata['SendTime'] = date("Ymdhis");
		$submitdata['OrderNo'] = date("YmdHis").mt_rand( 1000, 9999 );
		$submitdata['OrderAmount'] = number_format( $this->paydetail['money'], 2, ".", "" );
		$submitdata['OrderTime'] =date("Ymdhis");
		$submitdata['PayType'] = "PT001";
		//$submitdata['PayChannel'] = "19";/*（19 储蓄卡，20 信用卡）做直连时，储蓄卡和信用卡需要分开*/
		//$submitdata['InstCode'] = "CMB";/*银行编码，参看接口文档*/
		$submitdata['PageUrl'] = $this->return_url."?payid=shengpay";
		$submitdata['NotifyUrl'] = $this->notice_url."?payid=shengpay";
		$submitdata['ProductName'] = $this->glo['web_name']."帐户充值";
		$submitdata['BuyerContact'] = "";
		$submitdata['BuyerIp'] = "";
		$submitdata['Ext1'] = "";
		$submitdata['Ext2'] = "";
		$submitdata['SignType'] = "MD5";
		$submitdata['SignMsg'] = $this->getSign("shengpay", $submitdata );
		unset( $this->paydetail['bank'] );
		$this->paydetail['fee'] = getfloatvalue( $this->payConfig['shengpay']['feerate'] * $this->paydetail['money']/100, 2 );
		$this->paydetail['nid'] = $this->createnid("shengpay", $submitdata['OrderNo']);
		$this->paydetail['way'] = "shengpay";
		M("member_payonline")->add( $this->paydetail );
		//echo $submitdata['SignMsg'];
		$this->create( $submitdata, "https://mas.sdo.com/web-acquire-channel/cashier.htm" );//正式环境
		//$this->create( $submitdata, "https://mer.mas.sdo.com/web-acquire-channel/cashier.htm" );//测试环境
	}
	
	//财付通接口
	public function tenpay()
	{
		if ($this->payConfig['tenpay']['enable'] ==0)
		{
			$this->error( "对不起，该支付方式被关闭，暂时不能使用!" );
		}
		$this->getPaydetail();
		$submitdata['partner'] = $this->payConfig['tenpay']['partner'];
		$submitdata['out_trade_no'] = "tenpay".time().rand(10000, 99999);
		$submitdata['total_fee'] = $this->paydetail['money'] * 100;
		$submitdata['return_url'] = $this->return_url."?payid=tenpay";
		$submitdata['notify_url'] = $this->notice_url."?payid=tenpay";
		$submitdata['body'] = $this->glo['web_name']."帐户充值";
		$submitdata['bank_type'] = "DEFAULT";
		$submitdata['spbill_create_ip'] = get_client_ip();
		$submitdata['fee_type'] = 1;
		$submitdata['subject'] = $this->glo['web_name']."帐户充值";
		$submitdata['sign_type'] = "MD5";
		$submitdata['service_version'] = "1.0";
		$submitdata['input_charset'] = "UTF-8";
		$submitdata['sign_key_index'] = 1;
		$submitdata['trade_mode'] = 1;
		$submitdata['sign'] = $this->getSign("tenpay",$submitdata);
		unset( $this->paydetail['bank']);
		$this->paydetail['fee'] = 0;
		$this->paydetail['nid'] = $this->createnid("tenpay",$submitdata['out_trade_no']);
		$this->paydetail['way'] = "tenpay";
		M("payonline")->add( $this->paydetail);
		$this->create($submitdata, "https://gw.tenpay.com/gateway/pay.htm");
	}
	
	//汇潮支付
	public function ecpss(){
		if ( $this->payConfig['ecpss']['enable'] == 0 )
		{
			exit( "对不起，该支付方式被关闭，暂时不能使用!" );
		}
		$this->getPaydetail();
		$submitdata['MerNo'] = $this->payConfig['ecpss']['MerNo'];
		$submitdata['BillNo'] = date("YmdHis").mt_rand( 100000,999999);
		
		$submitdata['Amount'] = number_format( $this->paydetail['money'], 2, ".", "" );
		$submitdata['ReturnURL'] = $this->return_url."?payid=ecpss";
		$submitdata['AdviceURL'] = $this->notice_url."?payid=ecpss";
		$submitdata['Remark'] = "";
		$submitdata['orderTime'] = date("YmdHis");
		////////////////////////////////////////
		$submitdata['shippingFirstName'] = "";//'-------------------收货人的姓
		$submitdata['shippingLastName'] = "";//'-------------------收货人的名
		$submitdata['shippingEmail'] = "";//'----------收货人的Email
		$submitdata['shippingPhone'] = "";//'---------------收货人的固定电话
		$submitdata['shippingZipcode'] = "";//'----------------收货人的邮编
		$submitdata['shippingAddress'] = "";//'-------------收货人具体地址
		$submitdata['shippingCity'] = "";// '--------------------收货人所在城市
		$submitdata['shippingSstate'] = "";//'-------------------收货人所在省或者州
		$submitdata['shippingCountry'] = "";// '-------------------收货人所在国家
		$submitdata['products'] = $this->glo['web_name']."帐户充值";// '------------------物品信息
		//////////////////////////////////////////////////////////////////
		
		
		$submitdata['MD5info'] = $this->getSign( "ecpss", $submitdata);
		unset( $this->paydetail['bank'] );
		$this->paydetail['fee'] = getfloatvalue( $this->payConfig['ecpss']['feerate'] * $this->paydetail['money']/100,2);
		$this->paydetail['nid'] = $this->createnid("ecpss",$submitdata['BillNo']);
		$this->paydetail['way'] = "ecpss";
		M("member_payonline" )->add( $this->paydetail );
		$this->create( $submitdata, "https://pay.ecpss.com/sslpayment" );		//正式环境
	}
	
	//易生支付接口
	public function easypay(){
		if($this->payConfig['easypay']['enable'] == 0)
		{
			exit( "对不起，该支付方式被关闭，暂时不能使用!" );
		}
		$this->getPaydetail();
		
		$submitdata['service'] = "create_direct_pay_by_user";
		$submitdata['payment_type'] = "1";//支付类型
		$submitdata['partner'] = $this->payConfig['easypay']['partner'];
		$submitdata['seller_email'] = $this->payConfig['easypay']['seller_email'];//卖家Email
		$submitdata['return_url'] = $this->easypayreturn_url;//提交地址不能带参数
		$submitdata['notify_url'] = $this->easypaynotice_url;// 提交地址不能带参数
		$submitdata['_input_charset'] = "utf-8";
		$submitdata['out_trade_no'] = date('YmdHis').mt_rand( 100000,999999);//合作伙伴交易号既是订单号
		$submitdata['subject'] = "在线充值";
		$submitdata['body'] = $this->glo['web_name']."帐户充值";
		$submitdata['total_fee'] = number_format( $this->paydetail['money'], 2, ".", "" );
		$submitdata['paymethod'] = "bankPay";//支付方式
		$submitdata['defaultbank'] = "";
		
		$submitdata['buyer_email'] ='';//买家Email
		$submitdata['buyer_realname'] ='';//买家真实姓名
		$submitdata['buyer_contact'] ='';//买家联系方式
		
		$submitdata['sign_type'] = "MD5";
		$submitdata['sign'] = $this->getSign("easypay", $submitdata);
		
		unset($this->paydetail['bank']);
		$this->paydetail['fee'] = getfloatvalue( $this->payConfig['easypay']['feerate'] * $this->paydetail['money']/100, 2 );
		$this->paydetail['nid'] = $this->createnid("easypay", $submitdata['out_trade_no']);
		$this->paydetail['way'] = "easypay";
		M("member_payonline")->add( $this->paydetail);
		$this->create( $submitdata, "http://cashier.bhecard.com/portal?");//环境地址
	}
	
	//中国移动支付接口
	public function cmpay(){
		if ( $this->payConfig['cmpay']['enable'] == 0 )
		{
			exit( "对不起，该支付方式被关闭，暂时不能使用!" );
		}
		$this->getPaydetail();
		$submitdata['characterSet'] ='02';//
		$submitdata['callbackUrl'] =$this->return_url."?payid=cmpay";;//
		$submitdata['notifyUrl'] =$this->notice_url."?payid=cmpay";//
		$submitdata['ipAddress'] =getIp();//
		$submitdata['merchantId'] =$this->payConfig['cmpay']['merchantId'];//测试商户100000000000040
		$submitdata['requestId'] =date("YmdHis").mt_rand( 100000,999999);//商户请求号
		$submitdata['signType'] ='MD5';//
		$submitdata['type'] ='GWDirectPay';//接口类型
		$submitdata['version'] ='2.0.0';//
		$submitdata['amount'] = $this->paydetail['money']*100;//交易金额
		$submitdata['bankAbbr'] =$this->paydetail['bank'];//银行代码
		
		$submitdata['currency'] ='00';//
		$submitdata['orderDate'] =date(Ymd);//
		$submitdata['orderId'] ='cmpay'.date("YmdHis").mt_rand( 100000,999999);//商户订单号
		$submitdata['merAcDate'] =date(Ymd);//
		$submitdata['period'] =10;//有效期数量. 数字，不订单有效期单位同时构成订单有效期
		$submitdata['periodUnit'] ='00';//
		$submitdata['merchantAbbr'] ='';//商户展示名称
		$submitdata['productDesc'] ='';//商品描述
		$submitdata['productId'] ='';//商品编号
		$submitdata['productName'] ='toubiao';//商品名称
		$submitdata['productNum'] ='';//商品数量
		$submitdata['reserved1'] ='';//保留字段1
		$submitdata['reserved2'] ='';//保留字段2
		$submitdata['userToken'] ='';//用户标识
		$submitdata['showUrl'] ='';//商品展示地址
		$submitdata['couponsFlag'] ='00';//营销工具使用控制
		$submitdata['hmac'] =$this->getSign("cmpay_return", $submitdata);//签名数据
		//$submitdata['merchantCert'] ='';//商户证书公钥
		//echo '<pre>';
		//dump($submitdata);exit;

		unset( $this->paydetail['bank'] );
		$this->paydetail['fee'] = getfloatvalue( $this->payConfig['cmpay']['feerate'] * $this->paydetail['money']/100,2);
		$this->paydetail['nid'] = $this->createnid("cmpay",$submitdata['orderId']);
		$this->paydetail['way'] = "cmpay";
		M("member_payonline" )->add( $this->paydetail );
		$this->create( $submitdata, "https://ipos.10086.cn/ips/cmpayService" );		//正式环境
	}
		
//通联支付
	public function allinpay()
	{
		if ( $this->payConfig['allinpay']['enable'] == 0){
			exit( "对不起，该支付方式被关闭，暂时不能使用!" );
		}
		$this->getPaydetail();
		$submitdata['version'] = "v1.0";
		$submitdata['inputCharset'] = 1;
		$submitdata['language'] = 1;
		$submitdata['signType'] = 1;
		$submitdata['orderCurrency'] = 0;
		$submitdata['payerName'] = "";
		$submitdata['payerEmail'] = "";
		$submitdata['payerTelephone'] = "";
		$submitdata['payerIDCard'] = "";
		$submitdata['pid'] = "";
		$submitdata['orderExpireDatetime'] = "";
		$submitdata['orderNo'] = date("YmdHis").mt_rand( 10000,99999);
		$submitdata['orderAmount'] = number_format( $this->paydetail['money'], 2, ".", "" ) * 100;
		$submitdata['productName'] = $this->glo['web_name']."帐户充值";
		$submitdata['productPrice'] = number_format( $this->paydetail['money'], 2, ".", "" ) * 100;
		$submitdata['productNum'] = 1;
		$submitdata['productId'] = 1;
		$submitdata['productDescription'] = "";
		$submitdata['ext1'] = "";
		$submitdata['ext2'] = "";
		$submitdata['payType'] = 0;
		$submitdata['issuerId'] = "";
		$submitdata['pan'] = "";
		$submitdata['merchantId'] = $this->payConfig['allinpay']['MerCode'];
		$submitdata['orderDatetime'] = date( "YmdHis" );
	#	$submitdata['pickupUrl'] = $this->notice_url."?payid=allinpay";
	#	$submitdata['receiveUrl'] = $this->return_url."?payid=allinpay";
		$submitdata['pickupUrl'] = $this->allinpayback_url;
		$submitdata['receiveUrl'] = $this->allinpaynotice_url;
		$submitdata['signMsg'] = $this->getSign( "allinpay", $submitdata );
		unset( $this->paydetail['bank'] );
		$this->paydetail['fee'] = getfloatvalue( $this->payConfig['allinpay']['feerate'] * $this->paydetail['money'] / 100, 2 );
		$this->paydetail['nid'] = $this->createnid( "allinpay", $submitdata['orderNo'] );
		$this->paydetail['way'] = "allinpay";
		M("member_payonline")->add($this->paydetail);
		//$this->create($submitdata,"http://ceshi.allinpay.com/gateway/index.do" );//测试环境
		$this->create($submitdata,"https://service.allinpay.com/gateway/index.do" );//生产环境
	}	
	
	//新浪支付
		//sina
	public function sina(){
		
		if ( $this->payConfig['sina']['enable'] == 0 )
		{
			exit( "对不起，该支付方式被关闭，暂时不能使用!" );
		}
		$this->getPaydetail();
		
		$submitdata['inputCharset']='2';
		$submitdata['bgUrl']=$this->sinanotice_url;//后台通知url
		$submitdata['cancelUrl']='';//取消支付返回url
		$submitdata['version']='v2.3';
		$submitdata['language']='1';
		$submitdata['signType']='1';//1代表md5,4代表pki方式
		
		//买卖双方信息参数
		$submitdata['merchantAcctId']=$this->payConfig['sina']['merchantAcctId'];//
		$submitdata['payerName']='';//支付人姓名
		$submitdata['payerContactType']='';//支付人联系方式类型
		$submitdata['payerContact']='';//支付人联系方式
		$submitdata['payerIdType']='';//指定付款人
		$submitdata['payerId']='';//付款人标识
		//业务参数
		$submitdata['orderId']='sina'.date("YmdHis").mt_rand( 100000,999999);;//订单号
		$submitdata['orderAmount']=$this->paydetail['money']*100;;//商户订单金额，以分为单位
		$submitdata['orderTime']=date(YmdHis);//time();//订单提交时间
		$submitdata['productName']='';//商品名称
		$submitdata['productNum']='';//商品数量
		$submitdata['productId']='';//商品代码
		$submitdata['productDesc']='';//商品描述
		$submitdata['payType']='10';//支付方式
		$submitdata['bankId']='';//$this->paydetail['bank'];//银行代码
		$submitdata['redoFlag']=1;//同一订单禁止重复提交标志
		$submitdata['expiredTime']=10;//订单过期时间
		$submitdata['pid']=$this->payConfig['sina']['pid'];//数字串。商户的memberId
		$submitdata['ip']=get_client_ip();//
		$submitdata['deviceId']='';//用户在商户APP下单的时候的mac地址用于风控校验
		$submitdata['signMsg']=$this->getSign("sina", $submitdata);//签名字符串
		
		unset( $this->paydetail['bank'] );
		$this->paydetail['fee'] = getfloatvalue( $this->payConfig['sina']['feerate'] * $this->paydetail['money']/100,2);
		$this->paydetail['nid'] = $this->createnid("sina",$submitdata['orderId']);
		$this->paydetail['way'] = "sina";
		M("member_payonline" )->add( $this->paydetail );
		//$this->create( $submitdata, "https://testgate.pay.sina.com.cn/acquire-order-channel/gateway/receiveOrderLoading.htm" );		//测试环境
		$this->create( $submitdata, "https://gate.pay.sina.com.cn/acquire-order-channel/gateway/receiveOrderLoading.htm" );//正式环境
		//$this->create( $submitdata, "https://mas.weibopay.com/acquire-order-channel/gateway/batchRapidPayToBankcard.htm" );//快速通道正式环境
	}
	
	public function payreturn(){
		$payid = intval($_REQUEST['payid']);
		switch($payid){
			case "sumapay":
            $result=$_REQUEST["status"];
            $sign=$this->getSign("sumapay_return", $_REQUEST);
            if($sign==$_REQUEST["resultSignature"]){
                if($result==2){
                $this->success( "充值完成", __APP__."/member/" );
                }else{
                    $this->error( "充值失败", __APP__."/member/" );
                }
            }else{
            	$this->error( "签名不付", __APP__."/member/" );
            }
            break;
			case 'gfb':
				$recode = htmlspecialchars($_REQUEST['respCode'], ENT_QUOTES);
				if($recode=="0000"){//充值成功
					$signGet = $this->getSign('gfb',$_REQUEST);
					$nid = $this->createnid('gfb',$_REQUEST['merOrderNum']);
					if($_REQUEST['signValue']==$signGet){//充值完成
						$this->success("充值完成",__APP__."/member/");
					}else{//签名不付
						$this->error("签名不付",__APP__."/member/");
					}
				}else{//充值失败
						$this->error(auto_charset($_REQUEST['msgExt']),__APP__."/member/");
				}
			break;
			case "ips" :
				$recode = htmlspecialchars($_REQUEST['succ'], ENT_QUOTES);
				if ( $recode == "Y" )
				{
					$signGet = $this->getSign( "ips_return", $_REQUEST );
					$nid = $this->createnid( "ips", htmlspecialchars($_REQUEST['billno'], ENT_QUOTES) );
					if ( $_REQUEST['signature'] == $signGet )
					{
						$this->success( "充值完成", __APP__."/member/" );
					}
					else
					{
						$this->error( "签名不付", __APP__."/member/" );
					}
				}
				else
				{
					$this->error( "充值失败", __APP__."/member/" );
				}
			break;
			case 'chinabank':
			    $v_pstatus = $_REQUEST['v_pstatus'];
			    if($v_pstatus=="20"){//充值成功
			        $signGet = strtoupper($this->getSign('chinabank_return',$_REQUEST));
			        $nid = $this->createnid('chinabank',$_REQUEST['v_oid']);
			        if($_REQUEST['v_md5str']==$signGet){//充值完成
			            $this->success("充值完成",__APP__."/member/");
			        }else{//签名不付
			            $this->error("签名不付",__APP__."/member/");
			        }
			    }else{//充值失败
			        $this->error("充值失败",__APP__."/member/");
			    }
			    break;
			case 'jdpay':
			    $tradeStatus = intval($_REQUEST['tradeStatus']);
			    if($tradeStatus == 0){
			        $this->success("充值完成",__APP__."/member/");
			    }else{
			        $this->error("充值失败",__APP__."/member/");
			    }
			    break;
		case "shengpay" :
			$recode = htmlspecialchars($_REQUEST['TransStatus'], ENT_QUOTES);
			if($recode == "01"){
				$signGet = $this->getSign( "shengpay_return", $_REQUEST );
				$nid = $this->createnid( "shengpay", htmlspecialchars($_REQUEST['OrderNo'], ENT_QUOTES) );
				if ( $_REQUEST['SignMsg'] == $signGet )
				{
					$this->success( "充值完成", __APP__."/member/" );
				}
				else
				{
					$this->error( "签名不付", __APP__."/member/" );
				}
			}else{
				$this->error("充值失败", __APP__."/member/" );
			}
		break;
		case "ecpss":
			$signGet = $this->getSign("ecpss_return", $_REQUEST);
			//if($_REQUEST['MD5info'] == $signGet){
			if(strtoupper($_REQUEST['SignMD5info']) == $signGet){
				$recode = htmlspecialchars($_REQUEST['Succeed'], ENT_QUOTES);
				//if ($recode=="1" || $recode=="9" || $recode=="19" || $recode=="88") {
				if ($recode=="88") {
					$nid = $this->createnid( "ecpss", $_REQUEST['BillNo']);
					$this->success( "充值完成", __APP__."/member/" );
				}else{
					$this->error( "签名不付", __APP__."/member/" );
				}
			}else{
				$this->error("充值失败", __APP__."/member/" );
			}
		break;
		case "tenpay" :
			$recode = htmlspecialchars($_REQUEST['trade_state'], ENT_QUOTES);
			if ($recode == "0" ){
				$signGet = $this->getSign( "tenpay", $this->getRequest( ) );
				$nid = $this->createnid( "tenpay", $_REQUEST['out_trade_no'] );
				if ( strtolower( $_REQUEST['sign'] ) == $signGet )
				{
					$this->success( "充值完成", __APP__."/member/" );
				}
				else
				{
					$this->error( "充值失败", __APP__."/member/" );
				}
			}else{
				$this->error( "充值失败", __APP__."/member/" );
			}
			break;
		case "cmpay":
			$returnCode=$_REQUEST["returnCode"];
			$message=$_REQUEST["message"];
			$mac=$this->getSign("cmpay", $_REQUEST);
			
			if($mac==$_REQUEST["hmac"]){
				if($returnCode==000000){
				$this->success( "充值完成", __APP__."/member/" );
				}else{
					echo $message;
					$this->error( "充值失败", __APP__."/member/" );
				}
			}else{
				$this->error( "签名不付", __APP__."/member/" );
			}
			
			break;
		case "allinpay":
			$payResult = htmlspecialchars($_REQUEST['payResult'], ENT_QUOTES);
			if ( $payResult == "1"){
				$signGet = $this->getSign( "allinpay_return", $_REQUEST );
				if ($signGet){
					$this->success( "充值完成", __APP__."/member/" );
				}else{
					$this->error( "签名不付", __APP__."/member/" );
				}
			}else{
				$this->error( "充值失败", __APP__."/member/" );
			}
		break;
		}
	}
	
	public function paynotice(){
		$payid = intval($_REQUEST['payid']);
		switch($payid){
			case "sumapay":
            $returnCode=$_REQUEST["status"];
            $sign=$this->getSign( "sumapay_return", $_REQUEST);
            $nid = $this->createnid( "sumapay", $_REQUEST['requestId'] );
            if($sign==$_REQUEST["resultSignature"]){
                if($returnCode==2){
                $done = $this->payDone(1,$nid,$_REQUEST['requestId']);
                echo 'SUCCESS';
                }else{
                    $done = $this->payDone(2,$nid,$_REQUEST['requestId']);
					echo "fail";
                }
            }else{
                $done = $this->payDone(3,$nid);
				echo "fail";
            }
            break;
			case 'gfb':
				$recode = htmlspecialchars($_REQUEST['respCode'], ENT_QUOTES);
				if($recode=="0000"){//充值成功
					$signGet = $this->getSign('gfb',$_REQUEST);
					$nid = $this->createnid('gfb',$_REQUEST['merOrderNum']);
					$money = htmlspecialchars($_REQUEST['tranAmt'], ENT_QUOTES);
					if($_REQUEST['signValue']==$signGet){//充值完成
						$done = $this->payDone(1,$nid,$_REQUEST['orderId']);
					}else{//签名不付
						$done = $this->payDone(2,$nid,$_REQUEST['orderId']);
					}
				}else{//充值失败
					$done = $this->payDone(3,$nid);
				}
				if($done===true) echo "ResCode=0000|JumpURL=".$this->member_url;
				else echo "ResCode=9999|JumpURL=".$this->member_url;
			break;
			case 'chinabank':
				$v_pstatus = $_REQUEST['v_pstatus'];
				if($v_pstatus=="20"){//充值成功
					$signGet = strtoupper($this->getSign('chinabank_return',$_REQUEST));
					$nid = $this->createnid('chinabank',$_REQUEST['v_oid']);
					$money = $_REQUEST['v_amount'];
					if($_REQUEST['v_md5str']==$signGet){//充值完成
						$done = $this->payDone(1,$nid,$_REQUEST['v_oid']);
					}else{//签名不付
						$done = $this->payDone(2,$nid,$_REQUEST['v_oid']);
						echo "签名不正确";
					}
				}else{//充值失败
					$done = $this->payDone(3,$nid);
				}
				if($done===true) echo "ok";
				else echo "error";
			break;
			case 'jdpay':
			    $resp = trim($_REQUEST["resp"]);
			    if (null == $resp) {
			        echo "Error";exit;
			    }
			    $wepay = array(
			        'wepay' => array(
			            'merchantNum' => $this->payConfig['jdpay']['MerCode'],
			            'desKey' => $this->payConfig['jdpay']['desKey'],
			            'md5Key' => $this->payConfig['jdpay']['key'],
			            'serverPayUrl' => 'https://plus.jdpay.com/nPay.htm',
			            'serverQueryUrl' => 'https://m.jdpay.com/wepay/query',
			            'serverRefundUrl' => 'https://m.jdpay.com/wepay/refund',
			            'successCallbackUrl' => $this->return_url."?payid=jdpay",
			            'failCallbackUrl' => 'http://www.baidu.com',
			            'notifyUrl' => $this->notice_url."?payid=jdpay",
			            'forPayLayerUrl' => 'http://localhost/pcclient-php/forPayLayer.html',
			        )
			    );
			    $md5Key = get_val_by_key ( "md5Key",$wepay );
			
			    $desKey = get_val_by_key ( "desKey",$wepay );
			    // 解析XML
			    $params = xml_to_array( base64_decode ( $resp ) );
			    $ownSign = generateSign( $params, $md5Key );
			    $params_json = json_encode( $params );
			    if ($params ['SIGN'] [0] == $ownSign) {
			        $decryptArr = decrypt_jdpay ( $params ['DATA'] [0], $desKey );
			        $arr = xml_to_array($decryptArr);
			        $tradeNum = $arr['TRADE']['ID'];
			        $nid = $this->createnid( "jdpay", $tradeNum );
			        $done = $this->payDone(1,$nid,$tradeNum);
			    }else {
			        $done = $this->payDone(2,$nid,$tradeNum);
			    }
			break;
			case "shengpay" :
				$recode = htmlspecialchars($_REQUEST['TransStatus'], ENT_QUOTES);
				if ( $recode == "01" )
				{
					$signGet = $this->getSign( "shengpay_return", $_REQUEST );
					$nid = $this->createnid( "shengpay", $_REQUEST['OrderNo'] );
					if ($_REQUEST['SignMsg'] == $signGet){
						$done = $this->payDone(1,$nid,$_REQUEST['OrderNo']);
					}else{
						$done = $this->payDone(2,$nid,$_REQUEST['OrderNo']);
					}
				}else{
					$done = $this->payDone(3,$nid);
				}
				if($done === true){
					echo "OK";
				}else{
					echo "Error";
				}
			break;
			case "ecpss":
				$signGet = $this->getSign("ecpss_return", $_REQUEST);
				//if($_REQUEST['MD5info'] == $signGet){
				if(strtoupper($_REQUEST['SignMD5info']) == $signGet){
					$recode = htmlspecialchars($_REQUEST['Succeed'], ENT_QUOTES);
					//if ($recode=="1" || $recode=="9" || $recode=="19" || $recode=="88") {
					if ($recode=="88") {
						$nid = $this->createnid( "ecpss", $_REQUEST['BillNo']);
						$done = $this->payDone(1,$nid,$_REQUEST['BillNo']);
					}else{
						$done = $this->payDone(2,$nid,$_REQUEST['BillNo']);
					}
				}else{
					$done = $this->payDone(3,$nid);
				}
			break;
			case "tenpay":
				$recode = htmlspecialchars($_REQUEST['trade_state'], ENT_QUOTES);
				if ($recode == "0"){
					$signGet = $this->getSign("tenpay", $_REQUEST);
					$nid = $this->createnid( "tenpay", $_REQUEST['out_trade_no'] );
					if ( strtolower( $_REQUEST['sign']) == $signGet ){
						$done = $this->payDone(1,$nid,$_REQUEST['transaction_id']);
					}else{
						$done = $this->payDone(2,$nid,$_REQUEST['transaction_id']);
					}
				}else{
					$done = $this->payDone(3,$nid);
				}
				if($done === true){
					echo "success";
				}else{
					echo "fail";
				}
			break;
			case "cmpay":
			$returnCode=$_REQUEST["returnCode"];
			$message=$_REQUEST["message"];
			$mac=$this->getSign( "cmpay", $_REQUEST);
			$nid = $this->createnid( "cmpay", $_REQUEST['orderId'] );
			if($mac==$_REQUEST["hmac"]){
				if($returnCode=='000000'){
				$done = $this->payDone(1,$nid,$_REQUEST['orderId']);
				echo 'SUCCESS';
				}else{
					//echo $message;
					$done = $this->payDone(2,$nid,$_REQUEST['orderId']);
				}
			}else{
			$done = $this->payDone(3,$nid);
			echo "fail";
			}
			break;
			case "allinpay" :
				$payResult = htmlspecialchars($_REQUEST['payResult'], ENT_QUOTES);
				if($payResult == "1"){
					$signGet = $this->getSign( "allinpay_return", $_REQUEST );
					$nid = $this->createnid( "allinpay", $_REQUEST['orderNo'] );
					if ($nid ==$signGet){
						$done = $this->payDone( 1, $nid, $_REQUEST['orderNo'] );
					}else{
						$done = $this->payDone( 2, $nid, $_REQUEST['orderNo'] );
					}
				}else{
					$done = $this->payDone( 3, $nid );
				}
				if ($done){
					$this->success( "充值完成", __APP__."/member/" );
				}else{
					$this->error( "充值失败", __APP__."/member/" );
				}
			break;
		}
	}

	//////////////////////////////////////////融宝支付接口处理方法开始    qin2014-08-25/////////////////////////////
	public function payReapalReturn(){//同步
		$trade_status = $_POST['trade_status'];		//交易状态
		if($trade_status=="TRADE_FINISHED")
		{
			$this->success( "充值完成", __APP__."/member/" );
		}else{		
			$this->error( "交易失败", __APP__."/member/" );
		}
	}
	//异步
	public function payReapalNotice(){
		if(empty($_POST)){//判断提交来的数组是否为空
			return false;
		}else{
			$signGet = $this->getSign("reapal_return",$_POST);
			$trade_no = $_POST['trade_no'];				//融宝支付交易号
			$nid = $this->createnid( "reapal", $_POST['order_no']);	        //获取订单号
			$buyer_email = htmlspecialchars($_REQUEST['buyer_email'], ENT_QUOTES);		//买家融宝支付账号
			$trade_status = $_POST['trade_status'];		//交易状态
			if($signGet==$_REQUEST["sign"]){
				if($trade_status=="TRADE_FINISHED")
				{
					$done = $this->payDone( 1, $nid, $trade_no);
				}else{		
					//支付失败的处理
					$done = $this->payDone( 2, $nid, $trade_no);
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
	}
	//////////////////////////////////////////融宝支付接口处理方法结束    qin2014-08-25/////////////////////////////
	//////////////////////////////////////////新宝付接口处理方法开始    shao2014-01-26/////////////////////////////
	public function paybaofoback(){
		$recode = htmlspecialchars($_REQUEST['Result'], ENT_QUOTES);
		
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
		$recode = htmlspecialchars($_REQUEST['Result'], ENT_QUOTES);
				if ($recode == "1"){
					$signGet = $this->getSign("baofoo_return", $_REQUEST );
					$nid = $this->createnid("baofoo", $_REQUEST['TransID'] );
					if ($_REQUEST['Md5Sign'] == $signGet){
						$done = $this->payDone(1,$nid,$_REQUEST['TransID']);
						echo "OK";
					}else{
						$done = $this->payDone(2,$nid,$_REQUEST['TransID']);
						echo "Fail";
					}
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
	//////////////////////////////////////////新宝付接口处理方法结束    shao2014-01-26///////////////////////////

	////////////////////////////////////////////银生宝支付开始///////////////////////////////////
	public function payunspaynotice(){
		$merchantId = htmlspecialchars($_REQUEST["merchantId"], ENT_QUOTES);
		$merchantKey =$this->payConfig['unspay']['merchantKey'];				//注册商户在银生设置的密钥
		$responseMode = htmlspecialchars($_REQUEST["responseMode"], ENT_QUOTES);
		$orderId = htmlspecialchars($_REQUEST["orderId"], ENT_QUOTES);
		$currencyType = htmlspecialchars($_REQUEST["currencyType"], ENT_QUOTES);
		$amount = htmlspecialchars($_REQUEST["amount"], ENT_QUOTES);
		$returnCode = htmlspecialchars($_REQUEST["returnCode"], ENT_QUOTES);
		$returnMessage = htmlspecialchars($_REQUEST["returnMessage"], ENT_QUOTES);
		$mac = htmlspecialchars($_REQUEST["mac"], ENT_QUOTES);
	
		$success = $returnCode=="0000";
		$paid = $returnCode=="0001";
	
		$s = "merchantId=";
		$s .= $merchantId;
		$s .= "&responseMode=";
		$s .= $responseMode;
		$s .= "&orderId=";
		$s .= $orderId;
		$s .= "&currencyType=";
		$s .= $currencyType;
		$s .= "&amount=";
		$s .= $amount;
		$s .= "&returnCode=";
		$s .= $returnCode;
		$s .= "&returnMessage=";
		$s .= $returnMessage;
		$s .= "&merchantKey=";
		$s .= $merchantKey;
	//md5加密 
		$nowMac = strtoupper(md5($s));
		$nid = $this->createnid("unspay", $_REQUEST['orderId']);
		if($nowMac == $mac){ //若mac校验匹配
			$done = $this->payDone(1,$nid,$_REQUEST['orderId']);
			$this->success( "充值完成", __APP__."/member/" );
		}else{  //若mac校验不匹配
			if($success||$paid){
				$success = false;
				$paid = false;
				$returnCode = "0401";
				$returnMessage = "mac值校验错误！";
				$done = $this->payDone(3,$nid);
				$this->error( "mac值校验错误！", __APP__."/member/" );
			}
		}

	}
	////////////////////////////////////////////银生宝支付结束///////////////////////////////////
	
		////////////////////////////////////环迅主动对账////////////////////////////
	
		public function payipsnotice(){
			$recode = htmlspecialchars($_REQUEST['succ'], ENT_QUOTES);
				if ( $recode == "Y" )
				{
					$signGet = $this->getSign( "ips_return", $_REQUEST );
					$nid = $this->createnid( "ips", $_REQUEST['billno'] );
					if ( $_REQUEST['signature'] == $signGet ){
						$done = $this->payDone( 1, $nid, $_REQUEST['ipsbillno'] );
					}else{
						$done = $this->payDone( 2, $nid, $_REQUEST['ipsbillno'] );
							echo "签名不正确";
					}
				}else{
					$done = $this->payDone( 3, $nid );
				}
				if ( $done === true ){
					echo "ipscheckok";//回复ipscheckok表示已成功接收到该笔订单
				}else{
					echo "交易失败";
				}
		}
	////////////////////////////////////////////////////////////////////////////
	
	////////////////////////////////////////////易生支付接口返回处理方法开始	fan20140114/////////////////////////////
	//易生支付返回客户端处理
	public function payeasypayreturn(){
		if(empty($_POST)){//判断提交来的数组是否为空
			return false;
		}else{
			$signGet = $this->getSign("easypay",$_POST);
			if($signGet==$_POST["sign"]){
				$recode = $_POST['trade_status'];
				if ($recode=="TRADE_FINISHED") {
					$this->success( "充值完成", __APP__."/member/" );
				}else{
					$this->error( "交易失败", __APP__."/member/" );
				}
			}else{
				//验证失败的处理
				$this->error("数字签名不符".$_POST["sign"], __APP__."/member/" );
			}
		}
	}
	//易生支付返回服务器端处理
	public function payeasypaynotice(){
		if(empty($_POST)){//判断提交来的数组是否为空
			return false;
		}else{
			$signGet = $this->getSign("easypay",$_POST);
			$nid = $this->createnid( "easypay", $_POST['out_trade_no']);
			if($signGet==$_POST["sign"]){
				$recode = $_POST['trade_status'];
				if($recode == "TRADE_FINISHED"){
					$done = $this->payDone( 1, $nid, $_POST['out_trade_no']);
				}else{
					$done = $this->payDone( 2, $nid, $_POST['out_trade_no']);
				}
			}else{
				$done = $this->payDone(3,$nid);
			}
			if ( $done === true ){
				echo "success";//回复success表示已成功接收到该笔订单
			}else{
				echo "fail";
			}
		}
	}
////////////////////////////////////////////易生支付接口返回处理方法结束	fan20140114/////////////////////////////

	/////////////////////////////////////////通联支付接口处理方法开始    2014-08-21///////////////////////////
	//通联支付接口
	public function payAllinpayNotice(){
		$payResult = htmlspecialchars($_REQUEST['payResult'], ENT_QUOTES);
		$url = json_encode($_REQUEST);
		if($payResult == "1"){
			$signGet = $this->getSign( "allinpay_return", $_REQUEST );
			$nid = $this->createnid( "allinpay", $_REQUEST['orderNo'], $url );
			if ($signGet){
				$done = $this->payDone( 1, $nid, $_REQUEST['orderNo'], $url );
			}else{
				$done = $this->payDone( 2, $nid, $_REQUEST['orderNo'], $url );
			}
		}else{
			$done = $this->payDone( 3, $nid, $url );
		}
		if ($done){
			$this->success( "充值完成", __APP__."/member/" );
		}else{
			$this->error( "充值失败", __APP__."/member/" );
		}
	}
	//通联支付前台跳转
	public function payAllinpayBack(){
		$payResult = htmlspecialchars($_REQUEST['payResult'], ENT_QUOTES);
		if ( $payResult == "1"){
			$signGet = $this->getSign( "allinpay_return", $_REQUEST );
			if ($signGet){
				$this->success( "充值完成", __APP__."/member/" );
			}else{
				$this->error( "签名不付", __APP__."/member/" );
			}
		}else{
			$this->error( "充值失败", __APP__."/member/" );
		}
		
	}
/////////////////////////////////////////通联支付接口处理方法结束    2014-08-21///////////////////////////

	//////////////////sina-start////////////////////
	public function paysinaback(){
		$recode = htmlspecialchars($_REQUEST['payResult'], ENT_QUOTES);
		
			if($recode == "10"){
				$signGet = $this->getSign( "sina_return", $_REQUEST );
				
				if ( $_REQUEST['signMsg'] == $signGet )
				{
					$this->success( "充值完成", __APP__."/member/" );
				}
				else
				{
					$this->error( "签名不付", __APP__."/member/" );
				}
			}else{
				$this->error(auto_charset($_REQUEST['signMsg']), __APP__."/member/" );
			}
	}
	public function paysinanotice(){
		$recode = htmlspecialchars($_REQUEST['payResult'], ENT_QUOTES);
		//echo $recode;
			$signGet = $this->getSign("return_sina", $_REQUEST );
			//echo $signGet;
			//dump($_REQUEST);
				if ($recode == "10"){
					$nid = $this->createnid("sina", $_REQUEST['orderId'] );
					if ($_REQUEST['signMsg'] == $signGet){
						$done = $this->payDone(1,$nid,$_REQUEST['orderId']);
					}else{
						$done = $this->payDone(2,$nid,$_REQUEST['orderId']);
					}
				}else{
					$done = $this->payDone(3, $nid);
				}
				if($done===true){
					echo "OK";
				}else{
				 	echo "Fail";
				}
	}
	
	///////////sina-end/////////////////////
	
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
	
	private function createnid($type,$static){
			return md5("XXXXX@@#$%".$type.$static);
	}
	
	private function getPaydetail(){
		if(!$this->uid) exit;
		$this->paydetail['money'] = getFloatValue($_GET['t_money'],2);
		$this->paydetail['fee'] = 0;
		$this->paydetail['add_time'] = time();
		$this->paydetail['add_ip'] = get_client_ip();
		$this->paydetail['status'] = 0;
		$this->paydetail['uid'] = $this->uid;
		$this->paydetail['bank'] = strtoupper($_GET['bankCode']);
	}
	
	private function getSign($type,$data){
		$md5str="";
		switch($type){
			case "gfb":
				$signarray=array(
					"version",
					"tranCode",
					"merchantID",
					"merOrderNum",
					"tranAmt",
					"feeAmt",
					"tranDateTime",
					"frontMerUrl",
					"backgroundMerUrl",
					"orderId",
					"gopayOutOrderId",
					"tranIP",
					"respCode",
					"gopayServerTime"//新网关增加新字段
				);
				foreach($signarray as $v){
					if(!isset($data[$v])) $md5str .= "$v=[]";
					else $md5str .= "$v=[$data[$v]]";
				}
				$md5str.="VerficationCode=[".$this->payConfig['guofubao']['VerficationCode']."]";
				$md5str = md5($md5str);
				return $md5str;
			break;
			case "ips" :
				$md5str = "billno".$data['Billno']."currencytype".$data['Currency_Type']."amount".$data['Amount']."date".$data['Date']."orderencodetype".$data['OrderEncodeType'];
				$md5str .= $this->payConfig['ips']['MerKey'];
				$md5str = md5( $md5str );
				return $md5str;
			break;
			case "ips_return" :
				$md5str = "billno".$data['billno']."currencytype".$data['Currency_type']."amount".$data['amount']."date".$data['date']."succ".$data['succ']."ipsbillno".$data['ipsbillno']."retencodetype".$data['retencodetype'];
				$md5str .= $this->payConfig['ips']['MerKey'];
				$md5str = md5( $md5str );
				return $md5str;
			break;
			case "chinabank":
			    $signarray=array(
			    "v_amount",
			    "v_moneytype",
			    "v_oid",
			    "v_mid",
			    "v_url",
			    );
			    foreach($signarray as $v){
			        if(!isset($data[$v])) $md5str .= "";
			        else $md5str .= "$data[$v]";
			    }
			    $md5str.=$this->payConfig['chinabank']['key'];
			    $md5str = md5($md5str);
			    return $md5str;
			break;
			case "chinabank_return":
			    $signarray=array(
			    "v_oid",
			    "v_pstatus",
			    "v_amount",
			    "v_moneytype",
			    );
			    foreach($signarray as $v){
			        if(!isset($data[$v])) $md5str .= "";
			        else $md5str .= "$data[$v]";
			    }
			    $md5str.=$this->payConfig['chinabank']['key'];
			    $md5str = md5($md5str);
			    return $md5str;
			break;
			case "jdpay":
			    $submitdata['version'] = '1.1.5';//版本号
			    $submitdata["currency"] = 'CNY';
			    $submitdata['token'] = '';//token值
			    $submitdata['merchantNum'] = $this->payConfig['jdpay']['MerCode'];
			    $submitdata['merchantRemark'] = '测试商户号';//生产环境-测试商户号
			    $submitdata['tradeNum'] = $data['tradeNum'];//交易号
			    $submitdata['tradeName'] = '快捷支付';
			    $submitdata['tradeDescription'] = '快捷支付';
			    $submitdata['tradeTime'] = date("Y-m-d H:i:s",strtotime($data['tradeTime']));//交易时间
			    $submitdata['tradeAmount'] = $data['tradeAmount'];//交易金额 分为单位
			    $submitdata['notifyUrl'] = $this->notice_url."?payid=jdpay";//异步
			    $submitdata['successCallbackUrl'] = $this->return_url."?payid=jdpay";//同步
			    $submitdata['ip'] = get_client_ip();
			    $sign = signWithoutToHex($submitdata);
			    return $sign;
			break;
			/*case "baofoo"://老宝付支付接口
				$signarray = array( "MerchantID", "PayID", "TradeDate", "TransID", "OrderMoney", "Merchant_url", "Return_url", "NoticeType" );
				foreach ( $signarray as $v )
				{
					$md5str .= $data[$v];
				}
				$md5str .= $this->payConfig['baofoo']['pkey'];
				$md5str = md5( $md5str );
				return $md5str;
			break;
			case "baofoo_return":
				$signarray = array( "MerchantID", "TransID", "Result", "resultDesc", "factMoney", "additionalInfo", "SuccTime" );
				foreach ( $signarray as $v )
				{
					$md5str .= $data[$v];
				}
				$md5str .= $this->payConfig['baofoo']['pkey'];
				$md5str = md5( $md5str );
				return $md5str;
			break;*/
			case "baofoo":
				$signarray = array( "MemberID", "PayID", "TradeDate", "TransID", "OrderMoney", "PageUrl", "ReturnUrl", "NoticeType" );
				foreach ($signarray as $v){
					$md5str .= $data[$v].'|';
				}
				$md5str .= $this->payConfig['baofoo']['pkey'];
                
				$md5str = md5($md5str);
				return $md5str;
			break;
			case "baofoo_return":
				$signarray = array( "MemberID", "TerminalID", "TransID", "Result", "ResultDesc", "FactMoney", "AdditionalInfo",'SuccTime' );
				foreach ($signarray as $v){
					$md5str .= "$v".'='.$data[$v].'~|~';
				}
				//dump($md5str);
				$md5str .= 'Md5Sign='.$this->payConfig['baofoo']['pkey'];
				$md5str = md5( $md5str );
				return $md5str;
			break;
			case "sumapay"://丰付支付
            $signarray=array('requestId','tradeProcess','totalBizType','totalPrice','backurl','returnurl','noticeurl',
                'description');
                foreach($signarray  as $v){
                    $sign.=$data[$v];
                }
                
                $merKey=$this->payConfig['sumapay']['merKey'];
                $signatrue = HmacMd6($sign, $merKey);
            return $signatrue;
            break;
            
            case "sumapay_return":
            $signarray=array('requestId','payId','fiscalDate','description',);
                foreach($signarray  as $v){
                    $sign.=$data[$v];
                }
           
		   	$merKey=$this->payConfig['sumapay']['merKey'];
            $signatrue = HmacMd6($sign, $merKey);
            return $signatrue;
            break;
			case "shengpay":
				$signarray=array(
					'Name',
					'Version',
					'Charset',
					'MsgSender',
					'SendTime',
					'OrderNo',
					'OrderAmount',
					'OrderTime',
					'PayType',
					//'PayChannel', /*（19 储蓄卡，20 信用卡）做直连时，储蓄卡和信用卡需要分开*/
					//'InstCode',  /*银行编码，参看接口文档*/
					'PageUrl',
					'NotifyUrl',
					'ProductName',
					'BuyerContact',
					'BuyerIp',
					'Ext1',
					'Ext2',
					'SignType',
				);
				foreach($signarray as $v){
					if(!isset($data[$v])) $md5str .= "";
					else $md5str .= "$data[$v]";
				}
				$md5str.=$this->payConfig['shengpay']['pkey'];//MD5密钥
				$md5str = strtoupper(md5($md5str));
				return $md5str;
			break;
			case "shengpay_return":
				$signarray=array(
					'Name',
					'Version',
					'Charset',
					'TraceNo',
					'MsgSender',
					'SendTime',
					'InstCode',
					'OrderNo',
					'OrderAmount',
					'TransNo',
					'TransAmount',
					'TransStatus',
					'TransType',
					'TransTime',
					'MerchantNo',
					'ErrorCode',
					'ErrorMsg',
					'Ext1',
					'Ext2',
					'SignType',
				);
				foreach($signarray as $v){
					if(!isset($data[$v])) $md5str .= "";
					else $md5str .= "$data[$v]";
				}
				$md5str.=$this->payConfig['shengpay']['mkey'];
				$md5str = strtoupper(md5($md5str));
				return $md5str;
			break;
			case "tenpay" :
				$signPars = "";
				ksort($data);
				foreach ( $data as $k => $v )
				{
					if ("" != $v && "sign" != $k )
					{
						$signPars .= $k."=".$v."&";
					}
				}
				$signPars .= "key=".$this->payConfig['tenpay']['key'];
				$md5str = strtoupper(md5($signPars));
				return $md5str;
			break;
			case "ecpss":
				$signarray=array('MerNo','BillNo','Amount','ReturnURL');//校验源字符串
				foreach($signarray as $v){
					if(!isset($data[$v])) $md5str .= "";
					else $md5str .= $data[$v];
				}
				
				$md5str.=$this->payConfig['ecpss']['MD5key'];//MD5密钥
				$md5str = strtoupper(md5($md5str));
				return $md5str;
			break;
			case "ecpss_return":
				$signarray = array( "BillNo", "Amount", "Succeed");//校验源字符串
				foreach ($signarray as $v){
					$md5str .= $data[$v]."&";
				}
				$md5str .= $this->payConfig['ecpss']['MD5key'];
				$md5str = strtoupper(md5($md5str));
				return $md5str;
			break;
			case "easypay"://易生支付
				$para = array();
				while (list ($key, $val) = each ($data)){
					if($key == "sign" || $key == "sign_type" || $val == ""){
						continue;
					}else{
						$para[$key] = $data[$key];
					}
				}
				ksort($para);
				reset($para);
				
				$signPars  = "";
				while (list ($key, $val) = each($para)){
					$signPars.=$key."=".$val."&";
				}
				$signPars = substr($signPars,0,count($signPars)-2);	//去掉最后一个&字符
				$signPars .=$this->payConfig['easypay']['key'];
				$md5str =md5($signPars);
				return $md5str;
			break;
			case "cmpay"://中国移动
				$signarray=array('merchantId','payNo','returnCode','message','signType','type','version',
				'amount','amtItem','bankAbbr','mobile','orderId','payDate','accountDate','reserved1',
				'reserved2','status','orderDate','fee');
				foreach($signarray  as $v){
					$mac.=$data[$v];
				}
				
				$signKey=$this->payConfig['cmpay']['serverCert'];
				
				$mac=MD5sign($signKey,$mac);
				return $mac;
			break;
			case "cmpay_return"://中国移动
				foreach($data as $v){
					$mac.=$v;
				}
			
				$signKey=$this->payConfig['cmpay']['serverCert'];
				//MD5方式签名
				$hmac=MD5sign($signKey,$mac);
				return $hmac;
			break;
			case "allinpay":
				$signarray = array( "inputCharset", "pickupUrl", "receiveUrl", "version", "language", "signType", "merchantId", "payerName", "payerEmail", "payerTelephone", "payerIDCard", "pid", "orderNo", "orderAmount", "orderCurrency", "orderDatetime", "orderExpireDatetime", "productName", "productPrice", "productNum", "productId", "productDescription", "ext1", "ext2", "payType", "issuerId", "pan");
				$i = 0;
				foreach($signarray as $v){
					if(0 < $i){
						if($data[$v] !== ""){
							$md5str .= "&{$v}=".$data[$v];
						}
					}else if($data[$v] !== ""){
						$md5str .= "{$v}=".$data[$v];
					}
					++$i;
				}
				$md5str .= "&key=".$this->payConfig['allinpay']['key'];
				$md5str = strtoupper(md5($md5str));
			return $md5str;
			case "allinpay_return":
				$signarray = array( "merchantId", "version", "language", "signType", "payType", "issuerId", "paymentOrderId", "orderNo", "orderDatetime", "orderAmount", "payDatetime", "payAmount", "ext1", "ext2", "payResult", "errorCode", "returnDatetime");
				$i = 0;
				foreach($signarray as $v){
					if(0 < $i){
						if($data[$v] !== ""){
							$md5str .= "&{$v}=".$data[$v];
						}
					}else if($data[$v] !== ""){
						$md5str .= "{$v}=".$data[$v];
					}
					++$i;
				}
				//解析publickey.txt文本获取公钥信息
				require_once( C("APP_ROOT")."Lib/Pay/allinpay/php_rsa.php");
				$publickeyfile = C("APP_ROOT")."Lib/Pay/allinpay/publickey.txt";
				$publickeycontent = file_get_contents($publickeyfile);
				$publickeyarray = explode(PHP_EOL, $publickeycontent);
				$publickey = explode('=',$publickeyarray[0]);
				$modulus = explode('=',$publickeyarray[1]);

				$keylength = 1024;
				$verify_result = rsa_verify($md5str, $data['signMsg'], $publickey[1], $modulus[1], $keylength,"sha1");
				return $verify_result;
			break;
			case "sina"://
			$signarray=array('inputCharset','bgUrl','cancelUrl','version','language','signType','merchantAcctId','payerName','payerContactType','payerContact','payerIdType','payerId','orderId','orderAmount','orderTime','payType','bankId','redoFlag','expiredTime','pid','ip','deviceId');
			foreach($signarray as $v){
				if($v!="signMsg"  && @$data[$v]!="")
				{
				$params_str .= $v."=".$data[$v]."&";
				}
			}
			
			$params_str .= "key=" . @$this->payConfig['sina']['key'];
			$signMsg = strtolower(md5($params_str));
			return $signMsg;
			break;
			case "return_sina":
			$signarray=array('merchantAcctId','version','language','signType','payType','bankId','orderId','orderTime','orderAmount','dealId','bankDealId','dealTime','payAmount','fee','ext1','ext2','payResult','payIp');

			foreach($signarray as $v){
			if($v!="signMsg"  && @$data[$v]!="")
			{
				$params_str .= $v."=".$data[$v]."&";
			}
		}
		
		$params_str .= "key=" . @$this->payConfig['sina']['key'];
		$signMsg = strtolower(md5($params_str));
		return $signMsg;
		break;
		case "reapal_return"://融宝支付
				$this->parameter = para_filter($data);
				$this->_key = $this->payConfig['reapal']['MD5key'];
				//设定charset的值,为空值的情况下默认为GBK
		        if($parameter['charset'] == '')
				{
		            $this->parameter['charset'] = 'utf-8';
				}
		        $this->charset   = $this->parameter['charset'];

		        //获得签名结果
		        $sort_array   = arg_sort($this->parameter);    //得到从字母a到z排序后的签名参数数组
		        $mysign = build_mysign($sort_array,$this->_key);
				return $mysign;
			break;
		}
	}
	
	private function create($data,$submitUrl){
		$inputstr = "";
		foreach($data as $key=>$v){
			$inputstr .= '
		<input type="hidden"  id="'.$key.'" name="'.$key.'" value="'.$v.'"/>
		';
		}
		
		$form = '
		<form action="'.$submitUrl.'" name="pay" id="pay" method="POST">
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
        $html.=	$form;
        $html.=	'
        <script type="text/javascript">
			document.getElementById("pay").submit();
		</script>
        ';
        $html.= '
        </body>
</html>
		';
				 
		Mheader('utf-8');
		echo $html;
		exit;
	}
}
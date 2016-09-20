<?php
import("@.Pay.Abstract");
import("@.Pay.Tenpay.RequestHandler");
import("@.Pay.Tenpay.ResponseHandler");
import("@.Pay.Tenpay.ClientResponseHandler");
import("@.Pay.Tenpay.TenpayHttpClient");
class Tenpay extends paymentabstract{
	public function __construct($config = array()) {	
		if (!empty($config)) $this->set_config($config);
        
		$this->config['gateway_url'] = 'https://gw.tenpay.com/gateway/pay.htm';
		$this->config['gateway_method'] = 'POST';
		//$this->config['notify_url'] = return_url('tenpay',1);
		//$this->config['return_url'] = return_url('tenpay');
		$this->config['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/Pay/paynotice";
		$this->config['return_url'] = "http://".$_SERVER['HTTP_HOST']."/Pay/payreturn";
		$this->payConfig = FS("Webconfig/payconfig");
	}

	public function getpreparedata() {
		/* 创建支付请求对象 */
		$reqHandler = new RequestHandler();
		$reqHandler->init();
		$reqHandler->setKey($this->config['key']);
		$reqHandler->setGateUrl('https://gw.tenpay.com/gateway/pay.htm');

		//设置支付参数 
		$reqHandler->setParameter("partner", $this->config['partner']);
		$reqHandler->setParameter("out_trade_no", $this->order_info['id']);
		$reqHandler->setParameter("total_fee", ($this->product_info['price']*100));  //总金额
		$reqHandler->setParameter("return_url",  $this->config['return_url']);
		$reqHandler->setParameter("notify_url", $this->config['notify_url']);
		$reqHandler->setParameter("body", $this->product_info['body']);
		$reqHandler->setParameter("bank_type", "DEFAULT");  	  //银行类型，默认为财付通
		//用户ip
		$reqHandler->setParameter("spbill_create_ip", $_SERVER['REMOTE_ADDR']);//客户端IP
		$reqHandler->setParameter("fee_type", "1");               //币种
		$reqHandler->setParameter("subject",$this->product_info['name']);          //商品名称，（中介交易时必填）

		//系统可选参数
		$reqHandler->setParameter("sign_type", "MD5");  	 	  //签名方式，默认为MD5，可选RSA
		$reqHandler->setParameter("service_version", "1.0"); 	  //接口版本号
		$reqHandler->setParameter("input_charset", "UTF-8");   	  //字符集
		$reqHandler->setParameter("sign_key_index", "1");    	  //密钥序号

		//业务可选参数
		$reqHandler->setParameter("buyer_id", $this->order_info['buyer_email']);                //买方财付通帐号
		$reqHandler->setParameter("trade_mode", $this->config['service']);              //交易模式（1.即时到帐模式，2.中介担保模式，3.后台选择（卖家进入支付中心列表选择））
		$reqHandler->setParameter("trans_type", 2);                //1 实物，2 虚拟

		//请求的URL
		$reqUrl = $reqHandler->getRequestURL();
		$tmp = parse_url($reqUrl);
		parse_str($tmp['query'], $prepare_data);

		return $prepare_data;
	}
	
	/**
	 * GET接收数据
	 * 状态码说明  （0 交易完成 1 交易失败 2 交易超时 3 交易处理中 4 交易未支付 5交易取消 6交易发生错误）
	 */
    public function receive() {
		header("Content-type: text/html; charset=utf-8"); 
		$resHandler = new ResponseHandler();
		$resHandler->setKey($this->config['key']);
		if($resHandler->isTenpaySign()) {
			//支付结果
			$trade_state = $resHandler->getParameter("trade_state");
			//交易模式,1即时到账
			$trade_mode = $resHandler->getParameter("trade_mode");

			$return_data['order_id'] = $resHandler->getParameter("out_trade_no");
			$return_data['price'] = dround($resHandler->getParameter("total_fee")/100);
			$return_data['account'] = $resHandler->getParameter("partner");
			
			if("1" == $trade_mode ) {
				if( "0" == $trade_state){ 
					$return_data['order_status'] = 0;
				} else {
					$return_data['order_status'] = 1;
				}
			}elseif( "2" == $trade_mode  ) {
				switch ($trade_state)
				{	
					case 0: $return_data['order_status'] = 3; break;
					case 1: $return_data['order_status'] = 4; break;
					case 2: $return_data['order_status'] = 4; break;
					case 4: $return_data['order_status'] = 3; break;
					case 5: $return_data['order_status'] = 0; break;
					case 6: $return_data['order_status'] = 5; break;
					case 7: $return_data['order_status'] = 3; break;
					case 8: $return_data['order_status'] = 3; break;
					case 9: $return_data['order_status'] = 5; break;
					case 10: $return_data['order_status'] = 5; break;
					default:
						$return_data['order_status'] = 5;
				}			
			}
			return $return_data;
		} else {
			echo "<br/>" . "认证签名失败" . "<br/>";
			echo $resHandler->getDebugInfo() . "<br>";
			error_log(date('m-d H:i:s').'| GET: '.$resHandler->getDebugInfo().' |'."\r\n", 3, LOG_PATH.'tenpay_error_log.php');
			exit;
			return false;
		}    	  	
    }	

    /**
	 * POST接收数据
	 * 状态码说明  （0 交易完成 1 交易失败 2 交易超时 3 交易处理中 4 交易未支付 5交易取消 6交易发生错误）
	 */
    public function notify() {
		/* 创建支付应答对象 */
		$resHandler = new ResponseHandler();
		$resHandler->setKey($this->config['key']);

		//判断签名
		if($resHandler->isTenpaySign()) {
			//通知id
			$notify_id = $resHandler->getParameter("notify_id");
		
			//通过通知ID查询，确保通知来至财付通
			//创建查询请求
			$queryReq = new RequestHandler();
			$queryReq->init();
			$queryReq->setKey($this->config['key']);
			$queryReq->setGateUrl("https://gw.tenpay.com/gateway/simpleverifynotifyid.xml");
			$queryReq->setParameter("partner", $this->config['partner']);
			$queryReq->setParameter("notify_id", $notify_id);
			
			//通信对象
			$httpClient = new TenpayHttpClient();
			$httpClient->setTimeOut(5);
			//设置请求内容
			$httpClient->setReqContent($queryReq->getRequestURL());
	
			//后台调用
			if($httpClient->call()) {
				//设置结果参数
				$queryRes = new ClientResponseHandler();
				$queryRes->setContent($httpClient->getResContent());
				$queryRes->setKey($this->config['key']);
				if($resHandler->getParameter("trade_mode") == "1"){
					//判断签名及结果（即时到帐）
					//只有签名正确,retcode为0，trade_state为0才是支付成功
					if($queryRes->isTenpaySign() && $queryRes->getParameter("retcode") == "0" && $resHandler->getParameter("trade_state") == "0") {
						$return_data['order_id'] = $resHandler->getParameter("out_trade_no");
						$return_data['price'] = dround($resHandler->getParameter("total_fee")/100);
						$return_data['account'] = $resHandler->getParameter("partner");
						$return_data['order_status'] = 0;
						return $return_data;
					} else {
						//错误时，返回结果可能没有签名，写日志trade_state、retcode、retmsg看失败详情。
						//echo "验证签名失败 或 业务错误信息:trade_state=" . $resHandler->getParameter("trade_state") . ",retcode=" . $queryRes-> getParameter("retcode"). ",retmsg=" . $queryRes->getParameter("retmsg") . "<br/>" ;
						error_log(date('m-d H:i:s').'| POST: '.$queryRes->getParameter("retmsg").' |'."\r\n", 3, LOG_PATH.'tenpay_error_log.php');
						return false;
					}
				} elseif ($resHandler->getParameter("trade_mode") == "2") {
					//判断签名及结果（中介担保）
					//只有签名正确,retcode为0，trade_state为0才是支付成功
					if($queryRes->isTenpaySign() && $queryRes->getParameter("retcode") == "0" ) 
					{
						$return_data['order_id'] = $resHandler->getParameter("out_trade_no");
						$return_data['price'] = dround($resHandler->getParameter("total_fee")/100);
						$return_data['account'] = $resHandler->getParameter("partner");
						switch ($resHandler->getParameter("trade_state")) {
							case 0: $return_data['order_status'] = 3; break;
							case 1: $return_data['order_status'] = 4; break;
							case 2: $return_data['order_status'] = 4; break;
							case 4: $return_data['order_status'] = 3; break;
							case 5: $return_data['order_status'] = 0; break;
							case 6: $return_data['order_status'] = 5; break;
							case 7: $return_data['order_status'] = 3; break;
							case 8: $return_data['order_status'] = 3; break;
							case 9: $return_data['order_status'] = 5; break;
							case 10: $return_data['order_status'] = 5; break;
							default:
								$return_data['order_status'] = 5;
						}
						return $return_data;
					} else {
						//错误时，返回结果可能没有签名，写日志trade_state、retcode、retmsg看失败详情。
						//echo "验证签名失败 或 业务错误信息:trade_state=" . $resHandler->getParameter("trade_state") . ",retcode=" . $queryRes->             										       getParameter("retcode"). ",retmsg=" . $queryRes->getParameter("retmsg") . "<br/>" ;
						error_log(date('m-d H:i:s').'| POST: '.$queryRes->getParameter("retmsg").' |'."\r\n", 3, LOG_PATH.'tenpay_error_log.php');
						return false;
					}
				}
			//获取查询的debug信息,建议把请求、应答内容、debug信息，通信返回码写入日志，方便定位问题
			/*
				echo "<br>------------------------------------------------------<br>";
				echo "http res:" . $httpClient->getResponseCode() . "," . $httpClient->getErrInfo() . "<br>";
				echo "query req:" . htmlentities($queryReq->getRequestURL(), ENT_NOQUOTES, "GB2312") . "<br><br>";
				echo "query res:" . htmlentities($queryRes->getContent(), ENT_NOQUOTES, "GB2312") . "<br><br>";
				echo "query reqdebug:" . $queryReq->getDebugInfo() . "<br><br>" ;
				echo "query resdebug:" . $queryRes->getDebugInfo() . "<br><br>";
			*/
			} else {
				error_log(date('m-d H:i:s').'| POST: '.$httpClient->getErrInfo().' |'."\r\n", 3, LOG_PATH.'tenpay_error_log.php');
				return false;
			} 
		} else {
			error_log(date('m-d H:i:s').'| POST: '.$resHandler->getDebugInfo().' |'."\r\n", 3, LOG_PATH.'tenpay_error_log.php');
			return false;
		}
	}
    	
    /**
     * 相应服务器应答状态
     * @param $result
     */
    public function response($result) {
    	if (FALSE == $result) echo 'fail';
		else echo 'success';
    }
    
    /**
     * 返回字符过滤
     * @param $parameter
     */
	private function filterParameter($parameter)
	{
		$para = array();
		foreach ($parameter as $key => $value)
		{
			if ('' == $value || 'm' == $key  || 'a' == $key  || 'g' == $key   || 'payid' == $key || '_URL_' == $key) continue;
			else $para[$key] = $value;
		}
		return $para;
	}
}
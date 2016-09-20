<?php
import("@.Pay.Abstract");
class Alipay extends paymentabstract{
	public function __construct($config = array()) {	
		if (!empty($config)) $this->set_config($config);
		
	    if ($this->config['service']==1) $this->config['service'] = 'trade_create_by_buyer';
		elseif($this->config['service']==2) $this->config['service'] = 'create_direct_pay_by_user';
        else $this->config['service'] = 'create_partner_trade_by_buyer';
        
		$this->config['gateway_url'] = 'https://www.alipay.com/cooperate/gateway.do?_input_charset=utf-8';
		$this->config['gateway_method'] = 'POST';
		$this->config['notify_url'] = return_url('alipay',1);
		$this->config['return_url'] = return_url('alipay');
	}

	public function getpreparedata() {
		$prepare_data['service'] = $this->config['service'];
		$prepare_data['payment_type'] = '1';
		$prepare_data['seller_email'] = $this->config['account'];
		$prepare_data['partner'] = $this->config['pid'];
		$prepare_data['_input_charset'] = 'utf-8';		
		$prepare_data['notify_url'] = $this->config['notify_url'];
		$prepare_data['return_url'] = $this->config['return_url'];		
		
		// 商品信息
		$prepare_data['subject'] = $this->product_info['name'];
		$prepare_data['price'] = $this->product_info['price'];
		if (array_key_exists('url', $this->product_info)) $prepare_data['show_url'] = $this->product_info['url'];
		$prepare_data['body'] = $this->product_info['body'];
		
		//订单信息
		$prepare_data['out_trade_no'] = $this->order_info['id'];
		$prepare_data['quantity'] = $this->order_info['quantity'];

		// 物流信息
		if($this->config['service'] == 'create_partner_trade_by_buyer' || $this->config['service'] == 'trade_create_by_buyer') {
			$prepare_data['logistics_type'] = 'EXPRESS';
			$prepare_data['logistics_fee'] = '0.00';
			$prepare_data['logistics_payment'] = 'SELLER_PAY';
		}
		//买家信息
		$prepare_data['buyer_email'] = $this->order_info['buyer_email'];
		
		$prepare_data = arg_sort($prepare_data);
		// 数字签名
		$prepare_data['sign'] = build_mysign($prepare_data,$this->config['key'],'MD5');
		return $prepare_data;
	}
	
	/**
	 * GET接收数据
	 * 状态码说明  （0 交易完成 1 交易失败 2 交易超时 3 交易处理中 4 交易未支付 5交易取消 6交易发生错误）
	 */
    public function receive() {
		header("Content-type: text/html; charset=utf-8"); 
    	$receive_sign = $_GET['sign'];
    	$receive_data = $this->filterParameter($_GET);
    	$receive_data = arg_sort($receive_data);
    	if ($receive_data) {
			$verify_result = $this->get_verify('http://notify.alipay.com/trade/notify_query.do?partner=' . $this->config['pid'] . '&notify_id=' . $receive_data['notify_id']);
			if (preg_match('/true$/i', $verify_result))
			{
				$sign = '';
				$sign = build_mysign($receive_data,$this->config['key'],'MD5');				
				if ($sign != $receive_sign)
				{
					error_log(date('m-d H:i:s').'| GET: signature is bad |'."\r\n", 3, LOG_PATH.'alipay_error_log.php');					
					exit('签名错误');
					return false;
				}
				else
				{
					$return_data['order_id'] = $receive_data['out_trade_no'];
					$return_data['order_total'] = $receive_data['total_fee'];
					$return_data['price'] = $receive_data['price'];
					$return_data['account'] = $receive_data['seller_email'];
					switch ($receive_data['trade_status'])
					{
						case 'WAIT_BUYER_PAY': $return_data['order_status'] = 3; break;
						case 'WAIT_SELLER_SEND_GOODS': $return_data['order_status'] = 3; break;
						case 'WAIT_BUYER_CONFIRM_GOODS': $return_data['order_status'] = 3; break;
						case 'TRADE_CLOSED': $return_data['order_status'] = 5; break;						
						case 'TRADE_FINISHED': $return_data['order_status'] = 0; break;
						case 'TRADE_SUCCESS': $return_data['order_status'] = 0; break;
						default:
							 $return_data['order_status'] = 5;						
					}			
					return $return_data;
				}

			}
			else
			{
				error_log(date('m-d H:i:s').'| GET: illegality notice : flase |'."\r\n", 3, LOG_PATH.'alipay_error_log.php');
				exit('通知错误');
				return false;
			}
		} else {
			error_log(date('m-d H:i:s').'| GET: no return |'."\r\n", 3, LOG_PATH.'alipay_error_log.php');
			exit('信息返回错误');
			return false;
		}   	
    }	

    /**
	 * POST接收数据
	 * 状态码说明  （0 交易完成 1 交易失败 2 交易超时 3 交易处理中 4 交易未支付 5交易取消 6交易发生错误）
	 */
    public function notify() {
    	$receive_sign = $_POST['sign'];
    	$receive_data = $this->filterParameter($_POST);
    	$receive_data = arg_sort($receive_data);
    	if ($receive_data) {
			$verify_result = $this->get_verify('http://notify.alipay.com/trade/notify_query.do?service=notify_verify&partner=' . $this->config['pid'] . '&notify_id=' . $receive_data['notify_id']);
			//$verify_result = $this->get_verify('https://mapi.alipay.com/gateway.do?service=notify_verify&partner=' . $this->config['pid'] . '&notify_id=' . $receive_data['notify_id']);
			if (preg_match('/true$/i', $verify_result))
			{
				$sign = '';
				$sign = build_mysign($receive_data,$this->config['key'],'MD5');				
				if ($sign != $receive_sign)
				{
					error_log(date('m-d H:i:s').'| POST: signature is bad |'."\r\n", 3, LOG_PATH.'alipay_error_log.php');					
					return false;
				}
				else
				{
					$return_data['order_id'] = $receive_data['out_trade_no'];
					$return_data['order_total'] = $receive_data['total_fee'];
					$return_data['price'] = $receive_data['price'];
					$return_data['account'] = $receive_data['seller_email'];
					switch ($receive_data['trade_status']) {
						case 'WAIT_BUYER_PAY': $return_data['order_status'] = 3; break;
						case 'WAIT_SELLER_SEND_GOODS': $return_data['order_status'] = 3; break;
						case 'WAIT_BUYER_CONFIRM_GOODS': $return_data['order_status'] = 3; break;
						case 'TRADE_CLOSED': $return_data['order_status'] = 5; break;						
						case 'TRADE_FINISHED': $return_data['order_status'] = 0; break;
						case 'TRADE_SUCCESS': $return_data['order_status'] = 0; break;
						default:
							 $return_data['order_status'] = 5;
					}
					return $return_data;
				}

			}
			else
			{
				error_log(date('m-d H:i:s').'| POST: illegality notice : flase |'."\r\n", 3, LOG_PATH.'alipay_error_log.php');
				return false;
			}
		} else {
			error_log(date('m-d H:i:s').'| POST: no post return |'."\r\n", 3, LOG_PATH.'alipay_error_log.php');
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
			if ('sign' == $key || 'sign_type' == $key || '' == $value || 'm' == $key  || 'a' == $key  || 'g' == $key   || 'payid' == $key || '_URL_' == $key) continue;
			else $para[$key] = $value;
		}
		return $para;
	}
}
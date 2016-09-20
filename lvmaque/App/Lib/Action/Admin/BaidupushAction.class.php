<?php

require_once APP_PATH."Lib/Sdk/Channel.class.php";


class BaidupushAction extends ACommonAction {
	
	 public $apiKey='';
     public $secretKey='';
	 public function __construct(){
	   $msgconfig = FS("Webconfig/msgconfig");
	   $this->apiKey = $msgconfig['baidu']['apiKey'];
	   $this->secretKey = $msgconfig['baidu']['secretKey']; 
	   parent::__construct();
    }
	// 请开发者设置自己的apiKey与secretKey
	
	//var $apiKey = "k5mt8cZINNQI4Cl8gIqMAnnO";
//	var $secretKey = "PychX6ID9fYt6DtFMmvMavxnP7xDS9kR";
    public function index()
    { 
        $this->display();
    }
	

	function test_queryBindList ($userId) {
		$apiKey = $this->apiKey;
		$secretKey = $this->secretKey;
		
		$channel = new Channel ($apiKey, $secretKey) ; 
		// $optional [ Channel::CHANNEL_ID ] = "3915728604212165383";
		$ret = $channel -> queryBindList ($userId, $optional) ;
		if (false === $ret) {
			error_output ('WRONG, ' . __FUNCTION__ . ' ERROR!!!!!') ;
			error_output ('ERROR NUMBER: ' . $channel -> errno ()) ;
			error_output ('ERROR MESSAGE: ' . $channel -> errmsg ()) ;
			error_output ('REQUEST ID: ' . $channel -> getRequestId ());
		} else {
			right_output ('SUCC, ' . __FUNCTION__ . ' OK!!!!!') ;
			right_output ('result: ' . print_r ($ret, true)) ;
		} 
	} 

	function test_verifyBind ($userId) {
		$apiKey = $this->apiKey;
		$secretKey = $this->secretKey;
		
		$channel = new Channel ($apiKey, $secretKey) ; 
		// $optional [ Channel::CHANNEL_ID ] = 2484515682371722163;
		$ret = $channel -> verifyBind ($userId, $optional) ;
		if (false === $ret) {
			error_output ('WRONG, ' . __FUNCTION__ . ' ERROR!!!!!') ;
			error_output ('ERROR NUMBER: ' . $channel -> errno ()) ;
			error_output ('ERROR MESSAGE: ' . $channel -> errmsg ()) ;
			error_output ('REQUEST ID: ' . $channel -> getRequestId ());
		} else {
			right_output ('SUCC, ' . __FUNCTION__ . ' OK!!!!!') ;
			right_output ('result: ' . print_r ($ret, true)) ;
		} 
	} 
	// 推送android设备消息
	function push_message_android() {
		$msgconfig = FS("Webconfig/baiduconfig");
		$this->apiKey = $msgconfig['baidu']['apk_apiKey'];
		$this->secretKey = $msgconfig['baidu']['apk_secretKey']; 
		$apiKey = $this->apiKey;
		$secretKey = $this->secretKey;

		$user_id = "689325681215696567";
		$channel = new Channel ($apiKey, $secretKey) ; 
		// 推送消息到某个user，设置push_type = 1;
		// 推送消息到一个tag中的全部user，设置push_type = 2;
		// 推送消息到该app中的全部user，设置push_type = 3;
		$push_type = 1; //推送单播消息
		$optional[Channel :: USER_ID] = $user_id; //如果推送单播消息，需要指定user 
		// optional[Channel::TAG_NAME] = "xxxx";  //如果推送tag消息，需要指定tag_name
		// 指定发到android设备
		$optional[Channel :: DEVICE_TYPE] = 3; 
		// 指定消息类型为通知
		$optional[Channel :: MESSAGE_TYPE] = 1; 
		// 通知类型的内容必须按指定内容发送，示例如下：

		$data['title'] = $_POST['title'];
		$data['description'] = $_POST['description'];
		$data['custom_content']['noticeid'] = $_POST['noticeid'];
		$data['custom_content']['noticetitle'] = $_POST['noticetitle'];


		$message = json_encode($data);
//		$message = '{ 
//			"title": "test_push",
//			"description": "open url",
//			"custom_content": {
//	            "noticeid":"noticeid", 
//	            "noticetitle":"noticetitle"
//	            }, 
//		}';

		$message_key = "msg_key";
//		$ret = $channel -> pushMessage ($push_type, $message, $message_key, $optional) ;
		$push_type = 3;
        $ret = $channel->pushMessage($push_type, $message, $message_key, $optional);
		if (false === $ret) {
			//error_output ('WRONG, ' . __FUNCTION__ . ' ERROR!!!!!') ;
			//error_output ('ERROR NUMBER: ' . $channel -> errno ()) ;
			//error_output ('ERROR MESSAGE: ' . $channel -> errmsg ()) ;
			//error_output ('REQUEST ID: ' . $channel -> getRequestId ());
			//$out['message'] = 'WRONG, ' . __FUNCTION__ . ' ERROR!!!!!<br>ERROR NUMBER: ' . $channel -> errno () . '<br>ERROR MESSAGE: ' . $channel -> errmsg ().'<br>REQUEST ID: ' . $channel -> getRequestId ();
		// $out['message'] = $message;
		 //ajaxmsg($out,0);
		return 0;
		} else {
			//right_output ('SUCC, ' . __FUNCTION__ . ' OK!!!!!') ;
		//	right_output ('result: ' . print_r ($ret, true)) ;
			//$out['message'] = "SUCC, " . __FUNCTION__ . " OK!!!!!<br>result: " . print_r ($ret, true);
			//$out['message'] = $message;
		//	ajaxmsg($out);

		return 1;
			
		}
		 
		
	} 
	// 推送ios设备消息
	function  push_message_ios () {
        $msgcontent=$_POST['description'];
		$msgid=$_POST['noticeid'];
        $msgmobile=$_POST['mobilemodle']; 
		$msgmobile=empty($msgmobile)?1:$msgmobile;
		if(empty($msgcontent)){
		  return 0;
		
		}
		
		//$user_id = "689325681215696567";
		$msgconfig = FS("Webconfig/baiduconfig");
		$this->apiKey = $msgconfig['baidu']['ios_apiKey'];
		$this->secretKey = $msgconfig['baidu']['ios_secretKey']; 
		$apiKey = $this->apiKey;
		$secretKey = $this->secretKey;
		
		$channel = new Channel ($apiKey, $secretKey) ;

		$push_type = 3; //推送单播消息 1：向单用户发送，3：实现群发

		$optional[Channel :: USER_ID] = $user_id; //如果推送单播消息，需要指定user
		 
		// 指定发到ios设备
		$optional[Channel :: DEVICE_TYPE] = 4; 
		// 指定消息类型为通知
		$optional[Channel :: MESSAGE_TYPE] = 1; 
		// 如果ios应用当前部署状态为开发状态，指定DEPLOY_STATUS为1，默认是生产状态，值为2.
		// 旧版本曾采用不同的域名区分部署状态，仍然支持。
		$optional[Channel :: DEPLOY_STATUS] = $msgmobile; 
		// 通知类型的内容必须按指定内容发送，示例如下：
	    $message = array(
			"aps" => array( 
			"alert" => "$msgcontent",
			"badge" => 1,
			"sound" => "default" // 提示音，需要在Xcode工程中添加同名的音频资源
		    
		 ),
			 "noticeid"=>"$msgid"
		 );

		$message_key = "msg_key";
		$ret = $channel -> pushMessage ($push_type, $message, $message_key, $optional) ;
		if (false === $ret) {
			//error_output ('WRONG, ' . __FUNCTION__ . ' ERROR!!!!!') ;
			//error_output ('ERROR NUMBER: ' . $channel -> errno ()) ;
			//error_output ('ERROR MESSAGE: ' . $channel -> errmsg ()) ;
			//error_output ('REQUEST ID: ' . $channel -> getRequestId ());
            //$out['message'] = $message;
			//ajaxmsg($out,0);
			return 0;

		} else {
			//right_output ('SUCC, ' . __FUNCTION__ . ' OK!!!!!') ;
			//right_output ('result: ' . print_r ($ret, true)) ;
			//$out['message'] = $message;
			//ajaxmsg($out);
			return 1;
		} 
	} 

	function test_fetchMessageCount ($userId) {
		$apiKey = $this->apiKey;
		$secretKey = $this->secretKey;
		
		$channel = new Channel ($apiKey, $secretKey) ;
		$ret = $channel -> fetchMessageCount ($userId) ;
		if (false === $ret) {
			error_output ('WRONG, ' . __FUNCTION__ . ' ERROR!!!!!') ;
			error_output ('ERROR NUMBER: ' . $channel -> errno ()) ;
			error_output ('ERROR MESSAGE: ' . $channel -> errmsg ()) ;
			error_output ('REQUEST ID: ' . $channel -> getRequestId ());
		} else {
			right_output ('SUCC, ' . __FUNCTION__ . ' OK!!!!!') ;
			right_output ('result: ' . print_r ($ret, true)) ;
		} 
	} 

	function test_fetchMessage ($userId) {
		$apiKey = $this->apiKey;
		$secretKey = $this->secretKey;
		
		$channel = new Channel ($apiKey, $secretKey) ;
		$ret = $channel -> fetchMessage ($userId) ;
		if (false === $ret) {
			error_output ('WRONG, ' . __FUNCTION__ . ' ERROR!!!!!') ;
			error_output ('ERROR NUMBER: ' . $channel -> errno ()) ;
			error_output ('ERROR MESSAGE: ' . $channel -> errmsg ()) ;
			error_output ('REQUEST ID: ' . $channel -> getRequestId ());
		} else {
			right_output ('SUCC, ' . __FUNCTION__ . ' OK!!!!!') ;
			right_output ('result: ' . print_r ($ret, true)) ;
		} 
	} 

	function test_deleteMessage ($userId, $msgIds) {
		$apiKey = $this->apiKey;
		$secretKey = $this->secretKey;
		
		$channel = new Channel ($apiKey, $secretKey) ; 
		// $optional [ Channel::CHANNEL_ID ] = 4152049051604943232;
		$ret = $channel -> deleteMessage ($userId, $msgIds, $optional) ;
		if (false === $ret) {
			error_output ('WRONG, ' . __FUNCTION__ . ' ERROR!!!!!') ;
			error_output ('ERROR NUMBER: ' . $channel -> errno ()) ;
			error_output ('ERROR MESSAGE: ' . $channel -> errmsg ()) ;
			error_output ('REQUEST ID: ' . $channel -> getRequestId ());
		} else {
			right_output ('SUCC, ' . __FUNCTION__ . ' OK!!!!!') ;
			right_output ('result: ' . print_r ($ret, true)) ;
		} 
	} 

	function test_setTag($tag_name, $user_id) {
		$apiKey = $this->apiKey;
		$secretKey = $this->secretKey;
		
		$channel = new Channel($apiKey, $secretKey);
		$optional[Channel :: USER_ID] = $user_id;
		$ret = $channel -> setTag($tag_name, $optional);
		if (false === $ret) {
			error_output ('WRONG, ' . __FUNCTION__ . ' ERROR!!!!!') ;
			error_output ('ERROR NUMBER: ' . $channel -> errno ()) ;
			error_output ('ERROR MESSAGE: ' . $channel -> errmsg ()) ;
			error_output ('REQUEST ID: ' . $channel -> getRequestId ());
			return false;
		} else {
			right_output ('SUCC, ' . __FUNCTION__ . ' OK!!!!!') ;
			right_output ('result: ' . print_r ($ret, true)) ;
			return $ret['response_params']['tid'];
		} 
	} 

	function test_fetchTag($tag_name = null) {
		$apiKey = $this->apiKey;
		$secretKey = $this->secretKey;
		
		$channel = new Channel($apiKey, $secretKey);
		$optional[Channel :: TAG_NAME] = $tag_name;
		$ret = $channel -> fetchTag($optional);
		if (false === $ret) {
			error_output ('WRONG, ' . __FUNCTION__ . ' ERROR!!!!!') ;
			error_output ('ERROR NUMBER: ' . $channel -> errno ()) ;
			error_output ('ERROR MESSAGE: ' . $channel -> errmsg ()) ;
			error_output ('REQUEST ID: ' . $channel -> getRequestId ());
		} else {
			right_output ('SUCC, ' . __FUNCTION__ . ' OK!!!!!') ;
			right_output ('result: ' . print_r ($ret, true)) ;
		} 
	} 

	function test_deleteTag($tag_name) {
		$apiKey = $this->apiKey;
		$secretKey = $this->secretKey;
		
		$channel = new Channel($apiKey, $secretKey);
		$ret = $channel -> deleteTag($tag_name);
		if (false === $ret) {
			error_output ('WRONG, ' . __FUNCTION__ . ' ERROR!!!!!') ;
			error_output ('ERROR NUMBER: ' . $channel -> errno ()) ;
			error_output ('ERROR MESSAGE: ' . $channel -> errmsg ()) ;
			error_output ('REQUEST ID: ' . $channel -> getRequestId ());
		} else {
			right_output ('SUCC, ' . __FUNCTION__ . ' OK!!!!!') ;
			right_output ('result: ' . print_r ($ret, true)) ;
		} 
	} 

	function test_queryUserTags($user_id) {
		$apiKey = $this->apiKey;
		$secretKey = $this->secretKey;
		
		$channel = new Channel($apiKey, $secretKey);
		$ret = $channel -> queryUserTags($user_id);
		if (false === $ret) {
			error_output ('WRONG, ' . __FUNCTION__ . ' ERROR!!!!!') ;
			error_output ('ERROR NUMBER: ' . $channel -> errno ()) ;
			error_output ('ERROR MESSAGE: ' . $channel -> errmsg ()) ;
			error_output ('REQUEST ID: ' . $channel -> getRequestId ());
		} else {
			right_output ('SUCC, ' . __FUNCTION__ . ' OK!!!!!') ;
			right_output ('result: ' . print_r ($ret, true)) ;
		} 
	} 

	function test_initAppIoscert ($name, $description, $release_cert, $dev_cert) {
		$apiKey = $this->apiKey;
		$secretKey = $this->secretKey;
		
		$channel = new Channel ($apiKey, $secretKey) ; 
		// 如果ios应用当前部署状态为开发状态，指定DEPLOY_STATUS为1，默认是生产状态，值为2.
		// 旧版本曾采用不同的域名区分部署状态，仍然支持。
		// $optional[Channel::DEPLOY_STATUS] = 1;
		$ret = $channel -> initAppIoscert ($name, $description, $release_cert, $dev_cert) ;
		if (false === $ret) {
			error_output ('WRONG, ' . __FUNCTION__ . ' ERROR!!!!') ;
			error_output ('ERROR NUMBER: ' . $channel -> errno ()) ;
			error_output ('ERROR MESSAGE: ' . $channel -> errmsg ()) ;
			error_output ('REQUEST ID: ' . $channel -> getRequestId ());
		} else {
			right_output ('SUCC, ' . __FUNCTION__ . ' OK!!!!!') ;
			right_output ('result: ' . print_r ($ret, true)) ;
		} 
	} 

	function test_updateAppIoscert ($name, $description, $release_cert, $dev_cert) {
		$apiKey = $this->apiKey;
		$secretKey = $this->secretKey;
		
		$channel = new Channel ($apiKey, $secretKey) ; 
		// 如果ios应用当前部署状态为开发状态，指定DEPLOY_STATUS为1，默认是生产状态，值为2.
		// 旧版本曾采用不同的域名区分部署状态，仍然支持。
		// $optional[Channel::DEPLOY_STATUS] = 1;
		$optional[ Channel :: NAME ] = $name;
		$optional[ Channel :: DESCRIPTION ] = $description;
		$optional[ Channel :: RELEASE_CERT ] = $release_cert;
		$optional[ Channel :: DEV_CERT ] = $dev_cert;
		$ret = $channel -> updateAppIoscert ($optional) ;
		if (false === $ret) {
			error_output ('WRONG, ' . __FUNCTION__ . ' ERROR!!!!') ;
			error_output ('ERROR NUMBER: ' . $channel -> errno ()) ;
			error_output ('ERROR MESSAGE: ' . $channel -> errmsg ()) ;
			error_output ('REQUEST ID: ' . $channel -> getRequestId ());
		} else {
			right_output ('SUCC, ' . __FUNCTION__ . ' OK!!!!!') ;
			right_output ('result: ' . print_r ($ret, true)) ;
		} 
	} 

	function test_queryAppIoscert () {
		$apiKey = $this->apiKey;
		$secretKey = $this->secretKey;
		
		$channel = new Channel ($apiKey, $secretKey) ; 
		// 如果ios应用当前部署状态为开发状态，指定DEPLOY_STATUS为1，默认是生产状态，值为2.
		// 旧版本曾采用不同的域名区分部署状态，仍然支持。
		// $optional[Channel::DEPLOY_STATUS] = 1;
		$ret = $channel -> queryAppIoscert () ;
		if (false === $ret) {
			error_output ('WRONG, ' . __FUNCTION__ . ' ERROR!!!!') ;
			error_output ('ERROR NUMBER: ' . $channel -> errno ()) ;
			error_output ('ERROR MESSAGE: ' . $channel -> errmsg ()) ;
			error_output ('REQUEST ID: ' . $channel -> getRequestId ());
		} else {
			right_output ('SUCC, ' . __FUNCTION__ . ' OK!!!!!') ;
			right_output ('result: ' . print_r ($ret, true)) ;
		} 
	} 

	function test_deleteAppIoscert () {
		$apiKey = $this->apiKey;
		$secretKey = $this->secretKey;
		
		$channel = new Channel ($apiKey, $secretKey) ;
		$ret = $channel -> deleteAppIoscert () ;
		if (false === $ret) {
			error_output ('WRONG, ' . __FUNCTION__ . ' ERROR!!!!') ;
			error_output ('ERROR NUMBER: ' . $channel -> errno ()) ;
			error_output ('ERROR MESSAGE: ' . $channel -> errmsg ()) ;
			error_output ('REQUEST ID: ' . $channel -> getRequestId ());
		} else {
			right_output ('SUCC, ' . __FUNCTION__ . ' OK!!!!!') ;
			right_output ('result: ' . print_r ($ret, true)) ;
		} 
	}
  
	//实现按照条件推送数据

	function  push_androidoriso(){
	      
        $message=$_POST['msgmobile'];
		$msgmbole=explode(',',$message);
		$count=0;
		foreach($msgmbole as $k=>$v){
		  if(!empty($v)){
		    $count++;
		  }
		}
       if($count==1)
		{
	      $sort=$msgmbole[0];
		  switch($sort){
		     case 1:{$rs=$this->push_message_android();if($rs){ ajaxmsg("android success"); }else{ ajaxmsg("fail",0); }  };break;
			 case 2:{$rs1=$this->push_message_ios();if($rs1){ ajaxmsg("ios success"); }else{ ajaxmsg("fail",0); }}break;
		   } 
	   
	    }else if($count==2){
		  $rs=$this->push_message_android();
		  $rs1=$this->push_message_ios();
		  if($rs && $rs1){
		    if($rs){ ajaxmsg("android and ios success"); }else{ ajaxmsg("fail",0); }
       		  
		  }else if($rs && !$rs1){
		     ajaxmsg("android success");
		   }else if($rs1 && !$rs){
		   
		     ajaxmsg("ios success");
		   }else{
		      ajaxmsg("android and ios  fail",0);
		     }
           
		}

	   /**
        $out['message'] = $message."||".$count."||isooranuo".$sort;
	    ajaxmsg($out,0); **/
	
	 
	 
	 
	 }
 


} 

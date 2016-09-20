<?php
// 全局设置
class MsgonlineAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function index()
    {
       $msgconfig = FS("Webconfig/msgconfig");
       $type = $msgconfig['sms']['type'];// type=0 漫道短信接口
       $uid2=$msgconfig['sms']['user2']; //分配给你的账号
       $pwd2=$msgconfig['sms']['pass2']; //密码 
       if($type==0){
           $d=@file_get_contents("http://sdk2.zucp.net:8060/webservice.asmx/balance?sn={$uid2}&pwd={$pwd2}",false);
           preg_match('/<string.*?>(.*?)<\/string>/', $d, $matches);
           
           if($matches[1]<0){ 
               switch($matches[1]){
                   case -2:
                       $d="帐号/密码不正确或者序列号未注册";
                   break;
                   case -4:
                       $d="余额不足";
                   break;
                   case -6:
                       $d="参数有误";
                   break;
                   case -7:
                       $d="权限受限,该序列号是否已经开通了调用该方法的权限";
                   break;
                   case -12:
                       $d="序列号状态错误，请确认序列号是否被禁用";
                   break;
                   default:
                       $d="用户名或密码错误";
                   break;
               }
           }else{
               $d = $d."条";
           }
           $this->assign('zucp',$d);
       }
       #邮件/短信接口密码加密
       $msgconfig['stmp']['pass'] = empty($msgconfig['stmp']['pass']) ? '':sha1($msgconfig['stmp']['pass']);
       $msgconfig['sms']['pwd'] = empty($msgconfig['sms']['pwd']) ? '':sha1($msgconfig['sms']['pwd']);
 
       $this->assign('stmp_config',$msgconfig['stmp']);
       $this->assign('sms_config',$msgconfig['sms']);
       $this->assign('sms_config_type',$msgconfig['sms']['type']);
       $this->assign('baidu_config',$msgconfig['baidu']);
       $this->assign("type_list", array("0"=>'开通短信服务',"1"=>'关闭短信服务'));
        $this->display();
    }
    public function save(){    
        $msgconfig = FS("Webconfig/msgconfig");
       if($_GET['yx']){
           import("ORG.Net.UploadFile");
           $upload=new UploadFile();
           $upload->maxSize=3145728;
           $upload->saveRule = 'time';
           $upload->thumb = true ;
           $upload->thumbMaxWidth ="80,80" ;
           $upload->thumbMaxHeight = "80,80";
           $upload->allowExts=array('jpg','gif','png','jpg');
           $upload->savePath='./UF/Uploads/Article/';
           $pathsave="/UF/Uploads/Article/";
           $upload->upload();
   
           $info=$upload->getUploadFileInfo();
               
             if(empty($info)){
               $json['message']=$pathsave.$info[0]['savename'];
               $json['status']=0;
               exit(json_encode($json));
           }else{
              $json['message']=$pathsave.$info[0]['savename'];
              $json['status']=1;
              exit(json_encode($json));
           }
         }else{
           #邮件密码
           if ($_POST['msg']['stmp']['pass']==sha1($msgconfig['stmp']['pass'])) {
               $_POST['msg']['stmp']['pass'] = $msgconfig['stmp']['pass'];
           }else{
               $_POST['msg']['stmp']['pass'] = $_POST['msg']['stmp']['pass'];
           }
           #短信接口密码设置
           if ($_POST['msg']['sms']['pwd']==sha1($msgconfig['sms']['pwd'])) {
               $_POST['msg']['sms']['pwd'] = $msgconfig['sms']['pwd'];
           }else{
               $_POST['msg']['sms']['pwd'] = $_POST['msg']['sms']['pwd'];
           }
           $status = $_POST['msg']['sms']['type'];
           //0：开通 ，1：关闭
           if($status=='0'){
               $pwd = $_POST['msg']['sms']['user2'].$_POST['msg']['sms']['pwd'];
               $_POST['msg']['sms']['pass2'] =strtoupper(md5($pwd));//$pwd
           }

           $data = $_POST['msg'];
           $result = array();
           foreach($data as $k=>$v){
               $result[$k] = filter_only_array($data[$k]);
           }
           FS("msgconfig",$result,"Webconfig/");
           M('global')->where("code = 'is_manual'")->setField('text',$status);
           alogs("Msgonline",0,1,'成功执行了通知信息接口的编辑操作！');//管理员操作日志
           S('global_setting', NULL);
           $this->success("操作成功",__URL__."/index/"); 
        }
    }
   public function app_canshu(){
		$msgconfig = FS("Webconfig/baiduconfig");
		$this->assign('baidu_config',$msgconfig['baidu']);
		$this->display();
	}
	public function app_canshu_save(){
		if($_GET['yx']){
		    import("ORG.Net.UploadFile");
			$upload=new UploadFile();
	        $upload->maxSize=3145728;
	        $upload->saveRule = 'time';
			$upload->thumb = true ;
			$upload->thumbMaxWidth ="200" ;
			$upload->thumbMaxHeight = "360";
			$upload->allowExts=array('jpg','gif','png','jpg');
	        $upload->savePath='./UF/Uploads/Article/';
		    $pathsave="/UF/Uploads/Article/";
		    $upload->upload();
	
		    $info=$upload->getUploadFileInfo();
		        
		  	if(empty($info)){
				$json['message']=$pathsave.$info[0]['savename'];
				$json['status']=0;
				exit(json_encode($json));
			}else{
			   $json['message']=$pathsave.$info[0]['savename'];
			   $json['status']=1;
			   exit(json_encode($json));
			}
		}
		FS("baiduconfig",$_POST['msg'],"Webconfig/");
		alogs("Msgonline",0,1,'成功执行了通知信息接口的编辑操作！');//管理员操作日志
		$this->success("操作成功",__URL__."/app_canshu/"); 
	}
   
    public function templet()
    {
       $emailTxt = FS("Webconfig/emailtxt");
       $smsTxt = FS("Webconfig/smstxt");
       $msgTxt = FS("Webconfig/msgtxt");
 
       $this->assign('emailTxt',de_xie($emailTxt));
       $this->assign('smsTxt',de_xie($smsTxt));
       $this->assign('msgTxt',de_xie($msgTxt));
        $this->display();
    }
   
    public function templetsave()
    {
       FS("emailtxt",$_POST['email'],"Webconfig/");
       FS("smstxt",$_POST['sms'],"Webconfig/");
       FS("msgtxt",$_POST['msg'],"Webconfig/");
       alogs("Msgonline",0,1,'成功执行了通知信息模板的编辑操作！');//管理员操作日志
       $this->success("操作成功",__URL__."/templet/");
    }
}
?>
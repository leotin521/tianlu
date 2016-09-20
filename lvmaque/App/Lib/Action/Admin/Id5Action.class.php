<?php
// 全局设置
class id5Action extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作 id5认证
    +----------------------------------------------------------
    */
    public function index()
    {
		try{
			$id5_config = FS("Webconfig/id5");
			$client = new SoapClient($id5_config['account_api']);
			$auth = new stdClass();
			$auth->UserName = $id5_config['account_name'];
			$auth->Password = $id5_config['account_password'];
			$json = $client->QueryBalance(array('request'=> '','cred'=> json_encode($auth)))->QueryBalanceResult;
			$result = json_decode($json);
			
			$this->assign("SimpleBalance", $result->SimpleBalance);//简项认证的余额条数
			$this->assign("ExactBalance", $result->ExactBalance);//多项认证的余额条数
			$this->assign("Errmsg", "状态:".$result->ResponseText);//查询结果返回文本
	
		}catch (Exception $e){
			echo '<pre>';
			print_r($e);
		}
	   $id5_config['account_password'] = sha1($id5_config['account_password']);
       $this->assign("id5_config", $id5_config);
	   $this->assign("type_list", array("1"=>'开通服务',"0"=>'关闭服务'));
       $this->display();
    }
    public function save()
    {	
        $id5_config = FS("Webconfig/id5");
		$_POST['account_api'] ='http://service.sfxxrz.com/IdentifierService.svc?wsdl';
		if ($_POST['account_password']==sha1($id5_config['account_password'])) {
		    $_POST['account_password'] = $id5_config['account_password'];
		}else{
		    $_POST['account_password'] = $_POST['account_password'];
		}
        FS("id5",$_POST, 'Webconfig/');
        alogs("id5",0,1,'执行了身份验证接口参数的编辑操作！');//管理员操作日志
        $this->success("操作成功",__URL__."/index/");
    }
    
}
?>

<?php
// 全局设置
class LoginonlineAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function index()
    {
		$loginconfig = FS("Webconfig/loginconfig");
		#QQ密钥加密
		$loginconfig['qq']['key'] = empty($loginconfig['qq']['key']) ? '':sha1($loginconfig['qq']['key']);
		#sina密钥加密
		$loginconfig['sina']['skey'] = empty($loginconfig['sina']['skey']) ? '':sha1($loginconfig['sina']['skey']);
		
		$this->assign('qq_config',$loginconfig['qq']);
		$this->assign('sina_config',$loginconfig['sina']);
		$this->assign('uc_config',$loginconfig['uc']);
		$this->assign('cookie_config',$loginconfig['cookie']);
        $this->display();
    }
    public function save()
    {
        $loginconfig = FS("Webconfig/loginconfig");
        #QQ密钥加密
        if ($_POST['login']['qq']['key']==sha1($loginconfig['qq']['key'])) {
            $_POST['login']['qq']['key'] = $loginconfig['qq']['key'];
        }else{
            $_POST['login']['qq']['key'] = $_POST['login']['qq']['key'];
        }
        #sina密钥加密
        if ($_POST['login']['sina']['skey']==sha1($loginconfig['sina']['skey'])) {
            $_POST['login']['sina']['skey'] = $loginconfig['sina']['skey'];
        }else{
            $_POST['login']['sina']['skey'] = $_POST['login']['sina']['skey'];
        }
        $data = $_POST['login'];
        $result = array();
        foreach($data as $k=>$v){
            $result[$k] = filter_only_array($data[$k]);
        }
        alogs("Loginonline",0,1,'执行了登陆接口管理参数编辑操作！');//管理员操作日志
		FS("loginconfig",$result,"Webconfig/");
		$this->success("操作成功",__URL__."/index/");
    }
}
?>
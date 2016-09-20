<?php
// 全局设置
class PayonlineAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function index()
    {
		$payconfig = FS("Webconfig/payconfig");
		#baofoo终端号/证书加密
		$payconfig['baofoo']['TerminalID'] = empty($payconfig['baofoo']['TerminalID']) ? '':sha1($payconfig['baofoo']['TerminalID']);
		$payconfig['baofoo']['pkey'] = empty($payconfig['baofoo']['pkey']) ? '':sha1($payconfig['baofoo']['pkey']);
		$this->assign('baofoo_config', $payconfig['baofoo']);		//宝付
		#unspay商户密钥加密
		$payconfig['unspay']['merchantKey'] = empty($payconfig['unspay']['merchantKey']) ? '':sha1($payconfig['unspay']['merchantKey']);
		$this->assign('unspay_config', $payconfig['unspay']);			//银生宝支付
		$this->assign('guofubao_config',$payconfig['guofubao']);	//国付宝
		$this->assign('ips_config',$payconfig['ips']);				//环迅支付
		$this->assign('shengpay_config', $payconfig['shengpay']);	//盛付通
		$this->assign('tenpay_config', $payconfig['tenpay']);		//财付通
		$this->assign('ecpss_config', $payconfig['ecpss']);			//汇潮支付
		$this->assign('easypay_config', $payconfig['easypay']);		//易生支付
		$this->assign('allinpay_config',$payconfig['allinpay']);	//通联支付
		$this->assign('sina_config',$payconfig['sina']);			//新浪微支付
		#reapal支付密钥加密
		$payconfig['reapal']['MD5key'] = empty($payconfig['reapal']['MD5key']) ? '':sha1($payconfig['reapal']['MD5key']);
		$this->assign('reapal_config', $payconfig['reapal']);		//融宝
		$payconfig['chinabank']['key'] = empty($payconfig['chinabank']['key']) ? '':sha1($payconfig['chinabank']['key']);
		$this->assign('chinabank_config',$payconfig['chinabank']);			//网银在线
		$payconfig['jdpay']['key'] = empty($payconfig['jdpay']['key']) ? '':sha1($payconfig['jdpay']['key']);
		$payconfig['jdpay']['desKey'] = empty($payconfig['jdpay']['desKey']) ? '':sha1($payconfig['jdpay']['desKey']);
		$this->assign('jdpay_config',$payconfig['jdpay']);					//京东快捷
		$this->assign('others', 0);//这个参数控制前台是否显示“其它方式”第三方支付，21=显示//`mxl 20150210`
        $this->display();
    }
    public function save()
    {
        $payconfig = FS("Webconfig/payconfig");
//baofoo
        #终端号
        if ($_POST['pay']['baofoo']['TerminalID']==sha1($payconfig['baofoo']['TerminalID'])) {
            $_POST['pay']['baofoo']['TerminalID'] = $payconfig['baofoo']['TerminalID'];
        }else{
            $_POST['pay']['baofoo']['TerminalID'] = $_POST['pay']['baofoo']['TerminalID'];
        }
        #证书
        if ($_POST['pay']['baofoo']['pkey']==sha1($payconfig['baofoo']['pkey'])) {
            $_POST['pay']['baofoo']['pkey'] = $payconfig['baofoo']['pkey'];
        }else{
            $_POST['pay']['baofoo']['pkey'] = $_POST['pay']['baofoo']['pkey'];
        }
        if($_POST['pay']['baofoo']['feerate'] == ''){
            $_POST['pay']['baofoo']['feerate'] = 0;
        }
        /*
//unspay
        #商户密钥
        if ($_POST['pay']['unspay']['merchantKey']==sha1($payconfig['unspay']['merchantKey'])) {
            $_POST['pay']['unspay']['merchantKey'] = $payconfig['unspay']['merchantKey'];
        }else{
            $_POST['pay']['unspay']['merchantKey'] = $_POST['pay']['unspay']['merchantKey'];
        }
        */
//reapal
        #支付密钥
        if ($_POST['pay']['reapal']['MD5key']==sha1($payconfig['reapal']['MD5key'])) {
            $_POST['pay']['reapal']['MD5key'] = $payconfig['reapal']['MD5key'];
        }else{
            $_POST['pay']['reapal']['MD5key'] = $_POST['pay']['reapal']['MD5key'];
        }
        if($_POST['pay']['chinabank']['feerate'] == ''){
            $_POST['pay']['chinabank']['feerate'] = 0;
        }
        //网银在线密钥
        if ($_POST['pay']['chinabank']['key']==sha1($payconfig['chinabank']['key'])) {
            $_POST['pay']['chinabank']['key'] = $payconfig['chinabank']['key'];
        }else{
            $_POST['pay']['chinabank']['key'] = $_POST['pay']['chinabank']['key'];
        }
        //京东支付密钥
        if ($_POST['pay']['jdpay']['key']==sha1($payconfig['jdpay']['key'])) {
            $_POST['pay']['jdpay']['key'] = $payconfig['jdpay']['key'];
        }else{
            $_POST['pay']['jdpay']['key'] = $_POST['pay']['jdpay']['key'];
        }
        //京东支付key
        if ($_POST['pay']['jdpay']['desKey']==sha1($payconfig['jdpay']['desKey'])) {
            $_POST['pay']['jdpay']['desKey'] = $payconfig['jdpay']['desKey'];
        }else{
            $_POST['pay']['jdpay']['desKey'] = $_POST['pay']['jdpay']['desKey'];
        }
        if($_POST['pay']['jdpay']['feerate'] == ''){
            $_POST['pay']['jdpay']['feerate'] = 0;
        }
        $data = $_POST['pay'];
        $result = array();
        foreach($data as $k=>$v){
            $result[$k] = filter_only_array($data[$k]);
        }
		FS("payconfig",$result,"Webconfig/");
		alogs("Payonline",0,1,'执行了第三方支付接口参数的编辑操作！');//管理员操作日志
		$this->success("操作成功",__URL__."/index/");
    }
}
?>
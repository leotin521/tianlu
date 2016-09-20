 <?php
// 全局设置
class PaylogAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function index()
    {
	
		if($_REQUEST['status']>-1){
			$map['status'] = intval($_REQUEST['status']);
			$search['status'] = $map['status'];
		}else{
			$search['status'] = -1;
		}
		if(!empty($_REQUEST['way'])){
			if($_REQUEST['way']=='线下充值')
			{
				$map['way'] ='off'; //$_REQUEST['way'];
			}else
			{
				$map['way'] = htmlspecialchars($_REQUEST['way'], ENT_QUOTES);
			}
		}
		if(!empty($_REQUEST['uname'])){
			$uid = M("members")->getFieldByUserName(text(urldecode($_REQUEST['uname'])),'id');
			$map['uid'] = $uid;
			$search['uid'] = $map['uid'];
		}
		if(!empty($_REQUEST['dealuser'])){
			$map['deal_user'] = text(urldecode($_REQUEST['dealuser']));
			$search['dealuser'] = $map['deal_user'];
		}
		if(!empty($_REQUEST['uid'])){
			$map['uid'] = intval($_REQUEST['uid']);
			$search['uid'] = $map['uid'];
		}
		if(!empty($_REQUEST['start_time'])&&!empty($_REQUEST['end_time'])){

			$start_time = strtotime($_REQUEST['start_time']." 00:00:00");
			$end_time = strtotime($_REQUEST['end_time']." 23:59:59");
			$map['add_time'] = array("between","{$start_time},{$end_time}");
			$search['start_time'] = $_REQUEST['start_time'];
			$search['end_time'] = $_REQUEST['end_time'];
			$xtime['start_time'] = $_REQUEST['start_time'];
			$xtime['end_time'] = $_REQUEST['end_time'];
		}
		$this->assign('search',$search);
		
	 	$listType = C('PAYLOG_TYPE');
		$listType[-1]="不限制";
		$this->assign('type_list',$listType);
		$field= 'id,uid,status,money,add_time,tran_id,way,off_bank,off_way,deal_user';
		$this->_list(D('Paylog'),$field,$map,'id','DESC',$xtime);
        $this->display();
    }
	
	public function edit(){
		setBackUrl();
        $id = intval($_GET['id']);
        $img = M('member_payonline')->field('payimg')->where("id={$id}")->find();
        if($img['payimg']){
            $img = unserialize($img['payimg']);
        }else{
            $img = null;
        }

        $this->assign('img', $img);
		$this->assign("id",$id);	
		$this->display();
	}
	
	public function doEdit(){
		$id=intval($_POST['id']);	
		$status = intval($_POST['status']);
		
		$statusx = M('member_payonline')->getFieldById($id,"status");
		if ($statusx!=0){
			$this->error("请不要重复提交表单");
		}
		if($status==1){
			$vo = M('member_payonline')->field('money,fee,uid,way')->find($id);
			if($vo['way']=="off"){
			    $newid = memberMoneyLog($vo['uid'],27,$vo['money']-$vo['fee'],"管理员手动审核充值");
			}else{
			    $newid = memberMoneyLog($vo['uid'],3,$vo['money']-$vo['fee'],"管理员手动审核充值");
			}

			if($newid){
				
				////////////////////////////
				if($vo['way']=="off"){
					$tqfee = explode( "|", $this->glo['offline_reward']);
					$fee[0] = explode( "-", $tqfee[0]);
					$fee[2] = explode( "-", $tqfee[2]);
					$fee[1] = floatval($tqfee[1]);
					$fee[3] = floatval($tqfee[3]);
					$fee[4] = floatval($tqfee[4]);
					$fee[5] = floatval($tqfee[5]);
					if($vo['money']>=$fee[0][0] && $vo['money']<=$fee[0][1]){
						$fee_rate = 0<$fee[1]?($fee[1]/1000):0;
					}else if($vo['money']>=$fee[2][0] && $vo['money']<=$fee[2][1]){
						$fee_rate = 0<$fee[3]?($fee[3]/1000):0;
					}else if($vo['money']>=$fee[4]){
						$fee_rate = 0<$fee[5]?($fee[5]/1000):0;
					}else{
						$fee_rate = 0;
					}
					$newidx = memberMoneyLog($vo['uid'],32,$vo['money']*$fee_rate,"线下充值奖励");
				}
				/////////////////////////////
				/*
				$offline_reward = explode("|",$this->glo['offline_reward']);
				if($vo['money']>$offline_reward[0]){
					$fee_rate = 0<$offline_reward[1]?($offline_reward[1]/1000):0;
					$newidx = memberMoneyLog($vo['uid'],32,$vo['money']*$fee_rate,"线下充值奖励");
				}*/
				$save['deal_user'] = session('adminname');
				$save['deal_uid'] = $this->admin_id;
				$save['status'] = 1;
				M('member_payonline')->where("id={$id}")->save($save);
				$vx = M('members')->field("user_name,user_phone")->find($vo['uid']);
				if($vo['way']=="off") SMStip("payoffline",$vx['user_phone'],array("#USERANEM#","#MONEY#"),array($vx['user_name'],$vo['money']));
				else  SMStip("payonline",$vx['user_phone'],array("#USERANEM#","#MONEY#"),array($vx['user_name'],$vo['money']));
				alogs("Paylog",0,1,'执行了管理员手动审核充值操作！');//管理员操作日志
				$this->success("处理成功");
			}else{
				alogs("Paylog",0,1,'执行管理员手动审核充值操作失败！');//管理员操作日志
				$this->error("处理失败");
			}
		}else{
			$save['deal_user'] = session('adminname');
			$save['deal_uid'] = $this->admin_id;
			$save['status'] = 3;
			$newid = M('member_payonline')->where("id={$id}")->save($save);
			if($newid) $this->success("处理成功");
			else $this->error("处理失败");
		}
	}
	
	public function _listFilter($list){
	 	$listType = C('PAYLOG_TYPE');
	 	$payType = array('off'=>'线下充值','baofoo'=>'宝付','unspay'=>'银生宝支付','reapal'=>'融宝支付','chinabank'=>'网银在线','jdpay'=>'京东快捷');
		$this->assign("payType",$payType);
		$row=array();
		foreach($list as $key=>$v){
			$v['status_num'] = $v['status'];
			$v['status'] = $listType[$v['status']];
			$v['uname'] = M("members")->getFieldById($v['uid'],'user_name');
			$v['way'] = $payType[$v['way']];
			$row[$key]=$v;
		}
		return $row;
	}
	
		/**
    +----------------------------------------------------------
    * 线上充值操作
    +----------------------------------------------------------
    */
    public function paylogonline()
    {
	
		if(!empty($_REQUEST['status'])){
			$map['status'] = intval($_REQUEST['status']);
			$search['status'] = $map['status'];
		}
		if(!empty($_REQUEST['uname'])){
			$uid = M("members")->getFieldByUserName(text($_REQUEST['uname']),'id');
			$map['uid'] = $uid;
			$search['uid'] = $map['uid'];
		}
		if(!empty($_REQUEST['uid'])){
			$map['uid'] = intval($_REQUEST['uid']);
			$search['uid'] = $map['uid'];
		}
		if(!empty($_REQUEST['way'])){
			if($_REQUEST['way']=='线下充值')
			{
				$map['way'] ='off'; //$_REQUEST['way'];
			}else
			{
				$map['way'] = htmlspecialchars($_REQUEST['way'], ENT_QUOTES);
			}
		}
		if(!empty($_REQUEST['dealuser'])){
			$map['deal_user'] = text(urldecode($_REQUEST['dealuser']));
			$search['dealuser'] = $map['deal_user'];
		}
		
		if(!empty($_REQUEST['start_time'])&&!empty($_REQUEST['end_time'])){

			$start_time = strtotime($_REQUEST['start_time']." 00:00:00");
			$end_time = strtotime($_REQUEST['end_time']." 23:59:59");
			$map['add_time'] = array("between","{$start_time},{$end_time}");
			$search['start_time'] = $_REQUEST['start_time'];
			$search['end_time'] = $_REQUEST['end_time'];
			$xtime['start_time'] = $_REQUEST['start_time'];
			$xtime['end_time'] = $_REQUEST['end_time'];
		}
		$this->assign('search',$search);
		
	 	$listType = C('PAYLOG_TYPE');
		$listType[-1]="不限制";
		$this->assign('type_list',$listType);
		$field= 'id,uid,status,money,add_time,tran_id,way,off_bank,off_way,deal_user';
		$map['way']=array("in",'baofoo,unspay,reapal,chinabank,jdpay');
		$this->_list(D('Paylog'),$field,$map,'id','DESC',$xtime);
        $this->display();
    }
	
	/**
    +----------------------------------------------------------
    * 线下充值操作
    +----------------------------------------------------------
    */
    public function paylogoffline()
    {
	
		if(!empty($_REQUEST['status'])){
			$map['status'] = intval($_REQUEST['status']);
			$search['status'] = $map['status'];
		}
		if(!empty($_REQUEST['uname'])){
			$uid = M("members")->getFieldByUserName(text($_REQUEST['uname']),'id');
			$map['uid'] = $uid;
			$search['uid'] = $map['uid'];
		}
		if(!empty($_REQUEST['uid'])){
			$map['uid'] = intval($_REQUEST['uid']);
			$search['uid'] = $map['uid'];
		}
		if(!empty($_REQUEST['way'])){
			if($_REQUEST['way']=='线下充值')
			{
				$map['way'] ='off'; //$_REQUEST['way'];
			}else
			{
				$map['way'] = htmlspecialchars($_REQUEST['way'], ENT_QUOTES);
			}
		}
		if(!empty($_REQUEST['dealuser'])){
			$map['deal_user'] = text(urldecode($_REQUEST['dealuser']));
			$search['dealuser'] = $map['deal_user'];
		}
		if(!empty($_REQUEST['start_time'])&&!empty($_REQUEST['end_time'])){

			$start_time = strtotime($_REQUEST['start_time']." 00:00:00");
			$end_time = strtotime($_REQUEST['end_time']." 23:59:59");
			$map['add_time'] = array("between","{$start_time},{$end_time}");
			$search['start_time'] = $_REQUEST['start_time'];
			$search['end_time'] = $_REQUEST['end_time'];
			$xtime['start_time'] = $_REQUEST['start_time'];
			$xtime['end_time'] = $_REQUEST['end_time'];
		}
		$this->assign('search',$search);
		
	 	$listType = C('PAYLOG_TYPE');
		$listType[-1]="不限制";
		$this->assign('type_list',$listType);
		$field= 'id,uid,status,money,add_time,tran_id,way,off_bank,off_way,deal_user';
		$map['way']='off';
		$this->_list(D('Paylog'),$field,$map,'add_time','DESC',$xtime);
        $this->display();
    }
    
    public function offset()
    {  
        
        if(isset($_POST['bank'])){ 
            $bank_arr = array();
            foreach($_POST['bank'] as $k=>$v){
                $bank_arr[$k]=array(
                                'bank'=>stripslashes($v), 
                                'payee'=>stripslashes($_POST['payee'][$k]),
                                'account'=>stripslashes($_POST['account'][$k]),
                                'address'=>stripslashes($_POST['address'][$k]),
                                );
            }
            $info = $_POST['info']; 
			$is_open =$_POST['is_open'];  
            $this->saveConfig($bank_arr,$info,$is_open);
            $this->success("操作成功",__URL__."/offset/");
            exit;
        }
        
        import("ORG.Net.Keditor");
        $ke=new Keditor();
        $ke->id="info";
        $ke->width="700px";
        $ke->height="300px";
        $ke->items="['source', '|', 'fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
        'plainpaste', 'wordpaste', '|', 'justifyleft', 'justifycenter', 'justifyright',
        'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
        'superscript', '|', 'selectall', '-',
        'title', 'fontname', 'fontsize', '|', 'textcolor', 'bgcolor', 'bold',
        'italic', 'underline', 'strikethrough', 'removeformat', '|','table', 'hr', 'emoticons', 'link', 'unlink', '|', 'about']
        ";
        $ke->resizeMode=1;

        $ke->jspath="/Style/kindeditor/kindeditor.js";
        $ke->form="bankForm";
        $keshow=$ke->show();
        $this->assign("keshow",$keshow);
            
            
        $config = FS("Webconfig/payoff");
        $this->assign('bank', $config['BANK']);
        $this->assign('info', $config['BANK_INFO']);
		$this->assign('is_open', $config['IS_OPEN']);
        $this->display();
    }
    
    private function saveConfig($arr,$info,$is_open)
    {
        $config['BANK'] = $arr;
        $config['BANK_INFO'] = $info; 
		$config['IS_OPEN'] = $is_open;
        FS("payoff", $config, "Webconfig/"); 
    }
    

       
  
}
?>
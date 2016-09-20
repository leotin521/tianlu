<?php
// 全局设置
class VerifyfaceAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function index()
    {
		$map=array();
		if($_REQUEST['uname']){
			$map['m.user_name'] = array("like",urldecode($_REQUEST['uname'])."%");
			$search['uname'] = urldecode($_REQUEST['uname']);	
		}
		if($_REQUEST['realname']){
			$map['mi.real_name'] = urldecode($_REQUEST['realname']);
			$search['realname'] = $map['mi.real_name'];	
		}
		if(!empty($_REQUEST['bj']) && !empty($_REQUEST['lx']) && !empty($_REQUEST['money'])){
			if($_REQUEST['lx']=='allmoney'){
				if($_REQUEST['bj']=='gt'){
					$bj = '>';
				}else if($_REQUEST['bj']=='lt'){
					$bj = '<';
				}else if($_REQUEST['bj']=='eq'){
					$bj = '=';
				}
				$map['_string'] = "(mm.account_money+mm.back_money) ".$bj.$_REQUEST['money'];
			}else{
				$map[$_REQUEST['lx']] = array($_REQUEST['bj'],$_REQUEST['money']);
			}
			$search['bj'] = htmlspecialchars($_REQUEST['bj'], ENT_QUOTES);	
			$search['lx'] = htmlspecialchars($_REQUEST['lx'], ENT_QUOTES);
			$search['money'] = floatval($_REQUEST['money']);	
		}
		if(isset($_REQUEST['status'])&&$_REQUEST['status']!=''){
			$map['v.apply_status'] = intval($_REQUEST['status']);
			$search['status'] = $map['v.apply_status'];	
		}
		//if(session('admin_is_kf')==1)	$map['m.customer_id'] = session('admin_id');
		
		//分页处理
		import("ORG.Util.Page");
		$count = M('face_apply v')->join("{$this->pre}members m ON m.id=v.uid")->join("{$this->pre}member_money mm ON mm.uid=m.id")->join("{$this->pre}member_info mi ON mi.uid=m.id")->where($map)->count('m.id');
		$p = new Page($count, C('ADMIN_PAGE_SIZE'));
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
		
		$field= 'v.id,v.add_time,v.uid,v.apply_status,m.user_phone,m.reg_time,m.user_name,mi.real_name,mm.money_freeze,mm.money_collect,(mm.account_money+mm.back_money) account_money';
		$list = M('face_apply v')->field($field)->join("{$this->pre}members m ON m.id=v.uid")->join("{$this->pre}member_money mm ON mm.uid=m.id")->join("{$this->pre}member_info mi ON mi.uid=m.id")->where($map)->limit($Lsql)->order('v.id DESC')->select();
		
        $this->assign("status", array('待审核','已通过审核','未通过审核'));
        $this->assign("bj", array("gt"=>'大于',"eq"=>'等于',"lt"=>'小于'));
        $this->assign("lx", array("allmoney"=>'可用余额',"mm.money_freeze"=>'冻结金额',"mm.money_collect"=>'待收金额'));
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);
        $this->assign("query", http_build_query($search));
		
        $this->display();
    }


	public function edit(){
		setBackUrl();
		$id=intval($_GET['id']);
		$vo = M('face_apply')->find($id);
		if($vo['apply_status']!=0) $this->error("审核过的不能再次审核");

		$this->assign("vo",$vo);
		var_dump($vo);
		$this->display();
	}

	public function doEdit(){
        $model = D('face_apply');
        if (false === $model->create()) {
            $this->error($model->getError());
        }		
		$model->deal_user = session('admin_id');
		$model->deal_time = time();
        //保存当前数据对象
        if ($result = $model->save()) { //保存成功
		
			$uid = M('face_apply')->getFieldById($_POST['id'],'uid');

			if($_POST['apply_status'] == 1){
                setMemberStatus($uid, 'face', $_POST['apply_status'], 8, '现场');   
				addInnerMsg($uid,"您的现场认证审核通过","您的现场认证审核通过");
				alogs("Verifyface",0,1,'现场认证审核通过！');//管理员操作日志
			}else{
				addInnerMsg($uid,"您的现场认证审核未通过","您的现场认证审核未通过");
				alogs("Verifyface",0,0,'现场认证审核未通过！');//管理员操作日志
			}
			
            //成功提示
            $this->assign('jumpUrl', __URL__."/index".session('listaction'));
            $this->success(L('审核成功'));
        } else {
			
            //失败提示
            $this->error(L('审核失败'));
        }
	}

}
?>

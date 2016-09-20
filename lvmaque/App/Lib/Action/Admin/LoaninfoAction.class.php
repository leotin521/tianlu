<?php
// 全局设置
class LoaninfoAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function index()
    {
		$map=array();
		if($_REQUEST['uid'] && $_REQUEST['uname']){
			$map['v.uid'] = intval($_REQUEST['uid']);
			$search['uid'] = $map['v.uid'];	
			$search['uname'] = urldecode($_REQUEST['uname']);	
		}
		
		if($_REQUEST['uname'] && !$search['uid']){
			$map['m.user_name'] = array("like",urldecode($_REQUEST['uname'])."%");
			$search['uname'] = urldecode($_REQUEST['uname']);	
		}
		
		if($_REQUEST['realname']){
			$map['mi.real_name'] = urldecode($_REQUEST['realname']);
			$search['real_name'] = $map['mi.real_name'];	
		}
		
		if($_REQUEST['customer_name']){   //手机号码
			$map['m.user_phone'] = urldecode($_REQUEST['customer_name']);
			$search['customer_name'] = $cusname;	
		}
		
		if($_REQUEST['status'] != ''){
			$map['v.status'] = intval($_REQUEST['status']);
			$search['status'] = $map['v.status'];	
		}
		
		if (!empty($_REQUEST['user_type'])){
		    $map['v.user_type'] = intval($_REQUEST['user_type']);
		    $search['user_type'] = $map['v.user_type'];
		}
		/*  格式化日期
		if(!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])){
		    $timespan = urldecode($_REQUEST['start_time']).",".urldecode($_REQUEST['end_time']);
		    $map['v.update_time'] = array("between",$timespan);
		    $search['start_time'] = urldecode($_REQUEST['start_time']);
		    $search['end_time'] = urldecode($_REQUEST['end_time']);
		}elseif(!empty($_REQUEST['start_time'])){
		    $xtime = urldecode($_REQUEST['start_time']);
		    $map['v.update_time'] = array("gt",$xtime);
		    $search['start_time'] = $xtime;
		}elseif(!empty($_REQUEST['end_time'])){
		    $xtime = urldecode($_REQUEST['end_time']);
		    $map['v.update_time'] = array("lt",$xtime);
		    $search['end_time'] = $xtime;
		}
		*/
        // 时间戳
		if(!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])){
			$timespan = strtotime(urldecode($_REQUEST['start_time'])).",".strtotime(urldecode($_REQUEST['end_time']));
			$map['v.update_time'] = array("between",$timespan);
			$search['start_time'] = urldecode($_REQUEST['start_time']);	
			$search['end_time'] = urldecode($_REQUEST['end_time']);	
		}elseif(!empty($_REQUEST['start_time'])){
			$xtime = strtotime(urldecode($_REQUEST['start_time']));
			$map['v.update_time'] = array("gt",$xtime);
			$search['start_time'] = $xtime;	
		}elseif(!empty($_REQUEST['end_time'])){
			$xtime = strtotime(urldecode($_REQUEST['end_time']));
			$map['v.update_time'] = array("lt",$xtime);
			$search['end_time'] = $xtime;	
		}
		//分页处理
		import("ORG.Util.Page");
		$count = M('borrow_apply v')->join("lzh_members m ON v.uid=m.id")->join("lzh_member_info mi ON mi.uid=m.id")->where($map)->count('m.id');
		$p = new Page($count, C('ADMIN_PAGE_SIZE'));
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
		
		$field= 'v.*,m.user_name as uname,m.user_phone,mi.real_name';
		$list = M('borrow_apply v')
        		->field($field)
        		->join("{$this->pre}members m ON m.id=v.uid")
        		->join("{$this->pre}member_info mi ON mi.uid=v.uid")
        		->where($map)
        		->limit($Lsql)
        		->order('v.id DESC')
        		->select();
		$list = $this->_listFilter($list);
        //print_r($search);
        $this->assign("status", array('未通过审核','待审核','已通过审核'));
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);
        $this->display();
    }
    /**
     * 企业借款申请审核
     */
    public function edit_q(){   //企业类型34
        setBackUrl();
        $id=intval($_GET['id']);
        $vo = M('borrow_apply')->find($id);
        if($vo['status']!=1) $this->error("审核过的不能再次审核");
        $vo['uname'] = M('members')->getFieldById($vo['uid'],'user_name');
        $vx = getBusinessDetail($vo['uid']);
        $this->assign("vx",$vx);
        $this->assign("vo",$vo);
        $this->display();
    }
    public function doEdit_q(){
        $model = D(ucfirst($this->getActionName()));
        $info = $model->field('deal_time')->where('id='.intval($_POST['id']))->find();
        if($info['deal_time']){
            $this->error("此申请已处理过，请不要重复提交！");
        }
        $data = textPost($_POST);
        $data['deal_time'] = time();
        $data['deal_user'] = session('admin_id');
        
        $db = new Model();
        $db->startTrans();
        $newid = M('borrow_apply')->where('id='.$data['id'])->save($data);  //修改状态
        
        $vx = M('borrow_apply')->field("uid")->find($data['id']);   //获取操作对象uid
        
        $uid = $vx['uid'];
        if ($data['status']=='2'){ 
            M('members')->where('id='.$uid)->save(array('is_transfer'=>1)); //改变借款者身份
        }
        if($newid){   
            addInnerMsg($uid,"您的企业借款申请审核通过","您的企业借款申请审核通过");//站内信
            alogs("Loaninfo",0,0,'企业借款申请审核通过！');//管理员操作日志
            memberCreditsLog($uid,34,10,"企业借款审核通过,奖励积分10"); //奖励10积分
            
            $db->commit();
            
            $this->assign('jumpUrl', __URL__."/".session('listaction'));
            $this->success(L('修改成功'));
        }else{
            $db->rollback();
            $this->error(L('修改失败'));
        }
    }
    /**
     * 个人借款申请审核
     */
	public function edit(){
		setBackUrl();
		$id=intval($_GET['id']);
		$vo = M('borrow_apply')->find($id);
		if($vo['status']!=1) $this->error("审核过的不能再次审核");
		$vo['uname'] = M('members')->getFieldById($vo['uid'],'user_name');
		$vx = getMemberInfoDetail($vo['uid']);
		$this->assign("vx",$vx);
		$this->assign("vo",$vo);
		$this->display();
	}
	public function doEdit(){
        $model = D(ucfirst($this->getActionName()));
		$info = $model->field('deal_time')->where('id='.intval($_POST['id']))->find();
        if($info['deal_time']){
            $this->error("此申请已处理过，请不要重复提交！");   
        }
        $data = textPost($_POST);
        $data['deal_time'] = time();
        $data['deal_user'] = session('admin_id');
        
        $db = new Model();
        $db->startTrans();
        
        $newid = M("borrow_apply")->where('id='.$data['id'])->save($data);  //修改申请状态
        
        $vx = M('borrow_apply')->field("uid")->find($data['id']);   //获取操作对象的uid
        $uid = $vx['uid'];
        
        if ($data['status']=='2'){
            
            M('members')->where('id='.$uid)->save(array('is_transfer'=>2)); //改变借款者身份
            
            $body = '个人借款申请审核通过！';
            memberCreditsLog($uid,33,10,'个人借款审核通过,奖励积分10');     //奖励10积分
            
        }else{
            
            $body = '个人借款申请审核未通过！';
            
        }
        if($newid){
            
            addInnerMsg($uid,'您的'.$body,'您的'.$body);    //站内信
            alogs("Loaninfo",0,0,$body);    //管理员操作日志
            $db->commit();
        
            $this->assign('jumpUrl', __URL__."/".session('listaction'));
            $this->success(L('修改成功'));
        }else{
            $db->rollback();
            $this->error(L('修改失败'));
        }
	}

	
	public function _listFilter($list){
		$row=array();
		$aUser = get_admin_name();
		foreach($list as $key=>$v){
			$v['a_kfName'] = $aUser[$v['kfid']];
			$row[$key]=$v;
		}
		return $row;
	}
	
	public function getusername(){
		$uname = M("members")->getFieldById(intval($_POST['uid']),"user_name");
		if($uname) exit(json_encode(array("uname"=>"<span style='color:green'>".$uname."</span>")));
		else exit(json_encode(array("uname"=>"<span style='color:orange'>不存在此会员</span>")));
	}
	
}
?>
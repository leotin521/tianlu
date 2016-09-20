<?php
// 管理员管理
class BrokerAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function index()
    {
        import("ORG.Util.Page");
		//$teamlist = M('ausers')->where("u_group_id=25")->select();//`mxl:team20150116`hide
		
		$AdminU = M('ausers');
		$page_size = ($page_szie==0)?C('ADMIN_PAGE_SIZE'):$page_szie;
		
		//`mxl:team20150116`
		$aid = session('admin_id');
		if (chkGroup($aid, $ainfo) === false){ $this->error("请重新登录"); }
		if ($ainfo['me'] === "other"){
			$teamlist = M('ausers')->where("u_group_id = {$ainfo['r_leader']}")->select();
		}
		else{
			$teamlist = M('ausers')->where("id = {$ainfo['parent']}")->select();
			$map['parent'] = array("in", "0,{$ainfo['parent']}");
		}
		$this->assign('ainfo', $ainfo);
		$map['u_group_id'] = $ainfo['r_member'];
		$count = $AdminU->where($map)->count();
		$order = "parent, id DESC";
		//`mxl:team20150116`
		//$count  = $AdminU->where("u_group_id=26")->count(); // 查询满足要求的总记录数 //`mxl:team20150116`hide
		$Page = new Page($count,$page_size); // 实例化分页类传入总记录数和每页显示的记录数   
		$show = $Page->show(); // 分页显示输出
		   
		$fields = "id,user_name,u_group_id,real_name,is_ban,area_name,is_kf,qq,phone,user_word, guanlian_time";//增加guanlian_time`mxl:team20150116`
		//$order = "id DESC,u_group_id DESC";//`mxl:team20150116`hide
		//$map['u_group_id'] = 26;//`mxl:team20150116`hide
		$list = $AdminU->field(true)->where($map)->order($order)->limit($Page->firstRow.','.$Page->listRows)->select();

		$AdminUserList = $list;
		
		$GroupArr = get_group_data();
		foreach($AdminUserList as $key => $v){
			$AdminUserList[$key]['groupname'] = $GroupArr[$v['u_group_id']]['groupname'];
			$team = M('ausers')->field("user_name,real_name")->where("id={$v['parent']}")->find();
			$AdminUserList[$key]['parentuser_name'] = $team['user_name'];
			//$AdminUserList[$key]['parentreal_name'] = $team['real_name'];//`mxl:team20150116`hide
			if (empty($team) === false){ $AdminUserList[$key]['parentreal_name'] = "(".$team['real_name'].")"; }//`mxl:team20150116`
		}

		//$this->assign('position', '管理员管理');//`mxl:team20150117`hide
		$this->assign('pagebar', $show);
		$this->assign('admin_list', $AdminUserList);
		$this->assign('arealist', M("area")->field("id,name")->where("is_open=1")->select());
		$this->assign('group_list', $GroupArr);
		$this->assign('teamlist', $teamlist);
        $this->display();
    }

    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function addAdmin()
    {
	    $model = M("ausers");
		if (false === $model->create()) {
			$this->error($model->getError());
		}
		$data = $_POST;

		if(!isset($_POST['uid'])){//新增
			foreach($data as $key => $v){
				if($key == "user_pass") $data[$key] = md5($data['user_pass']);
				else $data[$key] = EnHtml($v);
			}
			$map['user_name'] = text($data['user_name']);
			$count = M('members')->where($map)->count('id');
            $count2 = M('ausers')->where($map)->count('id');
			if($count>0 || $count2>0) $this->error('用户名重复！');
			
			$data['area_name'] = M("area")->getFieldById($data['area_id'],'name');
			if(!empty($_FILES['photo']['name'])){
			    $this->saveRule = date("YmdHis",time()).rand(0,1000);
			    $this->savePathNew = C('ADMIN_UPLOAD_DIR').'Product/';
			    $this->thumbMaxWidth = C('PRODUCT_UPLOAD_W');
			    $this->thumbMaxHeight = C('PRODUCT_UPLOAD_H');
			    $info = $this->CUpload();
			    $data['photo'] = $info[0]['savepath'].$info[0]['savename'];
		    }
		    if($data['photo']) $model->b_img=$data['b_img'];//相片
			$data['add_time'] = time();
			$newid = $model->add($data);
			if(!$newid>0){
				alogs("AusersAdd",$newid,0,'管理员添加失败！');//管理员操作日志
				$this->error('添加失败，请确认填入数据正确');
				exit;
			}
			alogs("AusersAdd",$newid,1,'管理员添加成功！');//管理员操作日志
			$this->assign('jumpUrl', U('/admin/Broker/'));
			$this->success('管理员添加成功');
		}else{
			$data['id'] = intval($_POST['uid']);
			$data['user_pass'] = trim($data['user_pass']);
			if( empty($data['user_pass']) ) unset($data['user_pass']);
			foreach($data as $key => $v){
				if($key == "user_pass") $data[$key] = md5($data['user_pass']);
				else $data[$key] = EnHtml($v);
			}
			$map['user_name'] = text($data['user_name']);
			$map['id'] = array('neq',$_POST['uid']);
			$count = M('members')->where($map)->count('id');
			$count2 = M('ausers')->where($map)->count('id');
			if($count>0 || $count2>0) $this->error('用户名重复！');
			$data['area_name'] = M("area")->getFieldById($data['area_id'],'name');
			
			if(!empty($_FILES['photo']['name'])){
			    $this->saveRule = date("YmdHis",time()).rand(0,1000);
			    $this->savePathNew = C('ADMIN_UPLOAD_DIR').'Product/';
			    $this->thumbMaxWidth = C('PRODUCT_UPLOAD_W');
			    $this->thumbMaxHeight = C('PRODUCT_UPLOAD_H');
			    $info = $this->CUpload();
			    $data['photo'] = $info[0]['savepath'].$info[0]['savename'];
		    }
		    if($data['photo']) $model->b_img=$data['b_img'];//相片
			
			$newid = $model->save($data);
			if(!$newid>0){
				alogs("AusersEdit",$newid,0,'管理员修改失败！');//管理员操作日志
				$this->error('修改失败，数据没有改动或者改动未成功');
				exit;
			}
			alogs("AusersEdit",$newid,1,'管理员修改成功！');//管理员操作日志
			$this->assign('jumpUrl', U('/admin/Broker/'));
			$this->success('管理员修改成功');
		}
		
    }



    public function doDelete()
    {
		//`mxl:team20150116`
		$aid = session('admin_id');
		if (chkGroup($aid, $ainfo) === false){ $this->error("请重新登录"); }
		if ($ainfo['me'] !== "other"){ $this->error("没有权限进行删除操作"); }
		//`mxl:team20150116`
		$id=$_REQUEST['idarr'];
		$delnum = M('ausers')->where("id in ({$id})")->delete(); 

		if($delnum){
			alogs("AusersDel",$newid,1,'管理员删除失败！');//管理员操作日志
			$this->success("管理员删除成功",'',$id);
		}else{
			alogs("AusersDel",$newid,0,'管理员删除失败！');//管理员操作日志
			$this->success("管理员删除失败");
		}
		
    }
	
	public function header(){
		$kfuid = intval($_GET['id']);
		$this->assign("kfuid",$kfuid);
		$this->display();
	}
	public function reset(){
		$jingjirenid = $_GET['id'];
		import("ORG.Util.Page");
		
		$AdminU = M('ausers');
		$page_size = ($page_szie==0)?C('ADMIN_PAGE_SIZE'):$page_szie;
		$page_size = 10;
		
		//`mxl:team20150116`
		$right_leader = getGroupID("团队长", C("RIGHT_TEAM_LEADER"));
		$map['u_group_id'] = $right_leader;
		$count = $AdminU->where($map)->count();
		//`mxl:team20150116`
		//$count  = $AdminU->where("u_group_id=25")->count(); // 查询满足要求的总记录数   //`mxl:team20150116`hide
		$Page = new Page($count,$page_size); // 实例化分页类传入总记录数和每页显示的记录数   
		$show = $Page->show(); // 分页显示输出
		   
		$fields = "id,user_name,u_group_id,real_name,is_ban,area_name,is_kf,qq,phone,user_word";
		$order = "id DESC,u_group_id DESC";
		//$map['u_group_id'] = 25;//`mxl:team20150116`hide
		$list = $AdminU->field(true)->where($map)->order($order)->limit($Page->firstRow.','.$Page->listRows)->select();

		$AdminUserList = $list;
		
		$GroupArr = get_group_data();
		foreach($AdminUserList as $key => $v){
			$AdminUserList[$key]['groupname'] = $GroupArr[$v['u_group_id']]['groupname'];
		}

		$this->assign('position', '管理员管理');
		$this->assign('pagebar', $show);
		$this->assign('admin_list', $AdminUserList);
		$this->assign('arealist', M("area")->field("id,name")->where("is_open=1")->select());
		$this->assign('group_list', $GroupArr);
		$this->assign('jingjirenid', $jingjirenid);
        $this->display();
	}
	public function resetact(){
	    $teamid =$_POST['setradio'];
		$jingjirenid =$_POST['jingjirenid'];
		if(empty($teamid)){
		    echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' /><script language='javascript' type='text/javascript'>alert('重置失败');parent.location='/admin/broker/index';</script>";
		}else{
			$data['parent'] = $teamid;
			$data['guanlian_time'] = time();
		    $op = M('ausers')->where("id={$jingjirenid}")->save($data);
			if($op){
			     //$this->success("重置成功");
				 echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' /><script language='javascript' type='text/javascript'>alert('重置成功');parent.location='/admin/broker/index';</script>";
				 
			}else{
			     //$this->error("重置关系失败");
				 echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' /><script language='javascript' type='text/javascript'>alert('重置关系失败');parent.location='/admin/broker/index';</script>";
				 
			}
		}
	}
	public function memberheaderuplad(){
		$uid = intval($_GET['uid']) + 10000000;
		if($uid<=10000000) exit;
		else{
			alogs("AusersEditHead",0,0,'编辑管理员头像！');//管理员操作日志
			redirect(__ROOT__."/Style/header/upload.php?uid={$uid}");
		}
		exit;
	}
    public function getarea(){
		$rid = intval($_GET['rid']);
		if(empty($rid)){
			$data['NoCity'] = 1;
			exit(json_encode($data));
		}
		$map['reid'] = $rid;
		$alist = M('area')->field('id,name')->order('sort_order DESC')->where($map)->select();

		if(count($alist)===0){
			$str="<option value=''>--该地区下无下级地区--</option>\r\n";
		}else{
			if($rid==1) $str.="<option value='0'>请选择省份</option>\r\n";
			foreach($alist as $v){
				$str.="<option value='{$v['id']}'>{$v['name']}</option>\r\n";
			}
		}
		$data['option'] = $str;
		$res = json_encode($data);
		echo $res;
	}		
    public function relieve(){
	    $id =$_POST['aid'];
		if(empty($id)){
		    ajaxmsg("无法解除",0);
		}else{
			//`mxl:team20150116`
			$aid = session('admin_id');
			if (chkGroup($aid, $ainfo) === false){ ajaxmsg("请重新登录", 0); }
			if ($ainfo['me'] === "member"){ ajaxmsg("没有权限进行解除关联操作", 0); }
			if ($ainfo['me'] === "leader"){
				$pid = M("ausers")->where("id = {$id}")->getField("parent");
				if ($pid != $aid){ ajaxmsg("没有权限进行解除关联操作", 0); }
			}
			//`mxl:team20150116`
			$data['parent'] = 0;
		    $op = M('ausers')->where("id={$id}")->save($data);
			if($op){
			     ajaxmsg("解除关联成功");
			}else{
			     ajaxmsg("解除关系失败",0);
			}
		}
		
	}
	public function ajax_reset(){
	    $jingjirenid = $_GET['id'];
		$this->assign('jingjirenid', $jingjirenid);
		$data['content'] = $this->fetch();
		exit(json_encode($data));
	}
}
?>
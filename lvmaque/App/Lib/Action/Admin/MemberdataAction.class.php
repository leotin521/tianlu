<?php
//解决火狐swfupload的session bug
if (isset($_POST[session_name()]) && empty($_SESSION)) {
    session_destroy();
    session_id($_POST[session_name()]);
    session_start();
}
class MemberdataAction extends ACommonAction{
    public function index(){
		$map=array();
		if($_REQUEST['se_type']!=""){
			$map['mf.type'] = intval($_REQUEST['se_type']);
			$search['type'] = $map['mf.type'];
		}
		if($_REQUEST['status']!=''){
			$map['mf.status'] = intval($_REQUEST['status']);
   	   $search['status'] = intval($_REQUEST['status']);
		}
  
		if(!empty($_REQUEST['uname'])&&!$_REQUEST['uid']){
			$uid = M("members")->getFieldByUserName(text($_REQUEST['uname']),'id');
			$map['mf.uid'] = $uid;
			$search['uname'] = htmlspecialchars($_REQUEST['uname'], ENT_QUOTES);
		}
		if(!empty($_REQUEST['uid'])){
			$map['mf.uid'] = intval($_REQUEST['uid']);
			$search['uid'] = $map['mf.uid'];
			$search['uname'] = htmlspecialchars($_REQUEST['uname'], ENT_QUOTES);
		}
		import("ORG.Util.Page");
		$count = M('member_data_info mf')->join("{$this->pre}members m ON m.id=mf.uid")->where($map)->count('mf.uid');
		$p = new Page($count, C('ADMIN_PAGE_SIZE'));
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";

		$list = M('member_data_info mf')->field('mf.*,au.real_name,m.user_name as uname')->join("{$this->pre}members m ON m.id=mf.uid")->join("{$this->pre}ausers au ON au.id=mf.deal_user")->where($map)->limit($Lsql)->order("mf.id DESC")->select();
		$list = $this->_listFilter($list);
		$this->assign('search',$search);
		$this->assign('list',$list);
		$this->assign('pagebar',$page);
        $this->display();
    }

	public function _editFilter(){
	}
	
	public function doEdit(){
		$vd = M('member_data_info')->find(intval($_POST['id']));
        if (!is_array($vd))    $this->error("数据出错，该记录不存在");
        $integration = FS('Webconfig/integration');
        
        
        $type = intval($_POST['type']);
        $vd['status'] = (intval($_POST['status'])==1)?1:2;
        $vd['deal_info'] = text($_POST['deal_info']);
        $vd['deal_credits'] = $vd['status']==1 ? $integration[$type]['fraction'] : 0;
        
        $vd['deal_user'] = $this->admin_id;
        $vd['deal_time'] = time();
        
        $add_credits = $vd['deal_credits'];

        //保存当前数据对象
        if ($result = M('member_data_info')->save($vd)) { //保存成功
			if($add_credits<>0) memberCreditsLog($vd['uid'],1,$add_credits,"审核".$vd['id']."号资料(".$vd[data_name]."),信用积分增加".$add_credits);
			alogs("Memberdata",0,1,'成功执行了会员第'.$vd['id'].'号资料('.$vd[data_name].')的审核操作！');//管理员操作日志
            //成功提示
            $this->assign('jumpUrl');
            $this->success(L('修改成功'));
        } else {
			alogs("Memberdata",0,0,'执行会员第'.$vd['id'].'号资料('.$vd[data_name].')的审核操作失败！');//管理员操作日志
            //失败提示
            $this->error(L('修改失败'));
        }
	}

	public function upload(){
		//$Bconfig = require C("APP_ROOT")."Conf/borrow_config.php";
		//$Bconfig = get_bconf_setting();
		$uid = intval($_REQUEST['uid']);
		$user = M("members")->field("id AS uid,user_name")->find($uid);
		if(!is_array($user)) $this->error("数据出错，该用户不存在");

		//$this->assign('Bconfig',$Bconfig);
		//$this->assign('Bconfig',$this->gloconf);
		 $upload_type = get_upload_type($this->uid);
		$this->assign("upload_type", $upload_type); // 上传资料所有类型
		$this->assign('vo',$user);
        $this->display();
	}

	public function doupload(){
		// var_dump($_REQUEST);var_dump($_SESSION); exit();
		$show['uid'] = intval($_REQUEST['uid']);
		$show['add_time'] = time();
		$show['deal_user'] = session('adminname');
		foreach ($_POST['swfimglist'] as $key => $v ){ 
			$show['data_url']  = substr($v,1);                
			$show['data_name']  = $_POST['picinfo'][$key]; 
			$show['type']  = $_POST['pictype'][$key]; 
			
			$dot= explode(".",substr($v, 1));
			$show['ext'] = $dot[1];
		
			M("member_data_info")->add($show);
			//var_dump(M()->getError );
		}
		$result = alogs("Memberdata",0,1,'成功执行了上传会员资料的操作！');//管理员操作日志
		//$this->assign('jumpUrl', __URL__."/".session('listaction'));
		$this->success("保存成功"); 
	}
	
	public function uploadshow(){
		$uid = intval($_REQUEST['uid']);
		$user = M("members")->field("id AS uid,user_name")->find($uid);
		if(!is_array($user)) $this->error("数据出错，该用户不存在");

		$this->assign('vo',$user);
        $this->display();
	}

	public function doUploadShow(){
		$show['uid'] = intval($_REQUEST['uid']);
		$show['deal_time'] = time();
		$show['deal_user'] = session('adminname');

		alogs("Memberdata",0,1,'成功执行了上传会员展示资料的操作！');//管理员操作日志
		//$this->assign('jumpUrl', __URL__."/".session('listaction'));
		$this->success("保存成功");
	}

	public function swfupload() {
		$uid = intval($_REQUEST['uid']);
		
		if ( $_POST['picpath'] ){ //删除
			$imgpath = substr( $_POST['picpath'], 1 );           
			if ( in_array( $imgpath, $_SESSION['imgfiles'] ) ){                
				$res = unlink( C( "WEB_ROOT" ).$imgpath );                
				if ( $res )    	$this->success( "删除成功", "", $_POST['oid'] );                
				else 			$this->error( "删除失败", "", $_POST['oid'] );                
			}else{                
				$this->error( "图片不存在", "", $_POST['oid'] );            
			}        
		} else { //上传
			$this->savePathNew = C( "ADMIN_UPLOAD_DIR" )."MemberData/$uid/";            
			$this->saveRule = date( "YmdHis", time() ).rand( 0, 1000 );            
			$info = $this->CUpload(); 

			if ( !isset( $_SESSION['count_file'] ) )	$_SESSION['count_file'] = 1;            
			else 				++$_SESSION['count_file'];

			$data['img'] = $info[0]['savepath'].$info[0]['savename'];  
			
			          
			$_SESSION['imgfiles'][$_SESSION['count_file']] = $data['img'];            
			echo "{$_SESSION['count_file']}:".__ROOT__."/".$data['img'];        
		}    
	} 
	
	public function _listFilter($list){
		$Bconfig = require C("APP_ROOT")."Conf/borrow_config.php";
		//$Bconfig = get_bconf_setting();
		$upload_type = FilterUploadType(FS("Webconfig/integration"));
		$this->assign("upload_type", $upload_type); // 上传资料所有类型
		$arr = array();
		foreach($upload_type as $key=>$val){
		    $arr[$key] = $val['description'];
		}
		$this->assign("data_type", $arr); 
		$row=array();
		//$this->assign("data_type",$this->gloconf['DATA_TYPE']);
		$this->assign("data_status",$Bconfig['DATA_STATUS']);
		
		//dump($this->gloconf);exit;
		foreach($list as $key=>$v){
			$v['status_name'] = $Bconfig['DATA_STATUS'][$v['status']];
			//$v['type_name'] = $this->gloconf['DATA_TYPE'][$v['type']];
			$v['type_name'] = $upload_type[$v['type']]['description'];
			
			//资料图片查看权限
			/*if( session('admin')==119||session('admin')==129 ){//管理员
			}elseif( session('admin')==139||session('admin')==140 ){//审核
			}elseif( session('admin_is_kf')==1 && $v['type']==22 ){//客服的电话清单
			}else{
				$v['data_url'] = '2'; //session('admin_id')
			}*/
			$row[$key]=$v;
		}
		return $row;
	}
}
?>
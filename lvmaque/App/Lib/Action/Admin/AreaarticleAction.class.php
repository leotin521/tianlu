<?php
// 全局设置
class AreaarticleAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function index()
    {
		$field= 'id,title,type_id,art_writer,art_time';
		$this->_list(D('Areaarticle'),$field,array("area_id"=>session("admin_area_id")),'id','DESC');
        $this->display();
    }
	
    public function _addFilter()
    {
		$typelist = get_type_leve_list_area('0','Aacategory',session("admin_area_id"));//分级栏目
		$this->assign('type_list',$typelist);
    }
	
	public function _doDelFilter($id){
		$x =  D('Areaarticle')->where("id in ({$id}) AND area_id<>".session('admin_area_id')."")->count();
		if($x>0){
			alogs("AreaarticleDel",0,0,'删除失败,所删除的文章包含了没有权限删除的文章！');//管理员操作日志
			$this->error("删除失败,所删除的文章包含了没有权限删除的文章");
			exit;
		}
	}

	public function _doAddFilter($m){
		if(!empty($_FILES['imgfile']['name'])){
			$this->saveRule = date("YmdHis",time()).rand(0,1000);
			$this->savePathNew = C('ADMIN_UPLOAD_DIR').'Article/' ;
			$this->thumbMaxWidth = C('ARTICLE_UPLOAD_W');
			$this->thumbMaxHeight = C('ARTICLE_UPLOAD_H');
			$info = $this->CUpload();
			$data['art_img'] = $info[0]['savepath'].$info[0]['savename'];
		}
		if($data['art_img']) $m->art_img=$data['art_img'];
		$m->art_time=time();
		$m->area_id = session("admin_area_id");
		$m->art_writer = session("admin_user_name");
		if($_POST['is_remote']==1) $m->art_content = get_remote_img($m->art_content);
		return $m;
	}

	public function _doEditFilter($m){
		if(!empty($_FILES['imgfile']['name'])){
			$this->saveRule = date("YmdHis",time()).rand(0,1000);
			$this->savePathNew = C('ADMIN_UPLOAD_DIR').'Article/' ;
			$this->thumbMaxWidth = C('ARTICLE_UPLOAD_W');
			$this->thumbMaxHeight = C('ARTICLE_UPLOAD_H');
			$info = $this->CUpload();
			$data['art_img'] = $info[0]['savepath'].$info[0]['savename'];
		}
		if($data['art_img']) $m->art_img=$data['art_img'];
		$m->area_id = session("admin_area_id");
		if($_POST['is_remote']==1) $m->art_content = get_remote_img($m->art_content);
		return $m;
	}

	public function _editFilter($id){
		$x =  D('Areaarticle')->where("id = {$id} AND area_id=".session('admin_area_id')."")->count();
		if($x==0){
			alogs("AreaarticleEdit",0,0,'不能编辑,没有此文章的编辑权限！');//管理员操作日志
			$this->error("不能编辑,没有此文章的编辑权限");
			exit;
		}
		$typelist = get_type_leve_list_area('0','Aacategory',session("admin_area_id"));//分级栏目
		$this->assign('type_list',$typelist);
	}
	
	public function _listFilter($list){
	 	$listType = D('Aacategory')->getField('id,type_name');
		$row=array();
		foreach($list as $key=>$v){
			$v['type_name'] = $listType[$v['type_id']];
			$row[$key]=$v;
		}
		return $row;
	}
	
}
?>
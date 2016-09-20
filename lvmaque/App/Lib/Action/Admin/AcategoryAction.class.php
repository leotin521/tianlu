<?php
// 全局设置
class AcategoryAction extends ACommonAction
{
	var $typeleve=1;
	var $typeleve_default=1;
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function index()
    {
		$field= true;
		$this->_list(D('Acategory'),$field,array("parent_id"=>0, 'model'=>'article', 'type_keyword'=>''),'sort_order');
        $this->display();
    }
	
    public function _addFilter()
    {
	    $model = isset($_GET['model'])? $_GET['model']: 'article';
        $typelist = get_type_leve_list('0','acategory', $model);//分级栏目
        $this->assign('type_list',$typelist);
        $this->assign('model', $model);
    }

	public function _doAddFilter($m){
	    $m->type_name = htmlspecialchars($m->type_name, ENT_QUOTES);
	    $m->type_nid = htmlspecialchars($m->type_nid, ENT_QUOTES);
	    $m->type_content = $m->type_content;
	    if ($_FILES["file"]["name"]){
    	    if(file_exists("UF/Uploads/Coicon/".$_FILES["file"]["name"])){
    	        $this->error("文件名已经存在");
    	    }else{
    	        $path = "UF/Uploads/Coicon";
				mkdir($path);
    	        move_uploaded_file($_FILES["file"]["tmp_name"],$path."/".$_FILES["file"]["name"]);
    	    }
    	    $m->type_img='/'.$path."/".$_FILES["file"]["name"];
	    }
		$m->parent_id=intval($m->parent_id);
		$m->add_time=time();
		return $m;
	}

	public function _doEditFilter($m){
	    $m->type_name = htmlspecialchars($m->type_name, ENT_QUOTES);
	    $m->type_nid = htmlspecialchars($m->type_nid, ENT_QUOTES);
	    $m->type_content = $m->type_content;
	    if ($_FILES["file"]["name"])
	    {
	        if(file_exists("UF/Uploads/Coicon/".$_FILES["file"]["name"])){
	            $this->error("文件名已经存在");
	        }else {
	            //删除原图标
	            $file = ltrim($_POST['old_img'],'/');
	            unlink($file);
	            //添加新图标
	            $path = "UF/Uploads/Coicon/".$_FILES["file"]["name"];
	            move_uploaded_file($_FILES["file"]["tmp_name"],$path);
	        }
	        $m->type_img='/'.$path;
	    }
		$m->parent_id=intval($m->parent_id);
		$type_nid = $m->type_nid;
		S('data_article_category'.$type_nid,NULL);
		return $m;
	}

	public function _editFilter($id){
        $model = isset($_GET['model'])? $_GET['model']: 'article';
    	//-------变动部分 start -----#
    	$pid_1=D("article_category")->field('parent_id')->where("id=$id")->find();
        if ($pid_1['parent_id']!=0) {
        	$pid_2=D("article_category")->field('parent_id')->where("id=".$pid_1['parent_id']."")->find();
        	$typelist=D("article_category")->field('type_name,id,parent_id')->where("parent_id=".$pid_2['parent_id']."")->select();
        }else {
        	//$typelist="";
        }
    	//-------变动部分 end -----#
    	//原代码
    	//$typelist = get_type_leve_list('0','acategory', $model);//分级栏目
		$this->assign('type_list',$typelist);
	}

	public function addmultiple(){
        $model = isset($_GET['model'])? $_GET['model']: 'article';
		$typelist = get_type_leve_list('0','acategory', $model);//分级栏目
		$this->assign('model',$model);
		$this->assign('type_list',$typelist);
        $this->display();
	}
	
	public function doAddMul(){
		$mul_type=explode(",",$_POST['type_name']);
		$mul_nid=explode(",",$_POST['type_nid']);
		$Type=D("Acategory");
		foreach($mul_type as $key => $v){
			$data=array();
			$data['type_name'] = $v;
			$data['type_nid'] = $mul_nid[$key];
			$data['parent_id'] = intval($_POST['parent_id']);
			$data['type_set'] = intval($_POST['type_set']);
			$data['is_hiden'] = intval($_POST['is_hiden']);
			$data['add_time'] = time();
			$data['model'] = text($_POST['model']);
			$data['type_url'] = text($_POST['type_url']);
			$data = filter_only_array($data);
			$newid = $Type->add($data);
		}
		
        if($newid){
			$this->success("栏目批量添加成功");
		}else{ 
			$this->error("添加失败");
		}
	}
	
    public function listType()
    {
		$typeid=intval($_REQUEST['typeid']);
        $model = htmlspecialchars($_REQUEST['model']);
		$sonlist = D('Acategory')->field(true)->where("parent_id={$typeid}")->select();
		$sonlist = $this->_listFilter($sonlist);
		$list="";
		foreach($sonlist as $key=>$v){
		$leve = $this->_typeLeve($v['id']);
		$haveson=$v['haveson'];
		$list.='<tr overstyle="on" id="list_'.$v['id'].'" class="leve_'.$leve.'" typeid="'.$v['id'].'" parentid="'.$v['parent_id'].'">
				<td><input type="checkbox" name="checkbox" id="checkbox2" onclick="checkon(this)" value="'.$v['id'].'"></td>
				<td>'.$v['id'].'</td>
				<td>'.($haveson?'<span class="typeson typeon" data="son">&nbsp;</span>':'<span class="typeson">&nbsp;</span>').$v['type_name'].'</td>
				<td>'.$v['type_nid'].'&nbsp;</td>
				<td>'.$v['sort_order'].'</td>
				<td>
					<a href="'.__URL__.'/edit?id='.$v['id'].'&model='.$model.'">编辑</a> 
					<a href="javascript:void(0);" onclick="del('.$v['id'].');">删除</a>  
				</td>
			  </tr>';
		}
		

		$data['inner'] = $list;
		$data['typeid'] = $typeid;
		$this->ajaxReturn($data,"");
    }
	public function _doDelFilter($id){
		$n = D('Acategory')->where("parent_id in ({$id})")->count();
		$info = D('article_category')->where("id=$id")->select();
		//print_r($info[0]['type_img']);exit;
		if($n>0){
			$this->error("删除失败,所删除的栏目包含有子栏目,或者含有系统分类,不能删除!");
		}
		$file = ltrim($info[0]['type_img'],'/');
		if ($file) unlink($file);
		if($n==0) $n = D('article')->where("type_id in ({$id})")->count();
		if($n==0) $n = D('Acategory')->where("id in ({$id}) AND is_sys=1")->count();
	}
	
	public function _listFilter($list){
		$type_set = C('TYPE_SET');
		$row=array();
		foreach($list as $key=>$v){
			$v['haveson']  = $this->_typeSon($v['id']);
			$v['type_set'] = $type_set[$v['type_set']];
			$row[$key]=$v;
		}
		return $row;
	}
	//获取栏目的级别
	protected function _typeLeve($typeid){
		static $rt=0;//先声明要返回静态变量,不然在下面被赋值时是引用赋值
		$condition['id'] = $typeid;
		$v = D('Acategory')->field('parent_id')->where($condition)->find();
		if($v['parent_id']>0){
			$this->typeleve++;
			$this->_typeLeve($v['parent_id']);
		}else{
			$rt = $this->typeleve;
			$this->typeleve = $this->typeleve_default;
		}
		return $rt;
	}
	//获取栏目的上下级别
	protected function _typeSon($typeid){
		$condition['parent_id'] = $typeid;
		$v = D('Acategory')->field('id')->where($condition)->find();
		if($v['id']>0){
			return true;
		}else{
			return false;
		}
	}
    
    public function navigation()
    {
        $field= true;
        $this->_list(D('Acategory'),$field,array("parent_id"=>0, 'model'=>'navigation'),'sort_order');
        $this->display();
    }
	
}
?>
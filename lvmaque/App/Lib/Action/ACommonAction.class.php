<?php
// 全局设置
class ACommonAction extends Action
{
	var $savePathNew=NULL;
	var $thumbMaxHeight="50"; 
	var $thumbMaxWidth="145";
	var $thumbMaxWidthNew="10,50";
	var $thumbMaxHeightNew="10,50";
	var $thumbNew=NULL;
	var $allowExtsNew=NULL;
	var $saveRule=NULL;
	var $admin_id = 0;
	var $pre = NULL;
	//验证身份
	function _initialize(){
		$this->pre = C('DB_PREFIX');
		
		$query_string = explode("/",$_SERVER['REQUEST_URI']);
		
		!isset($this->justlogin)?$this->justlogin=false:$this->justlogin=$this->justlogin;
		if(session('admin')){//dump(session('adminname'));exit;
			$this->admin_id = session("admin");
			$this->assign('adminname',session('adminname'));
		}elseif(strtolower(ACTION_NAME) != 'verify' && strtolower(ACTION_NAME) != 'login' && strtolower(ACTION_NAME)!='logincheck'){
			redirect("/admin/index/logincheck?code=".$query_string[1]);
			exit;
		}
		
		
		if( !get_user_acl(session('admin')) && !$this->justlogin){
            if($this->isAjax()) {
                echo "<div style='padding: 20px;color:#012F51;font-weight: bold;'>对不起，权限不足</div>";
            }else {
                $this->error('对不起，权限不足');
            }
			exit;
		}

        if (method_exists($this, '_MyInit')) {
            $this->_MyInit();
        }
		
		$datag = get_global_setting();
		$this->glo = $datag;//供PHP里面使用
		$this->assign("glo",$datag);
		
		$bconf = get_bconf_setting();
		$this->gloconf = $bconf;//供PHP里面使用
		$this->assign("gloconf",$bconf);
	}
	//上传图片
    function CUpload($file=array()){
        if(!empty($_FILES)){
            return $this->_Upload($file);
        }
    }

    protected function isAjax() {
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
            if('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
                return true;
        }
        if(!empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')]))
            // 判断Ajax方式提交
            return true;
        return false;
    }

    function _Upload($file){
        import("ORG.Net.UploadFile");
        $upload = new UploadFile();

        $upload->thumb = true;
        $upload->saveRule = $this->saveRule;//图片命名规则
        $upload->thumbPrefix = 'thumb_';
        $upload->thumbMaxWidth = $this->thumbMaxWidth;
        $upload->thumbMaxHeight = $this->thumbMaxHeight;
        $upload->maxSize  = C('ADMIN_MAX_UPLOAD') ;// 设置附件上传大小
        $upload->allowExts  = C('ADMIN_ALLOW_EXTS');// 设置附件上传类型
        $upload->savePath =  $this->savePathNew?$this->savePathNew:C('ADMIN_UPLOAD_DIR');// 设置附件上传目录
        //$upload->thumbRemoveOrigin = true;//删除原图
        if (!empty($file)){
            $info = $upload->uploadOne($file);
            if(!$info) {// 上传错误提示错误信息
                $this->error($upload->getErrorMsg());
            }
        }
        else {
            if(!$upload->upload()) {// 上传错误提示错误信息
                $this->error($upload->getErrorMsg());
            }else{// 上传成功 获取上传文件信息
                $info =  $upload->getUploadFileInfo();
            }
        }
        return $info;
    }
	//上传图片END
    /**
      +----------------------------------------------------------
     * 根据表单生成查询条件
     * 进行列表过滤
      +----------------------------------------------------------
     * @access protected
      +----------------------------------------------------------
     * @param string $name 数据对象名称
      +----------------------------------------------------------
     * @return HashMap
      +----------------------------------------------------------
     * @throws ThinkExecption
      +----------------------------------------------------------
     */
    protected function _search($name = '') {
        //生成查询条件
        if (empty($name)) {
            $name = $this->getActionName();
        }
        $model = M($name);
        $map = array();
        foreach ($model->getDbFields() as $key => $val) {
            if (substr($key, 0, 1) == '_')
                continue;
            if (isset($_REQUEST[$val]) && $_REQUEST[$val] != '') {
                $map[$val] = $_REQUEST[$val];
            }
        }
        return $map;
    }

    /**
      +----------------------------------------------------------
     * 根据表单生成查询条件
     * 进行列表过滤
      +----------------------------------------------------------
     * @access protected
      +----------------------------------------------------------
     * @param Model $model 数据对象
     * @param HashMap $map 过滤条件
     * @param string $sortBy 排序
     * @param boolean $asc 是否正序
      +----------------------------------------------------------
     * @return void
      +----------------------------------------------------------
     * @throws ThinkExecption
      +----------------------------------------------------------
     */
    protected function _list($model, $field ='*', $map = array(), $sortBy = '', $asc = false,$search=array()) {
		session('listaction',ACTION_NAME);
        //排序字段 默认为主键名
		$pkname = $model->getPk();
        $order = !empty($sortBy) ? $sortBy : $pkname;
        //排序方式默认按照倒序排列
        //接受 sost参数 0 表示倒序 非0都 表示正序
        $sort = $asc ? $asc : 'desc';
        //取得满足条件的记录数
        $count = $model->where($map)->count($pkname);
        import("ORG.Util.Page");
        //创建分页对象
        $listRows = !empty($_REQUEST['listRows'])?$_REQUEST['listRows']:C('ADMIN_PAGE_SIZE');
        $p = new Page($count, $listRows);
        //分页查询数据
        $list = $model->field($field)->where($map)->order($order . ' ' . $sort)->limit($p->firstRow . ',' . $p->listRows)->select();
        //分页跳转的时候保证查询条件
        foreach ($map as $key => $val) {
            if (!is_array($val)) {
                $p->parameter .= "$key=" . urlencode($val) . "&";
            }
        }
		
		foreach($search as $key => $val){
            if (!is_array($val)) {
                $p->parameter .= "$key=" . urlencode($val) . "&";
            }
		}

        if (method_exists($this, '_listFilter')) {
            $list = $this->_listFilter($list);
        }

        //分页显示
        $page = $p->show();
        //列表排序显示
        $sortImg = $sort;                                   //排序图标
        $sortAlt = $sort == 'desc' ? '升序排列' : '倒序排列';    //排序提示
        $sort = $sort == 'desc' ? 1 : 0;                     //排序方式
		
        //模板赋值显示
        $this->assign('list', $list);
        $this->assign("pagebar", $page);
        return;
    }
	//添加
    public function add() {
		if (method_exists($this, '_addFilter')) {
            $this->_addFilter();
        }
        $this->display();
    }
	//编辑
    public function edit() {
        $model = D(ucfirst($this->getActionName()));

        $id = intval($_REQUEST['id']);
 		
		if (method_exists($this, '_editFilter')) {
            $this->_editFilter($id);
        }

        $vo = $model->find($id);
        $this->assign('vo', $vo);
        $this->display();
    }
	
	//添加数据
    public function doAdd() {
        $model = D(ucfirst($this->getActionName()));
        if (false === $model->create()) {
            $this->error($model->getError());
        }
		
		if (method_exists($this, '_doAddFilter')) {
            $model = $this->_doAddFilter($model);
        }
		
        //保存当前数据对象
        if ($result = $model->add()) { //保存成功
            //成功提示
            $this->assign('jumpUrl', __URL__);
            $this->success(L('新增成功'));
        } else {
            //失败提示
            $this->error(L('新增失败'));
        }
    }
	
	//添加数据
    public function doEdit() {
        $model = D(ucfirst($this->getActionName()));
        if (false === $model->create()) {
            $this->error($model->getError());
        }
		
		if (method_exists($this, '_doEditFilter')) {
            $model = $this->_doEditFilter($model);
        }
		
        //保存当前数据对象
        if ($result = $model->save()) { //保存成功
			if (method_exists($this, '_AfterDoEdit')) {
				$this->_AfterDoEdit();
			}
            //成功提示
            $this->assign('jumpUrl', __URL__."/".session('listaction'));
            $this->success(L('修改成功'));
        } else {
            //失败提示
            $this->error(L('修改失败'));
        }
    }

	//删除数据
	public function doDel(){
        $model = D(ucfirst($this->getActionName()));
        if (!empty($model)) {
			$id = $_REQUEST['idarr'];
            if (isset($id)) {
				if (method_exists($this, '_doDelFilter')) {
					$this->_doDelFilter($id);
				}
				$pk = $model->getPk();
				if (false !== $model->where("{$pk} in ({$id})")->delete()) {
					$this->success(L('删除成功'),'',$id);
				} else {
					$this->error(L('删除失败'));
				}
            } else {
                $this->error('非法操作');
            }
        }
	}
	
	public function memberheaderuplad(){
		$uid = intval($_GET['uid']);
        $user_id = $_SESSION['u_id'];
        if( !empty($uid) ) {
            if( $uid == $user_id || !empty($_SESSION['admin_user_id']) ) {
                redirect(__ROOT__."/Style/header/upload.php?uid={$uid}");
            }
        }
		exit;
	}
            
      
}
?>
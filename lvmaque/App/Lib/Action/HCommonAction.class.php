<?php
// 全局设置
class HCommonAction extends Action
{
	var $glo=NULL;
	//上传参数
	var $savePathNew=NULL;
	var $thumbMaxWidthNew="10,50";
	var $thumbMaxHeightNew="10,50";
	var $thumbNew=NULL;
	var $allowExtsNew=NULL;
	var $siteInfo = NULL;
	//获取公共数据
	function _initialize(){
		$datag = get_global_setting();
		$this->glo = $datag;//供PHP里面使用
		$this->assign("glo",$datag);
		
		$bconf = get_bconf_setting();
		$this->gloconf = $bconf;//供PHP里面使用
		$this->assign("gloconf",$bconf);

 		if(session("u_user_name")){
			$this->uid = session("u_id");
			$unread=M("inner_msg")->where("uid={$this->uid} AND status=0")->count('id');
			$this->assign('unread',$unread);
			$this->assign('UID',$this->uid);
		}else{
			$loginconfig = FS("Webconfig/loginconfig");
			$de_val = $this->_authcode(cookie('UKey'),'DECODE',$loginconfig['cookie']['key']);
			if(substr(md5($loginconfig['cookie']['key'].$de_val),14,10) == cookie('Ukey2')){
				$vo = M('members')->field("id,user_name")->find($de_val);
				if(is_array($vo)){
					foreach($vo as $key=>$v){
						session("u_{$key}",$v);
					}
					$this->uid = session("u_id");
					$this->assign('UID',$this->uid);
					$unread=M("inner_msg")->where("uid={$this->uid} AND status=0")->count('id');
					$this->assign('unread',$unread);
				}else{
					cookie("Ukey",NULL);
					cookie("Ukey2",NULL);
				}
			}
		}
		
        if (method_exists($this, '_MyInit')) {
            $this->_MyInit();
        }
        
        D("DebtBehavior");
        $Debt = new DebtBehavior();
        $Debt->checkDebtTime();

	}
	
	//上传图片
	function CUpload(){
		if(!empty($_FILES)){
			return $this->_Upload();
		}
	}
	
	function _Upload(){
		import("ORG.Net.UploadFile");
        $upload = new UploadFile();
		
		$upload->thumb = true;
		$upload->saveRule = $this->saveRule;//图片命名规则
		$upload->thumbMaxWidth = $this->thumbMaxWidth;
		$upload->thumbMaxHeight = $this->thumbMaxHeight;
		$upload->maxSize  = C('HOME_MAX_UPLOAD') ;// 设置附件上传大小
		$upload->allowExts  = C('HOME_ALLOW_EXTS');// 设置附件上传类型
		$upload->savePath =  $this->savePathNew?$this->savePathNew:C('HOME_UPLOAD_DIR');// 设置附件上传目录
		if(!$upload->upload()) {// 上传错误提示错误信息
			$this->error($upload->getErrorMsg());
		}else{// 上传成功 获取上传文件信息
			$info =  $upload->getUploadFileInfo();
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
    protected function _list($model, $field ='*', $map = array(), $sortBy = '', $asc = false) {
        //排序字段 默认为主键名
        $order = !empty($sortBy) ? $sortBy : $model->getPk();
        //排序方式默认按照倒序排列
        //接受 sost参数 0 表示倒序 非0都 表示正序
        $sort = $asc ? 'asc' : 'desc';
        //取得满足条件的记录数
        $count = $model->where($map)->count('id');
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
        $this->display();
    }
	//编辑
    function edit() {
        $model = M($this->getActionName());
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
        $model = D($this->getActionName());
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
        $model = D($this->getActionName());
        if (false === $model->create()) {
            $this->error($model->getError());
        }
		
		if (method_exists($this, '_doEditFilter')) {
            $model = $this->_doEditFilter($model);
        }
		
        //保存当前数据对象
        if ($result = $model->save()) { //保存成功
            //成功提示
            $this->assign('jumpUrl', __URL__);
            $this->success(L('修改成功'));
        } else {
            //失败提示
            $this->error(L('修改失败'));
        }
    }

	protected function _authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
			// 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
			$ckey_length = 4;
			// 密匙
			$key = md5($key ? $key : "lzh_jiedai");
			// 密匙a会参与加解密
			$keya = md5(substr($key, 0, 16));
			// 密匙b会用来做数据完整性验证
			$keyb = md5(substr($key, 16, 16));
			// 密匙c用于变化生成的密文
			$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
			// 参与运算的密匙
			$cryptkey = $keya.md5($keya.$keyc);
			$key_length = strlen($cryptkey);
			// 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性
			// 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
			$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
			$string_length = strlen($string);
			$result = '';
			$box = range(0, 255);
			$rndkey = array();
			
			// 产生密匙簿
			for($i = 0; $i <= 255; $i++) {
				$rndkey[$i] = ord($cryptkey[$i % $key_length]);
			}
			
			// 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
			for($j = $i = 0; $i < 256; $i++) {
				$j = ($j + $box[$i] + $rndkey[$i]) % 256;
				$tmp = $box[$i];
				$box[$i] = $box[$j];
				$box[$j] = $tmp;
			}
			
			// 核心加解密部分
			for($a = $j = $i = 0; $i < $string_length; $i++) {
				$a = ($a + 1) % 256;
				$j = ($j + $box[$a]) % 256;
				$tmp = $box[$a];
				$box[$a] = $box[$j];
				$box[$j] = $tmp;
				// 从密匙簿得出密匙进行异或，再转成字符
				$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
			}
			
			
			if($operation == 'DECODE') {
				// substr($result, 0, 10) == 0 验证数据有效性
				// substr($result, 0, 10) - time() > 0 验证数据有效性
				// substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性
				// 验证数据有效性，请看未加密明文的格式
				if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
					return substr($result, 26);
				} else {
					return '';
				}
			} else {
				// 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
				// 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
				return $keyc.str_replace('=', '', base64_encode($result));
			}
		}

}
?>
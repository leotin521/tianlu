<?php
// 全局设置
class CommentAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
   public function index()
    {
		$field= true;
		$map['type'] = 1;
		$this->_list(D('Comment'),$field,$map,'id','DESC');
        $this->display();
    }

    public function index2()
    {
		$field= true;
		$map['type'] = 2;
		$this->_list(D('Comment'),$field,$map,'id','DESC');
        $this->display('index');
    }

	public function _doEditFilter($m){
		$m->deal_time = time();
		return $m;
	}

}
?>
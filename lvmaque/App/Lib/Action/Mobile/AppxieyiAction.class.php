<?php
class AppHuanDengAction extends HCommonAction {


   public function index(){
	   
		 //$id=9;
		$id = $_GET['id'];
        $content=M('app')->find($id);
		$content['add_time'] = date("Y-m-d H:i:s",$content['add_time']);
		$this->assign('list',$content);
        $this->display();     
}

}

?>
<?php
// 本类由系统自动生成，仅供测试用途
class appnewaleAction extends HCommonAction {

   public function index(){
		$type_id=$_GET['id'];
		$cre_id = $_GET['type_id'];
		if(empty($cre_id)){
			$content=M('article')->find($type_id);
			//$content=M('article')->where("type_id=$type_id")->order('id desc')->find();
			$content['art_time'] = date("Y-m-d H:i:s",$content['art_time']);
		}elseif(empty($type_id)){
			$content=M('article_category')->find($cre_id);
			//$content=M('article')->where("type_id=$type_id")->order('id desc')->find();
			//$content['art_time'] = date("Y-m-d H:i:s",$content['art_time']);
			$content['art_content'] = $content['type_content'];
			$content['title'] = $content['type_name'];
		}
        
		$this->assign('list',$content);
        $this->display();
      
    }


















}
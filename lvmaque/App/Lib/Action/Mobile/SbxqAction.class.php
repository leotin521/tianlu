<?php
// 本类由系统自动生成，仅供测试用途
class sbxqAction extends HCommonAction {

   public function index(){
		$type_id=$_GET['id'];
		$borrowinfo = M("borrow_info bi")->field('bi.*,ac.title,ac.id as aid')->join('lzh_article ac on ac.id= bi.danbao')->where('bi.id='.$type_id.' and borrow_type<6')->find();
        
		$this->assign('list',$borrowinfo);
        $this->display();
      
    }


















}
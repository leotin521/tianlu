<?php
   class UploadFileAction extends action{
      
	   public function index(){
	      $info=FS("Webconfig/baiduconfig");
		  if(isMobile()){
			$ismobile = 1;
		  }else{
			$ismobile = 0;
		  }

		   $this->assign("WebUrl",C("WEB_URL")) ;  
		   $this->assign("ismobile",$ismobile);  
		 $this->assign("list",$info['baidu']);
	     $this->display();
	   }
	   public function download(){
	      $info=FS("Webconfig/baiduconfig");
		  $root = C('CUR_URI');
		   $this->assign("WebUrl",C("WEB_URL")) ;  
		 $this->assign("list",$info['baidu']);
		 $this->assign("pic",$root);
	     $this->display();
	   }
   
   }


?>
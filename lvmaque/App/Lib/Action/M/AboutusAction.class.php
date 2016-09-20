<?php

class AboutusAction extends HCommonAction
{
        public function index(){
			$name = $_GET['name'];
			$t = M("article_category")->field("type_content")->where("type_name='{$name}'")->find();
			//echo $t['type_content'];
			 $this->assign('art',$t['type_content']);
			$this->display();
        }

    }

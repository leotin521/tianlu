<?php
class NewwenAction extends HCommonAction{

    public function news(){
        $pre = C('DB_PREFIX');//表前缀
        $id = intval($_GET['id']);
        $article = M('article')->field("title,art_content,art_time")->where("id = {$id}")->find();
        $this->assign('art',$article);
        $this->display();
    }

    public function xinwen(){
        $parm['type_id'] = 2;
        $parm['pagesize'] = 10;
        $list = getArticleList($parm);
        $this->assign("noticeList",$list);
        $this->assign("page",$list['page']);
        $this->display();
    }


}
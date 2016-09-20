<?php
class HelpAction extends HCommonAction{
    public function index(){
        //网站公告
        $parm['type_id'] = 9;
        $parm['limit'] = 10;
        // $a = getArticleList($parm);
        // dump($a);exit;
        $this->assign("noticeList", getArticleList($parm));
        $this->display();
    }

    public function news(){
        $pre = C('DB_PREFIX');//表前缀
        $id = intval($_GET['id']);
        $article = M('article')->field("title,art_content,art_time")->where("id = {$id}")->find();
        $this->assign('art',$article);
        $this->display();
    }

}
<?php
class LetterAction extends MobileAction
{

    public function index(){

        $msg = M('inner_msg')->field(true)->where("uid = {$this->uid}")->order("id desc")->select();//weidu
        $this->assign("quanbu",$msg);
        //dump($msg);
        $this->display();

    }

    public function yidu(){

        $msg = M('inner_msg')->field(true)->where("uid = {$this->uid} and status = 1")->order("id desc")->select();//weidu
        $this->assign("yidu",$msg);
        //dump($msg);
        $this->display();

    }

    public function weidu(){

        $msg = M('inner_msg')->field(true)->where("uid = {$this->uid} and status = 0")->order("id desc")->select();//weidu
        $this->assign("weidu",$msg);
        //dump($msg);
        $this->display();

    }

    public function infos(){
        $id = intval($_POST['id']);
        $msg = M('inner_msg')->field('msg')->where("uid = {$this->uid} && id = {$id}")->find();//weidu
        ajaxmsg($msg,1);
    }

    public function infos_edit(){
        $id = intval($_POST['id']);
        $msg = M('inner_msg')->field('msg')->where("uid = {$this->uid} && id = {$id}")->find();//weidu
        if($msg){
            $data['status'] = 1;
            $msginfo = M('inner_msg')->where("uid = {$this->uid} && id = {$id}")->save($data);
            ajaxmsg($msg,1);
        }else{
            ajaxmsg("非法数据！",1);
        }

    }

}
<?php
// 本类由系统自动生成，仅供测试用途
class MsgAction extends MCommonAction {
    //Show+Screening
    public function index(){
        $search['status'] = '1';
        if (isset($_GET['status'])){
            if (text($_GET['status'])=='unread') {
                $map['status']=0;
                $search['status'] = 2;
            }
            if (text($_GET['status'])=='read') {
                $map['status']=1;
                $search['status'] = 3;
            }
        }
        $map['uid'] = $this->uid;
        //分页处理
        import("ORG.Util.Page");
        $count = M('inner_msg')->where($map)->count('id');
        $p = new Page($count, 10);  //15
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $list = M('inner_msg')->where($map)->order('id DESC')->limit($Lsql)->select();

        //$read=M("inner_msg")->where("uid={$this->uid} AND status=1")->count('id');
        $this->assign("list",$list);
        $this->assign("pagebar",$page);
        $this->assign("status",$search['status']);
        
		$this->display();
    }
    
    //ChangeStatic
    public function changestatus(){
        $id = intval($_GET['message_id']);
        $vo = M("inner_msg")->field('msg')->where("id={$id} AND uid={$this->uid}")->find();
        if(!is_array($vo)){
            ajaxmsg('访问数据不存在~',0);
        }
        M("inner_msg")->where("id={$id} AND uid={$this->uid}")->setField("status",1);
        ajaxmsg();
    }
    //Delete
    public function delmsg(){
        $id = intval($_POST['id']);
        $wsql = "uid={$this->uid}";
        $up = M("inner_msg")->where("{$wsql} AND id in({$id})")->delete();
        if($up){
            ajaxmsg();
        }else{
            ajaxmsg('操作失败~',0);
        }
    }
    //Allread
    public function allread(){
        $data['status'] = 1;
        $newid = M("inner_msg")->where("uid={$this->uid}")->save($data);
        if($newid) ajaxmsg();
    } 
}
<?php
class WeixinadminAction extends ACommonAction{

    public function index(){  //app--banner图片设置
        $vo=M("app")->where("type=0")->order("ranges desc")->select();
        $this->assign("vo",$vo);
        $this->display();
    }

    public function dobanner(){  //执行banner图片上传
        import("ORG.Net.UploadFile");
        $upload=new UploadFile();
        $upload->maxSize=3145728;
        $upload->thumbMaxWidth ="640" ;
        $upload->thumbMaxHeight = "300";
        $upload->saveRule = 'time';
        $upload->thumb = true ;
        $upload->allowExts=array('jpg','gif','png','jpg');
        $upload->savePath='./UF/Uploads/Article/';
        $pathsave="/UF/Uploads/Article/";
        $upload->upload();
        $info=$upload->getUploadFileInfo();
        $data=$_POST;
        if(empty($data)){
            $this->error("数据更新失败");
            exit();
        }
        $rs=array();
        $rs['title']=$data['title'];
        if(!empty($info)){
            $rs['pic']=$pathsave.$info[0]['savename'];
        }else{
            $row=M("app")->where("id={$data['updateid']}")->find();
            $rs['pic']=$row['pic'];
        }

        if($data['updateid']>0){ //如果大于零说明有更新的记录
            $update['title']=$data['title'];
            $update['content']=$data['borrow_info'];
            $update['pic']=$rs['pic'];
            $update['ranges']=$data['ranges'];
            $res=M("app")->where("id={$data['updateid']}")->save($update);
            if(!empty($res)){
                $this->success("修改成功");
            }else{
                $this->error("修改失败");
            }
        }else{	    //如果为空就是没有更新的记录
            $rs['type']=0;
            $rs['content']=$data['borrow_info'];
            $result=M("app")->add($rs);
            if($result>0){
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }

    }

    public function delbanner(){  //删除banner
        $id=intval($_GET['id']);
        if($id>0){
            $rs=M("app")->where("id={$id}")->delete();
            if($rs!==false){
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }
        }else{
            $this->error("不合法的输入",U("index/"));
        }
    }

    public function advertising(){  //app广告设置
        $id=$_GET['id'];
        if(!empty($id)){
            $row=M("app")->where("id={$id}")->find();
            $row['add_time']=date("Y-m-d H:i:s",$row['add_time']);
            $this->assign("row",$row);
            $this->assign("updateid",$id);
        }

        $vo=M("app")->where("type=1")->select();
        $this->assign("vo",$vo);
        $this->display();
    }

    public function adbanner(){  //app广告设置
        $id=$_GET['id'];
        if(!empty($id)){
            $row=M("app")->where("id={$id}")->find();
            $this->assign("row",$row);
            $this->assign("updateid",$id);
        }

        $vo=M("app")->where("type=0")->select();
        $this->assign("vo",$vo);
        $this->display();
    }

    public function doadvertising(){  //添加广告
        import("ORG.Net.UploadFile");
        $upload=new UploadFile();
        $upload->maxSize=3145728;
        $upload->saveRule = 'time';
        $upload->thumb = true ;
        $upload->allowExts=array('jpg','gif','png','jpg');
        $upload->savePath='./UF/Uploads/Article/';
        $pathsave="/UF/Uploads/Article/";
        $upload->upload();
        $info=$upload->getUploadFileInfo();
        $data=$_POST;
        if(empty($data)){
            $this->error("数据更新失败");
            exit();
        }
        $rs=array();
        $rs['title']=$data['title'];
        if(!empty($info)){
            $rs['pic']=$pathsave.$info[0]['savename'];
        }else{
            $rs['pic']="";
        }

        if($data['updateid']>0){ //如果大于零说明有更新的记录
            $update['title']=$data['title'];
            $update['content']=$data['borrow_info'];
            $update['pic']=$rs['pic'];
            $res=M("app")->where("id={$data['updateid']}")->save($update);
            if(!empty($res)){
                $this->success("修改成功");
            }else{
                $this->error("修改失败");
            }
        }else{	    //如果为空就是没有更新的记录
            $rs['add_time']=time();
            $rs['type']=1;
            $rs['content']=$data['borrow_info'];
            $result=M("app")->add($rs);
            if($result>0){
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }
    }

    public function listadvertising(){
        import("ORG.Util.Page");
        $count=M("app")->where("type=1")->count('id');
        $p=new Page($count, C('ADMIN_PAGE_SIZE'));
        $pagebar=$p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        $vo=M("app")->where("type=1")->limit($Lsql)->select();
        $this->assign("pagebar",$pagebar);
        $this->assign("vo",$vo);
        $this->display();
    }

    public function deladvertising(){  //删除广告
        $id=$_GET['id'];
        if(empty($id)){
            $this->error("操作有误");
        }else{

            $result=M("app")->where("id={$id}")->delete();
            if(empty($result))
                $this->error("删除失败");
            else
                $this->success("删除成功");

        }

    }




}


?>
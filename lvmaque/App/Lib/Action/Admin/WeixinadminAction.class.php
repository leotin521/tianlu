<?php
// 本类由系统自动生成，仅供测试用途
class WelcomeAction extends ACommonAction {
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

	
}
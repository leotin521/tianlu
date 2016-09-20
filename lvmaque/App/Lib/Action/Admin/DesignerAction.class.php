<?php
// 标名设置
class DesignerAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function index() {
        $designer = FS("Webconfig/designer");
        $version = FS("Webconfig/version");
        if ($version['single']==0) unset($designer[1], $designer[2], $designer[3], $designer[4], $designer[5]); 
        if ($version['business']==0) unset($designer[6]);
        if ($version['fund']==0) unset($designer[7]);
        $this->assign('list',$designer);
        $this->display();
    }
    
    public function edit()
    {
        $designer = FS("Webconfig/designer");
        $this->assign('borrow_id',$_GET['id']);
        $this->assign('borrow_name',$designer[$_GET['id']]);
        $this->display();
    }
    
    public function doEdit() {
        $designer = FS("Webconfig/designer");
        $key = intval($_POST['borrow_id']);
        if ($key > 0) {
            $designer[$key] = text($_POST['borrow_name']);
        }
        FS("designer",$designer,"Webconfig/");
        alogs("designer",0,1,'执行了标名设置的操作！');
        $this->success("操作成功",__URL__."/index/");
    }

}
?>

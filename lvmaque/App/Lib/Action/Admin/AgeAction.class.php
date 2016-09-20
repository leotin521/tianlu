<?php
// 全局设置
class AgeAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function index()
    {
		$ageconfig = FS("Webconfig/ageconfig");

		$this->assign('leve',$ageconfig);
        $this->display();
    }
    public function save()
    {
		FS("ageconfig",$_POST['leve'],"Webconfig/");
		alogs("Age",0,1,'会员年龄别称操作成功！');//管理员操作日志
		$this->success("操作成功",__URL__."/index/");
    }
}
?>
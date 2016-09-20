<?php
// 全局设置
class LeveAction extends ACommonAction
{
    public function index()
    {
		$leveconfig = FS("Webconfig/leveconfig");

		$this->assign('leve',$leveconfig);
        $this->display();
    }

    public function save()
    {
        $data = $_POST['leve'];
        $result = array();
        for($i=1; $i<count($data)+1; $i++ ) {
            $result[$i] = filter_only_array($data[$i]);
        }
		alogs("Leve",0,1,'执行了信用积分等级数据编辑操作！');//管理员操作日志
		FS("leveconfig",$result,"Webconfig/");
		$this->success("操作成功",__URL__."/index/");
    }
}
?>
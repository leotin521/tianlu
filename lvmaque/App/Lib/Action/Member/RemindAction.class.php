<?php
// 本类由系统自动生成，仅供测试用途
class RemindAction extends MCommonAction {
	
    public function index(){
        $remind = FS("Webconfig/remind");
        $this->assign("remind",$remind); 
        $this->assign("vo",M('sys_tip')->find($this->uid));
        $this->assign('status',getMemberstatus($this->uid));
		$this->display();
    }
    public function savetip(){
        $oldtip = M('sys_tip')->where("uid={$this->uid}")->count('uid');
        $data['tipset'] = text($_POST['Params']);
        $data['uid'] = $this->uid;
        if($oldtip) $newid = M('sys_tip')->save($data);
        else $newid = M('sys_tip')->add($data);
        if($newid) echo 1;
        else echo 0;
    }
}
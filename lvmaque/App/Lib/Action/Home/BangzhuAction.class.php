<?php
// 本类由系统自动生成，仅供测试用途
class BangzhuAction extends HCommonAction {
    public function index(){
        $curMod = intval($_GET['curMod']);
        $kefu = get_qq(0);
        if( !empty($kefu) ) {
            $this->assign('kefu', $kefu[0]);
        }
        $this->assign('curMod', $curMod);
		$this->display();
    }
}
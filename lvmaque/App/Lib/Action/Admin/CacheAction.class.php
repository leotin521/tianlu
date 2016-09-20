<?php
// 标名设置
class CacheAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function index() {
        $cache = FS("Webconfig/cache");
        if ($this->isPost()) {
            FS("cache",textPost($_POST),"Webconfig/");
            alogs("cache",0,1,'执行了缓存设置的操作！');
            $this->success("操作成功",__URL__."/index/");
        }else{
            $this->assign('list',$cache);
            $this->display();
        }
    }
    
}
?>

<?php
class QitaAction extends MobileAction{
    public function about(){
        $this->display();
    }

    public function fankui(){
        $this->display();
    }
    public function backinfo(){
        $uid = $this->uid;

        $feedback['name'] = M('members')->where('id='.$uid)->getField('user_name');
        $feedback['contact'] = text($_POST['content']);
        $feedback['system'] = 'wechat';
        $feedback['ip'] = get_client_ip();
        $feedback['add_time'] = time();
        $newid = M('feedback')->add($feedback);
        if($newid){
            ajaxmsg("您的信息已成功提交，感谢您的宝贵意见！", 1);
        }else{
            ajaxmsg("对不起，信息提交失败！", 0);
        }
    }
}
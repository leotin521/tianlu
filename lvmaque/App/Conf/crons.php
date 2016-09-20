<?php
// 系统默认的核心行为扩展列表文件
return array(
	'cron_1' => array('autoagility', 3600*24), //这里的意思是每隔1秒，执行一次autorepayment.php文件
    'cron_2' => array('automemberslogin', 3600*24,strtotime(date("Y-m-d",time())+3600*24)), //这里的意思是每隔1天，执行一次automemberslogin.php文件
    'cron_3' => array('autosendmobile', 3600*24, strtotime(date("Y-m-d",time())+3600*24)), //这里的意思是每隔1天
    'cron_4' => array('bonusreturn', 3600*24, strtotime(date("Y-m-d",time())+3600*24)), //红包守护进程
    'cron_5' => array('preborrow', 30), //预告中->招标中任务，
    'cron_6' => array('flowbid', 3600*2), //自动流标，状态更新
    'cron_7' => array('autorepayment', 3600*24), //自动还款
);
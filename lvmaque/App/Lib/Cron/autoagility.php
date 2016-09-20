<?php 

    D("AgilityBehavior");
    $Bao_auto = new AgilityBehavior();
    $Bao_auto->record_autodo();
    $time = date('Y-m-d H:i:s');
    $msg = '"code":10000,"msg":"灵活宝自动还息完成","time":"'.$time.'"';
    Log::write($msg, 'info');
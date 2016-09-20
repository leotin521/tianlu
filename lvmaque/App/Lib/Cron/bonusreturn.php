<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/6/6
 * Time: 13:18
 */
try {
    BonusModel::cron_expired_return();
    $msg = '"code":10000,"msg":"红包过期返回守护完成","time":"'.$time.'"';
    Log::write($msg, 'info');
}catch (Exception $e) {
    $msg =  '"code":10001,"msg":"红包过期返回守护失败","error": '.$e->getMessage().'"time":"'.$time.'"';
    Log::write($msg);
}
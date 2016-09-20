<?php
/**
 * Author: minister.xiang@gmail.com
 * Copyright (c) 2009-2015 http://www.lvmaque.com All rights reserved.
 * Date: 2015/6/17 16:48
 */
$time = date('Y-m-d H:i:s');
try{
    $where = $count = $data = $upWhere = null;//几个cron变量可能会串
    $count = 0;
    $where = array(
        'borrow_status' => BorrowModel::BID_SINGLE_CONFIG_STATUS_WAIT_ONLINE,
        'online_time' => array('lt', date('Y-m-d H:i:s',time()))
    );
    $pre_borrow_items = BorrowModel::get_borrow_info(null, 'id,can_auto,borrow_type', $where);
    if( !empty($pre_borrow_items) && is_array($pre_borrow_items) ) {
        $data = array(
            'borrow_status' => BorrowModel::BID_SINGLE_CONFIG_STATUS_VIEW_PASS
        );
        $pre_borrow_ids = only_array($pre_borrow_items, 'id');
        $upWhere = array(
            'id' => array('in', $pre_borrow_ids)
        );
        $db = M();
        $db->startTrans();
        $count = BorrowModel::update_borrow_info(null, $data, $upWhere);
        if( $count > 0 ) {
            //处理自动投标
            foreach( $pre_borrow_items as $val ) {
                if( $val['can_auto'] == 1 ) {
                    if( $val['borrow_type'] < BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID ) {
                        autoInvest($val['id']);

                    }else{
                        autotInvest($val['id'], $val['borrow_type']);
                    }
                }
            }
        }
        $db->commit();
    }
    $msg = '"code":10000,"msg":"预告守护完成","time":"'.$time.'","count":'.$count.'';
    Log::write($msg, 'info');
}catch (Exception $e) {
    $msg =  '"code":10001,"msg":"预告守护失败","method": '. ACTION_NAME .'""error": '.$e->getMessage().'"time":"'.$time.'"';
    Log::write($msg);
}
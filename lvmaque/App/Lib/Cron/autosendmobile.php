<?php
    /**
     * 框架任务       0： 不提醒  （当前程序）
     * 系统任务    -1： 表示不提醒   （待续）
     */
    $time = date('Y-m-d H:i:s',time()); 
    $expire = get_global_setting('expire');
    if(is_array($expire) && $expire[0] !=0){
        $count = count($expire);
        for ($i=0; $i<$count; $i++){
            $note_day = intval($expire[$i]);
            $start_time  = strtotime(date('Y-m-d',strtotime("+{$note_day} days", time()))); //设置日当天0时0分0秒
            $end_time = $start_time + 24*60*60 -1; // 设置日当天23时59分59秒
            try {
                $pre = C('DB_PREFIX');
                $where = null;
                $where['d.status'] = 7;
                $where['d.deadline'] =  array(array('egt',$start_time),array('lt',$end_time));
                $list = M("investor_detail d")
                ->field("m.user_phone,m.user_name,mi.real_name,d.borrow_id,d.sort_order,d.deadline,sum(capital) as capital,sum(interest) as interest")
                ->join("{$pre}members m ON m.id=d.borrow_uid")
                ->join("{$pre}member_info mi ON m.id=mi.uid")
                ->group("borrow_id")
                ->where($where)
                ->select();
        
                $smsTxt = FS("Webconfig/smstxt");
                $smsTxt = de_xie($smsTxt);
        
                $web_name = get_global_setting('web_name');
        
                if( !empty($list) ) {
                    foreach($list as $val) {
                        $total = '';
                        if( !empty($val['user_phone']) ) {
                            $repay_time = date('Y-m-d', $val['deadline']);
                            $total = bcadd($val['capital'] , $val['interest'], 2);
        
                            # v1
        
                            #设置模版
                            #USERANEM#您好，您的第#NUM#号借款标的第#SORT#期还款将要到期。还款总金额为#MONEY#,最后还款日为#DAY#【绿麻雀友情提醒】
                            sendsms($val['user_phone'], str_replace(array("#USERANEM#", "#NUM#", "#SORT#", "#MONEY#", "#DAY#"), array($val['user_name'], $val['borrow_id'], $val['sort_order'], $total, $repay_time), $smsTxt['collection']));
        
        
                            # v2
                            #后台未设置模版
                            //                    $msg = '';
                            //                    $msg = "#UserName#您好，您的第{$val['borrow_id']}号借款标的第{$val['sort_order']}期还款将要到期。还款总金额为{$total},最后还款日为{$repay_time}【{$web_name}友情提醒】";
                            //                    sendsms($val['user_phone'], str_replace(array("#UserName#"), array($val['real_name']), $msg));
                        }
                        }
                        }
                                $msg = '"code":10000,"msg":"逾期催收短信守护完成","time":"'.$time.'"';
                            Log::write($msg, 'info');
            }catch(Exception $e){
            $msg =  '"code":10001,"msg":"逾期催收短信守护失败","error": '.$e->getMessage().'"time":"'.$time.'"';
            Log::write($msg);
            }
        }
    }else{
        $msg = '';
        Log::write($msg);
    }
    
    
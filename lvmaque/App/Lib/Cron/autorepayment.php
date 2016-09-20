<?php 
    $time = date('Y-m-d H:i:s',time());
    try {
        $pre = C('DB_PREFIX');
        $where = null;
        $where['d.status'] = InvestorDetailModel::INVEST_DETAIL_STATUS_REPAYING;
        $time_date = strtotime(date('Y-m-d', time()));
        $where['d.deadline'] =  array(array('egt',$time_date),array('lt',($time_date+86400)));
        $list = M("investor_detail d")
                ->field("m.user_phone,m.user_name,d.borrow_id,d.sort_order,d.deadline,sum(d.capital+d.interest) as paymoney,sum(mm.account_money+mm.back_money) as money")
                ->join("{$pre}members m ON m.id=d.borrow_uid")
                ->join("{$pre}member_money mm ON mm.uid=d.borrow_uid")
                ->where($where)
                ->group("d.borrow_id")
                ->select();
        
        $web_name = get_global_setting('web_name');
        
        if( !empty($list) ) {
            foreach($list as $val) {
                if ($val['money']<$val['paymoney']) {
                    $total = '';
                    if( !empty($val['user_phone']) ) {
                        $repay_time = date('Y-m-d', $val['deadline']);
                        $total = $val['paymoney'];
                    
                        $msg = '';
                        $msg = "#UserName#您好，您的第{$val['borrow_id']}号借款标的第{$val['sort_order']}期还款将要到期。还款总金额为{$total}，最后还款日为{$repay_time}，由于您的账户可用余额不足，无法进行自动还款，请及时进行账户充值【{$web_name}友情提醒】";
                        sendsms($val['user_phone'], str_replace(array("#UserName#"), array($val['user_name']), $msg));
                    }
                }else{
                    borrowRepayment($val['borrow_id'],$val['sort_order'],1);
                }
            }
        }
        $msg = '"code":20000,"msg":"自动还款/短信提醒守护完成","time":"'.$time.'"';
        Log::write($msg, 'info');
    }catch(Exception $e){
        $msg =  '"code":20001,"msg":"自动还款/短信提醒守护失败","error": '.$e->getMessage().'"time":"'.$time.'"';
        Log::write($msg);
    }
<?php

// 本类由系统自动生成，仅供测试用途
class BorrowcapitalAction extends MCommonAction {

    public function index() {
            //待还折线图
            $interest_wait = 
                    M('investor_detail')
                    ->field("sum(interest+capital) as shouyi,FROM_UNIXTIME(deadline,'%Y%m') as deadline")
                    ->where("borrow_uid ={$this->uid} and repayment_time=0 and status in(6,7)")
                    ->group("FROM_UNIXTIME(deadline,'%Y%m')")
                    ->select();
            $date_first_month = $interest_wait[0]['deadline'];
            foreach ($interest_wait as $key => $v) {
                $interest_wait[$key]['deadline'] = strtotime($v['deadline'] . "01 08:00:00");
                $time_last_month = strtotime($v['deadline'] . "01 08:00:00");
            }
            $thismonth = date("Ym", time());
            $time1 = strtotime($thismonth . "01 08:00:00");
            $time2 = strtotime("+1 month", $time_last_month);

            $duration[]['deadline'] = $time1;
            while ($time1 < $time2) {
                $time1 = strtotime("+1 month", $time1);
                $duration[]['deadline'] = $time1;
            }
            foreach ($duration as $key => $val) {
                $status = 0;
                foreach ($interest_wait as $k => $v) {
                    if ($val['deadline'] == $v['deadline']) {
                        $status = 1;
                        $shouyi = $v['shouyi'];
                    }
                }
                if ($status === 0) {
                    $list[] = array("deadline" => $val['deadline'] * 1000, "shouyi" => 0);
                } elseif ($status === 1) {
                    $list[] = array("deadline" => $val['deadline'] * 1000, "shouyi" => $shouyi);
                }
            }

            $minfo = getMinfo($this->uid, "mm.money_freeze, mm.money_collect, mm.account_money, mm.back_money");
            $this->assign("interest_wait", $list);     //待还折线图
            $this->assign("minfo", $minfo);
            
            $wait = M('investor_detail') ->field("sum(interest) as interest ,sum(capital) as capital") ->where("borrow_uid ={$this->uid} and repayment_time=0 and status in(6,7) ")->find();
            $this->assign('wait',$wait);        
            
            
            $this->assign('benefit', get_personal_benefit($this->uid));   //收入
            $this->assign('pcount', get_personal_count($this->uid));
            $this->assign('out', get_personal_out($this->uid));      //支出
            
            
            $n_money = M('expand_money')->where("status=1 and expired_time> ".time()." and uid=".$this->uid)->sum('money');  //统计未使用优惠券金额  
        $this->assign('n_money', $n_money);
            $this->display();
    }
}

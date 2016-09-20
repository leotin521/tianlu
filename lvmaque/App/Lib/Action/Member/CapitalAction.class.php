<?php
// 本类由系统自动生成，仅供测试用途
class CapitalAction extends MCommonAction {

    public function index(){
        $pre = C('DB_PREFIX');
		//待收折线图，正在转让且未转让出去的待收也归转让人所有
        $where = "investor_uid ={$this->uid} and  (d.STATUS IN (6, 7) or (d.STATUS = 14 AND t.STATUS = 2 ))";
		$interest_wait = M('investor_detail d')
            ->field("sum(interest-interest_fee) as shouyi,FROM_UNIXTIME(deadline,'%Y%m') as deadline")
            ->join("{$pre}debt t ON t.invest_id = d.invest_id")
            ->where($where)
            ->group("FROM_UNIXTIME(deadline,'%Y%m')")
            ->select();
		if($interest_wait){
			$date_first_month = $interest_wait[0]['deadline'];
			foreach($interest_wait as $key=>$v){
				$interest_wait[$key]['deadline'] = strtotime($v['deadline']."01 08:00:00");
				$time_last_month = strtotime($v['deadline']."01 08:00:00");
			}
			
			$thismonth = date("Ym",time());
			$time1 =strtotime($thismonth."01 08:00:00");
			$time2 = strtotime("+1 month",$time_last_month);
			
			$duration[]['deadline'] = $time1;
			while($time1<$time2){
				$time1 = strtotime("+1 month",$time1);
				$duration[]['deadline'] = $time1;
			}
			foreach($duration as $key=>$val){
				$status = 0;
				foreach($interest_wait as $k=>$v){
					if($val['deadline']==$v['deadline']){
						$status = 1;
						$shouyi = $v['shouyi'];
					}
				}
				if($status===0){
					$list[] = array("deadline"=>$val['deadline']*1000,"shouyi"=>0);
				}elseif($status===1){
					$list[] = array("deadline"=>$val['deadline']*1000,"shouyi"=>$shouyi);
				}
			}
		}

		$minfo = getMinfo($this->uid, "mm.money_freeze, mm.money_collect, mm.account_money, mm.back_money");
		$benefit = get_personal_benefit($this->uid);
		$money_collect_total = bcadd($benefit['interest_collection'], $benefit['capital_collection'], 2);
		$this->assign('money_collect_total', $money_collect_total);
        //灵活宝资金详情
        $agility_money = BaoInvestModel::get_sum_money($this->uid);
		$expand_info = M('expand_money')->field("sum(money) as money")->where("uid={$this->uid} and expired_time > ".time()." and status=1")->find();
        $this->assign("agility_money", $agility_money);
		$this->assign("interest_wait",$list);//待收折线图
		$this->assign("minfo", $minfo);
		$this->assign('benefit', $benefit);   //收入,//TODO:灵活宝累计投资金额需要隔天才能看到
		$this->assign('pcount', get_personal_count($this->uid));
		$this->assign('out', get_personal_out($this->uid));      //支出
		$this->assign("expand_info",$expand_info);
		$this->display();
    }
}
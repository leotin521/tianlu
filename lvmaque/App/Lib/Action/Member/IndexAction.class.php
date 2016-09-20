<?php
// 本类由系统自动生成，仅供测试用途
class IndexAction extends MCommonAction {
    public function index(){ 
		$ucLoing = de_xie($_COOKIE['LoginCookie']);
		setcookie('LoginCookie','',time()-10*60,"/");
		$this->assign("uclogin",$ucLoing);
		
		//站内信
		$msg = M("inner_msg")->where("uid = {$this->uid} AND status = 0")->field("id, title")->order("send_time DESC")->find();
		$msg = (empty($msg) === false) ? "<span id='tip_msg'>{$msg['title']} <a target='_blank' href='/member/msg?id={$msg['id']}'>查看详细</a></span>" : "没有新的消息了";
		$this->assign("unread_msg", $msg);

		//资金账户
		$minfo = getMinfo($this->uid, "mm.money_freeze, mm.money_collect, mm.account_money, mm.back_money");
		$benefit = get_personal_benefit($this->uid);
		$money_collect_total = bcadd($benefit['interest_collection'], $benefit['capital_collection'], 2);
		$minfo['money_collect'] = $money_collect_total;
		//dump($minfo['money_collect']);
        //灵活宝资金详情
		$agility_money = BaoInvestModel::get_sum_money($this->uid);
		
		$agility_interest = BaoInvestModel::get_sum_interest($this->uid);
        $this->assign('agility_money', $agility_money);
        $this->assign('agility_interest', $agility_interest);

		//累计收益
		$income = get_personal_benefit($this->uid);
		$minfo['income'] = $income['total'];
		
		$this->assign("wait", $benefit['interest_collection']);
		$this->assign("minfo", $minfo);
		
		$this->display();
    }
	
	//更新通知消息//`mxl 20150306`
	public function tipMsg(){
		$ERROR = 0;
		$UPTXT = 1;
		$ALARM = 2;
		$map['status'] = 0;
		$map['uid'] = $this->uid;
		$msgs = M("inner_msg")->where($map)->field("id, title")->order("send_time DESC")->limit(2)->select();
		if (count($msgs) > 0){
			$map['status'] = 1;
			$map['id'] = $msgs[0]['id'];
			M("inner_msg")->save($map);
		}
		$msg = (count($msgs) === 2) ? "<span id='tip_msg'>{$msgs[1]['title']} <a target='_blank' href='/member/msg?id={$msgs[1]['id']}'>查看详细</a></span>" : "没有新的消息了";
		ajaxmsg($msg, $UPTXT);
	}
	
	//还款表
	public function repaylist(){
		$limit = (empty($_POST['limit']) === true) ? 4 : intval($_POST['limit']);
		$pre = C('DB_PREFIX');
		$field = "b.id as bid, b.borrow_name as name, b.borrow_type, d.deadline as expectedTime, d.repayment_time as actualTime, (d.capital + d.interest) as expectedMoney";
		$dinfo = M("investor_detail d")->field($field)->where("d.investor_uid = {$this->uid} AND d.status in (6,7)")->join("{$pre}borrow_info b ON d.borrow_id = b.id")->order("d.deadline")->limit($limit)->select();
		foreach ($dinfo as $k => $v){
			$dinfo[$k]['type'] = BorrowModel::get_borrow_type($v['borrow_type']);
			$dinfo[$k]['borrow_url'] = getBorrowUrl($v['borrow_type'], $v['bid']);
		}
		$json['data'] = $dinfo;
		$json['data']['length'] = count($dinfo);
		$json['code'] = 0;
		echo json_encode($json);
        exit;
	}
	
	//交易记录表
	public function translist(){
		$money_log = C("MONEY_LOG");
        $types = MemberMoneyLogModel::get_moneyLog_type_group();
        if( !empty($_GET['type']) ) {
            $map['type'] = array("in", $types[intval($_GET['type'])]);
        }
		$limit = (empty($_GET['limit']) === true) ? 4 : intval($_GET['limit']);
		$map['uid'] = $this->uid;
		$field = "id as mlgid, uid, add_time as addtime, affect_money as money, type, (account_money + back_money) as use_money, info as detail_cn, target_uid";
		$re = M("member_moneylog")->field($field)->where($map)->order("id DESC")->limit($limit)->select();
		foreach ($re as $k => $v){
			$re[$k]['type_cn'] = $money_log[$v['type']];
			$re[$k]['direction'] = 1;
			if (0 > intval($v['money'])){
				$re[$k]['money'] *= -1;
				$re[$k]['direction'] = 2;
			}
		}
		$json['data'] = $re;
		$json['data']['length'] = count($re);
		$json['code'] = 0;
		echo json_encode($json);
        exit;
	}
	
	//折线图数据,还款走势图
	public function dataLine(){
		$pre = C('DB_PREFIX');
		$type = intval($_GET['type']);
		if ($type !== 1 && $type !== 2 && $type !== 3){ $json['code'] = 1; echo json_encode($json); exit; }
		$arr = array("1" => array("key" => "interest", "name" => "收益", "color" => array("#C4C4C4","#FE6E00")),
					 "2" => array("key" => "capital", "name" => "本金", "color" => array("#C4C4C4","#FE6E00")),
					 "3" => array("key" => "total", "name" => "还款", "color" => array("#fb0","#0bf")));
		$time1 = "if (d.substitute_time > 0, d.substitute_time, d.repayment_time)";
		//$time2 = "if (b.rate_type = 1, i.add_time, b.second_verify_time)";
		$time2 = "d.deadline";
		$field1 = "sum(d.receive_interest) as interest, sum(d.receive_capital) as capital, sum(d.receive_interest + d.receive_capital) as total, (FROM_UNIXTIME({$time1}, '%Y') * 12 + FROM_UNIXTIME({$time1}, '%c')) as time";
		$field2 = "sum(d.interest) as interest, sum(d.capital) as capital, sum(d.interest + d.capital) as total, (FROM_UNIXTIME({$time2}, '%Y') * 12 + FROM_UNIXTIME({$time2}, '%c')) as time";
        // 已收
		$receive = M("investor_detail d")->field($field1)->where("d.investor_uid = {$this->uid} AND d.status in (1,2,3,4,5)")->group("FROM_UNIXTIME({$time1}, '%Y%m')")->order("{$time1} ASC")->select();//已收
        // 待收
		$waiting = M("investor_detail d")->field($field2)->where("d.investor_uid = {$this->uid} AND d.status in (6,7)")->join("{$pre}borrow_info b ON d.borrow_id = b.id")->join("{$pre}borrow_investor i ON d.invest_id = i.id")->group("FROM_UNIXTIME({$time2}, '%Y%m')")->order("{$time2} ASC")->select();//待收
        //灵活宝已收 TODO:灵活宝折线图未完成
//        $agility_receive = BaoInvestModel::get_sum_interest($this->uid);
        //灵活宝待收
//        $agility_collect = BaoInvestModel::get_collect_money($this->uid);
		getFullData($receive, $arr[$type]['key'], $data_receive);
		getFullData($waiting, $arr[$type]['key'], $data_waiting);
		$series[] = array("name" => "已收".$arr[$type]['name'], "data" => $data_receive, "color" => $arr[$type]['color'][0]);
		$series[] = array("name" => "待收".$arr[$type]['name'], "data" => $data_waiting, "color" => $arr[$type]['color'][1]);
		$json['data']['series'] = $series;
		$json['data']['line1'] = $data_receive;
		$json['data']['line2'] = $data_waiting;
		$json['code'] = 0;//0代表成功
		echo json_encode($json);
        exit;
	}
	
	//交易详情
	public function transferDetail(){
		$money_log = C("MONEY_LOG");
		$uid = intval($_GET["cid"]);
		$transid = intval($_GET["transid"]);
		$type = $money_log[intval($_GET["type"])];
		if ($uid === 0){ $uname = "网站平台"; }
		else{
			$uname = M("members")->getFieldById($uid, "user_name");
		}
		$json['data']['type'] = $type;
		$json['data']['name'] = $uname;
		$json['data']['transid'] = $transid;
		$json['data']['length'] = 3;
		$json['code'] = 0;
		echo json_encode($json);
		exit;
	}
	
	//新手指引状态
	public function cancel_guide(){
	    session('new_guide',null);
	}
}
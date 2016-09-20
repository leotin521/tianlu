<?php
// 全局设置
class ExpiredAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function index()
    {
		$map=array();
		$map['d.status'] = array('not in','0,14');
		$map['d.repayment_time'] = 0;
		$map['d.deadline'] = array("between","100000,".time());


		if($_REQUEST['uid'] && $_REQUEST['uname']){
			$map['d.borrow_uid'] = intval($_REQUEST['uid']);
			$search['uid'] = $map['d.borrow_uid'];	
			$search['uname'] = urldecode($_REQUEST['uname']);	
		}
		
		if($_REQUEST['uname'] && !$search['uid']){
			$map['m.user_name'] = array("like",urldecode($_REQUEST['uname'])."%");
			$search['uname'] = urldecode($_REQUEST['uname']);	
		}
		
		if($_REQUEST['status']){
			if($_REQUEST['status']==1) $map['d.substitute_money'] = array("gt",0);
			elseif($_REQUEST['status']==2) $map['d.substitute_money'] = array("elt",0);
			$search['status'] = intval($_REQUEST['status']);	
		}

		if(!empty($_REQUEST['bj']) && !empty($_REQUEST['money'])){
			$map['capital'] = array($_REQUEST['bj'],$_REQUEST['money']);
			$search['bj'] = htmlspecialchars($_REQUEST['bj'], ENT_QUOTES);	
			$search['money'] = floatval($_REQUEST['money']);	
		}

		if(!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])){
			$timespan = strtotime(urldecode($_REQUEST['start_time'])).",".strtotime(urldecode($_REQUEST['end_time']));
			$map['d.deadline'] = array("between",$timespan);
			$search['start_time'] = urldecode($_REQUEST['start_time']);	
			$search['end_time'] = urldecode($_REQUEST['end_time']);	
		}elseif(!empty($_REQUEST['start_time'])){
			$xtime = strtotime(urldecode($_REQUEST['start_time']));
			$map['d.deadline'] = array("between",$xtime.",".time());
			$search['start_time'] = $xtime;	
		}elseif(!empty($_REQUEST['end_time'])){
			$xtime = strtotime(urldecode($_REQUEST['end_time']));
			$map['d.deadline'] = array("between",time().",".$xtime);
			$search['end_time'] = $xtime;	
		}


		//if(session('admin_is_kf')==1)	$map['m.customer_id'] = session('admin_id');
		//分页处理
		import("ORG.Util.Page");
		$buildSql = M('investor_detail d')->field("d.id")->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->join("{$this->pre}members m ON m.id=b.borrow_uid")->where($map)->group('d.sort_order,d.borrow_id')->buildSql();
		$newsql = M()->query("select count(*) as tc from {$buildSql} as t");
		$count = $newsql[0]['tc'];
		$p = new Page($count, C('ADMIN_PAGE_SIZE'));
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
		
		$field = "m.user_name,d.borrow_id as id,b.borrow_name,b.borrow_type,d.status,d.total,d.borrow_id,b.borrow_uid,d.sort_order,sum(d.capital) as capital,sum(d.interest) as interest,sum(d.substitute_money) as substitute_money,d.deadline,b.borrow_duration,b.duration_unit";
		$list = M('investor_detail d')->field($field)
			->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")
			->join("{$this->pre}members m ON m.id=b.borrow_uid")
			->where($map)
			->group('d.sort_order,d.borrow_id')
			->order('d.borrow_id,d.sort_order')
			->limit($Lsql)
			->select();
		$list = $this->_listFilter($list);
		
        $this->assign("bj", array("gt"=>'大于',"eq"=>'等于',"lt"=>'小于'));
        $this->assign("status", array("1"=>'已代还',"2"=>'未代还'));
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);
        $this->assign("query", http_build_query($search));
		
        $this->display();
    }


    public function member()
    {
		$map=array();
		//$map['_string'] = ' (d.repayment_time=0 AND d.deadline<'.time().' AND d.status=0)  OR ( d.substitute_time >0 ) ';
		$map['_string'] = ' (d.repayment_time=0 AND d.deadline <'.time().' AND d.status=7)';
		if($_REQUEST['uname']){
			if($_REQUEST['uid']){
				$map['d.borrow_uid'] = intval($_REQUEST['uid']);
				$search['uid'] = $map['d.borrow_uid'];	
				$search['uname'] = urldecode($_REQUEST['uname']);	
			}else{
				$uid = M("members")->getFieldByUserName(urldecode($_REQUEST['uname']),"id");
				$map['d.borrow_uid'] = $uid;
				$search['uid'] = $map['d.borrow_uid'];	
				$search['uname'] = urldecode($_REQUEST['uname']);	
			}
		}
	//	if(session('admin_is_kf')==1)	$map['m.customer_id'] = session('admin_id');
		
		//分页处理
		import("ORG.Util.Page");
		$xcount = M('investor_detail d')->field("d.id")->where($map)->group('d.borrow_uid')->buildSql();
		$newxsql = M()->query("select count(*) as tc from {$xcount} as t");
		$count = $newxsql[0]['tc'];
		$p = new Page($count, C('ADMIN_PAGE_SIZE'));
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
		
		$buildSql = M('investor_detail d')->field("count(*) as num,sum(d.capital) as capital_all,borrow_uid")->where($map)->group('d.sort_order,d.borrow_id')->buildSql();
		$list = M()->query("select count(*) as tc,sum(t.capital_all) as total_expired,t.borrow_uid,t.borrow_uid as id,m.user_name  from {$buildSql} as t  left join {$this->pre}members m ON m.id=t.borrow_uid group by t.borrow_uid limit {$Lsql}");
		$list = $this->_listFilter($list);
		
        $this->assign("bj", array("gt"=>'大于',"eq"=>'等于',"lt"=>'小于'));
        $this->assign("status", array("1"=>'已代还',"2"=>'未代还'));
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);
        $this->assign("query", http_build_query($search));
		
        $this->display();
    }


	public function doexpired(){
		$borrow_id = intval($_GET['id']);
		$sort_order = intval($_GET['sort_order']);
		$investor_detail = M('investor_detail')->field('status')->where("borrow_id={$borrow_id} AND sort_order={$sort_order}")->find();
		if( !empty($investor_detail) ) {
			//判断是否代还过，是否需要还，上一期未还的不允许代还下一期
			$need_web_pay = InvestorDetailModel::get_need_web_repay_status();
			//判断状态是否是未还，如果已还或者已代还过，则不应该再代还
			if( !in_array($investor_detail['status'], $need_web_pay) ) {
				$this->error("已经还过了");
			}
			$vo = M('investor_detail')->where("borrow_id={$borrow_id} AND sort_order={$sort_order} AND substitute_money>0")->find();
			if(is_array($vo)) $this->error("已代还过了");
			else $newid = borrowRepayment($borrow_id,$sort_order,2);

			if($newid===true) $this->success("代还成功");
			elseif($newid) $this->error($newid);
			else  $this->error("代还失败，请重试");
		}

	}

	private function _listFilter($list){
		$row=array();
		foreach($list as $key=>$v){
			$v['breakday'] = getExpiredDays($v['deadline']);
			$v['expired_money'] = getExpiredMoney($v['breakday'],$v['capital'],$v['interest']);
			$v['call_fee'] = getExpiredCallFee($v['breakday'],$v['capital'],$v['interest']);
			$v['duration_unit_name'] = BorrowModel::get_unit_format($v['duration_unit']);
			$row[$key]=$v;
		}
		return $row;
	}
	
	
	
}
?>
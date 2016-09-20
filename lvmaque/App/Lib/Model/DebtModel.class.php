<?php
// 债权转让模型
class DebtModel extends ACommonModel {
	protected $tableName    = 'debt';
	protected $_validate	=	array(
	);

	/**
	 * 列出所有进行中的债权转让
	 *
	 * @param intval $size //每次读取数量，$page=true 每页数量，false 指定读取数量
	 * @param boolean $pstatus  // 分页开关 true 分页  false 不分页
	 */
	public static function listAll($parm, $size=0, $pstatus=true, $pagesize=15 )
	{
		$pre =  C('DB_PREFIX');
		if(empty($parm['map'])) return;
		$map= $parm['map'];

		//$map['d.status']=array("in","2,4");

		//$condition = "d.status in (2, 4)";
		$size && $pagesize = intval($size);
		$field = "d.id as debt_id,d.status,d.interest_rate, d.money, d.total_period, d.period, d.valid, d.id as debt_id, i.id as invest_id, d.assigned,d.addtime,
                     i.investor_uid, i.deadline, b.id, b.borrow_name, b.borrow_interest_rate,b.borrow_status,b.borrow_duration,m.credits, m.user_name, b.repayment_type";
		if($pstatus){
			//$count = M("debt")->where("status in (2, 4)")->count();
			$count = M("debt d")->where($map)->count();
			$Page = new Page($count, $pagesize);
			$list['data'] = M("debt d")
				->join("{$pre}borrow_investor i ON d.invest_id=i.id")
				->join("{$pre}borrow_info b ON i.borrow_id = b.id")
				->join("{$pre}members m ON i.investor_uid=m.id")
				->field($field)
				->where($map)
				->limit($Page->firstRow .','. $Page->listRows)
				->order("d.status asc")
				->select();
			$list['page'] = $Page->show();
			$list['data'] = self::homeList($list['data']);

		}else{
			$list = M("debt d")
				->join("{$pre}borrow_investor i ON d.invest_id=i.id")
				->join("{$pre}borrow_info b ON i.borrow_id = b.id")
				->join("{$pre}members m ON i.investor_uid=m.id")
				->field($field)
				->where($map)
				->limit($pagesize)
				->order("d.status asc")
				->select();
			$list = self::homeList($list);
		}

		return $list;
	}

	public static function homeList($list)
	{
		foreach($list as $key=>$val){
			$list[$key]['surplus'] = TborrowModel::get_remain_transfer_days($val['id'], 1);    //剩余期限
			$list[$key]['amount_money'] = $val['money'];    //转让金额
			if(floatval($val['money']) != floatval($val['assigned'])){
				$list[$key]['money'] = bcsub($val['money'],$val['assigned'],2);    //剩余可投金额
				$debt = new DebtBehavior();
				$list[$key]['interest_rate'] = $debt->getInterestRate($val['invest_id']);
			}else{
				$list[$key]['money'] = bcsub($val['money'],$val['assigned'],2);    //剩余可投金额
			}
			$list[$key]['progress'] = $val['assigned']/$list[$key]['amount_money']*100;  //债权转让进度
		}
		return $list ;
	}

	public static function cancelList($pagesize=15, $uid)
	{
		$pre =  C('DB_PREFIX');
		$count = M('invest_detb')->where("sell_uid = ".$uid."  and status = 3")->count();
		$Page = new Page($count, $pagesize);
		$Bonds['data'] = M('debt d')
			->join("{$pre}borrow_investor i ON d.invest_id = i.id")
			->join("{$pre}borrow_info b ON i.borrow_id = b.id")
			->field("d.invest_id, d.remark, i.borrow_id, d.money, d.transfer_price, d.cancel_time,
                            d.cancel_times, d.period, d.total_period, b.borrow_name, b.borrow_interest_rate, b.total, b.has_pay")
			->where("d.sell_uid = ".$uid."  and d.status = 3 ")
			->limit($Page->firstRow. ',' . $Page->listRows)
			->order('d.id')
			->select();

		$Bonds['page'] = $Page->show();
		return $Bonds;
	}

	/**
	 * 成功转让的债权
	 *
	 */
	public static function successDebt($pagesize=15, $uid)
	{
		$pre =  C('DB_PREFIX');
		$count = M('debt')->where("sell_uid = ".$uid."  and status = 4")->count();
		$Page = new Page($count, $pagesize);
		$lists['data'] = M('debt d')
			->join("{$pre}borrow_investor i ON d.invest_id = i.id")
			->join("{$pre}borrow_info b ON i.borrow_id = b.id")
			->field("d.id,d.invest_id, i.borrow_id, d.money,  d.status,d.addtime,
                             d.period, d.total_period, b.borrow_name,b.borrow_type, d.interest_rate, b.total, b.has_pay")
			->where("d.sell_uid = ".$uid."  and d.status =4 ")
			->limit($Page->firstRow. ',' . $Page->listRows)
			->order('d.id')
			->select();
		$lists['page'] = $Page->show();
		return $lists;
	}

	/**
	 * 已购买的债权
	 *
	 */
	public static function buydetb($pagesize=15, $uid)
	{
		$pre =  C('DB_PREFIX');
		$where = "investor_uid=".$uid." and parent_invest_id > 0 and d.sell_uid !=".$uid;
		$count = M('borrow_investor i')
			->join("{$pre}debt d ON d.invest_id = i.parent_invest_id")
			->where($where)->count();
		$Page = new Page($count, $pagesize);
		$lists['data'] = M('borrow_investor i')
			->join("{$pre}debt d ON d.invest_id = i.parent_invest_id")
			->join("{$pre}borrow_info b ON i.borrow_id = b.id")
			->join("{$pre}members m ON d.sell_uid=m.id")
			->field("d.id,i.id as invest_id, i.borrow_id, i.investor_capital, i.add_time, i.status, d.serialid, d.discount_gold, d.interest_rate, m.user_name,
                         d.period, d.total_period, b.borrow_name,b.borrow_type, d.interest_rate, b.total, b.has_pay")
			->where("i.investor_uid=".$uid." and d.status in(2,4) and d.sell_uid != ".$uid)
			->limit($Page->firstRow. ',' . $Page->listRows)
			->order('d.status')
			->select();
		if( !empty($lists['data']) ) {
			for( $i=0;$count=count($lists['data']),$i<$count; $i++ ) {
				$lists['data'][$i]['buy_money'] = $lists['data'][$i]['investor_capital'] * (1-$lists['data'][$i]['discount_gold']/100);
			}
		}
		$lists['page'] = $Page->show();
		return $lists;
	}

}
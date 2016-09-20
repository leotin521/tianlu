<?php
class CapitalrankAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
   public function index()
    {	
	
		$map = 'type=6';
		$last = time();
		$status = intval($_REQUEST['status']);
		$startdate = strtotime($_REQUEST['startdate']);
		$enddate = strtotime($_REQUEST['enddate']);
		$user_name = htmlspecialchars($_REQUEST['user_name'], ENT_QUOTES);
		$search = array();
		$search['status'] =  $status;
		if($status == 'w'){
			$sta_time = strtotime("-1 week");
			$map .= ' and add_time between ' . $sta_time . ' and ' . $last;
		}
		if($status == 'm'){
			$sta_time = strtotime("-1 month");
			$map .= ' and add_time between ' . $sta_time . ' and ' . $last;	
		}
		if($status == 'y'){
			$sta_time = strtotime("-1 year");
			$map .= ' and add_time between ' . $sta_time . ' and ' . $last;
		}
		if($startdate){
			$map .= ' and b.add_time   > ' . $startdate;
			$search['startdate'] =  date("Y-m-d",$startdate);
		}
		if($enddate){
			$map .= ' and b.add_time   < ' . $enddate;
			$search['enddate'] =  date("Y-m-d",$enddate);
		}
		
		if($user_name){
			$map .= ' and m.user_name   = "' . $user_name . '"';
			$search['user_name'] =  $user_name;
		}
		import("ORG.Util.Page");
		$count = M("member_moneylog b")->join("{$this->pre}members m ON m.id=b.uid")->where($map)->count('DISTINCT b.uid');
		
		$p = new Page($count, C('ADMIN_PAGE_SIZE'));
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		
		$list = M("member_moneylog b")->join("{$this->pre}members m ON m.id=b.uid")->field('m.user_name,b.add_time,abs(sum(b.affect_money)) as money')->order('money DESC')->where($map)->limit($Lsql)->group('b.uid')->select();

		$this->assign("list", $list);
		 $this->assign("pagebar", $page);
		$this->assign("search", $search);
		$search['export'] = 1;
		$this->assign("query", http_build_query($search));
		if (intval($_GET['export']) == 1) {
		    import("ORG.Io.Excel");
		    alogs("index", 0, 1, '执行了会员投资排行记录列表导出操作！'); //管理员操作日志
		    $row = array();
		    $row[0] = array('用户名', '投资金额');
		    $i = 1;
		    foreach ($list as $v) {
		        if (empty($v['money'])) $v['money']=0;
		        $row[$i]['user_name'] = $v['user_name'];
		        $row[$i]['money'] = $v['money'];
		        $i++;
		    }
		    $xls = new Excel_XML('UTF-8', false, 'datalist');
		    $xls->addArray($row);
		    $xls->generateXML("index");
		}else{
		    $this->display();
		}
    }

}
?>
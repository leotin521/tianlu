<?php
// 本类由系统自动生成，仅供测试用途
class TendoutAction extends MCommonAction {

    public function index(){
        $designer = FS("Webconfig/designer");
        $version = FS("Webconfig/version");
        #-----------默认条件--------------------------
        $map['i.investor_uid'] = $this->uid;
        $map['b.borrow_status'] = array('in','6,8');   //默认还款中
        #-----------保障机构----------------------------------
        $danbao = M('article') -> field('id,title') -> where('type_id=7') -> select();
        $this -> assign("danbao_list", $danbao);
        #*-----------搜索条件-----------------
        $curl = $_SERVER['REQUEST_URI'];
        $urlarr = parse_url($curl);
        parse_str($urlarr['query'],$surl); //array获取当前链接参数
        $urlArr = array('production','t','k','guarantors','as','ae','es','ee');
        foreach($urlArr as $v){
            $newpars = $surl;  //用新变量避免后面的连接受影响
            unset($newpars[$v],$newpars['type_list'],$newpars['order_sort'],$newpars['orderby']);   //去掉公共参数，对掉当前参数
            foreach($newpars as $skey=>$sv){
                if($sv=="0") unset($newpars[$skey]); //去掉"全部"状态的参数,避免地址栏全满
            }
            $newurl = http_build_query($newpars);  //生成此值的链接,生成必须是即时生成
            $searchUrl[$v]['url'] = $newurl;
            $searchUrl[$v]['cur'] = text($_GET[$v]);
        }
        if (empty($searchUrl['k']['cur'])){
            $searchUrl['k']['cur'] = 4; //保证进入页面，还款状态为复审中
        }
        //print_r($searchUrl['k']['cur']);
        foreach($urlArr as $v){
            if($_GET[$v]){
                switch($v){
                    case 'production':  //产品类型
                        $borrow_type = text($_GET[$v]);
                        $map["i.borrow_type"] = $borrow_type;
                        break;
                    case 'guarantors':  //保障机构
                        $guarantors = text($_GET[$v]);
                        $map['b.danbao'] = $guarantors;
                        break;
                    case 'as':  //投资时间
                        $as = strtotime($_GET[$v]);
                    case 'ae':
                        $ae = strtotime($_GET[$v]);
                        $map["i.add_time"] = array('between',array($as,$ae));
                        break;
                    case 'es':  //到期时间
                        $es = strtotime($_GET[$v]);
                    case 'ee':
                        $ee = strtotime($_GET[$v]);
                        $map["i.deadline"] = array('between',array($es,$ee));
                        break;
                    case 't':  //保障机构   //parent_invest_id，有值，认购债权。0，直投
                        $t = text($_GET[$v]);
                        if ($t=='1'){
                            $map['i.parent_invest_id'] = 0;
                        }elseif ($t=='2'){
                            $map['i.parent_invest_id'] = array('neq',0);
                        }
                        break;
                    case 'k':   //还款状态
                        $k = text($_GET[$v]);
                        if ($k=='4') {
                            $map['b.borrow_status'] = array('in','6,8');  //还款中
                        }elseif ($k=='1'){
                            $map['b.borrow_status'] = array('in','7,9,10');   //已完成
                        }elseif ($k=='14'){
                            $map['d.status'] = 4;   //已转让
                        }elseif ($k=='2'){
                            $map['b.borrow_status'] = array('in','0,2,4');   //竞标中
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        $list = getTTenderList($map, 10);
        $this->assign("list", $list['list']);
        $this->assign("pagebar", $list['page']);
        
        array_unshift($designer, "全部");
        if ($version['single']==0) unset($designer[1], $designer[2], $designer[3], $designer[4], $designer[5]);
        if ($version['business']==0) unset($designer[6]);
        if ($version['fund']==0) unset($designer[7]);

        $searchCnf['production'] = $designer;
        $searchCnf['t'] = array("全部","直接投资","认购债权");
        $searchCnf['k'] = array('2'=>'竞标中','4'=>"还款中",'1'=>"已完成",'14'=>"已转让");
        
        $this->assign("searchCnf",$searchCnf);
        $this->assign("searchUrl",$searchUrl);
        $this->assign("designer",$designer);
        
		$this->display();
    }
	public function tindex(){
		$this->display();
    }
	 public function trade(){
	     
	    $search['uid'] = $this->uid;
	    #*-----------搜索条件-----------------
	    $curl = $_SERVER['REQUEST_URI'];
	    $urlarr = parse_url($curl);
	    parse_str($urlarr['query'],$surl); //array获取当前链接参数
	    $urlArr = array('time','type','from_time','to_time');
	    if (isset($_GET['time'])&&($_GET['time'] != 'null')){
	        unset($urlArr[2]);
	        unset($urlArr[3]);
	        unset($surl['from_time']);
	        unset($surl['to_time']);
	    } 
	    foreach($urlArr as $v){
	        $newpars = $surl;  //用新变量避免后面的连接受影响
	        unset($newpars[$v],$newpars['type_list'],$newpars['order_sort'],$newpars['orderby']);   //去掉公共参数，对掉当前参数
	        foreach($newpars as $skey=>$sv){
	            if($sv=="null") unset($newpars[$skey]); //去掉"全部"状态的参数,避免地址栏全满
	        }

	        $newurl = http_build_query($newpars);  //生成此值的链接,生成必须是即时生成
	        $searchUrl[$v]['url'] = $newurl;
	        $searchUrl[$v]['cur'] = empty($_GET[$v])?"null":text($_GET[$v]);
	    }
	    #*----------------搜索条件显示----------------------------
	    $searchMap['time'] = array("null"=>"全部","2"=>"最近七天","3"=>"1个月","4"=>"3个月");
	    $searchMap['type'] = array("null"=>"全部","2"=>"充值","3"=>"提现","4"=>"投资","5"=>"收益","6"=>"回收本金","7"=>"其它");
	    #*------------------------------------------------
	    
	    foreach($urlArr as $v){
	        if($_GET[$v] && $_GET[$v]<>'null'){
	            switch($v){
	                case 'time':
	                    $time = text($_GET[$v]);
	                    if ($time == '2') {
	                        $time = time()-604800;
	                    }elseif ($time == '3'){
	                        $time = time()-2592000;
	                    }elseif ($time == '4'){
	                        $time = time()-7776000;
	                    }
	                    $search["add_time"] = array("gt",$time);
	                    break;
	                case 'type':
	                    $type = text($_GET[$v]);
	                    $types = array("2" => "3,27", "3" => "12,29", "4" => "6,37", "5" => "13,20,32,34,40,41,43,45,51,52", "6" => "9,10", "7"=>"2,4,5,7,8,11,14,15,16,17,18,19,21,22,23,24,25,26,28,30,31,33,35,36,38,39,42,44,46,47,48,49,50,");
	                    if(array_key_exists($type, $types)){
	                        $search['type']  = array('in',$types[$type]);
	                    }
	                    break;
	                case 'from_time':
	                    $from_time = strtotime($_GET[$v]);
	                case 'to_time':
	                    $to_time = strtotime($_GET[$v]);
	                    $search["add_time"] = array('between',array($from_time,$to_time));
	                    break;
	                default:
	                    break;
	            }
	        }
	    }
	    
	    $list = getMoneyLog($search,15);
	    $this->assign("list",$list['list']);
	    $this->assign("pagebar",$list['page']);
	    $this->assign("searchUrl",$searchUrl);
	    $this->assign("searchMap",$searchMap);
		$this->display();
    }
    public function summary(){
		$uid = $this->uid;
		$pre = C('DB_PREFIX');
		$this->assign("dc",M('investor_detail')->where("investor_uid = {$this->uid}")->sum('substitute_money'));
		$this->assign("mx",getMemberBorrowScan($this->uid));
		$data['html'] = $this->fetch();
		exit(json_encode($data));
    }
	
	public function tending(){
		$map['investor_uid'] = $this->uid;
		$map['status'] = 1;
		$list = getTenderList($map,15);
		$this->assign("list",$list['list']);
		$this->assign("pagebar",$list['page']);
		$this->assign("total",$list['total_money']);
		$this->assign("num",$list['total_num']);
		$data['html'] = $this->fetch();
		exit(json_encode($data));
	}

	public function tendbacking(){
		$map['investor_uid'] = $this->uid;
		$map['status'] = 4;
		$list = getTenderList($map,15);
		$this->assign("list",$list['list']);
		$this->assign("pagebar",$list['page']);
		$this->assign("total",$list['total_money']);
		$this->assign("num",$list['total_num']);
        $this->assign('uid', $this->uid);
		$data['html'] = $this->fetch();
		exit(json_encode($data));
	}

    public function getTendBacking()
    {
        import("ORG.Util.Page"); 
       $map = "(investor_uid={$this->uid} or debt_uid={$this->uid}) and status=4"; 
       $count = M("borrow_investor")->where($map)->count("id");
       $Page = new Page($count, 14);
       $list['list'] = M("borrow_investor i")
            ->join(C('DB_PREFIX')."borrow_info b ON i.borrow_id=b.id")
            ->join(C('DB_PREFIX')."members m ON i.investor_uid=m.id")
            ->join(C('DB_PREFIX')."invest_detb d ON i.id=d.invest_id")
            ->field("i.borrow_id, b.borrow_name, m.user_name as borrow_user, 
                     i.investor_capital, b.borrow_interest_rate, i.receive_interest, i.receive_capital,
                     b.total, b.has_pay, i.id, d.period, d.status, i.debt_uid")
            ->where("(i.investor_uid={$this->uid} or i.debt_uid={$this->uid}) and i.status=4")
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
       $list['page']=$Page->show();
       return $list;
    }

	public function tenddone(){
		$map['investor_uid'] = $this->uid;
		$map['status'] = array("in","5,6");
		$list = getTenderList($map,15);
		$this->assign("list",$list['list']);
		$this->assign("pagebar",$list['page']);
		$this->assign("total",$list['total_money']);
		$this->assign("num",$list['total_num']);
		$data['html'] = $this->fetch();
		exit(json_encode($data));
	}

	public function tendbreak(){
		$map['d.status'] = array('neq',0);
		$map['d.repayment_time'] = array('eq',"0");
		$map['d.deadline'] = array('lt',time());
		$map['d.investor_uid'] = $this->uid;
		$list = getMBreakInvestList($map,15);
		$this->assign("list",$list['list']);
		$this->assign("pagebar",$list['page']);
		$this->assign("total",$list['total_money']);
		$this->assign("num",$list['total_num']);
		$data['html'] = $this->fetch();
		exit(json_encode($data));
	}

    public function tendoutdetail(){
		$pre = C('DB_PREFIX');
		$status_arr =array('还未还','已还完','已提前还款','迟还','网站代还本金','逾期还款','','等待还款');
		$investor_id = intval($_GET['id']);
		$vo = M("borrow_investor i")->field("b.borrow_name")->join("{$pre}borrow_info b ON b.id=i.borrow_id")->where("i.investor_uid={$this->uid} AND i.id={$investor_id}")->find();
		if(!is_array($vo)) $this->error("数据有误");
		$map['invest_id'] = $investor_id;
		$list = M('investor_detail')->field(true)->where($map)->select();
		$this->assign("status_arr",$status_arr);
		$this->assign("list",$list);
		$this->assign("name",$vo['borrow_name'].$investor_id);
		$this->display();
    }
    //未完成
    public function ajax_detail(){
        $investid = intval($_GET['tender_id']);
        $this->assign('investid', $investid);
        $data['content'] = $this->fetch();
        echo $data['content'];
    }
    //未完成详情
    public function tenddetail()
    {
        $map['d.investor_uid'] = $this->uid;
        #$map['d.status'] = 7;   //未还款
        $map['d.invest_id'] = intval($_GET['id']);
        $list = getTDTenderList($map,10);
        if (empty($list['have_pay'])){
            $list['have_pay'] = '0.00';
        }
        if (empty($list['fail_pay'])){
            $list['fail_pay'] = '0.00';
        }
        $this->assign("have_pay", $list['have_pay']); 
        $this->assign("fail_pay", $list['fail_pay']);
        $this->assign("list", $list['list']);
        $this->assign("pagebar", $list['page']);
        $this->assign("total", $list['total_money']);
        $this->assign("num", $list['total_num']);
        $this->display();
    }

}
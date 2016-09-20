<?php
// 本类由系统自动生成，仅供测试用途
class InvestAction extends HCommonAction {
	/**
    * 普通标列表 
    */
    public function index()
    {
        static $newpars;
        $Bconfig = require C("APP_ROOT")."Conf/borrow_config.php";
        $per = C('DB_PREFIX');

        $vo1 = M('members')->field('id,user_name,user_email,user_pass,is_ban')->where("id={$this->uid}")->find();
        if($vo1['is_ban']==1||$vo1['is_ban']==2) $this->error("您的账户已被冻结，请联系客服处理！",__APP__."/index.html");


        $curl = $_SERVER['REQUEST_URI'];
        $urlarr = parse_url($curl);
        parse_str($urlarr['query'],$surl);//array获取当前链接参数，2.
        $urlArr = array('borrow_status','borrow_duration','leve');
        $leveconfig = FS("Webconfig/leveconfig");
        foreach($urlArr as $v){
            $newpars = $surl;//用新变量避免后面的连接受影响
            unset($newpars[$v],$newpars['type'],$newpars['order_sort'],$newpars['orderby']);//去掉公共参数，对掉当前参数
            foreach($newpars as $skey=>$sv){
                if($sv=="all") unset($newpars[$skey]);//去掉"全部"状态的参数,避免地址栏全满
            }

            $newurl = http_build_query($newpars);//生成此值的链接,生成必须是即时生成
            $searchUrl[$v]['url'] = $newurl;
            $searchUrl[$v]['cur'] = empty($_GET[$v])?"all":text($_GET[$v]);
        }
        $searchMap['borrow_status'] = array("all"=>"不限制","-1"=>"预告中","2"=>"进行中","4"=>"复审中","6"=>"还款中","7"=>"已完成");
        $searchMap['borrow_duration'] = array("all"=>"不限制","0-31"=>"天标","1-3"=>"3个月以内","3-6"=>"3-6个月","6-12"=>"6-12个月","12-24"=>"12-24个月");
        $searchMap['leve'] = array("all"=>"不限制","{$leveconfig['1']['start']}-{$leveconfig['1']['end']}"=>"{$leveconfig['1']['name']}","{$leveconfig['2']['start']}-{$leveconfig['2']['end']}"=>"{$leveconfig['2']['name']}","{$leveconfig['3']['start']}-{$leveconfig['3']['end']}"=>"{$leveconfig['3']['name']}","{$leveconfig['4']['start']}-{$leveconfig['4']['end']}"=>"{$leveconfig['4']['name']}","{$leveconfig['5']['start']}-{$leveconfig['5']['end']}"=>"{$leveconfig['5']['name']}","{$leveconfig['6']['start']}-{$leveconfig['6']['end']}"=>"{$leveconfig['6']['name']}","{$leveconfig['7']['start']}-{$leveconfig['7']['end']}"=>"{$leveconfig['7']['name']}");

        $search = array();
        //搜索条件
        foreach($urlArr as $v){
            if($_GET[$v] && $_GET[$v]<>'all'){
                switch($v){
                    case 'leve':
                        $barr = explode("-",text($_GET[$v]));
                        $search["m.credits"] = array("between",$barr);
                        break;
                    case 'borrow_status':
                        $search["b.".$v] = intval($_GET[$v]);
                        break;
                    default:
                        $barr = explode("-",text($_GET[$v]));
                        $search["b.".$v] = array("between",$barr);
                        break;
                }
            }
        }

        if($search['b.borrow_status']==0){
            $search['b.borrow_status']=array("in","-1,2,4,6,7");
        }
        $str = "%".urldecode($_REQUEST['searchkeywords'])."%";
        if($_GET['is_keyword']=='1'){
            $search['m.user_name']=array("like",$str);
        }elseif($_GET['is_keyword']=='2'){
            $search['b.borrow_name']=array("like",$str);

        }
        //
        $search['b.repayment_type']=array("neq",1);
        if($search['b.borrow_duration'][1][0]==0){
            $search['b.repayment_type']=array("eq",1);
        }
        if(!isset($search['b.borrow_duration'])){
            $search['b.repayment_type']=array("neq",10);
        }
        //
        $search['b.borrow_type']=array("lt","6");
        $parm['map'] = $search;
        $parm['pagesize'] = 10;
        //排序
        (strtolower($_GET['sort'])=="asc")?$sort="desc":$sort="asc";
        unset($surl['orderby'],$surl['sort']);
        $orderUrl = http_build_query($surl);
        if($_GET['orderby']){
            //if(strtolower($_GET['orderby'])=="leve") $parm['orderby'] = "m.credits ".text($_GET['sort']);
            if(strtolower($_GET['orderby'])=="rate") $parm['orderby'] = "b.borrow_interest_rate ".text($_GET['sort']);
            elseif(strtolower($_GET['orderby'])=="borrow_money") $parm['orderby'] = "b.borrow_money ".text($_GET['sort']);
            else $parm['orderby']="b.id DESC";
        }else{
            $parm['orderby']="b.borrow_status=7,b.borrow_status=6,b.borrow_status=4,b.borrow_status=-1,b.borrow_status=2,b.id DESC";
        }


        $Sorder['Corderby'] = strtolower(text($_GET['orderby']));
        $Sorder['Csort'] = strtolower(text($_GET['sort']));
        $Sorder['url'] = $orderUrl;
        $Sorder['sort'] = $sort;
        $Sorder['orderby'] = text($_GET['orderby']);
        //排序
        $list = getBorrowList($parm);
        //dump(M()->GetLastsql());exit;
        // 显示导航
        $navigate = get_navigate();
        $this->assign("navigate", $navigate);
        $this->assign("Sorder",$Sorder);
        $this->assign("searchUrl",$searchUrl);
        $this->assign("searchMap",$searchMap);
        $this->assign("Bconfig",$Bconfig);
        $this->assign("Buse",$this->gloconf['BORROW_USE']);
        $this->assign("list",$list);
        $this->display();
    }
    
	/////////////////////////////////////////////////////////////////////////////////////
	
    public function detail(){
		if($_GET['type']=='commentlist'){
			//评论
			$cmap['tid'] = intval($_GET['id']);
			$clist = getCommentList($cmap,5);
			$this->assign("commentlist",$clist['list']);
			$this->assign("commentpagebar",$clist['page']);
			$this->assign("commentcount",$clist['count']);
			$data['html'] = $this->fetch('commentlist');
			exit(json_encode($data));
		}
		$pre = C('DB_PREFIX');
		$id = intval($_GET['id']);
		$Bconfig = require C("APP_ROOT")."Conf/borrow_config.php";
		
		//合同ID
		if($this->uid){
			$invs = M('borrow_investor')->field('id')
                ->where("borrow_id={$id} AND (investor_uid={$this->uid} OR borrow_uid={$this->uid}) AND borrow_type<".BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID )->find();
			if($invs['id']>0) $invsx=$invs['id'];
			elseif(!is_array($invs)) $invsx='no';
		}else{
			$invsx='login';
		}
		$this->assign("invid",$invsx);
		//合同ID

		$this->assign("borrow_investor_num",M('borrow_investor')->where("borrow_id={$id}")->count("id"));//投资记录个数

		$borrowinfo = M("borrow_info bi")->field('bi.*,ac.title,ac.id as aid')->join('lzh_article ac on ac.id= bi.danbao')->where('bi.id='.$id.' and borrow_type<6')->find();
		if(!is_array($borrowinfo) || ($borrowinfo['borrow_status']==0 && $this->uid!=$borrowinfo['borrow_uid']) ) $this->error("数据有误");
		$borrowinfo['biao'] = $borrowinfo['borrow_times'];
		$borrowinfo['need'] = $borrowinfo['borrow_money'] - $borrowinfo['has_borrow'];
        //上线时间或剩余募集期时间
        if( $borrowinfo['borrow_status'] == BorrowModel::BID_SINGLE_CONFIG_STATUS_WAIT_ONLINE ) {
            $borrowinfo['lefttime'] =strtotime($borrowinfo['online_time']) - time();
            $borrowinfo['add_time'] = strtotime($borrowinfo['online_time']);//开始时间为上线时间
        }else{
            $borrowinfo['lefttime'] =$borrowinfo['collect_time'] - time();
        }
		$borrowinfo['progress'] = BorrowModel::get_progress_decimal(getFloatValue($borrowinfo['has_borrow']/$borrowinfo['borrow_money']*100,2));

        $borrowinfo['repayment_name'] = BorrowModel::get_repay_type($borrowinfo['repayment_type']);
        $borrowinfo['info_count'] = mb_strlen($borrowinfo['borrow_info'],'UTF8');
		
		$this->assign("vo",$borrowinfo);

		$memberinfo = M("members m")
    		->field("m.id,m.customer_name,m.customer_id,m.user_name,m.reg_time,m.credits,fi.*,mi.*,mm.*")
    		->join("{$pre}member_financial_info fi ON fi.uid = m.id")
    		->join("{$pre}member_info mi ON mi.uid = m.id")
    		->join("{$pre}member_money mm ON mm.uid = m.id")
    		->where("m.id={$borrowinfo['borrow_uid']}")
		    ->find();
		$areaList = getArea();
		$memberinfo['location'] = $areaList[$memberinfo['province']].$areaList[$memberinfo['city']];
		$memberinfo['location_now'] = $areaList[$memberinfo['province_now']].$areaList[$memberinfo['city_now']];
		$memberinfo['zcze']=$memberinfo['account_money']+$memberinfo['back_money']+$memberinfo['money_collect']+$memberinfo['money_freeze'];
		$this->assign("minfo",$memberinfo);

		//data_list
		$data_list = M("member_data_info")->field('type,add_time,count(status) as num,sum(deal_credits) as credits')->where("uid={$borrowinfo['borrow_uid']} AND status=1")->group('type')->select();
		$this->assign("data_list",$data_list);
		//data_list
		
        // 投资记录
        $this->investRecord($id);
        $this->assign('borrow_id', $id);
        $version = FS("Webconfig/version");
        if($version['mobile'] == 1 OR $version['wechat'] == 1) {
            $is_mobile = 1;
            $this->assign('is_mobile',$is_mobile);
        }
		//近期还款的投标
		//$time1 = microtime(true)*1000;
		$history = getDurationCount($borrowinfo['borrow_uid']);
		$this->assign("history",$history);

		//investinfo
		$fieldx = "bi.investor_capital,bi.add_time,m.user_name,bi.is_auto";
		$investinfo = M("borrow_investor bi")->field($fieldx)->join("{$pre}members m ON bi.investor_uid = m.id")->limit(10)->where("bi.borrow_id={$id}")->order("bi.id DESC")->select();
		$this->assign("investinfo",$investinfo);
		//investinfo
		
		//帐户资金情况
		$this->assign("investInfo", getMinfo($this->uid,true));
		$this->assign("mainfo", getMinfo($borrowinfo['borrow_uid'],true));
		$this->assign("capitalinfo", getMemberBorrowScan($borrowinfo['borrow_uid']));
		//帐户资金情况
		//展示资料
		
		//上传资料类型
		$upload_type = FilterUploadType(FS("Webconfig/integration"));
		$this->assign("upload_type", $upload_type); // 上传资料所有类型

        // 投标有奖显示到页面
        $special_award = ExpandMoneyModel::get_special_award($id);
		$this->assign('unlogin_home', DOMAIN . '/login?redirectUrl=' . rawurlencode(DOMAIN . $_SERVER['REQUEST_URI']));
        $this->assign('special_award', $special_award);
        $this->assign("Bconfig",$Bconfig);
        $this->assign("gloconf",$this->gloconf);
		$this->display();
    }
	
    public function fram(){
        if(intval($_GET['id'])){
            $id = intval($_GET['id']);
            $info = M("borrow_info")->field('borrow_info')->where('id='.$id)->find();
            if (is_array($info)){
                $vo = $info['borrow_info'];
            }
            else $vo = '暂无详情';;
        }else{
            $vo = '暂无详情';
        }
        $this->assign('vo',$vo);
        $this->display();
    }
    
	public function investcheck(){
		$pre = C('DB_PREFIX');
		if(!$this->uid) {
			ajaxmsg('',3);
			exit;
		}
		$pin = md5($_POST['pin']);
		$borrow_id = intval($_POST['borrow_id']);
		$money = intval($_POST['money']);
		$vm = getMinfo($this->uid,'m.pin_pass,mm.account_money,mm.back_money,mm.money_collect');
		$amoney = $vm['account_money']+$vm['back_money'];
		$uname = session('u_user_name');
		$pin_pass = $vm['pin_pass'];
		$amoney = floatval($amoney);
		
		$binfo = M("borrow_info")->field('borrow_money,has_borrow,has_vouch,borrow_max,borrow_min,borrow_type,password,money_collect')->find($borrow_id);
		if(!empty($binfo['password'])){
			if(empty($_POST['borrow_pass'])) ajaxmsg("此标是定向标，必须验证投标密码",3);
			else if($binfo['password']<>md5($_POST['borrow_pass'])) ajaxmsg("投标密码不正确",3);
		}
		////////////////////////////////////待收金额限制 2013-08-26  fan///////////////////
		if($binfo['money_collect']>0){
			if($vm['money_collect']<$binfo['money_collect']) {
				ajaxmsg("此标设置有投标待收金额限制，您账户里必须有足够的待收才能投此标",3);
			}
		}
		////////////////////////////////////待收金额限制 2013-08-26  fan///////////////////
		//投标总数检测
		$capital = M('borrow_investor')->where("borrow_id={$borrow_id} AND investor_uid={$this->uid}")->sum('investor_capital');
		if(($capital+$money)>$binfo['borrow_max']&&$binfo['borrow_max']>0){
			$xtee = $binfo['borrow_max'] - $capital;
			ajaxmsg("您已投标{$capital}元，此投上限为{$binfo['borrow_max']}元，你最多只能再投{$xtee}",3);
		}
		
		$need = $binfo['borrow_money'] - $binfo['has_borrow'];
		$caninvest = $need - $binfo['borrow_min'];
		$last_money = ($need*100-$money*100)/100;
		if(($binfo['borrow_min']-$money)>0 ){
			$this->error("尊敬的{$uname}，本标最低投标金额为{$binfo['borrow_min']}元，请重新输入投标金额");
		}
		if(($need-$money)<0 ){
			$this->error("尊敬的{$uname}，此标还差{$need}元满标,您最多只能再投{$need}元");
		}
		
		if($pin<>$pin_pass) ajaxmsg("支付密码错误，请重试!",0);
		if($money>$amoney){
			$msg = "尊敬的{$uname}，您准备投标{$money}元，但您的账户可用余额为{$amoney}元，请先充值？";
			ajaxmsg($msg,2);
		}else{
			ajaxmsg();
		}
		ajaxmsg();
	}
		
	public function investmoney(){
		if(!$this->uid) {
			ajaxmsg('请先登录',3);
			exit;
		}
        $money = intval($_POST['money']);
        $coupon_ids = filter_array($_POST['coupon']); // 使用的优惠券id
        $discount_money = 0;
        // 如果使用优惠券
        if( !empty($coupon_ids) ) {
            $coupon_items = M('expand_money')
                ->field('money, invest_money, expired_time, status')
                ->where(array('id'=>array('in',$coupon_ids),'uid'=>$this->uid))
                ->select();
            // 只要有一个优惠券不能使用，则认为是非法请求
            foreach( $coupon_items as $val ) {
                if( $money < $val['invest_money'] || $val['expired_time'] < time() || $val['status'] == 4 ) {//投资的金额必须要大于优惠券最小投资金额限制
                    $this->error('非法请求');
                }else {
                    $discount_money += $val['money'];
                }
            }
        }
        if( $money <= $discount_money ) {
            $this->error("使用优惠券总额不能高于（或等于）投资金额");
        }
        // 实际需要支付的金额 = 投资金额 - 使用优惠券的金额
        $actual_money = $money - $discount_money;
        $borrow_id = intval($_POST['borrow_id']);
		$m = M("member_money")->field('account_money,back_money,money_collect')->find($this->uid);
		$amoney = $m['account_money']+$m['back_money'];
		$uname = session('u_user_name');
		if($amoney<$actual_money) $this->error("尊敬的{$uname}，您准备投标{$money}元，但您的账户可用余额为{$amoney}元，请先去充值再投标.",__APP__."/member/charge#fragment-1");
		//定向标 检测密码
		$binfo = M("borrow_info")->field('borrow_money,has_borrow,has_vouch,borrow_max,borrow_min,borrow_type,password,money_collect,duration_unit')->find($borrow_id);
		if(!empty($binfo['password'])){
			if(empty($_POST['borrow_pass'])) ajaxmsg("此标是定向标，必须验证投标密码",3);
			else if($binfo['password']<>md5($_POST['borrow_pass'])) ajaxmsg("投标密码不正确",3);
		}
		
		$vm = getMinfo($this->uid,'m.pin_pass,mm.account_money,mm.back_money,mm.money_collect');
		$pin_pass = $vm['pin_pass'];
		$pin = md5($_POST['pin']);
		if($pin<>$pin_pass) $this->error("支付密码错误，请重试");

		$binfo = M("borrow_info")->field('borrow_money,borrow_max,has_borrow,has_vouch,borrow_type,borrow_min,money_collect,borrow_status')->find($borrow_id);
		
		////////////////////////////////////待收金额限制 2013-08-26  fan///////////////////
		if($binfo['money_collect']>0){
			if($m['money_collect']<$binfo['money_collect']) {
				ajaxmsg("此标设置有投标待收金额限制，您账户里必须有足够的待收才能投此标",3);
			}
		}
		////////////////////////////////////待收金额限制 2013-08-26  fan///////////////////
		
		//投标总数检测
		$capital = M('borrow_investor')->where("borrow_id={$borrow_id} AND investor_uid={$this->uid}")->sum('investor_capital');
		if(($capital+$money)>$binfo['borrow_max']&&$binfo['borrow_max']>0){
			$xtee = $binfo['borrow_max'] - $capital;
			$this->error("您已投标{$capital}元，此投上限为{$binfo['borrow_max']}元，你最多只能再投{$xtee}");
		}
		//if($binfo['has_vouch']<$binfo['borrow_money'] && $binfo['borrow_type'] == 2) $this->error("此标担保还未完成，您可以担保此标或者等担保完成再投标");
		$need = $binfo['borrow_money'] - $binfo['has_borrow'];
		$caninvest = $need - $binfo['borrow_min'];
		if( $money>$caninvest && $need==0){
			$msg = "尊敬的{$uname}，此标已被抢投满了,下次投标手可一定要快呦！";
			$this->error($msg);
		}
		if(($binfo['borrow_min']-$money)>0 ){
			$this->error("尊敬的{$uname}，本标最低投标金额为{$binfo['borrow_min']}元，请重新输入投标金额");
		}
		$version = FS("Webconfig/version");
		if(($need-$money)<0 ){
			$this->error("尊敬的{$uname}，此标还差{$need}元满标,您最多只能再投{$need}元");
		}else{
			if($binfo['borrow_status']==2){
				if( $version['single'] == 1 && $version['fund'] == 0 && $version['business'] == 0 ) {
					$done = investMoney($this->uid,$borrow_id,$money,0, $coupon_ids);
				}else{
					if( $discount_money > 0 ) {
						$done = investMoney($this->uid,$borrow_id,$money,0, $coupon_ids);
					}else{
						$done = investMoney($this->uid,$borrow_id,$money,0);
					}
				}
			}
		}
	
		if($done===true) {
			if( $discount_money == 0 ) {
				$this->success("恭喜成功投标{$money}元");
			}else{
				$this->success("恭喜成功投标{$money}元,优惠券抵押{$discount_money}元，实际付款{$actual_money}元");
			}

		}else if($done){
			$this->error($done);
		}else{
			$this->error("对不起，投标失败，请重试!");
		}
	}
	/**
	 * 投标奖励（一马当先，一鸣惊人，一锤定音）
	 */
	public function reward(){
	    $borrow_id = intval($_GET['id']);
	    $binfo=M('borrow_info')->where('id='.$borrow_id)->find();
	    $this->assign('binfo', $binfo);
	    $this->assign('borrow_id', $borrow_id);
	     
	    $expconf = FS("Webconfig/expconf");
	    $this->assign('expconf', $expconf);
	    //投标特殊奖励
	    $special_award = ExpandMoneyModel::get_special_award($borrow_id);
	    $this->assign('special_award', $special_award);
	    $pre = C('DB_PREFIX');
	
	    ///一马当先
	    $fields="sum(e.money) as reward_yi,i.investor_capital  as investor_capital,m.user_name,e.add_time";
	    $yima_history =M('expand_money e')
	    ->join("{$pre}members m ON m.id= e.uid")->join("{$pre}borrow_investor i ON i.id=e.invest_id")
	    ->field($fields)->where('e.type=5')->group('e.borrow_id')->order('i.add_time desc')->limit('7')->select();
	    $yichui_left =M('borrow_info')->where('id='.$borrow_id)->find();
	    $this->assign('yichui_left', $yichui_left);
	    $this->assign("yima_history",$yima_history);
	    ///一锤定音
	    $yichui_history =M('expand_money e')
	    ->join("{$pre}members m ON m.id= e.uid")->join("{$pre}borrow_investor i ON i.id=e.invest_id")
	    ->field($fields)->where('e.type=6')->group('e.borrow_id')->order('i.add_time desc')->limit('7')->select();
	    $this->assign("yichui_history",$yichui_history);
	
	    ///一鸣惊人
	    $yiming_history =M('expand_money e')
	    ->join("{$pre}members m ON m.id= e.uid")->join("{$pre}borrow_investor i ON i.id=e.invest_id")
	    ->field($fields)->where('e.type=7')->group('e.borrow_id')->order('i.add_time desc')->limit('7')->select();
	
	    $this->assign("yiming_history",$yiming_history);
	    $this->display();
	}

	public function addcomment(){
	
		$data['comment'] = text($_POST['comment']);
		if(!$this->uid)  ajaxmsg("请先登录",0);
		if(empty($data['comment']))  ajaxmsg("留言内容不能为空",0);
		$data['type'] = 1;
		$data['add_time'] = time();
		$data['uid'] = $this->uid;
		$data['uname'] = session("u_user_name");
		$data['tid'] = intval($_POST['tid']);
		$data['name'] = M('borrow_info')->getFieldById($data['tid'],'borrow_name');

		$newid = M('comment')->add($data);
		//$this->display("Public:_footer");
		if($newid) ajaxmsg();
		else ajaxmsg("留言失败，请重试",0);
	}
	
	public function jubao(){
		if($_POST['checkedvalue']){
			$data['reason'] = text($_POST['checkedvalue']);
			$data['text'] = text($_POST['thecontent']);
			$data['uid'] = $this->uid;
			$data['uemail'] = text($_POST['uemail']);
			$data['b_uid'] = text($_POST['b_uid']);
			$data['b_uname'] = text($_POST['theuser']);
			$data['add_time'] = time();
			$data['add_ip'] = get_client_ip();
			$newid = M('jubao')->add($data);
			if($newid) exit("1");
			else exit("0");
		}else{
			$id=intval($_GET['id']);
			$u['id'] = $id;
			$u['uname']=M('members')->getFieldById($id,"user_name");
			$u['uemail']=M('members')->getFieldById($this->uid,"user_email");
			$this->assign("u",$u);
			$data['content'] = $this->fetch("Public:jubao");
			exit(json_encode($data));
		}
	}
	
	public function ajax_invest(){
		if(!$this->uid) {
			ajaxmsg("请先登录",0);
		}
		$pre = C('DB_PREFIX');
		$id=intval($_GET['id']);
		$investMoney = intval($_GET['num']);
		$field = "id,borrow_uid,borrow_money,borrow_status,borrow_type,has_borrow,has_vouch,borrow_interest_rate,borrow_duration,repayment_type,collect_time,borrow_min,borrow_max,password,borrow_use,money_collect,duration_unit";
		$vo = M('borrow_info')->field($field)->find($id);
		if($this->uid == $vo['borrow_uid']) ajaxmsg("不能去投自己的标",0);
		if($vo['borrow_status'] <> 2) ajaxmsg("只能投正在借款中的标",0);
		//如果只有p2p，体验金让其使用，否则只有体验标才能使用
		$version = FS("Webconfig/version");
		if( $version['single'] == 1 && $version['fund'] == 0 && $version['business'] == 0 ) {
			$expand_where = " uid=".$this->uid." and status=1 and expired_time > ".time();
		}else{
			$expand_where = " uid=".$this->uid." and is_taste=0  and status=1 and expired_time > ".time();
		}
        $expand_list = M('expand_money') //取三个按最大金额的，另取三个按过期时间的，前台通过TAB切换
            ->field('id,money, invest_money, expired_time, type,is_taste')
            ->where($expand_where)
            ->order("money desc")
            ->select();
        foreach ($expand_list as $key=>$val){
            if ($val['invest_money']<=$investMoney){
                $arr[] = $val;
            }elseif ($val['invest_money']>$investMoney){
                $res[] = $val;
            }
        }
        if (empty($arr)):
        $list_merge = $res;
        elseif (empty($res)):
        $list_merge = $arr;
        else:
        $list_merge = array_merge($arr, $res);
        endif;
        $list = array_slice($list_merge, 0, 3);
        $list = ExpandMoneyModel::get_coupon_type_format($list);
        $this->assign('expand_list', $list);
        
        $expand_expired_list = M('expand_money') //取三个按过期时间的，另取三个按最大金额的，前台通过TAB切换
        ->field('id,money, invest_money, expired_time, type,is_taste')
            ->where($expand_where)
            ->order("expired_time asc")
            ->select();
        foreach ($expand_expired_list as $key=>$val){
            if ($val['invest_money']<=$investMoney){
                $arr_list[] = $val;
            }elseif ($val['invest_money']>$investMoney){
                $res_list[] = $val;
            }
        }
        if (empty($arr_list)):
        $list_list_merge = $res_list;
        elseif (empty($res_list)):
        $list_list_merge = $arr_list;
        else:
        $list_list_merge = array_merge($arr_list, $res_list);
        endif;
        $list_list = array_slice($list_list_merge, 0, 3);

        $list_list = ExpandMoneyModel::get_coupon_type_format($list_list);
        $this->assign('expand_expired_list', $list_list);
        $vm = getMinfo($this->uid,'m.pin_pass,mm.account_money,mm.back_money,mm.money_collect');
		////////////////////////////////////待收金额限制 2013-08-26  fan///////////////////
		if($vo['money_collect']>0){
			if($vm['money_collect']<$vo['money_collect']) {
				ajaxmsg("此标设置有投标待收金额限制，您账户里必须有足够的待收才能投此标",3);
			}
		}
		////////////////////////////////////待收金额限制 2013-08-26  fan///////////////////

		$pin_pass = $vm['pin_pass'];
		$has_pin = (empty($pin_pass))?"no":"yes";

        // 到期总回款
        $borrowInterest = round(getBorrowInterest($vo['repayment_type'], $investMoney, $vo['borrow_duration'], $vo['borrow_interest_rate'], $vo['duration_unit']), 2);
        $this->assign('jingli', $borrowInterest);
        $this->assign('receive_account', getFloatValue($investMoney+$borrowInterest, 2));
		$this->assign("has_pin",$has_pin);
		$this->assign("investMoney",$investMoney);
		$this->assign("voo",$vo);
        $this->assign("vm", $vm);
        
        $need_money = $vm['account_money'] + $vm['back_money'] - $investMoney;
        if( $need_money > 0 ) {
            $need_money = 0;
        } else {
            $need_money = abs($need_money);
        }
        $this->assign('need_money', $need_money);
		$data['content'] = $this->fetch();
		ajaxmsg($data);
	}
	
	public function getarea(){
		$rid = intval($_GET['rid']);
		if(empty($rid)){
			$data['NoCity'] = 1;
			exit(json_encode($data));
		}
		$map['reid'] = $rid;
		$alist = M('area')->field('id,name')->order('sort_order DESC')->where($map)->select();

		if(count($alist)===0){
			$str="<option value=''>--该地区下无下级地区--</option>\r\n";
		}else{
			if($rid==1) $str.="<option value='0'>请选择省份</option>\r\n";
			foreach($alist as $v){
				$str.="<option value='{$v['id']}'>{$v['name']}</option>\r\n";
			}
		}
		$data['option'] = $str;
		$res = json_encode($data);
		echo $res;
	}	
	
	public function addfriend(){
		if(!$this->uid) ajaxmsg("请先登录",0);
		$fuid = intval($_POST['fuid']);
		$type = intval($_POST['type']);
		if(!$fuid||!$type) ajaxmsg("提交的数据有误",0);
		
		$save['uid'] = $this->uid;
		$save['friend_id'] = $fuid;
		$vo = M('member_friend')->where($save)->find();	
		
		if($type==1){//加好友
		if($this->uid == $fuid) ajaxmsg("您不能对自己进行好友相关的操作",0);
			if(is_array($vo)){
				if($vo['apply_status']==3){
					$msg="已经从黑名单移至好友列表";
					$newid = M('member_friend')->where($save)->setField("apply_status",1);
				}elseif($vo['apply_status']==1){
					$msg="已经在你的好友名单里，不用再次添加";
				}elseif($vo['apply_status']==0){
					$msg="已经提交加好友申请，不用再次添加";
				}elseif($vo['apply_status']==2){
					$msg="好友申请提交成功";
					$newid = M('member_friend')->where($save)->setField("apply_status",0);
				}
			}else{
				$save['uid'] = $this->uid;
				$save['friend_id'] = $fuid;
				$save['apply_status'] = 0;
				$save['add_time'] = time();
				$newid = M('member_friend')->add($save);	
				$msg="好友申请成功";
			}
		}elseif($type==2){//加黑名单
		if($this->uid == $fuid) ajaxmsg("您不能对自己进行黑名单相关的操作",0);
			if(is_array($vo)){
				if($vo['apply_status']==3) $msg="已经在黑名单里了，不用再次添加";
				else{
					$msg="成功移至黑名单";
					$newid = M('member_friend')->where($save)->setField("apply_status",3);	
				}
			}else{
				$save['uid'] = $this->uid;
				$save['friend_id'] = $fuid;
				$save['apply_status'] = 3;
				$save['add_time'] = time();
				$newid = M('member_friend')->add($save);	
				$msg="成功加入黑名单";
			}
		}
		if($newid) ajaxmsg($msg);
		else ajaxmsg($msg,0);
	}
	
	
	public function innermsg(){
		if(!$this->uid) ajaxmsg("请先登录",0);
		$fuid = intval($_GET['uid']);
		if($this->uid == $fuid) ajaxmsg("您不能对自己进行发送站内信的操作",0);
		$this->assign("touid",$fuid);
		$data['content'] = $this->fetch("Public:innermsg");
		ajaxmsg($data);
	}
	public function doinnermsg(){
		$touid = intval($_POST['to']);
		$msg = text($_POST['msg']);	
		$title = text($_POST['title']);	
		$newid = addMsg($this->uid,$touid,$title,$msg);
		if($newid) ajaxmsg();
		else ajaxmsg("发送失败",0);
		
	}
     /**
    * ajax 获取投资记录
    * 
    */
    public function investRecord($borrow_id=0)
    {
        
        isset($_GET['borrow_id']) && $borrow_id = intval($_GET['borrow_id']);
        $Page = D('Page');       
        import("ORG.Util.Page");       
        $count = M("borrow_investor")->where('borrow_id='.$borrow_id." and debt_time=0")->count('id');
        $Page     = new Page($count,6);
        
        $version = FS("Webconfig/version");
        if($version['mobile'] == 1 OR $version['wechat'] == 1) {
            $is_mobile = 1;
        }
        $show = $Page->ajax_show();
        $this->assign('page', $show);
        if($_GET['borrow_id']){
            $list = M("borrow_investor as b")
                        ->join(C("DB_PREFIX")."members as m on  b.investor_uid = m.id")
                        ->join(C("DB_PREFIX")."borrow_info as i on  b.borrow_id = i.id")
                        ->field('i.borrow_interest_rate, i.repayment_type, b.investor_capital, b.add_time, b.is_auto, b.source, m.user_name, i.borrow_duration,i.duration_unit')
                        ->where('b.borrow_id='.$borrow_id." and debt_time=0")
                        ->order('b.id desc')
                        ->limit($Page->firstRow.','.$Page->listRows)
                        ->select();
            $string = '';
           foreach($list as $k=>$v){
			    if(!empty($v)){
			        $source = BorrowInvestorModel::get_invest_source($v['source']);
                    $is_auto = $v['is_auto']?'自动':'手动';
                    if( $k%2 ) {
                        $string .= "<tr class='ad_con_text_bg'>";
                    } else {
                        $string .= "<tr>";
                    }
                    $string .= "<td>".hidecard($v['user_name'],5)."</td>";
                    $string .="<td>". $v['borrow_interest_rate'] ."%</td>";
                    $string .="<td>" .date('Y-m-d H:i:s',$v['add_time']) ."</td>";
                    $string .="<td>". Fmoney($v['investor_capital']) ."元 </td>";
                    $string .="<td>".$is_auto ."</td>";
                    if($is_mobile){
                        $string .="<td>".$source ."</td>";
                    }
                    $string .="</tr>";
                }
           }
            echo empty($string)?'<tr style="background-color: rgb(255, 255, 255);" class="borrowlist3">
                   <td colspan=5 class="txtC">暂时没有投资记录</td></tr>':$string;

        }
        
    }

}
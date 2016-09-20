<?php
    class InvestAction extends HCommonAction
    {
        /**
         * 标列表
         */
        public function index()
        {
            /*散标开始*/
            $search = array();
            if($search['b.borrow_status']==0){
            	$search['b.borrow_status']=array("in","2,4,6,7");
	        }
	       
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
	        $parm['orderby']="b.borrow_status ASC,b.id DESC";
            $list = getBorrowList($parm);
            $this->assign("list",$list);
            $this->assign("page",$list['page']);
            /*散标结束*/
            $this->display();
        }


        public function detail()
        {
            $pre = C('DB_PREFIX');//表前缀
            $id = intval($_GET['id']);
            $field = "id,borrow_money,borrow_interest_rate,borrow_min,borrow_status,borrow_max,has_borrow,borrow_name,borrow_use,duration_unit,collect_time,borrow_duration,reward_num,repayment_type,add_time";
            $investlist = M('borrow_info')->field($field)->where("id = {$id}")->find();
            $this->assign('investlist',$investlist);
            $borrow_money = substr($investlist['has_borrow']/$investlist['borrow_money']*100,0,4);//已经融资多少
            $shenyu = ($investlist['borrow_money'] - $investlist['has_borrow']); //剩余金额
            //echo $investlist['has_borrow'];exit;
            $this->assign('borrow_moneys',$borrow_money);
            $this->assign('shenyu',$shenyu);
            $this->assign("borrow_investor_num",M('borrow_investor')->where("borrow_id={$id}")->count("id"));//投资记录个数
            $this->assign('gloconf',$this->gloconf);
            $vo = M('borrow_info')->field("borrow_uid")->find($id);
            $this->assign("borrow_uid",$vo['borrow_uid']);
            if($this->uid){
                $this->assign("uids",$this->uid);
            }else{
                $this->assign("uids",0);
            }
            $this->display();
        }
        
        /********投标详情页***********/
        public function ajax_invest(){
	        if(!$this->uid) {
	    		$this->error("请先登陆",U('m/common/logins'));
	    	}
        	$pre = C('DB_PREFIX');
        	$id=intval($_GET['id']);
        	$investMoney = intval($_GET['num']);
        	$borrowinfo = M("borrow_info")->field(true)->where("id='{$id}' and borrow_type<6")->find();
            //if($this->uid == $borrowinfo['borrow_uid']) ajaxmsg("不能去投自己的标",0);
			if(!is_array($borrowinfo) || ($borrowinfo['borrow_status']==0 && $this->uid!=$borrowinfo['borrow_uid']) ) $this->error("数据有误");
			$borrowinfo['need'] = $borrowinfo['borrow_money'] - $borrowinfo['has_borrow'];
			
        	//dump($binfo);exit;
        	//获取优惠券
            $_redbaolist = TborrowModel::get_format_borrow_info($id);
            //dump($_redbaolist);
            if ($_redbaolist['is_taste']==1){
                $expand_where = " uid=" . $this->uid . " and is_taste=1 and status=1 and expired_time > " . time();
            }else{
                $expand_where = " uid=" . $this->uid . " and is_taste=0 and status=1 and expired_time > " . time();
            }
            $expand_list = M('expand_money')
                ->field('id, money, invest_money, expired_time, type, use_time, remark, is_taste')
                ->where($expand_where)
                //->limit('3')
                ->order("money desc")
                ->select();
            //dump($expand_list);
            foreach ($expand_list as $key => $val) {
                if ($val['invest_money'] <= $num) {
                    $arr[] = $val;
                } elseif ($val['invest_money'] > $num) {
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
            $list_s = array_slice($list_merge, 0, 3);
            $list_s = ExpandMoneyModel::get_coupon_type_format($list_s);
            $this->assign('expand_list', $list_s);


            $expand_expired_list = M('expand_money')//取三个按最大金额的，另取三个按过期时间的，前台通过TAB切换
            ->field('id,money, invest_money, expired_time, type, use_time, remark, is_taste')
                ->where($expand_where)
                //->limit('3')
                ->order("expired_time asc")
                ->select();

            foreach ($expand_expired_list as $key => $val) {
                if ($val['invest_money'] <= $num) {
                    $arr_list[] = $val;
                } elseif ($val['invest_money'] > $num) {
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
            $list_lists = array_slice($list_list_merge, 0, 3);
            //dump($list_lists);
            $list_lists = ExpandMoneyModel::get_coupon_type_format($list_lists);
            $this->assign('expand_expired_list', $list_lists);
        	$vm = getMinfo($this->uid,'m.pin_pass,mm.account_money,mm.back_money,mm.money_collect');
        	
        	////////////////////////////////////待收金额限制 2013-08-26  fan///////////////////
        	$this->assign("binfo",$borrowinfo);
        	$this->assign("vm", $vm);
			$this->assign('id', $id);
        	$this->display();
        }

        /*
         * 此方法用于展示投标时优惠券页面信息
         * */
        public function yhq_id(){
            $coupon_ids = intval($_POST['yhq_id']); // 使用的优惠券id
            $coupon_items = M('expand_money')
                ->field('money, invest_money, expired_time, status')
                ->where("id = {$coupon_ids} && uid = {$this->uid}")
                ->find();
            if($coupon_items){
                ajaxmsg($coupon_items['money'],1);
            }else{
                ajaxmsg("数据错误！",1);
            }
        }

        public function investmoney(){
	        if(!$this->uid) {
	    		ajax("请先登陆",2);
	    	}

            $vm = getMinfo($this->uid,'m.pin_pass,mm.account_money,mm.back_money,mm.money_collect');
            $pin_pass = $vm['pin_pass'];
            $pin = md5($_POST['pin_pass']);
            if($pin<>$pin_pass) ajaxmsg("支付密码错误",0);

        	$money = intval($_POST['invest_money']);//投标金额
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
        				ajaxmsg('投资金额不够使用此优惠券',0);
        			}else {
        				$discount_money += $val['money'];
        			}
        		}
        	}

        	if( $money <= $discount_money ) {
        		ajaxmsg("使用优惠券总额不能高于（或等于）投资金额",0);
        	}
        	// 实际需要支付的金额 = 投资金额 - 使用优惠券的金额
        	$actual_money = $money - $discount_money;
        	
        	$borrow_id = intval($_POST['T_borrow_id']);
        	$m = M("member_money")->field('account_money,back_money,money_collect')->find($this->uid);
        	$amoney = $m['account_money']+$m['back_money'];
        	$uname = session('u_user_name');
        	if($amoney<$actual_money) ajaxmsg("尊敬的{$uname}，您准备投标{$money}元，但您的账户可用余额为{$amoney}元，请先去充值再投标",0);
        	//定向标 检测密码
        	$binfo = M("borrow_info")->field('borrow_money,has_borrow,has_vouch,borrow_max,borrow_min,borrow_type,password,money_collect,duration_unit')->find($borrow_id);
        	if(!empty($binfo['password'])){
        		if(empty($_POST['borrow_pass'])) ajaxmsg("此标是定向标，必须验证投标密码",0);
        		else if($binfo['password']<>md5($_POST['borrow_pass'])) ajaxmsg("投标密码不正确",0);
        	}

        
        	$binfo = M("borrow_info")->field('borrow_money,borrow_max,has_borrow,has_vouch,borrow_type,borrow_min,money_collect,borrow_status')->find($borrow_id);
        
        	////////////////////////////////////待收金额限制 2013-08-26  fan///////////////////
        	if($binfo['money_collect']>0){
        		if($m['money_collect']<$binfo['money_collect']) {
        			ajaxmsg("此标设置有投标待收金额限制，您账户里必须有足够的待收才能投此标",0);
        		}
        	}
        	////////////////////////////////////待收金额限制 2013-08-26  fan///////////////////
        
        	//投标总数检测
        	$capital = M('borrow_investor')->where("borrow_id={$borrow_id} AND investor_uid={$this->uid}")->sum('investor_capital');
        	if(($capital+$money)>$binfo['borrow_max']&&$binfo['borrow_max']>0){
        		$xtee = $binfo['borrow_max'] - $capital;
        		ajaxmsg("您已投标{$capital}元，此投上限为{$binfo['borrow_max']}元，你最多只能再投{$xtee}",0);
        	}
        	//if($binfo['has_vouch']<$binfo['borrow_money'] && $binfo['borrow_type'] == 2) $this->error("此标担保还未完成，您可以担保此标或者等担保完成再投标");
        	$need = $binfo['borrow_money'] - $binfo['has_borrow'];
        	$caninvest = $need - $binfo['borrow_min'];
        	if( $money>$caninvest && $need==0){
        		$msg = "尊敬的{$uname}，此标已被抢投满了,下次投标手可一定要快呦！";
        		ajaxmsg($msg,0);
        	}
        	if(($binfo['borrow_min']-$money)>0 ){
        		ajaxmsg("尊敬的{$uname}，本标最低投标金额为{$binfo['borrow_min']}元，请重新输入投标金额",0);
        	}
        	if(($need-$money)<0 ){
        		ajaxmsg("尊敬的{$uname}，此标还差{$need}元满标,您最多只能再投{$need}元",0);
        	}else{
        		if($binfo['borrow_status']==2){
        			$done = investMoney($this->uid,$borrow_id,$money,0, $coupon_ids);
        		}
        	}
        
        	if($done===true) {
        		ajaxmsg("恭喜成功投标{$money}元，使用优惠券{$coupon_items['money']}元,实际付款{$actual_money}元",1);
        	}else if($done){
        		ajaxmsg($done,0);
        	}else{
        		ajaxmsg("对不起，投标失败，请重试!",0);
        	}
        }
        
        /***************优惠券******************/
        public function coupons(){
        	ajaxmsg('',1);
        }

        public function investRecord(){

			isset($_GET['borrow_id']) && $borrow_id = intval($_GET['borrow_id']);
            $list = M("borrow_investor as b")
                        ->join(C("DB_PREFIX")."members as m on  b.investor_uid = m.id")
                        ->join(C("DB_PREFIX")."borrow_info as i on  b.borrow_id = i.id")
                        ->field('i.borrow_interest_rate, i.repayment_type, b.investor_capital, b.add_time, b.is_auto, m.user_name, i.borrow_duration,i.duration_unit')
                        ->where('b.borrow_id='.$borrow_id." and debt_time=0")
                        ->order('b.id desc')
                        ->select();
            $this->assign('list',$list);
			$this->display();
        }


        public function borrow_aboutus(){
            $borrow_id = intval($_GET['id']);
            $borrow_info = M('borrow_info')->field("borrow_info")->where("id = {$borrow_id}")->find();
            $this->assign('borrow_info',$borrow_info);
            $this->assign('borrowinfo',$borrow_info);
            $this->display();
        }

		public function info(){
			$type_id=$_GET['id'];
			$borrowinfo = M("borrow_info bi")->field('bi.*,ac.title,ac.id as aid')->join('lzh_article ac on ac.id= bi.danbao')->where('bi.id='.$type_id.' and borrow_type<6')->find();
			
			$this->assign('list',$borrowinfo);
			$this->display();
         }
        
    }
?>
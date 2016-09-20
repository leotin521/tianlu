<?php
    class TinvestAction extends HCommonAction
    {

        public function index(){
            $vo1 = M('members')->field('id,user_name,user_email,user_pass,is_ban')->where("id={$this->uid}")->find();
            if ($vo1['is_ban'] == 1 || $vo1['is_ban'] == 2) $this->error("您的帐户已被冻结，请联系客服处理！", __APP__ . "/index.html");
                       /*企业直投开始*/
            $search['b.borrow_type'] = BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID;
            if ($search['b.borrow_status'] == 0) {
                $search['b.borrow_status'] = array("in", array(BorrowModel::BID_SINGLE_CONFIG_STATUS_WAIT_ONLINE, BorrowModel::BID_SINGLE_CONFIG_STATUS_VIEW_PASS, BorrowModel::BID_SINGLE_CONFIG_STATUS_WAIT_REVIEW, BorrowModel::BID_SINGLE_CONFIG_STATUS_REPAYMENT, BorrowModel::BID_SINGLE_CONFIG_STATUS_SUCCESS));
            }

            $fields = "b.*, b.b_img, bd.bianhao";
            $page = (isset($_GET['p']) ? intval($_GET['p']) : 1);
            $order = "b.borrow_status=7,b.borrow_status=6,b.borrow_status=4,b.borrow_status=-1,b.borrow_status=2,b.id DESC";
            $data = TborrowModel::getTborrowByPage($search, $fields, $page, 15, $order);
            $this->assign("data", $data['tBorrow_items']);
            $this->assign("page", $data['page']);
            /*企业直投结束*/
            $this->display();
        }

        public function tdetail()
        {
            $pre = C('DB_PREFIX');//表前缀
            $id = intval($_GET['id']);
            $field = "id,borrow_money,borrow_interest_rate,borrow_min,borrow_max,has_borrow,borrow_name,borrow_status,borrow_use,duration_unit,collect_time,borrow_duration,reward_num,repayment_type,add_time,danbao,is_xinshou";
            $tinvestlist = M('borrow_info')->field($field)->where("id = {$id} and borrow_type = 6")->find();
            $borrow_money = substr($tinvestlist['has_borrow']/$tinvestlist['borrow_money']*100,0,4);//已经融资多少
            $shenyu = ($tinvestlist['borrow_money'] - $tinvestlist['has_borrow']); //剩余金额
            $tinvestlist['infos'] = "加入金额 100 元起，且以 100 元的倍数递增";
            $this->assign('borrow_moneys',$borrow_money);
            $this->assign("borrow_investor_num",M('borrow_investor')->where("borrow_id={$id}")->count("id"));//投资记录个数
            $this->assign('shenyu',$shenyu);
            $this->assign('investlist',$tinvestlist);
            if($tinvestlist['danbao']!=0 ){
                $danbao = M('article')->field('id,title')->where("type_id=7 and id={$tinvestlist['danbao']}")->find();
                $list['danbao']=$danbao['title'];//担保机构
            }else{
                $list['danbao']='暂无担保机构';//担保机构
            }
            $this->assign("danbao",$list['danbao']);

            $this->assign("uid",$this->uid);
            $this->assign("is_xinshou",$tinvestlist['is_xinshou']);
            $vo = M('borrow_info')->field("borrow_uid")->find($id);
            $this->assign("borrow_uid",$vo['borrow_uid']);
            if($this->uid){
                $this->assign("uids",$this->uid);
            }else{
                $this->assign("uids",0);
            }
            $this->display();
        }

        public function ajax_invest(){
        	if(!$this->uid) {
        		$this->error("请先登陆",U('m/common/logins'));
        	}
            $pre = C('DB_PREFIX');
            $id = intval($_GET['id']);
            $field = "m.account_money,m.back_money,b.id,b.borrow_min,b.borrow_money,b.has_borrow,b.borrow_name,b.add_time";
            $list = M('borrow_info b')->field($field)->join("{$pre}member_money m ON uid = {$this->uid}")->where("id = {$id}")->find();

            //优惠券
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
			$binfo = TborrowModel::get_format_borrow_info($id);
            $this->assign('list',$list);//标信息
            $this->assign('vo',$binfo);
            $this->assign('listmoney',($list['account_money']+$list['back_money']));
            $this->assign('hasmoney',($list['borrow_money']-$list['has_borrow']));
			$this->assign("id",$id);
            $this->display();
        }

         /*
         * 此方法用于展示投标时优惠券页面信息
         * */
        public function yhq_id(){
            $coupon_ids = text($_POST['yhq_id']); // 使用的优惠券id
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
	    	$pre = C('DB_PREFIX');
			$id = intval($_POST['T_borrow_id']);//借款人id
			$money = intval($_POST['invest_money']); //投资金额
	        $coupon_ids = filter_array($_POST['coupon']); // 使用的优惠券id array();
	        $is_xinshou = M("borrow_info")->field('is_xinshou')->where("id = {$id}")->find();
	        if($is_xinshou['is_xinshou'] == 1){
                $binfo = TborrowModel::get_format_borrow_info($id);
                if ($binfo['is_xinshou']==1) {
                    $binvest = BorrowInvestorModel::get_is_novice($this->uid);
                    if ($binvest==false){
                        ajaxmsg("当前标为新手专享标，只有新手才可以投", 0);
                    }
                }
	        }
			
			
			$discount_money = 0;
            //判断是不是体验标
            $is_taste = M("borrow_info")->getFieldById($id, "is_taste");
            // 如果使用优惠券
			if( !empty($coupon_ids) ) {
	            $expand_money = ExpandMoneyModel::get_discount_money($coupon_ids, $money, $this->uid,$is_taste);
			    if( $expand_money === false ) {
	                ajaxmsg('投资金额不够使用此优惠券',0);
	            }else {
	                $discount_money = $expand_money['discount_money'];
	            }
			}

            if( $money <= $discount_money ) {
                $this->error("使用优惠券总额不能高于（或等于）投资金额");
            }
			// 实际需要支付的金额 = 投资金额 - 使用优惠券的金额
			$actual_money = $money - $discount_money;
			if( $actual_money < 0 ) $actual_money = 0;
			$re = chkInvest($id, $this->uid, $money, $msg, text($_POST['pin_pass']), text($_POST['borrow_pass']));
			if ($re !== 1){ ajaxmsg($msg,0); }
			$re = false;
			$borrow_status = M("borrow_info")->getFieldById($id, "borrow_status");
			if (intval($borrow_status) === 2){ $re = TinvestMoney($this->uid, $id, $money, false, 0, 5, 1, $coupon_ids); }
			if($re === true){ 
			    $arr['status'] = 4;
	            $arr['loanno'] = $id;
	            $arr['use_time'] = time();
			    M('expand_money')->where(array('id'=>array('in',$coupon_ids),'uid'=>$this->uid))->save($arr);
                if($is_taste == 1){
                    $quan = "体验券";
                }else if($is_taste == 0){
                    unset($quan);
                    $quan = "抵现券";
                }
                ajaxmsg("恭喜成功认购{$money}元,{$quan}抵押{$discount_money}元，实际付款{$actual_money}元",1);
			}
			else if(empty($re) === false){ 
				ajaxmsg($done,0); 
			}
			else{ ajaxmsg("对不起，投标失败，请重试!",0); }
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
            $borrowinfo = TborrowModel::get_format_borrow_info($borrow_id, "b.*,  bwd.*, bd.bianhao");//`mxl 20150303`
            //dump($borrowinfo);
            $borrow_img = unserialize($borrowinfo['borrow_img']);//将json图片格式解析
            $this->assign('borrow_img',$borrow_img);
            $this->assign('borrowinfo',$borrowinfo);
            $this->display();
        }
	public function info(){
			$id = $_GET['id'];
			$borrowinfo = TborrowModel::get_format_borrow_info($id, "b.*, bwd.*, bd.bianhao");
			$this->assign('list',$borrowinfo);
			$this->display();
	}

    }
?>
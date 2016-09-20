<?php

// 本类是定投宝前台处理类
class FundAction extends HCommonAction
{
    public function index()
    {
        $vo1 = M('members')->field('id,user_name,user_email,user_pass,is_ban')->where("id={$this->uid}")->find();
        if ($vo1['is_ban'] == 1 || $vo1['is_ban'] == 2) $this->error("您的帐户已被冻结，请联系客服处理！", __APP__ . "/index.html");
        /*定投宝开始*/
        $where = array(
            'b.borrow_type' => BorrowModel::BID_CONFIG_TYPE_FINANCIAL,
            'b.borrow_status' => array('in', '-1,2,4,6,7'),
            'b.on_off' => 1
        );
        //dump($where);
        //exit;
        $fields = 'b.borrow_type,b.duration_unit,b.borrow_times,b.id,b.borrow_name,b.borrow_status,b.borrow_uid,b.borrow_duration,b.borrow_money,b.borrow_fee,b.borrow_interest_rate,b.repayment_type,b.has_borrow,b.add_time,m.user_name,b.borrow_max,b.is_tuijian,b.can_auto,b.is_xinshou';
        $page = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $order = "b.borrow_status=7,b.borrow_status=6,b.borrow_status=4,b.borrow_status=-1,b.borrow_status=2,b.id DESC";
        $fund_list = TborrowModel::getTborrowByPage($where, $fields, $page, false, $order);
        //dump($fund_list['tBorrow_items']);
        $this->assign("fund_list", $fund_list['tBorrow_items']);
        $this->assign("page", $fund_list['page']);
        /*定投宝结束*/
        $this->display();
    }

    public function tdetail(){
        $pre = C('DB_PREFIX');//表前缀
        $id = intval($_GET['id']);
        //dump($id);
        $borrowinfo = TborrowModel::get_borrow_info($id);
        //dump($borrowinfo);
        if( !empty($borrowinfo) ) {
            $article = M('article')->field('title,id as aid')->where('id='.$borrowinfo['danbao'])->find();
            if( !empty($article) ) {
                $borrowinfo['title'] = $article['title'];
                $borrowinfo['aid'] = $article['aid'];
            }
        }
        if($borrowinfo['borrow_type']!= 7){
            $this->error("非法操作");
        }
        $borrow_money = substr($borrowinfo['has_borrow']/$borrowinfo['borrow_money']*100,0,3);//已经融资多少
        $shenyu = ($borrowinfo['borrow_money'] - $borrowinfo['has_borrow']); //剩余金额
        $this->assign('borrow_moneys',$borrow_money);
        $this->assign('shenyu',$shenyu);
        $borrowinfo['progress'] = getfloatvalue($borrowinfo['has_borrow']/$borrowinfo['borrow_money'] * 100, 2);
        $borrowinfo['need'] =    $borrowinfo['borrow_money'] -$borrowinfo['has_borrow'];
        $borrowinfo['updata'] = unserialize($borrowinfo['updata']);
        if($borrowinfo['danbao']!=0 ){
            $danbao = M('article')->field('id,title')->where("type_id=7 and id={$borrowinfo['danbao']}")->find();
            $borrowinfo['danbao']=$danbao['title'];//担保机构
            $borrowinfo['danbaoid'] = $danbao['id'];
        }else{
            $borrowinfo['danbao']='暂无担保机构';//担保机构
        }
        $borrowinfo['infos'] = "加入金额 100 元起，且以 100 元的倍数递增";
        $borrowinfo['restday'] = ceil(($borrowinfo['deadline'] - time())/(24*60*60));
        $borrowinfo['currentday'] = time();
        $now=time();
        $borrowinfo['aa']=floor($borrowinfo['collect_day']-$now);
        $borrowinfo['leftday'] = ceil(($borrowinfo['collect_day']-$now)/3600/24);
        $borrowinfo['leftdays'] = floor(($borrowinfo['collect_day']-$now)/3600/24).'天以上';
        $this->assign("vo", $borrowinfo);
        $this->assign("borrow_investor_num",M('borrow_investor')->where("borrow_id={$id}")->count("id"));//投资记录个数
        $this->assign('uid',$this->uid);
        $this->assign("is_xinshou",$borrowinfo['is_xinshou']);
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

        $id = intval($_GET['id']);
        $num = intval($_GET['num']);
        $chooseWay = intval($_GET['chooseWay']);
        $this->assign("chooseway",$chooseWay);
        $binfo = TborrowModel::get_borrow_info($id);

        $binfo['uname'] = M("members")->getFieldById($binfo['borrow_uid'], "user_name");
        $binfo['need_num'] = ($binfo['borrow_money'] - $binfo['has_borrow']) ;
        $binfo['need'] =    $binfo['borrow_money'] -$binfo['has_borrow'];
        //dump($binfo['need_num']);exit;
        $minfo = getMinfo($this->uid, "m.pin_pass, mm.account_money, mm.back_money, mm.money_collect");


        $pin_pass = $minfo['pin_pass'];
        $has_pin = (empty($pin_pass) === true) ? "no" : "yes";
        $this->assign("has_pin", $has_pin);
        //  $this->assign("investMoney",$num);
        $this->assign("account_money", $minfo['account_money'] + $minfo['back_money']);
        $this->assign("vo", $binfo);
        $this->assign("hasmoney", $binfo['need_num']);
        $this->assign("borrow_min", $binfo['borrow_min']);
        $this->assign("num", $num);

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
        $repayment_type = intval($_POST['chooseWay']);
        $borrow_id = intval($_POST['T_borrow_id']);
        $money = intval($_POST['invest_money']);
        $coupon_ids = array(intval($_POST['coupon']));
        $discount_money = 0;
        if(intval($_POST['chooseWay'])==4){
            $invest_repayment_type = 1;
        }elseif (intval($_POST['chooseWay'])==6){
            $invest_repayment_type = 2;
        }
        /*******判断新手投标**********/
        $is_xinshou = M("borrow_info")->field('is_xinshou')->where("id = {$borrow_id}")->find();
        if($is_xinshou['is_xinshou'] == 1){
            $binfo = TborrowModel::get_format_borrow_info($borrow_id);
            if ($binfo['is_xinshou']==1) {
                $binvest = BorrowInvestorModel::get_is_novice($this->uid);
                if ($binvest==false){
                    ajaxmsg("当前标为新手专享标，只有新手才可以投", 0);
                }
            }
        }
        //判断是不是体验标
        $is_taste = M("borrow_info")->getFieldById($borrow_id, "is_taste");
        // 如果使用优惠券
        if(!empty($coupon_ids) ) {
            $expand_money = ExpandMoneyModel::get_discount_money($coupon_ids, $money, $this->uid,$is_taste);
            //file_put_contents("222.txt",$expand_money);
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

        $m = M("member_money")->field('uid,account_money,back_money,money_collect')->find($this->uid);
        $binfo = TborrowModel::get_borrow_info($borrow_id);

        $re= chkTwoInvest($m,$binfo,$money,1,$repayment_type);

        if($re===TRUE){
            $month = TborrowModel::get_remain_transfer_days($borrow_id);
            $done = TinvestMoney($this->uid,$borrow_id,$money,$month,0,$repayment_type,1, $coupon_ids,$invest_repayment_type);//投金额投标
            //$re = chkTwoInvest($m, $binfo, $tnum, 1, $repayment_type);
            if($done === true){
                $arr['status'] = 4;
                $arr['loanno'] = $borrow_id;
                $arr['use_time'] = time();
                M('expand_money')->where(array('id'=>array('in', $coupon_ids),'uid'=>$this->uid))->save($arr);
                if($is_taste == 1){
                    $quan = "体验券";
                }else if($is_taste == 0){
                    unset($quan);
                    $quan = "抵现券";
                }
                ajaxmsg("恭喜成功认购{$money}元,{$quan}抵押{$discount_money}元，实际付款{$actual_money}元",1);
            }else if($done){
                ajaxmsg($done,0);
            }else{
                ajaxmsg("对不起，认购失败，请重试!",0);
            }
        }else{
            ajaxmsg($re,0);
        }
    }

    public function investRecord(){

			isset($_GET['borrow_id']) && $borrow_id = intval($_GET['borrow_id']);
			//print_r($borrow_id);exit;
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

    public function coupons(){
        //优惠券
        $expand_where = " uid=".$this->uid." and status=1 and expired_time > ".time();
        $expand_list = M('expand_money')
            ->field('id,money, invest_money, expired_time, type, use_time, remark')
            ->where($expand_where)
            ->limit('3')
            ->order("id desc")
            ->select();
        //dump($expand_list);exit;
        $this->assign('expand_list', $expand_list);

        $expand_expired_list = M('expand_money') //取三个按最大金额的，另取三个按过期时间的，前台通过TAB切换
        ->field('id,money, invest_money, expired_time, type, use_time, remark')
            ->where($expand_where)
            ->limit('3')
            ->order("expired_time desc")
            ->select();
        $this->assign('expand_expired_list', $expand_expired_list);
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


}

?>
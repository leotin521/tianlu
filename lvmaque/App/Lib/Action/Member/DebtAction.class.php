<?php
    /**
    *  债权转让
    */
    class DebtAction extends MCommonAction
    {
        public $Detb;

        public function __construct()
        {
            parent::__construct();
            D("DebtBehavior");
            $this->Debt  = new DebtBehavior($this->uid);
        }
        /**
        * 债权转让默认页
        * 
        */
        public function index()
        {
            $debt_fee_rate = get_global_setting('debt_fee');
            $debt_duration = get_global_setting('debt_duration');
            $this->assign('debt_fee_rate', $debt_fee_rate);
            $this->assign('debt_duration', $debt_duration);
           $this->display();
        } 
        /**
        * 可流转的标,可以转让的债权
        */
        public function change()
        {
           $list = $this->Debt->canTransfer();
           $this->assign('now_time', strtotime(date('Y-m-d', time())));
           $this->assign('list', $list);
           $data = $this->fetch();
           exit($data);
        }
        public function sellhtml()
        {
            $datag = get_global_setting();
            $invest_id = isset($_GET['id'])? intval($_GET['id']):0;
            !$invest_id && ajaxmsg(L('parameter_error'),0);
            $info = $this->Debt->countDebt($invest_id);
            $price = $info['capital']+$info['interest'];
            $debt_fee_rate = $datag['debt_fee']; // 手续费率

            $this->assign('price', $price);
            $this->assign('info', $info);
            $this->assign('debt_fee_rate', $debt_fee_rate);
            $this->assign('datag', $datag['debt_fee']);
            $this->assign('invest_id', $invest_id);


            $borrow = M('borrow_investor i')
                ->join(C('DB_PREFIX')."borrow_info b ON i.borrow_id = b.id")
                ->field("borrow_name")
                ->where("i.id=".$invest_id)
                ->find();
            $this->assign("borrow_name", $borrow['borrow_name']);

            //判断支付密码
            $vm = getMinfo($this->uid,'m.pin_pass');
            $pin_pass = $vm['pin_pass'];
            $has_pin = (empty($pin_pass))?"no":"yes";
            $this->assign("has_pin",$has_pin);
            $d['status'] = 1;
            $d['content'] = $this->fetch();
            exit(json_encode($d));
        }

        public function sell()
        {
            $discount_gold = floatval($_POST['discount_gold']);
            $money = floatval($_POST['money']);
            $paypass = $_POST['paypass'];
            $invest_id = intval($_POST['invest_id']);
            if($discount_gold<0 || $discount_gold > 7.5){
                ajaxmsg('折让率超过0.0%-7.5%的范围',0);       
            }
            $deadline = M('investor_detail')->where("invest_id={$invest_id} and repayment_time=0")->getField('deadline');
            $day =   intval(($deadline - time())/ 3600/ 24);
            if($day < 5){
                ajaxmsg('剩余还款时间不得小于5天',0);       
            }
            if($money && $paypass && $invest_id){
                $result = $this->Debt->sell($invest_id, $money, $paypass, $discount_gold);
                if($result ==='TRUE')
                {
                    ajaxmsg('债权转让成功');   
                }else{
                    ajaxmsg($result,0);
                }
            }else{
                ajaxmsg('债权转让失败',0);
            }
        }
        /**
        * 进行中的债权
        * 
        */
        public function onBonds()
        {
            $list = $this->Debt->onBonds();
            $this->assign('list', $list);
            $data['html'] = $this->fetch();
            exit(json_encode($data));
        }
        /**
        *    成功的债权
        * 
        */
        public function successClaims()
        {
            $list = $this->Debt->successDebt();
            $this->assign('list', $list);
            $data['html'] = $this->fetch();
            exit(json_encode($data));
        }
        /**
        * 已购买的债权
        * 
        */
        public function buydebt()
        {
            $list = $this->Debt->buydetb();
            $this->assign('list', $list);
            $data['html'] = $this->fetch();
            exit(json_encode($data)); 
        }
        /**
        * 回收中的债权
        * 
        */
        public function ondetb()
        {
            $list = $this->Debt->onDetb();
            $this->assign('list', $list);
            $data['html'] = $this->fetch();
            exit(json_encode($data));
        }
        
        /**
        * 撤销转让债权ajax
        * 
        */
        public function cancelhtml()
        {
            $invest_id = intval($_REQUEST['invest_id']);
            $debt_info = M('debt')->field("assigned, money")->where("invest_id={$invest_id}")->find();
            if($debt_info['assigned']> 0.00){
                ajaxmsg('部分已转让，债权不能撤销', 0);
            }else{
                $this->assign('invest_id', $invest_id);
                $d['html'] = $this->fetch();
                $d['code'] = '10000';
                echo json_encode($d);
            }
        }
        /**
        *  撤销债权转让
        * 
        */
        public function cancel()
        {
            $invest_id = intval($_REQUEST['invest_id']);
            $paypsss = strval($_POST['paypass']);
            !$invest_id && ajaxmsg(L('parameter_error'), 0);
        
            if($this->Debt->cancel($invest_id, $paypsss)) {
                ajaxmsg(L('撤销成功'), 1);
            }else{  
                ajaxmsg(L('撤销失败'), 0);
            }
            
        }
        
        /**
        * 取消的债权软让
        * 
        */
        public function cancellist()
        {
            $list = $this->Debt->cancelList();
            $this->assign('list', $list);
            $data['html'] = $this->fetch();
            exit(json_encode($data));
        }
        
        public function  agreement()
        {
            //获取文章模版
            $article = M('article_category')->field('type_content')->where(array('type_nid'=>'zqht'))->find();
            if( !empty($article['type_content']) ) {
                $article_html = $article['type_content'];
                $invest_id = intval($_GET['invest_id']);
                $ht=M('hetong')->field('hetong_img,name,dizhi,tel')->find();
                $this->assign('ht', $ht);
                $fields = "i.serialid, d.sell_uid,d.discount_gold,d.interest_rate, i.investor_capital,i.add_time,m.user_name"
                    .",b.borrow_name,b.add_time as b_add_time,b.second_verify_time, b.id, b.borrow_interest_rate, b.total, b.has_pay";
                $debt = M("debt d")
                    ->field($fields)
                    ->join(C('DB_PREFIX')."borrow_investor i ON i.parent_invest_id=d.invest_id")
                    ->join(C('DB_PREFIX')."borrow_info b ON i.borrow_id=b.id")
                    ->join(C('DB_PREFIX')."members m ON d.sell_uid=m.id")
                    ->where("i.id={$invest_id}")->find();
                if( !empty($debt) ) {
                    //转让人真实姓名
                    $transfer_authentication = M('member_info')->field('idcard,real_name')->where(array('uid'=>$debt['sell_uid']))->find();
                    $borrow_investor = M('borrow_investor')->field("investor_uid,investor_capital,transfer_duration")->where(array('id'=>$invest_id))->find();
                    //不能过session获取，后期可直接移动到后台
                    if( !empty($borrow_investor) ) {
                        //购买者真实姓名
                        $invest_authentication = M('member_info')->field('idcard,real_name')->where(array('uid'=>$borrow_investor['investor_uid']))->find();
                    }
                    $debt['transfer_price'] = $debt['investor_capital']*(1-bcdiv($debt['discount_gold'], 100, 4));
                    if( $debt['borrow_type'] > BorrowModel::BID_CONFIG_TYPE_MORTGAGE ) {
                        $debt['second_verify_time'] = $debt['b_add_time'];
                    }
                }

                $web_name = $this->glo; //平台名称
                $transfer_price = $borrow_investor['investor_capital']*(1-$debt['discount_gold']/100); //本金的转让价格,这里的转让信息不包含利息
                $debt_fee_rate = get_global_setting('debt_fee');
                $debt_fee = $transfer_price*$debt_fee_rate/100; //转让手续费

                $healthy = array(
                    "[web_name]", "[serialid]", "[add_time]", "[transfer_real_name]", "[transfer_idcard]",
                    "[invest_real_name]", "[invest_idcard]", "[company_name]", "[domain]","[hetongzhang]",
                    "[transfer_capital]", "[transfer_price]", "[transfer_fee]", "[remain_days]", "[repayment_list]",
                );
                $yummy   = array(
                    $web_name['web_name'], $debt['serialid'], date('Y年m月d日', $debt['add_time']), $transfer_authentication['real_name'],  $transfer_authentication['idcard'],
                    $invest_authentication['real_name'], $invest_authentication['idcard'], $ht['name'], DOMAIN, '<img class="hetongzhang" src="/'.$ht['hetong_img'].'" border="0">',
                    $borrow_investor['investor_capital'], $transfer_price, $debt_fee, $borrow_investor['transfer_duration']
                );

                $newphrase = str_replace($healthy, $yummy, $article_html);


                $this->assign('article_html', $newphrase);
            } else {
                $this->error('系统有误，请联系网站客服！');
            }
            $debt_total = $this->Debt->getAlsoPeriods($invest_id);
            $this->assign('debt_total', $debt_total);
            $buy_user = M("members")->field("user_name")->where("id={$debt['buy_uid']}")->find();
            $this->assign('buy_user', $buy_user['user_name']);
            $this->assign('debt', $debt);
            $this->display();
        }
       
    }
?>

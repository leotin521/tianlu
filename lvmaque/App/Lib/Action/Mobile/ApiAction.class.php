<?php
// 本类由系统自动生成，仅供测试用途
class ApiAction extends HCommonAction {
	public function uc(){
		require C("APP_ROOT")."Lib/Uc/uc.php";
	}

	public function ruleserver(){
		header ( "Content-Type: text/html; charset=utf-8" );
		$info = M("article_category");
		$content = $info->where("id=6")->getField("type_content");
		$this->assign('content',$content);
		$this->display();
	}

	
 
	public function  agreement()
        {	$this->uid = $_GET['uid'];
			D("DebtBehavior");
			//print_r($this->uid);exit; 
            $this->Debt  = new DebtBehavior($this->uid);
			
            //获取文章模版
             $article = M('article_category')->field('type_content')->where(array('type_nid'=>'zqht'))->find();
            if( !empty($article['type_content']) ) {
                $article_html = $article['type_content'];
                $invest_id = $this->_get('invest_id','trim',0);
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
                    "[invest_real_name]", "[invest_idcard]", "[company_name]", "[domain]","[hetong_img]",
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
			//print_r($this->Debt->getAlsoPeriods($invest_id));exit;
            $debt_total = $this->Debt->getAlsoPeriods($invest_id);
            $this->assign('debt_total', $debt_total);
            $buy_user = M("members")->field("user_name")->where("id={$debt['buy_uid']}")->find();
            $this->assign('buy_user', $buy_user['user_name']);
            $this->assign('debt', $debt);
            $this->display();
        }
}
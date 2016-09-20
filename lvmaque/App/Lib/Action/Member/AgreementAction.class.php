<?php
// 本类由系统自动生成，仅供测试用途
class AgreementAction extends MCommonAction {
	
 public function downfile(){    //散biao
		$per = C('DB_PREFIX');
		//$borrow_config = require C("APP_ROOT")."Conf/borrow_config.php";
		$invest_id=intval($_GET['id']);
		//$borrow_id=intval($_GET['id']);
		$iinfo = M('borrow_investor')->field('id,borrow_id,investor_capital,investor_interest,deadline,investor_uid,add_time')->where("(investor_uid={$this->uid} OR borrow_uid={$this->uid}) AND id={$invest_id}")->find();
		$binfo = M('borrow_info')->field('id,repayment_type,borrow_duration,duration_unit,borrow_uid,borrow_type,borrow_use,borrow_money,full_time,add_time,borrow_interest_rate,deadline,second_verify_time')->find($iinfo['borrow_id']);
		$mBorrow = M("members m")->join("{$per}member_info mi ON mi.uid=m.id")->field('mi.real_name,m.user_name,mi.idcard')->where("m.id={$binfo['borrow_uid']}")->find();
		$mInvest = M("members m")->join("{$per}member_info mi ON mi.uid=m.id")->field('mi.real_name,m.user_name,mi.idcard')->where("m.id={$iinfo['investor_uid']}")->find();
		if(!is_array($iinfo)||!is_array($binfo)||!is_array($mBorrow)||!is_array($mInvest)) exit;
		
		$detail = M('investor_detail d')->field('d.borrow_id,d.investor_uid,d.borrow_uid,d.capital,sum(d.capital+d.interest-d.interest_fee) benxi,d.total')->where("d.borrow_id={$iinfo['borrow_id']} and d.invest_id ={$iinfo['id']}")->group('d.investor_uid')->find();

		$detailinfo = M('investor_detail d')->join("{$per}borrow_investor bi ON bi.id=d.invest_id")->join("{$per}members m ON m.id=d.investor_uid")->field('d.borrow_id,d.investor_uid,d.borrow_uid,d.capital,sum(d.capital+d.interest-d.interest_fee) benxi,d.total,m.user_name,bi.investor_capital,bi.add_time')->where("d.borrow_id={$iinfo['borrow_id']} and d.invest_id ={$iinfo['id']}")->find();
		$repayment_list = M('investor_detail d')->field('d.borrow_id,d.investor_uid,d.borrow_uid,(d.capital+d.interest-d.interest_fee) benxi,d.capital,d.interest,d.interest_fee,d.sort_order,d.deadline')->where("d.borrow_id={$iinfo['borrow_id']} and d.invest_id ={$iinfo['id']}")->select();
		if( !empty($repayment_list) ) {
            $repayment_list_str = '<table class="repayment_list" cellspacing="0" border="1">';
            $repayment_list_str .= '<tr><th>期数</th><th>金额</th><th>本金</td><th>利息</th><th>截止日</th></tr>';
            foreach( $repayment_list as $val ) {
                $deadline = date('Y年m月d日',$val['deadline']);
                $repayment_list_str .= "<tr>";
                $repayment_list_str .= "<td>第{$val['sort_order']}期</td>";
                $repayment_list_str .= "<td>{$val['benxi']}</td>";
                $repayment_list_str .= "<td>{$val['capital']}</td>";
                $repayment_list_str .= "<td>{$val['interest']}</td>";
                $repayment_list_str .= "<td>{$deadline}</td>";
                $repayment_list_str .= "</tr>";
            }
            $repayment_list_str .= '</table>';
        }
		
		$time = M('borrow_investor')->field('id,add_time')->where("borrow_id={$iinfo['borrow_id']} order by add_time asc")->limit(1)->find();
		
		if($binfo['repayment_type']==1){
				$deadline_last = strtotime("+{$binfo['borrow_duration']} day",$time['add_time']);
			}else{
				$deadline_last = strtotime("+{$binfo['borrow_duration']} month",$time['add_time']);
			}
        $jujianfang=M('hetong')->field('hetong_img,name,dizhi,tel')->find();
		$binfo['repayment_name'] = BorrowModel::get_repay_type($binfo['repayment_type']);

		$iinfo['repay'] = getFloatValue(($iinfo['investor_capital']+$iinfo['investor_interest'])/$binfo['borrow_duration'],2);
		
     $glo = $this->glo;
     //获取文章模版
     $article = M('article_category')->field('type_content')->where(array('type_nid'=>'sbht'))->find();
     if( !empty($article['type_content']) ) {
         $article_html = $article['type_content'];
         $hetong_num = "bytp2pD".date('Ymd',$iinfo['add_time']).$iinfo['id'];
         $duration_unit = BorrowModel::get_unit_format($binfo['duration_unit']);
         $healthy = array(
             "[web_name]", "[borrow_id]", "[company_name]", "[company_address]", "[hetong_img]",
             "[company_tel]", "[invest_real_name]", "[borrow_real_name]","[domain]", "[capital_interest]", "[invest_duration]","[interest_rate]",
             "[invest_user_name]", "[invest_idcard]", "[invest_capital]", "[repayment_name]",
             "[second_verify_time]","[hetong_num]", "[deadline]", "[repayment_list]"
         );
         $yummy   = array(
             $glo['web_name'], $binfo['borrow_id'], $jujianfang['name'], $jujianfang['dizhi'], '<div class="htzhang"><img src="/'.$jujianfang['hetong_img'].'" height="150px"/></div>',
             $jujianfang['tel'], $mInvest['real_name'], $mBorrow['real_name'], DOMAIN, $detailinfo['benxi'], $binfo['borrow_duration'].$duration_unit, $binfo['borrow_interest_rate'],
             $mInvest['user_name'], $mInvest['idcard'], $iinfo['investor_capital'], $binfo['repayment_name'],
             date('Y年m月d日', $binfo['second_verify_time']), $hetong_num, date('Y年m月d日', $binfo['deadline']), $repayment_list_str
         );

         $newphrase = str_replace($healthy, $yummy, $article_html);
     }

     $this->assign('glo', $glo);
        $this->assign('article_html', $newphrase);
		$this->display("index");
		
    }
	
	 public function downliuzhuanfile(){   //直投
		$per = C('DB_PREFIX');
		$borrow_config = require C("APP_ROOT")."Conf/borrow_config.php";
		$type = $borrow_config['REPAYMENT_TYPE'];

		$invest_id=intval($_GET['id']);
		
		$iinfo = M("borrow_investor")->field(true)->where("investor_uid={$this->uid} AND id={$invest_id}")->find();

		$binfo = M('borrow_info')->field(true)->find($iinfo['borrow_id']);
		$tou =  M('investor_detail')->where(" borrow_id={$iinfo['borrow_id']} AND investor_uid={$this->uid} ")->find();
		
		$mBorrow = M("members m")->join("{$per}member_info mi ON mi.uid=m.id")->field('mi.real_name,m.user_name')->where("m.id={$binfo['borrow_uid']}")->find();
		$mInvest = M("members m")->join("{$per}member_info mi ON mi.uid=m.id")->field('mi.real_name,m.user_name,mi.idcard')->where("m.id={$iinfo['investor_uid']}")->find();
		
		if(!is_array($tou)) $mBorrow['real_name'] = hidecard($mBorrow['real_name'],5);

		$binfo['repayment_name'] = $type[$binfo['repayment_type']];

		$this->assign("bid","LZBHT-".str_repeat("0",5-strlen($binfo['id'])).$binfo['id']);
		
		$detailinfo = M('investor_detail d')->join("{$per}borrow_investor bi ON bi.id=d.invest_id")->join("{$per}members m ON m.id=d.investor_uid")->field('d.borrow_id,d.investor_uid,d.borrow_uid,d.capital,sum(d.capital+d.interest-d.interest_fee) benxi,d.total,m.user_name,bi.investor_capital,bi.add_time')->where("d.borrow_id={$iinfo['borrow_id']} and d.invest_id ={$iinfo['id']}")->find();
		
		$time = M('borrow_investor')->field('id,add_time')->where("borrow_id={$iinfo['borrow_id']} order by add_time asc")->limit(1)->find();
		
		$deadline_last = strtotime("+{$binfo['borrow_duration']} month",$time['add_time']);
		
		$this->assign('deadline_last',$deadline_last);

		$type1 = $this->gloconf['BORROW_USE'];
		$binfo['borrow_use'] = $type1[$binfo['borrow_use']];



		$type = $borrow_config['REPAYMENT_TYPE'];
		$binfo['repayment_name'] = $type[$binfo['repayment_type']];

		$iinfo['repay'] = getFloatValue(($iinfo['investor_capital']+$iinfo['investor_interest'])/$binfo['borrow_duration'],2);
		
		$detail_list = M('investor_detail')->field(true)->where("invest_id={$invest_id}")->select();
		$this->assign("detail_list",$detail_list);

         $business_name = M('business_detail')->where(array('uid'=>$binfo['borrow_uid']))->getField('business_name');
         $duration_unit = BorrowModel::get_unit_format($binfo['duration_unit']);

         $jujianfang=M('hetong')->field('hetong_img,name,dizhi,tel')->find();
         $glo = $this->glo;
         //获取文章模版
         $article = M('article_category')->field('type_content')->where(array('type_nid'=>'ztht'))->find();
         if( !empty($article['type_content']) ) {
             $article_html = $article['type_content'];
             $healthy = array(
                 "[web_name]", "[borrow_id]", "[company_name]", "[company_address]", "[hetong_img]",
                 "[company_tel]", "[invest_real_name]", "[domain]", "[capital_interest]", "[invest_duration]",
                 "[invest_user_name]", "[invest_idcard]", "[invest_capital]", "[repayment_type]",
                 "[second_verify_time]","[business_name]", "[deadline]"
             );
             $yummy   = array(
                 $glo['web_name'], $binfo['borrow_id'], $jujianfang['name'], $jujianfang['dizhi'], '<div class="Seal"><img src="/'.$jujianfang['hetong_img'].'" height="150px"/></div>',
                 $jujianfang['tel'], $mInvest['real_name'], DOMAIN, $detailinfo['benxi'], $binfo['borrow_duration'].$duration_unit,
                 $mInvest['user_name'], $mInvest['idcard'], $iinfo['investor_capital'], $binfo['repayment_name'],
                 date('Y年m月d日', $binfo['second_verify_time']), $business_name, date('Y年m月d日', $binfo['deadline'])
             );

             $newphrase = str_replace($healthy, $yummy, $article_html);
         }

         $this->assign('article_html', $newphrase);
		$this->display("transfer");
    }

 public function downdingtoubao(){  //定投宝
		$per = C('DB_PREFIX');
		$borrow_config = require C("APP_ROOT")."Conf/borrow_config.php";
		$type = $borrow_config['REPAYMENT_TYPE'];

		$invest_id=intval($_GET['id']);
		if(empty($invest_id)) $this->display("dingtoubao");
		$datag = get_global_setting();
		$fee_rate = $datag['fee_invest_manage'];//投资者成交管理费费率
		$iinfo = M("borrow_investor")->field(true)->where("investor_uid={$this->uid} AND id={$invest_id}")->find();

		$binfo = M('borrow_info')->field(true)->find($iinfo['borrow_id']);
		$tou =  M('investor_detail')->where("invest_id={$iinfo['id']} AND investor_uid={$this->uid} ")->find();
		$mBorrow = M("members m")->join("{$per}member_info mi ON mi.uid=m.id")->field('mi.real_name,mi.address,m.user_phone,m.user_name')->where("m.id={$binfo['borrow_uid']}")->find();
		$mInvest = M("members m")->join("{$per}member_info mi ON mi.uid=m.id")->field('mi.real_name,mi.address,m.user_phone,mi.idcard,m.user_name,m.user_email')->where("m.id={$iinfo['investor_uid']}")->find();
      $danbao = M('article')->field('id,title,art_img')->where("id={$binfo['danbao']}")->find();
		if(!is_array($tou)) $mBorrow['real_name'] = hidecard($mBorrow['real_name'],4);

		$binfo['repayment_name'] = $type[$binfo['repayment_type']];

		$this->assign("bid","LZBHT-".str_repeat("0",5-strlen($iinfo['id'])).$iinfo['id']);
  
  //合同动态渲染
  
  //1.居间方资料获取
     $jujianfang=M('hetong')->field('hetong_img,name,dizhi,tel')->find();
      $glo = $this->glo;
     	$batch_no="LZBHT-".str_repeat("0",5-strlen($iinfo['id'])).$iinfo['id'];
      $borrow_name = $binfo['borrow_name'];
       $article = M('article_category')->field('type_content')->where(array('type_nid'=>'dtbht'))->find();
        if( !empty($article['type_content']) ) {
            $article_html = $article['type_content'];
            $healthy = array(
                    "[web_name]",
                    "[batch_no]",
                    "[borrow_name]",
                    "[company_name]",
                    "[company_address]",
                    "[company_phone]",
                    "[hetong_img]",
                    "[invest_real_name]", 
                    "[domain]",
                    "[invest_user_name]",
                    "[invest_phone]",
                    "[invest_idcard]", 
                    "[per_transfer]",
                    "[join_time]",
                    "[join_money]",
                    "[end_time]",
                    "[fee_rate]", //利息管理费
                    "[add_time]", //投资时间
                    
            );
            $yummy   = array(
                    $glo['web_name'], 
                    $batch_no,
                    $binfo['borrow_name'],
                    $jujianfang['name'],
                    $jujianfang['dizhi'],
                    $jujianfang['tel'],
                    '<div class="htzhang"><img src="/'.$jujianfang['hetong_img'].'" height="150px"/></div>', 
                    $mInvest['real_name'], 
                    DOMAIN,
                    $mInvest['user_name'],
                    $mInvest['user_phone'],
                    $mInvest['idcard'], 
                    $binfo['borrow_min'],
                    date('Y年m月d日', $iinfo['add_time']),
                    $iinfo['investor_capital'],
                    date('Y年m月d日', $iinfo['deadline']),
                    $glo['fee_invest_manage'],
                date('Y年m月d日',$binfo['add_time'])
            );
            $newphrase = str_replace($healthy, $yummy, $article_html);
        }
        $this->assign('article_html', $newphrase);
      	$this->display("dingtoubao");
    }

    public function flexible(){  //灵活宝
        $pre = C('DB_PREFIX');
        $batch_no = $this->_get('id');
        $glo = $this->glo;
        $where = array(
            'bi.batch_no' => $batch_no,
            'bi.uid' => $this->uid
        );
        $fields = 'b.interest_rate,b.repayment_period,b.term,b.start_funds,bi.*';
        $bao_item = M('bao b')->field($fields)->join("{$pre}bao_invest bi on b.batch_no=bi.batch_no")->where($where)->find();
        if( !empty($bao_item) ) {
            $diff_day = getDaysByMonth($bao_item['repayment_period']);
            $bao_item['time_et'] = strtotime("+ {$diff_day} days", $bao_item['add_time']);
            $bao_item['term_time_et'] = strtotime("+ {$bao_item['term']} days", $bao_item['add_time']);
        }
        if( empty($bao_item) ) $this->error('非法请求');

        //乙方真实姓名，身份证号，用户名
        $member_info = M('member_info')->field('real_name,idcard')->where(array('uid'=>$this->uid))->find();
        if( !empty($member_info) ) {
            $member_info['u_user_name'] = $_SESSION['u_user_name'];
            $member_info['idcard'] = hidecard($member_info['idcard'], 1);
        }

        //合同信息
        $jujianfang = M('hetong')->find();

        //获取文章模版
        $article = M('article_category')->field('type_content')->where(array('type_nid'=>'lhbht'))->find();
        if( !empty($article['type_content']) ) {
            $article_html = $article['type_content'];
            $healthy = array(
                "[web_name]", "[batch_no]", "[company_name]", "[company_address]", "[jujianfang.hetong_img]", "[invest_real_name]", "[domain]",
                "[invest_user_name]", "[invest_idcard]", "[bao_item.money]", "[bao_item.add_time]","[bao_item.term_time_et]",
                "[bao_item.time_et]", "[bao_item.interest_rate]", "[bao_item.start_funds]"
            );
            $yummy   = array(
                $glo['web_name'], $bao_item['batch_no'], $jujianfang['name'], $jujianfang['dizhi'], '<div style="position: absolute; bottom:30px;right:30px;"><img src="/'.$jujianfang['hetong_img'].'" height="150px"/></div>', $member_info['real_name'], DOMAIN,
                    $member_info['u_user_name'], $member_info['idcard'], $bao_item['money'], date('Y年m月d日', $bao_item['add_time']), date('Y年m月d日', $bao_item['term_time_et']),
                date('Y年m月d日', $bao_item['time_et']), $bao_item['interest_rate'], $bao_item['start_funds']
            );

            $newphrase = str_replace($healthy, $yummy, $article_html);
        }

        $this->assign('article_html', $newphrase);
        $this->display();
    }


}
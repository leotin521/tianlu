<?php

// 本类是定投宝前台处理类
class DebtAction extends HCommonAction
{
    public function index()
    {
        $vo1 = M('members')->field('id,user_name,user_email,user_pass,is_ban')->where("id={$this->uid}")->find();
        if ($vo1['is_ban'] == 1 || $vo1['is_ban'] == 2) $this->error("您的帐户已被冻结，请联系客服处理！", __APP__ . "/index.html");

        $search = array();
        if ($search['d.status'] == 0) {
            $search['d.status'] = array("in", "2,4");
        }
        $str = "%" . urldecode($_REQUEST['searchkeywords']) . "%";
        if ($_GET['is_keyword'] == '1') {
            $search['m.user_name'] = array("like", $str);
        } elseif ($_GET['is_keyword'] == '2') {
            $search['b.borrow_name'] = array("like", $str);
        }
        $parm['map'] = $search;

        D("DebtBehavior");
        $Debt = new DebtBehavior();
        $list = $Debt->listAll($parm);
        $this->assign("listdetb", $list['data']);
        $this->assign("page", $list['page']);
        $this->display();

    }

    /**
     * 债权转让详情，代码重构 150423
     */
    public function tdetail()
    {
        $pre = C('DB_PREFIX');
        $id = intval($_GET['id']);
        $debt = M('debt')->field('*')->where(array('id' => $id))->find(); // 撤销时需要把debt里的数据删除，否则会导致
        D("DebtBehavior");

        if (!empty($debt['invest_id'])) {

            $debt['need'] = $debt['money'] - $debt['assigned'];  //可投金额
            $debt_duration = get_global_setting('debt_duration');
            $debt['debt_et'] = date('Y-m-d', $debt['addtime'] + $debt_duration * 24 * 3600); // 截止时间
            $debt['progress'] = intval($debt['assigned'] / $debt['money'] * 100);
            $borrow_id = M('borrow_investor')->where(array('id' => $debt['invest_id']))->getField('borrow_id');
            if (!empty($borrow_id)) {
                //标的详情
                $borrow_info = M('borrow_info ')
                    ->field('id as borrow_id,borrow_uid,borrow_name,borrow_duration,duration_unit,borrow_money,has_borrow,borrow_min,borrow_max,borrow_interest_rate,borrow_type,repayment_type')
                    ->where(array('id' => $borrow_id))
                    ->find();
                $b_invest = M('borrow_investor')->field('invest_repayment_type')->where(array('borrow_id' => $borrow_id))->find();
                if (!empty($borrow_info)) {
                    $borrow_info['remain_duration'] = TborrowModel::get_remain_transfer_days($borrow_id, 1);

                    if ($b_invest['invest_repayment_type']==1){
                        $borrow_info['repayment_type_name'] = "按月还息";
                    }else {
                        $borrow_info['repayment_type_name'] = BorrowModel::get_repay_type($borrow_info['repayment_type']);
                    }

                    // 登录人信息
                    if (!empty($_SESSION['u_id'])) {
                        $vminfo = getMinfo($this->uid, 'mm.account_money,mm.back_money,mm.money_collect');
                        if (!empty($vminfo)) {
                            $vminfo['account'] = $vminfo['account_money'] + $vminfo['back_money'];
                        }
                    }

                    //转让人用户名
                    $sell_uname = M('members')->field('user_name')->where(array('id' => $debt['sell_uid']))->find();


                    $invest_num = M('borrow_investor')->where("parent_invest_id = {$debt['invest_id']}")->count();
                    //当前页面的url
                    $current_url = DOMAIN . $_SERVER['REQUEST_URI'];
                    $data['progress'] = $debt['progress'];//投标进度
                    $data['invest_id'] = $debt['invest_id'];
                    $data['invest_num'] = M('borrow_investor')->where("parent_invest_id={$debt['invest_id']}")->count('id');//债权投标人数
                    $data['borrow_name'] = $borrow_info['borrow_name'];//转让名称
                    $data['borrow_type'] = $borrow_info['borrow_type'];//原标类型
                    $data['money'] = $debt['money'];//转让金额
                    $data['interest_rate'] = $debt['interest_rate'].'%';//现年化收益率：
                    $data['borrow_interest_rate'] = $borrow_info['borrow_interest_rate'].'%';//原年化收益率：
                    $data['remain_duration'] = floatval($borrow_info['remain_duration']).'天';//剩余期限：
                    $data['need'] = number_format($debt['need'],2);//剩余可投
                    $data['repayment_type_name'] = $borrow_info['repayment_type_name'];//还款方式：
                    $data['debt_et'] = $debt['debt_et'];//截止时间：
                    $data['borrow_id'] = $borrow_info['borrow_id'];//查看原项目
                    $data['qitou'] = '10元';//起投金额
                    $data['status'] = $debt['status'];//起投金额

                    $borrow_money = substr($debt['assigned']/$debt['money']*100,0,4);//已经融资多少
                    $this->assign("borrow_moneys",$borrow_money);
                    if($this->uid){
                        $this->assign("uids",$this->uid);
                    }else{
                        $this->assign("uids",0);
                    }
                    $this->assign("borrow_uid",$debt['sell_uid']);
                    $this->assign("datas",$data);
                    $this->assign("debtid",$debt['id']);
                }

            }
        } else {
            //AppCommonAction::ajax_encrypt('提交参数有误!',0);
            $this->error('參數有誤，請聯繫管理員',"__APP__/m/debt/");
        }
            $this->display();
    }

    public function investRecord(){
		$where = array(
                        'parent_invest_id' => $_GET['borrow_id']
                    );
                    $fields = "bi.investor_uid,bi.add_time,investor_capital";
                    $invest_record = BorrowInvestorModel::getBorrowInvestByPage($where, true, 1, 100);//只取6个
                    
                        $invest_uids = only_array($invest_record['invest_items'], 'investor_uid');
                        if (!empty($invest_uids)) {
                            $invest_unames = M('members')->field('user_name,id')->where('id in(' . implode(',', $invest_uids) . ')')->select();
                            if (!empty($invest_unames)) {
                                for ($i = 0; $i < count($invest_record['invest_items']); $i++) {
                                    foreach ($invest_unames as $v) {
                                        if ($v['id'] == $invest_record['invest_items'][$i]['investor_uid']) {
                                            $invest_record['invest_items'][$i]['investor_uname'] = hidecard($v['user_name'], 5);
                                            break;
                                        }
                                    }
                                }
                            }
                        }
						//print_r($_GET['borrow_id']);exit;
                        $this->assign('list', $invest_record['invest_items']);
						$this->display();
                        //$this->assign('page', $invest_record['page']);
                    
        /*$debt['invest_id']= $_GET['borrow_id'];
        $investRecord = investRecord($debt['invest_id'],1);//如果是债权转让，则加第二个参数，代表债权
        echo $investRecord;exit;*/
    }


    public function ajax_invest(){
        $pre = C('DB_PREFIX');
        $id = intval($_GET['id']);
        $debt = M('debt')->field('*')->where(array('id' => $id))->find(); // 撤销时需要把debt里的数据删除，否则会导致

        if (!empty($debt['invest_id'])) {

            $debt['need'] = $debt['money'] - $debt['assigned'];  //可投金额
            $debt_duration = get_global_setting('debt_duration');
            $debt['debt_et'] = date('Y-m-d', $debt['addtime'] + $debt_duration * 24 * 3600); // 截止时间
            $debt['progress'] = intval($debt['assigned'] / $debt['money'] * 100);
            $borrow_id = M('borrow_investor')->where(array('id' => $debt['invest_id']))->getField('borrow_id');
            if (!empty($borrow_id)) {
                //标的详情
                $borrow_info = M('borrow_info ')
                    ->field('id as borrow_id,borrow_uid,borrow_name,borrow_duration,duration_unit,borrow_money,borrow_min,borrow_max,borrow_interest_rate,borrow_type,repayment_type')
                    ->where(array('id' => $borrow_id))
                    ->find();
                $b_invest = M('borrow_investor')->field('invest_repayment_type')->where(array('borrow_id' => $borrow_id))->find();
                if (!empty($borrow_info)) {
                    $borrow_info['remain_duration'] = TborrowModel::get_remain_transfer_days($borrow_id, 1);

                    if ($b_invest['invest_repayment_type']==1){
                        $borrow_info['repayment_type_name'] = "按月还息";
                    }else {
                        $borrow_info['repayment_type_name'] = BorrowModel::get_repay_type($borrow_info['repayment_type']);
                    }

                    // 登录人信息
                    if (!empty($_SESSION['u_id'])) {
                        $vminfo = getMinfo($this->uid, 'mm.account_money,mm.back_money,mm.money_collect');
                        if (!empty($vminfo)) {
                            $vminfo['account'] = $vminfo['account_money'] + $vminfo['back_money'];
                        }
                    }

                    //转让人用户名
                    $sell_uname = M('members')->field('user_name')->where(array('id' => $debt['sell_uid']))->find();


                    $invest_num = M('borrow_investor')->where("parent_invest_id = {$debt['invest_id']}")->count();
                    //当前页面的url
                    $current_url = DOMAIN . $_SERVER['REQUEST_URI'];
                    $data['progress'] = $debt['progress'];//投标进度
                    $data['invest_id'] = $debt['invest_id'];
                    $data['invest_num'] = M('borrow_investor')->where("parent_invest_id={$debt['invest_id']}")->count('id');//债权投标人数
                    $data['borrow_name'] = $borrow_info['borrow_name'];//转让名称
                    $data['borrow_type'] = $borrow_info['borrow_type'];//原标类型
                    $data['money'] = $debt['money'];//转让金额
                    $data['interest_rate'] = $debt['interest_rate'].'%';//现年化收益率：
                    $data['borrow_interest_rate'] = $borrow_info['borrow_interest_rate'].'%';//原年化收益率：
                    $data['remain_duration'] = floatval($borrow_info['remain_duration']).'天';//剩余期限：
                    $data['need'] = number_format($debt['need'],2);//剩余可投
                    $data['repayment_type_name'] = $borrow_info['repayment_type_name'];//还款方式：
                    $data['debt_et'] = $debt['debt_et'];//截止时间：
                    $data['borrow_id'] = $borrow_info['borrow_id'];//查看原项目
                    $data['qitou'] = '10元';//起投金额
                    $data['status'] = $debt['status'];//按钮状态
                    $data['borrow_max'] = $borrow_info['borrow_max'];//max
                    $this->assign("datas",$data);
                    $this->assign("debtid",$debt['id']);
                    //file_put_contents("8888.txt",$debt['id']);

                    $minfo = getMinfo($this->uid, "m.pin_pass, mm.account_money, mm.back_money, mm.money_collect");
                    $pin_pass = $minfo['pin_pass'];
                    $this->assign("account_money", $minfo['account_money'] + $minfo['back_money']);

                }

            }
        }
		$this->assign("id",$id);
        $this->display();
    }
    /*********在权转让购买*************/
    /**
     * 确认购买
     * 流程： 检测购买条件
     * 购买
     */
	    public function investcheck(){
	    	$paypass = strval($_POST['pin_pass']); //支付密码
	    	$invest_id = intval($_POST['invest_id']);
	    	$money = floatval($_POST['money']);
            $debtid = intval($_POST['debtid']);
            $debt = M('debt')->field("*")->where(array('id' => $debtid))->find();
            file_put_contents("99999999999.txt",M()->getLastSql());
            if ($this->uid == $debt['sell_uid']) ajaxmsg("不能购买自己转让的债权", 0);
            if ($debt['status'] <> 2) ajaxmsg("只能投正在转让中的债权", 0);

            D("DebtBehavior");
	    	$Debt = new DebtBehavior($this->uid);

	    	// 检测是否可以购买  密码是否正确，余额是否充足
	    	$result = $Debt->buy($paypass, $invest_id, $money);

	    	if($result === '购买成功'){
	    		$array = array(
	    				'status' => 1,
	    				'message' => '购买成功'
	    		);
	    	}else{
	    		$array = array(
	    				'status' => 0,
	    				'message' => $result
	    		);
	    	}
	    		exit(json_encode($array));
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
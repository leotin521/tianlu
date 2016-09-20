<?php

/**
 * 普通标债权转让控制器类
 *
 * @author  zhangjili 404851763@qq.com
 * @time 2014-01-03 16:28
 * @copyright lvmaque 超级版
 * @link www.lvmaque.com
 */
class DebtAction extends HCommonAction
{
    /**
     * 债权转让列表
     *
     */
    public function index()
    {
        $vo1 = M('members')->field('id,user_name,user_email,user_pass,is_ban')->where("id={$this->uid}")->find();
        if ($vo1['is_ban'] == 1 || $vo1['is_ban'] == 2) $this->error("您的帐户已被冻结，请联系客服处理！", __APP__ . "/index.html");

        $curl = $_SERVER['REQUEST_URI'];
        $urlarr = parse_url($curl);
        parse_str($urlarr['query'], $surl);//array获取当前链接参数，2.
        $urlArr = array('status', 'borrow_duration', 'interest_rate');
        $leveconfig = FS("Webconfig/leveconfig");
        foreach ($urlArr as $v) {
            $newpars = $surl;//用新变量避免后面的连接受影响
            unset($newpars[$v], $newpars['type'], $newpars['order_sort'], $newpars['orderby']);//去掉公共参数，对掉当前参数
            foreach ($newpars as $skey => $sv) {
                if ($sv == "all") unset($newpars[$skey]);//去掉"全部"状态的参数,避免地址栏全满
            }

            $newurl = http_build_query($newpars);//生成此值的链接,生成必须是即时生成
            $searchUrl[$v]['url'] = $newurl;
            $searchUrl[$v]['cur'] = empty($_GET[$v]) ? "all" : text($_GET[$v]);
        }
        $searchMap['status'] = array("all" => "不限制", "2" => "正在转让", "4" => "完成转让");
        //$searchMap['borrow_duration'] = array("all"=>"不限制","1-93"=>"3个月以内","94-186"=>"3-6个月","187-366"=>"6-12个月","367"=>"12个月以上");
        $searchMap['borrow_duration'] = array("all" => "不限制", "0-92" => "3个月以内", "93-184" => "3-6个月", "185-366" => "6-12个月", "366-731" => "12-24个月");
        $searchMap['interest_rate'] = array("all" => "不限制", "0-6" => "6%以下", "6-9" => "6%-9%", "9-12" => "9%-12%", "12-100" => "12%以上");

        $search = array();
        //搜索条件
        foreach ($urlArr as $v) {
            if ($_GET[$v] && $_GET[$v] <> 'all') {
                switch ($v) {
                    case 'interest_rate':
                        $barr = explode("-", text($_GET[$v]));
                        $search["d.interest_rate"] = array("between", $barr);
                        break;
                    case 'status':
                        $search["d." . $v] = intval($_GET[$v]);
                        break;
                    case 'borrow_duration':
                        $barr = explode("-", text($_GET[$v]));
                        if ($barr[1] == 92) {
                            $search["_string"] = "(b.borrow_duration<92 and b.duration_unit!=1) or (b.borrow_duration<3 and b.duration_unit=1)";
                        }
                        if ($barr[1] == 184) {
                            $search["_string"] = "(b.borrow_duration<184 and b.borrow_duration>=92 and b.duration_unit!=1) or (b.borrow_duration<6 and  b.borrow_duration>=3 and b.duration_unit=1)";
                        }
                        if ($barr[1] == 366) {
                            $search["_string"] = "(b.borrow_duration<366  and b.borrow_duration>=184 and b.duration_unit!=1) or (b.borrow_duration<12  and  b.borrow_duration>=6  and b.duration_unit=1)";
                        }
                        if ($barr[1] == 731) {
                            $search["_string"] = "(b.borrow_duration<731 and b.borrow_duration>=366 and b.duration_unit!=1) or (b.borrow_duration<24 and  b.borrow_duration>=123 and b.duration_unit=1)";
                        }
                        break;
                    default:
                        $barr = explode("-", text($_GET[$v]));
                        $search["b." . $v] = array("between", $barr);
                        break;
                }
            }
        }

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
        //导航
        $navigate = get_navigate();

        $this->assign("navigate", $navigate);
        $this->assign("list", $list);
        $this->assign("searchUrl", $searchUrl);
        $this->assign("searchMap", $searchMap);
        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";
        $this->assign("Bconfig", $Bconfig);

        $this->display();
    }

    /*public function buydebt()
    {
        $paypass = strval($_REQUEST['pin']);
        $invest_id = intval($_REQUEST['invest_id']);
        $money = floatval($_REQUEST['money']);

        D("DebtBehavior");
        $Debt = new DebtBehavior($this->uid);

        // 检测是否可以购买  密码是否正确，余额是否充足
        $result = $Debt->buy($paypass, $invest_id, $money);
        $this->success($result);
    }*/

    /**
     * 债权转让详情，代码重构 150423
     */
    public function detail()
    {
        $pre = C('DB_PREFIX');
        $id = intval($_GET['id']);
        $debt = M('debt')->field('*')->where(array('id' => $id))->find(); // 撤销时需要把debt里的数据删除，否则会导致
        D("DebtBehavior");

        $version = FS("Webconfig/version");
        if($version['mobile'] == 1 OR $version['wechat'] == 1) {
            $is_mobile = 1;
            $this->assign('is_mobile',$is_mobile);
        }
        if (!empty($debt['invest_id'])) {

            $debt['need'] = bcsub($debt['money'], $debt['assigned'],2);  //可投金额
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

                    // 投资记录，指购买此债权的记录
                    $where = array(
                        'parent_invest_id' => $debt['invest_id']
                    );
                    $fields = "bi.investor_uid,bi.add_time,bi.source,investor_capital";
                    $invest_record = BorrowInvestorModel::getBorrowInvestByPage($where, $fields, 1, 6);//只取6个
                    if (!empty($invest_record)) {
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
                        $this->assign('invest_record', $invest_record['invest_items']);
                        $this->assign('page', $invest_record['page']);
                    }

                    //当前页面的url
                    $current_url = DOMAIN . $_SERVER['REQUEST_URI'];
$this->assign('unlogin_home', DOMAIN . '/login?redirectUrl=' . rawurlencode(DOMAIN . $_SERVER['REQUEST_URI']));
                    $this->assign('current_url', $current_url);
                    $this->assign('vminfo', $vminfo);
                    $this->assign('borrow_info', $borrow_info);
                    $this->assign('buy_info', $vminfo);
                    $this->assign('sell_name', $sell_uname['user_name']);
                    $this->assign('debt', $debt);
                    $this->assign('debt_duration', $debt_duration);
                    $this->assign('debt_fee', get_global_setting('debt_fee'));
                }

            }
        } else {
            //TODO: 404页面
            $this->redirect(DOMAIN);
        }
        $this->display();
    }

    /**
     * 确认购买
     * 流程： 检测购买条件
     * 购买
     */
    public function investcheck()
    {
        $paypass = strval($_POST['pin']); //支付密码
        $invest_id = intval($_POST['invest_id']);
        $money = floatval($_POST['money']);

        D("DebtBehavior");
        $Debt = new DebtBehavior($this->uid);

        // 检测是否可以购买  密码是否正确，余额是否充足
        $result = $Debt->buy($paypass, $invest_id, $money);

        if ($result === '购买成功') {
            $array = array(
                'status' => 1,
                'message' => '购买成功'
            );
        } else {
            $array = array(
                'status' => 0,
                'message' => $result
            );
        }
        exit(json_encode($array));
    }

    public function ajax_invest()
    {
        if (!$this->uid) {
            ajaxmsg("请先登陆", 0);
        }
        $pre = C('DB_PREFIX');
        $id = intval($_GET['id']);
        $investMoney = floatval($_GET['num']);

        $debt = M('debt')->field("*")->where(array('id' => $id))->find();
        if ($this->uid == $debt['sell_uid']) ajaxmsg("不能购买自己转让的债权", 0);
        if ($debt['status'] <> 2) ajaxmsg("只能投正在转让中的债权", 0);

        $vm = getMinfo($this->uid, 'm.pin_pass,mm.account_money,mm.back_money,mm.money_collect');

        $pin_pass = $vm['pin_pass'];
        $has_pin = (empty($pin_pass)) ? "no" : "yes";

        $borrow_investor = M('borrow_investor')->field('investor_capital,investor_interest')->where(array('id'=>$debt['invest_id']))->find();
        if( !empty($borrow_investor) ) {
            // 到期总回款
            $jingli = bcmul($borrow_investor['investor_interest'],  bcdiv($investMoney, $borrow_investor['investor_capital'], 2 ), 2);
            //TODO 减去利息管理费
            $fee_invest_manage_conf = get_global_setting('fee_invest_manage');
            $jingli = bcsub($jingli, bcdiv($fee_invest_manage_conf*$jingli,100,2), 2);
            $this->assign('jingli', $jingli);
            $this->assign('receive_account', bcadd($jingli, $investMoney, 2));
        }

        $this->assign("has_pin", $has_pin);
        $this->assign("investMoney", $investMoney);
        $this->assign("debt", $debt);
        $this->assign("vm", $vm);
        $need_money = $vm['account_money'] + $vm['back_money'] - $investMoney;
        if ($need_money > 0) {
            $need_money = 0;
        } else {
            $need_money = abs($need_money);
        }
        $this->assign('need_money', $need_money);
        $data['content'] = $this->fetch();
        ajaxmsg($data);
    }

    //`mxl:debtnow`//end
    public function addcomment()
    {

        $data['comment'] = text($_POST['comment']);
        if (!$this->uid) ajaxmsg("请先登陆", 0);
        if (empty($data['comment'])) ajaxmsg("留言内容不能为空", 0);
        $data['type'] = 1;
        $data['add_time'] = time();
        $data['uid'] = $this->uid;
        $data['uname'] = session("u_user_name");
        $data['tid'] = intval($_POST['tid']);
        $data['name'] = M('borrow_info')->getFieldById($data['tid'], 'borrow_name');

        $newid = M('comment')->add($data);
        //$this->display("Public:_footer");
        if ($newid) ajaxmsg();
        else ajaxmsg("留言失败，请重试", 0);
    }

}

?>

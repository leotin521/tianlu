<?php

// 本类是定投宝前台处理类
class FundAction extends HCommonAction
{
    public function index()
    {
        static $newpars;
        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";
        $per = C('DB_PREFIX');
        $vo1 = M('members')->field('id,user_name,user_email,user_pass,is_ban')->where("id={$this->uid}")->find();
        if ($vo1['is_ban'] == 1 || $vo1['is_ban'] == 2) $this->error("您的帐户已被冻结，请联系客服处理！", __APP__ . "/index.html");
        //排序
        $where = array(
            'b.borrow_type' => BorrowModel::BID_CONFIG_TYPE_FINANCIAL,
            'b.borrow_status' => array('in', '-1,2,4,6,7'),
            'b.on_off' => 1
        );
        $fields = 'b.borrow_type,b.duration_unit,b.borrow_times,b.id,b.borrow_name,b.borrow_status,b.borrow_uid,b.borrow_duration,b.borrow_money,b.borrow_fee,b.borrow_interest_rate,b.repayment_type,b.has_borrow,b.add_time,m.user_name,b.borrow_max,b.is_tuijian,b.can_auto,b.is_xinshou,b.is_taste';
        $page = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $order = "b.borrow_status=7,b.borrow_status=6,b.borrow_status=4,b.borrow_status=-1,b.borrow_status=2,b.id DESC";
        $list = TborrowModel::getTborrowByPage($where, $fields, $page, false, $order);
        //加入用户数
        $invest_num = M('borrow_investor')->where("borrow_type = 7")->group("investor_uid")->select();
        //累计总金额
        $invest_total = M('borrow_investor')->where("borrow_type = 7")->sum("investor_capital");
        $invest_interest = M('borrow_investor')->where("borrow_type = 7")->sum("investor_interest");
        $this->assign("list", $list);
        $this->assign("invest_num", count($invest_num));
        $this->assign("invest_total", $invest_total);
        $this->assign("invest_interest", $invest_interest);
        $navigate = get_navigate();
        $this->assign("navigate", $navigate);
        $this->display();
    }

    public function reward()
    {
        $borrow_id = intval($_GET['id']);
        $this->assign('borrow_id', $borrow_id);

        $expconf = FS("Webconfig/expconf");
        $this->assign('expconf', $expconf);
        //投标特殊奖励
        $special_award = ExpandMoneyModel::get_special_award($borrow_id);
        $this->assign('special_award', $special_award);
        $pre = C('DB_PREFIX');
        ///一马当先
        $fields = "sum(e.money) as reward_yi,i.investor_capital  as investor_capital,m.user_name,e.add_time";
        $yima_history = M('expand_money e')
            ->join("{$pre}members m ON m.id= e.uid")->join("{$pre}borrow_investor i ON i.id=e.invest_id")
            ->field($fields)->where('e.type=5')->group('e.borrow_id')->order('i.add_time desc')->limit('7')->select();
        $yichui_left = M('borrow_info')->where('id=' . $borrow_id)->find();
        $this->assign('yichui_left', $yichui_left);
        $this->assign("yima_history", $yima_history);
        ///一锤定音
        $yichui_history = M('expand_money e')
            ->join("{$pre}members m ON m.id= e.uid")->join("{$pre}borrow_investor i ON i.id=e.invest_id")
            ->field($fields)->where('e.type=6')->group('e.borrow_id')->order('i.add_time desc')->limit('7')->select();
        $this->assign("yichui_history", $yichui_history);

        ///一鸣惊人
        $yiming_history = M('expand_money e')
            ->join("{$pre}members m ON m.id= e.uid")->join("{$pre}borrow_investor i ON i.id=e.invest_id")
            ->field($fields)->where('e.type=7')->group('e.borrow_id')->order('i.add_time desc')->limit('7')->select();

        $this->assign("yiming_history", $yiming_history);
        $this->display();
    }

    public function tdetail()
    {
        $pre = C('DB_PREFIX');
        $id = intval($_GET['id']);
        $this->assign("borrow_id", $id);
        //合同ID
        if ($this->uid) {
            $invs = M('borrow_investor')->field('id')->where("borrow_id={$id} AND (investor_uid={$this->uid} OR borrow_uid={$this->uid})")->find();
            if ($invs['id'] > 0) $invsx = $invs['id'];
            elseif (!is_array($invs)) $invsx = 'no';
        } else {
            $invsx = 'login';
        }
        $this->assign('unlogin_home', DOMAIN . '/login?redirectUrl=' . rawurlencode(DOMAIN . $_SERVER['REQUEST_URI']));
        $this->assign("invid", $invsx);
        //合同ID

        //帐户资金情况
        $this->assign("investInfo", getMinfo($this->uid, true));
        //帐户资金情况
        $borrowinfo = TborrowModel::get_borrow_info($id);
        if (!empty($borrowinfo)) {
            $article = M('article')->field('title,id as aid')->where('id=' . $borrowinfo['danbao'])->find();
            if (!empty($article)) {
                $borrowinfo['title'] = $article['title'];
                $borrowinfo['aid'] = $article['aid'];
            }
        }
        //上线时间或剩余募集期时间
        if( $borrowinfo['borrow_status'] == BorrowModel::BID_SINGLE_CONFIG_STATUS_WAIT_ONLINE ) {
            $borrowinfo['lefttime'] =strtotime($borrowinfo['online_time']) - time();
            $borrowinfo['add_time'] = strtotime($borrowinfo['online_time']);//开始时间为上线时间
        }else{
            $borrowinfo['lefttime'] =$borrowinfo['collect_time'] - time();
        }

        if ($borrowinfo['borrow_type'] != 7) {
            $this->error("非法操作");
        }
        $borrowinfo['progress'] = getfloatvalue($borrowinfo['has_borrow'] / $borrowinfo['borrow_money'] * 100, 2);
        $borrowinfo['need'] = $borrowinfo['borrow_money'] - $borrowinfo['has_borrow'];
        $borrowinfo['updata'] = unserialize($borrowinfo['updata']);
        if ($borrowinfo['danbao'] != 0) {
            $danbao = M('article')->field('id,title')->where("type_id=7 and id={$borrowinfo['danbao']}")->find();
            $borrowinfo['danbao'] = $danbao['title'];//担保机构
            $borrowinfo['danbaoid'] = $danbao['id'];
        } else {
            $borrowinfo['danbao'] = '暂无担保机构';//担保机构
        }
        $borrowinfo['restday'] = ceil(($borrowinfo['deadline'] - time()) / (24 * 60 * 60));
        $borrowinfo['currentday'] = time();
        $now = time();
        $borrowinfo['aa'] = floor($borrowinfo['collect_day'] - $now);
        $borrowinfo['leftday'] = ceil(($borrowinfo['collect_day'] - $now) / 3600 / 24);
        $borrowinfo['leftdays'] = floor(($borrowinfo['collect_day'] - $now) / 3600 / 24) . '天以上';
        $this->assign("vo", $borrowinfo);

        $fieldx = "bi.investor_capital,bi.transfer_duration,bi.transfer_num,bi.add_time,m.user_name,bi.is_auto,bi.final_interest_rate";
        $investinfo = M("borrow_investor bi")->field($fieldx)->join("{$pre}members m ON bi.investor_uid = m.id")->where("bi.borrow_id={$id}")->order("bi.id DESC")->select();
        $this->assign("investinfo", $investinfo);
        $investnum = M("borrow_investor")->where("borrow_id={$id}")->count("id");
        $this->assign("investnum", $investnum);
        //利息复投收益率
        $monthData['month_times'] = 12;
        $monthData['account'] = 100000;
        $monthData['year_apr'] = $borrowinfo['borrow_interest_rate'];
        $monthData['type'] = "all";
        $repay_detail = CompoundMonth($monthData);   //利息复投利率计算
        $this->assign("Compound", $repay_detail['shouyi']);
        //利息复投收益率
        //投标特殊奖励
        $special_award = ExpandMoneyModel::get_special_award($id);
        $this->assign('special_award', $special_award);
         $this->assign('unlogin_home', DOMAIN . '/login?redirectUrl=' . rawurlencode(DOMAIN . $_SERVER['REQUEST_URI']));
        // 投资记录
        $version = FS("Webconfig/version");
        if($version['mobile'] == 1 OR $version['wechat'] == 1) {
            $is_mobile = 1;
            $this->assign('is_mobile',$is_mobile);
        }
        $this->investRecord($id);
        $this->display();
    }

    public function investcheck()
    {
        if (1 > $this->uid) {
            ajaxmsg("请先登录", 0);
        }
        $re = chkInvest(intval($_POST['borrow_id']), $this->uid, intval($_POST['money']), $msg, text($_POST['pin']), text($_POST['borrow_pass']));
        ajaxmsg($msg, $re);
    }

    public function investmoney()
    {
        if (!$this->uid) {
            exit();
        }
        $borrow_id = intval($_POST['T_borrow_id']);
        $tnum = intval($_POST['transfer_invest_num']);
        $repayment_type = intval($_POST['chooseWay']);
        $coupon_ids = filter_array($_POST['coupon']);
        $discount_money = 0;
        if(intval($_POST['chooseWay'])==4){
            $invest_repayment_type = 1;
        }elseif (intval($_POST['chooseWay'])==6){
            $invest_repayment_type = 2;
        }
        //判断是不是体验标
        $is_taste = M("borrow_info")->getFieldById($borrow_id, "is_taste");
        // 如果使用优惠券
        if (!empty($coupon_ids)) {
            $expand_money = ExpandMoneyModel::get_discount_money($coupon_ids, $tnum, $this->uid,$is_taste);
            if ($expand_money === false) {
                $this->error('非法请求');
            } else {
                $discount_money = $expand_money['discount_money'];
            }
        }
        if( $tnum <= $discount_money ) {
            $this->error("使用优惠券总额不能高于（或等于）投资金额");
        }
        // 实际需要支付的金额 = 投资金额 - 使用优惠券的金额
        $actual_money = $tnum - $discount_money;
        if ($actual_money < 0) $actual_money = 0;

        $m = M("member_money")->field('uid,account_money,back_money,money_collect')->find($this->uid);
        $binfo = TborrowModel::get_borrow_info($borrow_id);
        $re = chkTwoInvest($m, $binfo, $tnum, 1, $repayment_type);

        if ($re === TRUE) {
            $month = TborrowModel::get_remain_transfer_days($borrow_id);
            //$invest_repayment_type;
            $done = TinvestMoney($this->uid, $borrow_id, $tnum, $month, 0, $repayment_type, 1, $coupon_ids, $invest_repayment_type);//投金额投标
            if ($done === true) {
                $arr['status'] = 4;
                $arr['loanno'] = $borrow_id;
                $arr['use_time'] = time();
                M('expand_money')->where(array('id' => array('in', $coupon_ids), 'uid' => $this->uid))->save($arr);
                $this->success("恭喜成功认购{$tnum}元,优惠券抵押{$discount_money}元，实际付款{$actual_money}元");
            } else if ($done) {
                $this->error($done);
            } else {
                $this->error("对不起，认购失败，请重试!");
            }
        } else {
            $this->error($re);
        }
    }


    public function ajax_invest()
    {
        if (1 > $this->uid) {
            ajaxmsg("请先登录", 0);
        }
        $id = intval($_GET['id']);
        $num = intval($_GET['num']);
        $chooseWay = intval($_GET['chooseWay']);
        $this->assign("chooseway", $chooseWay);
        $binfo = TborrowModel::get_borrow_info($id);
        if ($binfo['is_xinshou']==1) {
            $binvest = BorrowInvestorModel::get_is_novice($this->uid);
            if ($binvest==false){
                ajaxmsg("当前标为新手专享标，只有新手才可以投", 0);
            }
        }
        if ($num < $binfo['per_transfer']) {
            exit(json_encode(array('message' => '小于起投金额，请重新输入', 'status' => 0, 'money' => $binfo['per_transfer'])));
        }
        if ($num > ($binfo['borrow_money'] - $binfo['has_borrow'])) {
            exit(json_encode(array('message' => '超出可投金额，请重新输入', 'status' => 0, 'money' => $binfo['borrow_money'] - $binfo['has_borrow'])));
        }
        if (($num % $binfo['per_transfer']) > 0) {
            exit(json_encode(array('message' => '必须是起投金额的整数倍', 'status' => 0, 'money' => $binfo['per_transfer'])));
        }
        if ($num > $binfo['borrow_money']) {
            exit(json_encode(array('message' => '超出限投金额，请重新输入', 'status' => 0, 'money' => $binfo['borrow_money'])));
        }
        $binfo['uname'] = M("members")->getFieldById($binfo['borrow_uid'], "user_name");
        $binfo['need_num'] = ($binfo['borrow_money'] - $binfo['has_borrow']);
        $minfo = getMinfo($this->uid, "m.pin_pass, mm.account_money, mm.back_money, mm.money_collect");
        if ($this->uid == $binfo['borrow_uid']) {
            ajaxmsg("不能去投自己的标", 0);
        }
        if ($binfo['borrow_status'] <> 2) {
            ajaxmsg("只能投正在借款中的标", 0);
        }
        $pin_pass = $minfo['pin_pass'];
        $has_pin = (empty($pin_pass) === true) ? "no" : "yes";
        $this->assign("has_pin", $has_pin);
        //  $this->assign("investMoney",$num);
        $this->assign("account_money", $minfo['account_money'] + $minfo['back_money']);
        $this->assign("vo", $binfo);
        $this->assign("num", $num);
        
        //判断是否是体验标（体验标只能使用体验券，体验券只能用于体验标）
        if ($binfo['is_taste']==1){
            $expand_where = " uid=" . $this->uid . " and is_taste=1 and status=1 and expired_time > " . time();
        }else{
            $expand_where = " uid=" . $this->uid . " and is_taste=0 and status=1 and expired_time > " . time();
        }
        //优惠券
        $expand_list = M('expand_money')
            ->field('id, money, invest_money, expired_time, type, use_time, remark, is_taste')
            ->where($expand_where)
            //->limit('10')
            ->order("money desc")
            ->select();

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
        $list = array_slice($list_merge, 0, 3);
        $list = ExpandMoneyModel::get_coupon_type_format($list);
        $this->assign('expand_list', $list);

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
        $list_list = array_slice($list_list_merge, 0, 3);
        $list_list = ExpandMoneyModel::get_coupon_type_format($list_list);

        $this->assign('expand_expired_list', $list_list);

        // 到期总回款
        $borrowinfo = TborrowModel::get_format_borrow_info($id, "b.*,  bwd.*, bd.bianhao");
        $borrowInterest = getBorrowInterest($chooseWay, $num, $borrowinfo['borrow_duration'], $borrowinfo['borrow_interest_rate'], $borrowinfo['duration_unit']);
        $this->assign('jingli', $borrowInterest);


        $this->assign('receive_account', getFloatValue($num + $borrowInterest, 2));

        $this->assign("has_pin", $has_pin);
        $this->assign("account_money", $minfo['account_money'] + $minfo['back_money']);
        $this->assign("vo", $binfo);
        $this->assign("investMoney", $num);
        $need_money = $minfo['account_money'] + $minfo['back_money'] - $num;
        if ($need_money > 0) {
            $need_money = 0;
        } else {
            $need_money = abs($need_money);
        }
        $this->assign('need_money', $need_money);
        $data['content'] = $this->fetch();
        ajaxmsg($data);
    }

    public function investRecord($borrow_id = 0)
    {
        isset($_GET['borrow_id']) && $borrow_id = intval($_GET['borrow_id']);
        $Page = D('Page');
        import("ORG.Util.Page");
        $count = M("borrow_investor")->where('borrow_id=' . $borrow_id)->count('id');
        $Page = new Page($count, 6);
        $show = $Page->ajax_show();
        $this->assign('page', $show);
        $version = FS("Webconfig/version");
        if($version['mobile'] == 1 OR $version['wechat'] == 1) {
            $is_mobile = 1;
        }
        if ($_GET['borrow_id']) {
            $pre = C('DB_PREFIX');
            $list = M("borrow_investor as i")
                ->join("{$pre}members as m on i.investor_uid = m.id")
                ->join("{$pre}borrow_info as b on  b.id = i.borrow_id")
                ->field('b.borrow_interest_rate, i.invest_repayment_type, i.add_time, i.investor_capital, b.borrow_min as per_transfer, b.borrow_duration, i.source, i.is_auto, m.user_name')
                ->where('b.id=' . $borrow_id)->order('i.id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
            $string = '';
            foreach ($list as $k => $v) {
                $source = BorrowInvestorModel::get_invest_source($v['source']);
                if ($v['invest_repayment_type']==2){
                    //利息复投收益率
                    $monthData['month_times'] = 12;
                    $monthData['account'] = 100000;
                    $monthData['year_apr'] = $v['borrow_interest_rate'];
                    $monthData['type'] = "all";
                    $repay_detail = CompoundMonth($monthData);   //利息复投利率计算
                    $v['borrow_interest_rate'] = $repay_detail['shouyi'];
                    //利息复投收益率
                }
                if ($v['is_auto'] == 1) {
                    $d = "自动";
                } else {
                    $d = "手动";
                }
                if ($k % 2 != 0) {
                    $string .= "<tr calss='ad_con_text_bg'>";
                } else {
                    $string .= "<tr>";
                }
                $string .= "<td>" . hidecard($v['user_name'], 4) . "</td>
                                                      <td>" . $v['borrow_interest_rate'] . "%</td>
                                                      <td>" . date('Y-m-d H:i:s', $v['add_time']) . "</td>
                                                      <td>" . Fmoney($v['investor_capital']) . "元</td>
                                                       <td>" . $d . "</td>";
                                                        if($is_mobile){
                                                            $string .="<td>".$source ."</td></tr>";
                                                        }
            }
            echo empty($string) ? '<tr><td colspan=7>暂时没有投资记录</td></tr>' : $string;
        }
    }
}

?>
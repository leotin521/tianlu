<?php

// 本类由系统自动生成，仅供测试用途
class BorrowsAction extends MCommonAction
{
    public function index()
    {
        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";
        $this->assign("borrow_type", BorrowModel::get_borrow_type());
        $this->assign("repay_type", $Bconfig['REPAYMENT_TYPE']);
        $this->assign("cur_status", $Bconfig['BORROW_STATUS_SHOW']);
        $this->display();
    }

    public function invPage()
    {
        $bid = intval($_GET['bid']);
        $binfo = M("borrow_info")->field("id, borrow_name, borrow_type, add_time, borrow_money, has_borrow, borrow_times")->where("id = {$bid}")->find();
        $binfo["url"] = getBorrowUrl($binfo['borrow_type'], $bid);
        $this->assign("binfo", $binfo);
        $this->display();
    }

    public function payPage()
    {
        $bid = intval($_GET['bid']);
        $uid = $this->uid;
        $binfo = M("borrow_info")->field("id, borrow_name, borrow_type, add_time, borrow_money, has_borrow, borrow_times")->where("id = {$bid} and borrow_uid={$uid}")->find();
        $binfo["url"] = getBorrowUrl($binfo['borrow_type'], $bid);
        $this->assign("binfo", $binfo);
        $this->display();
    }

    public function calendar()
    {
        $minfo = M("investor_detail")->field("min(FROM_UNIXTIME(deadline, '%Y')) as min, max(FROM_UNIXTIME(deadline, '%Y')) as max")->where("borrow_uid = {$this->uid}")->group("borrow_uid")->find();
        $year = array();
        for ($i = $minfo['min']; $minfo['max'] >= $i; $i++) {
            $year[] = $i;
        }
        if (is_array($minfo) === false) {
            $year[0] = date("Y");
        }
        $month = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12");
        $this->assign("year", $year);
        $this->assign("month", $month);
        $this->assign("cur_year", date("Y"));
        $this->assign("cur_month", date("n"));
        $this->display();
    }

    public function summa()
    {
        if (isset($_GET['status'])) {
            $this->assign("status", $_GET['status']);
        }
        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";
        $this->assign("repay_type", $Bconfig['REPAYMENT_TYPE']);
        $this->display();
    }

    public function credit()
    {
        $Binfo = require C("APP_ROOT") . "Conf/borrow_config.php";

        $size = 10;
        $map['uid'] = $this->uid;

        //分页处理
        import("ORG.Util.Page");
        $count = M('member_apply')->where($map)->count('id');
        $p = new Page($count, $size);
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $status_arr = array('待审核', '审核通过', '审核未通过');
        $list = M('member_apply')->where($map)->order('id DESC')->limit($Lsql)->select();
        foreach ($list as $key => $v) {
            $list[$key]['status'] = $status_arr[$v['apply_status']];
        }

        $this->assign("aType", $Binfo['APPLY_TYPE']);
        $this->assign("list", $list);
        $this->assign("pagebar", $page);

        $json['html'] = $this->fetch();
        $this->display();
    }

    public function apply()
    {
        if (!M('member_apply')->autoCheckToken($_POST)) {
            $this->error('非法请求');
        }
        $xtime = strtotime("-1 month");
        $vo = M('member_apply')->field('apply_status')->where("uid={$this->uid}")->order("id DESC")->find();
        $xcount = M('member_apply')->field('add_time')->where("uid={$this->uid} AND add_time>{$xtime}")->order("id DESC")->find();
        if (is_array($vo) && $vo['apply_status'] == 0) {
            $xs = "您的申请正在审核，请等待此次审核结束再提交新的申请！";
            ajaxmsg($xs, 0);
        } elseif (is_array($xcount)) {
            $timex = date("Y-m-d", $xcount['add_time']);
            $xs = "一个月内只能进行一次额度申请，您已在{$timex}申请过了，如急需额度，请联系客服！";
            ajaxmsg($xs, 0);
        } else {
            $apply['uid'] = $this->uid;
            $apply['apply_type'] = intval($_POST['apply_type']);
            $apply['apply_money'] = floatval($_POST['apply_money']);
            $apply['apply_info'] = text($_POST['apply_info']);
            $apply['add_time'] = time();
            $apply['apply_status'] = 0;
            $apply['add_ip'] = get_client_ip();
            $nid = M('member_apply')->add($apply);
        }
        if ($nid) ajaxmsg('申请已提交，请等待审核！');
        else ajaxmsg('申请提交失败，请重试！', 0);
    }

    // 信用积分
    public function creditRank()
    {
        $members = M("members")->field('credits')->where("id=" . $this->uid)->find();
        $members['credit_rand'] = getLeveIco($members['credits'], 2);

        // 积分记录
        $where = array('uid' => $this->uid);
        $list = MemberCreditsLogModel::getCreditsLogByPage($where);

        // 额度信息
        $member_money = M('member_money')->field('credit_limit,credit_cuse')->where($where)->find();
        if (!empty($member_money)) {
            $member_money['used_credit'] = $member_money['credit_cuse'] - $member_money['credit_limit'];
        }
        $this->assign('list', $list);
        $this->assign('members', $members);
        $this->assign('member_money', $member_money);
        $this->display();
    }

    //借款列表 `mxl 20150318`
    public function borrowList()
    {
        $pre = C('DB_PREFIX');
        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";
        $brtype = array_keys(BorrowModel::get_borrow_type());
        $status = array_keys($Bconfig['BORROW_STATUS']);
        $rptype = array_keys($Bconfig['REPAYMENT_TYPE']);
        $perpage = $_POST['perpage'];
        $curpage = $_POST['curpage'];

        //条件查询
        $map = array();
        $map['b.borrow_uid'] = $this->uid;
        $cur_rate = intval($_POST['currate']);
        if ($cur_rate === 1 || $cur_rate === 2) {
            $map['b.rate_type'] = $cur_rate;
        }
        if (empty($_POST['brtype']) === false) {
            $map['b.borrow_type'] = $brtype[intval($_POST['brtype']) - 1];
        }
        if (empty($_POST['status']) === false) {
            $map['b.borrow_status'] = $status[intval($_POST['status']) - 1];
        }
        if (empty($_POST['rptype']) === false) {
            $map['b.repayment_type'] = $rptype[intval($_POST['rptype']) - 1];
        }
        if (empty($_POST['add_time']) === false) {
            $tmp = getTimeArray(text($_POST['add_time']));
            if (is_array($tmp) === true) {
                $map['b.add_time'] = $tmp;
            }
        }
        if (empty($_POST['deadline']) === false) {
            $tmp = getTimeArray(text($_POST['deadline']));
            if (is_array($tmp) === true) {
                $map['d.deadline'] = $tmp;
            }
        }

        //排序
        $order = "d.deadline > 0 DESC, d.deadline ASC, b.id DESC";
        if (empty($_POST['sort']) === false) {
            $sort = explode(",", text($_POST['sort']));
            if (count($sort) === 2) {
                switch ($sort[0]) {
                    case "time":
                        $order = "d.deadline > 0 DESC, pay_status DESC, d.deadline {$sort[1]}, b.id DESC";
                        break;
                    case "type":
                        $order = "b.borrow_type {$sort[1]}, b.id DESC";
                        break;
                    case "money":
                        $order = "b.borrow_money {$sort[1]}, b.id DESC";
                        break;
                    case "status":
                        $order = "b.borrow_status {$sort[1]}, b.id DESC";
                        break;
                }
            }
        }

        $count = M("borrow_info b")->join("{$pre}investor_detail d ON b.id = d.borrow_id")->where($map)->count("distinct b.id");
        $limit = calPage($count, $curpage, $perpage);
        $field = "b.id as bid, b.borrow_name, b.borrow_type, b.borrow_status, b.add_time, b.borrow_money, d.deadline, max(d.status in (6,7)) as pay_status, d.status as d_status, i.add_time as inv_time";
        $re = M("borrow_info b")->field($field)->where($map)->join("{$pre}investor_detail d ON b.id = d.borrow_id")->join("{$pre}borrow_investor i ON b.id = i.borrow_id")->order($order)->group("bid")->limit($limit)->select();
        foreach ($re as $k => $v) {
            $re[$k]['status'] = $Bconfig['BORROW_STATUS_SHOW'][$v['borrow_status']];
            $re[$k]['type'] = BorrowModel::get_borrow_type($v['borrow_type']);
            $re[$k]['borrow_url'] = getBorrowUrl($v['borrow_type'], $v['bid']);
        }
        $json['data']['list'] = $re;
        $json['data']['perpage'] = $perpage;
        $json['data']['curpage'] = $curpage;
        $json['data']['allpage'] = $count;
        $json['data']['length'] = (is_array($re) === false) ? 0 : count($re);
        $json['data']['now'] = time();
        $json['data']['near'] = strtotime("+3 day");
        $json['code'] = 0;
        echo json_encode($json);
        exit;
    }

    //投资列表 `mxl 20150320`
    public function invList()
    {
        $pre = C('DB_PREFIX');
        $bid = intval($_GET['bid']);
        $perpage = $_GET['perpage'];
        $curpage = $_GET['curpage'];
        $map['i.borrow_id'] = $bid;
        $count = M("borrow_investor i")->where($map)->count("i.id");
        $limit = calPage($count, $curpage, $perpage);
        $field = "i.investor_uid, m.user_name, i.investor_capital, i.investor_interest, i.add_time, i.transfer_duration, i.is_auto, i.reward_money";
        $re = M("borrow_investor i")->field($field)->where($map)->join("{$pre}members m ON i.investor_uid = m.id")->order("i.id DESC")->limit($limit)->select();
        $json['data']['bid'] = $bid;
        $json['data']['list'] = $re;
        $json['data']['perpage'] = $perpage;
        $json['data']['curpage'] = $curpage;
        $json['data']['allpage'] = $count;
        $json['data']['length'] = (is_array($re) === false) ? 0 : count($re);
        $json['code'] = 0;
        echo json_encode($json);
        exit;
    }

    //还款列表 `mxl 20150321`
    public function payList()
    {
        $pre = C('DB_PREFIX');
        $bid = intval($_GET['bid']);
        $filter = $_GET['filter'];
        if ($filter == '14') {
            $map['d.status'] = array("exp", " <> 14");
        } else {
            $map['bi.parent_invest_id'] = 0;
        }
        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";
        $perpage = $_GET['perpage'];
        $curpage = $_GET['curpage'];
        $map['d.borrow_id'] = $bid;
        $map['d.borrow_uid'] = $this->uid;

        if (empty($_GET['onlypay']) === false) { //只显示未还的记录
            $map['d.status'] = array("in", "4,6,7");
        }
        if (empty($_GET['onlyone']) === false) { //只显示最早一期未还的记录
            $map['d.status'] = array("in", "4,6,7");
            $map['d.sort_order'] = M("investor_detail d")->where($map)->order("d.sort_order ASC")->getField("d.sort_order");
        }
        $count = M("investor_detail d")
            ->join("{$pre}borrow_investor bi on bi.id=d.invest_id")
            ->where($map)
            ->count("distinct d.sort_order");
        $limit = calPage($count, $curpage, $perpage);
        $field = "d.deadline, d.repayment_time, sum(d.capital) as capital, sum(d.interest) as interest, d.status, sum(d.substitute_money) as substitute_money, d.sort_order,b.has_pay";
        $dinfo = M("investor_detail d")->field($field)
            ->join("{$pre}borrow_investor bi on bi.id=d.invest_id")
            ->join("{$pre}borrow_info b on b.id = d.borrow_id")
            ->where($map)->order("d.sort_order asc,d.status ASC")->group("d.sort_order")->limit($limit)->select();
        //查询已回款金额
        $receive_detail = InvestorDetailModel::get_has_receive($bid);
        if (!empty($receive_detail)) {
            foreach ($receive_detail as $value) {
                foreach ($dinfo as $key => $val) {
                    if ($value['sort_order'] == $val['sort_order']) {
                        $dinfo[$key]['receive'] = $value['receive'];
                        break;
                    }
                }
            }
        }

        // 如果has_pay小于当期，则当期出现"还款"接口还款
        if (!empty($dinfo)) {
            for ($i = 0; $i < count($dinfo); $i++) {
                if ($dinfo[$i]['has_pay'] < $dinfo[$i]['sort_order']) {
                    $dinfo[$i]['need_pay'] = 1; // 需要还款
                }else{
                    //当有债权转让的时候，且不需要还款时，在已还清里查询应该为已还款，状态从14改成1
                    if( $dinfo[$i]['status'] == 14 ) {
                        $dinfo[$i]['status'] = 1;
                    }
                }
            }
            // 查看取出来的状态是不是都是14，如果都是14，则重新查询出不带14的数据，正确情况下是不会出现都是14的。如果都是14，说明数据量非常小，也无太多消耗。
            $status_all = array_unique(only_array($dinfo, 'status'));
            if (count($status_all) == 1 && $status_all[0] == 14 && $filter != 14) {
                // 通过$filter再判断，防止出现死循环
//                header("Location: " . DOMAIN . $_SERVER['REQUEST_URI'] . 'filter=14');
            }
        }

        $re = calExpired($dinfo);

        $json['data']['bid'] = $bid;
        $json['data']['list'] = $re;
        $json['data']['perpage'] = $perpage;
        $json['data']['curpage'] = $curpage;
        $json['data']['allpage'] = $count;
        $json['data']['length'] = (is_array($re) === false) ? 0 : count($re);
        $json['data']['list_status'] = $Bconfig['DETAIL_STATUS'];
        $json['code'] = 0;
        echo json_encode($json);
        exit;
    }

    //还款
    public function doPay()
    {
        $ERROR = 0;
        $UPTXT = 1;
        $ALARM = 2;//报错,更新html,提示窗
        $bid = intval($_GET['bid']);
        $sort = intval($_GET['sort']);
        $re = borrowRepayment($bid, $sort);
        if ($re === true) {
            $json['code'] = $UPTXT;
            $json['data']['msg'] = "还款成功";
        } else if ($re === false) {
            $json['code'] = $ERROR;
            $json['data']['msg'] = "还款失败，请联系客服！";
        } else {
            $json['code'] = $ALARM;
            $json['data']['msg'] = $re;
        }
        echo json_encode($json);
        exit;
    }

    //撤销
    public function doErase()
    {
        $designer = FS("Webconfig/designer");
        $ERROR = 0;
        $UPTXT = 1;
        $ALARM = 2;//报错,更新html,提示窗
        $bid = intval($_GET['bid']);
        $binfo = M("borrow_info")->field("has_borrow, borrow_status, borrow_type")->where("id = {$bid} and borrow_uid=" . $this->uid)->find();
        if ($binfo['borrow_type'] == 6 || $binfo['borrow_type'] == 7) {
            $json['code'] = $ERROR;
            $json['data']['msg'] = $designer[6] . "和" . $designer[7] . "不能进行撤销操作";
            echo json_encode($json);
            exit;
        }
        if (empty($binfo) === false && is_array($binfo) === true) {
            if (canErase($binfo) === true) {
                if (getBorrowType($binfo['borrow_type']) === "man") {
                    M("borrow_info")->where("id = {$bid}")->delete();
                } else {
                    M("borrow_info")->where("id = {$bid}")->delete();
                    M("borrow_detail")->where("borrow_id = {$bid}")->delete();
                }
                $json['code'] = $UPTXT;
                $json['data']['msg'] = "完成{$bid}号借款标撤销操作";
            } else {
                $json['code'] = $ERROR;
                $json['data']['msg'] = "{$bid}号借款标无法撤销，已通过初审（散标）或已有会员投资此项目，请重新检查";
            }
        } else {
            $json['code'] = $ERROR;
            $json['data']['msg'] = "{$bid}号借款标不存在";
        }
        echo json_encode($json);
        exit;
    }

    //申请额度列表
    public function applyList()
    {
        $data_status = C('DATA_STATUS');
        $perpage = $_GET['perpage'];
        $curpage = $_GET['curpage'];
        $map['ap.uid'] = $this->uid;
        $count = M("member_apply ap")->where($map)->count("ap.id");
        $limit = calPage($count, $curpage, $perpage);
        $field = "ap.*";
        $re = M("member_apply ap")->field($field)->where($map)->order("ap.id DESC")->limit($limit)->select();
        $json['data']['list'] = $re;
        $json['data']['perpage'] = $perpage;
        $json['data']['curpage'] = $curpage;
        $json['data']['allpage'] = $count;
        $json['data']['length'] = (is_array($re) === false) ? 0 : count($re);
        $json['data']['status'] = $Bconfig['DATA_STATUS'];
        $json['data']['aptype'] = $Bconfig['APPLY_TYPE'];
        $json['code'] = 0;
        echo json_encode($json);
        exit;
    }

    //还款计划
    public function calList()
    {
        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";
        $pre = C('DB_PREFIX');
        $year = intval($_GET['year']);
        $month = intval($_GET['month']);
        $perpage = intval($_GET['perpage']);
        $curpage = intval($_GET['curpage']);
        $limit = calMonth($year, $month);
        $map['b.id'] = array("gt", "0");
        $map['d.borrow_uid'] = $this->uid;
        $map['bi.parent_invest_id'] = 0;
        $map['d.deadline'] = array("between", $limit);
        $group = "d.borrow_id, d.sort_order";
        $tmp = M("investor_detail d")->field("d.id")->where($map)->join("{$pre}borrow_info b ON d.borrow_id = b.id")->group($group)->select();
        $count = count($tmp);
        $limit = calPage($count, $curpage, $perpage);
        $field = "b.id as bid, b.borrow_name, b.borrow_type, d.deadline, d.repayment_time, b.borrow_status,d.status, d.sort_order, d.total, sum(d.capital) as capital, sum(d.interest) as interest, d.sort_order, b.has_pay";
        $dinfo = M("investor_detail d")->field($field)->where($map)
            ->join("{$pre}borrow_info b ON d.borrow_id = b.id")
            ->join("{$pre}borrow_investor bi ON bi.id = d.invest_id")
            ->order("d.borrow_id ASC")->limit($limit)->group($group)->select();
        if (!empty($dinfo)) {
            for ($i = 0; $i < count($dinfo); $i++) {
                if ($dinfo[$i]['has_pay'] < $dinfo[$i]['sort_order']) {
                    $dinfo[$i]['need_pay'] = 1; // 需要还款
                }
            }
            // 查看取出来的状态是不是都是14，如果都是14，则重新查询出不带14的数据，正确情况下是不会出现都是14的。如果都是14，说明数据量非常小，也无太多消耗。
            $status_all = array_unique(only_array($dinfo, 'status'));
            $re = calExpired($dinfo);

            $borrow_ids = only_array($dinfo, 'bid');
            //修正实际还款时间数据
            $where_repayment['borrow_id'] = array("in", $borrow_ids);
            $where_repayment['repayment_time'] = array("gt", 0);
            $field = "repayment_time,borrow_id";
            $borrow_detail = M('investor_detail')->field($field)->where($where_repayment)->group('borrow_id')->select();
        }
        foreach ($re as $k => $v) {
            $re[$k]['borrow_url'] = getBorrowUrl($v['borrow_type'], $v['bid']);
            $re[$k]['money'] = $v['capital'] + $v['interest'];
        }
        for ($i = 0; $i < count($re); $i++) {
            if (!empty($borrow_detail)) {
                foreach ($borrow_detail as $val) {
                    if ($re[$i]['bid'] == $val['borrow_id']) {
                        $re[$i]['repayment_time'] = $val['repayment_time'];
                        break;
                    }
                }
            }
        }
        $topay = M("investor_detail d")
            ->join("{$pre}borrow_info b ON d.borrow_id = b.id")
            ->join("{$pre}borrow_investor bi ON bi.id = d.invest_id")
            ->where($map)->sum("d.capital + d.interest");
        $map['d.status'] = array("in", "4,6,7,14");
        $json['data']['list'] = $re;
        $json['data']['topay'] = $topay;
        $json['data']['perpage'] = $perpage;
        $json['data']['curpage'] = $curpage;
        $json['data']['allpage'] = $count;
        $json['data']['length'] = (is_array($re) === false) ? 0 : count($re);
        $json['data']['list_status'] = $Bconfig['DETAIL_STATUS'];
        $json['data']['borrow_type'] = BorrowModel::get_borrow_type();
        $json['code'] = 0;
        exit(json_encode($json));
    }

    //借款总表
    public function summaList()
    {
        $now = time();
        $map = array();
        $order = "b.id DESC";
        $pre = C('DB_PREFIX');
        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";
        $rptype = array_keys($Bconfig['REPAYMENT_TYPE']);
        $typename = array("1" => "ing", "2" => "pay", "3" => "late", "4" => "fail", "5" => "done");
        $type = intval($_POST['type']);
        $typestr = getBorrowStatus($typename[$type]);
        $types = array(
            "1" => array(    //发标中
                "pay" => 0,
                "status" => array("in", "{$typestr}"),
//                "rate_type" => BorrowModel::BID_CONFIG_RATE_TYPE_FULL_BORROW,
                "width" => array(143, 163, 163, 163, 133),
                "name" => array("还款方式", "借款金额", "借款进度", "借款时间", "操作"),
                "code" => array("repayment_type", "borrow_money", "progress", "add_time", "option")
            ),
            "2" => array(    //偿还中
                "pay" => 1,
                "status" => array("exp", "in ({$typestr}) AND d.status in (4,6,7,14) AND d.deadline > {$now}"),
                "width" => array(129, 127, 127, 127, 127, 129),
                "name" => array("还款方式", "借款金额", "已还金额", "年化利率", "还款期限", "即将还款期限"),
                "code" => array("repayment_type", "borrow_money", "receive", "borrow_interest_rate", "borrow_duration", "deadline")
            ),
            "3" => array(    //已逾期
                "pay" => 1,
                "status" => array("exp", "in ({$typestr}) AND d.status in (4,6,7) AND {$now} > d.deadline"),
                "width" => array(85, 85, 85, 85, 85, 85, 85, 85),
                "name" => array("待还本金", "待还利息", "待付罚息", "待付催收费", "待付总金额", "应还日期", "当前/总期", "逾期天数"),
                "code" => array("capital", "interest", "expired_money_now", "call_fee_now", "expired_total", "deadline", "sort_total", "expired_time")
            ),
            "4" => array(    //已失败
                "pay" => 0,
                "status" => array("in", "{$typestr}"),
                "width" => array(143, 163, 163, 163, 133),
                "name" => array("还款方式", "借款金额", "借款时间", "标的状态", "备注"),
                "code" => array("repayment_type", "borrow_money", "borrow_duration", "borrow_status", "remark")
            ),
            "5" => array(    //已还清
                "pay" => 2,
                "status" => array("in", "{$typestr}"),
                "width" => array(143, 163, 163, 163, 133),
                "name" => array("还款方式", "借款金额", "借款期限", "借款时间", "已还本息"),
                "code" => array("repayment_type", "borrow_money", "borrow_duration", "add_time", "receive")
            )
        );
        if (empty($types[$type]['order']) === false) {
            $order = $types[$type]['order'];
        }
        if (empty($types[$type]['status']) === false) {
            $map['b.borrow_status'] = $types[$type]['status'];
        }
//        if (!empty($types[$type]['rate_type'])) $map['b.rate_type'] = $types[$type]['rate_type'];
        $perpage = $_POST['perpage'];
        $curpage = $_POST['curpage'];
        $map['b.borrow_uid'] = $this->uid;

        if (empty($_POST['rptype']) === false) {
            $map['b.repayment_type'] = $rptype[intval($_POST['rptype']) - 1];
        }

        if (empty($_POST['deadline']) === false) {
            $tmp = getTimeArray(text($_POST['deadline']));
            if (is_array($tmp) === true) {
                $map['d.deadline'] = $tmp;
            }
        }


        $field = "b.id as bid, b.borrow_name, b.borrow_status, b.add_time, b.borrow_money, b.has_borrow, b.borrow_interest_rate, b.borrow_duration, b.duration_unit, b.borrow_type, b.repayment_type, ";
        $field .= "min(d.deadline) as deadline, max(d.status in (6,7)) as pay_status, d.status as d_status, sum(d.capital) as capital, sum(d.interest) as interest, d.sort_order, d.total, ";
        $field .= "sum(d.receive_capital + d.receive_interest + if(d.repayment_time > 0, d.interest_fee, 0)) as receive, if(v.deal_time_2 > 0, v.deal_info_2, v.deal_info) as remark";
        if (!$type || $type == 1) {
            if (!empty($map['d.deadline'])) {
                $map['b.add_time'] = $map['d.deadline'];
            }
            unset($map['d.deadline']);
            $field = "b.id as bid, b.borrow_name, b.borrow_status, b.add_time, b.borrow_money, b.has_borrow, b.borrow_interest_rate, b.borrow_duration, b.duration_unit, b.borrow_type, b.repayment_type";
            $count = M("borrow_info b")->where($map)->count("distinct b.id");
//            $limit = calPage($count, $curpage, $perpage);
            $re = M("borrow_info b")->field($field)->where($map)->select();
        } else {
            if (!in_array($type, array(3, 4, 5))) {
                $map['bi.parent_invest_id'] = 0;
            }
            $count = M("borrow_info b")
                ->join("{$pre}investor_detail d ON b.id = d.borrow_id")
                ->join("{$pre}borrow_investor bi ON bi.id = d.invest_id")
                ->where($map)->count("distinct b.id");
            $limit = calPage($count, $curpage, $perpage);
            $re = M("borrow_info b")->field($field)->where($map)
                ->join("{$pre}investor_detail d ON b.id = d.borrow_id")
                ->join("{$pre}borrow_investor bi ON bi.id = d.invest_id")
                ->join("{$pre}borrow_verify v ON b.id = v.borrow_id")
                ->order($order)->group("bid")->limit($limit)->select();
        }
        foreach ($re as $k => $v) {
            $re[$k]['option'] = (canErase($v) === false || $v['borrow_type'] == 6 || $v['borrow_type'] == 7) ? "--" : "<span style='color:#3181d8; cursor:pointer;' class='do_erase' id='do_erase{$v['bid']}' _bid='{$v['bid']}'>撤销</span>";
            $re[$k]['deadline'] = date("Y-m-d", $v['deadline']);
            $re[$k]['add_time'] = date("Y-m-d", $v['add_time']);
            $re[$k]['borrow_url'] = getBorrowUrl($v['borrow_type'], $v['bid']);
            $re[$k]['borrow_status'] = $Bconfig['BORROW_STATUS'][$v['borrow_status']];
            $re[$k]['remark'] = (empty($v['remark']) === false) ? $v['remark'] : "--";
            $re[$k]['repayment_type'] = $Bconfig['REPAYMENT_TYPE'][$v['repayment_type']];
            $re[$k]['progress'] = getFloatValue($v['has_borrow'] * 100 / $v['borrow_money'], 0) . "%";
            $re[$k]['borrow_duration'] = $v['borrow_duration'] . BorrowModel::get_unit_format($v['duration_unit']);
            if ($type == 3) {    //逾期
                $re[$k]['expired_time'] = $expired_days = getExpiredDays($v['deadline']);
                $re[$k]['call_fee_now'] = getExpiredCallFee($expired_days, $v['capital'], $v['interest']);
                $re[$k]['expired_money_now'] = getExpiredMoney($expired_days, $v['capital'], $v['interest']);
                $re[$k]['expired_total'] = getFloatValue($v['capital'] + $v['interest'] + $re[$k]['expired_money_now'] + $re[$k]['call_fee_now'], 2);
                $re[$k]['sort_total'] = $v['sort_order'] . "/" . $v['total'];
            }
        }
        $json['data']['list'] = $re;
        $json['data']['pay'] = $types[$type]['pay'];
        $json['data']['name'] = $types[$type]['name'];
        $json['data']['code'] = $types[$type]['code'];
        $json['data']['width'] = $types[$type]['width'];
        $json['data']['cols'] = count($types[$type]['name']);
        $json['data']['perpage'] = $perpage;
        $json['data']['curpage'] = $curpage;
        $json['data']['allpage'] = $count;
        $json['data']['length'] = 1;
        $json['code'] = 0;
        echo json_encode($json);
        exit;
    }
    /**
    * 检测支付密码
    */
    public function chkpaypass(){
        $paypass = md5($_POST['oldpwd']);
        $vm = getMinfo($this->uid,'m.pin_pass');
        if($paypass == $vm['pin_pass']){
            ajaxmsg();
        }else{
            ajaxmsg('支付密码错误',0);
        }
    }
}
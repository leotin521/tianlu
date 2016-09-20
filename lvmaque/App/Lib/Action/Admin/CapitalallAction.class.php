<?php

// 全局设置
class CapitalallAction extends ACommonAction
{
    /**
     * +----------------------------------------------------------
     * 默认操作
     * +----------------------------------------------------------
     */
    public function index()
    {
        $version = FS("Webconfig/version");
        $this->assign('version', $version);
        $designer = FS("Webconfig/designer");
        $this->assign('designer', $designer);
        $map = array();
        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['add_time'] = array("between", $timespan);
            $search['start_time'] = strtotime(urldecode($_REQUEST['start_time']));
            $search['end_time'] = strtotime(urldecode($_REQUEST['end_time']));
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['add_time'] = array("gt", $xtime);
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['add_time'] = array("lt", $xtime);
            $search['end_time'] = $xtime;
        } else {
            $map['add_time'] = array("lt", time());
        }
        $list = M("member_moneylog")->field('type,sum(affect_money) as money')->where($map)->group('type')->select();
        $row = array();
        $name = C('MONEY_LOG');
        foreach ($list as $v) {
            $row[$v['type']]['money'] = ($v['money'] > 0) ? $v['money'] : $v['money'] * (-1);
            $row[$v['type']]['name'] = $name[$v['type']];
        }
        $map['withdraw_status'] = 2;
        $tx = M('member_withdraw')->where($map)->sum("second_fee");
        $row['tx']['name'] = '提现手续费';
        $row['tx']['money'] = $tx;
        $add_time = $map['add_time'];
        $map = array();
        $map1['deadline'] = $add_time;
        $map1['status'] = array("in", "7,3,4,5");
        $map2['deadline'] = $add_time;
        $map2['status'] = array("in", "3,4,5");
        //逾期
        $row['expired']['money'] = M('investor_detail')->where($map1)->sum('capital');
        $row['expired']['re_money'] = M('investor_detail')->where($map2)->sum('capital');
        //逾期

        //会员统计
        $mm = M('members')->count("id");
        $row['mm']['name'] = '会员数';
        $row['mm']['num'] = $mm;

        $ms_phone = M('members_status')->where("phone_status=1")->count("uid");
        $ms_id = M('members_status')->where("id_status=1")->count("uid");
        $row['mm']['name'] = '会员数';
        $row['mm']['num'] = $mm;
        $row['mm']['ms_phone'] = $ms_phone;
        $row['mm']['ms_id'] = $ms_id;

        #企业直投
        if (isset($version) && $version['business'] == 1) {
            $fieldyh = "sum(receive_capital) as receive_capital,sum(receive_interest) as receive_interest,sum(reward_money) as reward_money, sum(invest_fee) as invest_fee";
            $field = "sum(investor_capital) as investor_capital,sum(investor_interest) as investor_interest";
            $transfer = M("borrow_investor")->field($field)->where("borrow_type=6 and status>=4 and parent_invest_id=0")->find();
            $maps = array();
            $maps['borrow_type'] = 6;
            $maps['status'] = array('in', '5,6,7');
            $transferyh = M("borrow_investor")->where($maps)->field($fieldyh)->find();
            $transfer = array_merge($transfer, $transferyh);
            $this->assign("transfer", $transfer);
        }

        #定投宝
        if (isset($version) && $version['fund'] == 1) {
            $maps = array();
            $maps['borrow_type'] = 7;
            $maps['status'] = array('in', '5,6,7');
            $field = "sum(investor_capital) as investor_capital,sum(investor_interest) as investor_interest";
            $fund = M("borrow_investor")->field($field)->where("borrow_type=7 && parent_invest_id=0")->find();
            $fundyh = M("borrow_investor")->where($maps)->field($fieldyh)->find();
            $fund = array_merge($fundyh, $fund);
            $fund['need_pay_interest'] = bcsub(bcsub($fund['investor_interest'], $fund['receive_interest'], 2), $fund['invest_fee'], 2);
            $this->assign("fund", $fund);
        }

        #散标
        if (isset($version) && $version['single'] == 1) {
            $maps = array();
            $maps['borrow_type'] = array('lt', '6');
            $maps['status'] = array('in', '5,6,7');
            //5.6.7
            $field = "sum(investor_capital) as investor_capital,sum(investor_interest) as investor_interest";
            $invest = M("borrow_investor")->where("borrow_type<6 and status>3 && parent_invest_id=0")->field($field)->find();
            $investyh = M("borrow_investor")->where($maps)->field($fieldyh)->find();
            $invest = array_merge($investyh, $invest);
            $invest['need_pay_interest'] = bcsub(bcsub($invest['investor_interest'], $invest['receive_interest'], 2), $invest['invest_fee'], 2);
            $this->assign("invest", $invest);
        }

        #会员统计
        $this->assign("search", $search);
        $this->assign('list', $row);
        

        if (isset($version) && $version['agility'] == 1) {
            //投资者收益统计
            //灵活宝统计
            //i>总借出金额:
            $raise_funds = M('bao')->where('raise_funds>0')->sum('raise_funds');
            //$funds=M('bao')->where('raise_funds =0')->sum('funds'); //TODO:?
            $bao['total'] = $raise_funds;
            $bao['repay_total'] = M('bao_log')->where('status=1 and `type`=2')->sum('money'); //赎回记录统计
            $bao['wait_repay_total'] = M('bao_invest')->where("money>0")->sum('money');
            //待支付利息
            $agility = BaoInvestModel::get_collect_money();
            $bao['wait_repay_total_interest'] = $agility['collect_interest'];
            $bao['repay_total_interest'] = M('bao_invest')->where("money>0")->sum('interest');
            $this->assign('bao', $bao);
        }
        $this->display();
    }

    //成功借出明细
    public function borrow()
    {
        $designer = FS("Webconfig/designer");
        $this->assign('designer', $designer);
        $map = array();
        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['b.add_time'] = array("between", $timespan);
            $search['start_time'] = strtotime(urldecode($_REQUEST['start_time']));
            $search['end_time'] = strtotime(urldecode($_REQUEST['end_time']));
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['b.add_time'] = array("gt", $xtime);
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['b.add_time'] = array("lt", $xtime);
            $search['end_time'] = $xtime;
        }
        $map['b.borrow_times'] = array('gt', '0');
        $map['_string'] = "b.borrow_status in(4,6,7,8,9,10) or b.rate_type=1";
        import("ORG.Util.Page");
        $count = M('borrow_info b')->join("{$this->pre}members m ON m.id=b.borrow_uid")->where($map)->count('b.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $field = 'b.duration_unit,b.rate_type,b.has_borrow,b.id,b.borrow_name,b.borrow_uid,b.borrow_duration,b.borrow_type,b.updata,b.borrow_money,b.borrow_fee,b.borrow_interest_rate,b.repayment_type,b.add_time,m.user_name,m.id mid,b.is_tuijian,b.money_collect';
        $list = M('borrow_info b')->field($field)->join("{$this->pre}members m ON m.id=b.borrow_uid")->where($map)->limit($Lsql)->order("b.id DESC")->select();
        $this->assign("list", $list);

        $this->assign("pagebar", $page);
        $this->assign("search", $search);

        $search['export'] = 1;
        $this->assign("query", http_build_query($search));
        if (intval($_GET['export']) == 1) {
            $list = M('borrow_info b')->field($field)->join("{$this->pre}members m ON m.id=b.borrow_uid")->where($map)->order("b.id DESC")->select();
            import("ORG.Io.Excel");
            alogs("borrow", 0, 1, '执行了借款记录列表导出操作！'); //管理员操作日志
            $row = array();
            $row[0] = array('ID', '借款人', '借款种类', '标题', '借款金额', '还款方式', '借款期限', '借款手续费', '借款时间');
            $i = 1;
            foreach ($list as $v) {
                if (!$v['id']) {
                    break;
                }
                if ($v['borrow_type'] == 7) {
                    $t = $designer[7];
                } elseif ($v['borrow_type'] == 6) {
                    $t = $designer[6];
                } else {
                    $t = "散标";
                }
                $row[$i]['uid'] = $v['id'];
                $row[$i]['user_name'] = $v['user_name'];
                $row[$i]['borrow_type'] = $t;
                $row[$i]['borrow_name'] = $v['borrow_name'];
                if ($v['rate_type'] == 1) {
                    $row[$i]['borrow_money'] = $v['has_borrow'];
                } else {
                    $row[$i]['borrow_money'] = $v['borrow_money'];
                }

                $row[$i]['repayment_type'] = $v['repayment_type'];
                if ($v['duration_unit'] == 0) {
                    $d = "天";
                } else {
                    $d = "个月";
                }
                $row[$i]['borrow_duration'] = $v['borrow_duration'] . $d;
                $row[$i]['borrow_fee'] = $v['borrow_fee'];
                $row[$i]['add_time'] = date('Y-m-d H:i', $v['add_time']);
                $i++;
            }
            $xls = new Excel_XML('UTF-8', false, 'datalist');
            $xls->addArray($row);
            $xls->generateXML("borrow");
        } else {
            $this->display();
        }
    }

    /**
     * @todo 已还明细
     *
     */
    public function repayment()
    {
        $designer = FS("Webconfig/designer");
        $this->assign('designer', $designer);
        $map = array();
        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['d.repayment_time'] = array("between", $timespan);
            $search['start_time'] = strtotime(urldecode($_REQUEST['start_time']));
            $search['end_time'] = strtotime(urldecode($_REQUEST['end_time']));
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['d.repayment_time'] = array("gt", $xtime);
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['d.repayment_time']  = array('between',array(1,$xtime));
            $search['end_time'] = $xtime;
        } else{
            $map['d.repayment_time'] = array('neq', 0);
        }
        import("ORG.Util.Page");
        $count = M('investor_detail d')->join("{$this->pre}members m ON m.id=d.borrow_uid")->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->where($map)->count('d.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $list = M('investor_detail d')->join("{$this->pre}members m ON m.id=d.borrow_uid")->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->where($map)->limit($Lsql)->order("d.id DESC")->select();
        $this->assign("list", $list);

        $this->assign("pagebar", $page);
        $this->assign("search", $search);


        $search['export'] = 1;
        $this->assign("query", http_build_query($search));
        if (intval($_GET['export']) == 1) {
            $list = M('investor_detail d')->join("{$this->pre}members m ON m.id=d.borrow_uid")->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->where($map)->order("d.id DESC")->select();
            import("ORG.Io.Excel");
            alogs("borrow", 0, 1, '执行了散标还款记录列表导出操作！'); //管理员操作日志
            $row = array();
            $row[0] = array('ID', '借款人', '标题', '标类型', '还款本金', '还款利息', '当前器数/总期数', '还款时间');
            $i = 1;
            foreach ($list as $v) {
                if (!$v['id']) {
                    break;
                }
                if ($v['borrow_type'] == 7) {
                    $t = $designer[7];
                } elseif ($v['borrow_type'] == 6) {
                    $t = $designer[6];
                } else {
                    $t = "散标";
                }
                $row[$i]['uid'] = $v['id'];
                $row[$i]['user_name'] = $v['user_name'];
                $row[$i]['borrow_name'] = $v['borrow_name'];
                $row[$i]['borrow_type'] = $t;
                $row[$i]['capital'] = $v['capital'];
                $row[$i]['interest'] = $v['interest'];
                $row[$i]['sort_order'] = $v['sort_order'] . '/' . $v['total'];
                $row[$i]['repayment_time'] = date('Y-m-d H:i', $v['repayment_time']);
                $i++;
            }
            $xls = new Excel_XML('UTF-8', false, 'datalist');
            $xls->addArray($row);
            $xls->generateXML("repayment");
        } else {
            $this->display();
        }
    }

    /**
     * @todo 未还款明细
     *
     */
    public function norepayment()
    {
        $designer = FS("Webconfig/designer");
        $this->assign('designer', $designer);
        $map = array();
        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['d.deadline'] = array("between", $timespan);
            $search['start_time'] = strtotime(urldecode($_REQUEST['start_time']));
            $search['end_time'] = strtotime(urldecode($_REQUEST['end_time']));
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['d.deadline'] = array("gt", $xtime);
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['d.deadline'] = array("lt", $xtime);
            $search['end_time'] = $xtime;
        }
        import("ORG.Util.Page");
        $map['d.status'] = array("eq", ' 7');
        $map['d.repayment_time'] = array('eq', 0);
        $count = M('investor_detail d')->join("{$this->pre}members m ON m.id=d.borrow_uid")->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->where($map)->count('b.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        $field = "b.duration_unit,b.id bid,b.borrow_type,b.borrow_name,d.*,m.user_name,m.id mid";
        $list = M('investor_detail d')->field($field)->join("{$this->pre}members m ON m.id=d.borrow_uid")->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->where($map)->limit($Lsql)->order("d.id DESC")->select();
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);
        $search['export'] = 1;
        $this->assign("query", http_build_query($search));
        if (intval($_GET['export']) == 1) {
            $list = M('investor_detail d')->field($field)->join("{$this->pre}members m ON m.id=d.borrow_uid")->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->where($map)->order("d.id DESC")->select();
            import("ORG.Io.Excel");
            alogs("borrow", 0, 1, '执行了散标未还明细导出操作！'); //管理员操作日志
            $row = array();
            $row[0] = array('还款ID', '借款人', '标题', '标类型', '应还本金', '应还利息', '当前器数/总期数', '应还时间');
            $i = 1;
            foreach ($list as $v) {
                if (!$v['id']) {
                    break;
                }
                if ($v['borrow_type'] == 7) {
                    $t = $designer[7];
                } elseif ($v['borrow_type'] == 6) {
                    $t = $designer[6];
                } else {
                    $t = "散标";
                }
                $row[$i]['uid'] = $v['id'];
                $row[$i]['user_name'] = $v['user_name'];
                $row[$i]['borrow_name'] = $v['borrow_name'];
                $row[$i]['borrow_type'] = $t;
                $row[$i]['capital'] = $v['capital'];
                $row[$i]['interest'] = $v['interest'];
                $row[$i]['sort_order'] = $v['sort_order'] . '/' . $v['total'];
                $row[$i]['deadline'] = date('Y-m-d H:i', $v['deadline']);
                $i++;
            }
            $xls = new Excel_XML('UTF-8', false, 'datalist');
            $xls->addArray($row);
            $xls->generateXML("norepayment");
        } else {
            $this->display();
        }
    }


    //借款管理费
    public function borrowfee()
    {
        $map = array();
        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['ml.add_time'] = array("between", $timespan);
            $search['start_time'] = strtotime(urldecode($_REQUEST['start_time']));
            $search['end_time'] = strtotime(urldecode($_REQUEST['end_time']));
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['ml.add_time'] = array("gt", $xtime);
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['ml.add_time'] = array("lt", $xtime);
            $search['end_time'] = $xtime;
        } else {
            $map['add_time'] = array("lt", time());
        }
        $map['ml.type'] = array('eq', '18');
        import("ORG.Util.Page");
        $count = M('member_moneylog ml')->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->count('ml.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $field = 'ml.*,m.user_name';
        $list = M('member_moneylog ml')->field($field)->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->limit($Lsql)->order("ml.id DESC")->select();
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);

        $search['export'] = 1;
        $this->assign("query", http_build_query($search));
        if (intval($_GET['export']) == 1) {
            $list = M('member_moneylog ml')->field($field)->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->order("ml.id DESC")->select();
            import("ORG.Io.Excel");
            alogs("borrowfee", 0, 1, '执行了借款管理费明细导出操作！'); //管理员操作日志
            $row = array();
            $row[0] = array('ID', '借款人', '借款管理费', '详细信息', '时间');
            $i = 1;
            foreach ($list as $v) {
                if (!$v['id']) {
                    break;
                }
                $row[$i]['id'] = $v['id'];
                $row[$i]['user_name'] = $v['user_name'];
                $row[$i]['affect_money'] = abs($v['affect_money']);
                $row[$i]['info'] = $v['info'];
                $row[$i]['add_time'] = date('Y-m-d H:i', $v['add_time']);
                $i++;
            }
            $xls = new Excel_XML('UTF-8', false, 'datalist');
            $xls->addArray($row);
            $xls->generateXML("borrowfee");
        } else {
            $this->display();
        }
    }

    public function insterestfee()
    {
        $map = array();
        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['ml.add_time'] = array("between", $timespan);
            $search['start_time'] = strtotime(urldecode($_REQUEST['start_time']));
            $search['end_time'] = strtotime(urldecode($_REQUEST['end_time']));
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['ml.add_time'] = array("gt", $xtime);
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['ml.add_time'] = array("lt", $xtime);
            $search['end_time'] = $xtime;
        } else {
            $map['ml.add_time'] = array("lt", time());
        }
        $map['ml.type'] = array('eq', 23);
        //分页处理
        import("ORG.Util.Page");
        $count = M('member_moneylog ml')->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->count('ml.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $field = 'ml.*,m.user_name';
        $list = M('member_moneylog ml')->field($field)->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->limit($Lsql)->order("ml.id DESC")->select();

        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);

        $search['export'] = 1;
        $this->assign("query", http_build_query($search));
        if (intval($_GET['export']) == 1) {
            $list = M('member_moneylog ml')->field($field)->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->order("ml.id DESC")->select();

            import("ORG.Io.Excel");
            alogs("borrow", 0, 1, '执行了利息管理费明细导出操作！'); //管理员操作日志
            $row = array();
            $row[0] = array('ID', '投资人', '利息管理费', '详细信息', '时间');
            $i = 1;
            foreach ($list as $v) {
                if (!$v['id']) {
                    break;
                }
                $row[$i]['id'] = $v['id'];
                $row[$i]['user_name'] = $v['user_name'];
                $row[$i]['invest_fee'] = abs($v['affect_money']);
                $row[$i]['info'] = $v['info'];
                $row[$i]['add_time'] = date('Y-m-d H:i', $v['add_time']);
                $i++;
            }
            $xls = new Excel_XML('UTF-8', false, 'datalist');
            $xls->addArray($row);
            $xls->generateXML("insterestfee");
        } else {
            $this->display();
        }
    }

    //提现手续费
    public function withdrawfee()
    {
        //分页处理
        $map = array();
        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['w.add_time'] = array("between", $timespan);
            $search['start_time'] = strtotime(urldecode($_REQUEST['start_time']));
            $search['end_time'] = strtotime(urldecode($_REQUEST['end_time']));
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['w.add_time'] = array("gt", $xtime);
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['w.add_time'] = array("lt", $xtime);
            $search['end_time'] = $xtime;
        }
        import("ORG.Util.Page");
        $count = M('member_withdraw w')->join("{$this->pre}members m ON w.uid=m.id")->where($map)->count('w.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $field = 'w.*,m.user_name,(mm.account_money+mm.back_money) account_money';
        $list = M('member_withdraw w')->field($field)->join("{$this->pre}members m ON w.uid=m.id")->join("lzh_member_money mm on w.uid = mm.uid")->where($map)->limit($Lsql)->order("w.id DESC")->select();
        $this->assign("list", $list);
        $status = C('WITHDRAW_STATUS');
        $this->assign("status", C('WITHDRAW_STATUS'));
        $this->assign("pagebar", $page);
        $this->assign("search", $search);

        $search['export'] = 1;
        $this->assign("query", http_build_query($search));
        if (intval($_GET['export']) == 1) {
            $list = M('member_withdraw w')->field($field)->join("{$this->pre}members m ON w.uid=m.id")->join("lzh_member_money mm on w.uid = mm.uid")->where($map)->order("w.id DESC")->select();
            import("ORG.Io.Excel");
            alogs("borrow", 0, 1, '执行了提现手续费明细导出操作！'); //管理员操作日志
            $row = array();
            $row[0] = array('ID', '用户名', '提现金额', '提现手续费', '到账金额', '提现状态', '提现时间', '处理时间', '处理人');
            $i = 1;
            foreach ($list as $v) {
                if (!$v['id']) {
                    break;
                }
                $row[$i]['id'] = $v['id'];
                $row[$i]['user_name'] = $v['user_name'];
                $row[$i]['withdraw_money'] = $v['withdraw_money'];
                $row[$i]['second_fee'] = $v['second_fee'];
                if ($v['withdraw_status'] == 3) {
                    $row[$i]['success_money'] = 0;
                } else {
                    $row[$i]['success_money'] = $v['success_money'];
                }
                $row[$i]['withdraw_status'] = $status[$v['withdraw_status']];
                $row[$i]['add_time'] = date('Y-m-d H:i', $v['add_time']);
                if($v['deal_time']>0){
                    $row[$i]['deal_time'] = date('Y-m-d H:i', $v['deal_time']);
                }else{
                    $row[$i]['deal_time'] = '';
                }
                $row[$i]['deal_user'] = $v['deal_user'];
                $i++;
            }
            $xls = new Excel_XML('UTF-8', false, 'datalist');
            $xls->addArray($row);
            $xls->generateXML("withdrawfee");
        } else {
            $this->display();
        }
    }

    //债权转让手续费
    public function debtfee()
    {
        $map = array();
        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['m.add_time'] = array("between", $timespan);
            $search['start_time'] = strtotime(urldecode($_REQUEST['start_time']));
            $search['end_time'] = strtotime(urldecode($_REQUEST['end_time']));
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['m.add_time'] = array("gt", $xtime);
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['m.add_time'] = array("lt", $xtime);
            $search['end_time'] = $xtime;
        }
        $map['m.type'] = array('eq', '48');
        //分页处理
        import("ORG.Util.Page");
        $count = M('member_moneylog m')->where($map)->count('m.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $field = 'm.*';
        $list = M('member_moneylog m')->field($field)->where($map)->limit($Lsql)->order("m.id DESC")->select();
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);

        $search['export'] = 1;
        $this->assign("query", http_build_query($search));
        if (intval($_GET['export']) == 1) {
            $list = M('member_moneylog m')->field($field)->where($map)->order("m.id DESC")->select();
            import("ORG.Io.Excel");
            alogs("borrow", 0, 1, '执行了债权转让手续费明细导出操作！'); //管理员操作日志
            $row = array();
            $row[0] = array('ID', '影响金额', '备注说明', '时间');
            $i = 1;
            foreach ($list as $v) {
                if (!$v['id']) {
                    break;
                }
                $row[$i]['id'] = $v['id'];
                $row[$i]['affect_money'] = $v['affect_money'];
                $row[$i]['info'] = $v['info'];
                $row[$i]['add_time'] = date('Y-m-d H:i', $v['add_time']);
                $i++;
            }
            $xls = new Excel_XML('UTF-8', false, 'datalist');
            $xls->addArray($row);
            $xls->generateXML("debtfee");
        } else {
            $this->display();
        }
    }

    public function interest()
    {
        $map = array();
        $map['ml.type'] = array('eq', '28');
        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['ml.add_time'] = array("between", $timespan);
            $search['start_time'] = strtotime(urldecode($_REQUEST['start_time']));
            $search['end_time'] = strtotime(urldecode($_REQUEST['end_time']));
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['ml.add_time'] = array("gt", $xtime);
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['ml.add_time'] = array("lt", $xtime);
            $search['end_time'] = $xtime;
        } else {
            $map['ml.add_time'] = array("lt", time());
        }
        //分页处理
        import("ORG.Util.Page");
        $count = M('member_moneylog ml')->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->count('ml.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $field = 'ml.*,m.user_name';
        $list = M('member_moneylog ml')->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->limit($Lsql)->order("ml.id DESC")->select();
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);

        $search['export'] = 1;
        $this->assign("query", http_build_query($search));
        if (intval($_GET['export']) == 1) {
            $list = M('member_moneylog ml')->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->order("ml.id DESC")->select();
            import("ORG.Io.Excel");
            alogs("borrow", 0, 1, '执行了散标借款利息明细导出操作！'); //管理员操作日志
            $row = array();
            $row[0] = array('ID', '投资人', '影响金额', '详细说明', '时间');
            $i = 1;
            foreach ($list as $v) {
                if (!$v['id']) {
                    break;
                }
                $row[$i]['id'] = $v['id'];
                $row[$i]['user_name'] = $v['user_name'];
                $row[$i]['affect_money'] = abs($v['affect_money']);
                $row[$i]['info'] = $v['info'];
                $row[$i]['add_time'] = date('Y-m-d H:i', $v['add_time']);
                $i++;
            }
            $xls = new Excel_XML('UTF-8', false, 'datalist');
            $xls->addArray($row);
            $xls->generateXML("interest");
        } else {
            $this->display();
        }
    }

    //投标奖励
    public function reward()
    {
        //分页处理
        $map = array();
        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['ml.add_time'] = array("between", $timespan);
            $search['start_time'] = strtotime(urldecode($_REQUEST['start_time']));
            $search['end_time'] = strtotime(urldecode($_REQUEST['end_time']));
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['ml.add_time'] = array("gt", $xtime);
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['ml.add_time'] = array("lt", $xtime);
            $search['end_time'] = $xtime;
        }
        $map['ml.type'] = array('eq', 21);
        import("ORG.Util.Page");
        $count = M('member_moneylog ml')->field(true)->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->count('ml.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $list = M('member_moneylog ml')->field(true)->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->limit($Lsql)->order("ml.id DESC")->select();
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);

        $search['export'] = 1;
        $this->assign("query", http_build_query($search));
        if (intval($_GET['export']) == 1) {
            $list = M('member_moneylog ml')->field(true)->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->order("ml.id DESC")->select();
            import("ORG.Io.Excel");
            alogs("borrow", 0, 1, '执行了投标奖励明细导出操作！'); //管理员操作日志
            $row = array();
            $row[0] = array('ID', '投资人', '奖励金额', '奖励信息', '获取时间');
            $i = 1;
            foreach ($list as $v) {
                if (!$v['id']) {
                    break;
                }
                $row[$i]['id'] = $v['id'];
                $row[$i]['user_name'] = $v['user_name'];
                $row[$i]['affect_money'] = abs($v['affect_money']);
                $row[$i]['info'] = $v['info'];
                $row[$i]['add_time'] = date('Y-m-d H:i', $v['add_time']);
                $i++;
            }
            $xls = new Excel_XML('UTF-8', false, 'datalist');
            $xls->addArray($row);
            $xls->generateXML("reward");
        } else {
            $this->display();
        }
    }


    //线下充值奖励
    public function linereward()
    {
        $map = array();
        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['ml.add_time'] = array("between", $timespan);
            $search['start_time'] = strtotime(urldecode($_REQUEST['start_time']));
            $search['end_time'] = strtotime(urldecode($_REQUEST['end_time']));
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['ml.add_time'] = array("gt", $xtime);
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['ml.add_time'] = array("lt", $xtime);
            $search['end_time'] = $xtime;
        }
        //分页处理
        $map['ml.type'] = array('eq', 32);
        import("ORG.Util.Page");
        $count = M('member_moneylog ml')->field(true)->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->count('ml.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $list = M('member_moneylog ml')->field(true)->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->limit($Lsql)->order("ml.id DESC")->select();
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);

        $search['export'] = 1;
        $this->assign("query", http_build_query($search));
        if (intval($_GET['export']) == 1) {
            $list = M('member_moneylog ml')->field(true)->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->order("ml.id DESC")->select();
            import("ORG.Io.Excel");
            alogs("borrow", 0, 1, '执行了托管账户充值奖励明细导出操作！'); //管理员操作日志
            $row = array();
            $row[0] = array('ID', '充值人', '奖励金额', '奖励信息', '获取时间');
            $i = 1;
            foreach ($list as $v) {
                if (!$v['id']) {
                    break;
                }
                $row[$i]['id'] = $v['id'];
                $row[$i]['user_name'] = $v['user_name'];
                $row[$i]['affect_money'] = $v['affect_money'];
                $row[$i]['info'] = $v['info'];
                $row[$i]['add_time'] = date('Y-m-d H:i', $v['add_time']);
                $i++;
            }
            $xls = new Excel_XML('UTF-8', false, 'datalist');
            $xls->addArray($row);
            $xls->generateXML("linereward");
        } else {
            $this->display();
        }
    }


    //逾期已还
    public function expired()
    {
        $map = array();
        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['d.deadline'] = array("between", $timespan);
            $search['start_time'] = strtotime(urldecode($_REQUEST['start_time']));
            $search['end_time'] = strtotime(urldecode($_REQUEST['end_time']));
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['d.deadline'] = array("gt", $xtime);
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['d.deadline'] = array("lt", $xtime);
            $search['end_time'] = $xtime;
        } else {
            $map['d.deadline'] = array("lt", time());
        }
        import("ORG.Util.Page");
        $map['d.deadline'] = $map['d.deadline'];
        $map['d.status'] = array("in", "3,4,5");
        $count = M('investor_detail d')->where($map)->group('d.sort_order,d.borrow_id')->count();
        // dump($count);
        // $newsql = M()->query("select count(*) as tc from {$buildSql} as t");
        // $count = $newsql[0]['tc'];
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $field = "b.duration_unit,d.repayment_time,m.user_name,d.borrow_id as id,b.repayment_type,b.borrow_name,d.status,d.total,d.borrow_id,b.borrow_uid,d.sort_order,sum(d.capital+d.interest) as capital,d.interest as interest,d.substitute_money as substitute_money,d.deadline,b.borrow_duration,b.borrow_type,d.expired_days,d.expired_money,d.call_fee";
        $list = M('investor_detail d')->field($field)->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->join("{$this->pre}members m ON m.id=b.borrow_uid")->where($map)->group('d.sort_order,d.borrow_id')->order('d.borrow_id,d.sort_order')->limit($Lsql)->select();
        //$list = $this->_listFilter($list);
        $this->assign("status", array("1" => '已代还', "2" => '未代还'));
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);

        $search['export'] = 1;
        $this->assign("query", http_build_query($search));
        if (intval($_GET['export']) == 1) {
            $list = M('investor_detail d')->field($field)->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->join("{$this->pre}members m ON m.id=b.borrow_uid")->where($map)->group('d.sort_order,d.borrow_id')->order('d.borrow_id,d.sort_order')->select();
            import("ORG.Io.Excel");
            alogs("borrow", 0, 1, '执行了逾期已还明细导出操作！'); //管理员操作日志
            $row = array();
            $row[0] = array('ID', '用户名', '借款标题', '借款期限', '当前期数', '应还时间', '应还金额', '逾期天数', '罚息', '逾期管理费', '实际还款时间');
            $i = 1;
            foreach ($list as $v) {
                if (!$v['id']) {
                    break;
                }
                $row[$i]['id'] = $v['id'];
                $row[$i]['user_name'] = $v['user_name'];
                $row[$i]['borrow_name'] = $v['borrow_name'];
                if ($v['duration_unit'] == 0) {
                    $d = "天";
                } else {
                    $d = "个月";
                }
                $row[$i]['total'] = $v['total'] . $d;
                $row[$i]['sort_order'] = $v['sort_order'] . '/' . $v['total'];
                $row[$i]['deadline'] = date('Y-m-d H:i', $v['deadline']);
                $row[$i]['capital'] = $v['capital'];
                $row[$i]['expired_days'] = $v['expired_days'];
                $row[$i]['expired_money'] = $v['expired_money'];
                $row[$i]['call_fee'] = $v['call_fee'];
                if ($v['repayment_time'] > 0) {
                    $row[$i]['repayment_time'] = date('Y-m-d H:i', $v['repayment_time']);
                } else {
                    $row[$i]['repayment_time'] = '网站已代还';
                }
                $i++;
            }
            $xls = new Excel_XML('UTF-8', false, 'datalist');
            $xls->addArray($row);
            $xls->generateXML("expired");
        } else {
            $this->display();
        }
    }

    //企业直投逾期未还
    public function waitexpired()
    {
        import("ORG.Util.Page");
        $map = array();
        $map['d.status'] = array("eq", 7);
        $map['d.deadline'] = array("lt", time());
        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['d.deadline'] = array("between", $timespan);
            $search['start_time'] = urldecode($_REQUEST['start_time']);
            $search['end_time'] = urldecode($_REQUEST['end_time']);
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['d.deadline'] = array("between", $xtime . "," . time());
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['d.deadline'] = array("between", time() . "," . $xtime);
            $search['end_time'] = $xtime;
        }
        $buildSql = M('investor_detail d')->field("d.id")->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->join("{$this->pre}members m ON m.id=b.borrow_uid")->where($map)->group('d.sort_order,d.borrow_id')->buildSql();
        $newsql = M()->query("select count(*) as tc from {$buildSql} as t");
        $count = $newsql[0]['tc'];
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $field = "b.duration_unit,m.user_name,d.borrow_id as id,b.borrow_name,d.status,d.total,d.borrow_id,b.borrow_uid,d.sort_order,sum(d.capital) as capital,sum(d.interest) as interest,sum(d.substitute_money) as substitute_money,d.deadline,b.borrow_duration,b.borrow_type";
        $list = M('investor_detail d')->field($field)->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->join("{$this->pre}members m ON m.id=b.borrow_uid")->where($map)->group('d.sort_order,d.borrow_id')->order('d.borrow_id,d.sort_order')->limit($Lsql)->select();
        $list = $this->_listFilter($list);
        $this->assign("bj", array("gt" => '大于', "eq" => '等于', "lt" => '小于'));
        $this->assign("status", array("1" => '已代还', "2" => '未代还'));
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);

        $search['export'] = 1;
        $this->assign("query", http_build_query($search));
        if (intval($_GET['export']) == 1) {
            $list = M('investor_detail d')->field($field)->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->join("{$this->pre}members m ON m.id=b.borrow_uid")->where($map)->group('d.sort_order,d.borrow_id')->order('d.borrow_id,d.sort_order')->select();
            $list = $this->_listFilter($list);
            import("ORG.Io.Excel");
            alogs("borrow", 0, 1, '执行了逾期未还明细导出操作！'); //管理员操作日志
            $row = array();
            $row[0] = array('ID', '用户名', '借款标题', '借款期限', '当前期数', '应还时间', '应还金额', '逾期天数', '罚息', '逾期管理费');
            $i = 1;
            foreach ($list as $v) {
                if (!$v['id']) {
                    break;
                }
                $row[$i]['id'] = $v['id'];
                $row[$i]['user_name'] = $v['user_name'];
                $row[$i]['borrow_name'] = $v['borrow_name'];
                if ($v['duration_unit'] == 0) {
                    $d = "天";
                } else {
                    $d = "个月";
                }
                $row[$i]['total'] = $v['total'] . $d;
                $row[$i]['sort_order'] = $v['sort_order'] . '/' . $v['total'];
                $row[$i]['deadline'] = date('Y-m-d H:i', $v['deadline']);
                $row[$i]['capital'] = $v['interest'] + $v['capital'];
                $row[$i]['breakday'] = $v['breakday'];
                $row[$i]['expired_money'] = $v['expired_money'];
                $row[$i]['call_fee'] = $v['call_fee'];
                $i++;
            }
            $xls = new Excel_XML('UTF-8', false, 'datalist');
            $xls->addArray($row);
            $xls->generateXML("waitexpired");
        } else {
            $this->display();
        }
    }

    //逾期逾期管理费
    public function callfee()
    {
        $map = array();
        $map['d.repayment_time'] = array("gt", 0);
        $map['d.status'] = array("in", "3,4,5");
        $map['d.deadline'] = array("between", "100000," . time());
        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['d.deadline'] = array("between", $timespan);
            $search['start_time'] = urldecode($_REQUEST['start_time']);
            $search['end_time'] = urldecode($_REQUEST['end_time']);
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['d.deadline'] = array("between", $xtime . "," . time());
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['d.deadline'] = array("between", time() . "," . $xtime);
            $search['end_time'] = $xtime;
        }
        import("ORG.Util.Page");
        $buildSql = M('investor_detail d')->field("d.id")->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->join("{$this->pre}members m ON m.id=b.borrow_uid")->where($map)->group('d.sort_order,d.borrow_id')->buildSql();
        $newsql = M()->query("select count(*) as tc from {$buildSql} as t");
        $count = $newsql[0]['tc'];
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $field = "b.duration_unit,m.user_name,d.repayment_time,d.borrow_id as id,b.borrow_name,d.status,d.total,d.borrow_id,b.borrow_uid,d.sort_order,sum(d.capital) as capital,sum(d.interest) as interest,sum(d.substitute_money) as substitute_money,d.deadline,b.borrow_duration,b.borrow_type,d.expired_days,d.expired_money,d.call_fee";
        $list = M('investor_detail d')->field($field)->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->join("{$this->pre}members m ON m.id=b.borrow_uid")->where($map)->group('d.sort_order,d.borrow_id')->order('d.borrow_id,d.sort_order')->limit($Lsql)->select();
        $this->assign("bj", array("gt" => '大于', "eq" => '等于', "lt" => '小于'));
        $this->assign("status", array("1" => '已代还', "2" => '未代还'));
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);

        $search['export'] = 1;
        $this->assign("query", http_build_query($search));
        if (intval($_GET['export']) == 1) {
            $list = M('investor_detail d')->field($field)->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->join("{$this->pre}members m ON m.id=b.borrow_uid")->where($map)->group('d.sort_order,d.borrow_id')->order('d.borrow_id,d.sort_order')->select();
            import("ORG.Io.Excel");
            alogs("borrow", 0, 1, '执行了逾期逾期管理费细导出操作！'); //管理员操作日志
            $row = array();
            $row[0] = array('ID', '用户名', '借款标题', '借款期限', '当前期数', '应还时间', '逾期天数', '罚息', '逾期管理费', '实际还款时间');
            $i = 1;
            foreach ($list as $v) {
                if (!$v['id']) {
                    break;
                }
                $row[$i]['id'] = $v['id'];
                $row[$i]['user_name'] = $v['user_name'];
                $row[$i]['borrow_name'] = $v['borrow_name'];
                if ($v['duration_unit'] == 0) {
                    $d = "天";
                } else {
                    $d = "个月";
                }
                $row[$i]['total'] = $v['total'] . $d;
                $row[$i]['sort_order'] = $v['sort_order'] . '/' . $v['total'];
                $row[$i]['deadline'] = date('Y-m-d H:i', $v['deadline']);
                $row[$i]['breakday'] = $v['expired_days'];
                $row[$i]['expired_money'] = $v['expired_money'];
                $row[$i]['call_fee'] = $v['call_fee'];
                if ($v['substitute_money'] > 0) {
                    $row[$i]['status'] = '网站已代还';
                } else {
                    $row[$i]['status'] = date('Y-m-d H:i', $v['repayment_time']);
                }
                $i++;
            }
            $xls = new Excel_XML('UTF-8', false, 'datalist');
            $xls->addArray($row);
            $xls->generateXML("callfee");
        } else {
            $this->display();
        }
    }

    //逾期罚息
    public function expiredfee()
    {
        $map = array();
        $map['d.repayment_time'] = array("gt", 0);
        $map['d.status'] = array("in", "3,4,5");
        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['d.deadline'] = array("between", $timespan);
            $search['start_time'] = urldecode($_REQUEST['start_time']);
            $search['end_time'] = urldecode($_REQUEST['end_time']);
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['d.deadline'] = array("between", $xtime . "," . time());
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['d.deadline'] = array("between", time() . "," . $xtime);
            $search['end_time'] = $xtime;
        }
        import("ORG.Util.Page");
        $buildSql = M('investor_detail d')->field("d.id")->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->join("{$this->pre}members m ON m.id=b.borrow_uid")->where($map)->group('d.sort_order,d.borrow_id')->buildSql();
        $newsql = M()->query("select count(*) as tc from {$buildSql} as t");
        $count = $newsql[0]['tc'];
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $field = "b.duration_unit,m.user_name,d.borrow_id as id,d.repayment_time,b.borrow_name,d.status,d.total,d.borrow_id,b.borrow_uid,d.sort_order,sum(d.capital) as capital,sum(d.interest) as interest,sum(d.substitute_money) as substitute_money,d.deadline,b.borrow_duration,b.borrow_type,d.expired_days,d.expired_money,d.call_fee";
        $list = M('investor_detail d')->field($field)->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->join("{$this->pre}members m ON m.id=b.borrow_uid")->where($map)->group('d.sort_order,d.borrow_id')->order('d.borrow_id,d.sort_order')->limit($Lsql)->select();
        $this->assign("bj", array("gt" => '大于', "eq" => '等于', "lt" => '小于'));
        $this->assign("status", array("1" => '已代还', "2" => '未代还'));
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);

        $search['export'] = 1;
        $this->assign("query", http_build_query($search));
        if (intval($_GET['export']) == 1) {
            $list = M('investor_detail d')->field($field)->join("{$this->pre}borrow_info b ON b.id=d.borrow_id")->join("{$this->pre}members m ON m.id=b.borrow_uid")->where($map)->group('d.sort_order,d.borrow_id')->order('d.borrow_id,d.sort_order')->select();
            import("ORG.Io.Excel");
            alogs("borrow", 0, 1, '执行了逾期罚息明细导出操作！'); //管理员操作日志
            $row = array();
            $row[0] = array('ID', '用户名', '借款标题', '借款期限', '当前期数', '应还金额', '逾期天数', '罚息', '逾期管理费');
            $i = 1;
            foreach ($list as $v) {
                if (!$v['id']) {
                    break;
                }
                $row[$i]['id'] = $v['id'];
                $row[$i]['user_name'] = $v['user_name'];
                $row[$i]['borrow_name'] = $v['borrow_name'];
                if ($v['duration_unit'] == 0) {
                    $d = "天";
                } else {
                    $d = "个月";
                }
                $row[$i]['total'] = $v['total'] . $d;
                $row[$i]['sort_order'] = $v['sort_order'] . '/' . $v['total'];
                $row[$i]['deadline'] = date('Y-m-d H:i', $v['deadline']);
                $row[$i]['breakday'] = $v['expired_days'];
                $row[$i]['expired_money'] = $v['expired_money'];
                $row[$i]['call_fee'] = $v['call_fee'];
                if ($v['substitute_money'] > 0) {
                    $row[$i]['status'] = '网站已代还';
                } else {
                    $row[$i]['status'] = date('Y-m-d H:i', $v['repayment_time']);
                }
                $i++;
            }
            $xls = new Excel_XML('UTF-8', false, 'datalist');
            $xls->addArray($row);
            $xls->generateXML("expiredfee");
        } else {
            $this->display();
        }
    }

    public function xutou()
    {
        $map = array();
        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['ml.add_time'] = array("between", $timespan);
            $search['start_time'] = strtotime(urldecode($_REQUEST['start_time']));
            $search['end_time'] = strtotime(urldecode($_REQUEST['end_time']));
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['ml.add_time'] = array("gt", $xtime);
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['ml.add_time'] = array("lt", $xtime);
            $search['end_time'] = $xtime;
        } else {
            $map['add_time'] = array("lt", time());
        }
        $map['ml.type'] = array('in', '34，40');
        import("ORG.Util.Page");
        $count = M('member_moneylog ml')->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->count('ml.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $field = 'ml.*,m.user_name';
        $list = M('member_moneylog ml')->field($field)->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->limit($Lsql)->order("ml.id DESC")->select();
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);

        $search['export'] = 1;
        $this->assign("query", http_build_query($search));
        if (intval($_GET['export']) == 1) {
            $list = M('member_moneylog ml')->field($field)->join("{$this->pre}members m ON m.id=ml.uid")->where($map)->order("ml.id DESC")->select();
            import("ORG.Io.Excel");
            alogs("xutou", 0, 1, '执行了续投奖励明细导出操作！'); //管理员操作日志
            $row = array();
            $row[0] = array('ID', '投资人', '奖励金额', '详细信息', '时间');
            $i = 1;
            foreach ($list as $v) {
                if (!$v['id']) {
                    break;
                }
                $row[$i]['id'] = $v['id'];
                $row[$i]['user_name'] = $v['user_name'];
                $row[$i]['affect_money'] = abs($v['affect_money']);
                $row[$i]['info'] = $v['info'];
                $row[$i]['add_time'] = date('Y-m-d H:i', $v['add_time']);
                $i++;
            }
            $xls = new Excel_XML('UTF-8', false, 'datalist');
            $xls->addArray($row);
            $xls->generateXML("xutou");
        } else {
            $this->display();
        }
    }


    private function _listFilter($list)
    {
        $row = array();
        foreach ($list as $key => $v) {
            $v['breakday'] = getExpiredDays($v['deadline']);
            $v['expired_money'] = getExpiredMoney($v['breakday'], $v['capital'], $v['interest']);
            $v['call_fee'] = getExpiredCallFee($v['breakday'], $v['capital'], $v['interest']);
            $row[$key] = $v;
        }
        return $row;
    }

}

?>

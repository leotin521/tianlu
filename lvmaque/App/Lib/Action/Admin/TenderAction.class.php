<?php

class TenderAction extends ACommonAction
{
    public function index()
    {
        $startdate = htmlspecialchars($_REQUEST['startdate'], ENT_QUOTES);
        $enddate = htmlspecialchars($_REQUEST['enddate'], ENT_QUOTES);
        $user_name = htmlspecialchars($_REQUEST['user_name'], ENT_QUOTES);
        $real_name = htmlspecialchars($_REQUEST['real_name'], ENT_QUOTES);
        $borrow_type = intval($_REQUEST['borrow_type']);
        $isauto = intval($_REQUEST['isauto']);

        $search = array();
        if (!empty($_REQUEST['bj']) && !empty($_REQUEST['money'])) {
            $map['bi.investor_capital'] = array($_REQUEST['bj'], floatval($_REQUEST['money']));
            $search['bj'] = htmlspecialchars($_REQUEST['bj'], ENT_QUOTES);
            $search['money'] = floatval($_REQUEST['money']);
        }
        if ($borrow_type) {
            $map['bi.borrow_type'] = $borrow_type;
            $search['borrow_type'] = $borrow_type;
        }
        if ($isauto) {
            $map['bi.is_auto'] = $isauto;
            $search['isauto'] = $isauto;
        }
        if ($startdate) {
            $startdate = strtotime($startdate);
            $map['bi.add_time'] = array('gt', $startdate);
            $search['startdate'] = $startdate;
        }
        if ($enddate) {
            $enddate = strtotime($enddate);
            $map['bi.add_time'] = array('lt', $enddate);
            $search['enddate'] = $enddate;
        }
        if ($user_name) {
            $map['m.user_name'] = $user_name;
            $search['user_name'] = $user_name;
        }
        if ($real_name) {
            $map['mi.real_name'] = $real_name;
            $search['real_name'] = $real_name;
        }

        //分页处理
        import("ORG.Util.Page");
        $count = M('borrow_investor bi')->join("{$this->pre}members m ON m.id=bi.investor_uid")->join("{$this->pre}member_info mi ON m.id=mi.uid")->where($map)->count('bi.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $pre = $this->pre;
        $field = 'b.duration_unit,bi.id bid,b.id,bi.investor_capital,bi.investor_interest,bi.invest_fee,bi.add_time,bi.is_auto,m.user_name,m.id mid,m.user_phone,b.borrow_duration,b.repayment_type,m.customer_name,b.borrow_type,b.borrow_name,mi.real_name';
        $list = M('borrow_investor bi')->field($field)->join("{$this->pre}members m ON m.id=bi.investor_uid")->join("{$this->pre}member_info mi ON m.id=mi.uid")->join("{$this->pre}borrow_info b ON b.id=bi.borrow_id")->where($map)->limit($Lsql)->order("bi.id DESC")->select();
        $list = $this->mb_listFilter($list);
        $this->assign("list", $list);

        $this->assign("pagebar", $page);
        $this->assign("search", $search);

        $borrow_types = BorrowModel::get_borrow_type();
        $this->assign("borrow_type", $borrow_types);

        $this->assign("bj", array("gt" => '大于', "eq" => '等于', "lt" => '小于'));
        $this->assign("isauto", array("0" => '手动投标', "1" => '自动投标'));
        $this->assign("query", http_build_query($search));
        $this->display();
    }

    public function export()
    {

        import("ORG.Io.Excel");
        alogs("Capitalaccount", 0, 1, '执行指定条件下投标记录列表导出操作！');//管理员操作日志
        $startdate = htmlspecialchars($_REQUEST['startdate'], ENT_QUOTES);
        $enddate = htmlspecialchars($_REQUEST['enddate'], ENT_QUOTES);
        $user_name = htmlspecialchars($_REQUEST['user_name'], ENT_QUOTES);
        $real_name = htmlspecialchars($_REQUEST['real_name'], ENT_QUOTES);
        $borrow_type = intval($_REQUEST['borrow_type']);
        $isauto = intval($_REQUEST['isauto']);

        $search = array();
        if (!empty($_REQUEST['bj']) && !empty($_REQUEST['money'])) {
            $map['bi.investor_capital'] = array(htmlspecialchars($_REQUEST['bj'], ENT_QUOTES), floatval($_REQUEST['money']));
            $search['bj'] = htmlspecialchars($_REQUEST['bj'], ENT_QUOTES);
            $search['money'] = floatval($_REQUEST['money']);
        }
        if ($borrow_type) {
            $map['bi.borrow_type'] = $borrow_type;
            $search['borrow_type'] = $borrow_type;
        }
        if ($isauto) {
            $map['bi.is_auto'] = $isauto;
            $search['isauto'] = $isauto;
        }
        if ($startdate) {
            $startdate = strtotime($startdate);
            $map['bi.add_time'] = array('gt', $startdate);
            $search['startdate'] = $startdate;
        }
        if ($enddate) {
            $enddate = strtotime($enddate);
            $map['bi.add_time'] = array('lt', $enddate);
            $search['enddate'] = $enddate;
        }
        if ($user_name) {
            $map['m.user_name'] = $user_name;
            $search['user_name'] = $user_name;
        }
        if ($real_name) {
            $map['mi.real_name'] = $real_name;
            $search['real_name'] = $real_name;
        }

        //分页处理
        import("ORG.Util.Page");
        $count = M('borrow_investor bi')->join("{$this->pre}members m ON m.id=bi.investor_uid")->join("{$this->pre}member_info mi ON m.id=mi.uid")->where($map)->count('bi.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $pre = $this->pre;
        $field = 'bi.id bid,b.id,bi.investor_capital,bi.investor_interest,b.duration_unit,bi.invest_fee,bi.add_time,bi.is_auto,m.user_name,m.id mid,m.user_phone,b.borrow_duration,b.repayment_type,m.customer_name,b.borrow_type,b.borrow_name,mi.real_name';
        $list = M('borrow_investor bi')->field($field)->join("{$this->pre}members m ON m.id=bi.investor_uid")->join("{$this->pre}member_info mi ON m.id=mi.uid")->join("{$this->pre}borrow_info b ON b.id=bi.borrow_id")->where($map)->order("bi.id DESC")->select();
        $list = $this->mb_listFilter($list);

        foreach ($list as $v) {
            $list[$key]['xmoney'] = $money;
        }
        $row = array();
        $row[0] = array('标号', '用户名', '真实姓名', '手机号', '客服', '标题', '投资金额', '应得利息', '投资期限', '投资成交管理费', '还款方式', '标种类型', '投标方式', '投标时间');
        $i = 1;
        foreach ($list as $v) {
            if (!$v['bid']) {
                break;
            }
            $row[$i]['uid'] = $v['bid'];
            $row[$i]['user_name'] = $v['user_name'];
            $row[$i]['real_name'] = $v['real_name'];
            $row[$i]['user_phone'] = $v['user_phone'];
            $row[$i]['customer_name'] = $v['customer_name'];
            $row[$i]['borrow_name'] = $v['borrow_name'];
            $row[$i]['investor_capital'] = $v['investor_capital'];
            $row[$i]['investor_interest'] = $v['investor_interest'];
            $d = BorrowModel::get_unit_format($v['duration_unit']);
            $row[$i]['borrow_duration'] = $v['borrow_duration'] . $d;
            $row[$i]['invest_fee'] = $v['invest_fee'];

            $row[$i]['repayment_type'] = $v['repayment_type'];
            $row[$i]['borrow_type'] = $v['borrow_type'];
            $row[$i]['is_auto'] = $v['is_auto'];
            $row[$i]['add_time'] = date('Y-m-d H:i', $v['add_time']);

            $i++;
        }
        $xls = new Excel_XML('UTF-8', false, 'datalist');
        $xls->addArray($row);
        $xls->generateXML("tender");
    }

    public function mb_listFilter($list)
    {
        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";
        $listType = $Bconfig['REPAYMENT_TYPE'];
        $row = array();
        $aUser = get_admin_name();
        foreach ($list as $key => $v) {
            $v['repayment_type_num'] = $v['repayment_type'];
            $v['repayment_type'] = $listType[$v['repayment_type']];
            $v['btype'] = $v['borrow_type'];
            $v['borrow_type'] = BorrowModel::get_borrow_type($v['borrow_type']);
            if ($v['deadline']) $v['overdue'] = getLeftTime($v['deadline']) * (-1);
            if ($v['borrow_status'] == 1 || $v['borrow_status'] == 3 || $v['borrow_status'] == 5) {
                $v['deal_uname_2'] = $aUser[$v['deal_user_2']];
                $v['deal_uname'] = $aUser[$v['deal_user']];
            }
            $v['last_money'] = $v['borrow_money'] - $v['has_borrow'];//新增剩余金额
            if ($v['is_auto'] == 1) {
                $v['is_auto'] = "自动投标";
            } else {
                $v['is_auto'] = "手动投标";
            }
            $row[$key] = $v;
        }
        return $row;
    }
}

?>
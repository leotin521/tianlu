<?php

// 本类由系统自动生成，仅供测试用途
class IndexAction extends HCommonAction
{
    public function index()
    {
        $pre = C('DB_PREFIX');
        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";
        $version = FS("Webconfig/version");
        $this->assign('wechat', $version['wechat']);
        //网站公告
        $parm['type_id'] = 9;
        $parm['limit'] = 6;
        $this->assign("noticeList", getArticleList($parm));
        //网站公告
        //正在进行的贷款
        $searchMap = array();
        $searchMap['borrow_status'] = array("in", '-1,2,4,6,7');
        $searchMap['is_tuijian'] = array("in", '0,1');
        $parm = array();
        $searchMap['borrow_type'] = array('lt', BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID);
        $parm['map'] = $searchMap;
        $parm['limit'] = 3;
        $parm['orderby'] = "b.borrow_status=7,b.borrow_status=6,b.borrow_status=4,b.borrow_status=-1,b.borrow_status=2,b.id DESC";
        $listBorrow = getBorrowList($parm);
        $this->assign("listBorrow", $listBorrow);

        // 灵活宝
        $agility_bao = new AgilityBehavior();
        $bao = $agility_bao->format_list();
        $bao = $bao[0];
        if (!empty($bao)) {
            $bao['lefttime'] = time() - $bao['online_time'];
        }
        $this->assign('bao', $bao);

        // 定投宝
        $where = array(
            'b.borrow_type' => BorrowModel::BID_CONFIG_TYPE_FINANCIAL,
            'b.borrow_status' => array("in", '-1,2,4,6,7'),
            'b.on_off' => 1
        );
        $fields = 'b.borrow_type,b.duration_unit,b.borrow_times,b.id,b.borrow_name,b.borrow_status,b.borrow_uid,b.borrow_duration,b.borrow_money,b.borrow_fee,b.borrow_interest_rate,b.repayment_type,b.has_borrow,b.add_time,m.user_name,b.borrow_max,b.is_tuijian,b.can_auto,b.is_xinshou,b.is_taste';
        $order = "b.borrow_status=7,b.borrow_status=6,b.borrow_status=4,b.borrow_status=-1,b.borrow_status=2,b.id DESC";
        $page = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $list = TborrowModel::getTborrowByPage($where, $fields, $page, 2, $order);
        $this->assign('dingbao_items', $list);

        // 企业直投
        $where = array(
            'b.borrow_type' => BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID,
            'b.borrow_status' => array("in", '-1,2,4,6,7'),
            'b.on_off' => 1
        );
        $fields = 'b.borrow_type,b.duration_unit,b.borrow_times,b.id,b.borrow_name,b.borrow_status,b.borrow_uid,b.borrow_duration,b.borrow_money,b.borrow_fee,b.borrow_interest_rate,b.repayment_type,b.has_borrow,b.b_img,b.add_time,m.user_name,b.borrow_max,b.is_tuijian,b.can_auto,b.is_xinshou,b.is_taste';
        $transfer_items = TborrowModel::getTborrowByPage($where, $fields, 1, 2, $order);
        $this->assign('transfer_items', $transfer_items);

        //数据播报统计
        //实现借款(人）
        $sql = "select count(*) as count from (select distinct investor_uid from {$pre}borrow_investor) as a";
        $count_investor_uid = M()->query($sql);
        //投资完成（笔）
        $sql = "select count(*) as invest_count,sum(investor_interest) as interest_sum from {$pre}borrow_investor";
        $count_invest_interest = M()->query($sql);
        //累计收益(万元）
        if (!empty($count_invest_interest)) {
            $count_invest = $count_invest_interest[0];
            $count_invest['invest_uid_count'] = $count_investor_uid[0]['count'];
            if ($count_invest['interest_sum'] >= 10000) {
                $count_invest['interest_sum_unit'] = 1;
                $count_invest['interest_sum'] = ceil($count_invest['interest_sum'] / 10000);
            } else {
                $count_invest['interest_sum_unit'] = 0;
            }

            $this->assign('count_invest', $count_invest);
        }


        //累计投资总额
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
        $this->assign('list', $row);




        $this->display();
    }

}
	
<?php

// 本类由系统自动生成，仅供测试用途
class TinvestAction extends HCommonAction
{
    /**
     * 普通标列表
     */
    public function index()
    {
        //网站公告
        $parm['type_id'] = 1;
        $parm['limit'] = 8;
        $this->assign("noticeList", getArticleList($parm));
        //网站公告
        static $newpars;
        $curl = $_SERVER['REQUEST_URI'];
        $urlarr = parse_url($curl);
        parse_str($urlarr['query'], $surl);//array获取当前链接参数，2.
        $urlArr = array('borrow_status', 'interest_rate', 'borrow_money', 'borrow_duration', 'progress');
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
        $searchMap['borrow_status'] = array("all" => "全部", "-1" => "预告中", "2" => "进行中", "4" => "复审中", "6" => "还款中", "7" => "已完成");
        $searchMap['interest_rate'] = array("all" => "全部", "0-8" => "9%以下", "9-12" => "9%-13%", "13-18" => "13%-18%", "18-100" => "18%以上");
        $searchMap['borrow_money'] = array("all" => "全部", "0-100000" => "10万以下", "100000-1000000" => "10万-100万", "1000000-5000000" => "100万-500万", "5000000-100000000" => "500万以上");
        $searchMap['borrow_duration'] = array("all" => "全部", "0-92" => "3个月以内", "93-184" => "3-6个月", "185-366" => "6-12个月", "366-731" => "12个月以上");
        $searchMap['progress'] = array("all" => "全部", "0-50" => "50%以下", "50-75" => "50%-75%", "75-90" => "75%-90%", "90-101" => "90%以上");

        $search = array();
        //搜索条件
        foreach ($urlArr as $v) {
            if ($_GET[$v] && $_GET[$v] <> 'all') {
                $name = "b.{$v}";
                $barr = explode("-", text($_GET[$v]));
                switch ($v) {
                    case 'progress':
                        $search["b.has_borrow"] = array("exp", " >= (`borrow_money` * {$barr[0]} / 100) AND b.has_borrow < (`borrow_money` * {$barr[1]} / 100)");
                        break;
                    case 'borrow_duration':
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
                            $search["_string"] = "(b.borrow_duration<731 and b.borrow_duration>=366 and b.duration_unit!=1) or (b.borrow_duration>=12 and b.duration_unit=1)";
                        }
                        break;
                    case 'borrow_status':
                        $search['b.borrow_status'] = intval($_GET[$v]);
                        break;
                    case 'interest_rate':
                        $name = "b.borrow_interest_rate";
                    default:
                        $search[$name] = array("between", $barr);
                        break;
                }
            }
        }
        //$search['b.on_off'] = 1;
        $search['b.borrow_type'] = BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID;
        if ($search['b.borrow_status'] == 0) {
            $search['b.borrow_status'] = array("in", array(BorrowModel::BID_SINGLE_CONFIG_STATUS_WAIT_ONLINE, BorrowModel::BID_SINGLE_CONFIG_STATUS_VIEW_PASS, BorrowModel::BID_SINGLE_CONFIG_STATUS_REPAYMENT, BorrowModel::BID_SINGLE_CONFIG_STATUS_SUCCESS));
        }

        $fields = "b.*, b.b_img, bd.bianhao";
        $page = (isset($_GET['p']) ? intval($_GET['p']) : 1);
        $order = "b.borrow_status=7,b.borrow_status=6,b.borrow_status=4,b.borrow_status=-1,b.borrow_status=2,b.id DESC";
        $data = TborrowModel::getTborrowByPage($search, $fields, $page, 8, $order);

        //导航
        $navigate = get_navigate();

        $this->assign("navigate", $navigate);
        $this->assign("data", $data);
        $this->assign("searchUrl", $searchUrl);
        $this->assign("searchMap", $searchMap);
        $this->assign("gloconf", $this->gloconf);
        $this->display();
    }

    public function xinshou()
    {
        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";
        //dump($Bconfig);
        //网站公告
        $parm['type_id'] = 1;
        $parm['limit'] = 8;
        $this->assign("noticeList", getArticleList($parm));
        //网站公告
        static $newpars;
        $curl = $_SERVER['REQUEST_URI'];
        $urlarr = parse_url($curl);
        parse_str($urlarr['query'], $surl);//array获取当前链接参数，2.
        $urlArr = array('interest_rate', 'borrow_money', 'borrow_duration', 'progress');
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
        $searchMap['interest_rate'] = array("all" => "全部", "0-8" => "9%以下", "9-12" => "9%-13%", "13-18" => "13%-18%", "18-100" => "18%以上");
        $searchMap['borrow_money'] = array("all" => "全部", "0-100000" => "10万以下", "100000-1000000" => "10万-100万", "1000000-5000000" => "100万-500万", "5000000-100000000" => "500万以上");
        $searchMap['borrow_duration'] = array("all" => "全部", "0-92" => "3个月以内", "93-184" => "3-6个月", "185-366" => "6-12个月", "366-731" => "12-24个月");
        $searchMap['progress'] = array("all" => "全部", "0-49" => "50%以下", "50-74" => "50%以上", "75-89" => "75%以上", "90-100" => "90%以上");

        $search = array();
        //搜索条件
        foreach ($urlArr as $v) {
            if ($_GET[$v] && $_GET[$v] <> 'all') {
                $name = "b.{$v}";
                $barr = explode("-", text($_GET[$v]));
                switch ($v) {
                    case 'progress':
                        $search["b.has_borrow"] = array("exp", " >= (`borrow_money` * {$barr[0]} / 100) AND b.has_borrow <= (`borrow_money` * {$barr[1]} / 100)");
                        break;
                    case 'borrow_duration':
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
                    case 'interest_rate':
                        $name = "b.borrow_interest_rate";
                    default:
                        $search[$name] = array("between", $barr);
                        break;
                }
            }
        }
        $search['b.on_off'] = 1;
        $search['b.is_xinshou'] = 1;
        $search['b.borrow_type'] = BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID;
        $search['b.borrow_status'] = array("in", array(BorrowModel::BID_SINGLE_CONFIG_STATUS_VIEW_PASS, BorrowModel::BID_SINGLE_CONFIG_STATUS_REPAYMENT, BorrowModel::BID_SINGLE_CONFIG_STATUS_SUCCESS));
        $fields = "b.*,  b.b_img, bd.bianhao";
        $page = (isset($_GET['p']) ? intval($_GET['p']) : 1);
        $data = TborrowModel::getTborrowByPage($search, $fields, $page, 8);

        //导航
        $navigate = M('navigation')->field("type_name,type_url")->where(array('parent_id' => 2, 'model' => 'navigation'))->order("sort_order DESC")->select();

        $this->assign("navigate", $navigate);
        $this->assign("data", $data);
        $this->assign("searchUrl", $searchUrl);
        $this->assign("searchMap", $searchMap);
        $this->assign("gloconf", $this->gloconf);
        $this->display();
    }

    public function reward()
    {
        $borrow_id = intval($_GET['id']);
        $binfo = M('borrow_info')->where('id=' . $borrow_id)->find();
        $this->assign('binfo', $binfo);
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

    /////////////////////////////////////////////////////////////////////////////////////

    public function tdetail()
    {
        if ($_GET['type'] == 'commentlist') {
            //评论
            $cmap['tid'] = intval($_GET['id']);
            $clist = getCommentList($cmap, 5);
            $this->assign("commentlist", $clist['list']);
            $this->assign("commentpagebar", $clist['page']);
            $this->assign("commentcount", $clist['count']);
            $data['html'] = $this->fetch('commentlist');
            exit(json_encode($data));
        }
        $pre = C('DB_PREFIX');
        $id = intval($_GET['id']);
        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";

        //合同ID
        if ($this->uid) {
            $invs = M('borrow_investor')->field('id')->where("borrow_id={$id} AND (investor_uid={$this->uid} OR borrow_uid={$this->uid})")->find();
            if ($invs['id'] > 0) $invsx = $invs['id'];
            elseif (!is_array($invs)) $invsx = 'no';
        } else {
            $invsx = 'login';
        }
        $this->assign("invid", $invsx);
        //合同ID

        $borrowinfo = TborrowModel::get_format_borrow_info($id, "b.*, bwd.*, bd.bianhao");//`mxl 20150303`
        //上线时间或剩余募集期时间
        if( $borrowinfo['borrow_status'] == BorrowModel::BID_SINGLE_CONFIG_STATUS_WAIT_ONLINE ) {
            $borrowinfo['lefttime'] =strtotime($borrowinfo['online_time']) - time();
            $borrowinfo['add_time'] = strtotime($borrowinfo['online_time']);//开始时间为上线时间
        }else{
            $borrowinfo['lefttime'] =$borrowinfo['collect_time'] - time();
        }
        if (!is_array($borrowinfo) || ($borrowinfo['borrow_status'] == 0 && $this->uid != $borrowinfo['borrow_uid'])) $this->error("数据有误");
        $borrowinfo['biao'] = $borrowinfo['borrow_times'];
        $borrowinfo['need'] = $borrowinfo['borrow_money'] - $borrowinfo['has_borrow'];
        $borrowinfo['lefttime'] = $borrowinfo['collect_time'] - time();
        //$borrowinfo['progress'] = getFloatValue($borrowinfo['has_borrow']/$borrowinfo['borrow_money']*100,2);//`mxl 20150303`hide
        $borrowinfo['breif_count'] = mb_strlen($borrowinfo['borrow_breif'], 'UTF8');
        $borrowinfo['capital_count'] = mb_strlen($borrowinfo['borrow_capital'], 'UTF8');
        $borrowinfo['use_count'] = mb_strlen($borrowinfo['borrow_use'], 'UTF8');
        $borrowinfo['risk_count'] = mb_strlen($borrowinfo['borrow_risk'], 'UTF8');

        $this->assign("vo", $borrowinfo);

        $memberinfo = M("members m")->field("m.id,m.customer_name,m.customer_id,m.user_name,m.reg_time,m.credits,fi.*,mi.*,mm.*")->join("{$pre}member_financial_info fi ON fi.uid = m.id")->join("{$pre}member_info mi ON mi.uid = m.id")->join("{$pre}member_money mm ON mm.uid = m.id")->where("m.id={$borrowinfo['borrow_uid']}")->find();
        $areaList = getArea();
        $memberinfo['location'] = $areaList[$memberinfo['province']] . $areaList[$memberinfo['city']];
        $memberinfo['location_now'] = $areaList[$memberinfo['province_now']] . $areaList[$memberinfo['city_now']];
        $memberinfo['zcze'] = $memberinfo['account_money'] + $memberinfo['back_money'] + $memberinfo['money_collect'] + $memberinfo['money_freeze'];
        $this->assign("minfo", $memberinfo);

        //data_list
        $data_list = M("member_data_info")->field('type,add_time,count(status) as num,sum(deal_credits) as credits')->where("uid={$borrowinfo['borrow_uid']} AND status=1")->group('type')->select();
        $this->assign("data_list", $data_list);
        //data_list

        // 投资记录
        $this->investRecord($id);
        $this->assign('borrow_id', $id);
        $version = FS("Webconfig/version");
        if($version['mobile'] == 1 OR $version['wechat'] == 1) {
            $is_mobile = 1;
            $this->assign('is_mobile',$is_mobile);
        }
        
        //近期还款的投标
        //$time1 = microtime(true)*1000;
        $history = getDurationCount($borrowinfo['borrow_uid']);
        $this->assign("history", $history);

        //investinfo
        $fieldx = "bi.investor_capital,bi.add_time,m.user_name,bi.is_auto";
        $investinfo = M("borrow_investor bi")->field($fieldx)->join("{$pre}members m ON bi.investor_uid = m.id")->limit(10)->where("bi.borrow_id={$id}")->order("bi.id DESC")->select();
        $this->assign("investinfo", $investinfo);
        //investinfo

        //帐户资金情况
        $this->assign("investInfo", getMinfo($this->uid, true));
        $this->assign("mainfo", getMinfo($borrowinfo['borrow_uid'], true));
        $this->assign("capitalinfo", getMemberBorrowScan($borrowinfo['borrow_uid']));
        //帐户资金情况

        //上传资料类型
        $upload_type = FilterUploadType(FS("Webconfig/integration"));
        $this->assign("upload_type", $upload_type); // 上传资料所有类型

        //获得几天收益
        $day = TborrowModel::get_remain_transfer_days($id, 1);
        $this->assign("day", $day);

        //当前标种10w的利息
        $rge = getBorrowInterest($borrowinfo['backmoney_type'], 100000, $borrowinfo['borrow_duration'], $borrowinfo['borrow_interest_rate'], $borrowinfo['duration_unit_type']);
        $this->assign("rge", getFloatValue($rge, 2));

        //投标特殊奖励
        $special_award = ExpandMoneyModel::get_special_award($id);
        $this->assign('special_award', $special_award);
        //评论
        $cmap['tid'] = $id;
        $clist = getCommentList($cmap, 5);
        $this->assign('unlogin_home', DOMAIN . '/login?redirectUrl=' . rawurlencode(DOMAIN . $_SERVER['REQUEST_URI']));
        $this->assign("Bconfig", $Bconfig);
        $this->assign("gloconf", $this->gloconf);
        $this->assign("commentlist", $clist['list']);
        $this->assign("commentpagebar", $clist['page']);
        $this->assign("commentcount", $clist['count']);
        $this->display();
    }

    /**
     *
     */
    public function fram()
    {
        $id = intval($_GET['id']);
        $borrowinfo = TborrowModel::get_format_borrow_info($id, "b.*, bwd.*, bd.bianhao");
        $this->assign("vo", $borrowinfo);
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
        if (1 > $this->uid) {
            ajaxmsg("请先登录", 0);
        }
        $id = intval($_POST['T_borrow_id']);
        $money = intval($_POST['transfer_invest_num']); // 按金额投资

        $coupon_ids = filter_array($_POST['coupon']); // 使用的优惠券id array();
        $discount_money = 0;
        //判断是不是体验标
        $is_taste = M("borrow_info")->getFieldById($id, "is_taste");
        // 如果使用优惠券
        if (!empty($coupon_ids)) {
            $expand_money = ExpandMoneyModel::get_discount_money($coupon_ids, $money, $this->uid, $is_taste);
            if ($expand_money === false) {
                $this->error('非法请求');
            } else {
                $discount_money = $expand_money['discount_money'];
            }
        }
        if( $money <= $discount_money ) {
            $this->error("使用优惠券总额不能高于（或等于）投资金额");
        }
        // 实际需要支付的金额 = 投资金额 - 使用优惠券的金额
        $actual_money = $money - $discount_money;
        if ($actual_money < 0) $actual_money = 0;
        $re = chkInvest($id, $this->uid, $money, $msg, text($_POST['T_pin']), text($_POST['borrow_pass']));
        if ($re !== 1) {
            $this->error($msg);
        }
        $re = false;
        $borrow_status = M("borrow_info")->getFieldById($id, "borrow_status");
        if (intval($borrow_status) === 2) {
            $re = TinvestMoney($this->uid, $id, $money, false, 0, 5, 1, $coupon_ids);
        }
        if ($re === true) {
            $arr['status'] = 4;
            $arr['loanno'] = $id;
            $arr['use_time'] = time();
            $arr['coupon_id'] = ",{$coupon_ids},";
            M('expand_money')->where(array('id' => array('in', $coupon_ids), 'uid' => $this->uid))->save($arr);
            $this->success("恭喜成功投标{$money}元,优惠券抵押{$discount_money}元，实际付款{$actual_money}元");
        } else if (empty($re) === false) {
            $this->error($done);
        } else {
            $this->error("对不起，投标失败，请重试!");
        }
    }

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

    public function jubao()
    {
        if ($_POST['checkedvalue']) {
            $data['reason'] = text($_POST['checkedvalue']);
            $data['text'] = text($_POST['thecontent']);
            $data['uid'] = $this->uid;
            $data['uemail'] = text($_POST['uemail']);
            $data['b_uid'] = text($_POST['b_uid']);
            $data['b_uname'] = text($_POST['theuser']);
            $data['add_time'] = time();
            $data['add_ip'] = get_client_ip();
            $newid = M('jubao')->add($data);
            if ($newid) exit("1");
            else exit("0");
        } else {
            $id = intval($_GET['id']);
            $u['id'] = $id;
            $u['uname'] = M('members')->getFieldById($id, "user_name");
            $u['uemail'] = M('members')->getFieldById($this->uid, "user_email");
            $this->assign("u", $u);
            $data['content'] = $this->fetch("Public:jubao");
            exit(json_encode($data));
        }
    }

    public function ajax_invest()
    {
        if (1 > $this->uid) {
            ajaxmsg("请先登录", 0);
        }
        $id = intval($_GET['id']);
        $num = intval($_GET['num']);
        $binfo = TborrowModel::get_format_borrow_info($id);
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
        $binfo['need_num'] = $binfo['borrow_money'] - $binfo['has_borrow'];
        $minfo = getMinfo($this->uid, "m.pin_pass, mm.account_money, mm.back_money, mm.money_collect");
        if ($this->uid == $binfo['borrow_uid']) {
            ajaxmsg("不能去投自己的标", 0);
        }
        if ($binfo['borrow_status'] <> 2) {
            ajaxmsg("只能投正在借款中的标", 0);
        }
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
            //->limit('3')
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

        $pin_pass = $minfo['pin_pass'];
        $has_pin = (empty($pin_pass) === true) ? "no" : "yes";

        $borrowinfo = $binfo;
        $qiye = TborrowModel::get_borrow_info($id, 'duration_unit');

        // 到期总回款
        $borrowInterest = getBorrowInterest($borrowinfo['backmoney_type'], $num, $borrowinfo['borrow_duration'], $borrowinfo['borrow_interest_rate'], $qiye['duration_unit']);
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
        //exit($data['content']);
        ajaxmsg($data);
    }

    public function getarea()
    {
        $rid = intval($_GET['rid']);
        if (empty($rid)) {
            $data['NoCity'] = 1;
            exit(json_encode($data));
        }
        $map['reid'] = $rid;
        $alist = M('area')->field('id,name')->order('sort_order DESC')->where($map)->select();

        if (count($alist) === 0) {
            $str = "<option value=''>--该地区下无下级地区--</option>\r\n";
        } else {
            if ($rid == 1) $str .= "<option value='0'>请选择省份</option>\r\n";
            foreach ($alist as $v) {
                $str .= "<option value='{$v['id']}'>{$v['name']}</option>\r\n";
            }
        }
        $data['option'] = $str;
        $res = json_encode($data);
        echo $res;
    }

    public function addfriend()
    {
        if (!$this->uid) ajaxmsg("请先登陆", 0);
        $fuid = intval($_POST['fuid']);
        $type = intval($_POST['type']);
        if (!$fuid || !$type) ajaxmsg("提交的数据有误", 0);

        $save['uid'] = $this->uid;
        $save['friend_id'] = $fuid;
        $vo = M('member_friend')->where($save)->find();

        if ($type == 1) {//加好友
            if ($this->uid == $fuid) ajaxmsg("您不能对自己进行好友相关的操作", 0);
            if (is_array($vo)) {
                if ($vo['apply_status'] == 3) {
                    $msg = "已经从黑名单移至好友列表";
                    $newid = M('member_friend')->where($save)->setField("apply_status", 1);
                } elseif ($vo['apply_status'] == 1) {
                    $msg = "已经在你的好友名单里，不用再次添加";
                } elseif ($vo['apply_status'] == 0) {
                    $msg = "已经提交加好友申请，不用再次添加";
                } elseif ($vo['apply_status'] == 2) {
                    $msg = "好友申请提交成功";
                    $newid = M('member_friend')->where($save)->setField("apply_status", 0);
                }
            } else {
                $save['uid'] = $this->uid;
                $save['friend_id'] = $fuid;
                $save['apply_status'] = 0;
                $save['add_time'] = time();
                $newid = M('member_friend')->add($save);
                $msg = "好友申请成功";
            }
        } elseif ($type == 2) {//加黑名单
            if ($this->uid == $fuid) ajaxmsg("您不能对自己进行黑名单相关的操作", 0);
            if (is_array($vo)) {
                if ($vo['apply_status'] == 3) $msg = "已经在黑名单里了，不用再次添加";
                else {
                    $msg = "成功移至黑名单";
                    $newid = M('member_friend')->where($save)->setField("apply_status", 3);
                }
            } else {
                $save['uid'] = $this->uid;
                $save['friend_id'] = $fuid;
                $save['apply_status'] = 3;
                $save['add_time'] = time();
                $newid = M('member_friend')->add($save);
                $msg = "成功加入黑名单";
            }
        }
        if ($newid) ajaxmsg($msg);
        else ajaxmsg($msg, 0);
    }


    public function innermsg()
    {
        if (!$this->uid) ajaxmsg("请先登陆", 0);
        $fuid = intval($_GET['uid']);
        if ($this->uid == $fuid) ajaxmsg("您不能对自己进行发送站内信的操作", 0);
        $this->assign("touid", $fuid);
        $data['content'] = $this->fetch("Public:innermsg");
        ajaxmsg($data);
    }

    public function doinnermsg()
    {
        $touid = intval($_POST['to']);
        $msg = text($_POST['msg']);
        $title = text($_POST['title']);
        $newid = addMsg($this->uid, $touid, $title, $msg);
        if ($newid) ajaxmsg();
        else ajaxmsg("发送失败", 0);

    }

    /**
     * ajax 获取投资记录
     *
     */
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
            $list = M("borrow_investor as b")
                ->join(C(DB_PREFIX) . "members as m on b.investor_uid = m.id")
                ->join(C(DB_PREFIX) . "borrow_info as i on b.borrow_id = i.id")
                ->field('i.borrow_interest_rate,i.repayment_type, b.investor_capital, b.add_time, b.is_auto, b.source, m.user_name, i.borrow_duration, i.duration_unit')
                ->where('b.borrow_id=' . $borrow_id)->order('b.id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
            $string = '';
            foreach ($list as $k => $v) {
                $source = BorrowInvestorModel::get_invest_source($v['source']);
                $repayment_type = ($v['duration_unit'] == BorrowModel::BID_CONFIG_DURATION_UNIT_MONTH) ? "个月" : "天";
                $string .= "<tr>
                          <td>" . hidecard($v['user_name'], 4) . "</td>
                          <td>" . $v['borrow_interest_rate'] . "%</td>
                          <td>" . date('Y-m-d H:i:s', $v['add_time']) . "</td>
                          <td>" . Fmoney($v['investor_capital']) . "元</td>";
                if ($v['is_auto'] == 0) {
                    $string .= "<td>手动</td>";
                } else {
                    $string .= "<td>自动</td>";
                }
                if($is_mobile){
                    $string .="<td>".$source ."</td></tr>";
                }
            }
            echo empty($string) ? '<tr><td colspan=5>暂时没有投资记录</td></tr>' : $string;
        }

    }

}
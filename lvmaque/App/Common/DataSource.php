<?php

//获取特定栏目下文章列表
function getAreaArticleList($parm)
{
    if (empty($parm['type_id'])) return;
    $map['type_id'] = $parm['type_id'];
    $Osql = "id DESC";
    $field = "id,title,art_set,art_time,art_url,area_id";
    //查询条件
    if ($parm['pagesize']) {
        //分页处理
        import("ORG.Util.Page");
        $count = M('article_area')->
        where($map)->count('id');
        $p = new Page($count, $parm['pagesize']);
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
    } else {
        $page = "";
        $Lsql = "LIMIT {$parm['limit']}";
    }

    $data = M('article_area')->field($field)->where($map)->order($Osql)->limit($Lsql)->select();

    $suffix = C("URL_HTML_SUFFIX");
    $typefix = get_type_leve_area_nid($map['type_id'], $parm['area_id']);

    $typeu = implode("/", $typefix);
    foreach ($data as $key => $v) {
        if ($v['art_set'] == 1) $data[$key]['arturl'] = (stripos($v['art_url'], "http://") === false) ? "http://" . $v['art_url'] : $v['art_url'];
        //elseif(count($typefix)==1) $data[$key]['arturl'] =
        else $data[$key]['arturl'] = MU("Home/{$typeu}", "article", array("id" => "id-" . $v['id'], "suffix" => $suffix));
    }
    $row = array();
    $row['list'] = $data;
    $row['page'] = $page;

    return $row;
}

//获取下级或者同级栏目列表
function getAreaTypeList($parm)
{
    //if(empty($parm['type_id'])) return;
    $Osql = "sort_order DESC";
    $field = "id,type_name,type_set,add_time,type_url,type_nid,parent_id,area_id";
    //查询条件
    $Lsql = "{$parm['limit']}";
    $pc = D('Aacategory')->where("parent_id={$parm['type_id']} AND area_id={$parm['area_id']}")->count('id');
    if ($pc > 0) {
        $map['is_hiden'] = 0;
        $map['parent_id'] = $parm['type_id'];
        $map['area_id'] = $parm['area_id'];
        $data = D('Aacategory')->field($field)->where($map)->order($Osql)->limit($Lsql)->select();
    } elseif (!isset($parm['notself'])) {
        $map['is_hiden'] = 0;
        $map['parent_id'] = D('Aacategory')->getFieldById($parm['type_id'], 'parent_id');
        $map['area_id'] = $parm['area_id'];
        $data = D('Aacategory')->field($field)->where($map)->order($Osql)->limit($Lsql)->select();
    }

    //链接处理
    $typefix = get_type_leve_area_nid($parm['type_id'], $parm['area_id']);
    $typeu = $typefix[0];
    $suffix = C("URL_HTML_SUFFIX");
    foreach ($data as $key => $v) {
        if ($v['type_set'] == 2) {
            if (empty($v['type_url'])) $data[$key]['turl'] = "javascript:alert('请在后台添加此栏目链接');";
            else $data[$key]['turl'] = $v['type_url'];
        } elseif ($v['parent_id'] == 0 && count($typefix) == 1) $data[$key]['turl'] = MU("Home/{$v['type_nid']}/index", "typelist", array("id" => $v['id'], "suffix" => $suffix));
        else $data[$key]['turl'] = MU("Home/{$typeu}/{$v['type_nid']}", "typelist", array("id" => $v['id'], "suffix" => $suffix));
    }
    $row = array();
    $row = $data;

    return $row;
}


/**
 * 统计借款信息（借款总额、放款笔数、已还总额、待还总额）
 *
 */
function loan_total_info()
{
    $info = array();
    $info['ordinary_total'] = M("borrow_info")->where("borrow_type<6 and borrow_status in(6,7,8,9)")->sum("borrow_money"); //普通标借款总额
    $info['num_total'] = M("borrow_info")->where("borrow_type<6 and borrow_status in(6,7,8,9)")->count("id"); // 普通标总笔数
    $info['has_also'] = M("borrow_info")->where("borrow_type<6 and borrow_status in (7,8,9)")->count("borow_money");    //已还款总额
    $info['arrears'] = M("borrow_info")->where("borrow_type<6 and borrow_status = 6")->count("borow_money");   //未还款总额

    //企业直投汇总信息
    $borrow_type = BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID;
    $success_status = BorrowInvestorModel::BID_INVEST_STATUS_SUCCESS; // 已完成
    $transfer_total_money = M('borrow_investor')->where(array('borrow_type' => $borrow_type))->count('investor_capital');  //总借出
    $transfer_also_money = M('borrow_investor')->where(array('borrow_type' => $borrow_type, 'status' => $success_status))->count('investor_capital'); //已还款
    $transfer_arrears_money = M('borrow_investor')->where('borrow_type=6 and status<5')->count('investor_capital'); //未还款
    $transfer_num_total = M('borrow_info')->where(array('borrow_type' => $borrow_type))->count('id'); //总数

    $info['ordinary_total'] += $transfer_total_money;  //借款总额
    $info['has_also'] += $transfer_also_money; //已还款总额
    $info['arrears'] += $transfer_arrears_money;  //未还款总额
    $info['num_total'] += $transfer_num_total;  //借款笔数
    return $info;
}

/**
 * 获取用户投资收益汇总
 * （净赚利息、投标奖励、推广奖励、续投奖励、线下充值奖励、收入总和、代收利息）
 *
 * @param int $uid //用户ID
 * 修改人：赵辉  时间：20150309
 * 修改说明：去掉统计直投标，原代码重复统计直投标
 */
function get_personal_benefit($uid)
{
    $uid = intval($uid);
    $total = array();
    //统计回款利息interest、回款总额capital、利息手续费fee
    $investor = M("investor_detail")
        ->field("sum(receive_capital) as capital, sum(receive_interest) as interest, sum('interest_fee') as fee")
        ->where('investor_uid=' . $uid)->find();
    $investor['interest'] -= $investor['fee'];

    // 统计企业直投回款利息interest、回款总额capital、利息手续费fee
    //$transfer_investor = InvestorDetailModel::get_transfer_investor_detail_account('d.investor_uid='.$uid);
    //$transfer_investor['interest'] -= $transfer_investor['fee'];   //减去管理费

    //投资奖励 推广奖励  续投奖励 线下充值奖励
    $log = get_money_log($uid);

    $benefit['ireward'] = $log['20']['money'] + $log['41']['money'];     // 投标奖励
//    $benefit['spread_reward'] = $log['13']['money'];  //推广奖励 删除续投奖励(现奖励积分) minister 11.10
    $benefit['re_reward'] = $log['32']['money'];  // 线下充值
    $benefit['interest'] = $investor['interest']; //净赚利息
    $benefit['capital'] = $investor['capital']; // 回款总额
    $benefit['agility_income'] = BaoInvestModel::get_sum_interest($uid);
    $benefit['fee'] = $investor['fee'];
    //累计赚取收益
    $benefit['total'] = $benefit['ireward'] + $benefit['spread_reward'] + $benefit['con_reward'] + $benefit['re_reward'] + $benefit['interest'] + $benefit['agility_income'];

    $pre = C('DB_PREFIX');
    //待收利息
    $interest_collection = M('investor_detail d')
        ->join("{$pre}debt t ON t.invest_id=d.invest_id")
        ->field('sum(d.interest) as interest, sum(d.capital) as capital,sum(interest_fee) as fee')
        ->where("(d.investor_uid={$uid} and d.status in (6,7)) or (d.investor_uid={$uid} and d.status = 14 and t.status =2)")
        ->find();
    //$fields = 'sum(interest) as interest, sum(capital) as capital,sum(interest_fee) as fee';
    //$transfer_interest_collection = InvestorDetailModel::get_transfer_investor_detail_account("d.investor_uid={$uid} and d.status = 7", $fields);
    $benefit['interest_collection'] = $interest_collection['interest'] - $interest_collection['fee'];//dai shou ben xi, 扣除利息管理费
    $benefit['capital_collection'] = $interest_collection['capital']; // dai shou ben jin
    //灵活宝的待收本金，待收利息
    $agility_interest = BaoInvestModel::get_collect_money($uid);
    $benefit['interest_collection'] += $agility_interest['collect_interest'];
    $agility_money = BaoInvestModel::get_sum_money($uid);
    $benefit['capital_collection'] += $agility_money;
    return $benefit;
}

function get_money_log($uid)
{
    $uid = intval($uid);
    $log = array();
    if ($uid) {
        $list = M("member_moneylog")->field('type,sum(affect_money) as money')->where("uid={$uid}")->group('type')->select();
    } else {
        $list = M("member_moneylog")->field('type,sum(affect_money) as money')->group('type')->select();
    }

    foreach ($list as $v) {
        $log[$v['type']]['money'] = ($v['money'] > 0) ? $v['money'] : $v['money'] * (-1);
        $log[$v['type']]['name'] = $name[$v['type']];
    }
    return $log;
}

/**
 *   用户借款支出汇总
 * 、支付投标奖励、支付利息、提现手续费、借款管理费、会员及认证费用、逾期及催收费用 、 支出总和、待付利息总额
 *
 * @param mixed $uid //用户id
 */
function get_personal_out($uid)
{
    $log = get_money_log($uid);
    $out['borrow_manage'] = $log['18']['money']; //借款管理费
    $out['pay_tender'] = $log['21']['money'] + $log['42']['money'];                   //支付投标奖励
    $out['overdue'] = $log['30']['money'] + $log['31']['money'];//逾期催收
    $out['call_fee'] = $log['31']['money']; //崔收费
    $out['expired_money'] = $interest_pay['expired_money']; //罚金
    $out['authenticate'] = $log['14']['money'] + $log['22']['money'] + $log['25']['money'] + $log['26']['money'];// 认证费用

    $interest = M("investor_detail")
        ->field('sum(receive_capital) as capital, sum(receive_interest) as interest')
        ->where("borrow_uid={$uid} and status in (1,2,3,4,5)")
        ->find();

    $out['interest'] = $interest['interest'];   //支付利息
    $out['capital'] = $interest['capital']; // 已还本金

    //待付利息\本金
    $interest_pay = M('investor_detail')
        ->field('sum(interest) as interest, sum(capital) as capital')
        ->where("borrow_uid={$uid} and status in (6,7)")
        ->find();
    $out['interest_pay'] = $interest_pay['interest']; //待还利息
    $out['capital_pay'] = $interest_pay['capital']; //待还金额


    $czfee = M('member_payonline')->where("uid={$uid} AND status=1")->sum('fee');//在线充值手续费 
    $out['czfee'] = $czfee;
    //print_r($out);                    
    $withdraw = M('member_withdraw')->field('sum(second_fee) as fee, sum(withdraw_money) as withdraw_money')->where("uid={$uid} and withdraw_status=2")->find();
    $out['withdraw_fee'] = $withdraw['fee']; //提现手续费
    $out['withdraw_money'] = $withdraw['withdraw_money'];//提现金额

    $out['total'] = $out['interest'] + $out['borrow_manage'] + $out['pay_tender'] + $out['overdue'] + $out['authenticate'] + $out['withdraw_fee'];
    return $out;

}

/**
 * 累计投资金额 \累计款金额\累计充值金额\累计提现金额\累计支付佣金
 *
 * @param mixed $uid
 */
function get_personal_count($uid)
{
    $uid = intval($uid);
    $count = array();
    //*********累计投资金额************
    $p_ljtz = M('borrow_investor')->where("borrow_type in (1,2,3,4,5) and investor_uid={$uid} and status in (4,5,6,7)")->sum('investor_capital');
    $t_ljtz = M('borrow_investor')->where("borrow_type in(6,7) and investor_uid={$uid}")->sum('investor_capital');//加上定投宝
    //灵活宝投资总额
    $agility_invest = BaoInvestModel::get_invest_money($uid);
    $count['ljtz'] = $p_ljtz + $t_ljtz + $agility_invest;
    //**************
    //累计借入金额
    $p_jrje = M('borrow_info')->where("borrow_uid={$uid} and borrow_status in (6,7,8,9,10)")->sum('borrow_money');
    $count['jrje'] = $p_jrje;
    //****************
    //*****累计充值金额***
    $payonline = M('member_payonline')->where("uid={$uid} AND status=1")->sum('money');//累计充值金额 
    $count['payonline'] = $payonline;
    //*****************
    //累计提现金额
    $withdraw = M('member_withdraw')
        ->where("uid={$uid} and withdraw_status=2")
        ->sum('withdraw_money');
    $count['withdraw'] = $withdraw;
    //***************
    //  累计支付佣金  包括借款管理费、投资手续费
    $interest_fee = M('investor_detail')->where('investor_uid=' . $uid . ' and status in (1,2,3,4,5)')->sum('interest_fee'); // 普通标投资管理费（统计还款后的管理费）
    //$transfer_interest_fee = InvestorDetailModel::get_transfer_investor_detail_account("d.investor_uid={$uid} and d.status = 1", "sum(interest_fee) as interest_fee");  //企业直投投资管理费（统计还款后的管理费） 去掉重复统计 zhaohui time：20150309

    $borrow_fee = M('borrow_info')->where("borrow_uid={$uid} AND borrow_status in(6,7,8,9,10)")->sum('borrow_fee');  // 借款管理费 （统计复审通过后的管理费）
    $count['commission'] = $interest_fee + $borrow_fee; //累积支付佣金
    $count['interest_fee'] = $interest_fee;
    $count['borrow_fee'] = $borrow_fee;
    //*********************************
    return $count;

}

/**
 * 借款参数\累计款金额\累计充值金额\累计提现金额\累计支付佣金
 *
 * @param mixed $uid
 */

function get_bconf_setting()
{
    $bconf = array();
    if (!S('bconf_setting')) {
        $borrowconfig = require C("ROOT_URL") . "Webconfig/borrowconfig.php";
        $bconf = $borrowconfig;

        S('bconf_setting', $bconf);
        S('bconf_setting', $bconf, 3600 * C('TTXF_TMP_HOUR'));
    } else {
        $bconf = S('bconf_setting');
    }

    return $bconf;
}

/**
 * 密保问题\公司行业\公司规模\学历\收入\紧急联系人关系\证件类型\
 * @param mixed
 */
function get_basic()
{
    $bconf = array();
    if (!S('basic_set')) {
        $borrowconfig = require C("ROOT_URL") . "Webconfig/basic.php";
        $bconf = $borrowconfig;

        S('basic_set', $bconf);
        S('basic_set', $bconf, 3600 * C('TTXF_TMP_HOUR'));
    } else {
        $bconf = S('basic_set');
    }

    return $bconf;
}

/**
 * 标种小图标展示
 *
 * @param mixed
 */
function getIco($map)
{
    $str = "";
    if ($map['borrow_type'] == 2) $str .= '<img src="' . __ROOT__ . '/Style/H/images/icon/dan.png" align="absmiddle">'. '&nbsp;';
    elseif ($map['borrow_type'] == 3) $str .= '<img src="' . __ROOT__ . '/Style/H/images/icon/miao.png" align="absmiddle">'. '&nbsp;';
    elseif ($map['borrow_type'] == 4) $str .= '<img src="' . __ROOT__ . '/Style/H/images/icon/jing.png" align="absmiddle">'. '&nbsp;';
    elseif ($map['borrow_type'] == 1) $str .= '<img src="' . __ROOT__ . '/Style/H/images/icon/xin.png" align="absmiddle">'. '&nbsp;';
    elseif ($map['borrow_type'] == 5) $str .= '<img src="' . __ROOT__ . '/Style/H/images/icon/di.png" align="absmiddle">'. '&nbsp;';
//     elseif ($map['borrow_type'] == 6) $str .= '<img src="' . __ROOT__ . '/Style/H/images/icon/lbt.gif" align="absmiddle">';
    if ($map['is_xinshou'] == 1) $str .= '<img src="' . __ROOT__ . '/Style/H/images/icon/xiin.png" align="absmiddle">'. '&nbsp;';
    if ($map['is_taste'] == 1) $str .= '<img src="' . __ROOT__ . '/Style/H/images/icon/ti.png" align="absmiddle">'. '&nbsp;';
    if ($map['repayment_type'] == 1) $str .= '<img src="' . __ROOT__ . '/Style/H/images/icon/tian.png" align="absmiddle">'. '&nbsp;';
    if (!empty($map['password'])) $str .= '<img src="' . __ROOT__ . '/Style/H/images/icon/passw.jpg" align="absmiddle">'. '&nbsp;';
    if ($map['is_tuijian'] == 1) $str .= '<img src="' . __ROOT__ . '/Style/H/images/icon/tuijian.jpg" align="absmiddle">'. '&nbsp;';
    if ($map['reward_type'] > 0 && ($map['reward_num'] > 0 || $map['reward_money'] > 0)) $str .= '<img src="' . __ROOT__ . '/Style/H/images/icon/jiang.png" align="absmiddle">'. '&nbsp;';
    return $str . '&nbsp;';
}

/*
 * @mobile int 某些手机不支持js的eval方法，mobile用来区分
 */
function ajaxmsg($msg = "", $type = 1, $is_end = true ,$mobile = 0)
{
    $json['status'] = $type;
    if (is_array($msg)) {
        foreach ($msg as $key => $v) {
            if ($v === null) $v = '';
            $json[$key] = $v;
        }
    } elseif (!empty($msg)) {
        $json['message'] = $msg;
    }
    if ($is_end) {
        if($mobile != 1){
          echo json_encode($json);
          exit;
        }else{
          header('Content-Type:application/json; charset=utf-8');
          exit(json_encode($json));
        }
    } else {
        echo json_encode($json);
        exit;
    }
}

/**
 * 字段文字内容隐藏处理方法
 * 如果姓名为3个字符，前面显示1个，后面显示1个，其它隐藏
 * @param $cardnum
 * @param int $type
 * @param string $default
 * @return string
 */
function hidecard($cardnum, $type = 1, $default = "")
{
    if (empty($cardnum)) return $default;
    if ($type == 1) $cardnum = substr($cardnum, 0, 3) . str_repeat("*", 12) . substr($cardnum, strlen($cardnum) - 4);   //身份证
    elseif ($type == 2) $cardnum = substr($cardnum, 0, 3) . str_repeat("*", 4) . substr($cardnum, strlen($cardnum) - 4);    //手机号
    elseif ($type == 3) $cardnum = str_repeat("*", strlen($cardnum) - 4) . substr($cardnum, strlen($cardnum) - 4);    //银行卡
    elseif ($type == 4) {
        $cardnum = mb_substr($cardnum, 0, 3, 'UTF-8') . str_repeat("*", strlen($cardnum) - 3);   //用户名
    } elseif ($type == 5) {
        $mb_str = mb_strlen($cardnum, 'UTF-8');
        if ($mb_str <= 6) {
            $suffix = mb_substr($cardnum, $mb_str - 1, 1, 'UTF-8');
            $cardnum = mb_substr($cardnum, 0, 1, 'UTF-8') . str_repeat("*", 3) . $suffix;    //新用户名,无乱码截取
        } else {
            $suffix = mb_substr($cardnum, $mb_str - 3, 3, 'UTF-8');
            $cardnum = mb_substr($cardnum, 0, 3, 'UTF-8') . str_repeat("*", 3) . $suffix;    //新用户名,无乱码截取
        }
    } elseif ($type == 6) {
        $str = explode("@", $cardnum);
        $cardnum = substr($str[0], 0, 2) . str_repeat("*", strlen($str[0]) - 2) . "@" . $str[1];  //邮箱
    } elseif ($type == 7) $cardnum = mb_substr($cardnum, 0, 1, 'utf-8') . str_repeat("*", 3);    //真实姓名
    //elseif($type==7) $cardnum = substr($cardnum,0,3).str_repeat("*",3);    //真实姓名
    elseif ($type == 8) $cardnum = substr($cardnum, 6, 4) . "-" . substr($cardnum, 10, 2) . "-" . substr($cardnum, 12, 2);    //出生日期
    elseif ($type == 9) {
        if (empty($cardnum)) {
            $cardnum = "";
        } else $cardnum = date('Y', time()) - substr($cardnum, 6, 4) . "岁";    //年龄
    } elseif ($type == 10) $cardnum = str_repeat("*", (strlen($cardnum) - 1) / 3) . mb_substr($cardnum, -1, 1, 'utf-8');    //紧急联系人姓名
    elseif ($type == 11) {
        $num = substr($cardnum, -2, 1);
        if ($num % 2 == 0) {
            $cardnum = "女";
        } else {
            $cardnum = "男";
        }
    }
    return $cardnum;
}

/**
 *
 * @param unknown $type
 * @return string $cardnum
 */
function headimg($cardnum, $type = 1, $default = "")
{
    if (empty($cardnum)) return $default;
    $arr = end(explode(".", $cardnum));
    if ($type == 1) return $cardnum;   //原图
    elseif ($type == 2) $cardnum .= '_48x48.' . $arr;   //48*48
    elseif ($type == 3) $cardnum .= '_120x120.' . $arr;   //120*120
    elseif ($type == 4) $cardnum .= '_200x200.' . $arr;   //200*200
    return $cardnum;
}

function setmb($size)
{
    $mbsize = $size / 1024 / 1024;
    if ($mbsize > 0) {
        list($t1, $t2) = explode(".", $mbsize);
        $mbsize = $t1 . "." . substr($t2, 0, 2);
    }

    if ($mbsize < 1) {
        $kbsize = $size / 1024;
        list($t1, $t2) = explode(".", $kbsize);
        $kbsize = $t1 . "." . substr($t2, 0, 2);
        return $kbsize . "KB";
    } else {
        return $mbsize . "MB";
    }

}

function getMoneyFormt($money)
{
    if ($money >= 100000 && $money <= 100000000) {
        $res = getFloatValue(($money / 10000), 2) . "万";
    } else if ($money >= 100000000) {
        $res = getFloatValue(($money / 100000000), 2) . "亿";
    } else {
        $res = getFloatValue($money, 0);
    }
    return $res;
}


function getArea()
{
    $area = FS("Webconfig/area");
    if (!is_array($area)) {
        $list = M("area")->getField("id,name");
        FS("area", $list, "Webconfig/");
    } else {
        return $area;
    }
}

//信用等级图标显示
function getLeveIco($num, $type = 1)
{
    $leveconfig = FS("Webconfig/leveconfig");

    foreach ($leveconfig as $key => $v) {
        if ($num >= $v['start'] && $num <= $v['end']) {
            if ($type == 1) {
                return "/UF/leveico/" . $v['icoName'];
            } elseif ($type == 2) {
                return '<a  target="_blank" href="' . __APP__ . '/member/credit#fragment-1"><img src="' . __ROOT__ . '/UF/leveico/' . $v['icoName'] . '" title="' . $v['name'] . '"/></a>';
            } elseif ($type == 3) {
                return '<a href="' . __APP__ . '/member/credit#fragment-1">' . $v['name'] . '</a>';//手机版使用
            } elseif ($type == 4) { // 数字表示等级，最小等级为1
                return $key;
            } else {
                return '<a href="' . __APP__ . '/member/credit#fragment-1"><img src="' . __ROOT__ . '/UF/leveico/' . $v['icoName'] . '" title="' . $v['name'] . '"/></a>';
            }

        }
    }

}

//投资等级图标显示
function getInvestLeveIco($num, $type = 1)
{
    $leveconfig = FS("Webconfig/leveinvestconfig");
    foreach ($leveconfig as $key => $v) {
        if ($num >= $v['start'] && $num <= $v['end']) {
            if ($type == 1) {
                return "/UF/leveico/" . $v['icoName'];
            } elseif ($type == 2) {
                return '<a target="_blabk" href="' . __APP__ . '/member/credit#fragment-2"><img src="' . __ROOT__ . '/UF/leveico/' . $v['icoName'] . '" title="' . $v['name'] . '"/></a>';
            } elseif ($type == 3) {
                return $v['name'];//手机版使用
            } else {
                return '<a href="' . __APP__ . '/member/credit#fragment-2"><img src="' . __ROOT__ . '/UF/leveico/' . $v['icoName'] . '" title="' . $v['name'] . '"/></a>';
            }
        }
    }
}

function getAgeName($num)
{
    $ageconfig = FS("Webconfig/ageconfig");
    foreach ($ageconfig as $key => $v) {
        if ($num >= $v['start'] && $num <= $v['end']) {
            return $v['name'];
        }
    }
}

function getLocalhost()
{
    $vo['id'] = 1;
    $vo['name'] = "主站";
    $vo['domain'] = "www";
    return $vo;
}

function Fmoney($money)
{
    if (!is_numeric($money)) return "￥0.00";
    $sb = "";
    if ($money < 0) {
        $sb = "-";
        $money = $money * (-1);
    }

    $dot = explode(".", $money);
    $tmp_money = strrev_utf8($dot[0]);
    $format_money = "";
    for ($i = 3; $i < strlen($dot[0]); $i += 3) {
        $format_money .= substr($tmp_money, 0, 3) . ",";
        $tmp_money = substr($tmp_money, 3);
    }
    $format_money .= $tmp_money;
    if (empty($sb)) $format_money = "￥" . strrev_utf8($format_money);
    else $format_money = "￥-" . strrev_utf8($format_money);
    if ($dot[1]) return $format_money . "." . $dot[1];
    else return $format_money;
}

function strrev_utf8($str)
{
    return join("", array_reverse(
        preg_split("//u", $str)
    ));
}

/**
 * @param int $id
 * @param int $type 借款标类型
 * @return string
 */
function getInvestUrl($id, $type = '')
{
    if ($type == '6') {
        return __APP__ . "/tinvest/{$id}" . C('URL_HTML_SUFFIX');
    } elseif ($type == '7') {
        return __APP__ . "/fund/{$id}" . C('URL_HTML_SUFFIX');
    } else {
        return __APP__ . "/invest/{$id}" . C('URL_HTML_SUFFIX');
    }
}



function getFundUrl($id)
{
    return __APP__ . "/fund/{$id}" . C('URL_HTML_SUFFIX');
}

//获取管理员ID对应的名称,以id为键
function get_admin_name($id = false)
{
    $stype = "adminlist";
    $list = array();
    if (!S($stype)) {
        $rule = M('ausers')->field('id,user_name')->select();
        foreach ($rule as $v) {
            $list[$v['id']] = $v['user_name'];
        }

        S($stype, $list, 3600 * C('HOME_CACHE_TIME'));
        if (!$id) $row = $list;
        else $row = $list[$id];
    } else {
        $list = S($stype);
        if ($id === false) $row = $list;
        else $row = $list[$id];
    }
    return $row;
}


//添加会员操作记录
function addMsg($from, $to, $title, $msg, $type = 1)
{
    if (empty($from) || empty($to)) return;
    $data['from_uid'] = $from;
    $data['from_uname'] = M('members')->getFieldById($from, "user_name");
    $data['to_uid'] = $to;
    $data['to_uname'] = M('members')->getFieldById($to, "user_name");
    $data['title'] = $title;
    $data['msg'] = $msg;
    $data['add_time'] = time();
    $data['is_read'] = 0;
    $data['type'] = $type;
    $newid = M('member_msg')->add($data);
    return $newid;
}

//注册专用
function rand_string_reg($len = 6, $type = '1', $utype = '1', $addChars = '')
{
    $str = '';
    switch ($type) {
        case 0:
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
            break;
        case 1:
            $chars = str_repeat('0123456789', 3);
            break;
        case 2:
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
            break;
        case 3:
            $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
            break;
        default :
            // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
            $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
            break;
    }
    if ($len > 10) {//位数过长重复字符串一定次数
        $chars = $type == 1 ? str_repeat($chars, $len) : str_repeat($chars, 5);
    }
    $chars = str_shuffle($chars);
    $str = substr($chars, 0, $len);
    session("code_temp", $str);
    session("send_time", time());

    return $str;
}

/**
 * 设置用户认证状态 处理表为members_status
 *
 * @param int $uid // 用户id
 * @param string $type // 类型的名字 结合数据库字段
 * @param int $status // 状态0 or 1
 * @param string $info //类别说明，用户保存增加积分说明
 */
function setMemberStatus($uid, $type, $status, $log_type, $log_info, $db = null)
{
    if ($db == null) {
        $db = M();
    }
    $uid = intval($uid);
    $status = intval($status);

    $type_status = $type . '_status';
    $type_credits = $type . '_credits';
    $integration = FS('Webconfig/integration');
    $credits = $integration[$type]['fraction'];
    $nid = 0;
    $insert_info = M('members_status')->field('uid,' . $type_status . ', ' . $type_credits)->where("uid='" . $uid . "'")->find();
    if (!$insert_info['uid']) {  //如果记录不存在
        if ($status === 1) {
            $nid = $db->table(C('DB_PREFIX') . 'members_status')->data(array('uid' => $uid, $type_status => $status, $type_credits => $credits))->add();
        } else {
            $nid = $db->table(C('DB_PREFIX') . 'members_status')->data(array('uid' => $uid, $type_status => $status))->add();
        }
    } else { //如果记录存在切积分不存在  判断状态是否为1（不给积分） 为0 （认为是第一次审核给积分）
        if ($insert_info[$type_credits] || $insert_info[$type_status] === 1 || $status === 2) { //状态为 1 or 积分已存在 or 修改状态为2
            $nid = $db->table(C('DB_PREFIX') . 'members_status')->data(array($type_status => $status))->where('uid=' . $uid)->save();
        } elseif ($status === 1) { //状态为 1 （通过送积分）
            $nid = $db->table(C('DB_PREFIX') . 'members_status')->data(array($type_status => $status, $type_credits => $credits))->where('uid=' . $uid)->save();
        } else {
            $nid = $db->table(C('DB_PREFIX') . 'members_status')->data(array($type_status => $status))->where('uid=' . $uid)->save();
        }
    }
    if ($nid !== false) {
        $db->commit();
    } else {
        $db->rollback();
    }

    if ($status === 1 && $nid) {
        memberCreditsLog($uid, $log_type, $credits, $log_info . "认证通过,奖励积分{$credits}", $db);
    }
    return $nid;
}

/**
 * 过滤上传资料类型
 *
 * @param array $arr // Webconfig/integration 文件
 */
function FilterUploadType($arr)
{
    $uploadType = array();
    if (is_array($arr)) {
        foreach ($arr as $key => $val) {
            if (is_numeric($key)) {
                $uploadType[$key] = $val;
            }
        }
    }
    return $uploadType;
}

/**
 * 获取当前用户没有上传过的上传资料类型
 *
 * @param int $uid // 用户id
 */
function get_upload_type($uid)
{
    $integration = FilterUploadType(FS("Webconfig/integration"));
    $uploadType = M('member_data_info')->field('type')->where("uid='{$uid}' and status in (0,1)")->select();
    $utype = M('members')->field('is_transfer')->where('id=' . $uid)->find();
    foreach ($integration as $key => $vo) {
        if ($vo['utype'] != $utype['is_transfer'] && $vo['utype'] != 3) {
            unset($integration[$key]);
        }
    }
    foreach ($uploadType as $row) {
        unset($integration[$row['type']]);
    }
    foreach ($integration as $key => $val) {
        $integration[$key] = $val['description'];
    }
    return $integration;
}

/**
 * 获取当前用户没有上传过的银行卡名称
 *
 * @param int $uid // 用户id
 */
function get_bank_type($uid)
{
    $info = get_bconf_setting();
    $integration = $info['BANK_NAME'];
    $uploadType = M('member_banks')->field('bank_name')->where("uid='{$uid}'")->select();
    foreach ($uploadType as $row) {
        unset($integration[$row['bank_name']]);
    }
    return $integration;
}

/****************************
 * /*  手机短信接口（漫道短信www.zucp.net）
 * /* 参数：$mob        手机号码
 * /*        $content    短信内容
 *****************************/
function sendsms($mob, $content)
{

    $msgconfig = FS("Webconfig/msgconfig");
    $type = $msgconfig['sms']['type'];// type=0 漫道短信接口
    if ($type == 0) {
        /////////////////////////////////////////漫道短信接口 开始/////////////////////////////////////////////////////////////
        //如果您的系统是utf-8,请转成GB2312 后，再提交、
        $flag = 0;
        //要post的数据
        $argv = array(
            'sn' => $msgconfig['sms']['user2'], ////替换成您自己的序列号
            'pwd' => $msgconfig['sms']['pass2'], //此处密码需要加密 加密方式为 md5(sn+password) 32位大写

            'mobile' => $mob,//手机号 多个用英文的逗号隔开 post理论没有长度限制.推荐群发一次小于等于10000个手机号
            'content' => iconv("UTF-8", "gb2312//IGNORE", $content),//短信内容
            'ext' => '',
            'stime' => '',//定时时间 格式为2011-6-29 11:09:21
            'rrid' => ''
        );
        //构造要post的字符串
        foreach ($argv as $key => $value) {
            if ($flag != 0) {
                $params .= "&";
                $flag = 1;
            }
            $params .= $key . "=";
            $params .= urlencode($value);
            $flag = 1;
        }
        $length = strlen($params);
        //创建socket连接
        $fp = fsockopen("sdk2.zucp.net", 8060, $errno, $errstr, 10) or exit($errstr . "--->" . $errno);
        //构造post请求的头
        $header = "POST /webservice.asmx/mt HTTP/1.1\r\n";
        $header .= "Host:sdk2.zucp.net\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . $length . "\r\n";
        $header .= "Connection: Close\r\n\r\n";
        //添加post的字符串
        $header .= $params . "\r\n";
        //发送post的数据
        fputs($fp, $header);
        $inheader = 1;
        while (!feof($fp)) {
            $line = fgets($fp, 1024); //去除请求包的头只显示页面的返回数据
            if ($inheader && ($line == "\n" || $line == "\r\n")) {
                $inheader = 0;
            }
            if ($inheader == 0) {
                // echo $line;
            }
        }
        $line = str_replace("
	<string xmlns=\"http://tempuri.org/\">", "", $line);
        $line = str_replace("</string>
	", "", $line);
        $result = explode("-", $line);
        if (count($result) > 1) {
            return true;
        } else {
            return true;
        }
        /////////////////////////////////////////漫道短信接口 结束/////////////////////////////////////////////////////////////
    } else {
        return true;
    }
}

//手机日志
function alogsm($type, $tid, $tstatus, $deal_info = '', $deal_user = '')
{
    $arr = array();
    $arr['type'] = $type;
    $arr['tid'] = $tid;
    $arr['tstatus'] = $tstatus;
    $arr['deal_info'] = $deal_info;

    $arr['deal_user'] = session("u_id");
    $arr['deal_ip'] = get_client_ip();
    $arr['deal_time'] = time();
    //dump($arr);exit;
    $newid = M("auser_dologs")->add($arr);
    return $newid;
}


//提取广告
function get_ad($id)
{
    $stype = "home_ad" . $id;
    if (!S($stype)) {
        $list = array();
        /*$condition['start_time']=array("lt",time());
        $condition['end_time']=array("gt",time());*/
        $condition['id'] = array('eq', $id);
        $_list = M('ad')->field('ad_type,content')->where($condition)->find();
        if ($_list['ad_type'] == 1) $_list['content'] = unserialize($_list['content']);
        $list = $_list;
        S($stype, $list, 3600 * C('HOME_CACHE_TIME'));
    } else {
        $list = S($stype);
    }

    if ($list['ad_type'] == 0 || !$list['content']) {
        if (!$list['content']) $list['content'] = "广告未上传或已过期";
        echo $list['content'];
    } else return $list['content'];
}


function getVerify($uid)
{
    $pre = C('DB_PREFIX');
    $vo = M("members m")
        ->field("m.id,m.pin_pass,s.id_status,s.phone_status,s.email_status")
        ->join("{$pre}members_status s ON s.uid=m.id")
        ->where("m.id={$uid}")
        ->find();
    $str = "";
    if ($vo['id_status'] == 1) $str .= '&nbsp;<img alt="实名认证通过" src="' . __ROOT__ . '/Style/H/images/icon/id.gif"/>';
    else  $str .= '&nbsp;<img alt="实名认证未通过" src="' . __ROOT__ . '/Style/H/images/icon/id_0.gif"/>';
    if ($vo['phone_status'] == 1) $str .= '&nbsp;<img alt="手机认证通过" src="' . __ROOT__ . '/Style/H/images/icon/phone.gif"/>';
    else  $str .= '&nbsp;<img alt="手机认证未通过" src="' . __ROOT__ . '/Style/H/images/icon/phone_0.gif"/>';
    if ($vo['email_status'] == 1) $str .= '&nbsp;<img alt="邮件认证通过" src="' . __ROOT__ . '/Style/H/images/icon/email.gif"/>';
    else  $str .= '&nbsp;<img alt="邮件认证未通过" src="' . __ROOT__ . '/Style/H/images/icon/email_0.gif"/>';
    if (!empty($vo['pin_pass'])) {
        $str .= '<img alt="支付密码已设置" src="' . __ROOT__ . '/Style/H/images/icon/mima.gif"/>&nbsp;';
    } else {
        $str .= '<img alt="支付密码未设置" src="' . __ROOT__ . '/Style/H/images/icon/mima_0.gif"/>';
    }
    return $str;
}

function getVerify_ucenter($uid)
{
    $pre = C('DB_PREFIX');
    $vo = M("members m")->field("m.id,m.user_leve,m.time_limit,m.pin_pass,s.id_status,s.phone_status,s.email_status")->join("{$pre}members_status s ON s.uid=m.id")->where("m.id={$uid}")->find();
    $str = "";
    if ($vo['id_status'] == 1) $str .= '<a href="' . __APP__ . '/member/verify#fragment-3"><img alt="实名认证通过"   title="实名认证通过" src="' . __ROOT__ . '/Style/H/images/icon/id.gif"/></a>&nbsp;';
    else  $str .= '<a href="' . __APP__ . '/member/verify#fragment-3"><img alt="实名认证未通过"  title="实名认证未通过" src="' . __ROOT__ . '/Style/H/images/icon/id_0.gif"/></a>&nbsp;';
    if ($vo['phone_status'] == 1) $str .= '<a href="' . __APP__ . '/member/verify#fragment-2"><img alt="手机认证通过"   title="手机认证通过" src="' . __ROOT__ . '/Style/H/images/icon/phone.gif"/>&nbsp;';
    else  $str .= '<a href="' . __APP__ . '/member/verify#fragment-2"><img alt="手机认证未通过"   title="手机认证未通过" src="' . __ROOT__ . '/Style/H/images/icon/phone_0.gif"/></a>&nbsp;';
    if ($vo['email_status'] == 1) $str .= '<a href="' . __APP__ . '/member/verify?id=1#fragment-1"><img alt="邮件认证通过"   title="邮件认证通过" src="' . __ROOT__ . '/Style/H/images/icon/email.gif"/></a>&nbsp;';
    else  $str .= '<a href="' . __APP__ . '/member/verify?id=1#fragment-1"><img alt="邮件认证未通过"   title="邮件认证未通过" src="' . __ROOT__ . '/Style/H/images/icon/email_0.gif"/></a>&nbsp;';
    if ($vo['user_leve'] != 0 && $vo['time_limit'] > time()) $str .= '<img alt="VIP会员"   title="VIP会员" src="' . __ROOT__ . '/Style/H/images/icon/vip.gif"/></a>&nbsp;';
    else  $str .= '<a href="' . __APP__ . '/member/vip"><img alt="不是VIP会员"   title="不是VIP会员" src="' . __ROOT__ . '/Style/H/images/icon/vip_0.gif"/></a>&nbsp;';

    if (!empty($vo['pin_pass'])) {
        $str .= '<a  href="' . __APP__ . '/member/user#fragment-3"><img alt="支付密码已设置"   title="支付密码已设置" src="' . __ROOT__ . '/Style/H/images/icon/mima.gif"/></a>&nbsp;';
    } else {
        $str .= '<a  href="' . __APP__ . '/member/user#fragment-3"><img alt="支付密码未设置"   title="支付密码未设置" src="' . __ROOT__ . '/Style/H/images/icon/mima_0.gif"/></a>&nbsp;';
    }

    return $str;
}


//获得时间天数,暂只有等额本息使用，修改时慎重
function get_times($data = array())
{
    if (isset($data['time']) && $data['time'] != "") {
        $time = $data['time'];//时间
    } elseif (isset($data['date']) && $data['date'] != "") {
        $time = strtotime($data['date']);//日期
    } else {
        $time = time();//现在时间
    }
    if (isset($data['type']) && $data['type'] != "") {
        $type = $data['type'];//时间转换类型，有day week month year
    } else {
        $type = "month";
    }
    if (isset($data['num']) && $data['num'] != "") {
        $num = $data['num'];
    } else {
        $num = 1;
    }

    if ($type == "month") {
        $_result = strtotime("{$num} month", $time);
    } else {
        $_result = strtotime("$num $type", $time);
    }
    if (isset($data['format']) && $data['format'] != "") {
        return date($data['format'], $_result);
    } else {
        return $_result;
    }

}


/**
 * 企业直投自动投标设置
 * @param int $borrow_id 标的编号
 * @param int $borrow_type 标的类型
 * @return bool
 * @throws Exception
 */
function autotInvest($borrow_id, $borrow_type)
{
    $datag = get_global_setting();
    $field = 'borrow_money,borrow_uid,borrow_type,borrow_interest_rate,borrow_duration,duration_unit,borrow_min,has_borrow';
    $binfo = TborrowModel::get_borrow_info($borrow_id, $field);

    $map['a.borrow_type'] = $borrow_type;
    $map['a.is_use'] = 1;
    $map['a.end_time'] = array("gt", time());
    $autolist = M("auto_borrow a")
        ->join(C('DB_PREFIX') . "member_money m ON a.uid=m.uid")
        ->field("a.*, m.account_money+m.back_money as money")
        ->where($map)
        ->order("a.invest_time asc")
        ->select();
    $needMoney = $binfo['borrow_money'] - ($binfo['borrow_money'] * $binfo['progress'] / 100);
    foreach ($autolist as $key => $v) {
        if (!$needMoney) break;
        if ($v['uid'] == $binfo['borrow_uid']) continue;
        if ($v['money'] <= 0 || $v['money'] == NULL) {
            continue;
        }
        $num_max1 = floor(($v['money'] - $v['account_money']) / $binfo['per_transfer']);//余额最多可购买份数
        $num_max2 = floor($v['invest_money'] / $binfo['per_transfer']);//最大投资总额可购买份数
        $num_max3 = $needMoney / $binfo['per_transfer'];//$binfo['transfer_total'] - $binfo['has_borrow'];//剩余多少份
        $num_max4 = floor($binfo['transfer_total'] * $datag['auto_rate'] / 100);//每个人所投不能超过10%,结果可能为小数，舍位法
        $num_min = ceil($v['min_invest'] / $binfo['per_transfer']);//最少要买多少份
        if ($num_max1 > $num_max2) {
            $num = $num_max2;
        } else {
            $num = $num_max1;
        }
        if ($num > $num_max3) {
            $num = $num_max3;
        }
        if ($num > $num_max4) {
            $num = $num_max4;
        }
        if ($v['interest_rate'] > 0) {
            if (!($binfo['borrow_interest_rate'] >= $v['interest_rate'])) {//利率范围
                continue;
            }
        }
        if ($v['duration_from'] > 0 && $v['duration_to'] > 0 && $v['duration_from'] <= $v['duration_to']) {//借款期限范围
            //一个月按30天计算，将天转换成月计算
            if( $binfo['duration_unit'] == BorrowModel::BID_CONFIG_DURATION_UNIT_DAY ) {
                $duration_month = round($binfo['borrow_duration']/30, 2);
            }else{
                $duration_month = $binfo['borrow_duration'];
            }
            if (!(($duration_month >= $v['duration_from']) && ($duration_month <= $v['duration_to']))) {
                continue;
            }
        }
        if (!($num >= $num_min)) {//
            continue;
        }
        if (!(($v['money'] - $v['account_money']) >= ($num * $binfo['per_transfer']))) {//余额限制
            continue;
        }
        if ($needMoney <= 0) {//可投金额必须大于0
            continue;
        }
        $money = $num * $binfo['per_transfer'];  // 按金额投标
        TinvestMoney($v['uid'], $borrow_id, $money, false, 1);
        $needMoney = $needMoney - $num * $binfo['per_transfer'];   // 减去剩余已投金额
        NoticeSet('chk27', $v['uid'], $borrow_id, $v['id']);//sss
        M('auto_borrow')->where('id = ' . $v['id'])->save(array("invest_time" => time()));

    }
    return true;
}

/**
 * 新的普通标自动投标设置
 * @param $borrow_id
 * @param bool $durationMonth 两种还款方式（4，5）借款期限是否为自然月
 * @return bool
 * ~~
 * 显性条件：
 * i> 最大投资金额 invest_money
 * ii> 最小投资金额  min_invest
 * iii> 年化利率  可不填 interest_rate
 * iv> 借款期限 duration_from   最小几个月
 * v> 借款期限 duration_to  最大几个月
 * vi> 是否包括天标
 * vii> 账户保留金额  account_money
 * viii> 自动投标截止日期
 *
 * 隐性条件
 * i> 用户账户余额
 * ii> 是否超过配置文件里自动投标的最大投资上限
 * ii> 只对信用标进行自动投标 borrow_type = 1
 * iii> 借款标是否允许自动投标
 * ~~
 */
function autoInvest($borrow_id)
{
    $datag = get_global_setting();
    $pre = C('DB_PREFIX');

    $borSql = M("borrow_info");
    $binField = 'borrow_uid,borrow_money,borrow_type,repayment_type,borrow_interest_rate,borrow_duration,has_vouch,has_borrow,borrow_max,borrow_min,can_auto';
    $investMoney = 0;//实际可投金额
    //投标信息
    $binInfo = $borSql->field($binField)->where('can_auto<>0')->find($borrow_id);
    if (empty($binInfo)) return true;
    $needMoney = $binInfo['borrow_money'] - $binInfo['has_borrow'];//剩余可投的金额

    //所有设置自动投的客户
    $autoBorSql = M("auto_borrow a");
    $nowTime = time();
    $fields = "a.invest_time,a.uid,a.id,a.interest_rate,a.duration_from,a.duration_to,a.account_money,a.min_invest,a.invest_money,m.account_money+m.back_money as money";
    $where = "a.is_use=1 and a.borrow_type=1 and a.end_time>{$nowTime} "
        . ' AND ((min_invest <= ' . $binInfo['borrow_max'] . ' AND ' . $binInfo['borrow_max'] . ' > 0) OR ' . $binInfo['borrow_max'] . ' = 0)'
        . ' AND invest_money >= ' . $binInfo['borrow_min']  // 最小投资限制
        . ' AND (interest_rate = 0 OR interest_rate <= ' . $binInfo['borrow_interest_rate'] . ')'  // 存在大于，0.0表示不限
        . ' AND ((duration_from <= ' . $binInfo['borrow_duration'] . ' AND duration_to >= ' . $binInfo['borrow_duration'] . ') OR (duration_from=0 AND duration_to=0))'  // 投资期限
        . ' AND a.uid != ' . $binInfo['borrow_uid'];  // 不投自己的标
    $autoList = $autoBorSql
        ->join("{$pre}member_money m on a.uid=m.uid")
        ->field($fields)
        ->where($where)
        ->having("money >= min_invest")// 用户的账户资金必须大于等于最小投资金额
        ->order("a.invest_time asc")
        ->select();
    $i = 0;
    $canInvestMoney = $binInfo['borrow_money'] * $datag['auto_rate'] / 100;//可投资标的金额 如果设定的是10%则只能投标的10%
    foreach ($autoList as $key => $val) {
        if (!$needMoney) break;//如果已满标则中止循环
        // 用户可设置自动投标账户剩余金额
        $usableMoney = intval($val['money'] - $val['account_money']);//可用投资金额 总余额-设置的最少剩余金额

        //如果设置的最大投资金额大于可投资标的金额
        if ($val['invest_money'] > $canInvestMoney)
            $investMoney = $canInvestMoney;
        else
            $investMoney = $val['invest_money'];

        if ($canInvestMoney > $usableMoney) {
            $investMoney = $usableMoney;
        }
        //如果投标人设置了最小金额 如果直接满标则不考虑最小投标
        if ($binInfo['borrow_min'] > 0) {
            if ($investMoney < $binInfo['borrow_min']) { // 小于最低投标
                continue;//不符合最低投资金额
            } elseif (($needMoney - $investMoney) > 0 && ($needMoney - $investMoney) < $binInfo['borrow_min']) { // 剩余金额小于最小投标金额
                if (($investMoney - $binInfo['borrow_min']) >= $binInfo['borrow_min']) {  // 投资金额- 最小投资金额 大于最小投资金额
                    $investMoney = $investMoney - $binInfo['borrow_min'];  // 投资 = 投资-最小投资（保证下次投资金额大于最小投资金额）
                } else {
                    continue;
                }
            }
        }

        //可投金额大于标的剩余金额
        if ($investMoney > $needMoney) {
            $investMoney = $needMoney;
        }

        //投资金额不能大于借款金额的10%
        if ($investMoney > $canInvestMoney) {
            $investMoney = $canInvestMoney;
        }
        //如果发标人限制了可投资的最大金额
        if (($binInfo['borrow_max'] > 0) && ($investMoney > $binInfo['borrow_max'])) {
            $investMoney = $binInfo['borrow_max'];
        }
        //借款期限范围
        $MAXMOONS = 180;
        $val['is_auto_day'] = ($val['duration_to'] >= $MAXMOONS) ? 1 : 0;
        $val['duration_to'] = $val['duration_to'] % $MAXMOONS;
        if ($binInfo['repayment_type'] == 1) {
            if ($val['is_auto_day'] == false) continue;
        } else {
            if ($val['duration_from'] > 0 && $val['duration_to'] > 0 && $val['duration_from'] <= $val['duration_to']) {
                if (!(($binInfo['borrow_duration'] >= $val['duration_from']) && ($binInfo['borrow_duration'] <= $val['duration_to']))) {
                    continue;
                }
            }
        }
        //如果可投金额小于设置的最小金额
        if ($investMoney < $val['min_invest']) {
            continue;
        }
        //可投金额不能大于设置的最大金额
        if ($investMoney > $val['invest_money']) {
            $investMoney = $val['invest_money'];
        }

        //如果当前可投金额不是最小投资金额的整数倍
        $num = intval($investMoney / $binInfo['borrow_min']);
        if ($num > 0) {
            $investMoney = $binInfo['borrow_min'] * $num;
        }
        $x = investMoney($val['uid'], $borrow_id, intval($investMoney), 1); //TODO: Queue

        if ($x === true) {
            $needMoney = $needMoney - $investMoney;   // 减去剩余已投金额
            NoticeSet('chk27', $val['uid'], $borrow_id, $val['id']);
            M('auto_borrow')->where('id = ' . $val['id'])->save(array("invest_time" => $nowTime + $i));
        }
        if (C('MCQ_USE') == true) {
            sleep(1);
        }
        $i++;
    }
    return true;
}

/**
 *  生成订单号
 **/
function build_order_no()
{
    return date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
}

/**
 * 添加优惠券
 *
 * @param intval $uid // 操作的用户id
 * @param intval $type // 优惠券类型
 * @param string $remark // 优惠券说明
 * @param intval $source_uid // 被邀请人uid， 用于邀请奖励
 */
function addCoupon($uid, $type, $remark, $source_uid = 0, $borrow_id = false, $invest_id = false)
{
    $uid = intval($uid);
    $type = intval($type);
    $remark = text($remark);
    $source_uid = intval($source_uid);
    if (!$uid || !$type) {
        return false;
    }
    $expconf = FS("Webconfig/expconf");

    $type_conf = $expconf[$type];
    if (!$type_conf['num']) return "优惠券已关闭";

    $coupon_type = ExpandMoneyModel::get_coupon_type($type_conf['is_taste']);
    if ($type_conf['is_taste'] == ExpandMoneyModel::LZH_EXPAND_MONEY_IS_TASTE_YES) {
        $main_msg = "恭喜您获得" . $remark . "面值" . $type_conf['money'] . "元{$coupon_type}，投资" . $type_conf['invest_money'] . "元可用,本金不可以提现，利息可以提现。";
    } else {
        $main_msg = "恭喜您获得" . $remark . "面值" . $type_conf['money'] . "元{$coupon_type}，投资" . $type_conf['invest_money'] . "元可用";
    }
    addInnerMsg($uid, "恭喜您获得" . $remark . "面值" . $type_conf['money'] . "元{$coupon_type}！", $main_msg);//站内信

    if ($type_conf['is_taste'] == ExpandMoneyModel::LZH_EXPAND_MONEY_IS_TASTE_YES) {
        $remark .= "一张" . $type_conf['money'] . "元{$coupon_type}，投资" . $type_conf['invest_money'] . "元可用,本金不可以提现，利息可以提现。";
    } else {
        $remark .= "一张" . $type_conf['money'] . "元{$coupon_type}，投资" . $type_conf['invest_money'] . "元可用";
    }

    $expired_time = strtotime("+{$type_conf['expired_time']} month");
    $arr = array(5, 6, 7); // 这个在配置文件里面的EXP_TYPE，如果是一马当先之类，那么记录它的borrow_id
    for ($i = 0; $i < $type_conf['num']; $i++) {
        $expand_money[$i]['uid'] = $uid;
        $expand_money[$i]['money'] = $type_conf['money'];
        $expand_money[$i]['remark'] = $remark;
        $expand_money[$i]['expired_time'] = $expired_time;
        $expand_money[$i]['add_time'] = time();
        $expand_money[$i]['orders'] = "DH" . build_order_no();
        $expand_money[$i]['invest_money'] = $type_conf['invest_money'];
        $expand_money[$i]['type'] = $type;
        $expand_money[$i]['is_taste'] = $type_conf['is_taste'];
        if (in_array($type, $arr)) {
            $expand_money[$i]['borrow_id'] = $borrow_id; //记录哪个标送的
            $expand_money[$i]['invest_id'] = $invest_id; //记录哪次投资得的
        }
        $expand_money[$i]['source_uid'] = $source_uid;
    }
    if (!empty($expand_money)) {
        $exp_id = M('expand_money')->addAll($expand_money); // addAll从下标为0开始
    }

    return $exp_id;
}

//计算分页 `mxl 20150318`
function calPage($total, &$cur, &$page, $def = 10)
{
    $page = (empty($page) === true) ? $def : intval($page);
    $cur = (empty($cur) === true) ? 1 : intval($cur);
    $start = $cur * $page - $page;//$cur从1开始，0是不合法的
    if ($start >= $total || 1 > $page || 1 > $total || 1 > $cur) {
        return 0;
    }
    return "{$start},{$page}";
}

//拼装借款标url `mxl 20150318`
function getBorrowUrl($type, $id)
{
    return "/home/" . ((intval($type) === 6) ? "tinvest/tdetail" : ((intval($type) === 7) ? "fund/tdetail" : "invest/detail")) . "?id={$id}";
}

//拼装时间查询数组，例如 lt`2015-03-20 => array(lt,"143209350252") `mxl 20150320`
function getTimeArray($str)
{
    $arr = explode("`", $str);
    if (count($arr) !== 2) {
        return false;
    }
    if ($arr[0] === "between") {
        $pair = explode(",", $arr[1]);
        if (count($pair) !== 2) {
            return false;
        }
        $arr[1] = strtotime($pair[0]) . "," . strtotime($pair[1]);
        return $arr;
    }
    $arr[1] = strtotime($arr[1]);
    return $arr;
}

//返回当月时间戳区间
function calMonth(&$year, &$month, $min = 2010, $max = 10)
{
    $year = intval($year);
    $month = intval($month);
    if ($year > (date("Y", time()) + $max) || $min > $year) {
        $year = $min;
    }
    if ($month > 12 || 1 > $month) {
        $month = 1;
    }
    $next_month = ($month === 12) ? 1 : ($month + 1);
    $next_year = ($month === 12) ? ($year + 1) : $year;
    return strtotime("{$year}-{$month}-1 00:00:00") . "," . (strtotime("{$next_year}-{$next_month}-1 00:00:00") - 1);
}

//计算逾期罚金与催收费
function calExpired($arr, $key = "status")
{
    foreach ($arr as $k => $v) {
        $status = intval($v[$key]);
        if (($status === 4 || $status === 6 || $status === 5 || $status === 7 || $status === 14) && time() > $v['deadline']) {
            $expired_days = getExpiredDays($v['deadline']);
            $arr[$k]['expired_money_now'] = getExpiredMoney($expired_days, $v['capital'], $v['interest']);
            $arr[$k]['call_fee_now'] = getExpiredCallFee($expired_days, $v['capital'], $v['interest']);
            if ($status === 7 || $status === 14) {
                $arr[$k][$key] = (($arr[$k]['expired_money_now'] + $arr[$k]['call_fee_now']) > 0) ? 6 : 7;
            }
        }
    }
    return $arr;
}

//获取还款类型
function getRepaymentType($rtype = 5, $btype = 6)
{
    return (($btype == 6 || $btype == 7) ? BorrowModel::get_business_repay_type($rtype) : BorrowModel::get_repay_type($rtype));
}

//新进度条
function getCircleProgress($num)
{
    $num = intval($num);
    $ret = 0;
    if ($num > 0) {
        switch ($num) {
            case 100:
                $ret = 10;
                break;
            case $num >= 90:
                $ret = 9;
                break;
            case $num >= 80:
                $ret = 8;
                break;
            case $num >= 70:
                $ret = 7;
                break;
            case $num >= 60:
                $ret = 6;
                break;
            case $num >= 50:
                $ret = 5;
                break;
            case $num >= 40:
                $ret = 4;
                break;
            case $num >= 30:
                $ret = 3;
                break;
            case $num >= 20:
                $ret = 2;
                break;
            case $num >= 10:
                $ret = 1;
                break;
            default:
                $ret = 0;
        }
    }
    return $ret;
}

/**
 * @param type $borrow_max 标设置最大可投金额
 * @param type $need 还差多少满标
 * @return type
 */
function maxInvest($borrow_max, $need)
{
    $max_invest = '';
    if ($borrow_max > 0) {
        if ($borrow_max > $need) {
            $max_invest = $need;
        } else {
            $max_invest = $borrow_max;
        }

    } else {
        $max_invest = $need;
    }
    $max_invest = getMoneyFormt($max_invest);
    return $max_invest;
}

/**
 * @TODO 获取优惠券总额
 * @param int $uid 会员名
 * @param int $type 优惠券类型
 *
 */
function rwards($type = 'all', $uid = 'all')
{
    if ($type == 'all' && $uid == 'all') {        //获取网站优惠券总额
        $data = M('expand_money')->sum('money');
    } elseif ($type == 'all' && $uid != 'all') {   //获取指定用户获取所有优惠券总额
        $data = M('expand_money')->where('uid=' . $uid)->sum('money');
    } elseif ($type != 'all' && $uid == 'all') {  //获取指定类型下优惠券总额
        $data = M('expand_money')->where('type=' . $type)->sum('money');
    } else {                                                         //指定会员指定类型优惠券总额
        $data = M('expand_money')->where('type=' . $type . ' and uid=' . $uid)->sum('money');
    }
    return $data;
}

/**
 * 标列表页导航
 */
function get_navigate()
{

    $navigate = array();

    $navigate = M('navigation')
        ->field("id, type_name, type_url")
        ->where(array('parent_id' => 2, 'model' => 'navigation', 'is_hiden' => 0))
        ->order("sort_order DESC")
        ->select();

    return $navigate;
}

/**
微信端

 * @param int $id
 * @param int $type 借款标类型
 * @return string
 */
function getMinvestUrl($id, $type = '')
{
    if ($type == '3') {
        return  __APP__ . "/m/tinvest/{$id}" . C('URL_HTML_SUFFIX');
        //dump($a);
    } elseif ($type == '2') {
        return __APP__ . "/m/fund/{$id}" . C('URL_HTML_SUFFIX');
    } elseif($type == '1') {
        return __APP__ . "/m/invest/{$id}" . C('URL_HTML_SUFFIX');
    } elseif ($type == '4') {
        return __APP__ . "/m/debt/{$id}" . C('URL_HTML_SUFFIX');
    }elseif ($type == '5'){
        return __APP__ . "/m/newwen/{$id}" . C('URL_HTML_SUFFIX');
    }elseif ($type == '6'){
        return __APP__ . "/m/help/{$id}" . C('URL_HTML_SUFFIX');
    }
}

/**
 * 工作年限
 * @param mixed
 * */
function get_basics()
{
    $bconf = array();
    if (!S('gong_zuo')) {
        $borrowconfig = require C("ROOT_URL") . "Webconfig/gongzuonianxian.php";
        $bconf = $borrowconfig;

        S('gong_zuo', $bconf);
        S('gong_zuo', $bconf, 3600 * C('TTXF_TMP_HOUR'));
    } else {
        $bconf = S('gong_zuo');
    }

    return $bconf;
}

function getMoneyFormts($money){
    if($money>=10000 && $money<=100000000){
        $res = getFloatValue(($money/10000),0)."万";
    }else if($money>=100000000){
        $res = getFloatValue(($money/100000000),0)."亿";
    }else{
        $res = getFloatValue($money,0);
    }
    return $res;
}

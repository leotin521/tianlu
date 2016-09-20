<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * 设置自动投标的会员浏览与搜索
 *
 * @author 元<yjqphp@163.com> 2014-09-02
 */
class AutomembersAction extends ACommonAction {

    /**
     * 自动投标会员浏览搜索
     */
    public function index() {
        $map = array();
        if ($_REQUEST['uname']) {
            $map['m.user_name'] = array("like", urldecode($_REQUEST['uname']) . "%");
            $search['uname'] = urldecode($_REQUEST['uname']);
        }
        if ($_REQUEST['realname']) {
            $map['mi.real_name'] = urldecode($_REQUEST['realname']);
            $search['realname'] = $map['mi.real_name'];
        }
        if ($_REQUEST['is_transfer']) {
            $map['m.is_transfer'] = intval($_REQUEST['is_transfer']);
            $search['is_transfer'] = intval($_REQUEST['is_transfer']);
        }
        if ($_REQUEST['is_use'] == 'yes') {
            $map['a.is_use'] = 1;
//            $map['m.time_limit'] = array('gt', time());
            $search['is_use'] = 'yes';
        } elseif ($_REQUEST['is_use'] == 'no') {
            $map['is_use'] = 0;
            $search['is_use'] = 'no';
        }


        //if(session('admin_is_kf')==1){
        //		$map['m.customer_id'] = session('admin_id');
        //}else{
        if ($_REQUEST['customer_name']) {
            $map['m.customer_id'] = intval($_REQUEST['customer_id']);
            $search['customer_id'] = $map['m.customer_id'];
            $search['customer_name'] = urldecode($_REQUEST['customer_name']);
        }

        if ($_REQUEST['customer_name']) {
            $cusname = urldecode($_REQUEST['customer_name']);
            $kfid = M('ausers')->getFieldByUserName($cusname, 'id');
            $map['m.customer_id'] = $kfid;
            $search['customer_name'] = $cusname;
            $search['customer_id'] = $kfid;
        }
        //}
        if (!empty($_REQUEST['bj']) && !empty($_REQUEST['lx']) && !empty($_REQUEST['money'])) {

            if ($_REQUEST['lx'] == 'allmoney') {
                if ($_REQUEST['bj'] == 'gt') {
                    $bj = '>';
                } else if ($_REQUEST['bj'] == 'lt') {
                    $bj = '<';
                } else if ($_REQUEST['bj'] == 'eq') {
                    $bj = '=';
                }
                $map['_string'] = "(mm.account_money+mm.back_money) " . $bj . floatval($_REQUEST['money']);
            } else {
                $map[$_REQUEST['lx']] = array($_REQUEST['bj'], floatval($_REQUEST['money']));
            }
            $search['bj'] = htmlspecialchars($_REQUEST['bj'], ENT_QUOTES);
            $search['lx'] = htmlspecialchars($_REQUEST['lx'], ENT_QUOTES);
            $search['money'] = floatval($_REQUEST['money']);
        }

        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['a.invest_time'] = array("between", $timespan);
            $search['start_time'] = urldecode($_REQUEST['start_time']);
            $search['end_time'] = urldecode($_REQUEST['end_time']);
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['a.invest_time'] = array("gt", $xtime);
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['a.invest_time'] = array("lt", $xtime);
            $search['end_time'] = $xtime;
        }

        //分页处理
        import("ORG.Util.Page");
        $count = M('auto_borrow a')->field($field)->join("{$this->pre}member_money mm ON mm.uid=a.uid")->join("{$this->pre}member_info mi ON mi.uid=a.uid")->join("{$this->pre}members m  ON a.uid=m.id")->where($map)->count('m.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $field = 'a.uid,a.is_use,a.end_time,a.account_money,a.invest_money,a.duration_from,a.duration_to,a.interest_rate,a.borrow_type,a.invest_time,a.id,m.user_phone,m.user_name,m.customer_name,m.user_leve,m.time_limit,mi.real_name,mm.money_freeze,mm.money_collect,(mm.account_money+mm.back_money) account_moneys,m.user_email,m.recommend_id,m.is_borrow,m.is_transfer';
        $list = M('auto_borrow a')->field($field)->join("{$this->pre}member_money mm ON mm.uid=a.uid")->join("{$this->pre}member_info mi ON mi.uid=a.uid")->join("{$this->pre}members m  ON a.uid=m.id")->where($map)->limit($Lsql)->order('a.invest_time')->select();

        $list = $this->_listFilter($list);
        $this->assign("bj", array("gt" => '大于', "eq" => '等于', "lt" => '小于'));
        $this->assign("lx", array("allmoney" => '可用余额', "mm.money_freeze" => '冻结金额', "mm.money_collect" => '待收金额'));
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);
        $this->assign("query", http_build_query($search));
        $this->display();
    }

    /**
     * 会员投标时间修改
     */
    public function doEdit() {
        if ($this->isPost()) {
            $data['invest_time'] = strtotime($_POST['invest_time']);
            $id = intval($_POST['id']);
            $res = M('auto_borrow')->where('id=' . $id)->save($data);
            if ($res) {
               $data['type']='autoeditMembers' ;
               $data['tstatus']= 1;
               $data['deal_ip']= $_SERVER["REMOTE_ADDR"];
               $data['deal_time']= time();
               $data['deal_user']=$_SESSION['adminname'];
               $data['deal_info']= "对{$id}号自动投标，进行了投标时间修改";
               M('auser_dologs')->add($data);
                $this->success('修改成功！');
            } else {
                $this->error('修改失败！');
            }
        } elseif (!empty($_GET['idd'])) {
            $id = intval($_GET['idd']);
            $type = intval($_GET['type']);
            $res = M('auto_borrow')->where('id=' . $id)->save(array('is_use' => $type));
            if ($res) {
               $data['type']='autoeditMembers' ;
               $data['tstatus']= 1;
               $data['deal_ip']= $_SERVER["REMOTE_ADDR"];
               $data['deal_time']= time();
               $data['deal_user']= $_SESSION['adminname'];
               $data['deal_info']= "对{$id}号自动投标，进行了自动投标状态修改";
               M('auser_dologs')->add($data);
                $this->success('修改成功！');
            } else {
                $this->error('修改失败！');
            }
        } else {
            $aid = isset($_GET['id']) ? $_GET['id'] : '0';
            $autoInfo = M('auto_borrow')->where('id=' . intval($aid))->find();
            $this->assign('autoInfo', $autoInfo);
            $this->display('edit');
        }
    }

    public function _listFilter($list) {
        $row = array();
        foreach ($list as $key => $v) {
            $v['user_type'] = MembersModel::get_user_type($v['is_transfer']);
            $row[$key] = $v;
        }
        return $row;
    }

}

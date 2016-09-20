<?php

// 全局设置
class MembersAction extends ACommonAction {

    /**
      +----------------------------------------------------------
     * 默认操作
      +----------------------------------------------------------
     */
    public function index() {
        $map = array();
        if ($_REQUEST['uname']) {
            $map['m.user_name'] = array("like", urldecode($_REQUEST['uname']) . "%");
            $search['uname'] = htmlspecialchars(urldecode($_REQUEST['uname']), ENT_QUOTES);
        }
        if ($_REQUEST['realname']) {
            $map['mi.real_name'] = htmlspecialchars(urldecode($_REQUEST['realname']), ENT_QUOTES);
            $search['realname'] = $map['mi.real_name'];
        }
        if ($_REQUEST['user_phone']) {
            $map['m.user_phone'] = htmlspecialchars($_REQUEST['user_phone'], ENT_QUOTES);
            $search['user_phone'] = $map['m.user_phone'];
        }
        if ($_REQUEST['is_transfer']) {
            $map['m.is_transfer'] = intval($_REQUEST['is_transfer']);
            $search['is_transfer'] = intval($_REQUEST['is_transfer']);
        }
        
        if (!empty($_REQUEST['recommend_name'])) {
            $arr['user_name'] = urldecode($_REQUEST['recommend_name']);
            $info = M('members')->field('id')->where($arr)->find();
            $map['m.recommend_id'] = $info['id'];
            $search['recommend_name'] = urldecode($_REQUEST['recommend_name']);
        }

        if (!empty($_REQUEST['bj']) && !empty($_REQUEST['lx']) && !empty($_REQUEST['money'])) {

            if ($_REQUEST['lx'] == 'allmoney') {
                if ($_REQUEST['bj'] == 'gt') {
                    $bj = '>';
                } else if ($_REQUEST['bj'] == 'lt') {
                    $bj = '<';
                } else if ($_REQUEST['bj'] == 'eq') {
                    $bj = '=';
                }
                if($bj=='<'){
                          $map['_string'] = "(mm.account_money+mm.back_money) is null or (mm.account_money+mm.back_money) " . $bj . floatval($_REQUEST['money']);
                }else{
                          $map['_string'] = "(mm.account_money+mm.back_money) " . $bj . floatval($_REQUEST['money']);
                }
                
            } else {
                $map[$_REQUEST['lx']] = array(htmlspecialchars($_REQUEST['bj'], ENT_QUOTES), floatval($_REQUEST['money']));
            }
            $search['bj'] = htmlspecialchars($_REQUEST['bj'], ENT_QUOTES);
            $search['lx'] = htmlspecialchars($_REQUEST['lx'], ENT_QUOTES);
            $search['money'] = floatval($_REQUEST['money']);
        }

        if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['m.reg_time'] = array("between", $timespan);
            $search['start_time'] = urldecode($_REQUEST['start_time']);
            $search['end_time'] = urldecode($_REQUEST['end_time']);
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['m.reg_time'] = array("gt", $xtime);
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['m.reg_time'] = array("lt", $xtime);
            $search['end_time'] = $xtime;
        }

        //分页处理
        import("ORG.Util.Page");
        $count = M('members m')->join("{$this->pre}member_money mm ON mm.uid=m.id")->join("{$this->pre}member_info mi ON mi.uid=m.id")->where($map)->count('m.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $field = 'm.id,m.user_phone,m.reg_time,m.user_name,m.customer_name,m.user_leve,m.time_limit,mi.real_name,mm.money_freeze,mm.money_collect,(mm.account_money+mm.back_money) account_money,m.user_email,m.recommend_id, m.recommend_type,m.is_borrow,m.is_transfer'; //增加m.recommend_type//`mxl:team20141231debug`
        $list = M('members m')->field($field)->join("{$this->pre}member_money mm ON mm.uid=m.id")->join("{$this->pre}member_info mi ON mi.uid=m.id")->where($map)->limit($Lsql)->order('m.id DESC')->select();
        $list = $this->_listFilter($list);
        $this->assign("bj", array("gt" => '大于', "eq" => '等于', "lt" => '小于'));
        $this->assign("lx", array("allmoney" => '可用余额', "mm.money_freeze" => '冻结金额', "mm.money_collect" => '待收金额'));
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);
        $this->assign("query", http_build_query($search));

        $this->display();
    }

    public function edit() {
        $model = D(ucfirst($this->getActionName()));
        setBackUrl();
        $id = intval($_REQUEST['id']);
        $vo = $model->find($id);
        $vx = M('member_info')->where("uid={$id}")->find();
        if (!is_array($vx)) {
            M('member_info')->add(array("uid" => $id));
        } else {
            foreach ($vx as $key => $vxe) {
                $vo[$key] = $vxe;
            }
        }
        $integration = $this->gloconf['BANK_NAME'];
        #一人多卡模式
        $vb = M('member_banks')->where("uid={$id}")->select();
        if (is_array($vb)) {
            foreach ($vb as $key=>$val){
                $vb[$key]['bank_name'] = $integration[$val['bank_name']];
            }
        }
        $member_type = MembersModel::get_user_type();
        $this->assign('member_type', $member_type);
        //现居地
        $city = M('member_info')->field('address,profession')->where("uid={$id}")->find();
        $this->assign("city", $city);
        $this->assign('list',$vb);
        
        //紧急联系人关系
        $info = get_basic();
        $relation = $info['RELATION'];
        $this->assign("relation",$relation);

        //////////////////////
        $vo['id'] = $id; // 用户id被member_info替换掉了，这里还原
        $this->assign('vo', $vo);
        $this->assign("utype", C('XMEMBER_TYPE'));
        //$this->assign("bank_list", $this->gloconf['BANK_NAME']);
        $this->display();
    }
    
    /**
     * 修改银行卡信息
     */
    public function bankEdit(){
        $arr = array();
        $arr['id'] = intval($_GET['id']);
        $arr['uid'] = intval($_GET['uid']);
        $arr['bank_num'] = array('neq','');
        if($arr['id']==0){  //添加过滤已上传的银行名称
            $bank_list = get_bank_type($arr['uid']);
        }else{
            $bank_list = $this->gloconf['BANK_NAME'];
        }
        $vb = M('member_banks')->where($arr)->find();
        $vb['id'] = $arr['id'];
        $vb['uid'] = $arr['uid'];
        $this->assign('vo', $vb);
        $this->assign("bank_list", $bank_list);
        $this->assign("province", $this->city(1));   //省级
        $this->assign("city", $this->city($vb['bank_province']));    //市级
        $data['html'] = $this->fetch();
		echo $data['html'];
    }
    /**
     * keep
     */
    public function dobankEdit(){
        $data = textPost($_POST);
        $data['add_ip'] = get_client_ip();
        $data['add_time'] = time();
        if ($data['id']==0) {
            unset($data['id']);
            $newid = M('member_banks')->add($data);
        }else{
            $newid = M('member_banks')->where("id = ".$data['id']." and uid = ".$data['uid'])->save($data);
        }
        if ($newid || $newid !== false) {
            alogs("Members", 0, 1, '成功执行了会员银行卡信息的修改操作！'); //管理员操作日志
            //成功提示
            $this->assign('jumpUrl', __URL__ . "/" . session('listaction'));
            $this->success(L('修改成功'));
        } else {
            alogs("Members", 0, 0, '执行会员银行卡信息的修改操作失败！'); //管理员操作日志
            $model->rollback();
            //失败提示
            $this->error(L('修改失败'));
        }
    }
    /**
     * 
     */
    public function bankdel(){
        $arr['id'] = intval($_POST['id']);
        $arr['uid'] = intval($_POST['uid']);
        $newid = M('member_banks')->where($arr)->delete();
        if($newid){
            exit(json_encode(array('status'=>1,'message'=>'删除成功')));
        }else{
            exit(json_encode(array('status'=>0,'message'=>'删除失败')));
        }
        
    }
    /**
     * 获取地区
     */
    public function city($reid){
        $vobank = M("area")->field(true)->where("reid = ".$reid." and is_open=0")->order('id asc')->select();
        return $vobank;
    }
    /**
     * 地区联动
     */
    public function sele()
    {
        $arr = $this->city(intval($_POST['pid']));
        exit(json_encode($arr));
    }
    /**
     * 添加数据,如果调整为流转会员，更改user_type字段
     */
    public function doEdit() {
        $new_request = $_POST;
        $_POST = null;
        foreach($new_request as $key=>$val ) {
            $_POST[$key] = htmlspecialchars($val, ENT_QUOTES);
        }

        $idcard['idcard'] = text($_POST['idcard']);
        $email['user_email'] = text($_POST['user_email']);
        $phone['user_phone'] = text($_POST['user_phone']);

        if ($phone['user_phone']!=''){
            $phone['id']  = array('neq',text($_POST['id']));
            $count = M('members')->where($phone)->count('id');
            if ($count>0){
                $this->error(L('操作失败，该手机号码已被使用！'));
            }
        }
        if ($email['user_email']!=''){
            $email['id']  = array('neq',text($_POST['id']));
            $count = M('members')->where($email)->count('id');
            if ($count>0){
                $this->error(L('操作失败，该邮箱已被使用！'));
            }
        }
        if ($idcard['idcard']!=''){
            $idcard['uid']  = array('neq',text($_POST['uid']));
            $count = M('member_info')->where($idcard)->count('uid');
            if ($count>0){
                $this->error(L('操作失败，该身份证号已被使用！'));
            }
        }
        $model = D(ucfirst($this->getActionName()));
        $model2 = M("member_info");
        $model2->em_name = text($_POST['em_name']);
        $model2->em_phone = text($_POST['em_phone']);
        $model2->em_relation = text($_POST['em_relation']);

        if (false === $model->create()) {
            $this->error($model->getError());
        }
        if (false === $model2->create()) {
            $this->error($model2->getError());
        }

        $model->startTrans();
        if (!empty($model->user_pass)) {
            $model->user_pass = md5($model->user_pass);
        } else {
            unset($model->user_pass);
        }
        if (!empty($model->pin_pass)) {
            $model->pin_pass = md5($model->pin_pass);
        } else {
            unset($model->pin_pass);
        }
        $model->user_phone = text($_POST['user_phone']);
        $model->is_transfer = intval($_POST['is_transfer']);
        if (intval($_POST['is_transfer']) == MembersModel::MEMBERS_IS_TRANSFER_BUSINESS || intval($_POST['is_transfer']) == MembersModel::MEMBERS_IS_TRANSFER_PERSONAL) {
            $re = array('status' => 2);
            $ret4 = M('borrow_apply')->where(array('uid' => $model->id))->save($re);
        } else {
            $re = array('status' => 0);
            $ret4 = M('borrow_apply')->where(array('uid' => $model->id))->save($re);
        }

        $aUser = get_admin_name();
        $kfid = $model->customer_id;
        $model->customer_name = $aUser[$kfid];
        if ($model->is_ban == '0') {
            $time = strtotime(date("Y-m-d", time()));
            $where = ' and add_time >' . $time . ' and add_time<' . ($time + 3600 * 24);
            $id = $model->id;
            $data['is_success'] = '0';
            M('member_login')->where('uid=' . $id . $where)->save($data);
        }
        $result = $model->save();
        $result2 = $model2->save();



        //保存当前数据对象
        if ($result || $result2 || $ret4 !== false) { //保存成功
            $model->commit();
            alogs("Members", 0, 1, '成功执行了会员信息资料的修改操作！'); //管理员操作日志
            //成功提示
            $this->assign('jumpUrl', __URL__ . "/" . session('listaction'));
            $this->success(L('修改成功'));
        } else {
            alogs("Members", 0, 0, '执行会员信息资料的修改操作失败！'); //管理员操作日志
            $model->rollback();
            //失败提示
            $this->error(L('修改失败'));
        }
    }

    public function info() {

        $map = array();
        if ($_REQUEST['user_name']) {
            $map['m.user_name'] = array("like", urldecode($_REQUEST['user_name']) . "%");
            $search['uname'] = urldecode($_REQUEST['user_name']);
        }
        $map['m.is_transfer']=array('gt',0);
        //分页处理
        import("ORG.Util.Page");
        $count = M('members m')->join("{$this->pre}member_info mi ON mi.uid=m.id")->where($map)->count('m.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $field = 'm.id,m.user_phone,m.user_type,m.reg_time,m.user_name,m.user_leve,mi.real_name';
        $list = M('members m')->join("{$this->pre}member_info mi ON mi.uid=m.id")->where($map)->limit($Lsql)->order('m.id DESC')->select();

        $list = $this->_listFilter($list);
        $this->assign("list", $list);
        $this->assign("pagebar", $page);
        $this->assign("search", $search);
        $this->assign("query", http_build_query($search));

        $this->display();

        /*
         * 老版本会员资料
          if($_GET['user_name']) $search['m.user_name'] = text($_GET['user_name']);
          else $search=array();
          $list = getMemberInfoList($search,10);
          $this->assign("list",$list['list']);
          $this->assign("pagebar",$list['page']);
          $this->assign("search", $search);
          $this->display();
         * 
         */
    }

    public function infowait() {
        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";
        $map = array();
        if ($_GET['uname']){
            $map['m.user_name'] = text($_GET['user_name']);
            $search['uname']=   text($_GET['user_name']);
        }
         if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])) {
            $timespan = strtotime(urldecode($_REQUEST['start_time'])) . "," . strtotime(urldecode($_REQUEST['end_time']));
            $map['ap.add_time'] = array("between", $timespan);
            $search['start_time'] = urldecode($_REQUEST['start_time']);
            $search['end_time'] = urldecode($_REQUEST['end_time']);
        } elseif (!empty($_REQUEST['start_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['start_time']));
            $map['ap.add_time'] = array("gt", $xtime);
            $search['start_time'] = $xtime;
        } elseif (!empty($_REQUEST['end_time'])) {
            $xtime = strtotime(urldecode($_REQUEST['end_time']));
            $map['ap.add_time'] = array("lt", $xtime);
            $search['end_time'] = $xtime;
        }
  
		if($_REQUEST['status']!=''){
			$map['ap.apply_status'] = intval($_REQUEST['status']);
   	   $search['status'] = intval($_REQUEST['status']);
		}
           
        $list = getMemberApplyList($map, 10);

        $this->assign("aType", $Bconfig['APPLY_TYPE']);
        $this->assign("data_status", $Bconfig['DATA_STATUS']);
        $this->assign("list", $list['list']);
        $this->assign("pagebar", $list['page']);
        $this->assign("search", $search);
        $this->display();
    }

    public function viewinfo() {
        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";
        $this->assign("aType", $Bconfig['APPLY_TYPE']);
        setBackUrl();
        $id = intval($_GET['id']);
        $vo = M('member_apply')->field(true)->find($id);
        $uid = $vo['uid'];
        # 根据借款人身份来区分会员资料
        $mb = M('members')->field('is_transfer')->find($uid);   //判断借款者身份（ 个人  or 企业 ）
        if ($mb['is_transfer']==1){
            $status=1;
            $vx = getBusinessDetail($uid);  //企业借款列表
        }elseif ($mb['is_transfer']==2){
            $status=2;
            $vx = getMemberInfoDetail($uid);    //个人借款列表
        }
        $this->assign("status", $status);
        $this->assign("vo", $vo);
        $this->assign("vx", $vx);
        $this->assign("id", $id);
        $this->display();
    }

    public function viewinfom() {
        $id = intval($_GET['id']);
        $utype=M('members')->where('id='.$id)->field('is_transfer,province,city')->find();
        if($utype['is_transfer']=='1'){
            $vx = getBusinessDetail($id);
        }else{
            $vx = getMemberInfoDetail($id);
        }
        $province = $utype['province'];
        $city = $utype['city'];
        $vx['province'] = M('area')->where('id='.$province)->getField('name');
        $vx['city'] = M('area')->where('id='.$city)->getField('name');
        $map['mf.uid']=$id;
        $list = M('member_data_info mf')
                ->field('mf.*,au.real_name,m.user_name as uname,mf.type')
                ->join("{$this->pre}members m ON m.id=mf.uid")
                        ->join("{$this->pre}ausers au ON au.id=mf.deal_user")
                                ->where($map)->limit($Lsql)->order("mf.id DESC")->select();
//dump($list);exit;
                        $Bconfig = require C("APP_ROOT")."Conf/borrow_config.php";
		//$Bconfig = get_bconf_setting();
		$upload_type = FilterUploadType(FS("Webconfig/integration"));
                $this->assign('upload_type', $upload_type);
                    //    $v['type_name'] = $upload_type[$v['type']]['description'];
        $this->assign('search',$search);
        $this->assign("vx",$vx);
        $this->assign('list',$list);
        
        
        
        
        $this->assign("utype", $utype['is_transfer']);
        $this->display();
    }

    public function doEditCredit() {
        $id = intval($_POST['id']);
        $uid = intval($_POST['uid']);
        $data['id'] = $id;
        $data['deal_info'] = text($_POST['deal_info']);
        $data['apply_status'] = intval($_POST['apply_status']);
        $data['credit_money'] = floatval($_POST['credit_money']);
        $newid = M('member_apply')->save($data);

        if ($newid) {
            //审核通过后资金授信改动
            if ($data['apply_status'] == 1) {
                $vx = M('member_apply')->field(true)->find($id);
                $umoney = M('member_money')->field(true)->find($vx['uid']);

                $moneyLog['uid'] = $vx['uid'];
                if ($vx['apply_type'] == 1) {
                    $moneyLog['credit_limit'] = floatval($umoney['credit_limit']) + $data['credit_money'];
                    $moneyLog['credit_cuse'] = floatval($umoney['credit_cuse']) + $data['credit_money'];
                } elseif ($vx['apply_type'] == 2) {
                    $moneyLog['borrow_vouch_limit'] = floatval($umoney['borrow_vouch_limit']) + $data['credit_money'];
                    $moneyLog['borrow_vouch_cuse'] = floatval($umoney['borrow_vouch_cuse']) + $data['credit_money'];
                } elseif ($vx['apply_type'] == 3) {
                    $moneyLog['invest_vouch_limit'] = floatval($umoney['invest_vouch_limit']) + $data['credit_money'];
                    $moneyLog['invest_vouch_cuse'] = floatval($umoney['invest_vouch_cuse']) + $data['credit_money'];
                }

                if (!is_array($umoney))
                    M('member_money')->add($moneyLog);
                else
                    M('member_money')->where("uid={$vx['uid']}")->save($moneyLog);
            }//审核通过后资金授信改动
            alogs("Members", 0, 1, '成功执行了会员资料通过后资金授信改动的审核操作！'); //管理员操作日志
            $this->success("审核成功", __URL__ . "/infowait" . session('listaction'));
        }else {
            alogs("Members", 0, 0, '执行会员资料通过后资金授信改动的审核操作失败！'); //管理员操作日志
            $this->error("审核失败");
        }
    }

    public function moneyedit() {
        setBackUrl();
        $this->assign("id", intval($_GET['id']));
        $this->display();
    }

    public function doMoneyEdit() {
        $id = intval($_POST['id']);
        $uid = $id;
        $info = text($_POST['info']);
        $done = false;
        if (floatval($_POST['account_money']) != 0) {
            $done = memberMoneyLog($uid, 71, floatval($_POST['account_money']), $info);
        }
        if (floatval($_POST['money_freeze']) != 0) {
            $done = false;
            $done = memberMoneyLog($uid, 72, floatval($_POST['money_freeze']), $info);
        }
        if (floatval($_POST['money_collect']) != 0) {
            $done = false;
            $done = memberMoneyLog($uid, 73, floatval($_POST['money_collect']), $info);
        }
        //记录

        $this->assign('jumpUrl', __URL__ . "/index" . session('listaction'));
        if ($done) {
            alogs("Members", 0, 1, '成功执行了会员余额调整的操作！'); //管理员操作日志
            $this->success("操作成功");
        } else {
            alogs("Members", 0, 0, '执行会员余额调整的操作失败！'); //管理员操作日志
            $this->error("操作失败");
        }
    }

    public function creditedit() {
        setBackUrl();
        $uid = intval($_GET['id']);
        //可用信用额度
        $credit_limit = M('member_money')->field('credit_limit')->where(array('uid'=>$uid))->find();
        // 投资总积分
        $active_integral = M('members')->field('credits')->where(array('id'=>$uid))->find();
        if( empty($credit_limit) ) $credit_limit['credit_limit'] = 0.00;
        $this->assign("id", intval($_GET['id']));
        $this->assign("credit_limit", $credit_limit['credit_limit']);
        $this->assign("active_integral", $active_integral['credits']);

        $this->display();
    }

    public function doCreditEdit() {
        $id = intval($_POST['id']);

        $umoney = M('member_money')->field(true)->find($id);
        if (intval($_POST['credit_limit']) != 0) {
            $moneyLog['uid'] = $id;
            $moneyLog['credit_limit'] = floatval($umoney['credit_limit']) + floatval($_POST['credit_limit']);
            $moneyLog['credit_cuse'] = floatval($umoney['credit_cuse']) + floatval($_POST['credit_limit']);
            if (!is_array($umoney))
                $newid = M('member_money')->add($moneyLog);
            else
                $newid = M('member_money')->where("uid={$id}")->save($moneyLog);
        }
        if (intval($_POST['borrow_vouch_limit']) != 0) {
            $moneyLog = array();
            $moneyLog['uid'] = $id;
            $moneyLog['borrow_vouch_limit'] = floatval($umoney['borrow_vouch_limit']) + floatval($_POST['borrow_vouch_limit']);
            $moneyLog['borrow_vouch_cuse'] = floatval($umoney['borrow_vouch_cuse']) + floatval($_POST['borrow_vouch_limit']);
            if (!is_array($umoney) && !$newid)
                $newid = M('member_money')->add($moneyLog);
            else
                $newid = M('member_money')->where("uid={$id}")->save($moneyLog);
        }
        if (intval($_POST['invest_vouch_limit']) != 0) {
            $moneyLog = array();
            $moneyLog['uid'] = $id;
            $moneyLog['invest_vouch_limit'] = floatval($umoney['invest_vouch_limit']) + floatval($_POST['invest_vouch_limit']);
            $moneyLog['invest_vouch_cuse'] = floatval($umoney['invest_vouch_cuse']) + floatval($_POST['invest_vouch_limit']);
            if (!is_array($umoney) && !$newid)
                $newid = M('member_money')->add($moneyLog);
            else
                $newid = M('member_money')->where("uid={$id}")->save($moneyLog);
        }

        //修改会员信用等级积分（E级->AAA级）
        $userCredits = M('members')->field(true)->find($id);
        if (intval($_POST['credits']) != 0) {
            $moneyLog = array();
            $moneyLog['id'] = $id;
            $moneyLog['credits'] = intval($userCredits['credits']) + intval($_POST['credits']);
            if (!is_array($userCredits) && !$newid)
                $newid = M('members')->add($moneyLog);
            else
                $newid = M('members')->where("id={$id}")->save($moneyLog);
        }

        $this->assign('jumpUrl', __URL__ . "/index" . session('listaction'));
        if ($newid) {
            alogs("Members", 0, 1, '成功执行了会员授信调整的操作！'); //管理员操作日志
            $this->success("操作成功");
        } else {
            alogs("Members", 0, 0, '执行会员授信调整的操作失败！'); //管理员操作日志
            $this->error("操作失败");
        }
    }

    public function _listFilter($list) {
        $row = array();
        foreach ($list as $key => $v) {
            if ($v['recommend_id'] <> 0 && intval($v['recommend_type']) === 0) {//增加判断 && intval($v['recommend_type']) === 0//`mxl:team20141231debug`
                $v['recommend_name'] = M("members")->getFieldById($v['recommend_id'], "user_name");
            } else {
                $v['recommend_name'] = "<span style='color:#000'>无推荐人</span>";
            }
            if ($v['is_transfer'] == MembersModel::MEMBERS_IS_TRANSFER_BUSINESS ) {
                $v['is_vip'] = "企业";
            } else {
                $v['is_vip'] = "个人";
            }
            $v['user_type'] = MembersModel::get_user_type($v['is_transfer']);
            $row[$key] = $v;
        }
        return $row;
    }

    public function getusername() {
        $uname = M("members")->getFieldById(intval($_POST['uid']), "user_name");
        if ($uname)
            exit(json_encode(array("uname" => "<span style='color:green'>" . $uname . "</span>")));
        else
            exit(json_encode(array("uname" => "<span style='color:orange'>不存在此会员</span>")));
    }

    public function idcardedit() {

        $model = D(ucfirst($this->getActionName()));
    //    setBackUrl();
        $id = intval($_REQUEST['id']);
        $vo = $model->find($id);
        $vx = M('member_info')->where("uid={$id}")->find();
        if (!is_array($vx)) {
            M('member_info')->add(array("uid" => $id));
        } else {
            foreach ($vx as $key => $vxe) {
                $vo[$key] = $vxe;
            }
        }
        $this->assign('vo', $vo);
        $this->assign("utype", C('XMEMBER_TYPE'));

        $integration = FilterUploadType(FS("Webconfig/integration"));
        $this->assign('integration', $integration);
        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";
        $to_upload_type = get_upload_type($id);
        $this->assign('to_upload_type', $to_upload_type);

        $this->display();
    }

    //添加身份证信息
    public function doIdcardEdit() {
        $uid = intval($_REQUEST['uid']);
        if (intval($_POST['uptype']) != '1') {
            $model = D(ucfirst($this->getActionName()));
            $model2 = M("member_info");
            if (false === $model->create()) {
                $this->error($model->getError());
            }
            if (false === $model2->create()) {
                $this->error($model->getError());
            }
            $model->startTrans();
            if (!empty($_FILES['imgfile']['name'][0]) && !empty($_FILES['imgfile']['name'][0])) {
                $this->fix = false;
                //设置上传文件规则
                $this->saveRule = 'uniqid';
                $this->savePathNew = C('ADMIN_UPLOAD_DIR') . 'Idcard/';
                $this->thumbMaxWidth = C('IDCARD_UPLOAD_H');
                $this->thumbMaxHeight = C('IDCARD_UPLOAD_W');
                $info = $this->CUpload();
                $data['card_img'] = $info[0]['savepath'] . $info[0]['savename'];
                $data['card_back_img'] = $info[1]['savepath'] . $info[1]['savename'];

                if ($data['card_img'] && $data['card_back_img']) {
                    $model2->card_img = $data['card_img'];
                    $model2->card_back_img = $data['card_back_img'];
                }
            }
            ///////////////////////////
            $result = $model->save();
            $result2 = $model2->save();
            //保存当前数据对象
            if ($result || $result2) { //保存成功
                $model->commit();
                $ms = M("members_status")->where("uid = {$uid}")->count();
                if ($ms) {
                    $dat = array('id_status' => 1, 'id_credits' => 10);
                    M("members_status")->where("uid = {$uid}")->setField($dat);
                } else {
                    $date = array('uid' => $uid, 'id_status' => 1, 'id_credits' => 10);
                    M("members_status")->add($date);
                }
                alogs("Members", 0, 1, '成功执行了会员身份证代传的操作！'); //管理员操作日志
                //成功提示
                $this->assign('jumpUrl', __URL__ . "/info");
                $this->success(L('修改成功'));
            } else {
                $model->rollback();
                alogs("Members", 0, 0, '执行会员身份证代传的操作失败！'); //管理员操作日志
                //失败提示
                $this->error(L('修改失败'));
            }
            return;
        }
        //==其他资料上传==
        $model = M('member_data_info');
        $this->savePathNew = C('MEMBER_UPLOAD_DIR') . 'MemberData/' . $uid . '/';
        $this->saveRule = date("YmdHis", time()) . rand(0, 1000);
        $info = $this->CUpload();
        $savedata['data_url'] = $info[0]['savepath'] . $info[0]['savename'];
        $savedata['size'] = $info[0]['size'];
        $savedata['ext'] = $info[0]['extension'];
        $savedata['data_name'] = text(urldecode($_POST['name']));
        $savedata['type'] = intval($_POST['data_type']);
        $savedata['uid'] = $uid;
        $savedata['add_time'] = time();
        $savedata['status'] = 0;
        if (false === $model->create($savedata)) {
            $this->error($model->getError());
        } elseif ($result = $model->add()) {
            alogs("Members", 0, 1, '成功执行了文件代传代传的操作！');
            $this->assign('jumpUrl', __URL__ . "/viewinfom?id=" .$uid);
            $this->success(L('文件上传成功'));
        } else {
            alogs("Members", 0, 0, '执行会员文件代传的操作失败！'); //管理员操作日志
            $this->error(L('文件上传失败'));
        }
        //=====其他资料上传========
    }

    public function memberborrow() {

        $member_id = intval($_REQUEST['member_id']);
        $map = array();

        $map['m.id'] = $member_id;
        //分页处理
        import("ORG.Util.Page");
        $count = M('borrow_investor bi')->join("{$this->pre}members m ON m.id=bi.investor_uid")->where($map)->count('bi.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理

        $field = 'bi.id bid,b.id,bi.investor_capital,bi.investor_interest,bi.invest_fee,bi.add_time,bi.is_auto,m.user_name,m.id mid,m.user_phone,b.borrow_duration,b.repayment_type,m.customer_name,b.borrow_type,b.borrow_name';
        $list = M('borrow_investor bi')->field($field)->join("{$this->pre}members m ON m.id=bi.investor_uid")->join("{$this->pre}borrow_info b ON b.id=bi.borrow_id")->where($map)->limit($Lsql)->order("bi.id DESC")->select();
        $list = $this->mb_listFilter($list);

        //dump($list);exit;
        $this->assign("list", $list);
        $this->assign("member_id", $member_id);
        $this->assign("pagebar", $page);
        $this->display();
    }

    //qi  直标导出
    public function mb_export() {

        import("ORG.Io.Excel");
        alogs("Capitalaccount", 0, 1, '执行了某会员投标记录列表导出操作！'); //管理员操作日志
        $map = array();
        $member_id = intval($_REQUEST['member_id']);
        $map['m.id'] = $member_id;
        //分页处理
        import("ORG.Util.Page");
        $count = M('borrow_investor bi')->join("{$this->pre}members m ON m.id=bi.investor_uid")->where($map)->count('bi.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $pre = $this->pre;
        $field = 'bi.id bid,b.id,bi.investor_capital,bi.investor_interest,bi.invest_fee,bi.add_time,bi.is_auto,m.user_name,m.id mid,m.user_phone,b.borrow_duration,b.duration_unit,b.repayment_type,m.customer_name,b.borrow_type,b.borrow_name';
        $list = M('borrow_investor bi')->field($field)->join("{$this->pre}members m ON m.id=bi.investor_uid")->join("{$this->pre}borrow_info b ON b.id=bi.borrow_id")->where($map)->order("bi.id DESC")->select();
        $list = $this->mb_listFilter($list);

        foreach ($list as $v) {
            $list[$key]['xmoney'] = $money;
        }
        $row = array();
        $row[0] = array('标号', '用户名', '手机号', '客服', '标题', '投资金额', '应得利息', '投资期限', '投资成交管理费', '还款方式', '标种类型', '投标方式', '投标时间');
        $i = 1;
        foreach ($list as $v) {
            if (!$v['bid']) {
                break;
            }
            $row[$i]['uid'] = $v['bid'];
            $row[$i]['user_name'] = $v['user_name'];
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
        $xls->generateXML("mb_export");
    }

    //qi以患者为基础查询直标记录
    public function transferborrow() {

        $member_id = intval($_REQUEST['member_id']);
        $map = array();

        $map['m.id'] = $member_id;
        $map['bi.is_jijin'] = 0;
        //分页处理
        import("ORG.Util.Page");
        $count = M('transfer_borrow_investor bi')->join("{$this->pre}members m ON m.id=bi.investor_uid")->where($map)->count('bi.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理

        $field = 'bi.id bid,b.id,bi.investor_capital,bi.investor_interest,bi.invest_fee,bi.add_time,bi.is_auto,m.user_name,m.id mid,m.user_phone,b.borrow_duration,b.repayment_type,m.customer_name,b.borrow_type,b.borrow_name';
        $list = M('transfer_borrow_investor bi')->field($field)->join("{$this->pre}members m ON m.id=bi.investor_uid")->join("{$this->pre}transfer_borrow_info b ON b.id=bi.borrow_id")->where($map)->limit($Lsql)->order("bi.id DESC")->select();
        $list = $this->mb_listFilter($list);

        //dump($list);exit;
        $this->assign("list", $list);
        $this->assign("member_id", $member_id);
        $this->assign("pagebar", $page);
        $this->display();
    }

    //qi  散标导出
    public function trans_export() {

        import("ORG.Io.Excel");
        alogs("Capitalaccount", 0, 1, '执行了某会员投标记录列表导出操作！'); //管理员操作日志
        $map = array();
        $member_id = intval($_REQUEST['member_id']);
        $map['m.id'] = $member_id;
        $map['bi.is_jijin'] = 0;
        //分页处理
        import("ORG.Util.Page");
        $count = M('transfer_borrow_investor bi')->join("{$this->pre}members m ON m.id=bi.investor_uid")->where($map)->count('bi.id');
        $p = new Page($count, C('ADMIN_PAGE_SIZE'));
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        //分页处理
        $pre = $this->pre;
        $field = 'bi.id bid,b.id,bi.investor_capital,bi.investor_interest,bi.invest_fee,bi.add_time,bi.is_auto,m.user_name,m.id mid,m.user_phone,b.borrow_duration,b.repayment_type,m.customer_name,b.borrow_type,b.borrow_name';
        $list = M('transfer_borrow_investor bi')->field($field)->join("{$this->pre}members m ON m.id=bi.investor_uid")->join("{$this->pre}transfer_borrow_info b ON b.id=bi.borrow_id")->where($map)->order("bi.id DESC")->select();
        $list = $this->mb_listFilter($list);

        foreach ($list as $v) {
            $list[$key]['xmoney'] = $money;
        }
        $row = array();
        $row[0] = array('标号', '用户名', '手机号', '客服', '标题', '投资金额', '应得利息', '投资期限', '投资成交管理费', '还款方式', '标种类型', '投标方式', '投标时间');
        $i = 1;
        foreach ($list as $v) {
            if (!$v['bid']) {
                break;
            }
            $row[$i]['uid'] = $v['bid'];
            $row[$i]['user_name'] = $v['user_name'];
            $row[$i]['user_phone'] = $v['user_phone'];
            $row[$i]['customer_name'] = $v['customer_name'];
            $row[$i]['borrow_name'] = $v['borrow_name'];
            $row[$i]['investor_capital'] = $v['investor_capital'];
            $row[$i]['investor_interest'] = $v['investor_interest'];
            if ($v['repayment_type_num']) {
                $d = "天";
            } else {
                $d = "个月";
            }
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
        $xls->generateXML("trans_export");
    }

    //qi transferborrow
    public function mb_listFilter($list) {

        $Bconfig = require C("APP_ROOT") . "Conf/borrow_config.php";
        $listType = $Bconfig['REPAYMENT_TYPE'];
        $row = array();
        $aUser = get_admin_name();
        foreach ($list as $key => $v) {
            $v['repayment_type_num'] = $v['repayment_type'];
            $v['repayment_type'] = $listType[$v['repayment_type']];
            $v['borrow_type'] = BorrowModel::get_borrow_type($v['borrow_type']);
            if ($v['deadline'])
                $v['overdue'] = getLeftTime($v['deadline']) * (-1);
            if ($v['borrow_status'] == 1 || $v['borrow_status'] == 3 || $v['borrow_status'] == 5) {
                $v['deal_uname_2'] = $aUser[$v['deal_user_2']];
                $v['deal_uname'] = $aUser[$v['deal_user']];
            }

            $v['last_money'] = $v['borrow_money'] - $v['has_borrow']; //新增剩余金额
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
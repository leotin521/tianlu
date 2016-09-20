<?php
//解决火狐swfupload的session bug
if (isset($_POST[session_name()]) && empty($_SESSION)) {
    session_destroy();
    session_id($_POST[session_name()]);
    session_start();
}
class FundAction extends ACommonAction {

    public function index() {
        $designer = FS("Webconfig/designer");
        $this->assign("designer", $designer);
        $this->getBorrowListByCondition(BorrowModel::BID_SINGLE_CONFIG_STATUS_VIEW_PASS);
        $this->display();
    }

    public function endtran() {
        $designer = FS("Webconfig/designer");
        $this->assign("designer", $designer);
        $where['b.borrow_status'] = array(
            array('eq', BorrowModel::BID_SINGLE_CONFIG_STATUS_SUCCESS),
            array('eq', BorrowModel::BID_SINGLE_CONFIG_STATUS_FINISH_REPAY),
            'or'
        );
        $where['_string'] = 'b.borrow_type=' . BorrowModel::BID_CONFIG_TYPE_FINANCIAL;
        $this->getBorrowListByCondition(false, $where);
        $this->display();
    }

    /**
     * 添加定投宝
     * 
     */
    public function _addFilter() {
        $designer = FS("Webconfig/designer");
        $this->assign('designer',$designer);
        $btype = array("3" => $designer[7]);
        $this->assign("borrow_type", $btype);
        //
        $vo = M('members')->field("id,user_name")->where("is_transfer=1")->select(); //查询出所有流转会员
        $userlist = array();
        if (is_array($vo)) {
            foreach ($vo as $key => $v) {
                $userlist[$v['id']] = $v['user_name'];
            }
        }
        // 担保公司list
        $danbao = M('article')->field('id,title')->where('type_id=7')->select();
        $dblist = array();
        if (is_array($danbao)) {
            foreach ($danbao as $key => $v) {
                $dblist[$v['id']] = $v['title'];
            }
        }
        $repayment_type = BorrowModel::get_business_repay_type();
        $bid = M("borrow_info")->where("borrow_type=7")->count();
        if (isset($bid)) {
            $Newid = $bid + 1;
        } else {
            $Newid = 1;
        }
        $this->assign("borrow_name", "DTB-" . str_repeat("0", 8 - strlen($Newid)) . $Newid);
        $this->assign("danbao_list", $dblist); //新增担保
        $this->assign('repayment_type_items', $repayment_type);
        $this->assign("borrow_duration_list", array("1"=>"1","3" => "3", "6" => '6', "9" => '9', "12" => '12', "15" => '15', "18" => '18', "24" => '24')); //基金期限
        $this->assign("userlist", $userlist); //流转会员
      //  $duration_unit = GlobalModel::get_duration_unit(); // 1为月  0为天，
         $duration_unit=1;  //定投宝为月
        $this->assign("duration_unit", $duration_unit);
    }

    public function doAdd() {
        $designer = FS("Webconfig/designer");
        $db = new Model();
        $db->startTrans();
        $repayment_type = intval($this->_post('repayment_type')); //TODO:XX
        $borrow_money = $this->_post('borrow_money');
        $duration = intval($this->_post('borrow_duration'));
        $duration_unit = intval($this->_post('duration_unit'));
        $rate = floatval($this->_post('borrow_interest_rate')); // 年化收益率
        // 注意第三个参数为false
        $borrow_interest = getBorrowInterest($repayment_type, $borrow_money, $duration, $rate, false, true);
        $per_transfer = intval($this->_post('per_transfer'));
        
       
        if( $duration_unit ) {
             $total = $duration;
            $first_repayment_time = strtotime("+{$duration} months", time());
        } else {
             $total = 1;
            $first_repayment_time = strtotime("+{$duration} days", time());
        }
        $collect_day = ceil((strtotime("-3 days", $first_repayment_time) - time()) / 3600 / 24);  // 募集期期限，现在改成第一次还款日前3天为结束日
        $online_time = htmlspecialchars($this->_post('online_time'), ENT_QUOTES);
        if( strtotime($online_time) <= time() ) {
            $online_time = date('Y-m-d H:i:s',time());
            $borrow_status = BorrowModel::BID_SINGLE_CONFIG_STATUS_VIEW_PASS;
            $collect_time = strtotime("+{$collect_day} days", time());
        }else{
            $borrow_status = BorrowModel::BID_SINGLE_CONFIG_STATUS_WAIT_ONLINE;
            $collect_time = strtotime("+{$collect_day} days", strtotime($online_time)); //募集期从上线之后开始算起
        }
        $bid = M("borrow_info")->where("borrow_type=7")->count();
        if (isset($bid)) {
            $Newid = $bid + 1;
        } else {
            $Newid = 1;
        }
        $borrow_name="DTB-" . str_repeat("0", 8 - strlen($Newid)) . $Newid;
        $borrow_info = array(// 如果数组里显示错误，请考虑php版本是否过低
            'borrow_name' => $borrow_name,
            'borrow_uid' => intval($this->_post('borrow_uid')),
            'borrow_duration' => $duration,
            'duration_unit' => $duration_unit,
            'borrow_money' => $borrow_money,
            'borrow_interest' => $borrow_interest,
            'borrow_interest_rate' => $rate,
            'borrow_type' => BorrowModel::BID_CONFIG_TYPE_FINANCIAL,
            'borrow_status' => $borrow_status,
            'repayment_type' => $repayment_type,
            'add_time' => time(),
            'collect_day' => $collect_day,
            'collect_time' => $collect_time,
            'add_ip' => get_client_ip(),
            'total' => $total,
            'borrow_min' => $per_transfer, // borrow_min和 per_transfer都使用，这样改成按份投标容易切换
            'can_auto' => intval($this->_post('is_auto')),
            'is_xinshou' => intval($this->_post('is_xinshou')),
            'is_taste' => intval($this->_post('is_taste')),
            'online_time' => $online_time,
            'rate_type' => BorrowModel::BID_CONFIG_RATE_TYPE_IMMEDIATE,
            'on_off' => 1  // 删除掉是否显示功能，但为确保程序正常运行，此字段设为1，暂保留字段
        );

        // 如果是即投即息，直接获得项目的截止日期（不是投标截止日期）,满标复审方式在复审时添加截止日期
        $_P_fee = get_global_setting("back_time");
        $endTime = strtotime(date("Y-m-d",strtotime($online_time))." ".$_P_fee);
        $borrow_info['deadline'] = BorrowModel::get_deadline_time($duration_unit, $duration, $endTime);
        $borrow_info['second_verify_time'] = strtotime($online_time);//计息时间，不管复审还是即投

        // 企业合同章从配置里面调取，这里不再重复上传 20150303 minister
        if (!empty($_FILES['imgfile']['name'])) {
            $this->saveRule = date("YmdHis", time()) . rand(0, 1000);
            $this->savePathNew = C('ADMIN_UPLOAD_DIR') . 'Product/';
            $this->thumbMaxWidth = C('PRODUCT_UPLOAD_W');
            $this->thumbMaxHeight = C('PRODUCT_UPLOAD_H');
            $info = $this->CUpload($_FILES['imgfile']); //`mxl:fileup`
            $borrow_info['b_img'] = $info[0]['savepath'] . $info[0]['savename'];
        }

        foreach ($_POST['updata_name'] as $key => $v) {
            $updata[$key]['name'] = $v;
            $updata[$key]['time'] = $_POST['updata_time'][$key];
        }
        $borrow_info['updata'] = serialize($updata);
        $borrow_id = $db->table(C('DB_PREFIX') . 'borrow_info')->add($borrow_info);
        if (!$borrow_id) {
            $db->rollback();
            $this->error('添加失败');
        } else {
         /*   if (empty($_POST['borrow_breif']) || empty($_POST['borrow_capital']) || empty($_POST['borrow_use']) || empty($_POST['borrow_risk'])) {
                $db->rollback();
                $this->error('请填写借款详细信息');
            }*/
            // 借款详情表
            $borrow_detail = array(
                'borrow_id' => $borrow_id,
                'borrow_breif' => $this->_post('borrow_breif'),
                'borrow_capital' => $this->_post('borrow_capital'),
                'borrow_use' => $this->_post('borrow_use'),
                'borrow_risk' => $this->_post('borrow_risk'),
            );
            foreach ($_POST['swfimglist'] as $key => $v) {
                if ($key > 3)
                    break;
                $row[$key]['img'] = substr($v, 1);
                $row[$key]['info'] = $_POST['picinfo'][$key];
            }
            if (!empty($row)) {
                $borrow_detail['borrow_img'] = serialize($row);
            }
            // borrow_detail表数据添加
            if ($db->table(C('DB_PREFIX') . 'borrow_detail')->add($borrow_detail)) {

                // 流转标详情表
                if ($borrow_money % $per_transfer) {
                    $db->rollback();
                    alogs("Tborrow", $borrow_id, 0, '借款金额不是最小投资金额的整数倍！'); //管理员操作日志
                    $this->error('借款金额不是最小投资金额的整数倍');
                }

                $db->commit();
                $borrow_type = BorrowModel::BID_CONFIG_TYPE_FINANCIAL;
                if( $borrow_status == BorrowModel::BID_SINGLE_CONFIG_STATUS_VIEW_PASS && $borrow_info['can_auto'] == 1 ) {
                    autotInvest($borrow_id, $borrow_type);
                }
                alogs("Fund", $borrow_id, 1, '成功执行了'.$designer[7].'信息的添加操作！'); //管理员操作日志
                //成功提示
                $this->assign('jumpUrl', __URL__);
                $this->success(L('新增成功'));
            } else {
                $db->rollback();
                alogs("Fund", $borrow_id, 0, '执行'.$designer[7].'信息的添加操作失败！'); //管理员操作日志
                $this->error('新增失败');
            }
        }
    }

    public function prerelease()
    {
        $this->getBorrowListByCondition(BorrowModel::BID_SINGLE_CONFIG_STATUS_WAIT_ONLINE);
        $designer = FS("Webconfig/designer");
        header("Content-type:text/html;charset=utf-8");
        $this->assign("designer", $designer[7]);
        $this->display();
}

    public function delete(){
        $borrow_id = intval($_POST['borrow_id']);
        if( BorrowModel::delete_borrow_info($borrow_id) ) {
            $msg = '操作成功';
            ajaxmsg($msg, 1);
        }else{
            ajaxmsg('操作失败', 0);
        }
    }

    /**
     * 标满复审中定投宝，复审通过之后重新计算开始与结束时间，以复审当前算借款期限第一天
     * 复审之前投标的记录复审利息，并记录用户投资表里面
     */
    public function waitreview() {
        $where = array(
            'b.borrow_type' => BorrowModel::BID_CONFIG_TYPE_FINANCIAL,
            'b.borrow_status' => BorrowModel::BID_SINGLE_CONFIG_STATUS_WAIT_REVIEW,
            'b.on_off' => 1,
            'b.rate_type' => BorrowModel::BID_CONFIG_RATE_TYPE_FULL_BORROW
        );
        $fields = 'b.id,b.borrow_name,b.borrow_uid,b.borrow_duration,b.borrow_money,b.borrow_fee,b.borrow_interest_rate,'
                . 'b.repayment_type,b.has_borrow,b.add_time,b.full_time,m.user_name,b.borrow_max,b.is_tuijian,b.can_auto';
        $page = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $data = TborrowModel::getTborrowByPage($where, $fields, $page);
        $this->assign("list", $data['tBorrow_items']);
        $this->assign("pagebar", $data['page']);
        $this->assign("xaction", ACTION_NAME);
        $this->display();
    }

    public function edit() {
        $designer = FS("Webconfig/designer");
        $this->assign("designer", $designer);
        $id = intval($_REQUEST['id']);
        $opt = $this->_get('opt');
        if ($opt === 'review')
            $review = true;
        else
            $review = false;
        $vo = TborrowModel::get_borrow_info($id);
        $vo['borrow_user'] = M('members')->field('user_name')->find($vo['borrow_uid']);
        $vo2 = M('borrow_detail')->field('*')->where(array('borrow_id' => $id))->find();
        foreach ($vo2 as $key => $v) {
            if ($key == "borrow_img")
                $vo[$key] = unserialize($v);
            else
                $vo[$key] = $v;
        }
        $this->assign('vo', $vo);
        $this->assign('review', $review);
        $this->display();
    }

    /**
     * 修改的时候，投标期限和回收时间不作改变
     * 整个修改和复审流程为一个事务，如果嵌套事务，那么在进行下一个事务之前，会对上一个事务进行commit操作，从而导致数据不完整。
     */
    public function doEdit() {
        $designer = FS("Webconfig/designer");
        $db = new Model();
        $db->startTrans();
        $borrow_id = (int) $this->_post('id');
        $binfo = M('borrow_info')->field('borrow_status,borrow_duration,borrow_money,collect_day,duration_unit')->where(array('id' => $borrow_id))->find();
        $borrow_fee = $this->_post('borrow_fee');
        if( $borrow_fee === '' ) {
            $borrow_fee = BorrowModel::get_fee_borrow_manage($binfo['borrow_duration'], $binfo['borrow_money'], $binfo['duration_unit'] );
        }
        $borrow_info = array(// 如果数组里显示错误，请考虑php版本是否过低
           // 'borrow_name' => $this->_post('borrow_name'),
            //'add_ip' => get_client_ip(),
            'borrow_max' => $this->_post('borrow_max'), // 单人最大购买份数
            'is_tuijian' => intval($this->_post('is_tuijian')),
            'borrow_fee' => $borrow_fee,
          //  'on_off' => intval($this->_post('is_show'))
        );
        
        //预告中的直投标可以修改时间
        if (isset($_POST['online_time'])){
            $online_time = $this->_post('online_time');
            if( strtotime($online_time) <= time() ) {
                $borrow_info['online_time'] = date('Y-m-d H:i:s',time());
            }else{
                $borrow_info['online_time'] = $online_time;
            }
            //募集期时间更改
            $borrow_info['collect_time'] = strtotime("+{$binfo['collect_day']} days", strtotime($borrow_info['online_time'])); //募集期从上线之后开始算起;
        }

        //如果是即投计息，截止时间更改
        if( $binfo['rate_type'] == BorrowModel::BID_CONFIG_RATE_TYPE_IMMEDIATE ) {
            $_P_fee = get_global_setting("back_time");
            $endTime = strtotime(date("Y-m-d",strtotime($borrow_info['online_time']))." ".$_P_fee);
            $borrow_info['deadline'] = BorrowModel::get_deadline_time($binfo['duration_unit'], $binfo['borrow_duration'], $endTime);
            $borrow_info['second_verify_time'] = strtotime( $borrow_info['online_time']);
        }
        
        if (!empty($_FILES['imgfile']['name'])) {
            $this->saveRule = date("YmdHis", time()) . rand(0, 1000);
            $this->savePathNew = C('ADMIN_UPLOAD_DIR') . 'Product/';
            $this->thumbMaxWidth = C('PRODUCT_UPLOAD_W');
            $this->thumbMaxHeight = C('PRODUCT_UPLOAD_H');
            $info = $this->CUpload($_FILES['imgfile']); //`mxl:fileup`
            $borrow_info['b_img'] = $info[0]['savepath'] . $info[0]['savename'];
        }
        // 添加处理意见
        $bs = intval($_POST['borrow_status']);
        $access_status = array(
            BorrowModel::BID_SINGLE_CONFIG_STATUS_REVIEW_FAIL,
            BorrowModel::BID_SINGLE_CONFIG_STATUS_REPAYMENT
        );
        if ($bs > 0 && in_array($bs, $access_status)) { // 复审
            $review = true;
            $verify_info['borrow_id'] = intval($_POST['id']);
            $deal_info = text($_POST['deal_info_2']);
            if( $deal_info == '' ) $deal_info = text($_POST['deal_info']);
            $verify_info['deal_info_2'] = $deal_info;
            $verify_info['deal_user_2'] = $this->admin_id;
            $verify_info['deal_time_2'] = time();
            $verify_info['deal_status_2'] = $bs;
            $verify_res = $db->table(C('DB_PREFIX') . 'borrow_verify')->add($verify_info);
            if (!$verify_res) {
                $db->rollback();
                $this->error('复审失败');
            }
            if (!alogs("borrowApproved", $verify_res, 1, '复审操作失败！', '', $db)) {//管理员操作日志
                $db->rollback();
                $this->error('复审失败');
            }

            //借款天数、还款时间
            $endTime = strtotime(date("Y-m-d", time()) . " " . get_global_setting('back_time'));
            //复审通过时，借款时间都变成当前时间，截止日期也重新计算
            $borrow_info['borrow_status'] = $bs;
            $borrow_info['second_verify_time'] = time();
        }

        foreach ($_POST['updata_name'] as $key => $v) {
            $updata[$key]['name'] = $v;
            $updata[$key]['time'] = $_POST['updata_time'][$key];
        }
        $borrow_info['updata'] = serialize($updata);
        if ($db->table(C('DB_PREFIX') . 'borrow_info')->where(array('id' => $borrow_id))->save($borrow_info) === false) {
            $db->rollback();
            $this->error('修改失败');
        } else {
//            if (empty($_POST['borrow_breif']) || empty($_POST['borrow_capital']) || empty($_POST['borrow_use']) || empty($_POST['borrow_risk'])) {
//                $db->rollback();
//                $this->error('请填写借款详细信息');
//            }
            // 借款详情表
            $borrow_detail = array(
                'borrow_breif' => $this->_post('borrow_breif'),
                'borrow_capital' => $this->_post('borrow_capital'),
                'borrow_use' => $this->_post('borrow_use'),
                'borrow_risk' => $this->_post('borrow_risk'),
            );
            foreach ($_POST['swfimglist'] as $key => $v) {
                if ($key > 3)
                    break;
                $row[$key]['img'] = substr($v, 1);
                $row[$key]['info'] = $_POST['picinfo'][$key];
            }
            if (!empty($row)) {
                $borrow_detail['borrow_img'] = serialize($row);
            }
            // borrow_detail表数据添加
            if ($db->table(C('DB_PREFIX') . 'borrow_detail')->where(array('borrow_id' => $borrow_id))->save($borrow_detail) !== false) {
                // 流转标详情表
                if (!empty($_FILES['picpath']['name'])) {
                    // $this->saveRule = 'uniqid';
                    $this->saveRule = date("YmdHis", time()) . rand(0, 1000);
                    $this->savePathNew = C('ADMIN_UPLOAD_DIR') . 'Hetong/';
                    $this->thumbMaxWidth = C('HETONG_UPLOAD_H');
                    $this->thumbMaxHeight = C('HETONG_UPLOAD_W');
                    $info = $this->CUpload($_FILES['picpath']); //`mxl:fileup`
                    $business_detail['hetong_img'] = $info[0]['savepath'] . $info[0]['savename'];
                }
                if (!empty($business_detail)) {
                    if ($db->table(C('DB_PREFIX') . 'business_detail')->where(array('borrow_id' => $borrow_id))->save($business_detail) === false) {
                        $db->rollback();
                        return false;
                    }
                }

                if (isset($review)) {
                    // 原状态一定要为待复审状态，防止重复提交
                    if ($binfo['borrow_status'] == BorrowModel::BID_SINGLE_CONFIG_STATUS_WAIT_REVIEW) {
                        // 复审通过
                        if ($bs == BorrowModel::BID_SINGLE_CONFIG_STATUS_REPAYMENT) {
                            $review_ret = TborrowModel::tBorrowApproved($borrow_id, $db);
                        } else {
                            $review_ret = borrowRefuse($borrow_id, 3, $db);
                        }
                        if (!empty($review_ret)) {
                            $db->commit();
                            alogs(ACTION_NAME, $borrow_id, 1, '操作成功'); //管理员操作日志
                            $this->success('操作成功', U('tborrow/waitreview'));
                            die;
                        } else {
                            $db->rollback();
                            $this->error('操作失败');
                        }
                    } else {
                        $db->rollback();
                        $this->error('非法操作');
                    }
                } else {
                    $db->commit();
                    alogs("Tborrow", $borrow_id, 1, '成功修改了'.$designer[7].'信息的修改操作！'); //管理员操作日志
                }
                //成功提示
                $this->assign('jumpUrl', __URL__);
                $this->success(L('操作成功'));
            } else {
                $db->rollback();
                error_log("Exception: " . __METHOD__ . ": 修改或复审时操作失败"); //管理员操作日志
                $this->error('操作失败');
            }
        }
    }

    // 还款中的借款
    public function repayment() {
        $designer = FS("Webconfig/designer");
        $this->assign("designer", $designer);
        // 分页处理
        $status = array(
            BorrowModel::BID_SINGLE_CONFIG_STATUS_REPAYMENT,
            BorrowModel::BID_SINGLE_CONFIG_STATUS_PLATFORM_REPAY
        );
        $where = array(
            'b.borrow_type' => BorrowModel::BID_CONFIG_TYPE_FINANCIAL,
        );
        $where['b.borrow_status'] = array('in', implode(',', $status));
        $data = $this->getBorrowListByCondition(false, $where);
       // dump($data);exit;
        $this->assign("list", $data['tBorrow_items']);
        $this->assign("pagebar", $data['page']);
        $this->assign("xaction", ACTION_NAME);
        $this->display();
    }
    /**
     * @todo 还款计划
     */
    public   function repaymentdetail(){
        $borrow_id = intval($_GET['borrow_id']);
        $borrow_uid = intval($_GET['borrow_uid']);
        $list = getBorrowInvest($borrow_id,$borrow_uid);
        $this->assign("borrow_uid",$borrow_uid);
        $this->assign("list",$list);
        $this->display();
    }
    /**
     * @todo 还款
     * 
     */
    public function repaymentoperation(){
        $borrow_id=  intval($_GET['bid']);
        $sort_order=  intval($_GET['sort_order']);   
        $result = borrowRepayment($borrow_id,$sort_order,1);//这里还款相当于用户自己还 2015/07/07与fan确认
        if($result===true){
            $this->success('还款成功');
        }else{
            $msg=$result ? $result :"还款失败";
            $this->error($msg);
        }
    }

    protected function _AfterDoEdit() {
        switch (strtolower(session('listaction'))) {
            case "waitverify":
                $v = M('transfer_borrow_info')->field('borrow_uid,borrow_status,deal_time')->find(intval($_POST['id']));
                if (!empty($v['deal_time'])) {
                    break;
                }
                if (empty($v['deal_time'])) {
                    $newid = M('members')->where("id={$v['borrow_uid']}")->setInc('credit_use', floatval($_POST['borrow_money']));
                    if ($newid)
                        M('transfer_borrow_info')->where("id={$v['borrow_uid']}")->setField('deal_time', time());
                }
                break;
        }
    }

    public function _listFilter($list) {
        $listType = C('REPAYMENT_TYPE');
        $row = array();
        foreach ($list as $key => $v) {
            $v['repayment_type'] = $listType[$v['repayment_type']];
            $v['invest_num'] = M('borrow_investor')->where("borrow_id={$v['id']}")->count();
            $v['borrow_user'] = M('members')->field('user_name')->find($v['borrow_uid']);
            $row[$key] = $v;
        }
       // dump($row);exit;
        return $row;
    }

    public function getusername() {
        $uname = M("members")->field("is_transfer,user_name")->find(intval($_POST['uid']));
        if ($uname['user_name'] && $uname['is_transfer'] == 1)
            exit(json_encode(array("uname" => "<span style='color:green'>" . $uname['user_name'] . "</span>")));
        elseif ($uname['user_name'] && $uname['is_transfer'] == 0)
            exit(json_encode(array("uname" => "<span style='color:black'>此会员不是".MembersModel::get_user_type(1)."</span>")));
        elseif (!is_array($uname))
            exit(json_encode(array("uname" => "<span style='color:orange'>不存在此会员</span>")));
    }

    //swf上传图片
    public function swfupload() {
        if ($_POST['picpath']) {
            $imgpath = substr($_POST['picpath'], 1);
            if (in_array($imgpath, $_SESSION['imgfiles'])) {
                unlink(C("WEB_ROOT") . $imgpath);
                $thumb = get_thumb_pic($imgpath);
                $res = unlink(C("WEB_ROOT") . $thumb);
                if ($res)
                    $this->success("删除成功", "", $_POST['oid']);
                else
                    $this->error("删除失败", "", $_POST['oid']);
            }else {
                $this->error("图片不存在", "", $_POST['oid']);
            }
        } else {
            $this->savePathNew = C('ADMIN_UPLOAD_DIR') . 'Product/';
            $this->thumbMaxWidth = C('PRODUCT_UPLOAD_W');
            $this->thumbMaxHeight = C('PRODUCT_UPLOAD_H');
            $this->saveRule = date("YmdHis", time()) . rand(0, 1000);
            $info = $this->CUpload();
            $data['product_thumb'] = $info[0]['savepath'] . $info[0]['savename'];
            if (!isset($_SESSION['count_file']))
                $_SESSION['count_file'] = 1;
            else
                $_SESSION['count_file'] ++;
            $_SESSION['imgfiles'][$_SESSION['count_file']] = $data['product_thumb'];
            echo "{$_SESSION['count_file']}:" . __ROOT__ . "/" . $data['product_thumb']; //返回给前台显示缩略图
        }
    }

    //每个借款标的投资人记录
    public function doinvest() {
        $borrow_id = intval($_REQUEST['borrow_id']);
        $field = 'bi.id bid,b.id,bi.investor_capital,bi.investor_interest,bi.invest_fee,bi.add_time,m.user_name,m.id mid,'
                . 'm.user_phone,b.borrow_duration,b.repayment_type,m.customer_name,b.borrow_name,bi.transfer_duration,b.can_auto';
        $where = array(
            'bi.borrow_id' => $borrow_id
        );
        $page = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $data = BorrowInvestorModel::getBorrowInvestByPage($where, $field, $page);
        $this->assign("list", $data['invest_items']);
        $this->assign("pagebar", $data['page']);
        $this->display();
    }

    // 已流标的借款
    public function liubiaolist() {
        $this->getBorrowListByCondition(BorrowModel::BID_SINGLE_CONFIG_STATUS_UNFINISHED);
        $this->display();
    }

    // 复审未通过的借款
    public function reviewfail() {
        $this->getBorrowListByCondition(BorrowModel::BID_SINGLE_CONFIG_STATUS_REVIEW_FAIL);
        $this->display();
    }

    // 还款中的借款
    public function currentRepayment() {
        $designer = FS("Webconfig/designer");
        $this->assign("designer", $designer);
        // 分页处理
        $where = array(
            'b.borrow_type' => BorrowModel::BID_CONFIG_TYPE_FINANCIAL,
            'b.borrow_status' => BorrowModel::BID_SINGLE_CONFIG_STATUS_REPAYMENT,
            'b.has_borrow' =>  array('gt',0)
        );
        $data = $this->getBorrowListByCondition(false, $where);
        $this->assign("list", $data['tBorrow_items']);
        $this->assign("pagebar", $data['page']);
        $this->assign("xaction",ACTION_NAME);
        $this->display();
    }

    public function getBorrowListByCondition($status = false, $where = false) {
        if (empty($where)) {
            $where = array(
                'b.borrow_type' => BorrowModel::BID_CONFIG_TYPE_FINANCIAL,
                'b.borrow_status' => $status
            );
        }
        $fields = 'b.borrow_type,b.id,b.borrow_name,b.borrow_uid,b.borrow_duration,b.duration_unit,b.borrow_money,b.borrow_fee,b.borrow_interest_rate,'
                . 'b.repayment_type,b.has_borrow,b.add_time,m.user_name,b.borrow_max,b.is_tuijian,b.can_auto,b.online_time,b.rate_type';
        $page = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $data = TborrowModel::getTborrowByPage($where, $fields, $page);
        $this->assign("list", $data['tBorrow_items']);
        $this->assign("pagebar", $data['page']);
        $this->assign("xaction", ACTION_NAME);
        return $data;
    }
    public function _doDelFilter($id){
        $id = is_array($id) ? $id[0] : intval($id);
        $res = M('borrow_investor')->where(array('borrow_id'=>$id))->find();
        if ($res) {
            $this->error('抱歉，已经有投资的标不能执行删除操作');
        } else {
            M("borrow_info")->where("id={$id}")->delete();
            M("borrow_detail")->where("borrow_id={$id}")->delete();
        }
	}

}

?>

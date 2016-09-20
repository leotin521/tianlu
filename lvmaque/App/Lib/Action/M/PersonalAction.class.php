<?php
// 本类由系统自动生成，仅供测试用途
class PersonalAction extends MobileAction {

    public function index(){

        $member_info = M('member_info')->where("uid={$this->uid}")->select();//个人资料
//        dump($member_info);
//        exit;
        if($member_info[0]['address']!=''){
            $data['is_member_info'] = 1;
        }else{
            $data['is_member_info'] = 0;
        }
        $member_contact_info = M('member_contact_info')->where("uid={$this->uid}")->select();//联系方式
//        dump($member_contact_info);
//        exit;
        // echo M()->getLastSql();
        //exit;
        if($member_contact_info[0]['contact1']!=''){
            $data['is_member_contact_info'] = 1;
        }else{
            $data['is_member_contact_info'] = 0;
        }
        $member_department_info = M('member_department_info')->where("uid={$this->uid}")->select();//单位资料
        if($member_department_info[0]['department_name']!=''){
            $data['is_member_department_info'] = 1;
        }else{
            $data['is_member_department_info'] = 0;
        }
        $member_financial_info = M('member_financial_info')->where("uid={$this->uid}")->select();//财务状况
        if($member_financial_info[0]['fin_monthin']!=''){
            $data['is_member_financial_info'] = 1;
        }else{
            $data['is_member_financial_info'] = 0;
        }
        $business_detail = M('business_detail')->where("uid={$this->uid}")->select();//企业资料
        if($business_detail[0]['business_name']!=''){
            $data['is_business_detail'] = 1;
        }else{
            $data['is_business_detail'] = 0;
        }
        $this->assign("data_status",$data['is_member_info']);
        $this->assign("data_lianxi",$data['is_member_contact_info']);
        $this->assign("data_danwei",$data['is_member_department_info']);
        $this->assign("data_caiwu",$data['is_member_financial_info']);
        $this->assign("data_qiye",$data['is_business_detail']);
        $this->display();
    }

    /**
     * 账户管理-> 个人信息
     */
    public function one(){
        $pre = C('DB_PREFIX');
        $vo = M("members m")
            ->field("m.id,m.user_email,s.id_status,s.phone_status,s.email_status,s.safequestion_status,m.user_phone,mi.*")
            ->join("{$pre}members_status s ON s.uid=m.id")
            ->join("{$pre}member_info mi ON mi.uid=m.id")
            ->where("m.id={$this->uid}")
            ->find();
        $this->assign("vo",$vo);

        $info = get_basic();
        #公司行业
        $business_type = $info['BUSINESS_TYPE'];
        $this->assign("business_type",$business_type);
        #最高学历
        $education = $info['EDUCATION'];
        $this->assign("education",$education);
        #公司规模
        $business_scale = $info['BUSINESS_SCALE'];
        $this->assign("business_scale",$business_scale);
        #月收入
        $month_income = $info['MONTH_INCOME'];
        $this->assign("month_income",$month_income);

        $member_info = M('member_info')->where("uid={$this->uid}")->find();//个人资料
        //dump($member_info);
        //exit;
        //echo M()->getLastSql();
        $this->assign("minfo",$member_info);
        //exit;
        $this->display();
    }
    /**
     * 账户管理-> 修改个人信息
     */
    public function submemberinfo(){
        $model=M('member_info');
        $savedata['education'] = text($_POST['education']);
        $savedata['school'] = text($_POST['school_']);
        $savedata['marry'] = text($_POST['merriage_status']);
        $savedata['address'] = text($_POST['address_']);
        $savedata['range'] = text($_POST['business_type_']);
        $savedata['number'] = text($_POST['officeScale']);
        $savedata['position'] = text($_POST['position']);
        $savedata['income'] = text($_POST['salary']);
        $savedata['uid'] = $this->uid;
        $count = $model->where("uid={$this->uid}")->count("uid");
        if ($count>0){
            $newid = $model->save($savedata);
        }else{
            $newid = $model->add($savedata);
        }
        if($newid){
            ajaxmsg("保存成功",1);
        }
        else ajaxmsg('修改失败或者资料没有改动~', 0);
    }
    /**
     * 上传头像--预览
     */
    public function ajaximg(){
        $max_file_size = 2000000 ;     //上传文件大小限制, 单位BYTE
        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            $uptypes = array(
                'image/jpg',
                'image/jpeg',
                'image/pjpeg',
                'image/png',
                'image/x-png',
                'image/gif',
            );
            if (!is_uploaded_file($_FILES['file']['tmp_name'])) //是否存在文件
            {
                ajaxmsg('图片不存在！',0);
            }
            $file = $_FILES["file"];
            if($max_file_size < $file["size"]) //检查文件大小
            {
                ajaxmsg('文件太大！（大小限制2M）',0);
            }
            if(!in_array($file["type"], $uptypes))  //检查文件类型
            {
                ajaxmsg('文件类型不符合要求！（仅支持图片类型）',0);
            }
            $destination_folder = C('MEMBER_HEAD_DIR');    //上传文件路径
            $destination_folder.= date('Y',time())."/".date('m',time())."/";
            if(!file_exists($destination_folder)) mkdir('./'.$destination_folder,0777,true);
            $filename = $file["tmp_name"];
            $pinfo = pathinfo($file["name"]);
            $ftype = '.'.$pinfo['extension'];//'.png';
            $uniqid = md5(uniqid());
            $file_pre = substr($uniqid,0, 7). substr($uniqid,-10, 6);
            $destination = $destination_folder.$file_pre.$ftype;
            if(!move_uploaded_file ($filename, $destination))
            {
                ajaxmsg('移动文件出错！',0);
            }
            if (session('head_url')){
                $this->del_heade_photo(session('head_url'));
            }
            $file_name = $destination_folder.$file_pre;
            $model = new ShearModel();
            $model->imagecropper($destination,200,200);
            $model->imagecropper($destination,120,120);
            $model->imagecropper($destination,48,48);
            $saveSrc = '/'.$destination.'_120x120'.$ftype;
            session('head_url', '/'.$destination);  //存库
            ajaxmsg($saveSrc,1);
        }else{
            ajaxmsg('提交方式不对！',0);
        }
    }
    public function del_heade_photo($url){
        $type = end(explode(".",$url));
        $arr = '.'.$url;
        unlink($arr);
        unlink($arr.'_48x48.'.$type);
        unlink($arr.'_120x120.'.$type);
        unlink($arr.'_200x200.'.$type);
    }

    /**
     * 存入数据库
     */
    public function upload(){
        $model = M('member_info');
        $data['head_img'] = session('head_url');    //存库
        $result = $model->field('uid,head_img')->where("uid = {$this->uid}")->find();
        if(empty($result['uid'])){
            $data['uid'] = $this->uid;
            $model->add($data);
        }else{
            if ($result['head_img']){
                $this->del_heade_photo($result['head_img']);
            }
            $model->where("uid={$this->uid}")->save($data);
        }
        session('head_url',NULL);
        ajaxmsg();
    }
    /**
     * 取消上传删除本地
     */
    public function res(){
        if (session('head_url')){
            $this->del_heade_photo(session('head_url'));
            session('head_url',NULL);
        }
    }
    /**
     * 用户审核中不显示任何信息
     * 基本资料
     */
    public function index_index(){
        $ckid = isset($_GET['ckid'])?$_GET['ckid']:6;
        $apply_type = isset($_GET['tid'])? intval($_GET['tid']) : null;
        $ret = array(
            MembersModel::MEMBERS_IS_TRANSFER_BUSINESS,
            MembersModel::MEMBERS_IS_TRANSFER_PERSONAL
        );
        $user_type = BorrowModel::borrow_validate($this->uid);

        if( $user_type == MembersModel::MEMBERS_IS_TRANSFER_NORMAL ) {
            if( (isset($apply_type) && !in_array($apply_type, $ret)) || (empty($apply_type) && $user_type['is_transfer'] == MembersModel::MEMBERS_IS_TRANSFER_NORMAL )) {
                $this->error('非法请求',U('index/index'));
            }
        }

        if( $user_type['is_transfer'] == MembersModel::MEMBERS_IS_TRANSFER_BUSINESS || $apply_type == MembersModel::MEMBERS_IS_TRANSFER_BUSINESS ) {
            $ckid = 5;
        }
        $this->assign("is_transfer", $user_type['is_transfer']);
        $this->assign("apply_type", $apply_type);
        $this->assign("default",$ckid);
        $this->assign('s', intval($_GET['s']));
        $this->display();
    }

    /**
     * 企业基本资料显示
     */
    public function business(){
        $businessModel = M('business_detail');
        $user_id = session('u_id');
        $where = array('uid'=>$user_id);
        $business = $businessModel->where($where)->find();
        $apply = M('borrow_apply')->field('id')->where($where)->find();
        if( !empty($_POST) ) {
            $db = new Model();
            $db->startTrans();
            if ($businessModel->autoCheckToken($_POST)){ // 令牌验证
                $data = array(
                    'uid' => $user_id,
                    'business_name' => $this->_post('business_name'),
                    'legal_person' => $this->_post('legal_person'),
                    'registered_capital' => $this->_post('registered_capital'),
                    'city' => $this->_post('city'),
                    'bianhao' => $this->_post('bianhao'), //genRandChars(10)
                    'bid_money' => $this->_post('bid_money'),
                    'bid_duration' => $this->_post('bid_duration'),
                    'use_type' => $this->_post('use_type'),
                    'repay_source' => $this->_post('repay_source'),
                    'add_time' => date('Y-m-d H:i:s')
                );
                if( empty($business) ) {
                    if($db->table(C('DB_PREFIX').'business_detail')->add($data)) {
                        $borrow_apply = array(
                            'uid' => $user_id,
                            'user_type' => MembersModel::MEMBERS_IS_TRANSFER_BUSINESS,
                            'add_time' => time(),
                            'update_time' => time(),
                            'status' => 1
                        );
                        if( !empty($apply) ) {
                            $up_apply = $db->table(C('DB_PREFIX').'borrow_apply')->where($where)->save($borrow_apply);
                        } else {
                            $up_apply =  $db->table(C('DB_PREFIX').'borrow_apply')->add($borrow_apply);
                        }
                        if( $up_apply ) {
                            // 用户身份证号码
                            if( M('member_info')->where($where)->getfield('uid') ) {
                                $info = array(
                                    'idcard' => $this->_post('idcard')
                                );
                                if( $db->table(C('DB_PREFIX').'member_info')->where($where)->save($info) === false ){
                                    $db->rollback();
                                    $this->error('操作失败');
                                }
                            }else {
                                $info = array(
                                    'uid' => $user_id,
                                    'idcard' => $this->_post('idcard')
                                );
                                if(!$db->table(C('DB_PREFIX').'member_info')->add($info) ) {
                                    $db->rollback();
                                    $this->error('操作失败');
                                }
                            }
                            $businessModel->commit();
                            redirect(__ROOT__."/member/memberinfo/index_index/tid/1/s/1");
                            exit;
                        } else {
                            $db->rollback();
                            $this->error('操作失败');
                        }

                    } else {
                        $db->rollback();
                        $this->error('操作失败');
                    }
                } else { //修改资料

                    if( $businessModel->where(array('uid'=>$user_id))->save($data) !== false ) {
                        $msg = '操作成功';
                        $user_type =M('members')->where(array('id'=>$user_id))->getField('is_transfer');
                        if( $user_type == MembersModel::MEMBERS_IS_TRANSFER_NORMAL ) { // 用户被驳回后可以再次申请，但需修改资料
                            $up_ret = array(
                                'status' => 1,
                                'update_time' => time(),
                                'user_type' => MembersModel::MEMBERS_IS_TRANSFER_BUSINESS
                            );
                            if(  !$db->table(C('DB_PREFIX').'borrow_apply')->where($where)->save($up_ret) ) {
                                $db->rollback();
                                $this->error('操作失败');
                            }
                            $msg = '操作成功，请等待审核';
                        }
                        // 用户身份证号码
                        if( M('member_info')->where($where)->getfield('uid') ) {
                            $info = array(
                                'idcard' => $this->_post('idcard')
                            );
                            if( $db->table(C('DB_PREFIX').'member_info')->where($where)->save($info) === false ){
                                $db->rollback();
                                $this->error('操作失败');
                            }
                        } else {
                            $info = array(
                                'uid' => $user_id,
                                'idcard' => $this->_post('idcard')
                            );
                            if(!$db->table(C('DB_PREFIX').'member_info')->add($info) ) {
                                $db->rollback();
                                $this->error('操作失败');
                            }
                        }
                        $businessModel->commit();
                        redirect(__ROOT__."/member/memberinfo/index_index/tid/1/s/1");
                        exit;
                    } else {
                        $db->rollback();
                        $this->error('操作失败');
                    }
                }
            } else {
                $this->error('请不要重复提交');
            }
        }
        if( !empty($business) ) {
            //获取用户身份评点号
            $idcard = M('member_info')->where(array('uid'=>$user_id))->getField('idcard');
            if(!empty($idcard)) $business['idcard'] = $idcard;
            $this->assign('vo', $business);
        }
        $this->display();
    }
    /**
     * @param num $type
     * 联系方式，单位资料，财务状况
     * 每项增加10积分
     */
    /*
    protected  function mclog($type){
        $conf['type'] = $type;
        $conf['uid'] = $this->uid;
        $cont = M('member_creditslog')->where($conf)->count('id');
        if ($cont<1){
            $chars = "";
            switch($type) {
                case 31:
                    $chars="联系方式";
                    break;
                case 32:
                    $chars="单位资料";
                    break;
                case 33:
                    $chars="财务状况";
                    break;
                default :
                    break;
            }
            memberCreditsLog($this->uid,$type,10,$chars."认证通过,奖励积分10");
        }
    }
    */
    public function editindex(){
        $model = M('member_contact_info');
        $vo = $model->find($this->uid);
        //dump($vo);
        //exit;
        $this->assign("vo",$vo);
        $this->display();
    }
    /**
     * 联系方式
     */
    public function editcontact(){

        $model=M('member_contact_info');
        if(!$_POST){
            $vo = $model->find($this->uid);
            if(!is_array($vo)) $model->add(array('uid'=>$this->uid));
            else $this->assign('vo',$vo);
            $json['html'] = $this->fetch();
            echo $json['html'];
            exit;
        }
        $count = $model->where(array('uid'=>$this->uid))->count("uid");
        $savedata = textPost($_POST);
        $savedata['uid'] = $this->uid;
        if ($count>0){
            $newid = $model->save($savedata);
        }else{
            $newid = $model->add($savedata);
        }
        if($newid!==false){
            ajaxmsg('操作成功',1);
        }else{
            ajaxmsg('操作失败',0);

        }
    }
    /**
     * 单位资料
     */

    public function qiye(){
        $model = M('member_department_info');
        $vo = $model->find($this->uid);
        $this->assign("vo",$vo);
        $this->assign("datas",$vo['department_year']);
        $info = get_basics();
        $this->assign("datas",$gongzuonianxian = $info['GZNX_TIME']);
        //dump($gongzuonianxian = $info['GZNX_TIME']);
        $this->display();
    }

    public function editdepartment(){
        $model=M('member_department_info');
        if(!$_POST){
            $vo = $model->find($this->uid);
            if(!is_array($vo)) $model->add(array('uid'=>$this->uid));
            else $this->assign('vo',$vo);
            $json['html'] = $this->fetch();
            echo $json['html'];
            exit;
        }
        $count = $model->where(array('uid'=>$this->uid))->count("uid");
        $savedata = textPost($_POST);
        $savedata['uid'] = $this->uid;

        if ($count>0){
            $newid = $model->save($savedata);
        }else{
            $newid = $model->add($savedata);
        }
        if($newid!==false){
            ajaxmsg('操作成功',1);
        }else{
            ajaxmsg('操作失败',0);

        }
    }
    /**
     * 财务状况
     */
    public function caiwu(){
        $model = M('member_financial_info');
        $vo = $model->find($this->uid);
        //dump($vo);
        $info = get_basics();
        //dump($info);
        $this->assign("datas",$gongzuonianxian = $info['ZHU_FANG']);
        $this->assign("gouche",$gongzuonianxian = $info['GOU_CHE']);
        $this->assign('vo',$vo);
        $this->display();
    }

    public function editfinancial(){
        $model = M('member_financial_info');
        if(!$_POST){
            $vo = $model->find($this->uid);
            if(!is_array($vo)) $model->add(array('uid'=>$this->uid));
            else $this->assign('vo',$vo);
            $json['html'] = $this->fetch();
            echo $json['html'];
            exit;
        }
        $tid = $_POST['tid'];
        $count = $model->where(array('uid'=>$this->uid))->count("uid");
        //file_put_contents("222.txt",M()->getLastSql());
        $savedata = textPost($_POST);
        //file_put_contents("444.txt",$savedata);
        $savedata['uid'] = $this->uid;
        if ($count>0){
            $newid = $model->save($savedata);
            //file_put_contents("333.txt",M()->getLastSql());
        }else{
            $newid = $model->add($savedata);
        }
        if($newid!==false){
            #添加申请表
            if ($tid == 2){
                $info = M('borrow_apply')->where(array('uid'=>$this->uid))->find();
                $data['status'] = 1;
                $data['update_time'] = time();
                if(!is_array($info)){
                    $data['uid'] = $this->uid;
                    $data['user_type'] = MembersModel::MEMBERS_IS_TRANSFER_PERSONAL;    //个人借款者身份
                    $data['add_time'] = time(); //记录第一次添加时间
                    M('borrow_apply')->add($data);
                    file_put_contents("111.txt",M()->getLastSql());
                }else{
                    $data['user_type'] = MembersModel::MEMBERS_IS_TRANSFER_PERSONAL;
                    M('borrow_apply')->where(array('uid'=>$this->uid))->save($data);
                }
            }
            ajaxmsg('操作成功',1);
        }else{
            ajaxmsg('操作失败',0);

        }
    }


    /**
     * 上传资料
     */
    public function editdata(){
        $integration = FilterUploadType(FS("Webconfig/integration"));
        $this->assign('integration',$integration);

        $Bconfig = require C("APP_ROOT")."Conf/borrow_config.php";
        $to_upload_type = get_upload_type($this->uid);
        $model=M('member_data_info');
        if(!$_FILES){
            import("ORG.Util.Page");
            $count = $model->where("uid={$this->uid}")->count('id');
            $p = new Page($count, 15);
            $page = $p->show();
            $Lsql = "{$p->firstRow},{$p->listRows}";
            $list = $model->field('id,data_url,data_name,add_time,status,type,ext,size,deal_info,deal_credits')->where("uid={$this->uid}")->order("type DESC")->limit($Lsql)->select();
            $this->assign('Bconfig',$Bconfig);
            $this->assign('to_upload_type', $to_upload_type);   //待上传的类型
            $this->assign('list',$list);
            $this->assign('page',$page);
            $json['html'] = $this->fetch();
            echo $json['html'];
            exit;
            //exit(json_encode($json));
        }
        $uptypes = array(
            'image/jpg',
            'image/jpeg',
            'image/png',
            'image/pjpeg',
            'image/gif',
            'image/bmp',
            'image/x-png'
        );
        $file = $_FILES["uploadFile"];
        switch ($file['error'])
        {
            case 1:
            case 2:
                ajaxmsg('文件大小超过限制！（大小限制为2M）',0);
                break;
            case 3:
            case 4:
            case 6:
            case 7:
                ajaxmsg('文件上传失败！',0);
                break;
            default:
                continue;
        }
        if(!in_array($file["type"], $uptypes))  //检查文件类型
        {
            ajaxmsg('文件类型不符合要求！（仅支持图片类型）',0);
        }
        $this->savePathNew = C('MEMBER_UPLOAD_DIR').'MemberData/'.$this->uid.'/' ;
        $this->saveRule = date("YmdHis",time()).rand(0,1000);
        $this->allowExtss = array('jpg', 'gif', 'png', 'jpeg');
        $info = $this->CUpload();

        $savedata['data_url'] = $info[0]['savepath'].$info[0]['savename'];
        $savedata['size'] = $info[0]['size'];
        $savedata['ext'] = $info[0]['extension'];
        $savedata['data_name'] = text(urldecode($_GET['name']));
        $savedata['type'] = intval($_GET['data_type']);
        $savedata['uid'] = $this->uid;
        $savedata['add_time'] = time();
        $savedata['status'] = 0;

        if (false === $model->create($savedata)) {
            $this->error($model->getError());
        }elseif ($result = $model->add()) {
            $json['message'] = "文件上传成功";
            $json['status'] = 1;
            exit(json_encode($json));
        } else {
            $json['message'] = "文件上传失败";
            $json['status'] = 0;
            exit(json_encode($json));
        }
    }
    /**
     * 删除资料
     */
    public function delfile(){
        $id = intval($_POST['id']);

        $model=M('member_data_info');
        $vo = $model->field("uid,status")->where("id={$id}")->find();
        if(!is_array($vo)) ajaxmsg("提交数据有误！",0);
        else if($vo['uid']!=$this->uid) ajaxmsg("不是你的资料！",0);
        else if($vo['status']==1) ajaxmsg("审核通过的资料不能删除！",0);
        else{
            $newid = $model->where("id={$id}")->delete();
        }
        if($newid) ajaxmsg();
        else ajaxmsg('删除失败，请重试！',0);
    }

    //左侧页获取会员认证信息//`mxl 20150314`
    public function getStatus(){
        $pre = C('DB_PREFIX');
        $field = "m.user_phone, m.pin_pass, ms.id_status, ms.phone_status, ms.email_status";
        $minfo = M("members m")->where("m.id = {$this->uid}")->field($field)->join("{$pre}members_status ms ON m.id = ms.uid")->find();
        $minfo['user_phone'] = hidecard($minfo['user_phone'],2);
        $re['mail']['status'] = $minfo['email_status'];
        $re['phone']['status'] = $minfo['phone_status'];
        $re['identity']['status'] = $minfo['id_status'];
        $re['password']['status'] = (empty($minfo['pin_pass']) === true) ? "0" : "1";
        $re['mail']['number'] = $re['identity']['number'] = $re['password']['number'] = "";
        $re['phone']['number'] = (intval($minfo['phone_status']) === 1) ? $minfo['user_phone'] : "";
        echo json_encode($re);
        exit;
    }
    /**
     *个人资料
     *
     */
    public function people(){
        $pre = C('DB_PREFIX');
        $info = get_basic();
        #最高学历
        $education = $info['EDUCATION'];
        $this->assign("education",$education);
        #月收入
        $month_income = $info['MONTH_INCOME'];
        $this->assign("month_income",$month_income);


        $model=M('member_info mi');
        if(!$_POST){
            $field = "mi.*,m.user_phone,ms.id_status,ms.phone_status";
            $vo = $model->field($field)->join("{$pre}members m ON mi.uid = m.id")->join("{$pre}members_status ms ON mi.uid = ms.uid")->where("mi.uid = {$this->uid}")->find();
            if(!is_array($vo)) $model->add(array('uid'=>$this->uid));
            else $this->assign('vo',$vo);
            $json['html'] = $this->fetch();
            echo $json['html'];
            exit;
        }
        if ($_POST['_tps']=='post'){

            $model = M('member_info');

            $savedata = textPost($_POST);
            unset($savedata['_tps']);
            $savedata['uid'] = $this->uid;

            if (false === $model->create($savedata)) {
                $this->error($model->getError());
            }elseif ($result = $model->save()) {
                $json['message'] = "修改成功";
                $json['status'] = 1;
                exit(json_encode($json));
            } elseif ($model->save() == 0){
                $json['message'] = "修改成功";
                $json['status'] = 1;
                exit(json_encode($json));
            } else {
                $json['message'] = "修改失败或者资料没有改动";
                $json['status'] = 0;
                exit(json_encode($json));
            }
            $json['html'] = $this->fetch();
            echo $json['html'];
        }else{
            $this->error('非法请求',U('index/index'));
        }
    }

    public function qiyeziliao(){
        $business_detail = M('business_detail')->where("uid={$this->uid}")->select();//企业资料
        if($business_detail[0]['business_name']!=''){
            $data['is_business_detail'] = 1;
        }else{
            $data['is_business_detail'] = 0;
        }
        $this->assign("data_qiye",$data['is_business_detail']);
        $this->display();
    }

    public function info_qiyeziliao(){
        $vo = M('business_detail')->field(true)->where("uid = {$this->uid}")->find();
        $member['idcard'] = M('member_info')->field("idcard")->where("uid = {$vo['uid']}")->find();
        foreach($vo as $key => $value){
            $vo['idcard'] = $member['idcard'];
        }
        $this->assign("vo",$vo);
        $this->display();
    }

    public function info_qiyeziliaoadd_and_save(){
        $arr = array();
        $arr = textPost($_POST);
        //ajaxmsg($arr,0);
        //exit;
        if(!$this->uid){
            ajaxmsg('请您登陆！', 0);
        }

        $businessModel = M('business_detail');
        $user_id = session('u_id');
        $where = array('uid'=>$user_id);

        $business = $businessModel->where($where)->find();
        $apply = M('borrow_apply')->field('id')->where($where)->find();
        //if(intval($arr['is_data'])==2) {
        if(2 == 2) {
            unset($arr['is_data']);
            $db = new Model();
            $db->startTrans();
            if ($businessModel->autoCheckToken($arr)){ // 令牌验证
                $data = array(
                    'uid' => $user_id,
                    'business_name' => $arr['business_name'],
                    'legal_person' => $arr['legal_person'],
                    'registered_capital' => $arr['registered_capital'],
                    'city' => $arr['city'],
                    'bianhao' => $arr['bianhao'], //genRandChars(10)
                    'bid_money' => $arr['bid_money'],
                    'bid_duration' => $arr['bid_duration'],
                    'use_type' => $arr['use_type'],
                    'repay_source' => $arr['repay_source'],
                    'add_time' => date('Y-m-d H:i:s')
                );
                if( empty($business) ) {
                    if($db->table(C('DB_PREFIX').'business_detail')->add($data)) {
                        $borrow_apply = array(
                            'uid' => $user_id,
                            'user_type' => MembersModel::MEMBERS_IS_TRANSFER_BUSINESS,
                            'add_time' => time(),
                            'update_time' => time(),
                            'status' => 1
                        );
                        if( !empty($apply) ) {
                            $up_apply = $db->table(C('DB_PREFIX').'borrow_apply')->where($where)->save($borrow_apply);
                        } else {
                            $up_apply =  $db->table(C('DB_PREFIX').'borrow_apply')->add($borrow_apply);
                        }
                        if( $up_apply ) {
                            // 用户身份证号码
                            if( M('member_info')->where($where)->getfield('uid') ) {
                                $info = array(
                                    'idcard' => $arr['idcard']
                                );
                                if( $db->table(C('DB_PREFIX').'member_info')->where($where)->save($info) === false ){
                                    $db->rollback();
                                    ajaxmsg('操作失败', 0);
                                }
                            }else {
                                $info = array(
                                    'uid' => $user_id,
                                    'idcard' => $arr['idcard']
                                );
                                if(!$db->table(C('DB_PREFIX').'member_info')->add($info) ) {
                                    $db->rollback();
                                    AppCommonAction::ajax_encrypt('操作失败', 0);
                                }
                            }
                            $businessModel->commit();
                            ajaxmsg('您的申请资料已经提交，请等待审核！', 1);
                        } else {
                            $db->rollback();
                            ajaxmsg('操作失败', 0);
                        }

                    } else {
                        $db->rollback();
                        ajaxmsg('操作失败', 0);
                    }
                } else { //修改资料
                    if( $businessModel->where(array('uid'=>$user_id))->save($data) !== false ) {
                        $msg = '操作成功';

                        $user_type =M('members')->where(array('id'=>$user_id))->getField('is_transfer');
                        if( $user_type == MembersModel::MEMBERS_IS_TRANSFER_NORMAL ) { // 用户被驳回后可以再次申请，但需修改资料
                            $up_ret = array(
                                'status' => 1,
                                'update_time' => time(),
                                'user_type' => MembersModel::MEMBERS_IS_TRANSFER_BUSINESS
                            );
                            //$sb = $db->table(C('DB_PREFIX').'borrow_apply')->where($where)->save($up_ret);
                            if(1 == 1){
                                $bs =  M('borrow_apply')->where($where)->save($up_ret);
                            }
                                if(!$bs) {
                                $db->rollback();
                                ajaxmsg('操作失败', 0);
                            }

                            $db->commit();
                            $msg = '操作成功，请等待审核';
                            ajaxmsg($msg, 1);
                        }
                        // 用户身份证号码
                        if( M('member_info')->where($where)->getfield('uid') ) {
                            $info = array(
                                'idcard' => $arr['idcard']
                            );
                            if( $db->table(C('DB_PREFIX').'member_info')->where($where)->save($info) === false ){
                                $db->rollback();
                                ajaxmsg('操作失败', 0);
                            }
                        } else {
                            $info = array(
                                'uid' => $user_id,
                                'idcard' => $arr['idcard']
                            );
                            if(!$db->table(C('DB_PREFIX').'member_info')->add($info) ) {
                                $db->rollback();
                                ajaxmsg('操作失败', 0);
                            }
                        }
                        $businessModel->commit();
                        ajaxmsg('您的申请资料已经提交，请等待审核！', 1);
                    } else {
                        $db->rollback();
                        ajaxmsg('操作失败', 0);
                    }
                }
            } else {
                ajaxmsg('请不要重复提交', 0);
            }
        }
        if( !empty($business) ) {
//            //获取用户身份评点号
//            $idcard = M('member_info')->where(array('uid'=>$user_id))->getField('idcard');
//            if(!empty($idcard)) $business['idcard'] = $idcard;
//            $data['business_name'] = $business['business_name'];  //企业名称：
//            $data['bianhao'] = $business['bianhao'];   //注册号：
//            $data['legal_person'] = $business['legal_person'];   //法人代表：
//            $data['idcard'] = $business['idcard'];  //身份证号：
//            $data['registered_capital'] = $business['registered_capital'];   //注册资金：
//            $data['city'] = $business['city'];    //所在地：
//            $data['bid_money'] = $business['bid_money'];    //借款金额：
//            $data['bid_duration'] = $business['bid_duration'];   //周期：
//            $data['use_type'] = $business['use_type'];   //借款用途：
//            $data['repay_source'] = $business['repay_source'];   //还款来源：
//            ajaxmsg($data, 1);
        }
    }
}
<?php

class RewardadmAction extends MobileAction
{
    public function index(){
        $this->display();
    }

    //红包列表
    public function redbaolist(){
        $bonus_config = BonusModel::get_bonus_config();
        // 已发放未领取红包
        $status = isset($_GET['status']) ? intval($_GET['status']) : 1;
        $uid = $this->uid;

        $page = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $_GET['p'] = $page;

        /*已生成红包开始*/
        $where1 = array(
            'uid' => $uid,
            'status' => BonusModel::BONUS_STATUS_UNRECEIVE,
            'validate_et' => array('gt', date('Y-m-d H:i:s', time()))
        );
        //dump($where1);
        $bonus_items1 = BonusModel::get_bonus_byPage($where1, '*', null, null, $page);
        //dump($bonus_items1);
        if( !empty($bonus_items1['data']) ) {
            $bonus_items1['data'] = BonusModel::get_m_url_format($bonus_items1['data']);
            $send_uids = only_array($bonus_items1['data'], 'uid');
            $map['id'] = array('in', implode(',', $send_uids));
            $user_items = M('members')->field('user_name,id')->where($map)->select();
            if( !empty($user_items) ) {
                for($i=0; $i<count($bonus_items1['data']); $i++) {
                    foreach( $user_items as $val ) {
                        if( $bonus_items1['data'][$i]['uid'] == $val['id'] ) {
                            $bonus_items1['data'][$i]['source_name'] = $val['user_name'];
                            break;
                        }
                    }
                }
            }
        }
        $_list1 = array();
        foreach($bonus_items1['data'] as $k=>$value){
            $_list1[$k]['create_time'] = date('Y-m-d',strtotime($value['create_time'])); //生成时间
            $_list1[$k]['validate_et'] = date('Y-m-d',strtotime($value['validate_et'])); //过期时间
            $_list1[$k]['share_url'] = $value['share_url']; //生成连接
            $_list1[$k]['bonus_money'] = $value['bonus_money']; //金额

        }
        if(is_array($_list1)){
            $data1['list'] = $_list1;
        }else{
            $data1 = '暂无相关数据！';
        }
        //dump($data1);exit;
        $this->assign("datas1",$data1['list']);
        $this->assign("page",$bonus_items1['page']);
        //dump($bonus_items1['page']);
        /*已生成红包结束*/
        $this->display();
    }

    function redbaolist2(){
        $bonus_config = BonusModel::get_bonus_config();
        // 已发放未领取红包
        $status = isset($_GET['status']) ? intval($_GET['status']) : 4;
        
        //$status = 4;
        $uid = $this->uid;
        switch($status) {
            case 1:
                $where = array(
                    'uid' => $uid,
                    'status' => BonusModel::BONUS_STATUS_UNRECEIVE,
                    'validate_et' => array('gt', date('Y-m-d H:i:s', time()))
                );
                break;
            case 4:
                $where = array(
                    'receive_user_id' => $uid,
                );
                break;
            case 3:
                $where = array(
                    'uid' => $uid,
                    'status' => BonusModel::BONUS_STATUS_UNRECEIVE,
                    'validate_et' => array('lt', date('Y-m-d H:i:s', time()))
                );
                break;
            default:
                $where = array(
                    'status' => BonusModel::BONUS_STATUS_UNRECEIVE,
                    'validate_et' => array('gt', date('Y-m-d H:i:s', time()))
                );
        }
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $bonus_items = BonusModel::get_bonus_byPage($where, '*', null, null, $p);
        if( !empty($bonus_items['data']) ) {
            $bonus_items['data'] = BonusModel::get_m_url_format($bonus_items['data']);
            $send_uids = only_array($bonus_items['data'], 'uid');
            $map['id'] = array('in', implode(',', $send_uids));
            $user_items = M('members')->field('user_name,id')->where($map)->select();
            if( !empty($user_items) ) {
                for($i=0; $i<count($bonus_items['data']); $i++) {
                    foreach( $user_items as $val ) {
                        if( $bonus_items['data'][$i]['uid'] == $val['id'] ) {
                            $bonus_items['data'][$i]['source_name'] = $val['user_name'];
                            break;
                        }
                    }
                }
            }
        }
        $this->assign("datas",$bonus_items['data']);
        $this->assign("page",$bonus_items['page']);
        //dump($bonus_items['data']);
        $this->display();
    }

    function redbaolist3(){
        /*已过期红包开始*/
        $bonus_config = BonusModel::get_bonus_config();
        // 已发放未领取红包
        $status = 3;
        $uid = $this->uid;
        switch($status) {
            case 1:
                $where = array(
                    'uid' => $uid,
                    'status' => BonusModel::BONUS_STATUS_UNRECEIVE,
                    'validate_et' => array('gt', date('Y-m-d H:i:s', time()))
                );
                break;
            case 4:
                $where = array(
                    'receive_user_id' => $uid,
                );
                break;
            case 3:
                $where = array(
                    'uid' => $uid,
                    'status' => BonusModel::BONUS_STATUS_UNRECEIVE,
                    'validate_et' => array('lt', date('Y-m-d H:i:s', time()))
                );
                break;
            default:
                $where = array(
                    'status' => BonusModel::BONUS_STATUS_UNRECEIVE,
                    'validate_et' => array('gt', date('Y-m-d H:i:s', time()))
                );
        }
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $bonus_items = BonusModel::get_bonus_byPage($where, '*', null, null, $p);
        if( !empty($bonus_items['data']) ) {
            $bonus_items['data'] = BonusModel::get_m_url_format($bonus_items['data']);
            $send_uids = only_array($bonus_items['data'], 'uid');
            $map['id'] = array('in', implode(',', $send_uids));
            $user_items = M('members')->field('user_name,id')->where($map)->select();
            if( !empty($user_items) ) {
                for($i=0; $i<count($bonus_items['data']); $i++) {
                    foreach( $user_items as $val ) {
                        if( $bonus_items['data'][$i]['uid'] == $val['id'] ) {
                            $bonus_items['data'][$i]['source_name'] = $val['user_name'];
                            break;
                        }
                    }
                }
            }
        }
        $this->assign('datas3', $bonus_items['data']);
        $this->assign('page', $bonus_items['page']);
        //dump($bonus_items);
        /*已过期红包结束*/
        $this->display();
    }


    // 生成红包
    public function send()
    {
        $bonus_money = floatval($_POST['bonus_money']);//金额
        if( BonusModel::validate_bonus_money($bonus_money) == false ) {
            $ret = '红包金额填写有误';
            ajaxmsg($ret, 0);
        }
        $mm = getMinfo($this->uid);
        if( $mm['user_account'] < $bonus_money ) {
            $ret = '账户余额不足';
            ajaxmsg($ret, 0);
        }else {
            $config_id = getMillisecond();
            //生成配置文件
            $data = array(
                'config_id' => $config_id,
                'uid' => $this->uid,
                'bonus_money' => $bonus_money,
                'source_type' => BonusModel::BONUS_SOURCE_TYPE_USER,
                'bonus_type' => 1,
                'send_way' => 2,
                'take_way' => 1,
                'validate_st' => date('Y-m-d', time()),
                'validate_et' => date('Y-m-d', strtotime("+30 days", time())) //默认有效期一个月
            );
            $db = M();
            $db->startTrans();
            if( BonusModel::create_bonus($data) ) {
                //冻结用户的金钱
                $result = memberMoneyLog($this->uid, 55, -$bonus_money, '生成红包链接成功,冻结金额'.$bonus_money, '', '', 0, $db);
                if( $result == true ) {
                    $db->commit();
                    $bonus_items = BonusModel::get_bonus_byPage("uid = {$this->uid}", '*', null, null, 1);
                    $bonus_items['data'] = BonusModel::get_m_url_format($bonus_items['data']);
                    //$bao = M('bonus')->where("uid = {$this->uid}")->find();
					
                    $ret['url'] = $bonus_items['data'][0]['share_url'];
                    //file_put_contents('1.txt', $ret['url']);
                    $ret['message'] = '亲~，您的红包已生成！';
                    ajaxmsg($ret, 1);
                }else{
                    $db->rollback();
                    $ret = '生成失败';
                    ajaxmsg($ret, 0);
                }
            }else {
                $ret = '生成失败';
                ajaxmsg($ret, 0);
            }
        }
    }
    
    //领取
    public function take()
    {
    	if(empty($this->uid) ) $this->error('请先登录在复制链接');//MembersModel::unlogin_home();
    	$code = htmlspecialchars($_GET['code'], ENT_QUOTES);
    	//dump($code);exit;
    	$config_id = any2Dec($code, 62);
    	$bonus = BonusModel::get_bonus($config_id);
    	if( !empty($bonus) ) {
    		$bonus = $bonus[0];
    		$jump_url = DOMAIN.'/M/rewardadm/redbaolist2?status=4';
    		if( $bonus['uid'] == $this->uid ) {
    			$this->error('对不起，您不能领取自己的红包！', $jump_url);
    		}
    		elseif($bonus['status'] == BonusModel::BONUS_STATUS_TYPE_RECEIVE) {
    			$this->error('对不起，红包被别人抢走了！', $jump_url);
    		}
    		elseif( $bonus['status'] == BonusModel::BONUS_STATUS_UNRECEIVE && strtotime($bonus['validate_et']) < time() ) {
    			$this->error('对不起，此链接已失效！', $jump_url);
    		}elseif($bonus['status'] == BonusModel::BONUS_STATUS_UNRECEIVE && strtotime($bonus['validate_et']) > time() && $bonus['receive_user_id'] == 0) {
    			$db = M();
    			$db->startTrans();
    			$result = BonusModel::take_bonus($this->uid, $config_id, $db);
    			if( $result ) {
    				$db->commit();
    				$this->success('恭喜，领取红包'.$bonus['bonus_money'].'元！', $jump_url);
    			}else{
    				$db->rollback();
    				$this->error('服务器忙');
    			}
    		}
    	}else {
    		$this->error('非法请求');
    	}
    }

    //积分记录和积分兑换列表
    public function myjifen(){
        $expconf = FS("Webconfig/expconf");
        $yq = $expconf[4];
        $data['money'] = $yq['num']*$yq['money'];
        // 投资积分记录
//            $page = intval($arr['page'])? intval($arr['page']):1;
//            $limit = intval($arr['limit'])? intval($arr['limit']):5;
//            $_GET['p'] = $page;
        import('ORG.Util.Page');
        $count      = M('member_integrallog')->where("uid=".$this->uid)->count();
        $Page = new Page($count, 7);
        $show = $Page ->show();
        $totalPage = ceil($count/$limit);
        $Lsql = "{$Page->firstRow},{$Page->listRows}";
        $list = M('member_integrallog')->field(true) ->where("uid=".$this->uid)->limit($Lsql)->order("id desc")->select();
        $_list = array();
        foreach($list as $k=>$value){
            $_list[$k]['add_time'] = date('Y-m-d H:i:s',$value['add_time']); //时间
            $_list[$k]['affect_integral'] = $value['affect_integral'] > 0? '获取':'使用'; //类型
            $_list[$k]['info'] = $value['info']; //详情
            $_list[$k]['integral_log'] = $value['affect_integral'] > 0? '+'.$value['affect_integral']:$value['affect_integral']; //积分
        }

        if(is_array($_list)){
            $data['list'] = $_list;
            $data['totalPage'] = $totalPage;
            $data['nowPage'] =  $page;
        }else{
            $data = '暂无相关数据！';
        }

        // 积分兑换
        $reddemconf1 = FS("Webconfig/reddemconf");
        $_list = array();
        $listya1 = array();
        foreach($reddemconf1 as $k=>$value){
            $data1['goodid'] = $k; //抵现券id
            $data1['money'] = $value['money']; //抵现券金额
            $data1['info'] = '投资每满'.$value['invest_money'].'元可以抵'.$value['money'].'元,有效期'.$value['expired_time'].'个月'; //简介
            $data1['integral'] = $value['integral']; //需要积分
            array_push($listya1,$data1);
        }

        if(is_array($listya1)){
            $data1['list'] = $listya1;
        }else{
            $data1 = '暂无相关数据！';
        }

        $this->assign("duihuan",$data1['list']);
        $integral_info = M("members")->field('integral, invest_credits,active_integral')->where("id=".$this->uid)->find();
        $data['integral'] = $integral_info['integral'];//累计获取投资积分
        $data['active_integral'] = $integral_info['active_integral'];//累计获取投资积分
        $data['integral_use'] = $integral_info['integral']<>$integral_info['active_integral']? $integral_info['integral']-$integral_info['active_integral']:0;//累计获取投资积分


        $this->assign("datas",$data['list']);
        $this->assign("datas_info",$data);
        $this->assign('pages',$show);
        $this->display();
    }

    public function redbao_select_info(){
        $gid = intval($_POST['goodid']);
        $reddemconf1 = FS("Webconfig/reddemconf");
        //ajaxmsg($reddemconf1[$id]);
        $data['redbao_info'] = $reddemconf1[$gid]['money'];
        $data['my_integral'] = M('members')->field("integral")->where("id ={$this->uid}")->find();
        ajaxmsg($data);
    }
    //积分兑换
    public function ajaxcredit()
    {
        if($_POST['amount']!='' && $_POST['goodid']!=''){ // 提交兑换券
            $msg = array(
                'data'=>'',
                'code'=>0,
                'message'=>'兑换成功',
            );

            $reddemconf = FS("Webconfig/reddemconf");

            $amount = intval($_POST['amount']);
            $goodid = intval($_POST['goodid']);
            $need_integral = $amount* $reddemconf[$goodid]['integral'];

            $integral_info = M("members")->field('active_integral,integral')->where("id=".$this->uid)->find();
            $active_integral = $integral_info['active_integral']; //兑换前可用积分
            $integral = $integral_info['integral'];//总积分
            if(!$amount || !$goodid){
                $msg['code'] = 100;
                $msg['message'] = '参数有误！';
                ajaxmsg($msg,0);
            }elseif($active_integral < $need_integral){
                $msg['code'] = 101;
                $msg['message'] = '您的积分不足！';
                ajaxmsg($msg,0);
            }

            $remark = "积分兑换一张".$reddemconf[$goodid]['money']."元优惠券，投资".$reddemconf[$goodid]['invest_money']."元可用";
            $expired_time = strtotime("+{$reddemconf[$goodid]['expired_time']} month");

            M()->startTrans();
            for($i=1; $i<=$amount; $i++){
                $expand_money['uid'] =  $this->uid;
                $expand_money['money'] = $reddemconf[$goodid]['money'];
                $expand_money['remark'] = $remark;
                $expand_money['expired_time']  =  $expired_time;
                $expand_money['add_time'] = time();
                $expand_money['orders'] = "DH".build_order_no();
                $expand_money['invest_money'] = $reddemconf[$goodid]['invest_money'];
                $expand_money['type'] = 98;
                $expand_money['source_uid'] = 0;

                $exp_id = M('expand_money')->add($expand_money);
                if(!$exp_id) break;
            }

            $active_integral = $active_integral - $need_integral;   //兑换后可用积分
            $m_up_id = M("members")->save(array('id'=>$this->uid, 'active_integral'=>$active_integral));

            $data['uid'] = $this->uid;
            $data['type'] = 1;
            $data['affect_integral'] = -$need_integral;  //兑换消耗的积分
            $data['active_integral'] = $active_integral;    //活跃积分
            $data['account_integral'] = $integral;//总积分
            $data['info'] = "兑换优惠券使用".$need_integral."分";
            $data['add_time'] = time();
            $data['add_ip'] = get_client_ip();
            $credits_id = D('member_integrallog')->add($data);

            if($exp_id && $m_up_id && $credits_id){
                M()->commit();
            }else{
                M()->rollback();
                $msg['code'] = 102;
                $msg['message'] = '兑换失败，请联系客服！';
                ajaxmsg($msg,0);
            }

            ajaxmsg($msg,1);

        }
    }
    /**
     * 邀请链接
     */
    public function invite_link(){

        if(empty($_POST['uid']) || empty($this->uid)){
            AppCommonAction::ajax_encrypt('请先登录！',0);
        }
        $expconf = FS("Webconfig/expconf");

        $type_conf = $expconf[1];
        if($type_conf['num']){
            $money = "注册就送".$type_conf['money']."元！";
        }else{
            $money = '';
        }

        $uid = MembersModel::get_user_Encryption($arr['uid']);
        $data['url'] = "http://" . $_SERVER['HTTP_HOST'] . '/i/'. $uid;
        $data['message'] = "100元做投资人，{$money}10-15%年化收益，网上理财赚翻天！从此告别死工资，速速注册吧！";
        $this->assign('data',$data);
        $this->display();
    }

    //邀请列表
    public function yaoqing(){
        if(empty($this->uid)){
            $this->error('请先登录！',0);
        }

        $uid = MembersModel::get_user_Encryption($this->uid);
        $url = "http://" . $_SERVER['HTTP_HOST'] . '/j/'. $uid;
        $this->assign('spread_url', $url);
        $this->display();
    }
    /**
     * 优惠券查询
     */
    /**
     * 优惠券查询
     */
    public function youhuiquan() {
        if(!$this->uid){
            $this->error('请先登录！',0);
        }
        $time = time();
        $condition = "uid = ".$this->uid." and status = 1 and expired_time >".time();
        $conditions = "uid = ".$this->uid;
        $order = ' add_time asc ';

        import('ORG.Util.Page');
        $limit = 5;
        $exp_type = C('EXP_TYPE'); //优惠券类型
        $count      = M('expand_money')->where($condition)->count();
        $Page       = new Page($count,$limit);
        $show = $Page->show();
        $expand_list = M('expand_money')
            ->field('money, invest_money, status, expired_time, type, use_time, remark, is_taste')
            ->where($condition)
            ->limit($Page->firstRow.','.$Page->listRows)
            ->order($order)
            ->select();
        $expand_list = ExpandMoneyModel::get_coupon_type_format($expand_list);
        $_list = array();
        foreach($expand_list as $k=>$v){
            $_list[$k]['money'] = $v['money'];   //优惠卷金额
            $_list[$k]['invest_money'] = $v['invest_money'];  // 每多少金额
            $_list[$k]['funds'] = date('Y-m-d',$v['expired_time']);  //过期时间
            $_list[$k]['exp_type'] = $exp_type[$v['type']];  ///来源
            $_list[$k]['coupon_type'] = $v['coupon_type'];  ///卷类型
            if($v['status']==1 and $v['expired_time']>time()){
                $_list[$k]['status'] = 0;  ///未使用的
            }elseif($v['status']==4){
                $_list[$k]['status'] = 1;  ///已使用
            }elseif($v['status']==1 and $v['expired_time']<time()){
                $_list[$k]['status'] = 2;  ///已过期
            }
            //$_list[$k]['type'] = $v['is_taste']==1? '仅用于投资,不可提现,利息可提现,债权转让不可使用':'仅用于投资,债权转让不可使用';//提示信息

        }

        //$data['coupon_status'] =$status;
        $n_num = M('expand_money')->where("status=1 and expired_time> ".time()." and uid=".$this->uid)->count('id');
        $data['n_num'] = floatval($n_num);  ////统计未使用优惠券

        $n_money = M('expand_money')->where("status=1 and expired_time> ".time()." and uid=".$this->uid)->sum('money');
        $data['n_money'] = floatval($n_money);  //统计已过期优惠券金额

        $y_money = M('expand_money')->where("status=4  and uid=".$this->uid)->sum('money');
        $data['y_money'] = floatval($y_money);  //统计未使用优惠券总额

        $ex_money = M('expand_money')->where("status=1 and expired_time < ".time()." and uid=".$this->uid)->sum('money');
        $data['ex_money'] = ($ex_money=='')? 0:floatval($ex_money);     //统计已过期优惠券金额
        $data['list'] = $_list;
        $data['totalPage'] = $totalPage;
        $data['nowPage'] =  $page;
        $this->assign("data",$data['list']);
        $this->assign("totalPage",$data['totalPage']);
        $this->assign("nowPage",$data['nowPage']);
        $this->assign("vo",$data);
        $this->assign("page",$show);
        //dump($show);




        // 添加奖励记录 edit by  ybh 2015/9/23
//        if(!$this->uid){
//            $this->error('请先登录！',0);
//        }
//        $condition2 .= " uid={$this->uid}";
//        import("ORG.Util.Page");
//        $count2 = M('expand_money')
//            ->where($condition2)
//            ->count('id');
//        $p2 = new Page($count2, 7);
//        $page2 = $p2->show();
//        $Lsql2 = "{$p2->firstRow},{$p2->listRows}";
//
//        $list2 = M('expand_money')
//            ->field(true)
//            ->where($condition2)
//            ->limit($Lsql2)
//            ->order("add_time desc ")
//            ->select();
//        $_list2 = array();
//        $exp_type = C('EXP_TYPE');
//        foreach($list2 as $k=>$v){
//            $_list2[$k]['add_time'] = date('Y-m-d',$v['add_time']);//时间
//            $_list2[$k]['exp_type'] = $exp_type[$v['type']];//类型
//            $_list2[$k]['remark'] = $v['remark'];//获得详情
//            $_list2[$k]['money'] = $v['money'];  //奖励金额
//            if($v['status'] == 1 and $v['expired_time'] > time()){
//                $_list2[$k]['status'] = 0;  //未使用
//            }elseif($v['status'] == 4){
//                $_list2[$k]['status'] = 1;  //已使用
//            }else{
//                $_list2[$k]['status'] = 2;  //已过期
//            }
//            $_list2[$k]['id'] = $v['id'];  //奖励金额
//
//        }
//
//        if(is_array($_list2)){
//            $n_num2 = M('expand_money')->where("status=1 and expired_time> ".time()." and uid=".$this->uid)->count('id');  //统计未使用优惠券
//            $data2['n_num'] = floatval($n_num2);  ////统计未使用优惠券
//
//            $n_money2 = M('expand_money')->where("status=1 and expired_time> ".time()." and uid=".$this->uid)->sum('money');  //统计未使用优惠券金额
//            $data2['n_money'] = floatval($n_money2);  //统计未使用优惠券金额
//
//            $y_money2 = M('expand_money')->where("status=4  and uid=".$this->uid)->sum('money');  //统计已经使用优惠券金额
//            $data2['y_money'] = floatval($y_money2);  //统计已经使用优惠券金额
//
//            $ex_money2 = M('expand_money')->where("status=1 and expired_time < ".time()." and uid=".$this->uid)->sum('money');  //统计已过期优惠券金额
//            $data2['ex_money'] = $ex_money2==''? 0:floatval($ex_money2);     //统计已过期优惠券金额
//            $data2['list'] = $_list2;
//            $data2['totalPage'] = $totalPage;
//            $data2['nowPage'] =  $page2;
//        }else{
//            $data2['message'] = '暂无项目记录';
//        }
//        //dump($data2);
//        $this->assign("data2",$data2['list']);
//        $this->assign("vo",$data2);
//        $this->assign("pages",$page2);
        $this->display();
    }

    /**
     * 优惠券奖励记录
     * @author zhang ji li  2015-03-13
     */
    public function jiangli()
    {

        if(!$this->uid){
            $this->error('请先登录！',0);
        }
        $condition .= " uid={$this->uid}";
        import("ORG.Util.Page");
        $count = M('expand_money')
            ->where($condition)
            ->count('id');
        $p = new Page($count, 7);
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";

        $list = M('expand_money')
            ->field(true)
            ->where($condition)
            ->limit($Lsql)
            ->order("add_time desc ")
            ->select();
        $_list = array();
        $exp_type = C('EXP_TYPE');
        foreach($list as $k=>$v){
            $_list[$k]['add_time'] = date('Y-m-d',$v['add_time']);//时间
            $_list[$k]['exp_type'] = $exp_type[$v['type']];//类型
            $_list[$k]['remark'] = $v['remark'];//获得详情
            $_list[$k]['money'] = $v['money'];  //奖励金额
            if($v['status'] == 1 and $v['expired_time'] > time()){
                $_list[$k]['status'] = 0;  //未使用
            }elseif($v['status'] == 4){
                $_list[$k]['status'] = 1;  //已使用
            }else{
                $_list[$k]['status'] = 2;  //已过期
            }
            $_list[$k]['id'] = $v['id'];  //奖励金额

        }

        if(is_array($_list)){
            $n_num = M('expand_money')->where("status=1 and expired_time> ".time()." and uid=".$this->uid)->count('id');  //统计未使用优惠券
            $data['n_num'] = floatval($n_num);  ////统计未使用优惠券

            $n_money = M('expand_money')->where("status=1 and expired_time> ".time()." and uid=".$this->uid)->sum('money');  //统计未使用优惠券金额
            $data['n_money'] = floatval($n_money);  //统计未使用优惠券金额

            $y_money = M('expand_money')->where("status=4  and uid=".$this->uid)->sum('money');  //统计已经使用优惠券金额
            $data['y_money'] = floatval($y_money);  //统计已经使用优惠券金额

            $ex_money = M('expand_money')->where("status=1 and expired_time < ".time()." and uid=".$this->uid)->sum('money');  //统计已过期优惠券金额
            $data['ex_money'] = $ex_money==''? 0:floatval($ex_money);     //统计已过期优惠券金额
            $data['list'] = $_list;
            $data['totalPage'] = $totalPage;
            $data['nowPage'] =  $page;
        }else{
            $data['message'] = '暂无项目记录';
        }
        //dump($data);
        $this->assign("data",$data['list']);
        $this->assign("vo",$data);
        $this->assign("pages",$page);
        //dump($page);
        $this->display();
    }

    function jingliinfo(){
        $id = intval($_GET['id']);
        if(!$id)$this->error("非法参数！！");
        if(!$this->uid)$this->error("请先登陆");
        $money = M('expand_money')->field("add_time,remark,status,type,money,expired_time")->where("id = {$id} and uid = {$this->uid}")->find();
        $exp_type = C('EXP_TYPE'); //优惠券类型
        $money['type'] = $exp_type[$money['type']];
        if($money['status'] == 1 and $money['expired_time'] > time()){
            $money['status'] = 0;  //未使用
        }elseif($money['status'] == 4){
            $money['status'] = 1;  //已使用
        }else{
            $money['status'] = 2;  //已过期
        }

        $this->assign("data",$money);
        $this->display();
    }


}

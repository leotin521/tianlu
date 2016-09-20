<?php
/**
* 积分管理
*/
class CreditAction extends MCommonAction {

    /**
    * 我的积分首页
    * 
    */
    public function index(){
        //memberCreditsLog($this->uid, 1, -5, "调试测试-5分");
        $integral_info = M("members")->field('integral, invest_credits,active_integral')->where("id=".$this->uid)->find();
        $this->assign('integral_info', $integral_info);
        // 投资积分记录
        import('ORG.Util.Page');
        $count      = M('member_integrallog')->where("uid=".$this->uid)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list = M('member_integrallog')->field(true) ->where("uid=".$this->uid)->limit($Page->firstRow.','.$Page->listRows)->order("id desc")->select();
        $this->assign('list',$list);
        $this->assign('page',$show);
        //投资积分规则
        $_P_fee = get_global_setting();
        $invest_integral = $_P_fee['invest_integral'];
        $this->assign('invest_integral', $invest_integral);
        // 积分兑换
        $reddemconf = FS("Webconfig/reddemconf");
        $this->assign('reddemconf', $reddemconf);  
        
		$this->display();
    }
    
    public function ajaxCredit()
    {
        if($this->isAjax() && $_POST['confirm']=='yes'){ // 提交兑换券
            $msg = array(
                'data'=>'',
                'code'=>0,
                'info'=>'兑换成功',
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
                $msg['info'] = '参数有误！';
                echo json_encode($msg);exit;
            }elseif($active_integral < $need_integral){
                $msg['code'] = 101;
                $msg['info'] = '您的积分不足！';
                echo json_encode($msg);exit;
            }
            if($reddemconf[$goodid]['is_taste'] == '0'){
                $quan = "抵现券";
            }else{
                $quan = "体验券";
            }
            $remark = "积分兑换一张".$reddemconf[$goodid]['money']."元".$quan."，投资".$reddemconf[$goodid]['invest_money']."元可用"; 
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
                    $expand_money['is_taste'] = $reddemconf[$goodid]['is_taste'];
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
                $msg['info'] = '兑换失败，请联系客服！';
            }

            echo json_encode($msg);exit;    
                
        }
    }
    
    
    
    
    //*****************正在使用的方法放在线上****************************//
    public function auth()
    {
        $user = M('members')->where("id={$this->uid}")->find();
        if(!is_array($user)) $this->error("数据有误");
        $this->assign('user',$user);
        $this->getIntegration($this->uid);
        
        $leveconfig = FS("Webconfig/leveconfig");
        $this->assign('leveconfig', $leveconfig);
        
        //上传资料--积分
        $integration = FS("Webconfig/integration");
        $this->assign('integration', $integration);
        
        $uploadtype = FilterUploadType($integration);

        $this->assign('uploadtype', $uploadtype);
        $this->assign('upload_num', count($uploadtype));

        // 检查认证填写状态 $this->checkStatus（）
        $this->checkStatus();
        
        $data['html'] = $this->fetch();
        exit(json_encode($data));
    }
    public function detail(){
		$user = M('members')->where("id={$this->uid}")->find();
		if(!is_array($user)) $this->error("数据有误");
		$this->assign('user',$user);

		$logtype = C('MONEY_LOG');
		$this->assign('log_type',$logtype);

		$map['uid'] = $this->uid;
		if($_GET['start_time']&&$_GET['end_time']){
			$_GET['start_time'] = strtotime($_GET['start_time']." 00:00:00");
			$_GET['end_time'] = strtotime($_GET['end_time']." 23:59:59");
			
			if($_GET['start_time']<$_GET['end_time']){
				$map['add_time']=array("between","{$_GET['start_time']},{$_GET['end_time']}");
				$search['start_time'] = $_GET['start_time'];
				$search['end_time'] = $_GET['end_time'];
			}
		}
		if(!empty($_GET['log_type'])){
				$map['type'] = intval($_GET['log_type']);
				$search['log_type'] = intval($_GET['log_type']);
		}

		$list = getCreditsLog($map,15);

		$this->assign('search',$search);
		$this->assign("list",$list['list']);		
		$this->assign("pagebar",$list['page']);	
        $this->assign("query", http_build_query($search));

		$data['html'] = $this->fetch();
		exit(json_encode($data));
    }
	
	public function integraldetail(){
    	$user = M('members')->where("id={$this->uid}")->find();
		if(!is_array($user)) $this->error("数据有误");
		$this->assign('user',$user);

		$logtype = C('INTEGRAL_LOG');
		$this->assign('log_type',$logtype);

		$map['uid'] = $this->uid;
		if($_GET['start_time']&&$_GET['end_time']){
			$_GET['start_time'] = strtotime($_GET['start_time']." 00:00:00");
			$_GET['end_time'] = strtotime($_GET['end_time']." 23:59:59");
			
			if($_GET['start_time']<$_GET['end_time']){
				$map['add_time']=array("between","{$_GET['start_time']},{$_GET['end_time']}");
				$search['start_time'] = $_GET['start_time'];
				$search['end_time'] = $_GET['end_time'];
			}
		}

		if(!empty($_GET['log_type'])){
				$map['type'] = intval($_GET['log_type']);
				$search['log_type'] = intval($_GET['log_type']);
		}

		$list = getIntegralLog($map,15);
		// var_dump($list);

		$this->assign('search',$search);
		$this->assign("list",$list['list']);		
		$this->assign("pagebar",$list['page']);	
        $this->assign("query", http_build_query($search));
		$data['html'] = $this->fetch();
		exit(json_encode($data));
    }
	
	//信用积分记录导出
	public function export(){
		import("ORG.Io.Excel");

		$map=array();
		$map['uid'] = $this->uid;
		if($_GET['start_time']&&$_GET['end_time']){
			$_GET['start_time'] = strtotime($_GET['start_time']." 00:00:00");
			$_GET['end_time'] = strtotime($_GET['end_time']." 23:59:59");
			
			if($_GET['start_time']<$_GET['end_time']){
				$map['add_time']=array("between","{$_GET['start_time']},{$_GET['end_time']}");
				$search['start_time'] = $_GET['start_time'];
				$search['end_time'] = $_GET['end_time'];
			}
		}
		if(!empty($_GET['log_type'])){
				$map['type'] = intval($_GET['log_type']);
				$search['log_type'] = intval($_GET['log_type']);
		}

		$list = getMoneyLog($map,100000);
		
		$logtype = C('MONEY_LOG');
		$row=array();
		$row[0]=array('序号','发生日期','类型','影响金额','可用余额','冻结金额','待收金额','说明');
		$i=1;
		foreach($list['list'] as $v){
				$row[$i]['i'] = $i;
				$row[$i]['uid'] = date("Y-m-d H:i:s",$v['add_time']);
				$row[$i]['card_num'] = $v['type'];
				$row[$i]['card_pass'] = $v['affect_money'];
				$row[$i]['card_mianfei'] = $v['account_money']+$v['back_money'];
				$row[$i]['card_mianfei0'] = $v['freeze_money'];
				$row[$i]['card_mianfei1'] = $v['collect_money'];
				$row[$i]['card_mianfei2'] = $v['info'];
				$i++;
		}
		
		$xls = new Excel_XML('UTF-8', false, 'moneyLog');
		$xls->addArray($row);
		$xls->generateXML("moneyLog");
	}

	//投资积分记录导出
	public function integralexport(){
		import("ORG.Io.Excel");

		$map=array();
		$map['uid'] = $this->uid;
		if($_GET['start_time']&&$_GET['end_time']){
			$_GET['start_time'] = strtotime($_GET['start_time']." 00:00:00");
			$_GET['end_time'] = strtotime($_GET['end_time']." 23:59:59");
			
			if($_GET['start_time']<$_GET['end_time']){
				$map['add_time']=array("between","{$_GET['start_time']},{$_GET['end_time']}");
				$search['start_time'] = $_GET['start_time'];
				$search['end_time'] = $_GET['end_time'];
			}
		}
		if(!empty($_GET['log_type'])){
				$map['type'] = intval($_GET['log_type']);
				$search['log_type'] = intval($_GET['log_type']);
		}

		$list = getIntegralLog($map,100000);
		
		$logtype = C('INTEGRAL_LOG');
		$row=array();
		$row[0]=array('序号','发生日期','类型','影响积分','剩余活跃积分','总积分','说明');
		$i=1;
		foreach($list['list'] as $v){
				$row[$i]['i'] = $i;
				$row[$i]['uid'] = date("Y-m-d H:i:s",$v['add_time']);
				$row[$i]['card_num'] = $v['type'];
				$row[$i]['card_pass'] = $v['affect_integral'];
				$row[$i]['card_pass2'] = $v['active_integral'];
				$row[$i]['card_mianfei'] = $v['account_integral'];
				$row[$i]['card_mianfei2'] = $v['info'];
				$i++;
		}
		
		$xls = new Excel_XML('UTF-8', false, 'IntegralLog');
		$xls->addArray($row);
		$xls->generateXML("IntegralLog");
	}
    
    private function checkStatus()
    {
        $status = M("members_status")->where('uid='.$this->uid)->find();
                                         
        $status2 = $this->getDataInfo();
        $status = $status + $status2; 
        $this->assign('status', $status);
    }
    private function getDataInfo()
    {
        $arr = array();
        $model=M('member_data_info');
        $list = $model->field('id,status,type,deal_credits')->where("uid={$this->uid}")->select();
        if(count($list)){
            foreach($list as $key=>$val)
            {
                $arr[$val['type']] = $val;
            }
        }
        return $arr;
    }
    
    /**
    * 获取信用总积分和投标总积分
    * 
    * @param int    $uid   // 用户id
    */
    private function getIntegration($uid)
    {
        $array = array();
        $uid = intval($uid);
        // 上传资料积分
        $deal_credits = M("member_data_info")->where("uid='{$uid}' and status='1'")->sum('deal_credits');
        
        $data_credits = M("members_status")
                            ->where("uid='{$uid}'")
                            ->sum('phone_credits+
                                    id_credits+face_credits+
                                    email_credits+account_credits+
                                    credit_credits+
                                    safequestion_credits+
                                    video_credits+
                                    vip_credits');
       $array['credit'] =  $deal_credits + $data_credits;
       $bid = M('member_integrallog')->where("uid='{$uid}'")->sum('affect_integral');                            
       $array['bid'] = $bid;
       $this->assign('credits', $array);
    }
    
   
}
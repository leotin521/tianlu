<?php

class MainAction extends HCommonAction {

    public function index_class(){
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode, true);
        //$arr = AppCommonAction::get_decrypt_json($arr);
        $type = intval($arr['type']);
        $page = intval($arr['page'])? intval($arr['page']):1;
        $limit = intval($arr['limit'])? intval($arr['limit']):5;
        //$type=11;
        $data = '';
        if($type==11){
            $sWhere['b.borrow_type'] = array('lt', BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID);
            $data = $this->getBorrowList($page, $limit, $sWhere);
        }elseif($type==201){//定投宝
            $Zwhere['b.borrow_type'] = BorrowModel::BID_CONFIG_TYPE_FINANCIAL;
            $data = $this->getBorrowList($page, $limit, $Zwhere);
        }elseif($type==301){ //企业直投
            $Dwhere['b.borrow_type'] = BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID;
            $data = $this->getBorrowList($page, $limit, $Dwhere);
        }elseif($type==101){ //债权转让
            $data = $this->getDebtList($page, $limit); 
        }elseif($type==401){ //新手专享
            $Dwhere['b.borrow_type'] = BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID;
            $Dwhere['b.is_xinshou'] = 1;
            $data = $this->getBorrowList($page, $limit, $Dwhere);
        }elseif($type==501){ //推荐标
            $Dwhere['b.borrow_type'] = BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID;
            $Dwhere['b.borrow_status'] = 2;
            $Dwhere['b.is_tuijian'] = 1;
            $data = $this->getBorrowList($page, $limit, $Dwhere);
        }elseif($type==601){ //体验标
            $Dwhere['b.borrow_type'] = BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID;
            $Dwhere['b.borrow_status'] = 2;
            $Dwhere['b.is_taste'] = 1;
            $data = $this->getBorrowList($page, $limit, $Dwhere);
        }

//var_dump($data);die;
		 if(empty($data)){
             ajaxmsg("暂时没有记录",0);
		  }

        ajaxmsg($data, 1);
    }
    /**
    * 获取债权转让列表（带分页）
    *                     
    * @param mixed $page
    * @param mixed $limit
    * @return float
    */
    private function getDebtList($page=1, $limit=5)
    {
        $_GET['p'] = intval($page);     
        $parm['pagesize'] = $limit;
        $map['d.status'] = array('in', '2,4');
        import("ORG.Util.Page");
        $count = M('debt d')->where($map)->count('id');
        $totalPage = ceil($count/$limit);
        $p = new Page($count, $limit);
        $Lsql = "{$p->firstRow},{$p->listRows}";
        $pre = C('DB_PREFIX');

        $re_debt = M("debt d")
                    ->field("d.id, d.status, d.money as dq_money,d.assigned, d.money as borrow_money, d.invest_id, i.investor_uid as uid,b.borrow_interest_rate,d.interest_rate, b.borrow_name, b.borrow_duration, b.duration_unit,m.user_name,b.id as borrows_id, b.is_tuijian as suggest, d.addtime, d.period, d.total_period")
                    ->join("{$pre}borrow_investor i ON d.invest_id = i.id")
                    ->join("{$pre}borrow_info b ON i.borrow_id = b.id")
                    ->join("{$pre}members m ON i.investor_uid = m.id")
                    ->where($map)
                    ->limit($Lsql)
                    ->order("d.status asc, d.id desc")
                    ->select();
        foreach ($re_debt as $k => $v){
            $re_debt[$k]['type'] = 101; 
            $re_debt[$k]['suggest']=intval($v['suggest']); 
           // $re_debt[$k]['imgpath'] = get_avatar(intval($v['uid']));
            $re_debt[$k]['valid'] = $v['status'] == 2 ? 1 : 0; 
            $re_debt[$k]['addtime'] = date("Y-m-d", $v['addtime']);
			$re_debt[$k]['progress'] = intval($v['assigned'] / $v['borrow_money'] * 100);
            $re_debt[$k]['period'] = $v['period'].'期/'.$v['total_period'].'期';
			$re_debt[$k]['borrow_interest_rate']=$v['interest_rate'];   //2015-01-19  年化利率
			$re_debt[$k]['transfer']=$v['borrow_money'];   //2015-01-19  转让金额
			$re_debt[$k]['borrow_duration']=TborrowModel::get_remain_transfer_days($v['borrows_id'], 1).'天';   //剩余天数
        }
        if(is_array($re_debt)){
            $data['list'] = $re_debt;
            $data['totalPage'] = $totalPage;
            $data['nowPage'] =  $page;
        }else{
            $data = null;
        }
        return $data;
    }
    /**
    * 获取个人标（带分页）
    * 
    * @param mixed $page  //当前页数
    * @param mixed $limit  // 每页条数
    * @return array
    */
    private function getBorrowList($page=1, $limit=5, $where = false)
    {
		$Bconfig = require C("APP_ROOT")."Conf/borrow_config.php";
        $_GET['p'] = intval($page);     
        $parm['pagesize'] = $limit;
        $map['b.borrow_status'] = array("in", '2,4,6,7');
        if( !empty($where) ) {
            $map = array_merge($map, $where);
        }
        $parm['map'] = $map;
        $parm['orderby'] = "b.borrow_status ASC, b.id DESC";
        $list = getBorrowList($parm);
        $_list = null;
        foreach($list['list'] as $k => $v){
            $_list[$k]['uid'] = intval($v['uid']);
            $_list[$k]['type'] = $v['borrow_type'];
            $_list[$k]['id'] = intval($v['id']);
            $_list[$k]['borrow_name'] = text($v['borrow_name']);
            $_list[$k]['borrow_interest_rate'] = $v['borrow_interest_rate'];
            $_list[$k]['repayment_type'] = intval($v['repayment_type']);
            $_list[$k]['borrow_duration'] = $v['borrow_duration'].$v['duration_unit'];
            $_list[$k]['borrow_money'] = $v['borrow_money'];
            $_list[$k]['progress'] = $v['progress'];
            $_list[$k]['credits'] = $v['credits'];
			$_list[$k]['huankuan_type'] = $Bconfig['REPAYMENT_TYPE'][$v['repayment_type']];
            //$_list[$k]['user_name'] = $v['user_name'];
            //$_list[$k]['imgpath'] = get_avatar(intval($v['uid']));//标图像 
            //$_list[$k]['per_transfer'] = $v['borrow_min'];//最小投标金额
            $_list[$k]['is_tuijian'] = $v['is_tuijian'];
			$_list[$k]['is_xinshou'] = $v['is_xinshou'];
			$_list[$k]['is_taste'] = $v['is_taste'];
			$_list[$k]['borrow_status'] = $v['borrow_status'];
           // $_list[$k]['suo'] = (empty($v['password']) === true) ? 0 : 1;//是否定向标
            //if(intval($v['reward_type']) === 1){ $_list[$k]['reward'] = $v['reward_num']."%"; }
            //else if(intval($v['reward_type']) === 2){ $_list[$k]['reward'] = $v['reward_num']."元"; }
            //else{ $_list[$k]['reward'] = "0"; }//投标奖励
            $_list[$k]['addtime'] = date('Y-m-d', $v['add_time']);
        }
        $count = M("borrow_info b")->where($map)->count("b.id");
        $totalPage = ceil($count/$limit);    
        if(is_array($_list)){
            $data['list'] = $_list;
            $data['totalPage'] = $totalPage;
            $data['nowPage'] =  $page;
        }else{
            $data = null;
        }
        return $data;
    }

    //普通标详细信息
    public function detail(){
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        if (!is_array($arr)||empty($arr)||empty($arr['id'])) {
		     AppCommonAction::ajax_encrypt("查询错误！",0);
        }
        $pre = C('DB_PREFIX');
        $id = intval($arr['id']);
        $Bconfig = require C("APP_ROOT")."Conf/borrow_config.php";

        $borrowinfo = M("borrow_info bi")->field('bi.id as bid,bi.*,ac.title,ac.id')->join('lzh_article ac on ac.id= bi.danbao')->where('bi.id='.$id)->find();

        if(!is_array($borrowinfo) || ($borrowinfo['borrow_status']==0 && $this->uid!=$borrowinfo['borrow_uid']) ){
            AppCommonAction::ajax_encrypt("数据有误！",0);
        } 

        
        $borrowinfo['need'] = $borrowinfo['borrow_money'] - $borrowinfo['has_borrow'];
        
        $borrowinfo['progress'] = getFloatValue($borrowinfo['has_borrow']/$borrowinfo['borrow_money']*100,2);
        
        //$list['id'] = $id;
        //$list['type'] = $borrowinfo['borrow_type'];
        $list['borrow_name'] = $borrowinfo['borrow_name'];
        $list['borrow_money'] = $borrowinfo['borrow_money'];
        $list['borrow_interest_rate'] = $borrowinfo['borrow_interest_rate'];
        
        
        if($borrowinfo['duration_unit']==BorrowModel::BID_CONFIG_DURATION_UNIT_DAY){
            $list['borrow_duration'] = $borrowinfo['borrow_duration']."天";
        }else{
            $list['borrow_duration'] = $borrowinfo['borrow_duration']."个月";
        }
		$gloconf = get_bconf_setting();
        $list['huankuan_type'] = $Bconfig['REPAYMENT_TYPE'][$borrowinfo['repayment_type']];
        $list['borrow_use'] = $gloconf['BORROW_USE'][$borrowinfo['borrow_use']];//借款用途
        $list['borrow_min'] = $borrowinfo['borrow_min'];
        $list['borrow_max'] = $borrowinfo['borrow_max']=="0"? "无限制":$borrowinfo['borrow_max'];//最大借款金额
        $list['progress'] = $borrowinfo['progress'];
        $list['need'] = $borrowinfo['need'];
		$list['borrow_status'] = $borrowinfo['borrow_status'];
		if ($borrowinfo['is_xinshou']==1) {
            $binvest = BorrowInvestorModel::get_is_novice($this->uid);
            if ($binvest==false){
                $list['borrow_status']=100;
            }
        }
       /*
	   $borrowinfo['biao'] = $borrowinfo['borrow_times'];
	   $borrowinfo['lefttime'] =$borrowinfo['collect_time'] - time();
	   $list['repayment_type'] = $borrowinfo['repayment_type'];
	   if($borrowinfo['progress'] >= 100 ){
            $list['lefttime'] ="已结束";
        }elseif ($borrowinfo['lefttime'] > 0){
            $left_tian = floor($borrowinfo['lefttime']/ (60 * 60 * 24));
            $left_hour = floor(($borrowinfo['lefttime'] - $left_tian * 24 * 60 * 60)/3600);
            $left_minute = floor(($borrowinfo['lefttime'] - $left_tian * 24 * 60 * 60 - $left_hour * 60 * 60)/60);
            $left_second = floor($borrowinfo['lefttime'] - $left_tian * 24 * 60 * 60 - $left_hour * 60 * 60 - $left_minute *60);
            $list['lefttime'] = $left_tian.",".$left_hour.",".$left_minute.",".$left_second;
        }else {
            $list['lefttime'] ="已结束";
        }*/

        /*$list['weburl']=C("WEB_URL");
        $contentinfo=stripslashes($borrowinfo['borrow_info']);
        //先取图片出来，再去标签
        $imgarr=$this->replacePicUrl1 ($contentinfo, $list['weburl']);
        $list['borrow_info'] = strip_tags($imgarr['content']);
		$list['arrimg_path']=$imgarr['img'];*/

        $list['invest_num'] = $borrowinfo['borrow_times'];
        
        $minfo = M("members")->where("id={$borrowinfo['borrow_uid']}")->find();
        /*$list['user_name'] = $minfo['user_name'];//借款者姓名 
        $list['imgpath'] = $list['weburl'].get_avatar($borrowinfo['borrow_uid']);//TODO:缩略图规则要更改 借款者头像 
		*/
        $list['addtime'] = date("Y-m-d",$borrowinfo['add_time']);
        if($borrowinfo['reward_type']==1){
            $list['reward'] = $borrowinfo['reward_num']."%";
        }elseif($borrowinfo['reward_type']==2){
            $list['reward'] = $borrowinfo['reward_num'];
        }else{
            $list['reward']="无";
        }
      
        $list['purchased'] = intval(M("borrow_investor")->where("borrow_id = {$id}")->group("investor_uid")->count());//`mxl:mobile`
        AppCommonAction::ajax_encrypt($list,1);
        
    }

   //给图片添加域名给

  function replacePicUrl1($content = null, $strUrl = null) {
	  $arr=array();
	if ($strUrl) {
		//提取图片路径的src的正则表达式 并把结果存入$matches中  
    	preg_match_all("/<img(.*)src=\"([^\"]+)\"[^>]+>/isU",$content,$matches);
    	$img = "";  
        if(!empty($matches)) {  
        //注意，上面的正则表达式说明src的值是放在数组的第三个中  
        $img = $matches[2];  
        }else {  
           $img = "";  
        }
	      if (!empty($img)) {  
                $patterns= array();  
                $replacements = array();  
                foreach($img as $imgItem){  
	                $final_imgUrl = $strUrl.$imgItem;  
	                $replacements[] = $final_imgUrl;  
	                $img_new = "/".preg_replace("/\//i","\/",$imgItem)."/";  
	                $patterns[] = $img_new;  
                }  
  
                //让数组按照key来排序  
                ksort($patterns);  
                ksort($replacements);  
  
                //替换内容  
                $vote_content = preg_replace($patterns, $replacements, $content);
		        $arr['content']=$vote_content;
				$arr['img']= $replacements;
				return $arr;
		}else {
			  
			  $arr['content']=$content;
			  $arr['img']="";
			  return $arr;
			
		}           		
	} else {
		       $arr['content']=$content;
			  $arr['img']="";
			  return $arr;
	}
}


//end

    //投标记录
    public function investlog(){
        $jsoncode = file_get_contents("php://input");
       
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        $limit = intval($arr['limit'])? intval($arr['limit']): 5;
        $page = intval($arr['page'])? intval($arr['page']) :1;
		$type = intval($arr['type']);
		if($type==101){
			$data = $this->debt_investRecord($arr['id'], $page, $limit);   
		}else{
			$data = $this->investRecord($arr['id'], $page, $limit); 
		}
		if(empty($data)){
		    AppCommonAction::ajax_encrypt("暂无投标记录！",0);
		}else{
		    AppCommonAction::ajax_encrypt($data,1);
		}
    }
    /**
    * 普通标投资记录
    * 
    */
    private function investRecord($borrow_id, $page, $limit)
    {
        $pre = C('DB_PREFIX');
        $list = '';
        $_GET['p'] = intval($page);     
        $borrow_id= intval($borrow_id);
        import("ORG.Util.Page");    
        
            $fieldx = "bi.investor_capital,bi.add_time,m.user_name,bi.is_auto"; 
            $count = M('borrow_investor')->where("borrow_id={$borrow_id}")->count('id');
            $totalPage = ceil($count/$limit);
            $p = new Page($count, $limit);
            $Lsql = "{$p->firstRow},{$p->listRows}";
            $investinfo = M("borrow_investor bi")
                ->field($fieldx)
                ->join("{$pre}members m ON bi.investor_uid = m.id")
                ->where("bi.borrow_id={$borrow_id}")
                ->order("bi.id DESC")
                ->limit($Lsql)
                ->select();
        
        foreach($investinfo as $key=>$v){
            $list[$key]['user_name'] = hidecard($v['user_name'],5);
            $list[$key]['investor_capital'] = $v['investor_capital'];
            $list[$key]['add_time'] = date("Y-m-d H:i:s",$v['add_time']);
			$list[$key]['source'] = 2;
        }
        if($list){
            $row=array();
            $row['list'] = $list;
            $row['totalPage'] = $totalPage;
            $row['nowPage'] =  $page;
        }else{
            $row = null;
        }
        return $row;     
    }
	/**
    * 债权投资记录
    * 
    */
    private function debt_investRecord($borrow_id, $page, $limit)
    {
        $pre = C('DB_PREFIX');
        $list = '';
        $_GET['p'] = intval($page);     
        $borrow_id= intval($borrow_id);
        import("ORG.Util.Page");    
        
            $fieldx = "bi.investor_capital,bi.add_time,m.user_name,bi.is_auto"; 
            $count = M('borrow_investor')->where("parent_invest_id={$borrow_id}")->count('id');
            $totalPage = ceil($count/$limit);
            $p = new Page($count, $limit);
            $Lsql = "{$p->firstRow},{$p->listRows}";
            $investinfo = M("borrow_investor bi")
                ->field($fieldx)
                ->join("{$pre}members m ON bi.investor_uid = m.id")
                ->where("bi.parent_invest_id={$borrow_id}")
                ->order("bi.id DESC")
                ->limit($Lsql)
                ->select();
        
        foreach($investinfo as $key=>$v){
            $list[$key]['user_name'] = hidecard($v['user_name'],5);
            $list[$key]['investor_capital'] = $v['investor_capital'];
            $list[$key]['add_time'] = date("Y-m-d H:i:s",$v['add_time']);
			$list[$key]['source'] = 2;
        }
        if($list){
            $row=array();
            $row['list'] = $list;
            $row['totalPage'] = $totalPage;
            $row['nowPage'] =  $page;
        }else{
            $row = null;
        }
        return $row;     
    }
    
    
    //`mxl:mobile`
    public function ajax_debt(){
        $jsoncode = file_get_contents("php://input");
        if(!$this->uid){ 
            AppCommonAction::ajax_encrypt("请先登录！",0);
        }
        $arr = array();
        $arr = json_decode($jsoncode, true); 
        $arr = AppCommonAction::get_decrypt_json($arr);
        if (intval($arr['uid']) !== intval($this->uid)){ 
            AppCommonAction::ajax_encrypt("查询错误！",0);
        }

        $debt_id = intval($arr['id']);
        $debt = M("invest_detb")->field("transfer_price as price, money as dq_money")->where("id = {$debt_id}")->find();
        $buy_user = M("member_money")->field("account_money, back_money")->where(" uid = {$this->uid}")->find();
        $debt['account_money'] = $buy_user['account_money'] + $buy_user['back_money'];
        $pin = M("members")->getFieldById($this->uid, "pin_pass");
        $debt['has_pin'] = (empty($pin) === false);
        //$debt['id'] = $debt_id;
        AppCommonAction::ajax_encrypt($debt,1);
    }
    
    //`mxl:mobile`
    public function buy_debt(){
        $jsoncode = file_get_contents("php://input");
        if(!$this->uid){ 
            AppCommonAction::ajax_encrypt('请先登录！',0);
        }
        $arr = array();
        $arr = json_decode($jsoncode, true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        if (intval($arr['uid']) !== intval($this->uid)){ 
            AppCommonAction::ajax_encrypt('查询错误！',0);
        }
        $debt_id = intval($arr['id']);
        $invest_id = M("invest_detb")->getFieldById($debt_id, "invest_id");
        if (isset($invest_id) === false || 1 > $invest_id){ 
            AppCommonAction::ajax_encrypt('查找债权转让订单失败！',0);
        }
        $Debt = new DebtBehavior($this->uid);
        $info = $Debt->buy(text($arr['pin']), $invest_id);
        if ($info == "购买成功"){ 
            AppCommonAction::ajax_encrypt($info,1);
        }
        AppCommonAction::ajax_encrypt($info,0);
    }
    
    public function ajax_invest(){
       
        $jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
       
        if (!$this->uid || intval($arr['uid'])!=$this->uid){
            AppCommonAction::ajax_encrypt('登录有误，请重新登录！',0);
        }
        if (!is_array($arr)||empty($arr)||empty($arr['id'])) {
            AppCommonAction::ajax_encrypt('查询错误！',0);
        }

        $pre = C('DB_PREFIX');
        $id=intval($arr['id']); 
        $Bconfig = require C("APP_ROOT")."Conf/borrow_config.php";
        $field = "id,borrow_uid,borrow_money,borrow_status,borrow_type,has_borrow,has_vouch,borrow_interest_rate,borrow_duration,repayment_type,collect_time,borrow_min,borrow_max,password,borrow_use,money_collect, borrow_name, add_time";
        $vo = M('borrow_info')
            ->field($field)
            ->find($id);
        if($this->uid == $vo['borrow_uid']){
            AppCommonAction::ajax_encrypt('不能去投自己的标！',0);
        }
        if($vo['borrow_status'] <> 2) {
            AppCommonAction::ajax_encrypt('只能投正在借款中的标！',0);
        }

        $vo['need'] = $vo['borrow_money'] - $vo['has_borrow'];
        if($vo['need']<0){
            AppCommonAction::ajax_encrypt('投标金额不能超出借款剩余金额！',0);
        }
        $vo['lefttime'] =$vo['collect_time'] - time();
        $vo['progress'] = getFloatValue($vo['has_borrow']/$vo['borrow_money']*100,4);//ceil($vo['has_borrow']/$vo['borrow_money']*100);
        $vo['uname'] = M("members")->getFieldById($vo['borrow_uid'],'user_name');

        $vm = getMinfo($this->uid,'m.pin_pass,mm.account_money,mm.back_money,mm.money_collect');
        $amoney = $vm['account_money']+$vm['back_money'];
        
        ////////////////////////////////////待收金额限制 2013-08-26  fan///////////////////
        if($vo['money_collect']>0 && $vm['money_collect']<$vo['money_collect']){
            AppCommonAction::ajax_encrypt('此标设置有投标待收金额限制，您账户里必须有足够的待收才能投此标！',0);
        }
        ////////////////////////////////////待收金额限制 2013-08-26  fan///////////////////
        
      /* by zzx 删除自动添加可投镖 金额 
		if($amoney>=floatval($vo['borrow_max']) && floatval($vo['borrow_max'])>0){
            $toubiao = intval($vo['borrow_max']);
        }else if($amoney>=floatval($vo['need'])){
            $toubiao = intval($vo['need']);
        }else{
            $toubiao = floor($amoney);
        }
        $vo['toubiao'] =$toubiao;*/

        ////////////////////投标时自动填写可投标金额在投标文本框 2013-07-03 fan////////////////////////
        $pin_pass = $vm['pin_pass'];
		if(empty($pin_pass)){
			$msg['is_jumpmsg'] = '请设置支付密码再投标';
			AppCommonAction::ajax_encrypt($msg,1006);
		}
        
       /* by zzx 删除没用字段 
		$data['type'] = $arr['type'];
        $data['id'] = $id;  上传数据无需返回 
        $data['toubiao'] = $vo['toubiao'];*/
		

		
		//优惠券列表
        $expand_where = " uid=".$this->uid." and status=1 and expired_time > ".time();
        $expand_list = M('expand_money')
                ->field('id, money, invest_money, expired_time, type, is_taste')
                ->where($expand_where)
                ->limit('3')
                ->order("money desc")
                ->select();
		$expand_list = ExpandMoneyModel::get_coupon_type_format($expand_list);
		$_list = array();
            foreach($expand_list as $k=>$v){
				$_list[$k]['id'] = $v['id'];   //优惠卷id
				$_list[$k]['money'] = $v['money'];   //优惠卷金额
				$_list[$k]['invest_money'] = $v['invest_money'];  // 每多少金额
				$_list[$k]['expired_time'] = date('Y-m-d',$v['expired_time']);  //过期时间
				$_list[$k]['exp_type'] = $exp_type[$v['type']];  ///来源
				$_list[$k]['coupon_type'] = $v['coupon_type'];  ///卷类型
				$_list[$k]['desc'] = $v['coupon_type'].$v['money'].'元,满'.$v['invest_money'].'元抵'.$v['money'].'元,'.date('Y-m-d',$v['expired_time']).'过期';  //描述
				if($v['status']==1 and $v['expired_time']>time()){
					$_list[$k]['status'] = 0;  ///未使用的
				}elseif($v['status']==4){
					$_list[$k]['status'] = 1;  ///已使用
				}elseif($v['status']==1 and $v['expired_time']<time()){
					$_list[$k]['status'] = 2;  ///已过期
				}
				$_list[$k]['type'] = $v['type'];//提示信息
                
            }
		if($_list!=false)
        $data['expand_money_list'] = $_list;
        $data['borrow_min'] = $vo['borrow_min'];
        $data['borrow_max'] = $vo['borrow_max']=="0"?"无限制":"{$vo['borrow_max']}";
       //zzx $data['need'] = $vo['need'];
        $data['password'] = empty($vo['password'])?0:1;;
        $data['account_money'] = $amoney;
       //zzx $data['borrow_name'] = $vo['borrow_name']; 
       //zzx $data['addtime'] = date("Y-m-d", $vo['add_time']);
        AppCommonAction::ajax_encrypt($data,1);
    }
    

    public function investcheck(){
        $jsoncode = file_get_contents("php://input"); 
        if(!$this->uid) {
            AppCommonAction::ajax_encrypt('请先登录！',0);
        }
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        if (!is_array($arr)||empty($arr)||empty($arr['borrow_id'])) {
            AppCommonAction::ajax_encrypt('查询错误！',0);
        }
        if (intval($arr['uid'])!=$this->uid){
            AppCommonAction::ajax_encrypt('查询错误！',0);
        }
        $pre = C('DB_PREFIX');
        $_pin = $arr['pin'];
        $borrow_id = intval($arr['borrow_id']);
        $money = intval($arr['money']);

        $pin = md5($_pin);
        $borrow_pass = $arr['borrow_pass'];
        $vm = getMinfo($this->uid,'m.pin_pass,mm.account_money,mm.back_money,mm.money_collect');
        $amoney = $vm['account_money']+$vm['back_money'];
        $uname = session('u_user_name');
        $pin_pass = $vm['pin_pass'];
        $amoney = floatval($amoney);
        
        $binfo = M("borrow_info")->field('is_xinshou,borrow_money,has_borrow,has_vouch,borrow_max,borrow_min,borrow_type,password,money_collect')->find($borrow_id);
		if ($binfo['is_xinshou']==1) {
            $binvest = BorrowInvestorModel::get_is_novice($this->uid);
            if ($binvest==false){
                ajaxmsg("当前标为新手专享标，只有新手才可以投", 0);
            }
        }
		if ($money < $binfo['borrow_min']) {
            AppCommonAction::ajax_encrypt('小于起投金额，请重新输入!',0);
        }
        if ($money > ($binfo['borrow_money'] - $binfo['has_borrow'])) {
            AppCommonAction::ajax_encrypt('超出可投金额，请重新输入!',0);
        }
        if (($money % $binfo['borrow_min']) > 0) {
            AppCommonAction::ajax_encrypt('必须是起投金额的整数倍!',0);
        }
        if ($money > $binfo['borrow_money']) {
            AppCommonAction::ajax_encrypt('超出限投金额，请重新输入!',0);
        }
        //if($binfo['has_vouch']<$binfo['borrow_money'] && $binfo['borrow_type'] == 2) ajaxmsg("此标担保还未完成，您可以担保此标或者等担保完成再投标",3);
        if(!empty($binfo['password'])){
            if(empty($borrow_pass)){
                AppCommonAction::ajax_encrypt('此标是定向标，必须验证投标密码！',3);
            }
            else if($binfo['password']<>md5($borrow_pass)) {
                AppCommonAction::ajax_encrypt('投标密码不正确！',3);
            }
        }
		
		if($money%$binfo['borrow_min'] != 0){
		    AppCommonAction::ajax_encrypt('投资金额为起投金额的整数倍！',3);
        }
		
        ////////////////////////////////////待收金额限制 2013-08-26  fan///////////////////
        if($binfo['money_collect']>0){
            if($vm['money_collect']<$binfo['money_collect']) {
                AppCommonAction::ajax_encrypt('此标设置有投标待收金额限制，您账户里必须有足够的待收才能投此标！',3);
            }
        }
        ////////////////////////////////////待收金额限制 2013-08-26  fan///////////////////
        //投标总数检测
        $capital = M('borrow_investor')->where("borrow_id={$borrow_id} AND investor_uid={$this->uid}")->sum('investor_capital');
        if(($capital+$money)>$binfo['borrow_max']&&$binfo['borrow_max']>0){
            $xtee = $binfo['borrow_max'] - $capital;
            AppCommonAction::ajax_encrypt("您已投标{$capital}元，此投上限为{$binfo['borrow_max']}元，你最多只能再投{$xtee}元！",3);
        }
        
        $need = $binfo['borrow_money'] - $binfo['has_borrow'];
        $caninvest = $need - $binfo['borrow_min'];
        if( $money>$caninvest && ($need-$money)<>0 ){
            $msg = "尊敬的{$uname}，此标还差{$need}元满标,如果您投标{$money}元，将导致最后一次投标最多只能投".($need-$money)."元，小于最小投标金额{$binfo['borrow_min']}元，所以您本次可以选择<font color='#FF0000'>满标</font>或者投标金额必须<font color='#FF0000'>小于等于{$caninvest}元</font>";
            if($caninvest<$binfo['borrow_min']) $msg['message'] = "尊敬的{$uname}，此标还差{$need}元满标,如果您投标{$money}元，将导致最后一次投标最多只能投".($need-$money)."元，小于最小投标金额{$binfo['borrow_min']}元，所以您本次可以选择<font color='#FF0000'>满标</font>即投标金额必须<font color='#FF0000'>等于{$need}元</font>";
            AppCommonAction::ajax_encrypt($msg,3);
        }
        
        if(($need-$money)<0 ){
             $msg['message']="尊敬的{$uname}，此标还差{$need}元满标,您最多只能再投{$need}元";
             AppCommonAction::ajax_encrypt($msg,3);
        }
        
        if($pin<>$pin_pass) {
            AppCommonAction::ajax_encrypt('支付密码错误，请重试!',0);
        }
        if($money>$amoney){
            $msg['is_jumpmsg'] = "尊敬的{$uname}，您准备投标{$money}元，但您的账户可用余额为{$amoney}元，请先去充值!";
            AppCommonAction::ajax_encrypt($msg,1008);
        }else{
            $msg = "尊敬的{$uname}，您的账户可用余额为{$amoney}元，您确认投标{$money}元吗？";
            //$_msg['session_expired']=$session_expired;//$msg['session_expired']=$session_expired;//`mxl:mobile`
            //zzx $_msg['type'] = 1;
            //zzx $_msg['id'] = $borrow_id;
            $_msg['message'] = $msg;
            

          /*  $expand_expired_list = M('expand_money') //取三个按最大金额的，另取三个按过期时间的，前台通过TAB切换
            ->field('id,money, invest_money, expired_time, type, is_taste')
                ->where($expand_where)
                ->limit('5')
                ->order("expired_time asc")
                ->select();
            $_msg['expand_expired_list'] =  ($expand_expired_list==false) ? '' : $expand_expired_list;*/

            AppCommonAction::ajax_encrypt($_msg,1);
        }
    }
    
    
    public function investmoney(){ 
        $jsoncode = file_get_contents("php://input"); 
        if(!$this->uid) {
            AppCommonAction::ajax_encrypt('请先登录！',0);
        }
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        if (intval($arr['uid'])!=$this->uid){
            $msg['message']="查询错误！";
            AppCommonAction::ajax_encrypt($msg,0);
        }
        if (!is_array($arr)||empty($arr)||empty($arr['pin'])||empty($arr['borrow_id'])||empty($arr['money'])) {
           $msg['message']="查询错误！";
           AppCommonAction::ajax_encrypt($msg,0);
        }

        $pre = C('DB_PREFIX');
        $_pin = $arr['pin'];
        $pin = md5($_pin);
        $borrow_id = intval($arr['borrow_id']);
        $money = intval($arr['money']);

        $coupon_ids = filter_array($arr['coupon_id']);
        $discount_money = 0;//折扣金额
        // 如果使用优惠券
        if( !empty($coupon_ids) ) {
            $coupon_items = ExpandMoneyModel::get_discount_money($coupon_ids, $money, $this->uid);
            if( $coupon_items === false ) {
                AppCommonAction::ajax_encrypt('非法请求！',0);
            }else{
                $discount_money = $coupon_items['discount_money'];
            }
        }

        $m = M("member_money")->field('account_money,back_money,money_collect')->find($this->uid);
        $amoney = $m['account_money']+$m['back_money'];
        $uname = session('u_user_name');
        $msg['message']="尊敬的{$uname}，您准备投标{$money}元，但您的账户可用余额为{$amoney}元，请先去充值再投标.";
        if($amoney<($money-$discount_money)) {
            AppCommonAction::ajax_encrypt($msg,3);
        }
        
        $vm = getMinfo($this->uid,'m.pin_pass,mm.account_money,mm.back_money,mm.money_collect');
        $pin_pass = $vm['pin_pass'];
            $msg['message']="支付密码错误，请重试";
        if($pin<>$pin_pass){
            AppCommonAction::ajax_encrypt($msg,2);
        }

        $binfo = M("borrow_info")->field('is_xinshou,borrow_money,borrow_max,has_borrow,has_vouch,borrow_type,borrow_min,money_collect')->find($borrow_id);
        
        ////////////////////////////////////待收金额限制 2013-08-26  fan///////////////////
        if($binfo['money_collect']>0){
            if($m['money_collect']<$binfo['money_collect']) {
                $msg['message']="此标设置有投标待收金额限制，您账户里必须有足够的待收才能投此标";
                AppCommonAction::ajax_encrypt($msg,3);
            }
        }
        ////////////////////////////////////待收金额限制 2013-08-26  fan///////////////////
        
        //投标总数检测
        $capital = M('borrow_investor')->where("borrow_id={$borrow_id} AND investor_uid={$this->uid}")->sum('investor_capital');
        if(($capital+$money)>$binfo['borrow_max']&&$binfo['borrow_max']>0){
            $xtee = $binfo['borrow_max'] - $capital;
            AppCommonAction::ajax_encrypt("您已投标{$capital}元，此投上限为{$binfo['borrow_max']}元，你最多只能再投{$xtee}元！",3);
        }
        //if($binfo['has_vouch']<$binfo['borrow_money'] && $binfo['borrow_type'] == 2) $this->error("此标担保还未完成，您可以担保此标或者等担保完成再投标");
        $need = $binfo['borrow_money'] - $binfo['has_borrow'];
        $caninvest = $need - $binfo['borrow_min'];
        if( $money>$caninvest && ($need-$money)<>0 ){
            $msg['message'] = "尊敬的{$uname}，此标还差{$need}元满标,如果您投标{$money}元，将导致最后一次投标最多只能投".($need-$money)."元，小于最小投标金额{$binfo['borrow_min']}元，所以您本次可以选择满标或者投标金额必须小于等于{$caninvest}元";
            if($caninvest<$binfo['borrow_min']) $msg['message'] = "尊敬的{$uname}，此标还差{$need}元满标,如果您投标{$money}元，将导致最后一次投标最多只能投".($need-$money)."元，小于最小投标金额{$binfo['borrow_min']}元，所以您本次可以选择满标即投标金额必须等于{$need}元";
            AppCommonAction::ajax_encrypt($msg,0);
        }
        if(($need-$money)<0 ){
            $msg['message']="尊敬的{$uname}，此标还差{$need}元满标,您最多只能再投{$need}元";
            AppCommonAction::ajax_encrypt($msg,0);
        }else{
            $done = investMoney($this->uid,$borrow_id,$money, 0, $coupon_ids);
        }
        if($done===true) {
            $actual_money = $money - $discount_money;
            $_msg['id'] = $borrow_id;
			$repay_detail = $this->quick($borrow_id,$money,$type);
			$investdata['source'] = 2;
			//M('borrow_investor')->where("borrow_id = {$borrow_id} and investor_uid = {$this->uid} and investor_capital = {$money}")->order('id desc')->find()->save($investdata);
            $_msg['message'] = "恭喜成功投标{$money}元,实际支付{$actual_money}元,预计总收益{$repay_detail['amount']}元。"; 
            AppCommonAction::ajax_encrypt($_msg,1);
            
        }else if($done){ 
            AppCommonAction::ajax_encrypt($done,0);
        }else{
            $msg['message']="对不起，投标失败，请重试!";
            AppCommonAction::ajax_encrypt($msg,0);
        }
    }
    
    public function tdetail(){
        $glo = $this->glo; 
        $jsoncode = file_get_contents("php://input"); 
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        if (!is_array($arr)|| empty($arr)|| empty($arr['id'])){
           $msg['message']="查询错误！";
           AppCommonAction::ajax_encrypt($msg,0);
        }

        $pre = C('DB_PREFIX');
        $id = intval($arr['id']);
        $Bconfig = require C("APP_ROOT")."Conf/borrow_config.php";

        $borrowinfo =  M("borrow_info b")
                    ->join("{$pre}borrow_detail d ON d.borrow_id=b.id")
                    ->field(true)
                    ->find($id);
        $list['progress'] = getFloatValue($borrowinfo['has_borrow']/$borrowinfo['borrow_money']*100,2);//标的进度
        $list['need'] = getfloatvalue(($borrowinfo['borrow_money'] - $borrowinfo['has_borrow']), 2 );//剩余可投资金额
       
       /* $list['id'] = $id;
        $list['type'] = $borrowinfo['borrow_type'];*/
        $list['borrow_name'] = $borrowinfo['borrow_name'];//标的名字	
        $list['borrow_interest_rate'] = $borrowinfo['borrow_interest_rate'];//年利率
        $list['borrow_money'] = $borrowinfo['borrow_money'];//标的金额
        $borrowinfo['need'] = $borrowinfo['borrow_money'] - $borrowinfo['has_borrow'];
        if($borrowinfo['duration_unit']==BorrowModel::BID_CONFIG_DURATION_UNIT_DAY){
            $list['borrow_duration'] = $borrowinfo['borrow_duration']."天";
        }else{
            $list['borrow_duration'] = $borrowinfo['borrow_duration']."个月";
        }

        $list['borrow_max'] = $borrowinfo['borrow_max']==0? '无限制':$borrowinfo['borrow_max'];//最大投资金额
        

        $list['reward'] = $borrowinfo['reward_vouch_rate']==''? '无':$borrowinfo['reward_vouch_rate']."%";//网站奖励
        $list['borrow_min'] = $borrowinfo['borrow_min'];//最小投资金额
        $list['huankuan_type'] = BorrowModel::get_repay_type($borrowinfo['repayment_type']);//还款方式
        $minfo = M("members")->where("id={$borrowinfo['borrow_uid']}")->find();
        
        $list['addtime'] = date("Y-m-d",$borrowinfo['add_time']);//发布时间
        $list['invest_num'] = $borrowinfo['borrow_times']; //投资人数
       /* by zzx   待完善以下数据 */
       // $list['borrow_info'] = $borrowinfo['borrow_breif'];
		//$list['updata'] = unserialize($borrowinfo['borrow_img']);

	    /*$list['borrow_capital'] = $borrowinfo['borrow_capital'];
		//$list['borrow_benefit'] = $borrowinfo['borrow_benefit'];
        
        $list['borrow_risk'] = $borrowinfo['borrow_risk'];*/
		$list['borrow_use'] = $borrowinfo['borrow_use'];//借款用途
		$list['user_name'] = $minfo['user_name'];//投资人
		//zzx$weburl=C("WEB_URL");
        //zzx$list['imgpath'] = $weburl."/".get_avatar($borrowinfo['borrow_uid']); //头像
		$list['join_condition'] = "加入金额 {$borrowinfo['borrow_min']} 元起，且以 {$borrowinfo['borrow_min']} 元的倍数递增";//加入条件
		if($borrowinfo['danbao']!=0 ){
            $danbao = M('article')->field('id,title')->where("type_id=7 and id={$borrowinfo['danbao']}")->find();
            $list['danbao']=$danbao['title'];//担保机构
			$list['danbaoid'] = $danbao['id'];
		}else{
            $list['danbao']='暂无担保机构';//担保机构
		}
		
		$list['borrow_status'] = $borrowinfo['borrow_status'];
		if ($borrowinfo['is_xinshou']==1) {
            $binvest = BorrowInvestorModel::get_is_novice($this->uid);
            if ($binvest==false){
                $list['borrow_status']=100;
            }
        }
        //收益率
        $monthData['month_times'] = 12;
        $monthData['account'] = 100000;
        $monthData['year_apr'] = $borrowinfo['borrow_interest_rate'];
        $monthData['type'] = "all";
        $repay_detail = CompoundMonth($monthData);

        //$list['purchased'] = intval(M("transfer_borrow_investor")->where("borrow_id = {$id}")->group("investor_uid")->count());
        $list['borrow_benefit'] = '转入出借人在'.$glo['web_name'].'平台的账户，b.利息复投。'.$borrowinfo['borrow_interest_rate'].'% - '.$repay_detail['shouyi'].'%年化利率'; //收益率
        AppCommonAction::ajax_encrypt($list,1);
        
    }
    
    public function tajax_invest(){
        $jsoncode = file_get_contents("php://input");
        try{
            if(!$this->uid) {
                $list['message']="请先登录！";
                AppCommonAction::ajax_encrypt($list,0);
            }
            $arr = array();
            $arr = json_decode($jsoncode,true);
            $arr = AppCommonAction::get_decrypt_json($arr);
            if (intval($arr['uid'])!=$this->uid){
                $list['message']="用户信息有误！";
                AppCommonAction::ajax_encrypt($list,0);
            }
            if (!is_array($arr) || empty($arr['borrow_id'])) {
                $list['message']="查询错误！";
                AppCommonAction::ajax_encrypt($list,0);
            }
            $id = intval( $arr['borrow_id'] );
            $field = "*";
            $vo = M("borrow_info")
                ->field($field)
                ->find($id);
            if ($this->uid == $vo['borrow_uid'])
            {
                $list['message']="不能息投自己的标";
                AppCommonAction::ajax_encrypt($list,0);
            }
            if ($vo['borrow_status'] != BorrowModel::BID_SINGLE_CONFIG_STATUS_VIEW_PASS)
            {
                $list['message']="只能投正在借款中的标";
                AppCommonAction::ajax_encrypt($list,0);
            }
            $vo['need'] = $vo['borrow_money'] - $vo['has_borrow'];
           /* by zzx  删除  没用字段
				$list['uname'] = M("members")->getFieldById($vo['borrow_uid'], "user_name"); //借款人用户名
			    $list['id'] = $id;
            $list['rate'] = $vo['borrow_interest_rate'];
		   */

            $vm = getMinfo($this->uid,'m.pin_pass,mm.account_money,mm.back_money,mm.money_collect');
            $amoney = $vm['account_money']+$vm['back_money'];
            $pin_pass = $vm['pin_pass'];
			if(empty( $pin_pass )){
				AppCommonAction::ajax_encrypt('请先设置支付密码！',1006);
			}
            

            //$invest_money_max = !$vo['borrow_max'] ? $vo['need'] : min($vo['borrow_max'], $vo['need']);
			$invest_money_max = !$vo['borrow_max'] ? '无限制' : $vo['borrow_max'];
            $list['account_money'] = $amoney;//可用余额
           /* by zzx 删除没用数据
			$duration_unit = BorrowModel::get_unit_format($vo['duration_unit']);
            $list['borrow_duration'] = $vo['borrow_duration'].$duration_unit;
            $list['need'] = $vo['need'];//剩余可投
            $list['borrow_name'] = $vo['borrow_name']; 
            $list['addtime'] = date("Y-m-d", $vo['add_time']);*/
            $list['borrow_min'] = $vo['borrow_min'];//最小投资金额
            $list['borrow_max'] = $invest_money_max;//最大投资金额
			//优惠券列表
            $expand_where = " uid=".$this->uid." and status=1 and expired_time > ".time();
            $expand_list = M('expand_money')
                ->field('id, money, invest_money, expired_time, type, is_taste')
                ->where($expand_where)
                ->limit('3')
                ->order("money desc")
                ->select();
			$expand_list = ExpandMoneyModel::get_coupon_type_format($expand_list);
			$_list = array();
            foreach($expand_list as $k=>$v){
				$_list[$k]['id'] = $v['id'];   //优惠卷id
				$_list[$k]['money'] = $v['money'];   //优惠卷金额
				$_list[$k]['invest_money'] = $v['invest_money'];  // 每多少金额
				$_list[$k]['expired_time'] = date('Y-m-d',$v['expired_time']);  //过期时间
				$_list[$k]['exp_type'] = $exp_type[$v['type']];  ///来源
				$_list[$k]['coupon_type'] = $v['coupon_type'];  ///卷类型
				$_list[$k]['desc'] = $v['coupon_type'].$v['money'].'元,满'.$v['invest_money'].'元抵'.$v['money'].'元,'.date('Y-m-d',$v['expired_time']).'过期';  //描述
				if($v['status']==1 and $v['expired_time']>time()){
					$_list[$k]['status'] = 0;  ///未使用的
				}elseif($v['status']==4){
					$_list[$k]['status'] = 1;  ///已使用
				}elseif($v['status']==1 and $v['expired_time']<time()){
					$_list[$k]['status'] = 2;  ///已过期
				}
				$_list[$k]['type'] = $v['type'];//提示信息
                
            }
			if($_list!=false)
            $list['expand_money_list'] = $_list;

            $expand_expired_list = M('expand_money') //取三个按最大金额的，另取三个按过期时间的，前台通过TAB切换
            ->field('id,money, invest_money, expired_time, type, is_taste')
                ->where($expand_where)
                ->limit('3')
                ->order("expired_time asc")
                ->select();
			if($expand_expired_list!=false)
            $list['expand_expired_list'] =  $expand_expired_list;
			AppCommonAction::ajax_encrypt($list,1);
           /*  by zzx 目的和散标投资返回 数据一致
			if($has_pin == 0){
                $list['message']="投标前请先设置支付密码！";
                AppCommonAction::ajax_encrypt($list,0);
            }else {
                $list['message']="正确！";
                AppCommonAction::ajax_encrypt($list,1);
            }*/
        }catch (Exception $e) {
            $ret = array(
                'status' => 0,
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            );
            AppCommonAction::ajax_encrypt($ret,0);
        }
    }
    
    public function tinvestcheck()
    {
        $jsoncode = file_get_contents("php://input"); 
        if(!$this->uid) {
            $msg['message'] = "请先登录";
            AppCommonAction::ajax_encrypt($msg,0);
        }
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        if (intval($arr['uid'])!=$this->uid){
             $msg['message'] = "用户信息有误！";
             AppCommonAction::ajax_encrypt($msg,0);
        }
        if (!is_array($arr) || !$arr['borrow_id'] || !$arr['pin'] || !$arr['num']) {
          $msg['message'] = "查询错误！";
          AppCommonAction::ajax_encrypt($msg,0);
        }

        $_pin = $arr['pin'];
        $_borrow_id = $arr['borrow_id'];
        $_tnum = $arr['num'];
    
        $pin = md5($_pin);
        $borrow_id = intval($_borrow_id);
        $tnum = intval($_tnum);
    
        $m = M("member_money")->field('account_money,back_money,money_collect')->find($this->uid);
        $amoney = $m['account_money']+$m['back_money'];
        $uname = session("u_user_name");
        $vm = getMinfo($this->uid,"m.pin_pass");
        $pin_pass = $vm['pin_pass'];
        $amoney = floatval($amoney);
        $binfo = M("borrow_info")->field( "*")->find($borrow_id);
		if ($binfo['is_xinshou']==1) {
            $binvest = BorrowInvestorModel::get_is_novice($this->uid);
            if ($binvest==false){
                ajaxmsg("当前标为新手专享标，只有新手才可以投", 0);
            }
        }
		if ($tnum < $binfo['borrow_min']) {
            AppCommonAction::ajax_encrypt('小于起投金额，请重新输入!',0);
        }
        if ($tnum > ($binfo['borrow_money'] - $binfo['has_borrow'])) {
            AppCommonAction::ajax_encrypt('超出可投金额，请重新输入!',0);
        }
        if (($tnum % $binfo['borrow_min']) > 0) {
            AppCommonAction::ajax_encrypt('必须是起投金额的整数倍!',0);
        }
        if ($tnum > $binfo['borrow_money']) {
            AppCommonAction::ajax_encrypt('超出限投金额，请重新输入!',0);
        }

        $max_num = $binfo['borrow_money'] - $binfo['has_borrow'];
        if($tnum<$binfo['borrow_min']){
                    $msg['message'] = "购买金额必须大于最小投资金额！";
                    AppCommonAction::ajax_encrypt($msg,3);
        }
        
        if ($max_num < $tnum)
        {
            $msg['message'] = "本标还能认购最大金额为".$max_num."元，请重新输入认购金额";
            AppCommonAction::ajax_encrypt($msg,3);
        }
        $money = $tnum;
        if ($pin != $pin_pass)
        {
            $msg['message'] = "支付密码错误，请重试";
            AppCommonAction::ajax_encrypt($msg,0);
        }
        if ($amoney < $money)
        {
            $msg['is_jumpmsg'] = "尊敬的{$uname}，您准备认购{$money}元，但您的账户可用余额为{$amoney}元，您要先去充值吗？";
            AppCommonAction::ajax_encrypt($msg,1008);
        }
        else
        {
            

            $msg = "尊敬的{$uname}，您的账户可用余额为{$amoney}元，您确认认购{$money}元吗？";
            $_msg['id'] = $borrow_id;
            $_msg['message'] = $msg;
            AppCommonAction::ajax_encrypt($_msg,1);
        }
    }
    
    public function tinvestmoney()
    {
        $jsoncode = file_get_contents("php://input");
        if(!$this->uid) {
            $msg['message']='请先登录！';
            AppCommonAction::ajax_encrypt($msg,0);
        }
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        if (intval($arr['uid'])!=$this->uid){
            $msg['message']='用户信息错误！';
            AppCommonAction::ajax_encrypt($msg,0);
        }
        if (!is_array($arr)||empty($arr)||empty($arr['borrow_id'])||empty($arr['pin'])||empty($arr['num'])) {
            $msg['message']='参数错误！';
            AppCommonAction::ajax_encrypt($msg,0);
        }

        $_pin = $arr['pin'];
        $_borrow_id = $arr['borrow_id'];
        $_tnum = $arr['num'];
        $borrow_id = intval($_borrow_id);
        $tnum = intval($_tnum);

		//如果是定投宝，查看其是按月还息还是利息复投模式
        $binfo = M("borrow_info")->field( "*")->find($borrow_id);
		if($binfo['borrow_type']== BorrowModel::BID_CONFIG_TYPE_FINANCIAL)
		{
	     	$_repayment_type=intval($arr['invest_type']);
           if(!in_array($_repayment_type, array(4,6))){
               $msg['message']='投资类型错误！';
               AppCommonAction::ajax_encrypt($msg,0);
		   }
        }else{
            $_repayment_type = $binfo['repayment_type'];
		}

        $m = M("member_money")->field('account_money,back_money,money_collect')->find($this->uid);
        $amoney = $m['account_money']+$m['back_money'];
        $uname = session("u_user_name");

        if($this->uid == $binfo['borrow_uid']){
            $msg['message']='不能去投自己的标！';
            AppCommonAction::ajax_encrypt($msg,0);
        }
    
        $month = $binfo['borrow_duration'];
        $max_num = $binfo['borrow_money'] - $binfo['has_money'];
        if($tnum < $binfo['borrow_min']){
            $msg['message']='购买金额必须大于最小投资金额！';
            AppCommonAction::ajax_encrypt($msg,3);
        }
        
        if($max_num < $tnum){
            $msg['message']="本标还能认购最大金额为".$max_num."元，请重新输入认购金额！";
            AppCommonAction::ajax_encrypt($msg,3);
        }

        $coupon_ids = filter_array($arr['coupon_id']);
        $discount_money = 0;//折扣金额
        // 如果使用优惠券
        if( !empty($coupon_ids) ) {
            $coupon_items = ExpandMoneyModel::get_discount_money($coupon_ids, $tnum, $this->uid);
            if( $coupon_items === false ) {
                AppCommonAction::ajax_encrypt('非法请求',0);
            }else{
                $discount_money = $coupon_items['discount_money'];
            }
        }

        $money = $tnum;
        if($amoney < ($money - $discount_money)){
              $msg['message']="尊敬的{$uname}，您准备认购{$money}元，但您的账户可用余额为{$amoney}元，请先去充值再认购".__APP__."/member/charge#fragment-1";
              AppCommonAction::ajax_encrypt($msg,2);
        }
        $vm = getMinfo($this->uid,"m.pin_pass,mm.invest_vouch_cuse,mm.money_collect");
        $pin_pass = $vm['pin_pass'];
        $pin = md5($_pin);
        if ($pin != $pin_pass){
             $msg['message']="支付密码错误，请重试";
             AppCommonAction::ajax_encrypt($msg,0);
        }
        $done = TinvestMoney($this->uid,$borrow_id,$tnum,$month,0,$_repayment_type,1, $coupon_ids);//投企业直投
        if($done === true){
            $actual_money = $money - $discount_money;
            $_msg['id'] = $borrow_id;
			$repay_detail = $this->quick($borrow_id,$money,intval($arr['invest_type']));
            $_msg['message'] = "恭喜成功认购,共计{$money}元,实际支付{$actual_money}元,预计总收益{$repay_detail['amount']}元。";
            AppCommonAction::ajax_encrypt($_msg,1);
        }else if($done){
            AppCommonAction::ajax_encrypt($done,3);
        }else{
            $msg['message']="对不起，认购失败，请重试!";
            AppCommonAction::ajax_encrypt($msg,3);
        }
    }
    
    public function getNews()
    {
        $jsoncode = file_get_contents("php://input");
        $arr = json_decode($jsoncode, true);
        $arr = AppCommonAction::get_decrypt_json($arr);
		$type =intval($arr['type'])? intval($arr['type']):1;
		if($type==1){
			$type = 2;//新闻
		}else if($type==2){
			$type = 9;//公告
		}
        $limit = intval($arr['limit'])? intval($arr['limit']): 5;
        $page = intval($arr['page'])? intval($arr['page']) :1;
        $data =$this->getArticleList($type, $page, $limit);
         if(empty($data)){
             AppCommonAction::ajax_encrypt("暂无数据，请稍后再试！",0);
		 }else{
		     AppCommonAction::ajax_encrypt($data,1);
		 }
    }
	/* by zzx  删除重复方法
    public function getArticle()
    {
        $jsoncode = file_get_contents("php://input");
        $arr = json_decode($jsoncode, true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        $limit = intval($arr['limit'])? intval($arr['limit']): 5;
        $page = intval($arr['page'])? intval($arr['page']) :1;
        $data =$this->getArticleList(9, $page, $limit);
    
	    if(empty($data)){
	        AppCommonAction::ajax_encrypt("暂无数据，请稍后再试！",0);		
		}else{
		    AppCommonAction::ajax_encrypt($data,1);
		} 
    }*/
    private function getArticleList($typeid=9, $page = 1, $limit=5)
    {
        $_list = '';
        $_GET['p'] = intval($page);     
        $type_id= intval($typeid);
        $Allid = M("article_category")->field("id")->where("parent_id = {$type_id}")->select();
        $newlist = array();
        array_push($newlist,$type_id);
      
        foreach ($Allid as $ka => $v) {
            array_push($newlist,$v["id"]);
        }
        $map['type_id']= array("in",$newlist);
       
        $Osql="sort_order desc,id DESC";//id DESC,
        $field="id,title,art_time";
        
        import("ORG.Util.Page");
        $count = M('article')->where($map)->count('id');
        $totalPage = ceil($count/$limit);
        $p = new Page($count, $limit);
        $Lsql = "{$p->firstRow},{$p->listRows}";
        $data = M('article')
                ->field($field)
                ->where($map)
                ->order($Osql)
                ->limit($Lsql)
                ->select();
        foreach($data as $key=>$v){
               $_list[$key]['id'] = $v['id'];
               $_list[$key]['title'] = $v['title'];
               $_list[$key]['art_time'] = date("Y-m-d H:i:s",$v['art_time']);
        }
        if($_list){
            $row=array();
            $row['list'] = $_list;
            $row['totalPage'] = $totalPage;
            $row['nowPage'] =  $page;
        }else{
            $row = null;
        }
        return $row;    
    }

    /*
     *#34 API 检测是否可以更新新版本
     *14-09-15 元
     *参考文档 服务器与客户端协议v20140912.docx
     *增加ios版本更新,ios还返回新版本号,安卓原逻辑不变
     */
    public function version(){ 
        $jsoncode = file_get_contents("php://input"); 
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr); 
       
        $datag = FS("Webconfig/baiduconfig");
        $apkversion = $datag['baidu']['apkVersion'];
        $appversion = $datag['baidu']['appVersion'];
        if($arr['iosversion']){ 
        //ios客户端
            if(is_array($arr)&& (!empty($arr)) && (!empty($arr['iosversion'])) && ((float)$arr['iosversion'])<((float)$appversion)){
                $content['path'] = $datag['baidu']['appPath'];
                $content['version'] = $apkversion;
                AppCommonAction::ajax_encrypt($content,0);
            }else{
                $content['message'] = "已是最新版";
                AppCommonAction::ajax_encrypt($content,1);
            }
        }else{
        //安卓客户端
            if(is_array($arr)&& (!empty($arr)) && (!empty($arr['version'])) && ((float)$arr['version'])<((float)$apkversion)){
    			$content['status'] = 0;
                $content['path'] = $datag['baidu']['apkPath'];
    			$content['version']=$apkversion;
    			AppCommonAction::ajax_encrypt($content,0);
            }else{
				$content['status'] = 1;
                $content['message'] = "已是最新版";
                AppCommonAction::ajax_encrypt($content,1);
            }
        }
    }
    /*
     *#35 API 意见反馈  
     *14-09-16 元
     *参考文档 服务器与客户端协议v20140912.docx
     */
     public function feedback(){
        $jsoncode = file_get_contents("php://input");
        $arr = json_decode($jsoncode, true);
         $arr = AppCommonAction::get_decrypt_json($arr);
        $uid = intval($arr['uid']);
        if(!$this->uid || $uid != $this->uid){
            AppCommonAction::ajax_encrypt("登录信息有误，请重新登录！",0);
        }
        
        $feedback['name'] = M('members')->where('id='.$uid)->getField('user_name');
        $feedback['msg'] = text($arr['message']);
        $feedback['system'] = $arr['system'];
        $feedback['ip'] = get_client_ip();
        $feedback['add_time'] = time();
    
        $newid = M('feedback')->add($feedback);
        if($newid){
            AppCommonAction::ajax_encrypt("您的信息已成功提交，感谢您的宝贵意见！",1);       
        }else{
            AppCommonAction::ajax_encrypt("对不起，信息提交失败！",0);
        }
     }
     
  
    /*
     *#39 API 活动信息
     *参考文档 服务器与客户端协议v20140916.docx
     */
   /* public function event_show(){

        $jsoncode = file_get_contents("php://input");
        
        $arr = array();
        $arr = json_decode($jsoncode,true);
        $arr = AppCommonAction::get_decrypt_json($arr);
        if(!is_array($arr)||empty($arr)||empty($arr['id'])) {
           $_content['message']="查询错误！";
           AppCommonAction::ajax_encrypt($_content,0);
        }
        $id = $arr["id"];
        $content=M('article')->find($id);
        $_content['id'] = $content['id'];
        $_content['title'] = $content['title'];
        $_content['art_time'] = date("Y-m-d H:i:s",$content['art_time']);
        $_content['art_content'] = $content['art_content'];
        AppCommonAction::ajax_encrypt($_content,1);
    }*/

	
		


public function getpassword(){   //发送手机验证码
       $jsoncode = file_get_contents("php://input"); 
       $arr = array();
       $arr = json_decode($jsoncode,true);
		$arr = AppCommonAction::get_decrypt_json($arr);
       $pre = C('DB_PREFIX');
        if(is_array($arr) && isset($arr['phone']) ){
          
		   $minfo = M("members m")->join("{$pre}members_status ms ON m.id = ms.uid")->where("m.user_phone = '{$arr['phone']}'")->field("m.id, m.user_name, m.user_phone, ms.phone_status, m.last_log_time")->find();

         if (empty($minfo['id']) === true){ 
             AppCommonAction::ajax_encrypt("找不到您所填写的用户，请检查用户名称是否存在错误！",0);
         }

         if (empty($minfo['user_phone']) === true || empty($minfo['phone_status']) === true){ 
             AppCommonAction::ajax_encrypt("该用户未绑定手机，不能使用短信方式找回密码！",0);
         }

        // if ($minfo['user_phone'] !== $arr['phone']){ ajaxmsg("输入的号码不是该用户绑定的手机，不能使用短信方式找回密码", 0); }

         $smsTxt = FS("Webconfig/smstxt");
         $smsTxt = de_xie($smsTxt);
         $phone = $arr['phone'];
         $code = rand_string_reg(6, 1, 2);
         $datag = get_global_setting();
         $is_manual = $datag['is_manual'];
         $res = sendsms($phone, str_replace(array("#UserName#", "#CODE#"), array($minfo['user_name'], $code), $smsTxt['verify_phone']));
          
		  if(!empty($res)){
		      session("temp_phone", $phone);
              session("username",$minfo['user_name']);
              $list['message']="发送成功！";
              AppCommonAction::ajax_encrypt($list,1);
		  }else{
		      $list['message']="发送失败！";
		      AppCommonAction::ajax_encrypt($list,0);
		  }	
		}else{
		   $m_list['message']="参数错误";
		   AppCommonAction::ajax_encrypt($m_list,0);
		}
  }
  


  public function repreatphone(){
	  
     $jsoncode = file_get_contents("php://input");
     $arr = array();
     $arr = json_decode($jsoncode,true);
      $arr = AppCommonAction::get_decrypt_json($arr);
     if(is_array($arr) && !empty($arr['password'])&&!empty($arr['code']) ){ 
         if (session('code_temp')!=text($arr['code'])) { 
             $m_list['message']="验证码不正确";
             AppCommonAction::ajax_encrypt($m_list,0);
         }
	 $pass=md5($arr['password']);
     $userphone= session("temp_phone");
     // ajaxmsg($userphone);
     $data['user_pass']=$pass;
     $rs=M("members")->where("user_phone='{$userphone}'")->save($data);

	 // echo  M("members")->getLastSql();
	    if(empty($rs)){
		   $list['message']='修改失败';
		   AppCommonAction::ajax_encrypt($list,0);
		}else{
		    $list['message']='修改成功';
		    AppCommonAction::ajax_encrypt($list,1);
		 }
	
	 }else{
	     $m_list['message']="参数错误";
	     AppCommonAction::ajax_encrypt($m_list,0);
	 }
  }
   
  /**  
    *添加banner图片
    *s
	*2015-02-24
  **/ 
  
  public function bnlist(){   //切换图片列表
     $list=M("app")->where("type=0")->order("ranges desc")->select();
	  if(empty($list)){
	    $msg['message']='暂时没有数据！';
	    AppCommonAction::ajax_encrypt($msg,0);
	  }else{
	     $_list=array();
  	     foreach($list as $k=>$v){
		 $_list[$k]['id'] = $v['id']; 
		 $_list[$k]['pic']=$v['pic']; 
	     } 
	  }
	  $data['list'] = $_list;
	  AppCommonAction::ajax_encrypt($data,1);
  } 
  /*
  public function bnedit(){   //图片详情
  		$jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
      $arr = AppCommonAction::get_decrypt_json($arr);
		//$arr['id'] = 13;
		$bnid = intval($arr['id']);
     $list=M("app")->where("type=0 and id={$bnid}")->order("ranges desc")->select();
	  if(empty($list)){
	    $msg['message']='暂时没有数据！';
	    AppCommonAction::ajax_encrypt($msg,0);
	  }else{ 
  	     foreach($list as $k=>$v){ 
			$data['title']=$v['title'];
			$data['add_time']=date("Y-m-d H:i",$v['add_time']);
			$lists['weburl']=C("WEB_URL");
			$contentinfo=stripslashes($v['content']);
			$imgarr=$this->replacePicUrl1($contentinfo, $lists['weburl']); 
			$data['arrimg_path'] = $imgarr['img'];
			$data['content']=$imgarr['content']; 
	     } 
	  }
	  AppCommonAction::ajax_encrypt($data,1);
  } */
  /**
     * 债权转让详情，代码重构 150423
     */
    public function debt_detail()
    {
		$jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
		$arr = AppCommonAction::get_decrypt_json($arr);
        $pre = C('DB_PREFIX');
        $id = intval($arr['id']);
        $debt = M('debt')->field('*')->where(array('id' => $id))->find(); // 撤销时需要把debt里的数据删除，否则会导致
        D("DebtBehavior");

        if (!empty($debt['invest_id'])) {

            $debt['need'] = $debt['money'] - $debt['assigned'];  //可投金额
            $debt_duration = get_global_setting('debt_duration');
            $debt['debt_et'] = date('Y-m-d', $debt['addtime'] + $debt_duration * 24 * 3600); // 截止时间
            $debt['progress'] = intval($debt['assigned'] / $debt['money'] * 100);
            $borrow_id = M('borrow_investor')->where(array('id' => $debt['invest_id']))->getField('borrow_id');
            if (!empty($borrow_id)) {
                //标的详情
                $borrow_info = M('borrow_info ')
                    ->field('id as borrow_id,borrow_uid,borrow_name,borrow_duration,duration_unit,borrow_money,borrow_min,borrow_max,borrow_interest_rate,borrow_type,repayment_type')
                    ->where(array('id' => $borrow_id))
                    ->find();
                $b_invest = M('borrow_investor')->field('invest_repayment_type')->where(array('borrow_id' => $borrow_id))->find();
                if (!empty($borrow_info)) {
                    $borrow_info['remain_duration'] = TborrowModel::get_remain_transfer_days($borrow_id, 1);
                    
                    if ($b_invest['invest_repayment_type']==1){
                        $borrow_info['repayment_type_name'] = "按月还息";
                    }else {
                        $borrow_info['repayment_type_name'] = BorrowModel::get_repay_type($borrow_info['repayment_type']);
                    }
                    
                    // 登录人信息
                    if (!empty($_SESSION['u_id'])) {
                        $vminfo = getMinfo($this->uid, 'mm.account_money,mm.back_money,mm.money_collect');
                        if (!empty($vminfo)) {
                            $vminfo['account'] = $vminfo['account_money'] + $vminfo['back_money'];
                        }
                    }

                    //转让人用户名
                    $sell_uname = M('members')->field('user_name')->where(array('id' => $debt['sell_uid']))->find();

                    
					$invest_num = M('borrow_investor')->where("parent_invest_id = {$debt['invest_id']}")->count();
                    //当前页面的url
                    $current_url = DOMAIN . $_SERVER['REQUEST_URI'];
					$data['progress'] = $debt['progress'];//投标进度
                    $data['invest_id'] = $debt['invest_id'];
					$data['invest_num'] = M('borrow_investor')->where("parent_invest_id={$debt['invest_id']}")->count('id');//债权投标人数
					$data['borrow_name'] = $borrow_info['borrow_name'];//转让名称
					$data['borrow_type'] = $borrow_info['borrow_type'];//原标类型
					$data['money'] = $debt['money'];//转让金额
					$data['interest_rate'] = $debt['interest_rate'].'%';//现年化收益率：
					$data['borrow_interest_rate'] = $borrow_info['borrow_interest_rate'].'%';//原年化收益率：
					$data['remain_duration'] = floatval($borrow_info['remain_duration']).'天';//剩余期限：
					$data['need'] = number_format($debt['need'],2);//剩余可投
					$data['repayment_type_name'] = $borrow_info['repayment_type_name'];//还款方式：
					$data['debt_et'] = $debt['debt_et'];//截止时间：
					$data['borrow_id'] = $borrow_info['borrow_id'];//查看原项目
					$data['qitou'] = '10元';//起投金额
					AppCommonAction::ajax_encrypt($data,1);
                }

            }
        } else {
            //TODO: 404页面
            AppCommonAction::ajax_encrypt('提交参数有误!',0);
        }
        
    }
	//债权转让投标页
	public function debt_ajax_invest()
    {
		$jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
		$arr = AppCommonAction::get_decrypt_json($arr);
        if (!$this->uid) {
            AppCommonAction::ajax_encrypt("请先登陆", 0);
        }
        $pre = C('DB_PREFIX');
        $id = intval($arr['id']);

        $debt = M('debt')->field("*")->where(array('id' => $id))->find();
        if ($this->uid == $debt['sell_uid']) AppCommonAction::ajax_encrypt("不能购买自己转让的债权", 0);
        if ($debt['status'] <> 2) AppCommonAction::ajax_encrypt("只能投正在转让中的债权", 0);

        $vm = getMinfo($this->uid, 'm.pin_pass,mm.account_money,mm.back_money,mm.money_collect');
		
        $pin_pass = $vm['pin_pass'];
		if(empty($pin_pass)){
			AppCommonAction::ajax_encrypt('请先设置支付密码!',1006);
		}
		$data['user_money'] = $vm['account_money'] + $vm['back_money']; //帐户余额
		//$data['invest_id'] = $debt['invest_id']; //帐户余额
        
        AppCommonAction::ajax_encrypt($data);
    }
	/**
     * 债权确认购买
     * 流程： 检测购买条件
     * 购买
     */
    public function debt_investmoney()
    {
		$jsoncode = file_get_contents("php://input");
        $arr = array();
        $arr = json_decode($jsoncode,true);
		$arr = AppCommonAction::get_decrypt_json($arr);
        $paypass = strval($arr['pin']); //支付密码
        $invest_id = intval($arr['id']);
        $money = floatval($arr['money']);
		$uid = intval($arr['uid']);
		$this->uid = $uid;
        D("DebtBehavior");
        $Debt = new DebtBehavior($this->uid);
        // 检测是否可以购买  密码是否正确，余额是否充足
        $result = $Debt->buy($paypass, $invest_id, $money);
        if ($result === '购买成功') {
            ajaxmsg('购买成功!',1);
        } else {
			ajaxmsg($result,0);
        }
        
    }



	
/**
 * @zzx 手机快速计算投标收益  
 */
 public function quickcountrate() {
	$jsoncode = file_get_contents("php://input");
    $arr = array();
    $arr = json_decode($jsoncode,true);
	$arr = AppCommonAction::get_decrypt_json($arr);
	
	 $borrow_id = intval($arr['id']);
	 $amount = floatval($arr['amount']);
	 $type = intval($arr['type']);
	$repay_detail = $this->quick($borrow_id,$amount,$type);
	 
		ajaxmsg($repay_detail); 
	}
public function quick($borrow_id,$amount,$type) {

	$data =M('borrow_info')->where('id='.$borrow_id)->find();
	  if( !empty($data) )
	  {
		  $repayment_type = $data['repayment_type'];//借款类型 
		  $reward_num = $data['reward_num'];//奖励比例
		   $rate = $data['borrow_interest_rate'];//利率
		   $borrow_duration =  $data['borrow_duration'];//期限
			$date_type = $data['duration_unit'];
	  }
		

	 switch ($repayment_type) {
                case '1'://按天到期还款
                    $result = EqualEndMonthOnly(
                        array(
                            'month_times' => $borrow_duration,
                            'account' => $amount,
                            'year_apr' => $rate,
                            'type' => 'all'
                        ),
                        false
                    );
                    $_result[] = $result;
                    break;
                case '4'://每月还息到期还本息
                    $_result = EqualEndMonth(
                        array(
                            'duration' => $borrow_duration,
                            'account' => $amount,
                            'year_apr' => $rate,
							'type' => 'all'
                        ),
                        $date_type
                    );
                    break;
                case '5'://一次性还款
                    $result = EqualEndMonthOnly(
                        array(
                            'month_times' => $borrow_duration,
                            'account' => $amount,
                            'year_apr' => $rate,
                            'type' => 'all'
                        ),
                        $date_type
                    );
                    $_result[] = $result;
                    break;
                case '2'://分期还款
                default:
                    $_result = EqualMonth(
                        array(
                            'money' => $amount,
                            'duration' => $borrow_duration,
                            'year_apr' => $rate,
							'type' => 'all'
                        ),
                        false
                    );
                    break;
				case '6':
					break;
            }
		
		if($repayment_type == 6)
		{
				
				$borrowinfo = TborrowModel::get_format_borrow_info($borrow_id, "b.*,  bwd.*, bd.bianhao");
				$borrowInterest = getBorrowInterest($type, $amount, $borrow_duration, $rate, $borrowinfo['duration_unit']);

				$repay_detail['interest'] = $borrowInterest; //利息	
				$repay_detail['reward_num'] =$reward_num* $amount;//奖励
				$repay_detail['amount'] =$repay_detail['interest']+$amount;//还款金额
		}else{
			// 按月分期付款
			
			if( $repayment_type == BorrowModel::BID_SINGLE_CONFIG_REPAY_TYPE_MONTH ) { // 等额本息不是按天计息，其它都按天计息
				$repay_detail['interest'] = $_result['repayment_money'] - $amount;
			}else{
				if($date_type== 1) {
					$duration_days = getDaysByMonth($borrow_duration); // 计息天数
				} else {
					$duration_days = $borrow_duration;
				}
				$repay_detail['interest'] = bcdiv(bcmul(bcmul($amount, $duration_days, 6),$rate, 6), 36500, 2);  //利息

				//print_r($repay_detail['interest']);exit;
				//$repay_detail['interest'] = $_result['interest']; //利息	
			} 
			$repay_detail['reward_num'] =$reward_num* $amount;//奖励
			$repay_detail['amount'] =$repay_detail['interest']+$amount;//还款金额
		}
		return $repay_detail;
	}
/*public function quickcountrate() {
	$jsoncode = file_get_contents("php://input");
    $arr = array();
    $arr = json_decode($jsoncode,true);
	$arr = AppCommonAction::get_decrypt_json($arr);

	 $borrow_id = intval($arr['id']);
	 $amount = floatval($arr['amount']);
	 $type = intval($arr['type']);
	$repay_detail = $this->quick($borrow_id,$amount,$type);
	 
		ajaxmsg($repay_detail); 
	}
public function quick($borrow_id,$amount,$type) {

	$data =M('borrow_info')->where('id='.$borrow_id)->find();
	  if( !empty($data) )
	  {
		  $repayment_type = $data['repayment_type'];//借款类型 
		  $reward_num = $data['reward_num'];//奖励比例
		   $rate = $data['borrow_interest_rate'];//利率
		   $borrow_duration =  $data['borrow_duration'];//期限
			$date_type = $data['duration_unit'];
	  }
		

	 switch ($repayment_type) {
                case '1'://按天到期还款
                    $result = EqualEndMonthOnly(
                        array(
                            'month_times' => $borrow_duration,
                            'account' => $amount,
                            'year_apr' => $rate,
                            'type' => 'all'
                        ),
                        false
                    );
                    $_result[] = $result;
                    break;
                case '4'://每月还息到期还本息
                    $_result = EqualEndMonth(
                        array(
                            'duration' => $borrow_duration,
                            'account' => $amount,
                            'year_apr' => $rate,
							'type' => 'all'
                        ),
                        $date_type
                    );
                    break;
                case '5'://一次性还款
                    $result = EqualEndMonthOnly(
                        array(
                            'month_times' => $borrow_duration,
                            'account' => $amount,
                            'year_apr' => $rate,
                            'type' => 'all'
                        ),
                        $date_type
                    );
                    $_result[] = $result;
                    break;
                case '2'://分期还款
                default:
                    $_result = EqualMonth(
                        array(
                            'money' => $amount,
                            'duration' => $borrow_duration,
                            'year_apr' => $rate,
							'type' => 'all'
                        ),
                        false
                    );
                    break;
				case '6':
					break;
            }
		
		if($repayment_type == 6)
		{
				
				$borrowinfo = TborrowModel::get_format_borrow_info($borrow_id, "b.*,  bwd.*, bd.bianhao");
				$borrowInterest = getBorrowInterest($type, $amount, $borrow_duration, $rate, $borrowinfo['duration_unit']);

				$repay_detail['interest'] = $borrowInterest; //利息	
				$repay_detail['reward_num'] =$reward_num* $amount;//奖励
				$repay_detail['amount'] =$repay_detail['interest']+$amount;//还款金额
		}else{
			// 按月分期付款
			if( $repayment_type == BorrowModel::BID_SINGLE_CONFIG_REPAY_TYPE_MONTH ) { // 等额本息不是按天计息，其它都按天计息
				$repay_detail['interest'] = $_result['repayment_money'] - $amount;
			}else{
				$repay_detail['interest'] = $_result['interest']; //利息	
			} 
			$repay_detail['reward_num'] =$reward_num* $amount;//奖励
			$repay_detail['amount'] =$repay_detail['interest']+$amount;//还款金额
		}
		return $repay_detail;
	}*/
} 
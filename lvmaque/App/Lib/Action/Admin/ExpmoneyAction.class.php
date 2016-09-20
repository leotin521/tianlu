<?php
    class ExpmoneyAction extends ACommonAction
    {
        public function listExp()
        {
            
            $pre = C('DB_PREFIX');
            $condition = '1';
            
            $uname = text($_GET['uname']);
            $type = intval($_GET['type']);
            !empty($uname) && $condition .= " and m.user_name='{$uname}'";
            $type && $condition .= " and em.type=".$type;
            if(isset($_GET['coupon_type'])&& $_GET['coupon_type'] !== '' ){
                $coupon_type = intval($_GET['coupon_type']);
                $condition .= " and em.is_taste=".$coupon_type;
            }

            import("ORG.Util.Page");
            $count = M('expand_money em')
                    ->join($pre."members as m ON em.uid=m.id")
                    ->field("em.*, m.user_name")    
                    ->where($condition)
                    ->count('em.id');

            $p = new Page($count, 15);
            $page = $p->show();
            $Lsql = "{$p->firstRow},{$p->listRows}";

            $list = M('expand_money as em')
                        ->join($pre."members as m ON em.uid=m.id")
                        ->field("em.*, m.user_name")
                        ->where($condition)
                        ->limit($Lsql)
                        ->order("status, expired_time desc")
                        ->select();
            $this->assign('list', $list);
            $this->assign('pages',$page);

            $coupon_type_list = ExpandMoneyModel::get_coupon_type();
            $this->assign('coupon_type_list', $coupon_type_list);
            
            $status = array('1'=>'未使用', '2'=>'已过期', '4'=>'已使用');
            
            $this->assign('status_arr', $status);
            $exp_type = C('EXP_TYPE');
            $this->assign('exp_type', $exp_type);
            
            $this->display();    
        }   
        
        public function addExp()
        {    
            if($this->isPost()){

                $money =  intval($_POST['money']);
                $num = intval($_POST['num']);
                $expired = intval($_POST['expired_time']);
                $type = intval($_POST['type']);
                $is_taste = intval($_POST['is_taste']);
                $remark = "网站奖励";
                $invest_money = intval($_POST['invest_money']);
                
                $expired_time = strtotime("+{$expired} month");
                
                !$num && $this->error("优惠券已关闭");
                
                if(intval($_POST['isAll'])){
                    $users = M('members')->field('user_name')->select();
                    foreach($users as $user){
                        $user_arr[] = $user['user_name'];    
                    }
                }else{
                    if (empty($_POST['user'])){
                        $this->error("请输入指定会员的用户名！");
                    }
                    $user_arr = explode(',', $_POST['user']);
                }
                

                foreach($user_arr as $val){
                    if(!empty($val)){
                        $uid = M('members')->where("user_name= '{$val}'")->getField('id');
                        if(!$uid)  continue;
                        
                        $i=1;
                        
                        for(; $i<= $num; $i++){
                            $expand_money['uid'] =  $uid;
                            $expand_money['money'] = $money;
                            $expand_money['remark'] = $remark;
                            $expand_money['expired_time']  =  $expired_time;
                            $expand_money['add_time'] = time(); 
                            $expand_money['orders'] = "DH".build_order_no(); 
                            $expand_money['invest_money'] = $invest_money;
                            $expand_money['is_taste'] = $is_taste;
                            $expand_money['type'] = $type;
                            
                            $exp_id = M('expand_money')->add($expand_money);
                            $coupon_type = ExpandMoneyModel::get_coupon_type($is_taste);
                            addInnerMsg($uid,"恭喜您获得网站优惠券奖励！","恭喜您获得一张{$money}元的网站".$coupon_type."奖励，投资{$invest_money}元可用");//站内信
                        }
                    }
                         
                }
                alogs("Expmoney",0,1,'手动发放优惠券成功！');//管理员操作日志
        
                $this->success("发放完成", U('listexp'));
               
                
            }else{
                $expconf = array(
                    'num'=>1,
                    'money'=>5,
                    'expired_time'=>1,
                    'invest_money'=>1000,
                    'type'=>99,
                );
                $this->assign('expconf', $expconf);
                $this->display();
            }    
        } 
        
        public function index()
        {
            $map = array();
            if( !empty($_REQUEST['uname'])){
                $map['user_name'] = array("like",urldecode($_REQUEST['uname'])."%");
                $search['user_name'] = htmlspecialchars($_REQUEST['uname'], ENT_QUOTES);
            }
            if( !empty($_REQUEST['user_phone'])){
                $map['user_phone'] =$_REQUEST['user_phone'];
                $search['user_phone'] = htmlspecialchars($_REQUEST['user_phone'], ENT_QUOTES);
            }
            if(!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])){
                $timespan = strtotime(urldecode($_REQUEST['start_time'])).",".strtotime(urldecode($_REQUEST['end_time']));
                $map['reg_time'] = array("between",$timespan);
                $search['start_time'] = urldecode($_REQUEST['start_time']);
                $search['end_time'] = urldecode($_REQUEST['end_time']);
            }elseif(!empty($_REQUEST['start_time'])){
                $xtime = strtotime(urldecode($_REQUEST['start_time']));
                $map['reg_time'] = array("gt",$xtime);
                $search['start_time'] = $xtime;
            }elseif(!empty($_REQUEST['end_time'])){
                $xtime = strtotime(urldecode($_REQUEST['end_time']));
                $map['reg_time'] = array("lt",$xtime);
                $search['end_time'] = $xtime;
            }
            //分页处理
            import("ORG.Util.Page");
            $count = M('members')->count('id');
            $p = new Page($count, C('ADMIN_PAGE_SIZE'));
            $page = $p->show();
            $Lsql = "{$p->firstRow},{$p->listRows}";
            //分页处理
            
            //所有会员
            $memberinfo = M("members")->field(true)->where($map)->limit($Lsql)->order("id desc")->select();
            $this->assign("memberinfo",$memberinfo);
            $this->assign("pagebar", $page);
            $this->display();   
        }
        
        public function give_rewards()
        {
            if($this->isAjax() && !$_GET['uid']){
                $money = floatval($_POST['money']);
                $invest_money = floatval($_POST['investmoney']);
                $expired_time = strtotime($_POST['expired_time']);
                $remark =  text($_POST['remark']); 
                
                $expand_money['uid'] =  intval($_POST['uid']);
                $expand_money['money'] = $money;
                $expand_money['remark'] = $remark;
                $expand_money['expired_time']  =  $expired_time;
                $expand_money['type'] = 'exp';
                $expand_money['add_time'] = time(); 
                $expand_money['orders'] = "DH".build_order_no(); 
                $expand_money['invest_money'] = $invest_money;
                $expand_money['type'] = intval($_POST['type']); 
                
                $exp_res = M('expand_money')->add($expand_money);
                if($exp_res){
                    ajaxmsg('发放成功');
                }else{
                    ajaxmsg('发放失败',0);
                }
                
            }
            $uid = intval($_GET['uid']);
            $minfo = M("members")->field(true)->where("id = {$uid}")->find();
            $minfo['reg_time'] = date("Y-m-d",$minfo['reg_time']);
            $this->assign('minfo',$minfo);
            $this->assign('exp_type', C("EXP_TYPE"));
            
            $this->display();
        }

        /**
         * TODO:现优惠券配置以数组存进去，调取时通过存进去的key值判断，如果后期调整位置或者增加删除配置项，会导致整个前台调取的参数错误，漏洞级别高
         */
        public function setExp()
        {
            $data = $_POST['exp'];
            $result = array();
            foreach($data as $k=>$v){
                $result[$k] = filter_only_array($data[$k]);
            }
            if($this->isPost()){
                $exp_conf = $result;
                FS("expconf",$exp_conf,"Webconfig/");
                $this->success("保存成功");
                exit;
            }
            $expconf = FS("Webconfig/expconf");
            $this->assign('expconf', $expconf);
            $exp_type = C('EXP_TYPE');
            unset($exp_type[99]);
            unset($exp_type[98]);
            $this->assign('exp_type', $exp_type);
            $this->display();    
        }
        
        public function countExp()
        {
            $count_exp[0] = $this->countTypeExpMoney(0, 0);
            $count_exp[99] = $this->countTypeExpMoney(0, 0);
            $exp_type = C('EXP_TYPE');
            foreach($exp_type as $k=>$v){
                $count_exp[$k] = $this->countTypeExpMoney($k, 0);
            }
            $this->assign('count_exp', $count_exp);

            $count_exp_taste[0] = $this->countTypeExpMoney(0, 1);
            $count_exp_taste[99] = $this->countTypeExpMoney(0, 1);
            foreach($exp_type as $k=>$v){
                $count_exp_taste[$k] = $this->countTypeExpMoney($k, 1);
            }
            $this->assign('count_exp_taste', $count_exp_taste);
            $this->assign('exp_type', C('EXP_TYPE'));
            if (intval($_GET['export']) == 1) {
                import("ORG.Io.Excel");
                $row = array();
                if (intval($_GET['type']) == 1){
                    $info = $count_exp;
                    $res = '抵现券';
                }elseif (intval($_GET['type']) == 2){
                    $info = $count_exp_taste;
                    $res = '体验券';
                }
                alogs("countExp", 0, 1, "执行了{$res}统计导出操作！"); //管理员操作日志
                $i = 1;
                foreach ($info as $k=>$v) {
                    if (empty($info[$k]['c_money'])) $info[$k]['c_money']=0;
                    if (empty($info[$k]['y_money'])) $info[$k]['y_money']=0;
                    if (empty($info[$k]['ex_money'])) $info[$k]['ex_money']=0;
                    if (empty($info[$k]['n_money'])) $info[$k]['n_money']=0;
                    if ($k==0) {
                        $row[$i]['c_money'] = "总共派发了：{$info[$k]['c_money']}元{$res}";
                    }else{
                        $row[$i]['c_money'] = "{$exp_type[$k]}发了：{$info[$k]['c_money']}元{$res}";
                    }
                    $row[$i]['y_money'] = "使用了{$info[$k]['y_money']}元";
                    $row[$i]['ex_money'] = "过期了{$info[$k]['ex_money']}元";
                    $row[$i]['n_money'] = "未使用{$info[$k]['n_money']}元";
                    $i++;
                }
                $xls = new Excel_XML('UTF-8', false, 'datalist');
                $xls->addArray($row);
                $xls->generateXML("countExp");
            }else {
                $this->display();
            }
        }
        
        /**
        * 统计各类优惠券的资金
        * 
        * @param intval $type=0 // 类型id ，0 标识统计所有的
        */
        private function countTypeExpMoney($type=0, $coupon_type=null)
        {
            $condition = '';
            $type = intval($type);
            if($type){
               $condition .= " and type=".$type;
            }
            if(isset($coupon_type) ) {
                $coupon_type = intval($coupon_type);
                $condition .= " and is_taste=".$coupon_type;
            }

            $c_money = M('expand_money')->where("1 ".$condition)->sum('money');  //统计优惠券总金额  
            //$this->assign('c_money', $c_money);
            
            $n_money = M('expand_money')->where("status=1 and expired_time> ".time().$condition)->sum('money');  //统计未使用优惠券金额  
            //$this->assign('n_money', $n_money);
            
            $y_money = M('expand_money')->where("status=4 ".$condition)->sum('money');  //统计已经使用优惠券金额  
            //$this->assign('y_money', $y_money);
            
            $ex_money = M('expand_money')->where("status=1 and expired_time < ".time().$condition)->sum('money');  //统计已过期优惠券金额  
            //$this->assign('ex_money', $ex_money);    
            
            $exp_money = array(
                                'c_money'=>$c_money,
                                'n_money'=>$n_money,
                                'y_money'=>$y_money,
                                'ex_money'=>$ex_money
                            );
            return $exp_money;
        }
        
        /**
        * 积分兑换设置
        * 
        */
        public function redeem()
        {
            $data = $_POST['redeem'];
            $result = array();
            foreach($data as $k=>$v){
                $result[$k] = filter_only_array($data[$k]);
            }
            if($this->isPost()){
                foreach($result['integral'] as $key=>$val){
                    $i=$key+1;
                    $redeem_conf[$i]['integral'] = $val;
                    $redeem_conf[$i]['money'] = $result['money'][$key];
                    $redeem_conf[$i]['invest_money'] = $result['invest_money'][$key];
                    $redeem_conf[$i]['is_taste'] = $result['is_taste'][$key];
                    $redeem_conf[$i]['expired_time'] = $result['expired_time'][$key];

                }
                FS("reddemconf",$redeem_conf,"Webconfig/");
                $this->success("保存成功", U('redeem'));
                exit;
            }
            $reddemconf = FS("Webconfig/reddemconf");
            $this->assign('reddemconf', $reddemconf);
            $this->display();    
        }
        
    }  
?>

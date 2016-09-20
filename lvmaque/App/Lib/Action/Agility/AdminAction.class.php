<?php
    /**
    * 定投宝后台管理程序
    */
    class AdminAction extends ACommonAction
    {
        
        
        function _initialize()
        {
            parent::_initialize();
            $this->Bao = M('bao');
            $this->BaoLog = M('bao_log');
            $this->BaoInvest = M('bao_invest');
            $this->BaoRecord = M('bao_record');
            import("ORG.Util.Page");
        }
        /**
        * 定投宝金额统计
        * 
        */
        public function index(){
            /**
            * 转入总额，转出总额，收益总额，
            * 目前总资本（封存总额，解封总额）
            */
            $this->display();
        }  
        
        /**
        * 添加产品
        * 
        */
        public function addItem()
        {
            $id = intval($_GET['id']);
            if($this->isPost()){
                $this->checkItem();
                
                $item_arr['status'] = 1; 
                $item_arr['repayment_period'] = intval($_POST['repayment_period']);
                $item_arr['start_funds'] = intval($_POST['start_funds']); 
                $item_arr['funds'] = intval($_POST['funds']); 
                if($item_arr['funds']%$item_arr['start_funds']!=0){
                             $this->error('必须为正整数，且计划金额是起投金额的整数倍');
                }
                if(!$id){
                    $item_arr['batch_no'] = $_POST['batch_no'];
                    $item_arr['funds'] = intval($_POST['funds']);
                    $item_arr['interest_rate'] = floatval($_POST['interest_rate']);
                    $item_arr['term'] = intval($_POST['term']);
                    $item_arr['add_time'] = time();
                    $item_arr['add_ip']  = get_client_ip();
                    $item_arr['add_userid'] = session('admin_id'); 
                    $item_arr['online_time'] = empty($_POST['online_time']) ? time(): strtotime($_POST['online_time']);
                    $new_id = $this->Bao->add($item_arr); 
                    if($new_id){
                        $this->success('添加成功！', U('itemList',array('status'=>1)));
                        exit;
                    }else{
                        $this->error('抱歉，添加失败');
                    }  
                }else{
                    $new_id = $this->Bao->where("id={$id}")->save($item_arr); 
                    if($new_id){
                        $this->success('更新成功！', U('itemList',array('status'=>1)));
                        exit;
                    }else{
                        $this->error('抱歉，更新失败');
                    }    
                }

                
            }
            if($id){
                $vo = $this->Bao->where("id={$id}")->find();
                $this->assign('vo', $vo);
            }else{
                $this->checkItem();
            }
            $agiconf = FS("Webconfig/agiconf");
            $this->assign('info', $agiconf);
                
            $vo['batch_no'] = 'B'.date("YmdHis");
            $this->assign('vo', $vo);
            $this->display();
        }
        
        private function checkItem()
        {
            $is_not_item = $this->Bao->where("funds<>raise_funds and status=1")->count();
            if($is_not_item){
                $this->error("存在未完成的项目，不允许发布新项目！");
            }
        }
        
        /**
        * 删除操作
        * 
        */
        public function del()
        {
            $ids = intval($_POST['idarr']);
            $bao_info = $this->Bao->field('batch_no')->where("id={$id}")->find();
            $invest_num = $this->BaoInvest->where("batch_no='{$bao_info['batch_no']}'")->count('id');
            if(!$invest_num){
                if($this->Bao->where("id={$ids}")->delete()){
                    $this->success("成功删除项目",'',$ids.',');
                    exit;
                }
            }
            $this->error("删除项目失败");     
        }
        
        /**
        * 项目列表
        * 
        */
        public function itemList()
        {
            $condition = 1;
            $status = isset($_GET['status'])? intval($_GET['status']):0;
            if($status){
                $condition .= " and status=".$status;
            }
            $page_size = C('ADMIN_PAGE_SIZE');
            $count  = $this->Bao->where($condition)->count('id'); // 查询满足要求的总记录数   
            $Page = new Page($count,$page_size); // 实例化分页类传入总记录数和每页显示的记录数   
            $show = $Page->show(); // 分页显示输出
            $fields = "*";
            $order = "id DESC";
        
            $list = $this->Bao->field($fields)->where($condition)->order($order)->limit($Page->firstRow.','.$Page->listRows)->select();
            foreach($list as $k=>$v){

                $bao_invest = M('bao_invest')->field("sum(money) as invest_money, sum(out_money) as out_money, deadline")->where("batch_no='{$v['batch_no']}' ")->find();
                $list[$k]['deadline'] = $bao_invest['deadline'];
                $list[$k]['invest_money'] = $bao_invest['invest_money'];
                $list[$k]['out_money'] = $bao_invest['out_money'];
                
                
            }
            $this->assign('pagebar', $show);
            $this->assign('list', $list);
            $this->assign('status', $status);
            $this->display();    
        }
        
        public function buyList()
        {
            /**
            * 购买资金列表
            * 订单号，用户名，金额，购买时间，状态，处理人，处理时间，操作
            */
            
            $condition = " type=1";
            if(isset($_GET['status'])){
                $condition .= " and status=".intval($_GET['status']);
            }
            if(isset($_GET['batchno'])){
                $condition .= " and batch_no='".$_GET['batchno']."'";
            }
            $page_size = 15;
            $count  = $this->BaoLog->where($condition)->count('id'); // 查询满足要求的总记录数   
            $Page = new Page($count,$page_size); // 实例化分页类传入总记录数和每页显示的记录数   
            $show = $Page->show(); // 分页显示输出
            $fields = "*";
            $order = "id DESC";
        
            $list = $this->BaoLog->field($fields)->where($condition)->order($order)->limit($Page->firstRow.','.$Page->listRows)->select();

            /**
            foreach($list as $k=>$v){
                $completed = $this->BaoInvest->where("batch_no='{$v['batch_no']}'")->sum('money');
                $list[$k]['surplus'] = bcsub($v['funds'], $completed);
            } **/
            $this->assign('pagebar', $show);
            $this->assign('list', $list);
            $this->display();
        }
        
        public function redemptionList()
        {
            /**
            * 赎回资金列表
            * 订单号，用户名，金额，购买时间，状态，处理人，处理时间，操作
            */
            $condition = " type=2";
            if(isset($_GET['status'])){
                $condition .= " and status=".intval($_GET['status']);
            }
            if(isset($_GET['batchno'])){
                $condition .= " and batch_no='".$_GET['batchno']."'";
            }
            $page_size = 15;
            $count  = $this->BaoLog->where($condition)->count('id'); // 查询满足要求的总记录数   
            $Page = new Page($count,$page_size); // 实例化分页类传入总记录数和每页显示的记录数   
            $show = $Page->show(); // 分页显示输出
            $fields = "*";
            $order = "id DESC";
        
            $list = $this->BaoLog->field($fields)->where($condition)->order($order)->limit($Page->firstRow.','.$Page->listRows)->select();

            /**
            foreach($list as $k=>$v){
                $completed = $this->BaoInvest->where("batch_no='{$v['batch_no']}'")->sum('money');
                $list[$k]['surplus'] = bcsub($v['funds'], $completed);
            } **/
            $this->assign('pagebar', $show);
            $this->assign('list', $list);
            $this->display();
        }
        
        /**
        * 用户资金列表
        * 
        */
        public function userMoneyList()
        {
            /**
            * 用户、本金、利息    
            */
            $page_size = C('ADMIN_PAGE_SIZE');
            $count  = M('bao_invest')->group("uid")->count('id'); // 查询满足要求的总记录数   
            $Page = new Page($count,$page_size); // 实例化分页类传入总记录数和每页显示的记录数   
            $show = $Page->show(); // 分页显示输出
            $fields = "*";
            $order = "id DESC";
            
            $list = M('bao_invest')
                        ->field("sum(money) as money, sum(out_money) as out_money, sum(interest) as interest, uid")
                        ->group("uid")
                        ->limit($Page->firstRow.','.$Page->listRows)
                        ->select(); 
            foreach($list as $key=>$val){
                $list[$key]['user_name'] = M('members')->where("id={$val['uid']}")->getField('user_name');
                $list[$key]['total_money'] = $this->BaoLog->where("uid={$val['uid']} and status=1 and type=1")->sum('money');
            }
            $this->assign('list', $list);
            $this->assign('pagebar', $show);
            $this->display();
        }
        

        /**
        * 收益记录
        * 
        */
        public function interestRecord()
        {
            $this->display();
        }
        
        /**
        * 默认配置设置
        * 
        */
        public function setagi()
        {
            if($this->isPost()){
                $agi = $_POST['agi'];
                if(intval($agi['funds'])%intval($agi['start_funds'])!='0'){    
                    $this->error('必须为正整数，计划金额是起投金额的整数倍');      
                }else{
                    FS("agiconf",$agi,"Webconfig/"); 
                    $this->success("操作成功");
                }
            }else{
                $agiconf = FS("Webconfig/agiconf");
                $this->assign('info', $agiconf);
                $this->display();    
            }    
        }
		
		/**
        * 还款
        *
        */
        public function repayment(){
            
            if(!isset($_GET['batch_no'])) $this->error("编号错误");
            $batch_no  = text($_GET['batch_no']);
            
            $deadline = M('bao_invest')->where("batch_no='{$batch_no}' and money>0")->getField('deadline');
            $hours  = intval(($deadline-time())/3600);
            if($hours >0 && $hours > 24){
                $this->error("还未到还款日期，请在还款时间前24个小时内还款或者还款日期后还款！");
            }
            
            $list = M('bao_invest')->field(true)->where("batch_no='{$batch_no}' and money>0")->select();

            if(!empty($list)){
                $res = true;
                M()->startTrans();
                foreach($list as $key=>$v){
                    $user_info = getMinfo($v['uid'],"m.user_name");
                    
                    $data['batch_no'] = $v['batch_no'];
                    $data['money'] = $v['money'];
                    $data['status'] = 1;
                    $data['type'] = 2;
                    $data['uid'] = $v['uid'];
                    $data['user_name'] = $user_info['user_name'];
                    $data['add_time'] = time();
                    $data['add_ip'] = get_client_ip();
                    $data['auditors_id'] = session('admin_id');
                    $data['auditors_user'] = session('admin_user_name');
                    $data['auditors_time'] = time();
                    $data['auditors_ip'] = get_client_ip();
                    $data['remark'] = "到期还款";
                    
                    
                    $log_res = M('bao_log')->add($data);
                    
                    $invest_data['money'] = 0;
                    $invest_data['out_money'] = $v['out_money'] + $v['money'];
                    
                    $invest_res = M('bao_invest')->where("id={$v['id']}")->save($invest_data);
                    
                    $money_log_res = false;
                    D("AgilityBehavior");
                    $AgilityBehavior = new AgilityBehavior();
                    $money_log_res = $AgilityBehavior->moneyLog($v['uid'], 54, $v['money'], AgilityBehavior::$THE_SPIRIT."【{$batch_no}】还款{$v['money']}元");

                    if($log_res && $invest_res && $money_log_res){
                        $res = true;
                    }else{
                        $res = false;
                        break;
                    }
                }

                $bao_res = M('bao')->where("batch_no='{$batch_no}'")->save(array('status'=>4));  

                if($res && $bao_res){
                    M()->commit();
                    $this->success("还款完成"); exit;
                }else{
                    M()->rollback();
                    $this->error("还款失败");
                }
            
            }else{
                $this->error("没有要还款的数据！", U("itemlist"));
            }
        
        }
        
        /**
        * 持有记录
        *  一个用户只有一条记录
        * 指定项目的持有记录
        */
        public function holdsRecord()
        {
            $batchNo = text($_GET['batchno']);
            
            $condition = "batch_no='{$batchNo}'";
            $page_size = 15;
            $count  = $this->BaoInvest->where($condition)->count('id'); // 查询满足要求的总记录数   
            $Page = new Page($count,$page_size); // 实例化分页类传入总记录数和每页显示的记录数   
            $show = $Page->show(); // 分页显示输出
            $fields = "*";
            $order = "id DESC";
        
            $list = $this->BaoInvest->field($fields)->where($condition)->order($order)->limit($Page->firstRow.','.$Page->listRows)->select();
            foreach($list as $key=>$val){
                $user =  getMinfo($val['uid'], "m.user_name");
                $list[$key]['user_name'] = $user['user_name'];
            }
            $this->assign('pagebar', $show);
            $this->assign('list', $list);

            $this->display();
        }
        
        /**
        * 执行结束项目
        *  如果存在购买情况则进入还款状态，不存在购买进入结束状态
        */
        public function endItem()
        {
            $batchNo = text($_GET['batch_no']);
            if(!$batchNo){
                $this->error("参数错误！");    
            }    
            $status_arr = array('2'=>'还款', '4'=>'结束');
            $raise_funds = $this->Bao->where("batch_no='{$batchNo}'")->getField('raise_funds');
            if($raise_funds > 0){
                $status = 2;
            }else{
                $status = 4;
            }

            $bao_res = $this->Bao->where("batch_no='{$batchNo}'")->save(array('status'=>$status));
            if($bao_res){
                $this->success("操作成功，成为{$status_arr[$status]}状态！", U("itemlist", array('status'=>$status)));
                exit;
            }else{
                $this->error("很遗憾！操作失败");
            }
        }
        
        
    }  
?>

<?php
    /**
    * 灵活宝用户中心管理
    */
    class UserAction extends MCommonAction
    {
        
        /**
        * 产品首页
        * 
        */
        public function index()
        {
            $interest = BaoInvestModel::get_sum_interest($this->uid);
            $this->assign('interest', $interest); // 总收益

            $assets = BaoInvestModel::get_sum_money($this->uid); //资产总额

            $this->assign('assets', $assets);
            
            $recently = M('bao_record')->where("uid={$this->uid}")->order('id desc')->getField('money');
            $this->assign('recently', $recently);   // 最近收益
            
            $myItem = $this->getMyItem($this->uid); // 投资中的项目
            $this->assign('myitem', $myItem);   


            $this->display();    
        }
        
        /**
        * 获取投资中，还款中的项目
        * 
        * @param mixed $uid
        
        private function getMyItem($uid)
        {
            $condition =  " i.uid={$uid} and b.status in (1,2)";  
            $item_list = M("bao as b")
                        ->join(C('DB_PREFIX')."bao_invest as i ON b.batch_no=i.batch_no")
                        ->field("b.batch_no, i.add_time, b.interest_rate, i.deadline, i.interest, i.money")
                        ->where($condition)
                        ->order('i.add_time')
                        ->group('b.batch_no')
                        ->select();
            foreach($item_list as $key=>$val){
                //$out_money = M('bao_log')->where('type=2 and status<>2')->sum('money');
                $capital = M('bao_log')->where('type=1 and status=1')->sum('money');  
                //$item_list[$key]['out_money']   = $out_money;        // 已赎本息
                $item_list[$key]['capital'] = $capital;//当前本金  
            }
            if(empty($item_list)){
                return '';
            }else{
                return $item_list;
            }            
                        
            
        } */
        
        /**
        * 获取投资中，还款中的项目
        * 
        * @param mixed $uid
        */
        private function getMyItem($uid)
        {
            $condition =  " i.uid={$uid} and b.status in (1,2)";  
            $item_list = M("bao as b")
                        ->join(C('DB_PREFIX')."bao_invest as i ON b.batch_no=i.batch_no")
                        ->field("b.batch_no, b.interest_rate, i.deadline, i.interest, i.money")
                        ->where($condition)
                        ->order('i.add_time')
                        ->select();
            foreach($item_list as $key=>$val){
                $out_money = M('bao_log')->where("type=2 and status=1 and uid={$uid}  and batch_no='{$val['batch_no']}'")->sum('money');
                $capital = M('bao_log')->where("type=1 and status=1 and uid={$uid} and batch_no='{$val['batch_no']}'")->sum('money');  
                //$item_list[$key]['out_money']   = $out_money;        // 已赎本息
                $item_list[$key]['capital'] = $capital;//当前本金  
            }
            if(empty($item_list)){
                return '';
            }else{
                return $item_list;
            }            
                        
            
        }
        
        public function iteminfo()
        {
            $batch_no = text($_GET['batch']);
            $time = time();
            if(empty($batch_no)){
                $this->error('参数错误！');
            }
            $bao_invest = $this->getBaoInvest($batch_no, $this->uid);
            if (empty($bao_invest)){
                $this->error('非法操作！');
            }
            $bao_info = M('bao')->field(true)->where("batch_no='{$batch_no}'")->find(); 
            $this->assign('bao_info', $bao_info);
            $this->assign('bao_invest', $bao_invest);
            
            $archive_time = M('bao_log')->where("batch_no='{$batch_no}' and uid={$this->uid}")->order("archive_time desc")->getField('archive_time');
            $this->assign('archive_time', $archive_time);
            
            $add_time = M('bao_log')->where("batch_no='{$batch_no}' and uid={$this->uid} and type=1 and status=1" )->order("add_time asc")->getField('add_time');
            $this->assign('add_time', $add_time);
            
            $e_time = M('bao_record')->where("batch_no='{$batch_no}' and uid={$this->uid} and  status=1" )->order("e_time asc")->getField('e_time');

            $this->assign('e_time', $e_time);
            
            //统计收益记录
            $record['count'] = M('bao_record')->where("batch_no='{$batch_no}' and uid={$this->uid} and status=1")->count('id');
            $record['money'] = M('bao_record')->where("batch_no='{$batch_no}' and uid={$this->uid} and status=1")->sum('money');
            //已赚收益
            $record['incoming'] = BaoInvestModel::get_sum_interest($this->uid, $batch_no);
            
            $this->assign('record', $record);
            //赎回记录统计
            $ransom['count'] = M('bao_log')->where("batch_no='{$batch_no}' and type=2 ")->count('id');
            $ransom['money'] = M('bao_log')->where("batch_no='{$batch_no}' and type=2 and status=1")->sum('money');
            $this->assign('ransom', $ransom);

            $archive_money = M('bao_log')->where("batch_no='{$batch_no}' and uid={$this->uid} and type=1 and archive_time >= {$time} and status=1")->sum('money');// 封存本金

            $bao['money'] = bcsub($bao_invest['money'], $archive_money, 2);
            
            $this->assign('bao', $bao);
            
            $this->display();
        }
        
        /**
        * 赎回
        * 
        
        public function redeem()
        {
            $time = time();
            $batch = $_GET['batch'];
            if(empty($batch)){
                $this->error("参数错误");
            }
            $bao = M('bao_invest i')
                        ->field('i.money, b.interest_rate, b.name, b.batch_no, b.start_funds')
                        ->join("lzh_bao as b ON b.batch_no = i.batch_no")
                        ->where("i.batch_no='{$batch}' and i.uid={$this->uid}")
                        ->find();
            $e_time = M('bao_record')->where("batch_no='{$batch}' and uid={$this->uid}")->order("add_time desc")->getField('e_time');// 最后计息时间
            
            $archive_money = M('bao_log')->where("batch_no='{$batch}' and uid={$this->uid} and type=1 and archive_time >= {$time} and status=1")->sum('money');// 封存本金
            $bao['money'] = bcsub($bao['money'], $archive_money, 2);
            
            $this->assign('bao', $bao);
            $this->assign('e_time', $e_time);
            $this->display();    
        }
        */
        /**
        * 保存转出资金
        * 
        */
        public function redeemSave()
        {
            if(!$this->uid){
                echo json_encode(array("0","请登录后操作")); 
                exit;
            }
            $batch = text($_POST['batch']);
            $out_money = floatval($_POST['fredeemamount']);
            $uid = $this->uid; 
            $time =  time();
            
            $bao_info = M('bao')->field(true)->where("batch_no='{$batch}'")->find();
            $archive_money = M('bao_log')->where("batch_no='{$batch}' and uid={$uid} and type=1 and archive_time >= {$time}")->sum('money');// 封存本金
            $invest_money = M('bao_invest')->where("batch_no='{$batch}' and uid={$uid}")->getField("money");
            
            if(bcsub($invest_money, $archive_money, 2) < $out_money){
                echo json_encode(array("0","赎回金额大于可赎回金额"));  
                exit;   
            }
            if(bcsub($invest_money, $out_money, 2) && bcsub($invest_money, $out_money, 2) < $bao_info['start_funds']){
                echo json_encode(array("0","赎回后剩余金额不得小于最低投资金额"));
                exit;  
            }
            
            D("AgilityBehavior");
            $AgilityBehavior = new AgilityBehavior();
            $out_money_res = $AgilityBehavior->outMoney($batch, $out_money, $uid); // 赎回资金
            
            if($out_money_res){
               echo json_encode(array("1","赎回提交成功，请查证账户！")); 
            }else{
               echo json_encode(array("0","赎回提交失败，请重试！"));   
            }  

        }
        
        
        /**
        * 获取指定用户，指定项目的投资信息
        * 
        * @param mixed $batch
        * @param mixed $uid
        */
        private function getBaoInvest($batch, $uid)
        {
            $bao_invest = M('bao_invest')->field(true)->where("batch_no='{$batch}' and uid={$uid} ")->find();    
            return $bao_invest;
        }
        
        /**
        * 获取收益记录，带分页
        * 
        * @param mixed $batch
        * @param mixed $uid
        */
        public function getRecord()
        {
            $batch = $_GET['batch']; 
            $uid = intval($this->uid);
            $Page = D('Page');    
            $condition =  "batch_no='".$batch."' and uid={$uid}";  
            import("ORG.Util.Page");       
            $count = M("bao_record")->where($condition)->count('id');
            $Page     = new Page($count,4);
            
            $show = $Page->ajax_show2('record');
            $this->assign('page', $show);
            
            $list = M('bao_record')
                        ->field(true)
                        ->where($condition)
                        ->order('e_time desc')
                        ->limit($Page->firstRow.','.$Page->listRows)
                        ->select();
            $string = '';
            foreach($list as $k=>$v){
                $string .= '<tr class="yepageid"><td>'.date("Y-m-d", $v['e_time']).'</td><td>'.$v['money'].'</td><td>'.$v['funds'].'</td><td>已复投</td></tr>';
                
            }
            
            echo json_encode(array($show, $string));    

        }
        
        /**
        * 获取资金记录，带分页
        * 
        * @param mixed $batch
        * @param mixed $uid
        */
        public function getLog()
        {

            $status = array(0=>'审核中',1=>'成功',2=>'退回');
            $uid = intval($this->uid);
            $batch = $_GET['batch'];
            $type = intval($_GET['type']);
            $Page = D('Page');    
            $condition =  " uid={$uid}";  
            $type && $condition .= " and type=".$type;
            $batch && $condition .= " and batch_no='".$batch."'"; 
            
            import("ORG.Util.Page");       
            $count = M("bao_log")->where($condition)->count('id');
            $Page     = new Page($count,10);
            
            
            $show = $Page->ajax_show2('log');
            $this->assign('page', $show);
            
            $list = M('bao_log')
                        ->field(true)
                        ->where($condition)
                        ->order('add_time desc, auditors_time desc')
                        ->limit($Page->firstRow.','.$Page->listRows)
                        ->select();
            $string = '';
            foreach($list as $k=>$v){
                $v['remark']=='' && $v['remark'] = '无';
                $string .= '<tr height="35">
                            <td width="128" style="text-align:center">'.date("Y-m-d", $v['add_time']).'</td>
                            
                            <td width="128" style="text-align:center">'.$v['batch_no'].'</td>';
                if($v['type']==2){
                    $string .= '<td  style="text-align:center">-'.$v['money'].'元</td><td style="text-align:center">赎回</td>';
                }else{
                    $string .= '<td  style="text-align:center">+'.$v['money'].'</td><td style="text-align:center">投资</td>';
                }
                
                
                $string .= '<td  style="text-align:center">'.$status[$v['status']].'</td><td style="text-align:center">'.$v['remark'].'</td></tr>';
                
            }
            if( $string =='' )$string = '<tr height="35"><td colspan="6" style="text-align:center">暂无项目记录</td></tr>';

            $arr =  array($show, $string);
            echo json_encode($arr);
        }
        
         /**
        * 获取已结束项目，带分页
        * 
        * @param mixed $uid
        */
        public function getEndItem()
        {
            $uid = intval($this->uid);
            $Page = D('Page');    
            $condition =  " i.uid={$uid} and b.status=4 ";  
            import("ORG.Util.Page");       
            $count = M("bao as b")->join(C('DB_PREFIX')."bao_invest as i ON b.batch_no=i.batch_no")->where($condition)->count('b.id');
            
            $Page     = new Page($count,10);
            
            
            $show = $Page->ajax_show2('item');
            $this->assign('page', $show);
            
            $list =  M("bao as b")
                        ->join(C('DB_PREFIX')."bao_invest as i ON b.batch_no=i.batch_no")
                        ->field("b.batch_no, i.add_time, b.interest_rate, i.deadline, i.interest")
                        ->where($condition)
                        ->order('i.add_time')
                        ->limit($Page->firstRow.','.$Page->listRows)
                        ->select();
            $string = '';
            

            foreach($list as $k=>$v){
                $e_time = M('bao_record')->where("batch_no='{$v['batch_no']}'")->order("e_time asc")->getField('e_time');
                $money = M('bao_log')->where("batch_no='{$v['batch_no']}' and type=1 and status=1")->sum('money');
                $string .= '<tr height="35">
                            <td width="128" style="text-align:center">'.$v['batch_no'].'</td>
                            <td style="text-align:center">'.$v['interest_rate'].'%</td>
                            <td  style="text-align:center">'.date("Y-m-d", $e_time).'</td>
                            <td style="text-align:center">'.date("Y-m-d", $v['deadline']).'</td>
                            <td style="text-align:center">'.$money.'</td>
                            <td style="text-align:center" >'.$v['interest'].'</td></tr>';
                
            }
            $string =='' && $string = '<tr height="35"><td colspan="6" style="text-align:center">暂无项目记录</td></tr>';
            $arr =  array($show, $string);
            echo json_encode($arr);
        }
        
        /**
        * 获取资金记录，带分页
        * 
        * @param mixed $batch
        * @param mixed $uid
        */
        public function getLog2()
        {

            $status = array(0=>'审核中',1=>'成功',2=>'退回');
            $uid = intval($this->uid);
            $batch = $_GET['batch'];
            $type = intval($_GET['type']);
            $Page = D('Page');    
            $condition =  " uid={$uid}";  
            $type && $condition .= " and type=".$type;
            $batch && $condition .= " and batch_no='".$batch."'"; 
            
            import("ORG.Util.Page");       
            $count = M("bao_log")->where($condition)->count('id');
            $Page     = new Page($count,10);
            
            
            $show = $Page->ajax_show2('log');
            $this->assign('page', $show);
            
            $list = M('bao_log')
                        ->field(true)
                        ->where($condition)
                        ->order('add_time desc, auditors_time desc')
                        ->limit($Page->firstRow.','.$Page->listRows)
                        ->select();

            $string = '';
            foreach($list as $k=>$v){
                $v['remark']=='' && $v['remark'] = '无';
                $string .= '<tr>
                            <td>'.date("Y-m-d", $v['add_time']).'</td>';
                if($v['type']==2){
                    $string .= '<td >-'.$v['money'].'元</td><td >赎回</td>';
                }else{
                    $string .= '<td >+'.$v['money'].'</td><td >投资</td>';
                }
                
                
                $string .= '<td >'.$status[$v['status']].'</td><td >'.$v['remark'].'</td></tr>';
                
            }
            if($string==''){
                $string = '<tr><td colspan="5"> 无记录</td></tr>';
            }
            $arr =  array($show, $string);
            echo json_encode($arr);
        }
        
    }  
?>

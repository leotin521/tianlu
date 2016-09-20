<?php
    class DebtAction extends ACommonAction
    {
        private $Debt;
        function _initialize()
        {
            parent::_initialize();
            D("DebtBehavior");
            $this->Debt = new DebtBehavior();
        }
        public function index()
        {
            $get_status = $this->_get("status");
            
            $status = '1';
            $get_status && $status =  " d.status = ".$get_status;
            stripos($get_status, ',') && $status = " d.status in ({$get_status})";

            $list = $this->Debt->adminList($status);
            $this->assign('list', $list);
            
            $template = '';
            $get_status == 3 && $template='list3';
            
            $this->display($template);
        }
          /**
         * @TODO 债权购买记录
         */
        public function record() {
                    $debt_id=  intval($_GET['debt_id']);
				    $page=isset($_GET['p']) ?  intval($_GET['p']) :1;
                    $debt_invest_id=M('debt')->where("id={$debt_id}")->getField('invest_id');
                    $where = array(
                              'parent_invest_id' => $debt_invest_id,
                    );
                    $fields = "bi.transfer_duration,bi.id bid,bi.serialid,bi.investor_uid,bi.add_time,bi.investor_capital,m.user_name,m.id mid";
                    $list = BorrowInvestorModel::getBorrowInvestByPage($where, $fields, $page, 10); //只取6个
                    $this->assign('list',$list);
                    $this->display();
        }
        public function audit()
        {
            if($_POST['dosubmit']){
                $status = intval($this->_post('status', 'intval','99'));
                $debt_id = intval($this->_post('debt_id', 'intval', 0));
                $remark = '管理员：'.htmlspecialchars($_POST['remark'], ENT_QUOTES);
                $data = array(
                    'status'=>$status,
                    'remark'=>$remark,
                );
                if($status == 2){//通过
                    $data['valid'] = time()+60*60*24*7;
                    if(!$result = M("debt")->where("id={$debt_id}")->save($data)){
                        $this->error("审核失败", U("debt/index"));
                    }
                    D("DebtBehavior");
                    $Debt = new DebtBehavior();
                    $invest_id = M("debt")->where("id={$debt_id}")->getField("invest_id");
                    $sell_uid = M("debt")->where("id={$debt_id}")->getField("sell_uid");
                    $detail_info = M('investor_detail')->field(" sum(capital) as capital, sum(interest) as interest")->where("invest_id={$invest_id} and status=14")->find();
                    $money = $detail_info['capital'] + $detail_info['interest'];
                    $Debt->moneyLog2($sell_uid, 47, 0, $money, "转让{$invest_id}号债权", 0); 
                    
                    $money_collect = M('member_money')->where("uid={$sell_uid}")->getField('money_collect');
                    $money_collect = bcsub($money_collect, $money, 2);
                    M('member_money')->where("uid={$sell_uid}")->save(array('money_collect'=>$money_collect));
                   
                }elseif($status == 3){// 不通过
                    $debt_info = M("debt")->field("invest_id")->where("id={$debt_id}")->find();
                    M("debt")->where("id={$debt_id}")->save($data);
                    M("borrow_investor")->where("id={$debt_info['invest_id']}")->save(array('status'=>4));
                    M("investor_detail")->where("invest_id={$debt_info['invest_id']} and status = 14")->save(array('status'=>7));
                }
                $this->success("审核成功！", U("debt/index")); 
            }else{
                $debt_id = $this->_get('debt_id','intval');
                $this->assign("debt_id", $debt_id);
                $this->display();    
            }
            
        }
    }
?>

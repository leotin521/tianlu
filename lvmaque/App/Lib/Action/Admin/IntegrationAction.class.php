<?php
    class IntegrationAction extends ACommonAction
    {
        public function listinvest()
        {
            
            $pre = C('DB_PREFIX');
            $condition = '1';
            
            $uname = text($_GET['uname']);
            !empty($uname) && $condition .= " and user_name='{$uname}'";

            import("ORG.Util.Page");
            $count = M('members')
                    ->field("id")    
                    ->where($condition)
                    ->count('id');

            $p = new Page($count, 15);
            $page = $p->show();
            $Lsql = "{$p->firstRow},{$p->listRows}";

            $list = M('members')
                        ->field("id, user_name, invest_credits, integral, active_integral")
                        ->where($condition)
                        ->limit($Lsql)
                        ->order("integral desc")
                        ->select();
            $this->assign('list', $list);
            $this->assign('pages',$page);
            $this->display();    
        }
        
        public function investdetail(){
            $uid = intval($_GET['uid']);
            $uname = text($_GET['uname']);
            $condition = "uid=$uid";
            import("ORG.Util.Page");
            $count = M('member_integrallog')
                        ->field("uid")
                        ->where($condition)
                        ->count('id');
            
            $p = new Page($count, 15);
            $page = $p->show();
            $Lsql = "{$p->firstRow},{$p->listRows}";
            
            $detail = M('member_integrallog')
                        ->field("uid, type, affect_integral, active_integral, account_integral, info, add_time")
                        ->where($condition)
                        ->limit($Lsql)
                        ->order("add_time desc")
                        ->select();
            if(!empty($detail)) {
                foreach ($detail as $k => $v) {
                    $detail[$k]['leixing'] = IntegrationModel::get_integra_type($type = $detail[$k]['type']);
                }
            }
            $this->assign('uname',$uname);
            $this->assign('detail',$detail);
            $this->assign('pages',$page);
            $this->display();    
        }
        
        public function listcredit()
        {
        
            $pre = C('DB_PREFIX');
            $condition = '1';
        
            $uname = text($_GET['uname']);
            !empty($uname) && $condition .= " and user_name='{$uname}'";
        
            import("ORG.Util.Page");
            $count = M('members')
            ->field("id")
            ->where($condition)
            ->count('id');
        
            $p = new Page($count, 15);
            $page = $p->show();
            $Lsql = "{$p->firstRow},{$p->listRows}";
        
            $list = M('members')
            ->field("id, user_name, credits")
            ->where($condition)
            ->limit($Lsql)
            ->order("credits desc")
            ->select();
            $this->assign('list', $list);
            $this->assign('pages',$page);
            $this->display();
        }
        
        public function creditdetail(){
            $uid = intval($_GET['uid']);
            $uname = text($_GET['uname']);
            $condition = "uid=$uid";
            import("ORG.Util.Page");
            $count = M('member_integrallog')
            ->field("uid")
            ->where($condition)
            ->count('id');
        
            $p = new Page($count, 15);
            $page = $p->show();
            $Lsql = "{$p->firstRow},{$p->listRows}";
        
            $detail = M('member_creditslog')
            ->field("uid, type, affect_credits, account_credits, info, add_time")
            ->where($condition)
            ->limit($Lsql)
            ->order("add_time desc")
            ->select();
            if(!empty($detail)) {
                foreach ($detail as $k => $v) {
                    $detail[$k]['leixing'] = IntegrationModel::get_credit_type($type = $detail[$k]['type']);
                }
            }
            $this->assign('uname',$uname);
            $this->assign('detail',$detail);
            $this->assign('pages',$page);
            $this->display();
        }
        
    }  
?>

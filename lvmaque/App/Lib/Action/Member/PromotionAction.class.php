<?php
/**
* 优惠券 推广管理
* @author zhang ji li 2015.03.06
* @copyright lvmaque
* 
*/
class PromotionAction extends MCommonAction {

    /**
    * 邀请奖励
    * 
    */
    public function index(){
        $uid = MembersModel::get_user_Encryption($this->uid);
        $url = "http://" . $_SERVER['HTTP_HOST'] . '/i/'. $uid;
        $this->assign('spread_url', $url);
        
        $expconf = FS("Webconfig/expconf");
        $this->assign('yq', $expconf[4]);
        
        $type_conf = $expconf[1];
        if($type_conf['num']){
            $this->assign('money',$type_conf['money']);
        }
        
        $User = M('members'); 
        import('ORG.Util.Page');
        $count      = $User->where("recommend_id = ".$this->uid)->count();
        $Page       = new Page($count,10);
        $show       = $Page->show();
        $user_list = $User->field("id, user_name, reg_time")->where("recommend_id = ".$this->uid)->limit($Page->firstRow.','.$Page->listRows)->select();
        
        if(count($user_list)){
            foreach($user_list as $key=>$val){
                $exp_money = M('expand_money')->where("source_uid={$val['id']}")->getField('money'); //已经赠送了

                if(empty($exp_money)){
                    $user_list[$key]['be'] = 0; 
                    $user_list[$key]['money'] = $expconf[4]['money'];      
                }else{
                    $user_list[$key]['be'] = 1;
                    $user_list[$key]['money'] = $exp_money;   
                }
                
            }    
        }
        
        $this->assign('user_list', $user_list);
        $this->assign('page',$show);
        
		$this->display();
    }

    /**
    * 优惠券首页
    *  @author zhang ji li 2015-03-07 
    */
    public function coupon(){
        $time = time();
        $this->countExpand();
        $condition = "uid = ".$this->uid;
        $status = isset($_GET['status'])? intval($_GET['status']):1;
        $order_num = isset($_GET['order'])? intval($_GET['order']):7;
        
        $status==1 && $condition .= " and status=1 and expired_time > ".$time ;  // 未使用的
        $status==4 && $condition .= " and status=4 " ;  // 已使用
        $status==3 && $condition .= " and status=1 and expired_time < ".$time ;  // 已过期
        
        
        $order = ' add_time asc';
        
        $order_num == 3 && $order = " expired_time asc " ; 
        $order_num == 4 && $order = " expired_time desc " ;
        $order_num == 5 && $order = " money asc " ;
        $order_num == 6 && $order = " money desc " ;
        $order_num == 7 && $order = " add_time asc " ;
        $order_num == 8 && $order = " add_time desc " ;
        
        import('ORG.Util.Page');
        $count      = M('expand_money')->where($condition)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $expand_list = M('expand_money')
                        ->field('money, invest_money, expired_time, type, use_time, remark, is_taste')
                        ->where($condition)
                        ->limit($Page->firstRow.','.$Page->listRows)
                        ->order($order)
                        ->select();
        $expand_list = ExpandMoneyModel::get_coupon_type_format($expand_list);
        $this->assign('expand_list', $expand_list);
        $this->assign('exp_type', C('EXP_TYPE'));
        $this->assign('status', $status);
        $this->assign('page', $show);
        $this->assign('order', $order_num);
        $this->display();
    }
    /**
    * 统计优惠券相关情况
    *  @author zhang ji li 2015-03-07
    */
    private function countExpand()
    {
        $n_num = M('expand_money')->where("status=1 and expired_time> ".time()." and uid=".$this->uid)->count('id');  //统计未使用优惠券  
        $this->assign('n_num', $n_num);
        
        $n_money = M('expand_money')->where("status=1 and expired_time> ".time()." and uid=".$this->uid)->sum('money');  //统计未使用优惠券金额  
        $this->assign('n_money', $n_money);
        
        $y_money = M('expand_money')->where("status=4  and uid=".$this->uid)->sum('money');  //统计已经使用优惠券金额  
        $this->assign('y_money', $y_money);
        
        $ex_money = M('expand_money')->where("status=1 and expired_time < ".time()." and uid=".$this->uid)->sum('money');  //统计已过期优惠券金额  
        $this->assign('ex_money', $ex_money);   
    }
    
    
    /**
    * 优惠券奖励记录
    * @author zhang ji li  2015-03-13
    */
    public function expLog()
    {
        $condition .= " uid={$this->uid}";
        import("ORG.Util.Page");
        $count = M('expand_money')
                ->where($condition)
                ->count('id');

        $p = new Page($count, 15);
        $page = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
            
        
         $list = M('expand_money')
                        ->field(true)
                        ->where($condition)
                        ->order("add_time desc ")
                        ->select();  
         $this->assign('list', $list);
         $this->assign('exp_type', C('EXP_TYPE'));
         $this->assign('pages', $page);
         
            
        $this->display();    
    }
    
    

    public function rewardlog(){
		$this->display();
    }

    public function promotion(){
		$_P_fee=get_global_setting();
		$this->assign("reward",$_P_fee);	
		$data['html'] = $this->fetch();
		exit(json_encode($data));
    }

    public function promotionlog(){
		$map['uid'] = $this->uid;
		$map['type'] = array("in","1,13");
		$list = getMoneyLog($map,15);
		
		$totalR = M('member_moneylog')->where("uid={$this->uid} AND type in(1,13)")->sum('affect_money');
		$this->assign("totalR",$totalR);		
		$this->assign("CR",M('members')->getFieldById($this->uid,'reward_money'));		
		$this->assign("list",$list['list']);		
		$this->assign("pagebar",$list['page']);		

		$data['html'] = $this->fetch();
		exit(json_encode($data));
    }

	public function promotionfriend(){
		$pre = C('DB_PREFIX');
		$uid=session('u_id');
		$field = " m.id,m.user_name,m.reg_time,sum(ml.affect_money) jiangli ";
		$field1 = " m.user_name,m.reg_time";
		$vm = M("members m")->field($field)->join(" lzh_member_moneylog ml ON m.id = ml.target_uid ")->where(" m.recommend_id ={$uid} AND ml.type =13")->group("ml.target_uid")->select();
		$vm1 = M("members m")->field($field1)->where(" m.recommend_id ={$uid}")->group("m.id")->select();
		$this->assign("vm",$vm);	
		$this->assign("vi",$vm1);
		$data['html'] = $this->fetch();
		exit(json_encode($data));
    }
}

<?php
// 本类由系统自动生成，仅供测试用途
class AutoAction extends MCommonAction {

    public function index(){
        $ckid = isset($_GET['ckid'])?$_GET['ckid']:2;
        $this->assign("default",$ckid);
		$this->display();
    }

    public function auto(){
        $version = FS("Webconfig/version");
        $this->assign("version",$version);
        if ($version['single']==1){
            $vo = M('auto_borrow')->field(true)->where("uid={$this->uid} AND borrow_type=1")->find();
            if($vo){
                $vocount = M()->query("SELECT count( `id` ) num FROM `lzh_auto_borrow` WHERE `borrow_type` =1
                    AND `invest_time` < (
                    SELECT `invest_time`
                    FROM `lzh_auto_borrow`
                    WHERE borrow_type=1 and uid ={$this->uid}) AND `is_use`=1");
            }
            
            $this->assign("num",$vocount['0']['num']);
            $this->assign("now",$vocount['0']['num']+1);
            $this->assign("vo",$vo);
        }
        if ($version['business']==1){
            $vot = M('auto_borrow')->field(true)->where("uid={$this->uid} AND borrow_type=6")->find();
            if($vot){
                $votcount = M()->query("SELECT count( `id` ) tnum FROM `lzh_auto_borrow` WHERE `borrow_type` =6
                    AND `invest_time` < (
                    SELECT `invest_time`
                    FROM `lzh_auto_borrow`
                    WHERE borrow_type=6 and uid ={$this->uid}) AND `is_use`=1");
            }
            $this->assign("tnum",$votcount['0']['tnum']);
            $this->assign("tnow",$votcount['0']['tnum']+1);
            $this->assign("vot",$vot);
        }

        if ($version['fund']==1){
            $vod= M('auto_borrow')->field(true)->where("uid={$this->uid} AND borrow_type=7")->find();
            if($vod){
                $vodcount = M()->query("SELECT count( `id` ) tnum FROM `lzh_auto_borrow` WHERE `borrow_type` =7
                    AND `invest_time` < (
                    SELECT `invest_time`
                    FROM `lzh_auto_borrow`
                    WHERE borrow_type=7 and uid ={$this->uid}) AND `is_use`=1");
            }
            
            $this->assign("dnum",$vodcount['0']['tnum']);
            $this->assign("dnow",$vodcount['0']['tnum']+1);
            $this->assign("vod",$vod);
        }
		
		$data['html'] = $this->fetch();
		echo $data['html'];
    }
    
    public function autolong(){
        $designer = FS("Webconfig/designer");
        $borrow_type = array('1'=>'散标&nbsp;&nbsp;&nbsp;&nbsp;','6' => $designer[6].'&nbsp;&nbsp;&nbsp;&nbsp;','7'=>$designer[7]);
        $map['uid'] = $this->uid;
        $type = intval($_GET['d']);
        $map['borrow_type'] = $type;
        $this->assign('type',$type);
        $this->assign('xs',1);  
        $vo = M('auto_borrow')->where($map)->find();
        $list = array();
        if (is_array($vo)){
            //`mxl:autoday`
            $MAXMOONS = 180;
            $vo['is_auto_day'] = ($vo['duration_to'] >= $MAXMOONS) ? 1 : 0; //1：月标，0天标
            $vo['duration_to'] = $vo['duration_to'] % $MAXMOONS; 
            //`mxl:autoday`
            $list = array($vo);
        }
        $this->assign('borrow_type',$borrow_type);
        $this->assign('list',$list);
        $data['html'] = $this->fetch();
        echo $data['html'];
    }

	public function setupauto(){
		$aid = intval($_POST['aid']);
		$s = intval($_POST['s']);
		$vo = M('auto_borrow')->where("uid={$this->uid} AND id={$aid}")->find();
		if(is_array($vo)){
			$newid = M('auto_borrow')->where("id={$aid}")->setField('is_use',$s);
			if($newid) exit("1");
			else exit("0");
		}else{
			exit("0");
		}
	}

public function savelong(){
		$x = M('members')->field("time_limit,user_leve")->find($this->uid);
		(intval($_POST['tendAmount'])==0)?$is_full=1:$is_full=0;
		
		$duration = explode(",",text($_POST['loancycle']));
		$data['uid'] = $this->uid;
		$data['account_money'] = floatval($_POST['miniamount']);
		$data['borrow_type'] = intval($_POST['borrowtype']);
		$data['interest_rate'] = floatval($_POST['interest']);
		$data['duration_from'] = intval($duration[0]);
		if(!empty($_REQUEST['expireddate']) && isset($_REQUEST['expireddate'])){
			$data['end_time'] = strtotime($_POST['expireddate']." 00:00:00");
		}else{
			$data['end_time'] = time()+60*60*24*30;
		}
		$data['duration_to'] = intval($duration[1]);
		//`mxl:autoday`
		$MAXMOONS = 180;
		if (isset($_POST['chkautoday']) && $_POST['chkautoday'] == 2199){
			$data['duration_to'] += $MAXMOONS;//此处隐含限制条件是duration_to最大不能超过75个月
		}
		//`mxl:autoday`
		$data['is_auto_full'] = $is_full;
		$data['invest_money'] = floatval($_POST['tendAmount']);
		$data['min_invest'] = floatval($_POST['mininvest']);
		$data['add_ip'] = get_client_ip();
		$data['add_time'] = time();
		
		$c = M('auto_borrow')->field('id')->where("uid={$this->uid} AND borrow_type={$data['borrow_type']}")->find();
		if(is_array($c)){
			$data['id'] = $c['id'];
			$newid = M('auto_borrow')->save($data);
			if($newid) ajaxmsg("修改成功",1);
			else ajaxmsg("修改失败，请重试",0);
		}
		else{
			$data['invest_time'] = time();
			$newid = M('auto_borrow')->add($data);
			if($newid) ajaxmsg("添加成功",1);
			else ajaxmsg("添加失败，请重试",0);
		}
	}

    public function autotransferindex(){
	
		$vo = M('auto_borrow')->where("uid={$this->uid} AND borrow_type=3")->find();
		$vo['is_use_name'] = ($vo['is_use']==0)?"当前设置已暂停使用":"当前设置已启用";
		$x = M('members')->field("time_limit,user_leve")->find($this->uid);
		if($x['time_limit']>0 && $x['user_leve']==1) $is_vip=1;
		else $is_vip=0;
		
		$this->assign('isvip',$is_vip);
		$this->assign('vo',$vo);
		$data['html'] = $this->fetch();
		exit(json_encode($data));
    }

}
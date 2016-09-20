<?php

    class IndexAction extends HCommonAction
    {
        public function index(){ //获取散标一个 权当推荐标
            // 企业直投
            $where = array(
                'b.borrow_type' => BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID,//企业直投
                'b.borrow_status' => array("in",'2,4,6,7'),
                'b.on_off' => 1,
                'b.is_xinshou'=>1
            );

            $fields = 'b.borrow_type,b.duration_unit,b.borrow_times,b.id,b.borrow_name,b.borrow_status,b.borrow_uid,b.borrow_duration,b.borrow_money,b.borrow_fee,b.borrow_interest_rate,b.repayment_type,b.has_borrow,b.b_img,b.add_time,m.user_name,b.borrow_max,b.is_tuijian,b.can_auto';
            $transfer_items = TborrowModel::getTborrowByPage($where, $fields, 1, 1, $order);

            //dump($transfer_items);
            if($transfer_items){
                foreach($transfer_items['tBorrow_items'] as $v){
                   $money =round($v['has_borrow']/$v['borrow_money']*100);
                  // echo $money;
                }
                $this->assign('money',$money);
            }
            $this->assign('transfer_items',$transfer_items);
            $this->assign("uid",$this->uid);

            $list=M("app")->where("type=0")->order("ranges desc")->select();
            if(empty($list)){
              $data['list'] = "未上传";
            }else{
                $_list=array();
                foreach($list as $k=>$v){
                    $_list[$k]['id'] = $v['id'];
                    $_list[$k]['pic']=$v['pic'];
                }
            }
            $data['list'] = $_list;
            $this->assign("banners",$data['list']);

            //dump($data['list']);
            $this->display();
        }


        public function seeinvest(){
            $id=intval($_GET['id']);
            //判断借款是否到期 到期发站内信
            $borr = M('borrow_info')->where("id = ".$_GET['id'])->field("borrow_duration,add_time")->select();
            $tim = $borr[0]["add_time"]+$borr[0]["borrow_duration"]*24*60*60;
            if("time()"<$tim ){
                $borr = M('inner_msg')->where("title = '您的{$_GET[id]}号借款已到期'")->count();
                if($borr<=0){
                    $inn = M("inner_msg"); // 实例化inner_msg对象
                    $data['uid'] = $this->uid;
                    $data['title'] = "您的{$_GET['id']}号借款已到期";
                    $data['msg'] = "您的{$_GET['id']}号借款已到期";
                    $data['send_time'] = time();
                    $inn->add($data);
                }
            }
            $pre = C('DB_PREFIX');
            $Bconfig = require C("APP_ROOT")."Conf/borrow_config.php";

            //发表信息开始
            $borrowinfo = M("borrow_info bi")->field('bi.*,ac.title,ac.id as aid')->join('lzh_article ac on ac.id= bi.danbao')->where('bi.id='.$id)->find();
            if(!is_array($borrowinfo) || ($borrowinfo['borrow_status']==0 && $this->uid!=$borrowinfo['borrow_uid']) ) $this->error("数据有误");
            $borrowinfo['biao'] = $borrowinfo['borrow_times'];
            $borrowinfo['need'] = $borrowinfo['borrow_money'] - $borrowinfo['has_borrow'];
            $borrowinfo['lefttime'] =$borrowinfo['collect_time'] - time();
            $borrowinfo['progress'] = getFloatValue($borrowinfo['has_borrow']/$borrowinfo['borrow_money']*100,2);
            $this->assign("vo",$borrowinfo);
            //发表信息结束
            //会员信息start
            $memberinfo = M("members m")->field("m.id,m.customer_name,m.customer_id,m.user_name,m.reg_time,m.credits,fi.*,mi.*,mm.*")->join("{$pre}member_financial_info fi ON fi.uid = m.id")->join("{$pre}member_info mi ON mi.uid = m.id")->join("{$pre}member_money mm ON mm.uid = m.id")->where("m.id={$borrowinfo['borrow_uid']}")->find();
            $areaList = getArea();
            $memberinfo['location'] = $areaList[$memberinfo['province']].$areaList[$memberinfo['city']];
            $memberinfo['location_now'] = $areaList[$memberinfo['province_now']].$areaList[$memberinfo['city_now']];
            $memberinfo['zcze']=$memberinfo['account_money']+$memberinfo['back_money']+$memberinfo['money_collect']+$memberinfo['money_freeze'];
            $this->assign("minfo",$memberinfo);
            //获取借款者名称以及他的信用值 （借款者详细信息）（留着）end
            //帐户资金情况
            $this->assign("investInfo", getMinfo($this->uid,true));  //投资者信息...........................................
            $this->assign("mainfo", getMinfo($borrowinfo['borrow_uid'],true)); //借款者信息..................................
            $this->assign("capitalinfo", getMemberBorrowScan($borrowinfo['borrow_uid'])); //借款者借款信息列表.................
            //帐户资金情况
            $this->assign("Bconfig",$Bconfig);
            $this->display();
        }


    }
<?php
/**
 * Author: minister.xiang@gmail.com
 * Copyright (c) 2009-2015 http://www.lvmaque.com All rights reserved.
 * Date: 2015/6/18 15:38
 */
$time = date('Y-m-d H:i:s');

try{
    $mapT  = $tlist = $done = null;//几个cron变量可能会串
    $count = 0;
    /****************************募集期内标未满,满标计息自动流标，即投计息直投与理财状态改为还款中 修改 2015-05-21****************************/
    //流标返回
    $mapT = array();
    $mapT['collect_time'] = array("lt", time());
    $mapT['borrow_status'] = BorrowModel::BID_SINGLE_CONFIG_STATUS_VIEW_PASS;
    $tlist = M("borrow_info")->field("add_time,id,borrow_uid,borrow_type,borrow_status,borrow_money,first_verify_time,borrow_interest_rate,borrow_duration,repayment_type,collect_day,collect_time")
        ->where($mapT)
        ->select();
    if (!empty($tlist)) {
        foreach ($tlist as $key => $vbx) {
            $borrow_id = $vbx['id'];
            //流标
            $done = false;
            $borrowInvestor = D('borrow_investor');
            $binfo = M("borrow_info")->field("id,add_time,borrow_type,borrow_money,borrow_uid,borrow_duration,repayment_type,rate_type")->find($borrow_id);
            $investorList = $borrowInvestor->field('coupon_id,id,investor_uid,investor_capital')->where("borrow_id={$borrow_id}")->select();
            if ($binfo['borrow_type'] < BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID || $binfo['rate_type'] == BorrowModel::BID_CONFIG_RATE_TYPE_FULL_BORROW) {
                M('investor_detail')->where("borrow_id={$borrow_id}")->delete();
                if ($binfo['borrow_type'] == 1) $limit_credit = memberLimitLog($binfo['borrow_uid'], 12, ($binfo['borrow_money']), $info = "{$binfo['id']}号标流标");//返回额度
                $borrowInvestor->startTrans();

                $bstatus = 3;
                $upborrow_info = M('borrow_info')->where("id={$borrow_id}")->setField("borrow_status", $bstatus);
                //处理借款概要
                $buname = M('members')->getFieldById($binfo['borrow_uid'], 'user_name');
                
          //查询此标使用优惠券的情况，返还实际支付的金额，优惠券也一并返还
              $pre = C('DB_PREFIX');
              $coupon_items = M('expand_money')
                  ->field("id,uid,money")
                  ->where(array('loanno' => $borrow_id,'status'=>4))
                  ->select();
              $discount_money = 0; // 折扣金额

              if( !empty($coupon_items) ) {
                  //返还优惠券,过期时间再加上标的募集期时间
                  $diff_time = time() - $binfo['add_time'];
                  $sql = "update {$pre}expand_money set status=1,expired_time = expired_time+{$diff_time} where loanno={$borrow_id}";
                  if( !$borrowInvestor->execute($sql) ) {
                      $borrowInvestor->rollback();
                  }
              }   
                
                //处理借款概要
                if (is_array($investorList)) {
                    foreach ($investorList as $v) {
                              $discount_money=0;
                        NoticeSet('chk15', $v['investor_uid'], $borrow_id);//sss
                        $accountMoney_investor = M("member_money")->field(true)->find($v['investor_uid']);
                        if( !empty($coupon_items) ) {
                              foreach( $coupon_items as $val ) {
                                  if( $val['uid'] == $v['investor_uid'] && strpos($v['coupon_id'],$val['id']) ) {
                                      $discount_money = $val['money'];
                                      break;
                                  }
                              }
                          }
                        $datamoney_x['uid'] = $v['investor_uid'];
                        $datamoney_x['type'] = 8;
                        $datamoney_x['affect_money'] = $v['investor_capital'] - $discount_money;
                        $datamoney_x['account_money'] = ($accountMoney_investor['account_money'] + $datamoney_x['affect_money']);//投标不成功返回充值资金池
                        $datamoney_x['collect_money'] = $accountMoney_investor['money_collect'];
                        $datamoney_x['freeze_money'] = $accountMoney_investor['money_freeze'] - $datamoney_x['affect_money'] - $discount_money;
                        $datamoney_x['back_money'] = $accountMoney_investor['back_money'];

                        //会员帐户
                        $mmoney_x['money_freeze'] = $datamoney_x['freeze_money'];
                        $mmoney_x['money_collect'] = $datamoney_x['collect_money'];
                        $mmoney_x['account_money'] = $datamoney_x['account_money'];
                        $mmoney_x['back_money'] = $datamoney_x['back_money'];

                        //会员帐户
                        $_xstr = "募集期内标未满,流标";
                        $datamoney_x['info'] = "第{$borrow_id}号标" . $_xstr . "，返回冻结资金";
                        $datamoney_x['add_time'] = time();
                        $datamoney_x['add_ip'] = get_client_ip();
                        $datamoney_x['target_uid'] = $binfo['borrow_uid'];
                        $datamoney_x['target_uname'] = $buname;
                        $moneynewid_x = M('member_moneylog')->add($datamoney_x);
                        if ($moneynewid_x) $bxid = M('member_money')->where("uid={$datamoney_x['uid']}")->save($mmoney_x);
                    }
                } else {
                    $moneynewid_x = true;
                    $bxid = true;
                }

                if ($moneynewid_x && $bxid && $upborrow_info) {
                    $done = true;
                    $borrowInvestor->commit();
                } else {
                    $borrowInvestor->rollback();
                }
                if (!$done) continue;
                NoticeSet('chk11', $vbx['borrow_uid'], $borrow_id);
                $verify_info['borrow_id'] = $borrow_id;
                $verify_info['deal_info_2'] = '系统自动流标';
                $verify_info['deal_user_2'] = 0;
                $verify_info['deal_time_2'] = time();
                $verify_info['deal_status_2'] = 3;
                if ($vbx['borrow_status'] == BorrowModel::BID_SINGLE_CONFIG_STATUS_VIEW_PASS ) {
                    M('borrow_verify')->save($verify_info);
                }

                $vss = M("members")->field("user_phone,user_name")->where("id = {$vbx['borrow_uid']}")->find();
                //updateBinfo
                $newBinfo = array();
                $newBinfo['id'] = $borrow_id;
                $newBinfo['borrow_status'] = 3;
                $newBinfo['second_verify_time'] = time();
                $x = M("borrow_info")->save($newBinfo);
            } else {
                if( $binfo['rate_type'] == BorrowModel::BID_CONFIG_RATE_TYPE_IMMEDIATE ) {
                    $newBinfo['id'] = $borrow_id;
                    $newBinfo['borrow_status'] = BorrowModel::BID_SINGLE_CONFIG_STATUS_REPAYMENT;
                    $x = M("borrow_info")->save($newBinfo);
                    special_award($borrow_id);  //截标发放"一"系列奖励
                    $borrowInvestor->commit();
                }
            }
        }
    }
    $count = count($tlist);
    $msg = '"code":10000,"msg":"招标中过期状态守护完成","time":"'.$time.'","count":'.$count.'';
    Log::write($msg, 'info');
}catch (Exception $e) {
    $msg =  '"code":10001,"msg":"招标中过期状态守护失败","method": '. ACTION_NAME .'""error": '.$e->getMessage().'"time":"'.$time.'"';
    Log::write($msg);
}
<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * @TODO 易宝通知回调  <手机端> 
 * @author 元<yjqphp@163.com>
 */
class NotifyAction extends MCommonAction {
          var $notneedlogin = true;
          /**
           * @todo 个人会员注册通知
           * @author yuan <yjqphp@163.com>
           */
          public function bindNotify() {
                    import("ORG.Loan.Escrow");
                    $loan = new Escrow();
                    if ($loan->verifySign($_POST)) {
                              $data = xml_to_array($_POST["notify"]);
                              $str = $data['notify']['message'];
                              if ($data['notify']['code'] == '1') {
                                        if (M('escrow_account')->where("orders='{$data['notify']['requestNo']}'")->save(array('bind_status'=>1))) {
                                                  $user_data=M('escrow_account')->where("orders='{$data['notify']['requestNo']}'")->find();
                                                  $c = M('members_status')->where('uid='.$user_data['uid'])->find();
                                                  if ($c) {
                                                            $ids['id_status'] = 1;
                                                            $ids['phone_status'] = 1;
                                                            M('members_status')->where('uid='.$user_data['uid'])->save($ids);
                                                  } else {
                                                            $ids['id_status'] = 1;
                                                            $ids['phone_status'] = 1;
                                                            $ids['uid'] = $user_data['uid'];
                                                            M('members_status')->add($ids);
                                                  }
                                                  $str = "SUCCESS";
                                        }
                                        notifyMsg('绑定账号', $data, 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],$str);
                                        echo $str;
                                        exit;
                              }
                              notifyMsg('绑定账号', $data, 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],$str);
                    }
          }

          /**
           * @ TODO 充值通知
           * @author yuan <yjqphp@163.com>
           */
          public function chargeNotify() {
              $data = xml_to_array($_POST["notify"]);
              $url = 'test.txt';
              file_put_contents($url, json_encode($data));
              exit;
                    import("ORG.Loan.Escrow");
                    $loan = new Escrow();
                    if ($loan->verifySign($_POST)) {
                               $data = xml_to_array($_POST["notify"]);
                              $ResultCode = intval($data['notify']['code']);
                              $str = $data['notify']['message'];
                              $info = M('member_payonline')->where("loan_no='{$data['notify']['requestNo']}'")->find();
                              if ($ResultCode == '1') {  //成功 
                                        //快捷充值
                                        if($data['notify']['bizType']=='RECHARGE'){
                                                  if ($info['status'] == 1) {   //RechargeType
                                                            $str = 'SUCCESS';
                                                  } else {
                                                            $status = 1;
                                                            $updata = array(
                                                                      'status' => $status,
                                                                      'way'=>$data['notify']['payProduct'],
                                                            );
                                                            if ($status == 1 && memberMoneyLog($info['uid'], 3, bcsub($info['money'], $info['fee'], 2), "在线充值{$info['money']}元，手续费{$info['fee']}元")) {
                                                                      $str = "SUCCESS";
                                                            }
                                                            M("member_payonline")->where("loan_no='{$data['notify']['requestNo']}'")->save($updata); //核实成功，  
                                                            $status && $str = "SUCCESS";
                                                  }
                                                  notifyMsg('充值', $data, 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],$str);
                                        }
                                         //绑卡
                                         if($data['notify']['bizType']=='BIND_BANK_CARD'){
                                                  $uid=M('escrow_account')->where('platform='.$data['notify']['platformUserNo'])->getField('uid');
                                                  $borrowconfig = FS("Webconfig/borrowconfig");
                                                  $bbankname = $borrowconfig['BANK_NAME'];
                                                  $b=M('member_banks')->where('uid='.$uid)->find();
                                                  $bank_info['loan_no'] = text($data['notify']['requestNo']);
                                                  $bank_info['bank_status'] = ($data['notify']['cardStatus']=='VERIFYING') ? 0 : $data['notify']['cardStatus']=='VERIFIED' ? 1:3 ;
                                                  $bank_info['bank_code'] = $data['notify']['bank'];
                                                  $bank_info['bank_name'] = $bbankname[$data['notify']['bank']];
                                                  $bank_info['uid'] = $uid;
                                                  $bank_info['bank_num'] = text($data['notify']['cardNo']) ;
                                                  $data['add_ip'] = get_client_ip();
                                                  $data['add_time'] = time();
                                                  if(!$b){
                                                            $res=M('member_banks')->add($bank_info);
                                                            $res && $str='SUCCESS';
                                                  }else{
                                                            $res=M('member_banks')->save($bank_info);
                                                            $res && $str='ERROR';
                                                  }   
                                                  notifyMsg('快捷绑卡', $data, 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $str);
                                         }
                              }
                              echo $str;
                              exit;
                    }
                    notifyMsg('充值', $data, 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $str);
          }

          /**
           * @TODO 绑定银行卡通知
           * @author yuan <yjqphp@163.com>
           */
          public function addBankNotify() {
                    import("ORG.Loan.Escrow");
                    $loan = new Escrow();
                    if ($loan->verifySign($_POST)) {
                              $data = xml_to_array($_POST["notify"]);
                              $ResultCode = intval($data['notify']['code']);
                              $str = $data['notify']['message'];
                              if ($ResultCode == '1') {  //成功 
                                        $uid=M('escrow_account')->where('platform='.$data['notify']['platformUserNo'])->getField('uid');
                                        $borrowconfig = FS("Webconfig/borrowconfig");
                                        $bbankname = $borrowconfig['BANK_NAME'];
                                        $b=M('member_banks')->where('loan_no='.$data['notify']['requestNo'].' and uid='.$uid)->find();
                                        $bank_info['loan_no'] = $data['notify']['requestNo'];
                                        $bank_info['bank_status'] =  ($data['notify']['cardStatus']=='VERIFYING') ? 0 : $data['notify']['cardStatus']=='VERIFIED' ? 1:3 ;
                                        $bank_info['bank_code'] = $data['notify']['bank'];
                                        $bank_info['bank_name'] = $bbankname[$data['notify']['bank']];
                                        $bank_info['uid'] = $uid;
                                        $bank_info['bank_num'] =  txet($data['notify']['cardNo']) ;
                                        $data['add_ip'] = get_client_ip();
                                        $data['add_time'] = time();
                                        if(!$b){
                                                  $res=M('member_banks')->add($bank_info);
                                                  $str='SUCCESS';
                                        }else{
                                                  $res=M('member_banks')->save($bank_info);
                                                  $str='ERROR';
                                        }
                                        
                              }
                              notifyMsg('绑定银行卡', $data, 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $str);
                              echo $str;
                              exit;
                    }
                    notifyMsg('绑定银行卡', $data, 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '签名错误');
          }
          /**
           * @TODO 还款转账确认
           * @author yuan <yjqphp@163.com>
           */
          public function repaymentNotify() {
                     import("ORG.Loan.Escrow");
                    $loan = new Escrow();
                    if ($loan->verifySign($_POST)) {
                              $data = xml_to_array($_POST["notify"]);
                              $ResultCode = intval($data['notify']['code']);
                              $str = $data['notify']['message'];
                              if ($ResultCode == '1' ) {  //成功
                                        if($data['notify']['status']=='CONFIRM'){
                                                  //还款确认
                                                  $order= explode('_', $data['notify']['requestNo']); 
                                                  $bid=M('borrow_info')->field('id,has_pay')->where("id='{$order[1]}'")->find();
                                                  if($binfo['has_pay']>=$order[2]){
                                                               $str = "SUCCESS"; 
                                                  }else{
                                                            $re = borrowRepayment($bid, $order[2],$order[3]);
                                                            if ($re===true){
                                                                      $str = "SUCCESS"; 
                                                            }else{ 
                                                                      $str = "ERROR"; 
                                                            }
                                                  }
                                        }else{  
                                                  //还款取消
                                               $str = "SUCCESS";    
                                        }
                                  
                              }
                              notifyMsg('还款确认', $data, 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $str);
                              echo $str;
                              exit;    
                    }else{
                              notifyMsg('还款确认', $data, 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '签名错误');
                    }
          }
          /**
           * @todo 转账授权通知（正常还款）
           */
          public function rapayWebNotify() {
                   import("ORG.Loan.Escrow");
                    $loan = new Escrow();
                    if ($loan->verifySign($_POST)) {
                              $data = xml_to_array($_POST["notify"]);
                              $ResultCode = intval($data['notify']['code']);
                              $str = $data['notify']['message'];
                              if ($ResultCode == '1' ) {  //成功
                                         //还款确认
                                        $order= explode('_', $data['notify']['requestNo']); 
                                        $arr['order_no']=$data['notify']['requestNo'];
                                        $arr['repay_status']=2;
                                        $res=M('investor_detail')->where("borrow_id={$order[1]} and sort_order={$order[2]} and bao_pay=1")->save($arr);
                                        if(!$res){
                                                  $str = "SUCCESS";
                                        }else{
                                                  $str="ERROR";
                                        }    
                              }
                              notifyMsg('还款冻结', $data, 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $str);
                              echo $str;
                              exit;    
                    }else{
                              notifyMsg('还款冻结', $data, 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '签名错误');
                    }  
          }
          
          /**
           * @TODO   提现通知
           */
          public function withdrawNotify() {
              /*
                platformNo商户编号
                bizType固定值WITHDRAW
                code【见返回码】
                message描述信息
                requestNo请求流水号
                platformUserNo用户编号
                cardNo绑定的卡号
                bank【见银行代码】
               */
                    import("ORG.Loan.Escrow");
                    $loan = new Escrow();
                    if ($loan->verifySign($_POST)) {
                              $data = xml_to_array($_POST["notify"]);
                              $ResultCode = intval($data['notify']['code']);
                              $str = $data['notify']['message'];
                              $orders = $data['notify']['requestNo'];
                              $info = M('member_withdraw')->where("orders='{$orders}'")->find();
                              if ($ResultCode == '1') {  //成功 
                                        if ($info['status'] == 2) { 
                                                  $str = 'SUCCESS';
                                        } else {
                                                  $status = 2;
                                                  $updata = array(
                                                            'withdraw_status' => $status,
                                                            'withdraw_fee' => $data['notify']['fee'],
                                                            'bank_num' => $data['notify']['bankCardNo'],
                                                            'bank' => $data['notify']['bank'],
                                                            'success_money'=>$data['notify']['amount'],
                                                  );
                                                  if($data['notify']['feeMode']=='USER'){
                                                          $money=  $data['notify']['amount']+$data['notify']['fee'];  //实际扣除金额
                                                  }else{
                                                          $money=  $data['notify']['amount'];    
                                                  }
                                                  if ($status ==2 && memberMoneyLog($info['uid'],29,-$money, "提现成功,到账金额".$data['notify']['amount']."元",'0','@网站管理员@')) {
                                                            $str = "SUCCESS";
                                                  }
                                                 M('member_withdraw')->where("orders='{$orders}'")->save($updata); //核实成功，  
                                                 $status && $str = "SUCCESS";
                                        }
                              }
                              notifyMsg('提现', $data, 'http://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $str);
                              if($str=='SUCCESS'){
                                   MTip('chk6',$info['uid']);     
                              }
                              echo $str;
                              exit;
                    }
                    notifyMsg('提现', $data, 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $str);
          }
          /**
           * @TODO 自动投标授权通知
           */
          public function authorizeNotify() {
                     import("ORG.Loan.Escrow");
                    $loan = new Escrow();
                    if ($loan->verifySign($_POST)) {
                              $data = xml_to_array($_POST["notify"]);
                              $ResultCode = intval($data['notify']['code']);
                              $str = $data['notify']['message'];
                              $orders = $data['notify']['platformUserNo'];
                              $info = M('escrow_account')->where("platform='{$orders}'")->find();
                              if ($ResultCode == '1') {  //成功 
                                        if ($info['auto_invest_auth'] == 1) { 
                                                  $str = 'SUCCESS';
                                        } else {
                                                  $status = 1;
                                                  $updata = array(
                                                            'auto_invest_auth' => $status,
                                                  );
                                                  if ($status ==1 && M('escrow_account')->where("platform='{$orders}'")->save($updata)) {
                                                            $str = "SUCCESS";
                                                  }
                                        }
                              }
                              notifyMsg('自动投标授权', $data, 'http://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $str);
                              echo $str;
                              exit;
                    }
                    notifyMsg('自动投标授权', $data, 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $str); 
          }

}

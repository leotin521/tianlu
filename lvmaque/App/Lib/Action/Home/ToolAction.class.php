<?php
// 本类由系统自动生成，仅供测试用途
class ToolAction extends HCommonAction {
    /**
     * 借款计算器、投资计算器
     * 起息日期，还款日期，计息天数
     */
    public function index(){
        $id = $_GET['id'] ? $_GET['id'] : $_POST['id'];
        $type_name = $id ? "投资" : "借款";
        $borrowClass = $id ? "tz" : "jk";
        $investClass = $id ? "jk" : "tz";

        $this->assign('type_name', $type_name);
        $this->assign('type', $id);
        if($_POST){
            $amount = round(floatval($_POST['amount']),4);//借款金额
            $borrow_duration = intval($_POST['date_limit']);//借款期限
            $rate = floatval($_POST['rate']);			//借款年利率
            $reward_rate = floatval($_POST['reward_rate']);//借款奖励

            //$risk_reserve = floatval($_POST['risk_reserve']);//风险准备金
            $borrow_manage = floatval($_POST['borrow_manage']);//借款/利息管理费率

            $date_type = (intval($_POST['date_type'])==2) ? 0 : 1;//投资期限单位：1：月；2：日

            if( $date_type == 1 ) {
                $duration_days = getDaysByMonth($borrow_duration); // 计息天数
                $fee_rate = $borrow_manage;
            } else {
                $duration_days = $borrow_duration;
                $fee_rate = bcdiv($borrow_manage, 30, 2);
            }
            $repay_detail = array();
            $repayment_type = $_POST['repayment_type'];
            $repay_detail['risk_reserve'] = 0;//round($amount*$risk_reserve/100,4);//风险准备金
            $repay_detail['borrow_manage'] = round($amount*$fee_rate*$borrow_duration/100,2);//借款管理费

            $repay_detail['reward_money'] = round($amount*$reward_rate/100,2);//奖励

            switch ($repayment_type) {
                case '1'://按天到期还款
                    $result = EqualEndMonthOnly(
                        array(
                            'month_times' => $borrow_duration,
                            'account' => $amount,
                            'year_apr' => $rate,
                            'type' => 'all'
                        ),
                        false
                    );
                    $_result[] = $result;
                    break;
                case '4'://按天计息每月还息到期还本息
                    $_result = EqualEndMonth(
                        array(
                            'duration' => $borrow_duration,
                            'account' => $amount,
                            'year_apr' => $rate
                        ),
                        $date_type
                    );
                    break;
                case '5':
                    $result = EqualEndMonthOnly(
                        array(
                            'month_times' => $borrow_duration,
                            'account' => $amount,
                            'year_apr' => $rate,
                            'type' => 'all'
                        ),
                        $date_type
                    );
                    $_result[] = $result;
                    break;
                case '2'://按月等额分期还款
                default:
                    $_result = EqualMonth(
                        array(
                            'money' => $amount,
                            'duration' => $borrow_duration,
                            'year_apr' => $rate
                        ),
                        false
                    );
                    break;
                case '6'://利息复投
                    $result = CompoundMonths(
                        array(
                            'account' => $amount,
                            'month_times'   => $borrow_duration,
                            'year_apr'  => $rate,
                            'type'  =>  'all'
                        )
                    );
                    $_result[] = $result;
                    break;
            }

            if( $repayment_type == BorrowModel::BID_SINGLE_CONFIG_REPAY_TYPE_MONTH ) { // 等额本息不是按天计息，其它都按天计息
                $equalData = EqualMonth(
                    array(
                        'money' => $amount,
                        'duration' => $borrow_duration,
                        'year_apr' => $rate,
                        'type' => 'all'
                    )
                );
                $repay_detail['interest'] = bcsub($equalData['repayment_money'], $amount, 2);
                if( !empty($id) ) { // 投资
                    $repay_detail['borrow_manage'] = bcdiv(bcmul($repay_detail['interest'], $borrow_manage, 2), 100, 2);
                    $repay_detail['extra_money'] = $repay_detail['reward_money'] - $repay_detail['borrow_manage'];
                } else {
                    $repay_detail['extra_money'] = $repay_detail['risk_reserve'] + $repay_detail['borrow_manage'] + $repay_detail['reward_money'];
                }
                $repay_detail['total_interest'] = bcadd(bcsub($equalData['repayment_money'], $amount, 4), $repay_detail['extra_money'], 2);
                $repay_detail['repayment_money'] = bcadd($equalData['repayment_money'], 0, 2);
            } elseif($repayment_type == BorrowModel::BID_SINGLE_CONFIG_REPAY_TYPE_RECAST) {
                $compound_result = $_result[0];
                $repay_detail['interest'] = bcadd($compound_result['interest'], 0, 2);
                if( !empty($id) ) { // 投资
                    $repay_detail['borrow_manage'] = bcdiv(bcmul($repay_detail['interest'], $borrow_manage, 2), 100, 2);
                    $repay_detail['extra_money'] = $repay_detail['reward_money'] - $repay_detail['borrow_manage'];
                } else {
                    $repay_detail['extra_money'] = $repay_detail['risk_reserve'] + $repay_detail['borrow_manage'] + $repay_detail['reward_money'];
                }
                $repay_detail['total_interest'] = bcadd(bcsub($compound_result['repayment_account'], $amount, 4), $repay_detail['extra_money'], 2);
                $repay_detail['repayment_money'] = bcadd($compound_result['repayment_account'], 0, 2);
            }
            else {
                $repay_detail['interest'] = bcdiv(bcmul(bcmul($amount, $duration_days, 6),$rate, 6), 36500, 2);
                if( !empty($id) ) { // 投资
                    $repay_detail['borrow_manage'] = bcdiv($repay_detail['interest']*$borrow_manage, 100, 4);
                    $repay_detail['extra_money'] = $repay_detail['reward_money'] - $repay_detail['borrow_manage'];
                } else {
                    $repay_detail['extra_money'] = $repay_detail['risk_reserve'] + $repay_detail['borrow_manage'] + $repay_detail['reward_money'];
                }
                $repay_detail['total_interest'] = bcadd($repay_detail['interest'], $repay_detail['extra_money'], 2);
                $repay_detail['repayment_money'] = bcadd($amount , $repay_detail['interest'], 2);
            }

            $repay_detail['year_apr'] = $rate;
            $repay_detail['month_apr'] = bcdiv($rate, 12, 2);
            $repay_detail['day_apr'] = bcdiv($rate, 365, 2);


            $this->assign('repayment_type',$repayment_type);
            $this->assign('month',$borrow_duration);
            if( !empty($_result) && is_array($_result) ) {
                for( $i=0;$i<count($_result); $i++) {
                    if(!isset($_result[$i]['repayment_money'])) {
                        $_result[$i]['repayment_money'] = bcadd($_result[$i]['repayment_account'], 0, 2);
                    }
                    $_result[$i]['repayment_money'] = bcadd($_result[$i]['repayment_money'], 0, 2);
                    $_result[$i]['interest'] = bcadd($_result[$i]['interest'], 0, 2);
                }
            }
            $this->assign('repay_list',$_result);
            $this->assign('repay_detail',$repay_detail);
            $this->assign('amount',$amount);
            $this->assign('duration_days',$duration_days);
            $data['html'] = $this->fetch('index_res');
            exit(json_encode($data));
        }

        //网站公告
        $parm['type_id'] = 321;
        $parm['limit'] = 8;
        $repayment_items = BorrowModel::get_business_repay_type();
        $repayment_items[6] = "利息复投";
        $repayment_all = BorrowModel::get_repay_type($type = null, TRUE);
        $this->assign('repayment_items',$repayment_items);
        $this->assign('repayment_all',$repayment_all);
        $this->assign('repaymentObj', json_encode($repayment_items));
        $leftlist = array(1=>array("id"=>"{$investClass}","turl"=>"/tools/id/1.html","type_name"=>"投资计算器"),0=>array("id"=>"{$borrowClass}","turl"=>"/tools/tool.html","type_name"=>"借款计算器"));
        $this->assign("leftlist",$leftlist);
        $this->assign("cid","jk");
        $this->display();
    }

    /**
     * @author yuan<yjqphp@163.com>
     * @version 1.0 (各种还款方式需后期完善，目前只支持“按天计息，每月还息，到期还本”的方式)
     * @todo 快速计算投标收益(忽略掉了利息管理费和借款管理费)
     * @param array $data         //计算参数方式两种选择  i（投资期限，年化利率，投资金额，期限单位）  ii>(标id，投资金额)
     * @param type $repayment
     * @param array $result 利息，总收益，奖金
     */
    public function quickCountRate($data= array(),$repayment=4) {
        if(empty($data)){
            $borrow_id=  intval($_REQUEST['borrow_id']);
            $data =M('borrow_info')->where('id='.$borrow_id)->find();
            if( !empty($data) ) $repayment = $data['repayment_type'];
            $data['amount']=  floatval($_REQUEST['money']);
        }
        $repay_detail = false;
        //按天计息每月还息到期还本息
        if( $repayment == BorrowModel::BID_SINGLE_CONFIG_REPAY_TYPE_MONTH ) { // 等额本息不是按天计息，其它都按天计息
            $_result = EqualMonth(
                array(
                    'money' => $data['amount'],
                    'duration' => $data['borrow_duration'],
                    'year_apr' => $data['borrow_interest_rate'],
                    'type' => 'all'
                )
            );
            $repay_detail['interest'] = $_result['repayment_money'] - $data['amount'];
        } else {
            if(   $data['duration_unit']== 1 ) {
                $duration_days = getDaysByMonth($data['borrow_duration']); // 计息天数
            } else {
                $duration_days = $data['borrow_duration'];
            }
            $repay_detail['interest'] = bcdiv(bcmul(bcmul($data['amount'], $duration_days, 6),$data['borrow_interest_rate'], 6), 36500, 2);  //利息
        }
        $repay_detail['reward_money'] = round($data['amount']*$data['reward_num']/100,2);
        $repay_detail['total_interest'] = $repay_detail['reward_money'] + $repay_detail['interest']; //总收益

        exit(json_encode($repay_detail));
    }

}
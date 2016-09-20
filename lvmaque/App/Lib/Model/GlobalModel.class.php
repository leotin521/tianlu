<?php
// +----------------------------------------------------------------------
// | ThinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2008 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

// 管理用户模型
class GlobalModel extends ACommonModel {

    /**
     *  回款投标自动奖励
     *  以1|1.5|2的形式填入，表示回款续投一月标奖励1‰回款续投二月标奖励1.5‰ 回款续投三月标及以上奖励2‰，如果投标金额大于回款资金池金额，有效续投奖励以回款金额资金池总额为标准，否则以投标金额为准
     */
    const GLOBAL_TODAY_REWARD = 'today_reward';
    public static $limit = '1_2'; // min_limit 每分钟多少次

	protected $_validate	=	array(
			array('code','',"参数代码不能为空",0,'unique',self::MODEL_INSERT),
			array('name','',"参数名称不能为空",0,'unique',self::MODEL_INSERT),
		);

    public function checkBindAccount() {
        $map['id']   = array('neq',$_POST['id']);
        $map['bind_account']    = $_POST['bind_account'];
        if($this->where($map)->find()) {
            return false;
        }
        return true;
    }

	protected function pwdHash() {
		if(isset($_POST['password'])) {
			return pwdHash($_POST['password']);
		}else{
			return false;
		}
	}
    /**
     * 验证借款期限是否在配置文件有效时间内
     * @param $repay_type  int 还款类型
     * @param $duration int 借款期限
     * @param $p2c int 是否为p2c,p2c与p2p中，还款方式不一样，$duration也可能是月或者天
     * @return bool
     * @throws Exception
     */
    static public function validate_bid_duration($repay_type, $duration, $p2c = false)
    {
        $globals = get_global_setting();
        $ret = false;
        try {
            if( $repay_type == BorrowModel::BID_SINGLE_CONFIG_REPAY_TYPE_DAY ) {  // 天标
                $config = explode("|",$globals['borrow_duration_day']); // 按天计算区间
            } else {
                $config = explode("|",$globals['borrow_duration']); // 按月期限区间
                // 如果为p2c
                if( $p2c == true ) {
                    $duration_unit = get_global_setting('duration_unit'); // p2c借款期限单位
                    if( $duration_unit == BorrowModel::BID_CONFIG_DURATION_UNIT_DAY) {
                        $config = array(
                            $config[0],
                            $config[1]*30
                        );
                    }
                }
            }

            if( !empty($config) && is_array($config) ) {
                if( $duration >= $config[0] && $duration <= $config[1]) {
                    $ret = true;
                }
            }
        } catch (Exception $e){
            throw $e;
        }

        return $ret;
    }

    /**
     * 回款奖励时，按天计算的如果在30天以内，用原天标的计算方式收取管理费
     * @param $repayment_type int 还款类型
     * @param $duration  int 借款期限
     */
    static public function get_today_reward($repayment_type, $duration)
    {
        $globals = get_global_setting();
        $today_reward = explode('|', $globals['today_reward']);
        $ret = false;
        try {
            $reward_rate = 0;
            if( $repayment_type == BorrowModel::BID_SINGLE_CONFIG_REPAY_TYPE_DAY ) {
                return $reward_rate;
            }
            if(in_array($repayment_type, BorrowModel::repayment_by_dayInterest())){//如果是天标，则执行1个月的续投奖励利率
                if( $duration < 31 ) { // 小于31天，按原天标收取管理费
                    $reward_rate = floatval($today_reward[0]);
                    return $reward_rate;
                }
                $duration = ceil($duration/30);
            }

            if($duration==1){
                $reward_rate = floatval($today_reward[0]);
            }else if($duration==2){
                $reward_rate = floatval($today_reward[1]);
            }else{
                $reward_rate = floatval($today_reward[2]);
            }

            $ret = $reward_rate;
        } catch (Exception $e){
            throw $e;
        }

        return $ret;
    }

    /**
     * 根据配置文件获得借款期限的单位是天还是月
     */
    public static function get_format_duration_unit()
    {
        return self::get_duration_unit() == 1 ? '天' : '个月';
    }

    /**
     * 判断借款期限单位
     */
    public static function get_duration_unit()
    {
        return get_global_setting('duration_unit');
    }

    /**
     * 发送短信限制
     * @param $uid
     * @return bool
     */
    public static function send_msg_limit($uid=null)
    {
        $ret = false;
        //if( $uid > 0 ) {
            $time = time();
            if( empty($_SESSION['code_limit']) ) {
                $_SESSION['code_limit'] = '1_'. $time;
                $ret = true;
            }else{
                $limit = explode('_', self::$limit);
                $limit_min = $limit[0];
                $limit_num = $limit[1];
                $has_send = explode('_', $_SESSION['code_limit']);
                $min = ceil(($time - $has_send[1])/60);
                if( $min <= $limit_min ) {
                    if( $has_send[0] < $limit_num ) {
                        $now_num = $has_send[0] + 1;
                        $_SESSION['code_limit'] = $now_num.'_'.$has_send[1];
                        $ret = true;
                    }else{
                        return $ret;
                    }
                }else{
                    $_SESSION['code_limit'] = '1_'. $time;
                    $ret = true;
                }
            }
        //}
        return $ret;
    }
}
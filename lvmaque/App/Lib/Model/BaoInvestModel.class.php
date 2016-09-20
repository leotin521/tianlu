<?php
/**
 * Class BaoInvestModel
 * 灵活宝投资记录表
 */
class BaoInvestModel extends ACommonModel {
	protected $tableName = '';

    /**
     * 用户已获得的所有利息总和
     * @param bool $dataLine  是否为折线图格式化输出
     * @return mixed
     */
    public static function get_sum_interest($user_id, $batch_no = false,  $dataLine = false)
    {
        if( !empty($dataLine) ) {
            // 需要输出 interest（当月利息）,capital（当月本金）,total(当月的利息和本金之和),time
            $fields = "interest,money as capital, (FROM_UNIXTIME(bi.deadline, '%Y') * 12 + FROM_UNIXTIME(bi.deadline, '%c')) AS time ";
            $interest = M('bao_invest bi')->field($fields)->where("uid = {$user_id} and bi.record_time<=".time())->group("FROM_UNIXTIME(bi.deadline, '%Y%m')")->order("bi.deadline ASC")->select();
        } else {
            $where['uid'] = $user_id;
            if( !empty($batch_no) ) {
                $where['batch_no'] = $batch_no;
            }
            $interest = M('bao_invest')->where($where)->sum('interest');
        }
        return $interest;
    }

    /**
     * 获得用户灵活宝资产总额
     */
    public static function get_sum_money($user_id)
    {
        $ret = M('bao_invest')->where(array('uid'=>$user_id))->sum('money');
        return $ret;
    }

    /**
     * 当前用户投资的所有灵活宝的待收收益总和
     * 当前本金，剩余天数，天利率计算复利
     * @param $user_id
     * @param bool $dataLine  是否为折线图格式化输出
     * @return mixed|array
     */
    public static function get_collect_money($user_id = false, $dataLine = false)
    {
        $ret = false;
        $pre = C('DB_PREFIX');
        if( !empty($user_id) ) {
            $where = "uid={$user_id} and deadline > ".time();
        }else {
            $where = "deadline > ".time();
        }

        if( !empty($dataLine) ) { //待收区分待收收益和待收本金 TODO:有问题，暂定，需要建立临时表将每月还款金额算出来，再根据自然月分类展示给前端
            // 需要输出 interest（当月利息）,capital（当月本金）,total(当月的利息和本金之和),time
            $invest_items = M('bao_invest bi')
                ->field("bi.money,bi.record_time,bi.deadline,b.interest_rate,(FROM_UNIXTIME(bi.deadline, '%Y') * 12 + FROM_UNIXTIME(bi.deadline, '%c')) AS time ")
                ->join("{$pre}bao b on b.batch_no = bi.batch_no")
                ->where($where)
                ->group("FROM_UNIXTIME(bi.deadline, '%Y%m')")
                ->order("bi.deadline ASC")
                ->select();
            foreach($invest_items as $val) {
                $diff_day = ceil(($val['deadline'] - $val['record_time'])/3600/24);
                $invest_items[$val]['interest'] = bcadd($ret['collect_interest'] + compound($val['money'], $val['interest_rate']/100/365, $diff_day) - $val['money'], 2); //转化成天利率
            }
            $ret = $invest_items;
        } else {
            $invest_items = M('bao_invest bi')
                ->field('bi.money,bi.record_time,bi.deadline,b.interest_rate')
                ->join("{$pre}bao b on b.batch_no = bi.batch_no")
                ->where($where)
                ->select();
            $ret['collect_interest'] =  0;
            $ret['collect_days'] =  0;
            foreach($invest_items as $val) {
                //还剩多少天，ceil原因是还息为下一天的0时0分01秒
                $diff_day = ceil(($val['deadline'] - $val['record_time'])/3600/24);
                $ret['collect_interest'] = bcadd($ret['collect_interest'] + compound($val['money'], $val['interest_rate']/100/365, $diff_day), 2)  - $val['money']; //转化成天利率
                $ret['collect_days'] += $diff_day;
            }
        }
        return $ret;
    }

    /**
     * 获取灵活宝的投资总额
     * @param $user_id
     * @param bool|string $batch_no int:单个标的投资总额， false:投资的所有灵活宝的投资总额，因为是利息复投，所以利息也算投资额，取最后一条数据
     */
    public static function get_invest_money($user_id, $batch_no = false) {
        $ret = false;
        $pre = C('DB_PREFIX');
        if( !empty($batch_no) ) { //单条记录
            //TODO: code here
        }else { // 统计总额
            $sql = "select funds from (select funds,batch_no from {$pre}bao_record where uid={$user_id} and status=1 order by id desc) as a group by batch_no ";
            $record = M()->query($sql);
            if( !empty($record) ) {
                $record = only_array($record, 'funds');
                $ret = array_sum($record);
            }
        }
        return $ret;
    }





}
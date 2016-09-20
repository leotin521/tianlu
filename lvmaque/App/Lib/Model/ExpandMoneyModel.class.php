<?php
// 优惠券模型
class ExpandMoneyModel extends ACommonModel {
    protected $tableName    = 'expand_money';

    const DB_FIELD_EXPAND_MONEY_IS_TASTE = 'is_taste';

    // 状态
    const LZH_EXPAND_MONEY_IS_TASTE_UNUSED = 1; // 未使用
    const LZH_EXPAND_MONEY_IS_TASTE_USED = 4; // 已使用

    // 是否是体验优惠券
    const LZH_EXPAND_MONEY_IS_TASTE_NO = 0;
    const LZH_EXPAND_MONEY_IS_TASTE_YES = 1;

    public static function get_coupon_type($type = null)
    {
        $result = false;
        $repay_type = array(
            self::LZH_EXPAND_MONEY_IS_TASTE_NO => '抵现券',
            self::LZH_EXPAND_MONEY_IS_TASTE_YES => '体验券'
        );
        if(isset($type)) {
            if( isset($repay_type[$type]) ) {
                $result = $repay_type[$type];
            }
        } else {
            $result = $repay_type;
        }
        return $result;
    }

    public static function get_coupons_byPage($where, $fields='*', $order=null, $group=null, $page=1, $limit=null, $getCount = null)
    {
        $ret = false;
        import('ORG.Util.Page');
        $count = M('expand_money')->where($where)->count('id');
        if( $count > 0 ) {
            if ($limit==null && $getCount == null){
                $limit = 5;
            }else{
                $limit = 100;
            }
            $p = new Page($count, $limit);
            $nowPage = ($page-1) * ($limit);
            $Lsql = "$nowPage,$limit";
            if( empty($order) ) $order = "id DESC";
            //分页处理
            $list = M('expand_money')
                ->field($fields)
                ->where($where)
                ->limit($Lsql)
                ->order($order)
                ->group($group)
                ->select();
            if( !empty($list) ) {
                $list = self::get_coupon_type($list);
            }
            $show = $p->show();
            $ret['data'] = $list;
            $ret['page'] = $show;
        }
    }

    /**
     * 格式化类型
     * @param $coupon_items
     */
    public static function get_coupon_type_format($coupon_items)
    {
        if( !empty($coupon_items) ) {
            for($i=0;$i<count($coupon_items);$i++) {
                $coupon_items[$i]['coupon_type'] = self::get_coupon_type($coupon_items[$i]['is_taste']);
            }
            return $coupon_items;
        }
    }


    /**
     * 查询一马当先，一鸣惊人，一锤定音的奖励
     * @param $borrow_id
     */
    public static function get_special_award($borrow_id)
    {
        $pre = C('DB_PREFIX');
        $ret = false;
        $where = array(
            'e.borrow_id' => $borrow_id,
        );
        $special_award = M('expand_money e')->field('e.uid,e.add_time,sum(e.money) as money,e.type,i.investor_capital  as invest_money')
            ->join("{$pre}borrow_investor i ON i.id=e.invest_id")
            ->group('e.type')->where($where)->order('`type` asc')->select();
        if( !empty($special_award) ) {
            $binfo=M('borrow_info')->field('borrow_name')->where('id='.$borrow_id)->find();
            $uids = only_array($special_award, 'uid');
            if( !empty($uids) ) {
                $user_name = M('members')->field('user_name,id')->where(array('id'=>array('in', implode(',', $uids))))->select();
                if( !empty($user_name) ) {
                    foreach( $special_award as $k=>$v ) {
                        foreach($user_name as $val) {
                            if( $val['id'] == $v['uid'] ) {
                                $special_award[$k]['user_name'] = hidecard($val['user_name'],5);
                                break;
                            }

                        }
                        for($i=0;$i<count($special_award); $i++) {
                            $ret[$special_award[$i]['type']] = $special_award[$i];
                            $ret[$special_award[$i]['type']]['borrow_name'] = hidecard($binfo['borrow_name'],5);
                        }

                    }
                }
            }
        }
        return $ret;
    }

    /**
     * @param array $coupon_ids 优惠券ids
     * @param $money
     * @param $uid
     * @return bool
     */
    public static function get_discount_money($coupon_ids, $money, $uid, $is_taste=0)
    {
        $ret = false;
        if( !empty($coupon_ids) && $money > 0 ) {
            $discount_money = $taste_money =  0; //折扣金额，体验金额
            $coupon_items = M('expand_money')
                ->field('money, invest_money, expired_time, status, is_taste')
                ->where(array('id'=>array('in',$coupon_ids),'uid'=>$uid,'is_taste'=>$is_taste))
                ->select();
            // 只要有一个优惠券不能使用，则认为是非法请求
            foreach( $coupon_items as $val ) {
                if( $val['invest_money'] > $money || $val['expired_time'] < time() || $val['status'] == 4 ) {
                    return $ret;
                }else {
                    $discount_money += $val['money'];
                    if( $val['is_taste'] == ExpandMoneyModel::LZH_EXPAND_MONEY_IS_TASTE_YES ) {
                        $taste_money += $val['money'];
                    }
                }
            }
            $ret['discount_money'] = $discount_money;
            $ret['taste_money'] = $taste_money;
        }
        return $ret;
    }
}
?>
<?php
/**
 * Author: minister.xiang@gmail.com
 * Copyright (c) 2009-2015 http://www.lvmaque.com All rights reserved.
 * Date: 2015/9/9 14:11
 *
 * Ͷ积分模型
 */
class IntegrationModel extends ACommonModel {

    //投资积分类型
    const MEMBER_INTEGRALLOG_TYPE_EXCHANGE = 1; //兑换优惠券
    const MEMBER_INTEGRALLOG_TYPE_BID = 2; //投标奖励
    const MEMBER_INTEGRALLOG_TYPE_REPAYMENT = 20; //提前还款奖励
    //信用积分类型
    const MEMBER_CREDITSLOG_TYPE_EMAIL = 9; //邮箱认证
    const MEMBER_CREDITSLOG_TYPE_PHONE = 10; //手机认证
    const MEMBER_CREDITSLOG_TYPE_NAME = 2; //实名认证
    const MEMBER_CREDITSLOG_TYPE_QUESTION = 6; //安全问题认证
    const MEMBER_CREDITSLOG_TYPE_PERSON_LOAN = 33; //个人借款审核
    const MEMBER_CREDITSLOG_TYPE_COMPANY_LOAN = 34; //企业借款审核
    /**
     * 获得投资积分类型
     * @param null $type
     * @return array|bool
     */
    public static function get_integra_type($type = null)
    {
        $result = false;
        $invest_type = array(
            self::MEMBER_INTEGRALLOG_TYPE_EXCHANGE => '兑换优惠券',
            self::MEMBER_INTEGRALLOG_TYPE_BID => '投标奖励',
            self::MEMBER_INTEGRALLOG_TYPE_REPAYMENT => '提前还款奖励',
        );
        if(isset($type)) {
            if( isset($invest_type[$type]) ) {
                $result = $invest_type[$type];
            }
        } else {
            $result = $invest_type;
        }
        return $result;
    }
    /**
     * 获得信用积分类型
     * @param null $type
     * @return array|bool
     */
    public static function get_credit_type($type = null)
    {
        $result = false;
        $invest_type = array(
            self::MEMBER_CREDITSLOG_TYPE_EMAIL => '邮箱认证',
            self::MEMBER_CREDITSLOG_TYPE_PHONE => '手机认证',
            self::MEMBER_CREDITSLOG_TYPE_NAME => '实名认证',
            self::MEMBER_CREDITSLOG_TYPE_QUESTION => '安全问题认证',
            self::MEMBER_CREDITSLOG_TYPE_PERSON_LOAN => '个人借款审核',
            self::MEMBER_CREDITSLOG_TYPE_COMPANY_LOAN => '个人借款审核',
        );
        if(isset($type)) {
            if( isset($invest_type[$type]) ) {
                $result = $invest_type[$type];
            }
        } else {
            $result = $invest_type;
        }
        return $result;
    }
}
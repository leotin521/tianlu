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

// 会员模型
class MembersModel extends ACommonModel {
	protected $tableName = 'members';
    const LENGTH_NUM = 10000; // 不可更改

    //用户类型 1:普通会员 2,优良借款者 3,风险借款者  4,黑名单
    const MEMBERS_USER_TYPE_NORMAL = 1;

    // 0默认，1代表是企业借款会员 2：个人借款会员
    const MEMBERS_IS_TRANSFER_NORMAL = 0;
    const MEMBERS_IS_TRANSFER_BUSINESS = 1;
    const MEMBERS_IS_TRANSFER_PERSONAL = 2;

    //用户个人借款是否验证 0，未申请或驳回 1，已上传待审核 2，审核通过
    const MEMBERS_USER_VALIDATE_USER_TYPE_INIT = 0;
    const MEMBERS_USER_VALIDATE_USER_TYPE_WAIT_VIEW = 1;
    const MEMBERS_USER_VALIDATE_USER_TYPE_PASS = 2;

    public static function get_user_type($type = null, $borrow_type=null)
    {
        $result = false;
        $repay_type = array(
            self::MEMBERS_IS_TRANSFER_NORMAL => '普通会员',
        );
        $version = FS("Webconfig/version");
        if( $version['single'] == 1 ) {
            $repay_type[self::MEMBERS_IS_TRANSFER_PERSONAL] = '个人借款者';
        }
        if( $version['business'] == 1 || $version['fund'] == 1 ) {
            $repay_type[self::MEMBERS_IS_TRANSFER_BUSINESS] = '企业借款者';
        }
        if( isset($borrow_type) ) {
            unset($repay_type[self::MEMBERS_IS_TRANSFER_NORMAL]);
        }
        if(isset($type)) {
            if( isset($repay_type[$type]) ) {
                $result = $repay_type[$type];
            }
        } else {
            $result = $repay_type;
        }
        return $result;
    }

    /**
     * 跳转到未登录首页
     */
    public static function unlogin_home()
    {
        $url = self::unlogin_url();
        header('Location: ' . $url);
        exit;
    }

    public static function unlogin_url()
    {
        $old_url = rawurlencode(DOMAIN . $_SERVER['REQUEST_URI']);
        $url = DOMAIN . '/login?redirectUrl=' . $old_url;
        return $url;
    }

    public static function get_user_Encryption($user_id)
    {
        $ret = false;
        if( !empty($user_id) ) {
            $enc_num = self::LENGTH_NUM + $user_id;
            $ret = dec2Any($enc_num, 35);
        }
        return $ret;
    }

    public static function get_Decrypt_uid($string)
    {
        $ret = false;
        if( !empty($string) ) {
            $ret = any2Dec($string, 35) - self::LENGTH_NUM;
        }
        return $ret;
    }

    // 安全等级
    public static function get_safe_process($user_id) {
        $pre = C('DB_PREFIX');
        $ret = M("members m")
            ->field("m.id,m.user_name,m.user_email,m.is_transfer,s.id_status,s.phone_status,s.email_status,s.safequestion_status,m.user_phone,m.pin_pass,mi.em_name")
            ->join("{$pre}members_status s ON s.uid=m.id")
            ->join("{$pre}member_info mi ON mi.uid=m.id")
            ->where("m.id=$user_id")
            ->find();
        $process = 0;
        if( !empty($ret) ) {
            if( $ret['phone_status'] == 1 ) $process += 1;
            if( $ret['id_status'] == 1 ) $process += 1;
            if( $ret['email_status'] == 1 ) $process += 1;
            if( !empty($ret['pin_pass']) ) $process += 1;
            if( $ret['safequestion_status'] == 1 ) $process += 1;
            if( !empty($ret['em_name']) ) $process += 1;
        }
        return ceil($process/6*100);
    }

    public static function get_safe_rand($process)
    {
        $ret = '';
        $num = ceil($process);
        if( $num > 66 ) {
            $ret = '高';
        }elseif( $num >= 33 ) {
            $ret = '中';
        } else {
            $ret = '低';
        }
        return $ret;
    }
}
?>
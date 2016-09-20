<?php

// 信用积分模型
class MemberCreditsLogModel extends ACommonModel {

    /**
     * 企业直投列表分页显示
     * ~~
     * i>认证送的是信用积分,迟还，逾期扣除信用积分
     * ~~
     * @param bool|array $where  条件参数
     * @param string $fields   字段
     * @param int $page  分页页数
     * @param bool $getCount  是否分页/保留条件，暂不用
     * @param bool $count  /保留条件
     * @param bool $orderBy  /保留条件
     * @return bool
     * @throws Exception
     */
    public static function getCreditsLogByPage($where = false, $fields = '*', $page = 1)
    {
        $ret = false;
        try {
            $pre = C('DB_PREFIX');
            //分页处理
            import("ORG.Util.Page");
            $count = M('member_creditslog')->where($where)->count('id');
            if( $count > 0 ) {
                $p = new Page($count, C('ADMIN_PAGE_SIZE'));
                $nowPage = ($page-1) * ($p->listRows);

                $Lsql = "$nowPage,{$p->listRows}";
                //分页处理
                $list = M('member_creditslog')->field($fields)
                    ->where($where)->limit($Lsql)->order("id DESC")->select();
                $show = $p->show();
                $ret['data_items'] = $list;
                $ret['page'] = $show;
            }
        } catch( Exception $e ) {
            throw $e;
        }
        return $ret;
    }
}
?>
<?php
/**
 * 灵活宝
 * @author  zhang ji li 2015-03-17
 * @version  v1.00
 * @package  lvmaque.Agility
 * @link  http://www.lvmaque.com
 */
class AgilityAction extends HCommonAction
{

    private $AgilityBehavior;
    public function __construct()
    {
        parent::__construct();
        D("AgilityBehavior");
        $this->AgilityBehavior = new AgilityBehavior();
    }

    /**
     * 灵活宝首页
     *
     */
    public function index()
    {
        $user_money = M('member_money')->field('account_money, back_money')->where("uid=".$this->uid)->find();
        $user_money['money'] = bcadd($user_money['account_money'], $user_money['back_money'], 2);
        $this->assign('user_money', $user_money);

        $agility_bao = new AgilityBehavior();
        $bao = $agility_bao->format_list();
        $bao = $bao[0];
        if( !empty($bao) ) {
            $bao['lefttime'] = time() - $bao['online_time'];
        }

        $deadline = strtotime("+{$bao['repayment_period']} month", $bao['online_time']);
        $deadline = strtotime(date("Y-m-d 23:59:59", $deadline));
        $day = intval(($deadline-time())/3600/24); // 剩余天数
        $bao['day'] = $day;
        //dump($bao);
        $this->assign('bao', $bao);

        $code_str = $this->uid.$bao['id'].$user_money['money'];
        $auth_info = md5($code_str);
        session('agility_auth_info', $auth_info);

        $this->assign('unlogin_home', DOMAIN . '/login?redirectUrl=' . rawurlencode(DOMAIN . $_SERVER['REQUEST_URI']));
        $minfo = getMinfo($this->uid, "m.pin_pass");
        $has_pin = (empty($minfo['pin_pass']) === true) ? "no" : "yes";
        $this->assign('has_pin', $has_pin);

        $this->display();
    }

    /**
     * 转入资金
     *
     */
    public function investMoney()
    {
        if(!$this->uid){
            ajaxmsg("请先登录后进行操作",0);
        }


        $bao_id = intval($_POST['bao_id']);
        $invest_money = intval($_POST['money']);
        $pay_pass = $_POST['pay_pass'];



        if(!$bao_id || !$invest_money || !$pay_pass){
            ajaxmsg("参数有误！",0);
        }

        $pin_pass = M("members")->where("id={$this->uid}")->getField("pin_pass");
        if(md5($pay_pass)!==$pin_pass){
            ajaxmsg("支付密码不正确",0);
        }

        $user_money = M('member_money')->field('account_money, back_money')->where("uid=".$this->uid)->find();
        $user_money['money'] = bcadd($user_money['account_money'], $user_money['back_money'], 2);
        $bao = M("bao")->field(true)->where("id={$bao_id} and status=1")->find();
        $auth_info = $this->uid.$bao['id'].$user_money['money'];

        if($user_money['money'] < $invest_money){
            ajaxmsg("账户余额不足",0);
        }

        if($invest_money%$bao['start_funds']){
            ajaxmsg("投资金额必须为{$bao['start_funds']}的整数倍！",0);
        }


        $raise_money = bcadd($bao['raise_funds'], $invest_money, 2);
        if($raise_money > $bao['funds']){
            ajaxmsg("投资金额超过计划金额上限",0);
        }
        // 充值之后未刷新，提示信息有误
        if(md5($auth_info) != session("agility_auth_info")){
            ajaxmsg("验证信息有误",0);
        }else{ // 执行投资
            $bao_invest_id = $this->investment($this->uid, $bao_id, $invest_money);
            if($bao_invest_id){
                session('agility_auth_info', null);
                ajaxmsg("投资成功，投资金额{$invest_money}元");
            }else{
                ajaxmsg("很遗憾，投资失败！稍后重试", 0);
            }
        }


    }
    /**
     * 进行投资操作
     *
     */
    private function  investment($uid, $bao_id, $money)
    {
        /**
         * 1 更新bao 数据表 raise_funds  已集资 金额
         * 2 bao_invest 项目投资记录汇总，此数据每位投资者一条信息，同一投资者多次投资更新记录，次表数据用于投资账户资金别动资金池
         */

        $bao_info = M("bao")->field(true)->where("id={$bao_id} and status=1 and funds > raise_funds")->find();
        if(!$bao_info){
            ajaxmsg("信息有误", 0);
        }
        return $this->AgilityBehavior->investMoney($bao_info['batch_no'], $money, $uid); // 投资，成功返回id, 失败返回false


    }




}
?>

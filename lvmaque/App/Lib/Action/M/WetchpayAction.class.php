<?php
/**
 * 手机版 用户中心
 */
class WetchpayAction extends HCommonAction
{
    public function index(){
        $this->assign("ac",session('account_money'));
        /*微信配置文件*/
        $access_token = get_wetch_access_token('wxf5ea286a25dda4bf','67e2293d2b2026ed5a154a2d486c86cf','watch_token5');//调取签名
        //dump($access_token);
        $this->assign("appid",'wxf5ea286a25dda4bf');
        $this->assign("timestamp",time());
        $this->assign("nonceStr",'txcnmb');
        $sdk_qm = file_get_contents('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$access_token.'&type=jsapi');

        //dump($sdk_qm);
        /*微信配置文件*/
        /*签名*/
        $dai_sort = array();
        $tk = json_decode($sdk_qm);
        $dai_sort['noncestr'] = 'txcnmb';   //随机字符串
        $dai_sort['timestamp'] = time();    //时间戳
        $dai_sort['jsapi_ticket'] = $tk->ticket;  //ticket
        $dai_sort['url'] = 'http://newbz.wangjinkeji.com/m/wetchpay/index/'; //当前页面的url
        //dump($dai_sort);
        ksort($dai_sort,0); //按照ASCII字典排序;
        //dump($dai_sort);
        $sign =sha1('jsapi_ticket='.$tk->ticket.'&noncestr=txcnmb&timestamp='.time().'&url=http://newbz.wangjinkeji.com/m/wetchpay/index/');
        //dump($sign);
        $this->assign("signature",$sign);
        /*签名*/



        exit;


        /*
         * 微信支付登陆授权开始
         */
        ini_set('date.timezone','Asia/Shanghai');
        require_once "App/Lib/Wxpay/WxPay.Api.php";
        require_once "App/Lib/Wxpay/WxPay.JsApiPay.php";
        //require_once "App/Lib/Wxpay/log.php";  //日志记录


        //打印输出数组信息
        function printf_info($data)
        {
            foreach($data as $key=>$value){
                echo "<font color='#00ff55;'>$key</font> : $value <br/>";
            }
        }

//①、获取用户openid   授权登陆
        $tools = new JsApiPay();
        $openId = $tools->GetOpenid(); // openid

//②、统一下单
        $input = new WxPayUnifiedOrder();


        $input->SetOpenid($openId);
        $input->SetTime_start(date("YmdHis"));  //时间戳
        $input->SetBody("微信充值");  //商品描述
        $input->SetAttach("test");
        $input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));
        $input->SetTotal_fee(session('account_money')); //获取ajax提交时候存放在session里面的金额
        $input->SetTime_expire(date("YmdHis", time() + 600));
        //$input->SetGoods_tag("test");
        $input->SetNotify_url("http://newbz.wangjinkeji.com/m/wetchpay/notify_url");
        $input->SetTrade_type("JSAPI");
        $order = WxPayApi::unifiedOrder($input);
        echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
        printf_info($order);
        $jsApiParameters = $tools->GetJsApiParameters($order);
//获取共享收货地址js函数参数
        //$editAddress = $tools->GetEditAddressParameters();
        $this->assign("jsApiParameters",$jsApiParameters);
//③、在支持成功回调通知中处理成功之后的事宜，见 notify.php
        /**
         * 注意：
         * 1、当你的回调地址不可访问的时候，回调通知会失败，可以通过查询订单来确认支付是否成功
         * 2、jsapi支付时需要填入用户openid，WxPay.JsApiPay.php中有获取openid流程 （文档可以参考微信公众平台“网页授权接口”，
         * 参考http://mp.weixin.qq.com/wiki/17/c0f37d5704f0b64713d5d2c37b468d75.html）
         */

        /*
         * 微信支付登陆授权结束
         */


        $this->display();
    }

    public function notify_url(){

    }
}
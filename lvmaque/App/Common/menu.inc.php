<?php
$designer = FS("Webconfig/designer");
$version = FS("Webconfig/version");
/*array(菜单名，菜单url参数，是否显示)*/
$i=0;
$j=0;
$menu_left =  array();
$menu_left[$i]=array('全局','#',1);
$menu_left[$i]['low_title'][$i."-".$j] = array('全局设置','#',1);
$menu_left[$i][$i."-".$j][] = array('欢迎页',U('/admin/welcome/index'),1);
$menu_left[$i][$i."-".$j][] = array('网站设置',U('/admin/global/websetting'),1);
$menu_left[$i][$i."-".$j][] = array('缓存设置',U('/admin/cache/index'),0);
$menu_left[$i][$i."-".$j][] = array('标名设置',U('/admin/designer/index'),1);
$menu_left[$i][$i."-".$j][] = array('友情链接',U('/admin/global/friend'),1);
$menu_left[$i][$i."-".$j][] = array('广告管理',U('/admin/ad/'),1);
$menu_left[$i][$i."-".$j][] = array('导航菜单',U('/admin/navigation/index'),1);
$menu_left[$i][$i."-".$j][] = array('登录接口',U('/admin/loginonline/'),1);
$menu_left[$i][$i."-".$j][] = array("后台日志",U("/admin/global/adminlog"),1);
$menu_left[$i][$i."-".$j][] = array('清空缓存',U('/admin/global/cleanall'),1);

$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array('文章管理','#',1);
$menu_left[$i][$i."-".$j][] = array('文章列表',U('/admin/article/'),1);
$menu_left[$i][$i."-".$j][] = array('文章分类',U('/admin/acategory/'),1);

$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array('合同管理','#',1);
$menu_left[$i][$i."-".$j][] = array('合同列表',U('/admin/contract/'),1);

$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array('参数管理','#',1);
$menu_left[$i][$i."-".$j][] = array('业务参数',U('/admin/bconfig/index'),1);
$menu_left[$i][$i."-".$j][] = array('合同资料',U('/admin/hetong/index'),1);
$menu_left[$i][$i."-".$j][] = array('信用级别',U('/admin/leve/index'),1);
$menu_left[$i][$i."-".$j][] = array('年龄别称',U('/admin/age/index'),0);
$j++;
 
$menu_left[$i]['low_title'][$i."-".$j] = array('后台管理员',"#",1);
$menu_left[$i][$i."-".$j][] = array('管理员管理',U('/admin/Adminuser/'),1);
$menu_left[$i][$i."-".$j][] = array('用户组权限管理',U('/admin/acl/'),1);
$j++;

$i++;

#散标
$menu_left[$i]= array('借款','#',1);
$menu_left[$i]['low_title'][$i."-".$j] = array('借款列表','#',$version['single']);
$menu_left[$i][$i."-".$j][] = array('待初审借款',U('/admin/borrow/waitverify'),1);
$menu_left[$i][$i."-".$j][] = array('预发布借款',U('/admin/borrow/prerelease'),1);
$menu_left[$i][$i."-".$j][] = array('招标中借款',U('/admin/borrow/waitmoney'),1);
$menu_left[$i][$i."-".$j][] = array('待复审借款',U('/admin/borrow/waitverify2'),1);
$menu_left[$i][$i."-".$j][] = array('还款中借款',U('/admin/borrow/repaymenting'),1);
$menu_left[$i][$i."-".$j][] = array('已完成借款',U('/admin/borrow/done'),1);
$menu_left[$i][$i."-".$j][] = array('已流标借款',U('/admin/borrow/unfinish'),1);
$menu_left[$i][$i."-".$j][] = array('初审未通过',U('/admin/borrow/fail'),1);
$menu_left[$i][$i."-".$j][] = array('复审未通过',U('/admin/borrow/fail2'),1);
$j++;

#企业直投
$menu_left[$i]['low_title'][$i."-".$j] = array($designer[6],"#",$version['business']);
$menu_left[$i][$i."-".$j][] = array('添加'.$designer[6],U('/admin/tborrow/add'),1);
$menu_left[$i][$i."-".$j][] = array("预发布的借款",U("/admin/tborrow/prerelease"),1);
$menu_left[$i][$i."-".$j][] = array("投资中的借款",U("/admin/tborrow/index"),1);
$menu_left[$i][$i."-".$j][] = array("待复审的借款",U("/admin/tborrow/waitreview"),1);
$menu_left[$i][$i."-".$j][] = array("还款中的借款",U("/admin/tborrow/repayment"),1);
$menu_left[$i][$i."-".$j][] = array("已还完的借款",U("/admin/tborrow/endtran"),1);
$menu_left[$i][$i."-".$j][] = array("已流标的借款",U("/admin/tborrow/liubiaolist"),1);
$menu_left[$i][$i."-".$j][] = array("复审未通过",U("/admin/tborrow/reviewfail"),1);
$menu_left[$i][$i."-".$j][] = array("马上还款",U("/admin/tborrow/currentrepayment"),1);
$j++;

#定投宝
//`yjq20150202`
$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array($designer[7],"#",$version['fund']);
$menu_left[$i][$i."-".$j][] = array('添加'.$designer[7],U('/admin/fund/add'),1);
$menu_left[$i][$i."-".$j][] = array("预发布的".$designer[7],U("/admin/fund/prerelease"),1);
$menu_left[$i][$i."-".$j][] = array("认购中的".$designer[7],U("/admin/fund/index"),1);
$menu_left[$i][$i."-".$j][] = array("还款中的".$designer[7],U("/admin/fund/repayment"),1);
$menu_left[$i][$i."-".$j][] = array("已完成的".$designer[7],U("/admin/fund/endtran"),1);
$menu_left[$i][$i."-".$j][] = array("马上还款",U("/admin/fund/currentrepayment"),1);
//`yjq20150202`

// 灵 活 宝 zhang  ji li 20150318
$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array(AgilityBehavior::$THE_SPIRIT,"#",$version['agility']);
//$menu_left[$i][$i."-".$j][] = array('统计',U('/agility/admin/index'),1);
$menu_left[$i][$i."-".$j][] = array('参数配置',U('/agility/admin/setagi'),1);
$menu_left[$i][$i."-".$j][] = array("发布计划",U("/agility/admin/addItem"),1);
$menu_left[$i][$i."-".$j][] = array("募集中的".AgilityBehavior::$THE_SPIRIT,U("/agility/admin/itemlist", array('status'=>1)),1); 
$menu_left[$i][$i."-".$j][] = array("还款中的".AgilityBehavior::$THE_SPIRIT,U("/agility/admin/itemlist", array('status'=>2)),1);
$menu_left[$i][$i."-".$j][] = array("已还款的".AgilityBehavior::$THE_SPIRIT,U("/agility/admin/itemlist", array('status'=>4)),1);
//$menu_left[$i][$i."-".$j][] = array("资金投资列表",U("/agility/admin/buylist"),1);
//$menu_left[$i][$i."-".$j][] = array("资金赎回列表",U("/agility/admin/redemptionlist"),1);
$menu_left[$i][$i."-".$j][] = array("用户资金列表",U("/agility/admin/usermoneylist"),1);
//$menu_left[$i][$i."-".$j][] = array("收益记录",U("/agility/admin/interestrecord"),1);
// 灵 活 宝 zhang  ji li 20150318  

//`yjq20150202`
$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array("债权转让","#",1);
$menu_left[$i][$i."-".$j][] = array('债权转让',U('/admin/debt/index'),1);
$j++;

$menu_left[$i]['low_title'][$i."-".$j] = array('逾期借款','#',1);
$menu_left[$i][$i."-".$j][] = array('逾期统计',U('/admin/expired/detail'),0);
$menu_left[$i][$i."-".$j][] = array('已逾期借款',U('/admin/expired/index'),1);
$menu_left[$i][$i."-".$j][] = array('逾期会员列表',U('/admin/expired/member'),1);


$i++;
$menu_left[$i]= array('会员','#',1);
$menu_left[$i]['low_title'][$i."-".$j] = array('会员管理','#',1);
$menu_left[$i][$i."-".$j][] = array('会员列表',U('/admin/members/index'),1);
$menu_left[$i][$i."-".$j][] = array('借款会员',U('/admin/members/info'),1);
$j++;


$menu_left[$i]['low_title'][$i."-".$j] = array('推荐人管理','#',1);
$menu_left[$i][$i."-".$j][] = array('投资记录',U('/admin/refereedetail/index'),1);
$j++;

$menu_left[$i]['low_title'][$i."-".$j] = array('认证及申请','#',1);
$menu_left[$i][$i."-".$j][] = array('手机认证会员',U('/admin/verifyphone/index'),1);
$menu_left[$i][$i."-".$j][] = array('借款会员审核',U('/admin/loaninfo/index'),1);
$menu_left[$i][$i."-".$j][] = array('额度申请审核',U('/admin/members/infowait'),$version['single']);
$menu_left[$i][$i."-".$j][] = array('上传资料管理',U('/admin/memberdata/index'),1);
$j++;

$menu_left[$i]['low_title'][$i."-".$j] = array('实名认证管理','#',1);
$menu_left[$i][$i."-".$j][] = array('实名认证管理',U('/admin/memberid/index'),1);//手工认证申请
$menu_left[$i][$i."-".$j][] = array('实名认证接口',U('/admin/id5/'),1);
$j++;


///自动投标会员
$menu_left[$i]['low_title'][$i."-".$j] = array('自动投标会员','#',1);
$menu_left[$i][$i."-".$j][] = array('自动投标会员',U('/admin/automembers/index'),1);

///自动投标会员
/* $menu_left[$i]['low_title'][$i."-".$j] = array('快捷借款管理','#',1);
$menu_left[$i][$i."-".$j][] = array('快捷借款列表',U('/admin/feedback/index'),1);
$j++; */


$i++;
$menu_left[$i]= array('资金','#',1);
$menu_left[$i]['low_title'][$i."-".$j] = array('充值管理','#',1);
$menu_left[$i][$i."-".$j][] = array('在线充值',U('/admin/Paylog/paylogonline'),1);
$menu_left[$i][$i."-".$j][] = array('线下充值',U('/admin/Paylog/paylogoffline'),1);
$menu_left[$i][$i."-".$j][] = array('充值总表',U('/admin/Paylog/index'),1);
$j++;



$menu_left[$i]['low_title'][$i."-".$j] = array('提现管理','#',1);
$menu_left[$i][$i."-".$j][] = array('待审核提现',U('/admin/Withdrawlogwait/index'),1);
$menu_left[$i][$i."-".$j][] = array('处理中提现',U('/admin/Withdrawloging/index'),1);
$menu_left[$i][$i."-".$j][] = array('提现已成功 ',U('/admin/Withdrawlog/withdraw2'),1);
$menu_left[$i][$i."-".$j][] = array('审核未通过',U('/admin/Withdrawlog/withdraw3'),1);
$menu_left[$i][$i."-".$j][] = array('提现总列表',U('/admin/Withdrawlog/index'),1);
$j++;


/*$i++;
$menu_left[$i]= array('文章','#',1);
$menu_left[$i]['low_title'][$i."-".$j] = array('文章管理','#',1);
$menu_left[$i][$i."-".$j][] = array('文章列表',U('/admin/article/'),1);
$menu_left[$i][$i."-".$j][] = array('文章分类',U('/admin/acategory/'),1);*/

$i++;
$menu_left[$i]= array('统计','#',1);
$menu_left[$i]['low_title'][$i."-".$j] = array('会员帐户','#',1);
$menu_left[$i][$i."-".$j][] = array('会员帐户',U('/admin/capitalaccount/index'),1);
$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array('充值提现','#',1);
$menu_left[$i][$i."-".$j][] = array('充值记录',U('/admin/capitalonline/charge'),1);
$menu_left[$i][$i."-".$j][] = array('提现记录',U('/admin/capitalonline/withdraw'),1);
$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array('资金变动记录','#',1);
$menu_left[$i][$i."-".$j][] = array('会员资金记录',U('/admin/capitaldetail/index'),1);
$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array('会员投资排行','#',1);
$menu_left[$i][$i."-".$j][] = array('投资排行记录',U('/admin/capitalrank/index'),1);
$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array('网站资金统计','#',1);
$menu_left[$i][$i."-".$j][] = array('网站资金统计',U('/admin/capitalall/index'),1);

//=======================统计详情 yuan===============
$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array('借款统计','#',1);
$menu_left[$i][$i."-".$j][] = array('成功借出明细',U('/admin/capitalall/borrow'),1);
$menu_left[$i][$i."-".$j][] = array('已还款明细',U('/admin/capitalall/repayment'),1);
$menu_left[$i][$i."-".$j][] = array('未还款明细',U('/admin/capitalall/norepayment'),1);

$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array('网站收益','#',1);
$menu_left[$i][$i."-".$j][] = array('借款管理费',U('/admin/capitalall/borrowfee'),1);
$menu_left[$i][$i."-".$j][] = array('利息管理费',U('/admin/capitalall/insterestfee'),1);
$menu_left[$i][$i."-".$j][] = array('提现手续费',U('/admin/capitalall/withdrawfee'),1);
$menu_left[$i][$i."-".$j][] = array('转让债权手续费',U('/admin/capitalall/debtfee'),1);

$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array('投资收益','#',1);
$menu_left[$i][$i."-".$j][] = array('成功借款利息',U('/admin/capitalall/interest'),1);
$menu_left[$i][$i."-".$j][] = array('成功借款投标奖励',U('/admin/capitalall/reward'),1);
$menu_left[$i][$i."-".$j][] = array('线下充值奖励',U('/admin/capitalall/linereward'),1);
//$menu_left[$i][$i."-".$j][] = array('续投奖励',U('/admin/capitalall/xutou'),1);


$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array('逾期','#',1);
$menu_left[$i][$i."-".$j][] = array('逾期已还款',U('/admin/capitalall/expired'),1);
$menu_left[$i][$i."-".$j][] = array('逾期未还款',U('/admin/capitalall/waitexpired'),1);
$menu_left[$i][$i."-".$j][] = array('逾期管理费',U('/admin/capitalall/callfee'),1);
$menu_left[$i][$i."-".$j][] = array('逾期罚息',U('/admin/capitalall/expiredfee'),1);

//=======================统计详情 yuan===============

$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array('会员投标记录','#',1);
$menu_left[$i][$i."-".$j][] = array('会员投标记录',U('/admin/Tender/index'),1);
$j++;

$i++;
$menu_left[$i]= array('扩展','#',1);
$menu_left[$i]['low_title'][$i."-".$j] = array('充值设置','#',1);
$menu_left[$i][$i."-".$j][] = array('线下充值',U('/admin/payoffline/'),1);
$menu_left[$i][$i."-".$j][] = array('在线支付',U('/admin/payonline/'),1);

$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array('在线客服','#',1);
$menu_left[$i][$i."-".$j][] = array('客服QQ号',U('/admin/qq/index'),1);
$menu_left[$i][$i."-".$j][] = array('网站QQ群',U('/admin/qq/qun'),1);
$menu_left[$i][$i."-".$j][] = array('客服电话',U('/admin/qq/tel/'),1);

$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array('安全检测','#',1);
$menu_left[$i][$i."-".$j][] = array('文件管理',U('/admin/mfields/'),1);
$menu_left[$i][$i."-".$j][] = array('木马查杀',U('/admin/scan/'),1);
$j++;

$menu_left[$i]['low_title'][$i."-".$j] = array('数据库管理','#',1);
$menu_left[$i][$i."-".$j][] = array('数据库信息',U('/admin/db/'),1);
$menu_left[$i][$i."-".$j][] = array('备份管理',U('/admin/db/baklist'),1);
$menu_left[$i][$i."-".$j][] = array('清空数据',U('/admin/db/truncate'),1);

$j++;

$menu_left[$i]['low_title'][$i."-".$j] = array('优惠券管理','#',1);
$menu_left[$i][$i."-".$j][] = array('优惠券设置',U('/admin/expmoney/setexp'),1);
$menu_left[$i][$i."-".$j][] = array('优惠券颁发',U('/admin/expmoney/addexp'),1);
$menu_left[$i][$i."-".$j][] = array('优惠券记录',U('/admin/expmoney/listexp'),1);
$menu_left[$i][$i."-".$j][] = array('优惠券统计',U('/admin/expmoney/countexp'),1);
$menu_left[$i][$i."-".$j][] = array('积分兑换设置',U('/admin/expmoney/redeem'),1);

$j++;

$menu_left[$i]['low_title'][$i."-".$j] = array('积分管理','#',1);
$menu_left[$i][$i."-".$j][] = array('投资积分',U('/admin/integration/listinvest'),1);
$menu_left[$i][$i."-".$j][] = array('信用积分',U('/admin/integration/listcredit'),1);

$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array('提成管理',"#",1);
$menu_left[$i][$i."-".$j][] = array('团队长管理',U('/admin/Teamuser/'),1);
$menu_left[$i][$i."-".$j][] = array('经纪人管理',U('/admin/Broker/'),1);
$menu_left[$i][$i."-".$j][] = array('投资人管理',U('/admin/Investor/'),1);
$menu_left[$i][$i."-".$j][] = array('经纪人提成统计',U('/admin/Brokermoney/'),1);
$menu_left[$i][$i."-".$j][] = array('团队长提成统计',U('/admin/Teammoney/'),1);

$j++;


//重新将移动配置接口分配出来

$i++;

$menu_left[$i]= array('移动','#',1);
$menu_left[$i]['low_title'][$i."-".$j] = array('在线通知','#',1);
$menu_left[$i][$i."-".$j][] = array('信息接口',U('/admin/msgonline/'),1);
$menu_left[$i][$i."-".$j][] = array('信息模板',U('/admin/msgonline/templet/'),1);

$j++;
$menu_left[$i]['low_title'][$i."-".$j] = array('app客户端管理','#',$version['mobile']);
$menu_left[$i][$i."-".$j][] = array('手机消息推送',U('/mobile/baidupush/index'),1);
$menu_left[$i][$i."-".$j][] = array('意见反馈列表',U('/admin/feedback/index'),1);
$menu_left[$i][$i."-".$j][] = array('添加幻灯片',U('/mobile/Appsetup/adbanner'),1);
$menu_left[$i][$i."-".$j][] = array('幻灯片列表',U('/mobile/Appsetup/index'),1);
$menu_left[$i][$i."-".$j][] = array('app手机参数',U('/admin/msgonline/app_canshu'),1);

$j++;


//微信导航开始..................................................
$menu_left[$i]['low_title'][$i."-".$j] = array('微信管理',"#",$version['wechat']);
$menu_left[$i][$i."-".$j][] = array('添加幻灯片',U('/M/Weixinadmin/adbanner'),1);
$menu_left[$i][$i."-".$j][] = array('幻灯片列表',U('/M/Weixinadmin/index'),1);
$menu_left[$i][$i."-".$j][] = array('意见反馈列表',U('/admin/feedback/index'),1);
//微信导航结束....................................................

?>


<?php
$designer = FS("Webconfig/designer");
$version = FS("Webconfig/version");
/* array(菜单名，菜单url参数，是否显示) */
//error_reporting(E_ALL);
/*
  $acl_inc[$i]['low_leve']['global']  global是model
  每个action前必须添加eqaction_前缀'eqaction_websetting'  => 'at1','at1'表示唯一标志,可独自命名,eqaction_后面跟的action必须统一小写


 */
$acl_inc = array();
$i = 0;
$acl_inc[$i]['low_title'][] = '全局设置&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['global'] = array("网站设置" => array(
        "列表" => 'at1',
        "增加" => 'at2',
        "删除" => 'at3',
        "修改" => 'at4',
    ),
    "友情链接" => array(
        "列表" => 'at5',
        "增加" => 'at6',
        "删除" => 'at7',
        "修改" => 'at8',
        "搜索" => 'att8',
    ),
    "所有缓存" => array(
        "清除" => 'at22',
    ),
    "后台操作日志" => array(
        "列表" => 'at23',
        "删除" => 'at24',
        "删除一月前操作日志" => 'at25',
    ),
    "data" => array(
        //网站设置
        'eqaction_websetting' => 'at1',
        'eqaction_doadd' => 'at2',
        'eqaction_dodelweb' => 'at3',
        'eqaction_doedit' => 'at4',
        //友情链接
        'eqaction_friend' => 'at5',
        'eqaction_dodeletefriend' => 'at7',
        'eqaction_searchfriend' => 'att8',
        'eqaction_addfriend' => array(
            'at6' => array(
                'POST' => array(
                    "fid" => 'G_NOTSET',
                ),
            ),
            'at8' => array(
                'POST' => array(
                    "fid" => 'G_ISSET',
                ),
            ),
        ),
        //清除缓存
        'eqaction_cleanall' => 'at22',
        'eqaction_adminlog' => 'at23',
        'eqaction_dodeletelog' => 'at24',
        'eqaction_dodellogone' => 'at25', //删除近期一个月内的后台操作日志
    )
);

$acl_inc[$i]['low_leve']['ad'] = array("广告管理" => array(
        "列表" => 'ad1',
        "增加" => 'ad2',
        "删除" => 'ad4',
        "修改" => 'ad3',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'ad1',
        'eqaction_add' => 'ad2',
        'eqaction_doadd' => 'ad2',
        'eqaction_edit' => 'ad3',
        'eqaction_doedit' => 'ad3',
        'eqaction_swfupload' => 'ad3',
        'eqaction_dodel' => 'ad4',
    )
);

$acl_inc[$i]['low_leve']['designer'] = array("标名设置" => array(
    "查看" => 'des1',
    "修改" => 'des2',
),
    "data" => array(
        //标名设置
        'eqaction_index' => 'des1',
        'eqaction_edit' => 'des2',
        'eqaction_doedit' => 'des2',
    )
);

$acl_inc[$i]['low_leve']['cache'] = array("缓存设置" => array(
    "修改" => 'des1',
),
    "data" => array(
        'eqaction_index' => 'des1',
    )
);

$acl_inc[$i]['low_leve']['loginonline'] = array("登录接口管理" => array(
        "查看" => 'dl1',
        "修改" => 'dl2',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'dl1',
        'eqaction_save' => 'dl2',
    )
);
$i++;

if (isset($version) && $version['single']==1){
//散标
$i++;
$acl_inc[$i]['low_title'][] = '借款管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['borrow'] = array("初审待审核借款" => array(
        "列表" => 'br1',
        "审核" => 'br2',
    ),
    "复审待审核借款" => array(
        "列表" => 'br3',
        "审核" => 'br4',
    ),
    "预发布的借款" => array(
        "列表" => 'br16',
        "编辑" => 'br17',
    ),
    "招标中的借款" => array(
        "列表" => 'br5',
        "审核" => 'br6',
        "人工处理" => 'br8',
    ),
    "还款中的借款" => array(
        "列表" => 'br7',
        "一周内到期标" => 'br7',
        "投资记录" => 'br15',
        "还款明细"  => 'br22'
    //"编辑" => 'br16',//(开启可编辑还款状态下的借款图片资料)
    ),
    "已完成的借款" => array(
        "列表" => 'br9',
    ),
    "已流标借款" => array(
        "列表" => 'br11',
    ),
    "初审未通过的借款" => array(
        "列表" => 'br13',
    ),
    "复审未通过的借款" => array(
        "列表" => 'br14',
    ),
    "data" => array(
        //网站设置
        'eqaction_waitverify' => 'br1',
        'eqaction_edit' => 'br2',
        'eqaction_edit' => 'br4',
        'eqaction_edit' => 'br6',
        "eqaction_prerelease" => "br16",
        'eqaction_doeditwaitverify' => 'br2',
        'eqaction_waitverify2' => 'br3',
        'eqaction_doeditwaitverify2' => 'br4',
        'eqaction_waitmoney' => 'br5',
        'eqaction_doeditwaitmoney' => 'br6',
        'eqaction_repaymenting' => 'br7',
        'eqaction_doweek' => 'br7',
        'eqaction_done' => 'br9',
        'eqaction_unfinish' => 'br11',
        'eqaction_fail' => 'br13',
        'eqaction_fail2' => 'br14',
        'eqaction_swfupload' => 'br2',
        'eqaction_dowaitmoneycomplete' => 'br8',
        'eqaction_doinvest' => 'br15',
        'eqaction_doeditprerelease' => 'br17',
        'eqaction_repaymentdetail' => 'br22',
    //'eqaction_doeditrepaymenting' => 'br16',
    )
);
}

$i++;
$acl_inc[$i]['low_title'][] = '债权转让&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['debt'] = array("债权转让" => array(
        '查看' => 'debt1',
        '审核' => 'debt2',
		'购买记录' => 'debt3',
    ),
    "data" => array(
        'eqaction_index' => 'debt1',
        'eqaction_audit' => 'debt2',
		'eqaction_record' => 'debt3',
    ),
);

if (isset($version) && $version['business']==1){
//企业直投
$i++;
$acl_inc[$i]['low_title'][] = $designer[6].'管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['tborrow'] = array($designer[6]."管理" => array(
        "列表" => "tb1",
        "添加" => "tb2",
        "修改" => "tb3",
        "删除" => "tb6",
        "投资记录" => 'tb4',
        "还款明细" => 'tb5',
        "复审" => 'tb8',
        "流标" => 'tb7',
        "还款" => 'tb9',
),
    "data" => array(
        "eqaction_endtran" => "tb1",
        "eqaction_index" => "tb1",
        "eqaction_prerelease" => "tb1",
        "eqaction_repayment" => "tb1",
        "eqaction_liubiaolist" => "tb1",
        "eqaction_getusername" => "tb2",
        "eqaction_swfupload" => "tb2",
        "eqaction_add" => "tb2",
        "eqaction_doadd" => "tb2",
        "eqaction_getusername" => "tb3",
        "eqaction_swfupload" => "tb3",
        "eqaction_edit" => "tb3",
        "eqaction_doedit" => "tb3",
        "eqaction_delete" => "tb6",
        'eqaction_doinvest' => 'tb4',
        'eqaction_liubiao' => 'tb7',
        'eqaction_waitreview' => 'tb8',
        'eqaction_editreview' => 'tb8',
        'eqaction_reviewfail' => 'tb8',
        'eqaction_currentrepayment' => 'tb3',
        'eqaction_repaymentdetail' => 'tb5',
        "eqaction_repaymentoperation" => "tb9",
    )
);
}

if (isset($version) && $version['fund']==1){
//定投宝
$i++;
$acl_inc[$i]['low_title'][] = $designer[7].'管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['fund'] = array($designer[7]."管理" => array(
        "列表" => "fund1",
        "添加" => "fund2",
        "修改" => "fund3",
        "删除" => "fund5",
        "投资记录" => 'fund4',
        "还款明细" => 'fund7',
        "还款" => 'fund8',
    ),
    "data" => array(
        "eqaction_endtran" => "fund1",
        "eqaction_index" => "fund1",
        "eqaction_prerelease" => "fund1",
        "eqaction_repayment" => "fund1",
        "eqaction_repaymentdetail" => "fund7",
        "eqaction_repaymentoperation" => "fund8",
        "eqaction_getusername" => "fund2",
        "eqaction_swfupload" => "fund2",
        "eqaction_add" => "fund2",
        "eqaction_doadd" => "fund2",
        "eqaction_getusername" => "fund3",
        "eqaction_swfupload" => "fund3",
        "eqaction_edit" => "fund3",
        "eqaction_doedit" => "fund3",
        "eqaction_dodel" => "fund5",
        'eqaction_doinvest' => 'fund4',
        'eqaction_currentrepayment' => 'fund3',
    )
);
}

if (isset($version) && $version['agility']==1){
//灵活宝
$i++;
$acl_inc[$i]['low_title'][] = AgilityBehavior::$THE_SPIRIT.'管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['admin'] = array(AgilityBehavior::$THE_SPIRIT."管理" => array(
        "统计、列表、投资、用户资金" => "ali1",
        "设置、添加" => "ali2",
        "结束".AgilityBehavior::$THE_SPIRIT => "ali3",
        //"删除" => "ali4",
        ///"投资列表、赎回列表"=>'ali5',
        "投资记录,赎回记录,持有记录" => 'ali6',
        "还款" => 'ali7',
    ),
    "data" => array(
        "eqaction_endtran" => "ali1",
        "eqaction_index" => "ali1",
        "eqaction_repayment" => "ali1",
        "eqaction_itemlist" => "ali1",
        "eqaction_additem" => "ali2", 
       // "eqaction_repaymentdetail" => "ali7",
        "eqaction_interestrecord" => "ali1",
        "eqaction_usermoneylist" => "ali1",
        "eqaction_redemptionlist" => "ali6",
        "eqaction_buylist" => "ali6",
        "eqaction_holdsrecord" => "ali6",
        "eqaction_del" => "ali4", 
        "eqaction_setagi"=>'ali2',
        "eqaction_enditem"=>'ali3',
       // "eqaction_verifyout"=>'ali5', 
        
       
    )
);
}

//自动投标会员权限列表
$i++;
$acl_inc[$i]['low_title'][] = '自动投标会员&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['automembers'] = array("自动投标会员" => array(
        "列表" => "am1",
        "修改" => "am2",
    ),
    "data" => array(
        "eqaction_index" => 'am1',
        'eqaction_doedit' => 'am2',
    )
);
//自动投标会员权限列表
$i++;
$acl_inc[$i]['low_title'][] = '逾期借款管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['expired'] = array("逾期借款管理" => array(
        "查看" => 'yq1',
        "代还" => 'yq2',
    ),
    "逾期会员列表" => array(
        "列表" => 'yq3',
    ),
    "data" => array(
        'eqaction_index' => 'yq1',
        'eqaction_doexpired' => 'yq2',
        'eqaction_member' => 'yq3',
    )
);
$i++;
$acl_inc[$i]['low_title'][] = '会员管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['members'] = array("会员列表" => array(
        "列表" => 'me1',
        "调整余额" => 'mx2',
        "调整授信" => 'mx3',
        "删除会员" => 'mxw',
        "修改客户类型" => 'xmxw',
    ),
    "会员资料" => array(
        "列表" => 'me3',
        "查看" => 'me4',
    ),
    "额度申请待审核" => array(
        "列表" => 'me7',
        "审核" => 'me6',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'me1',
        'eqaction_info' => 'me3',
        'eqaction_viewinfom' => 'me4',
        'eqaction_infowait' => 'me7',
        'eqaction_viewinfo' => 'me6',
        'eqaction_doeditcredit' => 'me6',
        'eqaction_domoneyedit' => 'mx2',
        'eqaction_moneyedit' => 'mx2',
        'eqaction_creditedit' => 'mx3',
        'eqaction_dodel' => 'mxw',
        'eqaction_edit' => 'xmxw',
        'eqaction_doedit' => 'xmxw',
        'eqaction_docreditedit' => 'mx3',
        'eqaction_idcardedit' => 'xmxw',
        'eqaction_doidcardedit' => 'xmxw',
        'eqaction_memberborrow' => 'xmxw',
        'eqaction_mb_export' => 'xmxw',
        'eqaction_bankedit' => 'me1',
        'eqaction_dobankedit' => 'xmxw',
        'eqaction_bankdel' => 'mxw',
        'eqaction_sele' => 'me1',
        'eqaction_city' => 'me1',
    )
);
$acl_inc[$i]['low_leve']['common'] = array("会员详细资料" => array(
        "查询" => 'mex5',
        "账户通讯" => 'sms1',
        "具体通讯" => 'sms2',
        "节日通讯" => 'sms3',
        "通讯记录" => 'sms4',
    ),
    "data" => array(
        'eqaction_member' => 'mex5',
        'eqaction_sms' => 'sms1',
        'eqaction_sendsms' => 'sms2',
        'eqaction_sendgala' => 'sms3',
        'eqaction_smslog' => 'sms4',
    )
);
$acl_inc[$i]['low_leve']['refereedetail'] = array("推荐人管理" => array(
        "列表" => 'referee_1',
        "导出" => 'referee_2',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'referee_1',
        'eqaction_export' => 'referee_2',
    )
);

$i++;
$acl_inc[$i]['low_title'][] = '优惠券管理&nbsp;&nbsp;&nbsp;&nbsp;';

$acl_inc[$i]['low_leve']['expmoney'] = array("优惠券" => array(
        "列表" => 'exp1',
        "发放" => 'exp2',
        "优惠券设置" => 'exp3',
        "积分兑换设置" =>'exp4',
    ),
    "data" => array(
        'eqaction_index' => 'exp1',
        'eqaction_listexp' => 'exp1',
        'eqaction_addexp' => 'exp2',
        'eqaction_setexp' => 'exp3',
        'eqaction_give_rewards' => 'exp2',
        'eqaction_countexp' => 'exp1',
        'eqaction_redeem' => 'exp4',
    )
);

$i++;
$acl_inc[$i]['low_title'][] = '积分管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['integration'] = array("积分" => array(
    "投资积分列表" => 'inte1',
    "投资积分明细" => 'inte3',
    "信用积分列表" => 'inte2',
    "信用积分明细" => 'inte4',
    
),
    "data" => array(
        'eqaction_listinvest' => 'inte1',
        'eqaction_investdetail' => 'inte3',
        'eqaction_listcredit' => 'inte2',
        'eqaction_creditdetail' => 'inte4',
    )
);

$i++;
$acl_inc[$i]['low_title'][] = '认证及申请管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['loaninfo'] = array("借款会员审核" => array(
        "列表" => 'loan1',
        "审核" => 'loan2',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'loan1',
        'eqaction_edit' => 'loan2',
        'eqaction_edit_q' => 'loan2',
        'eqaction_doedit' => 'loan2',
        'eqaction_doedit_q' => 'loan2',
    )
);
$acl_inc[$i]['low_leve']['memberid'] = array("会员实名认证管理" => array(
        "列表" => 'me10',
        "审核" => 'me9',
        "id5验证" => 'me8',
        "导出" => 'me7',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'me10',
        'eqaction_edit' => 'me9',
        'eqaction_doedit' => 'me9',
        'eqaction_idcheck' => 'me8',
        'eqaction_export' => 'me7',
    )
);
$acl_inc[$i]['low_leve']['showimg'] = array("显示图片" => array(
        "显示" => 'show1',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'show1',
    )
);
$acl_inc[$i]['low_leve']['id5'] = array("实名认证参数管理" => array(
        "查看" => 'id1',
        "修改" => 'id2',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'id1',
        'eqaction_save' => 'id2',
    )
);
$acl_inc[$i]['low_leve']['memberdata'] = array("会员上传资料管理" => array(
        "列表" => 'dat1',
        "审核" => 'dat3',
        "上传资料" => 'dat4',
        "上传展示资料" => 'dat5',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'dat1',
        'eqaction_swfupload' => 'dat1',
        'eqaction_edit' => 'dat3',
        'eqaction_doedit' => 'dat3',
        'eqaction_upload' => 'dat4',
        'eqaction_doupload' => 'dat4',
        'eqaction_uploadshow' => 'dat5',
        'eqaction_douploadshow' => 'dat5',
    )
);
$acl_inc[$i]['low_leve']['verifyphone'] = array("手机认证会员" => array(
        "列表" => 'vphone1',
        "导出" => 'vphone2',
        "审核" => 'vphone3',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'vphone1',
        'eqaction_export' => 'vphone2',
        'eqaction_edit' => 'vphone3',
        'eqaction_doedit' => 'vphone3',
    )
);
$i++;
$acl_inc[$i]['low_title'][] = '充值提现管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['paylog'] = array("充值记录" => array(
        "列表" => 'cz',
        "充值处理" => 'czgl',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'cz',
        'eqaction_paylogonline' => 'cz',
        'eqaction_paylogoffline' => 'cz',
        'eqaction_edit' => 'czgl',
        'eqaction_doedit' => 'czgl'
    )
);
$acl_inc[$i]['low_leve']['withdrawlog'] = array("提现管理" => array(
        "列表" => 'cg2',
        "审核" => 'cg3',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'cg2',
        'eqaction_edit' => 'cg3',
        'eqaction_doedit' => 'cg3',
        'eqaction_withdraw0' => 'cg2', //待提现      新增加2012-12-02 fanyelei
        'eqaction_withdraw1' => 'cg2', //提现处理中	新增加2012-12-02 fanyelei
        'eqaction_withdraw2' => 'cg2', //提现成功		新增加2012-12-02 fanyelei
        'eqaction_withdraw3' => 'cg2', //提现失败		新增加2012-12-02 fanyelei
    )
);
$acl_inc[$i]['low_title'][] = '待提现列表';
$acl_inc[$i]['low_leve']['withdrawlogwait'] = array("待提现列表" => array(
        "列表" => 'cg4',
        "审核" => 'cg5',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'cg4',
        'eqaction_edit' => 'cg5',
        'eqaction_doedit' => 'cg5',
    )
);
$acl_inc[$i]['low_title'][] = '提现处理中列表';
$acl_inc[$i]['low_leve']['withdrawloging'] = array("提现处理中列表" => array(
        "列表" => 'cg6',
        "审核" => 'cg7',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'cg6',
        'eqaction_edit' => 'cg7',
        'eqaction_doedit' => 'cg7',
    )
);

//`mxl:teamreward`
//团队长管理
$i++;
$acl_inc[$i]['low_title'][] = '团队长管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['teamuser'] = array("团队长管理" => array(
        "列表" => 'at77',
        "增加" => 'at78',
        "删除" => 'at79',
        "上传头像" => 'at99',
        "修改" => 'at80',
        "省市" => 'at81',
    ),
    "data" => array(
        //权限管理
        'eqaction_index' => 'at77',
        'eqaction_getarea' => 'at81',
        'eqaction_dodelete' => 'at79',
        'eqaction_header' => 'at99',
        'eqaction_memberheaderuplad' => 'at99',
        'eqaction_addadmin' => array(
            'at78' => array(//增加
                'POST' => array(
                    "uid" => 'G_NOTSET',
                ),
            ),
            'at80' => array(//修改
                'POST' => array(
                    "uid" => 'G_ISSET',
                ),
            ),
        ),
    )
);
//经纪人管理
$i++;
$acl_inc[$i]['low_title'][] = '经纪人管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['broker'] = array("经纪人管理" => array(
        "列表" => 'at77',
        "增加" => 'at78',
        "删除" => 'at79',
        "上传头像" => 'at99',
        "修改" => 'at80',
        "省市" => 'at81',
        "解除关系" => 'at82',
        "重置关系" => 'at83',
    ),
    "data" => array(
        //权限管理
        'eqaction_index' => 'at77',
        'eqaction_getarea' => 'at81',
        'eqaction_relieve' => 'at82',
        'eqaction_reset' => 'at83',
        'eqaction_ajax_reset' => 'at83',
        'eqaction_resetact' => 'at83',
        'eqaction_dodelete' => 'at79',
        'eqaction_header' => 'at99',
        'eqaction_memberheaderuplad' => 'at99',
        'eqaction_addadmin' => array(
            'at78' => array(//增加
                'POST' => array(
                    "uid" => 'G_NOTSET',
                ),
            ),
            'at80' => array(//修改
                'POST' => array(
                    "uid" => 'G_ISSET',
                ),
            ),
        ),
    )
);
//投资人管理
$i++;
$acl_inc[$i]['low_title'][] = '投资人管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['investor'] = array("投资人管理" => array(
        "列表" => 'at77',
        "解除关系" => 'at82',
        "重置关系" => 'at83',
        "导出" => 'at80',
    ),
    "data" => array(
        //权限管理
        'eqaction_index' => 'at77',
        'eqaction_relieve' => 'at82',
        'eqaction_reset' => 'at83',
        'eqaction_ajax_reset' => 'at83',
        'eqaction_resetact' => 'at83',
        'eqaction_export' => 'at80',
    )
);
//经纪人提成统计管理
$i++;
$acl_inc[$i]['low_title'][] = '经纪人提成统计&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['brokermoney'] = array("经纪人提成统计" => array(
        "经纪人列表" => 'at77',
        "投资人列表" => 'at79',
        "导出" => 'at80',
    ),
    "data" => array(
        //权限管理
        'eqaction_index' => 'at77',
        'eqaction_investorlist' => 'at79',
        'eqaction_listinvestor' => 'at79', //`mxl:invlist`
        'eqaction_export' => 'at80',
    )
);
//团队长提成统计管理
$i++;
$acl_inc[$i]['low_title'][] = '团队长提成统计&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['teammoney'] = array("团队长提成统计" => array(
        "团队长列表" => 'at77',
        "经纪人列表" => 'at78',
        "投资人列表" => 'at79',
        "导出" => 'at80',
    ),
    "data" => array(
        //权限管理
        'eqaction_index' => 'at77',
        'eqaction_brokerlist' => 'at78',
        'eqaction_investorlist' => 'at79',
        'eqaction_export' => 'at80',
    )
);
//权限管理
//`mxl:teamreward`

$i++;
$acl_inc[$i]['low_title'][] = '文章管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['article'] = array("文章管理" => array(
        "列表" => 'at1',
        "添加" => 'at2',
        "删除" => 'at3',
        "修改" => 'at4',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'at1',
        'eqaction_add' => 'at2',
        'eqaction_doadd' => 'at2',
        'eqaction_dodel' => 'at3',
        'eqaction_edit' => 'at4',
        'eqaction_doedit' => 'at4',
    )
);
$acl_inc[$i]['low_leve']['acategory'] = array("文章分类" => array(
        "列表" => 'act1',
        "添加" => 'act2',
        "批量添加" => 'act5',
        "删除" => 'act3',
        "修改" => 'act4',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'act1',
        'eqaction_listtype' => 'act1',
        'eqaction_add' => 'act2',
        'eqaction_doadd' => 'act2',
        'eqaction_dodel' => 'act3',
        'eqaction_edit' => 'act4',
        'eqaction_doedit' => 'act4',
        'eqaction_addmultiple' => 'act5',
        'eqaction_doaddmul' => 'act5',
    )
);

//合同
$i++;
$acl_inc[$i]['low_title'][] = '合同管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['contract'] = array("合同列表" => array(
    "列表" => 'aact1',
    "添加" => 'aact2',
    /* "批量添加" => 'aact5', */
    "删除" => 'aact3',
    "修改" => 'aact4',
),
    "data" => array(
        //网站设置
        'eqaction_index' => 'aact1',
        'eqaction_listtype' => 'aact1',
        'eqaction_add' => 'aact2',
        'eqaction_doadd' => 'aact2',
        'eqaction_dodel' => 'aact3',
        'eqaction_edit' => 'aact4',
        'eqaction_doedit' => 'aact4',
        'eqaction_addmultiple' => 'aact5',
        'eqaction_doaddmul' => 'aact5',
    )
);

$i++;
$acl_inc[$i]['low_title'][] = '导航菜单管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['navigation'] = array("导航菜单" => array(
        "列表" => 'nav1',
        "添加" => 'nav2',
        "批量添加" => 'nav5',
        "删除" => 'nav3',
        "修改" => 'nav4',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'nav1',
        'eqaction_listtype' => 'nav1',
        'eqaction_add' => 'nav2',
        'eqaction_doadd' => 'nav2',
        'eqaction_dodel' => 'nav3',
        'eqaction_edit' => 'nav4',
        'eqaction_doedit' => 'nav4',
        'eqaction_addmultiple' => 'nav5',
        'eqaction_doaddmul' => 'nav5',
    )
);
$i++;
$acl_inc[$i]['low_title'][] = '快捷借款管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['feedback'] = array("快捷借款管理" => array(
        "列表" => 'msg1',
        "查看" => 'msg2',
        "删除" => 'msg3',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'msg1',
        'eqaction_dodel' => 'msg3',
        'eqaction_edit' => 'msg2',
    )
);
$i++;
$acl_inc[$i]['low_title'][] = '资金统计&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['capitalaccount'] = array("会员帐户" => array(
        "列表" => 'capital_1',
        "导出" => 'capital_2',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'capital_1',
        'eqaction_export' => 'capital_2',
    )
);
$acl_inc[$i]['low_leve']['capitalonline'] = array("充值记录" => array(
        "列表" => 'capital_3',
        "导出" => 'capital_4',
    ),
    "提现记录" => array(
        "列表" => 'capital_5',
        "导出" => 'capital_6',
    ),
    "data" => array(
        //网站设置
        'eqaction_charge' => 'capital_3',
        'eqaction_withdraw' => 'capital_5',
        'eqaction_chargeexport' => 'capital_4',
        'eqaction_withdrawexport' => 'capital_6',
    )
);
$acl_inc[$i]['low_leve']['remark'] = array("备注信息" => array(
        "列表" => 'rm1',
        "增加" => 'rm2',
        "修改" => 'rm3',
    ),
    "data" => array(
        'eqaction_index' => 'rm1',
        'eqaction_add' => 'rm2',
        'eqaction_doadd' => 'rm2',
        'eqaction_edit' => 'rm3',
        'eqaction_doedit' => 'rm3',
    )
);
$acl_inc[$i]['low_leve']['capitaldetail'] = array("会员资金记录" => array(
        "列表" => 'capital_7',
        "导出" => 'capital_8',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'capital_7',
        'eqaction_export' => 'capital_8',
    )
);
$arr = array(
        "查看" => 'capital_9',
        "成功借出明细" => 'capital_10',
        "已还款明细" => 'capital_12',
        "未还款明细" => 'capital_14',
        "提现手续费" => 'capital_20',
        "投标奖励" => 'capital_23',
        "线下充值奖励" => 'capital_25',
        "逾期已还款" => 'capital_32',
        "逾期未还款" => 'capital_33',
        "催收费" => 'capital_34',
        "逾期罚息" => 'capital_35',
        "债权转让手续费" => 'capital_36',
    );
if (isset($version) && $version['single']==1){
    $arr["散标借款管理费"] = 'capital_16';
    $arr["散标利息管理费"] = 'capital_18';
    $arr["散标借款利息"] = 'capital_21';
    $arr["散标续投奖励"] = 'capital_41';
}
if (isset($version) && $version['business']==1){
    $arr["直投借款管理费"] = 'capital_17';
    $arr["直投利息管理费"] = 'capital_19';
    $arr["直投借款利息"] = 'capital_22';
    $arr[$designer[6]."续投奖励"] = 'capital_42';
}
$acl_inc[$i]['low_leve']['capitalall'] = array("网站资金统计" => $arr,
    "data" => array(
        //网站设置
        'eqaction_index' => 'capital_9',
        'eqaction_borrow' => 'capital_10',
    
        'eqaction_repayment' => 'capital_12',

        'eqaction_norepayment' => 'capital_14',

        'eqaction_borrowfee' => 'capital_16',
        'eqaction_tborrowfee' => 'capital_17',
        'eqaction_insterestfee' => 'capital_18',
        'eqaction_tinsterestfee' => 'capital_19',
        'eqaction_withdrawfee' => 'capital_20',
        'eqaction_interest' => 'capital_21',
        'eqaction_tinterest' => 'capital_22',
        'eqaction_reward' => 'capital_23',
    
        'eqaction_linereward' => 'capital_25',
        
        'eqaction_expired' => 'capital_32',
        'eqaction_waitexpired' => 'capital_33',
        'eqaction_callfee' => 'capital_34',
        'eqaction_expiredfee' => 'capital_35',
        'eqaction_debtfee' => 'capital_36',
        'eqaction_xutou' => 'capital_41',
        'eqaction_txutou' => 'capital_42',
    )
);
$acl_inc[$i]['low_leve']['capitalrank'] = array("会员投资排行" => array(
        "列表" => 'capital_11',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'capital_11',
    )
);
//权限管理
$i++;
$acl_inc[$i]['low_title'][] = '权限管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['acl'] = array("权限管理" => array(
        "列表" => 'at73',
        "增加" => 'at74',
        "删除" => 'at75',
        "修改" => 'at76',
    ),
    "data" => array(
        //权限管理
        'eqaction_index' => 'at73',
        'eqaction_doadd' => 'at74',
        'eqaction_add' => 'at74',
        'eqaction_dodelete' => 'at75',
        'eqaction_doedit' => 'at76',
        'eqaction_edit' => 'at76',
    )
);
//管理员管理
$i++;
$acl_inc[$i]['low_title'][] = '管理员管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['adminuser'] = array("管理员管理" => array(
        "列表" => 'at77',
        "增加" => 'at78',
        "删除" => 'at79',
        "上传头像" => 'at99',
        "修改" => 'at80',
    ),
    "data" => array(
        //权限管理
        'eqaction_index' => 'at77',
        'eqaction_dodelete' => 'at79',
        'eqaction_header' => 'at99',
        'eqaction_memberheaderuplad' => 'at99',
        'eqaction_addadmin' => array(
            'at78' => array(//增加
                'POST' => array(
                    "uid" => 'G_NOTSET',
                ),
            ),
            'at80' => array(//修改
                'POST' => array(
                    "uid" => 'G_ISSET',
                ),
            ),
        ),
    )
);
//权限管理
$i++;
$acl_inc[$i]['low_title'][] = '数据库管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['db'] = array("数据库信息" => array(
        "查看" => 'db1',
        "备份" => 'db2',
        "查看表结构" => 'db3',
        "优化" => 'db9',
        "一键优化" => 'db10',
    ),
    "数据库备份管理" => array(
        "备份列表" => 'db4',
        "删除备份" => 'db5',
        "恢复备份" => 'db6',
        "打包下载" => 'db7',
        "操作短信验证" => 'db11',
    ),
    "清空数据" => array(
        "清空数据" => 'db8',
    ),
    "data" => array(
        //权限管理
        'eqaction_index' => 'db1',
        'eqaction_set' => 'db2',
        'eqaction_backup' => 'db2',
        'eqaction_showtable' => 'db3',
        'eqaction_baklist' => 'db4',
        'eqaction_delbak' => 'db5',
        'eqaction_restore' => 'db6',
        'eqaction_dozip' => 'db7',
        'eqaction_downzip' => 'db7',
        'eqaction_truncate' => 'db8',
        'eqaction_optimize' => 'db9',
        'eqaction_optimize_all' => 'db10',
        'eqaction_sendMsg'=>'db11',
         'eqaction_ajaxmsg'=>'db11',
         'eqaction_vercard'=>'db11' ,
    )
);
$i++;
$acl_inc[$i]['low_title'][] = '图片上传&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['kissy'] = array("图片上传" => array(
        "图片上传" => 'at81',
    ),
    "data" => array(
        //权限管理
        'eqaction_index' => 'at81',
    )
);


$i++;
$acl_inc[$i]['low_title'][] = '扩展管理&nbsp;&nbsp;&nbsp;&nbsp;';
$acl_inc[$i]['low_leve']['scan'] = array("安全检测" => array(
        "安全检测" => 'scan1',
    ),
    "data" => array(
        //权限管理
        'eqaction_index' => 'scan1',
        'eqaction_scancom' => 'scan1',
        'eqaction_updateconfig' => 'scan1',
        'eqaction_filefilter' => 'scan1',
        'eqaction_filefunc' => 'scan1',
        'eqaction_filecode' => 'scan1',
        'eqaction_scanreport' => 'scan1',
        'eqaction_view' => 'scan1',
    )
);
$acl_inc[$i]['low_leve']['mfields'] = array("文件管理" => array(
        "文件管理" => 'at82',
        "空间检查" => 'at83',
    ),
    "data" => array(
        //文件管理
        'eqaction_index' => 'at82',
        'eqaction_checksize' => 'at83',
    )
);

$acl_inc[$i]['low_leve']['bconfig'] = array("业务参数管理" => array(
        "查看" => 'fb1',
        "修改" => 'fb2',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'fb1',
        'eqaction_save' => 'fb2',
        'eqaction_ajaximg' => 'fb2',
    )
);
$acl_inc[$i]['low_leve']['leve'] = array("信用级别管理" => array(
        "查看" => 'jb1',
        "修改" => 'jb2',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'jb1',
        'eqaction_save' => 'jb2',
    )
);
$acl_inc[$i]['low_leve']['age'] = array("会员年龄别称" => array(
        "查看" => 'bc1',
        "修改" => 'bc2',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'bc1',
        'eqaction_save' => 'bc2',
    )
);
$acl_inc[$i]['low_leve']['hetong'] = array("合同居间方资料上传管理" => array(
        "查看" => 'ht1',
        "上传" => 'ht2',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'ht1',
        'eqaction_upload' => 'ht2',
    )
);
$acl_inc[$i]['low_title'][] = '在线客服管理';
$acl_inc[$i]['low_leve']['qq'] = array("QQ客服管理" => array(
        "列表" => 'qq5',
        "增加" => 'qq6',
        "删除" => 'qq7',
    ),
    "QQ群管理" => array(
        "列表" => 'qun5',
        "增加" => 'qun6',
        "删除" => 'qun7',
    ),
    "客服电话管理" => array(
        "列表" => 'tel5',
        "增加" => 'tel6',
        "删除" => 'tel7',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'qq5',
        'eqaction_addqq' => 'qq6',
        'eqaction_dodeleteqq' => 'qq7',
        'eqaction_qun' => 'qun5',
        'eqaction_addqun' => 'qun6',
        'eqaction_dodeletequn' => 'qun7',
        'eqaction_tel' => 'tel5',
        'eqaction_addtel' => 'tel6',
        'eqaction_dodeletetel' => 'tel7',
    )
);

//$acl_inc[$i]['low_title'][] = '在线通知管理';
$acl_inc[$i]['low_leve']['payonline'] = array("线上支付接口管理" => array(
        "查看" => 'jk1',
        "修改" => 'jk2',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'jk1',
        'eqaction_save' => 'jk2',
    )
);
$acl_inc[$i]['low_leve']['payoffline'] = array("线下充值银行管理" => array(
        "查看" => 'offline1',
        "修改" => 'offline2',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'offline1',
        'eqaction_saveconfig' => 'offline2',
    )
);
$acl_inc[$i]['low_leve']['msgonline'] = array("通知信息接口管理" => array(
        "查看" => 'jk3',
        "修改" => 'jk4',
		"显示app参数页"=>'app8',
		"修改app参数页"=>'app9',
    ),
    "通知信息模板管理" => array(
        "查看" => 'jk5',
        "修改" => 'jk6',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'jk3',
        'eqaction_save' => 'jk4',
        'eqaction_templet' => 'jk5',
        'eqaction_templetsave' => 'jk6',
		'eqaction_app_canshu'=>'app8',
		'eqaction_app_canshu_save'=>'app9',
    )
);
/* $acl_inc[$i]['low_leve']['baidupush']= array( "客户端云推送" =>array(
  "首页" 		=> 'bd27',
  "推送"     => 'bd26',

  ),
  "data" => array(
  //网站设置
  'eqaction_index'  => 'bd27',
  'eqaction_push_message_android'=>'bd26',

  )
  ); */
$acl_inc[$i]['low_leve']['tender'] = array("会员投标记录" => array(
        "列表" => 'capital_10',
        "导出" => 'capital_11',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'capital_10',
        'eqaction_export' => 'capital_11',
    )
);


if (isset($version) && $version['mobile']==1){
$i++;
$acl_inc[$i]['low_title'][] = '移动管理';	
$acl_inc[$i]['low_leve']['appsetup']=array("APP参数"=>array(
                          "首页"=>'app1',
						  "图片上传"=>'app2',
						  "未知"=>'app3',
						  "图片添加"=>'app4',
						  "添加广告"=>'app5',
						  "删除banner"=>'app6',
						  "删除app广告"=>'app7',
						   "app广告"=>'app1'
						 
	                       
					   ),
					"data"=>array(
					      'eqaction_index'  => 'app1',
						  'eqaction_dobanner'  => 'app2',
						  'eqaction_advertising'  => 'app3',
						  'eqaction_adbanner'=>'app4',
						  'eqaction_doadvertising'  => 'app5',
						  'eqaction_delbanner'=>'app6',
						  'eqaction_deladvertising'=>'app7',
						  'eqaction_listadvertising' => 'app1'	 
						 
						 )     
   
);
$acl_inc[$i]['low_leve']['baidupush'] = array("百度云推送" => array(
        "首页" => 'bd27',
        "消息推送" => 'bd26',
		"ios推送" =>'bd28',
		"选择发送" =>'bd29'
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'bd27',
        'eqaction_push_message_android' => 'bd26',
		'eqaction_push_message_ios' =>'bd28',
		'eqaction_push_androidoriso' =>'bd29'
    )
);

$acl_inc[$i]['low_leve']['feedback'] = array(
	"APP意见反馈" => array(
        "查看" => 'feedback1',
    ),
    "data" => array(
        //网站设置
        'eqaction_index' => 'feedback1',
        'eqaction_edit' => 'feedback1',
        'eqaction_dodel' => 'feedback1',
    )
);
}
if (isset($version) && $version['wechat']==1){
$i++;
$acl_inc[$i]['low_title'][] = '微信管理';	
$acl_inc[$i]['low_leve']['weixinadmin']=array("微信参数"=>array(
                        "首页"=>'app1',
                        "图片上传"=>'app2',
                        "未知"=>'app3',
                        "图片添加"=>'app4',
                        "添加广告"=>'app5',
                        "删除banner"=>'app6',
                        "删除app广告"=>'app7',
                        "app广告"=>'app1'
					   ),
                        "data"=>array(
                        'eqaction_index'  => 'app1',
                        'eqaction_dobanner'  => 'app2',
                        'eqaction_advertising'  => 'app3',
                        'eqaction_adbanner'=>'app4',
                        'eqaction_doadvertising'  => 'app5',
                        'eqaction_delbanner'=>'app6',
                        'eqaction_deladvertising'=>'app7',
                        'eqaction_listadvertising' => 'app1'
                        )
   
);
}
?>
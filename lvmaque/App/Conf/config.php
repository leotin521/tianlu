<?php
return array(
    // '配置项'=>'配置值'
    'APP_GROUP_LIST' => 'Home,Admin,Member,M,Agility,Mobile', // 分组
    'DEFAULT_GROUP' => 'Home', // 默认分组
    'DEFAULT_THEME' => 'default', // 使用的模板
    'TMPL_DETECT_THEME' => true, // 自动侦测模板主题
    'LANG_SWITCH_ON' => true, // 开启语言包
    'URL_MODEL' => 2, // 如果你的环境不支持PATHINFO 请设置为3,设置为2时配合放在项目入口文件一起的rewrite规则实现省略index.php/
    'URL_CASE_INSENSITIVE' => true, // 关闭大小写为true.忽略地址大小写
    'TMPL_CACHE_ON' => true, // 是否开启模板编译缓存,设为false则每次都会重新编译
    'TMPL_STRIP_SPACE' => false, // 是否去除模板文件里面的html空格与换行
    'REQUEST_VARS_FILTER' => true, // 参数安全过滤
    'APP_ROOT' => str_replace(array('\\', 'Conf', 'config.php', '//'), array('/', '/', '', '/'), dirname(__FILE__)), // APP目录物理路径
    'WEB_ROOT' => str_replace("\\", '/', substr(str_replace('\\Conf\\', '/', dirname(__FILE__)), 0, -8)), // 网站根目录物理路径
    'WEB_URL' => "http://" . $_SERVER['HTTP_HOST'], // 网站域名
    'CUR_URI' => $_SERVER['REQUEST_URI'], // 当前地址
    'URL_HTML_SUFFIX' => ".html", // 静态文件后缀
    'TMPL_ACTION_ERROR' => str_replace("\\", '/', substr(str_replace('\\Conf\\', '/', dirname(__FILE__)), 0, -8)) . "/Style/tip/tip.html", // 操作错误提示
    'TMPL_ACTION_SUCCESS' => str_replace("\\", '/', substr(str_replace('\\Conf\\', '/', dirname(__FILE__)), 0, -8)) . "/Style/tip/tip.html", // 操作正确提示
    'ERROR_PAGE' => '/Public/error.html',
    'LOAD_EXT_CONFIG' => 'crons', // 加载扩展配置文件
    'DB_PATH' => '/UF/data',
    'MCQ_USE' => false, // 是否使用mcq
    'SEND_MAIL' => 'send_mail',
    'AUTO_INVEST' => 'auto',
    'DATA_CACHE_TYPE' => 'File',
//    'DATA_CACHE_TYPE' => 'Memcache',
//    'MEMCACHE_HOST' => '',
//    'MEMCACHE_PORT'	=>	'11211',

    'HIDDEN_ACL_LIST' => false, // 后台是否隐藏无权限菜单列表
    // 数据库配置
    // grant all privileges on .* to @localhost identified by '';flush privileges;
    'DB_TYPE' => 'mysql',
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'lvmaque',
    'DB_USER' => 'root',
    'DB_PWD' => 'root',
    'DB_PORT' => '3306',
    'DB_PREFIX' => 'lzh_',

    //定义静态缓存
    'HTML_CACHE_ON'=>true, // 开启静态缓存
    'HTML_FILE_SUFFIX'  =>  '.html', // 设置静态缓存后缀为.html
    'HTML_CACHE_RULES'=> array(
//            'aboutus:index'=>array('{aid}','5'),
    ),
	
	

    // 'DB_PARAMS'			=>array('persist'=>true),
    // 数据库配置
    // 子域名配置
    'URL_ROUTER_ON' => true, // 开启路由规则
    'URL_ROUTE_RULES' => array(
        '/^m\/newwen\/(\d+).html$/' => 'm/newwen/news?id=:1', // 新闻页面
        '/^m\/help\/(\d+).html$/' => 'm/help/news?id=:1', // 新闻页面

        '/^tuiguang\/index.html$/' => 'Home/help/tuiguang', // 文章栏目页
        '/^service\/index.html$/' => 'Home/help/kf', // 文章栏目页
        '/^jifen\/index.html$/' => 'Home/help/jifen', // 文章栏目页
        '/^borrow\/tool\/index.html$/' => 'Home/tool/index', // 借款计算器
        '/^borrow\/tool\/tool(\d+).html$/' => 'Home/tool/tool:1',
        '/^borrow\/post\/([a-zA-z]+)\.html$/' => 'Home/borrow/post?type=:1', // 文章栏目页
        '/^M\/borrow\/postt\/([a-zA-z]+)\.html$/' => 'm/borrow/postt?type=:1', // 投标页面
        '/^tools\/tool.html$/' => 'Home/tool/index', //
        '/^tools\/([a-zA-z]+)\/(\d+).html$/' => 'Home/tool/index?:1=:2', // 投资计算器
        '/^invest\/index.html\?(.*)$/' => 'Home/invest/index?:1', // 文章栏目页
        '/^invest\/(\d+).html$/' => 'Home/invest/detail?id=:1', // 文章栏目页
        '/^invest\/(\d+).html\?(.*)$/' => 'Home/invest/detail?id=:1:2', // 积分

        '/^tinvest\/index.html\?(.*)$/' => 'Home/tinvest/index?:1', // 企业直投
        '/^tinvest\/(\d+).html$/' => 'Home/tinvest/tdetail?id=:1', // 企业直投详情
        '/^tinvest\/(\d+).html\?(.*)$/' => 'Home/tinvest/tdetail?id=:1:2', // 企业直投详情
        /*********微信手机端url**********/

        /*新增路由规则*/
        '/^m\/invest\/(\d+).html$/' => 'm/invest/detail?id=:1', // 微信端散标
        '/^m\/tinvest\/(\d+).html$/' => 'm/tinvest/tdetail?id=:1', // 微信端企业直投
        '/^m\/fund\/(\d+).html$/' => 'm/fund/tdetail?id=:1', // 微信端定投宝

        /*********微信手机端url**********/
        '/^fund\/index.html\?(.*)$/' => 'Home/fund/index?:1', // 定投宝栏目页
        '/^fund\/(\d+).html$/' => 'Home/fund/tdetail?id=:1', // 定投宝详情页
        '/^fund\/(\d+).html\?(.*)$/' => 'Home/tdetail?id=:1:2',

        '/^Market\/index.html\?(.*)$/' => 'Home/Market/index?:1',
        '/^Market\/(\d+).html$/' => 'Home/Market/detail?id=:1',
        '/^Market\/(\d+).html\?(.*)$/' => 'Home/Market/detail?id=:1:2', // 积分商城详情页
        '/^([a-zA-z]+)\/([a-zA-z]+).html(.*)$/' => 'Home/help/index:3', // 文章栏目页
        '/^([a-zA-z]+)\/(\d+).html$/' => 'Home/help/view?id=:2', // 文章内容页
        '/^([a-zA-z]+)\/id\-(\d+).html$/' => 'Home/help/view?id=:2&type=subsite', // 文章内容页
        '/^([a-zA-z]+)\/([a-zA-z]+)\/(\d+).html$/' => 'Home/help/view?id=:3', // 文章内容页
        '/^bangzhu\/index.html$/' => 'Home/bangzhu/index', // 文章栏目页
        '/^i\/([0-9a-zA-z]+)$/' => 'Member/common/register?invite=:1', //邀请注册链接
		'/^j\/([0-9a-zA-z]+)$/' => 'M/common/register?invite=:1', //邀请注册链接
		'/^j\/([0-9a-zA-z]+)\/p\/([0-9a-zA-z]+)$/' => 'M/common/register?invite=:1&popu=:2', //推广注册链接
        '/^b\/([0-9a-zA-z]+)$/' => 'Member/bonus/take?code=:1', //领取红包链接
		'/^c\/([0-9a-zA-z]+)$/' => 'M/rewardadm/take?code=:1', //领取红包链接
        '/^(register|login)[^a-zA-Z]?(.*)$/' => 'Member/common/:1', //登录页
    ),

    'SYS_URL' => array('admin', 'borrow', 'member', 'fund', 'invest', 'tinvest', 'tool', 'feedback', 'service', 'bid', 'Market', 'main', 'mcenter', 'debt', 'm', 'bangzhu'),

    'EXC_URL' => array('invest/tool/index.html', 'borrow/tool/index.html', 'borrow/tool/tool2.html', 'borrow/tool/tool2.html'),
    // 友情链接
    'FRIEND_LINK' => array(1 => '首页',
        2 => '内页',
    ),
    // 友情链接
    'TYPE_SET' => array(1 => '列表页',
        2 => '单页',
        3 => '跳转',
    ),
    'XMEMBER_TYPE' => array(1 => '普通借款者',
        2 => '优良借款者',
        3 => '风险借款者',
        4 => '黑名单',
    ),
    // 收费类型
    'MONEY_LOG' => array(
        3 => '会员充值', //线上
        4 => '提现冻结',
        5 => '撤消提现',
        6 => '投标冻结',  // 原6，37
        7 => '管理员操作',
        8 => '流标返还',
        9 => '会员还款本金', //`mxl 20150310`
        10 => '网站代还本金', //`mxl 20150310`
        11 => '偿还借款本金', //`mxl 20150310`
        12 => '提现失败',
        15 => '投标成功本金解冻',  // 原15，39
        16 => '复审未通过返还',
        17 => '借款入帐',
        18 => '借款管理费',
        19 => '借款保证金',
        20 => '投标奖励',  //原20，41
        21 => '支付投标奖励', // 原21，42
        23 => '利息管理费',
        24 => '还款完成解冻',
        25 => '实名认证费用',
        27 => '充值审核',
        28 => '投标成功待收利息',  // 原28，38
        29 => '提现成功',
        30 => '逾期罚息',
        31 => '催收费',
        32 => '线下充值奖励',
        36 => '提现通过，审核处理中',
        40 => '回款续投奖励',
        43 => '可用余额利息',
        45 => '网站抽奖奖励',
        46 => '购买债权',
        47 => '转让债权',
        48 => '转让债权手续费',
        60 => '支付债权当期利息',
        61 => '获得转让债权当期利息',
        62 => '债权转让减少待收资金',

        49 => '取消债权',
        50 => '偿还借款利息', //`mxl 20150310`
        51 => '会员还款利息', //`mxl 20150310`
        52 => '网站代还利息',  //`mxl 20150310`

        53 => '投资灵活宝',
        54 => '赎回灵活宝',

        55=> '发送红包',
        56=> '领取红包',//领取者增加，被领取者送去冻结金额
        57=> '红包过期返还' //未领取

    ),

    'REPAYMENT_TYPE' => array('1' => '每月还款',
        '2' => '一次性还款'
    ),

    'PAYLOG_TYPE' => array('0' => '充值未完成',
        '1' => '充值成功',
        '2' => '签名不符',
        '3' => '充值失败'
    ),

    'WITHDRAW_STATUS' => array('0' => '待审核',
        '1' => '审核通过,处理中',
        '2' => '已提现',
        '3' => '审核未通过'
    ),

    'FEEDBACK_TYPE' => array('1' => '借入借出',
        '2' => '资金账户',
        '3' => '推广奖金',
        '4' => '充值提现',
        '5' => '注册登录',
        '6' => '其他问题',
        '7' => '快速借款通道'
    ),
    // 积分类型
    'INTEGRAL_LOG' => array(1 => '还款积分',
        2 => '投资积分',
        3 => '消费积分',
        4 => '其它积分',
    ),
    // 信用积分类型
    'CREDIT_LOG' => array(1 => '上传资料审核',
        2 => '实名认证通过',
    ),
    'MARKET_LOG' => array(1 => '积分兑换',
        2 => '积分抽奖',
    ),

    'MARKET_WAY' => array(0 => '直接领取',
        1 => '折现',
        2 => '快递',
    ),

    'MARKET_TYPE' => array(0 => '未领取',
        1 => '正在发送中',
        2 => '已领取',
        3 => '领取失败',
    ),
    'EXP_TYPE'=> array(
        '1'=>'注册奖励',
        '2'=>'手机认证',
        '3'=>'实名认证',
        '4'=>'邀请奖励',
        '5'=>'一马当先',
        '6'=>'一锤定音',
        '7'=>'一鸣惊人',

        '98'=>'积分兑换',
        '99'=>'网站奖励',
    ),
);

?>
<?php
/*如果已使用了https安全协议，则可以放开这段注释
if ($_SERVER['HTTPS'] != "on") {
    $index = strstr($_SERVER['REQUEST_URI'],"index.php");
    if($index){
        $str = preg_replace('/\/index.php/', '', $_SERVER['REQUEST_URI']);
        $url = "https://" . $_SERVER["SERVER_NAME"] . $str;
        header("location:".$url);
    }
}*/

if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
    $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
    $___s = explode(".", $_SERVER['REQUEST_URI']);
    $____s = explode("?", $_SERVER['REQUEST_URI']);
    $_SERVER['PATH_INFO'] = $____s[0];
    $GLOBALS['is_iis'] = true;
}
// 缩短路径分隔符以助记
define("DS", DIRECTORY_SEPARATOR);
define('THINK_PATH', dirname(__FILE__) . '/CORE/');
define('APP_NAME', dirname(__FILE__) . 'App');
define('APP_PATH', dirname(__FILE__) . '/App/');
define('APP_DEBUG', 1);
define('APP_PUBLIC_PATH', dirname(__FILE__) . '/Public');
define('BAOFOOPUBLICKEY', dirname(__FILE__) . '/Style/NewWeChat/CER/bfkey_100000178@@100000916.pfx');//宝付
define('BAOFOOENCRIPTKEY', dirname(__FILE__) . '/Style/NewWeChat/CER/baofoo_pub.cer');//宝付

define('REAPALPUBLICKEY', dirname(__FILE__) . '/Style/NewWeChat/cert/itrus001.pem');//融宝
define('REAPALENCRIPTKEY', dirname(__FILE__) . '/Style/NewWeChat/cert/itrus001_pri.pem');//融宝
define('JDPAY_PATH', dirname(__FILE__) . '/Webconfig/jdpay/');
define('VERSION', '6.1.0');
define('LOG_PATH', APP_PATH.'/Logs/');
define('BUILD_DIR_SECURE', true);
define('DIR_SECURE_FILENAME', 'default.html');
define('DIR_SECURE_CONTENT', 'deney Access!');

if (isset($_SERVER['SHELL']) || (PHP_SAPI === 'cli')) { //load config file in the cli-mode
    $http_host = explode(DS, $_SERVER['PHP_SELF']);
    define('APP_DOMAIN', $http_host[1]);
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    $class = APP_PATH . 'Lib/Model/SwiftDispatcherModel.class.php';
    require($class);
    SwiftDispatcherModel::parse_uri();
    $path = SwiftDispatcherModel::$uri;
    $depr = '/';
    if (!empty($path)) {
        $params = explode($depr, trim($path, $depr));
    }

    !empty($params) ? $_GET['g'] = array_shift($params) : "";
    !empty($params) ? $_GET['m'] = array_shift($params) : "";
    !empty($params) ? $_GET['a'] = array_shift($params) : "";
} else {
    define('APP_DOMAIN', $_SERVER['HTTP_HOST']);
}
define('DOMAIN', 'http://' . APP_DOMAIN);

require(THINK_PATH . '/Core.php');



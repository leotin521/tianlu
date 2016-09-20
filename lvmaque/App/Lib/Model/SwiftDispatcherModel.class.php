<?php

class SwiftDispatcherModel {
    public static $uri;
    public static $default_controller = "site";

    public static function parse_uri() {
        if (PHP_SAPI === 'cli') {
            $options = self::detect_cli_options('uri', 'get', 'post');
            
            if (isset($options['uri'])) {
                $uri = $options['uri'];
            }
            
            if (isset($options['get'])) {
                parse_str($options['get'], $_GET);
            }
            
            if (isset($options['post'])) {
                parse_str($options['post'], $_POST);
            }
        } else {
            $uri = self::detect_uri();
        }
        
        $uri = preg_replace('#//+#', '/', $uri);
        $uri = preg_replace('#\.[\s./]*/#', '', $uri);
        $uri = trim($uri, '/');
        
        self::$uri = $uri;
    }
    
    public static function detect_cli_options($options) {
        $options = func_get_args();
        
        $values = array();
        
        // 第一个参数为当前执行文件
        for($i = 1; $i < $_SERVER['argc']; $i ++) {
            if (!isset($_SERVER['argv'][$i])) {
                break;
            }
            
            $opt = $_SERVER['argv'][$i];
            if (substr($opt, 0, 2) !== '--') {
                continue;
            }
            // 移除前缀 "--"
            $opt = substr($opt, 2);
            
            if (strpos($opt, '=')) {
                list($opt, $value) = explode('=', $opt, 2);
            } else {
                $value = NULL;
            }
            
            if (in_array($opt, $options)) {
                $values[$opt] = $value;
            }
        }
        
        return $values;
    }
    
    /**
     * 自动的使用 PATH_INFO,REQUEST_URI, PHP_SELF or REDIRECT_URL获取请求URI,
     * 
     * @return  string  URI
     * @throws  \Exception
     */
    public static function detect_uri() {
        // 不受base_url影响
        if (!empty($_SERVER['PATH_INFO'])) {
            $uri = $_SERVER['PATH_INFO'];
        } else {
            if (isset($_SERVER['REQUEST_URI'])) {
                // 提取path部分
                $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                $uri = rawurldecode($uri);
            } elseif (isset($_SERVER['PHP_SELF'])) {
                $uri = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['REDIRECT_URL'])) {
                $uri = $_SERVER['REDIRECT_URL'];
            } else {
                throw new \Exception('can not detect uri');
            }
            
            $base_url = rtrim(Swift_Core::$base_url, '/') . '/';
            $base_url = parse_url($base_url, PHP_URL_PATH);
            
            if (strpos($uri, $base_url) === 0) {
                // Remove the base URL from the URI
                $uri = substr($uri, strlen($base_url));
            }
        }
        
        return $uri;
    }
}
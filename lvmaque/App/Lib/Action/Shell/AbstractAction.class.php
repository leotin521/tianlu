<?php
/**
 *    1. cli执行
 *    2. 单进程判别
 *    3. IP访问限制
 */
abstract class AbstractAction extends Action
{
	/**
	 * 是否为开发模式
	 * @var int
	 */
	protected $develope_env = 0;

	/**
	 * 强制不使用cli 运行方式
	 * @var boolen
	 */
	protected $must_use_cli = true;

	protected static $fp = null;

	/**
	 * 初始化 各子类 需要继承子类
	 * @see Controller::init()
	 */
	public function init()
	{
		$this->check_cli();
	}

	/**
	 * 获取当前服务器IP
	 * TODO centos支持这种命令，需要注意有些操作系统如mac-osx等
	 * @return string
	 */
	protected function server_ip()
	{
		$str = "/sbin/ifconfig | grep 'inet addr' | awk '{ print $2 }' | awk -F ':' '{ print $2}' | head -1";
		$ip = exec($str);
		return $ip;
	}

	/**
	 * 检测是否cli执行
	 * 开发环境可以通过传入cli参数来执行
	 * @throws \Exception
	 */
	protected function check_cli()
	{
		if (PHP_SAPI !== 'cli' && (true === $this->must_use_cli)) {
			throw new \Exception('Only run it in php cli.');
		}
	}

	protected function log($cmd, $type, $msg)
	{
		$msg = '|' . strtoupper($cmd) . '-LOG|' . strtoupper($type) . "|$msg";
		error_log($msg);
		echo "{$msg}\r\n";
	}
}

<?php
class SignModel extends Model
{
	public static $sign_type = 'MD5';
	private static $sign_key = 'lvmaque20141205';  // TODO:这个在创建项目的时候需要在配置文件自动生成随机数，增加不同客户的保密安全性

	/**
	 * 设置加密类型
	 *
	 * @param string $sign_type
	 */
	public static function _set_sign_type($sign_type)
	{
		self::$sign_type = strtoupper($sign_type);
	}

	/**
	 * 返回MD5加密后的字符串
	 *
	 * @param array $arr_para 签名参数集合
	 * @param string $key 签名key
	 * @param boolean $is_urlencode
	 * @return string
	 */
	public static function generate_sign(array $arr_para, $key = '', $is_urlencode = false)
	{
		$sort_sign_data = self::arr_sort($arr_para);
		$arr_sign_data = self::filter_sign_data($sort_sign_data);
		$str_sign_data = self::get_link_string($arr_sign_data, $is_urlencode);

		switch (self::$sign_type) {
			case "MD5" :
				if (empty($key)) {
					$key = self::$sign_key;
				}
				return self::md5_sign($str_sign_data, $key);
			default :
				return false;
		}
	}

	/**
	 * 检验签名是否有效
	 *
	 * @param array $param
	 * @param string $type
	 *
	 */
	public static function check_sign($param, $sign_filter = null)
	{
		//特殊过滤
		if (!empty($sign_filter)) {
			foreach ($sign_filter as $value) {
				unset($param[$value]);
			}
		}
		//签名判断
		$sign_src = $param['sign'];
		$sign_gen = self::generate_sign($param, self::$sign_key);

		return $sign_gen == $sign_src;
	}

	/**
	 * 由签名算法要求，对数组排序
	 *
	 * @param array $arr_para
	 * @return array
	 */
	public static function arr_sort(array $arr_para)
	{
		ksort($arr_para);
		reset($arr_para);
		return $arr_para;
	}

	/**
	 * 过滤掉value 为null或empty的key，直接unset
	 *
	 * @param array $params
	 * @return array
	 */
	public static function array_filter(array $params)
	{
		$params = filter_null($params);
		$params = filter_empty($params);
		return $params;
	}

	/**
	 * 去除数组中的空值和签名参数
	 *
	 * @param array $arr_para
	 * @return array
	 */
	public static function filter_sign_data(array $arr_para)
	{
		$para_filter = array();
		$arr_para = self::array_filter($arr_para);

		foreach ($arr_para as $k => $v) {
			if ($k == "sign" || $k == "sign_type" || $v === "")
				continue;
			$para_filter[$k] = $arr_para[$k];
		}
		return $para_filter;
	}

	/**
	 * 传入一个数组，组成形如 < k1=val1&k2=val2 > 的link形式
	 *
	 * @param array $arr_para
	 * @param boolean $is_urlencode 是否需要urlencode处理
	 * @return string
	 */
	public static function get_link_string(array $arr_para, $is_urlencode = false)
	{
		$pairs = array();
		foreach ($arr_para as $k => $v) {
			if (true === $is_urlencode) {
				$pairs[] = $k . '=' . rawurlencode($v);
			} else {
				$pairs[] = "$k=$v";
			}
		}
		$sign_data = implode('&', $pairs);

		return $sign_data;
	}

	/**
	 * MD5 方式生成签名
	 *
	 * @param string $str_para
	 * @param string $key
	 * @return string
	 */
	public static function md5_sign($sign_data, $key)
	{
		return md5($sign_data . $key);
	}
}

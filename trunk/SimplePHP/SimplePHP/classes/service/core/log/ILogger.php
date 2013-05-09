<?php
namespace service\core\log;

interface ILogger {
	const LEVEL_DEBUG = 1;
	const LEVEL_INFO = 2;
	const LEVEL_WARN = 3;
	const LEVEL_ERROR = 4;
	const LEVEL_OFF = 10;
	
	/**
	 * 设置日志级别，可以是常量，也可以是字符串
	 * @param mix $level
	 */
	public function setLevel($level);
	
	/**
	 * 获取日志级别
	 * @return int 级别
	 */
	public function getLevel();
	
	/**
	 * 记录日志，第一个参数是级别，第二个参数是格式，之后的多个参数是变量
	 * 如：log($level, 'log format is %s and %s', $value1, $value2);
	 */
	public function log();
	
	/**
	 * 记录info级别的日志，第二个参数是格式，之后的多个参数是变量
	 * 如：info('some log data is %s and %s', $value1, $value2);
	 */
	public function info();
	
	/**
	 * 记录debug级别的日志，第二个参数是格式，之后的多个参数是变量
	 * 如：debug('some log data is %s and %s', $value1, $value2);
	 */
	public function debug();
	
	/**
	 * 记录warn级别的日志，第二个参数是格式，之后的多个参数是变量
	 * 如：warn('some log data is %s and %s', $value1, $value2);
	 */
	public function warn();
	
	/**
	 * 记录error级别的日志，第二个参数是格式，之后的多个参数是变量
	 * 如：info('some log data is %s and %s', $value1, $value2);
	 */
	public function error();
}
?>
<?php
namespace service\core\queue;
use \Exception;

/**
* 队列
*/
interface IQueue {
	
	/**
	* 往队列中写入数据
	* @param $name 队列名称
	* @param $data 数据
	*
	* @return array('code'=>错误代码, 'result'=>队列存入结果, 'pos'=>队列存入位置【没有错误时才有效】)
	* code 0: 没有错误，1：队列已满，-1：存入出错，-2：其它错误
	*/
	function put($name, $data);
	
	/**
	* 往队列中读出数据
	* @param $name 队列名称
	*
	* @return array('code'=>错误代码, 'data'=>数据, 'pos'=>队列读出位置【没有错误时才有效】)
	* code 0: 没有错误，1：队列已空，-2：其它错误
	*/
	function get($name);
}
?>
<?php
namespace service\core\queue;
use Exception;
use SimplePHP\IConfigurable;

/**
* 默认队列客户端的实现，连接httpsqs
*/
class HttpsqsQueue implements IQueue, IConfigurable {
	private $addr;
	private $port;
	private $auth;
	private $baseUrl;
	
	public function config($config) {
		$this->addr = $config->get('queue.httpsqs.address');
		$this->port = $config->get('queue.httpsqs.port');
		$this->auth = $config->get('queue.httpsqs.auth');
		$this->baseUrl = 'http://'.$this->addr.':'.$this->port.'/?';
	}
	
	/**
	* 往队列中写入数据
	* @param $name 队列名称
	* @param $data 数据
	*
	* @return array('code'=>错误代码, 'result'=>队列存入结果, 'pos'=>队列存入位置【没有错误时才有效】)
	* code 0: 没有错误，1：队列已满，-1：存入出错，-2：其它错误
	*/
	function put($name, $data) {
		$resp = http_post_data($this->getBaseUrl($name, 'put'), urlencode($data), NULL, $info);
		$rs = http_parse_message($resp);
		$code = -2;
		$data = 'error';
		if (is_object($rs)) {
			$data = $rs->body;
			switch ($rs->body) {
			case 'HTTPSQS_PUT_OK':
				$code = 0;
				break;
			case 'HTTPSQS_PUT_END':
				$code = 1;
				break;
			case 'HTTPSQS_PUT_ERROR':
				$code = -1;
				break;
			}
		}
		return array('code'=>$code, 'result'=>$data, 'pos'=>$code == 0 ? $rs->headers['Pos'] : NULL);
	}
	
	/**
	* 往队列中读出数据
	* @param $name 队列名称
	*
	* @return array('code'=>错误代码, 'data'=>数据, 'pos'=>队列读出位置【没有错误时才有效】)
	* code 0: 没有错误，1：队列已空，-2：其它错误
	*/
	function get($name) {
		$resp = http_get($this->getBaseUrl($name, 'get', '&charset=utf-8'), NULL, $info);
		$rs = http_parse_message($resp);
		$data = 'error';
		$code = -2;
		if (is_object($rs)) {
			$data = $rs->body;
			if ($rs->body == 'HTTPSQS_GET_END') {
				$code = 1;
			} else {
				$code = 0;
			}
		}
		return array('code'=>$code, 'data'=>$data, 'pos'=>$code == 0 ? $rs->headers['Pos'] : NULL);
	}
	
	function statusJson($name) {
		$resp = http_get($this->getBaseUrl($name, 'status_json'), NULL, $info);
		$rs = http_parse_message($resp);
		return $rs->body;
	}
	
	function status($name) {
		$resp = http_get($this->getBaseUrl($name, 'status'), NULL, $info);
		$rs = http_parse_message($resp);
		return $rs->body;
	}
	
	function reset($name) {
		$resp = http_get($this->getBaseUrl($name, 'reset'), NULL, $info);
		$rs = http_parse_message($resp);
		return $rs->body;
	}
	
	function view($name, $pos) {
		$resp = http_get($this->getBaseUrl($name, 'view', '&pos='.$pos), NULL, $info);
		$rs = http_parse_message($resp);
		return $rs->body;
	}
	
	private function getBaseUrl($name, $opt, $options='') {
		return $this->baseUrl.'name='.$name.'&opt='.$opt.'&auth='.$this->auth.$options;
	}
}
?>
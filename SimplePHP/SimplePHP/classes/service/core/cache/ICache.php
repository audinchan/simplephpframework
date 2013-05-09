<?php
namespace service\core\cache;

/**
* 缓存接口。
*/
interface ICache {
	/**
	* 将数据放入缓存。
	* @param $key 缓存项。
	* @param $data 要缓存的数据。
	* @param $expireSeconds 缓存过期时间（秒）。
	*/
	public function put($key, $data, $expireSeconds);
	
	/**
	*
	* 将数据放入缓存。
	* @param $key 缓存项。
	* @return 缓存中的数据。
	*/
	public function get($key);
	
	/**
	* 将数据放入缓存。
	* @param $key 缓存项。
	*/
	public function delete($key);
}
?>
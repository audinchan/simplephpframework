<?php
namespace service\core\cache;
use Memcached;
use SimplePHP\IConfigurable;

class MMCache implements ICache, IConfigurable {
	private $m;
	private $servers;
	
	public function config($config) {
		$this->servers = $config->get('cache.MMCache.servers');
		$this->m = new Memcached();
		$this->m->addServers($this->servers);
	}
	
	public function put($key, $data, $expireSeconds) {
		$this->m->set($key, $data, $expireSeconds);
	}
	
	public function get($key) {
		return $this->m->get($key);
	}
	
	public function delete($key) {
		$this->m->delete($key);
	}
}
?>
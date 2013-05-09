<?php
namespace service\core\cache;
use SimplePHP\IConfigurable;

class FileCache implements ICache, IConfigurable {
	private $cacheDir;
	
	public function config($config) {
		$this->cacheDir = $config->get('cache.FileCache.cacheDir');
	}
	
	public function put($key, $data, $expireSeconds) {
		if (!is_dir($this->cacheDir)) {
			@mkdir($this->cacheDir, 0755, true);
		}
		@file_put_contents($this->cacheDir.$key, $expireSeconds."\r\n".serialize($data));
	}
	
	public function get($key) {
		$filename = $this->cacheDir.$key;
		if (is_file($filename)) {
			$ftime = filectime($filename);
			$fh = @fopen($filename, 'r');
			if ($fh) {
				$expireSeconds = @fgets($fh);
				if ($expireSeconds) {
					$expireSeconds = intval($expireSeconds);
					if ((time()- $expireSeconds) < $ftime) {
						$content = '';
						while (!feof($fh)) {
						  $content .= fread($fh, 8192);
						}
						return unserialize($content);
					}
				}
				@fclose($fh);
			}
		}
		// elsewhere
		return false;
	}
	
	public function delete($key) {
		$filename = $this->cacheDir.$key;
		if (is_file($filename)) {
			@unlink($filename);
		}
	}
}
?>
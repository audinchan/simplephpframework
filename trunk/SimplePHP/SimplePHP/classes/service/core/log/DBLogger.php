<?php
namespace service\core\log;
use SimplePHP\IConfigurable, SimplePHP\IServiceCallerAware, SimplePHP\Services;

class DBLogger implements ILogger, IConfigurable, IServiceCallerAware {
	/**
	* @service(database)
	*/
	private $db;
	
	private $logTable;
	
	private $logLevel;
	public static $LEVEL_NAMES = array(1=>'debug', 2=>'info', 3=>'warn', 4=>'error', 10=>'off');
	public static $LEVEL_NAMES_REVERSE = array('debug'=>1, 'info'=>2, 'warn'=>3, 'error'=>4, 'off'=>10);
	
	private $callerClass;
	
	public function config($config) {
		$this->logTable = $config->get('logger.table', 'log');
		$this->setLevel($config->get('logger.level', 'off'));
	}
	
	public function setCallerInfo($obj) {
		$this->callerClass = get_class($obj);
	}
	
	public function setLevel($level) {
		if (is_numeric($level)) {
			$this->logLevel = $level;
		} else {
			$this->logLevel = self::$LEVEL_NAMES_REVERSE[$level];
		}
	}
	
	public function getLevel() {
		return $this->logLevel;
	}
	
	protected function _doLog($level, $format, $values) {
		if ($level >= $this->logLevel) {
			array_unshift($values, $format);
			$msg = call_user_func_array('sprintf', $values);
			$this->db->execQuery('insert into `'.$this->logTable.'` (logtime, level, msg, filename) values(now(), ?, ?, ?)', self::$LEVEL_NAMES[$level], $msg, $this->callerClass);
		}
	}
	
	public function log() {
		$args = func_get_args();
		$level = array_shift($args);
		$format = array_shift($args);
		$this->_doLog($level, $format, $args);
	}
	
	public function info() {
		$args = func_get_args();
		$format = array_shift($args);
		$this->_doLog(self::LEVEL_INFO, $format, $args);
	}
	
	public function debug() {
		$args = func_get_args();
		$format = array_shift($args);
		$this->_doLog(self::LEVEL_DEBUG, $format, $args);
	}
	
	public function warn() {
		$args = func_get_args();
		$format = array_shift($args);
		$this->_doLog(self::LEVEL_WARN, $format, $args);
	}
	
	public function error() {
		$args = func_get_args();
		$format = array_shift($args);
		$this->_doLog(self::LEVEL_ERROR, $format, $args);
	}
}
?>
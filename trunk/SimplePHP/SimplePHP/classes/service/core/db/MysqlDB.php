<?php
namespace service\core\db;
use PDO;
use PDOException;
use SimplePHP\IConfigurable, SimplePHP\ITransactionManager, SimplePHP\Services;
//error_reporting( E_ALL ); ini_set( 'display_errors', 'on' );

class MysqlDB implements IDatabase, IConfigurable, ITransactionManager {
	protected $dsn;
	protected $user;
	protected $password;
	
	protected $charset;
	protected $persistent;
	protected $bufferQuery;
	protected $compress;
	
	protected $db;

	public function config($config) {
		$this->dsn = $config->get('db.dsn');
		$this->user = $config->get('db.user');
		$this->password = $config->get('db.password');
		
		$this->charset = $config->get('db.charset', 'utf8');
		$this->persistent = $config->get('db.persistent', false);
		$this->bufferQuery = $config->get('db.bufferQuery', true);
		$this->compress = $config->get('db.compress', true);
		
	}
	
	protected function connect2db() {
		if (is_object($this->db)) return;
		
		$dbInfos = array(
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '".$this->charset."'",
			PDO::ATTR_PERSISTENT => $this->persistent,
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => $this->bufferQuery,
			PDO::MYSQL_ATTR_COMPRESS => $this->compress
		);
		try {
			$this->db = new PDO($this->dsn, $this->user, $this->password, $dbInfos);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
		} catch (PDOException $e) {
			//// can not connect to db
			// TODO log or something to do
			var_dump($e);
		}
	}

	protected function getArgs4db($args) {
		$result = array();
		$result['fetchMode'] = false;
		if ($args[0] == 'fetchMode') {
			array_shift($args);
			$result['fetchMode'] = array_shift($args);
		}
		$result['sql'] = $args[0];
		$argnum = count($args);
		if ($argnum > 1) {
			$dbargs = array();
			for ($i=1; $i<$argnum; $i++) {
				array_push($dbargs, $args[$i]);
			}
			$result['args'] = $dbargs;
		} else {
			$result['args'] = false;
		}
		return $result;
	}
	
	public function execQuery() {
		$this->connect2db();
		$args = $this->getArgs4db(func_get_args());
		$stmt = $this->db->prepare($args['sql']);
		if ($args['fetchMode']) $stmt->setFetchMode($args['fetchMode']);
		if (is_array($args['args'])) {
			$stmt->execute($args['args']);
		} else {
			$stmt->execute();
		}
		return $stmt;
	}
	
	public function row() {
		$stmt = call_user_func_array(array($this, 'execQuery'), func_get_args());
		return $stmt->fetch();
	}
	
	public function col() {
		$stmt = call_user_func_array(array($this, 'execQuery'), func_get_args());
		return $stmt->fetchColumn();
	}
	
	public function rows() {
		$stmt = call_user_func_array(array($this, 'execQuery'), func_get_args());
		return $stmt->fetchAll();
	}
	
	public function rowsWithParams($sql, $params=null) {
		$this->connect2db();
		$stmt = $this->db->prepare($sql);
		if ($params != null && is_array($params)) {
			$stmt->execute($params);
		} else {
			$stmt->execute();
		}
		return $stmt->fetchAll();
	}
	
	public function execNamedQuery($sql, $args) {
		$this->connect2db();
		$stmt = $this->db->prepare($sql);
		$stmt->execute($args);
		return $stmt;
	}
	
	public function beginTransaction() {
		$this->db->beginTransaction();
	}
	
	public function commit() {
		$this->db->commit();
	}
	
	public function rollback() {
		$this->db->rollback();
	}
	
	public function inTransaction() {
		return $this->db->inTransaction();
	}
	
	public function __call($name, $args) {
		$this->connect2db();
		$reflector = Services::reflector($this);
		if (!$reflector->hasMethod($name)) {
			// forward to pdo method
			return call_user_func_array(array($this->db, $name), $args);
		}
	}
	
	public function uuid() {
		return $this->col('select uuid()');
	}
	
	public function uuid32() {
		return str_replace('-', '', $this->uuid());
	}
}
?>
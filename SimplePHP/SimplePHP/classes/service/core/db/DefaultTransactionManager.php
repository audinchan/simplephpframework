<?php
namespace service\core\db;
use SimplePHP\ITransactionManager;

class DefaultTransactionManager implements ITransactionManager {
	/**
	* @service(database)
	*/
	private $db;
	
	public function beginTransaction() {
		if (!$this->inTransaction()) $this->db->beginTransaction();
	}
	
	public function commit() {
		if ($this->inTransaction()) $this->db->commit();
	}
	
	public function rollback() {
		if ($this->inTransaction()) $this->db->rollback();
	}
	
	public function inTransaction() {
		return $this->db->inTransaction();
	}
}
<?php
namespace action;

class example3 {
	/**
	* @service(database)
	*/
	private $db;
	
	public function execute() {
		$user = $this->db->row('select login_name from test where id=?', 1);
		$loginname = $this->db->col('select login_name from test where id=?', 1);
		echo 'loginname: '.$user['login_name'].'<br/>';
		echo 'loginname: '.$loginname;
	}
}
?>
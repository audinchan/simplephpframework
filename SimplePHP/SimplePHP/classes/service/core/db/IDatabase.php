<?php
namespace service\core\db;

interface IDatabase {
	
	/**
	* 从数据库查询一行数据，至少输入一个参数
	* @param 第一个参数是SQL语句，后面的参数是变量。
	* @return 一维数组
	* 范例：
	* row($sql, $v1, $v2, $v3)
	* row('select * from user where id=1');
	* row('select * from user where loginname=?', $loginname);
	*/
	public function row();
	
	// 从数据库查询一行一列的数据
	public function col();
	
	// 从数据库查询多行数据，返回二维数组
	public function rows();
	
	// 从数据库查询多行数据，返回二维数组
	public function rowsWithParams($sql, $params=null);
	
	// 执行SQL，返回Statement
	public function execQuery();
	
	// 命名SQL，返回Statement
	public function execNamedQuery($sql, $args);
}
?>
<?php
namespace action;

class example2 {
	// 自动从Request参数中获取
	public $username;
	
	public function execute() {
		// 返回一个字符串告诉框架视图名称，不加文件扩展名。
		return 'example2';
	}
}
?>
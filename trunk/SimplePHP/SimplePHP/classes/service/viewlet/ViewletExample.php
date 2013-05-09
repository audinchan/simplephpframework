<?php
namespace service\viewlet;

class ViewletExample {
	public $username;
	public $otherparam;
	
	// 每个Viewlet必须有execute方法，框架会调用此方法
	public function execute() {
		return 'ViewletExample';
	}
}
?>
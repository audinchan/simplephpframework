<?php
//error_reporting( E_COMPILE_ERROR|E_RECOVERABLE_ERROR|E_ERROR|E_CORE_ERROR ); 
error_reporting( E_ALL ); ini_set( 'display_errors', 'on' );
$config = array(
);

// 开始加载SimplePHP框架
require_once 'SimplePHP' . DIRECTORY_SEPARATOR . 'kernel.php';
use \SimplePHP\Services as spf;
// 加载配置
spf::loadConfig($config);

// 执行框架
spf::execute();
?>

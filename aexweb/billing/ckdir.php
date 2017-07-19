<?php
error_reporting(E_ALL ^ E_NOTICE);//ignore the "notice" error

define("__OEMROOT__",dirname(__FILE__));
//包含API和Billing的配置信息
require_once (dirname(dirname(__FILE__)).'/config/config.php');
require_once (__EZLIB__.'/billing/billing.php');			
require_once (__EZLIB__.'/common/remove_bom.php');			

if (isset($_GET['dir'])){ //config the basedir
	$basedir=$_GET['dir'];
}else{
	$basedir = '.';
}
 
$auto = 1; 
 
checkdir($basedir);

?>
<?php
error_reporting(E_ALL ^ E_NOTICE);//ignore the "notice" error

define("__OEMROOT__",dirname(__FILE__));
//包含API和Billing的配置信息
require_once (dirname(dirname(__FILE__)).'/config/config.php');
require_once (__EZLIB__.'/billing/billing.php');			

$module_id = find_params(array('module','moduleId'),'crm');
$method = find_params(array('a','act','method','action','func'),'login');

do_request($module_id,$method);

function do_request($module_id,$method){
	$config = new config();
	$billing = new billing_os($config);

	//var_dump($_REQUEST);
	$billing->um_request($module_id,$method);
}

?>

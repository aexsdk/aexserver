<?php
error_reporting(E_ALL ^ E_NOTICE);//ignore the "notice" error

define("__OEMROOT__",dirname(__FILE__));
//包含API和Billing的配置信息
require_once (dirname(dirname(__FILE__)).'/config/config.php');
require_once (__EZLIB__.'/billing/billing.php');			

$config = new config();
$billing = new billing_os($config);

$billing->smarty->compile_dir = dirname(__FILE__). '/templates_c/';
$billing->smarty->cache_dir = dirname(__FILE__).'/cache/';
$billing->smarty->config_dir = dirname(__FILE__).'/config/';

$billing->page_uphone_ad();

?>

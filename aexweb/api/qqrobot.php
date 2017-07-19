<?php
error_reporting(E_ALL ^ E_NOTICE);//ignore the "notice" error

require_once (dirname(__FILE__).'/config/api_config.php');
require_once (__EZLIB__.'/common/api_common.php');				
require_once (__EZLIB__.'/common/api_radius_funcs.php');				
require_once (__EZLIB__.'/common/api_log_class.php');				

require_once __EZLIB__.'/common/api_billing_mssql.php';
require_once __EZLIB__.'/common/api_billing_pgdb.php';
require_once __EZLIB__.'/common/api_ivr.php';

$config = new class_config();
//获取基本参数
$p_params = array(
	'run_start_time'=>microtime(),
	'gprs_caller'=>$_SERVER['HTTP_X_UP_CALLING_LINE_ID'],
	'gprs_agent'=>$_SERVER['HTTP_USER_AGENT'],
	'api_version' => $_REQUEST['v'],
	'api_o' =>$_REQUEST['o'],
	'api_lang' => $_REQUEST['lang'],
	'api_p' => $_REQUEST['p'],
	'api_action'=> $_REQUEST['a'],
	'api_params'=>array('action'=> $_REQUEST['a']),
	'common-lang-path'=> __EZLIB__.'',
	'lang-path' => __EZLIB__,
	'common-path'=> __EZLIB__.'/common',
	'config' => $config
);

$ExternalId = trim($_REQUEST["ExternalId"]);
$Sender = trim($_REQUEST["Sender"]);
$Message = trim($_REQUEST["Message"]);

if(empty($Message) or $ExternalId != 0)exit;		

//var_dump($p_params);
$config->dest_mod = 'IVR_API';		//重置模块名为OPHONE模块
$api_object = new class_api($config,$p_params);

	//$url = "http://202.134.124.228:8181";
	//$data = "ExternalId=$ExternalId&Sender=%s&Message=$Message";
	/*$ch = curl_init();
	$st = microtime();
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 35);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 35);
	curl_setopt($ch, CURLOPT_POSTFIELDS, sprintf($data,"1611852"));
	curl_exec($ch);
	curl_setopt($ch, CURLOPT_POSTFIELDS, sprintf($data,"614074427"));
	curl_exec($ch);*/
	$r = run_cmd($api_object,$Sender,$Message);
	
	echo $r;//array_to_string('\r\n',$r);

?>

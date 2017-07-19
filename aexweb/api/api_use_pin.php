<?php
error_reporting(E_ALL ^ E_NOTICE);//ignore the "notice" error

require_once (dirname(__FILE__).'/config/api_config.php');
require_once (__EZLIB__.'/common/api_common.php');				
require_once (__EZLIB__.'/common/api_radius_funcs.php');				
require_once (__EZLIB__.'/common/api_log_class.php');				

$v_lang = $_REQUEST['lang'];
if (empty($v_lang) or $v_lang == 'zh' or $v_lang == '*#0086#' or substr($v_lang, 0, 1) == '*')
	$v_lang = 'zh-CN';
//获取基本参数
$p_params = array(
	'run_start_time'=>microtime(),
	'gprs_caller'=>$_SERVER['HTTP_X_UP_CALLING_LINE_ID'],
	'gprs_agent'=>$_SERVER['HTTP_USER_AGENT'],
	'api_version' => $_REQUEST['v'],
	'api_o' =>$_REQUEST['o'],
	'api_lang' => strtolower($v_lang),
	'api_p' => $_REQUEST['p'],
	'api_action'=> 'use-pin',
	'api_params'=>array('action'=> 'use-pin'),
	'common-lang-path'=> __EZLIB__.'',
	'lang-path' => __EZLIB__.'/',
	'common-path'=> __EZLIB__.'/common'
);
$config = new class_config();
$config->dest_mod = 'use-pin';		//重置模块名为OPHONE模块
$api_obj = new class_api($config,$p_params);
$api_object->no_log = FALSE;

$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
$r = $billing_db->billing_use_pin($_REQUEST['card']);
$robj = new stdClass();
if(is_array($r)){
	$robj->return_code = $api_obj->return_code;
	$robj->balance = $r['Balance'];
	$robj->password = $r['Password'];
	$api_obj->push_return_data('data',array_to_string(',',$r));
	//var_dump($r);
	//echo sprintf('{"return_code":"%d","password":"%s"}',$robj->return_code,$r['Password']);
}else{
	$robj->return_code = -10;
	//echo sprintf('{"return_code":"%d"}',$robj->return_code);
}

echo $api_obj->json_encode($robj);

?>

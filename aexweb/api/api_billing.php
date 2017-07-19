<?php
error_reporting(E_ALL ^ E_NOTICE);//ignore the "notice" error

require_once (dirname(__FILE__).'/config/api_config.php');
require_once (__EZLIB__.'/common/api_common.php');				
require_once (__EZLIB__.'/common/api_radius_funcs.php');				
require_once (__EZLIB__.'/common/api_log_class.php');	
require_once (__EZLIB__.'/common/api_json.php');	

if(!function_exists('json_encode')){
	$GLOBALS['JSON_OBJECT'] = new Services_JSON();
	
	function json_encode($value){
		return $GLOBALS['JSON_OBJECT']->encode($value);
	}
   
	function json_decode($value){
		return $GLOBALS['JSON_OBJECT']->decode($value);
	}
}			

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
//var_dump($p_params);
$config = new class_config();
$config->dest_mod = 'UPHONE';		//重置模块名为OPHONE模块
$api_object = new class_api($config,$p_params);
$action = $api_object->decode_param($api_object->get_md5_key());

do_action($api_object);

/*
	执行API操作，操作是在modules目录下的api_开头加上action名称的PHP文件。引用这个文件后我们在这个文件里具体实现action操作
*/
function do_action($api_object){
	$fn =  __EZLIB__."/modules/api_".$api_object->params['api_params']['action'].".php";	
	
	//echo "<br>".$fn."<br>";
	if(file_exists($fn)){
		//action对应的PHP文件存在，包含此文件。在文件中应该包含action的实现
		require_once $fn;
	}else{
		//$fn = dirname(dirname(__FILE__)).'/ezbilling_api/modules/api_".$p_params['api_params']['action'].".php");
		//no this action function
		$api_object->return_code = _DB_NO_ACTION_FILE_;
		$api_object->write_warning("No this action file:$fn");
		exit;
	}
}

?>

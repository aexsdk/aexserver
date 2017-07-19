<?php
/*
 *filename:update.php
 *discript: get config info by update action
 *author:se7en
 *time:2009-06-14
 */
error_reporting(E_ALL ^ E_NOTICE);//ignore the "notice" error

require_once (dirname(__FILE__) . "/config/api_config.php");
require_once (__EZLIB__.'/common/api_common.php');
require_once (__EZLIB__.'/common/api_log_class.php');

//获取基本参数
$p_params = array(
		'run_start_time'=>microtime(),
		'gprs_caller'=>$_SERVER['HTTP_X_UP_CALLING_LINE_ID'],
		'gprs_agent'=>$_SERVER['HTTP_USER_AGENT'],
		'api_version' => $_REQUEST['v'],
		'api_o' =>$_REQUEST['o'],
		'api_lang' => $_REQUEST['lang'],
		'api_p' => $_REQUEST['p'],
		'api_action'=>'',
		'common-lang-path'=> __EZLIB__,
		'lang-path' => __EZLIB__.'/mlm',
		'common-path'=> __EZLIB__.'/common'
	);
//var_dump($p_params);
	
$config = new class_config();
$config->dest_mod = 'MLM';		//重置模块名为OPHONE模块
$api_object = new class_api($config,$p_params);
$api_object->md5_key = "ophone";
$action = $api_object->decode_param($api_object->md5_key);//$api_object->get_md5_key());
//echo 'md5='.$api_object->md5_key."\r\n";
$p_params['api_params']['action'] = $action;
//var_dump($api_object);
$api_object->write_hint(array_to_string("\r\n",$api_object->error_obj->error_array));
//调用action操作函数，实现具体的操作
do_update($api_object);
	
/*
	执行API操作，操作是在modules目录下的api_开头加上action名称的PHP文件。引用这个文件后我们在这个文件里具体实现action操作
*/
function do_update($api_object){
	$fn =  (__EZLIB__."/mlm/modules/api_update.php");	
		//echo "<br>".$fn."<br>";
	if(file_exists($fn)){
		//action对应的PHP文件存在，包含此文件。在文件中应该包含action的实现
		require_once $fn;
	}else{
		//no this action function
		//no this action function
		$api_object->return_code = _DB_NO_ACTION_FILE_;
		$api_object->write_warning("No this action file:$fn");
		exit;
	}
}

?>

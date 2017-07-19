<?php
error_reporting(E_ALL ^ E_NOTICE);//ignore the "notice" error

require_once (dirname(__FILE__).'/config/api_config.php');
require_once (__EZLIB__.'/common/api_common.php');				
require_once (__EZLIB__.'/common/api_radius_funcs.php');				
require_once (__EZLIB__.'/common/api_log_class.php');				

try{
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
		'api_action'=> $_REQUEST['a'],
	    //'api_r_action'=>$_REQUEST['r_a'],
		'api_params'=>array('action'=> $_REQUEST['a']),
		'common-lang-path'=> __EZLIB__.'',
		'lang-path' => __EZLIB__.'/ophone',
		'common-path'=> __EZLIB__.'/common'
	);
    //var_dump($p_params);
	$config = new class_config();
	$config->dest_mod = 'OPHONE';		//重置模块名为OPHONE模块
	$api_object = new class_api($config,$p_params);
	//$action = $api_object->decode_param($api_object->get_md5_key());
	
	if (!empty($_REQUEST['key']))
		$md5_key = $_REQUEST['key'];
	else 
		$md5_key = $api_object->get_md5_key();
	$action = $api_object->decode_param($md5_key);
 
	if (strpos($_SERVER['HTTP_ACCEPT'], 'xhtml+xml') > 0){
    	header("Content-type: text/vnd.wap.wml");
    	echo ("<wml> <card> <p>\n");	
	}
	
	//调用action操作函数，实现具体的操作
	if($_REQUEST['a'] != 'update')
		do_action($api_object);
	else{
		//var_dump($api_object->return_data);
		$api_object->push_return_data("NEED-UPDATE","您需要更新配置才可以继续使用。You need select ok for update your config.");
		//var_dump($api_object->return_data);
        $api_object->write_response();	
	}
	if (strpos($_SERVER['HTTP_ACCEPT'], 'xhtml+xml') > 0 ){
		echo ("</p> </card> </wml>\n");
	}
} catch ( Exception $e ) {
	echo sprintf("\r\n<UTONE><R>0</R><M>服务器异常：%s</M></UTONE>",$e->getMessage ());
}

/*
	执行API操作，操作是在modules目录下的api_开头加上action名称的PHP文件。引用这个文件后我们在这个文件里具体实现action操作
*/
function do_action($api_object){
	$fn =  __EZLIB__."/ophone/modules/api_".$api_object->params['api_params']['action'].".php";
	
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

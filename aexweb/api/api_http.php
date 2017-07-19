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

/*
 * 请求MD5校验
*/
function request_check_md5()
{
	$key = $_REQUEST['key'];
	$s = '';
	foreach ($_REQUEST as $k => $v){
		$s .= $v;
	}
	$ip = get_request_ipaddr();
	$crc = $_REQUEST['crc'];
	$md5 = md5($s.$ip.$key);
	return $md5 === $crc;
}

function get_message_callback($api_obj, $context, $msg) {
	return sprintf ( $msg, $api_obj->return_code);
}

/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj, $context) {
	//$api_obj->write_trace(0,'Run here');
	if (strpos($api_obj->return_code,':') >0 ) {
		$return_code = explode(':',$api_obj->return_code);
		$success = $return_code[1] > 0;
		$code = $return_code[1];
	}else{
		$success = $api_obj->return_code > 0;
		$code = $api_obj->return_code;
	}
	
	$api_obj->push_return_data ( 'success', $success );
	$api_obj->push_return_data ( 'message', $api_obj->get_error_message ( $api_obj->return_code ), '' );
	$api_obj->push_return_data ( 'response_code',$code);
	
	$resp = $api_obj->write_return_params_with_json ();
	return $resp;
}

try{
	$v_lang = $_REQUEST['lang'];
	if (empty($v_lang) or $v_lang == '*#0086#' or substr($v_lang, 0, 1) == '*')
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
		'api_params'=>array('action'=> $_REQUEST['a']),
		'common-lang-path'=> __EZLIB__.'',
		'lang-path' => __EZLIB__.'/httpapi',
		'common-path'=> __EZLIB__.'/common'
	);
    //var_dump($p_params);
	$config = new class_config();
	$config->dest_mod = 'httpapi';		//重置模块名为665API模块
	$api_object = new class_api($config,$p_params);
	$api_object->set_callback_func ( get_message_callback, write_response_callback, $api_object );
	
	if (!empty($_REQUEST['key']))
		$md5_key = $_REQUEST['key'];
	else 
		$md5_key = $api_object->get_md5_key();
	$action = $api_object->decode_param($md5_key);
	if(isset($api_object->config->allow_ips)){
		$ip = get_request_ipaddr();
		foreach ($api_object->config->allow_ips as $aip => $desc){
			if($ip == $aip){
				do_action($api_object);
				return;
			}
		}
		if(isset($api_object->config->allow_ips['default']))
		{
			do_action($api_object);
			return;
		}
		echo '{success:false,message:"not allow the request ip."}';		
	}else{
		do_action($api_object);
	}
} catch ( Exception $e ) {
	echo sprintf("\r\n<UTONE><R>0</R><M>服务器异常：%s</M></UTONE>",$e->getMessage ());
}

/*
	执行API操作，操作是在modules目录下的api_开头加上action名称的PHP文件。引用这个文件后我们在这个文件里具体实现action操作
*/
function do_action($api_object){
	$fn =  __EZLIB__."/httpapi/modules/api_".$api_object->params['api_params']['action'].".php";
	
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

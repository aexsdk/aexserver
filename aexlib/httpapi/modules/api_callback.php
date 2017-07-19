<?php
	api_action($api_object);


function get_message_cb_callback($api_obj, $context, $msg) {
	return sprintf ( $msg, $api_obj->return_code,$_REQUEST['callee']);
}
	
//查询CDR记录
function api_action($api_obj){
	$pno = empty($_REQUEST['pno'])? '' : $_REQUEST['pno'];
	$pin = empty($_REQUEST['pin'])? '' : $_REQUEST['pin'];
	$pass = empty($_REQUEST['pass'])? '' : $_REQUEST['pass'];
	$caller = empty($_REQUEST['caller'])? '' : $_REQUEST['caller'];
	$callee = empty($_REQUEST['callee'])? '' : $_REQUEST['callee'];
	$async = isset($_REQUEST['async'])?$_REQUEST['async']:'false';
	//$action= 'invite';
	$prefix = '0086';
	
	$api_obj->set_callback_func ( get_message_cb_callback, write_response_callback, $api_obj );
	if(isset($_REQUEST['pin']) && isset($_REQUEST['pass'])){
		//如果请求提供了pin和pass则使用请求中的pin和pass,Pass为使用key+pin作为密钥的MD5加密串
		if (isset($_REQUEST['key']))
			$pass = api_decrypt($_REQUEST['pass'],$_REQUEST['key'].$_REQUEST['pin']);
		else 	
			$pass = $_REQUEST['pass'];
	}
	//设置回调函数
	$params = array(
		'pin' => $pin, 
		'pass' => trim($pass), 
		'pno' => check_phone_number ( $pno, $prefix), 
		'caller' => $caller, 
		'callerip' => get_request_ipaddr(),
		'callee' => $callee, 
		'o' => '00', 
		'lang' => $api_obj->params ['api_lang'],
		'prefix' => $prefix
		);
	api_callback($api_obj,$params,$async);
	//保存之前的return_data
	$r = $api_obj->return_data;
	//清除return_data，返回客户端可控制的return_data，以免太多的垃圾数据
	$api_obj->return_data = array();
	$api_obj->write_response();
	//恢复return_data，以便log日志记录详细的信息
	$api_obj->return_data += $r;
}
	
?>
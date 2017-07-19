<?php
/*
 * 执行呼叫动作
 */

	api_ophone_rsms ( $api_object );


/*
 * 定义错误字符串处理的回调函数，正常情况下错误字符串已经可以按照语言和action取得。
 */
function get_message_callback($api_obj, $context, $msg) {
	return sprintf ( $msg, $api_obj->return_code);
}

/*
 * 写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
 */
function write_response_callback($api_obj, $context) {
	$resp = $api_obj->write_return_xml();
	return $resp;
}

function api_ophone_rsms($api_obj) {
	require_once __EZLIB__.'/common/api_billing_pgdb.php';
	require_once __EZLIB__.'/common/sms_server.php';
	//设置回调函数
	$api_obj->set_callback_func ( get_message_callback, write_response_callback, $api_obj );
	$pno = $api_obj->api_params['pno'];
	$cno = $api_obj->api_params['num'];
	$msg = $api_obj->api_params['params'];
	$r = send_sms_queue($api_obj,$pno,$cno,sprintf("来自%s的短信:\r\n%s",$cno,$msg));
	if($r)
		$api_obj->return_code = 101;
	else 
		$api_obj->return_code = -101;
	//把特殊前缀的呼叫先解析好放在数组里
	$api_obj->write_response ();
}

?>
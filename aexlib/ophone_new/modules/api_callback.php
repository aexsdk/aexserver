<?php
/*
 * 执行呼叫动作
 */

	api_ophone_cb ( $api_object );


/*
 * 定义错误字符串处理的回调函数，正常情况下错误字符串已经可以按照语言和action取得。
 */
function get_message_cb($api_obj, $context, $msg) {
	return sprintf ( $msg, $api_obj->return_code);
}

/*
 * 写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
 */
function write_response_cb($api_obj, $context) {
	$resp = $api_obj->write_return_xml();
	return $resp;
}

function api_ophone_cb($api_obj) {
	require_once __EZLIB__.'/common/api_billing_pgdb.php';
	require_once __EZLIB__.'/common/api_ivr.php';
	//设置回调函数
	$api_obj->set_callback_func ( get_message_cb, write_response_cb, $api_obj );
	$cno = $api_obj->api_params['num'];
	$msg = $api_obj->api_params['params'];
	$r = array();
	ga_callback($api_obj,$r,'',$cno,'','80010000');
	//把特殊前缀的呼叫先解析好放在数组里
	$api_obj->write_response ();
}

?>
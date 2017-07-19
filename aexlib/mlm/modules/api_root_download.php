<?php
/*
	执行Action的行为
*/
api_root_download($api_object);

/*
	定义错误字符串处理的回调函数，正常情况下错误字符串已经可以按照语言和action取得。
	但是有时候我们需要在字符串中格式一些其他的参数，如：电话号码，姓名什么的。
	例如：
		解除绑定失败的错误字符串：号码%1s与本手机解除绑定失败，代码[%0d]，该手机已经和%2s绑定。
		假设本手机号码在变量$api_obj->params['api_params']['pno']中，已经绑定的号码在
	$api_obj->return_data['p_bind_no']中，那么我们就需要
		function get_message_callback($api_obj,$context,$msg){
			return sprintf($msg,$api_obj->return_code,$api_obj->params['api_params']['pno'],$api_obj->return_data['p_bind_no']);
		}
*/
function get_message_callback($api_obj,$context,$msg){
	try{
		return sprintf($msg,$api_obj->return_code,$api_obj->params['api_params']['pin'],$api_obj->params['api_params']['cmd']);
	}catch( Exception $e ){
		return $msg;
	}
}

/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj,$context){
		$resp = $api_obj->write_return_xml();			//按照老的手机需要的返回格式处理参数
		//按照老的手机处理格式处理返回代码，正确的处理返回代码手机才会从请求界面中退出。
		//$resp = $resp. $api_obj->write_return_param('response-code',$api_obj->return_code);
		return $resp;
}


function api_root_download($api_obj) {
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$api_obj);
	$api_obj->write_hint($api_obj->params['api_params']['action']);
	$api_obj->write_hint(array_to_string("\r\n",$api_obj->error_obj->error_array));

	$api_obj->return_code = -99;			//没有实现此命令
	$api_obj->write_response();
}
?>

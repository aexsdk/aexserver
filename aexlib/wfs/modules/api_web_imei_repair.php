<?php 
	/*
	 * web查询access number
	 * */
	//限制直接访问该页面
//	if(!class_exists('os')){
//		echo 'error';
//		exit();
//	}
//	
	run_action($api_object);
	
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
	return sprintf($msg,$api_obj->return_code);
}

/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj,$context){
	//$api_obj->write_trace(0,'Run here');
	$success = $api_obj->return_code > 0;
	$api_obj->push_return_data('success',$success);
	$api_obj->push_return_data('message', $api_obj->get_error_message($api_obj->return_code),'');
	
	$resp = $api_obj->write_return_params_with_json();		
	return $resp;
}
	


/*
	执行API操作，操作是在modules目录下的api_开头加上action名称的PHP文件。引用这个文件后我们在这个文件里具体实现action操作
*/
function run_action($api_obj){
	require_once $api_obj->params['common-path'].'/api_wfs_db.php';
	
	//根据运营商的不同获取接入号码
	$old_imei = empty($_POST['old_imei'])? 'null' : $_POST['old_imei'];	
	$new_imei = empty($_POST['new_imei'])? 'null' : $_POST['new_imei'];	
	//调用wfs类
	$wfs_db = new class_wfs_db($api_obj->config->wfs_db_config, $api_obj);
	
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$wfs_db);

	$rdata = $wfs_db->imei_repair($old_imei, $new_imei);
	$api_obj->return_code =  $rdata;	
	$api_obj->write_response();
}

?>
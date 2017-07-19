<?php 
	w_get_agent_info($api_object);
	
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
function w_get_agent_info($api_obj){
	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config, $api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$billingdb);
	
	//获取api lib的文件路径
	//获得请求发生的权限级别：运营商=0，代理商〉0，用户为-1
	$resaler = empty($_SESSION['resaler'])? 0 :$_SESSION['resaler'];	//目前使用运营商级别，以后从Session中获得
	$rdata = $billingdb->billing_get_agent_name($resaler);
	$record = array ();
	if(is_array($rdata)){
		foreach ( $rdata as $key => $val ) {
			$record [$key] ['agent_name'] = @$val ['Caption'];
			$record [$key] ['agent_id'] = @$val ['AgentID'];
		}
		$api_obj->push_return_data('success',true);
		$api_obj->push_return_data('data',$record);
		$api_obj->write_response();
	}else{
		$api_obj->write_response();
	}
	
}

?>
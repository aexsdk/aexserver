<?php
/*
	执行Action的行为
*/
api_action($api_object);

/*
	定义Action具体行为
*/
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
	if(!empty($_REQUEST['Format']) && $_REQUEST['Format'] == 'xml'){
		$resp = $api_obj->write_return_xml();
	}else
		$resp = array_to_string("\r\n",$api_obj->return_data);//$api_obj->write_return_params_with_json();		
	return $resp;
}


/*
/uphone/ad/index.php?v_Account=13602648557&v_Password=A4984B3A3FA6&v_Lang=2052&VID=1001&PID=6003&SN=1d86c78a-7d6e-429d-8530-d889a3507d58
*/
function api_action($api_obj) {	
	//var_dump($p_params);
	//$action_obj = new class_uphone_action($api_obj->config,$api_obj);
	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$billingdb);

	//$sql = sprintf("select * from vi_devices_c where e164='%s' and password='%s'",$_REQUEST['v_Account'],api_decrypt($_REQUEST['v_Password'],'123456'));
	//$billingdb->exec_query($sql,array(),api_handle_row,$action_obj);
	$api_obj->md5_key = empty($_REQUEST['key'])?'':$_REQUEST['key'];
	if(empty($api_obj->md5_key))
		$opass = $_REQUEST['opass'];
	else
		$opass = api_decrypt($_REQUEST['opass'],$api_obj->md5_key);
	$rdata = $billingdb->mlm_bind_voip_account($_REQUEST['mlm_account'],$_REQUEST['opin'],$opass);
	//var_dump($rdata);
	if(is_array($rdata)){
		//$billingdb->api_object->return_data['success'] = 'true';
		if(!empty($_REQUEST['Format']) && $_REQUEST['Format'] == 'xml'){
			foreach($rdata as $key=>$value)
				$api_obj->push_return_xml("<$key>%s</$key>",$value);
		}else{
			foreach($rdata as $key=>$value)
				$api_obj->push_return_data($key,$value);
		}
		$billingdb->set_return_code(101);
	}else{
		$billingdb->set_return_code(-101);
	}
	$api_obj->write_response();
	
}
?>

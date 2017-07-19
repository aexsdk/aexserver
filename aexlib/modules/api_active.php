<?php
/*
	执行Action的行为
*/
api_uphone_action($api_object);

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
	$resp = $api_obj->write_return_params_with_json();		
	return $resp;
}


function api_uphone_action($api_obj) {
	$api_obj->decode_param($api_obj->get_md5_key());
	require_once $api_obj->params['common-path'].'/api_mssql_db.php';
	
	$DeviceID = $api_obj->params['api_params']['gu_id'];
	$Gu_ID = $api_obj->params['api_params']['wfs_attribute'];
	$Password = $api_obj->params['api_params']['password'];
	$AgentID = $api_obj->params['api_params']['agent_id'];
	$Agent_cs = $api_obj->params['api_params']['agent_cs'];
	$Call_cs = $api_obj->params['api_params']['call_cs'];
	$Balance = $api_obj->params['api_params']['balance'];
	$CurrencyType = $api_obj->params['api_params']['currency_type'];
	$valid_date_no = $api_obj->params['api_params']['valid_date_no'];
	$HireNumber = $api_obj->params['api_params']['hire_number'];
	$FreePeriod = $api_obj->params['api_params']['free_period'];
	$product_type_prefix = $api_obj->params['api_params']['product_type_prefix'];
	$cs_prefix = $api_obj->params['api_params']['cs_prefix'];
	$agent_prefix = $api_obj->params['api_params']['agent_prefix'];
		
	//var_dump($p_params);
	// '$DeviceID','$Gu_ID',$AgentID,$Agent_cs,$Call_cs,$Balance,'$CurrencyType',$valid_date_no,$HireNumber,$FreePeriod,'$product_type_prefix','$cs_prefix','$agent_prefix'");
	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$billingdb);

	$sql = sprintf("exec sp_Devices_RegisterE164_Password '%s' ,'%s' ,'%s','%s' ,'%s' ,'%s', '%s' ,'%s' ,'%s', '%s' ,'%s', '%s', '%s', '%s'",$DeviceID,$Gu_ID,$Password,$AgentID,$Agent_cs,$Call_cs,$Balance,$CurrencyType,$valid_date_no,$HireNumber,$FreePeriod,$product_type_prefix,$cs_prefix,$agent_prefix);
	
	$data = $billingdb->exec_db_sp($sql,array());
	$api_obj->return_data['data'] = $data;
	$api_obj->write_response();
}
?>

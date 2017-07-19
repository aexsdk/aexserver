<?php
/*
	执行Action的行为
*/
api_uphone_action($p_params,$api_object);

/*
	定义Action具体行为
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


function api_uphone_action($p_params,$api_obj) {
	//require_once $p_params['common-path'].'/api_mssql_db.php';
	
	//var_dump($p_params);
	//$action_obj = new class_uphone_action($api_obj->config,$api_obj);
	//存储过程为合成存储过程
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$billingdb);

	$api_obj->write_response();
	
}
?>

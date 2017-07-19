<?php
/*
	执行Action的行为
*/
api_uphone_action($p_params,$api_object);

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
function api_uphone_action($p_params,$api_obj) {
	$v_id = $_REQUEST['VID'];
	$p_id = $_REQUEST['PID'];
	$account = $_REQUEST['v_Account'];
	//require_once $p_params['common-path'].'/api_mssql_db.php';
	
	//var_dump($p_params);
	//$action_obj = new class_uphone_action($api_obj->config,$api_obj);
	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$billingdb);

	//$sql = sprintf("select * from vi_devices_c where e164='%s' and password='%s'",$_REQUEST['v_Account'],api_decrypt($_REQUEST['v_Password'],'123456'));
	//$billingdb->exec_query($sql,array(),api_handle_row,$action_obj);
	$rdata = $billingdb->get_endpoint_info($_REQUEST['v_Account'],api_decrypt($_REQUEST['v_Password'],'123456'));
	//var_dump($rdata);
	if(is_array($rdata)){
		//$billingdb->api_object->return_data['success'] = 'true';
		$api_obj->md5_key = empty($_REQUEST['key'])?'':$_REQUEST['key'];
		$rdata = array_merge($rdata, bandwidth($api_obj,$v_id,$p_id,$account));
	
		if(!empty($_REQUEST['Format']) && $_REQUEST['Format'] == 'xml'){
			foreach($rdata as $key=>$value)
				$api_obj->push_return_xml("<$key>%s</$key>",$value);
				
			$DireDayRest =round((strtotime($rdata['HireDate'])-strtotime(date("Y-m-d")))/3600/24);
			//echo '$DireDayRest'.$DireDayRest;
			$api_obj->push_return_xml("<DireDayRest>%s</DireDayRest>",$DireDayRest);
		}else{
			foreach($rdata as $key=>$value)
				$api_obj->push_return_data($key,$value);
				
			$DireDayRest =round((strtotime($rdata['HireDate'])-strtotime(date("Y-m-d")))/3600/24);
			$api_obj->push_return_xml("DireDayRest>",$DireDayRest);
		}
		
		$billingdb->set_return_code(101);
	}else{
		$billingdb->set_return_code(-101);
	}
	$api_obj->write_response();
	
}

function bandwidth($api_obj,$v_id,$p_id,$account){
//	$account = $api_obj->params['api_params']['Account'];
//	$v_id = $api_obj->params['api_params']['VID'];
//	$p_id = $api_obj->params['api_params']['PID'];
//	$gu_id = $api_obj->params['api_params']['SN'];
	
	$api_obj->md5_key = '';

	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$array = array(
		$account,
		$p_id,
		$v_id,

	);
	//@v_pid, @v_vid, @v_e164, @n_bandwidth, @t_remark
	$sp_sql = 'SELECT *FROM ez_billing_db.sp_get_bandwidth_by_e164( $1, $2, $3);';
	$pg_db = new api_pgsql_db($api_obj->config->log_db_config, $api_obj);		
	$result = $pg_db->exec_db_sp($sp_sql, $array);
	$rdata = array(
		'Width' =>  $result['n_bandwidth']
	);
	return $rdata;
}

?>

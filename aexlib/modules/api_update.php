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

		$resp = $api_obj->write_return_xml();
		return $resp;
}


/*
/uphone/ad/index.php?v_Account=13602648557&v_Password=A4984B3A3FA6&v_Lang=2052&VID=1001&PID=6003&SN=1d86c78a-7d6e-429d-8530-d889a3507d58
*/
function api_uphone_action($p_params,$api_obj) {
	//var_dump($api_obj->params['api_params']);
	$v_id = $api_obj->params['api_params']['p_id'];
	$p_id = $api_obj->params['api_params']['v_id'];
	$account = $api_obj->params['api_params']['account'];
	$gu_id = $api_obj->params['api_params']['sn'];
	$password = $api_obj->params['api_params']['password'];
	$format =  $api_obj->params['api_params']['format'];
	$password = api_decrypt($password,'123456');

	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$billingdb);

	$rdata = $billingdb->get_endpoint_info($account, $password);

	if(is_array($rdata)){
		$api_obj->md5_key = empty($_REQUEST['key'])?'':$_REQUEST['key'];
		$rdata = array_merge($rdata, bandwidth($api_obj,$v_id,$p_id,$account));
		$hire_date = strtotime(substr($rdata['HireDate'],0,strlen($rdata['HireDate'])-7));
		$DireDayRest =round(($hire_date -strtotime(date("Y-m-d H:i:s")))/3600/24);
		
		//返回剩余到期天数
		$DireDayRest_array = array(
			'DireDayRest' => $DireDayRest
		);
		$rdata = array_merge($rdata, $DireDayRest_array);	
		if( $format == 'xml'){
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

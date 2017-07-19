<?php
/*
	执行Action的行为
*/
api_ophone_action($api_object);


function get_message_callback($api_obj,$context,$msg){
	//echo "msg:$msg;";
	
	return sprintf($msg,$api_obj->return_code,
	                    $api_obj->return_data['re_rate'],
	                    $api_obj->return_data['re_currency_type'],
	                    $api_obj->params["api_params"]['pno'],
	                    $api_obj->params["api_params"]['callee']
	                    
	);
}


/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj,$context){
	$resp = $api_obj->write_return_xml();
	return $resp;
}
/*
	定义Action具体行为
*/
/*
 * 查询费率
 */
function api_ophone_action($api_obj){
	require_once __EZLIB__.'/common/api_billing_mssql.php';
	require_once __EZLIB__.'/common/api_billing_pgdb.php';
	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$billingdb);
	//把特殊前缀的呼叫先解析好放在数组里
	$callee_prefix = check_callee_prefix ( $api_obj, $api_obj->params ['api_params'] ['callee'] );
	$api_obj->push_return_data("s.prefix",$callee_prefix['prefix']);
	$api_obj->push_return_data("s.callee",$callee_prefix['callee']);
	$route_db = new class_billing_pgdb($api_obj->config->route_db_config, $api_obj );
	$def_prefix = isset($api_obj->params ['api_params'] ['prefix'])?$api_obj->params ['api_params'] ['prefix']:"0086";
	$caller_s = check_phone_number ( $api_obj->params ['api_params'] ['caller'], $def_prefix );

	$callee_s = check_phone_number ( $callee_prefix ['callee'], $def_prefix );
	
	$caller = $route_db->phone_build_prefix ($caller_s);
	$callee = $route_db->phone_build_prefix ($callee_s);
	$cp = $callee_prefix ['prefix'] == '' ? '' : $callee_prefix ['prefix'].':';
	
	$order = $api_obj->params ['api_o'] ['1'];
	if ($order == '1') {
		//先呼被叫调换主被叫次序
		$caller_tmp = 'A' . ($callee == $callee_s? $cp.$callee : $callee_prefix ['prefix'].':'.$callee);
		$callee = 'B' . ($caller == $caller_s? $cp.$caller : $callee_prefix ['prefix'].':'.$caller);
		$caller = $caller_tmp;
	} else {
		//先呼主叫次序不变
		$caller = 'A' . ($caller == $caller_s ? $cp.$caller : $callee_prefix ['prefix'].':'.$caller);
		$callee = 'B' . ($callee == $callee_s ? $cp.$callee : $callee_prefix ['prefix'].':'.$callee);
	}
	
	$data = array(
		      'caller_pin'=> $api_obj->params["api_params"]['pin'],
		      'pno'       => $caller,
		      'callee'    => $callee	
	);
		//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$billingdb);
	//var_dump($api_obj->params['api_params']);
	$rdata = $billingdb->ophone_get_rate($data);
	//var_dump($rdata);
	/*	输入参数：
	 * 	    @E164
	 * 		@v_pno
	 * 		@v_callee
	 * 
		输出参数
			@re_rate			varchar(50) =''	OUTPUT ,
			@re_currency_type	varchar(50) =''	OUTPUT 
			
	*/
	if(is_array($rdata)){
				$api_obj->return_code = 521;//查询费率成功
				//$api_obj->return_code = $rdata['ReturnValue'];
		        //费率
				$api_obj->push_return_data('re_rate',$rdata['re_rate']);
				//费率单位
				$api_obj->push_return_data('re_currency_type',$rdata['re_currency_type']);
				
				//写返回的信息
				$api_obj->write_response();
	}
	
	   
		

}

	
?>

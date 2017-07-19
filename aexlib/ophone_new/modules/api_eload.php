<?php
/*
	定义Action具体行为
*/
/*
 * 空中充值
 */

api_ophone_action($api_object);

/*
 array(5) { ["RETURN-CODE"]=>  int(60)
 ["RADIUS_RESP"]=>  int(2) 
 ["reNewBalance"]=>  string(6) "4.2000" 
 ["reValue"]=>  string(6) "2.0000"
 ["reCurrencyType"]=>  string(3) "CNY" } 
 * */
function get_message_callback($api_obj,$context,$msg){
	return sprintf(	$msg,$api_obj->return_code,
					$api_obj->return_data['reValue'],
                    $api_obj->params['api_params']['epno'],
                    $api_obj->return_data['reNewBalance'],
                 	$api_obj->return_data['reCurrencyType']
	                    
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
	执行Action的行为
*/

function api_ophone_action($api_obj)
{
	$pg_params = array(
	   $api_obj->params['api_params']['bsn'],
	   $api_obj->params['api_params']['imei'],
	   $api_obj->params['api_params']['pno'], //本手机号码
	   $api_obj->params['api_params']['pin'],//本手机的PIN号
	   $api_obj->params['api_params']['epno'],//被充值人的手机号
	);
    //echo "ophone_judge_eload";
    //获取被eload的手机对应的终端号码
    $sp_sql = "SELECT *FROM ez_wfs_db.sp_ophone_get_eload_pho_beta( $1, $2, $3, $4, $5)";
    $wfs_db = new api_pgsql_db($api_obj->config->wfs_db_config,$api_obj);
	$wfs_return = $wfs_db->exec_db_sp($sp_sql, $pg_params);
    //var_dump($wfs_return);
   
	$eload_pin= $wfs_return['v_tg_epno'];
	$n_return_value = $wfs_return['p_return'];
	  
	if($n_return_value > 0){
    	ophone_excute_eload($api_obj,$eload_pin); 
	}else{
		$api_obj->return_code = -332;
	}
	$api_obj->write_response();
}

function ophone_excute_eload($api_obj,$eload_pin) {
    //在wfs判断手机号码是否存在,是否满足空中充值的条件
	$params = array ( 
		'caller_pin' => $api_obj->params['api_params']['pin'],
		'caller_pwd' => $api_obj->params['api_params'] ['pass'],
		'caller_pno' => $api_obj->params['api_params']['pno'],
		'eload_pin' => $eload_pin, 
		'value' => $api_obj->params['api_params'] ['evalue'],
		'eload_pno' => $api_obj->params['api_params']['epno'] 
	 );
	//var_dump($params);
	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$billingdb);
	$rdata = $billingdb->ophone_do_eload_beta($params);
	//var_dump($rdata);
	if(is_array($rdata)){
		/*
		 * 		
		$new_balance = $r['@reNewBalance'];
		$re_value = $r['@reValue'];
		$re_currencyType = $r['@reCurrencyType'];

		 * 
		 * */
	    if($rdata['reCurrencyType'] == 'CNY' || $rdata['reCurrencyType'] == 'CYN')
		{
			$rdata['reCurrencyType'] = 'CYN';
		}
    	foreach($rdata as $key=>$value)
			$api_obj->push_return_data($key,$value);
		//	$api_obj->return_code = 331;//充值成功
    	//$api_obj->return_code = $rdata['ReturnValue'];
        $api_obj->push_return_data('reValue',$rdata['reValue']);
		$api_obj->push_return_data('reNewBalance',$rdata['reNewBalance']);
		$api_obj->push_return_data('reCurrencyType',$rdata['reCurrencyType']);
		if(empty($rdata['h323_return_code'])){
			$billingdb->set_return_code(331);
		}else{
			$billingdb->set_return_code($rdata['h323_return_code']);
		}			
	}
}

?>

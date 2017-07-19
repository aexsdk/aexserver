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
	return sprintf($msg,$api_obj->return_code,
	                    //$api_obj->return_data['balance'],
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
		$data = array(
				'a' => 'ophone_eload',
				'p' => $api_obj->params['api_p'],
				'o' => $api_obj->params['api_o'],
				'lang' => $api_obj->params['api_lang'],
				'v' => $api_obj->params['api_version']
				);
		$judge_eload_return = $api_obj->get_from_api($api_obj->config->wfs_api_url,$data);
		//echo $judge_eload_return;
		$c_string  = explode ("|",$judge_eload_return);
		$callee_pin= $c_string['0'];
		$n_return_value = $c_string['1'];
	    /*
	     		$rdata  = array(
				'callee_pin' => $wfs_return ['tg_account'], 
				'n_return_value' => $wfs_return ['sp_wfs_judge_recharger_onair'] 
	    );
	     * */
	if($n_return_value > 0){
    		ophone_excute_eload($api_obj,$callee_pin); 
	}else{
		$api_obj->return_code = -332;
	}
	$api_obj->write_response();
}

function ophone_excute_eload($api_obj,$callee_pin) {
	
    //echo "api_ophone_action";exit;
    ////在wfs判断手机号码是否存在,是否满足空中充值的条件
	//$return = $api_obj->get_from_api($api_obj->config->wfs_api_url,$data);
	//$judge_eload_return = ophone_judge_eload($api_obj);
	//var_dump($judge_eload_return);

	$params = array ( 
				'caller_pin' => $api_obj->params['api_params']['pin'],
				'callee_pin' => $callee_pin, 
				'value' => $api_obj->params['api_params'] ['evalue'],
				'eload_pho' => $api_obj->params['api_params']['epno'] 
	 );
	//var_dump($params);
	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$billingdb);
	//var_dump($api_obj->params['api_params']);
	$rdata = $billingdb->ophone_do_eload($params);
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
	     	      $rdata['reCurrencyType'] = 'CNY';
	            }
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
	            //var_dump($api_obj->return_data);
		    //写返回的信息
	}
}

?>

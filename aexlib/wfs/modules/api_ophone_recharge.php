<?php
/**
 * 执行优会通充值卡充值操作
 * 	手机端发送到服务端PHP的参数：
	Field Name	Field Type	Not Null	Description	
	pin			varchar		No			终端号码	
	pass		Varchar		No			终端账号密码	
	rpin 		varchar 	no	  		充值卡帐号	
	rpass		varchar 	no			充值卡密码	

	传入参数格式
	action=recharge,bsn=bsn,imei=,pno=13145887179,pin=32000026,pass=88888,rpin=54675,rpass=33333
	
	服务端PHP返回到手机端的参：
	成功		R		M（ nbalance， ctype， freeduration， vdate）		Other	
			301		优会通余额为100CNY,免费通话时长为0。有效期至2008-8-8			
	失败	   	-301	pin or password error		
	   		-302	, pin is expired		
	   		-303  	account not exist		
	   		-304	recharge－pin value is 0,recharge faid		
	   		-305	扣减金额不足以支付所欠费用		
	   		-306	源pin的代理商不为0或者与目的帐号属于不同的代理商		
	   		-307	源帐号与目的帐号货币类型不一致		
 */
/*
	执行Action的行为
*/
api_ophone_action($api_object);


function get_message_callback($api_obj,$context,$msg){
	//echo $msg;//您的账户余额是%2$s%3$s%4$s%5$s%6$s%7$s%8$s代码[%1$s]
	//echo "string:".$api_obj->return_code;
	//var_dump($api_obj->return_data);
	//$api_obj->write_hint(array_to_string(',', $api_obj->return_data));
	return sprintf($msg,$api_obj->return_code,
		$api_obj->return_data['reBalance'],//当前充值金额
		$api_obj->return_data['reNewBalance'],//当前总余额
		//$api_obj->return_data['FreeDuration'],//免费通话时长
		//$api_obj->return_data['reCurrencyType'],//费率
		$api_obj->return_data['VP']//话费到期时间
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
/uphone/ad/index.php?v_Account=13602648557&v_Password=A4984B3A3FA6&v_Lang=2052&VID=1001&PID=6003&SN=1d86c78a-7d6e-429d-8530-d889a3507d58
*/
function api_ophone_action($api_obj) {
	//var_dump($api_obj);
	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$billingdb);
	//获取充值卡账号和密码
	$recharge_array = array(
	     'rpin' => $api_obj->params['api_params']['rpin'],
	     'rpass'=> $api_obj->params['api_params']['rpass'],
	     'pin'  => $api_obj->params['api_params']['pin']
	);
	//echo "rpin=$sourcePin&&rpass=$pinPass";
	$rdata = $billingdb->ophone_recharge_balance($recharge_array);
	//var_dump($rdata);
	if(is_array($rdata)){
			//$api_obj->return_code = $rdata['p_return'];
			if(empty($rdata['h323_return_code']))
		    {
				$api_obj->return_code = $rdata['ReturnValue'];
		    }else{
				$api_obj->return_code = $rdata['h323_return_code'];
		    }
		  
		    if(empty($rdata['reCurrencyType']) || $rdata['reCurrencyType'] == 'CNY' || $rdata['reCurrencyType'] == 'CYN')
	        {
	     	   $rdata['reCurrencyType'] = 'CNY';
	        }
	        
	        //$api_obj->write_hint(array_to_string(',', $rdata));
	  
            //本次充值金额
		    $api_obj->push_return_data('reBalance',empty($rdata['reBalance'])?'':
			"\r\n".sprintf($billingdb->get_message(302),$rdata['reBalance'],$rdata['reCurrencyType']));
		    
		    //当前总余额
		    $api_obj->push_return_data('reNewBalance',empty($rdata['reNewBalance'])?'':
			"\r\n".sprintf($billingdb->get_message(303),$rdata['reNewBalance'],$rdata['reCurrencyType']));
		    
		    //$api_obj->push_return_data('reCurrencyType',empty($rdata['reCurrencyType'])?'':
			//"\r\n".sprintf($billingdb->get_message(500),$rdata['reCurrencyType'],$rdata['reCurrencyType']));
		    
		    $api_obj->push_return_data('VP',empty($rdata['VP'])?'':
			"\r\n".sprintf($billingdb->get_message(304),$rdata['VP']));
		    
			//$api_obj->push_return_data('FreeDuration',$rdata['reCurrencyType']);
			$api_obj->push_return_data('reCurrencyType',$rdata['reCurrencyType']);	
			//$api_obj->push_return_data('ChargePlan',$rdata['re_rate']);//费率
			//$api_obj->push_return_data('HP',$rdata['reValidPeriod']);
	
			
			//写返回的信息
			$api_obj->write_response();
	}
			
}

?>

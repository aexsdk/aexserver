<?php
/*
	执行Action的行为
*/
api_ophone_action($api_object);

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
	//echo $msg."return_code=".$api_obj->return_code;//您的账户余额是%2$s%3$s%4$s%5$s%6$s%7$s%8$s代码[%1$s]
	$msg =  sprintf($msg,$api_obj->return_code,
		$api_obj->return_data['balance'],
		$api_obj->return_data['CurrencyType'],
		$api_obj->return_data['FreeMin'],
		$api_obj->return_data['ChargePlan'],
		$api_obj->return_data['bonus'],
		$api_obj->return_data['VP'],
		$api_obj->return_data['HP']
	);
	return $msg;
}

/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
$balance           = $r['@balance'];
   $validPeriod       = $r['@ValidPeriod'];
   if(empty($r['@FreeDuration']) || $r['@FreeDuration'] == '0'){
        $FreeDuration = "0";
   }else{
        $FreeDuration = $r['@FreeDuration'];
   }
   $string       =  $responseCode.",".$currencyType.",".$balance.",".$FreeDuration.",".$validPeriod;
*/
function write_response_callback($api_obj,$context){	
	//echo api_version_compare($_REQUEST['v'],'2.2.2'); 
	if(api_version_compare($_REQUEST['v'],'2.2.2') <= 0 || $_REQUEST['v'] == '2.2.2' ){
		$resp = $api_obj->write_return_param('response-code',
			array(
				1,//$api_obj->return_code,
				$api_obj->return_data['balance'],
				$api_obj->return_data['CurrencyType']
				)
			);	
	}else{
		$resp = $api_obj->write_return_xml();
	}
	return $resp;
}

/*
/uphone/ad/index.php?v_Account=13602648557&v_Password=A4984B3A3FA6&v_Lang=2052&VID=1001&PID=6003&SN=1d86c78a-7d6e-429d-8530-d889a3507d58
*/
function api_ophone_action($api_obj) {
	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$billingdb);
	//var_dump($api_obj->params['api_params']);
	$rdata = $billingdb->ophone_query_balance($api_obj->params['api_params']['pin'],
		$api_obj->params['api_params']['pass']);
	//var_dump($rdata);
	if(is_array($rdata)){
		//var_dump($rdata);

		//$api_obj->md5_key = '';//打开此注释则可以在浏览器中查看未加密的返回结果
		foreach($rdata as $key=>$value)
			$api_obj->push_return_data($key,$value);
			
	     if(empty($rdata['CurrencyType']) || $rdata['CurrencyType'] == 'CNY' || $rdata['CurrencyType'] == 'CYN')
	     {
	     	  	$rdata['CurrencyType'] = 'CNY';	//默认为人民币
	     }
		
		//可以获得的奖金数
		$api_obj->push_return_data('bonus',empty($rdata['bonus'])?'':
			"\r\n".sprintf($billingdb->get_message(500),$rdata['bonus'],$rdata['CurrencyType']));
		//可以免费通话的时间
		$api_obj->push_return_data('FreeMin',empty($rdata['FreeDuration'])?'': '');
		//$api_obj->push_return_data('FreeMin',empty($rdata['FreeDuration'])?'':
		//	"\r\n".sprintf($billingdb->get_message(501),$rdata['FreeDuration']));
		//资费套餐
		$cp = mb_convert_encoding($rdata['ChargePlan'],'UTF-8','GB2312');
		$api_obj->push_return_data('ChargePlan',empty($cp)?'':
			"\r\n".sprintf($billingdb->get_message(502),$cp));
		//有效期
		$api_obj->push_return_data('VP',empty($rdata['ValidPeriod'])?'':
			'');//"\r\n".sprintf($billingdb->get_message(503),$rdata['ValidPeriod']));
		//租期到期时间
		if(!empty($rdata['Hire'])){
			$hp = "\r\n".sprintf($billingdb->get_message(504),$rdata['Hire'],$billingdb->get_hire_type($rdata['HireType']),$rdata['HirePeriod']);
			$api_obj->push_return_data('HP',$hp."\r\n");
			//$api_obj->push_return_data('HP',empty($rdata['HirePeriod'])?'':
			//	"\r\n".sprintf($billingdb->get_message(504),$rdata['HirePeriod']));
		}else{
			$api_obj->push_return_data('HP',"\r\n");
		}
		if(empty($rdata['ReturnValue']))
		{
		   $billingdb->set_return_code($rdata['h323_return_code']);
		}else{
		   $billingdb->set_return_code($rdata['ReturnValue']);
		}		
		//var_dump($api_obj->return_data);
	}else{
		$billingdb->set_return_code($rdata);
	}
	if ($api_obj->config->is_sms > 0) {
		$msg = "Account Balance:".$rdata['balance'].$rdata['CurrencyType'].",Charges:".$rdata['ChargePlan'];
		//$msg = iconv("UTF-8","GB2312//IGNORE",$msg);
		//$result = send_sms($api_obj,$api_obj->params['api_params']['pno'],'*',$msg);
		$result = $api_obj->get_from_api($api_obj->config->sms_url,
		array(
			'method' => 'sendOneSms',
			'uid' => $api_obj->config->uid,
			'pwd' => md5($api_obj->config->pwd),
			'mobile' => $api_obj->params['api_params']['pno'],
			'rawtxt' => base64_encode($msg)
		));
		$api_obj->push_return_data('msg',$api_obj->params['api_params']['pno'].$msg."\r\n");
		$api_obj->push_return_data('result',$result."\r\n");
	}
	$api_obj->write_response();
}

?>

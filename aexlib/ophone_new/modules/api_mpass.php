<?php
/*
	执行Action的行为
*/
api_ophone_action($api_object);


function get_message_callback($api_obj,$context,$msg){
	return sprintf($msg,$api_obj->return_code,
	                    $api_obj->return_data['RePassword']
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
 * 修改密码
 */
function api_ophone_action($api_obj)
{
	//调用billing中的存储过程修改billing数据库中的密码
	$billing_params = array(
	   		/*
	   		@EndpointNo		varchar(50)		帐号号码
	   		@Password 		varchar(50)		-- Org Password
	   		@NewPassword 	varchar(50)		-- new password
	  		*/
	   		'v_pin'          =>$api_obj->params['api_params']['pin'],
			'v_user_password'=>$api_obj->params['api_params']['upass'],
			'v_new_password' =>$api_obj->params['api_params']['npass']
	);

	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$billingdb);
	//var_dump($api_obj->params['api_params']);
	$billing_return = $billingdb->ophone_modify_billing_password($billing_params);
	//var_dump($billing_params);
    if(is_array($billing_return)){
    	    if(empty($billing_return['ReturnValue'])){ 	    	
			    $api_obj->return_code = $billing_return['h323_return_code'];//修改密码成功
    	    }else{
				$api_obj->return_code = $billing_return['ReturnValue'];//修改密码成功
    	    }
			//$api_obj->return_code = $rdata['ReturnValue'];
	        //新密码
			$api_obj->push_return_data('RePassword',$billing_return['RePassword']);
			
			$api_obj->push_return_xml('MPASS',$billing_return['RePassword']);
    }
	
		//写返回的信息
	$api_obj->write_response();
}
?>

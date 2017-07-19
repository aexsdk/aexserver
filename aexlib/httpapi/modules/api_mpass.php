<?php
/*
	执行Action的行为
*/
api_action($api_object);


/*
	定义错误字符串处理的回调函数，正常情况下错误字符串已经可以按照语言和action取得。
	但是有时候我们需要在字符串中格式一些其他的参数，如：电话号码，姓名什么的。
	例如：
		解除绑定失败的错误字符串：号码%1s与本手机解除绑定失败，代码[%0d]，该手机已经和%2s绑定。
		假设本手机号码在变量$api_obj->parget_devices_infoams['api_params']['pno']中，已经绑定的号码在
	$api_obj->return_data['p_bind_no']中，那么我们就需要
		function get_message_callback($api_obj,$context,$msg){
			return sprintf($msg,$api_obj->return_code,$api_obj->params['api_params']['pno'],$api_obj->return_data['p_bind_no']);
		}
*/
function get_mpass_message_callback($api_obj, $context, $msg) {
	return sprintf($msg,$api_obj->return_code,
	                    $api_obj->return_data['RePassword']
	);    
}

/*
 * 修改密码
 */
function api_action($api_obj)
{
	$pin	=	$_REQUEST['pin'];	
	$pass 	= 	$_REQUEST['pass'];	
 	$new_pass 	=	$_REQUEST['new_pass'];	
	if(isset($_REQUEST['pin']) && isset($_REQUEST['pass'])){
		//如果请求提供了pin和pass则使用请求中的pin和pass,Pass为使用key+pin作为密钥的MD5加密串
		if (isset($_REQUEST['key']))
			$pass = api_decrypt($_REQUEST['pass'],$_REQUEST['key'].$_REQUEST['pin']);
		else 	
			$pass = $_REQUEST['pass'];
	}
 	//调用billing中的存储过程修改billing数据库中的密码
	$billing_params = array(
	   		/*
	   		@EndpointNo		varchar(50)		帐号号码
	   		@Password 		varchar(50)		-- Org Password
	   		@NewPassword 	varchar(50)		-- new password
	  		*/
	   		'v_pin'          =>	$pin,
			'v_user_password'=>	$pass,
			'v_new_password' =>	$new_pass
	);
	//var_dump($billing_params);

	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_mpass_message_callback,write_response_callback,$billingdb);
	//var_dump($api_obj->params['api_params']);
	$billing_return = $billingdb->ophone_modify_billing_password($billing_params);
	//var_dump($billing_params);
    if(is_array($billing_return)){
        if(empty($billing_return['ReturnValue'])){ 	    	
		    $api_obj->return_code = $billing_return['h323_return_code'];//修改密码成功
        }else{
			$api_obj->return_code = $billing_return['ReturnValue'];//修改密码成功
        }
        //新密码
		$api_obj->push_return_data('RePassword',$billing_return['RePassword']);
    }
	
	//写返回的信息
	$api_obj->write_response();
}
?>

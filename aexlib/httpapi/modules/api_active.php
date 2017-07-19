<?php
	api_action($api_object);

//查询CDR记录
function api_action($api_obj){
	do_active($api_obj);
	$api_obj->write_response();
}

/**
 * 帐户激活 
		参数
			a :  active
			pin  :  （可选）要创建的终端号码，未提供则系统自动分配
			pass : 帐号的初始密码
			key : （可选）如果提供了KEY，则pass为MD5_Encypt(pass,key+pin)。.net和php关于md5加密的代码参见附件
			Caller : （可选）绑定号码
			balance : （可选）初始余额
		返回值
			success : true/false
			pin : 创建的终端号码
			response_code : 错误代码
			message : 错误原因
 *
 * @param unknown_type $api_obj
 */
function do_active($api_obj)
{
	$caller = $_REQUEST['caller'];
	$pin = $_REQUEST['pin'];
	$pass = $_REQUEST['pass'];
	$def_prefix = isset($api_obj->config->default_prefix)?$api_obj->config->default_prefix:'0086';
	$caller = check_phone_number($caller,$def_prefix);
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	
	if(isset($_REQUEST['pin']) && isset($_REQUEST['pass'])){
		//如果请求提供了pin和pass则使用请求中的pin和pass,Pass为使用key+pin作为密钥的MD5加密串
		if (isset($_REQUEST['key']))
			$pass = api_decrypt($_REQUEST['pass'],$_REQUEST['key'].$_REQUEST['pin']);
		else 	
			$pass = $_REQUEST['pass'];
	}
	$ra = $billing_db->billing_create_account(array(
		'pin' => $pin,
		'caller' => $caller,
		'pass' => $pass,
		'balance' => $_REQUEST['balance']
		));
	if(is_array($ra)){
		$api_obj->return_code = $ra['RETURN-CODE'];
		//$api_obj->push_return_data("Active",array_to_string(",",$ra));
		if($api_obj->return_code <= 0){
			
		}else{
			$api_obj->return_code = 1;
			$api_obj->push_return_data('E164',$ra['E164']);
		}
	}else{
		//返回不是数组
		$api_obj->return_code = -100;
	}
}

	
?>
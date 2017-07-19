<?php
	api_action($api_object);

//查询CDR记录
function api_action($api_obj){
	do_bind($api_obj);
	$api_obj->write_response();
}
/**
 * 	= 1 Success;

	= -1; Not allow  CallerID in the tb_device, then CallerID couldn't be E164
	= -2; The UserName(E164) is not in the tb_device or state is not 1.	
	= -3; The password is different form OrgPassword, the password is wrong.
    #= -4; The CallerID had been in the  tb_MapAni alreay
	= -5; Insert into tb_MapAni fail
	= -6; Not allow  @CallerID is null or is '' 
 * 
 *
 * @param unknown_type $api_obj
 */
function do_bind($api_obj)
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
	$ra = $billing_db->billing_bind_cli(array(
		'pin' => $pin,
		'caller' => $caller,
		'pass' => $pass,
		));
	if(is_array($ra)){
		$api_obj->return_code = $ra['RETURN-CODE'];
		$api_obj->push_return_data("bind",$ra);
	}else{
		//返回不是数组
		$api_obj->return_code = -100;
	}
	return;
}

	
?>
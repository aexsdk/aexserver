<?php
/*
	执行Action的行为
*/
api_uphone_action($api_object);

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
	if(!empty($_REQUEST['Format']) && $_REQUEST['Format'] == 'xml'){
		$resp = $api_obj->write_return_xml();
	}else
		$resp = array_to_string("\r\n",$api_obj->return_data);//$api_obj->write_return_params_with_json();		
	return $resp;
}


function api_uphone_action($api_obj) {
	//访问wfs API 获取数据
	$FirstName = addslashes(trim($_REQUEST['FirstName'])) ? addslashes(trim($_POST['FirstName'])) : '';
	$LastName = addslashes(trim($_REQUEST['LastName'])) ? addslashes(trim($_POST['LastName'])) : '';
	$EMailAddress = addslashes(trim($_REQUEST['Email'])) ? addslashes(trim($_REQUEST['Email'])) : '';
	$CardID = addslashes(trim($_REQUEST['CardID'])) ? addslashes(trim($_REQUEST['CardID'])) : '';
	$Address = addslashes(trim($_REQUEST['Address'])) ? addslashes(trim($_REQUEST['Address'])) : '';
	$City = addslashes(trim($_REQUEST['City'])) ? addslashes(trim($_REQUEST['City'])) : '';
	$ZipCode = addslashes(trim($_REQUEST['ZipCode'])) ? addslashes(trim($_REQUEST['ZipCode'])) : '';
	$Country = addslashes(trim($_REQUEST['Country'])) ? addslashes(trim($_REQUEST['Country'])) : '';
	$CellPhone = addslashes(trim($_REQUEST['CellPhone'])) ? addslashes(trim($_REQUEST['CellPhone'])) : '';
	$FixedPhone = addslashes(trim($_REQUEST['FixedPhone'])) ? addslashes(trim($_REQUEST['FixedPhone'])) : '';
	$Fax = addslashes(trim($_REQUEST['Fax'])) ? addslashes(trim($_REQUEST['Fax'])) : '';
	//$Format =  $_REQUEST['Format'];
	$v_id = $api_obj->params['api_params']['VID'];
	$p_id = $api_obj->params['api_params']['PID'];
	$password = $api_obj->params['api_params']['Password'];
	$gu_id = $api_obj->params['api_params']['SN'];
	//echo api_obj->get_md5_key();

	$data = array(
		'FirstName' => $FirstName,
		'LastName' => $LastName,
		'EMailAddress' => $EMailAddress,
		'CardID' => $CardID,
		'Address' => $Address,
		'City' => $City,
		'ZipCode' => $ZipCode,
		'Country' => $Country,
		'CellPhone' => $CellPhone,
		'FixedPhone' => $FixedPhone,
		'Fax' => $Fax,
		'v_id' => $v_id,
		'p_id' => $p_id,
		'password' => $password,
		'gu_id' => $gu_id,
		'a'=> 'active',
		'type'=> 'uphone'
	);
	//var_dump($data);
	$return = $api_obj->get_from_api($api_obj->config->wfs_api_url,$data);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$api_obj);
	
	$array = get_object_vars(json_decode($return));
	$api_obj->return_code = $array['response-code'];
	$api_obj->md5_key = '';
	$rdata = get_object_vars($array['data']);
	if(is_array($rdata))
	{
		if(!empty($_REQUEST['Format']) && $_REQUEST['Format'] == 'xml'){
			foreach($rdata as $key=>$value)
				$api_obj->push_return_xml("<$key>%s</$key>",$value);
		}else{
			foreach($rdata as $key=>$value)
				$api_obj->push_return_data($key,$value);
		}
	}else{
		$api_obj->push_return_data('M',$return);
	}
	$api_obj->write_response();
}
?>

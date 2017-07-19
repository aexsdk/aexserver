<?php
/*
	执行Action的行为
*/
api_register_account($api_object);

/*
	定义Action具体行为
*/
function get_message_callback($api_obj,$context,$msg){
	return sprintf($msg,$api_obj->return_code);
}

/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj,$context){
	$resp = $api_obj->write_return_xml();
	return $resp;
}

/*
 * 激活时，获取E164帐号
 */
function api_register_account($api_obj) 
{
	//设置回调函数
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$api_obj);
	
	/*
	 * 数据库需要的字段
			  v_v_date       		varchar       	no              有效期,可选 
			  v_account      		Varchar      	no              帐号 
			  v_password     		Varchar       	no              密码 
			  n_agent_id       		Int        		No              代理商ID 
			  n_agent_cs       		Int           	No              代理商计费方案 
			  n_call_cs       		Int        		No              用户计费方案 
			  n_balance       		Int       		No   			出库设计的余额 
			  v_currency_type  		Varchar   		No              货币类型 
			  n_free_period   		int                             免费时长 
			  n_hire_number   		int                             租期数 
			  n_valid_date_no  		Int                             有效期数 
			  v_agent_prefix  		Varchar                         代理商前缀 
			  v_product_type_prefix Varchar                         产品类型前缀 
			  v_cs_prefix    		Varchar                         计费方案前缀 
			  v_imei        		Varchar                         IMEI
	 */
	$acctParams = array (//生成ophone_get_account($acctParams)调用所需的数组集合
				"@DeviceID" => $api_obj->params['api_params']['deviceid'],
				"@Gu_ID" => $api_obj->params['api_params']['guid'],
				"@UserPassword" => $api_obj->params['api_params']['upass'],
				"@AgentID" => $api_obj->params['api_params']['agentid'],
				"@Agent_CS" => $api_obj->params['api_params']['agentcs'],
				"@Call_CS" => $api_obj->params['api_params']['callcs'],
				"@Balance" => $api_obj->params['api_params']['balance'],
				"@CurrencyType" => $api_obj->params['api_params']['currencyt'],
				"@valid_date_no" => $api_obj->params['api_params']['vdateno'],
				"@HireNumber" => $api_obj->params['api_params']['hireno'],
				"@FreePeriod" => $api_obj->params['api_params']['freeperiod'],
				"@product_type_prefix" => $api_obj->params['api_params']['ptypeprefix'],
				"@cs_prefix" => $api_obj->params['api_params']['csprefix'],
				"@agent_prefix" => $api_obj->params['api_params']['agentprefix']
			);
	
	//$api_obj->write_hint($acctParams['@DeviceID']);
	var_dump($acctParams);
		
	//请求获取E164帐号
	$storedProc = 'dbo.sp_Ophone_RegisterE164_New_Eztor;1';
	$r = radius_execute_proc($storedProc,$acctParams,$api_obj->config);
		
	if (is_array($r))
	{
		//$api_obj->write_hint($r['RETURN-CODE']);
		if ($r['RETURN-CODE'] < 0)
		{
			//Radius执行错误
			$api_obj->return_code = $r['RETURN-CODE'];
		} else {
			//返回正确的值
			echo array_to_string(',',$r);
		}
	} else {
		$api_obj->return_code = -208;
	}

	$api_obj->write_response();
}

?>

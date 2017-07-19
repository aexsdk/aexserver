<?php
/*
	执行Action的行为
*/
api_ophone_active($api_object);

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
	/*
	if (api_version_compare($api_obj->params['api_version'], '2.6.0') > 0) {
		$resp = $api_obj->write_return_xml();
	} else {
		if ($api_obj->return_code > 0)
		{
			$resp = $api_obj->write_return_param('response-code', 
							sprintf("%d,%s,%s", $api_obj->return_code, $api_obj->return_data['PIN'], $api_obj->return_data['PASS']));
		} else {
			$resp = $api_obj->write_return_param('response-code', $api_obj->return_code);
		}
	}
	*/
	//echo sprintf("%s,%d,2.2.2\r\n",$_REQUEST['v'],api_version_compare($_REQUEST['v'],'2.2.2'));
	if(api_version_compare($_REQUEST['v'],'2.2.2') > 0){
		$resp = $api_obj->write_return_params();
		$resp = $resp . $api_obj->write_return_xml();
		//echo "yyyy=$resp";
	}else{
		//echo "MD5_KEY=$api_obj->md5_key\r\n";
		$resp = $api_obj->write_return_param('response-code',
			array(
				1,//$api_obj->return_code,
				$api_obj->return_data['PIN'],
				$api_obj->return_data['PASS'],
				$api_obj->return_data['VALIDDATE']
				)
			);
		//echo $api_obj->return_data['RESPONSE-CODE'];
		//$resp = $api_obj->write_return_params();
	}
	return $resp;
}

/*首先到WFS进行判断是否可以激活
	*1.如果验证信息通过将返回用户入库时的详细信息
	*2.如果验证发现该手机已经激活成功，那么将直接从wfs返回激活绑定的终端号码和密码
*/
function ophone_judge_active($api_obj)
{
	//存储过程
	$sp_sql = "select * from ez_wfs_db.sp_wfs_get_ophone_active_info_beta($1, $2, $3, $4, $5, $6) ";
	
	//组合参数
	$pg_params = array (
						$api_obj->params['api_params']['imei'],
						$api_obj->params['api_params']['pno'],
						$api_obj->params['api_params']['upass'],
						$api_obj->params['api_params']['bsn'],
						$api_obj->params['api_params']['pid'],
						$api_obj->params['api_params']['vid']
						);					
	$wfs_db = new api_pgsql_db($api_obj->config->wfs_db_config,$api_obj);
	$active_array = $wfs_db->exec_db_sp($sp_sql, $pg_params);
	if (is_array($active_array))
	{
		//$api_obj->write_hint(array_to_string(',', $active_array));
		$api_obj->return_code = $active_array['p_return'];
		if ($active_array['p_return'] == 201)
		{
			//允许执行激活操作，返回允许执行激活操作字符串
			$CurrencyType = strtoupper($active_array['v_currency_type']);
			if ($CurrencyType == 'CNY' || $CurrencyType == 'CYN') {
				$CurrencyType = 'CYN';
			}
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
			/*
			$acctParams = array (//生成ophone_get_account($acctParams)调用所需的数组集合
				"@DeviceID" => $api_obj->params['api_params']['imei'],
				"@Gu_ID" => $api_obj->params['api_params']['pno'],
				"@UserPassword" => $api_obj->params['api_params']['upass'],
				"@AgentID" => $active_array['n_agent_id'],
				"@Agent_CS" => $active_array['n_agent_cs'],
				"@Call_CS" => $active_array['n_call_cs'],
				"@Balance" => $active_array['n_balance'],
				"@CurrencyType" => $CurrencyType,
				"@valid_date_no" => $active_array['n_valid_date_no'],
				"@HireNumber" => $active_array['n_hire_number'],
				"@FreePeriod" => $active_array['n_free_period'],
				"@product_type_prefix" => $active_array['v_product_type_prefix'],
				"@cs_prefix" => $active_array['v_cs_prefix'],
				"@agent_prefix" => $active_array['v_agent_prefix']
			);*/
			$acctParams = array (
				"deviceid" => $api_obj->params['api_params']['imei'],
				"guid" => $api_obj->params['api_params']['pno'],
				"upass" => $api_obj->params['api_params']['upass'],
				"agentid" => $active_array['n_agent_id'],
				"agentcs" => $active_array['n_agent_cs'],
				"callcs" => $active_array['n_call_cs'],
				"balance" => $active_array['n_balance'],
				"currencyt" => $CurrencyType,
				"vdateno" => $active_array['n_valid_date_no'],
				"hireno" => $active_array['n_hire_number'],
				"freeperiod" => $active_array['n_free_period'],
				"ptypeprefix" => $active_array['v_product_type_prefix'],
				"csprefix" => $active_array['v_cs_prefix'],
				"agentprefix" => $active_array['v_agent_prefix']
			);
			
			return $acctParams;
			
		} else if ($active_array['p_return'] == 202) { //该IMEI号码和该SIM卡号已经进行了激活绑定，返回激活信息
			
			$validPeriod = explode(" ", $active_array['v_v_date']); // --截取时间
			$validDate = $validPeriod['0'];
			$pin = $active_array['v_account'];
			$password = $active_array['v_password'];

			$api_obj->push_return_data('VALIDDATE',$validDate);
			$api_obj->push_return_data('PIN', $pin);
			$api_obj->push_return_data('PASS', $password);
			$api_obj->push_return_xml("<PIN>%s</PIN>", $pin);
			$api_obj->push_return_xml("<PASS>%s</PASS>", $password);
		}
		
	} else {
		
		//$api_obj->write_hint(sprintf("Active judge return %s", $active_array));
		$api_obj->return_code = -206;
	}
	
	return "";
}

/*
 * 发送要号请求，从Billing服务器获取帐号
 */
function ophone_acquire_account($api_obj, $acctParams)
{
	/*
	$storedProc = 'dbo.sp_Ophone_RegisterE164_New_Eztor;1';
	return radius_execute_proc($storedProc,$acctParams,$api_obj->config);
	*/
	
	//获取billing api url
	$db_obj = new api_pgsql_db($api_obj->config->wfs_db_config, $api_obj);
	$sp = "SELECT *FROM ez_wfs_db.sp_wfs_api_get_info_by_imei( $1, $2, $3, $4, $5)";
	$params = array(
			$api_obj->params['api_params']['bsn'],
			$api_obj->params['api_params']['imei'],
			$api_obj->params['api_params']['bsn'],
			$api_obj->params['api_params']['bsn'],
			'ophone'
	);
	$array = $db_obj->exec_db_sp($sp, $params);
	if (is_array($array) && !empty($array)) {
		$api_url = $array['v_api_ip']; 
		$secret = $array['v_serect'];
			
		$pp = array(
				//'action' => $api_obj->params['api_params']['action'],
				'action' => 'register_account'
			);
		$params = array_merge($pp, $acctParams);
			
		$str = '';
		foreach ($params as $key=>$value)
			$str .= $key."=".$value.',';
		//加密
		$en_string = api_encrypt($str, $secret);
			
		$api_data = array (
				'p' => $en_string,
				'v' => $api_obj->params['api_v'],
				'lang' => $api_obj->params['api_lang']
		);
	
		//调用billing api
		$billing_ret = $api_obj->get_from_api($api_url, $api_data);
		
		return api_string_to_array($billing_ret, ',', '=');
	}
}

/*
 * 执行激活的存储过程
 */
function ophone_do_active($api_obj, $activeArray)
{
	$pin        = $activeArray['@RE164'];
	$password   = $activeArray['@Password'];
	$validDate  = empty($activeArray['@valid_date'])?'0':$activeArray['@valid_date'];
	$email = '';
	$card_id = '';
		
	/*××××××××××××××××××在WFS中进行激活绑定××××××××××××××××××××*/
	$sp_sql     = "select * from ez_wfs_db.sp_wfs_active_ophone_beta($1, $2, $3, $4, $5, $6, $7) ";
	$pg_params  = array (
			$api_obj->params['api_params']['imei'],
			$api_obj->params['api_params']['pno'],
			$pin,
			$password,
			$api_obj->params['api_params']['username'],
			$email,
			$card_id
		);
	
	$wfs_db = new api_pgsql_db($api_obj->config->wfs_db_config,$api_obj);
	$active_result = $wfs_db->exec_db_sp($sp_sql, $pg_params);
	
	if (is_array($active_result))
	{	
		$active_return = $active_result['p_return'];
		$validDate     = $active_result['d_v_date'];
		if ($active_return > 0) { //在wfs进行执行激活绑定操作成功	
			
			$api_obj->return_code = $active_return;
			
			$api_obj->push_return_data('PIN', $pin);
			$api_obj->push_return_data('PASS', $password);
			$api_obj->push_return_xml("<PIN>%s</PIN>", $pin);
			$api_obj->push_return_xml("<PASS>%s</PASS>", $password);
			
		} else { //执行激活绑定失败
			
			$api_obj->return_code = $active_return;
			
		}
	} else {
		$api_obj->return_code = -228;
	}
}

/*
 * 激活
 */
function api_ophone_active($api_obj) 
{	
	//设置回调函数
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$api_obj);
	
	/*首先到WFS进行判断是否可以激活
	*1.如果验证信息通过将返回用户入库时的详细信息
	*2.如果验证发现该手机已经激活成功，那么将直接从wfs返回激活绑定的终端号码和密码
	*/
	$acctParams = ophone_judge_active($api_obj);
	
	if (is_array($acctParams))
	{
		//$api_obj->write_hint($acctParams['@DeviceID']);
		//var_dump($acctParams);
		
		//接着请求获取E164帐号
		$r = ophone_acquire_account($api_obj, $acctParams);
		if (is_array($r))
		{
			//$api_obj->write_hint($r['RETURN-CODE']);
			if ($r['RETURN-CODE'] < 0)
			{
				//Radius执行错误
				$api_obj->return_code = $r['RETURN-CODE'];
			} else {
				//激活
				ophone_do_active($api_obj, $r);
			}
		}
	} /*else {
		if ($api_obj->return_code <= 0) //访问wfs失败
			$api_obj->return_code = -206;
	}*/

	$api_obj->write_response();
}

?>

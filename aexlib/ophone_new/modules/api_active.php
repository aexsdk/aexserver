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

/*
 * 激活
 */
function api_ophone_active($api_obj)
{
	//设置回调函数
	//$api_obj->set_callback_func(get_message_callback,write_response_callback,$api_obj);

	/*首先到WFS进行判断是否可以激活
	 *1.如果验证信息通过将返回用户入库时的详细信息
	 *2.如果验证发现该手机已经激活成功，那么将直接从wfs返回激活绑定的终端号码和密码
	 */
	
	$acctParams = o_judge_active($api_obj);
	if (is_array($acctParams))
	{
		//接着请求获取E164帐号
		$activeArray = o_acquire_account($api_obj, $acctParams);
		if( is_array($activeArray))
		{
			if ($activeArray['RETURN-CODE'] < 0)
			{
				//Radius执行错误
				$api_obj->return_code = $activeArray['RETURN-CODE'];
			} else {
				//激活
				o_do_active($api_obj, $activeArray);
			}
		}
	} 
	$api_obj->write_response();
	
}

/*首先到WFS进行判断是否可以激活
 *1.如果验证信息通过将返回用户入库时的详细信息
 *2.如果验证发现该手机已经激活成功，那么将直接从wfs返回激活绑定的终端号码和密码
 */
function o_judge_active($api_obj)
{
	//存储过程
	$sp_sql = "select * from ez_wfs_db.sp_ophone_get_active_info_beta($1, $2, $3, $4, $5, $6) ";

	//组合参数
	$pg_params = array (
		$api_obj->params['api_params']['bsn'],
		$api_obj->params['api_params']['imei'],
		$api_obj->params['api_params']['pno'],
		$api_obj->params['api_params']['upass'],
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
			/*"{""agent_id"":""11863"",""call_cs"":""1002"",""agent_cs"":""1002"",""balance"":""50"",
			""currency_type"":""CNY"",""valid_date_no"":""24"",""free_period"":""0"",""hire_number"":""0"",
			""product_type_prefix"":""5"",""cs_prefix"":""39"",""agent_prefix"":""88""}"*/
			$active_array = get_object_vars( json_decode($active_array['t_charge_plan']));
			//允许执行激活操作，返回允许执行激活操作字符串
			$CurrencyType = strtoupper($active_array['currency_type']);
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
			$acctParams = array (
				"bsn" => $api_obj->params['api_params']['bsn'],
				"guid" => $api_obj->params['api_params']['imei'],
				"pno" => $api_obj->params['api_params']['pno'],
				"upass" => $api_obj->params['api_params']['upass'],
				"agentid" => $active_array['agent_id'],
				"agentcs" => $active_array['agent_cs'],
				"callcs" => $active_array['call_cs'],
				"balance" => $active_array['balance'],
				"currencyt" => $CurrencyType,
				"vdateno" => $active_array['valid_date_no'],
				"hireno" => $active_array['hire_number'],
				"freeperiod" => $active_array['free_period'],
				"ptypeprefix" => $active_array['product_type_prefix'],
				"csprefix" => $active_array['cs_prefix'],
				"agentprefix" => $active_array['agent_prefix']
			);
			return $acctParams;
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
function o_acquire_account($api_obj, $acctParams)
{
	/*
	 * 数据库需要的字段
		@DeviceID 		Varchar(100),
		@Gu_ID			varchar(100),	
		@PNO			varchar(100),	
		@UserName		varchar(100),
		@Email			varchar(100),
		@UserPassword		Varchar(100),			
		@AgentID 		integer =0,				
		@Agent_cs		Int = 0,				
		@Call_cs		Int,				
		@Balance  		integer,				
		@CurrencyType		Varchar(50),				
		@valid_date_no 		int,				
		@HireNumber		int=0,				
		@FreePeriod 		int=0,				
		@product_type_prefix	Varchar(50)='',			
		@cs_prefix		Varchar(50)='',			
		@agent_prefix		Varchar(50)='',			
		@RE164			varchar(50)='' output,
		@RPassword		varchar(50)='' output,
		@RValidDate		varchar(50)='' output
	 */
	$usr_name = $acctParams['bsn'] .'-'.$acctParams['guid'];
	$email =  $acctParams['pno'].'@eztor.com';
	$acctParams = array (//生成ophone_get_account($acctParams)调用所需的数组集合
		"@DeviceID" => $acctParams['bsn'],
		"@Gu_ID" => $acctParams['guid'],
		"@PNO" => $acctParams['pno'],
		"@UserName" => "$usr_name",
		"@Email" => $email,
		"@UserPassword" => $acctParams['upass'],
		"@AgentID" => intval($acctParams['agentid']),
		"@Agent_CS" => intval($acctParams['agentcs']),
		"@Call_CS" => intval($acctParams['callcs']),
		"@Balance" => intval($acctParams['balance']),
		"@CurrencyType" => $acctParams['currencyt'], 
		"@valid_date_no" =>	intval($acctParams['valid_date_no']),
		"@HireNumber" => intval($acctParams['hireno']),
		"@FreePeriod" => intval($acctParams['freeperiod']),
		"@product_type_prefix" => $acctParams['ptypeprefix'],
		"@cs_prefix" => $acctParams['csprefix'],
		"@agent_prefix" => $acctParams['agentprefix']
	);
	//请求获取E164帐号
	$storedProc = 'dbo.sp_Ophone_active;1';
	$r = radius_execute_proc($storedProc,$acctParams,$api_obj->config);
	if (is_array($r))
	{
		//$api_obj->write_hint($r['RETURN-CODE']);
		if ($r['RETURN-CODE'] < 0)
		{
			//Radius执行错误
			$api_obj->return_code = $r['RETURN-CODE'];
		}
	} else {
		$api_obj->return_code = -208;
	}
	return $r;
}

/*
 * 执行激活的存储过程
 */
function o_do_active($api_obj, $activeArray)
{
	$pin        = $activeArray['@RE164'];
	$password   = $activeArray['@RPassword'];
	$validDate  = empty($activeArray['@RValidDate'])?'0':$activeArray['@RValidDate'];
	$email = '';
	$card_id = '';

	/*××××××××××××××××××在WFS中进行激活绑定××××××××××××××××××××*/
	/*
	 * v_bsn				varchar,
	    v_gu_id 			varchar, 
	    v_wfs_attribute 	varchar, 
	    v_pid				varchar,
	    v_vid				varchar,
	    v_account 			varchar, 
	    v_password 			varchar, 
	  	v_valid_date		varchar,
	 * */
	$sp_sql     = "select * from ez_wfs_db.sp_ophone_active_beta($1, $2, $3, $4, $5, $6, $7, $8) ";
	$pg_params  = array (
		$api_obj->params['api_params']['bsn'],
		$api_obj->params['api_params']['imei'],
		$api_obj->params['api_params']['pno'],
		$api_obj->params['api_params']['pid'],
		$api_obj->params['api_params']['vid'],
		$pin,
		$password,
		$validDate
	);
	//var_dump($pg_params);
	$wfs_db = new api_pgsql_db($api_obj->config->wfs_db_config,$api_obj);
	//echo $api_obj->config->wfs_db_config;
	$active_result = $wfs_db->exec_db_sp($sp_sql, $pg_params);
	
	if (is_array($active_result))
	{
		$active_return = $active_result['p_return'];
		if ($active_return > 0) { //在wfs进行执行激活绑定操作成功	
				
			$api_obj->return_code = $active_return;
				
			$api_obj->push_return_data('PIN', $pin);
			$api_obj->push_return_data('PASS', $password);
			$api_obj->push_return_xml("<PIN>%s</PIN>", $pin);
			$api_obj->push_return_xml("<PASS>%s</PASS>", $password);
			
			//更新wfs
			$r = $api_obj->get_from_api($api_obj->config->wfs_api_url,
				array(
					'a' => 'wfs_update_imei_info',
					'bsn' => $api_obj->params['api_params']['bsn'],
					'imei' => $api_obj->params['api_params']['imei'],
					'pno' => $api_obj->params['api_params']['pno'],
					'ep' => $pin,
					'o_action' => 'update_active_info'
				)
			);
		} else { //执行激活绑定失败
			$api_obj->return_code = $active_return;
		}
	} else {
		$api_obj->return_code = -228;
	}
}

?>

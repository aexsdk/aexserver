<?php
/*
 * creater: lion wang
 * time: 2010.05.07
 * alter time: 2010.05.07
 * caption: uphone active
 *	
*/

api_uphone_action($api_object);

/*
	定义错误字符串处理的回调函数，正常情况下错误字符串已经可以按照语言和action取得。
*/
function get_message_callback($api_obj,$context,$msg){
	return sprintf($msg,$api_obj->return_code);
}


/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj,$context){
	$resp = $api_obj->write_return_params_with_json();		
	return $resp;
}


function api_uphone_action($api_obj) {
//	$api_obj->md5_key =	empty($_REQUEST['key'])?'':$_REQUEST['key'];
//	echo $api_obj->get_md5_key();
//	$api_obj->decode_param($api_obj->md5_key);
//	$api_obj->decode_param($api_obj->get_md5_key());
//	var_dump($api_obj->params['api_params']);
	
//	$FirstName = addslashes(trim($_REQUEST['FirstName'])) ? addslashes(trim($_POST['FirstName'])) : '';
//	$LastName = addslashes(trim($_REQUEST['LastName'])) ? addslashes(trim($_POST['LastName'])) : '';
//	$name = $FirstName . ' ' . $LastName;
//	$EMailAddress = addslashes(trim($_REQUEST['Email'])) ? addslashes(trim($_REQUEST['Email'])) : '';
//	$CardID = addslashes(trim($_REQUEST['CardID'])) ? addslashes(trim($_REQUEST['CardID'])) : '';
//	$Address = addslashes(trim($_REQUEST['Address'])) ? addslashes(trim($_REQUEST['Address'])) : '';
//	$City = addslashes(trim($_REQUEST['City'])) ? addslashes(trim($_REQUEST['City'])) : '';
//	$ZipCode = addslashes(trim($_REQUEST['ZipCode'])) ? addslashes(trim($_REQUEST['ZipCode'])) : '';
//	$Country = addslashes(trim($_REQUEST['Country'])) ? addslashes(trim($_REQUEST['Country'])) : '';
//	$CellPhone = addslashes(trim($_REQUEST['CellPhone'])) ? addslashes(trim($_REQUEST['CellPhone'])) : '';
//	$FixedPhone = addslashes(trim($_REQUEST['FixedPhone'])) ? addslashes(trim($_REQUEST['FixedPhone'])) : '';
//	$Fax = addslashes(trim($_REQUEST['Fax'])) ? addslashes(trim($_REQUEST['Fax'])) : '';
//	
//
//	//获取guid和产品属性  调用客户端的函数过程
//	/*$arr = "1001,6003,ABCDEF1234567890,05d0338c-5187-48d3-ad37-3ad9ebfabead";*/
	$v_id = empty($_REQUEST['v_id']) ? '' : $_REQUEST['v_id'];
	$p_id = empty($_REQUEST['p_id']) ? '' : $_REQUEST['p_id'];
	$attribute = empty($_REQUEST['attribute']) ? '' : $_REQUEST['attribute'];
	$password =  empty($_REQUEST['password']) ? '' : $_REQUEST['password'];
	$gu_id = empty($_REQUEST['gu_id']) ? '' : $_REQUEST['gu_id'];
	$type = empty($_REQUEST['type']) ? '' : $_REQUEST['type'];
	
	$FirstName = empty($_REQUEST['FirstName']) ? '' : $_REQUEST['FirstName'];
	$LastName = empty($_REQUEST['LastName']) ? '':  $_REQUEST['LastName'];
	$name = $FirstName . ' ' . $LastName;
	$EMailAddress = empty($_REQUEST['EMailAddress']) ? '' : $_REQUEST['EMailAddress'];
	$CardID = empty($_REQUEST['CardID']) ? '' : $_REQUEST['CardID'];
	$Address = empty($_REQUEST['Address']) ? '' : $_REQUEST['Address'];
	$City = empty($_REQUEST['City']) ? '' : $_REQUEST['City'];
	$ZipCode = empty($_REQUEST['ZipCode']) ? '' : $_REQUEST['ZipCode'];
	$Country = empty($_REQUEST['Country']) ? '' :  $_REQUEST['Country'];
	$CellPhone = empty($_REQUEST['CellPhone']) ? '' : $_REQUEST['CellPhone'];
	$FixedPhone = empty($_REQUEST['FixedPhone']) ? '' : $_REQUEST['FixedPhone'];
	$Fax = empty($_REQUEST['Fax']) ? '' : $_REQUEST['Fax'];
	
	
	
	//SQL 返回值 =2  该设备已经激活过 = 1	信息验证正确，允许激活 = -1 该设备没有入库 = -2 该设备没有出库 =-4; 获取参数信息失败
	try {
		require_once $api_obj->params['common-path'].'/api_pgsql_db.php';
		$db_obj = new api_pgsql_db($api_obj->config->wfs_db_config, $api_obj);
	
	
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$api_obj->set_callback_func(get_message_callback,write_response_callback, $api_obj);
	
		//v_bsn varchar, v_gu_id varchar, v_pid varchar, v_vid varchar, v_type varchar
		$sp = "SELECT *FROM ez_wfs_db.sp_wfs_api_get_info_by_imei( $1, $2, $3, $4, $5)";
		$params = array(
			'',
			$gu_id,
			$v_id,
			$p_id,
			$type
		);
		$array = $db_obj->exec_db_sp($sp, $params);
		if (is_array($array) && !empty($array)) {
			$api_url = $array['v_api_ip']; 
			$secret = $array['v_serect'];
			//获取激活信息
			//'$gu_id', '$attribute', '$v_id', '$p_id'
			$sp = "SELECT * FROM ez_wfs_db.sp_wfs_get_devices_active_info( $1, $2, $3, $4)";
			$params = array(
				$gu_id,
				$attribute,
				$v_id,
				$p_id
			);
			$rows = $db_obj->exec_db_sp($sp, $params);
			$n_return_value = intval($rows['n_return_value']);
			$v_v_date = $rows['v_v_date'];
			$v_account = $rows['v_account'];
			$v_account1 = $rows['v_account'];
			$v_password = $rows['v_password'];
			$n_agent_id = $rows['n_agent_id'];
			$n_balance = $rows['n_balance'];
			$v_currency_type = $rows['v_currency_type'];
			$n_free_period = $rows['n_free_period'];
			$n_hire_number = $rows['n_hire_number'];
			$n_agent_cs = $rows['n_agent_cs'] ? $rows['n_agent_cs'] : 0;
			$n_call_cs = $rows['n_call_cs'] ? $rows['n_call_cs'] : 0;
			$v_agent_prefix = $rows['v_agent_prefix'] ? $rows['v_agent_prefix'] : 'null';
			$v_product_type_prefix = $rows['v_product_type_prefix'] ? $rows['v_product_type_prefix'] : 'null';
			$v_cs_prefix = $rows['v_cs_prefix'] ? $rows['v_cs_prefix'] : 'null';
			$n_valid_date_no = $rows['n_valid_date_no'];
			switch ($n_return_value) {
				case 1:
					//'信息验证正确，允许激活';
					//通过Billing API在VoIP数据库创建帐号，获取帐号和密码 E164  password
					$e164Arr = array();
					$setArr = array(
						'action' => $api_obj->params['api_params']['action'],
						//'action' => 'uphone_active',
						'gu_id' => $gu_id,
						'wfs_attribute' => $attribute,
						'password' => $password,
						'agent_id' => $n_agent_id,
						'agent_cs' => $n_agent_cs,
						'call_cs' => $n_call_cs,
						'balance' => $n_balance,
						'currency_type' => $v_currency_type,
						'valid_date_no' => $n_valid_date_no,
						'hire_number' => $n_hire_number,
						'free_period' => $n_free_period,
						'product_type_prefix' => $v_product_type_prefix,
						'cs_prefix' => $v_cs_prefix,
						'agent_prefix' => $v_agent_prefix
					);
					$billing_arr = access_billing_api($api_url, $setArr, $secret);
					
				
					$billing_array = get_object_vars(json_decode($billing_arr));
				
					$data_array =  get_object_vars($billing_array['data']);
					$n_return_value1 = $data_array['n_return_value'];
					$v_account = $data_array['RE164'];
					$v_password = $data_array['Password'];
					$valid_date = $data_array['valid_date'];
				
					//获取E164就进行post信息填写
					if ($n_return_value1 > 0) {
						//'$gu_id', '$attribute', '$v_account', '$v_password', '$name', '$EMailAddress', '$CardID', '$Address', '$City', '$Country', '$ZipCode', '$FixedPhone', '$CellPhone', '$Fax'
						$sp2 = "SELECT * FROM ez_wfs_db.sp_wfs_devices_active($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14 )";
						$active_params = array(
							$gu_id, 
							$attribute, 
							$v_account, 
							$v_password, 
							$name, 
							$EMailAddress, 
							$CardID, 
							$Address, 
							$City, 
							$Country, 
							$ZipCode, 
							$FixedPhone,
							$CellPhone, 
							$Fax
						);
						$active_array = $db_obj->exec_db_sp($sp2, $active_params);
						
						$n_return_value2 = $active_array['n_return_value'];
						$v_v_date2 = $active_array['v_v_date'];
						if ($n_return_value2  > 0) {
							$api_obj->return_code = '101';
							//echo '激活成功';
							$rdata = array(
								'Account' => $v_account,
								'Passwrod' => $v_password
							);
						} else if ($n_return_value2 == -2) {
							//echo '生成用户信息失败';
							$api_obj->return_code = '-111';
						} else if ($n_return_value2 == -3) {
							//echo '生成帐号信息失败';
							$api_obj->return_code = '-112';
						} else {
							//echo '数据异常';
							$api_obj->return_code = '-113';
						}
					}else{
						switch ($n_return_value){
							case 0 : 
								//echo '插入E164失败';
								$api_obj->return_code = '-106';
							break;
							
							case -1 : 
								//echo '插入话费帐号失败';
								$api_obj->return_code = '-107';
							break;
							
							case -2 : 
								//echo '插入会员费帐号失败';
								$api_obj->return_code = '-108';
							break;
							
							case -3 : 
								//echo '获取号码失败';
								$api_obj->return_code = '-109';
							break;
							default:
								//echo '数据异常';
								$api_obj->return_code = '-110';
							break;
						}
					}
				break;
				case 2:
					//echo '该设备已经激活过';
					$rdata = array(
						'Account' => $v_account1,
						'Passwrod' => $v_password
					);
					$api_obj->return_code = '102';
				break;
				
				case -1:
					//echo '该设备没有入库';
					$api_obj->return_code = '-102';
				break;
				
				case -2:
					//echo '该设备没有出库';
					$api_obj->return_code = '-103';
				break;
				
				case -4:
					//echo '获取参数信息失败';
					$api_obj->return_code = '-104';
				break;
				
				default:
					//echo '数据异常';
					$api_obj->return_code = '-105';
				break;
			}	
		}else{
			$api_obj->return_code = '-101';
		}
		$api_obj->return_data['data'] = $rdata;
		$api_obj->write_response();
	} catch (Exception $e) {
		$rows = $e->getMessage();
		return $rows;
	}
}


/*
 *	wirter:  lion wang
 *	caption: access api by post
 *	version: 1.0
 *	time: 2010-04-23
 *	last time: 2010-04-23
 *	return:  api retuan result
 *
 * */
function access_billing_api($api_url, $params, $secret){
		if(is_array($params)){
			foreach ($params as $key=>$value)
				$str .= $key."=".$value.',';
		}
		//加密
		$en_string = api_encrypt($str, $secret);
		
		
		//post的参数
		$postfield = 'p='.$en_string.'&a='.$params['action'];
		$url = $api_url.'?'.$postfield;
		
		$ch = curl_init();	
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); 
		$ch_result = curl_exec($ch);
		curl_close($ch);

		return $ch_result;
} // access_api()




?>

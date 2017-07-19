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
	$v_id = empty($_REQUEST['VID']) ? '' : $_REQUEST['VID'];
	$p_id = empty($_REQUEST['PID']) ? '' : $_REQUEST['PID'];
	$gu_id = empty($_REQUEST['SN']) ? '' : $_REQUEST['SN'];
	$account = empty($_REQUEST['v_Account']) ? '' : $_REQUEST['v_Account'];
	$password = empty($_REQUEST['v_Password']) ? '' : $_REQUEST['v_Password'];
	$format = 'xml';
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
			$p_id,
			$v_id,
			'uphone'
		);
		$array = $db_obj->exec_db_sp($sp, $params);
		if (is_array($array) && !empty($array)) {
			$api_attribute = $array['v_attribute']; 
			$api_url = $array['v_api_ip']; 
			$secret = $array['v_serect'];
			$setArr = array(
				'action' => $api_obj->params['api_params']['action'],
				'v_id' => $v_id,
				'p_id' => $p_id,
				'gu_id' => $gu_id,
				'account' => $account,
				'password' => $password,
				'format' => $format
			);
			$billing_arr = access_billing_api($api_url, $setArr, $secret);
			
			echo $api_attribute;
			echo $billing_arr;
		}else{
			$api_obj->return_code = '-101';
		}
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
		//echo $url ;
		
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

<?php
/*
	执行Action的行为
*/
api_ophone_action($api_object);

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
	$resp = $api_obj->write_return_params_with_json();		
	return $resp;
}


/*
/uphone/ad/index.php?v_Account=13602648557&v_Password=A4984B3A3FA6&v_Lang=2052&VID=1001&PID=6003&SN=1d86c78a-7d6e-429d-8530-d889a3507d58
*/
function api_ophone_action($api_obj) {		
//	$v_bsn  =	trim($_POST['bsn']);   
//	$v_imei   =	trim($_POST['imei']);
//	$v_pno   =	trim($_POST['pno']);
//	$v_pid   =	trim($_POST['pid']);
//	$v_vid   =	trim($_POST['vid']);
//	$v_plan_params   =	trim($_POST['plan_params']);
	
	$v_bsn  =	trim($_REQUEST['bsn']);   
	$v_imei   =	trim($_REQUEST['imei']);
	$v_pno   =	trim($_REQUEST['pno']);
	$v_pid   =	trim($_REQUEST['pid']);
	$v_vid   =	trim($_REQUEST['vid']);
	$v_plan_params   =	trim($_REQUEST['plan_params']);
	
	$cp = $api_obj->json_decode($v_plan_params);
	$resaler = (is_object($cp) and isset($cp->agent_id) and !empty($cp->agent_id))?intval($cp->agent_id):0;
	$pg_params = array(
		$v_bsn, 
		$v_imei,
		$v_pno,
		$v_pid,
		$v_vid,
		$v_plan_params,
		$resaler
	);
	
	//var_dump($pg_params);
	$wfs_db = new api_pgsql_db($api_obj->config->wfs_db_config, $api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$wfs_db);
	
	//获取
	$sp_sql	= "select * from ez_wfs_db.sp_n_ophone_get_configure($1, $2, $3, $4, $5,$6,$7) ";
	$wfs_return = $wfs_db->exec_db_sp($sp_sql, $pg_params);
	
	if(is_array($wfs_return) && $wfs_return['p_return'] > 0){
		if (!empty($wfs_return['v_oem'])) {
			/*$data = array();
			$carrier_config = get_object_vars(json_decode($wfs_return['v_attribute']));
			foreach ($carrier_config as $key => $value){
				//获取OPHONE手机配置
				if ($key == 'OPHONE') {
					//var_dump($value);
					foreach ($value as $key => $ophone_value){
						$ophone_config = get_object_vars($ophone_value);
						$ophone_config['attribute'] = $wfs_return['v_oem'];
						array_push($data,$ophone_config);
					}
				}
			}
			$config_array = array(
				"OPHONE" => $data,
				"UPHONE" => array(
					array("IP" => "202")
				)
			);
			$wfs_return['v_attribute'] = json_encode($config_array);*/
			$resp = new stdClass();
			$r = json_decode($wfs_return['v_attribute']);
			if(is_object($r)){
				$api_obj->push_return_data('r',json_encode($r));
				$resp->OPHONE = $r->OPHONE;
				$resp->UPHONE = $r->UPHONE;
				$resp->OPHONE[0]->attribute = $wfs_return['v_oem'];
				$resp = json_encode($resp);
				$api_obj->push_return_data('data',$resp);
				echo $resp;
				return ;
			}
		}
		//$api_obj->write_response();
	}
	echo 'error';
	
}


?>

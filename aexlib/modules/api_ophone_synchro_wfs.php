<?php/*	执行Action的行为*/api_ophone_action($api_object);/*	定义Action具体行为*//*	定义错误字符串处理的回调函数，正常情况下错误字符串已经可以按照语言和action取得。	但是有时候我们需要在字符串中格式一些其他的参数，如：电话号码，姓名什么的。	例如：		解除绑定失败的错误字符串：号码%1s与本手机解除绑定失败，代码[%0d]，该手机已经和%2s绑定。		假设本手机号码在变量$api_obj->params['api_params']['pno']中，已经绑定的号码在	$api_obj->return_data['p_bind_no']中，那么我们就需要		function get_message_callback($api_obj,$context,$msg){			return sprintf($msg,$api_obj->return_code,$api_obj->params['api_params']['pno'],$api_obj->return_data['p_bind_no']);		}*/function get_message_callback($api_obj,$context,$msg){	return sprintf($msg,$api_obj->return_code);}/*	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。*/function write_response_callback($api_obj,$context){		$resp = $api_obj->write_return_xml();		return $resp;}function api_ophone_action($api_obj) {			$v_bsn =	trim($_POST['v_bsn']);   	$v_gu_id   =	trim($_POST['v_gu_id']);	$n_resaler   =	trim($_POST['n_resaler']);	$v_charge_plan  =	trim($_POST['v_charge_plan']);	$v_bind_pno   =	trim($_POST['v_bind_pno']);	$v_bind_epno   =	trim($_POST['v_bind_epno']);	$n_status  = trim($_POST['n_status']);   	$d_active_time   =	trim($_POST['d_active_time']);	$d_os_time   =	trim($_POST['d_os_time']);	$d_is_time   =	trim($_POST['d_is_time']);	$v_remark   =	trim($_POST['v_remark']);	$v_pid  =	trim($_POST['v_pid']);	$v_vid  =	trim($_POST['v_vid']);	$config_json  =	trim($_POST['config_json']);	$pg_params = array(		$v_bsn ,		$v_gu_id,		$n_resaler,		$v_charge_plan,		$v_bind_pno,		$v_bind_epno,		$n_status , 		$d_active_time,		$d_os_time,		$d_is_time,		$v_remark,		$v_pid,		$v_vid,		$config_json	);	$api_obj->write_hint(array_to_string(',',$pg_params));		$wfs_db = new api_pgsql_db($api_obj->config->wfs_db_config, $api_obj);	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。	//如需要获得返回的余额可以用，$context['p_balance']	$api_obj->set_callback_func(get_message_callback,write_response_callback,$wfs_db);		//获取	$sp_sql	= "select * from ez_wfs_db.sp_ophone_synchro_wfs($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14) ";	$wfs_return = $wfs_db->exec_db_sp($sp_sql, $pg_params);		$carrier_name = empty($api_obj->config->carrier_name) ? 'error' : $api_obj->config->carrier_name;	if( $wfs_return['p_return'] > 0){		echo $carrier_name;	}else{		echo 'error';	}	}?>
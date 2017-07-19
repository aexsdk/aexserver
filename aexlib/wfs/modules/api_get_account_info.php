<?php
/*
	执行Action的行为
*/
api_uphone_action($p_params,$api_object);

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
	//$api_obj->write_trace(0,'Run here');
	$resp = array_to_string("\r\n",$api_obj->return_data);//$api_obj->write_return_params_with_json();		
	return $resp;
}

class class_uphone_action extends api_base_class{
	function get_state($state){
		$msg = $this->get_message(900+$state,'Unknow');
		return $msg;
	}

	function get_rate_plan($rp,$row){
		$code = sprintf('99%d',$rp);
		$msg = $this->get_message($code,'-');
		if($row['Hire'] != 0){
			$msg = sprintf($msg,$this->get_hire_period($row['HirePeriod']),$this->get_hire_type($row['HireType']));
		}
		return $msg;
	}
	
	function get_active_period($value,$row){
		if($value<0){
			$msg = $this->get_message('972','-');
			$msg = sprintf($msg,-1*$value,$this->get_hire_type(1));
		}else{
			$msg = $this->get_message('973','-');
			$msg = sprintf($msg,$value,$this->get_hire_type(1));
		}
		return $msg;
	}
	
	function get_hire_period($value){
		if($value<0){
			$msg = $this->get_message('970','-');
			$msg = sprintf($msg,-1*$value);
		}else{
			$msg = $this->get_message('971','-');
			$msg = sprintf($msg,$value);
		}
		return $msg;
	}
	
	function get_hire_type($value){
		$msg = $this->get_message(sprintf('98%d',$value),'-');
		return $msg;
	}
}

function api_handle_row($connext,$index,$row){
	//var_dump($row);
	//reset($row);
	foreach($row as $key=>$value){
		if($connext){
			switch($key){
			case 'State':
			case 'Status':
				$value = $connext->get_state($value);
				//$connext->api_object->write_hint(sprintf("%s=%s<br>",$key,$value));
				break;
			case 'ChargeScheme':
				$connext->api_object->push_return_data('cs_id',$value);
				$value = $connext->get_rate_plan($value,$row);
				//$connext->api_object->write_hint(sprintf("%s=%s<br>",$key,$value));
				break;
			case 'ActivePeriod':
				$connext->api_object->push_return_data('WarrantyPeriod',$connext->get_active_period($value,$row));
				break;
			default:
				$value = mb_convert_encoding($value,"UTF-8","GB2312");
				break;
			}
			$connext->api_object->push_return_data($key,$value);
		}
	}
}
/*
/uphone/ad/index.php?v_Account=13602648557&v_Password=A4984B3A3FA6&v_Lang=2052&VID=1001&PID=6003&SN=1d86c78a-7d6e-429d-8530-d889a3507d58
*/
function api_uphone_action($p_params,$api_obj) {
	require_once $p_params['common-path'].'/api_mssql_db.php';
	
	//var_dump($p_params);
	$action_obj = new class_uphone_action($api_obj->config,$api_obj);
	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$action_obj);

	$sql = sprintf("select * from vi_devices_c where e164='%s' and password='%s'",$_REQUEST['v_Account'],api_decrypt($_REQUEST['v_Password'],'123456'));
	$billingdb->exec_query($sql,array(),api_handle_row,$action_obj);
	
	$api_obj->write_response();
	
}
?>

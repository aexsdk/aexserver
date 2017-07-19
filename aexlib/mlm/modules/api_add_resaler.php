<?php
/*
	执行Action的行为
*/
api_add_resaler($api_object);

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
	$resp = $api_obj->write_return_params();
	//当返回错误的时候以XML格式返回
	if($api_obj->return_code <= 0){
		$resp .= $api_obj->write_return_xml();			//按照老的手机需要的返回格式处理参数
	}
	//按照老的手机处理格式处理返回代码，正确的处理返回代码手机才会从请求界面中退出。
	$resp = $resp. $api_obj->write_return_param('response-code',$api_obj->return_code);
	return $resp;
}

function api_add_resaler($api_obj) {
	$v_order_num = explode(':', $api_obj->params['api_params']['orderid']);
	$pv_type = strtolower($v_order_num['0']);
	$pv_value = $v_order_num['1'];
	switch($pv_type){
	case 'pv':
		//直销用电子货币报单
		$pv_type = 'b2b';
		$v_order_num = 'NULL';
		$sp_sql = "select * from ez_marketing_db.sp_mlm_n_cmd_add_member($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11)";
		break;
	case 'pv1':
		//直销虚拟报单
		$pv_type = 'vb2b';
		$v_order_num = 'NULL';
		$sp_sql = 'select * from ez_marketing_db.sp_mlm_n_cmd_add_vmember($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11)';
		break;
	default:
		//直销通过商城订单报单
		$api_obj->return_code = -202;
		$api_obj->write_response();
		return;
	}
	
	/*"v_bsn" character varying, "v_imei" character varying, "v_phone_number" character varying, 
	"v_password" character varying, "v_orde_number" character varying, "v_recommen_id" character varying, 
	"v_parents_id" character varying, "v_card_id" character varying, "v_resaler_phone" character varying, 
	"v_resaler_password" character varying, "v_pv" character varying*/
	
	/*action=add_resaler,bsn=MT012345678901234567,imei=357116020326990,pno=13760334595,pin=10053,
	 * pass=321321,prefix=0086,o_pin=5188000003,o_pass=123123,orderid=PV:1000,recommend=10053,
	 * manager=13760334595,cardid=22,resaler_caller_id=15014020610,resaler_password=321321
	 */
	$mlm_params = array(
						$api_obj->params['api_params']['bsn'],						
						$api_obj->params['api_params']['imei'],
						$api_obj->params['api_params']['pno'],
						$api_obj->params['api_params']['pass'],
						'NULL',
						$api_obj->params['api_params']['recommend'],
						$api_obj->params['api_params']['manager'],       		
						$api_obj->params['api_params']['cardid'],    	
						$api_obj->params['api_params']['resaler_caller_id'],   
						$api_obj->params['api_params']['resaler_password'],
						$pv_value
						);
	//$api_obj->write_hint(array_to_string("\r\n",$mlm_params));
	$mlm_db = new api_pgsql_db($api_obj->config->mlm_db_config,$api_obj);
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$api_obj);
	$mlm_return = $mlm_db->exec_db_sp($sp_sql,$mlm_params);
	if(is_array($mlm_return)){
		if($api_obj->return_code > 0)
		{
			//存储过程返回成功，写入成功的参数和代码
			//echo "存储过程返回成功，写入成功的参数和代码";
			//$account = "<USER ACCOUNT='" . $mlm_return ['p_user_id'] . "' PASSWORD='" . $mlm_return ['p_user_pass'] . "' ISROOT='" . $mlm_return ['p_is_root']."' >%s<USER>";
			
			 $api_obj->push_return_data('01',$mlm_return['v_user_name']);
			 $api_obj->push_return_data('02',$mlm_return['v_nick_name']);
			 $api_obj->push_return_data('03',$mlm_return['v_card_id']);
			 $api_obj->push_return_data('04',$mlm_return['v_address']);
			 $api_obj->push_return_data('05',$mlm_return['v_post']);
			 $api_obj->push_return_data('06',$mlm_return['v_star']);
			 $api_obj->push_return_data('balance',$mlm_return['v_balance']);
				
			if($api_obj->return_code < 100)
				$api_obj->return_code = $api_obj->return_code + 100;
		}else{
			//存储过程执行成功，返回值小于0，表示请求失败。下面可以针对每一个失败返回失败需要的参数，如果没有则系统会自动处理返回代码和错误信息
			//错误信息根据语言不同在相应的XML文件中定义
			if($api_obj->return_code > -100)
				$api_obj->return_code = $api_obj->return_code - 100;		//如果返回值在0~-100之间，则调整为-100以上，-100以内的系统使用
		}
		//写返回的信息
		$api_obj->write_response();
	}
	
}
?>

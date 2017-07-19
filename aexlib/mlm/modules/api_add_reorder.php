<?php
/*
	执行Action的行为 这个存储过程还没有实现，等王天翔实现了，既可以引用了
*/
api_add_reorder($api_object);

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

function api_add_reorder($api_obj) {
	$api_obj->return_code = -99;
	$api_obj->write_response();
	return;
	$sp_sql	= "select * from ez_marketing_db.sp_mlm_add_reorder($1,$2,$3,$4,$5,$6,$7,$8,$9,$10)";

	//var_dump($api_obj );	
	$mlm_params = array(
							$api_obj->params['api_params']['v_bsn'], 
							$api_obj->params['api_params']['v_imei'],
							$api_obj->params['api_params']['v_pno'],
							$api_obj->params['api_params']['v_pass'],
							$api_obj->params['api_params']['v_order_id'],
							$api_obj->params['api_params']['v_card_id'],
							$api_obj->params['api_params']['v_o_num'],
							$api_obj->params['api_params']['v_o_pass'],//原密码，账号
							$api_obj->params['api_params']['v_n_phone'],
							$api_obj->params['api_params']['v_n_pass']//新密码，账号
							);
								
	$mlm_db = new api_pgsql_db($api_obj->config->mlm_db_config,$api_obj);
	$mlm_return = $mlm_db->exec_db_sp($sp_sql,$mlm_params);
	if(is_array($mlm_return)){
		if($api_obj->return_code > 0)
		{
			//存储过程返回成功，写入成功的参数和代码
			//echo "存储过程返回成功，写入成功的参数和代码";			
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

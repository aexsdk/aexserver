<?php
/*
	执行Action的行为
*/
api_active($p_params,$api_object);

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
	//echo $msg.",".$api_obj->return_code."\r\n";
	return sprintf($msg,$api_obj->return_code);
}

/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj,$context){
	    $resp = "";
		//$resp = $resp.$api_obj->write_return_params();      //按照老的手机需要的返回格式处理参数
		$resp = $resp.$api_obj->write_return_xml();			//write xml format response
		//按照老的手机处理格式处理返回代码，正确的处理返回代码手机才会从请求界面中退出。
		//$resp = $resp. $api_obj->write_return_param('response-code',$api_obj->return_code);
		return $resp;
}

//
function bind_voip_account($api_obj, $userid, $pno, $opass)
{
	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
	$rdata = $billingdb->mlm_bind_voip_account($userid,$pno,$opass);
	//var_dump($rdata);
	if(is_array($rdata)){
		//$billingdb->api_object->return_data['success'] = 'true';
		if(!empty($_REQUEST['Format']) && $_REQUEST['Format'] == 'xml'){
			foreach($rdata as $key=>$value)
				$api_obj->push_return_xml("<$key>%s</$key>",$value);
		}else{
			foreach($rdata as $key=>$value)
				$api_obj->push_return_data($key,$value);
		}
		$billingdb->set_return_code(101);
	}else{
		$billingdb->set_return_code(-101);
	}
}

function api_active($p_params,$api_obj) {
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$api_obj);
	
	//首先判断优会通功能是否激活。未激活则不能激活MLM系统。
	$opin = $api_obj->params['api_params']['o_pin'];
	$opass = $api_obj->params['api_params']['o_pass'];
	if (empty($opin) || empty($opass))
	{
		$api_obj->return_code = -105;
		//写返回的信息
		$api_obj->write_hint($api_obj->error_obj->error_array);
		$api_obj->write_response();
		return;
	}
	
	$sp_sql	= "select * from ez_marketing_db.sp_mlm_n_cmd_user_active($1,$2,$3,$4,$5) ";

	/*$parameter ['v_bsn'], $parameter ['v_imei'], $parameter ['v_pno'],
	         $parameter ['v_upass'] ,$parameter['v_uname']*/
	//var_dump($api_obj );
	$mlm_params = array(
						$api_obj->params['api_params']['bsn'], 
						$api_obj->params['api_params']['imei'], 
						$api_obj->params['api_params']['pno'],
						$api_obj->params['api_params']['pass'],
						$api_obj->params['api_params']['user_name']
						);
	$mlm_db = $api_obj->log_db;//new api_pgsql_db($api_obj->config->log_db_config,$api_obj);
	$mlm_return = $mlm_db->exec_db_sp($sp_sql,$mlm_params);
    //var_dump($mlm_return);
	if(is_array($mlm_return))
	{
		$api_obj->return_code = $mlm_return['p_return'];
		//echo "return code ".$api_obj->return_code."\r\n";
		if($api_obj->return_code > 0)
		{
			//存储过程返回成功，写入成功的参数和代码
			//echo "存储过程返回成功，写入成功的参数和代码";
			$account = "<USER ACCOUNT='" . $mlm_return ['p_user_id'] . "' PASSWORD='" . $mlm_return ['p_user_pass'] . "' ISROOT='" . $mlm_return ['p_is_root']."' >%s</USER>";
			$api_obj->push_return_xml($account,'1');
			$api_obj->push_return_xml("<ACTIVE-OK>1</ACTIVE-OK>","");
			
			$api_obj->push_return_data('ACCOUNT', $mlm_return ['p_user_id'] );
			$api_obj->push_return_data('PASSWORD', $mlm_return ['p_user_pass'] );
			$api_obj->push_return_data('ISROOT', $mlm_return ['p_is_root'] );
			if($api_obj->return_code < 100)
				$api_obj->return_code = $api_obj->return_code + 100;
			//激活成功更新与Billing系统的绑定
			//mlm_bind_voip_account
			/*
			$config = get_billing_api($api_obj)
			if(empty($config['v_serect']))
				$opass = $api_obj->params['api_params']['opass'];
			else 
				$opass = api_encrypt($api_obj->params['api_params']['opass'],$config['v_serect']);
			if(is_array($config)){
				$r = $api_obj->get_from_api($config['v_api_ip'],array(
							'a' => 'mlm_bind',
							'mlm_account' => p_user_id,
							'e164' => $api_obj->params['api_params']['opin'],
							'pass' => $opass
							));
			}
			*/
			bind_voip_account($api_obj, $mlm_return['p_user_id'], $api_obj->params['api_params']['pno'], $opass);
			
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

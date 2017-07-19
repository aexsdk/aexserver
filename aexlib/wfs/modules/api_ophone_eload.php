<?php
/**
 * 执行空中充值操作
 * 	手机端发送到服务端PHP的参数：
	Field Name	Field Type	Not Null	Description	
	pin			varchar		No			终端号码	
	pass		Varchar		No			终端账号密码	
	rpin 		varchar 	no	  		充值卡帐号	
	rpass		varchar 	no			充值卡密码	

	传入参数格式：
	action=recharge,bsn=bsn,imei=,pno=13145887179,pin=32000026,pass=88888,rpin=54675,rpass=33333
	
	服务端PHP返回到手机端的参：
	成功		R		M（ nbalance， ctype， freeduration， vdate）		Other	
			301		优会通余额为100CNY,免费通话时长为0。有效期至2008-8-8			
	失败	   	-301	pin or password error		
	   		-302	, pin is expired		
	   		-303  	account not exist		
	   		-304	recharge－pin value is 0,recharge faid		
	   		-305	扣减金额不足以支付所欠费用		
	   		-306	源pin的代理商不为0或者与目的帐号属于不同的代理商		
	   		-307	源帐号与目的帐号货币类型不一致		
 */

api_ophone_action($api_object);

/*
 array(5) { ["RETURN-CODE"]=>  int(60)
 ["RADIUS_RESP"]=>  int(2) 
 ["reNewBalance"]=>  string(6) "4.2000" 
 ["reValue"]=>  string(6) "2.0000"
 ["reCurrencyType"]=>  string(3) "CNY" } 
 * */
function get_message_callback($api_obj,$context,$msg){
	return sprintf($msg,$api_obj->return_code,
	                    //$api_obj->return_data['balance'],
	                    $api_obj->return_data['reValue'],
	                    $api_obj->params['api_params']['epno'],
	                    $api_obj->return_data['reNewBalance'],
	                    $api_obj->return_data['reCurrencyType']
	                    
	);
}
/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj,$context){
	$resp = $api_obj->write_return_xml();
	return $resp;
}

/*
	执行Action的行为
*/
/*
 * 1.sp_wfs_judge_recharger_onair
 *  功      能：判断送过来的两个手机的相关资料是否符合充值的要求
 *  输入参数：
 *                gawm_strbsn character varying , 
 *                gawm_strimei character varying, 
 **                tg_phonenumber character varying,
 *                g_awm_nvram_data_ophone_pin character varying
 *                v_password character varying
 *                "value" integer
 *  输出参数：
 *                 tg_account character varying,             --被充值手机号码的 E164 终端号码
 *                 sp_wfs_judge_recharger_onair integer --执行情况的返回值
 *
 *   -1; --查询号码时数据库运行出错，请再试一次，或者联系DBA
 *   -2; --被叫手机号码的Accout少于一个，或者多于一个，数据库数据出错了，请通知数据库管理人员
 *   -3; --主充值手机的记录不唯一，数据库记录出现错误，请通知数据库管理人员
 *   -4; --"空中充值"服务密码为空，意味着该号码没有给其他号码充值的权限，请垂询运营管理人员或经销商
 *    1; --查询号码成功
 *  语      法：
 *    ez_wfs_db.sp_wfs_judge_recharger_onair(IN gawm_strbsn character varying, IN gawm_strimei character varying, IN tg_phonenumber character varying, IN g_awm_nvram_data_ophone_pin character varying, IN v_password character varying, IN "value" integer, OUT tg_account character varying, OUT sp_wfs_judge_recharger_onair integer)
 * 
 */
function api_ophone_action($api_obj) {
	$pg_params = array(
	   $api_obj->params['api_params']['bsn'],
	   $api_obj->params['api_params']['imei'],
	   $api_obj->params['api_params']['epno'],//被充值人的手机号
	   $api_obj->params['api_params']['pin'],//本手机的PIN号
	   $api_obj->params['api_params']['pass'],//本手机的PIN号对应的操作密码
	   $api_obj->params['api_params']['evalue'],//充值金额
	);
    //echo "ophone_judge_eload";
    $sp_sql = "SELECT *FROM ez_wfs_db.sp_wfs_judge_recharger_onair_beta( $1, $2, $3, $4, $5, $6)";
    $wfs_db = new api_pgsql_db($api_obj->config->wfs_db_config,$api_obj);
	$wfs_return = $wfs_db->exec_db_sp($sp_sql, $pg_params);
    //var_dump($wfs_return);
    if($wfs_return['sp_wfs_judge_recharger_onair'] > 0)
	{
		$rdata  = array(
				'callee_pin' => $wfs_return ['tg_account'], 
				'n_return_value' => $wfs_return ['sp_wfs_judge_recharger_onair'] 
	    );
	    echo $rdata['callee_pin']."|".$rdata['n_return_value'];
	    
	    
	}else{
		//$rdata['n_return_value'] = $wfs_return ['sp_wfs_judge_recharger_onair'];
	    $api_obj->set_callback_func(get_message_callback,write_response_callback,$wfs_return);
		$api_obj->return_code = $wfs_return['sp_wfs_judge_recharger_onair'];
		//存储过程执行成功，返回值小于0，表示请求失败。下面可以针对每一个失败返回失败需要的参数，如果没有则系统会自动处理返回代码和错误信息
		$api_obj->write_response();
		exit;
	}
}

?>


<?php
/*
	执行Action的行为
*/
api_action($api_object);

/*
 * 查询费率
 * type: 0 = 软件拨打，1=回拨(WAP和手机)
			caller:主叫号码
			callee:被叫号码
 */
function api_action($api_obj)
{
	require_once __EZLIB__.'/common/api_billing_pgdb.php';
	$pin	=	$_REQUEST['pin'];	
	$pass 	= 	$_REQUEST['pass'];
	$type 	=	isset($_REQUEST['type']) ? $_REQUEST['type'] : 0;
	$caller = $_REQUEST['caller'];
	$callee = $_REQUEST['callee'];
	 	
	if(isset($_REQUEST['pin']) && isset($_REQUEST['pass'])){
		//如果请求提供了pin和pass则使用请求中的pin和pass,Pass为使用key+pin作为密钥的MD5加密串
		if (isset($_REQUEST['key']))
			$pass = api_decrypt($_REQUEST['pass'],$_REQUEST['key'].$_REQUEST['pin']);
		else 	
			$pass = $_REQUEST['pass'];
	}

	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);

	//把特殊前缀的呼叫先解析好放在数组里
	$callee_prefix = check_callee_prefix ( $api_obj, $callee );
	$api_obj->push_return_data("s.prefix",$callee_prefix['prefix']);
	$api_obj->push_return_data("s.callee",$callee_prefix['callee']);
	if($callee_prefix['prefix'] == ''){
		//号码中不包含前缀，先检查本地拨号规则，然后再次进行号码前缀检查
		//调整$Callee号码以便区分直接拨手机号码和使用0086拨号的区别
		$callee = add_callee_prefix($callee,$api_obj->config->inner_prefix);
		//再次检查号码前缀
		$callee_prefix = check_callee_prefix ( $api_obj, $callee );
	}		
	$route_db = new class_billing_pgdb($api_obj->config->route_db_config, $api_obj );
	//首先要做号码替换，调用相关数据的ez_utils_db.sp_build_prefix把号码变换为带地区和运营商标识的号码：国码+运营商+省份代码+区号+'-'+号码
	//经过变换后的主被叫号码为：
	//     特殊前缀+':'+国码+':'+运营商代码+省市代码+':'+区号+'：'+电话号码 ，如：A201:86:UGD:756:15602530665
	$def_prefix = isset($api_obj->params ['api_params'] ['prefix'])?$api_obj->params ['api_params'] ['prefix']:"0086";
	$caller_s = check_phone_number ( $caller, $def_prefix );
	$callee_s = check_phone_number ( $callee_prefix ['callee'], $def_prefix );
	$caller = $route_db->phone_build_prefix ($caller_s);
	$callee = $route_db->phone_build_prefix ($callee_s);
	$cp = $callee_prefix ['prefix'] == '' ? '' : $callee_prefix ['prefix'].':';		
	//var_dump($api_obj->params['api_params']);
	if($type == 0){
		$callee = 'G' . ($callee == $callee_s ? $cp.$callee : $callee_prefix ['prefix'].':'.$callee);
		$api_obj->push_return_data('callee',$callee);
		$billing_return = $billingdb->billing_get_rate(array(
			'pin' => $pin,
			'callee' => $callee
			));		
	}else{
		//先呼主叫次序不变
		$caller = 'A' . ($caller == $caller_s ? $cp.$caller : $callee_prefix ['prefix'].':'.$caller);
		$callee = 'B' . ($callee == $callee_s ? $cp.$callee : $callee_prefix ['prefix'].':'.$callee);
		$api_obj->push_return_data('caller',$caller);
		$api_obj->push_return_data('callee',$callee);
		$billing_return = $billingdb->billing_get_callback_rate(array(
			'pin' => $pin,
			'caller' => $caller,
			'callee' => $callee
			));
	}
	//var_dump($billing_params);
    if(is_array($billing_return)){
    	//$api_obj->push_return_data('data',$billing_return);
        if($billing_return['RETURN-CODE']> 0 ){ 	   
        	//费率
			$api_obj->push_return_data('rate',$billing_return['rate']);
			//费率单位
			$api_obj->push_return_data('currency_type',$billing_return['currency_type']);
        }
		$api_obj->return_code = $billing_return['RETURN-CODE'];
    }
	
	//写返回的信息
	$api_obj->write_response();
}
?>

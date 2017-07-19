<?php
error_reporting(E_ALL ^ E_NOTICE);//ignore the "notice" error

require_once (__EZLIB__.'/common/api_common.php');				
require_once (__EZLIB__.'/common/api_radius_funcs.php');				
require_once (__EZLIB__.'/common/api_log_class.php');				

require_once __EZLIB__.'/common/api_billing_mssql.php';
require_once __EZLIB__.'/common/api_billing_pgdb.php';
require_once (__EZLIB__.'/common/sms_server.php');				
require_once (__EZLIB__.'/common/api_json.php');	

if(!function_exists('json_encode')){
	$GLOBALS['JSON_OBJECT'] = new Services_JSON();
	
	function json_encode($value){
		return $GLOBALS['JSON_OBJECT']->encode($value);
	}
   
	function json_decode($value){
		return $GLOBALS['JSON_OBJECT']->decode($value);
	}
}			

class api_std{
	public $api_obj;
	/**
	 * 构造函数，传入配置参数以及
	 */
	public function __construct($api_obj)
	{
		$this->api_obj = $api_obj;
	}

	function __destruct() {
		$this->write_action_log();
    }
}

function ivr_check_caller($caller)
{
	if(function_exists('oem_ivr_check_caller')){
		return oem_ivr_check_caller($caller);
	}else{
		return true;
	}
}

function ga_active($api_obj,$r,$accessno,$caller,$callerip,$pass,$cfmpass)
{
	$def_prefix = isset($api_obj->config->default_prefix)?$api_obj->config->default_prefix:'0086';
	$pno = check_phone_number($caller,$def_prefix);
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	
	if($pass != $cfmpass)
	{
		$r['r_code'] = -1;
		$r['r_file'] = sprintf('%1$s/confirm_neq_new_pass',$api_obj->config->ivr_path);
		return $r;
	}
	
	$ra = $billing_db->billing_create_account(array(
		'pin' => $pno,
		'caller' => $pno,
		'pass' => $pass,
		));
	if(is_array($ra)){
		$r['r_code'] = $ra['RETURN-CODE'];
		$api_obj->push_return_data("Active",array_to_string(",",$ra));
		if($r['r_code'] <= 0){
			$r['r_file'] = $api_obj->config->ivr_path.'/active_fail';
		}else{
			$r['r_code'] = 1;
			$r['r_e164'] = $ra['E164'];
			$r['r_file'] = $api_obj->config->ivr_path.'/active_success';
		}
		$api_obj->return_code = $r['r_code'];
	}else{
		//返回不是数组
		$r['r_code'] = -100;
		$r['r_file'] = sprintf('%1$s/system_error_and_try_again',$api_obj->config->ivr_path);
	}
	$api_obj->no_log = FALSE;
	
	return $r;
}

/*
 * 定义错误字符串处理的回调函数，正常情况下错误字符串已经可以按照语言和action取得。
 */
function get_message_callback($api_obj, $context, $msg) {
	return sprintf ( $msg, $api_obj->return_code);
}

/*
 * 写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
 */
function write_response_callback($api_obj, $context) {
	$r = array(
		'r_retrun_code' => $api_obj->return_code,
		'r_file' => $api_obj->config->ivr_path.'/system_error_and_try_again'
	);
	if($api_obj->return_code > 0)
		$r['r_code'] = 1;
	else{ 
		$r['r_code'] = $api_obj->return_code;
		switch($r['r_code'])
		{
			case -151:
			case -152:
			case -153:
				$r['r_file'] = $api_obj->config->ivr_path.'/callback_fail_no_route';
				break;
			case -150:
			default:
				$r['r_file'] = $api_obj->config->ivr_path.'/callback_fail_no_enough';
				break;
		}
	} 
	return array_to_string(",",$r);
}

function ga_cb_invite($api_obj,$r,$accessno,$caller,$callerip,$callee)
{
	$def_prefix = isset($api_obj->config->default_prefix)?$api_obj->config->default_prefix:'0086';
	$pno = check_phone_number($caller,$def_prefix);
	$params = array(
		"pin" => $api_obj->config->cli_account,
		'pass' => '',
		'pno' => $pno,
		'caller' => $caller,
		'callerip' => $callerip,
		'callee' => $callee,
		'o' => '00',
		'lang' => 'zh-CN',
		'prefix' => '0086',
		'show' => '0'
		);
	$api_obj->set_callback_func ( get_message_callback, write_response_callback, $api_obj );
	$ra = api_cb_invite($api_obj,$params,'false');
	$r['r_callee'] = $ra['callee'];
	if($api_obj->return_code > 0)
	{
		$r['r_code'] = $api_obj->return_code;
		$r['r_file'] = $api_obj->config->ivr_path.'/callback_success';
	}else{
		$r['r_code'] = $api_obj->return_code;
		//$r['r_error'] = $api_obj->get_error_message($api_obj->return_code);
		$r['r_file'] = sprintf('callback_error%d',$api_obj->return_code);
		switch($r['r_code'])
		{
			case -151:
			case -152:
			case -153:
				$r['r_file'] = $api_obj->config->ivr_path.'/callback_fail_no_route';
				break;
			case -989:	//主叫号码没有绑定帐号
				$r['r_file'] = '';//$api_obj->config->ivr_path.'/';
				break;
			case -968:	//代理商帐户的余额不足
				$r['r_file'] = '';
				break;
			case -150:
			default:
				$api_obj->no_log = FALSE;
				$r['r_file'] = $api_obj->config->ivr_path.'/callback_fail_no_enough';
				break;
		}
	}
	return $r;	
}

function ga_callback($api_obj,$r,$accessno,$caller,$callerip,$callee)
{
	$def_prefix = isset($api_obj->config->default_prefix)?$api_obj->config->default_prefix:'0086';
	$pno = check_phone_number($caller,$def_prefix);
	if(isset($_REQUEST['pin']) && isset($_REQUEST['pass'])){
		//如果请求提供了pin和pass则使用请求中的pin和pass,Pass为使用key+pin作为密钥的MD5加密串
		$pin = $_REQUEST['pin'];
		if (isset($_REQUEST['key']))
			$pass = api_decrypt($_REQUEST['pass'],$_REQUEST['key'].$_REQUEST['pin']);
		else 	
			$pass = $_REQUEST['pass'];
	}else{
		$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
		$rani = $billing_db->billing_get_ani_account(array(
			"caller" => $pno 		//使用全国码的电话号码
			));
		if(is_array($rani) && isset($rani['EndpointNo'])){
			$pin = $rani['EndpointNo'];
			$pass = $rani['Password'];
		}else{
			$pin = $api_obj->config->cli_account;
			$pass = '';
		}
	}
	$params = array(
		"pin" => $pin,
		'pass' => $pass,
		'pno' => $pno,
		'caller' => $caller,
		'callerip' => $callerip,
		'callee' => $callee,
		'o' => '00',
		'lang' => 'zh-CN',
		'prefix' => '0086',
		'show' => '0'
		);
	$api_obj->set_callback_func ( get_message_callback, write_response_callback, $api_obj );
	api_callback($api_obj,$params,'false');
	if($api_obj->return_code > 0)
	{
		$r['r_code'] = $api_obj->return_code;
		$r['r_file'] = $api_obj->config->ivr_path.'/callback_success';
	}else{
		$r['r_code'] = $api_obj->return_code;
		//$r['r_error'] = $api_obj->get_error_message($api_obj->return_code);
		$r['r_file'] = sprintf('callback_error%d',$api_obj->return_code);
		switch($r['r_code'])
		{
			case -151:
			case -152:
			case -153:
				$r['r_file'] = $api_obj->config->ivr_path.'/callback_fail_no_route';
				break;
			case -150:
			default:
				$r['r_file'] = $api_obj->config->ivr_path.'/callback_fail_no_enough';
				break;
		}
	}
	$api_obj->no_log = FALSE;
	return $r;	
}

function ga_invite($api_obj,$r,$accessno,$caller,$callerip,$callee)
{	
	$api_obj->no_log = FALSE;
	$def_prefix = isset($api_obj->config->default_prefix)?$api_obj->config->default_prefix:'0086';
	$pno = check_phone_number($caller,$def_prefix);
	$r['r_caller'] = $pno;
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
		$api_obj->push_return_data("l.prefix",$callee_prefix['prefix']);
		$api_obj->push_return_data("l.callee",$callee_prefix['callee']);
	}
	
	//echo sprintf("callee=%s<br>inner-prefix=%s<br>",$callee,$api_obj->config->inner_prefix);
	//检查被叫的拨号前缀
	$api_obj->push_return_data("s.prefix",$callee_prefix['prefix']);
	$api_obj->push_return_data("s.callee",$callee_prefix['callee']);
	
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	$route_db = new class_billing_pgdb($api_obj->config->route_db_config, $api_obj );
	//取得默认的前缀，如果主叫号码前不带0，说明主叫号码省略了前缀，这里需要把前缀加上
	$def_prefix = isset($api_obj->config->default_ccode)?$api_obj->config->default_ccode:'0086';
	//把主被叫调整为全国码的电话号码
	$caller_s = $pno;//check_phone_number ( $caller, $def_prefix);
	$callee_s = check_phone_number ( $callee_prefix['callee'], $def_prefix);
	
	//首先要做号码替换，调用相关数据的ez_utils_db.sp_build_prefix把号码变换为带地区和运营商标识的号码：国码+运营商+省份代码+区号+'-'+号码
	//经过变换后的主被叫号码为：
	//     特殊前缀+':'+国码+':'+运营商代码+省市代码+':'+区号+'：'+电话号码 ，如：A201:86:UGD:756:15602530665
	$caller_b = $caller;//$route_db->phone_build_prefix ($caller_s);
	$callee_b = $route_db->phone_build_prefix ($callee_s);
	$calltype = isset($_REQUEST['calltype'])?$_REQUEST['calltype']:'G';
	$cp = $callee_prefix ['prefix'] == '' ? '' : $callee_prefix ['prefix'].':';
	$caller_b = $caller;//'G' . ($caller_b == $caller_s ? $cp.$caller_b : $callee_prefix ['prefix'].':'.$caller_b);
	$callee_b = $calltype . ($callee_b == $callee_s ? $cp.$callee_b : $callee_prefix ['prefix'].':'.$callee_b);
	
	$api_obj->push_return_data("caller",$caller_b);
	$api_obj->push_return_data("callee",$callee_b);
	$r['r_callee'] = $callee_b;
	$rani = $billing_db->billing_get_ani_account(array(
		"caller" => $pno 		//使用全国码的电话号码
		));
	if(is_array($rani) && isset($rani['EndpointNo'])){
		$pin = $rani['EndpointNo'];
	}else{
		$pin = $api_obj->config->cli_account;
	}
	$r['r_account'] = $pin;
	$params = array(
		"pin" => $pin,
		"pass" => '',
		"caller" => $pno, 		//使用全国码的电话号码
		"callee" => $callee_b,		//使用含地区信息的电话号码
		"callerip" => $callerip
		);
	//进行回拨认证
	$ra = $billing_db->billing_invite_auth($params);
	if (is_array ( $ra )) {
		//如果数数组说明验证通过，否则已经返回错误结果，这里不用再处理
		$api_obj->push_return_data("invite_auth",array_to_string(";",$ra));
		if($ra['RETURN-CODE'] <= 0){
			$r['r_code'] = $ra['RETURN-CODE']+100;
			switch ($r['r_code'])
			{
				case -1:
					$r['r_code'] = -11;
					$r['r_file'] = $api_obj->config->ivr_path.'/balance_no_enough';
					break;
				case -2:
				case -3:
				case -4:
					$r['r_file'] = $api_obj->config->ivr_path.'/system_error';
					break;
				case -5:
					$r['r_file'] = $api_obj->config->ivr_path.'/balance_no_enough';
					break;
				case -6:
				case -10:
				case -11:
				case -12:
				case -13:
				case -14:
				case -31:
				case -32:	//代理商费用不足
				default:
					$r['r_file'] = $api_obj->config->ivr_path.'/resaler_noe';
					break;
			}
		}else{
			//$r ['h323_redirect_number'] = $ra['RedirectNo'];
			$callee = $r ['h323_redirect_number'];
			$r ['h323_billing_mode'] = $ra['BillingMode'];
			if($ra['BillingMode'] !=0)
				$r ['h323_credit_time'] = $ra['MaxTime'];
			else 
				$r ['h323_credit_time'] = 0x7FFF;
			$r['r_balance'] = $ra['RemainMoney'];
			$r['r_currency'] = $ra['CurrencyType'];
			$gw_prefix = isset($api_obj->config->gateway_prefix)?$api_obj->config->gateway_prefix:'';
			$route = $route_db->get_callback_route ( array (
					'pno' => $caller, 
					'caller' => $caller_b, 
					'callee' => $callee_b, 
					'show' => '1'
					,'gateway_prefix' => $gw_prefix
				) );
			//$r['route'] = array_to_string(";",$route);
			//$route['t_callback_server'] = '202.134.80.108,9088,utonecb,utonecallback';
			$r['routers'] = $route['t_called_params'];
			//下面修改路由参数的分隔符，这是为了兼容存储过程中的分隔符和回拨系统能够识别的分隔符
			$rs = isset($api_obj->config->route_split)?$api_obj->config->route_split:",";
			//将路由中的逗号替换为配置中的路由分隔符
			$r['routers'] = str_replace(",",$rs,$r['routers']);
			//将路由中的分号替换为配置中的路由分隔符
			$r['routers'] = str_replace(";",$rs,$r['routers']);
			//下面修改路由数组的分隔符，这是为了兼容存储过程的分隔符和回拨系统可以识别的分隔符
			$ra_split = isset($api_obj->config->ra_split)?$api_obj->config->ra_split:"|";
			$r['routers'] = str_replace("|",$ra_split,$r['routers']);
			
			if(empty($r['routers'])){
				$r['r_code'] = -50;
				$r['r_callee'] = $callee_b;
				$r['r_file'] = $api_obj->config->ivr_path.'/get_route_error';				
			}else{
				$api_obj->push_return_data("routers",$r['routers']);
				
				$r['r_code'] = 1;
				$r['r_file'] = $api_obj->config->ivr_path.'/pls_wait';
				$r['r_callee'] = $callee_b;
			}
		}
	}
	return $r;
}

function ga_route($api_obj,$r,$accessno,$caller,$callerip,$callee)
{	
	$def_prefix = isset($api_obj->config->default_prefix)?$api_obj->config->default_prefix:'0086';
	$pno = check_phone_number($caller,$def_prefix);
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
		$api_obj->push_return_data("l.prefix",$callee_prefix['prefix']);
		$api_obj->push_return_data("l.callee",$callee_prefix['callee']);
	}
	
	//echo sprintf("callee=%s<br>inner-prefix=%s<br>",$callee,$api_obj->config->inner_prefix);
	//检查被叫的拨号前缀
	$api_obj->push_return_data("s.prefix",$callee_prefix['prefix']);
	$api_obj->push_return_data("s.callee",$callee_prefix['callee']);
	
	//$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	$route_db = new class_billing_pgdb($api_obj->config->route_db_config, $api_obj );
	//取得默认的前缀，如果主叫号码前不带0，说明主叫号码省略了前缀，这里需要把前缀加上
	$def_prefix = isset($api_obj->config->default_ccode)?$api_obj->config->default_ccode:'0086';
	//把主被叫调整为全国码的电话号码
	$caller_s = $caller;//check_phone_number ( $caller, $def_prefix);
	$callee_s = check_phone_number ( $callee_prefix['callee'], $def_prefix);
	
	//首先要做号码替换，调用相关数据的ez_utils_db.sp_build_prefix把号码变换为带地区和运营商标识的号码：国码+运营商+省份代码+区号+'-'+号码
	//经过变换后的主被叫号码为：
	//     特殊前缀+':'+国码+':'+运营商代码+省市代码+':'+区号+'：'+电话号码 ，如：A201:86:UGD:756:15602530665
	$caller_b = $caller;//$route_db->phone_build_prefix ($caller_s);
	$callee_b = $route_db->phone_build_prefix ($callee_s);
	
	$cp = $callee_prefix ['prefix'] == '' ? '' : $callee_prefix ['prefix'].':';
	$caller_b = $caller;//'G' . ($caller_b == $caller_s ? $cp.$caller_b : $callee_prefix ['prefix'].':'.$caller_b);
	$callee_b = 'G' . ($callee_b == $callee_s ? $cp.$callee_b : $callee_prefix ['prefix'].':'.$callee_b);
	
	$api_obj->push_return_data("caller",$caller_b);
	$api_obj->push_return_data("callee",$callee_b);
	$params = array(
		"pin" => $api_obj->config->cli_account,
		"pass" => '',
		"caller" => $caller_s, 		//使用全国码的电话号码
		"callee" => $callee_b,		//使用含地区信息的电话号码
		"callerip" => $callerip
		);
	$gw_prefix = isset($api_obj->config->gateway_prefix)?$api_obj->config->gateway_prefix:'';
	$route = $route_db->get_callback_route ( array (
			'pno' => $pno, 
			'caller' => $caller_b, 
			'callee' => $callee_b, 
			'show' => '1'
			,'gateway_prefix' => $gw_prefix
		) );
	$r['routers'] = $route['t_called_params'];
	//下面修改路由参数的分隔符，这是为了兼容存储过程中的分隔符和回拨系统能够识别的分隔符
	$rs = isset($api_obj->config->route_split)?$api_obj->config->route_split:",";
	//将路由中的逗号替换为配置中的路由分隔符
	$r['routers'] = str_replace(",",$rs,$r['routers']);
	//将路由中的分号替换为配置中的路由分隔符
	$r['routers'] = str_replace(";",$rs,$r['routers']);
	//下面修改路由数组的分隔符，这是为了兼容存储过程的分隔符和回拨系统可以识别的分隔符
	$ra_split = isset($api_obj->config->ra_split)?$api_obj->config->ra_split:"|";
	$r['routers'] = str_replace("|",$ra_split,$r['routers']);
	
	if(empty($r['routers'])){
		$r['r_code'] = -50;
		$r['r_file'] = $api_obj->config->ivr_path.'/get_route_error';				
	}else{
		$api_obj->push_return_data("routers",$r['routers']);
		
		$r['r_code'] = 1;
		$r['r_file'] = $api_obj->config->ivr_path.'/pls_wait';
		$r['r_callee'] = $callee_b;
	}
	return $r;
}

function ga_query($api_obj,$r,$accessno,$caller,$callerip)
{
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	$def_prefix = isset($api_obj->config->default_prefix)?$api_obj->config->default_prefix:'0086';
	$caller = check_phone_number($caller,$def_prefix);
	$ra = $billing_db->billing_query_balance(array(
		'pin' => $caller,
		'caller' => $caller,
		'pass' => ''
		));
	if(is_array($ra)){
		$r['r_code'] = $ra['RETURN-CODE'];
		$api_obj->push_return_data('ra',array_to_string(";",$ra));
		if($r['r_code'] <= 0){
			//查询失败
			switch ($r['r_code'])
			{
				case -1:
					$api_obj->return_code = -101;
					$r['r_file'] = sprintf('%1$s/caller_no_bind_account',$api_obj->config->ivr_path);
					break;
				default:
					$api_obj->return_code = -102;
					$r['r_file'] = sprintf('%1$s/pin_or_pass_error',$api_obj->config->ivr_path);;
					break;
			}
			
		}else{
			//查询成功
			$api_obj->return_code = 101;
			$r['r_code'] = 1;
			$r['r_balance'] = $ra['RemainMoney'];
			$r['r_currency'] = $ra['CurrencyType'];
			$r['r_file'] = '';
			$pno = $caller;
//			if(substr($pno,0,3) == '861'){
//				$pno = substr($pno,2);
//				$r['r_caller'] = $pno;
//				$fmt = isset($api_obj->config->sms_msg['query'])?$api_obj->config->sms_msg['query']:'您的帐户余额是%s元。';
//				$msg = sprintf($fmt,$r['r_balance']);
//				$result = send_sms_queue($api_obj,$pno,'*',$msg);
//				$r['sms'] = $result;
//			}
		}
	}else{
		//返回不是数组
		$api_obj->return_code = -100;
		$r['r_code'] = -100;
		$r['r_file'] = sprintf('%1$s/system_error_and_try_again',$api_obj->config->ivr_path);//'ezprepaid/system-error';
	}
	return $r;
}

function ga_bind($api_obj,$r,$accessno,$caller,$callerip,$pin,$pass)
{
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	$def_prefix = isset($api_obj->config->default_prefix)?$api_obj->config->default_prefix:'0086';
	$caller = check_phone_number($caller,$def_prefix);
	$api_obj->push_return_data('def_prefix',$def_prefix);
	$api_obj->push_return_data('caller',$caller);
	
	if(!ivr_check_caller($caller)){
		$r['r_code'] = -1;
		$r['r_file'] = $api_obj->config->ivr_path.'/caller_rule_error';
		return $r;
	}
	$ra = $billing_db->billing_bind_cli(array(
		'pin' => $pin,
		'caller' => $caller,
		'pass' => $pass
		));
	if(is_array($ra)){
		$r['r_code'] = $ra['RETURN-CODE'];
		$api_obj->push_return_data('ra',array_to_string(";",$ra));
		if($r['r_code'] <= 0){
			//绑定失败
			switch ($r['r_code'])
			{
			case -305:
			case -205:
				$r['r_file'] = sprintf('%1$s/pin_already_recharge',$api_obj->config->ivr_path);
				break;
			default:
				$r['r_file'] = sprintf('%1$s/pin_or_pass_error',$api_obj->config->ivr_path);
				break;
			}
		}else{
			//绑定成功
			$r['r_code'] = 1;
			$r['r_balance'] = $ra['RemainMoney'];
			$r['r_currency'] = $ra['CurrencyType'];
			$r['r_file'] = sprintf('%1$s/bind_success',$api_obj->config->ivr_path);//'ezperpaid/bind-success';
			$pno = $caller;
			if(substr($pno,0,3) == '861'){
				$pno = substr($pno,2);
				$fmt = isset($api_obj->config->sms_msg['bind'])?$api_obj->config->sms_msg['bind']:'绑定成功，成功为您充值%s元。';
				$msg = sprintf($fmt,$r['r_balance']);
				$r['sms'] = send_sms_queue($api_obj,$pno,'*',$msg);
			}
		}
	}else{
		//返回不是数组
		$r['r_code'] = -100;
		$r['r_file'] = sprintf('%1$s/system_error_and_try_again',$api_obj->config->ivr_path);
	}
	$api_obj->no_log = FALSE;
	return $r;
}

function ga_recharge($api_obj,$r,$accessno,$caller,$callerip,$pin,$pass)
{
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	$def_prefix = isset($api_obj->config->default_prefix)?$api_obj->config->default_prefix:'0086';
	$caller = check_phone_number($caller,$def_prefix);
	$api_obj->push_return_data('def_prefix',$def_prefix);
	$api_obj->push_return_data('caller',$caller);
	
	$rp = explode('*',$pin);
	if(Count($rp)>1 && $rp[0] <> '0'){
		$r['r_code'] = api_ophone_3pay($api_obj,$billing_db,$accessno,$caller,$pin,$pass);
		if($r['r_code'] > 0)
			$r['r_file'] = $api_obj->config->ivr_path.'/success_recharge_3pay';
		else{
			switch ($r['r_code'])
			{
			case -612:
				$r['r_file'] = $api_obj->config->ivr_path.'/pin_or_pass_error';
				break;
			default:
				$r['r_file'] = $api_obj->config->ivr_path.'/pin_or_pass_error';
				break;
			}
		}
	}else{
		if(Count($rp)>1)$pin = $rp[1];
		
		if(!ivr_check_caller($caller)){
			$r['r_code'] = -1;
			$r['r_file'] = $api_obj->config->ivr_path.'/caller_rule_error';
			return $r;
		}
		
//		$ra = $billing_db->billing_recharge(array(
//			'pin' => $caller,
//			'caller' => $caller,
//			'cardno' => $pin,
//			'pass' => $pass
//			));
		$ra = $billing_db->billing_bind_cli(array(
			'pin' => intval($pin),
			'caller' => $caller,
			'pass' => $pass
			));
		if(is_array($ra)){
			$r['r_code'] = $ra['RETURN-CODE'];
			//echo sprintf("ra=%s",array_to_string(',',array($pin,$caller,$pass,$api_obj->config->default_active['agent'])));
			$api_obj->push_return_data('ra',array_to_string(";",$ra));
			if($r['r_code'] <= 0){
				//充值失败
				switch ($r['r_code'])
				{
				case -305:
				case -205:
					$r['r_file'] = sprintf('%1$s/pin_already_recharge',$api_obj->config->ivr_path);
					break;
				default:
					$r['r_file'] = sprintf('%1$s/pin_or_pass_error',$api_obj->config->ivr_path);
					break;
				}
			}else{
				//充值成功
				$r['r_code'] = 1;
				$api_obj->return_code = 101;
				$r['r_balance'] = $ra['RemainMoney'];
				$r['r_currency'] = $ra['CurrencyType'];
				$r['r_file'] = '';
				$pno = $caller;
				if(substr($pno,0,3) == '861'){
					$pno = substr($pno,2);
					$r['r_caller'] = $pno;
					$fmt = isset($api_obj->config->sms_msg['recharge'])?$api_obj->config->sms_msg['recharge']:'充值成功，本次充值%s元。';
					$msg = sprintf($fmt,$r['r_balance']);
					$result = send_sms_queue($api_obj,$pno,'*',$msg);
					$r['sms'] = $result;
				}
			}
		}else{
			//返回不是数组
			$r['r_code'] = -100;
			$r['r_file'] = $api_obj->config->ivr_path.'/system_error';
		}
	}
	$api_obj->no_log = FALSE;
	return $r;
}

function ga_modify_pass($api_obj,$r,$accessno,$caller,$callerip,$pass,$newpass,$cfmpass)
{
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	
	if($newpass != $cfmpass)
	{
		$r['r_code'] = -1;
		$r['r_file'] = $api_obj->config->ivr_path.'/confirm_neq_new_pass';
		$api_obj->no_log = FALSE;
		return $r;
	}
	
	$ra = $billing_db->billing_modify_password(array(
		'pin' => $caller,
		'caller' => $caller,
		'pass' => $pass,
		'newpass' => $newpass
		));
	if(is_array($ra)){
		$r['r_code'] = $ra['RETURN-CODE'];
		$api_obj->push_return_data('ra',array_to_string(";",$ra));
		if($r['r_code'] <= 0){
			//查询失败
			$r['r_file'] = $api_obj->config->ivr_path.'/modify_fail';
		}else{
			//查询成功
			$api_obj->return_code = 101;
			$r['r_code'] = 1;
			$r['r_file'] = $api_obj->config->ivr_path.'/modify_pass_success';
		}
	}else{
		//返回不是数组
		$r['r_code'] = -100;
		$r['r_file'] = $api_obj->config->ivr_path.'/system_error';
	}
	$api_obj->no_log = FALSE;
	return $r;
}

/*
	 * @Description 易宝支付非银行卡支付专业版接口范例 
	 * @V3.0
	 * @Author yang.xu
*/
function api_ophone_3pay($api_obj,$billingdb,$accessno,$caller,$rpin,$rpass)
{
	require_once (__EZLIB__.'/common/api_3pay_yeepay.php');
    /*充值卡类型判别
    1.默认为优会通充值方式；
    2.根据充值传输过来的前缀来判断当前的充值方式
    */ 
    //UNICOM 联通卡 ,TELECOM 电信卡 ,SZX 神州行 
    $c_string  = explode ("*",strtoupper($rpin));
    $r_type = $c_string['0'];
    $pin_num   = $c_string['1'];
    
	$rani = $billingdb->billing_get_ani_account(array(
		"caller" => $caller 		//使用全国码的电话号码
		));
	if(is_array($rani) && isset($rani['EndpointNo'])){
		$pno = $rani['EndpointNo'];
	}else{
		$ra = $billingdb->billing_create_account(array(
			'pin' => $caller,
			'caller' => $caller,
			'pass' => substr($caller,-6,6),
			));
		if(is_array($ra)){
			$r['r_code'] = $ra['RETURN-CODE'];
			$api_obj->push_return_data("Active",array_to_string(",",$ra));
			if($r['r_code'] <= 0){
				$r['r_file'] = $api_obj->config->ivr_path.'/active_fail';
				return $r;
			}else{
				$r['r_code'] = 1;
				$r['r_e164'] = $ra['E164'];
				$r['r_file'] = $api_obj->config->ivr_path.'/active_success';
			}
			$api_obj->return_code = $r['r_code'];
		}else{
			//返回不是数组
			$r['r_code'] = -100;
			$r['r_file'] = sprintf('%1$s/system_error_and_try_again',$api_obj->config->ivr_path);
			return $r;
		}
		$pno = $ra['E164'];
	}
    //var_dump($c_string);exit;
    if($r_type == "SZX" || $r_type == "SZ"  || $r_type == "S" || $r_type == "1"){
		//用户需要在账号前输入大写的账号所属类型，如果无账号所属类型
		//默认识别为中国移动充值卡
		$c_type  = "SZX";// 神州行	
	}else if($r_type == "UNICOM" || $r_type == "UNICO"  ||$r_type == "UNIC" ||$r_type == "UNI" ||$r_type == "UN" ||
	         $r_type == "U" ||$r_type == "2"){	
		$c_type  = "UNICOM";// 中国联通
	}else if($r_type == "TELECOM" ||
			 $r_type == "TELECO"  ||
			 $r_type == "TELEC"   ||
			 $r_type == "TELE"    ||
	         $r_type == "TEL"     ||
	         $r_type == "TE"      ||
	         $r_type == "T"      ||
	         $r_type == "3"
	){
	 	$c_type  = iconv("UTF-8", "GB2312",	"TELECOM");// 中国电信
	}else{
		$c_type  = "SZX";// 神州行
	}    
	#商家设置用户购买商品的支付信息.
	#商户订单号.提交的订单号必须在自身账户交易中唯一.
	$time        =   date('YmdHis',time() + 3600 * 8);
    $p2_Order    =   sprintf("%s-%s-%s",$caller,$pno,$time);
	$p2_Order           = mb_convert_encoding($p2_Order,"GBK", "UTF-8");
	#支付卡面额
	$p3_Amt				= mb_convert_encoding("30","GBK", "UTF-8");
	#是否较验订单金额
	$p4_verifyAmt		= mb_convert_encoding("false","GBK", "UTF-8");//值：true校验金额;  false不校验金额
	#产品名称
	$p5_Pid				= mb_convert_encoding("ChinaUnicom/TEL/MOBILE","GBK", "UTF-8");
	#产品类型
	$p6_Pcat			= mb_convert_encoding("ChinaUnicom/TEL/MOBILE","GBK", "UTF-8");
	#产品描述
	$p7_Pdesc			= mb_convert_encoding("ChinaUnicom/TEL/MOBILE","GBK", "UTF-8");
	#商户接收交易结果通知的地址,易宝支付主动发送支付结果(服务器点对点通讯).通知会通过HTTP协议以GET方式到该地址上.	
	$p8_Url				= $api_obj->config->new_yeepay_p8_Url;;
	#临时信息
	$pa_MP				= mb_convert_encoding("utone","GBK", "UTF-8");
	#卡面额
	$pa7_cardAmt		= mb_convert_encoding("30","GBK", "UTF-8");
	#支付卡序列号.
	$pa8_cardNo			= mb_convert_encoding($pin_num,"GBK", "UTF-8");
	#支付卡密码.
	$pa9_cardPwd        = mb_convert_encoding($rpass, "GBK", "UTF-8"); 
	#支付通道编码
	$pd_FrpId			= mb_convert_encoding($c_type,"GBK", "UTF-8");//UNICOM 联通卡 ,TELECOM 电信卡 ,SZX 神州行 
	#应答机制
	$pr_NeedResponse	= mb_convert_encoding("1","GBK", "UTF-8");//需要（1）,不需要（0）
	#用户唯一标识
	$pz_userId			= sprintf("%s-%s",$caller,$accessno);
	$pz_userId          = mb_convert_encoding($pz_userId,"GBK", "UTF-8");
	#用户的注册时间
    $pz1_userRegTime	= gmdate('Y-m-d-H-i-s', time() + 3600 * 8);
    $pz1_userRegTime    = mb_convert_encoding($pz1_userRegTime,"GBK", "UTF-8");	
	#非银行卡支付专业版测试时调用的方法，在测试环境下调试通过后，请调用正式方法annulCard
	#两个方法所需参数一样，所以只需要将方法名改为annulCard即可
	#测试通过，正式上线时请调用该方法
    return annulCard($p2_Order,$p3_Amt,$p4_verifyAmt,$p5_Pid,$p6_Pcat,$p7_Pdesc,$p8_Url,$pa_MP,$pa7_cardAmt,$pa8_cardNo,$pa9_cardPwd,$pd_FrpId,$pz_userId,$pz1_userRegTime,$api_obj,$billingdb);	
}

/**
 * 检查命令是否允许请求QQ使用，使用简写命令格式
 *
 * @param unknown_type $api_obj
 * @param unknown_type $sender
 * @param unknown_type $cmd
 * @return unknown
 */
function check_qq_cmd($api_obj,$sender,$cmd)
{
	$allows = $api_obj->config->qq_allows[$sender];
	if(!isset($allows))$allows = 'r,q';
	if(trim($allows) == 'all')
		return true;
	$allows = explode(',',$allows);
	return in_array($cmd,$allows);
}

function get_msg_recharge_callback($api_obj, $context, $msg) {
	return sprintf ( $msg, $api_obj->return_code
		,$api_obj->return_data['caller']
		,$api_obj->return_data['balance']
		,$api_obj->return_data['currency']
		);
}

function cmd_recharge($api_obj,$sender,$params)
{
	if(!check_qq_cmd($api_obj,$sender,'r'))
		return sprintf("不允许QQ号码%s使用充值命令",$sender);
	$api_obj->set_action('recharge');
	$api_obj->load_error_xml('sms_sender.xml');
	list($caller,$pin,$pass,$pintype) = explode(',',$params);
	if(substr($caller,0,2) == '86')
		$caller = "00".$caller;
	if(isset($pintype))$pin = $pintype."*".$pin;
	$r = array();
	$r = ga_recharge($api_obj,$r,'',$caller,'',$pin,$pass);
	$api_obj->push_return_data('caller',$caller);
	$api_obj->push_return_data('balance',$r['r_balance']);
	$api_obj->push_return_data('currency',$r['r_currency']);
	$api_obj->set_callback_func ( get_msg_recharge_callback, NULL, $api_obj );
//	if(isset($r['sms']))
//		echo sprintf("短信回执：%s\r\n",$r['sms']);
//	if(isset($api_obj->return_data['sms_return']))
//		echo sprintf("短信返回:%s\r\n",$api_obj->return_data['sms_return']);
//	if(isset($api_obj->return_data['sms_route']))
//		echo sprintf("短信路由:%s\r\n",$api_obj->return_data['sms_route']);
//	if(isset($api_obj->return_data['other_sms_status']))
//		echo sprintf("其他未读短信短信状态：%s\r\n",$api_obj->return_data['other_sms_status']);
	return $api_obj->get_error_message($api_obj->return_code,"返回代码 %s");
}

function cmd_query($api_obj,$sender,$params)
{
	if(!check_qq_cmd($api_obj,$sender,'q'))
		return sprintf("不允许QQ号码%s使用查询号码余额命令",$sender);
	$api_obj->set_action('query');
	$api_obj->load_error_xml('sms_sender.xml');
	list($caller) = explode(',',$params);
	if(substr($caller,0,2) == '86')
		$caller = "00".$caller;
	$r = array();
	$r = ga_query($api_obj,$r,'',$caller,'');
	$api_obj->push_return_data('caller',$caller);
	$api_obj->push_return_data('balance',$r['r_balance']);
	$api_obj->push_return_data('currency',$r['r_currency']);
	$api_obj->set_callback_func ( get_msg_recharge_callback, NULL, $api_obj );
	//if(isset($r['sms']))
	//	echo sprintf("短信回执：%d\r\n",$r['sms']);
	//if(isset($api_obj->return_data['sms_return']))
	//	echo sprintf("短信返回:%s\r\n",$api_obj->return_data['sms_return']);
	//if(isset($api_obj->return_data['sms_route']))
	//	echo sprintf("短信路由:%s\r\n",$api_obj->return_data['sms_route']);
	//if(isset($api_obj->return_data['other_sms_status']))
	//	echo sprintf("其他未读短信短信状态：%s\r\n",$api_obj->return_data['other_sms_status']);
	return $api_obj->get_error_message($api_obj->return_code,"返回代码 %d");
}


function get_msg_qi_callback($api_obj, $context, $msg) {
	return sprintf ( $msg, $api_obj->return_code
		,$api_obj->return_data['caller']
		,$api_obj->return_data['e164']
		,$api_obj->return_data['balance']
		,$api_obj->return_data['status']
		,$api_obj->return_data['password']
		);
}
function cmd_query_info($api_obj,$sender,$params)
{
	if(!check_qq_cmd($api_obj,$sender,'qi'))
		return sprintf("不允许QQ号码%s使用查询帐号信息命令",$sender);
	$api_obj->set_action('query_info');
	list($caller) = explode(',',$params);
	if(substr($caller,0,2) == '86')
		$caller = "00".$caller;
	$def_prefix = isset($api_obj->config->default_prefix)?$api_obj->config->default_prefix:'0086';
	$pno = check_phone_number($caller,$def_prefix);
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	
	$rani = $billing_db->billing_get_ani_account(array(
		"caller" => $pno 		//使用全国码的电话号码
		));
	$api_obj->push_return_data('caller',$caller);
	if(is_array($rani) && isset($rani['EndpointNo'])){
		$pin = $rani['EndpointNo'];
		$pass = $rani['Password'];
		$api_obj->push_return_data('e164',$pin);
		$api_obj->push_return_data('password',$pass);
		$ra = $billing_db->get_endpoint_info ( $pin, $pass );
		//echo sprintf("ra=%s\r\n",array_to_string(',',$ra));	
		if(is_array($ra)){
			//查询成功
			$api_obj->push_return_data('balance',$ra['Balance']);
			$api_obj->push_return_data('status',$ra['Status']);
			$resaler = $ra['AgentID'];
			$api_obj->push_return_data('ra',$api_obj->json_encode($ra));
			if(($resaler == $api_obj->config->resaler)  
				 or ($api_obj->config->resaler == 0)){
				$api_obj->return_code = 101;
			}else{
				$api_obj->return_code = -103;
			}
		}else{
			$api_obj->return_code = -102;	
		}
	}else{
		$api_obj->return_code = -101;
	}
	$api_obj->push_return_data('caller',$caller);
	$api_obj->set_callback_func ( get_msg_qi_callback, NULL, $api_obj );
	return $api_obj->get_error_message($api_obj->return_code,"返回代码 %d");
}


function get_msg_qc_callback($api_obj, $context, $msg) {
	return sprintf ( $msg, $api_obj->return_code
		,$api_obj->return_data['pin']
		,$api_obj->return_data['balance']
		,$api_obj->return_data['password']
		,$api_obj->return_data['status']
		);
}
function cmd_query_card($api_obj,$sender,$params)
{
	if(!check_qq_cmd($api_obj,$sender,'qc'))
		return sprintf("不允许QQ号码%s使用查询充值卡命令",$sender);
	$api_obj->set_action('query_card');
	list($card) = explode(',',$params);
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	$r = $billing_db->billing_get_card_info($card);
	if(is_array($r)){
		$resaler = $r['AgentID'];
		if(($resaler == $api_obj->config->resaker) or ($resaler == 0) 
			or ($api_obj->config->resalser == 0)){
			$api_obj->push_return_data('pin',$card);
			$api_obj->push_return_data('password',$r['Password']);
			$api_obj->push_return_data('status',$r['Status']);
			$api_obj->push_return_data('balance',$r['BalanceM']);
			$api_obj->return_code = 101;
		}else{
			$api_obj->return_code = -102;	//代理商不匹配
		}
	}else{
		$api_obj->return_code = -101;		//充值卡不存在
	}
	$api_obj->set_callback_func ( get_msg_qc_callback, NULL, $api_obj );
	return $api_obj->get_error_message($api_obj->return_code,"返回代码 %s");
}

function get_use_pin($api_obj,$card,$type)
{
	$url = $api_obj->config->pin_3ths[strtolower($type)];
	$data = array(
		'card' => $card
	);
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, array_to_string('&',$data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,TRUE);
	curl_setopt($ch, CURLOPT_TIMEOUT, 35);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 35);
	$resp = curl_exec($ch);
	//echo sprintf("resp=%s\r\n%s\r\n",$resp,$api_obj->json_encode($api_obj->json_decode($resp)));
	curl_close($ch);
	return $api_obj->json_decode($resp);
}

//<充值卡>,<卡来源>,<面额>,<计费类别>
function cmd_card_add($api_obj,$sender,$params)
{
	if(!check_qq_cmd($api_obj,$sender,'qc'))
		return sprintf("不允许QQ号码%s使用添加外部充值卡命令",$sender);
	$api_obj->set_action('card_add');
	list($card,$type,$value,$cs) = explode(',',$params);
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	if(empty($card) or empty($type))
		return cmd_help($api_obj,$sender,'ca');
	$r = get_use_pin($api_obj,$card,$type);
	//var_dump($r);
	if(is_object($r)){
		if($r->return_code>0){
			/*
			'@pin' => $params['pin']
			,'@agent' => $params['agent']
			,'@cs_id' => $params['cs_id']
			,'@CurrencyType' => $params['currency']
			,'@Balance' => $params['balance']
			,'@Password' => $params['pass']
			* 
			*/
			if(!isset($cs)) $cs = 'A';
			if(!isset($value)) $value = '200';
			$api_obj->push_return_data('card',$card);
			$api_obj->push_return_data('balance',$r->balance);
			$api_obj->push_return_data('password',$r->password);
			$rb = $billing_db->billing_add_pin(array(
				'pin' => intval($card),
				'pass' => $r->password,
				'cs_id' => intval($api_obj->config->pin_cs[strtolower($cs)]),
				'currency' => 'CYN',
				'balance' => $value,
				'agent' => intval($api_obj->config->default_active['agent'])
				));
			if(is_array($rb)){
				$api_obj->push_return_data('rb',$rb);
				//echo $api_obj->json_encode($api_obj->return_data);
				if($api_obj->return_code>0){ 
					$api_obj->return_code = 101;
				}else{
					$api_obj->return_code += -300;
				}
			}else{
				$api_obj->return_code = -300;
			}
		}else{
			$api_obj->return_code = $r->return_code-200;	//获得充值卡失败
		}
	}else{
		$api_obj->return_code = -101;		//获得充值卡信息错误
	}
	$api_obj->set_callback_func ( get_msg_qc_callback, NULL, $api_obj );
	return $api_obj->get_error_message($api_obj->return_code,"返回代码 %s");
}

function cmd_unbind($api_obj,$sender,$params)
{
	if(!check_qq_cmd($api_obj,$sender,'ub'))
		return sprintf("不允许QQ号码%s使用解除绑定命令",$sender);
	$api_obj->set_action('unbind');
	list($caller) = explode(',',$params);
	if(substr($caller,0,2) == '86')
		$caller = "00".$caller;
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	$billing_db->billing_unbind($caller);
	
	$api_obj->set_callback_func ( get_msg_qc_callback, NULL, $api_obj );
	return $api_obj->get_error_message($api_obj->return_code,"返回代码 %d");
}

function cmd_modify_pass($api_obj,$sender,$params)
{
	if(!check_qq_cmd($api_obj,$sender,'mp'))
		return sprintf("不允许QQ号码%s使用此命令",$sender);
	$api_obj->set_action('mpass');
	list($caller,$pin,$pass,$pintype) = explode(',',$params);
	if(substr($caller,0,2) == '86')
		$caller = "00".$caller;
	if(isset($pintype))$pin = $pintype."*".$pin;
	$r = array();
	$r = ga_modify_pass($api_obj,$r,'',$caller,'',$pass,$newpass,$cfmpass);
	return $api_obj->get_error_message($api_obj->return_code,"返回代码 %s");
}

function cmd_stop_caller($api_obj,$sender,$params)
{
	if(!check_qq_cmd($api_obj,$sender,'sc'))
		return sprintf("不允许QQ号码%s使用此命令",$sender);
	$api_obj->set_action('stop_caller');
	list($caller) = explode(',',$params);
	if(substr($caller,0,2) == '86')
		$caller = "00".$caller;
	$def_prefix = isset($api_obj->config->default_prefix)?$api_obj->config->default_prefix:'0086';
	$pno = check_phone_number($caller,$def_prefix);
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	
	$rani = $billing_db->billing_get_ani_account(array(
		"caller" => $pno 		//使用全国码的电话号码
		));
	if(is_array($rani) && isset($rani['EndpointNo'])){
		$pin = $rani['EndpointNo'];
	}else{
		$pin = $api_obj->config->cli_account;
	}
	$ra = $billing_db->billing_query_balance(array(
		'pin' => $pno,
		'caller' => $pno,
		'pass' => ''
		));
	//echo sprintf("ra=%s\r\n",array_to_string(',',$ra));	
	if(is_array($ra)){
		if($ra['RETURN-CODE'] > 0){
			//查询成功
			$api_obj->push_return_data('balance',$ra['RemainMoney']);
			$api_obj->push_return_data('currency',$ra['CurrencyType']);
			$remark = mb_convert_encoding(sprintf('为号码%s退卡%s元',$caller,$ra['RemainMoney']),"gb2312");
			$resaler = $api_obj->config->default_active['agent'];
			$params = array(
				'resaler' => $resaler,
				'pass' => '',
				'pin' => $pin, 
				'value' => '-'.$ra['RemainMoney'],
				'type' => 'SC',
				'remark' => $remark
			);
			//echo "pin=$pin\r\n";
			$rdata = $billing_db->billing_resaler_recharge($params);	
			$api_obj->return_code = $rdata['RETURN-CODE'];
			//先返回给客户端，然后添加的会记录到日志里
			$api_obj->push_return_data('params',$params);
			$api_obj->push_return_data('data',$rdata);
			if($api_obj->return_code > 0)$api_obj->return_code = 101;
		}else{
			$api_obj->return_code = -101;
		}
		$api_obj->push_return_data('caller',$caller);
		$api_obj->set_callback_func ( get_msg_recharge_callback, NULL, $api_obj );
		$m = $api_obj->get_error_message($api_obj->return_code,"返回代码 %s");
		$m .= "\r\n".cmd_unbind($api_obj,$sender,$caller);
		return $m;
	}else{
		$api_obj->return_code = -102;	
	}
	$api_obj->push_return_data('caller',$caller);
	$api_obj->set_callback_func ( get_msg_recharge_callback, NULL, $api_obj );
	return $api_obj->get_error_message($api_obj->return_code,"返回代码 %s");
}

function cmd_sms($api_obj,$sender,$params)
{
	if(!check_qq_cmd($api_obj,$sender,'sms'))
		return sprintf("不允许QQ号码%s使用发送短信命令",$sender);
	$api_obj->set_action('send_sms');
	$api_obj->load_error_xml('sms_sender.xml');
	list($cno,$msg,$rk) = explode(',',$params);
	$result = send_sms_route($api_obj,$cno,"*",$msg,$rk);
	if(isset($rk)){
		if(isset($api_obj->return_data['sms_return']))
			echo sprintf("短信返回:%s\r\n",$api_obj->return_data['sms_return']);
		if(isset($api_obj->return_data['sms_route']))
			echo sprintf("短信路由:%s\r\n",$api_obj->return_data['sms_route']);
	}
	if(isset($api_obj->return_data['other_sms_status']))
		echo sprintf("其他未读短信短信状态：%s\r\n",$api_obj->return_data['other_sms_status']);
	return sprintf("发送短信回执：%s\r\n",$result);
}

function cmd_active($api_obj,$sender,$params){
	if(!check_qq_cmd($api_obj,$sender,'a'))
		return sprintf("不允许QQ号码%s使用发送激活命令",$sender);
	$api_obj->set_action('send_active');
	list($caller,$pass) = explode(',',$params);
	//$r,$accessno,$caller,$callerip,$pass,$cfmpass
	$r = array();
	$r = ga_active($api_obj,$r,$sender,$caller,"",$pass,$pass);
	return $api_obj->get_error_message($api_obj->return_code,"返回代码 %d");
}

function cmd_help($api_obj,$sender,$params)
{
	list($cmd) = explode(',',$params);
	
	switch ($cmd)
	{
	case 'recarge':
	case 'r':
	case '充值':
		if(!check_qq_cmd($api_obj,$sender,'r'))
			return sprintf("不允许QQ号码%s使用充值命令",$sender);
		$msg = sprintf("充值命令，对于新用户首先会以充值卡密码和主叫号码绑定一个帐号，然后用充值卡给这个帐号充值。对于老用户则直接充值。充值卡类别可选，如果指定则1表示使用移动充值卡，2表示使用联通充值卡，3表示使用电信充值卡。\r\n使用方法：\r\n\t%s <主叫号码>,<充值卡号码>,<充值卡密码>[,充值卡类别]",$cmd);
		break;
	case 'query':
	case 'q':
	case '查询余额':
		if(!check_qq_cmd($api_obj,$sender,'q'))
			return sprintf("不允许QQ号码%s使用查询余额命令",$sender);
		
		$msg = sprintf("查询余额命令，查询主叫号码所绑定帐号的余额。\r\n使用方法：\r\n\t%s <主叫号码>",$cmd);
		break;
	case 'queryinfo':
	case 'qi':
	case '查询帐号':
		if(!check_qq_cmd($api_obj,$sender,'qi'))
			return sprintf("不允许QQ号码%s使用查询帐号信息命令",$sender);
		
		$msg = sprintf("查询帐号命令，查询主叫号码所绑定帐号的信息，返回帐号、余额和密码信息。\r\n使用方法：\r\n\t%s <主叫号码>",$cmd);
		break;
	case 'ca':
		if(!check_qq_cmd($api_obj,$sender,'ca'))
			return sprintf("不允许QQ号码%s使用添加外部充值卡信息命令",$sender);
		
		$msg = sprintf("添加外部充值卡。卡来源:优会通使用u\r\n\t计费类别：A使用0.25计费方案，B使用0.20计费方案。\r\n使用方法：\r\n\t%s <充值卡>,<卡来源>,<面额>,<计费类别>",$cmd);
		break;
	case 'querycard':
	case 'qc':
	case '查询卡号':
		if(!check_qq_cmd($api_obj,$sender,'qi'))
			return sprintf("不允许QQ号码%s使用查询充值卡信息命令",$sender);
		
		$msg = sprintf("查询充值卡命令，查询充值卡的信息，返回帐号、余额和密码信息。\r\n使用方法：\r\n\t%s <主叫号码>",$cmd);
		break;
	case 'modifypass':
	case 'mp':
		if(!check_qq_cmd($api_obj,$sender,'mp'))
			return sprintf("不允许QQ号码%s使用此命令",$sender);
		
		$msg = sprintf("修改密码命令，修改主叫号码所绑定帐号的密码。默认密码为首次充值的充值卡密码。\r\n使用方法：\r\n\t%s <主叫号码>",$cmd);
		break;
	case 'stop_caller':
	case 'sc':
	case '退卡':
		if(!check_qq_cmd($api_obj,$sender,'sc'))
			return sprintf("不允许QQ号码%s使用此命令",$sender);
		
		$msg = sprintf("退卡命令，退卡是将号码对应的帐号的余额扣除。\r\n使用方法：\r\n\t%s <主叫号码>",$cmd);
		break;
	case 'a':
		if(!check_qq_cmd($api_obj,$sender,'a'))
			return sprintf("不允许QQ号码%s使用此命令",$sender);
		
		$msg = sprintf("激活命令。\r\n使用方法：\r\n\t%s <主叫号码>,<密码>",$cmd);
		break;
	case 'OFFLINE':
		return '';
		break;
	case 'help':
	case 'h':
	case '帮助':
	default:
		$cmd = 'help';
		$ci ="使用方法：\r\n\t%s <命令名>\r\n查询相关命令的用法。\r\n命令：\r\n";
		if(check_qq_cmd($api_obj,$sender,'r'))
			$ci .= "充值/recharge/r  充值，如果是新用户则会首先绑定帐号然后充值。\r\n";
		if(check_qq_cmd($api_obj,$sender,'q'))
			$ci .= "查询余额/query/q 查询帐户余额。\r\n";
		if(check_qq_cmd($api_obj,$sender,'qi'))
			$ci .= "查询帐号/queryinfo/qi 查询帐号绑定信息。\r\n";
		if(check_qq_cmd($api_obj,$sender,'qc'))
			$ci .= "查询卡号/querycard/qc 查询帐号绑定信息。\r\n";
		if(check_qq_cmd($api_obj,$sender,'ca'))
			$ci .= "ca 添加外部充值卡。\r\n";
//		if(check_qq_cmd($api_obj,$sender,'ub'))
//			$ci .= "解除绑定/unbind/ub 解除号码绑定。\r\n";
		if(check_qq_cmd($api_obj,$sender,'mp'))
			$ci .= "修改密码/modifypass/mp 修改密码。\r\n";
		if(check_qq_cmd($api_obj,$sender,'sc'))
			$ci .= "退卡/stop_caller/sc 退卡命令，将号码对应的帐户余额扣除。";
		
		$msg = sprintf($ci,$cmd);
		break;
	}
	
	return $msg;	
}

function run_cmd($api_obj,$sender,$params)
{
	$cmds = array(
		//cmd => func
		'recahrge' => cmd_recharge,		//caller,pin,pass
		'r'	=> cmd_recharge,			//caller,pin,pass
		'充值' => cmd_recharge,			//caller,pin,pass
		'query' => cmd_query,			//caller
		'q' => cmd_query,				//caller
		'查询余额' => cmd_query,			//caller
		'queryinfo' => cmd_query_info,	//caller
		'qi'	=> cmd_query_info,		//caller
		'查询帐号' => cmd_query_info,		//caller
		'querycard' => cmd_query_card,	//caller
		'qc'	=> cmd_query_card,		//caller
		'查询卡号' => cmd_query_card,		//caller
		'ca'	=> cmd_card_add,		//card,pass,value,type
//		'unbind' => cmd_unbind,	//caller
//		'ub'	=> cmd_unbind,		//caller
//		'解除绑定' => cmd_unbind,		//caller
		'modifypass' => cmd_modify_pass,		//caller,pass,newpass
		'mp' => cmd_modify_pass,				//caller,pass,newpass
		'stop_caller' => cmd_stop_caller,		//caller
		'sc' => cmd_stop_caller,		//caller
		'退卡' => cmd_stop_caller,		//caller
		'sms' => cmd_sms,				//发送短信
		'a' => cmd_active,				//cmd
		'h' => cmd_help,				//cmd
		'帮助' => cmd_help 				//cmd
	);
	list($cmd,$arg) = explode(' ',$params);
	$cmd = strtolower($cmd);
	if(!isset($cmds[$cmd])){
		$cmd = 'help';
	}
	
	if(!empty($cmds[$cmd])){
		//echo sprintf("cmd=%s\r\narg=%s\r\n",$cmd,$arg);
		$msg = call_user_func($cmds[$cmd],$api_obj,$sender,$arg);
	}else{
		$msg = cmd_help($api_obj,$sender,"");
	}
	if(!isset($msg))
		$msg = $api_obj->get_error_message($api_object->return_code,"返回代码 %s");
	return $msg;
}

?>

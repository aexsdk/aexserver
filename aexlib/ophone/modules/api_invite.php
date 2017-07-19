<?php
/*
 * 执行呼叫动作
 */

if ( isset($api_object->config->cbver) && $api_object->config->cbver >= 10800)
{
	do_callback($api_object);
}else{
	api_ophone_invite ( $api_object );
}

/*
 * 定义错误字符串处理的回调函数，正常情况下错误字符串已经可以按照语言和action取得。
 */
function get_message_callback($api_obj, $context, $msg) {
	return sprintf ( $msg, $api_obj->return_code
		,$api_obj->params ['api_params'] ['caller']
		,$api_obj->params ['api_params'] ['callee']
		);
}

function request_is_from_ophone(){
	$agent = $_SERVER['HTTP_USER_AGENT'];
	if(strpos($agent,'EZTOR')!=false){
		return true;
	}else{
		return false;
	}
}

/*
 * 写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
 */
function write_response_callback($api_obj, $context) {
	if($api_obj->return_code > 0){
		$resp = $api_obj->write_invite_return_xml();
		$resp .= $api_obj->write_invite_response_code();
	}else{
		$resp = $api_obj->write_return_xml ();
	}
	return $resp;
}

/**
 * 发起回拨呼叫
 *
 * @param object $api_obj
 * @param array $server
 * @param array $arouters
 * @param string $brouters
 * @param integer $maxtime
 * @return integer
 * 
Action: Originate
Synopsis: Originate Call
Privilege: call,all
Description: Generates an outgoing call to a Extension/Context/Priority or
  Application/Data
Variables: (Names marked with * are required)
	*Channel: Channel name to call
	Exten: Extension to use (requires 'Context' and 'Priority')
	Context: Context to use (requires 'Exten' and 'Priority')
	Priority: Priority to use (requires 'Exten' and 'Context')
	Application: Application to use
	Data: Data to use (requires 'Application')
	Timeout: How long to wait for call to be answered (in ms)
	CallerID: Caller ID to be set on the outgoing channel
	Variable: Channel variable to set, multiple Variable: headers are allowed
	Account: Account code
	Async: Set to 'true' for fast origination
 * 
 */
function callback($api_obj, $server, $arouters, $brouters,$maxtime) {
	$lang = $api_obj->params ['api_lang'];
	$caller = $api_obj->return_data['caller'];
	$callee = $api_obj->return_data['callee'];
	$var_split = isset($api_obj->config->var_split)?$api_obj->config->var_split:"|";
	$route_split = isset($api_obj->config->route_split)?$api_obj->config->route_split:",";
	$cb_path = isset($api_obj->config->cb_path)?$api_obj->config->cb_path:"asterisk";
	
	if (empty ( $lang )) {
		$lang = 'en-US';
		$ivr_lang = 'us';
	} elseif ($lang == "zh-tw") {
		$lang = 'zh-TW';
		$ivr_lang = 'cn';
	} elseif ($lang == "en-us") { //0201   0501
		$lang = 'en-US';
		$ivr_lang = 'us';
	} elseif ($lang == "zh-cn") { //0201 
		$lang = 'zh-CN';
		$ivr_lang = 'cn';
	} elseif ($lang == "en") { //L88 
		$lang = 'en-US';
		$ivr_lang = 'us';
	} elseif ($lang == "zh") { //L88 
		$lang = 'zh-CN';
		$ivr_lang = 'cn';
	} elseif ($lang == "*#0086#" or substr ( $lang, 0, 1 ) == "*") { // 0501
		$lang = 'zh-CN';
		$ivr_lang = 'cn';
	} else {
		$lang = 'en-US';
		$ivr_lang = 'us';
	}
	
	//设置asterisk_url
	$asterisk_url = sprintf ( "%s:%d", $server [0], $server [1] ); //$api_obj->config->asterisk_config['url'];
	//登录asterisk的用户名
	$asterisk_name = $server [2]; //$api_obj->config->asterisk_config['name'];
	//登录asterisk的密码
	$asterisk_pass = $server [3]; //$api_obj->config->asterisk_config['password'];
	
	$callerIP = get_request_ipaddr ();
	foreach ( $arouters as $arouter ) {
		//解析路由号码
		$caller_route = split_call_route($route_split,$arouter);
		$tmp = explode("|",$brouters);
		$callee_route = split_call_route($route_split,isset($tmp[0])?$tmp[0] : '');
		$callee_route2 = split_call_route($route_split,isset($tmp[1])?$tmp[1] : '');
		//echo sprintf("<br>Caller route <br>%s<br>Callee route:%s",array_to_string("<br>",$caller_route),$brouters);
		/*$callee_p = $ca[0];
		$callee_ip = $ca[1];
		$callee_num = $ca[2];
		$caller = $a [1];*/
		$times = $caller_route['times'];
		$sleep_time = $caller_route['sleep_time'];
		$var_ActionID = sprintf("%s-%s",date ( "Ymdhis", time () ),$caller_route['route']);
		//echo sprintf("<br>%s,%s,%d,%d<br>",$a[0],$a[1],$times,$timeout);
		//设置cookie
		$cookie_jar = tempnam ( './tmp', 'cookie' );
		//登录
		if (login_asterisk ( $api_obj, $asterisk_url, $asterisk_name, $asterisk_pass, $cookie_jar )) {
			while ( $times > 0 ) {
				$times = $times - 1;
				$api_obj->push_return_data('calllog',sprintf("%s\r\n\t %s call by %s  ...\r\n",
				$api_obj->return_data['calllog'],date ( "Y-m-d-h:i:s", time () ),$arouter));
				//构造呼叫参数
				$rad = $api_obj->config->rad_acct;
				$ufield = sprintf("Ophone%%26var_sourceip=%s%%26var_caller=%s/%s",
					$callerIP,$caller_route['ip'],$caller);
				$vars = array(
					"var_ActionID" => $var_ActionID, 
					"var_calleechan" => $callee_route['chan'], 
					"var_calleeip" => $callee_route['ip'], 
					"var_callee" => $callee_route['number'], 
					"var_calledchan" => $callee_route2['chan'], 
					"var_calledip" => $callee_route2['ip'], 
					"var_called" => $callee_route2['number'], 
					"var_callorder" => $api_obj->params['api_o'][1], 
					"var_oldcaller" => $api_obj->params['api_params']['pno'], 
					"var_lang" => $ivr_lang,
					"var_User_Field" => $ufield,
					"var_cdr_radius" => $rad,
					"var_account" => $api_obj->params['api_params']['pno'],
					"var_caller_ip" => get_request_ipaddr(),		//from ip address
					"var_callee_ip" => $caller_route['ip'],			//call the caller ip address
					"var_maxtime" => $maxtime,
					"var_routes" => $brouters		//传递被叫路由
				);
				$caller_field = sprintf('%1$s<%1$s>',$caller_route['caller']);
				$call_params =array(
					"Action" => "Originate", 
					"Channel" => $caller_route['route'], 
					"Context" => 'callback', 
					"Exten" => 'begin', 
					"Priority" => '1', 
					"Timeout" => 60000, 
					"Async" => request_use_cdma(), 
					"Callerid" => $caller_field,  
					"Account" => $api_obj->params ['api_params'] ['pin'], 
					"ActionID" => $var_ActionID,
					"variable" => array_to_string($var_split,$vars).$var_split.$api_obj->return_data['call-params']
				);
				//$url = sprintf('http://%s/asterisk/rawman?%s',$asterisk_url,array_to_string('&',$call_params));
				$url = sprintf('http://%s/%s/rawman?%s',$asterisk_url,$cb_path,array_to_string('&',$call_params));
				$rc = send_callback($api_obj,$url,$cookie_jar);
				$api_obj->push_return_data('calllog',sprintf("%s\r\n\tSend Callback return %d\r\n",
					$api_obj->return_data['calllog'],$rc));
				if($rc > 0){
					//登出
					logoff_asterisk ( $api_obj, $asterisk_url, $cookie_jar );
					//删除cookie
					unlink ( $cookie_jar );
					if($rc == 1)
						return $rc;
					else{ 
						$times = 0;
						continue;
					}
				}
			}//end while
			//登出
			logoff_asterisk ( $api_obj, $asterisk_url, $cookie_jar );
		}
		//删除cookie
		unlink ( $cookie_jar );
		sleep($sleep_time>3?3:$sleep_time);
	}
	return -1;
}

/*
 * login asterisk
 */
function login_asterisk($api_obj, $asterisk_url, $asterisk_user, $asterisk_pass, $cookie_jar) {
	$cb_path = isset($api_obj->config->cb_path)?$api_obj->config->cb_path:"asterisk";
	//通过curl，向asterisk发送请求
	//第一次登录
	$ch_login = curl_init ();
	//curl_setopt ( $ch_login, CURLOPT_URL, 
	//	sprintf('http://%s/asterisk/rawman?action=login&username=%s&secret=%s',$asterisk_url,$asterisk_user,$asterisk_pass));
	curl_setopt ( $ch_login, CURLOPT_URL, 
		sprintf('http://%s/%s/rawman?action=login&username=%s&secret=%s',$asterisk_url,$cb_path,$asterisk_user,$asterisk_pass));
	curl_setopt ( $ch_login, CURLOPT_HEADER, 0 );
	curl_setopt ( $ch_login, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ( $ch_login, CURLOPT_TIMEOUT, 30 );
	curl_setopt ( $ch_login, CURLOPT_CONNECTTIMEOUT, 30 );
	//把返回来的cookie信息保存在$cookie_jar文件中
	curl_setopt ( $ch_login, CURLOPT_COOKIEJAR, $cookie_jar );
	$output_login = curl_exec ( $ch_login );
	curl_close ( $ch_login );
	
	/*
	//第二次登录
	$ch_login1 = curl_init();	
	curl_setopt($ch_login1,CURLOPT_URL,'http://'.$asterisk_url.'/asterisk/rawman?action=login&username='.$asterisk_user.'&secret='.$asterisk_pass);
	curl_setopt($ch_login1, CURLOPT_HEADER, 0);
	curl_setopt($ch_login1, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch_login1, CURLOPT_TIMEOUT, 30); 
	//把返回来的cookie信息保存在$cookie_jar文件中
	curl_setopt($ch_login1, CURLOPT_COOKIEFILE, $cookie_jar);
	$output_login1 = curl_exec($ch_login1);
	curl_close($ch_login1);
	*/
	//获取返回值
	$login_response_array = api_string_to_array ( $output_login, "\n", ':' );
	$login_response_value = $login_response_array ['Response'];
	//echo sprintf("<br>Login response:<br>%s<hr>",$output_login);
	//flush();
	$api_obj->write_hint($output_login);
	if (strtoupper ( trim ( $login_response_value ) ) == strtoupper ( 'Error' )) {
		$return_value = - 1;
		$api_obj->set_return_code ( - 82 );
		$api_obj->push_return_data('callback',$output_login);
		$api_obj->write_error ( sprintf ( "Login to callbcak server fail:%s", $asterisk_url ) );
	} else {
		$return_value = 1;
	}
	return $return_value;
}

/*
 * logoff asterisk
 */
function logoff_asterisk($api_obj, $asterisk_url, $cookie_jar) {
	$cb_path = isset($api_obj->config->cb_path)?$api_obj->config->cb_path:"asterisk";
	//通过curl，向asterisk发送请求
	//$ch_logoff = curl_init ( 'http://' . $asterisk_url . '/asterisk/rawman?action=logoff' );
	$ch_logoff = curl_init ( 'http://' . $asterisk_url . "/$cb_path/rawman?action=logoff" );
	curl_setopt ( $ch_logoff, CURLOPT_HEADER, 0 );
	curl_setopt ( $ch_logoff, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ( $ch_logoff, CURLOPT_TIMEOUT, 30 );
	curl_setopt ( $ch_logoff, CURLOPT_CONNECTTIMEOUT, 30 );
	curl_setopt ( $ch_logoff, CURLOPT_COOKIEFILE, $cookie_jar );
	$output_logoff = curl_exec ( $ch_logoff );
	curl_close ( $ch_logoff );
	
	//获取返回值
	$logoff_response_array = api_string_to_array($output_logoff, "\n", ':' );
	$logoff_response_value = $logoff_response_array ['Response'];
	if (strtoupper ( trim ( $logoff_response_value ) ) == strtoupper ( 'Error' )) {
		$log_data = time () . 'log_id=' . 'logoff filed' . "\n";
		$myfile = 'logoff_log.txt';
		$file_pointer = fopen ( $myfile, "a" ) or die ( 'can not open' );
		fwrite ( $file_pointer, $log_data );
		fclose ( $file_pointer );
	}
	
	//删除cookie
	system ( 'rm -rf /tmp/' . $cookie_jar );
}

/**
 * 发送回拨呼叫
 *
 * @param string $url
 * @param string $cookie
 * @return integer
 */
function send_callback($api_obj,$url,$cookie){
	$ch = curl_init ( $url);
	curl_setopt ( $ch, CURLOPT_HEADER, 0 );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ( $ch, CURLOPT_TIMEOUT, 40 );
	curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 40 );
	curl_setopt ( $ch, CURLOPT_COOKIEFILE, $cookie);
	$output = curl_exec ( $ch ); //获取HTTP返回值
	$response_code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE);
	curl_close ( $ch );
	
	//获取返回值
	$output = str_ireplace("\r\n","\r",$output);
	$call_response_array = api_string_to_array ( trim($output), "\r", ':' );
	$call_response_value = strtolower(trim($call_response_array ['Response']));
	//$api_obj->write_hint(sprintf("Callback response :%s",$output));
	//$msg = sprintf("\r\n<br>Callback url :%s<hr>\r\nCallback response[%d]:\r\n%s",
	//	$url,$response_code,$output);
	//$api_obj->write_hint($msg);
	$api_obj->push_return_data('calllog',sprintf("%s<$call_response_value>\r\n\t\t[%s].",$api_obj->return_data['calllog'],array_to_string(",",$call_response_array)));
	if ($call_response_value  == 'error') 
	{
		$api_obj->push_return_data('Originate',$call_response_array ['Message']);
		$n_return_value = - 1;
	} else if($call_response_value == 'success'){
		$n_return_value = 1;
	} else if(is_null($call_response_value) or $call_response_value == ''){
		$n_return_value = 2;
	} else{
		$n_return_value = 0;
	}
	return $n_return_value;
}

/*
 *	This function is used to dispose error for callback.
 *	If callback is error,system will trying again in 10 second.
 *	
 */
function call_asterisk($api_obj, $v_url_string, $cookie_jar, $db_link, $params, $channel_name, $channel_ip, $channel_caller, $acion_id) {
	//开始呼叫
	//通过curl，向asterisk发送请求
	//呼叫开始时间
	//$d_run_start_time = microtime();
	$d_run_start_time = strtotime ( date ( "Y-m-d H:i:s", time () ) );
	$d_start_time = date ( "Y-m-d H:i:s", time () );
	
	$ch = curl_init ( $v_url_string );
	curl_setopt ( $ch, CURLOPT_HEADER, 0 );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ( $ch, CURLOPT_TIMEOUT, 35 );
	curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 35 );
	curl_setopt ( $ch, CURLOPT_COOKIEFILE, $cookie_jar );
	$output = curl_exec ( $ch ); //获取HTTP返回值
	$response_code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
	curl_close ( $ch );
	
	//获取返回值
	$call_response_array = api_string_to_array ( $output, "\n", ':' );
	$call_response_value = $call_response_array ['Response'];
	
	if (strtoupper ( trim ( $call_response_value ) ) == strtoupper ( 'Error' ) or is_null ( $call_response_value )) {
		$n_return_value = - 1;
	} else {
		$n_return_value = 1;
	}
	
	//写日志
	//$log_params = array ($params ['pno'], $params ['imei'], $acion_id, $channel_name, $channel_ip, $channel_caller, $params ['caller'], $params ['callee'], $d_start_time, date ( "Y-m-d H:i:s", time () ), strtotime ( date ( "Y-m-d H:i:s", time () ) ) - $d_run_start_time, $output . 'response_code:' . $response_code, $n_return_value, $v_url_string );
	//callback_write_log ( $api_obj, $db_link, $log_params );
	
	return $n_return_value;
}

/*
 * Callback System: Invite
 */
function api_ophone_invite($api_obj) {
	require_once __EZLIB__.'/common/api_billing_mssql.php';
	require_once __EZLIB__.'/common/api_billing_pgdb.php';
	//设置回调函数
	$api_obj->set_callback_func ( get_message_callback, write_response_callback, $api_obj );
	//把特殊前缀的呼叫先解析好放在数组里
	$callee = $api_obj->params ['api_params'] ['callee'];
	$callee_prefix = check_callee_prefix ( $api_obj, $callee );
	$api_obj->push_return_data("s.prefix",$callee_prefix['prefix']);
	$api_obj->push_return_data("s.callee",$callee_prefix['callee']);
	if($callee_prefix['prefix'] = ''){
		//号码中不包含前缀，先检查本地拨号规则，然后再次进行号码前缀检查
		//调整$Callee号码以便区分直接拨手机号码和使用0086拨号的区别
		$callee = add_callee_prefix($callee,$api_obj->config->inner_prefix);
		//再次检查号码前缀
		$callee_prefix = check_callee_prefix ( $api_obj, $callee );
		$api_obj->push_return_data("l.prefix",$callee_prefix['prefix']);
		$api_obj->push_return_data("l.callee",$callee_prefix['callee']);
	}
	
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	$route_db = new class_billing_pgdb($api_obj->config->route_db_config, $api_obj );
	$order = $api_obj->params ['api_o'] ['1'];
	//首先要做号码替换，调用相关数据的ez_utils_db.sp_build_prefix把号码变换为带地区和运营商标识的号码：国码+运营商+省份代码+区号+'-'+号码
	//经过变换后的主被叫号码为：
	//     特殊前缀+':'+国码+':'+运营商代码+省市代码+':'+区号+'：'+电话号码 ，如：A201:86:UGD:756:15602530665
	$def_prefix = isset($api_obj->params ['api_params'] ['prefix'])?$api_obj->params ['api_params'] ['prefix']:"0086";
	//echo '$def_prefix:'.$def_prefix.'<br />';
	
	//中华电信 本机号码设置了90
	/*$caller_s =  $api_obj->params ['api_params'] ['caller'];
	if( substr ( $caller_s, 0, 1 ) == '9'){
		$caller_s = substr ( $caller_s, 1, strlen ( $caller_s ));
	}*/
	//$caller_s = check_phone_number ( $caller_s, $def_prefix );
	$caller_s = check_phone_number ( $api_obj->params ['api_params'] ['caller'], $def_prefix );

	$callee_s = check_phone_number ( $callee_prefix ['callee'], $def_prefix );
	
	$caller = $route_db->phone_build_prefix ($caller_s);
	$callee = $route_db->phone_build_prefix ($callee_s);
//	echo '$$callee_s:'.$callee_s.'<br />';
//	echo '$$caller_s:'.$caller_s.'<br />';
//	echo '$$$caller:'.$caller.'<br />';
//	echo '$$$callee:'.$callee.'<br />';
	
	
	$cp = $callee_prefix ['prefix'] == '' ? '' : $callee_prefix ['prefix'].':';
	
	if ($order == '1') {
		//先呼被叫调换主被叫次序
		$caller_tmp = 'A' . ($callee == $callee_s? $cp.$callee : $callee_prefix ['prefix'].':'.$callee);
		$callee = 'B' . ($caller == $caller_s? $cp.$caller : $callee_prefix ['prefix'].':'.$caller);
		$caller = $caller_tmp;
//		echo '$cp:'.$cp.'<br />';
//		echo '$callee_prefix prefix:'.$callee_prefix ['prefix'].'<br />';
//		echo 'caller1:'.$caller.'<br />';
//		echo 'callee1:'.$callee.'<br />';
	} else {
		//先呼主叫次序不变
		$caller = 'A' . ($caller == $caller_s ? $cp.$caller : $callee_prefix ['prefix'].':'.$caller);
		$callee = 'B' . ($callee == $callee_s ? $cp.$callee : $callee_prefix ['prefix'].':'.$callee);
//		echo '$cp:'.$cp.'<br />';
//		echo '$callee_prefix prefix:'.$callee_prefix ['prefix'].'<br />';
//		echo 'caller0:'.$caller.'<br />';
//		echo 'callee0:'.$callee.'<br />';
	}
	
	$params = array (
		'pin' => $api_obj->params ['api_params'] ['pin'], 
		'pass' => trim($api_obj->params ['api_params'] ['pass']), 
		'pno' => check_phone_number ( $api_obj->params ['api_params'] ['pno'], $api_obj->params ['api_params'] ['prefix'] ), 
		'caller' => $caller, 
		'callee' => $callee, 
		'show' => $api_obj->params ['api_o'] ['0'], 
		'order' => $order,
		'lang' => $api_obj->params ['api_lang']
	);
	$api_obj->push_return_data("caller",$caller);
	$api_obj->push_return_data("callee",$callee);
	//进行回拨认证
	$r = $billing_db->callback_invite_auth($params);
	if (is_array ( $r )) {
		//如果数数组说明验证通过，否则已经返回错误结果，这里不用再处理
		$api_obj->push_return_data('auth',array_to_string(";",$r));
		if($api_obj->return_code < 0)
		{
			$api_obj->write_response();
			return;
		}
		//下面获取路由
		if (isset ( $r ['caller-h323_redirect_number'] ))
			$caller = $r ['caller-h323_redirect_number'];
		if (isset ( $r ['callee-h323_redirect_number'] ))
			$callee = $r ['callee-h323_redirect_number'];
		$mode_caller = isset ( $r ['caller-h323_billing_mode'] ) ? $r ['caller-h323-billing-mode'] : 1;
		if ($mode_caller != 0) {
			//预付费模式
			$caller_mt = isset ( $r ['caller-h323_credit_time'] ) ? $r ['caller-h323_credit_time'] : 0;
		} else {
			$caller_mt = 0x7FFF;
		}
		$mode_callee = isset ( $r ['callee-h323_billing_mode'] ) ? $r ['callee-h323_billing_mode'] : 1;
		if ($mode_callee != 0) {
			//预付费模式
			$callee_mt = isset ( $r ['callee-h323_credit_time'] ) ? $r ['callee-h323_credit_time'] : 0;
		} else {
			$callee_mt = 0x7FFF;
		}
		//echo sprintf("<hr>caller maxtime=%d,callee maxtime=%d<hr>",$caller_mt,$callee_mt);
		//通话时长单位秒，必须是整数类型
		$maxtime = intval(min ( array ($caller_mt, $callee_mt ) ) / 2); //回拨去最小时间的一半
		if ($maxtime < 60) { //最大通话市场不足一分钟，则拒绝呼叫
			$billing_db->set_return_code(- 150); //最大通话时长不足一分钟，错误显示代码
		} else {
			$route = $route_db->get_callback_route ( array (
					'pno' => $params ['pno'], 
					'caller' => $caller, 
					'callee' => $callee, 
					'show' => $api_obj->params ['api_o'] ['0'],
					'gateway_prefix' => $api_obj->config->gateway_prefix
				) 
			);
			$p_servers = empty($route ['t_callback_server'])?join(",",$api_obj->config->asterisk_config): $route ['t_callback_server'];
			//$p_servers = join(",",$api_obj->config->asterisk_config);
			$p_arouters = $route['t_caller_params']; //"SIP/112.91.144.201/8615602522020,8615602522020,3,30";
			$p_brouters = $route['t_called_params']; //"SIP/112.91.144.201/8615602522021,8615602522020,3,30";
			//下面修改路由参数的分隔符，这是为了兼容存储过程中的分隔符和回拨系统能够识别的分隔符
			$rs = isset($api_obj->config->route_split)?$api_obj->config->route_split:",";
			//将路由中的逗号替换为配置中的路由分隔符
			$p_arouters = str_replace(",",$rs,$p_arouters);
			//将路由中的分号替换为配置中的路由分隔符
			$p_arouters = str_replace(";",$rs,$p_arouters);
			//将路由中的逗号替换为配置中的路由分隔符
			$p_brouters = str_replace(",",$rs,$p_brouters);
			//将路由中的分号替换为配置中的路由分隔符
			$p_brouters = str_replace(";",$rs,$p_brouters);
			//下面修改路由数组的分隔符，这是为了兼容存储过程的分隔符和回拨系统可以识别的分隔符
			$ra_split = isset($api_obj->config->ra_split)?$api_obj->config->ra_split:"|";
			$p_arouters = str_replace("|",$ra_split,$p_arouters);
			$p_brouters = str_replace("|",$ra_split,$p_brouters);
			
			$api_obj->push_return_data("servers",$p_servers);
			$api_obj->push_return_data("A routers",$p_arouters);
			$api_obj->push_return_data("B routers",$p_brouters);
			$api_obj->push_return_data("call-params",sprintf('caller=%2$s%1$scallee=%3$s%1$sh323-credit-time=%4$d%1$sh323-billing-model=%5$d%1$scall_progress_time=%6$s',
				isset($api_obj->config->var_split)?$api_obj->config->var_split:"|",$caller,$callee,$maxtime,$mode_callee,60));
			//echo sprintf("<hr>server:%s<br>A router:<br>%s<br>B router:<br>%s<hr>",$p_servsers,$p_arouters,$p_brouters);
			if(empty($p_servers) || empty($p_arouters) || empty($p_brouters)){
				//选择路由失败
				$billing_db->set_return_code(-151);
				$billing_db->write_error ( sprintf ( "User %s use %s=>%s call to %s=>%s has no route.", 
					$params['pin'],$api_obj->params ['api_params'] ['caller'],$caller,$api_obj->params ['api_params'] ['callee'],$callee) );
			}else{
				$servers = explode ( "|", $p_servers );
				$api_obj->push_return_data('calllog',"\r\n");
				foreach ( $servers as $s ) {
					//echo sprintf("<br>Send call to server : %s<br>",$s);
					$api_obj->push_return_data('calllog',sprintf("%s\r\nSend to %s...",$api_obj->return_data['calllog'],$s));
					$server = explode ( ",", $s );
					if (is_array ( $server )) {
						//依次服务器发起呼叫，发送成功后退出程序
						$cdma = request_use_cdma()?'true':"false";
						$rcode = callback ( $api_obj, $server, explode ($ra_split, $p_arouters), $p_brouters,$maxtime,$params,$cdma);
						if ($rcode == 1) {
							$billing_db->set_return_code ( 451 );
							$api_obj->push_return_xml('<INCALLING>%d</INCALLING>',451);
							$api_obj->write_response ();
							return;
						}else if($rcode == 2){
							$billing_db->set_return_code ( - 152 );
							$api_obj->write_response ();
							return;
						}
					}
				}
				//如果执行到这里说明没有可用的服务器
				$billing_db->set_return_code ( - 153 );
				//$billing_db->write_error ("Do not call to caller with each server. ");
			}
		}
	}
	$api_obj->write_response ();
}

function do_callback($api_obj)
{
	require_once __EZLIB__.'/common/api_billing_mssql.php';
	require_once __EZLIB__.'/common/api_billing_pgdb.php';
	//设置回调函数
	$api_obj->set_callback_func ( get_message_callback, write_response_callback, $api_obj );
	$params = array(
		'pin' => $api_obj->params ['api_params'] ['pin'], 
		'pass' => trim($api_obj->params ['api_params'] ['pass']), 
		'pno' => check_phone_number ( $api_obj->params ['api_params'] ['pno'], $api_obj->params ['api_params'] ['prefix'] ), 
		'caller' => $api_obj->params ['api_params'] ['caller'], 
		'callerip' => get_request_ipaddr(),
		'callee' => $api_obj->params ['api_params'] ['callee'], 
		'o' => $api_obj->params ['api_o'], 
		'lang' => $api_obj->params ['api_lang'],
		'prefix' => $api_obj->params ['api_params']['prefix']
		);
	$api_obj->set_callback_func ( get_message_callback, write_response_callback, $api_obj );
	api_callback($api_obj,$params,'false');
	$api_obj->write_response();
}
?>
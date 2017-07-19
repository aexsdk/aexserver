<?php
require_once(dirname(__FILE__) .'/class.phpmailer-lite.php');
require_once(__EZLIB__.'/libary/firephp/FirePHPCore/fb.php');

date_default_timezone_set('Asia/Chongqing');


function find_params($pn,$default=''){
	if(is_array($pn)){
		foreach ($pn as $name){
			if(isset($_REQUEST[$name])){
				return $_REQUEST[$name];
			}
		}
		return $default;
	}else{
		return isset($_REQUEST[$pn])?$_REQUEST[$pn]:$default;
	}
}

/*try{
	echo $from;
	$from = strtotime($_REQUEST['$from']);
}catch( Exception $e){
	echo '<br>error<br>'.$e->getMessage().'<br>';
	$from = mktime(0, 0, 0, date("m")  , date("d"), date("Y"));
}*/

/**
 * 检查电话号码，把号码修改成为全国码的电话号码
 *		在计费和路由中我们只使用全国码的电话号码
 */
function check_phone_number($pn,$prefix){
	if(substr($pn,0,5) == '+0086')
		return substr($pn,3);
	if(substr($pn,0,1) == '+')
		return substr($pn,1);
	if(substr($pn,0,2) == '00'){
		//国际电话，不需要添加前缀只是把前导符00去掉就可以了
	}else if(substr($pn,0,1) == '0'){
		//国内长途
		$pn = $prefix.substr($pn,1);
	}else{
		$pn = $prefix.$pn;
	}
	return substr($pn,2);	//去掉前导00，变为全国码的电话号码
}

/**
 * 为被叫号码添加前缀，只是为了区别直接拨手机号码，区号+号码拨固话以及0086拨号的差别。
 * 如：加上9，则我们把直接拨手机号码和拨固话，以9前缀计费，0086拨号按照国际资费计算。
 * 我们在进行号码处理前先调用此函数处理被叫号码，以便我们可以区分这个拨号方式。
 */
function add_callee_prefix($pn,$prefix){
	$callee = $pn;
	if(substr($pn,0,1) == '+')
		$callee = '00' . substr($pn,1);
	if(substr($pn,0,2) == '00'){
		//国际拨号
	}else if(substr($pn,0,1) == '0'){
		//国内长途
		$callee = $prefix.substr($pn,1);
	}else{
		$callee = $prefix.$pn;
	}
	return $callee;	
}

function request_use_cdma(){
	$bear = $_SERVER['HTTP_X_UP_BEAR_TYPE'];
	if(substr(strtolower($bear),0,4) == 'cdma')
		return true;
	else 
		return false;
}

function split_call_route($route_split,$route){
	if($route[0] == '/')
				$route = substr($route,1,length($route));
	$route = explode($route_split,$route);
	if(!isset($route[1])) $route[1] = '';
	if(!isset($route[2])) $route[2] = 3;
	if(!isset($route[3])) $route[3] = 30;
	$ra = explode('/',$route[0]);
	if(!isset($ra[0])) $ra[0] = 'SIP';
	if(!isset($ra[1])) $ra[1] = '';
	if(!isset($ra[2])) $ra[2] = '';
	
	return array(
		'route' => $route[0],
		'chan' => $ra[0],
		'ip' => $ra[1],
		'number' => $ra[2],
		'caller' => $route[1],
		'times' => $route[2],
		'sleep_time' => $route[3]
		);
}

/**
 * 检查特殊呼叫的前缀，如：400、201等等，并把被叫号码拆分为前缀和号码
 *
 * @param object $api_obj 
 * @param string $callee
 * @return array
 */
function check_callee_prefix($api_obj, $callee) {
	$prefix = '';	
	$prefixs = $api_obj->config->api_prefixs;
	if (isset ( $prefixs )) {
		foreach ( $prefixs as $k => $v ) {
			$l = strlen ( $k );
			$ps = substr ( $callee, 0, $l );
			if ($ps == $k) {
				$prefix = $v;
				$callee = substr ( $callee, $l, strlen ( $callee ) );
				if (substr($callee,0,2) == 86) {
					$callee = substr ( $callee, 2, strlen ( $callee ) );
				}
				break;
			}
		}
	}
	return array ('prefix' => $prefix, 'callee' => $callee );
}

/**
 * 根据请求ip获得参数
 *
 * @return unknown
 */
function get_request_preparams($api_object)
{
	if(isset($api_object->config->allow_ips)){
		$ip = get_request_ipaddr();
		foreach ($api_object->config->allow_ips as $aip => $desc){
			if($ip == $aip){
				if(is_string($desc))
					$desc = api_string_to_array($desc,',','=');
				return $desc;
			}
		}
		if(isset($api_object->config->allow_ips['default'])){
			$desc = $api_object->config->allow_ips['default'];
			if(is_string($desc))
				$desc = api_string_to_array($desc,',','=');
			return $desc;
		}
		return false;
	}else{
		return false;	
	}
}


function get_trace_string(){
	$t = debug_backtrace();
	$r = '';
	foreach ($t as $tl){
		$r .= sprintf("\t%s",array_to_string(",",$tl));
	}
	return $r;
}

function send_mail($params,$subject,$body){

	$mail             = new PHPMailerLite(); // defaults to using php "Sendmail" (or Qmail, depending on availability)
	$mail->CharSet = 'UTF-8';
	$mail->SetFrom($params['from'], '');	
	//$mail->set();
	$address = $params['address'];
	$mail->AddAddress($address, '');
	if(!empty($params['cc']))
		$mail->AddCC($params['cc']);
	$mail->Subject    = $subject;
	
	$mail->AltBody    = "查看此邮件请使用HTML兼容的邮件查看器!"; // optional, comment out and test
	
	$mail->MsgHTML($body);
	
	//$mail->AddAttachment("images/phpmailer.gif");      // attachment
	//$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment
	
	if(!$mail->Send()) {
		return array('code' => -1,'error'=>$mail->ErrorInfo); 
	} else {
		return true;	//Send mail success
	}
}

/**
 替换请求数组中【action】字符串中的“-”为“_”
 * */
function to_regulate_action($string){
	//echo sprintf("\r\nKey=%s<br>\r\n",$key);
	$ReplaceStr  = preg_replace('/-/','_',$string);
	$ReplaceStr  = preg_replace("/'/","\\'",$ReplaceStr);
	return strtolower($ReplaceStr);
}

/*
	公共函数，用于把数组转化为以$d分开的$key=$value的格式的字符串。
*/
if(!function_exists('array_to_string'))
{
	function array_to_string($d,$array){
		$str = '';
		if(is_array($array)){
			foreach ($array as $key=>$value)
				$str = $str.$key."=".$value.$d;
		}
		$str = substr($str,0,strlen($str)-strlen($d));
		return $str;
	}
}
/**
 对字符串加密后转换为十六进制的表示格式
 * */
 if(!function_exists('api_encrypt')){
	function api_encrypt($string,$key){
		//echo sprintf("\r\nKey=%s<br>\r\n",$key);
		$encryptString  =  $string;//md5_encrypt_hex($string,$key);
		return   $encryptString;
	}
}

/**
 对十六进制表示的缓冲区解码后进行解密
 * */
if(!function_exists('api_decrypt')){
	function api_decrypt($string,$key){
		$decryptString  =   $string;//md5_decrypt_hex($string,$key);
		return   $decryptString;
	}
}
/*
	把字符串转换为数组的函数，字符串格式为以$delimiter_f隔开的，$key+$delimiter_s+$value上为
*/
if(!function_exists('api_string_to_array')){
	function api_string_to_array($string,$delimiter_f,$delimiter_s){
		$array = array();
		$string = explode($delimiter_f,$string);
		foreach($string as $value){
			$av = explode($delimiter_s,$value);
			if(! empty($av))
				$array[$av[0]] = trim($av[1]);
		}
		return $array;
	}
}

/*
 * 比较版本号
 * 返回值
 * 		0  版本好相同
 * 		1  新版本号大于旧版本号
 * 		-1 新版本好小于就版本号
 */
if (!function_exists('api_version_compare')){
	function api_version_compare($newversion, $oldversion) {
		$flag = 0;
		$newString  = explode(".",$newversion);
    	$oldString  = explode(".",$oldversion);
    
    	if(count($newString) == count($oldString))
    	{
        	for($i=0; $i < count($oldString); $i++)
        	{
            	if(intval($newString[$i]) > intval($oldString[$i]))
            	{
    	        	$flag = 1;
    	        	break;
            	}
            	else if(intval($newString[$i]) < intval($oldString[$i])) 
            	{
            		$flag = -1;
                	break;
            	}
            }
        } else {
        	$flag = (count($newString) > count($oldString) ? 1 : -1);
        }
        
        return $flag;
	}
}
/*
	定义Billing的数据库类
*/
require_once dirname(__FILE__).'/api_billing_mssql.php';

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
function ast_callback($api_obj, $server, $arouters, $brouters,$maxtime,$params,$Async='false') 
{
	$lang = $params ['lang'];
	$caller = $api_obj->return_data['caller'];
	$callee = $api_obj->return_data['callee'];
	$var_split = isset($api_obj->config->var_split)?$api_obj->config->var_split:"|";
	$route_split = isset($api_obj->config->route_split)?$api_obj->config->route_split:",";
	$cb_path = isset($api_obj->config->cb_path)?$api_obj->config->cb_path:"asterisk";
	$Context = isset($params['context'])?$params['context']:"callback";
	
	if (empty ( $lang )) {
		$lang = 'en-US';
		$ivr_lang = 'us';
	} elseif (strtolower($lang) == "zh-tw") {
		$lang = 'zh-TW';
		$ivr_lang = 'cn';
	} elseif (strtolower($lang) == "en-us") { //0201   0501
		$lang = 'en-US';
		$ivr_lang = 'us';
	} elseif (strtolower($lang) == "zh-cn") { //0201 
		$lang = 'zh-CN';
		$ivr_lang = 'cn';
	} elseif (strtolower($lang) == "en") { //L88 
		$lang = 'en-US';
		$ivr_lang = 'us';
	} elseif (strtolower($lang) == "zh") { //L88 
		$lang = 'zh-CN';
		$ivr_lang = 'cn';
	} elseif ($lang == "*#0086#" or substr ( $lang, 0, 1 ) == "*") { // 0501
		$lang = 'zh-CN';
		$ivr_lang = 'cn';
	} else {
		$lang = 'en-US';
		$ivr_lang = 'us';
	}
	
	//登录asterisk的用户名
	$asterisk_name = $server [2]; 
	//登录asterisk的密码
	$asterisk_pass = $server [3]; 
	$use_route_ext = isset($api_obj->config->use_route_ext)?$api_obj->config->use_route_ext:0;
	if($use_route_ext == 1 && isset($server [4])){
		$cb_path = $server [4];
	}
	//设置asterisk_url
	$asterisk_url = sprintf ( "http://%s:%d/%s/rawman", $server [0], $server [1],$cb_path ); 
	$callerIP = get_request_ipaddr ();
	foreach ( $arouters as $arouter ) {
		//解析路由号码
		$caller_route = split_call_route($route_split,$arouter);
		$tmp = explode("|",$brouters);
		$callee_route = split_call_route($route_split,isset($tmp[0])?$tmp[0] : '');
		$callee_route2 = split_call_route($route_split,isset($tmp[1])?$tmp[1] : '');
		$times = $caller_route['times'];
		$sleep_time = $caller_route['sleep_time'];
		$var_ActionID = sprintf("%s-%s",date ( "Ymdhis", time () ),$caller_route['route']);
		while ( $times > 0 ) {
			$times = $times - 1;
			$api_obj->push_return_data('calllog',sprintf("%s\r\n\t %s call by %s  ...\r\n",
				$api_obj->return_data['calllog'],date ( "Y-m-d-h:i:s", time () ),$arouter));
			//设置cookie
			$cookie_jar = tempnam ( './tmp', 'cookie' );
			//登录
			if (ast_login ( $api_obj, $asterisk_url, $asterisk_name, $asterisk_pass, $cookie_jar ) > 0) {
				//构造呼叫参数
				$rad = $api_obj->config->rad_acct;
				$ufield = sprintf("carrier=%s",$api_obj->config->carrier_name);
				$vars = array(
					"v_id" => $var_ActionID, 
					"v_callerip" => $caller_route['ip'], 
					"v_caller" => $caller, 
					"v_calleeip" => $callee_route['ip'], 
					"v_callee" => $callee_route['number'], 
					"v_lang" => $ivr_lang,
					"v_ufield" => $ufield,
					"v_order" => $params['o'],
					"v_cdr_radius" => $rad,
					"v_account" => $params['pin'],
					"v_src_ip" => get_request_ipaddr(),		//from ip address
					"v_maxtime" => $maxtime,
					"v_routes" => $brouters		//传递被叫路由
				);
				//extras
				if(is_array($params['extras']))
					$vars = array_merge($vars,  $params['extras']);		//传递给callback的扩展参数
				$caller_field = sprintf('%1$s<%1$s>',$caller_route['caller']);
				$call_params =array(
					"Action" => "Originate", 
					"Channel" => $caller_route['route'], 
					"Context" => $Context, 
					"Exten" => 'begin', 
					"Priority" => '1', 
					"Timeout" => 60000, 
					"Async" => $Async,		//request_use_cdma(), 
					"Callerid" => $caller_field,  
					"Account" => $params ['pin'], 
					"ActionID" => $var_ActionID,
					"variable" => urldecode(array_to_string($var_split,$vars).$var_split.$api_obj->return_data['call-params'])
				);
				$url = sprintf('%s?%s',$asterisk_url,array_to_string('&',$call_params));
				$rc = ast_send_callback($api_obj,$url,$cookie_jar);
				$api_obj->push_return_data('calllog',sprintf("%s\r\n\tSend Callback return %d\r\n",$api_obj->return_data['calllog'],$rc));
				if($rc > 0){
					//登出
					ast_logoff ( $api_obj, $asterisk_url, $cookie_jar );
					//删除cookie
					unlink ( $cookie_jar );
					if($rc == 1)
						return $rc;
					else{ 
						$times = 0;
						continue;
					}
				}
				//登出
				ast_logoff( $api_obj, $asterisk_url, $cookie_jar );
			}
			//删除cookie
			unlink ( $cookie_jar );
			sleep($sleep_time>3?3:$sleep_time);
		}//end while
	}
	return -1;
}

/*
 * login asterisk
 */
function ast_login($api_obj, $ast_url, $ast_user, $ast_pass, $cookie_jar) 
{
	$ch_login = curl_init ();
	curl_setopt ( $ch_login, CURLOPT_URL, 
		sprintf('%s?action=login&username=%s&secret=%s',$ast_url,$ast_user,$ast_pass));
	curl_setopt ( $ch_login, CURLOPT_HEADER, 0 );
	curl_setopt ( $ch_login, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ( $ch_login, CURLOPT_TIMEOUT, 30 );
	curl_setopt ( $ch_login, CURLOPT_CONNECTTIMEOUT, 30 );
	//把返回来的cookie信息保存在$cookie_jar文件中
	curl_setopt ( $ch_login, CURLOPT_COOKIEJAR, $cookie_jar );
	$output_login = curl_exec ( $ch_login );
	curl_close ( $ch_login );
	//获取返回值
	$login_response_array = api_string_to_array ( $output_login, "\n", ':' );
	$login_response_value = $login_response_array ['Response'];
	//echo sprintf("<br>Login response:<br>%s<hr>",$output_login);
	$api_obj->write_hint($output_login);
	if (strtoupper ( trim ( $login_response_value ) ) == strtoupper ( 'Error' )) {
		$return_value = - 1;
		$api_obj->return_code = - 82 ;
		$api_obj->push_return_data('callback',$output_login);
		return $api_obj->return_code;
		//$api_obj->write_error ( sprintf ( "Login to callbcak server fail:%s", $asterisk_url ) );
	} else {
		$return_value = 1;
	}
	return $return_value;
}

function ast_logoff($api_obj, $asterisk_url, $cookie_jar) 
{
	$cb_path = isset($api_obj->config->cb_path)?$api_obj->config->cb_path:"asterisk";
	
	$ch_logoff = curl_init ( $asterisk_url . "?action=logoff" );
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
		//$log_data = time () . 'log_id=' . 'logoff filed' . "\n";
		//$myfile = 'logoff_log.txt';
		//$file_pointer = fopen ( $myfile, "a" ) or die ( 'can not open' );
		//fwrite ( $file_pointer, $log_data );
		//fclose ( $file_pointer );
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
function ast_send_callback($api_obj,$url,$cookie){
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

function api_callback($api_obj,$params,$Async='false') {
	require_once __EZLIB__.'/common/api_billing_mssql.php';
	require_once __EZLIB__.'/common/api_billing_pgdb.php';
	//设置回调函数
	//把特殊前缀的呼叫先解析好放在数组里
	$callee = $params ['callee'];
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
		
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	$route_db = new class_billing_pgdb($api_obj->config->route_db_config, $api_obj );
	$order = $params ['o'] ['1'];
	//首先要做号码替换，调用相关数据的ez_utils_db.sp_build_prefix把号码变换为带地区和运营商标识的号码：国码+运营商+省份代码+区号+'-'+号码
	//经过变换后的主被叫号码为：
	//     特殊前缀+':'+国码+':'+运营商代码+省市代码+':'+区号+'：'+电话号码 ，如：A201:86:UGD:756:15602530665
	$def_prefix = isset($params ['prefix'])?$params ['prefix']:
		(isset($api_obj->config->default_ccode)?$api_obj->config->default_ccode:"0086");
	$api_obj->push_return_data('prefix',$params ['prefix']);
	$caller_s = check_phone_number ( $params ['caller'], $def_prefix );
	$callee_s = check_phone_number ( $callee_prefix ['callee'], $def_prefix );
	
	$caller = $route_db->phone_build_prefix ($caller_s);
	$callee = $route_db->phone_build_prefix ($callee_s);
	$cp = $callee_prefix ['prefix'] == '' ? '' : $callee_prefix ['prefix'].':';
	
	if ($order == '1') {
		//先呼被叫调换主被叫次序
		$caller_tmp = 'A' . ($callee == $callee_s? $cp.$callee : $callee_prefix ['prefix'].':'.$callee);
		$callee = 'B' . ($caller == $caller_s? $cp.$caller : $callee_prefix ['prefix'].':'.$caller);
		$caller = $caller_tmp;
	} else {
		//先呼主叫次序不变
		$caller = 'A' . ($caller == $caller_s ? $cp.$caller : $callee_prefix ['prefix'].':'.$caller);
		$callee = 'B' . ($callee == $callee_s ? $cp.$callee : $callee_prefix ['prefix'].':'.$callee);
	}
	$params['pno'] = $params ['pno'];//check_phone_number ( $params ['pno'], $params ['prefix'] );
	$params['caller'] = $caller;
	$params['callee'] = $callee;
	$params['show'] = $params ['o'] ['0'];
	$params['order'] = $order;
	$api_obj->push_return_data("caller",$caller);
	$api_obj->push_return_data("callee",$callee);
	//进行回拨认证
	/*$r = $billing_db->callback_invite_auth($params);
	if (is_array ( $r )) {
		$api_obj->push_return_data('auth',array_to_string(",",$r));
		if($api_obj->return_code < 0)
		{
			//$this->api_object->write_response();
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
*/
	$r = $billing_db->billing_cb_invite_auth($params);
	if (is_array ( $r )) {
		$api_obj->return_code = isset($r['RETURN-CODE'])?$r['RETURN-CODE']:$api_obj->return_code;
		if($api_obj->return_code < 0)
		{
			return $r;
		}
		//下面获取路由
		if (isset ( $r ['caller-auth']['h323_redirect_number'] ))
			$caller = $r ['caller-auth']['h323_redirect_number'];
		$mode_caller = isset ( $r ['caller-auth']['BillingMode'] ) ? $r ['caller-auth']['BillingMode'] : 1;
		if ($mode_caller != 0) {
			//预付费模式
			$caller_mt = isset ( $r ['caller-auth']['MaxTime'] ) ? $r ['caller-auth']['MaxTime'] : 0;
		} else {
			$caller_mt = 0x7FFF;
		}
		if (isset ( $r ['callee-auth']['h323_redirect_number'] ))
			$callee = $r ['callee-auth']['h323_redirect_number'];
		$mode_callee = isset ( $r ['callee-auth']['BillingMode'] ) ? $r ['callee-auth']['BillingMode'] : 1;
		if ($mode_callee != 0) {
			//预付费模式
			$callee_mt = isset ( $r ['callee-auth']['MaxTime'] ) ? $r ['callee-auth']['MaxTime'] : 0;
		} else {
			$callee_mt = 0x7FFF;
		}
	//echo sprintf("<hr>caller maxtime=%d,callee maxtime=%d<hr>",$caller_mt,$callee_mt);
		//通话时长单位秒，必须是整数类型
		$maxtime = intval(min ( array ($caller_mt, $callee_mt ) ) / 2); //回拨去最小时间的一半
		if ($maxtime < 60) { //最大通话市场不足一分钟，则拒绝呼叫
			$billing_db->set_return_code(- 150); //最大通话时长不足一分钟，错误显示代码
		} else {
			$params['gateway_prefix'] = $api_obj->config->gateway_prefix;
			$route = $route_db->get_callback_route ($params);
			$p_servers = empty($route ['t_callback_server'])?join(",",$api_obj->config->asterisk_config): $route ['t_callback_server'];
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
			if(empty($p_servers) || empty($p_arouters) || empty($p_brouters)){
				//选择路由失败
				$billing_db->set_return_code(-151);
				$billing_db->write_error ( sprintf ( "User %s use %s=>%s call to %s=>%s has no route.", 
					$params['pin'],$params ['caller'],$caller,$params ['callee'],$callee) );
			}else{
				$servers = explode ( "|", $p_servers );
				$api_obj->push_return_data('calllog',"\r\n");
				foreach ( $servers as $s ) {
					//echo sprintf("<br>Send call to server : %s<br>",$s);
					$api_obj->push_return_data('calllog',sprintf("%s\r\nSend to %s...",$api_obj->return_data['calllog'],$s));
					$server = explode ( ",", $s );
					if (is_array ( $server )) {
						//依次服务器发起呼叫，发送成功后退出程序
						if(strtolower($Async) == 'false' or $Async == false){
							//同步执行
							$rcode = ast_callback ( $api_obj, $server, explode ($ra_split, $p_arouters), $p_brouters,$maxtime,$params,'false');
							if ($rcode == 1) {
								$billing_db->set_return_code ( 451 );
								$api_obj->push_return_xml('<INCALLING>%d</INCALLING>',451);
								return;
							}else if($rcode == 2){
								$billing_db->set_return_code ( - 152 );
								return;
							}
						}else{
							//异步执行回拨流程，先返回给客户端
							if(function_exists('pcntl_fork')){
								$pid = pcntl_fork();
								if ($pid == -1) {
								     //
								} else if ($pid) {
									// we are the parent
									$billing_db->set_return_code ( 451 );
									$api_obj->push_return_xml('<INCALLING>%d</INCALLING>',451);
									return; 
								} else {
								    // we are the child
									$rcode = ast_callback ( $api_obj, $server, explode ($ra_split, $p_arouters), $p_brouters,$maxtime,$params,'false');
									if ($rcode == 1) {
										$billing_db->set_return_code ( 451 );
										$api_obj->push_return_xml('<INCALLING>%d</INCALLING>',451);
									}else if($rcode == 2){
										$billing_db->set_return_code ( - 152 );
									}
									return; 
								}
							}else{
								$rcode = ast_callback ( $api_obj, $server, explode ($ra_split, $p_arouters), $p_brouters,$maxtime,$params,'true');
								if ($rcode == 1) {
									$billing_db->set_return_code ( 451 );
									$api_obj->push_return_xml('<INCALLING>%d</INCALLING>',451);
								}else if($rcode == 2){
									$billing_db->set_return_code ( - 152 );
								}
								return;
							}
						}
					}
				}
				//如果执行到这里说明没有可用的服务器
				$billing_db->set_return_code ( - 153 );
				//$billing_db->write_error ("Do not call to caller with each server. ");
			}
		}
	}
}

function api_cb_invite($api_obj,$params,$Async='false') {
	require_once __EZLIB__.'/common/api_billing_mssql.php';
	require_once __EZLIB__.'/common/api_billing_pgdb.php';
	//设置回调函数
	//把特殊前缀的呼叫先解析好放在数组里
	$callee = $params ['callee'];
	$callee = str_ireplace('*','',$callee);
	$callee = str_ireplace('-','',$callee);
	$callee = str_ireplace(' ','',$callee);
	$callee = str_ireplace('(','',$callee);
	$callee = str_ireplace(')','',$callee);
	
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
		
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	$route_db = new class_billing_pgdb($api_obj->config->route_db_config, $api_obj );
	$order = $params ['o'] ['1'];
	//首先要做号码替换，调用相关数据的ez_utils_db.sp_build_prefix把号码变换为带地区和运营商标识的号码：国码+运营商+省份代码+区号+'-'+号码
	//经过变换后的主被叫号码为：
	//     特殊前缀+':'+国码+':'+运营商代码+省市代码+':'+区号+'：'+电话号码 ，如：A201:86:UGD:756:15602530665
	$def_prefix = isset($params ['prefix'])?$params ['prefix']:
		(isset($api_obj->config->default_ccode)?$api_obj->config->default_ccode:"0086");
	$api_obj->push_return_data('prefix',$params ['prefix']);
	$caller_s = check_phone_number ( $params ['caller'], $def_prefix );
	$callee_s = check_phone_number ( $callee_prefix ['callee'], $def_prefix );
	
	$caller = $route_db->phone_build_prefix ($caller_s);
	$callee = $route_db->phone_build_prefix ($callee_s);
	$cp = $callee_prefix ['prefix'] == '' ? '' : $callee_prefix ['prefix'].':';
	
	if ($order == '1') {
		//先呼被叫调换主被叫次序
		$caller_tmp = 'A' . ($callee == $callee_s? $cp.$callee : $callee_prefix ['prefix'].':'.$callee);
		$callee = 'B' . ($caller == $caller_s? $cp.$caller : $callee_prefix ['prefix'].':'.$caller);
		$caller = $caller_tmp;
	} else {
		//先呼主叫次序不变
		$caller = 'A' . ($caller == $caller_s ? $cp.$caller : $callee_prefix ['prefix'].':'.$caller);
		$callee = 'B' . ($callee == $callee_s ? $cp.$callee : $callee_prefix ['prefix'].':'.$callee);
	}
	//透传时被叫号码前加CLI
	$callee = $params ['o'] ['0'] == '1'?'CLI'.$callee:$callee;
	$params['pno'] = $params ['pno'];//check_phone_number ( $params ['pno'], $params ['prefix'] );
	$params['caller'] = $caller;
	$params['callee'] = $callee;
	$params['show'] = $params ['o'] ['0'];
	$params['order'] = $order;
	$api_obj->push_return_data("caller",$caller);
	$api_obj->push_return_data("callee",$callee);
	//进行回拨认证
	$r = $billing_db->billing_cb_invite_auth($params);
	if (is_array ( $r )) {
		$api_obj->return_code = isset($r['RETURN-CODE'])?$r['RETURN-CODE']:$api_obj->return_code;
		if($api_obj->return_code < 0)
		{
			return $r;
		}
		//下面获取路由
		if (isset ( $r ['caller-auth']['h323_redirect_number'] ))
			$caller = $r ['caller-auth']['h323_redirect_number'];
		$mode_caller = isset ( $r ['caller-auth']['BillingMode'] ) ? $r ['caller-auth']['BillingMode'] : 1;
		if ($mode_caller != 0) {
			//预付费模式
			$caller_mt = isset ( $r ['caller-auth']['MaxTime'] ) ? $r ['caller-auth']['MaxTime'] : 0;
		} else {
			$caller_mt = 0x7FFF;
		}
		if (isset ( $r ['callee-auth']['h323_redirect_number'] ))
			$callee = $r ['callee-auth']['h323_redirect_number'];
		$mode_callee = isset ( $r ['callee-auth']['BillingMode'] ) ? $r ['callee-auth']['BillingMode'] : 1;
		if ($mode_callee != 0) {
			//预付费模式
			$callee_mt = isset ( $r ['callee-auth']['MaxTime'] ) ? $r ['callee-auth']['MaxTime'] : 0;
		} else {
			$callee_mt = 0x7FFF;
		}
		//echo sprintf("<hr>caller maxtime=%d,callee maxtime=%d<hr>",$caller_mt,$callee_mt);
		//通话时长单位秒，必须是整数类型
		$maxtime = intval(min ( array ($caller_mt, $callee_mt ) ) / 2); //回拨去最小时间的一半
		if ($maxtime < 60) { //最大通话市场不足一分钟，则拒绝呼叫
			$billing_db->set_return_code(- 150); //最大通话时长不足一分钟，错误显示代码
		} else {
			$params['gateway_prefix'] = $api_obj->config->gateway_prefix;
			$route = $route_db->get_callback_route ($params);
			$p_servers = empty($route ['t_callback_server'])?join(",",$api_obj->config->asterisk_config): $route ['t_callback_server'];
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
			if(empty($p_servers) || empty($p_arouters) || empty($p_brouters)){
				//选择路由失败
				$billing_db->set_return_code(-151);
				$billing_db->write_error ( sprintf ( "User %s use %s=>%s call to %s=>%s has no route.", 
					$params['pin'],$params ['caller'],$caller,$params ['callee'],$callee) );
			}else{
				$billing_db->set_return_code ( 451 );
			}
		}
	}
	return $api_obj->return_data;
}

/*
 * 定义错误字符串处理的回调函数，正常情况下错误字符串已经可以按照语言和action取得。
 */
function get_message_callback_common($api_obj, $context, $msg) {
	return sprintf ( $msg, $api_obj->return_code);
}

/*
 * 写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
 */
function write_response_callback_common($api_obj, $context) {
	$resp = $api_obj->write_return_xml();
	return $resp;
}

function cc_callback($api_obj,$account,$phonelist,$ivrfiles,$endfile,$callee,$server)
{	
	$cdrurl = api_encrypt("key=ophone,action=cdr","ophone");
	$cdrurl = sprintf("http://%s:%s%s/api/api_ophone.php?p=%s",$_SERVER['SERVER_NAME'],$_SERVER['SERVER_PORT'],
		dirname(dirname($_SERVER['SCRIPT_NAME'])),$cdrurl);
	foreach ($phonelist as $caller){
		$caller = trim($caller);
		//检查号码合法性
		if(strlen($caller) < 10)continue;
		$params = array(
			'pin' => $account['pin'],
			'pass' => $account['pass'],
			'pno' => $caller,
			'caller' => $caller,
			'callee' => $callee,
			'o' => '00',
			'lang' => 'zh-CN',
			'prefix' => '0086',
			'context' => 'ccqueue',
			'extras' => array(
				"rpath"=>$account['rpath'],
				"cdrurl"=>$cdrurl,
				"sms"=>$_REQUEST['tt-sms'],
				"ivrfiles"=>$ivrfiles,
				'endfile'=>$endfile	
			)
		);
		$api_obj->set_callback_func ( get_message_callback_common, write_response_callback_common, $api_obj );
		api_callback($api_obj,$params,FALSE);
		if($api_obj->return_code > 0)
		{
			//$api_obj->config->ivr_path.'/callback_success';
		}else{
			switch($api_obj->return_code)
			{
				case -151:
				case -152:
				case -153:
					//$r['r_file'] = $api_obj->config->ivr_path.'/callback_fail_no_route';
					break;
				case -150:
				default:
					//$r['r_file'] = $api_obj->config->ivr_path.'/callback_fail_no_enough';
					break;
			}
		}
	}
}

?>
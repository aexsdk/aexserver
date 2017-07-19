<?php
error_reporting(E_ALL ^ E_NOTICE);//ignore the "notice" error

require_once (dirname(__FILE__).'/config/api_config.php');
require_once (__EZLIB__.'/common/api_common.php');				
require_once (__EZLIB__.'/common/api_radius_funcs.php');				
require_once (__EZLIB__.'/common/api_log_class.php');				
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
try{
	$v_lang = $_REQUEST['lang'];
	if (empty($v_lang) or $v_lang == 'zh' or $v_lang == '*#0086#' or substr($v_lang, 0, 1) == '*')
		$v_lang = 'zh-CN';
	//获取基本参数
	$p_params = array(
		'run_start_time'=>microtime(),
		'gprs_caller'=>$_SERVER['HTTP_X_UP_CALLING_LINE_ID'],
		'gprs_agent'=>$_SERVER['HTTP_USER_AGENT'],
		'api_version' => $_REQUEST['v'],
		'api_o' =>$_REQUEST['o'],
		'api_lang' => strtolower($v_lang),
		'api_p' => $_REQUEST['p'],
		'api_action'=> $_REQUEST['a'],
	    //'api_r_action'=>$_REQUEST['r_a'],
		'api_params'=>array('action'=> $_REQUEST['a']),
		'common-lang-path'=> __EZLIB__.'',
		'lang-path' => __EZLIB__.'/ophone_new',
		'common-path'=> __EZLIB__.'/common'
	);
	$config = new class_config();
	$config->dest_mod = 'admin';		//重置模块名为OPHONE模块
	$api_obj = new class_api($config,$p_params);
    $api_obj->set_action('append_cdr');
	
	$cdrfile = isset($_REQUEST['CDR'])?$_REQUEST['CDR']:$_REQUEST['cdr'];
	$starttime = isset($_REQUEST['start'])?strtotime($_REQUEST['start']):time();
	$endtime = isset($_REQUEST['end'])?strtotime($_REQUEST['end']):time();
	$v = isset($_REQUEST['v'])?$_REQUEST['v']:'8';
	$cdr = @fopen($cdrfile,"r");
	if ($cdr) {
		$line = 0;
	    while (!feof($cdr)) {
	        $buffer = fgets($cdr);
	        if($buffer === FALSE)break;
	        $line += 1;
	        $buffer = str_replace('"','',$buffer);
	        $record = explode(',',$buffer);
	        
	        $c = new stdClass();
	        /*
			    accountcode: What account number to use: Asterisk billing account, (string, 20 characters)
			    src: Caller*ID number (string, 80 characters)
			    dst: Destination extension (string, 80 characters)
			    dcontext: Destination context (string, 80 characters)
			    clid: Caller*ID with text (80 characters)
			    channel: Channel used (80 characters)
			    dstchannel: Destination channel if appropriate (80 characters)
			    lastapp: Last application if appropriate (80 characters)
			    lastdata: Last application data (arguments) (80 characters)
			    start: Start of call (date/time)
			    answer: Answer of call (date/time)
			    end: End of call (date/time)
			    duration: Total time in system, in seconds (integer)
			    billsec: Total time call is up, in seconds (integer)
			    disposition: What happened to the call: ANSWERED, NO ANSWER, BUSY, FAILED
			    amaflags: What flags to use: see amaflags::DOCUMENTATION, BILL, IGNORE etc, specified on a per channel basis like accountcode. 
			*/
	        $index=0;
	        switch (intval($v)){
	        case 4:
	        	/*
  [0]=>string(13) "1485201000045"
  [1]=>string(13) "0085266710348"
  [2]=>string(5) "begin"
  [3]=>string(12) "callback-out"
  [4]=>string(29) "0085266710348 <0085266710348>"
  [5]=>string(27) "SIP/202.130.158.90-08db8670"
  [6]=>string(27) "SIP/202.130.158.90-08dbef60"
  [7]=>string(4) "Dial"
  [8]=>string(62) "SIP/202.130.158.90/85202085221239177|55|L(5520000:60000:60000)"
  [9]=>string(19) "2010-05-20 06:14:55"
  [10]=>string(19) "2010-05-20 06:15:18"
  [11]=>string(19) "2010-05-20 06:15:23"
  [12]=>string(2) "28"
  [13]=>string(1) "5"
  [14]=>string(8) "ANSWERED"
  [15]=>string(13) "DOCUMENTATION"
  [16]=>string(12) "1274336087.4"
  [17]=>string(116) "Ophone&var_sourceip=123.136.10.22&var_caller=202.130.158.90/85201085266710348&call=202.130.158.90/85202085221239177
"				*/
	        	var_dump($record);
	        	$c->accountcode = $record[$index++];
		        $c->src = $record[$index++];
		        $c->dst = $record[$index++];
		        $c->dcontext = $record[$index++];
		        $c->clid = $record[$index++];
		        $c->dstchannel = $record[$index++];
		        $index++;
		        $c->lastapp = $record[$index++];
		        $c->lastdata = $record[$index++];
		        //$c->callee = $record[$index++];
		        //$c->timeout = $record[$index++];
		        $cdrstart = strtotime($record[$index++])+8*60*60;
		        $c->start = $cdrstart;
		        $c->answer = strtotime($record[$index++])+8*60*60;
		        $c->end = strtotime($record[$index++])+8*60*60;
		        $c->duration = intval($record[$index++]);
		        $c->billsec = intval($record[$index++]);
		        $c->disposition = $record[$index++];
		        $c->amaflags = $record[$index++];
		        $c->sessionid = $record[$index++];
		        $cdrfield = api_string_to_array($record[$index++],'&','=');
		        if(count($cdrfield)>0){
		        	$prefixes = array(
		        		'852010' => '00',
		        		'852020' => '00'
		        	);
		        	if(isset($cdrfield['call'])){
		        		//B路
		        		$c->cb_mode = 1;
			        	$c->dst = $cdrfield['call'];
			        	foreach ($prefixes as $pk=>$pv){
			        		$c->dst = str_replace($pk,$pv,$c->dst);
			        	}
		        	}else{
		        		//A路
		        		$c->cb_mode = 0;
			        	$c->dst = $cdrfield['var_caller'];
			        	foreach ($prefixes as $pk=>$pv){
			        		$c->dst = str_replace($pk,$pv,$c->dst);
			        	}
		        	}
		        }else{
		        	continue;
		        }
	        	break;
	        case 8:
	        default:
		        $c->accountcode = $record[$index++];
		        $c->src = $record[$index++];
		        $c->dst = $record[$index++];
		        $c->dcontext = $record[$index++];
		        $c->clid = $record[$index++];
		        $c->dstchannel = $record[$index++];
		        $c->lastapp = $record[$index++];
		        $c->lastdata = $record[$index++];
		        $c->callee = $record[$index++];
		        $c->timeout = $record[$index++];
		        $cdrstart = strtotime($record[$index++])+8*60*60;
		        $c->start = $cdrstart;
		        $c->answer = strtotime($record[$index++])+8*60*60;
		        $c->end = strtotime($record[$index++])+8*60*60;
		        $c->duration = intval($record[$index++]);
		        $c->billsec = intval($record[$index++]);
		        $c->disposition = $record[$index++];
		        $c->amaflags = $record[$index++];
		        $c->sessionid = $record[$index++];
	        	break;
	        }
	        
	        //echo json_encode($c);
	        //if($line>100)break;
	        //echo get_request_ipaddr();
	        //echo sprintf("cdrstart = %s,start = %s, end = %s<br>",
	        //	date('Y-m-d H:s:i',$cdrstart),date('Y-m-d H:s:i',$starttime),date('Y-m-d H:s:i',$endtime));
	        //flush();
	        if($cdrstart > $starttime && $cdrstart <$endtime)
	        {
	        	echo sprintf("<hr>Line %d<br>",$line);
	        	if(isset($c->cb_mode)){
					switch ($c->cb_mode){
						case 0:
							//var_dump($c);
							if(send_cdr_a($api_obj,$c) === false)
		        				break;
							break;
						case 1:
							//var_dump($c);
						default:
							if(send_cdr_b($api_obj,$c) === false)
		        				break;
							break;  		
					}
	        	}else{
		        	if(send_cdr($api_obj,$c) === false)
		        		break;
	        	}
		        sleep(2);
	        }
	        flush();
	    }
	    fclose($cdr);
	}else{
		echo sprintf("Open file error :%s\n",$cdrfile);
		return;
	}
	
} catch ( Exception $e ) {
	echo sprintf("\r\n<UTONE><R>0</R><M>服务器异常：%s</M></UTONE>",$e->getMessage ());
}

function send_cdr($api_obj,$c)
{
	require_once __EZLIB__.'/common/api_billing_mssql.php';
	require_once __EZLIB__.'/common/api_billing_pgdb.php';

	$caller = $c->src;
	$callee = $c->dst;
	$callee_array = explode('/',$c->callee);
	$calleeip = $callee_array[1];
	
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
	}
	
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	$route_db = new class_billing_pgdb($api_obj->config->route_db_config, $api_obj );
	//取得默认的前缀，如果主叫号码前不带0，说明主叫号码省略了前缀，这里需要把前缀加上
	$def_prefix = isset($api_obj->config->default_ccode)?$api_obj->config->default_ccode:'0086';
	//把主被叫调整为全国码的电话号码
	$callee_s = check_phone_number ( $callee_prefix['callee'], $def_prefix);
	
	//首先要做号码替换，调用相关数据的ez_utils_db.sp_build_prefix把号码变换为带地区和运营商标识的号码：国码+运营商+省份代码+区号+'-'+号码
	//经过变换后的主被叫号码为：
	//     特殊前缀+':'+国码+':'+运营商代码+省市代码+':'+区号+'：'+电话号码 ，如：A201:86:UGD:756:15602530665
	$caller_b = $caller;//$route_db->phone_build_prefix ($caller_s);
	$callee_b = $route_db->phone_build_prefix ($callee_s);
	
	$cp = $callee_prefix ['prefix'] == '' ? '' : $callee_prefix ['prefix'].':';
	$caller_b = $caller;
	$callee_b = 'G' . ($callee_b == $callee_s ? $cp.$callee_b : $callee_prefix ['prefix'].':'.$callee_b);
	
	$caller=$caller_b;
	$callee=$callee_b;
	$params = array(
		'server' => '202.134.80.109'
		,'pin' => $c->accountcode
		,'sessionid' => $c->sessionid
		,'callerip' => get_request_ipaddr()
		,'caller' => $caller
		,'callee' => $callee
		,'calleeip' => $calleeip
		,'start' => date('Y-m-d H:s:i',$c->start)
		,'answer' => date('Y-m-d H:s:i',$c->answer)
		,'end' => date('Y-m-d H:s:i',$c->end)
		,'billsec' => $c->billsec
		);
	echo 'CDR: '.array_to_string(',',$params).'<br>';
	flush();
	$r = $billing_db->billing_update_cdr($params);
	$api_obj->push_return_data('cdr',$params);
	if(is_array($r)){
		echo 'RADIUS:'.array_to_string(',',$r).'<br>';
		return $api_obj->return_code;
	}else{
		return -1000;
	}
}

function send_cdr_a($api_obj,$c)
{
	require_once __EZLIB__.'/common/api_billing_mssql.php';
	require_once __EZLIB__.'/common/api_billing_pgdb.php';

	$caller = $c->src;
	$callee = $c->dst;
	$callee_array = explode('/',$c->callee);
	$calleeip = $callee_array[1];
	
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
	}
	
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	$route_db = new class_billing_pgdb($api_obj->config->route_db_config, $api_obj );
	//取得默认的前缀，如果主叫号码前不带0，说明主叫号码省略了前缀，这里需要把前缀加上
	$def_prefix = isset($api_obj->config->default_ccode)?$api_obj->config->default_ccode:'0086';
	//把主被叫调整为全国码的电话号码
	$callee_s = check_phone_number ( $callee_prefix['callee'], $def_prefix);
	
	//首先要做号码替换，调用相关数据的ez_utils_db.sp_build_prefix把号码变换为带地区和运营商标识的号码：国码+运营商+省份代码+区号+'-'+号码
	//经过变换后的主被叫号码为：
	//     特殊前缀+':'+国码+':'+运营商代码+省市代码+':'+区号+'：'+电话号码 ，如：A201:86:UGD:756:15602530665
	$caller_b = $caller;//$route_db->phone_build_prefix ($caller_s);
	$callee_b = $route_db->phone_build_prefix ($callee_s);
	
	$cp = $callee_prefix ['prefix'] == '' ? '' : $callee_prefix ['prefix'].':';
	$caller_b = $caller;
	$callee_b = 'A' . ($callee_b == $callee_s ? $cp.$callee_b : $callee_prefix ['prefix'].':'.$callee_b);
	
	$caller=$caller_b;
	$callee=$callee_b;
	$params = array(
		'server' => '202.134.80.109'
		,'pin' => $c->accountcode
		,'sessionid' => $c->sessionid
		,'callerip' => get_request_ipaddr()
		,'caller' => $caller
		,'callee' => $callee
		,'calleeip' => $calleeip
		,'start' => date('Y-m-d H:s:i',$c->start)
		,'answer' => date('Y-m-d H:s:i',$c->answer)
		,'end' => date('Y-m-d H:s:i',$c->end)
		,'billsec' => $c->billsec
		);
	echo 'CDR: '.array_to_string(',',$params).'<br>';
	flush();
	$r = $billing_db->billing_update_cdr($params);
	$api_obj->push_return_data('cdr',$params);
	if(is_array($r)){
		echo 'RADIUS:'.array_to_string(',',$r).'<br>';
		return $api_obj->return_code;
	}else{
		return -1000;
	}
}

function send_cdr_b($api_obj,$c)
{
	require_once __EZLIB__.'/common/api_billing_mssql.php';
	require_once __EZLIB__.'/common/api_billing_pgdb.php';

	$caller = $c->src;
	$callee = $c->dst;
	$callee_array = explode('/',$c->callee);
	$calleeip = $callee_array[1];
	
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
	}
	
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	$route_db = new class_billing_pgdb($api_obj->config->route_db_config, $api_obj );
	//取得默认的前缀，如果主叫号码前不带0，说明主叫号码省略了前缀，这里需要把前缀加上
	$def_prefix = isset($api_obj->config->default_ccode)?$api_obj->config->default_ccode:'0086';
	//把主被叫调整为全国码的电话号码
	$callee_s = check_phone_number ( $callee_prefix['callee'], $def_prefix);
	
	//首先要做号码替换，调用相关数据的ez_utils_db.sp_build_prefix把号码变换为带地区和运营商标识的号码：国码+运营商+省份代码+区号+'-'+号码
	//经过变换后的主被叫号码为：
	//     特殊前缀+':'+国码+':'+运营商代码+省市代码+':'+区号+'：'+电话号码 ，如：A201:86:UGD:756:15602530665
	$caller_b = $caller;//$route_db->phone_build_prefix ($caller_s);
	$callee_b = $route_db->phone_build_prefix ($callee_s);
	
	$cp = $callee_prefix ['prefix'] == '' ? '' : $callee_prefix ['prefix'].':';
	$caller_b = $caller;
	$callee_b = 'B' . ($callee_b == $callee_s ? $cp.$callee_b : $callee_prefix ['prefix'].':'.$callee_b);
	
	$caller=$caller_b;
	$callee=$callee_b;
	$params = array(
		'server' => '202.134.80.109'
		,'pin' => $c->accountcode
		,'sessionid' => $c->sessionid
		,'callerip' => get_request_ipaddr()
		,'caller' => $caller
		,'callee' => $callee
		,'calleeip' => $calleeip
		,'start' => date('Y-m-d H:s:i',$c->start)
		,'answer' => date('Y-m-d H:s:i',$c->answer)
		,'end' => date('Y-m-d H:s:i',$c->end)
		,'billsec' => $c->billsec
		);
	echo 'CDR: '.array_to_string(',',$params).'<br>';
	flush();
	$r = $billing_db->billing_update_cdr($params);
	$api_obj->push_return_data('cdr',$params);
	if(is_array($r)){
		echo 'RADIUS:'.array_to_string(',',$r).'<br>';
		return $api_obj->return_code;
	}else{
		return -1000;
	}
}

?>
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

/*
 * 请求MD5校验
*/
function request_check_md5($key,$crc)
{
	$s = '';
	foreach ($_REQUEST as $k => $v){
		if(strtolower($k) != 'crc' && strtolower($k) !='fmd5' )
			$s .= $v;
	}
	//$ip = get_request_ipaddr();
	$md5 = md5($s.$key);
	return $md5 === $crc;
}

/*
 * 写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
 */
function write_response_callback($api_obj, $context) {
	$api_obj->md5_key = '';//表示输出内容不加密
	$r = $api_obj->return_data['result'];
	switch (strtolower($api_obj->return_data['type'])){
	case 'kv':
		foreach ($r as $k => $v)
			echo sprintf("\r\n%s=%s",$k,$v);
		echo "\r\n";
		break;
	case 'xml':
		echo $api_obj->json_to_xml($r);
		break;
	case 'json':
		break;
	default :
		echo sprintf("%s",json_encode($r));
		break;		
	}
}

$r = new stdClass();
$r->success = FALSE;
$r->message = '';
$a = isset($_REQUEST['a'])?$_REQUEST['a']:'update';
try{
	$v_lang = $_REQUEST['lang'];
	if (empty($v_lang) or $v_lang == '*#0086#' or substr($v_lang, 0, 1) == '*')
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
		'api_action'=> $a,
		'api_params'=>array('action'=> $a),
		'common-lang-path'=> __EZLIB__.'',
		'lang-path' => __EZLIB__.'/httpapi',
		'common-path'=> __EZLIB__.'/common'
	);
    //var_dump($p_params);
	$config = new class_config();
	$config->dest_mod = 'httpapi';		//重置模块名为665API模块
	$api_object = new class_api($config,$p_params);
	$api_object->set_action($a);
	$api_object->set_callback_func ( null, write_response_callback, $api_object );
	$crc = isset($_REQUEST['Fmd5'])?$_REQUEST['Fmd5']:$_REQUEST['crc'];
	if(!request_check_md5('ophone',$crc)){
		$r->success = 'false';
		$r->message = sprintf('CRC error,not allow request from %s.',get_request_ipaddr());
		$api_object->push_return_data('result',$r);
	}
	if(isset($api_object->config->allow_ips)){
		$ip = get_request_ipaddr();
		foreach ($api_object->config->allow_ips as $aip => $desc){
			if($ip == $aip){
				list($pin,$pass) = explode(',',$desc);
				$r = do_action($api_object,$a,$r,$pin,$pass);
// 				foreach ($r as $k=>$v){
// 					echo sprintf("%s=%s\r\n",$k,$v);
// 				}
				$api_object->push_return_data('result',$r);
				$api_object->write_response();
				return;
			}
		}
	}
	if(isset($_REQUEST['pin']) && isset($_REQUEST['pass'])){
		//request_check_md5();
		$r = do_action($api_object,$a,$r,$pin,$pass);
		$api_object->push_return_data('result',$r);
	}else{
		$r->success = 'false';
		$r->message = sprintf('not allow request from %s.',get_request_ipaddr());
		$api_object->push_return_data('result',$r);
	}		
	
} catch ( Exception $e ) {
	$r->message = $e->getMessage ();
}
$api_object->write_response();

/*
Webid utone分配的上海公司方编号
Linkid  上海公司提供的本次呼叫请求的唯一流水号（10到20位等长数字，由上海公司方确定）
CallingNo  主叫（固话加区号，手机加0或不加0）
CalledNo 被叫（固话加区号，手机加0或不加0）
CallTime 本次可通话时长（分钟）  当通话时长<  或 = 0，回拨系统仅仅呼叫主叫并播放余额不足的提示
Fmd5 32位长大写md5(Webid+Linkid+CallingNo+CalledNo+新加坡分配密钥32位长)
* */
function do_action($api_obj,$a,$r,$pin,$pass)
{
	$webid = isset($_REQUEST['Webid'])?$_REQUEST['Webid']:$_REQUEST['webid'];
	$linkid = isset($_REQUEST['Linkid'])?$_REQUEST['Linkid']:$_REQUEST['linkid'];
	$caller = isset($_REQUEST['CallingNo'])?$_REQUEST['CallingNo']:$_REQUEST['caller'];
	$callee = isset($_REQUEST['CalledNo'])?$_REQUEST['CalledNo']:$_REQUEST['callee'];
	$limit = isset($_REQUEST['CallTime'])?intval($_REQUEST['CallTime']):(isset($_REQUEST['maxtime'])?intval($_REQUEST['maxtime']):0);
	$cdrurl = $_REQUEST['cdrurl'];
	if(empty($caller) || empty($callee))
	{
		$r->success = 'false';
		$r->response = -101;
		$r->message = '没有提供主叫号码和被叫号码';
		return $r;
	}
	return ac_callback($api_obj,$r,$pin,$pass,$caller,get_request_ipaddr(),$callee,array(
		'extras' =>array(
			'limit_time' => $limit,
			'cdrurl' => $cdrurl,
			'linkid' => $linkid
		)
		,'sync' => isset($_REQUEST['sync'])?$_REQUEST['sync']:'false'
	));
}

function ac_callback($api_obj,$r,$pin,$pass,$caller,$callerip,$callee,$params)
{
	$def_prefix = isset($api_obj->config->default_prefix)?$api_obj->config->default_prefix:'0086';
	$pno = check_phone_number($caller,$def_prefix);
	$params += array(
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
	//var_dump($params);
	//echo sprintf("Sync=%s,[%s]",$params->sync,);
	api_callback($api_obj,$params,$params['sync']);
	if($api_obj->return_code > 0)
	{
		$r->response = $api_obj->return_code;
	}else{
		$r->response = $api_obj->return_code;
		switch($r->response)
		{
			case -151:
			case -152:
			case -153:
				break;
			case -150:
			default:
				break;
		}
	}
	$api_obj->no_log = FALSE;
	if($r->response > 0)
		$r->success = 'true';
	else 
		$r->success = 'false';
	return $r;	
}

?>

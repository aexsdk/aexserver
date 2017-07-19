<?php
error_reporting(E_ALL ^ E_NOTICE);//ignore the "notice" error

require_once (dirname(__FILE__).'/config/api_config.php');
require_once (__EZLIB__.'/common/api_common.php');				
require_once (__EZLIB__.'/common/api_radius_funcs.php');				
require_once (__EZLIB__.'/common/api_log_class.php');				

require_once __EZLIB__.'/common/api_billing_mssql.php';
require_once __EZLIB__.'/common/api_billing_pgdb.php';
require_once __EZLIB__.'/common/api_ivr.php';

$config = new class_config();
//获取基本参数
$p_params = array(
	'run_start_time'=>microtime(),
	'gprs_caller'=>$_SERVER['HTTP_X_UP_CALLING_LINE_ID'],
	'gprs_agent'=>$_SERVER['HTTP_USER_AGENT'],
	'api_version' => $_REQUEST['v'],
	'api_o' =>$_REQUEST['o'],
	'api_lang' => $_REQUEST['lang'],
	'api_p' => $_REQUEST['p'],
	'api_action'=> $_REQUEST['a'],
	'api_params'=>array('action'=> $_REQUEST['a']),
	'common-lang-path'=> __EZLIB__.'',
	'lang-path' => __EZLIB__,
	'common-path'=> __EZLIB__.'/common',
	'config' => $config
);
//var_dump($p_params);
$config->dest_mod = 'IVR_API';		//重置模块名为OPHONE模块
$api_object = new class_api($config,$p_params);
$api_object->no_log = TRUE;
//IP Dialer不需要加密
$api_object->md5_key = '';
//$action = $api_object->decode_param($api_object->get_md5_key());

$request_ip = $_SERVER['REMOTE_ADDR'];
/*判断IP是否是允许的IP*/

/*根据action做回应*/
$r = array(
	"temp" => 'temp',		//由于未知原因，第一个变量没有被设置成功
	"action" => $_REQUEST['a'],
	"r_code" => 0,
	'r_answer' => 0
);

$r['r_fnpath'] = dirname(dirname(__FILE__))."/monitor";

$caller = $_REQUEST['caller'];
if(substr($caller,0,2) == '86')
	$caller = "00".$caller;
$callerip = $_REQUEST['callerip'];
$accessno = $_REQUEST['accessno'];
switch($api_object->params['api_action'])
{
	case 'action':
		$r = ga_action($api_object,$r,$accessno,$caller,$callerip);
		break;
	case 'active':
		$r['action'] = 'active';
		$pass = $_REQUEST['pass'];
		$cfmpass = $_REQUEST['cfmpass'];
		$r = ga_active($api_object,$r,$accessno,$caller,$callerip,$pass,$cfmpass);
		break;
	case 'cb_invite':
		$r['action'] = 'cb_invite';
		$r['r_code'] = 1;
		$callee = $_REQUEST['callee'];
		$r['r_radius'] = $api_object->config->rad_acct;
		$r = ga_cb_invite($api_object,$r,$accessno,$caller,$callerip,$callee);
		break;
	case 'callback':
		$r['action'] = 'callback';
		$r['r_code'] = 1;
		$callee = $_REQUEST['callee'];
		$r['r_radius'] = $api_object->config->rad_acct;
		$r = ga_callback($api_object,$r,$accessno,$caller,$callerip,$callee);
		break;
	case 'invite':
		$r['action'] = 'invite';
		$r['r_code'] = 1;
		$callee = $_REQUEST['callee'];
		$r['r_radius'] = $api_object->config->rad_acct;
		$r = ga_invite($api_object,$r,$accessno,$caller,$callerip,$callee);
		break;
	case 'route':
		$r['action'] = 'route';
		$r['r_code'] = 1;
		$callee = $_REQUEST['callee'];
		$r['r_radius'] = $api_object->config->rad_acct;
		$r = ga_route($api_object,$r,$accessno,$caller,$callerip,$callee);
		break;
	case 'query':
		$r['r_code'] = -1;
		$r["r_file"] = 'ezprepaid/endpoint-or-password-incorrect';
		$r = ga_query($api_object,$r,$accessno,$caller,$callerip);
		break;
	case 'bind':
		$r['r_code'] = 1;
		$pin = $_REQUEST['pin'];
		$pass = $_REQUEST['pass'];
		$r = ga_bind($api_object,$r,$accessno,$caller,$callerip,$pin,$pass);
		break;
	case 'recharge':
		$r['r_code'] = 1;
		$pin = $_REQUEST['pin'];
		$pass = $_REQUEST['pass'];
		$r = ga_recharge($api_object,$r,$accessno,$caller,$callerip,$pin,$pass);
		break;
	case 'modify_pass':
		$r['r_code'] = 1;
		$pass = $_REQUEST['pass'];
		$newpass = $_REQUEST['newpass'];
		$cfmpass = $_REQUEST['cfmpass'];
		$r = ga_modify_pass($api_object,$r,$accessno,$caller,$callerip,$pass,$newpass,$cfmpass);
		break;
	case 'get_cc_number':
		$r['r_code'] = 1;
		$r['action'] = 'invite';
		$callee = $_REQUEST['callee'];
		$r['callee'] = substr($callee,1);//'18666973405';
		$r['r_radius'] = $api_object->config->rad_acct;
		$r = ga_invite($api_object,$r,$accessno,$caller,$callerip,$r['callee']);
		break;
	default:
		$r['r_code'] = -1;
		$r['r_file'] = sprintf('%1$s/system-error',$api_object->config->ivr_path);
		break;
}
echo array_to_string(",",$r);

function ga_action($api_obj,$r,$accessno,$caller,$callerip)
{
	switch ($accessno)
	{
		case "866628881590":
			$r['action'] = 'funcs';
			$r['r_code'] = 1;
			$r['r_file'] = sprintf('%1$s/funcs_query_1&%1$s/funcs_recharge_2&%1$s/funcs_bind_3&%1$s/funcs_modify_pass_4&%1$s/funcs_callback&%1$s/funcs_9',$api_obj->config->ivr_path);
			break;
		case "02066813480":
		case "02066813481":
			$r['action'] = 'invite';
			$r['r_code'] = 1;
			$r['r_answer'] = 1;
			break;
		case "02066813482":
		case "02066813484":
			$r['action'] = 'funcs';
			$r['r_code'] = 1;
			$r['r_answer'] = 1;
			$r['r_file'] = sprintf('%1$s/funcs_query_1&%1$s/funcs_recharge_2&%1$s/funcs_bind_3&%1$s/funcs_modify_pass_4&%1$s/funcs_callback&%1$s/funcs_9',$api_obj->config->ivr_path);
			break;
		default:
			$r['action'] = 'funcs';
			$r['r_code'] = 1;
			$r['r_file'] = sprintf('%1$s/funcs_query_1&%1$s/funcs_recharge_2&%1$s/funcs_bind_3&%1$s/funcs_modify_pass_4&%1$s/funcs_callback&%1$s/funcs_9',$api_obj->config->ivr_path);
			break;
	}	
	return $r;
}

?>

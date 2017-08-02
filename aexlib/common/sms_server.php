<?php

//require_once (__EZLIB__.'/libary/pnews/langconvert.inc.php');

define ( sms_user_id, 'J00030' );
define ( sms_password, '147258' );

function send_sms($api_obj, $pno, $sno, $msg) {
	if (! is_array ( $api_obj->config->sms_account ))
		return 'no_sms_account';
	$sms_type = isset ( $api_obj->config->sms_account ['type'] ) ? $api_obj->config->sms_account ['type'] : 1;
	$sms_url = $api_obj->config->sms_account ['url'];
	$sms_uid = $api_obj->config->sms_account ['uid'];
	$sms_pass = $api_obj->confg->sms_account ['pass'];
	$params = $api_obj->confg->sms_account;
	switch ($sms_type) {
		case 0 :
			return send_sms_0 ( $api_obj, $sms_url, $sms_uid, $sms_pass, $pno, $sno, $msg,$params );
			break;
		case 1 :
			return send_sms_1 ( $api_obj, $sms_url, $sms_uid, $sms_pass, $pno, $sno, $msg,$params );
			break;
		case 2 :
			return send_sms_2 ( $api_obj, $sms_url, $sms_uid, $sms_pass, $pno, $sno, $msg,$params );
			break;
		case 3:
			return send_sms_3 ( $api_obj, $sms_url, $sms_uid, $sms_pass, $pno, $sno, $msg,$params );
			break;
		default :
			return send_sms_1 ( $api_obj, $sms_url, $sms_uid, $sms_pass, $pno, $sno, $msg,$params );
			break;
	}
}

/**
 * 多路由发送短信
 *
 * @param api_object $api_obj
 * @param string $pno		要发送的手机号码
 * @param string $sno		扩展子号码
 * @param string $msg		短消息内容，UTF8编码
 */
function send_sms_queue($api_obj, $pno, $sno, $msg)
{
	if(isset($api_obj->config->sms_route) && is_array($api_obj->config->sms_route))
	{
		$result = FALSE;
		$log = '';
		foreach ($api_obj->config->sms_route as $k=>$r){
			if(!is_array($r))continue;
			$sms_url = $r ['url'];
			$sms_uid = $r ['uid'];
			$sms_pass = $r ['pass'];
			$params = $r;
			switch ($r['type']) {
				case 0 :
					$result = send_sms_0 ( $api_obj, $sms_url, $sms_uid, $sms_pass, $pno, $sno, $msg,$params );
					break;
				case 1 :
					$result = send_sms_1 ( $api_obj, $sms_url, $sms_uid, $sms_pass, $pno, $sno, $msg,$params );
					break;
				case 2 :
					$result = send_sms_2 ( $api_obj, $sms_url, $sms_uid, $sms_pass, $pno, $sno, $msg,$params );
					break;
				case 3:
					$result = send_sms_3 ( $api_obj, $sms_url, $sms_uid, $sms_pass, $pno, $sno, $msg,$params );
					break;
				default :
					$result = FALSE;
					break;
			}	
			$log .= sprintf("%s => [%d]%s\r\n",$k,$result,$api_obj->return_data['sms_return']);
			if($result){
				$api_obj->push_return_data('sms_route',$log);
				return $result;
			}		
		}
		$api_obj->push_return_data('sms_route',$log);
		return $result;
	}else{
		return FALSE;
	}
}

/**
 * 多路由发送短信
 *
 * @param api_object $api_obj
 * @param string $pno		要发送的手机号码
 * @param string $sno		扩展子号码
 * @param string $msg		短消息内容，UTF8编码
 * @param string $rk		路由类型
 */
function send_sms_route($api_obj, $pno, $sno, $msg,$rk)
{
	if(isset($api_obj->config->sms_route) && is_array($api_obj->config->sms_route))
	{
			$r = $api_obj->config->sms_route[$rk];
			if(!is_array($r))
				return send_sms_queue($api_obj,$pno,$sno,$msg);
			$sms_url = $r ['url'];
			$sms_uid = $r ['uid'];
			$sms_pass = $r ['pass'];
			$params = $r;
			switch ($r['type']) {
				case 0 :
					$result = send_sms_0 ( $api_obj, $sms_url, $sms_uid, $sms_pass, $pno, $sno, $msg,$params );
					break;
				case 1 :
					$result = send_sms_1 ( $api_obj, $sms_url, $sms_uid, $sms_pass, $pno, $sno, $msg,$params );
					break;
				case 2 :
					$result = send_sms_2 ( $api_obj, $sms_url, $sms_uid, $sms_pass, $pno, $sno, $msg,$params );
					break;
				case 3:
					$result = send_sms_3 ( $api_obj, $sms_url, $sms_uid, $sms_pass, $pno, $sno, $msg,$params );
					break;
				default :
					break;
			}	
			$api_obj->push_return_data('sms_route',$rk);
			return $result;
	}else{
		return FALSE;
	}
}

function send_sms_0($api_obj, $smsurl, $uid, $pass, $pno, $sno,$msg,$params) {
	$msg_gb = mb_convert_encoding ( $msg, 'GB2312', "UTF-8" );
	$result = $api_obj->get_from_api ( $smsurl, array ('method' => 'sendOneSms', 'uid' => $uid, 'pwd' => md5 ( $pass ), 'mobile' => $pno, 'rawtxt' => base64_encode ( $msg_gb ) ) );
	$state = substr_compare(trim($result),"OK",0,2) == 0;
	$api_obj->push_return_data('sms_return',$result);
	return $state;
}

function send_sms_1($api_obj, $smsurl, $uid, $pass, $pno, $sno,$msg,$params) {
	require_once 'nusoap.php';
	//require_once (__EZLIB__.'/common/nusoap/lib/nusoap.php');
	if (is_array ( $pno )) {
		$pno_count = count ( $pno );
		$pno_str = join ( ",", $pno );
	} else {
		$pno_count = 1;
		$pno_str = $pno;
	}
	if (empty ( $sno ))
		$sno = "*";
	$client = new nusoap_client ($smsurl);// 'http://ws.montnets.com:9002/MWGate/wmgw.asmx?wsdl' ); //$smsurl);
	$err = $client->getError ();
	if ($err) {
		$api_obj->push_return_data('sms_return',$err);
		return FALSE;//return sprintf ( 'send_sms_error：%s', $err );
	}
	$param = array ('userId' => $uid, 'password' => $pass, 'pszMobis' => $pno_str, 'pszMsg' => $msg, 'iMobiCount' => $pno_count, 'pszSubPort' => $sno );
	$result = $client->call ( 'MongateCsSpSendSmsNew', $param );
	$api_obj->push_return_data('sms_return',$result);
	if ($client->fault) {
		$api_obj->push_return_data('sms_return',$client->getError ());
		return FALSE;//sprintf ( 'send_sms_error：%d', - 1 );
	} else {
		$err = $client->getError ();
		if ($err) {
			//return sprintf ( 'send_sms_error：%s', $err );
			$api_obj->push_return_data('sms_return',$err);
			return FALSE;
		} else {
			//return sprintf ( 'sms_return：%s', $result );
			//2011-06-22,02:02:00,7712462742587757303,1065712039020054,2,DB:0140
			$sms_return = TRUE;
			sleep(3);
			$sr = $client->call ( 'MongateCsGetStatusReportExEx', $param );
			if(is_array($sr)){
				foreach ($sr as $s){
					if(is_array($s)){				
						list($sms_d,$sms_t,$sms_code,$sms_caller,$sms_resp,$sms_result) = explode(',',join(',',$s));
					}else{
						list($sms_d,$sms_t,$sms_code,$sms_caller,$sms_resp,$sms_result) = explode(',',$s);
					}
					if(trim($sms_code) == trim($result)){
						if($sms_result != 'DELIVRD'){
							$api_obj->push_return_data('other_sms_status',sprintf("%s\r\n梦网短信发送状态:%s %s=>%s[%s]",
								$api_obj->return_data['other_sms_status'],$sms_d,$sms_t,$api_obj->get_error_message($sms_result,"未知状态"),$sms_result));
							$sms_return = FALSE;
						}else{ 
							$api_obj->push_return_data('sms_return',sprintf('短信发送状态：%s %s=>%s[%s]',$sms_d,$sms_t,$api_obj->get_error_message($sms_result,"未知状态"),$sms_result));
							$sms_return = TRUE;
						}				
					}else{
						$api_obj->push_return_data('other_sms_status',sprintf("%s\r\n短信%s的发送状态:%s %s=>%s[%s]",
							$api_obj->return_data['other_sms_status'],$sms_code,$sms_d,$sms_t,$api_obj->get_error_message($sms_result,"未知状态"),$sms_result));
					}
				}
			}
			return $sms_return;
		}
	}
}

function send_sms_2($api_obj, $smsurl, $uid, $pass, $pno, $sno,$msg,$params) {
	require_once (__EZLIB__.'/common/nusoap/lib/nusoap.php');
	if (is_array ( $pno )) {
		//$pno_count = count($pno);
		$pno_str = join ( ",", $pno );
	} else {
		//$pno_count = 1;
		$pno_str = $pno;
	}
	if (empty ( $sno ))
		$sno = "*";

	$client = new nusoapclient($smsurl,true);//  );
//	$err = $client->getError ();
//	if ($err) {
//		$api_obj->push_return_data('sms_return',$err);
//		return FALSE;
//	}
	$client->soap_defencoding = 'GB2312';
//	$client->decode_utf8 = false;
		//$sn='SDK-BBX-010-XXXXX';
		//$password='XXXXX';
	$pwd = strtoupper ( md5 ( $uid . $pass ) );
	$mobile = $pno_str; //手机号
	$content = $msg;//mb_convert_encoding ( $msg, 'gb2312', 'utf-8' ); //内容
	$ext = $sno; //扩展码，可为空
	$stime = ''; //定时时间,可为空
	$rrid = ''; //唯一标志码，如果为空，将返回系统生成的标志码
	$parameters = array ("sn" => $uid, "pwd" => $pwd, "mobile" => $mobile, "content" => $content, "ext" => $ext, "stime" => $stime, "rrid" => $rrid );
	$result = $client->Call ( 'mt',array('parameters' =>$parameters), '', '', false, true,'document','encoded' );
	if ($client->fault) {
		$api_obj->push_return_data('sms_return',$client->getError ());
		return FALSE;//sprintf ( 'send_sms_error：%d', - 1 );
	} else {
		$err = $client->getError ();
		if ($err) {
			//return sprintf ( 'send_sms_error：%s', $err );
			$api_obj->push_return_data('sms_return',$err);
			return FALSE;
		} else {
			//return sprintf ( 'sms_return：%s', $result );
			//2011-06-22,02:02:00,7712462742587757303,1065712039020054,2,DB:0140
			$api_obj->push_return_data('sms_return',array_to_string(',',$result));
			$sms_return = TRUE;
			//mtResponse
			$sr = $client->Call ( 'mtResponse');
			$api_obj->return_data['sms_return'] .= sprintf("\r\nmtResponse=%s\r\n",json_encode($sr));
			return $sms_return;
		}
	}
}

function sms_status($api_obj,$uid, $pass) {
	$sms_url = $api_obj->config->sms_account ['url'];
	$client = new nusoap_client( 'http://ws.montnets.com:9002/MWGate/wmgw.asmx?wsdl' );
	$err = $client->getError ();
	if ($err) {
		return $err;
	}
	$param = array ('userId' => $uid, 'password' => $pass );
	$result = $client->call ( 'MongateCsGetStatusReportExEx', $param );
	$api_obj->push_return_data('sms_status',$result);
	if ($client->fault) {
		return - 1;
	} else {
		$err = $client->getError ();
		if ($err) {
			return $err;
		} else {
			return $result;
		}
	}

}

function recive_sms($api_obj,$uid, $pass) {
	$sms_url = $api_obj->config->sms_account ['url'];
	$client = new nusoap_client($sms_url);// ( 'http://ws.montnets.com:9002/MWGate/wmgw.asmx?wsdl' );
	$err = $client->getError ();
	if ($err) {
		return $err;
	}
	$param = array ('userId' => $uid, 'password' => $pass );
	$result = $client->call ( 'MongateCsGetSmsExEx', $param );
	$api_obj->push_return_data('sms_recive',$result);
	if ($client->fault) {
		return - 1;
	} else {
		$err = $client->getError ();
		if ($err) {
			return $err;
		} else {
			return $result;
		}
	}

}

/*
<?xml version="1.0" encoding="utf-8"?>

<CSubmitState xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://tempuri.org/">

  <State>0</State>

  <MsgID>110615092235578</MsgID>

  <MsgState>审查</MsgState>

  <Reserve>999810000</Reserve>

</CSubmitState>*/
function send_sms_3($api_obj, $smsurl, $uid, $pass, $pno, $sno,$msg,$params){
	$data = array(
		'sname' => $params['sname'],
		'spwd' => $params['spwd'],
		'scorpid' => $params['scorpid'],
		'sprdid' => $params['sprdid'],
		'sdst' => $pno,
		'smsg' => $msg
	);
	
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $smsurl);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 35);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 35);
	curl_setopt($ch, CURLOPT_POSTFIELDS, array_to_string('&',$data));
	$result = curl_exec($ch);
	$api_obj->push_return_data('sms_return',$result);
	if($result == '')return 0;
	$r = preg_match_all( "/\<State\>(.*?)\<\/State\>/s", $result, $state );
	if($r > 0)
		return intval($state) >= 0;
	else
		return  FALSE;
}

?>

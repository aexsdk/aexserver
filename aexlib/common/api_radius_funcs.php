<?php

/* TODO: Add code here */

/*
 * Created on 2009_4-11
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
error_reporting(E_ALL ^ E_NOTICE);//ignore the "notice" error
require_once (dirname(__FILE__).'/api_radius_def.php');

if (! extension_loaded ( 'radius' ) && function_exists('dl')) {
	dl ( 'radius.' . PHP_SHLIB_SUFFIX );
}

/* 
	加载Radius请求函数
	返回值：数组
		$return_data['ERROR'] =  radius的错误字符串
		$return_data['RETURN-CODE'] 大于零表示成功，小于0表示失败
 * */
function radius_execute_proc($storedProc, $acctParams, $config) {
	$return_data = array('RETURN-CODE'=>-66);
	if (! is_array ( $acctParams )){
		$return_data['RETURN-CODE'] = -61;
		return $return_data; 			//parameters error
	}
	$radius = radius_auth_open ();
	
	if ($radius == FALSE){
		$return_data['RETURN-CODE'] = -62;
		return $return_data;; 			//open radius error
	}
	/*
	if(radius_config($radius,'/etc/radius.config'))
	{
	    echo 'radius_config:' . radius_strerror($radius). "\n<br />";
	    return -101;
	    radius_close($radius);
	    exit;
	}*/
	if (! radius_add_server ( $radius, $config->radserver, $config->authport, 
		$config->sharedsecret, $config->retryTimes, $config->timeout )) {
		//echo 'radius_add_server:' . radius_strerror ( $radius ) . "\n<br>";
		$return_data['ERROR'] =  'radius_add_server:' .radius_strerror ( $radius );
		radius_close ( $radius );
		$return_data['RETURN-CODE'] = -63;
		return $return_data;		//添加Radius服务器错误
	}
	if (! radius_create_request ( $radius, RADIUS_ACCESS_REQUEST )) {
		$return_data['ERROR'] =  'radius_create_request:' .radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -64;			//创建Radius请求失败
		return $return_data;
	}
	/*
	 * Next will fill radius request parameters
	 */
	if (! radius_put_string ( $radius, RADIUS_NAS_IDENTIFIER, NAS_CALLBACK . '-' . $_SERVER ['SERVER_NAME'] )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_addr ( $radius, RADIUS_NAS_IP_ADDRESS, $config->server_addr )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_int ( $radius, RADIUS_SERVICE_TYPE, RADIUS_ST_GETACCOUNT )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_addr ( $radius, RADIUS_FRAMED_IP_ADDRESS, get_request_ipaddr () )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_vendor_attr ( $radius, VENDOR_CISCO, h323_call_type, 'CLI' )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_string ( $radius, RADIUS_USER_NAME, $storedProc )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! write_radius_password ( $radius, RADIUS_CHAP_PASSWORD, 'radius' )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	
	reset ( $acctParams );
	while ( list ( $key, $val ) = each ( $acctParams ) ) {
		if (! radius_put_vendor_attr ( $radius, VENDOR_CISCO, Cisco_AVPair, $key . '=' . $val )){
			$return_data['ERROR'] =  radius_strerror ( $radius );
			$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
			return $return_data;
		}
	}
	/*
	 * Next will send radius request
	 */
	$response = radius_send_request ( $radius );
	/*
	 * Handle radius response
	 */
	//echo 'radius_send_request:';
	//var_dump($response);
	//echo '<br>';
	if ($response == false) {
		$return_data['ERROR'] =  radius_strerror ( $radius );
		radius_close ( $radius );
		$return_data['RETURN-CODE'] = -66;			//发送请求失败
		return $return_data;
	} else {
		switch ($response) {
			case RADIUS_ACCESS_ACCEPT :
				{
					$return_data['RADIUS_RESP'] = RADIUS_ACCESS_ACCEPT;
					$return_data['RETURN-CODE'] = 60;			//挑战
				}
				break;
			case RADIUS_ACCESS_REJECT :
				{
					//echo 'AccessRequest Reject<br>';
					//parse return value for report error code
					$return_data['RADIUS_RESP'] = RADIUS_ACCESS_REJECT;
					$return_data['RETURN-CODE'] = 61;			//挑战
				}
				break;
			case RADIUS_ACCESS_CHALLENGE :
				{
					radius_close ( $radius );
					$return_data['RADIUS_RESP'] = RADIUS_ACCESS_CHALLENGE;
					$return_data['RETURN-CODE'] = -69;			//挑战
					return $return_data;
				}
				break;
			default :
				//echo 'AccessRequest Timeout<br>';
				radius_close ( $radius );
				$return_data['RETURN-CODE'] = -68;			//超时
				return $return_data;
		}
		while ( $resa = radius_get_attr ( $radius ) ) {
			if (! is_array ( $resa )) {
				//printf ("Error getting attribute: %s\n",  radius_strerror($radius));
				radius_close ( $radius );
				$return_data['ERROR'] =  radius_strerror ( $radius );
				$return_data['RETURN-CODE'] = -67;
				return $return_data;
			}
			$attr = $resa ['attr'];
			$data = $resa ['data'];
			switch ($attr) {
				case RADIUS_VENDOR_SPECIFIC :
					if ($attr == RADIUS_VENDOR_SPECIFIC) {
						$return_data = array_merge ( $return_data, get_cisco_subattr ( $data ) );
					}
					break;
				case RADIUS_CALLING_STATION_ID :
					//modify caller number
					$return_data ['caller'] = radius_cvt_string ( $data );
					break;
				case RADIUS_USERNAME :
					//modify callee number
					$return_data ['pin'] = radius_cvt_string ( $data );
					break;
				case RADIUS_PASSWORD :
					$return_data ['password'] = radius_cvt_string ( $data );
					break;
				default :
					$return_data ["ATTR-$attr"] = radius_cvt_string ( $data );
					break;
			}
		}		//end while
		//echo '<br>' . join(',',$callbackParams) . '<br>';
		radius_close ( $radius );
		$r = $return_data['h323_return_code'];
		if(isset($r)){
			if($r >= 0){
				$r = $r + 100;			//返回值大于0把返回值调整为大于100的数
			}else{
				$r = $r - 100;			//返回值小于0把返回值调整为小于-100的数
			}
			$return_data['RETURN-CODE'] = $r;  //把数据库存储过程的返回值转换为函数返回值
		}
	}
	return $return_data;
}

/*
 * This function write a attrib to radius request and handle error.
 */
function write_radius_attr($res,$rtype,$attr)
{
	if($rtype == RADIUS_CHAP_PASSWORD)
	{
		mt_srand(time());
		$chall = mt_rand();
		$chapval = md5(pack('Ca*',1 , 'sepp' . $chall));
		$pass = pack('CH*', 1, $chapval);
		if (!radius_put_attr($res, RADIUS_CHAP_PASSWORD, $pass)) {
		   echo 'radius_put_attr:' . radius_strerror($res). "\n<br />";
		   write_return_param('response-code','-8');
		   exit;
		}
		if (!radius_put_attr($res, RADIUS_CHAP_CHALLENGE, $chall)) {
		   echo 'radius_put_attr:' . radius_strerror($res). "\n<br />";
		   write_return_param('response-code','-8');
		   exit;
		}
	}else{
		if (!radius_put_string($res, $rtype, $attr)) {
		   echo 'radius_put_attr:' . radius_strerror($res). "\n<br />";
		   write_return_param('response-code','-8');
		   exit;
		}
	}
}

function write_radius_password($res,$rtype,$attr)
{
	if($rtype == RADIUS_CHAP_PASSWORD)
	{
		mt_srand(time());
		$chall = sprintf("%s",mt_rand());
		$chapval = md5(pack('Ca*',1,sprintf("%s%s",$attr,$chall)));
		//echo sprintf("<hr>%s %s<hr>",$attr,$chall);
		$pass = pack('CH*',1,$chapval);
		//echo sprintf("<hr>%s<hr>%s<hr>",base64_encode($pass),base64_encode($chall));
		if (!radius_put_attr($res, RADIUS_CHAP_PASSWORD, $pass)) {
		   return FALSE;
		}
		if (!radius_put_string($res, RADIUS_CHAP_CHALLENGE, $chall)) {
		   return FALSE;
		}
	}
	return TRUE;
}

function get_request_ipaddr()
{
	if($_SERVER['HTTP_CLIENT_IP']){
	    $onlineip=$_SERVER['HTTP_CLIENT_IP'];
	}elseif($_SERVER['HTTP_X_FORWARDED_FOR']){
	    $onlineip=$_SERVER['HTTP_X_FORWARDED_FOR'];
	}elseif($_SERVER['REMOTE_ADDR']){
	    $onlineip=$_SERVER['REMOTE_ADDR'];
	}else{
		$onlineip = server_addr;
	}
	return $onlineip;
}

function get_cisco_subattr($data)
{
	$Params = array();
    $resv = radius_get_vendor_attr($data);
    if (is_array($resv)) {
        $vendor = $resv['vendor'];
        $attrv = $resv['attr'];
        $datav = $resv['data'];
        if($vendor == VENDOR_CISCO)		//CISCO Vendor
        {
        	//need parse cisco parameters
	        switch($attrv)
	        {
	        case h323_credit_amount:	//
	        	$Params['h323_credit_amount'] = radius_cvt_string($datav);
	        	break;
	        case h323_currency:	//
	        	$Params['h323_currency'] = radius_cvt_string($datav);
	        	break;
	        case h323_credit_time:
	        	$Params['h323_credit_time'] = radius_cvt_string($datav);
	        	break;
	        case h323_redirect_number:
	        	$Params['h323_redirect_number'] = radius_cvt_string($datav);
	        	break;
	        case h323_redirect_ip_address:
	        	$Params['h323_redirect_ip_address'] = radius_cvt_string($datav);
	        	break;
	        case h323_billing_model:
	        	$Params['h323_billing_model'] = radius_cvt_string($datav);
	        	break;
	        case h323_return_code:
	        	$Params['h323_return_code'] = radius_cvt_string($datav);
	        	break;
	        case h323_prompt_id:
	        	$Params['h323_prompt_id'] = radius_cvt_string($datav);
	        	break;
	        case h323_preferred_lang:
	        	$Params['h323_preferred_lang'] = radius_cvt_string($datav);
	        	break;
	        case Cisco_AVPair:
	        	$subparams = explode('=',radius_cvt_string($datav));
	        	if(empty($subparams['0']))
	        		$Params["0"] = empty($subparams['1'])?'':$subparams['1'];
	        	else
	        		$Params[$subparams['0']] = empty($subparams['1'])?'':$subparams['1'];
	        	break;
	        default:
	        	$Params["CISCO-$attrv"] = radius_cvt_string($datav);
	        	break;
	        }
        }
    }
	return $Params;
}
/**
 * Radius发送呼叫请求的函数
 *
 * @param array $authParams
 * @param object $config
 * @return array
 */
function radius_invite_auth($authParams, $config) {
	$return_data = array('RETURN-CODE'=>-66);
	if (! is_array ( $authParams )){
		$return_data['RETURN-CODE'] = -61;
		return $return_data; 			//parameters error
	}
	$radius = radius_auth_open ();
	
	if ($radius == FALSE){
		$return_data['RETURN-CODE'] = -62;
		return $return_data;; 			//open radius error
	}
	if (! radius_add_server ( $radius, $config->radserver, $config->authport, 
		$config->sharedsecret, $config->retryTimes, $config->timeout )) {
		//echo 'radius_add_server:' . radius_strerror ( $radius ) . "\n<br>";
		$return_data['ERROR'] =  'radius_add_server:' .radius_strerror ( $radius );
		radius_close ( $radius );
		$return_data['RETURN-CODE'] = -63;
		return $return_data;		//添加Radius服务器错误
	}
	if (! radius_create_request ( $radius, RADIUS_ACCESS_REQUEST )) {
		$return_data['ERROR'] =  'radius_create_request:' .radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -64;			//创建Radius请求失败
		return $return_data;
	}
	/*
	 * Next will fill radius request parameters
	 */
	if (! radius_put_string ( $radius, RADIUS_NAS_IDENTIFIER, NAS_CALLBACK . '-' . $_SERVER ['SERVER_NAME'] )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_addr ( $radius, RADIUS_NAS_IP_ADDRESS, $config->server_addr/*$_SERVER['HTTP_HOST']*/ )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_int ( $radius, RADIUS_SERVICE_TYPE, RADIUS_ST_INVITE )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	$callerip = isset($authParams['callerip'])?$authParams['callerip']:get_request_ipaddr ();
	$callerip = empty($callerip)?get_request_ipaddr():$callerip;
	if (! radius_put_addr ( $radius, RADIUS_FRAMED_IP_ADDRESS,  $callerip)){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	/*if (! radius_put_vendor_attr ( $radius, VENDOR_CISCO, h323_call_type, 'CALLBACK' )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}*/
	if (! radius_put_string ( $radius, RADIUS_USER_NAME, $authParams['pin'] )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! write_radius_password ( $radius, RADIUS_CHAP_PASSWORD, $authParams['pass'] )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_string ( $radius, RADIUS_CALLING_STATION_ID, $authParams['caller'] )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_string ( $radius, RADIUS_CALLED_STATION_ID, $authParams['callee'] )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if(isset($authParams['avps']) && is_array($authParams['avps'])){
		while ( list ( $key, $val ) = each ( $authParams['avps'] ) ) {
			if (! radius_put_vendor_attr ( $radius, VENDOR_CISCO, Cisco_AVPair, $key . '=' . $val )){
				$return_data['ERROR'] =  radius_strerror ( $radius );
				$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
				return $return_data;
			}
		}
	}
	/*
	 * Next will send radius request
	 */
	$response = radius_send_request ( $radius );
	/*
	 * Handle radius response
	 */
	//echo 'radius_send_request:';
	//var_dump($response);
	//echo '<br>';
	if ($response == false) {
		$return_data['ERROR'] =  radius_strerror ( $radius );
		radius_close ( $radius );
		$return_data['RETURN-CODE'] = -66;			//发送请求失败
		return $return_data;
	} else {
		switch ($response) {
			case RADIUS_ACCESS_ACCEPT :
				{
					$return_data['RADIUS_RESP'] = RADIUS_ACCESS_ACCEPT;
					$return_data['RETURN-CODE'] = 60;			//挑战
				}
				break;
			case RADIUS_ACCESS_REJECT :
				{
					//echo 'AccessRequest Reject<br>';
					//parse return value for report error code
					$return_data['RADIUS_RESP'] = RADIUS_ACCESS_REJECT;
					$return_data['RETURN-CODE'] = 61;			//挑战
				}
				break;
			case RADIUS_ACCESS_CHALLENGE :
				{
					radius_close ( $radius );
					$return_data['RADIUS_RESP'] = RADIUS_ACCESS_CHALLENGE;
					$return_data['RETURN-CODE'] = -69;			//挑战
					return $return_data;
				}
				break;
			default :
				//echo 'AccessRequest Timeout<br>';
				radius_close ( $radius );
				$return_data['RETURN-CODE'] = -68;			//超时
				return $return_data;
		}
		while ( $resa = radius_get_attr ( $radius ) ) {
			if (! is_array ( $resa )) {
				//printf ("Error getting attribute: %s\n",  radius_strerror($radius));
				radius_close ( $radius );
				$return_data['ERROR'] =  radius_strerror ( $radius );
				$return_data['RETURN-CODE'] = -67;
				return $return_data;
			}
			$attr = $resa ['attr'];
			$data = $resa ['data'];
			switch ($attr) {
				case RADIUS_VENDOR_SPECIFIC :
					if ($attr == RADIUS_VENDOR_SPECIFIC) {
						$return_data = array_merge ( $return_data, get_cisco_subattr ( $data ) );
					}
					break;
				case RADIUS_CALLING_STATION_ID :
					//modify caller number
					$return_data ['caller'] = radius_cvt_string ( $data );
					break;
				case RADIUS_USERNAME :
					//modify callee number
					$return_data ['pin'] = radius_cvt_string ( $data );
					break;
				case RADIUS_PASSWORD :
					$return_data ['password'] = radius_cvt_string ( $data );
					break;
				default :
					$return_data ["ATTR-$attr"] = radius_cvt_string ( $data );
					break;
			}
		}		//end while
		//echo '<br>' . join(',',$callbackParams) . '<br>';
		radius_close ( $radius );
		$r = $return_data['h323_return_code'];
		if(isset($r)){
			if($r >= 0){
				$r = $r + 100;			//返回值大于0把返回值调整为大于100的数
			}else{
				$r = $r - 100;			//返回值小于0把返回值调整为小于-100的数
			}
			$return_data['RETURN-CODE'] = $r;  //把数据库存储过程的返回值转换为函数返回值
		}
	}
	return $return_data;
}

function radius_account_req($params, $config) {
	$return_data = array('RETURN-CODE'=>-66);
	if (! is_array ( $params )){
		$return_data['RETURN-CODE'] = -61;
		return $return_data; 			//parameters error
	}
	$radius = radius_acct_open();
	
	if ($radius == FALSE){
		$return_data['RETURN-CODE'] = -62;
		return $return_data;; 			//open radius error
	}
	if (! radius_add_server ( $radius, $config->radserver, $config->authport, 
		$config->sharedsecret, $config->retryTimes, $config->timeout )) {
		//echo 'radius_add_server:' . radius_strerror ( $radius ) . "\n<br>";
		$return_data['ERROR'] =  'radius_add_server:' .radius_strerror ( $radius );
		radius_close ( $radius );
		$return_data['RETURN-CODE'] = -63;
		return $return_data;		//添加Radius服务器错误
	}
	if (! radius_create_request ( $radius, RADIUS_ACCOUNTING_REQUEST )) {
		$return_data['ERROR'] =  'radius_create_request:' .radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -64;			//创建Radius请求失败
		return $return_data;
	}
	/*
	 * Next will fill radius request parameters
	 */
	if (! radius_put_string ( $radius, RADIUS_NAS_IDENTIFIER, NAS_CALLBACK . '-' . $_SERVER ['SERVER_NAME'] )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_addr ( $radius, RADIUS_NAS_IP_ADDRESS, $config->server_addr/*$_SERVER['HTTP_HOST']*/ )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_int ( $radius, RADIUS_SERVICE_TYPE, RADIUS_ST_INVITE )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_int ( $radius, RADIUS_ACCT_STATUS_TYPE, RADIUS_STOP )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	
	$callerip = isset($params['callerip'])?$params['callerip']:get_request_ipaddr ();
	$callerip = empty($callerip)?get_request_ipaddr():$callerip;
	if (! radius_put_addr ( $radius, RADIUS_FRAMED_IP_ADDRESS,  $callerip)){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	/*if (! radius_put_vendor_attr ( $radius, VENDOR_CISCO, h323_call_type, 'CALLBACK' )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}*/
	if (! radius_put_string ( $radius, RADIUS_ACCT_SESSION_ID, $params['sessionid'] )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_string ( $radius, RADIUS_USER_NAME, $params['pin'] )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_string ( $radius, RADIUS_CALLING_STATION_ID, $params['caller'] )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_string ( $radius, RADIUS_CALLED_STATION_ID, $params['callee'] )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_int ( $radius, RADIUS_ACCT_SESSION_TIME, intval($params['billsec']) )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_int ( $radius, RADIUS_ACCT_TERMINATE_CAUSE, 0)){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_string ( $radius, RADIUS_CALLED_STATION_ID, $params['callee'] )){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_vendor_attr ( $radius, VENDOR_CISCO, h323_setup_time, $params['start'])){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_vendor_attr ( $radius, VENDOR_CISCO, h323_connect_time, $params['start'])){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	if (! radius_put_vendor_attr ( $radius, VENDOR_CISCO, h323_disconnect_time, $params['start'])){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	
	if (! radius_put_vendor_attr ( $radius, VENDOR_CISCO, h323_remote_address, $params['calleeip'])){
		$return_data['ERROR'] =  radius_strerror ( $radius );
		$return_data['RETURN-CODE'] = -65;			//添加Radius参数失败
		return $return_data;
	}
	/*
	 * Next will send radius request
	 */
	$response = radius_send_request ( $radius );
	/*
	 * Handle radius response
	 */
	//echo 'radius_send_request:';
	//var_dump($response);
	//echo '<br>';
	if ($response == false) {
		$return_data['ERROR'] =  radius_strerror ( $radius );
		radius_close ( $radius );
		$return_data['RETURN-CODE'] = -66;			//发送请求失败
		return $return_data;
	} else {
		switch ($response) {
			case RADIUS_ACCOUNTING_RESPONSE :
				{
					$return_data['RADIUS_RESP'] = RADIUS_ACCOUNTING_RESPONSE;
					$return_data['RETURN-CODE'] = 60;			//挑战
				}
				break;
			default :
				//echo 'AccessRequest Timeout<br>';
				radius_close ( $radius );
				$return_data['RETURN-CODE'] = -68;			//超时
				return $return_data;
		}
		while ( $resa = radius_get_attr ( $radius ) ) {
			if (! is_array ( $resa )) {
				//printf ("Error getting attribute: %s\n",  radius_strerror($radius));
				radius_close ( $radius );
				$return_data['ERROR'] =  radius_strerror ( $radius );
				$return_data['RETURN-CODE'] = -67;
				return $return_data;
			}
			$attr = $resa ['attr'];
			$data = $resa ['data'];
			switch ($attr) {
				case RADIUS_VENDOR_SPECIFIC :
					if ($attr == RADIUS_VENDOR_SPECIFIC) {
						$return_data = array_merge ( $return_data, get_cisco_subattr ( $data ) );
					}
					break;
				case RADIUS_CALLING_STATION_ID :
					//modify caller number
					$return_data ['caller'] = radius_cvt_string ( $data );
					break;
				case RADIUS_USERNAME :
					//modify callee number
					$return_data ['pin'] = radius_cvt_string ( $data );
					break;
				case RADIUS_PASSWORD :
					$return_data ['password'] = radius_cvt_string ( $data );
					break;
				default :
					$return_data ["ATTR-$attr"] = radius_cvt_string ( $data );
					break;
			}
		}		//end while
		//echo '<br>' . join(',',$callbackParams) . '<br>';
		radius_close ( $radius );
		$r = $return_data['h323_return_code'];
		if(isset($r)){
			if($r >= 0){
				$r = $r + 100;			//返回值大于0把返回值调整为大于100的数
			}else{
				$r = $r - 100;			//返回值小于0把返回值调整为小于-100的数
			}
			$return_data['RETURN-CODE'] = $r;  //把数据库存储过程的返回值转换为函数返回值
		}
	}
	return $return_data;
}

?>

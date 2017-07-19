<?php
/*
 * creater: lion wang
 * time: 2010.05.07
 * alter time: 2010.05.07
 * caption: uphone active
 *	
*/

api_uphone_action($api_object);

/*
	定义错误字符串处理的回调函数，正常情况下错误字符串已经可以按照语言和action取得。
*/
function get_message_callback($api_obj,$context,$msg){
	return sprintf($msg,$api_obj->return_code);
}


/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj,$context){
	$resp = $api_obj->write_return_params_with_json();		
	return $resp;
}


function api_uphone_action($api_obj) {
	$bsn = empty($_REQUEST['bsn']) ? 'UPHONE_BSN' : $_REQUEST['bsn'];;
	$v_id = empty($_REQUEST['VID']) ? '' : $_REQUEST['VID'];
	$p_id = empty($_REQUEST['PID']) ? '' : $_REQUEST['PID'];
	$gu_id = empty($_REQUEST['SN']) ? '' : $_REQUEST['SN'];
	$account = empty($_REQUEST['v_Account']) ? '' : $_REQUEST['v_Account'];
	$password = empty($_REQUEST['v_Password']) ? '' : $_REQUEST['v_Password'];
	$format = 'xml';
	//SQL 返回值 =2  该设备已经激活过 = 1	信息验证正确，允许激活 = -1 该设备没有入库 = -2 该设备没有出库 =-4; 获取参数信息失败
	try {
		require_once $api_obj->params['common-path'].'/api_pgsql_db.php';
		$db_obj = new api_pgsql_db($api_obj->config->wfs_db_config, $api_obj);
	
	
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$api_obj->set_callback_func(get_message_callback,write_response_callback, $api_obj);
	
		//IN v_bsn varchar, IN v_gu_id varchar, IN v_phone_number varchar, IN v_pid varchar, IN v_vid varchar, IN v_version varchar, IN v_hwver varchar, 
		//OUT v_carrier_params text, OUT v_plan_params text, OUT v_extend_params text, OUT v_billing_api varchar, OUT p_return int4
		$sp = "SELECT *FROM ez_wfs_db.sp_wfs_api_get_info_by_imei( $1, $2, $3, $4, $5)";
		$params = array(
			$bsn,
			$gu_id,
			$p_id,
			$v_id,
			'uphone'
		);
		$array = $db_obj->exec_db_sp($sp, $params);
		//var_dump($array);
		if (is_array($array) && !empty($array)) {
			$api_attribute = $array['v_attribute']; 
			$api_url = $array['v_api_ip']; 
			$secret = $array['v_serect'];
			$setArr = array(
				'action' => $api_obj->params['api_params']['action'],
				'v_id' => $v_id,
				'p_id' => $p_id,
				'gu_id' => $gu_id,
				'account' => $account,
				'password' => $password,
				'format' => $format
			);
			$billing_arr = access_billing_api($api_url, $setArr, $secret);
			
			echo $api_attribute;
			echo $billing_arr;
			
			/*
		$db_obj = new api_pgsql_db($api_obj->config->new_wfs_db_config, $api_obj);
	
	
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$api_obj->set_callback_func(get_message_callback,write_response_callback, $api_obj);
	
		//IN v_bsn varchar, IN v_gu_id varchar, IN v_phone_number varchar, IN v_pid varchar, IN v_vid varchar, IN v_version varchar, IN v_hwver varchar, 
		//OUT v_carrier_params text, OUT v_plan_params text, OUT v_extend_params text, OUT v_billing_api varchar, OUT p_return int4
		$sp = "SELECT *FROM ez_wfs_db.sp_n_wfs_get_config_by_sn( $1, $2, $3, $4, $5,$6,$7)";
		$params = array(
			$bsn,
			$gu_id,
			$account,
			$p_id,
			$v_id,
			$version,
			'uphone'
		);
		$array = $db_obj->exec_db_sp($sp, $params);
		//var_dump($array);
		if (is_array($array) && !empty($array)) {
			$api_attribute = $array['v_attribute']; 
			$api_url = $array['v_api_ip']; 
			$secret = $array['v_serect'];
			$setArr = array(
				'action' => $api_obj->params['api_params']['action'],
				'v_id' => $v_id,
				'p_id' => $p_id,
				'gu_id' => $gu_id,
				'account' => $account,
				'password' => $password,
				'format' => $format
			);
			$billing_arr = access_billing_api($api_url, $setArr, $secret);
			  foreach ($array as $k => $v)
				$api_obj->push_return_data($k,$v);
			$params = $api_obj->json_decode($array['v_carrier_params']);
			if(is_object($params)){
				if(isset($params->UPHONE))
					$uphone = $params->UPHONE;
				else if(isset($params->uphone))
					$uphone = $params->uphone;
			}
			if(!isset($uphone)){
				$uphone = new stdClass();
				//设置默认值
				$uphone->config->WebSite->Address = 'http://www.eztor.com';
				$uphone->Proxy = array(
					'Server1' => '202.134.80.107:8060'
					);
				$uphone->StunServer = array(
					"Stun1" => '202.134.80.107'
					);
				$uphone->VPNServer=array(
					"VPN1" => '202.134.80.106:4000',
					"VPN2" => '202.134.124.233:4000'
					);
				$uphone->VPNConfig = array(
					"client" => '',
					"dev" => 'tun',
					"proto" => 'udp',
					"nobind" => '',
					"persist-key" => '',
					"persist-tun" => '',
					"ca" => '',
					"auth-user-pass" => '',
					"ns-cert-type" => '',
					"comp-lzo" => 'lzo',
					"verb" => '3'
				);				
			}
			echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\r\n";
			echo $api_obj->json_to_xml($uphone);
			return;*/
		}else{
			$api_obj->return_code = '-101';
			$api_obj->write_response();
		}
	} catch (Exception $e) {
		$rows = $e->getMessage();
		return $rows;
	}
}


/*
 *	wirter:  lion wang
 *	caption: access api by post
 *	version: 1.0
 *	time: 2010-04-23
 *	last time: 2010-04-23
 *	return:  api retuan result
 *
 * */
function access_billing_api($api_url, $params, $secret){
		if(is_array($params)){
			foreach ($params as $key=>$value)
				$str .= $key."=".$value.',';
		}
		//加密
		$en_string = api_encrypt($str, $secret);
		
		
		//post的参数
		$postfield = 'p='.$en_string.'&a='.$params['action'];
		$url = $api_url.'?'.$postfield;
		//echo $url ;
		
		$ch = curl_init();	
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); 
		$ch_result = curl_exec($ch);
		curl_close($ch);

		return $ch_result;
} // access_api()

function get_defalt_params()
{
	$params = '
		{
			"Config":{
				"WebSite":{
					"Address":"www.hkutone.com"
				}
			},
			"Proxy":{
				"Tag":"List",
				"Items":[{
					"Caption":"SIP Server",
					"Address":"202.134.80.107:6080"
				}]
			},
			{
			}
		}
<Proxy>
<List>
<Caption>SIP Server</Caption>
<Address>202.134.80.107:6080</Address>
</List>
</Proxy>
<StunServer>
<Stun>
<Address>stun.ekiga.net</Address>
</Stun>
</StunServer>
<VPNServer>
<List>
<Caption>eztorvpn.ovpn</Caption>
<Address>202.134.80.106:8000</Address>
<Host>
<udp>202.134.80.106:8000</udp>
<tcp>202.134.80.106:8000</tcp>
</Host>
</List>
</VPNServer>
<VPNConfig>
<option param="client"></option>
<option param="dev">tun</option>
<option param="proto">udp</option>
<option param="nobind"></option>
<option param="persist-key"></option>
<option param="persist-tun"></option>
<option param="ca">eztorvpn-ca.crt</option>
<option param="auth-user-pass"></option>
<option param="ns-cert-type">server</option>
<option param="comp-lzo"></option>
<option param="verb">3</option>
</VPNConfig>
<FTPServer>
<List>
<Address>221.193.194.230</Address>
<Port>21</Port>
</List>
</FTPServer>
<LinkUrl>
<Link Width="0" Height="0" tooltip="使用说明">http://www.eztor.com/faq/faqallutone.html</Link>
<Link Width="0" Height="0" tooltip="商务星空">http://www.eztor.com</Link>
<Link Width="0" Height="0" tooltip="商务领航">http://www.eztor.com</Link>
<Link Width="0" Height="0" tooltip="啦啦宝贝">http://www1.chinaddup.com/lala/lala.do?method=indexList</Link>
<Link Width="0" Height="0" tooltip="中国电信">http://www.chinatelecom.com.cn</Link>
<Link Width="0" Height="0" tooltip="怡拓科技">http://www.eztor.com</Link>
<Link Width="0" Height="0" tooltip="">http://www.eztor.com/bzphone/ezphone-ad.swf</Link>
<Link Width="720" Height="440" tooltip="">http://202.65.223.49/wiki/web800/bin/CallPhoneBook.swf</Link>
<Link Width="0" Height="0" tooltip="">http://202.134.124.228/utone</Link>
</LinkUrl>
<Update>
<Iso>http://config.oparner.com/update/10016003/iso_version.xml</Iso>
<File>http://config.oparner.com/update/10016003/web800_version.xml</File>
</Update>
<Active>
<Link>http://202.134.80.109/voip/utone/cj/index.html</Link>
</Active>
<WebUrl>
<UserInfo>http://202.134.80.109/uphone/show_user_info.php</UserInfo>
<VpnUserInfo>http://202.134.80.109/voip/utone/api/api_uphone.php?a=get_account_info</VpnUserInfo>
<VpnActiveUrl>http://202.134.80.109/ezbilling_devel/wfs_api/api_uphone.php?a=uphone_active&amp;key=abcd1234</VpnActiveUrl>
<VpnFlowControl>http://202.134.80.109/ezbilling_devel/ezbilling_api/api_uphone.php?a=bandwidth_control</VpnFlowControl>
</WebUrl>
<Billing>
<Url>http://202.134.124.228/utone</Url>
<Web>http://202.134.124.228/utone</Web>
</Billing>
<CallLimit>
<Time>A694</Time>
</CallLimit>
<ADPage>
<Open>1</Open>
<Link>http://202.134.80.109/ezbilling_devel/ezbilling/uphone_ad.php</Link>
</ADPage>
<SmallAD>
<Open>1</Open>
<Link>http://advert.oparner.com/oparner/ad.html</Link>
</SmallAD>
<OparnerAD>
<Link>
<Caption>首页</Caption>
<Url>http://advert.oparner.com/oparner/utone.html</Url>
</Link>
<Link>
<Caption>产品展示</Caption>
<Url>http://advert.oparner.com/oparner/produceshow.html</Url>
</Link>
<Link>
<Caption>资费说明</Caption>
<Url>http://advert.oparner.com/oparner/zifei.html</Url>
</Link>
</OparnerAD>
<PayWay>
<option param="card">1</option>
<option param="web">1</option>
</PayWay>
<SMS>
<Open>1</Open>
<Server>221.193.194.230:21567</Server>
</SMS>
<License>
<Company>优通国际</Company>
</License>
<SoftwareName>
<Name>优话通</Name>
<VpnName>EZVPN</VpnName>
</SoftwareName>
</Config>
 ';
	return $params;
}


?>

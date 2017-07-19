<?php
/*
 * 执行操作
 * */
api_ophone_action ( $api_object );

function get_message_callback($api_obj, $context, $msg) {
	if (empty ( $api_obj->return_data ['pno'] )) {
		return sprintf ( $msg, $api_obj->return_code, $api_obj->return_data ['pno'] );
	} else
		return sprintf ( $msg, $api_obj->return_code, $api_obj->return_data ['pno'] );
}
/*
 写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
 */

function write_response_callback($api_obj, $context) {
	$resp = $api_obj->write_return_params (); //按照老的手机需要的返回格式处理参数
	//当返回错误的时候以XML格式返回
	if ($api_obj->return_code <= 0) {
		//if($api_obj->return_code ==251){
		$resp .= $api_obj->write_return_xml (); //按照老的手机需要的返回格式处理参数
	//}
	} else {
		//按照老的手机处理格式处理返回代码，正确的处理返回代码手机才会从请求界面中退出。
		$resp = $resp . $api_obj->write_invite_response_code ();
	}
	return $resp;
}

function api_ophone_action($api_obj) {
	//先从新WFS更新
	$update_return = get_config_from_wfs ( $api_obj ); //update_new_wfs($api_obj, $version);
	//若新wfs无信息，再从老wfs更新，并同步数据到新WFS
	if ($update_return == - 101 or $update_return == 253) {
		//同步信息,可以 运营商分批同步
		$carrier_array = isset ( $api_obj->config->carrier_array ) ? $api_obj->config->carrier_array : array ();
		$r = synchro_wfs ( $api_obj, $carrier_array );
		$api_obj->write_hint ( sprintf ( "synchro wfs:\r\n%s", $r));
		$update_return = get_config_from_wfs ( $api_obj );
	} else {
		$api_obj->write_hint ( $api_obj->return_data );
		//返回新wfs的配置信息
	}
	$api_obj->write_response ();
}

/****
 * 获得配置使用存储过程 sp_n_wfs_get_configure，
 * 该存储过程的输入参数：
		v_bsn 				varchar, 		//设备序列号1(手机为主板序列号，U盘为USB设备序列号)
    	v_gu_id	 			varchar, 		//设备序列号2(手机为IMEI，U盘为GUID)
    	v_phone_number		varchar, 		//绑定的手机号码
    	v_pid	 			varchar, 		//设备的产品标识
    	v_vid 				varchar, 		//设备的生厂商标识
    	v_version 			varchar, 		//设备的软件版本号
		v_hwver				varchar,		//设备的硬件版本号
	输出参数：
		out p_carrier_params	text,    	--Json格式的设备运营参数，设备运营参数不能需要OEM，设备运营参数也会通过虚拟运营商平台获得，这样可以做到不同代理商使用不同的参数，虚拟运营平台返回的参数会覆盖WFS的默认配置
    	out p_cp_params 		text,    	--Json格式的设备激活使用的计费方案
    	out p_oem_params 	text, 		--Json格式的设备OEM参数，OEM参数是会从虚拟运营商根据代理商获得修改后的参数来覆盖WFS的默认配置
   	 	out p_billing_api 		varchar	    --虚拟运营平台的API接口地址
		out p_pno				varchar		--如果已经激活则返回激活的的手机号码
		out p_return			integer		--返回代码
 *  3 : 成功，设备表中不存在此设备信息，利用VID和PID来获得设备信息
 * 	2 : 成功，在设备表中找到设备信息，更新设备的版本号信息
 *  1 : 成功，设备已经激活，返回信息里会包含激活的手机号码信息
 *  -1 : 设备表中设备信息不存在，也没有设置VID和PID信息
 *  -2 : 设备已经过了激活期，不能激活
 *  -3 : 设备找到但是运营商参数有误
 * */
function get_config_from_wfs($api_obj) {
	$wfs_db = new api_pgsql_db ( $api_obj->config->new_wfs_db_config, $api_obj );
	$api_obj->set_callback_func ( get_message_callback, write_response_callback, $wfs_db );
	$sp_sql = "SELECT * FROM ez_wfs_db.sp_n_wfs_get_configure($1,$2,$3,$4,$5,$6,$7)";
	$sv = isset ( $api_obj->params ['api_version'] ) ? $api_obj->params ['api_version'] : '';
	$hv = isset ( $_REQUEST ['hwv'] ) ? $_REQUEST ['hwv'] : '';
	$params = array ($api_obj->params ["api_params"] ['bsn'], $api_obj->params ["api_params"] ['imei'], $api_obj->params ["api_params"] ['pno'], $api_obj->params ["api_params"] ['pid'], $api_obj->params ["api_params"] ['vid'], $sv, $hv );
	$r = $wfs_db->exec_db_sp ( $sp_sql, $params );
	//$api_obj->write_hint($r);
	if (is_array ( $r )) {
		$api_obj->return_code = $r ['p_return'];
		$api_obj->push_return_data ( 'pno', $r ['p_pno'] );
		if ($api_obj->return_code > 0) {
			$api_obj->return_code += 250;
			
			//foreach ($r as $k=>$v)
			//	$api_obj->push_return_data($k,$v);
			$ChargePlan = $r['p_cp_params'];
			$billing_api = $r ['p_billing_api']; //运营商billig api
			$ome_extend_params = $r ['p_oem_params']; //运营商OEM扩展参数
			//去运营商处获取代理商OEM信息和功能url地址
			$carrier_config = get_extend_info ( $api_obj, $billing_api, $ome_extend_params,$ChargePlan);
			//$api_obj->write_hint($oem_attribute);
			//$carrier_config = $api_obj->json_decode ( $oem_attribute );
			//echo sprintf(" oem=%s.",array_to_string('<br>',$r));
			if (! is_object ( $carrier_config )) {
				//设置默认的运营商参数
				$carrier_config = $api_obj->json_decode ( $r ['p_carrier_params'] );
			}else{
				//$api_obj->write_warning(sprintf("json_decode error:%s .",$oem_attribute));
			}
			if (is_object ( $carrier_config )) {
				//检查是否包含OPHONE对象
				if (isset ( $carrier_config->OPHONE ) && is_array ( $carrier_config->OPHONE )) {
					//检查版本号
					$tmp_version = 0;
					$version = str_replace ( '.', '0', $api_obj->params ['api_version'] );
					//$api_obj->write_hint($version);
					foreach ( $carrier_config->OPHONE as $k => $v ) {
						$config_version = isset ( $v->version ) ? $v->version : '';
						$config_version = str_replace ( '.', '0', $config_version );
						if ((intval ( $version ) >= intval ( $config_version )) && intval ( $config_version ) >= $tmp_version) {
							$tmp_version = intval ( $config_version );
							$ophone_config = $v;
						}
					}
					if (is_object ( $ophone_config )) {
						foreach ( $ophone_config as $k => $v ) {
							switch ($k) {
								case 'invite_url' :
									$api_obj->push_return_data ( 'INVITE-URL', str_replace('\/','/',$v) );
									break;
								case 'query_url' :
									$api_obj->push_return_data ( 'QUERYURL', str_replace('\/','/',$v) );
									break;
								case 'recharge_url' :
									$api_obj->push_return_data ( 'RECHARGE-URL', str_replace('\/','/',$v) );
									break;
								case 'encrypt' :
									$api_obj->push_return_data ( 'ENCRYPT', $v );
									break;
								case 'serect' :
									$api_obj->push_return_data ( 'SERECT', $v );
									break;
								case 'action_url' :
									$api_obj->push_return_data ( 'ACTION-URL', str_replace('\/','/',$v) );
									break;
								case 'active_url' :
									$api_obj->push_return_data ( 'ACTIVE-URL', str_replace('\/','/',$v) );
									break;
								default :
									if (strtolower ( $k ) != 'attribute')
										$api_obj->push_return_data ( $k, $v );
							}
						}
						
						if (! isset ( $ophone_config->attribute )) {
							$ophone_config->attribute = $api_obj->json_decode ( $ome_extend_params );
							if(!is_object($ophone_config->attribute))
								$ophone_config->attribute = api_string_to_array($ome_extend_params,',','=');
							else 
								$api_obj->write_warning(sprintf("json_decode error:%s .",$ome_extend_params));
						}else{
							if (is_string ( $ophone_config->attribute ) and trim($ophone_config->attribute) != '')
								$ophone_config->attribute = api_string_to_array ( $ophone_config->attribute, ',', '=' );
						}
						if(is_object($ophone_config->attribute) or is_array($ophone_config->attribute)){
							foreach ( $ophone_config->attribute as $k => $v )
								$api_obj->push_return_data ( $k, $v );
						}
						//$api_obj->write_hint(array($ophone_config->attribute,$ome_extend_params));
					}
				}else{
					$api_obj->write_warning(sprintf("Config no OPHONE array ."));
				}
			}else{
				$api_obj->write_warning(sprintf("json_decode error:%s .",$r ['p_carrier_params']));
			}
			return $api_obj->return_code;
		} else {
			$api_obj->return_code -= 100;
			return $api_obj->return_code;
		}
	} else {
		return 0; //存储过程执行失败
	}
}

/*
 * 通过imei和bsn获取扩展信息和ome信息
 * 
 * */
function get_extend_info($api_obj, $api_url, $ome_extend_params,$ChargePlan) {
	$params = array(
		'a'	=> 'ophone_get_oem_info',
		'bsn'	=>	$api_obj->params["api_params"]['bsn'],
		'imei'	=>	$api_obj->params["api_params"]['imei'],
		'pno'	=>	$api_obj->params["api_params"]['pno'],
		'pid'	=>	$api_obj->params["api_params"]['pid'],
		'vid'	=>	$api_obj->params["api_params"]['vid'],
		'v'		=>	$api_obj->params['api_version'],
		'hwv'	=>	$_REQUEST['hwv'],
		'plan_params'	=>	$ChargePlan
	)
	
	;
	//echo $api_url;
	$result = $api_obj->get_from_api ( $api_url, $params );
	/*if (! strpos ( $result, 'error' )) {
		//只获取json里数据
		$p = strpos ( $result, '{' );
		if ($p > 0) {
			$result = substr ( $result, $p, strlen ( $result ) );
		}
		$result_array = get_object_vars ( json_decode ( $result ) );
		//获取data里数据
		$result_array = get_object_vars ( $result_array ['data'] );
		$v_phone = $result_array ['v_pno'];
		$v_attribute = $result_array ['v_attribute'];
		return $v_attribute;
	} else {
		return - 1;
	}
	*/
	//$api_obj->push_return_data('from-api',$result);
	$r = json_decode($result);
	if(is_object($r)){
		return $r;		
	}else{
		return FALSE;
	}
}

/*
 * 通过imei和bsn获取扩展信息和ome信息
 * 并在运营商做出入库的操作
 * 
 * */
function insert_stock($api_obj, $api_url, $extend_params, $plan_params) {
	$params = array ('a' => 'ophone_insert_stock', 'bsn' => $api_obj->params ["api_params"] ['bsn'], 'imei' => $api_obj->params ["api_params"] ['imei'], 'pno' => $api_obj->params ["api_params"] ['pno'], 'pid' => $api_obj->params ["api_params"] ['pid'], 'vid' => $api_obj->params ["api_params"] ['vid'], 'plan_params' => $plan_params );
	$r = $api_obj->get_from_api ( $api_url, $params );
	echo $r;
	//获取运营商出入库成功，则在wfs更新
	if (! strpos ( $r, 'stock_error' ) && strpos ( $r, '}' )) {
		$p = strpos ( $r, '{' );
		if ($p > 0) {
			$r = substr ( $r, $p, strlen ( $r ) );
		}
		$result_array = get_object_vars ( json_decode ( $r ) );
		$data_array = get_object_vars ( $result_array ['data'] );
		$carriar_name = trim ( $data_array ['carrier_name'] );
		
		$wfs_params = array ($api_obj->params ["api_params"] ['bsn'], $api_obj->params ["api_params"] ['imei'], $plan_params, '0', $carriar_name, $api_obj->params ["api_params"] ['vid'], $api_obj->params ["api_params"] ['pid'], 'wfs by pid,vid' );
		
		if (empty ( $carriar_name )) {
			$api_obj->write_hint ( $wfs_params );
			return;
		} else {
			$sp_sql = "SELECT * FROM ez_wfs_db.sp_import_devices($1,$2,$3,$4,$5,$6,$7,$8)";
			
			$wfs_db = new api_pgsql_db ( $api_obj->config->new_wfs_db_config, $api_obj );
			$wfs_return = $wfs_db->exec_db_sp ( $sp_sql, $wfs_params );
			if (! is_array ( $wfs_return )) {
				$api_obj->write_hint ( $wfs_params );
			}
			return;
		}
	}
}

//同步新老wfs
function synchro_wfs($api_obj, $carrier_array) {
	//从老版的wfs获取数据，更新到新版的wfs
	//获取更新信息，配置信息格式为josn
	$sp_sql = "SELECT * FROM ez_wfs_db.sp_synchro_get_carrier($1,$2,$3,$4,$5)";
	$wfs_params = array (
		$api_obj->params ["api_params"] ['bsn'], 
		$api_obj->params ["api_params"] ['imei'], 
		$api_obj->params ["api_params"] ['pid'], 
		$api_obj->params ["api_params"] ['vid'], 
		'ophone' 
	);
	/*
		out v_api_ip varchar, 
	    out rv_bsn varchar, 
	    out rv_gu_id varchar, 
	    out n_resaler integer, 
	    out v_charge_plan text, 
	    out v_bind_pno varchar, 
	    out v_bind_epno varchar, 
	    out n_status integer,
	    out d_active_time timestamp,
	    out d_os_time timestamp, 
	    out d_is_time timestamp,  
	    out v_remark varchar,
    	out p_return integer
    */
	$wfs_db = new api_pgsql_db ( $api_obj->config->wfs_db_config, $api_obj );
	//通过bsn 和 imei从旧wfs获取设备信息和billng的api
	$wfs_return = $wfs_db->exec_db_sp ( $sp_sql, $wfs_params );
	$api_obj->write_hint(sprintf("sp_synchro_get_carrier return :%s\r\n",array_to_string("\r\n",$wfs_return)));
	if ($wfs_return ['p_return'] > 0) { //获取billing api成功
		//通过api.php去同步，但是从旧wfs获取api是 api_ophone.php，所以要替换
		$api_url = str_replace ( 'api_ophone', 'api_billing', $wfs_return ['v_api_ip'] );
		
		//同步配置
		if (! empty ( $wfs_return ['v_invite_url'] ) && ! empty ( $wfs_return ['v_oem_config'] )) {
			$config_array = array ("OPHONE" => array (array ("invite_url" => $wfs_return ["v_invite_url"], "query_url" => $wfs_return ["v_query_url"], "recharge_url" => $wfs_return ["v_query_url"], "encrypt" => $wfs_return ["v_encrypt"], "serect" => $wfs_return ["v_serect"], "action_url" => $wfs_return ["v_action_url"], "active_url" => $wfs_return ["v_active_url"], "attribute" => $wfs_return ["v_oem_config"], "version" => $wfs_return ["v_version"] ) ), "UPHONE" => array (array ("IP" => "202" ) ) );
			
			$config_json = json_encode ( $config_array );
		}
		$api_obj->write_hint ( array ('$config_json' => $config_json ) );
		$params = array ('a' => 'ophone_synchro_wfs', 'v_bsn' => $wfs_return ['rv_bsn'], 'v_gu_id' => $wfs_return ['rv_gu_id'], 'n_resaler' => $wfs_return ['n_resaler'], 'v_charge_plan' => $wfs_return ['v_charge_plan'], 'v_bind_pno' => $wfs_return ['v_bind_pno'], 'v_bind_epno' => $wfs_return ['v_bind_epno'], 'n_status' => $wfs_return ['n_status'], 'd_active_time' => $wfs_return ['d_active_time'], 'd_os_time' => $wfs_return ['d_os_time'], 'd_is_time' => $wfs_return ['d_is_time'], 'v_remark' => $wfs_return ['v_remark'], 'v_pid' => $api_obj->params ["api_params"] ['pid'], 'v_vid' => $api_obj->params ["api_params"] ['vid'], 'config_json' => $config_json );
		//同步运营商的信息
		$r = $api_obj->get_from_api ( $api_url, $params );
		
		$api_obj->write_hint ( array ('carriar_name' => $r ) );
		$r_len = strlen ( $r ); //防止同步失败
		

		//返回$r的是 运营商的名称,分批同步
		if (! strpos ( $r, 'error' ) && $r_len < 15 && in_array ( $r, $carrier_array )) {
			$carriar_name = trim ( $r );
			$sp_sql = "SELECT * FROM ez_wfs_db.sp_import_devices($1,$2,$3,$4,$5,$6,$7,$8)";
			
			$wfs_params = array ('ophone_bsn', $wfs_return ['rv_gu_id'], $wfs_return ['v_charge_plan'], $wfs_return ['n_resaler'], $carriar_name, $api_obj->params ["api_params"] ['vid'], $api_obj->params ["api_params"] ['pid'], 'synchro_wfs_store' );
			$wfs_db = new api_pgsql_db ( $api_obj->config->new_wfs_db_config, $api_obj );
			$wfs_return = $wfs_db->exec_db_sp ( $sp_sql, $wfs_params );
			$wfs_return ['p_return'] += 100; 
			return $wfs_return ['p_return'];
		} else {
			return -2; //同步失败
		}
	} else {
		return -1; //在旧wfs未出库
	}
}

function convert_string_to_array($string) {
	$array = array ();
	$params = explode ( ',', $string );
	for($i = 0; $i < count ( $params ); $i ++) {
		$params1 = explode ( '=', $params [$i] );
		$array1 = array ($params1 [0] => $params1 [1] );
		$array = array_merge ( $array, $array1 );
	}
	return $array;
}

?>

<?php



function get_stock_message_callback($api_obj, $context, $msg) {
	if (empty($api_obj->return_data['message'])) {
		return sprintf ( $msg, $api_obj->return_code );
	}else{
		return $api_obj->return_data['message'];
	} 
}

function write_stock_response_callback($api_obj, $context) {
	//$api_obj->write_trace(0,'Run here');
	if (strpos($api_obj->return_code,':') >0 ) {
		$return_code = explode(':',$api_obj->return_code);
		$success = $return_code[1] > 0;
	}else{
		$success = $api_obj->return_code > 0;
	}
	
	$api_obj->push_return_data ( 'success', $success );
	if (empty($api_obj->return_data['message'])) {
		$api_obj->push_return_data ( 'message', $api_obj->get_error_message ( $api_obj->return_code ), '' );
	}else
		$api_obj->push_return_data ( 'message', $api_obj->return_data['message']);
	
	$resp = $api_obj->write_return_params_with_json ();
	return $resp;
}
/*
	定义错误字符串处理的回调函数，正常情况下错误字符串已经可以按照语言和action取得。
	但是有时候我们需要在字符串中格式一些其他的参数，如：电话号码，姓名什么的。
	例如：
		解除绑定失败的错误字符串：号码%1s与本手机解除绑定失败，代码[%0d]，该手机已经和%2s绑定。
		假设本手机号码在变量$api_obj->parget_devices_infoams['api_params']['pno']中，已经绑定的号码在
	$api_obj->return_data['p_bind_no']中，那么我们就需要
		function get_message_callback($api_obj,$context,$msg){
			return sprintf($msg,$api_obj->return_code,$api_obj->params['api_params']['pno'],$api_obj->return_data['p_bind_no']);
		}
*/
function get_message_callback($api_obj, $context, $msg) {
	return sprintf ( $msg, $api_obj->return_code );
}

/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj, $context) {
	//$api_obj->write_trace(0,'Run here');
	if (strpos($api_obj->return_code,':') >0 ) {
		$return_code = explode(':',$api_obj->return_code);
		$success = $return_code[1] > 0;
	}else{
		$success = $api_obj->return_code > 0;
	}
	
	$api_obj->push_return_data ( 'success', $success );
	$api_obj->push_return_data ( 'message', $api_obj->get_error_message ( $api_obj->return_code ), '' );
	
	$resp = $api_obj->write_return_params_with_json ();
	return $resp;
}


function get_recharge_message_callback($api_obj,$context,$msg){
	return sprintf($msg,$api_obj->return_code,
		$api_obj->return_data['reBalance'],//当前充值金额
		$api_obj->return_data['reNewBalance'],//当前总余额
		$api_obj->return_data['rePINBalance'],//充值卡剩余余额
		$api_obj->return_data['reCurrencyType']//费率
		//$api_obj->return_data['VP']//话费到期时间
	);
		
}
/*
function write_recharge_response_callback($api_obj, $context) {
	//$api_obj->write_trace(0,'Run here');
	if (strpos($api_obj->return_code,':')>0) {
		$return_code = explode(':',$api_obj->return_code);
		$success = $return_code[1] > 0;
	}else{
		$success = $api_obj->return_code > 0;
	}
	
	$api_obj->push_return_data ( 'success', $success );
	$api_obj->push_return_data ( 'message', $api_obj->get_error_message ( $api_obj->return_code ), '' );
	
	$resp = $api_obj->write_return_params_with_json ();
	return $resp;
}
*/


/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback_json($api_obj,$context){
	$resp = $api_obj->json_encode($api_obj->return_data);		
	return $resp;
}


function ez_handle_append_row($context,$index,$row){
	array_push($context->rows,$row);
}


class ez_devices {
	
	private $os;
	private	$row;
	public $api_obj;
	/**
	 * __construct()
	 *
	 * @access public
	 * @param {class} $os The os.
	 */
	public function __construct(os $os) {
		  if(!$os->session_exists()){
         die('Session does not exist!');
      }

		$this->os = $os;
		$this->api_obj = $this->os->log_object;
		//加载多国语言
		$this->api_obj->load_error_xml(sprintf("%s.xml",'devices'));
	} // end __construct()
	

	/*
	 *	查询设备信息列表
	 *  @access public
     * 	@param:
     * 	gu_id phone_no
	 */
	public function d_list() {
		$p_query_value = addslashes ( $_POST ['value'] );
		$p_query_type = addslashes ( $_POST ['type'] );
		
		try {
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
			
			$post_array = array ('a' => 'get_wfs_info', 'value' => $p_query_value, 'type' => $p_query_type );
			
			//访问api,返回json，获取wfs上的数据
			$result = $this->os->log_object->get_from_api ( $this->os->log_object->config->wfs_api_url, $post_array );
			$result_array = get_object_vars ( json_decode ( $result ) );
			if (is_array ( $result_array )) {
				//组织数据
				$data = array (array (//wfs
				'IMEI' => $result_array ['gu_id'], 'PhoneNO' => $result_array ['wfs_attribute'], 'ProductType' => $result_array ['product_type_id'], 'ActiveTime' => substr ( date ( "Y-m-d H:i:s", strtotime ( $result_array ['active_time'] ) ), 0, - 3 ), 'Currency' => $result_array ['currency_type'], 'FreeTime' => $result_array ['free_period'], 'HireTime' => $result_array ['hire_number'], 'InitializeBalance' => $result_array ['balance'], //billing
				'Account' => $result_array ['account'], 'Password' => $result_array ['password'], 'UserPlan' => $result_array ['call_cs'], 'AgentPlan' => $result_array ['agent_cs'], 'Agent' => $result_array ['agent_id'], 'p_qtip' => "View", //icon Text
				'p_icon' => "icon-view-detail", //icon type application_view_detail
				'p_hide' => false )//icon is or not hide
				 );
						
				;
				//$api_obj->push_return_data('',$action_obj->row_count);
				$this->os->log_object->push_return_data ( 'data', $data );
				$this->os->log_object->push_return_data ( 'totalCount', 1 );
				$this->os->log_object->return_code = '101';
			} else {
				if ($p_query_type == '1') {
					$this->os->log_object->return_code = '-102';
				} else if ($p_query_type == '2') {
					$this->os->log_object->return_code = '-103';
				} else {
					$this->os->log_object->return_code = '-105';
				}
			}
			$this->os->log_object->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}
	
	/**** VPN limite speed strat *****/
	public function vpn_control() {
		$p_bandwidth = addslashes ( $_POST ['bandwidth'] ); //限制带宽
		$p_e164 = addslashes ( $_POST ['e164'] );
		$p_p_id = addslashes ( $_POST ['p_id'] );
		$p_remark = addslashes ( $_POST ['remark'] );
		$p_v_id = addslashes ( $_POST ['v_id'] );
		
		try {
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
			
			$array = array ($p_p_id, $p_v_id, $p_e164, ( int ) $p_bandwidth, $p_remark );
			$rdata = $this->os->billing_db->vpn_control ( $array );
			if (is_array ( $rdata )) {
				$this->os->log_object->return_code = $rdata ['p_return'];
			} else {
				//echo '$rdata is not a array';
				$this->os->log_object->return_code = '-105';
			}
			$this->os->log_object->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	
	}
	public function vpn_delete() {
		$p_e164 = addslashes ( $_REQUEST ['e164'] );
		$p_p_id = addslashes ( $_REQUEST ['p_id'] );
		$p_v_id = addslashes ( $_REQUEST ['v_id'] );
		
		try {
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
			
			$array = array ($p_p_id, $p_v_id, $p_e164 );
			$rdata = $this->os->billing_db->vpn_delete ( $array );
			if (is_array ( $rdata )) {
				$this->os->log_object->return_code = $rdata ['p_return'];
			} else {
				//echo '$rdata is not a array';
				$this->os->log_object->return_code = '-105';
			}
			$this->os->log_object->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}
	
	public function vpn_edit() {
		$p_o_e164 = addslashes ( $_REQUEST ['o_e164'] );
		$p_o_pid = addslashes ( $_REQUEST ['o_pid'] );
		$p_o_vid = addslashes ( $_REQUEST ['o_vid'] );
		$p_e164 = addslashes ( $_REQUEST ['e164'] );
		$p_p_id = addslashes ( $_REQUEST ['p_id'] );
		$p_v_id = addslashes ( $_REQUEST ['v_id'] );
		$p_bandwidth = addslashes ( $_REQUEST ['bandwidth'] );
		$p_remark = addslashes ( $_REQUEST ['remark'] );
		
		try {
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
			
			$array = array ($p_o_pid, $p_o_vid, $p_o_e164, $p_p_id, $p_v_id, $p_e164, ( int ) $p_bandwidth, $p_remark );
			$rdata = $this->os->billing_db->vpn_edit ( $array );
			if (is_array ( $rdata )) {
				$this->os->log_object->return_code = $rdata ['p_return'];
			} else {
				//echo '$rdata is not a array';
				$this->os->log_object->return_code = '-105';
			}
			$this->os->log_object->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}
	
	public function vpn_list() {
		$p_id = addslashes ( $_REQUEST ['p_id'] );
		$p_start = empty ( $_REQUEST ['start'] ) ? 0 : $_REQUEST ['start'];
		$p_limit = empty ( $_REQUEST ['limit'] ) ? 15 : $_REQUEST ['limit'];
		try {
			//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
			
			$array = array ('p_id' => $p_id, 'p_limit' => $p_limit, 'p_start' => $p_start );
			$rdata = $this->os->billing_db->vpn_list ( $array );
			if (is_array ( $rdata )) {
				$list_array = array ();
				//遍历数组
				for($i = 0; $i < count ( $rdata ); $i ++) {
					$r_data = array ('E164' => $rdata [$i] ['e164'], 'PID' => $rdata [$i] ['pid'], 'VID' => $rdata [$i] ['vid'], 'BandWidth' => $rdata [$i] ['bandwidth'] / 1024, 'Remark' => $rdata [$i] ['remark'], 'LogTime' => substr ( date ( "Y-m-d H:i:s", strtotime ( $r_data [$i] ['limit_start_time'] ) ), 0, - 3 ), 'p_qtip' => "Edit", //icon Text
					'p_icon' => "icon-edit-record", //icon type application_view_detail
					'p_hide' => false, //icon is or not hide
					'p_qtip2' => "Delete", //icon Text
					'p_icon2' => "icon-cross", //icon type application_view_detail
					'p_hide2' => false, //icon is or not hide
					'p_qtip3' => "Add", //icon Text
					'p_icon3' => "icon-add-table", //icon type application_view_detail
					'p_hide3' => false )//icon is or not hide
					;
					array_push ( $list_array, $r_data );
				}
				$this->os->log_object->return_data ['totalCount'] = $this->os->billing_db->total_count;
				$this->os->log_object->return_data ['data'] = $list_array;
			} else {
				//echo '$rdata is not a array';
				$this->os->log_object->set_return_code ( - 101 );
			}
			$this->os->log_object->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	
	}
	/**** VPN limite speed end *****/
	
	
	/* *
	 * *******设备管理 START***** 
	 * */
	
	public function	get_devices_info(){ 
		$value = empty($_POST['value']) ? '' : $_POST['value'];
		$type = empty($_POST['type']) ? 'is_time' : $_POST['type'];
		$from = empty($_POST['from']) ? '1001-01-01 00:00:00' : $_POST['from'];
		$to = empty($_POST['to']) ? '1001-01-01 00:00:00' : $_POST['to'];
		$resaler = $this->os->sessions['resaler'];
		$resaler = empty($_POST['agent_id']) ? $resaler : $_POST['agent_id'];
		if ( $resaler === 'root') { //agent_id = root,取登录的agent id
			$resaler = $this->os->sessions['resaler'];
		}
		//目前使用运营商级别，以后从Session中获得
		
		 
		if (!isset($resaler)) {
			$resaler = -1;
		}
		
		$offset = empty($_POST['start']) ? 0  : $_POST['start'];
		$limit = empty($_POST['limit']) ? 20 : $_POST['limit'];

		$params = array(
			"value" =>	$value,
			"type"	=>	$type,
			"from"	=>	$from,
			"to"	=> 	$to,
			"resaler"	=>	intval($resaler),
			"offset"	=>	intval($offset),
			"limit"	=>	intval($limit)
		);	
		try {
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
			$rdata = $this->os->billing_db->wfs_get_devices_info ($params);
			$device_array =array();
			if (is_array ( $rdata )) {
			//遍历数组
				for ($i = 0;$i < count($rdata); $i++) {
					if (empty($rdata[$i]['bind_epno'])) {
						$hide = true;
					}else {
						$hide = false;
					}
					
					$r_data = array(
						'id' => $rdata[$i]['bsn'].'-'.$rdata[$i]['imei'],
						'gu_id' => $rdata[$i]['bsn'],
						'imei' => $rdata[$i]['imei'],
						'status' => $rdata[$i]['status'],
						'is_time' => $rdata[$i]['is_time'],
						'os_time' => $rdata[$i]['os_time'],
						'active_time' => $rdata[$i]['active_time'],
						'va_time' => $rdata[$i]['va_time'],
						'vc_time' => $rdata[$i]['vc_time'],
						'charge_plan' => $rdata[$i]['charge_plan'],
						'resaler' => $rdata[$i]['resaler'],
						'bind_pno' => $rdata[$i]['bind_pno'],
						'bind_epno' => $rdata[$i]['bind_epno'],
						'vid' => $rdata[$i]['vid'],
						'pid' => $rdata[$i]['pid'],
						'remark' => $rdata[$i]['remark'],
						'p_qtip' => "View Devices Info",		//icon Text
						'p_icon' => "icon-device-view",	//icon type application_view_detail
						'p_hide' => false,		//icon is or not hide
						'p_qtip2' => "View CDR",		//icon Text
						'p_icon2' => "icon-cdr-view",	//icon type application_view_detail
						'p_hide2' => false,		//icon is or not hide
						'p_qtip3' => "Recharge",		//icon Text
						'p_icon3' => "icon-recharge-add",	//icon type application_view_detail
						'p_hide3' => false,		//icon is or not hide
						'p_qtip4' => "Recharge",		//icon Text
						'p_icon4' => "icon-recharge-view",	//icon type application_view_detail
						'p_hide4' => false,		//icon is or not hide
						'p_qtip5' => "Recharge",		//icon Text
						'p_icon5' => "icon-endpoint-view",	//icon type application_view_detail
						'p_hide5' => $hide		//icon is or not hid
					);
					array_push($device_array,$r_data);
		 		}
				
				$this->os->log_object->return_data ['totalCount'] = $this->os->billing_db->total_count;
				$this->os->log_object->return_data ['devices'] = $device_array;
			} else {
				//echo '$rdata is not a array';
				$this->os->log_object->set_return_code ( - 101 );
			}
			$this->os->log_object->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
		return $rows;
	}
	
	/**
	 *	BEGIN   仓库功能管理 入库
	 */
	
	/**
	 * 产看入库批次号
	 * */
	public function select_stock() {
		$web_params = array (
			'imei' => addslashes ( empty ( $_POST ['imei'] ) ? '?' : $_POST ['imei'] ), 
			'prodtype' => addslashes ( empty ( $_POST ['prodtype'] ) ? 0 : $_POST ['prodtype'] ), 
			'stime' => addslashes ( empty ( $_POST ['stime'] ) ? '1001-01-01 00:00:00' : $_POST ['stime'] ), 
			'etime' => addslashes ( empty ( $_POST ['etime'] ) ? '1001-01-01 00:00:00' : $_POST ['etime'] ), 
			'limit' => addslashes ( $_POST ['limit'] ), 
			'offset' => addslashes ( $_POST ['start'] ) );
		//var_dump($web_params);
		try {
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
			$rdata = $this->os->billing_db->wfs_select_stock ( $web_params );
			if (is_array ( $rdata )) {
				$stock_array =array();
				for ($i = 0;$i < count($rdata); $i++) {
					$r_data = array(
						'warehousing_id' => $rdata[$i]['warehousing_id'],
						'product_type_id' => $rdata[$i]['product_name'],
						'operate_id' => $rdata[$i]['la_active_name'],
						'product_number' => $rdata[$i]['product_number'],
						'model' => $rdata[$i]['model'],
						'warehousing_time' => $rdata[$i]['warehousing_time'],
						'factory_info' => $rdata[$i]['factory_info'],
						'remark' => $rdata[$i]['remark'],
						'store_number' => $rdata[$i]['store_number']
					);
					array_push($stock_array,$r_data);
				}
				
				$this->os->log_object->return_data ['totalCount'] = $this->os->billing_db->total_count;
				$this->os->log_object->return_data ['rows'] = $stock_array;
			} else {
				//echo '$rdata is not a array';
				$this->os->log_object->set_return_code ( - 101 );
			}
			$this->os->log_object->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
		return $rows;
	}

	public function select_prod_type() {
		$record = array ();
		try {
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
			$rdata = $this->os->billing_db->wfs_select_prod_type ();
			if (is_array ( $rdata )) {
				$rows = array ();
				$record = array ();
				$record [] = array ('product_type_id' => 0, 'product_name' => 'All' );
				foreach ( $rdata as $key => $val ) {
					$record [$key] ['product_type_id'] = @$val ['product_type_id'];
					$record [$key] ['product_name'] = @$val ['product_name'];
				}
				$this->os->log_object->return_data ['totalCount'] = $this->os->billing_db->total_count;
				$this->os->log_object->return_data ['rows'] = $record;
			} else {
				//echo '$rdata is not a array';
				$this->os->log_object->set_return_code ( - 101 );
			}
			$this->os->log_object->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}
	
	//仓库功能管理入库相关信息
	public function stock_action() {
		$product_type_id = addslashes ( empty ( $_POST ['product_name'] ) ? '' : $_POST ['product_name'] );
		$operate_id = $this->os->get_member_id();
		$product_number = addslashes ( empty ( $_POST ['product_number'] ) ? '0' : $_POST ['product_number'] );
		$model = addslashes ( empty ( $_POST ['model'] ) ? '' : $_POST ['model'] );
		$factory_info = addslashes ( empty ( $_POST ['factory_info'] ) ? '' : $_POST ['factory_info'] );
		$remark = addslashes ( empty ( $_POST ['remark'] ) ? '' : $_POST ['remark'] );
		$imei = addslashes ( empty ( $_POST ['imei'] ) ? '' : $_POST ['imei'] );
		//下面把IMEI中的的换行、回车换行、分号等全部换为逗号
		$imei = str_replace("\n",',',$imei);
		$imei = str_replace("\r\n",',',$imei);
		$imei = str_replace(";",',',$imei);
		$imei = str_replace(" ",',',$imei);
			
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->api_obj->set_callback_func(get_stock_message_callback, write_response_callback, $this);
		try{
			//更新WFS做出入库
			$carrier_name = $this->api_obj->config->carrier_name;
			$post_array = array(
				"a" => 'wfs_update_stock',
				"imei"	=>	$imei,
				"carrier_name"	=> $carrier_name
			);
			$r = $this->api_obj->get_from_api($this->api_obj->config->wfs_api_url,$post_array);
			$wfs_r = $this->api_obj->json_decode($r);
			if($wfs_r){
				$this->api_obj->return_code =  '101';		//调用WFS API成功
				//返回了json格式的数据
				if(isset($wfs_r->success)){
					$imeis_success = isset($wfs_r->imeis_success)?$wfs_r->imeis_success:"";
					$imeis_fail = isset($wfs_r->imeis_fail)?$wfs_r->imeis_fail:"";
				}else{
					$this->api_obj->write_hint(array(
						msg => $wfs_r->message,
						response => $r
					));
				$imeis_fail = $imei;
				}
			}else{
				//错误，返回的数据不是json格式
				$this->api_obj->return_code =  '-102';		//调用WFS API发生错误
				$this->api_obj->write_hint(array(
					msg => 'Execute wfs api error',
					response => $r
					));
				$imeis_fail = $imei;
			}
				
			$insertArr = array (
				'product_type_id' => $product_type_id, 
				'operate_id' => $operate_id, 
				'product_number' => $product_number, 
				'model' => $model, 
				'factory_info' => $factory_info, 
				'remark' => $remark,
				'imeis' => $imei,
				'imeis_success' => $imeis_success,
				'imeis_fail' => $imeis_fail
			);
			$res = $this->os->billing_db->wfs_insert_stock ( $insertArr );
			$exists = array();
			if($res['p_return'] == 1){
				$warehousing_id = $res ['n_warehousing_id'];
				$imeis_array = explode(',',$imei);
				foreach ($imeis_array as $v){
					if(trim($v)=='')continue;
					$upfileArr = array (
								"bsn"	=>	'ophone_bsn', 
								"imei"	=>	$v, 
								"pno"	=>	'', 
								"vid"	=>	'', 
								"pid"	=>	'', 
								"warehousing_id" => $warehousing_id
							);
					$res = $this->os->billing_db->wfs_upfile_stock ( $upfileArr );
					switch ($res ['p_return']){
					case 1:
						$this->api_obj->return_code = 101;
						break;
					case -1:
						array_push($exists,sprintf('%1$s : %2$s',$v,$this->api_obj->get_error_message( 'stock_action:-101' )));
						$this->api_obj->return_code = -101;
						break;
					case -2:
						array_push($exists,sprintf('%1$s : %2$s',$v,$this->api_obj->get_error_message( 'stock_action:-102' )));
						$this->api_obj->return_code = -102;
						break;
					default:
						array_push($exists,sprintf('%1$s : %2$s',$v,$this->api_obj->get_error_message( 'stock_action:-103' )));
						$this->api_obj->return_code = -103;
						break;
					}
				}
			}else{
				array_push($exists,$this->api_obj->get_error_message( 'stock_action:-105' ));
				$this->api_obj->return_code = -105;
			}
			$this->api_obj->push_return_data('message',join("\r\n",$exists));
		}catch (Exception $e ){
			$this->api_obj->return_code =  '-101';		//执行错误
			$this->api_obj->write_hint(array(
				msg => $e->getMessage()
				));
		}
		$this->api_obj->write_response();
		
		/*
		
		//添加IMEI到本地出库
		$res = $this->os->billing_db->wfs_insert_stock ( $insertArr );
		$flag = $res ['p_return'];
		$warehousing_id = $res ['n_warehousing_id'];
		if ($flag == 1) { //添加入库信息成功
			for($i = 0; $i < count($imei_array); $i ++) {
				if(trim($imei_array [$i])=='')
					continue;		//IMEI为空字符串，跳过
				$gu_id = trim ( $imei_array [$i] );
				$carrier_name = $this->os->log_object->config->carrier_name;
				//判断是否能更新wfs
				$post_array = array(
					"a" => 'wfs_stock_action',
					"imei"	=>	$gu_id, 
					"stock_action" => 'check_wfs',
					"carrier_name"	=> $carrier_name
				);
				$r = $this->os->log_object->get_from_api($this->os->log_object->config->wfs_api_url,$post_array);
				if (strlen($r)>2) {
					$r = 0;
				}		
				if ($gu_id && $r>0) {
					$upfileArr = array (
						"bsn"	=>	'', 
						"imei"	=>	$gu_id, 
						"pno"	=>	'', 
						"vid"	=>	'', 
						"pid"	=>	'', 
						"warehousing_id" => $warehousing_id
					);
					$res = $this->os->billing_db->wfs_upfile_stock ( $upfileArr );
					$res = $res ['p_return'];
					if ($res == 1) { //添加入库信息失败，要做数据库 添加失败 相关信息删除 处理
						//入库成功，更新wfs
						if ($r < 2) {
							$post_array = array(
								"a" => 'wfs_stock_action',
								"imei"	=>	$gu_id, 
								"stock_action" => 'update_wfs',
								"carrier_name"	=> $carrier_name
							);
							$r = $this->os->log_object->get_from_api($this->os->log_object->config->wfs_api_url,$post_array);
							if (strlen($r)>2) {
								$r = 0;
							}
						}
						$this->os->log_object->return_code = 'stock_action:'.'101';
					} else if ($res == - 1) {
						if ($i == 0){
							$exists = sprintf('%1$s : %2$s',$gu_id,$this->api_obj->get_error_message ( 'stock_action:-101' ));
						}else{
							$exists .= sprintf('%1$s : %2$s',$gu_id,$this->api_obj->get_error_message( 'stock_action:-101' ));
						}
						$this->os->log_object->return_code = 'stock_action:-101';
					} else if ($res == - 2) {
						if ($i == 0){
							$exists = sprintf('%1$s : %2$s',$gu_id,$this->api_obj->get_error_message( 'stock_action:-102' ));
						}else{
							$exists .= sprintf('%1$s : %2$s',$gu_id,$this->api_obj->get_error_message( 'stock_action:-102' ));
						}
						$this->os->log_object->return_code = 'stock_action:-102';
					} else {
						if ($i == 0) {
							$exists = sprintf('%1$s : %2$s',$gu_id,$this->api_obj->get_error_message( 'stock_action:-103' ));
						}else{
							$exists .= sprintf('%1$s : %2$s',$gu_id,$this->api_obj->get_error_message( 'stock_action:-103' ));
						}
						$this->os->log_object->return_code = 'stock_action:-103';
					}
				}else{
					if ($i == 0) {
						$exists = sprintf('%1$s : %2$s',$gu_id,$this->api_obj->get_error_message( 'stock_action:-105' ));
					}else{
						$exists .= sprintf('%1$s : %2$s',$gu_id,$this->api_obj->get_error_message( 'stock_action:-105' ));
					}
					$this->os->log_object->return_code = 'stock_action:-105';
				}
				$this->os->log_object->push_return_data ( 'message', $exists );
			}
		} else if ($flag == - 1) {
				//$tips = $this->_lang->outLang('STOCK_F_INSERT');
			$this->os->log_object->return_code = 'stock_action:-105';
		} else {
	 			//$tips = $this->_lang->outLang('DATABASE_EXCEPT');
			$this->os->log_object->return_code = '-105';
		}
		$this->os->log_object->write_response ();*/
	}
	

	//================================END   仓库功能管理 入库==============================
	
	//================================BEGIN   仓库功能管理 出库==============================
	public function select_delivery() {
		$web_params = array (
			'agenter' => addslashes ( empty ( $_POST ['agenter'] ) ? 0 : $_POST ['agenter'] ), 
			'prodtype' => addslashes ( empty ( $_POST ['prodtype'] ) ? 0 : $_POST ['prodtype'] ), 
			'stime' => addslashes ( empty ( $_POST ['stime'] ) ? '1001-01-01 00:00:00' : $_POST ['stime'] ), 
			'etime' => addslashes ( empty ( $_POST ['etime'] ) ? '1001-01-01 00:00:00' : $_POST ['etime'] ),
			'limit' => addslashes ( $_POST ['limit'] ), 'offset' => addslashes ( $_POST ['start'] ) 
		);
		//var_dump($web_params);
		

		try {
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
			$rdata = $this->os->billing_db->wfs_select_delivery ( $web_params );
			if (is_array ( $rdata )) {
				//遍历数组
				$delivery_array = array();
				for ($i = 0;$i < count($rdata); $i++) {
					if (empty($rdata[$i]['agent_name'])) {
						$agent_name = $rdata[$i]['agent_id'];
					}else{
						$agent_name = $rdata[$i]['agent_name'];
					}
					$r_data = array(
						'store_id' => $rdata[$i]['store_id'],
						'product_type_id' => $rdata[$i]['product_name'],
						'operator_id' => $rdata[$i]['la_active_name'],
						'agent_id' => $agent_name,
						'leaves_date' => $rdata[$i]['leaves_date'],
						'remark' => $rdata[$i]['remark'],
						'init_charge' => $rdata[$i]['init_charge']
					);
					array_push($delivery_array,$r_data);
		 		}
				$this->os->log_object->return_data ['totalCount'] = $this->os->billing_db->total_count;
				$this->os->log_object->return_data ['rows'] = $delivery_array;
			} else {
				//echo '$rdata is not a array';
				$this->os->log_object->set_return_code ( - 101 );
			}
			$this->os->log_object->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
		return $rows;
	}
	
	function get_resaler_tree(){
		require_once $this->os->log_object->params['common-path'].'/api_billing_db.php';
		$billing_db = new class_billing_intface($this->api_obj->config, $this->api_obj);
		$this->api_obj->set_callback_func(get_message_callback, write_response_callback_json, $this );
			
		try {
			//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
			//如需要获得返回的余额可以用，$context['p_balance']
			$resaler = empty($_POST['node'])? 0: $_POST['node'];	//目前使用运营商级别，以后从Session中获得
			$offset = empty($_REQUEST['start']) ? 0 : $_REQUEST['start'];
			$count = empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit'];
			if ($resaler !== 'root') {
				$rdata = $billing_db->billing_get_agent_tree($resaler);
			}else{
				$resaler  = $this->os->sessions['resaler'];
				//获取root节点
				if ($resaler == 0) {
					$rdata = array(
						array(
							'Agent_Name' => 'Carrier',
							'AgentID' => '0'
						)
					);
				}else{
					$rdata = $billing_db->billing_get_agent_info($resaler, 0);
				}
			}
			
			if(is_array($rdata)){
				//遍历数组
				for ($i = 0; $i < count($rdata); $i++) {
					$path['text'] = $rdata[$i]['Agent_Name'];
					$path['id']	= $rdata[$i]['AgentID'];
					$resaler = $rdata[$i]['AgentID'];
					$child_array = $billing_db->billing_get_agent_tree($resaler);
					if(count($child_array)> 0){
						$path['leaf']	= false;
						$path['cls']	= 'folder';
					}else{
						$path['leaf']	= true;
						$path['cls']	= 'file';
					}
					// call this function again to display this
					// child's children
					$nodes[] = $path;
		 		}
				$this->os->log_object->return_data = $nodes;
			}else{
				$this->os->log_object->return_code =  '-101';
			}
		} catch ( Exception $e ) {
			$this->os->log_object->return_code =  '-101';
		}
		$this->os->log_object->write_response ();
	}
	
	public function get_agent_name() {
		try {
			//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
			
			$resaler = $this->os->sessions['resaler'];
			$rdata = $this->os->billing_ms_db->billing_get_agent_name ( $resaler );
			if (is_array ( $rdata )) {
				$list_array = array ();
				//遍历数组
				for($i = 0; $i < count ( $rdata ); $i ++) {
					$r_data = array ('agent_id' => $rdata [$i] ['AgentID'], 'agent_name' => $rdata [$i] ['Agent_Name'] );
					array_push ( $list_array, $r_data );
				}
				$this->os->log_object->return_data ['totalCount'] = $this->os->billing_db->total_count;
				$this->os->log_object->return_data ['rows'] = $list_array;
			} else {
				//echo '$rdata is not a array';
				$this->os->log_object->set_return_code ( - 101 );
			}
			$this->os->log_object->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}
	
	public function get_agent_cs() {
		try {
			//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
			
			if (empty($_POST['agent_id'])) {
				$resaler = $this->os->sessions['resaler'];
			}else{
				$resaler = $_POST['agent_id'];
			}
			$rdata = $this->os->billing_ms_db->billing_get_agent_cs ( $resaler );
			if (is_array ( $rdata )) {
				$list_array = array ();
				//遍历数组
				for($i = 0; $i < count ( $rdata ); $i ++) {
					$r_data = array ('agent_cs_id' => $rdata [$i] ['CS_ID'], 'agent_cs_name' => $rdata [$i] ['Name'] );
					array_push ( $list_array, $r_data );
				}
				$this->os->log_object->return_data ['data'] = $list_array;
			} else {
				//echo '$rdata is not a array';
				$this->os->log_object->set_return_code ( - 101 );
			}
			$this->os->log_object->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}
	
	public function get_user_cs() {
		try {
			//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
			if (empty($_POST['agent_id'])) {
				$resaler = $this->os->sessions['resaler'];
			}else{
				$resaler = $_POST['agent_id'];
			}
			$rdata = $this->os->billing_ms_db->billing_get_user_cs ( $resaler );
			if (is_array ( $rdata )) {
				$list_array = array ();
				//遍历数组
				for($i = 0; $i < count ( $rdata ); $i ++) {
					$r_data = array ('user_cs_id' => $rdata [$i] ['CS_ID'], 'user_cs_name' => $rdata [$i] ['Name'] );
					array_push ( $list_array, $r_data );
				}
				$this->os->log_object->return_data ['data'] = $list_array;
			} else {
				//echo '$rdata is not a array';
				$this->os->log_object->set_return_code ( - 101 );
			}
			$this->os->log_object->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}

	//================================END   仓库功能管理 出库==============================
	//仓库功能管理出库相关信息
	public function delivery_action() {		
		$product_type_id = addslashes ( empty ( $_POST ['product_type_id'] ) ? '10001' : $_POST ['product_type_id'] );
		$operate_id = $this->os->get_member_id();
		$agent_id = addslashes ( empty ( $_POST ['agent_id'] ) ? 0 : $_POST ['agent_id'] ); 
		$call_cs = addslashes ( empty ( $_POST ['user_cs_id'] ) ? 0 : $_POST ['user_cs_id'] );
		$agent_cs = addslashes ( empty ( $_POST ['agent_cs_id'] ) ? 0 : $_POST ['agent_cs_id'] ); 
		$balance = addslashes ( empty ( $_POST ['balance'] ) ? 0 : $_POST ['balance'] ); 
		$currency_type = addslashes ( empty ( $_POST ['currency_type'] ) ? 'CNY' : $_POST ['currency_type'] ); 
		$free_period = addslashes ( empty ( $_POST ['free_period'] ) ? 0 : $_POST ['free_period'] );
		$valid_date_no = addslashes ( empty ( $_POST ['valid_date_no'] ) ? 0 : $_POST ['valid_date_no'] ); 
		$hire_number = addslashes ( empty ( $_POST ['hire_number'] ) ? 0 : $_POST ['hire_number'] ); 
		$remark = addslashes ( empty ( $_POST ['remark'] ) ? '' : $_POST ['remark'] ); 
		$imei = addslashes ( empty ( $_POST ['imei'] ) ? '' : $_POST ['imei'] );
		
		$imei_array = explode(',', str_replace("\n",',',$imei)) ;
		
		//获取产品类型前缀
		$product_type_prefix = '5';
		$product_data = $this->os->billing_db->wfs_select_prod_type();
		if (is_array ( $product_data )) {
			foreach ( $product_data as $key => $val ) {
				if ($val['product_type_id'] == $product_type_id) {
					$product_type_prefix = $val['product_type_prefix'];
				}
			}
		} else {
			$product_type_prefix = '5';
		}
		//初始计费方案
		$init_charge = json_encode(
			array(
				"agent_id"=> $agent_id, //代理商ID
				"call_cs" => $call_cs, //用户计费方案
				"agent_cs" => $agent_cs, //代理商计费方案
				"balance" => $balance,	//初始余额
				"currency_type" => $currency_type, //货币类型
				"free_period" => $free_period, //免费时长
				"hire_number" => $hire_number, //租期时长，根据计费方案确定租期类型
				"valid_date_no" => $valid_date_no,//有效期时长，按月算
				"product_type_prefix" => $product_type_prefix //产品前缀
			)
		);
		
		$insertArr = array (
			'product_type_id' => $product_type_id, 
			'operator_id' => $operate_id, 
			'agent_id' => $agent_id, 
			'init_charge' =>  $init_charge,
			'remark' => $remark
		);
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->os->log_object->set_callback_func(get_message_callback, write_response_callback, $this);
		
		//建立出库批次单
		$res = $this->os->billing_db->wfs_insert_leaves_stock ( $insertArr );
		$flag = $res['p_return'];
		$leaves_stock_id = $res ['n_store_id'];
		if ($flag == 1) { //添加入库信息成功
			for($i = 0; $i < count($imei_array); $i ++) {
				$gu_id = trim ( $imei_array [$i] );
				if ($gu_id) {
					//添加设备出库信息
					$devices_array = array (
						"bsn"	=> 	'',
						"imei"	 => $gu_id, 
					    "pno" 	=> 	'', 
					    "vid" 	=> 	'',
					    "pid"	=>	'',
					    "agent_id"	=>	$agent_id, 
					    "charge_plan"	=>	$init_charge,
						"leaves_stock_id" => $leaves_stock_id,
					    "remark"	=>	$remark
					);
					$res = $this->os->billing_db->wfs_insert_account_info ( $devices_array );
					$res = $res ['p_return'];
					if ($res > 0) { //添加入库信息失败，要做数据库 添加失败 相关信息删除 处理
						$this->os->log_object->return_code = 'delivery_action:101';
						continue;
					} else if ($res == -1) {
						$exists .= $gu_id . $this->os->log_object->get_error_message ( 'delivery_action:'.'-101' );
						$this->os->log_object->return_code = 'delivery_action:'.'-101';
						$this->os->log_object->push_return_data ( 'message', $exists );
						continue;
					} else if ($res == -2) {
						$exists .= $gu_id . $this->os->log_object->get_error_message ( 'delivery_action:'.'-102' );
						$this->os->log_object->return_code = 'delivery_action:'.'-102';
						$this->os->log_object->push_return_data ( 'message', $exists );
						continue;
					}else if ($res == -4) {
						$exists .= $gu_id . $this->os->log_object->get_error_message ( 'delivery_action:'.'-104' );
						$this->os->log_object->return_code = 'delivery_action:'.'-104';
						$this->os->log_object->push_return_data ( 'message', $exists );
						continue;
					} else {
						$exists .= $gu_id . $this->os->log_object->get_error_message ( 'delivery_action:'.'-103' );
						$this->os->log_object->return_code = 'delivery_action:'.'-103';
						$this->os->log_object->push_return_data ( 'message', $exists );
						continue;
					}
				}
			}
		} else if ($flag == -1) {
				//$tips = $this->_lang->outLang('STOCK_F_INSERT');
			$this->os->log_object->return_code = 'delivery_action:'.'-106';
		} else {
	 			//$tips = $this->_lang->outLang('DATABASE_EXCEPT');
			$this->os->log_object->return_code = '-105';
		}
		$this->os->log_object->write_response ();
	}
	/*********出库**********/
	
	//查看取消绑定信息
	function DTQuery(){
		$api_obj= $this->os->log_object;
		$p_query_value = addslashes($_REQUEST ['queryValue']);
		$p_query_type = addslashes($_REQUEST ['queryType']);
		if ( $p_query_type == 'Mobile') {
			$p_query_type = 1;
		}else{
			$p_query_type = 2;
		}
		try {
			
			//获取api lib的文件路径
			$action_obj = new api_base_class($api_obj->config,$api_obj);
			$api_obj->set_callback_func(get_message_callback,write_response_callback,$action_obj);
				
			$post_array = array(
				'a' => 'get_wfs_info',
				'value' => $p_query_value,
				'type' => $p_query_type
			);
		
			//访问api,返回json，获取wfs上的数据
			$result = $api_obj->get_from_api($api_obj->config->wfs_api_url,$post_array);
			$p =  strpos($result,'{');
			if( $p > 0){
				$result = substr($result, $p , strlen($result));
			}		
			$result_array =  get_object_vars(json_decode($result));
			
			if (is_array($result_array)) {
				//组织数据
				$data = array(
						//wfs
						'IMEI' => $result_array['gu_id'],
						'PhoneNO' => $result_array['wfs_attribute'],
						'ProductType' => $result_array['product_type_id'],
						'ActiveTime' => substr(date("Y-m-d H:i:s",strtotime($result_array['active_time'])),0,-3),
						'Currency' => $result_array['currency_type'],
						'FreeTime' => $result_array['free_period'],
						'HireTime' => $result_array['hire_number'],
						'InitializeBalance' => $result_array['balance'],
						//billing
						'Account' => $result_array['account'],
						'Password' => $result_array['password'],
						'UserPlan' => $result_array['call_cs'],
						'AgentPlan' => $result_array['agent_cs'],
						'Agent' => $result_array['agent_id']
				);
				//$api_obj->push_return_data('',$action_obj->row_count);
				$api_obj->push_return_data('data',$data);
				$api_obj->push_return_data('totalCount',1);
			}else{
				if ($p_query_type == '1') {
					$api_obj->return_code = 'DTQuery:'.'-102'; 
				}else if ($p_query_type == '2'){
					$api_obj->return_code = 'DTQuery:'.'-103'; 
				}else{
					$api_obj->return_code = '-105'; 
				}
			}
			$api_obj->write_response();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	//	echo '{"data":[{"IMEI":"357586007725077","PhoneNO":"15013879952","ProductType":"10001","ActiveTime":"2010-05-03 13:24","Currency":"CNY","FreeTime":"0","HireTime":"0","InitializeBalance":"50","Account":"5180800218","Password":"2881010","UserPlan":"1026","AgentPlan":"1026","Agent":"10613"}],"totalCount":1,"success":true,"message":"","response-code":"101"}';
	
	
	}
	
	//查询CDR记录
	public function cdr_list(){
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
		
		//获得请求发生的权限级别：运营商=0，代理商〉0，用户为-1
		$resaler = -1;	//目前使用运营商级别，以后从Session中获得
		$offset = empty($_REQUEST['offset'])? (empty($_REQUEST['start']) ? 0 : $_REQUEST['start']) : $_REQUEST['offset'];
		$count = empty($_REQUEST['count'])? (empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit']) : $_REQUEST['count'];
		$is_rt = empty($_REQUEST['type'])?0:1;
		$rdata = $this->os->billing_ms_db->billing_cdr_list($offset,$count,$resaler,$is_rt,
			$_REQUEST['from'],$_REQUEST['to'],$_REQUEST['caller'],$_REQUEST['callee'],$_REQUEST['endpoint']);
		//var_dump($rdata);
		if(is_array($rdata)){
			$cdr_array =array();
			for ($i = 0;$i < count($rdata); $i++) {
				$guid_sn_array = explode('-',$rdata [$i] ['Guid_SN']);
				if (count($guid_sn_array) > 2) {
					$guid_sn = $rdata [$i] ['Guid_SN'];
				}else{
					$guid_sn = $guid_sn_array[1];
				}
				
				$r_data = array(
					'CDRDatetime' => $rdata[$i]['CDRDatetime'],
					'SessionID' => $rdata[$i]['SessionID'],
					'Guid_SN' => $guid_sn,
					'AcctStartTime' => $rdata[$i]['AcctStartTime'],
					'PN_E164' => $rdata[$i]['PN_E164'],
					'CallerID' => $rdata[$i]['CallerID'],
					'CalledID' => $rdata[$i]['CalledID'],
					'SessionTimeMin' => $rdata[$i]['SessionTimeMin'],
					'AcctSessionFee' => $rdata[$i]['AcctSessionFee'] . $rdata[$i]['CurrencyType']
				);
				array_push($cdr_array,$r_data);
			}
				
			$this->os->log_object->return_data['totalCount'] =$this->os->billing_ms_db->total_count;
			$this->os->log_object->return_data['data'] = $cdr_array;
		}else{
			//echo '$rdata is not a array';
			$this->os->log_object->set_return_code(-101);
		}
		$this->os->log_object->write_response();
	} //
	
	public function get_recharge_log(){
		$resaler = $this->os->sessions['resaler'];	//目前使用运营商级别，以后从Session中获得
		$agent = empty($_POST['agent'])? '' : trim($_POST['agent']);
		$endpoint = empty($_POST['endpoint'])? '' : trim($_POST['endpoint']);
		$type = empty($_POST['type'])? '' : trim($_POST['type']);
		$from = empty($_POST['from'])? '' : trim($_POST['from']);
		$to = empty($_POST['to'])? '' : trim($_POST['to']);
							
		$offset = empty($_REQUEST['offset'])? (empty($_REQUEST['start']) ? 0 : $_REQUEST['start']) : $_REQUEST['offset'];
		$count = empty($_REQUEST['count'])? (empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit']) : $_REQUEST['count'];
		
		try {
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
			$rdata = $this->os->billing_ms_db->billing_balance_history($offset,$count,$resaler,$agent,$endpoint,$type,$from,$to);
			//var_dump($rdata);
			if (is_array ( $rdata )) {
				$list_array = array ();
				//遍历数组
				for($i = 0; $i < count ( $rdata ); $i ++) {
					$pno_array = explode('-',$rdata [$i] ['h323id']);
					$guid_sn_array = explode('-',$rdata [$i] ['Guid_SN']);
					if (count($guid_sn_array) > 2) {
						$guid_sn = $rdata [$i] ['Guid_SN'];
					}else{
						$guid_sn = $guid_sn_array[1];
					}

					//获取充值的类型
					//var_dump($this->os->log_object->error_obj->error_array);
					$rc_code_msg = $this->os->billing_ms_db->get_message($rdata [$i] ['RC_Code'],'');
					if (strpos($rc_code_msg,'error') > 0) {
						$rc_code = $rdata [$i] ['RC_Code'];
					}else{
						$rc_code = $rc_code_msg;
					}
					
					$r_data = array (
						'id' => trim($rdata [$i] ['id']), 
						'H_Datetime' => $rdata [$i] ['H_Datetime'], 
						'E164' => $rdata [$i] ['E164'], 
						'Cost' => $rdata [$i] ['Cost'], 
						'RealCost' => $rdata [$i] ['RealCost'], 
						'RC_Code' => $rc_code, 
						'Remark' => $rdata [$i] ['Remark'], 
						'Pno' => $pno_array[1], 
						'Guid_SN' => $guid_sn, 
						'CS_Name' => $rdata [$i] ['CS_Name'], 
						'SourcePin' => $rdata [$i] ['SourcePin'], 
						'Agent_Name' => $rdata [$i] ['Agent_Name'],
						'CurrencyType' => $rdata [$i] ['CurrencyType']
						//'FirstRecharge' => $f_rechrge
					);
					array_push ( $list_array, $r_data );
				}
				$this->os->log_object->return_data ['totalCount'] = $this->os->billing_ms_db->total_count;
				$this->os->log_object->return_data ['data'] = $list_array;
			} else {
				//echo '$rdata is not a array';
				$this->os->log_object->return_code =  '-101';
			}
			$this->os->log_object->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}
	
	//web为用户充值
	public function web_recharge(){
		$resaler = $this->os->sessions['resaler'];	//目前使用运营商级别，以后从Session中获得
		$endpoint = empty($_POST['endpoint'])? '' : trim($_POST['endpoint']);
		$pin = empty($_POST['pin'])? '' : trim($_POST['pin']);
		$pwd = empty($_POST['pwd'])? '' : trim($_POST['pwd']);
		if ($_POST['balance'] === '0' || $_POST['balance'] === 0) {
			$balance = '0';
		}else{
			$balance = empty($_POST['balance'])? '987654321' : trim($_POST['balance']);
		}
		$remark = empty($_POST['remark'])? '' : trim($_POST['remark']);

		require_once $this->api_obj->params['common-path'].'/api_billing_mssql.php';
		$billing_msdb = new class_billing_db($this->api_obj->config->billing_db_config, $this->api_obj);
		$this->os->log_object->set_callback_func ( get_recharge_message_callback, write_response_callback, $this );
		
		try {
			$rani = $billing_msdb->billing_get_ani_account(array(
				"caller" => $endpoint 		//使用全国码的电话号码
				));
			if(is_array($rani) && isset($rani['EndpointNo'])){
				$pin = $rani['EndpointNo'];
				$pass = $rani['Password'];
			}
			//获取充值卡账号和密码
			$recharge_array = array(
			    'rpin' => $pin,
			    'rpass'=> $pwd,
			    'pin'  => $endpoint,
				'value' => $balance
			);
			//echo "rpin=$sourcePin&&rpass=$pinPass";
			$rdata = $billing_msdb->billing_recharge_balance($recharge_array);
			//var_dump($rdata);
			if(is_array($rdata)){
					//$api_obj->return_code = $rdata['p_return'];
					if(empty($rdata['h323_return_code']))
				    {
						$this->api_obj->return_code = 'recharge:'.$rdata['ReturnValue'];
				    }else{
						$this->api_obj->return_code = 'recharge:'.$rdata['h323_return_code'];
				     }
				  
				    if(empty($rdata['reCurrencyType']) || $rdata['reCurrencyType'] == 'CNY' || $rdata['reCurrencyType'] == 'CYN')
			        {
			     	   $rdata['reCurrencyType'] = 'CNY';
			        }
			        
			        //$api_obj->write_hint(array_to_string(',', $rdata));
			  
		            //本次充值金额
				    $this->api_obj->push_return_data('reBalance',empty($rdata['reBalance'])?'':
					"\r\n".sprintf($billing_msdb->get_message('recharge:302'),$rdata['reBalance'],$rdata['reCurrencyType']));
				    
				    //当前总余额
				    $this->api_obj->push_return_data('reNewBalance',empty($rdata['reNewBalance'])?'':
					"\r\n".sprintf($billing_msdb->get_message('recharge:303'),$rdata['reNewBalance'],$rdata['reCurrencyType']));
				    
				    $this->api_obj->push_return_data('rePINBalance',empty($rdata['rePINBalance'])?'':
					"\r\n".sprintf($billing_msdb->get_message('recharge:304'),$rdata['rePINBalance'],$rdata['reCurrencyType']));
				    
				    //$this->api_obj->push_return_data('VP',empty($rdata['VP'])?'':
					//"\r\n".sprintf($billing_msdb->get_message('recharge:304'),$rdata['VP']));
				    
					//$api_obj->push_return_data('FreeDuration',$rdata['reCurrencyType']);
					$this->api_obj->push_return_data('reCurrencyType',$rdata['reCurrencyType']);	
					//$api_obj->push_return_data('ChargePlan',$rdata['re_rate']);//费率
					//$api_obj->push_return_data('HP',$rdata['reValidPeriod']);
					
			}
			//写返回的信息
			$this->api_obj->write_response();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}
	
	public function get_ep_list(){
		require_once $this->api_obj->params['common-path'].'/api_billing_mssql.php';
		$billing_msdb = new class_billing_db($this->api_obj->config->billing_db_config, $this->api_obj);
		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$billing_msdb);
		
		//获取api lib的文件路径
		//获得请求发生的权限级别：运营商=0，代理商〉0，用户为-1
		$resaler = $this->os->sessions['resaler'];	//目前使用运营商级别，以后从Session中获得
		$offset = empty($_REQUEST['offset'])? (empty($_REQUEST['start']) ? 0 : $_REQUEST['start']) : $_REQUEST['offset'];
		$count = empty($_REQUEST['count'])? (empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit']) : $_REQUEST['count'];
		
		$rdata = $billing_msdb->billing_endpoint_get_list($offset,$count,-1,$_REQUEST['type'],$_REQUEST['status'],
			$_REQUEST['endpoint']);
		if(is_array($rdata)){
			//var_dump($rdata);
			if ($rdata[0]['HireDuration'] < 0 || $rdata[0]['HirePeriod'] <= 1) {
				$rdata[0]['HireDuration'] = 0;
			}
			$this->os->log_object->return_data['totalCount'] = $billing_msdb->total_count;
			$this->os->log_object->return_data['data'] = $rdata[0];
		}else{
			//echo '$rdata is not a array';
			$this->os->log_object->return_code = '-101';
		}
	
		$this->os->log_object->write_response();	
	}
	
	public function edit_ep_info(){
		require_once $this->api_obj->params['common-path'].'/api_billing_mssql.php';
		$billing_msdb = new class_billing_db($this->api_obj->config->billing_db_config, $this->api_obj);
		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$billing_msdb);
		
		//获取api lib的文件路径
		//获得请求发生的权限级别：运营商=0，代理商〉0，用户为-1
		$resaler = $this->os->sessions['resaler'];	//目前使用运营商级别，以后从Session中获得
		$e164 = $_REQUEST['e164'];
		if (empty($e164 )) {
			$this->os->log_object->return_code = '-110';
			$this->os->log_object->write_response();
			exit();
		}
		$user_cs_id = empty($_REQUEST['user_cs_id']) ? 0 : $_REQUEST['user_cs_id'];
		if (!is_int(intval($user_cs_id))) {
			$user_cs_id = 0;
		}
		$status = empty($_REQUEST['value']) ? 0: $_REQUEST['value'];
		if (!is_int(intval($status))) {
			$status = 0;
		}
		$is_bind =  empty($_REQUEST['Bind_SN']) ? 0: $_REQUEST['Bind_SN']; 
		if ($is_bind === 'on') {
			$is_bind = 1;
		}else{
			$is_bind = 0;
		}
		$param_array = array(
			'e164' => $e164,
			'agnet_id' => $resaler,
			'user_cs_id' => $user_cs_id,
			'status' => $status,
			'is_bind' => $is_bind
		);
		
		$rdata = $billing_msdb->billing_edit_endpoint_info($param_array);
		if(is_array($rdata)){
			if(empty($rdata['h323_return_code']))
		    {
				$p_return = $rdata['@ReturnValue'];
		    }else{
				$p_return = $rdata['h323_return_code'];
			}
			$this->os->log_object->return_code = 'edit_ep_info:'.$p_return;
		}else{
			//echo '$rdata is not a array';
			$this->os->log_object->return_code = '-105';
		}
	
		$this->os->log_object->write_response();	
	}
	
	/*
	 *	取消设备绑定
	 *  @access public
     * 	@param:
     * 	gu_id phone_no
	 */
	public function dt_unbind() {
		$bsn =  addslashes ( $_POST ['bsn'] );
		$imei =  addslashes ( $_POST ['imei'] );
		$pno=  addslashes ( $_POST ['pno'] );
		
		require_once $this->api_obj->params['common-path'].'/api_billing_pgdb.php';
		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config, $this->api_obj);
		$this->api_obj->set_callback_func ( get_message_callback, write_response_callback, $wfs_db );
			
		try {
			$params_attay = array(
				$bsn,
				$imei,
				$pno
			);
			$rdata = $wfs_db->sp_wfs_web_cancel_bind($params_attay);
			if(is_array($rdata)){
				$this->api_obj->return_code =  'dt_unbind:'.$rdata['p_return'];
			}else{
				$this->api_obj->return_code =  '105';
			}
			$this->api_obj->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}
	
}

?>

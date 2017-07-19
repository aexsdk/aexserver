<?php

/*
	定义错误字符串处理的回调函数，正常情况下错误字符串已经可以按照语言和action取得。
	但是有时候我们需要在字符串中格式一些其他的参数，如：电话号码，姓名什么的。
	例如：
		解除绑定失败的错误字符串：号码%1s与本手机解除绑定失败，代码[%0d]，该手机已经和%2s绑定。
		假设本手机号码在变量$api_obj->params['api_params']['pno']中，已经绑定的号码在
	$api_obj->return_data['p_bind_no']中，那么我们就需要
		function get_message_callback($api_obj,$context,$msg){
			return sprintf($msg,$api_obj->return_code,$api_obj->params['api_params']['pno'],$api_obj->return_data['p_bind_no']);
		}
*/
function get_message_callback($api_obj,$context,$msg){
	return sprintf($msg,$api_obj->return_code);
}

/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj,$context){
	if (strpos($api_obj->return_code,':') >0 ) {
		$return_code = explode(':',$api_obj->return_code);
		$success = $return_code[1] > 0;
	}else{
		$success = $api_obj->return_code > 0;
	}
	$api_obj->push_return_data('success',$success);
	$api_obj->push_return_data('message', $api_obj->get_error_message($api_obj->return_code),'');
	
	$resp = $api_obj->write_return_params_with_json();		
	return $resp;
}

/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback_json($api_obj,$context){
	$resp = $api_obj->json_encode($api_obj->return_data);		
	return $resp;
}


class ez_resalers {

	private $os;
	private $api_obj;
   /**
    * __construct()
    *
    * @access public
    * @param {class} $os The os.
    */
   public function __construct(os $os){
      	if(!$os->session_exists()){
        	die('Session does not exist!');
   		}
		$this->os = $os;
		$this->api_obj = $this->os->log_object;
		//加载多国语言
		$os->log_object->load_error_xml(sprintf("%s.xml",'resalers'));
	} // end __construct()
	
	//通过PID VID获取经销商的产品列表
	public function get_p_list(){
		$p_id = '123456';	
		$v_id =	'123456';	
		$limit = empty($_REQUEST['limit']) ? 15 : $_REQUEST['limit'];		
		$start = empty($_REQUEST['start']) ? 0 : $_REQUEST['start'];
		$query_condition = empty($_POST['QueryCondition']) ? 'null' : $_POST['QueryCondition'];
		$start_time = empty($_POST['StartTime']) ? 'null' : $_POST['StartTime'];
		$end_time = empty($_POST['EndTime']) ? 'null' : $_POST['EndTime'];
		$array = array(
			'a' => 'web_get_p_list_by_pid_vid',
			'p_id' => $p_id,
			'v_id' => $v_id,
			'query_condition' => $query_condition,
			'start_time' => $start_time,
			'end_time' => $end_time,
			'start' => "$start",
			'limit' => "$limit"
		);
		$result = $this->os->log_object->get_from_api($this->os->log_object->config->wfs_api_url, $array);
		echo $result;
	}
	
	
	function get_resaler_list(){
		if (!class_exists('class_billing_db')) {
			require_once $this->os->log_object->params['common-path'].'/api_billing_mssql.php';
			$this->os->billing_ms_db = new class_billing_db($config->billing_db_config, $this->log_object);
		}
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback, $this);
		
		//获得请求发生的权限级别：运营商=0，代理商〉0，用户为-1
		//$this->os->sessions['resaler'];
		$resaler = empty($_POST['node'])? 0: $_POST['node'];	//目前使用运营商级别，以后从Session中获得
		$offset = empty($_REQUEST['start']) ? 0 : $_REQUEST['start'];
		$count = empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit'];
		if ($resaler == 'root') {
			$resaler  = $this->os->sessions['resaler'];
		}
		$rdata = $this->os->billing_ms_db->billing_get_agent_list($offset,$count, $resaler);
		$agent_array =array();
		if(is_array($rdata)){
			//遍历数组
			for ($i = 0;$i < count($rdata); $i++) {
				$r_data = array(
					'AgentID' => $rdata[$i]['AgentID'],
					'Agent_Name' => $rdata[$i]['Agent_Name'],
					'Caption' => $rdata[$i]['Caption'],
					'HireBalance' => $rdata[$i]['HireBalance'],
					'Balance' => $rdata[$i]['Balance'],
					'RealBalance' => $rdata[$i]['RealBalance'],
					'IsReal' => $rdata[$i]['IsReal'],
					'CurrencyType' => $rdata[$i]['CurrencyType'],
					'agtCurrencyType' => $rdata[$i]['agtCurrencyType'],
					'ChargeScheme' => $rdata[$i]['ChargeScheme'],
					'Default_AgentCS' => $rdata[$i]['Default_AgentCS'],
					'Address' => $rdata[$i]['Address'],
					'Leader' => $rdata[$i]['Leader'],
					'Connect' => $rdata[$i]['Connect'],
					'EMail' => $rdata[$i]['EMail'],
					'Prefix' => $rdata[$i]['Prefix'],
					'p_qtip' => "View",		//icon Text
					'p_icon' => "icon-resaler-billing-edit",	//icon type application_view_detail
					'p_hide' => false,		//icon is or not hide
					'p_qtip2' => "Edit",		//icon Text
					'p_icon2' => "icon-resaler-config-edit",	//icon type application_view_detail
					'p_hide2' => false,		//icon is or not hide
					'p_qtip3' => "Edit",		//icon Text
					'p_icon3' => "icon-resaler-chargeplan-edit",	//icon type application_view_detail
					'p_hide3' => false,		//icon is or not hide
					'p_qtip4' => "Edit",		//icon Text
					'p_icon4' => "icon-resaler-oem-edit",	//icon type application_view_detail
					'p_hide4' => false		
				);
				array_push($agent_array,$r_data);
	 		}
			$this->os->log_object->return_data['totalCount'] = count($rdata);
			$this->os->log_object->return_data['data'] = $agent_array;
		}else{
			//echo '$rdata is not a array';
			$this->os->log_object->return_code = '-101';
		}
	
		$this->os->log_object->write_response();
		
	}
	
	function get_resaler_tree(){
		if (!class_exists('class_billing_db')) {
			require_once $this->os->log_object->params['common-path'].'/api_billing_mssql.php';
			$this->os->billing_ms_db = new class_billing_db($config->billing_db_config, $this->api_obj);
		}
		try {
			//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback_json, $this );
			
			$resaler = empty($_POST['node'])? 0: $_POST['node'];	//目前使用运营商级别，以后从Session中获得
			$offset = empty($_REQUEST['start']) ? 0 : $_REQUEST['start'];
			$count = empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit'];
			if ($resaler !== 'root') {
				$rdata = $this->os->billing_ms_db->billing_get_agent_tree($resaler);
			
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
					$rdata = $this->os->billing_ms_db->billing_get_agent_info($resaler, 0);
				}
			}
			
			if(is_array($rdata)){
				//遍历数组
				for ($i = 0; $i < count($rdata); $i++) {
					$path['text'] = $rdata[$i]['Agent_Name'];
					$path['id']	= $rdata[$i]['AgentID'];
					$resaler = $rdata[$i]['AgentID'];
					$child_array = $this->os->billing_ms_db->billing_get_agent_tree($resaler);
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
	
	function get_resaler_info(){
		if (!class_exists('class_billing_db')) {
			require_once $this->os->log_object->params['common-path'].'/api_billing_mssql.php';
			$this->os->billing_ms_db = new class_billing_db($config->billing_db_config, $this->api_obj);
		}
		try {
			//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
			
			$sub_resaler = empty($_POST['agent_id'])? 0: $_POST['agent_id'];	//目前使用运营商级别，以后从Session中获得
		
			if ($sub_resaler == $this->os->sessions['resaler']) {
				$resaler  = 0;
			}else{
				$resaler = $this->os->sessions['resaler'];
			}
			$rdata = $this->os->billing_ms_db->billing_get_agent_info($sub_resaler, $resaler);
			if(is_array($rdata)){
				//遍历数组
				for ($i = 0;$i < count($rdata); $i++) {
					$r_data = array(
						'AgentID' => $rdata[$i]['AgentID'],
						'Agent_Name' => $rdata[$i]['Agent_Name'],
						'Caption' => $rdata[$i]['Caption'],
						'HireBalance' => $rdata[$i]['HireBalance'],
						'Balance' => $rdata[$i]['Balance'],
						'RealBalance' => $rdata[$i]['RealBalance'],
						'IsReal' => $rdata[$i]['IsReal'],
						'CurrencyType' => $rdata[$i]['CurrencyType'],
						'agtCurrencyType' => $rdata[$i]['agtCurrencyType'],
						'ChargeScheme' => $rdata[$i]['ChargeScheme'],
						'Default_AgentCS' => $rdata[$i]['Default_AgentCS'],
						'Address' => $rdata[$i]['Address'],
						'Leader' => $rdata[$i]['Leader'],
						'Connect' => $rdata[$i]['Connect'],
						'EMail' => $rdata[$i]['EMail'],
						'Prefix' => $rdata[$i]['Prefix']
					);
		 		}
				$this->os->log_object->return_data['data'] = $r_data;
				$this->os->log_object->return_code =  '101';
			}else{
				$this->os->log_object->return_code =  '-101';
			}
			
		} catch ( Exception $e ) {
			$this->os->log_object->return_code =  '-101';
		}
		$this->os->log_object->write_response ();
	}
	
	public function edit_resaler_info(){
		if (!class_exists('class_billing_db')) {
			require_once $this->os->log_object->params['common-path'].'/api_billing_mssql.php';
		}
		$billing_db = new class_billing_db($config->billing_db_config, $this->api_obj );
		try {
			//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->api_obj->set_callback_func ( get_message_callback, write_response_callback, $this );
			
			$sub_resaler = empty($_REQUEST['agent_id'])? 0: $_REQUEST['agent_id'];	//目前使用运营商级别，以后从Session中获得
		
			if ($sub_resaler == $this->os->sessions['resaler']) {
				$resaler  = 0;
			}else{
				$resaler = $this->os->sessions['resaler'];
			}
			$rdata = array(
						'Agent_Name' => $_REQUEST['Agent_Name'],
						'Caption' => $_REQUEST['Caption'],
						'CurrencyType' => $_REQUEST['CurrencyType'],
						'agtCurrencyType' => $_REQUEST['agtCurrencyType'],
						'ChargeScheme' => $_REQUEST['ChargeScheme'],
						'Default_AgentCS' => $_REQUEST['Default_AgentCS'],
						'Address' => $_REQUEST['Address'],
						'Leader' => $_REQUEST['Leader'],
						'Connect' => $_REQUEST['Connect'],
						'EMail' => $_REQUEST['EMail'],
						'Prefix' => $_REQUEST['Prefix']
			);
			$resaler_data = array();
			foreach ($rdata as $k => $v){
				$resaler_data[$k] = sprintf("'%s'",mb_convert_encoding(trim($v),'UTF-8','GB2312'));
			}
			$resaler_data['IsReal'] = $_REQUEST['IsReal'];
			$billing_db->billing_edit_agent_info($sub_resaler, $resaler,$resaler_data);
			//if(is_array($rdata)){
				//遍历数组
				$this->api_obj->return_code =  '101';
			//}else{
			//	$this->api_obj->return_code =  '-101';
			//}
			
		} catch ( Exception $e ) {
			$this->api_obj->return_code =  '-101';
		}
		$this->api_obj->write_response ();
	}
	
	//获取代理商计费方案
	public function get_agent_cs() {
		$this->api_obj->set_callback_func ( get_message_callback, write_response_callback, $this );
		$billingdb = new class_billing_db($this->api_obj->config->billing_db_config, $this->api_obj);
		
		$resaler = $this->api_obj['resaler'];
		$rdata = $billingdb->billing_get_agent_cs ( $resaler );
		if (is_array ( $rdata )) {
			$list_array = array ();
			//遍历数组
			for($i = 0; $i < count ( $rdata ); $i ++) {
				$r_data = array ('agent_cs_id' => $rdata [$i] ['CS_ID'], 'agent_cs_name' => $rdata [$i] ['Name'] );
				array_push ( $list_array, $r_data );
			}
			$this->api_obj->return_data ['data'] = $list_array;
		} else {
			//echo '$rdata is not a array';
			$this->api_obj->return_code = -101;
		}
		$this->api_obj->write_response ();
	}
	
	//获取用户计费方案
	public function get_user_cs() {
		try {
			//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
			$resaler = $this->os->sessions['resaler'];
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
	
	
	/**
	 *	creater: lion wang
	 *	time: 2010.12.03
	 *	@param: $agent_id:  agent id
	 *	caption: get agent config info by agent id
	 **/
	public function get_resaler_config(){
		require_once $this->api_obj->params['common-path'].'/api_billing_pgdb.php';
		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config, $this->api_obj);
		
		try {
			//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->api_obj->set_callback_func ( get_message_callback, write_response_callback, $this );
			
			$sub_resaler = empty($_POST['agent_id'])? 0: $_POST['agent_id'];	//目前使用运营商级别，以后从Session中获得
		
			if ($sub_resaler == $this->os->sessions['resaler']) {
				$resaler  = 0;
			}else{
				$resaler = $this->os->sessions['resaler'];
			}
			$rdata = $wfs_db->wfs_get_agent_oem_info($sub_resaler, $resaler);
			if(is_array($rdata)){
				$ophone_config_array = array();
				//遍历数组
				$r_data = array(
					'AgentID' => $rdata['agent_id'],
					'Agent_Name' => $rdata['name'],
					'Note' => $rdata['description'],
					'ChargePlan' => $rdata['description'],
					'Parameters' => $rdata['parameters'],
					'VID' => $rdata['v_id'],
					'PID' => $rdata['p_id'],
					'GroupID' => $rdata['group_id']
				);
				
				$agent_config = json_decode($rdata['parameters']);
				$config_version = 0;
				foreach ($agent_config as $key => $value){
					//获取OPHONE手机配置
					if ($key == 'OPHONE') {
						foreach ($value as $key => $ophone_value){
							$ophone_config = get_object_vars($ophone_value);
							if( ( intval(str_replace('.','0',$ophone_config['version'])) >= intval($config_version)) ){
								$ophone_config_array = $ophone_config;
								$config_version = str_replace('.','0',$ophone_config['version']);
							}
						}
					}
				}
				$r_data =array_merge($ophone_config_array, array(
												'AgentID' => $rdata['agent_id'],
												'Agent_Name' => $rdata['name'],
												'VID' => $rdata['v_id'],
												'PID' => $rdata['p_id'])
				);
			}else{
				$r_data = array(
					'AgentID' => '',
					'Agent_Name' => '',
					'Note' => '',
					'ChargePlan' => '',
					'Parameters' => '',
					'VID' => '',
					'PID' => '',
					'GroupID' => ''
				);
			}
			$this->api_obj->return_data['data'] = $r_data;
			$this->api_obj->return_code =  '101';
		} catch ( Exception $e ) {
			$this->api_obj->return_code =  '-101';
		}
		$this->api_obj->write_response ();
	}
	
	/**
	 *	creater: lion wang
	 *	time: 2010.12.03
	 *	@param: $agent_id:  agent id
	 *	caption: get agent oem info by agent id
	 **/
	function get_resaler_oem_info(){
		require_once $this->api_obj->params['common-path'].'/api_billing_pgdb.php';
		$this->api_obj->write_hint(sprintf("WFS_DB:%s",$this->api_obj->config->wfs_db_config['CONNECT_STRING']));
		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config, $this->api_obj);
		
		try {
			//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->api_obj->set_callback_func ( get_message_callback, write_response_callback, $this );
			$sub_resaler = empty($_POST['agent_id'])? 0: $_POST['agent_id'];	//目前使用运营商级别，以后从Session中获得
			if ($sub_resaler == $this->os->sessions['resaler']) {
				$resaler  = 0;
			}else{
				$resaler = $this->os->sessions['resaler'];
			}
			$rdata = $wfs_db->wfs_get_agent_oem_info($sub_resaler, $resaler);
			if(is_array($rdata)){
				if (empty($rdata['oem'])) {
					$r_data = array();
				}else{
					$oem_array = api_string_to_array($rdata['oem'],',','=');
					if (empty($oem_array['OPTIONS'])) {
						$r_data = array(
							'oem_name' => $oem_array['oem-name'],
							'follow_me' => $oem_array['follow_me'],
							'service_num' => $oem_array['service-num'],
							'ivr_num' => $oem_array['ivr-num'],
							'dtmf_num' => $oem_array['dtmf_num'],
							'mlm_update_url' => $oem_array['mlm_update_url'],
						);
					}else{
						$r_data = array(
							'oem_name' => $oem_array['oem-name'],
							'follow_me' => $oem_array['follow_me'],
							'service_num' => $oem_array['service-num'],
							'ivr_num' => $oem_array['ivr-num'],
							'dtmf_num' => $oem_array['dtmf_num'],
							'mlm_update_url' => $oem_array['mlm_update_url'],
						);
						//options参数
						$a = intval($oem_array['OPTIONS']); //111100110111;//3895
						//"gprs":"on",
						if (($a&1) === 1 ) {
							$r_data = array_merge($r_data, array("gprs"=> '1'));//1111 0011 0110;
						}
						
						//"ip":"on",
						if (($a&2) === 2 ) {
							$r_data = array_merge($r_data, array("ip"=> '1'));//1111 0011 0101;
						}
						
						//"sms":"on"
						if (($a&4) === 4 ) {
							$r_data = array_merge($r_data, array("sms"=> '1'));//1111 0011 0011;
						}
						//"e callback":"on"
						if (($a&8) === 8 ) {
							$r_data = array_merge($r_data, array("ecallback"=> '1'));//1111 0011 0011;
						}
						//"http":"on",
						if (($a&16) === 16 ) {
							$r_data = array_merge($r_data, array("http"=> '1'));//1111 0010 0111
						}
						
						//"mlm":"on",
						if (($a&32) === 32) {
							$r_data = array_merge($r_data, array("mlm"=> '1'));//1111 0001 0111
						}
						
						//"CTC":"on",
						if (($a&256) === 256 ) {
							$r_data = array_merge($r_data, array("CTC"=> '1'));//1110 0011 0111;
						}
						
						//"unicom":"on",
						if (($a&512) === 512  ) {
							$r_data = array_merge($r_data, array("unicom"=> '1'));//1101 0011 0111;
						}
						
						if (($a&1024) === 1024 ) {
							$r_data = array_merge($r_data, array("cmcc"=> '1'));// 1011 0011 0111
						}
						
						//"eload":"on",
						if (($a&2048) === 2048 ) {
							$r_data = array_merge($r_data, array("eload"=> '1'));// 0111 0011 0111; //1847
						}
					}
				}
			}else{
				$r_data = array();
				$this->api_obj->return_code =  '-101';
			}
			$this->api_obj->return_data['data'] = $r_data;
			$this->api_obj->return_code =  '101';
		} catch ( Exception $e ) {
			$this->api_obj->return_code =  '-101';
		}
		$this->api_obj->write_response ();
	}
	
/**
	 *	creater: lion wang
	 *	time: 2010.12.03
	 *	@param: $agent_id:  agent id
	 *	caption: edit agent oem info by agent id
	 **/
	public function edit_resaler_oem_info(){
		require_once $this->api_obj->params['common-path'].'/api_billing_pgdb.php';
		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config, $this->api_obj);
		
		try {
			//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->api_obj->set_callback_func ( get_message_callback, write_response_callback, $this );
			$agent_id = $_POST['agent_id'];	//目前使用运营商级别，以后从Session中获得
			
			//options参数
			$a = 0;//3895; //111100110111;//3895
			//"eload":"on",
			if ($_POST['eload'] == 'on' ) {
				$a = $a | (1<<11);//1847;// 0111 0011 0111; //1847
			}
			if ($_POST['CTC'] == 'on' ) {
				$a = $a | (1<<10);//& 2871;// 1011 0011 0111
			}
			//"unicom":"on",
			if ($_POST['unicom'] == 'on' ) {
				$a = $a | (1<<9);//& 3383;//1101 0011 0111;
			}
			//"CTC":"on",
			if ($_POST['cmcc'] == 'on' ) {
				$a = $a | (1<<8);// 3639;//1110 0011 0111;
			}
			//"mlm":"on",
			if ($_POST['mlm'] == 'on' ) {
				$a = $a | (1<<5);//& 3863;//1111 0001 0111
			}
			//"http":"on",
			if ($_POST['http'] == 'on' ) {
				$a = $a | (1<<54);//& 3879;//1111 0010 0111
			}
			//"sms":"on",
			if ($_POST['sms'] == 'on' ) {
					$a = $a | (1<<2);//& 3891;//1111 0011 0011;
				}
			//"ip":"on",
			if ($_POST['ip'] == 'on' ) {
					$a = $a | (1<<1);//& 3893;//1111 0011 0101;
				}
			//"gprs":"on",
			if ($_POST['gprs'] == 'on' ) {
				$a = $a | 1;//& 3894;//1111 0011 0110;
			}
			//"e callback":"on",
			if ($_POST['ecallback'] == 'on' ) {
				$a = $a |(1<<3);//& 8;//1111 0011 0111;
			}
			$options = $a;
			$oem_config =array(
				"OPTIONS" => $options
			);
			if ($_POST['follow_me'] === 'on' ) {
				 $oem_config = array_merge($oem_config, array("follow_me"=>'1'));
			}
			if (!empty($_POST['oem_name'])) {
				 $oem_config = array_merge($oem_config, array("oem-name"=>$_POST['oem_name']));
			};
			
			if (!empty($_POST['service_num'])) {
				 $oem_config = array_merge($oem_config, array("service-num"=>$_POST['service_num']));
			};
			if (!empty($_POST['ivr_num'])) {
				 $oem_config = array_merge($oem_config, array("ivr-num"=>$_POST['ivr_num']));
			};
			if (!empty($_POST['dtmf_num'])) {
				 $oem_config = array_merge($oem_config, array("dtmf_num"=>$_POST['dtmf_num']));
			};
			if (!empty($_POST['mlm_url'])) {
				 $oem_config = array_merge($oem_config, array("mlm_update_url"=>$_POST['mlm_url']));
			};
			$ome_info = array_to_string(',',$oem_config);
			$params_array = array(
				intval($agent_id),
				$ome_info
			);
			$rdata = $wfs_db->wfs_edit_agent_oem_info($params_array);
			if(is_array($rdata)){
				if ($rdata['p_return'] > 0) {
					$this->api_obj->return_code = 'edit_resaler_oem_info:101';
				}else{
					$this->api_obj->return_code = 'edit_resaler_oem_info:-101';
				}
			}
			$this->api_obj->return_data['data'] = $rdata;
		} catch ( Exception $e ) {
			$this->api_obj->return_code =  '-101';
		}
		$this->api_obj->write_response ();
	}
	
	
	/**
	 *	creater: lion wang
	 *	time: 2010.12.03
	 *	@param: $agent_id:  agent id
	 *	caption: get agent charge plan info by agent id
	 **/
	function get_resaler_charge_plan(){
		require_once $this->api_obj->params['common-path'].'/api_billing_pgdb.php';
		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config, $this->api_obj);
		
		try {
			//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->api_obj->set_callback_func ( get_message_callback, write_response_callback, $this );
			$sub_resaler = empty($_POST['agent_id'])? 0: $_POST['agent_id'];	//目前使用运营商级别，以后从Session中获得
			if ($sub_resaler == $this->os->sessions['resaler']) {
				$resaler  = 0;
			}else{
				$resaler = $this->os->sessions['resaler'];
			}
			$rdata = $wfs_db->wfs_get_agent_oem_info($sub_resaler, $resaler);
			if(is_array($rdata)){
				if (empty($rdata['charge_plan'])) {
					$charge_plan_array = array();
				}else{
					$charge_plan_array = get_object_vars(json_decode($rdata['charge_plan']));
				}
			}else{
				$r_data = array();
				$this->api_obj->return_code =  '101';
			}
			$this->api_obj->return_data['data'] = $charge_plan_array;
			$this->api_obj->return_code =  '101';
		} catch ( Exception $e ) {
			$this->api_obj->return_code =  '-101';
		}
		$this->api_obj->write_response ();
	}
	
/**
	 *	creater: lion wang
	 *	time: 2010.12.03
	 *	@param: $agent_id:  agent id
	 *	caption: edit agent charge plan info by agent id
	 **/
	function edit_resaler_charge_plan(){
		require_once $this->api_obj->params['common-path'].'/api_billing_pgdb.php';
		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config, $this->api_obj);
		
		try {
			//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
			//如需要获得返回的余额可以用，$context['p_balance']
			$this->api_obj->set_callback_func ( get_message_callback, write_response_callback, $this );
			$agent_id = $_POST['agent_id'];	//目前使用运营商级别，以后从Session中获得
			$call_cs = $_POST['user_cs_id'];
			$agent_cs = $_POST['agent_cs_id'];
			$balance = $_POST['balance'];
			$currency_type =  $_POST['currency_type'];
			$free_period = $_POST['free_period'];
			$hire_number= $_POST['hire_number'];
			$valid_date_no = $_POST['valid_date_no'];
			$product_type_prefix = $_POST['pcode'];
			
			$charge_plan =array(
				'agent_id' => $agent_id,
				'call_cs' => $call_cs,
				'agent_cs' => $agent_cs,
				'balance' => $balance,
				'currency_type' => $currency_type,
				'free_period' => $free_period,
				'hire_number' => $hire_number,
				'valid_date_no' => $valid_date_no,
				'product_type_prefix' => $product_type_prefix
			);
			
			$params_array = array(
				intval($agent_id),
				json_encode($charge_plan)
			);
			
			$rdata = $wfs_db->wfs_edit_agent_charge_plan($params_array);
			if(is_array($rdata)){
				if ($rdata['p_return'] > 0) {
					$this->api_obj->return_code = 'edit_resaler_charge_plan:101';
				}else{
					$this->api_obj->return_code = 'edit_resaler_charge_plan:-101';
				}
			}else{
				$r_data = array();
				$this->api_obj->return_code =  '-105';
			}
		} catch ( Exception $e ) {
			$this->api_obj->return_code =  '-101';
		}
		$this->api_obj->write_response ();
	}
	
	public function add_resaler_info(){
		require_once $this->api_obj->params['common-path'].'/api_billing_pgdb.php';
		require_once $this->api_obj->params['common-path'].'/api_billing_mssql.php';
		
		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config, $this->api_obj);
		$billing_msdb = new class_billing_db($this->api_obj->config->billing_db_config, $this->api_obj);
		
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->api_obj->set_callback_func ( get_message_callback, write_response_callback, $this );
			
		
		$params =get_object_vars( json_decode($_POST['data']));
		
		//代理商billing信息
		$base_info = get_object_vars($params['base_info']); 
		
		//上一级代理商
		$parent_agent_name = get_object_vars($params['group_id']);
		$p_id = $parent_agent_name['PID'];
		$v_id = $parent_agent_name['VID'];
		$parent_agent_name = $parent_agent_name["resalerSuperiorAgnet"];
		$parent_agent_id = $_POST['agent_id'];
		
		//默认资费信息
		$charge_info = get_object_vars($params['charge_info']);
		
		//OMEO信息
		$ome_info = get_object_vars($params['ome_info']); 
		//是否修改了OMEO信息
		/*
		 * 	Options参数：
			#define OPHONE_DM_GPRS 0x00000001 //可以使用GPRS预约呼叫
			#define OPHONE_DM_DIALER 0x00000002 //可以使用IP拨号的方式
			#define OPHONE_DM_SMS 0x00000004 //可以使用短信拨号
			
			#define OPHONE_REQ_MODE 0x00000010 //0=GET  1=POST
			#define OPHONE_MLM_MODE 0x00000020 //0=Close MLM 1=Open MLM
			#define OPHONE_REG_FLAG 0x00000040 //
			
			#define OPHONE_RECHARGE_CMCC 0x00000100 // 1=Open CMCC充值
			#define OPHONE_RECHARGE_UNICOM 0x00000200 // 1=Open Unicom
			#define OPHONE_RECHARGE_CTC 0x00000400 // 1=Open CTC
			#define OPHONE_RECAHRGE_ELOAD 0x00000800 // 1=Open ELoad
		 */
		if ($ome_info['is_ome'] < 1) {
			$ome_info = '';
		}else {
			//options参数
			$a = 3895; //111100110111;//3895
			//"eload":"on",
			if ($ome_info['eload'] !== 'on' ) {
				$a = $a & 1847;// 0111 0011 0111; //1847
			}
			if ($ome_info['cmcc'] !== 'on' ) {
				$a = $a & 2871;// 1011 0011 0111
			}
			//"unicom":"on",
			if ($ome_info['unicom'] !== 'on' ) {
				$a = $a & 3383;//1101 0011 0111;
			}
			//"CTC":"on",
			if ($ome_info['CTC'] !== 'on' ) {
				$a = $a & 3639;//1110 0011 0111;
			}
			//"mlm":"on",
			if ($ome_info['mlm'] !== 'on' ) {
				$a = $a & 3863;//1111 0001 0111
			}
			//"http":"on",
			if ($ome_info['http'] !== 'on' ) {
				$a = $a & 3879;//1111 0010 0111
			}
			//"sms":"on",
			if ($ome_info['sms'] !== 'on' ) {
					$a = $a & 3891;//1111 0011 0011;
				}
			//"ip":"on",
			if ($ome_info['ip'] !== 'on' ) {
					$a = $a & 3893;//1111 0011 0101;
				}
			//"gprs":"on",
			if ($ome_info['gprs'] !== 'on' ) {
				$a = $a & 3894;//1111 0011 0110;
			}
			$options = $a;
			$oem_config =array(
				"OPTIONS" => $options
			);
			if ($ome_info['follow_me'] === 'on' ) {
				 $oem_config = array_merge($oem_config, array("follow_me"=>'1'));
			}
			if (!empty($ome_info['oem_name'])) {
				 $oem_config = array_merge($oem_config, array("oem-name"=>$ome_info['oem_name']));
			};
			
			if (!empty($ome_info['service_num'])) {
				 $oem_config = array_merge($oem_config, array("service-num"=>$ome_info['service_num']));
			};
			if (!empty($ome_info['ivr_num'])) {
				 $oem_config = array_merge($oem_config, array("ivr-num"=>$ome_info['ivr_num']));
			};
			if (!empty($ome_info['dtm_num'])) {
				 $oem_config = array_merge($oem_config, array("dtm_num"=>$ome_info['dtm_num']));
			};
			if (!empty($ome_info['mlm_url'])) {
				 $oem_config = array_merge($oem_config, array("mlm_update_url"=>$ome_info['mlm_url']));
			};
			$ome_info = array_to_string(',',$oem_config);
		}
		
		//系统参数配置信息
		$config_info = get_object_vars($params['config_info']);
		//是否修改了系统参数配置信息
		if ($config_info['is_config'] < 1) {
			$config_info = '';
		}else {
			if ($config_info['encrypt'] === 'on') {
				$encrypt = 1;
			}else{
				$encrypt = 0;
			}
			$config_array = new stdClass();
			$config_array->OPHONE = array(
				'invite_url' => $config_info['invite_url'],
				'query_url' => $config_info['query_url'],
				'recharge_url' => $config_info['recharge_url'],
				'action_url' => $config_info['action_url'],
				'active_url' => $config_info['active_url'],
				'invite_url' => $config_info['invite_url'],
				'encrypt' => $encrypt,
				'serect' => $config_info['serect'],
				'version' => $config_info['version']
			);
			$config_info = $this->api_obj->json_encode($config_array);
		}
		
		try {
			//1.添加代理商的billing信息
			$IsReal = empty($base_info["IsReal"]) ? 0 : intval($base_info["IsReal"]);
			if ( $base_info["currency_type"] == 'CNY') {
				$currency_type = 'CYN';
			}else{
				$currency_type = $base_info["currency_type"];
			}
			
			$billing_info_array = array(
				"agent_Name" => trim($base_info["Caption"]),
				"caption" => trim($base_info["Caption"]),
				"address" => trim($base_info["Address"]),
				"leader" => trim($parent_agent_name),
				"currency_type" => trim($currency_type),
				"agt_currency_type" => trim($currency_type),
				"connect" => trim($base_info["EMail"]) ,
				"cs_user_id" => intval($base_info["user_cs_id"]),
				"cs_agent_id" => intval($base_info["agent_cs_id"]),
				"prefix" => trim($base_info["Prefix"]),
				"superior_agnet" => intval($parent_agent_id),
				"is_real" => intval($IsReal),
				"note" =>  trim($base_info["Note"])
			);
			$rdata = $billing_msdb->billing_add_agent($billing_info_array);
			if(is_array($rdata)){
				if(empty($rdata['h323_return_code']))
			    {
					$this->api_obj->return_code = 'add_resaler_info:'.$rdata['@p_return'];
					$p_return = $rdata['@p_return'];
			    }else{
					$this->api_obj->return_code = 'add_resaler_info:'.$rdata['h323_return_code'];
					$p_return = $rdata['h323_return_code'];
				}
			}
			
			//2.添加代理商的设备信息(OME信息、系统配置信息)
			if ($p_return > 0 ) {
				$n_agent_id = $rdata['@n_agent_id'];
				
				//是否添加了默认资费信息
				if ($charge_info['is_charge_plan'] < 1) {
					$charge_info = '';
				}else{
					$charge_info =$this->api_obj->json_encode( 
						array(
							"agent_id" => $n_agent_id,
							"call_cs" => $charge_info['user_cs_id'],
							"agent_cs" => $charge_info['agent_cs_id'],
							"balance" => $charge_info['initialize_balance'],
							"currency_type" => $charge_info['currency_type'],
							"free_period" => $charge_info['free_time'],
							"hire_number" => $charge_info['hire_time'],
							"valid_date_no" => $charge_info['valid_date_no'],
							"product_type_prefix" => $charge_info['pcode']
						)
					);
				}
				
				$devices_info_array = array(
					"agent_id" => $n_agent_id,	 	
					"name" => trim($base_info["Caption"]),	//产品OME名称
					"description" => trim($base_info["Note"]), 	//产品描述
					"charge_plan" => $charge_info,	//产品默认的计费套餐，包括资费套餐ID，预设话费，预设租期，预设免费通话时长、预设套餐有效期等等
					"parameters" => $config_info, //终端产品的扩展参数，格式为JSON，如：代理商信息，激活有效期
					"v_id" => empty($v_id) ? $n_agent_id :$v_id,	 	
					"p_id" => empty($p_id) ? $n_agent_id :$p_id, //终端设备ID
					"group_id" => $parent_agent_id,		
					"oem" => $ome_info
				);
				$rdata = $wfs_db->wfs_add_agent_info($devices_info_array);
				if(is_array($rdata)){
					$this->api_obj->return_code = 'add_wfs_resaler_info:'.$rdata['p_return'];
				}
			}
		} catch ( Exception $e ) {
			$this->api_obj->return_code =  '-101';
		}
		$this->api_obj->write_response ();
		
	}
}
?>
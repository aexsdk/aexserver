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
function get_message_callback($api_obj, $context, $msg) {
	return sprintf ( $msg, $api_obj->return_code );
}

/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj, $context) {
	//$api_obj->write_trace(0,'Run here');
	$success = $api_obj->return_code > 0;
	$api_obj->push_return_data ( 'success', $success );
	$api_obj->push_return_data ( 'message', $api_obj->get_error_message (  $api_obj->return_code ), '' );
	
	$resp = $api_obj->write_return_params_with_json ();
	return $resp;
}

/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback_json($api_obj,$context){
	$resp = $api_obj->json_encode($api_obj->return_data);		
	return $resp;
}



class ez_financial {

	private $os;

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
		//加载多国语言
		$os->log_object->load_error_xml(sprintf("%s.xml",'financial'));
	} // end __construct()

	//获取充值类型
	public function get_recharge_type(){
		try {
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
			$rdata = $this->os->billing_ms_db->billing_get_recharge_type();
			if (is_array ( $rdata )) {
				$this->os->log_object->return_data ['data'] = $rdata;
			} else {
				//echo '$rdata is not a array';
				$this->os->log_object->set_return_code ( - 101 );
			}
			$this->os->log_object->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}
	
	//获取代理商名称
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
	
	public function get_recharge_log(){
		$resaler = $this->os->sessions['resaler'];	//目前使用运营商级别，以后从Session中获得
		$_SESSION['resaler']  = $resaler;
		$agent = empty($_POST['agent'])? $resaler : trim($_POST['agent']);
		$_SESSION['agent']  = $agent;
		$endpoint = empty($_POST['endpoint'])? '' : trim($_POST['endpoint']);
		$_SESSION['endpoint']  = $endpoint;
		$type = empty($_POST['type'])? '' : trim($_POST['type']);
		$_SESSION['type']  = $type;
		$from = empty($_POST['from'])? '' : trim($_POST['from']);
		$_SESSION['from']  = $from;
		$to = empty($_POST['to'])? '' : trim($_POST['to']);
		$_SESSION['to']  = $to;
							
		$offset = empty($_REQUEST['offset'])? (empty($_REQUEST['start']) ? 0 : $_REQUEST['start']) : $_REQUEST['offset'];
		$_SESSION['offset']  = $offset;
		$count = empty($_REQUEST['count'])? (empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit']) : $_REQUEST['count'];
		$_SESSION['count']  = $count;
		
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
					$rc_code_msg = $this->os->billing_ms_db->get_message( $rdata [$i] ['RC_Code'],'');
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
				$this->os->log_object->return_code  =  '- 101';
			}
			$this->os->log_object->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}
	
	function get_resaler_tree(){
		if (!class_exists('class_billing_db')) {
			require_once $this->os->log_object->params['common-path'].'/api_billing_mssql.php';
			$this->os->billing_ms_db = new class_billing_db($config->billing_db_config, $this->log_object);
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
	
	function downloadXML(){
		$filename = 'recharge';
		$resaler = $_SESSION['resaler'];
		$agent =  $_SESSION['agent'];
		$endpoint = $_SESSION['endpoint'];
		$type =  $_SESSION['type'];
		$from = $_SESSION['from'];
		$to	= $_SESSION['to'];
		$offset = $_SESSION['offset'];
		$count = $_SESSION['count'];
		
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
		$rdata = $this->os->billing_ms_db->billing_balance_history($offset,$count,$resaler,$agent,$endpoint,$type,$from,$to);
		$count = $this->os->billing_ms_db->total_count;
		$rdata = $this->os->billing_ms_db->billing_balance_history($offset,$count,$resaler,$agent,$endpoint,$type,$from,$to);
		//var_dump($rdata);
		$header =  "id\tH_Datetime\tIncType\tPin\tE164\tOld_Balance\tCost\tNew_Balance\tRealCost\tUserName\tRC_Code\tRemark\tSourcePin\th323id\tGuid_SN\tCS_Name\tAgent_Name\tCurrencyType";
	        
		if (is_array ( $rdata )) {
	    	
			for ($i = 0; $i < count($rdata); $i++) {
	        	$line = '';
	        	foreach($rdata[$i] as $value) {
	        		if ((!isset($value)) OR ($value == "")) {
	        			$value = "\t";
	        		} else {
	        			$value = str_replace('"', '""', $value);
	        			$value = '"' . $value . '"' . "\t";
	        		}
					$value = stripslashes($value);
					$line .= $value;
	   			}
	    		$data .= trim($line)."\n";
			}
        	$data = str_replace("\r","",$data);

	        if ($data == "") {
	                $data = "\n(0) Records Found!\n";
	        }
	        header("Content-type: application/x-msdownload");
	        header("Content-Disposition: attachment; filename=$filename.xls");
	        header("Pragma: no-cache");
	        header("Expires: 0");
	        print "$header\n$data";
		} else {
			//echo '$rdata is not a array';
			$this->os->log_object->return_code  =  '- 101';
		}
	}
}

?>
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
	//$api_obj->write_trace(0,'Run here');
	$success = $api_obj->return_code > 0;
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



class ez_voip_cdr {
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
		$os->log_object->load_error_xml(sprintf("%s.xml",'voip_cdr'));
	} // end __construct()

		
	public function cdr_list(){
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
		
		//获得请求发生的权限级别：运营商=0，代理商〉0，用户为-1
		$resaler = $this->os->sessions['resaler'];	//目前使用运营商级别，以后从Session中获得
		$resaler = empty($_REQUEST['ageng_id'])?  $resaler  : $_REQUEST['ageng_id'];
		if ($resaler === 'root') {
			$resaler = $this->os->sessions['resaler'];
		}
		$_SESSION['resaler']  = $resaler;
		$offset = empty($_REQUEST['offset'])? (empty($_REQUEST['start']) ? 0 : $_REQUEST['start']) : $_REQUEST['offset'];
		$_SESSION['offset']  = $offset;
		$count = empty($_REQUEST['count'])? (empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit']) : $_REQUEST['count'];
		$_SESSION['count']  = $count;
		$is_rt = empty($_REQUEST['type'])?0:1;
		$_SESSION['is_rt']  = $is_rt;
		$_SESSION['from']  = $_REQUEST['from'];
		$_SESSION['to']  = $_REQUEST['to'];
		$_SESSION['caller']  = $_REQUEST['caller'];
		$_SESSION['callee']  = $_REQUEST['callee'];
		$_SESSION['endpoint']  = $_REQUEST['endpoint'];
		$rdata = $this->os->billing_ms_db->billing_cdr_list($offset,$count,$resaler,$is_rt,
			$_REQUEST['from'],$_REQUEST['to'],$_REQUEST['caller'],$_REQUEST['callee'],$_REQUEST['endpoint']);
		if(is_array($rdata)){
			$list_array = array ();
			//遍历数组
			for($i = 0; $i < count ( $rdata ); $i ++) {
				$guid_sn_array = explode('-',$rdata [$i] ['Guid_SN']);
				if (count($guid_sn_array) > 2) {
					$guid_sn = $rdata [$i] ['Guid_SN'];
				}else{
					$guid_sn = $guid_sn_array[1];
				}
				$r_data = array (
					'CDRDatetime' => trim($rdata [$i] ['CDRDatetime']), 
					'SessionID' => $rdata [$i] ['SessionID'], 
					'AcctStartTime' => $rdata [$i] ['AcctStartTime'], 
					'PN_E164' => $rdata [$i] ['PN_E164'], 
					'CallerID' => $rdata [$i] ['CallerID'], 
					'CallerGWIP' => $rdata [$i] ['CallerGWIP'], 
					'CalledID' => $rdata [$i] ['CalledID'], 
					'Guid_SN' => $guid_sn, 
					'CalledGWIP' => $rdata [$i] ['CalledGWIP'], 
					'AcctSessionTime' => $rdata [$i] ['AcctSessionTime'], 
					'SessionTimeMin' => $rdata [$i] ['SessionTimeMin'],
					'AcctSessionFee' => $rdata [$i] ['AcctSessionFee'],
					'AgentFee' => $rdata [$i] ['AgentFee'],
					'BaseFee' => $rdata [$i] ['BaseFee'],
					'AcctSessionTimeOrg' => $rdata [$i] ['AcctSessionTimeOrg'],
					'SessionTimeOrgMin' => $rdata [$i] ['SessionTimeOrgMin'],
					'TerminationCause' => $rdata [$i] ['TerminationCause'],
					'Remark' => $rdata [$i] ['Remark'],
				);
				array_push ( $list_array, $r_data );
			}
			$this->os->log_object->return_data['totalCount'] =  $this->os->billing_ms_db->total_count;
			$this->os->log_object->return_data['data'] = $list_array;
		}else{
			//echo '$rdata is not a array';
			$this->os->log_object->set_return_code(-101);
		}
		$this->os->log_object->write_response();
	} //
	
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
		$filename = 'CDR';
		$resaler = $_SESSION['resaler'];
		$agent =  $_SESSION['agent'];
		$endpoint = $_SESSION['endpoint'];
		$type =  $_SESSION['type'];
		$from = $_SESSION['from'];
		$to	= $_SESSION['to'];
		$offset = $_SESSION['offset'];
		$count = $_SESSION['count'];
		$is_rt = $_SESSION['is_rt'] ;
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
		$rdata = $this->os->billing_ms_db->billing_cdr_list($offset,$count,$resaler,$is_rt,
			$_SESSION['from'],$_SESSION['to'],$_SESSION['caller'],$_SESSION['callee'],$_SESSION['endpoint']);
		$count = $this->os->billing_ms_db->total_count;
		$rdata = $this->os->billing_ms_db->billing_cdr_list($offset,$count,$resaler,$is_rt,
			$_SESSION['from'],$_SESSION['to'],$_SESSION['caller'],$_SESSION['callee'],$_SESSION['endpoint']);
		$header =  "Guid_SN\tCurrencyType\tSessionID\tCDRDatetime\tAcctStartTime\tPN_E164\tCallerID\tCallerGWIP\tCalleeEndpointNo\tCalledID\tCalledGWIP\tAcctSessionTime\tSessionTimeMin\tAcctSessionFee\tAgentFee\tBaseFee\tTerminationCause\tRemark\tAcctSessionTimeOrg\tSessionTimeOrgMin\tRate\tcaller\tcallee";	        
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
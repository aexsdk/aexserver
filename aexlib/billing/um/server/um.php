<?php

function get_recharge_message_callback($api_obj,$context,$msg){
	return sprintf($msg,$api_obj->return_code,
		$api_obj->return_data['reBalance'],//当前充值金额
		$api_obj->return_data['reNewBalance'],//当前总余额
		//$api_obj->return_data['FreeDuration'],//免费通话时长
		$api_obj->return_data['reCurrencyType']//费率
		//$api_obj->return_data['VP']//话费到期时间
	);
		
}

function crm_get_message_callback($api_obj, $context, $msg) {
	return sprintf ( $msg, $api_obj->return_code );
}


/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function crm_write_response_callback($api_obj, $context) {
	//$api_obj->write_trace(0,'Run here');
	if (strpos($api_obj->return_code,':') >0 ) {
		$return_code = explode(':',$api_obj->return_code);
		$success = $return_code[1] > 0;
	}else{
		$success = $api_obj->return_code > 0;
	}
	
	$api_obj->push_return_data ( 'success', $success );
	$message = $api_obj->get_error_message ( $api_obj->return_code , '' );
	if(!empty($message))
		$api_obj->push_return_data ( 'message', $message);
	$api_obj->push_return_data ( 'response_code', $api_obj->return_code);
	
	$resp = $api_obj->write_return_params_with_json ();
	return $resp;
}


class crm{
	public $os;
	public $config;
	public $api_obj;
	public $sessions;
	
	public function __construct(os $os){
		$this->os = $os;
		$this->config = $os->config;
		$this->api_obj = $this->os->log_object;
		$this->sessions = $this->os->sessions;
		//header('Content-type: text/json');
		//加载多国语言
		$os->log_object->load_error_xml(sprintf("%s.xml",get_class($this)));
		$this->api_obj->set_callback_func ( crm_get_message_callback, crm_write_response_callback, $os);
	} // end __construct()
	
	public function crm_get_custmer_list() {
		$endpoint = $this->os->sessions['userId'];
		$group = empty($_REQUEST['group'])?'%':$_REQUEST['group'];
		$filtter = empty($_REQUEST['filtter'])?'%':$_REQUEST['filtter'];
		$start = empty($_REQUEST['start'])?'0':$_REQUEST['start'];
		$limit = empty($_REQUEST['limit'])?'10':$_REQUEST['limit'];
		
		$billing_db = new class_billing_intface($this->config, $this->api_obj);
		$this->api_obj->set_callback_func ( crm_get_message_callback, crm_write_response_callback, $billing_db );
		try {
			$rdata = $billing_db->crm_get_list($endpoint,$group,$filtter,$start,$limit);
			//var_dump($rdata);
			if (is_array ( $rdata )) {
				$list_array = array ();
				//遍历数组
				for($i = 0; $i < count ( $rdata ); $i ++) {
					$id = $rdata [$i] ['endpoint'].'-'.$rdata [$i] ['cust_group'].'-'.$rdata [$i] ['phoneno'];
					$name = $rdata [$i] ['lastname'].$rdata [$i] ['firstname'];
					$r_data = array (
						'id' => trim($rdata [$i] ['id']), 
						'name' => $name, 
						'phoneno' => $rdata [$i] ['phoneno'], 
						'company' => $rdata [$i] ['company'], 
						'email' => $rdata [$i] ['email'], 
						'cust_group' => $rdata [$i] ['cust_group'], 
						'remark' => $rdata [$i] ['remark'], 
						'sex' => $rdata [$i] ['sex'], 
						'office_no' => $rdata [$i] ['office_no'],
						'fax' => $rdata [$i] ['fax'],
						'alias' => $rdata [$i] ['alias']
					);
					array_push ( $list_array, $r_data );
				}
				$this->api_obj->return_data ['totalCount'] = $billing_db->total_count;
				$this->api_obj_object->return_data ['data'] = $list_array;
			} else {
				//echo '$rdata is not a array';
				$this->api_obj->return_code =  '-101';
			}
			$this->api_obj->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}
	
	public function crm_get_cdr_list() {
		$endpoint = $this->os->sessions['userId'];
		$filtter = empty($_REQUEST['filtter'])?'%':$_REQUEST['filtter'];
		$billingdb = new class_billing_intface($this->config, $this->api_obj);
		$start = empty($_REQUEST['start'])?'0':$_REQUEST['start'];
		$limit = empty($_REQUEST['limit'])?'10':$_REQUEST['limit'];
		$from =  empty($_REQUEST['from'])?'':$_REQUEST['from'];
		$to =  empty($_REQUEST['to'])?'':$_REQUEST['to'];
		$rt =  empty($_REQUEST['type'])?'0':$_REQUEST['type'];
		$billingdb->crm_get_cdr_list($endpoint,$rt,$from,$to,$filtter,$start,$limit);
		$this->api_obj->write_response();
	}
	
	public function crm_get_finance_list() {
		$endpoint = $this->os->sessions['userId'];
		$filtter = empty($_REQUEST['filtter'])?'%':$_REQUEST['filtter'];
		$billingdb = new class_billing_intface($this->config, $this->api_obj);
		$start = empty($_REQUEST['start'])?'0':$_REQUEST['start'];
		$limit = empty($_REQUEST['limit'])?'10':$_REQUEST['limit'];
		$from =  empty($_REQUEST['from'])?'':$_REQUEST['from'];
		$to =  empty($_REQUEST['to'])?'':$_REQUEST['to'];
		//crm_get_finance_list($endpoint,$from,$to,$filter,$start=0,$limit=10)
		$billingdb->crm_get_finance_list($endpoint,$from,$to,$filtter,$start,$limit);
		$this->api_obj->write_response();
	}
	
	public function crm_dialout_upload(){
		//$upload_dir = $api_obj->config->upload_dir;
		$recv = $_REQUEST['tt-recv']; 
		$upload_dir = dirname($this->config->OEMROOT_DIR)."/upload/";
		if (!is_dir($upload_dir)) {
			@mkdir($upload_dir,0777);  
		}
		$allowedType = array(
		    'wav'
		);
		$endpoint = $this->os->sessions['userId'];
		$upload_dir = $upload_dir.$endpoint;
		if (!is_dir($upload_dir)) {
			@mkdir($upload_dir,0777);  
		}
		
		$uploaded = 0;
		$failed = 0;
		$ivr_files = array();
		foreach($_FILES['ivr_file']['name'] as $key => $img) {
		        if ($_FILES['ivr_file']['size'][$key] <= 500000) {
		            // upload file
		            move_uploaded_file($_FILES['ivr_file']['tmp_name'][$key],
		                    $upload_dir.'/'.strtolower($_FILES['ivr_file']['name'][$key]));
		            array_push($ivr_files,strtolower($_FILES['ivr_file']['name'][$key]));
		            $uploaded++;
		        } else {
		            $failed++;
		        }
		}
		if($uploaded >= 1 ){
			$phone_list = str_replace(";",",",$_REQUEST['tt-dialout']);
			$phone_list = str_replace("\r\n",",",$phone_list);
			$phone_list = str_replace("\n",",",$phone_list);
			$phone_list = str_replace("\r",",",$phone_list);
			$phone_list = explode(",",$phone_list);
			$phone_list = array_unique($phone_list);
			$this->api_obj->return_code = '451';
			$this->api_obj->return_data = array();
		}else{
			$this->api_obj->return_code = '-101';
		}
		//$this->api_obj->push_return_data('success',$this->api_obj->return_code > 0);
		//$this->api_obj->push_return_data('message',$this->api_obj->get_error_message($this->api_obj->return_code));
		//FB::info($ivr_files);
		//FB::info($_FILES);
		//$this->api_obj->return_code = $this->crm_dialout($phone_list,$ivr_files);
		//$this->api_obj->set_callback_func ( crm_get_message_callback, crm_write_response_callback, $this);
		//$this->api_obj->write_response();
		$r = new stdClass();
		$r->success = $this->api_obj->return_code > 0;
		$r->message = $this->api_obj->get_error_message($this->api_obj->return_code);
		echo json_encode($r)."\r\n";
	}
	
	public function crm_dialout_test(){
		/*$phone_list = str_replace(";",",",$_REQUEST['phone_list']);
		$phone_list = str_replace("\r\n",",",$phone_list);
		$phone_list = str_replace("\n",",",$phone_list);
		$phone_list = str_replace("\r",",",$phone_list);
		$phone_list = explode(",",$phone_list);
		$phone_list = array_unique($phone_list);*/
		$phone_list = array(
			'13602648557'
			,'13923487001'
			,'13322929968'
		);
		$ivrfiles = array(
			"unicom1.wav",
			"unicom2.wav"
		);
		//var_dump($ivrfiles);
		
		$this->crm_dialout($phone_list,$ivrfiles);
		//$this->api_obj->push_return_data('success',$this->api_obj->return_code > 0);
		//$this->api_obj->push_return_data('message',$this->api_obj->get_error_message($this->api_obj->return_code));
		
		$this->api_obj->write_response();
	}
	
	public function run_cmd($cmd)
	{
		$r = exec($cmd,$out,$rc);
		$msg = sprintf("Command:%s\r\nOutput:%s\r\nReturn code:%s",$cmd,$out,$rc);
		$this->api_obj->write_hint($msg);
		return $rc;
	}
	
	public function crm_dialout($phonelist,$ivrfiles){
		$this->api_obj->set_action('ccqueue');
		$account = array(
			'pin' => $this->os->sessions['userId'],
			'pass' => $this->os->sessions['pass'],
			'rpath' => sprintf(dirname($this->config->OEMROOT_DIR)."/monitor/%s",$this->os->sessions['userId'])
		);
		$callee = isset($_REQUEST['callee'])?$_REQUEST['callee']:"8001000";
		$oem_vid = $this->api_obj->config->carrier_name;
		$ivrpath = "/opt/cb1.8.0-rc3/var/lib/asterisk/sounds/";
		$ast = "/opt/cb1.8.0-rc3/usr/sbin/rasterisk";
		$conf = "/opt/cb1.8.0-rc3/oem/$oem_vid/etc/asterisk/asterisk.conf";
		$server = array(
			//'cmd' => sprintf("scp -P 8664 %s root@221.4.210.94:%s/%s",'%s',$ivrpath,$account['pin'])
			'cmd' => "sox %s -r 8000 -c 1 %s.wav"
		);
		//run_cmd(sprintf('ssh -p 8664 root@221.4.210.94 "mkdir -p %s/%s" >null',$ivrpath,$account['pin']));
		$this->run_cmd(sprintf('mkdir -p %s%s >null',$ivrpath,$account['pin']));
		$ifiles = array();
		//fb($ivrfiles, FirePHP::INFO);
		//fb($ivrpath, FirePHP::INFO);
		foreach ($ivrfiles as $f){
			$f = strtolower($f);
			if (empty($f)) {
				continue;
			}
			//复制语音文件到回拨服务器的指定目录
			$finfo = pathinfo($f);
			//要播放的语音文件，不含扩展名
			$fname = sprintf("ccqueue/%s/%s",$account['pin'],basename($finfo['basename'],".".$finfo['extension']));
			//含完整路径的语音文件名，不含扩展名
			$fn = sprintf("%s%s/%s",$ivrpath,$account['pin'],basename($finfo['basename'],".".$finfo['extension']));
			//上传的语音文件的全路径文件名
			$sf = sprintf(dirname($this->config->OEMROOT_DIR)."/upload/%s/%s",$account['pin'],$f);
			$cmd = sprintf($server['cmd'],$sf,$fn);
			//$this->api_obj->write_hint($cmd);
			//fb($cmd, FirePHP::INFO);
			$this->run_cmd($cmd);
			if($finfo['extension'] == 'wav' or $finfo['extension'] == 'mp3'){
				$cmd = sprintf('%1$s -C %2$s -x "file convert %3$s.%4$s %3$s.g729" ',$ast,$conf,$fn,$finfo['extension']);
				//$this->api_obj->write_hint($cmd);
				//FB::info($cmd);
				//run_cmd(sprintf('ssh -p 8664 root@221.4.210.94 \'%s\'  ',$cmd));
				$this->run_cmd(sprintf('%s',$cmd));
				$cmd = sprintf('%1$s -C %2$s -x "file convert %3$s.%4$s %3$s.gsm" ',$ast,$conf,$fn,$finfo['extension']);
				//FB::info($cmd);
				$this->run_cmd(sprintf('%s',$cmd));
			}
			$ifiles[] = $fname;
		}
		$endfile = array_pop($ifiles);
		$ivrfiles = join('#',$ifiles); //要播放的语音文件
		FB::log($ivrfiles);
		return cc_callback($this->api_obj,$account,$phonelist,$ivrfiles,$endfile,$callee,$server);
	}
	
	public function get_recharge_log(){
		$agent = empty($_POST['agent'])? '' : trim($_POST['agent']);
		$endpoint = empty($_POST['endpoint'])? '' : trim($_POST['endpoint']);
		$type = empty($_POST['type'])? '' : trim($_POST['type']);
		$from = empty($_POST['from'])? '' : trim($_POST['from']);
		$to = empty($_POST['to'])? '' : trim($_POST['to']);
							
		$offset = empty($_REQUEST['offset'])? (empty($_REQUEST['start']) ? 0 : $_REQUEST['start']) : $_REQUEST['offset'];
		$count = empty($_REQUEST['count'])? (empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit']) : $_REQUEST['count'];
		
		require_once $this->api_obj->params['common-path'].'/api_billing_mssql.php';
		$billing_msdb = new class_billing_db($this->api_obj->config->billing_db_config, $this->api_obj);
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->os->log_object->set_callback_func ( crm_get_message_callback, crm_write_response_callback, $billing_msdb );
	
		try {
			$rdata = $billing_msdb->billing_balance_history($offset,$count,-1,-1,$endpoint,$type,$from,$to);
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
	public function crm_recharge(){
		$endpoint = $this->os->sessions['userId'];
		$pin = empty($_POST['pin'])? '' : trim($_POST['pin']);
		$pwd = empty($_POST['pwd'])? '' : trim($_POST['pwd']);
		$remark = empty($_POST['remark'])? '' : trim($_POST['remark']);

		require_once $this->api_obj->params['common-path'].'/api_billing_mssql.php';
		$billing_msdb = new class_billing_db($this->api_obj->config->billing_db_config, $this->api_obj);
		$this->os->log_object->set_callback_func ( get_recharge_message_callback, crm_write_response_callback, $this );
		
		try {
			//获取充值卡账号和密码
			$recharge_array = array(
			    'rpin' => $pin,
			    'rpass'=> $pwd,
			    'pin'  => $endpoint
			);
			//echo "rpin=$sourcePin&&rpass=$pinPass";
			$rdata = $billing_msdb->ophone_recharge_balance($recharge_array);
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
				    
				    //$api_obj->push_return_data('reCurrencyType',empty($rdata['reCurrencyType'])?'':
					//"\r\n".sprintf($billingdb->get_message(500),$rdata['reCurrencyType'],$rdata['reCurrencyType']));
				    
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
	
	//网银为用户充值
	public function crm_ebank(){
		$endpoint = $this->os->sessions['userId'];
		$value = empty($_POST['value'])? '0' : trim($_POST['value']);
		$remark = empty($_POST['remark'])? '' : trim($_POST['remark']);
		$api_url = $this->config->api_url;
		$params = array(
			'a'	=> 'chinabank_cell_send',
			'v_e164'	=>	$endpoint,
			'v_amount'	=>	$value
		);
		//echo $api_url;
		try {
			$result = $this->api_obj->get_from_api($api_url, $params);
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}
	
	public function get_server_member(){
		require_once $this->api_obj->params['common-path'].'/api_billing_db.php';
		$billing_db = new class_billing_intface($this->api_obj->config, $this->api_obj);
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->api_obj->set_callback_func ( crm_get_message_callback, crm_write_response_callback, $billing_db );
		
		
		$offset = empty($_POST['offset'])? (empty($_POST['start']) ? 0 : $_POST['start']) : $_POST['offset'];
		$count = empty($_POST['count'])? (empty($_POST['limit']) ? 10 : $_POST['limit']) : $_POST['count'];
		$endpoint = $this->os->sessions['userId'];
		$param_array = array(
			'endpoint' => $endpoint,
			'offset' => $offset,
			'count' => $count
		);
		try {
			$rdata = $billing_db->crm_get_server_member($param_array);
			//var_dump($rdata);
			if (is_array ( $rdata )) {
				$list_array = array ();
				//遍历数组
				/*
				 *	"uniqueid" INTEGER DEFAULT nextval('tb_queue_member_uniqueid_seq'::regclass) NOT NULL, 
					"membername" VARCHAR(40), 
					"queue_name" VARCHAR(128), 
					"interface" VARCHAR(128), 
					"penalty" INTEGER, 
					"paused" INTEGER, 
					"phone_no" VARCHAR(20) NOT NULL, 
					"customer_id" VARCHAR(50) NOT NULL
				 */
				for($i = 0; $i < count ( $rdata ); $i ++) {
					$r_data = array (
						'id' =>  $rdata [$i] ['uniqueid'],
						'pno' => trim($rdata [$i] ['phone_no']), 
						'customer_name' => $rdata [$i] ['membername'], 
						'customer_id' => $rdata [$i] ['customer_id'], 
						'p_qtip1' => "Delete", //icon Text
						'p_icon1' => "crm-edit-icon", //icon type application_view_detail
						'p_hide1' => false, //icon is or not hide
						'p_qtip2' => "Add", //icon Text
						'p_icon2' => "crm-del-icon", //icon type application_view_detail
						'p_hide2' => false//icon is or not hide
					);
					array_push ( $list_array, $r_data );
				}
				$this->api_obj->return_data ['totalCount'] = $billing_db->billing_ms_db->total_count;
				$this->api_obj->return_data ['data'] = $list_array;
			} else {
				//echo '$rdata is not a array';
				$this->api_obj->return_code =  '-101';
			}
			$this->api_obj->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}
	
	public function add_server_member(){
		require_once $this->api_obj->params['common-path'].'/api_billing_db.php';
		$billing_db = new class_billing_intface($this->api_obj->config, $this->api_obj);
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->api_obj->set_callback_func ( crm_get_message_callback, crm_write_response_callback, $billing_db );
		
		$customer_id = empty($_POST['customer_id'])?  '': $_POST['customer_id'];
		$customer_name = empty($_POST['customer_name'])? '': $_POST['customer_name'];
		$pno= empty($_POST['pno'])? '': $_POST['pno'];
		$endpoint = $this->os->sessions['userId'];
		$param_array = array(
			'customer_id' => $customer_id,
			'customer_name' => $customer_name,
			'pno' => $pno,
			'endpoint' => $endpoint
		);
		try {
			$rdata = $billing_db->crm_add_server_member($param_array);
			//var_dump($rdata);
			if (is_array ( $rdata )) {
				if ($rdata['p_return'] > 0) { //添加路由设置
					$this->api_obj->return_code = 'add_server_member:'. $billing_db->crm_add_routing_for_pno($endpoint,$pno);
				}else{
					$this->api_obj->return_code =  'add_server_member:'.$rdata['p_return'];
				}
			} else {
				$this->api_obj->return_code = '-105';
			}
			$this->api_obj->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}
	
	public function edit_server_member(){
		require_once $this->api_obj->params['common-path'].'/api_billing_db.php';
		$billing_db = new class_billing_intface($this->api_obj->config, $this->api_obj);
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->api_obj->set_callback_func ( crm_get_message_callback, crm_write_response_callback, $billing_db );
		
		$customer_id = empty($_POST['customer_id'])?  '': $_POST['customer_id'];
		$customer_name = empty($_POST['customer_name'])? '': $_POST['customer_name'];
		$pno= empty($_POST['pno'])? '': $_POST['pno'];
		$id= empty($_POST['pno'])? 0: $_POST['id'];
		$endpoint = $this->os->sessions['userId'];
		$param_array = array(
			'id' => $id,
			'customer_id' => $customer_id,
			'customer_name' => $customer_name,
			'pno' => $pno,
			'endpoint' => $endpoint
		);
		try {
			$rdata = $billing_db->crm_edit_server_member($param_array);
			//var_dump($rdata);
			if (is_array ( $rdata )) {
				$this->api_obj->return_code =  'edit_server_member:'.$rdata['p_return'];
			} else {
				$this->api_obj->return_code = '-105';
			}
			$this->api_obj->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}
	
	public function delete_server_member(){
		require_once $this->api_obj->params['common-path'].'/api_billing_db.php';
		$billing_db = new class_billing_intface($this->api_obj->config, $this->api_obj);
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->api_obj->set_callback_func ( crm_get_message_callback, crm_write_response_callback, $billing_db );
		
		$id= empty($_POST['jsonStr'])? 0: $_POST['jsonStr'];
		$id_array = explode(',',$id);
		$endpoint = $this->os->sessions['userId'];
		
		try {
			for($i = 0; $i < count($id_array); $i ++) {
				$param_array = array(
					'id' => $id_array[$i],
					'endpoint' => $endpoint
				);
				$rdata = $billing_db->crm_del_server_member($param_array);
				if (is_array ( $rdata )) {
					$this->api_obj->return_code =  'del_server_member:'.$rdata['p_return'];
				} else {
					$this->api_obj->return_code = '-105';
				}
			}
			//var_dump($rdata);
			$this->api_obj->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
	}
	/*
	 * 为终端提供接口，每个函数名为操作名称，参数在$_REQIEST中。
	 * 返回值必须为json格式
	 * 返回值的方法：
	 * 		当$this->api_obj->return_code大于0时success=true否则返回false
	 * 		$this->api_obj_return_data包含需要返回的附加json数据：
	 * 			如：totalCount ：记录数
	 * 				data : 记录数组
	*/
	public function check_login(){
		if(!$this->os->check_session(true)){
			$this->api_obj->return_code = - 101;
			$this->api_obj->push_return_data('success',false);
			$this->api_obj->push_return_data('msg',$this->os->lang_tr("SessionExprid"));
			$this->api_obj->write_response();
     		return true;
	     }else{
	     	return false;
	     }
	}
	public function geturl(){
		$url = $_REQUEST['url'];
		$ch = curl_init();
		$st = microtime();
		curl_setopt($ch, CURLOPT_URL, $url);//'http://202.134.80.109/eztor_billing/wfs_api/api.php');
		curl_setopt($ch, CURLOPT_POST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,TRUE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 35);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 35);
	   // echo "url=".$url;
	    //var_dump($data);
		$resp =  curl_exec($ch);
		/*$resp = api_string_to_array($resp,"\r\n","=");
		if(isset($_REQUEST['f']) and $_REQUEST['f'] == '1')
		{
			var_dump($resp);
		}*/
		curl_close($ch);
		echo $resp;
	}
	/**
	 * 根据送入的手机号码获取绑定的终端号码，如果没有绑定则返回传入的参数
	 * Enter description here ...
	 * @param unknown_type $caller
	 */
	public function find_ani($billing_db,$caller){
		//billing_get_ani_account		
		/**
		 * 这里有个bug就是如果caller已经注册了则后来的会覆盖之前的信息，以前的帐号就作废了。需要修改billing的存储过程
		 */
		$def_prefix = isset($this->api_obj->config->default_prefix)?$this->api_obj->config->default_prefix:'0086';
		$c = check_phone_number($caller,$def_prefix);
		$ra = $billing_db->billing_get_ani_account(array(
					'caller' => $c
		));
		if(is_array($ra)){
			 if(!empty($ra['EndpointNo']))
			 	$caller = $ra['EndpointNo'];
		}
		return $caller;
	}
	/**
	 * 操作：getcode
	 * 功能：返回验证码
	 */
	public function getcode(){
		require_once (__EZLIB__.'/common/getcode.php');
	}
	/**
	 * 操作：act=register
	 * 功能：注册新帐号
	 * 
	 */
	public function register(){
		$domain = empty($_REQUEST['domain'])?'utone':$_REQUEST['domain'];
		$resaler = empty($_REQUEST['resaler'])?'0':$_REQUEST['resaler'];
		$caller = $_REQUEST['caller'];
		//$pin = $this->sessions['userId'];
		//$pass = $this->sessions['pass'];
		$pin = '';
		$pass = $_REQUEST['pass'];
		if(empty($caller) || empty($pass)){
			$this->api_obj->return_code = -101;
			//$this->api_obj->push_return_data('message','');
			$this->api_obj->write_response();
			return;
		}
		$def_prefix = isset($this->api_obj->config->default_prefix)?$this->api_obj->config->default_prefix:'0086';
		$caller = check_phone_number($caller,$def_prefix);
		$billing_db = new class_billing_db ( $this->api_obj->config->billing_db_config, $this->api_obj );
		
		/**
		 * 这里有个bug就是如果caller已经注册了则后来的会覆盖之前的信息，以前的帐号就作废了。需要修改billing的存储过程
		 */
		$ra = $billing_db->billing_create_account(array(
			'pin' => $pin,
			'caller' => $caller,
			'pass' => $pass,
			'balance' => 0,
			'domain' => $domain,
			'resaler' => $resaler
			));
		if(is_array($ra)){
			$this->api_obj->return_code = $ra['RETURN-CODE'];
			//$api_obj->push_return_data("Active",array_to_string(",",$ra));
			$this->api_obj->push_return_data('RA',$ra);
			if($this->api_obj->return_code <= 0){
			}else if($this->api_obj->return_code == 100){
				$this->api_obj->return_code = 0;
				$this->api_obj->push_return_data('E164',$ra['E164']);
			}else{
				$this->api_obj->return_code = 1;
				$this->api_obj->push_return_data('E164',$ra['E164']);
			}
		}else{
			//返回不是数组
			$this->api_obj->return_code = -100;
		}
		$this->api_obj->write_response();
	}
	/**
	 * 操作：act=login
	 * 功能：用户登录
	 */
	public function login(){
    	$this->api_obj->push_return_data('success',false);
		$this->api_obj->set_action('login');
		$login_sql = 'select * from ez_login_db.sp_n_login($1,$2,$3,$4)';
		$domain = empty($_REQUEST['domain'])?'utone':$_REQUEST['domain'];
		$resaler = empty($_REQUEST['resaler'])?'0':$_REQUEST['resaler'];
		$signed = empty($_REQUEST['signed'])?'0':$_REQUEST['signed'];
		if(!isset($_REQUEST['user'])){
			$this->api_obj->return_code = -101;
			$this->api_obj->push_return_data('message',sprintf("%s",$this->os->lang_tr('login_fail_code')));
			$this->api_obj->push_return_data('msg',sprintf("%s",$this->os->lang_tr('login_fail_code')));
			$this->api_obj->write_response();
			return;
		}
    	$user = isset($_REQUEST['user'])?$_REQUEST['user']:'';
    	if(isset($_SESSION['signed_code']) && $signed != $_SESSION['signed_code'])
		{
			$this->api_obj->return_code = 0;
			$this->api_obj->push_return_data('message',sprintf("%s",$this->os->lang_tr('login_fail_signed_code_error')));
			$this->api_obj->push_return_data('msg',sprintf("%s",$this->os->lang_tr('login_fail_signed_code_error')));
			$_SESSION['signed_code'] = '';
			$this->api_obj->write_response();
			return;
		}
    	if(isset($this->config->encrypt_pass) && $this->config->encrypt_pass)
		{
			$this->os->load('security');
  			$pass = $this->os->security->encrypt($_REQUEST['pass']);
		}else{
			$pass = $_REQUEST['pass'];
		}
		$billingdb = new class_billing_intface($this->config, $this->api_obj);
		
		$login_return = $billingdb->user_login($user,$pass);
		if(is_array($login_return))
		{
			$this->api_obj->push_return_data('user',$login_return);
			$s = array(
					'userId' => $login_return['E164'],
					'user' => $_REQUEST['user'],
					'domain' => $domain,
					'resaler'=> $resaler,
					'group' => $login_return['AgentID'],
					'from'=> $_SERVER['REMOTE_ADDR'],
					'lang' => $_REQUEST['lang'],
					'oemroot' => $this->config->OEMROOT_DIR,
					'pass' => $pass
				);
			$_SESSION['accountInfo'] = $this->os->session_encode($s);
			if(!isset($_SESSION['user_agent'])){
				$_SESSION['user_agent'] = MD5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
			}
			// get our random session id
			$this->os->load('utility');
			$session_id = $this->os->utility->build_random_id();

			$this->api_obj->push_return_data('success',true);
			$this->api_obj->push_return_data('sessionId',$session_id);
		}else{
			$this->api_obj->return_code = -101;
			$this->api_obj->push_return_data('success',false);
			$this->api_obj->push_return_data('message',sprintf($this->os->lang_tr('login_fail_code'),$this->api_obj->return_code));
			$this->api_obj->push_return_data('msg',sprintf($this->os->lang_tr('login_fail_code'),$this->api_obj->return_code));
		}
		$this->api_obj->write_response();
    }
    /**
     * 操作：act=logoff
     */
    public function logout(){
    	$this->logoff();
    }
    public function logoff(){		
		setcookie('sessionId','');
		session_destroy();
		setcookie(session_name(),'',time()-3600);
		$_SESSION = array();
    }
	/**
	 * 帐户的基本信息
			参数
				a : get_account
				pin    :   绑定手机号码或者终端号码,格式为24位以内的数字
				pass : 帐号密码
				key : (可选)如果提供了KEY，则pass为MD5_Encypt(pass,key+pin)。.net和php关于md5加密的代码参见附件
				
			返回值
				E164  ：终端帐号
				Status : 状态 ， 0初始化，1=正常，2=停用
				Balance : 余额   以元为单位的字符串
				Caller  :  绑定号码
				ActiveTime :  字符串的激活时间，创建帐号的时间
				FirstRegister: 第一次注册的时间
				FirstCall : 第一次通话时间
				LastCall : 最后一次通话时间
				Remark : 说明
	 *
	 */
    public function get_account(){
    	$api_obj = $this->api_obj;
    	$pin = $_REQUEST['user'];
		$pass = $_REQUEST['pass'];
    	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
    	
    	if(empty($pin) || empty($pass)){
    		if($this->check_login())return;
    		$pin = empty($pin) ? $this->sessions['userId']:$pin;
    		$pass = empty($pass) ? $this->sessions['pass']:$pass;
    	}
    	$pin = $this->find_ani($billing_db,$pin);
    	$ra = $billing_db->get_endpoint_info ( $pin, $pass );
    	if (is_array ( $ra )) {
    		$api_obj->return_code = 1;
    		$api_obj->push_return_data ( 'data', $ra );
    	} else {
    		//返回不是数组
    		$api_obj->return_code = - 100;
    	}
    	$api_obj->write_response ();
    }
    public function get_cdr(){
    	$api_obj = $this->api_obj;
    	$pin = $_REQUEST['user'];
		$pass = $_REQUEST['pass'];
        if(empty($pin) || empty($pass)){
    		if($this->check_login())return;
    		$pin = empty($pin) ? $this->sessions['userId']:$pin;
    		$pass = empty($pass) ? $this->sessions['pass']:$pass;
    	}
		$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
		$pin = $this->find_ani($billingdb,$pin);
    	//获得请求发生的权限级别：运营商=0，代理商〉0，用户为-1
    	$resaler = -1;	//目前使用运营商级别，以后从Session中获得
    	$offset = empty($_REQUEST['offset'])? (empty($_REQUEST['start']) ? 0 : $_REQUEST['start']) : $_REQUEST['offset'];
    	$count = empty($_REQUEST['count'])? (empty($_REQUEST['limit']) ? 20 : $_REQUEST['limit']) : $_REQUEST['count'];
    	$is_rt = empty($_REQUEST['type'])?0:1;
    	$rdata = $billingdb->billing_cdr_list($offset,$count,$resaler,$is_rt,
    	$_REQUEST['from'],$_REQUEST['to'],$_REQUEST['caller'],$_REQUEST['callee'],$pin);
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
    			
    		$api_obj->return_data['totalCount'] = $billingdb->total_count;
    		$api_obj->return_data['data'] = $cdr_array;
    	}else{
    		//echo '$rdata is not a array';
    		$api_obj->set_return_code(-101);
    	}
    	$api_obj->write_response();
    }
    public function recharge_list(){
    	$api_obj = $this->api_obj;
    	$pin = $_REQUEST['user'];
		$pass = $_REQUEST['pass'];
        if(empty($pin) || empty($pass)){
    		if($this->check_login())return;
    		$pin = empty($pin) ? $this->sessions['userId']:$pin;
    		$pass = empty($pass) ? $this->sessions['pass']:$pass;
    	}
   		$resaler = -1;	//目前使用运营商级别，以后从Session中获得
		$agent = empty($_REQUEST['agent'])? '' : trim($_REQUEST['agent']);
		$type = empty($_REQUEST['type'])? '' : trim($_REQUEST['type']);
		$from = empty($_REQUEST['from'])? '' : trim($_REQUEST['from']);
		$to = empty($_REQUEST['to'])? '' : trim($_REQUEST['to']);
							
		$offset = empty($_REQUEST['offset'])? (empty($_REQUEST['start']) ? 0 : $_REQUEST['start']) : $_REQUEST['offset'];
		$count = empty($_REQUEST['count'])? (empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit']) : $_REQUEST['count'];
		
		try {
			$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
			$endpoint = $this->find_ani($billingdb,$pin);
			$rdata = $billingdb->billing_balance_history($offset,$count,$resaler,$agent,$endpoint,$type,$from,$to);
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
					//var_dump($api_obj->error_obj->error_array);
					$rc_code_msg = $billingdb->get_message($rdata [$i] ['RC_Code'],'');
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
						'CurrencyType' => $rdata [$i] ['CurrencyType'],
						'Old_Balance' => $rdata [$i] ['Old_Balance'],
						'New_Balance' => $rdata [$i] ['New_Balance']
					);
					array_push ( $list_array, $r_data );
				}
				$api_obj->return_data ['totalCount'] = $billingdb->total_count;
				$api_obj->return_data ['data'] = $list_array;
			} else {
				//echo '$rdata is not a array';
				$api_obj->return_code =  '-101';
			}
			$api_obj->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
			$api_obj->return_code = 0;
			$api_obj->return_data = $e->getMessage ();
			$api_obj->write_response ();
		}
    	 
    }
/**
 * 	= 1 Success;

	= -1; Not allow  CallerID in the tb_device, then CallerID couldn't be E164
	= -2; The UserName(E164) is not in the tb_device or state is not 1.	
	= -3; The password is different form OrgPassword, the password is wrong.
    #= -4; The CallerID had been in the  tb_MapAni alreay
	= -5; Insert into tb_MapAni fail
	= -6; Not allow  @CallerID is null or is '' 
 * 
 *
 */
    public function set_caller(){
    	$api_obj = $this->api_obj;
    	$pin = $_REQUEST['user'];
		$pass = $_REQUEST['pass'];
        if(empty($pin) || empty($pass)){
    		if($this->check_login())return;
    		$pin = empty($pin) ? $this->sessions['userId']:$pin;
    		$pass = empty($pass) ? $this->sessions['pass']:$pass;
    	}
   		$caller = $_REQUEST['caller'];
		//$pin = $_REQUEST['pin'];
		//$pass = $_REQUEST['pass'];
		$def_prefix = isset($api_obj->config->default_prefix)?$api_obj->config->default_prefix:'0086';
		$caller = check_phone_number($caller,$def_prefix);
		$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
		$pin = $this->find_ani($billing_db,$pin);
		if(isset($_REQUEST['pin']) && isset($_REQUEST['pass'])){
			//如果请求提供了pin和pass则使用请求中的pin和pass,Pass为使用key+pin作为密钥的MD5加密串
			if (isset($_REQUEST['key']))
				$pass = api_decrypt($_REQUEST['pass'],$_REQUEST['key'].$_REQUEST['pin']);
			else 	
				$pass = $_REQUEST['pass'];
		}
		$ra = $billing_db->billing_bind_cli(array(
			'pin' => $pin,
			'caller' => $caller,
			'pass' => $pass,
			));
		if(is_array($ra)){
			$api_obj->return_code = $ra['RETURN-CODE'];
			$api_obj->push_return_data("bind",$ra);
		}else{
			//返回不是数组
			$api_obj->return_code = -100;
		}
		$api_obj->write_response();
    }

    public function bind_card(){
    	//if(check_login())return;
    	$api_obj = $this->api_obj;
    	$pin = $_REQUEST['user'];
    	$pass = $_REQUEST['pass'];
    	$def_prefix = isset($api_obj->config->default_prefix)?$api_obj->config->default_prefix:'0086';
    	$caller = check_phone_number($pin,$def_prefix);
    	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
    
    	if(!isset($_REQUEST['user'])){
			$this->api_obj->return_code = -101;
			$this->api_obj->push_return_data('message',sprintf("%s",$this->os->lang_tr('login_fail_code')));
			$this->api_obj->push_return_data('msg',sprintf("%s",$this->os->lang_tr('login_fail_code')));
			$this->api_obj->write_response();
			return;
		}
    	
    	if(isset($_REQUEST['user']) && isset($_REQUEST['pass'])){
    		//如果请求提供了pin和pass则使用请求中的pin和pass,Pass为使用key+pin作为密钥的MD5加密串
    		if (isset($_REQUEST['key']))
    		$pass = api_decrypt($_REQUEST['pass'],$_REQUEST['key'].$_REQUEST['pin']);
    		else
    		$pass = $_REQUEST['pass'];
    	}
	    $rani = $msdb->billing_get_ani_account(array(
			"caller" => $pin 		//使用全国码的电话号码
			));
		if(is_array($rani) && isset($rani['EndpointNo'])){
			$pin = $rani['EndpointNo'];
		}else{
			$pin = $user;
		}
    	$iccard = $_REQUEST['iccard'];
    	$idcard = $_REQUEST['idcard'];
    	$name = $_REQUEST['name'];
    	$ra = $billing_db->billing_bind_card(array(
    			'pin' => $pin,
    			'pass' => $pass,
    			'caller' => $caller,
    			'iccard' => $iccard,
    			'idcard' => $idcard,
    			'name' => $name
    	));
    	if(is_array($ra)){
    		$api_obj->return_code = $ra['RETURN-CODE'];
    		$api_obj->push_return_data("bind",$ra);
    	}else{
    		//返回不是数组
    		$api_obj->return_code = -100;
    	}
    	$api_obj->write_response();
    }
    
    public function callback(){
    	$api_obj = $this->api_obj;
    	$pin = $_REQUEST['user'];
		$pass = $_REQUEST['pass'];
        if(empty($pin) || empty($pass)){
    		if($this->check_login())return;
    		$pin = empty($pin) ? $this->sessions['userId']:$pin;
    		$pass = empty($pass) ? $this->sessions['pass']:$pass;
    	}
       	$caller = empty($_REQUEST['caller'])? '' : $_REQUEST['caller'];
    	$callee = empty($_REQUEST['callee'])? '' : $_REQUEST['callee'];
    	$pno = $caller;
    	$async = isset($_REQUEST['async'])?$_REQUEST['async']:'false';
    	//$action= 'invite';
    	$prefix = '0086';
    	
    	//设置回调函数
    	$params = array(
    			'pin' => $pin, 
    			'pass' => trim($pass), 
    			'pno' => check_phone_number ( $pno, $prefix), 
    			'caller' => $caller, 
    			'callerip' => get_request_ipaddr(),
    			'callee' => $callee, 
    			'o' => '00', 
    			'lang' => $api_obj->params ['api_lang'],
    			'prefix' => $prefix
    	);
    	//api_callback($api_obj,$params,$async);
    	api_cb_invite($api_obj,$params,$async);
    	//保存之前的return_data
    	$r = $api_obj->return_data;
    	//清除return_data，返回客户端可控制的return_data，以免太多的垃圾数据
    	$api_obj->return_data = array();
    	$api_obj->write_response();
    	//恢复return_data，以便log日志记录详细的信息
    	$api_obj->return_data += $r;
    }
    
    public function recharge(){
    	$api_obj = $this->api_obj;
    	$caller = $_REQUEST['caller'];
        if(empty($caller)){
    		if($this->check_login())return;
    		$caller = $this->sessions['userId'];
    	}
    	
    	$pin = $_REQUEST['pin'];
		$pass = $_REQUEST['pass'];
		
		$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
		$def_prefix = isset($api_obj->config->default_prefix)?$api_obj->config->default_prefix:'0086';
		$caller = check_phone_number($caller,$def_prefix);
		$api_obj->push_return_data('def_prefix',$def_prefix);
		$api_obj->push_return_data('caller',$caller);
		
		$rp = explode('*',$pin);
		if(Count($rp)>1 && $rp[0] <> '0'){
			$api_obj->return_code = api_ophone_3pay($api_obj,$billing_db,$accessno,$caller,$pin,$pass);
		}else{
			if(Count($rp)>1)$pin = $rp[1];
			$ra = $billing_db->billing_recharge(array(
				'pin' => $caller,
				'caller' => $caller,
				'cardno' => $pin,
				'pass' => $pass
				));
			if(is_array($ra)){
				$api_obj->return_code = $ra['RETURN-CODE'];
				$api_obj->push_return_data('ra',array_to_string(";",$ra));
				if($api_obj->return_code > 0){
					//充值成功
					$api_obj->return_code = 101;
					$api_obj->return_data['r_balance'] = $ra['RemainMoney'];
					$api_obj->return_data['r_currency'] = $ra['CurrencyType'];
					$pno = $caller;
					if(substr($pno,0,3) == '861'){
						$pno = substr($pno,2);
						$r['r_caller'] = $pno;
						$fmt = isset($api_obj->config->sms_msg['recharge'])?$api_obj->config->sms_msg['recharge']:'充值成功，本次充值%s元。';
						$msg = sprintf($fmt,$r['r_balance']);
						$result = send_sms_queue($api_obj,$pno,'*',$msg);
						$r['sms'] = $result;
					}
				}
			}else{
				//返回不是数组
				$api_obj->return_code = -100;
			}
		}	
    }
    
    public function mpass(){
    	$api_obj = $this->api_obj;
    	$pin = $_REQUEST['user'];
		$pass = $_REQUEST['pass'];
    	if(empty($pin) || empty($pass)){
    		if($this->check_login())return;
    		$pin = empty($pin) ? $this->sessions['userId']:$pin;
    		$pass = empty($pass) ? $this->sessions['pass']:$pass;
    	}
       	$new_pass 	=	$_REQUEST['new_pass'];
    	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
    	$pin = $this->find_ani($billingdb,$pin);
    	$billing_params = array(
    		   		'v_pin'          =>	$pin,
    				'v_user_password'=>	$pass,
    				'v_new_password' =>	$new_pass
    	);
    	$billing_return = $billingdb->ophone_modify_billing_password($billing_params);
    	//var_dump($billing_params);
    	if(is_array($billing_return)){
    		if(empty($billing_return['ReturnValue'])){
    			$api_obj->return_code = $billing_return['h323_return_code'];//修改密码成功
    		}else{
    			$api_obj->return_code = $billing_return['ReturnValue'];//修改密码成功
    		}
    		//新密码
    		$api_obj->push_return_data('RePassword',$billing_return['RePassword']);
    	}
    	
    	//写返回的信息
    	$api_obj->write_response();
    }
    
/*
 * 查询费率
 * type: 0 = 软件拨打，1=回拨(WAP和手机)
			caller:主叫号码
			callee:被叫号码
 */
    public function grate(){
    	$api_obj = $this->api_obj;
    	$pin = $_REQUEST['user'];
		$pass = $_REQUEST['pass'];
    	if(empty($pin) || empty($pass)){
    		if($this->check_login())return;
    		$pin = empty($pin) ? $this->sessions['userId']:$pin;
    		$pass = empty($pass) ? $this->sessions['pass']:$pass;
    	}
    	$type 	=	isset($_REQUEST['type']) ? $_REQUEST['type'] : 0;
    	$caller = $_REQUEST['caller'];
    	$callee = $_REQUEST['callee'];
    	
    	//存储过程为合成存储过程
    	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
    	$pin = $this->find_ani($billingdb,$pin);
    	 
    	//把特殊前缀的呼叫先解析好放在数组里
    	$callee_prefix = check_callee_prefix ( $api_obj, $callee );
    	$api_obj->push_return_data("s.prefix",$callee_prefix['prefix']);
    	$api_obj->push_return_data("s.callee",$callee_prefix['callee']);
    	if($callee_prefix['prefix'] == ''){
    		//号码中不包含前缀，先检查本地拨号规则，然后再次进行号码前缀检查
    		//调整$Callee号码以便区分直接拨手机号码和使用0086拨号的区别
    		$callee = add_callee_prefix($callee,$api_obj->config->inner_prefix);
    		//再次检查号码前缀
    		$callee_prefix = check_callee_prefix ( $api_obj, $callee );
    	}
    	$route_db = new class_billing_pgdb($api_obj->config->route_db_config, $api_obj );
    	//首先要做号码替换，调用相关数据的ez_utils_db.sp_build_prefix把号码变换为带地区和运营商标识的号码：国码+运营商+省份代码+区号+'-'+号码
    	//经过变换后的主被叫号码为：
    	//     特殊前缀+':'+国码+':'+运营商代码+省市代码+':'+区号+'：'+电话号码 ，如：A201:86:UGD:756:15602530665
    	$def_prefix = isset($api_obj->params ['api_params'] ['prefix'])?$api_obj->params ['api_params'] ['prefix']:"0086";
    	$caller_s = check_phone_number ( $caller, $def_prefix );
    	$callee_s = check_phone_number ( $callee_prefix ['callee'], $def_prefix );
    	$caller = $route_db->phone_build_prefix ($caller_s);
    	$callee = $route_db->phone_build_prefix ($callee_s);
    	$cp = $callee_prefix ['prefix'] == '' ? '' : $callee_prefix ['prefix'].':';
    	//var_dump($api_obj->params['api_params']);
    	if($type == 0){
    		$callee = 'G' . ($callee == $callee_s ? $cp.$callee : $callee_prefix ['prefix'].':'.$callee);
    		$api_obj->push_return_data('callee',$callee);
    		$billing_return = $billingdb->billing_get_rate(array(
    				'pin' => $pin,
    				'callee' => $callee
    		));
    	}else{
    		//先呼主叫次序不变
    		$caller = 'A' . ($caller == $caller_s ? $cp.$caller : $callee_prefix ['prefix'].':'.$caller);
    		$callee = 'B' . ($callee == $callee_s ? $cp.$callee : $callee_prefix ['prefix'].':'.$callee);
    		$api_obj->push_return_data('caller',$caller);
    		$api_obj->push_return_data('callee',$callee);
    		$billing_return = $billingdb->billing_get_callback_rate(array(
    				'pin' => $pin,
    				'caller' => $caller,
    				'callee' => $callee
    		));
    	}
    	//var_dump($billing_params);
    	if(is_array($billing_return)){
    		//$api_obj->push_return_data('data',$billing_return);
    		if($billing_return['RETURN-CODE']> 0 ){
    			//费率
    			$api_obj->push_return_data('rate',$billing_return['rate']);
    			//费率单位
    			$api_obj->push_return_data('currency_type',$billing_return['currency_type']);
    		}
    		$api_obj->return_code = $billing_return['RETURN-CODE'];
    	}
    	
    	//写返回的信息
    	$api_obj->write_response();
    }
    
    public function send_sms(){
    	require_once (__EZLIB__.'/common/sms_server.php');
    	$pin = $_REQUEST['user'];
		$pass = $_REQUEST['pass'];
    	if(empty($pin) || empty($pass)){
    		if($this->check_login())return;
    		$pin = empty($pin) ? $this->sessions['userId']:$pin;
    		$pass = empty($pass) ? $this->sessions['pass']:$pass;
    	} 
    	$api_obj = $this->api_obj;
    	$caller = $_REQUEST['caller'];
		$msg = $_REQUEST['message'];		
		$result = send_sms_queue($api_obj,$caller,'*',$msg);
		$api_obj->return_code = 1;
		//$api_obj->push_return_data('success','true');
		$api_obj->push_return_data('message',$api_obj->return_data['sms_return']);
    	$api_obj->write_response();
    }
}

?>
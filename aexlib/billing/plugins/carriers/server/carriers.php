<?php
/*
* 虚拟运营商管理类
*    包含的方法：
* 		viewAllCarriers ： 获得运营商列表
* 		get_carrier_info ： 获得单个运营商讯息
* 		edit_carrier ： 保存运营商参数
* 		delete_carrier : 删除运营商
* 
* 		get_products : 获得终端产品列表
* 		get_product : 获得终端产品
* 		edit_product : 保存产品参数
* 		delete_product : 删除产品
* 
* 		get_devices : 获得终端设备列表，(带分页信息)
* 		get_device : 获得终端设备信息
* 		import_devices : 终端设备入库
* 		ostock_devices : 终端设备出库
* 
* 		ophone_update : 终端设备更新配置测试
*/

function ez_handle_append_row($context, $index, $row) {
	array_push ( $context->rows, $row );
	//echo sprintf("Row[%d]=%d<br>",$index,count($context->rows));
//var_dump($context);
}

class Ez_carriers {
	
	private $os;
	public $rows;
	public $api_obj;
	
	/**
	 * __construct()
	 *
	 * @access public
	 * @param {class} $os The os.
	 */
	public function __construct(os $os) {
		if (! $os->session_exists ()) {
			die ( 'Session does not exist!' );
		}
		
		$this->os = $os;
		$this->api_obj = $this->os->log_object;
		//加载多国语言
		$os->log_object->load_error_xml ( sprintf ( "%s.xml", get_class ( $this ) ) );
	} // end __construct()
	

	function do_action($api_object) {
		$fn = __EZLIB__ . "/ophone/modules/api_" . $api_object->params ['api_params'] ['action'] . ".php";
		
		if (file_exists ( $fn )) {
			//action对应的PHP文件存在，包含此文件。在文件中应该包含action的实现
			require_once $fn;
		} else {
			//$fn = dirname(dirname(__FILE__)).'/ezbilling_api/modules/api_".$p_params['api_params']['action'].".php");
			//no this action function
			$api_object->return_code = _DB_NO_ACTION_FILE_;
			$api_object->write_warning ( "No this action file:$fn" );
			exit ();
		}
	}
	
	public function load_carriers() {
		$this->api_obj->set_action ( 'active' );
		do_action ( $this->api_obj );
	}
	
	public function viewAllCarriers() {
		$sql = 'select * from ez_wfs_db.sp_get_carriers()';
		$wfs_db = new class_billing_pgdb ( $this->api_obj->config->wfs_db_config, $this->api_obj );
		$this->rows = array ();
		$wfs_db->exec_query ( $sql, array (), ez_handle_append_row, $this );
		
		if (is_array ( $this->rows )) {
			$this->api_obj->push_return_data ( 'total', count ( $this->rows ) );
			$this->api_obj->push_return_data ( 'success', true );
			$this->api_obj->push_return_data ( 'carriers', $this->rows );
			$this->api_obj->write_response ();
		} else {
			$this->api_obj->write_response ();
		}
	}
	
	public function get_carrier_info() {
		$sql = 'select * from ez_wfs_db.sp_get_carrier_by_id($1)';
		$carrierId = $_REQUEST ['id'];
		$wfs_db = new class_billing_pgdb ( $this->api_obj->config->wfs_db_config, $this->api_obj );
		$carrier = $wfs_db->exec_db_sp ( $sql, array ($carrierId ) );
		if (is_array ( $carrier )) {
			$this->api_obj->push_return_data ( 'total', 1 );
			$this->api_obj->push_return_data ( 'success', true );
			$this->api_obj->push_return_data ( 'carrier', $carrier );
			$this->api_obj->write_response ();
		} else {
			$this->api_obj->write_response ();
		}
	}
	
	public function edit_carrier() {
		//id,name,description,contact,api_params,oem_params,conf_params
		//如果id不存在会创建新的运营商，更新时不更新id，也就是说id已经创建就可以再更改(更改id需要更改好多个关联表)
		$sql = 'select * from ez_wfs_db.sp_edit_carrier($1,$2,$3,$4,$5,$6,$7,$8)';
		$oid = $_REQUEST ['oid'];
		$id = $_REQUEST ['id'];
		$name = $_REQUEST ['name'];
		$description = $_REQUEST ['description'];
		$contact = $_REQUEST ['contact'];
		$api_params = $_REQUEST ['api_params'];
		$oem_params = $_REQUEST ['oem_param'];
		$conf_params = $_REQUEST ['params'];
		
		$wfs_db = new class_billing_pgdb ( $this->api_obj->config->wfs_db_config, $this->api_obj );
		$this->api_obj->push_return_data ( 'success', false );
		$wfs_db->exec_proc ( $sql, array ($oid, $id, $name, $description, $contact, $api_params, $oem_params, $conf_params ) );
		$this->api_obj->push_return_data ( 'success', true );
		$this->api_obj->return_code = 101;
		$this->api_obj->push_return_data ( 'msg', sprintf ( 'Save parameters for %s success!', id ) );
		$this->api_obj->write_response ();
	}
	
	public function delete_carrier() {
		$sql = 'select * from ez_wfs_db.sp_delete_carrier($1)';
		$carrierId = $_REQUEST ['id'];
		$wfs_db = new class_billing_pgdb ( $this->api_obj->config->wfs_db_config, $this->api_obj );
		$this->api_obj->push_return_data ( 'success', false );
		$wfs_db->exec_proc ( $sql, array ($carrierId ) );
		$this->api_obj->push_return_data ( 'success', true );
		$this->api_obj->write_response ();
	}
	
	public function get_products() {
		$filter = $_REQUEST ['filter'];
		if($filter=='')$filter='%';
		$sql = 'select (vid||\'-\'||pid) as id, * from ez_wfs_db.tb_products ';
		$wfs_db = new class_billing_pgdb ( $this->api_obj->config->wfs_db_config, $this->api_obj );
		$this->rows = array ();
		$wfs_db->exec_query ( $sql, array (), ez_handle_append_row, $this );
		if (is_array ( $this->rows )) {
			$this->api_obj->push_return_data ( 'total', count($this->rows) );
			$this->api_obj->push_return_data ( 'success', true );
			$this->api_obj->push_return_data ( 'data', $this->rows );
			$this->api_obj->write_response ();
		} else {
			$this->api_obj->write_response ();
		}
	}
	
	public function get_product() {
		$vid = $_REQUEST ['VID'];
		$pid = $_REQUEST ['PID'];
		$sql = 'select * from ez_wfs_db.tb_products where pid = $1, vid=$2';
		$wfs_db = new class_billing_pgdb ( $this->api_obj->config->wfs_db_config, $this->api_obj );
		$row = $wfs_db->exec_db_dp ( $sql, array ($vid, $pid ) );
		if (is_array ( $row )) {
			$this->api_obj->push_return_data ( 'total', 1 );
			$this->api_obj->push_return_data ( 'success', true );
			$this->api_obj->push_return_data ( 'data', $row );
			$this->api_obj->write_response ();
		} else {
			$this->api_obj->write_response ();
		}
	}
	
	public function add_product() {
		$v_p_id =  $_POST ['PID'];
		$v_v_id = $_POST ['VID'] ;
		$v_carrier_id = $_POST ['carrier_id'] ;
		$v_charge_plan = $_POST ['charge_plan'] ;
		$v_description = $_POST ['description'];
		$v_name = $_POST ['name'];
		$v_parameters = $_POST ['parameters'];
		
		$params = array ($v_p_id, $v_v_id, $v_carrier_id, $v_charge_plan, $v_description, $v_name, $v_parameters );
		//var_dump ( $params );
		
		$sql = 'select * from ez_wfs_db.add_product_list($1, $2, $3, $4, $5, $6, $7)';
		$wfs_db = new class_billing_pgdb ( $this->api_obj->config->wfs_db_config, $this->api_obj );
		$return = $wfs_db->exec_db_dp ( $sql, $params );
		$this->api_obj->return_code = 'add_product_list' . ':' . $return ['p_return'];
		$this->api_obj->push_return_data ( 'success', true );
		$this->api_obj->write_response ();
	}
	
	public function edit_product() {
		//"VID", "PID", "name", description, carrier_id, charge_plan, parameters
		//如果id不存在会创建新的运营商，更新时不更新id，也就是说id已经创建就可以再更改(更改id需要更改好多个关联表)
		$sql = 'select * from ez_wfs_db.sp_edit_product($1,$2,$3,$4,$5,$6,$7)';
		$vid = $_REQUEST ['VID'];
		$pid = $_REQUEST ['PID'];
		$name = $_REQUEST ['name'];
		$description = $_REQUEST ['description'];
		$carrier_id = $_REQUEST ['carrier_id'];
		$charge_plan = $_REQUEST ['charge_lan'];
		$params = $_REQUEST ['parameters'];
		
		$wfs_db = new class_billing_pgdb ( $this->api_obj->config->wfs_db_config, $this->api_obj );
		$this->api_obj->push_return_data ( 'success', false );
		$wfs_db->exec_proc ( $sql, array ($vid, $pid, $name, $description, $carrier_id, $charge_plan, $params ) );
		$this->api_obj->push_return_data ( 'success', true );
		$this->api_obj->write_response ();
	}
	
	public function delete_product() {
		$sql = 'select * from ez_wfs_db.sp_delete_product($1,$2)';
		$vid = $_REQUEST ['VID'];
		$pid = $_REQUEST ['PID'];
		$wfs_db = new class_billing_pgdb ( $this->api_obj->config->wfs_db_config, $this->api_obj );
		$this->api_obj->push_return_data ( 'success', false );
		$wfs_db->exec_proc ( $sql, array ($vid, $pid ) );
		$this->api_obj->push_return_data ( 'success', true );
		$this->api_obj->write_response ();
	}
	
	/*********************** 设备管理 *****************/
	public function get_carrier_name() {
		$sql = 'select * from ez_wfs_db.sp_get_carriers()';
		$wfs_db = new class_billing_pgdb ( $this->api_obj->config->wfs_db_config, $this->api_obj );
		$this->rows = array ();
		$wfs_db->exec_query ( $sql, array (), ez_handle_append_row, $this );
		$record = array ();
		if (is_array ( $this->rows )) {
			foreach ( $this->rows as $key => $val ) {
				$record [$key] ['carrier_id'] = @$val ['id'];
				$record [$key] ['carrier_name'] = @$val ['name'];
			}
			$this->api_obj->push_return_data ( 'total', count ( $this->rows ) );
			$this->api_obj->push_return_data ( 'success', true );
			$this->api_obj->push_return_data ( 'data', $record );
			$this->api_obj->write_response ();
		} else {
			$this->api_obj->write_response ();
		}
	}
	
	public function get_devices_info() {
		$filter = isset($_REQUEST ['filter'])?$_REQUEST ['filter']:'';
		if($filter == '')
			$filter = '%';
		$offset = isset($_REQUEST ['start'])?$_REQUEST ['start']:0;
		$limit = isset($_REQUEST ['limit'])?$_REQUEST ['limit']:20;
		$time_type = isset($_REQUEST['time_type'])?$_REQUEST['time_type']:'is_time';
		$from = $_REQUEST['time_from'];
		if(empty($from))
			$from = strftime('%Y-%m-%d %H:%M:%S',mktime(0, 0, 0, date("m")  , date("d")-1, date("Y")));		//默认从当天的0点开始
		$to = $_REQUEST['time_to'];
		if(empty($to))
			$to = strftime('%Y-%m-%d %H:%M:%S',mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));		//默认到今天的12点结束，也就是说from to都是默认值时显示当天的数据
		
		$params = array ($filter, $from,$to );
		//$this->api_obj->push_return_data ('params',$params);
		$wfs_db = new class_billing_pgdb ( $this->api_obj->config->wfs_db_config, $this->api_obj );
		$sql = sprintf("select count(*) as totalcount from ez_wfs_db.tb_devices where (imei like $1 or bind_pno like $1 or bind_epno like $1) and %s between $2 and $3",$time_type);
		$this->rows = array ();
		$wfs_db->exec_query($sql,$params,ez_handle_append_row, $this);
		$r = $this->rows[0];
		$this->api_obj->push_return_data ( 'r',$r);
		if(is_array($r)){
			$totalCount = $r['totalcount'];
			$sql = sprintf("select * from ez_wfs_db.tb_devices where (imei like $1 or bind_pno like $1 or bind_epno like $1) and %s between $4 and $5 offset $2 limit $3 ",$time_type);
			//$this->api_obj->push_return_data ( 'sql',$sql);
			$this->rows = array ();
			$params = array ($filter, $offset, $limit,$from,$to );
			$wfs_db->exec_query ( $sql, $params, ez_handle_append_row, $this );
			if (is_array ( $this->rows )) {
				$this->api_obj->push_return_data ( 'total', $totalCount );
				$this->api_obj->push_return_data ( 'success', true );
				$this->api_obj->push_return_data ( 'devices', $this->rows );
				$this->api_obj->write_response ();
			} else {
				$this->api_obj->push_return_data ( 'total', 0 );
				$this->api_obj->push_return_data ( 'success', true );
				$this->api_obj->push_return_data ( 'devices',array());
				$this->api_obj->write_response ();
			}
		}else{
			$this->api_obj->push_return_data ( 'total', 0 );
			$this->api_obj->push_return_data ( 'success', true );
			$this->api_obj->push_return_data ( 'devices',array());
			$this->api_obj->write_response();
		}
	}
	
	//获取代理商名称，通过carrier id获取该运营商下的代理商信息
	public function get_agent_name() {
		$carrier_id = $_REQUEST ['filtter'];
		
		$sql = 'select * from ez_wfs_db.sp_get_carrier_by_id($1)';
		$wfs_db = new class_billing_pgdb ( $this->api_obj->config->wfs_db_config, $this->api_obj );
		$carrier = $wfs_db->exec_db_sp ( $sql, array ($carrier_id ) );
		if (is_array ( $carrier )) {
			$billing_api_url = $carrier ['p_billing_api'];
			$api_data = array ('a' => 'w_get_agent_info' );
			//通过biling api访问运营商的代理商
			$agent_result = $this->api_obj->get_from_api ( $billing_api_url, $api_data );
			echo $agent_result;
		}
	}
	
	//获取代理商的计费方案
	public function get_agent_cs() {
		$carrier_id = $_REQUEST ['filtter'];
		
		$sql = 'select * from ez_wfs_db.sp_get_carrier_by_id($1)';
		$wfs_db = new class_billing_pgdb ( $this->api_obj->config->wfs_db_config, $this->api_obj );
		$carrier = $wfs_db->exec_db_sp ( $sql, array ($carrier_id ) );
		if (is_array ( $carrier )) {
			$billing_api_url = $carrier ['p_billing_api'];
			$api_data = array ('a' => 'w_get_agent_cs' );
			//通过biling api访问运营商的代理商
			$agent_result = $this->api_obj->get_from_api ( $billing_api_url, $api_data );
			echo $agent_result;
		}
	}
	
	//获取用户的计费方案
	public function get_user_cs() {
		$carrier_id = $_REQUEST ['filtter'];
		
		$sql = 'select * from ez_wfs_db.sp_get_carrier_by_id($1)';
		$wfs_db = new class_billing_pgdb ( $this->api_obj->config->wfs_db_config, $this->api_obj );
		$carrier = $wfs_db->exec_db_sp ( $sql, array ($carrier_id ) );
		if (is_array ( $carrier )) {
			$billing_api_url = $carrier ['p_billing_api'];
			$api_data = array ('a' => 'w_get_user_cs' );
			//通过biling api访问运营商的代理商
			$agent_result = $this->api_obj->get_from_api ( $billing_api_url, $api_data );
			echo $agent_result;
		}
	}
	
	public function get_device() {
		$bsn = $_REQUEST ['bsn'];
		$imei = $_REQUEST ['imei'];
		$sql = 'select * from ez_wfs_db.sp_get_device($1,$2)';
		$wfs_db = new class_billing_pgdb ( $this->api_obj->config->wfs_db_config, $this->api_obj );
		$device = $wfs_db->exec_db_dp ( $sql, array ($bsn, $imei ) );
		if (is_array ( $device )) {
			$this->api_obj->push_return_data ( 'total', 1 );
			$this->api_obj->push_return_data ( 'success', true );
			$this->api_obj->push_return_data ( 'device', $device );
			$this->api_obj->write_response ();
		} else {
			$this->api_obj->write_response ();
		}
	}
	/*
	 * 设备入库
	 * 	输入参数：
	 * 		imeis:      以逗号，分号，换行，回车等分割的设备GUID或者手机IMEI
	 * 		carrier_id: 运营商代码，如果提供此值，则入库和出库一次完成
	 * 		remark:		设备的备注信息，如供应商，客户的联系方式，要求等等
	 * 输出参数(JSON格式返回)：
	 * 		success: 成功或者失败
	 * 		msg/message : 失败的文字描述
	 * 		fail_imeis: 失败的IMEI的数组，以及失败的原因
	*/
	public function import_devices() {
		$params = $this->api_obj->json_decode ( $_POST ['data'] );
		//基本信息
		$base_info = isset ( $params->base_info ) ? $params->base_info : new stdClass ( );
		//代理商信息
		$agent_info = isset ( $params->base_info ) ? $params->agent_info : new stdClass ( ); //get_object_vars($params['agent_info']);
		//资费信息
		$billing_info = isset ( $params->base_info ) ? $params->billing_info : new stdClass ( ); //get_object_vars($params['billing_info']);
		//进行wfs出入库操作
		$wfs_return = $this->wfs_import_devices ( $base_info, $agent_info, $billing_info );
		$this->api_obj->push_return_data ( 'wfs_return', $wfs_return );
		$this->api_obj->write_response ();
	}
	
	/*
	 * WFS设备入库
	 * 	输入参数：
	 * 		imeis:      以逗号，分号，换行，回车等分割的设备GUID或者手机IMEI
	 * 		carrier_id: 运营商代码，如果提供此值，则入库和出库一次完成
	 * 		remark:		设备的备注信息，如供应商，客户的联系方式，要求等等
	 * 输出参数(JSON格式返回)：
	 * 		success: 成功或者失败
	 * 		msg/message : 失败的文字描述
	 * 		fail_imeis: 失败的IMEI的数组，以及失败的原因
	*/
	public function wfs_import_devices($base_info, $agent_info, $billing_info) {
		//进行wfs出入库操作
		//@v_imei, @v_charge_plan, @v_resaler, @v_carrier_id, @v_vid, @v_pid, @v_remark
		$v_imei = $base_info->imei;
		$v_resaler = isset ( $agent_info->agent_id ) ? $agent_info->agent_id:0;
		$v_carrier_id = $base_info->carrier_id;
		$v_vid = $base_info->VID;
		$v_pid = $base_info->PID;
		$v_remark = $base_info->remark;
		/*{"mawc_welcome":{},"base_info":{"carrier_id":"utone","VID":"0","PID":"0","remark":"test","imei":"test\ntest1"},
		"agent_info":{"pcode":"5","agent_id":"10449","agent_cs_id":"","user_cs_id":""},
		"billing_info":{"balance":"0","currency_type":"CNY","valid_date":"48","free_period":"0","hire_number":"0"}}*/
		/*{"agent_id":"11891","call_cs":"1042","agent_cs":"1042",
		"balance":"2","currency_type":"USD","free_period":"0","hire_number":"0","valid_date_no":"24","product_type_prefix":"5","cs_prefix":"38","agent_prefix":"85215"}*/
		$v_cp = new stdClass ( );
		$v_cp->agent_id = $agent_info->agent_id;
		$v_cp->call_cs = $agent_info->user_cs_id;
		$v_cp->agent_cs = $agent_info->agent_cs_id;
		$v_cp->balance = $billing_info->balance;
		$v_cp->currency_type = $billing_info->currency_type;
		$v_cp->free_period = $billing_info->free_period;
		$v_cp->hire_number = $billing_info->hire_number;
		$v_cp->valid_date_no = $billing_info->valid_date;

		$v_charge_plan = $this->api_obj->json_encode ( $v_cp );
		$imei_str = str_replace ( "\r\n", ",", $v_imei );
		$imei_str = str_replace ( "\r", ",", $imei_str );
		$imei_str = str_replace ( "\n", ",", $imei_str );
		$imei_str = str_replace ( "/\n", ",", $imei_str );
		$imei_str = str_replace ( ";", ",", $imei_str );
		
		$imeis = explode ( ',', $imei_str );
		
		$wfs_db = new class_billing_pgdb ( $this->api_obj->config->wfs_db_config, $this->api_obj );
		$sql = 'SELECT *FROM ez_wfs_db.sp_n_import_devices( $1, $2, $3, $4, $5, $6, $7, $8);';
		
		for($i = 0; $i < count ( $imeis ); $i ++) {
			if (trim ( $imeis [$i] ) == '')
				continue;
			list($vimei,$vbsn) = explode(':',$imeis [$i]);
			FB::log(sprintf('Handle imei=%s,bsn=%s',$v_imei,$vbsn));
			$param_array = array (trim($vbsn), trim ( $vimei ), $v_charge_plan, $v_resaler, $v_carrier_id, $v_vid, $v_pid, $v_remark );
			//var_dump($param_array);
			$r = $wfs_db->exec_db_sp ( $sql, $param_array );
			if ($i == 0) {
				$return = $imeis [$i] . ':' . $r ['p_return'];
			} else {
				$return = $return . ',' . $imeis [$i] . ':' . $r ['p_return'];
			}
		
		}
		return $return;
	}
	//
	///*
	//	 * carrier 设备出入库
	//	 * 	输入参数：
	//	 * 		imeis:      以逗号，分号，换行，回车等分割的设备GUID或者手机IMEI
	//	 * 		carrier_id: 运营商代码，如果提供此值，则入库和出库一次完成
	//	 * 		remark:		设备的备注信息，如供应商，客户的联系方式，要求等等
	//	 * 输出参数(JSON格式返回)：
	//	 * 		success: 成功或者失败
	//	 * 		msg/message : 失败的文字描述
	//	 * 		fail_imeis: 失败的IMEI的数组，以及失败的原因
	//	*/
	//	public function wfs_import_devices($base_info, $agent_info, $billing_info){
	//		//进行wfs出入库操作
	//		//@v_imei, @v_charge_plan, @v_resaler, @v_carrier_id, @v_vid, @v_pid, @v_remark
	//		$v_imei	=	$base_info['imei']; 
	//		$v_charge_plan =	$agent_info; 
	//		$v_resaler =	$agent_info['agent_id'];
	//		$v_carrier_id =	$base_info['carrier_id']; 
	//		$v_vid =	$base_info['VID'];
	//		$v_pid =	$base_info['PID'];
	//		$v_remark =	$base_info['remark'];
	//		
	//		$imei_str = str_replace("\r\n",",",$v_imei);
	//		$imei_str = str_replace("\r",",",$imei_str);
	//		$imei_str = str_replace("\n",",",$imei_str);
	//		$imei_str = str_replace("/\n",",",$imei_str);
	//		$imei_str = str_replace(";",",",$imei_str);
	//		
	//		$imeis = explode(',', $imei_str);
	//		
	//		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config,$this->api_obj);
	//		$sql = 'SELECT ez_wfs_db.sp_import_devices( $1, $2, $3, $4, $5, $6, $7);';
	//		
	//		for($i=0; $i < count($imeis); $i++ ) {
	//			$param_array = array(
	//				$imeis[$i], 
	//				$v_charge_plan, 
	//				$v_resaler, 
	//				$v_carrier_id, 
	//				$v_vid, 
	//				$v_pid, 
	//				$v_remark
	//			);
	//			$r = $wfs_db->exec_db_sp($sql,$param_array);
	//			if ( $i == 0) {
	//				$return = $imeis[$i] .':'. $r['p_return'];
	//			}else{
	//				$return = $return .','. $imeis[$i] .':'. $r['p_return'];
	//			}
	//			 
	//		}
	//		return $return;
	//	}
	

	/*
	 * 设备出库
	 * 	输入参数：
	 * 		imeis:      以逗号，分号，换行，回车等分割的设备GUID或者手机IMEI
	 * 		carrier_id: 运营商代码，
	 * 		remark:		设备的备注信息，如供应商，客户的联系方式，要求等等
	 * 输出参数(JSON格式返回)：
	 * 		success: 成功或者失败
	 * 		msg/message : 失败的文字描述
	 * 		fail_imeis: 失败的IMEI的数组，以及失败的原因
	*/
	public function ostock_devices() {
		//"VID", "PID", "name", description, carrier_id, charge_plan, parameters
		//如果id不存在会创建新的运营商，更新时不更新id，也就是说id已经创建就不可以再更改(更改id需要更改好多个关联表)
		$sql = 'select * from ez_wfs_db.sp_ostock_devices($1,$2,$3)';
		$imei_str = $_REQUEST ['imeis'];
		$imei_str = str_replace ( "\r\n", ",", $imei_str );
		$imei_str = str_replace ( "\r", ",", $imei_str );
		$imei_str = str_replace ( "\n", ",", $imei_str );
		$imei_str = str_replace ( ";", ",", $imei_str );
		
		$imeis = explode ( ",", $_REQUEST ['imeis'] );
		$temp = array ();
		foreach ( $imeis as $imei ) {
			if (empty ( $imei ))
				continue;
			list($vimei,$vbsn) = explode(':',$imei);
			FB::log(sprintf('Handle imei=%s,bsn=%s',$v_imei,$vbsn));
			//$param_array = array (trim($vbsn), trim ( $vimei ), $v_charge_plan, $v_resaler, $v_carrier_id, $v_vid, $v_pid, $v_remark );
				
			$temp [] = $vimei;
		}
		
		$carrier_id = $_REQUEST ['carrier_id'];
		$remark = $_REQUEST ['remark'];
		
		$wfs_db = new class_billing_pgdb ( $this->api_obj->config->wfs_db_config, $this->api_obj );
		$this->api_obj->push_return_data ( 'success', false );
		$r = $wfs_db->exec_db_sp ( $sql, array (join ( ",", $temp ), $carrier_id, $remark ) );
		if (isset ( $r ['p_return'] )) {
			$this->api_obj->push_return_data ( 'success', $r ['p_return'] > 0 );
			$this->api_obj->push_return_data ( 'fail_imeis', $r ['p_fail_imeis'] );
		}
		$this->api_obj->write_response ();
	}
	/*********************** 设备管理 *****************/
	
	/*
	 * 根据版本号构建终端更新配置的请求参数，
	 * 		v : 版本号
	 * 		pa ： 请求所需的参数
	*/
	public function build_ophone_update_req($v, $pa, $lang, $key) {
		$req = '';
		if (! isset ( $v ) or empty ( $v ))
			$v = 'unknown';
		switch ($v) {
			case 'unknown' :
				$vunknown = array ($pa ['bsn'], $pa ['imei'], $pa ['pno'] );
				$req = join ( ",", $vunknown );
				break;
			case '2.2.2' :
				$v222 = array ($pa ['bsn'], $pa ['imei'], $pa ['pno'] );
				$req = join ( ",", $v222 );
				break;
			case '2.3.0' :
				$v230 = array ($pa ['bsn'], $pa ['imei'], $pa ['pno'] );
				$req = join ( ",", $v230 );
				break;
			case '2.8.4' :
			default :
				$req = "update," . array_to_string ( ",", pa );
				break;
		}
		$req = sprintf ( "p=%s&v=%s&lang=%s", api_encrypt ( $req, $key ), $v, $lang );
		return $req;
	}
	/*
	 * 终端设备更新配置测试
	 * 		输入参数：
	 * 			bsn :	设备bsn
	 * 			imei :	设备标识
	 * 			vid :	OEM生产商ID
	 * 			pid :	OEM产品ID
	 * 			pno :	配置手机号码
	 * 			v   :	设备版本号
	 * 			op	：	其他附加参数，参数格式为k=v并以逗号或者\r\n隔开
	*/
	public function ophone_action() {
		$v = $_REQUEST ['v'];
		$lang = $_REQUEST ['lang'];
		$pa = array ('bsn' => $_REQUEST ['bsn'], 'imei' => $_REQUEST ['imei'], 'pno' => $_REQUEST ['pno'], 'pass' => $_REQUEST ['pass'], 'vid' => $_REQUEST ['vid'], 'pid' => $_REQUEST ['pid'], 'lang' => $_REQUEST ['lang'], 'v' => $_REQUEST ['v'] );
		//var_dump($this->api_obj->config);
		$req = $this->build_ophone_update_req ( $v, $pa, $lang, $this->api_obj->md5_key );
		//echo $this->api_obj->config->ophone["update"];
		$r = $this->api_obj->get_from_api ( $this->api_obj->config->ophone ["update"], $req );
		$r = str_ireplace ( "\feff9", "<br>", $r );
		$r = str_ireplace ( "\r\n", "<br>", $r );
		$this->api_obj->push_return_data ( 'success', 'true' );
		$this->api_obj->push_return_data ( 'response', $r );
		$this->api_obj->write_response ();
	}
	
	public function delete_device(){
		
	}
}

?>
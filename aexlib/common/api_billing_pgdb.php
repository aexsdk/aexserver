<?php

date_default_timezone_set('Asia/Chongqing');
/*
	定义Billing的数据库类
*/
require_once dirname(__FILE__).'/api_pgsql_db.php';

/*
	将SQL返回的行附加到数组
	参数
		$context : 结果数组
		$index : 行序号
		$row : 行数组
*/
function pg_billing_handle_append_row($context,$index,$row){
//	foreach($row as $key=>$value)
//		if(is_string($value))
//			$row[$key] = mb_convert_encoding($value,"UTF-8","GB2312");
	array_push($context->rows,$row);
	//echo sprintf("Row[%d]=%d<br>",$index,count($context->rows));
	//var_dump($context);
}

class class_billing_pgdb extends api_pgsql_db{
	var $rows = array();
	var $total_count = 0;
	
/**
	 * Constructor a config object with db_host, db_pass, db_user and db_name
	 * may be passed so it can connect to a different database then the default.
	 *
	 * @param unknown_type $config
	 * @return db
	 */
	public function __construct($config,$api_obj)
	{
		parent::__construct($config,$api_obj);
	}
	
	//重写方法,获取总行数
	public function exec_db_sp($sql,$params=array())
	{
		$rdata = array();
		//echo $sql;
		$result = pg_query_params($this->dblink,$sql,$params);
		if($result){
			$this->total_count= $this->result_num_rows($result);
			$index = 0;
			$rdata = $this->get_sp_return_with_array($result);
			pg_free_result($result);
		}else{
			//echo sprintf("sql:'%s'",$sql);
			$this->write_log(_LEVEL_ERROR_,_DB_SQL_ERROR_,
				sprintf("sql=%s,\r\nerror=%s,\r\ntrace=\r\n%s\r\n",$sql,$this->get_last_error(),get_trace_string()));
			exit;
		}
		return $rdata;
	}
	
	public function backup_funcs($s)
	{
		$sql = sprintf("select * from ez_utils_db.sp_get_schema_funcs_declare('%s%%')",$s);
		$this->rows = array();
		$this->exec_query($sql, array(), pg_billing_handle_append_row , $this);
		return $this->rows;
	}

	/**
	 *	creater: lion wang
	 *	time: 2010.6.1
	 *	@param: $resaler:  agent id
	 *			$offset
	 *			$count		limite
	 *	caption: get agent list by agent id
	 **/
	public function billing_get_agent_list($offset='', $count='', $resaler=-1){
		if(empty($offset))
			$offset = 0;			//默认从头开始
		if(empty($count))
			$count = 15;		//默认一次返回15行
		if(!isset($resaler)){
			$this->write_warning('Server Error');
			$this->write_response();
			exit;
		}
		
		$params = array(
			"$resaler",
			"$count",
			"$offset"
		);
		
		$count = array(
			"$resaler",
			'null',
			'null'
		);
		
		$sp_sql = 'SELECT *FROM ez_billing_db.sp_agent_get_list($1, $2, $3);';
		$sp_count = 'SELECT *FROM ez_billing_db.sp_agent_get_list($1, $2, $3);';
		
		$rc = $this->exec_db_sp($sp_count, $count);
		if(is_array($rc))
		{
			$this->total_count =  count($rc);
			$this->rows = array();
			$this->exec_query($sp_sql, $params, pg_billing_handle_append_row , $this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		//$this->set_return_code(101);
		//var_dump($rdata->rows);
		return $this->rows;
	}
	
	
	/**
	 *	creater: lion wang
	 *	time: 2010.6.1
	 *	@param: $resaler:  agent id
	 *			$offset
	 *			$count		limite
	 *	caption: get agent list by agent id
	 **/
	public function billing_get_agent_tree($resaler=-1){
		if(!isset($resaler)){
			$this->write_warning('Server Error');
			$this->write_response();
			exit;
		}
	
		
		$params = array(
			"$resaler",
			'null',
			'null'
		);
		
		$sp_sql = 'SELECT *FROM ez_billing_db.sp_agent_get_list($1, $2, $3);';
		//$this->total_count =  count($rc);
		$this->rows = array();
		$this->exec_query($sp_sql, $params, pg_billing_handle_append_row , $this);
		$this->set_return_code(101);
		//$this->set_return_code(101);
		//var_dump($rdata->rows);
		return $this->rows;
	}
	
	//writer : lion wang
	//time : 2010-07-23
	//caption : 添加操作员
	public function billing_add_operater($add_operater_array){
		/*
		CREATE	PROCEDURE dbo.sp_agent_add_info
		(	
			"agent_id"	=>	$agent_id,
			"user_name"	=>	$user_name,
			"confirm_password"	=>	$confirm_password,
			"password"	=>	$password,
			"email"	=>	$email
		)--WITH ENCRYPTION
		*/
		$sql = "dbo.sp_agent_add_info;1";
		$sql_data =array(
			$add_operater_array["agent_id"], 
			$add_operater_array["user_name"],
			$add_operater_array["password"],
			$add_operater_array["confirm_password"],
			$add_operater_array["email"]
		);
		//var_dump($sql_data);
		$rdata = $this->exec_proc($sql,$sql_data);
		if(is_array($rdata)){
			return $rdata;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}
	}
	
	
	
		
	/******VNP LITIME SPEED start*****/
	public function vpn_control($web_params){ 
		//@v_pid, @v_vid, @v_e164, @n_bandwidth, @t_remark
		$sql = 'SELECT *FROM ez_billing_db.sp_add_bandwidth( $1, $2, $3, $4, $5);';
		$rc = $this->exec_db_sp($sql,$web_params);
		return $rc;
	}
	
	public function vpn_delete($web_params){ 
		//@v_pid, @v_vid, @v_e164, @n_bandwidth, @t_remark
		$sql = 'SELECT *FROM ez_billing_db.sp_delete_bandwidth( $1, $2, $3);';
		$rc = $this->exec_db_sp($sql, $web_params);
		return $rc;
	}
	
	public function vpn_edit($web_params){ 
		//@v_pid, @v_vid, @v_e164, @n_bandwidth, @t_remark
		$sql = 'SELECT *FROM ez_billing_db.sp_edit_bandwidth( $1, $2, $3, $4, $5, $6, $7, $8);';
		$rc = $this->exec_db_sp($sql, $web_params);
		return $rc;
	}
	
	public function vpn_list($web_params){ 
		$offset = $web_params['p_start'];
		$count = $web_params['p_limit'];
		
		if(empty($offset))
			$offset = 0;			//默认从头开始
		if(empty($count))
			$count = 15;		//默认一次返回15行
		
		$params = array(
			$web_params['p_id'],
			"$count",
			"$offset"
		);
		
		$sp_sql = 'SELECT *FROM ez_billing_db.sp_get_vpn_list($1, $2 ,$3);';
		$sp_count = "select count(*) as total_count from ez_billing_db.tb_endpoint_bandwidth;";
		
		$rc = $this->exec_db_sp($sp_count, array());
		if(is_array($rc))
		{
			//$this->total_count =  count($rc);
			$this->rows = array();
			$this->exec_query($sp_sql, $params, pg_billing_handle_append_row , $this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		//$this->set_return_code(101);
		//var_dump($rdata->rows);
		return $this->rows;
	}
	
	/*******VNP LITIME SPEED end*******/
	
	
	/*************** 路由 start**************/
	//================================BEGIN   全局号码替换==============================
	public function route_get_rewirte_list($offset, $limit) {
		if(empty($offset))
			$offset = 0;			//默认从头开始
			
		if(empty($limit))
			$limit = 15;		//默认一次返回15行

		$params_array = array(
			$limit,
			$offset
		);
		
		$sp_sql = "SELECT * FROM ez_routing_db.route_get_rewirte_list($1, $2)";
		$sp_count = 'SELECT * FROM ez_routing_db.wfs_routing_rewrite_list;';
		
		$rc = $this->exec_db_sp($sp_count, array());
		if(is_array($rc))
		{
			//$this->total_count =  count($rc);
			$this->rows = array();
			$this->exec_query($sp_sql, $params_array, pg_billing_handle_append_row , $this);
		}else{
			$this->set_return_code(-81);
		}
		//$this->set_return_code(101);
		//var_dump($rdata->rows);
		return $this->rows;
	}
	
	public function route_add_rewirte_list($new_prefix,$prefix,$validity) {	
		$prefix = addslashes($prefix);
		$new_prefix = addslashes($new_prefix);
		$validity = addslashes($validity);
		
		$param_array = array(
			$prefix,
			$new_prefix,
			$validity
		);
		
		$sql = "SELECT *FROM ez_routing_db.route_add_rewirte_list( $1, $2, $3);";
		$rc = $this->exec_db_sp($sql,$param_array);
		return $rc;
	}
	

	public function route_edit_rewirte_list($id, $prefix, $new_prefix, $validity) {
		$id = addslashes($id);
		$prefix = addslashes($prefix);
		$new_prefix = addslashes($new_prefix);
		$validity = addslashes($validity);
		
		
		$param_array = array(
			$id,
			$prefix,
			$new_prefix,
			$validity
		);
		
		$sql = "SELECT *FROM ez_routing_db.route_edit_rewirte_list( $1, $2, $3, $4);";
		$rc = $this->exec_db_sp($sql,$param_array);
		return $rc;
	}
	
	public function route_delete_rewirte_list($id) {
		$arrs = explode ( ',', substr ( $id, 1, - 1 ) );
		$count = count ( $arrs );
		$did = '';
		try {
			for($i = 0; $i < $count; $i ++) {
				$str = explode ( ':', $arrs [$i] );
				$did = trim ( $str [1] );
				$param_array = array(
					"$did"
				);
				
				$sql = "SELECT *FROM ez_routing_db.route_delete_rewirte_list($1);";
				$rc = $this->exec_db_sp($sql,$param_array);
			}
			return $rc;
		} catch (Exception $e) {
			return '-101';
		}
	}
	//================================END   全局号码替换==============================
	
	//================================BEGIN  回拨服务器信息==============================
	public function route_get_server_list($offset, $limit,$domain,$resaler) {
		if(empty($offset))
			$offset = 0;			//默认从头开始
			
		if(empty($limit))
			$limit = 15;		//默认一次返回15行

		$w = sprintf("domain = '%s'",$domain);
		if($resaler !=0 )
			$w = sprintf('%s and resaler = %d ',$w,$resaler);
		$sp_sql = "SELECT * FROM ez_routing_db.wfs_routing_server_list where $w ORDER BY server_id DESC LIMIT $limit OFFSET $offset";
		$sp_count = 'SELECT count(*) as totalcount FROM ez_routing_db.wfs_routing_server_list;';
		
		$rc = $this->exec_db_sp($sp_count, array());
		if(is_array($rc))
		{
			$this->total_count = $rc['totalcount'];
			$this->rows = array();
			$this->exec_query($sp_sql, array(), pg_billing_handle_append_row , $this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		return $this->rows;
	}
	
	public function route_edit_server_list($params,$domain,$resaler) {
		$id = $params['id'];
		$alias= $params['alias'];
		$port = $params['port'];
		$user_name = $params['user_name'];
		$password = $params['password'];
		$validity = $params['validity'];
		$server_ip = $params['server_ip'];
		$priority = $params['priority'];
		$remark = $params['remark'];
		
		$params_array = array(
			'id' => $id,
			'alias' => $alias,
			'password' => $password,
			'port' => $port,
			'priority' => $priority,
			'remark' => $remark,
			'server_ip' => $server_ip,
			'user_name' => $user_name,
			'validity' => $validity,
			'domain' => $domain,
			'resaler' => $resaler
		);
		$sql = "SELECT * FROM ez_routing_db.route_edit_server_list( $1, $2, $3, $4, $5, $6, $7, $8, $9,$10,$11)";
		$rc = $this->exec_db_sp($sql,$params_array);
		return $rc;
	}
	
	public function route_add_server_list($params,$domain,$resaler) {
		$alias= addslashes($params['alias']);
		$port = addslashes($params['port']);
		$user_name = addslashes($params['user_name']);
		$password = addslashes($params['password']);
		$validity = addslashes($params['validity']);
		$server_ip = addslashes($params['server_ip']);
		$priority = addslashes($params['priority']);
		$remark = addslashes($params['remark']);

		$param_array = array(
			$alias,
			$password,
			$port,
			$priority,
			$remark,
			$server_ip	,
			$user_name,
			$validity,
			$domain,
			$resaler
		);
		$sql = "SELECT *FROM ez_routing_db.route_n_add_server_list( $1, $2, $3, $4, $5, $6, $7, $8,$9,$10)";
		$rc = $this->exec_db_sp($sql,$param_array);
		return $rc;
	}
	
	public function route_delete_server_list($id ) {
		$arrs = explode ( ',', substr ( $id, 1, - 1 ) );
		$count = count ( $arrs );
		$did = '';
		for($i = 0; $i < $count; $i ++) {
			$str = explode ( ':', $arrs [$i] );
			$did = trim ( $str [1] );
			$param_array = array(
				$did
			);
			$sql = "SELECT *FROM ez_routing_db.route_delete_server_list( $1)";
			$rc = $this->exec_db_sp($sql,$param_array);
		}
		return $rc;
	}
	//================================END  回拨服务器信息==============================
	
	
	//================================BEGIN   服务器到网关速度==============================
	public function route_get_server_speed($offset, $limit) {
		if(empty($offset))
			$offset = 0;			//默认从头开始
			
		if(empty($limit))
			$limit = 15;		//默认一次返回15行
		
		$params_array = array(
			$limit,
			$offset	
		);	
			
		$sp_sql = "SELECT * FROM ez_routing_db.route_get_server_speed($1,$2)";
		$sp_count = 'SELECT * FROM ez_routing_db.wfs_routing_server_speed()';
		
		$rc = $this->exec_db_sp($sp_count, array());
		if(is_array($rc))
		{
			$this->rows = array();
			$this->exec_query($sp_sql, $params_array, pg_billing_handle_append_row , $this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		return $this->rows;
	}
	
	public function route_get_routing_ip() {
		try {
			$sql = "SELECT routing_ip AS id,routing_name FROM ez_routing_db.wfs_routing_list ORDER BY routing_id DESC";
			$this->exec_query($sql, array(), pg_billing_handle_append_row , $this);
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
		
		return $this->rows;
	}
	
	public function route_get_server_id() {
		try {
			$sql = "SELECT server_id AS id,alias FROM ez_routing_db.wfs_routing_server_list ORDER BY server_id DESC";
			$this->exec_query($sql, array(), pg_billing_handle_append_row , $this);
		} catch ( Exception $e ) {
			$this->rows = $e->getMessage ();
		}
		
		return $this->rows;
	}
	
	public function route_edit_server_speed($id, $routing_ip, $routing_name, $server_id, $speed, $stability) {
		$server_id = addslashes($server_id);
		$routing_name = addslashes($routing_name);
		$routing_ip = addslashes($routing_ip);
		$stability = addslashes($stability);
		$speed = addslashes($speed);
		
		$param_array = array(
			$id,
			$server_id,
			$routing_name,
			$routing_ip,
			$stability,
			$speed
		);
		
		$sql = "SELECT *FROM ez_routing_db.route_edit_server_speed( $1, $2, $3, $4, $5, $6)";
		try {
			$rc = $this->exec_db_sp($sql,$param_array);
		} catch (Exception $e) {
			$rc = '-105';
		}
		return $rc;
	}
	
	public function route_add_server_speed($routing_ip,$routing_name,$server_id,$speed,$stability) {
		$server_id = addslashes($server_id);
		$routing_name = addslashes($routing_name);
		$routing_ip = addslashes($routing_ip);
		$stability = addslashes($stability);
		$speed = addslashes($speed);
		
		$param_array = array(
			$server_id,
			$routing_name,
			$routing_ip,
			$stability,
			$speed
		);
		
		$sql = "SELECT *FROM ez_routing_db.route_add_server_speed($1, $2, $3, $4, $5)";
		
		try {
			$rc = $this->exec_db_sp($sql,$param_array);
		} catch (Exception $e) {
			$rc = '-105';
		}
		return $rc;
	}
	
	
	public function route_delete_server_speed($id) {
		$arrs = explode ( ',', substr ( $id, 1, - 1 ) );
		$count = count ( $arrs );
		$did = '';
		try {
			for($i = 0; $i < $count; $i ++) {
				$str = explode ( ':', $arrs [$i] );
				$did = trim ( $str [1] );
				$param_array = array(
					"$did"
				);
				
				$sql = "SELECT *FROM ez_routing_db.route_delete_server_speed($1);";
				$rc = $this->exec_db_sp($sql,$param_array);
			}
			return $rc;
		} catch (Exception $e) {
			return '-101';
		}
	}
	//================================END  服务器到网关速度==============================
	
	  //================================BEGIN   前缀替换管理==============================
	public function route_get_prefix_list($offset,$limit,$s_prefix,$s_rid){
		if(empty($offset))
			$offset = 0;			//默认从头开始
			
		if(empty($limit))
			$limit = 15;		//默认一次返回15行

		$params_array = array(
			"$limit",
			"$offset",
			"$s_prefix",
			"$s_rid"
		);	
			
		$sp_sql = "SELECT * FROM ez_routing_db.route_get_prefix_list($1,$2,$3,$4)";
		$sp_count = 'SELECT * FROM ez_routing_db.wfs_routing_prefix_list;';
		
		$rc = $this->exec_db_sp($sp_count, array());
		if(is_array($rc))
		{
			$this->rows = array();
			$this->exec_query($sp_sql, $params_array, pg_billing_handle_append_row , $this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		return $this->rows;	
	}
	
	public function route_get_routing_id() {
		try {
			$sql = "SELECT routing_id AS id,routing_name FROM ez_routing_db.wfs_routing_list ORDER BY routing_id DESC";
			$this->exec_query($sql, array(), pg_billing_handle_append_row , $this);
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
		
		return $this->rows;
	}
	
	public function route_edit_routing_prefix($params) {
//		$id = $params['id'];
//		$routing_id = $params['routing_id'];
//		$routing_prefix = $params['routing_prefix'];
//		$priority = $params['priority'];
//		
//		$params_array = array(
//			$id,
//			$routing_id,
//			$routing_prefix,
//			$priority
//		);
	
		$sql = "SELECT *FROM ez_routing_db.route_edit_routing_prefix($1, $2, $3, $4,$5,$6,$7,$8)";
		
		$rc = $this->exec_db_sp($sql,array(
			$params['id'],
			$params['rid'],
			$params['prefix'],
			$params['priority'],
			$params['domain'],
			$params['resaler'],
			$params['start_time'],
			$params['end_time']
		));
		return $rc;
	}
	
	public function route_add_prefix_list($params) {
		$sql = "SELECT *FROM ez_routing_db.route_add_prefix_list($1, $2, $3,$4,$5,$6,$7)";
		
		$rc = $this->exec_db_sp($sql,array(
			$params['rid'],
			$params['prefix'],
			$params['priority'],
			$params['domain'],
			$params['resaler'],
			$params['start_time'],
			$params['end_time']
		));
		return $rc;
	}
	
	public function route_delete_prefix_list($id) {
		$arrs = explode ( ',', substr ( $id, 1, - 1 ) );
		$count = count ( $arrs );
		$did = '';
		for($i = 0; $i < $count; $i ++) {
			$str = explode ( ':', $arrs [$i] );
			$did = trim ( $str [1] );
			$params_array = array($did);
			$sql = "SELECT *FROM ez_routing_db.route_delete_prefix_list($1)";
			$rc = $this->exec_db_sp($sql,$params_array);
		}
		return $rc;

	}
	//================================END   前缀替换管理==============================
	
		//================================BEGIN   落地管理==============================
	public function route_get_gateway_list($offset, $limit) {
		if(empty($offset))
			$offset = 0;			//默认从头开始
			
		if(empty($limit))
			$limit = 15;		//默认一次返回15行
		
		$params_array = array(
			$limit,
			$offset
		);
			
		$sp_sql = "SELECT * FROM ez_routing_db.wfs_routing_list LIMIT $1 OFFSET $2 ";
		$sp_count = 'SELECT count(*) as totalcount FROM ez_routing_db.wfs_routing_list;';
		
		$rc = $this->exec_db_sp($sp_count, array());
		if(is_array($rc))
		{
			$this->total_count = $rc['totalcount'];
			$this->rows = array();
			$this->exec_query($sp_sql, $params_array, pg_billing_handle_append_row , $this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		return $this->rows;
	}
		
	public function route_edit_gateway_list($params) {
		$params_array = array(
			$params['id'],
			$params['routing_ip'],
			$params['routing_type'],
			$params['routing_strip'],
			$params['routing_prefix'],
			$params['routing_name'],
			$params['routing_remark'],
			$params['validity'],
			$params['priority'],
			$params['cli'],
			$params['domain'],
			$params['resaler'],
			$params['retries'],
			$params['delay']
		);
	
		$sql = "SELECT *FROM ez_routing_db.route_edit_gateway_list( $1, $2, $3, $4, $5, $6, $7, $8, $9,$10,$11,$12,$13,$14)";
		try {
			$rc = $this->exec_db_sp($sql, $params_array);
		} catch (Exception $e) {
			$rc = '-105';
		}
		return $rc;
	}
	
	public function route_add_gateway_list($params) {		
		$params_array = array(
			$params['routing_ip'],
			$params['routing_type'],
			$params['routing_strip'],
			$params['routing_prefix'],
			$params['routing_name'],
			$params['routing_remark'],
			$params['validity'],
			$params['priority'],
			$params['cli'],
			$params['domain'],
			$params['resaler'],
			$params['retries'],
			$params['delay']
		);
		FB::log($params,'route_add_gateway_list');
		$sql = "SELECT * FROM ez_routing_db.route_add_gateway_list( $1, $2, $3, $4, $5, $6, $7, $8,$9,$10,$11,$12,$13)";
		$rc = $this->exec_db_sp($sql, $params_array);
		return $rc;
	}
	
	public function route_delete_gateway_list($id) {
		
		$arrs = explode ( ',', substr ( $id, 1, - 1 ) );
		$count = count ( $arrs );
		$did = '';
		try {
			for($i = 0; $i < $count; $i ++) {
				$str = explode ( ':', $arrs [$i] );
				$did = trim ( $str [1] );
				$params_array = array(
					"$did"
				);
				$sql = "SELECT *FROM ez_routing_db.route_delete_gateway_list($1)";
				$rc = $this->exec_db_sp($sql, $params_array);
			}
		} catch (Exception $e) {
			$rc = '-105';
		}

		return $rc;
	}
	
	//================================END   落地管理==============================
	
	public function route_get_gateway_for_crm($pno){
		$params = array(
			$pno
		);
		try {
			$sp_sql = "select *from ez_routing_db.sp_routing_get_crm_gateway($1)";
			$row = $this->exec_db_sp($sp_sql, $params);
		} catch (Exception $e) {
			$row = $e->getMessage();
		}
		return $row;
	}
	/*************** 路由 end****************/
	
	
	/***
	 *	WFS功能
	 */	
	/***************Strat 仓库功能管理 入库*************/
	public function wfs_get_devices_info($params_array) {
		$params = array(
			$params_array['value'],
			$params_array['type'],
			$params_array['from'],
			$params_array['to'],
			$params_array['resaler'],
			$params_array['offset'],
			$params_array['limit'],
		);
		
		$params_count	= array(
			$params_array['value'],
			$params_array['type'],
			$params_array['from'],
			$params_array['to'],
			$params_array['resaler'],
			null,
			null
		);
		
		$sp_sql = "select * from ez_wfs_db.sp_web_get_devices_info($1,$2,$3,$4,$5,$6,$7)";
		$rc = $this->exec_db_sp($sp_sql, $params_count);
		if(is_array($rc))
		{
			$this->rows = array();
			$this->exec_query($sp_sql, $params, pg_billing_handle_append_row, $this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		//$this->set_return_code(101);
		return $this->rows;
	}
	
	public function wfs_select_stock($web_params) {
		$params = array(
			intval($web_params['prodtype']),
			$web_params['imei'],
			$web_params['stime'],
			$web_params['etime'],
			intval($web_params['limit']),
			intval($web_params['offset'])
		);
		
		$count = array(
		    intval($web_params['prodtype']),
		    $web_params['imei'],
			$web_params['stime'],
			$web_params['etime'],
			null,
			null
		);
		
		$sp_sql = "SELECT * FROM ez_wfs_db.sp_web_get_warehousing_info( $1, $2, $3, $4, $5, $6)";
		
		$rc = $this->exec_db_sp($sp_sql, $count);
		if(is_array($rc))
		{
			$this->rows = array();
			$this->exec_query($sp_sql, $params, pg_billing_handle_append_row, $this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		//$this->set_return_code(101);
		return $this->rows;
	}
	
	public function wfs_select_prod_type() {
		try {
			$sql = "SELECT * FROM  ez_wfs_db.sp_web_get_product_type()";
			$this->exec_query($sql, array(), pg_billing_handle_append_row, $this);
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
			echo $rows;
			break;
		}
		return $this->rows;
	}
	
	//入库批次表
	public function wfs_insert_stock($params) {
		$params_array = array(
			intval($params ['product_type_id']),
			intval($params ['operate_id']),
			intval($params ['product_number']),
			$params ['model'],
			$params ['factory_info'],
			$params ['remark'],
			$params ['imeis'],
			$params ['imeis_success'],
			$params ['imeis_fail']
		);
		try {
			$sql = "SELECT *FROM ez_wfs_db.sp_web_insert_warehouse( $1, $2, $3, $4, $5, $6,$7,$8,$9)";
			$row = $this->exec_db_sp($sql, $params_array);
		} catch ( Exception $e ) {
			$row = $e->getMessage ();
		}
		return $row;
	}
	
	public function wfs_upfile_stock($params) {
		$params_array = array(
			$params ['bsn'],
			$params ['imei'],
			$params ['pno'],
			$params ['vid'],
			$params ['pid'],
			intval($params ['warehousing_id']),
		);
		
		try {
			$sql = "SELECT * FROM ez_wfs_db.sp_web_warehousing_devices( $1, $2, $3, $4, $5, $6)";
			$row = $this->exec_db_sp($sql, $params_array);
		} catch ( Exception $e ) {
			$row = $e->getMessage ();
		}
		return $row;
	}
	
	//================================END   仓库功能管理 入库==============================
	

	//================================BEGIN   仓库功能管理 出库==============================
	/**
	 *	creater: lion wang
	 *	time: 2010.8.26
	 *	@param: 
	 *	$imei
	 *	$wfs_attribute
	 *	$agenter 
	 *	$prodtype
	 * 	$isactive 
	 *	$stime
	 *	$etime
	 *	$limit
	 *	$offset
	 *	caption: get info for stock removal 
	 **/
	public function wfs_select_delivery($web_params){
		$params = array(
			(int)$web_params['prodtype'],
			$web_params['stime'],
			$web_params['etime'],
			(int)$web_params['agenter'],
			(int)$web_params['limit'],
			(int)$web_params['offset']	
		);
		$count = array(
			(int)$web_params['prodtype'],
			$web_params['stime'],
			$web_params['etime'],
			(int)$web_params['agenter'],
			null,
			null	
		);
		
		$sp_sql = "SELECT * FROM ez_wfs_db.sp_web_get_leaves_stroehouse_info( $1, $2, $3, $4, $5, $6)";
		
		$rc = $this->exec_db_sp($sp_sql, $count);
		if(is_array($rc))
		{
			$this->rows = array();
			$this->exec_query($sp_sql, $params, pg_billing_handle_append_row, $this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		//$this->set_return_code(101);
		return $this->rows;
	}

	
	public function wfs_insert_account_info($devices_array) {
		$params_array = array(
			$devices_array['bsn'],
			$devices_array['imei'],
			$devices_array['pno'],
			$devices_array['vid'],
			$devices_array['pid'],
			$devices_array['agent_id'],
			$devices_array['charge_plan'],
			$devices_array['leaves_stock_id'],
			$devices_array['remark'],
		);
		try {
			$sql = "SELECT *FROM ez_wfs_db.sp_web_leaves_devices($1, $2, $3, $4, $5, $6, $7 , $8, $9)";
			$row = $this->exec_db_sp($sql, $params_array);
		} catch (Exception $e) {
			$row = $e->getMessage();
		}
		return $row;
	}
	
	public function wfs_insert_leaves_stock($params) {
		$params_array = array(
			intval(addslashes($params ['product_type_id'])),
			intval(addslashes($params ['operator_id'])),
			intval(addslashes($params ['agent_id'])), 
			addslashes($params ['init_charge']),
			addslashes($params ['remark'])
		);
		
		try {
			$sql = 'SELECT * FROM ez_wfs_db.sp_web_insert_leaves_storehouse( $1, $2, $3, $4, $5)';
			$row = $this->exec_db_sp($sql, $params_array);
		} catch ( Exception $e ) {
			$row = $e->getMessage ();
		}
		return $row;
	}

	public function wfs_get_agent_oem_info($sub_resaler, $resaler) {
		$params_array = array(
			$sub_resaler,
			$resaler
		);
		
		try {
			$sql = 'SELECT * FROM ez_wfs_db.sp_web_get_agent_oem_info( $1, $2)';
			$row = $this->exec_db_sp($sql, $params_array);
		} catch ( Exception $e ) {
			$row = $e->getMessage ();
		}
		return $row;
	}
	
	public function wfs_edit_agent_oem_info($params_array) {
		try {
			$sql = 'SELECT * FROM ez_wfs_db.sp_web_edit_agent_oem_info( $1, $2)';
			$row = $this->exec_db_sp($sql, $params_array);
		} catch ( Exception $e ) {
			$row = $e->getMessage ();
		}
		return $row;
	}
	

	public function wfs_add_agent_info($params) {
		$params_array = array(
			intval($params ['agent_id']),
			$params ['name'],
			$params ['description'], 
			$params ['charge_plan'],
			$params ['parameters'],
			$params ['v_id'],
			$params ['p_id'],
			intval($params ['group_id']),
			$params ['oem']
		);
		
		try {
			$sql = 'SELECT * FROM ez_wfs_db.sp_web_insert_agent_info( $1, $2, $3, $4, $5, $6, $7, $8, $9)';
			$row = $this->exec_db_sp($sql, $params_array);
		} catch ( Exception $e ) {
			$row = $e->getMessage ();
		}
		return $row;
	}
	
	public function wfs_edit_agent_charge_plan($params_array) {
		try {
			$sql = 'SELECT * FROM ez_wfs_db.sp_web_edit_agent_charge_plan( $1, $2)';
			$row = $this->exec_db_sp($sql, $params_array);
		} catch ( Exception $e ) {
			$row = $e->getMessage ();
		}
		return $row;
	}
	
	public function sp_wfs_web_cancel_bind($params_attay) {
		try {
			$sql = "SELECT *FROM ez_wfs_db.sp_web_cancel_bind($1, $2, $3)";
			$row = $this->exec_db_sp($sql, $params_attay);
		} catch (Exception $e) {
			$row = $e->getMessage();
		}
		return $row;
	}
	
	/*================================END   仓库功能管理 出库==============================*/
	/********************仓库功能管理入库相关信息 ************************/
	
	
	/*************************Ophoen ****************************/
	/**
	 * 为号码添加运营商以及省市地区标识
	 * 参数
	 * 		$pno ： 全国码的电话号码
	 */
	public function phone_build_prefix($pno){
		if(isset($this->api_object->config->ajust_prefix) && $this->api_object->config->ajust_prefix == '1'){
			//只有配置了调整前缀，才会修改号码的规则
			$sql = 'select * from ez_utils_db.sp_build_prefix($1)';
			$rc = $this->exec_db_sp($sql, array($pno));
			if(is_array($rc)){
				return $rc['po_pno'];
			}else{
				return $pno;
			}
		}else{
			return $pno;
		}
	}
	
	/**
	 * 获得回拨路由
	 * 参数
	 * 		pno : 需要显示的号码，此号码为全国码的电话号码
	 * 		caller: 主叫号码，此号码包含特殊呼叫前缀、国码、运营商、省市以及区号信息
	 * 		callee: 被叫号码
	 * 		show : 是否透传，1表示透传，否则表示不透传
	 * 返回
	 * 		p_arouter : 主叫路由，格式为：/SIP/ip/num,caller,times,30|/SIP/ip/num,Caller,times,30 
	 * 		p_brouter : 被叫路由，格式同上
	 * 		p_servers ： 呼叫服务器列表，格式：ip,port,user,pass|ip,port,user,pass
	 */
	public function get_callback_route($params){

		$use_route_ext = isset($this->api_object->config->use_route_ext)?$this->api_object->config->use_route_ext:0;
		if($use_route_ext == 1){
			$sql = 'select * from ez_routing_db.sp_n_routing_choice_ext($1,$2,$3,$4,$5,$6,$7,$8)';
			//获得路由表
			$domain = isset($this->api_object->config->carrier_name)?$this->api_object->config->carrier_name:'utone';
			$resaler = isset($this->api_object->config->resaler)?$this->api_object->config->resaler:0;
			if(substr($params['pno'],0,3) == '861')
				$show_caller = substr($params['pno'],2);
			else if(substr($params['pno'],0,2) == '86')
				$show_caller = '0'.substr($params['pno'],0,2);
			else 
				$show_caller = $params['pno'];
			$rc = $this->exec_db_sp($sql, array(
				$domain,
				$resaler,
				$params['caller'],
				$params['callee'],
				$show_caller,
				$params['show'],
				$params['gateway_prefix'],
				''			//扩展的参数，格式name=value
			));
		}else{
			$sql = 'select * from ez_routing_db.sp_routing_choice($1,$2,$3,$4,$5)';
			//获得路由表
			$rc = $this->exec_db_sp($sql, array(
				$params['caller'],
				$params['callee'],
				$params['pno'],
				$params['show'],
				$params['gateway_prefix']
			));
		}
		return $rc;
	}
	
	/*************************Ophoen ****************************/
	
	/***************日志*****************/
	public function get_system_log_count($filter,$from,$to) {
		$params = array(
			$from,
			$to,
			$filter
		);
		//var_dump($params);
		$sql = "SELECT count(*) as totalcount FROM ez_log_db.tb_api_debug_log where (api_log_time between $1 and $2)  and (api_action like $3 or api_param like $3 or api_response like $3 or api_requests like $3 or api_mod_src like $3) ";
		//$this->push_return_data('params',$params);
		$r = $this->exec_db_sp($sql, $params);
		$this->push_return_data('r',$r);
		if(is_array($r)){
			return $r['totalcount'];
		}else{
			return 0;		
		}
	}
	
	public function log_get_system_list($filter,$from,$to,$offset,$limit) {
		$params = array(
			$from,
			$to,
			$filter,
			(int)$offset,
			(int)$limit
		);
		//var_dump($params);
		$sql = "SELECT * FROM ez_log_db.tb_api_debug_log where (api_log_time between $1 and $2)  and (api_action like $3 or api_param like $3 or api_response like $3 or api_requests like $3 or api_mod_src like $3) order by api_log_time desc offset $4 limit $5";
		
		$this->rows = array();
		$this->push_return_data('sql',$sql);
		$this->exec_query($sql, $params, pg_billing_handle_append_row , $this);
		return $this->rows;
	}
	/****************日志****************/
	public function crm_get_list($endpoint,$group,$filter,$offset=0,$limit=10){
		if(empty($offset))
			$offset = 0;			//默认从头开始
			
		if(empty($limit))
			$limit = 10;		//默认一次返回15行
		
		$params = array(
			$endpoint,
			$group,
			$filter,
			intval($limit),
			intval($offset)
		);
		$count_params = array(
			$endpoint,
			$group,
			$filter,
			null,
			null
		);
		//var_dump($params);
		//var_dump($count_params);
			
		$sp_sql = "select *from ez_crm_db.sp_crm_get_customer_list($1, $2, $3, $4, $5)";
		$sp_count = "select *from ez_crm_db.sp_crm_get_customer_list($1, $2, $3, $4, $5)";
		
		$rc = $this->exec_db_sp($sp_count, $count_params);
		if(is_array($rc))
		{	
			//$this->total_count = count($rc);
			$this->rows = array();
			$this->exec_query($sp_sql, $params, pg_billing_handle_append_row , $this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		return $this->rows;
	}
	
	public function crm_get_customer_list($param_array){
		$offset  = $param_array['offset'];
		$limit = $param_array['count'];
		$endpoint = $param_array['endpoint'];
		if(empty($offset))
			$offset = 0;			//默认从头开始
			
		if(empty($limit))
			$limit = 15;		//默认一次返回15行
		
		$params = array(
			$endpoint,
			intval($limit),
			intval($offset)
		);
		$count_params = array(
			$endpoint,
			null,
			null
		);
			
		$sp_sql = "select *from ez_crm_db.crm_get_member_list($1, $2, $3)";
		$sp_count = "select *from ez_crm_db.crm_get_member_list($1, $2, $3)";
		
		$rc = $this->exec_db_sp($sp_count, $count_params);
		if(is_array($rc))
		{	
			$this->total_count = count($rc);
			$this->rows = array();
			$this->exec_query($sp_sql, $params, pg_billing_handle_append_row , $this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		return $this->rows;
	}
	
	public function crm_add_server_member($param_array){
		$params = array(
			$param_array['endpoint'],
			$param_array['customer_id'],
			$param_array['customer_name'],
			$param_array['pno']
		);
		try {
			$sp_sql = "select *from ez_crm_db.sp_crm_add_server_member($1, $2, $3, $4)";
			$row = $this->exec_db_sp($sp_sql, $params);
		} catch (Exception $e) {
			$row = $e->getMessage();
		}
		return $row;
	}
	
	public function crm_edit_server_member($param_array){
		$params = array(
			intval($param_array['id']),
			$param_array['endpoint'],
			$param_array['customer_id'],
			$param_array['customer_name'],
			$param_array['pno']
		);
		try {
			$sp_sql = "select *from ez_crm_db.sp_crm_edit_server_member($1, $2, $3, $4, $5)";
			$row = $this->exec_db_sp($sp_sql, $params);
		} catch (Exception $e) {
			$row = $e->getMessage();
		}
		return $row;
	}
	
	public function crm_del_server_member($param_array){
		$params = array(
			intval($param_array['id']),
			$param_array['endpoint']
		);
		try {
			$sp_sql = "select *from ez_crm_db.sp_crm_del_server_member($1, $2)";
			$row = $this->exec_db_sp($sp_sql, $params);
		} catch (Exception $e) {
			$row = $e->getMessage();
		}
		return $row;
	}
	
	public function crm_set_routing_for_pno($endpoint,$pno,$call_params){
		$params = array(
			$endpoint,
			$pno,
			$call_params
		);
		try {
			$sp_sql = "select *from ez_crm_db.sp_crm_set_routing_for_pno($1, $2, $3)";
			$row = $this->exec_db_sp($sp_sql, $params);
		} catch (Exception $e) {
			$row = $e->getMessage();
		}
		return $row;
	}
	
	/**
	 * WFS相关的操作
	 */
	
	/**
	 * 根据bsn和imei检查设备库是否存在请求的设备，该函数用于更新时，检查设备在新设备库中是否存在，如果不存在则需要从老的库中把信息同步到新的WFS。
	 * 参数
	 * 	$p_bsn : 设备板载序列号
	 * 	$p_imei : 设备唯一标识
	 * 返回值
	 * 	1 : 设备存在
	 * -1 : 设备不存在
	 * -2 : 程序运行错误
	 */
	public function wfs_check_device_stock($p_bsn,$p_imei)
	{
		try {
			$sql_params = array(
				$params['p_bsn'],
				$params['p_imei']
			);
			$sp_sql = "select * from ez_wfs_db.sp_n_wfs_check_device_stock($1,$2)";
			$r = $this->exec_db_sp($sp_sql, $sql_params);
			return $r['p_return'];
		} catch (Exception $e) {
			$this->api_object->write_warning(array(
				"msg" => "Check device stock exception",
				"error" => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			));
			return -2;
		}
	}
	/**
	 * 功能：获得设备的配置参数，这个函数只从当前数据库的ez_wfs_db模式获得设备对应的配置信息。
	 * 参数：
	 * 	$params : 参数数组，包含：
	 * 		p_bsn : 设备的板载序列号
	 * 		p_imei: 设备的唯一串号
	 * 		p_pno : 手机上设置的本机号码
	 * 		p_swv : 设备的软件序列号
	 * 		p_hwv : 设备的硬件序列号
	 * 	$p_configures :返回的配置信息，此输出参数为对象
	 * 返回值：
	 *  3 : 成功，设备表中不存在此设备信息，利用VID和PID来获得设备信息
	 * 	2 : 成功，在设备表中找到设备信息，更新设备的版本号信息
	 *  1 : 成功，设备已经激活，返回信息里会包含激活的手机号码信息
	 *  -1 : 设备表中设备信息不存在，也没有设置VID和PID信息
	 *  -2 : 设备已经过了激活期，不能激活
	 *  -3 : 程序异常，PHP或者存储过程出错
	 */
	public function wfs_get_configure($params,$p_configures)
	{
		$p_configures = new stdClass();
		try {
			$sql_params = array(
				$params['p_bsn'],
				$params['p_imei'],
				$params['p_no'],
				$params['p_pid'],
				$params['p_vid'],
				$params['p_swv'],
				$params['p_hwv']
			);
			$sp_sql = "select * from ez_wfs_db.sp_n_wfs_get_configure($1,$2,$3,$4,$5)";
			$update_return = $this->exec_db_sp($sp_sql, $sql_params);
			$p_configures->result = $update_return['p_return'];
			if(isset($update_return['p_billing_api']))
				$p_configures->billing_api = $this->api_object->json_decode($update_return['p_billing_api']);
			if(isset($update_return['p_oem_params']))
				$p_configures->oem_params = $this->api_object->json_decode($update_return['p_oem_params']);
			if(isset($update_return['p_carrier_params']))
				$p_configures->carrier_params = $this->api_object->json_decode($update_return['p_carrier_params']);
		} catch (Exception $e) {
			$p_configures->result = -3;
			$p_configures->message = $e->getMessage();
			$this->api_object->write_warning(array(
				"msg" => "Get configure exception",
				"error" => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			));
		}
	}
	
	/**
	 * 更新设备信息到WFS设备库，此函数用于通就的WFS系统同步数据到WFS设备库，同时也用于WFS之间同步数据。
	 * 
	 */
	public function wfs_update_device_stock($params)
	{
		$sql_params = array(
			$params['p_bsn'],			//板载序列号
			$params['p_guid'],			//设备唯一序列号
			$params['p_resaler'],		//设备所属代理商
			$params['p_charge_plan'],	//设备使用的计费方案
			$params['p_bind_pno'],		//设备绑定的手机号码
			$params['p_bind_epno'],		//设备绑定的终端号码
			$params['p_status'], 		//设备的状态
			$params['p_active_time'],	//设备的激活时间
			$params['p_os_time'],		//设备出库时间
			$params['p_is_time'],		//设备入库时间
			$params['p_remark'],		//设备备注信息
			$params['p_pid'],			//设备的PID
			$params['p_vid'],			//设备的VID
			$params['p_config_json']	//设备的配置信息
		);
		$sp_sql	= "select * from ez_wfs_db.sp_ophone_synchro_wfs($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14) ";
		try {
			$r = $this->exec_db_sp($sp_sql, $sql_params);
			return $r['p_return'];
		} catch (Exception $e) {
			$this->api_object->write_warning(array(
				"msg" => "Update device stock exception",
				"error" => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			));
			return -2;
		}
	}
}


?>
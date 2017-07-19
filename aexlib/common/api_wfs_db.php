<?php

date_default_timezone_set('Asia/Chongqing');
/*
	定义wfs的数据库类
*/
require_once dirname(__FILE__).'/api_pgsql_db.php';

/*
	将SQL返回的行附加到数组
	参数
		$context : 结果数组
		$index : 行序号
		$row : 行数组
*/
function pg_wfs_handle_append_row($context,$index,$row){
//	foreach($row as $key=>$value)
//		if(is_string($value))
//			$row[$key] = mb_convert_encoding($value,"UTF-8","GB2312");
	array_push($context->rows,$row);
	//echo sprintf("Row[%d]=%d<br>",$index,count($context->rows));
	//var_dump($context);
}

class class_wfs_db extends api_pgsql_db{
	var $rows = array();
	var $total_count = 0;
	
	//重写方法,获取总行数
	public function exec_db_sp($sql,$params=array())
	{
		$rdata = array();
		$result = pg_query_params($this->dblink,$sql,$params);
		if(!$result){
			$this->write_log(_LEVEL_WARNING_,_DB_SQL_ERROR_,$this->get_last_error());
			exit;
		}else{
			$this->total_count= $this->result_num_rows($result);
			$index = 0;
			$rdata = $this->get_sp_return_with_array($result);
			pg_free_result($result);
		}
		return $rdata;
	}

	/**
	 *	creater: lion wang
	 *	time: 2010.6.1
	 *	@param: $carrier:  agent id
	 *			$offset
	 *			$count		limit
	 *			$p_access_no	=''
	 *			$p_phone	='' 
	 *	caption: get access no and phone 
	 **/
	public function get_access_no_list($offset='', $count='',$p_access_no='', $p_phone='' ,$carrier=-1){
		
		if(empty($offset))
			$offset = 0;			//默认从头开始
		if(empty($count))
			$count = 15;		//默认一次返回15行
		
		$params = array(
			"$p_access_no",
			"$p_phone",
			"$count",
			"$offset"
		);
		
		$count = array(
			"$p_access_no",
			"$p_phone",
			'null',
			'null'
		);
		
		$sp_sql = "SELECT *FROM ez_group_caller_db.sp_get_access_no( $1, $2, $3, $4)";
		$sp_count = "SELECT *FROM ez_group_caller_db.sp_get_access_no($1, $2, $3, $4)";
		
		$rc = $this->exec_db_sp($sp_count, $count);
		if(is_array($rc))
		{
			$this->rows = array();
			$this->exec_query($sp_sql, $params, pg_wfs_handle_append_row, $this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		//$this->set_return_code(101);
		return $this->rows;
	}
	
	/**
	 *	creater: lion wang
	 *	time: 2010.6.1
	 *	@param: $carrier: 
	 *			$offset
	 *			$count		limit
	 *			$p_access_no	=''
	 *			$p_phone	='' 
	 *	caption: add access no and phone 
	 **/
	public function access_add($p_access_no='', $p_phone=''){
		if(empty($offset))
			$offset = 0;			//默认从头开始
		if(empty($count))
			$count = 15;		//默认一次返回15行
		
		$params = array(
			"$p_access_no",
			"$p_phone",
		);
		
		$sp_sql = "SELECT *FROM  ez_group_caller_db.sp_access_no_add( $p_access_no, $p_phone);";
	
		$rc = $this->exec_db_sp($sp_count, $count);
		if(is_array($rc))
		{
			if ($rc['p_return'] > 0) {
				return '101';
			}else{
				return '-101';
			}
			
		}else{
			return '-102';
		}
	}
	
	/**
	 *	creater: lion wang
	 *	time: 2010.8.26
	 *	@param: $carrier:  agent id
	 *			$offset
	 *			$count		limit
	 *			$p_access_no	=''
	 *			$p_phone	='' 
	 *	caption: web_get_p_list_by_pid_vid
	 **/
	public function web_get_p_list_by_pid_vid($web_params){
		$params = array(
			$web_params['p_id'],
			$web_params['v_id'],
			$web_params['query_condition'],
			$web_params['start_time'],
			$web_params['end_time'],
			$web_params['limit'],
			$web_params['start']	
		);
		$count = array(
			$web_params['p_id'],
			$web_params['v_id'],
			'null',
			'null',
			'null',
			'null',
			'null'
		);
		$sp_sql = "SELECT *FROM ez_wfs_db.sp_wfs_get_p_list_by_pid_vid($1,$2,$3,$4,$5,$6,$7);";
		$sp_count = "SELECT *FROM ez_wfs_db.sp_wfs_get_p_list_by_pid_vid($1,$2,$3,$4,$5,$6,$7);";
		
		$rc = $this->exec_db_sp($sp_count, $count);
		if(is_array($rc))
		{
			$this->rows = array();
			$this->exec_query($sp_sql, $params, pg_wfs_handle_append_row, $this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		//$this->set_return_code(101);
		return $this->rows;
	}
	
	/**
	 *	creater: lion wang
	 *	time: 2010.6.1
	 *	@param: $carrier: 
	 *			$imei
	 *			$carrier_name		
	 *	caption: check stock is or isn't exist 
	 **/
	public function web_stock_check($params_attay){
		$sp_sql = "SELECT *FROM  ez_wfs_db.sp_web_stock_check( $1, $2);";
		$rc = $this->exec_db_sp($sp_sql, $params_attay);
		if(is_array($rc))
		{
			return $rc['p_return'];
		}else{
			return '-102';
		}
	}
	
	/**
	 *	creater: lion wang
	 *	time: 2010.6.1
	 *	@param: $carrier: 
	 *			$imei
	 *			$carrier_name		
	 *	caption: update stock info
	 **/
	public function web_stock_update($params_attay){
		$sp_sql = "SELECT *FROM  ez_wfs_db.sp_n_web_update_stock( $1, $2,$3,$4,$5,$6);";
		$rc = $this->exec_db_sp($sp_sql, $params_attay);
		if(is_array($rc))
		{
			return $rc['p_return'];
		}else{
			return '-102';
		}
	}
	
	/**
	 *	creater: lion wang
	 *	time: 2010.6.1
	 *	@param: $carrier: 
	 *			$imei
	 *			$carrier_name		
	 *	caption: update stock info
	 **/
	public function update_active_info($params_attay){
		$sp_sql = "SELECT *FROM  ez_wfs_db.sp_update_device_active_info( $1, $2, $3, $4);";
		$rc = $this->exec_db_sp($sp_sql, $params_attay);
		if(is_array($rc))
		{
			return $rc['p_return'];
		}else{
			return '-102';
		}
	}
	
	/**
	 *	creater: lion wang
	 *	time: 2010.6.1
	 *	@param: $carrier: 
	 *			$imei
	 *			$carrier_name		
	 *	caption: Record first recharge info 
	 **/
	public function record_first_recharge($params_attay){
		$sp_sql = "SELECT *FROM  ez_wfs_db.sp_record_first_recharge( $1, $2, $3, $4, $5, $6,$7,$8,$9);";
		$rc = $this->exec_db_sp($sp_sql, $params_attay);
		if(is_array($rc))
		{
			return $rc['p_return'];
		}else{
			return '-102';
		}
	}
	
}


?>
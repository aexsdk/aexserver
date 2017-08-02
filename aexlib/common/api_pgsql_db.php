<?php
/**
 * Copyright Intermesh
 *
 * This file is part of Group-Office. You should have received a copy of the
 * Group-Office license along with Group-Office. See the file /LICENSE.TXT
 *
 * If you have questions write an e-mail to info@intermesh.nl
 *
 * @copyright Copyright Intermesh
 * @version $Id: about.php 1088 2008-10-07 13:02:06Z mschering $
 * @author Merijn Schering <mschering@intermesh.nl>
 * @package go.database
 */

/**
 *
 * @version $Id: imap.class.inc 1201 2008-10-22 18:23:34Z mschering $
 * @author Merijn Schering <mschering@intermesh.nl>
 * @package go.database
 * @access public
 */
require_once(dirname(__FILE__) . "/api_base_db.php");

class api_pgsql_db extends api_base_db{
	public $connect_string;
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
		if(is_array($config)){
			if(isset($config['CONNECT_STRING']))
				$this->connect_string = $config['CONNECT_STRING'];
		}
		$this->connect();
	}
	
	function __destruct() {
		//$this->disconnect();
		parent::__destruct();
    }

	/**
	 * Connnects to the database
	 *
	 * @return resource The connection link identifier
	 */
	public function connect()
	{
		// Server in the this format: <computer>\<instance name> or 
		// <server>,<port> when using a non default port number
		//$this->api_object->write_hint($this->connect_string);
		if(empty($this->connect_string)){
			$this->write_log(_LEVEL_ERROR_,_DB_CONNECT_ERROR_,json_encode($this->api_object->config));
			return FALSE;
		}
		$this->dblink = pg_connect($this->connect_string);
		if($this->dblink)
		{
			return true;//mssql_select_db($this->dbname,$this->dblink);
		}else{
			$this->write_log(_LEVEL_ERROR_,_DB_CONNECT_ERROR_,$this->get_last_error());
			return false;
		}
	}

	/**
	 * Frees the memory associated with a result
	 * return void
	 */
	function disconnect() {
		if($this->dblink)
			pg_close($this->dblink);
	}

	/**
	 * 
	 */
	public function exec_query($sql,$params=array(),$each_row_func=null,$connext=null)
	{
		//$parameterString = join(',',$params);
		//$sql                 			= "select * from ".$p_sql."(".$parameterString.")";
		//var_dump($sql);
		$result = pg_query_params($this->dblink,$sql,$params);
		if($result){
			$index = 0;
			//$this->push_return_data('ExecQuery',$sql);
			$this->write_trace(5,sprintf('Exec sql:%s',$sql));
			while($result && ($row = $this->fetch_to_array($result))){
				//var_dump($row);
				if(is_array($row) and $each_row_func){
					$each_row_func($connext,$index,$row);
					$index += 1;
				}
			}
		}else{
			$this->set_return_code(_DB_SQL_ERROR_);
			$this->write_error(sprintf("sql=%s<br>param=<hr>%s<br><hr>error=%s<br>trace=<hr>%s<br>",$sql,array_to_string("<br>",$params),$this->get_last_error(),get_trace_string()));
			exit;
		}
	}

	public function exec_proc($sql,$params=array(),$each_row_func=null,$connext=null)
	{
		$result = pg_query_params($this->dblink,$sql,$params);
		if($result){
			$index = 0;
			//$this->push_return_data('ExecProc',$sql);
			$this->write_trace(5,sprintf('Exec sql:%s',$sql));
			while($result && ($row = $this->fetch_to_array($result))){
				//var_dump($row);
				if(is_array($row) and $each_row_func){
					$each_row_func($connext,$index,$row);
					$index += 1;
				}
			}
			//pg_free_result($result);
		}else{
			$this->set_return_code(_DB_SQL_ERROR_);
			$this->write_error(sprintf("sql=%s<br>param=<hr>%s<br><hr>error=%s<br>trace=<hr>%s<br>",$sql,array_to_string("<br>",$params),$this->get_last_error(),get_trace_string()));
			exit;
		}
	}

	public function exec_db_sp($sql,$params=array())
	{
		$rdata = array();
		//$this->write_hint(sprintf("sql=%s\r\n%s",$sql,array_to_string("\r\n",$params)));
		$result = pg_query_params($this->dblink,$sql,$params);
		if($result){
			//$index = 0;
			//$this->push_return_data('ExecDBSP',$sql);
			$rdata = $this->get_sp_return_with_array($result);
			pg_free_result($result);
		}else{
			$this->set_return_code(_DB_SQL_ERROR_);
			$this->write_error(sprintf("sql=%s,\r\nerror=%s,\r\ntrace=\r\n%s\r\n",
				$sql,$this->get_last_error(),get_trace_string()));
			exit;
		}
		return $rdata;
	}

	/*
	*/
	public function exec_db_send_sp($p_sql,$parameter){
		//echo $p_sql;
		$result =  pg_send_query_params($this->dblink,$p_sql,$parameter);
		return $result;
	}

	public function result_num_rows($result)
	{
		return pg_num_rows ($result);
	}

	public function get_last_error(){
		if($this->dblink)
			return pg_last_error($this->dblink);
		else
			return '';
	}

	public function fetch_to_array($result)
	{
		if(!$result)
			return false;
		else 
			return pg_fetch_array($result,NULL,PGSQL_ASSOC);
	}

}

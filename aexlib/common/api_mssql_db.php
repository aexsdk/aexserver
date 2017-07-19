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

class api_mssql_db extends api_base_db{
	public $host;			//��ݿ��ַ
	public $dbname;		//��ݿ����
	public $user;			//��¼��ݿ���û���
	public $password;	//��¼��ݿ������
	
	public $api_object;		//ȫ�ֿ��ƶ���
	public $dblink;			//��ݿ�l�Ӿ��
	/**
	 * ���캯�������ò����Լ�
	 */
	public function __construct($config,$api_obj)
	{
		parent::__construct($config,$api_obj);
		if(is_array($config)){
			if(isset($config['HOST']))
				$this->host = $config['HOST'];
			if(isset($config['DBNAME']))
				$this->dbname = $config['DBNAME'];
			if(isset($config['USER']))
				$this->user = $config['USER'];
			if(isset($config['PASSWORD']))
				$this->password = $config['PASSWORD'];
		}
		$this->connect();
	}
	function __destruct() {
		$this->disconnect();
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
		if(!function_exists('mssql_connect'))return false;
		$this->dblink = mssql_connect($this->host, $this->user,$this->password);
		if(!$this->dblink)
		{
			$this->write_log(_LEVEL_ERROR_,_DB_CONNECT_ERROR_,$this->get_last_error());
			return false;
		}else{
			return mssql_select_db($this->dbname,$this->dblink);
		}
	}

	/**
	 * Frees the memory associated with a result
	 * return void
	 */
	function disconnect() {
		if($this->dblink && function_exists('mssql_close'))
			mssql_close($this->dblink);
	}

	/**
	 * Queries the database
	 *
	 * @param string $sql
	 * @param string $types The types of the parameters. possible values: i, d, s, b for integet, double, string and blob
	 * @param mixed $params If a single or an array of parameters are given in the statement will be prepared
	 *
	 * @return object The result object
	 */
	public function exec_query($sql,$params=array(),$each_row_func=null,$connext=null)
	{
		//$parameterString = join(',',$params);
		//$sql                 			= "select * from ".$p_sql."(".$parameterString.")";
		//var_dump($sql);
		if(!function_exists('mssql_query'))return false;
		$result = mssql_query($sql,$this->dblink);
		if(!$result){
			//���ش洢��̳��
			//echo 'init error';
			$this->write_log(_LEVEL_ERROR_,_DB_SQL_ERROR_,$this->get_last_error());
		}else{
			$index = 0;
			if($each_row_func){
				while($row = $this->fetch_to_array($result)){
					if(is_array($row) and $each_row_func){
						$each_row_func($connext,$index,$row);
						$index += 1;
					}
				}
				//$this->write_hint(sprintf('mssql exec_query each_row_func,rows=%d',$index));
				mssql_free_result($result);
			}else{
				//$this->write_hint('mssql exec_query no each_row_func');
				$data = $this->get_sp_return_with_array($result);
				mssql_free_result($result);
				return $data;
			}
		}
	}
	/*
	 * 通过Radius调用MSSQL的存储过程
	*/
	public function exec_radius($sp,$params)
	{
		require_once(dirname(__FILE__) . "/api_radius_funcs.php");
		//var_dump($this->api_object->config);
		$r = radius_execute_proc($sp,$params,$this->api_object->config);
		//var_dump($r);
		if(!is_array($r)){
			//执行错误返回
			$this->set_return_code($r);
			return $r;
		}else{
			$this->set_return_code($r['RETURN-CODE']);
			return $r;
		}
	}
	
	public function exec_proc($sql,$params=array(),$each_row_func=null,$connext=null)
	{
		return $this->exec_radius($sql,$params);
		/*$stmt = mssql_init($sql,$this->dblink);
		if(!$stmt){
			//echo 'init error';
			$this->write_log(_LEVEL_WARNING_,_DB_SQL_ERROR_,$this->get_last_error());
		}else{
			foreach($params as $key=>$value)
			{
				mssql_bind($stmt,$key,$value,SQLVARCHAR,false,false,255);
			}
			$return_value =0;
			mssql_bind($stmt,'@RETURN_VALUE',$return_value,SQLINT4,true,true,4);
			$result = mssql_execute($stmt,true);
			if(!$result){
				//echo 'exec error:';
				$this->write_log(_LEVEL_WARNING_,_DB_SQL_ERROR_,$this->get_last_error());
			}else{
				$index = 0;
				if($each_row_func){
					while($row = $this->fetch_to_array($result)){
						if(is_array($row) and $each_row_func){
							$each_row_func($connext,$index,$row);
							$index += 1;
						}
					}
				}else{
					$data = $this->get_sp_return_with_array($result);
					mssql_free_statement($stmt);	
					return $data;
				}
			}
			mssql_free_statement($stmt);	
		}*/
	}

	public function exec_db_sp($sql,$params=array())
	{
		$rdata = array();
		if(!function_exists('mssql_query'))return false;
		$result = mssql_query($sql,$this->dblink);
		if(!$result){
			//���ش洢��̳��
			//echo '���ش洢��̳��';
			$this->write_log(_LEVEL_WARNING_,_DB_SQL_ERROR_,$this->get_last_error());
			return $rdata;
		}else{
			$index = 0;
			$rdata = $this->get_sp_return_with_array($result);
			mssql_free_result($result);
		}
		return $rdata;
	}

	/*
		ִ����ݿ��ϵĴ洢��̣����ǲ��ȴ�ؽ������п��ܳ���ִ�еĴ洢���ʹ�ô˺���
	*/
	public function exec_db_send_sp($p_sql,$params){
		$stmt = mssql_init($sql,$this->dblink);
		if(!$stmt){
			//echo '��ʼ���洢���ʧ��';
			$this->write_log(_LEVEL_WARNING_,_DB_SQL_ERROR_,$this->get_last_error());
		}else{
			foreach($params as $key=>$value)
			{
				mssql_bind($stmt,$key,$value,SQLVARCHAR);
			}
			$result = mssql_execute($stmt,true);
			if(!$result){
				//���ش洢��̳��
				$this->write_log(_LEVEL_WARNING_,_DB_SQL_ERROR_,$this->get_last_error());
			}else{
			}
			mssql_free_statement($stmt);	
		}
	}

	public function get_last_error(){
		if($this->dblink)
			return  mssql_get_last_message();//($this->dblink);
		else
			return '';
	}
	
	public function result_num_rows($result)
	{
		return mssql_num_rows ($result);
	}
	
	public function fetch_to_array($result)
	{
		return mssql_fetch_array($result,MSSQL_ASSOC);
	}

}

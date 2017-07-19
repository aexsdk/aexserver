<?php
/**
 */
require_once(dirname(__FILE__) . "/api_base_class.php");

class api_base_db extends api_base_class{	
	//public $return_code;	//数据库存储过程返回值
	public $dblink;			//数据库连接句柄
	/**
	 * 构造函数，传入配置参数以及
	 */
	public function __construct($config,$api_obj)
	{
		parent::__construct($config,$api_obj);
		//$this->connect();
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
		return false;
	}

	/**
	 * Frees the memory associated with a result
	 * return void
	 */
	function disconnect() {
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
		return false;
	}

	public function exec_proc($sql,$params=array(),$each_row_func=null,$connext=null)
	{
		return false;
	}
	
	public function exec_db_sp($sql,$params=array()){
		return false;
	}
	
	public function exec_db_send_sp($sql,$params=array()){
		return false;
	}

	public function result_num_rows($result)
	{
		return false;
	}
	
	public function fetch_to_array($result)
	{
		return false;
	}

	public function get_last_error(){
		return '';
	}
	/*
		处理返回一行数组的存储过程结果，返回的如果是数组表明数据库存储过程正确执行并返回。错误的情况已经处理并作了默认的回应
	*/
	public function get_sp_return_with_array($result){
		//var_dump($result);
		if(!$result){
			$err = get_last_error();
			//$this->return_code = '-2';		//执行存储过程失败
			$this->write_log(_LEVEL_ERROR_,_DB_SQL_ERROR_,$err);
			//$this->push_return_data('DB-ERROR',$err);
			//$this->write_response();
			return false;
		}else{
			$api_r	=	$this->fetch_to_array($result);
			if(is_array($api_r)){
				if(!isset($api_r['p_return']) or empty($api_r['p_return']))
				{
					if(isset($api_r['n_return_value']) and !empty($api_r['n_return_value'])) {
						$this->set_return_code($api_r['n_return_value']);
						$api_r['p_return'] = $api_r['n_return_value'];
					}else{
						//成功调用存储过程，但是存储过程没有p_return和n_return_value等返回参数
						$this->set_return_code(98);
						$api_r['p_return'] = 98;
					}
				}else{
					//echo $api_r['p_return'];
					$this->set_return_code($api_r['p_return']);
				}
				return $api_r;
			}else{
				//var_dump($api_r);
				//$this->return_code = '-10';		//return not a array
				//$this->return_data['DB-ERROR'] = pg_last_error($dblink);
				//$this->write_response();
				$this->write_log(_LEVEL_HINT_,_DB_NOT_ARRAY_,$this->get_last_error());
				return $api_r;
			}
		}
	}

}

?>
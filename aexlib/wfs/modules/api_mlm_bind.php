<?php
/*
	执行Action的行为
*/
api_uphone_action($p_params);

/*
	定义Action具体行为
*/
class class_api_uphone_action extends class_api_base{
	/*
		overide this function for custom response format
	*/
	public function write_response(){
		//echo sprintf("md5_key=%s<br>",$this->md5_key);
		//echo "<br>".$this->return_code."<br>";
		//$resp = $this->write_return_params();			//write old format response
		///$resp = $resp.$this->write_return_xml();					//write xml format response
		$resp = $this->write_return_params_with_json();		
		echo $resp;
		$this->resp_data = $resp;
	}
	
	/*
		获得错误代码字符串，各个操作里面有可能需要重写子函数，以实现更加详细的错误字符串表达
	*/
	function get_error_message(){
		$msg = $this->error_msg->get_message($this->return_code);
		//echo $msg."<br>";
		if($this->return_code > 100 or $this->return_code < -100){
			//如果是执行存储过程的返回信息，则把v_account作为第二个参数，那么在相应语言串里就可以使用%1s获得v_account的值
			$msg = sprintf($msg,$this->return_code,$this->params['api_params']['v_Account']);
		}else{
			$msg = sprintf($msg,$this->return_code);
		}
		//echo $msg."<br>";
		return $msg;
	}
	
}

function api_handle_row($connext,$index,$row){
		reset($row);
		foreach($row as $key=>$value){
			if($connext)
				$connext->push_return_data($key,$value);
		}
}
/*
/uphone/ad/index.php?v_Account=13602648557&v_Password=A4984B3A3FA6&v_Lang=2052&VID=1001&PID=6003&SN=1d86c78a-7d6e-429d-8530-d889a3507d58
*/
function api_uphone_action($p_params) {
	require_once $p_params['common-path'].'/api_mssql_db.php';
	
	//var_dump($p_params);
	$api_obj = new class_api_uphone_action(_DB_CONNECTION_STR_,$p_params['md5_key'],_API_PREFIX_,$p_params);
	//存储过程为合成存储过程
	$billingdb = new api_mssql_db($p_params['config']);
	
	$billingdb->exec_query($sql,array(),api_handle_row,$api_obj);
	
	$api_obj->write_response();
	
}
?>

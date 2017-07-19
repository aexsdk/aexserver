<?php
/*
	执行Action的行为
*/
api_get_config($p_params);

/*
	定义Action具体行为
*/
class class_api_get_config extends class_api_base{
	/*
		overide this function for custom response format
	*/
	public function write_response(){
		//echo sprintf("md5_key=%s<br>",$this->md5_key);
		//echo "<br>".$this->return_code."<br>";
		//$resp = $this->write_return_params();			//write old format response
		///$resp = $resp.$this->write_return_xml();					//write xml format response
		//$resp = $this->write_return_params_with_json();
		//$resp = $resp. $this->write_return_param('response-code',$this->return_code);
		foreach ($this->return_data as $key => $value){
			$resp .= $key."=".$value.",";
		}
		
		echo $resp;
		$this->resp_data = $resp;
	}
}

function api_get_config($p_params) {
	
	$sp_name = "ez_wfs_db.sp_wfs_api_get_info_by_imei";
	$sp_sql	= "select * from ".$sp_name."($1,$2,$3,$4,$5) ";

	//var_dump($p_params);
	$api_obj = new class_api_get_config(_DB_CONNECTION_STR_,$p_params['md5_key'],_API_PREFIX_,$p_params);
	/*$parameter ['v_bsn'], $parameter ['v_imei'], $parameter ['v_pno'],
	         $parameter ['v_upass'] ,$parameter['v_uname']*/
	$mlm_params = array(
		$api_obj->params['api_params']['bsn'], 
		//$api_obj->params['api_params']['imei'], 
		'357586007723551',
		$api_obj->params['api_params']['v_id'],
		$api_obj->params['api_params']['p_id'],
		//$api_obj->params['api_params']['type']
		'ophone'
	);
	$result = $api_obj->exec_db_sp($api_obj->dblink,$sp_sql,$mlm_params,$sp_name);
	
	$mlm_return = $api_obj->get_sp_return_with_array($api_obj->dblink,$result);
	//var_dump($mlm_return);
	if(is_array($mlm_return)){
		if($api_obj->return_code > 0)
		{
			//存储过程返回成功，写入成功的参数和代码
			//echo "存储过程返回成功，写入成功的参数和代码";
			$api_obj->push_return_data("api_ip", $mlm_return['v_api_ip']);
			$api_obj->push_return_data("serect", $mlm_return['v_serect']);
			$api_obj->push_return_data("attribute", $mlm_return['v_attribute']);
			if($api_obj->return_code < 100)
				$api_obj->return_code = $api_obj->return_code + 100;
		}else{
			//存储过程执行成功，返回值小于0，表示请求失败。下面可以针对每一个失败返回失败需要的参数，如果没有则系统会自动处理返回代码和错误信息
			//错误信息根据语言不同在相应的XML文件中定义
			if($api_obj->return_code > -100)
				$api_obj->return_code = $api_obj->return_code - 100;		//如果返回值在0~-100之间，则调整为-100以上，-100以内的系统使用
		}
		//写返回的信息
		$api_obj->write_response();
	}
	
}
?>

<?php
/*
	执行Action的行为
*/
api_ophone_action($api_object);

/*
	定义Action具体行为
*/


function get_message_callback($api_obj,$context,$msg){
	return sprintf($msg,$api_obj->return_code);
}
/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj,$context){
	$resp = $api_obj->write_return_xml();
	return $resp;
}


/*
传入参数：
	action=follow_me,bsn=ophone_bsn,imei=357116020287780,pno=15013879952,pin=12806000001,pass=222222,
	uphone=555555,did=55222
*/
function api_ophone_action($api_obj)
{
	echo $api_obj->config->ezip_db_config;
	if(empty($api_obj->config->ezip_db_config)){
		$api_obj->return_code = "-503";//该功能配置信息不存在，功能未开启
	    //$api_obj->return_code = $rdata['ReturnValue'];
	    //写返回的信息
	    $api_obj->write_response();
	    exit;
	}
	
	
	$wfs_db = new api_pgsql_db($api_obj->config->ezip_db_config, $api_obj);	
		
	$sp_sql = "SELECT * FROM ezsip.sp_ophone_delete_followme($1)";
	$wfs_params = array(
	    $api_obj->params["api_params"]['pin']
    );

	$web_result = $wfs_db->exec_db_sp($sp_sql,$wfs_params);
	
	$api_obj->return_code = $web_result['n_return_value'];//执行存储过程操作

	//写返回的信息
	$api_obj->write_response();
}
?>

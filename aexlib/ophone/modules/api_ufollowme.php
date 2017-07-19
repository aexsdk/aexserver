<?php

/*
 * 执行操作
 * */
api_ophone_action($api_object);


function get_message_callback($api_obj,$context,$msg){
	return sprintf($msg,$api_obj->return_code, $api_obj->return_data['pno']);
}
/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj,$context){
	$resp = $api_obj->write_return_xml();
	return $resp;
}


function api_ophone_action($api_obj){
	    if(substr($api_obj->params['api_params']['pno'], 0, 1) != '0'){
	     	$v_phone_num = '0'.$api_obj->params['api_params']['pno']; //show callee number
	    }else{	    	
	        $v_phone_num = $api_obj->params['api_params']['pno']; //show callee number
	    }
		
		$sp_sql = "SELECT * FROM ezsip.sp_ophone_update_followme($1,$2)";
		$wfs_params = array(
		            $api_obj->params["api_params"]['pin'],
		            $v_phone_num
		            
        );
        
		if(empty($api_obj->config->ezip_db_config)){
			$api_obj->return_code = "-503";//该功能配置信息不存在，功能未开启
		    //写返回的信息
		    $api_obj->write_response();
		    exit;
		}
		$wfs_db = new api_pgsql_db($api_obj->config->ezip_db_config, $api_obj);	
		$api_obj->set_callback_func(get_message_callback,write_response_callback,$wfs_db);	
		$rdata = $wfs_db->exec_db_sp($sp_sql,$wfs_params);
		 
		$api_obj->push_return_data('pno', $v_phone_num);
		$api_obj->return_code = $rdata['n_return_value'];//执行存储过程操作

		//写返回的信息
		$api_obj->write_response();
	   		
}

?>
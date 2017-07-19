<?php
	/*
		执行Action的行为
	*/
	api_get_wfs_info($api_object);
	/*
	 * 获取wfs的相关信息
	*/
	
	
	function api_get_wfs_info($api_obj) {
		$type = empty($_REQUEST['type']) ? 0 : $_REQUEST['type'];		
		$value = empty($_REQUEST['value']) ? 0 : $_REQUEST['value'];	
		$wfs_db = new api_pgsql_db($api_obj->config->wfs_db_config, $api_obj);
		
		if ($type == '1') {
			$sp_sql = "SELECT * FROM ez_wfs_db.sp_wfs_select_ophone_bind_info($1)";
			
			$web_params = array(
				$value
			);
			$web_result = $wfs_db->exec_db_sp($sp_sql,$web_params);
			if(is_array($web_result)){
				//写返回的信息
				//$api_obj->write_response();
				echo json_encode($web_result);
			}else {
				echo 'error';
			}
		}else{
			$sp_sql = "SELECT * FROM ez_wfs_db.sp_wfs_select_ophone_active_info($1)";
		
			$web_params = array(
				$value	
			);
			$web_result = $wfs_db->exec_db_sp($sp_sql,$web_params);
			if(is_array($web_result)){
				//写返回的信息
				//$api_obj->write_response();
				echo json_encode($web_result);
			}else {
				echo 'error';
			}
		}
	}
?>

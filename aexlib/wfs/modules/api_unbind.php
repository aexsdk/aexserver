<?php
	/*
		执行Action的行为
	*/
	api_unbind($api_object);

	function api_unbind($api_obj) {
		$p_query_value = addslashes($_POST ['value']);
		$p_query_type = addslashes($_POST ['type']);
		
		if (!empty($p_query_value))
		{
			$sp_sql = "SELECT * FROM ez_wfs_db.sp_wfs_cancel_ophone_bind($1)";
			$web_params = array($p_query_value);
		}else{
			echo -4;
			exit();
		}
		$wfs_db = new api_pgsql_db($api_obj->config->wfs_db_config, $api_obj);		
		$web_result = $wfs_db->exec_db_sp($sp_sql,$web_params);
		//echo $web_result['n_return_value'];
		$result = array(
			'p_return' => $web_result['n_return_value']
		);
		echo json_encode($result);
	}
?>

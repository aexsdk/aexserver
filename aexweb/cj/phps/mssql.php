<?php
	function getE164($params) {
		$DeviceID = $params['gu_id'];
		$Gu_ID = $params['wfs_attribute'];
		$AgentID = $params['agent_id'];
		$Agent_cs = $params['agent_cs'];
		$Call_cs = $params['call_cs'];
		$Balance = $params['balance'];
		$CurrencyType = $params['currency_type'];
		$valid_date_no = $params['valid_date_no'];
		$HireNumber = $params['hire_number'];
		$FreePeriod = $params['free_period'];
		$product_type_prefix = $params['product_type_prefix'];
		$cs_prefix = $params['cs_prefix'];
		$agent_prefix = $params['agent_prefix'];
			
		$cfgs = parse_ini_file('conf.ini',true);
		$host = $cfgs['MSDatabase']['db.config.host'];
		$username = $cfgs['MSDatabase']['db.config.username'];
		$password = $cfgs['MSDatabase']['db.config.password'];
		$dbname = $cfgs['MSDatabase']['db.config.dbname'];
		$dblink = mssql_connect($host, $username, $password);
		if ($dblink) {
			if (mssql_select_db($dbname, $dblink)) {
				$res = mssql_query("sp_Devices_RegisterE164 '$DeviceID','$Gu_ID',$AgentID,$Agent_cs,$Call_cs,$Balance,'$CurrencyType',$valid_date_no,$HireNumber,$FreePeriod,'$product_type_prefix','$cs_prefix','$agent_prefix'");
				$rows = mssql_fetch_array($res,MSSQL_NUM);
			} else {
				$rows = array('ErrorInfo' => 'No DB!');
			}
		} else {
			$rows = array('ErrorInfo' => '没有数据库连接！');
		}
		//$rows = "sp_Devices_RegisterE164 '$DeviceID','$Gu_ID',$AgentID,$Agent_cs,$Call_cs,$Balance,'$CurrencyType',$valid_date_no,$HireNumber,$FreePeriod,'$product_type_prefix','$cs_prefix','$agent_prefix'";
		
		return $rows;
	}
?>
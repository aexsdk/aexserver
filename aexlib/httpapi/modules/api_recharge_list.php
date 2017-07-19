<?php
api_action($api_object);

//查询CDR记录
function api_action($api_obj){
	$resaler = -1;	//目前使用运营商级别，以后从Session中获得
	$agent = empty($_REQUEST['agent'])? '' : trim($_REQUEST['agent']);
	$endpoint = empty($_REQUEST['pin'])? '' : trim($_REQUEST['pin']);
	$type = empty($_REQUEST['type'])? '' : trim($_REQUEST['type']);
	$from = empty($_REQUEST['from'])? '' : trim($_REQUEST['from']);
	$to = empty($_REQUEST['to'])? '' : trim($_REQUEST['to']);
						
	$offset = empty($_REQUEST['offset'])? (empty($_REQUEST['start']) ? 0 : $_REQUEST['start']) : $_REQUEST['offset'];
	$count = empty($_REQUEST['count'])? (empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit']) : $_REQUEST['count'];
	
	try {
		$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
		$rdata = $billingdb->billing_balance_history($offset,$count,$resaler,$agent,$endpoint,$type,$from,$to);
		//var_dump($rdata);
		if (is_array ( $rdata )) {
			$list_array = array ();
			//遍历数组
			for($i = 0; $i < count ( $rdata ); $i ++) {
				$pno_array = explode('-',$rdata [$i] ['h323id']);
				$guid_sn_array = explode('-',$rdata [$i] ['Guid_SN']);
				if (count($guid_sn_array) > 2) {
					$guid_sn = $rdata [$i] ['Guid_SN'];
				}else{
					$guid_sn = $guid_sn_array[1];
				}

				//获取充值的类型
				//var_dump($api_obj->error_obj->error_array);
				$rc_code_msg = $billingdb->get_message($rdata [$i] ['RC_Code'],'');
				if (strpos($rc_code_msg,'error') > 0) {
					$rc_code = $rdata [$i] ['RC_Code'];
				}else{
					$rc_code = $rc_code_msg;
				}
				
				$r_data = array (
					'id' => trim($rdata [$i] ['id']), 
					'H_Datetime' => $rdata [$i] ['H_Datetime'], 
					'E164' => $rdata [$i] ['E164'], 
					'Cost' => $rdata [$i] ['Cost'], 
					'RealCost' => $rdata [$i] ['RealCost'], 
					'RC_Code' => $rc_code, 
					'Remark' => $rdata [$i] ['Remark'], 
					'Pno' => $pno_array[1], 
					'Guid_SN' => $guid_sn, 
					'CS_Name' => $rdata [$i] ['CS_Name'], 
					'SourcePin' => $rdata [$i] ['SourcePin'], 
					'Agent_Name' => $rdata [$i] ['Agent_Name'],
					'CurrencyType' => $rdata [$i] ['CurrencyType'],
					'Old_Balance' => $rdata [$i] ['Old_Balance'],
					'New_Balance' => $rdata [$i] ['New_Balance']
				);
				array_push ( $list_array, $r_data );
			}
			$api_obj->return_data ['totalCount'] = $billingdb->total_count;
			$api_obj->return_data ['data'] = $list_array;
		} else {
			//echo '$rdata is not a array';
			$api_obj->return_code =  '-101';
		}
		$api_obj->write_response ();
	} catch ( Exception $e ) {
		$rows = $e->getMessage ();
	}
}
?>

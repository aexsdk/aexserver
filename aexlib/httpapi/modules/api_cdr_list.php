<?php

api_action($api_object);

//查询CDR记录
function api_action($api_obj){
	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
	
	//获得请求发生的权限级别：运营商=0，代理商〉0，用户为-1
	$resaler = -1;	//目前使用运营商级别，以后从Session中获得
	$offset = empty($_REQUEST['offset'])? (empty($_REQUEST['start']) ? 0 : $_REQUEST['start']) : $_REQUEST['offset'];
	$count = empty($_REQUEST['count'])? (empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit']) : $_REQUEST['count'];
	$is_rt = empty($_REQUEST['type'])?0:1;
	$rdata = $billingdb->billing_cdr_list($offset,$count,$resaler,$is_rt,
		$_REQUEST['from'],$_REQUEST['to'],$_REQUEST['caller'],$_REQUEST['callee'],$_REQUEST['endpoint']);
	//var_dump($rdata);
	if(is_array($rdata)){
		$cdr_array =array();
		for ($i = 0;$i < count($rdata); $i++) {
			$guid_sn_array = explode('-',$rdata [$i] ['Guid_SN']);
			if (count($guid_sn_array) > 2) {
				$guid_sn = $rdata [$i] ['Guid_SN'];
			}else{
				$guid_sn = $guid_sn_array[1];
			}
			
			$r_data = array(
				'CDRDatetime' => $rdata[$i]['CDRDatetime'],
				'SessionID' => $rdata[$i]['SessionID'],
				'Guid_SN' => $guid_sn,
				'AcctStartTime' => $rdata[$i]['AcctStartTime'],
				'PN_E164' => $rdata[$i]['PN_E164'],
				'CallerID' => $rdata[$i]['CallerID'],
				'CalledID' => $rdata[$i]['CalledID'],
				'SessionTimeMin' => $rdata[$i]['SessionTimeMin'],
				'AcctSessionFee' => $rdata[$i]['AcctSessionFee'] . $rdata[$i]['CurrencyType']
			);
			array_push($cdr_array,$r_data);
		}
			
		$api_obj->return_data['totalCount'] = $billingdb->total_count;
		$api_obj->return_data['data'] = $cdr_array;
	}else{
		//echo '$rdata is not a array';
		$api_obj->set_return_code(-101);
	}
	$api_obj->write_response();
}
?>

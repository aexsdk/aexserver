<?php
api_action($api_object);

//查询CDR记录
function api_action($api_obj){
	$billing_msdb = new class_billing_db($api_obj->config->billing_db_config, $api_obj);
	
	//获取api lib的文件路径
	//获得请求发生的权限级别：运营商=0，代理商〉0，用户为-1
	$resaler = -1;	//目前使用运营商级别，以后从Session中获得
	$offset = empty($_REQUEST['offset'])? (empty($_REQUEST['start']) ? 0 : $_REQUEST['start']) : $_REQUEST['offset'];
	$count = empty($_REQUEST['count'])? (empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit']) : $_REQUEST['count'];
	
	$rdata = $billing_msdb->billing_endpoint_get_list($offset,$count,-1,$_REQUEST['type'],$_REQUEST['status'],
		$_REQUEST['endpoint']);
	if(is_array($rdata)){
		//var_dump($rdata);
		if ($rdata[0]['HireDuration'] < 0 || $rdata[0]['HirePeriod'] <= 1) {
			$rdata[0]['HireDuration'] = 0;
		}
		$api_obj->return_data['totalCount'] = $billing_msdb->total_count;
		$api_obj->return_data['data'] = $rdata;
	}else{
		//echo '$rdata is not a array';
		$api_obj->return_code = '-101';
	}

	$api_obj->write_response();
	$api_obj->return_data['data'] = 'array';
}
?>

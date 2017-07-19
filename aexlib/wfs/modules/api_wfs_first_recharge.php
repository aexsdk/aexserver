<?php	/* * 执行操: 自动入库 * */api_action($api_object);function get_message_callback($api_obj,$context,$msg){	return sprintf($msg,$api_obj->return_code);}/*	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。*/function write_response_callback($api_obj,$context){	$success = $api_obj->return_code > 0;	$api_obj->push_return_data('success',$success);	$api_obj->push_return_data('message', $api_obj->get_error_message($api_obj->return_code),'');		$resp = $api_obj->write_return_params_with_json();			return $resp;}function api_action($api_obj){	require_once $api_obj->params['common-path'].'/api_wfs_db.php';	$wfs_db = new class_wfs_db($api_obj->config->new_wfs_db_config, $api_obj);	$api_obj->set_callback_func ( get_message_callback, write_response_callback, $wfs_db );	$bsn = $_REQUEST['bsn'];    	$imei = $_REQUEST['imei']; 	$carrier_id = $_REQUEST['carrier_id']; 	$agent_id	= $_REQUEST['agent_id']; 	$re_code	= $_REQUEST['re_code']; 	$pno = $_REQUEST['pno']; 	$ep = $_REQUEST['ep']; 	$cost = $_REQUEST['cost']; 	$real_cost = $_REQUEST['real_cost']; 	$ct = $_REQUEST['ct'];	$real_ct = empty($_REQUEST['real_ct']) ? $_REQUEST['ct']: $_REQUEST['real_ct']; 		$params_array = array(		$bsn,		$imei,		$carrier_id,		$agent_id,		$pno,		$ep,		$cost.$ct,		$real_cost.$real_ct,		$re_code	);	try {		$rdata = $wfs_db->record_first_recharge($params_array);		$api_obj->return_code = $rdata['p_return'];	} catch ( Exception $e ) {		$rows = $e->getMessage ();	}	$api_obj->write_response();}?>
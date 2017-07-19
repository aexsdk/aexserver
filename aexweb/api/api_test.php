<?php
error_reporting(E_ALL ^ E_NOTICE);//ignore the "notice" error
require_once (dirname(__FILE__).'/mlm/config/api_config.php');
require_once (dirname(__FILE__).'/config/api_radius_config.php');
require_once (dirname(__FILE__).'/common/api_common.php');				
require_once (dirname(__FILE__).'/common/api_radius_funcs.php');				
require_once (dirname(__FILE__).'/common/api_log_class.php');				

try{
	//获取基本参数
	$p_params = array(
		'run_start_time'=>microtime(),
		'gprs_caller'=>$_SERVER['HTTP_X_UP_CALLING_LINE_ID'],
		'gprs_agent'=>$_SERVER['HTTP_USER_AGENT'],
		'api_version' => $_REQUEST['v'],
		'api_o' =>$_REQUEST['o'],
		'api_lang' => $_REQUEST['lang'],
		'api_p' => $_REQUEST['p'],
		'api_action'=>'',
		'common-lang-path'=>dirname(__FILE__),
		'lang-path' => dirname(__FILE__).'/mlm',
		'common-path'=>dirname(__FILE__).'/common'
	
	);
	
	$config = new class_config();
	$api_object = new class_api($config,$p_params);
	
	$array = array(
		'server' => array(
						'caption' => '123'
					),
		'server' => array(
						'caption' => '123'
					)				
	);
		var_dump($array);
	
		$resp =  "\r\n<UTONE S=\"%d\">\r\n";
		if(is_array($array)){
			foreach ($array as $value){
				$resp = $resp . $value."\r\n";
			}
		}
		$resp = $resp. "</UTONE>\r\n";
		
	echo $resp;
	
	//调用action操作函数，实现具体的操作
	//do_action($p_params,$api_object);
} catch ( Exception $e ) {
	echo sprintf("\r\n<UTONE><R>0<R><M>服务器异常：%s</M></UTONE>",$e->getMessage ());
}

/*
	定义Action具体行为
*/
/*
	定义错误字符串处理的回调函数，正常情况下错误字符串已经可以按照语言和action取得。
	但是有时候我们需要在字符串中格式一些其他的参数，如：电话号码，姓名什么的。
	例如：
		解除绑定失败的错误字符串：号码%1s与本手机解除绑定失败，代码[%0d]，该手机已经和%2s绑定。
		假设本手机号码在变量$api_obj->params['api_params']['pno']中，已经绑定的号码在
	$api_obj->return_data['p_bind_no']中，那么我们就需要
		function get_message_callback($api_obj,$context,$msg){
			return sprintf($msg,$api_obj->return_code,$api_obj->params['api_params']['pno'],$api_obj->return_data['p_bind_no']);
		}
*/
function get_message_callback($api_obj,$context,$msg){
	return sprintf($msg,$api_obj->return_code);
}

/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj,$context){
	$success = $api_obj->return_code > 0;
	$api_obj->push_return_data('success',$success);
	$api_obj->push_return_data('message', $api_obj->get_error_message($api_obj->return_code),'');

	$resp = $api_obj->write_return_params_with_json();		
	return $resp;
}

/*
	执行API操作，操作是在modules目录下的api_开头加上action名称的PHP文件。引用这个文件后我们在这个文件里具体实现action操作
*/
function do_action($p_params,$api_obj){
	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$billingdb);
	/*
	$rdata = $billingdb->billing_endpoint_get_list($_REQUEST['offset'],$_REQUEST['count'],$_REQUEST['resaler'],$_REQUEST['type'],$_REQUEST['state'],$_REQUEST['endpoint']);
	if(is_array($rdata)){
		//var_dump($rdata);
		$billingdb->api_object->return_data['totalCount'] = $billingdb->total_count;
		$billingdb->api_object->return_data['data'] = $rdata;
	}else{
		echo '$rdata is not a array';
	}
	*/
	//var_dump($billingdb->rows);
	/*$rdata = $billingdb->billing_get_charge_plan_list($_REQUEST['offset'],$_REQUEST['count'],$_REQUEST['resaler']);
	if(is_array($rdata)){
		$billingdb->api_object->return_data['success'] = 'true';
		$billingdb->api_object->return_data['totalcount'] = count($rdata);
		$billingdb->api_object->return_data['data'] = $rdata;
	}
	*/
	//billing_runtime_cdr_list($offset='',$count='',$from='',$to='',$caller='',$callee='',$calleeip='',$endpoint='')
	/*$rdata = $billingdb->billing_cdr_list($_REQUEST['offset'],$_REQUEST['count'],$_REQUEST['resaler'],$_REQUEST['is_rt'],$_REQUEST['from'],$_REQUEST['to'],$_REQUEST['caller'],$_REQUEST['callee'],$_REQUEST['endpoint']);
	if(is_array($rdata)){
		$billingdb->api_object->return_data['success'] = 'true';
		$billingdb->api_object->return_data['totalcount'] = count($rdata);
		$billingdb->api_object->return_data['data'] = $rdata;
	}*/
	
	/*$rdata = $billingdb->get_endpoint_info('13602648557','690919');
	//var_dump($rdata);
	if(is_array($rdata)){
		$billingdb->api_object->return_data['success'] = 'true';
		$billingdb->api_object->return_data['totalcount'] = count($rdata);
		$billingdb->api_object->return_data['data'] = $rdata;
	}else{
		$billingdb->set_return_code(-101);
	}*/
	$rdata = $billingdb->billing_agent_balance_history($_REQUEST['offset'],$_REQUEST['count'],0,$_REQUEST['from'],$_REQUEST['to'],$_REQUEST['from_v'],$_REQUEST['to_v']);
	if(is_array($rdata)){
		$billingdb->api_object->return_data['success'] = 'true';
		$billingdb->api_object->return_data['totalcount'] = count($rdata);
		$billingdb->api_object->return_data['data'] = $rdata;
	}
	
	
	$api_obj->write_response();
	
}

?>

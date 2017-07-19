<?php 
	header("content-Type: text/html; charset=UTF-8");  
	/*
	 * 查询代理商列表
	 * */
	//限制直接访问该页面
	if(!class_exists('os')){
		echo 'error';
		exit();
	}
	
	do_action($api_obj);
	
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
	//$api_obj->write_trace(0,'Run here');
	//$success = $api_obj->return_code > 0;
	//$api_obj->push_return_data('success',$success);
	//$api_obj->push_return_data('message', $api_obj->get_error_message($api_obj->return_code),'');
	$resp = $api_obj->json_encode($api_obj->return_data);		
	return $resp;
}
	 
/*
	执行API操作，操作是在modules目录下的api_开头加上action名称的PHP文件。引用这个文件后我们在这个文件里具体实现action操作
*/
function do_action($api_obj){
	require_once $api_obj->params['common-path'].'/api_billing_pgdb.php';
	//存储过程为合成存储过程
	$billingdb = new class_billing_pgdb($api_obj->config->log_db_config, $api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback, write_response_callback,$billingdb);
	
	//获取api lib的文件路径
	//获得请求发生的权限级别：运营商=0，代理商〉0，用户为-1
	$resaler = empty($_POST['node'])? 0: $_POST['node'];	//目前使用运营商级别，以后从Session中获得
	$offset = empty($_REQUEST['start']) ? 0 : $_REQUEST['start'];
	$count = empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit'];
	
	if ($resaler == 'root') {
		$resaler  = '0';
	}
	
	$rdata = $billingdb->billing_get_agent_tree($resaler);
	if(is_array($rdata)){
		//遍历数组
		for ($i = 0; $i < count($rdata); $i++) {
			$path['text'] = $rdata[$i]['caption'];
//			/ html_entity_decode();
			$path['id']		= $rdata[$i]['agentid'];
			//$path['position']	= $row['upc_order'];
			// Check if node is a leaf or a folder.
			
			$child_array = $billingdb->billing_get_agent_tree($path['id']);
			
			if(count($child_array)> 0){
				$path['leaf']	= false;
				$path['cls']	= 'folder';
			}else{
				if (condition) {
					;
				}
				$path['leaf']	= true;
				$path['cls']	= 'file';
			}
	 
			// call this function again to display this
			// child's children
			$nodes[] = $path;
 		}
		$billingdb->api_object->return_data = $nodes;
	}else{
		//echo '$rdata is not a array';
		$billingdb->set_return_code(-101);
	}
	$api_obj->write_response();
	
}

?>
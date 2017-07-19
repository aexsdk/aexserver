<?php

date_default_timezone_set('Asia/Chongqing');
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



class ez_log {
	private $os;
	private $rows;
	/**
	 * __construct()
	 *
	 * @access public
	 * @param {class} $os The os.
	 */
	public function __construct(os $os) {
		if (! $os->session_exists ()) {
			die ( 'Session does not exist!' );
		}
		$this->os = $os;
		//加载多国语言
		$os->log_object->load_error_xml(sprintf("%s.xml",'log'));
	} // end __construct()
	
	/******** 落地网关 strat ********/
	//获取落地网关信息表	
	public function	get_action_log(){
		$is_rt = empty($_REQUEST['type'])?0:1;
		$filter = empty($_REQUEST['filter']) ? '%' : $_REQUEST['filter'];
		$offset = isset($_REQUEST['start']) ? $_REQUEST['start']:0;
		$limit = isset($_REQUEST['limit']) ? $_REQUEST['limit']:20;
		
		$from = $_REQUEST['from'];
		$to = $_REQUEST['to'];
		if(empty($from))
			$from = strftime('%Y-%m-%d %H:%M:%S',mktime(0, 0, 0, date("m")  , date("d")-1, date("Y")));		//默认从当天的0点开始
		if(empty($to))
			$to = strftime('%Y-%m-%d %H:%M:%S',mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));		//默认到今天的12点结束，也就是说from to都是默认值时显示当天的数据
		if(strpos($filter,'%') === false){
			$filter = sprintf('%%%s%%',$filter);	
		}
		
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$api_obj = $this->os->log_object;
		$billing_db = new class_billing_pgdb($api_obj->config->route_db_config, $api_obj);
		$api_obj->set_callback_func(get_message_callback, write_response_callback,$this);
		$totalCount = $billing_db->get_system_log_count($filter,$from,$to);
		if($totalCount > 0){
			$rdata = $billing_db->log_get_system_list($filter,$from,$to,$offset,$limit);
			if(is_array($rdata)){
				$list_array = array();
				//遍历数组
				for ($i = 0;$i < count($rdata); $i++) {
					$r_data = array(
						'ID' =>	$rdata[$i]['api_log_time'],
						'LogTime' => substr(date("Y-m-d H:i:s",strtotime($rdata[$i]['api_log_time'])),0,-3),
						'ModSrcIP'  => $rdata[$i]['api_mod_src'],
						'ModDest'  => $rdata[$i]['api_mod_dest'],
						'ApiSrcIP'  => $rdata[$i]['api_src_ip'],
						'Action'  => $rdata[$i]['api_action'],
						'ReturnValue'  => $rdata[$i]['api_return_value'],
						'RunTime'  => $rdata[$i]['api_run_time'],
						'Param'  => $rdata[$i]['api_param'],
						'Requests'  => $rdata[$i]['api_requests'],
						'Response'  => $rdata[$i]['api_response']
					);
					array_push($list_array,$r_data);
		 		}
				$api_obj->return_data['totalCount'] = $totalCount;
				$api_obj->return_data['data'] = $list_array;
			}else{
				//echo '$rdata is not a array';
				$billing_db->set_return_code(-101);
				$api_obj->return_data['totalCount'] =  0;
				$api_obj->return_data['data'] = array();
			}
		}else{
				$billing_db->set_return_code(100);
				$api_obj->return_data['totalCount'] =  0;
				$api_obj->return_data['data'] = array();
		}
		$api_obj->write_response();
	}
}
?>

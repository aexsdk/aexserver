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
	if (strpos($api_obj->return_code,':') >0 ) {
		$return_code = explode(':',$api_obj->return_code);
		$success = $return_code[1] > 0;
	}else{
		$success = $api_obj->return_code > 0;
	}
	$api_obj->push_return_data('success',$success);
	$api_obj->push_return_data('message', $api_obj->get_error_message($api_obj->return_code),'');
	
	$resp = $api_obj->write_return_params_with_json();		
	return $resp;
}



class ez_voip_route {
	private $os;
	private $rows;
	private $api_obj;
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
		$this->api_obj = $os->log_object;
		//加载多国语言
		$os->log_object->load_error_xml(sprintf("%s.xml",'route'));
	} // end __construct()
	
	/******** 落地网关 strat ********/
	//获取落地网关信息表	
	public function get_gateway_list(){
		$offset = empty($_POST['start']) ? 0 : $_POST['start'];
		$limit = empty($_POST['limit']) ? 15 : $_POST['limit'];

		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config, $this->api_obj);
		
		$this->api_obj->set_callback_func(get_message_callback, write_response_callback,$wfs_db);
		$rdata = $wfs_db->route_get_gateway_list($offset,$limit);
		
		if(is_array($rdata)){
			$list_array = array();
			//遍历数组
			for ($i = 0;$i < count($rdata); $i++) {
				$r_data = array(
					'id' => $rdata[$i]['routing_id'],
					'routing_ip' => $rdata[$i]['routing_ip'],
					'routing_type' => $rdata[$i]['routing_type'],
					'routing_strip' => $rdata[$i]['routing_strip'],
					'routing_prefix' => $rdata[$i]['routing_prefix'],
					'routing_name' => $rdata[$i]['routing_name'],
					'routing_remark' => $rdata[$i]['routing_remark'],
					'validity' => $rdata[$i]['validity'],
					'priority' => $rdata[$i]['priority'],
					'cli' => $rdata[$i]['cli'],
					'resaler' => $rdata[$i]['resaler'],
					'retries' => $rdata[$i]['retries'],
					'delay' => $rdata[$i]['delay']
				);
				array_push($list_array,$r_data);
	 		}
			$this->api_obj->return_data['totalCount'] = $wfs_db->total_count;
			$this->api_obj->return_data['data'] = $list_array;
		}else{
			//echo '$rdata is not a array';
			$this->api_obj->return_data['totalCount'] = 0;
			$this->api_obj->return_data['data'] = array();
			$this->api_obj->set_return_code(-101);
		}
		$this->api_obj->write_response();
	}
	
	//修改落地网关信息表
	public function edit_gateway_list(){
		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config, $this->api_obj);
		$this->api_obj->set_callback_func(get_message_callback,write_response_callback,$wfs_db);
		
		$id = empty($_REQUEST['id']) ? 0 : $_REQUEST['id'];
		$routing_ip = empty($_REQUEST['routing_ip']) ? 0 : $_REQUEST['routing_ip'];
		$routing_name = empty($_REQUEST['routing_name']) ? 0 : $_REQUEST['routing_name'];
		$routing_prefix = empty($_REQUEST['routing_prefix']) ? 0 : $_REQUEST['routing_prefix'];
		$routing_remark = empty($_REQUEST['routing_remark']) ? 0 : $_REQUEST['routing_remark'];
		$routing_strip = empty($_REQUEST['routing_strip']) ? 0 : $_REQUEST['routing_strip'];
		$routing_type = empty($_REQUEST['routing_type']) ? 0 : $_REQUEST['routing_type'];
		$validity = empty($_REQUEST['validity']) ? 0 : $_REQUEST['validity'];
		$priority = empty($_REQUEST['priority']) ? 0 : $_REQUEST['priority'];
		$cli = empty($_REQUEST['cli'])? '' : $_REQUEST['cli'];
		$domain = $this->api_obj->config->carrier_name;
		$resaler = isset($_REQUEST['resaler'])?$_REQUEST['resaler']:
				(isset($this->api_obj->config->resaler)?$this->api_obj->config->resaler:0);
		$retries = empty($_REQUEST['retries'])? 3 : $_REQUEST['retries'];
		$delay = empty($_REQUEST['delay'])? 1 : $_REQUEST['delay'];
		
		$param = array(
			'id' => $id,
			'routing_ip' => $routing_ip,
			'routing_name' => $routing_name,
			'routing_prefix' => $routing_prefix,
			'routing_remark' => $routing_remark,
			'routing_strip' => $routing_strip,
			'routing_type' => $routing_type,
			'validity' => $validity,
			'priority' => $priority,
			'cli' => $cli,
			'domain' => $domain,
			'resaler' => $resaler,
			'retries' => $retries,
			'delay' => $delay
		);
		
		$rdata = $wfs_db->route_edit_gateway_list($param);
		if(is_array($rdata)){
			$this->api_obj->return_code = 'edit_gateway_list:'. $rdata['p_return'];
		}else{
			//echo '$rdata is not a array';
			$this->api_obj->return_code = '-105';
		}
		$this->api_obj->write_response();
	}
	
	//删除落地网关信息表
	public function  delete_gateway_list(){
		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config, $this->api_obj);
		$this->api_obj->set_callback_func(get_message_callback,write_response_callback,$wfs_db);
		
		$id = empty($_POST['jsonStr']) ? 0 : $_POST['jsonStr'];
	
		$rdata = $wfs_db->route_delete_gateway_list($id);
		if(is_array($rdata)){
			$this->api_obj->return_code = 'delete_gateway_list:'. $rdata['p_return'];
		}else{
			//echo '$rdata is not a array';
			$this->api_obj->return_code = '-105';
		}
		$this->api_obj->write_response();
	}
	
	//添加落地网关信息表
	public function add_gateway_list(){
		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config, $this->api_obj);
		$this->api_obj->set_callback_func(get_message_callback,write_response_callback,$wfs_db);
		
		$routing_ip = empty($_REQUEST['routing_ip']) ? 0 : $_REQUEST['routing_ip'];
		$routing_name = empty($_REQUEST['routing_name']) ? 0 : $_REQUEST['routing_name'];
		$routing_prefix = empty($_REQUEST['routing_prefix']) ? 0 : $_REQUEST['routing_prefix'];
		$routing_remark = empty($_REQUEST['routing_remark']) ? 0 : $_REQUEST['routing_remark'];
		$routing_strip = empty($_REQUEST['routing_strip']) ? 0 : $_REQUEST['routing_strip'];
		$routing_type = empty($_REQUEST['routing_type']) ? 0 : $_REQUEST['routing_type'];
		$validity = empty($_REQUEST['validity']) ? 0 : $_REQUEST['validity'];
		$priority = empty($_REQUEST['priority']) ? 0 : $_REQUEST['priority'];
		$cli = empty($_REQUEST['cli'])? '' : $_REQUEST['cli'];
		$domain = $this->api_obj->config->carrier_name;
		$resaler = isset($_REQUEST['resaler'])?$_REQUEST['resaler']:
				(isset($this->api_obj->config->resaler)?$this->api_obj->config->resaler:0);
		$retries = isset($_REQUEST['retries'])? $_REQUEST['retries']:3;
		$delay = isset($_REQUEST['delay'])? $_REQUEST['delay']:1;
				
		$params = array(
			'routing_ip' => $routing_ip,
			'routing_name' => $routing_name,
			'routing_prefix' => $routing_prefix,
			'routing_remark' => $routing_remark,
			'routing_strip' => $routing_strip,
			'routing_type' => $routing_type,
			'priority' => $priority,
			'validity' => $validity,
			'cli' => $cli,
			'domain' => $domain,
			'resaler' => $resaler,
			'retries' => $retries,
			'delay' => $delay
		);
		
		$rdata = $wfs_db->route_add_gateway_list($params);
		if(is_array($rdata)){
			$this->api_obj->return_code = 'add_gateway_list:'. $rdata['p_return'];
		}else{
			//echo '$rdata is not a array';
			$this->api_obj->return_code = '-105';
		}
		$this->api_obj->write_response();
	}
	/********** 落地网关  end *********/
	
	/******** 路由前缀选择 strat ********/
	//添加路由前缀选择
	public function add_prefix_list(){
		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config, $this->api_obj);
		$this->api_obj->set_callback_func(get_message_callback,write_response_callback,$wfs_db);
		
		$routing_prefix = empty($_POST['routing_prefix']) ? 0 : $_POST['routing_prefix'];
		$routing_id = empty($_POST['routing_id']) ? 0 : $_POST['routing_id'];
		$priority = empty($_POST['priority']) ? 0 : $_POST['priority'];
		$domain = $this->api_obj->config->carrier_name;
		$resaler = isset($_REQUEST['resaler'])?$_REQUEST['resaler']:
				(isset($this->api_obj->config->resaler)?$this->api_obj->config->resaler:0);
		$start_time = empty($_REQUEST['start_time'])? '0' : $_REQUEST['start_time'];
		$end_time = empty($_REQUEST['end_time'])? '24' : $_REQUEST['end_time'];
		
		$rdata = $wfs_db->route_add_prefix_list(array(
			'rid' => $routing_id,
			'prefix' => $routing_prefix,
			'priority' => $priority,
			'domain' => $domain,
			'resaler' => $resaler,
			'start_time' => $start_time,
			'end_time' => $end_time
			));
		if(is_array($rdata)){
			$this->api_obj->return_code = 'add_prefix_list:'.$rdata['p_return'];
		}else{
			//echo '$rdata is not a array';
			$this->api_obj->return_code = '-105';
		}
		$this->api_obj->write_response();
	}
	
	//删除路由前缀选择
	public function delete_prefix_list(){
		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config, $this->api_obj);
		$this->api_obj->set_callback_func(get_message_callback,write_response_callback,$wfs_db);
		
		$id = empty($_POST['jsonStr']) ? 0 : $_POST['jsonStr'];
	
		$rdata = $wfs_db->route_delete_prefix_list($id);
		if(is_array($rdata)){
			$this->api_obj->return_code = 'delete_prefix_list:'. $rdata['p_return'];
		}else{
			//echo '$rdata is not a array';
			$this->api_obj->return_code = '-105';
		}
		$this->api_obj->write_response();
	}
	//修改路由前缀选择
	public function edit_prefix_list(){
		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config, $this->api_obj);
		$this->api_obj->set_callback_func(get_message_callback,write_response_callback,$wfs_db);
		
		$priority = empty($_POST['priority']) ? 0 : $_POST['priority'];
		$routing_id= empty($_POST['routing_id']) ? 0 : $_POST['routing_id'];
		$routing_prefix = empty($_POST['routing_prefix']) ? '' : $_POST['routing_prefix'];
		$id = empty($_POST['id']) ? '' : $_POST['id'];
		$domain = $this->api_obj->config->carrier_name;
		$resaler = isset($_REQUEST['resaler'])?$_REQUEST['resaler']:
				(isset($this->api_obj->config->resaler)?$this->api_obj->config->resaler:0);
		$start_time = empty($_REQUEST['start_time'])? '0' : $_REQUEST['start_time'];
		$end_time = empty($_REQUEST['end_time'])? '24' : $_REQUEST['end_time'];
		
//		$params = array(
//			'id' => "$id",
//			'priority' => "$priority",
//			'routing_id' => "$routing_id",
//			'routing_prefix' => "$routing_prefix"
//		);
		
		$rdata = $wfs_db->route_edit_routing_prefix(array(
			'id' => $id,
			'rid' => $routing_id,
			'prefix' => $routing_prefix,
			'priority' => $priority,
			'domain' => $domain,
			'resaler' => $resaler,
			'start_time' => $start_time,
			'end_time' => $end_time
			));
		if(is_array($rdata)){
			$this->api_obj->return_code = 'edit_prefix_list:'.$rdata['p_return'];
		}else{
			//echo '$rdata is not a array';
			$this->api_obj->return_code = '-105';
		}
		$this->api_obj->write_response();
	}
	
	//查询路由前缀选择
	public function get_prefix_list(){
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
		
		$offset = empty($_POST['start']) ? 0 : $_POST['start'];
		$limit = empty($_POST['limit']) ? 0 : $_POST['limit'];
		$s_prefix = empty($_POST['s_prefix']) ? 'null' : $_POST['s_prefix'];
		$s_rid = empty($_POST['s_rid']) ? 0 : $_POST['s_rid'];
	
		
		$rdata = $this->os->billing_db->route_get_prefix_list($offset,$limit,$s_prefix,$s_rid);
	
		if(is_array($rdata)){
			$list_array = array();
			//遍历数组
			for ($i = 0;$i < count($rdata); $i++) {
				$r_data = array(
					'id' => $rdata[$i]['prefix_list_id'],
					'routing_id' => $rdata[$i]['routing_id'],
					'routing_name' => $rdata[$i]['routing_name'],
					'routing_ip' => $rdata[$i]['routing_ip'],
					'routing_prefix' => $rdata[$i]['routing_prefix'],
					'priority' => $rdata[$i]['priority']
				);
				array_push($list_array,$r_data);
	 		}
			$this->os->log_object->return_data['totalCount'] = $this->os->billing_db->total_count;
			$this->os->log_object->return_data['data'] = $list_array;
		}else{
			//echo '$rdata is not a array';
			$this->os->log_object->return_code = '-101';
		}
		$this->os->log_object->write_response();
	}
	
	public function get_routing_id() {
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
	
		$rdata = $this->os->billing_db->route_get_routing_id();
	
		if(is_array($rdata)){
			$list_array = array();
			//遍历数组
			for ($i = 0;$i < count($rdata); $i++) {
				$r_data = array(
					'id' => $rdata[$i]['id'],
					'routing_name' => $rdata[$i]['routing_name']
				);
				array_push($list_array,$r_data);
	 		}
			$this->os->log_object->return_data['totalCount'] = $$this->os->billing_db->total_count;
			$this->os->log_object->return_data['data'] = $list_array;
		}else{
			//echo '$rdata is not a array';
			$this->os->log_object->return_code = '-101';
		}
		$this->os->log_object->write_response();
	}
	
		
	/******** 路由前缀选择 end  ********/
	
	/******** 全局前缀替换 start  ********/
	//删除全局前缀替换
	public function delete_rewrite_list() {
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
		
		$id = empty($_POST['jsonStr']) ? 0 : $_POST['jsonStr'];
	
		$rdata = $this->os->billing_db->route_delete_rewirte_list($id);
		if(is_array($rdata)){
			$this->os->log_object->return_code = $rdata['p_return'];
		}else{
			//echo '$rdata is not a array';
			$this->os->log_object->return_code = '-105';
		}
		$this->os->log_object->write_response();
	}
	
	//修改全局前缀替换
	public function edit_rewrite_list() {
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
		
		$new_prefix = empty($_POST['new_prefix']) ? 0 : $_POST['new_prefix'];
		$prefix = empty($_POST['prefix']) ? 0 : $_POST['prefix'];
		$validity = empty($_POST['validity']) ? 0 : $_POST['validity'];
		$id = empty($_POST['id']) ? 0 : $_POST['id'];
		
		$rdata = $this->os->billing_db->route_edit_rewirte_list($id, $prefix, $new_prefix, $validity);
		if(is_array($rdata)){
			$this->os->log_object->return_code = $rdata['p_return'];
		}else{
			//echo '$rdata is not a array';
			$this->os->log_object->return_code = '-105';
		}
		$this->os->log_object->write_response();
	}
	
	//增加全局前缀替换
	public function add_rewrite_list() {
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
		
		$new_prefix = empty($_POST['new_prefix']) ? 0 : $_POST['new_prefix'];
		$prefix = empty($_POST['prefix']) ? 0 : $_POST['prefix'];
		$validity = empty($_POST['validity']) ? 0 : $_POST['validity'];
		
		$rdata = $this->os->billing_db->route_add_rewirte_list($new_prefix,$prefix,$validity);
		if(is_array($rdata)){
			$this->os->log_object->return_code = $rdata['p_return'];
		}else{
			//echo '$rdata is not a array';
			$this->os->log_object->return_code = '-105';
		}
		$this->os->log_object->write_response();
	}
	
	//获取全局前缀替换
	public function get_rewrite_list() {
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
		
		$offset = empty($_REQUEST['start']) ? 0 : $_REQUEST['start'];
		$count = empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit'];
		$rdata = $this->os->billing_db->route_get_rewirte_list($offset, $count);
	
		if(is_array($rdata)){
			$list_array = array();
			//遍历数组
			for ($i = 0;$i < count($rdata); $i++) {
				$r_data = array(
					'id' => $rdata[$i]['rewrite_list_id'],
					'prefix' => $rdata[$i]['prefix'],
					'new_prefix' => $rdata[$i]['new_prefix'],
					'validity' => $rdata[$i]['validity']
				);
				array_push($list_array,$r_data);
	 		}
			$this->os->log_object->return_data['totalCount'] =  $this->os->billing_db->total_count;
			$this->os->log_object->return_data['data'] = $list_array;
		}else{
			//echo '$rdata is not a array';
			$this->os->log_object->return_code = '-101';
		}
		$this->os->log_object->write_response();
	}	
	
	/******** 全局路由选择 end  ********/
	
//	/******** 回拨服务器统计信息 start  ********/
//	public function get_dialserver_total() {
//		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
//		//如需要获得返回的余额可以用，$context['p_balance']
//		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
//		
//		$offset = empty($_REQUEST['start']) ? 0 : $_REQUEST['start'];
//		$limit = empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit'];
//		$rdata = $this->os->billing_db->route_get_dialserver_total($offset,$limit);
//	
//		if(is_array($rdata)){
//			$list_array = array();
//			//遍历数组
//			for ($i = 0;$i < count($rdata); $i++) {
//				$r_data = array(
//					'id' => $rdata[$i]['server_id'],
//					'bandwidth' => $rdata[$i]['bandwidth'],
//					'call_tiems' => $rdata[$i]['call_tiems'],
//					'call_quality' => $rdata[$i]['call_quality']
//				);
//				array_push($list_array,$r_data);
//	 		}
//			$this->os->log_object->return_data['totalCount'] = $this->os->billing_db->total_count;
//			$this->os->log_object->return_data['data'] = $list_array;
//		}else{
//			//echo '$rdata is not a array';
//			$this->os->log_object->return_code = '-101';
//		}
//		$this->os->log_object->write_response();
//	}	
	/******** 回拨服务器统计信息 end  ********/
	
	/******** 网关统计信息 start  ********/
//	public function get_gateway_total(){
//		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
//		//如需要获得返回的余额可以用，$context['p_balance']
//		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
//		
//		$offset = empty($_REQUEST['start']) ? 0 : $_REQUEST['start'];
//		$limit = empty($_REQUEST['limit']) ? 15 : $_REQUEST['limit'];
//		$rdata = $this->os->billing_db->route_get_gateway_total($limit, $offset);
//		if(is_array($rdata)){
//			$list_array = array();
//			//遍历数组
//			for ($i = 0;$i < count($rdata); $i++) {
//				$r_data = array(
//					'id' => $rdata[$i]['routing_name'],
//					'qos' => $rdata[$i]['qos'],
//					'choice_times' => $rdata[$i]['choice_times'],
//					'ip' => $rdata[$i]['routing_ip']
//				);
//				array_push($list_array,$r_data);
//	 		}
//			$this->os->log_object->return_data['totalCount'] = $this->os->billing_db->total_count;
//			$this->os->log_object->return_data['data'] = $list_array;
//		}else{
//			//echo '$rdata is not a array';
//			$this->os->log_object->return_code = '-101';
//		}
//		$this->os->log_object->write_response();
//	}
	/******** 网关统计信息 end  ********/
	
	/******** 回去服务器信息 strat  ********/
	//增加回去服务器信息
	public function add_server_list() {
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->api_obj->set_callback_func(get_message_callback,write_response_callback, $this->os->log_object);
		
		$alias= empty($_POST['alias']) ? 0 : $_POST['alias'];
		$password = empty($_POST['password']) ? 0 : $_POST['password'];
		$port = empty($_POST['port']) ? 0 : $_POST['port'];
		$priority = empty($_POST['priority']) ? 0 : $_POST['priority'];
		$remark = empty($_POST['remark']) ? 0 : $_POST['remark'];
		$server_ip = empty($_POST['server_ip']) ? 0 : $_POST['server_ip'];
		$user_name = empty($_POST['user_name']) ? 0 : $_POST['user_name'];
		$validity = empty($_POST['validity']) ? 0 : $_POST['validity'];
		$domain = isset($_REQUEST['domain'])?$_REQUEST['domain']:$this->os->config->carrier_name;
		$resaler = isset($_REQUEST['resaler'])?$_REQUEST['resaler']:
				(isset($this->os->config->resaler)?$this->os->config->resaler:0);
		
		$params = array(
			'alias' => $alias,
			'password' => $password,
			'port' => $port,
			'priority' => $priority,
			'remark' => $remark,
			'server_ip' => $server_ip,
			'user_name' => $user_name,
			'validity' => $validity
		);
		$wfs_db = new class_billing_pgdb($this->os->log_object->config->wfs_db_config, $this->os->log_object);
		$rdata = $wfs_db->route_add_server_list($params,$domain,$resaler);
		if(is_array($rdata)){
			$this->os->log_object->return_code = 'add_server_list:'. $rdata['p_return'];
		}else{
			//echo '$rdata is not a array';
			$this->os->log_object->return_code = '-105';
		}
		$this->os->log_object->write_response();
	}
	
	//删除回去服务器信息
	public function delete_server_list() {
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->api_obj->set_callback_func(get_message_callback,write_response_callback,$this);
		
		$id = empty($_POST['jsonStr']) ? 0 : $_POST['jsonStr'];
		$domain = isset($_REQUEST['domain'])?$_REQUEST['domain']:$this->api_obj->config->carrier_name;
		$resaler = isset($_REQUEST['resaler'])?$_REQUEST['resaler']:
				(isset($this->api_obj->config->resaler)?$this->api_obj->config->resaler:0);
		
		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config, $this->api_obj);
		$rdata = $wfs_db->route_delete_server_list($id,$domain,$resaler);
		if(is_array($rdata)){
			$this->api_obj->return_code = 'delete_server_list:'. $rdata['p_return'];
		}else{
			//echo '$rdata is not a array';
			$this->api_obj->return_code = '-105';
		}
		$this->api_obj->write_response();
	}
	
	//修改服务器信息
	public function edit_server_list() {				
		$alias= empty($_POST['alias']) ? 0 : $_POST['alias'];
		$password = empty($_POST['password']) ? 0 : $_POST['password'];
		$port = empty($_POST['port']) ? 0 : $_POST['port'];
		$priority = empty($_POST['priority']) ? 0 : $_POST['priority'];
		$remark = empty($_POST['remark']) ? 0 : $_POST['remark'];
		$server_ip = empty($_POST['server_ip']) ? 0 : $_POST['server_ip'];
		$user_name = empty($_POST['user_name']) ? 0 : $_POST['user_name'];
		$validity = empty($_POST['validity']) ? 0 : $_POST['validity'];
		$id = empty($_POST['id']) ? 0 : $_POST['id'];
		$domain = isset($_REQUEST['domain'])?$_REQUEST['domain']:$this->api_obj->config->carrier_name;
		$resaler = isset($_REQUEST['resaler'])?$_REQUEST['resaler']:
				(isset($this->api_obj->config->resaler)?$this->api_obj->config->resaler:0);
		
		$params = array(
			'id' => $id,
			'alias' => $alias,
			'password' => $password,
			'port' => $port,
			'priority' => $priority,
			'remark' => $remark,
			'server_ip' => $server_ip,
			'user_name' => $user_name,
			'validity' => $validity
		);
		
		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config, $this->api_obj);
		$this->api_obj->set_callback_func(get_message_callback,write_response_callback,$wfs_db);

		$rdata = $wfs_db->route_edit_server_list($params,$domain,$resaler);
		if(is_array($rdata)){
			$this->api_obj->return_code = 'edit_server_list:'. $rdata['p_return'];
		}else{
			//echo '$rdata is not a array';
			$this->api_obj->return_code = '-105';
		}
		$this->api_obj->write_response();
	}
	
	//查询回去服务器信息
	public function get_server_list() {
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$wfs_db = new class_billing_pgdb($this->api_obj->config->wfs_db_config, $this->api_obj);
		$this->api_obj->set_callback_func(get_message_callback,write_response_callback,$wfs_db);
		
		$offset = empty($_REQUEST['start']) ? 0 : $_REQUEST['start'];
		$limit = empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit'];
		$domain = isset($_REQUEST['domain'])?$_REQUEST['domain']:$this->api_obj->config->carrier_name;
		$resaler = isset($_REQUEST['resaler'])?$_REQUEST['resaler']:
				(isset($this->api_obj->config->resaler)?$this->api_obj->config->resaler:0);
		$rdata = $wfs_db->route_get_server_list($offset,$limit,$domain,$resaler);
		
		if(is_array($rdata)){
			$list_array = array();
			//遍历数组
			for ($i = 0;$i < count($rdata); $i++) {
				$r_data = array(
					'id' => $rdata[$i]['server_id'],
					'alias' => $rdata[$i]['alias'],
					'port' => $rdata[$i]['port'],
					'user_name' => $rdata[$i]['user_name'],
					'password' => $rdata[$i]['password'],
					'validity' => $rdata[$i]['validity'],
					'server_ip' => $rdata[$i]['server_ip'],
					'priority' => $rdata[$i]['priority'],
					'remark' => $rdata[$i]['remark'],
					'resaler' => $rdata[$i]['resaler']
				);
				array_push($list_array,$r_data);
	 		}
			$this->api_obj->return_data['totalCount'] = $wfs_db->total_count;
			$this->api_obj->return_data['data'] = $list_array;
			$this->api_obj->return_code = 101;
		}else{
			//echo '$rdata is not a array';
			$this->api_obj->return_data['totalCount'] = 0;
			$this->api_obj->return_data['data'] = array();
			$this->api_obj->return_code = -101;
		}
		$this->api_obj->write_response();
	}
	/******** 回去服务器信息  end  ********/
	
	/******** 落地到服务器信息  start  ********/
	//增加落地到服务器信息  
//	public function add_server_speed() {
//		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
//		//如需要获得返回的余额可以用，$context['p_balance']
//		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
//		
//		$routing_ip = empty($_POST['routing_ip']) ? '' : $_POST['routing_ip'];
//		$routing_name = empty($_POST['routing_name']) ? '' : $_POST['routing_name'];
//		$server_id = empty($_POST['server_id']) ? 0 : $_POST['server_id'];
//		$speed = empty($_POST['speed']) ? 0 : $_POST['speed'];
//		$stability = empty($_POST['stability']) ? 0 : $_POST['stability'];
//		
//		$rdata = $this->os->billing_db->route_add_server_speed($routing_ip,$routing_name,$server_id,$speed,$stability);
//		if(is_array($rdata)){
//			$this->os->log_object->return_code = 'add_server_speed:'.$rdata['p_return'];
//		}else{
//			//echo '$rdata is not a array';
//			$this->os->log_object->return_code = '-105';
//		}
//		$this->os->log_object->write_response();
//	}
	//删除落地到服务器信息  
//	public function delete_server_speed() {
//		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
//		//如需要获得返回的余额可以用，$context['p_balance']
//		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
//		
//		$id = empty($_POST['jsonStr']) ? 0 : $_POST['jsonStr'];
//	
//		$rdata = $this->os->billing_db->route_delete_server_speed($id);
//		if(is_array($rdata)){
//			$this->os->log_object->return_code = 'delete_server_speed:'.$rdata['p_return'];
//		}else{
//			//echo '$rdata is not a array';
//			$this->os->log_object->return_code = '-105';
//		}
//		$this->os->log_object->write_response();
//	}
	//修改落地到服务器信息  
//	public function edit_server_speed() {
//		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
//		//如需要获得返回的余额可以用，$context['p_balance']
//		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
//		
//		$id = empty($_POST['id']) ? 0 : $_POST['id'];
//		$routing_ip = empty($_POST['routing_ip']) ? 0 : $_POST['routing_ip'];
//		$routing_name = empty($_POST['routing_name']) ? 0 : $_POST['routing_name'];
//		$server_id = empty($_POST['server_id']) ? 0 : $_POST['server_id'];
//		$speed = empty($_POST['speed']) ? 0 : $_POST['speed'];
//		$stability = empty($_POST['stability']) ? 0 : $_POST['stability'];
//		
//		$rdata = $this->os->billing_db->route_edit_server_speed($id, $routing_ip, $routing_name, $server_id, $speed, $stability);
//		if(is_array($rdata)){
//			$this->os->log_object->return_code = 'edit_server_speed:'. $rdata['p_return'];
//		}else{
//			//echo '$rdata is not a array';
//			$this->os->log_object->return_code = '-105';
//		}
//		$this->os->log_object->write_response();
//	}
	
	//查询落地到服务器信息  
//	public function get_server_speed() {
//		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
//		//如需要获得返回的余额可以用，$context['p_balance']
//		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
//		
//		$offset = empty($_REQUEST['start']) ? 0 : $_REQUEST['start'];
//		$limit = empty($_REQUEST['limit']) ? 15 : $_REQUEST['limit'];
//		$rdata = $this->os->billing_db->route_get_server_speed($offset,$limit);
//	
//		
//		
//		if(is_array($rdata)){
//			$list_array = array();
//			//遍历数组
//			for ($i = 0;$i < count($rdata); $i++) {
//				if (intval($rdata[$i]['stability']) == 1) {
//					$stability = '稳定性差';
//				} else if (intval($rdata[$i]['stability']) == 2) {
//					$stability = '稳定性一般';
//				} else if (intval($rdata[$i]['stability']) == 3) {
//					$stability = '稳定性良';
//				} else if (intval($rdata[$i]['stability']) == 4) {
//					$stability = '稳定性优';
//				} else {
//					$stability = $rdata[$i]['stability'];
//				}
//				$r_data = array(
//					'id' => $rdata[$i]['server_speed_id'],
//					'server_id' => $rdata[$i]['server_id'],
//					'routing_name' => $rdata[$i]['routing_name'],
//					'routing_ip' => $rdata[$i]['routing_ip'],
//					'stability' => $rdata[$i]['stability'],
//					'stability_chn' => $stability,
//					'speed' => $rdata[$i]['speed']
//				);
//				array_push($list_array,$r_data);
//	 		}
//			$this->os->log_object->return_data['totalCount'] = $this->os->billing_db->total_count;
//			$this->os->log_object->return_data['data'] = $list_array;
//		}else{
//			//echo '$rdata is not a array';
//			$this->os->log_object->return_code = '-101';
//		}
//		$this->os->log_object->write_response();
//	}

	//get_routing_ip
//	public function get_routing_ip(){
//		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
//		//如需要获得返回的余额可以用，$context['p_balance']
//		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
//	
//		$rdata = $this->os->billing_db->route_get_routing_ip();
//	
//		if(is_array($rdata)){
//			$list_array = array();
//			//遍历数组
//			for ($i = 0;$i < count($rdata); $i++) {
//				$r_data = array(
//					'id' => $rdata[$i]['id'],
//					'server_id' => $rdata[$i]['server_id'],
//					'routing_name' => $rdata[$i]['routing_name']
//				);
//				array_push($list_array,$r_data);
//	 		}
//			$this->os->log_object->return_data['totalCount'] = $billingdb->total_count;
//			$$this->os->log_object->return_data['data'] = $list_array;
//		}else{
//			//echo '$rdata is not a array';
//			$this->os->log_object->return_code = '-101';
//		}
//		$this->os->log_object->write_response();
//	}
	
//	public function get_server_id() {
//		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
//		//如需要获得返回的余额可以用，$context['p_balance']
//		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
//		
//		$rdata = $this->os->billing_db->route_get_server_id();
//		
//		if(is_array($rdata)){
//			$list_array = array();
//			//遍历数组
//			for ($i = 0;$i < count($rdata); $i++) {
//				$r_data = array(
//					'id' => $rdata[$i]['id'],
//					'alias' => $rdata[$i]['alias']
//				);
//				array_push($list_array,$r_data);
//	 		}
//			$this->os->log_object->return_data['totalCount'] = $this->os->billing_db->total_count;
//			$this->os->log_object->return_data['data'] = $list_array;
//		}else{
//			//echo '$rdata is not a array';
//			$this->os->log_object->return_code = '-101';
//		}
//		$this->os->log_object->write_response();
//	}
	
	
	/******** 落地到服务器信息    end  ********/
}
?>

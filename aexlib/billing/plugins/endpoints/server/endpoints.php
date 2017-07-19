<?php

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
	$success = $api_obj->return_code > 0;
	$api_obj->push_return_data('success',$success);
	$api_obj->push_return_data('message', $api_obj->get_error_message($api_obj->return_code),'');
	
	$resp = $api_obj->write_return_params_with_json();		
	return $resp;
}
	

class ez_endpoints {

   private $os;

   /**
    * __construct()
    *
    * @access public
    * @param {class} $os The os.
    */
   public function __construct(os $os){
      if(!$os->session_exists()){
         die('Session does not exist!');
      }

		$this->os = $os;
	} // end __construct()
	
	public function ep_list(){
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
		
		//获取api lib的文件路径
		//获得请求发生的权限级别：运营商=0，代理商〉0，用户为-1
		$resaler = $this->os->sessions['resaler'];	//目前使用运营商级别，以后从Session中获得
		$offset = empty($_REQUEST['offset'])? (empty($_REQUEST['start']) ? 0 : $_REQUEST['start']) : $_REQUEST['offset'];
		$count = empty($_REQUEST['count'])? (empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit']) : $_REQUEST['count'];
		$rdata = $this->os->billing_ms_db->billing_endpoint_get_list($offset,$count,$resaler,$_REQUEST['type'],$_REQUEST['status'],
			$_REQUEST['endpoint']);
		if(is_array($rdata)){
			//var_dump($rdata);
			$this->os->log_object->return_data['totalCount'] = $this->os->billing_ms_db->total_count;
			$this->os->log_object->return_data['data'] = $rdata;
		}else{
			//echo '$rdata is not a array';
			$this->os->log_object->return_code = '-101';
		}
	
		$this->os->log_object->write_response();	
	}
	
	/*******ANI start******/
	public function ani_list(){
		//存储过程为合成存储过程
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback, $this);
		
		//获取api lib的文件路径
		//获得请求发生的权限级别：运营商=0，代理商〉0，用户为-1
		$resaler = empty($_SESSION['resaler'])?0:$_SESSION['resaler'];	//目前使用运营商级别，以后从Session中获得
		$offset = empty($_REQUEST['offset'])? (empty($_REQUEST['start']) ? 0 : $_REQUEST['start']) : $_REQUEST['offset'];
		$count = empty($_REQUEST['count'])? (empty($_REQUEST['limit']) ? 0 : $_REQUEST['limit']) : $_REQUEST['count'];
		$rdata = $this->os->billing_ms_db->billing_ani_list($offset,$count,$resaler,$_REQUEST['endpoint']);
		
		$ani_array =array();
		if(is_array($rdata)){
			//var_dump($rdata);
			//遍历数组
			for ($i = 0;$i < count($rdata); $i++) {
				$r_data = array(
					'ANI' => $rdata[$i]['AniNo'],
					'E164' => $rdata[$i]['EndpointNo'],
					'PIN' => $rdata[$i]['PIN'],
					//'LogTime' => substr(date("Y-m-d H:i:s",strtotime($row['limit_start_time'])),0,-3),
					'p_qtip' => "Edit",		//icon Text
					'p_icon' => "icon-edit-record",	//icon type application_view_detail
					'p_hide' => false,		//icon is or not hide
					'p_qtip2' => "Delete",		//icon Text
					'p_icon2' => "icon-cross",	//icon type application_view_detail
					'p_hide2' => false,		//icon is or not hide
					'p_qtip3' => "Add",		//icon Text
					'p_icon3' => "icon-add-table",	//icon type application_view_detail
					'p_hide3' => false	
				);
				array_push($ani_array,$r_data);
	 		}
			$this->os->log_object->return_data['totalCount'] = $billingdb->total_count;
			$this->os->log_object->return_data['data'] = $ani_array;
		}else{
			//echo '$rdata is not a array';
			$this->os->log_object->return_code = '-101';
		}
	
		$this->os->log_object->write_response();
	}
	
	public function ani_edit(){
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
		
		//获取api lib的文件路径
		//获得请求发生的权限级别：运营商=0，代理商〉0，用户为-1
		$resaler = empty($_SESSION['resaler'])?0:$_SESSION['resaler'];	//目前使用运营商级别，以后从Session中获得
		$o_ani = $_REQUEST['o_ani'];
		$n_ani = $_REQUEST['ANI'];
		$n_e164 = $_REQUEST['E164'];
		$n_pin = $_REQUEST['PIN'];
		$rdata = $this->os->billing_ms_db->billing_ani_update($resaler,$o_ani,$n_ani,$n_e164,$n_pin );
		if (empty($rdata ["@p_return"])) {
			$this->os->log_object->return_code = $rdata ["h323_return_code"];
		}else{
			$this->os->log_object->return_code = $rdata ["@p_return"];
		}
		
		$this->os->log_object->write_response();
	}
		
	public function ani_add(){
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
		
		//获取api lib的文件路径
		//获得请求发生的权限级别：运营商=0，代理商〉0，用户为-1
		$resaler = empty($_SESSION['resaler'])?0:$_SESSION['resaler'];	//目前使用运营商级别，以后从Session中获得
		$rdata = $this->os->billing_ms_db->billing_ani_add($resaler,$_REQUEST['ANI'],$_REQUEST['E164'],$_REQUEST['PIN']);
		if (empty($rdata ["@p_return"])) {
			$this->os->log_object->return_code = $rdata ["h323_return_code"];
		}else{
			$this->os->log_object->return_code = $rdata ["@p_return"];
		}
		
		$this->os->log_object->write_response();
	}
	
	public function ani_delete(){
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->os->log_object->set_callback_func(get_message_callback,write_response_callback,$this);
		
		//获取api lib的文件路径
		//获得请求发生的权限级别：运营商=0，代理商〉0，用户为-1
		$resaler = empty($_SESSION['resaler'])?0:$_SESSION['resaler'];	//目前使用运营商级别，以后从Session中获得
		$rdata = $this->os->billing_ms_db->billing_ani_delete($resaler,$_REQUEST['ani']);
		if (empty($rdata ["@p_return"])) {
			$api_obj->return_code = $rdata ["h323_return_code"];
		}else{
			$api_obj->return_code = $rdata ["@p_return"];
		}
		$this->os->log_object->write_response();
	}
	/*******ANI end	******/
	
	
	
}

?>
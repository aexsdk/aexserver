<?php
require_once dirname(__FILE__).'/api_billing_pgdb.php';
require_once dirname(__FILE__).'/api_billing_mssql.php';

/**
 * 		类class_billing_db实现计费系统的功能，在这个类里统一pgsql和mssql计费系统，对于其他系统不再区分pgsql和mssql。
 * 根据此设计则可以平滑过度到统一的数据库平台上。
 * 		实现方式对于计费系统需要访问数据的功能我们都在这个类上实现，如果是需要访问mssql数据库的则调用class_billing_mssql
 * 的实现方法，如果是pgsql的则调用class_billing_pgdb的实现方法，甚至于有时需要两个类交叉调用来完成。这样就很好的解决了数据
 * 存储在不同数据库上的应用，也可以为以后升级合并数据打好基础。
 *
 */
class class_billing_intface{
	public $config;
	public $api_obj;
	public $total_count = 0;
	/**
	 * 构造函数
	 */
	public function __construct($config,$api_obj)
	{
		$this->config = $config;
		$this->api_obj = $api_obj;
		//parent::__construct($config,$api_obj);
	}
	/**
	 * 用户登录的校验函数，用户包括：手机用户、优盘用户等
	 */
	public function user_login($user,$pass){
		//$this->billing_db = new class_billing_pgdb($this->config->route_db_config, $this->api_obj);
		$msdb = new class_billing_db($this->config->billing_db_config, $this->api_obj);
		//首先根据ANI查找帐号
		$def_prefix = isset($this->api_obj->config->default_prefix)?$this->api_obj->config->default_prefix:'0086';
		$pno = check_phone_number($user,$def_prefix);
		$rani = $msdb->billing_get_ani_account(array(
			"caller" => $pno 		//使用全国码的电话号码
			));
		if(is_array($rani) && isset($rani['EndpointNo'])){
			$pin = $rani['EndpointNo'];
		}else{
			$pin = $user;
		}
		$r = $msdb->get_endpoint_info($pin,$pass);
		if(is_array($r))
			$r['username'] = $user;
		return $r;
	}
	
	public function crm_get_list($endpoint,$group,$filter,$start=0,$limit=10){
		$pg_db = new class_billing_pgdb($this->config->crm_db_config, $this->api_obj);
		$data =  $pg_db->crm_get_list($endpoint,$group,$filter,$start,$limit);
		$this->total_count = $pg_db->total_count;
		return	$data;
	}
	
	public function crm_get_cdr_list($endpoint,$rt,$from,$to,$filter,$start=0,$limit=10){
		$msdb = new class_billing_db($this->config->billing_db_config, $this->api_obj);
		$r = $msdb->billing_cdr_list($start,$limit,-1,$rt,$from,$to,'','',$endpoint);
		$this->api_obj->push_return_data('success',$this->api_obj->return_code>0);
		$this->api_obj->push_return_data('totalCount',$msdb->total_count);
		$this->api_obj->push_return_data('data',$r);
		return $this->api_obj->return_data;
	}
	
	public function crm_get_finance_list($endpoint,$from,$to,$filter,$start=0,$limit=10){
		$msdb = new class_billing_db($this->config->billing_db_config, $this->api_obj);		
		$r = $msdb->billing_balance_history($start,$limit,-1,'',$endpoint,'',$from,$to);
		$this->api_obj->push_return_data('success',$this->api_obj->return_code>0);
		$this->api_obj->push_return_data('totalCount',$msdb->total_count);
		$this->api_obj->push_return_data('data',$r);
		return $this->api_obj->return_data;
	}
	
	/*
	* wirter: lion wang 
	* caption: view devices
	* version: 1.0 
	* time: 2010-11-14
	* last time: 2010-11-14
	*/
	public function crm_get_server_member($param_array) {
		$pg_db = new class_billing_pgdb($this->config->crm_db_config, $this->api_obj);
		$data = $pg_db->crm_get_customer_list($param_array);
		$this->total_count = $pg_db->total_count;
		return $data;
	}
	
	/*
	* wirter: lion wang 
	* caption: add customer server member
	* version: 1.0 
	* time: 2010-12-29
	* last time: 2010-12-29
	*/
	public function crm_add_server_member($param_array) {
		$pg_db = new class_billing_pgdb($this->config->crm_db_config, $this->api_obj);
		return $pg_db->crm_add_server_member($param_array);
	}
	
	/*
	* wirter: lion wang 
	* caption: edit customer server member
	* version: 1.0 
	* time: 2010-12-31
	* last time: 2010-12-31
	*/
	public function crm_edit_server_member($param_array) {
		$pg_db = new class_billing_pgdb($this->config->crm_db_config, $this->api_obj);
		return $pg_db->crm_edit_server_member($param_array);
	}
	
/*
	* wirter: lion wang 
	* caption: add route for phone number
	* version: 1.0 
	* time: 2010-12-31
	* last time: 2010-12-31
	*/
	public function crm_add_routing_for_pno($endpoint, $pno) {
		$pg_db = new class_billing_pgdb($this->config->crm_db_config, $this->api_obj);
		$pg_route_db = new class_billing_pgdb($this->config->route_db_config, $this->api_obj);
		//通过手机号码获取路由配置
		$rdata = $pg_db->route_get_gateway_for_crm($pno);
		if (is_array ( $rdata )) {
			if (!empty($rdata['v_call_params'])) { //为手机号码设置路由
				$rdata = $pg_route_db->crm_set_routing_for_pno($endpoint,$pno, $rdata['v_call_params']);;
				if (is_array ( $rdata )) {
					if ($rdata['p_return'] > 0) { //为手机号码设置路由
						return 1;
					}else{
						return '-6';
					}
				} else {
					return '-105';
				}
			}else{
				return  '-5';
			}
		} else {
			return '-105';
		}
	}
	
/*
	* wirter: lion wang 
	* caption: delete customer server member
	* version: 1.0 
	* time: 2010-12-31
	* last time: 2010-12-31
	*/
	public function crm_del_server_member($param_array) {
		$pg_db = new class_billing_pgdb($this->config->crm_db_config, $this->api_obj);
		return $pg_db->crm_del_server_member($param_array);
	}
	
	
	/*
	* wirter: lion wang 
	* caption:  get agent tree from mssql billing
	* version: 1.0 
	* time: 2010-12-29
	* last time: 2010-12-29
	*/
	public  function billing_get_agent_tree($resaler) {
		$ms_db = new class_billing_db($this->config->billing_db_config, $this->api_obj);
		return $ms_db->billing_get_agent_tree($resaler);
	}
	
	/*
	* wirter: lion wang 
	* caption:  get agent tree from mssql billing
	* version: 1.0 
	* time: 2010-12-29
	* last time: 2010-12-29
	*/
	public  function billing_get_agent_info($sub_resaler, $resaler = -1) {
		$ms_db = new class_billing_db($this->config->billing_db_config, $this->api_obj);
		return $ms_db->billing_get_agent_info($sub_resaler, $resaler);
	}
	
}

?>
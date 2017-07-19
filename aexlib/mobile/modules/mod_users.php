<?php

class class_users{
	public $config;
	public $api_obj;
	
	public function __construct(class_api $ao){
		$this->config = $ao->config;
		$this->api_obj = $ao;
		//header('Content-type: text/json');
		//加载多国语言
		$this->api_obj->load_error_xml(sprintf("%s.xml",get_class($this)));
	} 
	
	public function login(){
		require_once __EZLIB__.'/common/api_billing_db.php';

		$r = new stdClass();
		//存储过程为合成存储过程		
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		$domain = empty($_REQUEST['domain'])?'utone':$_REQUEST['domain'];
		$resaler = empty($_REQUEST['resaler'])?'0':$_REQUEST['resaler'];
	    				
	   	$params = array(
	   		$username,		//用户登录帐号
	   		$password, 		//登陆密码
	   		$domain,	//用户帐号所属域
	   		$resaler	//用户所属代理商，0表示运营商
	   		);		
		$billingdb = new class_billing_intface($this->config, $this->api_obj);
	   	$result = $billingdb->user_login($username,$password);
		if(is_array($result))
		{
			$this->api_obj->push_return_data('user',$result);
			$s = array(
					'userId' => $result['E164'],
					'user' => $username,
					'domain' => $domain,
					'resaler'=> $resaler,
					'group' => $result['AgentID'],
					'from'=> $_SERVER['REMOTE_ADDR'],
					'lang' => $_REQUEST['lang'],
					'oemroot' => $this->config->OEMROOT_DIR,
					'pass' => $password
				);
			$_SESSION['account'] = $s;	
			$r->session_id = session_id();
			$r->success = 'true';
			$r->domain = $_SERVER['SERVER_NAME'];
			$r->user = $result;	    				
		}else{
			$r->success = 'false';
			$r->message = $this->api_obj->lang_tr('用户名或者密码错误。');
		}
		return $r;
	}
	public function logout(){
		return $this->logoff();
	}
	public function logoff(){
		session_destroy();
		$r = new stdClass();
		$r->success = 'true';
		$r->message = 'Logoff success';
		return $r;
	}
	
}

?>
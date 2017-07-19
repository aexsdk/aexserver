<?php
	require_once __EZLIB__.'/common/api_billing_pgdb.php';
	
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
	$billingdb = new class_billing_intface($api_obj->config, $api_obj);
   	$result = $billingdb->user_login($username,$password);
	if(is_array($result))
	{
		$api_obj->push_return_data('user',$result);
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
		$r->user = $result;	    				
	}else{
		$r->success = 'false';
		$r->message = $api_obj->lang_tr('login_sp_return_not_array');
	}
	


?>
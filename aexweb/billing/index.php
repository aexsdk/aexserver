<?php
error_reporting(E_ALL ^ E_NOTICE);//ignore the "notice" error

define("__OEMROOT__",dirname(__FILE__));
//包含API和Billing的配置信息
require_once (dirname(dirname(__FILE__)).'/config/config.php');
require_once (__EZLIB__.'/billing/billing.php');			
require_once(dirname(__FILE__) .'/class.phpmailer-lite.php');
require_once(__EZLIB__.'/libary/firephp/FirePHPCore/fb.php');


if(isset($_REQUEST['method'])||isset($_REQUEST['moduleFunc'])){
	display('method',false);	
}else{
	$action = isset($_REQUEST['act'])? $_REQUEST['act']: $_REQUEST['service'];
	if(empty($action)){
		$page = $_REQUEST['page'];
		if(empty($page))
			$page = 'index';
		display($page,false);
	}else{
		display($action,true);
	}
}

function display($page,$action){
	$config = new config();
	$billing = new billing_os($config);

	$billing->smarty->compile_dir = dirname(__FILE__). '/templates_c/';
	$billing->smarty->cache_dir = dirname(__FILE__).'/cache/';
	$billing->smarty->config_dir = dirname(__FILE__).'/config/';
		
	switch(strtolower($page))
	{
		case 'index':
			$billing->page_index();
			break;
		case 'test':
			$billing->page_test();
			break;
		case 'login':
			if($action == true){
				$module_id = $_REQUEST['module'];
				if(!empty($module_id)){
					$billing->action_login($module_id);
				}else{
					//模块ID为空，或者为0，提示错误。并做错误记录
					$billing->write_error($billing->lang_tr('login_action_no_module_id'));
				}
			}else{ 
				$billing->page_login();
			}
			break;
		case 'logout':
		case 'logoff':
			$billing->action_logoff();
			break;
		case 'load':
			$module_id = $_REQUEST['moduleId'];
			if(!empty($module_id)){
				$billing->action_load($module_id);
			}else{
				//模块ID为空，或者为0，提示错误。并做错误记录
				$billing->write_error($billing->lang_tr('load_action_no_module_id'));
			}
			break;
		case 'connect':
			$action = $_REQUEST["action"];
			if(!isset($action))
				$action = $_REQUEST['service'];
			$module_id = $_REQUEST["moduleId"];			
			if(empty($module_id) or empty($action)){
				//模块ID为空，或者为0，提示错误。并做错误记录
				$billing->write_error($billing->lang_tr('connect_action_no_action_or_module_id'));
			}else{
				if($action == 'load'){
					$billing->action_load($module_id);
				}else{
					$billing->action_connect($action,$module_id);
				}
			}
			break;
		case 'desktop':
			$billing->action_desktop();
			break;
		case 'billinginfo':
			$billing->billing_info();
			//var_dump($billing->config);
			break;
		case 'backup':
			$billing->page_backup_function();
			break;
		case 'getcode':
			$billing->get_code();
			break;
		case 'user_login':
			if($action == true){
				$billing->user_action_login();
			}else{ 
				$billing->user_page_login();
			}
			break;
		case 'user_logoff':
			$billing->user_action_logoff();
			break;
		case 'phpinfo':
			phpinfo();
			break;
		default:
			$method_name = isset($_REQUEST['method'])?$_REQUEST['method']:$_REQUEST['moduleFunc'];
			$module_id = $_REQUEST['moduleId'];
			
			if(isset($module_id, $method_name) && $module_id != '' && $method_name != ''){
			      $billing->action_connect($method_name,$module_id);
			}else{
				$billing->write_warning($billing->lang_tr('undefine_page'));
				$billing->page_index();
			}
			break;
	}
}

?>

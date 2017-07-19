<?php
require_once (__EZLIB_OS__.'/server/os.php');
require_once (__EZLIB__.'/common/api_common.php');				
require_once (__EZLIB__.'/common/api_radius_funcs.php');				
require_once (__EZLIB__.'/common/api_log_class.php');				
require_once (__EZLIB__.'/common/api_billing_db.php');				
require_once (__EZLIB__.'/libary/smarty/Smarty.class.php');				

define("M_VERSION",'1.0');
define("SVN_VERSION","4934");

/*function exception_handler($exception) {
  echo sprintf("Uncaught exception: %s\r\nTrace:%s\r\n" , $exception->getMessage(), $exception->getTraceAsString());
}

set_exception_handler('exception_handler');
*/

function get_default_language(){
	$langs = explode(",",$_SERVER['HTTP_ACCEPT_LANGUAGE']);
	if(count($langs) > 0)
		return $langs[0];
	else 
		return 'zh-cn';
}

function path_to_url($path){
	//
	$uri = sprintf("http://%s:%s/",$_SERVER['SERVER_NAME'],$_SERVER['SERVER_PORT']);
	return str_replace($_SERVER['DOCUMENT_ROOT'],$uri,$path);
}
/*
 * 定义错误字符串处理的回调函数，正常情况下错误字符串已经可以按照语言和action取得。
 */
function billing_get_message_callback($api_obj,$context,$msg)
{
	return sprintf($msg,$api_obj->return_code);
}

/*
 * 写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
 */
function billing_write_response_callback($api_obj,$context)
{
	$resp = $api_obj->write_return_params_with_json();
	return $resp;
}

class billing_log extends class_api{
	public $biling;
	public function __construct($billing,$config)
	{
		$this->billing = $billing;
		$p_params = array(
			'run_start_time'=>microtime(),
			'gprs_caller'=>$_SERVER['HTTP_X_UP_CALLING_LINE_ID'],
			'gprs_agent'=>$_SERVER['HTTP_USER_AGENT'],
			'api_version' => $_REQUEST['v'],
			'api_o' =>$_REQUEST['o'],
			'api_lang' => $this->billing->get_lang(),
			'api_p' => $_REQUEST['p'],
			'api_action'=> $_REQUEST["action"],
			'api_params'=>array('action'=> $_REQUEST['a']),
			'common-lang-path'=> __EZLIB__,					//在此目录下的languages目录里
			'lang-path' => __EZLIB__.'/billing/system',		//语言文件在此目录下的languages目陆离
			'common-path'=> __EZLIB__.'/common'				//common文件的目录
		);
		parent::__construct($config,$p_params);
	}
	/*
	 * 主类析构函数，重载后不做自动记录请求日志
	*/
	function __destruct() {
    }
    /*
     * 获得代码语言的函数，这个函数是实现多国语言的基础。所有需要字符串的地方都需要经过他的处理才可以做到多国语言的支持
	*/
    public function lang_tr($str){
    	return $this->get_error_message($str,$str);
    }
    /*
     * 获得系统当前需要显示的语言
	*/
    public function get_lang(){
    	$v_lang = $_REQUEST['lang'];
		if (empty($v_lang) or substr($v_lang, 0, 1) == '*')
			$v_lang = get_default_language();
		return strtolower($v_lang);
    }
    /*
     * 记录错误信息的函数，调用此函数不会导致程序结束，需要调用者处理善后工作
	*/
	public function write_error($msg){
		parent::write_log(_LEVEL_ERROR_,$this->log_object->return_code,$msg);
		$this->return_code = _LEVEL_ERROR_;
		$this->push_return_data('msg',$msg);
		$this->write_response();
   		$body = sprintf($this->lang_tr('report_error_mail_body'),"<br>\r\n".array_to_string("<br>\r\n",$_SERVER));
   		$this->send_mail_to_support($msg,$body);
	}
    /*
     * 记录警告信息的函数，调用此函数不会导致程序结束，需要调用者处理善后工作
	*/
	public function write_warning($msg){
		parent::write_log(_LEVEL_WARNING_,$this->return_code,$msg);
		$this->return_code = _LEVEL_ERROR_;
		$this->push_return_data('msg',$msg);
		$this->write_response();
		$body = sprintf($this->lang_tr('report_warning_mail_body'),"<br>\r\n<hr>".array_to_string("<br>",$_SERVER));
   		$this->send_mail_to_support($msg,$body);
	}
	
    /*
     * 记录日志信息的函数，在程序中需要记录重要的操作是调用此函数
	*/
	public function write_hint($msg){
		parent::write_hint($msg);
	}

    /*
     * 记录跟踪信息的函数，当需要调试程序时调用此函数，tlevel越小级别越高，0=错误，1=警告，2=HINT
	*/
	public function write_trace($tlevel,$msg){
		parent::write_trace($tlevel,$msg);
	}
	
}

/*
 * 	定义billing的主类对象，billing的所有操作从该类开始
*/
class billing_os extends os{
	public $smarty; 		//模板对象，使Billing系统支持模板功能
	public $log_object;			//日志、错误信息、语言管理对象
	public $login_db;
	public $sessions = array();
	//定义EXTJS所需参数	
	public $Extjs_jsfiles;
	public $Extjs_cssfiles;
	//定义用户JS所需参数
	public $Userjs_jsfiles;
	public $Userjs_cssfiles;
	//JavaScripta变量定义
	public $js_vars;
	/*
	 * 主类的构造函数。
	 * $config传入配置对象
	*/
	public function __construct($config)
	{
		session_start();
		//echo sprintf("SESSION=%s<br><hr>",array_to_string("<br>",$_SESSION));
		$this->session_decode();
		//echo sprintf("/*sessions=<br>%s<hr>",array_to_string("<br>",$this->sessions));
		parent::__construct($config);
		//初始化模板对象
		$this->smarty = new Smarty;
		$this->smarty->addTemplateDir(array(__EZLIB__.'/billing/templates/'));
		$this->smarty->allow_php_templates= true;
		$this->smarty->force_compile = false;
		$this->smarty->debugging = false;
		$this->smarty->caching = true;
		$this->smarty->cache_lifetime = 120;
		//初始化日志、语言、错误管理类  
		$this->log_object = new billing_log($this,$config);
		//Pgsql 的billing数据库对象，访问login_db、voip billing、route等数据都可以通过此对象来完成
		$this->billing_db = new class_billing_pgdb($config->route_db_config, $this->log_object);
		//Mssql 的billing数据库对象，访问mssql billing DB
		$this->billing_ms_db = new class_billing_db($config->billing_db_config, $this->log_object);
		//调入以此类名命名的语言文件，系统会从项目语言目录、公共语言目录依次查找此文件合并语言文件内容，相同条目以项目语言为准
		$this->log_object->load_error_xml(sprintf("%s.xml",get_class($this)));
		//$this->log_object->load_error_xml(sprintf("%s.xml",'billing_base'));
		//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
		//如需要获得返回的余额可以用，$context['p_balance']
		$this->log_object->set_callback_func(billing_get_message_callback,billing_write_response_callback,$this);
		
		$this->init_extjs();
		$this->smarty->assign('config',$this->config);				
		$this->smarty->assign('image_loading',path_to_url(sprintf("%sresources/images/default/grid/loading.gif",
			$this->config->EXTJS_DIR)));
		$this->js_vars = array(
			'os_resaler' => $this->sessions['resaler'],
			'os_root_url' => path_to_url($this->config->OS_ROOT_DIR),
			'extjs_root_url' => path_to_url($this->config->EXTJS_DIR),
			'extjs_blank_url' => path_to_url($this->config->EXTJS_DIR.'resources/images/default/s.gif'),
			'os_login_url' => sprintf("http://%s:%s%s?act=login",$_SERVER['SERVER_NAME'],$_SERVER['SERVER_PORT'],$_SERVER['SCRIPT_NAME']),
			'os_logout_url' => sprintf("http://%s:%s%s?act=logoff",$_SERVER['SERVER_NAME'],$_SERVER['SERVER_PORT'],$_SERVER['SCRIPT_NAME']),
			'os_load_url' => sprintf("%s?act=load",$_SERVER['PHP_SELF']),
			'os_connect_url' => sprintf("%s?act=connect",$_SERVER['PHP_SELF']),
			'os_service_url' => sprintf("%s",$_SERVER['PHP_SELF']),
			'os_lang_code' => $this->get_lang()
		);
		$this->smarty->assign('extjs_js',$this->Extjs_jsfiles);
		$this->smarty->assign('extjs_css',$this->Extjs_cssfiles);
	}
	/*
	 * 主类析构函数
	*/
	function __destruct() {
		parent::__destruct();
    }
    
    public function get_version(){
    	return sprintf("V:%s(build:%s)",M_VERSION,SVN_VERSION);
    }
    /*
     * 输出系统配置参数以便检查系统
	*/
    public function billing_info(){
    	if(!$this->check_session())
    	{
    		$this->page_login();
    		return;
    	}
    	echo sprintf("Smarty dir=%s<br>Template dir=%s<br>Compile dir=%s<br>Plugins dir=%s<br>Cache dir=%s<br>Config dir=%s<br>",
	    	SMARTY_DIR,
    		join(",",$this->smarty->template_dir),
	        $this->smarty->compile_dir,
	        join(",",$this->smarty->plugins_dir),
	        $this->smarty->cache_dir,
	        $this->smarty->config_dir
	        );
    	echo sprintf("<br>Desktop dir=%s<br>Module dir=%s<br>Server Module dir=%s<br>Theme dir=%s<br>WallPapers dir=%s<br>Message dir=%s<br>Language dir=%s<br>",
    		$this->config->OS_ROOT_DIR,
			$this->MODULES_DIR,
			$this->SERVER_MODULES_DIR,
			$this->THEMES_DIR,
			$this->WALLPAPERS_DIR,
			$this->MESSAGE_DIR,
			$this->MESSAGE_LANG_DIR 
	        );
		echo sprintf("Lang is %s<br>Message:<br>%s<br>\r\n",
	    	$this->log_object->error_obj->lang,
	    	array_to_string("<br>\r\n",$this->log_object->error_obj->error_array));
	    if($this->check_session()){
	    	echo sprintf("Session is :<br>\r\n%s<br>\r\n",array_to_string("<br>\r\n",$this->sessions));
	    }
	    var_dump($this->config);
	    var_dump($_SERVER);
	    var_dump($_SESSION);
    }
    /*
     * 构建新的语言，该函数会以$src为基础创建$dest语言文件，请确保目标文件有写权限。
     * 语言文件包括：
     * 		1、ezlib/system/languages  ： 按照语言代码分类的语言文件，文件格式是xml，
     * 这些文件是为api类、action设置的，以类名和api_<action>命名的XML文件。billing_os.xml是
     * 主类的文件，很多公共语言库在此文件定义。此目录的文件在在PHP中可以用$billing->lang_tr(<$key>)
     * 来获得，在JS中使用lang_tr.<$key>来获得。
     * 		2、ezlib/system/login/language   : 登录的语言文件，这里的语言文件是以语言的代码为文件名的js文件，
     * 这个语言文件只能在login的页面中使用。
     * 		3、ezlib/system/module/<module_id>/language  : 模块语言文件，
	*/
    public function create_language($src,$dest){
    	
    }
    	
    public function get_lang_from_xml($file){
		//echo $file."<br>";
		$fp = @fopen($file,'r') or 
			$this->write_trace(10,sprintf("Can not load file %s",$file));
		$data = fread($fp, filesize($file));//读XML
		
		$this->write_trace(10,sprintf("Load xml file from %s\r\n%s",$file,$data));
		$xml2a = new api_XMLToArray(); //初始化类，将XML转化成array
		$root_node = $xml2a->parse($data);
		
		foreach($root_node["_ELEMENTS"] as $node){
			//if(is_array($v)){
			//foreach($v as $sk=>$sv){
				$js_context .= sprintf("lang_%s={\r\nlang_start:'lang start'\r\n",$node['_NAME']);
				foreach($node['_ELEMENTS'] as $ma){
						$js_context .= sprintf(",%s : '%s'\r\n",$this->regulate_key($ma['value']),$this->regulate_key($ma['message']));
					}
					$js_context .= "\r\n};\r\n";
				//}
			//}
		}		
		return $js_context;
	}
    
    /*
     * 获得代码语言的函数，这个函数是实现多国语言的基础。所有需要字符串的地方都需要经过他的处理才可以做到多国语言的支持
	*/
    public function lang_tr($str){
    	return $this->log_object->error_obj->get_message($str,$str);
    }
    
    public function regulate_key($string){
		$ReplaceStr  = preg_replace('/-/','_',$string);
		$ReplaceStr  = preg_replace("/'/","\\'",$ReplaceStr);
		return $ReplaceStr;
	}
    
    /*
     * 显示smarty模板
	*/
    public function display($tpl){
		$msgs = array();
		//var_dump($this->log_object);
		$ma = $this->log_object->get_messages();
		foreach ($ma as $k=>$v)
			if(!is_numeric($k))
				$msgs[$this->regulate_key($k)] = $this->regulate_key($v);
		$this->smarty->assign('lang_tr',$msgs);							//增加JS公共语言变量
		$this->smarty->assign('userjs_js',$this->Userjs_jsfiles);		//增加用户JS配置
		$this->smarty->assign('userjs_css',$this->Userjs_cssfiles);		//增加用户CSS配置
		$this->smarty->assign('js_vars',$this->js_vars);				//增加传递给JS的变量
		
		$this->smarty->display($tpl);
    }
    /*调用log的函数获得需要使用的语言代码，语言代码全部是小写*/
    function get_lang(){
    	$lang = $_REQUEST['lang'];
      	//echo sprintf("/*session=%s\r\n<br>request lang=%s*/",array_to_string("<br>",$this->sessions),$lang);
    	if(!isset($lang)){
      		$lang = $this->sessions['lang'];
      		//echo sprintf("/*session lang=%s*/",$lang);
    	}
      	if(!isset($lang)){
      		$lang = get_default_language();
      		//echo sprintf("/*default lang=%s*/",$lang);
      	}
      	//echo sprintf("/*lang=%s*/",$lang);
    	return $lang;
    }
    /*调用此函数调整语言代码以便在调用extjs的语言文件中查找文件名*/
    function adjust_lang($lang){
    	switch(strtolower($lang)){
    		case 'zh-cn':
    			return 'zh_CN';
    		case 'zh-tw':
    			return 'zh_TW';
    		case 'sr_rs':
    			return 'sr_RS';
    		case 'pt_br':
    			return 'pt_BR';
    		case 'pt_pt':
    			return 'pt_PT';
    		case 'en-us':
    		case 'en_us':
    			return 'en';
    		default:
    			return $lang;
    	}
    }
    /*
	 * 初始化Extjs的参数数组，之所以作为函数初始化，是为了实现多国语言
	*/	
    public function init_extjs(){
    	$lang = $this->adjust_lang($this->get_lang());
    	if(isset($_REQUEST['debug']) && $_REQUEST['debug'] == '1'){
			$this->Extjs_jsfiles = array(
				path_to_url($this->config->EXTJS_DIR.'adapter/ext/ext-base-debug.js')=>$this->lang_tr('Loading_extjs_file'),
				path_to_url($this->config->EXTJS_DIR."ext-all-debug-w-comments.js")=>$this->lang_tr('Loading_extjs_file'),
				path_to_url($this->config->EXTJS_DIR."examples/ux/ux-all-debug.js")=>$this->lang_tr('Loading_extjs_file'),
				//path_to_url($this->config->EXTJS_DIR."ext-fix.js")=>$this->lang_tr('Loading_extjs_file'),
				path_to_url($this->config->EXTJS_DIR."src/locale/ext-lang-$lang.js")=>$this->lang_tr('Loading_extjs_file')
			);
    	}else{
			$this->Extjs_jsfiles = array(
				path_to_url($this->config->EXTJS_DIR.'adapter/ext/ext-base.js')=>$this->lang_tr('Loading_extjs_file'),
				//path_to_url($this->config->EXTJS_DIR."ext-core.js")=>$this->lang_tr('Loading_extjs_file'),
				path_to_url($this->config->EXTJS_DIR."ext-all.js")=>$this->lang_tr('Loading_extjs_file'),
				path_to_url($this->config->EXTJS_DIR."examples/ux/ux-all.js")=>$this->lang_tr('Loading_extjs_file'),
				//path_to_url($this->config->EXTJS_DIR."ext-fix.js")=>$this->lang_tr('Loading_extjs_file'),
				path_to_url($this->config->EXTJS_DIR."src/locale/ext-lang-$lang.js")=>$this->lang_tr('Loading_extjs_file')
			);
    	}
		$this->Extjs_cssfiles = array(
			path_to_url($this->config->EXTJS_DIR."resources/css/ext-all-notheme.css") => $this->lang_tr('Loading_extcss_file'),
			path_to_url($this->config->EXTJS_DIR."resources/css/xtheme-gray.css") => $this->lang_tr('Loading_extcss_file'),
			path_to_url($this->config->EXTJS_DIR."examples/ux/css/ux-all.css") => $this->lang_tr('Loading_extcss_file')
		);
		$this->Userjs_jsfiles = array(
			path_to_url($this->config->USERJS_DIR.'ezjs.js')=>$this->lang_tr('Loading_userjs_file')  //添加用户定义JS文件，在这里不可以使用Desktop的类和变量
		);
		$this->Userjs_cssfiles = array(
			path_to_url($this->config->USERJS_DIR."ezjs.css")=>$this->lang_tr('Loading_usercss_file')
		);
		//var_dump($this->Extjs_jsfiles);
		//var_dump($this->Extjs_cssfiles);
		//var_dump($this->Userjs_jsfiles);
		//var_dump($this->Extjs_cssfiles);
    }
    /*
     * 添加用户自定义的CSS文件，对于每个页面也许需要添加特殊的CSS控制页面的显示
     * $cssfiles为数组：url=loading message
	*/
    public function addUsercss($cssfiles){
    	$this->Userjs_cssfiles = array_merge($this->Userjs_cssfiles,$cssfiles);
    	//$this->Userjs_cssfiles = array_unique($this->Userjs_cssfiles);
    }
    /*
     * 添加用户自定义的JS文件，对于每个页面也许需要添加特殊的JS控制页面的显示
     * $jsfiles为数组：url=Loading message
	*/
    public function addUserjs($jsfiles){
    	$this->Userjs_jsfiles = array_merge($this->Userjs_jsfiles,$jsfiles);
    	//$this->Userjs_jsfiles = array_unique($this->Userjs_jsfiles);
    }
    /*
     * 添加用户自定义的JS变量
     * $jsvars为数组：url=Loading message
	*/
    public function addJsvars($jsvars){
    	$this->js_vars = array_merge($this->js_vars,$jsvars);
    	//$this->Userjs_jsfiles = array_unique($this->Userjs_jsfiles);
    }
    /*
     * 记录错误信息的函数，调用此函数不会导致程序结束，需要调用者处理善后工作
	*/
	public function write_error($msg){
		$this->log_object->write_log(_LEVEL_ERROR_,$this->log_object->return_code,$msg);
		$this->log_object->return_code = _LEVEL_ERROR_;
		$this->log_object->push_return_data('msg',$msg);
		$this->log_object->write_response();
   		$body = sprintf($this->lang_tr('report_error_mail_body'),"<br>\r\n".array_to_string("<br>\r\n",$_SERVER));
   		$body .= sprintf("<hr>%s",$msg);
   		$this->send_mail_to_support($msg,$body);
	}

    /*
     * 记录警告信息的函数，调用此函数不会导致程序结束，需要调用者处理善后工作
	*/
	public function write_warning($msg){
		$this->log_object->write_log(_LEVEL_WARNING_,$this->log_object->return_code,$msg);
		$this->log_object->return_code = _LEVEL_ERROR_;
		$this->log_object->push_return_data('msg',$msg);
		$this->log_object->write_response();
		$body = sprintf($this->lang_tr('report_warning_mail_body'),"<br>\r\n<hr>".array_to_string("<br>",$_SERVER));
   		$body .= sprintf("<hr>%s",$msg);
   		$this->send_mail_to_support($msg,$body);
	}
	
    /*
     * 记录日志信息的函数，在程序中需要记录重要的操作是调用此函数
	*/
	public function write_hint($msg){
		$this->log_object->write_hint($msg);
	}

    /*
     * 记录跟踪信息的函数，当需要调试程序时调用此函数，tlevel越小级别越高，0=错误，1=警告，2=HINT
	*/
	public function write_trace($tlevel,$msg){
		$this->log_object->write_trace($tlevel,$msg);
	}
	/*
	 * session编码函数，把传入的数组编码为一个Session串
	*/
	public function session_encode($sessions){
		$s = array_to_string('&',$sessions);
		$es = api_encrypt($s,'session');	//对session进行加密
		return $es;
	}
	/*
	 * 对session进行解码，解码后放置到数组里
	*/
	public function session_decode(){
		if(!isset($_SESSION['user_agent'])){
	        $_SESSION['user_agent'] = MD5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
		}else if ($_SESSION['user_agent'] != MD5($_SERVER['REMOTE_ADDR']. $_SERVER['HTTP_USER_AGENT'])) {
	        session_regenerate_id();
	        return FALSE;
	    }
		
		if(isset($_SESSION["accountInfo"])){
			$s = api_decrypt($_SESSION["accountInfo"],'session');
			$this->sessions = api_string_to_array($s,'&','=');
			if(empty($this->sessions['userId']) or ($this->sessions['oemroot'] != $this->config->OEMROOT_DIR)){
				//Cookie中没有所需要的参数
				$_SESSION["accountInfo"] = $_SESSION["accountInfo"];
				return false;
			}else{
				return true;
			}
		}else{
			return false;
		}
	}
    /*
	 * 检查session的函数，如果没有session或者session已经过期，则跳到登录页面
	*/
    public function check_session($flag=false){
    	session_cache_expire(30);
    	$this->load('session');
    	if(!$flag)
    		$flag = $this->session->exists();
		if ($this->session_decode() && $flag) {
			return true;
		} else {
			//Redirect to login page,if the session is expired
			setcookie("sessionId", "");
			session_destroy();
			//$this->page_login();
			//var_dump($this->sessions);
			return false;
		}
    }
    /*
     * 显示登录页面的函数
	*/
    public function page_login(){
    	$lang = $this->get_lang();
    	$csses = array(
    		path_to_url($this->config->USERROOT_DIR.'/system/login/shared.css') => $this->lang_tr('Loading_usercss_file'),
    		path_to_url($this->config->USERROOT_DIR.'/resources/css/languages.css') => $this->lang_tr('Loading_usercss_file')
    		);
    	$this->addUsercss($csses);
    	
    	//如果OEM目录里的CSS文件存在的话则加载这些文件，以便覆盖OEM所需要的图片样式
    	$file = $this->config->OEMROOT_DIR.'/resources/user.css';
    	 
    	if(file_exists($file)){
    		$this->addUsercss(
    			array(
    				path_to_url($file) => $this->lang_tr('Loading_usercss_file')
    			)
    		);
    	}    	
    	
    	$jses = array(
    		path_to_url($this->config->USERROOT_DIR.'/system/login/cookies.js') => $this->lang_tr('Loading_userjs_file'),
    		path_to_url($this->config->USERROOT_DIR.'/system/login/login.js')  => $this->lang_tr('Loading_userjs_file')
    	);
    	$this->addUserjs($jses);
   	
    	if (file_exists($this->config->USERROOT_DIR."/system/login/language/$lang.xml")) {
    		$lang_login = $this->get_lang_from_xml($this->config->USERROOT_DIR."/system/login/language/$lang.xml");
    	}else{
    		$lang_login = $this->get_lang_from_xml($this->config->USERROOT_DIR."/system/login/language/zh-cn.xml");
    	}
    	
    	$this->smarty->assign('lang_login',$lang_login);
    	
		$this->display('login.tpl');
    }
    /*
     * 显示主页面函数
	*/
    public function page_index(){
    	if($this->check_session()){
			$this->init();
    		
	    	$lang = $this->get_lang();
	    	$csses = array(
	    		path_to_url($this->config->USERROOT_DIR.'/resources/css/desktop.css') => $this->lang_tr('Loading_usercss_file'),
	     		//path_to_url($this->config->OS_ROOT_DIR.'system/dialogs/colorpicker/colorpicker.css') => $this->lang_tr('Loading_usercss_file'),
	    		path_to_url($this->config->USERROOT_DIR.'/resources/css/languages.css') => $this->lang_tr('Loading_usercss_file')
	    		,path_to_url($this->config->OS_ROOT_DIR.'modules/common/static_lib/ext-ux-wiz/resources/css/ext-ux-wiz.css') => $this->lang_tr('Loading_usercss_file')
	    		,path_to_url($this->config->OS_ROOT_DIR.'modules/common/static_lib/billinglib/resources/billinglib.css') => $this->lang_tr('Loading_usercss_file')
	    		);
    		$this->addUsercss($csses);
	    	//如果OEM目录里的CSS文件存在的话则加载这些文件，以便覆盖OEM所需要的图片样式
	    	$file = $this->config->OEMROOT_DIR.'/resources/user.css';
	    	if(file_exists($file)){
	    		$this->addUsercss(array(
	    			path_to_url($file) => $this->lang_tr('Loading_usercss_file')
	    			));
	    	}
	    	
	    	$jses = array(
	    		//path_to_url($this->config->OS_ROOT_DIR."system/dialogs/colorpicker/ColorPicker.js") => $this->lang_tr('Loading_userjs_file'),
	    		path_to_url($this->config->OS_ROOT_DIR.'client/App.js') => $this->lang_tr('Loading_userjs_file'),
	    		path_to_url($this->config->OS_ROOT_DIR.'client/Desktop.js') => $this->lang_tr('Loading_userjs_file'),
	    		//path_to_url($this->config->OS_ROOT_DIR.'client/HexField.js') => $this->lang_tr('Loading_userjs_file'),
	    		path_to_url($this->config->OS_ROOT_DIR.'client/Module.js') => $this->lang_tr('Loading_userjs_file'),
	    		path_to_url($this->config->OS_ROOT_DIR.'client/Notification.js') => $this->lang_tr('Loading_userjs_file'),
	    		path_to_url($this->config->OS_ROOT_DIR.'client/Shortcut.js') => $this->lang_tr('Loading_userjs_file'),
	    		path_to_url($this->config->OS_ROOT_DIR.'client/StartMenu.js') => $this->lang_tr('Loading_userjs_file'),
	    		path_to_url($this->config->OS_ROOT_DIR.'client/TaskBar.js') => $this->lang_tr('Loading_userjs_file'),
	    		//添加模块公用脚本文件
	    		//path_to_url($this->config->OS_ROOT_DIR.'client/modules_common.js') => $this->lang_tr('Loading_userjs_file'),
	    		sprintf("%s?act=desktop",$_SERVER['PHP_SELF']) => $this->lang_tr('Loading_userjs_file')
	    		,path_to_url($this->config->OS_ROOT_DIR.'modules/common/static_lib/ext-ux-wiz/Wizard.js') => $this->lang_tr('Loading_userjs_file')
	    		,path_to_url($this->config->OS_ROOT_DIR.'modules/common/static_lib/ext-ux-wiz/Card.js') => $this->lang_tr('Loading_userjs_file')
	    		,path_to_url($this->config->OS_ROOT_DIR.'modules/common/static_lib/ext-ux-wiz/Header.js') => $this->lang_tr('Loading_userjs_file')
	    		,path_to_url($this->config->OS_ROOT_DIR.'modules/common/static_lib/ext-ux-wiz/CardLayout.js') => $this->lang_tr('Loading_userjs_file')
	    		//,path_to_url($this->config->OS_ROOT_DIR.'modules/common/static_lib/ext-ux-wiz/about-window/Ext.ux.AboutWindow.js') => $this->lang_tr('Loading_userjs_file')
	    		,path_to_url($this->config->OS_ROOT_DIR.'modules/common/static_lib/billinglib/billinglib.js') => $this->lang_tr('Loading_userjs_file')
	    		);
	    	$this->addUserjs($jses);
			/*var_dump($this->Extjs_jsfiles);
			var_dump($this->Extjs_cssfiles);
			var_dump($this->Userjs_jsfiles);
			var_dump($this->Extjs_cssfiles);*/
			
			//$this->smarty->assign('libaray',$this->print_library());//$this->theme->get());
			$this->smarty->assign('module_css',$this->print_module_css());//$this->module->get_css());

			$this->display('index.tpl');
    	}else{
    		$this->page_login();
    	}
    }
	/*
     * 显示主页面函数
	*/
    public function page_test(){
    	if($this->check_session()){
			$this->init();
    		
	    	$lang = $this->get_lang();
	    	$csses = array(
	    		path_to_url($this->config->USERROOT_DIR.'/resources/css/desktop.css') => $this->lang_tr('Loading_usercss_file'),
	    		//path_to_url($this->config->OS_ROOT_DIR.'system/dialogs/colorpicker/colorpicker.css') => $this->lang_tr('Loading_usercss_file'),
	    		path_to_url($this->config->USERROOT_DIR.'/resources/css/languages.css') => $this->lang_tr('Loading_usercss_file')
	    		);
    		$this->addUsercss($csses);
	    	//如果OEM目录里的CSS文件存在的话则加载这些文件，以便覆盖OEM所需要的图片样式
	    	$file = $this->config->USERROOT_DIR.'/resources/user.css';
	    	if(file_exists($file)){
	    		$this->addUsercss(array(
	    			path_to_url($file) => $this->lang_tr('Loading_usercss_file')
	    			));
	    	}
	    	
	    	$jses = array(
	    		//path_to_url($this->config->OS_ROOT_DIR."system/dialogs/colorpicker/ColorPicker.js") => $this->lang_tr('Loading_userjs_file'),
	    		path_to_url($this->config->OS_ROOT_DIR.'client/App.js') => $this->lang_tr('Loading_userjs_file'),
	    		path_to_url($this->config->OS_ROOT_DIR.'client/Desktop.js') => $this->lang_tr('Loading_userjs_file'),
	    		//path_to_url($this->config->OS_ROOT_DIR.'client/HexField.js') => $this->lang_tr('Loading_userjs_file'),
	    		path_to_url($this->config->OS_ROOT_DIR.'client/Module.js') => $this->lang_tr('Loading_userjs_file'),
	    		path_to_url($this->config->OS_ROOT_DIR.'client/Notification.js') => $this->lang_tr('Loading_userjs_file'),
	    		path_to_url($this->config->OS_ROOT_DIR.'client/Shortcut.js') => $this->lang_tr('Loading_userjs_file'),
	    		path_to_url($this->config->OS_ROOT_DIR.'client/StartMenu.js') => $this->lang_tr('Loading_userjs_file'),
	    		path_to_url($this->config->OS_ROOT_DIR.'client/TaskBar.js') => $this->lang_tr('Loading_userjs_file'),
	    		//添加模块公用脚本文件
	    		//path_to_url($this->config->OS_ROOT_DIR.'client/modules_common.js') => $this->lang_tr('Loading_userjs_file'),
	    		sprintf("%s?act=desktop",$_SERVER['PHP_SELF']) => $this->lang_tr('Loading_userjs_file'),
	    		sprintf("%s?act=load&moduleId=%s",$_SERVER['PHP_SELF'],$_REQUEST['moduleId']) => $this->lang_tr('Loading_userjs_file')
	    		);
	    	$this->addUserjs($jses);
			/*var_dump($this->Extjs_jsfiles);
			var_dump($this->Extjs_cssfiles);
			var_dump($this->Userjs_jsfiles);
			var_dump($this->Extjs_cssfiles);*/
			
			//$this->smarty->assign('libaray',$this->print_library());//$this->theme->get());
			$this->smarty->assign('module_css',$this->print_module_css());//$this->module->get_css());

			$this->display('test.tpl');
    	}else{
    		$this->page_login();
    	}
    }
    public function get_code()
    {
    	require_once (__EZLIB__.'/common/getcode.php');
    }
    /*
     * 执行login脚本
	*/
    public function action_login($module){
		$this->log_object->push_return_data('success',false);
    	switch (strtolower($module))
    	{
    		case 'login':
    			{
    				$this->log_object->set_action('login');
    				$login_sql = 'select * from ez_login_db.sp_n_login($1,$2,$3,$4)';
    				$domain = empty($_REQUEST['domain'])?'utone':$_REQUEST['domain'];
    				$resaler = empty($_REQUEST['resaler'])?'0':$_REQUEST['resaler'];
    				$signed = empty($_REQUEST['signed'])?'0':$_REQUEST['signed'];
    				if(isset($_SESSION['signed_code']) && strtoupper($signed) != strtoupper($_SESSION['signed_code']))
    				{
    					$this->log_object->push_return_data('msg',
    						sprintf("%s",$this->lang_tr('login_fail_signed_code_error')));
    					$_SESSION['signed_code'] = '';
						$this->log_object->write_response();
    					return;
    				}
    				
    				if(isset($this->log_object->config->encrypt_pass) && $this->log_object->config->encrypt_pass)
    				{
	    				$this->load('security');
	         			$pass = $this->security->encrypt($_REQUEST['pass']);
       				}else{
       					$pass = $_REQUEST['pass'];
       				}
			    	$params = array(
			    		$_REQUEST['user'],		//用户登录帐号
			    		$pass, 		//登陆密码
			    		$domain,	//用户帐号所属域
			    		$resaler	//用户所属代理商，0表示运营商
			    		);		
					$login_return = $this->billing_db->exec_db_sp($login_sql,$params);
					if(is_array($login_return)){
						if($this->log_object->return_code>0){
							$s = array(
								'userId' => $login_return['p_userid'],
								'user' => $_REQUEST['user'],
								'domain' => $domain,
								'resaler'=> $resaler,
								'group' => $login_return['p_group'],
								'from'=> $_SERVER['REMOTE_ADDR'],
								'lang' => $_REQUEST['lang'],
								'oemroot' => $this->config->OEMROOT_DIR
								);
							//$this->log_object->push_return_data('accountInfo',$this->session_encode($s));
							$_SESSION['accountInfo'] = $this->session_encode($s);
							if(!isset($_SESSION['user_agent'])){
						        $_SESSION['user_agent'] = MD5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
							}
							// get our random session id
							$this->load('utility');
							$session_id = $this->utility->build_random_id();
							
							$this->load('session');							
							// delete any existing sessions for the member
							$this->session->delete(null, $login_return['p_userid']);
							// attempt to save login session
							$success = $this->session->add($session_id, $login_return['p_userid'], $login_return['p_group']);
							if($success){
								$this->log_object->push_return_data('success',true);
								$this->log_object->push_return_data('sessionId',$session_id);
							}
							
						}else{
							$this->log_object->push_return_data('msg',
								sprintf($this->lang_tr('login_fail_code'),$this->log_object->return_code));//$this->log_object->get_error_message($this->billing_db->return_code,''));
						}		    				
					}else{
						$this->log_object->push_return_data('msg',$this->lang_tr('login_sp_return_not_array'));
					}
					$this->log_object->write_response();
    			}
    			break;
    		default:
				$this->log_object->push_return_data('msg', $this->lang_tr('login_no_module'));
				//发送错误信息给开发人员
				$this->write_error($this->lang_tr('login_no_module'));
    			break;
    	}
    }
    /*
     * 执行logoff脚本
	*/
    public function action_logoff(){		
		//$this->session->logout();
		setcookie('sessionId','');
		session_destroy();
		setcookie(session_name(),'',time()-3600);
		$_SESSION = array();
		$this->page_login();
    }
    /*
     * 执行action的函数脚本
	*/
    public function action_connect($action,$module_id){
		if($action != "" && $module_id != ""){	
			if ($this->check_session(true)) {
				//$this->module->run_action($module_id, $action);

				//$method_name = $_REQUEST['method'];
				//$module_id = $_REQUEST['moduleId'];
				if(isset($_REQUEST['um']) && $_REQUEST['um'] == '1')
					$this->um_request($module_id, $action);
				else
					$this->make_request($module_id, $action);
			}else{
				/*$this->log_object->push_return_data('success',false);
				$this->log_object->push_return_data('msg',$this->lang_tr("SessionExprid"));
				$this->log_object->push_return_data('okfunc',"var path = window.location.pathname;"
    				. "window.location = path.substring(0, path.lastIndexOf('/') + 1)+'?page=login';");
				$this->log_object->write_response();*/
				$this->print_alert($this->lang_tr("SessionExprid"));
			}
		}else{
			$this->log_object->push_return_data('success',false);
			$this->log_object->push_return_data('msg',$this->lang_tr("load_action_no_module_id"));
			$this->log_object->write_response();
		}
    }
    /*
     * Load module script
	*/
    public function action_load($module_id){
	    if($module_id != ""){
	    	if ($this->check_session()) {
	    		//echo sprintf("/*Load module=%s*/",$module_id);
				$this->load_module($module_id);
	    	}else{
	    		$this->print_alert($this->lang_tr("SessionExprid"));
	    	}
		}else{
			$this->print_alert($this->lang_tr("load_action_no_module_id"));
		}
    }
	/*
	 * Load desktop javascript
	*/
    public function action_desktop(){
    	if($this->check_session()){
			$this->init();
    		require_once (__EZLIB_OS__."/QoDesk.php");//"/billing/QoDesk.php");
	    	print_QoDesk($this);
    	}else{
    		$this->print_alert($this->lang_tr("SessionExprid"));
    	}
    }
    
    public function page_backup_function()
    {
    	//select * from ez_utils_db.sp_get_schema_funcs_declare('ez_%');
	    if ($this->check_session()) {
	    	$db = new class_billing_pgdb($this->log_object->config->wfs_db_config,$this->log_object);
	    	$funcs = $db->backup_funcs($_REQUEST['schema']);
    		$this->smarty->assign('funcs',$funcs);
    		$this->display('db_funcs.tpl');
    	}else{
    		$this->print_alert($this->lang_tr("SessionExprid"));
    	}
    }
    
    public function user_action_login(){
    	$this->log_object->push_return_data('success',false);
		$this->log_object->set_action('login');
		$login_sql = 'select * from ez_login_db.sp_n_login($1,$2,$3,$4)';
		$domain = empty($_REQUEST['domain'])?'utone':$_REQUEST['domain'];
		$resaler = empty($_REQUEST['resaler'])?'0':$_REQUEST['resaler'];
		$signed = empty($_REQUEST['signed'])?'0':$_REQUEST['signed'];
    	if(isset($_SESSION['signed_code']) && $signed != $_SESSION['signed_code'])
		{
			$this->log_object->push_return_data('msg',sprintf("%s",$this->lang_tr('login_fail_signed_code_error')));
			$_SESSION['signed_code'] = '';
			$this->log_object->write_response();
			return;
		}
    	if(isset($this->log_object->config->encrypt_pass) && $this->log_object->config->encrypt_pass)
		{
			$this->load('security');
  			$pass = $this->security->encrypt($_REQUEST['pass']);
		}else{
			$pass = $_REQUEST['pass'];
		}
		$billingdb = new class_billing_intface($this->config, $this->log_object);
		
		$login_return = $billingdb->user_login($_REQUEST['user'],$pass);
		if(is_array($login_return))
		{
			$this->log_object->push_return_data('user',$login_return);
			$s = array(
					'userId' => $login_return['E164'],
					'user' => $_REQUEST['user'],
					'domain' => $domain,
					'resaler'=> $resaler,
					'group' => $login_return['AgentID'],
					'from'=> $_SERVER['REMOTE_ADDR'],
					'lang' => $_REQUEST['lang'],
					'oemroot' => $this->config->OEMROOT_DIR,
					'pass' => $pass
				);
			$_SESSION['accountInfo'] = $this->session_encode($s);
			if(!isset($_SESSION['user_agent'])){
				$_SESSION['user_agent'] = MD5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
			}
			// get our random session id
			$this->load('utility');
			$session_id = $this->utility->build_random_id();

			$this->load('session');							
			$this->log_object->push_return_data('success',true);
			$this->log_object->push_return_data('sessionId',$session_id);
		}else{
			$this->log_object->push_return_data('msg',sprintf($this->lang_tr('login_fail_code'),$this->log_object->return_code));
		}
		$this->log_object->write_response();
    }
    
    public function user_page_login(){
    	$lang = $this->get_lang();
    	$csses = array(
    		path_to_url($this->config->USERROOT_DIR.'/resources/css/languages.css') => $this->lang_tr('Loading_usercss_file')
    		);
    	$this->addUsercss($csses);
    	
    	//如果OEM目录里的CSS文件存在的话则加载这些文件，以便覆盖OEM所需要的图片样式
    	$file = $this->config->OEMROOT_DIR.'/resources/user.css';
    	 
    	if(file_exists($file)){
    		$this->addUsercss(
    			array(
    				path_to_url($file) => $this->lang_tr('Loading_usercss_file')
    			)
    		);
    	}    	
    	
    	$jses = array(
    		path_to_url($this->config->USERROOT_DIR.'/login/cookies.js') => $this->lang_tr('Loading_userjs_file'),
    		path_to_url($this->config->USERROOT_DIR.'/login/login.js')  => $this->lang_tr('Loading_userjs_file')
    	);
    	$this->addUserjs($jses);
   	
    	if (file_exists($this->config->USERROOT_DIR."/login/language/$lang.xml")) {
    		$lang_login = $this->get_lang_from_xml($this->config->USERROOT_DIR."/login/language/$lang.xml");
    	}else{
    		$lang_login = $this->get_lang_from_xml($this->config->USERROOT_DIR."/login/language/zh-cn.xml");
    	}
    	
    	$this->smarty->assign('lang_login',$lang_login);
    	
		$this->display('user_login.tpl');
    }
    
    public function user_action_logoff(){		
		//$this->session->logout();
		setcookie('sessionId','');
		session_destroy();
		setcookie(session_name(),'',time()-3600);
		$_SESSION = array();
		$this->user_page_login();
    }
	function encode($o){
		if(function_exists('json_encode')){
			$resp = json_encode($o);
		}else{
			require_once(__EZLIB__."/common/api_json.php");
			$json = new Services_JSON();
			$resp = $json->encode($o);
		}
		return $resp;
	}
    public function user_page_admin(){
    	if($this->check_session(true)){
			$this->init();
	    	$lang = $this->get_lang();
	    	//如果OEM目录里的CSS文件存在的话则加载这些文件，以便覆盖OEM所需要的图片样式
	    	$file = dirname($_SERVER['SCRIPT_FILENAME']).'/resources/user.css';
	    	if(file_exists($file)){
	    		$this->addUsercss(array(
	    			path_to_url($file) => $this->lang_tr('Loading_usercss_file')
	    			,path_to_url($this->config->OS_ROOT_DIR.'modules/common/static_lib/ext-ux-wiz/resources/css/ext-ux-wiz.css') => $this->lang_tr('Loading_usercss_file')
	    			,path_to_url($this->config->OS_ROOT_DIR.'modules/common/static_lib/billinglib/resources/billinglib.css') => $this->lang_tr('Loading_usercss_file')
	    			));
	    	}
	    	
	    	$jses = array(
	    		//path_to_url($this->config->OS_ROOT_DIR."system/login/language/$lang.js") => $this->lang_tr('Loading_userjs_file'),
	    		path_to_url($this->config->OS_ROOT_DIR.'modules/common/static_lib/ext-ux-wiz/Wizard.js') => $this->lang_tr('Loading_userjs_file')
	    		,path_to_url($this->config->OS_ROOT_DIR.'modules/common/static_lib/ext-ux-wiz/Card.js') => $this->lang_tr('Loading_userjs_file')
	    		,path_to_url($this->config->OS_ROOT_DIR.'modules/common/static_lib/ext-ux-wiz/Header.js') => $this->lang_tr('Loading_userjs_file')
	    		,path_to_url($this->config->OS_ROOT_DIR.'modules/common/static_lib/ext-ux-wiz/CardLayout.js') => $this->lang_tr('Loading_userjs_file')
	    		//,path_to_url($this->config->OS_ROOT_DIR.'modules/common/static_lib/ext-ux-wiz/about-window/Ext.ux.AboutWindow.js') => $this->lang_tr('Loading_userjs_file')
	    		,path_to_url($this->config->OS_ROOT_DIR.'modules/common/static_lib/billinglib/billinglib.js') => $this->lang_tr('Loading_userjs_file')
	    		,path_to_url($this->config->USERROOT_DIR.'um/client/um_main.js') => $this->lang_tr('Loading_userjs_file')
	    		);
	    	$this->addUserjs($jses);
	    	if (file_exists($this->config->USERROOT_DIR."/um/language/$lang.xml")) {
	    		$lang_um = $this->get_lang_from_xml($this->config->USERROOT_DIR."/um/language/$lang.xml");
	    	}else{
	    		$lang_um = $this->get_lang_from_xml($this->config->USERROOT_DIR."/um/language/zh-cn.xml");
	    	}
	    	$this->smarty->assign('lang_um',$lang_um);
    		$billingdb = new class_billing_intface($this->config, $this->log_object);
			$login_return = $billingdb->user_login($this->sessions['user'],$this->sessions['pass']);
			if(is_array($login_return))
				$this->smarty->assign('uphone_account',$this->encode($login_return));
	
			$this->display('uphone_admin.tpl');
    	}else{
    		//$billingdb = new class_billing_intface($this->config, $this->log_object);
			//$login_return = $billingdb->user_login($this->sessions['user'],$this->sessions['pass']);
			//var_dump($login_return);
			$this->user_page_login();
    	}
    }
    
	public function um_request($module_id, $method_name){
      // do we have the required params?
      if(!isset($module_id, $method_name) || $module_id == '' || $method_name == ''){
         die("{success: false, message: 'Missing required params!'}");
      }
      
      $error_found = false;
      $error_message = '';

	  $module_dir = $this->config->USERROOT_DIR."/um/server/";
      $file = $module_dir."um.php";
      $class = $module_id;

      // does the file exist and is a regular file
      if(!is_file($file)){
         $error_found = true;
         $error_message = 'Message: File ('.$file.') not found for module: '.$module_id;
         $this->log_object->push_return_data('success',false);
		$this->log_object->push_return_data('msg',$this->lang_tr("load_action_no_module_id"));
		$this->log_object->write_response();
		return;
      }

      require($file);

      // does the class exist?
      if(!class_exists($class)){
         $error_found = true;
         $error_message = 'Message: '.$class.' does not exist for server module: '.$module_id;
      }

      $module = new $class($this);

      // does the method exist?
      if(!method_exists($module, $method_name)){
         $error_found = true;
         $error_message = 'Message: '.$method_name.' does not exist for server module: '.$module_id;
      }

      if(!$error_found){
         $module->$method_name();
      }

		// log errors
		if($error_found){
			$this->errors[] = 'Script: os.php, Method: call_module_method, Message: '.$error_message;
			$this->load('log');
		   $this->log->error($this->errors);
		}
	} 
    
	/*
     * UPhone Ad page
	*/
    public function page_uphone_ad(){
    	$lang = $this->get_lang();
    	//如果OEM目录里的CSS文件存在的话则加载这些文件，以便覆盖OEM所需要的图片样式
    	$file = dirname($_SERVER['SCRIPT_FILENAME']).'/resources/user.css';
    	if(file_exists($file)){
    		$this->addUsercss(array(
    			path_to_url($file) => $this->lang_tr('Loading_usercss_file')
    			));
    	}
    	
    	$jses = array(
    		//path_to_url($this->config->OS_ROOT_DIR."system/login/language/$lang.js") => $this->lang_tr('Loading_userjs_file'),
    		path_to_url($this->config->USERROOT_DIR.'system/uphone/uphone_ad.js') => $this->lang_tr('Loading_userjs_file')
    		);
    	$this->addUserjs($jses);

		$v_Account = $_REQUEST['v_Account'];
		$v_Password = $_REQUEST['v_Password'];
		$VID = $_REQUEST['VID'];
		$SN = $_RE['SN'];
		$data = 'a=get_account_info';
		foreach($_REQUEST as $key => $value)
			$data .= sprintf("&%s=%s",$key,$value);
		//echo $data;
		try{
			$ch = curl_init();
			$st = microtime();
			//echo $data;
			$api_uphone = dirname($this->config->OEMROOT_DIR).'/api/api_uphone.php';
			curl_setopt($ch, CURLOPT_URL, path_to_url($api_uphone));
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 35); 
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 35); 
			
			$resp =  curl_exec($ch);
			if(isset($_REQUEST['f']) and $_REQUEST['f'] == '1')
			{
				var_dump($resp);
			}
			$resp = api_string_to_array($resp,"\r\n","=");
			if(isset($_REQUEST['f']) and $_REQUEST['f'] == '1')
			{
				var_dump($resp);
			}
			if($resp['cs_id'] == 1014){
				$resp['message'] = '<font size="4">尊敬的优话通客户：<br><font size="3">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 从即日起拨打国内免费电话时，帐户余额不得低于20元，且每月帐户消费不低于12元，不足12元的按照12元扣。时间按照自充值扣款成功起每月按照30天计算。当不满足条件时帐户自动调整为拨打国内按照每分钟0.099元计算。<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 免费拨打国内电话的规则是：<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 手机： 400+手机号码&nbsp;&nbsp;&nbsp; 如：40013800138000<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 固话： 400+区号（不带0）+号码&nbsp; 如：4001095555，400279555<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 使用国际电话拨号方式拨打国内电话，满足使用免费电话条件时每分钟0.07元，不满足使用免费电话条件时每分钟0.099元。<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 充值方法：<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1、在软件上使用充值菜单，直接使用网银充值；<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2、在网站http://www.chinautone.com/shop上购买充值卡充值。</font><br></font>';
			}else{
				$resp['message'] = "";
			}
			curl_close($ch);
			//echo microtime() - $st;
			//exit;
		}catch(Exception $e){
			echo $e->getMessage();
			exit;
		}
		//$datahtml = '<iframe src="http://www.chinautone.com/shop/goodtab.php" width="100%" height="100%" scrolling="no"></iframe>';
		//$dataurl = "http://www.chinautone.com/shop/goodtab.php?$data";
		$datahtml = '<font size="4">尊敬的优话通客户：<br><font size="3">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 从即日起拨打国内免费电话时，帐户余额不得低于20元，且每月帐户消费不低于12元，消费可以是拨打国际电话和使用国际拨号方式拨打国内电话。时间按照自充值扣款成功起每月按照30天计算。当不满足条件时帐户自动调整为拨打国内按照每分钟0.099元计算。<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 免费拨打国内电话的规则是：<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 手机： 400+手机号码&nbsp;&nbsp;&nbsp; 如：40013800138000<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 固话： 400+区号（不带0）+号码&nbsp; 如：4001095555，400279555<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 使用国际电话拨号方式拨打国内电话每分钟低至0.07元。<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 充值方法：<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1、在软件上使用充值菜单，直接使用网银充值；<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2、在网站<a href="http://www.chinautone.com/shop" target="_BLANK">http://www.chinautone.com/shop</a>上购买充值卡充值。</font><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;您可以使用优会通手机绑定优话通充抵最低消费，优会通90元包月，买手机送话费，老用户享受5折购机详情请登录http://www.hkutone.com查看。</font>';
		$this->smarty->assign('datahtml',$datahtml);
		$this->smarty->assign('uphone_account',$resp);

		$this->display('uphone_ad.tpl');
    }
    
    public  function send_mail_to_support($subject,$context){
    	$this->log_object->send_mail_to_support($subject,$context);
    }
    
    public function print_alert($msg){
    	//
    	$context = sprintf("EzDesk.ux.write_error('%s',function(btn, text,opt){var path = window.location.pathname;"
    		. "window.location = path.substring(0, path.lastIndexOf('/') + 1)+'?page=login';});",$msg);
    	echo $context;
    }
    
}
	
?>

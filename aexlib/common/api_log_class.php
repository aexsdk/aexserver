<?php
require_once(dirname(__FILE__) . "/api_base_class.php");
require_once(dirname(__FILE__) . "/api_pgsql_db.php");
require_once(dirname(__FILE__) . "/api_error_class.php");
require_once(dirname(__FILE__) . "/api_common.php");

define("_DB_CONNECT_ERROR_",-1);//   :  Database connect error
define("_DB_SQL_ERROR_",-2);//   :  Execute database sql error
define("_DB_NO_ACTION_FILE_",-3);//  :  Not found the action implementation file
define("_DB_NO_ACTION_",-4);//   :  No action found at request
define("_DB_GET_RESULT_ERROR_",-5);//   :  获得存储过程数组失败
define("_DB_NOT_ARRAY_",-10);// :  sp return not a array
define("_DB_IP_BLOCK_",-51);// :  src_ip can not allow use this api


//错误级别，表示发生严重错误，对于开发人员排错比较有利
define("_LEVEL_ERROR_",0);
//警告表示一般的错误级别，对于开发人员排错比较有利
define("_LEVEL_WARNING_",1);
//提示信息，一般的提示信息，对运营人员做好运营服务比较有利
define("_LEVEL_HINT_",2);
//跟踪调试信息，一般系统稳定后不会再记录这些信息，主要是开发阶段排错使用
define("_LEVEL_TRACE0_",10);
define("_LEVEL_TRACE1_",11);
define("_LEVEL_TRACE2_",12);
define("_LEVEL_TRACE3_",13);
define("_LEVEL_TRACE4_",14);

/*
	重新定义的API全局控制类，不再依赖于具体的ACTION，它处理基本的参数处理、错误处理、LOG日志等等，LOG日志的数据库对象与具体action的DB对象分离，支持不同的数据库。
	Log不再只在退出的时候写Log，程序的任何位置都可以调用Log函数写入调试信息，以便正确处理和调试分析。也就是说除了记录操作的详细信息外还可以记录所有的操作过程。操作过程的LOG是由Action的实现者根据需要写入的的。
*/
class class_api{
	var $config;		//配置信息作为一个对象存储和传递，这样更加灵活
	var $error_obj;		//错误处理对象
	var $log_db;		//处理log的数据库连接对象
	var $no_log;
	
	public $md5_key;
	//系统的参数，此参数是从入口文件中传入的
	public $params;
	public $dest_mod; //模块名称
	
	public $return_code = 0;
	public $return_data = array();
	public $return_xml = array();
	
	public $resp_data = '';
	
	var $write_response_func ;
	var $get_message_func ;
	var $callback_context ;
	/**
	 * 构造函数，传入配置参数以及
	 */
	public function __construct($config,$p_params)
	{
		$this->no_log = FALSE;
		$this->params = $p_params;
		$this->dest_mod = $config->dest_mod;
		if(empty($p_params['api_params']) or empty($p_params['api_params']['action']))
			$this->params['api_params']['action'] = '';
		else
			$this->params['api_params']['action'] = to_regulate_action($p_params['api_params']['action']);
		$this->config = $config;
		if(isset($this->config)){
			$md5_key = $this->config->key;
			//echo $md5_key;
			/*初始化错误字符串处理对象*/
			if(empty($this->params['api_lang']))
				$this->params['api_lang'] = $this->get_default_language();
			$this->log_db = new class_log_db($config->log_db_config,$this,$this->config->LogLevel);
			$this->error_obj = new class_api_error(array(
												   'lang-path' => $this->params['lang-path'],
												   'action' => $this->params['api_params']['action'],
												   'lang' => $this->params['api_lang'],
												   'common-lang-path' => $this->params['common-lang-path']
												   ),$this);
		}else{
			//没有给出配置信息，将使用默认的配置信息
			die("Have not set config object for api class!");
		}
	}

	function __destruct() {
		if(isset($this->no_log) && $this->no_log)
			return;
		$this->write_action_log();
    }
	
    public function check_hacks(){
    	$r_ip = $this->get_source_ip_address();
    	$agent = $_SERVER['HTTP_USER_AGENT'];
    	if(isset($this->config->hack_ip)){
    		foreach ($this->config->hack_ip as $ip=>$a)
    		{
    			if(trim($r_ip) == trim($ip) or (strpos($agent,$a)!=false)){
    				//如果是要阻止的IP则修改md5_key
    				echo sprintf("%s=%s,%s include %s ",$r_ip,$ip,$agent,$a);
    				$this->md5_key = api_encrypt($ip.$a,sprintf("abcd%f%s",round(9999),$agent));
    				echo sprintf("\r\n key=%s\r\n",$this->md5_key);
    				return true;
    			}
    		}
    	}
    	return false;
    }
    
    public  function get_messages(){
    	return $this->error_obj->error_array;
    }
    static public function get_default_language(){
    	// 分析 HTTP_ACCEPT_LANGUAGE 的属性
		// 这里只取第一语言设置 （其他可根据需要增强功能，这里只做简单的方法演示）
		preg_match('/^([a-z\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
		$lang = $matches[1];
		switch ($lang) {
	       case 'zh-cn' :
	               break;
	       case 'zh-tw' :
	               break;
	       case 'ko' :
	               break;
	       default:
	               break;
		} 
		return $lang;
    }
	public function decode_param($md5key){
		if(empty($this->params['api_p']))
			return '';
		//var_dump($p_params['api_action']);
		if (empty($md5key))
			$unwrapMD5string = $this->params['api_p'];
		else
			$unwrapMD5string = api_decrypt($this->params['api_p'], $md5key);
		//var_dump($unwrapMD5string);
		$this->write_hint(sprintf("key=%s,%s", $md5key, $unwrapMD5string));
		if ( strpos("$unwrapMD5string","=") >0 ){
			//解析参数
			$p = api_string_to_array($unwrapMD5string, ',', '=');
			$this->params['api_params'] = $p;
			//var_dump($this->params['api_params']);
		}else {
			//解析参数
			$p = explode(',', $unwrapMD5string);
			if(!empty($p['0']))
				$p['action'] = $p['0'];
			//如果数组名为数字（老的方式），此处做兼容处理
			if (!empty($p['1']))
				$p['bsn'] = $p['1'];
			if (!empty($p['2']))
				$p['imei'] = $p['2'];
			if (!empty($p['3']))
				$p['pno'] = $p['3'];
			if (!empty($p['4']))
				$p['pin'] = $p['4'];		
			if (!empty($p['5']))
				$p['pass'] = $p['5'];		
			if (!empty($p['6']))
				$p['unpass'] = $p['5'];
			$this->params['api_params'] = $p;
			
		}
		
		$bsn = $this->params['api_params']['bsn'];
		if (!empty($bsn))
		{
			$order = array(" ", "\n", "\r");
			$this->params['api_params']['bsn'] = str_replace($order, '', $bsn);
		}
		
		$action = to_regulate_action($this->params['api_params']['action']);
		if(empty($action))
			$action = $this->params['api_action'];
		$this->set_action($action);
		return $this->params['api_params']['action'];
	}

	public function set_action($action){
		if($this->check_hacks()){
			$this->params['api_params']['action'] = 'hack';
			$this->push_return_data('h','h');
			$this->push_return_data('ha','ha');
			$this->push_return_data('hac','hac');
			$this->push_return_data('hack','hack');
			$this->write_response();
		}else{
			if(!empty($action)){
				$action = to_regulate_action($action);
				$this->params['api_params']['action'] = $action;
				if(isset($this->error_obj)){
					$this->error_obj->load_action_error($action);
					//$this->write_hint(array_to_string(',',$this->error_obj->error_array));
				}
			}else{
				$this->write_log(_LEVEL_WARNING_,_DB_NO_ACTION_,'');
			}
		}
	}

	public function get_from_api($url,$data){
		$ch = curl_init();
		$st = microtime();
		if(is_array($data))
			$data = array_to_string("&",$data);
		
		curl_setopt($ch, CURLOPT_URL, $url);//'http://202.134.80.109/eztor_billing/wfs_api/api.php');
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,TRUE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 35);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 35);
	   // echo "url=".$url;
	    //var_dump($data);
		$resp =  curl_exec($ch);
		/*$resp = api_string_to_array($resp,"\r\n","=");
		if(isset($_REQUEST['f']) and $_REQUEST['f'] == '1')
		{
			var_dump($resp);
		}*/
		curl_close($ch);
		return $resp;
	}
	function load_error_xml($file,$use_prefix=false){
		if(isset($this->error_obj)){
			$this->error_obj->get_from_xml($file,$use_prefix);
		}
	}

	public function set_callback_func($msg_func,$resp_func,$context){
		$this->get_message_func = $msg_func;
		$this->write_response_func = $resp_func;
		$this->callback_context = $context;
	}
	/*
     * 获得代码语言的函数，这个函数是实现多国语言的基础。所有需要字符串的地方都需要经过他的处理才可以做到多国语言的支持
	*/
    public function lang_tr($str){
    	return $this->error_obj->get_message($str,$str);
    }
	
	/*
		获得错误字符串的函数，在本类的派生类中均使用此函数来根据错误代码获得错误的字符串。
		派生类可以修改重载此函数，来实现新的获取方法，比如为错误串用sprintf格式化，添加其他内容。
	*/
	public function get_error_message($code,$default=''){
		if(isset($this->error_obj))
			$msg = $this->error_obj->get_message($code,$default);
		else
			$msg = $default;
		try{
			if(!empty($this->get_message_func))
				$msg = call_user_func($this->get_message_func,$this,$this->callback_context,$msg);		//用回调函数对错误字符串做格式化，如果需要的话
			else{
				$msg = sprintf($msg,$this->return_code);
			}
		}catch( Exception $e ){
			$this->write_warning(array(
					"msg" => $msg,
					"code" => $code,
					"error" => $e->getMessage()
				));
			return $msg;
		}
		return $msg;
	}
	
	public function get_md5_key(){
		if($this->log_db){
			$sql = $this->config->log_sqls['get_params'];
			$api_r = $this->log_db->exec_db_sp($sql, array($this->get_source_ip_address(), $this->dest_mod));
			//$this->write_hint(sprintf("ip=%s,sql=%s", $this->get_source_ip_address(), $sql));
			//Log db orveride set_return_code function,then need reset return code to p_return
			$this->return_code = $api_r['p_return'];
			//$this->write_hint(sprintf("Code=%d,APR_R=%s",$this->return_code,array_to_string("\r\n",$api_r)));
			if($this->return_code > 0){
				$this->md5_key = $api_r['p_md5_key'];
			}else{
				//$this->error_msg = new api_error($this->params['lang-path'],'',$this->params['api_lang'],$this->params['common-lang-path']);
				//$this->write_response();
				//exit;
				$this->write_log(_LEVEL_WARNING_,_DB_IP_BLOCK_,sprintf("sql=%s\r\nIP From %s",$sql,$this->get_source_ip_address()));
			}
			return $this->md5_key;
		}
	}

	/*
		need overide this function for custom response format
	*/
	public function write_response(){
		if($this->write_response_func != NULL){
			//$resp = $resp. $this->write_return_params_with_json();
			$resp = call_user_func($this->write_response_func,$this,$this->callback_context);		//用回调函数来修改回应的功能，注意，此时需要返回回应的字符串就可以了
		}else{
			$resp = $this->write_return_params();			//write old format response
			$resp = $resp. $this->write_return_xml();					//write xml format response
		}
		echo $resp;
		$this->resp_data = $resp;
	}
	
	public function push_return_data($name,$value){
		//echo '<br>'.$name.'='.$value.'<br>';
		$this->return_data[$name] = $value;
	}
	
	public function push_return_xml($fmt,$value){
		//针对2.6.0以前的版本的返回值<UTONE>做兼容
		$vcheck = api_version_compare($api_obj->params['api_version'], '2.6.0');
		
		if($this->md5_key == '' || $vcheck <= 0){
			$msg = sprintf($fmt,$value);
		}else{
			$msg = sprintf($fmt,api_encrypt($value,$this->md5_key));
		}
		//echo '<br>'.$msg.'<br>';
		array_push($this->return_xml,$msg);
	}
	
	function write_return_param($name,$value)
	{
		$values = $value;
		if(is_array($value))
			$values = join(",",$value);
		if(strlen($name) != 0)
		{
			$values = $name . '=' . $values;
		}
		if($this->md5_key <> '')
			$values = api_encrypt($values,$this->md5_key);
		if($name == 'response-code')
			$resp .= $this->write_return_param('',$value);				//Compatible early processing mode
		if($this->check_response_tag()){
			//采用动态tag解析返回值
			$tb = $_REQUEST['tb'];
			$te = $_REQUEST['te'];
			$resp .= sprintf("%s:%s:%s\r\n",$tb,$values,$te);
		}else{
			$resp .= $this->config->api_prefix . $values . "\r\n";
		}
		return $resp;
	}
	
	
	function write_invite_response_code()
	{
		$values = sprintf("RESPONSE-CODE=%d",$this->return_code);
		if($this->md5_key <> '')
			$values = api_encrypt($values,$this->md5_key);
		if($this->check_response_tag()){
			//采用动态tag解析返回值
			$tb = $_REQUEST['tb'];
			$te = $_REQUEST['te'];
			$resp .= sprintf("%s:%s:%s\r\n",$tb,$values,$te);
		}else{
			$resp .= $this->config->api_prefix . $values . "\r\n";
		}
		return $resp;
	}

	function write_invite_return_xml(){
		//针对2.6.0以前的版本的返回值<UTONE>做兼容
		$vcheck = api_version_compare($api_obj->params['api_version'], '2.6.0');
		
		if ( $vcheck > 0)
			$resp =  "\r\n<UTONE S=\"%d\">\r\n";
		else
			$resp =  "\r\n<UTONE>\r\n";
		if($this->md5_key == ''){
			$resp = sprintf($resp,0);
		}else{
			$resp = sprintf($resp,1);
		}
		if(is_array($this->return_xml)){
			foreach ($this->return_xml as $value){
				$resp = $resp . $value."\r\n";
			}
		}
		if($this->check_response_tag())
		{
			//3.0.0以后的版本把XML作为一个DATA项目加密后送给客户端，这样可以避免WAP对返回内容中的XML符号过滤
			$values = api_encrypt($resp,$this->md5_key);
			$xb = $_REQUEST['xb'];
			$xe = $_REQUEST['xe'];
			$resp = sprintf("%s:%s:%s\r\n",$xb,$values,$xe);
		}
		return $resp;
	}
	
	/*
		如果支持json编码的话，此函数把返回的参数编码为json格式的数据输出。
	*/
	function write_return_params_with_json(){
		$resp = "";
		//var_dump($this->return_data);
		if(function_exists('json_encode')){
			$resp = json_encode(array_merge($this->return_data,array('response-code'=>$this->return_code)));
		}else{
			require_once(dirname(__FILE__) . "/api_json.php");

			/*foreach ($this->return_data as $key => $value){
				$resp .= sprintf("%s=%s\r\n",$key,$value);			//$key."=".$value.",";
			}*/
			$json = new Services_JSON();
			$resp = $json->encode(array_merge($this->return_data,array('response-code'=>$this->return_code)));
		}
		return $resp;
	}
	
	/*
		如果支持json编码的话，此函数把返回的参数编码为json格式的数据输出。
	*/
	/*function json_encode(){
		if(function_exists('json_encode')){
			$resp = json_encode($this->return_data);
		}else{
			require_once(dirname(__FILE__) . "/api_json.php");

			$json = new Services_JSON();
			$resp = $json->encode($this->return_data);
		}
		return $resp;
	}*/
	function json_encode($obj){
		if(function_exists('json_encode')){
			$resp = json_encode($obj);
		}else{
			require_once(dirname(__FILE__) . "/api_json.php");

			/*foreach ($this->return_data as $key => $value){
				$resp .= sprintf("%s=%s\r\n",$key,$value);			//$key."=".$value.",";
			}*/
			$json = new Services_JSON();
			$resp = $json->encode($obj);
		}
		return $resp;
	}
	/*
		如果支持json编码的话，此函数把返回的参数json格式以数组输出。
	*/
	function json_decode($json){
		if(function_exists('json_decode')){
			$resp = json_decode($json);
		}else{
			require_once(dirname(__FILE__) . "/api_json.php");

			/*foreach ($this->return_data as $key => $value){
				$resp .= sprintf("%s=%s\r\n",$key,$value);			//$key."=".$value.",";
			}*/
			$json = new Services_JSON();
			$resp = $json->decode($json);
		}
		return $resp;
	}
	
	function to_xml($v)
	{
		if(is_array($v)){
			foreach ($v as $n => $p){
				if($this->md5_key == '')
					$vp = $p;
				else
					$vp = api_encrypt($p,$this->md5_key);
				$resp .= sprintf("\r\n".'<%1$s>%2$s</%1$s>',$n,$vp);
			}
		}else{
			if($this->md5_key == '')
				$vp = $v;
			else
				$vp = api_encrypt($v,$this->md5_key);
			$resp = $vp;	
		}
		return $resp;
	}
	
	function json_to_xml($json_obj)
	{
		$resp = "";
		if(!is_object($json_obj))
			$json_obj = $this->json_decode($json_obj);
		foreach ($json_obj as $id => $property)
		{
			if(is_object($property)){
				if(isset($property->attrs))
				{
					//节点包含attrs属性，说明使用attrs标示节点的属性，value标示节点的值
					$attr_resp = "";
					foreach ($property->attrs as $attr => $avalue)
					{
						$attr_resp .= sprintf(' %s=%s ',$attr,$avalue);
					}
					if(isset($property->value)){
						if(is_object($property->value)){
							//值为对象，说明有子节点，递归调用生成子节点
							if($property->value <> $json_obj)
							{
								$v = $this->json_to_xml($property->value);
								$resp .= sprintf("\r\n".'<%1$s %2$s>%3$s</%1$s>',$id,$attr_resp,$v);
							}
						}else{
							//值不是对象，则根据md5_key，如果需要加密则加密生成该节点，否则生成明文节点
							$v = $this->to_xml($property->value);
							$resp .= sprintf("\r\n".'<%1$s %2$s>%3$s</%1$s>',$id,$attr_resp, $v);
						}
					}else{
						//没有定义value，节点值为空
						$resp .= sprintf("\r\n".'<%1$s %2$s></%1$s>',$id,$attr_resp);
					}
				}else{
					//节点的property标示节点的值
					if(isset($property->value)){
						//节点不包含attrs但是包含value
						if(is_object($property->value)){
							if($property->value <> $json_obj){
								$v = $this->json_to_xml($property->value);
								$resp .= sprintf("\r\n".'<%1$s>%2$s</%1$s>',$id,$v);
							}
						}else
							$v = $this->to_xml($property->value);
							$resp .= sprintf("\r\n".'<%1$s>%2$s</%1$s>',$id,$v);
					}else{
						//节点即不包含attrs，也不包含value
						if($property <> $json_obj){
							$v = $this->json_to_xml($property);
							$resp .= sprintf("\r\n".'<%1$s>%2$s</%1$s>',$id,$v);
						}
					}
				}
			}else{
				$v = $this->to_xml($property);
				$resp .= sprintf("\r\n".'<%1$s>%2$s</%1$s>',$id,$v);			
			}
		}
		return $resp;
	}
	
	function check_response_tag()
	{
		if(!empty($_REQUEST['xb']) && !empty($_REQUEST['xe']) && !empty($_REQUEST['tb']) && !empty($_REQUEST['te'])){
			return true;
		}else{
			return false;
		}
	}
	
	/*
		按照老的格式返回参数
	*/
	function write_return_params(){
		if(is_array($this->return_data)){
			$resp = "\r\n";
			foreach ($this->return_data as $key => $value){
				$resp = $resp. $this->write_return_param($key,$value);
			}
		}
		return $resp;
	}
	
	/*
		完成XML格式的参数回应
	*/
	function write_return_xml(){
		//针对2.6.0以前的版本的返回值<UTONE>做兼容
		$vcheck = api_version_compare($api_obj->params['api_version'], '2.6.0');
		
		if ( $vcheck > 0)
			$resp =  "\r\n<UTONE S=\"%d\">\r\n";
		else
			$resp =  "\r\n<UTONE>\r\n";
		if($this->md5_key == ''){
			$resp = sprintf($resp,0);
		}else{
			$resp = sprintf($resp,1);
		}
		if(is_array($this->return_xml)){
			foreach ($this->return_xml as $value){
				$resp = $resp . $value."\r\n";
			}
		}
		$msg = $this->get_error_message($this->return_code,'Return code [%1$d]');
		if($this->md5_key == '' || $vcheck <= 0){
			$resp = $resp. "<R>".$this->return_code."</R>\r\n";
			if($msg != ''){
				$resp = $resp. "<M>".$msg."</M>\r\n";
			}else{
				$resp = $resp. "<M>Return code ".$this->return_code."</M>\r\n";
			}
		}else{
			$resp = $resp. "<R>".api_encrypt($this->return_code,$this->md5_key)."</R>\r\n";
			if($msg != ''){
				$resp = $resp. "<M>".api_encrypt($msg,$this->md5_key)."</M>\r\n";
			}
		}
		$resp = $resp. "</UTONE>\r\n";
		if($this->check_response_tag())
		{
			//3.0.0以后的版本把XML作为一个DATA项目加密后送给客户端，这样可以避免WAP对返回内容中的XML符号过滤
			$values = api_encrypt($resp,$this->md5_key);
			$xb = $_REQUEST['xb'];
			$xe = $_REQUEST['xe'];
			$resp = sprintf("%s:%s:%s\r\n",$xb,$values,$xe);
		}else{
			$resp = $resp. $this->write_return_param('response-code',$this->return_code);
		}
		//echo "\r\n".$this->get_error_message($this->return_code)."\r\n";
		return $resp;
	}
	
	public function get_source_ip_address(){
		if(isset($_SERVER["REMOTE_ADDR"]))
			return $_SERVER["REMOTE_ADDR"];
		else
			return '';
	}

	/*
		发生错误，写入错误代码和错误信息
	*/
	public function write_action_log(){
		//写log信息
		if($this->log_db){
			$sql = $this->config->log_sqls['action_log_sql'];
			//$msg = $this->get_error_message($code,'');
			$action = $this->params['api_params']['action'];
			if(empty($action))
				$action = 'Unassigned';
			$log_params = array(
				$this->get_source_ip_address(),			//访问者的Ip地址
				$this->get_source_ip_address(),			//访问者的IP地址						
				$this->config->dest_mod,								//配置里定义的目标模块
				$action,
				sprintf("api_param:\r\n%s\r\n_REQUEST:\r\n%s\r\n",
						array_to_string("\r\n",$this->params['api_params']),
						array_to_string("\r\n",$_REQUEST)),
				array_to_string("\r\n",$_SERVER),
				sprintf("Message:%s\r\nReturn Data:\r\n%s\r\nReturn XML:\r\n%s\r\nResponse:\r\n%s",
						$this->get_error_message($this->return_code),
						$this->json_encode($this->return_data),
						$this->json_encode($this->return_xml),
						$this->resp_data
						),
				$this->return_code,
				(microtime() - $this->params['run_start_time'])
			);
			if($this->return_code >0)
				$level = _LEVEL_HINT_;
			else
				$level = _LEVEL_WARNING_;
			$this->log_db->write_log_to_db($level,$sql,$log_params);
		}
	}

	public function write_error($msg){
		$this->write_log(_LEVEL_ERROR_,$this->return_code,$msg);
		$this->write_response();
		if(is_array($msg))
		{
			$body = sprintf("%s<hr>%s<br>\r\n<hr>%s<hr>%s",
				array_to_string("<br>",$msg),
				array_to_string("<br>",$_SERVER),
				array_to_string("<br>",$this->return_data),
				array_to_string("br",$this->params['api_params']));
   			$this->send_mail_to_support($msg['msg'],$body);
		}else{
			$body = sprintf("%s<hr>%s<br>\r\n<hr>%s<hr>%s",
				$msg,
				array_to_string("<br>",$_SERVER),
				array_to_string("<br>",$this->return_data),
				array_to_string("br",$this->params['api_params']));
   			$this->send_mail_to_support($msg,$body);
			//$this->write_response();
		}
		exit;
	}

	/**
	 * 记录警告信息的函数，此函数会在日志中记录警告信息，并且给技术支持发送邮件以便及时解决问题。
	 * 参数
	 * 	1、字符串参数
	 * 		$msg : 警告字符串
	 * 	2、数组参数
	 * 		$msg['msg'] : 警告字符串
	 * 		$msg[] : 其他数组项，存储警告发生时的上下文信息，以便及时解决问题
	 * 注：
	 * 		警告函数功能有所改动，发生警告时不会再推出程序，而是继续进行。如果需要终止程序执行，则需要调用错误函数。
	 */
	public function write_warning($msg){
		$this->write_log(_LEVEL_WARNING_,$this->return_code,$msg);
		if(is_array($msg))
		{
			$body = sprintf("%s<hr>%s<br>\r\n<hr>%s<hr>%s",
				array_to_string("<br>",$msg),
				array_to_string("<br>",$_SERVER),
				array_to_string("<br>",$this->return_data),
				array_to_string("br",$this->params['api_params']));
   			$this->send_mail_to_support($msg['msg'],$body);
		}else{
			$body = sprintf("%s<hr>%s<br>\r\n<hr>%s<hr>%s",
				$msg,
				array_to_string("<br>",$_SERVER),
				array_to_string("<br>",$this->return_data),
				array_to_string("br",$this->params['api_params']));
   			$this->send_mail_to_support($msg,$body);
			//$this->write_response();
		}
	}
	
	public function write_hint($msg){
		$this->write_log(_LEVEL_HINT_,$this->return_code,$msg);
	}

	public function write_trace($tlevel,$msg){
		$this->write_log(_LEVEL_TRACE0_+$tlevel,$this->return_code,$msg);
	}
	/*
		发生错误，写入错误代码和错误信息
	*/
	public function write_log($level,$code,$default=''){
		//写log信息
		$this->return_code = $code;
		$msg = $this->get_error_message($code,'');
		//echo sprintf("Level=%d,Code=%d,msg=%s(%s)\r\n",$level,$code,$msg,$default);
		if(!is_array($default)){
			$default = array('msg'=>$default);
		}
		/*if(function_exists('json_encode')){
			$default = json_encode($default);
		}else{
			require_once(dirname(__FILE__) . "/api_json.php");

			$json = new Services_JSON();
			$default = $json->encode($default);
		}*/
		$default = array_to_string(',',$default);
		if($this->log_db){
			$sql = $this->config->log_sqls['log_sql'];
			$action = $this->params['api_params']['action'];
			if(empty($action))
				$action = 'Unassigned';
			$p = array(
					   $this->get_source_ip_address(),
					   sprintf("%s.%s",$this->config->dest_mod,$action),
					   $level,
					   $code,
					   $msg,
					   $default);
			$this->log_db->write_log_to_db($level,$sql,$p);
		}
	}
    
    public  function send_mail_to_support($subject,$context){
    	$params = array(
    		'from' => 'website@eztor.com',
    		'cc' => isset($this->config->error_mail)?$this->config->error_mail:'',
    		'address' => 'support@eztor.com'
    		);
    	$body = sprintf("%s<br>\r\nreturn data is :%s<br>\r\nReturn XML is :<br>\r\n%s",
    		$context,array_to_string("<br>\r\n",$this->return_data),
    		array_to_string("<br>\r\n",$this->return_xml));
    	send_mail($params,$subject,$body);
    }
    
	public  function send_info_by_recharge($subject,$context){
		if (is_array($context))
			$context = array_to_string("<br>\r\n",$context);
			
    	$params = array(
    		'from' => 'website@eztor.com',
    		'cc' => isset($this->config->error_mail)?$this->config->error_mail:'',
    		'address' => 'support@eztor.com'
    		);
    	$body = sprintf("%s<br>\r\nreturn data is :%s<br>\r\nReturn XML is :<br>\r\n%s",
    		$context,array_to_string("<br>\r\n",$this->return_data),
    		array_to_string("<br>\r\n",$this->return_xml));
    	send_mail($params,$subject,$body);
    }    
    
	public function execute_action($module,$action){
		  // does the class exist?
		  $module = strtolower($module);
		  $action = strtolower($action);
		  $mclass = sprintf("class_%s",$module);
	      $r = new stdClass();
	      if(!class_exists($mclass)){
	         $r->success = 'false';
	         $r->message = sprintf("Moudle %s not exists.",$module);
	         return $r;
	      }
	      $m = new $mclass($this);
	
	      // does the method exist?
	      if(!method_exists($m, $action)){
	         $r->success = 'false';
	         $r->message = sprintf("Action of moudle %s not exists.",$module);
	         return $r;
	      }
	      return $m->$action();
	}
	/*
	 * session编码函数，把传入的数组编码为一个Session串
	*/
	public function set_session($sessions){
		//$s = array_to_string('&',$sessions);
		//$es = api_encrypt($s,'session');	//对session进行加密
		session_cache_expire(30);
		$_REQUEST['account'] = $sessions;
	}
	/*
	 * 对session进行解码，解码后放置到数组里
	*/
	public function check_session($rootdir){
		if(!isset($_SESSION['user_agent'])){
	        $_SESSION['user_agent'] = MD5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
		}else if ($_SESSION['user_agent'] != MD5($_SERVER['REMOTE_ADDR']. $_SERVER['HTTP_USER_AGENT'])) {
	        session_regenerate_id();
	        return FALSE;
	    }
		
		if(isset($_SESSION["account"])){
			$sessions = $_SESSION["account"];
			if(empty($sessions['userId']) or ($sessions['oemroot'] != rootdir)){
				//Cookie中没有所需要的参数
				session_destroy();
				return FALSE;
			}else{
				session_cache_expire(30);
				return $_SESSION['account'];
			}
		}else{
			return false;
		}
	}
}

class class_log_db extends api_pgsql_db{
	public $debug_level = 4;

	public function __construct($config,$api_obj,$level)
	{
		parent::__construct($config,$api_obj);
		if(!empty($level))
			$this->debug_level = $level;
		else
			$this->debug_level = 4;
	}
	function __destruct() {
		parent::__destruct();
    }

	public function set_return_code($code){
	}

	public function write_error($msg){
		syslog(LOG_WARNING,sprintf("write log to db error:%s %s",$this->get_error_message($code,''),$default));//echo "\r\n".$this->get_last_error();
	}

	public function write_warning($msg){
		syslog(LOG_WARNING,sprintf("write log to db warning:%s %s",$this->get_error_message($code),$default));
	}
	
	public function write_hint($msg){
		syslog(LOG_WARNING,sprintf("write log to db hint:%s %s",$this->get_error_message($code),$default));
	}

	public function write_trace($tlevel,$msg){
		syslog(LOG_WARNING,sprintf("write log to db trace:%s %s",$this->get_error_message($code),$default));
	}
	/*
		发生错误，写入错误代码和错误信息，并结束程序，写入log
	*/
	public function write_log($level,$code,$default=''){
		syslog(LOG_WARNING,sprintf("write log to db trace:%s",$this->get_error_message($code),$default));
	}

	public function write_log_to_db($level,$sql,$log_params){
		if($level < $this->debug_level){ 
			//echo $sql;
			if($this->exec_db_sp($sql,$log_params) == false)
				syslog(LOG_WARNING,sprintf("write log to db error:%s",$this->get_last_error()));//echo "\r\n".$this->get_last_error();
		}else{
			//echo "$level >= $this->debug_level\r\n";
		}
	}	
	/*
	 * 	获得系统log的函数，只有代理商才可以看到
	*/
	public function get_action_log(){
	}
	
	/*
	 * 
	*/
	public function get_event_log(){
		//
	}
}


?>

<?php
/**
 */

class api_base_class{
	public $api_object;		//全局控制对象
	/**
	 * 构造函数，传入配置参数以及
	 */
	public function __construct($config,$api_obj)
	{
		$this->api_object = $api_obj;
		if($this->api_object){
			//为此类调入特殊的错误定义码
			//在相应的语言代码下增加以此类名称的XML文件即可，如果没有则不调入
			//echo sprintf("Load from %s.xml<br>",get_class($this));
			//$this->api_object->write_hint(sprintf("Load from %s.xml",get_class($this)));
			$this->api_object->load_error_xml(sprintf("%s.xml",get_class($this)));
		}
	}

	function __destruct() {
    }
	
    public function write_response(){
		if($this->api_object)
			$this->api_object->write_response();
    }
    
	public function set_return_code($code){
		if($this->api_object)
			$this->api_object->return_code = $code;
	}
	
	public function get_return_code(){
		return $this->api_object->return_code;
	}
	
	public function push_return_data($key,$value){
		$this->api_object->push_return_data($key,$value);
	}
	
	public function get_return_data($key){
		return $this->api_object->return_data[$key];
	}
	
	/*
		获得错误字符串的函数，在本类的派生类中均使用此函数来根据错误代码获得错误的字符串。
		派生类可以修改重载此函数，来实现新的获取方法，比如为错误串用sprintf格式化，添加其他内容。
	*/
	public function get_error_message($code,$default=''){
		if(isset($this->api_object)){
				//调用api控制全局对象的获得错误列表的函数
				$msg = $this->api_object->get_error_message($code,$default);
		}else{
				$msg = $default;
		}
		return $msg;
	}

	public function get_message($code,$default=''){
		if($this->api_object)
			return $this->api_object->error_obj->get_message($code,$default);
		else 
			return '';
	}
	
	public function write_error($msg){
		if($this->api_object)
			$this->api_object->write_error($msg);
		else 
			echo $msg;
	}

	public function write_warning($msg){
		if($this->api_object)
			$this->api_object->write_warning($msg);
		else 
			echo $msg;
	}
	
	public function write_hint($msg){
		if($this->api_object)
			$this->api_object->write_hint($msg);
	}

	public function write_trace($tlevel,$msg){
		if($this->api_object)
			$this->api_object->write_trace($tlevel,$msg);
	}
	/*
		发生错误，写入错误代码和错误信息，并结束程序，写入log
	*/
	public function write_log($level,$code,$default=''){
		if($this->api_object){
				//调用api控制全局对象的获得错误列表的函数
				$msg = $this->api_object->write_log($level,$code,$default);
		}else{
			echo sprintf("Not set api object:\r\nLevel=%d,$code=%d,$msg=%s\r\n",$level,$code,$default);
			exit;
		}
	}
}

?>
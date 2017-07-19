<?php

class class_system{
	public $config;
	public $api_obj;
	
	public function __construct(class_api $ao){
		$this->config = $ao->config;
		$this->api_obj = $ao;
		//header('Content-type: text/json');
		//加载多国语言
		$this->api_obj->load_error_xml(sprintf("%s.xml",get_class($this)));
	} // end __construct()

	public function update(){
		$r = new stdClass();
		
		$r->success = 'true';
		$r->startup = "app.log('run startup script.');";
		$r->message = sprintf('更新配置成功');
		return $r;		
	}
}

?>
<?php
function ez_handle_append_row($context,$index,$row){
	array_push($context->rows,$row);
	//echo sprintf("Row[%d]=%d<br>",$index,count($context->rows));
	//var_dump($context);
}

class Ezwfs_Setting {

	private $os;
	public $rows;
	public $api_obj;
   
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
		$this->api_obj = $this->os->log_object;
		//加载多国语言
		$os->log_object->load_error_xml(sprintf("%s.xml",get_class($this)));
	} // end __construct()

	function do_action($api_object){
		$fn =  __EZLIB__."/ophone/modules/api_".$api_object->params['api_params']['action'].".php";
		
		if(file_exists($fn)){
			//action对应的PHP文件存在，包含此文件。在文件中应该包含action的实现
			require_once $fn;
		}else{
			//$fn = dirname(dirname(__FILE__)).'/ezbilling_api/modules/api_".$p_params['api_params']['action'].".php");
			//no this action function
			$api_object->return_code = _DB_NO_ACTION_FILE_;
			$api_object->write_warning("No this action file:$fn");
			exit;
		}
	} 	
}

?>
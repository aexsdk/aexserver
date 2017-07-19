<?php
/*
* 系统概要统计模块
* 	该模块为运营维护以及代理商提供系统的统计信息：
* 		1、系统概要：当前系统版本、登录类别（运营商、高级代理商商、一般代理商、用户登录）、登录系统的用户、权限组、货币类型、语言、落地固定前缀、代理商数量、用户数量等等；
* 		2、一周话务统计，用图表显示，话费和落地费只有在所有用户的货币类型为同一种货币才有参考价值，如果包含不同货币系统没有做汇率换算
* 			横坐标：日期		纵坐标：呼叫数量(X)、接通数量(X)、接通率(X)、话务(V)、话费(V)、落地话费(V)（纵坐标可以多选）
* 				11.1 周一           100			70			70%   200min  20	  5 
* 				11.2 周二		   
* 				11.3 周三
* 			底部工具栏设置选项：
* 				选择横坐标起始日期、选择纵坐标组合
* 		3、一周充值统计
* 			横坐标：日期		纵坐标：后台充值、充值卡充值、网银充值、移动充值卡充值、联通充值卡充值、电信充值卡充值
* 			底部工具栏：
* 				选择横坐标起始日期，选择纵坐标组合
* 	生成PDF并下载。
*    包含的方法：
*/

function ez_handle_append_row($context,$index,$row){
	array_push($context->rows,$row);
	//echo sprintf("Row[%d]=%d<br>",$index,count($context->rows));
	//var_dump($context);
}

class Ezwfs_Summary {

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

	function load_summary(){
		$formConfig = array(
		     "labelAlign"=>"right"
		    ,"columnCount"=>1
		);
		$fields = array(
		    // company name
		    array(
		         "name"=>"dbversion"
		        ,"fieldLabel"=>"Database version"
		        ,"editor"=>array(
		        	"xtype" => "displayfield"
		        )
		    )
		    ,array(
		         "name"=>"webversion"
		         ,"fieldLabel"=>"Web version"
		         ,"editor"=>array(
		             "xtype" => "displayfield"
		         )
		    )
		    ,array(
		        "name"=>"login_domain"
		        ,"fieldLabel"=>"Domain"
		        ,"editor"=>array(
		             "xtype" => "displayfield"
		        )
		    )
		    ,array(
		        "name"=>"login_resaler"
		        ,"fieldLabel"=>"Resaler"
		        ,"editor"=>array(
		             "xtype" => "displayfield"
		        )
		    )
		    ,array(
		        "name"=>"default_currency"
		        ,"fieldLabel"=>"Default currency"
		        ,"editor"=>array(
		             "xtype" => "displayfield"
		        )
		    )
		    ,array(
		        "name"=>"sub_resalers"
		        ,"fieldLabel"=>"Sub reslaers"
		        ,"editor"=>array(
		             "xtype" => "displayfield"
		        )
		    )
		    ,array(
		        "name"=>"sub_accounts"
		        ,"fieldLabel"=>"Sub accounts"
		        ,"editor"=>array(
		             "xtype" => "displayfield"
		        )
		    )
		);
		$billing_db = new class_billing_db($this->os->config->billing_db_config, $this->api_obj);
		//$sql = sprintf("select ",$this->os->sessions['resaler']);
		$config = array(
		     "success"=>true
		    ,"metaData"=>array(
		         "fields"=>$fields
		        ,"formConfig"=>$formConfig
		    )
		    //下面是初始化数据，这里都是显示数据，现在都是测试数据，有些数据需要从数据库中获得
		    ,"data"=>array(
		         "dbversion" => $billing_db->get_db_version()
		        ,"webversion" => $this->os->get_version()
		        ,"login_domain" => $this->os->sessions['domain']
		        ,"login_resaler" => $this->os->sessions['resaler']
		        ,"default_currency" => "CYN"
		        ,"sub_resalers" => $billing_db->get_sub_resaler_count($this->os->sessions['resaler'])
		        ,"sub_accounts" => $billing_db->get_sub_account_count($this->os->sessions['resaler'])
		    )
		);
		echo json_encode($config);
	}
	
	public function get_traffic(){
		$billing_db = new class_billing_db($this->os->config->billing_db_config, $this->api_obj);
		$r = $billing_db->get_traffic('2010-12-1','2010-12-7','%-%',$this->os->sessions['resaler']);
		if(is_array($r)){
			$this->api_obj->push_return_data('success',true);
			$this->api_obj->push_return_data('total',count($r));
			$this->api_obj->push_return_data('data',$r);
		}else{
			$this->api_obj->push_return_data('success',false);
		}
		$this->api_obj->write_response();
	}
	
	public function get_recharge(){
		$billing_db = new class_billing_db($this->os->config->billing_db_config, $this->api_obj);
		
	}
}

?>
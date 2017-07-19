<?php
//error_reporting(E_ALL);

define("__EZLIB__",dirname(dirname(dirname(__FILE__))).'/aexlib');
define("__EZLIB_OS__",__EZLIB__.'/libary/qoslib');

/*
 *为Session定义数据库连接字符串
*/
define('_SESSION_DB_STR_','dbname=aex_wfs user=postgres  hostaddr=127.0.0.1  port=5432');

//一般请求的加密密钥
define("_KEY_", "billing");
//更新配置请求的加密密钥
define("_UPDATE_KEY_","ophone");
//旧格式的返回参数的前缀
define("_API_PREFIX_","UTONE-EZTOR-OPHONE:");
//定义数据库的连接参数
define("_DB_CONNECTION_STR_",'dbname=aex_wfs user=postgres  hostaddr=127.0.0.1  port=5432');
//定义Route数据库的连接参数
define("_ROUTE_CONNECTION_STR_",'dbname=aex_wfs user=postgres  hostaddr=127.0.0.1  port=5432');
//定义API的目的模块
define("_DEST_MOD_","BILLING");

/*
 * API和Billing的配置类，
*/
class config {
	/*api配置参数开始*/
	public $key = _KEY_;
	public $update_key = _UPDATE_KEY_;
	public $api_prefix = _API_PREFIX_;
	public $log_db_config = array(
								  "CONNECT_STRING" =>_DB_CONNECTION_STR_
								  );
	public $dest_mod = _DEST_MOD_;
	public $LogLevel  = 10;
	public $billing_db_config=array(
				"HOST" => '202.134.124.228',
				"DBNAME" => 'utone_db',
				"USER" => 'utone',
				"PASSWORD" => 'utone_db'
				);
	public $wfs_db_config=array(
								  "CONNECT_STRING" => 'dbname=aex_wfs user=postgres  hostaddr=127.0.0.1  port=5432'
								);
	public $mlm_db_config=array(
								  "CONNECT_STRING" =>_DB_CONNECTION_STR_
								);
	public $route_db_config=array(
								  "CONNECT_STRING" =>_ROUTE_CONNECTION_STR_,
								  "SCHEMA_NAME" => 'ez_routing_db'
								);
	public $log_sqls=array(
				"get_params" => 'select * from ez_log_db.sp_get_api_param($1,$2)',
				"log_sql" => 'select * from ez_log_db.sp_write_api_log($1,$2,$3,$4,$5,$6)',		//ip,mod_action,level,code,msg
				"action_log_sql" => 'select * from ez_log_db.sp_write_log($1,$2,$3,$4,$5,$6,$7,$8,$9)'
				);
	public $asterisk_config=array(
				"url" => '127.0.0.1:9088',   //202.134.80.109  本地
				"name" => 'webcall',
				"password" => 'webcallback'
				);
	public $radserver = '202.134.124.228';
	public $authport = 9815;
	public $sharedsecret = 'VoIP168';
	public $retryTimes = 2;
	public $timeout = 3;
	public $server_addr = '202.134.80.109';
	public $wfs_api_url = 'http://billing.eztor.com/wfs/api.php';
	
	public $ophone = array(
		"update" => 'http://config.oparner.com/ophone/ophone_update.php',
		"action" => 'http://billing.eztor.com/dp/api/api_ophone.php'
		);
	/*
	 * @Description 易宝支付非银行卡支付专业版接口范例 
	 * @V3.0
	 * @Author yang.xu
	 */
	  	
	/*# 商户编号p1_MerId,以及密钥merchantKey 需要从易宝支付平台获得
	$p1_MerId	= "10001162155";//怡拓电脑商户编号																											#测试使用
	$merchantKey= "aff0ve7953gfzxo7e6e35v43h9vf8345o11twpcpesgozkmvnml02n0vfcc4";#怡拓电脑商户密钥
	$logName	= "YeePay_CARD.log";
	# 非银行卡支付专业版请求地址,无需更改.
	$reqURL_SNDApro		= "https://www.yeepay.com/app-merchant-proxy/command.action";
	# 非银行卡支付专业版测试地址,无需更改.
	#$reqURL_SNDApro		= "http://tech.yeepay.com:8080/robot/debug.action";

	/*api配置参数结束*/
	
	public $Timeout = 30;	//以秒为单位的超时时间
	public $session_expired = 30;

	//OS库的根路径
	public $OS_ROOT_DIR = __EZLIB_OS__;
	public $OS_PLUGIN_DIR = '/billing/plugins';
					
	/********api interface************/
	public $WFS_API_URL = 'http://billing.eztor.com/wfs/api.php';
	public $WFS_API_SECRET = 'abcd1234';

	//定义EXTJS所需参数	
	public $EXTJS_DIR = '/libary/ext-3.3.0/';				//Extjs目录路径
	//定义用户JS所需参数
	public $USERJS_DIR = '';
	public $USERROOT_DIR = '';
	public $OEMROOT_DIR = '';
	
	/*
	 * 主类的构造函数。
	*/
	public function __construct()
	{
		$this->OS_ROOT_DIR = __EZLIB_OS__.'/';
		$this->OS_PLUGIN_DIR = __EZLIB__.'/billing/plugins/';
		$this->EXTJS_DIR = __EZLIB__.'/libary/ext-3.3.0/';				//Extjs目录路径
		$this->USERJS_DIR = __EZLIB__.'/billing/userjs/';
		$this->OEMROOT_DIR = __OEMROOT__;
		$this->USERROOT_DIR = __EZLIB__.'/billing/';
	}
	/*
	 * 主类析构函数
	*/
	function __destruct() {
    }
}

?>
<?php

/*
//PHP5.3以后不再支持dl函数，扩展在PHP的配之中完成
if (!extension_loaded('md5ext')) {
	dl('php_md5ext.' . PHP_SHLIB_SUFFIX);
}*/

//一般请求的加密密钥
define("_KEY_", "mlm");
//更新配置请求的加密密钥
define("_UPDATE_KEY_","ophone");
//旧格式的返回参数的前缀
define("_API_PREFIX_","UTONE-EZTOR-OPHONE:");
//定义数据库的连接参数
define("_DB_CONNECTION_STR_",'dbname=eztor_billing user=pgadmin password=Eztor-HK109.*  hostaddr=192.168.168.1  port=5432');
//定义Route数据库的连接参数
define("_ROUTE_CONNECTION_STR_",'dbname=ez_oparner_db_beta user=pgadmin password=Eztor-HK109.*  hostaddr=192.168.168.1  port=5432');
//定义API的目的模块
define("_DEST_MOD_","UPHONE");


class class_config{
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
				//"DBNAME" => 'EztorDB',
				"DBNAME" => 'EztorDB',
				"USER" => 'ezbillingweb',
				"PASSWORD" => 'eztor123+-*'
				);
	public $mlm_db_config=array(
								  "CONNECT_STRING" =>_DB_CONNECTION_STR_
								);
	public $route_db_config=array(
								  "CONNECT_STRING" =>_ROUTE_CONNECTION_STR_,
								  "SCHEMA_NAME" => 'ez_routing_db'
								);
	public $log_sqls=array(
				"get_params" => 'select * from ez_log_db.sp_get_api_param($1)',
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
	public $wfs_api_url = 'http://202.134.80.109/ezbilling_wfs/wfs_api/api.php';
	
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
*/
}

?>

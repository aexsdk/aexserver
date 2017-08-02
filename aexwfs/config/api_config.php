<?php

/*
//PHP5.3以后不再支持dl函数，扩展在PHP的配之中完成
if (!extension_loaded('md5ext')) {
	dl('php_md5ext.' . PHP_SHLIB_SUFFIX);
}*/
define("__EZLIB__",dirname(dirname(dirname(__FILE__))).'/aexlib');

//一般请求的加密密钥
define("_KEY_", "abcd1234");
//更新配置请求的加密密钥
define("_UPDATE_KEY_","ophone");
//旧格式的返回参数的前缀
define("_API_PREFIX_","UTONE-EZTOR-OPHONE:");
//定义数据库的连接参数
define("_DB_CONNECTION_STR_",'dbname=aex_wfs user=postgres  hostaddr=127.0.0.1  port=5432');
//定义Route数据库的连接参数
define("_ROUTE_CONNECTION_STR_",'dbname=aex_wfs user=postgres  hostaddr=127.0.0.1  port=5432');
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
	public $wfs_db_config=array(
								  "CONNECT_STRING" =>_DB_CONNECTION_STR_
								);
	public $new_wfs_db_config=array(
		"CONNECT_STRING" =>_NEW_DB_CONNECTION_STR_
	);
	public $log_sqls=array(
				"get_params" => 'select * from ez_log_db.sp_get_api_param($1,$2)',
				"log_sql" => 'select * from ez_log_db.sp_write_api_log($1,$2,$3,$4,$5,$6)',		//ip,mod_action,level,code,msg
				"action_log_sql" => 'select * from ez_log_db.sp_write_log($1,$2,$3,$4,$5,$6,$7,$8,$9)'
				);
	public $carrier_array = array(
		'utone',
		'abccall',
		'macauzh'
	);
}

?>

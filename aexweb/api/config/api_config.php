<?php

/*
//PHP5.3以后不再支持dl函数，扩展在PHP的配之中完成
if (!extension_loaded('md5ext')) {
	dl('php_md5ext.' . PHP_SHLIB_SUFFIX);
}*/

//一般请求的加密密钥
define("_KEY_", "aex");
//更新配置请求的加密密钥
define("_UPDATE_KEY_","ophone");
//旧格式的返回参数的前缀
define("_API_PREFIX_","UTONE-EZTOR-OPHONE:");
//定义数据库的连接参数
//define("_DB_CONNECTION_STR_",'dbname=utone_db user=utone hostaddr=221.4.210.94  port=5432');
define("_DB_CONNECTION_STR_",'dbname=aex_db user=utone hostaddr=127.0.0.1  port=5432');
//定义Route数据库的连接参数
//define("_ROUTE_CONNECTION_STR_",'dbname=utone_db user=utone hostaddr=221.4.210.94  port=5432');
define("_ROUTE_CONNECTION_STR_",'dbname=aex_db user=utone hostaddr=127.0.0.1  port=5432');
//定义API的目的模块
define("_DEST_MOD_","UPHONE");


class class_config{
	public $carrier_name = 'utone'; //运营商的名称
	public $resaler = 0;			//代理商的代码，0表示运营商，不指定特殊的代理商
	public $use_route_ext = 1;
	public $recharge_need_sms_response = 1;
	public $sms_format = '%s，代码:%s。类型:%s,金额:%s,卡号:%s';
	public $sms_format_0 = '充值成功，本次充值%s元。感谢使用珠三角蜜聊卡，详情情咨询全国统一客服热线：18666973405。凭此信息再次充值可获得96折优惠。';
	public $hack_ip = array(
		"120.80.49.100" => "Nokia7610",
		"211.138.129.204" => "SymbianOS",
		"211.138.129.202" => "Nokia",
		"211.138.129.207" => "Nokia",
		"211.138.129.209" => "Nokia",
		"211.138.129.190" => "Nokia"
		);
	public $allow_ips = array(
		'default' => 'resaler=11690,pass=',
		"202.134.80.106" => "866400099,shdz",
		"210.51.55.157" => "866400099,shdz"
	);
	
	public $inner_prefix = '201';
	public $api_prefixs = array(
			'400' => '400',			//400前缀拨打大陆固话手机
			'201' => '201'			//201拨打电话
			,'9' => '9'				//国内长途拨号方式而外增加的前缀
			,'8' => '800'
			);
	public $ajust_prefix = '1';
	public $var_split = ',';		//回拨请求的变量分隔符，1.8以上需要以逗号分开，之前的版本需要以|分开
	public $route_split = ';';		//路由字符串的各个参数的分隔符，之前为逗号，但是有时变量分隔符也是逗号的时候就会有问题。
	public $ra_split = '|';			//路由数组的分割符，当返回多个路由时用这个分隔符分开
	public $cb_path = 'utone';		//回拨服务器的路径，此路径配置要与回拨服务器中http.conf中的配置相同
	public $cbver = 10800;			//asterisk的版本号
	
	public $cli_account = 'ivr-proxy';
	public $ivr_path = 'givr';
	public $select_pintype = '0';
	public $default_active = array(
		'agent' => 11901,			//新激活用户使用的代理商ID
		'Balance' => 0,				//新激活用户帐户余额为0
		'FreePeriod' => 0,			//租期为0
		'FreeDuration' => 0,		//免费通话时长为0
		'Valid_day' => 360			//预设参数的有效期为1年
		);
	public $qq_allows = array(
		'1904261215' => 'all',	//点点通
		'437413593'  => 'all'	//灵风
	);
		
	public $sms_msg = array(
		'recharge' => '您已经成功充值%s元.感谢使用珠三角蜜聊卡,咨询热线:18666973405.凭借此短信再次充值可获得更多优惠.',
		'query' => '您的帐户余额是%s元.感谢使用珠三角蜜聊卡,咨询热线:18666973405.',
		'bind' => '绑定成功,您的帐户余额是%s元.感谢使用珠三角蜜聊卡,咨询热线:18666973405.'
		);

	public $sms_route = array(
		'ZLChan' => array(
			'type' => 3,
			'url' => 'http://60.28.195.138/submitdata/service.asmx/g_Submit',
			'sname' => 'dlszytdn',
			'spwd' => '87654321',
			'scorpid' => '',
			'sprdid' => '1011818',
			'sdst' => '',
			'smsg' => ''
		),
		'339' => array(
			'type' => 0,						//0,
			'url' => '125.91.0.15:8080/sms.do',	//'http://ws.montnets.com:9002/MWGate/wmgw.asmx?wsdl',
			'uid' => '339',						//'J00030',
			'pass' => '123456()'				//'147258'
		),
		'梦网' => array(
			'type' => 1,
			'url' => 'http://ws.montnets.com:9002/MWGate/wmgw.asmx?wsdl',
			'uid' => 'J00030',//'ytdn-C000',
			'pass' => '147258' //'760309'
		),
		'Xie' => array(
			'type' => 2,
			'url' => 'http://117.79.237.3:8060/webservice.asmx?wsdl',
			'uid' => 'SDK-GUP-010-00031',
			'pass' => '155663'
		)
	);
	public $sms_account = array(
//		'type' => 0,						//0,
//		'url' => '125.91.0.15:8080/sms.do',	//'http://ws.montnets.com:9002/MWGate/wmgw.asmx?wsdl',
//		'uid' => '339',						//'J00030',
//		'pass' => '123456()'				//'147258'

		'type' => 1,
		'url' => 'http://ws.montnets.com:9002/MWGate/wmgw.asmx?wsdl',
		'uid' => 'J00030',//'ytdn-C000',
		'pass' => '147258' //'760309'

//		'type' => 2,
//		'url' => 'http://117.79.237.3:8060/webservice.asmx?wsdl',
//		'uid' => 'SDK-GUP-010-00031',
//		'pass' => '155663'

//		'type' => 3,
//		'url' => 'http://60.28.195.138/submitdata/service.asmx/g_Submit',
//		'sname' => 'dlqdcs02',
//		'spwd' => '87654321',
//		'scorpid' => '',
//		'sprdid' => '1012818',
//		'sdst' => '',
//		'smsg' => ''
	);
	
	public $pin_3ths = array(
		'u' => 'http://wfs.eztor.com/aexweb/api/api_use_pin.php'  
	);
	
	public $pin_cs = array(
		'a' => 1026,
		'b' => 1026
	);
			
	public $key = _KEY_;
	public $update_key = _UPDATE_KEY_;
	public $api_prefix = _API_PREFIX_;
	public $log_db_config = array(
								  "CONNECT_STRING" =>_DB_CONNECTION_STR_
								  );
    public $wfs_db_config = array(
								  "CONNECT_STRING" =>_DB_CONNECTION_STR_
								  );
	public $sms_db_config = array(
								  "CONNECT_STRING" =>_DB_CONNECTION_STR_
								  );
	public $dest_mod = _DEST_MOD_;
	public $LogLevel  = 10;
	public $billing_db_config=array(
				"HOST" => '127.0.0.1',
				"DBNAME" => 'utone_db',
				"USER" => 'utone',
				"PASSWORD" => 'utone_db'
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

	public $rad_acct = "127.0.0.1/9815/VoIP168/2/3";
	public $radserver = '127.0.0.1';
	public $authport = 9815;
	public $sharedsecret = 'VoIP168';
	public $retryTimes = 2;
	public $timeout = 3;
	public $server_addr = '127.0.0.1';
	public $wfs_api_url = 'http://wfs.eztor.com/aexwfs/api.php';
	public $new_wfs_api_url = 'http://wfs.eztor.com/aexwfs/api_new.php';
	
	/********** 易宝支付非银行卡支付*************/
	//商户编号p1_MerId,以及密钥merchantKey 需要从易宝支付平台获得
	public  $yeepay_name = "yeepay";
	public	$yeepay_p1_MerId	= "10001162155"; //怡拓电脑商户编号																											#测试使用
	public	$yeepay_merchantKey= "aff0ve7953gfzxo7e6e35v43h9vf8345o11twpcpesgozkmvnml02n0vfcc4";//怡拓电脑商户密钥
	public	$yeepay_logName	= "YeePay_CARD.log";
	//非银行卡支付专业版请求地址,无需更改.
	public	$yeepay_reqURL_SNDApro	= "https://www.yeepay.com/app-merchant-proxy/command.action";
	//易宝支付callback页面
	public	$yeepay_p8_Url = "http://wfs.eztor.com/aexweb/api/api_ophone.php?a=3pay_callback";
	public	$new_yeepay_p8_Url = "http://wfs.eztor.com/aexweb/api/api_ophone_new.php?a=3pay_callback";
	public  $yeepay_currency_type = "CNY";
	
	/********** 易宝支付非银行卡支付*************/
}

?>

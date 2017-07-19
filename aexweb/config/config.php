<?php
error_reporting(E_ALL);

define("__EZLIB__",dirname(dirname(dirname(__FILE__))).'/aexlib');
define("__EZLIB_OS__",__EZLIB__.'/libary/qoslib');

/*
 *为Session定义数据库连接字符串
*/
define('_SESSION_DB_STR_','dbname=aex_db user=postgres hostaddr=127.0.0.1  port=5432');

//一般请求的加密密钥
define("_KEY_", "billing");
//更新配置请求的加密密钥
define("_UPDATE_KEY_","ophone");
//旧格式的返回参数的前缀
define("_API_PREFIX_","UTONE-EZTOR-OPHONE:");
//定义数据库的连接参数
define("_DB_CONNECTION_STR_",'dbname=aex_db user=postgres hostaddr=127.0.0.1  port=5432');
//定义Route数据库的连接参数
define("_ROUTE_CONNECTION_STR_",'dbname=aex_db user=postgres hostaddr=127.0.0.1  port=5432');
//定义API的目的模块
define("_DEST_MOD_","BILLING");

/*
 * API和Billing的配置类，
*/
class config {
	public $carrier_name = 'utone'; //运营商的名称
	public $resaler = 0;			//代理商的代码，0表示运营商，不指定特殊的代理商
	public $use_route_ext = 1;
	public $recharge_need_sms_response = 1;
	
	public $hack_ip = array(
		"120.80.49.100" => "Nokia7610"
		);
	public $api_prefixs = array(
			'40000' => '400',			//400拨打国际
			'4000' => '400',		//防止用户拨号错误，400+带0的区号
			'400' => '400',			//400前缀拨打大陆固话手机
			'20100' => '201',			//201拨打国际
			'2010' => '201',		//201拨打固话
			'2011' => '201'			//201拨打手机
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
			'url' => 'http://chufa.lmobile.cn/submitdata/service.asmx',//'http://60.28.195.138/submitdata/service.asmx/g_Submit',
			'sname' => 'dlqdcs02',
			'spwd' => 'rzEGMOSU',
			'scorpid' => '',
			'sprdid' => '1012818',
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
		'u' => 'http://billing.eztor.com/utone/api/api_use_pin.php'  
	);
	
	public $pin_cs = array(
		'a' => 1026,
		'b' => 1026
	);
				
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
				"HOST" => '127.0.0.1',
				"DBNAME" => 'aex_db',
				"USER" => 'utone',
				"PASSWORD" => 'aex_db'
				);
	public $mlm_db_config=array(
								  "CONNECT_STRING" =>_DB_CONNECTION_STR_
								);
	public $route_db_config=array(
								  "CONNECT_STRING" =>_ROUTE_CONNECTION_STR_,
								  "SCHEMA_NAME" => 'ez_routing_db'
								);
	public $crm_db_config=array(
								  "CONNECT_STRING" =>_ROUTE_CONNECTION_STR_
								);
	public $ezip_db_config = array(
		"CONNECT_STRING" =>_SIP_CONNECTION_STR_
	);	
	public $wfs_db_config = array(
		"CONNECT_STRING" =>_DB_CONNECTION_STR_
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
	public $radserver = '127.0.0.1';
	public $authport = 9815;
	public $sharedsecret = 'VoIP168';
	public $retryTimes = 2;
	public $timeout = 3;
	public $server_addr = '127.0.0.1';
	public $wfs_api_url = 'http://billing.eztor.com/wfs/api.php';
	
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

	//OS库的根路径
	public $OS_ROOT_DIR = '/billing/';
					
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

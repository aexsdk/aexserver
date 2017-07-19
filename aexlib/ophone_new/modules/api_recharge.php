<?php
/*
	执行Action的行为
*/
api_ophone_action($api_object);



function get_message_callback($api_obj,$context,$msg){

	return sprintf($msg,$api_obj->return_code,
		$api_obj->return_data['reBalance'],//当前充值金额
		$api_obj->return_data['reNewBalance'],//当前总余额
		//$api_obj->return_data['FreeDuration'],//免费通话时长
		//$api_obj->return_data['reCurrencyType'],//费率
		$api_obj->return_data['VP']//话费到期时间
		);
		
}

/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj,$context){
	$resp = $api_obj->write_return_xml();
	return $resp;
}

/*
	定义Action具体行为
*/
/*
 * 空中充值
 */
function api_ophone_action($api_obj)
{
	//echo "api_ophone_action";
	//调用URL，间接访问wfs
	$billingdb  = new class_billing_db($api_obj->config->billing_db_config,$api_obj);
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$billingdb);
	$api_obj->load_error_xml('3pay_callback.xml');
	if(strrpos($api_obj->params['api_params']['rpin'],"#") === FALSE)
	{
		//用户需要在账号前输入大写的账号所属类型，如果无账号所属类型
		//默认识别为中国移动充值卡
		api_ophone_recharge($api_obj,$billingdb);
		
	}else{//当填写有其他充值方式标示时使用相应的充值方式
		
		//define("__EZLIB__",dirname(dirname(dirname(dirname(__FILE__)))).'/ezlib');
 	    //require_once (__EZLIB__."/common/api_3pay_YeePayCommon.php");	
		//include dirname(dirname(dirname(dirname(__FILE__))))."/ezlib/common/api_3pay_YeePayCommon.php";
		api_ophone_3pay($api_obj,$billingdb);

	} 
	//写返回的信息
	$api_obj->write_response();
}


function api_ophone_recharge($api_obj,$billingdb) {
	//获取充值卡账号和密码
	$recharge_array = array(
	    'rpin' => $api_obj->params['api_params']['rpin'],
	    'rpass'=> $api_obj->params['api_params']['rpass'],
	    'pin'  => $api_obj->params['api_params']['pin'],
		'value' => '987654321'
	);
	//echo "rpin=$sourcePin&&rpass=$pinPass";
	$rdata = $billingdb->ophone_recharge_balance($recharge_array);
	//var_dump($rdata);
	if(is_array($rdata)){
		//$api_obj->return_code = $rdata['p_return'];
		$api_obj->push_return_data('data',array_to_string(',',$rdata));
		if(isset($rdata['h323_return_code']))
	    {
	    	$api_obj->return_code = $rdata['h323_return_code'];
	    }else{
			$api_obj->return_code = $rdata['RETURN-CODE'];
	    }
	  
	    if(empty($rdata['reCurrencyType']) || $rdata['reCurrencyType'] == 'CNY' || $rdata['reCurrencyType'] == 'CYN')
        {
     	   $rdata['reCurrencyType'] = 'CNY';
        }
        
        //$api_obj->write_hint(array_to_string(',', $rdata));
  
            //本次充值金额
	    $api_obj->push_return_data('reBalance',empty($rdata['reBalance'])?'':
		"\r\n".sprintf($billingdb->get_message(302),$rdata['reBalance'],$rdata['reCurrencyType']));
	    
	    //当前总余额
	    $api_obj->push_return_data('reNewBalance',empty($rdata['reNewBalance'])?'':
		"\r\n".sprintf($billingdb->get_message(303),$rdata['reNewBalance'],$rdata['reCurrencyType']));
	    
	    //$api_obj->push_return_data('reCurrencyType',empty($rdata['reCurrencyType'])?'':
		//"\r\n".sprintf($billingdb->get_message(500),$rdata['reCurrencyType'],$rdata['reCurrencyType']));
	    
	    $api_obj->push_return_data('VP',empty($rdata['VP'])?'':
		"\r\n".sprintf($billingdb->get_message(304),$rdata['VP']));
	    
		//$api_obj->push_return_data('FreeDuration',$rdata['reCurrencyType']);
		$api_obj->push_return_data('reCurrencyType',$rdata['reCurrencyType']);	
		//$api_obj->push_return_data('ChargePlan',$rdata['re_rate']);//费率
		//$api_obj->push_return_data('HP',$rdata['reValidPeriod']);
		
		if ($api_obj->config->is_sms > 0) {
			switch ($api_obj->return_code) {
				case 301:
				$msg ="The amount of cost:".$rdata['reBalance'].$rdata['reCurrencyType'].";Current account balance:".$rdata['reNewBalance'].$rdata['reCurrencyType'];
				break;
				case -301:
				$msg = "Recharge failure,Incorrect recharge-pin or recharge-password。Return code -301";
 				break;
				case -302:
				$msg = "Recharge failure,pin number expired,Return code -302";
 				break;
				case -303:
				$msg = "Recharge failure,pin number miss,Return code -303";
  				break;
				case -304:
				$msg = "Recharge failure,This recharge-pin balance not enough。Return code -304";
  				break;
				case -305:
				$msg = "Recharge failure,This card has already been used。Return code -305"; 
  				break;
				case -306:
				$msg = "Recharge failure,Invalid Distributor ID。Return code -306";
  				break;
				case -307:
				$msg = "Recharge failure,Inconsistence currency,Return code -307";
 				break;
				case -308:
				$msg =  "Recharge failure,Return code -308";
  				break;
				case -309:
				$msg = "Recharge failure,Deduct recharge-pin balances failure,Return code -309";
 				break;
				default:
				$msg ="The amount of cost:".$rdata['reBalance'].$rdata['reCurrencyType'].";Current account balance:".$rdata['reNewBalance'].$rdata['reCurrencyType'];
				break;
			}
			//$msg = $api_obj->get_error_message($api_obj->return_code,'Return code [%1$d]');
 			//$result = send_sms($api_obj,$api_obj->params['api_params']['pno'],'*',$msg);
			$result = $api_obj->get_from_api($api_obj->config->sms_url,
			array(
				'method' => 'sendOneSms',
				'uid' => $api_obj->config->uid,
				'pwd' => md5($api_obj->config->pwd),
				'mobile' => $api_obj->params['api_params']['pno'],
				'rawtxt' => base64_encode($msg)
			));
			
			$api_obj->push_return_data('msg',$api_obj->params['api_params']['pno'].$msg."\r\n");
			$api_obj->push_return_data('result',$result."\r\n");
		}
		
		//更新wfs
		$r = $api_obj->get_from_api($api_obj->config->new_wfs_api_url,
			array(
				'a' => 'wfs_first_recharge',
				'bsn' => $api_obj->params['api_params']['bsn'],
				'imei' => $api_obj->params['api_params']['imei'],
				'carrier_id' =>	$api_obj->config->carrier_name,
				'pno' => $api_obj->params['api_params']['pno'],
				'ep' => $api_obj->params['api_params']['pin'],
				'cost' => $rdata['reBalance'],
				'real_cost' => $rdata['reRealCost'],
				'ct' => $rdata['reCurrencyType'],
				're_code' => 'CardPIN'
			)
		);
		$api_obj->write_hint(array_to_string(',', array(
				'a' => 'wfs_first_recharge',
				'bsn' => $api_obj->params['api_params']['bsn'],
				'imei' => $api_obj->params['api_params']['imei'],
				'carrier_id' =>	$api_obj->config->carrier_name,
				'pno' => $api_obj->params['api_params']['pno'],
				'ep' => $api_obj->params['api_params']['pin'],
				'cost' => $rdata['reBalance'],
				'real_cost' => $rdata['reRealCost'],
				'ct' => $rdata['reCurrencyType'],
				're_code' => 'CardPIN',
				'url' => $api_obj->config->wfs_api_url,
				'return' => $r
			)));
	}
			
}

/*
	 * @Description 易宝支付非银行卡支付专业版接口范例 
	 * @V3.0
	 * @Author yang.xu
*/
function api_ophone_3pay($api_obj,$billingdb){
	include "api_3pay_YeePayCommon.php";
    /*充值卡类型判别
    1.默认为优会通充值方式；
    2.根据充值传输过来的前缀来判断当前的充值方式
    */ 
    //UNICOM 联通卡 ,TELECOM 电信卡 ,SZX 神州行 
    $c_string  = explode ("#",strtoupper($api_obj->params['api_params']['rpin']));
    $api_obj->params['api_params']['r_type'] = $c_string['0'];
    $api_obj->params['api_params']['pin_num']   = $c_string['1'];
    
    //var_dump($c_string);exit;
    if($api_obj->params['api_params']['r_type'] == "SZX" ||
       $api_obj->params['api_params']['r_type'] == "SZ"  ||
       $api_obj->params['api_params']['r_type'] == "S"   ||
       $api_obj->params['api_params']['r_type'] == "1"  
    ){
		//用户需要在账号前输入大写的账号所属类型，如果无账号所属类型
		//默认识别为中国移动充值卡
		$c_type  = "SZX";// 神州行	
	}else if($api_obj->params['api_params']['r_type'] == "UNICOM" ||
	         $api_obj->params['api_params']['r_type'] == "UNICO"  ||
	         $api_obj->params['api_params']['r_type'] == "UNIC"   ||
	         $api_obj->params['api_params']['r_type'] == "UNI"    ||
	         $api_obj->params['api_params']['r_type'] == "UN"     ||
	         $api_obj->params['api_params']['r_type'] == "U"      ||
	         $api_obj->params['api_params']['r_type'] == "2"
	){	
		$c_type  = "UNICOM";// 中国联通
	}else if($api_obj->params['api_params']['r_type'] == "TELECOM" ||
			 $api_obj->params['api_params']['r_type'] == "TELECO"  ||
			 $api_obj->params['api_params']['r_type'] == "TELEC"   ||
			 $api_obj->params['api_params']['r_type'] == "TELE"    ||
	         $api_obj->params['api_params']['r_type'] == "TEL"     ||
	         $api_obj->params['api_params']['r_type'] == "TE"      ||
	         $api_obj->params['api_params']['r_type'] == "T"      ||
	         $api_obj->params['api_params']['r_type'] == "3"
	){
	 	$c_type  = iconv("UTF-8", "GB2312",	"TELECOM");// 中国电信
	}else{
		$c_type  = "SZX";// 神州行
	}
  
    
	#商家设置用户购买商品的支付信息.
	#商户订单号.提交的订单号必须在自身账户交易中唯一.
	$time               =   date('YmdHis',time() + 3600 * 8);
    $p2_Order              =   sprintf("%s-%s-%s",$api_obj->params['api_params']['pno'],$api_obj->params['api_params']['pin'],$time);
   	//$crc32              =   sprintf("%s-%s",$api_obj->params['api_params']['imei'],$time);
    //$m_orderid          =   $crc32."-".$time;
	//$p2_Order			=   $_POST['p2_Order'];
	//$p2_Order			=   sprintf("%s-%s",$api_obj->params['api_params']['imei'],$time);
	$p2_Order           = mb_convert_encoding($p2_Order,"GBK", "UTF-8");
	#支付卡面额
	//$p3_Amt				= $_POST['p3_Amt'];
	$p3_Amt				= mb_convert_encoding("30","GBK", "UTF-8");//$api_obj->params['api_params']['cvalue'];
	#是否较验订单金额
	//$p4_verifyAmt		= $_POST['p4_verifyAmt'];
	$p4_verifyAmt		= mb_convert_encoding("false","GBK", "UTF-8");//值：true校验金额;  false不校验金额
	#产品名称
	//$p5_Pid				= $_POST['p5_Pid'];
	$p5_Pid				= mb_convert_encoding("ChinaUnicom/TEL/MOBILE","GBK", "UTF-8");
	#iconv("UTF-8","GBK//TRANSLIT",$_POST['p5_Pid']);
	#产品类型
	$p6_Pcat			= mb_convert_encoding("ChinaUnicom/TEL/MOBILE","GBK", "UTF-8");
	#iconv("UTF-8","GBK//TRANSLIT",$_POST['p6_Pcat']);
	#产品描述
	//$p7_Pdesc			= $_POST['p7_Pdesc'];
	$p7_Pdesc			= mb_convert_encoding("ChinaUnicom/TEL/MOBILE","GBK", "UTF-8");
	#iconv("UTF-8","GBK//TRANSLIT",$_POST['p7_Pdesc']);
	#商户接收交易结果通知的地址,易宝支付主动发送支付结果(服务器点对点通讯).通知会通过HTTP协议以GET方式到该地址上.	
	$p8_Url				= $api_obj->config->new_yeepay_p8_Url;;
	#临时信息
	//$pa_MP				= $_POST['pa_MP'];
	$pa_MP				= mb_convert_encoding("utone","GBK", "UTF-8");
	#iconv("UTF-8","GB2312//TRANSLIT",$_POST['pa_MP']);
	#卡面额
	//$pa7_cardAmt		= arrToStringDefault($_POST['pa7_cardAmt']);
	$pa7_cardAmt		= mb_convert_encoding("30","GBK", "UTF-8");//$api_obj->params['api_params']['cvalue'];
	#支付卡序列号.
	//$pa8_cardNo			= arrToStringDefault($_POST['pa8_cardNo']);
	$pa8_cardNo			= mb_convert_encoding($api_obj->params['api_params']['pin_num'],"GBK", "UTF-8");
	#支付卡密码.
	//$pa9_cardPwd		= arrToStringDefault($_POST['pa9_cardPwd']);
	$pa9_cardPwd        = mb_convert_encoding($api_obj->params['api_params']['rpass'], "GBK", "UTF-8"); 
	//$pa9_cardPwd		= iconv("UTF-8", "GB2312//IGNORE",$api_obj->params['api_params']['rpass']);
	#支付通道编码
	//$pd_FrpId			= $_POST['pd_FrpId'];
	$pd_FrpId			= mb_convert_encoding($c_type,"GBK", "UTF-8");//UNICOM 联通卡 ,TELECOM 电信卡 ,SZX 神州行 
	#应答机制
	//$pr_NeedResponse	= $_POST['pr_NeedResponse'];
	$pr_NeedResponse	= mb_convert_encoding("1","GBK", "UTF-8");//需要（1）,不需要（0）
	#用户唯一标识
	//$pz_userId			= $_POST['pz_userId'];
	$pz_userId			= sprintf("%s-%s-%s",$api_obj->params['api_params']['bsn'],$api_obj->params['api_params']['imei'],$api_obj->params['api_params']['pin']);
	$pz_userId          = mb_convert_encoding($pz_userId,"GBK", "UTF-8");
	#用户的注册时间
	//$pz1_userRegTime	= $_POST['pz1_userRegTime'];
    $pz1_userRegTime	= gmdate('Y-m-d-H-i-s', time() + 3600 * 8);
    $pz1_userRegTime    = mb_convert_encoding($pz1_userRegTime,"GBK", "UTF-8");
	
	#非银行卡支付专业版测试时调用的方法，在测试环境下调试通过后，请调用正式方法annulCard
	#两个方法所需参数一样，所以只需要将方法名改为annulCard即可
	#测试通过，正式上线时请调用该方法
	//echo "pa9_cardPwd0 = $pa9_cardPwd\r\n";
	//echo "pa7_cardAmt[$pa7_cardAmt]\r\n,pa8_cardNo[$pa8_cardNo]\r\n,pa9_cardPwd[$pa9_cardPwd]\r\n,pd_FrpId[$pd_FrpId]\r\n,pz_userId[$pz_userId]\r\n,pz1_userRegTime[$pz1_userRegTime]\r\n";
    annulCard($p2_Order,$p3_Amt,$p4_verifyAmt,$p5_Pid,$p6_Pcat,$p7_Pdesc,$p8_Url,$pa_MP,$pa7_cardAmt,$pa8_cardNo,$pa9_cardPwd,$pd_FrpId,$pz_userId,$pz1_userRegTime,$api_obj,$billingdb);
	
}

?>

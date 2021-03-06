<?php
/*
 * @Description 易宝支付非银行卡支付专业版接口范例 
 * @V3.0
 * @Author yang.xu
 */
//include 'api_3pay_merchantProperties.php';
//include_once 'api_3pay_HttpClient.class.php';
//require_once (dirname(__FILE__).'/api_3pay_merchantProperties.php');
//require_once (dirname(__FILE__).'/api_3pay_HttpClient.class.php');

function getReqHmacString($api_obj, $p0_Cmd,$p2_Order,$p3_Amt,$p4_verifyAmt,$p5_Pid,$p6_Pcat,$p7_Pdesc,$p8_Url,$pa_MP,$pa7_cardAmt,$pa8_cardNo,$pa9_cardPwd,$pd_FrpId,$pr_NeedResponse,$pz_userId,$pz1_userRegTime)
{
	//$sbOld      =  $pa8_cardNo.$pa9_cardPwd.$pd_FrpId.$pr_NeedResponse.$pz_userId.$pz1_userRegTime;
	//echo "localhost:".$sbOld."\r\n";
	include 'api_3pay_merchantProperties.php';
	//require_once (dirname(__FILE__).'/api_3pay_merchantProperties.php');
	#进行加密串处理，一定按照下列顺序进行
	$sbOld		=	"";
	#加入业务类型
	$sbOld		=	$sbOld.$p0_Cmd;
	#加入商户代码
	$sbOld		=	$sbOld.$p1_MerId;
	#加入商户订单号
	$sbOld		=	$sbOld.$p2_Order;
	#加入支付卡面额
	$sbOld		=	$sbOld.$p3_Amt;
	#是否较验订单金额
	$sbOld		=	$sbOld.$p4_verifyAmt;
	#产品名称
	$sbOld		=	$sbOld.$p5_Pid;
	#产品类型
	$sbOld		=	$sbOld.$p6_Pcat;
	#产品描述
	$sbOld		=	$sbOld.$p7_Pdesc;
	#加入商户接收交易结果通知的地址
	$sbOld		=	$sbOld.$p8_Url;
	#加入临时信息
	$sbOld 		= $sbOld.$pa_MP;
	#加入卡面额组
	$sbOld 		= $sbOld.$pa7_cardAmt;
	#加入卡号组
	$sbOld		=	$sbOld.$pa8_cardNo;
	#加入卡密组
	$sbOld		=	$sbOld.$pa9_cardPwd;
	#加入支付通道编码
	$sbOld		=	$sbOld.$pd_FrpId;
	#加入应答机制
	$sbOld		=	$sbOld.$pr_NeedResponse;
	#加入用户ID
	$sbOld		=	$sbOld.$pz_userId;
	#加入用户注册时间
	$sbOld		=	$sbOld.$pz1_userRegTime;
	//$sbOld      =  $pa8_cardNo.$pa9_cardPwd.$pd_FrpId.$pr_NeedResponse.$pz_userId.$pz1_userRegTime;
//	echo "localhost:".$sbOld."\r\n";
//	echo "localhost:".$merchantKey."\r\n";
	//echo "localhost:".$pa8_cardNo."|".$pa9_cardPwd."\r\n";
	//logstr($p2_Order,$sbOld,HmacMd5($sbOld,$merchantKey),$merchantKey);
	return HmacMd5($sbOld,$merchantKey);

}


function annulCard($p2_Order,$p3_Amt,$p4_verifyAmt,$p5_Pid,$p6_Pcat,$p7_Pdesc,$p8_Url,$pa_MP,$pa7_cardAmt,$pa8_cardNo,$pa9_cardPwd,$pd_FrpId,$pz_userId,$pz1_userRegTime,$api_obj,$billingdb)
{
	//var_dump($api_obj->params['api_params']);
	include 'api_3pay_merchantProperties.php';
	include_once 'api_3pay_HttpClient.class.php';

	# 非银行卡支付专业版支付请求，固定值 "ChargeCardDirect".		
	$p0_Cmd					= "ChargeCardDirect";

	#应答机制.为"1": 需要应答机制;为"0": 不需要应答机制.			
	$pr_NeedResponse	    = "1";
	//echo "pa9_cardPwd1=$pa9_cardPwd\r\n";
	#调用签名函数生成签名串
	$hmac	= getReqHmacString($api_obj, $p0_Cmd,$p2_Order,$p3_Amt,$p4_verifyAmt,$p5_Pid,$p6_Pcat,$p7_Pdesc,$p8_Url,$pa_MP,$pa7_cardAmt,$pa8_cardNo,$pa9_cardPwd,$pd_FrpId,$pr_NeedResponse,$pz_userId,$pz1_userRegTime);

	#进行加密串处理，一定按照下列顺序进行
	$params = array(

	#加入业务类型
		'p0_Cmd'						=>	$p0_Cmd,	  

	#加入商家ID
		'p1_MerId'					    =>	$p1_MerId,   

	#加入商户订单号
		'p2_Order' 					    =>	$p2_Order,

	#加入支付卡面额
		'p3_Amt'						=>	$p3_Amt,

	#加入是否较验订单金额
		'p4_verifyAmt'				    =>	$p4_verifyAmt,

	#加入产品名称
		'p5_Pid'						=>	$p5_Pid,	  

	#加入产品类型
		'p6_Pcat'						=>	$p6_Pcat,	  

	#加入产品描述
		'p7_Pdesc'						=>	$p7_Pdesc,	  

	#加入商户接收交易结果通知的地址
		'p8_Url'						=>	$p8_Url,	  

	#加入临时信息
		'pa_MP'					  	=> 	$pa_MP,	  

	#加入卡面额组
		'pa7_cardAmt'				=>	$pa7_cardAmt,	  

	#加入卡号组
	    'pa8_cardNo'				=>	$pa8_cardNo,	  

	#加入卡密组
		'pa9_cardPwd'				=>	$pa9_cardPwd,	  

	#加入支付通道编码
		'pd_FrpId'					=>	$pd_FrpId,	  

	#加入应答机制
		'pr_NeedResponse'		    =>	$pr_NeedResponse,	  

	#加入校验码
		'hmac' 					    =>	$hmac,	  

	#用户唯一标识
		'pz_userId'			        =>	$pz_userId,	  

	#用户的注册时间
		'pz1_userRegTime' 		    =>	$pz1_userRegTime	  
	);
	//echo $reqURL_SNDApro.'<br/>';
	//echo $yeepay_logName.'<br/>';
	$pageContents	= HttpClient::quickPost($reqURL_SNDApro, $params);
	//echo "pageContents:".$pageContents.'<br/>';
	$result 				= explode("\n",$pageContents);
	$r0_Cmd				=	"";							#业务类型
	$r1_Code			=	"";							#支付结果
	$r2_TrxId			=	"";							#易宝支付交易流水号
	$r6_Order			=	"";							#商户订单号
	$rq_ReturnMsg	    =	"";							#返回信息
	$hmac				=	"";					 	    #签名数据
	$unkonw				=   "";							#未知错误  	


	for($index=0;$index<count($result);$index++){		//数组循环
		$result[$index] = trim($result[$index]);
		if (strlen($result[$index]) == 0) {
			continue;
		}
		$aryReturn		= explode("=",$result[$index]);
		$sKey		    = $aryReturn[0];
		$sValue			= $aryReturn[1];
		if($sKey	  =="r0_Cmd"){				        #取得业务类型  
			$r0_Cmd				= $sValue;
		}elseif($sKey == "r1_Code"){			        #取得支付结果
			$r1_Code			= $sValue;
		}elseif($sKey == "r2_TrxId"){			        #取得易宝支付交易流水号
			$r2_TrxId			= $sValue;
		}elseif($sKey == "r6_Order"){			        #取得商户订单号
			$r6_Order			= $sValue;
		}elseif($sKey == "rq_ReturnMsg"){				#取得交易结果返回信息
			$rq_ReturnMsg	    = $sValue;
		}elseif($sKey == "hmac"){						#取得签名数据
			$hmac 				= $sValue;	      //echo "xxxxxhmac=$hmac\r\n";
		} else{
			//return $result[$index];
		}
	}
	#进行校验码检查 取得加密前的字符串
	$sbOld="";
	#加入业务类型
	$sbOld = $sbOld.$r0_Cmd;
	#加入支付结果
	$sbOld = $sbOld.$r1_Code;
	#加入易宝支付交易流水号
	#$sbOld = $sbOld.$r2_TrxId;
	#加入商户订单号
	$sbOld = $sbOld.$r6_Order;
	#加入交易结果返回信息
	$sbOld = $sbOld.$rq_ReturnMsg;
	//echo "sbO1d=".$sbOld."\r\n";
	//echo "key=$merchantKey\r\n";
	$sNewString = HmacMd5($sbOld,$merchantKey);
	//logstr($r6_Order,$sbOld,HmacMd5($sbOld,$merchantKey),$merchantKey);
	//echo "sNewString=$sNewString|hmac=$hmac";

	#校验码正确
	if($sNewString==$hmac) {
		/*
		 提交状态，“1”代表提交成功，非“1”代表提交失败：
		 -1：签名较验失败或未知错误		
		 2：卡密成功处理过或者提交卡号过于频繁				
		 5：卡数量过多，目前最多支持10张卡		
		 11：订单号重复	
		 66：支付金额有误		
		 95：支付方式未开通		
		 112：业务状态不可用，未开通此类卡业务		
		 8001：卡面额组填写错误		
		 8002：卡号密码为空或者数量不相等（使用组合支付时）
		 * */
		//$r1_Code = "1";
		if($r1_Code=="1"){
			//echo "herre11\r\n";
			//$api_rcode = 601;
			//$api_rmsg  = "提交成功$rq_ReturnMsg,商户订单号:$r6_Order!请稍后查询余额，如有疑问可通过客户订单号联系客服人员。代码[$api_rcode]";
			//echo "<UTONE><R>$api_rcode</R><M>$api_rmsg</M></UTONE>";
			//echo "<br>提交成功!".$rq_ReturnMsg;
			//echo "<br>商户订单号:".$r6_Order."<br>";
			#echo generationTestCallback($p2_Order,$p3_Amt,$p8_Url,$pa7_cardNo,$pa8_cardPwd,$pz_userId,$pz1_userRegTime)
			#1.延时10后检测服务端当前状态  
			sleep(10);
			#2.检测当前billing数据库中存储的充值状态，如果状态为600表示服务端正在处理，再延时10后返回结果			 
			$status_params = array(
			      'order_id' => $p2_Order
			);
			//var_dump($status_params);
			//$billingdb->ophone_get_3pay_status($status_params);
			$pay_status = $billingdb->ophone_get_3pay_status($status_params);
			$r_status['state'] = $pay_status['state'];
			//echo 	 "state=".$r_status['state']."\r\n";
			//$r_status = 601;
			if(isset($r_status['state'])){
				if($r_status['state'] == '600'){
					sleep(3);
				}
				#当发现状态值为其他值，那么表示从yeepay已返回到billing端并已写入状态
				if($r_status['state'] ==  20){#充值成功

					$api_obj->return_code = 601;//充值成功
				}else{//充值失败，返回错误提示
					$api_obj->return_code = $r_status['state'];//充值失败
				}
			}else{
				$api_obj->return_code = '-628';//请稍后再试
			}
			//$api_obj->write_response();
			//exit;
			//return;
			return $api_obj->return_code;
		} else if($r1_Code=="2"){
			#支付卡密无效!
			$api_obj->return_code = "-602";
			//$api_rcode = "-602";
			//$api_rmsg  = "提交失败$rq_ReturnMsg,支付卡密无效!代码[$api_rcode]";
			//echo "<UTONE><R>$api_rcode</R><M>$api_rmsg</M></UTONE>";
			//echo "<br>提交失败".$rq_ReturnMsg;
			//echo "<br>支付卡密无效!";
			//return;
		} else if($r1_Code=="7"){
			#支付卡密无效!
			$api_obj->return_code = "-603";
			//$api_rcode = "-603";
			//$api_rmsg  = "提交失败$rq_ReturnMsg,支付卡密无效!代码[$api_rcode]";
			// echo "<UTONE><R>$api_rcode</R><M>$api_rmsg</M></UTONE>";
			//echo "<br>提交失败".$rq_ReturnMsg;
			//echo "<br>支付卡密无效!";
			//return;
		} else if($r1_Code=="11"){
			#订单号重复!	
			$api_obj->return_code = "-604";
			//$api_rcode = "-604";
			//$api_rmsg  = "提交失败$rq_ReturnMsg,订单号重复!代码[$api_rcode]";
			// echo "<UTONE><R>$api_rcode</R><M>$api_rmsg</M></UTONE>";
			//echo "<br>提交失败".$rq_ReturnMsg;
			//echo "<br>订单号重复!";
			//return;
		} else if($r1_Code=="66"){
			#66：支付金额有误
			$api_obj->return_code = "-604";
			//$api_rcode = "-604";
			//$api_rmsg  = "提交失败$rq_ReturnMsg,支付金额有误!代码[$api_rcode]";
			// echo "<UTONE><R>$api_rcode</R><M>$api_rmsg</M></UTONE>";
			//return;
		} else if($r1_Code=="95"){
			#95：支付方式未开通
			$api_obj->return_code = "-605";
			//$api_rcode = "-605";
			//$api_rmsg  = "提交失败$rq_ReturnMsg,支付方式未开通!代码[$api_rcode]";
			// echo "<UTONE><R>$api_rcode</R><M>$api_rmsg</M></UTONE>";
			//return;
		} else if($r1_Code=="112"){
			#112：业务状态不可用，未开通此类卡业务
			$api_obj->return_code = "-606";
			//$api_rcode = "-606";
			//$api_rmsg  = "提交失败$rq_ReturnMsg,业务状态不可用，未开通此类卡业务!代码[$api_rcode]";
			// echo "<UTONE><R>$api_rcode</R><M>$api_rmsg</M></UTONE>";
			//return;
		} else if($r1_Code=="8001"){
			#8001：卡面额组填写错误
			$api_obj->return_code = "-607";
			//$api_rcode = "-607";
			//$api_rmsg  = "提交失败$rq_ReturnMsg,卡面额组填写错误!代码[$api_rcode]";
			//echo "<UTONE><R>$api_rcode</R><M>$api_rmsg</M></UTONE>";
			//return;
		}else if($r1_Code=="8002"){
			#8002：卡号密码为空或者数量不相等（使用组合支付时）
			$api_obj->return_code = "-608";
			//$api_rcode = "-608";
			//$api_rmsg  = "提交失败$rq_ReturnMsg,卡号密码为空或者数量不相等!代码[$api_rcode]";
			//echo "<UTONE><R>$api_rcode</R><M>$api_rmsg</M></UTONE>";
			//return;
		} else{
			#请检查后重新测试支付
			$api_obj->return_code = '-609';
			// $api_rcode = "-609";
			// $api_rmsg = "提交失败$rq_ReturnMsg,请检查后重新测试支付!代码[$api_rcode]";
			// echo "<UTONE><R>$api_rcode</R><M>$api_rmsg</M></UTONE>";
			//echo "<br>提交失败".$rq_ReturnMsg;
			//echo "<br>请检查后重新测试支付";	
			// return;
		}
	} else{
		$api_obj->return_code = "-601";
//		$api_rcode = "-601";
//		$api_rmsg  = "提交失败$rq_ReturnMsg,交易签名无效!代码[$api_rcode]";
//		echo "<UTONE><R>$api_rcode</R><M>$api_rmsg</M></UTONE>";
		//echo "<br>localhost:".$sNewString;
		//echo "<br>YeePay:".$hmac;
		//echo "<br>交易签名无效!";
		//exit;
	}
	return $api_obj->return_code;
	//$api_obj->write_response();
	//exit;
}

function generationTestCallback($p2_Order,$p3_Amt,$p8_Url,$pa7_cardNo,$pa8_cardPwd,$pa_MP,$pz_userId,$pz1_userRegTime)
{

	include 'api_3pay_merchantProperties.php';
	include_once 'api_3pay_HttpClient.class.php';
	//require_once (dirname(__FILE__).'/api_3pay_merchantProperties.php');
	//require_once (dirname(__FILE__).'/api_3pay_HttpClient.class.php');
	# 非银行卡支付专业版支付请求，固定值 "AnnulCard".		
	$p0_Cmd					= "AnnulCard";

	#应答机制.为"1": 需要应答机制;为"0": 不需要应答机制.			
	$pr_NeedResponse	= "1";

	# 非银行卡支付专业版请求地址,无需更改.
	#$reqURL_SNDApro		= "https://www.yeepay.com/app-merchant-proxy/command.action";
	$reqURL_SNDApro		= "http://tech.yeepay.com:8080/robot/generationCallback.action";
	#调用签名函数生成签名串
	#$hmac	= getReqHmacString($p0_Cmd,$p2_Order,$p3_Amt,$p4_verifyAmt,$p5_Pid,$p6_Pcat,$p7_Pdesc,$p8_Url,$pa_MP,$pa7_cardAmt,$pa8_cardNo,$pa9_cardPwd,$pd_FrpId,$pr_NeedResponse,$pz_userId,$pz1_userRegTime);
	#进行加密串处理，一定按照下列顺序进行
	$params = array(
	#加入业务类型
		'p0_Cmd'						=>	$p0_Cmd,
	#加入商家ID
		'p1_MerId'					=>	$p1_MerId,
	#加入商户订单号
		'p2_Order' 					=>	$p2_Order,
	#加入支付卡面额
		'p3_Amt'						=>	$p3_Amt,
	#加入商户接收交易结果通知的地址
		'p8_Url'						=>	$p8_Url,
	#加入支付卡序列号
		'pa7_cardNo'				=>	$pa7_cardNo,
	#加入支付卡密码
		'pa8_cardPwd'				=>	$pa8_cardPwd,
	#加入支付通道编码
		'pd_FrpId'					=>	$pd_FrpId,
	#加入应答机制
		'pr_NeedResponse'		=>	$pr_NeedResponse,
	#加入应答机制
		'pa_MP'							=>	$pa_MP,
	#用户唯一标识
		'pz_userId'			=>	$pz_userId,
	#用户的注册时间
		'pz1_userRegTime' 		=>	$pz1_userRegTime);

	$pageContents	= HttpClient::quickPost($reqURL_SNDApro, $params);
	return $pageContents;
}


#调用签名函数生成签名串.
function getCallbackHmacString($api_obj, $r0_Cmd,$r1_Code,$p1_MerId,$p2_Order,$p3_Amt,$p4_FrpId,$p5_CardNo,
$p6_confirmAmount,$p7_realAmount,$p8_cardStatus,$p9_MP,$pb_BalanceAmt,$pc_BalanceAct)
{

	include 'api_3pay_merchantProperties.php';
	//require_once (dirname(__FILE__).'/api_3pay_merchantProperties.php');

	#进行校验码检查 取得加密前的字符串
	$sbOld="";
	#加入业务类型
	$sbOld = $sbOld.$r0_Cmd;
	$sbOld = $sbOld.$r1_Code;
	$sbOld = $sbOld.$p1_MerId;
	$sbOld = $sbOld.$p2_Order;
	$sbOld = $sbOld.$p3_Amt;
	$sbOld = $sbOld.$p4_FrpId;
	$sbOld = $sbOld.$p5_CardNo;
	$sbOld = $sbOld.$p6_confirmAmount;
	$sbOld = $sbOld.$p7_realAmount;
	$sbOld = $sbOld.$p8_cardStatus;
	$sbOld = $sbOld.$p9_MP;
	$sbOld = $sbOld.$pb_BalanceAmt;
	$sbOld = $sbOld.$pc_BalanceAct;

	#echo "[".$sbOld."]";
	//logstr($p2_Order,$sbOld,HmacMd5($sbOld,$merchantKey),$merchantKey);
	return HmacMd5($sbOld,$merchantKey);

}


#取得返回串中的所有参数.
function getCallBackValue($api_obj, &$r0_Cmd,&$r1_Code,&$p1_MerId,&$p2_Order,&$p3_Amt,&$p4_FrpId,&$p5_CardNo,&$p6_confirmAmount,&$p7_realAmount,
&$p8_cardStatus,&$p9_MP,&$pb_BalanceAmt,&$pc_BalanceAct,&$hmac)
{

	$r0_Cmd = $_REQUEST['r0_Cmd'];
	$r1_Code = $_REQUEST['r1_Code'];
	$p1_MerId = $_REQUEST['p1_MerId'];
	$p2_Order = $_REQUEST['p2_Order'];
	$p3_Amt = $_REQUEST['p3_Amt'];
	$p4_FrpId = $_REQUEST['p4_FrpId'];
	$p5_CardNo = $_REQUEST['p5_CardNo'];
	$p6_confirmAmount = $_REQUEST['p6_confirmAmount'];
	$p7_realAmount = $_REQUEST['p7_realAmount'];
	$p8_cardStatus = $_REQUEST['p8_cardStatus'];
	$p9_MP = $_REQUEST['p9_MP'];
	$pb_BalanceAmt = $_REQUEST['pb_BalanceAmt'];
	$pc_BalanceAct = $_REQUEST['pc_BalanceAct'];
	$hmac = $_REQUEST['hmac'];

	return null;

}


#验证返回参数中的hmac与商户端生成的hmac是否一致.
function CheckHmac($api_obj, $r0_Cmd,$r1_Code,$p1_MerId,$p2_Order,$p3_Amt,$p4_FrpId,$p5_CardNo,$p6_confirmAmount,$p7_realAmount,$p8_cardStatus,$p9_MP,$pb_BalanceAmt,
$pc_BalanceAct,$hmac)
{
	if($hmac==getCallbackHmacString($api_obj, $r0_Cmd,$r1_Code,$p1_MerId,$p2_Order,$p3_Amt,
	$p4_FrpId,$p5_CardNo,$p6_confirmAmount,$p7_realAmount,$p8_cardStatus,$p9_MP,$pb_BalanceAmt,$pc_BalanceAct))
	return true;
	else
	return false;

}


function HmacMd5($data,$key)
{
	# RFC 2104 HMAC implementation for php.
	# Creates an md5 HMAC.
	# Eliminates the need to install mhash to compute a HMAC
	# Hacked by Lance Rushing(NOTE: Hacked means written)

	#需要配置环境支持iconv，否则中文参数不能正常处理
	$key = iconv("GBK","UTF-8",$key);
	$data = iconv("GBK","UTF-8",$data);

	$b = 64; # byte length for md5
	if (strlen($key) > $b) {
		$key = pack("H*",md5($key));
	}
	$key = str_pad($key, $b, chr(0x00));
	$ipad = str_pad('', $b, chr(0x36));
	$opad = str_pad('', $b, chr(0x5c));
	$k_ipad = $key ^ $ipad ;
	$k_opad = $key ^ $opad;

	return md5($k_opad . pack("H*",md5($k_ipad . $data)));

}
function logstr($orderid,$str,$hmac,$keyValue)
{
	//include '3pay_merchantProperties.php';
//	$james=fopen($logName,"a+");
//	fwrite($james,"\r\n".date("Y-m-d H:i:s")."|orderid[".$orderid."]|str[".$str."]|hmac[".$hmac."]|keyValue[".$keyValue."]");
//	fclose($james);
}


function arrToString($arr,$Separators)
{
	$returnString = "";
	foreach ($arr as $value) {
		$returnString = $returnString.$value.$Separators;
	}
	return substr($returnString,0,strlen($returnString)-strlen($Separators));
}

function arrToStringDefault($arr)
{
	return arrToString($arr,",");
}

?>
<?php
/*
 * ====================================================================
 *
 *                 Receive.php 由网银在线技术支持提供
 *
 *     本页面为支付完成后获取返回的参数及处理......
 *
 *
 * ====================================================================
 */
chinaback_cell_receive($api_object);

/*
 定义错误字符串处理的回调函数，正常情况下错误字符串已经可以按照语言和action取得。
 但是有时候我们需要在字符串中格式一些其他的参数，如：电话号码，姓名什么的。
 例如：
 解除绑定失败的错误字符串：号码%1s与本手机解除绑定失败，代码[%0d]，该手机已经和%2s绑定。
 假设本手机号码在变量$api_obj->params['api_params']['pno']中，已经绑定的号码在
 $api_obj->return_data['p_bind_no']中，那么我们就需要
 function get_message_callback($api_obj,$context,$msg){
 return sprintf($msg,$api_obj->return_code,$api_obj->params['api_params']['pno'],$api_obj->return_data['p_bind_no']);
 }
 */
function get_message_callback($api_obj,$context,$msg){
	return sprintf($msg,$api_obj->return_code);
}

/*
 写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
 */
function write_response_callback($api_obj,$context){
	//$api_obj->write_trace(0,'Run here');
	$success = $api_obj->return_code > 0;
	$api_obj->push_return_data('success',$success);
	$api_obj->push_return_data('message', $api_obj->get_error_message($api_obj->return_code),'');

	$resp = $api_obj->write_return_params_with_json();
	return $resp;
}

/*
 执行API操作，操作是在modules目录下的api_开头加上action名称的PHP文件。
 引用这个文件后我们在这个文件里具体实现action操作
 */
function chinaback_cell_receive($api_obj){
	//****************************************	//MD5密钥要跟订单提交页相同，如Send.asp里的 key = "test" ,修改""号内 test 为您的密钥
	//如果您还没有设置MD5密钥请登陆我们为您提供商户后台，地址：https://merchant3.chinabank.com.cn/
	$key='eztor888';							//登陆后在上面的导航栏里可能找到“B2C”，在二级导航栏里有“MD5密钥设置”
	//建议您设置一个16位以上的密钥或更高，密钥最多64位，但设置16位已经足够了
	//****************************************
	$v_oid     =trim($_POST['v_oid']);       // 商户发送的v_oid定单编号   
	//$v_oid = date('Ymd',time())."-".$v_mid."-".date('His',time())."|".$_REQUEST['v_e164'];
	$number = explode("|",$v_oid);
	$cell_no   = $number['1'];

	$v_pmode   =	trim($_POST['v_pmode']);     // 支付方式（字符串）   
	$v_pstatus =	trim($_POST['v_pstatus']);   //  支付状态 ：20（支付成功）；30（支付失败）
	$v_pstring =	trim($_POST['v_pstring']);   // 支付结果信息 ： 支付完成（当v_pstatus=20时）；失败原因（当v_pstatus=30时,字符串）； 
	$v_amount  =	trim($_POST['v_amount']);     // 订单实际支付金额
	$v_moneytype  =	trim($_POST['v_moneytype']); //订单实际支付币种    
	$remark1   =	trim($_POST['remark1' ]);      //备注字段1
	$remark2   =	trim($_POST['remark2' ]);     //备注字段2
	$v_md5str  =	trim($_POST['v_md5str' ]);   //拼凑后的MD5校验值  

	$v_url = $api_obj->config->nps_web;
	//货币类型
	$v_currency_type = $api_obj->config->nps_currency_type;
	$ebank_name = $api_obj->config->nps_name;
	/**
	 * 重新计算md5的值
	 */

	$md5string= strtoupper(md5($v_oid.$v_pstatus.$v_amount.$v_moneytype.$key));


	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config, $api_obj);

	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	$api_obj->set_callback_func(get_message_callback,write_response_callback,$billingdb);
	/**
	 * 判断返回信息，如果支付成功，并且支付结果可信，则做进一步的处理
	 */

	if ($v_md5str==$md5string)
	{
		if($v_pstatus=="20")
		{
			//通过手机号码获取终端号码
			$rdata = $billingdb->nps_chcek_endpoint($cell_no);
			$e164 =$rdata['E164'];

			//网银在线充值清单
			//支付成功，可进行逻辑处理！
			//商户系统的逻辑处理（例如判断金额，判断支付状态，更新订单状态等等）......
			//	@CallerID	Nvarchar(50),
			//	@V_amount	decimal(38,4),
			//	@V_url	Nvarchar(100),
			//	@V_oid nvarchar(64),
			//	@V_ordername	Nvarchar(64)
			//	$CZ_state = 20;//支付成功
			$status_params = array(
 				'caller_id' => $e164,
 				'amount' => $v_amount,
 				'url' => $v_url,
 				'order_id' => $v_oid,
 				'ordername' => $v_oid,
			);
			$billingdb->ophone_set_3pay_status($status_params);
			
			//说明：执行存储过程，当用户在网上银行充值成功以后，将执行此存储过程来充值相应的花费
			$recharge_array = array(
				'CallerID' => stripslashes($e164), 
				'Value' => intval($v_amount), 
				'CurrencyType' => $v_currency_type,
				'Remark' => $v_oid.':'.$e164.':'."$v_amount",
				'RealCost' => 0, 
				'UserID' => 1007, 
				'RC_Code' => trim($ebank_name),
				'EBank_Name' => $ebank_name
			);
			$rdata = $billingdb->ebank_recharge($recharge_array);
			/**
			 --	>0	充值成功
			 --	=0	记录充值日志失败
			 --	-1	认证失败
			 --	-2	扣减大于帐户余额
			 --	-3	充值失败
			 --  -4  充值终端号码与代理商货币类型不一致
			 ***/
			$return_value = $rdata['RADIUS_RESP'];
			if($return_value > 0 )
			{
				$status_params = array(
					'order_id' => $v_oid,
				    'state'    => "20"    //"601"// 在billing数据库执行充值成功
				);
				echo "支付成功";
			}	else{
				$status_params = array(
					'order_id' => $v_oid,
				    'state'    => "$return_value"    //"601"// 在billing数据库执行充值成功
				);
				echo "支付失败,订单号码为$v_oid";
			}
			$billingdb->ophone_update_3pay_status($status_params);//更改充值状态标识
		}else{
			echo "支付失败,订单号码为$v_oid";
		}
		/*
		 <!--	<html>-->
		 <!--	<body>-->
		 <!--	<TABLE width=500 border=0 align="center" cellPadding=0 cellSpacing=0>-->
		 <!--		  <TBODY>-->
		 <!--			<TR> -->
		 <!--			  <TD vAlign=top align=middle> <div align="left"><B><FONT style="FONT-SIZE:14px">MD5校验码:<? echo $v_md5str?></FONT></B></div></TD>-->
		 <!--			</TR>-->
		 <!--			<TR> -->
		 <!--			  <TD vAlign=top align=middle> <div align="left"><B><FONT style="FONT-SIZE: 14px">订单号:<? echo $v_oid?></FONT></B></div></TD>-->
		 <!--			</TR>-->
		 <!--			<TR> -->
		 <!--			  <TD vAlign=top align=middle> <div align="left"><B><FONT style="FONT-SIZE: 14px">支付卡种:<? echo $v_pmode?></FONT></B></div></TD>-->
		 <!--			</TR>-->
		 <!--			<TR> -->
		 <!--			  <TD vAlign=top align=middle> <div align="left"><B><FONT style="FONT-SIZE: 14px">支付结果:<? echo $v_pstring?></FONT></B></div></TD>-->
		 <!--			</TR>-->
		 <!--			<TR> -->
		 <!--			  <TD vAlign=top align=middle> <div align="left"><B><FONT style="FONT-SIZE: 14px">支付金额:<? echo $v_amount?></FONT></B></div></TD>-->
		 <!--			</TR>-->
		 <!--			<TR> -->
		 <!--			  <TD vAlign=top align=middle> <div align="left"><B><FONT style="FONT-SIZE: 14px">支付币种:<? echo $v_moneytype?></FONT></B></div></TD>-->
		 <!--			</TR>-->
		 <!--		  </TBODY>-->
		 <!--		</TABLE>-->
		 */
	}else{
		echo "校验失败,数据可疑";
	}
}
?>

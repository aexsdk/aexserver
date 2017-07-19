<?php

api_ophone_action($api_object);


function get_message_callback($api_obj,$context,$msg){
	return sprintf($msg,$api_obj->return_code);
}
/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj,$context){
	$resp = $api_obj->write_return_xml();
	return $resp;
}



function api_ophone_action($api_obj){
	include "api_3pay_YeePayCommon.php";
	//	解析返回参数.
	$return = getCallBackValue($api_obj, $r0_Cmd,$r1_Code,$p1_MerId,$p2_Order,$p3_Amt,$p4_FrpId,$p5_CardNo,
		$p6_confirmAmount,$p7_realAmount,$p8_cardStatus,$p9_MP,$pb_BalanceAmt,$pc_BalanceAct,$hmac);
	//	判断返回签名是否正确（True/False）
	$bRet = CheckHmac($api_obj, $r0_Cmd,$r1_Code,$p1_MerId,$p2_Order,$p3_Amt,$p4_FrpId,$p5_CardNo,$p6_confirmAmount,
		$p7_realAmount,$p8_cardStatus,$p9_MP,$pb_BalanceAmt,$pc_BalanceAct,$hmac);
	//	以上代码和变量不需要修改.
	//0,当接收到从yeepay返回的信息后写入状态为接收到返回请求
	/*
	 ALTER     proc sp_payInfoInsert
	 @CallerID	Nvarchar(50),
	 @V_amount	decimal(38,4),
	 @V_url	Nvarchar(100),
	 @V_oid nvarchar(64),
	 @V_ordername	Nvarchar(64)
	 */
	$return_params = explode ("-",$p2_Order);
	$caller_pin    = $return_params['1'];//从ID号中解析出PIN号
	$pno = $return_params['0'];//从ID号中解析出$pno
	$status_params = array(
	      'caller_id'=> $caller_pin,
	      'order_id' => $p2_Order,
	      'amount'   => intval($p7_realAmount),
	      'url'      => $api_obj->config->yeepay_p8_Url,
	      'ordername'=> $r0_Cmd."|".$p4_FrpId
	);
	
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	//如需要获得返回的余额可以用，$context['p_balance']
	//$api_obj->set_callback_func(get_message_callback,write_response_callback,$billingdb);
	
	//$api_obj->write_hint($api_obj->config->billing_db_config);
	$billingdb = new class_billing_db($api_obj->config->billing_db_config, $api_obj);
	
	//插入订单号
	$rdata = $billingdb->ophone_set_3pay_status($status_params);
	if(empty($rdata['h323_return_code']))
    {
		$order_status = $rdata['ReturnValue'];
    }else{
		$order_status = $rdata['h323_return_code'];
	}
				
	//网银名称
	$ebank_name =  $api_obj->config->yeepay_name;
	//网银名称的充值类型
	$currency_type =$api_obj->config->yeepay_currency_type;
	
	//校验码正确.
	if($bRet){
		#获取订单号和状态
		echo "success \r\n";//此返回为必须，反应给服务端做相应	
		if($r1_Code=="1" &&  $order_status > 0){
			echo "<br>支付成功!";
			echo "<br>商户订单号:".$p2_Order;
			echo "<br>支付金额:".$p3_Amt;
			
			if($p8_cardStatus == "0" ){
				//2.执行存储过程，当用户在网上银行充值成功以后，将执行此存储过程来充值相应的花费
				//说明：执行存储过程，当用户在网上银行充值成功以后，将执行此存储过程来充值相应的花费
				$recharge_array = array(
					'CallerID' => $caller_pin, 
					'Value' => intval($p7_realAmount), 
					'CurrencyType' => $currency_type,
					'Remark' => "Recharge by ".$p4_FrpI."Card:".$p5_CardNo,
					'RealCost' => 0, 
					'UserID' => 1007, 
					'RC_Code' => trim("$p4_FrpId"),
					'EBank_Name' => $ebank_name
				);
				$rdata = $billingdb->ebank_recharge($recharge_array);
				if($rdata > 0){//在billing数据库执行充值成功$rdata['RETVAL']
					#3.执行写入当前状态(充值成功或者充值失败)
					$status_params = array(
					    'order_id' => $p2_Order,
						'state'    => "20",    //"601"// 在billing数据库执行充值成功
						'note' => "充值成功"
					);
					
					//更新wfs
					$r = $api_obj->get_from_api($api_obj->config->new_wfs_api_url,
						array(
							'a' => 'wfs_first_recharge',
							'bsn' => $rdata['reBSN'],
							'imei' => $rdata['reIMEI'],
							'carrier_id' =>	$api_obj->config->carrier_name,
							'pno' => $pno,
							'ep' => $caller_pin,
							'cost' => $p7_realAmount,
							'real_cost' => $rdata['reRealCost'],
							'ct' => $currency_type,
							'real_ct' => $rdata['reCurrencyType'],
							're_code' => trim("$p4_FrpId")
						)
					);
					$api_obj->write_hint(array_to_string(',', array(
						'a' => 'wfs_first_recharge',
						'bsn' => $rdata['reBSN'],
						'imei' => $rdata['reIMEI'],
						'carrier_id' =>	$api_obj->config->carrier_name,
						'pno' => $pno,
						'ep' => $caller_pin,
						'cost' => $p7_realAmount,
						'real_cost' => $rdata['reRealCost'],
						'ct' => $currency_type,
						'real_ct' => $rdata['reCurrencyType'],
						're_code' => trim("$p4_FrpId")
					)));
				}else{
					$status_params = array(
					    'order_id' => $p2_Order,
					    'state'    => "-610",// 在billing数据库执行充值失败
						'note' => "网银充值成功,计费系统充值成功"
					);
				}
			}else{//返回r_code=1成功，但是返回card_status错误
				$status_params = array(
				    'order_id' => $p2_Order,
				    'state'    => "-611" ,
					'note' => "返回r_code=1成功，但是返回card_status错误"
				);

			}
		} else if($r1_Code=="2"){
			echo "<br>支付失败!";
		    echo "<br>商户订单号:".$p2_Order;			
			if($p8_cardStatus == '7'){//7：卡面额与卡号卡密不一致
				$status_params = array(
				    'order_id' => $p2_Order,
				    'state'    => "-612", // ,
					'note' => "卡面额与卡号卡密不一致"
				);
			}else if($p8_cardStatus == '1002'){//1002：本张卡密您提交过于频繁，请您稍后再试
				$status_params = array(
				    'order_id' => $p2_Order,
				    'state'    => "-613", 
					'note' => "本张卡密您提交过于频繁，请您稍后再试"
				);
			}else if($p8_cardStatus == '1003'){//1003：不支持的卡类型（比如电信地方卡）	  	 	
				$status_params = array(
				    'order_id' => $p2_Order,
				    'state'    => "-614", 
					'note' => "不支持的卡类型（比如电信地方卡）"
				);
			}else if($p8_cardStatus == '1004'){//1004：密码错误或充值卡无效
				$status_params = array(
				    'order_id' => $p2_Order,
				    'state'    => "-615", 
					'note' => "密码错误或充值卡无效"
				);
			}else if($p8_cardStatus == '1006'){//1006：该卡在同一天连续错误提交
				$status_params = array(
					'order_id' => $p2_Order,
					'state'    => "-616", 
					'note' => "该卡在同一天连续错误提交"
				);
				 
			}else if($p8_cardStatus == '1007'){//1007：卡内余额不足
				$status_params = array(
				     'order_id' => $p2_Order,
				     'state'    => "-617",
					'note' => "卡内余额不足"
				);
				 
			}else if($p8_cardStatus == '1010'){//1010：此卡正在处理中
				$status_params = array(
				    'order_id' => $p2_Order,
				    'state'    => "-618",
					'note' => "此卡正在处理中"
				);
			}else if($p8_cardStatus == '2005'){//	2005：此卡已使用
				$status_params = array(
				    'order_id' => $p2_Order,
				    'state'    => "-619",
					'note' => "此卡已使用"
				);
			}else if($p8_cardStatus == '2006'){//	2006：卡密在系统处理中
				$status_params = array(
					'order_id' => $p2_Order,
					'state'    => "-620",
					'note' => "卡密在系统处理中"
				);
			}else if($p8_cardStatus == '2007'){//2007：该卡为假卡
				$status_params = array(
					'order_id' => $p2_Order,
					'state'    => "-621",
					'note' => "该卡为假卡"
				);
			}else if($p8_cardStatus == '2008'){//	2008：该卡种正在维护
				$status_params = array(
					'order_id' => $p2_Order,
					'state'    => "-622",
					'note' => "该卡种正在维护"
				);
			}else if($p8_cardStatus == '2009'){//	2009：浙江省移动维护
				$status_params = array(
				    'order_id' => $p2_Order,
				    'state'    => "-623",
					'note' => "浙江省移动维护"
				);
			}else if($p8_cardStatus == '2010'){//2010：江苏省移动维护
				$status_params = array(
					'order_id' => $p2_Order,
					'state'    => "-624",
					'note' => "江苏省移动维护"
				);
			}else if($p8_cardStatus == '2011'){//2011：福建省移动维护
				$status_params = array(
					'order_id' => $p2_Order,
					'state'    => "-625",
					'note' => "福建省移动维护"
				);
			}else if($p8_cardStatus == '2012'){//2012：辽宁省移动维护
				$status_params = array(
					'order_id' => $p2_Order,
					'state'    => "-626",
					'note' => "辽宁省移动维护"
				);
			}else if($p8_cardStatus == '10000'){//10000：未知错误
				$status_params = array(
					'order_id' => $p2_Order,
					'state'    => "-627",
					'note' => "未知错误"
				);
			}else{
				$status_params = array(
					'order_id' => $p2_Order,
					'state'    => "-628",
					'note' => "网银支付失败"
				);
			}
			
		}else{
			$status_params = array(
				'order_id' => $p2_Order,
				'state'    => "-629",
				'note' => "网银支付失败或者写入订单失败"
			);
		}
	}else{
		$status_params = array(
		    'order_id' => $p2_Order,
		    'state'    => "-601",
			'note' => "交易签名无效"
		);
		
	}
	try{
		$def_prefix = isset($api_obj->config->default_prefix)?$api_obj->config->default_prefix:'0086';
		$pno = check_phone_number($return_params['0'],$def_prefix);
		$racharge_array = array(
			'carrier_name' => $api_obj->config->carrier_name,
			'order_id' => $status_params['order_id'],
			'recharge_phone' => $pno,
			'recharge_e164' => $return_params['1'],
			'recharge_card' => $p5_CardNo,
			'recharge_type' =>	$p4_FrpId,
			'recharge_value' =>	$p7_realAmount,
			'state' => $status_params['state'],
			'note' => $status_params['note']
		);
		$billingdb->ophone_update_3pay_status_new($status_params);//更改充值状态标识	
		$api_obj->send_info_by_recharge("支付充值(联通、移动、电信)",$racharge_array);
		if($api_obj->config->recharge_need_sms_response){
			//如果第三方充值需要短信回应则使用短信接口报告充值状态
			require_once (__EZLIB__.'/common/sms_server.php');
			//取得全国码的电话号码
			$sno = '10010';
			//判断号码是否为大陆手机号码
			if((substr($pno,0,3) == '861') and $status_params['state'] <> '-629'){
				$pno = substr($pno,2);
				$fmt = isset($api_obj->config->sms_format)?$api_obj->config->sms_format:'%s，代码:%s。类型:%s,金额:%s,卡号:%s';
				$msg = sprintf($fmt,$status_params['note'],$status_params['state'],$p4_FrpId,$p7_realAmount,$p5_CardNo);
				$result = send_sms_queue($api_obj,$pno,$sno,$msg);
				$api_obj->write_hint(array(
					'pno' => $pno,
					'sno' => $sno,
					'msg' => $msg,
					'Result' => $result
				));
			}
		}
	}catch (Exception $e){
		
	}
}
?>

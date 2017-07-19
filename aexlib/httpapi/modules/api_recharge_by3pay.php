<?php
api_action($api_object);

/*
 执行API操作，操作是在modules目录下的api_开头加上action名称的PHP文件。
 引用这个文件后我们在这个文件里具体实现action操作
 */
function api_action($api_obj){
	$v_oid  = trim($_REQUEST['oid']);       // 商户发送的v_oid定单编号   
	$e164 =	empty($_REQUEST['pin']) ? '' : $_REQUEST['pin'];
	$v_amount  =	empty($_REQUEST['amount']) ? '0' : $_REQUEST['amount']; // 订单实际支付金额
	$v_moneytype  =	empty($_REQUEST['moneytype']) ? 'CNY' : $_REQUEST['moneytype'];  //订单实际支付币种    
	$v_rc_code	= empty($_REQUEST['rc_code']) ? 'RCINC' : $_REQUEST['rc_code'];
	$remark   =	trim(mb_convert_encoding($_REQUEST['remark' ],'gb2312'));      //备注字段1

	//存储过程为合成存储过程
	$billingdb = new class_billing_db($api_obj->config->billing_db_config, $api_obj);
	
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
	$rdata = $billingdb->ophone_set_3pay_status($status_params);
	if(empty($rdata['h323_return_code']))
    {
		$order_status = $rdata['ReturnValue'];
    }else{
		$order_status = $rdata['h323_return_code'];
	}
	
	if ($order_status > 0) {
		//说明：执行存储过程，当用户在网上银行充值成功以后，将执行此存储过程来充值相应的花费
		$recharge_array = array(
			'CallerID' => stripslashes($e164), 
			'Value' => intval($v_amount), 
			'CurrencyType' => $v_moneytype,
			'Remark' => $remark,
			'RealCost' => 0, 
			'UserID' => 1046, 
			'RC_Code' => trim($v_rc_code),
			'EBank_Name' => $v_rc_code
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
		$return_value = $rdata['RETURN-CODE'];
		if($return_value > 0 )
		{
			$billingdb->ophone_update_3pay_status($status_params);//更改充值状态标识
			$api_obj->return_code = '1';
		}	else{
			$billingdb->ophone_update_3pay_status($status_params);//更改充值状态标识
			$api_obj->return_code = $rdata['h323_return_code'];
		}
	}else{
		$api_obj->return_code = '-8';
	}
	
	//写返回的信息
	$api_obj->write_response();
}
?>

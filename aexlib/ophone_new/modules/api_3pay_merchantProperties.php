<?php
	/*
	 * @Description 易宝支付非银行卡支付专业版接口范例 
	 * @V3.0
	 * @Author yang.xu
	 */
	
 	/* 商户编号p1_MerId,以及密钥merchantKey 需要从易宝支付平台获得*/
	//怡拓电脑商户编号	
	$p1_MerId	= $api_obj->config->yeepay_p1_MerId;	
	//怡拓电脑商户密钥																									#测试使用
	$merchantKey=  $api_obj->config->yeepay_merchantKey; 
	$logName	=  $api_obj->config->yeepay_logName;
	# 非银行卡支付专业版请求地址,无需更改.
	$reqURL_SNDApro		=  $api_obj->config->yeepay_reqURL_SNDApro;
	
?> 
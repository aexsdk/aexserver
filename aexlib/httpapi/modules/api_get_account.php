<?php
api_action ( $api_object );

/**
 * 帐户的基本信息
		参数
			a : get_account
			pin    :   绑定手机号码或者终端号码,格式为24位以内的数字
			pass : 帐号密码
			key : (可选)如果提供了KEY，则pass为MD5_Encypt(pass,key+pin)。.net和php关于md5加密的代码参见附件
			
		返回值
			E164  ：终端帐号
			Status : 状态 ， 0初始化，1=正常，2=停用
			Balance : 余额   以元为单位的字符串
			Caller  :  绑定号码
			ActiveTime :  字符串的激活时间，创建帐号的时间
			FirstRegister: 第一次注册的时间
			FirstCall : 第一次通话时间
			LastCall : 最后一次通话时间
			Remark : 说明
 *
 * @param unknown_type $api_obj
 */
function api_action($api_obj) {
	$pin = $_REQUEST ['pin'];
	$pass = $_REQUEST ['pass'];
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	
	if (isset ( $_REQUEST ['pin'] ) && isset ( $_REQUEST ['pass'] )) {
		//如果请求提供了pin和pass则使用请求中的pin和pass,Pass为使用key+pin作为密钥的MD5加密串
		if (isset ( $_REQUEST ['key'] ))
			$pass = api_decrypt ( $_REQUEST ['pass'], $_REQUEST ['key'] . $_REQUEST ['pin'] );
		else
			$pass = $_REQUEST ['pass'];
	}
	$ra = $billing_db->get_endpoint_info ( $pin, $pass );
	if (is_array ( $ra )) {
		$api_obj->return_code = 1;
		$api_obj->push_return_data ( 'data', $ra );
	} else {
		//返回不是数组
		$api_obj->return_code = - 100;
	}
	$api_obj->write_response ();
}

?>
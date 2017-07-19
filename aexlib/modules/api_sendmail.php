<?php
/*
	执行Action的行为
*/
api_sendmail_action($api_object);

/*
	定义Action具体行为
*/
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
	$resp = $api_obj->write_return_xml();			
	//按照老的手机处理格式处理返回代码，正确的处理返回代码手机才会从请求界面中退出。
	$resp = $resp. $api_obj->write_return_param('response-code',$api_obj->return_code);
	return $resp;
}

function api_sendmail_action($api_obj) {
	require_once ($api_obj->params['common-path'].'/class.phpmailer-lite.php');				

	$mail             = new PHPMailerLite(); // defaults to using php "Sendmail" (or Qmail, depending on availability)
	$body             = $_REQUEST['contents'];//file_get_contents('contents.html');
	
	$mail->SetFrom('Monit@eztor.com', 'Monit');
	
	$address = "support@eztor.com";
	$mail->AddAddress($address, "Support");
	
	$mail->Subject    = $_REQUEST['subject'];
	
	$mail->AltBody    = "查看此邮件请使用HTML兼容的邮件查看器!"; // optional, comment out and test
	
	$mail->MsgHTML($body);
	
	//$mail->AddAttachment("images/phpmailer.gif");      // attachment
	//$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment
	
	if(!$mail->Send()) {
		$api_obj->return_code = -201;
		$api_obj->write_hint(sprintf("Send mail error:%s",$mail->ErrorInfo)); 
	} else {
		$api_obj->return_code = 101;	//Send mail success
	}
	
	$api_obj->write_response();
}

?>

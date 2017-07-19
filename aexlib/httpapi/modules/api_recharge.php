<?php
	api_action($api_object);

//查询CDR记录
function api_action($api_obj){
	$pin = $_REQUEST['pin'];
	$pass = $_REQUEST['pass'];
	$spin = $_REQUEST['spin'];
	$spass = $_REQUEST['spass'];
	$type = $_REQUEST['type'];
	$value = $_REQUEST['value'];
	
	$billing_db = new class_billing_db ( $api_obj->config->billing_db_config, $api_obj );
	
	if(isset($_REQUEST['pin']) && isset($_REQUEST['pass'])){
		//如果请求提供了pin和pass则使用请求中的pin和pass,Pass为使用key+pin作为密钥的MD5加密串
		if (isset($_REQUEST['key']))
			$pass = api_decrypt($_REQUEST['pass'],$_REQUEST['key'].$_REQUEST['pin']);
		else 	
			$pass = $_REQUEST['pass'];
	}
	if(isset($type) && isset($value)){
		$remark = mb_convert_encoding(sprintf('recharge from %s',get_request_ipaddr()),"gb2312");
		$resaler = get_request_preparams($api_obj);
		if(is_array($resaler)){
			$params = array(
				'resaler' => $resaler['resaler'],
				'pass' => $resaler['pass'],
				'pin' => $pin, 
				'value' => $value,
				'type' => $type,
				'remark' => $remark
			);
			$rdata = $billing_db->billing_resaler_recharge($params);	
			$api_obj->return_code = $rdata['RETURN-CODE'];
			$api_obj->write_response();
			//先返回给客户端，然后添加的会记录到日志里
			$api_obj->push_return_data('params',$params);
			$api_obj->push_return_data('data',$rdata);
			return;
		}else{
			$api_obj->return_code = -110;
			$api_obj->push_return_data('error','没有设置代理商参数，请与管理员联系');
		}
	}else{
		$spin = str_replace('#',"*",$spin);
		$rp = explode('*',$spin);
		if(Count($rp)>1 && $rp[0] <> '0'){
			require_once (__EZLIB__.'/common/api_ivr.php');
			$api_obj->return_code = api_ophone_3pay($api_obj,$billing_db,'',$pin,$spin,$pass);
		}else{
			if(Count($rp)>1)$spin = $rp[1];
			$ra = $billing_db->billing_recharge(array(
				'pin' => $pin,
				'caller' => $pin,
				'cardno' => $spin,
				'pass' => $spass
				));
			if(is_array($ra)){
				$api_obj->return_code = $ra['RETURN-CODE'];
				$api_obj->push_return_data('data',$ra);
			}else{
				//返回不是数组
				$api_obj->return_code = -100;
			}
		}		
	}
	$api_obj->write_response();
}
	
?>
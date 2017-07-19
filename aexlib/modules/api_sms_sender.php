<?php
/*
	执行Action的行为
*/
api_sms_action($api_object);

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

/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj,$context){
	$resp = $api_obj->write_return_xml();			
	//按照老的手机处理格式处理返回代码，正确的处理返回代码手机才会从请求界面中退出。
	//$resp = $resp. $api_obj->write_return_param('response-code',$api_obj->return_code);
	return $resp;
}

class class_sms_db extends api_pgsql_db{
	public $rows = array();
}

function sms_handle_append_row($context,$index,$row){
	foreach($row as $key=>$value)
		if(is_string($value))
			$row[$key] = mb_convert_encoding($value,"UTF-8","GB2312");
	array_push($context->rows,$row['cell_phone_no']);
	//var_dump($row);
}

function api_sms_action($api_obj) {
	require_once ($api_obj->params['common-path'].'/sms_server.php');				
	
	$sms_db = new class_sms_db($api_obj->config->sms_db_config,$api_obj);
	//我们设置回调函数的时候把存储过程的返回数组传给了相应的回调函数，也就是说在这些函数里可以使用返回值的数组了。
	$api_obj->set_callback_func(NULL,write_response_callback,$sms_db);		
	$api_obj->md5_key = empty($_REQUEST['key'])?'':$_REQUEST['key'];
	$func = $_REQUEST['func'];
	
	//var_dump($sms_db->rows);
	if(function_exists($func)){
		execute_sms_func($api_obj,$sms_db,$func);	
	}else{
		$sms_db->set_return_code(-102);		
	}
	$api_obj->write_response();
}

function save_report_log($sms_db,$record)
{
	$rs = array();
	foreach($record as $rv){
		//$rv = mb_convert_encoding($rv,"UTF-8","GB2312");
		array_push($rs,"'$rv'");
	}
	$sql = sprintf("insert into ez_crm_db.tb_send_report_log values (%s)",join(",",$rs));
	//var_dump($sql);
	$sms_db->exec_db_sp($sql);
}

function save_recive_sms($sms_db,$record)
{
	$rs = array();
	foreach($record as $rv){
		array_push($rs,"'$rv'");
	}
	$sql = sprintf("insert into ez_crm_db.tb_recv_sms_log values (%s)",join(",",$rs));
	//var_dump($sql);
	$sms_db->exec_db_sp($sql);
}

function execute_sms_func($api_obj,$sms_db,$func)
{
	switch($func){
	case 'send_sms':
	{
		$pno = explode(",",empty($_REQUEST['pno'])?"":$_REQUEST['pno']);
		$msg = $_REQUEST['msg'];
		$sport = $_REQUEST['sport'];
		if(!empty($msg)){
			//从数据库获得客户资料
			$offset = empty($_REQUEST['offset'])?0:$_REQUEST['offset'];
			$count = empty($_REQUEST['count'])?10:$_REQUEST['count'];
			$sql = sprintf("select substring(cell_phone_no,3) as cell_phone_no from ez_crm_db.tb_targets "
				."where status = 0 offset %d limit %d ",$offset,$count);
			//var_dump($sql);
			$sms_db->exec_query($sql,array(),sms_handle_append_row,$sms_db);
			$pnos_data = $sms_db->rows;
			if(is_array($pnos_data)){
				//var_dump($pno);
				if(count($pno)>0 && $pno[0]!= '')
					$pno = $pno + $pnos_data;
				else 
					$pno = $pnos_data;
				$r = call_user_func($func,$api_obj->config,$api_obj,$pno,$sport,$msg);
				echo sprintf("<br><hr>Send sms list(Result=%s) :<br>%s<br><hr>",$r,join("<br>",$pno));
				if($r > 0){
					$api_obj->push_return_data('Result',$r);
					$upnos = array();
					foreach ($pnos_data as $v)
						array_push($upnos,"'$v'");
					$upnos = join(",",$upnos);
					$usql = "update ez_crm_db.tb_targets set   status = 100,   remark = '$r'"
	   								." where substring(cell_phone_no,3) in ($upnos)";
	   				//var_dump($usql);
	   				$sms_db->exec_db_sp($usql);
					
	   				$sql = sprintf("insert into ez_crm_db.tb_send_sms_log values ('%s','%s',1,'%s')",$r,join(",",$pnos_data),$msg);
	   				//var_dump($sql);
					$sms_db->exec_db_sp($sql);
					$sms_db->set_return_code(101);
				}else{
					$sms_db->set_return_code($r);
					$api_obj->push_return_data('Result',$r);
				}
			}else{
				$sms_db->set_return_code(-101);		
			}
			execute_sms_func($api_obj,$sms_db,'sms_status');
			execute_sms_func($api_obj,$sms_db,'recvice_sms');
		}else{
			$sms_db->set_return_code(-99);			
		}
 			break;
	}
	case 'sms_status':
	{
		$r = call_user_func($func,$api_obj,$api_obj->config->sms_account['uid'],$api_obj->config->sms_account['pass']);
		//echo var_dump($r);
		if(is_array($r)){
			//var_dump($r);
			foreach($r as $v){
				if(is_array($v)){
					echo sprintf("<br><hr>Send sms report list :<br>%s<br><hr>",join("<br>",$v));
					foreach ($v as $rv)
						save_report_log($sms_db,explode(",",$rv));
				}else{
					echo sprintf("<br><hr>Send sms report list :<br>%s<br><hr>",$v);
					$record = explode(",",$v);
					save_report_log($sms_db,$record);
				}
			}
			$sms_db->set_return_code(103);
			$api_obj->push_return_data('Result',join(",",$r));
		}else{
			$sms_db->set_return_code(-103);
			$api_obj->push_return_data('Result',$r);
		}
		break;
	}
	case 'recive_sms':
	{
		$r = call_user_func($func,$api_obj,$api_obj->config->sms_account['uid'],$api_obj->config->sms_account['pass']);
		//echo $r;
		if(is_array($r)){
			//var_dump($r);
			foreach($r as $v){
				if(is_array($v)){
					echo sprintf("string encoding %s<br>",mb_detect_encoding($v));
					$v = mb_convert_encoding($v,"UTF-8","GBK,GB2312,UCS-2LE,big5,JIS, eucjp-win, sjis-win");
					echo sprintf("<br><hr>Send sms report list :<br>%s<br><hr>",join("<br>",$v));
					foreach ($v as $rv)
						save_recive_sms($sms_db,explode(",",$rv));
				}else{
					echo sprintf("string encoding %s<br>",mb_detect_encoding($v));
					$v = mb_convert_encoding($v,"UTF-8","GBK,GB2312,UCS-2LE,big5,JIS, eucjp-win, sjis-win");
					echo sprintf("<br><hr>Send sms report list :<br>%s<br><hr>",$v);
					$record = explode(",",$v);
					save_recive_sms($sms_db,$record);
				}
			}
			$sms_db->set_return_code(104);
			$api_obj->push_return_data('Result',join(",",$r));
		}else{
			$sms_db->set_return_code(-104);
			$api_obj->push_return_data('Result',$r);
		}
		break;
	}
	default:
		$sms_db->set_return_code(-100);
		break;
	}
}

?>
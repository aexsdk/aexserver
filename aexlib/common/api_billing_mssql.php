<?php
/*
 定义Billing的数据库类
 */
require_once dirname(__FILE__).'/api_mssql_db.php';

/*
 将SQL返回的行附加到数组
 参数
 $context : 结果数组
 $index : 行序号
 $row : 行数组
 */
function billing_handle_append_row($context,$index,$row){
	foreach($row as $key=>$value){
		switch($key){
		case 'SessionID':
			$sid = explode('-',$value);
			switch(count($sid)){
				case 5:
					$row['fn'] = sprintf("%s-%s-%s",$sid[0],$sid[1],substr($sid[2],0,strlen($sid[2])-4));
					break;
				case 3:
					$row['fn'] = $value;
					break;
				default:
					$row['fn'] = '';
					break;
			}
			break;
		case 'CallerID':
			$phoneno = explode(':',$value);
			if(is_array($phoneno)){
				switch(count($phoneno)){
				case 5:
					$caller = sprintf("(+%s)%s-%s",$phoneno[1],$phoneno[3],$phoneno[4]);
					break;
				case 2:
					$caller = sprintf("+%s",$phoneno[1]);
					break;
				default:
					$caller = $value;
				}
			}else{
				$caller = $value;
			}
			$row['caller'] = $caller;
			break;
		case 'CalledID':
			$phoneno = explode(':',$value);
			if(is_array($phoneno)){
				switch(count($phoneno)){
				case 5:
					$callee = sprintf("(+%s)%s-%s",$phoneno[1],$phoneno[3],$phoneno[4]);
					break;
				case 2:
					$callee = sprintf("+%s",$phoneno[1]);
					break;
				default :
					$callee = "+".substr($value,1);
				}
			}else{
				$caller = $value;
			}
			$row["callee"] = $callee;
			break;
		
		}
		if(is_string($value))
			$row[$key] = mb_convert_encoding($value,"UTF-8","GB2312");
	}
	array_push($context->rows,$row);
	//echo sprintf("Row[%d]=%d<br>",$index,count($context->rows));
	//var_dump($context);
}

function billing_handle_endpoint_append_row($context,$index,$row){
	//var_dump($context);
	foreach($row as $key=>$value){
		switch($key){
			//case 'State':
			case 'Status':
				$row['status'] = empty($value)?0:$value;
				$row[$key] = $context->get_state(empty($value)?0:$value);
				//$connext->api_object->write_hint(sprintf("%s=%s<br>",$key,$value));
				break;
			case 'ChargeScheme':
				$row['cs_id'] = empty($value)?0:$value;
				$row[$key] = $context->get_rate_plan($row['cs_id'],$row);
				//$connext->api_object->write_hint(sprintf("%s=%s<br>",$key,$value));
				break;
			case 'ActivePeriod':
				$row['WarrantyPeriod'] = $context->get_active_period(empty($value)?0:$value,$row);
				break;
			default:
				if(is_string($value))
					$row[$key] = mb_convert_encoding($value,"UTF-8","GB2312");
				break;
		}
	}
	array_push($context->rows,$row);
}

class class_billing_db extends api_mssql_db{
	var $rows = array();
	var $total_count = 0;

	public function get_state($state){
		$msg = $this->get_message(900+$state,$state);
		return $msg;
	}

	public function get_rate_plan($rp,$row){
		$code = sprintf('99%d',$rp);
		$sn = mb_convert_encoding($row['Name'],"UTF-8","GB2312");
		if(isset($this->api_object->params['is_uphone']))
			$msg = $this->get_message($code,$sn);
		else 
			$msg = $sn;
		if($row['Hire'] != 0){
			$msg = sprintf($msg,$this->get_hire_period($row['HirePeriod']),$this->get_hire_type($row['HireType']));
		}
		return $msg;
	}

	public function get_active_period($value,$row){
		if($value<0){
			$msg = $this->get_message('972','-');
			$msg = sprintf($msg,-1*$value,$this->get_hire_type(1));
		}else{
			$msg = $this->get_message('973','-');
			$msg = sprintf($msg,$value,$this->get_hire_type(1));
		}
		return $msg;
	}

	public function get_hire_period($value){
		if($value<0){
			$msg = $this->get_message('970','-');
			$msg = sprintf($msg,-1*$value);
		}else{
			$msg = $this->get_message('971','-');
			$msg = sprintf($msg,$value);
		}
		return $msg;
	}

	public function get_hire_type($value){
		$msg = $this->get_message(sprintf('98%d',$value),'-');
		return $msg;
	}


	/********************MLM Billing  Start*******************************/
	/*
		MLM系统绑定帐号到VoIP帐号，只有绑定的帐号才可以获得收益
		参数
		$mlm_account  MLM系统跟节点的帐号，帐号使用字段合成格式ID:CellPhoneNumber，这样就支持同一个手机号码在多个网络中应用的模式
		$e164 绑定手机的VoIP帐号
		$pass  绑定手机VoIP帐号的密码
		*/
	public function mlm_bind_voip_account($mlm_account,$e164,$pass){
		//$sql = 'exec sp_n_mlm_bind_account \'%s\',\'%s\',\'%s\' ';//'dbo.sp_n_mlm_bind_account:1';
		$sql = 'sp_n_mlm_bind_account;1';//sprintf($sql,$mlm_account,$e164,$pass);
		//echo $sql;
		$rc = $this->exec_proc($sql,array('@mlm_account'=>$mlm_account,'@phone_no'=>$e164,'@phone_pass'=>$pass));
		if(is_array($rc))
		{
			$this->total_count = $rc["total_count"];
			$this->set_return_code($rc['p_return']);
		}else{
			$this->set_return_code(-81);
		}
		return $rc;
	}
	/*
		MLM系统获取帐号的收益余额
		参数
		$mlm_account   MLM用户的帐号，此帐号为合成帐号ID:CellPhoneNumber
		$e164 绑定手机的VoIP帐号
		$pass  绑定手机VoIP帐号的密码
		返回值
		如果$this->return_code > 0 时
		返回值为收益余额，数据库中的收益余额清空
		否则，返回值无效
		*/
	public function mlm_get_voip_income($mlm_account,$phone_no,$pass){
		$sql = 'dbo.sp_n_mlm_get_balance;1';
		$rdata = $this->exec_proc($sql, array(
											 '@mlm_account'=>$mlm_account
		));
		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->write_hint(array_to_string("\r\n",$rdata));
			return $rdata['@Value'];
		}
	}
	/*
		MLM系统查询帐号的收益余额
		参数
		$mlm_account   MLM用户的帐号，此帐号为合成帐号ID:CellPhoneNumber
		$e164 绑定手机的VoIP帐号
		$pass  绑定手机VoIP帐号的密码
		返回值
		如果$this->return_code > 0 时
		返回值为收益余额
		否则，返回值无效
		*/
	public function mlm_query_voip_income($mlm_account){
		$sql = 'dbo.sp_n_mlm_query_balance;1';
		$rdata = $this->exec_proc($sql,array(
											 '@root_mlm_account'=>$mlm_account
		));
		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->write_hint(array_to_string("\r\n",$rdata));
			return $rdata['@Value'];
		}
	}

	/*
		MLM系统为VoIP帐号充值的函数。
		参数
		$root_mlm_account  MLM系统跟节点的帐号，帐号使用字段合成格式ID:CellPhoneNumber，这样就支持同一个手机号码在多个网络中应用的模式
		$e164 绑定手机的VoIP帐号
		$pass  绑定手机VoIP帐号的密码
		$value 转帐金额 
		返回值
		>0表示成功，<0表示失败，失败代码见相应的XML文件
		*/
	public function mlm_recharge_for_voip($root_mlm_account,$e164,$pass,$value){
		$sql = 'dbo.sp_n_mlm_recharge;1';
		$rdata = $this->exec_proc($sql,array(
											 '@root_mlm_account'=>$root_mlm_account,
											 '@e164'=>$e164,
											 '@pass'=>$pass
		));
		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->write_hint(array_to_string("\r\n",$rdata));
			return $this->return_code;
		}
	}

	/********************MLM Billing  End*******************************/

	/*****************China Back NPS start*******************/
	/**
	 * 检查充值的手机号码或终端号码是否存在
	 * 返回充值的终端号码
	 */
	public function nps_chcek_endpoint($endpoint_no){
		//$recharge_array =  radius_execute_proc("dbo.sp_Ophone_Recharge_beta;1",$parm);
		//执行存储过程，赋值参数
		$sql = 'sp_NPS_check_endpoint;1';
		$rdata = $this->exec_proc($sql,array('@Endpoint' => $endpoint_no));
		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->rows = array();
			foreach ($rdata as $k => $v)
			{
				if($k[0] == '@')
				$this->rows[substr($k,1)] = $v;
				else
				$this->rows[$k] = $v;
			}
			return $this->rows;
		}else{
			return $rdata;
		}
	}
	
	public function ebank_recharge($recharge_array){
		/*@CallerID nvarchar(50),
		@Value integer,
		@CurrencyType	varchar(50),
		@RealCost integer=NULL,	--实际付费
		@UserID integer,
		@RC_Code nvarchar(10),
		@Remark nvarchar(80)=NULL
		*/
		$sql = 'sp_IncBalanceByEBank;1';
		$rdata = $this->exec_proc($sql,array(
				'@CallerID' => $recharge_array['CallerID'], 
		        '@Value' => $recharge_array['Value'], 
				'@CurrencyType' => $recharge_array['CurrencyType'],
				'@RealCost' => $recharge_array['RealCost'], 
		        '@UserID' => $recharge_array['UserID'], 
		        '@RC_Code' => $recharge_array['RC_Code'],
				'@Remark' => $recharge_array['Remark'],
				'@EBank_Name' => $recharge_array['EBank_Name']
			)
		);
		//$this->write_hint($rdata);
		//var_dump($rdata);
		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->rows = array();
			foreach ($rdata as $k => $v)
			{
				if($k[0] == '@')
				$this->rows[substr($k,1)] = $v;
				else
				$this->rows[$k] = $v;
			}
			//$this->api_object->push_return_data('data',$rdata);
			return $this->rows;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}
	}
	
	/*****************China Back NPS end*********************/

	/********************Ophone Billing  Start*******************************/
	/*
	 * 修改billing端E164号码对应的密码
	 * */
	public function ophone_modify_billing_password($params) {
		$sql = "dbo.sp_Ophone_ModifyPassword_beta;1";
		$sql_data = array(
		/*
		 @EndpointNo		varchar(50)		帐号号码
		 @Password 		varchar(50)		-- Org Password
		 @NewPassword 	varchar(50)		-- new password
		 */
		   		"@EndpointNo" => $params ['v_pin'],
				"@Password" => $params ['v_user_password'], 
				"@NewPassword" => $params ['v_new_password'] 
		);
		$rdata = $this->exec_proc($sql,$sql_data);

		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->rows = array();
			foreach ($rdata as $k => $v)
			{
				if($k[0] == '@')
				$this->rows[substr($k,1)] = $v;
				else
				$this->rows[$k] = $v;
			}
			//$this->api_object->push_return_data('data',$rdata);
			//$this->write_hint($rdata);
			return $this->rows;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}

	}

	/*
	 * 根据被叫号码和当前账号的计费方案获取本次通话的费率
	 * */
	public function ophone_get_rate($params){
		//var_dump($params);
		$sql_data = array(
			'@E164' => $params['caller_pin'],
			'@v_pno' => $params['pno'],
			'@v_callee' => $params['callee']
		);
		//response from radius by produre
		$sql = "sp_Ophone_GetReta_beta;1";
		//var_dump($sql_data);
		$rdata = $this->exec_proc($sql,$sql_data);
		//var_dump($rdata);
		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->rows = array();
			foreach ($rdata as $k => $v)
			{
				if($k[0] == '@')
				$this->rows[substr($k,1)] = $v;
				else
				$this->rows[$k] = $v;
			}
			//$this->api_object->push_return_data('data',$rdata);
			//$this->write_hint($rdata);
			return $this->rows;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}
	}

	/**
	 * 优会通手机执行空中充值操作
	 * caller_pin:本机手机的PIN号码
	 * callee_pin:被充值的手机的PIN号
	 * value：充值金额
	 * */
	/*
	 2.sp_ophone_recharge_onair
	 功      能：如果上一步返回值为1，那么在sqlserver中进行充值操作。
	 输入参数：
	 @g_awm_nvram_data_ophone_pin nvarchar(50), --主动充值电话的E164号码
	 @tg_account nvarchar(50),                         --被动充值电话的E164号码，从wfs（pgsql）中通过手机号码获取到的唯一号码
	 @Value integer                         --充值金额，整型数
	 输出参数：
	 --     返回值
	 -- >0 充值成功
	 -- =0 记录充值日志失败
	 -- -1 认证失败
	 -- -2 扣减大于帐户余额
	 -- -3 充值失败
	 -- -4   源帐号和目的帐号相同
	 -- -5 目的帐号不存在
	 -- -8   源帐号与目的帐号货币类型不一致
	 语法：
	 sp_ophone_recharge_onair
	 (
		@g_awm_nvram_data_ophone_pin nvarchar(50), --主动充值电话的E164号码
		@tg_account nvarchar(50),                  --被动充值电话的E164号码，从wfs（pgsql）中通过手机号码获取到的唯一号码
		@Value integer                             --充值金额，整型数
		)
		*/
	
	public function ophone_do_eload($params){

		$sql = "sp_ophone_recharge_onair_beta;1";
		$sql_data =array(
		    '@g_awm_nvram_data_ophone_pin' => $params ['caller_pin'], 
		    '@tg_account' => $params ['callee_pin'], 
		    '@Value' => $params ['value'] 
			
		);
		//var_dump($sql_data);
		$rdata = $this->exec_proc($sql,$sql_data);
		//var_dump($rdata);
		//if(is_array($rdata)){
		//解析VoIP数据库存储过程返回的参数
		//	$this->write_hint(array_to_string("\r\n",$rdata));
		//	return $this->return_code;
		//}
		//return $rdata;

		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->rows = array();
			foreach ($rdata as $k => $v)
			{
				if($k[0] == '@')
				$this->rows[substr($k,1)] = $v;
				else
				$this->rows[$k] = $v;
			}
			//$this->api_object->push_return_data('data',$rdata);
			//$this->write_hint($rdata);
			return $this->rows;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}

	}
	
/**
	 * 优会通手机执行空中充值操作
	 * caller_pin:本机手机的PIN号码
	 * callee_pin:被充值的手机的PIN号
	 * value：充值金额
	 * */
	/*
	 2.sp_ophone_recharge_onair
	 功      能：如果上一步返回值为1，那么在sqlserver中进行充值操作。
	 输入参数：
	 @g_awm_nvram_data_ophone_pin nvarchar(50), --主动充值电话的E164号码
	 @tg_account nvarchar(50),                         --被动充值电话的E164号码，从wfs（pgsql）中通过手机号码获取到的唯一号码
	 @Value integer                         --充值金额，整型数
	 输出参数：
	 --     返回值
	 -- >0 充值成功
	 -- =0 记录充值日志失败
	 -- -1 认证失败
	 -- -2 扣减大于帐户余额
	 -- -3 充值失败
	 -- -4   源帐号和目的帐号相同
	 -- -5 目的帐号不存在
	 -- -8   源帐号与目的帐号货币类型不一致
	 语法：
	 sp_ophone_recharge_onair
	 (
		@g_awm_nvram_data_ophone_pin nvarchar(50), --主动充值电话的E164号码
		@tg_account nvarchar(50),                  --被动充值电话的E164号码，从wfs（pgsql）中通过手机号码获取到的唯一号码
		@Value integer                             --充值金额，整型数
		)
		*/
	public function ophone_do_eload_beta($params){
		$sql = "sp_ophone_eload;1";
		$sql_data =array(
		    '@CallerPin' => $params ['caller_pin'], 
			'@CallerPwd' => $params ['caller_pwd'],
			'@CallerPno' => $params ['caller_pno'],
		    '@EloadPin' => $params ['eload_pin'], 
		    '@Value' => $params ['value'], 
			'@EloadPno' => $params ['eload_pno'],
		);
		$rdata = $this->exec_proc($sql,$sql_data);
		//var_dump($rdata);
		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->rows = array();
			foreach ($rdata as $k => $v)
			{
				if($k[0] == '@')
				$this->rows[substr($k,1)] = $v;
				else
				$this->rows[$k] = $v;
			}
			//$this->api_object->push_return_data('data',$rdata);
			//$this->write_hint($rdata);
			return $this->rows;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}

	}
	
	/*说明：根据ID号到billing中获取对应ID号当前执行充值后返回的状态值
	 CREATE   proc sp_payczstateselect
	 @v_oid nvarchar(64),
	 @state int = 0 output
			
	 as
	 	
	 select  @state = CZ_state from tb_pay where v_oid = @v_oid
	 	
	 if @@Rowcount <1
	 return -1 --update cz_state fail
	 	
	 return  1 --successfully update the cz_state
	 * */
	public function ophone_get_3pay_status($status_params){
		echo "order_id =".$status_params['order_id']."\r\n";
		$sql = 'sp_payczstateselect;1';
		$rdata = $this->exec_proc($sql,array(
					        '@v_oid' => $status_params['order_id']
		)
		);
		//var_dump($rdata);
		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->rows = array();
			foreach ($rdata as $k => $v)
			{
				if($k[0] == '@')
				$this->rows[substr($k,1)] = $v;
				else
				$this->rows[$k] = $v;
			}
			//$this->api_object->push_return_data('data',$rdata);
			return $this->rows;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}
	}

	/*
	 * 说明：根据获取到的订单ID设置当前初始状态
	 ALTER     proc sp_payInfoInsert

	 @CallerID	Nvarchar(50),
	 @V_amount	decimal(38,4),
	 @V_url	Nvarchar(100),
	 @V_oid nvarchar(64),
	 @V_ordername	Nvarchar(64)
	 as


	 insert into tb_pay (CZ_Date,CallerID,V_oid,V_amount,V_moneytype,V_url,CZ_State,V_orderstatus,V_ordername,V_ymd,CZ_Type,Ptype)
		values(getdate(),isnull(@CallerID,''),@V_oid,@V_amount ,1,@V_url,0,0,@V_ordername,CONVERT(varchar(12) ,getdate(), 112 ),'PayOnline',0)

		if @@RowCount <1
		return -1 --insert values fail
		return 1  --insert successfully
		* */
	public function ophone_set_3pay_status($status_params){
		$sql = 'sp_payInfoInsert;1';
		$rdata = $this->exec_proc($sql,array(
                    '@CallerID'=>$status_params['caller_id'],
                    '@V_amount'=>$status_params['amount'],
                    '@V_url'   =>$status_params['url'],
	        		'@V_oid' => $status_params['order_id'],
                    '@V_ordername' =>$status_params['ordername']
			)
		);
		//var_dump($rdata);
		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->rows = array();
			foreach ($rdata as $k => $v)
			{
				if($k[0] == '@')
				$this->rows[substr($k,1)] = $v;
				else
				$this->rows[$k] = $v;
			}
			//$this->api_object->push_return_data('data',$rdata);
			return $this->rows;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}
	}

	/*
	 * 说明：在通过第三方支付时将返回的状态写入到billing数据库
	 ALTER   proc sp_payczstateupdate

		@state int ,
		@v_oid nvarchar(64)
		 
		as

		update tb_pay set CZ_state = @state where v_oid = @v_oid

		if @@Rowcount <1
		return -1 --update cz_state fail
		return  1 --successfully update the cz_state
	 * */
	public function ophone_update_3pay_status($status_params){
		$sql = 'sp_payczstateupdate;1';
		$rdata = $this->exec_proc($sql,array(
			'@state' => $status_params['state'], 
	        '@v_oid' => $status_params['order_id']
		)
		);
		//var_dump($rdata);
		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->rows = array();
			foreach ($rdata as $k => $v)
			{
				if($k[0] == '@')
				$this->rows[substr($k,1)] = $v;
				else
				$this->rows[$k] = $v;
			}
			//$this->api_object->push_return_data('data',$rdata);
			return $this->rows;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}
	}
	
/*
	 * 说明：在通过第三方支付时将返回的状态写入到billing数据库
	 * 增加日志记录
	 ALTER   proc sp_payczstateupdate

		@state int ,
		@v_oid nvarchar(64)
		 
		as

		update tb_pay set CZ_state = @state where v_oid = @v_oid

		if @@Rowcount <1
		return -1 --update cz_state fail
		return  1 --successfully update the cz_state
	 * */
	public function ophone_update_3pay_status_new($status_params){
		$sql = 'sp_payczstateupdate_new;1';
		$rdata = $this->exec_proc($sql,array(
			'@state' => $status_params['state'], 
	        '@v_oid' => $status_params['order_id'],
			'@n_note' => mb_convert_encoding( $status_params['note'], "GB2312", "UTF-8")
		)
		);
		//var_dump($rdata);
		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->rows = array();
			foreach ($rdata as $k => $v)
			{
				if($k[0] == '@')
				$this->rows[substr($k,1)] = $v;
				else
				$this->rows[$k] = $v;
			}
			//$this->api_object->push_return_data('data',$rdata);
			return $this->rows;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}
	}

	/*
	 * 支持yeepay支付方式第三方卡支付方式
	 优会通手机使用联通，移动，电信充值卡进行充值
	 @CallerID nvarchar(50),
	 @Value integer,
	 @RealCost integer=NULL,	--如果此值为NULL则表示为不打折出售
	 @UserID integer,
	 @RC_Code nvarchar(10) NULL,
	 @Remark ntext nps
	  
	 //说明：执行存储过程，当用户在网上银行充值成功以后，将执行此存储过程来充值相应的花费
	 */
	public function ophone_3pay_recharge($recharge_array){
		//$recharge_array =  radius_execute_proc("dbo.sp_Ophone_Recharge_beta;1",$parm);
		//执行存储过程，赋值参数
		/*
		'CallerID' => $api_obj->params['api_params']['pin'],
		'Value'    => $pa7_cardAmt,
		'RealCost' => 'NULL',
		'UserID'   => $p2_Order,
		'RC_Code'  => 'NULL',
		'Remark'   => "$p2_Order"
		* */
		$sql = 'sp_IncBalanceByWeb;1';
		$rdata = $this->exec_proc($sql,array(
			'@CallerID' => $recharge_array['CallerID'], 
	        '@Value' => $recharge_array['Value'], 
	        '@Remark' => $recharge_array['Remark'],
			'@RealCost' => $recharge_array['RealCost'], 
	        '@UserID' => $recharge_array['UserID'], 
	        '@RC_Code' => $recharge_array['RC_Code']
		)
		);
		//var_dump($rdata);
		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->rows = array();
			foreach ($rdata as $k => $v)
			{
				if($k[0] == '@')
				$this->rows[substr($k,1)] = $v;
				else
				$this->rows[$k] = $v;
			}
			//$this->api_object->push_return_data('data',$rdata);
			return $this->rows;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}
	}

	/*
	 优会通手机使用优会通充值卡进行充值
	 */
	public function ophone_recharge_balance($recharge_array){
		//$recharge_array =  radius_execute_proc("dbo.sp_Ophone_Recharge_beta;1",$parm);
		//执行存储过程，赋值参数
		$sql = 'sp_Ophone_Recharge_beta;1';
		$rdata = $this->exec_proc($sql,array(
			'@SourcePin' => $recharge_array['rpin'], 
	        '@PinPassword' => $recharge_array['rpass'], 
	        '@CallerID' => $recharge_array['pin']
//			,
//			'@Balance' => $recharge_array['value']
		)
		);
		//var_dump($rdata);
		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->rows = array();
			foreach ($rdata as $k => $v)
			{
				if($k[0] == '@')
				$this->rows[substr($k,1)] = $v;
				else
				$this->rows[$k] = $v;
			}
			//$this->api_object->push_return_data('data',$rdata);
			$this->write_hint($rdata);
			return $this->rows;
		}else{
			$this->set_return_code(-10);
			$this->write_response();
			exit;
		}
	}
	
	/*
	 手机查询余额，返回余额，免费通话时长等信息
	 */
	public function ophone_query_balance($e164,$pass){
		$sql = 'sp_Ophone_GetBalance_beta;1';
		$rdata = $this->exec_proc($sql,array(
												'@E164' => $e164,
												'@Password' => $pass
		));
		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->rows = array();
			foreach ($rdata as $k => $v)
			{
				if($k[0] == '@')
				$this->rows[substr($k,1)] = $v;
				else
				$this->rows[$k] = $v;
			}
			//$this->api_object->push_return_data('data',$rdata);
			//$this->write_hint($rdata);
			return $this->rows;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}
	}
	/********************Ophone Billing  End*******************************/

	/******************** Billing  Start*******************************/
	/*
		获得终端号码列表的函数
		参数
		$resaler : 代理商ID，0表示运营商，-1表示全部，我们以此来过滤要取得的计费方案的列表
		$offset : 偏移值，开始的行数
		$count : 取回的行数，以后的会丢弃
		返回数组
		表示返回的表内容
		*/
	public function billing_get_charge_plan_list($offset,$count,$resaler){
		switch($resaler){
			case -1:
				$sql = sprintf('select * from ( select top %3$d * from( select top %2$d * from tb_ChangeSchemes order by [id] asc )as a1 order by id desc) as a2 order by id asc ',$resaler,$offset+$count,$count);
				break;
			case 0:
			default:
				$sql = sprintf('select * from ( select top %3$d * from( select top %2$d * from tb_ChangeSchemes where id in (select CS_ID from tb_CS_Purview where PurviewType = 0 and PurviewCode = %1$d) order by [id] asc )as a1 order by id desc) as a2 order by id asc ',$resaler,$offset+$count,$count);
				break;
		}
		//echo $sql;
		$rdata = new class_handle_rows();
		$this->exec_query($sql,array(),billing_handle_append_row,$rdata);
		$this->set_return_code(101);
		//var_dump($rdata->rows);
		//解析VoIP数据库存储过程返回的参数
		//$this->write_hint(array_to_string("\r\n",$rdata));
		return $rdata->rows;
	}

	/*
		获得终端信息表的字段
		参数
		$type ： 请求发起的类型，-1表示终端用户登录查询，=0表示运营商的工作人员登录查询，>0表示代理商的工作人员登录查询
		*/
	function get_endpoint_fields($type=0){
		switch($type){
			case -1:
				$fields = '[h323id], [E164], [FirstRegister], [LastRegister], [GuestID], [GuestName], [LoginName], [AgentID], [AgentName], [Caption], [CallPin], [Status], [ChargeScheme], [Balance], [FreePeriod], [LimitType], [ValidPeriod], [AliasList], [AccessType], [RegArea], [RegArea_CSID], [HireDate], [FirstCall], [LastCall], [EndpointType], [AniCount], [CurrencyType], [IsPrepaid], [CallerNo], [Bind_SN], [Guid_SN],  [ActiveTime], [Hire], [HireType], [Name], [HirePeriod], HireDuration';
				break;
			case 0:
				$fields = '[h323id], [E164], [FirstRegister], [LastRegister], [GuestID], [GuestName], [LoginName], [AgentID], [AgentName], [Caption], [CallPin], [Status], [ChargeScheme], [Balance], [FreePeriod], [LimitType], [ValidPeriod], [AliasList], [AccessType], [RegArea], [RegArea_CSID], [HireDate], [AgentCS], [FirstCall], [LastCall], [EndpointType], [AniCount], [CurrencyType], [IsPrepaid], [CallerNo], [Bind_SN], [Guid_SN], [HireCharge], [ActiveTime], [Hire], [HireType], [Name], [HirePeriod], [ActivePeriod] ';
				break;
			case 1:
				$fields = '[h323id], [E164], [FirstRegister], [LastRegister], [GuestID], [GuestName], [LoginName], [AgentID], [AgentName], [Caption], [CallPin], [Status], [ChargeScheme], [Balance], [FreePeriod], [LimitType], [ValidPeriod], [AliasList], [AccessType], [RegArea], [RegArea_CSID], [HireDate], [AgentCS], [FirstCall], [LastCall], [EndpointType], [AniCount], [CurrencyType], [IsPrepaid], [CallerNo], [Bind_SN], [Guid_SN], [HireCharge], [ActiveTime], [Hire], [HireType], [Name], [HirePeriod], [ActivePeriod] ';
				break;
			default:
				$fields = '[h323id], [E164], [FirstRegister], [LastRegister], [GuestID], [GuestName], [LoginName], [AgentID], [AgentName], [Caption], [CallPin], [Status], [ChargeScheme], [Balance], [FreePeriod], [LimitType], [ValidPeriod], [AliasList], [AccessType], [RegArea], [RegArea_CSID], [HireDate], [AgentCS], [FirstCall], [LastCall], [EndpointType], [AniCount], [CurrencyType], [IsPrepaid], [CallerNo], [Bind_SN], [Guid_SN], [HireCharge], [ActiveTime], [Hire], [HireType], [Name], [HirePeriod], [ActivePeriod] ';
				break;
		}
		return $fields;
	}

	/*
		获得终端号码列表的函数
		参数
		$type : 允许的终端号码类型的数组，如0=注册的终端号码，1=通过设备激活或者批量开出的身份卡
		$status ：允许的终端号码状态的数组，如0=初始化；1=激活；2=停用
		$offset : 偏移值，开始的行数
		$count : 取回的行数，以后的会丢弃
		$filter : 其他过滤条件，如果需要的话调用端可以使用他扩展查询条件
		*/
	public function billing_endpoint_get_list($offset='',$count='',$resaler=-1,$type='',$status='',$endpoint='',$filter=''){
		if(empty($type))
		$type = '0,1';
		if(empty($status))
		$status = '0,1,2';
		if(empty($offset))
		$offset = 0;
		if(empty($count))
		$count = 15;
		if(!isset($resaler))
		$resaler = -1;
		$fields = $this->get_endpoint_fields($resaler);
		$condiction = '';
		switch($resaler){
			case 0:
				if(!empty($endpoint)){
					$condiction .= sprintf(' and (E164 like \'%1$s\' or h323id like \'%1$s\' or guid_sn like \'%1$s\' or callerno like \'%1$s\' or AliasList like \'%1$s\' ) ',$endpoint);
				}
				break;
			case -1:
				if(empty($endpoint)){
					$this->set_return_code(-98);		//请求的参数有误，请联系管理员
					//var_dump($this->api_object->return_data);
					$this->write_warning('请求用户的终端列表但是没有提供EndpointID');
					$this->api_object->write_response();
					exit;//die('请求用户的CDR但是没有提供EndpointID');
				}else{
					$condiction .= sprintf(" and E164 = '%s' ",$endpoint);
				}
				break;
			default:
				if(!empty($endpoint)){
					$condiction .= sprintf(' and (E164 like \'%1$s\' or h323id like \'%1$s\' or guid_sn like \'%1$s\' or callerno like \'%1$s\' or AliasList like \'%1$s\') ',$endpoint);
				}
				$condiction .= sprintf(" and AgentID = %d",$resaler);
				break;
		}
		$sql = sprintf('select * from (select top %5$d * from (select top %4$d %7$s from vi_devices_c where (Status in (%2$s)) and EndpointType in (%1$s) %3$s %6$s  order by e164 asc )as a1 order by e164 desc) as a2 order by e164 asc',
			$type,$status,$condiction,$offset+$count,$count,$filter,$fields);
		$countsql = sprintf('select count(*) as total_count from vi_devices_c where (Status in (%2$s)) and EndpointType in (%1$s) %3$s %6$s  ',
			$type,$status,$condiction,$offset+$count,$count,$filter,$fields);
		//var_dump($countsql);
		//$this->push_return_data('query',$countsql);
		$rc = $this->exec_query($countsql,array());
		if(is_array($rc))
		{
			//var_dump($rc);
			$this->total_count = $rc["total_count"];
			//echo $sql;
			$this->rows = array();
			$this->exec_query($sql,array(),billing_handle_endpoint_append_row,$this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		//var_dump($rdata->rows);
		return $this->rows;
	}
	
	/*
		获得单条终端信息数据，需要带终端密码
		*/

	public function get_endpoint_info($endpointno,$endpointpass){
		$r = $this->billing_endpoint_get_list(0,1,-1,'0,1','0,1,2',$endpointno,sprintf(" and [Password] = '%s'",$endpointpass));
		if(count($r) == 1){
			$row1 = $r[0];
			return $row1;
		}else{
			return false;
		}
	}
	
	//writer : lion wang
	//time : 2010-12-18
	//caption : edit endpoint info
	public function billing_edit_endpoint_info($param_array){
		$sql = "dbo.sp_billing_edit_endpoint_info;1";
		$sql_data =array(
		   	'@E164' => $param_array['e164'],
			'@AgentID' => intval($param_array['agnet_id']),
			'@IS_BIND' => intval($param_array['is_bind']),
			'@Status' => intval($param_array['status']),
			'@UserCS' => intval($param_array['user_cs_id'])
		);
		$rdata = $this->exec_proc($sql,$sql_data);
		return $rdata;
	}

	/*
		获得CDR需要的字段列表
		$type ： 请求发起的类型，-1表示终端用户登录查询，=0表示运营商的工作人员登录查询，>0表示代理商的工作人员登录查询
		*/
	function get_cdr_fields($type=0){
		switch($type){
			case -1:
				$fields = "Guid_SN, CurrencyType, dbo.SplitString(Session_ID,'@',1) as  SessionID,Session_ID as CDRDatetime,AcctStartTime, PN_E164, CallerID, CallerGWIP, CalleeEndpointNo, CalledID,"
		  				." AcctSessionTime, SessionTimeMin, AcctSessionFee, TerminationCause, Remark";
				break;
			case 0:
				$fields = "Guid_SN, CurrencyType ,dbo.SplitString(Session_ID,'@',1) as  SessionID,Session_ID as CDRDatetime,AcctStartTime, PN_E164, CallerID, CallerGWIP, CalleeEndpointNo, CalledID,"
			  			."CalledGWIP, AcctSessionTime, SessionTimeMin, AcctSessionFee, AgentFee," 
			  			."BaseFee, TerminationCause, Remark,AcctSessionTimeOrg, SessionTimeOrgMin";//CalleeID_Org, 
				break;
			case 1:
				$fields = "Guid_SN, CurrencyType ,dbo.SplitString(Session_ID,'@',1) as  SessionID,Session_ID as CDRDatetime,AcctStartTime, PN_E164, CallerID, CallerGWIP, CalleeEndpointNo, CalledID,"
			  			 ."AcctSessionTime, SessionTimeMin, AcctSessionFee, AgentFee, TerminationCause, Remark";
				break;
			default:
				$fields = "Guid_SN, CurrencyType ,dbo.SplitString(Session_ID,'@',1) as  SessionID,Session_ID as CDRDatetime,AcctStartTime, PN_E164, CallerID, CallerGWIP, CalleeEndpointNo, CalledID,"
			  			 ."AcctSessionTime, SessionTimeMin, AcctSessionFee, AgentFee, TerminationCause, Remark";
				break;
		}
		return $fields;
	}
	/*
		获得CDR列表的函数, AcctStartTime, PN_E164, CallerID, CallerGWIP, CalledID, 
		CalledGWIP

		参数
		$offset : 偏移值，开始的行数
		$count : 取回的行数，以后的会丢弃
		$resaler : 代理商ID，-1表示是终端用户，$endpoint是用户的ID，0表示运营商
		$from : 开始时间
		$to ：结束时间
		$caller : 主叫的通配字符串
		$callee : 被叫的通配字符串
		$endpoint : 终端信息的通配字符串
		*/
	public function billing_cdr_list($offset='',$count='',$resaler=-1,$is_rt='',$from='',$to='',$caller='',$callee='',$endpoint=''){
		$condiction='';
		if(empty($offset))
		$offset = 0;			//默认从头开始
		if(empty($count))
		$count = 15;		//默认一次返回15行
		if(empty($from))
		$from = strftime('%Y-%m-%d %H:%M:%S',mktime(0, 0, 0, date("m")  , date("d")-1, date("Y")));		//默认从当天的0点开始
		if(empty($to))
		$to = strftime('%Y-%m-%d %H:%M:%S',mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));		//默认到今天的12点结束，也就是说from to都是默认值时显示当天的数据
		if(!empty($caller))
		$condiction .= sprintf(' and (CallerID like \'%1$s\' or CallerGWIP like \'%1$s\' ) ',$caller);
		if(!empty($callee))
		$condiction .= sprintf(' and (CalledID like \'%1$s\' or CalledGWIP like \'%1$s\') ',$callee);
		
		if(!isset($resaler))
		$resaler = -1;
		
		$fields = $this->get_cdr_fields($resaler);
		switch($resaler){
			case 0:
				if(!empty($endpoint)){
					$condiction .= sprintf(" and (PN_E164 like '%s') ",$endpoint);
				}
				break;
			case -1:
				if(empty($endpoint)){
					$this->set_return_code(-98);		//请求的参数有误，请联系管理员
					//var_dump($this->api_object->return_data);
					$this->write_warning('请求用户的CDR但是没有提供EndpointID');
					//$this->write_response();
					return false;//die('请求用户的CDR但是没有提供EndpointID');
				}else{
					$condiction .= sprintf(" and PN_E164 = '%s' ",$endpoint);
				}
				break;
			default:
				if(!empty($endpoint)){
					$condiction .= sprintf(" and (PN_E164 like '%s') ",$endpoint);
				}
				$condiction .= sprintf(" and Agent = %d",$resaler);
				break;
		}
		$condiction = sprintf(" where  AcctSessionTime>0 and (AcctStartTime between '%s' and '%s' ) %s ",$from,$to,$condiction);
		if(empty($is_rt)){
			$is_rt = 'vi_cdr';
		}else{
			switch($is_rt){
				case 0:
					$is_rt = 'vi_cdr';
					break;
				case 1:
					$is_rt = 'vi_cdr_history';
					break;
				default:
					$is_rt = 'vi_cdr';
					break;
			}
		}
		$countsql = sprintf('select count(*) as total_count from %2$s  %1$s ',$condiction,$is_rt);
		//echo $sql ."<br>";
		$rc = $this->exec_query($countsql,array());
		if(is_array($rc))
		{
			//var_dump($rc);
			$this->total_count = $rc["total_count"];
			$lr = $this->total_count - $offset;
			if($lr < $count)
				$count = $lr;
			$sql = sprintf('select * from (select top %3$d *,CAST((AcctSessionFee/Round(SessionTimeMin+0.5,0)) as money) as Rate from (select top %2$d %4$s from %5$s  %1$s order by AcctStartTime asc )as a1 order by AcctStartTime desc) as a2 order by AcctStartTime asc',
				$condiction,$offset+$count,$count,$fields,$is_rt);
			fb($sql,FirePHP::INFO);
			$this->rows = array();
			$this->exec_query($sql,array(),billing_handle_append_row,$this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		//$this->set_return_code(101);
		//var_dump($rdata->rows);
		return $this->rows;
	}

	
	/*
	 * 	caption : 获取充值的类型
	 * 	writer : lion wang
	 *	time : 2010-10-22
	 *	
	 */
	public function billing_get_recharge_type()
	{
		$sql = 'SELECT distinct  RC_Code as Type FROM dbo.tb_IncBalance_History';
		$this->rows = array();
		$this->exec_query($sql,array(),billing_handle_append_row,$this);
		return $this->rows;
	}
	
	/*
		获得充值列表信息的函数
		$offset : 偏移值，开始的行数
		$count : 取回的行数，以后的会丢弃
		$resaler : 代理商ID，-1表示是终端用户，$endpoint是用户的ID，0表示运营商
		$from : 开始时间
		$to ：结束时间
		$from_v : 主叫的通配字符串
		$to_v : 被叫的通配字符串
		$endpoint : 终端信息的通配字符串
		*/
	public function billing_balance_history($offset='',$count='',$resaler=-1,$agent,$endpoint,$type,$from,$to)
	{
		$condiction='';
		if(empty($offset))
		$offset = 0;			//默认从头开始
		if(empty($count))
		$count = 15;		//默认一次返回15行
		
		if(empty($from))
		$from = strftime('%Y-%m-%d %H:%M:%S',mktime(0, 0, 0, date("m")  , 0, date("Y")));		//默认从当天的0点开始
		if(empty($to))
		$to = strftime('%Y-%m-%d %H:%M:%S',mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));		//默认到今天的12点结束，也就是说from to都是默认值时显示当天的数据
		
		if(!empty($type))
			//$type = mb_convert_encoding($type,"", "GBK");
			$condiction .= sprintf(" and (RC_Code = '%s')",  $type);
		
		if(!isset($resaler))
		$resaler = -1;
		
		switch($resaler){
			case 0:
				if(!empty($endpoint)){
					$condiction .= sprintf(" and E164 like '%s' ",$endpoint);
				}else{
					if(!empty($agent))
					$condiction .= " and E164 in (select E164 from dbo.tb_Devices where  AgentID in (select AgentID  from dbo.tb_Agents 
														where  AgentID =  $agent or GroupID = $agent or AgentID in (select AgentID from dbo.tb_Agents 
								where GroupID in (select AgentID from dbo.tb_Agents 
								where GroupID = $agent ) ))  )";
					/*$condiction .= sprintf(" and E164 in (select E164 from dbo.tb_Devices where  AgentID in (select AgentID  from dbo.tb_Agents 
														where  AgentID =  $agent or GroupID = $agent or AgentID in (select AgentID from dbo.tb_Agents 
								where GroupID in (select AgentID from dbo.tb_Agents 
								where GroupID = $agent ) ))  )",$agent);*/
				}
				break;
			case -1:
				if(empty($endpoint)){
					$this->set_return_code(-98);		//请求的参数有误，请联系管理员
//					//var_dump($this->api_object->return_data);
//					$this->write_warning('请求用户的充值记录但是没有提供EndpointID');
					$this->write_response();
					exit;//die('请求用户的充值记录但是没有提供EndpointID');
				}else{
					$condiction .= sprintf(" and E164 = '%s' ",$endpoint);
				}
				break;
			default:
				if(!empty($endpoint)){
					$condiction .= sprintf(" and E164 like '%s' ",$endpoint);
				}else{
					if(empty($agent))
						$condiction .= sprintf(" and AgentID = %d", $resaler);
					else
						$condiction .= sprintf(" and E164 in (select E164 from dbo.tb_Devices where  AgentID = %d )",$agent);
			
				}
				break;
		}
		$condiction = sprintf(" where (H_Datetime between '%s' and '%s' ) %s ",$from,$to,$condiction);
		$fields = '(E164 + convert( char(50),[H_Datetime],120)) as id,[H_Datetime], [IncType], [Pin], [E164], [Old_Balance], [Cost], [New_Balance], 
				[RealCost], [UserName], [RC_Code], [Remark], [SourcePin], [h323id], [Guid_SN], [CS_Name], [Agent_Name], [CurrencyType]';
		$sql = sprintf('select * from (select top %3$d * from (select top %2$d %4$s from vi_IncBalanceHistory  %1$s order by H_Datetime asc )as a1 order by H_Datetime desc) as a2 order by H_Datetime asc',
		$condiction,$offset+$count,$count,$fields);
		$countsql = sprintf('select count(*) as total_count from vi_IncBalanceHistory  %1$s ',$condiction);
		//echo $sql ."<br>$countsql<br>";
		$rc = $this->exec_query($countsql,array());
		if(is_array($rc))
		{
			//var_dump($rc);
			$this->total_count = $rc["total_count"];
			//echo $sql;
			$this->rows = array();
			//$this->api_object->push_return_data('sql',$sql);
			$this->exec_query($sql,array(),billing_handle_append_row,$this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		//$this->set_return_code(101);
		//var_dump($rdata->rows);
		return $this->rows;
	}

	/*
	判断是否第一次充值的函数
	$E164 : 偏移值，开始的行数
	*/
	public function billing_first_history($E164)
	{
		$countsql = sprintf('select count(*) as total_count from vi_IncBalanceHistory where E164 = %1$s' ,$E164);
		//echo $sql ."<br>$countsql<br>";
		$rc = $this->exec_query($countsql,array());
		if(is_array($rc))
		{
			//var_dump($rc);
			return $rc["total_count"];
		}else{
			$this->set_return_code(-81);
			return 0;
		}
	}
	
	
	//[ID], [H_Datetime], [RC_Code], [AgentID], [Caption], [BalanceType], [Old_Balance], [Cost], [New_Balance], [RealCost], [UserName], [Remark], [IsRTBalance]
	/*
	获得充值列表信息的函数
	$offset : 偏移值，开始的行数
	$count : 取回的行数，以后的会丢弃
	$resaler : 代理商ID，-1表示是终端用户，$endpoint是用户的ID，0表示运营商
	$from : 开始时间
	$to ：结束时间
	$caller : 主叫的通配字符串
	$callee : 被叫的通配字符串
	$calleeip : 落地地址的通配字符串
	$endpoint : 终端信息的通配字符串
	*/
	public function billing_agent_balance_history($offset='',$count='',$resaler=-1,$from='',$to='',$from_v='',$to_v='')
	{
		$condiction='';
		if(empty($offset))
		$offset = 0;			//默认从头开始
		if(empty($count))
		$count = 15;		//默认一次返回15行
		if(empty($from))
		$from = strftime('%Y-%m-%d %H:%M:%S',mktime(0, 0, 0, date("m")  , 0, date("Y")));		//默认从当天的0点开始
		if(empty($to))
		$to = strftime('%Y-%m-%d %H:%M:%S',mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));		//默认到今天的12点结束，也就是说from to都是默认值时显示当天的数据
		if(!empty($from_v))
		$condiction .= sprintf(' and (Cost >= %1$s)',$from_v);
		if(!empty($to_v))
		$condiction .= sprintf(' and (Cost <= %1$s)',$to_v);
		if(!isset($resaler))
		$resaler = -1;
		switch($resaler){
			case 0:
				break;
			case -1:
				$this->set_return_code(-98);		//请求的参数有误，请联系管理员
				//var_dump($this->api_object->return_data);
				$this->write_warning('请求用户的充值记录但是没有提供EndpointID');
				$this->write_response();
				exit;//die('请求用户的充值记录但是没有提供EndpointID');
				break;
			default:
				$condiction .= sprintf(" and AgentID = %d",$resaler);
				break;
		}
		$condiction = sprintf(" where (H_Datetime between '%s' and '%s' ) %s ",$from,$to,$condiction);
		$fields = '[ID], [H_Datetime], [RC_Code], [AgentID], [Caption], [BalanceType], [Old_Balance], [Cost], [New_Balance], [RealCost], [UserName], [IsRTBalance]';//[Remark],
		$sql = sprintf('select * from (select top %3$d * from (select top %2$d %4$s from vi_AgentIncHistory  %1$s order by H_Datetime asc )as a1 order by H_Datetime desc) as a2 order by H_Datetime asc',
		$condiction,$offset+$count,$count,$fields);
		$countsql = sprintf('select count(*) from vi_AgentIncHistory  %1$s ',$condiction);
		echo $sql ."<br>$countsql<br>";
		$rc = $this->exec_query($countsql,array());
		if(is_array($rc))
		{
			//var_dump($rc);
			$this->total_count = $rc["total_count"];
			//echo $sql;
			$this->rows = array();
			$this->exec_query($sql,array(),billing_handle_append_row,$this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		//$this->set_return_code(101);
		//var_dump($rdata->rows);
		return $this->rows;
	}

	/* writer : lion wang
	 * time : 2010-05-28
	 * caption : ani 用于集团网
	 * 
	 */
	public function billing_ani_list($offset='',$count='',$resaler=-1,$endpoint=''){
		if(empty($offset))
		$offset = 0;			//默认从头开始
		if(empty($count))
		$count = 15;
			
		if ($endpoint == '' or empty($endpoint)) {
			$countsql = 'select count(*) from dbo.tb_MapAni';
			$sql = 'select * from dbo.tb_MapAni';
		}else{
			$countsql = "select count(*) from dbo.tb_MapAni where AniNo = $endpoint or EndpointNo = $endpoint" ;
			$sql = "select * from dbo.tb_MapAni where AniNo = $endpoint or EndpointNo = $endpoint";
		}
		$rc = $this->exec_query($countsql,array());
		if(is_array($rc))
		{
			//var_dump($rc);
			$this->total_count = $rc["total_count"];
			//echo $sql;
			$this->rows = array();
			$this->exec_query($sql,array(),billing_handle_append_row,$this);
			$this->set_return_code(101);
		}else{
			$this->set_return_code(-81);
		}
		return $this->rows;
	}

	//writer : lion wang
	//time : 2010-05-28
	//caption : ani 用于集团网
	public function billing_ani_add($resaler=-1,$pno,$endpoint='',$pin){
		$sql = "dbo.sp_ANI_add;1";
		$sql_data =array(
		    '@ANI' => $pno, 
		    '@E164' => $endpoint, 
		    '@PIN' => $pin 
			
		);

		//var_dump($sql_data);
		$rdata = $this->exec_proc($sql,$sql_data);
		if(is_array($rdata)){
			return $rdata;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}
	}

	//writer : lion wang
	//time : 2010-05-28
	//caption : ani 用于集团网
	public function billing_ani_delete($resaler=-1,$pno){
		$sql = "dbo.sp_ANI_delete;1";
		$sql_data =array(
		    '@ANI' => $pno				
		);

		//var_dump($sql_data);
		$rdata = $this->exec_proc($sql,$sql_data);
		if(is_array($rdata)){
			return $rdata;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}
	}

	//writer : lion wang
	//time : 2010-05-28
	//caption : ani 用于集团网
	public function billing_ani_update($resaler=-1,$o_pno,$n_pno,$n_e164,$n_pin){
		$sql = "dbo.sp_ANI_update;1";
		$sql_data =array(
		   	'@OANI' => $o_pno,
			'@NANI' => $n_pno,
			'@NE164' => $n_e164,
			'@NPIN' => $n_pin	
		);

		//var_dump($sql_data);
		$rdata = $this->exec_proc($sql,$sql_data);
		if(is_array($rdata)){
			return $rdata;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}
	}

	//writer : lion wang
	//time : 2010-07-22
	//caption : 获取代理商计费方案
	public function billing_get_agent_cs($resaler=-1){
		if ($resaler == 0) {
			$sql = "select DISTINCT [CS_ID], [Name], [CurrencyUnit], [CurrencyType], [DefaultGroupID], [Hire], [HireType], [HirePeriod], [OverHireGroupID], [EnableTimeZone], [LimitType], [OneCallFee], [OneTimesFee] from dbo.vi_cs_agent where AgentID = 0";
		}else{
			$sql = "select DISTINCT [CS_ID], [Name], [CurrencyUnit], [CurrencyType], [DefaultGroupID], [Hire], [HireType], [HirePeriod], [OverHireGroupID], [EnableTimeZone], [LimitType], [OneCallFee], [OneTimesFee] from dbo.vi_cs_agent where AgentID = 0 or (AgentID = $resaler)";
		}
		$this->exec_query($sql, array(), billing_handle_append_row, $this);
		return $this->rows;
	}

	//writer : lion wang
	//time : 2010-07-22
	//caption : 获取客户计费方案
	public function billing_get_user_cs($resaler=-1){
		if ($resaler == 0) {
			$sql = "select DISTINCT [CS_ID], [Name], [CurrencyUnit], [CurrencyType], [DefaultGroupID], [Hire], [HireType], [HirePeriod], [OverHireGroupID], [EnableTimeZone], [LimitType], [OneCallFee], [OneTimesFee] from dbo.vi_cs_agent where AgentID = 0 ";
		}else{
			$sql = "select DISTINCT [CS_ID], [Name], [CurrencyUnit], [CurrencyType], [DefaultGroupID], [Hire], [HireType], [HirePeriod], [OverHireGroupID], [EnableTimeZone], [LimitType], [OneCallFee], [OneTimesFee] from dbo.vi_cs_agent where AgentID = 0 or (AgentID = $resaler)";
		}
		
		$this->exec_query($sql, array(), billing_handle_append_row, $this);
		return $this->rows;
	}

	//writer : lion wang
	//time : 2010-07-22
	//caption : 增加代理商
	public function billing_add_agent($add_agent_array){
		/*
		 CREATE	PROCEDURE dbo.sp_agent_add_info
		 (
			@AgentName	varchar(100),
			--'@Password
			@Caption	varchar(200),
			@Address	varchar(200),
			@Leader		varchar(100),
			@CurrencyType 	varchar(100),
			@agtCurrencyType varchar(100),
			@Connect	varchar(100),
			@Note	varchar(100),
			@DefaultGuestCS	int,
			@DefaultAgentCS	int,
			@Prefix	varchar(100),
			@GroupID	int,
			@IsReal		int =0,
			@p_return	int =0 output
			)--WITH ENCRYPTION
			*/
		$sql = "dbo.sp_agent_add_info;1";
		$sql_data =array(
		   	"@AgentName" => $add_agent_array["agent_Name"], 
			"@Caption"	=> $add_agent_array["caption"],
			"@Address" => $add_agent_array["address"],
			"@Leader" => $add_agent_array["leader"],
			"@CurrencyType" => $add_agent_array["currency_type"],
			"@agtCurrencyType" => $add_agent_array["agt_currency_type"],
			"@Connect" => $add_agent_array["connect"],
			"@Note" => $add_agent_array["note"],
			"@DefaultGuestCS" => $add_agent_array["cs_user_id"],
			"@DefaultAgentCS" => $add_agent_array["cs_agent_id"],
			"@Prefix" => $add_agent_array["prefix"],
			"@GroupID" => $add_agent_array["superior_agnet"],
			"@IsReal" =>  $add_agent_array["is_real"]	
		);
		$rdata = $this->exec_proc($sql,$sql_data);
		if(is_array($rdata)){
			return $rdata;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}
	}

	//writer : lion wang
	//time : 2010-08-31
	//caption : 获取代理商
	public function billing_get_agent_name($resaler=-1){
		if($resaler == 0){
			$sql = 'select Agent_Name,Caption, AgentID from dbo.tb_Agents';
		}else{
			$sql = ' select Agent_Name,Caption, AgentID from dbo.tb_Agents where AgentID = '.$resaler.' or GroupID = '.$resaler.' or 
	AgentID in (select AgentID from dbo.tb_Agents where GroupID in (select AgentID from dbo.tb_Agents where GroupID = '.$resaler.'))';
		}
		$this->exec_query($sql, array(), billing_handle_append_row, $this);
		return $this->rows;
	}
	/******************** Billing  End*******************************/
	/**
	 * 回拨呼叫认证函数，
	 */
	public function callback_invite_auth($params){
		$return_code = 0;
		$rdata = array();
		//首先验证回拨主叫
		$r = radius_invite_auth(array(
			"pin" => $params['pin'],
			"pass" => $params['pass'],
			"caller" => $params["pno"],
			"callerip" => $params["callerip"], 
			"callee" => $params["caller"]
		),$this->api_object->config);
		if(is_array($r)){
			//var_dump($r);
			foreach ($r as $k=>$v)
			$rdata['caller-'.$k] = $v;		//保存主叫认证信息
			if($r['RADIUS_RESP'] == RADIUS_ACCESS_ACCEPT){
				//Radius接受请求
				//认证回拨被叫
				$r = radius_invite_auth(array(
					"pin" => $params['pin'],
					"pass" => $params['pass'],
					"caller" => $params["pno"], 
					"callerip" => $params["callerip"], 
					"callee" => $params["callee"]
				),$this->api_object->config);
				if(is_array($r)){
					//var_dump($r);
					foreach ($r as $k=>$v)
					$rdata['callee-'.$k] = $v;		//保存被叫认证信息
					if($r['RADIUS_RESP'] == RADIUS_ACCESS_ACCEPT){
						$return_code = $r['RETURN-CODE'];
						//var_dump($rdata);
					}else{
						$return_code = $r['RETUEN-CODE'] - 2000;  //认证回拨被叫失败
					}
				}else{
					$return_code = $r - 2000;	//认证回拨被叫radius错误
				}
			}else{
				$return_code = $r['RETURN-CODE'] - 1000; //认证回拨主叫失败
			}
		}else{
			$return_code = $r - 1000;  //认证回拨主叫radius发生错误
		}
		$this->set_return_code($return_code);
		if($return_code < 0){
			$this->rows = $rdata;
			return $this->rows;
			//$this->api_object->write_response();
			//exit;		//程序结束
		}else{
			$this->rows = $rdata;
			return $this->rows;
		}
	}

	/**
	 * 加载billing存储过程的函数，加载完成后将返回结果存储在数组里返回
	 *  $proc   : 存储过程名称
	 *  $params : 存储过程所需要的参数，请记得为存储过程的所有参数赋值，如果是输出参数，
	 * 存储过程没有给出默认值时，在这里也要赋值。否则在RB上会看到格式转换错误的异常。
	 */
	public function exec_billing_proc($proc,$params)
	{
		$r = $this->exec_proc($proc,$params);
		if(is_array($r)){
			//Radius执行成功
			$this->rows = array();
			foreach ($r as $k => $v)
			{
				if($k[0] == '@')
				$this->rows[substr($k,1)] = $v;
				else
				$this->rows[$k] = $v;
			}
			//$this->rows['RETURN-CODE'] = $r['h323_return_code'];
			return $this->rows;
		}else{
			//Radius执行失败
			return $r;
		}
	}
	/**
	 * 呼叫认证函数，会调用radius的存储过程，可用于检验radius的正确性
	 * 输入参数：
	 * 	$param : 输入的参数数组
	 * 返回：
	 * 	返回radius返回的数组
	 *  $api_obj->return_code表示radius返回的代码
	 */
	public function billing_invite_auth($params){
		$sql = "dbo.sp_InvitAuth;1";
		$params = array(
			"@ProxyServer" => $_SERVER['HTTP_HOST'],			//服务器地址 
			"@CallerH323id" => $params['pin'], 					//呼叫请求的帐号，如果是CLI则需要是呼叫类型为CLI的帐号
			"@CallerIP" => $params['callerip'],				//主叫的Ip地址
			"@CallerID" => $params['caller'],  					//主叫号码，如果是CLI，则应该是PSTN的主叫号码，在这里号码需要替换为全国码的电话号码
			"@Password" => $params['pass'], 					//帐户密码，如果是CLI忽略密码
			"@CalledID" => $params['callee'] ,					//拨打的被叫号码
			"@MaxTime" => 0										//输出的最大通话时长的参数，在这里给出初始值，原因是存储过程没有给它默认值
		);
		return $this->exec_billing_proc($sql,$params);
	}
	/**
	 * 回拨认证函数
	 */
	public function billing_cb_invite_auth($params){
		$sql = "dbo.sp_InvitAuth;1";
		$sqlparams = array(
			"@ProxyServer" => $_SERVER['HTTP_HOST'],			//服务器地址 
			"@CallerH323id" => $params['pin'], 					//呼叫请求的帐号，如果是CLI则需要是呼叫类型为CLI的帐号
			"@CallerIP" => $params['callerip'],				//主叫的Ip地址
			"@CallerID" => $params['pno'],  					//主叫号码，如果是CLI，则应该是PSTN的主叫号码，在这里号码需要替换为全国码的电话号码
			"@Password" => $params['pass'], 					//帐户密码，如果是CLI忽略密码
			"@CalledID" => $params['caller'] ,					//拨打的被叫号码
			"@MaxTime" => 0										//输出的最大通话时长的参数，在这里给出初始值，原因是存储过程没有给它默认值
		);
		$ra = $this->exec_billing_proc($sql,$sqlparams);
		
		$this->api_object->return_code = isset($ra['RETURN-CODE'])?$ra['RETURN-CODE']:$this->api_object->return_code;
		$this->api_object->push_return_data('caller-auth',$ra);
		if($this->api_object->return_code <= 0){
			$this->api_object->return_code = -1000 - $this->api_object->return_code;
			return $ra;
		}else{
			//开始验证被叫
			$sqlparams = array(
				"@ProxyServer" => $_SERVER['HTTP_HOST'],			//服务器地址 
				"@CallerH323id" => $params['pin'], 					//呼叫请求的帐号，如果是CLI则需要是呼叫类型为CLI的帐号
				"@CallerIP" => $params['callerip'],				//主叫的Ip地址
				"@CallerID" => $params['pno'],  					//主叫号码，如果是CLI，则应该是PSTN的主叫号码，在这里号码需要替换为全国码的电话号码
				"@Password" => $params['pass'], 					//帐户密码，如果是CLI忽略密码
				"@CalledID" => $params['callee'] ,					//拨打的被叫号码
				"@MaxTime" => 0										//输出的最大通话时长的参数，在这里给出初始值，原因是存储过程没有给它默认值
			);
			$rb = $this->exec_billing_proc($sql,$sqlparams);
			$this->api_object->return_code = isset($rb['RETURN-CODE'])?$rb['RETURN-CODE']:$this->api_object->return_code;
			$this->api_object->push_return_data('callee-auth',$rb);	
			if($this->api_object->return_code <= 0){
				$this->api_object->return_code = -2000 - $this->api_object->return_code;
				return $rb;
			}else{
				return $this->api_object->return_data;
			}
		}
	}

	public function billing_create_account($params)
	{
		$balance = isset($params['balance'])? $params['balance'] : $this->api_object->config->default_active['Balance'];
		$sql = "sp_billing_create_account;1";
		$params = array(
			"@Caller"  => $params['caller'],			//--The bind caller number for the new endpoint 	
			"@AgentID" => $this->api_object->config->default_active['agent'],							//--Agent ID for the new endpoint
			"@Password" => $params['pass'],					//--The password for the new endpoint
			"@Balance" => $balance,						//--The init balance for the new endpoint 
			"@FreePeriod" => $this->api_object->config->default_active['FreePeriod'],					//--The init free period for the new endpoint
			"@FreeDuration" => $this->api_object->config->default_active['FreeDuration'],				//--The init free duration for the new endpoint
			"@Valid_day" =>	$this->api_object->config->default_active['Valid_day']			//--valid date
			,'@user_cs' => 0
			);
		return $this->exec_billing_proc($sql,$params);
	}
	
	public function billing_get_ani_account($params)
	{
		$sql = "dbo.sp_billing_get_ani;1";
		$params = array(
			"@CallerID" => $params['caller'], 
			"@EndpointNo" => '',
			"@Password" => ''
			);
		return $this->exec_billing_proc($sql,$params);
	}
	
	public function billing_bind_cli($params){
		$sql = "dbo.sp_billing_bind_cli_ext;1";
		$params = array(
			"@CardNo" => $params['pin'], 						//要绑定的E164帐号，如果是充值卡帐号(pin)，则存储过程需要首先创建E164帐号，然后
			"@Password" => $params['pass'], 					//帐号密码
			"@CallerID" => $params['caller']					//要绑定的主叫号码，号码规则为全国码的电话号码
			,"@AgentID" => $this->api_object->config->default_active['agent']
			,"@RemainMoney" => '0'			  					// 返回的充值金额
			,"@CurrencyType" => ''			  					// 返回金额的货币类型
			,"@BillingMode"  => 1			  					// 返回的计费类型
			);
		return $this->exec_billing_proc($sql,$params);
	}
	
	public function billing_query_balance($params){
		$sql = "dbo.sp_QueryBalance;1";
		$params = array(
			'@UserName' => $params['pin'],						//要查询的CLI帐号，也就是绑定了帐号的PSTN主叫号码，
			'@CallerID' => $params['caller'],					//发起查询的PSTN主叫号码
			'@Password' => $params['pass'],						//帐号密码，如果不是本机查询则需要有密码才可以查询
			'@CallType' => 'CLI', 								//呼叫类别，主叫绑定的类别是CLI		--CallType ,VoIP/Tele/Card/CLI
			'@RemainMoney' => '0', 								//返回的参数
			'@CurrencyType' => 'CNY', 							//货币类型
			'@BillingMode' => 1,								//计费模式  --0 = credit;1 || 2 =debit¡£ h323-billing-model
			'@preferred_lang' => 'cn'							//语音提示语言 'cn' output --this is return languageID 
		);
		return $this->exec_billing_proc($sql,$params);
	}

	public function billing_recharge($params){
		$sql = "dbo.sp_RechargeBalance;1";
		$params = array(
			"@UserName" => $params['caller'],    					// 欲充值的CLI帐号	
			"@CallerID" => $params['caller'], 					// 发起充值的PSTN主叫号码
			"@CardNo" => $params['cardno'],   					// 充值卡号码		--CardNo
			"@Password" => $params['pass'] ,  					// 充值卡密码   	--CLI对应的密码
			"@RemainMoney" => '0',			  					// 返回的充值金额
			"@CurrencyType" => '',			  					// 返回金额的货币类型
			"@BillingMode"  => 1			  					// 返回的计费类型
		);
		return $this->exec_billing_proc($sql,$params);
	}
	
	public function billing_modify_password($params)
	{
		$sql = 'dbo.sp_ModifyPassword;1';
		$params = array(
			'@EndpointNo'	=> $params['pin'],	// varchar(50),	--µç»°ºÅÂë
			'@Password' 	=> $params['pass'],	//varchar(50), -- Org Password
			'@NewPassword'	=> $params['newpass']
			);
		return $this->exec_billing_proc($sql,$params);
	}
	
	
	/**
	 *	creater: lion wang
	 *	time: 2010.6.1
	 *	@param: $resaler:  agent id
	 *			$offset
	 *			$count		limite
	 *	caption: get agent list by agent id
	 **/
	public function billing_get_agent_tree($resaler=-1){
		$this->rows = array();
		if(!isset($resaler)){
			$this->write_warning('Server Error');
			return -1;
		}
		$sql = 'select Agent_Name, AgentID from dbo.tb_Agents where GroupID = '.$resaler;
		$this->exec_query($sql, array(), billing_handle_append_row, $this);
		return $this->rows;
	
	}
	
	/**
	 *	creater: lion wang
	 *	time: 2010.6.1
	 *	@param: $resaler:  agent id
	 *			$offset
	 *			$count		limite
	 *	caption: get agent list by agent id
	 **/
	public function billing_get_agent_list($offset='', $count='', $resaler=-1){
		$sql = 'select AgentID,Agent_Name,Caption,HireBalance, CAST(CAST(Balance  AS decimal(38,4))/dbo.GetDefaultCurrencyUnit() as  decimal(38,4)) as Balance,CAST(CAST(RealBalance  AS decimal(38,4))/dbo.GetDefaultCurrencyUnit() as  decimal(38,4)) as RealBalance ,IsReal,CurrencyType,agtCurrencyType,Default_AgentCS,Prefix,ChargeScheme,Address,Leader,Connect,EMail from dbo.tb_Agents where GroupID = '.$resaler;
		$this->rows = array();
		$this->exec_query($sql, array(), billing_handle_append_row, $this);
		return $this->rows;
	}
	
	/**
	 *	creater: lion wang
	 *	time: 2010.6.1
	 *	@param: $resaler:  agent id
	 *			$offset
	 *			$count		limite
	 *	caption: get agent info by agent id
	 **/
	public function billing_get_agent_info($sub_resaler, $resaler = -1){
		if ($resaler == 0) {
			$sql = 'select AgentID,Agent_Name,Caption,HireBalance, CAST(CAST(Balance  AS decimal(38,4))/dbo.GetDefaultCurrencyUnit() as  decimal(38,4)) as Balance,CAST(CAST(RealBalance  AS decimal(38,4))/dbo.GetDefaultCurrencyUnit() as  decimal(38,4)) as RealBalance ,IsReal,CurrencyType,agtCurrencyType,Default_AgentCS,Prefix,ChargeScheme,Address,Leader,Connect,EMail from dbo.tb_Agents where AgentID = '.$sub_resaler;
		}else{
			$sql = 'select AgentID,Agent_Name,Caption,HireBalance, CAST(CAST(Balance  AS decimal(38,4))/dbo.GetDefaultCurrencyUnit() as  decimal(38,4)) as Balance,CAST(CAST(RealBalance  AS decimal(38,4))/dbo.GetDefaultCurrencyUnit() as  decimal(38,4)) as RealBalance,IsReal,CurrencyType,agtCurrencyType,Default_AgentCS,Prefix,ChargeScheme,Address,Leader,Connect,EMail from dbo.tb_Agents where AgentID = '.$sub_resaler.'AND GroupID ='.$resaler ;
		}
		//echo $sql;
		$this->rows = array();
		$rows = $this->exec_query($sql, array(), billing_handle_append_row, $this);
		return $this->rows;
	}

	public function billing_edit_agent_info($sub_resaler,$resaler, $resaler_data){
		//首先检查子代理商是否属于登录代理商的子代理商
		//然后合成更新列表
		$resaler_info = array_to_string(',',$resaler_data);
		//调用更新函数
		$sql = sprintf('update dbo.tb_Agents set %s  where AgentID = %d',$resaler_info,$sub_resaler);
		$this->api_object->write_hint(array('sql'=>$sql));
		$this->exec_db_send_sp($sql, array());
		return 1;
	}
	
	public function billing_recharge_by_pin($recharge_array){
		//$recharge_array =  radius_execute_proc("dbo.sp_Ophone_Recharge_beta;1",$parm);
		//执行存储过程，赋值参数
		$sql = 'sp_Ophone_Recharge_beta;1';
		$rdata = $this->exec_proc($sql,array(
			'@SourcePin' => $recharge_array['rpin'], 
	        '@PinPassword' => $recharge_array['rpass'], 
	        '@CallerID' => $recharge_array['pin']
		)
		);
		//var_dump($rdata);
		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->rows = array();
			foreach ($rdata as $k => $v)
			{
				if($k[0] == '@')
				$this->rows[substr($k,1)] = $v;
				else
				$this->rows[$k] = $v;
			}
			//$this->api_object->push_return_data('data',$rdata);
			$this->write_hint($rdata);
			return $this->rows;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}
	}
	
	/*
	 billing web通过充值卡充值
	 */
	public function billing_recharge_balance($recharge_array){
		//$recharge_array =  radius_execute_proc("dbo.sp_Ophone_Recharge_beta;1",$parm);
		//执行存储过程，赋值参数
		$sql = 'sp_IncBalanceByBilling;1';
		$rdata = $this->exec_proc($sql,array(
			'@SourcePin' => $recharge_array['rpin'], 
	        '@PinPassword' => $recharge_array['rpass'], 
	        '@CallerID' => $recharge_array['pin'],
			'@Balance' => $recharge_array['value']
			)
		);
		//var_dump($rdata);
		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			return $rdata;
		}else{
			$this->set_return_code(-10);
			//$this->set_return_data('data',$rdata);
			$this->write_response();
			exit;
		}
	}
	
	
	/**
	 * description:
	 * 		get database version from mssql database
	 */
	public function get_db_version(){
		$sql = "dbo.sp_GetDatabaseVersion;1";
		$params = array();
		$r = $this->exec_billing_proc($sql,$params);
		if(is_array($r)){
			return $r['Version'];
		}else{
			return '';
		}
	}

	/**
	 * description:
	 * 		get child agents count
	 */
	public function get_sub_resaler_count($resaler){
		$sql = sprintf("select count(*) as acount from tb_Agents where GroupID = %s",$resaler);
		$params = array();
		$r = $this->exec_query($sql,$params);
		if(is_array($r)){
			return $r['acount'];
		}else{
			return '0';
		}
	}
	
	public function get_sub_account_count($resaler){
		$sql = sprintf("select count(*) as acount from tb_devices where AgentID = %s",$resaler);
		$params = array();
		$r = $this->exec_query($sql,$params);
		if(is_array($r)){
			return $r['acount'];
		}else{
			return '0';
		}
	}
	
	public function get_traffic($from,$to,$t,$resaler){
		/*
select SUM(s_Count) as s_Count,
	SUM(s_Connected) as s_Connected,
	SUM(s_Durations) as s_Durations,
	SUM(s_Cost) as s_Cost,
	SUM(s_AgentCost) as s_AgentCost ,
	SUM(s_TerminationCost) as s_TerminationCost 
from  tb_DurationStatisticsDay  
where  CAST((CAST( s_Year as nvarchar)+'-'+CAST( s_Month as nvarchar)+'-'+CAST(s_Day as nvarchar)) AS datetime )  
 between @StartDateTime and @EndDateTime  and s_Terminations = '%-%' and (s_Agent = 0)

select *  from dbo.tb_DurationStatisticsDay 
where  CAST((CAST( s_Year as nvarchar)+'-'+CAST( s_Month as nvarchar)+'-'+CAST(s_Day as nvarchar)) AS datetime )  
 between @StartDateTime and @EndDateTime  and s_Terminations = '%-%' and (s_Agent = 0)		*/
		//'cdrdate', 'calls', 'connecteds','asr','session','fee','base_fee','ctype'
		$rdate = "CAST( s_Year as nvarchar)+'-'+CAST( s_Month as nvarchar)+'-'+CAST(s_Day as nvarchar)";
		if($resaler = 0)
			$fields = "s_Count as calls,s_Connected as connecteds,s_Durations as session,s_Cost as fee,s_AgentCost as base_fee";
		else 
			$fields = "s_Count as calls,s_Connected as connecteds,s_Durations as session,s_Cost as fee,s_TerminationCost as base_fee";
		$where = sprintf(" CAST(($rdate) AS datetime ) between '%s' and '%s'  and s_Terminations = '%s' and (s_Agent = %s)"
			,$from,$to,$t,$resaler);
		
		$tsql = "select 'Total' as cdrdate,$fields from tb_DurationStatisticsDay where $where";
		$dsql = "select ($rdate) as cdrdate,$fields from tb_DurationStatisticsDay where $where";
		$this->rows = array();
		$this->api_object->push_return_data('sql',$dsql);
		$rows = $this->exec_query($dsql, array(), billing_handle_append_row, $this);
		return $this->rows;
	}
	
	/*
	 * 根据被叫号码和当前账号的计费方案获取本次通话的费率
	 * */
	public function liuliu_get_rate($params){
		//var_dump($params);
		$sql_data = array(
			'@E164' => $params['v_pin'],
			'@v_pno' => $params['v_pno']
		);
		//response from radius by produre
		$sql = "sp_liuliucall_GetReta;1";
		//var_dump($sql_data);
		$rdata = $this->exec_proc($sql,$sql_data);
		//var_dump($rdata);
		if(is_array($rdata)){
			//解析VoIP数据库存储过程返回的参数
			$this->rows = array();
			foreach ($rdata as $k => $v)
			{
				if($k[0] == '@')
				$this->rows[substr($k,1)] = $v;
				else
				$this->rows[$k] = $v;
			}
			//$this->api_object->push_return_data('data',$rdata);
			//$this->write_hint($rdata);
			return $this->rows;
		}else{
			$this->set_return_code($rdata);
			$this->write_response();
			exit;
		}
	}
	
	/**
	 * 查询VoIP费率，对于分段费率查询只做第一段
	 */
	public function billing_get_rate($params)
	{
		$sql = "dbo.sp_billing_get_rate;1";
		$params = array(
			'@E164' => $params['pin']
			,'@callee' => $params['callee']
			,'@rate' => ''
			,'@currency_type' => ''
			);
		return $this->exec_billing_proc($sql,$params);
	}
	/**
	 * 查询回拨费率，对于分段费率只查询到第一段
	 */
	public function billing_get_callback_rate($params)
	{
		$sql = "dbo.sp_billing_get_callback_rate;1";
		$params = array(
			'@E164' => $params['pin']
			,'@caller' => $params['caller']
			,'@callee' => $params['callee']
			,'@rate' => ''
			,'@currency_type' => ''
			);
		return $this->exec_billing_proc($sql,$params);
	}
	/**
	 * 代理商为其客户充值
	 */
	public function billing_resaler_recharge($params)
	{
		$sql = "dbo.sp_billing_resaler_recharge;1";
		$params = array(
			'@resaler' => $params['resaler']
			,'@pass' => $params['pass']
			,'@E164' => $params['pin']
			,'@value' => $params['value']
			,'@type' => $params['type']
			,'@remark' => $params['remark']
			);
		return $this->exec_billing_proc($sql,$params);
	}
	
	/**
	 * 查询充值卡的状态、余额和密码
	 */
	public function billing_get_card_info($pin){
		$sql = sprintf("select *,CAST(Balance AS money) / dbo.GetDefaultCurrencyUnit() AS BalanceM from tb_account where pin = %d",$pin);
		$this->exec_query($sql,array(),billing_handle_endpoint_append_row,$this);
		if(count($this->rows)> 0){
			$this->set_return_code(101);
			return $this->rows[0];
		}else{
			$this->set_return_code(-101);
			return false;
		}
	}

	/**
	 * 解除号码绑定
	 */
	public function billing_unbind($pno){
		$sql = "dbo.sp_ANI_delete;1 ";
		return $this->exec_billing_proc($sql,array('@ANI'=>$pno,'@p_return'=>0));
	}
	
	/**
	 * 1 成功
	 * 
	 * -1 卡号不存在
	 */
	public function billing_use_pin($pin){
		$sql = "dbo.sp_billing_use_pin;1";
		$params = array(
			'@pin' => $pin
			,'@Password' => ''
			,'@Balance' => ''
			);
		return $this->exec_billing_proc($sql,$params);
	}

	/**
	 * @pin int,
	@agent int,
	@cs_id int,
	@CurrencyType nvarchar(20),
	@Balance nvarchar(50),
	@Password nvarchar(50)
	 */
	public function billing_add_pin($params){
		$sql = "dbo.sp_billing_create_pin;1";
		$params = array(
			'@pin' => $params['pin']
			,'@agent' => $params['agent']
			,'@cs_id' => $params['cs_id']
			,'@CurrencyType' => $params['currency']
			,'@Balance' => $params['balance']
			,'@Password' => $params['pass']
			);
		return $this->exec_billing_proc($sql,$params);
	}

	/**
	 * @ProxyServer nvarchar(50),
	@Session_ID nvarchar(100),
	@CallerGateway nvarchar(50)='',
	@CallerGWIP nvarchar(50),
	@CallerID nvarchar(50),
	@CalleeGateway nvarchar(50)='',
	@CalleeGWIP nvarchar(50),
	@CalleeID nvarchar(50),
	@AcctStartTime datetime,
	@SetupTime datetime,
	@ConnectedTime datetime,
	@DisconnectedTime datetime,
	@AcctSessionTime integer,
	@ServiceType integer = 0,
	@InBytes integer = 0,
	@OutByte integer = 0,
	@EnCodecType integer = 0,
	@DeCodecType integer = 0,
	@TerminationCause varchar(50) = ''
	 */
	public function billing_update_cdr($params)
	{
		/*$sql = "dbo.sp_UpdateCallLog;1";
		$params = array(
			'@ProxyServer' => $params['server']
			,'@AcctStatusType' => 2
			,'@Session_ID' => $params['sessionid']
			,'@CallerGateway' => $params['pin']
			,'@CallerGWIP' => $params['callerip']
			,'@CallerID' => $params['caller']
			,'@CalleeGateway' => ''
			,'@CalleeGWIP' => $params['calleeip']
			,'@CalleeID' => $params['callee']
			,'@StartCallTime' => $params['start']
			,'@SetupTime' => $params['start']
			,'@AlertingTime' => $params['start']
			,'@ConnectedTme' => $params['answer']
			,'@DisconnectedTime' => $params['end']
			,'@AcctSessionTime' => intval($params['billsec'])
			,'@ServiceType' => 0
			,'@InBytes' => 0
			,'@OutBytes' => 0
			,'@EnCodecType' => 0
			,'@DecodecType' => 0
			,'@TerminationCause' => '0'
			);
		return $this->exec_billing_proc($sql,$params);
		*/
		return radius_account_req($params,$this->api_object->config);
	}
}

?>

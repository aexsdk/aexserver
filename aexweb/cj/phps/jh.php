<?php
	include_once 'pdo_db.php';
	include_once 'mssql.php';
	$db = new pdo_db ( );
	$cfgs = parse_ini_file('conf.ini',true);
	$key = $cfgs['KEYCODE']['key.code'];
	define('_KEY_',$key);

	$FirstName = addslashes(trim($_POST['FirstName'])) ? addslashes(trim($_POST['FirstName'])) : null;
	$LastName = addslashes(trim($_POST['LastName'])) ? addslashes(trim($_POST['LastName'])) : null;
	$name = $FirstName . ' ' . $LastName;
	$EMailAddress = addslashes(trim($_POST['EMailAddress'])) ? addslashes(trim($_POST['EMailAddress'])) : null;
	$CardID = addslashes(trim($_POST['CardID'])) ? addslashes(trim($_POST['CardID'])) : null;
	$Address = addslashes(trim($_POST['Address'])) ? addslashes(trim($_POST['Address'])) : null;
	$City = addslashes(trim($_POST['City'])) ? addslashes(trim($_POST['City'])) : null;
	$ZipCode = addslashes(trim($_POST['ZipCode'])) ? addslashes(trim($_POST['ZipCode'])) : null;
	$Country = addslashes(trim($_POST['Country'])) ? addslashes(trim($_POST['Country'])) : null;
	$CellPhone = addslashes(trim($_POST['CellPhone'])) ? addslashes(trim($_POST['CellPhone'])) : null;
	$FixedPhone = addslashes(trim($_POST['FixedPhone'])) ? addslashes(trim($_POST['FixedPhone'])) : null;
	$Fax = addslashes(trim($_POST['Fax'])) ? addslashes(trim($_POST['Fax'])) : null;
	//$active = addslashes(trim($_POST['active'])) ? addslashes(trim($_POST['active'])) : null;
	$getclientinfo = trim($_POST['getclientinfo']);
	$decode = md5_decrypt(hex2buf($getclientinfo),_KEY_);
	//$decode = "1001,6003,13888888888,05d0338c-5187-48d3-ad37-3ad9ebfabead";
	$decodeArr = explode(',',$decode);
	
	//获取guid和产品属性  调用客户端的函数过程
	/*$arr = "1001,6003,ABCDEF1234567890,05d0338c-5187-48d3-ad37-3ad9ebfabead";*/
	$v_id = $decodeArr[0];
	$p_id = $decodeArr[1];
	$attribute = $decodeArr[2];
	$gu_id = $decodeArr[3];
	$n_free_period = '';
	$n_hire_number = '';
	$rows = array();
	
	//SQL 返回值 =2  该设备已经激活过 = 1	信息验证正确，允许激活 = -1 该设备没有入库 = -2 该设备没有出库 =-4; 获取参数信息失败
	try {
		$sql = "SELECT * FROM ez_wfs_db.sp_wfs_get_devices_active_info( '$gu_id', '$attribute', '$v_id', '$p_id')";
		$rows = array();
		$rows = $db->query ( $sql );
		
		$n_return_value = intval($rows[0]['n_return_value']);
		$v_v_date = $rows[0]['v_v_date'];
		$v_account = $rows[0]['v_account'];
		$v_account1 = $rows[0]['v_account'];
		$v_password = $rows[0]['v_password'];
		$n_agent_id = $rows[0]['n_agent_id'];
		$n_balance = $rows[0]['n_balance'];
		$v_currency_type = $rows[0]['v_currency_type'];
		$n_free_period = $rows[0]['n_free_period'];
		$n_hire_number = $rows[0]['n_hire_number'];
		$n_agent_cs = $rows[0]['n_agent_cs'] ? $rows[0]['n_agent_cs'] : 0;
		$n_call_cs = $rows[0]['n_call_cs'] ? $rows[0]['n_call_cs'] : 0;
		$v_agent_prefix = $rows[0]['v_agent_prefix'] ? $rows[0]['v_agent_prefix'] : 'null';
		$v_product_type_prefix = $rows[0]['v_product_type_prefix'] ? $rows[0]['v_product_type_prefix'] : 'null';
		$v_cs_prefix = $rows[0]['v_cs_prefix'] ? $rows[0]['v_cs_prefix'] : 'null';
		$n_valid_date_no = $rows[0]['n_valid_date_no'];
		switch ($n_return_value) {
			case 1:
				//echo '信息验证正确，允许激活';
				//在VoIP数据库创建帐号，获取帐号和密码 E164  password
				$e164Arr = array();
				$setArr = array('gu_id' => $gu_id,'wfs_attribute' => $attribute,'agent_id' => $n_agent_id,'agent_cs' => $n_agent_cs,'call_cs' => $n_call_cs,'balance' => $n_balance,'currency_type' => $v_currency_type,'valid_date_no' => $n_valid_date_no,'hire_number' => $n_hire_number,'free_period' => $n_free_period,'product_type_prefix' => $v_product_type_prefix,'cs_prefix' => $v_cs_prefix,'agent_prefix' => $v_agent_prefix);
				$e164Arr = getE164($setArr);
				$n_return_value1 = $e164Arr['0'];
				$v_account = $e164Arr['1'];
				$v_password = $e164Arr['2'];
				$valid_date = $e164Arr['3'];
				
				//获取E164就进行post信息填写
				switch ($n_return_value1) {
					case 1:
						$sql2 = "SELECT * FROM ez_wfs_db.sp_wfs_devices_active( '$gu_id', '$attribute', '$v_account', '$v_password', '$name', '$EMailAddress', '$CardID', '$Address', '$City', '$Country', '$ZipCode', '$FixedPhone', '$CellPhone', '$Fax')";
						$rows2 = $db->query($sql2);
						
						$n_return_value2 = $rows2[0]['n_return_value'];
						$v_v_date2 = $rows2[0]['v_v_date'];
						if ($n_return_value2 == 1) {
							//echo '激活成功';
							$info = $gu_id . ',' . $v_account . ',' . $v_password;
							$info  =  buf2hex(md5_encrypt($info,_KEY_));
							$v_account = buf2hex(md5_encrypt($v_account,_KEY_));
							$v_password = buf2hex(md5_encrypt($v_password,_KEY_));
							echo "1,$v_account,$v_password,$info";
						} else if ($n_return_value2 == -2) {
							//echo '生成用户信息失败';
							echo -5;
						} else if ($n_return_value2 == -3) {
							//echo '生成帐号信息失败';
							echo -3;
						} else {
							//echo '数据异常';
							echo $rows2;
						}
					break;
					
					case 2:
						$sql2 = "SELECT * FROM ez_wfs_db.sp_wfs_devices_active( '$gu_id', '$attribute', '$v_account', '$v_password', '$name', '$EMailAddress', '$CardID', '$Address', '$City', '$Country', '$ZipCode', '$FixedPhone', '$CellPhone', '$Fax')";
						$rows2 = $db->query($sql2);
						
						$n_return_value2 = $rows2[0]['n_return_value'];
						$v_v_date2 = $rows2[0]['v_v_date'];
						if ($n_return_value2 == 1) {
							//echo '激活成功';
							$info = $gu_id . ',' . $v_account . ',' . $v_password;
							$info  =  buf2hex(md5_encrypt($info,_KEY_));
							$v_account = buf2hex(md5_encrypt($v_account,_KEY_));
							$v_password = buf2hex(md5_encrypt($v_password,_KEY_));
							echo "1,$v_account,$v_password,$info";
						} else if ($n_return_value2 == -2) {
							//echo '生成用户信息失败';
							echo -5;
						} else if ($n_return_value2 == -3) {
							//echo '生成帐号信息失败';
							echo -3;
						} else {
							//echo '数据异常';
							echo $rows2;
						}
					break;
					
					case 0 : 
						//echo '插入E164失败';
						echo -100;
					break;
					
					case -1 : 
						//echo '插入话费帐号失败';
						echo -101;
					break;
					
					case -2 : 
						//echo '插入会员费帐号失败';
						echo -102;
					break;
					
					case -3 : 
						//echo '获取号码失败';
						echo -103;
					break;
					
					default:
						//echo '数据异常';
						echo $e164Arr;
					break;
				}
			break;
			
			case 2:
				//echo '该设备已经激活过';
				$v_account1 = buf2hex(md5_encrypt($v_account1,_KEY_));
				$v_password = buf2hex(md5_encrypt($v_password,_KEY_));
				echo "2,$v_account1,$v_password";
			break;
			
			case -1:
				//echo '该设备没有入库';
				echo -1;
			break;
			
			case -2:
				//echo '该设备没有出库';
				echo -2;
			break;
			
			case -4:
				//echo '获取参数信息失败';
				echo -4;
			break;
			
			default:
				//echo '数据异常';
				echo 123;
			break;
		}
	} catch (Exception $e) {
		$rows = $e->getMessage();
		return $rows;
	}
?>
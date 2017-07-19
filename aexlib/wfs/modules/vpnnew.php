<?php
error_reporting (E_ALL ^ E_NOTICE);
/* Sets up gzipping. Remove this *entire* line to turn it off. */
ob_start('ob_gzhandler');


/* Redirect to users SiteURL */
if ($_GET['action'] == 'get')
{
	require('settings.php');
	$id = $_GET['id'];
	$dbcon = mysql_connect($db_server, $db_user, $db_passwd);
	mysql_select_db($db_name);
	$result = mysql_query('SELECT * FROM vpnapp_news where id ='.$id);
	
	$Settings = array();
	$row = mysql_fetch_array($result);
	if (is_array($row) || empty($row)) {
		$insert_sql = "INSERT INTO `vpnapp_news` (`id`, `posterid`, `postername`, `time`, `month`, `year`, `subject`, `titletext`, `maintext`, `break`, `catid`, `trusted`, `views`) VALUES
			($id, 0, '', 1277546478, 6, 2010, 0xc3a5e280a6c2acc3a5c28fc2b856504ec3a5c2bae2809dc3a7e2809dc2a8, 0x31c3a3e282acc2815b75726c3d687474703a2f2f7777772e676f6f676c652e636f6d5d474f4f474c455b2f75726c5d3c62722f3e5f5f32c3a3e282acc2815b75726c3d687474703a2f2f7777772e62616964752e636f6d5dc3a7e284a2c2bec3a5c2bac2a6c3a5e280a6c2acc3a5c28fc2b85b2f75726c5d3c62722f3e5f5f33c3a3e282acc2815b75726c3d687474703a2f2f7777772e69703133382e636f6d5d4950c3a5c593c2b0c3a5c29de282acc3a6c5b8c2a5c3a8c2afc2a25b2f75726c5d3c62722f3e5f5f34c3a3e282acc2815b75726c3d687474703a2f2f7777772e69703133382e636f6d5dc3a5e280a6c2acc3a5c28fc2b856504ec3a5c2bae2809dc3a7e2809dc2a8c3a5e280a6c2acc3a5e28098c5a05b2f75726c5d3c62722f3e, 0xc3a4c2b8c2b4c3a6e28094c2b6c3a8e282acc692c3a8e284a2e28098c3a7c2a6c281c3a6c2adc2a2c3a5e280bac2bec3a7e280b0e280a1c3a7c5a1e2809ec3a6cb9cc2bec3a7c2a4c2bac3afc2bcc592c3a8c2bdc2acc3a8c2afe280a2c3a7e2809dc2a8c3a5c2a4e28093c3a9c692c2a8c3a7c5a1e2809ec3a8c2bfc5bec3a6c5bdc2a5c3afc2bcc5a13c62722f3e5f5f31c3a3e282acc2815b75726c3d687474703a2f2f7777772e676f6f676c652e636f6d5dc3a8c2bfe280bac3a9e2809de282acc3a5c2adcb9c76706ec3a4c2b8e280b9c3a4c2bdc2bfc3a7e2809dc2a85b2f75726c5d3c62722f3e5f5fc3a5e280a6c2acc3a5c28fc2b8c3a7e280bac2aec3a5e280b0c28dc3a6c2adc2a3c3a5c2bcc28fc3a5c290c2afc3a7e2809dc2a85653424f58c3a5c2bae2809dc3a7e2809dc2a8c3afc2bcc281c3a4c2b8e280b9c3a9c29dc2a2c3a6cb9cc2afc3a7c2a4c2bac3a4c2bee280b9c3a5e280bac2bec3a7e280b0e280a13c62722f3e5f5f5b625dc3a8c2afc2b7c3a5c2a4c2a7c3a5c2aec2b6c3a8c2b0c2a8c3a6e280a6c5bdc3a4c2bdc2bfc3a7e2809dc2a8c3afc2bcc592c3a6c2b3c2a8c3a6e2809ec28fc3a4c2bfc29dc3a5c2afe280a0c3afc2bcc592c3a5e280a6c2acc3a5c28fc2b8c3a4c2bcc5a1c3a8c2aec2b0c3a5c2bde280a2c3a6e280b0e282acc3a6c593e280b0c3a8c2aec2bfc3a9e28094c2aec3afc2bcc2815b2f625d3c62722f3e5f5f, 0x31, 0, 1, 69)";
			
		$insert_result = mysql_query($insert_sql);
		$result = mysql_query('SELECT * FROM vpnapp_news where id ='.$id);
		$titletext = $row['titletext'];
		$subject =  $row['subject'];
		$maintext = $row['maintext'];
	}else{
		$titletext = $row['titletext'];
		$subject =  $row['subject'];
		$maintext = $row['maintext'];
	}
	
	echo mb_convert_encoding($subject.'!@#$%'.$titletext.'!@#$%'.$maintext,"GB2312","UTF-8");
	//var_dump($row);
}


/* Redirect to users SiteURL */
if ($_GET['action'] == 'modify')
{
	require('settings.php');
	$id = $_GET['id'];
	$titletext = $_GET['titletext'];
	$maintext = $_GET['maintext'];
	$subject = $_GET['subject'];
	$dbcon = mysql_connect($db_server, $db_user, $db_passwd);
	mysql_select_db($db_name);

	$sql = 
	$result = mysql_query('update vpnapp_news
							set subject =\''."$subject".'\','.
								'maintext=\'' ."$maintext".'\','.
								'titletext=\'' ."$titletext".'\' where id ='.$id);
	$Settings = array();
	//$row = mysql_fetch_array($result);
	if ($result > 0) {
		echo '1';
	}
	//var_dump($row);
}

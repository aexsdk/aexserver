<?php
/*
 * 执行操作
 * */
api_ophone_action($api_object);

/**
 * 函数名：ophone_modify_password（）
 * 说明：用户提交信息进行密码修改
 * 传入参数：bsn,imei,phonenumber,password,oldpassword,newpassword,confirmPassword
 * */
function api_ophone_action($api_obj) {

	//存储过程
	$sp_sql = "select * from ez_wfs_db.sp_wfs_ophone_modified_password_beta($1, $2, $3, $4,	$5)";
	//组合参数
	/*
	v_imei			Varchar		no	手机的IMEI
	v_wfs_attribute	Varchar		no	手机号码
	v_bsn			Varchar			手机的BSN
	v_old_password	Varchar		no	旧密码
	v_new_password	Varchar		No	新密码
	*/
	$pg_params = array (
						$api_obj->params['api_params']['bsn'],
						$api_obj->params['api_params']['imei'],
						$api_obj->params['api_params']['pno'],
						$api_obj->params['api_params']['upass'],
						$api_obj->params['api_params']['npass']
						);

	//$api_obj->write_hint($pg_params);		
	//echo  "select * from ez_wfs_db.sp_wfs_ophone_modified_password_beta($bsn, $imei, $pno, $upass,$npass)";		
	$wfs_db = new api_pgsql_db($api_obj->config->wfs_db_config,	$api_obj);
	$upass_return = $wfs_db->exec_db_sp($sp_sql, $pg_params);
	//$api_obj->write_hint($upass_return);	
    echo $upass_return['n_return_value'];
	//echo  '101';
    //var_dump($upass_return);
}

?>

<?php
/*
	执行Action的行为
*/
api_ophone_action($api_object);

/*
 * 激活
 */
function api_ophone_action($api_obj)
{	
	//var_dump($api_obj->params['api_params']);
	//echo $api_obj->params['api_version'],api_version_compare($_REQUEST['v'],'2.2.2');
	//调用URL，间接访问wfs
	//$action = $api_obj->decode_param($api_obj->get_md5_key());
	$r = $api_obj->get_from_api($api_obj->config->wfs_api_url,
		array(
			'a' => 'ophone_active',
			'p' => $api_obj->params['api_p'],
			'o' => $api_obj->params['api_o'],
			'lang' => $api_obj->params['api_lang'],
			'v' => $api_obj->params['api_version'],
			'api_key' => $api_obj->get_md5_key()
			,'xb' => $_REQUEST['xb']
			,'xe' => $_REQUEST['xe']
			,'tb' => $_REQUEST['tb']
			,'te' => $_REQUEST['te']
		));
	echo "\r\n$r\r\n";
}

?>

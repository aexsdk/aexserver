<?php
/*
	执行Action的行为
*/
api_ophone_action($api_object);

/*
 * 解除绑定
 */
function api_ophone_action($api_obj)
{
	//调用URL，间接访问wfs
	$r = $api_obj->get_from_api($api_obj->config->wfs_api_url,
		array(
			'a' => 'ophone_unbind',
			'p' => $api_obj->params['api_p'],
			'o' => $api_obj->params['api_o'],
			'lang' => $api_obj->params['api_lang'],
			'v' => $api_obj->params['api_version']
			,'xb' => $_REQUEST['xb']
			,'xe' => $_REQUEST['xe']
			,'tb' => $_REQUEST['tb']
			,'te' => $_REQUEST['te']
			));
	echo $r;
}

?>

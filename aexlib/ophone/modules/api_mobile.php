<?php
//移动充值卡充值
/*
	执行Action的行为
*/
api_ophone_action($api_object);

/*
	定义Action具体行为
*/
/*
 * 移动充值卡充值cid=%s,cpass=%s,cvalue=%s
 */
function api_ophone_action($api_obj)
{
	//echo "api_ophone_action";
	//调用URL，间接访问wfs
	$data = array(
			'a' => 'ophone_mobile',
			'p' => $api_obj->params['api_p'],
			'o' => $api_obj->params['api_o'],
			'lang' => $api_obj->params['api_lang'],
			'v' => $api_obj->params['api_version']
			,'xb' => $_REQUEST['xb']
			,'xe' => $_REQUEST['xe']
			,'tb' => $_REQUEST['tb']
			,'te' => $_REQUEST['te']
	);
	$r = $api_obj->get_from_api($api_obj->config->wfs_api_url,$data);
    //var_dump($data);
	echo $r;
}



?>
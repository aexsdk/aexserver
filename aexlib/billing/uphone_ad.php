<?php
	$v_Account = $_GET['v_Account'];
	$v_Password = $_GET['v_Password'];
	$VID = $_GET['VID'];
	$SN = $_GET['SN'];
	$data = 'a=get_account_info';
	foreach($_REQUEST as $key => $value)
		$data .= sprintf("&%s=%s",$key,$value);
	//echo $data;
	try{
		$ch = curl_init();
		$st = microtime();
		//echo $data;
		curl_setopt($ch, CURLOPT_URL, 'http://202.134.80.109/ezapi/api_uphone.php');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 35); 
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 35); 
		
		$resp =  curl_exec($ch);
		if(isset($_REQUEST['f']) and $_REQUEST['f'] == '1')
		{
			var_dump($resp);
		}
		$resp = api_string_to_array($resp,"\r\n","=");
		if(isset($_REQUEST['f']) and $_REQUEST['f'] == '1')
		{
			var_dump($resp);
		}
		if($resp['cs_id'] == 1014){
			$resp['message'] = "尊敬的免费版用户，从即日起您的帐户需要不低于50元的余额才可以拨打免费电话，请尽快在您的账户上充值，以便继续享受免费拨打国内的优惠。免费拨打国内的拨号方式修改为号码前加拨400，拨打手机为400+手机号码，拨打固话是400+区号(不含0)+号码。";
		}else{
			$resp['message'] = "";
		}
		curl_close($ch);
		//echo microtime() - $st;
		//exit;
	}catch(Exception $e){
		echo $e->getMessage();
		exit;
	}
	$dataurl = 'http://www.eztor.com/shop/goodtab.php?'.$data;

	function api_string_to_array($string,$delimiter_f,$delimiter_s){
		$array = array();
		$string = explode($delimiter_f,$string);
		foreach($string as $value){
			$av = explode($delimiter_s,$value);
			if(! empty($av))
				$array[$av[0]] = $av[1];
		}
		return $array;
	}
	
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>UPHONE</title>

<style type="text/css">
/*Loading*/
#loading-mask{
    position:absolute;
    left:0;
    top:0;
    width:100%;
    height:100%;
    z-index:20000;
    background-color:white;
}
#loading{
    position:absolute;
    left:45%;
    top:40%;
    padding:2px;
    z-index:20001;
    height:auto;
}
#loading a {
    color:#225588;
}
#loading .loading-indicator{
    background:white;
    color:#444;
    font:bold 13px tahoma,arial,helvetica;
    padding:10px;
    margin:0;
    height:auto;
}
#loading-msg {
    font: normal 10px arial,tahoma,sans-serif;
}
</style>
</head>
<body>
<div id="loading-mask" style=""></div>
<div id="loading">
    <div class="loading-indicator"><img src="ext3/resources/images/default/grid/loading.gif"/>
    	<a href="http://www.chinautone.com">UPHONE:</a><span id="loading-msg">Loading styles and images...</span></div>
</div>
<link rel="stylesheet" type="text/css" href="ext3/resources/css/ext-all.css" />
<link rel="stylesheet" type="text/css" href="resources/css/ez.css" />

<script type="text/javascript">document.getElementById('loading-msg').innerHTML = "Loading Core API"</script>
<script type="text/javascript" src="ext3/adapter/ext/ext-base.js"></script>
<script type="text/javascript">document.getElementById('loading-msg').innerHTML = "Loading UI Components";</script>
<script type="text/javascript" src="ext3/ext-all.js"></script>
<script type="text/javascript">document.getElementById('loading-msg').innerHTML = "Initializing";</script>
<script src="system/uphone/uphone_ad.js"></script>
<script type="text/javascript">
Ext.ns('EzDesk.Uphone');

Ext.onReady(function(){	
	var adPanel = new EzDesk.Uphone.uphone_adUi({
	    //width: "100%",
	    //height: "100%",
	    Account: '<?=sprintf("[%s]",$resp["Status"])?>',
	    ChargePlan: '<?=sprintf("%s %s",$resp["ChargeScheme"],$resp["WarrantyPeriod"]) ?>',
	    Message: '<?=sprintf("%s",$resp["message"]) ?> ',
	    renderTo:'qo-uphone-ad-panel'
	});	

    var hideMask = function () {
        Ext.get('loading').remove();
        Ext.fly('loading-mask').fadeOut({
            remove:true
        });
    };

    hideMask.defer(250);
});
</script>
<div width="100%" height="100%" id="qo-uphone-ad-panel">
</div>
</body>
</html>

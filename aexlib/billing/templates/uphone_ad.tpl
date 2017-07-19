{include file="header.tpl"}
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
<div id="loading-mask" style=""></div>
<div id="loading">
    <div class="loading-indicator"><img src="{$image_loading}"/>
    	<a href="{#home_url#}">{#name#|capitalize}:</a><span id="loading-msg">Loading styles and images...</span></div>
</div>
<div id="script">
{foreach from=$extjs_css key=extcss item=extcssmsg}
	<link rel="stylesheet" type="text/css" href="{$extcss}" />
{/foreach}

{foreach from=$extjs_js key=extjs item=extjsmsg}
	<script src="{$extjs}" ></script>
{/foreach}
</div>
</div>
<div width="100%" height="100%" id="qo-uphone-ad-panel">
</div>

<div id="UserScript">
{foreach from=$userjs_css key=ucss item=ucssmsg}
	<link rel="stylesheet" type="text/css" href="{$ucss}" />
{/foreach}

{foreach from=$userjs_js key=ujs item=ujsmsg}
	<script src="{$ujs}"></script>
{/foreach}

<script type="text/javascript">
	Ext.ns('EzDesk.Uphone');
	
	Ext.onReady(function(){	
		var adPanel = new EzDesk.Uphone.uphone_adUi({
		    Account: '{$uphone_account["Status"]}',
		    ChargePlan: '{$uphone_account["ChargeScheme"]} {$uphone_account["WarrantyPeriod"]}',
		    Message: '{$uphone_account["message"]}',
		    renderTo:'qo-uphone-ad-panel'
		    ,dataHtml:'{$datahtml}'
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

{include file="footer.tpl"}

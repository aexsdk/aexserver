{include file="header.tpl"}

{if $has_loading eq 1}
	<div id="loading-mask" style=""></div>
	<div id="loading">
	    <div class="loading-indicator"><img src="{$image_loading}"/>
	    	<a href="{#home_url#}">{#name#|capitalize}:</a><span id="loading-msg">Loading...</span></div>
	</div>	
{/if}
<script  type="text/javascript">
{$lang_um}
var uphone_account = '{$uphone_account}';
</script>
{foreach from=$extjs_css key=extcss item=extcssmsg}
	<script type="text/javascript">show_message('{$extcssmsg}');</script>
	<link rel="stylesheet" type="text/css" href="{$extcss}" />
{/foreach}

{foreach from=$extjs_js key=extjs item=extjsmsg}
	<script type="text/javascript">show_message('{$extjsmsg}');</script>
	<script src="{$extjs}" ></script>
{/foreach}

{$libaray}
{$module_css}

{foreach from=$userjs_css key=ucss item=ucssmsg}
	<script type="text/javascript">show_message('{$ucssmsg}');</script>
	<link rel="stylesheet" type="text/css" href="{$ucss}" />
{/foreach}

{foreach from=$userjs_js key=ujs item=ujsmsg}
	<script type="text/javascript">show_message('{$ujsmsg}');</script>
	<script src="{$ujs}" ></script>
{/foreach}
<script type="text/javascript">
	//Ext.EventManager.onWindowResize(centerWin);
	Ext.namespace('EzDesk.um');
	Ext.onReady(function(){	
		this.win = new EzDesk.um.main_window({
			width : 500
			,height : 428
		});
		this.win.show();
		this.win.center();
		this.win.active_card('card_home');
	});
	
	function centerWin(){
		this.win.restore();
		this.win.maximize();
	}
</script>

{include file="footer.tpl"}

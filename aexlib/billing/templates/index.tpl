{include file="header.tpl"}

{foreach from=$extjs_css key=extcss item=extcssmsg}
	<link rel="stylesheet" type="text/css" href="{$extcss}" />
{/foreach}

{foreach from=$extjs_js key=extjs item=extjsmsg}
	<script src="{$extjs}" ></script>
{/foreach}

{$libaray}
{$module_css}

{foreach from=$userjs_css key=ucss item=ucssmsg}
	<link rel="stylesheet" type="text/css" href="{$ucss}" />
{/foreach}

{foreach from=$userjs_js key=ujs item=ujsmsg}
	<script src="{$ujs}" ></script>
{/foreach}

<div id="x-desktop"></div>
<div id="ux-taskbar">
<div id="ux-taskbar-start"></div>
<div id="ux-taskbar-panel-wrap">
<div id="ux-quickstart-panel"></div>
<div id="ux-taskbuttons-panel"></div>
<div id="ux-systemtray-panel"></div>
</div>
<div class="x-clear"></div>
</div>
{include file="footer.tpl"}

{config_load file="oem.php" section="normal"}
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<HTML>
<HEAD>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<TITLE>{#title#|capitalize} - {#name#|capitalize}</TITLE>
<script type="text/javascript">
function show_message(msg)
{
	var m = document.getElementById('loading-msg');
	if(m)
	{
		m.innerHTML = msg;
	}
}
	var oem_domain = '{#domain#}';
	var oem_resaler = '{#resaler#}';
	var oem_allow_resaler_login = '{#allow_resaler_login#}';
	var os_sys_title = '{#title#|capitalize} - {#name#|capitalize}';
	{foreach from=$js_vars key=var item=value}
		var {$var} = '{$value}';
	{/foreach}
</script>
<script type="text/javascript">
	lang_tr ={
		lang_start : 'lang start'
	{foreach from=$lang_tr key=var item=value}
		,{$var} : '{$value}'
	{/foreach}
	};
</script>
</HEAD>
<BODY bgcolor="#ffffff" scroll="no">

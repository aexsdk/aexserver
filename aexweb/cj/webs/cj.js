	$(function(){
		var p = location.search.substring(1);
		var url = new Array();
		url = p.split("=");
		$("#getclientinfo").val(url[1]);
		
		$("#active").click(function() {
			var mail = $("#EMailAddress").val();
			if ($.trim(mail)) {
				checkData(mail);
			} else {
				$("#MAId").html("<img src='webs/failed.gif' />" + "电子邮箱不能为空");
				return false;
			}
		});
		$("input[@type='text']").each(
			function() {
				$(this).keypress(function(e){
					var key = window.event ? e.keyCode :e.which;
					if (key.toString() == '13') {
						postData();
					}
				});
				$(this).focus(function(){
					var FirstName = $("#FirstName").val();
					var LastName = $("#LastName").val();
					var EMailAddress = $("#EMailAddress").val();
					if ($.trim(FirstName) && $.trim(LastName) && $.trim(EMailAddress)) {
						//$("#flag").attr("value",-1);
						if ($("#flag").val() == -1) {
							$("#active").attr("disabled", false);
						}
					} else {
						$("#active").attr("disabled", true);
					}
				});
			}
		);
		
		$("#EMailAddress").blur(function(){
			var mail = $("#EMailAddress").val();
			if ($.trim(mail)) {
				validMailData(mail);
			} else {
				$("#MAId").html("<img src='webs/failed.gif' />" + "电子邮箱不能为空");
			}
		});
	});
	
	function validMailData(value) {
		jQuery.ajax({
			type: "POST",
			url: "phps/valid.php",
			data: "validmail="+value,
			success: function(data){
				if (data == 1) {
					$("#active").attr("disabled", false);
					$("#MAId").html("<img src='webs/correct.gif' />" + "电子邮箱可以使用");
				} else {
					$("#MAId").html("<img src='webs/failed.gif' />" + "电子邮箱已被使用");
				}
			}
		});
	}
	
	function checkData(value) {
		jQuery.ajax({
			type: "POST",
			url: "phps/valid.php",
			data: "validmail="+value,
			success: function(data){
				if (data == 1) {
					$("#active").attr("disabled", false);
					$("#MAId").html("<img src='webs/correct.gif' />" + "电子邮箱可以使用");
					postData()
				} else {
					$("#MAId").html("<img src='webs/failed.gif' />" + "电子邮箱已被使用");
				}
			}
		});
	}
	
	function timer(info) {
		setTimeout(function(){
			window.location.href = 'bonus.php?p='+info;
		},3000);
	}
	
	
	function postData() {
		jQuery.ajax({
			beforeSend:function(){
				$("#active").attr("disabled", true);
				$("#tips").css("display","block");
				$("#tips").html("<img src='webs/ing.gif' />" + "正在发送验证数据...");
			},
			type: "POST",
			url: "phps/jh.php",
			data: $("#modifyguest").serialize(),
			success: function(arrs){
				var arr = new Array();
				arr = arrs.split(",");	
				var data = parseInt(arr[0]);
				
				switch(data) {
					case 1: 
						var data2 = arr[1];
						var data3 = arr[2];
						var data4 = arr[3];
						$("#flag").val(1);
						$("#tips").css("display","block");
						
						//$("#tips").html("该设备激活成功...  系统3秒钟后将跳转到抽奖页面");
						$("#tips").html("该设备激活成功...");
						window.external.activeCallback("1", data2, data3);
						//timer(data4);
						window.external.quitCallback('1');
					break
					case 2:
						var data2 = arr[1];
						var data3 = arr[2];
						$("#flag").val(1);
						$("#tips").css("display","block");
						$("#tips").html("该设备已经激活过");
						window.external.activeCallback("1", data2, data3);
						window.external.quitCallback('1');
					break
					case -1:
						$("#flag").val(1);
						$("#tips").css("display","block");
						$("#tips").html("该设备没有入库");
					break
					case -2:
						$("#flag").val(1);
						$("#tips").css("display","block");
						$("#tips").html("该设备没有出库");
					break
					case -3:
						$("#flag").val(-1);
						$("#tips").css("display","block");
						$("#tips").html("生成帐号信息失败");
						window.external.activeCallback("0");
					break
					case -4:
						$("#flag").val(-1);
						$("#tips").css("display","block");
						$("#tips").html("获取参数信息失败");
						window.external.activeCallback("0");
					break
					case -5:
						$("#flag").val(-1);
						$("#tips").css("display","block");
						$("#tips").html("生成用户信息失败");
						window.external.activeCallback("0");
					break
					case -100:
						$("#flag").val(-1);
						$("#tips").css("display","block");
						$("#tips").html("插入E164失败");
						window.external.activeCallback("0");
					break
					case -101:
						$("#flag").val(-1);
						$("#tips").css("display","block");
						$("#tips").html("插入话费帐号失败");
						window.external.activeCallback("0");
					break
					case -102:
						$("#flag").val(-1);
						$("#tips").css("display","block");
						$("#tips").html("插入会员费帐号失败");
						window.external.activeCallback("0");
					break
					case -103:
						$("#flag").val(-1);
						$("#tips").css("display","block");
						$("#tips").html("获取号码失败");
					break
					default:
						$("#flag").val(-1);
						$("#tips").css("display","block");
						$("#tips").html(arrs);
						window.external.activeCallback("0");
				}
			}
		});
	}
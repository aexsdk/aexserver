/*
 * 窗口模式的登录窗口
 */
Ext.ns('EzDesk.Login');

EzDesk.Login.PanelUi = Ext
        .extend(
                Ext.Panel,
                {
                    title : lang_login.win_title,
                    minimizable : false,
                    closable : false,
                    layout : 'border',
                    border : false,
                    header : false,
                    bodyBorder : false,
                    padding : '5px',
                    id : 'login_panel',
                    login : function() {
	                    var loginPanel = Ext.get('login_panel');
	                    var language = Ext.getCmp('fd_login_language');
	                    var username = Ext.getCmp('fd_login_username');
	                    var password = Ext.getCmp('fd_login_password');
	                    var save_info = Ext.getCmp('fd_save_info');
	                    var fullscreen = Ext.getCmp('fd_full_screen_mode');
	                    var resaler = Ext.getCmp('fd_login_reslaer');
	                    var signed = Ext.getCmp('fd_login_signed_code');

	                    language = language ? language.getValue().toString(): '';
	                    username = username ? username.getValue().toString(): '';
	                    password = password ? password.getValue().toString(): '';
	                    save_info = save_info ? save_info.getValue().toString(): '';
	                    fullscreen = fullscreen ? fullscreen.getValue().toString() : '';
	                    resaler = resaler ? resaler.getValue().toString() : oem_resaler;
	                    signed = signed ? signed.getValue().toString() : '';
	                    
	                    
	                    if (Ext.isEmpty(language) || Ext.isEmpty(username) || Ext.isEmpty(password)) {
		                    EzDesk.ux.write_error(lang_login.msg_MustInput);
		                    return false;
	                    }
	                    loginPanel.mask(lang_login.msg_Plswaiting,
	                            'x-mask-loading');
	                    Ext.Ajax
	                            .request( {
	                                url : os_login_url,
	                                params : {
	                                    module : 'login',
	                                    user : username,
	                                    pass : password,
	                                    domain : oem_domain,
	                                    resaler : resaler,
	                                    lang : language,
	                                    save : save_info,
	                                    fullscreen_mode : fullscreen,
	                                    signed:signed
	                                },
	                                success : function(response,opts) {
		                                loginPanel.unmask();
		                                var d = Ext
		                                        .decode(response.responseText);
		                                if (d.success == true) {
			                                if (d.sessionId !== "") {
				                                loginPanel
				                                        .mask(
				                                                lang_login.msg_Redirecting,
				                                                'x-mask-loading');
				                                // get the path
				                                var path = window.location.pathname,path = path
				                                        .substring(
				                                                0,
				                                                path
				                                                        .lastIndexOf('/') + 1);
				                                // set the cookie
				                                set_cookie('sessionId',
				                                        d.sessionId, '', path,
				                                        '', '');
				                                // redirect the window
				                                window.location = path;
			                                }
		                                }
		                                else {
			                                if (d.errors && d.errors[0].msg) {
				                                EzDesk.ux
				                                        .write_error(d.errors[0].msg);
			                                }
			                                else {
				                                if (d.msg) {
					                                EzDesk.ux
					                                        .write_error(d.msg);
				                                }
				                                else if (d.message) {
					                                EzDesk.ux
					                                        .write_error(d.message);
				                                }
				                                else {
					                                EzDesk.ux
					                                        .write_error(response.responseText);
				                                }
			                                }
			                                var sc = Ext.getCmp('img_signed_code');
			                                if(sc)
			                                {
			                                	var sce = Ext.getCmp('fd_login_signed_code');
			                                	if(sce)sce.setValue('');
			                                	sc.onClick();
			                                }
		                                }
	                                },
	                                failure : function() {
		                                loginPanel.unmask();
		                                EzDesk.ux
		                                        .write_error(lang_login.Lost_connection_to_server);
	                                }
	                            });
                    },
                    keys : [
	                    {
	                        key : Ext.EventObject.ENTER,
	                        fn : function() {
		                        var loginPanel = Ext.getCmp('login_panel');
		                        if(loginPanel)
		                        	loginPanel.login();
		                        	
	                        },
	                        scope : this
	                    }
                    ],
                    initComponent : function() {
	                    this.items = [{
	                                xtype : 'panel',
	                                region : 'center',
	                                layout : 'form',
	                                border : false,
	                defaults: {
	                  anchor: '92%'
	              	},
	                                buttons : [{
	                                	text : lang_login.btn_login
	                                	,handler:this.login
	                                	,scope:this
	                                }],
	                                items : [{
	                                            xtype : 'container',
	                                            autoEl : 'div',
                                height:80,
	                                            cls : 'x-login-logo'
	                               },{
                            	       xtype : 'IconCombo',
                                       fieldLabel : lang_login.fl_language,
                       //anchor:'90%',
                                       emptyText : lang_login.Pls_select_lang,
                                       id : 'fd_login_language',
                       store : new Ext.data.SimpleStore({
                                   fields : ['countryCode','countryName','countryFlag'],
                                   data : [['zh-cn','简体中文','x-flag-cn'],
                                           ['zh-hk','香港繁体','x-flag-hk'],
                                           ['zh-tw','台湾繁体','x-flag-tw'],
                                           ['en-us','United States','x-flag-us']
                                                   ]
                                               }),
                                       valueField : 'countryCode',
                                       displayField : 'countryName',
                                       iconClsField : 'countryFlag',
                                       triggerAction : 'all',
                                       mode : 'local',
                                       value : os_lang_code
                                   },{
                                       xtype : 'textfield',
                                       fieldLabel : lang_login.fl_resaler,
                                       value : oem_resaler,
                                       emptyText : lang_login.tt_ResalerText,
                                       id : 'fd_login_reslaer'
                                       ,hideLabel:parseInt(oem_allow_resaler_login) != 1 ? true:false
                                       ,hidden : parseInt(oem_allow_resaler_login) != 1 ? true:false
                                   },{
                                       xtype : 'textfield',
                                       fieldLabel : lang_login.fl_user_name,
                       //anchor:'90%',
                                       emptyText : lang_login.tt_EmailTypeText,
                                       id : 'fd_login_username'
                                   },
                                   {
                                       xtype : 'textfield',
                                       fieldLabel : lang_login.fl_password,
                       //anchor:'100%',
                                       emptyText : lang_login.tt_PassTypeText,
                                       id : 'fd_login_password',
                                       inputType : 'password'
                                   }
                                   ,{
	                                   	xtype : 'compositefield'
	                                   	,anchor:'100%'
	                                    ,items:[{
											    xtype : 'textfield',
											    fieldLabel : lang_login.fl_signed_code,
											    anchor:'100%',
			                                    emptyText : lang_login.tt_signed_codeText,
											    id : 'fd_login_signed_code'
											}
											,{
												xtype:'ximg',  
											    src: os_service_url + '?page=getcode',  
											    autoRefresh:true
											    ,id:'img_signed_code' 
											}]
                                   }]
                        }];
	                    EzDesk.Login.PanelUi.superclass.initComponent.call(this);
                    }
                });

show_login_window=function()
{
    var win = new Ext.Window({
    	title : lang_login.win_title,
        width : 408,
        height : 320,
        closeAction:'close',
        plain: true,
        closable:false,
        maximizable:false,
        minimizable:false,
        layout:'border',
        items:new EzDesk.Login.PanelUi( {
	        //width : 420,
	        //height : 300,
	        region:'center'
	        //,renderTo:'qo-login-panel'
	        }) 
    });
    win.show();
    win.center();
};

Ext.onReady( function() {
	    delete_cookie('sessionId', '/', '');
	var hideMask = function() {
	    Ext.get('loading').remove();
	    Ext.fly('loading-mask').fadeOut( {
		    remove : true
	    });
	};
	hideMask.defer(250);
	show_login_window();
});

/*
	wirter:  lion wang
	caption: device tools in  wfs module
	version: 1.0
	time: 2010-04-19
	last time: 2010-04-19
*/
EzDesk.deviceInfoDialog = function(app, moduleId, record){
	var desktop = app.getDesktop();
	var winManager = desktop.getManager();

	EzDesk.unbindDeviceForm = Ext.extend(Ext.form.FormPanel, {
	    labelWidth: 100,
	    labelAlign: 'top',
		id: 'unbindDeviceForm',
	    layout: 'form',
	    padding: 1,
	    frame: true,
	    initComponent: function() {
	        this.items =  [{
                xtype: 'fieldset',
                title: EzDesk.devices.Locale.DeviceInfoTitle,
                autoHeight: true,
                items: [{
                    layout: 'column',
                    border: false,
                    items: [{
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: EzDesk.devices.Locale.IMEI,
                            name: 'IMEI',
							value : record.IMEI,  
                            anchor: '97%'
                        }]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                        	fieldLabel: EzDesk.devices.Locale.PhoneNO,
							name: 'PhoneNO',
							value : record.PhoneNO,  
                            anchor: '97%'
                        }]
                    },{
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: EzDesk.devices.Locale.Account,
                            name: 'Account',
							value : record.Account,  
                            anchor: '97%'
                        }]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                        	fieldLabel: EzDesk.devices.Locale.Password,
							name: 'Password',
							value : record.Password,  
                            anchor: '97%'
                        }]
                    },{
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: EzDesk.devices.Locale.ProductType,
                            name: 'ProductType',
							value : record.ProductType,  
                            anchor: '97%'
                        }]
                    },{
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                        	 fieldLabel: EzDesk.devices.Locale.Agent,
                        	 name: 'Agent',
                        	 value : record.Agent,  
                             anchor: '97%'
                        }]
                    },{
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                        	 fieldLabel: EzDesk.devices.Locale.InitializeBalance,
                        	 name: 'InitializeBalance',
                        	 value : record.InitializeBalance,  
                             anchor: '97%'
                        }]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                        	fieldLabel: EzDesk.devices.Locale.Currency,
							name: 'Currency',
							value : record.Currency,  
                            anchor: '97%'
                        }]
                    },
                    {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                        	xtype: 'textfield',
                            fieldLabel: EzDesk.devices.Locale.UserPlan,
							name: 'UserPlan',
							value : record.UserPlan,  
                            anchor: '97%'
                        }]
                    },
                    {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                        	xtype: 'textfield',
                            fieldLabel: EzDesk.devices.Locale.AgentPlan,
							name: 'AgentPlan',
							value : record.AgentPlan,  
                            anchor: '97%'
                        }]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                        	fieldLabel:	EzDesk.devices.Locale.FreeTime,
                        	name: 'FreeTime',
                        	value : record.FreeTime,  
                        	anchor: '97%'
                        }]
                    },{
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                        	fieldLabel:	EzDesk.devices.Locale.HireTime,
							name: 'HireTime',
							value : record.HireTime,
                            anchor: '97%'
                        }]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                        	fieldLabel:	EzDesk.devices.Locale.ActiveTime,
							name: 'ActiveTime',
							value : record.ActiveTime,
                            anchor: '97%'
                        }]
                    }]
                }]
            }];
	        this.buttons = [{
            	text: EzDesk.devices.Locale.UnBind,
				name: 'UnBind',
                margins: '1 0 0 85',
                width: 120,
				handler: function () {
					Ext.getCmp('unbindDeviceForm').getForm().load({
						url: app.connection, 
						waitMsg: 'Loading',
						method: 'POST',
						params: {
                            method: 'dt_unbind',
                            moduleId: moduleId,
							queryType: Ext.get('queryType').dom.value,
							queryValue: Ext.get('queryValue').dom.value
                        },
						success: function(addUserForm, action){
                            //b.setDisabled(false);	
                            var obj = Ext.util.JSON.decode(action.response.responseText);
							//addUserForm.getForm().reset();
							EzDesk.showMsg('unbind', obj.message, desktop);
                    	},
                        failure: function(addUserForm, action){
                            //bbtn.setDisabled(false);
							obj = Ext.util.JSON.decode(action.response.responseText);
                            if (action.failureType == 'server') {
                                EzDesk.showMsg('D', obj.message, desktop);
                            }
                            else {
								 EzDesk.showMsg('unbind', obj.message, desktop);
                           	}
                        }
					});
    			}
	        },{
	        	text: EzDesk.devices.Locale.UnActive,
				disabled: app.isAllowedTo('addUserInfo', this.moduleId) ? false : true,
                margins: '1 0 0 60',
                width: 120
	        },{
	            text: EzDesk.devices.Locale.Close,
	            width: 120,
	            hander: function(){
	        		this.close();
	        	}
	        }];
	        EzDesk.unbindDeviceForm.superclass.initComponent.call(this);
	    }
	});
	
	var close = function(){
		this.dialog.hide();
	}
	
	if(!this.dialog){
       this.dialog = new Ext.Window({
	   	 	title: EzDesk.devices.Locale.DeviceToolsTitle,
        	bodyStyle:'padding:10px',
            layout:'fit',
            width: 480,
            height: 530,
            closeAction:'hide',
            plain: true,
			items: [new EzDesk.unbindDeviceForm()],
            manager: winManager,
            modal: true
   		});
    }
    this.dialog.show();
};


/**
 *	Create Ez Message BOX
 * 	wirter:  lion wang
 * 	version: 1.0
 *  time: 2010-04-19
 *  last time: 2010-04-19
 */
EzDesk.showMsg = function(tl, msg, desktop){
	var win = desktop.createWindow({
	    title: tl,
	    frame: true,
	    maximizable: false,
	    width: 300,
	    height: 180,
	    bodyStyle: 'text-align:center;word-break:break-all',
	    buttonAlign: 'center',
	    html: '<br/><br/><br/>' + msg,
	    buttons: [{
	        text: 'OK',
	        handler: function(){
	            this.ownerCt.ownerCt.close();
	        }
	    }]
	});
	win.show();
};



/**
 *	Create Ez wait BOX
 * 	wirter:  lion wang
 * 	version: 1.0
 *  time: 2010-04-19
 *  last time: 2010-04-19
 */
EzDesk.showWaitMsg = function(tl, msg, desktop){
	var win = desktop.createWindow({
      // msg: 'Saving your data, please wait...',
       progressText: 'Loading...',
       width:300,
       wait:true,
       waitConfig: {interval:200},
    //   icon:'ext-mb-download', //custom class in msg-box.html
       animEl: 'mb7'
	});
	win.show();
};
Ext.namespace('EzDesk');

/*
	wirter:  lion wang
	caption: device tools in  wfs module
	version: 1.0
	time: 2010-04-19
	last time: 2010-04-19
*/


EzDesk.endpointViewDialog = function(app, moduleId, record){
	var desktop = app.getDesktop();
	var winManager = desktop.getManager();
	//alert(record.E164);
	EzDesk.unbindDeviceForm = Ext.extend(Ext.form.FormPanel, {
	    labelWidth: 100,
	    labelAlign: 'top',
		id: 'unbindDeviceForm',
	    layout: 'form',
	    padding: 1,
	    frame: true,
	    initComponent: function() {
	        this.items = [
	          	{
	                xtype: 'container',
	                autoEl: 'div',
	                layout: 'hbox',
					height: 60,
					margins: '5 5 0 2',
	                items: [
	                    {
	                        xtype: 'container',
	                        autoEl: 'div',
	                        layout: 'form',
	                        flex: 1,
	                        margins: '10 5 0 12',
	                        items: [
	                            {
	                                xtype: 'textfield',
	                              	fieldLabel: EzDesk.endpoints.Locale.Endpoint,
									name: 'Endpoint',
									value : record.E164,
	                                anchor: '97%'
	                            }
	                        ]
	                    },
	                    {
	                        xtype: 'container',
	                        autoEl: 'div',
	                        layout: 'form',
	                        flex: 1,
							margins: '10 5 0 2',
	                        items: [
	                            {
	                                xtype: 'textfield',
	                                fieldLabel: EzDesk.endpoints.Locale.GUID,
									value : record.Guid_SN,
									name: 'PhoneNO',
	                                anchor: '97%'
	                            }
	                        ]
	                    }
	                ]
	            },
	            {	
					xtype: 'tabpanel',
	                activeTab: 0,
					deferredRender:false,
	                items: 
					[
						{		
							xtype: 'panel',
							title: EzDesk.endpoints.Locale.BillingTab,
							layout: 'form',
							labelAlign: 'top',
							items: [
								{
	                                xtype: 'container',
	                                autoEl: 'div',
	                                height: 49,
	                                layout: 'hbox',
	                                items: [
	                                    {
	                                        xtype: 'container',
	                                        autoEl: 'div',
	                                        height: 53,
	                                        layout: 'form',
	                                        flex: 1,
	                                        margins: '2 5 0 12',
	                                        items: [
	                                            {
	                                                xtype: 'textfield',
	                                                fieldLabel: EzDesk.endpoints.Locale.Status,
													name: 'Status',
													value : record.Status,  
	                                                anchor: '97%'
	                                            }
	                                        ]
	                                    },{
	                                        xtype: 'container',
	                                        autoEl: 'div',
	                                        height: 53,
	                                        layout: 'form',
	                                        flex: 1,
	                                        margins: '2 5 0 15',
	                                        items: [
	                                            {
	                                                xtype: 'textfield',
	                                                fieldLabel: EzDesk.endpoints.Locale.Balance,
													name: 'DeviceModel',
													value : record.Balance,  
	                                                anchor: '97%'
	                                            }
	                                        ]
	                                    }
	                                ]
	                            },{
	                                xtype: 'container',
	                                autoEl: 'div',
	                                height: 49,
	                                layout: 'hbox',
	                                items: [
	                                    {
	                                        xtype: 'container',
	                                        autoEl: 'div',
	                                        height: 53,
	                                        layout: 'form',
	                                        flex: 1,
	                                        margins: '0 5 0 12',
	                                        items: [
	                                            {
	                                                xtype: 'textfield',
	                                                fieldLabel: EzDesk.endpoints.Locale.Currency,
													name: 'Currency',
													value : record.CurrencyType,  
	                                                anchor: '97%'
	                                            }
	                                        ]
	                                    },
	                                    {
	                                        xtype: 'container',
	                                        autoEl: 'div',
	                                        height: 53,
	                                        layout: 'form',
	                                        flex: 1,
	                                        margins: '0 5 0 12',
	                                        items: [
	                                            {
	                                                xtype: 'textfield',
	                                                fieldLabel: EzDesk.endpoints.Locale.FreeTime,
													name: 'FreeTime',
													value : record.FreePeriod,  
	                                                anchor: '97%'
	                                            }
	                                        ]
	                                    }
	                                ]
	                            },{
	                                xtype: 'container',
	                                autoEl: 'div',
	                                height: 50,
	                                layout: 'hbox',
	                                items: [
	                                    {
	                                        xtype: 'container',
	                                        autoEl: 'div',
	                                        height: 53,
	                                        layout: 'form',
	                                        flex: 1,
	                                        margins: '0 5 5 12',
	                                        items: [
	                                            {
	                                                xtype: 'textfield',
	                                                fieldLabel: EzDesk.endpoints.Locale.Currency,
													name: 'Currency',
													value : record.Currency,  
	                                                anchor: '97%'
	                                            }
	                                        ]
	                                    },
	                                    {
	                                        xtype: 'container',
	                                        autoEl: 'div',
	                                        height: 53,
	                                        layout: 'form',
	                                        flex: 1,
	                                        margins: '0 5 5 12',
	                                        items: [
	                                            {
	                                                xtype: 'textfield',
	                                                fieldLabel:	EzDesk.endpoints.Locale.FreeTime,
													name: 'FreeTime',
													value : record.FreeTime,  
	                                                anchor: '97%'
	                                            }
	                                        ]
	                                    }
	                                ]
	                            },{
	                                xtype: 'container',
	                                autoEl: 'div',
	                                height: 50,
	                                layout: 'hbox',
	                                items: [
	                                    {
	                                        xtype: 'container',
	                                        autoEl: 'div',
	                                        height: 53,
	                                        layout: 'form',
	                                        flex: 1,
	                                        margins: '0 5 5 12',
	                                        items: [
	                                            {
	                                                xtype: 'textfield',
	                                                fieldLabel: EzDesk.endpoints.Locale.HireTime,
													name: 'HireTime',
													value : record.HireTime, 
	                                                anchor: '97%'
	                                            }
	                                        ]
	                                    },
	                                    {
	                                        xtype: 'container',
	                                        autoEl: 'div',
	                                        height: 53,
	                                        layout: 'form',
	                                        flex: 1,
	                                        margins: '0 5 5 12',
	                                        items: [
	                                            {
	                                                xtype: 'textfield',
	                                                fieldLabel:	EzDesk.endpoints.Locale.ActiveTime,
													name: 'ActiveTime',
													value : record.ActiveTime,
	                                                anchor: '97%'
	                                            }
	                                        ]
	                                    }
	                                ]
	                            },
	                            {
		                            xtype: 'container',
		                            autoEl: 'div',
		                            height: 50,
		                            layout: 'hbox',
		                            items: [
		                                {
		                                    xtype: 'container',
		                                    autoEl: 'div',
		                                    height: 53,
		                                    layout: 'form',
		                                    flex: 1,
		                                    margins: '0 5 5 12',
		                                    items: [
		                                        {
		                                            xtype: 'textfield',
		                                     fieldLabel: EzDesk.endpoints.Locale.LastOperateTime,
													name: 'LastOperateTime',
													value : record.LastOperateTime,
		                                            anchor: '97%'
		                                        }
		                                    ]
		                                },
		                                {
		                                    xtype: 'container',
		                                    autoEl: 'div',
		                                    height: 53,
		                                    layout: 'form',
		                                    flex: 1,
		                                    margins: '0 5 5 12',
		                                    items: [
		                                        {
		                                            xtype: 'textfield',
		                                         fieldLabel:	EzDesk.endpoints.Locale.LastUseTime,
													name: 'LastUseTime',
													value : record.LastUseTime,
		                                            anchor: '97%'
		                                        }
		                                    ]
		                                }
									]
		                    	}
							]
						}           
					]
	            },
	            {
	                xtype: 'tbseparator'
	            },
	            {
	                xtype: 'container',
	                autoEl: 'div',
	                layout: 'hbox',
	                height: 40,
	                items: [
	                    {
	                        xtype: 'button',
	                        text: EzDesk.endpoints.Locale.UnBind,
							name: 'UnBind',
	                        margins: '15 0 5 85',
	                        width: 120,
							handler: function () {
								//Ext.get('')
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
	                    },
	                    {
	                        xtype: 'button',
	                       text: EzDesk.endpoints.Locale.UnActive,
							disabled: app.isAllowedTo('addUserInfo', this.moduleId) ? false : true,
	                        margins: '15 0 5 60',
	                        width: 120
	                    }
	                ]
	            },
	            {
	                xtype: 'container',
	                autoEl: 'div',
	                layout: 'hbox',
	                height: 40,
	                items: [
	                    {
	                        xtype: 'button',
	                      text: EzDesk.endpoints.Locale.Rechage,
							name: 'Rechage',
							disabled: app.isAllowedTo('addUserInfo', this.moduleId) ? false : true,
	                        margins: '10 0 5 85',
	                        width: 120
	                    },
	                    {
	                        xtype: 'button',
	                       text: EzDesk.endpoints.Locale.GetCDR,
							name: 'GetCDR',
							disabled: app.isAllowedTo('addUserInfo', this.moduleId) ? false : true,
	                        margins: '10 0 5 60',
	                        width: 120
	                    }
	                ]
	            }
	        ];
	        EzDesk.unbindDeviceForm.superclass.initComponent.call(this);
	    }
	});

	
	if(!this.dialog){
       this.dialog = new Ext.Window({
	   	 	title: EzDesk.endpoints.Locale.EPTitle,
        	bodyStyle:'padding:10px',
            layout:'fit',
            width: 530,
            height: 555,
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
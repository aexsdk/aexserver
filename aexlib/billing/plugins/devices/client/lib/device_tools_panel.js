EzDesk.vpnGridPanel = Ext.extend(Ext.grid.GridPanel, {
    initComponent: function(){
        // Create RowActions Plugin
        this.action = new EzDesk.RowActions({
            header: EzDesk.devices.Locale.ActionText,
            align: 'center',
            keepSelection: true,
            actions: [{
                iconCls: 'icon-wrench',
                tooltip: 'Edit',
                qtipIndex: 'p_qtip',
                iconIndex: 'p_icon',
                hideIndex: 'p_hide'
                // ,text: '修改'
            }, {
                iconCls: 'icon-wrench',
                tooltip: 'Configure',
                qtipIndex: 'p_tip2',
                iconIndex: 'p_icon2',
                hideIndex: 'p_hide2'
                // ,text:'删除'
            }, {
                iconCls: 'icon-wrench',
                tooltip: 'Configure',
                qtipIndex: 'p_tip3',
                iconIndex: 'p_icon3',
                hideIndex: 'p_hide3'
                // ,text:'增加'
            }]
        });
        // dummy action event handler - just outputs some arguments to console
        this.action.on({
            action: function(grid, record, action, row, col){
				var data = record.data;
				switch(action) 
				{
					case 'icon-add-table':
					new EzDesk.addVPNDialog(grid.app, grid.moduleId, grid);
					break;
					case 'icon-edit-record':
					new EzDesk.editVPNDialog(grid.app, grid.moduleId, grid, data);
					break;
					case 'icon-cross':
					new EzDesk.deleteVPNDialog(grid.app, grid.moduleId, grid, data);
					break;
				}
            }
            
        });// eo privilege actions
        Ext.apply(this, {
            // autoWidth: true
            // height: 344
            store: new Ext.data.GroupingStore({
                reader: new Ext.data.JsonReader({
                    id: 'E164',
                    totalProperty: 'totalCount',
                    root: 'data',
                    fields: [{
                        name: 'E164',
                        type: 'string'
                    }, {
                        name: 'PID',
                        type: 'string'
                    }, {
                        name: 'VID',
                        type: 'string'
                    }, {
                        name: 'BandWidth',
                        type: 'string'
                    }, {
                        name: 'Remark',
                        type: 'string'
                    }, {
                        name: 'LogTime',
                        type: 'string'
                    }, {
                        name: 'p_qtip',
                        type: 'string'
                    }, {
                        name: 'p_icon',
                        type: 'string'
                    }, {
                        name: 'p_hide',
                        type: 'boolean'
                    }, {
                        name: 'p_qtip2',
                        type: 'string'
                    }, {
                        name: 'p_icon2',
                        type: 'string'
                    }, {
                        name: 'p_hide2',
                        type: 'boolean'
                    }, {
                        name: 'p_qtip3',
                        type: 'string'
                    }, {
                        name: 'p_icon3',
                        type: 'string'
                    }, {
                        name: 'p_hide3',
                        type: 'boolean'
                    }]
                }),
                proxy: new Ext.data.HttpProxy({
                    url: this.app.connection,
                    method: 'POST'
                
                }),
                baseParams: {
                    method: 'vpn_list',
                    moduleId: this.moduleId
                },
                listeners: {
                    load: {
                        scope: this,
                        fn: function(){
                            this.getSelectionModel().selectFirstRow();
                        }
                    }
                }
            }),
            columns: [{
                id: 'E164',
                header: EzDesk.devices.Locale.E164,
                fixed: true,
                width: 150,
                align: 'center',
                resizable: true,
                dataIndex: 'E164'
            }, {
                id: 'PID',
                header: EzDesk.devices.Locale.PID,
                width: 90,
                align: 'center',
                resizable: true,
                dataIndex: 'PID'
            }, {
                id: 'VID',
                header: EzDesk.devices.Locale.VID,
                width: 90,
                align: 'center',
                resizable: true,
                dataIndex: 'VID'
            }, {
                id: 'BandWidth',
                header: EzDesk.devices.Locale.Bandwidth,
                width: 100,
                align: 'center',
                resizable: true,
                dataindex: 'BandWidth'
            }, {
                id: 'Remark',
                header: EzDesk.devices.Locale.Remark,
                width: 230,
                align: 'center',
                resizable: true,
                dataindex: 'Remark'
            }, {
                id: 'LogTime',
                header: EzDesk.devices.Locale.LogTime,
                width: 120,
                align: 'center',
                resizable: true,
                dataindex: 'LogTime'
            }, this.action],
            plugins: [this.action],
            view: new Ext.grid.GroupingView({
                forceFit: true,
                groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
            }),
            loadMask: true
            // ,viewConfig:{forceFit:true}
        }); // eo apply
        // add paging toolbar
        this.bbar = new Ext.PagingToolbar({
            store: this.store,
            displayInfo: true,
            pageSize: 20
        });
        
        // call parent
        EzDesk.vpnGridPanel.superclass.initComponent.apply(this, arguments);
    } // eo function initComponent
    ,
    onRender: function(){
        // call parent
        EzDesk.vpnGridPanel.superclass.onRender.apply(this, arguments);
        // load the store
        this.store.load({
            params: {
                start: 0,
                limit: 20
            }
        });
        
    } // eo function onRender
}); // eo extend grid
Ext.reg('vpn_grid_panel', EzDesk.vpnGridPanel);

/*
 * wirter: lion wang caption: vpn control in wfs module version: 1.0 time:
 * 2010-05-14 last time: 2010-05-14
 */
EzDesk.vpnListDialog = function(app, moduleId){
    var desktop = app.getDesktop();
    var winManager = desktop.getManager();
    
    EzDeskMyPanelUi = Ext.extend(Ext.Panel, {
        layout: 'fit',
        initComponent: function(){
            this.items = [{
                xtype: 'vpn_grid_panel',
                id: 'vpn_grid_panel_obj',
                name: 'vpn_grid_panel_obj',
                app: app,
                moduleId: moduleId
            }];
            
            this.tbar = {
                xtype: 'toolbar',
                items: [{
                    xtype: 'label',
                    text: EzDesk.devices.Locale.WhereEx
                }, {
                    xtype: 'textfield',
                    fieldLabel: 'Label',
					id: 'vpn_filter'
                }, {
                    xtype: 'button',
                    text: EzDesk.devices.Locale.Query,
					handler: function(){
						var grid = Ext.getCmp('vpn_grid_panel_obj');
						if(grid){
							grid.store.removeAll();
							var value = Ext.get('vpn_filter').dom.value;
							grid.store.setBaseParam('p_id',value);
							grid.store.load(
								{params:{start:0, limit:20}}
							);
						}
					}
                }]
            }
            EzDeskMyPanelUi.superclass.initComponent.call(this);
        }
    });
    
    
    var colse = function(){
        this.dialog.hide();
    };
    
    if (!this.dialog) {
        this.dialog = new Ext.Window({
            title: EzDesk.devices.Locale.DeviceVPNTitle,
            bodyStyle: 'padding:10px',
            layout: 'fit',
            width: 940,
            height: 500,
            closeAction: 'hide',
            plain: true,
            items: [new EzDeskMyPanelUi()],
            manager: winManager,
            modal: true
        });
    }
    this.dialog.show();
};




/*
 * wirter: lion wang caption: vpn control in wfs module version: 1.0 time:
 * 2010-05-14 last time: 2010-05-14
 */
EzDesk.addVPNDialog = function(app, moduleId, grid){
    var desktop = app.getDesktop();
    var winManager = desktop.getManager();
    
    EzDesk.SimpleFormUi = Ext.extend(Ext.form.FormPanel, {
        labelWidth: 75,
        labelAlign: 'left',
        layout: 'form',
        id: 'vpnForm',
        // padding: 10,
        // frame: true,
        initComponent: function(){
            this.items = [{
                xtype: 'textfield',
                fieldLabel: EzDesk.devices.Locale.E164,
                anchor: '97%',
                name: 'e164',
                allowBlank: false
            }, {
                xtype: 'textfield',
                fieldLabel: EzDesk.devices.Locale.PID,
                anchor: '97%',
                name: 'p_id'
            }, {
                xtype: 'textfield',
                fieldLabel: EzDesk.devices.Locale.VID,
                anchor: '97%',
                name: 'v_id'
            }, {
                xtype: 'textfield',
                fieldLabel: EzDesk.devices.Locale.Bandwidth,
                anchor: '97%',
                name: 'bandwidth',
                allowBlank: false
            }, {
                xtype: 'textarea',
                fieldLabel: EzDesk.devices.Locale.Remark,
                name: 'remark',
                anchor: '97%'
            }];
            this.fbar = {
                xtype: 'toolbar',
                items: [{
                    xtype: 'button',
                    text: EzDesk.devices.Locale.Cancel,
                    hander: function(){
                		colse();
                	}
                }, {
                    xtype: 'button',
                    text: EzDesk.devices.Locale.Save,
                    handler: function(){
                        // Ext.get('')
                        Ext.getCmp('vpnForm').getForm().submit({
                            url: app.connection,
                            waitMsg: 'Loading',
                            method: 'POST',
                            params: {
                                method: 'vpn_control',
                                moduleId: moduleId
                            },
                            success: function(addUserForm, action){
                                // b.setDisabled(false);
                                var obj = Ext.util.JSON.decode(action.response.responseText);
                                // addUserForm.getForm().reset();
								 grid.store.load({
						            params: {
						                start: 0,
						                limit: 20
						            }
        						});
                                EzDesk.showMsg('VPN Bandwidth', obj.message, desktop);
                            },
                            failure: function(addUserForm, action){
                                // bbtn.setDisabled(false);
                                obj = Ext.util.JSON.decode(action.response.responseText);
                                if (action.failureType == 'server') {
                                    EzDesk.showMsg('VPN Bandwidth', obj.message, desktop);
                                }
                                else {
                                    EzDesk.showMsg('VPN Bandwidth', obj.message, desktop);
                                }
                            }
                        });
                    }
                }]
            };
            EzDesk.SimpleFormUi.superclass.initComponent.call(this);
        }
    });
    
    var colse = function(){
        this.dialog.hide();
    };
    
    if (!this.dialog) {
        this.dialog = new Ext.Window({
            title: EzDesk.devices.Locale.DeviceVPNTitle,
            bodyStyle: 'padding:10px',
            layout: 'fit',
            width: 430,
            height: 300,
            closeAction: 'hide',
            plain: true,
            items: [new EzDesk.SimpleFormUi()],
            manager: winManager,
            modal: true
        });
    }
    this.dialog.show();
};



/*
 * wirter: lion wang caption: vpn control in wfs module version: 1.0 time:
 * 2010-05-14 last time: 2010-05-14
 */
EzDesk.deleteVPNDialog = function(app, moduleId, grid, data){
    var desktop = app.getDesktop();
    var winManager = desktop.getManager();
	
	 var win = desktop.createWindow({
        title: 'Delete Record',
        frame: true,
        maximizable: false,
        width: 300,
        height: 180,
        bodyStyle: 'text-align:center;word-break:break-all',
        buttonAlign: 'center',
        html: '<br/><br/><br/>' + 'Do you really want to delete <b>' + data.E164 + '</b><br/>There is no undo.',
        buttons: [{
	            text: EzDesk.devices.Locale.Cancel,
	            handler: function(){
	                this.ownerCt.ownerCt.close();
	            }
			},
			{
				text: EzDesk.devices.Locale.Delete,
	            handler: function(){
					this.ownerCt.ownerCt.close();
	                Ext.Ajax.request({
						url: app.connection
						,params: {
							method: 'vpn_delete',
                            moduleId: moduleId,
							e164: data.E164,
							p_id: data.PID,
							v_id: data.VID
						}
						,success: function(o){
							var obj = Ext.util.JSON.decode(o.responseText);
                            // addUserForm.getForm().reset();
							 grid.store.load({
						            params: {
						                start: 0,
						                limit: 20
						            }
        						});
                            EzDesk.showMsg('VPN', obj.message, desktop);
							
						}
						,failure: function(){
							
						}
					});
            }
        }]
    });
    win.show();
};


/*
 * wirter: lion wang caption: vpn control in wfs module version: 1.0 time:
 * 2010-05-14 last time: 2010-05-14
 */
EzDesk.editVPNDialog = function(app, moduleId, grid, data){
    var desktop = app.getDesktop();
    var winManager = desktop.getManager();
 
    EzDesk.FormUi = Ext.extend(Ext.form.FormPanel, {
        labelWidth: 75,
        labelAlign: 'left',
        layout: 'form',
        id: 'editVPNForm',
       // padding: 10,
       // frame: true,
        initComponent: function(){
            this.items = [{
                xtype: 'textfield',
                fieldLabel: EzDesk.devices.Locale.E164,
                anchor: '97%',
                name: 'e164',
				value : data.E164,
                allowBlank: false
            }, {
                xtype: 'textfield',
                fieldLabel: EzDesk.devices.Locale.PID,
                anchor: '97%',
				value : data.PID,
                name: 'p_id'
            }, {
                xtype: 'textfield',
                fieldLabel: EzDesk.devices.Locale.VID,
                anchor: '97%',
				value : data.VID,
                name: 'v_id'
            }, {
                xtype: 'textfield',
                fieldLabel: EzDesk.devices.Locale.Bandwidth,
                anchor: '97%',
                name: 'bandwidth',
				value : data.BandWidth,
                allowBlank: false
            }, {
                xtype: 'textarea',
                fieldLabel: EzDesk.devices.Locale.Remark,
                name: 'remark',
				value : data.Remark,
                anchor: '97%'
            }];
            this.fbar = {
                xtype: 'toolbar',
                items: [{
                    xtype: 'button',
                    text: 'Cancel'
                }, {
                    xtype: 'button',
                    text: 'Save',
                    handler: function(){
                        // Ext.get('')
                        Ext.getCmp('editVPNForm').getForm().submit({
                            url: app.connection,
                            waitMsg: 'Loading',
                            method: 'POST',
                            params: {
                                method: 'vpn_edit',
                                moduleId: moduleId,
								o_e164: data.E164,
								o_pid: data.PID,
								o_vid: data.VID
                            },
                            success: function(addUserForm, action){
                                // b.setDisabled(false);
                                var obj = Ext.util.JSON.decode(action.response.responseText);
                                // addUserForm.getForm().reset();
								 grid.store.load({
						            params: {
						                start: 0,
						                limit: 20
						            }
        						});
                                EzDesk.showMsg('VPN Bandwidth', obj.message, desktop);
                            },
                            failure: function(addUserForm, action){
                                // bbtn.setDisabled(false);
                                obj = Ext.util.JSON.decode(action.response.responseText);
                                if (action.failureType == 'server') {
                                    EzDesk.showMsg('VPN Bandwidth', obj.message, desktop);
                                }
                                else {
                                    EzDesk.showMsg('VPN Bandwidth', obj.message, desktop);
                                }
                            }
                        });
                    }
                }]
            };
            EzDesk.FormUi.superclass.initComponent.call(this);
        }
    });
   	
    var colse = function(){
        this.dialog.hide();
    };
    
    if (!this.dialog) {
        this.dialog = new Ext.Window({
            title: EzDesk.devices.Locale.DeviceVPNTitle,
            bodyStyle: 'padding:10px',
            layout: 'fit',
            width: 430,
            height: 300,
            closeAction: 'hide',
            plain: true,
            items: [new EzDesk.FormUi()],
            manager: winManager,
            modal: true
        });
    }
	// Ext.getCmp('editVPNForm').getForm().findField('e164').setValue('Mueller');
	// Ext.getCmp('editVPNForm').getForm().setValues({ e164: data.E164, p_id:
	// data.PID, v_id: data.VID, bandwidth: data.BandWidth, remark:data.Remark
	// });
    this.dialog.show();
};



/*
 * wirter: lion wang caption: device tools in wfs module version: 1.0 time:
 * 2010-04-19 last time: 2010-04-19
 */
EzDesk.unbindDialog = function(app, moduleId){
    var desktop = app.getDesktop();
    var winManager = desktop.getManager();
    
    EzDesk.unbindDeviceForm = Ext.extend(Ext.form.FormPanel, {
        labelWidth: 100,
        labelAlign: 'top',
        id: 'unbindDeviceForm',
        layout: 'form',
		padding: 1,
		frame: true,
        initComponent: function(){
			this.items =  [{
                xtype: 'tabpanel',
                activeTab: 0,
                deferredRender: false,
                items: [{
                	 xtype: 'panel',
                     title: EzDesk.devices.Locale.DeviceTab,
                     layout: 'fit',
                     labelAlign: 'top',
                     items: [{   
     				    xtype: 'fieldset',
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
    								fieldLabel: EzDesk.devices.Locale.Password,
    								name: 'Password',
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
    								anchor: '97%'
    					       }]
    					   }]
    					}]	
                    }]	
                },{
                	  xtype: 'panel',
                      title: EzDesk.devices.Locale.BillingTab,
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
                                              fieldLabel: EzDesk.devices.Locale.Account,
												name: 'Account',
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
                                      margins: '2 5 0 15',
                                      items: [
                                          {
                                              xtype: 'textfield',
                                              fieldLabel: EzDesk.devices.Locale.Password,
												name: 'Password',
                                              anchor: '97%'
                                          }
                                      ]
                                  }
                              ]
                          },
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
                                      margins: '0 5 0 12',
                                      items: [
                                          {
                                              xtype: 'textfield',
                                              fieldLabel: EzDesk.devices.Locale.CurrentBalance,
												name: 'CurrentBalance',
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
                                              fieldLabel: EzDesk.devices.Locale.AccountStatus,
												name: 'AccountStatus',
                                              anchor: '97%'
                                          }
                                      ]
                                  }
                              ]
                          },
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
                                      margins: '0 5 0 12',
                                      items: [
                                          {
                                              xtype: 'textfield',
                                              fieldLabel: EzDesk.devices.Locale.UserPlan,
												name: 'UserPlan',
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
                                              fieldLabel: EzDesk.devices.Locale.AgentPlan,
                                              name: 'AgentPlan',
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
                                              fieldLabel: EzDesk.devices.Locale.AgentBalance,
												name: 'AgentBalance',
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
                                              fieldLabel: EzDesk.devices.Locale.GiveAccount,
												name: 'GiveAccount',
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
                                              fieldLabel: EzDesk.devices.Locale.IncreaseAccount,
												name: 'IncreaseAccount',
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
                                              fieldLabel: EzDesk.devices.Locale.HireDuration,
												name: 'HireDuration',
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
                                              fieldLabel: EzDesk.devices.Locale.HireStartTime,
												name: 'HireStartTime',
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
                                              fieldLabel: EzDesk.devices.Locale.Hiredate,
												name: 'Hiredate',
                                              anchor: '97%'
                                          }
                                      ]
                                  }
                              ]
                          }
                      ]
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
				    		// b.setDisabled(false);
					    	var obj = Ext.util.JSON.decode(action.response.responseText);
							// addUserForm.getForm().reset();
							EzDesk.showMsg('unbind', obj.message, desktop);
					    },
					    failure: function(addUserForm, action){
				         // bbtn.setDisabled(false);
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
		        text: EzDesk.devices.Locale.Close,
		        width: 120,
		        hander: function(){
		    		this.close();
		    	}
		    }];
			this.tbar = {
			    xtype: 'toolbar',
				padding: 2,
				items: [{
				     xtype: 'tbtext',
				     text: EzDesk.devices.Locale.WhereEx
				}, {
					 xtype: 'combo',
					 fieldLabel: 'Label',
					 valueField: 'type',
					 allowBlank: false,
					 forceSelection: true,
					 displayField: 'state',
					 typeAhead: true,
					 selectOnFocus: true,
					 emptyText: EzDesk.devices.Locale.QueryTypeText,
					 store: new Ext.data.SimpleStore({
					 fields: ['type', 'state'],
					 	data: [['1', 'Mobile no.'], ['2', 'IMEI']]
					 }),
					 mode: 'local',
					 triggerAction: 'all',
					 hiddenName: 'queryToolType',
					 width: 133
				}, {
					xtype: 'tbseparator'
				}, {
					xtype: 'textfield',
					fieldLabel: 'Label',
					name: 'queryToolValue',
					id: 'queryToolValue',
				    	width: 156,
				    	allowBlank: false
				 }, 
					{
				     xtype: 'tbseparator'
				 }, {
					 xtype: 'button',
					 text: EzDesk.devices.Locale.Query,
					 name: 'Query',
					 width: 100,
					 handler: function(){
					     Ext.getCmp('unbindDeviceForm').load({
					     url: app.connection,
					     waitMsg: 'Loading',
					     method: 'POST',
					     params: {
					         method: 'DTQuery',
					         moduleId: moduleId,
					         queryType: Ext.get('queryToolType').dom.value,
					         queryValue: Ext.get('queryToolValue').dom.value
					     },
					     failure: function(addUserForm, action){
					         // bbtn.setDisabled(false);
					         if (action.failureType == 'server') {
					             var obj = Ext.util.JSON.decode(action.response.responseText);
					             EzDesk.showMsg('Query', obj.message, desktop);
					         }
					         else {
					             Ext.getCmp('unbindDeviceForm').getForm().reset();
					             var obj = Ext.util.JSON.decode(action.response.responseText);
					             EzDesk.showMsg('Query', obj.message, desktop);
					         }
					     }
					  });
				 	}
				 }]
			 };
    		   
    		EzDesk.unbindDeviceForm.superclass.initComponent.call(this);
    	}
    });
    
    var colse = function(){
        this.dialog.hide();
    };
    
    if (!this.dialog) {
        this.dialog = new Ext.Window({
            title: EzDesk.devices.Locale.DeviceToolsTitle,
            bodyStyle: 'padding:10px',
            layout: 'fit',
            width: 530,
            height: 540,
            closeAction: 'hide',
            plain: true,
            items: [new EzDesk.unbindDeviceForm()],
            manager: winManager,
            modal: true
        });
    }
    this.dialog.show();
};


/**
 * Create Ez Message BOX wirter: lion wang version: 1.0 time: 2010-04-19 last
 * time: 2010-04-19
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
 * Create Ez wait BOX wirter: lion wang version: 1.0 time: 2010-04-19 last time:
 * 2010-04-19
 */
EzDesk.showWaitMsg = function(tl, msg, desktop){
    var win = desktop.createWindow({
        // msg: 'Saving your data, please wait...',
        progressText: 'Loading...',
        width: 300,
        wait: true,
        waitConfig: {
            interval: 200
        },
        // icon:'ext-mb-download', //custom class in msg-box.html
        animEl: 'mb7'
    });
    win.show();
};




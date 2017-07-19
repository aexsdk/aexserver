Ext.namespace('EzDesk');

EzDesk.aniGridPanel = Ext.extend(Ext.grid.GridPanel, {
    initComponent: function(){
        // Create RowActions Plugin
        this.action = new EzDesk.RowActions({
            header: EzDesk.endpoints.Locale.ActionText,
            align: 'center',
            keepSelection: true,
            actions: [{
                iconCls: 'icon-wrench',
                tooltip: 'Edit',
                qtipIndex: 'p_qtip',
                iconIndex: 'p_icon',
                hideIndex: 'p_hide'
                //	,text: '修改'
            }, {
                iconCls: 'icon-wrench',
                tooltip: 'Configure',
                qtipIndex: 'p_tip2',
                iconIndex: 'p_icon2',
                hideIndex: 'p_hide2'
                //,text:'删除'
            }, {
                iconCls: 'icon-wrench',
                tooltip: 'Configure',
                qtipIndex: 'p_tip3',
                iconIndex: 'p_icon3',
                hideIndex: 'p_hide3'
                //,text:'增加'
            }]
        });
        // dummy action event handler - just outputs some arguments to console
        this.action.on({
            action: function(grid, record, action, row, col){
				var data = record.data;
				switch(action) 
				{
					case 'icon-add-table':
					new EzDesk.addANIDialog(grid.app, grid.moduleId, grid);
					break;
					case 'icon-edit-record':
					new EzDesk.editANIDialog(grid.app, grid.moduleId, grid, data);
					break;
					case 'icon-cross':
					new EzDesk.deleteANIDialog(grid.app, grid.moduleId, grid, data);
					break;
				}
            }
            
        });//eo privilege  actions
        Ext.apply(this, {
            //autoWidth: true
            //height: 344
            store: new Ext.data.GroupingStore({
                reader: new Ext.data.JsonReader({
                    id: 'ANI',
                    totalProperty: 'totalCount',
                    root: 'data',
                    fields: [{
                        name: 'ANI',
                        type: 'string'
                    }, {
                        name: 'E164',
                        type: 'string'
                    }, {
                        name: 'PIN',
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
            		method: 'ani_list',
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
                id: 'ANI',
                header: EzDesk.endpoints.Locale.PhoneNO,
                fixed: true,
                width: 150,
                align: 'center',
                resizable: true,
                dataIndex: 'ANI'
            }, {
                id: 'E164',
                header: EzDesk.endpoints.Locale.Endpoint,
                width: 90,
                align: 'center',
                resizable: true,
                dataIndex: 'E164'
            }, {
                id: 'PIN',
                header: EzDesk.endpoints.Locale.PIN,
                width: 90,
                align: 'center',
                resizable: true,
                dataIndex: 'PIN'
            }, {
                id: 'LogTime',
                header: EzDesk.endpoints.Locale.LogTime,
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
            //,viewConfig:{forceFit:true}
        }); // eo apply
        // add paging toolbar
        this.bbar = new Ext.PagingToolbar({
            store: this.store,
            displayInfo: true,
            pageSize: 20
        });
        
        // call parent
        EzDesk.aniGridPanel.superclass.initComponent.apply(this, arguments);
    } // eo function initComponent
    ,
    onRender: function(){
        // call parent
        EzDesk.aniGridPanel.superclass.onRender.apply(this, arguments);
        // load the store
        this.store.load({
            params: {start: 0,limit: 20}
	        ,callback :function(r,options,success) {	
				if(!success){
					var notifyWin = this.desktop.showNotification({
				        html: this.store.reader.jsonData.message.toString()
						, title: lang_tr.Error
				      });
				}
			}
			,scope:this		        
        });
        
    } // eo function onRender
}); // eo extend grid
Ext.reg('ani_grid_panel', EzDesk.aniGridPanel);

/*
 wirter:  lion wang
 caption: vpn control  in  wfs module
 version: 1.0
 time: 2010-05-14
 last time: 2010-05-14
 */
EzDesk.aniListDialog = function(app, moduleId){
    var desktop = app.getDesktop();
    var winManager = desktop.getManager();
    EzDeskMyPanelUi = Ext.extend(Ext.Panel, {
        layout: 'fit',
        initComponent: function(){
            this.items = [{
                xtype: 'ani_grid_panel',
                id: 'ani_grid_panel',
                name: 'ani_grid_panel',
                app: app,
                moduleId: moduleId
            }];
            
            this.tbar = {
                xtype: 'toolbar',
                items: [{
                    xtype: 'label',
                    text: EzDesk.endpoints.Locale.WhereEx
                }, {
                    xtype: 'textfield',
                    fieldLabel: 'Label',
					id: 'ani_filter'
                }, {
                    xtype: 'button',
                    text: EzDesk.endpoints.Locale.Query,
					handler: function(){
						var grid = Ext.getCmp('ani_grid_panel');
						if(grid){
							grid.store.removeAll();
							var value = Ext.get('ani_filter').dom.value;
							grid.store.setBaseParam('endpoint',value);
							grid.store.load(
								{params:{start:0, limit:20}}
							);
						}
					}
                }]
            };
            EzDeskMyPanelUi.superclass.initComponent.call(this);
        }
    });
    
    
    var colse = function(){
        this.dialog.hide();
    };
    
    if (!this.dialog) {
        this.dialog = new Ext.Window({
            title: EzDesk.endpoints.Locale.DeviceVPNTitle,
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
 wirter:  lion wang
 caption: vpn control  in  wfs module
 version: 1.0
 time: 2010-05-14
 last time: 2010-05-14
 */
EzDesk.addANIDialog = function(app, moduleId, grid){
    var desktop = app.getDesktop();
    var winManager = desktop.getManager();
    
    EzDesk.SimpleFormUi = Ext.extend(Ext.form.FormPanel, {
        labelWidth: 75,
        labelAlign: 'left',
        layout: 'form',
        id: 'aniForm',
        padding: 10,
        frame: true,
        initComponent: function(){
            this.items = [{
                xtype: 'textfield',
                fieldLabel: EzDesk.endpoints.Locale.PhoneNO,
                anchor: '100%',
                name: 'ANI',
                allowBlank: false
            }, {
                xtype: 'textfield',
                fieldLabel: EzDesk.endpoints.Locale.Endpoint,
                anchor: '100%',
                name: 'E164'
            }, {
                xtype: 'textfield',
                fieldLabel: EzDesk.endpoints.Locale.PIN,
                anchor: '100%',
                name: 'PIN'
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
                        //Ext.get('')
                        Ext.getCmp('aniForm').getForm().submit({
                            url: app.connection,
                            waitMsg: 'Loading',
                            method: 'POST',
                            params: {
                        	method: 'ani_add',
                                moduleId: moduleId
                            },
                            success: function(addUserForm, action){
                                //b.setDisabled(false);	
                                var obj = Ext.util.JSON.decode(action.response.responseText);
                                //addUserForm.getForm().reset();
								 grid.store.load({
						            params: {
						                start: 0,
						                limit: 20
						            }
        						});
                                EzDesk.showMsg('VPN Bandwidth', obj.message, desktop);
                            },
                            failure: function(addUserForm, action){
                                //bbtn.setDisabled(false);
                                obj = Ext.util.JSON.decode(action.response.responseText);
                                if (action.failureType == 'server') {
                                    EzDesk.showMsg('ANI Manage', obj.message, desktop);
                                }
                                else {
                                    EzDesk.showMsg('ANI Manage', obj.message, desktop);
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
            title: EzDesk.endpoints.Locale.DeviceVPNTitle,
            bodyStyle: 'padding:10px',
            layout: 'fit',
            width: 330,
            height: 200,
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
 wirter:  lion wang
 caption: vpn control  in  wfs module
 version: 1.0
 time: 2010-05-14
 last time: 2010-05-14
 */
EzDesk.deleteANIDialog = function(app, moduleId, grid, data){
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
        html: '<br/><br/><br/>' + 'Do you really want to delete <b>' + data.ANI + '</b><br/>There is no undo.',
        buttons: [{
	            text: 'Cancel',
	            handler: function(){
	                this.ownerCt.ownerCt.close();
	            }
			},
			{
				text: 'Save',
	            handler: function(){
					this.ownerCt.ownerCt.close();
	                Ext.Ajax.request({
						url: app.connection
						,params: {
	                		method: 'ani_delete',
                            moduleId: moduleId,
							ani: data.ANI
						}
						,success: function(o){
							var obj = Ext.util.JSON.decode(o.responseText);
                            //addUserForm.getForm().reset();
							 grid.store.load({
						            params: {
						                start: 0,
						                limit: 20
						            }
        						});
                            EzDesk.showMsg('ANI Manage', obj.message, desktop);
							
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
 wirter:  lion wang
 caption: vpn control  in  wfs module
 version: 1.0
 time: 2010-05-14
 last time: 2010-05-14
 */
EzDesk.editANIDialog = function(app, moduleId, grid, data){
    var desktop = app.getDesktop();
    var winManager = desktop.getManager();
 
    EzDesk.FormUi = Ext.extend(Ext.form.FormPanel, {
        labelWidth: 75,
        labelAlign: 'left',
        layout: 'form',
        id: 'editANIForm',
        padding: 10,
        frame: true,
        initComponent: function(){
            this.items = [{
                xtype: 'textfield',
                fieldLabel: EzDesk.endpoints.Locale.PhoneNO,
                anchor: '100%',
                name: 'ANI',
				value : data.ANI,
                allowBlank: false
            }, {
                xtype: 'textfield',
                fieldLabel: EzDesk.endpoints.Locale.Endpoint,
                anchor: '100%',
				value : data.E164,
                name: 'E164'
            }, {
                xtype: 'textfield',
                fieldLabel: EzDesk.endpoints.Locale.PIN,
                anchor: '100%',
				value : data.PIN,
                name: 'PIN'
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
                        //Ext.get('')
                        Ext.getCmp('editANIForm').getForm().submit({
                            url: app.connection,
                            waitMsg: 'Loading',
                            method: 'POST',
                            params: {
                        		method: 'ani_edit',
                                moduleId: moduleId,
								o_ani: data.ANI
                            },
                            success: function(addUserForm, action){
                                //b.setDisabled(false);	
                                var obj = Ext.util.JSON.decode(action.response.responseText);
                                //addUserForm.getForm().reset();
								 grid.store.load({
						            params: {
						                start: 0,
						                limit: 20
						            }
        						});
                                EzDesk.showMsg('VPN Bandwidth', obj.message, desktop);
                            },
                            failure: function(addUserForm, action){
                                //bbtn.setDisabled(false);
                                obj = Ext.util.JSON.decode(action.response.responseText);
                                if (action.failureType == 'server') {
                                    EzDesk.showMsg('ANI Manage', obj.message, desktop);
                                }
                                else {
                                    EzDesk.showMsg('ANI Manage', obj.message, desktop);
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
            title: EzDesk.endpoints.Locale.ANITitle,
            bodyStyle: 'padding:10px',
            layout: 'fit',
            width: 330,
            height: 200,
            closeAction: 'hide',
            plain: true,
            items: [new EzDesk.FormUi()],
            manager: winManager,
            modal: true
        });
    }
	//Ext.getCmp('editVPNForm').getForm().findField('e164').setValue('Mueller');
	//Ext.getCmp('editVPNForm').getForm().setValues({ e164: data.E164, p_id: data.PID, v_id: data.VID, bandwidth: data.BandWidth, remark:data.Remark  });
    this.dialog.show();
};

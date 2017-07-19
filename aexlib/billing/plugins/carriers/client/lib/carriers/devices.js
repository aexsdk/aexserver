/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */
EzDesk.carriers.devices = function(ownerModule){
    this.addEvents({
        'carrieredited': true
    });
    
    this.ownerModule = ownerModule;
    
    EzDesk.carriers.devices.superclass.constructor.call(this, {
        title: this.ownerModule.locale.Devices,
        closable: true,
        iconCls: 'm-carriers-icon',
        id: 'ez-carriers_devices',
        layout: 'border',
        items: [{
            xtype: 'devices_grid_panel',
            id: 'devices_grid_panel',
            name: 'devices_grid_panel',
            ownerModule:this.ownerModule,
            app: this.app,
            connect: this.ownerModule.app.connection,
            moduleId: this.ownerModule.id,
            locale: this.ownerModule.locale
        }],
        tbar: [{
            //disabled: this.ownerModule.app.isAllowedTo('viewAllCarriers', this.ownerModule.id) ? false : true
            handler: this.onRefreshClick,
            iconCls: 'qo-admin-refresh',
            scope: this,
            tooltip: this.ownerModule.locale.field.refresh
        
        }, '-', {
            //disabled: this.ownerModule.app.isAllowedTo('addDevices', this.ownerModule.id) ? false : true
            handler: this.onAddClick,
            iconCls: 'qo-admin-add',
            scope: this,
            text: this.ownerModule.locale.field.add,
            tooltip: this.ownerModule.locale.field.add_new_group
        }, {
            //disabled: this.ownerModule.app.isAllowedTo('editDevices', this.ownerModule.id) ? false : true
            handler: this.onEditClick,
            iconCls: 'qo-admin-edit',
            scope: this,
            text: this.ownerModule.locale.field.edit,
            tooltip: 'Edit selected'
        }, {
            //disabled: this.ownerModule.app.isAllowedTo('deleteDevices', this.ownerModule.id) ? false : true
            handler: this.onDeleteClick,
            iconCls: 'qo-admin-delete',
            scope: this,
            text: this.ownerModule.locale.field.del,
            tooltip: this.ownerModule.locale.field.delete_selected
        },{
            xtype: 'tbseparator'
        },
        {
            xtype: 'combo',
            width: 85,
            id: 'fd_time_type',
            editable:false,
        	typeAhead: true,
            triggerAction: 'all',
            lazyRender:true,
            mode: 'local',
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: [
                    'code',
                    'displayText'
                ],
                data: [['is_time', lang_tr.is_time], ['os_time', lang_tr.os_time],['active_time',lang_tr.active_time]]
            }),
            valueField: 'code',
            displayField: 'displayText'
            ,value : 'is_time'
        },
        {
            xtype: 'tbtext',
            text: lang_tr.From
        },
        {
            xtype: 'datefield',
            fieldLabel: lang_tr.From,
            width: 130,
            id: 'fd_from',
            format:'Y-m-d H:i:s',
            value:new Date((new Date()).getFullYear(),(new Date()).getMonth(),(new Date()).getDate())
        },
        {
            xtype: 'tbtext',
            text: lang_tr.To
        },
        {
            xtype: 'datefield',
            fieldLabel: lang_tr.To,
            width: 130,
            id: 'fd_to',
            format:'Y-m-d H:i:s',
            value:new Date((new Date()).getFullYear(),(new Date()).getMonth(),(new Date()).getDate()+1)
        },{
            xtype: 'tbfill'
        },{
            xtype: 'tbseparator'
        },{
            xtype: 'label',
            text: this.ownerModule.locale.WhereEx,
            width: 26
        }, {
            xtype: 'textfield',
            name: 'queryValue',
            id: 'queryValue',
            width: 150,
            allowBlank: false
        }, {
            xtype: 'button',
            text: this.ownerModule.locale.Query,
            name: lang_tr.Query,
            width: 50
            ,handler:  this.onRefreshClick
            ,scope : this
        }]
    });
};

Ext.extend(EzDesk.carriers.devices, EzDesk.BillingPanel, {
    onActiveToggled: function(record){
        this.fireEvent('carrieredited', record);
    },
    onAddClick: function(){
        var deviceWizForm = new EzDesk.deviceWizForm({
            ownerModule: this.ownerModule,
            scope: this
        });
        deviceWizForm.show();
    },
    onDeleteClick: function(){
    	var grid = Ext.getCmp('devices_grid_panel');
        var sm = grid.getSelectionModel(), count = sm.getCount();
        
        if (count > 0) {
            Ext.MessageBox.confirm('Confirm', 'Are you sure you want to delete the selected device(s)?', function(btn){
                if (btn === "yes") {
                    this.showMask('Deleting...');
                    var selected = sm.getSelections();
                    var bsn = selected[0].bsn;
                    var imei = selected[0].imei;
                    var pid = selected[0].PID;
                    var vid = selected[0].VID;
                	this.send_request({
                		waitMsg: 'Deleting...'
                		,params: {
                            method: "delete_device",
                            moduleId: this.ownerModule.id,
                            bsn: bsn,
                            imei:imei,
                            pid:pid,
                            vid:vid
                            , domain: oem_domain
    						, resaler :os_resaler
                        }
                	});
                	this.onRefreshClick();                    
                }
            }, this);
        }
    },
    onEditClick: function(){
        var grid = Ext.getCmp('devices_grid_panel');
        
        var record = grid.getSelectionModel().getSelected();
        
        if (record) {
            //var id = record.id, g = this.grid, s = g.getStore();
            
            // callback to reload the grid, fire
            var callback = function(){
                s.reload();
            };
            var desktop = this.ownerModule.app.getDesktop();
            var winManager = desktop.getManager();
            
            var close = function(){
                this.win.close();
            }
            if (!this.win) {
                this.win = new Ext.Window({
                    width: 480,
                    height: 500,
                    buttonAlign: "center",
                    title: EzDesk.carriers.Locale.launcherText,
                    modal: true,
                    shadow: true,
                    closeAction: "hide",
                    items: [new EzDesk.deviceEditForm({
                    	name:'deviceEditForm',
                        record: record.data
                    })],
                    manager: winManager,
                    maximized: false,
                    modal: true,
                    border:false,
                    buttons : [{
                        text: EzDesk.carriers.Locale.Close,
                        width: 120,
                        hander: function(){
                    		this.close();
                        }
                        ,scope:this.win
                    }]        
                });
            }
            var dform = Ext.getCmp('deviceEditForm');

            if(dform)
            	dform.bindData(record.data);
            this.win.show();
            
        }
    },
    grid_load_callback:function(){
    	//this.hideMask(this);
    },
    onRefreshClick: function(){
        //this.showMask('Refreshing...',this);
        var grid = Ext.getCmp('devices_grid_panel');
        
        grid.store.setBaseParam('time_type',EzDesk.get_cmp_value('fd_time_type'));
        grid.store.setBaseParam('time_from',EzDesk.get_cmp_value('fd_from'));
        grid.store.setBaseParam('time_to',EzDesk.get_cmp_value('fd_to'));
        grid.store.setBaseParam('filter',EzDesk.get_cmp_value('queryValue'));
        grid.store.setBaseParam('domain',oem_domain);
        grid.store.setBaseParam('resaler',os_resaler);
        grid.store.reload({
            callback: this.grid_load_callback,
            scope: this
        });
    },
    viewDetail: function(sm, index, record){
        if (record && record.data) {
            var data = record.data;
        }
    }
});


EzDesk.deviceWizForm = Ext.extend(Ext.ux.Wiz, {
    constructor: function(config){
        config = config || {};
        this.ownerModule = config.ownerModule;
		var v_carrier_id;
		var v_agent_id;
        Ext.applyIf(config, {
            autoScroll: true,
            animCollapse: false,
            constrainHeader: true,
            iconCls: this.iconCls,
            layout: 'fit',
            maximizable: false,
            manager: this.ownerModule.app.getDesktop().getManager(),
            modal: true,
            shim: false,
            title: this.ownerModule.locale.devices_form.addtitle,
            headerConfig: {
                title: this.ownerModule.locale.devices_form.addtitle
            },
            cardPanelConfig: {
                defaults: {
                    baseCls: 'x-small-editor',
                    border: false,
                    bodyStyle: 'padding:10px 10px 10px 10px;background-color:#F6F6F6;'
                }
            },
            cards: [new Ext.ux.Wiz.Card({
                title: lang_tr.Welcome,
                id: 'mawc_welcome',
                monitorValid: false,
                defaults: {
                    labelStyle: 'font-size:12px'
                },
                items: [{
                    border: false,
                    bodyStyle: 'background:none;padding-bottom:10px;',
                    html: this.ownerModule.locale.devices_form.f_page
                }]
            }), new Ext.ux.Wiz.Card({
                title: this.ownerModule.locale.devices_form.base_info_title,
                id: 'base_info',
                monitorValid: true,
                defaults: {
                    labelStyle: 'font-size:12px'
                },
                items: [{
                    border: false,
                    bodyStyle: 'background:none;padding-bottom:10px;',
                    html: this.ownerModule.locale.devices_form.s_page
                }, {
                    xtype: 'combo',
					name: 'd_c_name',
					id: 'd_c_name',
					editable:false,
                    fieldLabel: this.ownerModule.locale.field.Name,
                    hiddenName: 'carrier_id',
                    store: new EzDesk.Carriers.nameStore({
                        desktop: this.ownerModule.app.getDesktop(),
                        moduleId: this.ownerModule.id,
                        connection: this.ownerModule.app.connection
                        , domain: oem_domain
						, resaler :os_resaler
                    }),
                    valueField: 'carrier_id',
                    displayField: 'carrier_name',
                    typeAhead: true,
                    mode: 'remote',
                    triggerAction: 'all',
                    emptyText: this.ownerModule.locale.devices_form.c_name_empty_text,
                    selectOnFocus: true,
                    anchor: '95%', 
					listeners: {
		                select: function(combo, record, index){
		                	var agent = Ext.getCmp('fd_agent_id');
		                	var agent_cs = Ext.getCmp('fd_agent_cs_id');
		                	if(agent_cs)
		                		agent_cs.store.setBaseParam('filtter',combo.value);
		                	var user_cs = Ext.getCmp('fd_user_cs_id');
		                	if(user_cs)
		                		user_cs.store.setBaseParam('filtter',combo.value);
							if(agent){
								agent.store.setBaseParam('filtter',combo.value);
								agent.store.removeAll();
								agent.reset();
								agent.store.load();
							}
		                }
		            }
                }, {
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.VID,
                    name: 'VID',
                    anchor: '95%'
                }, {
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.PID,
                    name: 'PID',
                    anchor: '95%'
                }, {
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.remark,
                    name: 'remark',
                    anchor: '95%'
                }, {
                    xtype: 'textarea',
                    id: 'imei',
                    name: 'imei',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.imei,
                    height: 80,
                    anchor: '95%'
                }]
            }), new Ext.ux.Wiz.Card({
                title: this.ownerModule.locale.devices_form.agent_info_title,
                id: 'agent_info',
                monitorValid: true,
                defaults: {
                    labelStyle: 'font-size:12px'
                },
                items: [{
                    border: false,
                    bodyStyle: 'background:none;padding-bottom:10px;padding-top:10px;',
                    html: this.ownerModule.locale.devices_form.t_page
                }, {
                    xtype: 'BProductType',
                    name: 'product_type',
                    anchor: '95%'
                }, {
                    xtype: 'combo',
                    fieldLabel: this.ownerModule.locale.devices_grid.agent_id,
                    hiddenName: 'agent_id',
                    id:'fd_agent_id',
                    editable:false,
                    store: new EzDesk.Billing.agentStore({
                        desktop: this.ownerModule.app.getDesktop(),
                        moduleId: this.ownerModule.id,
                        connection: this.ownerModule.app.connection,
						filtter: 'utone'
						, domain: oem_domain
						, resaler :os_resaler
                    }),
                    disable: true,
                    valueField: 'agent_id',
                    displayField: 'agent_name',
                    typeAhead: true,
                    mode: 'remote',
                    triggerAction: 'all',
                    emptyText: this.ownerModule.locale.devices_form.c_name_empty_text,
                    selectOnFocus: true,
                    anchor: '95%',
					listeners: {
		                select: function(combo, record, index){
		                	var agent_cs = Ext.getCmp('fd_agent_cs_id');
							if(agent_cs){
								agent_cs.store.setBaseParam('status',combo.value);
								agent_cs.store.removeAll();
								agent_cs.reset();
								agent_cs.store.load();
							}
							var user_cs = Ext.getCmp('fd_user_cs_id');
							if(user_cs){
								user_cs.store.setBaseParam('status',combo.value);
								user_cs.store.removeAll();
								user_cs.reset();
								user_cs.store.load();
							}
		                }
            		}
                }, {
					xtype: 'combo',
                    fieldLabel: this.ownerModule.locale.devices_grid.agent_cs,
                    hiddenName: 'agent_cs_id',
                    id:'fd_agent_cs_id',
                    editable:false,
                    store: new EzDesk.Billing.agentCSStore({
                        desktop: this.ownerModule.app.getDesktop(),
                        moduleId: this.ownerModule.id,
                        connection: this.ownerModule.app.connection,
						filtter: 'utone',
						status: ''
						, domain: oem_domain
						, resaler :os_resaler
                    }),
                    disable: true,
                    valueField: 'agent_cs_id',
                    displayField: 'agent_cs_name',
                    typeAhead: true,
                    mode: 'remote',
                    triggerAction: 'all',
                    emptyText: this.ownerModule.locale.devices_form.c_name_empty_text,
                    selectOnFocus: true,
                    anchor: '95%',
                    listeners: {
		                select: function(combo, record, index){
		                	
		                }
            		}
                }, {
					xtype: 'combo',
                    fieldLabel:  this.ownerModule.locale.devices_grid.user_cs,
                    hiddenName: 'user_cs_id',
                    id:'fd_user_cs_id',
                    editable:false,
                    store: new EzDesk.Billing.userCSStore({
                        desktop: this.ownerModule.app.getDesktop(),
                        moduleId: this.ownerModule.id,
                        connection: this.ownerModule.app.connection,
						filtter: 'utone',
						status: ''
						, domain: oem_domain
						, resaler :os_resaler
                    }),
                    disable: true,
                    valueField: 'user_cs_id',
                    displayField: 'user_cs_name',
                    typeAhead: true,
                    mode: 'remote',
                    triggerAction: 'all',
                    emptyText: this.ownerModule.locale.devices_form.c_name_empty_text,
                    selectOnFocus: true,
                    anchor: '95%'
                }]
            })
            , new Ext.ux.Wiz.Card({
                title: this.ownerModule.locale.devices_form.billing_info_title,
                id: 'billing_info',
                monitorValid: true,
                defaults: {
                    labelStyle: 'font-size:12px'
                },
                items: [{
                    border: false,
                    bodyStyle: 'background:none;padding-bottom:10px;padding-top:10px;',
                    html: this.ownerModule.locale.devices_form.t_page
                }, {
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.balance,
                    name: 'balance',
                    anchor: '95%',
                    value: '0'
                }, {
                    xtype: 'BCurrencyTypeCombo',
                    name: 'currency_type',
                    anchor: '95%'
                }, {
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.valid_date,
                    name: 'valid_date',
                    anchor: '95%'
                }, {
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.free_period,
                    name: 'free_period',
                    anchor: '95%'
                }, {
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.hire_number,
                    name: 'hire_number',
                    anchor: '95%'
                }]
            })
            ]
        });
        EzDesk.deviceWizForm.superclass.constructor.call(this, config);
        this.on({
            'finish': { 
                fn: function(wiz, data){
        			var m = Ext.getCmp('ez-carriers_devices');
	        		if(m){
	        			m.send_request({
	                		waitMsg: 'Importing...'
	                		,params: {
	                            method: "import_devices",
	                            moduleId: this.ownerModule.id,
	                            data: Ext.util.JSON.encode(data)
	                            , domain: oem_domain
	    						, resaler :os_resaler
	                        }
	        				,success:function(s,x){
	        					Ext.getCmp('devices_grid_panel').store.reload();
	        				}
	                	});
	        		}
                },
                scope: this
            }
        });
    }
    
});

EzDesk.deviceEditForm = Ext.extend(Ext.form.FormPanel, {
    labelWidth: 100,
    labelAlign: 'top',
    id: 'deviceEditForm',
    layout: 'form',
    padding: 0,
    frame: true,
    border:false,
    bindData: function(record){
		this.form.setValues(record);
	}
    ,initComponent: function(){
        this.items = [{
            layout: 'column',
            items: [/*{
            		xtype:'compositefield'
            		,items:[{
            			xtype:'textfield'
        				,fieldLabel: EzDesk.carriers.Locale.devices_grid.bsn
                        ,name: 'bsn'
            		},{
            			xtype:'textfield'
            			,fieldLabel: EzDesk.carriers.Locale.devices_grid.imei
                        ,name: 'imei'
            		}]
            	},*/{
                columnWidth: .5,
                layout: 'form',
                items: [{
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.bsn,
                    name: 'bsn',
                    anchor: '95%',
                    value: this.record.bsn
                    ,disabled: true
                },{
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.bind_epno,
                    name: 'bind_epno',
                    anchor: '95%',
                    value: this.record.bind_epno
                    ,disabled: true
                }]
            }, {
                columnWidth: .5,
                layout: 'form',
                items: [  {
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.imei,
                    name: 'imei',
                    anchor: '95%',
                    value: this.record.imei,
                    disabled: true
                },{
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.bind_pno,
                    name: 'bind_pno',
                    anchor: '95%',
                    value: this.record.bind_pno,
                    disabled: true
                }]
            }, {
                columnWidth: .5,
                layout: 'form',
                items: [{
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.is_time,
                    name: 'is_time',
                    anchor: '95%',
                    value: this.record.is_time,
                    disabled: true
                }, {
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.charge_plan,
                    name: 'charge_plan',
                    anchor: '95%',
                    value: this.record.charge_plan,
                    disabled: true
                }]
            }, {
                columnWidth: .5,
                layout: 'form',
                items: [{
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.os_time,
                    name: 'os_time',
                    anchor: '95%',
                    value: this.record.os_time,
                    disabled: true
                }, {
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.active_time,
                    name: 'active_time',
                    anchor: '95%',
                    value: this.record.active_time,
                    disabled: true
                }]
            }, {
                columnWidth: .5,
                layout: 'form',
                items: [{
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.va_time,
                    name: 'va_time',
                    anchor: '95%',
                    value: this.record.va_time
                }, {
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.carrier_id,
                    name: 'carrier_id',
                    anchor: '95%',
                    value: this.record.carrier_id
                }]
            }, {
                columnWidth: .5,
                layout: 'form',
                items: [{
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.vc_time,
                    name: 'vc_time',
                    anchor: '95%',
                    value: this.record.vc_time
                }, {
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.resaler,
                    name: 'resaler',
                    anchor: '95%',
                    value: this.record.resaler
                }]
            }, {
                columnWidth: .5,
                layout: 'form',
                items: [{
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.VID,
                    name: 'vid',
                    anchor: '95%',
                    value: this.record.vid
                }]
            }, {
                columnWidth: .5,
                layout: 'form',
                items: [{
                    xtype: 'textfield',
                    fieldLabel: EzDesk.carriers.Locale.devices_grid.PID,
                    name: 'pid',
                    anchor: '95%',
                    value: this.record.pid
                }]
            }]
        }, {
            xtype: 'textarea',
            id: 'remark',
            name: 'remark',
            fieldLabel: EzDesk.carriers.Locale.devices_grid.remark,
            height: 50,
            anchor: '98%',
            value: this.record.remark
        }];
        
        EzDesk.deviceEditForm.superclass.initComponent.call(this);
    }
});


EzDesk.devicesGrid = Ext.extend(Ext.grid.GridPanel, {
    columnLines: true,
    region: 'center',
    initComponent: function(){
        // Create RowActions Plugin
        this.action = new EzDesk.RowActions({
            //header:EzDesk.endpoints.Locale.Actions
            align: 'center',
            keepSelection: true,
            actions: [{
                iconCls: 'icon-wrench' 
                ,text: this.locale.field.edit
            }]
        });
        // dummy action event handler - just outputs some arguments to console
        this.action.on({
            action: function(grid, record, action, row, col){
                new EzDesk.devicesEditGrid(grid.app, grid.moduleId, record.data);
            }
            
        });// eo privilege actions
        var sm = new Ext.grid.CheckboxSelectionModel();
        // configure the grid		
        Ext.apply(this, {
            store: new EzDesk.Devices.ListStore({
            	desktop: this.ownerModule.app.getDesktop()
            	,moduleId:this.ownerModule.id
            	,connection: this.ownerModule.app.connection
            	,locate:this.ownerModule.locate
            	, domain: oem_domain
				, resaler :os_resaler
            }),
            autoExpandColumn: 'remark',
            columnLines: true,
            multiSelect: false,
            singleSelect: true,
            columns:[new Ext.grid.RowNumberer(),sm,{
                id: 'bsn',
                header: this.locale.devices_grid.bsn,
                width: 120,
                align: 'Left',
                resizable: true,
                dataIndex: 'bsn'
            }, {
                id: 'imei',
                header: this.locale.devices_grid.imei,
                width: 120,
                align: 'Left',
                resizable: true,
                dataIndex: 'imei'
            }, {
                id: 'bind_pno',
                header: this.locale.devices_grid.bind_pno,
                width: 120,
                align: 'Left',
                resizable: true,
                dataIndex: 'bind_pno'
            }, {
                id: 'bind_epno',
                header: this.locale.devices_grid.bind_epno,
                width: 120,
                align: 'Left',
                resizable: true,
                dataIndex: 'bind_epno'
            }, {
                id: 'carrier_id',
                header: this.locale.devices_grid.carrier_id,
                width: 80,
                align: 'Left',
                resizable: true,
                dataIndex: 'carrier_id'
            }, {
                id: 'active_time',
                header: this.locale.devices_grid.active_time,
                width: 120,
                align: 'Left',
                resizable: true,
                dataIndex: 'active_time'
            }],
            plugins: [this.action],
            loadMask: true,
            viewConfig: {
                forceFit: true
            }
        }); // eo apply
        // add paging toolbar
        this.bbar = new Ext.PagingToolbar({
            store: this.store,
            displayInfo: true,
            pageSize: 20
        });
        
        // call parent
        EzDesk.devicesGrid.superclass.initComponent.apply(this, arguments);
    } // eo function initComponent
    ,
    onRender: function(){
        // call parent
        EzDesk.devicesGrid.superclass.onRender.apply(this, arguments);
        // load the store
        this.store.load({
            params: {
                start: 0,
                limit: 20
            }
        });
    } // eo function onRender
}); // eo extend grid
Ext.reg('devices_grid_panel', EzDesk.devicesGrid);


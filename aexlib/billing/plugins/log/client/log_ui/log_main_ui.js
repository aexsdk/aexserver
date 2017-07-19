Ext.namespace('EzDesk.log');

EzDesk.log.Grid = Ext.extend(Ext.grid.GridPanel, {
    columnLines: true,
    region: 'center',
    initComponent: function(){
        // Create RowActions Plugin
        this.action = new EzDesk.RowActions({
            header: EzDesk.log.Locale.View,
            align: 'center',
            keepSelection: true,
            actions: [{
                iconCls: 'icon-wrench' 
                //,qtipIndex: 'p_qtip'
                //,iconIndex: 'p_icon'
                //,hideIndex: 'p_hide'
                ,
                text: EzDesk.log.Locale.View
            }]
        });
        this.action.on({
            action: function(grid, record, action, row, col){
                var data = record.data;
                new EzDesk.viewInfoDialog(grid,grid.app, grid.moduleId, record.data);
            }
            
        });//eo privilege  actions
        // configure the grid
        Ext.apply(this, {
            //autoWidth: true
            //height: 344
            store: new EzDesk.Billing.ListLogStore({
            	desktop: this.ownerModule.app.getDesktop()
            	,moduleId:this.ownerModule.id
            	,connection: this.ownerModule.app.connection
            }),
            columns: [{
                id: 'LogTime',
                header: EzDesk.log.Locale.LogTime,
                width: 180,
                resizable: true,
                dataIndex: 'LogTime'
            }, {
                id: 'ModSrcIP',
                header: EzDesk.log.Locale.ModSrcIP,
                width: 180,
                resizable: true,
                dataIndex: 'ModSrcIP'
            }, {
                id: 'ModDest',
                header: EzDesk.log.Locale.ModDest,
                width: 160,
                resizable: true,
                dataIndex: 'ModDest'
            }, {
                id: 'Action',
                header: EzDesk.log.Locale.Action,
                width: 180,
                resizable: true,
                dataIndex: 'Action'
            }, {
                id: 'ReturnValue',
                header: EzDesk.log.Locale.ReturnValue,
                width: 60,
                align: 'right',
                resizable: true,
                dataIndex: 'ReturnValue'
            }, this.action],
            plugins: [this.action],
            loadMask: true,
            viewConfig: {
                forceFit: true
            }
        }); // eo apply
        // add paging toolbar
        this.bbar = {
            xtype: 'paging',
            store: this.store,
            displayInfo: true,
            pageSize: 20
            //,plugins: new Ext.ux.ProgressBarPager()
        };
        
        // call parent
        EzDesk.log.Grid.superclass.initComponent.apply(this, arguments);
    } // eo function initComponent
    ,
    onRender: function(){
        // call parent
        EzDesk.log.Grid.superclass.onRender.apply(this, arguments);
        // load the store
        this.store.load({
            params: {
                start: 0,
                limit: 20
            }
        });
        
    } // eo function onRender
}); // eo extend grid
Ext.reg('log-grid-panel', EzDesk.log.Grid);


/*
 wirter:  lion wang
 caption: view  detail info for log
 version: 1.0
 time: 2010-04-19
 last time: 2010-04-19
 */
EzDesk.viewInfoDialog = function(grid,app, moduleId, record){
    var desktop = app.getDesktop();
    var winManager = desktop.getManager();
    this.ownerModule = grid.ownerModule;
    EzDesk.viewInfoForm = Ext.extend(Ext.form.FormPanel, {
        labelWidth: 80,
        labelAlign: 'right',
        border: false,
        id: 'viewInfoForm',
        layout: 'border',
        padding: 2,
        frame: false,
        initComponent: function(){
            this.items = [{
                layout: 'column',
                border: false,
                region:'north',
                autoHeight:true,
                items: [{
                    columnWidth: .5,
                    layout: 'form',
                    border: false,
                    items: [{
                        xtype: 'displayfield',
                        fieldLabel: EzDesk.log.Locale.LogTime,
                        name: 'LogTime',
                        anchor: '95%',
						value: record.LogTime
                    }, {
                        xtype: 'displayfield',
                        fieldLabel: EzDesk.log.Locale.ReturnValue,
                        name: 'ReturnValue',
                        anchor: '95%',
						value: record.ReturnValue
                    }]
                }, {
                    columnWidth: .5,
                    layout: 'form',
                    border: false,
                    items: [{
                        xtype: 'displayfield',
                        fieldLabel: EzDesk.log.Locale.ModDest,
                        name: 'ModDest',
                        anchor: '95%',
						value: record.ModDest
                    }, {
                        xtype: 'displayfield',
                        fieldLabel: EzDesk.log.Locale.Action,
                        name: 'Action',
                       	value: record.Action,
                        anchor: '95%'
                    }]
                }]
			}, {
                xtype: 'tabpanel',
                id:'log_tabpanel',
                region:'center',
                plain: true,
                activeTab: 0,
                height: 260,
                deferredRender: false,
                defaults: {
                    bodyStyle: 'padding:1px'
                },
                anchor: '100%',
                items: [{
                    title: EzDesk.log.Locale.Param,
                    layout: 'fit',
                    defaultType: 'textarea',
                    items: [{
                        fieldLabel: EzDesk.log.Locale.Param,
                        name: 'Param',
                        value: record.Param,
                        readOnly:true,
                        anchor: '100%'
                    }]
                }, {
                    title: EzDesk.log.Locale.Requests,
                    layout: 'fit',
                    defaultType: 'textarea',
                    items: [{
                        fieldLabel: EzDesk.log.Locale.Requests,
                        name: 'Requests',
                        readOnly:true,
                        value: record.Requests,
                        anchor: '100%'
                    }]
                }, {
                    title: EzDesk.log.Locale.Response,
                    layout: 'fit',
                    defaultType: 'textarea',
                    items: {
                        fieldLabel: EzDesk.log.Locale.Response,
                        name: 'Response',
                        readOnly:true,
                        style:'word-wrap : normal;',
                        value: record.Response,
                        anchor: '100%'
                    }
				}]
            }];
            
            this.buttons = [{
                text: EzDesk.log.Locale.Close,
                width: 90,
                hander: function(){
                    this.ownerModule.onCancel();
                },
                scope:this
            }];
            EzDesk.viewInfoForm.superclass.initComponent.call(this);
            /*var tabp = Ext.getCmp('log_tabpanel');
            if(tabp) {
            		tabp.hideTabStripItem(0);
            		tabp.hideTabStripItem(1);
            }*/
            
        }
    });
    
    /*var close = function(){
        this.dialog.hide();
    }*/
    
    if (!this.dialog) {
        this.dialog = new Ext.Window({
            constrainHeader: true,
            footer: true,
            shim: false,
            animCollapse: false,
            title: EzDesk.log.Locale.launcherText,
            bodyStyle: 'padding:0px',
            layout: 'fit',
            width: 480,
            height: 500,
            closeAction: 'hide',
            plain: true,
            border: false,
            items: [new EzDesk.viewInfoForm()],
            manager: winManager,
			maximized: false,
            modal: true
        });
    }
    this.dialog.show();
};



EzDesk.log.mainUi = Ext.extend(Ext.Panel, {
    header: false,
    layout: 'border',
    region: 'center',
    initComponent: function(){
        //this.actions = new EzDesk.log.Actions(this.app,this.connect);
        this.tbar = {
            xtype: 'toolbar',
            items: [{
                xtype: 'buttongroup',
                columns: 2,
                id: 'btg_log_type',
                items: [{
                    xtype: 'button',
                    text: EzDesk.log.Locale.SystmeEvent,
                    id: 'fd_runtime',
                    allowDepress: true,
                    enableToggle: true,
                    toggleGroup: 'type',
                    pressed: true,
                    clickEvent: 'click'
                }, {
                    xtype: 'button',
                    text: EzDesk.log.Locale.ActionEvent,
                    id: 'fd_history',
                    allowDepress: true,
                    enableToggle: true,
                    toggleGroup: 'type',
                    pressed: false,
                    clickEvent: 'click'
                }]
            }, {
                xtype: 'tbseparator'
            }, {
                xtype: 'tbtext',
                text: EzDesk.log.Locale.From
            }, {
                xtype: 'datefield',
                fieldLabel: 'Label',
                width: 150,
                id: 'fd_from',
                format: 'Y-m-d H:i:s',
                value: new Date((new Date()).getFullYear(), (new Date()).getMonth(), (new Date()).getDate())
            }, {
                xtype: 'tbtext',
                text: EzDesk.log.Locale.To
            }, {
                xtype: 'datefield',
                fieldLabel: 'Label',
                width: 150,
                id: 'fd_to',
                format: 'Y-m-d H:i:s',
                value: new Date((new Date()).getFullYear(), (new Date()).getMonth(), (new Date()).getDate() + 1)
            }, {
                xtype: 'tbfill'
            }, {
                xtype: 'tbtext',
                text: 'Filter'
            }, {
                xtype: 'textfield',
                fieldLabel: '',
                width: 100,
                id: 'fd_endpoint_filter'
            }, {
                xtype: 'button',
                text: EzDesk.log.Locale.Query,
                handler: function(){
                    var fdr_from = Ext.getCmp('fd_from');
                    var fdr_to = Ext.getCmp('fd_to');
                    var fdr_filter = Ext.get('fd_endpoint_filter');
                    var fdr_rt = Ext.getCmp('fd_runtime');
                    
                    var from = fdr_from ? fdr_from.getValue() : '';
                    var to = fdr_to ? fdr_to.getValue() : '';
                    var filter = fdr_filter ? fdr_filter.getValue() : '';
                    var type = fdr_rt ? (fdr_rt.pressed ? 0 : 1) : 0;
                    
                    if (fdr_from && (!Ext.isDate(from))) {
                        pfrom = new Date();
                        //alert(pfrom.format('Y-m-d H:i:s'));
                        from = new Date(pfrom.getFullYear(), pfrom.getMonth(), 0);
                        alert(from.format('Y-m-d H:i:s'));
                        fdr_from.setValue(from);
                    }
                    if (fdr_to && (!Ext.isDate(to))) {
                        pto = new Date();
                        //alert(pfrom.format('Y-m-d H:i:s'));
                        to = new Date(pto.getFullYear(), pto.getMonth() + 1, 0);
                        //alert(to.format('Y-m-d H:i:s'));
                        fdr_to.setValue(from);
                    }
                    var grid = Ext.getCmp('log_grid_pannel_obj');
                    if (grid) {
                        //alert(grid.xtype);
                        grid.store.removeAll();
                        grid.store.setBaseParam('from', from.format('Y-m-d H:i:s'));
                        grid.store.setBaseParam('to', to.format('Y-m-d H:i:s'));
                        grid.store.setBaseParam('filter', filter.toString());
                        grid.store.setBaseParam('type', type.toString());
                        grid.store.load({
                            params: {
                                start: 0,
                                limit: 20
                            },
                            callback: function(r, options, success){
                                if (!success) {
                                    var notifyWin = this.desktop.showNotification({
                                        html: this.store.reader.jsonData.message.toString(),
                                        title: lang_tr.Error
                                    });
                                }
                            },
                            scope: grid
                        });
                    }
                },
                scope: this
            }]
        };
        this.items = [{
            xtype: 'log-grid-panel',
            ownerModule:this.ownerModule,
            region: 'center',
            id: 'log_grid_pannel_obj',
            name: 'log_grid_pannel_obj',
            app: this.app,
            connect: this.connect,
            desktop: this.desktop,
            moduleId: this.moduleId
        }];
        EzDesk.log.mainUi.superclass.initComponent.call(this);
    }
});

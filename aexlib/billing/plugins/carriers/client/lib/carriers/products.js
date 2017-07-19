/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */
EzDesk.carriers.products = function(ownerModule){
    this.addEvents({
        'product_edited': true
    });
    
    this.ownerModule = ownerModule;
    EzDesk.carriers.products.superclass.constructor.call(this, {
        border: false,
        title: this.ownerModule.locale.Products,
        closable: true,
        iconCls: 'm-carriers-icon',
        id: 'ez-carriers_products',
        items: [{
            xtype: 'products_grid_panel',
            id: 'products_grid_panel',
            name: 'products_grid_panel',
            app: this.app,
            connect: this.ownerModule.app.connection,
            moduleId: this.ownerModule.id,
            locale: this.ownerModule.locale
        }],
        layout: 'border',
        tbar: [{
            disabled: this.ownerModule.app.isAllowedTo('get_products', this.ownerModule.id) ? false : true,
            handler: this.onRefreshClick,
            iconCls: 'qo-admin-refresh',
            scope: this,
            tooltip: this.ownerModule.locale.field.refresh
        
        }, '-', {
            disabled: this.ownerModule.app.isAllowedTo('add_products', this.ownerModule.id) ? false : true,
            handler: this.onAddClick            //, iconCls: 'qo-admin-add'
            ,
            scope: this,
            text: this.ownerModule.locale.field.add,
            tooltip: this.ownerModule.locale.field.add_new_group
        }, {
            disabled: this.ownerModule.app.isAllowedTo('edit_products', this.ownerModule.id) ? false : true,
            handler: this.onEditClick            //, iconCls: 'qo-admin-edit'
            ,
            scope: this,
            text: this.ownerModule.locale.field.edit,
            tooltip: 'Edit selected'
        }, {
            disabled: this.ownerModule.app.isAllowedTo('delete_products', this.ownerModule.id) ? false : true,
            handler: this.onDeleteClick            //, iconCls: 'qo-admin-delete'
            ,
            scope: this,
            text: this.ownerModule.locale.field.del,
            tooltip: this.ownerModule.locale.field.delete_selected
        }]
    });
    this.grid = Ext.getCmp('products_grid_panel');
};

Ext.extend(EzDesk.carriers.products,  EzDesk.BillingPanel, {
    onActiveToggled: function(record){
        this.fireEvent('product_edited', record);
    },
    onAddClick: function(){
		//this.createForm('450','500',this.ownerModule.locale.ProductsTile);
    },
    onDeleteClick: function(){
        var sm = this.grid.getSelectionModel(), count = sm.getCount();
        
        if (count > 0) {
            Ext.MessageBox.confirm('Confirm', 'Are you sure you want to delete the selected product(s)?', function(btn){
                if (btn === "yes") {
                    var selected = sm.getSelections();
                    var encodedId = selected[0].id;
                	this.send_request({
                		waitMsg: 'Delete...'
                		,params: {
                            method: "delete_products",
                            moduleId: this.ownerModule.id,
                            id: encodedId
                        }
                	});
                	this.onRefreshClick();
                }
            },this);
        }
    },
    onEditClick: function(){
		var grid = this.grid;
        if (grid.selModel.hasSelection()) {
            var records = grid.selModel.getSelections();//得到被选择的行的数组
            var recordsLen = records.length;//得到行数组的长度
            if (recordsLen > 1) {
                Ext.Msg.alert("系统提示信息", "请选择其中一项进行编辑！");
            }//一次只给编辑一行
            else {
                var record = grid.getSelectionModel().getSelected();//获取选择的记录集
                var id = record.get("id");
                this.createForm('400','500','edit');
                fp.form.loadRecord(record); //往表单（fp.form）加载数据
            }
        }
        else {
            Ext.Msg.alert("提示", "请先选择要编辑的行!");
        }
    },
    grid_load_callback:function(){
    	//this.hideMask(this);
    },
    onRefreshClick: function(){
        var grid = this.grid;
        
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
	,createForm: function(width, height, title){
    	var v_plan_value = 'agent_id=,agent_cs=,user_cs=,balance=0,currency_type=CNY,valid_date_no=24,free_period=0,hire_number=0';
    	var v_parameters = 'OPTIONS=1843,oem-name=,service-num=';
		var fp = new Ext.FormPanel({
			layout: 'form'
			,bodyStyle : 'padding:15px;'
			,labelAlign:'left'
			,items: [{
                xtype: "hidden",
                name: "moduleId",
                value: this.ownerModule.id
            },{
                xtype: "hidden",
                name: "method",
                value: "add_products"
            },{
		      xtype: 'textfield',
		      fieldLabel: this.ownerModule.locale.products_form.VID,
		      labelSeparator: '',
		      name: 'VID',
		      id:'VID',
		      anchor:'100%'
			},{
		      xtype: 'textfield',
		      fieldLabel: this.ownerModule.locale.products_form.PID,
		      labelSeparator: '',
		      name: 'PID',
		      id:'PID',
		      anchor:'100%'
			},{
		      xtype: 'textfield',
		      fieldLabel: this.ownerModule.locale.products_form.name,
		      labelSeparator: '',
		      name: 'name',
		      id:'name',
		      anchor:'100%'
			},{
                xtype: 'combo',
				name: 'p_c_name',
				id: 'p_c_name',
                fieldLabel: this.ownerModule.locale.field.Name,
                hiddenName: 'carrier_id',
                store: new EzDesk.Carriers.nameStore({
                    desktop: this.ownerModule.app.getDesktop(),
                    moduleId: this.ownerModule.id, 
                    connection: this.ownerModule.app.connection
                }),
                valueField: 'carrier_id',
                displayField: 'carrier_name',
                typeAhead: true,
                mode: 'local',
                triggerAction: 'all',
                emptyText: this.ownerModule.locale.devices_form.c_name_empty_text,
                selectOnFocus: true,
                anchor: '100%', 
				listeners: {
	                select: function(combo, record, index){
	        			v_carrier_id = combo.value;
	                }
	            }
            },{
		      xtype: 'textarea',
		      fieldLabel: this.ownerModule.locale.products_form.charge_plan,
		      labelSeparator: '',
		      name: 'charge_plan',
		      id:'charge_plan',
		      anchor:'100%',
		      height: '120',
		      value: v_plan_value
			},{
		      xtype: 'textarea',
		      fieldLabel: this.ownerModule.locale.products_form.parameters,
		      labelSeparator: '',
		      name: 'parameters',
		      id:'parameters',
		      height: '90',
		      anchor:'100%',
		      value: v_parameters
			},{
		      xtype: 'textfield',
		      fieldLabel: this.ownerModule.locale.products_form.description,
		      labelSeparator: '',
		      name: 'description',
		      id:'description',
		      anchor:'100%'
			}]
		});
		
		var winManager = this.ownerModule.app.desktop.getManager();
    	var win = Ext.Window({
            width: width,
            height: height,
            title: title,
			iconCls : 'm-carriers-icon',
			items : [this.tabPanel],
			layout : 'fit',
			shadow: true,
			manager: winManager,
            modal: true,
            items: [fp],
            buttons: [{
                text: this.ownerModule.locale.Save,
                handler: function(desktop){
	            	 fp.form.submit({
	                	 waitMsg: '正在保存... ...',
	                     waitTitle: '请稍等...',
	                     url: this.ownerModule.app.connection,
	                     method: 'POST',
	                     success: function(form_instance_create, action){
	                 	 	var obj = Ext.util.JSON.decode(action.response.responseText);
	                      	EzDesk.showMsg('title', obj.message, this.ownerModule.app.desktop);
	        				//this.store.reload();
	                     },
	                     failure: function(form_instance_create, action){
	                     	var obj = Ext.util.JSON.decode(action.response.responseText);
	                      	EzDesk.showMsg('title', obj.message, this.ownerModule.app.desktop);
	                     },
	                     scope: this
	                });
	        	},
                scope: this
            }, {
                text: this.ownerModule.locale.Cancel,
                handler: this.reset,
                scope: this
            }, {
                text: this.ownerModule.locale.Close,
                handler: this.closeWin,
                scope: this
            }]
        });
        return win;
    }
});

EzDesk.productsGrid = Ext.extend(Ext.grid.GridPanel, {
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
                //,tooltip: EzDesk.endpoints.Locale.ViewTooltip
                // ,qtipIndex: 'p_qtip'
                // ,iconIndex: 'p_icon'
                // ,hideIndex: 'p_hide'
                //,text: EzDesk.endpoints.Locale.View
            }]
        });
        // dummy action event handler - just outputs some arguments to console
        this.action.on({
            action: function(grid, record, action, row, col){
                //new EzDesk.endpointViewDialog(grid.app, grid.moduleId, record.data);
            }
            
        });// eo privilege actions
        // configure the grid		
        Ext.apply(this, {
            // autoWidth: true
            store: new Ext.data.GroupingStore({
                reader: new Ext.data.JsonReader({
                    id: 'id',
                    totalProperty: 'totalCount',
                    messageProperty: "message",
                    root: 'data',
                    fields: [{
                        name: 'vid',
                        type: 'string'
                    }, {
                        name: 'pid',
                        type: 'string'
                    }, {
                        name: 'name',
                        type: 'string'
                    }, {
                        name: 'description',
                        type: 'string'
                    }, {
                        name: 'carrier_id',
                        type: 'string'
                    }]
                }),
                proxy: new Ext.data.HttpProxy({
                    url: this.connect,
                    method: 'POST'
                
                }),
                baseParams: {
                    method: 'get_products',
                    moduleId: this.moduleId,
                    query_condition: '',
                    status: ''
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
                id: 'VID',
                header: this.locale.products_grid.VID,
                width: 120,
                align: 'Left',
                resizable: true,
                dataIndex: 'vid'
            }, {
                id: 'PID',
                header: this.locale.products_grid.PID,
                width: 120,
                align: 'Left',
                resizable: true,
                dataIndex: 'pid'
            }, {
                id: 'name',
                header: this.locale.products_grid.name,
                width: 120,
                align: 'Left',
                resizable: true,
                dataIndex: 'name'
            }, {
                id: 'description',
                header: this.locale.products_grid.description,
                width: 120,
                align: 'Left',
                resizable: true,
                dataIndex: 'description'
            }, {
                id: 'carrier_id',
                header: this.locale.products_grid.carrier_id,
                width: 150,
                align: 'Right',
                resizable: true,
                dataIndex: 'carrier_id'
            }
            ],
            plugins: [this.action]
            ,loadMask: true
        }); // eo apply
        this.bbar = new Ext.PagingToolbar({
            store: this.store,
            displayInfo: true,
            pageSize: 20
        });
        EzDesk.productsGrid.superclass.initComponent.apply(this, arguments);
    }
    , onRender: function(){
        // call parent
        EzDesk.productsGrid.superclass.onRender.apply(this, arguments);
        // load the store
        this.store.load({
            params: {
                start: 0,
                limit: 20
            }
        });
        
    } 
}); 
Ext.reg('products_grid_panel', EzDesk.productsGrid);

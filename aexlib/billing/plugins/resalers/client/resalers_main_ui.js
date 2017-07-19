Ext.namespace('EzDesk');
EzDesk.resalers_main_ui = Ext.extend(Ext.Panel, {
    region: 'center',
    app: null,
    desktop: null,
    connect: '',
    moduleId: '',
    layout: 'border',
    initComponent: function(){
        var r_app = this.app;
        var r_moduleId = this.moduleId;
        this.tbar = {
            xtype: 'toolbar',
            region: 'north',
            items: [{
                xtype: 'button'
                ,text: EzDesk.resalers.Locale.resaler_info
                ,itemId: 'btn_resalers'
				,handler: function(){
					new EzDesk.rasaler_info_panel(r_app, r_moduleId);
				}
            },'-'
			,{
                xtype: 'button'
                ,text: EzDesk.resalers.Locale.ResalerFinancial 
				,itemId: 'btn_financial '
				,menu: {
                    xtype: 'menu',
                    itemId: 'menu_stock',
                    items: [{
                        xtype: 'menuitem'
                        ,text: EzDesk.resalers.Locale.RechargeLog
						,handler: function(){
							var desktop = r_app.getDesktop();
						    var winManager = desktop.getManager();
							
							var colse = function(){
						        this.dialog.hide();
						    };
						    if (!this.dialog) {
						        this.dialog = new Ext.Window({
						            layout: 'border'
						            ,width: 900
						            ,height: 540
						            ,closeAction: 'hide'
						            ,plain: true
						            ,items: [{
										xtype : 'recharge_log_grid'
										,id : 'recharge_log_grid'
										,name: 'recharge_log_grid'
										,app: r_app
										,moduleId: r_moduleId
									}],
						            manager: winManager,
						            modal: true
						        });
						    }
						    this.dialog.show();
						}
                    },
                    {
                        xtype: 'menuitem'
                        ,text: EzDesk.resalers.Locale.BalanceLog
						,handler: function(){
							var desktop = r_app.getDesktop();
						    var winManager = desktop.getManager();
							
							var colse = function(){
						        this.dialog.hide();
						    };
						    if (!this.dialog) {
						        this.dialog = new Ext.Window({
						            layout: 'border',
						            width: 900,
						            height: 540,
						            closeAction: 'hide',
						            plain: true
									,tbar: {
										xtype: 'toolbar',
							            region: 'north',
							            items: [{
							                xtype: 'button'
							                ,text: EzDesk.resalers.Locale.BalanceLog
							                ,itemId: 'btn_resalers'
											,handler: function(){
												alert('ok');
											}
							            }]
									},
						            items: [{
										xtype : 'balance_log_grid',
										id : 'balance_log_grid',
										name: 'balance_log_grid',
										app: r_app,
										moduleId: r_moduleId
									}],
						            manager: winManager,
						            modal: true
						        });
						    }
						    this.dialog.show();
						}
                    }]
                }
            }]
        };
        this.items = [{
            xtype: 'panel'
			,region: 'center'
			,layout: 'border'
			,tbar: {
				xtype: 'toolbar',
	            region: 'north',
	            items: [{
					xtype: 'tbspacer'
					,width: '5'
				},{
	                xtype: 'label'
	                ,text: EzDesk.resalers.Locale.QueryCondition
	                ,name: 'QueryCondition'
                    ,width:190
	            },{
	                xtype: 'textfield'
	                ,name: 'QueryCondition'
                    ,width:190
//					,handler: function(){
//						new EzDesk.rasaler_info_panel(r_app, r_moduleId);
//					}
	            }, '-'
				,{
					xtype: 'tbspacer'
					,width: '25'
				},{
	                xtype: 'label'
	                ,text: EzDesk.resalers.Locale.StartTime
	                ,name: 'QueryCondition'
                    ,width:100
	            },{
	                xtype: 'datefield'
	                ,name: 'StartTime'
                    ,width:190
                    ,allowBlank:false
	            },{
					xtype: 'tbspacer'
					,width: '10'
				},{
	                xtype: 'label'
	                ,text: EzDesk.resalers.Locale.EndTime
	                ,name: 'QueryCondition'
                    ,width: 100
	            },{
					xtype: 'datefield'
					,name: 'EndTime'
                    ,width:190
                    ,allowBlank:false
				},{
					xtype: 'tbfill'
				},{
					xtype: 'button'
	                ,text: EzDesk.resalers.Locale.Query
					,name: 'Query'
                    ,width: 120
				}]
			}
			,items: [{
				xtype : 'rasaler_products_grid',
				id : 'rasaler_products_grid',
				name: 'rasaler_products_grid',
				app: r_app,
				moduleId: r_moduleId
			}]
        }];
        EzDesk.resalers_main_ui.superclass.initComponent.call(this);
    }
});


Ext.reg('resalers_main_ui', EzDesk.resalers_main_ui);



//经销商信息页面
EzDesk.rasaler_info_panel = function(app, moduleId){
	var r_app = this.app;
	var r_moduleId = this.moduleId;
	var desktop = app.getDesktop();
    var winManager = desktop.getManager();
	
	EzDesk.rasaler_info_panel = Ext.extend(Ext.Panel, {
		/**
		 * Read only.
		 * @type {String}
		 */
		app: null		/**
		 * Read only.
		 * @type {String}
		 */
		,desktop: null		/**
		 * Read only.
		 * @type {String}
		 */
		,connect: null		/**
		 * Read only.
		 * @type {String}
		 */
		,moduleId: null
		,layout: 'border'		//初始化模块
		,initComponent: function(){
			//定义tbar
			this.tbar = [{
				xtype: 'toolbar',
				region: 'north',
				items: [{
					xtype: 'button'
					,text: EzDesk.resalers.Locale.Add
					,itemId: 'btn_add'
					,iconCls: 'addIconCss'
					,handler: function(){
						alert('ok');
					}
				}, {
					xtype: 'button'
					,text: EzDesk.resalers.Locale.Edit
					,itemId: 'btn_edit'
					,iconCls: 'editIconCss'
					,handler: function(){
						alert('ok');
					}
				}, {
					xtype: 'button'
					,text: EzDesk.resalers.Locale.Del
					,itemId: 'btn_del'
					,iconCls: 'deleteIconCss'
					,handler: function(){
						alert('ok');
					}
				}, {
					xtype: 'button'
					,text: EzDesk.resalers.Locale.Refresh
					,itemId: 'btn_refresh'
					,iconCls: 'refreshIcon'
					,handler: function(){
						alert('ok');
					}
				}]
			}];
			this.items = [{
				xtype: 'panel'
				,region: 'center'
				,layout: 'border'
				,items: [{
					xtype : 'rasaler_info_grid',
					id : 'rasaler_info_grid',
					name: 'rasaler_info_grid',
					app: r_app,
					moduleId: r_moduleId
				}]
			}];
			EzDesk.rasaler_info_panel.superclass.initComponent.call(this);
		}
	});
	
	 var colse = function(){
        this.dialog.hide();
    };
    
    if (!this.dialog) {
        this.dialog = new Ext.Window({
            layout: 'fit',
            width: 900,
            height: 540,
            closeAction: 'hide',
            plain: true,
            items: [new EzDesk.rasaler_info_panel()],
            manager: winManager,
            modal: true
        });
    }
    this.dialog.show();
}

//经销商的列表
EzDesk.rasaler_info_grid = Ext.extend(Ext.grid.GridPanel, {
    columnLines: true
    ,header : false
	,region : 'center'
	,title: 'Agent Grid'
	,initComponent:function() {	
		// Create RowActions Plugin
		this.action = new EzDesk.RowActions({
			 header:'Actions'
			,align: 'center'
			,keepSelection:true
			,actions: [{
                iconCls: 'icon-wrench',
                tooltip: 'View',
                qtipIndex: 'p_qtip',
                iconIndex: 'p_icon',
                hideIndex: 'p_hide'
                //	,text: '查看'
            },{
                iconCls: 'icon-wrench',
                tooltip: 'Edit',
                qtipIndex: 'p_qtip2',
                iconIndex: 'p_icon2',
                hideIndex: 'p_hide2'
                //,text:'编辑'
            }]
		});
		// dummy action event handler - just outputs some arguments to console
		this.action.on({
			action:function(grid, record, action, row, col) {
				var data = record.data;
				switch(action) 
				{
//					case 'icon-form-view':
//					new EzDesk.addANIDialog(grid.app, grid.moduleId, grid);
//					break;
					case 'icon-edit-record':
					new EzDesk.editAgentDialog(grid.app, grid.moduleId, grid, data);
					break;
				}
			}
			
		});//eo privilege  actions
		// configure the grid
		Ext.apply(this, {
		//	autoWidth: true
		//	autoHeight: true,
			//width: '700'
			//,height: '500'
			store : new Ext.data.GroupingStore({
				reader : new Ext.data.JsonReader({
					 idProperty: 'AgentID'
					,totalProperty: 'totalCount'
					,root: 'data'
					,messageProperty:'message'
					,successProperty: 'success'
					,fields:[
						{name: 'ResalerName', type: 'string'}
						,{name: 'AgentID', type: 'string'}
						,{name: 'PID', type: 'string'}
						,{name: 'VID', type: 'string'}
						,{name: 'ProductTypeID', type: 'string'}
						,{name: 'CallCS', type: 'string'}
						,{name: 'AgentCS', type: 'string'}
						,{name: 'Balance', type: 'string'}
						,{name: 'CurrencyType', type: 'string'}
						,{name: 'ValidDateNo', type: 'string'}
						,{name: 'FreePeriod', type: 'string'}
						,{name: 'HireNumber', type: 'string'}
						,{name: 'Bonus',  type: 'string'}
						,{name: 'p_qtip', type: 'string'}
						,{name: 'p_icon', type: 'string'}
						,{name: 'p_hide', type: 'boolean'}
						,{name: 'p_qtip2',type: 'string'}
						,{name: 'p_icon2',type: 'string'}
						,{name: 'p_hide2', type: 'boolean'}
					]
				})
				,proxy:new Ext.data.HttpProxy({
					url : this.connect
					,method : 'POST'
					
				})
				,baseParams: {
					action: 'rasaler_info_list',
					moduleId: this.moduleId
				}
				,listeners:{
					load:{scope:this, fn:function() {
						this.getSelectionModel().selectFirstRow();
					}}
				}
			})
			,columns:[
				{id:'ResalerName',header: EzDesk.resalers.Locale.ResalerName, width: 100, align: 'center',resizable: true, dataIndex: 'ResalerName'}
				,{id:'PID', header: EzDesk.resalers.Locale.PID, width: 50, align: 'center',resizable: true, dataIndex: 'PID'}
				,{id:'VID',header: EzDesk.resalers.Locale.VID, width: 50, align:'center',resizable: true,dataIndex:'VID'}
				,{id:'Bonus',header: EzDesk.resalers.Locale.Bonus, width: 60, align: 'center',resizable: true, dataIndex:'Bonus'}
				,{id:'CallCS',header: EzDesk.resalers.Locale.CallCS, width: 100, align: 'center',resizable: true,dataIndex:'CallCS'}
				,{id:'AgentCS',header: EzDesk.resalers.Locale.AgentCS, width: 100, align: 'center',resizable: true,dataIndex:'AgentCS'}
				,{id:'Balance',header: EzDesk.resalers.Locale.Balance, width: 160, align: 'center',resizable: true,dataIndex:'Balance'}
				,{id:'CurrencyType',header: EzDesk.resalers.Locale.CurrencyType, width: 100, align: 'center',resizable: true,dataIndex:'CurrencyType'}
				,{id:'FreePeriod',header: EzDesk.resalers.Locale.FreePeriod, width: 100, align: 'center',resizable: true,dataIndex:'FreePeriod'}
				,{id:'HireNumber',header: EzDesk.resalers.Locale.HireNumber, width: 100, align: 'center',resizable: true,dataIndex:'HireNumber'}
				,this.action
			]
			,plugins:[this.action]
			,loadMask:true
			,viewConfig:{ forceFit:true }
		}); // eo apply

		// add paging toolbar
		this.bbar = new Ext.PagingToolbar({
			 store:this.store
			,displayInfo:true
			,pageSize:20
		});

		// call parent
		EzDesk.rasaler_info_grid.superclass.initComponent.apply(this, arguments);
	} // eo function initComponent
//	,onRender:function() {
//		// call parent
//		EzDesk.Grid.superclass.onRender.apply(this, arguments);
//		// load the store
//		this.store.load({params:{start:0, limit:20}});
//
//	} // eo function onRender
}); // eo extend grid

Ext.reg('rasaler_info_grid', EzDesk.rasaler_info_grid);

//经销商的产品列表
EzDesk.rasaler_products_grid = Ext.extend(Ext.grid.GridPanel, {
    columnLines: true
    ,header : false
	,region : 'center'
	,title: 'Agent Grid'
	,initComponent:function() {	
		// Create RowActions Plugin
		this.action = new EzDesk.RowActions({
			 header:'Actions'
			,align: 'center'
			,keepSelection:true
			,actions: [{
                iconCls: 'icon-wrench',
                tooltip: 'View',
                qtipIndex: 'p_qtip',
                iconIndex: 'p_icon',
                hideIndex: 'p_hide'
                //	,text: '查看'
            },{
                iconCls: 'icon-wrench',
                tooltip: 'Edit',
                qtipIndex: 'p_qtip2',
                iconIndex: 'p_icon2',
                hideIndex: 'p_hide2'
                //,text:'编辑'
            }]
		});
		// dummy action event handler - just outputs some arguments to console
		this.action.on({
			action:function(grid, record, action, row, col) {
				var data = record.data;
				switch(action) 
				{
//					case 'icon-form-view':
//					new EzDesk.addANIDialog(grid.app, grid.moduleId, grid);
//					break;
					case 'icon-edit-record':
					new EzDesk.editAgentDialog(grid.app, grid.moduleId, grid, data);
					break;
				}
			}
			
		});//eo privilege  actions
		// configure the grid
		Ext.apply(this, {
		//	autoWidth: true
		//	autoHeight: true,
			//width: '700'
			//,height: '500'
			store : new Ext.data.GroupingStore({
				reader : new Ext.data.JsonReader({
					 idProperty: 'AgentID'
					,totalProperty: 'totalCount'
					,root: 'data'
					,messageProperty:'message'
					,successProperty: 'success'
					,fields:[				
						{name: 'PID', type: 'string'}
						,{name: 'VID', type: 'string'}
						,{name: 'GUID', type: 'string'}
						,{name: 'Account', type: 'string'}
						,{name: 'Attribute', type: 'string'}
						,{name: 'ProductTypeID', type: 'string'}
						,{name: 'CallCS', type: 'string'}
						,{name: 'AgentCS', type: 'string'}
						,{name: 'Balance', type: 'string'}
						,{name: 'CurrencyType', type: 'string'}
						,{name: 'ValidDate', type: 'string'}
						,{name: 'ActiveTime', type: 'string'}
						,{name: 'p_qtip', type: 'string'}
						,{name: 'p_icon', type: 'string'}
						,{name: 'p_hide', type: 'boolean'}
						,{name: 'p_qtip2',type: 'string'}
						,{name: 'p_icon2',type: 'string'}
						,{name: 'p_hide2', type: 'boolean'}
					]
				})
				,proxy:new Ext.data.HttpProxy({
					url : this.app.connection
					,method : 'POST'
					
				})
				,baseParams: {
					method: 'get_p_list',
					moduleId: this.moduleId
				}
				,listeners:{
					load:{scope:this, fn:function() {
						this.getSelectionModel().selectFirstRow();
					}}
				}
			})
			,columns:[
				{id:'PID', header: EzDesk.resalers.Locale.PID, width: 80, align: 'center',resizable: true, dataIndex: 'PID'}
				,{id:'VID',header: EzDesk.resalers.Locale.VID, width: 80, align:'center',resizable: true,dataIndex:'VID'}
				//,{id:'ProductTypeID',header: EzDesk.resalers.Locale.ProductTypeID, width: 160, align: 'center',resizable: true,dataIndex:'ProductTypeID'}
				,{id:'GUID',header: EzDesk.resalers.Locale.GUID, width: 160, align: 'center',resizable: true, dataIndex:'GUID'}
				,{id:'Account',header: EzDesk.resalers.Locale.Account, width: 100, align: 'center',resizable: true,dataIndex:'Account'}
				,{id:'Attribute',header: EzDesk.resalers.Locale.Attribute, width: 160, align: 'center',resizable: true,dataIndex:'Attribute'}
				,{id:'Balance',header: EzDesk.resalers.Locale.Balance, width: 60, align: 'center',resizable: true,dataIndex:'Balance'}
				,{id:'CurrencyType',header: EzDesk.resalers.Locale.CurrencyType, width: 60, align: 'center',resizable: true,dataIndex:'CurrencyType'}
				,{id:'ActiveTime',header: EzDesk.resalers.Locale.ActiveTime, width: 150, align: 'center',resizable: true,dataIndex:'ActiveTime'}
				,{id:'ValidDate',header: EzDesk.resalers.Locale.ValidDate, width: 150, align: 'center',resizable: true,dataIndex:'ValidDate'}
				,this.action
			]
			,plugins:[this.action]
			,loadMask:true
			,viewConfig:{ forceFit:true }
		}); // eo apply

		// add paging toolbar
		this.bbar = new Ext.PagingToolbar({
			 store:this.store
			,displayInfo:true
			,pageSize:20
		});
		// call parent
		EzDesk.rasaler_products_grid.superclass.initComponent.apply(this, arguments);
	} // eo function initComponent
	,onRender:function() {
		// call parent
		EzDesk.rasaler_products_grid.superclass.onRender.apply(this, arguments);
		// load the store
		this.store.load({params:{start:0, limit:20}});

	} // eo function onRender
}); // eo extend grid

Ext.reg('rasaler_products_grid', EzDesk.rasaler_products_grid);


//经销商充值查询页面
EzDesk.recharge_log_grid = Ext.extend(Ext.grid.GridPanel, {
    columnLines: true
    ,header : false
	,region : 'center'
	,title: 'Agent Grid'
	,initComponent:function() {	
		// Create RowActions Plugin
		this.action = new EzDesk.RowActions({
			 header:'Actions'
			,align: 'center'
			,keepSelection:true
			,actions: [{
                iconCls: 'icon-wrench',
                tooltip: 'View',
                qtipIndex: 'p_qtip',
                iconIndex: 'p_icon',
                hideIndex: 'p_hide'
                //	,text: '查看'
            },{
                iconCls: 'icon-wrench',
                tooltip: 'Edit',
                qtipIndex: 'p_qtip2',
                iconIndex: 'p_icon2',
                hideIndex: 'p_hide2'
                //,text:'编辑'
            }]
		});
		// dummy action event handler - just outputs some arguments to console
		this.action.on({
			action:function(grid, record, action, row, col) {
				var data = record.data;
				switch(action) 
				{
//					case 'icon-form-view':
//					new EzDesk.addANIDialog(grid.app, grid.moduleId, grid);
//					break;
					case 'icon-edit-record':
					new EzDesk.editAgentDialog(grid.app, grid.moduleId, grid, data);
					break;
				}
			}
			
		});//eo privilege  actions
		// configure the grid
		Ext.apply(this, {
		//	autoWidth: true
		//	autoHeight: true,
			//width: '700'
			//,height: '500'
			store : new Ext.data.GroupingStore({
				reader : new Ext.data.JsonReader({
					 idProperty: 'AgentID'
					,totalProperty: 'totalCount'
					,root: 'data'
					,messageProperty:'message'
					,successProperty: 'success'
					,fields:[				
						{name: 'PID', type: 'string'}
						,{name: 'VID', type: 'string'}
						,{name: 'GUID', type: 'string'}
						,{name: 'Account', type: 'string'}
						,{name: 'Attribute', type: 'string'}
						,{name: 'RechargeType', type: 'string'}
						,{name: 'RechargeBalance', type: 'string'}
						,{name: 'CurrencyType', type: 'string'}
						,{name: 'RechargeTime', type: 'string'}
						,{name: 'Commission', type: 'string'}
						,{name: 'p_qtip', type: 'string'}
						,{name: 'p_icon', type: 'string'}
						,{name: 'p_hide', type: 'boolean'}
						,{name: 'p_qtip2',type: 'string'}
						,{name: 'p_icon2',type: 'string'}
						,{name: 'p_hide2', type: 'boolean'}
					]
				})
				,proxy:new Ext.data.HttpProxy({
					url : this.connect
					,method : 'POST'
					
				})
				,baseParams: {
					action: 'rasaler_info_list',
					moduleId: this.moduleId
				}
				,listeners:{
					load:{scope:this, fn:function() {
						this.getSelectionModel().selectFirstRow();
					}}
				}
			})
			,columns:[
				{id:'PID', header: EzDesk.resalers.Locale.PID, width: 50, align: 'center',resizable: true, dataIndex: 'PID'}
				,{id:'VID',header: EzDesk.resalers.Locale.VID, width: 50, align:'center',resizable: true,dataIndex:'VID'}
				,{id:'GUID',header: EzDesk.resalers.Locale.GUID, width: 60, align: 'center',resizable: true, dataIndex:'GUID'}
				,{id:'Account',header: EzDesk.resalers.Locale.Account, width: 100, align: 'center',resizable: true,dataIndex:'Account'}
				,{id:'Attribute',header: EzDesk.resalers.Locale.Attribute, width: 100, align: 'center',resizable: true,dataIndex:'Attribute'}
				,{id:'RechargeType',header: EzDesk.resalers.Locale.RechargeType, width: 160, align: 'center',resizable: true,dataIndex:'RechargeType'}
				,{id:'RechargeBalance',header: EzDesk.resalers.Locale.RechargeBalance, width: 100, align: 'center',resizable: true,dataIndex:'RechargeBalance'}
				,{id:'CurrencyType',header: EzDesk.resalers.Locale.CurrencyType, width: 100, align: 'center',resizable: true,dataIndex:'CurrencyType'}
				,{id:'RechargeTime',header: EzDesk.resalers.Locale.RechargeTime, width: 100, align: 'center',resizable: true,dataIndex:'RechargeTime'}
				,{id:'Commission',header: EzDesk.resalers.Locale.Commission, width: 100, align: 'center',resizable: true,dataIndex:'Commission'}
				,this.action
			]
			,plugins:[this.action]
			,loadMask:true
			,viewConfig:{ forceFit:true }
		}); // eo apply

		// add paging toolbar
		this.bbar = new Ext.PagingToolbar({
			 store:this.store
			,displayInfo:true
			,pageSize:20
		});

		// call parent
		EzDesk.recharge_log_grid.superclass.initComponent.apply(this, arguments);
	} // eo function initComponent
//	,onRender:function() {
//		// call parent
//		EzDesk.Grid.superclass.onRender.apply(this, arguments);
//		// load the store
//		this.store.load({params:{start:0, limit:20}});
//
//	} // eo function onRender
}); // eo extend grid

Ext.reg('recharge_log_grid', EzDesk.recharge_log_grid);


//经销商提成结算查询页面
EzDesk.balance_log_grid = Ext.extend(Ext.grid.GridPanel, {
    columnLines: true
    ,header : false
	,region : 'center'
	,title: 'Agent Grid'
	,initComponent:function() {	
		// Create RowActions Plugin
		this.action = new EzDesk.RowActions({
			 header:'Actions'
			,align: 'center'
			,keepSelection:true
			,actions: [{
                iconCls: 'icon-wrench',
                tooltip: 'View',
                qtipIndex: 'p_qtip',
                iconIndex: 'p_icon',
                hideIndex: 'p_hide'
                //	,text: '查看'
            },{
                iconCls: 'icon-wrench',
                tooltip: 'Edit',
                qtipIndex: 'p_qtip2',
                iconIndex: 'p_icon2',
                hideIndex: 'p_hide2'
                //,text:'编辑'
            }]
		});
		// dummy action event handler - just outputs some arguments to console
		this.action.on({
			action:function(grid, record, action, row, col) {
				var data = record.data;
				switch(action) 
				{
//					case 'icon-form-view':
//					new EzDesk.addANIDialog(grid.app, grid.moduleId, grid);
//					break;
					case 'icon-edit-record':
					new EzDesk.editAgentDialog(grid.app, grid.moduleId, grid, data);
					break;
				}
			}
			
		});//eo privilege  actions
		// configure the grid
		Ext.apply(this, {
		//	autoWidth: true
		//	autoHeight: true,
			//width: '700'
			//,height: '500'
			store : new Ext.data.GroupingStore({
				reader : new Ext.data.JsonReader({
					 idProperty: 'AgentID'
					,totalProperty: 'totalCount'
					,root: 'data'
					,messageProperty:'message'
					,successProperty: 'success'
					,fields:[				
						{name: 'PID', type: 'string'}
						,{name: 'VID', type: 'string'}
						,{name: 'CommissionType', type: 'string'}
						,{name: 'CommissionBalance', type: 'string'}
						,{name: 'CurrencyType', type: 'string'}
						,{name: 'RequestCommissionTime', type: 'string'}
						,{name: 'CommissionTime', type: 'string'}
						,{name: 'p_qtip', type: 'string'}
						,{name: 'p_icon', type: 'string'}
						,{name: 'p_hide', type: 'boolean'}
						,{name: 'p_qtip2',type: 'string'}
						,{name: 'p_icon2',type: 'string'}
						,{name: 'p_hide2', type: 'boolean'}
					]
				})
				,proxy:new Ext.data.HttpProxy({
					url : this.connect
					,method : 'POST'
					
				})
				,baseParams: {
					action: 'rasaler_info_list',
					moduleId: this.moduleId
				}
				,listeners:{
					load:{scope:this, fn:function() {
						this.getSelectionModel().selectFirstRow();
					}}
				}
			})
			,columns:[
				{id:'PID', header: EzDesk.resalers.Locale.PID, width: 50, align: 'center',resizable: true, dataIndex: 'PID'}
				,{id:'VID',header: EzDesk.resalers.Locale.VID, width: 50, align:'center',resizable: true,dataIndex:'VID'}
				,{id:'CommissionType',header: EzDesk.resalers.Locale.CommissionType, width: 60, align: 'center',resizable: true, dataIndex:'CommissionType'}
				,{id:'CommissionBalance',header: EzDesk.resalers.Locale.CommissionBalance, width: 100, align: 'center',resizable: true,dataIndex:'CommissionBalance'}
				,{id:'CurrencyType',header: EzDesk.resalers.Locale.CurrencyType, width: 100, align: 'center',resizable: true,dataIndex:'CurrencyType'}
				,{id:'RequestCommissionTime',header: EzDesk.resalers.Locale.RequestCommissionTime, width: 100, align: 'center',resizable: true,dataIndex:'RequestCommissionTime'}
				,{id:'CommissionTime',header: EzDesk.resalers.Locale.CommissionTime, width: 100, align: 'center',resizable: true,dataIndex:'CommissionTime'}
				,this.action
			]
			,plugins:[this.action]
			,loadMask:true
			,viewConfig:{ forceFit:true }
		}); // eo apply

		// add paging toolbar
		this.bbar = new Ext.PagingToolbar({
			 store:this.store
			,displayInfo:true
			,pageSize:20
		});

		// call parent
		EzDesk.balance_log_grid.superclass.initComponent.apply(this, arguments);
	} // eo function initComponent
//	,onRender:function() {
//		// call parent
//		EzDesk.balance_log_grid.superclass.onRender.apply(this, arguments);
//		// load the store
//		this.store.load({params:{start:0, limit:20}});
//
//	} // eo function onRender
}); // eo extend grid

Ext.reg('balance_log_grid', EzDesk.balance_log_grid);

EzDesk.cdrGrid =  Ext.extend(Ext.grid.GridPanel, {
	initComponent:function() {	
		// Create RowActions Plugin
		this.action = new EzDesk.RowActions({
			 header: lang_log.ActionText
			,align: 'center'
			,keepSelection:true
			,actions:[
				{
					iconCls: 'icon-wrench'
					,tooltip: 'Edit'
					,text: '设备'
				}
			]
		});
		// dummy action event handler - just outputs some arguments to console
		this.action.on({
			action:function(grid, record, action, row, col) {	
				var data = record.data;
				new EzDesk.deviceInfoDialog(grid.app, grid.moduleId, record.data);									
			}
			
		});//eo privilege  actions
		
		// configure the grid
		Ext.apply(this, {
			//autoWidth: true
			//height: 344
			store : new Ext.data.GroupingStore({
				reader : new Ext.data.JsonReader({
					 id: 'IMEI'
					,totalProperty: 'totalCount'
					,messageProperty:"message"
					,root: 'data'
					,fields:[
						{name: 'IMEI', type: 'string'}
						,{name: 'PhoneNO', type: 'string'}
						,{name: 'Account', type: 'string'}
						,{name: 'ProductType', type: 'string'}
						,{name: 'ActiveTime', type: 'string'}
						,{name: 'Currency', type: 'string'}
						,{name: 'FreeTime', type: 'string'}
						,{name: 'HireTime', type: 'string'}
						,{name: 'Account', type: 'string'}
						,{name: 'UserPlan', type: 'string'}
						,{name: 'AgentPlan', type: 'string'}
						,{name: 'Agent', type: 'string'},
						,{name: 'InitializeBalance', type: 'string'}
						
					]
				})
				,proxy:new Ext.data.HttpProxy({
					url : this.app.connection
					,method : 'POST'
					
				})
				,baseParams: {
					action: 'd_list',
					moduleId: this.moduleId
				}
				,listeners:{
					load:{scope:this, fn:function() {
						this.getSelectionModel().selectFirstRow();
					}}
				}
			})
			,columns:[
				{id:'IMEI',header:lang_log.IMEI,fixed:true,width: 180, align: 'center',resizable: true, dataIndex: 'IMEI'}
				,{id:'PhoneNO', header: lang_log.PhoneNO, width: 150, align: 'center',resizable: true, dataIndex: 'PhoneNO'}
				,{id:'Account',header: lang_log.Account, width: 150, align: 'center',resizable: true, dataIndex: 'Account'}
				,{id:'ProductType',header: lang_log.ProductType, width: 100, align: 'center',resizable: true,dataindex:'ProductType'}
				,this.action
			]
			,plugins:[this.action]
			,view: new Ext.grid.GroupingView({
			 	 forceFit:true
				,groupTextTpl:'{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
			})
			,loadMask:true
//			,viewConfig:{forceFit:true}
		}); // eo apply

		// add paging toolbar
		this.bbar = new Ext.PagingToolbar({
			 store:this.store
			,displayInfo:true
			,pageSize:20
		});

		// call parent
		EzDesk.cdrGrid.superclass.initComponent.apply(this, arguments);
	} // eo function initComponent
	,onRender:function() {
		// call parent
		EzDesk.cdrGrid.superclass.onRender.apply(this, arguments);
		// load the store
		this.store.load({
				params:{start:0, limit:20}
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



Ext.reg('cdr_grid_panel',EzDesk.cdrGrid);
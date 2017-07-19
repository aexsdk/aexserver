Ext.namespace('EzDesk');

EzDesk.endpoints = Ext.extend(Ext.app.Module, {
   /**
	 * Read only.
	 * 
	 * @type {String}
	 */
   id: 'endpoints'
   /**
	 * Read only.
	 * 
	 * @type {String}
	 */
   , type: 'billing/endpoints'
   /**
	 * Read only.
	 * 
	 * @type {Object}
	 */
   , locale: null
   /**
	 * Read only.
	 * 
	 * @type {Ext.Window}
	 */
   , win: null
   /**
	 * Read only.
	 * 
	 * @type {String}
	 */
   , errorIconCls : 'x-status-error'

   , init : function(){
    	this.locale = EzDesk.endpoints.Locale;
	}

   , createWindow : function(){
      var d = this.app.getDesktop();
      this.win = d.getWindow(this.id);

      var h = parseInt(d.getWinHeight() * 0.9);
      var w = parseInt(d.getWinWidth() * 0.9);
      if(h > 260){h = 550;}
      if(w > 310){w = 900;}

      if(this.win){
         this.win.setSize(w, h);
      }else{
         this.statusbar = new Ext.ux.StatusBar({
            defaultText: lang_tr.Ready
         });

         this.win = d.createWindow({
            animCollapse: false
            , constrainHeader: true
            , id: this.id
            , height: h
            , iconCls: 'm-devices-icon'
            , items:  [
                new EzDesk.js_manage_endpointsUi({
					app : this.app,
					desktop: this.app.getDesktop(),
					connect: this.app.connection,
					moduleId: this.id
				})	
			]
            , layout: 'fit'
            , shim: false
            , taskbuttonTooltip: this.locale.launcherTooltip
            , title: this.locale.windowTitle
            , width: w
         });
      }
      // show the window
      this.win.show();
   }
   , onCancel : function(){
      this.win.close();
   }
});


Ext.namespace('EzDesk');

EzDesk.MessageBox = Ext.extend(Ext.Window,{
							   modal:true
							   });

EzDesk.EndpointActions = function(p_app,p_connect){
	return {
		app : p_app
		,connect : p_connect
		,about : function(){
					// alert(this.connect);
					var win = this.app.getDesktop().createWindow({
							title:lang_module.mc_Terminal,
							"width": 341,
							"height": 217,
							"header": false,
							"layout": "border",
							"items": [
								{
									"xtype": "panel",
									"title": "",
									"region": "south",
									"header": false,
									"autoHeight": false,
									"layout": "absolute",
									"height": 43,
									"width": 398,
									"items": [
										{
											"xtype": "button",
											"text": lang_tr.Ok,
											"x": 120,
											"y": 0,
											"width": 70,
											"height": 30
										}
									]
								},
								{
									"xtype": "panel",
									"region": "center",
									"header": false,
									"layout": "absolute",
									"items": [
										{
											"xtype": "label",
											"text": lang_module.mc_Terminal + '  ' + lang_endpoints.Version,// "Endpoint
																											// Manager
																											// V1.0-Bata",
											"x": 80,
											"y": 40,
											"style": "",
											"cls": "font:48px;"
										},
										{
											"xtype": "label",
											"text": EzDesk.endpoints.Locale.Author,
											"x": 120,
											"y": 120
										}
									]
								}
							]
						});
					win.show();
			}
			,query:function(){
				var ep_type_btng = Ext.getCmp('btng_endpoint_type');
				var ep_status_btng = Ext.getCmp('btng_endpoint_status');
				var input = Ext.getCmp('fc_ep_main_input_endpoint');
				var types = new Array();
				if(ep_type_btng){
					for(var i = 0, len = ep_type_btng.items.length; i < len; i++){
						   if(ep_type_btng.items.get(i).pressed === true){
							   types.push(i);
						   }
					 }
			   }
			   // alert(types.toString());
			   if(types.length == 0)
			   		types.push(1);
				// alert(types.toString());
				var statuss = new Array();
				if(ep_status_btng){
					for(var i = 0, len = ep_status_btng.items.length; i < len; i++){
						   if(ep_status_btng.items.get(i).pressed === true){
							   statuss.push(i);
						   }
					 }
			   }
			   if(statuss.length == 0)
					statuss.push(1);
				// alert(statuss.toString());
			
				var endpoint = '';
				if(input){
					endpoint = input.getValue();
				}else{
					endpoint = '';
				}
				
				// var post_str =
				// String.format('type={0}&status={1}&endpoint={2}',types.toString(),statuss.toString(),endpoint);
				// alert(post_str);
				var grid = Ext.getCmp('endpoints_grid_pannel_obj');
				if(grid){
					// alert(grid.xtype);
					grid.store.setBaseParam('type',types.toString());
					grid.store.setBaseParam('endpoint',endpoint);
					grid.store.setBaseParam('status',statuss.toString());
					grid.store.load({
						params:{start:0, limit:20}
						,callback :function(r,options,success) {	
							if(!success){
								var notifyWin = this.desktop.showNotification({
							        html: this.store.reader.jsonData.message || this.store.reader.jsonData.msg
									, title: lang_tr.Error
							      });
								grid.store.removeAll();
							}
						}
						,scope:grid					
					});
				}
			}
	};
};

EzDesk.endpointGrid = Ext.extend(Ext.grid.GridPanel, {
	/*
	 * app : this.app, //Ӧ�ó������ desktop: null, //������� connect: this.app,
	 * //Action�����l�ӵ�ַ��ָ����ַ�Action��PHP moduleId: '',
	 */// ģ�����
    columnLines: true
	,region : 'center'
	,initComponent:function() {	
		// Create RowActions Plugin
		this.action = new EzDesk.RowActions({
			 header:EzDesk.endpoints.Locale.Actions
			,align: 'center'
			,keepSelection:true
			,actions:[
				{
					iconCls: 'icon-wrench'
					,tooltip: EzDesk.endpoints.Locale.ViewTooltip
					// ,qtipIndex: 'p_qtip'
					// ,iconIndex: 'p_icon'
					// ,hideIndex: 'p_hide'
					,text: EzDesk.endpoints.Locale.View
				}
			]
		});
		// dummy action event handler - just outputs some arguments to console
		this.action.on({
			action:function(grid, record, action, row, col) {
				new EzDesk.endpointViewDialog(grid.app, grid.moduleId, record.data);
			}
			
		});// eo privilege actions
		// configure the grid
		Ext.apply(this, {
			// autoWidth: true
			// height: 344
			store : new Ext.data.GroupingStore({
				reader : new Ext.data.JsonReader({
					 id: 'E164'
					,totalProperty: 'totalCount'
					,messageProperty:"message"
					,root: 'data'
					,fields:[
						{name: 'E164', type: 'string'}
						,{name: 'h323id', type: 'string'}
						,{name: 'Status', type: 'string'}
						,{name: 'CallerNo', type: 'string'}
						,{name: 'GustName', type: 'string'}
						,{name: 'Caption', type: 'string'}
						,{name: 'ChargeScheme', type: 'string'}
						,{name: 'Balance', type: 'string'}
						,{name: 'CurrencyType', type: 'string'}
						,{name: 'FreePeriod', type: 'string'}
						,{name: 'EndpointType', type: 'int'}
						,{name: 'Bind_SN', type: 'boolean'}
						,{name: 'Guid_SN', type: 'string'}
						,{name: 'ActivePeriod', type: 'string'}
						,{name: 'WarrantyPeriod', type: 'string'}
						,{name: 'FirstRegister', type: 'string'}
						,{name: 'LastRegisger', type: 'string'}
						,{name: 'FirstCall', type: 'string'}
						,{name: 'LastCall', type: 'string'}
						,{name: 'ValidPeriod', type: 'string'}
						,{name: 'ActiveTime', type: 'string'}
						,{name: 'HireDate',type:'string'}
						,{name: 'AliasList', type: 'string'}
						,{name: 'cs_id', type: 'int'}
						,{name: 'status', type: 'int'}
						
					]
				})
				,proxy:new Ext.data.HttpProxy({
					url : this.connect
					,method : 'POST'
					
				})
				,baseParams: {
					method: 'ep_list',
					moduleId: this.moduleId,
					type: '0,1',
					status : '0,1,2',
					endpoint: ''
				}
				,listeners:{
					load:{scope:this, fn:function() {
						this.getSelectionModel().selectFirstRow();
					}}
				}
			})
			,columns:[
				{id:'E164',header:EzDesk.endpoints.Locale.Account,width: 120, align: 'Left',resizable: true, dataIndex: 'E164'}
				,{id:'h323id',header:EzDesk.endpoints.Locale.ActivePNO,width: 120, align: 'Left',resizable: true, dataIndex: 'h323id'}
				,{id:'Status', header: EzDesk.endpoints.Locale.Status, width: 45, align: 'Left',resizable: true, dataIndex: 'Status'}
				,{id:'ChargeScheme',header: EzDesk.endpoints.Locale.ChargePlan, width: 120, align: 'Left',resizable: true, dataIndex: 'ChargeScheme'}
				,{id:'Balance',header: EzDesk.endpoints.Locale.Balance, width: 50, align:'Right',resizable: true,dataIndex:'Balance'}
				,{id:'Currency',header: EzDesk.endpoints.Locale.Currency, width: 40, align: 'Left',resizable: true, dataIndex:'CurrencyType'}
				,{id:'FreePeriod',header: EzDesk.endpoints.Locale.FreeTime, width: 60, align: 'Left',resizable: true, dataIndex:'FreePeriod'}
				,{id:'AliasList',header: EzDesk.endpoints.Locale.IMEI, width: 120, align: 'Left',resizable: true,dataIndex:'AliasList'}
				,{id:'ActiveTime',header: EzDesk.endpoints.Locale.ActiveTime, width: 120, align: 'Left',resizable: true,dataIndex:'ActiveTime'}
				,{id:'LastCall',header: EzDesk.endpoints.Locale.LastCallTime, width: 120, align: 'Left',resizable: true,dataIndex:'LastCall'}
				,{id:'HireDate',header: EzDesk.endpoints.Locale.Hiredate, width: 120, align: 'Left',resizable: true,dataIndex:'HireDate'}
				,{id:'CallerNo',header:EzDesk.endpoints.Locale.PhoneNO,width: 120, align: 'Left',resizable: true, dataIndex: 'CallerNo'}
				,{id:'Guid_SN',header:EzDesk.endpoints.Locale.GUID,width: 120, align: 'Left',resizable: true, dataIndex: 'Guid_SN'}
				,{id:'Bind_SN',header:EzDesk.endpoints.Locale.BINDGUID,width: 40, align: 'Left',resizable: true, dataIndex: 'Bind_SN'}
				,this.action
			]
			,plugins:[this.action]
			,loadMask:true
			// ,viewConfig:{forceFit:true}
		}); // eo apply

		// add paging toolbar
		this.bbar = new Ext.PagingToolbar({
			 store:this.store
			,displayInfo:true
			,pageSize:20
		});

		// call parent
		EzDesk.endpointGrid.superclass.initComponent.apply(this, arguments);
	} // eo function initComponent
	,onRender:function() {
		// call parent
		EzDesk.endpointGrid.superclass.onRender.apply(this, arguments);
		// load the store
		this.store.load({params:{start:0, limit:20}});

	} // eo function onRender
}); // eo extend grid

Ext.reg('endpoints_grid_panel',EzDesk.endpointGrid);
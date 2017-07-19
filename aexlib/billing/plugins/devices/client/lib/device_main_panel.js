Ext.namespace('EzDesk');

EzDesk.EndpointActions = function(p_app,p_connect){
	return {
		app : p_app
		,connect : p_connect
		,query : function(){
			var grid = Ext.getCmp('device_grid_panel_obj');
			if(grid){
				grid.store.removeAll();
				var type = Ext.getCmp('queryType').getValue();
				//var type = Ext.getCno('queryType').dom.value;
				var value = Ext.get('queryValue').dom.value;
				grid.store.setBaseParam('type',type);
				grid.store.setBaseParam('value',value);
				grid.store.load({
					params:{start:0, limit:20}
					,callback :function(r,options,success) {	
						if(!success){
							var notifyWin = p_app.getDesktop.showNotification({
						        html: this.store.reader.jsonData.message || this.store.reader.jsonData.msg
								, title: lang_tr.Error
						      });
						}
						grid.store.removeAll();
					}
					,scope:grid				
				});
			}
		}
	}
};

EzDesk.device_main_panel = Ext.extend(Ext.Panel, {
	app : null,
	desktop: null,
	connect: '',
	moduleId: '',
	layout: 'border',
    initComponent: function() {
		var r_app = this.app; 
		var r_moduleId = this.moduleId; 
		var r_desktop = this.desktop; 
		var r_connect = this.connect; 
		var deviceAgentID;
		this.actions = new EzDesk.EndpointActions(this.app, this.connect);
        this.tbar = {
            xtype: 'toolbar',
            region: 'north',
            items: [
               {
                    xtype: 'button',
                    text: EzDesk.devices.Locale.DeviceStock,
                    itemId: 'btn_stock',
                    hidden : this.app.isAllowedTo('stock_action', this.moduleId) ? false : true,
                    menu: {
                        xtype: 'menu',
                        itemId: 'menu_stock',
                        items: [
	                        {
	                            xtype: 'menuitem',
	                            hidden : this.app.isAllowedTo('select_stock', this.moduleId) ? false : true,
	                            text: EzDesk.devices.Locale.InStock,
	                            handler: function(){
		                        	var stock_panel = new EzDesk.StockPanel({
		                            	baseUrl : r_connect,
		                        		moduleId : r_moduleId,
		                        		desktop : r_desktop
		                            });
		                        	
		                            var stock_win = r_desktop.createWindow({
		                                animCollapse: false
		                                , constrainHeader: true
		                                , id: 'stock_panel'
		                                , height: 500
		                                , iconCls: 'm-devices-icon'
		                                , items:  [stock_panel]
		                                , layout: 'fit'
		                                , shim: false
		                                , width: 900
										, title: "入库管理"
		                             });
		                    		// show the window
		                    		stock_win.show();
		                    	}
	                        },{
	                            xtype: 'menuitem',
	                            text: EzDesk.devices.Locale.OutStock,
	                            handler: function(){
	                        		var delivery_panel = new EzDesk.DeliveryPanel({
		                            	baseUrl : r_connect,
		                        		moduleId : r_moduleId,
		                        		desktop : r_desktop
		                            });
		                        	
		                            var delivery_win = r_desktop.createWindow({
		                                animCollapse: false
		                                , constrainHeader: true
		                                , id: 'delivery_panel'
		                                , height: 500
		                                , iconCls: 'm-devices-icon'
		                                , items:  [delivery_panel]
		                                , layout: 'fit'
		                                , shim: false
		                                , width: 900
										, title: "出库管理"
		                             });
		                    		// show the window
		                    		delivery_win.show();
								}    
	                        }
						]
                    }
                }
//               ,{
//                    xtype: 'button',
//					text: EzDesk.devices.Locale.Tool,
//                    itemId: 'btn_tools',
//                    menu: {
//                        xtype: 'menu',
//                        itemId: 'menu_tools',
//                        items: [
//                            {
//                                xtype: 'menuitem',
//                                text: EzDesk.devices.Locale.UnbindTool,
//								handler: function(){
//									new EzDesk.unbindDialog(r_app, r_moduleId);
//								}
//                            }
//                        ]
//                    }
//                }
            ]
        };
        this.items = [
        {
            xtype: 'panel',
            title: 'My Panel',
            region: 'center',
            width: 100,
            layout: 'fit',
            header: false
			,items:
			 [
                {
                  	xtype : 'devices_grid_panel',
					id : 'device_grid_panel_obj',
					name: 'device_grid_panel_obj',
					app: r_app,
					moduleId: r_moduleId
                }
            ]
        },
        {
            xtype: 'panel',
            title: 'My Panel',
            region: 'north',
            width: 100,
            header: false,
            autoHeight: true,
            tbar: {
                xtype: 'toolbar',
                region: 'north',
                items: [
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
				        data: [['is_time', lang_tr.is_time], ['os_time', lang_tr.os_time],['active_time',lang_tr.active_time],['update_time',EzDesk.devices.Locale.UpdateTime]]
				    }),
				    valueField : 'code',
				    displayField: 'displayText'
				    ,value : 'is_time'
				},
				{
				    xtype: 'tbtext',
				    text: lang_tr.From
				},
				{
				    xtype: 'datefield',
				    fieldLabel: 'Label',
				    width: 130,
				    id: 'fd_device_from',
				    format:'Y-m-d H:i:s',
				    value:new Date((new Date()).getFullYear()-1,(new Date()).getMonth(),(new Date()).getDate())
				},
				{
				    xtype: 'tbtext',
				    text: lang_tr.To
				},{
				    xtype: 'datefield',
				    fieldLabel: 'Label',
				    width: 130,
				    id: 'fd_device_to',
				    format:'Y-m-d H:i:s',
				    value:new Date((new Date()).getFullYear(),(new Date()).getMonth(),(new Date()).getDate()+1)
				},'&nbsp',
				{
				    xtype: 'tbtext',
				    text: EzDesk.devices.Locale.Agent
				},{
					xtype: 'combo'
				    ,width: 120
				    ,name:'deviceAgent'
				    ,id: 'deviceAgent'
				    ,editable:false
					,typeAhead: true
				    ,triggerAction: 'all'
				    ,lazyRender:true
				    ,mode: 'local'
				    ,disabled : true
				    ,store: new Ext.data.ArrayStore({
				        fields: [
				            'agent_name',
				            'agent_id'
				        ]
				       // ,data: deviceAgentArray
				    })
				    ,valueField : 'agent_id'
				    ,displayField: 'agent_name'
				},{
				    xtype: 'button'
				    ,text: EzDesk.devices.Locale.AgentQuery
				    //,name: lang_tr.Query
				    ,width: 30
					,handler:  function(){
					 	var winManager = r_desktop.getManager();
						EzDesk.deviceAgentTree = new Ext.ux.tree.RemoteTreePanel({
							 id:'remotetree'
							,autoScroll:true
							,rootVisible:false
							,editable:false
							,contextMenu:false
							,root:{
								 nodeType:'async'
								,id:'root'
								,text:'Root'
								,expanded:true
								,uiProvider:false
							}
							,loader: {
								 url: r_connect
								,preloadChildren:true
								,baseParams:{
									method: 'get_resaler_tree'
									,moduleId: r_moduleId 
									,treeTable: 'tree'
									,treeID: 1
								}
							}
						});
						
						if (!dialog) {
					        var dialog = new Ext.Window({
					            //title: EzDesk.AgentLang.Title.ANITitle,
					            bodyStyle: 'padding:10px',
					            layout: 'fit',
					            width: 225,
					            height: 400,
					            closeAction: 'close',
					            plain: true,
					            items: [ EzDesk.deviceAgentTree],
					            buttons: [{
				                    text: EzDesk.devices.Locale.Sure
				                    ,handler: function(){
				                    	dialog.close();
				                    }
				                }],
					            manager: winManager,
					            modal: true
					        });
					    }
						dialog.show();
						
						EzDesk.deviceAgentTree.on('beforeexpandnode',function(node){//展开时在gird加载对应的数据数据
							var combo = Ext.getCmp('deviceAgent');
							combo.setValue(node.text);
							deviceAgentID = node.id;
							//combo.valueField.value = node.id;
				    	});
				
						EzDesk.deviceAgentTree.on('click',function(node){//单击树的一个节点 grid显示该节点的单位信息
							var combo = Ext.getCmp('deviceAgent');
							combo.setValue(node.text);
							deviceAgentID = node.id;
							//combo.valueField.value = node.id;
						});
					}
				},{
				    xtype: 'tbfill'
				},{
				    xtype: 'tbseparator'
				},{
				    xtype: 'label',
				    text: EzDesk.devices.Locale.WhereEx,
				    width: 26
				}, {
				    xtype: 'textfield',
				    name: 'queryValue',
				    id: 'queryValue',
				    width: 150,
				    allowBlank: false
				}, {
				    xtype: 'button',
				    text: EzDesk.devices.Locale.Query,
				    name: lang_tr.Query,
				    width: 50
					,handler:  function(){
						var fd_time_type = Ext.getCmp('fd_time_type').getValue();
						var fdr_from = Ext.getCmp('fd_device_from').getValue();
						var fdr_to = Ext.getCmp('fd_device_to').getValue();
						var value = Ext.getCmp('queryValue').getValue();
						var grid = Ext.getCmp('device_grid_panel_obj');
						if(grid){
							grid.store.removeAll();
							grid.store.setBaseParam('from',fdr_from.format('Y-m-d H:i:s'));
							grid.store.setBaseParam('to',fdr_to.format('Y-m-d H:i:s'));
							grid.store.setBaseParam('type',fd_time_type);
							grid.store.setBaseParam('agent_id',deviceAgentID);
							grid.store.setBaseParam('value',value);
							grid.store.load({
								params:{start:0, limit:20}
								,callback :function(r,options,success) {	
									if(!success){
	//									var notifyWin = p_app.getDesktop.showNotification({
	//								        html: this.store.reader.jsonData.message.toString()
	//										, title: lang_tr.Error
	//								      });
									}
								}
								,scope:grid				
							});
						}
					}
				}]
            }
        }];
        EzDesk.device_main_panel.superclass.initComponent.call(this);
    }
});


Ext.reg('device_main_panel', EzDesk.device_main_panel);
Ext.namespace('EzDesk.voip_cdr');

EzDesk.voip_cdr.Actions = function(p_app,p_connect){
	return {
		app : p_app
		,connect : p_connect
		,about : function(){
					//alert(this.connect);
					var win = this.app.getDesktop().createWindow({
							title:lang_cdr.Title,
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
											"text": EzDesk.voip_cdr.Locale.Ok,
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
											"text": EzDesk.voip_cdr.Locale.Title + '  ' + EzDesk.voip_cdr.Locale.Version,//"Endpoint Manager V1.0-Bata",
											"x": 80,
											"y": 40,
											"style": "",
											"cls": "font:48px;"
										},
										{
											"xtype": "label",
											"text": EzDesk.voip_cdr.Locale.Author,
											"x": 120,
											"y": 120
										}
									]
								}
							]
						});
					win.show();
			}
	}
};

EzDesk.voip_cdr.Grid = Ext.extend(Ext.grid.GridPanel, {
    columnLines: true
	,region : 'center'
	,initComponent:function() {	
		// Create RowActions Plugin
		var r_moduleId = this.moduleId; 
		var r_desktop = this.desktop; 
		var r_connect = this.connect; 
		this.action = new EzDesk.RowActions({
			 header:EzDesk.voip_cdr.Locale.Actions
			,align: 'center'
			,keepSelection:true
			,actions:[
				{
					iconCls: 'icon-wrench'
					,tooltip: EzDesk.voip_cdr.Locale.ViewTooltip
					//,qtipIndex: 'p_qtip'
					//,iconIndex: 'p_icon'
					//,hideIndex: 'p_hide'
					,text: EzDesk.voip_cdr.Locale.View
				}
			]
		});
		// dummy action event handler - just outputs some arguments to console
		this.action.on({
			action:function(grid, record, action, row, col) {
				//new EzDesk.endpointViewDialog(grid.app, grid.moduleId, record.data);
			}
			
		});//eo privilege  actions
		// configure the grid
		Ext.apply(this, {
			//autoWidth: true
			//height: 344
			store : new Ext.data.GroupingStore({
				reader : new Ext.data.JsonReader({
					 idProperty: 'CDRDatetime'
					,totalProperty: 'totalCount'
					,root: 'data'
					,messageProperty:'message'
					,successProperty: 'success'
					,fields:[
						{name: 'CDRDatetime', type: 'string'}
						,{name: 'SessionID', type: 'string'}
						,{name: 'AcctStartTime', type: 'string'}
						,{name: 'PN_E164', type: 'string'}
						,{name: 'CallerID', type: 'string'}
						,{name: 'CallerGWIP', type: 'string'}
						,{name: 'CalledID', type: 'string'}
						,{name: 'CalledGWIP', type: 'string'}
						,{name: 'AcctSessionTime', type: 'string'}
						,{name: 'SessionTimeMin', type: 'string'}
						,{name: 'AcctSessionFee', type: 'string'}
						,{name: 'AgentFee', type: 'string'}
						,{name: 'BaseFee', type: 'string'}
						,{name: 'AcctSessionTimeOrg', type: 'string'}
						,{name: 'SessionTimeOrgMin', type: 'string'}
						,{name: 'TerminationCause', type: 'string'}
						,{name: 'Remark', type: 'string'}
						,{name: 'Guid_SN', type: 'string'}
					]
				})
				,proxy:new Ext.data.HttpProxy({
					url : this.connect
					,method : 'POST'
					
				})
				,baseParams: {
					method: 'cdr_list',
					moduleId: this.moduleId,
					from: '',
					to : '',
					caller:'',
					callee:'',
					type: ''
				}
				,listeners:{
					load:{scope:this, fn:function() {
						this.getSelectionModel().selectFirstRow();
					}}
				}
			})
			,columns:[
				{id:'AcctStartTime',header:EzDesk.voip_cdr.Locale.AcctStartTime,width: 140, align: 'Left',resizable: true, dataIndex: 'AcctStartTime'}
				,{id:'SessionID', header: 'SessionID', width: 120, align: 'Left',resizable: true, dataIndex: 'SessionID'}
				,{id:'Guid_SN', header: 'IMEI', width: 120, align: 'Left',resizable: true, dataIndex: 'Guid_SN'}
				,{id:'PN_E164', header: EzDesk.voip_cdr.Locale.E164, width: 100, align: 'Left',resizable: true, dataIndex: 'PN_E164',hidden:true}
				,{id:'CallerID',header: EzDesk.voip_cdr.Locale.CallerID, width: 120, align: 'Left',resizable: true, dataIndex: 'CallerID'}
				,{id:'CallerGWIP',header: EzDesk.voip_cdr.Locale.CallerGWIP, width: 100, align: 'Left',resizable: true,dataIndex:'CallerGWIP',
					hidden:true}
				,{id:'CalledID',header: EzDesk.voip_cdr.Locale.CalleeID, width: 200, align:'Left',resizable: true,dataIndex:'CalledID'}
				,{id:'CalledGWIP',header: EzDesk.voip_cdr.Locale.CalleeGWIP, width: 100, align: 'Left',resizable: true, dataIndex:'CalledGWIP',
					hidden:true}
				,{id:'AcctSessionTime',header: EzDesk.voip_cdr.Locale.AcctSessionTime, width: 60, align: 'Right',resizable: true, dataIndex:'AcctSessionTime',
					hidden:true}
				,{id:'SessionTimeMin',header: EzDesk.voip_cdr.Locale.SessionTimeMin, width: 100, align: 'Right',resizable: true,dataIndex:'SessionTimeMin'}
				,{id:'AcctSessionFee',header: EzDesk.voip_cdr.Locale.AcctSessionFee, width: 60, align: 'Right',resizable: true,dataIndex:'AcctSessionFee'}
				//,{id:'TerminationCause',header:EzDesk.voip_cdr.Locale.TerminationCause,width: 60, align: 'Left',resizable: true, dataIndex: 'TerminationCause',hidden:true}
			]
			,plugins:[this.action]
			,loadMask:true
			,viewConfig:{forceFit:true}
		}); // eo apply

		// add paging toolbar
		this.bbar = {
			 xtype:'paging'
			,store:this.store
			,displayInfo:true
			,pageSize:20,
	        buttons: [{
				id: 'grid-excel-button',
				text: 'Export to Excel...',
	            handler: function(){
	                document.location =	r_connect+'?method=downloadXML&moduleId='+r_moduleId;
  	            }
			}]
			//,plugins: new Ext.ux.ProgressBarPager()
		};

		// call parent
		EzDesk.voip_cdr.Grid.superclass.initComponent.apply(this, arguments);
	} // eo function initComponent
	,onRender:function() {
		// call parent
		EzDesk.voip_cdr.Grid.superclass.onRender.apply(this, arguments);
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

Ext.reg('cdr-grid-panel',EzDesk.voip_cdr.Grid);



EzDesk.voip_cdr.mainUi = Ext.extend(Ext.Panel, {
    header: false,
    layout: 'border',
	region: 'center',
    initComponent: function() {
		var cdrAgentID = '';
		var r_app = this.app; 
		var r_moduleId = this.moduleId; 
		var r_desktop = this.desktop; 
		var r_connect = this.connect; 
    	this.actions = new EzDesk.voip_cdr.Actions(this.app,this.connect);

        this.tbar = {
            xtype: 'toolbar',
            items: [
                {
                    xtype: 'buttongroup',
                    columns: 2,
                    id: 'btg_cdr_type',
                    items: [
                        {
                            xtype: 'button',
                            text: 'Runtime',
                            id: 'fd_runtime',
                            allowDepress: true,
                            enableToggle: true,
                            toggleGroup:'type',
                            pressed: true,
                            clickEvent: 'click'
                        },
                        {
                            xtype: 'button',
                            text: 'History',
                            id: 'fd_history',
                            allowDepress: true,
                            enableToggle: true,
                            toggleGroup:'type',
                            pressed: false,
                            clickEvent: 'click'
                        }
                    ]
                },
                {
                    xtype: 'tbseparator'
                },
                {
                    xtype: 'tbtext',
                    text: lang_tr.From
                },
                {
                    xtype: 'datefield',
                    fieldLabel: 'Label',
                    width: 140,
                    id: 'fd_cdr_from',
                    format:'Y-m-d H:i:s',
                    value:new Date((new Date()).getFullYear(),(new Date()).getMonth(),(new Date()).getDate())
                },
                {
                    xtype: 'tbtext',
                    text: lang_tr.To
                },
                {
                    xtype: 'datefield',
                    fieldLabel: 'Label',
                    width: 140,
                    id: 'fd_cdr_to',
                    format:'Y-m-d H:i:s',
                    value:new Date((new Date()).getFullYear(),(new Date()).getMonth(),(new Date()).getDate()+1)
                },
                {
                    xtype: 'tbseparator'
                },
                {
                    xtype: 'tbtext',
                    text: 'Caller'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Label',
                    width: 90,
                    id: 'fd_caller_filter'
                },
                {
                    xtype: 'tbtext',
                    text: 'Callee'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Label',
                    width: 90,
                    id: 'fd_callee_filter'
                },'&nbsp',
				{
				    xtype: 'tbtext',
				    text: EzDesk.voip_cdr.Locale.Agent
				},{
					xtype: 'combo'
				    ,width: 110
				    ,name:'cdrAgent'
				    ,id: 'cdrAgent'
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
				    ,text: EzDesk.voip_cdr.Locale.AgentQuery
				    //,name: lang_tr.Query
				    ,width: 30
					,handler:  function(){
					 	var winManager = r_desktop.getManager();
						EzDesk.cdrAgentTree = new Ext.ux.tree.RemoteTreePanel({
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
					            items: [ EzDesk.cdrAgentTree],
					            buttons: [{
				                    text: EzDesk.voip_cdr.Locale.Sure
				                    ,handler: function(){
				                    	dialog.close();
				                    }
				                }],
					            manager: winManager,
					            modal: true
					        });
					    }
						dialog.show();
						
						EzDesk.cdrAgentTree.on('beforeexpandnode',function(node){//展开时在gird加载对应的数据数据
							var combo = Ext.getCmp('cdrAgent');
							combo.setValue(node.text);
							cdrAgentID = node.id;
							//combo.valueField.value = node.id;
				    	});
				
						EzDesk.cdrAgentTree.on('click',function(node){//单击树的一个节点 grid显示该节点的单位信息
							var combo = Ext.getCmp('cdrAgent');
							combo.setValue(node.text);
							cdrAgentID = node.id;
							//combo.valueField.value = node.id;
						});
					}
				},
                {
                    xtype: 'tbfill'
                },
                {
                    xtype: 'tbtext',
                    text: 'Filter'
                },
                {
                    xtype: 'textfield',
                    fieldLabel:'',
                    width: 100,
                    id: 'fd_endpoint_filter'
                },
                {
                    xtype: 'button',
                    text: 'Query',
                    handler: function(){
        				var fdr_from = Ext.getCmp('fd_cdr_from');
        				var fdr_to = Ext.getCmp('fd_cdr_to');
        				var fdr_caller = Ext.getCmp('fd_caller_filter');
        				var fdr_callee = Ext.getCmp('fd_callee_filter');
        				var fdr_filter = Ext.getCmp('fdr_endpoint_filter');
        				var fdr_rt = Ext.getCmp('fd_runtime');
        				
        				var from = fdr_from?fdr_from.getValue():'';
        				var to = fdr_to?fdr_to.getValue():'';
        				var caller = fdr_caller?fdr_caller.getValue():'';
        				var callee = fdr_callee?fdr_callee.getValue():'';
        				var filter = fdr_filter?fdr_filter.getValue():'';
        				var type = fdr_rt? (fdr_rt.pressed?0:1):0;
        				
        				if(fdr_from && (!Ext.isDate(from))){
        					pfrom = new Date();
        					//alert(pfrom.format('Y-m-d H:i:s'));
        					from = new Date(pfrom.getFullYear(),pfrom.getMonth(),0);
        					alert(from.format('Y-m-d H:i:s'));
        					fdr_from.setValue(from);
        				}
        				if(fdr_to && (!Ext.isDate(to))){
        					pto = new Date();
        					//alert(pfrom.format('Y-m-d H:i:s'));
        					to = new Date(pto.getFullYear(),pto.getMonth()+1,0);
        					//alert(to.format('Y-m-d H:i:s'));
        					fdr_to.setValue(from);
        				}
        				var grid = Ext.getCmp('cdr_grid_pannel_obj');
        				
        				if(grid){
        					//alert(grid.xtype);
        					grid.store.setBaseParam('from',from.format('Y-m-d H:i:s'));
        					grid.store.setBaseParam('to',to.format('Y-m-d H:i:s'));
        					grid.store.setBaseParam('caller',caller.toString());
        					grid.store.setBaseParam('callee',callee.toString());
        					grid.store.setBaseParam('endpoint',filter.toString());
        					grid.store.setBaseParam('type',type.toString());
        					grid.store.setBaseParam('ageng_id', cdrAgentID.toString());
        					grid.store.load({
        						params:{start:0, limit:20}
        						,callback :function(r,options,success) {	
        							if(!success){
        								var notifyWin = this.desktop.showNotification({
        							        html: this.store.reader.jsonData.message||this.store.reader.jsonData.msg
        									, title: lang_tr.Error
        							      });
        							}
        						}
        						,scope:grid
        					});
        				}
                	},
                    scope:this
                }
            ]
        };
        this.items = [{
			xtype: 'cdr-grid-panel',
			region: 'center',
			id:'cdr_grid_pannel_obj',
			name: 'cdr_grid_pannel_obj',
			app: this.app,
			connect : this.connect,
			desktop: this.desktop,
			moduleId: this.moduleId
		}];
        EzDesk.voip_cdr.mainUi.superclass.initComponent.call(this);
    }
});


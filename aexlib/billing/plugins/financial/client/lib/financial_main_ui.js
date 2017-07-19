Ext.namespace('EzDesk.financial');

EzDesk.financial.Grid = Ext.extend(Ext.grid.GridPanel, {
    columnLines: true,
    region: 'center',
    initComponent: function(){
		var r_app = this.app; 
		var r_moduleId = this.moduleId; 
		var r_desktop = this.desktop; 
		var r_connect = this.connect; 
		var linkButton = this.linkButton;
		var deviceAgentID;
		var financialAgentID;
//        // Create RowActions Plugin
        this.action = new EzDesk.RowActions({
            header: EzDesk.financial.Locale.Actions,
            align: 'center',
            keepSelection: true,
            actions: [{
                iconCls: 'icon-wrench',
                tooltip: EzDesk.financial.Locale.ViewTooltip //,qtipIndex: 'p_qtip'
                //,iconIndex: 'p_icon'
                //,hideIndex: 'p_hide'
                //,
                //text: EzDesk.financial.Locale.View
            }]
        });
        // dummy action event handler - just outputs some arguments to console
//        this.action.on({
//            action: function(grid, record, action, row, col){
//                //new EzDesk.endpointViewDialog(grid.app, grid.moduleId, record.data);
//            }
//            
//        });//eo privilege  actions
        // configure the grid
        Ext.apply(this, {
            //autoWidth: true
            //height: 344
            store: new Ext.data.GroupingStore({
                reader: new Ext.data.JsonReader({
                    idProperty: 'id',
                    totalProperty: 'totalCount',
                    root: 'data',
                    messageProperty: 'message',
                    successProperty: 'success',
                    fields: [{
                        name: 'id',
                        type: 'string'
                    },{
                        name: 'H_Datetime',
                        type: 'string'
                    }, {
                        name: 'E164',
                        type: 'string'
                    }, {
                        name: 'Cost',
                        type: 'string'
                    }, {
                        name: 'RealCost',
                        type: 'string'
                    }, {
                        name: 'RC_Code',
                        type: 'string'
                    }, {
                        name: 'Remark',
                        type: 'string'
                    }, {
                        name: 'Pno',
                        type: 'string'
                    }, {
                        name: 'Guid_SN',
                        type: 'string'
                    }, {
                        name: 'CS_Name',
                        type: 'string'
                    }, {
                        name: 'SourcePin',
                        type: 'string'
                    }, {
                        name: 'Agent_Name',
                        type: 'string'
                    }, {
                        name: 'CurrencyType',
                        type: 'string'
                    }]
                }),
                proxy: new Ext.data.HttpProxy({
                    url: this.connect,
                    method: 'POST'
                
                }),
                baseParams: {
                    method: 'get_recharge_log',
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
            })      
            ,
            columns: [{
                id: 'H_Datetime',
                header: EzDesk.financial.Locale.H_Datetime,
                width: 140,
                align: 'Center',
                resizable: true,
                dataIndex: 'H_Datetime'
            }, {
                id: 'E164',
                header: EzDesk.financial.Locale.E164,
                width: 120,
                align: 'Center',
                resizable: true,
                dataIndex: 'E164'
            },{
                id: 'RC_Code',
                header: EzDesk.financial.Locale.RC_Code,
                width: 80,
                align: 'Center',
                resizable: true,
                dataIndex: 'RC_Code'
            }, {
                id: 'Pno',
                header: EzDesk.financial.Locale.Pno,
                width: 140,
                align: 'Center',
                resizable: true,
                dataIndex: 'Pno'
            }, {
                id: 'Guid_SN',
                header: EzDesk.financial.Locale.Guid_SN,
                width: 140,
                align: 'Center',
                resizable: true,
                dataIndex: 'Guid_SN'
            }, {
                id: 'CS_Name',
                header: EzDesk.financial.Locale.CS_Name,
                width: 80,
                align: 'Center',
                resizable: true,
                dataIndex: 'CS_Name'
            }, {
                id: 'Agent',
                header: EzDesk.financial.Locale.Agent,
                width: 80,
                align: 'Center',
                resizable: true,
                dataIndex: 'Agent_Name'
            }
//            , {
//                id: 'SourcePin',
//                header: EzDesk.financial.Locale.SourcePin,
//                width: 70,
//                align: 'Center',
//                resizable: true,
//                dataIndex: 'SourcePin'
//            }
            , 
            {
                id: 'Cost',
                header: EzDesk.financial.Locale.Cost,
                width: 60,
                align: 'Center',
                resizable: true,
                dataIndex: 'Cost'
            }, {
                id: 'RealCost',
                header: EzDesk.financial.Locale.RealCost,
                width: 60,
                align: 'Center',
                resizable: true,
                dataIndex: 'RealCost'
            }, {
                id: 'CurrencyType',
                header: EzDesk.financial.Locale.CurrencyType,
                width: 30,
                align: 'Center',
                resizable: true,
                dataIndex: 'CurrencyType'
            }, {
                id: 'Remark',
                header: EzDesk.financial.Locale.Remark,
                width: 180,
                align: 'Center',
                resizable: true,
                dataIndex: 'Remark'
            }],
            plugins: [this.action]            
            ,view: new Ext.grid.GroupingView({
            	forceFit:true
            }) ,
            loadMask: true
            //			,viewConfig:{forceFit:true}
        }); // eo apply
        // add paging toolbar
        this.bbar =new Ext.PagingToolbar({
    	    store: this.store,
    	    pageSize: 20,
	        buttons: [{
				id: 'grid-excel-button',
				text: 'Export to Excel...',
	            handler: function(){
	                document.location =	r_connect+'?method=downloadXML&moduleId='+r_moduleId;
  	            }
			}]
    	});

        var typeStore = new Ext.data.Store({
            autoLoad: true,
            method: 'POST',
            url: this.connect,
            //设定读取的格式    
            reader: new Ext.data.JsonReader({
                totalProperty: 'totalCount',
                root: 'data'
            }, ['Type']),
            baseParams :{
				method: 'get_recharge_type',
            	moduleId: this.moduleId
			}
        });
      
        this.tbar = [{
            xtype: 'tbtext',
            text: lang_tr.From
        },{
            xtype: 'datefield',
            id: 'fd_financial_from',
            format:'Y-m-d H:i:s',
            value:new Date((new Date()).getFullYear(),(new Date()).getMonth(),(new Date()).getDate()-90)
        },{
            xtype: 'tbtext',
            text: lang_tr.To
        },{
            xtype: 'datefield',
            id: 'fd_financial_to',
            format:'Y-m-d H:i:s',
            value:new Date((new Date()).getFullYear(),(new Date()).getMonth(),(new Date()).getDate()+1)
        },{
            xtype: 'tbseparator'
        },{
            xtype: 'tbtext',
            text: EzDesk.financial.Locale.Type
        },{
			xtype: 'combo',
			id: 'rechargeType',
			hiddenName: 'rechargeType',
            store: typeStore,
            valueField: 'Type',
            isFormField: true,
            displayField: 'Type',
            editable: false,
            typeAhead: true,
            mode: 'remote',
            triggerAction: 'all',
            selectOnFocus: true,
			margins:'5 0 1 10'
		},'&nbsp',
		{
		    xtype: 'tbtext',
		    text: EzDesk.financial.Locale.Agent
		},{
			xtype: 'combo'
		    ,width: 120
		    ,name:'financialAgent'
		    ,id: 'financialAgent'
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
		    ,text: EzDesk.financial.Locale.AgentQuery
		    //,name: lang_tr.Query
		    ,width: 30
			,handler:  function(){
			 	var winManager = r_desktop.getManager();
				EzDesk.financialAgentTree = new Ext.ux.tree.RemoteTreePanel({
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
			            items: [ EzDesk.financialAgentTree],
			            buttons: [{
		                    text: EzDesk.financial.Locale.Sure
		                    ,handler: function(){
		                    	dialog.close();
		                    }
		                }],
			            manager: winManager,
			            modal: true
			        });
			    }
				dialog.show();
				
				EzDesk.financialAgentTree.on('beforeexpandnode',function(node){//展开时在gird加载对应的数据数据
					var combo = Ext.getCmp('financialAgent');
					combo.setValue(node.text);
					financialAgentID = node.id;
					//combo.valueField.value = node.id;
		    	});
		
				EzDesk.financialAgentTree.on('click',function(node){//单击树的一个节点 grid显示该节点的单位信息
					var combo = Ext.getCmp('financialAgent');
					combo.setValue(node.text);
					financialAgentID = node.id;
					//combo.valueField.value = node.id;
				});
			}
		},{
            xtype: 'tbfill'
        },{
            xtype: 'tbtext',
            text: EzDesk.financial.Locale.E164
        },{
        	xtype: 'textfield',
        	id	: 'endpoint',
            name: 'endpoint',
			margins:'5 0 1 10'
		},{
			xtype: 'button',
            text: EzDesk.financial.Locale.Query,
			width: 80,
            name: 'Query',
			margins:'5 0 1 1', 
			handler: function(){
				var from = Ext.getCmp('fd_financial_from').getValue();
				var to = Ext.getCmp('fd_financial_to').getValue();
				
				var endpoint = Ext.get('endpoint').getValue();
				var rechargeType =   Ext.getCmp('rechargeType').getValue();

				var grid = Ext.getCmp('financial_grid_pannel_obj');
				if(grid){
					//alert(grid.xtype);
					grid.store.setBaseParam('type', rechargeType);
					grid.store.setBaseParam('endpoint', endpoint);
					grid.store.setBaseParam('agent', financialAgentID);
					grid.store.setBaseParam('from', from);
					grid.store.setBaseParam('to', to);
					grid.store.load({
						params:{start:0, limit:20}
						,callback :function(r,options,success) {	
							if(!success){
								var notifyWin = this.desktop.showNotification({
							        html: this.store.reader.jsonData.message.toString()
									, title: lang_tr.Error
							      });
							}
						}
						,scope:grid					
					});
				}else{
					alert('no');
				}
			}
		}];
        // call parent
        EzDesk.financial.Grid.superclass.initComponent.apply(this, arguments);
    } // eo function initComponent
    ,
    onRender: function(){
        // call parent
        EzDesk.financial.Grid.superclass.onRender.apply(this, arguments);
        // load the store
        this.store.load({
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
            scope: this
        });
    } // eo function onRender
}); // eo extend grid
Ext.reg('financial_grid_panel', EzDesk.financial.Grid);
//array with data - dummy data

EzDesk.AgentGrid = Ext.extend(Ext.grid.GridPanel, {
    columnLines: true
    ,header : false
	//,region : 'center'
	//,title: 'Agent Grid'
	,initComponent:function() {	
		// Create RowActions Plugin
		this.action = new EzDesk.RowActions({
			 header: EzDesk.resalers.Locale.ActionText
			,align: 'center'
			,keepSelection:true
			,actions: [{
                tooltip: EzDesk.resalers.Locale.EditBillingInfo,
                //qtipIndex: 'p_qtip',
                iconIndex: 'p_icon',
                hideIndex: 'p_hide'
            },{
                tooltip: EzDesk.resalers.Locale.EditConfig,
                //qtipIndex: 'p_qtip2',
                iconIndex: 'p_icon2',
                hideIndex: 'p_hide2'
            },{
                tooltip: EzDesk.resalers.Locale.EditChargePlan,
                //qtipIndex: 'p_qtip2',
                iconIndex: 'p_icon3',
                hideIndex: 'p_hide3'
            },{
                tooltip: EzDesk.resalers.Locale.EditOEM,
                //qtipIndex: 'p_qtip2',
                iconIndex: 'p_icon4',
                hideIndex: 'p_hide4'
            }]
		});
		// dummy action event handler - just outputs some arguments to console
		this.action.on({
			action:function(grid, record, action, row, col) {
				var data = record;
				switch(action) 
				{
					case 'icon-resaler-oem-edit':
						new EzDesk.editAgentOEMDialog(grid.app, grid.moduleId, grid,  record);
						break;
					case 'icon-resaler-config-edit':
						new EzDesk.editAgentConfigDialog(grid.app, grid.moduleId, grid,  record);
						break;
					case 'icon-resaler-chargeplan-edit':
						new EzDesk.editAgentChargePlanDialog(grid.app, grid.moduleId, grid,  record);
						break;
					case 'icon-resaler-billing-edit':
						new EzDesk.editAgentDialog(grid.app, grid.moduleId, grid,  record);
						break;
				}
			}
		});//eo privilege  actions
		// configure the grid
		Ext.apply(this, {
			store : new Ext.data.GroupingStore({
				reader : new Ext.data.JsonReader({
					 idProperty: 'AgentID'
					,totalProperty: 'totalCount'
					,root: 'data'
					,messageProperty:'message'
					,successProperty: 'success'
					,fields:[
						{name: 'AgentID', type: 'string'}
						,{name: 'Agent_Name', type: 'string'}
						,{name: 'Caption', type: 'string'}
						,{name: 'HireBalance', type: 'string'}
						,{name: 'Balance', type: 'string'}
						,{name: 'RealBalance', type: 'string'}
						,{name: 'IsReal', type: 'string'}
						,{name: 'CurrencyType', type: 'string'}
						,{name: 'agtCurrencyType', type: 'string'}
						,{name: 'ChargeScheme', type: 'string'}
						,{name: 'Default_AgentCS', type: 'string'}
						,{name: 'Address', type: 'string'}
						,{name: 'Leader', type: 'string'}
						,{name: 'Connect', type: 'string'}
						,{name: 'EMail', type: 'string'}
						,{name: 'Prefix', type: 'string'}
						,{name: 'p_qtip', type: 'string'}
						,{name: 'p_icon', type: 'string'}
						,{name: 'p_hide', type: 'boolean'}
						,{name: 'p_qtip2', type: 'string'}
						,{name: 'p_icon2', type: 'string'}
						,{name: 'p_hide2', type: 'boolean'}
						,{name: 'p_qtip3', type: 'string'}
						,{name: 'p_icon3', type: 'string'}
						,{name: 'p_hide3', type: 'boolean'}
						,{name: 'p_qtip4', type: 'string'}
						,{name: 'p_icon4', type: 'string'}
						,{name: 'p_hide4', type: 'boolean'}
					]
				})
				,proxy:new Ext.data.HttpProxy({
					url : this.connect
					,method : 'POST'
					
				})
				,baseParams: {
					method: 'get_resaler_list',
					moduleId: this.moduleId
				}
				,listeners:{
					load:{scope:this, fn:function() {
						this.getSelectionModel().selectFirstRow();
					}}
				}
			})
			,columns:[
				{id:'AgentID',header: EzDesk.resalers.Locale.AgentID, width: 60, align: 'left',resizable: true, dataIndex: 'AgentID'}
				,{id:'Agent_Name', header: EzDesk.resalers.Locale.Agent_Name, width: 120, align: 'left',resizable: true, dataIndex: 'Agent_Name'}
				,{id:'Balance',header: EzDesk.resalers.Locale.Balance, width: 60, align:'right',resizable: true,dataIndex:'Balance'}
				,{id:'RealBalance',header: EzDesk.resalers.Locale.RealBalance, width: 60, align: 'right',resizable: true, dataIndex:'RealBalance'}
				,{id:'Leader',header: EzDesk.resalers.Locale.Leader, width: 100, align: 'left',resizable: true,dataIndex:'Leader'}
				//,{id:'Connect',header: EzDesk.resalers.Locale.Connect, width: 160, align: 'center',resizable: true,dataIndex:'Connect'}
				,{id:'Prefix',header: EzDesk.resalers.Locale.Prefix, width: 50, align: 'left',resizable: true,dataIndex:'Prefix'}
				,this.action
			]
			,plugins:[this.action]
			/*,view: new Ext.grid.GroupingView({
				// forceFit:true
				groupTextTpl:'{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
			})*/
			,loadMask:true
			,viewConfig:{ forceFit:true }
		}); // eo apply

		// add paging toolbar
		this.bbar = new Ext.PagingToolbar({
			 store:this.store
			,displayInfo:true
			,pageSize:25
		});

		// call parent
		EzDesk.AgentGrid.superclass.initComponent.apply(this, arguments);
	} // eo function initComponent
	,onRender:function() {
		// call parent
		EzDesk.AgentGrid.superclass.onRender.apply(this, arguments);
		// load the store
		//this.store.load({params:{start:0, limit:20}});

	} // eo function onRender
}); // eo extend grid

Ext.reg('agent_grid_panel',EzDesk.AgentGrid);
 




EzDesk.agent_main_ui = Ext.extend(Ext.Panel, {
	border: false,
    layout: 'border',
    initComponent: function() {
		var app = this.app;
		var moduleId = this.moduleId;
		var connect = this.connect;
		var agengID;
        /*this.layoutConfig = {
            align: 'stretch'
        };*/
        this.tbar = {
            xtype: 'toolbar',
            border:false,
            items: [
                {
                    xtype: 'button',
                    text: EzDesk.resalers.Locale.AddAgent,
                    width: 100,
					iconCls: 'icon-group-add',
					handler: function(){
	                	 var addAgentWizForm = new EzDesk.addAgentWizForm({
	                         ownerModule: this.ownerModule,
	                         scope: this
	                     });
	                	 addAgentWizForm.show();
					}
					,scope:this
                }
            ]
        };
        
		EzDesk.agentTree = new Ext.ux.tree.RemoteTreePanel({
			 id:'remotetree'
			,width:200
			,border:false
			,autoScroll:true
			,rootVisible:false
			,editable:false
			,contextMenu:false
			,region: 'west'
			,split: true
			,root:{
				 nodeType:'async'
				,id:'root'
				,text:'Root'
				,expanded:true
				,uiProvider:false
			}
			,loader: {
				 url:this.connect
				,preloadChildren:true
				,baseParams:{
					method: 'get_resaler_tree'
					,moduleId: this.moduleId 
					,treeTable: 'tree'
					,treeID: 1
				}
			}
		});
		
		EzDesk.agentTree.on('beforeexpandnode',function(node){//展开时在gird加载对应的数据数据
            var grid = Ext.getCmp('agent_grid_panel');
			if(grid){
				grid.store.removeAll();
				grid.store.setBaseParam('node',node.id);
				grid.store.load(
					{params:{start:0, limit:20}}
				);
			} 
    	});
	
		EzDesk.agentTree.on('click',function(node){//单击树的一个节点 grid显示该节点的单位信息
			var grid = Ext.getCmp('agent_grid_panel');
			agengID = node.id;
			if(node.leaf == false){
				if(grid){
					grid.store.removeAll();
					grid.store.setBaseParam('node',node.id);
					grid.store.load(
						{params:{start:0, limit:20}}
					);
				} 
			}else {
				if(grid){
					grid.store.removeAll();
				} 
			}
     	});   

        this.items = [
	        EzDesk.agentTree
	        ,{
	    		xtype: 'agent_grid_panel',
				id:	'agent_grid_panel',
				name: 'agent_grid_panel',
				border:false,
				region: 'center',
				app: this.app,
				connect : this.connect,
				desktop: this.desktop,
				moduleId: this.moduleId 
	        }];
        EzDesk.agent_main_ui.superclass.initComponent.call(this);
    }
});
Ext.reg('resaler_main_ui',EzDesk.agent_main_ui);




EzDesk.AgentBillingInfoFormUi = Ext.extend(Ext.form.FormPanel, {
    labelWidth: 75,
    labelAlign: 'left',
    layout: 'form',
    padding: 10,
    frame: true,
    border:false,
    initComponent: function(){
        this.items = [{
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.AgentID,
            anchor: '100%',
            name: 'AgentID',
			disabled  :true ,
            allowBlank: false
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.Agent_Name,
            anchor: '100%',
            name: 'Agent_Name'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.Caption,
            anchor: '100%',
            name: 'Caption'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.Balance,
            anchor: '100%',
			disabled  :true ,
            name: 'Balance'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.RealBalance,
            anchor: '100%',
			disabled  :true ,
            name: 'RealBalance'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.IsReal,
            anchor: '100%',
            name: 'IsReal'
        },{
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.EMail,
            anchor: '100%',
            name: 'EMail'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.ChargeScheme,
            anchor: '100%',
            name: 'ChargeScheme'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.Default_AgentCS,
            anchor: '100%',
            name: 'Default_AgentCS'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.Address,
            anchor: '100%',
            name: 'Address'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.Leader,
            anchor: '100%',
            name: 'Leader'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.Connect,
            anchor: '100%',
            name: 'Connect'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.Prefix,
            anchor: '100%',
            name: 'Prefix'
        }];
        this.fbar = {
            xtype: 'toolbar',
            items: [{
                xtype: 'button',
                text: 'Cancel'
            }, {
                xtype: 'button',
                text: 'Save',
                scope:this,
                handler: function(){
                    this.getForm().submit({
                        url: this.app.connection,
                        waitMsg: 'Loading',
                        method: 'POST',
                        params: {
                    		method: 'ani_edit',
                            moduleId: this.moduleId
							//,o_ani: data.ANI
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
        EzDesk.AgentBillingInfoFormUi.superclass.initComponent.call(this);
    }
});


/*
 wirter:  lion wang
 caption: edit agent
 version: 1.0
 time: 2010-06-03
 last time: 2010-06-03
 */
EzDesk.editAgentDialog = function(app, moduleId, grid, data){
    var desktop = app.getDesktop();
    var winManager = desktop.getManager();
    var connect = app.connection;
   	
    var f = new EzDesk.agentBillingFormUi({
    	id: 'editAgentBillingForm',
    	connect: connect,
        moduleId: moduleId,
        desktop: desktop,
        agent_id: data.data.AgentID
    });
    
    if (!this.dialog) {
        this.dialog = new Ext.Window({
            //title: EzDesk.AgentLang.Title.ANITitle,
            border: false,
            layout: 'fit',
            width: 500,
            height: 530,
            closeAction: 'close',
            plain: true,
            items: [f],
            manager: winManager,
            modal: true,
            fbar:[{
                xtype: 'button',
                text: EzDesk.resalers.Locale.Close
                ,scope:this
                ,handler:function(){
            		this.dialog.close();
            	}
            },{
            	xtype: 'button',
                text: EzDesk.resalers.Locale.Save
                ,scope:f
                ,handler:function(){
            		this.save();
            	}
            }]
        });
    }
    
	f.getForm().loadRecord(data) ;
	Ext.getCmp('edit_raslaerBUserCS').setValue(data.data.ChargeScheme);
	Ext.getCmp('edit_raslaerBAgentCS').setValue(data.data.Default_AgentCS);
    this.dialog.show();
};


EzDesk.agentBillingFormUi = Ext.extend(Ext.form.FormPanel, {
    labelWidth: 100,
    labelAlign: 'left',
    layout: 'form',
    id: 'editAgentForm',
    border: false,
    frame: true,
    initComponent: function(){
		var connect = this.connect;
	    var moduleId = this.moduleId;
	    var desktop = this.desktop;
	    var agent_id = this.agent_id;
        this.items = [{
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.AgentID,
            anchor: '100%',
            name: 'AgentID',
			disabled  :true ,
			//value : data.AgentID,
            allowBlank: false
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.Agent_Name,
            anchor: '100%',
			//value : data.Agent_Name,
            name: 'Agent_Name'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.Caption,
            anchor: '100%',
			//value : data.Caption,
            name: 'Caption'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.Balance,
            anchor: '100%',
			//value : data.Balance,
			disabled  :true ,
            name: 'Balance'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.RealBalance,
            anchor: '100%',
			//value : data.RealBalance,
			disabled  :true ,
            name: 'RealBalance'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.IsReal,
            anchor: '100%',
			//value : data.IsReal,
            name: 'IsReal'
        },{
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.EMail,
            anchor: '100%',
			//value : data.EMail,
            name: 'EMail'
        },{
        	xtype: 'BUserCSType',
        	id: 'edit_raslaerBUserCS',
        	name: 'edit_raslaer_user_cs',
        	allowBlank: false,
        	width: 302,
        	fieldLabel: EzDesk.resalers.Locale.ChargeScheme,	
        	connection: this.connect,
            moduleId: this.moduleId,
            desktop: this.desktop
        },{
        	xtype: 'BAgentCSType',
        	id: 'edit_raslaerBAgentCS',
        	name: 'edit_raslaer_agent_cs',
        	allowBlank: false,
        	width: 302,
        	fieldLabel: EzDesk.resalers.Locale.Default_AgentCS,	
        	connection: this.connect,
            moduleId: this.moduleId,
            desktop: this.desktop
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.Address,
            anchor: '100%',
			//value : data.Address,
            name: 'Address'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.Leader,
            anchor: '100%',
			//value : data.Leader,
            name: 'Leader'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.Connect,
            anchor: '100%',
			//value : data.Connect,
            name: 'Connect'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.Prefix,
            anchor: '100%',
			//value : data.Prefix,
            name: 'Prefix'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.Note,
            anchor: '100%',
			//value : data.Note,
            name: 'Note'
        }];
        this.save = function(){
    		this.getForm().submit({
                url: connect,
                waitMsg: 'Loading',
                method: 'POST',
                params: {
            		method: 'edit_resaler_info',
                    moduleId: moduleId,
                    agent_id: agent_id
                },
                success: function(addUserForm, action){
                    var obj = Ext.util.JSON.decode(action.response.responseText);
                    EzDesk.showMsg(EzDesk.resalers.Locale.EditChargePlan, obj.message, desktop);
                },
                failure: function(addUserForm, action){
                    //bbtn.setDisabled(false);
                    obj = Ext.util.JSON.decode(action.response.responseText);
                    if (action.failureType == 'server') {
                        EzDesk.showMsg(EzDesk.resalers.Locale.EditChargePlan, obj.message, desktop);
                    }
                    else {
                        EzDesk.showMsg(EzDesk.resalers.Locale.EditChargePlan, obj.message, desktop);
                    }
                }
            });
        };
        EzDesk.agentBillingFormUi.superclass.initComponent.call(this);
    }
});




EzDesk.editAgentChargePlanDialog = function(app, moduleId, grid, data){
    var desktop = app.getDesktop();
    var winManager = desktop.getManager();
    var connect = app.connection;
    var f = new EzDesk.AgentChargePlanFormUi({
    	id: 'editAgentChargePlanForm',
    	connect: connect,
        moduleId: moduleId,
        desktop: desktop,
        agent_id: data.data.AgentID
    });
    if (!this.dialog) {
        this.dialog = new Ext.Window({
            title: EzDesk.resalers.Locale.EditChargePlan,
            border:false,
            layout: 'fit',
            width: 500,
            height: 405,
            closeAction: 'close',
            plain: true,
            items: [f],
            manager: winManager,
            modal: true,
            fbar:[{
                xtype: 'button',
                text: EzDesk.resalers.Locale.Close
                ,scope:this
                ,handler:function(){
            		this.dialog.close();
            	}
            },{
            	xtype: 'button',
                text: EzDesk.resalers.Locale.Save
                ,scope:f
                ,handler:function(){
            		this.save();
            	}
            }]
        });
    }
    
	f.getForm().load({
		url:connect,
		waitMsg:'Loading...',
		params: {
		  	method : 'get_resaler_charge_plan',
	        moduleId: moduleId,
	        agent_id: data.data.AgentID
	    },
	    failure: function(form, action){
	        Ext.MessageBox.alert(lang_tr.Warning, lang_tr.ConnectServerError);
	    },
	    success: function(form, action ){
	    	var obj = Ext.util.JSON.decode(action.response.responseText);
	    	Ext.getCmp('product_type_prefix').setValue(obj.data.product_type_prefix);
	    	Ext.getCmp('chargeBAgentCS').setValue(obj.data.agent_cs);
	    	Ext.getCmp('chargeBUserCS').setValue(obj.data.call_cs);
	    	Ext.getCmp('chargeBCurrencyType').setValue(obj.data.currency_type);
	    }
	});
    this.dialog.show();
};


EzDesk.AgentChargePlanFormUi = Ext.extend(Ext.form.FormPanel, {
    labelWidth: 100,
    labelAlign: 'left',
    layout: 'form',
    border: false,
    frame: true,
    initComponent: function(){
		var connect = this.connect;
	    var moduleId = this.moduleId;
	    var desktop = this.desktop;
	    var agent_id = this.agent_id;
        this.items = [/*{
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.AgentID,
            anchor: '100%',
            name: 'agent_id',
			disabled  :true ,
            allowBlank: false
        },*/{
        	xtype: 'BProductType',
        	id: 'product_type_prefix',
            anchor: '90%'
        },{
        	xtype: 'BUserCSType',
        	id: 'chargeBUserCS',
        	name: 'user_cs',
        	anchor: '90%',
        	fieldLabel: EzDesk.resalers.Locale.ChargeScheme,	
        	connection: this.connect,
            moduleId: this.moduleId,
            desktop: this.desktop
        },{
        	xtype: 'BAgentCSType',
        	id: 'chargeBAgentCS',
        	name: 'agent_cs',
        	anchor: '90%',
        	fieldLabel: EzDesk.resalers.Locale.Default_AgentCS,	
        	connection: this.connect,
            moduleId: this.moduleId,
            desktop: this.desktop
        },
        {
        	xtype: 'textfield',
        	fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.InitializeBalance,
        	name: 'balance',
            anchor: '90%'
        },
        {
        	xtype: 'BCurrencyTypeCombo',
        	name: 'currency_type',
        	id:'chargeBCurrencyType',
            anchor: '90%'
        },
        {
        	xtype: 'textfield',
        	fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.FreeTime,
        	name: 'free_period',
            anchor: '90%'
        },
        {
        	xtype: 'textfield',
        	fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.ValidDateNo,
        	name: 'valid_date_no',
             anchor: '90%'
        },
        {
        	xtype: 'textfield',
        	fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.HireTime,
        	name: 'hire_number',
             anchor: '90%'
        }];
        this.save = function(){
    		this.getForm().submit({
                url: connect,
                waitMsg: 'Loading',
                method: 'POST',
                params: {
            		method: 'edit_resaler_charge_plan',
                    moduleId: moduleId,
                    agent_id: agent_id
                },
                success: function(addUserForm, action){
                    var obj = Ext.util.JSON.decode(action.response.responseText);
                    EzDesk.showMsg(EzDesk.resalers.Locale.EditChargePlan, obj.message, desktop);
                },
                failure: function(addUserForm, action){
                    //bbtn.setDisabled(false);
                    obj = Ext.util.JSON.decode(action.response.responseText);
                    if (action.failureType == 'server') {
                        EzDesk.showMsg(EzDesk.resalers.Locale.EditChargePlan, obj.message, desktop);
                    }
                    else {
                        EzDesk.showMsg(EzDesk.resalers.Locale.EditChargePlan, obj.message, desktop);
                    }
                }
            });
        };
        EzDesk.AgentChargePlanFormUi.superclass.initComponent.call(this);
    }
});

EzDesk.editAgentOEMDialog = function(app, moduleId, grid, data){
    var desktop = app.getDesktop();
    var winManager = desktop.getManager();
    var connect = app.connection;
   	
    /*var close = function(){
        this.dialog.hide();
    };*/
    
    var f = new EzDesk.AgentOEMInfoFormUi({
    	id: 'editAgentOEMInfoForm',
    	connect: connect,
        moduleId: moduleId,
        desktop: desktop,
        agent_id: data.data.AgentID
    });
    if (!this.dialog) {
        this.dialog = new Ext.Window({
            title: EzDesk.resalers.Locale.EditOEM,
            bodyStyle: 'padding:0px',
            border:false,
            layout: 'fit',
            width: 500,
            height: 405,
            closeAction: 'close',
            plain: true,
            items: [f],
            manager: winManager,
            modal: true,
            fbar:[{
                xtype: 'button',
                text: EzDesk.resalers.Locale.Close
                ,scope:this
                ,handler:function(){
            		this.dialog.close();
            	}
            },{
            	xtype: 'button',
                text: EzDesk.resalers.Locale.Save
                ,scope:f
                ,handler:function(){
            		this.save();
            	}
            }]
        });
    }
    
	f.getForm().load({
		url:connect,
		waitMsg:'Loading...',
		params: {
		  	method : 'get_resaler_oem_info',
	        moduleId: moduleId,
	        agent_id: data.data.AgentID
	    },
	    failure: function(response, options){
	        Ext.MessageBox.alert(lang_tr.Warning, lang_tr.ConnectServerError);
	    },
	    success: function(result, request ){
	    	var obj = Ext.util.JSON.decode(result.responseText);
	    	//EzDesk.showMsg(EzDesk.resalers.Locale.AddAgent, obj.message, desktop);
	    }
	});
    this.dialog.show();
};


EzDesk.AgentOEMInfoFormUi = Ext.extend(Ext.form.FormPanel, {
    labelWidth: 100,
    labelAlign: 'left',
    border:false,
    layout: 'form',
    //padding: 5,
    frame: true,
    initComponent: function(){
		var connect = this.connect;
	    var moduleId = this.moduleId;
	    var desktop = this.desktop;
	    var agent_id = this.agent_id;
        this.items = [{
            // Use the default, automatic layout to distribute the controls evenly
            // across a single row
            xtype: 'checkboxgroup',
            fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.RechargeMode,
            name: 'recharge_mode',
            anchor: '95%',
            items: [
                {boxLabel: EzDesk.resalers.Locale.AddAgentWizForm.Eload, name: 'eload'},
                {boxLabel: EzDesk.resalers.Locale.AddAgentWizForm.CMCC, name: 'cmcc'},
                {boxLabel: EzDesk.resalers.Locale.AddAgentWizForm.Unicom, name: 'unicom'},
                {boxLabel: EzDesk.resalers.Locale.AddAgentWizForm.CTC, name: 'CTC'}
            ]
        },{
            // Use the default, automatic layout to distribute the controls evenly
            // across a single row
            xtype: 'checkboxgroup',
            fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.SystemSet,
            name: 'recharge_mode',
            anchor: '95%',
            items: [{
            	boxLabel: EzDesk.resalers.Locale.AddAgentWizForm.MLM, 
            	name: 'mlm'
            },
                {boxLabel: EzDesk.resalers.Locale.AddAgentWizForm.HTTP, name: 'http'},
                {boxLabel: EzDesk.resalers.Locale.AddAgentWizForm.FollowMe, name: 'follow_me'}
            ]
        },{
            // Use the default, automatic layout to distribute the controls evenly
            // across a single row
            xtype: 'checkboxgroup',
            fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.CallMethod,
            name: 'recharge_mode',
            anchor: '95%',
            items: [
                {
                	boxLabel: EzDesk.resalers.Locale.AddAgentWizForm.IP, 
                	name: 'ip'
                },
                {boxLabel: EzDesk.resalers.Locale.AddAgentWizForm.SMS, name: 'sms'},
                {boxLabel: EzDesk.resalers.Locale.AddAgentWizForm.GPRS, name: 'gprs'}
                ,{boxLabel: EzDesk.resalers.Locale.AddAgentWizForm.ECallback, name: 'ecallback'}
            ]
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.OEMName,
            name: 'oem_name',
            anchor: '95%',
            value: '0'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.ServiceNum,
            name: 'service_num',
            anchor: '95%',
            value: '0'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.IVRNum,
            name: 'ivr_num',
            anchor: '95%',
            value: '0'
        },{
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.DTMFNum,
            id: 'dtmf_num',
            name: 'dtmf_num',
            //disabled: true,
            anchor: '95%',
            value: '0'
        },{
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.MLMURL,
            id: 'mlm_url',
            name: 'mlm_url',
            //disabled: true,
            anchor: '95%',
            value: ''
        }];
        this.save = function(){
    		this.getForm().submit({
                url: connect,
                waitMsg: 'Loading',
                method: 'POST',
                params: {
            		method: 'edit_resaler_oem_info',
                    moduleId: moduleId,
                    agent_id: agent_id
                },
                success: function(addUserForm, action){
                    var obj = Ext.util.JSON.decode(action.response.responseText);
                    EzDesk.showMsg(EzDesk.resalers.Locale.EditChargePlan, obj.message, desktop);
                },
                failure: function(addUserForm, action){
                    //bbtn.setDisabled(false);
                    obj = Ext.util.JSON.decode(action.response.responseText);
                    if (action.failureType == 'server') {
                        EzDesk.showMsg(EzDesk.resalers.Locale.EditChargePlan, obj.message, desktop);
                    }
                    else {
                        EzDesk.showMsg(EzDesk.resalers.Locale.EditChargePlan, obj.message, desktop);
                    }
                }
            });
        };
        EzDesk.AgentOEMInfoFormUi.superclass.initComponent.call(this);
    }
});



EzDesk.editAgentConfigDialog = function(app, moduleId, grid, data){
    var desktop = app.getDesktop();
    var winManager = desktop.getManager();
    var connect = app.connection;
   	
    var colse = function(){
        this.dialog.hide();
    };
    
    if (!this.dialog) {
        this.dialog = new Ext.Window({
            title: EzDesk.resalers.Locale.EditConfig,
            bodyStyle: 'padding:10px',
            layout: 'fit',
            width: 500,
            height: 405,
            closeAction: 'close',
            plain: true,
            items: [
                new EzDesk.AgentConfigFormUi({
                	id: 'editAgentConfigForm',
                	connect: connect,
                    moduleId: moduleId,
                    desktop: desktop
                })
            ],
            manager: winManager,
            modal: true
        });
    }
    
	Ext.getCmp('editAgentConfigForm').getForm().load({
		url:connect,
		waitMsg:'Loading...',
		params: {
		  	method : 'get_resaler_config',
	        moduleId: moduleId,
	        agent_id: data.data.AgentID
	    },
	    failure: function(response, options){
	        Ext.MessageBox.alert(lang_tr.Warning, lang_tr.ConnectServerError);
	    },
	    success: function(result, request ){
	    	var obj = Ext.util.JSON.decode(result.responseText);
	    	//EzDesk.showMsg(EzDesk.resalers.Locale.AddAgent, obj.message, desktop);
	    }
	});
    this.dialog.show();
};

EzDesk.AgentConfigFormUi = Ext.extend(Ext.form.FormPanel, {
    labelWidth: 75,
    labelAlign: 'left',
    layout: 'form',
    padding: 10,
    frame: true,
    initComponent: function(){
		var connect = this.connect;
	    var moduleId = this.moduleId;
	    var desktop = this.desktop;
	    var agent_id = this.agent_id;
        this.items = [{
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.AgentID,
            anchor: '95%',
            name: 'AgentID',
			//disabled  :true ,
            readOnly:true,
            allowBlank: false
        },{
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.invite_url,
            id: 'invite_url',
            name: 'invite_url',
            anchor: '95%'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.query_url,
            id: 'query_url',
            name: 'query_url',
            anchor: '95%'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.recharge_url,
            id: 'recharge_url',
            name: 'recharge_url',
            anchor: '95%'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.action_url,
            id: 'action_url',
            name: 'action_url',
            anchor: '95%'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.active_url,
            id: 'active_url',
            name: 'active_url',
            anchor: '95%'
        },{
            xtype: 'checkbox',
            fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.encrypt,
            anchor: '95%',
            id: 'encrypt',
            name: 'encrypt'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.serect,
            name: 'serect',
            id: 'serect',
            anchor: '95%'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.version,
            name: 'version',
            id: 'version',
            anchor: '95%'
        }, {
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.VID,
            anchor: '95%',
            name: 'VID'
        },{
            xtype: 'textfield',
            fieldLabel: EzDesk.resalers.Locale.PID,
            anchor: '95%',
            name: 'PID'
        }];
        this.fbar = {
            xtype: 'toolbar',
            items: [{
                xtype: 'button',
                text: EzDesk.resalers.Locale.Close
            }, {
                xtype: 'button',
                text: EzDesk.resalers.Locale.Save,
                scope:this,
                handler: function(){
	        		this.getForm().submit({
	                    url: this.connect,
	                    waitMsg: 'Loading',
	                    method: 'POST',
	                    params: {
	                		method: 'edit_resaler_oem_info',
	                        moduleId: this.moduleId,
	                        agent_id: this.agent_id
	                    },
	                    success: function(addUserForm, action){
	                        var obj = Ext.util.JSON.decode(action.response.responseText);
	                        EzDesk.showMsg(EzDesk.resalers.Locale.EditChargePlan, obj.message, this.desktop);
	                    },
	                    failure: function(addUserForm, action){
	                        //bbtn.setDisabled(false);
	                        obj = Ext.util.JSON.decode(action.response.responseText);
	                        if (action.failureType == 'server') {
	                            EzDesk.showMsg(EzDesk.resalers.Locale.EditChargePlan, obj.message, this.desktop);
	                        }
	                        else {
	                            EzDesk.showMsg(EzDesk.resalers.Locale.EditChargePlan, obj.message, this.desktop);
	                        }
	                    }
	                });
                }
            }]
        };
        EzDesk.AgentConfigFormUi.superclass.initComponent.call(this);
    }
});



EzDesk.addAgentWizForm = Ext.extend(Ext.ux.Wiz, {
    constructor: function(config){
        config = config || {};
        this.ownerModule = config.ownerModule;
		var v_carrier_id;
		var v_agent_id;
		var connect =  this.ownerModule.app.connection;
		var desktop =  this.ownerModule.app.getDesktop();
		var moduleId =  this.ownerModule.id;
		
		var addAgentTree = new Ext.ux.tree.RemoteTreePanel({
			 id:'remotetree111'
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
				 url: connect
				,preloadChildren:true
				,baseParams:{
					method: 'get_resaler_tree'
					,moduleId: moduleId 
					,treeTable: 'tree'
					,treeID: 1
				}
			}
		});
		
		addAgentTree.on('click',function(node){//单击树的一个节点 grid显示该节点的单位信息
			v_agent_id = node.id;
			Ext.getCmp('resalerSuperiorAgnet').setValue(node.text);
		});   
			
		var coin_type = new Ext.form.ComboBox({
            name: 'currency_type',
            store: new Ext.data.SimpleStore({
                fields: ['value', 'text'],
                data: [[1, 'CNY'], [2, 'USD'], [3, 'PTS'], [4, 'TWD'], [5, 'HKD']]
            }),
            valueField: 'value',
            fieldLabel: this.ownerModule.locale.Currency,
            displayField: 'text',
            editable: false,
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            allowBlank: false,
            //blankText: this.ownerModule.locale.Stock.BlankText,
            emptyText: this.ownerModule.locale.QueryTypeText,
            selectOnFocus: false,
            forceSelection: true,
            width: 150
        });
       
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
            title: this.ownerModule.locale.AddAgentTitle,
            headerConfig: {
                title: this.ownerModule.locale.AddAgentTitle
            },
            cardPanelConfig: {
                defaults: {
                    baseCls: 'x-small-editor',
                    border: false,
                    bodyStyle: 'padding:10px 10px 10px 60px;background-color:#F6F6F6;'
                }
            },
            cards: [new Ext.ux.Wiz.Card({
                title: this.ownerModule.locale.AddAgentWizForm.Welcome,
                id: 'mawc_welcome',
                monitorValid: false,
                defaults: {
                    labelStyle: 'font-size:12px'
                },
                items: [{
                    border: false,
                    bodyStyle: 'background:none;padding-bottom:10px;',
                    html: this.ownerModule.locale.f_page
                }]
            }), new Ext.ux.Wiz.Card({
                title: this.ownerModule.locale.AddAgentWizForm.AgentBinfoTitle,
                id: 'base_info',
                monitorValid: true,
                defaults: {
                    labelStyle: 'font-size:12px'
                },
                items: [{
                    border: false,
                    bodyStyle: 'background:none;padding-bottom:10px;',
                    html: this.ownerModule.locale.s_page
                }, {
	                xtype: 'textfield',
	                fieldLabel: EzDesk.resalers.Locale.Caption,
	                anchor: '90%',
	                name: 'Caption',
	                allowBlank: false
	            },{
	            	xtype: 'BUserCSType',
	            	id: 'raslaerBUserCSType',
	            	name: 'raslaer_user_cs',
	            	allowBlank: false,
	            	width: 302,
	            	fieldLabel: EzDesk.resalers.Locale.ChargeScheme,	
	            	connection: connect,
	                moduleId: moduleId,
	                desktop: desktop
	            },{
	            	xtype: 'BAgentCSType',
	            	id: 'raslaerBAgentCS',
	            	name: 'raslaer_agent_cs',
	            	allowBlank: false,
	            	width: 302,
	            	fieldLabel: EzDesk.resalers.Locale.Default_AgentCS,	
	            	connection: connect,
	                moduleId: moduleId,
	                desktop: desktop
	            },
                {
                	xtype: 'BCurrencyTypeCombo',
                	allowBlank: false,
                	width: 302
                }
				,{
	                xtype: 'checkbox',
	                fieldLabel: EzDesk.resalers.Locale.IsReal,
	                anchor: '90%',
	                name: 'IsReal',
	                inputValue: '1'
	            }, {
	                xtype: 'textfield',
	                fieldLabel: EzDesk.resalers.Locale.EMail,
	                anchor: '90%',
	                allowBlank: false,
	                name: 'EMail'
	            }, {
	                xtype: 'textfield',
	                fieldLabel: EzDesk.resalers.Locale.Address,
	                anchor: '90%',
	                name: 'Address'
	            }, {
	                xtype: 'textfield',
	                fieldLabel: EzDesk.resalers.Locale.Prefix,
	                anchor: '90%',
	                allowBlank: false,
	                name: 'Prefix'
	            }, {
	                xtype: 'textfield',
	                fieldLabel: EzDesk.resalers.Locale.Note,
	                anchor: '90%',
	                allowBlank: false,
	                name: 'Note'
	            }]
            }), new Ext.ux.Wiz.Card({
                title: this.ownerModule.locale.AddAgentWizForm.ChoiceGroupID,
                id: 'group_id',
                monitorValid: true,
                defaults: {
                    labelStyle: 'font-size:12px'
                },
                items: [{
                    border: false,
                    bodyStyle: 'background:none;padding-bottom:10px;padding-top:10px;',
                    html: this.ownerModule.locale.t_page
                },{
	                xtype: 'textfield',
	                fieldLabel: EzDesk.resalers.Locale.SuperiorAgnet,
	                anchor: '95%',
	                allowBlank: false,
	                id: 'resalerSuperiorAgnet',
	                name: 'resalerSuperiorAgnet'
	            }, {
	            	xtype: 'panel',
	                name: 'SuperiorAgnet',
	                anchor: '95%',
	                layout: 'fit',
	                height: 150,
	                items: [
	                   addAgentTree
	              ]
	            }, {
                    xtype: 'textfield',
                    fieldLabel: EzDesk.resalers.Locale.VID,
                    anchor: '95%',
                    name: 'VID'
                },{
                    xtype: 'textfield',
                    fieldLabel: EzDesk.resalers.Locale.PID,
                    anchor: '95%',
                    name: 'PID'
                }]
            }), new Ext.ux.Wiz.Card({
                title: this.ownerModule.locale.AddAgentWizForm.ChargePlanTitle,
                id: 'charge_info',
                monitorValid: true,
                defaults: {
                    labelStyle: 'font-size:12px'
                },
                //OPTIONS=1,oem-name=DIRcall,service-num=0019148858722,=10070,=1, ,dtmf_num=81969800||%s
                items: [{
                    border: false,
                    bodyStyle: 'background:none;padding-bottom:10px;padding-top:10px;',
                    html: this.ownerModule.locale.t_page
                },{
                	xtype:'fieldset',
	                checkboxToggle:true,
	                title: this.ownerModule.locale.AddAgentWizForm.IsChargePlanTitle,
	                autoHeight:true,
	                defaults: {width: 210},
	                defaultType: 'textfield',
	                anchor: '95%',
	                collapsed: true,
	                listeners: {
                    	expand: function(p) {
	                		Ext.getCmp('is_charge_plan').setValue('1');
                        },
                        collapse: function(p) {
	                		Ext.getCmp('is_charge_plan').setValue('0');
                        }
                    },
	                items :[{
                        xtype: "hidden",
                        name: "is_charge_plan",
                        id:"is_charge_plan"
                    }, {
	                	xtype: 'BProductType',
	 	                anchor: '90%'
	                },{
		            	xtype: 'BUserCSType',
		            	id: 'chargeBUserCSType',
		            	name: 'charge_user_cs',
		            	anchor: '90%',
		            	fieldLabel: EzDesk.resalers.Locale.ChargeScheme,	
		            	connection: connect,
		                moduleId: moduleId,
		                desktop: desktop
		            },{
		            	xtype: 'BAgentCSType',
		            	id: 'chargeBAgentCS',
		            	name: 'charge_agent_cs',
		            	anchor: '90%',
		            	fieldLabel: EzDesk.resalers.Locale.Default_AgentCS,	
		            	connection: connect,
		                moduleId: moduleId,
		                desktop: desktop
		            },
	                {
	                	xtype: 'textfield',
	                	fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.InitializeBalance,
	                	name: 'initialize_balance',
	                	value: '0',
	 	                anchor: '90%'
	                },
	                {
	                	xtype: 'BCurrencyTypeCombo',
	 	                anchor: '90%'
	                },
	                {
	                	xtype: 'textfield',
	                	fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.FreeTime,
	                	name: 'free_time',
	                	value: '0',
	 	                anchor: '90%'
	                },
	                {
	                	xtype: 'textfield',
	                	fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.ValidDateNo,
	                	name: 'valid_date_no',
	                	value: '0',
	 	                anchor: '90%'
	                },
	                {
	                	xtype: 'textfield',
	                	fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.HireTime,
	                	name: 'hire_time',
	                	value: '0',
	 	                anchor: '90%'
	                }]
                }]
            }), new Ext.ux.Wiz.Card({
                title: this.ownerModule.locale.AddAgentWizForm.ParametersTitle,
                id: 'ome_info',
                monitorValid: true,
                defaults: {
                    labelStyle: 'font-size:12px'
                },
                //OPTIONS=1,oem-name=DIRcall,service-num=0019148858722,=10070,=1, ,dtmf_num=81969800||%s
                items: [{
                    border: false,
                    bodyStyle: 'background:none;padding-bottom:10px;padding-top:10px;',
                    html: this.ownerModule.locale.t_page
                }, {
                    xtype:'fieldset',
                    checkboxToggle:true,
                    title: this.ownerModule.locale.AddAgentWizForm.isOEMTitle,
                    autoHeight:true,
                    defaults: {width: 210},
                    defaultType: 'textfield',
                    anchor: '95%',
                    collapsed: true,
                    listeners: {
                    	expand: function(p) {
	                		Ext.getCmp('is_ome').setValue('1');
                        },
                        collapse: function(p) {
	                		Ext.getCmp('is_ome').setValue('0');
                        }
                    },
                    items :[{
                        xtype: "hidden",
                        name: "is_ome",
                        id:"is_ome"
                    },{
                        // Use the default, automatic layout to distribute the controls evenly
                        // across a single row
                        xtype: 'checkboxgroup',
                        fieldLabel: this.ownerModule.locale.AddAgentWizForm.RechargeMode,
                        name: 'recharge_mode',
                        anchor: '95%',
                        items: [
                            {boxLabel: this.ownerModule.locale.AddAgentWizForm.Eload, name: 'eload'},
                            {boxLabel: this.ownerModule.locale.AddAgentWizForm.CMCC, name: 'cmcc'},
                            {boxLabel: this.ownerModule.locale.AddAgentWizForm.Unicom, name: 'unicom'},
                            {boxLabel: this.ownerModule.locale.AddAgentWizForm.CTC, name: 'CTC'}
                        ]
                    },{
                        // Use the default, automatic layout to distribute the controls evenly
                        // across a single row
                        xtype: 'checkboxgroup',
                        fieldLabel: this.ownerModule.locale.AddAgentWizForm.SystemSet,
                        name: 'recharge_mode',
                        anchor: '95%',
                        items: [{
                        	boxLabel: this.ownerModule.locale.AddAgentWizForm.MLM, 
                        	name: 'mlm',
                        	listeners: {
	                            'check': {
	                                fn: function(){
                        				Ext.getCmp('mlm_url').enable();
                        			},
	                                scope: this
	                            }
                        	}
                        },
                            {boxLabel: this.ownerModule.locale.AddAgentWizForm.HTTP, name: 'http'},
                            {boxLabel: this.ownerModule.locale.AddAgentWizForm.FollowMe, name: 'follow_me'}
                        ]
                    },{
                        // Use the default, automatic layout to distribute the controls evenly
                        // across a single row
                        xtype: 'checkboxgroup',
                        fieldLabel: this.ownerModule.locale.AddAgentWizForm.CallMethod,
                        name: 'recharge_mode',
                        anchor: '95%',
                        items: [
                            {
                            	boxLabel: this.ownerModule.locale.AddAgentWizForm.IP, 
                            	name: 'ip',
                            	listeners: {
    	                            'check': {
    	                                fn: function(){
                            				Ext.getCmp('dtmf_num').enable();
                            			},
    	                                scope: this
    	                            }
                            	}
                            },
                            {boxLabel: this.ownerModule.locale.AddAgentWizForm.SMS, name: 'sms'},
                            {boxLabel: this.ownerModule.locale.AddAgentWizForm.GPRS, name: 'gprs'}
                        ]
                    }, {
                        xtype: 'textfield',
                        fieldLabel: this.ownerModule.locale.AddAgentWizForm.OEMName,
                        name: 'oem_name',
                        anchor: '95%',
                        value: 'M-Dial'
                    }, {
                        xtype: 'textfield',
                        fieldLabel: this.ownerModule.locale.AddAgentWizForm.ServiceNum,
                        name: 'service_num',
                        anchor: '95%',
                        value: ''
                    }, {
                        xtype: 'textfield',
                        fieldLabel: this.ownerModule.locale.AddAgentWizForm.IVRNum,
                        name: 'ivr_num',
                        anchor: '95%',
                        value: '10070'
                    },{
                        xtype: 'textfield',
                        fieldLabel: this.ownerModule.locale.AddAgentWizForm.DTMFNum,
                        id: 'dtmf_num',
                        name: 'dtmf_num',
                        disabled: true,
                        anchor: '95%',
                        value: ''
                    },{
                        xtype: 'textfield',
                        fieldLabel: this.ownerModule.locale.AddAgentWizForm.MLMURL,
                        id: 'mlm_url',
                        name: 'mlm_url',
                        disabled: true,
                        anchor: '95%',
                        value: '0'
                    }]
                }]
            }), new Ext.ux.Wiz.Card({
                title: this.ownerModule.locale.AddAgentWizForm.ConfigInfoTitle,
                id: 'config_info',
                monitorValid: true,
                defaults: {
                    labelStyle: 'font-size:12px'
                },
                //OPTIONS=1,oem-name=DIRcall,service-num=0019148858722,=10070,=1, ,dtmf_num=81969800||%s
                items: [{
                    border: false,
                    bodyStyle: 'background:none;padding-bottom:10px;padding-top:10px;',
                    html: this.ownerModule.locale.t_page
                }, {
                    xtype:'fieldset',
                    checkboxToggle:true,
                    title: this.ownerModule.locale.AddAgentWizForm.IsConfigTitle,
                    autoHeight:true,
                    defaults: {width: 210},
                    defaultType: 'textfield',
                    anchor: '95%',
                    collapsed: true,
                    listeners: {
                    	expand: function(p) {
                    		Ext.getCmp('is_config').setValue('1');
                    		Ext.Ajax.request({
                    	        url: connect,
                    	        params: {
                                    method : 'get_resaler_config',
                                    moduleId: moduleId,
                                    agent_id: v_agent_id
                                },
                    	        failure: function(response, options){
                    	            Ext.MessageBox.alert(lang_tr.Warning, lang_tr.ConnectServerError);
                    	        },
                    	        success: function(result, request ){
                    	        	var obj = Ext.util.JSON.decode(result.responseText);
                    	        	Ext.getCmp('invite_url').setValue(obj.data.invite_url);
                    	        	Ext.getCmp('query_url').setValue(obj.data.query_url);
                    	        	Ext.getCmp('recharge_url').setValue(obj.data.recharge_url);
                    	        	Ext.getCmp('action_url').setValue(obj.data.action_url);
                    	        	Ext.getCmp('active_url').setValue(obj.data.active_url);
                    	        	Ext.getCmp('encrypt').setValue(obj.data.encrypt);
                    	        	Ext.getCmp('serect').setValue(obj.data.serect);
                    	        	Ext.getCmp('version').setValue(obj.data.version);
                    	        },
                    	        scope: config.ownerModule
                    	    });
                        },
                        collapse: function(p) {
                    		Ext.getCmp('is_config').setValue('0');
                        }
                    },
                    items :[{
                        xtype: "hidden",
                        name: "is_config",
                        id:"is_config"
                    },{
                        xtype: 'textfield',
                        fieldLabel: this.ownerModule.locale.AddAgentWizForm.invite_url,
                        id: 'invite_url',
                        name: 'invite_url',
                        anchor: '95%'
                    }, {
                        xtype: 'textfield',
                        fieldLabel: this.ownerModule.locale.AddAgentWizForm.query_url,
                        id: 'query_url',
                        name: 'query_url',
                        anchor: '95%'
                    }, {
                        xtype: 'textfield',
                        fieldLabel: this.ownerModule.locale.AddAgentWizForm.recharge_url,
                        id: 'recharge_url',
                        name: 'recharge_url',
                        anchor: '95%'
                    }, {
                        xtype: 'textfield',
                        fieldLabel: this.ownerModule.locale.AddAgentWizForm.action_url,
                        id: 'action_url',
                        name: 'action_url',
                        anchor: '95%'
                    }, {
                        xtype: 'textfield',
                        fieldLabel: this.ownerModule.locale.AddAgentWizForm.active_url,
                        id: 'active_url',
                        name: 'active_url',
                        anchor: '95%'
                    },{
    	                xtype: 'checkbox',
    	                fieldLabel: EzDesk.resalers.Locale.AddAgentWizForm.encrypt,
    	                anchor: '90%',
    	                id: 'encrypt',
    	                name: 'encrypt'
    	            }, {
                        xtype: 'textfield',
                        fieldLabel: this.ownerModule.locale.AddAgentWizForm.serect,
                        name: 'serect',
                        id: 'serect',
                        anchor: '95%'
                    }, {
                        xtype: 'textfield',
                        fieldLabel: this.ownerModule.locale.AddAgentWizForm.version,
                        name: 'version',
                        id: 'version',
                        anchor: '95%'
                    }]
                }]
            })
            ]
        });
        EzDesk.addAgentWizForm.superclass.constructor.call(this, config);
        this.on({
            'finish': { 
                fn: function(wiz, data){
		        	Ext.Ajax.request({
		    	        url: connect,
		    	        params: {
		                    method : 'add_resaler_info',
		                    moduleId: moduleId,
		                    agent_id: v_agent_id,
		                    data: Ext.util.JSON.encode(data)
		                },
		    	        failure: function(response, options){
		    	            Ext.MessageBox.alert(lang_tr.Warning, lang_tr.ConnectServerError);
		    	        },
		    	        success: function(result, request ){
		    	        	var obj = Ext.util.JSON.decode(result.responseText);
		    	        	EzDesk.showMsg(EzDesk.resalers.Locale.AddAgent, obj.message, desktop);
		    	        },
		    	        scope: this.ownerModule
		    	    });
//        			send_ajax({
//                		waitMsg: 'Importing...'
//                		,params: {
//                            method: "import_devices",
//                            ，moduleId: this.ownerModule.id,
//                            ，
//                            , domain: oem_domain
//    						, resaler :os_resaler
//                        }
//        				,success:function(s,x){
//        					Ext.getCmp('resalers_panel').store.reload();
//        				}
//                	});
                },
                scope: this
            }
        });
    }
    
});
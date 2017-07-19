Ext.namespace('EzDesk.um');


EzDesk.um.home_panel = Ext.extend(EzDesk.BillingPanel,{
	initComponent: function() {
		var account = this.account;
		Ext.apply(this,{
			border: false
			,layout: 'fit'
			,padding : '0px'
			//,frame: true
			,tbar:[{
			    xtype : 'button'
			    ,iconCls:'recharge-icon'
			    ,text : lang_um.bt_recharge
			    ,handler: function(){
					var	RechargeWindow = new  EzDesk.um.RechargeWindow({
						title : lang_um.fd_recharge_title,
						id: 'recharge_window',
						account:account
					});
					RechargeWindow.show();
				}
			},'-',{
				xtype : 'button'
				,text : lang_um.bt_service_list
				,iconCls:'customer-server-icon'
				,handler: function(){
					var	ServiceWindow = new  EzDesk.um.ServiceWindow({
						title : lang_um.gp_server_title,
						id: 'service_window'
					});
					ServiceWindow.show();
				}
			},'->',{
				xtype : 'button'
				,text : lang_um.bt_modify_account
			}]
	        ,items: [{
    			xtype : 'form'
    			,id : 'home_form'
    			,frame : true
    		    ,border : false
    			,padding : '0px'
    			,autoScroll : true
    			,labelAlign : 'right'
    			,labelWidth : 120
    			,items : [{
					xtype : 'displayfield'
    				,fieldLabel : lang_um.fd_userid
    				,name : 'E164'
    				,value : account.E164
    			},{
    				xtype : 'displayfield'
	    			,fieldLabel : lang_um.fd_name
	    			,name : 'GuestName'
	    			,value : account.GuestName
	    		}/*,{
    				xtype : 'displayfield'
    				,fieldLabel : lang_um.fd_resaler
    				,name : 'Caption'
    				,value : this.account.Caption
    			}*/,{
    				xtype : 'displayfield'
	    			,fieldLabel : lang_um.fd_caller
	    			,name : 'CallerNo'
	    			,value : account.CallerNo
    			},{
    				xtype : 'displayfield'
	    			,fieldLabel : lang_um.fd_status
	    			,name : 'Status'
	    			,value : account.Status
	    		},{
    				xtype : 'displayfield'
		    		,fieldLabel : lang_um.fd_balance
		    		,name : 'Balance'
		    		,value : account.Balance +' '+ account.CurrencyType
		    	},{
    				xtype : 'displayfield'
			    	,fieldLabel : lang_um.fd_billing
			    	,name : 'ChargeScheme'
			    	,value : account.ChargeScheme 
			    },{
    				xtype : 'displayfield'
			    	,fieldLabel : lang_um.fd_active_time
			    	,name : 'ActiveTime'
			    	,value : account.ActiveTime
			    },{
    				xtype : 'displayfield'
				    ,fieldLabel : lang_um.fd_register
				    ,name : 'FirstRegister'
				    ,value : account.FirstRegister
				},{
    				xtype : 'displayfield'
				    ,fieldLabel : lang_um.fd_first_call
				    ,name : 'FirstCall'
				    ,value : account.FirstCall
				},{
    				xtype : 'displayfield'
    				,fieldLabel : lang_um.fd_last_call
    				,name : 'LastCall'
				    ,value : account.LastCall
				}] 
    		}]
		});
		EzDesk.um.home_panel.superclass.initComponent.call(this);
	}
});
Ext.reg('home_panel', EzDesk.um.home_panel);

EzDesk.um.RechargeWindow = Ext.extend(Ext.Window,{
	closable : true
	,header : true
	//border : true,
	,width : 375
	,height : 210
	//bodyBorder : true
	,frame : true
	,padding : '0px'
	,initComponent: function() {
		Ext.apply(this,{
			layout : 'border'
		    ,items : [{
		    	xtype : 'tabpanel',
	    		activeTab: 0,
	    		region : 'center',
	            plain:true,
	            defaults:{autoScroll: true},
	            items:[{
                    title: lang_um.fd_recharge_card,
                    layout: 'fit',
                    items:[ new EzDesk.um.RechargeForm({
		    	    	id: 'umRecharge',
		    	    	name: 'umRecharge',
		    	    	data: this.account
		    	    })]
                },{
                    title: lang_um.fd_recharge_ebank,
                    layout: 'fit',
                    items:[ new EzDesk.um.EBankForm({
		    	    	id: 'umebankRecharge',
		    	    	name: 'umebankRecharge',
		    	    	data: this.account
		    	    })]
                }]
		    }]
		});
		EzDesk.um.RechargeWindow.superclass.initComponent.call(this);
		//this.active_card('card_home');
	},
	active_card:function(card_id){
		var contextPanel = Ext.getCmp('contextPanel');
		if(contextPanel) {
			var l = contextPanel.getLayout();
			if(l)
				l.setActiveItem(card_id);
		}
	}
	,listeners: {
		contextmenu: function(e){
		}
	}
});

EzDesk.um.RechargeForm = Ext.extend(Ext.form.FormPanel, {
    labelWidth: 75,
    labelAlign: 'left',
    layout: 'form',
    id: 'umRecharge',
   // padding: 10,
    frame: true,
    initComponent: function(){
		var data = this.data;
        this.items = [{
            xtype: 'textfield',
            fieldLabel: lang_um.fd_userid,
            anchor: '97%',
            value : data.E164,
            name: 'endpoint',
            disabled: true
        }, {
            xtype: 'textfield',
            fieldLabel: lang_um.fd_recharge_pin,
            anchor: '97%',
            name: 'pin'
        }, {
            xtype: 'textfield',
            fieldLabel: lang_um.fd_recharge_pwd,
            anchor: '97%',
            name: 'pwd'
        }];
        this.fbar = {
            xtype: 'toolbar',
            items: [{
                xtype: 'button',
                text: lang_um.Close
            }, {
                xtype: 'button',
                text: lang_um.Recharge,
                handler: function(){
         	   		Ext.getCmp('umRecharge').getForm().submit({
	         	   		url: os_service_url,
	                    waitMsg: '',
	                    params: {
	     	   				method: 'crm_recharge',
	     	   				moduleId: 'crm',
	     	   				um: 1
                    	},
                        success: function(addUserForm, action){
                     	   obj = Ext.util.JSON.decode(action.response.responseText);
                     	   Ext.MessageBox.alert('', obj.message); 
                     	},
                        failure: function(addUserForm, action){
                            obj = Ext.util.JSON.decode(action.response.responseText);
                            if (action.failureType == 'server') {
                            	Ext.MessageBox.alert('', obj.message);   
                            }
                            else {
                            	Ext.MessageBox.alert('', obj.message); 
                            }
                        }
                    });
                }
            }]
        };
        EzDesk.um.RechargeForm.superclass.initComponent.call(this);
    }
});

EzDesk.um.EBankForm = Ext.extend(Ext.form.FormPanel, {
    labelWidth: 75,
    labelAlign: 'left',
    layout: 'form',
    id: 'umebankRecharge',
   // padding: 10,
    frame: true,
    initComponent: function(){
		var data = this.data;
        this.items = [{
            xtype: 'textfield',
            fieldLabel: lang_um.fd_userid,
            anchor: '97%',
            value : data.E164,
            id: 'endpoint',
            name: 'endpoint',
            disabled: true
        }, {
            xtype: 'textfield',
            fieldLabel: lang_um.fd_recharge_value,
            anchor: '97%',
            id: 'value',
            name: 'value'
        }];
        this.fbar = {
            xtype: 'toolbar',
            items: [{
                xtype: 'button',
                text: lang_um.Close
            }, {
                xtype: 'button', 
                text: lang_um.Recharge,
                handler: function(){
            		var E164 = Ext.getCmp('endpoint').getValue();
            		var Value = Ext.getCmp('value').getValue();
            		msgWindow=window.open("recharge.html","displayWindow","menubar=yes");
                }
            }]
        };
        EzDesk.um.EBankForm.superclass.initComponent.call(this);
    }
});



EzDesk.um.ServiceWindow = Ext.extend(Ext.Window,{
	closable : true
	,header : true
	//border : true,
	,width : 450
	,height : 342
	//bodyBorder : true
	,frame : true
	,padding : '0px'
	,initComponent: function() {
		Ext.apply(this,{
			layout : 'border'
		    ,items : [
		    	new EzDesk.serviceListGrid({
		    		baseUrl : os_service_url,
            		moduleId : 'crm'
		    	})
		    ]
		});
		EzDesk.um.ServiceWindow.superclass.initComponent.call(this);
	}
});


/*
 *CRUD CRM面板基类
 */
EzDesk.CRMCrudPanel = Ext.extend(Ext.Panel, {
    closable: true,
    region : 'center',
    layout: "border",
    gridViewConfig: {},
    //链接
    linkRenderer: function(v){
        if (!v) 
            return "";
        else 
            return String.format("<a href='{0}' target='_blank'>{0}</a>", v);
    },
    //时间
    dateRender: function(format){
        format = format || "Y-m-d h:i";
        return Ext.util.Format.dateRenderer(format);
    },
    //刷新
    refresh: function(){
        this.store.removeAll();
        this.store.reload();
    },
    //初始化窗口（用于新增，修改时）,继承后在createWin中调用该方法显示窗口
    initWin: function(width, height, title){
		var win = new Ext.Window({
            width: width,
            height: height,
            buttonAlign: "center",
            title: title,
            modal: true,
            shadow: true,
            closeAction: "hide",
            items: [this.fp],
            buttons: [{
                text: "保存",
                handler: function(){
        			this.save(title);
        		},
                scope: this
            }, {
                text: "清空",
                handler: this.reset,
                scope: this
            }, {
                text: "关闭",
                handler: this.closeWin,
                scope: this
            }]
		});
    	
        return win;
    },
    //显示（新增/修改）窗口
    showWin: function(){ //createForm()需要在继承时提供，该方法作用是创建表单
        if (!this.win) {
            if (!this.fp) {
                this.fp = this.createForm();
            }
            this.win = this.createWin();
            this.win.on("close", function(){
                this.win = null;
                this.fp = null;
                this.store.reload();
            }, this);
        }
        //窗口关闭时，数据重新加载
        this.win.show();
    },
    //创建（新增/修改）窗口
    create: function(){
        this.showWin();
        this.reset();
    },
    //数据保存[（新增/修改）窗口]
    save: function(title){
        this.fp.form.submit({
        	 waitMsg: '正在保存... ...',
             waitTitle: '请稍等...',
             url: this.baseUrl,
             method: 'POST',
             success: function(form_instance_create, action){
         	 	var obj = Ext.util.JSON.decode(action.response.responseText);
         	 	Ext.Msg.alert(title, obj.message);
              	this.closeWin();
				this.store.reload();
             },
             failure: function(form_instance_create, action){
             	var obj = Ext.util.JSON.decode(action.response.responseText);
             	Ext.Msg.alert(title, obj.message);
             },
             scope: this
        });
    },
    //（新增/修改）窗口上的清空
    reset: function(){
        if (this.win) 
            this.fp.form.reset();
    },
    //（新增/修改）窗口上的关闭
    closeWin: function(){
        if (this.win) 
            this.win.close();
        this.win = null;
        this.fp = null;
        this.store.reload();
    },
    //修改，双击行，或选中一行点击修改，
    edit: function(action){
        if (this.grid.selModel.hasSelection()) {
            var records = this.grid.selModel.getSelections();//得到被选择的行的数组
            var recordsLen = records.length;//得到行数组的长度
            if (recordsLen > 1) {
                Ext.Msg.alert("系统提示信息", "请选择其中一项进行编辑！");
            }//一次只给编辑一行
            else {
                var record = this.grid.getSelectionModel().getSelected();//获取选择的记录集
                var id = record.get("id");
				this.showWin();
				this.fp.form.setValues({
					method: action
				});
                this.fp.form.loadRecord(record); //往表单（fp.form）加载数据
            }
        }
        else {
            Ext.Msg.alert("提示", "请先选择要编辑的行!");
        }
    },
    //删除,pid为主键值
    remove: function(pid, title, moduleId, action){
        var store = this.store;
        var baseUrl = this.baseUrl;
        var jsonStr;
        if (this.grid.selModel.hasSelection()) {
            var records = this.grid.selModel.getSelections();//得到被选择的行的数组
            var recordsLen = records.length;//得到行数组的长度
            for (var i = 0; i < recordsLen; i++) {
                var id = records[i].get(pid);
                if (i != 0) {
                    jsonStr += ',' + id;
                }
                else {
                    jsonStr = id;
                }
            }
        	var win = new Ext.Window({
                title: title,
                frame: true,
                maximizable: false,
                width: 300,
                height: 100,
                bodyStyle: 'text-align:center;word-break:break-all',
                buttonAlign: 'center',
                html: '<br/>' + lang_um.delete_html,
                buttons: [{
    	            text: lang_um.Close,
    	            handler: function(){
    	                this.ownerCt.ownerCt.close();
    	            }
    			},
    			{
    				text: lang_um.Delete,
    	            handler: function(){
	    				this.ownerCt.ownerCt.close();
		                Ext.Ajax.request({
							url: baseUrl,
							method: 'POST',
							params: {
		                		method:  action,
	                            moduleId: moduleId,
	                            jsonStr: jsonStr,
	                            um: 1
							},
							success: function(o){
								var obj = Ext.util.JSON.decode(o.responseText);
								Ext.Msg.alert(title, obj.message);
								store.reload();
							},
							failure: function(){
								
							}
						});
    				}
                }]
            });
            win.show();
        }
        else {
            Ext.Msg.alert("提示", "请先选择要删除的行!");
        }
    },
    sm: function(){
        var csm = new Ext.grid.CheckboxSelectionModel();
        return csm;
    },
    //初始化GRID面板
    initComponent: function(){
        EzDesk.CRMCrudPanel.superclass.initComponent.call(this);
		if(!this.grid){
			var viewConfig = Ext.apply({
            	forceFit: true
        		}, this.gridViewConfig
        	);
		
			this.cm.defaultSortable = true;
        	this.sm = new Ext.grid.CheckboxSelectionModel();
			this.grid = new Ext.grid.GridPanel({
	            store: this.store,
				region : 'center',
	            cm: this.cm,
	            sm: this.sm,
	            trackMouseOver: true,
	            loadMask: true,
	            stripeRows: true,
	            viewConfig: viewConfig,
	            tbar: [{
	                id: 'addButton',
	                text: '新增',
	                iconCls: 'addIconCss',
	                tooltip: '添加新纪录',
	                handler: this.create,
	                scope: this
	            }, '-', {
	                id: 'editButton',
	                text: '编辑',
	                iconCls: 'editIconCss',
	                tooltip: '修改记录',
	                handler: this.edit,
	                scope: this
	            }, '-', {
	                text: '删除',
	                iconCls: 'deleteIconCss',
	                tooltip: '删除所选中的信息',
	                handler: this.remove,
	                scope: this
	            }, '-', {
	                text: '刷新',
	                iconCls: 'refreshIcon',
	                tooltip: '刷新纪录',
	                handler: this.refresh,
	                scope: this
	            }, '   '],
	            bbar: new Ext.PagingToolbar({
	                pageSize: 20,
	                store: this.store,
	                displayInfo: true,
	                displayMsg: '显示第 {0} - {1} 条记录，共 {2}条记录',
	                emptyMsg: "没有记录"
	            })
	        });
		}
        
        //双击时执行修改
       	this.grid.on("celldblclick", this.edit, this);
        this.add(this.grid);
        this.store.load({
            params: {
                start: 0,
                limit: 20
            }
        });
    }
});


EzDesk.serviceListGrid = Ext.extend(EzDesk.CRMCrudPanel, {
    //id，需唯一
    id: "service_list_grid",
    //数据源
    baseUrl: "",
    //模块名  
    moduleId: "",
    desktop: "",
    store: "",
    //表单
    createForm: function(){
		var formPanel = new Ext.form.FormPanel({
			labelWidth: 75,
		    labelAlign: 'left',
		    layout: 'form',
		    id: 'addService',
		   // padding: 10,
		    frame: true,
		    items : [{
                xtype: "hidden",
                name: "moduleId",
                value: 'crm'
            },{
                xtype: "hidden",
                name: "method",
                value: "add_server_member"
            },{
                xtype: "hidden",
                name: "um",
                value: 1
            },{
                xtype: "hidden",
                name: "id"
            },{
	            xtype: 'textfield',
	            fieldLabel: lang_um.gp_customer_id,
	            anchor: '97%',
	            name: 'customer_id'
	        }, {
	            xtype: 'textfield',
	            fieldLabel: lang_um.gp_customer_name,
	            anchor: '97%',
	            name: 'customer_name'
	        }, {
	            xtype: 'textfield',
	            fieldLabel: lang_um.gp_pno,
	            anchor: '97%',
	            name: 'pno'
	        }]
		});
        return formPanel;
    },
	//查询
    search: function(){

    },
    //编辑
    editData: function(){
        this.edit("edit_server_member");
    },
    //删除
    removeData: function(){
    	this.remove('id', 'Delete', 'crm', 'delete_server_member');
    },
    //创建窗口
    createWin: function(){
        return this.initWin(400, 150, lang_um.fd_add_server);
    },
    storeMapping: ["id","customer_id", "pno", "customer_name"],
    initComponent: function(){
        var sm = new Ext.grid.CheckboxSelectionModel();
        this.cm = new Ext.grid.ColumnModel([new Ext.grid.RowNumberer(),//获得行号
 		sm, {
            header: 'id',
            sortable: true,
            hidden: true,
            width: 150,
            dataIndex: "id"
        }, {
            header: lang_um.gp_customer_id,
            sortable: true,
            width: 150,
            dataIndex: "customer_id"
        }, {
            header: lang_um.gp_pno,
            sortable: true,
            width: 300,
            dataIndex: "pno"
        }, {
            header: lang_um.gp_customer_name,
            sortable: true,
            width: 300,
            dataIndex: "customer_name"
        }]);
        
        this.store = new Ext.data.JsonStore({
        	id: "pno",
            url: this.baseUrl, //默认的数据源地址，继承时需要提供
            root: "data",
            totalProperty: "totalCount",
            remoteSort: true,
            method: 'POST',
            fields: this.storeMapping,
            baseParams :{
	    		method: 'get_server_member',
	            moduleId: 'crm',
	            um: 1
			}
        });
        
        this.cm.defaultSortable = true;
        this.sm = new Ext.grid.CheckboxSelectionModel();
		
        var viewConfig = Ext.apply({
            forceFit: true
        }, this.gridViewConfig);
		
        this.grid = new Ext.grid.GridPanel({
            store: this.store,
            id: 'crm_service_grid',
			height: 500,
			autoScroll: true,
            cm: this.cm,
            sm: sm,
            trackMouseOver: true,
            loadMask: true,
            viewConfig: viewConfig,
			region : 'center',
            tbar: [{
                id: 'addButton',
                text: '新增',
                iconCls: 'crm-add-icon',
                tooltip: '添加新纪录',
                handler: this.create,
                scope: this
            }, '-', {
                id: 'editButton',
                text: '编辑',
                iconCls: 'crm-edit-icon',
                tooltip: '修改记录',
                handler: this.editData,
                scope: this
            }, '-', {
                text: '删除',
                iconCls: 'crm-del-icon',
                tooltip: '删除所选中的信息',
                handler: this.removeData,
                scope: this
            }, '-', {
                text: '刷新',
                iconCls: 'crm-refresh-icon',
                tooltip: '刷新纪录',
                handler: this.refresh,
                scope: this
            }, '   '],
            bbar: new Ext.PagingToolbar({
                pageSize: 20,
                store: this.store,
                displayInfo: true
            })
        });
        EzDesk.serviceListGrid.superclass.initComponent.call(this);
    }
});

EzDesk.um.addServiceWindow = Ext.extend(Ext.Window,{
	closable : true
	,header : true
	//border : true,
	,width : 375
	,height : 210
	//bodyBorder : true
	,frame : true
	,padding : '0px'
	,initComponent: function() {
		Ext.apply(this,{
			layout : 'fit'
		    ,items : [
	            new EzDesk.um.addServiceForm({
	    	    	id: 'addService',
	    	    	name: 'addService'
	    	    })
		    ]
		});
		EzDesk.um.addServiceWindow.superclass.initComponent.call(this);
		//this.active_card('card_home');
	}
});

EzDesk.um.addServiceForm = Ext.extend(Ext.form.FormPanel, {
    labelWidth: 75,
    labelAlign: 'left',
    layout: 'form',
    id: 'addService',
   // padding: 10,
    frame: true,
    initComponent: function(){
        this.items = [{
            xtype: 'textfield',
            fieldLabel: lang_um.gp_customer_id,
            anchor: '97%',
            name: 'customer_id'
        }, {
            xtype: 'textfield',
            fieldLabel: lang_um.gp_customer_name,
            anchor: '97%',
            name: 'customer_name'
        }, {
            xtype: 'textfield',
            fieldLabel: lang_um.gp_pno,
            anchor: '97%',
            name: 'pno'
        }];
        EzDesk.um.addServiceForm.superclass.initComponent.call(this);
    }
});


EzDesk.customerGrid = Ext.extend(EzDesk.CRMCrudPanel, {
  //id，需唯一
	id: "customer_grid",
	//数据源
	baseUrl: "",
	//模块名  
	moduleId: "",
	desktop: "",
	store: "",
	//表单
	createForm: function(){
		var formPanel = new Ext.form.FormPanel({
			labelWidth: 75,
		    labelAlign: 'left',
		    layout: 'form',
		    id: 'add_customer',
		   // padding: 10,
		    frame: true,
		    items : [{
              xtype: "hidden",
              name: "moduleId",
              value: 'crm'
          },{
              xtype: "hidden",
              name: "method",
              value: "add_server_member"
          },{
              xtype: "hidden",
              name: "um",
              value: 1
          },{
              xtype: "hidden",
              name: "id"
          }, {
        	  xtype: 'textfield'
              ,id: 'name'
              ,fieldLabel: lang_um.fd_name
              ,anchor: '97%'
              ,name: 'name'
          },{
        	  xtype: 'textfield'
              ,id: 'sex'
              ,fieldLabel: lang_um.fd_sex
              ,anchor: '97%'
              ,dataIndex: 'sex'
          }, {
        	  xtype: 'textfield'
              ,id: 'phoneno'
              ,fieldLabel: lang_um.fd_phoneno
              ,anchor: '97%'
              ,name: 'phoneno'
          }, {
        	  xtype: 'textfield'
              ,id: 'company'
              ,fieldLabel: lang_um.fd_company
              ,anchor: '97%'
              ,name: 'company'
          }, {
        	  xtype: 'textfield'
              ,id: 'email'
              ,fieldLabel: lang_um.fd_email
              ,anchor: '97%'
              ,name: 'email'
          }, {
        	  xtype: 'textfield'
              ,id: 'office_no'
              ,fieldLabel: lang_um.fd_office_no
              ,anchor: '97%'
          }, {
        	  xtype: 'textfield'
              ,id: 'fax'
              ,fieldLabel: lang_um.fd_fax
              ,anchor: '97%'
          }, {
        	  xtype: 'textfield'
              ,id: 'alias'
              ,fieldLabel: lang_um.fd_alias
              ,anchor: '97%'
          }, {
        	  xtype: 'combo'
              ,id: 'group'
              ,fieldLabel: lang_um.fd_group
              ,anchor: '97%'
              ,name: 'cust_group'
              //store: store,
              //,displayField:'state'
              ,typeAhead: true
              ,mode: 'local'
              ,triggerAction: 'all'
              //,emptyText:'Select a state...'
              ,selectOnFocus:true
          }, {
        	  xtype: 'textfield'
              ,id: 'remark'
              ,fieldLabel: lang_um.fd_remark
              ,anchor: '97%'
              ,name: 'remark'
          }]
		});
      return formPanel;
  },
	//查询
  search: function(){
	  	this.grid.store.removeAll();
	  	var searchWindow = Ext.Window({
	  		closable : true
	  		,header : true
	  		//border : true,
	  		,width : 375
	  		,height : 210
	  		//bodyBorder : true
	  		,frame : true
	  		,padding : '0px'
	  		,layout : 'fit'
		    ,items : [
		        formPanel
		    ]	
	  	});
	  	
	  	var formPanel = new Ext.form.FormPanel({
			labelWidth: 75,
		    labelAlign: 'left',
		    layout: 'form',
		    id: 'add_customer',
		   // padding: 10,
		    frame: true,
		    items : [{
	            xtype: 'tbtext',
	            text: lang_um.fd_group
	    	},{
				xtype : 'combo',
				width: 100
	    	},{
	    	  xtype : 'textfield'
	    	  ,fieldLabel : lang_um.fd_filtter
	        }]
	  	})
	  	searchWindow.show();
//		var grid = Ext.getCmp('device_grid_panel_obj');
//		if(grid){
//			grid.store.removeAll();
//			var type = Ext.getCmp('queryType').getValue();
//			//var type = Ext.getCno('queryType').dom.value;
//			var value = Ext.get('queryValue').dom.value;
//			grid.store.setBaseParam('type',type);
//			grid.store.setBaseParam('value',value);
//			grid.store.load({
//				params:{start:0, limit:20}
//				,callback :function(r,options,success) {	
//					if(!success){
//						var notifyWin = p_app.getDesktop.showNotification({
//					        html: this.store.reader.jsonData.message || this.store.reader.jsonData.msg
//							, title: lang_tr.Error
//					      });
//					}
//					grid.store.removeAll();
//				}
//				,scope:grid				
//			});
//		}
  },
  //编辑
  editData: function(){
      this.edit("edit_server_member");
  },
  //删除
  removeData: function(){
  	this.remove('id', 'Delete', 'crm', 'delete_server_member');
  },
  //创建窗口
  createWin: function(){
      return this.initWin(400, 334, lang_um.add_customer);
  },
  storeMapping: ["id","name","phoneno", "company", "email", "cust_group", "remark", "office_no", "fax", "alias", "sex"],
  initComponent: function(){
      var sm = new Ext.grid.CheckboxSelectionModel({
        singleSelect: false,
        listeners: {
            rowselect: function(sm, row, rec) {
    			//this.phone_array.push(rec.data.phoneno); 
                Ext.getCmp('card_customers').phone_array.push(rec.data.phoneno);
            }
      	}
      });
      this.cm = new Ext.grid.ColumnModel([new Ext.grid.RowNumberer(),//获得行号
		sm,{
            id: 'name'
            ,header: lang_um.fd_name
            ,dataIndex: 'name'
            ,width:80
        },{
            id: 'sex'
            ,header: lang_um.fd_sex
            ,dataIndex: 'sex'
            ,width:30
        }, {
            id: 'phoneno',
            header: lang_um.fd_phoneno,
            dataIndex: 'phoneno'
            ,width:80
        }, {
            id: 'company',
            header: lang_um.fd_company,
            dataIndex: 'company'
        }, {
            id: 'email',
            header: lang_um.fd_email,
            dataIndex: 'email'
        }, {
            id: 'remark',
            header: lang_um.fd_remark,
            dataIndex: 'remark'
        }, {
            id: 'office_no',
            header: lang_um.fd_office_no,
            dataIndex: 'office_no'
        }, {
            id: 'fax',
            header: lang_um.fd_fax,
            dataIndex: 'fax'
        }, {
            id: 'alias',
            header: lang_um.fd_alias,
            dataIndex: 'alias'
        }, {
            id: 'group',
            header: lang_um.fd_group,
            width: 100,
            dataIndex: 'cust_group'
        }]);
      
//      this.store = new EzDesk.um.customer_list({
//	      	desktop: null
//	      	,um:1
//	      	,moduleId: 'crm'
//	      	,connection: this.baseUrl
//	      	,locate:lang_um
//	      	,params :{
//					um:1
//				}
//      });
      
      this.store = new Ext.data.JsonStore({
      	  id: "id",
          url: this.baseUrl, //默认的数据源地址，继承时需要提供
          root: "data",
          totalProperty: "totalCount",
          remoteSort: true,
          method: 'POST',
          fields: this.storeMapping,
          baseParams :{
	    		method: 'crm_get_custmer_list',
	            moduleId: 'crm',
	            um: 1
			}
      });
      
      this.cm.defaultSortable = true;
      var viewConfig = Ext.apply({
          forceFit: true
      }, this.gridViewConfig);
      
      var combo = new Ext.form.ComboBox({
    	  name : 'perpage',
    	  width: 45,
    	  store: new Ext.data.ArrayStore({
    	    fields: ['id'],
    	    data  : [
    	      ['10'],
    	      ['50'],
    	      ['100']
    	    ]
    	  }),
    	  mode : 'local',
    	  value: '15',

    	  listWidth     : 45,
    	  triggerAction : 'all',
    	  displayField  : 'id',
    	  valueField    : 'id',
    	  editable      : false,
    	  forceSelection: true
    	});
      
      var bbar = new Ext.PagingToolbar({
          pageSize: 10,
          store: this.store,
          displayInfo: true,
          items:[
		    '-',
		    lang_um.page_size,
		    combo
		  ]
      });
      
      combo.on('select', function(combo, record) {
    	  bbar.pageSize = parseInt(record.get('id'), 10);
    	  bbar.doLoad(bbar.cursor);
      }, this);
      
      this.grid = new Ext.grid.GridPanel({
    	  store: this.store,
          id: 'crm_service_grid',
          height: 500,
          autoScroll: true,
          cm: this.cm,
          sm: sm,
          trackMouseOver: true,
          loadMask: true,
          //viewConfig: viewConfig,
          region : 'center',
          tbar: [{
              id: 'addButton',
              text: '新增',
              iconCls: 'crm-add-icon',
              tooltip: '添加新纪录',
              handler: this.create,
              scope: this
          }, '-', {
              id: 'editButton',
              text: '编辑',
              iconCls: 'crm-edit-icon',
              tooltip: '修改记录',
              handler: this.editData,
              scope: this
          }, '-', {
              text: '删除',
              iconCls: 'crm-del-icon',
              tooltip: '删除所选中的信息',
              handler: this.removeData,
              scope: this
          }, '-', {
              text: '刷新',
              iconCls: 'crm-refresh-icon',
              tooltip: '刷新纪录',
              handler: this.refresh,
              scope: this
          },'-', {
              text: '查询',
              iconCls: 'crm-view-icon',
              tooltip: '刷新纪录',
              handler: this.search,
              scope: this
          },'  '],
          bbar: bbar
      });
      
      EzDesk.customerGrid.superclass.initComponent.call(this);
  }
});

EzDesk.um.customer_panel = Ext.extend(EzDesk.BillingPanel,{
	header : false
	,padding : '0px'
	,phone_array :  new Array()
	,initComponent: function() {
		Ext.apply(this,{
			layout : 'border'
			,tbar : [{
		    	xtype: 'button',
                text: lang_um.action_list,
                itemId: 'action_list',
                menu: {
                    xtype: 'menu',
                    itemId: 'menu_stock',
                    items: [
                        {
                            xtype: 'menuitem'
                            ,text: lang_um.fd_group_call
                            ,iconCls: 'call-icon'
                            ,handler: function(){
                        		phone_array = Ext.getCmp('crm_customer_grid').phone_array; 
	                        	Ext.getCmp('main_window').active_card('card_dialout');
	        	        		Ext.getCmp('tt-dialout').setValue(this.phone_array.toString());
	        	        		this.phone_array = new   Array(); 
	                    	},
	                    	scope : this
                        },{
                            xtype: 'menuitem'
                            ,text: lang_um.batch_import
                            ,iconCls: 'batch-import-icon'
                            ,handler: function(){
							}    
                        },{
                        	xtype: 'menuitem'
                            ,text: lang_um.batch_export
                            ,iconCls: 'batch-export-icon'
                            ,handler: function(){
							}    
                        }
					]
                }
		    }]
			,items : [
			    new EzDesk.customerGrid({
			    	id: 'crm_customer_grid',
			    	name: 'crm_customer_grid',
		    		baseUrl : os_service_url,
	        		moduleId : 'crm'
			    })
			]
		});
		EzDesk.um.customer_panel.superclass.initComponent.call(this);
	}
});
Ext.reg('customer-panel', EzDesk.um.customer_panel);

EzDesk.um.cdr_panel = Ext.extend(EzDesk.BillingPanel,{
	header : false
	,padding : '0px'
	,initComponent: function() {
		var phone_array = new Array();
		this.store = new EzDesk.um.cdr_list({
        	desktop: null
        	,um:1
        	,moduleId:'crm'
        	,connection: os_service_url
        	,locate:lang_um
        	,params :{
				um:1
			}
        });
		
		function renderPlayRecord(value, p, record) {
	        /*return String.format(
	            '<embed autoplay="false" src="{0}?act=cdr&fn={1}" width="200" controls=smallconsole></embed> ',
	            os_service_url,record.data['fn']
	        );*/
			return String.format('<a href="{0}?act=cdr&fn={1}" target="_BLANK">Play</a>',os_service_url,record.data['fn']);
	    }
		Ext.apply(this,{
			layout : 'border'
			,tbar : [{
	                xtype: 'buttongroup',
	                columns: 2,
	                id: 'btg_cdr_type',
	                items: [
	                    {
	                        xtype: 'button',
	                        text: lang_um.fd_realtime,
	                        id: 'fd_runtime',
	                        allowDepress: true,
	                        enableToggle: true,
	                        toggleGroup:'type',
	                        pressed: true,
	                        clickEvent: 'click'
	                    },
	                    {
	                        xtype: 'button',
	                        text: lang_um.fd_history,
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
	                xtype: 'tbtext',
	                text: lang_tr.From
	            },
	            {
	                xtype: 'datefield',
	                fieldLabel: 'Label',
	                width: 85,
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
	                width: 85,
	                id: 'fd_cdr_to',
	                format:'Y-m-d H:i:s',
	                value:new Date((new Date()).getFullYear(),(new Date()).getMonth(),(new Date()).getDate()+1)
	            },'->',{
			    	xtype : 'tbtext'
					,text : lang_um.fd_filtter
				},{
					xtype : 'textfield'
					,id : 'fdr_endpoint_filter'
					,width: 80
		            ,fieldLabel : lang_um.fd_filtter
				},
	            {
	                xtype: 'tbseparator'
	            },{
					xtype:'button'
					,text:lang_um.fd_query
					,handler: function(){
	    				var fdr_from = Ext.getCmp('fd_cdr_from');
	    				var fdr_to = Ext.getCmp('fd_cdr_to');
	    				var fdr_filter = Ext.getCmp('fdr_endpoint_filter');
	    				var fdr_rt = Ext.getCmp('fd_runtime');
	    				
	    				var from = fdr_from?fdr_from.getValue():'';
	    				var to = fdr_to?fdr_to.getValue():'';
	    				var filter = fdr_filter?fdr_filter.getValue():'';
	    				var type = fdr_rt? (fdr_rt.pressed?0:1):0;
	    				
	    				if(fdr_from && (!Ext.isDate(from))){
	    					pfrom = new Date();
	    					from = new Date(pfrom.getFullYear(),pfrom.getMonth(),0);
	    					fdr_from.setValue(from);
	    				}
	    				if(fdr_to && (!Ext.isDate(to))){
	    					pto = new Date();
	    					to = new Date(pto.getFullYear(),pto.getMonth()+1,0);
	    					fdr_to.setValue(from);
	    				}
	    				var grid = Ext.getCmp('lv-cdr');
	    				
	    				if(grid){
	    					grid.store.setBaseParam('from',from.format('Y-m-d H:i:s'));
	    					grid.store.setBaseParam('to',to.format('Y-m-d H:i:s'));
	    					grid.store.setBaseParam('fillter',filter.toString());
	    					grid.store.setBaseParam('type',type.toString());
	    					grid.store.load({
	    						params:{start:0, limit:10}
	    						,callback :function(r,options,success) {	
	    							if(!success){
	    								Ext.Msg.show({
	    				                    title: lang_tr.Error,
	    				                    buttons: Ext.Msg.OK,
	    				                    icon: Ext.MessageBox.ERROR,
	    				                    msg: this.store.reader.jsonData.message||this.store.reader.jsonData.msg,
	    				                    manager: m
	    				                });
	    							}
	    						}
	    						,scope:grid
	    					});
	    				}
	            	},
	                scope:this
				}]
			,bbar : new Ext.PagingToolbar({
		        store: this.store, 
		        displayInfo: true,
		        pageSize: 10,
		        prependButtons: true
		    })
			,items : [{
			    xtype: 'grid',
		        id: 'lv-cdr',
		        region: 'center',
		        store: this.store,
		        //autoExpandColumn: 'Remark',
		        columnLines: true,
		        trackMouseOver: true,
		        loadMask: true,
		        sm: new Ext.grid.RowSelectionModel({
                    singleSelect: false,
                    listeners: {
                        rowselect: function(sm, row, rec) {
		        			phone_array.push(rec.data.phoneno); 
                            //Ext.getCmp("company-form").getForm().loadRecord(rec);
                        }
                    }
                }),
		        cm: new Ext.grid.ColumnModel({
		        	defaults: {
		                width: 95
		                //,sortable: true
		            }
		        	,columns: [
		        	    new Ext.grid.RowNumberer() 
			        	,{
			                id: 'StartTime',
			                header: lang_um.fd_start_time,
			                width: 128,
			                dataIndex: 'AcctStartTime'
			            }, {
			                id: 'Caller',
			                header: lang_um.fd_caller,
			                dataIndex: 'caller'
			            }, {
			                id: 'Callee',
			                header: lang_um.fd_callee,
			                width: 135,
			                dataIndex: 'callee'
			            }, {
			                id: 'Duration',
			                header: lang_um.fd_duration,
			                width: 40,
			                dataIndex: 'SessionTimeMin'
			            }, {
			                id: 'Fee',
			                header: lang_um.fd_fee,
			                width: 40,
			                dataIndex: 'AcctSessionFee'
			            }, {
			                id: 'Play',
			                header: lang_um.fd_paly,
			                dataIndex: 'fn',
			                renderer:renderPlayRecord
		            }]
		        })
			}]
		});
		EzDesk.um.cdr_panel.superclass.initComponent.call(this);
		//this.store.load({params:{start:0, limit:10}});
	}
});
Ext.reg('cdr-panel', EzDesk.um.cdr_panel);

EzDesk.um.finance_panel = Ext.extend(EzDesk.BillingPanel,{
	header : false
	,padding : '0px'
	,initComponent: function() {
		var phone_array = new Array();
		this.store = new EzDesk.um.finance_list({
        	desktop: null
        	,um:1
        	,moduleId:'crm'
        	,connection: os_service_url
        	,locate:lang_um
        	,params :{
				um:1
			}
        });
		
		Ext.apply(this,{
			layout : 'border'
			,tbar : [{
	                xtype: 'tbtext',
	                text: lang_tr.From
	            },
	            {
	                xtype: 'datefield',
	                fieldLabel: 'Label',
	                width: 85,
	                id: 'fd_finance_from',
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
	                width: 85,
	                id: 'fd_finance_to',
	                format:'Y-m-d H:i:s',
	                value:new Date((new Date()).getFullYear(),(new Date()).getMonth(),(new Date()).getDate()+1)
	            },'->',{
			    	xtype : 'tbtext'
					,text : lang_um.fd_filtter
				},{
					xtype : 'textfield'
					,id : 'fd_finance_filter'
					,width: 80
		            ,fieldLabel : lang_um.fd_filtter
				},
	            {
	                xtype: 'tbseparator'
	            },{
					xtype:'button'
					,text:lang_um.fd_query
					,handler: function(){
	    				var fdr_from = Ext.getCmp('fd_finance_from');
	    				var fdr_to = Ext.getCmp('fd_finance_to');
	    				var fdr_filter = Ext.getCmp('fd_finance_filter');
	    				
	    				var from = fdr_from?fdr_from.getValue():'';
	    				var to = fdr_to?fdr_to.getValue():'';
	    				var filter = fdr_filter?fdr_filter.getValue():'';
	    				
	    				if(fdr_from && (!Ext.isDate(from))){
	    					pfrom = new Date();
	    					from = new Date(pfrom.getFullYear(),pfrom.getMonth(),0);
	    					fdr_from.setValue(from);
	    				}
	    				if(fdr_to && (!Ext.isDate(to))){
	    					pto = new Date();
	    					to = new Date(pto.getFullYear(),pto.getMonth()+1,0);
	    					fdr_to.setValue(from);
	    				}
	    				var grid = Ext.getCmp('lv-finance');
	    				
	    				if(grid){
	    					grid.store.setBaseParam('from',from.format('Y-m-d H:i:s'));
	    					grid.store.setBaseParam('to',to.format('Y-m-d H:i:s'));
	    					grid.store.setBaseParam('fillter',filter.toString());
	    					grid.store.load({
	    						params:{start:0, limit:10}
	    						,callback :function(r,options,success) {	
	    							if(!success){
	    								Ext.Msg.show({
	    				                    title: lang_tr.Error,
	    				                    buttons: Ext.Msg.OK,
	    				                    icon: Ext.MessageBox.ERROR,
	    				                    msg: this.store.reader.jsonData.message||this.store.reader.jsonData.msg
	    				                    ,manager: m
	    				                });
	    							}
	    						}
	    						,scope:grid
	    					});
	    				}
	            	},
	                scope:this
				}]
			,bbar : new Ext.PagingToolbar({
		        store: this.store, 
		        displayInfo: true,
		        pageSize: 10,
		        prependButtons: true
		    })
			,items : [{
			    xtype: 'grid',
		        id: 'lv-finance',
		        region: 'center',
		        store: this.store,
		        autoExpandColumn: 'Remark',
		        columnLines: true,
		        trackMouseOver: true,
		        loadMask: true,
		        sm: new Ext.grid.RowSelectionModel({
                    singleSelect: false,
                    listeners: {
                        rowselect: function(sm, row, rec) {
		        			phone_array.push(rec.data.phoneno); 
                            //Ext.getCmp("company-form").getForm().loadRecord(rec);
                        }
                    }
                }),
		        cm: new Ext.grid.ColumnModel({
		        	defaults: {
		                width: 50
		                //,sortable: true
		            }
		        	,columns: [
		        	    new Ext.grid.RowNumberer() 
			        	,{
			                id: 'RechargeTime',
			                header: lang_um.fd_recharge_time,
			                width: 128,
			                dataIndex: 'H_Datetime'
			            }, {
			                id: 'Type',
			                header: lang_um.fd_type,
			                width: 50,
			                dataIndex: 'IncType'
			            }, {
			                id: 'Old_Balance',
			                header: lang_um.fd_old_balance,
			                dataIndex: 'Old_Balance'
			            }, {
			                id: 'Cost',
			                header: lang_um.fd_cost,
			                dataIndex: 'Cost'
			            }, {
			                id: 'New_Balance',
			                header: lang_um.fd_new_balance,
			                dataIndex: 'New_Balance'
			            }, {
			                id: 'RealCost',
			                header: lang_um.fd_realcost,
			                dataIndex: 'RealCost'
			            }, {
			                id: 'SourcePin',
			                header: lang_um.fd_src_pin,
			                dataIndex: 'SourcePin'
			            }, {
			                id: 'Remark',
			                header: lang_um.fd_remark,
			                dataIndex: 'Remark'
		            }]
		        })
			}]
		});
		EzDesk.um.finance_panel.superclass.initComponent.call(this);
	}
});
Ext.reg('finance-panel', EzDesk.um.finance_panel);

EzDesk.um.dialout_panel = Ext.extend(EzDesk.BillingPanel,{
	initComponent: function() {
		Ext.apply(this,{
			border: false
			,padding : '5px'
			,frame: true
			,buttons: [{
	            text: lang_um.submit,
	            handler: function(){
	        		var fp = Ext.getCmp('dialout-form');
	                if(fp && fp.getForm().isValid()){
	                	try {
		                	fp.getForm().submit({
		                        url: os_service_url,
		                        waitMsg: lang_um.wait_msg,
		                        params: {
		        	   				method: 'crm_dialout_upload',
		        	   				moduleId: 'crm',
		        	   				um: 1
		                       	}
		                       	,success: function ( fp, action ) { 
		                       		obj = Ext.decode(action.response.responseText);
		                    		Ext.MessageBox.alert('', obj.message); 
		                    	}
		                    	,failure: function ( fp, action) { 
		                    		obj = Ext.decode(action.response.responseText);
		                    		Ext.MessageBox.alert('', obj.message); 
		                    	} 
		                    });
	                	}catch (err) {
	                    	m = null;
	                    	if(s.ownerModule && s.ownerModule.app && s.ownerModule.app.getDesktop()) 
	                    		m = s.ownerModule.app.getDesktop().getManager();
	                        Ext.Msg.show({
	                            autoScroll: true,
	                            animCollapse: false,
	                            constrainHeader: true,
	                            maximizable: false,
	                            manager: m,
	                            modal: true,
	                            title: lang_tr.Error,
	                            msg: 'Error:' + err.message + '<br>Line:' + err.lineNumber//+'<br><hr>Stack:<br>'+err.stack
	                            ,
	                            buttons: Ext.Msg.OK,
	                            icon: Ext.MessageBox.ERROR
	                        });
	                    }
	                }
	            }
	        },{
	            text: lang_um.reset,
	            handler: function(){
	        		fp.getForm().reset();
	            }
	        }]
	        ,items: [{
				xtype: 'form',
				id:'dialout-form',
		        fileUpload: true,
		        header : false,
		        border : false,
		        defaults: {
		            anchor: '95%'
		        }
				,items: [{
		    			xtype: 'textarea'
		    			,id: 'tt-dialout'
		    			,name: 'tt-dialout'
		    	        ,fieldLabel : lang_um.dialout_list
		    	        ,height: 100
		    	        ,allowBlank : false
		    	        ,style: {
		    	        	width: '95%',
		    	        	marginBottom: '1px'
		    	    	}
		    		},{
		    	        xtype: 'fileuploadfield',
		    	        id: 'ivr_file1',
		    	        emptyText: lang_um.empty_text,
		    	        fieldLabel: lang_um.ivr_file1,
		    	        name: 'ivr_file[]',
		    	        buttonText: '',
		    	        buttonCfg: {
		    	            iconCls: 'upload-icon'
		    	        },
		    	        style: {
		    	        	width: '90%',
		    	        	marginBottom: '1px'
		    	    	}
		    	    },{
		    	        xtype: 'fileuploadfield',
		    	        id: 'ivr_file2',
		    	        emptyText: lang_um.empty_text,
		    	        fieldLabel: lang_um.ivr_file2,
		    	        name: 'ivr_file[]',
		    	        buttonText: '',
		    	        buttonCfg: {
		    	            iconCls: 'upload-icon'
		    	        },
		    	        style: {
		    	        	width: '90%',
		    	        	marginBottom: '1px'
		    	    	}
		    	    },{
		    	        xtype: 'fileuploadfield',
		    	        id: 'ivr_file3',
		    	        emptyText: lang_um.empty_text,
		    	        fieldLabel: lang_um.ivr_file3,
		    	        name: 'ivr_file[]',
		    	        buttonText: '',
		    	        buttonCfg: {
		    	            iconCls: 'upload-icon'
		    	        },
		    	        style: {
		    	        	width: '90%',
		    	        	marginBottom: '1px'
		    	    	}
		    	    },{
		    	        xtype: 'fileuploadfield',
		    	        id: 'ivr_file4',
		    	        emptyText: lang_um.empty_text,
		    	        fieldLabel: lang_um.ivr_file4,
		    	        name: 'ivr_file[]',
		    	        buttonText: '',
		    	        buttonCfg: {
		    	            iconCls: 'upload-icon'
		    	        },
		    	        style: {
		    	        	width: '90%',
		    	        	marginBottom: '1px'
		    	    	}
		    	    },{
				    		xtype: 'textarea'
			    			,id: 'tt-sms'
			    			,name: 'tt-sms'
			    	        ,fieldLabel : lang_um.sms
			    	        ,height: 100
			    	        //,allowBlank : false
			    	        ,style: {
			    	        	width: '95%',
			    	        	marginBottom: '1px'
			    	    	}
				    }]
			}]
		});
		EzDesk.um.dialout_panel.superclass.initComponent.call(this);
	}
});
Ext.reg('dialout-panel', EzDesk.um.dialout_panel);

EzDesk.um.main_actions = function(send){
	this.home = new Ext.Action({
		text: lang_um.menu_home,
        handler: function(){
            send.active_card('card_home');
        },
        scope:send,
        iconCls: 'blist'
	});
	this.cdr = new Ext.Action({
		text: lang_um.menu_cdr,
        handler: function(){
            send.active_card('card_cdr');
        },
        scope:send,
        iconCls: 'blist'
	});
	this.finance = new Ext.Action({
		text: lang_um.menu_finance,
        handler: function(){
			send.active_card('card_finance');
        },
        scope:send,
        iconCls: 'blist'
	});
	this.customers = new Ext.Action({
		text: lang_um.menu_customers,
        handler: function(){
			send.active_card('card_customers');
        },
        scope:send,
        iconCls: 'blist'
	});
	this.dialout = new Ext.Action({
		text: lang_um.menu_dialout,
        handler: function(){
            send.active_card('card_dialout');
        },
        scope:send,
        iconCls: 'blist'
	});
	this.exit = new Ext.Action({
		text: lang_um.menu_exit,
        handler: function(){
			window.location = os_logout_url;
        },
        scope:send,
        iconCls: 'blist'
	});
};

EzDesk.um.main_window = Ext.extend(Ext.Window,{
	title : lang_um.win_title,
	id: 'main_window'
	,closable : false
	,header : true
	//border : true,
	,width : 780
	,height : 580
	//bodyBorder : true
	,frame : true
	,padding : '0px'
	,initComponent: function() {
		this.actions = new EzDesk.um.main_actions(this);
		this.account = Ext.decode(uphone_account);
		
		Ext.apply(this,{
			layout : 'border'
			,activeItem: 0
//			,bbar:{
//				xtype:'statusbar'
//				,defaultText: lang_tr.Ready
//				,text: lang_tr.Ready
//		        ,iconCls: 'x-status-valid'
//		        ,items: [
//		            new Date().format('Y-d-n')
//		        ]	
//			}
			,tbar : [
		      this.actions.home,
		      this.actions.cdr,
		      this.actions.finance,
		      this.actions.customers,
		      this.actions.dialout,
		      '->',
		      this.actions.exit
		      ]
		    ,items : [{
		    	xtype : 'panel'
		    	,id : 'contextPanel'
		    	,padding : '0px'
		    	,border : false
		    	,region : 'center'
		    	,layout : 'card'
		    	,items : [{
		    			xtype : 'home_panel'
		    			,id : 'card_home'
		    			,account : this.account
			    		,autoScroll : true
			    		,border : false
		    		},
		    		{
		    			xtype : 'cdr-panel'
		    			,id : 'card_cdr'
		    			,border : false
			    		,autoScroll : true
		    		},{
		    			xtype : 'finance-panel'
		    			,id : 'card_finance'
		    			,border : false
			    		,autoScroll : true
		    		},{
		    			xtype : 'customer-panel'
		    			,id : 'card_customers'
		    			,border : false
			    		,autoScroll : true
		    		},{
		    			xtype : 'dialout-panel'
		    			,id : 'card_dialout'
				    	,autoScroll : true
		    			,border : false
		    		}]
		    }]
		});
		EzDesk.um.main_window.superclass.initComponent.call(this);
		//this.active_card('card_home');
	},
	active_card:function(card_id){
		var contextPanel = Ext.getCmp('contextPanel');
		if(contextPanel) {
			var l = contextPanel.getLayout();
			if(l)
				l.setActiveItem(card_id);
		}
	}
	,listeners: {
		contextmenu: function(e){
		}
	}
});
//出库管理
EzDesk.DeliveryPanel = Ext.extend(EzDesk.CrudPanel, {
    //id，需唯一
    id: "deliveryPanel",
    //数据源
    baseUrl: "",
    //模块名  
    moduleId: "",
    desktop: "",
    store: "",
    //表单
    createForm: function(){
		var agent_id;
        
        var coin_type = new Ext.form.ComboBox({
            name: 'currency_type',
            store: new Ext.data.SimpleStore({
                fields: ['value', 'text'],
                data: [[1, 'CNY'], [2, 'USD'], [3, 'PTS'], [4, 'TWD'], [5, 'HKD']]
            }),
            valueField: 'value',
            fieldLabel: EzDesk.devices.Locale.Currency,
            displayField: 'text',
            editable: false,
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            allowBlank: false,
            blankText: EzDesk.devices.Locale.Stock.BlankText,
            emptyText: EzDesk.devices.Locale.QueryTypeText,
            selectOnFocus: false,
            forceSelection: true,
            width: 150
        });
        
        var prodtypeStore = new Ext.data.Store({
            autoLoad: true,
            method: 'POST',
            url: this.baseUrl,
            //设定读取的格式    
            reader: new Ext.data.JsonReader({
                totalProperty: 'totalCount',
                root: 'rows'
            }, ['product_type_id', 'product_name']),
            baseParams :{
				method: 'select_prod_type',
                moduleId: this.moduleId
			}
        });
        var prodType = new Ext.form.ComboBox({
            hiddenName: 'product_type_id',
            store: prodtypeStore,
            valueField: 'product_type_id',
            isFormField: true,
            fieldLabel: EzDesk.devices.Locale.Stock.ProductName,
            displayField: 'product_name',
            editable: false,
            typeAhead: true,
            mode: 'local',
            allowBlank: false,
            blankText: EzDesk.devices.Locale.Stock.BlankText,
            triggerAction: 'all',
            emptyText: EzDesk.devices.Locale.Stock.ProductEmptyText,
            selectOnFocus: true,
            width: 150
        });
		
        var agenterStore = new Ext.data.Store({
            autoLoad: true,
            method: 'POST',
            url: this.baseUrl,
            //设定读取的格式    
            reader: new Ext.data.JsonReader({
                totalProperty: 'totalCount',
                root: 'rows'
            }, ['agent_id', 'agent_name']),
            baseParams :{
				method: 'get_agent_name',
                moduleId: this.moduleId
			}
        });
		 
        var agenter = new Ext.form.ComboBox({
            hiddenName: 'agent_id',
            store: agenterStore,
            valueField: 'agent_id',
            isFormField: true,
            fieldLabel: EzDesk.devices.Locale.Agent,
            displayField: 'agent_name',
            editable: false,
            typeAhead: true,
            mode: 'local',
            allowBlank: false,
            blankText: EzDesk.devices.Locale.Stock.BlankText,
            triggerAction: 'all',
            emptyText: EzDesk.devices.Locale.Stock.AgentBlankText,
            selectOnFocus: true,
            width: 150,
            listeners: {
                select: function(combo, record, index){
        			userStore.setBaseParam('agent_id',  combo.value);
        			billStore.setBaseParam('agent_id',  combo.value);
                    userStore.load();
                    billStore.load();
                }
            }
        });
        
		var userStore = new Ext.data.Store({
            //autoLoad: true,
            method: 'POST',
            url: this.baseUrl,
            //设定读取的格式    
            reader: new Ext.data.JsonReader({
                totalProperty: 'totalCount',
                root: 'data'
            }, ['user_cs_id', 'user_cs_name']),
            baseParams :{
				method: 'get_user_cs',
            	moduleId: this.moduleId
			}
		
        });
		var usercombo = new Ext.form.ComboBox({
			hiddenName: 'user_cs_id',
            store: userStore,
            valueField: 'user_cs_id',
            isFormField: true,
            fieldLabel: EzDesk.devices.Locale.UserPlan,
            displayField: 'user_cs_name',
            editable: false,
            typeAhead: true,
            mode: 'local',
            allowBlank: false,
            blankText: EzDesk.devices.Locale.Stock.BlankText,
            triggerAction: 'all',
            emptyText: EzDesk.devices.Locale.Stock.UPlanBlankText,
            selectOnFocus: true,
            width: 150
        });
		
        var billStore = new Ext.data.Store({
            //autoLoad: true,
            method: 'POST',
            url: this.baseUrl,
            //设定读取的格式    
            reader: new Ext.data.JsonReader({
                totalProperty: 'totalCount',
                root: 'data'
            }, ['agent_cs_id', 'agent_cs_name']),
            baseParams :{
				method: 'get_agent_cs',
        		moduleId: this.moduleId
			}
        });
        
        var billingcombo = new Ext.form.ComboBox({
            hiddenName: 'agent_cs_id',
            store: billStore,
            valueField: 'agent_cs_id',
            isFormField: true,
            fieldLabel: EzDesk.devices.Locale.AgentPlan,
            displayField: 'agent_cs_name',
            editable: false,
            mode: 'local',
            triggerAction: 'all',
            emptyText: EzDesk.devices.Locale.Stock.APlanBlankText,
            allowBlank: false,
            blankText: EzDesk.devices.Locale.Stock.BlankText,
            selectOnFocus: true,
            width: 150
        });
		
        var formPanel = new Ext.form.FormPanel({
            frame: true,
            labelWidth: 90,
            labelAlign: 'right',
            fileUpload: true,
            items: [{
                xtype: 'fieldset',
                title: EzDesk.devices.Locale.Stock.BaseInfo,
                autoHeight: true,
                items: [{
                    layout: 'column',
                    border: false,
                    items: [{
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            xtype: "hidden",
                            name: "id"
                        },{
                            xtype: "hidden",
                            name: "moduleId",
                            value: this.moduleId
                        },{
                            xtype: "hidden",
                            id: "method",
                            name: "method",
                            value: "delivery_action"
                        }, prodType]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: EzDesk.devices.Locale.Stock.Operater,
                            inputType: 'password',
                            disabled: true,
                            allowBlank: false,
                            name: 'operator_id',
                            value: '********'
                        }]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [agenter]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: EzDesk.devices.Locale.InitializeBalance,
                            allowBlank: false,
                            blankText: EzDesk.devices.Locale.Stock.BlankText,
                            name: 'balance',
                            width: 150
                        }]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [coin_type]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: EzDesk.devices.Locale.FreeTime,
                            allowBlank: false,
                            blankText: EzDesk.devices.Locale.Stock.BlankText,
                            name: 'free_period',
                            width: 150
                        }]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: EzDesk.devices.Locale.ValidDateNo,
                            allowBlank: false,
                            blankText: EzDesk.devices.Locale.Stock.BlankText,
                            name: 'valid_date_no',
                            width: 150
                        }]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: EzDesk.devices.Locale.HireTime,
                            name: 'hire_number',
                            width: 150
                        }]
                    },{
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [usercombo]
                    },{
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [billingcombo]
                    }]
                }]
            }, {
                xtype: 'fieldset',
                title:  EzDesk.devices.Locale.Stock.Remark,
                autoHeight: true,
                items: [{
                    xtype: "textarea",
                    width: 495,
                    height: 25,
                    name: "remark",
                    hideLabel: true
                }]
            }, {
                xtype: 'fieldset',
                title: EzDesk.devices.Locale.Stock.ImportIMEI,
               // autoHeight: true,
                items: [{
                    xtype: "textarea",
                    width: 451,
                    height: 100,
                    name: "imei",
                    hideLabel: true,
					multiline: true
                }]
            }]
        });
        return formPanel;
    },
	//查询
    search: function(){
        this.store.load({
            params: {
                start: 0,
                limit: 30,
				prodtype : Ext.get('product_name').dom.value,//得到输入框的值
				wfs_attribute : Ext.get('wfs_attribute').dom.value,//得到输入框的值
				isactive : Ext.get('is_ative').dom.value,//得到输入框的值
				stime : Ext.get('warehousing_start_date').dom.value,//得到输入框的值
				etime : Ext.get('warehousing_end_date').dom.value,//得到输入框的值
                imei: Ext.get('imei').dom.value,
            	agenter: Ext.get('agent_id').dom.value
            }
        });
    },
    //创建窗口
    createWin: function(){
        return this.initWin(this.desktop,550, 420, "出库管理");
    },
	//编辑
    editData: function(){
        this.edit("edit_server_list");
    },
	//保存
	saveData: function(){
        this.save(this.desktop, EzDesk.devices.Locale.Stock.WinText);
    },
    storeMapping: [ "store_id", "product_type_id", "product_name", "operator_id", "agent_id", "leaves_date", "init_charge", "remark"],
    initComponent: function(){
        var sm = new Ext.grid.CheckboxSelectionModel();
        this.cm = new Ext.grid.ColumnModel([new Ext.grid.RowNumberer(),//获得行号
 		sm, {
            header: EzDesk.devices.Locale.Stock.StockID,
            sortable: true,
            width: 150,
            dataIndex: "store_id"
        }, {
            header: EzDesk.devices.Locale.Stock.ProductName,
            sortable: true,
            width: 300,
            dataIndex: "product_type_id"
        }, {
            header: EzDesk.devices.Locale.Stock.Agent,
            sortable: true,
            width: 300,
            dataIndex: "agent_id"
        }, {
            header: EzDesk.devices.Locale.Stock.LeavesDate,
            sortable: true,
            width: 300,
            dataIndex: "leaves_date"
        }, {
            header: EzDesk.devices.Locale.Stock.Remark,
            sortable: true,
            width: 300,
            dataIndex: "remark"
        }]);
		
		var agenterStore = new Ext.data.Store({
            //autoLoad: true,
            method: 'POST',
            url: this.baseUrl,
            //设定读取的格式    
            reader: new Ext.data.JsonReader({
                totalProperty: 'totalCount',
                root: 'rows'
            }, ['agent_id', 'agent_name']),
            baseParams :{
				method: 'get_agent_name',
                moduleId: this.moduleId
			}
        });
		
        this.agenter = new Ext.form.ComboBox({
            //id: 'agent_id',
            hiddenName: 'agent_id',
            store: agenterStore,
            valueField: 'user_id',
            isFormField: true,
            fieldLabel: EzDesk.devices.Locale.Agent,
            displayField: 'real_name',
            editable: false,
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            emptyText: EzDesk.devices.Locale.Stock.AgentBlankText,
            selectOnFocus: true,
            width: 120
        });
        
		var prodStore = new Ext.data.Store({
            //autoLoad: true,
            method: 'POST',
            url: this.baseUrl,
            //设定读取的格式    
            reader: new Ext.data.JsonReader({
                totalProperty: 'totalCount',
                root: 'rows'
            }, ['product_type_id', 'product_name'])
        });
        this.prodType = new Ext.form.ComboBox({
            //id: 'product_name',
            hiddenName: 'product_name',
            store: prodStore,
            valueField: 'product_type_id',
            isFormField: true,
            fieldLabel: EzDesk.devices.Locale.Stock.ProductName,
            displayField: 'product_name',
            editable: false,
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            emptyText: EzDesk.devices.Locale.Stock.ProductEmptyText,
            selectOnFocus: true,
            width: 150
        });
		this.stime = new Ext.form.DateField({
            id: 'warehousing_start_date',
            name: 'warehousing_start_date',
            width: 90,
			//emptyText : '开始时间',
            format: 'Y-m-d' //格式化日期   
        });
        
        this.etime = new Ext.form.DateField({
			//emptyText : '结束时间',
            id: 'warehousing_end_date',
            name: 'warehousing_end_date',
            width: 90,
            format: 'Y-m-d'//格式化日期   
        });
        
        
        this.store = new Ext.data.JsonStore({
            id: "id",
            url: this.baseUrl, //默认的数据源地址，继承时需要提供
            root: "rows",
            totalProperty: "totalCount",
            remoteSort: true,
            fields: this.storeMapping,
            baseParams :{
				method: 'select_delivery',
                moduleId: this.moduleId
			}
        });
        
        this.cm.defaultSortable = true;
        this.sm = new Ext.grid.CheckboxSelectionModel();
		
        var viewConfig = Ext.apply({
            forceFit: true
        }, this.gridViewConfig);
		
        this.grid = new Ext.grid.GridPanel({
            store: this.store,
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
                text: EzDesk.devices.Locale.Stock.Add,
                iconCls: 'Icon-add',
                tooltip: EzDesk.devices.Locale.Stock.AddTip,
                handler: this.create,
                scope: this
            }, '-', {
                text: EzDesk.devices.Locale.Stock.Refresh,
                iconCls: 'Icon-refresh',
                tooltip: EzDesk.devices.Locale.Stock.RefreshTip,
                handler: this.refresh,
                scope: this
            }, 
            	//'->',this.agenter,'&nbsp', this.prodType,'&nbsp', this.stime,'&nbsp', this.etime, 
//            {
//                text: EzDesk.devices.Locale.Stock.Query,
//                pressed: true,
//                iconCls: 'selectIconCss',
//                handler: this.search,
//                scope: this
//            }, '   '
            ],
            bbar: new Ext.PagingToolbar({
                pageSize: 20,
                store: this.store,
                displayInfo: true
            })
        });
        EzDesk.DeliveryPanel.superclass.initComponent.call(this);
    }
});

//入库管理
EzDesk.StockPanel = Ext.extend(EzDesk.CrudPanel, {
    //id，需唯一
    id: "stockpanel",
    //数据源
    baseUrl : '',
	moduleId : '',
	desktop : '',
    //表单
    createForm: function(){
		var prodtypeStore = new Ext.data.Store({
            autoLoad: true,
            method: 'POST',
            url: this.baseUrl,
            //设定读取的格式    
            reader: new Ext.data.JsonReader({
                totalProperty: 'totalCount',
                root: 'rows'
            }, ['product_type_id','product_name']),
            baseParams :{
				method: 'select_prod_type',
            	moduleId: this.moduleId
			}
        });
        var prodType = new Ext.form.ComboBox({
            hiddenName: 'product_name',
            store: prodtypeStore,
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
            width: 120
        });
        
        var formPanel = new Ext.form.FormPanel({
            frame: true,
            labelWidth: 70,
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
                            value: "stock_action"
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
                            allowBlank: false,
                            blankText: EzDesk.devices.Locale.Stock.ProductEmptyText,
                            name: 'operate_id',
							inputType : 'password',
							value : '********',
							disabled: true
                        }]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: EzDesk.devices.Locale.Stock.ProductNumber,
                            name: 'product_number',
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
                            fieldLabel: EzDesk.devices.Locale.Stock.Model,
                            name: 'model',
                            width: 150
                        }]
                    }]
                }, {
                    layout: 'form',
                    defaultType: 'textfield',
                    items: [{
                        width: 376,
                        name: "factory_info",
                        fieldLabel: EzDesk.devices.Locale.Stock.FactoryInfo
                    }]
                },{
                    layout: 'form',
                    defaultType: 'textfield',
                    items: [{
                        width: 376,
                        name: "remark",
                        fieldLabel: EzDesk.devices.Locale.Stock.Remark
                    }]
                },]
            }, {
                xtype: 'fieldset',
                title: EzDesk.devices.Locale.Stock.ImportIMEI,
               // autoHeight: true,
                items: [{
                    xtype: "textarea",
                    width: 451,
                    height: 140,
                    name: "imei",
                    hideLabel: true,
					multiline: true
                }]
            }]
        });
        return formPanel;
    },
    //创建窗口
    createWin: function(){
        return this.initWin(this.desktop, 500, 420, EzDesk.devices.Locale.Stock.WinText);
    },
	//删除
    removeData: function(){
        this.remove('id', this.desktop, 'delete', this.moduleId,  'delete_server_list');
    },
	//编辑
    editData: function(){
        this.edit("edit_server_list");
    },
	//保存
	saveData: function(){
        this.save(this.desktop, EzDesk.devices.Locale.Stock.WinText);
    },
	 //时间
    dateRender: function(format){
        format = format || "Y-m-d h:i";
        return Ext.util.Format.dateRenderer(format);
    },
    //查询
    search: function(){
        //alert(this.imei.getValue());
        this.store.load({
            params: {
                start: 0,
                limit: 30,
				imei: Ext.get('imei').dom.value,
				prodtype: Ext.get('product_name').dom.value,
				stime: Ext.get('warehousing_start_date').dom.value,
				etime: Ext.get('warehousing_end_date').dom.value
            }
        });
    },
    storeMapping: ["warehousing_id", "product_type_id", "operate_id", "product_number", "model", "factory_info", "remark", "store_number"],
    initComponent: function(){
		this.store = new Ext.data.JsonStore({
            id: "id",
            url: this.baseUrl, //默认的数据源地址，继承时需要提供
            root: "rows",
            totalProperty: "totalCount",
            remoteSort: true,
            fields: this.storeMapping,
            baseParams :{
				method: 'select_stock',
                moduleId: this.moduleId
			}
        });
		
        var sm = new Ext.grid.CheckboxSelectionModel();
        this.cm = new Ext.grid.ColumnModel([
			new Ext.grid.RowNumberer(),//获得行号
 			sm, 
 			{
 	            header: EzDesk.devices.Locale.Stock.WarehousingID,
 	            sortable: true,
 	            width: 150,
 	            dataIndex: "warehousing_id"
 	        },
			{
	            header: EzDesk.devices.Locale.Stock.ProductName,
	            sortable: true,
	            width: 150,
	            dataIndex: "product_type_id"
	        },{
	            header: EzDesk.devices.Locale.Stock.Model,
	            sortable: true,
	            width: 150,
	            dataIndex: "model"
	        },{
	            header: EzDesk.devices.Locale.Stock.Operater,
	            sortable: true,
	            width: 200,
	            dataIndex: "operate_id"
	        }, {
	            header: EzDesk.devices.Locale.Stock.ProductNumber,
	            sortable: true,
	            width: 300,
	            dataIndex: "product_number"
	        }, {
	            header: EzDesk.devices.Locale.Stock.StoreNumber,
	            sortable: true,
	            width: 250,
	            dataIndex: "store_number"
	        }, {
	            header: EzDesk.devices.Locale.Stock.Remark,
	            sortable: true,
	            width: 200,
	            dataIndex: "remark"
	        }]);
		
		this.imei = new Ext.form.TextField({
			name: 'imei',
			id: 'imei',
			anchor:'85%',
			//emptyText : '标识',
			maxLength:18
		}); 
        var prodStore = new Ext.data.Store({
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
        this.prodType = new Ext.form.ComboBox({
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
			height : 80,
            width: 150
        });

		this.stime = new Ext.form.DateField({
            //emptyText : '开始时间',
            id: 'warehousing_start_date',
            name: 'warehousing_start_date',
            width: 90,
            format: 'Y-m-d' //格式化日期   
        });
        
        this.etime = new Ext.form.DateField({
			//emptyText : '结束时间',
            id: 'warehousing_end_date',
            name: 'warehousing_end_date',
            width: 90,
            format: 'Y-m-d'//格式化日期   
        });
		
		var viewConfig = Ext.apply({
        	forceFit: true
    		}, this.gridViewConfig
    	);
		
		this.grid = new Ext.grid.GridPanel({
            store: this.store,
            cm: this.cm,
            sm: sm,
			height: 500,
			autoScroll: true,
            trackMouseOver: true,
            loadMask: true,
            stripeRows: true,
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
                id: 'editButton',
                text: EzDesk.devices.Locale.Stock.Edit,
                iconCls: 'Icon-edit',
                tooltip: EzDesk.devices.Locale.Stock.EditTip,
                handler: this.edit,
                disabled : true,
                scope: this
            }, '-', {
                text: EzDesk.devices.Locale.Stock.Delete,
                iconCls: 'Icon-delete',
                tooltip: EzDesk.devices.Locale.Stock.DeleteTip,
                handler: this.removeData,
				disabled : true,
                scope: this
            }, '-', {
                text: EzDesk.devices.Locale.Stock.Refresh,
                iconCls: 'Icon-refresh',
                tooltip: EzDesk.devices.Locale.Stock.RefreshTip,
                handler: this.refresh,
                scope: this
            }
//            , 
//            //'->', this.imei,'&nbsp',this.prodType,'&nbsp', this.stime,'&nbsp', this.etime, 
//            {
//                text: EzDesk.devices.Locale.Stock.Query,
//                pressed: true,
//                iconCls: 'selectIconCss',
//                handler: this.search,
//                scope: this
//            }, '   '
            ],
            bbar: new Ext.PagingToolbar({
                pageSize: 30,
                store: this.store,
                displayInfo: true
//				,
//                displayMsg: '显示第 {0} - {1} 条记录，共 {2}条记录',
//                emptyMsg: "没有记录"
            })
        });
       EzDesk.StockPanel.superclass.initComponent.call(this);
    }
});
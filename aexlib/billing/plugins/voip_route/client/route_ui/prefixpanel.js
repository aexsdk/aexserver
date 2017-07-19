/**
 * 定义命名空间
 */
Ext.namespace("EzDesk");
//前缀替换管理
EzDesk.PrefixPanel = Ext.extend(EzDesk.CrudPanel, {
    //id，需唯一
    id: "prefixPanel",
    //标题
    title: "前缀替换管理",
    //数据源
    baseUrl: "",
    //模块名  
    moduleId: "",
    desktop: "",
    store: "",
    //表单
    createForm: function(){
		var routing_idStore = new Ext.data.Store({
            autoLoad: true,
            method: 'POST',
            url: this.baseUrl,
            baseParams: {
  				method : 'get_routing_id',
  				moduleId: this.moduleId
  			},
            //设定读取的格式    
            reader: new Ext.data.JsonReader({
                totalProperty: 'totalCount',
                root: 'data'
            }, ['id', 'routing_name'])
        });
		
        var routingType = new Ext.form.ComboBox({
            hiddenName: 'routing_id',
            store: routing_idStore,
            valueField: 'id',
            isFormField: true,
            fieldLabel: '路由ID',
            displayField: 'routing_name',
            editable: false,
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            allowBlank: false,
            blankText: '请务必填写该信息',
            emptyText: '请选择路由ID...',
            selectOnFocus: true,
            width: 150
        });
        
        var formPanel = new Ext.form.FormPanel({
            frame: true,
            labelWidth: 100,
            labelAlign: 'right',
            items: [{
                xtype: 'fieldset',
                title: '基本信息',
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
                            name: "method",
                            value: "add_prefix_list"
                        },{
                        	xtype: "hidden",
                            name: "moduleId",
                            value: this.moduleId
                        }, routingType]
                    },  {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: '路由前缀',
                            name: 'routing_prefix',
                            width: 150
                        }]
                    },  {
                        columnWidth: .9,
                        layout: 'form',
                        defaultType: 'numberfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: '优先级（数值越低优先级越高）',
                            name: 'priority',
                            width: 150
                        }]
                    }]
                }]
            }]
        });
        return formPanel;
    },
    //创建窗口
    createWin: function(){
        return this.initWin(this.desktop, 560, 183, "前缀替换管理");
    },
    //删除
    removeData: function(){
        this.remove('id', this.desktop, 'Delete Record', this.moduleId,  'delete_prefix_list');
    },
	//编辑
    editData: function(){
        this.edit("edit_prefix_list");
    },
	//保存
	saveData: function(){
        this.save(this.desktop, '添加路由前缀');
    },
    initComponent: function(){
    	this.store = new Ext.data.JsonStore({
             id: "id",
             url: this.baseUrl, //默认的数据源地址，继承时需要提供
             root: "data",
             totalProperty: "totalCount",
             remoteSort: true,
             fields: ["id", "routing_id","routing_name","routing_ip","routing_prefix", "priority"],
             baseParams: {
    			method : 'get_prefix_list',
  				moduleId: this.moduleId
  			}
        });
    	
        var sm = new Ext.grid.CheckboxSelectionModel();
        this.cm = new Ext.grid.ColumnModel([new Ext.grid.RowNumberer(),//获得行号
 		sm, {
        	 header: "路由前前缀",
             sortable: false,
             width: 150,
             dataIndex: "routing_prefix"
        }, {
            header: "路由ID",
            sortable: false,
            width: 150,
            dataIndex: "routing_id"
        },{
            header: "路由名称",
            sortable: false,
            width: 250,
            dataIndex: "routing_name"
        },{
            header: "路由IP",
            sortable: false,
            width: 250,
            dataIndex: "routing_ip"
        }, {
            header: "优先级",
            sortable: false,
            width: 50,
            dataIndex: "priority"
        }]);
        EzDesk.PrefixPanel.superclass.initComponent.call(this);
    }
});
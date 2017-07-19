/**
 * @author cilun
 */
/**
 * 定义命名空间
 */
Ext.namespace("EzDesk");
/*
 *CRUD面板基类
 */
//服务器到网关速度
EzDesk.ServerSpeedPanel = Ext.extend(EzDesk.CrudPanel, {
    //id，需唯一
    id: "serverspeedPanel",
    //标题
    title: "服务器网关速度",
    //数据源
    baseUrl: "",
    //模块名  
    moduleId: "",
    desktop: "",
    store: "",
    //表单
    createForm: function(){
        var server_ipStore = new Ext.data.Store({
            autoLoad: true,
            method: 'POST',
            url: this.baseUrl,
            baseParams: {
  				method: 'get_routing_id',
  				moduleId: this.moduleId
  			},
            //设定读取的格式    
            reader: new Ext.data.JsonReader({
                totalProperty: 'totalCount',
                root: 'data'
            }, ['id', 'routing_name'])
        });
        var serveripType = new Ext.form.ComboBox({
            hiddenName: 'routing_ip',
            store: server_ipStore,
            valueField: 'id',
            isFormField: true,
            fieldLabel: '路由IP',
            displayField: 'routing_name',
            editable: false,
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            allowBlank: false,
            blankText: '请务必填写该信息',
            emptyText: '请选择路由...',
            selectOnFocus: true,
            width: 150
        });
        
        var server_idStore = new Ext.data.Store({
            autoLoad: true,
            method: 'POST',
            url: this.baseUrl,
            baseParams: {
  				method: 'get_server_id',
  				moduleId: this.moduleId
  			},
            //设定读取的格式    
            reader: new Ext.data.JsonReader({
                totalProperty: 'totalCount',
                root: 'data'
            }, ['id', 'alias'])
        });
        var serveripdType = new Ext.form.ComboBox({
            hiddenName: 'server_id',
            store: server_idStore,
            valueField: 'id',
            isFormField: true,
            fieldLabel: '呼叫服务器',
            displayField: 'alias',
            editable: false,
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            allowBlank: false,
            blankText: '请务必填写该信息',
            emptyText: '请选择服务...',
            selectOnFocus: true,
            width: 150
        });
        
        var formPanel = new Ext.form.FormPanel({
            frame: true,
            labelWidth: 70,
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
                        	id: "method",
                            name: "method",
                            value: "add_server_speed"
                        },{
                        	xtype: "hidden",
                            name: "moduleId",
                            value: this.moduleId
                        }, serveripdType]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: '服务器名称',
                            name: 'routing_name',
                            width: 150
                        }]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [serveripType]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'numberfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: '稳定性',
                            name: 'stability',
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
                            fieldLabel: '速度（毫秒）',
                            name: 'speed',
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
        return this.initWin(this.desktop, 500, 200, "服务器网关速度");
    },
	 //删除
    removeData: function(){
        this.remove('id', this.desktop, EzDesk.voip_route.Locale.DeleteText, this.moduleId,  'delete_server_speed');
    },
	//编辑
    editData: function(){
        this.edit('edit_server_speed');
    },
	//保存
	saveData: function(){
        this.save(this.desktop, '添加网关到服务器的速度信息');
    },
    initComponent: function(){
    	this.store = new Ext.data.JsonStore({
            id: "id",
            url: this.baseUrl, //默认的数据源地址，继承时需要提供
            root: "data",
            totalProperty: "totalCount",
            remoteSort: true,
            fields: ["id", "server_id", "routing_name", "routing_ip", "stability", "stability_chn", "speed"],
            baseParams: {
    			method: 'get_server_speed',
  				moduleId: this.moduleId
  			}
        });
    	
        var sm = new Ext.grid.CheckboxSelectionModel();
        this.cm = new Ext.grid.ColumnModel([new Ext.grid.RowNumberer(),//获得行号
            sm, {
            header: "服务器ID",
            sortable: true,
            width: 180,
            dataIndex: "server_id"
        }, {
            header: "路由名称",
            sortable: true,
            width: 200,
            dataIndex: "routing_name"
        }, {
            header: "路由IP",
            sortable: true,
            width: 180,
            dataIndex: "routing_ip"
        }, {
            header: "稳定性",
            sortable: true,
            width: 150,
            dataIndex: "stability_chn"
        }, {
            header: "速度",
            sortable: true,
            width: 150,
            dataIndex: "speed"
        }]);
        EzDesk.ServerSpeedPanel.superclass.initComponent.call(this);
    }
});

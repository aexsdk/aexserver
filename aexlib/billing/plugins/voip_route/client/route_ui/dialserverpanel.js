/**
 * @author cilun
 */
/**
 * 定义命名空间
 */
Ext.namespace("EzDesk");


//回拨服务器信息
EzDesk.DialServerPanel = Ext.extend(EzDesk.CrudPanel, {
    //id，需唯一
    id: "dialserverPanel",
    //标题
    title: "回拨服务器信息",
    //数据源
    baseUrl: "",
    //模块名  
    moduleId: "",
    desktop: "",
    store: "",
    //表单
    createForm: function(){
		var validityType = new Ext.form.ComboBox({
            hiddenName: 'validity',
            store: new Ext.data.SimpleStore({
                fields: ['value', 'text'],
                data: [[1, '有效'], [2, '无效']]
            }),
            valueField: 'value',
            displayField: 'text',
			fieldLabel: '有效性',
            editable: false,
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            emptyText: '请输入有效性',
            selectOnFocus: false,
            forceSelection: true,
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
                            name: "moduleId",
                            value: this.moduleId
                        },{
                            xtype: "hidden",
                            name: "method",
                            value: "add_server_list"
                        },{
                            fieldLabel: '别名',
                            name: 'alias',
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
                            fieldLabel: '端口',
                            name: 'port'
                        }]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: '用户名',
                            name: 'user_name',
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
                            fieldLabel: '密码',
                            name: 'password',
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
                            fieldLabel: '服务器地址',
                            name: 'server_ip',
                            allowBlank: false,
                            blankText: '请务必填写该信息',
                            width: 150
                        }]
                    },{
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 80
                        },
                        items: [{
                            fieldLabel: '代理商',
                            name: 'resaler',
                            allowBlank: false,
                            blankText: '',
                            width: 150
                        }]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [validityType]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: '优先级',
                            name: 'priority',
                            width: 150
                        }]
                    }]
                }, {
                    layout: 'form',
                    defaultType: 'textarea',
                    defaults: {
                        width: 376
                    },
                    items: [{
                        fieldLabel: '注释',
                        name: 'remark',
                        width: 376
                    }]
                }]
            }]
        });
        return formPanel;
    },
    //创建窗口
    createWin: function(){
        return this.initWin(this.desktop, 500, 290, "回拨服务器信息");
    },
    //删除
    removeData: function(){
        this.remove('id', this.desktop, EzDesk.voip_route.Locale.DeleteText, this.moduleId,  'delete_server_list');
    },
	//编辑
    editData: function(){
        this.edit("edit_server_list");
    },
	//保存
	saveData: function(){
        this.save(this.desktop, '添加回拨服务器信息');
    },
    initComponent: function(){
    	this.store = new Ext.data.JsonStore({
            id: "id",
            url: this.baseUrl, //默认的数据源地址，继承时需要提供
            root: "data",
            totalProperty: "totalCount",
            remoteSort: true,
            fields: ["id", "alias", "port", "user_name", "password", "validity", "remark", "server_ip","resaler", "priority"],
            baseParams: {
    			method: 'get_server_list',
  				moduleId: this.moduleId
  			}
        });
    	
        var sm = new Ext.grid.CheckboxSelectionModel();
        this.cm = new Ext.grid.ColumnModel([new Ext.grid.RowNumberer(),//获得行号
            sm, {
            header: "ID",
            sortable: true,
            width: 50,
            dataIndex: "id"
        },{
            header: "别名",
            sortable: true,
            width: 110,
            dataIndex: "alias"
        }, {
            header: "端口",
            sortable: true,
            width: 50,
            dataIndex: "port"
        }, {
            header: "用户名",
            sortable: true,
            width: 80,
            dataIndex: "user_name"
        }, {
            header: "密码",
            sortable: true,
            width: 100,
            dataIndex: "password"
        }, {
            header: "优先级别",
            sortable: true,
            width: 40,
            dataIndex: "priority"
        }, {
            header: "服务器地址",
            sortable: true,
            width: 150,
            dataIndex: "server_ip"
        }, {
            header: "代理商",
            sortable: true,
            width: 150,
            dataIndex: "resaler"
        }, {
            header: "有效性",
            sortable: true,
            width: 50,
            dataIndex: "validity"
        }, {
            header: "注释",
            sortable: true,
            width: 150,
            dataIndex: "remark"
        }]);
        EzDesk.DialServerPanel.superclass.initComponent.call(this);
    }
});

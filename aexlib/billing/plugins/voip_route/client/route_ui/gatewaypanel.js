/**
 * @author cilun
 */
/**
 * 定义命名空间
 */
Ext.namespace("EzDesk");
//落地网关管理
EzDesk.GateWayPanel = Ext.extend(EzDesk.CrudPanel, {
    //id，需唯一
    id: "gatewayPanel",
    //标题
    title: "落地网关管理",
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
                data: [[0, '无效'], [1, '有效']]
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
        var routeType = new Ext.form.ComboBox({
            hiddenName: 'routing_type',
            store: new Ext.data.SimpleStore({
                fields: ['value', 'text'],
                data: [[0, '隐藏'], [1, '透传']]
            }),
            valueField: 'value',
            displayField: 'text',
            fieldLabel: '类型',
            editable: false,
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            emptyText: '请输入路由类型',
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
                        	id: "action",
                            name: "method",
                            value: "add_gateway_list"
             			},{ 
                        	xtype: "hidden",
                            name: "id"
             			},{
             				xtype: "hidden",
                            name: "moduleId",
                            value: this.moduleId
                        },{
                            fieldLabel: '路由IP',
                            name: 'routing_ip',
                            allowBlank: false,
                            blankText: '请务必填写该信息',
                            width: 150
                        }]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [routeType]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: '移除位数',
                            name: 'routing_strip',
                            width: 150,
                            regex: /^[0-9]+$/,
                            blankText: '移除位数以E.164号码为基础',
                            regexText: '输入格式不正确'
                        }]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: '增加前缀',
                            name: 'routing_prefix',
                            allowBlank: false,
                            blankText: '请输入需添加的前缀',
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
                            fieldLabel: '路由名称',
                            name: 'routing_name',
                            allowBlank: false,
                            blankText: '请务必填写该信息',
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
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        items: [{
                            fieldLabel: '替换主叫',
                            name: 'cli',
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
                            fieldLabel: 'Retries',
                            name: 'retries',
                            width: 150
                        }]
                    }, {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        items: [{
                            fieldLabel: 'Delay',
                            name: 'delay',
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
                        fieldLabel: '路由描述',
                        name: 'routing_remark',
                        width: 376
                    }]
                }]
            }]
        });
        return formPanel;
    },
    //创建窗口
    createWin: function(){
        return this.initWin(this.desktop, 500, 290, "落地网关管理");
    },
	 //删除
    removeData: function(){
        this.remove('id', this.desktop, EzDesk.voip_route.Locale.DeleteText , this.moduleId,  'delete_gateway_list');
    },
	//编辑
    editData: function(){
        this.edit('edit_gateway_list');
    },
	//保存
	saveData: function(){
        this.save(this.desktop, EzDesk.voip_route.Locale.DeleteText.AddGateWay);
    },
    initComponent: function(){
    	this.store = new Ext.data.JsonStore({
            id: "id",
            url: this.baseUrl, //默认的数据源地址，继承时需要提供
            root: "data",
            totalProperty: "totalCount",
            remoteSort: true,
            fields:  ["id", "routing_ip", "routing_type", "routing_strip", "routing_prefix", "routing_name", "routing_remark", "validity", "priority","cli","retries","delay"],
            baseParams: {
    			method: 'get_gateway_list',
				moduleId: this.moduleId
			}
    	});
    	
        var sm = new Ext.grid.CheckboxSelectionModel();
        this.cm = new Ext.grid.ColumnModel([new Ext.grid.RowNumberer(),//获得行号
 		sm, {
            header: "ID",
            sortable: true,
            width: 60,
            dataIndex: "id"
        }, {
            header: "路由IP",
            sortable: false,
            width: 150,
            dataIndex: "routing_ip"
        }, {
            header: "类型",
            sortable: false,
            width: 50,
            dataIndex: "routing_type"
        }, {
            header: "移除位数",
            sortable: false,
            width: 70,
            dataIndex: "routing_strip"
        }, {
            header: "增加前缀",
            sortable: false,
            width: 120,
            dataIndex: "routing_prefix"
        }, {
            header: "路由名称",
            sortable: false,
            width: 120,
            dataIndex: "routing_name"
        }, {
            header: "有效性",
            sortable: false,
            width: 70,
            dataIndex: "validity"
        }, {
            header: "重试次数",
            sortable: true,
            width: 70,
            dataIndex: "retries"
        }, {
            header: "延迟",
            sortable: true,
            width: 70,
            dataIndex: "delay"
        }, {
            header: "注释",
            sortable: false,
            width: 280,
            dataIndex: "routing_remark"
        }]);
        EzDesk.GateWayPanel.superclass.initComponent.call(this);
    }
});

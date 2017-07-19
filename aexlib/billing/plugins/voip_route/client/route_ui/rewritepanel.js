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
EzDesk.RewriteCrudPanel = Ext.extend(Ext.Panel, {
    closable: true,
    layout: "fit",
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
    initWin: function(desktop, width, height, title){
        var win = desktop.createWindow({
            width: width,
            height: height,
            buttonAlign: "center",
            title: title,
            layout: 'fit',
            modal: true,
            shadow: true,
            closeAction: "hide",
            items: [this.fp],
            buttons: [{
                text: "保存",
                handler: function(desktop){
            		this.save(desktop);
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
    save: function(desktop){
        this.fp.form.submit({
            waitMsg: '正在保存... ...',
            waitTitle: '请稍等...',
            url: this.baseUrl,
            method: 'POST',
            success: function(form_instance_create, action){
        	 	var obj = Ext.util.JSON.decode(action.response.responseText);
             	EzDesk.showMsg('全局号码替换', obj.message, this.desktop);
                this.closeWin();
               // this.store.reload();
            },
            failure: function(form_instance_create, action){
            	var obj = Ext.util.JSON.decode(action.response.responseText);
             	EzDesk.showMsg('全局号码替换', obj.message, this.desktop);
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
    edit: function(){
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
                this.fp.form.findField('method').setValue("edit_rewrite_list");
                this.fp.form.loadRecord(record); //往表单（fp.form）加载数据
            }
        }
        else {
            Ext.Msg.alert("提示", "请先选择要编辑的行!");
        }
    },
    //删除,pid为主键值
    remove: function(pid, desktop, moduleId){
        var store = this.store;
        var baseUrl = this.baseUrl;
        if (this.grid.selModel.hasSelection()) {
            var records = this.grid.selModel.getSelections();//得到被选择的行的数组
            var recordsLen = records.length;//得到行数组的长度
            var jsonStr = '{';
            for (var i = 0; i < recordsLen; i++) {
                var id = records[i].get(pid);
                if (i != 0) {
                    jsonStr += ',"' + id + '":' + id;
                }
                else {
                    jsonStr += '"' + id + '":' + id;
                }
            }
            jsonStr += '}';
            
            var winManager = this.desktop.getManager();
        	
        	var win = this.desktop.createWindow({
                title: 'Delete Record',
                frame: true,
                maximizable: false,
                width: 300,
                height: 180,
                bodyStyle: 'text-align:center;word-break:break-all',
                buttonAlign: 'center',
                html: '<br/><br/><br/>' + EzDesk.voip_route.Locale.Html,
                buttons: [{
    	            text:  EzDesk.voip_route.Locale.Close,
    	            handler: function(){
    	                this.ownerCt.ownerCt.close();
    	            }
    			},
    			{
    				text: EzDesk.voip_route.Locale.Delete,
    	            handler: function(){
	    				this.ownerCt.ownerCt.close();
		                Ext.Ajax.request({
							url: baseUrl,
							method: 'POST',
							params: {
								method: 'delete_rewrite_list',
	                            moduleId: moduleId,
	                            jsonStr: jsonStr
							},
							success: function(o){
								var obj = Ext.util.JSON.decode(o.responseText);
								store.reload();
	                            EzDesk.showMsg('Delete', obj.message, desktop);
								
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
        this.sm = new Ext.grid.CheckboxSelectionModel();
      
        var viewConfig = Ext.apply({
            forceFit: true
        }, this.gridViewConfig);
        
        EzDesk.RewriteCrudPanel.superclass.initComponent.call(this);
        
        this.grid = new Ext.grid.GridPanel({
            store: this.store,
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
                handler: this.removeData,
                scope: this
            }, '-', {
                text: '刷新',
                iconCls: 'refreshIcon',
                tooltip: '刷新纪录',
                handler: this.refresh,
                scope: this
            }, '   '],
            bbar: new Ext.PagingToolbar({
                pageSize: 15,
                store: this.store,
                displayInfo: true,
                displayMsg: '显示第 {0} - {1} 条记录，共 {2}条记录',
                emptyMsg: "没有记录"
            })
        });
        //双击时执行修改
        this.grid.on("celldblclick", this.edit, this);
        this.add(this.grid);
        this.store.load({
            params: {
                start: 0,
                limit: 15
            }
        });
      
    }
});

//全局号码替换
EzDesk.RewritePanel = Ext.extend(EzDesk.RewriteCrudPanel, {
    //id，需唯一
    id: "rewritePanel",
    //标题
    title: "全局号码替换",
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
                        	id: "method",
                            name: "method",
                            value: "add_rewrite_list"
             			},{ 
                        	xtype: "hidden",
                            name: "id"
             			},{
             				xtype: "hidden",
                            name: "moduleId",
                            value: this.moduleId
                        }, {
                            fieldLabel: '号码前缀',
                            name: 'prefix',
                            width: 150
                        }]
                    },  {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [{
                            fieldLabel: '号码新前缀',
                            name: 'new_prefix',
                            width: 150
                        }]
                    },  {
                        columnWidth: .5,
                        layout: 'form',
                        defaultType: 'textfield',
                        defaults: {
                            width: 150
                        },
                        items: [validityType]
                    }]
                }]
            }]
        });
        return formPanel;
    },
    //创建窗口
    createWin: function(){
        return this.initWin(this.desktop, 500, 170, "全局号码替换");
    },
    //删除
    removeData: function(){
        this.remove('id', this.desktop, this.moduleId);
    },
    initComponent: function(){
    	this.store = new Ext.data.JsonStore({
             id: "id",
             url: this.baseUrl, //默认的数据源地址，继承时需要提供
             root: "data",
             totalProperty: "totalCount",
             remoteSort: true,
             fields: ["id", "prefix", "new_prefix", "validity"],
             baseParams: {
    			method: 'get_rewrite_list',
 				moduleId: this.moduleId
 			}
        });
        var sm = new Ext.grid.CheckboxSelectionModel();
        this.cm = new Ext.grid.ColumnModel([
            new Ext.grid.RowNumberer(),//获得行号
            sm, 
        {
            header: "全局号码前缀",
            sortable: true,
            width: 100,
            dataIndex: "prefix",
            resizable: true,
            align: 'center'
        }, {
            header: "全局号码新前缀",
            sortable: true,
            width: 150,
            dataIndex: "new_prefix",
            resizable: true,
            align: 'center'
        }, {
            header: "有效性",
            sortable: true,
            width: 150,
            dataIndex: "validity",
            resizable: true,
            align: 'center'
        }]);
        EzDesk.RewritePanel.superclass.initComponent.call(this);
    }
});

/**
 *	Create Ez Message BOX
 * 	wirter:  lion wang
 * 	version: 1.0
 *  time: 2010-04-19
 *  last time: 2010-04-19
 */
EzDesk.showMsg = function(tl, msg, desktop){
    var win = desktop.createWindow({
        title: tl,
        frame: true,
        maximizable: false,
        width: 300,
        height: 180,
        bodyStyle: 'text-align:center;word-break:break-all',
        buttonAlign: 'center',
        html: '<br/><br/><br/>' + msg,
        buttons: [{
            text: 'OK',
            handler: function(){
                this.ownerCt.ownerCt.close();
            }
        }]
    });
    win.show();
};

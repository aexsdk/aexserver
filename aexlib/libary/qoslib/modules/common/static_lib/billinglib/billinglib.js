Ext.namespace('EzDesk');

EzDesk.set_cmp_value= function(cmp, value){
    var cmp_o = Ext.getCmp(cmp);
    if (cmp_o) 
        cmp_o.setValue(value);
};
EzDesk.get_cmp_value= function(cmp){
    var cmp_o = Ext.getCmp(cmp);
    if (cmp_o) 
        return cmp_o.getValue();
    else 
        return '';
};

EzDesk.msg = function(title, format){
    if(!msgCt){
        msgCt = Ext.DomHelper.insertFirst(document.body, {id:'msg-div'}, true);
    }
    msgCt.alignTo(document, 't-t');
    var s = String.format.apply(String, Array.prototype.slice.call(arguments, 1));
    var m = Ext.DomHelper.append(msgCt, {html:createBox(title, s)}, true);
    m.slideIn('t').pause(1).ghost("t", {remove:true});
};

EzDesk.clone = function(o) {
    if(!o || 'object' !== typeof o) {
        return o;
    }
    if('function' === typeof o.clone) {
        return o.clone();
    }
    var c = '[object Array]' === Object.prototype.toString.call(o) ? [] : {};
    var p, v;
    for(p in o) {
        if(o.hasOwnProperty(p)) {
            v = o[p];
            if(v && 'object' === typeof v) {
                c[p] = EzDesk.clone(v);
            }
            else {
                c[p] = v;
            }
        }
    }
    return c;
}; // eo function clone  

EzDesk.CenterLayout = Ext.extend(Ext.layout.FitLayout, {
	// private
    setItemSize : function(item, size){
        //this.container.addClass('ux-layout-center');
        item.addClass('ux-layout-center-item');
        if(item && size.height > 0){
            if(item.width){
                size.width = item.width;
            }
            if(item.height)
            	size.height = item.height;
            item.setSize(size);
        }
    }
});

Ext.Container.LAYOUTS['center'] = EzDesk.CenterLayout;

/**
 * runjs : 加载javascript文本的函数，函数作了异常处理，如果文本发生错误则会用错误窗口提示。
 *  参数
 *  	js_text：ajax返回的文本
 *  	s:config的内容
 */
runjs = function(js_text,s){
	try {
		if(js_text != '')
			eval(js_text);
    } 
    catch (err) {
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
};

/**
 * parse_ajax_response
 * 	这个函数主要用于对Ajax返回的数据进行校验，如果返回了失败如
 * 		success=false
 * 		msg or message 为错误字符串，如果没有指定，显示空字符串的错误窗口
 * 		onfunc 为点击错误窗口ok键后执行的js脚本，如果没有指定则不会做任何操作。
 * 	参数
 * 		r：ajax返回的文本
 * 		s：config
 *    		s.success : 当success=true时执行的函数，如果未指定，则根据msg或者message字段如果提供了则出现提示对话框，否则不做任何操作。
 *    		s.fail ： 当success=false时执行的函数，如果未指定，则根据msg或者message字段显示错误提示，否则什么也不做
 *    				如果返回有okfunc则在错误提示按ok键后执行onfunc的js脚本
 */
parse_ajax_response = function(r,s,sender){
    if(!r || r == ''){
    	m = null;
    	if(s.ownerModule && s.ownerModule.app && s.ownerModule.app.getDesktop())  
    		m = s.ownerModule.app.getDesktop().getManager();
    	Ext.Msg.show({
            title: lang_tr.Error,
            buttons: Ext.Msg.OK,
            icon: Ext.MessageBox.ERROR,
            msg: lang_tr.update_error,
            manager: m
        });
    	return;
    }
    var x = Ext.decode(r); //按照json格式解码返回值
    if (x) {
        if (x.success) {
            if (s.success) 
                s.success(sender, x);
            else {
            	if(x.message || x.msg) {
            		m = null;
                	if(s.ownerModule && s.ownerModule.app && s.ownerModule.app.getDesktop()) 
                		m = s.ownerModule.app.getDesktop().getManager();
                    
	                Ext.Msg.show({
	                    title: lang_tr.Hint,
	                    buttons: Ext.Msg.OK,
	                    icon: Ext.MessageBox.INFO,
	                    msg: x.message || x.msg,
	                    manager: null
	                });
            	}
            }
        }
        else {
            if (s.fail) 
                s.fail(sender, x);
            else {
            	if(x.message || x.msg) {
            		m = null;
                	if(s.ownerModule && s.ownerModule.app && s.ownerModule.app.getDesktop()) 
                		m = s.ownerModule.app.getDesktop().getManager();
                    
	                Ext.Msg.show({
	                    title: lang_tr.Error,
	                    buttons: Ext.Msg.OK,
	                    icon: Ext.MessageBox.ERROR,
	                    msg: x.message || x.msg,
	                    manager: m
	                });
	                if(x.okfunc) {
	                	runjs(x.okfunc,s);
	                }
            	}
            }
        }
    }
    else {
    	runjs(r,s);
    }
};

send_ajax = function(config,s){ 
    if(config.showMask)
    	config.showMask(config.msg,s);
    Ext.Ajax.request({
        waitMsg: config.waitMsg,
        url: config.url,
        params: config.params,
        failure: function(response, options){
            if(config.hideMask)
            	config.hideMask(s);
            Ext.MessageBox.alert(lang_tr.Warning, lang_tr.ConnectServerError);
        },
        success: function(o){
        	if(config.hideMask)
        		config.hideMask(s);
        	parse_ajax_response(o.responseText,config,s);
        },
        scope: s
    });
};

/*
 *CRUD Desktop面板基类
 */
EzDesk.CrudPanel = Ext.extend(Ext.Panel, {
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
    initWin: function(desktop, width, height, title){
		var win = desktop.createWindow({
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
                handler: function(desktop){
        			this.saveData(desktop, title);
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
    save: function(desktop, title){
        var id = this.fp.form.findField("id").getValue();
        this.fp.form.submit({
        	 waitMsg: '正在保存... ...',
             waitTitle: '请稍等...',
             url: this.baseUrl,
             method: 'POST',
             success: function(form_instance_create, action){
         	 	var obj = Ext.decode(action.response.responseText);
         	 	if(obj)
         	 		EzDesk.showMsg(title, obj.message, this.desktop);
         	 	else
         	 		EzDesk.showMsg(title, action.response.responseText, this.desktop);
              	this.closeWin();
				this.store.reload();
             },
             failure: function(form_instance_create, action){
             	var obj = Ext.decode(action.response.responseText);
             	if(obj)
         	 		EzDesk.showMsg(title, obj.message, this.desktop);
         	 	else
         	 		EzDesk.showMsg(title, action.response.responseText, this.desktop);
              	
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
    remove: function(pid, desktop, title, moduleId, action){
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
                title: title,
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
		                		method:  action,
	                            moduleId: moduleId,
	                            jsonStr: jsonStr
							},
							success: function(o){
								var obj = Ext.util.JSON.decode(o.responseText);
	                            EzDesk.showMsg(title, obj.message, desktop);
								
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
    //初始化GRID面板
    initComponent: function(){
        EzDesk.CrudPanel.superclass.initComponent.call(this);
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
	                handler: this.editData,
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
	                pageSize: 20,
	                store: this.store,
	                displayInfo: true,
	                displayMsg: '显示第 {0} - {1} 条记录，共 {2}条记录',
	                emptyMsg: "没有记录"
	            })
	        });
		}
        
        //双击时执行修改
       	//this.grid.on("celldblclick", this.edit, this);
        this.add(this.grid);
        this.store.load({
            params: {
                start: 0,
                limit: 20
            }
        });
    }
});

/***
 * EzDesk.BillingPanel
 * 		计费系统重要的面板基础类。该基础类为窗口或者面板提供了发送Ajax请求的方法，当开始发送请求时使用掩码提示等待信息。
 * 完成后会根据返回结果作处理：
 * 		如果Ajax失败提示失败原因，如果成功则按JSON解析返回结果，根据success的结果确定请求返回的代码，如果success=false则会
 * 显示msg或者message作为错误信息。
 * 		如果返回空字符串，则提示请求回应为空的信息，这有可能是服务端代码出现错误。如果解析JSON出错，则尝试按照JS解析返回代码。
 * 
 * 方法
 * 		send_request(config) ： 发送Ajax请求。
 * 			config参数如下
 			{
 				waitMsg: '发送请求时显示的等待字符串',
            	params: {
                	method: '请求的方法',
                	moduleId: '模块代码',
                	...		//扩展的参数，这些参数会根其他参数一起作为POST参数提交
            	}
            	//s BillingPanel对象  x 回应数据的JSON对象
            	,success: function(s,x){
					s.hideMask();
                }
            }
        showMask: function(msg) ： 显示等待提示框。
        hideMask: function()  ：隐藏等待提示框。
        set_cmp_value: function(cmp, value) ：根据组件ID设置组件的值。
        get_cmp_value: function(cmp) ： 根据组件ID获得组件的值。
        edit_object : function(s,o,m,func) ： 编辑JSON对象，s为作用域对象，o为JSON对象，func当选择保存按钮时调用的函数。
        	func(s,o_json) ：s为编辑窗口，o_json为编辑的对象的JSON编码后的字符串。
 * 
 */

EzDesk.BillingPanel = Ext.extend(Ext.Panel, {
    border:false,
	progressIndicator: null,
    selectedId: null,
    onRender: function(ct, position){
        EzDesk.BillingPanel.superclass.onRender.call(this, ct, position);
        var el = this.body;
        
        if (el.dom) 
            el = el.dom;
        if (el.parentNode) 
            el = el.parentNode;
        this.progressIndicator = new Ext.LoadMask(Ext.get(el), {
            msg: lang_tr.plswait
        });
    },
    send_request: function(config){
    	config = config || {};
    	Ext.apply(config,{
    		ownerModule:this.ownerModule
    		,url:this.ownerModule.app.connection
    		,showMask:this.showMask
    		,hideMask:this.hideMask
    	});
    	send_ajax(config,this);
    },
    hideMask: function(s){
    	if(!s)
    		s = this;
        s.progressIndicator.hide();
    },
    showMask: function(msg,s){
    	if(!s)s = this;
        var pi = s.progressIndicator;
        
        if (msg) {
            pi.msg = msg;
        }
        pi.show();
    },
    set_cmp_value: function(cmp, value){
        var cmp_o = Ext.getCmp(cmp);
        if (cmp_o) 
            cmp_o.setValue(value);
    },
    get_cmp_value: function(cmp){
        var cmp_o = Ext.getCmp(cmp);
        if (cmp_o) 
            return cmp_o.getValue();
        else 
            return '';
    }
    ,edit_object : function(s,o,m,func){
    	//s 作用域对象
    	//o 要编辑的对象
    	//m 模块对象
    	var desktop = m.app.getDesktop();

    	var h = parseInt(desktop.getWinHeight() * 0.9);
    	var w = parseInt(desktop.getWinWidth() * 0.7);
    	if (h > 360) {
    		h = 320;
    	}
    	if (w > 480) {
    		w = 480;
    	}		
    	var tp = new Ext.grid.PropertyGrid({
            title : lang_tr.Edit,
            id:'property_grid',
            enableHdMenu: true
            ,source:o
        });
    	//tp.setSource(o);
    	var win_test = desktop.createWindow({
    		animCollapse : false,
    		constrainHeader : true,
    		bodyStyle : 'padding:2px;',
    		iconCls : 'm-carriers-icon',
    		id:'win_edit_object',
    		items : [tp],
    		layout : 'fit',
    		shim : false,
    		title : lang_tr.Edit,
    		height : h,
    		width : w
    		,buttons:[{
    			xtype:"button"
        			,text : lang_tr.Save
        			,handler:function(){
		    			var g = Ext.getCmp('property_grid');
						if(g && func)
							func(this,Ext.encode(g.getSource()));
    					}
        			,scope:s
        		},{
    			xtype:"button"
    			,text : lang_tr.Close
    			,handler:function(){
    					var win = Ext.getCmp('win_edit_object');
    					if(win)
    						win.close();
    				}
    			,scope:win_test
    		}]
    	});
    	win_test.show();
    }
});

Ext.reg('billing-panel', EzDesk.BillingPanel);

/**
 * EzDesk.Nav
 * 		导航菜单的基类，菜单分为三部分：图标48x48、菜单标题、菜单说明。
 * 使用方法
 * 		继承此类后实现一个actions对象他是菜单的内容，可以在locale里面本地化此对象。
 * 如：
 * 	"data": {
		"nav": [
		     {
		     	"cls": "ez-normal-icon"
		     	, "id": "viewNormals"
		     	, "text": "基本信息"
		     	, "description": "查看系统基本参数和登录信息"
		     }
		     , {
		        "cls": "ez-traffic-icon"
		        , "id": "viewTraffics"
		        , "text": "流量统计"
		        , "description": "查看系统一周流量统计信息"
		     }
		     , {
		        "cls": "ez-recharge-icon"
		        , "id": "viewRecharges"
		        , "text": "充值统计"
		        , "description": "查看系统一周用户充值统计信息"
		     }
		]
   }
   然后在JS
   	new EzDesk.Summary.Nav({
					ownerModule : this,
					id:'summary-win-card-menu',
					title : this.locale.Home,
					width : 200
					});
					
	EzDesk.Summary.Nav = Ext.extend(EzDesk.Nav,{
		actions : {
				'viewNormals' : function(ownerModule) {
					ownerModule.viewNormals();
				},
				'viewTraffics' : function(ownerModule) {
					ownerModule.viewTraffics();
				},
				'viewRecharges' : function(ownerModule) {
					ownerModule.viewRecharges();
				}
			}
	});
 */
EzDesk.Nav = Ext.extend(EzDesk.BillingPanel,{
	autoScroll : true,
	bodyStyle : 'padding:15px;',
	border : false,
	split : true,
	width : 200,
	afterRender : function() {
		var tpl = new Ext.XTemplate(
				'<ul class="pref-nav-list">',
				'<tpl for=".">',
				'<li><div>',
				'<div class="prev-link-item-icon"><img src="' + Ext.BLANK_IMAGE_URL + '" class="{cls}"/></div>',
				'<div class="prev-link-item-txt"><a id="{id}" href="#">{text}</a><br />{description}</div>',
				'<div class="x-clear"></div>', '</div></li>',
				'</tpl>', '</ul>');
		tpl.overwrite(this.body,this.ownerModule.locale.data.nav);

		this.body.on({
			'mousedown' : {
				fn : this.doAction,
				scope : this,
				delegate : 'a'
			},
			'click' : {
				fn : Ext.emptyFn,
				scope : null,
				delegate : 'a',
				preventDefault : true
			}
		});

		EzDesk.Nav.superclass.afterRender.call(this); 
	}
	,doAction : function(e, t) {
		e.stopEvent();
		if(this.actions && this.actions[t.id])
			this.actions[t.id](this.ownerModule);
	}
});

/**
 * Creates new MetaForm
 * @constructor
 * @param {Object} config A config object
 */
EzDesk.MetaForm = Ext.extend(Ext.form.FormPanel, {
    border:false,
    set_cmp_value: function(cmp, value){
        var cmp_o = Ext.getCmp(cmp);
        if (cmp_o) 
            cmp_o.setValue(value);
    },
    get_cmp_value: function(cmp){
        var cmp_o = Ext.getCmp(cmp);
        if (cmp_o) 
            return cmp_o.getValue();
        else 
            return '';
    },

    // {{{
    // config options
    moduleId:''
    ,method:''
    ,connection: ''
     /**
     * @cfg {Boolean/Object} autoInit
     * Load runs immediately after the form is rendered if autoInit is set. In the case of boolean true
     * the load runs with {meta:true} and in the case of object the load takes autoInit as argument 
     * (defaults to true)
     */
     ,autoInit:true

    /**
     * @cfg {Object} baseParams
     * Params sent with each request (defaults to undefined)
     */

    /**
     * @cfg {Boolean} border
     * True to display the borders of the panel's body element, false to hide them (defaults to false).  By default,
     * the border is a 2px wide inset border, but this can be further altered by setting {@link #bodyBorder} to false.
     */
    ,border:false

    /**
     * @cfg {Boolean} focusFirstField
     * True to try to focus the first form field on metachange (defaults to true)
     */
    ,focusFirstField:true

    /**
     * True to render the panel with custom rounded borders, false to render with plain 1px square borders (defaults to true).
     */
    ,frame:true

    /**
     * @cfg {String} loadingText
     * Localizable text for "Loading..."
     */
    ,loadingText:lang_tr.Loading

    /**
     * @cfg {String} savingText
     * Localizable text for "Saving..."
     */
    ,savingText:lang_tr.saving

    /**
     * @cfg {Number} buttonMinWidth 
     * Minimum width of buttons (defaults to 90)
     */
    ,buttonMinWidth:90

    /**
     * @cfg {Number} columnCount
     * MetaForm has a column layout insise with this number of columns (defaults to 1)
     */
    ,columnCount:1

    /**
     * @cfg {Array} createButtons Array of buttons to create.
     * Valid values are ['meta', 'load', defaults', 'reset', 'save', 'ok', 'cancel'] or any subset of them
     * (defaults to undefined)
     */

    /**
     * @cfg {Object} data
     * Data object bound to this form. If both {@link #metaData} and data are set at config time
     * the data is bound and loaded on form render after metaData processing.  Read-only at run-time.
     */

    /**
     * True if meta data has been processed and fields have been created, false otherwise (read-only)
     * @property hasMeta
     * @type Boolean
     */
    ,hasMeta:false

    /**
     * @cfg {Array} ignoreFields Array of field names to ignore when received in metaData (defaults to undefined)
     */

    /**
     * @cfg {Object} metaData Meta data used to configure this form. If set it takes precedence over {@link #autoInit}
     * and server is not accessed to get meta data.
     */

    /**
     * @cfg {String} method Sever access method. 'GET' or 'POST' (if undefined 'POST' is used)
     */

    /**
     * @cfg {String} url URL for loading an submitting the form (defaults to undefined)
     */
    // }}}
    // {{{
    /**
     * Runs after the meta data has been processed and the form fields have been created.
     * Override it to add your own extra processing if you need (defaults to Ext.emptyFn)
     * @method afterMetaChange
     */
    ,afterMetaChange:Ext.emptyFn
    // }}}
    // {{{
    /**
     * Runs after bound data is updated. Override to add any extra processing you may need
     * after the bound data is updated (defaults to Ext.emptyFn)
     * @param {Ext.ux.form.MetaForm} form This form
     * @param {Object} data Updated bound data
     */
    ,afterUpdate:Ext.emptyFn
    // }}}
    // {{{
    ,applyDefaultValues:function(o) {
        if('object' !== typeof o) {
            return;
        }
        for(var name in o) {
            if(o.hasOwnProperty(name)) {
                var field = this.form.findField(name);
                if(field) {
                    field.defaultValue = o[name];
                }
            }
        }
    } // eo function applyDefaultValues
    // }}}
    // {{{
    /**
     * @private
     * Changes order of execution in Ext.form.Action.Load::success
     * to allow reading of data in this server request (otherwise data would
     * be loaded to the form before onMetaChange is run from actioncomplete event
     */
    ,beforeAction:function(form, action) {
        action.success = function(response) {
            var result = this.processResponse(response);
            if(result === true || !result.success || !result.data){
                this.failureType = Ext.form.Action.LOAD_FAILURE;
                this.form.afterAction(this, false);
                return;
            }
            // original
            this.form.afterAction(this, true);
            this.form.clearInvalid();
            this.form.setValues(result.data);
        };
    } // eo function beforeAction
    // }}}
    // {{{
    /**
     * Backward compatibility function, calls {@link #bindData} function
     * @param {Object} data 
     * A reference to an external data object. The idea is that form can display/change an external object
     */
    ,bind:function(data) {
        this.bindData(data);
    } // eo function bind
    // }}}
    // {{{
    /**
     * @param {Object} data 
     * A reference to an external data object. The idea is that form can display/change an external object
     */
    ,bindData:function(data) {
        this.data = data;
        this.form.setValues(this.data);
    } // eo function bindData
    // }}}
    // {{{
    /**
     * Closes the parent if it is a window
     * @private
     */
    ,closeParentWindow:function() {
        if(this.ownerCt && this.ownerCt.isXType('window')) {
            this.ownerCt[this.ownerCt.closeAction]();
        }
    } // eo function closeParentWindow
    // }}}
    // {{{
    /**
     * Returns button thet has the given name
     * @param {String} name Button name
     * @return {Ext.Button/Null} Button found or null if not found
     */
    ,findButton:function(name) {
        var btn = null;
        Ext.each(this.buttons, function(b) {
            if(name === b.name) {
                btn = b;
            }
        });
        return btn;
    } // eo function findButton
    // }}}
    // {{{
    /**
     * Returns the button. This funcion is undefined by default, supply it if you want an automated button creation.
     * @method getButton
     * @param {String} name A symbolic button name
     * @param {Object} config The button config object
     * @return {Ext.Button} The created button
     */
    // getButton
    // }}}
    // {{{
    /**
     * override this if you want a special buttons config
     */
    ,getButtons:function() {
        var buttons = [];
        if(Ext.isArray(this.createButtons)) {
            Ext.each(this.createButtons, function(name){
                buttons.push(this.getButton(name, {
                     handler:this.onButtonClick
                    ,scope:this
                    ,minWidth:this.buttonMinWidth
                    ,name:name
                }));
            }, this);
        }
        return buttons;
    } // eo function getButtons
    // }}}
    // {{{
    ,getOptions:function(o) {
        o = o || {};
        var options = {
            method:this.method || 'POST'
            ,url: this.connection
            ,failure: function(form, action){
        		parse_ajax_response(action.response.responseText,{
        			ownerModule:form.ownerModule
        		},this);
	        }
        };
        Ext.apply(options, o);
        var params = this.baseParams ? EzDesk.clone(this.baseParams) : {};
        options.params = Ext.apply(params, o.params);
        return options;
    } // eo function getOptions
    // }}}
    // {{{
    /**
     * Returns values calling the individual fields' getValue() methods
     * @return {Object} object with name/value pairs
     */
    ,getValues:function() {
        var values = {};
        this.form.items.each(function(f) {
            values[f.name] = f.getValue();
        });
        return values;
    } // eo function getValues
    // }}}
    // {{{
    ,initComponent:function() {

        var config = {
            items:this.items || {}
        };
        if('function' === typeof this.getButton) {
            config.buttons = this.getButtons();
        }

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        // call parent
        EzDesk.MetaForm.superclass.initComponent.apply(this, arguments);
        
        // add events
        this.addEvents(
            /**
             * @event beforemetachange
             * Fired before meta data is processed. Return false to cancel the event
             * @param {Ext.ux.form.MetaForm} form This form
             * @param {Object} metaData The meta data being processed
             */
             'beforemetachange'
            /**
             * @event metachange
             * Fired after meta data is processed and form fields are created.
             * @param {Ext.ux.form.Metadata} form This form
             * @param {Object} metaData The meta data processed
             */
            ,'metachange'
            /**
             * @event beforebuttonclick
             * Fired before the button click is processed. Return false to cancel the event
             * @param {Ext.ux.form.MetaForm} form This form
             * @param {Ext.Button} btn The button clicked
             */
            ,'beforebuttonclick'
            /**
             * @event buttonclick
             * Fired after the button click has been processed
             * @param {Ext.ux.form.MetaForm} form This form
             * @param {Ext.Button} btn The button clicked
             */
            ,'buttonclick'
        );

        // install event handlers on basic form
        this.form.on({
             beforeaction:{scope:this, fn:this.beforeAction}
            ,actioncomplete:{scope:this, fn:function(form, action) {
                // (re) configure the form if we have (new) metaData
                if('load' === action.type && action.result.metaData) {
                    this.onMetaChange(this, action.result.metaData);
                }
                // update bound data on successful submit
                else if('submit' === action.type) {
                    this.updateBoundData();
                }
            }}
        });
        this.form.trackResetOnLoad = true;

    } // eo function initComponent
    // }}}
    // {{{
    ,load:function(o) {
    	o = o || {};
        var options = this.getOptions(o);
        if(this.loadingText) {
            options.waitMsg = this.loadingText;
        }
        this.form.load(options);
    } // eo function load
    // }}}
    // {{{
    /**
     * Called in the scope of this form when user clicks a button. Override it if you need a different
     * functionality of the button handlers.
     * <i>Note: Buttons created by MetaForm has name property that matches {@link #createButtons} names</i>
     * @param {Ext.Button} btn The button clicked. 
     * @param {Ext.EventObject} e Click event
     */
    ,onButtonClick:function(btn, e) {
        if(false === this.fireEvent('beforebuttonclick', this, btn)) {
            return;
        }
        switch(btn.name) {
            case 'meta':
                this.load({params:{meta:true}});
            break;

            case 'load':
                this.load({params:{meta:!this.hasMeta}});
            break;

            case 'defaults':
                this.setDefaultValues();
            break;

            case 'reset':
                this.form.reset();
            break;
            case 'submit':
            case 'save':
                this.updateBoundData();
                this.submit();
                this.closeParentWindow();
            break;

            case 'ok':
                this.updateBoundData();
                this.closeParentWindow();
            break;

            case 'cancel':
                this.closeParentWindow();
            break;
        }
        this.fireEvent('buttonclick', this, btn);
    } // eo function onButtonClick
    // }}}
    // {{{
    /**
     * Override this if you need a custom functionality
     *
     * @param {Ext.FormPanel} this
     * @param {Object} meta Metadata
     * @return void
     */
    ,onMetaChange:function(form, meta) {
        if(false === this.fireEvent('beforemetachange', this, meta)) {
            return;
        }
        this.removeAll();
        this.hasMeta = false;

        // declare varables
        var columns, colIndex, tabIndex, ignore = {};

        // add column layout
        this.add(new Ext.Panel({
             layout:'column'
            ,anchor:'100%'
            ,border:false
            ,defaults:(function(){
                this.columnCount = meta.formConfig ? meta.formConfig.columnCount || this.columnCount : this.columnCount;
                return Ext.apply({}, meta.formConfig || {}, {
                     columnWidth:1/this.columnCount
                    ,autoHeight:true
                    ,border:false
                    ,hideLabel:true
                    ,layout:'form'
                });
            }).createDelegate(this)()
            ,items:(function(){
                var items = [];
                for(var i = 0; i < this.columnCount; i++) {
                    items.push({
                         defaults:this.defaults
                        ,listeners:{
                            // otherwise basic form findField does not work
                            add:{scope:this, fn:this.onAdd}
                        }
                    });
                }
                return items;
            }).createDelegate(this)()
        }));
        
        columns = this.items.get(0).items;
        colIndex = 0;
        tabIndex = 1;

        if(Ext.isArray(this.ignoreFields)) {
            Ext.each(this.ignoreFields, function(f) {
                ignore[f] = true;
            });
        }
        // loop through metadata colums or fields
        // format follows grid column model structure
        Ext.each(meta.columns || meta.fields, function(item) {
            if(true === ignore[item.name]) {
                return;
            }
            var config = Ext.apply({}, item.editor, {
                 name:item.name || item.dataIndex
                ,fieldLabel:item.fieldLabel || item.header
                ,defaultValue:item.defaultValue
                ,xtype:item.editor && item.editor.xtype ? item.editor.xtype : 'textfield'
            });

            // handle regexps
            if(config.editor && config.editor.regex) {
                config.editor.regex = new RegExp(item.editor.regex);
            }

            // to avoid checkbox misalignment
            if('checkbox' === config.xtype) {
                Ext.apply(config, {
                      boxLabel:' '
                     ,checked:item.defaultValue
                });
            }
            if(meta.formConfig.msgTarget) {
                config.msgTarget = meta.formConfig.msgTarget;
            }

            // add to columns on ltr principle
            config.tabIndex = tabIndex++;
            columns.get(colIndex++).add(config);
            colIndex = colIndex === this.columnCount ? 0 : colIndex;

        }, this);
        if(this.rendered && 'string' !== typeof this.layout) {
            this.el.setVisible(false);
            this.doLayout();
            this.el.setVisible(true);
        }
        this.hasMeta = true;
        if(this.data) {
            // give DOM some time to settle
            (function() {
                this.form.setValues(this.data);
            }.defer(1, this))
        }
        this.afterMetaChange();
        this.fireEvent('metachange', this, meta);

        // try to focus the first field
        if(this.focusFirstField) {
            var firstField = this.form.items.itemAt(0);
            if(firstField && firstField.focus) {
                var delay = this.ownerCt && this.ownerCt.isXType('window') ? 1000 : 100;
                firstField.focus(firstField.selectOnFocus, delay);
            }
        }
    } // eo function onMetaChange
    // }}}
    // {{{
    ,onRender:function() {
        // call parent
        EzDesk.MetaForm.superclass.onRender.apply(this, arguments);

        this.form.waitMsgTarget = this.el;

        if(this.metaData) {
            this.onMetaChange(this, this.metaData);
            if(this.data) {
                this.bindData(this.data);
            }
        }
        else if(true === this.autoInit) {
            this.load(this.getOptions({params:{meta:true}}));
        }
        else if ('object' === typeof this.autoInit) {
            this.load(this.autoInit);
        }

    } // eo function onRender
    // }}}
    // {{{
    /**
     * @private
     * Removes all items from both formpanel and basic form
     */
    ,removeAll:function() {
        // remove border from header
        var hd = this.body.up('div.x-panel-bwrap').prev();
        if(hd) {
            hd.applyStyles({border:'none'});
        }
        // remove form panel items
        this.items.each(this.remove, this);

        // remove basic form items
        this.form.items.clear();
    } // eo function removeAllItems
    // }}}
    // {{{
    ,reset:function() {
        this.form.reset();
    } // eo function reset
    // }}}
    // {{{
    ,setDefaultValues:function() {
        this.form.items.each(function(item) {
            item.setValue(item.defaultValue);
        });
    } // eo function setDefaultValues
    // }}}
    // {{{
    ,submit:function(o) {
        var options = this.getOptions(o);
        if(this.savingText) {
            options.waitMsg = this.savingText;
        }
        this.form.submit(options);
    } // eo function submit
    // }}}
    // {{{
    /**
     * Updates bound data
     */
    ,updateBoundData:function() {
        if(this.data) {
            Ext.apply(this.data, this.getValues());
            this.afterUpdate(this, this.data);
        }
    } // eo function updateBoundData
    // }}}
    // {{{
    ,beforeDestroy:function() {
        if(this.data) {
            this.data = null;
        }
        EzDesk.MetaForm.superclass.beforeDestroy.apply(this, arguments);
    } // eo function beforeDestroy
    // }}}

});

// register xtype
Ext.reg('metaform', EzDesk.MetaForm);


EzDesk.BillingStore = Ext.extend(Ext.data.GroupingStore, {
    connection: '',
    moduleId: '',
	filtter: '',
	status: '',
    desktop: null,
    sm: null,
    autoLoad: true,
    load:function(o){
		o = o || {
        	callback :function(r,options,success) {	
				if(!success && this.reader.jsonData){
					if(this.desktop) {
						var notifyWin = this.desktop.showNotification({
					        html: this.reader.jsonData.message || this.reader.jsonData.msg
							, title: lang_tr.Error
					      });
					}else {
						Ext.Msg.show({
				            title: lang_tr.Error,
				            buttons: Ext.Msg.OK,
				            icon: Ext.MessageBox.ERROR,
				            msg: this.reader.jsonData.message || this.reader.jsonData.msg
				        });
					}
					this.removeAll();
				}
			}
	    	,scope:this
	    };
		EzDesk.BillingStore.superclass.load.call(this,o);
	},
    constructor: function(config){
        config = config || {};
        EzDesk.BillingStore.superclass.constructor.call(this, config);
        this.addListener('load', function(s, r, o){
            if (!s.reader.jsonData.success && s.reader.jsonData) {
                if(this.desktop) {
                	var notifyWin = this.desktop.showNotification({
                		html: s.reader.jsonData.message || s.reader.jsonData.msg,
                		title: lang_tr.Error
                	});
                }else {
					Ext.Msg.show({
			            title: lang_tr.Error,
			            buttons: Ext.Msg.OK,
			            icon: Ext.MessageBox.ERROR,
			            msg: this.reader.jsonData.message || this.reader.jsonData.msg
			        });
				}
            }else {
                if (this.sm && s.data.length > 0) {
                    this.sm.selectRow(0);
                }
            }
        }, this, true);
        this.addListener('beforeload', function(s, o){
            for (var key in s.params) {
                s.setBaseParam(key, s.params[key]);
            }
        }, this, true);
        this.proxy = new Ext.data.HttpProxy({
            scope: this,
            method: 'POST',
            url: this.connection
        });
        Ext.apply(this.baseParams, {
            method: this.method,
            moduleId: this.moduleId,
            filtter: this.filtter,
            status: this.status
            , domain: oem_domain
			, resaler :os_resaler
        });
    }
});


Ext.ns('EzDesk.Carriers');
/*
 * 定义运营商的相关操作,如果附加提交的参数使用：
 *    store.setBaseParam(key,value)
 * */
EzDesk.Carriers.ListStore = Ext.extend(EzDesk.BillingStore, {
    method: 'viewAllCarriers',
    reader: new Ext.data.JsonReader({
    	totalProperty: 'total',
        root: 'carriers',
        idProperty: 'id',
        messageProperty: 'message',
        successProperty: 'success',
        fields: [{
            name: 'id'
        }, {
            name: 'name'
        }]
    })
});

EzDesk.Carriers.ColumnModel = Ext.extend(Ext.grid.ColumnModel, {
    constructor: function(config){
        config = config || {};
        EzDesk.Carriers.ColumnModel.superclass.constructor.call(this, config);
        /*l = config.locale;
        Ext.apply(this.columns, [{
            id: 'id',
            header: l.field.id,
            width: 60,
            dataIndex: 'id'
        }, {
            id: 'name',
            header: l.field.Name,
            dataIndex: 'name'
        }]);*/
    },
    defaults: {
        width: 150,
        sortable: true
    }
});

EzDesk.Carriers.nameStore = Ext.extend(EzDesk.BillingStore, {
    method: 'get_carrier_name',
    reader: new Ext.data.JsonReader({
        idProperty: 'carrier_name',
        totalProperty: 'totalCount',
        root: 'data',
        messageProperty: 'message',
        successProperty: 'success',
        fields: [{
            name: 'carrier_name',
            type: 'string'
        }, {
            name: 'carrier_id',
            type: 'string'
        }]
    })
});

Ext.ns('EzDesk.Products');
/*
 * 终端产品的列表数据源
 * */
EzDesk.Products.ListStore = Ext.extend(EzDesk.BillingStore, {
    method: 'get_products',
    reader: new Ext.data.JsonReader({
        total: 'total',
        root: 'data',
        id: 'id',
        fields: [{
            name: 'VID',
            type: 'integer'
        }, {
            name: 'PID',
            type: 'integer'
        }, {
            name: 'name',
            type: 'string'
        }, {
            name: 'description',
            type: 'string'
        }, {
            name: 'carrier_id',
            type: 'string'
        }]
    })
});

EzDesk.Products.ColumnModel = Ext.extend(Ext.grid.ColumnModel, {
    constructor: function(config){
        config = config || {};
        EzDesk.Products.ColumnModel.superclass.constructor.call(this, config);
        Ext.apply(this.columns, [{
            id: 'VID',
            //header: this.locale.field.vid,
            width: 60,
            dataIndex: 'id'
        }, {
            id: 'name',
            header: this.locale.field.Name,
            dataIndex: 'name'
        }]);
    },
    defaults: {
        width: 150,
        sortable: true
    }
});

EzDesk.Products.DetailStore = Ext.extend(EzDesk.BillingStore, {
    method: 'get_product_info',
    reader: new Ext.data.JsonReader({
        total: 'total',
        root: 'data',
        id: 'id',
        fields: [{
            name: 'VID',
            type: 'integer'
        }, {
            name: 'PID',
            type: 'integer'
        }, {
            name: 'name',
            type: 'string'
        }, {
            name: 'description',
            type: 'string'
        }, {
            name: 'carrier_id',
            type: 'string'
        }, {
            name: 'charge_plan',
            type: 'string'
        }, {
            name: 'parameters',
            type: 'string'
        }]
    })
});

//产品类型
EzDesk.Products.ProductType = Ext.extend(Ext.form.ComboBox, {
    initComponent: function(){
        Ext.apply(this, {
            name: 'product_type',
            hiddenName: 'pcode',
            store: new Ext.data.SimpleStore({
                fields: ['pcode', 'product'],
                data: [['5', '手机'], ['7', 'U盘']]
            }),
            valueField: 'pcode',
            fieldLabel: '产品类型',
            displayField: 'product',
            editable: false,
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            //allowBlank: false,
            //blankText: '请务必填写该信息',
            emptyText: '请选择...',
            selectOnFocus: false,
            forceSelection: true
        });
        EzDesk.Products.ProductType.superclass.initComponent.apply(this, arguments);
    },
    onRender: function(){
    	EzDesk.Products.ProductType.superclass.onRender.apply(this, arguments);
    }
});
Ext.reg('BProductType', EzDesk.Products.ProductType);


Ext.ns('EzDesk.Devices');
/*
 * 终端的列表数据源
 * */
EzDesk.Devices.ListStore = Ext.extend(EzDesk.BillingStore, {
    method: 'get_devices_info',
    reader: new Ext.data.JsonReader({
    	id: 'imei',
        totalProperty: 'totalCount',
        root: 'devices',
        fields: [
        {
            name: 'bsn',
            type: 'string'
        }, {
            name: 'imei',
            type: 'string'
        }, {
            name: 'status',
            type: 'string'
        }, {
            name: 'is_time',
            type: 'string'
        }, {
            name: 'os_time',
            type: 'string'
        }, {
            name: 'active_time',
            type: 'string'
        }, {
            name: 'va_time',
            type: 'string'
        }, {
            name: 'vc_time',
            type: 'string'
        }, {
            name: 'charge_plan',
            type: 'string'
        }, {
            name: 'resaler',
            type: 'string'
        }, {
            name: 'bind_pno',
            type: 'string'
        }, {
            name: 'bind_epno',
            type: 'string'
        }, {
            name: 'carrier_id',
            type: 'string'
        }, {
            name: 'vid',
            type: 'string'
        }, {
            name: 'pid',
            type: 'string'
        }, {
            name: 'remark',
            type: 'string'
        }]
    })
});


Ext.ns('EzDesk.Billing');
/*此ListStore返回用户相关的通话清单，根据登录权限查看用户的通话清单
 * */
EzDesk.Billing.ListUserCDRStore = Ext.extend(EzDesk.BillingStore, {
    method: 'user_cdr_list',
    reader: new Ext.data.JsonReader({
        idProperty: 'CDRDatetime',
        totalProperty: 'totalCount',
        root: 'data',
        messageProperty: 'message',
        successProperty: 'success',
        fields: [{
            name: 'CDRDatetime',
            type: 'string'
        }, {
            name: 'AcctStartTime',
            type: 'string'
        }, {
            name: 'PN_E164',
            type: 'string'
        }, {
            name: 'CallerID',
            type: 'string'
        }, {
            name: 'CallerGWIP',
            type: 'string'
        }, {
            name: 'CalledID',
            type: 'string'
        }, {
            name: 'CalledGWIP',
            type: 'string'
        }, {
            name: 'AcctSessionTime',
            type: 'string'
        }, {
            name: 'SessionTimeMin',
            type: 'string'
        }, {
            name: 'AcctSessionFee',
            type: 'string'
        }, {
            name: 'TerminationCause',
            type: 'string'
        }, {
            name: 'Remark',
            type: 'string'
        }]
    })
});

/*此ListStore返回代理商相关的通话清单，根据登录权限查看代理的通话清单
 * */
EzDesk.Billing.ListAgentCDRStore = Ext.extend(EzDesk.BillingStore, {
    method: '',
    reader: new Ext.data.JsonReader({
        idProperty: 'CDRDatetime',
        totalProperty: 'totalCount',
        root: 'data',
        messageProperty: 'message',
        successProperty: 'success',
        fields: [{
            name: 'CDRDatetime',
            type: 'string'
        }, {
            name: 'AcctStartTime',
            type: 'string'
        }, {
            name: 'PN_E164',
            type: 'string'
        }, {
            name: 'CallerID',
            type: 'string'
        }, {
            name: 'CallerGWIP',
            type: 'string'
        }, {
            name: 'CalledID',
            type: 'string'
        }, {
            name: 'CalledGWIP',
            type: 'string'
        }, {
            name: 'AcctSessionTime',
            type: 'string'
        }, {
            name: 'SessionTimeMin',
            type: 'string'
        }, {
            name: 'AcctSessionFee',
            type: 'string'
        }, {
            name: 'TerminationCause',
            type: 'string'
        }, {
            name: 'Remark',
            type: 'string'
        }]
    })
});

/*此ListStore返回网关相关的通话清单，根据登录权限查看落地的通话清单
 * */
EzDesk.Billing.ListGatewayCDRStore = Ext.extend(EzDesk.BillingStore, {
    method: '',
    reader: new Ext.data.JsonReader({
        idProperty: 'CDRDatetime',
        totalProperty: 'totalCount',
        root: 'data',
        messageProperty: 'message',
        successProperty: 'success',
        fields: [{
            name: 'CDRDatetime',
            type: 'string'
        }, {
            name: 'AcctStartTime',
            type: 'string'
        }, {
            name: 'PN_E164',
            type: 'string'
        }, {
            name: 'CallerID',
            type: 'string'
        }, {
            name: 'CallerGWIP',
            type: 'string'
        }, {
            name: 'CalledID',
            type: 'string'
        }, {
            name: 'CalledGWIP',
            type: 'string'
        }, {
            name: 'AcctSessionTime',
            type: 'string'
        }, {
            name: 'SessionTimeMin',
            type: 'string'
        }, {
            name: 'AcctSessionFee',
            type: 'string'
        }, {
            name: 'TerminationCause',
            type: 'string'
        }, {
            name: 'Remark',
            type: 'string'
        }]
    })
});

EzDesk.Billing.ListLogStore = Ext.extend(EzDesk.BillingStore, {
    method: 'get_action_log',
    reader: new Ext.data.JsonReader({
        idProperty: 'ID',
        totalProperty: 'totalCount',
        root: 'data',
        messageProperty: 'message',
        successProperty: 'success',
        fields: [{
            name: 'ID',
            type: 'string'
        },{
            name: 'LogTime',
            type: 'string'
        }, {
            name: 'ModSrcIP',
            type: 'string'
        }, {
            name: 'ModDest',
            type: 'string'
        }, {
            name: 'SrcIP',
            type: 'string'
        }, {
            name: 'Action',
            type: 'string'
        }, {
            name: 'ReturnValue',
            type: 'string'
        }, {
            name: 'RunTime',
            type: 'string'
        }, {
            name: 'ApiSrcIP',
            type: 'string'
        }, {
            name: 'Param',
            type: 'string'
        }, {
            name: 'Requests',
            type: 'string'
        }, {
            name: 'Response',
            type: 'string'
        }]
    })
});

EzDesk.Billing.DetailLogStore = Ext.extend(EzDesk.BillingStore, {
    method: 'get_action_log',
    reader: new Ext.data.JsonReader({
        idProperty: 'logDatetime',
        totalProperty: 'totalCount',
        root: 'data',
        messageProperty: 'message',
        successProperty: 'success',
        fields: [{
            name: 'LogTime',
            type: 'string'
        }, {
            name: 'ModSrcIP',
            type: 'string'
        }, {
            name: 'ModDest',
            type: 'string'
        }, {
            name: 'SrcIP',
            type: 'string'
        }, {
            name: 'Action',
            type: 'string'
        }, {
            name: 'ReturnValue',
            type: 'string'
        }, {
            name: 'RunTime',
            type: 'string'
        }, {
            name: 'Param',
            type: 'string'
        }, {
            name: 'Requests',
            type: 'string'
        }, {
            name: 'Response',
            type: 'string'
        }, {
            name: 'ApiSrcIP',
            type: 'string'
        }]
    })
});


//货币类型
EzDesk.Billing.CurrencyType = Ext.extend(Ext.form.ComboBox, {
    initComponent: function(){
        Ext.apply(this, {
            name: 'currency_type',
            store: new Ext.data.SimpleStore({
                fields: ['value', 'text'],
                data: [[1, 'CNY'], [2, 'USD'], [3, 'PTS'], [4, 'TWD'], [5, 'HKD']]
            }),
            valueField: 'value',
            fieldLabel: '货币类型',
            displayField: 'text',
            editable: false,
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            //allowBlank: false,
            //blankText: '请务必填写该信息',
            emptyText: '请选择...',
            selectOnFocus: false,
            forceSelection: true
        });
        EzDesk.Billing.CurrencyType.superclass.initComponent.apply(this, arguments);
    },
    onRender: function(){
    	EzDesk.Billing.CurrencyType.superclass.onRender.apply(this, arguments);
    }
});
Ext.reg('BCurrencyTypeCombo', EzDesk.Billing.CurrencyType);


//代理商信息
EzDesk.Billing.agentStore = Ext.extend(EzDesk.BillingStore, {
    method: 'get_agent_name',
    reader: new Ext.data.JsonReader({
        idProperty: 'agent_name',
        totalProperty: 'totalCount',
        root: 'data',
        messageProperty: 'message',
        successProperty: 'success',
        fields: [{
            name: 'agent_name',
            type: 'string'
        }, {
            name: 'agent_id',
            type: 'string'
        }]
    })
});

//代理商计费方案
EzDesk.Billing.agentCSStore = Ext.extend(EzDesk.BillingStore, {
    method: 'get_agent_cs',
    reader: new Ext.data.JsonReader({
        idProperty: 'agent_cs_name',
        totalProperty: 'totalCount',
        root: 'data',
        messageProperty: 'message',
        successProperty: 'success',
        fields: [{
            name: 'agent_cs_name',
            type: 'string'
        }, {
            name: 'agent_cs_id',
            type: 'string'
        }]
    })
});

//用户计费方案信息
EzDesk.Billing.userCSStore = Ext.extend(EzDesk.BillingStore, {
    method: 'get_user_cs',
    reader: new Ext.data.JsonReader({
        idProperty: 'user_cs_name',
        totalProperty: 'totalCount',
        root: 'data',
        messageProperty: 'message',
        successProperty: 'success',
        fields: [{
            name: 'user_cs_name',
            type: 'string'
        }, {
            name: 'user_cs_id',
            type: 'string'
        }]
    })
});

//summary for traffic
EzDesk.Billing.summary_traffic = Ext.extend(EzDesk.BillingStore, {
    method: 'get_traffic',
    reader: new Ext.data.JsonReader({
        idProperty: 'cdrdate',
        totalProperty: 'totalCount',
        root: 'data',
        //messageProperty: 'message',
        successProperty: 'success',
        fields: [{
            name: 'cdrdate',
            type: 'string'
        }, {
            name: 'calls',
            type: 'int'
        }, {
            name: 'connecteds',
            type: 'int'
        }, {
            name: 'asr',
            type: 'float'
        }, {
            name: 'session',
            type: 'float'
        }, {
            name: 'fee',
            type: 'float'
        }, {
            name: 'base_fee',
            type: 'float'
        }, {
            name: 'ctype',
            type: 'string'
        }]
    })
});

//summary for recharge
EzDesk.Billing.summary_recharge = Ext.extend(EzDesk.BillingStore, {
    method: 'get_recharge',
    reader: new Ext.data.JsonReader({
        idProperty: 'rdate',
        totalProperty: 'totalCount',
        root: 'data',
        //messageProperty: 'message',
        successProperty: 'success',
        fields: [{
            name: 'cdrdate',
            type: 'string'
        }, {
            name: 'rt_total',
            type: 'float'
        }, {
            name: 'rt_web',
            type: 'float'
        }, {
            name: 'rt_pin',
            type: 'float'
        }, {
            name: 'rt_online',
            type: 'float'
        }, {
            name: 'rt_cmcc',
            type: 'float'
        }, {
            name: 'rt_unicom',
            type: 'float'
        }, {
            name: 'rt_ctc',
            type: 'float'
        }]
    })
});



/**************billing ComboBox Start***************
 * 与blling有关的combobox，创建类型
 *******/

/**
 * @class	Ext.Billing.AentCS
 * @extends Ext.form.ComboBox
 * @author	lion wang	
 * 
 * This class use to get charge plan for agent from biiling DB
 * The store of ComboBox is EzDesk.Billing.agentCSStore
 * @constructor
 * @param	desktop
 * 			moduleId    
 * 			connection		HTTP Proxy
 */
EzDesk.Billing.AgentCSType = Ext.extend(Ext.form.ComboBox, {
    initComponent: function(){
        Ext.apply(this, {
            //name: 'agent_cs_name',
        	hiddenName: 'agent_cs_id',
            store: new EzDesk.Billing.agentCSStore({
            	desktop: this.desktop
            	,moduleId: this.moduleId
            	,connection: this.connection
            }),
            valueField: 'agent_cs_id',
            //fieldLabel: '产品类型',
            displayField: 'agent_cs_name',
            editable: false,
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            //blankText: lang_tr.BlankText,
            emptyText: lang_tr.EmptyText,
            selectOnFocus: false,
            forceSelection: true
        });
        EzDesk.Billing.AgentCSType.superclass.initComponent.apply(this, arguments);
    },
    onRender: function(){
    	EzDesk.Billing.AgentCSType.superclass.onRender.apply(this, arguments);
    }
});
Ext.reg('BAgentCSType', EzDesk.Billing.AgentCSType);


/**
 * @class	Ext.Billing.UserCSType
 * @extends Ext.form.ComboBox
 * @author	lion wang	
 * 
 * This class use to get charge plan for user from biiling DB
 * The store of ComboBox is EzDesk.Billing.userCSStore
 * @constructor
 * @param	desktop
 * 			moduleId    
 * 			connection		HTTP Proxy
 */
EzDesk.Billing.UserCSType = Ext.extend(Ext.form.ComboBox, {
    initComponent: function(){
        Ext.apply(this, {
        	//name: 'user_cs_name',
        	hiddenName: 'user_cs_id',
            store: new EzDesk.Billing.userCSStore({
            	desktop: this.desktop
            	,moduleId: this.moduleId
            	,connection: this.connection
            }),
            valueField: 'user_cs_id',
            //fieldLabel: '产品类型',
            displayField: 'user_cs_name',
            editable: false,
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            //allowBlank: false,
            //blankText: lang_tr.BlankText,
            emptyText: lang_tr.EmptyText,
            selectOnFocus: false,
            forceSelection: true
        });
        EzDesk.Billing.UserCSType.superclass.initComponent.apply(this, arguments);
    },
    onRender: function(){
    	EzDesk.Billing.UserCSType.superclass.onRender.apply(this, arguments);
    }
});
Ext.reg('BUserCSType', EzDesk.Billing.UserCSType);
/**************billing ComboBox End ***************/

Ext.ns('EzDesk.um');

/****User Management****/
EzDesk.um.customer_list = Ext.extend(EzDesk.BillingStore, {
    method: 'crm_get_custmer_list',
    autoLoad: true,
    reader: new Ext.data.JsonReader({
        idProperty: 'ID',
        totalProperty: 'totalCount',
        root: 'data',
        messageProperty: 'message',
        successProperty: 'success',
        fields: [{
            name: 'ID',
            type: 'string'
        },{
            name: 'endpoint',
            type: 'string'
        }, {
            name: 'cust_group',
            type: 'string'
        }, {
            name: 'phoneno',
            type: 'string'
        }, {
            name: 'name',
            type: 'string'
        }, {
            name: 'sex',
            type: 'string'
        }, {
            name: 'company',
            type: 'string'
        }, {
            name: 'email',
            type: 'string'
        }, {
            name: 'remark',
            type: 'string'
        }]
    })
});

EzDesk.um.cdr_list = Ext.extend(EzDesk.BillingStore, {
    method: 'crm_get_cdr_list',
    reader: new Ext.data.JsonReader({
        idProperty: 'ID',
        totalProperty: 'totalCount',
        root: 'data',
        messageProperty: 'message',
        successProperty: 'success',
        fields: [{
            name: 'SessionID',
            type: 'string'
        },{
            name: 'AcctStartTime',
            type: 'string'
        }, {
            name: 'caller',
            type: 'string'
        }, {
            name: 'callee',
            type: 'string'
        }, {
            name: 'SessionTimeMin',
            type: 'string'
        }, {
            name: 'AcctSessionFee',
            type: 'string'
        }, {
            name: 'CurrencyType',
            type: 'string'
        }, {
            name: 'Rate',
            type: 'string'
        }, {
            name: 'Remark',
            type: 'string'
        }]
    })
});

EzDesk.um.finance_list = Ext.extend(EzDesk.BillingStore, {
    method: 'crm_get_finance_list',
    reader: new Ext.data.JsonReader({
        idProperty: 'ID',
        totalProperty: 'totalCount',
        root: 'data',
        messageProperty: 'message',
        successProperty: 'success',
        fields: [{
            name: 'H_timestamp',
            type: 'string'
        },{
            name: 'H_Datetime',
            type: 'string'
        },{
            name: 'IncType',
            type: 'string'
        }, {
            name: 'Old_Balance',
            type: 'string'
        }, {
            name: 'Cost',
            type: 'string'
        }, {
            name: 'New_Balance',
            type: 'string'
        }, {
            name: 'RealCost',
            type: 'string'
        }, {
            name: 'Remark',
            type: 'string'
        }, {
            name: 'SourcePin',
            type: 'string'
        }]
    })
});


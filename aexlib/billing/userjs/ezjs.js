/*
 * 在这里不可以使用Desktop的类和变量
 */
Ext.namespace('EzDesk');

EzDesk.ux = {
	write_error:function(msg,fn){
		Ext.Msg.show({
			   title:lang_tr.lang_Error,
			   msg: msg,
			   buttons: Ext.Msg.OK,
			   animEl: 'elId',
			   fn:fn,
			   icon: Ext.MessageBox.ERROR
			});
	},
	write_warning:function(msg,fn){
		Ext.Msg.show({
			   title:lang_tr.lang_Warning,
			   msg: msg,
			   buttons: Ext.Msg.OK,
			   animEl: 'elId',
			   fn:fn,
			   icon: Ext.MessageBox.WARNING
			});
	},
	write_hint:function(msg,fn){
		Ext.Msg.show({
			   title:lang_tr.lang_Hint,
			   msg: msg,
			   buttons: Ext.Msg.OK,
			   animEl: 'elId',
			   fn:fn,
			   icon: Ext.MessageBox.INFO
			});
	}
	
};

// Create创建用户的扩展（User eXtensions namespace (Ext.ux)）
Ext.namespace('Ext.ux'); 
  
/**
 * @class Ext.ux.IconCombo
 * @extends Ext.form.ComboBox
 * @constructor
 * @param {Object}
 *            config 配置项参数
 */ 
Ext.ux.IconCombo = function(config) { 
  
    // 调用父类的构建函数 
    Ext.ux.IconCombo.superclass.constructor.call(this, config); 
    this.tpl = config.tpl || 
		  '<tpl for="."><div class="x-combo-list-item">' 
		+ '<table><tr>'  +'<td>'
		+ '<td>'
		+ '<div class="{' + this.iconClsField + '} x-icon-combo-icon"></div></td>' 
		+ '<td>{' + this.displayField + '}</td>' 
		+ '</tr></table>' 
		+ '</div></tpl>' ; 
	this.on({ 
		render:{scope:this, fn:function() { 
			var wrap = this.el.up('div.x-form-field-wrap'); 
			this.wrap.applyStyles({position:'relative'}); 
			this.el.addClass('x-icon-combo-input'); 
			this.flag = Ext.DomHelper.append(wrap, { 
				tag: 'div', style:'position:absolute' 
			}); 
		}} 
	}); 
		  
} // Ext.ux.IconCombo构建器的底部 

// 进行扩展
Ext.extend(Ext.ux.IconCombo, Ext.form.ComboBox, { 
	editable : false,
    setIconCls: function() { 
        var rec = this.store.query(this.valueField, this.getValue()).itemAt(0); 
        if(rec) { 
            this.flag.className = 'x-icon-combo-icon ' + rec.get(this.iconClsField); 
        } 
    }, 
    setValue: function(value) { 
        Ext.ux.IconCombo.superclass.setValue.call(this, value); 
        this.setIconCls(); 
    }
}); // 扩展完毕

Ext.reg('IconCombo',Ext.ux.IconCombo);



Ext.ns('Ext.ux.tree');

/**
 * Creates new RemoteTreePanel
 * 
 * @constructor
 * @param {Object}
 *            config A config object
 */
Ext.ux.tree.RemoteTreePanel = Ext.extend(Ext.tree.TreePanel, {
 
	// {{{
	// config options
	// localizable texts
	 appendText:'Append'
	,collapseAllText:'Collapse All'
	,collapseText:'Collapse'
	,contextMenu:true
	,deleteText:'Delete'
	,errorText:'Error'
	,expandAllText:'Expand All'
	,expandText:'Expand'
	,insertText:'Insert'
	,newText:'New'
	,reallyWantText:'Do you really want to'
	,reloadText:'Reload'
	,renameText:'Rename'

	// other options
	/**
	 * @cfg {Object} actions Public interface to methods of tree operations.
	 *      Actions are created internally and then are available for user space
	 *      program. Actions provided from outside at instatiation time are
	 *      honored.
	 */

	/**
	 * @cfg {Boolean} allowLeafAppend When dragging a node over a leaf the node
	 *      cannot be appended. If this config option is true then the leaf
	 *      dragged over is turned into node allowing to append dragged node to
	 *      it. Defaults to true.
	 */
	,allowLeafAppend:true

	/**
	 * @cfg {String} appendIconCls Icon class to use for {Ext.Action} iconCls.
	 *      This icon is then used for the action user interface (context menu,
	 *      button, etc.)
	 */
	,appendIconCls:'icon-arrow-down'

	/**
	 * @cfg {Boolean} border Draw border around panel if true. (Defaults to
	 *      false)
	 */
    ,border:false

	/**
	 * @cfg {Object} cmdNames Names of commands sent to the server
	 */
	,cmdNames:{
		 moveNode:'moveTreeNode'
		,renameNode:'renameTreeNode'
		,removeNode:'removeTreeNode'
		,appendChild:'appendTreeChild'
		,insertChild:'insertTreeChild'
	}

	/**
	 * @cfg {String} collapseAllIconCls Icon class to use for {Ext.Action} iconCls. This icon is then used
	 * for the action user interface (context menu, button, etc.)
	 */
	,collapseAllIconCls:'icon-collapse'

	/**
	 * @cfg {String} collapseIconCls Icon class to use for {Ext.Action} iconCls.
	 *      This icon is then used for the action user interface (context menu,
	 *      button, etc.)
	 */
	,collapseIconCls:'icon-collapse'

	/**
	 * @cfg {String} deleteIconCls Icon class to use for {Ext.Action} iconCls.
	 *      This icon is then used for the action user interface (context menu,
	 *      button, etc.)
	 */
	,deleteIconCls:'icon-cross'

	/**
	 * @cfg {Boolean} editable Set it to false to switch tree to read-only mode.
	 *      Defaults to true.
	 */
	,editable:true

	/**
	 * @cfg {Object} editorConfig Configuration for Ext.tree.TreeEditor
	 */
	,editorConfig:{
		 cancelOnEsc:true
		,completeOnEnter:true
	}

	/**
	 * @cfg {Object} editorFieldConfig Configuration for tree editor field
	 */
	,editorFieldConfig:{
		 allowBlank:false
		,selectOnFocus:true
	}

	/**
	 * @cfg {Boolean} enableDD Enable drag and drop operations. Defaults to true.
	 */
	,enableDD:true

	/**
	 * @cfg {String} expandAllIconCls Icon class to use for {Ext.Action}
	 *      iconCls. This icon is then used for the action user interface
	 *      (context menu, button, etc.)
	 */
	,expandAllIconCls:'icon-expand'

	/**
	 * @cfg {String} expandIconCls Icon class to use for {Ext.Action} iconCls.
	 *      This icon is then used for the action user interface (context menu,
	 *      button, etc.)
	 */
	,expandIconCls:'icon-expand'

	/**
	 * @cfg {String} insertIconCls Icon class to use for {Ext.Action} iconCls.
	 *      This icon is then used for the action user interface (context menu,
	 *      button, etc.)
	 */
	,insertIconCls:'icon-arrow-right'

	/**
	 * @cfg {String} layout Default layout used for the panel. Defaults to
	 *      'fit'.
	 */
	,layout:'fit'

	/**
	 * @cfg {Object} paramNames Names of parameters sent to server in requests.
	 */
	,paramNames:{
		 cmd:'cmd'
		,id:'id'
		,target:'target'
		,point:'point'
		,text:'text'
		,newText:'newText'
		,oldText:'oldText'
	}

	/**
	 * @cfg {String} reloadIconCls Icon class to use for {Ext.Action} iconCls. This icon is then used
	 * for the action user interface (context menu, button, etc.)
	 */
	,reloadIconCls:'icon-refresh'

	/**
	 * @cfg {String} renameIconCls Icon class to use for {Ext.Action} iconCls.
	 *      This icon is then used for the action user interface (context menu,
	 *      button, etc.)
	 */
	,renameIconCls:'icon-pencil'
	// }}}
    // {{{
    ,initComponent:function() {

        // {{{
        // hard coded config (cannot be changed from outside)
        var config = {};

		// todo: add other keys and put them to context menu
		if(!this.keys) {
			config.keys = (function() {
				var keys = [];
				if(true === this.editable) {
					keys.push({
						 key:Ext.EventObject.DELETE
						,scope:this
						,stopEvent:true
						,handler:this.onKeyDelete
					});

					keys.push({
						 key:Ext.EventObject.F2
						,scope:this
						,stopEvent:true
						,handler:this.onKeyEdit
					});
				}
				return keys;
			}.call(this));
		}
 
        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));
        // }}}
		// {{{
        // call parent
        Ext.ux.tree.RemoteTreePanel.superclass.initComponent.apply(this, arguments);
		// }}}
		// {{{
		// make sure that all nodes are created
		if(true === this.loader.preloadChildren) {
			this.loader.on({load:function(loader, node) {
				node.cascade(function(n) {
					loader.doPreload(n);
				});
			}});
		}
		// }}}
		// {{{
		// create tree editor
		if(true === this.editable && !this.editor) {
			this.editor = new Ext.tree.TreeEditor(this, this.editorFieldConfig, this.editorConfig);
			this.editor.on({
				 complete:{scope:this, fn:this.onEditComplete}
				,beforestartedit:{scope:this, fn:function(){ return this.editable; }}
			});
		}
		// }}}
		// {{{
		// remember selected node
		if(true === this.editable) {
			this.getSelectionModel().on({
				selectionchange:{scope:this, fn:function(selModel, node) {
					this.selectedNode = node;
				}
			}});
		}
		// }}}
		// {{{
		// create actions
		if(true === this.editable && !this.actions) {
			this.actions = {
				 reloadTree:new Ext.Action({
					 text:this.reloadText
					,iconCls:this.reloadIconCls
					,scope:this
					,handler:function() {this.root.reload();}
				})
				,expandNode:new Ext.Action({
					 text:this.expandText
					,iconCls:this.expandIconCls
					,scope:this
					,handler:this.onExpandNode
				})
				,expandAll:new Ext.Action({
					 text:this.expandAllText
					,iconCls:this.expandAllIconCls
					,scope:this
					,handler:this.onExpandAll
				})
				,collapseNode:new Ext.Action({
					 text:this.collapseText
					,iconCls:this.collapseIconCls
					,scope:this
					,handler:this.onCollapseNode
				})
				,collapseAll:new Ext.Action({
					 text:this.collapseAllText
					,iconCls:this.collapseAllIconCls
					,scope:this
					,handler:this.onCollapseAll
				})
				,renameNode:new Ext.Action({
					 text:this.renameText
					,iconCls:this.renameIconCls
					,scope:this
					,handler:this.onRenameNode
				})
				,removeNode:new Ext.Action({
					 text:this.deleteText
					,iconCls:this.deleteIconCls
					,scope:this
					,handler:this.onRemoveNode
				})
				,appendChild:new Ext.Action({
					 text:this.appendText
					,iconCls:this.appendIconCls
					,scope:this
					,handler:this.onAppendChild
				})
				,insertChild:new Ext.Action({
					 text:this.insertText
					,iconCls:this.insertIconCls
					,scope:this
					,handler:this.onInsertChild
				})
			};
		}
		// }}}
		// {{{
		// create context menu
		if(true === this.editable && true === this.contextMenu) {
			this.contextMenu = new Ext.menu.Menu([
				 new Ext.menu.TextItem({text:'', style:'font-weight:bold;margin:0px 4px 0px 27px;line-height:18px'})
				,'-'
				,this.actions.reloadTree
				,this.actions.expandAll
				,this.actions.collapseAll
				,'-'
				,this.actions.expandNode
				,this.actions.collapseNode
				,'-'
				,this.actions.renameNode
				,'-'
				,this.actions.appendChild
				,this.actions.insertChild
				,'-'
				,this.actions.removeNode
			]);
		}

		// install event handlers on contextMenu
		if(this.contextMenu) {
			this.on({contextmenu:{scope:this, fn:this.onContextMenu, stopEvent:true}});
			this.contextMenu.on({
				hide:{scope:this, fn:function() {
					this.actionNode = null;
				}}
				,show:{scope:this, fn:function() {
					var node = this.actionNode;
					var text = Ext.util.Format.ellipsis(node ? node.text : '', 12);
					this.contextMenu.items.item(0).el.update(text);
					this.contextMenu.el.shadow.hide();
					this.contextMenu.el.shadow.show(this.contextMenu.el);
				}}
			});
		}
		// }}}
		// {{{
		// setup D&D
		if(true === this.enableDD) {
			this.on({
				 beforenodedrop:{scope:this, fn:this.onBeforeNodeDrop}
				,nodedrop:{scope:this, fn:this.onNodeDrop}
				,nodedragover:{scope:this, fn:this.onNodeDragOver}
				,startdrag:{scope:this, fn:this.onStartDrag}
			});

		}
		// }}}
		// {{{
		// add events
		this.addEvents(
			/**
			 * @event beforeremoverequest
			 * Fires before request is sent to the server, return false to cancel the event.
			 * @param {Ext.ux.tree.RemoteTreePanel} tree This tree panel
			 * @param {Object} options Options as passed to Ajax.request
			 */
			 'beforeremoverequest'

			/**
			 * @event beforerenamerequest Fires before request is sent to the
			 *        server, return false to cancel the event.
			 * @param {Ext.ux.tree.RemoteTreePanel}
			 *            tree This tree panel
			 * @param {Object}
			 *            options Options as passed to Ajax.request
			 */
			,'beforerenamerequest'

			/**
			 * @event beforeappendrequest Fires before request is sent to the
			 *        server, return false to cancel the event.
			 * @param {Ext.ux.tree.RemoteTreePanel}
			 *            tree This tree panel
			 * @param {Object}
			 *            options Options as passed to Ajax.request
			 */
			,'beforeappendrequest'

			/**
			 * @event beforeinsertrequest Fires before request is sent to the
			 *        server, return false to cancel the event.
			 * @param {Ext.ux.tree.RemoteTreePanel}
			 *            tree This tree panel
			 * @param {Object}
			 *            options Options as passed to Ajax.request
			 */
			,'beforeinsertrequest'

			/**
			 * @event beforeremoverequest Fires before request is sent to the
			 *        server, return false to cancel the event.
			 * @param {Ext.ux.tree.RemoteTreePanel}
			 *            tree This tree panel
			 * @param {Object}
			 *            options Options as passed to Ajax.request
			 */
			,'beforemoverequest'

			/**
			 * @event appendchildsuccess Fires after server returned success
			 * @param {Ext.ux.tree.RemoteTreePanel}
			 *            tree This tree panel
			 * @param {Ext.tree.AsyncTreeNode}
			 *            node The node involved in action
			 */
			,'appendchildsuccess'

			/**
			 * @event insertchildsuccess Fires after server returned success
			 * @param {Ext.ux.tree.RemoteTreePanel}
			 *            tree This tree panel
			 * @param {Ext.tree.AsyncTreeNode}
			 *            node The node involved in action
			 */
			,'insertchildsuccess'

			/**
			 * @event removenodesuccess Fires after server returned success
			 * @param {Ext.ux.tree.RemoteTreePanel}
			 *            tree This tree panel
			 * @param {Ext.tree.AsyncTreeNode}
			 *            node The node involved in action
			 */
			,'removenodesuccess'

			/**
			 * @event renamenodesuccess Fires after server returned success
			 * @param {Ext.ux.tree.RemoteTreePanel}
			 *            tree This tree panel
			 * @param {Ext.tree.AsyncTreeNode}
			 *            node The node involved in action
			 */
			,'renamenodesuccess'

			/**
			 * @event movenodesuccess Fires after server returned success
			 * @param {Ext.ux.tree.RemoteTreePanel}
			 *            tree This tree panel
			 * @param {Ext.tree.AsyncTreeNode}
			 *            node The node involved in action
			 */
			,'movenodesuccess'
		);
		// }}}

    } // eo function initComponent
    // }}}
	// {{{
	/**
	 * initEvents override
	 * 
	 * @private
	 */
	,initEvents:function() {
		Ext.ux.tree.RemoteTreePanel.superclass.initEvents.apply(this, arguments);
		if(true === this.enableDD) {
			// prevent dragging if the tree is not editable
			this.dragZone.onBeforeDrag = function(data, e) {
				var n = data.node;
				return n && n.draggable && !n.disabled && this.tree.editable;
			}; // eo function onBeforeDrag
		}
	} // eo function initEvents
	// }}}
	// {{{
	/**
	 * Server request (action) callback function)
	 * 
	 * @param {Object}
	 *            options Options used for request
	 * @param {Boolean}
	 *            success
	 * @param {Object}
	 *            response
	 */
	,actionCallback:function(options, success, response) {

		// remove loading indicator
		if(options.node) {
			options.node.getUI().afterLoad();
		}

		// {{{
		// failure handling
		if(true !== success) {
			this.showError(response.responseText);
			return;
		}
		var o;
		try {
			o = Ext.decode(response.responseText);
		}
		catch(ex) {
			this.showError(response.responseText);
			return;
		}
		if(true !== o.success) {
			this.showError(o.error || o.errors);
			switch(options.action) {
				case 'appendChild':
				case 'insertChild':
					options.node.parentNode.removeChild(options.node);
				break;

				default:
				break;
			}
			return;
		}
		if(!options.action) {
			this.showError('Developer error: no options.action');
		}
		// }}}
		//{{{
		// success handling - synchronize ui with server action
		switch(options.action) {
			case 'renameNode':
				options.node.setText(options.params.newText);
			break;

			case 'removeNode':
				options.node.parentNode.removeChild(options.node);
			break;

			case 'moveNode':
				if('append' === options.e.point) {
					options.e.target.expand();
				}
				this.dropZone.completeDrop(options.e);
			break;

			case 'appendChild':
			case 'insertChild':
				// change id of the appended/inserted node
				this.unregisterNode(options.node);
				options.node.id = o.id;
				Ext.fly(options.node.getUI().elNode).set({'ext:tree-node-id':o.id});
				this.registerNode(options.node);
				options.node.select();
			break;
		}
		//}}}
		this.fireEvent(options.action.toLowerCase() + 'success', this, options.node);

	} // eo function actionCallback
	// }}}
	// {{{
	/**
	 * Returne combined object of baseParams and params
	 * 
	 * @private
	 * @param {Object}
	 *            params params to combine with baseParams
	 */
	,applyBaseParams:function(params) {
		var o = Ext.apply({}, this.baseParams || this.loader.baseParams || {});
		Ext.apply(o, params || {});
		return o;
	}
	// }}}
	// {{{
	/**
	 * Requests server to append the child node. Child node has already been appended
	 * at client but it is removed if server append fails
	 * 
	 * @param {Ext.tree.TreeNode} childNode node to append
	 * @param {Boolean} insert Do not apppend but insert flag
	 */
	,appendChild:function(childNode, insert) {

		var params = this.applyBaseParams();
		params[this.paramNames.cmd] = true === insert ? this.cmdNames.insertChild : this.cmdNames.appendChild;
		params[this.paramNames.id] = childNode.parentNode.id;
		params[this.paramNames.text] = childNode.text;

		var o = Ext.apply(this.getOptions(), {
			 action:true === insert ? 'insertChild' : 'appendChild'
			,node:childNode
			,params:params
		});

		if(false !== this.fireEvent('before' + (insert ? 'insert' : 'append') + 'request', this, o)) {

			// set loading indicator
			childNode.getUI().beforeLoad();
			Ext.Ajax.request(o);
		}
	} // eo function appendChild
	// }}}
	// {{{
	/**
	 * Returns options for server request
	 * 
	 * @return {Object} options for request
	 * @private
	 */
	,getOptions:function() {
		return {
			 url:this.loader.url || this.loader.dataUrl || this.url || this.dataUrl
			,method:this.loader.method || this.method || 'POST'
			,scope:this
			,callback:this.actionCallback
		};
	} // eo function getOptions
	// }}}
	// {{{
	/**
	 * appendChild action handler
	 * 
	 * @param {Boolean}
	 *            insert Do not append but insert flag
	 * @private
	 */
	,onAppendChild:function(insert) {
		this.actionNode = this.actionNode || this.selectedNode;
		if(!this.actionNode) {
			return;
		}
		var node = this.actionNode;
		var child;
		node.leaf = false;
		node.expand(false, false, function(n) {
			if(true === insert) {
				child = n.insertBefore(this.loader.createNode({text:this.newText, loaded:true}), n.firstChild);
			}
			else {
				child = n.appendChild(this.loader.createNode({text:this.newText, loaded:true}));
			}
		}.createDelegate(this));

		this.editor.creatingNode = true;
		if(true === insert) {
			this.editor.on({complete:{scope:this, single:true, fn:this.onInsertEditComplete}});
		}
		else {
			this.editor.on({complete:{scope:this, single:true, fn:this.onAppendEditCompete}});
		}

		this.editor.triggerEdit(child);
		this.actionNode = null;

	} // eo function onAppendChild
	// }}}
	// {{{
	/**
	 * Editing complete event handler
	 * 
	 * @param {Ext.tree.TreeEditor}
	 *            editor
	 * @param {String}
	 *            newText
	 * @param {String}
	 *            oldText
	 * @private
	 */
	,onAppendEditCompete:function(editor, newText, oldText) {
		this.appendChild(editor.editNode);
	} // onAppendEditCompete
	// }}}
	// {{{
	/**
	 * Before node drop event handler. Cancels node drop at client but initiates
	 * request to server. Drop is completed if the server returns success
	 * 
	 * @param {Object}
	 *            e DD object
	 * @private
	 */
	,onBeforeNodeDrop:function(e) {

		this.moveNode(e);
		e.dropStatus = true;
		return false;

	} // eo function onBeforeNodeDrop
	// }}}
	// {{{
	/**
	 * contextmenu event handler. Shows context menu
	 * 
	 * @param {Ext.tree.TreeNode}
	 *            node right-clicked node
	 * @param {Ext.EventObject}
	 *            e event
	 */
	,onContextMenu:function(node, e) {
		var menu = this.contextMenu;

		// no node under click - use root node
		if(node.browserEvent) {
			this.getSelectionModel().clearSelections();
			menu.showAt(node.getXY());
			this.actionNode = this.getRootNode();
			node.stopEvent();
		}
		// a node under click
		else {
			node.select();
			this.actionNode = node;
			var alignEl = node.getUI().getEl();
			var xy = menu.getEl().getAlignToXY(alignEl, 'tl-tl', [0, 18]);
			menu.showAt([e.getXY()[0], xy[1]]);
			e.stopEvent();
		}

		var actions = this.actions;
		var disable = true !== this.editable || !this.actionNode;
		actions.appendChild.setDisabled(disable);
		actions.renameNode.setDisabled(disable);
		actions.removeNode.setDisabled(disable);
		actions.insertChild.setDisabled(disable);

	} // eo function onContextMenu
	// }}}
	// {{{
	/**
	 * Event handler of editor complete event Calls rename node but returns
	 * false as ui is updated later on success
	 * 
	 * @param {Ext.tree.TreeEditor}
	 *            editor
	 * @param {String}
	 *            newText
	 * @param {String}
	 *            oldText
	 * @return {Boolean} false - to cancel immediate editing/renaming
	 */
	,onEditComplete:function(editor, newText, oldText) {
		if(editor.creatingNode) {
			editor.creatingNode = false;
			return;
		}

		this.renameNode(editor.editNode, newText);
		return false;
	} // eo function onEditComplete
	// }}}
	// {{{
	/**
	 * expandAll action handler
	 * 
	 * @private
	 */
	,onExpandAll:function() {
		this.getRootNode().expand(true, false);
	} // eo function onExpandAll
	// }}}
	// {{{
	/**
	 * expandNode action handler
	 * 
	 * @private
	 */
	,onExpandNode:function() {
		(this.actionNode || this.selectedNode || this.getRootNode()).expand(true, false);
	} // eo function onExpandNode
	// }}}
	// {{{
	/**
	 * collapseAll action handler
	 * 
	 * @private
	 */
	,onCollapseAll:function() {
		this.getRootNode().collapse(true, false);
	} // eo function onCollapseAll
	// }}}
	// {{{
	/**
	 * collapseNode action handler
	 * 
	 * @private
	 */
	,onCollapseNode:function() {
		(this.actionNode || this.selectedNode || this.getRootNode()).collapse(true, false);
	} // eo function onCollapseNode
	// }}}
	// {{{
	/**
	 * insertNode editing completed event handler
	 * 
	 * @param {Ext.tree.TreeEditor}
	 *            editor
	 * @param {String}
	 *            newText
	 * @param {String}
	 *            oldText
	 * @private
	 */
	,onInsertEditComplete:function(editor, newText, oldText) {
		this.appendChild(editor.editNode, true);
	} // eo onInsertEditComplete
	// }}}
	// {{{
	/**
	 * insertChild action handler
	 * 
	 * @private
	 */
	,onInsertChild:function() {
		this.onAppendChild(true);
	} // onInsertChild
	// }}}
	 // {{{
	/**
	 * Delete key event handler. Calls delete action if a node is selected
	 * 
	 * @param {Number}
	 *            key
	 * @param {Ext.EventObject}
	 *            e
	 */
	,onKeyDelete:function(key, e) {
		this.actionNode = this.getSelectionModel().getSelectedNode();
		this.actions.removeNode.execute();
	} // eo onKeyDelete
	// }}}
	 // {{{
	/**
	 * Edit key (F2) event handler. Triggers editing.
	 * 
	 * @param {Number}
	 *            key
	 * @param {Ext.EventObject}
	 *            e
	 */
	,onKeyEdit:function(key, e) {
		var node = this.getSelectionModel().getSelectedNode();
		if(node && true === this.editable) {
			this.actionNode = node;
			this.onRenameNode();
		}
	} // eo onKeyEdit
	// }}}
	// {{{
	/**
	 * nodedragover event handler. Resets leaf flag if appendig to leafs is
	 * allowed
	 * 
	 * @param {Object}
	 *            e DD object
	 * @private
	 */
	,onNodeDragOver:function(e) {
		if(true === this.allowLeafAppend) {
			e.target.leaf = false;
		}
	} // eo function onNodeDragOver
	// }}}
	// {{{
	/**
	 * nodedrop event handler
	 * 
	 * @param {Object}
	 *            e DD object
	 * @private
	 */
	,onNodeDrop:function(e) {
	} // eo function onNodeDrop
	// }}}
	// {{{
	/**
	 * onRender override
	 * 
	 * @private
	 */
	,onRender:function() {
		Ext.ux.tree.RemoteTreePanel.superclass.onRender.apply(this, arguments);
		if(false === this.rootVisible && this.contextMenu) {
			this.el.on({contextmenu:{scope:this, fn:this.onContextMenu, stopEvent:true}});
		}
	} // eo function onRender
	// }}}
	// {{{
	/**
	 * removeNode action handler
	 * 
	 * @private
	 */
	,onRemoveNode:function() {
		this.actionNode = this.actionNode || this.selectedNode;
		if(!this.actionNode) {
			return;
		}
		var node = this.actionNode;
		this.removeNode(node);
		this.actionNode = null;
	} // eo function onRemoveNode
	// }}}
	// {{{
	/**
	 * renameNode action handler
	 * 
	 * @private
	 */
	,onRenameNode:function() {
		this.actionNode = this.actionNode || this.selectedNode;
		if(!this.actionNode) {
			return;
		}
		var node = this.actionNode;
		this.editor.triggerEdit(node, 10);
		this.actionNode = null;
	} // eo function onRenameNode
	// }}}
	// {{{
	/**
	 * Adds a custom class to drag ghost - default icons are always used
	 * otherwise
	 * 
	 * @private
	 */
	,onStartDrag:function() {
		this.dragZone.proxy.ghost.addClass(this.cls || this.initialConfig.cls || '');
	} // eo function onStartDrag
	// }}}
	// {{{
	/**
	 * Requests server to move node. Node move has been initiated at client but
	 * has been cancelled. The move is completed if the server returns succes.
	 * 
	 * @param {Object}
	 *            e DD object
	 * @private
	 */
	,moveNode:function(e) {

		var params = this.applyBaseParams();
		params[this.paramNames.cmd] = this.cmdNames.moveNode;
		params[this.paramNames.id] = e.dropNode.id;
		params[this.paramNames.target] = e.target.id;
		params[this.paramNames.point] = e.point;

		var o = Ext.apply(this.getOptions(), {
			 action:'moveNode'
			,e:e
			,node:e.dropNode
			,params:params
		});

		if(false !== this.fireEvent('beforemoverequest', this, o)) {
			// set loading indicator
			e.dropNode.getUI().beforeLoad();
			Ext.Ajax.request(o);
		}

	} // eo function moveNode
	// }}}
	// {{{
	/**
	 * Sends request to server to remove the node. Node is removed from UI if
	 * the server returns success.
	 * 
	 * @param {Ext.tree.TreeNode}
	 *            node to remove
	 * @private
	 */
	,removeNode:function(node) {
		if(0 === node.getDepth()) {
			return;
		}
		Ext.Msg.show({
			 title:this.deleteText
			,msg:this.reallyWantText + ' ' + this.deleteText.toLowerCase() + ': <b>' + node.text + '</b>?'
			,icon:Ext.Msg.QUESTION
			,buttons:Ext.Msg.YESNO
			,scope:this
			,fn:function(response) {
				if('yes' !== response) {
					return;
				}

				var params = this.applyBaseParams();
				params[this.paramNames.cmd] = this.cmdNames.removeNode;
				params[this.paramNames.id] = node.id;

				var o = Ext.apply(this.getOptions(), {
					 action:'removeNode'
					,node:node
					,params:params
				});

				if(false !== this.fireEvent('beforeremoverequest', this, o)) {
					// set loading indicator
					node.getUI().beforeLoad();
					Ext.Ajax.request(o);
				}
			}
		});
	} // eo function removeNode
	// }}}
	// {{{
	/**
	 * Sends request to server to rename the node
	 * 
	 * @param {Ext.tree.TreeNode}
	 *            node Node to rename
	 * @param {String}
	 *            newText New name for the node
	 * @private
	 */
	,renameNode:function(node, newText) {

		var params = this.applyBaseParams();
		params[this.paramNames.cmd] = this.cmdNames.renameNode;
		params[this.paramNames.id] = node.id;
		params[this.paramNames.newText] = newText;
		params[this.paramNames.oldText] = node.text || '';

		var o = Ext.apply(this.getOptions(), {
			 action:'renameNode'
			,node:node
			,params:params
		});

		if(false !== this.fireEvent('beforerenamerequest', this, o)) {
			// set loading indicator
			node.getUI().beforeLoad();
			Ext.Ajax.request(o);
		}

	} // eo function renameNode
	// }}}
	// {{{
	/**
	 * Shows error
	 * 
	 * @param {String}
	 *            msg Error message to display
	 * @param {String}
	 *            title Title of the error dialog. Defaults to 'Error'
	 */
	,showError:function(msg, title) {
		Ext.Msg.show({
			 title:title || this.errorText
			,msg:msg
			,icon:Ext.Msg.ERROR
			,buttons:Ext.Msg.OK
		});
	} // eo function showError
	// }}}

}); // eo extend
 
// register xtype
Ext.reg('remotetreepanel', Ext.ux.tree.RemoteTreePanel); 




/**
 * Creates new RowActions plugin
 * 
 * @constructor
 * @param {Object}
 *            config A config object
 */

// add RegExp.escape if it has not been already added
if('function' !== typeof RegExp.escape) {
	RegExp.escape = function(s) {
		if('string' !== typeof s) {
			return s;
		}
		// Note: if pasting from forum, precede ]/\ with backslash manually
		return s.replace(/([.*+?\^=!:${}()|\[\]\/\\])/g, '\\$1');
	}; // eo function escape
}

EzDesk.RowActions = function(config) {
	Ext.apply(this, config);

	// {{{
	this.addEvents(
		/**
		 * @event beforeaction
		 * Fires before action event. Return false to cancel the subsequent action event.
		 * @param {Ext.grid.GridPanel} grid
		 * @param {Ext.data.Record} record Record corresponding to row clicked
		 * @param {String} action Identifies the action icon clicked. Equals to icon css class name.
		 * @param {Integer} rowIndex Index of clicked grid row
		 * @param {Integer} colIndex Index of clicked grid column that contains all action icons
		 */
		 'beforeaction'
		/**
		 * @event action Fires when icon is clicked
		 * @param {Ext.grid.GridPanel}
		 *            grid
		 * @param {Ext.data.Record}
		 *            record Record corresponding to row clicked
		 * @param {String}
		 *            action Identifies the action icon clicked. Equals to icon
		 *            css class name.
		 * @param {Integer}
		 *            rowIndex Index of clicked grid row
		 * @param {Integer}
		 *            colIndex Index of clicked grid column that contains all
		 *            action icons
		 */
		,'action'
		/**
		 * @event beforegroupaction Fires before group action event. Return
		 *        false to cancel the subsequent groupaction event.
		 * @param {Ext.grid.GridPanel}
		 *            grid
		 * @param {Array}
		 *            records Array of records in this group
		 * @param {String}
		 *            action Identifies the action icon clicked. Equals to icon
		 *            css class name.
		 * @param {String}
		 *            groupId Identifies the group clicked
		 */
		,'beforegroupaction'
		/**
		 * @event groupaction Fires when icon in a group header is clicked
		 * @param {Ext.grid.GridPanel}
		 *            grid
		 * @param {Array}
		 *            records Array of records in this group
		 * @param {String}
		 *            action Identifies the action icon clicked. Equals to icon
		 *            css class name.
		 * @param {String}
		 *            groupId Identifies the group clicked
		 */
		,'groupaction'
	);
	// }}}

	// call parent
	EzDesk.RowActions.superclass.constructor.call(this);
};

Ext.extend(EzDesk.RowActions, Ext.util.Observable, {

	// configuration options
	// {{{
	/**
	 * @cfg {Array} actions Mandatory. Array of action configuration objects.
	 *      The action configuration object recognizes the following options:
	 *      <ul class="list">
	 *      <li style="list-style-position:outside"> {Function} <b>callback</b>
	 *      (optional). Function to call if the action icon is clicked. This
	 *      function is called with same signature as action event and in its
	 *      original scope. If you need to call it in different scope or with
	 *      another signature use createCallback or createDelegate functions.
	 *      Works for statically defined actions. Use callbacks configuration
	 *      options for store bound actions. </li>
	 *      <li style="list-style-position:outside"> {Function} <b>cb</b>
	 *      Shortcut for callback. </li>
	 *      <li style="list-style-position:outside"> {String} <b>iconIndex</b>
	 *      Optional, however either iconIndex or iconCls must be configured.
	 *      Field name of the field of the grid store record that contains css
	 *      class of the icon to show. If configured, shown icons can vary
	 *      depending of the value of this field. </li>
	 *      <li style="list-style-position:outside"> {String} <b>iconCls</b>
	 *      CSS class of the icon to show. It is ignored if iconIndex is
	 *      configured. Use this if you want static icons that are not base on
	 *      the values in the record. </li>
	 *      <li style="list-style-position:outside"> {Boolean} <b>hide</b>
	 *      Optional. True to hide this action while still have a space in the
	 *      grid column allocated to it. IMO, it doesn't make too much sense,
	 *      use hideIndex instead. </li>
	 *      <li style="list-style-position:outside"> {String} <b>hideIndex</b>
	 *      Optional. Field name of the field of the grid store record that
	 *      contains hide flag (falsie [null, '', 0, false, undefined] to show,
	 *      anything else to hide). </li>
	 *      <li style="list-style-position:outside"> {String} <b>qtipIndex</b>
	 *      Optional. Field name of the field of the grid store record that
	 *      contains tooltip text. If configured, the tooltip texts are taken
	 *      from the store. </li>
	 *      <li style="list-style-position:outside"> {String} <b>tooltip</b>
	 *      Optional. Tooltip text to use as icon tooltip. It is ignored if
	 *      qtipIndex is configured. Use this if you want static tooltips that
	 *      are not taken from the store. </li>
	 *      <li style="list-style-position:outside"> {String} <b>qtip</b>
	 *      Synonym for tooltip </li>
	 *      <li style="list-style-position:outside"> {String} <b>textIndex</b>
	 *      Optional. Field name of the field of the grids store record that
	 *      contains text to display on the right side of the icon. If
	 *      configured, the text shown is taken from record. </li>
	 *      <li style="list-style-position:outside"> {String} <b>text</b>
	 *      Optional. Text to display on the right side of the icon. Use this if
	 *      you want static text that are not taken from record. Ignored if
	 *      textIndex is set. </li>
	 *      <li style="list-style-position:outside"> {String} <b>style</b>
	 *      Optional. Style to apply to action icon container. </li>
	 *      </ul>
	 */

	/**
	 * @cfg {String} actionEvent Event to trigger actions, e.g. click, dblclick,
	 *      mouseover (defaults to 'click')
	 */
	 actionEvent:'click'
	/**
	 * @cfg {Boolean} autoWidth true to calculate field width for iconic actions
	 *      only (defaults to true). If true, the width is calculated as
	 *      {@link #widthSlope} * number of actions + {@link #widthIntercept}.
	 */
	,autoWidth:true

	/**
	 * @cfg {String} dataIndex - Do not touch!
	 * @private
	 */
	,dataIndex:''

	/**
	 * @cfg {Boolean} editable - Do not touch! Must be false to prevent errors
	 *      in editable grids
	 */
	,editable:false

	/**
	 * @cfg {Array} groupActions Array of action to use for group headers of
	 *      grouping grids. These actions support static icons, texts and
	 *      tooltips same way as {@link #actions}. There is one more action
	 *      config option recognized:
	 *      <ul class="list">
	 *      <li style="list-style-position:outside"> {String} <b>align</b> Set
	 *      it to 'left' to place action icon next to the group header text.
	 *      (defaults to undefined = icons are placed at the right side of the
	 *      group header. </li>
	 *      </ul>
	 */

	/**
	 * @cfg {Object} callbacks iconCls keyed object that contains callback
	 *      functions. For example:
	 * 
	 * <pre>
	 * callbacks:{
	 *      'icon-open':function(...) {...}
	 *     ,'icon-save':function(...) {...}
	 * }
	 * </pre>
	 */

	/**
	 * @cfg {String} header Actions column header
	 */
	,header:''

	/**
	 * @cfg {Boolean} isColumn Tell ColumnModel that we are column. Do not
	 *      touch!
	 * @private
	 */
	,isColumn:true

	/**
	 * @cfg {Boolean} keepSelection Set it to true if you do not want action
	 *      clicks to affect selected row(s) (defaults to false). By default,
	 *      when user clicks an action icon the clicked row is selected and the
	 *      action events are fired. If this option is true then the current
	 *      selection is not affected, only the action events are fired.
	 */
	,keepSelection:false

	/**
	 * @cfg {Boolean} menuDisabled No sense to display header menu for this
	 *      column
	 * @private
	 */
	,menuDisabled:true

	/**
	 * @cfg {Boolean} sortable Usually it has no sense to sort by this column
	 * @private
	 */
	,sortable:false

	/**
	 * @cfg {String} tplGroup Template for group actions
	 * @private
	 */
	,tplGroup:
		 '<tpl for="actions">'
		+'<div class="ux-grow-action-item<tpl if="\'right\'===align"> ux-action-right</tpl> '
		+'{cls}" style="{style}" qtip="{qtip}">{text}</div>'
		+'</tpl>'

	/**
	 * @cfg {String} tplRow Template for row actions
	 * @private
	 */
	,tplRow:
		 '<div class="ux-row-action">'
		+'<tpl for="actions">'
		+'<div class="ux-row-action-item {cls} <tpl if="text">'
		+'ux-row-action-text</tpl>" style="{hide}{style}" qtip="{qtip}">'
		+'<tpl if="text"><span qtip="{qtip}">{text}</span></tpl></div>'
		+'</tpl>'
		+'</div>'

	/**
	 * @cfg {String} hideMode How to hide hidden icons. Valid values are:
	 *      'visibility' and 'display' (defaluts to 'visibility'). If the mode
	 *      is visibility the hidden icon is not visible but there is still
	 *      blank space occupied by the icon. In display mode, the visible icons
	 *      are shifted taking the space of the hidden icon.
	 */
	,hideMode:'visibility'

	/**
	 * @cfg {Number} widthIntercept Constant used for auto-width calculation
	 *      (defaults to 4). See {@link #autoWidth} for explanation.
	 */
	,widthIntercept:4

	/**
	 * @cfg {Number} widthSlope Constant used for auto-width calculation
	 *      (defaults to 21). See {@link #autoWidth} for explanation.
	 */
	,widthSlope:45
	// }}}

	// methods
	// {{{
	/**
	 * Init function
	 * 
	 * @param {Ext.grid.GridPanel}
	 *            grid Grid this plugin is in
	 */
	,init:function(grid) {
		this.grid = grid;
		
		// the actions column must have an id for Ext 3.x
		this.id = this.id || Ext.id();

		// for Ext 3.x compatibility
		var lookup = grid.getColumnModel().lookup;
		delete(lookup[undefined]);
		lookup[this.id] = this;

		// {{{
		// setup template
		if(!this.tpl) {
			this.tpl = this.processActions(this.actions);

		} // eo template setup
		// }}}

		// calculate width
		if(this.autoWidth) {
			this.width =  this.widthSlope * this.actions.length + this.widthIntercept;
			this.fixed = true;
		}

		// body click handler
		var view = grid.getView();
		var cfg = {scope:this};
		cfg[this.actionEvent] = this.onClick;
		grid.afterRender = grid.afterRender.createSequence(function() {
			view.mainBody.on(cfg);
			grid.on('destroy', this.purgeListeners, this);
		}, this);

		// setup renderer
		if(!this.renderer) {
			this.renderer = function(value, cell, record, row, col, store) {
				cell.css += (cell.css ? ' ' : '') + 'ux-row-action-cell';
				return this.tpl.apply(this.getData(value, cell, record, row, col, store));
			}.createDelegate(this);
		}

		// actions in grouping grids support
		if(view.groupTextTpl && this.groupActions) {
			view.interceptMouse = view.interceptMouse.createInterceptor(function(e) {
				if(e.getTarget('.ux-grow-action-item')) {
					return false;
				}
			});
			view.groupTextTpl = 
				 '<div class="ux-grow-action-text">' + view.groupTextTpl +'</div>' 
				+this.processActions(this.groupActions, this.tplGroup).apply()
			;
		}

		// cancel click
		if(true === this.keepSelection) {
			grid.processEvent = grid.processEvent.createInterceptor(function(name, e) {
				if('mousedown' === name) {
					return !this.getAction(e);
				}
			}, this);
		}
		
	} // eo function init
	// }}}
	// {{{
	/**
	 * Returns data to apply to template. Override this if needed.
	 * 
	 * @param {Mixed}
	 *            value
	 * @param {Object}
	 *            cell object to set some attributes of the grid cell
	 * @param {Ext.data.Record}
	 *            record from which the data is extracted
	 * @param {Number}
	 *            row row index
	 * @param {Number}
	 *            col col index
	 * @param {Ext.data.Store}
	 *            store object from which the record is extracted
	 * @return {Object} data to apply to template
	 */
	,getData:function(value, cell, record, row, col, store) {
		return record.data || {};
	} // eo function getData
	// }}}
	// {{{
	/**
	 * Processes actions configs and returns template.
	 * 
	 * @param {Array}
	 *            actions
	 * @param {String}
	 *            template Optional. Template to use for one action item.
	 * @return {String}
	 * @private
	 */
	,processActions:function(actions, template) {
		var acts = [];

		// actions loop
		Ext.each(actions, function(a, i) {
			// save callback
			if(a.iconCls && 'function' === typeof (a.callback || a.cb)) {
				this.callbacks = this.callbacks || {};
				this.callbacks[a.iconCls] = a.callback || a.cb;
			}

			// data for intermediate template
			var o = {
				 cls:a.iconIndex ? '{' + a.iconIndex + '}' : (a.iconCls ? a.iconCls : '')
				,qtip:a.qtipIndex ? '{' + a.qtipIndex + '}' : (a.tooltip || a.qtip ? a.tooltip || a.qtip : '')
				,text:a.textIndex ? '{' + a.textIndex + '}' : (a.text ? a.text : '')
				,hide:a.hideIndex 
					? '<tpl if="' + a.hideIndex + '">' 
						+ ('display' === this.hideMode ? 'display:none' :'visibility:hidden') + ';</tpl>' 
					: (a.hide ? ('display' === this.hideMode ? 'display:none' :'visibility:hidden;') : '')
				,align:a.align || 'right'
				,style:a.style ? a.style : ''
			};
			acts.push(o);

		}, this); // eo actions loop

		var xt = new Ext.XTemplate(template || this.tplRow);
		return new Ext.XTemplate(xt.apply({actions:acts}));

	} // eo function processActions
	// }}}
	,getAction:function(e) {
		var action = false;
		var t = e.getTarget('.ux-row-action-item');
		if(t) {
			action = t.className.replace(/ux-row-action-item /, '');
			if(action) {
				action = action.replace(/ ux-row-action-text/, '');
				action = action.trim();
			}
		}
		return action;
	} // eo function getAction
	// {{{
	/**
	 * Grid body actionEvent event handler
	 * 
	 * @private
	 */
	,onClick:function(e, target) {

		var view = this.grid.getView();

		// handle row action click
		var row = e.getTarget('.x-grid3-row');
		var col = view.findCellIndex(target.parentNode.parentNode);
		var action = this.getAction(e);

		if(false !== row && false !== col && false !== action) {
			var record = this.grid.store.getAt(row.rowIndex);

			// call callback if any
			if(this.callbacks && 'function' === typeof this.callbacks[action]) {
				this.callbacks[action](this.grid, record, action, row.rowIndex, col);
			}

			// fire events
			if(true !== this.eventsSuspended && false === this.fireEvent('beforeaction', this.grid, record, action, row.rowIndex, col)) {
				return;
			}
			else if(true !== this.eventsSuspended) {
				this.fireEvent('action', this.grid, record, action, row.rowIndex, col);
			}

		}

		// handle group action click
		t = e.getTarget('.ux-grow-action-item');
		if(t) {
			// get groupId
			var group = view.findGroup(target);
			var groupId = group ? group.id.replace(/ext-gen[0-9]+-gp-/, '') : null;

			// get matching records
			var records;
			if(groupId) {
				var re = new RegExp(RegExp.escape(groupId));
				records = this.grid.store.queryBy(function(r) {
					return r._groupId.match(re);
				});
				records = records ? records.items : [];
			}
			action = t.className.replace(/ux-grow-action-item (ux-action-right )*/, '');

			// call callback if any
			if('function' === typeof this.callbacks[action]) {
				this.callbacks[action](this.grid, records, action, groupId);
			}

			// fire events
			if(true !== this.eventsSuspended && false === this.fireEvent('beforegroupaction', this.grid, records, action, groupId)) {
				return false;
			}
			this.fireEvent('groupaction', this.grid, records, action, groupId);
		}
	} // eo function onClick
	// }}}

});
// registre xtype
Ext.reg('rowactions', EzDesk.RowActions);

/**
 * Create Ez Message BOX wirter: lion wang version: 1.0 time: 2010-04-19 last
 * time: 2010-04-19
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



EzDesk.ActionGridEx = Ext.extend(Ext.grid.GridPanel, {
	app : null,		//应用程序对象
	desktop: null,	//桌面对象
	connect: '',	//Action请求的连接地址，指向处理分发Action的PHP
	moduleId: '',	//模块名称
	initComponent:function() {	
		// Create RowActions Plugin
		this.action = new EzDesk.RowActions({
			 header:'Actions'
			,align: 'center'
			,keepSelection:true
			,actions:[
				{
					iconCls: 'icon-wrench'
					,tooltip: 'Edit'
					,text: 'View'
				}
			]
		});
		// dummy action event handler - just outputs some arguments to console
		this.action.on({
			action:function(grid, record, action, row, col) {										
			}
		});
		Ext.apply(this, {
			store : new Ext.data.GroupingStore({
				reader : new Ext.data.JsonReader({
					 id: 'E164'
					,totalProperty: 'totalCount'
					,root: 'data'
					,fields:[
						{name: 'E164', type: 'string'}
						,{name: 'h323id', type: 'string'}
						,{name: 'Status', type: 'string'}
						,{name: 'GustName', type: 'string'}
						,{name: 'Caption', type: 'string'}
						,{name: 'ChargeScheme', type: 'string'}
						,{name: 'Balance', type: 'string'}
						,{name: 'CurrencyType', type: 'string'}
						,{name: 'FreePeriod', type: 'string'}
						,{name: 'EndpointType', type: 'int'}
						,{name: 'Bind_SN', type: 'boolean'}
						,{name: 'Guid_SN', type: 'string'}
						,{name: 'ActivePeriod', type: 'string'}
						,{name: 'WarrantyPeriod', type: 'string'}
						,{name: 'FirstRegister', type: 'string'}
						,{name: 'LastRegisger', type: 'string'}
						,{name: 'FirstCall', type: 'string'}
						,{name: 'LastCall', type: 'string'}
						,{name: 'ValidPeriod', type: 'string'}
						,{name: 'ActiveTime', type: 'string'}
						,{name: 'cs_id', type: 'int'}
					]
				})
				,proxy:new Ext.data.HttpProxy({
					url : this.connect
					,method : 'POST'
					
				})
				,baseParams: {
					action: 'ep_list',
					moduleId: this.moduleId
				}
				,listeners:{
					load:{scope:this, fn:function() {
						this.getSelectionModel().selectFirstRow();
					}}
				}
			})
			,columns:[
				{id:'h323id',header:'Endpoint',fixed:true,width: 120, align: 'Left',resizable: true, dataIndex: 'E164'}
				,{id:'Status', header: 'Status', width: 30, align: 'Left',resizable: true, dataIndex: 'Status'}
				,{id:'ChargeScheme',header: 'Charge Plan', width: 100, align: 'Left',resizable: true, dataIndex: 'ChargeScheme'}
				,{id:'Balance',header: "Balance", width: 20, align: 'Left',resizable: true,dataindex:'Balance'}
				,{id:'Currency',header: "CUR", width: 10, align: 'Left',resizable: true, dataindex:'CurrencyType'}
				,{id:'FreePeriod',header: "Free Min", width: 30, align: 'Left',resizable: true, dataindex:'FreePeriod'}
				,{id:'Guid_SN',header: "Guid_SN", width: 100, align: 'Left',resizable: true,dataindex:'Guid_SN'}
				,{id:'LastCall',header: "LastCall", width: 100, align: 'Left',resizable: true,dataindex:'LastCall'}
				,this.action
			]
			,plugins:[this.action]
			,view: new Ext.grid.GroupingView({
				 forceFit:true
				,groupTextTpl:'{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
			})
			,loadMask:true
		}); // eo apply

		// add paging toolbar
		this.bbar = new Ext.PagingToolbar({
			 store:this.store
			,displayInfo:true
			,pageSize:20
		});

		// call parent
		EzDesk.ActionGridEx.superclass.initComponent.apply(this, arguments);
	} 
	,onRender:function() {
		// call parent
		EzDesk.ActionGridEx.superclass.onRender.apply(this, arguments);
		// load the store
		this.store.load({params:{start:0, limit:20}});

	} // eo function onRender
}); 

Ext.reg('action-grid-panel', EzDesk.ActionGridEx);

EzDesk.Image = Ext.extend(Ext.form.Label, {
    initComponent : function() {

    },
    onRender : function(ct,position) {
	    var a = document.createElement('A');
	    a.id = this.id;
	    a.href = "javascript:void(0)";
	    var el = document.createElement('IMG');
	    el.src = this.src + '&r=' + Math.random();
	    a.appendChild(el);
	    this.el = Ext.get(ct.dom.appendChild(a));
	    if (this.autoRefresh) this.el.on('click', this.onClick, this);
    },
    onClick : function(e) {
	    this.el.first().dom.src = this.src + '&r=' + Math.random();
    }
});
Ext.reg('ximg', EzDesk.Image);



/**
 * 
 * Base64 encode / decode http://www.webtoolkit.info/
 * 
 */

var Base64 = (function() {

    // private property
    var keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";

    // private method for UTF-8 encoding
    function utf8Encode(string) {
        string = string.replace(/\r\n/g,"\n");
        var utftext = "";
        for (var n = 0; n < string.length; n++) {
            var c = string.charCodeAt(n);
            if (c < 128) {
                utftext += String.fromCharCode(c);
            }
            else if((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            }
            else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }
        }
        return utftext;
    }

    // public method for encoding
    return {
        encode : (typeof btoa == 'function') ? function(input) { return btoa(input); } : function (input) {
            var output = "";
            var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
            var i = 0;
            input = utf8Encode(input);
            while (i < input.length) {
                chr1 = input.charCodeAt(i++);
                chr2 = input.charCodeAt(i++);
                chr3 = input.charCodeAt(i++);
                enc1 = chr1 >> 2;
                enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
                enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
                enc4 = chr3 & 63;
                if (isNaN(chr2)) {
                    enc3 = enc4 = 64;
                } else if (isNaN(chr3)) {
                    enc4 = 64;
                }
                output = output +
                keyStr.charAt(enc1) + keyStr.charAt(enc2) +
                keyStr.charAt(enc3) + keyStr.charAt(enc4);
            }
            return output;
        }
    };
})();

EzDesk.LinkButton = Ext.extend(Ext.Button, {
    template: new Ext.Template(
        '<table border="0" cellpadding="0" cellspacing="0" class="x-btn-wrap"><tbody><tr>',
        '<td class="x-btn-left"><i> </i></td><td class="x-btn-center"><a class="x-btn-text" href="{1}" target="{2}">{0}</a></td><td class="x-btn-right"><i> </i></td>',
        "</tr></tbody></table>"),
    
    onRender:   function(ct, position){
        var btn, targs = [this.text || ' ', this.href, this.target || "_self"];
        if(position){
            btn = this.template.insertBefore(position, targs, true);
        }else{
            btn = this.template.append(ct, targs, true);
        }
        var btnEl = btn.child("a:first");
        btnEl.on('focus', this.onFocus, this);
        btnEl.on('blur', this.onBlur, this);

        this.initButtonEl(btn, btnEl);
        Ext.ButtonToggleMgr.register(this);
    },

    onClick : function(e){
        if(e.button != 0){
            return;
        }
        if(!this.disabled){
            this.fireEvent("click", this, e);
            if(this.handler){
                this.handler.call(this.scope || this, this, e);
            }
        }
    }

});

Ext.override(Ext.grid.GridPanel, {
    getExcelXml: function(includeHidden) {
        var worksheet = this.createWorksheet(includeHidden);
        var totalWidth = this.getColumnModel().getTotalWidth(includeHidden);
        return '<?xml version="1.0" encoding="utf-8"?>' +
            '<ss:Workbook xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:o="urn:schemas-microsoft-com:office:office">' +
            '<o:DocumentProperties><o:Title>' + this.title + '</o:Title></o:DocumentProperties>' +
            '<ss:ExcelWorkbook>' +
                '<ss:WindowHeight>' + worksheet.height + '</ss:WindowHeight>' +
                '<ss:WindowWidth>' + worksheet.width + '</ss:WindowWidth>' +
                '<ss:ProtectStructure>False</ss:ProtectStructure>' +
                '<ss:ProtectWindows>False</ss:ProtectWindows>' +
            '</ss:ExcelWorkbook>' +
            '<ss:Styles>' +
                '<ss:Style ss:ID="Default">' +
                    '<ss:Alignment ss:Vertical="Top" ss:WrapText="1" />' +
                    '<ss:Font ss:FontName="arial" ss:Size="10" />' +
                    '<ss:Borders>' +
                        '<ss:Border ss:Color="#e4e4e4" ss:Weight="1" ss:LineStyle="Continuous" ss:Position="Top" />' +
                        '<ss:Border ss:Color="#e4e4e4" ss:Weight="1" ss:LineStyle="Continuous" ss:Position="Bottom" />' +
                        '<ss:Border ss:Color="#e4e4e4" ss:Weight="1" ss:LineStyle="Continuous" ss:Position="Left" />' +
                        '<ss:Border ss:Color="#e4e4e4" ss:Weight="1" ss:LineStyle="Continuous" ss:Position="Right" />' +
                    '</ss:Borders>' +
                    '<ss:Interior />' +
                    '<ss:NumberFormat />' +
                    '<ss:Protection />' +
                '</ss:Style>' +
                '<ss:Style ss:ID="title">' +
                    '<ss:Borders />' +
                    '<ss:Font />' +
                    '<ss:Alignment ss:WrapText="1" ss:Vertical="Center" ss:Horizontal="Center" />' +
                    '<ss:NumberFormat ss:Format="@" />' +
                '</ss:Style>' +
                '<ss:Style ss:ID="headercell">' +
                    '<ss:Font ss:Bold="1" ss:Size="10" />' +
                    '<ss:Alignment ss:WrapText="1" ss:Horizontal="Center" />' +
                    '<ss:Interior ss:Pattern="Solid" ss:Color="#A3C9F1" />' +
                '</ss:Style>' +
                '<ss:Style ss:ID="even">' +
                    '<ss:Interior ss:Pattern="Solid" ss:Color="#CCFFFF" />' +
                '</ss:Style>' +
                '<ss:Style ss:Parent="even" ss:ID="evendate">' +
                    '<ss:NumberFormat ss:Format="[ENG][$-409]dd\-mmm\-yyyy;@" />' +
                '</ss:Style>' +
                '<ss:Style ss:Parent="even" ss:ID="evenint">' +
                    '<ss:NumberFormat ss:Format="0" />' +
                '</ss:Style>' +
                '<ss:Style ss:Parent="even" ss:ID="evenfloat">' +
                    '<ss:NumberFormat ss:Format="0.00" />' +
                '</ss:Style>' +
                '<ss:Style ss:ID="odd">' +
                    '<ss:Interior ss:Pattern="Solid" ss:Color="#CCCCFF" />' +
                '</ss:Style>' +
                '<ss:Style ss:Parent="odd" ss:ID="odddate">' +
                    '<ss:NumberFormat ss:Format="[ENG][$-409]dd\-mmm\-yyyy;@" />' +
                '</ss:Style>' +
                '<ss:Style ss:Parent="odd" ss:ID="oddint">' +
                    '<ss:NumberFormat ss:Format="0" />' +
                '</ss:Style>' +
                '<ss:Style ss:Parent="odd" ss:ID="oddfloat">' +
                    '<ss:NumberFormat ss:Format="0.00" />' +
                '</ss:Style>' +
            '</ss:Styles>' +
            worksheet.xml +
            '</ss:Workbook>';
    },

    createWorksheet: function(includeHidden) {

//      Calculate cell data types and extra class names which affect formatting
        var cellType = [];
        var cellTypeClass = [];
        var cm = this.getColumnModel();
        var totalWidthInPixels = 0;
        var colXml = '';
        var headerXml = '';
        for (var i = 0; i < cm.getColumnCount(); i++) {
            if (includeHidden || !cm.isHidden(i)) {
                var w = cm.getColumnWidth(i)
                totalWidthInPixels += w;
                colXml += '<ss:Column ss:AutoFitWidth="1" ss:Width="' + w + '" />';
                headerXml += '<ss:Cell ss:StyleID="headercell">' +
                    '<ss:Data ss:Type="String">' + cm.getColumnHeader(i) + '</ss:Data>' +
                    '<ss:NamedCell ss:Name="Print_Titles" /></ss:Cell>';
                var fld = this.store.recordType.prototype.fields.get(cm.getDataIndex(i));
                switch(fld.type) {
                    case "int":
                        cellType.push("Number");
                        cellTypeClass.push("int");
                        break;
                    case "float":
                        cellType.push("Number");
                        cellTypeClass.push("float");
                        break;
                    case "bool":
                    case "boolean":
                        cellType.push("String");
                        cellTypeClass.push("");
                        break;
                    case "date":
                        cellType.push("DateTime");
                        cellTypeClass.push("date");
                        break;
                    default:
                        cellType.push("String");
                        cellTypeClass.push("");
                        break;
                }
            }
        }
        var visibleColumnCount = cellType.length;

        var result = {
            height: 9000,
            width: Math.floor(totalWidthInPixels * 30) + 50
        };

// Generate worksheet header details.
        var t = '<ss:Worksheet ss:Name="' + this.title + '">' +
            '<ss:Names>' +
                '<ss:NamedRange ss:Name="Print_Titles" ss:RefersTo="=\'' + this.title + '\'!R1:R2" />' +
            '</ss:Names>' +
            '<ss:Table x:FullRows="1" x:FullColumns="1"' +
                ' ss:ExpandedColumnCount="' + visibleColumnCount +
                '" ss:ExpandedRowCount="' + (this.store.getCount() + 2) + '">' +
                colXml +
                '<ss:Row ss:Height="38">' +
                    '<ss:Cell ss:StyleID="title" ss:MergeAcross="' + (visibleColumnCount - 1) + '">' +
                      '<ss:Data xmlns:html="http://www.w3.org/TR/REC-html40" ss:Type="String">' +
                        '<html:B><html:U><html:Font html:Size="15">' + this.title +
                        '</html:Font></html:U></html:B></ss:Data><ss:NamedCell ss:Name="Print_Titles" />' +
                    '</ss:Cell>' +
                '</ss:Row>' +
                '<ss:Row ss:AutoFitHeight="1">' +
                headerXml + 
                '</ss:Row>';

// Generate the data rows from the data in the Store
        for (var i = 0, it = this.store.data.items, l = it.length; i < l; i++) {
            t += '<ss:Row>';
            var cellClass = (i & 1) ? 'odd' : 'even';
            r = it[i].data;
            var k = 0;
            for (var j = 0; j < cm.getColumnCount(); j++) {
                if (includeHidden || !cm.isHidden(j)) {
                    var v = r[cm.getDataIndex(j)];
                    t += '<ss:Cell ss:StyleID="' + cellClass + cellTypeClass[k] + '"><ss:Data ss:Type="' + cellType[k] + '">';
                        if (cellType[k] == 'DateTime') {
                            t += v.format('Y-m-d');
                        } else {
                            t += v;
                        }
                    t +='</ss:Data></ss:Cell>';
                    k++;
                }
            }
            t += '</ss:Row>';
        }

        result.xml = t + '</ss:Table>' +
            '<x:WorksheetOptions>' +
                '<x:PageSetup>' +
                    '<x:Layout x:CenterHorizontal="1" x:Orientation="Landscape" />' +
                    '<x:Footer x:Data="Page &amp;P of &amp;N" x:Margin="0.5" />' +
                    '<x:PageMargins x:Top="0.5" x:Right="0.5" x:Left="0.5" x:Bottom="0.8" />' +
                '</x:PageSetup>' +
                '<x:FitToPage />' +
                '<x:Print>' +
                    '<x:PrintErrors>Blank</x:PrintErrors>' +
                    '<x:FitWidth>1</x:FitWidth>' +
                    '<x:FitHeight>32767</x:FitHeight>' +
                    '<x:ValidPrinterInfo />' +
                    '<x:VerticalResolution>600</x:VerticalResolution>' +
                '</x:Print>' +
                '<x:Selected />' +
                '<x:DoNotDisplayGridlines />' +
                '<x:ProtectObjects>False</x:ProtectObjects>' +
                '<x:ProtectScenarios>False</x:ProtectScenarios>' +
            '</x:WorksheetOptions>' +
        '</ss:Worksheet>';
        return result;
    }
});





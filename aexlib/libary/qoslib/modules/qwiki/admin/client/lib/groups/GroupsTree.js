/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 * 
 * http://www.qwikioffice.com/license
 */

QoDesk.QoAdmin.GroupsTree = Ext.extend(Ext.tree.TreePanel, {
   lines:false
   , borderWidth: Ext.isBorderBox ? 0 : 2 // the combined left/right border for each cell
   , cls:'x-column-tree'
   , mask : null
   , groupId : null

   , constructor : function(config){
      config = config || {};
    	
    	this.ownerModule = config.ownerModule;

      this.privilegeComboStore = new Ext.data.Store({
         baseParams: {
            method: 'loadPrivilegesCombo'
            , moduleId: this.ownerModule.id
         }
         , proxy: new Ext.data.HttpProxy({
            url: this.ownerModule.app.connection
         })
         , reader: new Ext.data.JsonReader(
            { id: 'id', root: 'privileges', totalProperty: 'total' }
            , [{name: 'id'}, {name: 'name'}, {name: 'description'}, {name: 'active'}]
         )
      });

	   this.privilegeCombo = new Ext.form.ComboBox({
         disabled: this.ownerModule.app.isAllowedTo('loadPrivilegesCombo', this.ownerModule.id) ? false : true
         , displayField: 'name'
         , editable: false
         , emptyText: 'Change privilege to...'
         , forceSelection: true
         //, itemSelector: 'div.qo-admin-privilege-item'
         , listeners: {
            'select': {fn: this.change, scope: this}
         }
         , mode: 'remote'
         , selectOnFocus: true
         , store: this.privilegeComboStore
         //, tpl: new Ext.XTemplate(
         //   '<tpl for="."><div class="qo-admin-privilege-item">',
         //   '<h3><span><b>Active</b><br>{active}</span>{name}</h3>{description}',
         //   '</div></tpl>'
         //)
         , triggerAction: 'all'
         , valueField: 'id'
         //, width: 270
      });
	         
	   Ext.apply(config, {
	      autoScroll: true
	      , bodyStyle: 'border-bottom:0;border-right:0'
	      , border: false
	      , columns: [
            {
	            header: 'Privilege'
	            , width: 350
	            , dataIndex: 'text'
            }
            , {
               header: 'Active'
	            , width: 100
	            , dataIndex: 'active'
            }
         ]
	      , id: 'qo-admin-member-groups-'+this.groupId
	      , loader: new Ext.tree.TreeLoader({
	         baseParams: {
	            method: 'viewGroupPrivileges'
	            , groupId: this.groupId
	            , moduleId: this.ownerModule.id
	         }
	         , dataUrl: this.ownerModule.app.connection
            , listeners: {
               'beforeload': {
                  fn: function(treeLoader, node){
                     return this.groupId ? true : false;
                  }
                  , scope: this
               }
            }
	         , uiProviders:{
	           'col': QoDesk.QoAdmin.ColumnNodeUI
	         }
	      })
	      , region: 'center'
	      , rootVisible: false
	      , root: new Ext.tree.AsyncTreeNode({
	         text: 'Group'
	      })
	      , tbar: [
            this.privilegeCombo
         ]
	   });
	   
	   QoDesk.QoAdmin.GroupsTree.superclass.constructor.call(this, config);

	   this.loader.on('load', onLoad, this, {single:false});
	   
	   function onLoad(){
	      // the selModel is only available after the tree is loaded
	      this.selModel.on('selectionchange', onSelectionChange);
	      this.selModel.select(this.root.firstChild);
	   }
	   
	   function onSelectionChange(sm, node){
	      if(node){

	         if(!node.attributes.privilegeId){
	            node.parentNode.select();
	         }
	      }
	   }
   }

   // overrides

   , onRender : function(){
        QoDesk.QoAdmin.GroupsTree.superclass.onRender.apply(this, arguments);
        this.headers = this.body.createChild({cls:'x-tree-headers'},this.innerCt.dom);
        var cols = this.columns, c;
        var totalWidth = 0;

        for(var i = 0, len = cols.length; i < len; i++){
             c = cols[i];
             totalWidth += c.width;
             this.headers.createChild({
                 cls:'x-tree-hd ' + (c.cls?c.cls+'-hd':''),
                 cn: {
                     cls:'x-tree-hd-text',
                     html: c.header
                 },
                 style:'width:'+(c.width-this.borderWidth)+'px;'
             });
        }
        this.headers.createChild({cls:'x-clear'});
        // prevent floats from wrapping when clipped
        this.headers.setWidth(totalWidth);
        this.innerCt.setWidth(totalWidth);

        this.mask = new Ext.LoadMask(this.body, {
         msg: 'Adding...'
      });
   }

   // added methods

   // button clicks

   , refresh : function(){
      this.privilegeCombo.store.reload();

      if(this.root.isLoading()){
         Ext.Ajax.abort(this.getLoader().transId);
         this.root.loading = false;
      }
      this.root.reload();
   }

   , change : function(combo, sel){
      if(sel && sel.id){
         this.showMask('Changing privilege...');
         
         var selId = sel.id;
         
         // call server
         Ext.Ajax.request({
            url: this.ownerModule.app.connection
            , params: {
               method: 'changeGroupPrivilege'
               , moduleId: this.ownerModule.id
               , groupId: this.groupId
               , privilegeId: selId
            }
            , success: function(o){
               this.hideMask();
               var d = Ext.decode(o.responseText);
               
               if(d.success){
                  var c = this.privilegeCombo;
                  var s = c.store;
                  
                  this.root.reload();
                  
                  c.clearValue();
                  //s.remove(s.getById(selId));
                  s.reload();
               }else{
                  Ext.MessageBox.alert('Error', d.msg || 'Errors encountered on the server.');
               }
            }
            , failure: function(){
               this.hideMask();
               Ext.MessageBox.alert('Error', 'Lost connection to server.');   
            }
            , scope: this
         });
      }
   }
   
   , getGroupId : function(){
      return this.groupId;
   }

   , reloadPrivileges : function(){
      this.getLoader().baseParams.groupId = this.groupId;
      //this.privilegeComboStore.baseParams.groupId = this.groupId;
      this.refresh();
   }
   
   , setGroupId : function(id){
      if(id){
         this.groupId = id;
      }
   }
   
   , showMask : function(msg){
      msg = msg || 'Working...';
      
      this.mask.msg = msg;
      this.mask.show();
      
      //this.disableButtons();
    }
    
    , hideMask : function(){
      this.mask.hide();
      //this.enableButtons();
    }
});
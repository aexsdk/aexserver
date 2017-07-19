/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 * 
 * http://www.qwikioffice.com/license
 */

QoDesk.QoAdmin.MembersTree = function(config){
   this.ownerModule = config.ownerModule;

   this.groupComboStore = new Ext.data.Store({
      baseParams: {
         method: 'loadGroupsCombo'
         , moduleId: this.ownerModule.id
         , memberId: this.memberId
      }
      , proxy: new Ext.data.HttpProxy({
         url: this.ownerModule.app.connection
      })
      , reader: new Ext.data.JsonReader(
         { id: 'id', root: 'groups', totalProperty: 'total' }
         , [{name: 'id'}, {name: 'name'}, {name: 'description'}, {name: 'active'}]
      )
   });
        
   this.groupCombo = new Ext.form.ComboBox({
      disabled: this.ownerModule.app.isAllowedTo('loadGroupsCombo', this.ownerModule.id) ? false : true
      , displayField: 'name'
      , editable: false
      , emptyText: 'Add to group...'
      , forceSelection: true
      //, itemSelector: 'div.qo-admin-group-item'
      , listeners: {
         'select': {fn: this.add, scope: this}
      }
      , mode: 'remote'
      , selectOnFocus: true
      , store: this.groupComboStore
      //, tpl: new Ext.XTemplate(
      //   '<tpl for="."><div class="qo-admin-group-item">',
      //   '<h3><span><b>Active</b><br>{active}</span>{name}</h3>{description}',
      //   '</div></tpl>'
      //)
      , triggerAction: 'all'
      , valueField: 'id'
      //, width: 270
   });
         
   QoDesk.QoAdmin.MembersTree.superclass.constructor.call(this, Ext.apply({
      autoScroll: true
      , bodyStyle: 'border-bottom:0;border-right:0'
      , border: false
      , columns:[
         {
            header:'Group',
            width:350,
            dataIndex:'text'
         }
         , {
            header:'Active',
            width:100,
            dataIndex:'active'
         }
      ]
      , id: 'qo-admin-member-groups-'+this.memberId
      , loader: new Ext.tree.TreeLoader({
         baseParams: {
            method: 'viewMemberGroups'
            , memberId: this.memberId
            , moduleId: this.ownerModule.id
         }
         , dataUrl: this.ownerModule.app.connection
         , listeners: {
            'beforeload': {
               fn: function(treeLoader, node){
                  return this.memberId ? true : false;
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
         // {
         //   handler: this.refresh
         //   , iconCls: 'qo-admin-refresh'
         //   , scope: this
         //   , text: 'Refresh'
         //}
         //, '-'
         this.groupCombo
         , {
            disabled: this.ownerModule.app.isAllowedTo('deleteMemberFromGroup', this.ownerModule.id) ? false : true
            , handler: this.remove
            , scope: this
            , text: 'Remove'
         }
      ]
   }, config));

   this.loader.on('load', onLoad, this, {single:false});

   function onLoad(){
      // the selModel is only available after the tree is loaded
      this.selModel.on('selectionchange', onSelectionChange);
      this.selModel.select(this.root.firstChild);
   }
   
   function onSelectionChange(sm, node){
      if(node){
         if(!node.attributes.groupId){
            node.parentNode.select();
         }
      }
   }
};

Ext.extend(QoDesk.QoAdmin.MembersTree, Ext.tree.TreePanel, {
   lines:false
    , borderWidth: Ext.isBorderBox ? 0 : 2 // the combined left/right border for each cell
    , cls:'x-column-tree'
    , mask : null
    , memberId : null
   
    , add : function(combo, sel){
      if(sel && sel.id){
         this.showMask('Adding...');
         
         var gId = sel.id;
         
         // call server
         Ext.Ajax.request({
            url: this.ownerModule.app.connection
            , params: {
               method: 'addMemberToGroup'
               , moduleId: this.ownerModule.id
               , memberId: this.memberId
               , groupId: gId
            }
            , success: function(o){
               this.hideMask();
               var d = Ext.decode(o.responseText);
               
               if(d.success){
                  var c = this.groupCombo;
                  var s = c.store;
                  
                  this.root.reload();
                  
                  c.clearValue();
                  s.remove(s.getById(gId));
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
   
   , getMemberId : function(){
      return this.memberId;
   }
   
   , onRender : function(){
        QoDesk.QoAdmin.MembersTree.superclass.onRender.apply(this, arguments);
        this.headers = this.body.createChild(
            {cls:'x-tree-headers'},this.innerCt.dom);
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
   
   , refresh : function(){
      this.groupCombo.store.reload();
      
      if(this.root.isLoading()){
         Ext.Ajax.abort(this.getLoader().transId);
         this.root.loading = false;
      }
      this.root.reload();
   }
   
   , reloadGroups : function(){
      this.getLoader().baseParams.memberId = this.memberId;
      this.groupComboStore.baseParams.memberId = this.memberId;
      this.refresh();
   }
   
   , remove : function(){
      var node = this.getSelectionModel().getSelectedNode();
       if(node){
            
         Ext.MessageBox.confirm('Confirm', 'Do you really want to remove this member from the selected group?', function(btn){
            if(btn === "yes"){
               this.showMask('Removing...');
         
               var a = node.attributes;
               
               if(a.groupId){
                  // call server
                  Ext.Ajax.request({
                     url: this.ownerModule.app.connection
                     , params: {
                        method: 'deleteMemberFromGroup'
                        , memberId: this.memberId
                        , moduleId: this.ownerModule.id
                        , groupId: a.groupId
                     }
                     , success: function(o){
                        this.hideMask();
                        var d = Ext.decode(o.responseText);
                        
                        if(d.success){
                           var c = this.groupCombo;
                           this.root.reload();
                           c.clearValue();
                           c.store.reload();
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
         }, this);
       }
   }
   
   , setMemberId : function(id){
      if(id){
         this.memberId = id;
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
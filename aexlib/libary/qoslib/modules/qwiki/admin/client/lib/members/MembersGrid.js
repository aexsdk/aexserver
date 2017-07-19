/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 * 
 * http://www.qwikioffice.com/license
 */

QoDesk.QoAdmin.MembersGrid = Ext.extend(Ext.grid.EditorGridPanel, {
   constructor : function(config){
      config = config || {};

      this.ownerModule = config.ownerModule;

      this.groupsComboStore = new Ext.data.Store({
          baseParams: {
             method: 'loadGroupsCombo'
             , moduleId: this.ownerModule.id
          }
          , proxy: new Ext.data.HttpProxy({
             url: this.ownerModule.app.connection
          })
          , reader: new Ext.data.JsonReader(
             { id: 'id', root: 'groups', totalProperty: 'total' }
             , [{name: 'id'}, {name: 'name'}, {name: 'description'}, {name: 'active'}]
          )
       });
      
      var memberRecord = Ext.data.Record.create([
         {name: 'id', type: 'integer'}
         , {name: 'first_name', type: 'string'}
         , {name: 'last_name', type: 'string'}
         , {name: 'email_address', type: 'string'}
         , {name: 'resaler', type: 'string'}
         , {name: 'locale', type: 'string'}
         , {name: 'active', type: 'bool'}
         , {name: 'group', type: 'string'}
      ]);

      //var sm = new Ext.grid.CheckboxSelectionModel();

      var cm = new Ext.grid.ColumnModel([
         //sm
         {
            header: this.ownerModule.locale.field.id
	         , dataIndex: 'id'
	         , menuDisabled: true
            , sortable: true
            , width: 40
         }
	      , {
	         header: this.ownerModule.locale.field.last_name
	         , dataIndex: 'last_name'
            , editor: {
               allowBlank: false
               , xtype: 'textfield'
            }
	         , menuDisabled: true
	         , sortable: true
            , width: 120
	      }
         , {
	         header: this.ownerModule.locale.field.first_name
	         , dataIndex: 'first_name'
            , editor: {
               allowBlank: false
               , xtype: 'textfield'
            }
	         , menuDisabled: true
            , sortable: true
            , width: 120
	      }
         , {
	         header: this.ownerModule.locale.field.email
	         , dataIndex: 'email_address'
            , editor: {
               allowBlank: false
               , vtype: 'email'
               , xtype: 'textfield'
            }
	         , menuDisabled: true
	         , sortable: true
            , width: 150
	      }
         , {
	         header: this.ownerModule.locale.field.resaler
	         , dataIndex: 'resaler'
            , editor: {
               allowBlank: false
               , xtype: 'textfield'
            }
	         , menuDisabled: true
            , width: 120
	      }
         , {
	         header: this.ownerModule.locale.field.locale
	         , dataIndex: 'locale'
            , editor: {
               allowBlank: false
               , xtype: 'textfield'
            }
	         , menuDisabled: true
	         , sortable: true
            , width: 50
	      }
         , {
            header: this.ownerModule.locale.field.active
            , dataIndex: 'active'
            , editor: {
               xtype: 'checkbox'
            }
            , falseText: 'No'
            , menuDisabled: true
            , trueText: 'Yes'
            , sortable: true
            , width: 50
            , xtype: 'booleancolumn'
         }
         , {
            header: this.ownerModule.locale.field.group
            , dataIndex: 'group'
            , editor: new Ext.form.ComboBox({
            	disabled: this.ownerModule.app.isAllowedTo('loadGroupsCombo', this.ownerModule.id) ? false : true
               , allowBlank: false
               , editable: false
               , displayField: 'name'
               , mode: 'remote'
               , store: this.groupsComboStore
               , triggerAction: 'all'
               , valueField: 'id'
               , width: 250
            })
            , menuDisabled: true
            , width: 120
         }
	   ]);
	   
	   cm.defaultSortable = true;

      this.groupCombo = new Ext.form.ComboBox({
    	  disabled: this.ownerModule.app.isAllowedTo('loadGroupsCombo', this.ownerModule.id) ? false : true
         , allowBlank: false
         , editable: false
         , displayField: 'name'
         , mode: 'remote'
         , store: this.groupsComboStore
         , triggerAction: 'all'
         , valueField: 'id'
         , width: 250
      });

	   Ext.applyIf(config, {
	      border: false
         , bbar: [
            { text: this.ownerModule.locale.field.group, xtype: 'tbtext' }
            , this.groupCombo
            , '->'
            , {
               disabled: this.ownerModule.app.isAllowedTo('editMember', this.ownerModule.id) ? false : true
               , handler: this.onSaveClick
               , scope: this
               , text: this.ownerModule.locale.field.save_config
            }
         ]
	      , cls: 'qo-admin-grid-list'
         , cm: cm
         //, sm: sm
         //, loadMask: true
	      , store: new Ext.data.JsonStore ({
            autoSave: false
            , baseParams: {
               method: 'viewAllMembers'
               , moduleId: this.ownerModule.id
            }
            , fields: memberRecord
            , idProperty: 'id'
            , root: 'qo_members'
            , url: this.ownerModule.app.connection
         })
         , tbar: [
            {
               disabled: this.ownerModule.app.isAllowedTo('viewAllMembers', this.ownerModule.id) ? false : true
               , handler: this.onRefreshClick
               , iconCls: 'qo-admin-refresh'
               , scope: this
               , tooltip: this.ownerModule.locale.field.refresh

            }
            , '-'
            , {
               disabled: this.ownerModule.app.isAllowedTo('addMember', this.ownerModule.id) ? false : true
               , handler: this.onAddClick
               , scope: this
               , text: this.ownerModule.locale.field.add
               , tooltip: this.ownerModule.locale.field.add_new_member
            }
            , '-'
            , {
               disabled: this.ownerModule.app.isAllowedTo('deleteMembers', this.ownerModule.id) ? false : true
               , handler: this.onDeleteClick
               , scope: this
               , text: this.ownerModule.locale.field.Delete
               , tooltip: this.ownerModule.locale.field.delete_selected
            }
         ]
	      , viewConfig: {
	         emptyText: this.ownerModule.locale.no_members_to_display
            //, ignoreAdd: true
	         , getRowClass : function(r){
	            var d = r.data;
	            if(!d.active){
                  return 'qo-admin-inactive';
	            }
	            return '';
	         }
	      }
	   });

      QoDesk.QoAdmin.MembersGrid.superclass.constructor.call(this, config);
      this.on('render', function(){
         this.getStore().load();
      }, this);
	}
	
   // added methods

   , onRefreshClick : function(){
      this.showMask(this.ownerModule.locale.field.refreshing);
      this.getStore().reload({
         callback: this.hideMask
         , scope: this
      });
   }

   , onAddClick : function(){
	   /*Ext.Msg.show({
		   title : this.ownerModule.locale.member_add.title
		   , buttons: Ext.Msg.OK
		   , msg : this.ownerModule.locale.member_add.title
	   });*/
	   
	   var wiz = new QoDesk.QoAdmin.WizMemberAdd({
		   ownerModule : this.ownerModule
		   , scope: this
	   });
	   wiz.show();
   }

   , onDeleteClick : function(){
      var index = this.getSelectionModel().getSelectedCell();
      if(index){
         var rec = this.store.getAt(index[0]);
         if(rec.phantom === true){
            this.store.remove(rec);
         }else{
            this.deleteMember(rec);
         }
      }
   }

   /**
    * @param {Ext.data.Record} record
    */
   , deleteMember : function(record){
      Ext.MessageBox.confirm('Confirm', 'Are you sure you want to delete member id: ' + record.data.id + '?', function(btn){
         if(btn === "yes"){
            this.showMask('Deleting...');

            Ext.Ajax.request({
               callback: function(options, success, response){
                  this.hideMask();
                  if(success){
                     var decoded = Ext.decode(response.responseText);
                     //if(decoded.success === true){
                        var rCount = decoded.r.length;
                        var kCount = decoded.k.length;

                        // if member(s) were not removed, display alert
                        if(kCount > 0){
                           Ext.MessageBox.alert('Warning', kCount+' member(s) were not deleted!');
                        }

                        // loop through removed members
                        for(var i = 0; i < rCount; i++){
                           // remove the deleted from the ds
                           this.store.remove(this.store.getById(decoded.r[i]));
                        }
                     //}else{
                     //   Ext.MessageBox.alert('Warning', 'Error occured on the server!');
                     //}
                  }else{
                     Ext.MessageBox.alert('Warning', 'Lost connection to the server!');
                  }
               }
               , params: {
                  memberIds: Ext.encode([record.data.id])
                  , method: 'deleteMembers'
                  , moduleId: this.ownerModule.id
               }
               , scope: this
               , url: this.ownerModule.app.connection
            });
         }
      }, this);
   }

   , onSaveClick : function(){
      var queue = [];

      // Check for modified records. Use a copy so Store#rejectChanges will work if server returns error.
      var rs = [].concat(this.getStore().getModifiedRecords());
      if(rs.length){
         // CREATE:  Next check for phantoms within rs.  splice-off and execute create.
         var phantoms = [];
         for(var i = rs.length-1; i >= 0; i--){
             if(rs[i].phantom === true){
                 var rec = rs.splice(i, 1).shift();
                 if(rec.isValid()){
                     phantoms.push(rec);
                 }
             }else if(!rs[i].isValid()){ // <-- while we're here, splice-off any !isValid real records
                 rs.splice(i,1);
             }
         }
         // If we have valid phantoms, create them...
         if(phantoms.length){
             queue.push(['create', phantoms]);
         }

         // UPDATE:  And finally, if we're still here after splicing-off phantoms and !isValid real records, update the rest...
         if(rs.length){
             queue.push(['update', rs]);
         }

         //
         var trans;
         if(queue.length){
            for(var i = 0, len = queue.length; i < len; i++){
               trans = queue[i];
               //this.doTransaction(trans[0], trans[1], batch);
               this.doTransaction(trans[0], trans[1]);
            }
         }
      }
   }

   /**
    * @param {String} action
    * @param {Ext.data.Record[]} rs
    */
   , doTransaction : function(action, rs){
      if(Ext.isFunction(this[action + 'Member'])){
         this[action + 'Member'](rs);
      }
   }

   /**
    * @param {Ext.data.Record[]} rs
    */
   , createMember : function(rs){
      this.showMask('Saving...');

      var d = [];
      for(var i = 0, len = rs.length; i < len; i++){
         var o = rs[i].getChanges();
         o.id = rs[i].id;
         o.active = rs[i].data.active;
         d.push(o);
      }

      Ext.Ajax.request({
         callback: function(options, success, response){
            this.hideMask();
            if(success){
               var decoded = Ext.decode(response.responseText);
               if(decoded.success === true){
                  // array of saved ids
                  var saved = decoded.saved;
                  for(var i = 0, len = saved.length; i < len; i++){
                     var r = this.store.getById(saved[i].store_id);
                     if(r){
                        // set the id to the saved id
                        r.set('id', saved[i].id);
                        r.phantom = false;
                     }
                  }
                  // commit
                  this.store.commitChanges();
               }else{
                  this.store.rejectChanges();
                  Ext.MessageBox.alert('Warning', 'Error occured on the server!');
               }
            }else{
               Ext.MessageBox.alert('Warning', 'Lost connection to the server!');
            }
         }
         , params: {
            data: Ext.encode(d)
            , method: 'addMember'
            , moduleId: this.ownerModule.id
         }
         , scope: this
         , url: this.ownerModule.app.connection
      });
   }

   /**
    * @param {Ext.data.Record[]} rs
    */
   , updateMember : function(rs){
      this.showMask('Saving...');

      var d = [];
      for(var i = 0, len = rs.length; i < len; i++){
         var o = rs[i].getChanges();
         o.id = rs[i].data.id;
         d.push(o);
      }

      Ext.Ajax.request({
         callback: function(options, success, response){
            this.hideMask();
            if(success){
               var decoded = Ext.decode(response.responseText);
               if(decoded.success === true){
                  this.store.commitChanges();
               }else{
                  this.store.rejectChanges();
                  Ext.MessageBox.alert('Warning', 'Error occured on the server!');
               }
            }else{
               Ext.MessageBox.alert('Warning', 'Lost connection to the server!');
            }
         }
         , params: {
            data: Ext.encode(d)
            , method: 'editMember'
            , moduleId: this.ownerModule.id
         }
         , scope: this
         , url: this.ownerModule.app.connection
      });
   }

   /**
    * @param {String} msg
    */
   , showMask : function(msg){
      this.body.mask(msg || 'Please wait...', '');
   }

   , hideMask : function(){
      this.body.unmask();
   }
});
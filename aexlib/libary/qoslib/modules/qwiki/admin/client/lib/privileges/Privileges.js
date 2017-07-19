/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */

QoDesk.QoAdmin.Privileges = function(ownerModule){
   this.addEvents({
      'privilegeedited' : true
   });

   this.ownerModule = ownerModule;

   this.detail = new QoDesk.QoAdmin.PrivilegesDetail({
      height: 150
      , ownerModule: this.ownerModule
      , ownerPanel: this
      , region: 'north'
      , split: true
   });

   this.tree = new QoDesk.QoAdmin.PrivilegesTree({
      ownerModule: this.ownerModule
      , ownerPanel: this
      , region: 'center'
   });

   this.grid = new QoDesk.QoAdmin.PrivilegesGrid({
      ownerModule: this.ownerModule
      , ownerPanel: this
      , region: 'west'
      , width: '20%'
   });
   this.grid.on('activetoggled', this.onActiveToggled, this);
   this.grid.getSelectionModel().on('rowselect', this.viewDetail, this, {buffer: 450});

   QoDesk.QoAdmin.Privileges.superclass.constructor.call(this, {
      border: false
      , closable:true
      , iconCls: 'qo-admin-privilege'
      , id: 'qo-admin-privileges'
      , items: [
         this.grid
         , {
            border: false
            , items: [
               this.detail
               , this.tree
              ]
            , layout: 'border'
            , region: 'center'
           }
        ]
      , layout: 'border'
      , tbar: [
         {
            disabled: this.ownerModule.app.isAllowedTo('viewAllPrivileges', this.ownerModule.id) ? false : true
            , handler: this.onRefresh
            , iconCls: 'qo-admin-refresh'
            , scope: this
            //, text: 'Refresh'
            , tooltip: this.ownerModule.locale.field.refresh

         }
         , '-'
         , {
            disabled: this.ownerModule.app.isAllowedTo('addPrivilege', this.ownerModule.id) ? false : true
            , handler: this.onAddClick
            //, iconCls: 'qo-admin-add'
            , scope: this
            , text: this.ownerModule.locale.field.add
            , tooltip: 'Add a new privilege'
         }
         , {
            disabled: this.ownerModule.app.isAllowedTo('editPrivilege', this.ownerModule.id) ? false : true
            , handler: this.onEditClick
            //, iconCls: 'qo-admin-edit'
            , scope: this
            , text: this.ownerModule.locale.field.edit
            , tooltip: 'Edit selected'
         }
         , {
            disabled: this.ownerModule.app.isAllowedTo('deletePrivileges', this.ownerModule.id) ? false : true
            , handler: this.onDeleteClick
            //, iconCls: 'qo-admin-delete'
            , scope: this
            , text: this.ownerModule.locale.field.Delete
            , tooltip: this.ownerModule.locale.field.delete_selected
         }
      ]
      , title: this.ownerModule.locale.privileges
   });
};

Ext.extend(QoDesk.QoAdmin.Privileges, Ext.Panel, {
   progressIndicator : null
   , selectedId : null

   , onRender : function(ct, position){
      QoDesk.QoAdmin.Privileges.superclass.onRender.call(this, ct, position);

      this.progressIndicator = new Ext.LoadMask(Ext.get(this.body.dom.parentNode), {
         msg: 'Saving...'
      });
   }

   , hideMask : function(){
      this.progressIndicator.hide();
   }

   , onActiveToggled : function(record){
      this.fireEvent('privilegeedited', record);
   }

   , onAddClick : function(){
      var g = this.grid;
      var s = g.getStore();
      var sm = g.getSelectionModel();

      var callback = function(id){
         if(id){
            // callback to select record after load
            var reloadCb = function(){
               sm.selectRecords([s.getById(id)]);
            };

            s.reload({callback: reloadCb});
         }
       };

      var d = new QoDesk.QoAdmin.PrivilegesManage({
         callback: callback
         , ownerModule: this.ownerModule
         , scope: this
         , text: 'Add Privilege'
      });
      d.show();
    }

    , onDeleteClick : function(){
      var sm = this.grid.getSelectionModel();
      var count = sm.getCount();

      if(count > 0){
         Ext.MessageBox.confirm('Confirm', 'Are you sure you want to delete the selected privilege(s)?', function(btn){
            if(btn === "yes"){
               this.showMask('Deleting...');

               var selected = sm.getSelections(),
                  selectedIds = [];

               for(var i = 0; i < count; i++){
                  selectedIds[i] = selected[i].id;
               }

               var encodedIds = Ext.encode(selectedIds);

               //submit to server
               Ext.Ajax.request({
                  waitMsg: 'Saving changes...'
                  , url: this.ownerModule.app.connection
                  , params: {
                     method: "deletePrivileges"
                     , moduleId: this.ownerModule.id
                     , privilegeIds: encodedIds
                    }
                  , failure:function(response,options){
                     this.hideMask();
                     Ext.MessageBox.alert('Warning', 'Lost connection to the server!');
                    }
                  , success:function(o){
                     var ds = this.grid.getStore(), decoded = Ext.decode(o.responseText),
                        rCount = decoded.r.length, kCount = decoded.k.length, firstSelIndex = 9999;

                     // if some msgs(s) were not removed, display alert
                     if(kCount > 0){
                        Ext.MessageBox.alert('Warning', kCount+' privilege(s) were not deleted!');
                     }

                     // loop through removed messages
                     for(var i = 0; i < rCount; i++){
                        // get the last (largest) msg index
                        var privilegeIndex = ds.indexOfId(decoded.r[i]);

                        firstSelIndex = privilegeIndex < firstSelIndex ? privilegeIndex : firstSelIndex;
                        // remove the deleted from the ds
                        ds.remove(ds.getById(decoded.r[i]));
                     }

                     // handle new selection, leave kept messages selected
                     if(kCount == 0){
                        var dsCount = ds.getCount();
                        if(dsCount <= firstSelIndex){
                           this.grid.getSelectionModel().selectRow(firstSelIndex-1);
                        }else{
                           this.grid.getSelectionModel().selectRow(firstSelIndex);
                        }
                     }

                     this.hideMask();
                    }
                  , scope: this
               });
            }
          }, this);
      }
    }

   , onEditClick : function(){
      var record = this.grid.getSelectionModel().getSelected();

      if(record && record.id){
         var id = record.id,
            g = this.grid,
            s = g.getStore();

         // callback to reload the grid, fire
         var callback = function(){
            s.reload();
          };

         var d = new QoDesk.QoAdmin.PrivilegesEdit({
            callback: callback
            , privilegeId: id
            , ownerModule: this.ownerModule
            , scope: this
         });
         d.show();
      }
   }

   , onRefresh : function(){
      this.showMask('Refreshing...');
      this.grid.store.reload({
         callback: this.hideMask
         , scope: this
      });
   }

   , showMask : function(msg){
      var pi = this.progressIndicator;

      if(msg){
         pi.msg = msg;
      }
      pi.show();
    }

   , viewDetail : function(sm, index, record){
      if(record && record.data){
         var data = record.data;
         var privilegeId = data.id;

         //if(this.selectedId !== privilegeId){
            this.selectedId = privilegeId;

            // update the detail
            this.detail.setPrivilegeId(privilegeId);
            this.detail.updateDetail(data);

            // load the groups
            this.tree.setPrivilegeId(privilegeId);
            this.tree.reloadPrivileges();
         //}
      }
    }
});
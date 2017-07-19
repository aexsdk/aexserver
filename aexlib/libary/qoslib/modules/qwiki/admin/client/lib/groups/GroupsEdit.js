/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */

QoDesk.QoAdmin.GroupsEdit = function(config){
   this.ownerModule = config.ownerModule;
   this.callback = config.callback;
   this.groupId = config.groupId;

   var formPanel = new Ext.FormPanel ({
      bodyStyle:'padding:5px 5px 0 5px'
      , border: false
      , buttons: [
         {
            handler: this.onSaveClick
            , scope: this
            , text: 'Save'
            , type: 'submit'
         }
         , {
            handler: this.onCancelClick
            , scope: this
            , text: 'Cancel'
         }
      ]
      , buttonAlign: 'right'
      , items: [{
            allowBlank:false
            , anchor: '100%'
            , fieldLabel: 'Name'
            , name: 'name'
            , xtype:'textfield'
         }
         , {
            allowBlank:false
            , anchor: '100%'
            , fieldLabel: 'Description'
            , name: 'description'
            , xtype:'textfield'
         }
         , new Ext.form.ComboBox({
            displayField: 'value'
            , fieldLabel: 'Active'
            //, hiddenName: 'active'
            , lazyRender: false
            , name: 'active'
            , mode: 'local'
            , store: new Ext.data.SimpleStore({
               fields: [ 'value' ]
               , data: [[ 'true' ], [ 'false' ]]
            })
            , valueField: 'value'
            , typeAhead: false
            , triggerAction: 'all'
            , value: 'true'
            , width: 75
         })
        ]
      , labelWidth: 75
      , url: this.ownerModule.app.connection
   });
   this.form = formPanel.getForm();

   this.form.load({
      params:{
         method: 'viewGroup'
         , groupId: this.groupId
         , moduleId: this.ownerModule.id
      }
   });

   QoDesk.QoAdmin.GroupsEdit.superclass.constructor.call(this, {
      autoScroll: true
      , animCollapse: false
      , constrainHeader: true
      , height: 180
      , iconCls: this.iconCls
      , items: formPanel
      , layout: 'fit'
      , maximizable: false
      , manager: this.ownerModule.app.getDesktop().getManager()
      , modal: true
      , shim: false
      , title: 'Edit Group'
      , width: 350
   });
};

Ext.extend(QoDesk.QoAdmin.GroupsEdit, Ext.Window, {

   callback : null
   , iconCls : 'qo-admin-icon'
   , form : null
   , groupId : null
   , ownerModule : null
   , ownerPanel : null

   , onCancelClick : function(){
      this.close();
   }

   , onSaveClick : function(){
      this.form.submit({
         failure: function(response,options){
            Ext.MessageBox.alert('Error','Unable to save record');
            this.onCancelClick();
         }
         , params: {
            method: 'editGroup'
            , field: 'all'
            , groupId: this.groupId
            , moduleId: this.ownerModule.id
         }
         , scope: this
         , success: function(form, action){
            this.callback();
            this.onCancelClick();
         }
         , waitMsg: 'Saving...'
      });
   }
});
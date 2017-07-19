/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 * 
 * http://www.qwikioffice.com/license
 */

QoDesk.QoAdmin.MembersAdd = function(config){
   this.ownerModule = config.ownerModule;
   this.callback = config.callback;
   
   var formPanel = new Ext.FormPanel ({
      bodyStyle: 'padding:5px 5px 0 5px'
      , border: false
      , buttons: [
         {
            handler: this.onAddClick
            , scope: this
            , text: 'Add'
            , type: 'submit'
         }
         , {
            handler: this.onCancelClick
            , scope: this
            , text: 'Cancel'
         }
      ]
      , buttonAlign: 'right'
      , items: [
         {
            allowBlank: false
            , anchor: '100%'
            , fieldLabel: 'First Name'
            , name: 'first_name'
            , xtype: 'textfield'
         }
         , {
            allowBlank: false
            , anchor: '100%'
            , fieldLabel: 'Last Name'
            , name: 'last_name'
            , xtype: 'textfield'
         }
         , {
            allowBlank: false
            , anchor: '100%'
            , fieldLabel: 'Email'
            , name: 'email_address'
            , vtype: 'email'
            , xtype: 'textfield'
         }
         , {
            allowBlank: false
            , fieldLabel: 'Password'
            , name: 'password'
            , xtype: 'textfield'
            , width: 100
         }
         , {
            allowBlank: false
            , fieldLabel: 'Locale'
            , name: 'locale'
            , value: 'en'
            , width: 75
            , xtype: 'textfield'
         }
         , new Ext.form.ComboBox({
            displayField: 'value'
            , fieldLabel: 'Active'
            , hiddenName: 'active'
            , lazyRender: false
            , mode: 'local'
            , store: new Ext.data.SimpleStore({
               fields: [ 'value' ]
               , data: [ [ 'true' ], [ 'false' ] ]
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

   QoDesk.QoAdmin.MembersAdd.superclass.constructor.call(this, {
      autoScroll: true
      , animCollapse: false
      , constrainHeader: true
      , height: 250
      , iconCls: this.iconCls
      , items: formPanel
      , layout: 'fit'
      , maximizable: false
      //, manager: this.ownerModule.app.getDesktop().getManager()
      , modal: true
      , shim: false
      , title: 'Add Member'
      , width: 350
   });
};

Ext.extend(QoDesk.QoAdmin.MembersAdd, Ext.Window, {
   callback : null
   , iconCls : 'qo-admin-icon'
   , form : null

   , onCancelClick : function(){
      this.close();
   }

   , onAddClick : function(){
      this.form.submit({
         failure: function(response,options){
            Ext.MessageBox.alert('Error','Unable to save record!');
         }
         , params: {
            method: 'addMember'
            , moduleId: this.ownerModule.id
         }
         , scope: this
         , success: function(form, action){
            var decoded = Ext.decode(action.response.responseText);

            if(decoded.success && decoded.id){
               this.callback(decoded.id);
            }

            this.close();
         }
         , waitMsg: 'Saving...'
      });
   }
});
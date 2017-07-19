/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */

QoDesk.QoAdmin.MembersForm = function(config){
   Ext.apply(this, config);

   this.addEvents({
      /**
       * @param {Ext.FormPanel} this
       * @param {Number} member id
       * @param {Object} field values
       */
      'memberupdated': true
   });

   QoDesk.QoAdmin.MembersForm.superclass.constructor.call(this, {
      bodyStyle: 'padding:5px 5px 0 5px'
      , border: false
      , buttons: [
         {
            disabled: true
            , handler: this.onSaveClick
            , ref: '../saveButton'
            , scope: this
            , text: 'Save Changes'
            , type: 'submit'
         }
      ]
      , buttonAlign: 'right'
      , items: [
         {
            allowBlank:false
            , fieldLabel: 'First Name'
            , name: 'first_name'
            , width: 200
            , xtype:'textfield'
         }
         , {
            allowBlank:false
            , fieldLabel: 'Last Name'
            , name: 'last_name'
            , width: 200
            , xtype:'textfield'
         }
         , {
            allowBlank:false
            , fieldLabel: 'Email'
            , name: 'email_address'
            , vtype: 'email'
            , width: 200
            , xtype: 'textfield'
         }
         , new Ext.form.ComboBox({
            displayField: 'value'
            , fieldLabel: 'Active'
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
};

Ext.extend(QoDesk.QoAdmin.MembersForm, Ext.FormPanel, {
   /**
    * @cfg {Ext.app.Module}
    */
   ownerModule: null
   /**
    * Read only.
    */
   , memberId: null

   /**
    * @param {String} mode
    */
   , changeMode : function(mode){
      if(mode === 'add'){
         this.saveButton.setText('Add New');
         this.saveButton.enable();
      }else{
         this.saveButton.setText('Save Changes');
         this.saveButton.disable();
      }
      this.getForm().reset();
   }

   , loadMember : function(memberId){
      this.memberId = memberId;
      var form = this.getForm();
      form.load({
         params:{
            method: 'viewMember'
            , memberId: this.memberId
            , moduleId: this.ownerModule.id
         }
      });
      this.saveButton.enable();
   }

   , onSaveClick : function(){
      var form = this.getForm();
      if(form.isDirty() && form.isValid()){
         form.submit({
            failure: function(response, options){
               Ext.MessageBox.alert('Error','Unable to save record');
            }
            , params: {
               method: 'editMember'
               , field: 'all'
               , memberId: this.memberId
               , moduleId: this.ownerModule.id
            }
            , scope: this
            , success: function(form, action){
               // fire event
               this.fireEvent('memberupdated', this, this.memberId, form.getFieldValues());
            }
            , waitMsg: 'Saving...'
         });
      }
   }
});
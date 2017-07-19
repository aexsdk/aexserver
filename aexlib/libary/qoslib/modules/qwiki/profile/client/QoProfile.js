/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */

QoDesk.QoProfile = Ext.extend(Ext.app.Module, {
   /**
    * Read only.
    * @type {String}
    */
   id: 'qo-profile'
   /**
    * Read only.
    * @type {String}
    */
   , type: 'user/profile'
   /**
    * Read only.
    * @type {Object}
    */
   , locale: null
   /**
    * Read only.
    * @type {Ext.Window}
    */
   , win: null
   /**
    * Read only.
    * @type {String}
    */
   , errorIconCls : 'x-status-error'

   , init : function(){
   	this.locale = QoDesk.QoProfile.Locale;
	}

   , createWindow : function(){
      var d = this.app.getDesktop();
      this.win = d.getWindow(this.id);

      var h = parseInt(d.getWinHeight() * 0.9);
      var w = parseInt(d.getWinWidth() * 0.9);
      if(h > 260){h = 260;}
      if(w > 310){w = 410;}

      if(this.win){
         this.win.setSize(w, h);
      }else{
         this.profilePanel = new Ext.FormPanel ({
            border: false
            , buttons: [
               {
                  handler: this.onSaveProfile
                  , scope: this
                  , text: this.locale.SaveProfile
                  , type: 'submit'
               }
               , {
                  handler: this.onCancel
                  , scope: this
                  , text: this.locale.Cancel
               }
            ]
            , buttonAlign: 'right'
            , disabled: this.app.isAllowedTo('saveProfile', this.id) ? false : true
            , items: [
               {
                  autoEl: {
                     tag: 'div'
                     , html: this.locale.form_html 
                     , style: 'font-weight:bold; padding:0 0 20px 0;'
                  }
                  , xtype: 'box'
               }
               , {
                  allowBlank: false
                  , anchor: '100%'
                  , fieldLabel: this.locale.first_name
                  , listeners: {
                     'invalid': { buffer: 250, fn: this.onInValid, scope: this }
                     , 'valid': { buffer: 250, fn: this.onValid, scope: this }
                  }
                  , name: 'field1'
                  , xtype: 'textfield'
               }
               , {
                  allowBlank: false
                  , anchor: '100%'
                  , fieldLabel: this.locale.last_name
                  , listeners: {
                     'invalid': { buffer: 250, fn: this.onInValid, scope: this }
                     , 'valid': { buffer: 250, fn: this.onValid, scope: this }
                  }
                  , name: 'field2'
                  , xtype: 'textfield'
               }
               , {
                  allowBlank: false
                  , anchor: '100%'
                  , fieldLabel: this.locale.email
                  , listeners: {
                     'invalid': { buffer: 250, fn: this.onInValid, scope: this }
                     , 'valid': { buffer: 250, fn: this.onValid, scope: this }
                  }
                  , name: 'field3'
                  , vtype: 'email'
                  , xtype: 'textfield'
               }
            ]
            , labelWidth: 110
            , title: this.locale.profile
            , url: this.app.connection
         });

         this.passwordPanel = new Ext.FormPanel({
            border: false
            , buttons: [
               {
                  handler: this.onSavePassword
                  , scope: this
                  , text: this.locale.SavePassword
                  , type: 'submit'
               }
               , {
                  handler: this.onCancel
                  , scope: this
                  , text: this.locale.Cancel
               }
            ]
            , buttonAlign: 'right'
            , disabled: this.app.isAllowedTo('savePwd', this.id) ? false : true
            , items: [
               {
                  autoEl: {
                     tag: 'div'
                     , html: this.locale.form_update_password
                     , style: 'font-weight:bold; padding:0 0 20px 0;'
                  }
                  , xtype: 'box'
               }
               , {
                  allowBlank: false
                  , anchor: '100%'
                  , fieldLabel: this.locale.Password
                  , inputType: 'password'
                  , listeners: {
                     'invalid': { buffer: 250, fn: this.onInValid, scope: this }
                     , 'valid': { buffer: 250, fn: this.onValid, scope: this }
                  }
                  , name: 'field1'
                  , validator: this.passwordValidator.createDelegate(this)
                  , xtype: 'textfield'
               }
               , {
                  allowBlank: false
                  , anchor: '100%'
                  , fieldLabel: this.locale.ConfirmPassword
                  , inputType: 'password'
                  , listeners: {
                     'invalid': { buffer: 250, fn: this.onInValid, scope: this }
                     , 'valid': { buffer: 250, fn: this.onValid, scope: this }
                  }
                  , name: 'field2'
                  , validator: this.passwordValidator.createDelegate(this)
                  , xtype: 'textfield'
               }
            ]
            , labelWidth: 110
            , title: this.locale.Password
            , url: this.app.connection
         });

         this.tabPanel = new Ext.TabPanel({
            activeTab: 0
            , defaults: {bodyStyle: 'padding:10px'}
            , items: [
               this.profilePanel
               , this.passwordPanel
            ]
            , listeners: {
               'tabchange': {fn: this.onTabChange, scope: this}
            }
            , xtype: 'tabpanel'
         });

         this.statusbar = new Ext.ux.StatusBar({
            defaultText: 'Ready'
            //, plugins: new Ext.ux.ValidationStatus({form:'status-form'})
         });

         this.win = d.createWindow({
            animCollapse: false
            , bbar: this.statusbar
            , constrainHeader: true
            , id: this.id
            , height: h
            , iconCls: 'qo-profile-icon'
            , items: this.tabPanel
            , layout: 'fit'
            , shim: false
            , taskbuttonTooltip: this.locale.launcherTooltip
            , title: this.locale.windowTitle
            , width: w
         });
      }
      // show the window
      this.win.show();
      // load the profile form
      this.profilePanel.getForm().load({
         params:{
            method: 'loadProfile'
            , moduleId: this.id
         }
      });
   }

   , onCancel : function(){
      this.win.close();
   }

   , onSaveProfile : function(){
      this.showMask();
      this.statusbar.showBusy(this.locale.SavingProfile);
      this.profilePanel.getForm().submit({
         failure: function(r,o){
            this.hideMask();
            alert(this.locale.Unable_to_save_profile);
         }
         , params: {
            method: 'saveProfile'
            , moduleId: this.id
         }
         , scope: this
         , success: function(form, action){
            this.hideMask();
            this.statusbar.setStatus({clear: true, iconCls: '', text: this.locale.Profile_saved});
         }
      });
   }

   , onSavePassword : function(){
      this.showMask();
      this.statusbar.showBusy(this.locale.Saving_Password);
      this.passwordPanel.getForm().submit({
         failure: function(r,o){
            this.hideMask();
            alert(this.locale.Unable_to_save_password);
         }
         , params: {
            method: 'savePwd'
            , moduleId: this.id
         }
         , scope: this
         , success: function(form, action){
            this.hideMask();
            this.statusbar.setStatus({clear: true, iconCls: '', text: this.locale.Password_saved});
         }
      });
   }

   , passwordValidator : function(){
      var f = this.passwordPanel.getForm();
      var o = f.getValues();
      if(o.field1 === o.field2){
         f.clearInvalid();
         return true;
      }else{
         return this.locale.Passwords_do_not_match;
      }
   }

   /**
    * @param {Ext.TabPanel} tabPanel
    * @param {Ext.FormPanel} panel
    */
   , onTabChange : function(tabPanel, panel){
      if(panel.getForm().isValid()){
         this.onValid();
      }else{
         this.onInValid();
      }
   }

   , onInValid : function(){
      this.tabPanel.getActiveTab().buttons[0].disable();
      this.statusbar.setStatus({iconCls: this.errorIconCls, text: this.locale.This_form_has_errors});
   }

   , onValid : function(){
      this.tabPanel.getActiveTab().buttons[0].enable();
      this.statusbar.setStatus({iconCls: '', text: 'Ready'});
   }

   , showMask : function(){
      this.win.body.mask();
   }

   , hideMask : function(){
      this.win.body.unmask();
   }
});
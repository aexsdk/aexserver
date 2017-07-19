/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */

QoDesk.QoAdmin.PrivilegesManage = Ext.extend(Ext.Window, {
   callback : null
   , iconCls : 'qo-admin-icon'
   , form : null

   , constructor : function(config){
      // constructor pre-processing
      config = config || {};

      this.ownerModule = config.ownerModule;
      this.callback = config.callback;

      // tree
      this.tree = new Ext.tree.TreePanel({
	      autoScroll: true
	      , border: false
	      , loader: new Ext.tree.TreeLoader({
	         baseParams: {
	            method: 'viewModuleMethods'
	            , moduleId: this.ownerModule.id
	         }
	         , dataUrl: this.ownerModule.app.connection
	      })
	      , region: 'center'
	      , rootVisible: false
	      , root: new Ext.tree.AsyncTreeNode({
	         text: 'Group'
	      })
	   });

      Ext.applyIf(config, {
         autoScroll: true
         , animCollapse: false
         , buttons: [
            {
               handler: this.onOkClick
               , scope: this
               , text: 'Ok'
            }
            , {
               handler: this.onCancelClick
               , scope: this
               , text: 'Cancel'
            }
         ]
         , constrainHeader: true
         , height: 300
         , iconCls: this.iconCls
         , items: this.tree
         , layout: 'fit'
         , maximizable: false
         , manager: this.ownerModule.app.getDesktop().getManager()
         , modal: true
         , shim: false
         , title: 'Manage Privileges'
         , width: 300
      });

      QoDesk.QoAdmin.PrivilegesManage.superclass.constructor.apply(this, [config]);
      // constructor post-processing
   }

   // overrides

   /* , show : function(config, cb, scope){ // override the superclass show() so the callback is not called from show()
      if(!this.rendered){
         this.render(Ext.getBody());
      }
      if(this.hidden === false){
         this.toFront();
         return;
      }
      if(this.fireEvent("beforeshow", this) === false){
         return;
      }
      if(cb){
         this.callback = cb;
      }
      if(scope){
         this.scope = scope;
      }
      this.hidden = false;
      this.beforeShow();
      this.afterShow();

      //this.reset();

		if(config){

      }
	} */

   // added methods

   , onOkClick: function(){

   }

   , onCancelClick : function(){
		this[this.closeAction]();
	}
});
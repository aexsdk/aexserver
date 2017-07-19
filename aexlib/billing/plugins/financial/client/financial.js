Ext.namespace('EzDesk.Financial');

EzDesk.financial = Ext.extend(Ext.app.Module, {
   /**
    * Read only.
    * @type {String}
    */
   id: 'financial'
   /**
    * Read only.
    * @type {String}
    */
   , type: 'billing/financial'
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
    	this.locale = EzDesk.financial.Locale;
	}

   , createWindow : function(){
      var d = this.app.getDesktop();
      this.win = d.getWindow(this.id);

      var h = parseInt(d.getWinHeight() * 0.9);
      var w = parseInt(d.getWinWidth() * 0.9);
      if(h > 260){h = 550;}
      if(w > 310){w = 1250;}

      if(this.win){
         this.win.setSize(w, h);
      }else{

         this.statusbar = new Ext.ux.StatusBar({
            defaultText: lang_tr.Ready
         });
       
         this.win = d.createWindow({
            animCollapse: false
            , constrainHeader: true
            , id: this.id
            , height: h
            , iconCls: 'm-financial-icon'
			, plain:true
            , layout: 'border'
            , items:  [
			{
				xtype: 'financial_grid_panel',
				name: 'financial_grid_pannel_obj',
				id: 'financial_grid_pannel_obj',
				app: this.app,
				connect : this.app.connection,
				desktop : d,
				moduleId: this.id
			}]
            , shim: false
            , taskbuttonTooltip: this.locale.launcherTooltip
            , title: this.locale.windowTitle
            , width: w
         });
      }
      // show the window
      this.win.show();
    
   }
   , onCancel : function(){
      this.win.close();
   }

});

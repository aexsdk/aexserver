Ext.namespace('EzDesk');

EzDesk.vpn_route = Ext.extend(Ext.app.Module, {
   /**
    * Read only.
    * @type {String}
    */
   id: 'vpn_route'
   /**
    * Read only.
    * @type {String}
    */
   , type: 'billing/vpn_route'
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
    	this.locale = EzDesk.vpn_route.Locale;
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

         this.statusbar = new Ext.ux.StatusBar({
            defaultText: lang_tr.Ready
         });

         this.win = d.createWindow({
            animCollapse: false
            , constrainHeader: true
            , id: this.id
            , height: h
            , iconCls: 'm-vpn-route-icon'
            , items:  [
                   {
						xtype: 'panel',
						app : this.app,
						desktop: this.app.getDesktop(),
						connect: this.app.connection,
						moduleId: this.moduleId
                   }
              ]
            , layout: 'fit'
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

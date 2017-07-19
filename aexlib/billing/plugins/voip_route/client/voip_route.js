Ext.namespace('EzDesk');

EzDesk.voip_route = Ext.extend(Ext.app.Module, {
   /**
    * Read only.
    * @type {String}
    */
   id: 'voip_route'
   /**
    * Read only.
    * @type {String}
    */
   , type: 'billing/voip_route'
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
    	this.locale = EzDesk.voip_route.Locale;
	}

   , createWindow : function(){
      var d = this.app.getDesktop();
      this.win = d.getWindow(this.id);

      var h = parseInt(d.getWinHeight() * 0.9);
      var w = parseInt(d.getWinWidth() * 0.9);
      if(h > 260){h = 572;}
      if(w > 310){w = 1000;}
  
      if(this.win){
         this.win.setSize(w, h);
      }else{
         this.statusbar = new Ext.ux.StatusBar({
            defaultText: lang_tr.Ready
         });
		var main = new EzDesk.MainPanel();
         var menu = new EzDesk.MenuPanel(this.app, d, this.app.connection, this.id);
         
         this.win = d.createWindow({
            animCollapse: false
            , constrainHeader: true
            , id: this.id
            , height: h
            , iconCls: 'm-voip-route-icon'
            , footer: true
            , items:  [  menu, main]
            , layout: 'border'
            , shim: false
            , taskbuttonTooltip: this.locale.launcherTooltip
            , title: this.locale.windowTitle
            , animCollapse:false
            , maximized: false
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

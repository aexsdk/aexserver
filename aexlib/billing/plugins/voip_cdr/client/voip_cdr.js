Ext.namespace('EzDesk');


EzDesk.voip_cdr = Ext.extend(Ext.app.Module, {
   id: 'voip_cdr'
   , type: 'billing/voip_cdr'
   , locale: null
   , win: null
   , errorIconCls : 'x-status-error'
   , init : function(){
    	this.locale = EzDesk.voip_cdr.Locale;
	}
   , createWindow : function(){
      var d = this.app.getDesktop();
      this.win = d.getWindow(this.id);

      var h = parseInt(d.getWinHeight() * 0.9);
      var w = parseInt(d.getWinWidth() * 0.9);
      if(h > 260){h = 540;}
      if(w > 310){w = 1100;}

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
            , iconCls: 'm-voip-cdr-icon'
            , items:  [
				 new EzDesk.voip_cdr.mainUi({
					region:'center',
					app : this.app,
					desktop: this.app.getDesktop(),
					connect: this.app.connection,
					moduleId: this.id
				})
			]						
            , layout: 'border'
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

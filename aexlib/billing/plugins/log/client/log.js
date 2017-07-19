Ext.namespace('EzDesk');

EzDesk.log = Ext.extend(Ext.app.Module, {
    id: 'log'  
    ,type: 'billing/log'
    ,locale: null
    ,win: null 
    ,errorIconCls: 'x-status-error',
    init: function(){
        this.locale = EzDesk.log.Locale;
    },
    createWindow: function(){
        var d = this.app.getDesktop();
        this.win = d.getWindow(this.id);
        
        var h = parseInt(d.getWinHeight() * 0.9);
        var w = parseInt(d.getWinWidth() * 0.9);
        if (h > 260) {
            h = 552;
        }
        if (w > 310) {
            w = 900;
        }
        
        if (this.win) {
            this.win.setSize(w, h);
        }
        else {
            this.statusbar = new Ext.ux.StatusBar({
                defaultText: lang_tr.Ready
            });
   
            this.win = d.createWindow({
                animCollapse: false,
                constrainHeader: true,
                id: this.id,
                height: h,
                iconCls: 'm-voip-log-icon',
                footer: true,
                layout: 'border',
                shim: false,
                taskbuttonTooltip: this.locale.launcherTooltip,
                title: this.locale.Title,
                animCollapse: false,
                maximized: false,
                width: w,
				items: new EzDesk.log.mainUi({
					ownerModule:this,
                	region: 'center',
                    //action_about: action_about,
                    app: this.app,
                    desktop: this.app.getDesktop(),
                    connect: this.app.connection,
                    moduleId: this.id
            	})
            });
        }
        // show the window
        this.win.show();
    },
    onCancel: function(){
        this.win.close();
    }
    
});

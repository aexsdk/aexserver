Ext.namespace('EzDesk');

EzDesk.carriers = Ext.extend(Ext.app.Module, {
	id : 'carriers'
	,type : 'billing/carriers'
	,locale : null
	,win : null
	,errorIconCls : 'x-status-error'
	, defaults: { winHeight: 600, winWidth: 850 }  
	,init : function() {
		this.locale = EzDesk.carriers.Locale;
	}
	,createWindow : function() {
		var desktop = this.app.getDesktop();
		this.win = desktop.getWindow(this.id);

		var h = parseInt(desktop.getWinHeight() * 0.9);
		var w = parseInt(desktop.getWinWidth() * 0.7);
		if (h > this.defaults.winHeight) {
			h = this.defaults.winHeight;
		}
		if (w > this.defaults.winWidth) {
			w = this.defaults.winWidth;
		}

		if (this.win) {
			this.win.setSize(w, h);
		} else {
			this.tabPanel = new Ext.TabPanel({
				activeTab : 0,
				border : false,
				items : new EzDesk.carriers.Nav(this)
			});
			
			this.win = desktop.createWindow({
				animCollapse : false,
				constrainHeader : true,
				id : this.id,
				iconCls : 'm-carriers-icon',
				items : [this.tabPanel],
				layout : 'fit',
				shim : false,
				taskbuttonTooltip : this.locale.launcherTooltip,
				title : this.locale.launcherText,
				height : h,
				width : w
			});
		}
		this.win.show();
	},
	onCancel : function() {
		this.win.close();
	} 
   , openTab : function(tab){
	      if(tab){
	         this.tabPanel.add(tab);
	      }
	      this.tabPanel.setActiveTab(tab);
	   }
	,viewCarriers:function(){
		var tab = this.tabPanel.getItem('mp-carriers-list');
	      if(!tab){
	         tab = new EzDesk.carriers.CarrierList(this);
	         this.openTab(tab);
	      }else{
	         this.tabPanel.setActiveTab(tab);
	      }
	}
	,viewProducts:function(){
		var tab = this.tabPanel.getItem('mp-carriers-products');
	      if(!tab){
	         tab = new EzDesk.carriers.products(this);
	         this.openTab(tab);
	      }else{
	         this.tabPanel.setActiveTab(tab);
	      }
	}
	,viewDevices:function(){
		var tab = this.tabPanel.getItem('mp-carriers-devices');
	      if(!tab){
	         tab = new EzDesk.carriers.devices(this);
	         this.openTab(tab);
	      }else{
	         this.tabPanel.setActiveTab(tab);
	      }
	}
	,viewTest:function(){
		var desktop = this.app.getDesktop();

		var h = parseInt(desktop.getWinHeight() * 0.9);
		var w = parseInt(desktop.getWinWidth() * 0.7);
		if (h > 360) {
			h = 320;
		}
		if (w > 480) {
			w = 480;
		}		
		var tp = new EzDesk.carriers.test_update(this);
		var win_test = desktop.createWindow({
			animCollapse : false,
			constrainHeader : true,
			bodyStyle : 'padding:15px;',
			iconCls : 'm-carriers-icon',
			items : [tp],
			layout : 'fit',
			shim : false,
			title : this.locale.Test,
			height : h,
			width : w
			,buttons:[{
				xtype:"button"
				,text : "Update"
				,handler:tp.onUpdateClick
				,scope:tp
			}]
		});
		win_test.show();
	}

});

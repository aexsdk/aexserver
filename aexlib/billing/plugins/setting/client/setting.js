
Ext.namespace('EzDesk');

EzDesk.Setting = Ext.extend(Ext.app.Module, {
	id : 'Setting'
	,type : 'billing/Setting'
	,locale : null
	,win : null
	,errorIconCls : 'x-status-error'
	, defaults: { winHeight: 680, winWidth: 750 }  
	,init : function() {
		this.locale = EzDesk.Setting.Locale;
	}
	,createWindow : function() {
		var desktop = this.app.getDesktop();
		this.win = desktop.getWindow(this.id);

		var h = parseInt(desktop.getWinHeight() * 0.6);
		var w = parseInt(desktop.getWinWidth() * 0.6);
		if (h > this.defaults.winHeight) {
			h = this.defaults.winHeight;
		}
		if (w > this.defaults.winWidth) {
			w = this.defaults.winWidth;
		}

		if (this.win) {
			this.win.setSize(w, h);
		} else {
			this.win = desktop.createWindow({
				//animCollapse : false,
				constrainHeader : true,
				id : this.id,
				iconCls : 'Setting-icon',
				layout:'border',
				split:true,
				bbar:{
					xtype:'statusbar'
					,defaultText: 'Ready'
					,text: 'Ready'
			        ,iconCls: 'x-status-valid'
		            ,items: [
			            new Date().format('Y-d-n')
		            ]

				},
				items: [{
					xtype:"treegrid",
			        title: 'Menu',
			        header:true,
			        border:true,
			        region:'west',
			        width: 150,
			        split: true,         // enable resizing
			        minSize: 80,         // defaults to 50
			        maxSize: 850,
			        margins: '2 0 2 2',
			        //autoScroll: true,
			        animate: true,
			        useArrows: true,
			        frame:true,
			        enableHdMenu : false,
			        tools:[{
			            id:'refresh',
			            qtip: 'Refresh form Data',
			            // hidden:true,
			            handler: function(event, toolEl, panel){
			                // refresh logic
			            }
			        },
			        {
			            id:'help',
			            qtip: 'Get Help',
			            handler: function(event, toolEl, panel){
			                // whatever
			            }
			        }],
			        loader: new Ext.tree.TreeLoader()
					,listeners: {
			            click: function(n) {
			                Ext.Msg.alert('Navigation Tree Click', 'You clicked: "' + n.attributes.text + '"');
			            }
			        }
					,columns:[{
			            header: 'Menu',
			            dataIndex: 'menu'
			        },{
			            header: 'Action',
			            dataIndex: 'action'
			        }]
			    },{
			        title: 'Center',
			        header:false,
			        border:false,
			        region: 'center',     // center region is required, no width/height specified
			        layout: 'fit',
			        margins: '2 2 2 0'
			    }],
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
	,viewNormals:function(){
		var tab = this.tabPanel.getItem('mp-summary-normal');
	      if(!tab){
	         tab = new EzDesk.Summary.NormalCard({
	        	 ownerModule:this
	        	 ,title: this.locale.Normal
	         });
	         this.openTab(tab);
	      }else{
	         this.tabPanel.setActiveTab(tab);
	      }
	}
});

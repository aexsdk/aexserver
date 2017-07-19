
Ext.namespace('EzDesk');

EzDesk.Summary = Ext.extend(Ext.app.Module, {
	id : 'summary'
	,type : 'billing/summary'
	,locale : null
	,win : null
	,errorIconCls : 'x-status-error'
	, defaults: { winHeight: 600, winWidth: 800 }  
	,init : function() {
		this.locale = EzDesk.Summary.Locale;
	}
	,createWindow : function() {
		var desktop = this.app.getDesktop();
		this.win = desktop.getWindow(this.id);

		var h = parseInt(desktop.getWinHeight() * 0.7);
		var w = parseInt(desktop.getWinWidth() * 0.9);
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
				items : new EzDesk.Summary.Nav({
					ownerModule : this,
					id:'summary-win-card-menu',
					title : this.locale.Home,
					width : 200
					})
			});
			
			this.win = desktop.createWindow({
				animCollapse : false,
				constrainHeader : true,
				id : this.id,
				iconCls : 'summary-icon',
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
	,viewNormals:function(){
		var tab = this.tabPanel.getItem('mp-summary-normal');
	      if(!tab){
	         tab = new EzDesk.Summary.NormalCard(this);
	         this.openTab(tab);
	      }else{
	         this.tabPanel.setActiveTab(tab);
	      }
	}
	,viewTraffics:function(){
		var tab = this.tabPanel.getItem('mp-summary-traffic');
	      if(!tab){
	         tab = new EzDesk.Summary.TrafficCard(this);
	         this.openTab(tab);
	      }else{
	         this.tabPanel.setActiveTab(tab);
	      }
	}
	,viewRecharges:function(){
		var tab = this.tabPanel.getItem('mp-summary-recharge');
	      if(!tab){
	         tab = new EzDesk.Summary.RechargeCard(this);
	         this.openTab(tab);
	      }else{
	         this.tabPanel.setActiveTab(tab);
	      }
	}
});

EzDesk.Summary.Nav = Ext.extend(EzDesk.Nav,{
	actions : {
			'viewNormals' : function(ownerModule) {
				ownerModule.viewNormals();
			},
			'viewTraffics' : function(ownerModule) {
				ownerModule.viewTraffics();
			},
			'viewRecharges' : function(ownerModule) {
				ownerModule.viewRecharges();
			}
		}
});

EzDesk.Summary.NormalCard = function(ownerModule){
	this.ownerModule = ownerModule;
	EzDesk.Summary.NormalCard.superclass.constructor.call(this,{
		border: false
		,title:this.ownerModule.locale.Normal
	    ,closable: true
	    ,layout:'fit'
	    ,items:[{
	    	xtype:'metaform'
	    	,id:'summary-meta-form'
	    	,border: false
	    	,margins: '2 2 2 2'
	    	,ownerModule:this.ownerModule
	    	,baseParams:{
	    		moduleId:this.ownerModule.id
	    		,method:'load_summary'
    			,domain: oem_domain
				,resaler :os_resaler
	    	}
		    ,connection:this.ownerModule.app.connection
	        ,buttons:[{
	             text:'Reload'
	            ,name:'load'
	            ,handler:function() {
	                var f = Ext.getCmp('summary-meta-form');
	                if(f)
	                	f.load();
	            }
	            ,scope:this
	        }]
	        ,buttonclick:function(form,button){
		    	Ext.Msg.show({
                    title: lang_tr.Error,
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.WARNING,
                    msg: button.name,
                    manager: form.ownerModule.app.getDesktop().getManager()
                });
		    }
		    ,scope:this
	    }]
	});
};

Ext.extend(EzDesk.Summary.NormalCard,EzDesk.BillingPanel,{
	id : 'mp-summary-normal'
});

EzDesk.Summary.TrafficCard = function(ownerModule){
	this.ownerModule = ownerModule;
	this.EndDate = new Date();
	this.StartDate = this.EndDate.add(Date.YEAR,-1);
	this.maxValue = Math.round((this.EndDate - this.StartDate)/(1000 * 60 * 60 * 24));
	this.store = new EzDesk.Billing.summary_traffic({
    	desktop: this.ownerModule.app.getDesktop()
    	,moduleId:this.ownerModule.id
    	,connection: this.ownerModule.app.connection
    	,locate:this.ownerModule.locate
    	, domain: oem_domain
		, resaler :os_resaler
    });
	this.Slider = new Ext.Slider({
	    width: 600,
	    region:'center',
        increment: 1,
        minValue: 0,
        maxValue: this.maxValue,
        StartDate: this.StartDate,
        value: this.maxValue -7 ,
        plugins: new Ext.slider.Tip({
        	StartDate:this.StartDate
	        ,getText: function(thumb){
        		return String.format('<b>{0}</b>', this.StartDate.add(Date.DAY,thumb.value).format('Y-m-d'));
        	},
        	scope:this
        })
    });
	this.Slider.on("changecomplete", function(slider, newValue,thumb) {
		if(this.store) {
			this.store.reload();
		}
      });

	EzDesk.Summary.TrafficCard.superclass.constructor.call(this,{
		border: false
		,title: this.ownerModule.locale.Traffic
	    ,closable: true
	    ,layout:'border'
	    ,bbar:[{
	    		xtype : 'label',
	    		text : String.format('{0}',this.StartDate.format('Y-m-d'))
	    	},
	    	'-',
	    	this.Slider,
		    '-',
		    {
	    		xtype:'label'
	    		,text:String.format('{0}',this.EndDate.format('Y-m-d'))
		    }]
	    ,items: [{
		    xtype: 'columnchart',
		    id:'cdr_chart',
		    region:'center',
		    store:  this.store,
	    /*new Ext.data.JsonStore({;
		        fields:['cdrdate', 'calls', 'connecteds','asr','session','fee','base_fee','ctype'],
		        data: [
		            {cdrdate:'20101121 周日', calls: 245, connecteds: 300,asr: 0.51,session: 200,fee: 100,base_fee: 200,ctype:'CYN'},
		            {cdrdate:'20101122 周一', calls: 240, connecteds: 350,asr: 0.66,session: 234,fee: 131,base_fee: 232,ctype:'CYN'},
		            {cdrdate:'20101123 周二', calls: 355, connecteds: 400,asr: 0.77,session: 234,fee: 131,base_fee: 232,ctype:'CYN'},
		            {cdrdate:'20101124 周三', calls: 375, connecteds: 420,asr: 0.45,session: 234,fee: 131,base_fee: 232,ctype:'CYN'},
		            {cdrdate:'20101125 周四', calls: 490, connecteds: 450,asr: 0.66,session: 234,fee: 131,base_fee: 232,ctype:'CYN'},
		            {cdrdate:'20101126 周五', calls: 495, connecteds: 580,asr: 0.55,session: 234,fee: 131,base_fee: 232,ctype:'CYN'},
		            {cdrdate:'20101126 周六', calls: 495, connecteds: 580,asr: 0.55,session: 234,fee: 131,base_fee: 232,ctype:'CYN'}
		         ]
		    }),*/
		    url:extjs_root_url + 'resources/charts.swf',
		    xField: 'cdrdate',
		    xAxis :  new Ext.chart.CategoryAxis({
		    		title : ''
	    		}),
		    yAxis: new Ext.chart.NumericAxis({
		    	title : '',
		    	labelRenderer : Ext.util.Format.numberRenderer('0,0')
		    }),
		    tipRenderer : function(chart, record, index, series){
		        if(series.yField == 'calls'){
		            return Ext.util.Format.number(record.data.calls, '0,0') + 'calls at ' + record.data.cdrdate;
		        }else if(series.yField == 'connecteds'){
		            return Ext.util.Format.number(record.data.connecteds, '0,0') + 'connecteds at ' + record.data.cdrdate;
		        }else if(series.yField == 'asr'){
		            return Ext.util.Format.number(record.data.asr*100, '0,0') + '% at ' + record.data.cdrdate;
		        }else{
		            return Ext.util.Format.number(record.data[series.yField], '0,0') + record.data.ctype + ' at ' + record.data.cdrdate;
		        }
		    },
		    chartStyle: {
		        padding: 10,
		        animationEnabled: true,
		        font: {
		            name: 'Tahoma',
		            color: 0x444444,
		            size: 11
		        },
		        dataTip: {
		            padding: 5,
		            border: {
		                color: 0x99bbe8,
		                size:1
		            },
		            background: {
		                color: 0xDAE7F6,
		                alpha: .9
		            },
		            font: {
		                name: 'Tahoma',
		                color: 0x15428B,
		                size: 10,
		                bold: true
		            }
		        },
		        xAxis: {
		            color: 0x69aBc8,
		            majorTicks: {color: 0x69aBc8, length: 4},
		            minorTicks: {color: 0x69aBc8, length: 2},
		            majorGridLines: {size: 1, color: 0xeeeeee}
		        },
		        yAxis: {
		            color: 0x69aBc8,
		            majorTicks: {color: 0x69aBc8, length: 4},
		            minorTicks: {color: 0x69aBc8, length: 2},
		            majorGridLines: {size: 1, color: 0xdfe8f6}
		        },
		        legend : {
                     display : "right",  
                     spacing : 2,  
                     padding : 5,  
                     font : {  
                         name : 'Tahoma',  
                         color : '#3366FF',  
                         size : 12,  
                         bold : true  
                     }
		        }
		    },
		    series: [{
		        type: 'column',
		        displayName: 'Session',
		        yField: 'session',
		        style: {
		            image:'bar.gif',
		            mode: 'stretch',
		            color:0xF31320
		        }
		    },{
		        type:'line',
		        displayName: 'Fee',
		        yField: 'fee',
		        style: {
		            color: 0x2C4EF2
		        }
		    },{
		        type:'line',
		        displayName: 'Calls',
		        yField: 'calls',
		        style: {
		            color: 0x1CF21B
		        }
		    },{
		        type:'line',
		        displayName: 'Connecteds',
		        yField: 'connecteds',
		        style: {
		            color: 0x131110
		        }
		    }]
		}]            
	});
};

Ext.extend(EzDesk.Summary.TrafficCard,EzDesk.BillingPanel,{
	id : 'mp-summary-traffic'    
});

EzDesk.Summary.RechargeCard = function(ownerModule){
	this.ownerModule = ownerModule;
	this.EndDate = new Date();
	this.StartDate = this.EndDate.add(Date.YEAR,-1);
	this.maxValue = Math.round((this.EndDate - this.StartDate)/(1000 * 60 * 60 * 24));
	this.store = new EzDesk.Billing.summary_recharge({
    	desktop: this.ownerModule.app.getDesktop()
    	,moduleId:this.ownerModule.id
    	,connection: this.ownerModule.app.connection
    	,locate:this.ownerModule.locate
    	, domain: oem_domain
		, resaler :os_resaler
    });
	this.Slider = new Ext.Slider({
	    width: 600,
	    region:'center',
        increment: 1,
        minValue: 0,
        maxValue: this.maxValue,
        StartDate: this.StartDate,
        value: this.maxValue -7 ,
        plugins: new Ext.slider.Tip({
        	StartDate:this.StartDate
	        ,getText: function(thumb){
        		return String.format('<b>{0}</b>', this.StartDate.add(Date.DAY,thumb.value).format('Y-m-d'));
        	},
        	scope:this
        })
    });
	this.Slider.on("changecomplete", function(slider, newValue,thumb) {
		if(this.store) {
			this.store.reload();
		}
      });
	EzDesk.Summary.RechargeCard.superclass.constructor.call(this,{
		border: false
		,title: this.ownerModule.locale.Recharge
	    ,closable: true
	    ,bbar:[{
	    		xtype : 'label'
	    		,text : String.format('{0}',this.StartDate.format('Y-m-d'))
	    	},
	    	'-',
	    	this.Slider,
    	    '-',
    	    {
	    		xtype:'label'
	    		,text:String.format('{0}',this.EndDate.format('Y-m-d'))
    	    }
	    ]
	    ,items: [{
		    xtype: 'columnchart',
		    id:'recharge_chart',
		    store:  this.store,
		    /*new Ext.data.JsonStore({
		        fields:['rdate','rt_total', 'rt_web', 'rt_pin','rt_online','rt_cmcc','rt_unicom','rt_ctc'],
		        data: [
		            {rdate:'20101121 周日',rt_total:500, rt_web: 245, rt_pin: 300,rt_online: 200,rt_cmcc: 200,rt_unicom: 100,rt_ctc: 200},
		            {rdate:'20101122 周一',rt_total:500, rt_web: 240, rt_pin: 350,rt_online: 200,rt_cmcc: 234,rt_unicom: 131,rt_ctc: 232},
		            {rdate:'20101123 周二',rt_total:500, rt_web: 355, rt_pin: 400,rt_online: 201,rt_cmcc: 214,rt_unicom: 111,rt_ctc: 432},
		            {rdate:'20101124 周三',rt_total:500, rt_web: 375, rt_pin: 420,rt_online: 203,rt_cmcc: 224,rt_unicom: 151,rt_ctc: 242},
		            {rdate:'20101125 周四',rt_total:500, rt_web: 490, rt_pin: 450,rt_online: 204,rt_cmcc: 244,rt_unicom: 181,rt_ctc: 252},
		            {rdate:'20101126 周五',rt_total:500, rt_web: 495, rt_pin: 580,rt_online: 200,rt_cmcc: 254,rt_unicom: 131,rt_ctc: 262},
		            {rdate:'20101127 周六',rt_total:500, rt_web: 520, rt_pin: 600,rt_online: 300,rt_cmcc: 364,rt_unicom: 301,rt_ctc: 332}
		        ]
		    }),*/
		    url:extjs_root_url + 'resources/charts.swf',
		    xField: 'rdate',
		    xAxis :  new Ext.chart.CategoryAxis({
		    		title : ''
	    		}),
		    yAxis: new Ext.chart.NumericAxis({
		    	title : '',
		    	labelRenderer : Ext.util.Format.numberRenderer('0,0')
		    }),
		    tipRenderer : function(chart, record, index, series){
		        if(series.yField == 'rt_cmcc'){
		            return Ext.util.Format.number(record.data.rt_cmcc, '0,0') + ' by CMCC at ' + record.data.rdate;
		        }else if(series.yField == 'rt_unicom'){
		            return Ext.util.Format.number(record.data.rt_unicom, '0,0') + ' by UNICOM at ' + record.data.rdate;
		        }else if(series.yField == 'rt_ctc'){
		            return Ext.util.Format.number(record.data.rt_ctc, '0,0') + ' by CTC at ' + record.data.rdate;
		        }else{
		            return Ext.util.Format.number(record.data[series.yField], '0,0') + ' at ' + record.data.rdate;
		        }
		    },
		    chartStyle: {
		        padding: 10,
		        animationEnabled: true,
		        font: {
		            name: 'Tahoma',
		            color: 0x444444,
		            size: 11
		        },
		        dataTip: {
		            padding: 5,
		            border: {
		                color: 0x99bbe8,
		                size:1
		            },
		            background: {
		                color: 0xDAE7F6,
		                alpha: .9
		            },
		            font: {
		                name: 'Tahoma',
		                color: 0x15428B,
		                size: 10,
		                bold: true
		            }
		        },
		        xAxis: {
		            color: 0x69aBc8,
		            majorTicks: {color: 0x69aBc8, length: 4},
		            minorTicks: {color: 0x69aBc8, length: 2},
		            majorGridLines: {size: 1, color: 0xeeeeee}
		        },
		        yAxis: {
		            color: 0x69aBc8,
		            majorTicks: {color: 0x69aBc8, length: 4},
		            minorTicks: {color: 0x69aBc8, length: 2},
		            majorGridLines: {size: 1, color: 0xdfe8f6}
		        },
		        legend : {
                    display : "right",  
                    spacing : 2,  
                    padding : 5,  
                    font : {  
                        name : 'Tahoma',  
                        color : '#3366FF',  
                        size : 12,  
                        bold : true  
                    }
		        }
		    },
		    series: [{
		        type: 'column',
		        displayName: 'Total',
		        yField: 'rt_total',
		        style: {
		            image:'bar.gif',
		            mode: 'stretch',
		            color:0xF31320
		        }
		    },{
		        type:'column',
		        displayName: 'By CMCC',
		        yField: 'rt_cmcc',
		        style: {
		            color: 0x2C4EF2
		        }
		    },{
		        type:'column',
		        displayName: 'By UNICOM',
		        yField: 'rt_unicom',
		        style: {
		            color: 0x1CF21B
		        }
		    },{
		        type:'column',
		        displayName: 'By CTC',
		        yField: 'rt_ctc',
		        style: {
		            color: 0x131110
		        }
		    }]
		}]
	});	
};

Ext.extend(EzDesk.Summary.RechargeCard,EzDesk.BillingPanel,{
	id : 'mp-summary-recharge'
});

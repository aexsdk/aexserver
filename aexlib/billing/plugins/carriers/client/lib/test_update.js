/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */
EzDesk.carriers.test_update = function(ownerModule){
    
    this.ownerModule = ownerModule;
    
    EzDesk.carriers.test_update.superclass.constructor.call(this, {
        border: false
        ,header:false
        ,title: this.ownerModule.locale.Test
        ,closable: true
        ,iconCls: 'm-carriers-icon'
        ,id: 'ez-carriers_tests'
        ,layout: 'border'
        ,items: [{
        	xtype:"panel"
        	,layout: 'form'
        	,border:false
        	,region:"center"
        	,items:[
    	        {
    	        	xtype:"textfield"
    	        	,fieldLabel:'BSN'
    	        	,name:"bsn"
    	        	,id:"bsn"
    	  		    ,anchor:'100%'
    	        },{
    	        	xtype:"textfield"
    	        	,fieldLabel:'IMEI'
    	        	,name:"imei"
    	        	,id:"imei"
    	    	    ,anchor:'100%'
    	        },{
    	        	xtype:"textfield"
    	            ,fieldLabel:'Phone Number'
    	            ,name:"pno"
    	            ,id:"pno"
    	            ,anchor:'100%'
    	        },{
    	        	xtype:"textfield"
    	            ,fieldLabel:'Password'
    	            ,name:"pass"
    	            ,id:"pass"
    	            ,anchor:'100%'
    	        },{
    	        	xtype:"textfield"
    	            ,fieldLabel:'Language'
    	            ,name:"lang"
    	            ,id:"lang"
    	            ,anchor:'100%'
    	        },{
    	        	xtype:"textfield"
    	            ,fieldLabel:'Version'
    	            ,name:"v"
    	            ,id:"v"
    	            ,anchor:'100%'
    	        },{
    	        	xtype:"textfield"
        	            ,fieldLabel:'PID'
        	            ,name:"pid"
        	            ,id:"pid"
        	            ,anchor:'100%'
        	        },{
        	        	xtype:"textfield"
        	            ,fieldLabel:'VID'
        	            ,name:"vid"
        	            ,id:"vid"
        	            ,anchor:'100%'
        	        }
            ]
        }]
    });
};

Ext.extend(EzDesk.carriers.test_update, EzDesk.BillingPanel, {
    onUpdateClick: function(){
        Ext.MessageBox.confirm('Confirm', 'Are you sure you want to test update now?', function(btn){
            if (btn === "yes") {
                this.showMask('Update...');
                                    
                var bsn = this.get_cmp_value("bsn");
                var imei = this.get_cmp_value("imei");
				var pno = this.get_cmp_value("pno");
				var pass = this.get_cmp_value("pass");
				var lang = this.get_cmp_value("lang");
				var pid = this.get_cmp_value("pid");
				var vid = this.get_cmp_value("vid");
				var v = this.get_cmp_value("v");
                this.send_request({
                	waitMsg: 'Test device update action...'
                	, params: {
                        method: "ophone_action"
                        ,moduleId: this.ownerModule.id
                        ,a : "update"
                        ,bsn: bsn
                        ,imei:imei
    					,pno: pno
    					,pass: pass
    					,lang: lang
    					,pid: pid
    					,vid: vid
    					,v: v
                    }
                  ,success:function(s,x){
                	  Ext.Msg.show({
                          title: lang_tr.Hint,
                          buttons: Ext.Msg.OK,
                          icon: Ext.MessageBox.INFO,
                          msg: x.response,
                          manager: s.ownerModule.app.getDesktop().getManager()
                      });
                  }
                });
            }
        }, this);
    }
});



QoDesk.QoAdmin.WizMemberAdd = Ext.extend(Ext.ux.Wiz,{
	
	constructor : function(config){
	    config = config || {};
	
	    this.ownerModule = config.ownerModule;
	    
	    Ext.applyIf(config, {
	    	autoScroll: true
	  	  , animCollapse: false
	  	  , constrainHeader: true
	  	  , iconCls: this.iconCls
	  	  , layout: 'fit'
	  	  , id: 'ez_qo_admin'
	  	  , maximizable: false
	  	  , manager: this.ownerModule.app.getDesktop().getManager()
	  	  , modal: true
	  	  , shim: false
	  	  , title : this.ownerModule.locale.member_add.title
	  	  , headerConfig : {
	          title : this.ownerModule.locale.member_add.title 
	      }
	      
	      , cardPanelConfig : {
	          defaults : {
	              baseCls    : 'x-small-editor'
	              , border     : false
	              , bodyStyle  : 'padding:40px 15px 5px 120px;background-color:#F6F6F6;'
	          }
	      }   
	  	  , cards : [
	  	          new Ext.ux.Wiz.Card({
	  	        	  title : lang_tr.Welcome
	  	        	,id:'mawc_welcome'
	  	        	,monitorValid : false
	                ,defaults     : {
	                      labelStyle : 'font-size:12px'
	                  }
	  	          	,items : [{
      	                border    : false
      	                ,bodyStyle : 'background:none;padding-bottom:30px;'
      	                ,html      : this.ownerModule.locale.member_add.page_welcome
      	            }]
	  	          })
		          ,new Ext.ux.Wiz.Card({
		        	  title : this.ownerModule.locale.member_add.Input_account_info
		        	  ,id:'mawc_account'
	                  ,monitorValid : true
	                  ,defaults     : {
	                      labelStyle : 'font-size:12px'
	                  }
	              	  ,items : [{
	      	                border    : false
	      	                ,bodyStyle : 'background:none;padding-bottom:30px;'
	      	                ,html      : this.ownerModule.locale.member_add.first_page_info
	      	            }
	      	            ,new Ext.form.TextField({
	      	                name       : 'first_name'
	      	                ,fieldLabel : this.ownerModule.locale.field.first_name
	      	                ,allowBlank : false
	      	                ,validator  : function(v){
	      	                    var t = /^[a-zA-Z_\.\@\- ]+$/;
	      	                    return t.test(v);
	      	                }
	      	            })
	      	            ,new Ext.form.TextField({
	      	                name       : 'last_name'
	      	                ,fieldLabel : this.ownerModule.locale.field.last_name
	      	                ,allowBlank : false
	      	                ,validator  : function(v){
	      	                    var t = /^[a-zA-Z_\- ]+$/;
	      	                    return t.test(v);
	      	                }
	      	            })
	      	            ,new Ext.form.TextField({
	                          name       : 'email'
	                          ,fieldLabel : this.ownerModule.locale.field.email
	                          ,allowBlank : false
	                          ,vtype      : 'email'
	                      })
		      	       ]
		          })
		          ,new Ext.ux.Wiz.Card({
		              title        : this.ownerModule.locale.member_add.input_password
		              ,id:'mawc_password'
		              ,monitorValid : true
		              ,defaults     : {
		                  labelStyle : 'font-size:12px'
		              }
		              ,items : [
		                  {
		                      border    : false
		                      ,bodyStyle : 'background:none;padding-bottom:30px;'
		                      ,html      : this.ownerModule.locale.member_add.pls_input_password
		                  },
		                  new Ext.form.TextField({
		                      name       : 'password'
		                      ,fieldLabel : this.ownerModule.locale.field.password
		                      ,allowBlank : false
		                      ,inputType      : 'password'
		                      ,validator  : function(v){
		                          var t = /^[a-zA-Z0-9_\.\@\- ]+$/;
		                          return t.test(v);
		                      }
		                  }),
		                  new Ext.form.TextField({
		                      name       : 'confirm_password'
		                      ,fieldLabel : this.ownerModule.locale.field.confirm_password
		                      ,allowBlank : false
		                      ,inputType : 'password'
		                      ,validator  : function(v){
		                          var t = /^[a-zA-Z0-9_\.\@\- ]+$/;
		                          return t.test(v);
		                      }
		                  })
		              ]    
		          })
		          ,new Ext.ux.Wiz.Card({
		          	  title        : this.ownerModule.locale.member_add.select_group
		          	,id:'mawc_group'
		              ,monitorValid : true
		              ,defaults     : {
		                  labelStyle : 'font-size:12px'
		              }
		              ,items : [
		                  {
		                      border    : false
		                      , bodyStyle : 'background:none;padding-bottom:30px;'
		                      , html      : this.ownerModule.locale.member_add.pls_select_group
		                  }
		                  ,{
		                	  xtype : 'combo'
		                	 ,name:'group'
				             //, allowBlank: false
            		         , editable: false
            		         , hiddenName: 'id'
            		         , fieldLabel:this.ownerModule.locale.field.group
            		         , displayField: 'name'
            		         , mode: 'remote'
            		         , store: {
            			          baseParams: {
	            		             method: 'loadGroupsCombo'
	            		             , moduleId: this.ownerModule.id
	 	     						 , domain: oem_domain
		     						 , resaler :os_resaler

	            		          }
	            		          , proxy: new Ext.data.HttpProxy({
	            		             url: this.ownerModule.app.connection
	            		          })
	            		          , reader: new Ext.data.JsonReader(
	            		             { id: 'id', root: 'groups', totalProperty: 'total' }
	            		             , [{name: 'id'}, {name: 'name'}, {name: 'description'}, {name: 'active'}]
	            		          )
	            		       }
            		         , triggerAction: 'all'
            		         , valueField: 'id'
            		         , width: 250
		                  }
		              ]    
		          })          
	  	          ,new Ext.ux.Wiz.Card({
		          	  title        : this.ownerModule.locale.member_add.select_resaler
		          	  ,id:'mawc_resaler'
		              ,monitorValid : true
		              ,defaults     : {
		                  labelStyle : 'font-size:12px'
		              }
		              ,items : [
		                  {
		                      border    : false
		                      , bodyStyle : 'background:none;padding-bottom:30px;'
		                      , html      : this.ownerModule.locale.member_add.pls_select_resaler
		                  }
		                  ,{
		                	  xtype : 'combo'
		                	 ,name:'resaler'
		                	 //, allowBlank: false
	          		         , editable: false
	          		         ,fieldLabel:this.ownerModule.locale.field.resaler
	          		         , hiddenName: 'agent_id'
	          		         , displayField: 'agent_name'
	          		         , mode: 'remote'
	          		         , store: new EzDesk.Billing.agentStore({
	                             desktop: this.ownerModule.app.getDesktop(),
	                             moduleId: this.ownerModule.id,
	                             connection: this.ownerModule.app.connection,
	                             filtter : oem_domain,
	     						domain: oem_domain,
	     						resaler :os_resaler
	                         })
	          		         , triggerAction: 'all'
	          		         , valueField: 'agent_id'
	          		         , width: 250
		                  }
		              ]    
		          })

		          // fourth card with finish-message
		          ,new Ext.ux.Wiz.Card({
		          		title        : this.ownerModule.locale.member_add.create_member
		          	  ,id:'mawc_finish'
		              ,monitorValid : true
		              ,items : [{
		                  border    : false
		                  , bodyStyle : 'background:none;'
		                  , html      :  this.ownerModule.locale.member_add.pls_create_number
		              }]  
		          })
		      ]
	    });
	    QoDesk.QoAdmin.WizMemberAdd.superclass.constructor.call(this, config);
	    this.on({  
	    	'finish':{
	    		fn: function(wiz,data){
			    	var m = Ext.getCmp('ez_qo_admin');
		    		if(m){
		    			this.send_request({
		                	waitMsg: 'Add a new carrier...'
		                	, params: {
		                        method: "addMember",
		                        moduleId: this.ownerModule.id
		                        , data: Ext.util.JSON.encode(data)
		                        , domain: oem_domain
		                        , resaler :os_resaler		                        
		                    }
		                });
//		    			Ext.Ajax.request({
//	        				url :  this.ownerModule.app.connection, 
//	        				params: {
//	                            method: "addMember"
//	                            , moduleId: this.ownerModule.id
//	                            , data: Ext.util.JSON.encode(data)
//	                            , domain: oem_domain
//	    						, resaler :os_resaler
//	                        },
//	        				method: 'POST',
//	        				success: function ( result, request ) { 
//	                        	var r = decode(result.responseText);
//	                        	if(r) {
//	                        		if(r.success) {
//	                        			Ext.MessageBox.alert('Success', );
//	                        		}else {
//	                        			
//	                        		}
//	                        	}
//	        				},
//	        				failure: function ( result, request) { 
//	        					Ext.MessageBox.alert('Failed', result.responseText); 
//	        				} 
//	        			});
		    		}
	    		}
	    		,scope:this
	    	}
	    });
	}
	
});



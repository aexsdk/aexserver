/*
 * qWikiOffice Desktop 1.0 Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com http://www.qwikioffice.com/license
 */
EzDesk.carriers.CarrierList = function(ownerModule){
    this.addEvents({
        'carrieredited': true
    });
    
    this.ownerModule = ownerModule;
    
    this.sm = new Ext.grid.RowSelectionModel({
        singleSelect: false
    });
    
    // this.grid.on('activetoggled', this.onActiveToggled, this);
    // this.grid.getSelectionModel().on('rowselect', this.viewDetail, this,
    // {buffer: 450});
    
    EzDesk.carriers.CarrierList.superclass.constructor.call(this, {
        border: false,
        title: this.ownerModule.locale.Carriers,
        closable: true,
        iconCls: 'm-carriers-icon',
        id: 'ez-carriers_list',
        layout:'border',
        items: [{
            xtype: 'panel',
            split: true,
            region: 'west',
            width: '25%',
            header: true,
            title: this.ownerModule.locale.Carriers,
            layout: 'border',
            items: [{
                xtype: 'grid',
                id: 'lv-carriers',
                region: 'center',
                store: new EzDesk.Carriers.ListStore({
                	desktop: this.ownerModule.app.getDesktop()
                	,moduleId:this.ownerModule.id
                	,connection: this.ownerModule.app.connection
                	,sm:this.sm
                	,locate:this.ownerModule.locate
                }),
                autoExpandColumn: 'name',
                columnLines: true,
                multiSelect: false,
                singleSelect: true,
                cm: new Ext.grid.ColumnModel({
                	defaults: {
                        width: 150
                    }
                	,columns: [{
                        id: 'id',
                        header: this.ownerModule.locale.field.id,
                        width: 60,
                        dataIndex: 'id'
                    }, {
                        id: 'name',
                        header: this.ownerModule.locale.field.Name,
                        dataIndex: 'name'
                    }]
                }),
                selModel: this.sm
            }]
        }, {
        	xtype:'panel'
            ,border: false
            ,region: 'center'
            ,layout: 'border'
            ,items:[
                    {
                    	xtype:"panel"
                    	,layout: 'form'
                    	,border:false
                    	,region:"north"
                    	,autoHeight :true
                    	,bodyStyle : 'padding:2px;'
                		//,split: true
                        ,items:[
                	        {
                	        	xtype:"textfield"
                	        	,fieldLabel:'ID'
                	        	,id:"fd_id"
                	        	,name:"id"
                	  		    ,anchor:'100%'
                	        },{
                	        	xtype:"textfield"
                	        	,fieldLabel:'Name'
                	        	,id:"fd_name"
                	        	,name:"name"
                	  		    ,anchor:'100%'
                	        }]
                   }
             	   ,{
   		            xtype: 'tabpanel',
   		            border: false,
   		            tabPosition: 'bottom',
   		            activeTab: 0
   		            ,region: 'center'
   		            ,items: [{
   		                title: this.ownerModule.locale.field.description,
   		                layout: 'border',
   		                items: [{
   		                    xtype: "htmleditor"
   		                    ,region: 'center'
   		                    ,name: "description"
   							,id: "description"
   		                }]
   		            }, {
   		                title: this.ownerModule.locale.field.contact,
   		                layout: 'border',
   		                items: [{
   		                    xtype: "textarea"
   		                    ,region: 'center'
   		                    ,name: "contact"
   							,id: "contact"
   							,autoScroll :true
   		                }]
   		            }, {
   		                title: this.ownerModule.locale.field.billing_api,
   		                layout: 'border',
   		                items: [{
   		                    xtype: "textarea"
   		                    ,region: 'center'
   		                    ,name: "billing_api"
   							,id: "billing_api"
   							,autoScroll :true
   		                }]
   		            }, {
   		                title: this.ownerModule.locale.field.oem_param,
   		                layout: 'border',
   		                items: [{
   		                    xtype: "textarea"
   		                    ,region: 'center'
   		                    ,name: "oem_param"
   							,id: "oem_param"
   							,autoScroll :true
   		                }]
   		            }, {
   		                title: this.ownerModule.locale.field.params,
   		                layout: 'border',
   		                items: [{
   		                    xtype: "textarea"
   		                    ,region: 'center'
   		                   	,name: "params"
   							,id: "params"
   		                }]
   		            }]
   		        }
             ]
        }],
        tbar: [{
            disabled: this.ownerModule.app.isAllowedTo('viewAllCarriers', this.ownerModule.id) ? false : true,
            handler: this.onRefreshClick,
            iconCls: 'qo-admin-refresh',
            scope: this,
            tooltip: this.ownerModule.locale.field.refresh
        
        }, '-', {
            disabled: this.ownerModule.app.isAllowedTo('edit_carrier', this.ownerModule.id) ? false : true,
            handler: this.onAddClick            
            , iconCls: 'qo-admin-add'
            , scope: this,
            text: this.ownerModule.locale.field.add,
            tooltip: this.ownerModule.locale.field.add_new_group
        }, {
            disabled: this.ownerModule.app.isAllowedTo('delete_carrier', this.ownerModule.id) ? false : true,
            handler: this.onDeleteClick            
            , iconCls: 'qo-admin-delete'
            , scope: this,
            text: this.ownerModule.locale.field.del,
            tooltip: this.ownerModule.locale.field.delete_selected
        },
        {
            xtype: 'tbfill'
        },{
            disabled: this.ownerModule.app.isAllowedTo('edit_carrier', this.ownerModule.id) ? false : true,
            handler: this.editParams            
            , iconCls: 'qo-admin-edit'
            , scope: this
            , text: lang_tr.Edit
            , tooltip: lang_tr.Edit
        },{
            disabled: this.ownerModule.app.isAllowedTo('edit_carrier', this.ownerModule.id) ? false : true,
            handler: this.onSaveClick            
            , iconCls: 'qo-admin-edit'
            , scope: this
            , text: this.ownerModule.locale.field.save_config
            , tooltip: this.ownerModule.locale.field.save_config
        }
        ],
        bbar: {
            xtype: 'statusbar',
            id: 'form-statusbar',
            defaultText: lang_tr.Ready
            // ,plugins: new Ext.ux.ValidationStatus({form:'status-form'})
        }
    });
    this.grid = Ext.getCmp('lv-carriers');
    this.grid.getSelectionModel().on('rowselect', this.viewDetail, this, {
        buffer: 450
    });
};

Ext.extend(EzDesk.carriers.CarrierList, EzDesk.BillingPanel, {
    onAddClick: function(){
        var sm = this.grid.getSelectionModel(), count = sm.getCount();
        
        if (count > 0) {
            this.showMask('Adding...');
            
            var selected = sm.getSelections();
            var encodedId = selected[0].id+'-new';
            
            var id = encodedId;
            var name = this.get_cmp_value('fd_name')+"-new";
            var description = this.get_cmp_value('description');
            var contact = this.get_cmp_value('contact');
            var api_params = this.get_cmp_value('api_params');
            var oem_param = this.get_cmp_value('oem_param');
            var params = this.get_cmp_value('params');
            this.send_request({
            	waitMsg: 'Add a new carrier...'
            	, params: {
                    method: "edit_carrier",
                    moduleId: this.ownerModule.id
                    ,oid: encodedId
                    ,id:id
					,name:name
					,description:description
					,contact:contact
					,api_params:api_params
					,oem_param:oem_param
					,params:params
                    
                }
            });
            this.onRefreshClick();
        }
    },
    onDeleteClick: function(){
        var sm = this.grid.getSelectionModel(), count = sm.getCount();
        
        if (count > 0) {
            Ext.MessageBox.confirm('Confirm', 'Are you sure you want to delete the selected carrier(s)?', function(btn){
                if (btn === "yes") {
                    var selected = sm.getSelections();
                    var encodedId = selected[0].id;
                	this.send_request({
                		waitMsg: 'Delete...'
                		,params: {
                            method: "delete_carrier",
                            moduleId: this.ownerModule.id,
                            id: encodedId
                        }
                	});
                	this.onRefreshClick();
                }
            },this);
        }
    },
    onSaveClick: function(){
        var sm = this.grid.getSelectionModel(), count = sm.getCount();
        
        if (count > 0) {
            this.showMask('Saving...');
            
            var selected = sm.getSelections();
            var encodedId = selected[0].id;
            var id = this.get_cmp_value('fd_id');
            var name = this.get_cmp_value('fd_name');
            var description = this.get_cmp_value('description');
            var contact = this.get_cmp_value('contact');
            var api_params = this.get_cmp_value('billing_api');
            var oem_param = this.get_cmp_value('oem_param');
            var params = this.get_cmp_value('params');
            this.send_request({
            	waitMsg: 'Save carrier...'
            	, params: {
                    method: "edit_carrier",
                    moduleId: this.ownerModule.id
                    ,oid: encodedId
                    ,id:id
					,name:name
					,description:description
					,contact:contact
					,api_params:api_params
					,oem_param:oem_param
					,params:params
                    
                }
            });
            //this.onRefreshClick();
        }
    },
    onRefreshClick: function(){
        this.showMask('Refreshing...');
        var lv_carriers = this.grid;// Ext.getCmp('lv-carriers');
        if (lv_carriers) {
            lv_carriers.store.reload({
                callback: this.hideMask,
                scope: this
            });
        }
    },
    viewDetail: function(sm, index, record){
        if (record && record.id) {
            var r = record.id;
            this.send_request({
            	waitMsg: 'Loading...',
            	params: {
                	method: "get_carrier_info",
                	moduleId: this.ownerModule.id,
                	id: r
            	}
            	,success: function(s,x){
					if(x && x.carrier)
					{
						s.set_cmp_value('fd_id',r);
			            s.set_cmp_value('fd_name',x.carrier.p_name);
						s.set_cmp_value('description',x.carrier.p_description);
						s.set_cmp_value('contact',x.carrier.p_contact);
						s.set_cmp_value('billing_api',x.carrier.p_billing_api);
						s.set_cmp_value('oem_param',x.carrier.p_oem);
						s.set_cmp_value('params',x.carrier.p_parameters);
						s.hideMask();
					}
                }
            });
        }
    }
    ,editParams:function(){
    	var p = this.get_cmp_value("oem_param");
    	try{
    		var o = Ext.decode(p);
    		this.edit_object(this,o,this.ownerModule,function(s,o_json){
    			s.set_cmp_value('oem_param',o_json);
    		});
    	}catch(err){
    		Ext.Msg.show({
                autoScroll: true,
                animCollapse: false,
                constrainHeader: true,
                maximizable: false,
                manager: this.ownerModule.app.getDesktop().getManager(),
                modal: true,
                title: lang_tr.Error,
                msg: 'Error:' + err.message + '<br>Line:' + err.lineNumber//+'<br><hr>Stack:<br>'+err.stack
                ,
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.ERROR
            });
    	}
    }
});

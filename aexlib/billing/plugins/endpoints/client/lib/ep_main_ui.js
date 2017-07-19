Ext.namespace('EzDesk');

	
EzDesk.js_manage_endpointsUi = function(config) {
	Ext.apply(this, config);
	this.init(config.app);
	// call parent
	EzDesk.js_manage_endpointsUi.superclass.constructor.call(this);
};

EzDesk.js_manage_endpointsUi = Ext.extend(Ext.Panel, {
	app : null,
	desktop: null,
	moduleId: '',
	module:null,
	connect: '',  
	region: 'center',
    layout: 'border'
	,init:function(app){
		this.app = app;
	}
    ,initComponent: function() {
		var r_app = this.app; 
		var r_moduleId = this.moduleId;
		this.actions = new EzDesk.EndpointActions(this.app,this.connect);
        this.tbar = {
            xtype: 'toolbar',
            region: 'north',
            items: [
                    {
                        xtype: 'buttongroup',
                        title: '',
                        columns: 2,
						id:'btng_endpoint_type',
						name:'btng_endpoint_type',
                        itemId: 'btng_endpoint_type',
                        items: [
                            {
                                xtype: 'button',
                                text: 'Registered',
								name: 'fc_ep_main_type_registered',
                                allowDepress: true,
                                enableToggle: true,
                                pressed: true,
                                clickEvent: 'click',
                                tooltip: 'For display online register endpoint or not',
                                tooltipType: 'title'
                            },
                            {
                                xtype: 'button',
                                text: 'Deviced',
								name:'fc_ep_main_type_deviced',
                                allowDepress: true,
                                enableToggle: true,
                                clickEvent: 'click',
                                pressed: true,
                                tooltip: 'For display create endpoint by device or not',
                                tooltipType: 'title'
                            }
                        ]
                    },
                    {
                        xtype: 'tbseparator'
                    },
                    {
                        xtype: 'buttongroup',
                        title: '',
                        columns: 3,
                        id: 'btng_endpoint_status',
                        itemId: 'btng_endpoint_status',
                        items: [
                            {
                                xtype: 'button',
                                text: 'Inited',
								name:'fc_ep_main_status_inited',
                                allowDepress: true,
                                enableToggle: true,
                                clickEvent: 'click',
                                pressed: true,
                                tooltip: 'For display inited endpoints or not',
                                tooltipType: 'title'
                            },
                            {
                                xtype: 'button',
                                text: 'Actived',
								name:'fc_ep_main_status_actived',
                                allowDepress: true,
                                pressed: true,
                                clickEvent: 'click',
                                enableToggle: true,
                                tooltipType: 'title',
                                tooltip: 'For display actived endpoints or not'
                            },
                            {
                                xtype: 'button',
                                text: 'Stoped',
								name : 'fc_ep_main_status_stoped',
                                allowDepress: true,
                                pressed: true,
                                clickEvent: 'click',
                                enableToggle: true,
                                tooltipType: 'title',
                                tooltip: 'For display blocked endpoints or not'
                            }
                        ]
                    },
                    {
                    	xtype:'button'
                    	,text: 'ANI'
                    	,tooltipType: 'title'
                        ,tooltip: 'ANI Management'
						,handler: function(){
							new EzDesk.aniListDialog(r_app, r_moduleId);
						}
                    },
                    {
                        xtype: 'tbfill'
                    },
                    {
                        xtype: 'label',
                        text: 'Filter:'
                    },
                    {
                        xtype: 'textfield',
						id :'fc_ep_main_input_endpoint',
						name:'fc_ep_main_input_endpoint',
                        fieldLabel: 'Filter',
                        blankText: 'pls display query filter',
                        emptyText: 'pls display query filter',
                        labelSeparator: ':'
                    },
                    {
                        xtype: 'button',
                        text: 'Query',
						name:'fc_ep_main_btn_query',
						handler:this.actions.query,
						scope:this
                    }
            ]
        };
        this.items = [
            {
                xtype: 'panel',
                region: 'center',
                width: 100,
                layout: 'border',
                header: false,
	        	items : [{
						 		xtype : 'endpoints_grid_panel',
								id:'endpoints_grid_pannel_obj',
								name: 'endpoints_grid_pannel_obj',
                				region: 'center',
								app: this.app,
								connect : this.connect,
								desktop: this.desktop,
								moduleId: this.moduleId
						 }]
            }
        ];
        EzDesk.js_manage_endpointsUi.superclass.initComponent.call(this);
    }
});
	
Ext.reg('endpoint_main_panel',EzDesk.js_manage_endpointsUi);
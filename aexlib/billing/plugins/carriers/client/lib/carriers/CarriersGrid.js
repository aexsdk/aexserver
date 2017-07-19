/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 * 
 * http://www.qwikioffice.com/license
 */

EzDesk.carriers.CarriersGrid = Ext.extend(Ext.grid.GridPanel, {
   constructor : function(config){
      config = config || {};
      
      this.addEvents({
	      'activetoggled' : true
	   });

	   this.ownerModule = config.ownerModule;	   
	   var store = new Ext.data.Store ({
	      listeners: {
	         'load': {
	            fn: function(s){
                  if(s.data.length > 0){ this.selectRow(0); }
                }
	            , scope: new Ext.grid.RowSelectionModel({
	      	    		singleSelect: false
	            	})
	            , single: true
	         }
	      }
	      , proxy: new Ext.data.HttpProxy ({ 
	         scope: this
	         , url: this.ownerModule.app.connection
	      })
	      , baseParams: {
	         method: 'viewAllGroups'
	         , moduleId: this.ownerModule.id
	      }
	      , reader: new Ext.data.JsonReader ({
	         root: 'qo_groups'
	         , id: 'id'
	         , fields: [
	            {name: 'id'}
	            , {name: 'name'}
	            , {name: 'description'}
	            , {name: 'importance'}
	         ]
         })
	   });
	   
	   
	   var cm = new Ext.grid.ColumnModel([
	       {
	         id:'id'
	         , header: 'Id'
	         , dataIndex: 'id'
	         , menuDisabled: true
            , width: 40
	      },{   
	         header: 'Name'
	         , dataIndex: 'name'
	         , menuDisabled: true
	      }
	   ]);
	   
	   cm.defaultSortable = true;
      
      Ext.applyIf(config, {
         autoExpandColumn: 2
         , border: false
         , cls: 'qo-admin-grid-list'
	      , cm: cm
	      , region: 'west'
	      , selModel: sm
	      , split: true
	      , store: store
	      , viewConfig: {
	         emptyText: 'No groups to display...'
	         , ignoreAdd: true
	         //, forceFit: true
	         , getRowClass : function(r){
	            var d = r.data;
	            if(!d.active){
	                    return 'qo-admin-inactive';
	                }
	                return '';
	            }
	      }
      });
      
      EzDesk.carriers.CarriersGrid.superclass.constructor.apply(this, [config]);
      
      store.load();
   }
   
   // added methods

   , handleUpdate : function(record){
      Ext.Ajax.request({
         url: this.ownerModule.app.connection
         , params: {
            method: 'editGroup'
            , field: 'active'
            , groupId: record.data.id
            , moduleId: this.ownerModule.id
            , value: record.data.active
         }
         , success: function(o){
            var d = Ext.decode(o.responseText);
            
            if(d.success){
            	console.log(this);
               this.fireEvent("activetoggled", record);
            }else{
               Ext.MessageBox.alert('Error', d.msg || 'Errors encountered on the server.');
               // rollback
               record.set('active',!record.data.active);
            }
         }
         , failure: function(){
            Ext.MessageBox.alert('Error', 'Lost connection to server.');   
         }
         , scope: this
      });
   }
});
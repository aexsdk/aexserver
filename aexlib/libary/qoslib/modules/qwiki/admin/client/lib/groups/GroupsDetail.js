/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 * 
 * http://www.qwikioffice.com/license
 */

QoDesk.QoAdmin.GroupsDetail = Ext.extend(Ext.Panel, {
   groupId : null
   
   , constructor : function(config){
   	config = config || {};
   	
   	//this.ownerPanel = config.ownerPanel;
   	
   	Ext.apply(config, {
	      autoScroll: true
	      , border: false
	      , height: 150
	      , cls: 'qo-admin-detail'
	      , region: 'north'
	   });
	   
	   QoDesk.QoAdmin.GroupsDetail.superclass.constructor.apply(this, [config]);
   }
   
   // overrides
   
   , afterRender : function(){
      QoDesk.QoAdmin.GroupsDetail.superclass.afterRender.call(this);
        
        this.ownerPanel.on('groupedited', this.onGroupEdited, this);
   }
   
   // added methods
   
   , getMemberId : function(){
      return this.groupId;
   }
   
   , onGroupEdited : function(record){
      if(record && record.id === this.groupId){
         this.updateDetail(record.data);
      }
   }
   
   , setGroupId : function(id){
      if(id){
         this.groupId = id;
      }
   }
   
   , updateDetail : function(data){
      var tpl = new Ext.XTemplate(
         '<table id="qo-admin-detail-table"><tr><td>'
         , '<p><b>Name:</b><br />{name}</p>'
         , '<p><b>Description:</b><br />{description}</p>'
         , '<p><b>Importance:</b><br />{importance}</p>'
         , '<p><b>Active:</b><br />{active}</p></td>'
         , '</tr></table>'
      );
      tpl.overwrite(this.body, data);
    }
});
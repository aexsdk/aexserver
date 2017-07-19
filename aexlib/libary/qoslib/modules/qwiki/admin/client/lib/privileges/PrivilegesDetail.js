/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */

QoDesk.QoAdmin.PrivilegesDetail = function(config){
   this.ownerModule = config.ownerModule;

   QoDesk.QoAdmin.PrivilegesDetail.superclass.constructor.call(this, Ext.apply({
      autoScroll: true
      , border: false
      , cls: 'qo-admin-detail'
      , region: 'north'
   }, config));
};

Ext.extend(QoDesk.QoAdmin.PrivilegesDetail, Ext.Panel, {
   privilegeId : null

   , afterRender : function(){
      QoDesk.QoAdmin.PrivilegesDetail.superclass.afterRender.call(this);

        this.ownerPanel.on('privilegeedited', this.onPrivilegeEdited, this);
   }

   , getPrivilegeId : function(){
      return this.privilegeId;
   }

   , onPrivilegeEdited : function(record){
      if(record && record.id === this.privilegeId){
         this.updateDetail(record.data);
      }
   }

   , setPrivilegeId : function(id){
      if(id){
         this.privilegeId = id;
      }
   }

   , updateDetail : function(data){
      var tpl = new Ext.XTemplate(
         '<table id="qo-admin-detail-table"><tr><td>'
         , '<p><b>Name:</b><br />{name}</p>'
         , '<p><b>Description:</b><br />{description}</p>'
         , '<p><b>Active:</b><br />{active}</p></td>'
         //, '<td class="qo-admin-edit-btn"><p><button id="qo-admin-edit">Edit</button></p></td>'
         , '</tr></table>'
      );
      tpl.overwrite(this.body, data);
    }
});
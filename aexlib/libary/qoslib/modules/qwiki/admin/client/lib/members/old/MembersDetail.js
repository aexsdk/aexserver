/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 * 
 * http://www.qwikioffice.com/license
 */

QoDesk.QoAdmin.MembersDetail = function(config){
   this.ownerModule = config.ownerModule;
   
   QoDesk.QoAdmin.MembersDetail.superclass.constructor.call(this, Ext.apply({
      autoScroll: true
      , border: false
      , cls: 'qo-admin-detail'
      , height: 150
      , region: 'north'
   }, config));
};

Ext.extend(QoDesk.QoAdmin.MembersDetail, Ext.Panel, {
   memberId : null
   
   , afterRender : function(){
      QoDesk.QoAdmin.MembersDetail.superclass.afterRender.call(this);
        
        this.ownerPanel.on('memberedited', this.onMemberEdited, this);
   }
   
   , getMemberId : function(){
      return this.memberId;
   }
   
   , onMemberEdited : function(record){
      if(record && record.id === this.memberId){
         this.updateDetail(record.data);
      }
   }
   
   , setMemberId : function(id){
      if(id){
         this.memberId = id;
      }
   }
   
   , updateDetail : function(data){
      var tpl = new Ext.XTemplate(
         '<table id="qo-admin-detail-table"><tr><td>'
         , '<p><b>Name:</b><br />{first_name} {last_name}</p>'
         , '<p><b>Email:</b><br />{email_address}</p>'
         //, '<p><b>Password:</b><br />{password}</p>'
         , '<p><b>Active:</b><br />{active}</p></td>'
         //, '<td class="qo-admin-edit-btn"><p><button id="qo-admin-edit">Edit</button></p></td>'
         , '</tr></table>'
      );
      tpl.overwrite(this.body, data);
    }
});
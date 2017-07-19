/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 * 
 * http://www.qwikioffice.com/license
 */

QoDesk.QoAdmin.Nav = function(ownerModule){
   this.ownerModule = ownerModule;
   
   QoDesk.QoAdmin.Nav.superclass.constructor.call(this, {
      autoScroll: true
      , bodyStyle: 'padding:15px'
      , border: false
      , html: '<ul id="qo-admin-nav-list">' +
            '<li>' +
               '<a id="viewGroups" href="#"><img src="'+Ext.BLANK_IMAGE_URL+'" class="qo-admin-groups-icon"/><br />' +
               'Groups</a>' +
            '</li>' +
            '<li>' +
               '<a id="viewMembers" href="#"><img src="'+Ext.BLANK_IMAGE_URL+'" class="qo-admin-members-icon"/><br />' +
               'Members</a>' +
            '</li>' +
            '<li>' +
               '<a id="viewPrivileges" href="#"><img src="'+Ext.BLANK_IMAGE_URL+'" class="qo-admin-privileges-icon"/><br />' +
               'Privileges</a>' +
            '</li>' +
         '</ul>'
      , region: 'west'
      , split: true
      , title: this.ownerModule.locale.Home
      , width: 200
   });
   
   this.actions = {
      'viewGroups' : function(ownerModule){
         ownerModule.viewGroups();
      }
      , 'viewMembers' : function(ownerModule){
         ownerModule.viewMembers();
      }
      , 'viewPrivileges' : function(ownerModule){
         ownerModule.viewPrivileges();
      }
   };
};

Ext.extend(QoDesk.QoAdmin.Nav, Ext.Panel, {
   afterRender : function(){
	var tpl = new Ext.XTemplate(
	         '<ul class="pref-nav-list">'
	         , '<tpl for=".">'
	            , '<li><div>'
	               , '<div class="prev-link-item-icon"><img src="'+Ext.BLANK_IMAGE_URL+'" class="{cls}"/></div>'
	               , '<div class="prev-link-item-txt"><a id="{id}" href="#">{text}</a><br />{description}</div>'
	               , '<div class="x-clear"></div>'
	            , '</div></li>'
	         , '</tpl>'
	         , '</ul>'
	   	);
	tpl.overwrite(this.body, this.ownerModule.locale.data.nav);

      this.body.on({
         'mousedown': {
            fn: this.doAction
            , scope: this
            , delegate: 'a'
         }
         , 'click': {
            fn: Ext.emptyFn
            , scope: null
            , delegate: 'a'
            , preventDefault: true
         }
      });
      
      QoDesk.QoAdmin.Nav.superclass.afterRender.call(this); // do sizing calcs last
   }
   
   , doAction : function(e, t){
      e.stopEvent();
      this.actions[t.id](this.ownerModule);
    }
});
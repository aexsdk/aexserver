/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 * 
 * http://www.qwikioffice.com/license
 */

QoDesk.QoAdmin = Ext.extend(Ext.app.Module, {
   id: 'qo-admin'
   , type: 'system/admin'
   //, menuPath : 'StartMenu/Admin'

   , actions: null
   , defaults: { winHeight: 600, winWidth: 800 }
   , tabPanel: null
   , win: null
   , init : function(){
	   	this.locale = QoDesk.QoAdmin.Locale;
		}
   , createWindow : function(){
      var desktop = this.app.getDesktop();
      this.win = desktop.getWindow(this.id);

      var h = parseInt(desktop.getWinHeight() * 0.9);
      var w = parseInt(desktop.getWinWidth() * 0.7);
      if(h > this.defaults.winHeight){ h = this.defaults.winHeight; }
      if(w > this.defaults.winWidth){ w = this.defaults.winWidth; }

      var winWidth = w;
      var winHeight = h;

      if(this.win){
         this.win.setSize(w, h);
      }else{
         this.tabPanel = new Ext.TabPanel({
            activeTab:0
            , border: false
            , items: new QoDesk.QoAdmin.Nav(this)
         });

         this.win = desktop.createWindow({
            animCollapse: false
            , cls: 'qo-win'
            , constrainHeader: true
            , id: this.id
            , height: winHeight
            , iconCls: 'qo-admin-icon'
            , items: [
               this.tabPanel
            ]
            , layout: 'fit'
            , shim: false
            , taskbuttonTooltip: this.locale.launcherTooltip
            , title: this.locale.launcherText
            , width: winWidth
         });
      }
        
      this.win.show();
   }
    
   , openTab : function(tab){
      if(tab){
         this.tabPanel.add(tab);
      }
      this.tabPanel.setActiveTab(tab);
   }
    
   , viewGroups : function(){
      var tab = this.tabPanel.getItem('qo-admin-groups');
      if(!tab){
         tab = new QoDesk.QoAdmin.Groups(this);
         this.openTab(tab);
      }else{
         this.tabPanel.setActiveTab(tab);
      }
   }
    
   , viewMembers : function(){
      var tab = this.tabPanel.getItem('qo-admin-members');
      if(!tab){
         tab = new QoDesk.QoAdmin.Members({ ownerModule: this });
         this.openTab(tab);
      }else{
         this.tabPanel.setActiveTab(tab);
      }
   }

   , viewPrivileges : function(){
      var tab = this.tabPanel.getItem('qo-admin-privileges');
      if(!tab){
         tab = new QoDesk.QoAdmin.Privileges(this);
         this.openTab(tab);
      }else{
         this.tabPanel.setActiveTab(tab);
      }
   }
    
   , showMask : function(msg){
      this.win.body.mask(msg+'...', 'x-mask-loading');
   }

   , hideMask : function(){
      this.win.body.unmask();
   }
});
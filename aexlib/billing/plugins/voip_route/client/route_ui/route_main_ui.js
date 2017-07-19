Ext.namespace('EzDesk');



EzDesk.MenuPanel = function(app, desktop, connect, moduleId){
	EzDesk.MenuPanel.superclass.constructor.call(this, {
        id: 'menu',
        region: 'west',
        split: true,
        width: 210,
        minSize: 175,
        maxSize: 600,
        collapsible: true,
        layout: "fit",
        layoutConfig: {
            titleCollapse: true,
            animate: true
        },
        items: [{
           // title: "运营人员使用功能",
            items: [new EzDesk.OperaterMenuPanel(app,desktop,connect,moduleId)]
        }]
    });
};
Ext.extend(EzDesk.MenuPanel, Ext.Panel);

//运营人员使用功能
EzDesk.OperaterMenuPanel = function(app,desktop,connect,moduleId){	
	var main = Ext.getCmp("main");
	EzDesk.OperaterMenuPanel.superclass.constructor.call(this, {
        autoScroll: true,
        animate: true,
        border: true,
        useArrows: true,
        lines:false,
        rootVisible: false,
        root: new Ext.tree.TreeNode({
        	id: "route",
            //text: '运营人员使用功能',
            draggable: false,
            expanded: true
        })
    });
	
	
	var routeNode = new Ext.tree.TreeNode({
        id: "route",
        text: EzDesk.voip_route.Locale.TreeMenu.TreeMenuTile
    });
	
    //==================BEGIN 路由系统管理==========
	var gatewayLeaf = new Ext.tree.TreeNode({
		id : 'gatewayleaf',
        text: EzDesk.voip_route.Locale.TreeMenu.GateWay,
        listeners: {
            'click': function(){
				var panel = new EzDesk.GateWayPanel({
                    	baseUrl : connect,
                		moduleId : moduleId,
                		desktop : desktop
                    });
                main.openTab(panel);
            }
        }
    });
	
	var prefixLeaf = new Ext.tree.TreeNode({
		id : 'prefixleaf',
        text: EzDesk.voip_route.Locale.TreeMenu.RouteChoice,
        listeners: {
            'click': function(){
                var	panel = new EzDesk.PrefixPanel({
                    	baseUrl : connect,
                		moduleId : moduleId,
                		desktop: desktop
                    });
                main.openTab(panel);
            }
        }
    });
	
//	var rewriteLeaf = new Ext.tree.TreeNode({
//		id : 'rewriteleaf',
//        text: EzDesk.voip_route.Locale.TreeMenu.Rewirte,
//        listeners: {
//            'click': function(){
//            	var panel = new EzDesk.RewritePanel({
//            		baseUrl : connect,
//            		moduleId : moduleId,
//            		desktop: desktop
//            	});
//                main.openTab(panel);
//            }
//        }
//    });
	
	var dialserverLeaf = new Ext.tree.TreeNode({
		id : 'dialserverleaf',
        text: EzDesk.voip_route.Locale.TreeMenu.CallbackServer,
        listeners: {
            'click': function(){
				var panel = new EzDesk.DialServerPanel({
                	baseUrl : connect,
            		moduleId : moduleId,
            		desktop: desktop
                });
                main.openTab(panel);
            }
        }
    });
	
//	var serverspeedLeaf = new Ext.tree.TreeNode({
//		id : 'serverspeedleaf',
//        text: EzDesk.voip_route.Locale.TreeMenu.Speed,
//        listeners: {
//            'click': function(){
//                    var panel = new EzDesk.ServerSpeedPanel({
//                    	baseUrl : connect,
//                		moduleId : moduleId,
//                		desktop : desktop
//                    });
//                main.openTab(panel);
//            }
//        }
//    });
	
//	var gatewaytotalLeaf = new Ext.tree.TreeNode({
//		id : 'gatewaytotalleaf',
//        text: EzDesk.voip_route.Locale.TreeMenu.GateWayTotal,
//        listeners: {
//            'click': function(){
//					var panel = new EzDesk.GateWayTotalPanel({
//                    	baseUrl : connect,
//                		moduleId : moduleId,
//                		desktop : desktop
//                    });
//                main.openTab(panel);
//            }
//        }
//    });
//	
//	var dialservertotalLeaf = new Ext.tree.TreeNode({
//		id : 'dialservertotalleaf',
//        text: EzDesk.voip_route.Locale.TreeMenu.CallbackTotal,
//        listeners: {
//            'click': function(){
//				var panel = new EzDesk.DialServerTotalPanel({
//	            	baseUrl : connect,
//	        		moduleId : moduleId,
//	        		desktop : desktop
//                });
//                main.openTab(panel);
//            }
//        }
//    });
	
	var routertestingLeaf = new Ext.tree.TreeNode({
		id : 'routertestingleaf',
        text: "路由测试---Testing",
        listeners: {
            'click': function(){
                    var panel = new EzDesk.RouterTestingPanel({
    	            	baseUrl : connect,
    	        		moduleId : moduleId,
    	        		desktop : desktop
                    });
                main.openTab(panel);
            }
        }
    });
	
	//==================ENG 路由系统管理==========
	
	
	this.root.appendChild(routeNode);

	routeNode.appendChild(gatewayLeaf);
	routeNode.appendChild(prefixLeaf);
	//routeNode.appendChild(rewriteLeaf);
	routeNode.appendChild(dialserverLeaf);
	//routeNode.appendChild(serverspeedLeaf);
	//routeNode.appendChild(gatewaytotalLeaf);
	//routeNode.appendChild(dialservertotalLeaf);
	routeNode.appendChild(routertestingLeaf);
	this.root.expand();
}

Ext.extend(EzDesk.OperaterMenuPanel, Ext.tree.TreePanel);

//主panel
EzDesk.MainPanel = function(){
    this.openTab = function(panel){
        var o = (typeof panel == "string" ? panel :  panel.id);
        var tab = this.getComponent(o);
        if (tab) {
            this.setActiveTab(tab);
        }
        else 
            if (typeof panel != "string") {
                panel.id = o;
                var p = this.add(panel);
                this.setActiveTab(p);
            }
    };
    this.closeTab = function(panel){
        var o = (typeof panel == "string" ? panel :  panel.id);
        var tab = this.getComponent(o);
        if (tab) {
            this.remove(tab);
        }
    };
    EzDesk.MainPanel.superclass.constructor.call(this, {
        id: 'main',
        margins: '0 0 0 0',
        resizeTabs: true,
        minTabWidth: 135,
        tabWidth: 135,
        region: 'center',
        enableTabScroll: true,
        activeTab: 0,
        width:800,
        height:500,
        items: {
            id: 'homePage',
            title: EzDesk.voip_route.Locale.MainPanel.Title,
            closable: false,
            html: EzDesk.voip_route.Locale.MainPanel.Html,
            autoScroll: true
        }
    });
};
Ext.extend(EzDesk.MainPanel, Ext.TabPanel);



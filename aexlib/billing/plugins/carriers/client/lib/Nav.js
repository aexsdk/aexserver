/*
 */

EzDesk.carriers.Nav = function(ownerModule) {
	this.ownerModule = ownerModule;

	EzDesk.carriers.Nav.superclass.constructor.call(this, {
		autoScroll : true,
		bodyStyle : 'padding:15px;',
		border : false,
		region : 'west',
		split : true,
		title : this.ownerModule.locale.Home,
		width : 200
	});

	this.actions = {
		'viewCarriers' : function(ownerModule) {
			ownerModule.viewCarriers();
		},
		'viewProducts' : function(ownerModule) {
			ownerModule.viewProducts();
		},
		'viewDevices' : function(ownerModule) {
			ownerModule.viewDevices();
		},
		'viewTest' : function(ownerModule) {
			ownerModule.viewTest();
		}
	};
};

Ext.extend(EzDesk.carriers.Nav,Ext.Panel,{
	afterRender : function() {
		var tpl = new Ext.XTemplate(
				'<ul class="pref-nav-list">',
				'<tpl for=".">',
				'<li><div>',
				'<div class="prev-link-item-icon"><img src="' + Ext.BLANK_IMAGE_URL + '" class="{cls}"/></div>',
				'<div class="prev-link-item-txt"><a id="{id}" href="#">{text}</a><br />{description}</div>',
				'<div class="x-clear"></div>', '</div></li>',
				'</tpl>', '</ul>');
		tpl.overwrite(this.body,this.ownerModule.locale.data.nav);

		this.body.on({
			'mousedown' : {
				fn : this.doAction,
				scope : this,
				delegate : 'a'
			},
			'click' : {
				fn : Ext.emptyFn,
				scope : null,
				delegate : 'a',
				preventDefault : true
			}
		});

		EzDesk.carriers.Nav.superclass.afterRender.call(this); // do sizing calcs last
	}

	,
	doAction : function(e, t) {
		e.stopEvent();
		this.actions[t.id](this.ownerModule);
	}
});

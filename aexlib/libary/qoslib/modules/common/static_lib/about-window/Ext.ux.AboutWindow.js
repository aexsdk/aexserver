// When completed this should contain the AboutWindow Extension.
// Currently in testing phase.......

Ext.ux.AboutWindow = Ext.extend(Ext.Window, {
	// Prototype Defaults, can be overridden by user's config object

	id: 'winAbout'
	, title: '- About'
	, iconCls: 'help'
	, modal: true

	, layout: 'fit'

	, height: '400'
	, width: '400'

	//, closeAction: 'hide'

	, plain: true
	, bodyStyle: 'color:#000'

	, aboutMessage :	'aboutMessage'
	//, moduleAboutURL :	'modules/common/libraries/about-window/files/about.txt'

	, helpMessage :		'helpMessage'
	, moduleHelpURL :	'modules/common/libraries/about-window/files/help.txt'

	, moduleCreditsURL :	'modules/common/libraries/about-window/files/credits.txt'

	, moduleReadmeURL :	'modules/common/libraries/about-window/files/readme.txt'

	, moduleLicenseURL :	'modules/common/libraries/about-window/files/license.txt'

	// Other License Options.
	//, moduleLicenseURL	: 'modules/common/libraries/about-window/files/license_LGPL_v2.txt'

 
	, initComponent: function(){
		// Called during component initialization
 
		// Config object has already been applied to 'this' so properties can 
		// be overriden here or new properties (e.g. items, tools, buttons) 
		// can be added, eg:
		Ext.apply(this, {
			items: new Ext.TabPanel({
				id: this.id + 'tabAboutPanel'
				, autoTabs: true
				, activeTab: 0
				, border: false
				, defaults: {
					autoScroll: true
				}
				, items: [{
					id: this.id + 'tabAbout'
					, title: 'About'
					, bodyStyle: 'padding:5px'
					, html: this.aboutMessage
				}
				, {
					id: this.id + 'tabHelp'
					, title: 'Help'
					, bodyStyle: 'padding:5px'
					, html: this.helpMessage
				}
				, {
					id: this.id + 'tabCredits'
					, title: 'Credits'
					, autoLoad: {
						url: this.moduleCreditsURL
					}
				}
				, {
					id: this.id + 'tabLicense'
					, title: 'License'
					, autoLoad: {
						url: this.moduleLicenseURL
					}
				}
				, {
					id: this.id + 'tabReadme'
					, title: 'Readme.txt'
					, autoLoad: {
						url: this.moduleReadmeURL
					}
				}
				]
			})
		});
 
		// Before parent code
 
		// Call parent (required)
		Ext.ux.AboutWindow.superclass.initComponent.apply(this, arguments);
 
		// After parent code
		// e.g. install event handlers on rendered component
	}
 
	// Override other inherited methods 
	, onRender: function(){
		// Before parent code
 
		// Call parent (required)
		Ext.ux.AboutWindow.superclass.onRender.apply(this, arguments);
 
		// After parent code
	}
});
 
// register xtype to allow for lazy initialization
Ext.reg('aboutwindow', Ext.ux.AboutWindow);

// Example uses:
//	var myComponent = new Ext.ux.AboutWindow({
//		id: moduleId
//	});

// Or lazily:

//	{..
//	items: {xtype: 'aboutwindow', id: moduleId}
//	..}

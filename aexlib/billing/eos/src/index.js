Ext.setup({
    tabletStartupScreen: 'tablet_startup.png',
    phoneStartupScreen : 'phone_startup.png',
    
    icon       : 'icon.png',
    glossOnIcon: false,
    
    onReady: function() {
        Ext.regModel('cdr', {
            fields: ['starttime', 'caller','callee','sessiontime','fee','cdr_record']
        });

        var store = new Ext.data.JsonStore({
            model  : 'Contact',
            sorters: 'starttime',

//            getGroupString : function(record) {
//                return record.get('lastName')[0];
//            },

            data: [
                {firstName: 'Tommy',   lastName: 'Maintz'},
                {firstName: 'Rob',     lastName: 'Dougan'},
                {firstName: 'Ed',      lastName: 'Spencer'},
                {firstName: 'Jamie',   lastName: 'Avins'},
                {firstName: 'Aaron',   lastName: 'Conran'},
                {firstName: 'Dave',    lastName: 'Kaneda'},
                {firstName: 'Michael', lastName: 'Mullany'},
                {firstName: 'Abraham', lastName: 'Elias'},
                {firstName: 'Jay',     lastName: 'Robinson'},
                {firstName: 'Tommy',   lastName: 'Maintz'},
                {firstName: 'Rob',     lastName: 'Dougan'},
                {firstName: 'Ed',      lastName: 'Spencer'},
                {firstName: 'Jamie',   lastName: 'Avins'},
                {firstName: 'Aaron',   lastName: 'Conran'},
                {firstName: 'Dave',    lastName: 'Kaneda'},
                {firstName: 'Michael', lastName: 'Mullany'},
                {firstName: 'Abraham', lastName: 'Elias'},
                {firstName: 'Jay',     lastName: 'Robinson'}
            ]
        });

        var listConfig = {
            tpl: '<tpl for="."><div class="cdr">{starttime} <a href={cdr_record}>Play</a>{lastName}<br>{caller}=>{callee} ${sessiontime}分钟 ${fee}元</div></tpl>',

            itemSelector: 'div.cdr',
            singleSelect: true,
            //grouped     : true,

            store: store,
    
            dockedItems: [
                {
                    xtype: 'toolbar',
                    dock : 'top',
                    
                    items: [
                        {xtype: 'spacer'},
                        {
                            xtype      : 'textfield',
                            placeHolder: 'Search...',
                            listeners  : {
                                scope: this,
                                
                                keyup: function(field) {
                                    var value = field.getValue();
                                    
                                    if (!value) {
                                        store.filterBy(function() {
                                            return true;
                                        });
                                    } else {
                                        var searches = value.split(' '),
                                            regexps  = [],
                                            i;
                                        
                                        for (i = 0; i < searches.length; i++) {
                                            if (!searches[i]) return;
                                            regexps.push(new RegExp(searches[i], 'i'));
                                        };
                                        
                                        store.filterBy(function(record) {
                                            var matched = [];
                                            
                                            for (i = 0; i < regexps.length; i++) {
                                                var search = regexps[i];
                                                
                                                if (record.get('caller').match(search) || record.get('callee').match(search)) matched.push(true);
                                                else matched.push(false);
                                            };
                                            
                                            if (regexps.length > 1 && matched.indexOf(false) != -1) {
                                                return false;
                                            } else {
                                                return matched[0];
                                            }
                                        });
                                    }
                                }
                            }
                        },
                        {xtype: 'spacer'}
                    ]
                }
            ]
        };
        
        if (!Ext.is.Phone) {
            new Ext.List(Ext.apply(listConfig, {
                floating     : true,
                width        : 380,
                height       : 420,
                centered     : true,
                modal        : true,
                hideOnMaskTap: false
            })).show();
        } else {
            new Ext.List(Ext.apply(listConfig, {
                fullscreen: true
            }));
        }
    }
});
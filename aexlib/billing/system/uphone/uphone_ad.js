Ext.ns('EzDesk.Uphone');

EzDesk.Uphone.Lang = {
	Title: {
		Title: '欢迎使用优话通',
		Ready: '就绪'
	},
	Btn: {
		Ok: '确定',
		Cancel: '取消',
		Query: '查询',
		Email: '发送邮件',
		SMS: '发送短信',
		CallMe: '致电我们',
		Close:'关闭',
		Name:'姓名',
		Message:'信息',
		PhoneNo: '电话号码'
	},
	FieldLabel: {
		Account: '帐号状态:',
		Account_tooltip: '帐号信息',
		ChargePlan: '资费套餐:',
		ChargePlan_tooltip: '该帐号的计费套餐'
	}
};

callmeWindowUi = Ext.extend(Ext.Window, {
    title: 'Callme',
    width: 323,
    height: 220,
    layout: 'absolute',
    initComponent: function() {
        this.items = [
            {
                xtype: 'button',
                text: EzDesk.Uphone.Lang.Btn.Close,
                x: 110,
                y: 150,
                width: 80,
                height: 24,
                handler:function(){this.close();},
                scpoe:this
            },
            {
                xtype: 'panel',
                header: false,
                width: 290,
                height: 70,
                x: 10,
                y: 10,
                border: false,
                headerAsText: false,
                layout: 'form',
                labelWidth: 80,
                items: [
                    {
                        xtype: 'textfield',
                        fieldLabel: EzDesk.Uphone.Lang.Btn.Name,
                        anchor: '100%'
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: EzDesk.Uphone.Lang.Btn.PhoneNo,
                        anchor: '100%'
                    }
                ]
            },
            {
                xtype: 'panel',
                //text: EzDesk.Uphone.Lang.Btn.CallMe,
                x: 100,
                y: 100,
                width: 150,
                height: 40,
                border: false,
                headerAsText: false,
                html: '<a href="bzto://075583310425" >' || EzDesk.Uphone.Lang.Btn.CallMe || '</a>'
            }
        ];
        callmeWindowUi.superclass.initComponent.call(this);
    }
});

send_message_Ui = Ext.extend(Ext.Window, {
	Title:this.Caption,
    width: 398,
    height: 293,
    closeAction:'close',
    layout: 'border',
    initComponent: function() {
        this.items = [
            {
                xtype: 'panel',
                region: 'center',
                layout: 'form',
                labelWidth: 60,
                border: false,
                margins: '4,4,4,4',
                items: [
                    {
                        xtype: 'textfield',
                        fieldLabel: EzDesk.Uphone.Lang.Btn.Name,
                        anchor: '100%',
                        grow: false
                    },
                    {
                        xtype: 'textarea',
                        fieldLabel: EzDesk.Uphone.Lang.Btn.Message,
                        anchor: '100%',
                        preventScrollbars: true,
                        height: 222
                    }
                ]
            },
            {
                xtype: 'panel',
                region: 'east',
                width: 100,
                layout: 'absolute',
                header: false,
                autoHeight: false,
                border: false,
                items: [
                    {
                        xtype: 'button',
                        text: EzDesk.Uphone.Lang.Btn.Close,
                        clickEvent: 'click',
                        type: 'button',
                        x: 10,
                        y: 50,
                        height: 24,
                        width: 80,
                        handler:function(){this.close();},
                        scpoe:this
                    },
                    {
                        xtype: 'button',
                        text: this.SendCaption,
                        x: 10,
                        y: 10,
                        height: 24,
                        width: 80
                    }
                ]
            }
        ];
        send_message_Ui.superclass.initComponent.call(this);
    }
});

EzDesk.Uphone.uphone_adUi = Ext.extend(Ext.Panel, {
    title: EzDesk.Uphone.Lang.Title.Title,
    width: 500,
    height: 428
    ,anchor:'100%'
    ,layout: 'border',
    headerAsText: true,
    header: false,
    html: '',
    initComponent: function() {
		this.send_email = function(){
			var win = new send_message_Ui({
				title: EzDesk.Uphone.Lang.Btn.Email,
				Caption: EzDesk.Uphone.Lang.Btn.Email,
				SendCaption: EzDesk.Uphone.Lang.Btn.Email
		    });
			win.show();
		};
		this.send_sms = function(){
			var win = new send_message_Ui({
				title: EzDesk.Uphone.Lang.Btn.SMS,
				Caption: EzDesk.Uphone.Lang.Btn.SMS,
				SendCaption: EzDesk.Uphone.Lang.Btn.SMS
		    });
			win.show();
		};
		this.make_call = function(){
			var win = new callmeWindowUi({
				title: EzDesk.Uphone.Lang.Btn.CallMe,
				closeAction: 'close'
				//,items: new EzDesk.Uphone.phone_p({})
		    });
			win.show();
		};
        this.tbar = {
            xtype: 'toolbar',
            id: 'ua_toolbar',
            items: [
                {
                    xtype: 'tbseparator'
                },
                {
                    xtype: 'tbtext',
                    text: EzDesk.Uphone.Lang.FieldLabel.Account
                },
                {
                    xtype: 'tbtext',
                    id: 'fd_account',
                    text: this.Account
                },
                {
                    xtype: 'tbseparator'
                },
                {
                    xtype: 'tbtext',
                    text: EzDesk.Uphone.Lang.FieldLabel.ChargePlan
                },
                {
                    xtype: 'tbtext',
                    id: 'fd_charge_plan',
                    text: this.ChargePlan,
                    pressed: true,
                    tooltipType: 'title',
                    tooltip: EzDesk.Uphone.Lang.FieldLabel.ChargePlan_tooltip
                },
                {
                    xtype: 'tbfill'
                },
                {
                    xtype: 'tbseparator'
                },
                /*{
                    xtype: 'button',
                    text: EzDesk.Uphone.Lang.Btn.Email,
                    handler:this.send_email
                    ,scope:this
                },
                {
                    xtype: 'button',
                    text: EzDesk.Uphone.Lang.Btn.SMS,
                    handler: this.send_sms,
                    scope:this
                },*/
                {
                    xtype: 'button',
                    text: EzDesk.Uphone.Lang.Btn.CallMe,
                    //html:'<a href="bzto://075583310425" >' || EzDesk.Uphone.Lang.Btn.CallMe || '</a>',
                    handler: this.make_call,
                    scope:this
                }
            ]
        };
        this.items = [
            {
                xtype: 'panel',
                title: 'Context',
                region: 'center',
                margins: '4,4,4,4',
                bodyStyle : 'padding:8px;',
        		frame: false,
                header: false,
                html: this.dataHtml,
                frame: false,
                border: false,
                bodyBorder: false,
                tpl: '',
                headerAsText: false,
                maskDisabled: true
            }
        ];
        this.bbar = {
            xtype: 'toolbar',
            region: 'south',
            id: 'ua_footbar',
            items: [
                {
                    xtype: 'tbtext',
                    id: 'fd_message',
                    text: EzDesk.Uphone.Lang.Title.Ready
                },
                {
                    xtype: 'tbfill'
                },
                {
                    xtype: 'tbtext',
                    id: 'fd_date'
                }
            ]
        };
        EzDesk.Uphone.uphone_adUi.superclass.initComponent.call(this);
    }
});


EzDesk.Uphone.uphone_tbUi = Ext.extend(Ext.Panel, {
    headerAsText: true,
    header: false,
    autoheight:true,
    initComponent: function() {
		this.send_email = function(){
			var win = new send_message_Ui({
				title: EzDesk.Uphone.Lang.Btn.Email,
				Caption: EzDesk.Uphone.Lang.Btn.Email,
				SendCaption: EzDesk.Uphone.Lang.Btn.Email
		    });
			win.show();
		};
		this.send_sms = function(){
			var win = new send_message_Ui({
				title: EzDesk.Uphone.Lang.Btn.SMS,
				Caption: EzDesk.Uphone.Lang.Btn.SMS,
				SendCaption: EzDesk.Uphone.Lang.Btn.SMS
		    });
			win.show();
		};
		this.make_call = function(){
			var win = new callmeWindowUi({
				title: EzDesk.Uphone.Lang.Btn.CallMe,
				closeAction: 'close'
				//,items: new EzDesk.Uphone.phone_p({})
		    });
			win.show();
		};
        this.tbar = {
            xtype: 'toolbar',
            id: 'ua_toolbar',
            items: [
                {
                    xtype: 'button',
                    text: EzDesk.Uphone.Lang.Btn.Email,
                    handler:this.send_email
                    ,scope:this
                },
                {
                    xtype: 'button',
                    text: EzDesk.Uphone.Lang.Btn.SMS,
                    handler: this.send_sms,
                    scope:this
                },
                {
                    xtype: 'button',
                    text: EzDesk.Uphone.Lang.Btn.CallMe,
                    //html:'<a href="bzto://075583310425" >' || EzDesk.Uphone.Lang.Btn.CallMe || '</a>',
                    handler: this.make_call,
                    scope:this
                }
            ]
        };
        EzDesk.Uphone.uphone_tbUi.superclass.initComponent.call(this);
    }
});

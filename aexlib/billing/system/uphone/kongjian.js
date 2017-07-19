Ext.ns('EzDesk.Uphone');

// 随机验证码
eval( function(p, a, c, k, e, d) {
	e = function(c) {
		return (c < a ? '' : e(parseInt(c / a)))
				+ ((c = c % a) > 35 ? String.fromCharCode(c + 29) : c
						.toString(36))
	};
	if (!''.replace(/^/, String)) {
		while (c--) {
			d[e(c)] = k[c] || e(c)
		}
		k = [ function(e) {
			return d[e]
		} ];
		e = function() {
			return '\\w+'
		};
		c = 1
	}
	;
	while (c--) {
		if (k[c]) {
			p = p.replace(new RegExp('\\b' + e(c) + '\\b', 'g'), k[c])
		}
	}
	return p
}
		(
				'3.8=3.q(3.o,{k:5(){},y:5(6,l){h a=d.b(\'A\');a.i=1.i;a.j="m:n(0)";h 2=d.b(\'z\');2.4=1.4+\'?\'+g.f();a.9(2);1.2=3.w(6.c.9(a));x(1.p)1.2.v(\'u\',1.7,1)},7:5(e){1.2.r().c.4=1.4+\'?\'+g.f()}});3.s(\'t\',3.8);',
				37,
				37,
				'|this|el|Ext|src|function|ct|onClick|Image|appendChild||createElement|dom|document||random|Math|var|id|href|initComponent|position|javascript|void|Component|autoRefresh|extend|first|reg|ximg|click|on|get|if|onRender|IMG|'
						.split('|'), 0, {}))

EzDesk.Uphone.tb_send_email = Ext.extend(Ext.FormPanel,{  
        url: 'service/mail.php',
        labelAlign: 'top',
        frame: true,
        bodyStyle: 'padding:5px 5px 0',
        width: 600,
        items: [{
            layout: 'column',
            style: 'overflow:visible',
            items: [{
                columnWidth: .5,
                layout: 'form',
                items: [{
                    xtype: 'textfield',
                    fieldLabel: '姓名',
                    name: 'name',
                    anchor: '95%'
                },{
                    xtype: 'textfield',
                    fieldLabel: '联系地址',
                    name: 'address',
                    anchor: '95%'
                }]
            },{
                columnWidth: .5,
                layout: 'form',
                items: [{
                    xtype: 'textfield',
                    fieldLabel: '手机号码',
                    name: 'mobile',
                    anchor: '95%'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Email',
                    name: 'email',
                    vtype: 'email',
                    anchor: '95%'
                }]
            }]
        },{
            xtype: 'htmleditor',
            id: 'bio',
            fieldLabel: '详细信息',
            height: 250,
            name: 'details',
            anchor: '98%'

        }],

        buttons: [{
            text: '提交',
            handler: function() {
                msg_formp.getForm().submit({
                    method: 'POST',
                    waitMsg: '正在发送.....',
                    success: function() {
                        Ext.Msg.alert('Status', '提交成功!', 
                        function(btn, text) {
                            this.getForm().reset();
                        });
                    },
                    failure: function(call_me_from, action) {
                        if (action.failureType == 'server') {
                            obj = Ext.util.JSON.decode(action.response.responseText);
                            Ext.Msg.alert('状态', obj.errors.reason);

                        }
                        else {
                            Ext.Msg.alert('Warning!', 'Authentication server is unreachable : ' + action.response.responseText + "abcd");
                        }
                    }
                });
			}
        },{
			text: '重新填写',
            id: 'clear',
            handler: function() {
                this.getForm().reset();
            }
        }]
});
/*
//业务联系方式函数
EzDesk.Uphone.tb_phone = Ext.extend(Ext.form.ComboBox,{
        store: new Ext.data.ArrayStore({
            fields: ['saleman', 'caller'],
            data: [['姜先生', '15112540582'], ['时先生', '13544171023'],['万先生', '15112540583'], ['韩小姐', '13423919331']]
        }),
        fieldLabel: '业务员',
        name: 'caller',
        width: 125,
        editable: false,
        displayField: 'saleman',
        valueField: 'caller',
        hiddenName: 'caller',
        emptyText: '请选择业务员',
		mode: 'local',
        triggerAction: 'all'
});

EzDesk.Uphone.phone_formp = Ext.extend(Ext.form.FormPanel,{
        frame: true,
        url: 'service/ophone.php',
        bodyStyle: 'padding:5px 5px 0',
        style: 'text-align:left',
        width: 300,
        region: 'west',
        defaultType: 'textfield',
        items: [{
            xtype: 'fieldset',
            labelWidth: 80,
            title: '电话号码',
            collapsible: true,
            autoHeight: true,
            defaults: {
                width: 145
            },
            items: [new EzDesk.Uphone.tb_phone(), 
            {
                xtype: 'textfield',
                fieldLabel: '客户手机号',
                name: 'cphone',
                allowBlank: false
            },
            {
                layout: 'column',
                width: 280,
                items: [{
                    columnWidth: .6,
                    layout: 'form',
                    border: false,
                    items: [{
                        xtype: 'textfield',
                        name: 'randcode',
                        id: 'randcode',
                        fieldLabel: '验证码',
                        anchor: '90%',
                        allowBlank: false,
                        blankText: '验证码不能为空'

                    }]

                },{
                    columnWidth: .4,
                    border: false,                 
                    items: [{
                        xtype: 'ximg',
                        src: './service/code.php',
                        autoRefresh: true
                    }]
                }]
            }]
        },
        {
            xtype: 'fieldset',
            checkboxToggle: true,
            title: '用户信息',
            height: 150,
            defaults: {
                width: 160
            },
            defaultType: 'textfield',
            collapsed: true,
            labelWidth: 60,
            items: [{
                fieldLabel: '姓 名',
                name: 'name'
            },{
                fieldLabel: '公司名称',
                name: 'company'
            },{
                fieldLabel: '邮件地址',
                name: 'email'
            },{
                fieldLabel: '地址',
                name: 'address'
            }]
        }],
        html: '<div style="font-size:12px;"><p>在您点击确定后提交信息成功后,我们的业务员将会立即 给您致电,显示的号码为130中国联通手机号码,请认真填写您的真实信息,本次呼叫不会收取您的任何费用!</p></div>',
        buttons: [{
            text: '确定拨打',
            handler: function() {
                phone_formp.getForm().submit({
                    method: 'POST',
                    waitMsg: '正在呼叫.....',
                    success: function() {
                        Ext.Msg.alert('Status', '拨打成功!', 
                        function(btn, text) {
                            phone_formp.getForm().reset();
                        });


                    },
                    failure: function(call_me_from, action) {
                        if (action.failureType == 'server') {
                            obj = Ext.util.JSON.decode(action.response.responseText);
                            Ext.Msg.alert('状态', obj.errors.reason);
                        }
                        else {
                            Ext.Msg.alert('Warning!', 'Authentication server is unreachable : ' + action.response.responseText + "abcd");
                        }
                    }
                });
            }
        },{
            text: '重新填写',
            id: 'clear',
            handler: function() {
                phone_form.getForm().reset();
            }
        }]
});


EzDesk.Uphone.phone_p = Ext.extend(Ext.Panel,{
        frame: true,
        layout: 'border',
        bodyStyle: 'padding:5px 5px 0;font-size:14px',
        items: [{
            xtype: 'panel',
            region: 'center',
            width: 250,
            height: 300,
            frame: true,
            autoScroll: true,
			html:'<div style=" font-size:12px;"><h1>隐私声明</h1>优通国际非常重视对用户隐私权的保护，承诺不会在未获得用户许可的情况下擅自将用户的个人资料信息出租或出售给任何第三方，但以下情况除外:<br/>'+
			'您同意让第三方共享资料；<br/>'+
			'您同意公开你的个人资料，享受为您提供的产品和服务；<br/>'+
			'本站需要听从法庭传票、法律命令或遵循法律程序 ；<br/>'+
			'本站发现您违反了本站服务条款或本站其它使用规定。<div>'

        },
        new EzDesk.Uphone.phone_formp({})]

 });
*/
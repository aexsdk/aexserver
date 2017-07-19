/**
 * @author cilun
 * 定义命名空间
 */
//路由测试-Testing
Ext.namespace("EzDesk");
EzDesk.RouterTestingPanel = Ext.extend(Ext.Panel, {
    //width: 779,
    //height: 530,
    layout: 'fit',
    baseUrl : '',
	moduleId : '',
	desktop : '',
    initComponent: function() {
        this.items = [
            {
                xtype: 'form',
                labelWidth: 120,
                labelAlign: 'left',
				id: 'addAgentForm',
                layout: 'form',
                width: 479,
				items: [{
	                xtype: 'textfield',
	                fieldLabel: '主叫号码:',
	                anchor: '100%',
	                name: 'Agent_Name'
	            }, {
	                xtype: 'textfield',
	                fieldLabel: '被叫号码：',
	                anchor: '100%',
	                name: 'Caption'
	            },{
	                xtype: 'textfield',
	                fieldLabel: '透传号码：',
	                anchor: '100%',
	                name: 'SuperiorAgnet'
	            },{
	                xtype: 'textfield',
	                fieldLabel: '呼叫顺序：',
	                anchor: '100%',
	                name: 'EMail'
	            }]
            }
        ];
		this.fbar = {
            xtype: 'toolbar',
            items: [{
                xtype: 'button',
                text: lang_route.Close
            }, {
                xtype: 'button',
                text: lang_route.Save,
                handler: function(){
                    Ext.getCmp('addAgentForm').getForm().submit({
                        url: app.connection,
                        waitMsg: 'Loading',
                        method: 'POST',
                        params: {
                            action: 'ani_edit',
                            moduleId: moduleId
                        },
                        success: function(addUserForm, action){
                            //b.setDisabled(false);	
                            var obj = Ext.util.JSON.decode(action.response.responseText);
                          	EzDesk.addOperatorMsg(app, moduleId);
                        },
                        failure: function(addUserForm, action){
                            //bbtn.setDisabled(false);
                            obj = Ext.util.JSON.decode(action.response.responseText);
                            if (action.failureType == 'server') {
								EzDesk.addOperatorMsg(app, moduleId);
                               // EzDesk.showMsg('ANI Manage', obj.message, desktop);
                            }
                            else {
								EzDesk.addOperatorMsg(app, moduleId);
                              //  EzDesk.showMsg('ANI Manage', obj.message, desktop);
                            }
                        }
                    });
                }
            }]
        };
		EzDesk.RouterTestingPanel.superclass.initComponent.call(this);
    }
});
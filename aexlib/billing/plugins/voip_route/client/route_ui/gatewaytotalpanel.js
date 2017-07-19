/**
 * @author cilun
 */
/**
 * 定义命名空间
 */
Ext.namespace("EzDesk");

//落地网关统计信息
EzDesk.GateWayTotalPanel = Ext.extend(EzDesk.CrudPanel, {
    //id，需唯一
    id: "gatewaytotalPanel",
    //标题
    title: "落地网关统计信息",
    baseUrl: "",
    //模块名  
    moduleId: "",
    desktop: "",
    store: "", //数据源
    initComponent: function(){
		this.store = new Ext.data.JsonStore({
			 id: "id",
			 url: this.baseUrl, //默认的数据源地址，继承时需要提供
			 root: "data",
			 totalProperty: "totalCount",
			 remoteSort: true,
			 baseParams: {
	 				method: 'get_gateway_total',
	 				moduleId: this.moduleId
	 		 },
			 fields: ["id", "qos", "choice_times","ip"]
		});
		 
        var sm = new Ext.grid.CheckboxSelectionModel();
        this.cm = new Ext.grid.ColumnModel([new Ext.grid.RowNumberer(),//获得行号
 		sm, {
            header: "路由名称",
            sortable: true,
            width: 210,
            dataIndex: "id"
        },{
            header: "路由IP",
            sortable: true,
            width: 210,
            dataIndex: "ip"
        }, {
            header: "质量",
            sortable: true,
            width: 100,
            dataIndex: "qos"
        }, {
            header: "拨打次数",
            sortable: true,
            width: 180,
            dataIndex: "choice_times"
        }]);
        EzDesk.GateWayTotalPanel.superclass.initComponent.call(this);
    }
});

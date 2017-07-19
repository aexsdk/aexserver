/**
 * @author cilun
 */
/**
 * 定义命名空间
 */
Ext.namespace("EzDesk");

//回拨服务器统计信息
EzDesk.DialServerTotalPanel = Ext.extend(EzDesk.CrudPanel, {
    //id，需唯一
    id: "dialservertotalPanel",
    //标题
    title: "回拨服务器统计信息",
    //数据源
    baseUrl: "",
    //模块名  
    moduleId: "",
    desktop: "",
    store: "",
    storeMapping: ["id", "bandwidth", "call_tiems", "call_quality"],
    initComponent: function(){
		 this.store = new Ext.data.JsonStore({
	         id: "id",
	         url: this.baseUrl, //默认的数据源地址，继承时需要提供
	         root: "data",
	         totalProperty: "totalCount",
	         remoteSort: true,
	         fields: this.storeMapping,
	         baseParams: {
 				method: 'get_dialserver_total',
 				moduleId: this.moduleId
 			 }
	     });
	 
        var sm = new Ext.grid.CheckboxSelectionModel();
        this.cm = new Ext.grid.ColumnModel([new Ext.grid.RowNumberer(),//获得行号
 		sm, {
            header: "服务器ID",
            sortable: true,
            width: 180,
            dataIndex: "id"
        }, {
            header: "带宽",
            sortable: true,
            width: 200,
            dataIndex: "bandwidth"
        }, {
            header: "呼叫质量",
            sortable: true,
            width: 250,
            dataIndex: "call_quality"
        }, {
            header: "呼叫次数",
            sortable: true,
            width: 180,
            dataIndex: "call_tiems"
        }]);
        EzDesk.DialServerTotalPanel.superclass.initComponent.call(this);
    }
});

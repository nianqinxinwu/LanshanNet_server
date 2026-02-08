define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'xiluxc/brand/shop_service/index' + location.search,
                    add_url: 'xiluxc/brand/shop_service/add',
                    edit_url: 'xiluxc/brand/shop_service/edit',
                    del_url: 'xiluxc/brand/shop_service/del',
                    multi_url: 'xiluxc/brand/shop_service/multi',
                    dragsort_url: '',
                    table: 'xiluxc_shop_service',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                fixedColumns: true,
                fixedRightNumber: 1,
                search:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'shop.name', title: __('Shop_id'),operate:"LIKE"},
                        {field: 'brand.brand_name', title: __('Brand_id'),operate:"LIKE"},
                        {field: 'service.name', title: __('Service_id'),operate:"LIKE"},
                        {field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'salesprice', title: __('Salesprice'), operate:false},
                        {field: 'vip_price', title: __('Vip_price'), operate:false},
                        {field: 'status', title: __('Status'), searchList: {"hidden":__('Status hidden'),"normal":__('Status normal')}, formatter: Table.api.formatter.status},
                        {field: 'weigh', title: __('Weigh'), operate: false},
                        {field: 'sales', title: __('Sales'),operate: false,sortable:true},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        selects: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'xiluxc/brand/shop_service/selects' + location.search,
                    dragsort_url: "",
                    table: 'xiluxc_shop_service',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                fixedColumns: true,
                fixedRightNumber: 1,
                maintainSelected: true,
                //pageSize:1,
                search: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        //{field: 'shop_id', title: __('Shop_id'),visible:false},
                        //{field: 'shop.name', title: __('Shop_id'),operate:false},
                        {field: 'service.name', title: __('Service_id'),operate:"LIKE"},
                        {field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'salesprice', title: __('Salesprice'), operate:false},
                        {field: 'vip_price', title: __('Vip_price'), operate:false},
                        {field: 'status', title: __('Status'), searchList: {"hidden":__('Status hidden'),"normal":__('Status normal')}, formatter: Table.api.formatter.status},
                        {field: 'weigh', title: __('Weigh'), operate: false},
                        {field: 'sales', title: __('Sales'),operate:false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 选中多个
            $(document).on("click", ".btn-choose-multi", function () {
                var data =  Table.api.selecteddata(table);
                if(data.length<=0){
                    Toastr.error('请选择至少一个');
                    return false;
                }
                Fast.api.close({data: data});
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
            var options = table.bootstrapTable('getOptions');
            var selectedIds = [];
            if (new URLSearchParams(location.search).get('ids')) {
                selectedIds = new URLSearchParams(location.search).get('ids').split(',');
                for (var i=0;i<selectedIds.length;i++){
                    selectedIds[i] = parseInt(selectedIds[i]);
                }
                options.selectedIds = selectedIds;
                options.selectedData = Config.selectdata;
            }

        },
        api: {
            bindevent: function () {
                $("#c-service_id").data("params", function (obj) {
                    //obj为SelectPage对象
                    return {custom: {shop_id: $("#c-shop_id").val(),service_id: ["neq",$("#c-service_id").val()]}};
                });
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

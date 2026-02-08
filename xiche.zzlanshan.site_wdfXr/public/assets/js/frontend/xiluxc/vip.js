define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'xiluxc/vip/index' + location.search,
                    add_url: 'xiluxc/vip/add',
                    edit_url: 'xiluxc/vip/edit',
                    del_url: 'xiluxc/vip/del',
                    multi_url: 'xiluxc/vip/multi',
                    dragsort_url: '',
                    table: 'xiluxc_shop_vip',
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
                search: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        //{field: 'shop.name', title: __('Shop_id'),operate:"LIKE"},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'salesprice', title: __('Salesprice'), operate:'BETWEEN'},
                        {field: 'original_price', title: __('Original_price'), operate:'BETWEEN'},
                        {field: 'days', title: __('Days')+'（天）', operate:false},
                        {field: 'status', title: __('Status'), searchList: {"hidden":__('Status hidden'),"normal":__('Status normal')}, formatter: Table.api.formatter.status},
                        {field: 'weigh', title: __('Weigh'), operate: false},
                        {field: 'sales', title: __('Sales'), operate: false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        //{field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                        {field: 'operate', title: __('Operate'),buttons: [
                                {
                                    name: 'edit',
                                    text: __(''),
                                    title: __('编辑'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: function (row) {
                                        return $.fn.bootstrapTable.defaults.extend.edit_url+'/ids/'+row.id;
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return Config.brand || Config.shop.type == 1;
                                    }
                                },
                                {
                                    name: 'del',
                                    text: __(''),
                                    title: __('删除'),
                                    classname: 'btn btn-xs btn-danger btn-del btn-ajax',
                                    icon: 'fa fa-trash',
                                    url: function (row) {
                                        return $.fn.bootstrapTable.defaults.extend.del_url+'/ids/'+row.id;
                                    },
                                    confirm: '确认删除',
                                    refresh: true,
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return Config.brand || Config.shop.type == 1;
                                    }
                                }
                            ], table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

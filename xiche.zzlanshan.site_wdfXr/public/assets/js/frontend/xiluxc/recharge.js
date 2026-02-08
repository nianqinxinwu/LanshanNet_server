define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'xiluxc/recharge/index' + location.search,
                    add_url: 'xiluxc/recharge/add',
                    edit_url: 'xiluxc/recharge/edit',
                    del_url: 'xiluxc/recharge/del',
                    multi_url: 'xiluxc/recharge/multi',
                    dragsort_url: '',
                    table: 'xiluxc_recharge',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                search: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'money', title: __('Money')},
                        {field: 'extra_money', title: __('Extra_money'), operate: false},
                        {field: 'weigh', title: __('Weigh'), operate: false},
                        {field: 'status', title: __('Status'), searchList: {"hidden":__('Status hidden'),"normal":__('Status normal')}, formatter: Table.api.formatter.status},
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

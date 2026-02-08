define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'xiluxc/shop/index' + location.search,
                    add_url: 'xiluxc/shop/add',
                    edit_url: 'xiluxc/shop/edit',
                    del_url: 'xiluxc/shop/del',
                    multi_url: 'xiluxc/shop/multi',
                    dragsort_url: '',
                    service_url: 'xiluxc/shop_service/index',
                    table: 'xiluxc_shop',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user.mobile', title: __('手机号'),operate: "LIKE"},
                        {field: 'name', title: __('Name'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'connector', title: __('Connector'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'concat_mobile', title: __('Concat_mobile'), operate: 'LIKE'},
                        {field: 'address', title: __('Address'), operate: 'LIKE'},
                        {field: 'images', title: __('Images'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.images},
                        {field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'starttime', title: __('Starttime')},
                        {field: 'endtime', title: __('Endtime')},
                        {field: 'status', title: __('Status'), searchList: {"hidden":__('Status hidden'),"normal":__('Status normal')}, formatter: Table.api.formatter.status},
                        {field: 'account.rate', title: __('Rate'), operate: false},
                        {field: 'account.vip_rate', title: __('会员费率'), operate: false},
                        {field: 'account.total_money', title: __('Total_money'), operate:'BETWEEN'},
                        {field: 'account.money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'account.withdraw_money', title: __('Withdraw_money'), operate:'BETWEEN'},
                        {field: 'audit_status', title: __('Audit_status'), searchList: {"checked":__('Audit_status checked'),"passed":__('Audit_status passed'),"failed":__('Audit_status failed')}, formatter: Table.api.formatter.status},
                        {field: 'refuse_reason', title: __('Refuse_reason'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'audittime', title: __('Audittime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        //{field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'),buttons: [
                                {
                                    name: 'shop_service',
                                    text: __('门店服务'),
                                    title: __('门店服务'),
                                    classname: 'btn btn-xs btn-warning btn-dialog',
                                    icon: 'fa fa-list',
                                    url: function (row) {
                                        return Fast.api.fixurl($.fn.bootstrapTable.defaults.extend.service_url+'?shop.name='+row.name)
                                    },
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

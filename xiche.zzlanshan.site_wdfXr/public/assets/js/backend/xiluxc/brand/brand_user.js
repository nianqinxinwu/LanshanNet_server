define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'xiluxc/brand/brand_user/index' + location.search,
                    add_url: 'xiluxc/brand/brand_user/add',
                    edit_url: 'xiluxc/brand/brand_user/edit',
                    del_url: 'xiluxc/brand/brand_user/del',
                    multi_url: 'xiluxc/brand/brand_user/multi',
                    shop_url: 'xiluxc/brand/shop/index',
                    table: 'xiluxc_user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'user.id', title: __('Id')},
                        {field: 'brand.brand_name', title: __('Brand_name'), operate: 'LIKE'},
                        {field: 'brand.logo', title: __('Logo'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'user.mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'group_type', title: __('Group_type'), searchList: {"1":__('Group_type 1'),"2":__('Group_type 2'),"3":__('Group_type 3')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), buttons: [
                                {
                                    name: 'shop',
                                    text: __('门店列表'),
                                    dropdown: '更多操作',
                                    title: __('门店列表'),
                                    classname: 'btn btn-xs btn-dialog',
                                    icon: 'fa fa-list',
                                    url: function (row) {
                                        return Fast.api.fixurl($.fn.bootstrapTable.defaults.extend.shop_url+'?brand_id='+row.user_id)
                                    },
                                }
                            ],table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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

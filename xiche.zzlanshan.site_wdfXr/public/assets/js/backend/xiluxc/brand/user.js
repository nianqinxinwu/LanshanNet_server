define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'xiluxc/brand/user/index' + location.search,
                    add_url: 'xiluxc/brand/user/add',
                    edit_url: 'xiluxc/brand/user/edit',
                    del_url: 'xiluxc/brand/user/del',
                    multi_url: 'xiluxc/brand/user/multi',
                    vip_url: 'xiluxc/brand/user/uservip',
                    package_url: 'xiluxc/brand/user/package',
                    order_url: 'xiluxc/order/order/index',
                    moneylog_url: 'xiluxc/finance/money_log/index',
                    table: 'xiluxc_user',
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
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'username', title: __('Username'), operate: 'LIKE'},
                        {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'avatar', title: __('Avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'account.money', title: __('余额'), operate: 'BETWEEN', sortable: true},
                        {field: 'account.total_money', title: __('累计佣金'), operate: 'BETWEEN', sortable: true},
                        {field: 'account.first_username', title: __('直属用户'), operate: 'LIKE'},
                        {field: 'account.second_username', title: __('间属用户'), operate: 'LIKE'},
                        {field: 'gender', title: __('Gender'), visible: false, searchList: {1: __('Male'), 0: __('Female')}},
                        {field: 'maxsuccessions', title: __('Maxsuccessions'), visible: false, operate: 'BETWEEN', sortable: true},
                        {field: 'logintime', title: __('Logintime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'jointime', title: __('Jointime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.status, searchList: {normal: __('Normal'), hidden: __('Hidden')}},
                        {field: 'operate', title: __('Operate'),buttons: [
                                {
                                    name: 'userpackage',
                                    title: __('服务套餐'),
                                    text: __('服务套餐'),
                                    dropdown: '更多操作',
                                    classname: 'btn btn-xs btn-dialog',
                                    icon: 'fa',
                                    url: function (row) {
                                        return Fast.api.fixurl($.fn.bootstrapTable.defaults.extend.package_url+'?user_id='+row.id)
                                    },
                                },
                                {
                                    name: 'uservip',
                                    title: __('会员卡'),
                                    text: __('会员卡'),
                                    dropdown: '更多操作',
                                    classname: 'btn btn-xs btn-dialog',
                                    icon: 'fa',
                                    url: function (row) {
                                        return Fast.api.fixurl($.fn.bootstrapTable.defaults.extend.vip_url+'?user_id='+row.id)
                                    },
                                },
                                {
                                    name: 'order',
                                    title: __('服务订单'),
                                    text: __('服务订单'),
                                    dropdown: '更多操作',
                                    classname: 'btn btn-xs btn-dialog',
                                    icon: 'fa',
                                    url: function (row) {
                                        return Fast.api.fixurl($.fn.bootstrapTable.defaults.extend.order_url+'?user_id='+row.id)
                                    },
                                },
                                {
                                    name: 'moneylog',
                                    title: __('收入日志'),
                                    text: __('收入日志'),
                                    dropdown: '更多操作',
                                    classname: 'btn btn-xs btn-dialog',
                                    icon: 'fa',
                                    url: function (row) {
                                        return Fast.api.fixurl($.fn.bootstrapTable.defaults.extend.moneylog_url+'?user_id='+row.id)
                                    },
                                }
                            ] , table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
        uservip: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'xiluxc/brand/user/uservip' + location.search,
                    add_url: '',
                    edit_url: '',
                    del_url: '',
                    multi_url: '',
                    table: 'xiluxc_user_shop_vip',
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
                search: false,
                showExport: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'user_id', title: __('Id'), visible: false},
                        {field: 'shop.name', title: __('门店'), operate: false},
                        {field: 'brand.brand_name', title: __('品牌'), operate: false},
                       // {field: 'vip.name', title: __('会员卡'), operate: 'LIKE'},
                        {field: 'expire_in', title: __('到期时间'), datetimeFormat: 'YYYY-MM-DD',formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
                        //{field: 'operate', title: __('Operate') , table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        package: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'xiluxc/brand/user/package' + location.search,
                    add_url: '',
                    edit_url: '',
                    del_url: '',
                    multi_url: '',
                    table: 'xiluxc_user_package',
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
                search: false,
                showExport: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'user_id', title: __('Id'), visible: false},
                        {field: 'shop.name', title: __('门店'), operate: false},
                        {field: 'brand.brand_name', title: __('品牌'), operate: false},
                        {field: 'package_name', title: __('套餐名'), operate: 'LIKE'},
                       // {field: 'expire_in', title: __('到期时间'), datetimeFormat: 'YYYY-MM-DD',formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
                        //{field: 'operate', title: __('Operate') , table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ],

                detailView: true,
                detailFormatter: function(index, row){
                    return	'<table class="table table-striped table-bordered table-hover table-nowrap view-table" id="view-table-' + row.id + '">' +
                        '<thead>' +
                        '<tr style="background:#f9f9f9;">' +
                        // '<tr>' +
                        // // '<th style="text-align:center" colspan="7"><div>参与商品列表</div></th>' +
                        // '</tr>' +
                        // '<tr>' +
                        "<th style='text-align:center;width: 73px' ></th>"+
                        '<th style="text-align:center;width: 271px"><div>服务</div></th>' +
                        '<th style="text-align:center;width: 271px"><div>规格名</div></th>' +
                        '<th style="text-align:center;width: 303px"><div>总次数</div></th>' +
                        '<th style="text-align:center;width: 303px"><div>剩余次数</div></th>' +

                        '<th style="text-align:center"><div>已使用次数</div></th>' +
                        //'<th style="text-align:center"><div>状态</div></th>' +
                        '</tr>' +
                        '</thead>' +
                        '<tbody>' +
                        (
                            row.package_service.map(function(service){
                                return	'<tr>' +
                                    '<td></td>'+
                                    '<td style="text-align:left;width: 271px""><div style="white-space: pre-line;display: flex;align-items: center;height:100%;" ><img width="50px" style="display:block;margin-right:7px;object-fit: contain;"  src="' + Fast.api.cdnurl(service.service_image) + '">' + '<div style="flex:1;" >' + (service.service_name) + '</div>'  + '</div></td>' +
                                    '<td style="text-align:center;line-height:47px"><div>' + service.service_price_name + '</div></td>' +
                                    '<td style="text-align:center;line-height:47px">' + service.total_count + '</td>' +
                                    '<td style="text-align:center;line-height:47px">' + service.stock + '</td>' +
                                    '<td style="text-align:center;line-height:47px">' + service.use_count + '</td>' +
                                    '</tr>';
                            }).join('')
                        ) +
                        '</tbody>'

                },


            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

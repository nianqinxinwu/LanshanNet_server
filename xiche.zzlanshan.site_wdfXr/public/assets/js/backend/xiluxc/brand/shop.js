define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'xiluxc/brand/shop/index' + location.search,
                    add_url: 'xiluxc/brand/shop/add',
                    edit_url: 'xiluxc/brand/shop/edit',
                    del_url: 'xiluxc/brand/shop/del',
                    multi_url: 'xiluxc/brand/shop/multi',
                    dragsort_url: '',
                    service_url: 'xiluxc/brand/shop_service/index',
                    package_url: 'xiluxc/brand/package/index',
                    recharge_url: 'xiluxc/brand/recharge/index',
                    vip_url: 'xiluxc/brand/vip/index',
                    comment_url: 'xiluxc/comment/service_comment/index',
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
                        {field: 'brand_id', title: __('Brand_id'),searchList: Config.brands, formatter: function(val,row,index){
                               if(val>0){
                                   return Table.api.formatter.normal.call(this, val,row,index);
                               }else{
                                   return '-';
                               }

                            }},
                        {field: 'connector', title: __('Connector'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2')}, formatter: Table.api.formatter.normal},
                        {field: 'status', title: __('Status'), searchList: {"hidden":__('Status hidden'),"normal":__('Status normal')}, formatter: Table.api.formatter.status},
                        {field: 'audit_status', title: __('Audit_status'), searchList: {"checked":__('Audit_status checked'),"passed":__('Audit_status passed'),"failed":__('Audit_status failed')},formatter:function(val,row,index){
                                var a = '<br /><a href="'+Fast.api.fixurl('xiluxc/brand/shop/audit/ids/'+row.id)+'" class="btn btn-success btn-xs btn-dialog" data-title="资料">'+(val=='checked'?'去审核':'查看')+'</a>';
                                return Table.api.formatter.status.call(this, val,row,index) + a;
                            }},
                        {field: 'concat_mobile', title: __('Concat_mobile'), operate: 'LIKE'},
                        {field: 'address', title: __('Address'), operate: 'LIKE'},
                        {field: 'images', title: __('Images'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.images},
                        {field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'starttime', title: __('Starttime'), operate: false},
                        {field: 'endtime', title: __('Endtime'), operate: false},
                        {field: 'point', title: __('Point'), operate: false},
                        {field: 'account.rate', title: __('Rate'), operate: false},
                        {field: 'account.total_money', title: __('Total_money'), operate:'BETWEEN'},
                        {field: 'account.money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'account.withdraw_money', title: __('Withdraw_money'), operate:'BETWEEN'},
                        {field: 'refuse_reason', title: __('Refuse_reason'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'audittime', title: __('Audittime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        //{field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'),align:"left",buttons: [
                                {
                                    name: 'shop_service',
                                    text: __('门店服务'),
                                    title: __('门店服务'),
                                    dropdown: '更多操作',
                                    classname: 'btn btn-xs btn-dialog',
                                    icon: 'fa fa-list',
                                    url: function (row) {
                                        if(row.type == '2'){
                                            let brands = Config.brands;
                                            return Fast.api.fixurl($.fn.bootstrapTable.defaults.extend.service_url+'?brand.brand_name='+brands[row.brand_id])
                                        }else{
                                            return Fast.api.fixurl($.fn.bootstrapTable.defaults.extend.service_url+'?shop.name='+row.name)
                                        }

                                    },
                                },
                                {
                                    name: 'package',
                                    text: __('门店套餐'),
                                    title: __('门店套餐'),
                                    dropdown: '更多操作',
                                    classname: 'btn btn-xs btn-dialog',
                                    icon: 'fa fa-list',
                                    url: function (row) {
                                        if(row.type == '2'){
                                            let brands = Config.brands;
                                            return Fast.api.fixurl($.fn.bootstrapTable.defaults.extend.package_url+'?brand.brand_name='+brands[row.brand_id])
                                        }else{
                                            return Fast.api.fixurl($.fn.bootstrapTable.defaults.extend.package_url+'?shop.name='+row.name)
                                        }
                                    },
                                },
                                {
                                    name: 'recharge',
                                    text: __('充值套餐'),
                                    title: __('充值套餐'),
                                    dropdown: '更多操作',
                                    classname: 'btn btn-xs btn-dialog',
                                    icon: 'fa fa-list',
                                    url: function (row) {
                                        if(row.type == '2'){
                                            let brands = Config.brands;
                                            return Fast.api.fixurl($.fn.bootstrapTable.defaults.extend.recharge_url+'?brand.brand_name='+brands[row.brand_id])
                                        }else{
                                            return Fast.api.fixurl($.fn.bootstrapTable.defaults.extend.recharge_url+'?shop.name='+row.name)
                                        }
                                    },
                                },
                                {
                                    name: 'vip',
                                    text: __('会员卡'),
                                    title: __('会员卡'),
                                    dropdown: '更多操作',
                                    classname: 'btn btn-xs btn-dialog',
                                    icon: 'fa fa-list',
                                    url: function (row) {
                                        if(row.type == '2'){
                                            let brands = Config.brands;
                                            return Fast.api.fixurl($.fn.bootstrapTable.defaults.extend.vip_url+'?brand.brand_name='+brands[row.brand_id])
                                        }else{
                                            return Fast.api.fixurl($.fn.bootstrapTable.defaults.extend.vip_url+'?shop.name='+row.name)
                                        }
                                    },
                                },
                                {
                                    name: 'comment',
                                    text: __('评价管理'),
                                    title: __('评价管理'),
                                    dropdown: '更多操作',
                                    classname: 'btn btn-xs btn-dialog',
                                    icon: 'fa fa-list',
                                    url: function (row) {
                                        return Fast.api.fixurl($.fn.bootstrapTable.defaults.extend.comment_url+'?shop.name='+row.name)
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
        audit: function () {
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

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'xiluxc/finance/withdraw/index' + location.search,
                    add_url: '',
                    edit_url: 'xiluxc/finance/withdraw/edit',
                    del_url: 'xiluxc/finance/withdraw/del',
                    multi_url: '',
                    processing_url: 'xiluxc/withdraw/processing',
                    refuse_url: 'xiluxc/withdraw/refuse',
                    table: 'xiluxc_withdraw',
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
                search:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user.nickname', title: __('User_id'), operate: 'LIKE'},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'real_money', title: __('Real_money'), operate:'BETWEEN'},
                        {field: 'rate', title: __('Rate'), operate:'BETWEEN'},
                        {field: 'rate_money', title: __('Rate_money'), operate:'BETWEEN'},
                        {field: 'state', title: __('State'), searchList: {"1":__('State 1'),"2":__('State 2'),"3":__('State 3'),"4":__('State 4')}, formatter: Table.api.formatter.normal},
                        {field: 'reason', title: __('Reason'), operate: 'LIKE'},
                        {field: 'certificate', title: __('Certificate'), operate: 'LIKE'},
                        {field: 'order_no', title: __('Order_no'), operate: 'LIKE'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), buttons: [
                                {
                                    name: 'processing',
                                    title: __('处理中'),
                                    text: __('处理中'),
                                    classname: 'btn btn-xs btn-warning btn-ajax',
                                    icon: '',
                                    confirm: '确认设置为处理中？',
                                    url: 'xiluxc/finance/withdraw/processing',
                                    success: function (data, ret) {
                                        //Layer.success(ret.msg);
                                        table.bootstrapTable('refresh',{});
                                        //如果需要阻止成功提示，则必须使用return false;
                                        // return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        return row.state == 1;
                                    }
                                },
                                {
                                    name: 'edit',
                                    title: __('标记已处理'),
                                    text: __('标记已处理'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: '',
                                    url: $.fn.bootstrapTable.defaults.extend.edit_url,
                                    visible: function (row) {
                                        return row.state == 2 || row.state == 1;
                                    }
                                },
                                {
                                    name: 'ok',
                                    title: __('拒绝'),
                                    text: __('拒绝'),
                                    classname: 'btn btn-xs btn-danger btn-click',
                                    icon: '',
                                    click: function (data,row) {
                                        Layer.prompt({
                                            formType: 2,
                                            value: '',
                                            title: '拒绝理由',
                                            //area: ['80px', '30px'] //自定义文本域宽高
                                        },function (value,index) {
                                            value = value.trim();
                                            if(!value) {
                                                Toastr.error('输入备注信息');
                                                return false;
                                            }
                                            Fast.api.ajax({
                                                url: 'xiluxc/finance/withdraw/refuse/ids/'+row.id,
                                                data: {reason:value},
                                            },function () {
                                                Layer.close(index);
                                                table.bootstrapTable('refresh',{});
                                            })
                                        })
                                    },
                                    visible: function (row) {
                                        return row.state == 2 || row.state == 1;
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

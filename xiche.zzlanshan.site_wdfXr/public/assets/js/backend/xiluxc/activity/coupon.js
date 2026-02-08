define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'xiluxc/activity/coupon/index' + location.search,
                    add_url: 'xiluxc/activity/coupon/add',
                    edit_url: 'xiluxc/activity/coupon/edit',
                    del_url: 'xiluxc/activity/coupon/del',
                    multi_url: 'xiluxc/activity/coupon/multi',
                    coupon_url: 'xiluxc/activity/coupon/coupon_log',
                    table: 'xiluxc_coupon',
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
                        {field: 'shop.name', title: __('Shop_id'), operate: 'LIKE'},
                        {field: 'name', title: __('Name'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'use_start_time', title: __('Use_start_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime,datetimeFormat:"YYYY-MM-DD"},
                        {field: 'use_end_time', title: __('Use_end_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime,datetimeFormat:"YYYY-MM-DD"},
                        {field: 'max_count', title: __('Max_count')},
                        {field: 'at_least', title: __('At_least'), operate:'BETWEEN'},
                        // {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2')}, formatter: Table.api.formatter.normal},
                        // {field: 'discount', title: __('Discount')},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'freight_type', title: __('Freight_type'), searchList: {"1":__('Freight_type 1'),"2":__('Freight_type 2')}, formatter: Table.api.formatter.normal},
                        {field: 'range_type', title: __('Range_type'), searchList: {"1":__('Range_type 1'),"2":__('Range_type 2')}, formatter: Table.api.formatter.normal},
                        {field: 'range_status', title: __('Range_status'), searchList: {"0":__('Range_status 0'),"1":__('Range_status 1')}, formatter: Table.api.formatter.normal},
                        {field: 'status', title: __('Status'), searchList: {"hidden":__('Status hidden'),"normal":__('Status normal')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), buttons: [
                                {
                                    name: 'coupons',
                                    text: __('领取记录'),
                                    title: __('领取记录'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-list',
                                    url: function (row) {
                                        return Fast.api.fixurl($.fn.bootstrapTable.defaults.extend.coupon_url+'?coupon_id='+row.id)
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
        coupon_log: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'xiluxc/activity/coupon/coupon_log'+location.search,
                    table: 'xiluxc_user_coupon',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true,operate:false},
                        {field: 'coupon_id', title: __('优惠券id'),visible:false},
                        {field: 'user.nickname', title: __('Nickname'), operate: 'LIKE'},
                        {field: 'user.mobile', title: __('手机号'), operate: 'LIKE'},
                        {field: 'user.avatar', title: __('头像'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'use_status', title: __('Status'), formatter: Table.api.formatter.status, searchList: {'0': __('未使用'), '1': __('已使用')}},
                        {field: 'createtime', title: __('领取'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'usetime', title: __('使用时间'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        api: {
            daterangepicker: function (form) {
                //绑定日期时间元素事件
                if ($(".datetimerange2", form).length > 0) {
                    require(['bootstrap-daterangepicker'], function () {
                        var ranges = {};
                        var options = {
                            timePicker: false,
                            autoUpdateInput: false,
                            timePickerSeconds: true,
                            timePicker24Hour: true,
                            autoApply: true,
                            locale: {
                                format: 'YYYY-MM-DD HH:mm:ss',
                                customRangeLabel: __("Custom Range"),
                                applyLabel: __("Apply"),
                                cancelLabel: __("Clear"),
                            },
                            ranges: ranges,
                        };
                        var origincallback = function (start, end) {
                            $(this.element).val(start.format(this.locale.format) + " - " + end.format(this.locale.format));
                            $(this.element).trigger('change');
                        };
                        $(".datetimerange2", form).each(function () {
                            var callback = typeof $(this).data('callback') == 'function' ? $(this).data('callback') : origincallback;
                            $(this).on('apply.daterangepicker', function (ev, picker) {
                                callback.call(picker, picker.startDate, picker.endDate);
                            });
                            $(this).on('cancel.daterangepicker', function (ev, picker) {
                                $(this).val('').trigger('change');
                            });
                            $(this).daterangepicker($.extend(true, {}, options, $(this).data() || {}, $(this).data("daterangepicker-options") || {}));
                        });
                    });
                }
            },
            bindevent: function () {
                //切换使用模块选项
                $(document).on('click','.c-range_type input',function () {
                    $(".table tbody").html('');
                })
                //切换使用范围
                $(document).on('click','.c-range_status input',function () {
                    // $(".table tbody").html('');
                    let value = $(this).val();
                    console.log(value)
                    if(value == 0){
                        $("#range-key").removeClass("hide");
                    }else{
                        $("#range-key").addClass("hide");
                    }
                })
                //删除选择
                $(document).on('click','.btn-danger',function () {
                    $(this).parents('tr').remove();
                })
                //选择
                $(document).on('click','.btn-choose',function () {
                    let value = $(".c-range_type").find("input:checked").val();
                    var url = value == 2 ? 'xiluxc/brand/package/selects' : 'xiluxc/brand/shop_service/selects';
                    var items_ids_obj = $("[name^='row[items_ids]']");
                    var selectids = [];
                    for (var i=0;i<items_ids_obj.length;i++) {
                        selectids[i] = $(items_ids_obj[i]).val();
                    }
                    let shopId = $("#c-shop_id").val() || '';
                    if(!shopId){
                        Toastr.error("请先选择门店");
                        return;
                    }
                    Fast.api.open(url + "?shop_id="+shopId+"&ids="+selectids, __('Choose'), {
                        callback: function (data) {
                            var html = '';
                            for(let i=0;i<data.data.length;i++){
                                html += '<tr >';
                                html += '<input type="hidden" name="row[items_ids][]" value="'+data.data[i].id+'">';
                                html += '<td><img width="40px" src="'+Fast.api.cdnurl(data.data[i].image)+'" alt=""></td>';
                                html += '<td>'+(value==1?data.data[i].service.name:data.data[i].name)+'</td>';
                                html += '<td><button type="button" class="btn btn-danger" ><i class="fa fa-trash"></i></button></td>';
                                html += '</tr>';
                            }
                            $(".table tbody").html(html);
                        }
                    });
                })
                Form.api.bindevent($("form[role=form]"));
                Controller.api.daterangepicker($("form[role=form]"));
            }
        }
    };
    return Controller;
});

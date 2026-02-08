define(['jquery', 'bootstrap', 'backend', 'table', 'form','vue'], function ($, undefined, Backend, Table, Form,Vue) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'xiluxc/brand/package/index' + location.search,
                    add_url: 'xiluxc/brand/package/add',
                    edit_url: 'xiluxc/brand/package/edit',
                    del_url: 'xiluxc/brand/package/del',
                    multi_url: 'xiluxc/brand/package/multi',
                    dragsort_url: '',
                    table: 'xiluxc_shop_package',
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
                        {field: 'shop.name', title: __('Shop_id'), operate: 'LIKE'},
                        {field: 'brand.brand_name', title: __('Brand_id'),operate:"LIKE"},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'salesprice', title: __('Salesprice'), operate:false},
                        {field: 'vip_price', title: __('Vip_price'), operate:false},
                        {field: 'original_price', title: __('Original_price'), operate:false},
                        {field: 'status', title: __('Status'), searchList: {"hidden":__('Status hidden'),"normal":__('Status normal')}, formatter: Table.api.formatter.status},
                        {field: 'weigh', title: __('Weigh'), operate: false},
                        {field: 'sales', title: __('Sales'), operate: false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.form();
        },
        edit: function () {
            Controller.form();
        },
        form: function () {
            var vm = new Vue({
                el: "#app",
                data(){
                    return {
                        services:[]
                    }
                },
                mounted(){
                    console.log(Config.services)
                    this.services = Config.services || [];
                    Controller.api.bindevent();
                },
                methods: {
                    // 选择服务
                    chooseService(){
                        var that = this;
                        let shopId = $("#c-shop_id").val();
                        if(!shopId){
                            Toastr.error("请选择门店");
                            return;
                        }
                        let selectids = [];
                        that.services.forEach(item=>{
                            selectids.push(parseInt(item.id));
                        });
                        Fast.api.open( "xiluxc/brand/shop_service/selects?shop_id="+shopId+"&ids="+selectids.join(','), __('服务选择'), {
                            callback: function (data) {
                                let arr = []
                                for (let i = 0; i < data.data.length; i++) {
                                    if(!selectids.includes(data.data[i].id)){
                                        arr.push(data.data[i]);
                                    }
                                }
                                that.services = that.services.concat(arr);
                            }
                        });
                    },
                    delService(index){
                        this.services.splice(index,1);
                    }


                }
            })
        },

        selects: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'xiluxc/brand/package/selects' + location.search,
                    dragsort_url: "",
                    table: 'xiluxc_shop_package',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                search:false,
                fixedColumns: true,
                fixedRightNumber: 1,
                maintainSelected: true,
                showToggle: false,
                showExport: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'status', title: __('Status'), searchList: {"hidden":__('Status hidden'),"normal":__('Status normal')}, formatter: Table.api.formatter.status},
                        {field: 'image', title: __('图片'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},

                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', title: __('Operate'), width: 85, events: {
                                'click .btn-chooseone': function (e, value, row, index) {
                                    Fast.api.close({data: [row]});
                                },
                            }, formatter: function () {
                                return '<a href="javascript:;" class="btn btn-danger btn-chooseone btn-xs"><i class="fa fa-check"></i> ' + __('Choose') + '</a>';
                            }
                        }
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
                $(document).on("change", "#c-shop_id", function(){
                    //后续操作
                    $('.form-inline .selectpage').selectPageClear();
                });

                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

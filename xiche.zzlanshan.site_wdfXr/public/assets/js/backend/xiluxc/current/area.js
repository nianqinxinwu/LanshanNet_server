define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'xiluxc/current/area/index' + location.search,
                    add_url: 'xiluxc/current/area/add',
                    edit_url: 'xiluxc/current/area/edit',
                    del_url: 'xiluxc/current/area/del',
                    multi_url: 'xiluxc/current/area/multi',
                    table: 'xiluxc_area',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'asc',
                fixedColumns: true,
                fixedRightNumber: 1,
                search: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'pid', title: __('Pid'),visible:false},
                        {field: 'shortname', title: __('Shortname'),operate:false},
                        {field: 'name', title: __('Name'),operate:'LIKE'},
                        {field: 'mergename', title: __('Mergename'),operate:false},
                        {field: 'level', title: __('Level'), searchList: {"0":__('Level 0'),"1":__('Level 1'),"2":__('Level 2'),"3":__('Level 3'),"4":__('Level 4')}, formatter: Table.api.formatter.normal,operate:false},
                        //  {field: 'pinyin', title: __('Pinyin'),operate:'LIKE',visible:false},
                        //{field: 'code', title: __('Code'),operate:'LIKE',visible:false},
                        //{field: 'zip', title: __('Zip'),operate:'LIKE',visible:false},
                        {field: 'first', title: __('First'),operate: false},
                        //{field: 'lng', title: __('Lng'),operate:false},
                        //{field: 'lat', title: __('Lat'),operate:false},
                        {field: 'is_re', title: __('Is_re'), searchList: {"1":__('Is_re 1'),"2":__('Is_re 2')}, formatter: Table.api.formatter.normal},
                        // { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                        {field: 'status', title: __('Status'), searchList: {"normal":__('Status normal'),"hidden":__('Status hidden')}, formatter: Table.api.formatter.status},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [{
                                name: 'pid',
                                text: '区域',
                                title: '区域',
                                icon: 'fa fa-flash',
                                classname: 'btn btn-xs btn-info btn-dialog',
                                url: 'xiluxc/current/area/index/level/3'
                            }], formatter: Table.api.formatter.operate, formatter: function (value, row, index) {
                                var that = $.extend({}, this);
                                var buttons = that.buttons;
                                if(row.level==1){
                                    buttons[0].title='城市';
                                    buttons[0].text='城市';
                                    buttons[0].url='xiluxc/current/area/index?level=2&&pid='+row.id;
                                }else if(row.level==2){
                                    buttons[0].url='xiluxc/current/area/index?level=3&&pid='+row.id;
                                }else if(row.level==3){
                                    buttons[0].title='街道或镇';
                                    buttons[0].text='街道或镇';
                                    buttons[0].url='xiluxc/current/area/index?level=4&&pid='+row.id;
                                }else{
                                    buttons[0]=[];
                                }
                                that.buttons = buttons;
                                return Table.api.formatter.operate.call(that, value, row, index);
                            }}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'xiluxc/current/area/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name'), align: 'left'},
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '140px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'xiluxc/current/area/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'xiluxc/current/area/destroy',
                                    refresh: true
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
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

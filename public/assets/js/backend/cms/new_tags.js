define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cms/new_tags/index' + location.search,
                    add_url: 'cms/new_tags/add',
                    edit_url: 'cms/new_tags/edit',
                    del_url: 'cms/new_tags/del',
                    multi_url: 'cms/new_tags/multi',
                    import_url: 'cms/new_tags/import',
                    table: 'cms_tags_new',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {
                            field: 'channel_id',
                            title: __('Channel_id'),
                            visible: false,
                        },
                        {
                            field: 'channel.name',
                            title: __('Channel'),
                            operate: false,
                            formatter: function (value, row, index) {
                                return '<a href="javascript:;" class="searchit" data-field="channel_id" data-value="' + row.channel_id + '">' + value + '</a>';
                            }
                        },
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {
                            field: 'image',
                            title: __('Image'),
                            operate: false,
                            events: Table.api.events.image,
                            formatter: Table.api.formatter.image,
                            visible: false,
                        },
                        {field: 'seotitle', title: __('Seotitle'), operate: 'LIKE', visible: false},
                        {field: 'keywords', title: __('Keywords'), operate: 'LIKE', visible: false},
                        {field: 'description', title: __('Description'), operate: 'LIKE', visible: false},
                        {field: 'diyname', title: __('Diyname'), operate: 'LIKE',visible: false},
                        {
                            field: 'fullurl', title: __('Url'), formatter: function (value, row, index) {
                                return '<a href="' + value + '" target="_blank" class="btn btn-default btn-xs"><i class="fa fa-link"></i></a>';
                            }
                        },
                        {field: 'nums', sortable: true, title: __('Nums')},
                        {
                            field: 'weigh',
                            title: __('Weigh'),
                            formatter: function (value, row, index) {
                                return '<input type="text" class="form-control text-center text-weigh" data-id="' + row.id + '" value="' + value + '" style="width:50px;margin:0 auto;" />';
                            },
                            events: {
                                "dblclick .text-weigh": function (e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    return false;
                                }
                            }
                        },
                        {field: 'is_show_tags', title: __('Is_show_tags'), searchList: {"1": __('Yes'), "0": __('No')}, formatter: Table.api.formatter.toggle},
                        {field: 'pub_status', title: __('Pub_status'),formatter: Table.api.formatter.toggle},
                        {
                            field: 'createtime',
                            title: __('Createtime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'updatetime',
                            title: __('Updatetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            $(document).on("change", ".text-weigh", function () {
                $(this).data("params", {weigh: $(this).val()});
                Table.api.multi('', [$(this).data("id")], table, this);
                return false;
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
                url: 'cms/new_tags/recyclebin' + location.search,
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
                            width: '130px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'cms/new_tags/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'cms/new_tags/destroy',
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

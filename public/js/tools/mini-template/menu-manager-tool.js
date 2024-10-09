$(function () {
    var $body = $('body');
    var siteSelect = '#mini-template-menu-manager-site-select';
    var languageSelect = '.mini-template-menu-manager-lang-select li a';
    var form = '#id_menu_manager_tool_site';
    var tree = '#mini-template-category-tree';
    var isInitialized = false;
    var selectedNode = '';
    var locale = melisLangId;

    $body.on('click', '.mtpl-menu-plugins-tab', function () {
        $('.melis-mini-template-menu-manager-table-refresh').trigger('click');
    });

    // how to get selected node
    // $(tree).jstree().get_selected(true)[0].text
    $body.on('click', '.melis-mini-template-menu-manager-table-refresh', function () {
        melisHelper.zoneReload(
            'id_meliscms_mini_template_menu_manager_tool_add_category_body_plugins_table',
            'meliscms_mini_template_menu_manager_tool_add_category_body_plugins_table',
            {
                formType: 'edit'
            }
        );
    })

    $body.on('click', '.add-m-tpl-category', function () {
        $('#id_meliscms_mini_template_menu_manager_tool_add_category_container').removeClass('hidden');
        melisHelper.zoneReload(
            'id_meliscms_mini_template_menu_manager_tool_add_category_container',
            'meliscms_mini_template_menu_manager_tool_add_category_container',
            {
                isHidden: false
            },
            function () {
                $('#id_meliscms_mini_template_menu_manager_tool_header a').trigger("click");
            }
        );
    });

    $body.on('click', '.add-m-tpl-plugin', function () {
        melisHelper.tabOpen(translations.tr_meliscms_mini_template_manager_tool, 'fa fa-tasks', 'id_meliscms_mini_template_manager_tool', 'meliscms_mini_template_manager_tool');
        var alreadyOpen = $("body #melis-id-nav-bar-tabs li a.tab-element[data-id='id_meliscms_mini_template_manager_tool']");

        var checkTab = setInterval(function() {
            if (alreadyOpen.length) {
                $(".close.close-tab[data-id='new_template_id_meliscms_mini_template_manager_tool_add']").trigger("click");

                melisHelper.tabOpen(
                    translations.tr_meliscms_mini_template_manager_tool_header_add_btn,
                    'fa fa-list-alt',
                    'new_template_id_meliscms_mini_template_manager_tool_add',
                    'meliscms_mini_template_manager_tool_add',
                    {
                        templateName: 'new_template',
                        module: $(siteSelect).find('option:selected').val()
                    },
                    'id_meliscms_mini_template_manager_tool'
                );
                clearInterval(checkTab);
            }
        }, 500);
    });

    $body.on('click', '.close', function () {
        if ($(this).data('id') == 'id_meliscms_mini_template_menu_manager_tool') {
            isInitialized = false;
            locale = melisLangId;
        }
    });

    $body.on('click', '#close-all-tab', function () {
        isInitialized = false;
    });

    $body.on('change', siteSelect, function () {
        var value = $(this).val();
        $("#mini-template-tree-no-data").css("display","none");

        if (! $('#id_meliscms_mini_template_menu_manager_tool_add_category_container').hasClass('hidden')) {
            melisHelper.zoneReload(
                'id_meliscms_mini_template_menu_manager_tool_add_category_container',
                'meliscms_mini_template_menu_manager_tool_add_category_container',
                {},
                function () {}
            );
        }

        if (value != 0) {
            if (isInitialized) {
                $(tree).jstree(true).settings.core.data.data = [
                    {name: 'langlocale', value: $("#mini-template-category-tree").data('langlocale')},
                    {name: 'siteId', value: $(siteSelect).find('option:selected').data('id')}
                ];
                $(tree).jstree(true).refresh();
            } else {
                initCmsMiniTemplateCategoryTree();
                isInitialized = true;
            }
        } else {
            $(tree).jstree(true).destroy();
            isInitialized = false;

            $('.add-m-tpl-category').attr('disabled', 'disabled');
            $('.add-m-tpl-category').attr('title', translations.tr_meliscms_mini_template_menu_manager_select_site_first_btn_title);
            $('.add-m-tpl-plugin').attr('disabled', 'disabled');
            $('.add-m-tpl-plugin').attr('title', translations.tr_meliscms_mini_template_menu_manager_select_site_first_btn_title);
            $('.mini-template-menu-manager-lang').find('a').addClass('disabled');
            $('.mini-template-menu-manager-lang').find('a').attr('title', translations.tr_meliscms_mini_template_menu_manager_select_site_first_btn_title);
        }
    });

    $body.on('click', languageSelect, function () {
        var $this = $(this);
        var text = $this.text();
        // var locale = $this.data('locale');
        locale = $this.data('locale');

        $('.mini-template-menu-manager-lang a span.filter-key').text(text);
        $(tree).data('langlocale', locale);
        $(tree).jstree(true).settings.core.data.data = [{name : "langlocale", value: locale}, {name:"module", value : $(siteSelect).val()}];
        $(tree).jstree(true).refresh();
    });

    $body.on('click', '#id_meliscms_mini_template_menu_manager_save_btn', function () {
        var status = $('#mtpl-category-status').find('.switch-on');

        if (status.length) {
            status = 1;
        } else {
            status = 0;
        }

        var formData = $("#id_meliscms_mini_template_menu_manager_tool_add_category_body_properties_form form").serializeArray();

        formData.push({
            name: 'site_id',
            value: $(siteSelect).find('option:selected').data('id')
        });
        formData.push({
            name: 'cat_id',
            value: $('#menu-manager-category-id').data('id')
        });
        formData.push({
            name: 'status',
            value: status
        });
        formData.push({
            name: 'status',
            value: status
        });
        formData.push({
            name: 'currentLocale',
            value: melisLangId
        });

        melisCoreTool.pending('#id_meliscms_mini_template_menu_manager_save_btn');
        $.ajax({
            type: 'POST',
            url: '/melis/MelisCms/MiniTemplateMenuManager/saveCategory',
            data: formData
        }).done(function (data) {
            if (data.success) {
                $(tree).jstree(true).refresh();
                melisHelper.zoneReload(
                    'id_meliscms_mini_template_menu_manager_tool_add_category_container',
                    'meliscms_mini_template_menu_manager_tool_add_category_container',
                    {
                        isHidden: false,
                        id: data.id + '-' + data.categoryName,
                        formType: 'edit',
                        status: status
                    },
                    function () {
                        if ($('#id_meliscms_mini_template_menu_manager_tool_header a').hasClass('collapsed'))
                            $('#id_meliscms_mini_template_menu_manager_tool_header a').trigger("click");
                    }
                );

                melisHelper.melisOkNotification(data.textTitle, data.textMessage);
            } else {
                var errors = {};
                $.each(data.errors, function (key, value) {
                    if (value.error === translations.tr_meliscms_mini_template_error_category_atleast_one_provided) {
                        errors[key] = value;
                        return false;
                    } else {
                        errors[key] = value;
                    }
                });
                melisHelper.melisKoNotification(translations.tr_meliscms_mini_template_menu_manager_save_category, '', errors);

                var formData = $("#id_meliscms_mini_template_menu_manager_tool_add_category_body_properties_form form").serializeArray();
                var form_id = '_id_menu_manager_tool_site_add_category';
                var tab = '-mini-template-menu-manager-category';

                if (typeof $('#menu-manager-category-id').data('id') !== 'undefined')
                    form_id = '_id_menu_manager_tool_site_update_category';

                $.each(formData, function (key, value) {
                    var eSuccess = 1;
                    var input_lang_id = value.name.split('_', 1);
                    var form_errors = {};
                    var count = 0;

                    $.each(data.errors, function (eKey, eValue) {
                        if (value.name == eKey) {
                            eSuccess = 0;
                            form_errors[input_lang_id + '_category_name'] = eValue;
                            $('a[href="#' + input_lang_id + tab + '"] span.mm-lang-name').addClass('mm-tab-error-color');
                            count++;
                        }
                    });

                    if (count == 0) {
                        $('a[href="#' + input_lang_id + tab + '"] span.mm-lang-name').removeClass('mm-tab-error-color');
                    }

                    melisCoreTool.highlightErrors(eSuccess, form_errors, input_lang_id + form_id);
                });
            }

            melisCore.flashMessenger();
            melisCoreTool.done('#id_meliscms_mini_template_menu_manager_save_btn');
        }).fail(function (data) {
            melisCoreTool.done('#id_meliscms_mini_template_menu_manager_save_btn');
        });
    });

    $body.on('click', '.menu-mini-template-tool-edit-btn', function() {
        var row_data = $('#tableMiniTemplateMenuManagerPlugins').DataTable().row('#'+$(this).closest('tr').attr('id')).data();

        if ($(this).closest('tr').hasClass('child')) {
            row_data = $('#tableMiniTemplateMenuManagerPlugins').DataTable().row('#'+$(this).closest('tr').prev().attr('id')).data();
        }

        var templateName = row_data.DT_RowAttr.templateName;
        var module = row_data.DT_RowAttr.module;
        var thumbnail = row_data.DT_RowAttr.thumbnail;

        melisHelper.tabOpen(translations.tr_meliscms_mini_template_manager_tool, 'fa fa-tasks', 'id_meliscms_mini_template_manager_tool', 'meliscms_mini_template_manager_tool');
        var alreadyOpen = $("body #melis-id-nav-bar-tabs li a.tab-element[data-id='id_meliscms_mini_template_manager_tool']");
        var checkTab = setInterval(function() {
            if (alreadyOpen.length) {
                melisHelper.tabOpen(
                    'Tpl ' + templateName,
                    'fa fa-tasks',
                    templateName + '_id_meliscms_mini_template_manager_tool_add',
                    'meliscms_mini_template_manager_tool_add',
                    {
                        module: module,
                        templateName: templateName,
                        thumbnail: thumbnail
                    },
                    'id_meliscms_mini_template_manager_tool'
                );
                clearInterval(checkTab);
            }
        }, 500);
    });

    $body.on('click', '.menu-mini-template-tool-delete-btn', function() {
        var row_data = $('#tableMiniTemplateMenuManagerPlugins').DataTable().row('#'+$(this).closest('tr').attr('id')).data();

        if ($(this).closest('tr').hasClass('child')) {
            row_data = $('#tableMiniTemplateMenuManagerPlugins').DataTable().row('#' + $(this).closest('tr').prev().attr('id')).data();
        }

        var templateName = row_data.DT_RowAttr.templateName;

        melisCoreTool.confirm(
            translations.tr_meliscms_mini_template_manager_tool_delete_modal_confirm,
            translations.tr_meliscms_mini_template_manager_tool_delete_modal_cancel,
            translations.tr_meliscms_mini_template_menu_manager_remove_plugin,
            translations.tr_meliscms_mini_template_menu_manager_remove_plugin_text,
            function () {
                $.ajax({
                    type: 'POST',
                    url: '/melis/MelisCms/MiniTemplateMenuManager/removePluginFromCategory',
                    data: {
                        template: templateName,
                        siteId: $(siteSelect).find('option:selected').data('id')
                    },
                }).done(function (response) {
                    if (response.success) {
                        $body.find('.melis-mini-template-menu-manager-table-refresh').trigger('click');
                        if ($body.find('#id_meliscms_mini_template_menu_manager_tool').length) {
                            $(tree).jstree(true).refresh();
                        }
                        $('li[data-tool-id="' + templateName + '_id_meliscms_mini_template_manager_tool_add"]').find('a.close').trigger('click');
                    } else {
                        melisHelper.melisKoNotification(
                            translations.tr_meliscms_mini_template_manager_tool_delete_modal_title,
                            translations.tr_meliscms_mini_template_delete_fail
                        );
                    }
                }).fail(function (data) {

                });
            }
        );
    });

    function initCmsMiniTemplateCategoryTree () {
        var current_level;
        var query = 'langlocale=' + $("#mini-template-category-tree").data('langlocale');
        query = query + ' &siteId=' + $(siteSelect).find('option:selected').data('id');

        $body.on("click", "#mini-template-category-tree", function(evt) {
            $("#mini-template-category-tree ul li div").removeClass("jstree-wholerow-clicked");
            evt.stopPropagation();
            evt.preventDefault();
        });

        $(tree)
            .on('#mini-template-category-tree changed.jstree', function (e, data) {

            })
            .on('#mini-template-category-tree refresh.jstree', function (e, data) {
                if ($("#mini-template-category-tree .jstree-container-ul").children("li").length ===  0) {
                    $("#mini-template-tree-no-data").css("display","inline-block");
                } else {
                    $("#mini-template-tree-no-data").css("display","none");
                }
            })
            .on('#mini-template-category-tree loading.jstree', function (e, data) {})
            .on('#mini-template-category-tree loaded.jstree', function (e, data) {
                if ($("#mini-template-category-tree .jstree-container-ul").children("li").length ===  0) {
                    $("#mini-template-tree-no-data").css("display","inline-block");
                } else {
                    $("#mini-template-tree-no-data").css("display","none");
                }

                $('.add-m-tpl-category').prop('disabled', false);
                $('.add-m-tpl-category').attr('title', '');
                $('.add-m-tpl-plugin').prop('disabled', false);
                $('.add-m-tpl-plugin').attr('title', '');
                $('.mini-template-menu-manager-lang').find('a').removeClass('disabled');
                $('.mini-template-menu-manager-lang').find('a').attr('title', '');
            })
            .on('#mini-template-category-tree ready.jstree', function (e, data) {})
            .on('#mini-template-category-tree load_node.jstree', function (e, data) {})
            .on('#mini-template-category-tree open_node.jstree', function (e, data) {})
            .on('#mini-template-category-tree after_open.jstree', function (e, data) {})
            .on('#mini-template-category-tree dblclick.jstree', function (e, data) {
                var selected = $(tree).jstree().get_selected(true)[0];

                if (selected.type == 'category') {
                    $('#id_meliscms_mini_template_menu_manager_tool_add_category_container').removeClass('hidden');
                    melisHelper.zoneReload(
                        'id_meliscms_mini_template_menu_manager_tool_add_category_container',
                        'meliscms_mini_template_menu_manager_tool_add_category_container',
                        {
                            isHidden: false,
                            id: selected.id,
                            formType: 'edit',
                            status: selected.original.status
                        },
                        function () {
                            $('#id_meliscms_mini_template_menu_manager_tool_header a').trigger("click");
                        }
                    );
                } else if (selected.type == 'mini-template') {
                    melisHelper.tabOpen(translations.tr_meliscms_mini_template_manager_tool, 'fa fa-tasks', 'id_meliscms_mini_template_manager_tool', 'meliscms_mini_template_manager_tool');
                    var alreadyOpen = $("body #melis-id-nav-bar-tabs li a.tab-element[data-id='id_meliscms_mini_template_manager_tool']");

                    var checkTab = setInterval(function() {
                        if (alreadyOpen.length) {
                            melisHelper.tabOpen(
                                'Tpl ' + selected.original.id,
                                'fa fa-tasks',
                                selected.original.id + '_id_meliscms_mini_template_manager_tool_add',
                                'meliscms_mini_template_manager_tool_add',
                                {
                                    module: selected.original.module,
                                    templateName: selected.original.id,
                                    thumbnail: selected.original.imgSource
                                },
                                'id_meliscms_mini_template_manager_tool'
                            );
                            clearInterval(checkTab);
                        }
                    }, 500);
                }
            })
            .on('#mini-template-category-tree move_node.jstree', function (e, data) {
                var tree_data = $(tree).jstree(true).get_json('#', {flat:true});

                $.ajax({
                    type: 'POST',
                    url: '/melis/MelisCms/MiniTemplateMenuManager/saveTree',
                    data: {
                        tree_data: JSON.stringify(tree_data),
                        site_id: $(siteSelect).find('option:selected').data('id')
                    },
                }).done(function (data) {
                    if (data.success) {
                        $('.melis-mini-template-menu-manager-table-refresh').trigger('click');
                    } else {

                    }
                }).fail(function (data) {

                });
            })
            .on('#mini-template-category-tree select_node.jstree', function (e, data) {
                // if (selectedNode === data.node.text) {
                //     $(tree).jstree().deselect_all();
                //     selectedNode = '';
                // } else {
                //     selectedNode = data.node.text;
                // }
            })
            .jstree({
                "contextmenu" : {
                    "items" : function (node) {
                        var menu = {
                            'add_category_template' : {
                                "label" : translations.tr_meliscms_mini_template_menu_manager_tool_jstree_add_minitemplate,
                                "icon"  : "fa fa-plus",
                                "action" : function (obj) {
                                    melisHelper.tabOpen(translations.tr_meliscms_mini_template_manager_tool, 'fa fa-tasks', 'id_meliscms_mini_template_manager_tool', 'meliscms_mini_template_manager_tool');
                                    var alreadyOpen = $("body #melis-id-nav-bar-tabs li a.tab-element[data-id='id_meliscms_mini_template_manager_tool']");

                                    var checkTab = setInterval(function() {
                                        if (alreadyOpen.length) {
                                            $(".close.close-tab[data-id='new_template_id_meliscms_mini_template_manager_tool_add']").trigger("click");

                                            melisHelper.tabOpen(
                                                translations.tr_meliscms_mini_template_manager_tool_header_add_btn,
                                                'fa fa-tasks',
                                                'new_template_id_meliscms_mini_template_manager_tool_add',
                                                'meliscms_mini_template_manager_tool_add',
                                                {
                                                    templateName: 'new_template',
                                                    siteId: $(siteSelect).find('option:selected').data('id'),
                                                    module: $(siteSelect).find('option:selected').val(),
                                                    categoryId: node.original.categoryId
                                                },
                                                'id_meliscms_mini_template_manager_tool'
                                            );
                                            clearInterval(checkTab);
                                        }
                                    }, 500);
                                }
                            },
                            "edit_category" : {
                                "label" : translations.tr_meliscms_mini_template_menu_manager_tool_jstree_edit_category,
                                "icon"  : "fa fa-edit",
                                "action" : function (obj) {
                                    var parentId = parseInt(node.id),
                                        position = node.children.length + 1;

                                    $('#id_meliscms_mini_template_menu_manager_tool_add_category_container').removeClass('hidden');
                                    melisHelper.zoneReload(
                                        'id_meliscms_mini_template_menu_manager_tool_add_category_container',
                                        'meliscms_mini_template_menu_manager_tool_add_category_container',
                                        {
                                            isHidden: false,
                                            id: node.id,
                                            formType: 'edit',
                                            status: node.original.status
                                        },
                                        function () {
                                            $('#id_meliscms_mini_template_menu_manager_tool_header a').trigger("click");
                                        }
                                    );
                                }
                            },
                            "delete_category" : {
                                "label" : translations.tr_meliscms_mini_template_menu_manager_tool_jstree_delete_category,
                                "icon"  : "fa fa-trash-o",
                                "action" : function (obj) {
                                    if (node.children.length == 0) {
                                        melisCoreTool.confirm(
                                            translations.tr_meliscms_mini_template_manager_tool_delete_modal_confirm,
                                            translations.tr_meliscms_mini_template_manager_tool_delete_modal_cancel,
                                            translations.tr_meliscms_mini_template_menu_manager_tool_jstree_delete_category_title,
                                            translations.tr_meliscms_mini_template_menu_manager_tool_jstree_delete_category_text,
                                            function () {
                                                $.ajax({
                                                    type: 'POST',
                                                    url: '/melis/MelisCms/MiniTemplateMenuManager/deleteCategory',
                                                    data: {
                                                        id: node.id
                                                    },
                                                }).done(function (data) {
                                                    if (data.success) {
                                                        $(tree).jstree(true).refresh();
                                                        melisHelper.melisOkNotification(data.textTitle, data.textMessage);
                                                    } else {
                                                        melisHelper.melisKoNotification(
                                                            translations.tr_meliscms_mini_template_menu_manager_tool_jstree_delete_category_title,
                                                            translations.tr_meliscms_mini_template_menu_manager_category_delete_fail
                                                        );
                                                    }

                                                    melisCore.flashMessenger();
                                                }).fail(function (data) {

                                                });
                                            }
                                        );
                                    } else {
                                        melisHelper.melisKoNotification(
                                            translations.tr_meliscms_mini_template_menu_manager_tool_jstree_delete_category_title,
                                            translations.tr_meliscms_mini_template_menu_manager_tool_jstree_delete_category_error_children,
                                            []
                                        );
                                    }
                                }
                            },
                            'edit_plugin': {
                                "label" : translations.tr_meliscms_mini_template_menu_manager_tool_jstree_edit_minitemplate,
                                "icon"  : "fa fa-edit",
                                "action" : function (obj) {
                                    melisHelper.tabOpen(translations.tr_meliscms_mini_template_manager_tool, 'fa fa-tasks', 'id_meliscms_mini_template_manager_tool', 'meliscms_mini_template_manager_tool');
                                    var alreadyOpen = $("body #melis-id-nav-bar-tabs li a.tab-element[data-id='id_meliscms_mini_template_manager_tool']");

                                    var checkTab = setInterval(function() {
                                        if (alreadyOpen.length) {
                                            melisHelper.tabOpen(
                                                'Tpl ' + node.original.id,
                                                'fa fa-tasks',
                                                node.original.id + '_id_meliscms_mini_template_manager_tool_add',
                                                'meliscms_mini_template_manager_tool_add',
                                                {
                                                    module: node.original.module,
                                                    templateName: node.original.id,
                                                    thumbnail: node.original.imgSource
                                                },
                                                'id_meliscms_mini_template_manager_tool'
                                            );
                                            clearInterval(checkTab);
                                        }
                                    }, 500);
                                }
                            },
                        };

                        if (node.original.type == 'category') {
                            delete menu.edit_plugin;
                            delete menu.delete_plugin;
                        }

                        if (node.original.type == 'mini-template') {
                            delete menu.edit_category;
                            delete menu.delete_category;
                            delete menu.add_category_template;
                        }

                        return menu;
                    }
                },
                "core" : {
                    "multiple": false,
                    "check_callback": function (operation, node, node_parent, node_position, more) {
                        // if (
                        //     more
                        //     && more.dnd
                        //     && (operation === 'move_node' || operation === 'copy_node')
                        //     && node.type == 'mini-template'
                        //     && node_parent.id == '#'
                        // )  {
                        //     return false;
                        // }
                        // if (
                        //     more
                        //     && more.dnd
                        //     && (operation === 'move_node' || operation === 'copy_node')
                        //     && node.type == 'category'
                        //     && $(tree).jstree(true).get_node(
                        //         $(tree).jstree(true).get_node('#').children[node_position]
                        //     ).type == 'mini-template'
                        //     ){
                        //     return false;
                        // }
                        // return true;
                    },
                    "animation" : 500,
                    "themes": {
                        "name": "proton",
                        "responsive": false
                    },
                    "dblclick_toggle" : false,
                    "data" : {
                        "cache" : false,
                        "url" : "/melis/MelisCms/MiniTemplateMenuManager/getTree?" + query,
                    },
                    'strings': {
                        'Loading ...' : translations.tr_meliscms_mini_template_menu_manager_js_tree_loading
                    },
                },
                "types" : {
                    "#" : {
                        "valid_children" : ["mini-template", "category"]
                    },
                    "category" : {
                        "valid_children" : ["mini-template"]
                    },
                    "mini-template" : {
                        "valid_children" : ["none"]
                    },
                },
                "plugins": [
                    "contextmenu", // plugin makes it possible to right click nodes and shows a list of configurable actions in a menu.
                    "changed", // Plugins for Change and Click Event
                    "dnd", // Plugins for Drag and Drop
                    "search", // Plugins for Search of the Node(s) of the Tree View
                    "types", // Plugins for Customizing the Nodes
                ]
            });
    }
});

window.initMiniTemplateMenuManagerPluginTables = function (data, tableSettings) {
    data.id = $('#menu-manager-category-id').data('id');

    $('#tableMiniTemplateMenuManagerPlugins').on('row-reorder.dt', function ( e, diff, edit ) {
        var result = 'Reorder started on row: '+edit.triggerRow.data()[1]+'<br>';

        for ( var i=0, ien=diff.length ; i<ien ; i++ ) {
            var rowData = $('#tableMiniTemplateMenuManagerPlugins').DataTable().row(diff[i].node).data();
            result += rowData[1]+' updated to be in position '+ diff[i].newData+' (was '+diff[i].oldData+')<br>';
        }

        if (!$.isEmptyObject(diff) ) {
            // var form_data = $('#tableMiniTemplateMenuManagerPlugins').DataTable().rows().data();

            var dataString 	= new Array,
                prdNodes 	= new Array;

            $.each(diff, function() {
                prdNodes.push(this.node.id+'-'+this.newPosition);
            });

            dataString.push({
                name : "data",
                value: prdNodes.join()
            });

            dataString = $.param(dataString);
            $.ajax({
                type        : "POST",
                url         : "/melis/MelisCms/MiniTemplateMenuManager/reorderMiniTemplates",
                data		: dataString,
                dataType    : "json",
                encode		: true,
                beforeSend: function () {
                    $('#tableMiniTemplateMenuManagerPlugins').addClass('loading-changes');
                    $('#tableMiniTemplateMenuManagerPlugins').DataTable().rowReorder.disable();
                }
            }).done(function(data) {
                $('#mini-template-category-tree').jstree(true).refresh();
                $('#tableMiniTemplateMenuManagerPlugins').DataTable().rowReorder.enable();
                $('#tableMiniTemplateMenuManagerPlugins').removeClass('loading-changes');
            }).fail(function(){

            });
        }
    });
};
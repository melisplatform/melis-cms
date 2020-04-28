$(function () {
    var $body = $('body');
    var siteSelect = '#mini-template-menu-manager-site-select';
    var languageSelect = '.mini-template-menu-manager-lang-select li a';
    var form = '#id_menu_manager_tool_site';
    var tree = '#mini-template-category-tree';
    var isInitialized = false;
    var selectedNode = '';

    // how to get selected node
    // $(tree).jstree().get_selected(true)[0].text

    $body.on('click', '.add-m-tpl-category', function () {
        $('#id_meliscms_mini_template_menu_manager_tool_add_category_container').removeClass('hidden');
        melisHelper.zoneReload(
            'id_meliscms_mini_template_menu_manager_tool_add_category_container',
            'meliscms_mini_template_menu_manager_tool_add_category_container',
            {
                isHidden: false
            },
            function () {
                $('#id_meliscms_mini_template_menu_manager_tool_header a').click();
            }
        );
    });

    $body.on('click', '.add-m-tpl-plugin', function () {
        melisHelper.tabOpen(
            'Add mini-template',
            'fa fa-list-alt',
            'new_template_id_meliscms_mini_template_manager_tool_add',
            'meliscms_mini_template_manager_tool_add',
            {
                templateName: 'new_template',
                siteId:$(siteSelect).find('option:selected').data('id')
            }
        );
    });

    $body.on('click', '.close', function () {
        if ($(this).data('id') == 'id_meliscms_mini_template_menu_manager_tool') {
            isInitialized = false;
        }
    });

    $body.on('change', siteSelect, function () {
        var siteId = $(this).val();

        if (isInitialized) {
            $(tree).jstree(true).settings.core.data.data = [
                {name: 'langlocale', value: $("#mini-template-category-tree").data('langlocale')},
                {name: 'module', value: $(siteSelect).val()}
            ];
            $(tree).jstree(true).refresh();
        } else {
            initCmsMiniTemplateCategoryTree();
            isInitialized = true;
        }
    });

    $body.on('click', languageSelect, function () {
        var $this = $(this);
        var text = $this.text();
        var locale = $this.data('locale');

        $('.mini-template-menu-manager-lang a span.filter-key').text(text);
        $(tree).data('langlocale', locale);
        $(tree).jstree(true).settings.core.data.data = [{name : "langlocale", value: locale}, {name:"siteId", value : $(siteSelect).val()}];
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

        melisCoreTool.pending('#id_meliscms_mini_template_menu_manager_save_btn');
        $.ajax({
            type: 'POST',
            url: '/melis/MelisCms/MiniTemplateMenuManager/saveCategory',
            data: formData,
        }).done(function (data) {
            if (data.success) {
                $(tree).jstree(true).refresh();
                melisHelper.zoneReload(
                    'id_meliscms_mini_template_menu_manager_tool_add_category_container',
                    'meliscms_mini_template_menu_manager_tool_add_category_container',
                    {},
                    function () {
                        $('#id_meliscms_mini_template_menu_manager_tool_header a').click();
                    }
                );
            } else {

            }
            melisCoreTool.done('#id_meliscms_mini_template_menu_manager_save_btn');
        }).fail(function (data) {
            melisCoreTool.done('#id_meliscms_mini_template_menu_manager_save_btn');
        });
    });

    // Know when an element is already rendered
    function waitForElem(selector, callback){
        var poller1 = setInterval(function(){
            $jObject = $(selector);
            if($jObject.length < 1){
                return;
            }
            clearInterval(poller1);
            callback($jObject)
        },100);
    }

    function initCmsMiniTemplateCategoryTree () {
        var current_level;
        var query = 'langlocale=' + $("#mini-template-category-tree").data('langlocale');
        query = query + ' &module=' + $(siteSelect).val();

        $body.on("click", "#mini-template-category-tree", function(evt) {
            $("#mini-template-category-tree ul li div").removeClass("jstree-wholerow-clicked");
            evt.stopPropagation();
            evt.preventDefault();
        });

        $(tree)
            .on('#mini-template-category-tree changed.jstree', function (e, data) {})
            .on('#mini-template-category-tree refresh.jstree', function (e, data) {})
            .on('#mini-template-category-tree loading.jstree', function (e, data) {})
            .on('#mini-template-category-tree loaded.jstree', function (e, data) {
                if ($('#mini-template-category-tree').find(".jstree-unchecked").length) {
                    $('mini-template-tree-no-data').css('display', '');
                }

                $('.add-m-tpl-category').removeAttr('disabled');
                $('.add-m-tpl-plugin').removeAttr('disabled');
            })
            .on('#mini-template-category-tree ready.jstree', function (e, data) {})
            .on('#mini-template-category-tree load_node.jstree', function (e, data) {})
            .on('#mini-template-category-tree open_node.jstree', function (e, data) {})
            .on('#mini-template-category-tree after_open.jstree', function (e, data) {})
            .on('#mini-template-category-tree move_node.jstree', function (e, data) {
                var id = data.node.id;
                var type = data.node.type;
                var newParentId = data.parent;
                var oldParentId = data.old_parent;
                var position = data.position + 1;
                var old_position = data.old_position + 1;

                $.ajax({
                    type: 'POST',
                    url: '/melis/MelisCms/MiniTemplateMenuManager/saveTree',
                    data: {
                        id: id,
                        type: type,
                        newParentId: newParentId,
                        oldParentId: oldParentId,
                        position: position,
                        oldPosition: old_position
                    },
                }).done(function (data) {
                    if (data.success) {

                    } else {

                    }
                }).fail(function (data) {

                });
            })
            .on('#mini-template-category-tree select_node.jstree', function (e, data) {
                if (selectedNode === data.node.text) {
                    $(tree).jstree().deselect_all();
                    selectedNode = '';
                } else {
                    selectedNode = data.node.text;
                }
            })
            .jstree({
                "contextmenu" : {
                    "items" : function (node) {
                        var menu = {
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
                                            $('#id_meliscms_mini_template_menu_manager_tool_header a').click();
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
                                                    } else {
                                                        melisHelper.melisKoNotification(
                                                            translations.tr_meliscms_mini_template_menu_manager_tool_jstree_delete_category_title,
                                                            translations.tr_meliscms_mini_template_menu_manager_tool_jstree_delete_category_error,
                                                            data.errors
                                                        );
                                                    }
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
                                    waitForElem('#miniTemplateThumbnail', function (element) {
                                        $('#new-minitemplate-thumbnail').attr('src', node.original.imgSource);
                                    });

                                    melisHelper.tabOpen(
                                        'Tpl ' + node.original.id,
                                        'fa fa-tasks',
                                        node.original.id + '_id_meliscms_mini_template_manager_tool_add',
                                        'meliscms_mini_template_manager_tool_add',
                                        {
                                            module: node.original.module,
                                            templateName: node.original.id
                                        }
                                    );
                                }
                            },
                            'delete_plugin': {
                                "label" : translations.tr_meliscms_mini_template_menu_manager_tool_jstree_delete_minitemplate,
                                "icon"  : "fa fa-edit",
                                "action" : function (obj) {
                                    var templateName = node.original.id;
                                    var module = node.original.module;

                                    melisCoreTool.confirm(
                                        translations.tr_meliscms_mini_template_manager_tool_delete_modal_confirm,
                                        translations.tr_meliscms_mini_template_manager_tool_delete_modal_cancel,
                                        translations.tr_meliscms_mini_template_manager_tool_delete_modal_title,
                                        translations.tr_meliscms_mini_template_manager_tool_delete_modal_text,
                                        function () {
                                            $.ajax({
                                                type: 'POST',
                                                url: '/melis/MelisCms/MiniTemplateManager/deleteMiniTemplate',
                                                data: {
                                                    template: templateName,
                                                    module: module
                                                },
                                            }).done(function (data) {
                                                if (data.success) {
                                                    $body.find('.mini-template-manager-tool-table-refresh .melis-mini-template-manager-table-refresh').trigger('click');
                                                    $(tree).jstree(true).refresh();
                                                } else {
                                                    melisHelper.melisKoNotification(
                                                        translations.tr_meliscms_mini_template_manager_tool_delete_modal_title,
                                                        '',
                                                        data.errors
                                                    );
                                                }
                                            }).fail(function (data) {

                                            });
                                        }
                                    );
                                }
                            }
                        };

                        if (node.original.type == 'category') {
                            delete menu.edit_plugin;
                            delete menu.delete_plugin;
                        }

                        if (node.original.type == 'mini-template') {
                            delete menu.edit_category;
                            delete menu.delete_category;
                        }

                        return menu;
                    }
                },
                "core" : {
                    "multiple": false,
                    "check_callback": function (operation, node, node_parent, node_position, more) {
                        console.log($(tree).jstree(true).get_node('#').children[node_position]);
                        if (
                            more
                            && more.dnd
                            && (operation === 'move_node' || operation === 'copy_node')
                            && node.type == 'mini-template'
                            && node_parent.id == '#'
                        )  {
                            return false;
                        }
                        if (
                            more
                            && more.dnd
                            && (operation === 'move_node' || operation === 'copy_node')
                            && node.type == 'category'
                            && $(tree).jstree(true).get_node(
                                $(tree).jstree(true).get_node('#').children[node_position]
                            ).type == 'mini-template'
                            ){
                            return false;
                        }
                        return true;
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
                        'Loading ...' : 'Loading'
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
        var miniTemplates = [];

        for ( var i=0, ien=diff.length ; i<ien ; i++ ) {
            var rowData = $('#tableMiniTemplateMenuManagerPlugins').DataTable().row(diff[i].node).data();
            result += rowData[1]+' updated to be in position '+ diff[i].newData+' (was '+diff[i].oldData+')<br>';
        }

        if (!$.isEmptyObject(diff) ) {
            var dataString 	= new Array,
                prdNodes 	= new Array;

            $.each(diff, function() {
                var new_position = parseInt(this.newPosition) + 1;
                prdNodes.push(this.node.id + '=' + new_position.toString());
            });

            dataString.push({
                name : "data",
                value: prdNodes.join()
            });

            dataString = $.param(dataString);

            $('#mini-template-category-tree').jstree(true).refresh();

            $.ajax({
                type        : "POST",
                url         : "/melis/MelisCms/MiniTemplateMenuManager/reorderMiniTemplates",
                data		: dataString,
                dataType    : "json",
                encode		: true
            }).done(function(data) {
                if(!data.success) {

                }
            }).fail(function(){

            });
        }
    });
};
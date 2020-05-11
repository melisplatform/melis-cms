$(function () {
    var $body = $('body');
    var header_add_btn = '#id_meliscms_mini_template_manager_tool_header_add_btn';
    var add_form = '#id_mini_template_manager_tool_add';
    var add_btn = '#melis-cms-minitemplate-add-btn';
    var table_edit = '.mini-template-tool-edit-btn';
    var table_delete = '.mini-template-tool-delete-btn';
    var table_site_select = '#mini-template-manager-tool-table-filter-sites-select';
    var dataTable = '#tableMiniTemplateManager';
    var thumbnail_preview = '#new-minitemplate-thumbnail';
    var thumbnail_input = '#miniTemplateThumbnail';
    var remove_thumbnail_preview = '#remove-mini-template-thumbnail-preview';
    var add_body_form_container = '#id_meliscms_mini_template_manager_tool_add_body_form';
    var tree = '#mini-template-category-tree';
    var table_refresh_btn = '.melis-mini-template-manager-table-refresh';

    $body.on('keypress keyup', '#miniTemplateName', function (e) {
        // regex for special characters except for _ and -
        var regex = /^[^:\?*\/"<>\)\(}{\]\[\.,\^!@#$|&%+=;\'\\\s]+$/;
        var key = String.fromCharCode(!e.charCode ? e.which : e.charCode);

        if (e.event == 'keyup') {
            key = String.fromCharCode(e.keyCode);
        }

        if (!regex.test(key)) {
            e.preventDefault();
        }

        // catch space and change to dash
        if (e.which === 32) {
            // get old value
            var start = e.target.selectionStart;
            var end = e.target.selectionEnd;
            var old_value = e.target.value;

            // replace point and change input value
            var new_value = old_value.slice(0, start) + '-' + old_value.slice(end)
            e.target.value = new_value;

            // replace cursor
            e.target.selectionStart = e.target.selectionEnd = start + 1;
            e.preventDefault();
        }

        // when enter is pressed
        if (e.keyCode === 13) {
            e.preventDefault();
        }

    });

    $body.on('paste', '#miniTemplateName', function (e) {
        e.preventDefault();
    });

    // Open add mini-template tab
    $body.on('click', header_add_btn, function () {
        miniTemplateManagerTool.openTab(
            translations.tr_meliscms_mini_template_manager_tool_header_add_btn,
            'new_template',
            {
                templateName: 'new_template',
                siteId: $(table_site_select).find('option:selected').data('id')
            }
        );
    });

    // Open edit mini-template tab
    $body.on('click', table_edit, function () {
        var templateName = $(this).closest('tr').attr('id');
        var module = $(this).closest('tr').find('p').data('module');
        var imgSource = $(this).closest('tr').find('img').attr('src');

        waitForEl(thumbnail_input, function (element) {
            $(thumbnail_preview).attr('src', imgSource);
        });

        miniTemplateManagerTool.openTab(
            'Tpl ' + templateName,
            templateName,
            {
                module: module,
                templateName: templateName,
            }
        );
    });

    $body.on('click', add_btn, function () {
       $(add_form).trigger('submit');
    });

    // Creating mini-template
    $body.on('submit', add_form, function (e) {
        melisCoreTool.pending(add_btn);
        var formData = new FormData(this);
        formData.append('categoryId', $('#mini-template-manager-category-id').val());

        $.ajax({
            type: 'POST',
            url: '/melis/MelisCms/MiniTemplateManager/createMiniTemplate',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
        }).done(function (data) {
            if (data.success) {
                melisHelper.tabClose('new_template_id_meliscms_mini_template_manager_tool_add');
                $body.find('.mini-template-manager-tool-table-refresh ' + table_refresh_btn).trigger('click');
                if ($body.find('#id_meliscms_mini_template_menu_manager_tool').length) {
                    $(tree).jstree(true).refresh();
                }
                $body.find('.melis-mini-template-menu-manager-table-refresh').trigger('click');
            } else {
                melisHelper.melisKoNotification(translations.tr_meliscms_mini_template_menu_manager_save_mini_template, '', data.errors);
                melisCoreTool.highlightErrors(data.success, data.errors, 'id_mini_template_manager_tool_add');
            }

            melisCoreTool.done(add_btn);
        }).fail(function (data) {
            melisCoreTool.done(add_btn);
        });

       e.preventDefault();
    });

    $body.on('click', '#melis-cms-minitemplate-edit-btn', function () {
        $('#id_mini_template_manager_tool_update').trigger('submit');
    });

    // Update mini-template
    $body.on('submit', '#id_mini_template_manager_tool_update', function(e) {
        melisCoreTool.pending('#melis-cms-minitemplate-edit-btn');
        var $form_container = $(add_body_form_container);
        var current_module = $form_container.data('currentmodule');
        var current_template = $form_container.data('currenttemplate');
        var formData = new FormData(this);
        var image_flag = false;

        if ($(thumbnail_preview).attr('src') == '/MelisFront/plugins/images/default.jpg') {
            image_flag = true;
        }

        formData.append('image', image_flag);
        formData.append('current_module', current_module);
        formData.append('current_template', current_template);

        $.ajax({
            type: 'POST',
            url: '/melis/MelisCms/MiniTemplateManager/updateMiniTemplate',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
        }).done(function (data) {
            if (data.success) {
                melisHelper.tabClose(current_template + '_id_meliscms_mini_template_manager_tool_add');
                $body.find('.mini-template-manager-tool-table-refresh ' + table_refresh_btn).trigger('click');
                if ($body.find('#id_meliscms_mini_template_menu_manager_tool').length) {
                    $(tree).jstree(true).refresh();
                }
                $body.find('.melis-mini-template-menu-manager-table-refresh').trigger('click');
            } else {
                melisHelper.melisKoNotification(translations.tr_melis_cms_page_tree_import, '', data.errors);
                melisCoreTool.highlightErrors(data.success, data.errors, 'id_mini_template_manager_tool_update');
            }
            melisCoreTool.done('#melis-cms-minitemplate-edit-btn');
        }).fail(function (data) {
            melisCoreTool.done('#melis-cms-minitemplate-edit-btn');
        });

        e.preventDefault();
    });

    // Delete mini-template
    $body.on('click', table_delete, function () {
        var templateName = $(this).closest('tr').attr('id');
        var module = $(this).closest('tr').find('p').data('module');

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
                        $body.find('.mini-template-manager-tool-table-refresh ' + table_refresh_btn).trigger('click');
                        $body.find('.melis-mini-template-menu-manager-table-refresh').trigger('click');
                        if ($body.find('#id_meliscms_mini_template_menu_manager_tool').length) {
                            $(tree).jstree(true).refresh();
                        }
                        $body.find('.melis-mini-template-menu-manager-table-refresh').trigger('click');
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
    });

    // Thumbnail preview
    $body.on('change', thumbnail_input, function(e) {
        var input = this;
        var max_size = $body.find('#mini-template-manager-max-size').val();

        if ( input.files && input.files[0] ) {
            if (parseInt(input.files[0].size) > parseInt(max_size)) {
                e.preventDefault();
                $(this).val('');
                $(thumbnail_preview).attr('src', '/MelisFront/plugins/images/default.jpg');

                melisHelper.melisKoNotification(
                    translations.tr_melis_cms_page_tree_import,
                    translations.tr_melis_cms_page_tree_error_file_size_exceeded + formatBytes(max_size, 2),
                    []
                );
            } else {
                var reader = new FileReader();
                var $newImg = $(thumbnail_preview);

                reader.onload = function (e) {
                    $newImg.attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        } else {
            $(thumbnail_preview).attr('src', '/MelisFront/plugins/images/default.jpg');
        }
    });

    function formatBytes(bytes, decimals) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    // Remove thumbnail
    $body.on('click', remove_thumbnail_preview, function (e) {
        e.preventDefault();
        $(thumbnail_input).val('');
        $(thumbnail_preview).attr('src', '/MelisFront/plugins/images/default.jpg');
    });

    // Refresh table
    $body.on('click', table_refresh_btn, function () {
        melisHelper.zoneReload(
            'id_meliscms_mini_template_manager_tool_body_data_table',
            'meliscms_mini_template_manager_tool_body_data_table',
            {}
        );
    });

    // Select site
    $body.on('change', table_site_select, function() {
        $(dataTable).DataTable().ajax.reload();
        $(header_add_btn).removeClass('disabled');
        $(header_add_btn).removeAttr('disabled');
    });

    var miniTemplateManagerTool = {
        openTab: function (title, template_name, parameters) {
            melisHelper.tabOpen(
                title,
                'fa fa-tasks',
                template_name + '_id_meliscms_mini_template_manager_tool_add',
                'meliscms_mini_template_manager_tool_add',
                parameters
            );
        }
    };
});

window.initMiniTemplateManagerToolTableSites = function (data, tableSettings) {
    var $select = $('#mini-template-manager-tool-table-filter-sites-select');
    if ($select.length) {
        data.site_name = $select.val();
    }
};

window.miniTemplateManagerToolTableCallback = function () {
    waitForEl('#mini-template-manager-tool-table-filter-sites-select', function (element) {
        if (element.val() < 1) {
            element.find(':first-child').remove();
            element.val(element.find('option').val()).change();
        }
    });
};

// Know when an element is already rendered
function waitForEl(selector, callback){
    var poller1 = setInterval(function(){
        $jObject = $(selector);
        if($jObject.length < 1){
            return;
        }
        clearInterval(poller1);
        callback($jObject)
    },100);
}
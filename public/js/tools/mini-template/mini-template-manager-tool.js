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

    // Open add mini-template tab
    $body.on('click', header_add_btn, function () {
        miniTemplateManagerTool.openTab(
            'Add mini-template',
            'new_template',
            {
                templateName: 'new_template'
            }
        );
    });

    // Open edit mini-template tab
    $body.on('click', table_edit, function () {
        var templateName = $(this).closest('tr').find('.mini-template-tool-table-path').data('templatename');
        var module = $(this).closest('tr').find('.mini-template-tool-table-path').data('module');
        var path = $(this).closest('tr').find('.mini-template-tool-table-path').data('path');
        var imgSource = $(this).closest('tr').find('img').attr('src');
        var image = $(this).closest('tr').find('img').data('image');

        waitForEl(thumbnail_input, function (element) {
            $(thumbnail_preview).attr('src', imgSource);
        });

        miniTemplateManagerTool.openTab(
            'Tpl ' + templateName,
            templateName,
            {
                module: module,
                templateName: templateName,
                path: path,
                image: image
            }
        );
    });

    // Delete mini-template
    $body.on('click', table_delete, function () {
        var templateName = $(this).closest('tr').find('.mini-template-tool-table-path').data('templatename');
        var module = $(this).closest('tr').find('.mini-template-tool-table-path').data('module');
        var path = $(this).closest('tr').find('.mini-template-tool-table-path').data('path');
        var image = $(this).closest('tr').find('img').data('image');

        melisCoreTool.confirm(
            'confirm',
            'cancel',
            'Are you sure to delete this mini-template?',
            'This will remove the files and can’t be undone. This will also remove all link with a category.',
            function () {
                $.ajax({
                    type: 'POST',
                    url: '/melis/MelisCms/MiniTemplateManager/deleteMiniTemplate',
                    data: {
                        template: templateName,
                        module: module,
                        path: path,
                        image: image
                    },
                }).done(function (data) {
                    if (data.success) {
                        $body.find('.mini-template-manager-tool-table-refresh .melis-refreshTable').trigger('click');
                    } else {
                        melisHelper.melisKoNotification('Delete minitemplate', 'there was an error in deleting', data.errors);
                    }
                }).fail(function (data) {

                });
            }
        );
    });

    // Select site
    $body.on('change', table_site_select, function() {
        $(dataTable).DataTable().ajax.reload();
    });

    // Creating mini-template
    $body.on('submit', add_form, function (e) {
        melisCoreTool.pending(add_btn);
        var formData = new FormData(this);

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
                $body.find('.mini-template-manager-tool-table-refresh .melis-refreshTable').trigger('click');
            } else {
                melisHelper.melisKoNotification(translations.tr_melis_cms_page_tree_import, 'there are some errors', data.errors);
            }

            melisCoreTool.done(add_btn);
        }).fail(function (data) {
            melisCoreTool.done(add_btn);
        });

       e.preventDefault();
    });

    $body.on('submit', '#id_mini_template_manager_tool_update', function(e) {
        var $form_container = $(add_body_form_container);
        var current_module = $form_container.data('currentmodule');
        var current_template = $form_container.data('currenttemplate');
        var formData = new FormData(this);
        formData.append('image', $(thumbnail_preview).attr('src'));
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

            } else {
                melisHelper.melisKoNotification(translations.tr_melis_cms_page_tree_import, 'there are some errors', data.errors);
            }
        }).fail(function (data) {

        });

        e.preventDefault();
    });

    // Thumbnail preview
    $body.on('change', thumbnail_input, function() {
        var input = this;
        if ( input.files && input.files[0] ) {
            var reader = new FileReader();
            var $newImg = $(thumbnail_preview);

            reader.onload = function (e) {
                $newImg.attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            $(thumbnail_preview).attr('src', '/MelisCore/images/profile/default_picture.jpg');
        }
    });

    // Remove thumbnail
    $body.on('click', remove_thumbnail_preview, function (e) {
        e.preventDefault();
        $(thumbnail_input).val('');
        $(thumbnail_preview).attr('src', '/MelisCore/images/profile/default_picture.jpg');
    });

    var miniTemplateManagerTool = {
        openTab: function (title, template_name, parameters) {
            melisHelper.tabOpen(
                title,
                'fa fa-list-alt',
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
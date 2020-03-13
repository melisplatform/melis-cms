$(function () {
    $body = $('body');

    console.log('mini-template-manager-tool.js loaded!');

    // Open add mini-template tab
    $body.on('click', '#id_meliscms_mini_template_manager_tool_header_add_btn', function () {
        miniTemplateManagerTool.openTab('Add mini-template', 0);
    });

    var miniTemplateManagerTool = {
        openTab: function (title, id) {
            melisHelper.tabOpen(
                title,
                'fa fa-list-alt',
                id + '_id_meliscms_mini_template_manager_tool_add',
                'meliscms_mini_template_manager_tool_add',
                {
                    id: id
                }
            );
        }
    };
});
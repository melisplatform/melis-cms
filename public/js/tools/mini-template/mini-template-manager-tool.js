$(function () {
    var $body = $('body');

    console.log('mini-template-manager-tool.js loaded!');

    // Open add mini-template tab
    $body.on('click', '#id_meliscms_mini_template_manager_tool_header_add_btn', function () {
        miniTemplateManagerTool.openTab('Add mini-template', 0);
    });

    $body.on('change', "#mini-template-manager-tool-table-filter-sites-select", function() {
        console.log('changedddddd!');
        $('#tableMiniTemplateManager').DataTable().ajax.reload();
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

window.initMiniTemplateManagerToolTableSites = function (data, tableSettings) {
    console.log('init site select!!');
    var $select = $('#mini-template-manager-tool-table-filter-sites-select');
    if ($select.length) {
        data.site_id = $select.val();
    }
};

window.miniTemplateManagerToolTableCallback = function () {
    console.log('callback !');

    waitForEl('#mini-template-manager-tool-table-filter-sites-select', function (element) {
        if (element.val() < 1) {
            element.find(':first-child').remove();
            element.val(element.find('option').val()).change();
        }
    });
};

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
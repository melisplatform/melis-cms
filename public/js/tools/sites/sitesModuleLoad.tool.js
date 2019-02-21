$(document).ready(function() {

    $body = $("body");


    function switchButtonWithoutEvent(moduleName, status)
    {
        $('div[data-module-name="'+moduleName+'"]').find("div.switch-animate").removeClass("switch-on switch-off").addClass("switch-"+status);
    }

    /**
     * This block of code handles the main switch in site module loading
     * where will switch on/off all modules.
     */
    $body.on("switch-change", "#site-select-deselect-all-module", function(e, data){

        var currentTabId = activeTabId.split("_")[0];
        var val = "";
        if(data.value === false){
            val = "off";
        }else{
            val = "on";
        }

        $('.'+currentTabId+'_module_switch').find('div.switch-animate').removeClass("switch-on switch-off").addClass("switch-"+val);
    });

    $("body").on('switch-change', 'div[data-siteModule-name]', function (e, data) {

        var moduleName = $(this).attr("data-siteModule-name");
        var value 	   = data.value;
        var isInactive = false;
        var isActive   = true;

        if(value === isInactive) {


            $("h4#meliscore-tool-module-content-title").html(translations.tr_meliscore_module_management_checking_dependencies);
            $('div[data-siteModule-name]').bootstrapSwitch('setActive', false);


            $.ajax({
                type        : 'POST',
                url         : '/melis/MelisCms/SitesModuleLoader/getDependents',
                data		: {module : moduleName},
                dataType    : 'json',
                encode		: true,
            }).success(function(data){
                var modules    = "<br/><br/><div class='container'><div class='row'><div class='col-lg-12'><ul>%s</ul></div></div></div>";
                var moduleList = '';

                $.each(data.modules, function(i, v) {
                    moduleList += "<li>"+v+"</li>";

                });

                modules = modules.replace("%s", moduleList);

                if(data.success) {
                    melisCoreTool.confirm(
                        translations.tr_meliscore_common_yes,
                        translations.tr_meliscore_tool_emails_mngt_generic_from_header_cancel,
                        translations.tr_meliscore_general_proceed,
                        data.message+modules,
                        function() {
                            $.each(data.modules, function(i, v) {
                                // this will trigger a switch-change event
                                // $('div[data-siteModule-name="'+v+'"]').bootstrapSwitch('setState', false, false);
                                // this will just trigger an animate switch
                                switchButtonWithoutEvent(v, "off");
                            });
                        }
                    );
                }
                $('div[data-siteModule-name]').bootstrapSwitch('setActive', true);
                $("h4#meliscore-tool-module-content-title").html(translations.tr_meliscore_module_management_modules);
            });

        }


        if(value === isActive) {
            $("h4#meliscore-tool-module-content-title").html(translations.tr_meliscore_module_management_checking_dependencies);
            $('div[data-siteModule-name]').bootstrapSwitch('setActive', false);

            $.ajax({
                type        : 'POST',
                url         : '/melis/MelisCms/SitesModuleLoader/getRequiredDependencies',
                data		: {module : moduleName},
                dataType    : 'json',
                encode		: true,
            }).success(function(data){
                if(data.success) {
                    $.each(data.modules, function(i, v) {
                        // this will trigger a switch-change event
                        // $('div[data-siteModule-name="'+v+'"]').bootstrapSwitch('setState', false, false);
                        // this will just trigger an animate switch
                        switchButtonWithoutEvent(v, "on");
                    });

                }
                $('div[data-siteModule-name]').bootstrapSwitch('setActive', true);
                $("h4#meliscore-tool-module-content-title").html(translations.tr_meliscore_module_management_modules);
            });
        }

    });

});

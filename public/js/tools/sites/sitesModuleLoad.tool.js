$(document).ready(function() {

    $body = $("body");

    /**
     * Get all input values into one array on clicking save button except for the site translation inputs
     */
    $body.on("click","#btn-save-meliscms-tool-sites", function () {
        var currentTabId = activeTabId.split("_")[0];
        var dataString = $("#"+currentTabId+"_id_meliscms_tool_sites_edit_site form").serializeArray();
        // serialize the new array and send it to server
        dataString = $.param(dataString);

        $.ajax({
            type        : 'POST',
            url         : '/melis/MelisCms/Sites/saveSite?siteId='+currentTabId,
            data        : dataString,
            dataType    : 'json',
            encode		: true
        }).success(function(data){
            if(data.success === 1){

                // call melisOkNotification
                melisHelper.melisOkNotification( data.textTitle, data.textMessage, '#72af46' );

                // update flash messenger values
                melisCore.flashMessenger();

            }
            else
            {
                melisCoreTool.highlightErrors(data.success, data.errors, currentTabId+"_id_meliscms_tool_sites_edit_site");
                // error modal
                melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors );
            }

            // update flash messenger values
            melisCore.flashMessenger();

        }).error(function(xhr, textStatus, errorThrown){
            alert( translations.tr_meliscore_error_message );
        });

    });

    function switchButtonWithoutEvent(moduleName, status)
    {
        var currentTabId = activeTabId.split("_")[0];
        $('div[data-siteModule-name="'+moduleName+'"].'+currentTabId+'_module_switch').find("div.switch-animate").removeClass("switch-on switch-off").addClass("switch-"+status);
        if(status === 'on'){
            $('div[data-siteModule-name='+moduleName+'].'+currentTabId+'_module_switch>div>input').attr("checked",true);
        }else{
            $('div[data-siteModule-name='+moduleName+'].'+currentTabId+'_module_switch>div>input').removeAttr("checked");
        }
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
        
        $("."+currentTabId+"_module_switch").each(function (index, el) {
            var moduleName = $(el).attr('data-siteModule-name');
            switchButtonWithoutEvent(moduleName,val);
        });
    });

    $("body").on('switch-change', 'div[data-siteModule-name]', function (e, data) {
        var currentTabId = activeTabId.split("_")[0];
        var moduleName = $(this).attr("data-siteModule-name");
        var value 	   = data.value;
        var isInactive = false;
        var isActive   = true;

        if(value === isInactive) {


            $("h4#meliscore-tool-module-content-title").html(translations.tr_meliscore_module_management_checking_dependencies);
            $('.'+currentTabId+'_module_switch').bootstrapSwitch('setActive', false);

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
                        },
                        function() {
                            switchButtonWithoutEvent(moduleName, "on");
                        }
                    );
                }else{
                    switchButtonWithoutEvent(moduleName, "off");
                    // $('div[data-siteModule-name='+moduleName+']').find("div.switch-animate").removeClass("switch-on switch-off").addClass("switch-off");
                }
                $('.'+currentTabId+'_module_switch').bootstrapSwitch('setActive', true);
                $("h4#meliscore-tool-module-content-title").html(translations.tr_meliscore_module_management_modules);
            });
        }


        if(value === isActive) {
            $("h4#meliscore-tool-module-content-title").html(translations.tr_meliscore_module_management_checking_dependencies);
            $('.'+currentTabId+'_module_switch').bootstrapSwitch('setActive', false);

            $.ajax({
                type        : 'POST',
                url         : '/melis/MelisCms/SitesModuleLoader/getRequiredDependencies?siteId='+currentTabId,
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
                                switchButtonWithoutEvent(v, "on");
                            });
                            switchButtonWithoutEvent(moduleName, "on");
                        },
                        function() {
                            switchButtonWithoutEvent(moduleName, "off");
                        }
                    );


                }else{
                    switchButtonWithoutEvent(moduleName, "on");
                    // $('div[data-siteModule-name='+moduleName+']').find("div.switch-animate").removeClass("switch-on switch-off").addClass("switch-off");
                }
                $('.'+currentTabId+'_module_switch').bootstrapSwitch('setActive', true);
                $("h4#meliscore-tool-module-content-title").html(translations.tr_meliscore_module_management_modules);
            });
        }

    });

    window.moduleLoadJsCallback = function () {
        setOnOff();
        if($("#not-admin-notice").length > 0){
            $(".has-switch").bootstrapSwitch('setActive', false);
        }
    };

});

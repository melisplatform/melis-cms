$(document).ready(function() {
    $body = $("body");


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

    /**
     * variables used for module loading
     * stated detection
     */
    var selectedModule = "";
    var switchState = true;
    var isCallBackTriggered = false;

    $("body").on('switch-change', 'div[data-siteModule-name]', function (e, data) {
        var currentTabId = activeTabId.split("_")[0];
        var moduleName = $(this).attr("data-siteModule-name");
        var value 	   = data.value;
        var isInactive = false;
        var isActive   = true;
        selectedModule = moduleName;

        if(value === isInactive) {

            switchState = false;

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
                        translations.tr_meliscore_common_no,
                        translations.tr_meliscms_tool_site_module_load_activation_title,
                        data.message+modules,
                        function() {
                            $.each(data.modules, function(i, v) {
                                // this will trigger a switch-change event
                                // $('div[data-siteModule-name="'+v+'"]').bootstrapSwitch('setState', false, false);
                                // this will just trigger an animate switch
                                switchButtonWithoutEvent(v, "off");
                            });

                            isCallBackTriggered = true;
                        },
                        function() {
                            switchButtonWithoutEvent(moduleName, "on");
                            isCallBackTriggered = true;
                        }
                    );
                }else{
                    switchButtonWithoutEvent(moduleName, "off");
                    // $('div[data-siteModule-name='+moduleName+']').find("div.switch-animate").removeClass("switch-on switch-off").addClass("switch-off");
                }
                $('.'+currentTabId+'_module_switch').bootstrapSwitch('setActive', true);
                $("h4#meliscore-tool-module-content-title").html(translations.tr_meliscore_module_management_modules);

                setTimeout(function(){
                    $("body").find(".confirm-modal-header").addClass("module-modal-dependency-checker");
                },200);
            });
        }


        if(value === isActive) {

            switchState = true;

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
                        translations.tr_meliscore_common_nope,
                        translations.tr_meliscms_tool_site_module_load_activation_title,
                        data.message+modules+translations.tr_melis_cms_sites_module_loading_activate_module_with_prerequisites_notice_confirmation,
                        function() {
                            $.each(data.modules, function(i, v) {
                                // this will trigger a switch-change event
                                // $('div[data-siteModule-name="'+v+'"]').bootstrapSwitch('setState', false, false);
                                // this will just trigger an animate switch
                                switchButtonWithoutEvent(v, "on");
                                isCallBackTriggered = true;
                            });
                            switchButtonWithoutEvent(moduleName, "on");
                        },
                        function() {
                            switchButtonWithoutEvent(moduleName, "on");
                            isCallBackTriggered = true;
                        }
                    );


                }else{
                    switchButtonWithoutEvent(moduleName, "on");
                    // $('div[data-siteModule-name='+moduleName+']').find("div.switch-animate").removeClass("switch-on switch-off").addClass("switch-off");
                }
                $('.'+currentTabId+'_module_switch').bootstrapSwitch('setActive', true);
                $("h4#meliscore-tool-module-content-title").html(translations.tr_meliscore_module_management_modules);

                setTimeout(function(){
                    $("body").find(".confirm-modal-header").addClass("module-modal-dependency-checker");
                },200);
            });
        }
    });

    //hide the selected module on modal close
    $(document).on("hidden.bs.modal", ".module-modal-dependency-checker", function(){
        if(isCallBackTriggered === false) {
            if (switchState === true) {
                switchButtonWithoutEvent(selectedModule, "off");
            } else {
                switchButtonWithoutEvent(selectedModule, "on");
            }
        }

        isCallBackTriggered = false;
        switchState = false;
        selectedModule = "";
    });

    window.moduleLoadJsCallback = function () {
        setOnOff();
        if($("#not-admin-notice").length > 0){
            $(".has-switch").bootstrapSwitch('setActive', false);
        }
    };

});

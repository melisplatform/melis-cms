$(document).ready(function() {

    $body = $("body");

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

});

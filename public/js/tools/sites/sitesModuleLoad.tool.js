$(document).ready(function() {

    $body = $("body");

    /**
     * This block of code handles the main switch in site module loading
     * where will switch on/off all modules.
     */
    if($("#select-deselect-all-module").length > 0){
        $body.on("switch-change", "#select-deselect-all-module", function(e, data){

            var val = "";
            if(data.value === false){
                val = "off";
            }else{
                val = "on";
            }
            $('.module-switch').find("div.switch-animate").removeClass("switch-on switch-off").addClass("switch-"+val);
        });
    }

});

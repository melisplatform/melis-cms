$(document).ready(function(){
    var $body = $("body");

    /**
     * Process the pages export
     */
    $body.on("click", "#btn-export-pages", function(){
        $(".page-export-form").addClass("hidden");
        $(".page-export-progress prog_percent").text(20);
        updateProgressValue(20);
    });

    /**
     * Function to show progress
     * on exporting/importing pages
     *
     * @param val
     */
    function updateProgressValue(val) {
        $("div#exportImportProgressbar").attr("arial-valuenow", val)
            .css("width", val + "%")
            .parent().parent().removeClass("hidden");
    }
});
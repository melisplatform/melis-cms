$(document).ready(function(){
    var $body = $("body");

    /**
     * Process the pages export
     */
    $body.on("click", "#btn-export-pages", function(){
        $(".page-export-form").addClass("hidden");
        $(".page-export-progress prog_percent").text(20);

        var dataString = $("#pageExportForm").serializeArray();

        //send ajax request to export pages
        $.ajax({
            url: '/melis/MelisCms/PageExport/exportPage',
            data: dataString,
            type: 'JSON',
            beforeSend: function(){
                updateProgressValue(20);
            },
        }).success(function(data){
            updateProgressValue(100);
            console.log(data);
        }).error(function(data){

        });
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
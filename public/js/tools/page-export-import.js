$(document).ready(function(){
    var $body = $("body");

    /**
     * Process the pages export
     */
    $body.on("click", "#btn-export-pages", function(){
        updateProgressValue(0);

        $(".page-export-form").addClass("hidden");

        var dataString = $("#pageExportForm").serializeArray();

        //send ajax request to export pages
        $.ajax({
            url: '/melis/MelisCms/PageExport/exportPage',
            data: dataString,
            type: 'POST',
            dataType: 'text',
            mimeType: 'text/plain; charset=x-user-defined',
            beforeSend: function(){
                setTimeout(function(){
                    updateProgressValue(20);
                }, 100);
            }
        }).success(function(data, status, request){
            updateProgressValue(100);

            var fileName = request.getResponseHeader("fileName");
            var mime = request.getResponseHeader("Content-Type");

            var newContent = "";
            for (var i = 0; i < data.length; i++) {
                newContent += String.fromCharCode(data.charCodeAt(i) & 0xFF);
            }
            var bytes = new Uint8Array(newContent.length);
            for (var i=0; i<newContent.length; i++) {
                bytes[i] = newContent.charCodeAt(i);
            }
            var blob = new Blob([bytes], {type: mime})
            saveAs(blob, fileName);
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
        $(".page-export-progress prog_percent").text(val);

        $("div#exportImportProgressbar").attr("arial-valuenow", val)
            .css("width", val + "%")
            .parent().parent().removeClass("hidden");
    }
});
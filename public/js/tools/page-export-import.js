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

    /**
     * Test File
     */
    $body.on('click', '#page-tree-import-test', function () {
        submitImportForm($('#id_meliscms_tree_sites_import_page_form'));
    });

    function submitImportForm (form) {
        form.unbind("submit");
        form.on("submit", function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                type: 'POST',
                url: '/melis/MelisCms/PageImport/checkImportForm',
                data: formData,
                cache: false,
                contentType: false,
                processData: false
            }).success(function (data) {
                if (data.success) {
                    importTest(data.result);
                } else {
                    melisHelper.melisKoNotification('test tittle', 'test message', data.errors);
                }
            }).error(function (data) {

            });
        });

        form.submit();
    }

    function importTest(formData) {
        $.ajax({
            type: 'POST',
            url: '/melis/MelisCms/PageImport/importTest',
            data: {formData: JSON.stringify(formData)},
            beforeSend: function () {
                $body.find('#pageImportConsole').css('display', '');
                $body.find('#pageImportConsole').append('<p>Name of file: ' + formData.page_tree_import.name + '</p>');
                $body.find('#pageImportConsole').append('<p>Validated: <span style="color: red;">No</span></p>');
                $body.find('#pageImportConsole').append('<div id="pageImportProcessing"><p>Processing file <i class="fa fa-spinner fa-spin"></i></p></div>');
            }
        }).success(function (data) {
            if (data.success) {
                $body.find('#importPageForm').css('display', 'none');
                $body.find('#importPageValidated').css('display', '');
                $body.find('#importPageValidated .tab-content .tab-pane').append('<p>File name: ' + formData.page_tree_import.name + '</p>');
                $body.find('#importPageValidated .tab-content .tab-pane').append('<p>Validated: <span style="color: green;">Yes</span></p>');

                var btnCancel = '<button type="button" data-dismiss="modal" class="btn btn-danger pull-left">' + translations.tr_meliscms_tool_sites_cancel + '</button>';
                var btnImport = '<button type="button" class="btn btn-success" id="page-tree-import">Import File</button>';

                $body.find('#importPageValidated .btn-container').append(btnCancel);
                $body.find('#importPageValidated .btn-container').append(btnImport);
            } else {
                $body.find('#pageImportConsole #pageImportProcessing').text('');
                $body.find('#pageImportConsole #pageImportProcessing').append('<p>Processing File Done <i style="color: green;" class="fa fa-check-circle"></i></p>');
                body.find('#pageImportConsole').append('<p style="color: red;">The file cannot be imported because of the following errors:</p>');
                $.each(data.errors, function (key, error) {
                    $body.find('#pageImportConsole').append('<p style="color: red;"> - ' + error + '</p>');
                });
            }
        }).error(function (data) {

        });
    }

    $body.on('click', '#page-tree-import', function () {
        melisCoreTool.confirm('Continue & Import file', 'cancel', 'Are you sure to import?', 'It is always a good idea to make a back-up of the database before doing such actions.', function () {

        });
    });
});
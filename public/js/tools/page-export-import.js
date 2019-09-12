$(document).ready(function(){
    var $body = $("body");
    var importFormData;
    var idsMap;

    /**
     * Process the pages export
     */
    $body.on("click", "#btn-export-pages", function(){
        var flag = true;
        melisCoreTool.pending('#btn-export-pages');
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
            var oData;

            try {
                oData = JSON.parse(data);
            } catch (e) {
                flag = false;
                updateProgressValue(100);

                var fileName = request.getResponseHeader("fileName");
                var mime = request.getResponseHeader("Content-Type");
                var newContent = "";

                for (var i = 0; i < data.length; i++) {
                    newContent += String.fromCharCode(data.charCodeAt(i) & 0xFF);
                }

                var bytes = new Uint8Array(newContent.length);

                for (var i = 0; i < newContent.length; i++) {
                    bytes[i] = newContent.charCodeAt(i);
                }

                var blob = new Blob([bytes], {type: mime});
                saveAs(blob, fileName);

                melisCore.flashMessenger();
                melisHelper.melisOkNotification(
                    translations.tr_melis_cms_tree_export_title,
                    translations.tr_melis_cms_tree_export_notification_message,
                    '#72af46'
                );
            }

            if (flag)
                melisHelper.melisKoNotification(translations.tr_melis_cms_tree_export_page, '', [oData.message]);

            $body.find('#btn-export-pages').siblings('button.btn.btn-danger.pull-left').trigger('click');

            melisCoreTool.done('#btn-export-pages');
        }).error(function(data){
            melisCoreTool.done('#btn-export-pages');
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
        melisCoreTool.pending('#page-tree-import-test');
        submitImportForm($('#id_meliscms_tree_sites_import_page_form'));
    });

    function request(url, type, data, success, error) {
        $.ajax({
            type: type,
            url: url,
            data: data,
            cache: false,
            contentType: false,
            processData: false
        }).success(success(data)).error(error);
    }

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
                    importFormData = data.result;
                } else {
                    melisHelper.melisKoNotification(translations.tr_melis_cms_page_tree_import, '', data.errors);
                    melisCoreTool.done('#page-tree-import-test');
                }
            }).error(function (data) {
                melisCoreTool.done('#page-tree-import-test');
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
                $body.find('#pageImportConsole').append('<p>' + translations.tr_melis_cms_page_tree_import_name_of_file + ': ' + formData.page_tree_import.name + '</p>');
                $body.find('#pageImportConsole').append('<p>' + translations.tr_melis_cms_page_tree_import_validated + ': <span style="color: red;">' + translations.tr_meliscms_common_no + '</span></p>');
                $body.find('#pageImportConsole').append('<div id="pageImportProcessing"><p>' + translations.tr_melis_cms_page_tree_import_modal_processing + ' <i class="fa fa-spinner fa-spin"></i></p></div>');
            }
        }).success(function (data) {
            if (data.success) {
                $body.find('#importPageForm').css('display', 'none');
                $body.find('#importPageValidated').css('display', '');
                $body.find('#importPageValidated .tab-content .tab-pane').append('<p>' + translations.tr_melis_cms_page_tree_import_name_of_file + ': ' + formData.page_tree_import.name + '</p>');
                $body.find('#importPageValidated .tab-content .tab-pane').append('<p>' + translations.tr_melis_cms_page_tree_import_validated +': <span style="color: green;">' + translations.tr_meliscms_common_yes + '</span></p>');

                var btnCancel = '<button type="button" data-dismiss="modal" class="btn btn-danger pull-left">' + translations.tr_meliscms_tool_sites_cancel + '</button>';
                var btnImport = '<button type="button" class="btn btn-success" id="page-tree-import">' + translations.tr_melis_cms_page_tree_import_file + '</button>';

                $body.find('#importPageValidated .btn-container').append(btnCancel);
                $body.find('#importPageValidated .btn-container').append(btnImport);
            } else {
                $body.find('#pageImportConsole #pageImportProcessing').text('');
                $body.find('#pageImportConsole #pageImportProcessing').append('<p>' + translations.tr_melis_cms_page_tree_import_modal_processing_done + ' <i style="color: green;" class="fa fa-check-circle"></i></p>');
                body.find('#pageImportConsole').append('<p style="color: red;">' + translations.tr_melis_cms_page_tree_import_modal_errors + ':</p>');
                $.each(data.errors, function (key, error) {
                    $body.find('#pageImportConsole').append('<p style="color: red;"> - ' + error + '</p>');
                });
            }
            melisCoreTool.done('#page-tree-import-test');
        }).error(function (data) {
            melisCoreTool.done('#page-tree-import-test');
        });
    }

    $body.on('click', '#page-tree-import', function () {
        var pageid = $(this).closest('#id_meliscms_page_import_modal').data('pageid');

        melisCoreTool.confirm(
            translations.tr_melis_cms_page_tree_import_continue_and_import,
            translations.tr_meliscms_tool_sites_cancel,
            translations.tr_melis_cms_page_tree_import_modal_are_you_sure,
            translations.tr_melis_cms_page_tree_import_modal_are_you_sure_content,
            function () {
                melisCoreTool.pending('#page-tree-import');
                $.ajax({
                    type: 'POST',
                    url: '/melis/MelisCms/PageImport/importPage',
                    data: {
                        formData: JSON.stringify(importFormData),
                        pageid: pageid
                    },
                    beforeSend: function () {
                        $body.find('#pageImportConsole').css('display', '');
                        $body.find('#pageImportConsole').append('<p>' + translations.tr_melis_cms_page_tree_import_name_of_file + ': ' + importFormData.page_tree_import.name + '</p>');
                        $body.find('#pageImportConsole').append('<p>' + translations.tr_melis_cms_page_tree_import_validated + ': <span style="color: red;">' + translations.tr_meliscms_common_no + '</span></p>');
                        $body.find('#pageImportConsole').append('<div id="pageImportProcessing"><p>' + translations.tr_melis_cms_page_tree_import_modal_processing + ' <i class="fa fa-spinner fa-spin"></i></p></div>');
                    }
                }).success(function (data) {
                    var btnClose = '<button type="button" id="importPageDoneClose" data-dismiss="modal" class="btn btn-danger pull-left" style="margin-top: -15px; margin-left: -15px;">' + translations.tr_melis_cms_page_tree_import_close + '</button>';

                    $body.find('#importPageValidated').css('display', 'none');
                    $body.find('#importPageDone').css('display', '');

                    if (data.success) {
                        idsMap = data.idsMap;
                        $body.find('#importPageDone .tab-content .tab-pane .main-error').append('<p>' + translations.tr_melis_cms_page_tree_import_name_of_file + ': ' + importFormData.page_tree_import.name + '</p>');
                        $body.find('#importPageDone .tab-content .tab-pane .main-error').append('<p>' + translations.tr_melis_cms_page_tree_import_result + ': <span style="color: green;">' + translations.tr_melis_cms_page_tree_import_success + '</span></p>');
                        $body.find('#importPageDone .tab-content .tab-pane .main-error').append('</br>');
                        var text = translations.tr_melis_cms_page_tree_import_modal_done + data.pagesCount + translations.tr_melis_cms_page_tree_import_modal_done2_p;

                        if (data.pagesCount == 1) {
                            text = translations.tr_melis_cms_page_tree_import_modal_done + data.pagesCount + translations.tr_melis_cms_page_tree_import_modal_done2_s;
                        }

                        $body.find('#importPageDone .tab-content .tab-pane .main-error').append(text);

                        $body.find('#importPageDone .tab-content .tab-pane .main-error').append('</br>');
                        $body.find('#importPageDone .tab-content .tab-pane .main-error').append('</br>');

                        if (!data.keepIds) {
                            $body.find('#importPageDone .tab-content .tab-pane .main-error').append('<p><i style="color:red;">NOTE: </i>' + translations.tr_melis_cms_page_tree_import_file_final_message_csv + '</p>');
                        } else {
                            $body.find('#importPageDone .tab-content .tab-pane .main-error').append('<p><i style="color:red;">NOTE: </i>' + translations.tr_melis_cms_page_tree_import_file_final_message_csv_keep_ids + '</p>');
                        }

                        $body.find('#importPageDone .btn-container').append(btnClose);

                        melisCore.flashMessenger();
                        melisHelper.melisOkNotification(
                            translations.tr_melis_cms_page_tree_import_title,
                            translations.tr_melis_cms_page_tree_import_notification_message,
                            '#72af46'
                        );

                        $("#id-mod-menu-dynatree").fancytree("destroy");
                        mainTree();

                        // download for csv mapping array
                        $.ajax({
                            type: 'POST',
                            url: '/melis/MelisCms/PageImport/exportCsv',
                            data: {
                                idsMap: idsMap
                            },
                            success: function (data, textStatus, request) {
                                if (data) {
                                    var fileName = request.getResponseHeader("fileName");
                                    var mime = request.getResponseHeader("Content-Type");
                                    var blob = new Blob([request.responseText], {type: mime});
                                    saveAs(blob, fileName);
                                }
                            }
                        });
                    } else {
                        $body.find('#importPageDone .tab-content .tab-pane .main-error').append('<p>' + translations.tr_melis_cms_page_tree_import_name_of_file + ': ' + importFormData.page_tree_import.name + '</p>');
                        $body.find('#importPageDone .tab-content .tab-pane .main-error').append('<p>' + translations.tr_melis_cms_page_tree_import_result + ': <span style="color: red;">' + translations.tr_melis_cms_page_tree_import_failed + '</span></p>');
                        $body.find('#importPageDone .tab-content .tab-pane .main-error').append('</br>');
                        $body.find('#importPageDone .tab-content .tab-pane .main-error').append(translations.tr_melis_cms_page_tree_import_modal_unexpected_errors + translations.tr_melis_cms_page_tree_import_modal_unexpected_errors2 + '</br>' + translations.tr_melis_cms_page_tree_import_modal_unexpected_error_detail + '</br>');

                        $body.find('#pageImportDoneConsole').css('display', '');

                        $.each(data.errors, function (key, error) {
                            $body.find('#importPageDone #pageImportDoneConsole').append('<p style="color: red;"> - ' + error + '</p>');
                        });

                        $body.find('#importPageDone .btn-container').append(btnClose);
                    }
                    melisCoreTool.done('#page-tree-import');
                }). error(function() {
                    melisCoreTool.done('#page-tree-import');
                });
            }
        );
    });
});
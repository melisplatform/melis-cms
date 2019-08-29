$(function() {
    $("body").on("click", "a.melis-pageduplicate", function() {
        var pagenumber = $(this).data().pagenumber;
        $.ajax({
            type        : 'POST',
            url         : '/melis/MelisCms/PageDuplication/duplicate-page',
            data        : {id : pagenumber},
            dataType    : 'json',
            encode		: true,
            success: function(data) {
                if(data.success) {
                    melisCms.refreshTreeview(data.response.pageId);
                    if(data.response.openPageAfterDuplicate) {
                        // open page
                        melisHelper.tabOpen( data.response.name, data.response.icon, data.response.pageId + '_id_meliscms_page', 'meliscms_page',  { idPage: data.response.pageId} );
                    }
                    melisHelper.melisOkNotification(data.textTitle, data.textMessage);
                }
                else {
                    melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors, 0);
                }
                melisCore.flashMessenger();
            },
            error: function(xhr, textStatus, errorThrown) {
                alert( translations.tr_meliscore_error_message );
            }
        });
    });
});
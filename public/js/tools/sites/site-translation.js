$(document).ready(function(){
    var body = $("body");
    var mst_id = 0;
    var mstt_id = 0;
    var transZoneKey = "meliscms_tool_sites_site_translations";

    /**
     * This will trigger the language filter
     */
    body.on("change", "#siteTranslationLanguageName", function(){
        var tableId = $(this).parents().eq(6).find('table').attr('id');
        $("#"+tableId).DataTable().ajax.reload();
    });

    /**
     * This will refresh the table
     */
    body.on("click", ".mt-tr-refresh", function(){
        var siteId = activeTabId.split("_")[0];
        melisHelper.zoneReload(siteId+'_id_meliscms_tool_sites_site_translations', transZoneKey, {siteId:siteId}, function(){
            $("#"+siteId+'_id_meliscms_tool_sites_site_translations').addClass("active");
        });
    });

    /**
     * This will edit the translation
     */
    body.on("click", ".btnEditSiteTranslation", function(){
        var zoneId = "id_meliscms_tool_sites_site_translations_modal_edit";
        var melisKey = "meliscms_tool_sites_site_translations_modal_edit";
        var modalUrl = "/melis/MelisCms/SitesTranslation/renderToolSitesSiteTranslationModal";

        var langId = $(this).closest("tr").attr('data-lang-id');
        var siteId = $(this).closest("tr").attr('data-site-id');
        var key = $(this).closest("tr").find('td:first').text();

        mstt_id = $(this).closest("tr").attr('data-mstt-id');
        mst_id = $(this).closest("tr").attr('data-mst-id');

        melisHelper.createModal(zoneId, melisKey, true, {translationKey:key, langId:langId, siteId:siteId},  modalUrl);
    });

    /**
     * This will delete the translation
     */
    body.on("click", "#btnDeleteSiteTranslation", function(e){
        var siteId = activeTabId.split("_")[0];
        var t_id = $(this).closest("tr").attr('data-mst-id');
        var tt_id = $(this).closest("tr").attr('data-mstt-id');
        var obj = {};
        obj.mst_id = t_id;
        obj.mstt_id = tt_id;

        if(t_id != 0 && t_id != "") {
            melisCoreTool.confirm(
                translations.tr_meliscore_common_yes,
                translations.tr_meliscore_common_no,
                translations.tr_melis_site_translation_name,
                translations.tr_melis_site_translation_delete_confirm,
                function() {
                    $.ajax({
                        type: 'POST',
                        url: '/melis/MelisCms/SitesTranslation/deleteTranslation',
                        data: $.param(obj)
                    }).done(function (data) {
                        //process the returned data
                        if (data.success) {//success
                            melisHelper.melisOkNotification(translations.tr_meliscore_common_success, translations.tr_melis_site_translation_delete_success);
                            melisHelper.zoneReload(siteId+'_id_meliscms_tool_sites_site_translations', transZoneKey, {siteId:siteId}, function(){
                                $("#"+siteId+'_id_meliscms_tool_sites_site_translations').addClass("active");
                            });
                        }
                    });
                });
        }
        e.preventDefault();
    });

    /**
     * This will save the translation
     */
    body.on("click", ".btnSaveSiteTranslation", function(e){
        var siteId = activeTabId.split("_")[0];
        // var form = $("#site-translation-form");
        var form = $("form[name='sitestranslationform']").serializeArray();

        $.ajax({
            type        : 'POST',
            url         : '/melis/MelisCms/SitesTranslation/saveTranslation',
            data		   : $.param(form)
        }).done(function(data) {
            //process the returned data
            if(data.success){//success
                if(mst_id == 0) {
                    melisHelper.melisOkNotification(translations.tr_meliscore_common_success, translations.tr_melis_site_translation_inserting_success);
                }else{
                    melisHelper.melisOkNotification(translations.tr_meliscore_common_success, translations.tr_melis_site_translation_update_success);
                }
                //remove highlighted label
                // melisCoreTool.highlightErrors(1, null, "site-translation-form");
                $("#modal-site-translation").modal("hide");
                melisHelper.zoneReload(siteId+'_id_meliscms_tool_sites_site_translations', transZoneKey,{siteId:siteId}, function(){
                    mst_id = 0;
                    mstt_id = 0;
                    $("#"+siteId+'_id_meliscms_tool_sites_site_translations').addClass("active");
                });
            }else{//failed
                //show errors
                melisHelper.melisKoNotification(translations.tr_melis_site_translations, translations.tr_melis_site_translation_save_failed, data.errors);
                //highlight errors
                $.each(data.langErrorIds, function(i, langId){
                    melisCoreTool.highlightErrors(0, data.errors, langId+"_site-translation-form");
                });
            }
        });
        e.preventDefault();
    });
});

/**
 * Remove the delete button if the
 * translation is came from the file
 * @param data
 * @param tblSetting
 */
window.initSiteTranslationTable = function(data, tblSetting){
    //hide delete button if data-mst-id is 0
    $("#"+activeTabId.split("_")[0]+"_tableMelisSiteTranslation tbody tr[data-mst-id='0']").find("#btnDeleteSiteTranslation").remove();
};

/**
 * This will prepare to add the additional
 * data of the translation
 * @param data
 */
window.initAdditionalTransParam = function(data){
    if($('#siteTranslationLanguageName').length){
        data.site_translation_language_name = $('#siteTranslationLanguageName').val();
    }
    data.siteId = activeTabId.split("_")[0];
};
